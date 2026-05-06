@extends('layouts.site')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Bảng giá sản phẩm hàng ngày', 'url' => route('pages.my_orders.daily_prices')],
    ['label' => 'Bảng giá sản phẩm hàng ngày', 'url' => '']
]"/>
@endsection

@section('content')
<style>
    .sp-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 40px 0 68px;
    }
    .sp-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .sp-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
    }
    .sp-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .sp-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .sp-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 18px;
        min-height: 100%;
    }
    .sp-kpi-title {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .sp-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }
    .sp-filter {
        padding: 24px;
    }
    .sp-filter .form-control {
        height: 48px;
        border-radius: 14px;
        border-color: #d8deea;
    }
    .sp-filter .form-select[multiple] {
        min-height: 140px;
        border-radius: 14px;
        border-color: #d8deea;
    }
    .sp-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .sp-company-card {
        padding: 24px;
    }
    .sp-company-header {
        display: grid;
        grid-template-columns: 160px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }
    .sp-company-logo-wrap {
        min-height: 120px;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        padding-right: 0;
    }
    .sp-company-logo {
        max-height: 88px;
        max-width: 100%;
        object-fit: contain;
    }
    .sp-company-title {
        font-weight: 900;
        font-size: 1.15rem;
        color: #0f172a;
        margin-bottom: 10px;
    }
    .sp-company-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 16px;
        font-size: .86rem;
        color: #475569;
    }
    .sp-company-line {
         
        padding: 2px 10px;
    }
    .sp-company-line.full {
        grid-column: 1 / -1;
    }
    .sp-company-label {
        color: #0f172a;
        font-weight: 700;
        margin-right: 6px;
    }
    .sp-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .sp-actions .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 8px 14px;
    }
    .sp-signature {
        text-align: center;
    }
    .sp-signature-title {
        text-align: center;
        margin-bottom: 60px;
        font-size: 0.9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .sp-signature-name {
        padding-top: 12px;
        font-size: 0.85rem;
        color: #0f172a;
        font-weight: 600;
    }
    .sp-table-wrap {
        padding: 0 18px 18px;
    }
    .sp-table {
        margin-bottom: 0;
        min-width: 900px;
    }
    .sp-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        padding: 16px 14px;
        white-space: nowrap;
    }
    .sp-table tbody td {
        padding: 18px 14px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .sp-avatar {
        width: 52px;
        height: 52px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e8edf5;
    }
    .sp-product-name {
        font-weight: 800;
        color: #0f172a;
    }
    .sp-product-sub {
        font-size: .82rem;
        color: #64748b;
    }
    .sp-price {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .sp-page-badge {
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
    .sp-empty {
        padding: 44px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    @media (max-width: 991.98px) {
        .sp-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .sp-filter {
            padding: 20px;
        }
    }
    @media (max-width: 767.98px) {
        .sp-page {
            padding: 20px 0 48px;
        }
        .sp-shell {
            padding: 0 12px;
        }
        .sp-kpi-value {
            font-size: 1.35rem;
        }
        .sp-company-grid {
            grid-template-columns: 1fr;
        }
        .sp-company-header {
            grid-template-columns: 1fr;
        }
        .sp-actions {
            justify-content: flex-start;
        }
    }

    @media print {
        .breadcrumb,
        .sp-hero,
        .sp-filter,
        .sp-actions,
        .sp-non-export,
        .btn,
        .pagination,
        .card-footer {
            display: none !important;
        }

        .sp-page {
            background: #fff !important;
            padding: 0 !important;
        }

        .sp-shell,
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .sp-card {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0 !important;
        }
    }
</style>

<div class="sp-page">
    <div class="container sp-shell">
        <div class="sp-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-6">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Pricing Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Bảng giá sản phẩm hàng ngày</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:560px;">
                        Theo dõi giá bán đang hiệu lực cho từng biến thể để sale chốt đơn nhanh, chuẩn giá và đồng bộ toàn team.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="sp-kpi">
                                <div class="sp-kpi-title">Tổng sản phẩm</div>
                                <div class="sp-kpi-value">{{ number_format($products->total()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="sp-kpi">
                                <div class="sp-kpi-title">Tổng biến thể</div>
                                <div class="sp-kpi-value">{{ number_format($totalVariants ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="sp-kpi">
                                <div class="sp-kpi-title">Cập nhật lúc</div>
                                <div class="sp-kpi-value">{{ $asOfDate->format('H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card sp-card mb-4" id="priceExportScope">
            <div class="sp-filter">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1 fw-bold">Bộ lọc bảng giá</h2>
                        <p class="mb-0 text-muted">Tìm nhanh theo tên sản phẩm, biến thể hoặc SKU.</p>
                    </div> 
                </div>

                <form method="GET" action="{{ route('pages.my_orders.daily_prices') }}" class="row g-2">
                    <div class="col-12 col-md-4">
                        <input type="text" name="keyword" value="{{ $keyword }}" class="form-control" placeholder="Tìm theo tên sản phẩm, tên biến thể, SKU">
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="product_ids[]" class="form-select" multiple>
                            @foreach(($selectableProducts ?? collect()) as $selectableProduct)
                                <option value="{{ $selectableProduct->id }}" {{ in_array((int) $selectableProduct->id, $selectedProductIds ?? [], true) ? 'selected' : '' }}>
                                    {{ $selectableProduct->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Chọn sản phẩm cần hiển thị báo giá (giữ Ctrl/Cmd để chọn nhiều).</small>
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i>Lọc</button>
                    </div>
                    <div class="col-6 col-md-2">
                        <a href="{{ route('pages.my_orders.daily_prices') }}" class="btn btn-light border w-100">Đặt lại</a>
                    </div>
                    <div class="col-12 col-md-12 d-flex align-items-center">
                        <div class="form-check mt-2 mt-md-0">
                            <input class="form-check-input" type="checkbox" id="show_all_variants" name="show_all_variants" value="1" {{ !empty($showAllVariants) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_all_variants">
                                Hiển thị tất cả biến thể
                            </label>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Company Information Header -->
        @php
            $brandName       = $settings['brand_name']->value ?? null;
            $companyLegal    = $settings['company_legal_name']->value ?? null;
            $brandAddr       = $settings['address']->value ?? null;
            $brandPhone      = $settings['hotline']->value ?? null;
            $brandTax        = $settings['tax_number']->value ?? null;
            $brandEmail      = $settings['email']->value ?? null;
            $bankAccount     = $settings['bank_account']->value ?? null;
            $bankName        = $settings['bank_name']->value ?? null;
            $bankBranch      = $settings['bank_branch']->value ?? null;
            $priceLogoId     = $settings['price_logo']->value ?? null;
            $logoMediaId     = $priceLogoId ?: ($settings['logo']->value ?? null);
            $logoMedia       = $logoMediaId ? App\Models\Media::find($logoMediaId) : null;
        @endphp
        <div class="card sp-card mb-4">
            
            <div id="pdfExportContent">
                <div class="sp-company-card">
                    <div class="sp-company-header">
                        <div>
                            <div class="sp-company-logo-wrap">
                                @if($logoMedia)
                                    <img src="{{ asset('storage/' . $logoMedia->file_path) }}" alt="{{ $brandName }}" class="sp-company-logo">
                                @else
                                    <div style="height: 64px; width: 64px; display: flex; align-items: center; justify-content: center;    font-weight: 900; font-size: 1.4rem; color: #334155;">
                                        {{ mb_substr($companyLegal ?? $brandName ?? 'C', 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="sp-company-title">{{ $companyLegal ?? $brandName ?? '-' }}</div>

                            <div class="sp-company-grid">
                                @if($brandTax)
                                    <div class="sp-company-line"><span class="sp-company-label">Mã số thuế:</span>{{ $brandTax }}</div>
                                @endif
                                @if($user->phone)
                                    <div class="sp-company-line"><span class="sp-company-label">Điện thoại:</span>{{ $user->phone }}</div>
                                @elseif($brandPhone)
                                    <div class="sp-company-line"><span class="sp-company-label">Điện thoại:</span>{{ $brandPhone }}</div>
                                @endif
                                @if($user->email)
                                    <div class="sp-company-line"><span class="sp-company-label">Email:</span>{{ $user->email }}</div>
                                @endif
                                @if($brandAddr)
                                    <div class="sp-company-line full"><span class="sp-company-label">Địa chỉ:</span>{{ $brandAddr }}</div>
                                @endif
                                @if($bankAccount)
                                    <div class="sp-company-line"><span class="sp-company-label">Số tài khoản:</span>{{ $bankAccount }}</div>
                                @endif
                                @if($bankName)
                                    <div class="sp-company-line"><span class="sp-company-label">Ngân hàng:</span>{{ $bankName }}</div>
                                @endif
                                @if($bankBranch)
                                    <div class="sp-company-line full"><span class="sp-company-label">Chi nhánh:</span>{{ $bankBranch }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p4 Text-center border-top" style="background: #f8f9fa;">
                    <h2 class="text-center p-4">BẢNG BÁO GIÁ</h2>
                </div>

                <div class="sp-table-wrap">
                    <div class="d-flex justify-content-between align-items-center px-1 py-3">
                        <span class="date-approved">
                            <i class="fa fa-calendar"></i>
                            Hiệu lực: <strong>{{ $asOfDate->format('d/m/Y H:i') }}</strong>
                        </span> 
                        <span class="text-muted small">Trang {{ $products->currentPage() }}/{{ max(1, $products->lastPage()) }}</span>
                    </div>
                    <div class="table-responsive border-top">
                        <table class="table sp-table mb-0">
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
                                @forelse($products as $index => $product)
                                    @php
                                        $price = (float) ($product->current_price ?? 0);
                                        $imagePath = $product->avatar?->media?->file_path;
                                        $allVariantsSamePrice = ($product->priceDiffVariants->count() ?? 0) === 0;
                                        $size = $allVariantsSamePrice ? 'ALL' : '-';
                                    @endphp
                                    <tr>
                                        <td>{{ $products->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($imagePath)
                                                    <img src="{{ asset('storage/' . $imagePath) }}" alt="{{ $product->name }}" class="sp-avatar">
                                                @else
                                                    <div class="sp-avatar d-flex align-items-center justify-content-center bg-light text-muted">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="sp-product-name">{{ $product->name ?? '-' }}</div>
                                                    <div class="sp-product-sub">
                                                        {{ number_format((int) ($product->total_variants_count ?? 0)) }} biến thể
                                                        @if(($product->priceDiffVariants->count() ?? 0) > 0)
                                                            · {{ number_format($product->priceDiffVariants->count()) }} {{ !empty($showAllVariants) ? 'biến thể hiển thị' : 'biến thể lệch giá' }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->is_priced_by_kg ? 'kg' : ($product->unit_label ?? '-') }}</td>
                                        <td>{{ $size }}</td>
                                        <td class="text-end"><span class="sp-price">{{ number_format($price, 0, ',', '.') }} đ</span></td>
                                    </tr>

                                    @foreach(($product->priceDiffVariants ?? collect()) as $diffVariant)
                                        <tr>
                                            <td></td>
                                            <td>
                                                <div class="ps-4">
                                                    <div class="fw-semibold">• {{ $diffVariant->name ?: ('Biến thể #' . $diffVariant->id) }}</div>
                                                    <div class="sp-product-sub">SKU: {{ $diffVariant->sku ?: '-' }}</div>
                                                </div>
                                            </td>
                                            <td>{{ $product->is_priced_by_kg ? 'kg' : ($product->unit_label ?? '-') }}</td>
                                            <td>{{ $diffVariant->size ?: '-' }}</td>
                                            <td class="text-end"><span class="sp-price">{{ number_format((float) ($diffVariant->current_price ?? 0), 0, ',', '.') }} đ</span></td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="5"><div class="sp-empty">Không có dữ liệu bảng giá theo bộ lọc hiện tại.</div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            
                @if($products->hasPages())
                    <div class="card-footer bg-white border-0 pt-2 pb-3">
                        {{ $products->links() }}
                    </div>
                @endif
                <div class="row mt-4 sp-non-export">
                    <!-- Notes -->
                    <div class="col-md-8" >
                        <div class="pl-4">
                            <h5 style="font-weight: 700; color: #0f172a; margin-bottom: 12px;">Ghi chú:</h5>
                            <ul style="margin: 0; padding-left: 24px; color: #64748b; line-height: 1.8; margin-left: 15px;">
                                <li>Giá trên chưa bao gồm VAT</li>
                                <li>Các giấy tờ theo yêu cầu</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4" >
                        <!-- Signature Section -->
                        @php
                            $userRole = $user->roles->first();
                            $roleTitleMap = [
                                'sale' => 'Nhân viên Kinh Doanh',
                                'leader_sale' => 'Trưởng phòng Kinh Doanh',
                                'leader' => 'Trưởng phòng Kinh Doanh',
                                'sale_manager' => 'Giám đốc Kinh Doanh',
                                'manager' => 'Giám đốc',
                                'director' => 'Giám đốc',
                                'admin' => 'Quản trị viên',
                            ];
                            $userTitle = trim((string) ($userRole?->description ?? ''));
                            if ($userTitle === '') {
                                $userTitle = $roleTitleMap[strtolower((string) ($userRole?->name ?? ''))]
                                    ?? ucwords(str_replace(['_', '-'], ' ', (string) ($userRole?->name ?? 'Nhân viên Kinh Doanh')));
                            }
                        @endphp
                        <div class="sp-signature">
                            <div>
                                Đại diện công ty
                            </div>
                            <div class="sp-signature-title">
                                {{ $userTitle }}
                            </div>
                            <div>
                                <div class="sp-signature-name">{{ $user->name ?? '-' }}</div>
                            </div>
                        </div>

                    </div>

                    <div style="text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e8edf5;">
                        <div style="font-size: 0.8rem; color: #94a3b8;" class="pb-3">
                            Bảng giá có hiệu lực từ lúc {{ $asOfDate->format('H:i:s') }} ngày {{ $asOfDate->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-md-12">
                <div style="padding: 12px 24px;">
                    <div class="sp-actions">
                        <button type="button" class="btn btn-outline-primary" id="btnExportPdf">
                            <i class="fa fa-file-pdf-o me-1"></i>Xuất PDF
                        </button>
                        <a href="#" class="btn btn-outline-success" id="btnShareZalo" target="_blank" rel="noopener">
                            <i class="fa fa-share-alt me-1"></i>Chia sẻ Zalo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var pdfButton = document.getElementById('btnExportPdf');
    if (pdfButton) {
        pdfButton.addEventListener('click', function () {
            var exportNode = document.getElementById('pdfExportContent');
            if (!exportNode) {
                window.print();
                return;
            }

            var printWindow = window.open('', '_blank', 'width=1024,height=768');
            if (!printWindow) {
                window.print();
                return;
            }

            var styleTags = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
                .map(function (node) { return node.outerHTML; })
                .join('');

            var printHtml = `
                <!doctype html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Bang bao gia</title>
                    ${styleTags}
                    <style>
                        body { background: #fff !important; margin: 0; padding: 16px; }
                        .card { border: none !important; box-shadow: none !important; }
                        .sp-actions { display: none !important; }
                        .card-footer { display: block !important; }
                        .sp-non-export { display: flex !important; }
                        .sp-company-header {
                            display: grid !important;
                            grid-template-columns: 160px minmax(0, 1fr) !important;
                            gap: 20px !important;
                            align-items: start !important;
                        }
                        .sp-company-logo-wrap {
                            justify-content: flex-start !important;
                            align-items: flex-start !important;
                            padding-right: 0 !important;
                            min-height: auto !important;
                        }
                        .sp-company-logo {
                            display: block !important;
                            margin: 0 !important;
                        }
                        .sp-signature,
                        .sp-signature-title,
                        .sp-signature-name { 
                            text-align: center !important;
                        }
                        .sp-company-grid {
                            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        }
                    </style>
                </head>
                <body>
                    ${exportNode.outerHTML}
                </body>
                </html>
            `;

            printWindow.document.open();
            printWindow.document.write(printHtml);
            printWindow.document.close();

            printWindow.onload = function () {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
        });
    }

    var shareButton = document.getElementById('btnShareZalo');
    if (shareButton) {
        var shareUrl = encodeURIComponent(window.location.href);
        shareButton.setAttribute('href', 'https://zalo.me/share?url=' + shareUrl);
    }
});
</script>
@endsection
