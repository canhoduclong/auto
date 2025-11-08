@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Danh sách quyền</h2>
    <a href="{{ route('permissions.create') }}" class="btn btn-primary mb-3">+ Thêm quyền</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên quyền</th>
                <th>Mô tả</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($permissions as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->description }}</td>
                <td>
                    <a href="{{ route('permissions.edit', $p->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                    <form action="{{ route('permissions.destroy', $p->id) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Xóa quyền này?')" class="btn btn-sm btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
