@extends('layouts.accounting')

@section('title', 'Sửa thông tin khách hàng')
@section('subtitle', $customer->name)

@section('accounting_content')
<form method="POST" action="{{ route('accounting.customers.update-profile', $customer) }}" class="card">
    @csrf
    @method('PATCH')
    <div class="card-body">
        <div class="row g-3 mb-4">
            <h2 class="h6 mb-0">Thông tin liên hệ</h2>
            @foreach(['name' => 'Tên khách hàng', 'phone' => 'Số điện thoại', 'email' => 'Email'] as $key => $label)
                <label class="col-md-4">
                    <span class="form-label">{{ $label }}</span>
                    <input name="{{ $key }}" type="{{ ['phone' => 'tel', 'email' => 'email'][$key] ?? 'text' }}"
                        maxlength="{{ $key === 'phone' ? 30 : 255 }}" value="{{ old($key, $customer->{$key}) }}" class="form-control" @required($key === 'name')>
                    @error($key)<span class="text-danger small">{{ $message }}</span>@enderror
                </label>
            @endforeach
            <label class="col-12">
                <span class="form-label">Địa chỉ mặc định</span>
                <textarea name="address" maxlength="1000" rows="2" class="form-control">{{ old('address', $address?->note ?? $customer->address) }}</textarea>
                @error('address')<span class="text-danger small">{{ $message }}</span>@enderror
            </label>
            <label class="col-md-6">
                <span class="form-label">Tỉnh/Thành phố</span>
                <select name="province_id" id="accounting-customer-province" class="form-select">
                    <option value="">Chưa chọn</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) $provinceId === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
                @error('province_id')<span class="text-danger small">{{ $message }}</span>@enderror
            </label>
            <label class="col-md-6">
                <span class="form-label">Phường/Xã</span>
                <select name="ward_id" id="accounting-customer-ward" class="form-select">
                    <option value="">Chưa chọn</option>
                    @foreach($wards->get($provinceId, []) as $ward)
                        <option value="{{ $ward->id }}" @selected((string) old('ward_id', $address?->ward_id) === (string) $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
                @error('ward_id')<span class="text-danger small">{{ $message }}</span>@enderror
            </label>
        </div>
        <div class="row g-3">
            <h2 class="h6 mb-0">Thông tin xuất hóa đơn</h2>
            @foreach(['company_name' => 'Tên công ty', 'tax_code' => 'Mã số thuế', 'company_address' => 'Địa chỉ xuất hóa đơn', 'company_email' => 'Email nhận hóa đơn'] as $key => $label)
                <label class="col-md-6">
                    <span class="form-label">{{ $label }}</span>
                    <input name="{{ $key }}" type="{{ $key === 'company_email' ? 'email' : 'text' }}"
                        maxlength="{{ $key === 'tax_code' ? 50 : 255 }}" value="{{ old($key, $customer->{$key}) }}" class="form-control">
                    @error($key)<span class="text-danger small">{{ $message }}</span>@enderror
                </label>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary" type="submit">Lưu thông tin</button>
        <a href="{{ route('accounting.customers-list') }}" class="btn btn-outline-secondary">Về danh sách</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (() => {
        const province = document.getElementById('accounting-customer-province');
        const ward = document.getElementById('accounting-customer-ward');
        const wards = {{ Illuminate\Support\Js::from($wards) }};
        province.addEventListener('change', () => {
            ward.replaceChildren(new Option('Chưa chọn', ''));
            for (const item of wards[province.value] ?? []) {
                ward.add(new Option(item.name, item.id));
            }
        });
    })();
</script>
@endpush
