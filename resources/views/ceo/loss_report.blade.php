@extends('layouts.ceo')

@section('title', 'Báo cáo hao hụt')
@section('subtitle', 'Tổng hợp khối lượng và giá trị hao hụt theo thời điểm phát sinh')

@push('styles')
<style>
    .loss-filter {
        display: grid;
        grid-template-columns: 150px repeat(3, minmax(0, 1fr)) auto;
        gap: 10px;
        align-items: end;
    }
    .loss-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
    }
    .loss-kpi {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }
    .loss-kpi .item {
        min-width: 0;
        padding: 14px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .loss-kpi .item.highlight {
        background: #fff7ed;
        border-color: #fed7aa;
    }
    .loss-kpi .label {
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .loss-kpi .value {
        margin-top: 6px;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        overflow-wrap: anywhere;
    }
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
    @media (max-width: 1200px) {
        .loss-kpi { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 992px) {
        .loss-filter { grid-template-columns: 1fr 1fr; }
        .loss-kpi { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 576px) {
        .loss-filter, .loss-kpi { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="loss-card p-3 mb-3">
    <form method="GET" action="{{ route('ceo.loss-report') }}" class="loss-filter">
        <div>
            <label class="form-label small fw-semibold mb-1">Kiểu báo cáo</label>
            <select name="mode" class="form-select form-select-sm" id="lossReportMode">
                <option value="day" @selected($mode === 'day')>Theo ngày</option>
                <option value="range" @selected($mode === 'range')>Từ ngày đến ngày</option>
            </select>
        </div>
        <div class="loss-day-field">
            <label class="form-label small fw-semibold mb-1">Ngày phát sinh</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
        </div>
        <div class="loss-range-field">
            <label class="form-label small fw-semibold mb-1">Từ ngày</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
        </div>
        <div class="loss-range-field">
            <label class="form-label small fw-semibold mb-1">Đến ngày</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i>Lọc
            </button>
            <a href="{{ route('ceo.loss-report') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<div class="loss-kpi mb-3">
    <div class="item">
        <div class="label">Lần ghi nhận</div>
        <div class="value">{{ number_format((int) ($summary->event_count ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Đơn bị hao hụt</div>
        <div class="value">{{ number_format((int) ($summary->order_count ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Khối lượng đầu vào</div>
        <div class="value">{{ format_kg((float) ($summary->input_weight ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Khối lượng đầu ra</div>
        <div class="value">{{ format_kg((float) ($summary->output_weight ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Tổng khối lượng hao hụt</div>
        <div class="value text-danger">
            {{ format_kg((float) ($summary->loss_weight ?? 0)) }}
            <span class="fs-6">({{ number_format((float) ($summary->loss_percent ?? 0), 3, ',', '.') }}%)</span>
        </div>
    </div>
    <div class="item highlight">
        <div class="label">Tổng giá trị hao hụt</div>
        <div class="value text-danger">{{ number_format((float) ($summary->loss_value ?? 0), 0, ',', '.') }} đ</div>
    </div>
</div>

@if((int) ($summary->unpriced_event_count ?? 0) > 0)
    <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            Có <strong>{{ number_format((int) $summary->unpriced_event_count) }}</strong> phát sinh chưa có giá min hiệu lực tại thời điểm hao hụt.
            Giá trị của các dòng này đang được tính là 0 đ; vui lòng bổ sung lịch sử giá để báo cáo đủ giá trị.
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="loss-card h-100">
            <div class="p-3 border-bottom fw-semibold">Hao hụt theo công đoạn</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Công đoạn</th>
                            <th class="text-end">Lần</th>
                            <th class="text-end">Đầu vào</th>
                            <th class="text-end">Hao hụt</th>
                            <th class="text-end">Tỷ lệ</th>
                            <th class="text-end">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stageRows as $row)
                            <tr>
                                <td><span class="loss-stage">{{ $row->label }}</span></td>
                                <td class="text-end">{{ number_format((int) $row->event_count) }}</td>
                                <td class="text-end">{{ format_kg((float) $row->input_weight) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ format_kg((float) $row->loss_weight) }}</td>
                                <td class="text-end">{{ number_format((float) $row->loss_percent, 3, ',', '.') }}%</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $row->loss_value, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Chưa có dữ liệu hao hụt trong kỳ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="loss-card h-100">
            <div class="p-3 border-bottom fw-semibold">Đơn hàng hao hụt nhiều nhất</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Đơn hàng</th>
                            <th class="text-end">Lần</th>
                            <th class="text-end">Hao hụt</th>
                            <th class="text-end">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orderRows->take(10) as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->label }}</td>
                                <td class="text-end">{{ number_format((int) $row->event_count) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ format_kg((float) $row->loss_weight) }}</td>
                                <td class="text-end">{{ number_format((float) $row->loss_value, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có đơn hàng phát sinh hao hụt.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="loss-card mb-3">
    <div class="p-3 border-bottom fw-semibold">Diễn biến theo ngày</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th class="text-end">Lần ghi nhận</th>
                    <th class="text-end">Đầu vào</th>
                    <th class="text-end">Đầu ra</th>
                    <th class="text-end">Hao hụt</th>
                    <th class="text-end">Tỷ lệ</th>
                    <th class="text-end">Giá trị hao hụt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyRows as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->day_key)->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format((int) $row->event_count) }}</td>
                        <td class="text-end">{{ format_kg((float) $row->input_weight) }}</td>
                        <td class="text-end">{{ format_kg((float) $row->output_weight) }}</td>
                        <td class="text-end text-danger fw-semibold">{{ format_kg((float) $row->loss_weight) }}</td>
                        <td class="text-end">{{ number_format((float) $row->loss_percent, 3, ',', '.') }}%</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $row->loss_value, 0, ',', '.') }} đ</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Chưa có dữ liệu hao hụt.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="loss-card">
    <div class="p-3 border-bottom">
        <div class="fw-semibold">Chi tiết phát sinh hao hụt</div>
        <div class="loss-note mt-1">Giá trị = khối lượng hao hụt × giá min có hiệu lực tại ngày phát sinh. Trường hợp nhiều sản phẩm dùng giá min bình quân gia quyền.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
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
                        <td class="text-end text-nowrap">{{ number_format((float) $event['min_price'], 0, ',', '.') }} đ/kg</td>
                        <td class="text-end text-nowrap fw-semibold">{{ number_format((float) $event['loss_value'], 0, ',', '.') }} đ</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Không có phát sinh hao hụt trong thời gian đã chọn.</td></tr>
                @endforelse
            </tbody>
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
