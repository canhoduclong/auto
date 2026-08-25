@extends('layouts.warehouse')

@section('title', $slip->code)

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div><a href="{{ route('warehouse.dispatch-slips.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Danh sách phiếu</a><h4 class="fw-bold mt-1 mb-1">{{ $slip->code }}</h4><div class="text-muted">{{ $slip->sourceWarehouse?->name }} → {{ $slip->targetWarehouse?->name }} · {{ $slip->business_date->format('d/m/Y') }}</div></div>
    <div class="d-flex gap-2 flex-wrap">
        <a target="_blank" href="{{ route('warehouse.dispatch-slips.print-export', $slip) }}" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>In phiếu xuất tổng</a>
        <a target="_blank" href="{{ route('warehouse.dispatch-slips.print-import', $slip) }}" class="btn btn-outline-success"><i class="bi bi-printer me-1"></i>In phiếu nhập tổng</a>
        @if($slip->status === 'draft' && (!auth()->user()->warehouse_id || (int) auth()->user()->warehouse_id === (int) $slip->source_warehouse_id))
            <form method="POST" action="{{ route('warehouse.dispatch-slips.finalize', $slip) }}" onsubmit="return confirm('Chốt phiếu và khóa danh sách bàn giao?');">@csrf<button class="btn btn-success fw-bold">Chốt phiếu</button></form>
            <form method="POST" action="{{ route('warehouse.dispatch-slips.destroy', $slip) }}" onsubmit="return confirm('Xóa phiếu đang mở này?');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Xóa</button></form>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Trạng thái chứng từ</div><div class="fw-bold {{ $slip->status === 'draft' ? 'text-warning' : 'text-success' }}">{{ $slip->status === 'draft' ? 'Đang mở' : 'Đã chốt' }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Tài xế điều chuyển</div><div class="fw-bold">{{ $slip->shipper?->short_name ?: $slip->shipper?->name }}</div><div class="small">{{ $slip->shipper?->phone }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Tiến độ kho nhận</div><div class="fw-bold">{{ $slip->progress_label }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Người lập/chốt</div><div class="fw-bold">{{ $slip->creator?->name }}</div><div class="small">{{ $slip->finalizer?->name ?: 'Chưa chốt' }}</div></div></div></div>
</div>

@if($slip->notes)<div class="alert alert-light border"><strong>Ghi chú bàn giao:</strong> {{ $slip->notes }}</div>@endif

<div class="card mb-3"><div class="card-header bg-white fw-bold">Danh sách đơn hoàn thiện</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>STT</th><th>Mã đơn/Khách hàng</th><th>Sale</th><th class="text-end">KL bàn giao</th><th class="text-end">KL nhận</th><th>Trạng thái</th></tr></thead><tbody>
@forelse($orderRows as $row)<tr><td>{{ $loop->iteration }}</td><td><strong>{{ $row['code'] }}</strong><div class="small text-muted">{{ $row['customer_name'] }}</div></td><td>{{ $row['sale_name'] }}</td><td class="text-end">{{ number_format($row['packed_weight'], 3, ',', '.') }} kg</td><td class="text-end">{{ $row['movement']?->received_total_weight !== null ? number_format((float) $row['movement']->received_total_weight, 3, ',', '.').' kg' : '—' }}</td><td><span class="badge {{ $row['received'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $row['received'] ? 'Đã tiếp nhận' : 'Chưa tiếp nhận' }}</span></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-3">Phiếu không có đơn hoàn thiện.</td></tr>@endforelse
</tbody></table></div></div>

<div class="card"><div class="card-header bg-white fw-bold">Tổng hợp hàng hóa trên phiếu</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Sản phẩm</th><th>SKU/Size</th><th class="text-end">SL xuất</th><th class="text-end">KL xuất</th><th class="text-end">SL đã nhận</th><th class="text-end">KL đã nhận</th></tr></thead><tbody>
@forelse($summaryRows as $row)<tr><td><strong>{{ $row['product_name'] }}</strong></td><td>{{ $row['sku'] ?: '—' }} / {{ $row['size'] ?: '—' }}</td><td class="text-end">{{ number_format($row['quantity']) }}</td><td class="text-end">{{ number_format($row['weight'], 3, ',', '.') }} kg</td><td class="text-end">{{ $row['received_quantity'] === null ? '—' : number_format($row['received_quantity']) }}</td><td class="text-end">{{ $row['received_weight'] === null ? '—' : number_format($row['received_weight'], 3, ',', '.').' kg' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-3">Chưa có hàng hóa.</td></tr>@endforelse
</tbody><tfoot class="table-light fw-bold"><tr><td colspan="2">Tổng cộng</td><td class="text-end">{{ number_format($summaryRows->sum('quantity')) }}</td><td class="text-end">{{ number_format((float) $summaryRows->sum('weight'), 3, ',', '.') }} kg</td><td class="text-end">{{ number_format((int) $summaryRows->sum(fn($row) => $row['received_quantity'] ?? 0)) }}</td><td class="text-end">{{ number_format((float) $summaryRows->sum(fn($row) => $row['received_weight'] ?? 0), 3, ',', '.') }} kg</td></tr></tfoot></table></div></div>
@endsection
