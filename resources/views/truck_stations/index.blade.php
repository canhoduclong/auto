@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Quản lý nhà xe</h2>
        <a href="{{ route('truck-stations.create') }}" class="btn btn-primary">Thêm nhà xe</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ $keyword ?? '' }}" class="form-control" placeholder="Tìm theo tên, địa chỉ hoặc số điện thoại">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary">Tìm</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('truck-stations.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tên nhà xe</th>
                    <th>Khu vực hoạt động</th>
                    <th>Liên hệ</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($truckStations as $truckStation)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $truckStation->name }}</div>
                            <div class="small text-muted">{{ $truckStation->address ?: 'Chưa có địa chỉ chi tiết' }}</div>
                        </td>
                        <td>
                            <div>{{ $truckStation->province?->name ?: 'Chưa chọn Tỉnh/Thành' }}</div>
                            <div class="small text-muted">{{ $truckStation->ward?->name ?: 'Chưa chọn Phường/Xã' }}</div>
                        </td>
                        <td>{{ $truckStation->phone ?: '-' }}</td>
                        <td>
                            @if($truckStation->is_active)
                                <span class="badge bg-success">Đang hoạt động</span>
                            @else
                                <span class="badge bg-secondary">Tạm ngưng</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('truck-stations.edit', $truckStation) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('truck-stations.destroy', $truckStation) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa nhà xe này?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có nhà xe nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $truckStations->links() }}
</div>
@endsection
