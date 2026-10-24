@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer management" title="Customers">
        <x-slot name="actions"><a class="btn btn-primary" href="{{ route('customers.create') }}"><span class="fas fa-user-plus me-2"></span>Add customer</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('customers.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'customer', 'name' => 'name'],
                ['data' => 'contact', 'orderable' => false, 'searchable' => false],
                ['data' => 'notes', 'orderable' => false, 'searchable' => false],
                ['data' => 'repair_tickets_count', 'name' => 'repair_tickets_count'],
                ['data' => 'projects_count', 'name' => 'projects_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Customer</th><th>Contact</th><th>Notes</th><th>Repair tickets</th><th>Projects</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection