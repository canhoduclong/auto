@php
    $departmentBroadcasts = getDepartmentBroadcastNotifications(auth()->user(), $limit ?? 5);
@endphp

@if($departmentBroadcasts->isNotEmpty())
    <div class="dept-broadcast-card mb-3">
        <div class="dept-broadcast-head">
            <div>
                <div class="dept-broadcast-title">Thông báo phòng ban</div>
                <div class="dept-broadcast-subtitle">Cập nhật mới từ ban quản trị</div>
            </div>
            <span class="badge text-bg-warning">{{ $departmentBroadcasts->whereNull('read_at')->count() }} mới</span>
        </div>
        <div class="dept-broadcast-list">
            @foreach($departmentBroadcasts as $broadcast)
                @php
                    $message = trim((string) ($broadcast['message'] ?? ''));
                    $href = $broadcast['url'] ?: null;
                @endphp
                <div class="dept-broadcast-item {{ empty($broadcast['read_at']) ? 'is-unread' : '' }}">
                    @if($href)
                        <a href="{{ $href }}" class="dept-broadcast-link">
                    @endif
                            <div class="dept-broadcast-item-title">{{ $broadcast['title'] }}</div>
                            @if($message !== '')
                                <div class="dept-broadcast-message">{{ \Illuminate\Support\Str::limit($message, 180) }}</div>
                            @endif
                            <div class="dept-broadcast-time">{{ optional($broadcast['created_at'])->format('d/m/Y H:i') }}</div>
                    @if($href)
                        </a>
                    @endif
                </div>
            @endforeach
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
            .dept-broadcast-time {
                color: #92400e;
                font-size: .78rem;
                margin-top: 2px;
            }
        </style>
    @endpush
@endonce
