@extends('layouts.shipper')

@section('title', 'Báo cáo đội hình ship')
@section('subtitle', 'Theo dõi hiệu suất và tải công việc đội shipper')

@push('styles')
<style>
    .ts-filter-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
    }
    .ts-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
    }
    .ts-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        padding: 14px;
    }
    .ts-kpi-label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        font-weight: 700;
    }
    .ts-kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .ts-kpi-value.primary {
        color: var(--theme-primary);
    }
    .ts-kpi-value.warning {
        color: #b45309;
    }
    .ts-table-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
    }
    .ts-table-wrap {
        overflow-x: auto;
    }
    .ts-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 980px;
    }
    .ts-table thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        color: #334155;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 10px 12px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .ts-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        font-size: .88rem;
        color: #0f172a;
        vertical-align: middle;
        white-space: nowrap;
    }
    .ts-table tbody tr:hover {
        background: #f8fdfc;
    }
    .ts-name {
        font-weight: 700;
    }
    .ts-meta {
        font-size: .75rem;
        color: #64748b;
    }
    .ts-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
    }
    .ts-pill.blue { background: #dbeafe; color: #1e40af; }
    .ts-pill.green { background: #dcfce7; color: #166534; }
    .ts-pill.orange { background: #ffedd5; color: #9a3412; }
    .ts-pill.gray { background: #e2e8f0; color: #334155; }
    .ts-rate-track {
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        width: 120px;
    }
    .ts-rate-fill {
        height: 100%;
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
        border-radius: 999px;
    }
    .ts-empty {
        text-align: center;
        padding: 32px 16px;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="card ts-filter-card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('shipper.team-report') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Từ ngày</label>
                <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Đến ngày</label>
                <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Lọc báo cáo
                </button>
                <a href="{{ route('shipper.team-report') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i>Hôm nay
                </a>
            </div>
        </form>
    </div>
</div>

<div class="ts-kpi-grid mb-3">
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Tổng Shipper</div>
        <div class="ts-kpi-value primary">{{ number_format($teamSummary['total_shippers'], 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Shipper Có Đơn</div>
        <div class="ts-kpi-value">{{ number_format($teamSummary['active_shippers'], 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Tổng Đơn Được Gán</div>
        <div class="ts-kpi-value">{{ number_format($teamSummary['total_orders'], 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Đơn Đang Giao</div>
        <div class="ts-kpi-value warning">{{ number_format($teamSummary['delivering'], 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Đơn Đã Xong</div>
        <div class="ts-kpi-value">{{ number_format($teamSummary['done_orders'], 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Đơn Chờ Gán</div>
        <div class="ts-kpi-value warning">{{ number_format($unassignedReadyOrders, 0, ',', '.') }}</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Tổng Phí Ship</div>
        <div class="ts-kpi-value">{{ number_format($teamSummary['total_ship_fee'], 0, ',', '.') }} đ</div>
    </div>
    <div class="ts-kpi-card">
        <div class="ts-kpi-label">Tổng COD Thu</div>
        <div class="ts-kpi-value primary">{{ number_format($teamSummary['total_collected'], 0, ',', '.') }} đ</div>
    </div>
</div>

<div class="card ts-table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold">Chi tiết theo shipper</h6>
            <div class="text-muted" style="font-size:.8rem;">Khung thời gian: {{ \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') }}</div>
        </div>
    </div>
    <div class="card-body p-0">
        @if($shipperStats->isEmpty())
            <div class="ts-empty">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                Không có dữ liệu shipper trong khoảng thời gian đã chọn.
            </div>
        @else
            <div class="ts-table-wrap">
                <table class="ts-table">
                    <thead>
                        <tr>
                            <th>Shipper</th>
                            <th>Tổng đơn</th>
                            <th>Đang giao</th>
                            <th>Đã giao</th>
                            <th>Hoàn thành</th>
                            <th>Đang trả</th>
                            <th>Tỷ lệ xong</th>
                            <th>Phí ship</th>
                            <th>COD thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shipperStats as $row)
                            <tr>
                                <td>
                                    <div class="ts-name">{{ $row['shipper']->name }}</div>
                                    <div class="ts-meta">{{ $row['shipper']->phone ?: ($row['shipper']->email ?: 'Không có liên hệ') }}</div>
                                </td>
                                <td><span class="ts-pill gray">{{ $row['total_orders'] }}</span></td>
                                <td><span class="ts-pill blue">{{ $row['delivering'] }}</span></td>
                                <td><span class="ts-pill green">{{ $row['delivered'] }}</span></td>
                                <td><span class="ts-pill green">{{ $row['completed'] }}</span></td>
                                <td><span class="ts-pill orange">{{ $row['returning'] + $row['returned_completed'] }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="ts-rate-track">
                                            <div class="ts-rate-fill" style="width: {{ max(0, min(100, $row['completion_rate'])) }}%;"></div>
                                        </div>
                                        <strong>{{ number_format($row['completion_rate'], 1, ',', '.') }}%</strong>
                                    </div>
                                </td>
                                <td><strong>{{ number_format($row['total_ship_fee'], 0, ',', '.') }} đ</strong></td>
                                <td><strong>{{ number_format($row['total_collected'], 0, ',', '.') }} đ</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
