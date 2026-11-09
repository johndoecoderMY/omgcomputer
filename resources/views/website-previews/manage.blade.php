@extends('layouts.app')

@section('title', 'Manage Website Previews')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Website portfolio" title="Website previews"
        description="Manage the screenshots and links shown on the public portfolio page.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('website-previews.index') }}" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>View public page
            </a>
            <a class="btn btn-primary" href="{{ route('development.website-previews.create') }}">
                <i class="bi bi-plus me-2" aria-hidden="true"></i>Add preview
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        @forelse ($previews as $preview)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 overflow-hidden">
                    @if (Storage::disk('public')->exists($preview->image_path))
                        <img src="{{ Storage::disk('public')->url($preview->image_path) }}" alt="Website design preview: {{ $preview->title }}" loading="lazy" style="aspect-ratio: 16 / 10; object-fit: cover;">
                    @else
                        <div class="bg-body-tertiary text-center py-5"><span class="bi bi-image fs-1 text-body-secondary" aria-hidden="true"></span></div>
                    @endif
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h2 class="h5 mb-1">{{ $preview->title }}</h2>
                                @if ($preview->category)<span class="small text-body-secondary">{{ $preview->category }}</span>@endif
                            </div>
                            <span class="badge {{ $preview->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $preview->is_published ? 'Published' : 'Draft' }}</span>
                        </div>
                        @if ($preview->description)<p class="text-body-secondary mt-2 mb-1">{{ $preview->description }}</p>@endif
                        @if ($preview->preview_url)<a href="{{ $preview->preview_url }}" target="_blank" rel="noopener noreferrer">Live preview</a>@endif
                        <div class="small text-body-secondary mt-2">Display order: {{ $preview->sort_order }}</div>
                    </div>
                    <div class="card-footer bg-body d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('development.website-previews.edit', $preview) }}">Edit</a>
                        <form method="POST" action="{{ route('development.website-previews.destroy', $preview) }}" onsubmit="return confirm('Remove this website preview and its image?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-center py-5">
                <h2 class="h5">No website previews yet</h2>
                <p class="text-body-secondary">Add a project screenshot and publish it when it’s ready.</p>
                <a class="btn btn-primary" href="{{ route('development.website-previews.create') }}">Add your first preview</a>
            </div></div></div>
        @endforelse
    </div>
</div>
@endsection
