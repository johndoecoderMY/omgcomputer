@extends('errors.layout')

@section('title', __('Too many requests'))
@section('code', '429')
@section('heading', __('Please slow down'))
@section('message', __('Too many requests were made in a short time. Wait a moment, then try again.'))
