@extends('layouts.site')

@push('styles')
<style>
    .schedule-edit-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .schedule-edit-shell { max-width: 1240px; }
    .table > :not(caption) > * > * { padding: .5rem .3rem; }
    .schedule-edit-hero {
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(15,23,42,.96), rgba(161,29,29,.82));
        color: #f8fafc;
        box-shadow: 0 18px 44px rgba(15,23,42,.14);
        padding: 22px 24px;
        margin-bottom: 18px;
    }
    .schedule-edit-panel {
        background: #fff;
        border: 1px solid rgba(148,163,184,.28);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15,23,42,.06);
    }
    .schedule-edit-panel-body { padding: 20px; }
    .schedule-edit-table { margin-bottom: 0; vertical-align: middle; }
    .schedule-edit-table thead th {
        background: #f8fafc;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #334155;
        white-space: nowrap;
    }
    .schedule-product { display: flex; align-items: center; gap: 10px; min-width: 240px; }
    .schedule-product img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(148,163,184,.25); background: #e2e8f0; }
    .schedule-product-meta { line-height: 1.2; }
    .schedule-product-title { font-size: .9rem; font-weight: 700; color: #0f172a; }
    .schedule-product-sub { font-size: .74rem; color: #64748b; }
    .schedule-summary { position: sticky; top: 20px; }
    .schedule-summary-grid { display: grid; gap: 10px; margin-bottom: 14px; }
    .schedule-kpi { padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(148,163,184,.28); background: #f8fafc; }
    .schedule-kpi-label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: 4px; }
    .schedule-kpi-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
    .empty-schedule-message { text-align: center; padding: 60px 20px; color: #555; }
    .empty-schedule-message i { font-size: 3rem; color: #ccc; }
    .form-control-sm { padding: .25rem .0rem .25rem .3rem !important; }
    .min50 { min-width: 50px !important; }
</style>
@endpush

@section('content')
<section class="schedule-edit-page">
    <div class="container schedule-edit-shell">

        <div class="schedule-edit-hero">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8;">Chỉnh sửa lịch #{{ $schedule->id }}</div>
            <h1 class="h4 mb-1 fw-bold">Sửa lịch lên đơn — {{ $schedule->customer->name }}</h1>
            <p class="mb-0" style="opacity:.85;">Thay đổi sản phẩm hoặc ngày lên đơn. Trạng thái sẽ được đặt lại về <strong>Pending</strong> sau khi lưu.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-3">
                <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('my_customer.schedules.update', $schedule) }}" method="POST" id="schedule-edit-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-lg-8">

                    {{-- Customer (readonly) --}}
                    <div class="schedule-edit-panel mb-3">
                        <div class="schedule-edit-panel-body">
                            <h2 class="h6 fw-bold mb-3">Khách hàng</h2>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tên khách</label>
                                    <input type="text" class="form-control" value="{{ $schedule->customer->name }}" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <input type="text" class="form-control" value="{{ $schedule->customer->phone }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Schedule date --}}
                    <div class="schedule-edit-panel mb-3">
                        <div class="schedule-edit-panel-body">
                            <h2 class="h6 fw-bold mb-3">Ngày lên đơn</h2>
                            <input type="date" name="schedule_date" class="form-control"
                                   value="{{ $schedule->schedule_date->format('Y-m-d') }}" required>
                            <div class="form-text">Thay đổi ngày sẽ đặt lại trạng thái lịch về Pending.</div>
                        </div>
                    </div>

                    {{-- Products --}}
                    <div class="schedule-edit-panel mb-3">
                        <div class="schedule-edit-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Sản phẩm trong lịch</h2>
                                <span class="text-muted small">Chỉnh sửa số lượng hoặc xoá bỏ sản phẩm.</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table schedule-edit-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">SKU</th>
                                            <th class="text-center">Giá hiện tại</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-center">Tổng cộng</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items-container">
                                        @foreach($schedule->items as $idx => $item)
                                            @php
                                                $v     = $item->variant;
                                                $price = (float) $item->scheduled_price;
                                                $img   = $v?->media?->first()?->url ?? asset('images/no-image.png');
                                            @endphp
                                            <tr class="cart-item-row" data-variant-id="{{ $item->product_variant_id }}">
                                                <td>
                                                    <div class="schedule-product">
                                                        <img src="{{ $img }}" alt="{{ $v?->name }}">
                                                        <div class="schedule-product-meta">
                                                            <div class="schedule-product-title">{{ $v?->product?->name ?? 'N/A' }}</div>
                                                            <div class="schedule-product-sub">{{ $v?->sku }}</div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="items[{{ $idx }}][variant_id]" value="{{ $item->product_variant_id }}">
                                                </td>
                                                <td class="text-center">{{ $v?->sku }}</td>
                                                <td class="text-center price" data-price="{{ $price }}">
                                                    {{ number_format($price, 0, ',', '.') }}đ
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" name="items[{{ $idx }}][quantity]"
                                                           class="form-control form-control-sm quantity-input min50"
                                                           min="1" value="{{ $item->quantity }}" required>
                                                </td>
                                                <td class="text-center row-total">
                                                    {{ number_format($price * $item->quantity, 0, ',', '.') }}đ
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div id="empty-cart-message" class="empty-schedule-message" style="{{ $schedule->items->isNotEmpty() ? 'display:none' : '' }}">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0 mt-2">Không có sản phẩm. Hãy thêm từ panel bên dưới.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Products --}}
                    <div class="schedule-edit-panel mb-3">
                        <div class="schedule-edit-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tìm và thêm sản phẩm</h2>
                            <div class="input-group">
                                <input type="text" id="variant-search" class="form-control" placeholder="Nhập SKU hoặc tên sản phẩm">
                                <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
                            </div>
                            <div id="variant-search-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="col-lg-4">
                    <div class="schedule-edit-panel schedule-summary">
                        <div class="schedule-edit-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tóm tắt</h2>
                            <div class="schedule-summary-grid">
                                <div class="schedule-kpi">
                                    <span class="schedule-kpi-label">Tạm tính</span>
                                    <span class="schedule-kpi-value" id="summarySubtotal">0đ</span>
                                </div>
                                <div class="schedule-kpi">
                                    <span class="schedule-kpi-label">Số dòng sản phẩm</span>
                                    <span class="schedule-kpi-value" id="summaryLineCount">0</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-4">
                                <a href="{{ route('my_customer.schedules.index') }}" class="btn btn-outline-secondary w-50">Huỷ</a>
                                <button type="submit" class="btn btn-warning w-50 fw-bold">
                                    <i class="bi bi-save me-1"></i>Lưu thay đổi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cartContainer       = document.getElementById('cart-items-container');
    const emptyCartMessage    = document.getElementById('empty-cart-message');
    const variantSearchInput  = document.getElementById('variant-search');
    const variantSearchButton = document.getElementById('variant-search-button');
    const variantSearchResults = document.getElementById('variant-search-results');
    const summarySubtotal     = document.getElementById('summarySubtotal');
    const summaryLineCount    = document.getElementById('summaryLineCount');

    let itemIndex = {{ $schedule->items->count() }};
    let searchTimeout = null;

    function formatMoney(n) {
        return Number(n).toLocaleString('vi-VN') + 'đ';
    }
    function toNumber(v, fb = 0) { const p = Number(v); return Number.isFinite(p) ? p : fb; }

    function getCartVariantIds() {
        return Array.from(cartContainer.querySelectorAll('.cart-item-row'))
            .map(r => r.getAttribute('data-variant-id')).filter(Boolean);
    }

    function updateNameIndexes() {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row, idx) => {
            row.querySelectorAll('input[name^="items["]').forEach(input => {
                const m = input.name.match(/^items\[\d+\]\[(.+)\]$/);
                if (m) input.name = `items[${idx}][${m[1]}]`;
            });
        });
    }

    function updateCartVisibility() {
        const has = cartContainer.querySelectorAll('.cart-item-row').length > 0;
        emptyCartMessage.style.display = has ? 'none' : 'block';
    }

    function updateCartTotal() {
        let subtotal = 0;
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach(row => {
            const priceEl  = row.querySelector('.price');
            const qtyInput = row.querySelector('.quantity-input');
            const totalEl  = row.querySelector('.row-total');
            const price    = toNumber(priceEl?.getAttribute('data-price'), 0);
            const qty      = Math.max(1, parseInt(qtyInput?.value || '0', 10));
            const line     = price * qty;
            if (totalEl) totalEl.textContent = formatMoney(line);
            subtotal += line;
        });
        const count = cartContainer.querySelectorAll('.cart-item-row').length;
        summarySubtotal.textContent  = formatMoney(subtotal);
        summaryLineCount.textContent = count;
    }

    function performVariantSearch(page = 1) {
        const term = (variantSearchInput.value || '').trim();
        if (term.length < 2) { variantSearchResults.innerHTML = ''; return; }
        variantSearchResults.innerHTML = '<div class="text-center text-muted py-3">Đang tải...</div>';
        fetch(`{{ route('orders.ajax_variant_search') }}?search=${encodeURIComponent(term)}&page=${page}&exclude_ids=${getCartVariantIds().join(',')}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(p => { variantSearchResults.innerHTML = p.html || ''; })
        .catch(() => { variantSearchResults.innerHTML = '<div class="text-danger text-center py-3">Lỗi tải dữ liệu.</div>'; });
    }

    variantSearchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performVariantSearch(1), 300);
    });
    variantSearchButton.addEventListener('click', () => performVariantSearch(1));

    variantSearchResults.addEventListener('click', function (e) {
        const btn = e.target.closest('.add-variant-to-cart');
        if (!btn) return;
        e.preventDefault();

        const variantId = String(btn.dataset.variantId || '');
        if (!variantId || cartContainer.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`)) {
            alert('Biến thể này đã có trong lịch.'); return;
        }

        const name   = btn.dataset.variantName  || 'N/A';
        const sku    = btn.dataset.variantSku    || 'N/A';
        const price  = parseFloat(btn.dataset.variantPrice || '0');
        const stock  = parseInt(btn.dataset.variantStock || '0', 10);
        const img    = btn.dataset.variantImage  || '{{ asset("images/no-image.png") }}';

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.setAttribute('data-variant-id', variantId);
        row.innerHTML = `
            <td>
                <div class="schedule-product">
                    <img src="${img}" alt="${name}">
                    <div class="schedule-product-meta">
                        <div class="schedule-product-title">${name}</div>
                        <div class="schedule-product-sub">${sku}</div>
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
            </td>
            <td class="text-center">${sku}</td>
            <td class="text-center price" data-price="${price}">${formatMoney(price)}</td>
            <td class="text-center">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input min50"
                       min="1" ${stock > 0 ? `max="${stock}"` : ''} value="1" required>
            </td>
            <td class="text-center row-total">${formatMoney(price)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
            </td>`;
        cartContainer.appendChild(row);
        itemIndex++;
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();
        performVariantSearch(1);
    });

    cartContainer.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-cart-item');
        if (!btn) return;
        btn.closest('.cart-item-row')?.remove();
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();
    });

    cartContainer.addEventListener('input', function (e) {
        if (e.target.classList.contains('quantity-input')) {
            const v = parseInt(e.target.value || '1', 10);
            if (isNaN(v) || v < 1) e.target.value = '1';
            updateCartTotal();
        }
    });

    // init totals
    updateCartTotal();
    updateCartVisibility();
});
</script>
@endpush
