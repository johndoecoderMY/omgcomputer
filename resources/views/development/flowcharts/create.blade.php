@extends('layouts.app')

@section('title', 'Create Flow Chart')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" title="Create flow chart"
        :back-url="route('development.flowcharts.index')" back-label="All flow charts" />

    @include('development.flowcharts._form', [
        'action' => route('development.flowcharts.store'),
        'method' => 'POST',
        'buttonLabel' => 'Create flow chart',
    ])
</div>
@endsection
