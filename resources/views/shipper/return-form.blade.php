@extends('layouts.shipper')

@section('title', 'Trả hàng')
@section('subtitle', 'Đơn #{{ $order->code }}')

@section('content')
@php
    $customerAddress = $order->customer?->address
        ?? $order->customer?->addresses?->first()?->address
        ?? 'Chưa có địa chỉ';
    $deliveryTime = $order->delivery_time
        ?? $order->customer?->delivery_time
        ?? 'Chưa có khung giờ giao';
@endphp
<style>
    .shipper-return-shell .card {
        border-radius: 14px;
    }
    .shipper-meta-grid {
        display: grid;
        grid-template-columns: 132px 1fr;
        gap: 8px 10px;
        font-size: .92rem;
    }
    .shipper-meta-key {
        color: #64748b;
        font-weight: 600;
    }
    .shipper-customer-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        margin-top: 12px;
    }
    .shipper-customer-title {
        font-size: .82rem;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .shipper-warning-note {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #9f1239;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: .83rem;
        margin-bottom: 12px;
    }
</style>
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8 shipper-return-shell">
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <div class="fw-semibold mb-1">Không thể gửi trả hàng</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-1 text-primary"></i>Thông tin đơn hàng
            </div>
            <div class="card-body">
                <div class="shipper-meta-grid">
                    <div class="shipper-meta-key">Mã đơn:</div>
                    <div class="fw-semibold">{{ $order->code }}</div>
                    <div class="shipper-meta-key">Khách hàng:</div>
                    <div>{{ $order->customer?->name ?? '—' }}</div>
                    <div class="shipper-meta-key">Số điện thoại:</div>
                    <div>{{ $order->customer?->phone ?? '—' }}</div>
                    <div class="shipper-meta-key">Tổng tiền:</div>
                    <div class="fw-bold text-danger">{{ number_format($order->total) }}đ</div>
                </div>

                <div class="shipper-customer-box">
                    <div class="shipper-customer-title">Thông tin giao hàng</div>
                    <div class="small mb-2"><i class="bi bi-geo-alt me-1"></i><strong>Địa chỉ:</strong> {{ $customerAddress }}</div>
                    <div class="small"><i class="bi bi-clock me-1"></i><strong>Giờ giao:</strong> {{ $deliveryTime }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-start border-4 border-danger">
            <div class="card-header bg-white fw-semibold text-danger">
                <i class="bi bi-arrow-return-left me-1"></i>Gửi trả hàng về kho
            </div>
            <div class="card-body">
                <div class="shipper-warning-note">
                    <i class="bi bi-exclamation-octagon me-1"></i>Chỉ gửi trả khi đã xác minh lý do với khách hàng và có ảnh bằng chứng.
                </div>

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
