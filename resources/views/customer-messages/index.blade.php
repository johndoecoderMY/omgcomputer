@extends('layouts.app')

@section('title', 'Copywriting')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" title="Copywriting" description="Reusable wording for clear, considerate customer updates."><x-slot name="actions"><a class="btn btn-primary" href="{{ route('customer-messages.create') }}"><span class="bi bi-plus me-2"></span>New template</a></x-slot></x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row g-2 mb-3" method="GET" action="{{ route('customer-messages.index') }}"><div class="col-md-4"><label class="visually-hidden" for="stage">Filter by stage</label><select class="form-select" id="stage" name="stage"><option value="">All customer moments</option>@foreach ($stages as $value => $label)<option value="{{ $value }}" @selected(request('stage') === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('customer-messages.index') }}">Reset</a></div></form>
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('customer-messages.datatable', request()->only('stage')) }}"
            data-columns="{{ json_encode([
                ['data' => 'template', 'name' => 'name'],
                ['data' => 'stage_label', 'name' => 'stage'],
                ['data' => 'channel_label', 'name' => 'channel'],
                ['data' => 'status_label', 'name' => 'is_active'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Template</th><th>Customer moment</th><th>Channel</th><th>Status</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection