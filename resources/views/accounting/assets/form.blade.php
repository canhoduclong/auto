@extends(accounting_layout())

@php
    $entity = ['assets' => 'tài sản', 'partners' => 'đối tác', 'contracts' => 'hợp đồng'][$kind];
    $title = ($row ? 'Cập nhật ' : 'Thêm ').$entity;
    $routeArgs = ['kind' => $kind] + ($row ? ['record' => $record] : []);
@endphp

@section('title', $title)

@section('accounting_content')
@include('accounting.assets.feedback')

<div class="card rounded-3">
    <div class="card-body">
        <a class="small" href="{{ $row && $kind === 'contracts' ? accounting_route('assets.contracts.show', ['contract' => $record]) : accounting_route('assets.index', ['tab' => $kind]) }}">← Quay lại</a>
        <h2 class="h5 my-3">{{ $title }}</h2>
        <form method="POST" action="{{ accounting_route($row ? 'assets.update' : 'assets.store', $routeArgs) }}" class="row g-3" enctype="multipart/form-data">
            @csrf
            @if($row) @method('PATCH') @endif
            @if($kind === 'contracts')
                <label class="col-md-6"><span class="form-label">Số hợp đồng</span>
                    <input class="form-control" name="number" value="{{ old('number', $row->number ?? '') }}" maxlength="100" required>
                </label>
                <label class="col-md-6"><span class="form-label">Tên hợp đồng</span>
                    <input class="form-control" name="title" value="{{ old('title', $row->title ?? '') }}" maxlength="255" required>
                </label>
                <label class="col-md-6"><span class="form-label">Đối tác</span>
                    <select class="form-select" name="partner_id" required>
                        <option value="">Chọn đối tác</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((int) old('partner_id', $row->partner_id ?? 0) === $partner->id)>{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="col-md-6 d-flex align-items-end">
                    <a class="btn btn-outline-primary" href="{{ accounting_route('assets.create', ['kind' => 'partners']) }}">Thêm đối tác</a>
                </div>
                <label class="col-sm-6 col-lg-3"><span class="form-label">Ngày ký</span>
                    <input class="form-control" type="date" name="signed_on" value="{{ old('signed_on', $row->signed_on ?? today()->toDateString()) }}" required>
                </label>
                <label class="col-sm-6 col-lg-3"><span class="form-label">Ngày hết hạn</span>
                    <input class="form-control" type="date" name="expires_on" value="{{ old('expires_on', $row->expires_on ?? '') }}">
                </label>
                <label class="col-sm-6 col-lg-3"><span class="form-label">Giá trị hợp đồng (đ)</span>
                    <input class="form-control" type="number" name="value" min="0" max="999999999999999" value="{{ old('value', $row->value ?? '') }}" required>
                </label>
            @else
                <label class="col-md-6"><span class="form-label">{{ $kind === 'assets' ? 'Tên tài sản' : 'Tên đối tác' }}</span>
                    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" maxlength="255" required>
                </label>
                @if($kind === 'partners')
                    <label class="col-md-6"><span class="form-label">Mã số thuế</span>
                        <input class="form-control" name="tax_code" value="{{ old('tax_code', $row->tax_code ?? '') }}" maxlength="40">
                    </label>
                    <label class="col-md-6"><span class="form-label">Điện thoại</span>
                        <input class="form-control" name="phone" type="tel" value="{{ old('phone', $row->phone ?? '') }}" maxlength="40">
                    </label>
                    <label class="col-md-6"><span class="form-label">Email</span>
                        <input class="form-control" name="email" type="email" value="{{ old('email', $row->email ?? '') }}" maxlength="255">
                    </label>
                    <label class="col-12"><span class="form-label">Địa chỉ</span>
                        <input class="form-control" name="address" value="{{ old('address', $row->address ?? '') }}" maxlength="500">
                    </label>
                @else
                    <label class="col-md-6"><span class="form-label">Mã tài sản</span>
                        <input class="form-control" name="code" value="{{ old('code', $row->code ?? '') }}" maxlength="100" required>
                    </label>
                    <label class="col-md-6"><span class="form-label">Nhóm quản lý</span>
                        <select class="form-select" name="category">
                            <option value="">Chọn nhóm</option>
                            @foreach(\App\Http\Controllers\CompanyAssetController::CATEGORIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('category', $row->category ?? null) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="col-md-6"><span class="form-label">Giá trị ghi nhận (đ)</span>
                        <input class="form-control" type="number" name="value" min="0" max="999999999999999" value="{{ old('value', $row->value ?? '') }}">
                    </label>
                    <label class="col-md-6"><span class="form-label">Vị trí</span>
                        <input class="form-control" name="location" value="{{ old('location', $row->location ?? '') }}" maxlength="255">
                    </label>
                    <label class="col-md-6"><span class="form-label">Hợp đồng</span>
                        <select class="form-select" name="contract_id">
                            <option value="">Chưa gắn hợp đồng</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}" @selected((int) old('contract_id', $row->contract_id ?? request('contract_id')) === $contract->id)>{{ $contract->number }} · {{ $contract->title }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            @endif
            @if($kind !== 'partners')
                @php($statuses = $kind === 'contracts' ? \App\Http\Controllers\CompanyAssetController::CONTRACT_STATUSES : \App\Http\Controllers\CompanyAssetController::ASSET_STATUSES)
                <label class="col-sm-6 col-lg-3"><span class="form-label">Trạng thái</span>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $row->status ?? array_key_first($statuses)) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="col-12"><span class="form-label">Ghi chú</span>
                <textarea name="note" class="form-control" rows="3" maxlength="4000">{{ old('note', $row->note ?? '') }}</textarea>
            </label>
            @if(!$row && $kind !== 'partners')
                <div class="col-12">
                    <details class="border rounded p-3" @if(old('document_type') || $errors->has('files') || $errors->has('files.*')) open @endif>
                        <summary class="fw-semibold">Đính kèm chứng từ khi tạo</summary>
                        <div class="mt-3">@include('accounting.assets.document-fields', ['required' => false])</div>
                    </details>
                </div>
            @endif
            <div class="col-12"><button class="btn btn-primary">Lưu {{ $entity }}</button></div>
        </form>
    </div>
</div>
@endsection
