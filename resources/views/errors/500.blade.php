@extends('errors.layout')

@section('title', __('Something went wrong'))
@section('code', '500')
@section('heading', __('Something went wrong'))
@section('message', __('The server could not complete your request. Please try again later.'))
