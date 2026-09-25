<div class="row gy-2">
    <div class="col-md-6">
        <label class="form-label">Tên công ty</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $company->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Mã số thuế</label>
        <input type="text" name="mst" class="form-control" value="{{ old('mst', $company->mst ?? '') }}">
        @error('mst') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone ?? '') }}">
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $company->email ?? '') }}">
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Địa chỉ</label>
        <textarea name="address" class="form-control" rows="3">{{ old('address', $company->address ?? '') }}</textarea>
        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Ghi chú</label>
        <textarea name="note" class="form-control" rows="3">{{ old('note', $company->note ?? '') }}</textarea>
        @error('note') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>