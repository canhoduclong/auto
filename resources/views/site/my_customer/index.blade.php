@extends('layouts.site')

@section('content')

<div class="py-4">
    <div class="container py-4">
        <div class="bg-white rounded-3 shadow-sm p-4 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="fs-5 fw-bold mb-1" style="color:#0f766e;">Danh sách khách hàng</div>
                <div class="text-muted">Quản lý và theo dõi tất cả khách hàng của bạn</div>
            </div>
            <form action="{{ route('pages.my_customer') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1">Tìm kiếm</label>
                    <input type="text" name="search" class="form-control" placeholder="Nhập tên hoặc email..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1">Hiển thị</label>
                    <select name="per_page" class="form-select">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 khách hàng</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 khách hàng</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 khách hàng</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 khách hàng</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
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

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border h-100">
                    <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold" style="font-size:1.5rem;">{{ $customers->total() }}</div>
                    <div class="text-muted">Tổng khách hàng</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border h-100">
                    <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-person-check"></i></div>
                    <div class="fw-bold" style="font-size:1.5rem;">{{ $customers->count() }}</div>
                    <div class="text-muted">Trên trang này</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border h-100">
                    <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-receipt"></i></div>
                    <div class="fw-bold" style="font-size:1.5rem;">{{ $customers->sum('orders_count') }}</div>
                    <div class="text-muted">Tổng đơn hàng</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border h-100">
                    <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-person-badge"></i></div>
                    <div class="fw-bold" style="font-size:1.5rem;">{{ $customers->count() }}</div>
                    <div class="text-muted">Khách hàng hoạt động</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
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
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                    <th>Tên khách hàng</th>
                                    <th>Email</th>
                                    <th>Điện thoại</th>
                                    <th>Size</th>
                                    <th>Sản lượng</th>
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
                                            <span class="text-muted">{{ $customer->size ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $customer->production ?: '-' }}</span>
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
                                                <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info" title="Xem chi tiết">
                                                    <i class="bi bi-eye"></i> Xem
                                                </a>
                                                @if($customer->orders_count > 0)
                                                    <a href="{{ route('my_customer.show', $customer) }}#orders" class="btn btn-outline-primary" title="Xem đơn hàng">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success" title="Lên đơn hàng">
                                                    <i class="bi bi-file-text"></i>
                                                </a>
                                                <a href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments']) }}" class="btn btn-outline-secondary" title="Thanh toán">
                                                    <i class="bi bi-cash"></i>
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
                @endif
            </div>
        </div>
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
