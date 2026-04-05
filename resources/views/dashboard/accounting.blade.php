@extends('layouts.accounting')

@section('accounting_content')
    <div class="card mb-0">
        <div class="card-body">
            <h5 class="mb-2">Dashboard Kế Toán</h5>
            <p class="mb-0">Chào {{ $user->name ?? auth()->user()->name }}, bạn đang ở khu vực kế toán.</p>
        </div>
    </div>
@endsection
