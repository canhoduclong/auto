@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ __('order_returns.titles.detail', ['id' => $return->id]) }}</h1>
        <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">{{ __('order_returns.buttons.back') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>{{ __('order_returns.labels.order') }}:</strong> {{ $return->order->code ?? __('order_returns.default.na') }}</div>
                <div class="col-md-3"><strong>{{ __('order_returns.labels.customer') }}:</strong> {{ $return->customer->name ?? __('order_returns.default.na') }}</div>
                <div class="col-md-3"><strong>{{ __('order_returns.labels.warehouse') }}:</strong> {{ $return->warehouse->name ?? __('order_returns.default.na') }}</div>
                <div class="col-md-3"><strong>{{ __('order_returns.labels.status') }}:</strong> {{ __('order_returns.statuses.' . $return->status) }}</div>
                <div class="col-md-3"><strong>{{ __('order_returns.labels.scope') }}:</strong> {{ $return->return_scope === 'full' ? __('order_returns.scopes.full') : ($return->return_scope === 'partial' ? __('order_returns.scopes.partial') : '-') }}</div>
                <div class="col-md-3"><strong>{{ __('order_returns.labels.refund_amount') }}:</strong> {{ number_format((float) ($return->refund_amount ?? 0), 0, ',', '.') }}</div>
                <div class="col-md-6"><strong>{{ __('order_returns.labels.reason') }}:</strong> {{ $return->reason ?: '-' }}</div>
                <div class="col-md-6"><strong>{{ __('order_returns.labels.note') }}:</strong> {{ $return->note ?: '-' }}</div>
                <div class="col-md-4"><strong>{{ __('order_returns.labels.creator') }}:</strong> {{ $return->creator->name ?? __('order_returns.default.na') }}</div>
                <div class="col-md-4"><strong>{{ __('order_returns.labels.ship_confirm') }}:</strong> {{ $return->shipConfirmer->name ?? '-' }} {{ $return->ship_confirmed_at ? '(' . $return->ship_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
                <div class="col-md-4"><strong>{{ __('order_returns.labels.warehouse_confirm') }}:</strong> {{ $return->warehouseConfirmer->name ?? '-' }} {{ $return->warehouse_confirmed_at ? '(' . $return->warehouse_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
                <div class="col-md-4"><strong>{{ __('order_returns.labels.refund_tx') }}:</strong> {{ $return->refundTransaction ? ('#' . $return->refundTransaction->id) : '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">{{ __('order_returns.titles.items') }}</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('order_returns.labels.order') }}</th>
                        <th>{{ __('order_returns.labels.variant') }}</th>
                        <th>{{ __('order_returns.labels.quantity') }}</th>
                        <th>{{ __('order_returns.labels.condition') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($return->returnItems as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->productVariant->product->name ?? __('order_returns.default.na') }}</td>
                            <td>{{ $item->productVariant->name ?? __('order_returns.default.na') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->condition ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">{{ __('order_returns.empty.no_products') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if(auth()->check() && (auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')) && $return->status === 'requested')
            <form action="{{ route('order-returns.ship-confirm', $return) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-info" onclick="return confirm('{{ __('order_returns.confirms.ship_confirm') }}')">{{ __('order_returns.buttons.ship_confirm') }}</button>
            </form>
        @endif

        @if(auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin')) && $return->status === 'ship_confirmed')
            <form action="{{ route('order-returns.warehouse-confirm', $return) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('{{ __('order_returns.confirms.warehouse_confirm') }}')">{{ __('order_returns.buttons.warehouse_confirm') }}</button>
            </form>
        @endif
    </div>
</div>
@endsection
