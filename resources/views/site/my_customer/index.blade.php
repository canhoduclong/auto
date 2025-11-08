@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Khách hàng của bạn</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    
        <div class="card">
            <div class="card-header">
                <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_ids" id="bulkDeleteIds">
                    <div class="d-flex justify-content-between">
                        <span>Danh sách khách hàng</span>
                        <div>
                            <a href="{{ route('my_customer.create') }}" class="btn btn-primary btn-sm">Thêm mới</a>
                            <a href="{{ route('my_customer.import_form') }}" class="btn btn-info btn-sm">Import</a>
                            <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Xóa đã chọn</button>
                        </div>
                    </div>
                </form>
            </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form action="{{ route('pages.my_customer') }}" method="GET" class="form-inline d-flex justify-content-between  align-items-center">
                    <div class="input-group mr-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm kiếm..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="input-group mr-2">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="per_page">Hiển thị</label>
                        </div>
                        <select name="per_page" id="per_page" class="form-control form-control-sm">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
                </form>
                <div>
                    {{ $customers->appends(request()->except('page'))->links() }}
                </div>
            </div>

            <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST">
                @csrf
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
                            <th>Đơn hàng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="customer-checkbox"></td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>
                                    @if($customer->orders_count > 0)
                                        <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-info btn-sm">Xem đơn hàng</a>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-primary btn-sm">Xem</a>
                                    <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-success btn-sm">Lên đơn</a>
                                    <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-warning btn-sm">Sửa</a>
                                    <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </form>             
        </div>
        </div> 
</div>

@endsection

@push('scripts')
<script>
 const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    const hiddenInput = document.getElementById('bulkDeleteIds');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    // Cập nhật giá trị hidden input mỗi khi checkbox thay đổi
    function updateHiddenInput() {
        const selectedIds = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        hiddenInput.value = selectedIds.join(',');
    }

    // Khi tick "Chọn tất cả"
    selectAllCheckbox.addEventListener('change', function (e) {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateHiddenInput();
    });

    // Khi tick / bỏ tick từng checkbox riêng lẻ
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateHiddenInput);
    });

    // Khi click nút xóa
    bulkDeleteBtn.addEventListener('click', function (e) {
        e.preventDefault();
        updateHiddenInput(); // đảm bảo hiddenInput được cập nhật mới nhất
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
