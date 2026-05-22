@extends('layouts.warehouse')

@section('title', 'Điều chuyển kho')

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
<div class="transfer-hero d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-arrow-left-right me-2"></i>Tạo phiếu điều chuyển kho</h4>
        <div class="small" style="opacity:.92;">Lấy hàng tồn tại kho đang quản lý để chuyển sang kho khác, kho nhận sẽ vào phần tiếp nhận để nhập kho.</div>
        <div class="small mt-2">Kho nguồn: <strong>{{ $sourceWarehouse?->name ?? '—' }}</strong></div>
    </div>
    <a href="{{ route('warehouse.inventory-transfers.incoming') }}" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-box-arrow-in-down me-1"></i>Tiếp nhận điều chuyển ({{ number_format($incomingPendingCount) }})
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
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
        <strong><i class="bi bi-file-earmark-plus me-1"></i>Thông tin phiếu điều chuyển</strong>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouse.inventory-transfers.store') }}" method="POST" id="inventoryTransferForm">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Kho nhận <span class="text-danger">*</span></label>
                    <select name="target_warehouse_id" class="form-select" required>
                        <option value="">-- Chọn kho nhận --</option>
                        @foreach($targetWarehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) old('target_warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-semibold small">Ghi chú</label>
                    <input type="text" class="form-control" name="note" maxlength="1000" value="{{ old('note') }}" placeholder="Ví dụ: điều chuyển bổ sung hàng bán nhanh cho kho trung tâm...">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Sản phẩm điều chuyển từ tồn kho hiện có</h6>
                <button class="btn btn-outline-primary btn-sm" type="button" id="addTransferItemBtn">
                    <i class="bi bi-plus-circle me-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle transfer-table" id="transferItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:320px;">Sản phẩm</th>
                            <th class="text-center" style="min-width:120px;">Tồn khả dụng</th>
                            <th class="text-center" style="min-width:120px;">Số lượng chuyển</th>
                            <th class="text-center" style="min-width:120px;">Đơn giá</th>
                            <th class="text-center" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="transferItemsBody">
                        <tr class="transfer-item-row" data-row-index="0">
                            <td>
                                <select name="items[0][product_variant_id]" class="form-select variant-select" required>
                                    <option value="">-- Chọn sản phẩm tồn kho --</option>
                                    @foreach($availableVariants as $variant)
                                        <option
                                            value="{{ $variant['variant_id'] }}"
                                            data-available="{{ $variant['available'] }}"
                                            data-unit-label="{{ $variant['unit_label'] }}"
                                            {{ (string) old('items.0.product_variant_id') === (string) $variant['variant_id'] ? 'selected' : '' }}>
                                            {{ $variant['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border available-badge">0</span>
                            </td>
                            <td>
                                <input type="number" min="1" name="items[0][quantity]" class="form-control text-center qty-input" value="{{ old('items.0.quantity', 1) }}" required>
                            </td>
                            <td>
                                <input type="number" min="0" step="1000" name="items[0][unit_cost]" class="form-control text-end" value="{{ old('items.0.unit_cost', 0) }}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-link text-danger p-0 remove-row-btn" title="Xóa dòng">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i>Tạo phiếu điều chuyển kho
                </button>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($outgoingTransfers as $transfer)
                        @php
                            $status = (string) $transfer->status;
                            $statusMeta = $status === 'received_completed'
                                ? ['Đã tiếp nhận', 'bg-success']
                                : ['Chờ kho nhận', 'bg-warning text-dark'];
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $transfer->transfer_code ?? ('#' . $transfer->id) }}</td>
                            <td>{{ $transfer->targetWarehouse?->name ?? '—' }}</td>
                            <td>
                                @foreach($transfer->items as $item)
                                    <div class="small">
                                        {{ $item->variant?->product?->name ?? 'Sản phẩm' }} - {{ $item->variant?->name ?? 'Biến thể' }}
                                        <span class="text-muted">x {{ number_format((int) $item->quantity) }}</span>
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ $transfer->requester?->name ?? '—' }}</td>
                            <td>{{ optional($transfer->requested_at ?? $transfer->created_at)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge transfer-pill {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span></td>
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

<script>
(function () {
    const tableBody = document.getElementById('transferItemsBody');
    const addBtn = document.getElementById('addTransferItemBtn');
    const form = document.getElementById('inventoryTransferForm');
    let rowIndex = 1;

    function updateAvailableBadge(row) {
        const select = row.querySelector('.variant-select');
        const badge = row.querySelector('.available-badge');
        if (!select || !badge) {
            return;
        }

        const option = select.options[select.selectedIndex];
        const available = option ? parseInt(option.dataset.available || '0', 10) : 0;
        badge.textContent = Number.isFinite(available) ? available.toLocaleString('vi-VN') : '0';

        const qtyInput = row.querySelector('.qty-input');
        if (qtyInput) {
            qtyInput.max = String(Math.max(available, 1));
            if (Number(qtyInput.value) > available && available > 0) {
                qtyInput.value = String(available);
            }
        }
    }

    function attachRowHandlers(row) {
        const variantSelect = row.querySelector('.variant-select');
        const qtyInput = row.querySelector('.qty-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        if (variantSelect) {
            variantSelect.addEventListener('change', function () {
                updateAvailableBadge(row);
            });
            updateAvailableBadge(row);
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                const max = parseInt(qtyInput.max || '0', 10);
                const value = parseInt(qtyInput.value || '0', 10);
                if (max > 0 && value > max) {
                    qtyInput.value = String(max);
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const rows = tableBody.querySelectorAll('.transfer-item-row');
                if (rows.length <= 1) {
                    alert('Phiếu điều chuyển cần ít nhất 1 sản phẩm.');
                    return;
                }
                row.remove();
            });
        }
    }

    addBtn.addEventListener('click', function () {
        const firstRow = tableBody.querySelector('.transfer-item-row');
        if (!firstRow) {
            return;
        }

        const newRow = firstRow.cloneNode(true);
        newRow.setAttribute('data-row-index', String(rowIndex));

        newRow.querySelectorAll('select, input').forEach(function (input) {
            const currentName = input.getAttribute('name') || '';
            input.setAttribute('name', currentName.replace(/\[\d+\]/, '[' + rowIndex + ']'));

            if (input.tagName === 'SELECT') {
                input.value = '';
            }
            if (input.classList.contains('qty-input')) {
                input.value = '1';
            }
            if (input.type === 'number' && !input.classList.contains('qty-input')) {
                input.value = '0';
            }
        });

        tableBody.appendChild(newRow);
        attachRowHandlers(newRow);
        rowIndex += 1;
    });

    tableBody.querySelectorAll('.transfer-item-row').forEach(attachRowHandlers);

    form.addEventListener('submit', function (event) {
        const rows = tableBody.querySelectorAll('.transfer-item-row');
        const selected = new Set();

        for (const row of rows) {
            const select = row.querySelector('.variant-select');
            const qty = row.querySelector('.qty-input');
            const variantId = select ? String(select.value || '') : '';
            const qtyValue = qty ? parseInt(qty.value || '0', 10) : 0;

            if (!variantId) {
                continue;
            }

            if (selected.has(variantId)) {
                event.preventDefault();
                alert('Một sản phẩm đang bị chọn lặp. Vui lòng gộp số lượng vào một dòng.');
                return;
            }

            selected.add(variantId);

            const selectedOption = select.options[select.selectedIndex];
            const available = selectedOption ? parseInt(selectedOption.dataset.available || '0', 10) : 0;
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
