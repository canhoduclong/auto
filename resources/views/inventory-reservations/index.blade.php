@extends('layouts.app')

@section('title', 'Reservation hàng hóa')

@push('styles')
<style>
    .reservation-page { max-width: 1600px; }
    .reservation-panel { border: 1px solid #dbe4ee; border-radius: 14px; overflow: hidden; box-shadow: 0 5px 18px rgba(15, 23, 42, .06); }
    .reservation-table { min-width: 1280px; margin: 0; }
    .reservation-table th { padding: .75rem; border-bottom: 1px solid #dbe4ee; background: #f7f9fc; color: #64748b; font-size: .72rem; letter-spacing: .035em; text-transform: uppercase; white-space: nowrap; }
    .reservation-table td { padding: .85rem .75rem; border-color: #edf1f5; vertical-align: top; }
    .reservation-customer { color: #172033; font-size: 1.08rem; font-weight: 800; line-height: 1.25; }
    .reservation-order-code { margin-top: .2rem; color: #64748b; font-size: .72rem; font-weight: 400; line-height: 1.2; }
    .reservation-meta-label { color: #94a3b8; font-size: .66rem; font-weight: 700; letter-spacing: .025em; text-transform: uppercase; }
    .reservation-person, .reservation-product { color: #263449; font-size: .86rem; font-weight: 700; }
    .reservation-subtext { margin-top: .18rem; color: #64748b; font-size: .75rem; }
    .reservation-quantity { color: #0f766e; font-size: 1.05rem; font-weight: 800; white-space: nowrap; }
    .reservation-status { display: inline-block; padding: .35rem .58rem; border-radius: 999px; font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .reservation-status.is-success { background: #dcfce7; color: #166534; }
    .reservation-status.is-info { background: #dbeafe; color: #1d4ed8; }
    .reservation-status.is-warning { background: #fef3c7; color: #92400e; }
    .reservation-status.is-danger { background: #fee2e2; color: #b91c1c; }
    .reservation-status.is-secondary { background: #e2e8f0; color: #475569; }
    .reservation-recovery { min-width: 330px; }
    .reservation-recovery .form-control { min-width: 90px; }
</style>
@endpush

@section('content')
@php
    $formatReservationQuantity = static fn ($value): string => rtrim(
        rtrim(number_format((float) $value, 3, ',', '.'), '0'),
        ','
    );
@endphp
<div class="container-fluid reservation-page py-3">
    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Reservation hàng hóa</h1>
            <div class="text-muted small">Theo dõi hàng giữ chỗ theo khách hàng, sale, shipper và trạng thái đơn.</div>
        </div>
        <span class="badge bg-light text-dark border">{{ number_format($reservations->total(), 0, ',', '.') }} reservation</span>
    </div>

    <div class="reservation-panel bg-white">
        <div class="table-responsive">
            <table class="table reservation-table">
                <thead><tr>
                    <th style="width:260px">Đơn hàng của khách hàng</th>
                    <th style="width:145px">Sale phụ trách</th>
                    <th style="width:155px">Shipper đang giao</th>
                    <th style="width:130px">Trạng thái đơn</th>
                    <th style="width:220px">Sản phẩm giữ chỗ</th>
                    <th class="text-end" style="width:90px">Số lượng</th>
                    <th style="width:150px">Kho / Thời gian</th>
                    <th>Nhập cứu hộ</th>
                </tr></thead>
                <tbody>
                @forelse($reservations as $reservation)
                    @php
                        $order = $reservation->orderItem?->order;
                        $customer = $order?->customer;
                        $sale = $order?->user;
                        $shipper = $order?->shipper;
                        $status = (string) ($order?->status ?? '');
                        $statusLabel = $statusOptions[$status] ?? ($status !== '' ? $status : 'Không xác định');
                        $statusTone = match ($status) {
                            'delivered', 'completed' => 'success',
                            'delivering', 'in_delivery', 'shipping', 'picked_up' => 'info',
                            'cancelled', 'rejected', 'returned', 'returned_completed' => 'danger',
                            'packed', 'packed_waiting_pickup', 'packing', 'ready_to_pack' => 'warning',
                            default => 'secondary',
                        };
                        $variant = $reservation->orderItem?->variant;
                        $remainingRecovery = max(0, (float) $reservation->quantity - (float) ($reservation->recovered_quantity ?? 0));
                    @endphp
                    <tr>
                        <td>
                            <div class="reservation-customer">{{ $customer?->name ?? 'Khách hàng không xác định' }}</div>
                            <div class="reservation-order-code">{{ $order?->code ?? '—' }}</div>
                            <div class="reservation-subtext"><i class="bi bi-calendar3 me-1"></i>Ngày đơn: {{ $order?->created_at?->copy()->timezone(config('app.display_timezone'))->format('d/m/Y') ?? '—' }}</div>
                            @if($customer?->phone)<div class="reservation-subtext">{{ $customer->phone }}</div>@endif
                        </td>
                        <td><div class="reservation-meta-label">Sale</div><div class="reservation-person">{{ $sale?->short_name ?: ($sale?->name ?? 'Chưa xác định') }}</div></td>
                        <td>
                            <div class="reservation-meta-label">Shipper</div>
                            <div class="reservation-person">{{ $shipper?->short_name ?: ($shipper?->name ?? 'Chưa phân công') }}</div>
                            @if($shipper?->phone)<div class="reservation-subtext">{{ $shipper->phone }}</div>@endif
                        </td>
                        <td><span class="reservation-status is-{{ $statusTone }}">{{ $statusLabel }}</span></td>
                        <td>
                            <div class="reservation-product">{{ $variant?->product?->name ?? 'Sản phẩm' }}</div>
                            <div class="reservation-subtext">{{ $variant?->name ?? '—' }} @if($variant?->sku) · SKU {{ $variant->sku }} @endif</div>
                            <div class="reservation-subtext">Reservation #{{ $reservation->id }} · Item #{{ $reservation->order_item_id }}</div>
                        </td>
                        <td class="text-end"><span class="reservation-quantity">{{ $formatReservationQuantity($reservation->quantity) }}</span></td>
                        <td>
                            <div class="reservation-person">{{ $reservation->inventory?->warehouse?->name ?? 'Chưa xác định' }}</div>
                            <div class="reservation-subtext">{{ optional($reservation->reserved_at)->format('d/m/Y H:i') ?: '—' }}</div>
                        </td>
                        <td class="reservation-recovery">
                            @if(auth()->user()?->isAdmin() && $remainingRecovery > 0)
                                <form method="POST" action="{{ route('inventory-reservations.recovery-receipt.store', $reservation) }}" class="d-flex gap-2 align-items-start">
                                    @csrf
                                    <input type="number" name="quantity" class="form-control form-control-sm" min="0.001" step="0.001" max="{{ $remainingRecovery }}" value="{{ $remainingRecovery }}" required title="Số lượng nhập">
                                    <input type="text" name="reason" class="form-control form-control-sm" maxlength="500" placeholder="Lý do phát sinh" required>
                                    <button class="btn btn-sm btn-warning text-nowrap" onclick="return confirm('Lập phiếu nhập cứu hộ và tăng tồn kho? Reservation vẫn được giữ nguyên.');">Tạo phiếu nhập</button>
                                </form>
                                @if((float) ($reservation->recovered_quantity ?? 0) > 0)<div class="reservation-subtext">Đã nhập cứu hộ: {{ $formatReservationQuantity($reservation->recovered_quantity) }}</div>@endif
                            @else
                                <span class="text-muted small">Đã nhập {{ $formatReservationQuantity($reservation->recovered_quantity ?? 0) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Chưa có reservation hàng hóa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $reservations->links() }}</div>
</div>
@endsection
