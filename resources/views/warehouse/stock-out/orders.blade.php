@extends('layouts.warehouse')

@section('title', 'Đơn Xuất Kho')
@section('subtitle', 'Danh sách đơn hàng đã xuất theo phiếu xuất kho')

@push('styles')
<style>
    .wh-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .wh-item-table-wrap {
        overflow-x: auto;
    }
    .wh-item-table-head,
    .wh-item-table-row {
        display: grid;
        grid-template-columns: 48px minmax(50px, 1fr) 42px 52px 86px 86px;
        gap: 8px;
        align-items: center;
    }
    .wh-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 6px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .wh-item-row {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .wh-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .wh-item-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }
    .wh-item-cell strong {
        color: #0f172a;
    }
    .wh-exported-badge {
        border: 1px solid #dc2626;
        background: #fef2f2;
        color: #b91c1c;
        border-radius: 999px;
        font-weight: 700;
        padding: 4px 10px;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    @media (max-width: 767.98px) {
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 44px minmax(120px, 1fr) 42px 52px 70px 70px;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalRows = $exportRows->count();
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Đến ngày</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ route('warehouse.stock-out.orders') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-danger rounded-pill">Đơn đã xuất: {{ $totalRows }}</span>
    </div>
    <a href="{{ route('warehouse.stock-out') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Quay lại phiếu xuất
    </a>
</div>

@if($exportRows->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn hàng xuất kho trong khoảng thời gian đã chọn.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($exportRows as $row)
            @php
                $document = $row['document'];
                $order = $row['order'];
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-semibold">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                            <div class="small text-muted">{{ $order?->code ?? '—' }}, {{ optional($document->document_date)->format('d/m/Y') ?: '—' }}</div>
                        </div>
                        <span class="wh-exported-badge">ĐÃ XUẤT KHO</span>
                    </div>

                    <div class="card-body">
                        <div class="row g-2 mb-3 small">
                            <div class="col-12 col-md-6">
                                <div class="text-muted">Mã phiếu</div>
                                <div class="fw-semibold">{{ $document->document_number ?? ('#' . $document->id) }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="text-muted">Người tạo phiếu</div>
                                <div class="fw-semibold">{{ $document->user?->name ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <div class="wh-item-table-wrap">
                                <div class="wh-item-table-head">
                                    <div>Ảnh</div>
                                    <div>Sản phẩm</div>
                                    <div class="text-center">Size</div>
                                    <div class="text-center">SL</div>
                                    <div class="text-center">Tổng</div>
                                    <div class="text-end">Khối lượng</div>
                                </div>
                                <ul class="wh-item-list">
                                    @foreach($order?->items ?? [] as $item)
                                        @php
                                            $variant = $item->variant;
                                            $orderedQty = (int) ($item->quantity ?? 0);
                                            $variantSize = $variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? (string) $variantSize
                                                : '-';
                                            $itemWeight = (float) ($item->packed_weight ?? $item->total_weight ?? 0);
                                            $imagePath = $variant?->avatar?->media?->file_path
                                                ?? $item->product?->avatar?->media?->file_path
                                                ?? null;
                                        @endphp
                                        <li class="wh-item-row">
                                            <div class="wh-item-table-row">
                                                <div>
                                                    @if($imagePath)
                                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}">
                                                    @else
                                                        <span class="wh-item-thumb-placeholder">
                                                            <i class="bi bi-image"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-name">
                                                    {{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                                    @if($variant?->sku)
                                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ number_format($orderedQty) }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ $item->display_total_label ?? '—' }}</strong></div>
                                                <div class="wh-item-cell text-end"><strong>{{ format_kg($itemWeight) }}</strong></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center gap-2">
                        <div style="font-size:.8rem;" class="text-muted">
                            Kho xuất: <strong>{{ $document->warehouse?->name ?? '—' }}</strong>
                            @if($order?->shipper?->name)
                                , shipper: <strong>{{ $order->shipper->name }}</strong>
                            @endif
                        </div>
                        <a href="{{ route('warehouse.stock-out.show', $document) }}" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-eye me-1"></i>Chi tiết phiếu
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
