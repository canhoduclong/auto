@extends('layouts.shipper')

@section('title', 'Lịch sử giao hàng')
@section('subtitle', 'Đơn đã giao, đã trả và hoàn thành')

@section('content')
@php
$statusMap = [
    'delivered'          => ['label' => 'Đã giao',         'color' => 'success'],
    'returning'          => ['label' => 'Đang trả',        'color' => 'warning'],
    'returned_completed' => ['label' => 'Đã nhập kho trả', 'color' => 'secondary'],
    'completed'          => ['label' => 'Hoàn thành',      'color' => 'success'],
];
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1 text-secondary"></i>Lịch sử giao hàng
    </div>
    @if($orders->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2">Chưa có lịch sử giao hàng.</p>
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Đã thu</th>
                    <th>Trạng thái</th>
                    <th>Lý do trả</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $i => $order)
                @php $st = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'secondary']; @endphp
                <tr>
                    <td class="text-muted">{{ $orders->firstItem() + $i }}</td>
                    <td class="fw-semibold">{{ $order->code }}</td>
                    <td>
                        <div>{{ $order->customer?->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $order->customer?->phone }}</div>
                    </td>
                    <td>{{ number_format($order->total) }}đ</td>
                    <td>
                        @if($order->collected_amount !== null)
                            <span class="fw-semibold text-success">{{ number_format($order->collected_amount) }}đ</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $st['color'] }}">{{ $st['label'] }}</span>
                    </td>
                    <td class="small text-muted">
                        @php
                            $reasons = [
                                'customer_refused' => 'Khách từ chối',
                                'no_contact'       => 'Không liên lạc được',
                                'wrong_address'    => 'Sai địa chỉ',
                                'damaged'          => 'Hàng hỏng',
                            ];
                        @endphp
                        {{ $reasons[$order->return_reason] ?? ($order->return_reason ? $order->return_reason : '—') }}
                    </td>
                    <td class="text-muted small">{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
