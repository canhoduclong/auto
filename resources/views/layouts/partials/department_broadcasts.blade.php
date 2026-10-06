@php
    $departmentNotificationLayoutKey = request('layout');
    if (!$departmentNotificationLayoutKey) {
        $routeName = request()->route()?->getName() ?? '';
        $departmentNotificationLayoutKey = match (true) {
            str_starts_with($routeName, 'ceo.') => 'ceo',
            str_starts_with($routeName, 'director.') => 'director',
            str_starts_with($routeName, 'accounting.') => 'accounting',
            str_starts_with($routeName, 'warehouse.') => 'warehouse',
            str_starts_with($routeName, 'shipper.') => 'shipper',
            str_starts_with($routeName, 'admin.') => 'admin',
            default => 'site',
        };
    }
    $departmentBroadcasts = getDepartmentBroadcastNotifications(auth()->user(), $limit ?? 5, $departmentNotificationLayoutKey);
    $departmentNotificationShowRoute = $departmentNotificationLayoutKey === 'admin'
        ? 'admin.notifications.show'
        : 'department-notifications.show';
    $readBroadcastCount = $departmentBroadcasts->whereNotNull('read_at')->count();
@endphp

@if($departmentBroadcasts->isNotEmpty() || ($showEmpty ?? false))
    <div class="dept-broadcast-card mb-3">
        <div class="dept-broadcast-head">
            <div>
                <div class="dept-broadcast-title">Thông báo phòng ban</div>
                <div class="dept-broadcast-subtitle">Cập nhật theo vai trò của bạn</div>
            </div>
            <div class="dept-broadcast-actions">
                <span class="badge text-bg-warning">{{ $departmentBroadcasts->whereNull('read_at')->count() }} mới</span>
                @if($readBroadcastCount > 0 && $departmentNotificationLayoutKey !== 'admin')
                    <form method="POST" action="{{ route('department-notifications.read.destroy', ['layout' => $departmentNotificationLayoutKey]) }}" onsubmit="return confirm('Xóa tất cả thông báo phòng ban đã đọc?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dept-broadcast-action" title="Xóa thông báo đã đọc"><i class="bi bi-trash3"></i> Xóa đã đọc</button>
                    </form>
                @endif
                <button type="button" class="dept-broadcast-action js-dept-broadcast-toggle" aria-expanded="true"><i class="bi bi-chevron-up"></i> Thu gọn</button>
                <button type="button" class="dept-broadcast-action js-dept-broadcast-dismiss" title="Tắt khối thông báo"><i class="bi bi-x-lg"></i> Tắt</button>
            </div>
        </div>
        <div class="dept-broadcast-list">
            @forelse($departmentBroadcasts as $broadcast)
                @php
                    $message = trim((string) ($broadcast['message'] ?? ''));
                    $href = route($departmentNotificationShowRoute, [
                        'notificationId' => $broadcast['id'],
                        'layout' => $departmentNotificationLayoutKey,
                    ]);
                @endphp
                <div class="dept-broadcast-item {{ empty($broadcast['read_at']) ? 'is-unread' : '' }}">
                    <a href="{{ $href }}" class="dept-broadcast-link">
                            <div class="dept-broadcast-item-title">{{ $broadcast['title'] }}</div>
                            <div class="dept-broadcast-sender">
                                Từ: <strong>{{ $broadcast['sender_name'] ?? 'Hệ thống' }}</strong>
                                <span>·</span>
                                Phòng ban/Vai trò: <strong>{{ $broadcast['sender_department'] ?? 'Hệ thống' }}</strong>
                            </div>
                            @if($message !== '')
                                <div class="dept-broadcast-message">{{ \Illuminate\Support\Str::limit($message, 180) }}</div>
                            @endif
                            <div class="dept-broadcast-time">{{ optional($broadcast['created_at'])->format('d/m/Y H:i') }}</div>
                    </a>
                    <form method="POST" action="{{ route('department-notifications.notification.destroy', ['notificationId' => $broadcast['id'], 'layout' => $departmentNotificationLayoutKey]) }}" class="dept-broadcast-delete" onsubmit="return confirm('Xóa thông báo này khỏi hộp thư của bạn?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dept-broadcast-action" title="Xóa thông báo khỏi hộp thư của bạn"><i class="bi bi-trash3"></i> Xóa</button>
                    </form>
                </div>
            @empty
                <div class="dept-broadcast-item">
                    <div class="dept-broadcast-message">Chưa có thông báo phòng ban.</div>
                </div>
            @endforelse
        </div>
    </div>
@endif

@once
    @push('styles')
        <style>
            .dept-broadcast-card {
                border: 1px solid #fde68a;
                border-left: 4px solid #f59e0b;
                border-radius: 14px;
                background: #fffbeb;
                box-shadow: 0 8px 18px rgba(146, 64, 14, .08);
                overflow: hidden;
            }
            .dept-broadcast-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 14px;
                border-bottom: 1px solid #fde68a;
            }
            .dept-broadcast-actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: wrap; }
            .dept-broadcast-actions form { margin: 0; }
            .dept-broadcast-delete { margin: 6px 0 0; text-align: right; }
            .dept-broadcast-action { padding: 3px 7px; border: 1px solid #f4c04e; border-radius: 5px; background: #fff; color: #92400e; font-size: .68rem; font-weight: 700; }
            .dept-broadcast-action:hover { background: #fef3c7; }
            .dept-broadcast-card.is-collapsed .dept-broadcast-list { display: none; }
            .dept-broadcast-title {
                color: #92400e;
                font-weight: 800;
                text-transform: uppercase;
                font-size: .82rem;
                letter-spacing: .04em;
            }
            .dept-broadcast-subtitle {
                color: #b45309;
                font-size: .82rem;
            }
            .dept-broadcast-list {
                padding: 0 14px;
            }
            .dept-broadcast-item {
                padding: 10px 0;
                border-bottom: 1px dashed #fcd34d;
            }
            .dept-broadcast-item:last-child {
                border-bottom: 0;
            }
            .dept-broadcast-item.is-unread .dept-broadcast-item-title::before {
                content: '';
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #dc2626;
                margin-right: 6px;
            }
            .dept-broadcast-link {
                display: block;
                color: inherit;
                text-decoration: none;
            }
            .dept-broadcast-link:hover .dept-broadcast-item-title {
                color: #0f766e;
            }
            .dept-broadcast-item-title {
                font-weight: 700;
                color: #1f2937;
            }
            .dept-broadcast-message {
                color: #374151;
                font-size: .9rem;
                margin-top: 2px;
                overflow-wrap: anywhere;
            }
            .dept-broadcast-sender {
                color: #92400e;
                font-size: .78rem;
                margin-top: 2px;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            .dept-broadcast-time {
                color: #92400e;
                font-size: .78rem;
                margin-top: 2px;
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function (event) {
                const dismissButton = event.target.closest('.js-dept-broadcast-dismiss');
                if (dismissButton) {
                    dismissButton.closest('.dept-broadcast-card')?.remove();
                    return;
                }
                const button = event.target.closest('.js-dept-broadcast-toggle');
                if (!button) return;
                const card = button.closest('.dept-broadcast-card');
                if (!card) return;
                const collapsed = card.classList.toggle('is-collapsed');
                button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                button.innerHTML = collapsed
                    ? '<i class="bi bi-chevron-down"></i> Mở thông báo'
                    : '<i class="bi bi-chevron-up"></i> Thu gọn';
            });
        </script>
    @endpush
@endonce
