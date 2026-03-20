@extends('layouts.shipper')

@section('title', 'Xác nhận đã giao hàng')
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
                    <div class="col-5 text-muted">COD cần thu:</div>
                    <div class="col-7 fw-bold text-success fs-6">{{ number_format($order->total) }}đ</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-check-circle me-1 text-success"></i>Xác nhận giao hàng thành công
            </div>
            <div class="card-body">
                <form action="{{ route('shipper.mark-delivered', $order) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền đã thu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₫</span>
                            <input type="number" name="collected_amount" class="form-control"
                                   value="{{ old('collected_amount', $order->total) }}"
                                   step="1000" min="0" required>
                        </div>
                        <div class="form-text text-muted">COD cần thu: {{ number_format($order->total) }}đ</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức thanh toán <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pm_cash" value="cash" checked>
                                <label class="form-check-label" for="pm_cash">
                                    <i class="bi bi-cash me-1"></i>Tiền mặt
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pm_transfer" value="transfer">
                                <label class="form-check-label" for="pm_transfer">
                                    <i class="bi bi-bank me-1"></i>Chuyển khoản
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh xác nhận giao hàng <span class="text-danger">*</span></label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*" required id="proofPreviewInput">
                        <div class="form-text text-muted">Ảnh chụp khi giao hàng (tối đa 5MB)</div>
                        <div id="proofPreview" class="mt-2 d-none">
                            <img id="proofPreviewImg" src="" class="img-fluid rounded" style="max-height:200px;">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('shipper.my-orders') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận đã giao hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('proofPreviewInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('proofPreviewImg').src = ev.target.result;
        document.getElementById('proofPreview').classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
