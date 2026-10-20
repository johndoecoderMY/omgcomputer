@extends('errors.layout')

@section('title', 'Too many requests')
@section('code', '429')
@section('heading', 'Please slow down')
@section('message', 'Too many requests were made in a short time. Wait a moment, then try again.')
