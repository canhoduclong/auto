@extends('layouts.app')

@section('content')
    <h1>Manager Dashboard</h1>
    <p>Xin chào {{ auth()->user()->name }}, bạn có quyền quản lý sản phẩm & báo cáo.</p>
@endsection