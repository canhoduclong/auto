@extends('layouts.ceo')

@section('title', 'Phiếu xuất kho tổng')
@section('subtitle', 'Tra cứu và in phiếu từ Kho')

@push('styles')
<style>
.dispatch-hero,.dispatch-panel{border:1px solid #dbe5e3;border-radius:12px;background:#fff}.dispatch-hero{padding:18px;background:linear-gradient(135deg,#0f766e,#115e59);color:#fff}.dispatch-panel{padding:16px}.dispatch-slip-card{border:1px solid #dbe5e3;border-left:4px solid #0f766e;border-radius:10px;padding:13px;background:#fff}.dispatch-progress{height:7px;border-radius:99px;background:#e2e8f0;overflow:hidden}.dispatch-progress>span{display:block;height:100%;background:#0f766e}.dispatch-actions{border-top:1px solid #eef2f7;padding-top:10px;margin-top:10px}.dispatch-empty{padding:24px;text-align:center;color:#64748b}
</style>
@endpush

@section('content')
<div class="dispatch-hero mb-3">
    <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Phiếu xuất kho tổng</h4>
    <div class="small opacity-75">Tra cứu, xem chi tiết và in phiếu xuất kho tổng do Kho lập.</div>
</div>

<div class="dispatch-panel">
    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-2"><label class="small">Từ ngày</label><input type="date" name="from_date" value="{{ $from }}" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="small">Đến ngày</label><input type="date" name="to_date" value="{{ $to }}" class="form-control form-control-sm"></div>
        <div class="col-md-3"><label class="small">Kho xuất</label><select name="source_warehouse_id" class="form-select form-select-sm"><option value="">Tất cả kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int) request('source_warehouse_id') === (int) $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="small">Trạng thái</label><select name="status" class="form-select form-select-sm"><option value="">Tất cả</option><option value="draft" @selected(request('status') === 'draft')>Đang mở</option><option value="finalized" @selected(request('status') === 'finalized')>Đã chốt</option></select></div>
        <div class="col-md-2"><label class="small">Mã phiếu</label><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="PXKT-..."></div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary btn-sm">Lọc</button></div>
    </form>

    <div class="d-grid gap-2">
        @forelse($slips as $slip)
            @php $percent = $slip->entry_total ? round($slip->entry_received * 100 / $slip->entry_total) : 0; @endphp
            <div class="dispatch-slip-card">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div><a class="fw-bold text-decoration-none" href="{{ route('ceo.warehouse-dispatch-slips.show', $slip) }}">{{ $slip->code }}</a><div class="small text-muted">{{ $slip->business_date->format('d/m/Y') }} · {{ $slip->sourceWarehouse?->name }} → {{ $slip->targetWarehouse?->name }}</div></div>
                    <span class="badge {{ $slip->status === 'draft' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $slip->status === 'draft' ? 'Đang mở' : 'Đã chốt' }}</span>
                </div>
                <div class="row g-2 mt-1 small"><div class="col-md-4">Tài xế: <strong>{{ $slip->shipper?->short_name ?: $slip->shipper?->name }}</strong></div><div class="col-md-4">Nội dung: <strong>{{ $slip->entry_total }} mục</strong></div><div class="col-md-4">{{ $slip->progress_label }}</div></div>
                <div class="dispatch-progress mt-2"><span style="width:{{ $percent }}%"></span></div>
                <div class="dispatch-actions d-flex gap-2 flex-wrap">
                    <a href="{{ route('ceo.warehouse-dispatch-slips.show', $slip) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>Xem chi tiết</a>
                    <a target="_blank" href="{{ route('ceo.warehouse-dispatch-slips.print-export', $slip) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>In phiếu xuất</a>
                </div>
            </div>
        @empty
            <div class="dispatch-empty">Chưa có phiếu trong điều kiện tra cứu.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $slips->links() }}</div>
</div>
@endsection
