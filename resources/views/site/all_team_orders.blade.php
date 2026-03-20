@extends('layouts.site')

@section('content')
<style>
    .mto-hero {
        background: linear-gradient(120deg, #5b1f7a 0%, #8142a8 55%, #4a6fd1 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.4rem 1.2rem;
        margin-bottom: 1rem;
    }
    .mto-kpi,
    .mto-filter,
    .mto-table-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06);
    }
    .mto-kpi .value { font-weight: 700; font-size: 1.3rem; }
    .mto-code { font-weight: 700; color: #4d2a8a; }
    .mto-status {
        display: inline-block;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 600;
        padding: .26rem .56rem;
    }
    .mto-status-pending { background: #fff3cd; color: #7c5a00; }
    .mto-status-approved { background: #d1e7dd; color: #0f5132; }
    .mto-status-rejected { background: #f8d7da; color: #842029; }
    .mto-status-default { background: #e2e3e5; color: #41464b; }
    .mto-mobile-card {
        border: 0;
        border-left: 4px solid #6a3fb3;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(17, 24, 39, 0.06);
    }
    .mto-actions form { display: inline-block; }

    @media (max-width: 767.98px) {
        .mto-desktop { display: none; }
    }
    @media (min-width: 768px) {
        .mto-mobile { display: none; }
    }
</style>

<div class="container py-3">
    <div class="mto-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Duyệt Đơn PKD</h4>
                <div class="opacity-75">Dành cho manager: duyệt đơn của toàn bộ sale và leader</div>
            </div>
            <div class="small opacity-75">Manager: {{ $user->name }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card mto-kpi">
                <div class="card-body">
                    <div class="text-muted small">Tổng đơn</div>
                    <div class="value text-primary">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card mto-kpi">
                <div class="card-body">
                    <div class="text-muted small">Chờ manager duyệt</div>
                    <div class="value text-warning">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card mto-kpi">
                <div class="card-body">
                    <div class="text-muted small">Đã duyệt</div>
                    <div class="value text-success">{{ number_format($stats['approved']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mto-filter mb-3">
        <div class="card-body">
            <h6 class="mb-3">Duyệt đơn tự động (Manager)</h6>
            <form method="POST" action="{{ route('pages.all_tearm_orders.auto_approve') }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">
                <input type="hidden" name="team_id" value="{{ request('team_id') }}">

                <div class="col-12">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="condition_item_qty" name="condition_item_qty" value="1" {{ old('condition_item_qty') ? 'checked' : '' }}>
                        <label class="form-check-label" for="condition_item_qty">Điều kiện theo số lượng sản phẩm</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">SL tối thiểu</label>
                    <input type="number" min="1" class="form-control" name="min_item_qty" value="{{ old('min_item_qty') }}" placeholder="Ví dụ: 10">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">SL tối đa (tuỳ chọn)</label>
                    <input type="number" min="1" class="form-control" name="max_item_qty" value="{{ old('max_item_qty') }}" placeholder="Ví dụ: 200">
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="condition_sale_price" name="condition_sale_price" value="1" {{ old('condition_sale_price') ? 'checked' : '' }}>
                        <label class="form-check-label" for="condition_sale_price">Điều kiện theo giá bán (giá trị đơn hàng)</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Giá bán tối thiểu</label>
                    <input type="number" min="0" step="1000" class="form-control" name="min_sale_price" value="{{ old('min_sale_price') }}" placeholder="Ví dụ: 1000000">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Giá bán tối đa (tuỳ chọn)</label>
                    <input type="number" min="0" step="1000" class="form-control" name="max_sale_price" value="{{ old('max_sale_price') }}" placeholder="Ví dụ: 5000000">
                </div>

                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Duyệt đơn tự động</button>
                </div>
            </form>
            <div class="small text-muted mt-2">
                Chỉ duyệt các đơn đã qua leader và đang ở bước manager duyệt.
            </div>
        </div>
    </div>

    <div class="card mto-filter mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('pages.all_tearm_orders') }}" class="row g-2 align-items-end">
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
                    <label class="form-label mb-1">Team</label>
                    <select class="form-select" name="team_id">
                        <option value="">Tất cả team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Trạng thái đơn</label>
                    <input type="text" class="form-control" name="status" value="{{ request('status') }}" placeholder="vd: pending_manager_approval">
                </div>
                <div class="col-12 col-md-1 d-flex">
                    <button class="btn btn-primary w-100" type="submit">Lọc</button>
                </div>
                <div class="col-12 d-flex gap-2">
                    <a href="{{ route('pages.all_tearm_orders') }}" class="btn btn-outline-secondary btn-sm">Hôm nay</a>
                    <div class="form-check ms-2 mt-1">
                        <input class="form-check-input" type="checkbox" name="pending_only" id="pending_only" value="1" {{ $pendingOnly ? 'checked' : '' }}>
                        <label class="form-check-label" for="pending_only">Chỉ hiển thị đơn đang tới lượt tôi duyệt</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mto-table-card mto-desktop">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Người tạo đơn</th>
                        <th>Team</th>
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
                                'pending_manager_approval' => 'mto-status-pending',
                                'approved' => 'mto-status-approved',
                                'rejected' => 'mto-status-rejected',
                                default => 'mto-status-default',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="mto-code">{{ $order->code }}</div>
                                <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $order->customer->phone ?? '' }}</div>
                            </td>
                            <td>{{ $order->user->name ?? '-' }}</td>
                            <td>{{ $order->user?->team?->name ?? '-' }}</td>
                            <td class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                            <td><span class="mto-status {{ $statusClass }}">{{ $order->status }}</span></td>
                            <td>
                                @if($step && $step->step)
                                    <span class="badge bg-info-subtle text-info border">
                                        B{{ $step->step->step_order }} - {{ $step->step->role_slug }}
                                    </span>
                                @else
                                    <span class="text-muted small">Không có bước chờ</span>
                                @endif
                            </td>
                            <td class="text-end mto-actions">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                @if($canApprove)
                                    <form method="POST" action="{{ route('orders.approve', $order) }}" class="ms-1">
                                        @csrf
                                        <input type="hidden" name="note" value="Manager duyệt từ trang all team orders">
                                        <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('orders.reject', $order) }}" class="ms-1">
                                        @csrf
                                        <input type="hidden" name="note" value="Manager từ chối từ trang all team orders">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận từ chối đơn này?')">Từ chối</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Không có đơn hàng phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mto-mobile">
        @forelse($orders as $order)
            @php
                $step = $currentStepByOrder[$order->id] ?? null;
                $canApprove = $canApproveByOrder[$order->id] ?? false;
            @endphp
            <div class="card mto-mobile-card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="mto-code">{{ $order->code }}</div>
                            <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="mto-status mto-status-default">{{ $order->status }}</span>
                    </div>
                    <div class="small mb-2">
                        <div><strong>Khách:</strong> {{ $order->customer->name ?? '-' }}</div>
                        <div><strong>Người tạo:</strong> {{ $order->user->name ?? '-' }}</div>
                        <div><strong>Team:</strong> {{ $order->user?->team?->name ?? '-' }}</div>
                        <div><strong>Tổng:</strong> {{ number_format((float) $order->total, 0, ',', '.') }} đ</div>
                        <div><strong>Bước duyệt:</strong> {{ $step?->step?->role_slug ?? 'Không có' }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                        @if($canApprove)
                            <form method="POST" action="{{ route('orders.approve', $order) }}">
                                @csrf
                                <input type="hidden" name="note" value="Manager duyệt từ mobile all team orders">
                                <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                            </form>
                            <form method="POST" action="{{ route('orders.reject', $order) }}">
                                @csrf
                                <input type="hidden" name="note" value="Manager từ chối từ mobile all team orders">
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
