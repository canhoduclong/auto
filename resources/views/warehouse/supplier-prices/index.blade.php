@extends('layouts.warehouse')

@section('title', 'Bảng giá thu mua')
@section('subtitle', 'Quản lý giá thu mua theo nhà cung cấp và sản phẩm')

@push('styles')
<style>
    .sp-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 14px rgba(15,23,42,.06); }
    .sp-table th { font-size:.74rem; text-transform:uppercase; color:#64748b; white-space:nowrap; }
    .sp-table td { vertical-align:middle; }
    .money-cell { font-variant-numeric: tabular-nums; text-align:right; white-space:nowrap; }
    .price-warning { display:none; }
    .price-warning.show { display:block; }
</style>
@endpush

@section('content')
<div class="sp-card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-cash-coin me-2"></i>Bảng giá thu mua hàng ngày</h5>
            <div class="text-muted small">Mỗi lần điều chỉnh giá sẽ tạo một bản ghi mới, không ghi đè lịch sử cũ.</div>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#attachProductModal">
            <i class="bi bi-link-45deg me-1"></i>Thêm sản phẩm NCC
        </button>
    </div>

    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Nhà cung cấp</label>
            <select name="supplier_id" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ (int) $supplierId === (int) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Sản phẩm</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ (int) $productId === (int) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Từ ngày</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Đến ngày</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Trạng thái</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Đang cung cấp</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Tạm tắt</option>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Tìm kiếm</button>
            <a href="{{ route('warehouse.supplier-prices.index') }}" class="btn btn-sm btn-outline-secondary">Đặt lại</a>
        </div>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="sp-card table-responsive">
    <table class="table table-hover sp-table mb-0">
        <thead class="table-light">
            <tr>
                <th>Nhà cung cấp</th>
                <th>Sản phẩm</th>
                <th>Kiểu tính giá</th>
                <th class="text-end">Giá min</th>
                <th class="text-end">Chênh lệch</th>
                <th class="text-end">Giá bán hôm nay</th>
                <th>Ngày cập nhật</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($supplierProducts as $row)
                @php
                    $latest = $latestPrices->get($row->supplier_id . ':' . $row->product_id);
                    $type = $latest?->price_calculation_type ?? $row->price_calculation_type ?? \App\Models\SupplierProduct::TYPE_COMPONENT_BASED;
                    $isSaleSynced = (bool) ($saleSyncStatus[$row->supplier_id . ':' . $row->product_id] ?? false);
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $row->supplier?->name ?? 'NCC' }}</td>
                    <td>{{ $row->product?->name ?? 'Sản phẩm' }}</td>
                    <td>
                        @if($type === \App\Models\SupplierProduct::TYPE_DIRECT_PURCHASE)
                            <span class="badge bg-info">Nhập trực tiếp</span>
                        @else
                            <span class="badge bg-primary">Theo thành phần</span>
                        @endif
                    </td>
                    <td class="money-cell">{{ $latest ? number_format((float) $latest->min_price, 0, ',', '.') : '—' }}</td>
                    <td class="money-cell">{{ $latest ? number_format((float) $latest->suggested_margin, 0, ',', '.') : '—' }}</td>
                    <td class="money-cell">{{ $latest ? number_format((float) $latest->today_sale_price, 0, ',', '.') : '—' }}</td>
                    <td>{{ $latest?->effective_date?->format('d/m/Y') ?? 'Chưa có giá' }}</td>
                    <td>
                        @if(!$row->active)
                            <span class="badge bg-secondary">Tạm tắt</span>
                        @elseif(!$latest)
                            <span class="badge bg-warning text-dark">Chưa có giá</span>
                        @else
                            <span class="badge bg-success">Đang cung cấp</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary js-adjust-price"
                                data-bs-toggle="modal"
                                data-bs-target="#priceModal"
                                data-supplier-id="{{ $row->supplier_id }}"
                                data-supplier-name="{{ $row->supplier?->name }}"
                                data-product-id="{{ $row->product_id }}"
                                data-product-name="{{ $row->product?->name }}"
                                data-price-calculation-type="{{ $type }}"
                                data-purchase-price="{{ $latest?->purchase_price ?? 0 }}"
                                data-material-price="{{ $latest?->material_price ?? 0 }}"
                                data-processing-cost="{{ $latest?->processing_cost ?? 0 }}"
                                data-other-cost="{{ $latest?->other_cost ?? 0 }}"
                                data-min-price="{{ $latest?->min_price ?? 0 }}"
                                data-suggested-margin="{{ $latest?->suggested_margin ?? 2000 }}"
                                data-today-sale-price="{{ $latest?->today_sale_price ?? 0 }}">
                            Điều chỉnh
                        </button>
                        @if($latest)
                            <form method="POST" action="{{ route('warehouse.supplier-prices.apply-today-sale-price', [$row->supplier_id, $row->product_id]) }}" class="d-inline">
                                @csrf
                                <button
                                    class="btn btn-sm {{ $isSaleSynced ? 'btn-outline-success' : 'btn-success' }}"
                                    title="{{ $isSaleSynced ? 'Đã áp dụng giá bán hôm nay cho toàn bộ biến thể.' : 'Áp dụng giá bán hôm nay của NCC cho toàn bộ biến thể.' }}"
                                    onclick="return confirm('Dùng Giá bán hôm nay này để cập nhật toàn bộ biến thể của sản phẩm?')"
                                >
                                    {{ $isSaleSynced ? 'Đã dùng giá này' : 'Dùng giá này' }}
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('warehouse.suppliers.products.detach', [$row->supplier_id, $row->product_id]) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-secondary">Tắt</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Chưa có sản phẩm nào được gán cho nhà cung cấp.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center mt-3">{{ $supplierProducts->links() }}</div>

<div class="modal fade" id="attachProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="attachProductForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Thêm sản phẩm cho nhà cung cấp</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Nhà cung cấp</label>
                    <select id="attachSupplier" class="form-select mb-3" required>
                        <option value="">Chọn nhà cung cấp</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <label class="form-label small fw-semibold">Sản phẩm</label>
                    <select name="product_id" class="form-select mb-3" required>
                        <option value="">Chọn sản phẩm</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <label class="form-label small fw-semibold">Kiểu tính giá</label>
                    <select name="price_calculation_type" class="form-select mb-3" required>
                        <option value="component_based">Tính từ thành phần</option>
                        <option value="direct_purchase">Nhập giá thu mua trực tiếp</option>
                    </select>
                    <label class="form-label small fw-semibold">Ghi chú</label>
                    <textarea name="note" rows="2" class="form-control"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary btn-sm">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('warehouse.supplier-prices.store') }}" id="priceForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Điều chỉnh giá thu mua & giá bán</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" id="priceSupplierId">
                    <input type="hidden" name="product_id" id="priceProductId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nhà cung cấp</label>
                            <input type="text" id="priceSupplierName" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sản phẩm</label>
                            <input type="text" id="priceProductName" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ngày áp dụng</label>
                            <input type="date" name="effective_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kiểu tính giá</label>
                            <select name="price_calculation_type" id="priceCalculationType" class="form-select" required>
                                <option value="component_based">Tính từ thành phần</option>
                                <option value="direct_purchase">Nhập giá thu mua trực tiếp</option>
                            </select>
                        </div>
                        <div class="col-md-4 direct-field">
                            <label class="form-label small fw-semibold">Giá thu mua</label>
                            <input type="number" name="purchase_price" class="form-control price-source" min="0" step="1000">
                        </div>
                        <div class="col-md-4 component-field">
                            <label class="form-label small fw-semibold">Giá nguyên liệu</label>
                            <input type="number" name="material_price" class="form-control price-source" min="0" step="1000">
                        </div>
                        <div class="col-md-4 component-field">
                            <label class="form-label small fw-semibold">Chi phí sơ chế</label>
                            <input type="number" name="processing_cost" class="form-control price-source" min="0" step="1000">
                        </div>
                        <div class="col-md-4 component-field">
                            <label class="form-label small fw-semibold">Phí khác</label>
                            <input type="number" name="other_cost" class="form-control price-source" min="0" step="1000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Giá min</label>
                            <input type="number" name="min_price" class="form-control" min="0" step="1000" readonly>
                            <div class="form-text" id="minPreview"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Chênh lệch đề xuất</label>
                            <input type="number" name="suggested_margin" class="form-control price-source" min="0" step="1000" value="2000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Giá bán hôm nay</label>
                            <input type="number" name="today_sale_price" id="todaySalePrice" class="form-control" min="0" step="1000">
                            <div class="form-text">Có thể chỉnh tay, nhưng phải lớn hơn hoặc bằng giá min và sẽ được áp dụng cho toàn bộ biến thể.</div>
                            <div class="price-warning text-danger small mt-1" id="saleWarning">Giá bán hôm nay phải lớn hơn hoặc bằng giá min.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold">Lịch sử điều chỉnh giá</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th class="text-end">Nguyên liệu</th>
                                    <th class="text-end">Sơ chế</th>
                                    <th class="text-end">Phí khác</th>
                                    <th class="text-end">Giá min</th>
                                    <th class="text-end">Chênh lệch</th>
                                    <th class="text-end">Giá bán hôm nay</th>
                                    <th>Người cập nhật</th>
                                </tr>
                            </thead>
                            <tbody id="priceHistoryBody">
                                <tr><td colspan="8" class="text-muted text-center">Chọn sản phẩm để xem lịch sử.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary btn-sm">Lưu giá mới</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const attachForm = document.getElementById('attachProductForm');
document.getElementById('attachSupplier')?.addEventListener('change', function () {
    attachForm.action = this.value ? `{{ url('/warehouse/suppliers') }}/${this.value}/products` : '';
});

function numberValue(input) {
    return parseFloat(input?.value || '0') || 0;
}

function formatMoney(value) {
    return Math.round(value).toLocaleString('vi-VN') + 'đ';
}

function updateSaleWarning() {
    const form = document.getElementById('priceForm');
    const minPrice = numberValue(form.min_price);
    const salePrice = numberValue(form.today_sale_price);

    document.getElementById('saleWarning').classList.toggle('show', salePrice < minPrice);
}

function recalcPriceForm() {
    const form = document.getElementById('priceForm');
    const type = form.price_calculation_type.value;
    const purchase = numberValue(form.purchase_price);
    const material = numberValue(form.material_price);
    const processing = numberValue(form.processing_cost);
    const other = numberValue(form.other_cost);
    const margin = numberValue(form.suggested_margin);
    const minPrice = type === 'direct_purchase' ? purchase : material + processing + other;
    const salePrice = minPrice + margin;

    form.min_price.value = Math.round(minPrice);
    form.today_sale_price.value = Math.round(salePrice);

    document.getElementById('minPreview').textContent = type === 'direct_purchase'
        ? `Giá min = giá thu mua: ${formatMoney(minPrice)}`
        : `Giá min = nguyên liệu + sơ chế + phí khác: ${formatMoney(minPrice)}`;
    updateSaleWarning();
}

function syncPriceTypeFields() {
    const form = document.getElementById('priceForm');
    const isDirect = form.price_calculation_type.value === 'direct_purchase';
    document.querySelectorAll('.component-field').forEach(el => el.classList.toggle('d-none', isDirect));
    document.querySelectorAll('.direct-field').forEach(el => el.classList.toggle('d-none', !isDirect));
    form.processing_cost.disabled = isDirect;
    form.other_cost.disabled = isDirect;
    recalcPriceForm();
}

document.querySelectorAll('.price-source, #priceCalculationType').forEach(input => {
    input.addEventListener('input', recalcPriceForm);
    input.addEventListener('change', syncPriceTypeFields);
});

document.getElementById('todaySalePrice')?.addEventListener('input', updateSaleWarning);

document.querySelectorAll('.js-adjust-price').forEach(button => {
    button.addEventListener('click', async function () {
        const form = document.getElementById('priceForm');
        form.reset();
        document.getElementById('priceSupplierId').value = this.dataset.supplierId;
        document.getElementById('priceProductId').value = this.dataset.productId;
        document.getElementById('priceSupplierName').value = this.dataset.supplierName || '';
        document.getElementById('priceProductName').value = this.dataset.productName || '';
        form.effective_date.value = '{{ now()->toDateString() }}';
        form.price_calculation_type.value = this.dataset.priceCalculationType || 'component_based';
        form.purchase_price.value = this.dataset.purchasePrice || 0;
        form.material_price.value = this.dataset.materialPrice || 0;
        form.processing_cost.value = this.dataset.processingCost || 0;
        form.other_cost.value = this.dataset.otherCost || 0;
        form.suggested_margin.value = this.dataset.suggestedMargin || 2000;
        form.today_sale_price.value = this.dataset.todaySalePrice || 0;
        syncPriceTypeFields();
        updateSaleWarning();

        const tbody = document.getElementById('priceHistoryBody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">Đang tải...</td></tr>';
        try {
            const response = await fetch(`{{ url('/api/suppliers') }}/${this.dataset.supplierId}/products/${this.dataset.productId}/price-history`, {
                headers: {'Accept': 'application/json'}
            });
            const payload = await response.json();
            const rows = payload.data || [];
            tbody.innerHTML = rows.length ? rows.map(row => `
                <tr>
                    <td>${row.effective_date || ''}</td>
                    <td class="text-end">${formatMoney(row.material_price || 0)}</td>
                    <td class="text-end">${formatMoney(row.processing_cost || 0)}</td>
                    <td class="text-end">${formatMoney(row.other_cost || 0)}</td>
                    <td class="text-end">${formatMoney(row.min_price || 0)}</td>
                    <td class="text-end">${formatMoney(row.suggested_margin || 0)}</td>
                    <td class="text-end">${formatMoney(row.today_sale_price || 0)}</td>
                    <td>${row.created_by_name || ''}</td>
                </tr>
            `).join('') : '<tr><td colspan="8" class="text-muted text-center">Chưa có lịch sử giá.</td></tr>';
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-danger text-center">Không tải được lịch sử giá.</td></tr>';
        }
    });
});
</script>
@endpush
