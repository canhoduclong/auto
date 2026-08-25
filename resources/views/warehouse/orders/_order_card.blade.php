            @php
                $orderCardReadonly = (bool) ($orderCardReadonly ?? false);
                $isTodaySelected = \Illuminate\Support\Carbon::parse($selectedDate ?? now()->toDateString())->isToday();
                $canProcessThisOrder = !$orderCardReadonly && (
                    $order->accounting_sales_import_batch_id !== null
                    || (bool) $order->skip_auto_cancel
                    || $order->hasCompletedAdjustment()
                    || ($isTodaySelected && $order->created_at->isToday())
                );
                $meta = $statusMeta[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary'];
                $isReadyToPack = in_array($order->status, ['approved', 'ready_to_pack'], true);
                $isPacking = $order->status === 'packing';
                $isPackedReadonly = in_array($order->status, ['packed', 'packed_waiting_pickup', 'delivering', 'delivered', 'completed'], true);
                $canAdminReopenPacking = !$orderCardReadonly && auth()->user()?->hasRole('admin') && in_array($order->status, ['packed', 'packed_waiting_pickup'], true);
                $activePackingHistory = $order->histories
                    ?->where('action', 'start_packing')
                    ->sortByDesc('id')
                    ->first();
                $canUndoStartPacking = $isPacking
                    && (int) ($activePackingHistory?->user_id ?? 0) === (int) auth()->id();
                $packingHistory = $order->histories
                    ?->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                    ->sortByDesc('id')
                    ->first();
                $sourceWarehouseName = $order->warehouse?->name ?: $packingHistory?->user?->warehouse?->name;
                $packedByName = $packingHistory?->user?->name;
                $packedAt = $packingHistory?->created_at?->format('d/m/Y H:i');
                $stockGuard = $order->stock_guard ?? [];
                $hasStockShortage = (bool) ($stockGuard['has_shortage'] ?? false);
                $canStartPacking = (bool) ($stockGuard['can_start_packing'] ?? true);
                $stockShortages = collect($stockGuard['shortages'] ?? []);
                $orderCuttingPlans = collect($cuttingPlansByOrder[$order->id] ?? []);
                $activeCuttingBatches = collect($activeCuttingBatchesByOrder[$order->id] ?? []);
                $hasActiveCuttingBatch = $activeCuttingBatches->isNotEmpty();
                $isPackageOrderLayout = ($orderRoutePrefix ?? 'warehouse') === 'package';
                $isPendingSaleConfirmation = $order->warehouse_adjustment_status === \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION;
                $isConfirmedBySale = $order->warehouse_adjustment_status === \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED;
                $isRejectedBySale = $order->warehouse_adjustment_status === \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED;
                $warehouseCanAdjust = (bool) ($order->warehouse_can_adjust ?? false);
                $adjustmentChanges = collect($order->warehouse_adjustment_changes ?? []);
                $activeTransfer = $activeTransfersByOrder[$order->id] ?? null;
                $shipPickupWarehouseName = $sourceWarehouseName;
                $shipPickupWarehouseHint = null;
                $customerFeedbackContext = $order->getAttribute('customer_feedback_context') ?? [];
                $customerFeedbackMeta = $customerFeedbackContext['highest_meta'] ?? \App\Models\Order::customerFeedbackMeta(null);
                $customerFeedbackRows = collect($customerFeedbackContext['recent'] ?? []);
                $hasCustomerFeedback = (bool) ($customerFeedbackContext['has_feedback'] ?? false);

                if ($activeTransfer?->targetWarehouse?->name) {
                    $shipPickupWarehouseName = $activeTransfer->targetWarehouse->name;
                    $shipPickupWarehouseHint = match ($activeTransfer->status) {
                        'pending_shipper_pickup' => 'Đang điều chuyển sang kho nhận',
                        'in_transit' => 'Đang vận chuyển sang kho nhận',
                        'delivered_waiting_receive' => 'Chờ kho nhận tiếp nhận trước khi ship lấy',
                        default => null,
                    };
                }
            @endphp
            <div class="col-12" id="order-card-{{ $order->id }}">
                <div class="wh-order-card-grid {{ $hasCustomerFeedback ? 'has-feedback' : 'no-feedback' }}">
                <div class="wh-order-main">
                <div class="card wh-order-card js-order-card {{ $hasActiveCuttingBatch ? 'has-cutting-in-progress' : '' }}" data-order-id="{{ $order->id }}">
                    <div class="d-flex align-items-center card-header bg-white">
                        @php
                            $isPacked = in_array($order->status, ['packed', 'packed_waiting_pickup', 'delivering', 'delivered', 'completed'], true);
                            $orderIndexClass = $isPacking ? 'is-packing' : ($isPacked ? 'is-packed' : 'is-unpacked');
                        @endphp
                        <div class="wh-order-index {{ $orderIndexClass }} text-center">{{ $order->daily_sequence ?? '—' }}</div>
                        <div class=" border-0 w-100  d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold fs-5 mb-0 pb-0">{{ $order->customer?->name ?? '—' }} </div>
                                <div class="text-muted card-desript">
                                    #{{ $order->daily_sequence ?? '—' }}, lên đơn {{ $order->created_at->format('d/m/Y H:i') }},
                                    giao {{ optional($order->delivery_date)->format('d/m/Y') ?: 'chưa cập nhật' }}, {{ $order->code }}
                                </div>
                            </div> 
                            <div class="d-flex align-items-center gap-2">
                                @if($warehouseCanAdjust)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Kho được phép trực tiếp điều chỉnh đơn">
                                        <i class="bi bi-pencil-square me-1"></i>Kho được sửa
                                    </span>
                                @endif
                                <span class="badge {{ $meta['class'] }} js-order-status">{{ $meta['label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if($hasActiveCuttingBatch)
                            <div class="alert wh-cutting-progress-alert py-2 px-3 mb-2">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="bi bi-scissors me-1"></i>Đã xác nhận lấy hàng pha lóc
                                        </div>
                                        <div class="small text-muted">Đơn đang chờ bộ phận đóng hàng hoàn thiện kg thực tế và nhập kho.</div>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @foreach($activeCuttingBatches as $batch)
                                            @php
                                                $batchModalId = 'complete-cutting-batch-' . (int) $batch->id;
                                            @endphp
                                            @if($isPackageOrderLayout)
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#{{ $batchModalId }}">
                                                    <i class="bi bi-play-fill me-1"></i>Thực hiện
                                                </button>
                                            @else
                                                <form method="POST"
                                                      action="{{ route('warehouse.cutting-batches.revert', $batch) }}"
                                                      onsubmit="return confirm('Quay lại xác nhận lấy hàng pha lóc và hoàn nguyên tồn nguyên liệu?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Quay lại
                                                    </button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @if($isPackageOrderLayout)
                                    <div class="mt-3">
                                        @foreach($activeCuttingBatches as $batch)
                                            @php
                                                $sourceItems = collect($batch->exportDocument?->items ?? []);
                                                $verifications = collect($batch->picked_material_verifications ?? [])->keyBy(fn ($row) => (int) ($row['variant_id'] ?? 0));
                                                $targetName = trim(($batch->targetVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($batch->targetVariant?->name ?: ''));
                                            @endphp
                                            <div class="small fw-semibold mb-2">Kho đã lấy cho: {{ $targetName }}</div>
                                            <div class="wh-picked-material-list">
                                                @forelse($sourceItems as $sourceItem)
                                                    @php
                                                        $sourceVariant = $sourceItem->productVariant;
                                                        $sourceVariantId = (int) ($sourceItem->product_variant_id ?? 0);
                                                        $sourceName = trim(($sourceVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($sourceVariant?->name ?: ''));
                                                        $pickedVerification = $verifications->get($sourceVariantId);
                                                        $isPickedVerified = !empty($pickedVerification);
                                                    @endphp
                                                    <div class="wh-picked-material-row {{ $isPickedVerified ? 'is-verified' : '' }}"
                                                         data-picked-material-row
                                                         data-batch-id="{{ (int) $batch->id }}"
                                                         data-variant-id="{{ $sourceVariantId }}"
                                                         data-picked-url="{{ route('package.cutting-batches.materials.picked', ['batch' => $batch, 'variant' => $sourceVariantId]) }}"
                                                         data-unpicked-url="{{ route('package.cutting-batches.materials.unpicked', ['batch' => $batch, 'variant' => $sourceVariantId]) }}">
                                                        <div>
                                                            <div class="fw-semibold">{{ $sourceName }}</div>
                                                            <div class="wh-picked-material-meta" data-picked-material-meta-base="Kho xuất {{ rtrim(rtrim(number_format((float) $sourceItem->quantity, 3, '.', ''), '0'), '.') }} con{{ $sourceVariant?->sku ? ' · ' . $sourceVariant->sku : '' }}">
                                                                Kho xuất {{ rtrim(rtrim(number_format((float) $sourceItem->quantity, 3, '.', ''), '0'), '.') }} con{{ $sourceVariant?->sku ? ' · ' . $sourceVariant->sku : '' }}
                                                                <span data-picked-material-verify-text>{{ $isPickedVerified ? ' · Verify bởi ' . ($pickedVerification['verified_by_name'] ?? 'Package') : '' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="wh-picked-material-actions">
                                                            <span class="badge wh-picked-material-badge {{ $isPickedVerified ? '' : 'd-none' }}" data-picked-material-badge>
                                                                <i class="bi bi-check2-circle me-1"></i>Đã lấy
                                                            </span>
                                                            <button type="button" class="btn btn-sm btn-success js-picked-material-action {{ $isPickedVerified ? 'd-none' : '' }}" data-picked-action="pick">
                                                                <i class="bi bi-check2-circle me-1"></i>Đã lấy
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger js-picked-material-action {{ $isPickedVerified ? '' : 'd-none' }}" data-picked-action="unpick">
                                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Quay lại
                                                            </button>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="small text-muted">Chưa có dữ liệu nguyên liệu kho đã xuất.</div>
                                                @endforelse
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div class="wh-section">
                            @if($isConfirmedBySale)
                                <div class="alert alert-success py-2 px-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                        <div>
                                            <div class="fw-semibold">Sale đã xác nhận và áp dụng thay đổi</div>
                                            <div class="small">Phản hồi bởi: {{ $order->warehouseAdjustmentConfirmer?->name ?? $order->user?->name ?? 'Sale' }}</div>
                                        </div>
                                        @if($order->warehouse_adjustment_confirmed_at)
                                            <span class="small text-muted">{{ $order->warehouse_adjustment_confirmed_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </div>
                                    <div class="small mt-2"><strong>Yêu cầu từ Package:</strong> {{ $order->warehouse_adjustment_note ?: 'Chưa cập nhật nội dung' }}</div>
                                    <div class="small text-muted mt-1">
                                        Gửi bởi {{ $order->warehouseAdjustmentRequester?->name ?? 'Nhân viên Package' }}
                                        @if($order->warehouse_adjustment_requested_at)
                                            lúc {{ $order->warehouse_adjustment_requested_at->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                    @if($adjustmentChanges->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @foreach($adjustmentChanges as $change)
                                                <span class="badge bg-white text-success border border-success-subtle">
                                                    {{ $change['product_name'] ?? 'Sản phẩm' }}:
                                                    {{ (int) ($change['old_quantity'] ?? 0) }} → {{ (int) ($change['new_quantity'] ?? 0) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($isRejectedBySale)
                                <div class="alert alert-danger py-2 px-3 mb-2">
                                    <div class="fw-semibold mb-1">Sale đã từ chối yêu cầu điều chỉnh - cần xử lý lại</div>
                                    <div class="small mb-1">Yêu cầu từ Package: {{ $order->warehouse_adjustment_note ?: 'Chưa cập nhật nội dung' }}</div>
                                    <div class="small text-muted mb-1">
                                        Gửi bởi {{ $order->warehouseAdjustmentRequester?->name ?? 'Nhân viên Package' }}
                                        @if($order->warehouse_adjustment_requested_at)
                                            lúc {{ $order->warehouse_adjustment_requested_at->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                    <div class="small mb-1">Phản hồi bởi: {{ $order->warehouseAdjustmentRejecter?->name ?? $order->user?->name ?? 'Sale' }}</div>
                                    <div class="small mb-1">Lý do: {{ $order->warehouse_adjustment_rejected_reason ?: 'Chưa cập nhật' }}</div>
                                    @if($order->warehouse_adjustment_rejected_at)
                                        <div class="small text-muted">Từ chối lúc: {{ $order->warehouse_adjustment_rejected_at->format('d/m/Y H:i') }}</div>
                                    @endif

                                    @if($adjustmentChanges->isNotEmpty())
                                        @php
                                            $changeByVariantId = $adjustmentChanges
                                                ->keyBy(fn ($change) => (int) ($change['product_variant_id'] ?? 0));

                                            $currentItemsByVariantId = $order->items
                                                ->mapWithKeys(function ($item) {
                                                    $variantId = (int) ($item->product_variant_id ?? 0);
                                                    if ($variantId <= 0) {
                                                        return [];
                                                    }

                                                    return [
                                                        $variantId => [
                                                            'product_name' => $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm',
                                                            'sku' => $item->variant?->sku,
                                                            'size' => $item->variant?->size,
                                                            'quantity' => (int) ($item->quantity ?? 0),
                                                        ],
                                                    ];
                                                });

                                            $oldSaleState = $currentItemsByVariantId
                                                ->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0);

                                            $newAdjustedState = $oldSaleState->map(function ($item) {
                                                return [
                                                    'product_name' => $item['product_name'] ?? 'Sản phẩm',
                                                    'sku' => $item['sku'] ?? null,
                                                    'size' => $item['size'] ?? null,
                                                    'quantity' => (int) ($item['quantity'] ?? 0),
                                                ];
                                            });

                                            foreach ($adjustmentChanges as $change) {
                                                $variantId = (int) ($change['product_variant_id'] ?? 0);
                                                if ($variantId <= 0) {
                                                    continue;
                                                }

                                                $newQty = (int) ($change['new_quantity'] ?? 0);
                                                if ($newQty <= 0) {
                                                    $newAdjustedState->forget($variantId);
                                                    continue;
                                                }

                                                $current = $newAdjustedState->get($variantId, []);
                                                $newAdjustedState->put($variantId, [
                                                    'product_name' => $change['product_name'] ?? ($current['product_name'] ?? 'Sản phẩm'),
                                                    'sku' => $change['sku'] ?? ($current['sku'] ?? null),
                                                    'size' => $change['size'] ?? ($current['size'] ?? null),
                                                    'quantity' => $newQty,
                                                ]);
                                            }
                                        @endphp

                                        <div class="row g-2 mt-1">
                                            <div class="col-12 col-md-6">
                                                <div class="small fw-semibold text-dark mb-1">Hiện trạng cũ của sale</div>
                                                <div class="border rounded p-2 bg-white">
                                                    @forelse($oldSaleState as $stateItem)
                                                        @php
                                                            $oldSize = $stateItem['size'] ?? null;
                                                            $oldSizeLabel = (is_numeric($oldSize) && (float) $oldSize > 0)
                                                                ? rtrim(rtrim(number_format((float) $oldSize, 2, '.', ''), '0'), '.')
                                                                : null;
                                                        @endphp
                                                        <div class="small {{ $loop->last ? '' : 'mb-1 pb-1 border-bottom' }}">
                                                            <div class="fw-semibold">{{ $stateItem['product_name'] ?? 'Sản phẩm' }}</div>
                                                            <div class="text-muted">
                                                                SKU: {{ $stateItem['sku'] ?: '---' }}
                                                                @if($oldSizeLabel)
                                                                    | Size: {{ $oldSizeLabel }}
                                                                @endif
                                                                | SL: {{ (int) ($stateItem['quantity'] ?? 0) }}
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="small text-muted">Không có dữ liệu hiện trạng cũ.</div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="small fw-semibold text-dark mb-1">Hiện trạng sau chỉnh (đã bị từ chối)</div>
                                                <div class="border rounded p-2 bg-white">
                                                    @forelse($newAdjustedState as $stateItem)
                                                        @php
                                                            $newSize = $stateItem['size'] ?? null;
                                                            $newSizeLabel = (is_numeric($newSize) && (float) $newSize > 0)
                                                                ? rtrim(rtrim(number_format((float) $newSize, 2, '.', ''), '0'), '.')
                                                                : null;
                                                        @endphp
                                                        <div class="small {{ $loop->last ? '' : 'mb-1 pb-1 border-bottom' }}">
                                                            <div class="fw-semibold">{{ $stateItem['product_name'] ?? 'Sản phẩm' }}</div>
                                                            <div class="text-muted">
                                                                SKU: {{ $stateItem['sku'] ?: '---' }}
                                                                @if($newSizeLabel)
                                                                    | Size: {{ $newSizeLabel }}
                                                                @endif
                                                                | SL: {{ (int) ($stateItem['quantity'] ?? 0) }}
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="small text-muted">Không có dữ liệu hiện trạng mới.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($isPendingSaleConfirmation)
                                <div class="alert alert-warning py-2 px-3 mb-2">
                                    <div class="fw-semibold mb-1">Đang chờ sale xác nhận thay đổi đơn</div>
                                    <div class="small mb-1">Lý do: {{ $order->warehouse_adjustment_note ?: 'Chưa cập nhật' }}</div>
                                    @if($order->warehouse_adjustment_requested_at)
                                        <div class="small text-muted">Gửi lúc: {{ $order->warehouse_adjustment_requested_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                    @if($adjustmentChanges->isNotEmpty())
                                        <div class="d-grid gap-2 mt-2">
                                            @foreach($adjustmentChanges as $change)
                                                @php
                                                    $sizeValue = $change['size'] ?? null;
                                                    $formattedSize = (is_numeric($sizeValue) && (float) $sizeValue > 0)
                                                        ? rtrim(rtrim(number_format((float) $sizeValue, 2, '.', ''), '0'), '.')
                                                        : null;
                                                @endphp
                                                <div class="wh-adjustment-pending-item">
                                                    <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">{{ $change['product_name'] ?? 'Sản phẩm' }}</div>
                                                            <div class="small text-muted">
                                                                SKU: {{ $change['sku'] ?: '---' }}
                                                                @if($formattedSize)
                                                                    | Size: {{ $formattedSize }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div style="min-width: 170px;" class="text-md-end">
                                                            <label class="form-label small mb-1">Số lượng thay đổi</label>
                                                            <div class="form-control form-control-sm bg-light text-center fw-semibold">
                                                                {{ (int) ($change['old_quantity'] ?? 0) }} -> {{ (int) ($change['new_quantity'] ?? 0) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                             
                            <div class="small text-muted mb-1">
                                <i class="bi bi-geo-alt me-1"></i>
                                {{ $order->customer?->address ?: 'Chưa có địa chỉ' }}
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Giờ giao: {{ $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật') }}
                            </div>
                            @if($isPackedReadonly)
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-box-seam me-1"></i>
                                    Từ kho: {{ $sourceWarehouseName ?: 'Chưa xác định' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-truck me-1"></i>
                                    Kho ship sẽ lấy: {{ $shipPickupWarehouseName ?: 'Chưa xác định' }}
                                </div>
                            @endif
                        </div>

                        <div class="wh-section pb-0"> 
                            <div class="wh-item-table-wrap mt-2">
                                <div class="wh-item-table-head">
                                    <div>Ảnh</div>
                                    <div>Sản phẩm</div>
                                    <div class="text-center">Size</div>
                                    <div class="text-center">SL</div>                                    
                                    <div class="text-center">Tổng</div>
                                    <div class="text-center">Khối lượng</div>
                                    <div class="text-center">Đơn giá</div>
                                    <div class="text-end">Thành tiền</div>
                                    
                                </div>
                                <ul class="wh-item-list">
                                    @foreach($order->items as $item)
                                        @php

                                            $variant = $item->variant;
                                            $orderedQty = (int) $item->quantity;
                                            $unitPrice = (float) ($item->price ?? 0);
                                            $unitLabel = $variant?->product?->unit_label ?? '--'; 
                                            $pricedByKg = (bool) $item->effective_priced_by_kg;
                                            $weightUnitLabel = $pricedByKg ? 'Kg' : $unitLabel;
                                            $itemActualWeight = is_null($item->actual_weight) ? null : (float) $item->actual_weight;
                                            $lineTotal = $pricedByKg
                                                ? (!is_null($itemActualWeight) ? ($itemActualWeight * $unitPrice) : null)
                                                : ($orderedQty * $unitPrice);
                                            $variantSize = $variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? $formatCompactDecimal((float) $variantSize)
                                                : '-';
                                            if ($pricedByKg) {
                                                $displayActualWeight = (!is_null($itemActualWeight) && (float) $itemActualWeight > 0)
                                                    ? $formatKg((float) $itemActualWeight)
                                                    : '---';
                                            } else {
                                                $nonKgVal = (!is_null($itemActualWeight) && (float) $itemActualWeight > 0)
                                                    ? (float) $itemActualWeight
                                                    : round((float) $item->effective_unit_weight * $orderedQty, 3);
                                                $displayActualWeight = $formatCompactDecimal($nonKgVal) . ' ' . $unitLabel;
                                            }
                                            $imagePath = $variant?->avatar?->media?->file_path
                                                ?? $item->product?->avatar?->media?->file_path
                                                ?? null;
                                        @endphp
                                        <li class="wh-item-row">
                                            <div class="wh-item-table-row" data-unit-price="{{ number_format($unitPrice, 2, '.', '') }}" data-weight-unit="{{ $weightUnitLabel }}">
                                                <div>
                                                    @if($imagePath)
                                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}">
                                                    @else
                                                        <span class="wh-item-thumb-placeholder">
                                                            <i class="bi bi-image"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-name">
                                                    {{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                                    @if($variant?->sku)
                                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ number_format($orderedQty) }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                                
                                               
                                                @if(!$isPackedReadonly && $canProcessThisOrder)
                                                    @php
                                                        $defaultComputedWeight = round((float) $item->effective_unit_weight * $orderedQty, 3);
                                                        $itemWeightDefault = is_null($item->actual_weight)
                                                            ? ($defaultComputedWeight > 0 ? number_format($defaultComputedWeight, 3, '.', '') : '')
                                                            : number_format((float) $item->actual_weight, 3, '.', '');
                                                    @endphp
                                                    @if($pricedByKg)
                                                        @php
                                                            $isItemLogisticsSaved = !is_null($lineTotal);
                                                        @endphp
                                                        <div class="wh-item-action js-packing-only {{ $isPacking ? '' : 'd-none' }}">
                                                            <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.logistics', $order) }}" method="POST" class="js-logistics-item-form wh-compact-form justify-content-end">
                                                                @csrf
                                                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                                <input type="number" name="item_actual_weight" class="form-control form-control-sm actual_weight js-weight-input"
                                                                    value="{{ $itemWeightDefault }}"
                                                                    placeholder="{{ $weightUnitLabel }}"
                                                                    min="0" step="0.001" required
                                                                    inputmode="decimal"
                                                                    data-qty="{{ $orderedQty }}"
                                                                    data-size="{{ is_numeric($variantSize) && (float)$variantSize > 0 ? (float)$variantSize : 0 }}">
                                                                <button class="btn btn-sm {{ $isItemLogisticsSaved ? 'btn-secondary' : 'wh-warning-action-btn' }} js-logistics-submit-btn" type="submit">Lưu</button>
                                                            </form>
                                                        </div>
                                                        <div class="wh-readonly-item js-ready-only {{ $isPacking ? 'd-none' : '' }}">{{ $displayActualWeight }}</div>
                                                    @else
                                                        <div class="wh-readonly-item">{{ $displayActualWeight }}</div>
                                                    @endif
                                                @else
                                                    <div class="wh-readonly-item js-item-readonly-kg">
                                                        {{ $displayActualWeight }}
                                                    </div>
                                                @endif
                                           
                                                <div class="wh-item-cell">{{ number_format($unitPrice) }}đ</div>
                                                <div class="wh-item-cell js-item-total-amount">
                                                    <strong>{{ !is_null($lineTotal) ? number_format($lineTotal) . 'đ' : ($pricedByKg ? '---' : number_format($orderedQty * $unitPrice) . 'đ') }}</strong>
                                                </div>
                                             </div>
                                             <div class="js-weight-error text-danger text-center px-1" style="font-size:.72rem;display:none;"></div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div> 

                        </div>
                    </div>

                    <div class="card-footer bg-white border-top py-2">
                        @if($isPackedReadonly)
                            <div class="wh-section border-top-0 pt-0 mb-2">
                                <div class="wh-logistics-title">Thông tin đơn hàng hoàn chỉnh</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="wh-meta-label">Kg thực tế</div>
                                        <div class="wh-meta-value text-primary">{{ $order->actual_weight !== null ? $formatKg((float) $order->actual_weight) : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Trạng thái</div>
                                        <div class="wh-meta-value">Đã khóa chỉnh sửa</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Từ kho</div>
                                        <div class="wh-meta-value">{{ $sourceWarehouseName ?: 'Chưa xác định' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Kho ship sẽ lấy</div>
                                        <div class="wh-meta-value">{{ $shipPickupWarehouseName ?: 'Chưa xác định' }}</div>
                                        @if($shipPickupWarehouseHint)
                                            <div class="small text-muted">{{ $shipPickupWarehouseHint }}</div>
                                        @endif
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Nhân viên kho</div>
                                        <div class="wh-meta-value">{{ $packedByName ?: 'Chưa xác định' }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="wh-meta-label">Thời điểm đóng gói</div>
                                        <div class="wh-meta-value">{{ $packedAt ?: 'Chưa có dữ liệu' }}</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="wh-meta-label">Điều chuyển kho</div>
                                        @if($activeTransfer)
                                            @php
                                                $transferBadgeClass = match($activeTransfer->status) {
                                                    'pending_shipper_pickup' => 'bg-secondary',
                                                    'in_transit' => 'bg-warning text-dark',
                                                    'delivered_waiting_receive' => 'bg-info text-dark',
                                                    default => 'bg-success',
                                                };
                                                $transferStatusLabel = match($activeTransfer->status) {
                                                    'pending_shipper_pickup' => 'Chờ shipper nhận hàng',
                                                    'in_transit' => 'Đang vận chuyển',
                                                    'delivered_waiting_receive' => 'Đã giao kho nhận, chờ tiếp nhận',
                                                    default => 'Đã hoàn tất',
                                                };
                                            @endphp
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <span class="badge {{ $transferBadgeClass }}">{{ $transferStatusLabel }}</span>
                                                <span class="small text-muted">Kho nhận: {{ $activeTransfer->targetWarehouse?->name ?? '—' }}</span>
                                                <span class="small text-muted">Shipper: {{ $activeTransfer->shipper?->name ?? '—' }}</span>
                                            </div>
                                        @elseif(!$orderCardReadonly)
                                            @php
                                                $sourceWarehouseId = (int) ($order->warehouse_id ?? 0);
                                                $targetWarehouses = collect($warehouses ?? [])->filter(function ($warehouse) use ($sourceWarehouseId) {
                                                    return (int) $warehouse->id !== $sourceWarehouseId;
                                                });
                                            @endphp
                                            <details class="border rounded p-2 bg-light-subtle">
                                                <summary class="fw-semibold">Tạo điều chuyển kho cho shipper</summary>
                                                <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.transfer-request', $order) }}" method="POST" class="mt-2">
                                                    @csrf
                                                    <div class="row g-2">
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label small mb-1">Kho nhận</label>
                                                            <select name="target_warehouse_id" class="form-select form-select-sm" required>
                                                                <option value="">Chọn kho nhận</option>
                                                                @foreach($targetWarehouses as $warehouse)
                                                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label small mb-1">Shipper vận chuyển</label>
                                                            <select name="shipper_id" class="form-select form-select-sm" required>
                                                                <option value="">Chọn shipper</option>
                                                                @foreach($shippers ?? [] as $shipper)
                                                                    <option value="{{ $shipper->id }}">{{ $shipper->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small mb-1">Ghi chú</label>
                                                            <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú điều chuyển (nếu có)"></textarea>
                                                        </div>
                                                        <div class="col-12 d-grid">
                                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-arrow-left-right me-1"></i>Tạo phiếu điều chuyển
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </details>
                                        @else
                                            <div class="small text-muted">Không có điều chuyển kho đang hoạt động.</div>
                                        @endif
                                    </div>
                                    @if($canAdminReopenPacking)
                                        <div class="col-12">
                                            <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.reopen-packing', $order) }}" method="POST" class="d-grid">
                                                @csrf
                                                <button class="btn btn-outline-warning btn-sm" type="submit">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Admin bỏ khóa chỉnh sửa
                                                </button>
                                            </form>
                                            <div class="small text-muted mt-1">
                                                Đưa đơn quay lại bước đang đóng gói để warehouse chỉnh sửa lại dữ liệu.
                                            </div>
                                        </div>
                                    @endif
                                    
                                </div>
                            </div>
                        @endif

                        @if($canProcessThisOrder && ($isReadyToPack || $isPacking))
                            @if($isReadyToPack && !$isPendingSaleConfirmation && $stockShortages->isNotEmpty())
                                <div class="wh-stock-alert mt-2">
                                    <details open>
                                        <summary>Chi tiết thiếu hàng ({{ $stockShortages->count() }} sản phẩm)</summary>
                                        <ul>
                                            @foreach($stockShortages as $shortage)
                                                @php
                                                    $cuttingPlan = $orderCuttingPlans->get((int) ($shortage['variant_id'] ?? 0));
                                                    $cuttingModalId = $cuttingPlan ? 'cutting-order-modal-' . $order->id . '-' . (int) ($shortage['variant_id'] ?? 0) : null;
                                                @endphp
                                                <li>
                                                    <strong>{{ $shortage['variant_name'] ?? 'Sản phẩm' }}</strong>:
                                                    cần {{ number_format((float)($shortage['required_qty'] ?? 0), 0) }},
                                                    còn {{ number_format((float)($shortage['available_qty'] ?? 0), 0) }}
                                                    @php $shortQty = (float)($shortage['short_qty'] ?? 0); @endphp
                                                    @if($shortQty > 0)
                                                        <span class="text-danger">(thiếu {{ number_format($shortQty, 0) }})</span>
                                                    @endif
                                                    @if(($shortage['reason'] ?? '') === 'blocked_by_prior_order')
                                                        <span class="text-warning">- bị chặn bởi đơn ưu tiên trước</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </div>
                            @endif

                            <div class="wh-order-actions mt-3">
                                @if(!$isPackedReadonly && $canProcessThisOrder && !$isPacking)
                                    <details class="wh-footer-adjustment">
                                        <summary>
                                            <i class="bi bi-pencil-square me-1"></i>
                                            {{ $warehouseCanAdjust ? 'Yêu cầu Điều chỉnh' : 'Yêu cầu Điều chỉnh' }}
                                        </summary>
                                        <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.request-adjustment', $order) }}" method="POST" class="mt-2">
                                            @csrf
                                            <div class="small text-muted mb-2">Đặt số lượng = 0 để xóa sản phẩm khỏi đơn.</div>
                                            <div class="d-grid gap-2 mb-2">
                                                @foreach($order->items as $item)
                                                    @php
                                                        $adjustmentSize = $item->variant?->size;
                                                        $formattedAdjustmentSize = (is_numeric($adjustmentSize) && (float) $adjustmentSize > 0)
                                                            ? rtrim(rtrim(number_format((float) $adjustmentSize, 2, '.', ''), '0'), '.')
                                                            : null;
                                                    @endphp
                                                    <div class="wh-adjustment-pending-item">
                                                        <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold">{{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}</div>
                                                                <div class="small text-muted">
                                                                    SKU: {{ $item->variant?->sku ?: '---' }}
                                                                    @if($formattedAdjustmentSize)
                                                                        | Size: {{ $formattedAdjustmentSize }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-end gap-2">
                                                                <div style="min-width: 140px;">
                                                                    <label class="form-label small mb-1">Số lượng mới</label>
                                                                    <input type="hidden" name="items[{{ $item->id }}][order_item_id]" value="{{ $item->id }}">
                                                                    <input type="number" min="0" step="1"
                                                                           name="items[{{ $item->id }}][quantity]"
                                                                           class="form-control form-control-sm js-existing-adjustment-qty"
                                                                           value="{{ (int) ($item->quantity ?? 0) }}">
                                                                </div>
                                                                <button type="button"
                                                                        class="btn btn-outline-danger btn-sm mb-1 js-mark-adjustment-item-remove"
                                                                        data-target-name="items[{{ $item->id }}][quantity]"
                                                                        title="Đặt số lượng về 0 để xóa sản phẩm">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="border rounded p-2 mb-2 bg-white">
                                                <div class="d-grid gap-2 js-new-adjustment-items mb-2" id="new-adjustment-items-{{ $order->id }}" data-next-index="0"></div>
                                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                    <div class="small fw-semibold mb-0">Thêm sản phẩm mới vào đơn</div>
                                                    <button type="button"
                                                            class="btn btn-outline-primary btn-sm js-open-adjustment-product-picker"
                                                            data-order-id="{{ $order->id }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#warehouseAdjustmentProductModal">
                                                        <i class="bi bi-plus-circle me-1"></i>Thêm sản phẩm
                                                    </button>
                                                </div>
                                                <div class="small text-muted mb-0">Chọn sản phẩm từ popup. Popup hỗ trợ tìm kiếm, sắp xếp và phân trang.</div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Lý do thay đổi</label>
                                                <textarea class="form-control form-control-sm" name="reason" rows="2" required>{{ old('reason') }}</textarea>
                                            </div>
                                            <button class="btn btn-outline-warning btn-sm" type="submit">
                                                <i class="bi {{ $warehouseCanAdjust ? 'bi-save2' : 'bi-send' }} me-1"></i>
                                                {{ $warehouseCanAdjust ? 'Lưu điều chỉnh' : 'Lưu thay đổi và gửi sale xác nhận' }}
                                            </button>
                                        </form>
                                    </details>
                                @endif

                                @if($isReadyToPack && !$isPendingSaleConfirmation && $stockShortages->isNotEmpty() && !$hasActiveCuttingBatch)
                                    @foreach($stockShortages as $shortage)
                                        @php
                                            $cuttingPlan = $orderCuttingPlans->get((int) ($shortage['variant_id'] ?? 0));
                                            $cuttingModalId = $cuttingPlan ? 'cutting-order-modal-' . $order->id . '-' . (int) ($shortage['variant_id'] ?? 0) : null;
                                        @endphp
                                        @if($cuttingPlan)
                                            <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#{{ $cuttingModalId }}">
                                                <i class="bi bi-scissors me-1"></i>Thêm hàng pha lóc
                                            </button>
                                        @endif
                                    @endforeach
                                @endif

                                @if($isReadyToPack && !$isPendingSaleConfirmation && $stockShortages->isNotEmpty())
                                    <a class="btn btn-outline-danger btn-sm wh-inventory-action-btn" href="{{ route($packingInventoryRoute ?? 'warehouse.stock-in') }}">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>Nhập kho
                                    </a>
                                @endif

                                @if($isReadyToPack)
                                    @if($canStartPacking && !$isPendingSaleConfirmation)
                                        <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.start-packing', $order) }}" method="POST" class="js-start-packing-form">
                                            @csrf
                                            <input type="hidden" name="packing_date" value="{{ $selectedDate ?? now()->toDateString() }}">
                                            <button class="btn btn-primary btn-sm js-start-packing-btn" type="submit">
                                                <i class="bi bi-box2 me-1"></i>
                                                {{ $isTodaySelected ? 'Đóng hàng' : 'Đóng hàng ngày ' . \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m') }}
                                            </button>
                                        </form>
                                    @elseif($isPendingSaleConfirmation)
                                        <button class="btn btn-warning btn-sm" type="button" disabled>
                                            <i class="bi bi-hourglass-split me-1"></i>Đang chờ sale xác nhận thay đổi đơn
                                        </button>
                                    @else
                                        <button class="btn btn-danger btn-sm" type="button" disabled>
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Không đủ hàng - Chờ nhập kho
                                        </button>
                                    @endif
                                @endif

                                <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.return-to-ready', $order) }}" method="POST" class="js-undo-packing-form {{ $canUndoStartPacking ? '' : 'd-none' }}">
                                    @csrf
                                    <button class="btn btn-outline-warning btn-sm js-undo-packing-btn" type="submit">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Undo nhận đơn
                                    </button>
                                </form>

                                <form action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders.complete-packing', $order) }}" method="POST" class="js-complete-packing-form {{ $isPacking ? '' : 'd-none' }}">
                                    @csrf
                                    <input type="hidden" name="packing_date" value="{{ $selectedDate ?? now()->toDateString() }}">
                                    <button class="btn btn-sm wh-warning-action-btn" {{ $isPendingSaleConfirmation ? 'disabled' : '' }}>
                                        <i class="bi bi-check2-all me-1"></i>
                                        {{ $isTodaySelected ? 'Hoàn thành đóng gói' : 'Hoàn thành đóng gói ngày ' . \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m') }}
                                    </button>
                                </form>
                            </div>

                            @if($isPacking)
                                <div class="small text-muted mt-2">
                                    {{ $canUndoStartPacking ? 'Bạn có thể Undo để trả đơn về hàng chờ.' : 'Đơn đang được user khác nhận đóng hàng.' }}
                                </div>
                            @endif
                        @else
                            @php
                                $isNotReceived = in_array($order->status, [
                                    'approved',
                                    'ready_to_pack',
                                    'pending',
                                    'pending_leader_approval',
                                    'pending_manager_approval',
                                    'pending_warehouse_approval',
                                ], true);
                            @endphp
                            <span class="badge {{ $isNotReceived ? 'bg-secondary' : 'bg-success' }}">
                                {{ $isNotReceived ? 'Chưa tiếp nhận' : 'Đã xử lý' }}
                            </span>
                        @endif
                    </div>
                </div>
                </div>
                @foreach($orderCuttingPlans as $cuttingPlan)
                    @include('warehouse.cutting._order_modal', ['cuttingOrder' => $order, 'cuttingPlan' => $cuttingPlan, 'selectedDate' => $selectedDate ?? now()->toDateString()])
                @endforeach
                @if($isPackageOrderLayout)
                    @foreach($activeCuttingBatches as $batch)
                        @php
                            $batchModalId = 'complete-cutting-batch-' . (int) $batch->id;
                            $plannedComponents = collect($batch->planned_components ?? []);
                            $sourceItems = collect($batch->exportDocument?->items ?? []);
                            $verifications = collect($batch->picked_material_verifications ?? [])->keyBy(fn ($row) => (int) ($row['variant_id'] ?? 0));
                            $sourceVariantIds = $sourceItems->pluck('product_variant_id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
                            $allMaterialsPicked = $sourceVariantIds->isEmpty() || $sourceVariantIds->every(fn ($id) => $verifications->has((int) $id));
                            $targetName = trim(($batch->targetVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($batch->targetVariant?->name ?: ''));
                        @endphp
                        <div class="modal fade" id="{{ $batchModalId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable wh-orders-scroll-modal">
                                <div class="modal-content border-warning">
                                    <form method="POST" action="{{ route('package.cutting-batches.complete', $batch) }}">
                                        @csrf
                                        <div class="modal-header bg-warning-subtle">
                                            <div>
                                                <h5 class="modal-title">Hoàn thiện pha lóc</h5>
                                                <div class="small text-muted">{{ $order->code }} · {{ $targetName }}</div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning py-2">
                                                Nguyên liệu đã lấy: <strong>{{ format_kg((float) $batch->input_weight) }}</strong>.
                                                Nhập kg thực tế để ghi nhận nhập kho và tính hao hụt.
                                            </div>
                                            <div class="mb-3">
                                                <div class="fw-semibold mb-2">Kho đã xác nhận lấy các mặt hàng</div>
                                                <div class="wh-picked-material-list">
                                                    @forelse($sourceItems as $sourceItem)
                                                        @php
                                                            $sourceVariant = $sourceItem->productVariant;
                                                            $sourceVariantId = (int) ($sourceItem->product_variant_id ?? 0);
                                                            $sourceName = trim(($sourceVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($sourceVariant?->name ?: ''));
                                                            $pickedVerification = $verifications->get($sourceVariantId);
                                                            $isPickedVerified = !empty($pickedVerification);
                                                        @endphp
                                                        <div class="wh-picked-material-row {{ $isPickedVerified ? 'is-verified' : '' }}"
                                                             data-picked-material-row
                                                             data-batch-id="{{ (int) $batch->id }}"
                                                             data-variant-id="{{ $sourceVariantId }}"
                                                             data-picked-url="{{ route('package.cutting-batches.materials.picked', ['batch' => $batch, 'variant' => $sourceVariantId]) }}"
                                                             data-unpicked-url="{{ route('package.cutting-batches.materials.unpicked', ['batch' => $batch, 'variant' => $sourceVariantId]) }}">
                                                            <div>
                                                                <div class="fw-semibold">{{ $sourceName }}</div>
                                                                <div class="wh-picked-material-meta" data-picked-material-meta-base="Kho xuất {{ rtrim(rtrim(number_format((float) $sourceItem->quantity, 3, '.', ''), '0'), '.') }} con{{ $sourceVariant?->sku ? ' · ' . $sourceVariant->sku : '' }}">
                                                                    Kho xuất {{ rtrim(rtrim(number_format((float) $sourceItem->quantity, 3, '.', ''), '0'), '.') }} con{{ $sourceVariant?->sku ? ' · ' . $sourceVariant->sku : '' }}
                                                                    <span data-picked-material-verify-text>{{ $isPickedVerified ? ' · Verify bởi ' . ($pickedVerification['verified_by_name'] ?? 'Package') : '' }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="wh-picked-material-actions">
                                                                <span class="badge wh-picked-material-badge {{ $isPickedVerified ? '' : 'd-none' }}" data-picked-material-badge>
                                                                    <i class="bi bi-check2-circle me-1"></i>Đã lấy
                                                                </span>
                                                                <button type="button" class="btn btn-sm btn-success js-picked-material-action {{ $isPickedVerified ? 'd-none' : '' }}" data-picked-action="pick">
                                                                    <i class="bi bi-check2-circle me-1"></i>Đã lấy
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger js-picked-material-action {{ $isPickedVerified ? '' : 'd-none' }}" data-picked-action="unpick">
                                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Quay lại
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="small text-muted">Chưa có dữ liệu nguyên liệu kho đã xuất.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Thành phẩm thực tế</label>
                                                <div class="input-group">
                                                    <input type="number" name="actual_finished_weight" class="form-control" min="0.001" step="0.001" value="{{ number_format((float) $batch->planned_finished_weight, 3, '.', '') }}" required>
                                                    <span class="input-group-text">kg</span>
                                                </div>
                                            </div>
                                            <div class="fw-semibold mb-2">Thành phần còn lại thực tế</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Thành phần</th>
                                                            <th class="text-end" style="width:180px;">Kg thực tế</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($plannedComponents as $index => $component)
                                                            <tr>
                                                                <td>
                                                                    <div class="fw-semibold">{{ $component['name'] ?? 'Thành phần' }}</div>
                                                                    <input type="hidden" name="components[{{ $index }}][variant_id]" value="{{ (int) ($component['variant_id'] ?? 0) }}">
                                                                </td>
                                                                <td>
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number" name="components[{{ $index }}][weight]" class="form-control text-end" min="0" step="0.001" value="{{ number_format((float) ($component['weight'] ?? 0), 3, '.', '') }}">
                                                                        <span class="input-group-text">kg</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="2" class="text-center text-muted py-3">Không có thành phần còn lại.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" value="1" name="defer_components" id="{{ $batchModalId }}-defer">
                                                <label class="form-check-label" for="{{ $batchModalId }}-defer">Nhập sau các thành phần còn lại</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <div class="me-auto small text-danger fw-semibold js-cutting-picked-warning {{ $allMaterialsPicked ? 'd-none' : '' }}" data-batch-id="{{ (int) $batch->id }}">
                                                Cần bấm Đã lấy cho tất cả mặt hàng kho đã xuất.
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                                            <button type="submit" class="btn btn-success js-complete-cutting-batch-btn" data-batch-id="{{ (int) $batch->id }}" {{ $allMaterialsPicked ? '' : 'disabled' }}>
                                                <i class="bi bi-check2-circle me-1"></i>Hoàn thiện nhập kho
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                @if($hasCustomerFeedback)
                <div class="wh-customer-feedback-panel is-alert">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="wh-customer-feedback-title">Tình trạng khách hàng</div>
                        <span class="badge border {{ $customerFeedbackMeta['class'] ?? 'bg-secondary-subtle text-secondary border-secondary-subtle' }}">
                            {{ $customerFeedbackMeta['label'] ?? 'Chưa có phản hồi' }}
                        </span>
                    </div>
                    @if($customerFeedbackRows->isNotEmpty())
                        <div class="d-grid gap-2">
                            @foreach($customerFeedbackRows->take(3) as $feedback)
                                <div class="border-top pt-2">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="badge border {{ $feedback['meta']['class'] ?? 'bg-secondary-subtle text-secondary border-secondary-subtle' }}">
                                            {{ $feedback['meta']['label'] ?? 'Phản hồi' }}
                                        </span>
                                        <span class="small text-muted">{{ $feedback['at'] ?? '' }}</span>
                                    </div>
                                    <div class="wh-customer-feedback-note mt-1">{{ $feedback['note'] ?? '' }}</div>
                                    @if(!empty($feedback['sale_review']))
                                        <div class="wh-customer-feedback-note mt-1">
                                            <strong>Đánh giá sale:</strong> {{ $feedback['sale_review'] }}
                                        </div>
                                    @endif
                                    @if(!empty($feedback['images']))
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @foreach($feedback['images'] as $image)
                                                <a href="{{ $image['url'] ?? '#' }}" target="_blank" rel="noopener">
                                                    <img src="{{ $image['url'] ?? '' }}" alt="Feedback" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="small text-muted mt-1">
                                        {{ $feedback['code'] ?? '' }}{{ !empty($feedback['user']) ? ' • ' . $feedback['user'] : '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @endif
                </div>
            </div>
