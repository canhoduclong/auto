@php($purchaseFormOpen = isset($errors) && $errors->any() && old('paste_data') === null)
@php($selectedPurchaseFarm = $purchaseFarms->firstWhere('id', (int) old('duck_farm_id')))
<div class="card border-0 shadow-sm mb-4 {{ $purchaseFormOpen ? '' : 'd-none' }}" id="purchaseFormCard">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div><strong>Form thu mua</strong><span id="selectedFarmLabel" class="badge bg-success ms-2 d-none"></span></div>
        <button type="button" class="btn-close" data-purchase-form-close aria-label="Đóng"></button>
    </div>
    <div class="card-body p-3 p-lg-4">
        <form method="POST" action="{{ route('procurement.purchases.store') }}" id="purchaseForm">
            @csrf
            <div class="purchase-section mb-3">
                <div class="purchase-section-title"><i class="bi bi-info-circle me-1"></i>Thông tin chuyến thu mua</div>
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Ngày giờ thu mua <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="purchased_at" value="{{ old('purchased_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Hình thức mua <span class="text-danger">*</span></label>
                    <select class="form-select" name="purchase_type" id="purchaseType" required>
                        <option value="live_duck" @selected(old('purchase_type', 'live_duck') === 'live_duck')>Mua vịt lông tại trại</option>
                        <option value="processed_duck" @selected(old('purchase_type') === 'processed_duck')>Mua vịt thịt đã sơ chế</option>
                        <option value="product_purchase" @selected(old('purchase_type') === 'product_purchase')>Mua theo sản phẩm bán (đùi, lòng, huyết...)</option>
                    </select>
                    </div>
                    <div class="col-xl-6 live-field">
                        <label class="form-label">Trang trại <span class="text-danger">*</span></label>
                        <input type="hidden" name="duck_farm_id" id="farmSelect" value="{{ old('duck_farm_id') }}">
                        <button type="button" class="form-control farm-picker-button d-flex justify-content-between align-items-center text-start {{ $selectedPurchaseFarm ? 'has-value' : '' }}" id="farmPickerButton" data-farm-picker-open>
                            <span><i class="bi bi-houses me-2"></i><span id="farmPickerLabel">{{ $selectedPurchaseFarm?->name ?? 'Chọn trang trại' }}</span></span><i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="col-xl-6 processed-field d-none">
                        <label class="form-label">Nhà cung cấp vịt thịt <span class="text-danger">*</span></label>
                        <select class="form-select" name="supplier_id" id="supplierSelect"><option value="">Chọn nhà cung cấp</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6 duck-meta-field">
                        <label class="form-label">Loại vịt <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2"><select class="form-select js-other-select" name="duck_type" data-other-target="duckTypeOther" required><option value="">Chọn loại vịt</option><option value="Chery" @selected(old('duck_type') === 'Chery')>Chery</option><option value="Grimoud" @selected(old('duck_type') === 'Grimoud')>Grimoud</option><option value="other" @selected(old('duck_type') === 'other')>Khác</option></select><input class="form-control d-none" id="duckTypeOther" name="duck_type_other" value="{{ old('duck_type_other') }}" placeholder="Loại vịt khác"></div>
                    </div>
                    <div class="col-md-6 duck-meta-field">
                        <label class="form-label">Loại trại <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2"><select class="form-select js-other-select" name="farm_type" data-other-target="farmTypeOther" required><option value="">Chọn loại trại</option><option value="Hở" @selected(old('farm_type') === 'Hở')>Hở</option><option value="Lạnh" @selected(old('farm_type') === 'Lạnh')>Lạnh</option><option value="other" @selected(old('farm_type') === 'other')>Khác</option></select><input class="form-control d-none" id="farmTypeOther" name="farm_type_other" value="{{ old('farm_type_other') }}" placeholder="Loại trại khác"></div>
                    </div>
                </div>
            </div>

            <div class="purchase-section mb-3 duck-batch-field">
                <div class="purchase-section-title"><i class="bi bi-calculator me-1"></i>Số lượng và giá mua</div>
                <div class="row g-2">
                    <div class="col-6 col-xl"><label class="form-label">Số lượng (con)</label><input type="number" min="1" class="form-control calc" name="quantity" id="quantity" value="{{ old('quantity') }}" required></div>
                    <div class="col-6 col-xl"><label class="form-label">Size trung bình</label><input type="number" min="0.1" max="10" step=".001" class="form-control" name="live_size" id="averageSize" value="{{ old('live_size') }}" required></div>
                    <div class="col-6 col-xl"><label class="form-label">Khối lượng (kg)</label><input type="number" min="0.001" step=".001" class="form-control calc" name="total_weight" id="weight" value="{{ old('total_weight') }}" required></div>
                    <div class="col-6 col-xl"><label class="form-label">Giá mua/kg</label><input type="number" min="0" step=".01" class="form-control calc" name="unit_price" id="price" value="{{ old('unit_price') }}" required></div>
                    <div class="col-12 col-xl"><label class="form-label">Tiền hàng</label><input class="form-control fw-bold text-danger bg-white" id="subtotalPreview" value="0đ" readonly></div>
                </div>
            </div>

            <div class="purchase-section mb-3 product-purchase-field d-none">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2"><div class="purchase-section-title mb-0"><i class="bi bi-basket me-1"></i>Sản phẩm thu mua</div><button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectPurchaseProduct" data-bs-toggle="modal" data-bs-target="#productSelectionModal"><i class="bi bi-search me-1"></i>Chọn sản phẩm</button></div>
                <div class="card border-primary-subtle bg-primary-subtle mb-3"><div class="card-body py-2"><div class="d-flex justify-content-between gap-2 flex-wrap"><div><strong>Mẫu thu mua</strong><div class="small text-muted">Lưu và áp dụng nhanh nhà cung cấp cùng danh sách sản phẩm.</div></div><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#savePurchaseTemplateModal"><i class="bi bi-bookmark-plus me-1"></i>Lưu thành mẫu</button></div><div class="row g-2 mt-1"><div class="col-md-8"><select class="form-select form-select-sm" id="purchaseTemplateSelect"><option value="">-- Chọn mẫu thu mua --</option></select></div><div class="col-md-4 d-flex gap-2"><button type="button" class="btn btn-sm btn-success flex-grow-1" id="btnApplyPurchaseTemplate">Áp dụng mẫu</button><button type="button" class="btn btn-sm btn-outline-danger" id="btnDeletePurchaseTemplate"><i class="bi bi-trash"></i></button></div></div><div class="small text-muted mt-1" id="purchaseTemplateSummary"></div></div></div>
                <div class="alert alert-warning py-2 small" id="purchaseSupplierNotice">Vui lòng chọn nhà cung cấp trước khi chọn sản phẩm.</div>
                <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0" style="min-width:1050px"><thead class="table-light"><tr><th style="width:42px">#</th><th>Sản phẩm / Biến thể</th><th style="width:110px">Số lượng</th><th style="width:85px">ĐVT</th><th style="width:125px">Khối lượng kg</th><th style="width:145px">Đơn giá mua</th><th style="width:140px">Thành tiền</th><th>Ghi chú</th><th style="width:42px"></th></tr></thead><tbody id="purchaseProductRows"><tr data-empty-products><td colspan="9" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr></tbody></table></div>
            </div>

            <div class="purchase-section mb-3">
                <div class="purchase-section-title"><i class="bi bi-receipt me-1"></i>Chi phí chuyến</div>
                <div class="row g-2">
                    @foreach([['Phí cò', 'broker_fee', 'brokerFee'], ['Phí sơ chế', 'processing_fee', 'processingFee'], ['Chi phí thu mua', 'procurement_fee', 'procurementFee'], ['Phí vận chuyển', 'transportation_fee', 'transportationFee'], ['Chi phí khác', 'other_fee', 'otherFee']] as [$label, $name, $id])
                        <div class="col-6 col-lg"><label class="form-label">{{ $label }}</label><div class="input-group"><input type="number" min="0" step=".01" class="form-control calc" name="{{ $name }}" id="{{ $id }}" value="{{ old($name, 0) }}"><span class="input-group-text">đ</span></div></div>
                    @endforeach
                </div>
            </div>

            <div class="purchase-section mb-3">
                <div class="purchase-section-title"><i class="bi bi-wallet2 me-1"></i>Thanh toán và ghi chú</div>
                <div class="row g-2">
                    <div class="col-md-2"><label class="form-label">Đã thanh toán</label><div class="input-group"><input type="number" min="0" step=".01" class="form-control calc" name="paid_amount" id="paidAmount" value="{{ old('paid_amount', 0) }}"><span class="input-group-text">đ</span></div></div>
                    <div class="col-md-2"><label class="form-label">Còn phải trả</label><input class="form-control fw-semibold text-danger bg-white" id="remainingPreview" value="0đ" readonly></div>
                    <div class="col-md-2"><label class="form-label">Ngày phải trả</label><input type="date" class="form-control" name="payment_due_date" value="{{ old('payment_due_date') }}"></div>
                    <div class="col-md-6"><label class="form-label">Ghi chú</label><input class="form-control" name="notes" value="{{ old('notes') }}" placeholder="Tình trạng vịt, thỏa thuận hoặc lưu ý thêm..."></div>
                </div>
            </div>
            <div class="processed-field duck-batch-field d-none border rounded-3 p-3 mb-3 bg-light">
                <label class="fw-semibold mb-2">Phân loại size vịt thịt</label>
                <div class="row g-2">@foreach($processedSizes as $size)<div class="col-4 col-md-2"><label class="small">Size {{ number_format($size, 1) }}</label><input type="number" min="0" value="{{ old('sizes.' . $size, 0) }}" class="form-control form-control-sm" name="sizes[{{ $size }}]"></div>@endforeach</div>
            </div>
            <div class="purchase-total-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div><div class="small text-muted">Tổng giá trị chuyến thu mua</div><strong class="fs-4 text-danger" id="totalPreview">0đ</strong></div>
                <button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Ghi nhận thu mua</button>
            </div>
        </form>
    </div>
</div>

@include('warehouse.stock-in.product-selection-modal', ['availableVariants' => $purchaseProductVariants ?? [], 'productSelectionTitle' => 'Chọn sản phẩm thu mua'])
<div class="modal fade" id="savePurchaseTemplateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Lưu mẫu thu mua</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Tên mẫu</label><input id="purchaseTemplateName" class="form-control" maxlength="150" placeholder="Ví dụ: Thu mua phụ phẩm buổi sáng"><div id="purchaseTemplateError" class="alert alert-danger py-2 small mt-2 d-none"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="btnSavePurchaseTemplate">Lưu mẫu</button></div></div></div></div>

<div class="modal fade" id="farmPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title">Chọn trang trại thu mua</h5><div class="small text-muted">Tìm kiếm theo tên, điện thoại, địa chỉ, loại vịt và ghi chú.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <div class="farm-picker-toolbar p-3 border-bottom bg-light">
                    <div class="row g-2">
                        <div class="col-md-8"><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input type="search" class="form-control" id="farmPickerSearch" placeholder="Tìm tên trại, SĐT, địa chỉ, loại vịt, ghi chú..."></div></div>
                        <div class="col-md-4"><select class="form-select" id="farmPickerSort"><option value="name_asc">Tên trại A → Z</option><option value="name_desc">Tên trại Z → A</option><option value="scale_desc">Quy mô lớn nhất</option><option value="rating_desc">Đánh giá cao nhất</option><option value="recent_desc">Thu mua gần nhất</option></select></div>
                    </div>
                    <div class="small text-muted mt-2"><strong id="farmPickerCount">{{ $purchaseFarms->count() }}</strong> trang trại phù hợp</div>
                </div>
                <div class="table-responsive farm-picker-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top"><tr><th>Trang trại</th><th>Liên hệ</th><th>Quy mô / Loại vịt</th><th>Đánh giá</th><th>Ghi chú</th><th></th></tr></thead>
                        <tbody id="farmPickerRows">
                        @forelse($purchaseFarms as $farm)
                            <tr class="farm-picker-row" data-id="{{ $farm->id }}" data-name="{{ $farm->name }}" data-scale="{{ (int) ($farm->scale ?? 0) }}" data-rating="{{ (float) $farm->rating }}" data-recent="{{ $farm->last_purchase_at?->timestamp ?? 0 }}" data-search="{{ implode(' ', array_filter([$farm->name, $farm->phone, $farm->address, $farm->duck_breed, $farm->notes])) }}">
                                <td><div class="fw-semibold">{{ $farm->name }}</div><div class="small text-muted">{{ $farm->address ?: 'Chưa có địa chỉ' }}</div></td>
                                <td>{{ $farm->phone ?: '—' }}<div class="small text-muted">{{ ['individual'=>'Cá nhân','household'=>'Hộ kinh doanh','company'=>'Công ty','cooperative'=>'Hợp tác xã'][$farm->business_type] ?? $farm->business_type }}</div></td>
                                <td>{{ number_format($farm->scale ?? 0) }} con<div class="small text-muted">{{ $farm->duck_breed ?: 'Chưa rõ loại vịt' }}</div></td>
                                <td><span class="text-warning">★</span> {{ number_format((float) $farm->rating, 1) }}<div class="small text-muted">Lần bắt: {{ $farm->last_purchase_at?->format('d/m/Y') ?? '—' }}</div></td>
                                <td><div class="small farm-note" title="{{ $farm->notes }}">{{ $farm->notes ?: '—' }}</div></td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-success text-nowrap" data-select-farm>Chọn trại</button></td>
                            </tr>
                        @empty
                            <tr data-empty-farms><td colspan="6" class="text-center text-muted py-5">Chưa có trang trại đang hoạt động.</td></tr>
                        @endforelse
                        <tr class="d-none" id="farmPickerEmpty"><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-search d-block fs-3 mb-2"></i>Không tìm thấy trang trại phù hợp.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap p-3 border-top bg-white">
                    <div class="small text-muted" id="farmPickerRange">Chưa có dữ liệu</div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted text-nowrap" for="farmPickerPerPage">Hiển thị</label>
                        <select class="form-select form-select-sm" id="farmPickerPerPage" style="width:105px"><option value="10">10 / trang</option><option value="20">20 / trang</option><option value="50">50 / trang</option></select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="farmPickerPrevious" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></button>
                        <span class="small fw-semibold text-nowrap" id="farmPickerPage">Trang 1 / 1</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="farmPickerNext" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .purchase-section{border:1px solid #f0dfc5;border-radius:10px;padding:1rem;background:#fffdf9}.purchase-section-title{font-size:.82rem;font-weight:800;text-transform:uppercase;color:#92400e;margin-bottom:.65rem}.purchase-section .form-label{font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.3rem}.purchase-section .form-control,.purchase-section .form-select,.purchase-section .input-group-text{min-height:38px}.purchase-total-bar{background:#fff7e6;border:1px solid #f3d39f;border-radius:10px;padding:.8rem 1rem}.farm-picker-button{background:#fff;color:#64748b}.farm-picker-button:hover,.farm-picker-button:focus{border-color:#92400e;box-shadow:0 0 0 .2rem rgba(146,64,14,.12)}.farm-picker-button.has-value{color:#1f2937;font-weight:600}.farm-picker-table-wrap{max-height:58vh}.farm-picker-row.is-selected td{background:#f0fdf4}.farm-note{max-width:260px;white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.purchase-product-line-total{white-space:nowrap;font-weight:700;text-align:right}@media(max-width:576px){.purchase-section{padding:.75rem}.purchase-total-bar .btn{width:100%}}
        </style>
    @endpush
    @push('scripts')
        <script>
            (() => {
                const card = document.getElementById('purchaseFormCard');
                const type = document.getElementById('purchaseType');
                if (!card || !type) return;
                const farm = document.getElementById('farmSelect');
                const supplier = document.getElementById('supplierSelect');
                const farmPickerButton = document.getElementById('farmPickerButton');
                const farmPickerLabel = document.getElementById('farmPickerLabel');
                const farmPickerModalElement = document.getElementById('farmPickerModal');
                const farmPickerSearch = document.getElementById('farmPickerSearch');
                const farmPickerSort = document.getElementById('farmPickerSort');
                const farmPickerRowsBody = document.getElementById('farmPickerRows');
                const farmPickerEmpty = document.getElementById('farmPickerEmpty');
                const farmPickerPerPage = document.getElementById('farmPickerPerPage');
                const farmPickerPrevious = document.getElementById('farmPickerPrevious');
                const farmPickerNext = document.getElementById('farmPickerNext');
                const farmPickerPageLabel = document.getElementById('farmPickerPage');
                const farmPickerRange = document.getElementById('farmPickerRange');
                const productRows = document.getElementById('purchaseProductRows');
                let purchaseVariants = [];
                let purchaseTemplates = @json($purchaseTemplates ?? []);
                const oldProductItems = @json(old('product_items', []));
                let productRowIndex = 0;
                const farmRows = [...document.querySelectorAll('.farm-picker-row')];
                const normalizeText = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/gi, 'd').toLowerCase().replace(/\s+/g, ' ').trim();
                const collator = new Intl.Collator('vi', {sensitivity: 'base', numeric: true});
                let farmPickerCurrentPage = 1;
                const showForm = () => { card.classList.remove('d-none'); setTimeout(() => card.scrollIntoView({behavior:'smooth', block:'start'}), 30); };
                document.querySelectorAll('[data-purchase-form-toggle]').forEach(button => button.addEventListener('click', showForm));
                document.querySelector('[data-purchase-form-close]')?.addEventListener('click', () => card.classList.add('d-none'));
                const syncType = () => {
                    const live = type.value === 'live_duck';
                    const products = type.value === 'product_purchase';
                    document.querySelectorAll('.live-field').forEach(element => element.classList.toggle('d-none', !live));
                    document.querySelectorAll('.processed-field').forEach(element => element.classList.toggle('d-none', live));
                    document.querySelectorAll('.duck-batch-field').forEach(element => element.classList.toggle('d-none', products));
                    document.querySelectorAll('.duck-meta-field').forEach(element => element.classList.toggle('d-none', products));
                    document.querySelectorAll('.product-purchase-field').forEach(element => element.classList.toggle('d-none', !products));
                    if (farm) farm.required = live;
                    if (supplier) supplier.required = !live;
                    ['quantity','averageSize','weight','price'].forEach(id => { const input=document.getElementById(id); if(input) input.required=!products; });
                    document.querySelectorAll('.duck-meta-field select').forEach(input => input.required=!products);
                    syncPurchaseSupplierState();
                    calculate();
                };
                type.addEventListener('change', syncType);
                const refreshFarmRows = () => {
                    if (!farmPickerRowsBody) return;
                    const keywords = normalizeText(farmPickerSearch?.value).split(' ').filter(Boolean);
                    const visibleRows = farmRows.filter(row => keywords.every(keyword => normalizeText(row.dataset.search).includes(keyword)));
                    const [sortKey, sortDirection] = (farmPickerSort?.value || 'name_asc').split('_');
                    visibleRows.sort((left, right) => {
                        let result = sortKey === 'name'
                            ? collator.compare(left.dataset.name, right.dataset.name)
                            : Number(left.dataset[sortKey] || 0) - Number(right.dataset[sortKey] || 0);
                        return sortDirection === 'desc' ? -result : result;
                    });
                    const perPage = Number(farmPickerPerPage?.value || 10);
                    const lastPage = Math.max(1, Math.ceil(visibleRows.length / perPage));
                    farmPickerCurrentPage = Math.min(Math.max(1, farmPickerCurrentPage), lastPage);
                    const firstIndex = (farmPickerCurrentPage - 1) * perPage;
                    const pageRows = visibleRows.slice(firstIndex, firstIndex + perPage);
                    farmRows.forEach(row => row.classList.add('d-none'));
                    pageRows.forEach(row => { row.classList.remove('d-none'); farmPickerRowsBody.insertBefore(row, farmPickerEmpty); });
                    if (farmPickerEmpty) farmPickerEmpty.classList.toggle('d-none', visibleRows.length > 0 || farmRows.length === 0);
                    const count = document.getElementById('farmPickerCount');
                    if (count) count.textContent = visibleRows.length.toLocaleString('vi-VN');
                    if (farmPickerRange) farmPickerRange.textContent = visibleRows.length ? `Hiển thị ${firstIndex + 1}–${firstIndex + pageRows.length} trong ${visibleRows.length} trang trại` : 'Không có trang trại phù hợp';
                    if (farmPickerPageLabel) farmPickerPageLabel.textContent = `Trang ${farmPickerCurrentPage} / ${lastPage}`;
                    if (farmPickerPrevious) farmPickerPrevious.disabled = farmPickerCurrentPage <= 1;
                    if (farmPickerNext) farmPickerNext.disabled = farmPickerCurrentPage >= lastPage;
                };
                document.querySelector('[data-farm-picker-open]')?.addEventListener('click', () => {
                    refreshFarmRows();
                    bootstrap.Modal.getOrCreateInstance(farmPickerModalElement).show();
                    farmPickerModalElement.addEventListener('shown.bs.modal', () => farmPickerSearch?.focus(), {once: true});
                });
                farmPickerSearch?.addEventListener('input', () => { farmPickerCurrentPage = 1; refreshFarmRows(); });
                farmPickerSort?.addEventListener('change', () => { farmPickerCurrentPage = 1; refreshFarmRows(); });
                farmPickerPerPage?.addEventListener('change', () => { farmPickerCurrentPage = 1; refreshFarmRows(); });
                farmPickerPrevious?.addEventListener('click', () => { farmPickerCurrentPage--; refreshFarmRows(); });
                farmPickerNext?.addEventListener('click', () => { farmPickerCurrentPage++; refreshFarmRows(); });
                farmRows.forEach(row => row.querySelector('[data-select-farm]')?.addEventListener('click', () => {
                    farm.value = row.dataset.id;
                    farmPickerLabel.textContent = row.dataset.name;
                    farmPickerButton.classList.add('has-value');
                    farmRows.forEach(item => item.classList.toggle('is-selected', item === row));
                    const selectedLabel = document.getElementById('selectedFarmLabel');
                    selectedLabel.textContent = 'Đã chọn: ' + row.dataset.name;
                    selectedLabel.classList.remove('d-none');
                    bootstrap.Modal.getInstance(farmPickerModalElement)?.hide();
                }));
                farmRows.forEach(row => row.classList.toggle('is-selected', String(row.dataset.id) === String(farm?.value || '')));
                document.querySelectorAll('.js-other-select').forEach(select => {
                    const syncOther = () => {
                        const input = document.getElementById(select.dataset.otherTarget);
                        const show = select.value === 'other';
                        input.classList.toggle('d-none', !show);
                        input.required = show;
                    };
                    select.addEventListener('change', syncOther);
                    syncOther();
                });
                const normalizeNumber = value => Number(String(value ?? '').replace(',', '.')) || 0;
                const money = value => Math.round(value).toLocaleString('vi-VN')+'đ';
                const variantById = id => purchaseVariants.find(item => String(item.id ?? item.variant_id) === String(id));
                const syncPurchaseSupplierState = () => {
                    const enabled = !!supplier?.value && type.value === 'product_purchase';
                    document.getElementById('btnSelectPurchaseProduct')?.toggleAttribute('disabled', !enabled);
                    document.getElementById('purchaseSupplierNotice')?.classList.toggle('d-none', enabled);
                };
                const refreshProductModal = () => {
                    const allowed = new Set(purchaseVariants.map(item => String(item.id ?? item.variant_id)));
                    document.querySelectorAll('.product-selection-row').forEach(row => {
                        const button=row.querySelector('.js-select-product'); const id=String(button?.dataset.id || ''); const meta=variantById(id); const show=allowed.has(id);
                        row.dataset.supplierAllowed=show?'1':'0'; row.classList.toggle('d-none', !show);
                        if(meta && button){button.dataset.latest_price=meta.latest_price ?? '';button.dataset.price_id=meta.price_id ?? '';button.dataset.weight_per_unit=meta.weight_per_unit ?? 1;button.dataset.unit_label=meta.unit_label ?? '';button.dataset.label=meta.label ?? '';}
                    });
                    window.syncProductSelectionGroups?.();
                };
                const loadPurchaseSupplierProducts = async supplierId => {
                    purchaseVariants=[]; refreshProductModal(); if(!supplierId)return;
                    const response=await fetch(`{{ url('/api/suppliers') }}/${supplierId}/products`,{headers:{Accept:'application/json'}}); const payload=await response.json();
                    purchaseVariants=(payload.data||[]).flatMap(product=>product.variants||[]).map(item=>({...item,id:item.id??item.variant_id})); refreshProductModal();
                };
                const productSubtotal = () => [...document.querySelectorAll('#purchaseProductRows [data-product-row]')].reduce((sum,row)=>sum+normalizeNumber(row.querySelector('[name*="[quantity]"]')?.value)*normalizeNumber(row.querySelector('[name*="[unit_cost]"]')?.value),0);
                const updateProductRow = row => { const qty=normalizeNumber(row.querySelector('[name*="[quantity]"]')?.value); const cost=normalizeNumber(row.querySelector('[name*="[unit_cost]"]')?.value); row.querySelector('.purchase-product-line-total').textContent=money(qty*cost); calculate(); };
                window.addOrIncreaseVariantRow = (data, quantityToAdd=1) => {
                    const id=String(data.product_variant_id ?? data.id ?? ''); if(!id)return {status:'invalid'};
                    const existing=[...document.querySelectorAll('#purchaseProductRows [data-product-row]')].find(row=>String(row.dataset.variantId)===id);
                    if(existing){const input=existing.querySelector('[name*="[quantity]"]');input.value=normalizeNumber(input.value)+normalizeNumber(quantityToAdd);updateProductRow(existing);return {status:'incremented'};}
                    productRows.querySelector('[data-empty-products]')?.remove(); const meta=variantById(id)||data; const idx=productRowIndex++; const qty=normalizeNumber(data.quantity||quantityToAdd)||1; const weight=normalizeNumber(data.weight)||(qty*normalizeNumber(meta.weight_per_unit||1)); const cost=normalizeNumber(data.unit_cost ?? meta.latest_price);
                    productRows.insertAdjacentHTML('beforeend',`<tr data-product-row data-variant-id="${id}"><td class="text-center">${idx+1}</td><td><strong>${meta.label||'Sản phẩm'}</strong><input type="hidden" name="product_items[${idx}][product_variant_id]" value="${id}"><input type="hidden" name="product_items[${idx}][source_price_id]" value="${meta.price_id||data.source_price_id||''}"></td><td><input type="number" min="0.001" step=".001" class="form-control form-control-sm" name="product_items[${idx}][quantity]" value="${qty}" required></td><td class="text-center">${meta.unit_label||''}</td><td><input type="number" min="0" step=".001" class="form-control form-control-sm" name="product_items[${idx}][weight]" value="${weight}"></td><td><input type="number" min="0" step=".01" class="form-control form-control-sm" name="product_items[${idx}][unit_cost]" value="${cost}" required></td><td class="purchase-product-line-total">${money(qty*cost)}</td><td><input class="form-control form-control-sm" name="product_items[${idx}][note]"></td><td><button type="button" class="btn btn-sm text-danger" data-remove-product><i class="bi bi-x-circle"></i></button></td></tr>`); calculate(); return {status:'added'};
                };
                productRows?.addEventListener('input',event=>{const row=event.target.closest('[data-product-row]');if(row)updateProductRow(row);});
                productRows?.addEventListener('click',event=>{if(event.target.closest('[data-remove-product]')){event.target.closest('[data-product-row]').remove();if(!productRows.querySelector('[data-product-row]'))productRows.innerHTML='<tr data-empty-products><td colspan="9" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr>';calculate();}});
                supplier?.addEventListener('change',()=>{ if(type.value==='product_purchase'){productRows.innerHTML='<tr data-empty-products><td colspan="9" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr>';loadPurchaseSupplierProducts(supplier.value);} syncPurchaseSupplierState(); });
                let averageWasEdited = document.getElementById('averageSize').value !== '';
                document.getElementById('averageSize').addEventListener('input', () => averageWasEdited = true);
                const calculate = () => {
                    const quantity = +document.getElementById('quantity').value || 0;
                    const weight = +document.getElementById('weight').value || 0;
                    const price = +document.getElementById('price').value || 0;
                    const broker = +document.getElementById('brokerFee').value || 0;
                    const processing = +document.getElementById('processingFee').value || 0;
                    const procurement = +document.getElementById('procurementFee').value || 0;
                    const transportation = +document.getElementById('transportationFee').value || 0;
                    const other = +document.getElementById('otherFee').value || 0;
                    const paid = +document.getElementById('paidAmount').value || 0;
                    const subtotal = type.value === 'product_purchase' ? productSubtotal() : weight * price;
                    const total = subtotal + broker + processing + procurement + transportation + other;
                    if (!averageWasEdited && quantity && weight) document.getElementById('averageSize').value = (weight / quantity).toFixed(3);
                    document.getElementById('subtotalPreview').value = subtotal.toLocaleString('vi-VN') + 'đ';
                    document.getElementById('totalPreview').textContent = total.toLocaleString('vi-VN') + 'đ';
                    document.getElementById('remainingPreview').value = Math.max(0, total - paid).toLocaleString('vi-VN') + 'đ';
                };
                document.querySelectorAll('.calc').forEach(input => input.addEventListener('input', calculate));
                const selectedTemplate=()=>purchaseTemplates.find(item=>String(item.id)===String(document.getElementById('purchaseTemplateSelect')?.value));
                const renderTemplates=(selected='')=>{const select=document.getElementById('purchaseTemplateSelect');if(!select)return;select.innerHTML='<option value="">-- Chọn mẫu thu mua --</option>';purchaseTemplates.forEach(template=>{const option=document.createElement('option');option.value=template.id;option.textContent=`${template.name} - ${template.supplier_name} (${template.items.length} sản phẩm)`;option.selected=String(template.id)===String(selected);select.appendChild(option);});const template=selectedTemplate();document.getElementById('purchaseTemplateSummary').textContent=template?template.items.map(item=>`${item.label}: ${item.quantity}`).join('; '):(purchaseTemplates.length?`Hiện có ${purchaseTemplates.length} mẫu.`:'Chưa có mẫu thu mua.');};
                document.getElementById('purchaseTemplateSelect')?.addEventListener('change',()=>renderTemplates(document.getElementById('purchaseTemplateSelect').value));
                document.getElementById('btnApplyPurchaseTemplate')?.addEventListener('click',async()=>{const template=selectedTemplate();if(!template)return alert('Vui lòng chọn mẫu.');type.value='product_purchase';supplier.value=template.supplier_id;productRows.innerHTML='<tr data-empty-products><td colspan="9" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr>';syncType();await loadPurchaseSupplierProducts(template.supplier_id);template.items.forEach(item=>{const meta=variantById(item.product_variant_id);if(meta)window.addOrIncreaseVariantRow({...item,...meta,product_variant_id:item.product_variant_id,unit_cost:meta.latest_price||0,source_price_id:meta.price_id||''},item.quantity);});});
                document.getElementById('btnSavePurchaseTemplate')?.addEventListener('click',async function(){const name=document.getElementById('purchaseTemplateName');const error=document.getElementById('purchaseTemplateError');const items=[...document.querySelectorAll('#purchaseProductRows [data-product-row]')].map(row=>({product_variant_id:Number(row.dataset.variantId),quantity:normalizeNumber(row.querySelector('[name*="[quantity]"]')?.value),weight:normalizeNumber(row.querySelector('[name*="[weight]"]')?.value)}));error.classList.add('d-none');if(!name.value.trim()||!supplier.value||!items.length){error.textContent='Cần nhập tên mẫu, chọn nhà cung cấp và ít nhất một sản phẩm.';error.classList.remove('d-none');return;}const response=await fetch(@json(route('procurement.purchase-templates.store')),{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({name:name.value.trim(),supplier_id:Number(supplier.value),items})});const payload=await response.json();if(!response.ok){error.textContent=payload.message||'Không thể lưu mẫu.';error.classList.remove('d-none');return;}purchaseTemplates.push(payload.template);renderTemplates(payload.template.id);name.value='';bootstrap.Modal.getInstance(document.getElementById('savePurchaseTemplateModal'))?.hide();});
                document.getElementById('btnDeletePurchaseTemplate')?.addEventListener('click',async()=>{const template=selectedTemplate();if(!template||!confirm(`Xóa mẫu "${template.name}"?`))return;const url=@json(route('procurement.purchase-templates.destroy',['template'=>'__ID__'])).replace('__ID__',template.id);const response=await fetch(url,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':@json(csrf_token())}});if(response.ok){purchaseTemplates=purchaseTemplates.filter(item=>item.id!==template.id);renderTemplates();}});
                document.getElementById('purchaseForm')?.addEventListener('submit',event=>{if(type.value==='product_purchase'&&!productRows.querySelector('[data-product-row]')){event.preventDefault();alert('Vui lòng chọn ít nhất một sản phẩm thu mua.');}});
                renderTemplates();
                syncType();
                if(type.value==='product_purchase'&&supplier?.value)loadPurchaseSupplierProducts(supplier.value).then(()=>oldProductItems.forEach(item=>{const meta=variantById(item.product_variant_id);if(meta)window.addOrIncreaseVariantRow({...item,...meta,product_variant_id:item.product_variant_id,unit_cost:item.unit_cost,source_price_id:item.source_price_id},item.quantity);}));
                calculate();
                document.querySelectorAll('.farm-card').forEach(farmCard => farmCard.addEventListener('click', () => {
                    document.querySelectorAll('.farm-card').forEach(item => item.classList.remove('selected'));
                    farmCard.classList.add('selected');
                    farm.value = farmCard.dataset.farmId;
                    if (farmPickerLabel) farmPickerLabel.textContent = farmCard.dataset.farmName;
                    farmPickerButton?.classList.add('has-value');
                    type.value = 'live_duck';
                    syncType();
                    const label = document.getElementById('selectedFarmLabel');
                    label.textContent = 'Đã chọn: ' + farmCard.dataset.farmName;
                    label.classList.remove('d-none');
                    showForm();
                }));
            })();
        </script>
    @endpush
@endonce
