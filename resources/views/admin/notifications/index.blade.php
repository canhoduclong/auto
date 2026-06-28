@extends($notificationLayout ?? 'layouts.admin')

@section($notificationSection ?? 'content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tạo thông báo phòng ban</h4>
        <form action="{{ route($notificationReadAllRouteName ?? 'admin.notifications.read_all', ['layout' => $notificationLayoutKey ?? null]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">Danh dau tat ca da doc</button>
        </form>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light fw-semibold">Tạo thông báo tới phòng ban</div>
        <div class="card-body">
            <form action="{{ route($notificationBroadcastRouteName ?? 'admin.notifications.department_broadcast', ['layout' => $notificationLayoutKey ?? null]) }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Link khi bấm vào thông báo</label>
                    <input type="text" name="url" class="form-control" maxlength="500" value="{{ old('url') }}" placeholder="/dashboard hoặc https://...">
                </div>
                <div class="col-12">
                    <label class="form-label">Phòng ban nhận</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($departmentRoleOptions as $role => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="target_roles[]" value="{{ $role }}" @checked(in_array($role, old('target_roles', []), true))>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Nội dung</label>
                    <textarea name="message" class="form-control" rows="3" maxlength="2000" required>{{ old('message') }}</textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Gửi thông báo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tieu de</th>
                            <th>Noi dung</th>
                            <th>Thoi gian</th>
                            <th>Trang thai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ is_null($notification->read_at) ? 'table-warning' : '' }}">
                                <td>{{ $notification->data['title'] ?? 'Thong bao' }}</td>
                                <td>{{ $notification->data['message'] ?? '-' }}</td>
                                <td>{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if(is_null($notification->read_at))
                                        <span class="badge bg-warning text-dark">Chua doc</span>
                                    @else
                                        <span class="badge bg-success">Da doc</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route($notificationShowRouteName ?? 'admin.notifications.show', ['notificationId' => $notification->id, 'layout' => $notificationLayoutKey ?? null]) }}" class="btn btn-sm btn-primary">
                                        Xem chi tiết
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chua co thong bao nao.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
