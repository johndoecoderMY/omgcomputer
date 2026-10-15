@extends('layouts.app')

@section('title', 'Website Flow Charts')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" title="Website flow charts"
        description="Create and maintain maps of website pages and user journeys.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('development.flowcharts.create') }}">
                <i class="fas fa-plus me-2"></i>New flow chart
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        @forelse ($flowCharts as $flowChart)
            <div class="col-12 col-xl-6">
                <article class="card h-100 flow-chart-preview">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                            <div>
                                <h2 class="h5 mb-1">
                                    <a class="text-decoration-none" href="{{ route('development.flowcharts.show', $flowChart) }}">
                                        {{ $flowChart->title }}
                                    </a>
                                </h2>
                                <p class="small text-body-secondary mb-0">
                                    {{ $flowChart->description ?: 'No description provided.' }}
                                </p>
                            </div>
                            <span class="badge text-bg-light border text-nowrap">{{ count($flowChart->steps) }} steps</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <span class="small text-body-secondary">
                                {{ collect($flowChart->steps)->sum(fn ($step) => count($step['connections'] ?? [])) }} connections
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-body d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('development.flowcharts.show', $flowChart) }}">View</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('development.flowcharts.edit', $flowChart) }}">Edit</a>
                        <form method="POST" action="{{ route('development.flowcharts.destroy', $flowChart) }}"
                            onsubmit="return confirm('Delete this flow chart?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-diagram-project text-primary fs-2 mb-3" aria-hidden="true"></i>
                        <h2 class="h5">No flow charts yet</h2>
                        <p class="text-body-secondary">Create a flow chart to document how visitors move through the website.</p>
                        <a class="btn btn-primary" href="{{ route('development.flowcharts.create') }}">Create a flow chart</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
