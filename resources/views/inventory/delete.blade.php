@extends('layouts.app')

@section('title', 'Delete '.$part->name)

@section('content')
<div class="container py-3">
    <x-page-header eyebrow="Parts & inventory" title="Delete inventory item" :back-url="route('inventory.parts.show', $part)" back-label="Cancel" />
    <div class="card border-danger"><div class="card-body">
        <p>Archive <strong>{{ $part->name }}</strong>? The item will be removed from active inventory, while its stock movement history and existing billing records are retained.</p>
        <form method="POST" action="{{ route('inventory.parts.destroy', $part) }}" class="d-flex justify-content-end gap-2">
            @csrf
            @method('DELETE')
            <a class="btn btn-outline-secondary" href="{{ route('inventory.parts.show', $part) }}">Cancel</a>
            <button class="btn btn-danger">Archive item</button>
        </form>
    </div></div>
</div>
@endsection
