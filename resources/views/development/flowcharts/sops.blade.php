@extends('layouts.app')

@section('title', 'SOP Library')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" title="SOP Library"
        description="Browse all product, service, and student FYP SOPs. Use the table filters or search to find the right procedure."
        :back-url="route('development.flowcharts.index')" back-label="All flow charts">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('development.sops.create') }}">
                <i class="bi bi-plus me-2"></i>Add new SOP
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="card card-body p-2 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" for="sop-category">Category</label>
                <select class="form-select" id="sop-category" data-datatable-filter>
                    <option value="">All categories</option>
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                    <option value="education">Student FYP</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" for="sop-type">Type</label>
                <select class="form-select" id="sop-type" data-datatable-filter>
                    <option value="">Examples and custom SOPs</option>
                    <option value="example">Examples</option>
                    <option value="custom">Custom SOPs</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" for="sop-subject">Product, service, or programme</label>
                <select class="form-select" id="sop-subject" data-datatable-filter>
                    <option value="">All products and programmes</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject }}">{{ $subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" for="sop-search">Search SOPs</label>
                <input class="form-control" id="sop-search" type="search" data-datatable-search
                    placeholder="Search title, product, or notes">
            </div>
        </div>
    </div>

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('development.sops.datatable') }}"
            data-datatable-filters="{{ json_encode([
                ['name' => 'category', 'selector' => '#sop-category'],
                ['name' => 'type', 'selector' => '#sop-type'],
                ['name' => 'subject_name', 'selector' => '#sop-subject'],
            ]) }}"
            data-datatable-search="#sop-search"
            data-columns="{{ json_encode([
                ['data' => 'title', 'name' => 'flow_charts.title'],
                ['data' => 'category_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'example_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'subject_name', 'name' => 'flow_charts.subject_name'],
                ['data' => 'description', 'name' => 'flow_charts.description'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>SOP</th><th>Category</th><th>Type</th><th>Product, service, or programme</th><th>Procedure notes</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
