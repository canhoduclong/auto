@extends('layouts.ceo')

@section('title', 'Báo cáo hao hụt')
@section('subtitle', 'Theo dõi hao hụt thực tế trong pha lóc')

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
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }
    .loss-kpi .item {
        padding: 14px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .loss-kpi .label {
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .loss-kpi .value {
        margin-top: 6px;
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
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
            <label class="form-label small fw-semibold mb-1">Ngày</label>
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
        <div class="label">Số mẻ pha lóc</div>
        <div class="value">{{ number_format((int) ($summary->batch_count ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Nguyên liệu</div>
        <div class="value">{{ format_kg((float) ($summary->input_weight ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Thành phẩm</div>
        <div class="value">{{ format_kg((float) ($summary->finished_weight ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Thành phần còn lại</div>
        <div class="value">{{ format_kg((float) ($summary->component_weight ?? 0)) }}</div>
    </div>
    <div class="item">
        <div class="label">Hao hụt</div>
        <div class="value text-danger">{{ format_kg((float) ($summary->loss_weight ?? 0)) }} <span class="fs-6">({{ number_format((float) ($summary->loss_percent ?? 0), 3, ',', '.') }}%)</span></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="loss-card">
            <div class="p-3 border-bottom fw-semibold">Tổng hợp theo ngày</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th class="text-end">Mẻ</th>
                            <th class="text-end">Nguyên liệu</th>
                            <th class="text-end">Đầu ra</th>
                            <th class="text-end">Hao hụt</th>
                            <th class="text-end">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->day_key)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format((int) $row->batch_count) }}</td>
                                <td class="text-end">{{ format_kg((float) $row->input_weight) }}</td>
                                <td class="text-end">{{ format_kg((float) $row->finished_weight + (float) $row->component_weight) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ format_kg((float) $row->loss_weight) }}</td>
                                <td class="text-end">{{ number_format((float) $row->loss_percent, 3, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Chưa có dữ liệu hao hụt.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="loss-card">
            <div class="p-3 border-bottom fw-semibold">Theo kho</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kho</th>
                            <th class="text-end">Mẻ</th>
                            <th class="text-end">Hao hụt</th>
                            <th class="text-end">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouseRows as $row)
                            <tr>
                                <td>{{ $row->warehouse_name }}</td>
                                <td class="text-end">{{ number_format((int) $row->batch_count) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ format_kg((float) $row->loss_weight) }}</td>
                                <td class="text-end">{{ number_format((float) $row->loss_percent, 3, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="loss-card">
    <div class="p-3 border-bottom fw-semibold">Chi tiết mẻ pha lóc</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Kho</th>
                    <th>Sản phẩm pha lóc</th>
                    <th>Người làm</th>
                    <th class="text-end">Nguyên liệu</th>
                    <th class="text-end">Thành phẩm</th>
                    <th class="text-end">Thành phần</th>
                    <th class="text-end">Hao hụt</th>
                    <th class="text-end">Tỷ lệ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>{{ optional($batch->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $batch->warehouse?->name ?? '—' }}</td>
                        <td class="fw-semibold">{{ trim(($batch->targetVariant?->product?->name ?? 'Sản phẩm') . ' ' . ($batch->targetVariant?->name ?: '')) }}</td>
                        <td>{{ $batch->performer?->name ?? '—' }}</td>
                        <td class="text-end">{{ format_kg((float) $batch->input_weight) }}</td>
                        <td class="text-end">{{ format_kg((float) $batch->actual_finished_weight) }}</td>
                        <td class="text-end">{{ format_kg((float) $batch->actual_component_weight) }}</td>
                        <td class="text-end text-danger fw-semibold">{{ format_kg((float) $batch->loss_weight) }}</td>
                        <td class="text-end">{{ number_format((float) $batch->loss_percent, 3, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">Không có mẻ pha lóc trong kỳ này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $batches->links() }}
    </div>
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
