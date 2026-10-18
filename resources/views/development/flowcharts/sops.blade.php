@extends('layouts.app')

@section('title', $categoryLabel . ' SOPs')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" :title="$categoryLabel . ' SOPs'"
        description="Start from an editable SOP workflow template, then adapt the steps and approvals for each product, service, or student FYP activity."
        :back-url="route('development.flowcharts.index')" back-label="All flow charts">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('development.flowcharts.create', ['category' => $category]) }}">
                <i class="fas fa-plus me-2"></i>New {{ $categoryLabel }} SOP
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <form class="card card-body p-2 mb-3" method="GET" action="{{ route('development.sops') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" for="sop-category">SOP category</label>
                <select class="form-select" id="sop-category" name="category">
                    @foreach (['product' => 'Product SOPs', 'service' => 'Service SOPs', 'education' => 'Student FYP SOPs'] as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" for="sop-type">Type</label>
                <select class="form-select" id="sop-type" name="type">
                    <option value="">All types</option>
                    <option value="example" @selected($type === 'example')>Examples</option>
                    <option value="custom" @selected($type === 'custom')>Custom SOPs</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" for="sop-subject">Product or service</label>
                <select class="form-select" id="sop-subject" name="subject_name">
                    <option value="">All products and services</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject }}" @selected($subjectName === $subject)>{{ $subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('development.sops', ['category' => $category]) }}">Reset</a>
            </div>
        </div>
    </form>

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('development.sops.datatable', ['category' => $category, 'type' => $type, 'subject_name' => $subjectName]) }}"
            data-columns="{{ json_encode([
                ['data' => 'title', 'name' => 'flow_charts.title'],
                ['data' => 'example_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'subject_name', 'name' => 'flow_charts.subject_name'],
                ['data' => 'description', 'name' => 'flow_charts.description'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>SOP</th><th>Example</th><th>Product, service, or programme</th><th>Procedure notes</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
