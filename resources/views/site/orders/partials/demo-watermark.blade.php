@if(config('demo.order_watermark'))
    <span class="order-demo-watermark" aria-label="Đơn hàng đang ở giai đoạn thử nghiệm">DRAFT</span>
@once
<style>
.order-demo-watermark{position:absolute;top:9px;right:12px;z-index:0;color:#0f766e;font-size:30px;font-weight:900;line-height:1;letter-spacing:.12em;opacity:.09;pointer-events:none;user-select:none;transform:rotate(-7deg)}
@media(max-width:575.98px){.order-demo-watermark{top:8px;right:8px;font-size:24px}}
@media print{.order-demo-watermark{opacity:.11!important}}
</style>
@endonce
@endif
