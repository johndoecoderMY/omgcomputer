@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Customer service" title="Customers" description="Customer records and repair ticket history.">
            <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('tickets.create') }}"><span class="fas fa-plus me-2"></span>Create repair ticket</a>
            </x-slot:actions>
        </x-page-header>
        <section class="card">
            <div class="card-header">
                <form method="GET" action="{{ route('customers.index') }}" class="d-flex gap-2">
                    <input class="form-control" name="search" value="{{ $search }}" placeholder="Search name, email, or phone" aria-label="Search customers">
                    <button class="btn btn-falcon-default" type="submit">Search</button>
                </form>
            </div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0" data-datatables='{"pageLength":25,"language":{"emptyTable":"No customers yet. Customers are added when you create a repair ticket."}}'>
                <thead><tr><th>Customer</th><th>Phone</th><th>Repair tickets</th><th>Last ticket</th><th></th></tr></thead>
                <tbody>@foreach ($customers as $customer)
                    <tr><td><a class="fw-semi-bold" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a><div class="text-600">{{ $customer->email ?? 'No email recorded' }}</div></td><td>{{ $customer->phone ?? '—' }}</td><td>{{ $customer->tickets_count }}</td><td>{{ $customer->tickets_max_created_at ? \Illuminate\Support\Carbon::parse($customer->tickets_max_created_at)->format('M j, Y') : '—' }}</td><td><a class="btn btn-sm btn-falcon-default" href="{{ route('customers.show', $customer) }}">View history</a></td></tr>
                @endforeach</tbody>
            </table></div>
        </section>
    </div>
@endsection
