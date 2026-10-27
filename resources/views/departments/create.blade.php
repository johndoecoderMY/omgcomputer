@extends('layouts.app')

@section('title', 'Add Department')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Members & organization" title="Add department" :back-url="route('departments.index')" back-label="All departments" />
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('departments.store') }}">
        @csrf
        @include('departments._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('departments.index') }}">Cancel</a><button class="btn btn-primary">Create department</button></div>
    </form>
</div>
@endsection
