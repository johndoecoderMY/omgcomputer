@extends('errors.layout')

@section('title', __('Service unavailable'))
@section('code', '503')
@section('heading', __('We will be back soon'))
@section('message', __('The service is temporarily unavailable. Please try again in a little while.'))
