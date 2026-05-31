@extends(accounting_layout())

@section('title', 'Don Hang')
@section('subtitle', 'Danh sach don hang cua tat ca sale trong ngay')

@section('accounting_content')
@php
    $dailyQtyText = rtrim(rtrim(number_format((float) $dailyTotalItemQuantity, 3, '.', ''), '0'), '.');
    $filteredQtyText = rtrim(rtrim(number_format((float) $filteredItemQuantity, 3, '.', ''), '0'), '.');
@endphp

<div class="acc-kpi mb-3">
    <div class="acc-card p-3">
        <div class="text-muted small">Tong don hang trong ngay</div>
        <div class="h4 mb-0">{{ number_format((int) $dailyTotalOrders) }}</div>
    </div>
    <div class="acc-card p-3">
        <div class="text-muted small">Tong so luong hang hoa trong ngay</div>
        <div class="h4 mb-0">{{ $dailyQtyText }}</div>
    </div>
    <div class="acc-card p-3">
        <div class="text-muted small">So luong tren trang hien tai</div>
        <div class="h4 mb-0">{{ $filteredQtyText }}</div>
    </div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Ngay</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Khach hang</label>
                <select class="form-select" name="customer_id">
                    <option value="0">Tat ca</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $customerId === (int) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sale</label>
                <select class="form-select" name="sale_id">
                    <option value="0">Tat ca sale</option>
                    @foreach($sales as $sale)
                        <option value="{{ $sale->id }}" {{ $saleId === (int) $sale->id ? 'selected' : '' }}>{{ $sale->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Trang thai don</label>
                <input class="form-control" name="status" value="{{ $status }}" placeholder="order_placed...">
            </div>
            <div class="col-md-2">
                <label class="form-label">TT thanh toan</label>
                <input class="form-control" name="payment_status" value="{{ $paymentStatus }}" placeholder="paid/partial/...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tim nhanh</label>
                <input class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Ma don, ten khach, ten sale">
            </div>
            <div class="col-md-2">
                <label class="form-label">So dong/trang</label>
                <select class="form-select" name="per_page">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sap xep</label>
                <select class="form-select" name="sort_by">
                    <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>Ngay tao</option>
                    <option value="code" {{ $sortBy === 'code' ? 'selected' : '' }}>Ma don</option>
                    <option value="total" {{ $sortBy === 'total' ? 'selected' : '' }}>Tong tien</option>
                    <option value="customer_name" {{ $sortBy === 'customer_name' ? 'selected' : '' }}>Khach hang</option>
                    <option value="sale_name" {{ $sortBy === 'sale_name' ? 'selected' : '' }}>Sale</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Chieu sap xep</label>
                <select class="form-select" name="sort_dir">
                    <option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>Giam dan</option>
                    <option value="asc" {{ $sortDir === 'asc' ? 'selected' : '' }}>Tang dan</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Loc danh sach</button>
            </div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body">
        <div class="mb-3 small text-muted">
            Hien thi {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} / {{ $orders->total() }} don.
        </div>

        @forelse($orders as $order)
            @php
                $orderQty = rtrim(rtrim(number_format((float) ($order->total_item_quantity ?? 0), 3, '.', ''), '0'), '.');
                $pendingAdjustments = $order->adjustments->where('status', \App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL);
                $otherAdjustments = $order->adjustments->whereNotIn('status', [\App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL]);
                $isReturnOrder = (bool) ($order->is_return_order ?? false)
                    || (string) ($order->order_type ?? '') === 'order_return'
                    || (string) ($order->workflow_code ?? '') === 'order_return';
            @endphp
            <div class="border rounded mb-3 bg-white overflow-hidden {{ $isReturnOrder ? 'border-danger' : '' }}">
                <div class="d-flex flex-wrap gap-0">
                    {{-- Order card --}}
                    <div class="flex-grow-1 p-3" style="min-width:280px">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div>
                                <div class="fw-bold">
                                    {{ $order->code ?: ('#' . $order->id) }}
                                    @if($isReturnOrder)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Đơn hoàn trả</span>
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    {{ optional($order->created_at)->format('d/m/Y H:i') }}
                                    | Khach: {{ $order->customer?->name ?? '-' }}
                                    | Sale: {{ $order->user?->name ?? '-' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} d</div>
                                <div class="small text-muted">
                                    So luong: {{ $orderQty }}
                                    | TT: {{ $order->status ?? '-' }}
                                    | Thanh toan: {{ $order->payment_status ?? '-' }}
                                </div>
                            </div>
                        </div>

                        @if($order->items->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>San pham</th>
                                            <th>Bien the</th>
                                            <th class="text-end">So luong</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product?->name ?? '-' }}</td>
                                                <td>{{ $item->variant?->name ?? '-' }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Adjustment panel --}}
                    @if($order->adjustments->isNotEmpty())
                        <div class="border-start p-3 bg-light" style="min-width:300px;max-width:420px;flex:0 0 auto">
                            <div class="fw-semibold small mb-2 text-secondary">
                                <i class="fa fa-exchange me-1"></i>Yeu cau dieu chinh
                            </div>

                            @foreach($order->adjustments as $adj)
                                @php
                                    $isPending = $adj->status === \App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL;
                                    $canApprove = $isPending && (
                                        $authUser?->hasRole('admin') ||
                                        $authUser?->hasRole('accountant') ||
                                        app(\App\Services\ApprovalService::class)->canApproveAdjustmentStep($adj, $authUser)
                                    );
                                    $statusColor = match($adj->status) {
                                        'pending_approval' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'completed' => 'primary',
                                        default => 'secondary',
                                    };
                                @endphp
                                <div class="border rounded p-2 mb-2 bg-white small">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">#{{ $adj->id }}</span>
                                        <span class="badge bg-{{ $statusColor }}">{{ $adj->status }}</span>
                                    </div>
                                    <div class="text-muted mb-1">
                                        Nguoi yeu cau: {{ $adj->requester?->name ?? '-' }}
                                        @if($adj->submitted_at)
                                            | {{ $adj->submitted_at->format('d/m H:i') }}
                                        @endif
                                    </div>
                                    @if($adj->adjustment_note)
                                        <div class="mb-1 fst-italic text-muted">"{{ Str::limit($adj->adjustment_note, 80) }}"</div>
                                    @endif

                                    {{-- Items changed --}}
                                    @if($adj->items->isNotEmpty())
                                        <table class="table table-sm mb-1" style="font-size:0.78rem">
                                            <thead><tr>
                                                <th>San pham</th>
                                                <th class="text-end">SL cu</th>
                                                <th class="text-end">SL moi</th>
                                                <th class="text-end">Gia moi</th>
                                            </tr></thead>
                                            <tbody>
                                            @foreach($adj->items as $adjItem)
                                                <tr>
                                                    <td>{{ $adjItem->variant?->product?->name ?? '-' }}</td>
                                                    <td class="text-end">{{ $adjItem->original_quantity }}</td>
                                                    <td class="text-end @if($adjItem->adjusted_quantity < $adjItem->original_quantity) text-danger @elseif($adjItem->adjusted_quantity > $adjItem->original_quantity) text-success @endif">
                                                        {{ $adjItem->adjusted_quantity }}
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $adjItem->adjusted_price, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @endif

                                    @if($canApprove)
                                        <div class="d-flex gap-2 mt-2">
                                            <form action="{{ route('site.order-adjustments.approve', $adj) }}" method="POST" class="flex-grow-1">
                                                @csrf
                                                <input type="hidden" name="note" value="">
                                                <button type="submit" class="btn btn-success btn-sm w-100"
                                                        onclick="return confirm('Duyet yeu cau dieu chinh #{{ $adj->id }}?')">
                                                    <i class="fa fa-check me-1"></i>Duyet
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm flex-grow-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectAdjModal{{ $adj->id }}">
                                                <i class="fa fa-times me-1"></i>Tu choi
                                            </button>
                                        </div>
                                    @endif

                                    @if($adj->reject_reason)
                                        <div class="text-danger mt-1 small">Ly do tu choi: {{ $adj->reject_reason }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reject modals (outside card) --}}
            @foreach($order->adjustments->where('status', \App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL) as $adj)
                @php
                    $canApprove = $authUser?->hasRole('admin') || $authUser?->hasRole('accountant') ||
                        app(\App\Services\ApprovalService::class)->canApproveAdjustmentStep($adj, $authUser);
                @endphp
                @if($canApprove)
                <div class="modal fade" id="rejectAdjModal{{ $adj->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('site.order-adjustments.reject', $adj) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h6 class="modal-title">Tu choi yeu cau dieu chinh #{{ $adj->id }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label fw-semibold">Ly do tu choi <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Nhap ly do..."></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Huy</button>
                                    <button type="submit" class="btn btn-danger btn-sm">Xac nhan tu choi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        @empty
            <div class="text-center text-muted py-4">Khong co don hang nao trong bo loc hien tai.</div>
        @endforelse

        {{ $orders->links() }}
    </div>
</div>
@endsection
