@extends('mobile.layouts.app')

@section('title', 'Mobile Accounting')

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'Kế toán'])

<div class="m-card">
    <div class="m-grid">
        <a class="m-btn m-btn-primary" href="{{ route('accounting.dashboard') }}">Dashboard</a>
        <a class="m-btn m-btn-outline" href="{{ route('accounting.reconciliation') }}">Đối soát</a>
        <a class="m-btn m-btn-outline" href="{{ route('accounting.cashflow') }}">Thu chi</a>
        <a class="m-btn m-btn-outline" href="{{ route('accounting.customer-debts') }}">Công nợ</a>
    </div>
</div>
@endsection
