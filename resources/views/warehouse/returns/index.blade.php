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
                    <th class="text-center">Chi tiết</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $i => $order)
                @php
                    $returnWarehouseName = $order->resolved_return_warehouse_name;
                    $returnWarehouseId = $order->resolved_return_warehouse_id;
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
                        @if($returnWarehouseName)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                {{ $returnWarehouseName }}
                            </span>
                        @else
                            <span class="text-muted small">Chưa xác định</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $order->shipper_note ?? '—' }}</td>
                    <td class="small">{{ $order->items->sum('quantity') }} sp</td>
                    <td class="fw-semibold">{{ number_format($order->total) }}đ</td>
                    <td class="text-muted small">{{ $order->updated_at->format('d/m H:i') }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-info btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#detail-modal-{{ $order->id }}"
                                title="Xem chi tiết sản phẩm cần nhận">
                            <i class="bi bi-box-seam me-1"></i>Xem hàng
                        </button>
                    </td>
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

{{-- Modals chi tiết đơn trả hàng --}}
@foreach($orders as $order)
<div class="modal fade" id="detail-modal-{{ $order->id }}" tabindex="-1" aria-labelledby="detail-modal-label-{{ $order->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title" id="detail-modal-label-{{ $order->id }}">
                    <i class="bi bi-box-seam me-2"></i>
                    Chi tiết đơn trả — <strong>#{{ $order->code }}</strong>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Thông tin chung --}}
                <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap gap-3 small">
                    <div>
                        <span class="text-muted">Khách hàng:</span>
                        <strong>{{ $order->customer?->name ?? '—' }}</strong>
                        @if($order->customer?->phone)
                            <span class="text-muted ms-1">({{ $order->customer->phone }})</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-muted">Shipper:</span>
                        <strong>{{ $order->shipper?->name ?? '—' }}</strong>
                    </div>
                    @php
                        $reasons = [
                            'customer_refused' => 'Khách từ chối',
                            'no_contact'       => 'Không liên lạc được',
                            'wrong_address'    => 'Sai địa chỉ',
                            'damaged'          => 'Hàng bị hỏng',
                        ];
                        $returnOrderWarehouseName = $order->resolved_return_warehouse_name;
                    @endphp
                    <div>
                        <span class="text-muted">Lý do trả:</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                            {{ $reasons[$order->return_reason] ?? $order->return_reason ?? '—' }}
                        </span>
                    </div>
                    @if($returnOrderWarehouseName)
                    <div>
                        <span class="text-muted">Kho nhận:</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                            {{ $returnOrderWarehouseName }}
                        </span>
                    </div>
                    @endif
                    @if($order->shipper_note)
                    <div>
                        <span class="text-muted">Ghi chú shipper:</span>
                        <em>{{ $order->shipper_note }}</em>
                    </div>
                    @endif
                </div>

                {{-- Danh sách sản phẩm cần nhận --}}
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Sản phẩm</th>
                                <th>SKU</th>
                                <th class="text-center">SL cần nhận</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $idx => $item)
                            <tr>
                                <td class="text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item->product?->name ?? '—' }}</div>
                                    @if($item->variant && $item->variant->name)
                                        <div class="text-muted" style="font-size:.75rem;">{{ $item->variant->name }}</div>
                                    @endif
                                </td>
                                <td class="small text-muted font-monospace">{{ $item->variant?->sku ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-1">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="text-end small">{{ number_format($item->price) }}đ</td>
                                <td class="text-end fw-semibold">{{ number_format($item->total) }}đ</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Không có sản phẩm</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Tổng cộng</th>
                                <th class="text-center text-warning">{{ $order->items->sum('quantity') }} sp</th>
                                <th></th>
                                <th class="text-end text-danger">{{ number_format($order->total) }}đ</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
