@extends($notificationLayout ?? 'layouts.admin')

@section($notificationSection ?? 'content')
@php
    $data = $notification->data ?? [];
    $title = $data['title'] ?? 'Thông báo';
    $message = trim((string) ($data['message'] ?? ''));
    $targetRoles = collect($data['target_roles'] ?? [])->filter()->values();
    $externalUrl = trim((string) ($data['url'] ?? ''));
    $expiresAt = !empty($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null;
    $sender = !empty($data['sender_id'])
        ? \App\Models\User::with(['department', 'roles'])->find((int) $data['sender_id'])
        : null;
    $senderRoles = $sender?->roles?->pluck('name')->filter()->values() ?? collect();
    $senderDepartment = $sender?->department?->name ?: ($senderRoles->isNotEmpty() ? $senderRoles->join(', ') : 'Hệ thống');
@endphp

<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route($notificationIndexRouteName ?? 'admin.notifications.index', ['layout' => $notificationLayoutKey ?? null]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="text-uppercase small fw-semibold text-muted">Chi tiết thông báo</div>
                <h4 class="mb-0 mt-1">{{ $title }}</h4>
            </div>
            <span class="badge {{ $notification->read_at ? 'text-bg-success' : 'text-bg-warning' }}">
                {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="small text-muted">Người gửi</div>
                    <div class="fw-semibold">{{ $sender?->name ?: 'Hệ thống' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Phòng ban/Vai trò người gửi</div>
                    <div class="fw-semibold">{{ $senderDepartment }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Thời gian gửi</div>
                    <div class="fw-semibold">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Vai trò nhận</div>
                    <div class="fw-semibold">{{ $targetRoles->isNotEmpty() ? $targetRoles->join(', ') : 'Cá nhân' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Người nhận</div>
                    <div class="fw-semibold">{{ auth()->user()?->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Thời hạn hiển thị</div>
                    <div class="fw-semibold">{{ $expiresAt ? $expiresAt->format('d/m/Y H:i') : 'Không hết hạn' }}</div>
                </div>
            </div>

            <div class="border rounded-3 p-3 bg-white">
                <div class="small text-muted text-uppercase fw-semibold mb-2">Nội dung</div>
                <div style="white-space: pre-line; overflow-wrap:anywhere;">{{ $message ?: '-' }}</div>
            </div>

            @if($externalUrl !== '')
                <div class="mt-3">
                    <a href="{{ $externalUrl }}" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Mở liên kết tham khảo
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
