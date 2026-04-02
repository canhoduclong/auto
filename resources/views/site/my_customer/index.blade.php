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
        max-width: 1180px;
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
    .mc-filter .form-control,
    .mc-filter .form-select {
        height: 48px;
        border-radius: 14px;
        border-color: #d8deea;
    }
    .mc-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .mc-section-head {
        padding: 22px 24px 0;
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
        display: flex;
        gap: 6px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .mc-actions .btn {
        border-radius: 10px;
        padding: 6px 9px;
        font-size: .8rem;
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

        <div class="mc-panel mb-4">
            <div class="mc-filter">
                <form action="{{ route('pages.my_customer') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-6">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Tìm kiếm</label>
                        <input type="text" name="search" class="form-control" placeholder="Tên, email, số điện thoại..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Hiển thị</label>
                        <select name="per_page" class="form-select">
                            @foreach([10, 25, 50, 100] as $perPageOption)
                                <option value="{{ $perPageOption }}" {{ $currentPerPage === $perPageOption ? 'selected' : '' }}>{{ $perPageOption }} khách hàng</option>
                            @endforeach
                        </select>
                    </div>
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
            </div>
        </div>

        <div class="mc-panel">
            <div class="mc-section-head d-flex flex-wrap gap-2 justify-content-between align-items-center">
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
                <div>
                    {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                </div>
            </div>

            <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="_ids" id="bulkDeleteIds">
            </form>

            @if($customers->total() === 0)
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
            @else
                <div class="mc-table-wrap table-responsive">
                    <table class="table mc-table align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>Tên khách hàng</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Size</th>
                                <th>Sản lượng</th>
                                <th>Giờ giao</th>
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
                                        <div class="mc-name">{{ $customer->name }}</div>
                                        <div class="mc-subtle">Mã KH #{{ $customer->id }}</div>
                                    </td>
                                    <td><span class="mc-subtle">{{ $customer->email ?: '-' }}</span></td>
                                    <td>{{ $customer->phone ?: '-' }}</td>
                                    <td><span class="mc-subtle">{{ $customer->size ?: '-' }}</span></td>
                                    <td><span class="mc-subtle">{{ $customer->production ?: '-' }}</span></td>
                                    <td><span class="mc-subtle">{{ $customer->delivery_time ?: '-' }}</span></td>
                                    <td class="text-center"><span class="mc-orders-pill">{{ $customer->orders_count }}</span></td>
                                    <td><span class="mc-status-badge mc-status-active">Hoạt động</span></td>
                                    <td class="text-center">
                                        <div class="mc-actions">
                                            <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
                                            <a href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments']) }}" class="btn btn-outline-secondary" title="Thanh toán"><i class="bi bi-cash"></i></a>
                                            <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-warning" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mc-mobile-list">
                    <div class="d-grid gap-3">
                        @foreach($customers as $customer)
                            <div class="mc-mobile-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="mc-name">{{ $customer->name }}</div>
                                        <div class="mc-subtle">KH #{{ $customer->id }}</div>
                                    </div>
                                    <input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="form-check-input customer-checkbox">
                                </div>

                                <div class="mc-mobile-meta">
                                    <div>
                                        <span>Email</span>
                                        <div class="mc-subtle">{{ $customer->email ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <span>Điện thoại</span>
                                        <div>{{ $customer->phone ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <span>Giờ giao</span>
                                        <div>{{ $customer->delivery_time ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <span>Số đơn</span>
                                        <div><span class="mc-orders-pill">{{ $customer->orders_count }}</span></div>
                                    </div>
                                </div>

                                <div class="mc-actions justify-content-start">
                                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
                                    <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-warning" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    const hiddenInput = document.getElementById('bulkDeleteIds');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDeleteLabel = document.getElementById('bulkDeleteLabel');

    function updateBulkDeleteButton() {
        const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        bulkDeleteBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
        if (bulkDeleteLabel) {
            bulkDeleteLabel.textContent = selectedCount > 0
                ? `Xóa đã chọn (${selectedCount})`
                : 'Xóa đã chọn';
        }
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
