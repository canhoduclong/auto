@extends($notificationLayout ?? 'layouts.admin')

@section($notificationSection ?? 'content')
@php
    $isAdminCenter = $isAdminNotificationCenter ?? false;
    $priorityMeta = [
        'info' => ['Thông tin', 'primary', 'ph-info'],
        'success' => ['Tích cực', 'success', 'ph-check-circle'],
        'warning' => ['Quan trọng', 'warning', 'ph-warning'],
        'danger' => ['Khẩn cấp', 'danger', 'ph-siren'],
    ];
@endphp
<div class="container-fluid py-3 notification-admin">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">{{ $isAdminCenter ? 'Quản trị thông báo' : 'Thông báo phòng ban' }}</h3>
            <div class="text-muted">{{ $isAdminCenter ? 'Phát tin, lên lịch và theo dõi mức độ tiếp nhận trên toàn hệ thống.' : 'Tạo và theo dõi thông báo trong phạm vi phòng ban.' }}</div>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route($notificationReadAllRouteName ?? 'admin.notifications.read_all', ['layout' => $notificationLayoutKey ?? null]) }}" method="POST">@csrf
                <button class="btn btn-outline-secondary"><i class="ph-checks me-1"></i>Đánh dấu đã đọc</button>
            </form>
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#notificationComposer"><i class="ph-paper-plane-tilt me-1"></i>Tạo thông báo</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Chưa thể lưu thông báo:</strong><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($isAdminCenter)
    <div class="row g-3 mb-4">
        @foreach([
            ['Tổng chiến dịch', $broadcastMetrics['total'], 'text-dark'],
            ['Đang hiển thị', $broadcastMetrics['active'], 'text-success'],
            ['Đã lên lịch', $broadcastMetrics['scheduled'], 'text-primary'],
            ['Lượt đã đọc', $broadcastMetrics['read_count'].'/'.$broadcastMetrics['recipient_count'], 'text-dark'],
        ] as [$label, $value, $color])
            <div class="col-6 col-xl"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="small text-muted">{{ $label }}</div><div class="fs-3 fw-bold {{ $color }}">{{ $value }}</div></div></div></div>
        @endforeach
        <div class="col-12 col-xl"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><div class="d-flex justify-content-between"><span class="small text-muted">Tỷ lệ đã đọc</span><strong>{{ $broadcastMetrics['read_rate'] }}%</strong></div><div class="progress mt-3" style="height:8px"><div class="progress-bar" style="width:{{ $broadcastMetrics['read_rate'] }}%"></div></div></div></div></div>
    </div>
    @endif

    <div class="collapse {{ $errors->any() ? 'show' : '' }} mb-4" id="notificationComposer">
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary text-white fw-semibold"><i class="ph-megaphone me-1"></i>Soạn thông báo mới</div>
            <div class="card-body">
                <form id="broadcastCreateForm" action="{{ route($notificationBroadcastRouteName ?? 'admin.notifications.department_broadcast', ['layout' => $notificationLayoutKey ?? null]) }}" method="POST" class="row g-3">@csrf
                    <div class="col-lg-8"><label class="form-label">Tiêu đề *</label><input id="broadcastTitle" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required></div>
                    <div class="col-lg-4"><label class="form-label">Mức ưu tiên</label><select id="broadcastPriority" name="priority" class="form-select">@foreach($priorityOptions as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'info') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Nội dung *</label><textarea id="broadcastMessage" name="message" class="form-control" rows="4" maxlength="2000" required>{{ old('message') }}</textarea><div class="text-end form-text" id="messageCounter">0/2000</div></div>
                    <div class="{{ $isAdminCenter ? 'col-lg-6' : 'col-12' }}"><label class="form-label">Nhóm/phòng ban nhận</label><div class="border rounded p-3 role-grid">
                        @foreach($departmentRoleOptions as $role => $label)<label class="form-check mb-0 {{ $role === 'all' ? 'fw-semibold text-primary' : '' }}"><input class="form-check-input js-target-role" type="checkbox" name="target_roles[]" value="{{ $role }}" @checked(in_array($role, old('target_roles', []), true))><span class="form-check-label">{{ $label }}</span></label>@endforeach
                    </div></div>
                    @if($isAdminCenter)<div class="col-lg-6"><label class="form-label">Nhân sự cụ thể</label><select id="broadcastUsers" name="target_user_ids[]" class="form-select" multiple size="6">@foreach($notificationUsers as $notificationUser)<option value="{{ $notificationUser->id }}" @selected(in_array($notificationUser->id, array_map('intval', old('target_user_ids', [])), true))>{{ $notificationUser->name }} — {{ $notificationUser->email }}</option>@endforeach</select><div class="form-text">Giữ Ctrl/Cmd để chọn nhiều người; người trùng phòng ban chỉ nhận một lần.</div></div>@endif
                    <div class="col-lg-4"><label class="form-label">Liên kết hành động</label><input id="broadcastUrl" name="url" class="form-control" maxlength="500" value="{{ old('url') }}" placeholder="/dashboard hoặc https://..."></div>
                    @if($isAdminCenter)<div class="col-lg-4"><label class="form-label">Bắt đầu hiển thị</label><input id="broadcastScheduledAt" type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}"><div class="form-text">Để trống để gửi ngay.</div></div>@endif
                    <div class="col-lg-4"><label class="form-label">Tự động hết hạn</label><input id="broadcastExpiresAt" type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}"><div class="form-text">Để trống nếu không hết hạn.</div></div>
                    <div class="col-12 text-end"><button type="reset" class="btn btn-light me-2">Nhập lại</button><button class="btn btn-primary"><i class="ph-paper-plane-tilt me-1"></i>Gửi / lên lịch</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-1">{{ $isAdminCenter ? 'Thông báo toàn hệ thống' : 'Thông báo tôi đã tạo' }}</h5><small class="text-muted">{{ $sentBroadcasts->total() }} kết quả</small>
            <form method="GET" action="{{ route($notificationIndexRouteName ?? 'admin.notifications.index') }}" class="row g-2 mt-2">
                @if(!empty($notificationLayoutKey))<input type="hidden" name="layout" value="{{ $notificationLayoutKey }}">@endif
                <div class="col-lg-4"><input type="search" name="broadcast_search" class="form-control" value="{{ request('broadcast_search') }}" placeholder="Tìm tiêu đề, nội dung, người gửi..."></div>
                <div class="col-6 col-lg-2"><select name="broadcast_status" class="form-select"><option value="">Mọi trạng thái</option><option value="active" @selected(request('broadcast_status') === 'active')>Đang hiển thị</option><option value="scheduled" @selected(request('broadcast_status') === 'scheduled')>Đã lên lịch</option><option value="expired" @selected(request('broadcast_status') === 'expired')>Hết hạn</option></select></div>
                <div class="col-6 col-lg-2"><select name="broadcast_priority" class="form-select"><option value="">Mọi ưu tiên</option>@foreach($priorityOptions as $value => $label)<option value="{{ $value }}" @selected(request('broadcast_priority') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-8 col-lg-2"><select name="broadcast_role" class="form-select"><option value="">Mọi nhóm nhận</option>@foreach($departmentRoleOptions as $value => $label)<option value="{{ $value }}" @selected(request('broadcast_role') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-4 col-lg-2 d-grid"><button class="btn btn-outline-primary"><i class="ph-funnel me-1"></i>Lọc</button></div>
            </form>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Thông báo</th>@if($isAdminCenter)<th>Người gửi</th>@endif<th>Đối tượng</th><th>Tiếp nhận</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($sentBroadcasts as $broadcast)
                @php
                    $meta = $priorityMeta[$broadcast['priority']] ?? $priorityMeta['info'];
                    $expiresAt = !empty($broadcast['expires_at']) ? \Carbon\Carbon::parse($broadcast['expires_at']) : null;
                    $scheduledAt = !empty($broadcast['scheduled_at']) ? \Carbon\Carbon::parse($broadcast['scheduled_at']) : null;
                    $rowId = 'broadcast-' . \Illuminate\Support\Str::slug($broadcast['broadcast_id']);
                    $readRate = $broadcast['recipient_count'] > 0 ? round($broadcast['read_count'] / $broadcast['recipient_count'] * 100) : 0;
                    $duplicateData = ['title'=>$broadcast['title'],'message'=>$broadcast['message'],'url'=>$broadcast['url'],'priority'=>$broadcast['priority'],'target_roles'=>$broadcast['target_roles'],'target_user_ids'=>$broadcast['target_user_ids']];
                @endphp
                <tr>
                    <td style="min-width:280px"><div><span class="badge text-bg-{{ $meta[1] }} me-1"><i class="ph {{ $meta[2] }}"></i> {{ $meta[0] }}</span><strong>{{ $broadcast['title'] }}</strong></div><div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($broadcast['message'], 105) }}</div><small class="text-muted">Tạo {{ optional($broadcast['created_at'])->format('d/m/Y H:i') }}</small></td>
                    @if($isAdminCenter)<td><strong>{{ $broadcast['sender_name'] }}</strong><br><small class="text-muted">{{ $broadcast['sender_email'] }}</small></td>@endif
                    <td style="min-width:150px">@foreach(collect($broadcast['target_roles'])->take(3) as $role)<span class="badge bg-light text-dark border me-1 mb-1">{{ $departmentRoleOptions[$role] ?? $role }}</span>@endforeach @if(count($broadcast['target_user_ids']))<small class="d-block text-muted">+ {{ count($broadcast['target_user_ids']) }} người chọn riêng</small>@endif</td>
                    <td style="min-width:135px"><div class="d-flex justify-content-between small"><span>{{ $broadcast['read_count'] }}/{{ $broadcast['recipient_count'] }}</span><strong>{{ $readRate }}%</strong></div><div class="progress mt-1" style="height:6px"><div class="progress-bar bg-success" style="width:{{ $readRate }}%"></div></div></td>
                    <td>@if($broadcast['is_expired'])<span class="badge text-bg-secondary">Hết hạn</span>@elseif($broadcast['is_scheduled'])<span class="badge text-bg-primary">Lên lịch</span><small class="d-block text-muted">{{ $scheduledAt?->format('d/m H:i') }}</small>@else<span class="badge text-bg-success">Đang hiển thị</span>@endif @if($expiresAt)<small class="d-block text-muted">Hết {{ $expiresAt->format('d/m H:i') }}</small>@endif</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#detail-{{ $rowId }}" title="Người nhận"><i class="ph-eye"></i></button>
                        @if($isAdminCenter)<button class="btn btn-sm btn-outline-secondary" title="Nhân bản" onclick="duplicateBroadcast({{ \Illuminate\Support\Js::from($duplicateData) }})"><i class="ph-copy"></i></button>@endif
                        @if($broadcast['can_edit'])<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $rowId }}"><i class="ph-pencil-simple"></i></button><form action="{{ route($notificationDestroyRouteName ?? 'admin.notifications.destroy', ['broadcastId'=>$broadcast['broadcast_id'],'layout'=>$notificationLayoutKey ?? null]) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa thông báo khỏi tất cả người nhận?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ph-trash"></i></button></form>@endif
                    </td>
                </tr>
                <tr class="collapse" id="detail-{{ $rowId }}"><td colspan="{{ $isAdminCenter ? 6 : 5 }}" class="bg-light p-3"><div class="fw-semibold mb-2">Chi tiết người nhận ({{ $broadcast['recipient_count'] }})</div><div class="recipient-grid">@foreach($broadcast['recipients'] as $recipient)<div class="bg-white border rounded p-2 d-flex justify-content-between"><div><strong class="small">{{ $recipient['name'] }}</strong><div class="text-muted small">{{ $recipient['email'] }}</div></div><span class="badge text-bg-{{ $recipient['read_at'] ? 'success' : 'warning' }} align-self-center">{{ $recipient['read_at'] ? 'Đã đọc' : 'Chưa đọc' }}</span></div>@endforeach</div></td></tr>
                @if($broadcast['can_edit'])<tr class="collapse" id="edit-{{ $rowId }}"><td colspan="{{ $isAdminCenter ? 6 : 5 }}" class="bg-light p-3">
                    <form action="{{ route($notificationUpdateRouteName ?? 'admin.notifications.update', ['broadcastId'=>$broadcast['broadcast_id'],'layout'=>$notificationLayoutKey ?? null]) }}" method="POST" class="row g-2">@csrf @method('PUT')
                        <div class="col-lg-6"><label class="form-label">Tiêu đề</label><input name="title" class="form-control" value="{{ $broadcast['title'] }}" maxlength="160" required></div><div class="col-lg-3"><label class="form-label">Ưu tiên</label><select name="priority" class="form-select">@foreach($priorityOptions as $value=>$label)<option value="{{ $value }}" @selected($broadcast['priority']===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-lg-3"><label class="form-label">Liên kết</label><input name="url" class="form-control" value="{{ $broadcast['url'] }}"></div>
                        <div class="col-12"><label class="form-label">Nội dung</label><textarea name="message" class="form-control" rows="3" maxlength="2000" required>{{ $broadcast['message'] }}</textarea></div>
                        <div class="{{ $isAdminCenter ? 'col-lg-6' : 'col-12' }}"><label class="form-label">Nhóm nhận</label><div class="d-flex flex-wrap gap-3 border rounded p-2">@foreach($departmentRoleOptions as $role=>$label)<label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="target_roles[]" value="{{ $role }}" @checked(in_array($role,$broadcast['target_roles'],true))><span class="form-check-label">{{ $label }}</span></label>@endforeach</div></div>
                        @if($isAdminCenter)<div class="col-lg-6"><label class="form-label">Nhân sự cụ thể</label><select name="target_user_ids[]" class="form-select" multiple size="4">@foreach($notificationUsers as $notificationUser)<option value="{{ $notificationUser->id }}" @selected(in_array($notificationUser->id,array_map('intval',$broadcast['target_user_ids']),true))>{{ $notificationUser->name }} — {{ $notificationUser->email }}</option>@endforeach</select></div><div class="col-lg-3"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="scheduled_at" class="form-control" value="{{ $scheduledAt?->format('Y-m-d\TH:i') }}"></div>@endif
                        <div class="col-lg-3"><label class="form-label">Hết hạn</label><input type="datetime-local" name="expires_at" class="form-control" value="{{ $expiresAt?->format('Y-m-d\TH:i') }}"></div><div class="col-12 text-end"><button class="btn btn-primary"><i class="ph-floppy-disk me-1"></i>Lưu và cập nhật người nhận</button></div>
                    </form>
                </td></tr>@endif
            @empty<tr><td colspan="{{ $isAdminCenter ? 6 : 5 }}" class="text-center text-muted py-5"><i class="ph-bell-slash fs-1 d-block"></i>Không có thông báo phù hợp.</td></tr>@endforelse
            </tbody>
        </table></div>
        @if($sentBroadcasts->hasPages())<div class="card-footer bg-white">{{ $sentBroadcasts->links('pagination::bootstrap-5') }}</div>@endif
    </div>

    <div class="card shadow-sm"><div class="card-header bg-white"><h5 class="mb-0">Hộp thư của tôi</h5></div><div class="list-group list-group-flush">
        @forelse($notifications as $notification)@php $receivedMeta=$priorityMeta[$notification->data['priority'] ?? 'info'] ?? $priorityMeta['info']; @endphp
            <div class="list-group-item d-flex align-items-center gap-2 p-0 {{ is_null($notification->read_at) ? 'bg-light' : '' }}">
                <a href="{{ route($notificationShowRouteName ?? 'admin.notifications.show', ['notificationId'=>$notification->id,'layout'=>$notificationLayoutKey ?? null]) }}" class="list-group-item-action d-flex gap-3 py-3 px-3 text-decoration-none text-body flex-grow-1">
                    <span class="notification-icon text-bg-{{ $receivedMeta[1] }}"><i class="ph {{ $receivedMeta[2] }}"></i></span>
                    <span class="flex-grow-1"><span class="d-flex justify-content-between"><strong>{{ $notification->data['title'] ?? 'Thông báo' }}</strong><small class="text-muted">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</small></span><span class="text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '-',180) }}</span></span>
                    @if(is_null($notification->read_at))<span class="badge bg-primary rounded-pill align-self-center">Mới</span>@endif
                </a>
                <form action="{{ route($notificationDeleteRouteName ?? 'admin.notifications.notification.destroy', ['notificationId'=>$notification->id,'layout'=>$notificationLayoutKey ?? null]) }}" method="POST" class="pe-3" onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này khỏi hộp thư?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa thông báo" aria-label="Xóa thông báo"><i class="ph-trash"></i></button>
                </form>
            </div>
        @empty<div class="text-center text-muted py-5">Chưa có thông báo nào trong hộp thư.</div>@endforelse
    </div>@if($notifications->hasPages())<div class="card-footer bg-white">{{ $notifications->links('pagination::bootstrap-5') }}</div>@endif</div>
</div>

@push('styles')<style>
.notification-admin .role-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:.7rem 1rem;max-height:190px;overflow:auto}.notification-admin .recipient-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.5rem;max-height:280px;overflow:auto}.notification-admin .notification-icon{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto}.notification-admin .table>:not(caption)>*>*{padding:.85rem}
</style>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const m=document.getElementById('broadcastMessage'),c=document.getElementById('messageCounter'),refresh=()=>{if(m&&c)c.textContent=`${m.value.length}/2000`};m?.addEventListener('input',refresh);refresh();const all=document.querySelector('.js-target-role[value="all"]');all?.addEventListener('change',function(){if(this.checked)document.querySelectorAll('.js-target-role:not([value="all"])').forEach(i=>i.checked=false)});document.querySelectorAll('.js-target-role:not([value="all"])').forEach(i=>i.addEventListener('change',()=>{if(i.checked&&all)all.checked=false}))});
function duplicateBroadcast(data){document.getElementById('broadcastTitle').value=`Bản sao - ${data.title}`;document.getElementById('broadcastMessage').value=data.message||'';document.getElementById('broadcastUrl').value=data.url||'';document.getElementById('broadcastPriority').value=data.priority||'info';document.querySelectorAll('.js-target-role').forEach(i=>i.checked=(data.target_roles||[]).includes(i.value));const users=document.getElementById('broadcastUsers');if(users)Array.from(users.options).forEach(o=>o.selected=(data.target_user_ids||[]).map(String).includes(o.value));bootstrap.Collapse.getOrCreateInstance(document.getElementById('notificationComposer')).show();document.getElementById('broadcastTitle').scrollIntoView({behavior:'smooth',block:'center'});document.getElementById('broadcastTitle').focus()}
</script>@endpush
@endsection
