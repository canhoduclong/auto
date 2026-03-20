@extends('layouts.site')

@section('content')
<style>
    .mc-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
    }
    .mc-hero h1 { font-size: 2.5rem; font-weight: 700; margin: 0; }
    .mc-hero p { font-size: 1.1rem; margin: 0.5rem 0 0; opacity: 0.95; }
    
    .mc-kpi-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
        margin-bottom: 1rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .mc-kpi-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.12); }
    .mc-kpi-value { font-size: 2rem; font-weight: 700; color: #667eea; }
    .mc-kpi-label { font-size: 0.875rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.5rem; }
    
    .mc-filter-panel {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .mc-table-wrapper {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .mc-table-wrapper table { margin: 0; }
    .mc-table-wrapper thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .mc-table-wrapper th {
        padding: 1.25rem 0.75rem !important;
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .mc-table-wrapper tbody tr {
        border-bottom: 1px solid #dee2e6;
        transition: background-color 0.2s;
    }
    .mc-table-wrapper tbody tr:hover { background-color: #f8f9fa; }
    .mc-table-wrapper td {
        padding: 1rem 0.75rem !important;
        vertical-align: middle;
    }
    
    .mc-cards-container {
        display: none;
    }
    
    .mc-customer-card {
        background: white;
        border-radius: 8px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        border-left: 4px solid #667eea;
    }
    .mc-customer-card-name { font-weight: 700; font-size: 1.1rem; color: #212529; margin-bottom: 0.5rem; }
    .mc-customer-card-detail {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    .mc-customer-card-detail i { margin-right: 0.5rem; color: #667eea; }
    .mc-customer-card-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .mc-customer-card-actions .btn { font-size: 0.75rem; padding: 0.375rem 0.75rem; }
    
    .mc-btn-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .mc-btn-group .btn { font-size: 0.75rem; padding: 0.4rem 0.75rem; }
    
    .mc-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6c757d;
    }
    .mc-empty-state-icon {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 767.98px) {
        .mc-table-wrapper { display: none; }
        .mc-cards-container { display: block; }
        .mc-hero h1 { font-size: 1.75rem; }
        .mc-hero { padding: 2rem 0; }
        .mc-filter-panel { padding: 1rem; }
    }
    
    .mc-status-badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .mc-status-active { background-color: #d4edda; color: #155724; }
</style>

<div class="mc-hero">
    <div class="container">
        <h1>Danh sách khách hàng</h1>
        <p>Quản lý và theo dõi tất cả khách hàng của bạn</p>
    </div>
</div>

@if(session('success'))
    <div class="container">
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="container">
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

<div class="container">
    <!-- KPI Cards -->
    <div class="row mb-4 g-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="mc-kpi-card">
                <div class="mc-kpi-value">{{ $customers->total() }}</div>
                <div class="mc-kpi-label">Tổng khách hàng</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="mc-kpi-card">
                <div class="mc-kpi-value">{{ $customers->count() }}</div>
                <div class="mc-kpi-label">Trên trang này</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="mc-kpi-card">
                <div class="mc-kpi-value">{{ $customers->sum('orders_count') }}</div>
                <div class="mc-kpi-label">Tổng đơn hàng</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="mc-kpi-card">
                <div class="mc-kpi-value">
                    @php
                        $activeCount = $customers->count();
                    @endphp
                    {{ $activeCount }}
                </div>
                <div class="mc-kpi-label">Khách hàng hoạt động</div>
            </div>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="mc-filter-panel">
        <form action="{{ route('pages.my_customer') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label fw-600 mb-2">Tìm kiếm khách hàng</label>
                <input type="text" name="search" class="form-control" placeholder="Nhập tên hoặc email..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-600 mb-2">Hiển thị</label>
                <select name="per_page" class="form-select">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 khách hàng</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 khách hàng</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 khách hàng</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 khách hàng</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search"></i> Lọc
                </button>
                @if($search)
                    <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Xóa
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Actions Bar -->
    <div class="mb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="mc-btn-group">
            <a href="{{ route('my_customer.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Thêm mới
            </a>
            <a href="{{ route('my_customer.import_form') }}" class="btn btn-info">
                <i class="bi bi-upload"></i> Nhập danh sách
            </a>
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                <i class="bi bi-trash"></i> Xóa đã chọn
            </button>
        </div>
        <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST" class="d-none">
            @csrf
            <input type="hidden" name="_ids" id="bulkDeleteIds">
        </form>
        <div>
            {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
        </div>
    </div>

    @if($customers->total() == 0)
        <div class="mc-table-wrapper">
            <div class="mc-empty-state">
                <div class="mc-empty-state-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <h5>Chưa có khách hàng</h5>
                <p class="mb-3">Hãy bắt đầu bằng cách thêm khách hàng mới hoặc nhập danh sách khách hàng.</p>
                <div class="mc-btn-group justify-content-center">
                    <a href="{{ route('my_customer.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Thêm khách hàng đầu tiên
                    </a>
                    <a href="{{ route('my_customer.import_form') }}" class="btn btn-info">
                        <i class="bi bi-upload"></i> Nhập danh sách
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Desktop Table View -->
        <div class="mc-table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                        <th>Tên khách hàng</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Giờ giao hàng</th>
                        <th class="text-center">Số đơn</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="form-check-input customer-checkbox">
                            </td>
                            <td>
                                <strong>{{ $customer->name }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">{{ $customer->email }}</small>
                            </td>
                            <td>
                                {{ $customer->phone ?: '-' }}
                            </td>
                            <td>
                                <span class="text-muted">{{ $customer->delivery_time ?: '-' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $customer->orders_count }}</span>
                            </td>
                            <td>
                                <span class="mc-status-badge mc-status-active">Hoạt động</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($customer->orders_count > 0)
                                        <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-primary" title="Xem đơn hàng">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success" title="Lên đơn hàng">
                                        <i class="bi bi-file-text"></i>
                                    </a>
                                    <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-warning" title="Chỉnh sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mc-cards-container">
            @foreach($customers as $customer)
                <div class="mc-customer-card">
                    <div class="mc-customer-card-name">{{ $customer->name }}</div>
                    <div class="mc-customer-card-detail">
                        <i class="bi bi-envelope-fill"></i> {{ $customer->email }}
                    </div>
                    @if($customer->phone)
                        <div class="mc-customer-card-detail">
                            <i class="bi bi-telephone-fill"></i> {{ $customer->phone }}
                        </div>
                    @endif
                    <div class="mc-customer-card-detail">
                        <i class="bi bi-clock-fill"></i> Giờ giao: {{ $customer->delivery_time ?: '-' }}
                    </div>
                    <div class="mc-customer-card-detail">
                        <i class="bi bi-file-text"></i> <strong>{{ $customer->orders_count }}</strong> đơn hàng
                    </div>
                    <div class="mc-customer-card-actions">
                        @if($customer->orders_count > 0)
                            <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-eye"></i> Xem đơn
                            </a>
                        @endif
                        <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-sm btn-outline-success flex-fill">
                            <i class="bi bi-file-text"></i> Lên đơn
                        </a>
                        <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-sm btn-outline-warning flex-fill">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="flex-fill" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination at bottom -->
        <div class="d-flex justify-content-center mt-4">
            {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    const hiddenInput = document.getElementById('bulkDeleteIds');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    function updateBulkDeleteButton() {
        const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        bulkDeleteBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
    }

    function updateHiddenInput() {
        const selectedIds = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        hiddenInput.value = selectedIds.join(',');
        updateBulkDeleteButton();
    }

    selectAllCheckbox?.addEventListener('change', function (e) {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateHiddenInput();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateHiddenInput);
    });

    bulkDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        updateHiddenInput();
        if (hiddenInput.value === '') {
            alert('Vui lòng chọn ít nhất một khách hàng để xóa.');
            return;
        }
        if (confirm('Bạn có chắc chắn muốn xóa các khách hàng đã chọn không?')) {
            document.getElementById('bulkDeleteForm').submit();
        }
    });
</script>
@endpush
