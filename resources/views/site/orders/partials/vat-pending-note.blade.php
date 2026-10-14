@if(!(bool) $order->charge_vat || (float) ($vatNoteAmount ?? $order->vat_amount ?? 0) <= 0)
    <div class="monitor-vat-pending-note" style="font-size:12px;line-height:1.5;color:#334155;overflow-wrap:anywhere">Ghi chú: Giá chưa bao gồm VAT (chờ kế toán xử lý)</div>
@endif
