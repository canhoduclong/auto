@extends('layouts.site')

@section('content')
<style>
    .tmo-hero {
        background: linear-gradient(120deg, #1f4a7c 0%, #2d7ba8 55%, #4aa3a8 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.4rem 1.2rem;
        margin-bottom: 1rem;
    }
    .tmo-kpi {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06);
    }
    .tmo-kpi .value { font-weight: 700; font-size: 1.3rem; }
    .tmo-filter,
    .tmo-table-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06);
    }
    .tmo-code { font-weight: 700; color: #0d4d77; }
    .tmo-mobile-card {
        border: 0;
        border-left: 4px solid #2d7ba8;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(17, 24, 39, 0.06);
    }
    .tmo-status {
        display: inline-block;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 600;
        padding: .26rem .56rem;
    }
    .tmo-status-pending { background: #fff3cd; color: #7c5a00; }
    .tmo-status-approved { background: #d1e7dd; color: #0f5132; }
    .tmo-status-rejected { background: #f8d7da; color: #842029; }
    .tmo-status-default { background: #e2e3e5; color: #41464b; }
    .tmo-actions form { display: inline-block; }

    @media (max-width: 767.98px) {
        .tmo-desktop { display: none; }
    }
    @media (min-width: 768px) {
        .tmo-mobile { display: none; }
    }
</style>

<div class="container py-3">
    <div class="tmo-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Duyệt Đơn Của Team</h4>
                <div class="opacity-75">Danh sách đơn hàng của sale trong team bạn đang quản lý</div>
            </div>
            <div class="small opacity-75">Leader: {{ $user->name }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card tmo-kpi">
                <div class="card-body">
                    <div class="text-muted small">Tổng đơn</div>
                    <div class="value text-primary">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card tmo-kpi">
                <div class="card-body">
                    <div class="text-muted small">Chờ leader duyệt</div>
                    <div class="value text-warning">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card tmo-kpi">
                <div class="card-body">
                    <div class="text-muted small">Đã duyệt</div>
                    <div class="value text-success">{{ number_format($stats['approved']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card tmo-filter mb-3">
        <div class="card-body">
            <h6 class="mb-3">Duyệt đơn tự động</h6>
            <form method="POST" action="{{ route('pages.my_tearm_orders.auto_approve') }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">

                <div class="col-12">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="condition_item_qty" name="condition_item_qty" value="1" {{ old('condition_item_qty') ? 'checked' : '' }}>
                        <label class="form-check-label" for="condition_item_qty">Điều kiện theo số lượng sản phẩm trong đơn</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">SL tối thiểu</label>
                    <input type="number" min="1" class="form-control" name="min_item_qty" value="{{ old('min_item_qty') }}" placeholder="Ví dụ: 10">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">SL tối đa (tuỳ chọn)</label>
                    <input type="number" min="1" class="form-control" name="max_item_qty" value="{{ old('max_item_qty') }}" placeholder="Ví dụ: 100">
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="condition_order_total" name="condition_order_total" value="1" {{ old('condition_order_total') ? 'checked' : '' }}>
                        <label class="form-check-label" for="condition_order_total">Điều kiện theo giá trị đơn hàng</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Giá trị tối thiểu</label>
                    <input type="number" min="0" step="1000" class="form-control" name="min_order_total" value="{{ old('min_order_total') }}" placeholder="Ví dụ: 1000000">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Giá trị tối đa (tuỳ chọn)</label>
                    <input type="number" min="0" step="1000" class="form-control" name="max_order_total" value="{{ old('max_order_total') }}" placeholder="Ví dụ: 5000000">
                </div>

                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        Duyệt đơn tự động
                    </button>
                </div>
            </form>
            <div class="small text-muted mt-2">
                Hệ thống chỉ duyệt các đơn đang tới lượt role leader của bạn theo workflow hiện tại.
            </div>
        </div>
    </div>

    <div class="card tmo-filter mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('pages.my_tearm_orders') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Mã đơn, khách hàng, sale...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Từ ngày</label>
                    <input type="date" class="form-control" name="from_date" value="{{ $fromDate }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Đến ngày</label>
                    <input type="date" class="form-control" name="to_date" value="{{ $toDate }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Trạng thái đơn</label>
                    <input type="text" class="form-control" name="status" value="{{ request('status') }}" placeholder="vd: pending_leader_approval">
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Lọc</button>
                    <a href="{{ route('pages.my_tearm_orders') }}" class="btn btn-outline-secondary w-100">Hôm nay</a>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="pending_only" id="pending_only" value="1" {{ $pendingOnly ? 'checked' : '' }}>
                        <label class="form-check-label" for="pending_only">Chỉ hiển thị đơn đang tới lượt tôi duyệt</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card tmo-table-card tmo-desktop">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Sale phụ trách</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Bước duyệt hiện tại</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $step = $currentStepByOrder[$order->id] ?? null;
                            $canApprove = $canApproveByOrder[$order->id] ?? false;
                            $statusClass = match ($order->status) {
                                'pending_leader_approval' => 'tmo-status-pending',
                                'approved' => 'tmo-status-approved',
                                'rejected' => 'tmo-status-rejected',
                                default => 'tmo-status-default',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="tmo-code">{{ $order->code }}</div>
                                <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $order->customer->phone ?? '' }}</div>
                            </td>
                            <td>
                                <div>{{ $order->user->name ?? '-' }}</div>
                                <div class="small text-muted">Team: {{ $order->user?->team?->name ?? '-' }}</div>
                            </td>
                            <td class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                            <td><span class="tmo-status {{ $statusClass }}">{{ $order->status }}</span></td>
                            <td>
                                @if($step && $step->step)
                                    <span class="badge bg-info-subtle text-info border">
                                        B{{ $step->step->step_order }} - {{ $step->step->role_slug }}
                                    </span>
                                @else
                                    <span class="text-muted small">Không có bước chờ</span>
                                @endif
                            </td>
                            <td class="text-end tmo-actions">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                @if($canApprove)
                                    <form method="POST" action="{{ route('orders.approve', $order) }}" class="ms-1">
                                        @csrf
                                        <input type="hidden" name="note" value="Leader duyệt từ trang team orders">
                                        <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('orders.reject', $order) }}" class="ms-1">
                                        @csrf
                                        <input type="hidden" name="note" value="Leader từ chối từ trang team orders">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận từ chối đơn này?')">Từ chối</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Không có đơn hàng phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tmo-mobile">
        @forelse($orders as $order)
            @php
                $step = $currentStepByOrder[$order->id] ?? null;
                $canApprove = $canApproveByOrder[$order->id] ?? false;
            @endphp
            <div class="card tmo-mobile-card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="tmo-code">{{ $order->code }}</div>
                            <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="tmo-status tmo-status-default">{{ $order->status }}</span>
                    </div>
                    <div class="small mb-2">
                        <div><strong>Khách:</strong> {{ $order->customer->name ?? '-' }}</div>
                        <div><strong>Sale:</strong> {{ $order->user->name ?? '-' }}</div>
                        <div><strong>Tổng:</strong> {{ number_format((float) $order->total, 0, ',', '.') }} đ</div>
                        <div><strong>Bước duyệt:</strong> {{ $step?->step?->role_slug ?? 'Không có' }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                        @if($canApprove)
                            <form method="POST" action="{{ route('orders.approve', $order) }}">
                                @csrf
                                <input type="hidden" name="note" value="Leader duyệt từ mobile team orders">
                                <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                            </form>
                            <form method="POST" action="{{ route('orders.reject', $order) }}">
                                @csrf
                                <input type="hidden" name="note" value="Leader từ chối từ mobile team orders">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận từ chối đơn này?')">Từ chối</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-light border text-center text-muted">Không có đơn hàng phù hợp.</div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
