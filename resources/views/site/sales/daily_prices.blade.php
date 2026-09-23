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
         text-align: center 
         
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
    .sp-select-col { width: 48px; text-align: center; }
    .sp-product-check,
    #spSelectAll { width: 18px; height: 18px; cursor: pointer; }
    .sp-product-row.is-selected td { background: #f0f9ff; }
    .sp-product-row { cursor: pointer; }
    .sp-selection-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin: 0 0 12px;
    }
    .sp-selection-toolbar .btn { border-radius: 10px; font-weight: 700; }
    .sp-export-summary { color: #64748b; font-size: .86rem; font-weight: 600; }
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
                        <label class="form-label small fw-semibold mb-1">Lọc theo sản phẩm</label>
                        <select name="product_ids[]" class="form-select sp-native-multiselect" multiple size="5">
                            @foreach(($selectableProducts ?? collect()) as $selectableProduct)
                                <option value="{{ $selectableProduct->id }}" {{ in_array((int) $selectableProduct->id, $selectedProductIds ?? [], true) ? 'selected' : '' }}>
                                    {{ $selectableProduct->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Giữ Ctrl để chọn nhiều sản phẩm. Việc chọn sản phẩm xuất báo giá được thực hiện ở bảng bên dưới.</small>
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
            $displayPhone    = $user->phone ?: ($user->customer?->phone ?: $brandPhone);
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
                                @if($displayPhone)
                                    <div class="sp-company-line"><span class="sp-company-label">Điện thoại:</span>{{ $displayPhone }}</div>
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
                                @if($bankBranch)
                                    <div class="sp-company-line full"><span class="sp-company-label">Chi nhánh:</span>{{ $bankBranch }}</div>
                                @endif
                                @if($bankName)
                                    <div class="sp-company-line"><span class="sp-company-label">Ngân hàng:</span>{{ $bankName }}</div>
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
                        <span class="text-muted small sp-page-number">Trang {{ $products->currentPage() }}/{{ max(1, $products->lastPage()) }}</span>
                    </div>
                    <div class="sp-selection-toolbar sp-selection-control">
                        <div>
                            <div class="fw-bold">Chọn sản phẩm đưa vào báo giá</div>
                            <div class="small text-muted">Tích ô hoặc bấm trực tiếp vào dòng sản phẩm.</div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="sp-export-summary" id="spTableSelectionSummary">Chưa chọn sản phẩm</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="spSelectVisible">Chọn tất cả trang này</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="spClearSelection">Bỏ chọn</button>
                        </div>
                    </div>
                    <div class="table-responsive border-top">
                        <table class="table sp-table mb-0">
                            <thead>
                                <tr>
                                    <th class="sp-select-col sp-selection-control">
                                        <input type="checkbox" id="spSelectAll" aria-label="Chọn tất cả sản phẩm trên trang">
                                    </th>
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
                                    <tr class="sp-product-row" data-product-id="{{ $product->id }}">
                                        <td class="sp-select-col sp-selection-control">
                                            <input type="checkbox" class="form-check-input sp-product-check" value="{{ $product->id }}" aria-label="Chọn {{ $product->name }} để báo giá" @checked(in_array((int) $product->id, $selectedProductIds ?? [], true))>
                                        </td>
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
                                        <tr class="sp-variant-row" data-parent-product-id="{{ $product->id }}">
                                            <td class="sp-selection-control"></td>
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
                                        <td colspan="6"><div class="sp-empty">Không có dữ liệu bảng giá theo bộ lọc hiện tại.</div></td>
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
                        <span class="sp-export-summary" id="spExportSummary">Chưa chọn sản phẩm</span>
                        <button type="button" class="btn btn-outline-primary" id="btnExportPdf">
                            <i class="fa fa-file-pdf-o me-1"></i>Xuất PDF báo giá đã chọn
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

<script src="{{ asset('js/html2canvas.min.js') }}"></script>
<script src="{{ asset('js/jspdf.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Global site JS applies Nice Select to every select, which does not
    // support this native multiple selector correctly.
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.niceSelect) {
        window.jQuery('.sp-native-multiselect').niceSelect('destroy');
    }

    var productChecks = Array.from(document.querySelectorAll('.sp-product-check'));
    var selectAll = document.getElementById('spSelectAll');
    var exportSummary = document.getElementById('spExportSummary');
    var tableSelectionSummary = document.getElementById('spTableSelectionSummary');

    function refreshProductSelection() {
        var selectedCount = productChecks.filter(function (checkbox) { return checkbox.checked; }).length;
        productChecks.forEach(function (checkbox) {
            var row = checkbox.closest('.sp-product-row');
            if (row) row.classList.toggle('is-selected', checkbox.checked);
        });
        if (selectAll) {
            selectAll.checked = productChecks.length > 0 && selectedCount === productChecks.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < productChecks.length;
        }
        if (exportSummary) {
            exportSummary.textContent = selectedCount > 0
                ? 'Đã chọn ' + selectedCount + ' sản phẩm'
                : 'Chưa chọn sản phẩm';
        }
        if (tableSelectionSummary) {
            tableSelectionSummary.textContent = selectedCount > 0
                ? 'Đã chọn ' + selectedCount + '/' + productChecks.length + ' sản phẩm'
                : 'Chưa chọn sản phẩm';
        }
    }

    productChecks.forEach(function (checkbox) {
        checkbox.addEventListener('change', refreshProductSelection);
        var row = checkbox.closest('.sp-product-row');
        if (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('input, a, button')) return;
                checkbox.checked = !checkbox.checked;
                refreshProductSelection();
            });
        }
    });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            productChecks.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
            refreshProductSelection();
        });
    }
    document.getElementById('spSelectVisible')?.addEventListener('click', function () {
        productChecks.forEach(function (checkbox) { checkbox.checked = true; });
        refreshProductSelection();
    });
    document.getElementById('spClearSelection')?.addEventListener('click', function () {
        productChecks.forEach(function (checkbox) { checkbox.checked = false; });
        refreshProductSelection();
    });
    refreshProductSelection();

    var pdfButton = document.getElementById('btnExportPdf');
    if (pdfButton) {
        pdfButton.addEventListener('click', async function () {
            var exportNode = document.getElementById('pdfExportContent');
            if (!exportNode || typeof window.html2canvas !== 'function' || !window.jspdf?.jsPDF) {
                window.alert('Không thể khởi tạo trình xuất PDF. Vui lòng tải lại trang và thử lại.');
                return;
            }

            var selectedIds = productChecks
                .filter(function (checkbox) { return checkbox.checked; })
                .map(function (checkbox) { return checkbox.value; });
            if (!selectedIds.length) {
                window.alert('Vui lòng chọn ít nhất một sản phẩm để xuất báo giá.');
                return;
            }

            var exportClone = exportNode.cloneNode(true);
            exportClone.querySelectorAll('.sp-product-row').forEach(function (row) {
                if (!selectedIds.includes(row.dataset.productId)) row.remove();
            });
            exportClone.querySelectorAll('.sp-variant-row').forEach(function (row) {
                if (!selectedIds.includes(row.dataset.parentProductId)) row.remove();
            });
            exportClone.querySelectorAll('.sp-selection-control, .card-footer, .sp-page-number').forEach(function (node) {
                node.remove();
            });
            exportClone.querySelectorAll('.sp-product-row').forEach(function (row, index) {
                if (row.cells[0]) row.cells[0].textContent = String(index + 1);
                row.classList.remove('is-selected');
            });

            var renderHost = document.createElement('div');
            renderHost.style.cssText = 'position:fixed;left:-12000px;top:0;width:1120px;background:#fff;padding:20px;z-index:-1';
            exportClone.style.cssText = 'width:1080px;background:#fff;box-shadow:none;border:0';
            exportClone.querySelectorAll('.table-responsive').forEach(function (node) { node.style.overflow = 'visible'; });
            exportClone.querySelectorAll('.sp-table').forEach(function (node) { node.style.minWidth = '0'; });
            renderHost.appendChild(exportClone);
            document.body.appendChild(renderHost);

            var originalHtml = pdfButton.innerHTML;
            pdfButton.disabled = true;
            pdfButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tạo PDF...';

            try {
                var canvas = await window.html2canvas(exportClone, {
                    scale: 1.5,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                });
                var pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4', compress: true });
                var pageWidth = pdf.internal.pageSize.getWidth();
                var pageHeight = pdf.internal.pageSize.getHeight();
                var margin = 8;
                var imageWidth = pageWidth - margin * 2;
                var imageHeight = canvas.height * imageWidth / canvas.width;
                var imageData = canvas.toDataURL('image/jpeg', 0.92);
                var printableHeight = pageHeight - margin * 2;
                var offset = 0;

                do {
                    if (offset > 0) pdf.addPage('a4', 'landscape');
                    pdf.addImage(imageData, 'JPEG', margin, margin - offset, imageWidth, imageHeight, undefined, 'FAST');
                    offset += printableHeight;
                } while (offset < imageHeight);

                pdf.save('bao-gia-{{ $asOfDate->format('Y-m-d') }}.pdf');
            } catch (error) {
                console.error(error);
                window.alert('Xuất PDF không thành công. Vui lòng thử lại.');
            } finally {
                renderHost.remove();
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalHtml;
            }
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
