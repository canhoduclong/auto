@php
    $modalOrder = $cuttingOrder ?? $order ?? null;
    $plan = $cuttingPlan ?? [];
    $targetVariantId = (int) ($plan['target_variant_id'] ?? 0);
    $modalId = 'cutting-order-modal-' . (int) ($modalOrder?->id ?? 0) . '-' . $targetVariantId;
    $selectedDateValue = $selectedDate ?? now()->toDateString();
    $materials = collect($plan['materials'] ?? []);
    $materialOptions = collect($plan['material_options'] ?? $materials);
    $materials = $materialOptions;
    $selectedMaterials = collect($plan['selected_materials'] ?? []);
    $shortage = $plan['shortage'] ?? [];
    $orderItems = collect($modalOrder?->items ?? []);
    $targetOrderItems = $orderItems->filter(fn ($item) => (int) ($item->product_variant_id ?? 0) === $targetVariantId);
    $planJson = [
        'target_variant_id' => $targetVariantId,
        'target_name' => $plan['target_name'] ?? 'Hàng pha lóc',
        'demand' => (float) ($plan['demand'] ?? 0),
        'required_qty' => (float) ($shortage['required_qty'] ?? $targetOrderItems->sum('quantity')),
        'available_qty' => (float) ($shortage['available_qty'] ?? 0),
        'short_qty' => (float) ($shortage['short_qty'] ?? $plan['demand'] ?? 0),
        'order_items' => $orderItems->map(function ($item) use ($targetVariantId) {
            $variant = $item->variant;
            $product = $item->product;
            $qty = (float) ($item->quantity ?? 0);
            $unitPrice = (float) ($item->price ?? 0);
            $lineTotal = (float) ($item->total ?? 0);
            if ($lineTotal <= 0) {
                $lineTotal = $qty * $unitPrice;
            }

            return [
                'variant_id' => (int) ($item->product_variant_id ?? 0),
                'name' => (string) ($variant?->name ?? $product?->name ?? 'Sản phẩm'),
                'sku' => (string) ($variant?->sku ?? ''),
                'size' => (float) ($variant?->size ?? $variant?->kg ?? 0),
                'quantity' => $qty,
                'total_label' => (string) ($item->display_total_label ?? ''),
                'line_total' => $lineTotal,
                'is_target' => (int) ($item->product_variant_id ?? 0) === $targetVariantId,
            ];
        })->values()->all(),
        'materials' => $materials->map(function ($material) {
            $removedIds = collect($material['removed_component_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
            $targetIds = collect($material['target_component_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

            return [
                'variant_id' => (int) ($material['variant_id'] ?? 0),
                'label' => (string) ($material['label'] ?? 'Nguyên con'),
                'size' => (float) ($material['size'] ?? 0),
                'available' => (float) ($material['available'] ?? 0),
                'unit_weight' => (float) ($material['unit_weight'] ?? 0),
                'output_per_unit' => (float) ($material['output_per_unit'] ?? 0),
                'target_component_ids' => $targetIds,
                'removed_component_ids' => $removedIds,
                'components' => collect($material['components'] ?? [])->map(fn ($component) => [
                    'variant_id' => (int) ($component['variant_id'] ?? 0),
                    'name' => (string) ($component['name'] ?? 'Thành phần'),
                    'standard_weight' => (float) ($component['standard_weight'] ?? 0),
                ])->values()->all(),
            ];
        })->values()->all(),
        'material_options' => $materialOptions->map(function ($material) {
            $removedIds = collect($material['removed_component_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
            $targetIds = collect($material['target_component_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

            return [
                'variant_id' => (int) ($material['variant_id'] ?? 0),
                'label' => (string) ($material['label'] ?? 'Nguyên con'),
                'size' => (float) ($material['size'] ?? 0),
                'available' => (float) ($material['available'] ?? 0),
                'unit_weight' => (float) ($material['unit_weight'] ?? 0),
                'output_per_unit' => (float) ($material['output_per_unit'] ?? 0),
                'target_component_ids' => $targetIds,
                'removed_component_ids' => $removedIds,
                'components' => collect($material['components'] ?? [])->map(fn ($component) => [
                    'variant_id' => (int) ($component['variant_id'] ?? 0),
                    'name' => (string) ($component['name'] ?? 'Thành phần'),
                    'standard_weight' => (float) ($component['standard_weight'] ?? 0),
                ])->values()->all(),
            ];
        })->values()->all(),
    ];
    $planJsonOptions = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP;
@endphp

@if($modalOrder && $targetVariantId > 0)
    @once
        @push('styles')
            <style>
                .cutting-order-dialog {
                    --bs-modal-width: min(1120px, calc(100vw - 24px));
                }
                .cutting-order-dialog .modal-content {
                    max-height: calc(100vh - 32px);
                }
                .cutting-order-dialog .modal-body {
                    overflow-y: auto;
                    max-height: calc(100vh - 190px);
                }
                .cutting-order-dialog .modal-footer,
                .cutting-order-dialog .modal-header {
                    flex-shrink: 0;
                }
                .js-cutting-modal.is-cutting-in-progress .modal-content {
                    border: 2px solid #f59e0b;
                    background: #fffbeb;
                }
                .js-cutting-modal.is-cutting-in-progress .modal-header,
                .js-cutting-modal.is-cutting-in-progress .modal-footer {
                    background: #fef3c7;
                }
                .cutting-actual-panel {
                    border: 1px solid #facc15;
                    background: #fff7ed;
                    border-radius: 8px;
                    padding: 12px;
                }
                @media (max-width: 575.98px) {
                    .cutting-order-dialog .modal-content {
                        max-height: calc(100vh - 12px);
                    }
                    .cutting-order-dialog .modal-body {
                        max-height: calc(100vh - 170px);
                    }
                }
            </style>
        @endpush
    @endonce

    <div class="modal fade js-cutting-modal"
         id="{{ $modalId }}"
         tabindex="-1"
         aria-labelledby="{{ $modalId }}-label"
         aria-hidden="true"
         data-plan='@json($planJson, $planJsonOptions)'>
        <div class="modal-dialog modal-xl modal-dialog-scrollable cutting-order-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('warehouse.cutting.confirm', ['variant' => $targetVariantId]) }}" class="js-cutting-execute-form">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $modalOrder->id }}">
                    <input type="hidden" name="selected_date" value="{{ $selectedDateValue }}">
                    <input type="hidden" class="js-cutting-actual-finished" value="{{ (float) data_get($plan, 'preview.finished_weight', 0) }}">
                    <div class="js-cutting-components-hidden"></div>
                    <textarea name="note" class="d-none">Xuất kho nguyên con để pha lóc bổ sung cho đơn {{ $modalOrder->code }}.</textarea>

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="{{ $modalId }}-label">Thêm hàng pha lóc cho đơn {{ $modalOrder->code }}</h5>
                            <div class="small text-muted">
                                {{ $plan['target_name'] ?? 'Hàng pha lóc' }} cần bổ sung
                                <strong>{{ format_kg((float) ($shortage['short_qty'] ?? $plan['demand'] ?? 0)) }}</strong>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <div class="fw-semibold mb-2">1. Thông tin đơn hàng để đối chiếu</div>
                        <div class="border rounded mb-3">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sản phẩm trong đơn</th>
                                            <th class="text-end">Size</th>
                                            <th class="text-end">SL</th>
                                            <th class="text-end">Tổng</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orderItems as $item)
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
                                                $variantSize = $variant?->size ?? $variant?->kg;
                                                $isTargetItem = (int) ($item->product_variant_id ?? 0) === $targetVariantId;
                                            @endphp
                                            <tr class="{{ $isTargetItem ? 'table-warning' : '' }}">
                                                <td>
                                                    <div class="fw-semibold">{{ $productName }}</div>
                                                    @if($variant?->sku)
                                                        <div class="small text-muted">SKU: {{ $variant->sku }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end">{{ $variantSize ? format_kg((float) $variantSize) : '—' }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</td>
                                                <td class="text-end">{{ $item->display_total_label }}</td>
                                                <td class="text-end">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">Đơn hàng chưa có sản phẩm.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="small px-3 py-2 bg-light border-top">
                                Sản phẩm đang cần bổ sung:
                                <strong>{{ $plan['target_name'] ?? 'Hàng pha lóc' }}</strong>,
                                đơn cần {{ rtrim(rtrim(number_format((float) ($shortage['required_qty'] ?? $targetOrderItems->sum('quantity')), 3, '.', ''), '0'), '.') }},
                                khả dụng {{ rtrim(rtrim(number_format((float) ($shortage['available_qty'] ?? 0), 3, '.', ''), '0'), '.') }},
                                thiếu <span class="text-danger fw-semibold">{{ format_kg((float) ($shortage['short_qty'] ?? $plan['demand'] ?? 0)) }}</span>.
                            </div>
                        </div>

                        <div class="fw-semibold mb-1">2. Danh sách sản phẩm/biến thể dùng để pha lóc</div>
                        <div class="small text-muted mb-2">Toàn bộ biến thể nguyên con đang hoạt động trong kho của bạn. Khối lượng dự kiến tính theo tỷ lệ % chung của sản phẩm pha lóc. Tồn khả dụng đã trừ số lượng giữ chỗ. Nguyên liệu chưa cấu hình mục tiêu hoặc thiếu % thành phần phụ sẽ có sản lượng dự kiến bằng 0 và không được xác nhận lấy hàng.</div>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Thêm biến thể nguyên con</label>
                                <select class="form-select form-select-sm js-cutting-material-select">
                                    <option value="">Chọn biến thể nguyên con...</option>
                                    @foreach($materialOptions as $option)
                                        <option value="{{ (int) ($option['variant_id'] ?? 0) }}">
                                            {{ $option['label'] ?? 'Nguyên con' }}
                                            - tồn {{ rtrim(rtrim(number_format((float) ($option['available'] ?? 0), 3, '.', ''), '0'), '.') }}
                                            - dự kiến {{ format_kg((float) ($option['output_per_unit'] ?? 0)) }}/con
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 js-cutting-material-add">
                                    Thêm vào danh sách
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">Chọn</th>
                                        <th>Sản phẩm / biến thể</th>
                                        <th class="text-end">Size</th>
                                        <th class="text-end">Tồn khả dụng</th>
                                        <th class="text-end">Dự kiến / con</th>
                                        <th style="width:170px;">Số lượng cần thêm</th>
                                        <th class="text-end" style="width:70px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="js-cutting-material-body">
                                    @forelse($materials as $index => $material)
                                        @php
                                            $materialId = (int) ($material['variant_id'] ?? 0);
                                            $suggestedQty = (float) ($material['suggested_quantity'] ?? data_get($selectedMaterials, $materialId . '.quantity', 0));
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       class="form-check-input js-cutting-material-check"
                                                       @checked($suggestedQty > 0)>
                                            </td>
                                            <td>
                                                <input type="hidden" name="materials[{{ $index }}][variant_id]" value="{{ $materialId }}">
                                                <div class="fw-semibold">{{ $material['label'] ?? 'Nguyên con' }}</div>
                                            </td>
                                            <td class="text-end">{{ format_kg((float) ($material['size'] ?? 0)) }}</td>
                                            <td class="text-end">{{ number_format((float) ($material['available'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="text-end">{{ format_kg((float) ($material['output_per_unit'] ?? 0)) }}</td>
                                            <td>
                                                <input type="number"
                                                       name="materials[{{ $index }}][quantity]"
                                                       class="form-control form-control-sm js-cutting-material-qty"
                                                       min="0"
                                                       max="{{ (float) ($material['available'] ?? 0) }}"
                                                       step="1"
                                                       value="{{ $suggestedQty > 0 ? (int) $suggestedQty : 0 }}"
                                               data-variant-id="{{ $materialId }}">
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger js-cutting-material-remove" title="Xóa biến thể khỏi danh sách">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-3">Chưa có sản phẩm/biến thể phù hợp để pha lóc.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 js-cutting-summary d-none"></div>
                        <div class="cutting-actual-panel mt-3 js-cutting-actual-panel d-none">
                            <div class="fw-semibold mb-2">4. Hoàn thiện: nhập kg thực tế sau pha lóc</div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Thành phẩm thực tế</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="actual_finished_weight" step="0.001" min="0.001" class="form-control js-cutting-actual-finished-input" value="{{ (float) data_get($plan, 'preview.finished_weight', 0) }}">
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="small text-muted mt-md-4 js-cutting-loss-preview"></div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thành phần còn lại</th>
                                            <th class="text-end" style="width:180px;">Kg thực tế</th>
                                        </tr>
                                    </thead>
                                    <tbody class="js-cutting-actual-components"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" name="defer_components" id="{{ $modalId }}-defer-components">
                            <label class="form-check-label fw-semibold" for="{{ $modalId }}-defer-components">
                                Nhập sau các thành phần còn lại
                            </label>
                            <div class="small text-muted">
                                Khi chọn, các thành phần phát sinh sẽ được gom vào phiếu yêu cầu nhập kho trên dashboard kho trong ngày.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary js-cutting-build-preview">
                            Tính lại
                        </button>
                        <button type="submit" class="btn btn-warning js-cutting-confirm d-none">
                            Xác nhận lấy hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function formatKg(value) {
                    const num = Number(value || 0);
                    return num.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 3 }) + ' kg';
                }

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function renderCuttingPreview(modal) {
                    const plan = JSON.parse(modal.dataset.plan || '{}');
                    const materialOptions = plan.material_options || plan.materials || [];
                    const materialsById = new Map(materialOptions.map((material) => [String(material.variant_id), material]));
                    const selectedRows = [];
                    const components = new Map();
                    let inputWeight = 0;
                    let removedWeight = 0;
                    let finishedWeight = 0;
                    let usesDirectTargetComponent = false;

                    modal.querySelectorAll('.js-cutting-material-qty').forEach((input) => {
                        const row = input.closest('tr');
                        const checked = row?.querySelector('.js-cutting-material-check')?.checked;
                        const qty = checked ? Math.max(0, Number(input.value || 0)) : 0;
                        const material = materialsById.get(String(input.dataset.variantId));

                        if (!material || qty <= 0) {
                            input.value = '0';
                            return;
                        }

                        inputWeight += qty * Number(material.unit_weight || 0);
                        selectedRows.push({
                            label: material.label || 'Nguyên con',
                            size: Number(material.size || 0),
                            quantity: qty,
                        });

                        const targetIds = new Set((material.target_component_ids || []).map((id) => String(id)));
                        const removedIds = new Set((material.removed_component_ids || []).map((id) => String(id)));
                        (material.components || []).forEach((component) => {
                            const componentWeight = qty * Number(component.standard_weight || 0);
                            if (componentWeight <= 0) {
                                return;
                            }

                            if (targetIds.size > 0) {
                                usesDirectTargetComponent = true;
                                if (targetIds.has(String(component.variant_id))) {
                                    finishedWeight += componentWeight;
                                    return;
                                }
                            } else if (!removedIds.has(String(component.variant_id))) {
                                return;
                            }

                            removedWeight += componentWeight;
                            const key = String(component.variant_id);
                            const current = components.get(key) || {
                                variant_id: component.variant_id,
                                name: component.name || 'Thành phần',
                                weight: 0,
                            };
                            current.weight += componentWeight;
                            components.set(key, current);
                        });
                    });

                    if (!usesDirectTargetComponent) {
                        finishedWeight = Math.max(0, inputWeight - removedWeight);
                    }
                    modal.querySelector('.js-cutting-actual-finished').value = finishedWeight.toFixed(3);
                    modal.dataset.inputWeight = inputWeight.toFixed(3);
                    modal.dataset.plannedFinishedWeight = finishedWeight.toFixed(3);

                    const hidden = modal.querySelector('.js-cutting-components-hidden');
                    hidden.innerHTML = '';

                    const actualFinishedInput = modal.querySelector('.js-cutting-actual-finished-input');
                    if (actualFinishedInput && !modal.classList.contains('is-cutting-in-progress')) {
                        actualFinishedInput.value = finishedWeight.toFixed(3);
                    }

                    const actualComponentsBody = modal.querySelector('.js-cutting-actual-components');
                    if (actualComponentsBody && !modal.classList.contains('is-cutting-in-progress')) {
                        actualComponentsBody.innerHTML = '';
                        if (components.size) {
                            Array.from(components.values()).forEach((component, index) => {
                                actualComponentsBody.insertAdjacentHTML('beforeend', `
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">${escapeHtml(component.name || 'Thành phần')}</div>
                                            <input type="hidden" name="components[${index}][variant_id]" value="${component.variant_id}">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="components[${index}][weight]" class="form-control text-end js-cutting-actual-component-weight" min="0" step="0.001" value="${component.weight.toFixed(3)}">
                                                <span class="input-group-text">kg</span>
                                            </div>
                                        </td>
                                    </tr>
                                `);
                            });
                        } else {
                            actualComponentsBody.innerHTML = '<tr><td colspan="2" class="text-muted text-center py-2">Không có thành phần còn lại.</td></tr>';
                        }
                    }

                    const sourceHtml = selectedRows.length
                        ? selectedRows.map((row) => `<li>${row.quantity} ${row.label} size ${formatKg(row.size)}</li>`).join('')
                        : '<li>Chưa chọn sản phẩm/biến thể.</li>';
                    const componentHtml = components.size
                        ? Array.from(components.values()).map((component) => `<li>${component.name}: ${formatKg(component.weight)}</li>`).join('')
                        : '<li>Không có thành phần phát sinh.</li>';
                    const requiredQty = Number(plan.required_qty || 0);
                    const availableQty = Number(plan.available_qty || 0);
                    const shortQty = Number(plan.short_qty || plan.demand || 0);
                    const finishedDelta = finishedWeight - shortQty;
                    const isEnough = finishedDelta >= 0;
                    const compareClass = isEnough ? 'text-success' : 'text-danger';
                    const compareLabel = finishedDelta >= 0
                        ? `Dư ${formatKg(finishedDelta)} so với lượng thiếu`
                        : `Thiếu thêm ${formatKg(Math.abs(finishedDelta))} so với lượng thiếu`;
                    const statusBadge = isEnough
                        ? '<span class="badge bg-success">Đủ bổ sung cho đơn</span>'
                        : '<span class="badge bg-danger">Chưa đủ so với đơn</span>';
                    const orderTargetRows = (plan.order_items || []).filter((item) => item.is_target);
                    const orderTargetHtml = orderTargetRows.length
                        ? orderTargetRows.map((item) => `<li>${item.name}: SL ${formatCompactNumber(item.quantity)}${item.total_label ? `, tổng ${item.total_label}` : ''}</li>`).join('')
                        : '<li>Không tìm thấy dòng sản phẩm cần bổ sung trong đơn.</li>';

                    const summary = modal.querySelector('.js-cutting-summary');
                    summary.classList.remove('d-none');
                    summary.innerHTML = `
                        <div class="fw-semibold mb-1">3. Thông tin sẽ thực hiện</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4"><span class="text-muted">Đơn cần:</span> <strong>${formatCompactNumber(requiredQty)}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Khả dụng:</span> <strong>${formatCompactNumber(availableQty)}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Thiếu:</span> <strong class="text-danger">${formatKg(shortQty)}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Thành phẩm dự kiến:</span> <strong>${formatKg(finishedWeight)}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Chênh lệch:</span> <strong class="${compareClass}">${finishedDelta >= 0 ? '+' : '-'}${formatKg(Math.abs(finishedDelta))}</strong></div>
                            <div class="col-md-4">${statusBadge}</div>
                        </div>
                        <div class="small mb-1">Dòng hàng trong đơn để đối chiếu:</div>
                        <ul class="mb-2">${orderTargetHtml}</ul>
                        <div>Lấy trong kho:</div>
                        <ul class="mb-2">${sourceHtml}</ul>
                        <div>Nhập kho thành phẩm: <strong>${plan.target_name || 'Hàng pha lóc'} ${formatKg(finishedWeight)}</strong></div>
                        <div class="${compareClass} fw-semibold mt-1">${compareLabel}</div>
                        <div class="mt-2">Nhập kho các thành phần:</div>
                        <ul class="mb-0">${componentHtml}</ul>
                    `;

                    const confirmButton = modal.querySelector('.js-cutting-confirm');
                    if (confirmButton) {
                        const canConfirm = selectedRows.length > 0 && finishedWeight > 0;
                        confirmButton.classList.toggle('d-none', !canConfirm);
                        confirmButton.disabled = !canConfirm;
                    }

                    updateCuttingLossPreview(modal);
                }

                function formatCompactNumber(value) {
                    const num = Number(value || 0);
                    return num.toLocaleString('vi-VN', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 3,
                    });
                }

                function cuttingNumber(value) {
                    const parsed = Number(String(value ?? '').replace(',', '.'));
                    return Number.isFinite(parsed) ? parsed : 0;
                }

                function actualComponentWeight(modal) {
                    return Array.from(modal.querySelectorAll('.js-cutting-actual-component-weight'))
                        .reduce((sum, input) => sum + cuttingNumber(input.value), 0);
                }

                function updateCuttingLossPreview(modal) {
                    const inputWeight = cuttingNumber(modal.dataset.inputWeight);
                    const finishedWeight = cuttingNumber(modal.querySelector('.js-cutting-actual-finished-input')?.value);
                    const componentWeight = actualComponentWeight(modal);
                    const lossWeight = Math.max(0, inputWeight - finishedWeight - componentWeight);
                    const lossPercent = inputWeight > 0 ? lossWeight / inputWeight * 100 : 0;
                    const preview = modal.querySelector('.js-cutting-loss-preview');
                    if (preview) {
                        preview.innerHTML = `
                            Tổng nguyên liệu ${formatKg(inputWeight)} · đầu ra thực tế ${formatKg(finishedWeight + componentWeight)}
                            · hao hụt <strong class="${lossWeight > 0 ? 'text-danger' : 'text-success'}">${formatKg(lossWeight)} (${formatCompactNumber(lossPercent)}%)</strong>
                        `;
                    }
                }

                document.querySelectorAll('.js-cutting-modal').forEach((modal) => {
                    const refreshCuttingPreview = () => renderCuttingPreview(modal);
                    const plan = JSON.parse(modal.dataset.plan || '{}');
                    const materialOptions = plan.material_options || plan.materials || [];
                    const materialsById = new Map(materialOptions.map((material) => [String(material.variant_id), material]));

                    function bindMaterialRow(row) {
                        if (!row || row.dataset.bound === '1') {
                            return;
                        }
                        row.dataset.bound = '1';

                        const checkbox = row.querySelector('.js-cutting-material-check');
                        if (checkbox) {
                            checkbox.addEventListener('change', function () {
                                const qtyInput = checkbox.closest('tr')?.querySelector('.js-cutting-material-qty');
                                if (!qtyInput) return;
                                if (checkbox.checked && Number(qtyInput.value || 0) <= 0) qtyInput.value = '1';
                                if (!checkbox.checked) qtyInput.value = '0';
                                refreshCuttingPreview();
                            });
                        }

                        const input = row.querySelector('.js-cutting-material-qty');
                        if (input) {
                            const syncQty = function () {
                                const rowCheckbox = input.closest('tr')?.querySelector('.js-cutting-material-check');
                                if (rowCheckbox) rowCheckbox.checked = Number(input.value || 0) > 0;
                                refreshCuttingPreview();
                            };

                            input.addEventListener('input', syncQty);
                            input.addEventListener('change', syncQty);
                        }

                        row.querySelector('.js-cutting-material-remove')?.addEventListener('click', function () {
                            row.remove();
                            refreshCuttingPreview();
                        });
                    }

                    function materialRowHtml(material) {
                        const rowIndex = Date.now() + '-' + String(material.variant_id || 0);
                        return `
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input js-cutting-material-check" checked>
                                </td>
                                <td>
                                    <input type="hidden" name="materials[${rowIndex}][variant_id]" value="${material.variant_id}">
                                    <div class="fw-semibold">${escapeHtml(material.label || 'Nguyên con')}</div>
                                </td>
                                <td class="text-end">${formatKg(material.size || 0)}</td>
                                <td class="text-end">${formatCompactNumber(material.available || 0)}</td>
                                <td class="text-end">${formatKg(material.output_per_unit || 0)}</td>
                                <td>
                                    <input type="number"
                                           name="materials[${rowIndex}][quantity]"
                                           class="form-control form-control-sm js-cutting-material-qty"
                                           min="0"
                                           max="${Number(material.available || 0)}"
                                           step="1"
                                           value="1"
                                           data-variant-id="${material.variant_id}">
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger js-cutting-material-remove" title="Xóa biến thể khỏi danh sách">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }

                    modal.querySelectorAll('.js-cutting-material-body tr').forEach(bindMaterialRow);

                    modal.addEventListener('input', function (event) {
                        if (event.target.matches('.js-cutting-actual-finished-input, .js-cutting-actual-component-weight')) {
                            updateCuttingLossPreview(modal);
                        }
                    });

                    modal.querySelector('.js-cutting-material-add')?.addEventListener('click', function () {
                        const select = modal.querySelector('.js-cutting-material-select');
                        const variantId = String(select?.value || '');
                        if (!variantId) {
                            return;
                        }

                        const existingInput = Array.from(modal.querySelectorAll('.js-cutting-material-qty'))
                            .find((input) => String(input.dataset.variantId || '') === variantId);
                        if (existingInput) {
                            existingInput.value = Math.max(1, Number(existingInput.value || 0));
                            const existingCheckbox = existingInput.closest('tr')?.querySelector('.js-cutting-material-check');
                            if (existingCheckbox) existingCheckbox.checked = true;
                            refreshCuttingPreview();
                            select.value = '';
                            return;
                        }

                        const material = materialsById.get(variantId);
                        const body = modal.querySelector('.js-cutting-material-body');
                        if (!material || !body) {
                            return;
                        }

                        body.querySelector('td[colspan]')?.closest('tr')?.remove();
                        body.insertAdjacentHTML('beforeend', materialRowHtml(material));
                        bindMaterialRow(body.lastElementChild);
                        refreshCuttingPreview();
                        select.value = '';
                    });

                    modal.querySelector('.js-cutting-build-preview')?.addEventListener('click', function () {
                        refreshCuttingPreview();
                    });

                    modal.addEventListener('shown.bs.modal', function () {
                        refreshCuttingPreview();
                    });
                });
            });
        </script>
    @endpush
@endonce
