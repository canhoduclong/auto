@extends('layouts.procurement')
@section('title', 'Dashboard thu mua')
@section('subtitle', 'Trại sắp có hàng, thu mua hôm nay và chuyển nhập kho')
@push('styles')
<style>.farm-card{cursor:pointer;border:2px solid transparent;transition:.15s}.farm-card.selected{border-color:#16a34a;background:#f0fdf4;box-shadow:0 0 0 3px #16a34a22}.money{font-weight:800;color:#b91c1c}</style>
@endpush
@section('content')
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Trại có hàng / sắp có hàng trong 7 ngày</strong><div class="small text-muted">Dựa trên lần bắt gần nhất + 39–45 ngày. Chọn một trại để mở form thu mua.</div></div>
            <div class="card-body"><div class="row g-2">
                @forelse($farms as $farm)
                    <div class="col-md-6"><div class="farm-card card h-100 p-3" data-farm-id="{{ $farm->id }}" data-farm-name="{{ $farm->name }}">
                        <div class="d-flex justify-content-between"><strong>{{ $farm->name }}</strong><span class="badge {{ $farm->availability === 'overdue' ? 'bg-danger' : ($farm->availability === 'soon' ? 'bg-success' : 'bg-secondary') }}">{{ $farm->availability === 'overdue' ? 'Đã tới kỳ' : ($farm->availability === 'soon' ? 'Sắp có hàng' : 'Chưa có lịch sử') }}</span></div>
                        <div class="small text-muted mt-2"><i class="bi bi-geo-alt"></i> {{ $farm->address ?: 'Chưa có địa chỉ' }}</div>
                        <div class="small">{{ $farm->phone ?: '—' }} · Quy mô {{ number_format($farm->scale ?? 0) }} con · {{ $farm->duck_breed ?: 'Chưa rõ giống' }}</div>
                        <div class="small text-success mt-1">Dự kiến: {{ $farm->available_from?->format('d/m/Y') ?? '—' }} – {{ $farm->available_to?->format('d/m/Y') ?? '—' }}</div>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2">Chọn trại này</button>
                    </div></div>
                @empty
                    <div class="col-12 text-center py-4"><div class="text-muted mb-3">Chưa có trang trại phù hợp trong danh sách.</div><button type="button" class="btn btn-primary" data-purchase-form-toggle><i class="bi bi-plus-circle me-1"></i>Tạo thu mua</button></div>
                @endforelse
            </div></div>
        </div>
    </div>
    <div class="col-lg-5"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><strong>Thống kê hôm nay</strong></div><div class="card-body row text-center g-3"><div class="col-6"><div class="fs-2 fw-bold">{{ $todayPurchases->count() }}</div><small>lần thu mua</small></div><div class="col-6"><div class="fs-2 fw-bold">{{ number_format($todayPurchases->sum('quantity')) }}</div><small>con vịt</small></div><div class="col-6"><div class="fs-4 fw-bold">{{ number_format($todayPurchases->sum('total_weight'), 1) }}</div><small>kg</small></div><div class="col-6"><div class="fs-4 money">{{ number_format($todayPurchases->sum('total_amount')) }}đ</div><small>tổng chi</small></div></div></div></div>
</div>

@include('procurement.partials.purchase_form')

<div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Nhật ký thu mua hôm nay</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mã</th><th>Nguồn mua</th><th>Loại</th><th>SL/Kg/Size TB</th><th>Thành tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Gửi nhập kho</th></tr></thead><tbody>
@forelse($todayPurchases as $p)
    <tr><td class="fw-bold">{{ $p->code }}</td><td>{{ $p->farm?->name ?? $p->supplier?->name }}</td><td>{{ $p->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt thịt' }}</td><td>{{ number_format($p->quantity) }} con / {{ number_format($p->total_weight, 1) }}kg / {{ number_format($p->average_weight, 2) }}</td><td>{{ number_format($p->total_amount) }}đ</td><td>{{ $p->payment_status }}</td><td><span class="badge bg-secondary">{{ $p->status }}</span></td><td>@if($p->status === 'draft')<form class="d-flex gap-1" method="POST" action="{{ route('procurement.purchases.send-warehouse', $p) }}">@csrf<select class="form-select form-select-sm" name="warehouse_id" required><option value="">Chọn kho</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select><button class="btn btn-sm btn-success">Gửi</button></form>@else {{ $p->warehouse?->name ?? '—' }} @endif</td></tr>
@empty
    <tr><td colspan="8" class="text-center text-muted py-4">Chưa có lần thu mua hôm nay.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
