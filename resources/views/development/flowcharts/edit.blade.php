@extends('layouts.app')

@section('title', 'Edit Flow Chart')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" title="Edit flow chart"
        :back-url="route('development.flowcharts.show', $flowChart)" back-label="View flow chart" />

    @include('development.flowcharts._form', [
        'action' => route('development.flowcharts.update', $flowChart),
        'method' => 'PUT',
        'buttonLabel' => 'Save changes',
    ])
</div>
@endsection
