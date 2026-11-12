@if($alert['balance'] > 0)
<div class="d-flex flex-wrap gap-1 mb-1">
    @if($alert['overdue'])<span class="badge bg-danger">Trễ thanh toán {{ $alert['overdue_days'] }} ngày</span>@endif
    @if($alert['remind'])<span class="badge bg-warning text-dark">Nhắc thu tiền</span>@endif
    @if($alert['risk'])<span class="badge {{ $alert['risk'] === 'medium' ? 'bg-warning text-dark' : ($alert['risk'] === 'high' ? 'bg-danger' : 'bg-dark') }}">Rủi ro {{ ['medium'=>'Trung bình','high'=>'Cao','very_high'=>'Rất cao'][$alert['risk']] }}</span>@endif
</div>
@if($compact ?? false)
<div class="small text-muted" title="{{ $alert['unpaid_orders'] }} Bill còn nợ">Tuổi nợ {{ $alert['age_days'] }} ngày @if($alert['due_date']) · Hạn {{ \Carbon\Carbon::parse($alert['due_date'])->format('d/m/Y') }}@endif</div>
@else
<div class="small">{{ $alert['unpaid_orders'] }} Bill còn nợ · Tuổi nợ {{ $alert['age_days'] }} ngày</div>
@if($alert['due_date'])<div class="small text-muted">Đến hạn: {{ \Carbon\Carbon::parse($alert['due_date'])->format('d/m/Y') }}</div>@endif
@endif
@if(($compact ?? false) && count($alert['reasons']))<details class="debt-alert-explanation"><summary>Chi tiết cảnh báo</summary><div class="small">{{ $alert['unpaid_orders'] }} Bill còn nợ</div>@endif
@foreach($alert['reasons'] as $reason)<div class="small text-muted">{{ $reason }}</div>@endforeach
@if(($compact ?? false) && count($alert['reasons']))</details>@endif
@else
<span class="small text-success">Không còn công nợ phải thu</span>
@endif
