@extends('layouts.warehouse')

@section('title', 'Duyệt điều chỉnh sản lượng')
@section('subtitle', 'Xác nhận thay đổi số lượng hoặc loại hàng sau các bước phê duyệt')

@section('content')
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1"><i class="bi bi-clipboard2-check-fill text-warning me-2"></i>Yêu cầu Kho cần xử lý</h5>
                    <div class="text-muted small">Chỉ hiển thị yêu cầu thay đổi số lượng hoặc loại hàng. Yêu cầu chỉ đổi giá/cân nặng sẽ tự động bỏ qua Kho.</div>
                </div>
                <span class="badge text-bg-warning fs-6">{{ $adjustments->count() }} yêu cầu</span>
            </div>
            <form method="GET" class="row g-2 mt-2">
                <div class="col-md-9"><input name="keyword" value="{{ $keyword }}" class="form-control" placeholder="Tìm mã đơn, khách hàng hoặc mã yêu cầu"></div>
                <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Tìm</button><a href="{{ route('warehouse.order-adjustments.index') }}" class="btn btn-outline-secondary">Xóa lọc</a></div>
            </form>
        </div>
    </div>

    <div class="d-grid gap-3">
    @forelse($adjustments as $adjustment)
        @php
            $waitingWorkflowApproval = $adjustment->status === \App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL;
        @endphp
        <article class="card border-0 shadow-sm" id="adjustment-{{ $adjustment->id }}">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-bold">Yêu cầu #{{ $adjustment->id }} · {{ $adjustment->order?->code ?? ('Đơn #'.$adjustment->order_id) }}</div>
                    <div class="small text-muted">{{ $adjustment->order?->customer?->name ?? 'Khách hàng' }} · Sale: {{ $adjustment->order?->user?->name ?? '—' }} · Gửi bởi: {{ $adjustment->requester?->name ?? '—' }}</div>
                </div>
                <span class="badge {{ $waitingWorkflowApproval ? 'text-bg-warning' : 'text-bg-info' }}">{{ $waitingWorkflowApproval ? 'Chờ Kho duyệt' : 'Chờ xác nhận sản lượng' }}</span>
            </div>
            <div class="card-body">
                @if($adjustment->adjustment_note)<div class="alert alert-light border py-2"><strong>Lý do:</strong> {{ $adjustment->adjustment_note }}</div>@endif
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Sản phẩm</th><th class="text-end">SL cũ</th><th class="text-end">SL mới</th><th class="text-end">Chênh lệch</th><th class="text-end">Khối lượng cũ</th><th class="text-end">Khối lượng mới</th></tr></thead>
                        <tbody>
                        @foreach($adjustment->items as $item)
                            @php $quantityDiff = (int) $item->adjusted_quantity - (int) $item->original_quantity; @endphp
                            <tr>
                                <td><strong>{{ $item->variant?->product?->name ?? 'Sản phẩm' }}</strong><div class="small text-muted">{{ $item->variant?->name ?? '—' }}</div></td>
                                <td class="text-end">{{ number_format((int) $item->original_quantity) }}</td>
                                <td class="text-end fw-bold">{{ number_format((int) $item->adjusted_quantity) }}</td>
                                <td class="text-end fw-bold {{ $quantityDiff === 0 ? 'text-muted' : ($quantityDiff > 0 ? 'text-success' : 'text-danger') }}">{{ $quantityDiff > 0 ? '+' : '' }}{{ number_format($quantityDiff) }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->original_weight, 3, ',', '.'), '0'), ',') }} kg</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->adjusted_weight, 3, ',', '.'), '0'), ',') }} kg</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($waitingWorkflowApproval)
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                        <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">@csrf<button class="btn btn-success btn-sm" onclick="return confirm('Kho duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')">Duyệt bước Kho</button></form>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#warehouseReject{{ $adjustment->id }}">Từ chối</button>
                    </div>
                    <div class="collapse mt-3" id="warehouseReject{{ $adjustment->id }}">
                        <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="border rounded bg-light p-3">@csrf<label class="form-label">Lý do từ chối</label><textarea name="reason" class="form-control mb-2" required></textarea><button class="btn btn-danger btn-sm">Xác nhận từ chối</button></form>
                    </div>
                @else
                    <form method="POST" action="{{ route('site.order-adjustments.warehouse-confirm', $adjustment) }}">
                        @csrf
                        @foreach($adjustment->items as $index => $item)
                            @php $decrease = max((int) $item->original_quantity - (int) $item->adjusted_quantity, 0); @endphp
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            @if($decrease > 0)
                                <div class="row g-2 align-items-end mb-2">
                                    <div class="col-md-4"><label class="form-label small">{{ $item->variant?->product?->name ?? 'Sản phẩm' }} — SL Kho nhận lại</label><input type="number" min="0" max="{{ $decrease }}" name="items[{{ $index }}][warehouse_received_quantity]" value="{{ $decrease }}" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small">Khối lượng nhận (kg)</label><input type="number" min="0" step="0.001" name="items[{{ $index }}][warehouse_received_weight]" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small">Tình trạng hàng</label><input name="items[{{ $index }}][warehouse_condition]" class="form-control" placeholder="Đủ chất lượng / hư hỏng..."></div>
                                </div>
                            @endif
                        @endforeach
                        <div class="mb-2"><label class="form-label">Ghi chú xác nhận của Kho</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <button name="mode" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Kho từ chối yêu cầu này?')">Từ chối</button>
                            <button name="mode" value="confirm_partial" class="btn btn-warning btn-sm">Xác nhận một phần</button>
                            <button name="mode" value="confirm_full" class="btn btn-success btn-sm" onclick="return confirm('Xác nhận sản lượng và hoàn tất điều chỉnh?')">Xác nhận và hoàn tất</button>
                        </div>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5"><i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>Không có yêu cầu sản lượng nào đang chờ Kho.</div></div>
    @endforelse
    </div>
</div>
@endsection
