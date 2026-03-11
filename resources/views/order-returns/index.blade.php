@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Don tra hang</h1>
        @if(auth()->check() && (auth()->user()->hasRole('sale') || auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')))
            <a href="{{ route('order-returns.create') }}" class="btn btn-primary">Tao don tra hang</a>
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
                <th>Ma don</th>
                <th>Customer</th>
                <th>Kho nhap</th>
                <th>Status</th>
                <th>Phan loai</th>
                <th>Tien refund</th>
                <th>Reason</th>
                <th>Nguoi tao</th>
                <th>Created At</th>
                <th>Thao tac</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returns as $return)
            <tr>
                <td>{{ $return->id }}</td>
                <td>{{ $return->order->code ?? 'N/A' }}</td>
                <td>{{ $return->customer->name ?? 'N/A' }}</td>
                <td>{{ $return->warehouse->name ?? 'N/A' }}</td>
                <td>
                    @php
                        $statusLabel = [
                            'requested' => 'Cho ship xac nhan',
                            'ship_confirmed' => 'Cho kho nhap',
                            'warehouse_received' => 'Da nhap kho',
                            'cancelled' => 'Da huy',
                        ][$return->status] ?? $return->status;
                    @endphp
                    <span class="badge bg-secondary">{{ $statusLabel }}</span>
                </td>
                <td>
                    @if($return->return_scope === 'full')
                        <span class="badge bg-danger">Tra toan bo</span>
                    @elseif($return->return_scope === 'partial')
                        <span class="badge bg-warning text-dark">Tra mot phan</span>
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
                <td>{{ $return->creator->name ?? 'N/A' }}</td>
                <td>{{ $return->created_at?->format('d/m/Y H:i') }}</td>
                <td>
                    <a href="{{ route('order-returns.show', $return) }}" class="btn btn-sm btn-outline-secondary">Chi tiet</a>

                    @if(auth()->check() && (auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')) && $return->status === 'requested')
                        <form action="{{ route('order-returns.ship-confirm', $return) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Xac nhan ship da nhan hang tra?')">Ship xac nhan</button>
                        </form>
                    @endif

                    @if(auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin')) && $return->status === 'ship_confirmed')
                        <form action="{{ route('order-returns.warehouse-confirm', $return) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Xac nhan kho da nhap hang tra?')">Kho xac nhan nhap</button>
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