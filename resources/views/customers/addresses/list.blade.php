@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Danh sách tất cả địa chỉ khách hàng</h2>

    <form method="GET" action="{{ route('customers.addresses.list') }}" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Tìm (tên khách / SĐT / địa chỉ)</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nhập tên, sđt, đường, căn...">
        </div>

        <div class="col-md-2">
            <label class="form-label">Tên khách hàng</label>
            <input type="text" name="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="Tên khách">
        </div>

        <div class="col-md-2">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="customer_phone" class="form-control" value="{{ request('customer_phone') }}" placeholder="SĐT">
        </div>

        <div class="col-md-2">
            <label class="form-label">Thành phố</label>
            <select name="city" class="form-select">
                <option value="">-- Tất cả --</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}" {{ request('city') === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-1">
            <label class="form-label">Số / trang</label>
            <select name="perPage" class="form-select">
                @foreach([10,15,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)request('perPage',15) === $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary">Lọc</button>
            <a href="{{ route('customers.addresses.list') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>ID</th> 
                <th>Khách hàng</th>
                <th>Phone</th>
                <th>Dự án / Zone / Block</th>
                <th>Tầng / Căn</th>
                <th>Địa chỉ (Đường / Phường / Quận / Thành phố)</th>
                <th>Mặc định</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($addresses as $addr)
                <tr>
                    <td>{{ $addr->id }}</td>
                     
                    <td>
                        {{ optional($addr->customer)->name ?? 'N/A' }}

                        {{ $addr->customer_id }}
                        
                    </td>
                    <td>{{ optional($addr->customer)->phone ?? '-' }}</td>
                    <td>
                        {{ $addr->project_name ? $addr->project_name . ' / ' : '' }}
                        {{ $addr->zone ? $addr->zone . ' / ' : '' }}
                        {{ $addr->block ?? '' }}
                    </td>
                    <td>{{ $addr->floor ? 'T' . $addr->floor . ' / ' : '' }}{{ $addr->unit_number }}</td>
                    <td>
                        {{ $addr->street ?? '' }}
                        {{ $addr->ward ? ', ' . $addr->ward : '' }}
                        {{ $addr->district ? ', ' . $addr->district : '' }}
                        {{ $addr->city ? ', ' . $addr->city : '' }}
                    </td>
                    <td>
                        @if($addr->is_default)
                            <span class="badge bg-success">Mặc định</span>
                        @endif
                    </td>
                    <td>
                        {{-- Link edit nested route: /customers/{customer}/addresses/{address}/edit --}}
                        <a href="{{ route('customers.addresses.edit', [$addr->customer_id, $addr->id]) }}" class="btn btn-sm btn-warning">Sửa</a>

                        <form action="{{ route('customers.addresses.destroy', [$addr->customer_id, $addr->id]) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Xác nhận xóa địa chỉ này?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">Không tìm thấy địa chỉ nào</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>Hiển thị {{ $addresses->firstItem() ?? 0 }} - {{ $addresses->lastItem() ?? 0 }} / {{ $addresses->total() }} địa chỉ</div>
        <div>{{ $addresses->links() }}</div>
    </div>
</div>
@endsection
