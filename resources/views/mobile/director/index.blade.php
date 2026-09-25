@extends('mobile.layouts.app')

@section('title', 'Mobile Director')

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'Director'])

<div class="m-card">
    <div class="m-grid">
        <a class="m-btn m-btn-primary" href="{{ route('director.dashboard') }}">Dashboard</a>
        <a class="m-btn m-btn-outline" href="{{ route('director.daily-sales') }}">Bán hàng</a>
        <a class="m-btn m-btn-outline" href="{{ route('director.cashflow') }}">Thu chi</a>
        <a class="m-btn m-btn-outline" href="{{ route('director.finance-requests.index') }}">Phiếu yêu cầu</a>
        <a class="m-btn m-btn-outline" href="{{ route('director.financial-reports') }}">Tài chính</a>
    </div>
</div>
@endsection
