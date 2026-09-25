@extends('layouts.site')

@section('title', 'Yêu cầu điều chỉnh đơn hàng')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Yêu cầu điều chỉnh đơn hàng</h2>
                    <p class="text-muted mb-0">Đơn hàng: <a href="{{ route('site.orders.show', $order) }}" class="fw-semibold">{{ $order->code ?: '#' . $order->id }}</a> - Khách hàng: {{ $order->customer?->name }}</p>
                </div>
                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại chi tiết đơn
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('site.order-adjustments.store', $order) }}" enctype="multipart/form-data">
                @csrf

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Sản phẩm cần điều chỉnh</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($order->items as $item)
                                <div class="list-group-item px-3 py-3">
                                    <input type="hidden" name="items[{{ $loop->index }}][order_item_id]" value="{{ $item->id }}">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $image = $item->variant?->mediaLink?->media ?? $item->variant?->product?->avatar?->media;
                                                @endphp
                                                @if($image)
                                                    <img src="{{ asset('storage/' . $image->file_path) }}" alt="{{ $item->variant->name }}" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $item->variant->product->name ?? 'Sản phẩm' }}</div>
                                                    <small class="text-muted">{{ $item->variant->name ?? 'Biến thể' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small text-muted">Số lượng</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Gốc: {{ (int)$item->quantity }}</span>
                                                <input type="number" name="items[{{ $loop->index }}][adjusted_quantity]" class="form-control" value="{{ old('items.'.$loop->index.'.adjusted_quantity', (int)$item->quantity) }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small text-muted">Đơn giá</label>
                                             <div class="input-group input-group-sm">
                                                <span class="input-group-text">Gốc: {{ number_format($item->price) }}</span>
                                                <input type="number" name="items[{{ $loop->index }}][adjusted_price]" class="form-control" value="{{ old('items.'.$loop->index.'.adjusted_price', (float)$item->price) }}" min="0" step="1000">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label small text-muted">Ghi chú điều chỉnh</label>
                                            <input type="text" name="items[{{ $loop->index }}][note]" class="form-control form-control-sm" value="{{ old('items.'.$loop->index.'.note') }}" placeholder="VD: Hàng lỗi, đổi size...">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Thông tin chung & Lý do</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="adjustment_note" class="form-label fw-semibold">Lý do điều chỉnh tổng thể <span class="text-danger">*</span></label>
                                    <textarea name="adjustment_note" id="adjustment_note" class="form-control" rows="4" placeholder="Mô tả chung về lý do cần điều chỉnh đơn hàng này">{{ old('adjustment_note') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="evidence_images" class="form-label fw-semibold">Hình ảnh minh chứng</label>
                                    <input type="file" name="evidence_images[]" id="evidence_images" class="form-control" multiple accept="image/*">
                                    <div class="form-text">Tải lên hình ảnh sản phẩm lỗi, tin nhắn với khách hàng, v.v. (tối đa 8 ảnh)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="return-warehouse-wrapper">
                                    <label for="return_warehouse_id" class="form-label fw-semibold">Kho nhận hàng trả lại</label>
                                    <select name="return_warehouse_id" id="return_warehouse_id" class="form-select">
                                        <option value="">-- Chọn kho --</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" @selected(old('return_warehouse_id') == $warehouse->id)>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-danger">Bắt buộc chọn nếu có giảm số lượng sản phẩm.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                            <i class="bi bi-save me-1"></i> Lưu nháp
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Gửi duyệt
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .input-group-sm .input-group-text {
        font-size: 0.75rem;
        background-color: #f8f9fa;
        border-right: 0;
        color: #6c757d;
    }
    .input-group-sm .form-control {
        border-left: 0;
        padding-left: 0.5rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.querySelector('.list-group');
    const returnWarehouseWrapper = document.getElementById('return-warehouse-wrapper');
    const returnWarehouseSelect = document.getElementById('return_warehouse_id');

    function checkRequiresReturn() {
        let requiresReturn = false;
        const quantityInputs = itemsContainer.querySelectorAll('input[name$="[adjusted_quantity]"]');

        quantityInputs.forEach(input => {
            const originalQuantityText = input.previousElementSibling.textContent;
            const originalQuantity = parseInt(originalQuantityText.replace('Gốc:', '').trim(), 10);
            const adjustedQuantity = parseInt(input.value, 10);

            if (!isNaN(originalQuantity) && !isNaN(adjustedQuantity) && adjustedQuantity < originalQuantity) {
                requiresReturn = true;
            }
        });

        if (returnWarehouseWrapper) {
            returnWarehouseWrapper.style.display = requiresReturn ? 'block' : 'none';
        }
        if (returnWarehouseSelect) {
            returnWarehouseSelect.required = requiresReturn;
        }
    }

    if (itemsContainer) {
        itemsContainer.addEventListener('input', function (e) {
            if (e.target && e.target.matches('input[name$="[adjusted_quantity]"]')) {
                checkRequiresReturn();
            }
        });
    }

    // Initial check on page load
    checkRequiresReturn();
});
</script>
@endsection
```

### Giải thích các cải tiến

1.  **Bố cục rõ ràng**:
    *   Sử dụng `card` để nhóm các phần thông tin (sản phẩm, lý do, hành động) một cách logic.
    *   Tiêu đề trang hiển thị rõ mã đơn hàng và tên khách hàng, giúp người dùng định vị nhanh chóng.

2.  **Hiển thị sản phẩm trực quan**:
    *   Mỗi sản phẩm được hiển thị trong một `list-group-item`, có hình ảnh, tên sản phẩm và tên biến thể.
    *   Số lượng và đơn giá được đặt cạnh nhau, có hiển thị giá trị "Gốc" để dễ dàng so sánh với giá trị "Điều chỉnh" mà người dùng nhập vào.
    *   Sử dụng `input-group` để giao diện nhập liệu trông gọn gàng và chuyên nghiệp hơn.

3.  **Cải thiện trải nghiệm người dùng (UX)**:
    *   Phần "Kho nhận hàng trả lại" được ẩn đi và chỉ hiện ra khi người dùng giảm số lượng của ít nhất một sản phẩm. Điều này giúp form gọn gàng hơn và tránh gây bối rối.
    *   Sử dụng JavaScript để tự động kiểm tra và hiển thị/ẩn trường "Kho nhận hàng" một cách linh hoạt.
    *   Các nút hành động "Lưu nháp" và "Gửi duyệt" được đặt ở cuối form, rõ ràng và dễ tiếp cận.

4.  **Responsive**: Giao diện được thiết kế để hiển thị tốt trên cả máy tính và thiết bị di động.

Để áp dụng thay đổi này, bạn chỉ cần tạo file `resources/views/site/orders/adjustments/create.blade.php` với nội dung trên. Controller `OrderAdjustmentController@create` sẽ tự động sử dụng file view này.

Hy vọng những cải tiến này sẽ giúp trải nghiệm người dùng trên trang của bạn tốt hơn!

<!--
[PROMPT_SUGGESTION]Tối ưu hóa query trong `OrderAdjustmentController` để tăng tốc độ tải trang[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Thêm chức năng tìm kiếm sản phẩm để thêm vào yêu cầu điều chỉnh[/PROMPT_SUGGESTION]
-->