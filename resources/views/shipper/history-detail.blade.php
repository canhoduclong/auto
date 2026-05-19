@extends('layouts.shipper')

@section('title', 'Chi tiết giao hàng')
@section('subtitle', 'Thông tin giao hàng và hoàn trả của đơn')

@push('styles')
<style>
    .sp-detail-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }
    .sp-detail-stat {
        border-radius: 14px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        padding: 1rem 1.1rem;
        height: 100%;
    }
    .sp-detail-stat-label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .35rem;
    }
    .sp-detail-stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .sp-proof-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
    .sp-proof-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
    }
    .sp-proof-item img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
    }
    .sp-proof-item .caption {
        padding: .65rem .8rem;
        font-size: .8rem;
        color: #475569;
    }
    .sp-item-table thead th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
@php
    $statusMap = [
        'delivered' => ['label' => 'Đã giao', 'color' => 'success'],
        'returning' => ['label' => 'Đang trả', 'color' => 'warning'],
        'returned_completed' => ['label' => 'Đã nhập kho trả', 'color' => 'secondary'],
        'completed' => ['label' => 'Hoàn thành', 'color' => 'success'],
    ];
    $statusMeta = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'secondary'];
    $paymentMethod = '—';
    if ($deliveryHistory?->note) {
        if (str_contains($deliveryHistory->note, 'Tiền mặt')) {
            $paymentMethod = 'Tiền mặt';
        } elseif (str_contains($deliveryHistory->note, 'Chuyển khoản')) {
            $paymentMethod = 'Chuyển khoản';
        }
    }
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <div class="h4 mb-1 fw-bold">Đơn {{ $order->code }}</div>
        <div class="text-muted">Khách hàng: {{ $order->customer?->name ?? '—' }} - {{ $order->customer?->phone ?? '—' }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('shipper.history') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại lịch sử
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="sp-detail-stat">
            <div class="sp-detail-stat-label">Trạng thái</div>
            <div class="sp-detail-stat-value">
                <span class="badge bg-{{ $statusMeta['color'] }}">{{ $statusMeta['label'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="sp-detail-stat">
            <div class="sp-detail-stat-label">Tổng tiền đơn</div>
            <div class="sp-detail-stat-value">{{ number_format($order->total) }}đ</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="sp-detail-stat">
            <div class="sp-detail-stat-label">Đã thu</div>
            <div class="sp-detail-stat-value">{{ number_format((float) ($order->collected_amount ?? 0)) }}đ</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="sp-detail-stat">
            <div class="sp-detail-stat-label">Phương thức</div>
            <div class="sp-detail-stat-value">{{ $paymentMethod }}</div>
        </div>
    </div>
</div>

<div class="card sp-detail-card mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-1 text-primary"></i>Thông tin giao hàng</span>
        <span class="text-muted small">{{ optional($order->delivered_at ?? $deliveryHistory?->created_at)->format('d/m/Y H:i') ?? '—' }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="small text-muted mb-1">Địa chỉ giao</div>
                <div class="fw-semibold">{{ $order->recipient_name ?: ($order->customer?->name ?? '—') }}</div>
                <div>{{ $order->recipient_phone ?: ($order->customer?->phone ?? '—') }}</div>
                <div class="text-muted">{{ $order->recipient_address ?: 'Chưa có địa chỉ giao hàng' }}</div>
            </div>
            <div class="col-lg-6">
                <div class="small text-muted mb-1">Ghi chú giao hàng</div>
                <div class="text-dark">{{ $deliveryHistory?->note ?? $order->shipper_note ?? 'Không có ghi chú.' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card sp-detail-card mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-box-seam me-1 text-secondary"></i>Chi tiết sản phẩm đã giao
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 sp-item-table">
            <thead class="table-light">
                <tr>
                    <th>Sản phẩm</th>
                    <th class="text-end">SL</th>
                    <th class="text-end">Kg thực giao</th>
                    <th class="text-end">Đơn giá</th>
                    <th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $weight = (float) ($item->actual_weight ?? ($item->packed_weight ?? ($item->effective_unit_weight * $item->quantity)));
                        $lineTotal = (bool) $item->effective_priced_by_kg
                            ? $weight * (float) ($item->price ?? 0)
                            : (int) $item->quantity * (float) ($item->price ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}</div>
                            <div class="text-muted small">{{ $item->variant?->sku ?? '—' }}</div>
                        </td>
                        <td class="text-end">{{ number_format((int) $item->quantity) }}</td>
                        <td class="text-end">{{ (bool) $item->effective_priced_by_kg ? number_format($weight, 3) . ' kg' : '—' }}</td>
                        <td class="text-end">{{ number_format((float) ($item->price ?? 0)) }}đ</td>
                        <td class="text-end fw-semibold">{{ number_format($lineTotal) }}đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($latestReturn)
<div class="card sp-detail-card mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-arrow-return-left me-1 text-warning"></i>Thông tin hoàn trả</span>
        <span class="badge bg-warning text-dark">{{ $latestReturn->status }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="small text-muted mb-1">Kho nhận hàng trả</div>
                <div class="fw-semibold">{{ $latestReturn->warehouse?->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mb-1">Lý do</div>
                <div class="fw-semibold">{{ $latestReturn->reason ?: '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mb-1">Người tạo phiếu</div>
                <div class="fw-semibold">{{ $latestReturn->creator?->name ?? '—' }}</div>
            </div>
        </div>
        <div class="small text-muted mb-1">Ghi chú</div>
        <div class="mb-3">{{ $latestReturn->note ?: 'Không có ghi chú.' }}</div>

        @if($latestReturn->returnItems->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm trả</th>
                            <th class="text-end">Số lượng</th>
                            <th>Tình trạng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestReturn->returnItems as $returnItem)
                            <tr>
                                <td>{{ $returnItem->productVariant?->name ?? $returnItem->productVariant?->sku ?? 'Sản phẩm' }}</td>
                                <td class="text-end">{{ number_format((int) $returnItem->quantity) }}</td>
                                <td>{{ $returnItem->condition ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif

@if(!empty($order->proof_images))
<div class="card sp-detail-card">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-images me-1 text-success"></i>Ảnh bằng chứng giao hàng
    </div>
    <div class="card-body">
        <div class="sp-proof-grid">
            @foreach($order->proof_images as $index => $image)
                <a href="{{ Storage::url($image) }}" target="_blank" class="sp-proof-item text-decoration-none">
                    <img src="{{ Storage::url($image) }}" alt="Ảnh giao hàng {{ $index + 1 }}">
                    <div class="caption">Ảnh bằng chứng {{ $index + 1 }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
