@extends('layouts.accounting')
@section('title', 'Sửa dòng doanh số')
@section('subtitle', 'Điều chỉnh dữ liệu doanh số lịch sử')
@section('accounting_content')
<div class="container py-4"><div class="card border-0 shadow-sm mx-auto" style="max-width:900px"><div class="card-header bg-white py-3"><h4 class="mb-0">Sửa dòng doanh số #{{ $entry->id }}</h4></div><div class="card-body">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if($entry->order_id)<div class="alert alert-info">Dòng này đã thuộc đơn lịch sử #{{ $entry->order_id }}. Ngày, khách hàng và NVKD được khóa theo nhóm đơn; bạn chỉ có thể sửa DVT, số lượng và giá trị.</div>@endif
    <form method="POST" action="{{ route('accounting.sales-ledger.update',$entry) }}">@csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Ngày</label><input type="date" class="form-control" name="entry_date" value="{{ old('entry_date',$entry->entry_date->toDateString()) }}" @readonly($entry->order_id) required></div>
            <div class="col-md-4"><label class="form-label">Mã KH</label><input class="form-control" name="customer_code" value="{{ old('customer_code',$entry->customer_code) }}" @readonly($entry->order_id)></div>
            <div class="col-md-4"><label class="form-label">NVKD</label><select class="form-select" name="sale_id" @disabled($entry->order_id) required>@foreach($salesUsers as $sale)<option value="{{ $sale->id }}" @selected((int)old('sale_id',$entry->sale_id)===(int)$sale->id)>{{ $sale->name }}</option>@endforeach</select>@if($entry->order_id)<input type="hidden" name="sale_id" value="{{ $entry->sale_id }}">@endif</div>
            <div class="col-md-8"><label class="form-label">Khách hàng</label><input class="form-control" name="customer_name" value="{{ old('customer_name',$entry->customer_name) }}" @readonly($entry->order_id) required></div>
            <div class="col-md-4"><label class="form-label">DVT</label><input class="form-control" name="unit" value="{{ old('unit',$entry->unit) }}" required></div>
            <div class="col-md-4"><label class="form-label">SL</label><input type="number" step="0.001" class="form-control" name="quantity" value="{{ old('quantity',$entry->quantity) }}" required></div>
            <div class="col-md-4"><label class="form-label">Kg/con</label><input type="number" step="0.001" class="form-control" name="unit_weight" value="{{ old('unit_weight',$entry->unit_weight) }}" required></div>
            <div class="col-md-4"><label class="form-label">Tổng</label><input type="number" step="0.001" class="form-control" name="total_quantity" value="{{ old('total_quantity',$entry->total_quantity) }}" required></div>
            <div class="col-md-6"><label class="form-label">Đơn giá</label><input type="number" step="0.01" class="form-control" name="unit_price" value="{{ old('unit_price',$entry->unit_price) }}"></div>
            <div class="col-md-6"><label class="form-label">Tổng tiền</label><input type="number" step="0.01" class="form-control" name="total_amount" value="{{ old('total_amount',$entry->total_amount) }}" required></div>
        </div><div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Lưu thay đổi</button><a class="btn btn-outline-secondary" href="{{ route('accounting.sales-ledger.index') }}">Hủy</a></div>
    </form>
</div></div></div>
@endsection
