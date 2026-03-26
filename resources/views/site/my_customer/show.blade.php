@extends('layouts.site')

@section('content')
<style>
    .customer-detail-shell {
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.12), transparent 30%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        min-height: 100vh;
        padding: 32px 0 56px;
    }
    .customer-detail-container {
        width: min(1240px, calc(100% - 24px));
        margin: 0 auto;
    }
    .customer-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #0ea5e9 100%);
        color: #fff;
        border-radius: 28px;
        padding: 28px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        position: relative;
        overflow: hidden;
    }
    .customer-hero::after {
        content: '';
        position: absolute;
        inset: auto -80px -120px auto;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
    }
    .customer-hero-top {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }
    .customer-hero-title {
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 10px;
    }
    .customer-hero-subtitle {
        max-width: 760px;
        color: rgba(255, 255, 255, 0.82);
        margin: 0;
    }
    .customer-hero-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px;
        margin-top: 24px;
        position: relative;
        z-index: 1;
    }
    .customer-hero-meta-item {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        border-radius: 18px;
        padding: 14px 16px;
    }
    .customer-hero-meta-label {
        display: block;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.72);
        margin-bottom: 6px;
    }
    .customer-hero-meta-value {
        font-size: 15px;
        font-weight: 700;
    }
    .customer-action-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-start;
    }
    .customer-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0 16px;
        border-radius: 999px;
        border: 1px solid transparent;
        text-decoration: none;
        font-weight: 700;
        transition: 0.2s ease;
        cursor: pointer;
    }
    .customer-btn-primary {
        background: #fff;
        color: #0f172a;
    }
    .customer-btn-primary:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(255, 255, 255, 0.18);
    }
    .customer-btn-outline {
        border-color: rgba(255, 255, 255, 0.36);
        color: #fff;
        background: transparent;
    }
    .customer-btn-outline:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.12);
    }
    .customer-btn-soft {
        background: #e2e8f0;
        color: #0f172a;
    }
    .customer-btn-soft:hover {
        color: #0f172a;
        background: #cbd5e1;
    }
    .customer-alert {
        margin-top: 18px;
        border-radius: 16px;
        padding: 14px 18px;
        font-weight: 600;
    }
    .customer-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 18px;
        margin-top: 22px;
    }
    .customer-card {
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 24px;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    .customer-card-body {
        padding: 22px;
    }
    .customer-filter-card {
        grid-column: span 12;
    }
    .customer-summary-grid {
        grid-column: span 12;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }
    .customer-summary-card {
        padding: 22px;
        border-radius: 24px;
        color: #0f172a;
        position: relative;
        overflow: hidden;
    }
    .customer-summary-card::after {
        content: '';
        position: absolute;
        right: -24px;
        bottom: -24px;
        width: 120px;
        height: 120px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
    }
    .customer-summary-card.total { background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); }
    .customer-summary-card.paid { background: linear-gradient(135deg, #dcfce7 0%, #f0fdf4 100%); }
    .customer-summary-card.debt { background: linear-gradient(135deg, #fee2e2 0%, #fff1f2 100%); }
    .customer-summary-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #475569;
        margin-bottom: 10px;
        display: inline-block;
    }
    .customer-summary-value {
        font-size: clamp(24px, 3vw, 34px);
        font-weight: 800;
        line-height: 1.1;
        position: relative;
        z-index: 1;
    }
    .customer-summary-note {
        margin-top: 10px;
        position: relative;
        z-index: 1;
        color: #475569;
        font-weight: 600;
    }
    .customer-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 22px;
    }
    .customer-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 18px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.7);
        color: #334155;
        border: 1px solid rgba(148, 163, 184, 0.28);
        transition: 0.2s ease;
    }
    .customer-tab:hover,
    .customer-tab.active {
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
        border-color: transparent;
    }
    .customer-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 8px;
        margin-left: 8px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.18);
        font-size: 12px;
        font-weight: 800;
    }
    .customer-tab.active .customer-tab-count,
    .customer-tab:hover .customer-tab-count {
        background: rgba(255, 255, 255, 0.18);
    }
    .customer-section-title {
        margin: 0 0 6px;
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
    }
    .customer-section-subtitle {
        color: #64748b;
        margin: 0;
    }
    .customer-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .customer-info-item {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 16px 18px;
        background: #fff;
    }
    .customer-info-label {
        display: block;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 8px;
    }
    .customer-info-value {
        color: #0f172a;
        font-weight: 700;
        word-break: break-word;
    }
    .customer-two-column {
        grid-column: span 12;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 18px;
    }
    .customer-table-wrap {
        overflow-x: auto;
        margin-top: 18px;
    }
    .customer-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px;
    }
    .customer-table th,
    .customer-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
    }
    .customer-table th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        background: #f8fafc;
    }
    .customer-table tr:hover td {
        background: rgba(241, 245, 249, 0.6);
    }
    .customer-link {
        color: #2563eb;
        text-decoration: none;
        font-weight: 700;
    }
    .customer-link:hover { color: #1d4ed8; }
    .customer-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #b91c1c; }
    .badge-info { background: #dbeafe; color: #1d4ed8; }
    .badge-dark { background: #e2e8f0; color: #0f172a; }
    .customer-empty {
        text-align: center;
        padding: 38px 18px;
        color: #64748b;
        font-weight: 600;
    }
    .customer-form-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }
    .customer-field { grid-column: span 3; }
    .customer-field.wide { grid-column: span 4; }
    .customer-field.full { grid-column: span 12; }
    .customer-label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-weight: 700;
    }
    .customer-input,
    .customer-select,
    .customer-textarea {
        width: 100%;
        min-height: 46px;
        border-radius: 16px;
        border: 1px solid #cbd5e1;
        background: #fff;
        padding: 0 14px;
        color: #0f172a;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .customer-textarea {
        min-height: 110px;
        padding-top: 12px;
        padding-bottom: 12px;
        resize: vertical;
    }
    .customer-input:focus,
    .customer-select:focus,
    .customer-textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .customer-inline-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
    }
    .customer-muted {
        color: #64748b;
        font-size: 14px;
    }
    .customer-report-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 18px;
    }
    .customer-report-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 18px;
    }
    .customer-report-card .value {
        display: block;
        font-size: 28px;
        font-weight: 800;
        margin-top: 10px;
        color: #0f172a;
    }
    .customer-pagination {
        margin-top: 18px;
    }
    .customer-list-stack {
        display: grid;
        gap: 12px;
        margin-top: 18px;
    }
    .customer-list-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        padding: 16px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
    }
    .customer-list-item-title {
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 6px;
    }
    .customer-list-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        color: #64748b;
        font-size: 14px;
    }
    .customer-list-amount {
        white-space: nowrap;
        text-align: right;
        font-weight: 800;
        color: #0f172a;
    }
    .customer-border{
        border: #dfdfdf 1px solid;
    }
    @media (max-width: 991px) {
        .customer-summary-grid,
        .customer-report-grid,
        .customer-two-column {
            grid-template-columns: 1fr;
        }
        .customer-info-grid { grid-template-columns: 1fr; }
        .customer-field,
        .customer-field.wide { grid-column: span 6; }
    }
    @media (max-width: 640px) {
        .customer-detail-shell { padding-top: 20px; }
        .customer-hero { padding: 22px; border-radius: 22px; }
        .customer-card-body { padding: 18px; }
        .customer-field,
        .customer-field.wide,
        .customer-field.full { grid-column: span 12; }
        .customer-summary-grid { grid-template-columns: 1fr; }
        .customer-tabs { display: grid; grid-template-columns: 1fr 1fr; }
    }
</style>

@php
    $defaultAddress = $customer->addresses->firstWhere('is_default', 1) ?? $customer->addresses->first();
    $addressParts = collect([
        optional($defaultAddress)->house_number,
        optional($defaultAddress)->street,
        optional($defaultAddress)->ward,
        optional($defaultAddress)->district,
        optional($defaultAddress)->city,
        optional($defaultAddress)->province,
        optional($defaultAddress)->note,
        $customer->address,
    ])->filter()->unique()->values();
    $fullAddress = $addressParts->implode(', ');
    $region = collect([
        optional($defaultAddress)->district,
        optional($defaultAddress)->city,
        optional($defaultAddress)->province,
    ])->filter()->implode(', ');

    $queryParams = request()->except('orders_page', 'debt_page', 'payments_page');
    $periodLabel = match($period) {
        'today' => 'Hom nay',
        'week' => 'Tuan nay',
        'custom' => 'Khoang tuy chon',
        default => 'Thang nay',
    };

    $debtStatusLabel = 'Da thanh toan du';
    $debtStatusClass = 'badge-success';
    $hasOverdueDebt = false;

    foreach ($debtOrders as $debtOrder) {
        $paid = (float) $debtOrder->transactions->where('type', 'payment')->sum('amount') - (float) $debtOrder->transactions->where('type', 'refund')->sum('amount');
        $remaining = max((float) $debtOrder->total - $paid, 0);
        $dueDate = optional($debtOrder->created_at)?->copy()->addDays(30);
        if ($remaining > 0 && $dueDate && $dueDate->isPast()) {
            $hasOverdueDebt = true;
            break;
        }
    }

    if ($totalDebtAmount > 0) {
        if ($hasOverdueDebt) {
            $debtStatusLabel = 'No qua han';
            $debtStatusClass = 'badge-danger';
        } else {
            $debtStatusLabel = 'Con no';
            $debtStatusClass = 'badge-warning';
        }
    }

    $reportOrderCount = (int) $reportByMonth->sum('order_count');
    $reportOrderTotal = (float) $reportByMonth->sum('order_total');
    $reportOutstandingTotal = (float) $reportByMonth->sum('outstanding_total');
@endphp

<div class="customer-detail-shell">
    <div class="customer-detail-container">
        <div class="row g-3 mb-4">
            <!-- Card: Thông tin khách hàng -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color:#0f766e;font-weight:700;">Thông tin khách hàng</h5>
                        <div class="row g-2">
                            <div class="col-6"><strong>Tên:</strong> {{ $customer->name }}</div>
                            <div class="col-6"><strong>Điện thoại:</strong> {{ $customer->phone ?: '-' }}</div>
                            <div class="col-6"><strong>Email:</strong> {{ $customer->email ?: '-' }}</div>
                            <div class="col-6"><strong>Loại khách:</strong> {{ optional($customer->type)->name ?: 'Chưa phân loại' }}</div>
                            <div class="col-6"><strong>Size:</strong> {{ $customer->size ?: '-' }}</div>
                            <div class="col-6"><strong>Sản lượng:</strong> {{ $customer->production ?: '-' }}</div>
                            <div class="col-12"><strong>Địa chỉ:</strong> {{ $fullAddress }}</div>
                            <div class="col-6"><strong>Người phụ trách:</strong> {{ optional($customer->assignedTo)->name ?: auth()->user()->name }}</div>
                        </div> 
                        <h5 class="card-title my-3" style="color:#0f766e;font-weight:700;">Thống kê</h5>
                        <div class="d-flex">
                            <div class="customer-summary-label">Tong tien don hang</div>
                            <div class="customer-summary ml-2"><strong>{{ number_format($totalOrderAmount, 0, ',', '.') }} đ</strong></div>
                             
                        </div>
                        <div class="d-flex">
                            <div class="customer-summary-label">Tong da thanh toan</div>
                            <div class="customer-summary ml-2"><strong>{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</strong></div>
                            
                        </div>  
                        <div class="d-flex">                                 
                            <div class="customer-summary-label">Cong no hien tai</div>
                            <div class="customer-summary ml-2"><strong>{{ number_format($totalDebtAmount, 0, ',', '.') }} đ</strong></div> 
                         </div>
                    </div>
                </div>
            </div>
            <!-- Card: Tình trạng & Nhật ký chăm sóc -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title mb-3" style="color:#0f766e;font-weight:700;">Tình trạng & Nhật ký chăm sóc</h5>
                            <div class="mb-2">
                                <span class="badge bg-info"><i class="bi bi-activity"></i> Trạng thái: {{ $customer->status }}</span>
                                @if($customer->potential)
                                    <span class="badge bg-warning text-dark ms-2">Khách tiềm năng</span>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('my_customer.update', $customer) }}" class="mb-2">
                            @csrf
                            @method('PUT')
                            <div class="mb-2">
                                <textarea name="care_note" class="form-control form-control-sm" rows="2" placeholder="Nhập tình trạng hoặc nhật ký chăm sóc..."></textarea>
                            </div>
                            <div>
                                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus"></i> Lưu</button>
                            </div>
                        </form>
                        <div style="max-height:120px;overflow:auto;">
                            <ul class="list-unstyled mb-0">
                                @foreach(($careLogs ?? $customer->careLogs ?? []) as $log)
                                    <li class="small text-muted mb-1">
                                        <i class="bi bi-clock"></i> <strong>{{ $log->created_at->format('d/m/Y H:i') }}</strong>
                                        - <span class="fw-bold">{{ $log->user->name ?? '' }}</span>: {{ $log->note }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Card: Tần suất lấy hàng -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title mb-3" style="color:#0f766e;font-weight:700;">Tần suất lấy hàng</h5>
                            <div class="mb-2">
                                <span class="badge bg-success"><i class="bi bi-bar-chart"></i> Thực tế: {{ $customer->order_frequency_count ?? '?' }} lần/{{ $customer->order_frequency_type == 'week' ? 'tuần' : 'tháng' }}</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('my_customer.update', $customer) }}">
                            @csrf
                            @method('PUT')
                            <div class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label class="form-label mb-1">Tần suất mong muốn</label>
                                    <input type="number" name="order_frequency_count" class="form-control form-control-sm" min="1" value="{{ $customer->order_frequency_count ?? '' }}">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-1">Đơn vị</label>
                                    <select name="order_frequency_type" class="form-select form-select-sm">
                                        <option value="week" {{ $customer->order_frequency_type == 'week' ? 'selected' : '' }}>Tuần</option>
                                        <option value="month" {{ $customer->order_frequency_type == 'month' ? 'selected' : '' }}>Tháng</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save"></i> Cập nhật</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Card: Cuộc hẹn khách -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color:#0f766e;font-weight:700;">Cuộc hẹn khách hàng</h5>
                        <form method="POST" action="{{ route('customer_reminders.store', $customer) }}" class="mb-2">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <input type="text" name="title" class="form-control form-control-sm" placeholder="Nội dung cuộc hẹn...">
                                </div>
                                <div class="col-auto">
                                    <input type="datetime-local" name="remind_at" class="form-control form-control-sm">
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-calendar-plus"></i> Thêm</button>
                                </div>
                            </div>
                        </form>
                        <div style="max-height:120px;overflow:auto;">
                            @foreach(($reminders ?? $customer->reminders ?? []) as $reminder)
                                <div class="small mb-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="bi bi-calendar-event"></i> <span class="fw-bold">{{ $reminder->remind_at->format('d/m/Y H:i') }}</span> - {{ $reminder->title }}
                                        @if($reminder->remind_at->isToday())
                                            <span class="badge bg-danger ms-2"><i class="bi bi-bell"></i> Hôm nay</span>
                                        @elseif($reminder->remind_at->isTomorrow())
                                            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-bell"></i> Nhắc trước 1 ngày</span>
                                        @endif
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-link btn-sm text-primary p-0 me-2" data-bs-toggle="modal" data-bs-target="#editReminderModal{{ $reminder->id }}"><i class="bi bi-pencil-square"></i></button>
                                        <form method="POST" action="{{ route('customer_reminders.destroy', [$customer, $reminder]) }}" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Xóa cuộc hẹn này?')"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <!-- Edit Reminder Modal -->
                                <div class="modal fade" id="editReminderModal{{ $reminder->id }}" tabindex="-1" aria-labelledby="editReminderModalLabel{{ $reminder->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('customer_reminders.update', [$customer, $reminder]) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editReminderModalLabel{{ $reminder->id }}">Sửa cuộc hẹn</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-2">
                                                        <label class="form-label">Nội dung cuộc hẹn</label>
                                                        <input type="text" name="title" class="form-control" value="{{ $reminder->title }}" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">Thời gian nhắc</label>
                                                        <input type="datetime-local" name="remind_at" class="form-control" value="{{ $reminder->remind_at->format('Y-m-d\TH:i') }}" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">Ghi chú</label>
                                                        <textarea name="note" class="form-control">{{ $reminder->note }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Lưu</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="customer-alert" style="background:#dcfce7;color:#166534;">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="customer-alert" style="background:#fee2e2;color:#991b1b;">
                <ul style="margin:0;padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <style>
        .pro-filter-card {
            background: linear-gradient(90deg, #f8fafc 60%, #e0e7ef 100%);
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(15,23,42,0.07);
            margin-bottom: 0;
            padding: 0;
            border: none;
        }
        .pro-filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 18px;
            padding: 22px 28px 12px 28px;
        }
        .pro-filter-group {
            display: flex;
            flex-direction: column;
            min-width: 120px;
            margin-bottom: 10px;
        }
        .pro-filter-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .pro-filter-input, .pro-filter-select {
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            min-height: 38px;
            font-size: 15px;
            padding: 0 12px;
            background: #fff;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .pro-filter-input:focus, .pro-filter-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px #dbeafe;
            outline: none;
        }
        .pro-filter-btn {
            border-radius: 16px;
            min-width: 90px;
            font-weight: 700;
            font-size: 15px;
            padding: 0 18px;
        }
        .pro-filter-summary {
            font-size: 13px;
            color: #64748b;
            margin-left: 12px;
            margin-top: 8px;
        }
        @media (max-width: 900px) {
            .pro-filter-form { flex-direction: column; align-items: stretch; gap: 10px; padding: 18px 10px 8px 10px; }
            .pro-filter-group { min-width: 100px; }
        }
        </style>
        <div class="pro-filter-card">
            <form action="{{ route('my_customer.show', $customer) }}" method="GET" class="pro-filter-form">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="pro-filter-group">
                    <label class="pro-filter-label"><i class="bi bi-calendar2-week"></i> Thời gian</label>
                    <select name="period" class="pro-filter-select" onchange="toggleMyCustomerCustomRange(this.value)">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hôm nay</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Tuần</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Tháng</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Tùy chọn</option>
                    </select>
                </div>
                <div class="pro-filter-group customer-custom-range" style="display: {{ $period === 'custom' ? 'flex' : 'none' }};">
                    <label class="pro-filter-label"><i class="bi bi-arrow-right-circle"></i> Từ ngày</label>
                    <input type="date" name="from_date" class="pro-filter-input" value="{{ $fromDate->toDateString() }}">
                </div>
                <div class="pro-filter-group customer-custom-range" style="display: {{ $period === 'custom' ? 'flex' : 'none' }};">
                    <label class="pro-filter-label"><i class="bi bi-arrow-left-circle"></i> Đến ngày</label>
                    <input type="date" name="to_date" class="pro-filter-input" value="{{ $toDate->toDateString() }}">
                </div>
                <div class="pro-filter-group">
                    <label class="pro-filter-label"><i class="bi bi-list-check"></i> Trạng thái</label>
                    <select name="order_status" class="pro-filter-select">
                        <option value="">Tất cả</option>
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary pro-filter-btn"><i class="bi bi-funnel"></i> Lọc</button>
                <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-light pro-filter-btn"><i class="bi bi-x-circle"></i> Reset</a>
                <div class="pro-filter-summary">
                    <i class="bi bi-calendar-range"></i> {{ $periodLabel }} ({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})
                </div>
            </form>
        </div>

        <div class="customer-tabs mb-3" style="font-size:15px;"> 
            <a class="customer-tab{{ $activeTab === 'orders' ? ' active' : '' }}" href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'orders'] + $queryParams) }}">
                <i class="bi bi-bag-check"></i> Đơn hàng <span class="customer-tab-count">{{ $orders->total() }}</span>
            </a>
            <a class="customer-tab{{ $activeTab === 'payments' ? ' active' : '' }}" href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments'] + $queryParams) }}">
                <i class="bi bi-credit-card"></i> Thanh toán <span class="customer-tab-count">{{ $payments->total() }}</span>
            </a>
             <a class="customer-tab{{ $activeTab === 'debt' ? ' active' : '' }}" href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'debt'] + $queryParams) }}">
                <i class="bi bi-cash-coin"></i> Công nợ <span class="customer-tab-count">{{ $debtOrders->total() }}</span>
            </a>
            <a class="customer-tab{{ $activeTab === 'reports' ? ' active' : '' }}" href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'reports'] + $queryParams) }}">
                <i class="bi bi-graph-up"></i> Báo cáo
            </a>
        </div>


        @if($activeTab === 'debt')
            <div class="customer-grid">
                <div class="customer-card" style="grid-column: span 12;">
                    <div class="customer-card-body">
                        <h2 class="customer-section-title">Chi tiet cong no</h2>
                        <p class="customer-section-subtitle">Cong no duoc tu dong tinh tu Orders va Payments, khong nhap tay.</p>
                        <div class="customer-table-wrap">
                            <table class="customer-table">
                                <thead>
                                    <tr>
                                        <th>Ma don</th>
                                        <th>Gia tri don</th>
                                        <th>Da thanh toan</th>
                                        <th>Con lai</th>
                                        <th>Han thanh toan</th>
                                        <th>Trang thai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($debtOrders as $order)
                                        @php
                                            $paid = (float) $order->transactions->where('type', 'payment')->sum('amount') - (float) $order->transactions->where('type', 'refund')->sum('amount');
                                            $remaining = max((float) $order->total - $paid, 0);
                                            $dueDate = optional($order->created_at)?->copy()->addDays(30);
                                            $isOverdue = $remaining > 0 && $dueDate && $dueDate->isPast();
                                            $rowBadge = $remaining <= 0 ? 'badge-success' : ($isOverdue ? 'badge-danger' : 'badge-warning');
                                            $rowLabel = $remaining <= 0 ? 'Da thanh toan du' : ($isOverdue ? 'No qua han' : 'Con no');
                                        @endphp
                                        <tr>
                                            <td><a class="customer-link" href="{{ route('site.orders.show', $order) }}">{{ $order->code ?: ('#' . $order->id) }}</a></td>
                                            <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                            <td style="color:#15803d;font-weight:700;">{{ number_format($paid, 0, ',', '.') }} đ</td>
                                            <td style="color:{{ $remaining > 0 ? '#b91c1c' : '#15803d' }};font-weight:800;">{{ number_format($remaining, 0, ',', '.') }} đ</td>
                                            <td>{{ $dueDate ? $dueDate->format('d/m/Y') : '-' }}</td>
                                            <td><span class="customer-badge {{ $rowBadge }}">{{ $rowLabel }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="customer-empty">Khong co du lieu cong no trong khoang loc.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="customer-pagination">{{ $debtOrders->links() }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'orders')
            <div class="customer-grid">
                <div class="customer-report-grid" style="grid-column: span 12; margin-top:0;">
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tong so don</span>
                        <span class="value">{{ number_format($filteredOrderCount, 0, ',', '.') }}</span>
                    </div>
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tong gia tri don</span>
                        <span class="value">{{ number_format($filteredOrderTotal, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tong thanh toan trong ky</span>
                        <span class="value">{{ number_format($filteredPaidTotal, 0, ',', '.') }} đ</span>
                    </div>
                </div>
                <div class="customer-card" style="grid-column: span 12;">
                    <div class="customer-card-body">
                        <h2 class="customer-section-title">Don hang cua khach</h2>
                        <p class="customer-section-subtitle">Co the loc theo thoi gian va trang thai de theo doi nhanh hoat dong mua hang.</p>
                        <div class="customer-table-wrap">
                            <table class="customer-table">
                                <thead>
                                    <tr>
                                        <th>Ma don hang</th>
                                        <th>Ngay tao</th>
                                        <th>Tong tien</th>
                                        <th>Trang thai</th>
                                        <th>Xem chi tiet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php
                                            $status = (string) $order->status;
                                            $statusClass = 'badge-dark';
                                            if (in_array($status, ['completed', 'delivered'], true)) {
                                                $statusClass = 'badge-success';
                                            } elseif (in_array($status, ['shipping', 'delivering', 'in_delivery'], true)) {
                                                $statusClass = 'badge-info';
                                            } elseif (in_array($status, ['returned', 'returning', 'returned_completed'], true)) {
                                                $statusClass = 'badge-dark';
                                            } elseif (in_array($status, ['cancelled', 'rejected'], true)) {
                                                $statusClass = 'badge-danger';
                                            } elseif (in_array($status, ['pending', 'order_placed', 'approved', 'packing', 'ready_to_pack'], true)) {
                                                $statusClass = 'badge-warning';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $order->code ?: ('#' . $order->id) }}</td>
                                            <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                            <td><span class="customer-badge {{ $statusClass }}">{{ $status }}</span></td>
                                            <td><a class="customer-link" href="{{ route('site.orders.show', $order) }}">Xem don</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="customer-empty">Khong co don hang trong khoang loc.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="customer-pagination">{{ $orders->links() }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'payments')
            <div class="customer-grid">
                <div class="customer-two-column">
                    <div class="customer-card">
                        <div class="customer-card-body">
                            <h2 class="customer-section-title">Lich su thanh toan</h2>
                            <p class="customer-section-subtitle">Theo doi ngay thanh toan, phuong thuc, nguoi xac nhan va chung tu dinh kem.</p>
                            <div class="customer-table-wrap">
                                <table class="customer-table">
                                    <thead>
                                        <tr>
                                            <th>Ngay thanh toan</th>
                                            <th>Don hang</th>
                                            <th>So tien</th>
                                            <th>Phuong thuc</th>
                                            <th>Nguoi xac nhan</th>
                                            <th>Ghi chu</th>
                                            <th>Chung tu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payments as $payment)
                                            @php
                                                $methodLabel = match($payment->method) {
                                                    'cash' => 'Tien mat',
                                                    'bank_transfer' => 'Chuyen khoan',
                                                    default => $payment->method ?: '-',
                                                };
                                                $actorId = $transactionActorIds[$payment->id] ?? null;
                                                $actorName = $actorId ? ($actorNames[$actorId] ?? '-') : '-';
                                            @endphp
                                            <tr>
                                                <td>{{ optional($payment->created_at)->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    @if($payment->order)
                                                        <a class="customer-link" href="{{ route('site.orders.show', $payment->order) }}">{{ $payment->order->code ?: ('#' . $payment->order->id) }}</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td style="color:#15803d;font-weight:800;">{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</td>
                                                <td>{{ $methodLabel }}</td>
                                                <td>{{ $actorName }}</td>
                                                <td>{{ $payment->note ?: '-' }}</td>
                                                <td>
                                                    @if($payment->receipt_image_path)
                                                        <a class="customer-link" target="_blank" href="{{ asset('storage/' . $payment->receipt_image_path) }}">Xem</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="customer-empty">Chua co lich su thanh toan trong khoang loc.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="customer-pagination">{{ $payments->links() }}</div>
                        </div>
                    </div>

                    <div class="customer-card">
                        <div class="customer-card-body">
                            <h2 class="customer-section-title">Them thanh toan</h2>
                            <p class="customer-section-subtitle">Validate so tien > 0 va khong duoc vuot qua cong no hien tai.</p>
                            <form action="{{ route('my_customer.payments.store', $customer) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="period" value="{{ $period }}">
                                <input type="hidden" name="from_date" value="{{ $fromDate->toDateString() }}">
                                <input type="hidden" name="to_date" value="{{ $toDate->toDateString() }}">
                                <input type="hidden" name="order_status" value="{{ request('order_status') }}">
                                <input type="hidden" name="orders_per_page" value="{{ request('orders_per_page', 10) }}">
                                <input type="hidden" name="debt_per_page" value="{{ request('debt_per_page', 10) }}">
                                <input type="hidden" name="payments_per_page" value="{{ request('payments_per_page', 10) }}">
                                <div class="customer-form-grid">
                                    <div class="customer-field full">
                                        <label class="customer-label">So tien</label>
                                        <input type="number" name="amount" min="0.01" step="0.01" max="{{ max($totalDebtAmount, 0) }}" class="customer-input" value="{{ old('amount') }}" required>
                                        <div class="customer-muted" style="margin-top:8px;">Cong no hien tai: {{ number_format($totalDebtAmount, 0, ',', '.') }} đ</div>
                                    </div>
                                    <div class="customer-field full">
                                        <label class="customer-label">Phuong thuc</label>
                                        <select name="method" class="customer-select" required>
                                            <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Tien mat</option>
                                            <option value="bank_transfer" {{ old('method') === 'bank_transfer' ? 'selected' : '' }}>Chuyen khoan</option>
                                        </select>
                                    </div>
                                    <div class="customer-field full">
                                        <label class="customer-label">Ghi chu</label>
                                        <textarea name="note" class="customer-textarea">{{ old('note') }}</textarea>
                                    </div>
                                    <div class="customer-field full">
                                        <label class="customer-label">Upload chung tu</label>
                                        <input type="file" name="receipt_image" class="customer-input" accept="image/*" style="padding-top:10px;">
                                    </div>
                                    <div class="customer-field full">
                                        <button type="submit" class="customer-btn customer-btn-primary" style="border:none;">Them thanh toan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'reports')
            @php
                $reportSubtotalAmount = (float) $reportByMonth->sum('subtotal_amount');
                $reportItemDiscountAmount = (float) $reportByMonth->sum('item_discount_total');
                $reportExtraDiscountAmount = (float) $reportByMonth->sum('extra_discount_total');
            @endphp
            <div class="customer-grid">
                <div class="customer-report-grid" style="grid-column: span 12;">
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tien hang theo thoi gian</span>
                        <span class="value">{{ number_format($reportSubtotalAmount, 0, ',', '.') }} đ</span>
                        <div class="customer-muted">Tong truoc giam gia trong ky loc</div>
                    </div>
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tien giam theo thoi gian</span>
                        <span class="value">{{ number_format($reportItemDiscountAmount + $reportExtraDiscountAmount, 0, ',', '.') }} đ</span>
                        <div class="customer-muted">Discount va discount ngoai</div>
                    </div>
                    <div class="customer-report-card">
                        <span class="customer-summary-label">Tong tien cuoi</span>
                        <span class="value">{{ number_format($reportOrderTotal, 0, ',', '.') }} đ</span>
                        <div class="customer-muted">Gia tri don sau giam gia</div>
                    </div>
                </div>
                <div class="customer-card" style="grid-column: span 12;">
                    <div class="customer-card-body">
                        <h2 class="customer-section-title">Tong hop theo thang</h2>
                        <p class="customer-section-subtitle">Theo doi doanh thu, so don va cong no cua khach qua tung thang.</p>
                        <div class="customer-table-wrap">
                            <table class="customer-table">
                                <thead>
                                    <tr>
                                        <th>Thang</th>
                                        <th>So don</th>
                                        <th>Tien hang</th>
                                        <th>Tien giam</th>
                                        <th>Giam them</th>
                                        <th>Tong gia tri don</th>
                                        <th>Tong da thanh toan</th>
                                        <th>Cong no</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportByMonth as $row)
                                        <tr>
                                            <td>{{ $row['period'] ?: '-' }}</td>
                                            <td>{{ number_format((int) $row['order_count'], 0, ',', '.') }}</td>
                                            <td>{{ number_format((float) ($row['subtotal_amount'] ?? 0), 0, ',', '.') }} đ</td>
                                            <td>{{ number_format((float) ($row['item_discount_total'] ?? 0), 0, ',', '.') }} đ</td>
                                            <td>{{ number_format((float) ($row['extra_discount_total'] ?? 0), 0, ',', '.') }} đ</td>
                                            <td>{{ number_format((float) $row['order_total'], 0, ',', '.') }} đ</td>
                                            <td style="color:#15803d;font-weight:700;">{{ number_format((float) $row['paid_total'], 0, ',', '.') }} đ</td>
                                            <td style="color:{{ (float) $row['outstanding_total'] > 0 ? '#b91c1c' : '#15803d' }};font-weight:800;">{{ number_format((float) $row['outstanding_total'], 0, ',', '.') }} đ</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="customer-empty">Khong co du lieu bao cao trong khoang loc.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function toggleMyCustomerCustomRange(period) {
        document.querySelectorAll('.customer-custom-range').forEach(function (element) {
            element.style.display = period === 'custom' ? 'block' : 'none';
        });
    }
</script>
@endsection
