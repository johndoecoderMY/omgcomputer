@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer management" title="Customers">
        <x-slot name="actions"><a class="btn btn-primary" href="{{ route('customers.create') }}"><span class="fas fa-user-plus me-2"></span>Add customer</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row g-2 mb-3" method="GET"><div class="col-md-6"><label class="visually-hidden" for="search">Search customers</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email, phone or company"></div><div class="col-auto"><button class="btn btn-outline-primary">Search</button></div></form>
    <div class="card p-2"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Customer</th><th>Contact</th><th>Project</th><th class="text-end">Repair tickets</th><th></th></tr></thead><tbody>
        @forelse ($customers as $customer)<tr><td><a class="fw-semibold" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>@if ($customer->company)<div class="small text-body-secondary">{{ $customer->company }}</div>@endif</td><td>{{ $customer->email ?: '—' }}<div class="small text-body-secondary">{{ $customer->phone }}</div></td><td>@if ($customer->project)<a href="{{ route('projects.show', $customer->project) }}">{{ $customer->project->name }}</a>@else<span class="text-body-secondary">—</span>@endif</td><td class="text-end">{{ $customer->repair_tickets_count }}</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.show', $customer) }}">View profile</a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-body-secondary py-5">No customers found.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $customers->links() }}</div></div>
</div>
@endsection