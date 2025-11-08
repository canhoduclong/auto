@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Danh sách khách hàng</h2>
    <div class="d-flex gap-2 mb-3 align-items-end">
        <form id="bulkDeleteForm" action="{{ route('customers.bulkDelete') }}" method="POST" class="d-inline-block ms-2">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận xóa các khách hàng đã chọn?')">Xóa đã chọn</button>
        </form>
        <div class="d-inline-block">
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="d-inline-block">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" style="display:inline-block;width:auto" required>
                <button class="btn btn-warning">Import Excel</button>
            </form>
            <a href="{{ route('customers.export') }}" class="btn btn-info">Export Excel</a>
            <a href="{{ route('customers.create') }}" class="btn btn-success">+ Thêm khách hàng</a>
        </div>
    </div> 
    <div class="mb-3 d-flex justify-content-between align-items-end">
        <form class="row g-2" method="GET" action="{{ route('customers.index') }}">
            <div class="col-auto">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên / SĐT / Email">
            </div>
            <div class="col-auto">
                <select name="type_id" class="form-select">
                    <option value="">-- Tất cả loại --</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" {{ (string)$t->id === request('type_id') ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($users)
            <div class="col-auto">
                <select name="assigned_to" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả nhân viên --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string)$user->id === request('assigned_to') ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10,15,25,50,100] as $pp)
                        <option value="{{ $pp }}" {{ request('per_page',15)==$pp?'selected':'' }}>{{ $pp }} / trang</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">Lọc</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

       
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>#</th>
                <th>Họ & Tên</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Loại</th> 
                <th>Nhân viên</th>
                <th>Địa chỉ mặc định</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><input type="checkbox" class="row-check" value="{{ $customer->id }}"></td>
                    <td>{{ $customer->id }}</td>
                    <td>
                        {{ $customer->name }} <br>
                         @if($customer->dob)
                            <small class="text-muted">
                                {{ $customer->dob->format('d/m/Y') }} - 
                                {{ $customer->dob->age }} tuổi
                            </small>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ optional($customer->type)->name ?? '-' }}</td>
                    <td>{{ optional($customer->assignedTo)->name ?? '-' }}</td>
                     
                    <td>
                         @if($customer->addresses->isNotEmpty()) 
                            @php
                                $default = $customer->addresses->firstWhere('is_default', 1);
                            @endphp
                            @if($default)
                                {{ $default->note }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap"> 
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">Sửa</a>
                        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-sm btn-info">Địa chỉ</a>
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Xác nhận xóa khách hàng này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Chưa có khách hàng</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

    </div>

    <div class="mt-3">
        {{ $customers->appends(request()->except('page'))->links() }}
    </div>
    <script>
    // Checkbox chọn tất cả
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.row-check');
        checkAll && checkAll.addEventListener('change', function() {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
        // Gửi danh sách id đã chọn khi submit xóa nhiều
        const bulkForm = document.getElementById('bulkDeleteForm');
        if(bulkForm) {
            bulkForm.addEventListener('submit', function(e) {
                const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
                if(ids.length === 0) {
                    alert('Vui lòng chọn ít nhất 1 khách hàng để xóa!');
                    e.preventDefault();
                    return false;
                }
                document.getElementById('bulkDeleteIds').value = ids.join(',');
            });
        }
    });
    </script>
</div>
@endsection
