@extends('layouts.site')
@section('title', 'Chi tiết công nợ · '.$selected['name'])

@section('content')
<div class="sale-debt-detail-page">
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
        @if(session($key))<div class="alert alert-{{ $color }}">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @include('customer-debt.detail-report', ['debtDetailAction' => route('pages.my_customer_debt'), 'saleDebtDetail' => true])
</div>
@if($detailPolicyAction)
    @include('customer-debt.policy-dialog', ['policyFromReport' => true])
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
@endpush
