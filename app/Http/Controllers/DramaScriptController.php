<?php

namespace App\Http\Controllers;

use App\Models\DramaScript;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DramaScriptController extends Controller
{
    public function index(): View
    {
        return view('drama-content.index', [
            'scripts' => DramaScript::query()->with('creator')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('drama-content.form', [
            'dramaScript' => new DramaScript([
                'status' => 'draft',
                'characters' => [['id' => (string) Str::uuid(), 'name' => '', 'description' => '']],
                'scenes' => [[
                    'id' => (string) Str::uuid(),
                    'title' => '',
                    'setting' => '',
                    'script' => '',
                    'ai_prompt' => '',
                    'character_ids' => [],
                    'references' => [],
                ]],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $script = DB::transaction(function () use ($data, $request): DramaScript {
            $script = DramaScript::create([
                ...$data,
                'created_by_user_id' => $request->user()->id,
            ]);

            $this->storeReferenceImages($request, $script);

            return $script;
        });

        return redirect()->route('drama-content.edit', $script)
            ->with('success', 'Drama content created.');
    }

    public function edit(DramaScript $dramaScript): View
    {
        return view('drama-content.form', compact('dramaScript'));
    }

    public function update(Request $request, DramaScript $dramaScript): RedirectResponse
    {
        $data = $this->validatedData($request);
        $oldPaths = $this->referenceImagePaths($dramaScript);
        $characterPaths = collect($dramaScript->characters ?? [])->pluck('image_path', 'id');
        $scenePaths = collect($dramaScript->scenes ?? [])->pluck('image_path', 'id');
        foreach ($data['characters'] as &$character) {
            if ($characterPaths->has($character['id'])) {
                $character['image_path'] = $characterPaths->get($character['id']);
            }
        }
        unset($character);
        foreach ($data['scenes'] as &$scene) {
            if ($scenePaths->has($scene['id'])) {
                $scene['image_path'] = $scenePaths->get($scene['id']);
            }
        }
        unset($scene);
        DB::transaction(function () use ($data, $dramaScript, $request): void {
            $dramaScript->update($data);
            $this->storeReferenceImages($request, $dramaScript);
        });
        $newPaths = $this->referenceImagePaths($dramaScript->fresh());
        $removedPaths = array_diff($oldPaths, $newPaths);
        if ($removedPaths !== [] && ! Storage::disk('public')->delete($removedPaths)) {
            throw new \RuntimeException('Could not remove replaced drama reference images.');
        }

        return redirect()->route('drama-content.edit', $dramaScript)
            ->with('success', 'Drama content saved.');
    }

    public function destroy(DramaScript $dramaScript): RedirectResponse
    {
        $imagePaths = $this->referenceImagePaths($dramaScript);
        $dramaScript->delete();
        if ($imagePaths !== [] && ! Storage::disk('public')->delete($imagePaths)) {
            throw new \RuntimeException('Could not remove drama reference images.');
        }

        return redirect()->route('drama-content.index')
            ->with('success', 'Drama content deleted.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'genre' => ['nullable', 'string', 'max:80'],
            'synopsis' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'ready'])],
            'characters' => ['required', 'array', 'min:1', 'max:50'],
            'characters.*.id' => ['required', 'uuid'],
            'characters.*.name' => ['required', 'string', 'max:120'],
            'characters.*.description' => ['nullable', 'string', 'max:3000'],
            'character_images' => ['nullable', 'array'],
            'character_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'scenes' => ['required', 'array', 'min:1', 'max:100'],
            'scenes.*.id' => ['required', 'uuid'],
            'scenes.*.title' => ['required', 'string', 'max:180'],
            'scenes.*.setting' => ['nullable', 'string', 'max:500'],
            'scenes.*.time' => ['nullable', 'date_format:H:i'],
            'scenes.*.script' => ['required', 'string', 'max:20000'],
            'scenes.*.ai_prompt' => ['nullable', 'string', 'max:10000'],
            'scenes.*.character_ids' => ['nullable', 'array'],
            'scenes.*.character_ids.*' => ['required', 'uuid'],
            'scenes.*.references' => ['nullable', 'string', 'max:10000'],
            'scene_images' => ['nullable', 'array'],
            'scene_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $characterIds = collect($data['characters'])->pluck('id')->all();
        foreach ($data['scenes'] as &$scene) {
            $sceneCharacterIds = $scene['character_ids'] ?? [];
            if (array_diff($sceneCharacterIds, $characterIds)) {
                abort(422, 'Each scene must use characters from this drama.');
            }
            $scene['character_ids'] = array_values(array_unique($sceneCharacterIds));
            $scene['time'] = $scene['time'] ?? null;
            $scene['references'] = collect(preg_split('/\r\n|\r|\n/', $scene['references'] ?? ''))
                ->map(fn (string $reference): string => trim($reference))
                ->filter()
                ->values()
                ->all();
        }
        unset($scene);

        unset($data['character_images'], $data['scene_images']);

        return [
            'title' => $data['title'],
            'genre' => $data['genre'] ?? null,
            'synopsis' => $data['synopsis'] ?? null,
            'status' => $data['status'],
            'characters' => array_values($data['characters']),
            'scenes' => array_values($data['scenes']),
        ];
    }

    private function storeReferenceImages(Request $request, DramaScript $script): void
    {
        $characters = $script->characters ?? [];
        foreach ($request->file('character_images', []) as $characterId => $image) {
            $index = collect($characters)->search(fn (array $character): bool => $character['id'] === $characterId);
            if ($index === false) {
                abort(422, 'Character reference image did not match a character in this drama.');
            }

            $characters[$index]['image_path'] = $this->storeReferenceImage($image, $script, 'characters');
        }

        $scenes = $script->scenes ?? [];
        foreach ($request->file('scene_images', []) as $sceneId => $image) {
            $index = collect($scenes)->search(fn (array $scene): bool => $scene['id'] === $sceneId);
            if ($index === false) {
                abort(422, 'Scene reference image did not match a scene in this drama.');
            }

            $scenes[$index]['image_path'] = $this->storeReferenceImage($image, $script, 'scenes');
        }

        $script->forceFill(['characters' => $characters, 'scenes' => $scenes])->save();
    }

    private function storeReferenceImage(UploadedFile $image, DramaScript $script, string $type): string
    {
        $extension = $image->guessExtension() ?: 'img';
        $path = "drama-content/{$script->id}/{$type}/".Str::uuid().".{$extension}";
        if (! Storage::disk('public')->putFileAs(dirname($path), $image, basename($path))) {
            throw new \RuntimeException('Could not save a drama reference image.');
        }

        return $path;
    }

    private function referenceImagePaths(DramaScript $script): array
    {
        return collect([...($script->characters ?? []), ...($script->scenes ?? [])])
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();
    }
}
