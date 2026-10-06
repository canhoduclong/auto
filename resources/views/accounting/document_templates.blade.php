@extends('layouts.accounting')
@section('title', 'Mẫu chứng từ Thu / Chi')
@section('accounting_content')
<div class="container-fluid py-3">
<h4>Quản lý mẫu chứng từ Thu / Chi</h4>
<p>Mỗi mẫu quy định bố cục và dòng tiền. Thu là tiền vào; Chi là tiền ra. Mẫu Thu / Chi cho phép chọn dòng tiền khi tạo phiếu. Đề nghị thanh toán và tạm ứng luôn là Chi.</p>
<p>Ngừng sử dụng để ẩn mẫu khỏi phiếu mới. Thay đổi mẫu không thay đổi các phiếu đã tạo. <a href="{{ route('accounting.transaction-categories.index') }}">Quản lý danh mục thu / chi</a></p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@php($formTypes = ['cash_request'=>'Phiếu thu / chi', 'payment_proposal'=>'Đề nghị thanh toán', 'advance_request'=>'Đề nghị tạm ứng'])
@foreach($templates->concat([null]) as $template)
<form method="POST" action="{{ $template ? route('accounting.document-templates.update', $template) : route('accounting.document-templates.store') }}" class="card card-body mb-3">
@csrf
@if($template) @method('PUT') @endif
<h6>{{ $template ? 'Mẫu #'.$template->id : '+ Thêm mẫu chứng từ' }}</h6>
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Tên mẫu</label><input name="name" class="form-control" required maxlength="255" value="{{ $template?->name }}"></div>
<div class="col-md-3"><label class="form-label">Bố cục chứng từ</label><select name="form_type" class="form-select">@foreach($formTypes as $value=>$label)<option value="{{ $value }}" @selected($template?->form_type === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Dòng tiền</label><select name="flow_direction" class="form-select">@foreach(['out'=>'Chi — tiền ra','in'=>'Thu — tiền vào','both'=>'Thu / Chi'] as $value=>$label)<option value="{{ $value }}" @selected($template?->flow_direction === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1" @selected(!$template || $template->is_active)>Đang sử dụng</option><option value="0" @selected($template && !$template->is_active)>Ngừng sử dụng</option></select></div>
<div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary">Lưu</button></div>
</div>
</form>
@endforeach
</div>
@endsection
