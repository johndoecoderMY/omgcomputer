@extends('layouts.app')

@section('title', 'Drama Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Creative studio" title="Drama content"
        description="Plan characters, scenes, scripts, and references for AI-assisted production.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('drama-content.create') }}">
                <i class="fas fa-plus me-2" aria-hidden="true"></i>New drama
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead><tr><th>Drama</th><th>Genre</th><th>Characters</th><th>Scenes</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse ($scripts as $script)
                        <tr>
                            <td>
                                <a class="fw-semibold" href="{{ route('drama-content.edit', $script) }}">{{ $script->title }}</a>
                                @if ($script->synopsis)
                                    <span class="d-block small text-body-secondary">{{ \Illuminate\Support\Str::limit($script->synopsis, 100) }}</span>
                                @endif
                            </td>
                            <td>{{ $script->genre ?: '—' }}</td>
                            <td>{{ count($script->characters ?? []) }}</td>
                            <td>{{ count($script->scenes ?? []) }}</td>
                            <td><x-status-badge :status="$script->status" :label="ucfirst($script->status)" /></td>
                            <td>{{ $script->updated_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('drama-content.edit', $script) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('drama-content.destroy', $script) }}"
                                    onsubmit="return confirm('Delete this drama and its scenes?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-5">
                            <p class="mb-2">No drama content yet.</p>
                            <a class="btn btn-sm btn-primary" href="{{ route('drama-content.create') }}">Create your first drama</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
