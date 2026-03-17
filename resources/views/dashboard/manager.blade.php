@extends('layouts.app')

@section('content')
    <h1>{{ __('dashboard.manager.title') }}</h1>
    <p>{{ __('dashboard.manager.welcome', ['name' => auth()->user()->name]) }}</p>
@endsection