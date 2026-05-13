@extends('layouts.site')

@section('title', 'My Dashboard')

@push('styles')
<style>
    .my-dashboard {
        background: linear-gradient(180deg, #f6faf9 0%, #eef4f8 100%);
        min-height: calc(100vh - 80px);
        padding: 24px 0 36px;
    }

    .my-dashboard .hero-card {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #0f766e, #15803d);
        color: #fff;
        box-shadow: 0 10px 30px rgba(15, 118, 110, 0.25);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(120px, 1fr));
        gap: 12px;
    }

    .stat-card {
        border-radius: 14px;
        border: 1px solid #dbe4ea;
        background: #fff;
        padding: 14px;
        min-height: 120px;
    }

    .stat-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
    }

    .shortcut-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        background: #0f766e;
        color: #fff;
        text-decoration: none;
    }

    .section-card {
        border: 1px solid #dbe4ea;
        border-radius: 14px;
        background: #fff;
    }

    .feed-item {
        border-bottom: 1px solid #eef2f7;
        padding: 12px 0;
    }

    .feed-item:last-child {
        border-bottom: 0;
    }

    .timeline-item {
        border-left: 2px solid #dbe4ea;
        padding-left: 12px;
        margin-left: 8px;
        margin-bottom: 12px;
    }

    .timeline-item .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #0f766e;
        position: relative;
        left: -18px;
        top: 16px;
    }

    .assigned-customer-card {
        transition: all 0.2s ease;
        border-left: 3px solid #0f766e !important;
    }

    .assigned-customer-card:hover {
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.1);
    }

    .accept-customer-btn {
        white-space: nowrap;
        font-size: 12px;
        padding: 4px 8px;
    }

    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(3, minmax(120px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(120px, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="my-dashboard">
    <div class="container">
        <div class="card hero-card mb-3">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                <div>
                    <h4 class="mb-1">My Dashboard</h4>
                    <div class="small opacity-75">Thống kê nhanh doanh số, hoa hồng và công việc</div>
                </div>
                <a href="{{ route('my-tasks') }}" class="shortcut-btn">
                    <i class="bi bi-list-task"></i>
                    Nhiệm vụ được giao
                </a>
            </div>
        </div>

        <div class="stats-grid mb-3" id="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Tổng doanh số</div>
                <div class="stat-value" data-stat="total_revenue">{{ number_format($dashboardStats['total_revenue'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Hoa hồng tháng này</div>
                <div class="stat-value" data-stat="commission_this_month">{{ number_format($dashboardStats['commission_this_month'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tổng khách hàng</div>
                <div class="stat-value" data-stat="total_customers">{{ number_format($dashboardStats['total_customers'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Khách đang hoạt động</div>
                <div class="stat-value" data-stat="active_customers">{{ number_format($dashboardStats['active_customers'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Đơn tháng này</div>
                <div class="stat-value" data-stat="orders_this_month">{{ number_format($dashboardStats['orders_this_month'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Task đang xử lý</div>
                <div class="stat-value" data-stat="tasks_processing">{{ number_format($dashboardStats['tasks_processing'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Task chưa hoàn thành</div>
                <div class="stat-value" data-stat="tasks_unfinished">{{ number_format($dashboardStats['tasks_unfinished'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                

                <div class="section-card p-3" id="commission-feed">
                    <h6 class="mb-2">Chúc mừng nhận hoa hồng</h6>
                    @forelse($commissionFeed as $item)
                        <div class="feed-item">
                            <div class="fw-semibold">{{ $item->order_code ?: ('#' . $item->order_id) }} - {{ $item->customer_name ?: 'Khách hàng' }}</div>
                            <div class="small text-muted">
                                Giá trị đơn: {{ number_format((float) $item->order_total, 0, ',', '.') }} |
                                {{ number_format((float) $item->commission_percent, 2) }}% |
                                Hoa hồng: <span class="text-success fw-semibold">{{ number_format((float) $item->commission_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="small text-muted">{{ \Carbon\Carbon::parse($item->confirmed_at)->format('d/m/Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Chưa có bản ghi hoa hồng.</div>
                    @endforelse
                </div>
                <div class="section-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Biểu đồ doanh số</h6>
                        <small class="text-muted">Cập nhật tự động mỗi 30 giây</small>
                    </div>
                    <canvas id="salesChart" height="120"></canvas>
                </div>

            </div>

            <div class="col-lg-5">
                {{-- Assigned Customers Section --}}
                <h6 class="mx-1 mb-3 text-uppercase">Khách hàng mới</h6>
                <div class="pb-3 mb-3" id="assigned-customers"> 
                    @forelse($assignedCustomers as $idx => $customer)
                        <div class="assigned-customer-card p-2 mb-2 border rounded" style="background: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="d-flex  border-right   text-muted justify-content-center align-items-center " style="width: 30px;"> 
                                    <span class="fs-2">{{ $idx + 1 }}</span>
                                </div>
                                <div class="flex-grow-1">                                   
                                    <div class="small fw-semibold">{{ $customer['name'] }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone"></i> {{ $customer['phone'] ?: '—' }}
                                        @if($customer['address'])
                                            <br><i class="bi bi-geo-alt"></i> {{ $customer['address'] }}
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm {{ $customer['is_accepted'] ? 'btn-success' : 'btn-outline-primary' }} accept-customer-btn" 
                                        data-customer-id="{{ $customer['id'] }}" 
                                        {{ $customer['is_accepted'] ? 'disabled' : '' }}>
                                    @if($customer['is_accepted'])
                                        <i class="bi bi-check-circle"></i> Đã nhận
                                    @else
                                        <i class="bi bi-plus-circle"></i> Nhận
                                    @endif
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">Không có khách hàng nào được giao.</div>
                    @endforelse
                </div>

                {{-- Activity Timeline Section --}}
                <div class="section-card p-3" id="activity-timeline">
                    <h6 class="mb-2">Timeline hoạt động task</h6>
                    @forelse($timeline as $log)
                        <div class="timeline-item">
                            <div class="dot"></div>
                            <div class="small fw-semibold">{{ $log->task_code }} - {{ $log->task_title }}</div>
                            <div class="small text-muted">{{ $log->from_status }} → {{ $log->to_status }}</div>
                            <div class="small text-muted">{{ $log->changed_by_name ?: 'System' }} - {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Chưa có hoạt động gần đây.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartLabels = @json($salesChart['labels'] ?? []);
    const chartValues = @json($salesChart['values'] ?? []);

    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Doanh số',
                data: chartValues,
                borderColor: '#0f766e',
                backgroundColor: 'rgba(15, 118, 110, 0.15)',
                tension: 0.35,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
        }
    });

    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
    }

    function renderCommissionFeed(feed) {
        const container = document.getElementById('commission-feed');
        const html = ['<h6 class="mb-2">Chúc mừng nhận hoa hồng</h6>'];

        if (!Array.isArray(feed) || feed.length === 0) {
            html.push('<div class="text-muted small">Chưa có bản ghi hoa hồng.</div>');
            container.innerHTML = html.join('');
            return;
        }

        feed.forEach(item => {
            const code = item.order_code || ('#' + item.order_id);
            const customer = item.customer_name || 'Khách hàng';
            const time = item.confirmed_at ? new Date(item.confirmed_at).toLocaleString('vi-VN') : '';
            html.push(`
                <div class="feed-item">
                    <div class="fw-semibold">${code} - ${customer}</div>
                    <div class="small text-muted">
                        Giá trị đơn: ${formatNumber(item.order_total)} |
                        ${Number(item.commission_percent || 0).toFixed(2)}% |
                        Hoa hồng: <span class="text-success fw-semibold">${formatNumber(item.commission_amount)}</span>
                    </div>
                    <div class="small text-muted">${time}</div>
                </div>
            `);
        });

        container.innerHTML = html.join('');
    }

    function renderTimeline(items) {
        const container = document.getElementById('activity-timeline');
        const html = ['<h6 class="mb-2">Timeline hoạt động task</h6>'];

        if (!Array.isArray(items) || items.length === 0) {
            html.push('<div class="text-muted small">Chưa có hoạt động gần đây.</div>');
            container.innerHTML = html.join('');
            return;
        }

        items.forEach(log => {
            const time = log.created_at ? new Date(log.created_at).toLocaleString('vi-VN') : '';
            html.push(`
                <div class="timeline-item">
                    <div class="dot"></div>
                    <div class="small fw-semibold">${log.task_code || ''} - ${log.task_title || ''}</div>
                    <div class="small text-muted">${log.from_status || ''} → ${log.to_status || ''}</div>
                    <div class="small text-muted">${log.changed_by_name || 'System'} - ${time}</div>
                </div>
            `);
        });

        container.innerHTML = html.join('');
    }

    function refreshDashboard() {
        fetch("{{ route('pages.my_dashboard.stats') }}", {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then((response) => response.json())
        .then((data) => {
            const stats = data.dashboardStats || {};
            Object.keys(stats).forEach((key) => {
                const el = document.querySelector(`[data-stat="${key}"]`);
                if (el) {
                    el.textContent = formatNumber(stats[key]);
                }
            });

            if (data.salesChart) {
                salesChart.data.labels = data.salesChart.labels || [];
                salesChart.data.datasets[0].data = data.salesChart.values || [];
                salesChart.update();
            }

            renderCommissionFeed(data.commissionFeed || []);
            renderTimeline(data.timeline || []);
        })
        .catch(() => {});
    }

    // Handle accept customer buttons
    document.querySelectorAll('.accept-customer-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const customerId = this.dataset.customerId;
            const button = this;
            const originalHTML = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang xử lý...';

            fetch("{{ route('pages.my_dashboard.accept_customer', ['customer' => ':id']) }}".replace(':id', customerId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-success');
                    button.disabled = true;
                    button.innerHTML = '<i class="bi bi-check-circle"></i> Đã nhận';
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show mt-2';
                    alert.innerHTML = `
                        <strong>Thành công!</strong> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('#assigned-customers')?.insertAdjacentElement('beforeend', alert);
                } else {
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                    alert('Lỗi: ' + (data.error || 'Không thể nhận khách hàng'));
                }
            })
            .catch(error => {
                button.disabled = false;
                button.innerHTML = originalHTML;
                console.error('Error:', error);
                alert('Lỗi kết nối: ' + error.message);
            });
        });
    });

    setInterval(refreshDashboard, 30000);
</script>
@endpush
