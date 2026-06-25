@extends('layouts.site')

@php
    use App\Models\Media;
    use App\Models\Setting;

    $adjustmentBrandName = Setting::get('brand_name', config('app.name', 'Hoàng Long TNT'));
    $adjustmentSlogan = Setting::get('slogan', 'Chất lượng tạo niềm tin');
    $adjustmentLogoId = Setting::get('logo');
    $adjustmentLogo = $adjustmentLogoId ? Media::find($adjustmentLogoId) : null;
@endphp

@push('styles')
<style>
    .adjustment-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }
    .adjustment-brand-logo {
        width: 54px;
        height: 54px;
        border-radius: 12px;
        object-fit: contain;
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .35);
        padding: 5px;
        flex: 0 0 auto;
    }
    .adjustment-brand-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        border-radius: 12px;
        background: #0f766e;
        color: #fff;
        font-weight: 900;
        flex: 0 0 auto;
    }
    .adjustment-brand-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1.2;
    }
    .adjustment-brand-slogan {
        color: #64748b;
        font-size: .9rem;
        font-weight: 600;
    }
    @media (max-width: 768px) {
        .adjustment-brand-title {
            font-size: 1.1rem;
        }
        .adjustment-brand-logo,
        .adjustment-brand-fallback {
            width: 46px;
            height: 46px;
        }
    }
</style>
@endpush

@section('content')
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="adjustment-brand">
                @if($adjustmentLogo)
                    <img src="{{ asset('storage/' . $adjustmentLogo->file_path) }}" alt="{{ $adjustmentBrandName }}" class="adjustment-brand-logo">
                @else
                    <span class="adjustment-brand-fallback">{{ mb_substr($adjustmentBrandName, 0, 2) }}</span>
                @endif
                <div>
                    <div class="adjustment-brand-slogan">{{ $adjustmentSlogan }}</div>
                    <h3 class="adjustment-brand-title">Yêu cầu điều chỉnh đơn hàng</h3>
                    <div class="text-muted">Đơn gốc: {{ $order->code ?: ('#' . $order->id) }} - {{ $order->customer?->name ?? '-' }}</div>
                </div>
            </div>
            <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">Quay lại chi tiết đơn</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('site.order-adjustments.store', $order) }}" method="POST" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Ghi chu dieu chinh</label>
                    <textarea name="adjustment_note" class="form-control" rows="3" placeholder="Mo ta ly do dieu chinh, thay doi gia/so luong/can ky...">{{ old('adjustment_note') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Hinh anh minh chung (toi da 8 anh)</label>
                    <input type="file" name="evidence_images[]" class="form-control" accept="image/*" multiple>
                </div>

                <div class="mb-3" id="returnWarehouseWrap" style="display:none;">
                    <label class="form-label">Kho tra hang (bat buoc khi giam so luong)</label>
                    <select name="return_warehouse_id" id="return_warehouse_id" class="form-select">
                        <option value="">-- Chon kho --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) old('return_warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>San pham</th>
                                <th>So luong goc</th>
                                <th>So luong dieu chinh</th>
                                <th>Gia goc</th>
                                <th>Gia dieu chinh</th>
                                <th>Can ky goc (kg)</th>
                                <th>Can ky dieu chinh (kg)</th>
                                <th>Ghi chu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $idx => $item)
                                @php
                                    $variant = $item->variant;
                                    $productName = $variant?->product?->name ?? 'San pham';
                                    $variantName = $variant?->name ?? ('Variant #' . $item->product_variant_id);
                                    $originalWeight = (float) ($item->actual_weight ?? $item->total_weight ?? $item->display_total_value ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $productName }}</div>
                                        <div class="text-muted small">{{ $variantName }} @if($variant?->sku) ({{ $variant->sku }}) @endif</div>
                                        <input type="hidden" name="items[{{ $idx }}][order_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>{{ (int) ($item->quantity ?? 0) }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            name="items[{{ $idx }}][adjusted_quantity]"
                                            class="form-control adjustment-qty"
                                            data-original-qty="{{ (int) ($item->quantity ?? 0) }}"
                                            value="{{ old('items.' . $idx . '.adjusted_quantity', (int) ($item->quantity ?? 0)) }}"
                                            required
                                        >
                                    </td>
                                    <td>{{ number_format((float) ($item->price ?? 0), 0, ',', '.') }} đ</td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            name="items[{{ $idx }}][adjusted_price]"
                                            class="form-control"
                                            value="{{ old('items.' . $idx . '.adjusted_price', (float) ($item->price ?? 0)) }}"
                                            required
                                        >
                                    </td>
                                    <td>{{ rtrim(rtrim(number_format($originalWeight, 3, '.', ''), '0'), '.') }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            name="items[{{ $idx }}][adjusted_weight]"
                                            class="form-control"
                                            value="{{ old('items.' . $idx . '.adjusted_weight', $originalWeight) }}"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="items[{{ $idx }}][note]"
                                            class="form-control"
                                            value="{{ old('items.' . $idx . '.note') }}"
                                            placeholder="Ghi chu dong dieu chinh"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
                <button type="submit" class="btn btn-outline-secondary" name="action" value="draft">Luu nhap</button>
                <button type="submit" class="btn btn-primary" name="action" value="submit">Gui yeu cau duyet</button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const qtyInputs = Array.from(document.querySelectorAll('.adjustment-qty'));
    const wrap = document.getElementById('returnWarehouseWrap');

    const refresh = () => {
        const requiresReturn = qtyInputs.some((input) => {
            const originalQty = Number(input.dataset.originalQty || 0);
            const adjustedQty = Number(input.value || 0);
            return adjustedQty < originalQty;
        });

        if (wrap) {
            wrap.style.display = requiresReturn ? 'block' : 'none';
        }
    };

    qtyInputs.forEach((input) => input.addEventListener('input', refresh));
    refresh();
})();
</script>
@endpush
@endsection
