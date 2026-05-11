@extends(accounting_layout())

@section('title', $account ? 'Sửa Tài Khoản' : 'Thêm Tài Khoản')
@section('subtitle', $account ? 'Cập nhật thông tin tài khoản' : 'Tạo tài khoản tiền mặt hoặc ngân hàng')

@section('accounting_content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="acc-card">
            <div class="card-body">
                <div class="fw-bold mb-4 fs-6">
                    <i class="bi bi-wallet2 me-2 text-primary"></i>
                    {{ $account ? 'Cập nhật tài khoản: ' . $account->name : 'Thêm tài khoản mới' }}
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ $account ? accounting_route('accounts.update', $account) : accounting_route('accounts.store') }}"
                      method="POST">
                    @csrf
                    @if($account) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên tài khoản <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $account?->name) }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="cash" {{ old('type', $account?->type) === 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                            <option value="bank" {{ old('type', $account?->type) === 'bank' ? 'selected' : '' }}>Ngân hàng</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Chủ thể tài khoản <span class="text-danger">*</span></label>
                            <select name="owner_type" class="form-select @error('owner_type') is-invalid @enderror">
                                <option value="personal" {{ old('owner_type', $account?->owner_type ?? 'personal') === 'personal' ? 'selected' : '' }}>Cá nhân</option>
                                <option value="company" {{ old('owner_type', $account?->owner_type) === 'company' ? 'selected' : '' }}>Công ty</option>
                                <option value="business_household" {{ old('owner_type', $account?->owner_type) === 'business_household' ? 'selected' : '' }}>Hộ kinh doanh</option>
                                <option value="other" {{ old('owner_type', $account?->owner_type) === 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('owner_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tên chủ thể</label>
                            <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror"
                                   value="{{ old('owner_name', $account?->owner_name) }}" maxlength="150"
                                   placeholder="VD: Nguyễn Văn A / Công ty ABC">
                            @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Số tài khoản</label>
                            <input type="text" name="account_number" class="form-control"
                                   value="{{ old('account_number', $account?->account_number) }}" maxlength="50">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tên ngân hàng</label>
                            <input type="text" name="bank_name" class="form-control"
                                   value="{{ old('bank_name', $account?->bank_name) }}" maxlength="100">
                        </div>
                    </div>

                    @if(!$account)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số dư ban đầu (đ) <span class="text-danger">*</span></label>
                        <input type="number" name="balance" class="form-control @error('balance') is-invalid @enderror"
                               value="{{ old('balance', 0) }}" required min="0" step="1000">
                        @error('balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngưỡng cảnh báo (đ) <span class="text-danger">*</span></label>
                        <input type="number" name="warning_threshold" class="form-control @error('warning_threshold') is-invalid @enderror"
                               value="{{ old('warning_threshold', $account?->warning_threshold ?? 50000000) }}" required min="0" step="1000">
                        <div class="form-text">Hệ thống sẽ cảnh báo khi số dư xuống dưới mức này.</div>
                        @error('warning_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2" maxlength="500">{{ old('note', $account?->note) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                   {{ old('is_active', $account ? ($account->is_active ? '1' : '0') : '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Đang hoạt động</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i>{{ $account ? 'Cập nhật' : 'Tạo tài khoản' }}
                        </button>
                        <a href="{{ accounting_route('accounts.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
