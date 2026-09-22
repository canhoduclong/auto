@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('inventory.titles.reservations') }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('inventory.labels.order_item_id') }}</th>
                <th>{{ __('inventory.labels.inventory_id') }}</th>
                <th>{{ __('inventory.labels.quantity') }}</th>
                <th>{{ __('inventory.labels.reserved_at') }}</th>
                <th>Đơn hàng</th>
                <th>Nhập cứu hộ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $reservation)
            <tr>
                <td>{{ $reservation->id }}</td>
                <td>{{ $reservation->order_item_id }}</td>
                <td>{{ $reservation->inventory_id }}</td>
                <td>{{ $reservation->quantity }}</td>
                <td>{{ $reservation->reserved_at }}</td>
                <td>#{{ $reservation->orderItem?->order?->code ?? '—' }}</td>
                <td style="min-width:320px">
                    @php($remainingRecovery = max(0, (float) $reservation->quantity - (float) ($reservation->recovered_quantity ?? 0)))
                    @if(auth()->user()?->isAdmin() && $remainingRecovery > 0)
                        <form method="POST" action="{{ route('inventory-reservations.recovery-receipt.store', $reservation) }}" class="d-flex gap-2 align-items-start">
                            @csrf
                            <input type="number" name="quantity" class="form-control form-control-sm" min="0.001" step="0.001" max="{{ $remainingRecovery }}" value="{{ $remainingRecovery }}" required title="Số lượng nhập">
                            <input type="text" name="reason" class="form-control form-control-sm" maxlength="500" placeholder="Lý do phát sinh" required>
                            <button class="btn btn-sm btn-warning text-nowrap" onclick="return confirm('Lập phiếu nhập cứu hộ và tăng tồn kho? Reservation vẫn được giữ nguyên.');">Tạo phiếu nhập</button>
                        </form>
                    @else
                        <span class="text-muted">Đã nhập {{ number_format((float) ($reservation->recovered_quantity ?? 0), 3, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $reservations->links() }}
</div>
@endsection
