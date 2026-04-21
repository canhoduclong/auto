@extends('layouts.admin')

@section('content')
<div class="content-inner">
    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-path me-2 text-primary"></i> Quản lý Tuyến vận chuyển</h4>
                <span class="text-muted">Danh sách các tuyến đường nhà xe</span>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('admin.truck-routes.create') }}" class="btn btn-primary">
                    <i class="ph-plus me-1"></i> Tạo tuyến mới
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
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px;" placeholder="Tên tuyến...">
                    <select name="brand_id" class="form-select form-select-sm" style="max-width:200px;">
                        <option value="">-- Nhà xe --</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ (string)$brandId === (string)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="ph-magnifying-glass"></i> Lọc</button>
                    <a href="{{ route('admin.truck-routes.index') }}" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Tên tuyến</th>
                            <th>Nhà xe</th>
                            <th>Điểm xuất phát → Điểm đến</th>
                            <th class="text-center">Chặng</th>
                            <th class="text-end">Biểu giá (₫)</th>
                            <th class="text-center">Trạng thái</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($routes as $route)
                        @php
                            $firstStop = $route->stops->first();
                            $lastStop  = $route->stops->last();
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $route->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $route->name }}</div>
                                @if($route->description)
                                    <div class="text-muted" style="font-size:.78rem;">{{ Str::limit($route->description, 50) }}</div>
                                @endif
                            </td>
                            <td>{{ $route->brand?->name ?? '-' }}</td>
                            <td style="font-size:.85rem;">
                                @if($firstStop)
                                    <span class="badge bg-success bg-opacity-10 text-success me-1">{{ $firstStop->station?->name }}</span>
                                    @if($firstStop->arrival_time) <span class="text-muted">{{ $firstStop->arrival_time }}</span> @endif
                                @endif
                                @if($lastStop && $lastStop->id !== $firstStop?->id)
                                    <i class="ph-arrow-right text-muted mx-1"></i>
                                    <span class="badge bg-danger bg-opacity-10 text-danger">{{ $lastStop->station?->name }}</span>
                                    @if($lastStop->arrival_time) <span class="text-muted">{{ $lastStop->arrival_time }}</span> @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $route->stops_count }}</span>
                            </td>
                            <td class="text-end">
                                {{ $route->current_price ? number_format($route->current_price) : '-' }}
                            </td>
                            <td class="text-center">
                                @if($route->is_active)
                                    <span class="badge bg-success bg-opacity-15 text-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-secondary">Tạm dừng</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.truck-routes.edit', $route) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ph-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.truck-routes.destroy', $route) }}" class="d-inline"
                                      onsubmit="return confirm('Xóa tuyến {{ addslashes($route->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có tuyến nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($routes->hasPages())
            <div class="card-footer">{{ $routes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
