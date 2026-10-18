@extends('layouts.app')

@section('title', 'Edit '.$department->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization / Departments" :title="'Edit '.$department->name" :back-url="route('departments.show', $department)" back-label="Back to department" />
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('departments.update', $department) }}">
        @csrf
        @method('PUT')
        @include('departments._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('departments.show', $department) }}">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
