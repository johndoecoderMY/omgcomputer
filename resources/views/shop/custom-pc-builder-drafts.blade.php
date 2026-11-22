@extends('layouts.app')

@section('title', 'Saved PC build drafts')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="Saved PC build drafts"
        description="A shared planning list of what to build and who each PC is for." />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Build</th>
                        <th>What it is for</th>
                        <th>Build for</th>
                        <th>Planned components</th>
                        <th>Last updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drafts as $draft)
                        @php
                            $selectedComponents = collect($draft->form_data['items'] ?? [])
                                ->pluck('custom_pc_component_id')
                                ->filter()
                                ->unique()
                                ->map(fn ($id) => $components->get($id))
                                ->filter()
                                ->values();
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $draft->build_name ?: 'Untitled build' }}</td>
                            <td>{{ $draft->intended_use ?: 'Not specified' }}</td>
                            <td>
                                <div>{{ $draft->customer_name ?: 'No customer assigned' }}</div>
                                <div class="small text-body-secondary">{{ $draft->form_data['customer_email'] ?? $draft->form_data['customer_phone'] ?? '' }}</div>
                            </td>
                            <td>
                                @if ($selectedComponents->isEmpty())
                                    <span class="text-body-secondary">No parts selected yet</span>
                                @else
                                    {{ $selectedComponents->take(3)->implode(', ') }}@if ($selectedComponents->count() > 3) <span class="text-body-secondary">+{{ $selectedComponents->count() - 3 }} more</span>@endif
                                @endif
                            </td>
                            <td>
                                <div>{{ $draft->updated_at->format('M j, Y') }}</div>
                                <div class="small text-body-secondary">Draft · {{ $draft->user?->name ?? 'Former staff member' }}</div>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('shop.custom-pc-builder.drafts.edit', $draft) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('shop.custom-pc-builder.drafts.destroy', $draft) }}"
                                    onsubmit="return confirm('Delete this saved PC build draft?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-5 text-center text-body-secondary" colspan="6">No saved PC build drafts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drafts->hasPages())
            <div class="card-footer">{{ $drafts->links() }}</div>
        @endif
    </div>
</div>
@endsection
