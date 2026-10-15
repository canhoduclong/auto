@if($order->assignment_location)
    <div class="small mt-1 text-break" role="status">
        <span class="fw-semibold text-dark">Vị trí / trạng thái:</span>
        <span class="text-primary">{{ $order->assignment_location }}</span>
    </div>
@endif
