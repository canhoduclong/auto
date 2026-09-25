@extends('layouts.warehouse')

@section('title', 'Pha lóc')

@push('styles')
<style>
    .cutting-order-list {
        display: grid;
        gap: 12px;
    }
    .cutting-order-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .cutting-order-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eef2f7;
    }
    .cutting-order-sequence {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 54px;
        height: 54px;
        border-radius: 12px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 1.45rem;
        font-weight: 900;
        line-height: 1;
    }
    .cutting-order-code {
        font-size: 1.05rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.2;
    }
    .cutting-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(92px, 1fr));
        gap: 8px;
    }
    .cutting-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .cutting-meta-value {
        font-size: .92rem;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .cutting-section {
        padding-top: 12px;
    }
    .cutting-section-title {
        font-size: .78rem;
        font-weight: 800;
        color: #334155;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .cutting-item-table-wrap {
        overflow-x: auto;
    }
    .cutting-item-head,
    .cutting-item-row {
        display: grid;
        grid-template-columns: 48px minmax(180px, 1.5fr) 64px 64px 90px 90px 110px;
        gap: 8px;
        align-items: center;
        min-width: 760px;
    }
    .cutting-item-head {
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        background: #eef3f9;
        padding: 6px 10px;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        font-weight: 800;
    }
    .cutting-item-list {
        list-style: none;
        margin: 6px 0 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .cutting-item-row {
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        padding: 6px 10px;
        background: #f8fafc;
        font-size: .82rem;
    }
    .cutting-item-thumb,
    .cutting-item-thumb-placeholder {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
    }
    .cutting-item-thumb {
        object-fit: cover;
        display: block;
    }
    .cutting-item-thumb-placeholder {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        border-style: dashed;
    }
    .cutting-item-name {
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cutting-item-cell {
        text-align: center;
        color: #334155;
        white-space: nowrap;
    }
    .cutting-item-cell strong {
        color: #0f172a;
    }
    .cutting-shortage-panel {
        border: 1px solid #fecaca;
        background: #fff7f7;
        border-radius: 10px;
        padding: 10px 12px;
    }
    @media (max-width: 767.98px) {
        .cutting-order-head {
            flex-direction: column;
        }
        .cutting-meta-grid {
            grid-template-columns: 1fr;
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $selected = $selectedMaterials ?? collect();
@endphp
<div class="d-flex align-items-start justify-content-between gap-3 mb-3">
    <div>
        <h1 class="h5 fw-bold mb-1">Pha lóc: {{ $targetVariant->product?->name }} {{ $targetVariant->name }}</h1>
        <div class="text-muted small">Chọn sản phẩm và số lượng để pha lóc, bấm Thực hiện, nhập kg thực tế rồi Hoàn thành.</div>
    </div>
    <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary btn-sm">Quay lại</a>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Cần bổ sung chi tiết đơn</div>
    <div class="card-body">
        <div class="cutting-order-list">
            @forelse($cuttingOrders ?? collect() as $cuttingOrder)
                @php
                    $cuttingPlan = $cuttingPlansByOrder[$cuttingOrder->id][$targetVariant->id] ?? null;
                    $shortQty = (float) data_get($cuttingPlan, 'shortage.short_qty', 0);
                    $modalId = $cuttingPlan ? 'cutting-order-modal-' . $cuttingOrder->id . '-' . $targetVariant->id : null;
                    $items = $cuttingOrder->items ?? collect();
                    $totalQty = (float) $items->sum('quantity');
                    $deliveryAddress = $cuttingOrder->recipient_address ?: ($cuttingOrder->customer?->address ?: 'Chưa có địa chỉ');
                    $deliveryTime = $cuttingOrder->delivery_time ?: ($cuttingOrder->customer?->delivery_time ?: 'Chưa cập nhật');
                    $customerCode = $cuttingOrder->customer?->customer_code ?? ('#' . ($cuttingOrder->customer?->id ?? ''));
                @endphp
                <div class="cutting-order-card" id="cutting-order-card-{{ $cuttingOrder->id }}">
                    <div class="cutting-order-head">
                        <div class="d-flex align-items-start gap-3">
                            <div class="cutting-order-sequence">{{ $cuttingOrder->daily_sequence ?? '—' }}</div>
                            <div>
                                <div class="cutting-order-code">{{ $cuttingOrder->customer?->name ?? 'Khách hàng' }}</div>
                                <div class="text-muted small mt-1">
                                    {{ optional($cuttingOrder->created_at)->format('d/m/Y H:i') ?: '—' }}
                                    @if($cuttingOrder->customer?->phone)
                                        , <i class="bi bi-telephone me-1"></i>{{ $cuttingOrder->customer->phone }}
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">{{ $cuttingOrder->code }}</div>
                            </div>
                        </div>
                        <div class="cutting-meta-grid">
                            <div>
                                <div class="cutting-meta-label">Mã KH</div>
                                <div class="cutting-meta-value">{{ $customerCode }}</div>
                            </div>
                            <div>
                                <div class="cutting-meta-label">Tổng số lượng</div>
                                <div class="cutting-meta-value">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</div>
                            </div>
                            <div>
                                <div class="cutting-meta-label">Thiếu pha lóc</div>
                                <div class="cutting-meta-value text-danger">{{ format_kg($shortQty) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="cutting-section">
                        <div class="cutting-section-title">Giao hàng</div>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $deliveryAddress }}
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i>Giờ giao: {{ $deliveryTime }}
                        </div>
                    </div>

                    <div class="cutting-section">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                            <div class="cutting-section-title mb-0">Danh sách sản phẩm</div>
                            @if($cuttingPlan)
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}">
                                    <i class="bi bi-scissors me-1"></i>Thêm hàng pha lóc
                                </button>
                            @endif
                        </div>
                        @if($items->isNotEmpty())
                            <div class="cutting-item-table-wrap">
                                <div class="cutting-item-head">
                                    <div>Ảnh</div>
                                    <div>Sản phẩm</div>
                                    <div class="text-center">SL</div>
                                    <div class="text-center">Size</div>
                                    <div class="text-center">Tổng</div>
                                    <div class="text-center">Đơn giá</div>
                                    <div class="text-end">Thành tiền</div>
                                </div>
                                <ul class="cutting-item-list">
                                    @foreach($items as $item)
                                        @php
                                            $variant = $item->variant;
                                            $product = $item->product;
                                            $productName = $variant?->name ?? $product?->name ?? 'Sản phẩm';
                                            $qty = (float) ($item->quantity ?? 0);
                                            $unitPrice = (float) ($item->price ?? 0);
                                            $lineTotal = (float) ($item->total ?? 0);
                                            if ($lineTotal <= 0) {
                                                $lineTotal = $qty * $unitPrice;
                                            }
                                            $variantSize = $variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                                : '-';
                                            $imagePath = $variant?->avatar?->media?->file_path
                                                ?? $product?->avatar?->media?->file_path
                                                ?? null;
                                            $isTargetItem = (int) ($item->product_variant_id ?? 0) === (int) $targetVariant->id;
                                        @endphp
                                        <li>
                                            <div class="cutting-item-row {{ $isTargetItem ? 'border-danger bg-danger-subtle' : '' }}">
                                                <div>
                                                    @if($imagePath)
                                                        <img class="cutting-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">
                                                    @else
                                                        <span class="cutting-item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                                    @endif
                                                </div>
                                                <div class="cutting-item-name" title="{{ $productName }}">
                                                    {{ $productName }}
                                                    @if($variant?->sku)
                                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="cutting-item-cell"><strong>{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</strong></div>
                                                <div class="cutting-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="cutting-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                                <div class="cutting-item-cell">{{ number_format($unitPrice, 0, ',', '.') }}đ</div>
                                                <div class="cutting-item-cell text-end"><strong>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="text-muted small">Không có sản phẩm trong đơn.</div>
                        @endif
                    </div>

                    <div class="cutting-section">
                        <div class="cutting-shortage-panel d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <div>
                                <div class="fw-semibold text-danger">Thiếu {{ $targetVariant->product?->name }} {{ $targetVariant->name }}</div>
                                <div class="small text-muted">Cần bổ sung {{ format_kg($shortQty) }} để đủ đóng đơn này.</div>
                            </div>
                            @if($cuttingPlan)
                                <button type="button"
                                        class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}">
                                    Thêm hàng pha lóc
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3">Chưa có đơn hôm nay đang thiếu hàng pha lóc này.</div>
            @endforelse
        </div>
    </div>
</div>

@foreach($cuttingOrders ?? collect() as $cuttingOrder)
    @php
        $cuttingPlan = $cuttingPlansByOrder[$cuttingOrder->id][$targetVariant->id] ?? null;
    @endphp
    @if($cuttingPlan)
        @include('warehouse.cutting._order_modal', ['cuttingOrder' => $cuttingOrder, 'cuttingPlan' => $cuttingPlan, 'selectedDate' => now()->toDateString()])
    @endif
@endforeach

<form method="GET" action="{{ route('warehouse.cutting.form', $targetVariant) }}" class="card mb-3" id="cutting-materials-form">
    <input type="hidden" name="demand" value="{{ $demand }}">
    <div class="card-header fw-semibold">Bước 1. Chọn nguyên liệu</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Chọn</th>
                    <th>Sản phẩm</th>
                    <th class="text-end">Size</th>
                    <th class="text-end">Tồn kho</th>
                    <th style="width:180px;">Số lượng sử dụng</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $index => $material)
                    @php
                        $selectedQty = (float) ($selected->get($material['variant_id'])['quantity'] ?? 0);
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="form-check-input js-material-check" @checked($selectedQty > 0)></td>
                        <td>
                            <input type="hidden" name="materials[{{ $index }}][variant_id]" value="{{ $material['variant_id'] }}">
                            <div class="fw-semibold">{{ $material['label'] }}</div>
                        </td>
                        <td class="text-end">{{ number_format($material['size'], 2) }} kg</td>
                        <td class="text-end">{{ number_format($material['available'], 3) }}</td>
                        <td>
                            <input type="number"
                                   name="materials[{{ $index }}][quantity]"
                                   class="form-control form-control-sm js-material-qty"
                                   step="0.001"
                                   min="0"
                                   max="{{ $material['available'] }}"
                                   value="{{ $selectedQty > 0 ? $selectedQty : 0 }}">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa có nguyên liệu nguyên con phù hợp. Vui lòng cấu hình bảng thành phần.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary btn-sm" @disabled($materials->isEmpty())>Thực hiện</button>
    </div>
</form>

@if($selected->isNotEmpty() && $preview['finished_weight'] > 0)
<form method="POST" action="{{ route('warehouse.cutting.execute', $targetVariant) }}" id="cutting-completion-form" data-input-weight="{{ $preview['input_weight'] }}">
    <div class="alert alert-info">Đã chọn nguyên liệu. Nhập kg thực tế sau pha lóc bên dưới rồi bấm Hoàn thành để cập nhật tồn kho.</div>
    @csrf
    @foreach($selected as $idx => $row)
        <input type="hidden" name="materials[{{ $loop->index }}][variant_id]" value="{{ $row['variant_id'] }}">
        <input type="hidden" name="materials[{{ $loop->index }}][quantity]" value="{{ $row['quantity'] }}">
    @endforeach

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Khối lượng dự kiến để đối chiếu</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Tổng nguyên liệu</span>
                        <strong>{{ number_format($preview['input_weight'], 3) }} kg</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Thành phẩm dự kiến</span>
                        <strong>{{ number_format($preview['finished_weight'], 3) }} kg</strong>
                    </div>
                    @if($demand > 0)
                        <div class="alert {{ $preview['finished_weight'] >= $demand ? 'alert-success' : 'alert-warning' }} mt-3 mb-0">
                            Nhu cầu: {{ number_format($demand, 3) }} kg.
                            @if($preview['finished_weight'] >= $demand)
                                Đủ đáp ứng nhu cầu.
                            @else
                                Chưa đủ, cần bổ sung thêm nguyên liệu.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header fw-semibold">Bước 2. Kết quả thực tế sau pha lóc</div>
                <div class="card-body">
                    <label class="form-label fw-semibold">Thành phẩm nhập kho thực tế</label>
                    <div class="input-group input-group-sm mb-3">
                        <input type="number" name="actual_finished_weight" class="form-control js-finished-weight" step="0.001" min="0.001" value="{{ old('actual_finished_weight', $preview['finished_weight']) }}" required>
                        <span class="input-group-text">kg</span>
                    </div>

                    <div class="fw-semibold mb-2">Phụ phẩm sau pha lóc</div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Thành phần</th><th style="width:180px;">Khối lượng thực tế</th></tr></thead>
                            <tbody>
                                @forelse($preview['components'] as $component)
                                    <tr>
                                        <td>
                                            {{ $component['name'] }}
                                            <input type="hidden" name="components[{{ $loop->index }}][variant_id]" value="{{ $component['variant_id'] }}">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="components[{{ $loop->index }}][weight]" step="0.001" min="0" class="form-control js-component-weight" value="{{ old('components.' . $loop->index . '.weight', $component['weight']) }}">
                                                <span class="input-group-text">kg</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">Chưa có thành phần phát sinh.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <input type="hidden" name="defer_components" value="0">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="defer_components" value="1" id="defer-cutting-components" @checked(old('defer_components', '1') === '1')>
                        <label class="form-check-label" for="defer-cutting-components">Nhập kho phụ phẩm sau</label>
                        <div class="small text-muted">Phụ phẩm được ghi vào phiếu chờ nhập kho. Bỏ chọn để nhập kho cùng thành phẩm khi hoàn thành.</div>
                    </div>
                    <div class="small text-muted mb-3" id="cutting-weight-summary" aria-live="polite"></div>
                    <label class="form-label fw-semibold">Ghi chú xuất kho nguyên liệu</label>
                    <textarea name="note" class="form-control" rows="2">{{ old('note', 'Xuất kho để thực hiện pha lóc.') }}</textarea>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-success">Hoàn thành</button>
                </div>
            </div>
        </div>
    </div>
</form>
@elseif($selected->isNotEmpty())
    <div class="alert alert-warning">Nguyên liệu đã chọn chưa có sản lượng pha lóc phù hợp. Vui lòng kiểm tra cấu hình thành phần hoặc chọn nguyên liệu khác.</div>
@endif
@endsection

@push('scripts')
<script>
const materialsForm = document.getElementById('cutting-materials-form');
const completionForm = document.getElementById('cutting-completion-form');

document.querySelectorAll('.js-material-check').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        const input = checkbox.closest('tr').querySelector('.js-material-qty');
        if (checkbox.checked && Number(input.value) <= 0) input.value = 1;
        if (!checkbox.checked) input.value = 0;
    });
});
document.querySelectorAll('.js-material-qty').forEach(function (input) {
    input.addEventListener('input', function () {
        input.closest('tr').querySelector('.js-material-check').checked = Number(input.value) > 0;
    });
});
materialsForm.addEventListener('input', function () {
    if (completionForm) {
        completionForm.hidden = true;
    }
});
materialsForm.addEventListener('submit', function (event) {
    const quantities = Array.from(materialsForm.querySelectorAll('.js-material-qty'));
    if (!quantities.some(input => Number(input.value) > 0)) {
        event.preventDefault();
        const first = quantities[0];
        if (first) {
            first.setCustomValidity('Vui lòng chọn ít nhất một sản phẩm và số lượng pha lóc.');
            first.reportValidity();
        }
    }
});
materialsForm.addEventListener('input', function () {
    materialsForm.querySelectorAll('.js-material-qty').forEach(input => input.setCustomValidity(''));
});
if (completionForm) {
    const finished = completionForm.querySelector('.js-finished-weight');
    const updateWeights = function () {
        const inputWeight = Number(completionForm.dataset.inputWeight);
        const components = Array.from(completionForm.querySelectorAll('.js-component-weight'))
            .reduce((total, input) => total + Number(input.value || 0), 0);
        const outputWeight = Number(finished.value || 0) + components;
        finished.setCustomValidity(Math.round(outputWeight * 1000) > Math.round(inputWeight * 1000)
            ? 'Tổng kg thành phẩm và phụ phẩm không được vượt quá kg nguyên liệu.' : '');
        document.getElementById('cutting-weight-summary').textContent =
            'Hao hụt: ' + Math.max(0, inputWeight - outputWeight).toLocaleString('vi-VN', { maximumFractionDigits: 3 }) + ' kg';
    };
    completionForm.addEventListener('input', updateWeights);
    completionForm.addEventListener('submit', function (event) {
        if (completionForm.dataset.submitting === '1' || completionForm.hidden) {
            event.preventDefault();
            return;
        }
        completionForm.dataset.submitting = '1';
        completionForm.querySelector('button[type="submit"]').disabled = true;
    });
    updateWeights();
    completionForm.scrollIntoView({ block: 'start' });
}
</script>
@endpush
