@extends('layouts.procurement')
@section('title', 'Dashboard thu mua')
@section('subtitle', 'Trại sắp có hàng, thu mua hôm nay và chuyển nhập kho')
@push('styles')
<style>.farm-card{cursor:pointer}.farm-card td{transition:.15s}.farm-card:hover td{background:#fffbeb}.farm-card.selected td{background:#f0fdf4!important;box-shadow:inset 0 2px 0 #16a34a,inset 0 -2px 0 #16a34a}.sortable-heading{border:0;background:transparent;padding:0;font-weight:700;white-space:nowrap}.sortable-heading:hover{color:#92400e}.money{font-weight:800;color:#b91c1c}</style>
@endpush
@section('content')
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><strong>Thống kê hôm nay</strong></div><div class="card-body row text-center g-3"><div class="col-6 col-lg-3"><div class="fs-2 fw-bold">{{ $todayPurchases->count() }}</div><small>lần thu mua</small></div><div class="col-6 col-lg-3"><div class="fs-2 fw-bold">{{ number_format($todayPurchases->sum('quantity')) }}</div><small>con vịt</small></div><div class="col-6 col-lg-3"><div class="fs-4 fw-bold">{{ number_format($todayPurchases->sum('total_weight'), 1) }}</div><small>kg</small></div><div class="col-6 col-lg-3"><div class="fs-4 money">{{ number_format($todayPurchases->sum('total_amount')) }}đ</div><small>tổng chi</small></div></div></div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Trại có vịt đang nuôi từ 39–50 ngày</strong><div class="small text-muted">Chỉ hiển thị các trại có tuổi đàn từ 39 đến 50 ngày, tính từ lần bắt gần nhất. Bấm tiêu đề có biểu tượng mũi tên để sắp xếp; bấm một dòng để thu mua.</div></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr>
        <th>Trang trại</th><th>Liên hệ / Quy mô</th>
        <th><button type="button" class="sortable-heading" data-farm-sort="lastDate">Lần bắt gần nhất <i class="bi bi-arrow-down-up ms-1"></i></button></th>
        <th><button type="button" class="sortable-heading" data-farm-sort="age">Số ngày vịt đang nuôi <i class="bi bi-arrow-down-up ms-1"></i></button></th>
        <th><button type="button" class="sortable-heading" data-farm-sort="availableDate">Ngày dự kiến có hàng <i class="bi bi-arrow-down-up ms-1"></i></button></th>
        <th>Trạng thái</th><th></th>
    </tr></thead><tbody id="farmTableBody">
        @forelse($farms as $farm)
            <tr class="farm-card" data-farm-id="{{ $farm->id }}" data-farm-name="{{ $farm->name }}" data-last-date="{{ $farm->last_purchase_at?->timestamp ?? '' }}" data-age="{{ $farm->raising_age_days ?? '' }}" data-available-date="{{ $farm->available_from?->timestamp ?? '' }}">
                <td><strong>{{ $farm->name }}</strong><div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $farm->address ?: 'Chưa có địa chỉ' }}</div><div class="small">{{ $farm->duck_breed ?: 'Chưa rõ giống' }}</div></td>
                <td>{{ $farm->phone ?: '—' }}<div class="small text-muted">{{ number_format($farm->scale ?? 0) }} con</div></td>
                <td>{{ $farm->last_purchase_at?->format('d/m/Y') ?? '—' }}</td>
                <td>@if($farm->raising_age_days !== null)<strong>{{ $farm->raising_age_days }} ngày</strong><div class="small text-muted">Chu kỳ chuẩn {{ $farm->raising_days }} ngày</div>@else — @endif</td>
                <td>@if($farm->available_from)<span class="text-success">{{ $farm->available_from->format('d/m/Y') }}</span><div class="small text-muted">đến {{ $farm->available_to->format('d/m/Y') }}</div>@else — @endif</td>
                <td><span class="badge {{ $farm->availability === 'overdue' ? 'bg-danger' : ($farm->availability === 'soon' ? 'bg-success' : 'bg-secondary') }}">{{ $farm->availability === 'overdue' ? 'Đã tới kỳ' : ($farm->availability === 'soon' ? 'Sắp có hàng' : 'Chưa có lịch sử') }}</span></td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-success text-nowrap">Chọn trại</button></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4"><div class="text-muted mb-3">Không có trang trại nào đang nuôi vịt trong khoảng 39–50 ngày.</div><button type="button" class="btn btn-primary" data-purchase-form-toggle><i class="bi bi-plus-circle me-1"></i>Tạo thu mua</button></td></tr>
        @endforelse
    </tbody></table></div>
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
@push('scripts')
<script>
(() => {
    const body = document.getElementById('farmTableBody');
    if (!body) return;
    let activeSort = 'availableDate';
    let direction = 1;
    document.querySelectorAll('[data-farm-sort]').forEach(button => button.addEventListener('click', () => {
        const key = button.dataset.farmSort;
        direction = activeSort === key ? direction * -1 : 1;
        activeSort = key;
        const rows = [...body.querySelectorAll('tr.farm-card')];
        rows.sort((left, right) => {
            const leftValue = left.dataset[key] === '' ? Number.POSITIVE_INFINITY : Number(left.dataset[key]);
            const rightValue = right.dataset[key] === '' ? Number.POSITIVE_INFINITY : Number(right.dataset[key]);
            return (leftValue - rightValue) * direction;
        });
        rows.forEach(row => body.appendChild(row));
        document.querySelectorAll('[data-farm-sort] i').forEach(icon => icon.className = 'bi bi-arrow-down-up ms-1');
        button.querySelector('i').className = `bi ${direction === 1 ? 'bi-sort-up' : 'bi-sort-down'} ms-1`;
    }));
})();
</script>
@endpush
