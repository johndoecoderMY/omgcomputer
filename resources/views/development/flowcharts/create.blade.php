@extends('layouts.app')

@section('title', ($isSop ?? false) ? 'Create SOP' : 'Create Flow Chart')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" :title="($isSop ?? false) ? 'Create SOP' : 'Create flow chart'"
        :back-url="route('development.flowcharts.index')" back-label="All flow charts" />

    @include('development.flowcharts._form', [
        'action' => route('development.flowcharts.store'),
        'method' => 'POST',
        'buttonLabel' => ($isSop ?? false) ? 'Create SOP' : 'Create flow chart',
    ])
</div>
@endsection
