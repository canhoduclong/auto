@extends('mobile.layouts.app')

@section('title', 'Mobile CEO')

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'CEO'])

<div class="m-card">
    <div class="m-grid">
        <a class="m-btn m-btn-primary" href="{{ route('ceo.dashboard') }}">Dashboard</a>
        <a class="m-btn m-btn-outline" href="{{ route('ceo.daily-sales') }}">Bán hàng</a>
        <a class="m-btn m-btn-outline" href="{{ route('ceo.cashflow') }}">Thu chi</a>
        <a class="m-btn m-btn-outline" href="{{ route('ceo.financial-reports') }}">Tài chính</a>
    </div>
</div>
@endsection
