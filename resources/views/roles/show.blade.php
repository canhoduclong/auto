@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Chi tiết Role</h2>

    <p><strong>ID:</strong> {{ $role->id }}</p>
    <p><strong>Tên Role:</strong> {{ $role->name }}</p>
    <p><strong>Mô tả:</strong> {{ $role->description }}</p>
    <p><strong>Ngày tạo:</strong> {{ $role->created_at->format('d/m/Y') }}</p>

    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning">Sửa</a>
    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Quay lại</a>

    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline-block;">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa role này không?')">Xóa</button>
    </form>
</div>
@endsection
