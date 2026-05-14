@php
    $customer = $order->customer;
    $address = $order->recipient_address ?: $customer?->address;
    $totalItems = $order->items->sum('quantity');
    $deliveryTime = $order->delivery_time ?: $customer?->delivery_time ?: 'Chưa cập nhật';
    $customerName = $customer?->name ?? $order->recipient_name;
@endphp
<div class="col-12">
    <div class="card ma-order-card p-2" style="min-height:unset; position: relative;">
        @if(($showAssignmentButtons ?? false) === false && $order->shipper_id)
            <form action="{{ route('shipper.unassign-order', [$order->id]) }}" method="POST" class="d-inline-block" style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Gỡ ra khỏi danh sách" onclick="return confirm('Bạn có chắc chắn muốn gỡ đơn này ra?')" style="font-size: 1.3rem; text-decoration: none;">
                    <i class="bi bi-x-circle"></i>
                </button>
            </form>
        @endif
        <div class="row align-items-stretch g-0">
            <div class="col-3 d-flex flex-column justify-content-center align-items-center border-end" style="min-width:90px;">
                <div class="fw-bold text-primary" style="font-size:1.1rem;">{{ $deliveryTime }}</div>
            </div>
            <div class="col-9 ps-3">
                <div class="fw-semibold text-dark">{{ $customerName }}</div>
                <div class="text-muted small mb-1">{{ $address ? mb_substr($address, 0, 60) . (mb_strlen($address) > 60 ? '...' : '') : 'Chưa cập nhật' }}</div>
                <div class="mt-1">
                    @foreach($order->items as $item)
                        <div class="small text-dark">
                            <span class="fw-semibold">{{ $item->variant?->name ?? $item->product_name }}</span>
                            @if($item->variant?->size)
                                <span class="badge bg-light text-dark border ms-1">Size: {{ $item->variant->size }}</span>
                            @endif
                            <span class="badge bg-warning text-dark ms-1">x{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if(($showAssignmentButtons ?? false) === true)
            <div class="mt-2">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.03em;">Gán cho:</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($shippers as $shipper)
                        <form action="{{ route('shipper.assign-order', [$order->id, $shipper->id]) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm ma-shipper-btn btn-outline-primary" title="Gán cho {{ $shipper->name }}">
                                {{ mb_substr($shipper->name, 0, 12) }}
                            </button>
                        </form>
                    @endforeach
                    @php $user = auth()->user(); @endphp
                    @if($user && ($user->hasRole('manager_shipper') || $user->hasRole('admin')))
                        <form action="{{ route('shipper.assign-order', [$order->id, $user->id]) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm ma-shipper-btn btn-outline-danger" title="Gán cho tôi ({{ $user->name }})">
                                Gán cho tôi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            @if(($showAssignmentButtons ?? false) === false && $order->shipper_id)
                {{-- X button moved to top right corner --}}
            @endif
        @endif
    </div>
</div>