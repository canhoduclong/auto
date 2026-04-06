@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2>Quản lý Tỉnh / Thành phố</h2>
        <a href="{{ route('provinces.create') }}" class="btn btn-success">Tạo tỉnh/thành phố mới</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Tỉnh / Thành phố</th>
                <th>Số lượng phường/xã</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($provinces as $province)
                <tr>
                    <td>{{ $province->id }}</td>
                    <td>{{ $province->name }}</td>
                    <td>{{ $province->wards_count }}</td>
                    <td>
                        <a href="{{ route('provinces.show', $province) }}" class="btn btn-primary btn-sm">Xem chi tiết</a>
                        <a href="{{ route('provinces.edit', $province) }}" class="btn btn-warning btn-sm">Sửa</a>
                        <form action="{{ route('provinces.destroy', $province) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa tỉnh/thành phố này cùng toàn bộ phường/xã?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $provinces->links() }}
</div>
@endsection
