@extends('layouts.procurement')
@section('title', 'Bảng điều hành phòng thu mua')
@section('subtitle', 'Hiệu quả thu mua, chất lượng nhập kho và chi phí theo từng chuyến')
@push('styles')
<style>
    .dashboard-shell{--navy:#12356b;--green:#168a55;--red:#cf3f32;--muted:#64748b}.dash-filter,.dash-panel,.kpi-card{background:#fff;border:1px solid #dbe3ee;border-radius:10px}.dash-title{color:var(--navy);font-weight:900;letter-spacing:.02em}.section-bar{background:var(--navy);color:#fff;text-align:center;text-transform:uppercase;font-weight:800;font-size:.78rem;padding:.42rem .75rem;border-radius:8px 8px 0 0;letter-spacing:.03em}.kpi-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.65rem}.kpi-card{padding:.85rem;position:relative;overflow:hidden}.kpi-card:before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--kpi-color,#168a55)}.kpi-icon{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:color-mix(in srgb,var(--kpi-color,#168a55) 14%,white);color:var(--kpi-color,#168a55);font-size:1.15rem}.kpi-label{font-size:.7rem;text-transform:uppercase;color:#64748b;font-weight:800}.kpi-value{font-size:1.25rem;font-weight:900;color:#16243a;line-height:1.15}.kpi-note{font-size:.68rem;color:#64748b}.dash-grid{display:grid;grid-template-columns:1.35fr 1fr .8fr;gap:.65rem}.dash-grid-two{display:grid;grid-template-columns:1.15fr .85fr;gap:.65rem}.panel-body{padding:.85rem}.chart-box{height:235px;position:relative}.chart-box-sm{height:185px;position:relative}.metric-table{font-size:.76rem}.metric-table td,.metric-table th{padding:.4rem .5rem}.stars{color:#e9a008;letter-spacing:1px}.alert-line{display:flex;gap:.55rem;padding:.46rem 0;border-bottom:1px solid #eef2f7;font-size:.78rem}.alert-line:last-child{border:0}.farm-card{cursor:pointer}.farm-card:hover td{background:#fffbeb}.farm-card.selected td{background:#f0fdf4!important}.sortable-heading{border:0;background:transparent;padding:0;font-weight:700;white-space:nowrap}.status-label{font-size:.68rem}.trip-cost{font-size:.7rem;color:#64748b}.quality-number{font-size:1.6rem;font-weight:900;color:var(--navy)}
    @media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(3,1fr)}.dash-grid{grid-template-columns:1fr 1fr}.dash-grid>:first-child{grid-column:1/-1}}
    @media(max-width:768px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.dash-grid,.dash-grid-two{grid-template-columns:1fr}.dash-grid>:first-child{grid-column:auto}.chart-box{height:220px}}
</style>
@endpush
@section('content')
<div class="dashboard-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div><h4 class="dash-title mb-1"><i class="bi bi-bar-chart-line-fill me-2"></i>BẢNG ĐIỀU HÀNH PHÒNG THU MUA</h4><div class="small text-muted">Dữ liệu từ {{ $from->format('d/m/Y') }} đến {{ $to->format('d/m/Y') }} · cập nhật {{ now()->format('H:i d/m/Y') }}</div></div>
        <form class="dash-filter p-2 d-flex align-items-end gap-2 flex-wrap" method="GET">
            <div><label class="small fw-semibold d-block">Mốc thời gian</label><input type="date" class="form-control form-control-sm" name="date" value="{{ $referenceDate->toDateString() }}"></div>
            <div><label class="small fw-semibold d-block">Kỳ xem</label><select class="form-select form-select-sm" name="period"><option value="day" @selected($period==='day')>Theo ngày</option><option value="week" @selected($period==='week')>Theo tuần</option><option value="month" @selected($period==='month')>Theo tháng</option></select></div>
            <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Làm mới</button>
        </form>
    </div>

    <div class="section-bar mb-2">Tổng quan hiệu quả thu mua</div>
    <div class="kpi-grid mb-3">
        @foreach([
            ['Số lượng mua', number_format($metrics['quantity']).' con', $metrics['trip_count'].' chuyến · '.number_format($metrics['weight'],1).' kg', 'bi-cart-check-fill', '#168a55'],
            ['Giá mua bình quân', number_format($metrics['average_price']).' đ/kg', 'Tính theo giá trị tiền hàng', 'bi-currency-dollar', '#2765ad'],
            ['Tỷ lệ hao hụt', number_format($metrics['loss_rate'],2).'%', 'Theo đối chiếu thực nhập kho', 'bi-percent', '#df6c1c'],
            ['Tỷ lệ hàng loại', number_format($metrics['reject_rate'],2).'%', 'Kho xác nhận hàng lỗi / loại', 'bi-patch-exclamation-fill', '#cf3f32'],
            ['Chi phí vận chuyển', number_format($metrics['transport_per_duck']).' đ/con', number_format($metrics['transport_cost']).'đ trong kỳ', 'bi-truck', '#7141a8'],
            ['Chi phí thu mua', number_format($metrics['operating_per_duck']).' đ/con', number_format($metrics['operating_cost']).'đ ngoài tiền hàng', 'bi-cash-stack', '#0f8b8d'],
        ] as [$label,$value,$note,$icon,$color])
            <div class="kpi-card" style="--kpi-color:{{ $color }}"><div class="d-flex gap-2 align-items-center"><div class="kpi-icon"><i class="bi {{ $icon }}"></i></div><div class="min-w-0"><div class="kpi-label">{{ $label }}</div><div class="kpi-value">{{ $value }}</div><div class="kpi-note">{{ $note }}</div></div></div></div>
        @endforeach
    </div>

    <div class="dash-grid mb-3">
        <div class="dash-panel"><div class="section-bar">Kế hoạch & thực hiện theo ngày</div><div class="panel-body"><div class="chart-box"><canvas id="purchaseTrendChart"></canvas></div></div></div>
        <div class="dash-panel"><div class="section-bar">Cơ cấu nguồn hàng</div><div class="panel-body"><div class="chart-box"><canvas id="sourceChart"></canvas></div></div></div>
        <div class="dash-panel"><div class="section-bar">Công nợ & dòng tiền</div><div class="panel-body">
            <table class="table table-sm metric-table mb-2"><tbody><tr><td>Tổng chi thu mua</td><th class="text-end">{{ number_format($metrics['total_cost']) }}đ</th></tr><tr><td>Đã thanh toán</td><th class="text-end text-success">{{ number_format($metrics['total_cost']-$metrics['unpaid']) }}đ</th></tr><tr><td>Công nợ hiện tại</td><th class="text-end text-danger">{{ number_format($metrics['unpaid']) }}đ</th></tr><tr><td>Chờ kho tiếp nhận</td><th class="text-end">{{ $metrics['pending_warehouse'] }} chuyến</th></tr></tbody></table>
            <div class="text-center border-top pt-3"><div class="small text-muted text-uppercase fw-bold">Đánh giá bình quân từ kho</div><div class="quality-number">{{ $metrics['average_rating'] > 0 ? number_format($metrics['average_rating'],1).'/5' : '—' }}</div><div class="stars">{{ str_repeat('★', (int) round($metrics['average_rating'])) }}{{ str_repeat('☆', 5-(int) round($metrics['average_rating'])) }}</div></div>
        </div></div>
    </div>

    <div class="dash-grid-two mb-3">
        <div class="dash-panel"><div class="section-bar">Hiệu quả nguồn hàng theo đánh giá kho</div><div class="table-responsive"><table class="table table-sm table-hover metric-table align-middle mb-0"><thead><tr><th>#</th><th>Nguồn hàng</th><th class="text-end">Sản lượng</th><th class="text-end">Giá TB/kg</th><th class="text-end">Hàng loại</th><th class="text-center">Kho đánh giá</th></tr></thead><tbody>
            @forelse($supplierRanking->take(8) as $supplier)<tr><td>{{ $loop->iteration }}</td><td class="fw-semibold">{{ $supplier['name'] }}</td><td class="text-end">{{ number_format($supplier['quantity']) }}</td><td class="text-end">{{ number_format($supplier['average_price']) }}đ</td><td class="text-end {{ $supplier['reject_rate'] > 2 ? 'text-danger fw-bold' : '' }}">{{ number_format($supplier['reject_rate'],2) }}%</td><td class="text-center"><span class="stars">{{ $supplier['rating'] ? str_repeat('★',(int)round($supplier['rating'])) : '—' }}</span></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu trong kỳ.</td></tr>@endforelse
        </tbody></table></div></div>
        <div class="dash-panel"><div class="section-bar">Cảnh báo & khuyến nghị</div><div class="panel-body">
            @if($metrics['loss_rate'] > 3)<div class="alert-line"><i class="bi bi-exclamation-triangle-fill text-danger"></i><div><strong>Hao hụt {{ number_format($metrics['loss_rate'],2) }}%</strong><br><span class="text-muted">Kiểm tra khâu bắt vịt, kiểm đếm và vận chuyển.</span></div></div>@endif
            @if($metrics['reject_rate'] > 2)<div class="alert-line"><i class="bi bi-exclamation-triangle-fill text-danger"></i><div><strong>Hàng loại {{ number_format($metrics['reject_rate'],2) }}%</strong><br><span class="text-muted">Ưu tiên nguồn có đánh giá kho cao hơn.</span></div></div>@endif
            @if($metrics['pending_warehouse'] > 0)<div class="alert-line"><i class="bi bi-hourglass-split text-warning"></i><div><strong>{{ $metrics['pending_warehouse'] }} chuyến chờ kho</strong><br><span class="text-muted">Theo dõi tiếp nhận và phản hồi chất lượng.</span></div></div>@endif
            @if($metrics['unpaid'] > 0)<div class="alert-line"><i class="bi bi-wallet2 text-warning"></i><div><strong>Công nợ {{ number_format($metrics['unpaid']) }}đ</strong><br><span class="text-muted">Rà soát hạn trả và phiếu yêu cầu thanh toán.</span></div></div>@endif
            @if($metrics['loss_rate'] <= 3 && $metrics['reject_rate'] <= 2 && !$metrics['pending_warehouse'] && !$metrics['unpaid'])<div class="text-center text-success py-4"><i class="bi bi-check-circle-fill fs-2"></i><div class="fw-bold mt-2">Không có cảnh báo trong kỳ</div></div>@endif
        </div></div>
    </div>

    <div class="dash-grid-two mb-3">
        <div class="dash-panel"><div class="section-bar">Xu hướng KPI chính</div><div class="panel-body"><div class="chart-box-sm"><canvas id="costTrendChart"></canvas></div></div></div>
        <div class="dash-panel"><div class="section-bar">Trại có vịt đang nuôi từ 39–50 ngày</div><div class="table-responsive" style="max-height:250px"><table class="table table-sm table-hover align-middle metric-table mb-0"><thead class="sticky-top table-light"><tr><th>Trang trại</th><th>Tuổi đàn</th><th>Dự kiến có hàng</th><th></th></tr></thead><tbody id="farmTableBody">@forelse($farms as $farm)<tr class="farm-card" data-farm-id="{{ $farm->id }}" data-farm-name="{{ $farm->name }}"><td><strong>{{ $farm->name }}</strong><div class="small text-muted">{{ $farm->phone ?: 'Chưa có SĐT' }}</div></td><td>{{ $farm->raising_age_days }} ngày</td><td>{{ $farm->available_from?->format('d/m/Y') }}</td><td><button type="button" class="btn btn-sm btn-outline-success">Chọn</button></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Không có trại tới kỳ.</td></tr>@endforelse</tbody></table></div></div>
    </div>

    @include('procurement.partials.purchase_form')

    <div class="dash-panel"><div class="section-bar">Chi tiết các chuyến thu mua trong kỳ</div><div class="table-responsive"><table class="table table-sm table-hover align-middle metric-table mb-0"><thead><tr><th>Mã / Thời gian</th><th>Nguồn hàng</th><th class="text-end">SL / Khối lượng</th><th class="text-end">Tiền hàng</th><th class="text-end">CP thu mua</th><th class="text-end">CP vận chuyển</th><th>Kho đánh giá</th><th>Gửi nhập kho</th></tr></thead><tbody>
        @forelse($dashboardPurchases as $p)
            <tr><td><strong>{{ $p->code }}</strong><div class="text-muted">{{ $p->purchased_at->format('d/m H:i') }}</div></td><td>{{ $p->farm?->name ?? $p->supplier?->name }}<div class="text-muted">{{ $p->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt thịt' }}</div></td><td class="text-end">{{ number_format($p->quantity) }} con<div class="text-muted">{{ number_format($p->total_weight,1) }} kg</div></td><td class="text-end fw-semibold">{{ number_format($p->subtotal) }}đ</td><td class="text-end">{{ number_format((float)$p->broker_fee+(float)$p->processing_fee+(float)$p->procurement_fee+(float)$p->other_fee) }}đ</td><td class="text-end">{{ number_format($p->transportation_fee) }}đ</td><td>@if($p->warehouse_rating)<span class="stars">{{ str_repeat('★',(int)$p->warehouse_rating) }}</span><div class="text-muted">{{ $p->warehouse_condition }}</div>@else<span class="text-muted">Chưa đánh giá</span>@endif</td><td>@if($p->status === 'draft')<form class="d-flex gap-1" method="POST" action="{{ route('procurement.purchases.send-warehouse',$p) }}">@csrf<select class="form-select form-select-sm" name="warehouse_id" required><option value="">Chọn kho</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select><button class="btn btn-sm btn-success">Gửi</button></form>@else<span class="badge {{ $p->status==='received'?'bg-success':'bg-warning text-dark' }} status-label">{{ $p->status==='received'?'Đã nhập':'Chờ kho' }}</span><div class="trip-cost">{{ $p->warehouse?->name }}</div>@endif</td></tr>
        @empty<tr><td colspan="8" class="text-center text-muted py-5">Chưa có chuyến thu mua trong kỳ được chọn.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(() => {
    const daily = @json($dailyData);
    const sources = @json($sourceStructure->take(8));
    const palette = ['#184e8e','#168a55','#ee7d22','#cf3f32','#7141a8','#0f8b8d','#7a8ca5','#e4ae2b'];
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif'; Chart.defaults.font.size = 11; Chart.defaults.color = '#53637a';
    new Chart(document.getElementById('purchaseTrendChart'), {type:'bar',data:{labels:daily.map(x=>x.label),datasets:[{label:'Số lượng (con)',data:daily.map(x=>x.quantity),backgroundColor:'#2f69ae',borderRadius:3,yAxisID:'y'},{label:'Giá mua TB (đ/kg)',data:daily.map(x=>x.price),type:'line',borderColor:'#168a55',backgroundColor:'#168a55',tension:.3,pointRadius:2,yAxisID:'y1'}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,grid:{color:'#edf1f5'}},y1:{position:'right',beginAtZero:true,grid:{display:false},ticks:{callback:v=>Number(v).toLocaleString('vi-VN')}}},plugins:{legend:{position:'top'}}}});
    new Chart(document.getElementById('sourceChart'), {type:'doughnut',data:{labels:sources.map(x=>x.name),datasets:[{data:sources.map(x=>x.quantity),backgroundColor:palette,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{position:'right',labels:{boxWidth:10}}}}});
    new Chart(document.getElementById('costTrendChart'), {type:'line',data:{labels:daily.map(x=>x.label),datasets:[{label:'Giá mua TB (đ/kg)',data:daily.map(x=>x.price),borderColor:'#184e8e',backgroundColor:'#184e8e',tension:.3},{label:'Vận chuyển (đ/con)',data:daily.map(x=>x.transport_per_duck),borderColor:'#7141a8',backgroundColor:'#7141a8',tension:.3}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,ticks:{callback:v=>Number(v).toLocaleString('vi-VN')}}}}});
})();
</script>
@endpush
