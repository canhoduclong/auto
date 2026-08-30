@extends('layouts.warehouse')

@section('title', 'Kiểm Kê Kho')
@section('subtitle', 'Đối chiếu số thực tế và điều chỉnh tồn kho có lưu lịch sử')

@push('styles')
<style>
    .stocktake-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 14px rgba(15,23,42,.05); overflow:hidden; }
    .stocktake-table { min-width:1110px; table-layout:fixed; }
    .stocktake-table th { white-space:nowrap; font-size:.7rem; text-transform:uppercase; color:#64748b; background:#f8fafc; padding:.55rem .45rem; }
    .stocktake-table td { padding:.55rem .45rem; }
    .stocktake-table td, .stocktake-table th { vertical-align:middle; border-color:#e2e8f0; }
    .stocktake-product { display:flex; align-items:baseline; gap:14px; min-width:0; white-space:nowrap; }
    .stocktake-product-name { overflow:hidden; text-overflow:ellipsis; }
    .stocktake-variant { color:#64748b; font-size:.8rem; flex-shrink:0; }
    .stocktake-sku { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .stocktake-number { font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .stocktake-input { width:104px; min-width:0; text-align:right; font-variant-numeric:tabular-nums; }
    .stocktake-diff { font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .stocktake-diff.positive { color:#047857; }
    .stocktake-diff.negative { color:#dc2626; }
    .stocktake-diff.equal { color:#64748b; }
    .stocktake-history-item { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
    .stocktake-history-head { background:#f8fafc; padding:12px 14px; }
    .stocktake-help { border-left:4px solid #0ea5e9; background:#f0f9ff; }
</style>
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Không thể chốt kiểm kê</div>
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="stocktake-help rounded p-3 mb-3 small">
    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Hướng dẫn kiểm kê</div>
    Có thể nhập số con/số lượng, số kg hoặc cả hai cho mặt hàng đã cân/đếm. Đại lượng để trống sẽ được giữ nguyên. Khi chốt, hệ thống lưu riêng chênh lệch số lượng và chênh lệch khối lượng rồi cập nhật tồn kho.
</div>

<div class="stocktake-card mb-3">
    <div class="p-3 border-bottom">
        <form method="GET" action="{{ route('warehouse.stocktakes.index') }}" class="row g-2 align-items-end">
            @if($warehouses->count() > 1)
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Kho kiểm kê</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        @foreach($warehouses as $warehouseOption)
                            <option value="{{ $warehouseOption->id }}" @selected((int) $warehouse->id === (int) $warehouseOption->id)>{{ $warehouseOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            @endif
            <div class="col-lg-5 col-md-5">
                <label class="form-label small fw-semibold mb-1">Tìm sản phẩm / biến thể / SKU</label>
                <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Nhập từ khóa...">
            </div>
            <div class="col-lg-3 col-md-4">
                <label class="form-label small fw-semibold mb-1">Tồn tại thời điểm</label>
                <input type="datetime-local" name="counted_at" class="form-control form-control-sm"
                       value="{{ $countedAt->format('Y-m-d\TH:i:s') }}" max="{{ now()->format('Y-m-d\TH:i:s') }}" step="1" required>
            </div>
            <div class="col-lg-2 col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-arrow-clockwise me-1"></i>Tải tồn</button>
                <a href="{{ route('warehouse.stocktakes.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
            </div>
        </form>
    </div>

    <form method="POST" action="{{ route('warehouse.stocktakes.store') }}" id="stocktakeForm">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
        <input type="hidden" name="counted_at" value="{{ $countedAt->format('Y-m-d H:i:s') }}">
        <div class="p-3 border-bottom bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Kho</label>
                    <input class="form-control form-control-sm" value="{{ $warehouse->name }}" readonly>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Thời điểm kiểm kê</label>
                    <input class="form-control form-control-sm" value="{{ $countedAt->format('d/m/Y H:i:s') }}" readonly>
                    <div class="small text-muted mt-1">Đổi thời điểm ở bộ lọc phía trên rồi bấm “Tải tồn”.</div>
                </div>
                <div class="col-lg-6 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Ghi chú</label>
                    <input type="text" name="note" class="form-control form-control-sm" value="{{ old('note') }}" maxlength="2000" placeholder="Ca kiểm kê, lý do hoặc nội dung cần lưu ý...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 stocktake-table">
                <colgroup>
                    <col style="width:44px;">
                    <col style="width:215px;">
                    <col style="width:125px;">
                    <col style="width:62px;">
                    <col style="width:70px;">
                    <col style="width:82px;">
                    <col style="width:125px;">
                    <col style="width:125px;">
                    <col style="width:82px;">
                    <col style="width:82px;">
                    <col style="width:98px;">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sản phẩm / Biến thể</th>
                        <th>SKU</th>
                        <th>ĐVT</th>
                        <th class="text-end" title="Tồn hệ thống tại {{ $countedAt->format('d/m/Y H:i:s') }}">SL tại giờ kiểm</th>
                        <th class="text-end" title="Khối lượng hệ thống tại {{ $countedAt->format('d/m/Y H:i:s') }}">Kg tại giờ kiểm</th>
                        <th class="text-end">SL thực tế</th>
                        <th class="text-end">Kg thực tế</th>
                        <th class="text-end">Lệch SL</th>
                        <th class="text-end">Lệch kg</th>
                        <th class="text-end">Đã giữ chỗ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventories as $inventory)
                        @php
                            $variant = $inventory->productVariant;
                            $productName = $variant?->product?->name ?? 'Sản phẩm';
                            $variantName = $variant?->name ?: 'Mặc định';
                            $oldQuantity = old('items.'.$inventory->id.'.counted_quantity');
                            $oldWeight = old('items.'.$inventory->id.'.counted_weight_kg');
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $inventories->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="stocktake-product">
                                    <span class="stocktake-product-name fw-semibold">{{ $productName }}</span>
                                    <span class="stocktake-variant">{{ $variantName }}</span>
                                </div>
                            </td>
                            <td class="stocktake-sku">{{ $variant?->sku ?: '—' }}</td>
                            <td>{{ $variant?->product?->unit_label ?? '—' }}</td>
                            <td class="text-end stocktake-number">{{ number_format((float) $inventory->stocktake_quantity, 0, ',', '.') }}</td>
                            <td class="text-end stocktake-number">{{ format_kg((float) $inventory->stocktake_weight_kg) }}</td>
                            <td class="text-end">
                                <input type="hidden" name="items[{{ $inventory->id }}][expected_quantity]" value="{{ number_format((float) $inventory->stocktake_quantity, 3, '.', '') }}">
                                <input type="hidden" name="items[{{ $inventory->id }}][expected_weight_kg]" value="{{ number_format((float) $inventory->stocktake_weight_kg, 3, '.', '') }}">
                                <input type="number"
                                       name="items[{{ $inventory->id }}][counted_quantity]"
                                       class="form-control form-control-sm stocktake-input ms-auto js-counted-value"
                                       value="{{ $oldQuantity }}"
                                       min="0" step="1"
                                       data-system="{{ number_format((float) $inventory->stocktake_quantity, 3, '.', '') }}"
                                       data-diff-target="quantity-diff-{{ $inventory->id }}"
                                       data-suffix=""
                                       aria-label="Số lượng thực tế {{ $productName }} {{ $variantName }}">
                            </td>
                            <td class="text-end">
                                <input type="number"
                                       name="items[{{ $inventory->id }}][counted_weight_kg]"
                                       class="form-control form-control-sm stocktake-input ms-auto js-counted-value"
                                       value="{{ $oldWeight }}"
                                       min="0" step="0.001"
                                       data-system="{{ number_format((float) $inventory->stocktake_weight_kg, 3, '.', '') }}"
                                       data-diff-target="weight-diff-{{ $inventory->id }}"
                                       data-suffix=" kg"
                                       aria-label="Kg thực tế {{ $productName }} {{ $variantName }}">
                            </td>
                            <td class="text-end">
                                <span id="quantity-diff-{{ $inventory->id }}" class="stocktake-diff equal">—</span>
                            </td>
                            <td class="text-end">
                                <span id="weight-diff-{{ $inventory->id }}" class="stocktake-diff equal">—</span>
                            </td>
                            <td class="text-end {{ (float) $inventory->reserved_quantity > (float) $inventory->quantity ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ number_format((float) $inventory->reserved_quantity, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">Kho chưa có dữ liệu tồn phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($inventories->isNotEmpty())
            <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small text-muted">Bỏ trống mặt hàng chưa kiểm kê. Nếu chỉ nhập một đại lượng, đại lượng còn lại được giữ nguyên.</div>
                <button type="submit" class="btn btn-success" onclick="return confirm('Chốt kiểm kê và cập nhật tồn kho theo số thực tế đã nhập?');">
                    <i class="bi bi-check2-square me-1"></i>Chốt kiểm kê & cập nhật tồn
                </button>
            </div>
        @endif
    </form>

    @if($inventories->hasPages())
        <div class="p-3 border-top">{{ $inventories->links() }}</div>
    @endif
</div>

<div class="stocktake-card">
    <div class="p-3 border-bottom fw-semibold"><i class="bi bi-clock-history me-1"></i>10 phiếu kiểm kê gần nhất</div>
    <div class="p-3 d-grid gap-2">
        @forelse($recentStocktakes as $stocktake)
            @php
                $totalDifference = (float) $stocktake->items->sum('difference');
                $totalWeightDifference = (float) $stocktake->items->sum('weight_difference');
                $changedCount = $stocktake->items->filter(fn ($item) => abs((float) $item->difference) >= 0.001 || abs((float) $item->weight_difference) >= 0.001)->count();
            @endphp
            <details class="stocktake-history-item">
                <summary class="stocktake-history-head d-flex flex-wrap align-items-center gap-3" style="cursor:pointer;">
                    <strong>{{ $stocktake->code }}</strong>
                    <span>{{ optional($stocktake->counted_at)->format('d/m/Y H:i') }}</span>
                    <span class="text-muted">{{ $stocktake->creator?->name ?? '—' }}</span>
                    <span class="badge bg-light text-dark border">{{ $stocktake->items_count }} mặt hàng</span>
                    <span class="badge {{ $changedCount > 0 ? 'bg-warning text-dark' : 'bg-success' }}">{{ $changedCount }} chênh lệch</span>
                    <span class="ms-auto fw-semibold {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-success' : 'text-muted') }}">
                        Lệch SL: {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 0, ',', '.') }}
                    </span>
                    <span class="fw-semibold {{ $totalWeightDifference < 0 ? 'text-danger' : ($totalWeightDifference > 0 ? 'text-success' : 'text-muted') }}">
                        Lệch kg: {{ $totalWeightDifference > 0 ? '+' : '' }}{{ format_kg($totalWeightDifference) }}
                    </span>
                </summary>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>Sản phẩm</th><th class="text-end">SL hệ thống</th><th class="text-end">SL thực tế</th><th class="text-end">Lệch SL</th><th class="text-end">Kg hệ thống</th><th class="text-end">Kg thực tế</th><th class="text-end">Lệch kg</th></tr>
                        </thead>
                        <tbody>
                            @foreach($stocktake->items as $item)
                                <tr>
                                    <td>{{ trim(($item->productVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($item->productVariant?->name ?: '')) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->system_quantity, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->counted_quantity, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold {{ (float) $item->difference < 0 ? 'text-danger' : ((float) $item->difference > 0 ? 'text-success' : 'text-muted') }}">
                                        {{ (float) $item->difference > 0 ? '+' : '' }}{{ number_format((float) $item->difference, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">{{ format_kg((float) $item->system_weight_kg) }}</td>
                                    <td class="text-end">{{ format_kg((float) $item->counted_weight_kg) }}</td>
                                    <td class="text-end fw-semibold {{ (float) $item->weight_difference < 0 ? 'text-danger' : ((float) $item->weight_difference > 0 ? 'text-success' : 'text-muted') }}">
                                        {{ (float) $item->weight_difference > 0 ? '+' : '' }}{{ format_kg((float) $item->weight_difference) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($stocktake->note)
                    <div class="p-2 px-3 border-top small text-muted">Ghi chú: {{ $stocktake->note }}</div>
                @endif
            </details>
        @empty
            <div class="text-center text-muted py-3">Chưa có phiếu kiểm kê.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatter = new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 3 });

    document.querySelectorAll('.js-counted-value').forEach(function (input) {
        const updateDifference = function () {
            const target = document.getElementById(input.dataset.diffTarget);
            if (!target) return;
            if (input.value === '') {
                target.textContent = '—';
                target.className = 'stocktake-diff equal';
                return;
            }

            const difference = Number(input.value) - Number(input.dataset.system || 0);
            target.textContent = `${difference > 0 ? '+' : ''}${formatter.format(difference)}${input.dataset.suffix || ''}`;
            target.className = `stocktake-diff ${difference > 0 ? 'positive' : (difference < 0 ? 'negative' : 'equal')}`;
        };

        input.addEventListener('input', updateDifference);
        updateDifference();
    });
});
</script>
@endpush
