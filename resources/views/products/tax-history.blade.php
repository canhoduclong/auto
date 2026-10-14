@extends($prefix === 'ceo' ? 'layouts.ceo' : 'layouts.accounting')
@section('title','Lịch sử thuế suất VAT')
@section('accounting_content')
<div class="card my-3"><div class="card-body"><a href="{{ route($prefix.'.tax-settings.products') }}" class="btn btn-outline-secondary mb-3">← Danh sách sản phẩm</a><h4>{{ $product->name }}</h4><div class="d-flex flex-wrap gap-3 mb-3">@foreach(\App\Models\ProductTaxRate::ENTITY_COLUMNS as $entity=>$column)<span>{{ \App\Models\ProductTaxRate::ENTITY_TYPES[$entity] }}: <strong>{{ $product->$column === null ? 'Chưa thiết lập' : $product->$column.'%' }}</strong></span>@endforeach</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@php $approved=$rates->where('status','approved')->sortBy('effective_from')->values(); @endphp
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nhóm pháp nhân</th><th>Thuế suất VAT</th><th>Thời gian áp dụng</th><th>Trạng thái</th><th>Người lưu / thời điểm</th><th>Người áp dụng / thời điểm</th><th>Ghi chú</th><th></th></tr></thead><tbody>
@forelse($rates as $rate)
@php
 $next=$approved->first(fn($entry)=>$entry->entity_type===$rate->entity_type && $entry->effective_from->gt($rate->effective_from));
 $isCurrent=$rate->status==='approved' && $rate->effective_from->lte(today()) && (!$next || $next->effective_from->gt(today()));
@endphp
<tr><td>{{ \App\Models\ProductTaxRate::ENTITY_TYPES[$rate->entity_type] ?? 'Khác' }}</td><td class="fw-bold">{{ $rate->tax_percent === null ? 'Chưa thiết lập' : $rate->tax_percent.'%' }}</td><td>{{ $rate->effective_from->format('d/m/Y') }} → {{ $rate->status==='approved' ? ($next ? $next->effective_from->copy()->subDay()->format('d/m/Y') : 'Đến khi có thay đổi') : 'Chưa áp dụng' }}</td><td>{{ $rate->status==='draft' ? 'Bản nháp' : ($isCurrent ? 'Đang áp dụng' : ($rate->effective_from->isFuture() ? 'Chờ ngày hiệu lực' : 'Đã hết hiệu lực')) }}</td><td>{{ $rate->creator?->name ?? 'Hệ thống' }}<br><small>{{ $rate->created_at->format('d/m/Y H:i') }}</small></td><td>{{ $rate->approver?->name ?? '—' }}<br><small>{{ $rate->approved_at?->format('d/m/Y H:i') }}</small></td><td>{{ $rate->note }}</td><td>@if($rate->status==='draft')<form method="POST" action="{{ route($prefix.'.tax-settings.products.apply',[$product,$rate]) }}">@csrf<button class="btn btn-success btn-sm">Áp dụng</button></form>@endif</td></tr>
@empty<tr><td colspan="8">Chưa có lịch sử thuế suất.</td></tr>@endforelse
</tbody></table></div><p class="small text-muted">Lịch sử được ghi từ khi bật chức năng này. Không suy diễn thuế suất của các thời điểm trước mốc cấu hình ban đầu; các đơn hàng cũ giữ nguyên.</p></div></div>
@endsection
