@extends('layouts.app')
@section('content')
<div class="container py-4" style="max-width:900px">
    <h2>Thông báo đăng nhập — Web & my_app</h2>
    <p class="text-muted">Thông báo xuất hiện sau mỗi lần đăng nhập khi được bật. Nội dung dùng chung cho web và ứng dụng, giữ nguyên các dòng xuống hàng.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.login-notice.update') }}" class="card card-body">
        @csrf @method('PUT')
        <input type="hidden" name="enabled" value="0">
        <label class="mb-3"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $notice['enabled']))> Bật thông báo trên web và my_app</label>
        <label for="notice-title">Tiêu đề</label>
        <input id="notice-title" class="form-control mb-3" name="title" maxlength="200" required value="{{ old('title', $notice['title']) }}">
        <label for="notice-content">Nội dung (bao gồm thời gian demo)</label>
        <textarea id="notice-content" class="form-control mb-3" name="content" rows="12" maxlength="10000" required>{{ old('content', $notice['content']) }}</textarea>
        <label for="notice-footer">Footer — ghi chú cuối popup</label>
        <textarea id="notice-footer" class="form-control mb-3" name="footer" rows="4" maxlength="3000">{{ old('footer', $notice['footer']) }}</textarea>
        <div><button class="btn btn-primary">Lưu thông báo</button></div>
    </form>
</div>
@endsection
