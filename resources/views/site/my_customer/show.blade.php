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
        <div class="customer-hero">
            <div class="customer-hero-top">
                <div>
                    <h1 class="customer-hero-title">{{ $customer->name }}</h1>
                    <p class="customer-hero-subtitle">Trang chi tiet khach hang da duoc nang cap voi thong tin kinh doanh, cong no, don hang, thanh toan va bao cao nhanh de sale thao tac ngay tai /my-customer/{{ $customer->id }}.</p>
                </div>
                <div class="customer-action-group">
                    <a href="{{ route('my_customer.edit', $customer) }}" class="customer-btn customer-btn-outline">Chinh sua</a>
                    @if($customer->phone)
                        <a href="tel:{{ preg_replace('/\D+/', '', $customer->phone) }}" class="customer-btn customer-btn-outline">Goi nhanh</a>
                    @endif
                    <a href="{{ route('my_customer.order.create', $customer) }}" class="customer-btn customer-btn-primary">Tao don moi</a>
                    <a href="{{ route('pages.my_customer') }}" class="customer-btn customer-btn-soft">Quay lai</a>
                </div>
            </div>

            <div class="customer-hero-meta">
                <div class="customer-hero-meta-item">
                    <span class="customer-hero-meta-label">Dien thoai</span>
                    <div class="customer-hero-meta-value">{{ $customer->phone ?: '-' }}</div>
                </div>
                <div class="customer-hero-meta-item">
                    <span class="customer-hero-meta-label">Email</span>
                    <div class="customer-hero-meta-value">{{ $customer->email ?: '-' }}</div>
                </div>
                <div class="customer-hero-meta-item">
                    <span class="customer-hero-meta-label">Loai khach</span>
                    <div class="customer-hero-meta-value">{{ optional($customer->type)->name ?: 'Chua phan loai' }}</div>
                </div>
                <div class="customer-hero-meta-item">
                    <span class="customer-hero-meta-label">Nguoi phu trach</span>
                    <div class="customer-hero-meta-value">{{ optional($customer->assignedTo)->name ?: auth()->user()->name }}</div>
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

        <div class="customer-grid">
            <div class="customer-card customer-filter-card">
                <div class="customer-card-body">
                    <h2 class="customer-section-title">Bo loc du lieu</h2>
                    <p class="customer-section-subtitle">Ap dung cho don hang, thanh toan va bao cao theo khach hang.</p>
                    <form action="{{ route('my_customer.show', $customer) }}" method="GET" class="customer-form-grid">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="customer-field">
                            <label class="customer-label">Khoang thoi gian</label>
                            <select name="period" class="customer-select" onchange="toggleMyCustomerCustomRange(this.value)">
                                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hom nay</option>
                                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Tuan nay</option>
                                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Thang nay</option>
                                <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Khoang tuy chon</option>
                            </select>
                        </div>
                        <div class="customer-field customer-custom-range" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                            <label class="customer-label">Tu ngay</label>
                            <input type="date" name="from_date" class="customer-input" value="{{ $fromDate->toDateString() }}">
                        </div>
                        <div class="customer-field customer-custom-range" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                            <label class="customer-label">Den ngay</label>
                            <input type="date" name="to_date" class="customer-input" value="{{ $toDate->toDateString() }}">
                        </div>
                        <div class="customer-field wide">
                            <label class="customer-label">Trang thai don hang</label>
                            <select name="order_status" class="customer-select">
                                <option value="">Tat ca</option>
                                @foreach($orderStatuses as $status)
                                    <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="customer-field full">
                            <div class="customer-inline-actions">
                                <button type="submit" class="customer-btn customer-btn-primary" style="border:none;">Loc du lieu</button>
                                <a href="{{ route('my_customer.show', $customer) }}" class="customer-btn customer-btn-soft">Reset</a>
                                <span class="customer-muted">Bo loc hien tai: {{ $periodLabel }} ({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="customer-summary-grid">
                <div class="customer-summary-card total">
                    <span class="customer-summary-label">Tong tien don hang</span>
                    <div class="customer-summary-value">{{ number_format($totalOrderAmount, 0, ',', '.') }} đ</div>
                    <div class="customer-summary-note">Doanh thu toan bo cua khach hang</div>
                </div>
                <div class="customer-summary-card paid">
                    <span class="customer-summary-label">Tong da thanh toan</span>
                    <div class="customer-summary-value">{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</div>
                    <div class="customer-summary-note">Tat ca thanh toan da xac nhan</div>
                </div>
                <div class="customer-summary-card debt">
                    <span class="customer-summary-label">Cong no hien tai</span>
                    <div class="customer-summary-value">{{ number_format($totalDebtAmount, 0, ',', '.') }} đ</div>
                    <div class="customer-summary-note"><span class="customer-badge {{ $debtStatusClass }}">{{ $debtStatusLabel }}</span></div>
                </div>
            </div>
        </div>

        <div class="customer-tabs">
            <a class="customer-tab {{ $activeTab === 'info' ? 'active' : '' }}" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'info'], $queryParams)) }}">Thong tin</a>
            <a class="customer-tab {{ $activeTab === 'debt' ? 'active' : '' }}" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'debt'], $queryParams)) }}">Cong no<span class="customer-tab-count">{{ $debtOrders->total() }}</span></a>
            <a class="customer-tab {{ $activeTab === 'orders' ? 'active' : '' }}" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'orders'], $queryParams)) }}">Don hang<span class="customer-tab-count">{{ $orders->total() }}</span></a>
            <a class="customer-tab {{ $activeTab === 'payments' ? 'active' : '' }}" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'payments'], $queryParams)) }}">Thanh toan<span class="customer-tab-count">{{ $payments->total() }}</span></a>
            <a class="customer-tab {{ $activeTab === 'reports' ? 'active' : '' }}" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'reports'], $queryParams)) }}">Bao cao</a>
        </div>

        @if($activeTab === 'info')
            <div class="customer-grid">
                <div class="customer-card" style="grid-column: span 12;">
                    <div class="customer-card-body">
                        <h2 class="customer-section-title">Thong tin khach hang</h2>
                        <p class="customer-section-subtitle">Tong hop ho so kinh doanh va thong tin lien he cua khach hang.</p>
                        <div class="customer-info-grid">
                            <div class="customer-info-item"><span class="customer-info-label">Ten khach hang</span><div class="customer-info-value">{{ $customer->name }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">So dien thoai</span><div class="customer-info-value">{{ $customer->phone ?: '-' }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Email</span><div class="customer-info-value">{{ $customer->email ?: '-' }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Loai khach</span><div class="customer-info-value">{{ optional($customer->type)->name ?: '-' }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Khu vuc / tinh thanh</span><div class="customer-info-value">{{ $region ?: '-' }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Nguoi phu trach</span><div class="customer-info-value">{{ optional($customer->assignedTo)->name ?: auth()->user()->name }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Ngay tao khach</span><div class="customer-info-value">{{ optional($customer->created_at)->format('d/m/Y H:i') ?: '-' }}</div></div>
                            <div class="customer-info-item"><span class="customer-info-label">Dia chi</span><div class="customer-info-value">{{ $fullAddress ?: '-' }}</div></div>
                        </div>
                    </div>
                </div>
                <div class="customer-two-column">
                    <div class="customer-card">
                        <div class="customer-card-body">
                            <h2 class="customer-section-title">Danh sach don hang gan day</h2>
                            <p class="customer-section-subtitle">5 don hang moi nhat theo bo loc hien tai.</p>
                            <div class="customer-list-stack">
                                @forelse($recentOrders as $order)
                                    @php
                                        $paid = (float) $order->transactions->where('type', 'payment')->sum('amount') - (float) $order->transactions->where('type', 'refund')->sum('amount');
                                        $remaining = max((float) $order->total - $paid, 0);
                                    @endphp
                                    <div class="customer-list-item">
                                        <div>
                                            <div class="customer-list-item-title">{{ $order->code ?: ('#' . $order->id) }}</div>
                                            <div class="customer-list-meta">
                                                <span>{{ optional($order->created_at)->format('d/m/Y H:i') }}</span>
                                                <span>{{ $order->status ?: '-' }}</span>
                                                <span>Con lai: {{ number_format($remaining, 0, ',', '.') }} đ</span>
                                            </div>
                                        </div>
                                        <div class="customer-list-amount">
                                            <div>{{ number_format((float) $order->total, 0, ',', '.') }} đ</div>
                                            <a class="customer-link" href="{{ route('site.orders.show', $order) }}">Xem don</a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="customer-empty">Chua co don hang nao trong khoang loc.</div>
                                @endforelse
                            </div>
                            <div class="customer-inline-actions">
                                <a class="customer-btn customer-btn-soft" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'orders'], $queryParams)) }}">Xem tat ca don hang</a>
                            </div>
                        </div>
                    </div>
                    <div class="customer-card">
                        <div class="customer-card-body">
                            <h2 class="customer-section-title">Thanh toan gan day</h2>
                            <p class="customer-section-subtitle">5 giao dich thanh toan moi nhat cua khach hang.</p>
                            <div class="customer-list-stack">
                                @forelse($recentPayments as $payment)
                                    @php
                                        $methodLabel = match($payment->method) {
                                            'cash' => 'Tien mat',
                                            'bank_transfer' => 'Chuyen khoan',
                                            default => $payment->method ?: '-',
                                        };
                                        $actorId = $transactionActorIds[$payment->id] ?? null;
                                        $actorName = $actorId ? ($actorNames[$actorId] ?? '-') : '-';
                                    @endphp
                                    <div class="customer-list-item">
                                        <div>
                                            <div class="customer-list-item-title">{{ $payment->order?->code ?: 'Khong gan don' }}</div>
                                            <div class="customer-list-meta">
                                                <span>{{ optional($payment->created_at)->format('d/m/Y H:i') }}</span>
                                                <span>{{ $methodLabel }}</span>
                                                <span>{{ $actorName }}</span>
                                            </div>
                                        </div>
                                        <div class="customer-list-amount" style="color:#15803d;">
                                            <div>{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</div>
                                            @if($payment->receipt_image_path)
                                                <a class="customer-link" target="_blank" href="{{ asset('storage/' . $payment->receipt_image_path) }}">Chung tu</a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="customer-empty">Chua co thanh toan nao trong khoang loc.</div>
                                @endforelse
                            </div>
                            <div class="customer-inline-actions">
                                <a class="customer-btn customer-btn-soft" href="{{ route('my_customer.show', array_merge(['customer' => $customer, 'tab' => 'payments'], $queryParams)) }}">Xem tat ca thanh toan</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
