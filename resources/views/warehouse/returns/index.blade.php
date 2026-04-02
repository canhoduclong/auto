@extends('layouts.warehouse')

@section('title', 'Đơn hàng trả về')
@section('subtitle', 'Xác nhận nhập kho hàng trả')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="badge bg-danger rounded-pill">{{ $orders->count() }} đơn đang trả về</span>
    <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-check2-all fs-1 text-success"></i>
        <p class="mt-2 text-muted">Không có đơn trả hàng nào cần xử lý.</p>
    </div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Shipper</th>
                    <th>Lý do trả</th>
                    <th>Kho trả về</th>
                    <th>Ghi chú shipper</th>
                    <th>Sản phẩm</th>
                    <th>Tổng tiền</th>
                    <th>Ngày cập nhật</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $i => $order)
                @php
                    $returnWarehouse = $order->returnWarehouse ?? $order->warehouse;
                    $returnWarehouseId = $returnWarehouse?->id;
                    $canConfirm = !$managedWarehouseId
                        || ($returnWarehouseId && (int) $managedWarehouseId === (int) $returnWarehouseId);
                @endphp
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $order->code }}</td>
                    <td>
                        <div class="small fw-semibold">{{ $order->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $order->customer?->phone }}</div>
                    </td>
                    <td class="small">{{ $order->shipper?->name ?? '—' }}</td>
                    <td>
                        @php
                            $reasons = [
                                'customer_refused'   => 'Khách từ chối',
                                'no_contact'         => 'Không liên lạc được',
                                'wrong_address'      => 'Sai địa chỉ',
                                'damaged'            => 'Hàng bị hỏng',
                            ];
                        @endphp
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                            {{ $reasons[$order->return_reason] ?? $order->return_reason ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($returnWarehouse)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                {{ $returnWarehouse->name }}
                            </span>
                        @else
                            <span class="text-muted small">Chưa xác định</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $order->shipper_note ?? '—' }}</td>
                    <td class="small">{{ $order->items->sum('quantity') }} sp</td>
                    <td class="fw-semibold">{{ number_format($order->total) }}đ</td>
                    <td class="text-muted small">{{ $order->updated_at->format('d/m H:i') }}</td>
                    <td class="text-end">
                        <form action="{{ route('warehouse.returns.confirm', $order) }}" method="POST"
                              onsubmit="return confirm('Xác nhận đã nhận hàng trả từ đơn #{{ $order->code }}?')">
                            @csrf
                            <button class="btn btn-success btn-sm" {{ $canConfirm ? '' : 'disabled' }}
                                title="{{ $canConfirm ? 'Xác nhận nhập kho' : 'Đơn này không thuộc kho bạn quản lý' }}">
                                <i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho
                            </button>
                        </form>
                        @if(!$canConfirm)
                            <div class="text-muted" style="font-size:.7rem;">Không đúng kho quản lý</div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
