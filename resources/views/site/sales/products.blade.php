@extends('layouts.site')

@push('styles')
<style>
    .sale-products-page {
        background: #f6f8fb;
        padding: 30px 0 48px;
    }
    .sale-products-shell {
        max-width: 1180px;
    }
    .sale-products-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }
    .sale-products-title {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 900;
        color: #0f172a;
    }
    .sale-products-subtitle {
        margin: 4px 0 0;
        color: #64748b;
    }
    .sale-products-toolbar {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }
    .sale-products-table-wrap {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    }
    .sale-products-table {
        margin: 0;
        vertical-align: middle;
    }
    .sale-products-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .76rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sale-product-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 280px;
    }
    .sale-product-thumb {
        width: 52px;
        height: 52px;
        border-radius: 8px;
        object-fit: cover;
        background: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, .35);
        flex: 0 0 auto;
    }
    .sale-product-name {
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
    }
    .sale-product-meta {
        color: #64748b;
        font-size: .82rem;
    }
    .sale-products-pin {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 800;
        background: #f1f5f9;
        color: #475569;
    }
    .sale-products-pin.active {
        background: #fef3c7;
        color: #92400e;
    }
    .sale-products-sort-input {
        width: 90px;
        text-align: center;
        font-weight: 800;
    }
    .sale-products-status {
        min-width: 150px;
        color: #64748b;
        font-size: .84rem;
    }
    @media (max-width: 768px) {
        .sale-products-head {
            align-items: flex-start;
            flex-direction: column;
        }
        .sale-products-title {
            font-size: 1.28rem;
        }
    }
</style>
@endpush

@section('content')
<section class="sale-products-page">
    <div class="container sale-products-shell">
        <div class="sale-products-head">
            <div>
                <h1 class="sale-products-title">Sản phẩm hiển thị khi lên đơn</h1>
                <p class="sale-products-subtitle">Ghim sản phẩm hoặc đặt thứ tự riêng. Thứ tự này được áp dụng trong màn thêm sản phẩm của đơn hàng.</p>
            </div>
            <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Đơn hàng
            </a>
        </div>

        <form method="GET" action="{{ route('pages.my_products') }}" class="sale-products-toolbar">
            <div class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label fw-semibold">Tìm sản phẩm</label>
                    <input type="search" name="q" value="{{ $keyword }}" class="form-control" placeholder="Tên sản phẩm, SKU, size">
                </div>
                <div class="col-sm-4 col-lg-3">
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select name="pinned" class="form-select">
                        <option value="all" {{ $pinned === 'all' ? 'selected' : '' }}>Tất cả</option>
                        <option value="yes" {{ $pinned === 'yes' ? 'selected' : '' }}>Đã ghim</option>
                        <option value="no" {{ $pinned === 'no' ? 'selected' : '' }}>Chưa ghim</option>
                    </select>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <label class="form-label fw-semibold">Mỗi trang</label>
                    <select name="per_page" class="form-select">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-lg-2 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search me-1"></i> Lọc
                    </button>
                </div>
            </div>
        </form>

        <div class="sale-products-table-wrap">
            <div class="table-responsive">
                <table class="table sale-products-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Tồn</th>
                            <th>Ghim</th>
                            <th>Thứ tự riêng</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($variants as $variant)
                            @php
                                $imageUrl = 'https://via.placeholder.com/80';
                                if ($variant->media) {
                                    $imageUrl = asset('storage/' . $variant->media->file_path);
                                } elseif ($variant->product?->avatar?->media) {
                                    $imageUrl = asset('storage/' . $variant->product->avatar->media->file_path);
                                }
                                $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                                $isPinned = (bool) ($variant->is_pinned ?? false);
                            @endphp
                            <tr data-variant-row="{{ $variant->id }}">
                                <td>
                                    <div class="sale-product-cell">
                                        <img src="{{ $imageUrl }}" alt="{{ $variant->product?->name ?? $variant->sku }}" class="sale-product-thumb">
                                        <div>
                                            <div class="sale-product-name">{{ $variant->product?->name ?? 'Sản phẩm' }}</div>
                                            <div class="sale-product-meta">
                                                SKU {{ $variant->sku ?: '--' }}
                                                @if($variant->size)
                                                    · Size {{ $variant->size }}
                                                @endif
                                                @if($variant->name)
                                                    · {{ $variant->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold">{{ number_format($price, 0, ',', '.') }}đ</td>
                                <td>{{ number_format((float) ($variant->available_stock ?? 0), 0, ',', '.') }}</td>
                                <td>
                                    <span class="sale-products-pin {{ $isPinned ? 'active' : '' }}" data-pin-label>
                                        <i class="bi {{ $isPinned ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        {{ $isPinned ? 'Đã ghim' : 'Chưa ghim' }}
                                    </span>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="999999"
                                        class="form-control form-control-sm sale-products-sort-input"
                                        value="{{ $variant->user_sort_order ?? '' }}"
                                        placeholder="-"
                                        data-sort-input>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm {{ $isPinned ? 'btn-warning' : 'btn-outline-warning' }}"
                                            data-pin-button
                                            data-url="{{ route('pages.my_products.preference', $variant) }}"
                                            data-pinned="{{ $isPinned ? '1' : '0' }}">
                                            <i class="bi {{ $isPinned ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            data-save-sort
                                            data-url="{{ route('pages.my_products.preference', $variant) }}">
                                            Lưu
                                        </button>
                                    </div>
                                    <div class="sale-products-status mt-1" data-row-status></div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Không có sản phẩm phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($variants->hasPages())
            <div class="mt-3">
                {{ $variants->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('click', async function (event) {
    const pinButton = event.target.closest('[data-pin-button]');
    const saveButton = event.target.closest('[data-save-sort]');
    if (!pinButton && !saveButton) {
        return;
    }

    const button = pinButton || saveButton;
    const row = button.closest('[data-variant-row]');
    const status = row?.querySelector('[data-row-status]');
    const sortInput = row?.querySelector('[data-sort-input]');
    const pinLabel = row?.querySelector('[data-pin-label]');
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const payload = {};

    if (pinButton) {
        payload.is_pinned = pinButton.dataset.pinned !== '1';
    }
    if (saveButton) {
        const raw = sortInput?.value;
        payload.sort_order = raw === '' ? null : Math.max(0, parseInt(raw || '0', 10));
    }

    button.disabled = true;
    if (status) status.textContent = 'Đang lưu...';

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify(payload),
        });
        if (!response.ok) {
            throw new Error('save_failed');
        }
        const data = await response.json();
        if (pinButton) {
            const pinned = data.is_pinned === true;
            pinButton.dataset.pinned = pinned ? '1' : '0';
            pinButton.classList.toggle('btn-warning', pinned);
            pinButton.classList.toggle('btn-outline-warning', !pinned);
            pinButton.innerHTML = `<i class="bi ${pinned ? 'bi-star-fill' : 'bi-star'}"></i>`;
            if (pinLabel) {
                pinLabel.classList.toggle('active', pinned);
                pinLabel.innerHTML = `<i class="bi ${pinned ? 'bi-star-fill' : 'bi-star'}"></i> ${pinned ? 'Đã ghim' : 'Chưa ghim'}`;
            }
        }
        if (saveButton && sortInput) {
            sortInput.value = data.sort_order ?? '';
        }
        if (status) status.textContent = 'Đã lưu';
    } catch (error) {
        if (status) status.textContent = 'Không lưu được';
    } finally {
        button.disabled = false;
    }
});
</script>
@endpush
