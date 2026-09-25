@extends('layouts.app')

@section('content')
    <h1>{{ __('dashboard.user.title') }}</h1>
    <p>{{ __('dashboard.user.welcome', ['name' => auth()->user()->name]) }}</p>
@endsection