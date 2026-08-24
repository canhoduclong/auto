@extends('layouts.app')

@section('content')
<div class="content">
    <div class="orders-page-header card border-0 mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1">{{ __('orders.titles.index') }}</h4>
                <div class="text-muted">Theo dõi, duyệt và xử lý đơn hàng trên một màn hình.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ url('/admin/sync-daily-sequence') }}" class="btn btn-warning">
                    <i class="bi bi-arrow-repeat"></i> Đồng bộ số thứ tự ưu tiên
                </a>
                <a href="{{ route('approval-workflows.create') }}" class="btn btn-outline-primary">
                    {{ __('orders.buttons.create_workflow') }}
                </a>
                <a href="{{ route('orders.create') }}" class="btn btn-success">
                    + {{ __('orders.buttons.add_order') }}
                </a>
            </div>
        </div>
    </div>

    @php
        $hasActiveFilter = request()->hasAny(['customer_name','phone_number','user_id','team_id','payment_status','status','from_date','to_date','my_pending_approval']);
    @endphp
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center" style="cursor:pointer;" id="ordersFilterToggle" role="button" aria-expanded="{{ $hasActiveFilter ? 'true' : 'false' }}" aria-controls="ordersFilterBody">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-secondary"></i>
                <h6 class="mb-0 fw-semibold">{{ __('orders.labels.filter') }}</h6>
                @if($hasActiveFilter)
                    <span class="badge bg-primary-subtle text-primary border small">Đang lọc</span>
                @endif
            </div>
            <span class="btn btn-sm btn-outline-secondary py-0 px-2 d-flex align-items-center gap-1">
                <i class="bi bi-chevron-{{ $hasActiveFilter ? 'up' : 'down' }}" id="ordersFilterChevron"></i>
                <span id="ordersFilterToggleLabel" class="small">{{ $hasActiveFilter ? 'Thu gọn' : 'Mở rộng' }}</span>
            </span>
        </div>
        <div class="{{ $hasActiveFilter ? '' : 'collapse' }}" id="ordersFilterBody">
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="customer_name" class="form-label">{{ __('orders.labels.customer') }}</label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="Tên khách hàng">
                </div>
                <div class="col-md-3">
                    <label for="phone_number" class="form-label">{{ __('orders.labels.phone') }}</label>
                    <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ request('phone_number') }}" placeholder="Số điện thoại">
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">{{ __('orders.labels.updated_by') }}</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">{{ __('orders.labels.all') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="team_id" class="form-label">{{ __('orders.labels.team') }}</label>
                    <select name="team_id" id="team_id" class="form-select">
                        <option value="">{{ __('orders.labels.all') }}</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="payment_status" class="form-label">{{ __('orders.labels.payment_status') }}</label>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="">{{ __('orders.labels.all') }}</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.paid') }}</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.unpaid') }}</option>
                        <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.partially_paid') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">{{ __('orders.labels.status') }}</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">{{ __('orders.labels.all') }}</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label">{{ __('orders.labels.from_date') }}</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">{{ __('orders.labels.to_date') }}</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="my_pending_approval" id="my_pending_approval" value="1" {{ request('my_pending_approval') ? 'checked' : '' }}>
                        <label class="form-check-label" for="my_pending_approval">{{ __('orders.labels.my_pending_approval') }}</label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('orders.buttons.filter') }}</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-light border">{{ __('orders.buttons.clear_filter') }}</a>
                </div>
            </form>
        </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('orders.stats.total_invoice') }}</div>
                    <h5 class="mb-0 text-primary">{{ number_format($totalInvoiceAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('orders.stats.total_paid') }}</div>
                    <h5 class="mb-0 text-success">{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('orders.stats.total_outstanding') }}</div>
                    <h5 class="mb-0 text-danger">{{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('orders.labels.statistics') }}</div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                        <span class="badge bg-success-subtle text-success border">{{ __('orders.stats.paid_orders') }}: {{ $fullyPaidOrders }}</span>
                        <span class="badge bg-warning-subtle text-warning border">{{ __('orders.stats.partial_paid_orders') }}: {{ $partiallyPaidOrders }}</span>
                        <span class="badge bg-danger-subtle text-danger border">{{ __('orders.stats.unpaid_orders') }}: {{ $unpaidOrders }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Thống kê hàng hóa theo bộ lọc --}}
    @if($productStats->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.06em;">
                    Hàng - Số lượng
                    <span class="fw-normal">({{ $productStats->count() }} sản phẩm)</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="ordersToggleProductStats">
                    <i class="bi bi-chevron-expand"></i> Chi tiết
                </button>
            </div>
            <div class="d-none" id="ordersProductStatsWrap">
                <div style="display:grid;gap:.35rem;margin-top:.45rem;" id="ordersProductStatsList">
                    <div style="display:grid;grid-template-columns:44px 1.8fr 1fr 1fr 80px;gap:.35rem;border:1px solid #e5edf7;border-radius:8px;padding:.36rem .5rem;background:#eef2f7;font-size:.73rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;align-items:center;">
                        <div>STT</div>
                        <div>Sản phẩm</div>
                        <div>Số lượng</div>
                        <div>Tổng tiền</div>
                        <div>ĐVT</div>
                    </div>
                    @foreach($productStats as $i => $ps)
                    <div style="display:grid;grid-template-columns:44px 1.8fr 1fr 1fr 80px;gap:.35rem;border:1px solid #e5edf7;border-radius:8px;padding:.36rem .5rem;background:#f8fafc;font-size:.8rem;align-items:center;">
                        <div class="text-muted">{{ $i + 1 }}</div>
                        <div class="fw-semibold">{{ $ps->product_name }}</div>
                        <div class="text-primary fw-bold">{{ rtrim(rtrim(number_format((float)$ps->total_qty, 2, '.', ''), '0'), '.') }}</div>
                        <div>{{ number_format((float)$ps->total_amount, 0, ',', '.') }}đ</div>
                        <div class="text-muted">{{ $ps->unit_label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-semibold">Danh sách đơn hàng</h6>
            <span class="text-muted small">Tổng: {{ $orders->total() }} đơn</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 orders-table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 90px;">ID</th>
                            <th style="min-width: 120px;">{{ __('orders.labels.code') }}</th>
                            <th style="min-width: 180px;">{{ __('orders.labels.customer') }}</th>
                            <th style="min-width: 150px;">{{ __('orders.labels.employee') }}</th>
                            <th style="min-width: 140px;">{{ __('orders.labels.total') }}</th>
                            <th style="min-width: 190px;">{{ __('orders.labels.payment_status') }}</th>
                            <th style="min-width: 230px;">{{ __('orders.labels.status') }} / {{ __('orders.labels.approval_column') }}</th>
                            <th style="min-width: 250px;">{{ __('orders.labels.actions') }}</th>
                            <th style="min-width: 110px;">{{ __('orders.labels.qrcode') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $currentApproval = $currentStepByOrder[$order->id] ?? null;
                                $canApprove = $canApproveByOrder[$order->id] ?? false;
                                $paid = $order->transactions->where('type', 'payment')->sum('amount') - $order->transactions->where('type', 'refund')->sum('amount');

                                $statusBadgeClass = match ((string) $order->status) {
                                    \App\Enums\OrderStatus::Approved->value => 'bg-success-subtle text-success border',
                                    \App\Enums\OrderStatus::Rejected->value => 'bg-danger-subtle text-danger border',
                                    default => 'bg-secondary-subtle text-secondary border',
                                };
                            @endphp
                            <tr>
                                <td class="text-muted">#{{ $order->id }}</td>
                                <td class="fw-semibold">{{ $order->code }}</td>
                                <td>
                                    <div class="fw-medium">{{ $order->customer->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $order->customer->phone ?? '' }}</div>
                                </td>
                                <td>
                                    <div>{{ $order->user->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ __('orders.labels.created_at') }}: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                <td>
                                    @if($order->isPaid() || $order->status === \App\Models\Order::STATUS_COMPLETED)
                                        <span class="badge bg-success">Đã thanh toán đủ</span>
                                    @elseif($order->isPartialPaid())
                                        <div class="fw-semibold text-warning">
                                            Đã thanh toán: {{ number_format((float) $paid, 0, ',', '.') }} đ
                                        </div>
                                        <div class="small text-muted">Còn lại: {{ number_format((float) max(0, $order->total - $paid), 0, ',', '.') }} đ</div>
                                    @else
                                        <span class="badge bg-danger">Chưa thanh toán</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <button class="btn btn-sm btn-outline-secondary btn-toggle-status" data-id="{{ $order->id }}" data-status="{{ $order->status }}">
                                            {{ $order->status }}
                                        </button>
                                        @if($order->skip_auto_cancel)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning ms-1" title="Đơn được phục hồi ngoại lệ và không bị hủy tự động do quá hạn">
                                                <i class="bi bi-shield-check me-1"></i>Đơn ngoại lệ
                                            </span>
                                        @endif
                                    </div>

                                    @if($order->status === \App\Enums\OrderStatus::Approved->value)
                                        <span class="badge {{ $statusBadgeClass }}">Đã duyệt</span>
                                    @elseif($order->status === \App\Enums\OrderStatus::Rejected->value)
                                        <span class="badge {{ $statusBadgeClass }}">Đã từ chối</span>
                                    @elseif($currentApproval && $currentApproval->step)
                                        <div class="mb-1">
                                            <span class="badge bg-info-subtle text-info border">
                                                B{{ $currentApproval->step->step_order }} - {{ $currentApproval->step->role_slug }}
                                            </span>
                                        </div>
                                        @if($canApprove)
                                            <form method="POST" action="{{ route('orders.approve', $order) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="note" value="{{ __('orders.approval.quick_approve_note') }}">
                                                <button type="submit" class="btn btn-sm btn-success">{{ __('orders.buttons.approve') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('orders.reject', $order) }}" class="d-inline ms-1">
                                                @csrf
                                                <input type="hidden" name="note" value="{{ __('orders.approval.quick_reject_note') }}">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('orders.confirms.reject_order') }}')">{{ __('orders.buttons.reject') }}</button>
                                            </form>
                                        @else
                                            <small class="text-muted">{{ __('orders.approval.not_your_role') }}</small>
                                        @endif
                                    @else
                                        <small class="text-muted">{{ __('orders.approval.no_pending_step') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">{{ __('orders.buttons.view') }}</a>

                                        @if(in_array($order->status, ['picked_up', 'shipping', 'completed'], true))
                                            <a href="{{ route('order-returns.create', ['order_id' => $order->id]) }}" class="btn btn-sm btn-outline-warning">Trả hàng</a>
                                        @endif

                                        @if(!$order->isPaid() && $order->status !== \App\Models\Order::STATUS_COMPLETED)
                                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-outline-info">{{ __('orders.buttons.edit') }}</a>
                                        @endif

                                        @if($order->status !== \App\Models\Order::STATUS_COMPLETED && !$order->isPaid())
                                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-sm btn-success">{{ __('orders.buttons.pay') }}</a>
                                        @endif

                                        @if(auth()->user()?->isAdmin() && in_array($order->status, \App\Models\Order::CANCELLABLE_STATUSES, true))
                                            <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn {{ addslashes($order->code ?: ('#' . $order->id)) }}? Booking tồn kho của đơn sẽ được giải phóng.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Hủy đơn hàng
                                                </button>
                                            </form>
                                        @endif

                                        @if(auth()->user()?->isAdmin() && $order->status === \App\Models\Order::STATUS_CANCELLED && empty($order->trash_at))
                                            <form action="{{ route('orders.restore-cancelled', $order) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Phục hồi đơn {{ addslashes($order->code ?: ('#' . $order->id)) }} và đánh dấu là đơn ngoại lệ để tiếp tục thực hiện? Booking tồn kho sẽ được dựng lại.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Phục hồi &amp; đánh dấu ngoại lệ
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('orders.confirms.delete_order') }}')">{{ __('orders.buttons.delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    @if($order->qr_code)
                                        <img src="data:image/svg+xml;base64,{{ $order->qr_code }}" alt="QR Code" width="52" height="52">
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">Chưa có đơn hàng phù hợp với điều kiện lọc.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('ordersFilterToggle');
    var body = document.getElementById('ordersFilterBody');
    var chevron = document.getElementById('ordersFilterChevron');
    var label = document.getElementById('ordersFilterToggleLabel');
    if (toggle && body) {
        toggle.addEventListener('click', function () {
            var isOpen = !body.classList.contains('collapse');
            body.classList.toggle('collapse', isOpen);
            if (chevron) {
                chevron.className = isOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
            }
            if (label) {
                label.textContent = isOpen ? 'Mở rộng' : 'Thu gọn';
            }
        });
    }

    var btn = document.getElementById('ordersToggleProductStats');
    var wrap = document.getElementById('ordersProductStatsWrap');
    var visible = false;
    if (btn && wrap) {
        btn.addEventListener('click', function () {
            visible = !visible;
            wrap.classList.toggle('d-none', !visible);
            btn.innerHTML = visible
                ? '<i class="bi bi-chevron-contract"></i> Ẩn chi tiết'
                : '<i class="bi bi-chevron-expand"></i> Chi tiết';
        });
    }
})();
</script>
@endpush

@endsection

@push('styles')
<style>
.orders-page-header {
    background: linear-gradient(130deg, #eef7ff 0%, #f6f9ff 50%, #ffffff 100%);
}

.orders-table thead th {
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
}

.badge.bg-success-subtle,
.badge.bg-warning-subtle,
.badge.bg-danger-subtle,
.badge.bg-secondary-subtle,
.badge.bg-info-subtle {
    font-weight: 600;
}
</style>
@endpush
