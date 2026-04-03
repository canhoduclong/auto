@extends('layouts.site')

@push('styles')
<style>
    .wr-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .wr-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .wr-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        position: relative;
        overflow: hidden;
    }
    .wr-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .wr-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 18px;
        min-height: 100%;
    }
    .wr-kpi-label {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .wr-kpi-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
    }
    .wr-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .wr-filter {
        padding: 24px;
    }
    .wr-tablist {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .wr-tab-btn {
        border: 1px solid #c9d4e7;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 9px 14px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
    }
    .wr-tab-btn.active {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
        box-shadow: 0 8px 18px rgba(29, 78, 216, 0.25);
    }
    .wr-range-fields {
        display: none;
    }
    .wr-range-fields.active {
        display: flex;
    }
    .wr-filter .form-control {
        height: 44px;
        border-radius: 12px;
        border-color: #d8deea;
    }
    .wr-filter .btn {
        height: 44px;
        border-radius: 12px;
        font-weight: 700;
    }
    .wr-summary {
        border-top: 1px solid #e7ecf3;
        margin-top: 18px;
        padding-top: 14px;
        color: #475569;
        font-size: .92rem;
    }
    .wr-stats {
        padding: 0 24px 20px;
    }
    .wr-stat-card {
        border: 1px solid #e4ebf5;
        border-radius: 16px;
        padding: 14px;
        background: #fff;
        height: 100%;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }
    .wr-stat-label {
        font-size: .76rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 6px;
    }
    .wr-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
    }
    .wr-content-tabs {
        border-bottom: 1px solid #e8edf5;
        padding: 0 24px;
    }
    .wr-content-tab-btn {
        border: 0;
        background: transparent;
        color: #475569;
        font-weight: 700;
        padding: 14px 2px;
        margin-right: 16px;
        border-bottom: 2px solid transparent;
        cursor: pointer;
    }
    .wr-content-tab-btn.active {
        color: #1e293b;
        border-bottom-color: #1d4ed8;
    }
    .wr-content-pane {
        display: none;
        padding: 18px 24px 24px;
    }
    .wr-content-pane.active {
        display: block;
    }
    .wr-table {
        margin-bottom: 0;
    }
    .wr-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        white-space: nowrap;
        padding: 14px 12px;
    }
    .wr-table tbody td {
        padding: 14px 12px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .wr-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .75rem;
        font-weight: 700;
    }
    .wr-badge.pending { background: #fff7ed; color: #c2410c; }
    .wr-badge.progress { background: #eff6ff; color: #1d4ed8; }
    .wr-badge.success { background: #ecfdf5; color: #047857; }
    .wr-badge.danger { background: #fef2f2; color: #b91c1c; }
    .wr-badge.muted { background: #f1f5f9; color: #475569; }
    .wr-empty {
        text-align: center;
        color: #64748b;
        padding: 26px 12px;
    }
    .wr-activity-meta {
        font-size: .8rem;
        color: #64748b;
    }
    .wr-activity-role {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: .72rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
        text-transform: uppercase;
    }
    .wr-activity-link {
        font-weight: 700;
        text-decoration: none;
    }
    @media (max-width: 767.98px) {
        .wr-page {
            padding: 20px 0 48px;
        }
        .wr-shell {
            padding: 0 12px;
        }
        .wr-filter,
        .wr-stats,
        .wr-content-tabs,
        .wr-content-pane {
            padding-left: 16px;
            padding-right: 16px;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusClasses = [
        \App\Models\Order::STATUS_COMPLETED => 'success',
        \App\Models\Order::STATUS_DELIVERED => 'success',
        \App\Models\Order::STATUS_ORDER_PLACED => 'pending',
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 'progress',
        \App\Models\Order::STATUS_PACKED => 'progress',
        \App\Models\Order::STATUS_IN_DELIVERY => 'progress',
        \App\Models\Order::STATUS_READY_TO_PACK => 'pending',
        \App\Models\Order::STATUS_PACKING => 'progress',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'progress',
        \App\Models\Order::STATUS_DELIVERING => 'progress',
        \App\Models\Order::STATUS_RETURNING => 'danger',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'muted',
        \App\Models\Order::STATUS_RETURNED => 'danger',
        \App\Models\Order::STATUS_CANCELLED => 'danger',
        'shipping' => 'progress',
        'picked_up' => 'progress',
    ];

    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ giao đơn vị vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];
@endphp

<section class="wr-page">
    <div class="container wr-shell">
        <div class="wr-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Work Reports</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Báo cáo công việc cá nhân</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:540px;">
                        Theo dõi hiệu suất bán hàng của bạn theo ngày, tuần, tháng hoặc khoảng thời gian tùy chọn.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="wr-kpi">
                                <div class="wr-kpi-label">Đơn hàng</div>
                                <div class="wr-kpi-value">{{ number_format($orderCount ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="wr-kpi">
                                <div class="wr-kpi-label">Khách hàng mới</div>
                                <div class="wr-kpi-value">{{ number_format($newCustomerCount ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="wr-kpi">
                                <div class="wr-kpi-label">Doanh thu</div>
                                <div class="wr-kpi-value">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }}đ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wr-panel mb-4">
            <div class="wr-filter">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1 fw-bold">Bộ lọc thời gian</h2>
                        <p class="mb-0 text-muted">Điều chỉnh theo mốc thời gian để xem đúng hiệu suất.</p>
                    </div>
                    <a href="{{ route('work-reports.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-refresh me-1"></i>Đặt lại
                    </a>
                </div>

                <form method="GET" action="{{ route('work-reports.index') }}" id="workReportFilterForm">
                    <input type="hidden" name="type" id="reportTypeInput" value="{{ $type ?? 'month' }}">
                    <input type="hidden" name="date" value="{{ $date ?? now()->toDateString() }}">
                    <input type="hidden" name="tab" id="reportTabInput" value="{{ $tab ?? 'orders' }}">
                    <input type="hidden" name="per_page" id="reportPerPageInput" value="{{ $perPage ?? 10 }}">

                    <div class="wr-tablist mb-3" role="tablist" aria-label="Khoảng thời gian">
                        <button type="button" class="wr-tab-btn {{ ($type ?? 'month') === 'today' ? 'active' : '' }}" data-type="today">Hôm nay</button>
                        <button type="button" class="wr-tab-btn {{ ($type ?? 'month') === 'week' ? 'active' : '' }}" data-type="week">Tuần này</button>
                        <button type="button" class="wr-tab-btn {{ ($type ?? 'month') === 'month' ? 'active' : '' }}" data-type="month">Tháng này</button>
                        <button type="button" class="wr-tab-btn {{ ($type ?? 'month') === 'all' ? 'active' : '' }}" data-type="all">Tất cả</button>
                        <button type="button" class="wr-tab-btn {{ ($type ?? 'month') === 'range' ? 'active' : '' }}" data-type="range">Tùy chọn</button>
                    </div>

                    <div class="row g-2 align-items-end wr-range-fields {{ ($type ?? 'month') === 'range' ? 'active' : '' }}" id="rangeFilterGroup">
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold" for="from_date">Từ ngày</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $fromDate ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold" for="to_date">Đến ngày</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $toDate ?? '' }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3 {{ ($type ?? 'month') === 'range' ? '' : 'd-none' }}" id="rangeSubmitWrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search me-1"></i>Xem báo cáo
                        </button>
                    </div>
                </form>

                <div class="wr-summary">
                    @if(($type ?? 'month') === 'all')
                        Đang hiển thị dữ liệu cho <strong>tất cả thời gian</strong>.
                    @else
                        Đang hiển thị dữ liệu từ <strong>{{ $start->format('d/m/Y') }}</strong> đến <strong>{{ $end->format('d/m/Y') }}</strong>.
                    @endif
                </div>
            </div>

            <div class="wr-stats">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="wr-stat-card">
                            <div class="wr-stat-label">Tổng khách hàng</div>
                            <div class="wr-stat-value">{{ number_format($totalCustomerCount ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="wr-stat-card">
                            <div class="wr-stat-label">Khách hàng tương tác</div>
                            <div class="wr-stat-value">{{ number_format($interactingCustomerCount ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="wr-stat-card">
                            <div class="wr-stat-label">Khách hàng cũ có đơn</div>
                            <div class="wr-stat-value">{{ number_format($oldCustomerCount ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="wr-stat-card">
                            <div class="wr-stat-label">Tổng công nợ</div>
                            <div class="wr-stat-value">{{ number_format($totalDebt ?? 0, 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wr-content-tabs" role="tablist" aria-label="Dữ liệu báo cáo">
                <button type="button" class="wr-content-tab-btn {{ ($tab ?? 'orders') === 'orders' ? 'active' : '' }}" data-pane="orders">
                    Đơn hàng ({{ number_format($tabCounts['orders'] ?? 0) }})
                </button>
                <button type="button" class="wr-content-tab-btn {{ ($tab ?? 'orders') === 'new-customers' ? 'active' : '' }}" data-pane="new-customers">
                    Khách hàng mới ({{ number_format($tabCounts['new-customers'] ?? 0) }})
                </button>
                <button type="button" class="wr-content-tab-btn {{ ($tab ?? 'orders') === 'all-customers' ? 'active' : '' }}" data-pane="all-customers">
                    Tất cả khách hàng ({{ number_format($tabCounts['all-customers'] ?? 0) }})
                </button>
                <button type="button" class="wr-content-tab-btn {{ ($tab ?? 'orders') === 'daily-activities' ? 'active' : '' }}" data-pane="daily-activities">
                    Hoạt động hằng ngày ({{ number_format($tabCounts['daily-activities'] ?? 0) }})
                </button>
            </div>

            <div class="d-flex justify-content-end align-items-center gap-2 px-4 pt-3">
                <label for="tabPerPageSelect" class="small text-muted mb-0">Hiển thị</label>
                <select id="tabPerPageSelect" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} / trang</option>
                    @endforeach
                </select>
            </div>

            <div id="workReportTabContainer" class="pt-2 pb-2">
                @include('site.partials.work_report_tab_content', [
                    'tab' => $tab ?? 'orders',
                    'tabData' => $tabData,
                    'statusClasses' => $statusClasses,
                    'statusLabels' => $statusLabels,
                    'activityByDay' => $activityByDay,
                    'activityCount' => $activityCount,
                    'activeUserCount' => $activeUserCount,
                ])
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('workReportFilterForm');
        const typeInput = document.getElementById('reportTypeInput');
        const tabInput = document.getElementById('reportTabInput');
        const perPageInput = document.getElementById('reportPerPageInput');
        const rangeGroup = document.getElementById('rangeFilterGroup');
        const rangeSubmitWrap = document.getElementById('rangeSubmitWrap');
        const tabContainer = document.getElementById('workReportTabContainer');
        const perPageSelect = document.getElementById('tabPerPageSelect');

        const updateFilterTabState = (activeType) => {
            document.querySelectorAll('.wr-tab-btn').forEach((btn) => {
                btn.classList.toggle('active', btn.getAttribute('data-type') === activeType);
            });

            const isRange = activeType === 'range';
            rangeGroup.classList.toggle('active', isRange);
            if (rangeSubmitWrap) {
                rangeSubmitWrap.classList.toggle('d-none', !isRange);
            }
        };

        document.querySelectorAll('.wr-tab-btn').forEach((button) => {
            button.addEventListener('click', function () {
                const type = this.getAttribute('data-type') || 'month';
                typeInput.value = type;
                updateFilterTabState(type);

                if (type !== 'range') {
                    loadTab(1);
                }
            });
        });

        const updateTabHeaderState = (activeTab) => {
            document.querySelectorAll('.wr-content-tab-btn').forEach((btn) => {
                btn.classList.toggle('active', btn.getAttribute('data-pane') === activeTab);
            });
        };

        const updateTabCounts = (counts) => {
            if (!counts) {
                return;
            }

            const labels = {
                'orders': 'Đơn hàng',
                'new-customers': 'Khách hàng mới',
                'all-customers': 'Tất cả khách hàng',
                'daily-activities': 'Hoạt động hằng ngày'
            };

            Object.keys(labels).forEach((key) => {
                const button = document.querySelector('.wr-content-tab-btn[data-pane="' + key + '"]');
                if (button) {
                    const value = Number(counts[key] || 0).toLocaleString('vi-VN');
                    button.textContent = labels[key] + ' (' + value + ')';
                }
            });
        };

        const loadTab = (page = 1) => {
            const params = new URLSearchParams(new FormData(form));
            params.set('ajax_tab', '1');
            params.set('page', String(page));

            fetch('{{ route('work-reports.index') }}?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then((response) => response.json())
                .then((data) => {
                    tabContainer.innerHTML = data.html || '';
                    updateTabCounts(data.counts || null);
                    updateTabHeaderState(data.tab || tabInput.value || 'orders');

                    const nextUrlParams = new URLSearchParams(new FormData(form));
                    nextUrlParams.set('page', String(page));
                    window.history.replaceState({}, '', '{{ route('work-reports.index') }}?' + nextUrlParams.toString());
                })
                .catch(() => {
                    // Keep current content if request fails.
                });
        };

        document.querySelectorAll('.wr-content-tab-btn').forEach((button) => {
            button.addEventListener('click', function () {
                const pane = this.getAttribute('data-pane') || 'orders';
                tabInput.value = pane;
                loadTab(1);
            });
        });

        if (perPageSelect) {
            perPageSelect.addEventListener('change', function () {
                perPageInput.value = this.value;
                loadTab(1);
            });
        }

        tabContainer.addEventListener('click', function (event) {
            const pageLink = event.target.closest('.pagination a');
            if (!pageLink) {
                return;
            }

            event.preventDefault();
            const url = new URL(pageLink.getAttribute('href'), window.location.origin);
            const page = parseInt(url.searchParams.get('page') || '1', 10);
            loadTab(page > 0 ? page : 1);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const currentTab = tabInput.value || 'orders';
            tabInput.value = currentTab;
            perPageInput.value = perPageSelect ? perPageSelect.value : (perPageInput.value || '10');
            loadTab(1);
        });

        updateFilterTabState(typeInput.value || 'month');
    })();
</script>
@endpush
@endsection
