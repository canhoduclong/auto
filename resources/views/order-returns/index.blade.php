@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ __('order_returns.titles.index') }}</h1>
        @if(auth()->check() && (auth()->user()->hasRole('sale') || auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')))
            <a href="{{ route('order-returns.create') }}" class="btn btn-primary">{{ __('order_returns.buttons.create') }}</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('order_returns.labels.order') }}</th>
                <th>{{ __('order_returns.labels.customer') }}</th>
                <th>{{ __('order_returns.labels.warehouse') }}</th>
                <th>{{ __('order_returns.labels.status') }}</th>
                <th>{{ __('order_returns.labels.scope') }}</th>
                <th>{{ __('order_returns.labels.refund_amount') }}</th>
                <th>{{ __('order_returns.labels.reason') }}</th>
                <th>{{ __('order_returns.labels.creator') }}</th>
                <th>{{ __('order_returns.labels.created_at') }}</th>
                <th>{{ __('order_returns.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returns as $return)
            <tr>
                <td>{{ $return->id }}</td>
                <td>{{ $return->order->code ?? __('order_returns.default.na') }}</td>
                <td>{{ $return->customer->name ?? __('order_returns.default.na') }}</td>
                <td>{{ $return->warehouse->name ?? __('order_returns.default.na') }}</td>
                <td>
                    @php
                        $statusLabel = [
                            'requested' => __('order_returns.statuses.requested'),
                            'ship_confirmed' => __('order_returns.statuses.ship_confirmed'),
                            'warehouse_received' => __('order_returns.statuses.warehouse_received'),
                            'cancelled' => __('order_returns.statuses.cancelled'),
                        ][$return->status] ?? $return->status;
                    @endphp
                    <span class="badge bg-secondary">{{ $statusLabel }}</span>
                </td>
                <td>
                    @if($return->return_scope === 'full')
                        <span class="badge bg-danger">{{ __('order_returns.scopes.full') }}</span>
                    @elseif($return->return_scope === 'partial')
                        <span class="badge bg-warning text-dark">{{ __('order_returns.scopes.partial') }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    {{ number_format((float) ($return->refund_amount ?? 0), 0, ',', '.') }}
                    @if($return->refundTransaction)
                        <div class="small text-muted">TX#{{ $return->refundTransaction->id }}</div>
                    @endif
                </td>
                <td>{{ $return->reason }}</td>
                <td>{{ $return->creator->name ?? __('order_returns.default.na') }}</td>
                <td>{{ $return->created_at?->format('d/m/Y H:i') }}</td>
                <td>
                    <a href="{{ route('order-returns.show', $return) }}" class="btn btn-sm btn-outline-secondary">{{ __('order_returns.buttons.view') }}</a>

                    @if(auth()->check() && (auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')) && $return->status === 'requested')
                        <form action="{{ route('order-returns.ship-confirm', $return) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('{{ __('order_returns.confirms.ship_confirm') }}')">{{ __('order_returns.buttons.ship_confirm') }}</button>
                        </form>
                    @endif

                    @if(auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin')) && $return->status === 'ship_confirmed')
                        <form action="{{ route('order-returns.warehouse-confirm', $return) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('{{ __('order_returns.confirms.warehouse_confirm') }}')">{{ __('order_returns.buttons.warehouse_confirm') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $returns->links() }}
</div>
@endsection