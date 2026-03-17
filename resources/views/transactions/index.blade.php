@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('transactions.titles.index') }}</h2>
    <a href="{{ route('transactions.create') }}" class="btn btn-success mb-3">+ {{ __('transactions.buttons.add') }}</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('transactions.labels.order') }}</th>
                <th>{{ __('transactions.labels.customer') }}</th>
                <th>{{ __('transactions.labels.amount') }}</th>
                <th>{{ __('transactions.labels.type') }}</th>
                <th>{{ __('transactions.labels.method') }}</th>
                <th>{{ __('transactions.labels.note') }}</th>
                <th>{{ __('transactions.labels.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>@if($t->order_id)<a href="{{ route('orders.show', $t->order_id) }}">#{{ $t->order_id }}</a>@endif</td>
                    <td>@if($t->customer_id){{ $t->customer->name }}@endif</td>
                    <td>{{ number_format($t->amount,0,',','.') }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->method }}</td>
                    <td>{{ $t->note }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $transactions->links() }}
</div>
@endsection
