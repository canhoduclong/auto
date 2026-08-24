@extends('layouts.app')
@section('content')
<div class="container">
    @php
        $formatSignedMoney = static function (float $amount): string {
            $prefix = $amount < 0 ? '+' : '-';

            return $prefix . number_format(abs($amount), 0, ',', '.') . ' đ';
        };
    @endphp
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">{{ __('orders.titles.detail', ['code' => $order->code]) }}</h4>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('orders.buttons.back_to_list') }}</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ __('orders.labels.customer') }}:</strong> {{ $order->customer->name ?? '' }}</p>
                    <p><strong>{{ __('orders.labels.employee') }}:</strong> {{ $order->user->name ?? '' }}</p>
                    <p><strong>{{ __('orders.labels.total') }}:</strong> {{ number_format($order->total, 0, ',', '.') }} đ</p>
                    <p><strong>{{ __('orders.labels.created_at') }}:</strong> {{ $order->created_at }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ __('orders.labels.current_status') }}:</strong> {{ $statusLabels[$order->status] ?? $order->status }}</p>
                    @if($order->skip_auto_cancel)
                        <p><span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="bi bi-shield-check me-1"></i>Đơn ngoại lệ – không hủy tự động</span></p>
                    @endif
                    <p><strong>{{ __('orders.labels.payment_status') }}:</strong> {{ __('orders.payment_statuses.' . $order->payment_status) }}</p>
                    <p><strong>{{ __('orders.labels.delivery_status') }}:</strong> {{ __('orders.delivery_statuses.' . $order->delivery_status) }}</p>
                    <p><strong>Giờ giao hàng:</strong> {{ $order->delivery_time ?: ($order->customer->delivery_time ?? '-') }}</p>
                    @if($currentPendingApproval && $currentPendingApproval->step)
                        <p><strong>{{ __('orders.labels.pending_approval') }}:</strong> {{ __('orders.labels.step') }} {{ $currentPendingApproval->step->step_order }} ({{ __('orders.labels.role') }}: {{ $currentPendingApproval->step->role_slug }})</p>
                    @endif
                </div>
            </div>

            @if(auth()->check() && (
                auth()->user()->hasRole('admin')
                || auth()->user()->hasRole('sale')
                || auth()->user()->hasRole('leader_sale')
                || auth()->user()->hasRole('leader')
                || auth()->user()->hasRole('sale_manager')
                || auth()->user()->hasRole('manager_sale')
                || auth()->user()->hasRole('manager')
            ))
                <hr>
                <form action="{{ route('orders.update-delivery-time', $order->id) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label for="delivery_time" class="form-label mb-1">Điều chỉnh giờ giao hàng theo yêu cầu khách</label>
                        <input
                            type="text"
                            id="delivery_time"
                            name="delivery_time"
                            class="form-control"
                            value="{{ old('delivery_time', $order->delivery_time ?: ($order->customer->delivery_time ?? '')) }}"
                            placeholder="Ví dụ: 9h-11h, sau 17h"
                        >
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary">Cập nhật giờ giao</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="card-footer">
            @if($order->status === 'approved' && $canWarehouse)
                <form action="{{ route('orders.picking', $order->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">{{ __('orders.buttons.warehouse_pick') }}</button>
                </form>
            @endif

            @if($order->status === 'packing' && $canWarehouse)
                <form action="{{ route('orders.complete-packing', $order->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-4">
                        <input type="file" name="packed_image" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="note" class="form-control" placeholder="{{ __('orders.placeholders.packing_note') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">{{ __('orders.buttons.warehouse_complete_packing') }}</button>
                    </div>
                </form>
            @endif

            @if($order->status === 'packed' && $canShipper)
                <form action="{{ route('orders.pickup', $order->id) }}" method="POST" class="d-inline mt-2">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('orders.buttons.shipper_pickup') }}</button>
                </form>
            @endif

            @if($order->status === 'shipping' && $canShipper)
                <form action="{{ route('orders.delivered', $order->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-4">
                        <input type="file" name="delivered_image" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="note" class="form-control" placeholder="{{ __('orders.placeholders.delivery_note') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100">{{ __('orders.buttons.mark_delivered') }}</button>
                    </div>
                </form>
            @endif

            @if($order->status === 'delivered' && $canShipper)
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <form action="{{ route('orders.complete-payment', $order->id) }}" method="POST" enctype="multipart/form-data" class="border rounded p-2">
                            @csrf
                            <h6 class="mb-2">{{ __('orders.buttons.pay') }}</h6>
                            <div class="mb-2">
                                <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="{{ __('transactions.labels.amount') }}" required>
                            </div>
                            <div class="mb-2">
                                <input type="file" name="receipt_image" class="form-control" required>
                                <small class="text-muted">{{ __('orders.labels.note') }}</small>
                            </div>
                            <div class="mb-2">
                                <input type="file" name="delivery_image" class="form-control">
                                <small class="text-muted">{{ __('orders.labels.delivery_status') }}</small>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="note" class="form-control" placeholder="{{ __('orders.placeholders.payment_note') }}">
                            </div>
                            <button type="submit" class="btn btn-success">{{ __('orders.buttons.complete_order') }}</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('orders.refund', $order->id) }}" method="POST" class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                            @csrf
                            <div>
                                <h6 class="mb-2">Refund</h6>
                                <p class="text-muted mb-2">Tạo đơn hoàn trả liên kết với đơn gốc nếu khách không nhận hàng hoặc trả hàng.</p>
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('orders.confirms.create_refund') }}')">{{ __('orders.buttons.refund') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            @if(auth()->user()?->isAdmin() && in_array($order->status, \App\Models\Order::CANCELLABLE_STATUSES, true))
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('orders.confirms.cancel_order') }}')">{{ __('orders.buttons.cancel_order') }}</button>
                </form>
            @endif

            @if(auth()->user()?->isAdmin() && $order->status === \App\Models\Order::STATUS_CANCELLED && empty($order->trash_at))
                <form action="{{ route('orders.restore-cancelled', $order) }}" method="POST" class="d-inline ms-2"
                      onsubmit="return confirm('Phục hồi đơn {{ addslashes($order->code ?: ('#' . $order->id)) }} và đánh dấu là đơn ngoại lệ để tiếp tục thực hiện? Booking tồn kho sẽ được dựng lại.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-success">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Phục hồi &amp; đánh dấu ngoại lệ
                    </button>
                </form>
            @endif

            @if(auth()->user()?->isAdmin())
                <form action="{{ route('orders.admin-delete', $order) }}" method="POST" class="d-inline ms-2"
                      onsubmit="const reason = window.prompt('Nhập lý do xóa đơn (ít nhất 5 ký tự):'); if (!reason || reason.trim().length < 5) { window.alert('Vui lòng nhập lý do xóa ít nhất 5 ký tự.'); return false; } this.elements.reason.value = reason.trim(); return window.confirm('Xóa vĩnh viễn đơn này và loại toàn bộ doanh số, hoa hồng liên quan?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="reason" value="">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Xóa &amp; loại doanh số
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('orders.titles.approval') }}</h5>
        </div>
        <div class="card-body">
            @if($order->status === \App\Enums\OrderStatus::Approved->value)
                <span class="badge bg-success">{{ __('orders.approval.approved') }}</span>
            @elseif($order->status === \App\Enums\OrderStatus::Rejected->value)
                <span class="badge bg-danger">{{ __('orders.approval.rejected') }}</span>
            @elseif($canApproveCurrentStep)
                <form method="POST" action="{{ route('orders.approve', $order) }}" class="mb-2">
                    @csrf
                    <div class="mb-2">
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('orders.placeholders.approval_note') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">{{ __('orders.buttons.approve') }}</button>
                </form>
                <form method="POST" action="{{ route('orders.reject', $order) }}">
                    @csrf
                    <div class="mb-2">
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('orders.placeholders.reject_reason') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">{{ __('orders.buttons.reject') }}</button>
                </form>
            @else
                <p class="mb-0 text-muted">{{ __('orders.approval.no_permission') }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('orders.titles.history') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('orders.labels.time') }}</th>
                            <th>{{ __('orders.labels.employee') }}</th>
                            <th>{{ __('orders.labels.role') }}</th>
                            <th>{{ __('orders.labels.action') }}</th>
                            <th>{{ __('orders.labels.status_before') }}</th>
                            <th>{{ __('orders.labels.status_after') }}</th>
                            <th>{{ __('orders.labels.note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->histories->sortBy('created_at') as $history)
                            <tr>
                                <td>{{ $history->created_at }}</td>
                                <td>{{ $history->user->name ?? '-' }}</td>
                                <td>{{ $history->role ?? '-' }}</td>
                                <td>{{ $history->action }}</td>
                                <td>{{ $history->status_before ?? '-' }}</td>
                                <td>{{ $history->status_after ?? '-' }}</td>
                                <td>{{ $history->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('orders.empty.history') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('orders.titles.approval_history') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('orders.labels.step') }}</th>
                            <th>{{ __('orders.labels.role') }}</th>
                            <th>{{ __('orders.labels.status') }}</th>
                            <th>{{ __('orders.labels.employee') }}</th>
                            <th>{{ __('orders.labels.time') }}</th>
                            <th>{{ __('orders.labels.note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->approvals->sortBy(fn($item) => $item->step->step_order ?? 999) as $approval)
                            <tr>
                                <td>{{ $approval->step->step_order ?? '' }}</td>
                                <td>{{ $approval->step->role_slug ?? '' }}</td>
                                <td>{{ $approval->status }}</td>
                                <td>{{ $approval->approver->name ?? '' }}</td>
                                <td>{{ $approval->approved_at }}</td>
                                <td>{{ $approval->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">{{ __('orders.empty.approval_history') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <h5>{{ __('orders.titles.products') }}</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{{ __('orders.titles.products') }}</th>
                <th>{{ __('orders.labels.variant') }}</th>
                <th>{{ __('orders.labels.quantity') }}</th>
                <th>Giá gốc</th>
                <th>Giá Min</th>
                <th>{{ __('orders.labels.unit_price') }}</th>
                <th>Điều chỉnh</th>
                <th>{{ __('orders.labels.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->variant->product->name ?? '' }}</td>
                <td>{{ $item->variant->variant_name ?? ($item->variant->sku ?? '') }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format((float) ($item->base_price ?? $item->price), 0, ',', '.') }} đ</td>
                <td>{{ number_format((float) ($item->variant->latestPriceRule->min_price ?? 0), 0, ',', '.') }} đ</td>
                <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                <td>{{ $formatSignedMoney((float) ($item->discount_total ?? 0)) }}</td>
                <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!$order->isPaid())
        <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success mb-3">+ {{ __('orders.buttons.add_transaction') }}</a>
    @endif
    <h5>{{ __('orders.titles.transactions') }}</h5>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('transactions.labels.amount') }}</th>
                <th>{{ __('transactions.labels.type') }}</th>
                <th>{{ __('transactions.labels.method') }}</th>
                <th>{{ __('transactions.labels.note') }}</th>
                <th>{{ __('transactions.labels.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ number_format($t->amount,0,',','.') }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->method }}</td>
                    <td>{{ $t->note }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
