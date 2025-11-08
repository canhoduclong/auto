@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Danh sách công ty</h2>
    <div class="d-flex gap-2 mb-3 align-items-end">
        <a href="{{ route('companies.create') }}" class="btn btn-success">+ Thêm công ty</a>
        <a href="{{ route('companies.import.form') }}" class="btn btn-warning">Import Excel</a>
        <a href="{{ route('companies.export') }}" class="btn btn-info">Export Excel</a>
    </div> 
    <div class="mb-3 d-flex justify-content-between align-items-end">
        <form class="row g-2" method="GET" action="{{ route('companies.index') }}">
            <div class="col-auto">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên / SĐT / Email / MST">
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
                <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                <th>#</th>
                <th>Tên công ty</th>
                <th>Mã số thuế</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Nhân viên</th>
                <th>Địa chỉ</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
                <tr>
                    <td>{{ $company->id }}</td>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->mst }}</td>
                    <td>{{ $company->phone }}</td>
                    <td>{{ $company->email }}</td>
                    <td>{{ optional($company->assignedTo)->name ?? '-' }}</td>
                    <td>{{ $company->address }}</td>
                    <td class="text-nowrap"> 
                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-warning">Sửa</a>
                        <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('Xác nhận xóa công ty này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Chưa có công ty</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

    </div>

    <div class="mt-3">
        {{ $companies->appends(request()->except('page'))->links() }}
    </div>
</div>
@endsection
