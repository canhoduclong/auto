@extends('layouts.ceo')

@section('title', 'CEO Dashboard')
@section('subtitle', 'Tổng quan điều hành doanh nghiệp')

@push('styles')
<style>
    .ceo-grid { display: grid; gap: 14px; }
    .ceo-kpi { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .ceo-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .ceo-card .label { color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    .ceo-card .value { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-top: 8px; }
    .ceo-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ceo-table { margin-bottom: 0; }
    .ceo-table th { font-size: .75rem; color: #64748b; text-transform: uppercase; }
    .ceo-alert { border-left: 4px solid transparent; }
    .ceo-alert.high { border-left-color: #ef4444; }
    .ceo-alert.medium { border-left-color: #f59e0b; }
    .ceo-alert.low { border-left-color: #0ea5e9; }
    .ceo-header-line {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    @media (max-width: 1200px) { .ceo-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 900px) { .ceo-two { grid-template-columns: 1fr; } }
    @media (max-width: 576px) { .ceo-kpi { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ceo-header-line">
    <span class="badge text-bg-light border">{{ $rangeLabel }}</span>
    <span class="badge text-bg-light border">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</span>
</div>

<div class="ceo-grid">
    <div class="ceo-kpi">
        <div class="ceo-card">
            <div class="label">Doanh thu thuần</div>
            <div class="value">{{ number_format($overview['net_revenue']) }} đ</div>
        </div>
        <div class="ceo-card">
            <div class="label">Tổng đơn hàng</div>
            <div class="value">{{ number_format($overview['total_orders']) }}</div>
        </div>
        <div class="ceo-card">
            <div class="label">Tỷ lệ hoàn tất</div>
            <div class="value">{{ number_format($overview['completion_rate'], 1) }}%</div>
        </div>
        <div class="ceo-card">
            <div class="label">Công nợ</div>
            <div class="value">{{ number_format($overview['debt_total']) }} đ</div>
        </div>
    </div>

    <div class="ceo-two">
        <div class="ceo-card">
            <h6 class="mb-3">Top Sale</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Nhân sự</th><th class="text-end">Đơn</th><th class="text-end">Doanh số</th></tr></thead>
                    <tbody>
                    @forelse($salesTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount) }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ceo-card">
            <h6 class="mb-3">Top Khách Hàng</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Khách hàng</th><th class="text-end">Đơn</th><th class="text-end">Doanh số</th></tr></thead>
                    <tbody>
                    @forelse($customerTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount) }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ceo-two">
        <div class="ceo-card">
            <h6 class="mb-3">Hiệu suất Shipper</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Shipper</th><th class="text-end">Đơn</th><th class="text-end">Tỷ lệ thành công</th></tr></thead>
                    <tbody>
                    @forelse($shipperTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->success_rate, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ceo-card">
            <h6 class="mb-3">Cảnh báo cần xử lý</h6>
            <div class="d-grid gap-2">
                @foreach($alerts as $alert)
                    <div class="ceo-alert {{ $alert['level'] }} p-2 rounded border bg-light">
                        <div class="fw-semibold">{{ $alert['title'] }}</div>
                        <div class="small text-muted">{{ $alert['description'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
