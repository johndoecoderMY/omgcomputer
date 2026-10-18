@extends('layouts.app')

@section('title', 'Flow Charts & SOPs')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" title="Flow charts"
        description="Map website journeys, business processes, product procedures, service SOPs, and student FYP support.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('development.sops') }}"><i class="fas fa-book me-2"></i>SOP Library</a>
            <a class="btn btn-primary" href="{{ route('development.flowcharts.create') }}">
                <i class="fas fa-plus me-2"></i>New flow chart
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('development.flowcharts.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'title', 'name' => 'flow_charts.title'],
                ['data' => 'category_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'example_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'subject_name', 'name' => 'flow_charts.subject_name'],
                ['data' => 'projects', 'orderable' => false, 'searchable' => false],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Flow chart</th><th>Type</th><th>Example</th><th>Product, service, or programme</th><th>Linked projects</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
