@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2>Chi tiết Tỉnh / Thành phố</h2>
            <p class="mb-0">{{ $province->name }} &middot; {{ $province->wards->count() }} phường/xã</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('provinces.edit', $province) }}" class="btn btn-warning">Sửa tên</a>
            <a href="{{ route('provinces.index') }}" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">Thêm Phường / Xã</div>
                <div class="card-body">
                    <form action="{{ route('provinces.wards.store', $province) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Tên phường/xã</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Thêm mới</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Danh sách Phường / Xã</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên phường/xã</th>
                                <th style="width: 220px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($wards as $ward)
                                <tr>
                                    <td>{{ $ward->id }}</td>
                                    <td>
                                        <form action="{{ route('provinces.wards.update', [$province, $ward]) }}" method="POST" class="d-flex gap-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ old('name', $ward->name) }}" class="form-control" required>
                                            <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="{{ route('provinces.wards.destroy', [$province, $ward]) }}" method="POST" onsubmit="return confirm('Xóa phường/xã này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Chưa có phường/xã nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
