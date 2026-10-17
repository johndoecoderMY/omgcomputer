@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Edit department"
        :back-url="route('departments.index')" back-label="All departments" />
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form class="card p-2" method="POST" action="{{ route('departments.update', $department) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="mb-3"><label class="form-label" for="department-name">Department name</label><input class="form-control" id="department-name" name="name" value="{{ old('name', $department->name) }}" required maxlength="255"></div>
            <div><label class="form-label" for="department-description">Description</label><textarea class="form-control" id="department-description" name="description" rows="3" maxlength="2000">{{ old('description', $department->description) }}</textarea></div>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between"><a class="btn btn-outline-secondary" href="{{ route('departments.index') }}">Cancel</a><button class="btn btn-primary">Save department</button></div>
    </form>
    <form class="mt-3" method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?');">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger" @disabled($department->teams()->exists())>Delete department</button>
        @if ($department->teams()->exists())<span class="small text-body-secondary ms-2">Move or remove this department's teams first.</span>@endif
    </form>
</div>
@endsection
