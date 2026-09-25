@extends('layouts.app')

@section('content')
    <h1>{{ __('dashboard.staff.title') }}</h1>
    <p>{{ __('dashboard.staff.welcome', ['name' => auth()->user()->name]) }}</p>
@endsection
