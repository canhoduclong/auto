<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Nhân viên</th>
                <th>Tổng tiền</th>
                <th>Trạng thái đơn</th>
                <th>Tạo lúc</th>
                @foreach($steps as $step)
                    <th>B{{ $step->step_order }} - {{ strtoupper(str_replace('_', ' ', (string) $step->role_slug)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order['id']) }}">{{ $order['code'] }}</a>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $order['customer_name'] }}</div>
                        <small class="text-muted">Giờ giao: {{ $order['delivery_time'] ?: '-' }}</small>
                    </td>
                    <td>{{ $order['staff_name'] }}</td>
                    <td>{{ number_format((float) $order['total'], 0, ',', '.') }} đ</td>
                    <td><span class="badge bg-secondary">{{ $order['status'] }}</span></td>
                    <td>{{ $order['created_at'] }}</td>
                    @foreach($order['cells'] as $cell)
                        <td>
                            @php
                                $badgeClass = match ($cell['status']) {
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'returned' => 'bg-danger',
                                    'processing' => 'bg-info text-dark',
                                    'pending' => 'bg-warning text-dark',
                                    default => 'bg-light text-dark',
                                };

                                $statusText = match ($cell['status']) {
                                    'approved' => 'Đã duyệt',
                                    'rejected' => 'Từ chối',
                                    'returned' => 'Trả hàng',
                                    'processing' => 'Đang đóng hàng',
                                    'pending' => 'Đang chờ',
                                    default => 'Chưa khởi tạo',
                                };
                            @endphp

                            <div class="d-flex flex-column gap-1">
                                <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                                <small class="text-muted">{{ $cell['approver_name'] ?: '-' }}</small>
                                <small class="text-muted">{{ $cell['approved_at'] ?: '' }}</small>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + $steps->count() }}" class="text-center text-muted py-4">Chưa có đơn hàng để theo dõi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
