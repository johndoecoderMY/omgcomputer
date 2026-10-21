@extends('layouts.app')

@section('title', isset($dramaScript->id) ? 'Edit Drama' : 'Create Drama')

@section('content')
@php
    $characters = old('characters', $dramaScript->characters);
    $characters = is_array($characters) ? $characters : [];
    $scenes = old('scenes', $dramaScript->scenes);
    $scenes = is_array($scenes) ? $scenes : [];
@endphp
<div class="container-fluid py-3">
    <x-page-header eyebrow="Creative studio" :title="isset($dramaScript->id) ? 'Edit drama' : 'Create drama'"
        :back-url="route('drama-content.index')" back-label="All drama content" />

    <form method="POST" enctype="multipart/form-data" action="{{ isset($dramaScript->id) ? route('drama-content.update', $dramaScript) : route('drama-content.store') }}">
        @csrf
        @if (isset($dramaScript->id))
            @method('PUT')
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-semibold mb-1">Please correct the following:</p>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card p-2 mb-3">
            <div class="card-header bg-body">
                <h2 class="h5 mb-1">Drama outline</h2>
                <p class="small text-body-secondary mb-0">Build a reusable story brief before preparing individual scenes.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="drama-title">Title</label>
                        <input class="form-control" id="drama-title" name="title" maxlength="180" required value="{{ old('title', $dramaScript->title) }}">
                        @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="drama-genre">Genre</label>
                        <input class="form-control" id="drama-genre" name="genre" maxlength="80" value="{{ old('genre', $dramaScript->genre) }}" placeholder="e.g. Drama, thriller">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="drama-status">Status</label>
                        <select class="form-select" id="drama-status" name="status">
                            <option value="draft" @selected(old('status', $dramaScript->status) === 'draft')>Draft</option>
                            <option value="ready" @selected(old('status', $dramaScript->status) === 'ready')>Ready for AI</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="drama-synopsis">Synopsis</label>
                        <textarea class="form-control" id="drama-synopsis" name="synopsis" rows="3" maxlength="5000">{{ old('synopsis', $dramaScript->synopsis) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <section class="card p-2 mb-3" aria-labelledby="drama-characters-heading">
            <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1" id="drama-characters-heading">Characters</h2>
                    <p class="small text-body-secondary mb-0">Describe appearance, personality, voice, or continuity details.</p>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" id="add-drama-character">Add character</button>
            </div>
            <div class="card-body">
                <div id="drama-characters" class="row g-3">
                    @foreach ($characters as $key => $character)
                        @php($characterId = $character['id'] ?? (string) \Illuminate\Support\Str::uuid())
                        <div class="col-12 col-xl-6" data-character-row data-character-id="{{ $characterId }}">
                            <div class="border rounded p-3 h-100">
                                <input type="hidden" name="characters[{{ $key }}][id]" value="{{ $characterId }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Character</span>
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-remove-character>Remove</button>
                                </div>
                                <label class="form-label" for="character-{{ $characterId }}">Name</label>
                                <input class="form-control mb-2" id="character-{{ $characterId }}" name="characters[{{ $key }}][name]" value="{{ $character['name'] ?? '' }}" maxlength="120" required>
                                <label class="form-label" for="character-description-{{ $characterId }}">Character reference / description</label>
                                <textarea class="form-control" id="character-description-{{ $characterId }}" name="characters[{{ $key }}][description]" rows="3" maxlength="3000" placeholder="Appearance, age, personality, clothing, voice, or visual consistency notes">{{ $character['description'] ?? '' }}</textarea>
                                <label class="form-label mt-3" for="character-image-{{ $characterId }}">Character reference image</label>
                                @if (!empty($character['image_path']))
                                    <img class="d-block rounded mb-2" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($character['image_path']) }}" alt="{{ $character['name'] ?? 'Character' }} reference" style="max-width: 180px; max-height: 180px; object-fit: cover">
                                @endif
                                <x-image-upload-input :id="'character-image-'.$characterId" :name="'character_images['.$characterId.']'" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card p-2 mb-3" aria-labelledby="drama-scenes-heading">
            <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1" id="drama-scenes-heading">Scenes</h2>
                    <p class="small text-body-secondary mb-0">Write each scene, assign its characters, and attach reference links or notes.</p>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" id="add-drama-scene">Add scene</button>
            </div>
            <div class="card-body">
                <div id="drama-scenes" class="d-flex flex-column gap-3">
                    @foreach ($scenes as $key => $scene)
                        @php($sceneId = $scene['id'] ?? (string) \Illuminate\Support\Str::uuid())
                        @php($references = $scene['references'] ?? '')
                        @if (is_array($references))
                            @php($references = implode("\n", $references))
                        @endif
                        <article class="border rounded p-3" data-scene-row>
                            <input type="hidden" name="scenes[{{ $key }}][id]" value="{{ $sceneId }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 mb-0">Scene</h3>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-scene>Remove scene</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="scene-title-{{ $sceneId }}">Scene title</label>
                                    <input class="form-control" id="scene-title-{{ $sceneId }}" name="scenes[{{ $key }}][title]" value="{{ $scene['title'] ?? '' }}" maxlength="180" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="scene-setting-{{ $sceneId }}">Setting / location</label>
                                    <input class="form-control" id="scene-setting-{{ $sceneId }}" name="scenes[{{ $key }}][setting]" value="{{ $scene['setting'] ?? '' }}" maxlength="500" placeholder="Place, time, atmosphere">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="scene-time-{{ $sceneId }}">Scene time</label>
                                    <input class="form-control" id="scene-time-{{ $sceneId }}" name="scenes[{{ $key }}][time]" type="time" value="{{ $scene['time'] ?? '' }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="scene-script-{{ $sceneId }}">Scene script</label>
                                    <textarea class="form-control" id="scene-script-{{ $sceneId }}" name="scenes[{{ $key }}][script]" rows="6" maxlength="20000" required placeholder="Action, dialogue, and transitions">{{ $scene['script'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="scene-characters-{{ $sceneId }}">Characters in this scene</label>
                                    <select class="form-select" id="scene-characters-{{ $sceneId }}" name="scenes[{{ $key }}][character_ids][]" multiple>
                                        @foreach ($characters as $character)
                                            @php($optionId = $character['id'] ?? '')
                                            @if ($optionId !== '')
                                                <option value="{{ $optionId }}" @selected(in_array($optionId, $scene['character_ids'] ?? [], true))>{{ $character['name'] ?? 'Unnamed character' }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="form-text">Use Ctrl or Command to select multiple characters.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="scene-references-{{ $sceneId }}">Scene references</label>
                                    <textarea class="form-control" id="scene-references-{{ $sceneId }}" name="scenes[{{ $key }}][references]" rows="3" maxlength="10000" placeholder="One URL or visual/audio reference note per line">{{ $references }}</textarea>
                                    <label class="form-label mt-3" for="scene-image-{{ $sceneId }}">Scene reference image</label>
                                    @if (!empty($scene['image_path']))
                                        <img class="d-block rounded mb-2" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($scene['image_path']) }}" alt="{{ $scene['title'] ?? 'Scene' }} reference" style="max-width: 180px; max-height: 180px; object-fit: cover">
                                    @endif
                                    <x-image-upload-input :id="'scene-image-'.$sceneId" :name="'scene_images['.$sceneId.']'" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="scene-prompt-{{ $sceneId }}">AI generation prompt</label>
                                    <textarea class="form-control" id="scene-prompt-{{ $sceneId }}" name="scenes[{{ $key }}][ai_prompt]" rows="4" maxlength="10000" placeholder="Optional prompt/instructions for generating this scene">{{ $scene['ai_prompt'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a class="btn btn-outline-secondary" href="{{ route('drama-content.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save drama</button>
        </div>
    </form>
</div>

<template id="drama-character-template">
    <div class="col-12 col-xl-6" data-character-row data-character-id="__ID__">
        <div class="border rounded p-3 h-100">
            <input type="hidden" name="characters[__ID__][id]" value="__ID__">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold">Character</span>
                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-character>Remove</button>
            </div>
            <label class="form-label" for="character-__ID__">Name</label>
            <input class="form-control mb-2" id="character-__ID__" name="characters[__ID__][name]" maxlength="120" required>
            <label class="form-label" for="character-description-__ID__">Character reference / description</label>
            <textarea class="form-control" id="character-description-__ID__" name="characters[__ID__][description]" rows="3" maxlength="3000" placeholder="Appearance, age, personality, clothing, voice, or visual consistency notes"></textarea>
            <label class="form-label mt-3" for="character-image-__ID__">Character reference image</label>
            <x-image-upload-input id="character-image-__ID__" name="character_images[__ID__]" />
        </div>
    </div>
</template>

<template id="drama-scene-template">
    <article class="border rounded p-3" data-scene-row>
        <input type="hidden" name="scenes[__ID__][id]" value="__ID__">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0">Scene</h3>
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-scene>Remove scene</button>
        </div>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="scene-title-__ID__">Scene title</label>
                <input class="form-control" id="scene-title-__ID__" name="scenes[__ID__][title]" maxlength="180" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scene-setting-__ID__">Setting / location</label>
                <input class="form-control" id="scene-setting-__ID__" name="scenes[__ID__][setting]" maxlength="500" placeholder="Place, time, atmosphere">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scene-time-__ID__">Scene time</label>
                <input class="form-control" id="scene-time-__ID__" name="scenes[__ID__][time]" type="time">
            </div>
            <div class="col-12">
                <label class="form-label" for="scene-script-__ID__">Scene script</label>
                <textarea class="form-control" id="scene-script-__ID__" name="scenes[__ID__][script]" rows="6" maxlength="20000" required placeholder="Action, dialogue, and transitions"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="scene-characters-__ID__">Characters in this scene</label>
                <select class="form-select" id="scene-characters-__ID__" name="scenes[__ID__][character_ids][]" multiple></select>
                <div class="form-text">Use Ctrl or Command to select multiple characters.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="scene-references-__ID__">Scene references</label>
                <textarea class="form-control" id="scene-references-__ID__" name="scenes[__ID__][references]" rows="3" maxlength="10000" placeholder="One URL or visual/audio reference note per line"></textarea>
                <label class="form-label mt-3" for="scene-image-__ID__">Scene reference image</label>
                <x-image-upload-input id="scene-image-__ID__" name="scene_images[__ID__]" />
            </div>
            <div class="col-12">
                <label class="form-label" for="scene-prompt-__ID__">AI generation prompt</label>
                <textarea class="form-control" id="scene-prompt-__ID__" name="scenes[__ID__][ai_prompt]" rows="4" maxlength="10000" placeholder="Optional prompt/instructions for generating this scene"></textarea>
            </div>
        </div>
    </article>
</template>

<script>
    (() => {
        const characterList = document.getElementById('drama-characters');
        const sceneList = document.getElementById('drama-scenes');
        const characterTemplate = document.getElementById('drama-character-template');
        const sceneTemplate = document.getElementById('drama-scene-template');

        const addCharacterOption = (id, name) => {
            sceneList.querySelectorAll('select[name$="[character_ids][]"]').forEach((select) => {
                if (!select.querySelector(`option[value="${id}"]`)) {
                    select.add(new Option(name || 'Unnamed character', id));
                }
            });
        };

        document.getElementById('add-drama-character').addEventListener('click', () => {
            const id = crypto.randomUUID();
            characterList.insertAdjacentHTML('beforeend', characterTemplate.innerHTML.replaceAll('__ID__', id));
            addCharacterOption(id, 'Unnamed character');
        });

        document.getElementById('add-drama-scene').addEventListener('click', () => {
            const id = crypto.randomUUID();
            const sceneHtml = sceneTemplate.innerHTML.replaceAll('__ID__', id);
            sceneList.insertAdjacentHTML('beforeend', sceneHtml);
            const select = sceneList.querySelector(`#scene-characters-${id}`);
            characterList.querySelectorAll('[data-character-row]').forEach((row) => {
                const characterId = row.dataset.characterId;
                const name = row.querySelector('input[name$="[name]"]').value;
                select.add(new Option(name || 'Unnamed character', characterId));
            });
        });

        document.addEventListener('input', (event) => {
            if (!event.target.matches('#drama-characters input[name$="[name]"]')) {
                return;
            }
            const row = event.target.closest('[data-character-row]');
            sceneList.querySelectorAll(`option[value="${row.dataset.characterId}"]`).forEach((option) => {
                option.textContent = event.target.value || 'Unnamed character';
            });
        });

        document.addEventListener('click', (event) => {
            const removeCharacter = event.target.closest('[data-remove-character]');
            if (removeCharacter) {
                const row = removeCharacter.closest('[data-character-row]');
                const id = row.dataset.characterId;
                row.remove();
                sceneList.querySelectorAll(`option[value="${id}"]`).forEach((option) => option.remove());
                return;
            }

            const removeScene = event.target.closest('[data-remove-scene]');
            if (removeScene) {
                removeScene.closest('[data-scene-row]').remove();
            }
        });
    })();
</script>
@endsection
