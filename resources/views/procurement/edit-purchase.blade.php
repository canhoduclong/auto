@extends('layouts.procurement')
@section('title', 'Sửa nhật ký thu mua')
@section('content')
<a href="{{ route('procurement.purchases.index') }}" class="btn btn-outline-secondary mb-3">Quay lại nhật ký</a>
@include('procurement.partials.purchase_form')
@endsection
