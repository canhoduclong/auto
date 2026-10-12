@extends('layouts.admin')
@section('content')
<div class="container-fluid py-3 py-lg-4">
    <h4>Chỉnh sửa cấu hình .env</h4>
    <p class="text-muted">Nội dung được tải trực tiếp từ tệp cấu hình trên server. Chỉ admin có quyền truy cập.</p>
    @if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
    @if($error)<div class="alert alert-danger" role="alert">{{ $error }}</div>@endif
    <div class="alert alert-warning">Tệp chứa mật khẩu và khóa kết nối. Thay đổi APP_KEY, cơ sở dữ liệu hoặc phiên đăng nhập có thể ảnh hưởng dữ liệu và quyền truy cập. Mỗi lần lưu có thay đổi sẽ tạo bản dự phòng riêng tư.</div>
    <form method="POST" action="{{ route('admin.environment.update') }}" autocomplete="off">
        @csrf @method('PUT')
        <input type="hidden" name="env_hash" value="{{ $hash }}">
        <label for="env-content" class="form-label fw-semibold">Nội dung .env</label>
        <textarea id="env-content" name="env_content" rows="25" class="form-control font-monospace mb-3" style="font-size:14px;line-height:1.6;tab-size:4" spellcheck="false" autocapitalize="off" autocorrect="off" required>{{ $content }}</textarea>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" @disabled(!$hash)>Lưu cấu hình</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.environment.edit') }}">Tải lại nội dung</a>
            <a class="btn btn-light" href="{{ route('admin.settings.index') }}">Về cài đặt</a>
        </div>
    </form>
</div>
@endsection
