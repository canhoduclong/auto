@extends($layout ?? 'layouts.warehouse')

@section('title', $slip->code)

@section('content')
@php
    $formatKg = static fn (float|int|string $value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',').' kg';
    $dispatchRoutePrefix = $dispatchRoutePrefix ?? 'warehouse.dispatch-slips';
    $readOnly = $readOnly ?? false;
@endphp
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div><a href="{{ route($dispatchRoutePrefix.'.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Danh sách phiếu</a><h4 class="fw-bold mt-1 mb-1">{{ $slip->code }}</h4><div class="text-muted">{{ $slip->sourceWarehouse?->name }} → {{ $slip->targetWarehouse?->name }} · {{ $slip->business_date->format('d/m/Y') }}</div></div>
    <div class="d-flex gap-2 flex-wrap">
        <a target="_blank" href="{{ route($dispatchRoutePrefix.'.print-export', $slip) }}" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>In phiếu xuất tổng</a>
        @unless($readOnly)
        <a target="_blank" href="{{ route($dispatchRoutePrefix.'.print-import', $slip) }}" class="btn btn-outline-success"><i class="bi bi-printer me-1"></i>In phiếu nhập tổng</a>
        @if($slip->status === 'draft' && (auth()->user()->hasRole('admin') || !auth()->user()->warehouse_id || (int) auth()->user()->warehouse_id === (int) $slip->source_warehouse_id))
            <a href="{{ route($dispatchRoutePrefix.'.edit', $slip) }}" class="btn btn-warning"><i class="bi bi-pencil-square me-1"></i>Sửa phiếu</a>
            <form method="POST" action="{{ route($dispatchRoutePrefix.'.finalize', $slip) }}" onsubmit="return confirm('Chốt phiếu và khóa danh sách bàn giao?');">@csrf<button class="btn btn-success fw-bold">Chốt phiếu</button></form>
            <form method="POST" action="{{ route($dispatchRoutePrefix.'.destroy', $slip) }}" onsubmit="return confirm('Xóa phiếu đang mở này?');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Xóa</button></form>
        @endif
        @endunless
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Trạng thái chứng từ</div><div class="fw-bold {{ $slip->status === 'draft' ? 'text-warning' : 'text-success' }}">{{ $slip->status === 'draft' ? 'Đang mở' : 'Đã chốt' }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Tài xế điều chuyển</div><div class="fw-bold">{{ $slip->shipper?->short_name ?: $slip->shipper?->name }}</div><div class="small">{{ $slip->shipper?->phone }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Tiến độ kho nhận</div><div class="fw-bold">{{ $slip->progress_label }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Người lập/chốt</div><div class="fw-bold">{{ $slip->creator?->name }}</div><div class="small">{{ $slip->finalizer?->name ?: 'Chưa chốt' }}</div></div></div></div>
</div>

@if($slip->notes)<div class="alert alert-light border"><strong>Ghi chú bàn giao:</strong> {{ $slip->notes }}</div>@endif

<div class="card mb-3"><div class="card-header bg-white fw-bold">Danh sách đơn hoàn thiện</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>STT</th><th>Khách hàng / Mã đơn</th><th>Sale</th><th>Size</th><th class="text-end">Số lượng hàng</th><th class="text-end">KL bàn giao</th><th class="text-end">KL nhận</th><th>Trạng thái</th></tr></thead><tbody>
@forelse($orderRows as $row)<tr><td>{{ $loop->iteration }}</td><td><div class="fs-5 fw-bold lh-sm text-dark">{{ $row['customer_name'] ?: 'Không rõ khách hàng' }}</div><div class="small fw-normal text-muted mt-1">Mã đơn: {{ $row['code'] }}</div></td><td>{{ $row['sale_name'] }}</td><td class="fw-semibold">{{ $row['sizes'] }}</td><td class="text-end fw-bold text-nowrap">{{ number_format($row['item_quantity']) }}</td><td class="text-end fw-semibold text-nowrap">{{ $formatKg($row['packed_weight']) }}</td><td class="text-end text-nowrap">{{ $row['movement']?->received_total_weight !== null ? $formatKg($row['movement']->received_total_weight) : '—' }}</td><td><span class="badge {{ $row['received'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $row['received'] ? 'Đã tiếp nhận' : 'Chưa tiếp nhận' }}</span></td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-3">Phiếu không có đơn hoàn thiện.</td></tr>@endforelse
</tbody></table></div></div>

<div class="card"><div class="card-header bg-white fw-bold">Tổng hợp hàng hóa trên phiếu</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Sản phẩm</th><th>SKU/Size</th><th class="text-end">SL xuất</th><th class="text-end">KL xuất</th><th class="text-end">SL đã nhận</th><th class="text-end">KL đã nhận</th></tr></thead><tbody>
@forelse($summaryRows as $row)<tr><td><strong>{{ $row['product_name'] }}</strong></td><td>{{ $row['sku'] ?: '—' }} / {{ $row['size'] ?: '—' }}</td><td class="text-end">{{ number_format($row['quantity']) }}</td><td class="text-end text-nowrap">{{ $formatKg($row['weight']) }}</td><td class="text-end">{{ $row['received_quantity'] === null ? '—' : number_format($row['received_quantity']) }}</td><td class="text-end text-nowrap">{{ $row['received_weight'] === null ? '—' : $formatKg($row['received_weight']) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-3">Chưa có hàng hóa.</td></tr>@endforelse
</tbody><tfoot class="table-light fw-bold"><tr><td colspan="2">Tổng cộng</td><td class="text-end">{{ number_format($summaryRows->sum('quantity')) }}</td><td class="text-end text-nowrap">{{ $formatKg($summaryRows->sum('weight')) }}</td><td class="text-end">{{ number_format((int) $summaryRows->sum(fn($row) => $row['received_quantity'] ?? 0)) }}</td><td class="text-end text-nowrap">{{ $formatKg($summaryRows->sum(fn($row) => $row['received_weight'] ?? 0)) }}</td></tr></tfoot></table></div></div>
@endsection
