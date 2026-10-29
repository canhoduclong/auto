@php($context = $customer->card_context ?? [])
<div class="small border-top pt-2 mt-2 text-muted">
    @if($isFreeCustomer)<div>Sale trước đó: <strong>{{ $context['previous_sale'] ?? 'Chưa có thông tin' }}</strong></div>@endif
    <div>Đơn gần nhất: <strong>{{ $context['last_order_date'] ?? 'Chưa có đơn hợp lệ' }}</strong></div>
    <div>Lần lấy hàng gần nhất: <strong>{{ $context['last_received_date'] ?? 'Chưa ghi nhận giao thành công' }}</strong>@if(isset($context['days_without_order'])) · <strong>{{ $context['days_without_order'] }} ngày</strong> chưa lấy hàng @endif</div>
    <div>Phản hồi đơn hàng ({{ $context['feedback_total'] ?? 0 }}): {{ ($context['feedback_total'] ?? 0) > 0 ? $context['feedback_counts'] : 'Chưa có phản hồi' }}</div>
    @if(!empty($context['latest_feedback']))<div class="text-break">Phản hồi gần nhất ({{ $context['latest_feedback_date'] ?? '—' }}): {{ $context['latest_feedback'] }}</div>@endif
</div>
