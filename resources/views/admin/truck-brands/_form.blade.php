<div class="card" style="max-width:680px;">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Tên nhà xe <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $brand?->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $brand?->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $brand?->email) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mô tả</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $brand?->description) }}</textarea>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $brand?->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Đang hoạt động</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ph-floppy-disk me-1"></i> Lưu</button>
                <a href="{{ route('admin.truck-brands.index') }}" class="btn btn-light border">Hủy</a>
            </div>
        </form>
    </div>
</div>
