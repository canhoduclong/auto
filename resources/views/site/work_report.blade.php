        <style>
        .pro-stat-card {
            border-radius: 22px;
            box-shadow: 0 4px 18px rgba(15,23,42,0.07);
            background: linear-gradient(120deg, #f8fafc 70%, #e0e7ef 100%);
            border: none;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .pro-stat-card:hover, .card-stat-link:focus {
            box-shadow: 0 8px 32px rgba(37,99,235,0.13);
            transform: translateY(-2px) scale(1.03);
            background: linear-gradient(120deg, #e0e7ef 60%, #f8fafc 100%);
        }
        .pro-stat-card .stat-icon {
            font-size:2.3rem;
            color:#0f766e;
            margin-bottom: 8px;
        }
        .pro-stat-card .stat-value {
            font-size:1.6rem;
            font-weight:800;
        }
        .pro-stat-card .stat-label {
            color:#64748b;
            font-size:1rem;
        }
        .pro-table th, .pro-table td {
            vertical-align: middle;
        }
        .pro-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .pro-table tbody tr {
            border-radius: 12px;
            background: #fff;
            transition: box-shadow 0.15s;
        }
        .pro-table tbody tr:hover {
            box-shadow: 0 2px 12px rgba(15,23,42,0.07);
        }
        .pro-table img.rounded-circle { border: 2px solid #e0e7ef; }
        @media (max-width: 900px) {
            .pro-stat-card { margin-bottom: 12px; }
        }
        </style>

@php
    use App\Models\Setting;
    $brandName = Setting::get('brand_name', config('app.name'));
    $logoId = Setting::get('logo');
    $logoUrl = null;
    if ($logoId) {
        $media = \App\Models\Media::find($logoId);
        if ($media) {
            $logoUrl = asset('storage/' . $media->file_path);
        }
    }
@endphp

@extends('layouts.site')
 
@section('content')
<div class="py-4"> 
    <div class="  shadow-sm border-0 mb-4">

        @section('content')
        <div class="container py-4">
           
            <div class="mb-4">
                <div class="bg-white rounded-3 shadow-sm p-4 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="fs-5 fw-bold mb-1" style="color:#0f766e;">Báo cáo tổng hợp cá nhân</div>
                        <div class="text-muted">Chỉ thống kê khách hàng, đơn hàng, doanh thu, công nợ của bạn trong từng giai đoạn.</div>
                    </div>
                    <form method="GET" action="{{ route('work-reports.index') }}" class="d-flex flex-wrap align-items-end gap-2" id="workReportFilterForm">
                        <input type="hidden" name="type" id="reportTypeInput" value="{{ $type ?? 'today' }}">
                        <button type="button" class="btn btn-outline-primary filter-btn {{ $type == 'today' ? 'active' : '' }}" onclick="setReportType('today')">Hôm nay</button>
                        <button type="button" class="btn btn-outline-primary filter-btn {{ $type == 'week' ? 'active' : '' }}" onclick="setReportType('week')">Tuần này</button>
                        <button type="button" class="btn btn-outline-primary filter-btn {{ $type == 'month' ? 'active' : '' }}" onclick="setReportType('month')">Tháng này</button>
                        <div class="d-flex align-items-end gap-1" id="rangeFilterGroup" style="display: {{ $type == 'range' ? 'flex' : 'none' }};">
                            <label class="form-label mb-1 me-1">Từ ngày</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', $date) }}">
                            <label class="form-label mb-1 ms-2 me-1">Đến ngày</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', $date) }}">
                        </div>
                        <button type="button" class="btn btn-outline-secondary filter-btn {{ $type == 'range' ? 'active' : '' }}" onclick="setReportType('range')">Từ ngày - Đến ngày</button>
                        <button type="submit" class="btn btn-primary ms-2"><i class="bi bi-funnel"></i> Lọc</button>
                    </form>
                    <style>
                        .filter-btn.active, .filter-btn:focus {
                            background: #0f766e;
                            color: #fff;
                            border-color: #0f766e;
                        }
                    </style>
                    <script>
                    function setReportType(type) {
                        document.getElementById('reportTypeInput').value = type;
                        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                        if(type === 'today') document.querySelector('.filter-btn:nth-child(2)').classList.add('active');
                        if(type === 'week') document.querySelector('.filter-btn:nth-child(3)').classList.add('active');
                        if(type === 'month') document.querySelector('.filter-btn:nth-child(4)').classList.add('active');
                        if(type === 'range') document.querySelector('.filter-btn:nth-child(6)').classList.add('active');
                        document.getElementById('rangeFilterGroup').style.display = (type === 'range') ? 'flex' : 'none';
                    }
                    </script>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                function toggleFilterFields() {
                    var type = document.getElementById('reportType').value;
                    document.querySelectorAll('#workReportFilterForm [data-filter]').forEach(function(el) {
                        el.style.display = 'none';
                    });
                    if(type === 'day') {
                        document.querySelector('#workReportFilterForm [data-filter="day"]').style.display = '';
                    } else if(type === 'range') {
                        document.querySelectorAll('#workReportFilterForm [data-filter="range"]').forEach(function(el){el.style.display = '';});
                    }
                }
                document.getElementById('reportType').addEventListener('change', toggleFilterFields);
                toggleFilterFields();
            });
            </script>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
                        <h5 class="card-title mb-0" style="color:#0f766e;font-weight:700;">Thống kê từ <span class="text-primary">{{ $start->format('d/m/Y') }}</span> đến <span class="text-primary">{{ $end->format('d/m/Y') }}</span></h5>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <a href="#" class="pro-stat-card text-center d-block card-stat-link h-100 py-3 px-2" onclick="toggleStatList('all-customers');return false;">
                                <div class="stat-icon"><i class="bi bi-people"></i></div>
                                <div class="stat-value">{{ $totalCustomerCount ?? 0 }}</div>
                                <div class="stat-label">Tổng khách hàng</div>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#" class="pro-stat-card text-center d-block card-stat-link h-100 py-3 px-2" onclick="toggleStatList('new-customers');return false;">
                                <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
                                <div class="stat-value">{{ $newCustomerCount ?? 0 }}</div>
                                <div class="stat-label">Khách hàng mới</div>
                            </a>
                        </div>
                                            <div id="stat-list-all-customers" style="display:none;">
                                                <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-people"></i> Danh sách tất cả khách hàng</h6>
                                                <div class="table-responsive">
                                                    <table class="table pro-table align-middle table-borderless table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th></th>
                                                                <th>Tên khách hàng</th>
                                                                <th>Email</th>
                                                                <th>SĐT</th>
                                                                <th>Ngày tạo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($allCustomers ?? [] as $customer)
                                                                <tr>
                                                                    <td>
                                                                        @php
                                                                            $avatar = $customer->avatar ?? null;
                                                                            $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                                        @endphp
                                                                        <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                                                    </td>
                                                                    <td class="fw-semibold">{{ $customer->name }}</td>
                                                                    <td>{{ $customer->email }}</td>
                                                                    <td>{{ $customer->phone }}</td>
                                                                    <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '' }}</td>
                                                                </tr>
                                                            @empty
                                                                <tr><td colspan="5" class="text-center text-muted">Không có khách hàng</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div id="stat-list-new-customers" style="display:none;">
                                                <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-person-plus"></i> Danh sách khách hàng mới</h6>
                                                <div class="table-responsive">
                                                    <table class="table pro-table align-middle table-borderless table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th></th>
                                                                <th>Tên khách hàng</th>
                                                                <th>Email</th>
                                                                <th>SĐT</th>
                                                                <th>Ngày tạo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($newCustomers ?? [] as $customer)
                                                                <tr>
                                                                    <td>
                                                                        @php
                                                                            $avatar = $customer->avatar ?? null;
                                                                            $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                                        @endphp
                                                                        <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                                                    </td>
                                                                    <td class="fw-semibold">{{ $customer->name }}</td>
                                                                    <td>{{ $customer->email }}</td>
                                                                    <td>{{ $customer->phone }}</td>
                                                                    <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '' }}</td>
                                                                </tr>
                                                            @empty
                                                                <tr><td colspan="5" class="text-center text-muted">Không có khách hàng mới</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <script>
                                                function toggleStatList(type) {
                                                    document.getElementById('stat-list-all-customers').style.display = (type === 'all-customers') ? 'block' : 'none';
                                                    document.getElementById('stat-list-new-customers').style.display = (type === 'new-customers') ? 'block' : 'none';
                                                }
                                            </script>
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-person-check"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ $interactingCustomerCount ?? 0 }}</div>
                                <div class="text-muted">Đang tương tác</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="#" class="pro-stat-card text-center d-block card-stat-link h-100 py-3 px-2" onclick="toggleStatList('orders');return false;">
                                <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                                <div class="stat-value">{{ $orderCount ?? 0 }}</div>
                                <div class="stat-label">Tổng đơn hàng</div>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-cash-stack"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }} đ</div>
                                <div class="text-muted">Tổng doanh thu</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-cash-coin"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ number_format($totalDebt ?? 0, 0, ',', '.') }} đ</div>
                                <div class="text-muted">Tổng công nợ</div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-orders" style="display:none;">
                        <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-receipt"></i> Danh sách đơn hàng mới</h6>
                        <div class="table-responsive">
                            <table class="table align-middle table-borderless table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Ngày tạo</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Tổng tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders ?? [] as $order)
                                        <tr>
                                            <td class="fw-bold text-primary">#{{ $order->code ?? $order->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @php
                                                        $avatar = $order->customer->avatar ?? null;
                                                        $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($order->customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                    @endphp
                                                    <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                                    <div>
                                                        <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                                        <div class="text-muted small">{{ $order->customer->email ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</td>
                                            <td>
                                                @php
                                                    $status = $order->status ?? '';
                                                    $badgeClass = 'secondary';
                                                    if (str_contains($status, 'thành công') || $status === 'completed') $badgeClass = 'success';
                                                    elseif (str_contains($status, 'hủy') || $status === 'cancelled') $badgeClass = 'danger';
                                                    elseif (str_contains($status, 'chờ') || $status === 'pending') $badgeClass = 'warning';
                                                    elseif (str_contains($status, 'đang') || $status === 'processing') $badgeClass = 'info';
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">{{ number_format($order->total_amount ?? 0, 0, ',', '.') }} đ</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Không có đơn hàng mới</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="tab-customers" style="display:none;">
                        <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-person-plus"></i> Danh sách khách hàng mới</h6>
                        <div class="table-responsive">
                            <table class="table align-middle table-borderless table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Tên khách hàng</th>
                                        <th>Email</th>
                                        <th>SĐT</th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($newCustomers ?? [] as $customer)
                                        <tr>
                                            <td>
                                                @php
                                                    $avatar = $customer->avatar ?? null;
                                                    $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                @endphp
                                                <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                            </td>
                                            <td class="fw-semibold">{{ $customer->name }}</td>
                                            <td>{{ $customer->email }}</td>
                                            <td>{{ $customer->phone }}</td>
                                            <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Không có khách hàng mới</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
        <script>
        // Hide all stat lists and tabs
        function hideAllStatLists() {
            document.getElementById('stat-list-all-customers').style.display = 'none';
            document.getElementById('stat-list-new-customers').style.display = 'none';
            var tabOrders = document.getElementById('tab-orders');
            if (tabOrders) tabOrders.style.display = 'none';
            var tabCustomers = document.getElementById('tab-customers');
            if (tabCustomers) tabCustomers.style.display = 'none';
        }

        // Show the correct list based on type
        function toggleStatList(type) {
            hideAllStatLists();
            if (type === 'all-customers') {
                document.getElementById('stat-list-all-customers').style.display = 'block';
            } else if (type === 'new-customers') {
                document.getElementById('stat-list-new-customers').style.display = 'block';
            } else if (type === 'orders') {
                var tabOrders = document.getElementById('tab-orders');
                if (tabOrders) tabOrders.style.display = 'block';
            } else if (type === 'customers') {
                var tabCustomers = document.getElementById('tab-customers');
                if (tabCustomers) tabCustomers.style.display = 'block';
            }
        }
        </script>
        @endpush
        @endsection
