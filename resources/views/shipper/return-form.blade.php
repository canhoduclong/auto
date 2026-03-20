@extends('layouts.shipper')

@section('title', 'Trả hàng')
@section('subtitle', 'Đơn #{{ $order->code }}')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-1 text-primary"></i>Thông tin đơn hàng
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-5 text-muted">Mã đơn:</div>
                    <div class="col-7 fw-semibold">{{ $order->code }}</div>
                    <div class="col-5 text-muted">Khách hàng:</div>
                    <div class="col-7">{{ $order->customer?->name ?? '—' }}</div>
                    <div class="col-5 text-muted">SĐT:</div>
                    <div class="col-7">{{ $order->customer?->phone ?? '—' }}</div>
                    <div class="col-5 text-muted">Tổng tiền:</div>
                    <div class="col-7 fw-bold text-danger">{{ number_format($order->total) }}đ</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-start border-4 border-danger">
            <div class="card-header bg-white fw-semibold text-danger">
                <i class="bi bi-arrow-return-left me-1"></i>Gửi trả hàng về kho
            </div>
            <div class="card-body">
                <form action="{{ route('shipper.store-return', $order) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do trả hàng <span class="text-danger">*</span></label>
                        <select name="return_reason" class="form-select" required>
                            <option value="">-- Chọn lý do --</option>
                            <option value="customer_refused" {{ old('return_reason') === 'customer_refused' ? 'selected' : '' }}>
                                Khách từ chối nhận hàng
                            </option>
                            <option value="no_contact" {{ old('return_reason') === 'no_contact' ? 'selected' : '' }}>
                                Không liên lạc được với khách
                            </option>
                            <option value="wrong_address" {{ old('return_reason') === 'wrong_address' ? 'selected' : '' }}>
                                Sai địa chỉ giao hàng
                            </option>
                            <option value="damaged" {{ old('return_reason') === 'damaged' ? 'selected' : '' }}>
                                Hàng bị hỏng / không đủ điều kiện giao
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú thêm</label>
                        <textarea name="return_note" class="form-control" rows="2"
                                  placeholder="Mô tả thêm tình huống nếu cần...">{{ old('return_note') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh chứng minh <span class="text-danger">*</span></label>
                        <input type="file" name="return_image" class="form-control" accept="image/*" required id="returnPreviewInput">
                        <div class="form-text text-muted">Ảnh chụp hàng hóa / tình huống (tối đa 5MB)</div>
                        <div id="returnPreview" class="mt-2 d-none">
                            <img id="returnPreviewImg" src="" class="img-fluid rounded" style="max-height:200px;">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('shipper.my-orders') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-danger flex-fill"
                                onclick="return confirm('Xác nhận trả hàng đơn #{{ $order->code }}?')">
                            <i class="bi bi-arrow-return-left me-1"></i>Xác nhận trả hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('returnPreviewInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('returnPreviewImg').src = ev.target.result;
        document.getElementById('returnPreview').classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
