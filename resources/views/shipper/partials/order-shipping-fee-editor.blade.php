<div class="shipping-fee-editor js-shipping-fee-editor"
    data-saved-fee="{{ (int) round($shippingFee) }}"
    data-save-url="{{ route('shipper.update-fee', $order) }}">
    <small class="shipping-fee-default">Phí mặc định: {{ number_format($defaultShippingFee ?? 0, 0, ',', '.') }} đ</small>
    <div class="input-group input-group-sm">
        <input type="text" inputmode="numeric" autocomplete="off"
            class="form-control form-control-sm trip-shipping-fee-input js-order-shipping-fee"
            value="{{ number_format($shippingFee, 0, '', '') }}"
            aria-label="Tiền ship đơn {{ $order->code ?: $order->id }}">
        <button type="button" class="btn btn-success js-save-order-shipping-fee" disabled>
            <i class="bi bi-check-lg me-1"></i><span>Đã lưu</span>
        </button>
    </div>
    <small class="shipping-fee-save-status js-shipping-fee-save-status text-success">Phí ship hiện tại</small>
</div>
