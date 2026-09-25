{{--
  Partial: sort toolbar + schedule list + pagination
  Used both server-side (main view @include) and AJAX (rendered to JSON).
  Variables required: $schedules, $sort, $sortDir, $settings
--}}
@php
    $sortColumns = [
        'schedule_date' => ['label' => 'Ngày tháng',     'icon' => 'bi-calendar3'],
        'customer_name' => ['label' => 'Khách hàng',     'icon' => 'bi-person'],
        'is_active'     => ['label' => 'Trạng thái chạy','icon' => 'bi-toggles'],
    ];
    $baseParams = array_filter(request()->only(['status','customer_id','from_date','to_date','search','per_page']), fn($v) => $v !== '' && $v !== null);
@endphp

{{-- Sort toolbar --}}
<div class="d-flex align-items-center gap-2 flex-wrap mb-2 px-1" style="font-size:.82rem;">
    <span class="text-muted">Sắp xếp:</span>
    @foreach($sortColumns as $key => $col)
        @php
            $isActive = $sort === $key;
            $nextDir  = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
        @endphp
        <button type="button"
                class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }} d-flex align-items-center gap-1 py-1 js-sort-btn"
                data-sort="{{ $key }}"
                data-dir="{{ $isActive ? $nextDir : 'asc' }}">
            <i class="bi {{ $col['icon'] }}"></i>
            {{ $col['label'] }}
            @if($isActive)
                <i class="bi {{ $sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down' }}"></i>
            @else
                <i class="bi bi-arrow-down-up opacity-50"></i>
            @endif
        </button>
    @endforeach
    <span class="ms-auto text-muted" id="schedTotalInfo">{{ $schedules->total() }} lịch</span>
</div>

{{-- Schedule list --}}
@if($schedules->isEmpty())
    <div class="schidx-empty">
        <i class="bi bi-calendar2-x" style="font-size:2.5rem;opacity:.25;"></i>
        <p class="mt-2 mb-0">Không có lịch nào khớp với bộ lọc</p>
    </div>
@else
    <div class="schidx-list">
        @foreach($schedules as $schedule)
            @php
                $rowClass = match($schedule->status) {
                    'need_review' => 'schidx-row-need-review',
                    'pending'     => 'schidx-row-pending',
                    'approved'    => 'schidx-row-approved',
                    'generated'   => 'schidx-row-generated',
                    default       => '',
                };
                $stoppedClass = $schedule->is_active ? '' : 'schidx-row-stopped';
                $daysLeft = $schedule->status === 'generated'
                    ? null
                    : (int) now()->startOfDay()->diffInDays(
                        optional($schedule->schedule_date)->startOfDay(),
                        false
                      );
            @endphp
            <div class="schidx-row {{ $rowClass }} {{ $stoppedClass }}" id="schedule-row-{{ $schedule->id }}">
                {{-- Top row --}}
                <div class="schidx-row-top">
                    {{-- Customer --}}
                    <div>
                        <div class="schidx-customer-name">{{ $schedule->customer->name ?? 'N/A' }}</div>
                        <div class="schidx-customer-phone">{{ $schedule->customer->phone ?? '' }}</div>
                    </div>
                    {{-- Date + ID --}}
                    <div>
                        <div class="fw-bold">{{ optional($schedule->schedule_date)->format('d/m/Y') }}</div>
                        @php
                            $dow = optional($schedule->schedule_date)->dayOfWeek; // 0=Sun..6=Sat
                                $dowLabels = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ bảy'];
                            $dowLabel  = $dow !== null ? $dowLabels[$dow] : '';
                        @endphp
                        <div class="schidx-mini">{{ $dowLabel }} &middot; #{{ $schedule->id }}</div>
                        @if($schedule->status !== 'generated')
                            @if($daysLeft < 0)
                                <span class="schidx-badge schidx-badge-need-review mt-1" title="Quá hạn {{ abs($daysLeft) }} ngày">
                                    <i class="bi bi-alarm-fill me-1"></i>Quá {{ abs($daysLeft) }} ngày
                                </span>
                            @elseif($daysLeft === 0)
                                <span class="schidx-badge" style="background:#fef3c7;color:#92400e;margin-top:3px;" title="Hôm nay lên đơn">
                                    <i class="bi bi-lightning-fill me-1"></i>Hôm nay
                                </span>
                            @else
                                <span class="schidx-badge schidx-badge-ok mt-1" title="{{ $daysLeft }} ngày nữa lên đơn">
                                    <i class="bi bi-hourglass-split me-1"></i>{{ $daysLeft }} ngày nữa
                                </span>
                            @endif
                        @endif
                    </div>
                    {{-- Status badges --}}
                    <div class="d-flex flex-column gap-1">
                        <span class="schidx-badge schidx-badge-{{ str_replace('_', '-', $schedule->status) }}">{{ $schedule->status }}</span>
                        <span class="schidx-badge {{ $schedule->price_status === 'changed' ? 'schidx-badge-changed' : 'schidx-badge-ok' }}">Giá: {{ $schedule->price_status }}</span>
                        @if(optional($schedule->schedule_date)->lte(now()->startOfDay()))
                        <span class="schidx-badge {{ $schedule->stock_status === 'insufficient' ? 'schidx-badge-insufficient' : 'schidx-badge-ok' }}">Tồn: {{ $schedule->stock_status }}</span>
                        @else
                        <span class="schidx-badge" style="background:#f1f5f9;color:#94a3b8;" title="Tồn kho sẽ được kiểm tra vào ngày hẹn">Tồn: chưa kiểm tra</span>
                        @endif
                        <button type="button"
                            class="schidx-active-btn {{ $schedule->is_active ? 'is-on' : 'is-off' }}"
                            id="active-btn-{{ $schedule->id }}"
                            data-id="{{ $schedule->id }}"
                            data-url="{{ route('my_customer.schedules.toggle_active', $schedule) }}"
                            onclick="toggleActiveSchedule(this)">
                            @if($schedule->is_active)
                                <i class="bi bi-play-fill me-1"></i>Đang chạy
                            @else
                                <i class="bi bi-stop-fill me-1"></i>Đã dừng
                            @endif
                        </button>
                    </div>
                    {{-- Order link --}}
                    <div>
                        @if($schedule->generatedOrder)
                            <a href="{{ route('site.orders.show', $schedule->generatedOrder) }}" class="fw-bold text-primary small">
                                <i class="bi bi-box-arrow-up-right me-1"></i>{{ $schedule->generatedOrder->code ?? ('#'.$schedule->generatedOrder->id) }}
                            </a>
                        @else
                            <span class="schidx-mini">—</span>
                        @endif
                        <div class="schidx-mini mt-1">{{ $schedule->items->count() }} SP</div>
                    </div>
                    {{-- Actions --}}
                    <div class="d-flex flex-column gap-1 align-items-end">
                        <a href="{{ route('my_customer.schedules.show', $schedule) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-square"></i> Review
                        </a>
                        @if($schedule->status !== 'generated')
                            <a href="{{ route('my_customer.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete({{ $schedule->id }}, '{{ addslashes(optional($schedule->customer)->name ?? 'N/A') }}', '{{ optional($schedule->schedule_date)->format('d/m/Y') }}')">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        @endif
                        <button type="button" class="schidx-toggle-btn" onclick="toggleDetail({{ $schedule->id }}, this)">
                            <i class="bi bi-chevron-down me-1"></i>Chi tiết
                        </button>
                    </div>
                </div>

                {{-- Expandable detail --}}
                <div class="schidx-detail" id="detail-{{ $schedule->id }}">
                    @if($schedule->items->isNotEmpty())
                        <div class="schidx-items-grid">
                            <div class="schidx-item-row schidx-item-head">
                                <div>Sản phẩm</div>
                                <div class="text-center">SL lịch</div>
                                <div class="text-center">Tồn kho</div>
                                <div class="text-end">Giá lịch</div>
                                <div class="text-end">Giá hiện tại</div>
                            </div>
                            @foreach($schedule->items as $item)
                                @php
                                    $pName     = $item->variant->product->name ?? 'N/A';
                                    $pSize     = $item->variant->size ?? '-';
                                    $pUnit     = $item->variant->unit_label ?? 'cái';
                                    $pQty      = (int) $item->quantity;
                                    $pPrice    = (float) $item->scheduled_price;
                                    $pSubtotal = $pQty * $pPrice;
                                @endphp
                                <div class="schidx-item-row js-product-line"
                                     data-product-name="{{ e($pName) }}"
                                     data-product-size="{{ e($pSize) }}"
                                     data-product-unit="{{ e($pUnit) }}"
                                     data-product-qty="{{ $pQty }}"
                                     data-product-price="{{ $pPrice }}"
                                     data-product-subtotal="{{ $pSubtotal }}">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">{{ $pName }}</div>
                                        <div style="font-size:.72rem;color:#64748b;">{{ $item->variant->sku ?? ('#'.$item->product_variant_id) }}</div>
                                    </div>
                                    <div class="text-center fw-bold">{{ $pQty }}</div>
                                    <div class="text-center {{ $item->stock_diff ? 'text-danger fw-bold' : '' }}">{{ $item->stock_available }}</div>
                                    <div class="text-end">{{ number_format($pPrice, 0, ',', '.') }}đ</div>
                                    <div class="text-end {{ $item->price_diff ? 'text-warning fw-bold' : 'text-success' }}">
                                        {{ number_format((float)($item->current_price ?? 0), 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small text-center py-2">Không có sản phẩm</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-between align-items-center mt-3" id="schedPaginationWrap">
        <small class="text-muted">Hiển thị {{ $schedules->firstItem() ?? 0 }}–{{ $schedules->lastItem() ?? 0 }} / {{ $schedules->total() }}</small>
        {{ $schedules->links() }}
    </div>
@endif
