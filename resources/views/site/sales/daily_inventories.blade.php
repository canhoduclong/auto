@extends('layouts.site')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Tồn kho hàng ngày', 'url' => route('pages.my_orders.daily_inventories')],
    ['label' => 'Tồn kho hàng ngày', 'url' => '']
]"/>
@endsection

@section('content')
<style>
    .si-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 40px 0 68px;
    }
    .si-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .si-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
    }
    .si-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .si-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .si-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 18px;
        min-height: 100%;
    }
    .si-kpi-title {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .si-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }
    .si-filter {
        padding: 24px;
    }
    .si-filter .form-control {
        height: 48px;
        border-radius: 14px;
        border-color: #d8deea;
    }
    .si-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .si-table-wrap {
        padding: 0 18px 18px;
    }
    .si-table {
        margin-bottom: 0;
        min-width: 900px;
    }
    .si-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        padding: 16px 14px;
        white-space: nowrap;
    }
    .si-table tbody td {
        padding: 18px 14px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .si-avatar {
        width: 52px;
        height: 52px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e8edf5;
    }
    .si-product-name {
        font-weight: 800;
        color: #0f172a;
    }
    .si-product-sub {
        font-size: .82rem;
        color: #64748b;
    }
    .si-stock {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-weight: 700;
        font-size: .8rem;
    }
    .si-stock.ok {
        background: #ecfdf5;
        color: #047857;
    }
    .si-stock.low {
        background: #fff7ed;
        color: #c2410c;
    }
    .si-stock.out {
        background: #fef2f2;
        color: #b91c1c;
    }
    .si-page-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #d8deea;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: .82rem;
        font-weight: 700;
        color: #334155;
        background: #f8fafc;
    }
    .si-empty {
        padding: 44px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    @media (max-width: 991.98px) {
        .si-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .si-filter {
            padding: 20px;
        }
    }
    @media (max-width: 767.98px) {
        .si-page {
            padding: 20px 0 48px;
        }
        .si-shell {
            padding: 0 12px;
        }
        .si-kpi-value {
            font-size: 1.35rem;
        }
    }
</style>

<div class="si-page">
    <div class="container si-shell">
        <div class="si-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-6">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Inventory Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Tồn kho hàng ngày</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:560px;">
                        Theo dõi tồn khả dụng của từng biến thể để sale tư vấn chính xác, tránh nhận đơn vượt tồn kho thực tế.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="si-kpi">
                                <div class="si-kpi-title">Tổng biến thể</div>
                                <div class="si-kpi-value">{{ number_format($variants->total()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="si-kpi">
                                <div class="si-kpi-title">Sắp hết (&lt; 20)</div>
                                <div class="si-kpi-value">{{ number_format($lowCount) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="si-kpi">
                                <div class="si-kpi-title">Hết hàng</div>
                                <div class="si-kpi-value">{{ number_format($outCount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $outCount = $variants->getCollection()->filter(fn($v) => (int) $v->available_stock <= 0)->count();
            $lowCount = $variants->getCollection()->filter(fn($v) => (int) $v->available_stock > 0 && (int) $v->available_stock < 20)->count();
        @endphp

        <div class="card si-card mb-4">
            <div class="si-filter">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1 fw-bold">Bộ lọc tồn kho</h2>
                        <p class="mb-0 text-muted">Tìm nhanh theo tên sản phẩm, biến thể hoặc SKU.</p>
                    </div>
                    <span class="si-page-badge">
                        <i class="fa fa-calendar"></i>
                        Cập nhật: {{ $asOfDate->format('d/m/Y H:i') }}
                    </span>
                </div>

                <form method="GET" action="{{ route('pages.my_orders.daily_inventories') }}" class="row g-2">
                    <div class="col-12 col-md-8">
                        <input type="text" name="keyword" value="{{ $keyword }}" class="form-control" placeholder="Tìm theo tên sản phẩm, tên biến thể, SKU">
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i>Lọc</button>
                    </div>
                    <div class="col-6 col-md-2">
                        <a href="{{ route('pages.my_orders.daily_inventories') }}" class="btn btn-light border w-100">Đặt lại</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card si-card">
            <div class="si-table-wrap">
                <div class="d-flex justify-content-between align-items-center px-1 py-3">
                    <h3 class="h6 mb-0 fw-bold">Danh sách tồn kho biến thể</h3>
                    <span class="text-muted small">Trang {{ $variants->currentPage() }}/{{ max(1, $variants->lastPage()) }}</span>
                </div>
                <div class="table-responsive border-top">
                    <table class="table si-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sản phẩm</th>
                                <th>Biến thể</th>
                                <th>SKU</th>
                                <th class="text-end">Tồn khả dụng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variants as $index => $variant)
                                @php
                                    $availableStock = (int) $variant->available_stock;
                                    $stockClass = $availableStock <= 0 ? 'out' : ($availableStock < 20 ? 'low' : 'ok');
                                    $imagePath = $variant->product?->avatar?->media?->file_path;
                                @endphp
                                <tr>
                                    <td>{{ $variants->firstItem() + $index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($imagePath)
                                                <img src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant->product?->name }}" class="si-avatar">
                                            @else
                                                <div class="si-avatar d-flex align-items-center justify-content-center bg-light text-muted">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="si-product-name">{{ $variant->product?->name ?? '-' }}</div>
                                                <div class="si-product-sub">Mã biến thể: {{ $variant->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">{{ $variant->name ?: '-' }}</td>
                                    <td><span class="badge text-bg-light border">{{ $variant->sku ?: '-' }}</span></td>
                                    <td class="text-end">
                                        <span class="si-stock {{ $stockClass }}">{{ number_format($availableStock) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><div class="si-empty">Không có dữ liệu tồn kho theo bộ lọc hiện tại.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($variants->hasPages())
                <div class="card-footer bg-white border-0 pt-2 pb-3">
                    {{ $variants->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
