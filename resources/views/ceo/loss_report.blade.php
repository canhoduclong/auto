@extends('layouts.ceo')

@section('title', 'Báo cáo hao hụt')
@section('subtitle', 'Danh sách hao hụt chung theo thời điểm phát sinh')

@push('styles')
<style>
    .loss-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
    }
    .loss-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: end;
    }
    .loss-filter .filter-sm { flex: 0 1 160px; }
    .loss-filter .filter-md { flex: 1 1 190px; }
    .loss-filter .filter-lg { flex: 1 1 230px; }
    .loss-filter .filter-actions { flex: 0 0 auto; }
    .loss-stage {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .loss-note {
        color: #64748b;
        font-size: 12px;
    }
    .loss-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .loss-summary .item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 9px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
    }
    .loss-summary .item strong { color: #0f172a; }
    .loss-table { min-width: 1420px; }
    .loss-table thead th {
        white-space: nowrap;
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .02em;
        background: #f8fafc;
    }
    .loss-table tfoot td {
        background: #fff7ed;
        border-top: 2px solid #fdba74;
    }
    @media (max-width: 576px) {
        .loss-filter > div { flex-basis: 100% !important; }
        .loss-filter .filter-actions { width: 100%; }
        .loss-filter .filter-actions .btn { flex: 1; }
    }
</style>
@endpush

@section('content')
<div class="loss-card p-3 mb-3">
    <form method="GET" action="{{ route('ceo.loss-report') }}" class="loss-filter">
        <div class="filter-sm">
            <label class="form-label small fw-semibold mb-1">Kiểu thời gian</label>
            <select name="mode" class="form-select form-select-sm" id="lossReportMode">
                <option value="day" @selected($mode === 'day')>Theo ngày</option>
                <option value="range" @selected($mode === 'range')>Từ ngày đến ngày</option>
            </select>
        </div>
        <div class="filter-md loss-day-field">
            <label class="form-label small fw-semibold mb-1">Ngày phát sinh</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
        </div>
        <div class="filter-md loss-range-field">
            <label class="form-label small fw-semibold mb-1">Từ ngày</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
        </div>
        <div class="filter-md loss-range-field">
            <label class="form-label small fw-semibold mb-1">Đến ngày</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
        </div>
        <div class="filter-md">
            <label class="form-label small fw-semibold mb-1">Công đoạn</label>
            <select name="stage" class="form-select form-select-sm">
                <option value="">Tất cả công đoạn</option>
                <option value="cutting" @selected($stageFilter === 'cutting')>Pha lóc</option>
                <option value="transfer" @selected($stageFilter === 'transfer')>Điều chuyển kho</option>
                <option value="return" @selected($stageFilter === 'return')>Nhận hàng trả</option>
            </select>
        </div>
        <div class="filter-lg">
            <label class="form-label small fw-semibold mb-1">Kho / Luồng xử lý</label>
            <select name="location" class="form-select form-select-sm">
                <option value="">Tất cả kho / luồng</option>
                @foreach($locationOptions as $location)
                    <option value="{{ $location }}" @selected($locationFilter === $location)>{{ $location }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-lg">
            <label class="form-label small fw-semibold mb-1">Đơn / Khách hàng</label>
            <input type="search" name="order" class="form-control form-control-sm" value="{{ $orderFilter }}" placeholder="Mã đơn hoặc tên khách">
        </div>
        <div class="filter-lg">
            <label class="form-label small fw-semibold mb-1">Sản phẩm / Người ghi nhận</label>
            <input type="search" name="keyword" class="form-control form-control-sm" value="{{ $keywordFilter }}" placeholder="Nhập từ khóa tìm kiếm">
        </div>
        <div class="filter-actions d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel me-1"></i>Lọc
            </button>
            <a href="{{ route('ceo.loss-report') }}" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
        </div>
    </form>
</div>

<div class="loss-card">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <div class="fw-semibold">Bảng hao hụt chung</div>
            <div class="loss-note mt-1">Giá trị = khối lượng hao hụt × giá min tại thời điểm phát sinh; nhiều sản phẩm dùng giá min bình quân gia quyền.</div>
        </div>
        <div class="loss-summary">
            <span class="item">Phát sinh <strong>{{ number_format((int) $summary->event_count) }}</strong></span>
            <span class="item">Đơn hàng <strong>{{ number_format((int) $summary->order_count) }}</strong></span>
            <span class="item">Hao hụt <strong class="text-danger">{{ format_kg((float) $summary->loss_weight) }}</strong></span>
            <span class="item">Tỷ lệ <strong>{{ number_format((float) $summary->loss_percent, 3, ',', '.') }}%</strong></span>
            <span class="item">Giá trị <strong class="text-danger">{{ number_format((float) $summary->loss_value, 0, ',', '.') }} đ</strong></span>
        </div>
    </div>

    @if((int) ($summary->unpriced_event_count ?? 0) > 0)
        <div class="alert alert-warning rounded-0 border-start-0 border-end-0 border-top-0 d-flex align-items-start gap-2 py-2 mb-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                Có <strong>{{ number_format((int) $summary->unpriced_event_count) }}</strong> phát sinh chưa có giá min hiệu lực;
                giá trị các dòng này đang được tính là 0 đ.
            </div>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 loss-table">
            <thead>
                <tr>
                    <th>Thời điểm</th>
                    <th>Công đoạn</th>
                    <th>Đơn / Khách hàng</th>
                    <th>Kho / Luồng xử lý</th>
                    <th>Sản phẩm</th>
                    <th>Người ghi nhận</th>
                    <th class="text-end">Đầu vào</th>
                    <th class="text-end">Đầu ra</th>
                    <th class="text-end">Hao hụt</th>
                    <th class="text-end">Giá min BQ</th>
                    <th class="text-end">Giá trị</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lossEvents as $event)
                    <tr>
                        <td class="text-nowrap">{{ $event['occurred_at']->format('d/m/Y H:i') }}</td>
                        <td><span class="loss-stage">{{ $event['stage'] }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $event['order_label'] }}</div>
                            <div class="loss-note">{{ $event['customer_name'] }}</div>
                        </td>
                        <td>{{ $event['location'] }}</td>
                        <td>{{ $event['product_name'] }}</td>
                        <td>{{ $event['actor_name'] }}</td>
                        <td class="text-end text-nowrap">{{ format_kg((float) $event['input_weight']) }}</td>
                        <td class="text-end text-nowrap">{{ format_kg((float) $event['output_weight']) }}</td>
                        <td class="text-end text-nowrap text-danger fw-semibold">{{ format_kg((float) $event['loss_weight']) }}</td>
                        <td class="text-end text-nowrap">
                            @if((float) $event['min_price'] > 0)
                                {{ number_format((float) $event['min_price'], 0, ',', '.') }} đ/kg
                            @else
                                <span class="text-warning fw-semibold">Chưa có giá</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap fw-semibold">{{ number_format((float) $event['loss_value'], 0, ',', '.') }} đ</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Không có phát sinh hao hụt phù hợp với bộ lọc.</td></tr>
                @endforelse
            </tbody>
            @if($summary->event_count > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end fw-bold">Tổng kết quả đang lọc</td>
                        <td class="text-end text-nowrap fw-bold">{{ format_kg((float) $summary->input_weight) }}</td>
                        <td class="text-end text-nowrap fw-bold">{{ format_kg((float) $summary->output_weight) }}</td>
                        <td class="text-end text-nowrap text-danger fw-bold">{{ format_kg((float) $summary->loss_weight) }}</td>
                        <td class="text-end text-nowrap">—</td>
                        <td class="text-end text-nowrap text-danger fw-bold">{{ number_format((float) $summary->loss_value, 0, ',', '.') }} đ</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if($lossEvents->hasPages())
        <div class="p-3 border-top">
            {{ $lossEvents->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mode = document.getElementById('lossReportMode');
    const dayFields = document.querySelectorAll('.loss-day-field');
    const rangeFields = document.querySelectorAll('.loss-range-field');

    function syncMode() {
        const isRange = mode?.value === 'range';
        dayFields.forEach((el) => el.style.display = isRange ? 'none' : '');
        rangeFields.forEach((el) => el.style.display = isRange ? '' : 'none');
    }

    mode?.addEventListener('change', syncMode);
    syncMode();
});
</script>
@endpush
