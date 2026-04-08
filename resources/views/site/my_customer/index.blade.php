@extends('layouts.site')

@push('styles')
<style>
    .mc-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .mc-shell { 
        margin: 0 auto;
    }
    .mc-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        position: relative;
        overflow: hidden;
    }
    .mc-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .mc-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 16px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }
    .mc-kpi-label {
        font-size: .75rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .68);
        margin-bottom: 6px;
    }
    .mc-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }
    .mc-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .mc-filter {
        padding: 22px;
    }
    .mc-customer-card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s ease;
    }
    .mc-customer-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .mc-filter .form-select {
        height: 40px;
        border-radius: 8px;
        border-color: #d8deea;
    }
    .mc-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .mc-section-head {
        padding: 15px 24px;
    }
    .mc-action-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .mc-action-group .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 9px 14px;
    }
    .mc-table-wrap {
        padding: 0 18px 18px;
    }
    .mc-table {
        min-width: 1060px;
        margin-bottom: 0;
    }
    .mc-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        white-space: nowrap;
        padding: 15px 12px;
    }
    .mc-table tbody td {
        padding: 16px 12px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .mc-name {
        font-weight: 800;
        color: #1e293b;
    }
    .mc-subtle {
        font-size: .82rem;
        color: #64748b;
    }
    .mc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
    }
    .mc-status-active {
        background: #ecfdf5;
        color: #047857;
    }
    .mc-orders-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: .8rem;
    }
    .mc-actions {
           
    }
    .mc-actions .btn {
        border-radius: 10px;
        padding: 6px 9px;
        font-size: .8rem;
    }
    .mc-alert-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.05);
    }
    .mc-alert-title {
        font-size: .95rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #0f172a;
    }
    .mc-alert-item {
        border-radius: 18px;
        padding: 14px 16px;
        background: #f8fafc;
        margin-bottom: 12px;
    }
    .mc-alert-item:last-child {
        margin-bottom: 0;
    }
    .mc-alert-label {
        color: #475569;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
        display: block;
    }
    .mc-alert-value {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .mc-alert-meta {
        color: #64748b;
        font-size: .82rem;
    }
    .mc-area-panel {
        padding: 22px;
    }
    .mc-area-title {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #0f172a;
    }
    .area-city {
        margin-bottom: 20px;
    }
    .area-city-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .area-ward-group {
        margin-left: 12px;
        margin-bottom: 10px;
    }
    .area-ward-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .area-streets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .area-ward {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.78rem;
        text-decoration: none;
        transition: background-color .2s ease, color .2s ease;
    }
    .area-ward:hover,
    .area-ward.active {
        background: #1d4ed8;
        color: #ffffff;
    }
    .area-ward-title a {
        color: inherit;
        text-decoration: none;
    }
    .area-ward-title a:hover {
        text-decoration: underline;
    }
    .area-filter-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: #475569;
    }
    .mc-empty {
        padding: 42px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    .mc-mobile-list {
        display: none;
        padding: 0 16px 16px;
    }
    .mc-mobile-card {
        border: 1px solid #e7ecf3;
        border-radius: 22px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }
    .mc-mobile-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 12px 0;
    }
    .mc-mobile-meta span {
        display: block;
        font-size: .74rem;
        color: #64748b;
        margin-bottom: 3px;
    }
    @media (max-width: 991.98px) {
        .mc-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .mc-filter {
            padding: 20px;
        }
    }
    @media (max-width: 767.98px) {
        .mc-page {
            padding: 20px 0 48px;
        }
        .mc-shell {
            padding: 0 12px;
        }
        .mc-hero {
            padding: 18px;
        }
        .mc-kpi-value {
            font-size: 1.35rem;
        }
        .mc-table-wrap {
            display: none;
        }
        .mc-mobile-list {
            display: block;
        }
        .mc-mobile-meta {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $currentPerPage = (int) request('per_page', 10);
    $pageCustomers = $customers->getCollection();
    $pageOrdersCount = (int) $pageCustomers->sum('orders_count');
    $withPhoneCount = (int) $pageCustomers->filter(fn($customer) => !empty($customer->phone))->count();
@endphp

<section class="mc-page">
    <div class="container mc-shell">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mc-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Customer Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Khách hàng của bạn</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.82);max-width:520px;">
                        Quản lý tệp khách hàng, truy cập nhanh lịch sử đơn và thực hiện thao tác bán hàng chỉ trong một màn hình.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Tổng khách</div>
                                <div class="mc-kpi-value">{{ number_format($customers->total()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Trên trang</div>
                                <div class="mc-kpi-value">{{ number_format($customers->count()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Đơn hàng</div>
                                <div class="mc-kpi-value">{{ number_format($pageOrdersCount) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Có SĐT</div>
                                <div class="mc-kpi-value">{{ number_format($withPhoneCount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="mc-alert-panel">
                    <div class="mc-alert-title">Chú ý quan trọng</div>
                    <div class="mc-alert-item">
                        <span class="mc-alert-label">Lịch hẹn sắp tới</span>
                        @if($upcomingReminders->isNotEmpty())
                            @foreach($upcomingReminders as $reminder)
                                <div class="mc-alert-value">{{ optional($reminder->customer)->name ?? 'Khách hàng chưa xác định' }}</div>
                                <div class="mc-alert-meta">{{ $reminder->remind_at?->format('d/m/Y H:i') }} - {{ \Illuminate\Support\Str::limit($reminder->title ?? $reminder->note ?? 'Không có nội dung', 80) }}</div>
                                @if(!$loop->last)
                                    <hr class="my-2" style="border-color: rgba(148, 163, 184, .22);">
                                @endif
                            @endforeach
                        @else
                            <div class="mc-alert-value">Không có lịch hẹn mới.</div>
                            <div class="mc-alert-meta">Tạo lịch nhắc hoặc cập nhật tình trạng khách hàng để theo dõi.</div>
                        @endif
                    </div>
                    <div class="mc-alert-item">
                        <span class="mc-alert-label">Ghi chú mới nhất</span>
                        @if($latestCareLog)
                            <div class="mc-alert-value">{{ optional($latestCareLog->customer)->name ?? 'Khách hàng chưa xác định' }}</div>
                            <div class="mc-alert-meta">{{ $latestCareLog->created_at?->format('d/m/Y H:i') }} - {{ \Illuminate\Support\Str::limit($latestCareLog->note, 100) }}</div>
                        @else
                            <div class="mc-alert-value">Chưa có ghi chú chăm sóc.</div>
                            <div class="mc-alert-meta">Hãy thêm ghi chú mới khi tương tác với khách.</div>
                        @endif
                    </div>
                </div>
                <div class="mc-panel mb-4">
                    <div class="mc-area-panel">
                        <div class="mc-area-title">Khu vực</div>

                        @if(request('city') || request('ward') || request('street'))
                            <div class="area-filter-summary mb-3">
                                <span class="fw-semibold">Đang lọc:</span>
                                @if(request('city'))
                                    <span>{{ request('city') }}</span>
                                @else
                                    <span>Tất cả tỉnh/thành</span>
                                @endif
                                @if(request('ward'))
                                    <span>» {{ request('ward') }}</span>
                                @endif
                                @if(request('street'))
                                    <span>» {{ request('street') }}</span>
                                @endif
                                <span class="ms-2 text-secondary">Khách hàng: {{ number_format($selectedAreaCustomerCount) }}</span>
                                <a href="{{ route('pages.my_customer', request()->except(['page', 'city', 'ward', 'street'])) }}" class="ms-2 small text-decoration-none">Xóa bộ lọc</a>
                            </div>
                        @endif

                        @if(!empty($locationTree) && $locationTree->isNotEmpty())
                            @foreach($locationTree as $city => $cityData)
                                <div class="area-city">
                                    <div class="area-city-title">
                                        <a href="{{ route('pages.my_customer', array_filter(array_merge(request()->except(['page', 'city', 'ward', 'street']), ['city' => $city]), function ($value) { return $value !== null && $value !== ''; })) }}" class="text-reset text-decoration-none">
                                            {{ $city }} <span class="text-muted">({{ number_format($cityData['customer_count']) }})</span>
                                        </a>
                                    </div>
                                    @foreach($cityData['wards'] as $ward => $wardData)
                                        <div class="area-ward-group">
                                            <div class="area-ward-title">
                                                <a href="{{ route('pages.my_customer', array_filter(array_merge(request()->except(['page', 'city', 'ward', 'street']), ['city' => $city, 'ward' => $ward ?: null]), function ($value) { return $value !== null && $value !== ''; })) }}" class="text-reset text-decoration-none">
                                                    {{ $ward ?: 'Chưa rõ phường/xã' }} <span class="text-muted">({{ number_format($wardData['customer_count']) }})</span>
                                                </a>
                                            </div>
                                            <div class="area-streets">
                                                @forelse($wardData['streets'] as $streetData)
                                                    <a href="{{ route('pages.my_customer', array_filter(array_merge(request()->except(['page', 'city', 'ward', 'street']), ['city' => $city, 'ward' => $ward ?: null, 'street' => $streetData['street']]), function ($value) { return $value !== null && $value !== ''; })) }}" class="area-ward{{ request('street') === $streetData['street'] ? ' active' : '' }}">
                                                        {{ $streetData['street'] ?: 'Chưa rõ đường' }} <span class="text-muted">({{ number_format($streetData['customer_count']) }})</span>
                                                    </a>
                                                @empty
                                                    <span class="area-ward">Chưa rõ đường</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <div class="text-muted">Chưa có dữ liệu khu vực khách hàng.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="mc-panel mb-4">
                    <div class="mc-filter">
                        <form action="{{ route('pages.my_customer') }}" method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-lg-6">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Tìm kiếm</label>
                                <input type="text" name="search" class="form-control" placeholder="Tên, email, số điện thoại..." value="{{ $search ?? '' }}">
                            </div>
                            <input type="hidden" name="city" value="{{ request('city') }}">
                            <input type="hidden" name="ward" value="{{ request('ward') }}">
                            <input type="hidden" name="street" value="{{ request('street') }}">
                            <div class="col-lg-3 col-md-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search"></i> Lọc
                                </button>
                                @if(!empty($search))
                                    <a href="{{ route('pages.my_customer') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Sắp xếp theo</label>
                                <select id="sortBy" class="form-select">
                                    <option value="">Mặc định</option>
                                    <option value="production" {{ request('sort_by') === 'production' ? 'selected' : '' }}>Sản lượng</option>
                                    <option value="size" {{ request('sort_by') === 'size' ? 'selected' : '' }}>Size</option>
                                    <option value="delivery_time" {{ request('sort_by') === 'delivery_time' ? 'selected' : '' }}>Giờ giao</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Thứ tự</label>
                                <select id="sortDir" class="form-select">
                                    <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>Tăng dần</option>
                                    <option value="desc" {{ request('sort_dir') !== 'asc' ? 'selected' : '' }}>Giảm dần</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Hiển thị</label>
                                <select id="perPage" class="form-select">
                                    <option value="10" {{ $currentPerPage === 10 ? 'selected' : '' }}>10 khách hàng</option>
                                    <option value="25" {{ $currentPerPage === 25 ? 'selected' : '' }}>25 khách hàng</option>
                                    <option value="50" {{ $currentPerPage === 50 ? 'selected' : '' }}>50 khách hàng</option>
                                    <option value="100" {{ $currentPerPage === 100 ? 'selected' : '' }}>100 khách hàng</option>
                                </select>
                            </div>
                            
                        </div>
                    </div>
                </div>

        <div class="mc-panel">
            <div class="mc-section-head d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Danh sách khách hàng</h4>
                    <div id="pagination-info" class="text-muted small">
                        Hiển thị {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} của {{ $customers->total() }} khách hàng
                    </div>
                </div>
                <div class="mc-action-group">
                    <a href="{{ route('my_customer.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Thêm mới
                    </a>
                    <a href="{{ route('my_customer.import_form') }}" class="btn btn-info text-white">
                        <i class="bi bi-upload"></i> Nhập danh sách
                    </a>
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                        <i class="bi bi-trash"></i> <span id="bulkDeleteLabel">Xóa đã chọn</span>
                    </button>
                </div>
            </div>
        </div>
 
 <div class="mt-3">
            <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="_ids" id="bulkDeleteIds">
            </form>

            <div id="customer-list" class="row g-3">
                @foreach($customers as $customer)
                    @php
                        $addressText = $customer->address ?: '';
                        if (!$addressText && $customer->addresses->first()) {
                            $address = $customer->addresses->first();
                            $parts = array_filter([$address->house_number, $address->street, $address->ward, $address->city]);
                            $addressText = implode(', ', $parts);
                        }
                    @endphp
                    <div class="col-12">
                        <div class="mc-customer-card border rounded p-3 bg-white">
                            <div class="row justify-content-between">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start gap-3">
                                        
                                        <div>
                                            <h6 class="mb-1 fw-bold fs-5">{{ $customer->name }}</h6>
                                            @if($customer->updated_at)
                                                <small class="text-muted fst-italic"><i class="bi bi-clock me-1"></i>Cập nhật: {{ $customer->updated_at->format('d/m/Y') }}</small><br>
                                            @endif
                                            @if($customer->type)
                                                <small class="text-muted">Phân loại: {{ $customer->type->name }}</small><br>
                                            @endif
                                            @if($customer->brand)
                                                <small class="text-muted">Brand: {{ $customer->brand }}</small><br>
                                            @endif
                                            
                                            @if($customer->phone)
                                                <small class="fw-bold fs-6"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</small><br>
                                            @endif
                                            @if($addressText)
                                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $addressText }}</small><br>
                                            @endif
                                            @if($customer->email)
                                                <small class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 d-flex flex-column justify-content-between">
                                    <div class="row">
                                        <div class="col-12  text-end">
                                            @if($customer->production)
                                                 
                                                    <small class="text-muted">Sản lượng: <strong>{{ $customer->production }}</strong></small>
                                                
                                            @endif
                                            @if($customer->size)
                                                
                                                    <small class="text-muted">Size: <strong>{{ $customer->size }}</strong></small>
                                                
                                            @endif
                                            <small class="text-muted">Mã KH: <strong>{{ $customer->customer_code ?: '#'.$customer->id }}</strong></small>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @if($customer->delivery_time)
                                            <div class="col-12  text-end">
                                                <small class="text-muted">Giờ giao: {{ $customer->delivery_time }}</small>
                                            </div>
                                        @endif
                                       <div class="text-end">
                                            @if($customer->total_debt)
                                            <div class="text-muted">Công nợ: <strong>{{ number_format($customer->total_debt ?? 0, 0, ',', '.') }} đ</strong></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-end align-items-center">
                                             <div class="text-end me-3">
                                                <small class="text-muted">Đơn: {{ $customer->orders_count }}</small>
                                            </div>
                                            <div class="text-end me-3 mt-1">
                                             <input type="checkbox" name="ids[]" value="{{ $customer->id }}" class=" customer-checkbox">
                                            </div>
                                             <div class="mc-actions justify-content-end gap-2">
                                                <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info btn-sm" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                                <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success btn-sm" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
                                                <a href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments']) }}" class="btn btn-outline-secondary btn-sm" title="Thanh toán"><i class="bi bi-cash"></i></a>
                                                <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-warning btn-sm" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                                <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                           
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 d-flex justify-content-center">
                <div id="pagination-links">
                    {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
    
</section>

@endsection

@push('scripts')
<script>
    let currentParams = {
        search: '{{ $search ?? '' }}',
        city: '{{ request('city') }}',
        ward: '{{ request('ward') }}',
        street: '{{ request('street') }}',
        sort_by: '{{ request('sort_by') }}',
        sort_dir: '{{ request('sort_dir') }}',
        per_page: {{ $currentPerPage }},
        page: 1
    };

    function loadCustomers(params = {}) {
        Object.assign(currentParams, params);
        const queryString = new URLSearchParams(currentParams).toString();

        fetch('{{ route('pages.my_customer.ajax') }}?' + queryString)
            .then(response => response.json())
            .then(data => {
                updateCustomerList(data.customers);
                updatePagination(data.pagination);
                updatePaginationInfo(data.pagination);
            })
            .catch(error => console.error('Error loading customers:', error));
    }

    function updateCustomerList(customers) {
        const container = document.getElementById('customer-list');
        if (customers.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="mc-empty">
                        <i class="bi bi-inbox" style="font-size:2.6rem;"></i>
                        <h5 class="mt-3 mb-2">Chưa có khách hàng</h5>
                        <p class="mb-3">Hãy thêm mới hoặc nhập danh sách để bắt đầu quản lý tệp khách hàng.</p>
                        <div class="mc-action-group justify-content-center">
                            <a href="{{ route('my_customer.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Thêm khách hàng đầu tiên
                            </a>
                            <a href="{{ route('my_customer.import_form') }}" class="btn btn-info text-white">
                                <i class="bi bi-upload"></i> Nhập danh sách
                            </a>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = customers.map(customer => `
            <div class="col-12">
                <div class="mc-customer-card border rounded p-3 bg-white">
                    <div class="row justify-content-between">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <input type="checkbox" name="ids[]" value="${customer.id}" class="form-check-input customer-checkbox mt-1">
                                <div>
                                    <h6 class="mb-1 fw-bold fs-5">${customer.name}</h6>
                                    ${customer.updated_at_formatted ? `<small class="text-muted fst-italic"><i class="bi bi-clock me-1"></i>Cập nhật: ${customer.updated_at_formatted}</small><br>` : ''}
                                    ${customer.type ? `<small class="text-muted">Phân loại: ${customer.type.name}</small><br>` : ''}
                                    ${customer.brand ? `<small class="text-muted">Brand: ${customer.brand}</small><br>` : ''}
                                    <small class="text-muted">Mã KH: ${customer.customer_code || '#' + customer.id}</small><br>
                                    ${customer.phone ? `<small class="fw-bold fs-6"><i class="bi bi-telephone me-1"></i>${customer.phone}</small><br>` : ''}
                                    ${customer.address_text ? `<small class="text-muted"><i class="bi bi-geo-alt me-1"></i>${customer.address_text}</small><br>` : ''}
                                    ${customer.email ? `<small class="text-muted"><i class="bi bi-envelope me-1"></i>${customer.email}</small>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="row g-2">
                                ${customer.production ? `<div class="col-6"><small class="text-muted">Sản lượng: ${customer.production}</small></div>` : ''}
                                ${customer.size ? `<div class="col-6"><small class="text-muted">Size: ${customer.size}</small></div>` : ''}
                                ${customer.delivery_time ? `<div class="col-6"><small class="text-muted">Giờ giao: ${customer.delivery_time}</small></div>` : ''}
                                <div class="col-6"><small class="text-muted">Đơn: ${customer.orders_count}</small></div>
                            </div>
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted me-3">Công nợ: <strong>${Number(customer.total_debt || 0).toLocaleString('vi-VN')} đ</strong></div>
                                    <input type="checkbox" name="ids[]" value="${customer.id}" class="form-check-input customer-checkbox">
                                    <div class="mc-actions justify-content-end">
                                        <a href="{{ route('my_customer.show', ':id') }}".replace(':id', customer.id) class="btn btn-outline-info btn-sm" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('my_customer.order.create', ':id') }}".replace(':id', customer.id) class="btn btn-outline-success btn-sm" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
                                        <a href="{{ route('my_customer.show', ['customer' => ':id', 'tab' => 'payments']) }}".replace(':id', customer.id) class="btn btn-outline-secondary btn-sm" title="Thanh toán"><i class="bi bi-cash"></i></a>
                                        <a href="{{ route('my_customer.edit', ':id') }}".replace(':id', customer.id) class="btn btn-outline-warning btn-sm" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('my_customer.destroy', ':id') }}".replace(':id', customer.id) method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        // Re-attach event listeners for checkboxes
        attachCheckboxEvents();
    }

    function updatePagination(pagination) {
        const container = document.getElementById('pagination-links');
        container.innerHTML = pagination.links;
        // Attach click events to pagination links
        container.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page');
                loadCustomers({ page: page });
            });
        });
    }

    function updatePaginationInfo(pagination) {
        const info = document.getElementById('pagination-info');
        info.textContent = `Hiển thị ${pagination.from || 0} - ${pagination.to || 0} của ${pagination.total} khách hàng`;
    }

    function attachCheckboxEvents() {
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteLabel = document.getElementById('bulkDeleteLabel');
        const hiddenInput = document.getElementById('bulkDeleteIds');

        function updateBulkDeleteButton() {
            const selectedIds = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            hiddenInput.value = selectedIds.join(',');
            const selectedCount = selectedIds.length;
            bulkDeleteBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
            if (bulkDeleteLabel) {
                bulkDeleteLabel.textContent = selectedCount > 0
                    ? `Xóa đã chọn (${selectedCount})`
                    : 'Xóa đã chọn';
            }
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteButton);
        });

        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (hiddenInput.value === '') {
                    alert('Vui lòng chọn ít nhất một khách hàng để xóa.');
                    return;
                }
                if (confirm('Bạn có chắc chắn muốn xóa các khách hàng đã chọn không?')) {
                    document.getElementById('bulkDeleteForm').submit();
                }
            });
        }
    }

    // Event listeners
    document.getElementById('sortBy').addEventListener('change', function() {
        loadCustomers({ sort_by: this.value, page: 1 });
    });

    document.getElementById('sortDir').addEventListener('change', function() {
        loadCustomers({ sort_dir: this.value, page: 1 });
    });

    document.getElementById('perPage').addEventListener('change', function() {
        loadCustomers({ per_page: this.value, page: 1 });
    });

    // Initial attach for existing checkboxes
    attachCheckboxEvents();
</script>
@endpush
