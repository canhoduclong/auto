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
                <div class="d-flex justify-content-between">
                    <span>Danh sách khách hàng</span>
                    <div>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Thêm mới</button>
                        <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Xóa đã chọn</button>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCustomerModal">Import</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
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
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal-{{ $customer->id }}">Sửa</button>
                                    <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            
            {{ $customers->links() }}
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
@include('site.partials.add_customer_modal')

<!-- Edit Customer Modals -->
@foreach($customers as $customer)
    @include('site.partials.edit_customer_modal', ['customer' => $customer])
@endforeach

<!-- Import Customer Modal -->
@include('site.partials.import_customer_modal')

@endsection

@push('scripts')
<script>
    document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to delete selected customers?')) {
            document.getElementById('bulkDeleteForm').submit();
        }
    });
</script>
@endpush
