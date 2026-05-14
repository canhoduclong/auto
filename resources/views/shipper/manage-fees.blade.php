@extends('layouts.shipper')

@section('title', 'Quản lý phí ship')
@section('subtitle', 'Cập nhật phí giao hàng cho từng đơn')

@push('styles')
<style>
    .mf-order-row {
        display: grid;
        grid-template-columns: 100px 1fr 120px 120px 130px;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }
    @media (max-width: 992px) {
        .mf-order-row {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        .mf-label {
            display: inline-block;
            min-width: 80px;
            font-weight: 600;
            color: #64748b;
            font-size: 0.75rem;
        }
    }
    .mf-order-code {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
    }
    .mf-customer {
        font-size: 0.85rem;
        color: #475569;
    }
    .mf-fee-input {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
    }
    .mf-fee-current {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-align: right;
    }
    .mf-btn-update {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }
    .mf-bulk-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .mf-filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .mf-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .mf-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }
    .mf-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .mf-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="mf-filter-group">
    <form method="GET" action="{{ route('shipper.manage-fees') }}" class="d-flex gap-2 align-items-center flex-grow-1">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-search me-1"></i>Lọc
        </button>
        <a href="{{ route('shipper.manage-fees') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
        </a>
    </form>
</div>

<div class="mf-stats">
    <div class="mf-stat-card">
        <div class="mf-stat-value">{{ $orders->total() }}</div>
        <div class="mf-stat-label">Tổng đơn</div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-value">{{ number_format($orders->sum('shipping_fee'), 0, ',', '.') }}</div>
        <div class="mf-stat-label">Tổng phí (đ)</div>
    </div>
</div>

<!-- Bulk Fee Update Section -->
<div class="mf-bulk-section">
    <h6 class="mb-3"><i class="bi bi-sliders me-2"></i>Cập nhật hàng loạt</h6>
    <form action="{{ route('shipper.bulk-update-fees') }}" method="POST">
        @csrf
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label small">Loại điều chỉnh</label>
                <select name="adjustment_type" class="form-select form-select-sm" required>
                    <option value="fixed">Số tiền cố định</option>
                    <option value="percent">Phần trăm (%)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Giá trị</label>
                <input type="number" name="fee_adjustment" class="form-control form-control-sm" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Ghi chú</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Lý do điều chỉnh...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-check-circle me-1"></i>Áp dụng
                </button>
            </div>
        </div>
        <div class="form-text mt-2">Chọn các đơn bên dưới, sau đó nhấn "Áp dụng" để cập nhật hàng loạt</div>
    </form>
</div>

<!-- Individual Order Fee Updates -->
@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn hàng nào để quản lý phí.</p>
    </div>
@else
<div>
    @foreach($orders as $order)
        @php
            $customer = $order->customer;
            $shipper = $order->shipper;
        @endphp
        <div class="mf-order-row">
            <div class="text-center">
                <div class="mf-order-code">#{{ $order->code }}</div>
                <div style="font-size: 0.7rem; color: #94a3b8;">{{ $order->created_at->format('d/m') }}</div>
            </div>
            <div>
                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $customer?->name ?? 'N/A' }}</div>
                <div class="mf-customer">
                    {{ $shipper?->name ?? 'Chưa gán' }} 
                    <span class="badge bg-light text-dark ms-1">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                </div>
            </div>
            <div>
                <div class="mf-label d-lg-none">Phí hiện tại:</div>
                <div class="mf-fee-current">
                    {{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }} đ
                </div>
            </div>
            <div>
                <div class="mf-label d-lg-none">Phí mới:</div>
                <form action="{{ route('shipper.update-fee', $order->id) }}" method="POST" class="d-inline-block w-100">
                    @csrf
                    <input type="number" name="shipping_fee" class="form-control mf-fee-input" value="{{ $order->shipping_fee ?? 0 }}" step="1000" required style="width: 100%;">
            </div>
            <div>
                <div class="mf-label d-lg-none">Hành động:</div>
                <button type="submit" class="btn btn-sm btn-primary mf-btn-update w-100">
                    <i class="bi bi-check me-1"></i>Cập nhật
                </button>
                </form>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-4">
    {{ $orders->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
