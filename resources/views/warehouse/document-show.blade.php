@extends('layouts.warehouse')

@section('title', $document->type === 'import' ? 'Chi tiết Phiếu Nhập Kho' : 'Chi tiết Phiếu Xuất Kho')

@push('styles')
<style>
.doc-show-header { border-radius: 14px; color: #fff; padding: 28px 32px; margin-bottom: 28px; }
.doc-show-header.import { background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); }
.doc-show-header.export { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); }
.doc-show-code { font-size: 1.8rem; font-weight: 900; letter-spacing: .02em; }
.doc-show-type { display: inline-block; background: rgba(255,255,255,.2); padding: 3px 14px; border-radius: 20px; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
.meta-block { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); padding: 20px 24px; margin-bottom: 20px; }
.meta-row { display: flex; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: .87rem; }
.meta-row:last-child { border-bottom: 0; }
.meta-label { color: #64748b; min-width: 130px; font-weight: 600; }
.meta-value { color: #0f172a; font-weight: 700; }
.items-table { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); overflow: hidden; margin-bottom: 20px; }
.items-table .table { margin: 0; }
.items-table thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.items-table th { padding: 12px 16px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
.items-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.items-table tbody tr:last-child td { border-bottom: 0; }
.user-badge { display: inline-flex; align-items: center; gap: 10px; background: #f1f5f9; border-radius: 10px; padding: 8px 14px; }
.user-avatar { width: 38px; height: 38px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: .95rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.user-avatar.export { background: #fee2e2; color: #b91c1c; }
.summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: .88rem; color: #475569; }
.summary-row.total { border-top: 2px solid #e2e8f0; margin-top: 8px; padding-top: 12px; font-weight: 800; color: #0f172a; font-size: 1rem; }
</style>
@endpush

@section('content')

@php
    $isImport = $document->type === 'import';
    $backRoute = $isImport ? 'warehouse.stock-in' : 'warehouse.stock-out';
    $backLabel = $isImport ? 'Danh sách Nhập Kho' : 'Danh sách Xuất Kho';
    $itemsSubtotal = $document->items->sum(fn($i) => $i->quantity * $i->unit_cost);
    $shippingFee   = (float) ($document->shipping_fee ?? 0);
    $grandTotal    = $itemsSubtotal + $shippingFee;
@endphp

{{-- Back --}}
<div class="mb-3">
    <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>{{ $backLabel }}
    </a>
</div>

{{-- Header --}}
<div class="doc-show-header {{ $isImport ? 'import' : 'export' }}">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="doc-show-code">{{ $document->document_number ?? '#'.$document->id }}</div>
            <div class="mt-2">
                <span class="doc-show-type">{{ $isImport ? 'Phiếu Nhập Kho' : 'Phiếu Xuất Kho' }}</span>
            </div>
        </div>
        <div class="text-end">
            <div style="font-size:.82rem;opacity:.75;">Ngày tạo</div>
            <div style="font-weight:700;">{{ $document->created_at->format('H:i d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Meta info --}}
        <div class="meta-block">
            <div class="meta-row">
                <div class="meta-label"><i class="bi bi-calendar3 me-2 text-muted"></i>Ngày phiếu</div>
                <div class="meta-value">{{ $document->document_date->format('d/m/Y') }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label"><i class="bi bi-building me-2 text-muted"></i>Kho</div>
                <div class="meta-value">{{ $document->warehouse?->name ?? '—' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label"><i class="bi bi-person me-2 text-muted"></i>Người tạo phiếu</div>
                <div>
                    <div class="user-badge">
                        <div class="user-avatar {{ $isImport ? '' : 'export' }}">
                            {{ strtoupper(substr($document->user?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:.88rem;">{{ $document->user?->name ?? '—' }}</div>
                            <div style="font-size:.72rem;color:#64748b;">{{ $document->created_at->format('H:i - d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @if($document->notes)
            <div class="meta-row">
                <div class="meta-label"><i class="bi bi-chat-left-text me-2 text-muted"></i>Ghi chú</div>
                <div class="meta-value" style="font-weight:400;">{{ $document->notes }}</div>
            </div>
            @endif
        </div>

        {{-- Items --}}
        <div class="items-table">
            <div style="padding:14px 18px 10px;font-weight:700;font-size:.9rem;color:#0f172a;border-bottom:1px solid #f1f5f9;">
                <i class="bi bi-list-ul me-1"></i>Chi tiết hàng hoá ({{ $document->items->count() }} dòng)
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-center">ĐVT</th>
                        <th class="text-center">Khối lượng</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($document->items as $i => $item)
                    @php
                        $unitLabel = $item->productVariant?->product?->unit_label ?? 'Cái';
                        $weightUnitLabel = in_array((string) ($item->productVariant?->product?->unit ?? 'cai'), ['con', 'cai'], true)
                            ? 'Kg'
                            : $unitLabel;
                        $lineWeight = (float) (($item->productVariant?->size ?? 0) * ($item->quantity ?? 0));
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:700;font-size:.88rem;">{{ $item->productVariant?->product?->name ?? '—' }}</div>
                            <div style="font-size:.75rem;color:#64748b;">{{ $item->productVariant?->name }}</div>
                        </td>
                        <td><code class="text-muted small">{{ $item->productVariant?->sku ?? '—' }}</code></td>
                        <td class="text-center fw-700 {{ $isImport ? 'text-success' : 'text-danger' }}">
                            {{ number_format($item->quantity) }}
                        </td>
                        <td class="text-center">{{ $unitLabel }}</td>
                        <td class="text-center">{{ number_format($lineWeight, 3) }} {{ $weightUnitLabel }}</td>
                        <td class="text-end">{{ number_format($item->unit_cost) }}đ</td>
                        <td class="text-end fw-700">{{ number_format($item->quantity * $item->unit_cost) }}đ</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Summary --}}
        <div class="meta-block">
            <div style="font-weight:800;font-size:.9rem;margin-bottom:14px;color:#0f172a;">
                <i class="bi bi-receipt me-1"></i>Tổng kết
            </div>
            <div class="summary-row">
                <span>Số dòng hàng hoá</span>
                <strong>{{ $document->items->count() }}</strong>
            </div>
            <div class="summary-row">
                <span>Tổng số lượng</span>
                <strong>{{ number_format($document->items->sum('quantity')) }}</strong>
            </div>
            <div class="summary-row">
                <span>Tiền hàng</span>
                <strong>{{ number_format($itemsSubtotal) }}đ</strong>
            </div>
            @if($shippingFee > 0)
            <div class="summary-row">
                <span>Phí vận chuyển</span>
                <strong>{{ number_format($shippingFee) }}đ</strong>
            </div>
            @endif
            <div class="summary-row total">
                <span>Tổng cộng</span>
                <span style="color:{{ $isImport ? '#0ea5e9' : '#ef4444' }}">{{ number_format($grandTotal) }}đ</span>
            </div>
        </div>

        {{-- Print --}}
        <div class="meta-block">
            <button onclick="window.print()" class="btn btn-outline-secondary w-100 btn-sm">
                <i class="bi bi-printer me-1"></i> In phiếu
            </button>
        </div>
    </div>
</div>

@endsection
