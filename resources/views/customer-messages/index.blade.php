@extends('layouts.app')

@section('title', 'Customer Messages')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="Customer messages" description="Reusable wording for clear, considerate customer updates."><x-slot name="actions"><a class="btn btn-primary" href="{{ route('customer-messages.create') }}"><span class="fas fa-plus me-2"></span>New template</a></x-slot></x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row g-2 mb-3" method="GET"><div class="col-md-5"><label class="visually-hidden" for="search">Search templates</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search names or message text"></div><div class="col-md-4"><label class="visually-hidden" for="stage">Filter by stage</label><select class="form-select" id="stage" name="stage"><option value="">All customer moments</option>@foreach ($stages as $value => $label)<option value="{{ $value }}" @selected(request('stage') === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('customer-messages.index') }}">Reset</a></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Template</th><th>Customer moment</th><th>Channel</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse ($templates as $template)<tr><td><a class="fw-semibold" href="{{ route('customer-messages.show', $template) }}">{{ $template->name }}</a>@if ($template->subject)<div class="small text-body-secondary">{{ $template->subject }}</div>@endif</td><td>{{ $stages[$template->stage] }}</td><td>{{ \App\Models\CustomerMessageTemplate::CHANNELS[$template->channel] }}</td><td><span class="badge text-bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Available' : 'Archived' }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('customer-messages.show', $template) }}">Preview</a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-body-secondary py-5">No message templates found.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $templates->links() }}</div></div>
</div>
@endsection