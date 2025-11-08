@extends('layouts.app')

@section('content')
    <h1>User Dashboard</h1>
    <p>Chào mừng {{ auth()->user()->name }}!</p>
@endsection