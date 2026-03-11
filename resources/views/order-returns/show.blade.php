@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Chi tiet don tra hang #{{ $return->id }}</h1>
        <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">Quay lai</a>
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
                <div class="col-md-3"><strong>Don hang:</strong> {{ $return->order->code ?? 'N/A' }}</div>
                <div class="col-md-3"><strong>Khach hang:</strong> {{ $return->customer->name ?? 'N/A' }}</div>
                <div class="col-md-3"><strong>Kho nhap:</strong> {{ $return->warehouse->name ?? 'N/A' }}</div>
                <div class="col-md-3"><strong>Trang thai:</strong> {{ $return->status }}</div>
                <div class="col-md-3"><strong>Loai tra:</strong> {{ $return->return_scope === 'full' ? 'Tra toan bo' : ($return->return_scope === 'partial' ? 'Tra mot phan' : '-') }}</div>
                <div class="col-md-3"><strong>Tien refund:</strong> {{ number_format((float) ($return->refund_amount ?? 0), 0, ',', '.') }}</div>
                <div class="col-md-6"><strong>Ly do:</strong> {{ $return->reason ?: '-' }}</div>
                <div class="col-md-6"><strong>Ghi chu:</strong> {{ $return->note ?: '-' }}</div>
                <div class="col-md-4"><strong>Nguoi tao:</strong> {{ $return->creator->name ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Ship xac nhan:</strong> {{ $return->shipConfirmer->name ?? '-' }} {{ $return->ship_confirmed_at ? '(' . $return->ship_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
                <div class="col-md-4"><strong>Kho xac nhan:</strong> {{ $return->warehouseConfirmer->name ?? '-' }} {{ $return->warehouse_confirmed_at ? '(' . $return->warehouse_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
                <div class="col-md-4"><strong>Refund TX:</strong> {{ $return->refundTransaction ? ('#' . $return->refundTransaction->id) : '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">San pham tra hang</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>San pham</th>
                        <th>Variant</th>
                        <th>So luong</th>
                        <th>Tinh trang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($return->returnItems as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->productVariant->product->name ?? 'N/A' }}</td>
                            <td>{{ $item->productVariant->name ?? 'N/A' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->condition ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Khong co san pham</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if(auth()->check() && (auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')) && $return->status === 'requested')
            <form action="{{ route('order-returns.ship-confirm', $return) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-info" onclick="return confirm('Xac nhan ship da nhan hang tra?')">Ship xac nhan</button>
            </form>
        @endif

        @if(auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin')) && $return->status === 'ship_confirmed')
            <form action="{{ route('order-returns.warehouse-confirm', $return) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Xac nhan kho da nhap hang tra?')">Kho xac nhan nhap</button>
            </form>
        @endif
    </div>
</div>
@endsection
