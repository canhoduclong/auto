@extends('layouts.admin')

@section('content')
<div class="content-inner">
    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-map-pin me-2 text-primary"></i> Quản lý Trạm xe</h4>
                <span class="text-muted">Danh sách tất cả trạm/điểm nhà xe</span>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('admin.truck-stations.create') }}" class="btn btn-primary">
                    <i class="ph-plus me-1"></i> Thêm trạm xe
                </a>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Tên, địa chỉ, SĐT...">
                    </div>
                    <div class="col-md-2">
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">-- Nhà xe --</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$brandId === (string)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="province_id" class="form-select form-select-sm">
                            <option value="">-- Tỉnh/thành --</option>
                            @foreach($provinces as $p)
                                <option value="{{ $p->id }}" {{ (string)$provinceId === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Trạng thái --</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>Tạm dừng</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary"><i class="ph-magnifying-glass"></i> Lọc</button>
                        <a href="{{ route('admin.truck-stations.index') }}" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Tên trạm</th>
                            <th>Nhà xe</th>
                            <th>Tỉnh/thành</th>
                            <th>Địa chỉ</th>
                            <th>SĐT</th>
                            <th class="text-end">Phí bãi (₫)</th>
                            <th class="text-center">Trạng thái</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $station)
                        <tr>
                            <td class="text-muted">{{ $station->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $station->name }}</div>
                                @if($station->branch_info)
                                    <div class="text-muted" style="font-size:.78rem;">{{ $station->branch_info }}</div>
                                @endif
                            </td>
                            <td>{{ $station->brand?->name ?? '<span class="text-muted">-</span>' }}</td>
                            <td>{{ $station->province?->name ?? '-' }}</td>
                            <td class="text-muted" style="font-size:.85rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $station->address }}</td>
                            <td>{{ $station->phone ?: '-' }}</td>
                            <td class="text-end">{{ $station->parking_fee ? number_format($station->parking_fee) : '-' }}</td>
                            <td class="text-center">
                                @if($station->is_active)
                                    <span class="badge bg-success bg-opacity-15 text-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-secondary">Tạm dừng</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.truck-stations.edit', $station) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ph-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.truck-stations.destroy', $station) }}" class="d-inline"
                                      onsubmit="return confirm('Xóa trạm xe {{ addslashes($station->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Không có trạm xe nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($stations->hasPages())
            <div class="card-footer">{{ $stations->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
