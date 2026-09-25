@extends('layouts.site')

@section('title', 'Hoàng Long TNT Profile')

@push('styles')
<style>
    .hlt-profile {
        background: linear-gradient(180deg, #f8fafc 0%, #eef4f3 100%);
        padding: 28px 0 44px;
        min-height: 60vh;
    }
    .hlt-profile-shell {
        max-width: 1040px;
    }
    .hlt-profile-hero {
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        padding: 24px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .hlt-profile-title {
        color: #0f172a;
        font-size: 1.85rem;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .hlt-profile-body {
        color: #334155;
        font-size: 15px;
        line-height: 1.75;
        white-space: pre-line;
    }
    .hlt-doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
    }
    .hlt-doc-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 72px;
        padding: 14px;
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
    }
    .hlt-doc-link:hover {
        color: #0f766e;
        border-color: #0f766e;
    }
    .hlt-doc-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #ccfbf1;
        color: #0f766e;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 18px;
    }
    .hlt-price-card {
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .hlt-price-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid #e5edf2;
        background: #f8fafc;
    }
    .hlt-price-title {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
    }
    .hlt-price-meta {
        color: #64748b;
        font-size: 13px;
        white-space: nowrap;
    }
    .hlt-price-table {
        min-width: 760px;
        margin-bottom: 0;
    }
    .hlt-price-table thead th {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        border-bottom-color: #e5edf2;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .hlt-price-table tbody td {
        border-color: #edf2f7;
        padding: 13px 14px;
        vertical-align: middle;
    }
    .hlt-product-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 280px;
    }
    .hlt-product-avatar {
        width: 44px;
        height: 44px;
        border: 1px solid #e5edf2;
        border-radius: 8px;
        object-fit: cover;
        background: #f8fafc;
        flex: 0 0 auto;
    }
    .hlt-product-name {
        color: #0f172a;
        font-weight: 800;
    }
    .hlt-product-sub {
        color: #64748b;
        font-size: 12px;
    }
    .hlt-price-value {
        color: #0f172a;
        font-weight: 800;
        white-space: nowrap;
    }
    .hlt-variant-row td {
        background: #fbfdff;
    }
    .hlt-empty {
        padding: 28px;
        text-align: center;
        color: #64748b;
    }
    @media (max-width: 767.98px) {
        .hlt-profile-title {
            font-size: 1.45rem;
        }
        .hlt-price-head {
            display: block;
        }
        .hlt-price-meta {
            display: block;
            margin-top: 6px;
            white-space: normal;
        }
    }
</style>
@endpush

@section('content')
<main class="hlt-profile">
    <div class="container hlt-profile-shell">
        <section class="hlt-profile-hero mb-3">
            <div class="text-muted small text-uppercase fw-semibold mb-1">Company Profile</div>
            <h1 class="hlt-profile-title">Hoàng Long TNT Profile</h1>
            @if(filled($profileInfo))
                <div class="hlt-profile-body">{{ $profileInfo }}</div>
            @else
                <div class="text-muted">Thông tin profile đang được cập nhật.</div>
            @endif
        </section>

        <section class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Tài liệu đính kèm</h5>
            </div>
            @if(count($documents) > 0)
                <div class="hlt-doc-grid">
                    @foreach($documents as $document)
                        @php
                            $href = $document['url'] ?: $document['file_url'];
                        @endphp
                        @if($href)
                            <a href="{{ $href }}" class="hlt-doc-link" target="_blank" rel="noopener">
                                <span class="hlt-doc-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="fw-semibold">{{ $document['title'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="hlt-profile-hero text-muted">Chưa có tài liệu đính kèm.</div>
            @endif
        </section>

        <section class="mt-3">
            <div class="hlt-price-card">
                <div class="hlt-price-head">
                    <div>
                        <h2 class="hlt-price-title">Bảng giá sản phẩm hàng ngày</h2>
                        <div class="text-muted small">Đồng bộ từ trang /my-orders/daily-prices.</div>
                    </div>
                    <div class="hlt-price-meta">
                        {{ number_format(($priceProducts ?? collect())->count()) }} sản phẩm ·
                        {{ number_format($totalPriceVariants ?? 0) }} biến thể ·
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table hlt-price-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sản phẩm</th>
                                <th>DVT</th>
                                <th>Size</th>
                                <th class="text-end">Bảng giá (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($priceProducts ?? collect()) as $index => $product)
                                @php
                                    $price = (float) ($product->current_price ?? 0);
                                    $imagePath = $product->avatar?->media?->file_path;
                                    $differentVariants = $product->priceDiffVariants ?? collect();
                                    $size = $differentVariants->count() === 0 ? 'ALL' : '-';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="hlt-product-cell">
                                            @if($imagePath)
                                                <img src="{{ asset('storage/' . $imagePath) }}" alt="{{ $product->name }}" class="hlt-product-avatar">
                                            @else
                                                <div class="hlt-product-avatar d-flex align-items-center justify-content-center text-muted">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="hlt-product-name">{{ $product->name ?? '-' }}</div>
                                                <div class="hlt-product-sub">
                                                    {{ number_format((int) ($product->total_variants_count ?? 0)) }} biến thể
                                                    @if($differentVariants->count() > 0)
                                                        · {{ number_format($differentVariants->count()) }} biến thể lệch giá
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $product->is_priced_by_kg ? 'kg' : ($product->unit_label ?? '-') }}</td>
                                    <td>{{ $size }}</td>
                                    <td class="text-end"><span class="hlt-price-value">{{ number_format($price, 0, ',', '.') }} đ</span></td>
                                </tr>

                                @foreach($differentVariants as $diffVariant)
                                    <tr class="hlt-variant-row">
                                        <td></td>
                                        <td>
                                            <div class="ps-4">
                                                <div class="fw-semibold">• {{ $diffVariant->name ?: ('Biến thể #' . $diffVariant->id) }}</div>
                                                <div class="hlt-product-sub">SKU: {{ $diffVariant->sku ?: '-' }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $product->is_priced_by_kg ? 'kg' : ($product->unit_label ?? '-') }}</td>
                                        <td>{{ $diffVariant->size ?: '-' }}</td>
                                        <td class="text-end">
                                            <span class="hlt-price-value">{{ number_format((float) ($diffVariant->current_price ?? 0), 0, ',', '.') }} đ</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="hlt-empty">Chưa có dữ liệu bảng giá sản phẩm.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
