@extends('layouts.warehouse')

@section('title', $editingTransfer ? 'Sửa phiếu điều chuyển' : 'Điều chuyển kho')

@push('styles')
<style>
.transfer-hero {
    background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #67e8f9 100%);
    color: #fff;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
}
.transfer-card {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(15, 23, 42, .07);
}
.transfer-table th {
    font-size: .75rem;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: .03em;
}
.transfer-pill {
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    padding: .35rem .65rem;
}
</style>
@endpush

@section('content')
@include('warehouse.transfers._module_nav')
<div class="transfer-hero d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-arrow-left-right me-2"></i>{{ $editingTransfer ? 'Sửa phiếu điều chuyển '.($editingTransfer->transfer_code ?? '#'.$editingTransfer->id) : 'Tạo phiếu điều chuyển kho' }}</h4>
        <div class="small" style="opacity:.92;">Lấy hàng tồn tại kho đang quản lý để chuyển sang kho khác, kho nhận sẽ vào phần tiếp nhận để nhập kho.</div>
        <div class="small mt-2">Kho nguồn: <strong>{{ $sourceWarehouse?->name ?? '—' }}</strong></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('warehouse.transfers.index') }}" class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Lập phiếu xuất tổng</a>
        <a href="{{ route('warehouse.inventory-transfers.incoming') }}" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-box-arrow-in-down me-1"></i>Tiếp nhận điều chuyển ({{ number_format($incomingPendingCount) }})
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-1"></i>
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="card transfer-card mb-4">
    <div class="card-header bg-white">
        <strong><i class="bi bi-{{ $editingTransfer ? 'pencil-square' : 'file-earmark-plus' }} me-1"></i>{{ $editingTransfer ? 'Cập nhật phiếu điều chuyển' : 'Thông tin phiếu điều chuyển' }}</strong>
    </div>
    <div class="card-body">
        <form action="{{ $editingTransfer ? route('warehouse.inventory-transfers.update', $editingTransfer) : route('warehouse.inventory-transfers.store') }}" method="POST" id="inventoryTransferForm">
            @csrf
            @if($editingTransfer) @method('PUT') @endif
            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Kho nhận <span class="text-danger">*</span></label>
                    <select name="target_warehouse_id" class="form-select" required>
                        <option value="">-- Chọn kho nhận --</option>
                        @foreach($targetWarehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) old('target_warehouse_id', $editingTransfer?->target_warehouse_id) === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-semibold small">Ghi chú</label>
                    <input type="text" class="form-control" name="note" maxlength="1000" value="{{ old('note', $editingTransfer?->note) }}" placeholder="Ví dụ: điều chuyển bổ sung hàng bán nhanh cho kho trung tâm...">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Sản phẩm điều chuyển từ tồn kho hiện có</h6>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#productSelectionModal">
                    <i class="bi bi-search me-1"></i>Chọn sản phẩm
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle transfer-table" id="transferItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:320px;">Sản phẩm</th>
                            <th class="text-center" style="min-width:120px;">Tồn khả dụng</th>
                            <th class="text-center" style="min-width:120px;">Số lượng chuyển</th>
                            <th class="text-center" style="min-width:145px;">Khối lượng xuất (kg)</th>
                            <th class="text-center" style="min-width:120px;">Đơn giá</th>
                            <th class="text-center" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="transferItemsBody">
                        @php
                            $oldItems = old('items');
                            if ($oldItems === null && $editingTransfer) {
                                $oldItems = $editingTransfer->items->map(fn ($item) => [
                                    'product_variant_id' => $item->product_variant_id,
                                    'quantity' => $item->quantity,
                                    'weight_kg' => $item->weight_kg,
                                    'unit_cost' => $item->unit_cost,
                                ])->values()->all();
                            }
                            $oldItems = $oldItems ?? [];
                        @endphp
                        @foreach($oldItems as $index => $item)
                            @php
                                $vid = $item['product_variant_id'] ?? '';
                                $variant = collect($availableVariants)->firstWhere('variant_id', $vid);
                                if (!$variant) continue;
                            @endphp
                            <tr class="transfer-item-row" data-row-index="{{ $index }}" data-variant-id="{{ $vid }}">
                                <td>
                                    <div class="fw-semibold">{{ $variant['label'] }}</div>
                                    <input type="hidden" name="items[{{ $index }}][product_variant_id]" class="variant-input" value="{{ $vid }}">
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border available-badge" data-available="{{ $variant['available'] }}">{{ number_format($variant['available']) }}</span>
                                </td>
                                <td>
                                    <input type="number" min="1" max="{{ max(1, $variant['available']) }}" name="items[{{ $index }}][quantity]" class="form-control text-center qty-input" value="{{ $item['quantity'] ?? 1 }}" required>
                                </td>
                                <td>
                                    <input type="number" min="0.001" step="0.001" name="items[{{ $index }}][weight_kg]" class="form-control text-end weight-input" value="{{ number_format((float) ($item['weight_kg'] ?? (($item['quantity'] ?? 1) * $variant['weight_per_unit'])), 3, '.', '') }}" data-unit-weight="{{ $variant['weight_per_unit'] }}" data-auto-weight="0" required>
                                    <div class="small text-muted text-end">Chuẩn: {{ number_format($variant['weight_per_unit'], 3, ',', '.') }} kg/đv</div>
                                </td>
                                <td>
                                    <input type="number" min="0" step="1000" name="items[{{ $index }}][unit_cost]" class="form-control text-end" value="{{ $item['unit_cost'] ?? 0 }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-link text-danger p-0 remove-row-btn" title="Xóa dòng">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="fw-semibold text-primary">Tổng khối lượng xuất: <span id="transferTotalWeight">0,000</span> kg</div>
                <div class="d-flex justify-content-end gap-2">
                @if($editingTransfer)
                    <a href="{{ route('warehouse.inventory-transfers.index') }}" class="btn btn-outline-secondary">Hủy sửa</a>
                @endif
                <button type="submit" class="btn btn-success fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i>{{ $editingTransfer ? 'Lưu thay đổi' : 'Tạo phiếu điều chuyển kho' }}
                </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card transfer-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-clock-history me-1"></i>Phiếu điều chuyển đã tạo</strong>
        <span class="text-muted small">{{ number_format($outgoingTransfers->total()) }} phiếu</span>
    </div>
    @if($outgoingTransfers->isEmpty())
        <div class="card-body text-center text-muted py-4">
            Chưa có phiếu điều chuyển nào từ kho này.
        </div>
    @else
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Kho nhận</th>
                        <th>Sản phẩm</th>
                        <th>Người tạo</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outgoingTransfers as $transfer)
                        @php
                            $status = (string) $transfer->status;
                            $statusMeta = match ($status) {
                                'received_completed' => ['Đã tiếp nhận', 'bg-success'],
                                'cancelled' => ['Đã hủy', 'bg-secondary'],
                                default => ['Chờ kho nhận', 'bg-warning text-dark'],
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $transfer->transfer_code ?? ('#' . $transfer->id) }}
                                @if($transfer->order)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-block mt-1">Đơn {{ $transfer->order->code ?: '#'.$transfer->order->id }}</span>
                                @endif
                                @if($transfer->dispatchEntry?->slip)
                                    <a class="d-block small text-decoration-none mt-1" href="{{ route('warehouse.dispatch-slips.show', $transfer->dispatchEntry->slip) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ $transfer->dispatchEntry->slip->code }}</a>
                                @endif
                            </td>
                            <td>{{ $transfer->targetWarehouse?->name ?? '—' }}</td>
                            <td>
                                @foreach($transfer->items as $item)
                                    <div class="small">
                                        {{ $item->variant?->product?->name ?? 'Sản phẩm' }} - {{ $item->variant?->name ?? 'Biến thể' }}
                                        <span class="text-muted">x {{ number_format((int) $item->quantity) }} · {{ number_format((float) $item->weight_kg, 3, ',', '.') }} kg</span>
                                    </div>
                                @endforeach
                                <div class="small fw-semibold text-primary mt-1">Tổng: {{ number_format((float) $transfer->items->sum('weight_kg'), 3, ',', '.') }} kg</div>
                            </td>
                            <td>{{ $transfer->requester?->name ?? '—' }}</td>
                            <td>{{ optional($transfer->requested_at ?? $transfer->created_at)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge transfer-pill {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span></td>
                            <td class="text-end">
                                @if($transfer->status === \App\Models\WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE && !$transfer->dispatchEntry && !$transfer->order_id)
                                    <a href="{{ route('warehouse.inventory-transfers.edit', $transfer) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i>Sửa
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $outgoingTransfers->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="productSelectionModal" tabindex="-1" aria-labelledby="productSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="productSelectionModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Chọn sản phẩm tồn kho
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom sticky-top bg-white">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo tên sản phẩm hoặc biến thể...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 60%; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light" style="">
                            <tr>
                                <th class="ps-3">Sản phẩm - Biến thể</th>
                                <th class="text-center">ĐVT</th>
                                <th class="text-center">Tồn khả dụng</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="productSelectionList">
                            @foreach($availableVariantsGrouped as $group)
                                @php
                                    $totalAvailable = $group['variants']->sum('available');
                                @endphp
                                <tr class="product-group-row" data-group-id="{{ $group['product']['id'] }}">
                                    <td colspan="4" class="bg-light fw-bold align-middle" style="cursor:pointer;">
                                        <span class="toggle-group me-2" data-group-id="{{ $group['product']['id'] }}">▼</span>
                                        @if($group['product']['thumbnail'])
                                            <img src="{{ asset('storage/' . $group['product']['thumbnail']) }}" alt="thumb" style="width:32px;height:32px;object-fit:cover;border-radius:6px;margin-right:8px;">
                                        @endif
                                        {{ $group['product']['name'] }}
                                        @if($group['product']['sku'])<span class="text-muted small ms-2">[{{ $group['product']['sku'] }}]</span>@endif
                                        @if($group['product']['category'])<span class="badge bg-secondary ms-2">{{ $group['product']['category'] }}</span>@endif
                                        <span class="badge bg-info ms-2">{{ $group['variants']->count() }} biến thể</span>
                                        <span class="badge bg-success ms-2">Tổng tồn: {{ number_format($totalAvailable) }}</span>
                                    </td>
                                </tr>
                                @foreach($group['variants'] as $variant)
                                    <tr class="product-selection-row group-variant-row group-{{ $group['product']['id'] }}" data-search="{{ mb_strtolower($group['product']['name'].' '.$variant['name'].' '.$variant['sku']) }}" data-group-id="{{ $group['product']['id'] }}">
                                        <td class="ps-4">
                                            <span class="fw-semibold">{{ $variant['name'] }}</span>
                                            @if($variant['sku'])<span class="text-muted small ms-2">[{{ $variant['sku'] }}]</span>@endif
                                            @if(isset($variant['attributes']) && $variant['attributes'])<span class="text-muted small ms-2">{{ $variant['attributes'] }}</span>@endif
                                        </td>
                                        <td class="text-center">{{ $variant['unit_label'] }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill">{{ number_format($variant['available']) }}</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-success js-select-product" 
                                                data-id="{{ $variant['variant_id'] }}"
                                                data-label="{{ $group['product']['name'] }} - {{ $variant['name'] }}"
                                                data-available="{{ $variant['available'] }}"
                                                data-unit-weight="{{ $variant['weight_per_unit'] }}">
                                                <i class="bi bi-plus-circle me-1"></i>Chọn
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr id="noProductsFound" style="display: none;">
                                <td colspan="4" class="text-center text-muted py-4">Không tìm thấy sản phẩm phù hợp.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const tableBody = document.getElementById('transferItemsBody');
    const form = document.getElementById('inventoryTransferForm');
    const searchInput = document.getElementById('productSearchInput');
    const noProductsFound = document.getElementById('noProductsFound');
    const totalWeight = document.getElementById('transferTotalWeight');
    let rowIndex = tableBody.querySelectorAll('.transfer-item-row').length || 0;

    function updateTotalWeight() {
        const total = Array.from(tableBody.querySelectorAll('.weight-input')).reduce(function (sum, input) {
            return sum + (parseFloat(input.value || '0') || 0);
        }, 0);
        if (totalWeight) totalWeight.textContent = total.toLocaleString('vi-VN', {minimumFractionDigits: 3, maximumFractionDigits: 3});
    }

    function attachRowHandlers(row) {
        const qtyInput = row.querySelector('.qty-input');
        const weightInput = row.querySelector('.weight-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                const max = parseInt(qtyInput.max || '0', 10);
                const value = parseInt(qtyInput.value || '0', 10);
                if (max > 0 && value > max) {
                    qtyInput.value = String(max);
                }
                if (weightInput?.dataset.autoWeight === '1') {
                    const unitWeight = parseFloat(weightInput.dataset.unitWeight || '1') || 1;
                    weightInput.value = (Math.max(1, parseInt(qtyInput.value || '1', 10)) * unitWeight).toFixed(3);
                }
                updateTotalWeight();
            });
        }

        if (weightInput) {
            weightInput.addEventListener('input', function () {
                weightInput.dataset.autoWeight = '0';
                updateTotalWeight();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                updateTotalWeight();
            });
        }
    }

    // Xử lý chọn biến thể sản phẩm trong popup
    document.querySelectorAll('.js-select-product').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const variantId = this.getAttribute('data-id');
            const label = this.getAttribute('data-label');
            const available = parseInt(this.getAttribute('data-available') || '0', 10);
            const unitWeight = parseFloat(this.getAttribute('data-unit-weight') || '1') || 1;
            // Check if already in table
            let exists = false;
            tableBody.querySelectorAll('.transfer-item-row').forEach(function (row) {
                const input = row.querySelector('.variant-input');
                if (input && input.value === variantId) {
                    exists = true;
                }
            });
            if (exists) return;

            // Tạo dòng mới
            const tr = document.createElement('tr');
            tr.className = 'transfer-item-row';
            tr.innerHTML = `
                <td>
                    <div class="fw-semibold">${label}</div>
                    <input type="hidden" name="items[${rowIndex}][product_variant_id]" class="variant-input" value="${variantId}">
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border available-badge" data-available="${available}">${available.toLocaleString('vi-VN')}</span>
                </td>
                <td>
                    <input type="number" min="1" max="${Math.max(1, available)}" name="items[${rowIndex}][quantity]" class="form-control text-center qty-input" value="1" required>
                </td>
                <td>
                    <input type="number" min="0.001" step="0.001" name="items[${rowIndex}][weight_kg]" class="form-control text-end weight-input" value="${unitWeight.toFixed(3)}" data-unit-weight="${unitWeight}" data-auto-weight="1" required>
                    <div class="small text-muted text-end">Chuẩn: ${unitWeight.toLocaleString('vi-VN', {minimumFractionDigits: 3, maximumFractionDigits: 3})} kg/đv</div>
                </td>
                <td>
                    <input type="number" min="0" step="1000" name="items[${rowIndex}][unit_cost]" class="form-control text-end" value="0">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0 remove-row-btn" title="Xóa dòng">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(tr);
            attachRowHandlers(tr);
            updateTotalWeight();
            rowIndex++;

            // Đóng modal
            const modalEl = document.getElementById('productSelectionModal');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });

    tableBody.querySelectorAll('.transfer-item-row').forEach(attachRowHandlers);
    updateTotalWeight();

    // Giữ lại các logic kiểm tra submit form
    form.addEventListener('submit', function (event) {
        const rows = tableBody.querySelectorAll('.transfer-item-row');
        if (rows.length === 0) {
            event.preventDefault();
            alert('Phiếu điều chuyển cần ít nhất 1 sản phẩm.');
            return;
        }
        const selected = new Set();
        for (const row of rows) {
            const input = row.querySelector('.variant-input');
            const qty = row.querySelector('.qty-input');
            const badge = row.querySelector('.available-badge');
            const variantId = input ? String(input.value || '') : '';
            const qtyValue = qty ? parseInt(qty.value || '0', 10) : 0;
            const available = badge ? parseInt(badge.getAttribute('data-available') || '0', 10) : 0;
            if (!variantId) continue;
            if (selected.has(variantId)) {
                event.preventDefault();
                alert('Một sản phẩm đang bị chọn lặp. Vui lòng gộp số lượng vào một dòng.');
                return;
            }
            selected.add(variantId);
            if (available > 0 && qtyValue > available) {
                event.preventDefault();
                alert('Số lượng điều chuyển vượt quá tồn khả dụng. Vui lòng kiểm tra lại.');
                return;
            }
        }
    });
})();
</script>
@endsection
