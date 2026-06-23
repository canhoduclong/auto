@php($purchaseFormOpen = isset($errors) && $errors->any())
<div class="card border-0 shadow-sm mb-4 {{ $purchaseFormOpen ? '' : 'd-none' }}" id="purchaseFormCard">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div><strong>Form thu mua</strong><span id="selectedFarmLabel" class="badge bg-success ms-2 d-none"></span></div>
        <button type="button" class="btn-close" data-purchase-form-close aria-label="Đóng"></button>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('procurement.purchases.store') }}" id="purchaseForm">
            @csrf
            <div class="purchase-form-line">
                <div class="purchase-form-number"><i class="bi bi-calendar-event"></i></div>
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold">Ngày giờ thu mua</label>
                    <input type="datetime-local" class="form-control" name="purchased_at" value="{{ old('purchased_at', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
            </div>

            <div class="purchase-form-line">
                <div class="purchase-form-number">1</div>
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold">Hình thức mua</label>
                    <select class="form-select" name="purchase_type" id="purchaseType" required>
                        <option value="live_duck" @selected(old('purchase_type', 'live_duck') === 'live_duck')>Mua vịt lông tại trại</option>
                        <option value="processed_duck" @selected(old('purchase_type') === 'processed_duck')>Mua vịt thịt đã sơ chế</option>
                    </select>
                </div>
            </div>

            <div class="purchase-form-line">
                <div class="purchase-form-number">2</div>
                <div class="flex-grow-1 live-field">
                    <label class="form-label fw-semibold">Trang trại</label>
                    <select class="form-select" name="duck_farm_id" id="farmSelect">
                        <option value="">Chọn trang trại</option>
                        @foreach($purchaseFarms as $farm)
                            <option value="{{ $farm->id }}" @selected((string) old('duck_farm_id') === (string) $farm->id)>{{ $farm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-grow-1 processed-field d-none">
                    <label class="form-label fw-semibold">Nhà cung cấp vịt thịt</label>
                    <select class="form-select" name="supplier_id" id="supplierSelect">
                        <option value="">Chọn nhà cung cấp</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="purchase-form-line">
                <div class="purchase-form-number">3</div>
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold">Loại vịt</label>
                    <div class="row g-2">
                        <div class="col-md-5"><select class="form-select js-other-select" name="duck_type" data-other-target="duckTypeOther" required><option value="">Chọn loại vịt</option><option value="Chery" @selected(old('duck_type') === 'Chery')>Chery</option><option value="Grimoud" @selected(old('duck_type') === 'Grimoud')>Grimoud</option><option value="other" @selected(old('duck_type') === 'other')>Khác</option></select></div>
                        <div class="col-md-7"><input class="form-control d-none" id="duckTypeOther" name="duck_type_other" value="{{ old('duck_type_other') }}" placeholder="Nhập loại vịt khác"></div>
                    </div>
                </div>
            </div>

            <div class="purchase-form-line">
                <div class="purchase-form-number">4</div>
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold">Loại trại</label>
                    <div class="row g-2">
                        <div class="col-md-5"><select class="form-select js-other-select" name="farm_type" data-other-target="farmTypeOther" required><option value="">Chọn loại trại</option><option value="Hở" @selected(old('farm_type') === 'Hở')>Hở</option><option value="Lạnh" @selected(old('farm_type') === 'Lạnh')>Lạnh</option><option value="other" @selected(old('farm_type') === 'other')>Khác</option></select></div>
                        <div class="col-md-7"><input class="form-control d-none" id="farmTypeOther" name="farm_type_other" value="{{ old('farm_type_other') }}" placeholder="Nhập loại trại khác"></div>
                    </div>
                </div>
            </div>

            <div class="purchase-form-line">
                <div class="purchase-form-number">5</div>
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold">Số lượng và giá mua</label>
                    <div class="row g-2">
                        <div class="col-6 col-lg"><label class="small text-muted">Số lượng</label><input type="number" min="1" class="form-control calc" name="quantity" id="quantity" value="{{ old('quantity') }}" required></div>
                        <div class="col-6 col-lg"><label class="small text-muted">TB Size</label><input type="number" min="0.1" max="10" step=".001" class="form-control" name="live_size" id="averageSize" value="{{ old('live_size') }}" required></div>
                        <div class="col-6 col-lg"><label class="small text-muted">Số KG</label><input type="number" min="0.001" step=".001" class="form-control calc" name="total_weight" id="weight" value="{{ old('total_weight') }}" required></div>
                        <div class="col-6 col-lg"><label class="small text-muted">Giá mua/kg</label><input type="number" min="0" step=".01" class="form-control calc" name="unit_price" id="price" value="{{ old('unit_price') }}" required></div>
                        <div class="col-12 col-lg"><label class="small text-muted">Thành tiền</label><input class="form-control fw-bold text-danger" id="subtotalPreview" value="0đ" readonly></div>
                    </div>
                </div>
            </div>

            @foreach([6 => ['Phí cò', 'broker_fee', 'brokerFee'], 7 => ['Phí sơ chế (gia công)', 'processing_fee', 'processingFee'], 8 => ['Phí khác', 'other_fee', 'otherFee']] as $number => [$label, $name, $id])
                <div class="purchase-form-line">
                    <div class="purchase-form-number">{{ $number }}</div>
                    <div class="flex-grow-1"><label class="form-label fw-semibold">{{ $label }}</label><input type="number" min="0" step=".01" class="form-control calc" name="{{ $name }}" id="{{ $id }}" value="{{ old($name, 0) }}"></div>
                </div>
            @endforeach

            <div class="purchase-form-line">
                <div class="purchase-form-number">9</div>
                <div class="flex-grow-1"><label class="form-label fw-semibold">Ghi chú</label><textarea class="form-control" name="notes" rows="3" placeholder="Ghi nhận thêm tình trạng vịt, thỏa thuận hoặc chi phí...">{{ old('notes') }}</textarea></div>
            </div>

            <div class="rounded-3 border p-3 my-3">
                <div class="fw-semibold mb-2"><i class="bi bi-wallet2 me-1"></i>Theo dõi thanh toán</div>
                <div class="row g-2">
                    <div class="col-md-4"><label class="small text-muted">Đã thanh toán</label><input type="number" min="0" step=".01" class="form-control calc" name="paid_amount" id="paidAmount" value="{{ old('paid_amount', 0) }}"></div>
                    <div class="col-md-4"><label class="small text-muted">Còn lại</label><input class="form-control fw-semibold text-danger" id="remainingPreview" value="0đ" readonly></div>
                    <div class="col-md-4"><label class="small text-muted">Ngày phải trả</label><input type="date" class="form-control" name="payment_due_date" value="{{ old('payment_due_date') }}"></div>
                </div>
            </div>
            <div class="processed-field d-none border rounded-3 p-3 mb-3 bg-light">
                <label class="fw-semibold mb-2">Phân loại size vịt thịt</label>
                <div class="row g-2">@foreach($processedSizes as $size)<div class="col-4 col-md-2"><label class="small">Size {{ number_format($size, 1) }}</label><input type="number" min="0" value="{{ old('sizes.' . $size, 0) }}" class="form-control form-control-sm" name="sizes[{{ $size }}]"></div>@endforeach</div>
            </div>
            <div class="alert alert-warning-subtle border d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Tổng chi phí thu mua</span><strong class="fs-5 text-danger" id="totalPreview">0đ</strong>
            </div>
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Ghi nhận thu mua</button>
        </form>
    </div>
</div>

@once
    @push('styles')
        <style>
            .purchase-form-line{display:flex;gap:1rem;padding:1rem 0;border-bottom:1px solid #f1e4cf}.purchase-form-line:last-of-type{border-bottom:0}.purchase-form-number{width:34px;height:34px;flex:0 0 34px;border-radius:50%;display:grid;place-items:center;background:#92400e;color:#fff;font-weight:700}@media(max-width:576px){.purchase-form-line{gap:.65rem}.purchase-form-number{width:29px;height:29px;flex-basis:29px}}
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
                const showForm = () => { card.classList.remove('d-none'); setTimeout(() => card.scrollIntoView({behavior:'smooth', block:'start'}), 30); };
                document.querySelectorAll('[data-purchase-form-toggle]').forEach(button => button.addEventListener('click', showForm));
                document.querySelector('[data-purchase-form-close]')?.addEventListener('click', () => card.classList.add('d-none'));
                const syncType = () => {
                    const live = type.value === 'live_duck';
                    document.querySelectorAll('.live-field').forEach(element => element.classList.toggle('d-none', !live));
                    document.querySelectorAll('.processed-field').forEach(element => element.classList.toggle('d-none', live));
                    if (farm) farm.required = live;
                    if (supplier) supplier.required = !live;
                };
                type.addEventListener('change', syncType);
                syncType();
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
                let averageWasEdited = document.getElementById('averageSize').value !== '';
                document.getElementById('averageSize').addEventListener('input', () => averageWasEdited = true);
                const calculate = () => {
                    const quantity = +document.getElementById('quantity').value || 0;
                    const weight = +document.getElementById('weight').value || 0;
                    const price = +document.getElementById('price').value || 0;
                    const broker = +document.getElementById('brokerFee').value || 0;
                    const processing = +document.getElementById('processingFee').value || 0;
                    const other = +document.getElementById('otherFee').value || 0;
                    const paid = +document.getElementById('paidAmount').value || 0;
                    const total = weight * price + broker + processing + other;
                    if (!averageWasEdited && quantity && weight) document.getElementById('averageSize').value = (weight / quantity).toFixed(3);
                    document.getElementById('subtotalPreview').value = (weight * price).toLocaleString('vi-VN') + 'đ';
                    document.getElementById('totalPreview').textContent = total.toLocaleString('vi-VN') + 'đ';
                    document.getElementById('remainingPreview').value = Math.max(0, total - paid).toLocaleString('vi-VN') + 'đ';
                };
                document.querySelectorAll('.calc').forEach(input => input.addEventListener('input', calculate));
                calculate();
                document.querySelectorAll('.farm-card').forEach(farmCard => farmCard.addEventListener('click', () => {
                    document.querySelectorAll('.farm-card').forEach(item => item.classList.remove('selected'));
                    farmCard.classList.add('selected');
                    farm.value = farmCard.dataset.farmId;
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
