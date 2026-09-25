@if(($tab ?? 'orders') === 'orders')
    <div class="wr-content-pane active">
        <div class="table-responsive">
            <table class="table wr-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabData as $order)
                        @php
                            $statusKey = (string) ($order->status ?? '');
                            $statusClass = $statusClasses[$statusKey] ?? 'muted';
                            $statusText = $statusLabels[$statusKey] ?? ($statusKey ?: '-');
                        @endphp
                        <tr>
                            <td class="fw-bold text-primary">#{{ $order->code ?? $order->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $order->customer->phone ?? '' }}</div>
                            </td>
                            <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</td>
                            <td><span class="wr-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="text-end fw-bold">{{ number_format($order->total ?? 0, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="wr-empty">Không có đơn hàng trong khoảng thời gian này.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tabData->hasPages())
            <div class="px-4 pb-3 pt-2">
                {{ $tabData->links() }}
            </div>
        @endif
    </div>
@elseif(($tab ?? '') === 'new-customers')
    <div class="wr-content-pane active">
        <div class="table-responsive">
            <table class="table wr-table">
                <thead>
                    <tr>
                        <th>Tên khách hàng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Tình trạng chăm sóc mới nhất</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabData as $customer)
                        <tr>
                            <td class="fw-semibold">{{ $customer->name ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>
                                @if($customer->latestCareLog)
                                    <div class="fw-semibold">{{ $customer->latestCareLog->note }}</div>
                                    <div class="wr-activity-meta">
                                        {{ optional($customer->latestCareLog->created_at)->format('d/m/Y H:i') }}
                                        @if($customer->latestCareLog->user)
                                            - {{ $customer->latestCareLog->user->name }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Chưa có chăm sóc</span>
                                @endif
                            </td>
                            <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="wr-empty">Không có khách hàng mới trong khoảng thời gian này.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tabData->hasPages())
            <div class="px-4 pb-3 pt-2">
                {{ $tabData->links() }}
            </div>
        @endif
    </div>
@elseif(($tab ?? '') === 'all-customers')
    <div class="wr-content-pane active">
        <div class="table-responsive">
            <table class="table wr-table">
                <thead>
                    <tr>
                        <th>Tên khách hàng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Tình trạng chăm sóc mới nhất</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabData as $customer)
                        <tr>
                            <td class="fw-semibold">{{ $customer->name ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>
                                @if($customer->latestCareLog)
                                    <div class="fw-semibold">{{ $customer->latestCareLog->note }}</div>
                                    <div class="wr-activity-meta">
                                        {{ optional($customer->latestCareLog->created_at)->format('d/m/Y H:i') }}
                                        @if($customer->latestCareLog->user)
                                            - {{ $customer->latestCareLog->user->name }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Chưa có chăm sóc</span>
                                @endif
                            </td>
                            <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="wr-empty">Không có khách hàng trong khoảng thời gian này.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tabData->hasPages())
            <div class="px-4 pb-3 pt-2">
                {{ $tabData->links() }}
            </div>
        @endif
    </div>
@else
    <div class="wr-content-pane active">
        <div class="row g-3 mb-3 px-3">
            <div class="col-6 col-md-3">
                <div class="wr-stat-card">
                    <div class="wr-stat-label">Tổng hoạt động</div>
                    <div class="wr-stat-value">{{ number_format($activityCount ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="wr-stat-card">
                    <div class="wr-stat-label">User hoạt động</div>
                    <div class="wr-stat-value">{{ number_format($activeUserCount ?? 0) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="wr-stat-card">
                    <div class="wr-stat-label">Nhịp hoạt động theo ngày</div>
                    <div class="wr-activity-meta">
                        @if(!empty($activityByDay) && count($activityByDay) > 0)
                            {{ collect($activityByDay)->map(fn($row) => $row['day'] . ': ' . $row['count'])->join(' | ') }}
                        @else
                            Chưa có hoạt động trong khoảng thời gian đã chọn.
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table wr-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Hành động</th>
                        <th>Nội dung</th>
                        <th>Liên kết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabData as $event)
                        @php
                            $actorName = $event->actor?->name ?? 'Không xác định';
                            $actorRole = $event->actor?->roles?->pluck('name')->first() ?? 'N/A';
                        @endphp
                        <tr>
                            <td>{{ $event->created_at ? $event->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $actorName }}</div>
                                <span class="wr-activity-role">{{ $actorRole }}</span>
                            </td>
                            <td>{{ $event->action ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $event->title ?: '-' }}</div>
                                @if(!empty($event->message))
                                    <div class="wr-activity-meta">{{ $event->message }}</div>
                                @endif
                            </td>
                            <td>
                                @if(!empty($event->url))
                                    <a href="{{ $event->url }}" class="wr-activity-link">Xem</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="wr-empty">Không có dữ liệu hoạt động trên app cho Sale/Leader/Manager trong khoảng này.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tabData->hasPages())
            <div class="px-4 pb-3 pt-2">
                {{ $tabData->links() }}
            </div>
        @endif
    </div>
@endif
