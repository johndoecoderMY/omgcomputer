@extends('layouts.app')

@section('title', $categoryLabel . ' SOPs')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" :title="$categoryLabel . ' SOPs'"
        description="Start from an editable SOP workflow template, then adapt the steps and approvals for each product, service, or student FYP activity."
        :back-url="route('development.flowcharts.index')" back-label="All flow charts">
        <x-slot:actions>
            @foreach (['product' => 'Product SOPs', 'service' => 'Service SOPs', 'education' => 'Student FYP SOPs'] as $key => $label)
                @if ($category === $key)
                    <span class="btn btn-primary" aria-current="page">{{ $label }}</span>
                @else
                    <a class="btn btn-outline-primary" href="{{ route('development.sops', ['category' => $key]) }}">{{ $label }}</a>
                @endif
            @endforeach
            <a class="btn btn-primary" href="{{ route('development.flowcharts.create', ['category' => $category]) }}">
                <i class="fas fa-plus me-2"></i>New {{ $categoryLabel }} SOP
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('development.sops.datatable', ['category' => $category]) }}"
            data-columns="{{ json_encode([
                ['data' => 'title', 'name' => 'flow_charts.title'],
                ['data' => 'subject_name', 'name' => 'flow_charts.subject_name'],
                ['data' => 'description', 'name' => 'flow_charts.description'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>SOP</th><th>Product, service, or programme</th><th>Procedure notes</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
