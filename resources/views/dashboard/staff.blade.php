@extends('layouts.app')

@section('content')
    <h1>Staff Dashboard</h1>
    <p>Xin chào {{ auth()->user()->name }}, bạn chỉ có thể thao tác với sản phẩm được phân công.</p>
@endsection
