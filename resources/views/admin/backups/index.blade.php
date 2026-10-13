@extends('layouts.admin')
@section('content')
<div class="container-fluid py-3 py-lg-4">
    <h4 class="mb-2">{{ $mode === 'backup' ? 'Sao lưu hệ thống' : 'Phục hồi hệ thống' }}</h4>
    <p class="text-muted">Sao lưu toàn bộ cơ sở dữ liệu và tài liệu; mã hóa trước khi gửi lên Google Drive.</p>
    @foreach(['success'=>'success','error'=>'danger'] as $key=>$color)
        @if(session($key))<div class="alert alert-{{ $color }}" role="alert" style="white-space: pre-line">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn {{ $mode === 'backup' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.backups.index') }}">Sao lưu</a>
        <a class="btn {{ $mode === 'restore' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.backups.restore') }}">Phục hồi</a>
    </div>
    <div class="card mb-3"><div class="card-header fw-semibold">Cấu hình tài khoản Google và nguồn backup</div><div class="card-body">
        <form method="POST" action="{{ route('admin.backups.configuration') }}" autocomplete="off">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Tài khoản Google</label><input name="google_email" class="form-control" type="email" value="{{ config('system_backup.google_email') }}" required></div>
                <div class="col-md-6"><label class="form-label">Folder ID trên Drive</label><input name="folder_id" class="form-control" value="{{ config('system_backup.drive_folder_id') }}" required></div>
                <div class="col-md-6"><label class="form-label">Google Spreadsheet ID</label><input name="spreadsheet_id" class="form-control" value="{{ config('system_backup.spreadsheet_id') }}" required></div>
                <div class="col-md-12"><label class="form-label">OAuth Redirect URI</label><input name="oauth_redirect_uri" type="url" class="form-control" value="{{ app(\App\Services\Backup\BackupOAuth::class)->redirectUri() }}" required><div class="form-text">Dùng đúng domain bạn đang mở trang admin. URL phải trùng hoàn toàn với Authorized redirect URIs trong Google Cloud.</div></div>
                <div class="col-md-6"><label class="form-label">OAuth Client ID</label><input name="oauth_client_id" class="form-control" value="{{ config('system_backup.oauth_client_id') }}" required></div>
                <div class="col-md-6"><label class="form-label">OAuth Client Secret</label><input type="password" name="oauth_client_secret" class="form-control" placeholder="{{ config('system_backup.oauth_client_secret') ? 'Đã lưu — để trống để giữ nguyên' : 'Nhập Client Secret' }}" autocomplete="new-password"></div>
            </div>
            <button class="btn btn-primary mt-3">Lưu cấu hình vào .env</button>
        </form>
        <p class="small mt-3">URL chuyển hướng cần khai báo trong Google Cloud: <code class="text-break">{{ app(\App\Services\Backup\BackupOAuth::class)->redirectUri() }}</code></p>
        <p>Trạng thái OAuth: <strong>{{ config('system_backup.oauth_connected_email') ?: 'Chưa kết nối' }}</strong></p>
        <form method="POST" action="{{ route('admin.backups.oauth.connect') }}">@csrf<button class="btn btn-outline-primary">Kết nối tài khoản Google</button></form>
        <details class="mt-3"><summary>Hướng dẫn thiết lập Google OAuth</summary><ol class="mt-2"><li>Bật Google Drive API và Google Sheets API trong dự án Google Cloud.</li><li>Google Auth Platform: cấu hình ứng dụng; nếu đang Testing, thêm email đã cấu hình vào Test users.</li><li>Tạo OAuth Client loại Web application, thêm URL chuyển hướng ở trên vào Authorized redirect URIs.</li><li>Nhập Client ID / Secret và lưu. Bấm Kết nối Google, chọn đúng tài khoản đã cấu hình và cấp quyền.</li><li>Chia sẻ thư mục và Sheet cho tài khoản đã cấu hình với quyền chỉnh sửa, sau đó Kiểm tra kết nối Google.</li></ol><p class="small">Ứng dụng External ở chế độ Testing có thể hết hạn refresh token sau 7 ngày. Cần cấu hình trạng thái phát hành phù hợp để sao lưu hằng tháng.</p></details>
    </div></div>
    <div class="card mb-3"><div class="card-header fw-semibold">Kết nối Google</div><div class="card-body">
        <p>Chia sẻ thư mục Drive và Google Sheets với tài khoản dưới đây, quyền <strong>Người chỉnh sửa</strong>.</p>
        @if(config('system_backup.google_mode') === 'oauth')
            <div class="alert alert-info">Tài khoản backup: {{ config('system_backup.google_email') }} (OAuth). Chia sẻ thư mục và Sheet cho tài khoản này.</div>
        @elseif($serviceAccount)
            <div class="alert alert-info text-break">{{ $serviceAccount }}</div>
        @else
            <div class="alert alert-warning">Chưa đọc được tài khoản dịch vụ Google trên server này. Cần cấu hình thông tin kết nối trước khi kiểm tra quyền truy cập.</div>
        @endif
        @if($googleProjectId && config('system_backup.google_mode') !== 'oauth')
            <p>Dự án Google của tài khoản kết nối: <strong>{{ $googleProjectId }}</strong>. Bật API trong đúng dự án này.</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="https://console.cloud.google.com/apis/library/drive.googleapis.com?project={{ urlencode($googleProjectId) }}">Bật Drive API đúng dự án</a>
                <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="https://console.cloud.google.com/apis/library/sheets.googleapis.com?project={{ urlencode($googleProjectId) }}">Bật Sheets API đúng dự án</a>
            </div>
        @endif
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="https://drive.google.com/drive/folders/{{ config('system_backup.drive_folder_id') }}">Mở thư mục sao lưu</a>
            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="https://docs.google.com/spreadsheets/d/{{ config('system_backup.spreadsheet_id') }}/edit">Mở Google Sheets</a>
        </div>
        <form method="POST" action="{{ route('admin.backups.check') }}" class="mb-3">@csrf<button class="btn btn-outline-primary">Kiểm tra kết nối Google</button></form>
        <p class="small text-muted mb-0">Dùng Shared Drive cho service account; Drive cá nhân cần kết nối OAuth. Quyền kết nối được kiểm tra bằng nút phía trên. Nguồn sao lưu này được cấu hình riêng với bảng đồng bộ đơn hàng hiện tại.</p>
    </div></div>
    <div class="card"><div class="card-header fw-semibold">{{ $mode === 'backup' ? 'Chuẩn bị sao lưu' : 'Chuẩn bị phục hồi' }}</div><div class="card-body">
        @if($mode === 'backup')
            <p>Chỉ cần chuẩn bị một thư mục gốc trên Drive và một Google Sheets. Các thư mục cho từng bản sao sẽ được chương trình sao lưu tạo tự động.</p>
            <p>Cấu trúc dự kiến: <strong>Mã bản sao / dữ liệu, tài liệu, danh sách kiểm tra</strong>. Google Sheets ghi danh sách và trạng thái các bản sao.</p>
        @else
            <p>Hệ thống dự phòng sử dụng cùng nguồn Google để chọn bản sao hoàn tất, kiểm tra và nhập vào cơ sở dữ liệu phục hồi riêng.</p>
            <p>Trước khi nhập cần kiểm tra phiên bản dữ liệu và tắt các tác vụ gửi thông báo, thanh toán, xuất kho tự động.</p>
        @endif
        @if($mode === 'backup')
        <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
            <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary" @disabled($backups->contains(fn($item)=>in_array($item->status,['pending','running'])))>Sao lưu ngay</button></form>
            <form method="POST" action="{{ route('admin.backups.schedule') }}" class="d-flex flex-wrap gap-2 align-items-center">@csrf
                <input type="hidden" name="enabled" value="0">
                <label class="d-flex align-items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked($monthlyEnabled)> Tự động ngày 1 mỗi tháng, 02:00 ({{ config('app.timezone') }})</label>
                <button class="btn btn-outline-primary">Lưu lịch</button>
            </form>
        </div>
        <p class="small text-muted">Tác vụ chạy nền qua Laravel Scheduler. Sau khi gửi yêu cầu, tải lại trang để cập nhật tiến độ. Bản sao local được giữ lại nếu Google lỗi để thử lại.</p>
        <div class="alert alert-warning">Lưu khóa mã hóa riêng ở nơi an toàn trên server dự phòng. <a href="{{ route('admin.backups.key') }}">Tải khóa mã hóa</a>. Bản sao không chứa khóa Google, .env và mã nguồn ứng dụng. Trong lúc sao lưu, không chạy cập nhật cấu trúc DB hoặc sửa/xóa tài liệu.</div>
        @else
        <div class="alert alert-info">Danh sách bên dưới cho phép tải gói mã hóa. Chức năng nhập phục hồi tự động chưa được triển khai; không nhập SQL vào hệ thống đang hoạt động.</div>
        @endif
    </div></div>
    <div class="card mt-3"><div class="card-header fw-semibold">Lịch sử sao lưu</div><div class="card-body">
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Bản sao</th><th>Trạng thái / tiến độ</th><th>Thời gian</th><th>Dung lượng</th><th>Thao tác</th></tr></thead><tbody>
        @forelse($backups as $backup)
            <tr><td><strong>{{ $backup->code }}</strong><div class="small text-muted">{{ $backup->type === 'monthly' ? 'Tự động hằng tháng' : 'Thủ công' }}</div></td>
                <td><span class="badge bg-{{ ['completed'=>'success','failed'=>'danger','running'=>'primary','pending'=>'secondary'][$backup->status] ?? 'secondary' }}">{{ ['completed'=>'Hoàn tất','failed'=>'Thất bại','running'=>'Đang chạy','pending'=>'Chờ xử lý'][$backup->status] ?? $backup->status }}</span><div class="small mt-1">{{ $backup->stage }}</div>@if($backup->error)<div class="small text-danger text-break">{{ $backup->error }}</div>@endif</td>
                <td>{{ $backup->created_at->format('d/m/Y H:i') }}@if($backup->completed_at)<div class="small text-muted">Hoàn tất: {{ $backup->completed_at->format('d/m/Y H:i') }}</div>@endif</td>
                <td>{{ $backup->bytes ? number_format($backup->bytes / 1048576,2,',','.') . ' MB' : '—' }}</td>
                <td><div class="d-flex flex-wrap gap-2">@if($backup->local_path)<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.backups.download',$backup) }}">Tải bản mã hóa</a>@endif
                @if($backup->drive_file_id)<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://drive.google.com/file/d/{{ $backup->drive_file_id }}/view">Drive</a>@endif
                @if($backup->status === 'failed')<form method="POST" action="{{ route('admin.backups.retry',$backup) }}">@csrf<button class="btn btn-outline-warning btn-sm">Thử lại</button></form>@endif</div></td></tr>
        @empty<tr><td colspan="5" class="text-muted">Chưa có bản sao.</td></tr>@endforelse
        </tbody></table></div>{{ $backups->links() }}
    </div></div>
</div>
@endsection
