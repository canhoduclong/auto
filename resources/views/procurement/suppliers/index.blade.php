@extends('layouts.procurement')
@section('title','Nhà cung cấp hàng sơ chế')
@section('subtitle','Quản lý nguồn hàng sơ chế, sản phẩm và công nợ')
@section('content')
@include('procurement.suppliers.filter')
<div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Danh sách nhà cung cấp</strong><button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newSupplier">Thêm nhà cung cấp</button></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nhà cung cấp</th><th>Liên hệ</th><th>Số lần mua trong kỳ</th><th class="text-end">Tổng tiền mua trong kỳ</th><th class="text-end">Công nợ hiện tại (tất cả)</th></tr></thead><tbody>
@forelse($suppliers as $supplier)
<tr><td><a href="{{ route('procurement.suppliers.show', [$supplier, 'period' => $period, 'from_date' => $from, 'to_date' => $to]) }}" class="fw-bold">{{ $supplier->name }}</a><div class="small {{ $supplier->is_active ? 'text-success' : 'text-muted' }}">{{ $supplier->is_active ? 'Đang hợp tác' : 'Ngừng hợp tác' }}</div></td><td>{{ $supplier->phone ?: '—' }}<div class="small text-muted">{{ $supplier->address }}</div></td><td>{{ number_format($supplier->period_count) }}</td><td class="text-end">{{ number_format($supplier->period_total ?? 0) }}đ</td><td class="text-end text-danger">{{ number_format($supplier->current_debt ?? 0) }}đ</td></tr>
@empty<tr><td colspan="5" class="text-center py-4">Không có nhà cung cấp phù hợp.</td></tr>@endforelse
</tbody></table></div><div class="card-footer">{{ $suppliers->links() }}</div></div>
<div id="newSupplier" class="collapse {{ $errors->any() ? 'show' : '' }}"><div class="card card-body"><h5>Thêm nhà cung cấp</h5><form method="POST" action="{{ route('procurement.suppliers.store') }}">@include('procurement.suppliers.form', ['supplier' => null])</form></div></div>
@endsection
