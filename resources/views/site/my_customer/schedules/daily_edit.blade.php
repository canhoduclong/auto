@extends('layouts.site')

@push('styles')
<style>
    .daily-rule-edit-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .daily-rule-edit-shell { max-width: 1240px; }
    .table > :not(caption) > * > * { padding: .5rem .3rem; }
    .daily-rule-hero {
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(15,23,42,.96), rgba(15,118,110,.86));
        color: #f8fafc;
        box-shadow: 0 18px 44px rgba(15,23,42,.14);
        padding: 22px 24px;
        margin-bottom: 18px;
    }
    .daily-rule-panel {
        background: #fff;
        border: 1px solid rgba(148,163,184,.28);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15,23,42,.06);
    }
    .daily-rule-panel-body { padding: 20px; }
    .daily-rule-table { margin-bottom: 0; vertical-align: middle; }
    .daily-rule-table thead th {
        background: #f8fafc;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #334155;
        white-space: nowrap;
    }
    .daily-product { display: flex; align-items: center; gap: 10px; min-width: 240px; }
    .daily-product img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(148,163,184,.25); background: #e2e8f0; }
    .daily-product-meta { line-height: 1.2; }
    .daily-product-title { font-size: .9rem; font-weight: 700; color: #0f172a; }
    .daily-product-sub { font-size: .74rem; color: #64748b; }
    .daily-summary { position: sticky; top: 20px; }
    .daily-summary-grid { display: grid; gap: 10px; margin-bottom: 14px; }
    .daily-kpi { padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(148,163,184,.28); background: #f8fafc; }
    .daily-kpi-label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: 4px; }
    .daily-kpi-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
    .empty-message { text-align: center; padding: 60px 20px; color: #555; }
    .empty-message i { font-size: 3rem; color: #ccc; }
    .min50 { min-width: 50px !important; }
</style>
@endpush

@section('content')
<section class="daily-rule-edit-page">
    <div class="container daily-rule-edit-shell">

        <div class="daily-rule-hero">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8;">Sửa cấu hình đơn tự động #{{ $dailySchedule->id }}</div>
            <h1 class="h4 mb-1 fw-bold">Lên đơn tự động hàng ngày — {{ $dailySchedule->customer->name }}</h1>
            <p class="mb-0" style="opacity:.85;">Cập nhật trạng thái hoạt động, chế độ duyệt, và danh sách sản phẩm của cấu hình hằng ngày.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger border-0 rounded-3 mb-3">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-3">
                <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('my_customer.daily_schedules.update', $dailySchedule) }}" method="POST" id="daily-rule-edit-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-lg-8">

                    <div class="daily-rule-panel mb-3">
                        <div class="daily-rule-panel-body">
                            <h2 class="h6 fw-bold mb-3">Thông tin cấu hình</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold mb-1">Khách hàng</label>
                                    <input type="text" class="form-control" value="{{ $dailySchedule->customer->name }}" readonly>
                                    <div class="form-text">{{ $dailySchedule->customer->phone ?? 'Không có số điện thoại' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold mb-1">Ngày bắt đầu</label>
                                    <input type="text" class="form-control" value="{{ optional($dailySchedule->start_date)->format('d/m/Y') }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is-active-input" name="is_active" value="1" {{ old('is_active', $dailySchedule->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="is-active-input">Đang bật cấu hình</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="approval-required-input" name="approval_required" value="1" {{ old('approval_required', $dailySchedule->approval_required ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="approval-required-input">Sale duyệt trước khi tạo đơn</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="daily-rule-panel mb-3">
                        <div class="daily-rule-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Sản phẩm áp dụng hàng ngày</h2>
                                <span class="text-muted small">Cập nhật số lượng hoặc xóa sản phẩm khỏi cấu hình.</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table daily-rule-table">
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
                                        @foreach($dailySchedule->items as $idx => $item)
                                            @php
                                                $v = $item->variant;
                                                $price = (float) $item->scheduled_price;
                                                $img = $v?->media?->first()?->url ?? asset('images/no-image.png');
                                            @endphp
                                            <tr class="cart-item-row" data-variant-id="{{ $item->product_variant_id }}">
                                                <td>
                                                    <div class="daily-product">
                                                        <img src="{{ $img }}" alt="{{ $v?->name }}">
                                                        <div class="daily-product-meta">
                                                            <div class="daily-product-title">{{ $v?->product?->name ?? 'N/A' }}</div>
                                                            <div class="daily-product-sub">{{ $v?->sku }}</div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="items[{{ $idx }}][variant_id]" value="{{ $item->product_variant_id }}">
                                                </td>
                                                <td class="text-center">{{ $v?->sku }}</td>
                                                <td class="text-center price" data-price="{{ $price }}">{{ number_format($price, 0, ',', '.') }}đ</td>
                                                <td class="text-center">
                                                    <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm quantity-input min50" min="1" value="{{ $item->quantity }}" required>
                                                </td>
                                                <td class="text-center row-total">{{ number_format($price * $item->quantity, 0, ',', '.') }}đ</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div id="empty-cart-message" class="empty-message" style="{{ $dailySchedule->items->isNotEmpty() ? 'display:none' : '' }}">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0 mt-2">Không có sản phẩm. Hãy thêm từ phần tìm kiếm phía dưới.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="daily-rule-panel mb-3">
                        <div class="daily-rule-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tìm và thêm sản phẩm</h2>
                            <div class="input-group">
                                <input type="text" id="variant-search" class="form-control" placeholder="Nhập SKU hoặc tên sản phẩm">
                                <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
                            </div>
                            <div id="variant-search-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="daily-rule-panel daily-summary">
                        <div class="daily-rule-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tóm tắt</h2>
                            <div class="daily-summary-grid">
                                <div class="daily-kpi">
                                    <span class="daily-kpi-label">Tạm tính</span>
                                    <span class="daily-kpi-value" id="summarySubtotal">0đ</span>
                                </div>
                                <div class="daily-kpi">
                                    <span class="daily-kpi-label">Số dòng sản phẩm</span>
                                    <span class="daily-kpi-value" id="summaryLineCount">0</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-4">
                                <a href="{{ route('my_customer.schedules.index') }}" class="btn btn-outline-secondary w-50">Hủy</a>
                                <button type="submit" class="btn btn-primary w-50 fw-bold">
                                    <i class="bi bi-save me-1"></i>Lưu cấu hình
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
    const cartContainer = document.getElementById('cart-items-container');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const variantSearchInput = document.getElementById('variant-search');
    const variantSearchButton = document.getElementById('variant-search-button');
    const variantSearchResults = document.getElementById('variant-search-results');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryLineCount = document.getElementById('summaryLineCount');

    let itemIndex = {{ $dailySchedule->items->count() }};
    let searchTimeout = null;
    let variantPerPage = 5;

    function formatMoney(n) {
        return Number(n).toLocaleString('vi-VN') + 'đ';
    }

    function toNumber(v, fallback = 0) {
        const p = Number(v);
        return Number.isFinite(p) ? p : fallback;
    }

    function getCartVariantIds() {
        return Array.from(cartContainer.querySelectorAll('.cart-item-row'))
            .map(row => row.getAttribute('data-variant-id'))
            .filter(Boolean);
    }

    function updateNameIndexes() {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row, idx) => {
            row.querySelectorAll('input[name^="items["]').forEach(input => {
                const match = input.name.match(/^items\[\d+\]\[(.+)\]$/);
                if (match) {
                    input.name = `items[${idx}][${match[1]}]`;
                }
            });
        });
    }

    function updateCartVisibility() {
        const hasRows = cartContainer.querySelectorAll('.cart-item-row').length > 0;
        emptyCartMessage.style.display = hasRows ? 'none' : 'block';
    }

    function updateCartTotal() {
        let subtotal = 0;

        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach(row => {
            const priceEl = row.querySelector('.price');
            const qtyInput = row.querySelector('.quantity-input');
            const rowTotalEl = row.querySelector('.row-total');
            const price = toNumber(priceEl?.getAttribute('data-price'), 0);
            const quantity = Math.max(1, parseInt(qtyInput?.value || '0', 10));
            const lineTotal = price * quantity;

            if (rowTotalEl) {
                rowTotalEl.textContent = formatMoney(lineTotal);
            }

            subtotal += lineTotal;
        });

        summarySubtotal.textContent = formatMoney(subtotal);
        summaryLineCount.textContent = cartContainer.querySelectorAll('.cart-item-row').length;
    }

    function performVariantSearch(page = 1) {
        const term = (variantSearchInput.value || '').trim();
        if (term.length < 2) {
            variantSearchResults.innerHTML = '';
            return;
        }

        variantSearchResults.innerHTML = '<div class="text-center text-muted py-3">Đang tải...</div>';

        const params = new URLSearchParams({
            search: term,
            page: String(page),
            per_page: String(variantPerPage),
        });
        getCartVariantIds().forEach(id => params.append('exclude_ids[]', id));

        fetch(`{{ route('orders.ajax_variant_search') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(payload => {
                variantSearchResults.innerHTML = payload.html || '';
            })
            .catch(() => {
                variantSearchResults.innerHTML = '<div class="text-danger text-center py-3">Lỗi tải dữ liệu.</div>';
            });
    }

    variantSearchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performVariantSearch(1), 300);
    });

    variantSearchButton.addEventListener('click', function () {
        performVariantSearch(1);
    });

    variantSearchResults.addEventListener('click', function (event) {
        const pageLink = event.target.closest('.pagination a, a.page-link');
        if (pageLink) {
            event.preventDefault();
            const url = new URL(pageLink.href, window.location.origin);
            const page = parseInt(url.searchParams.get('page') || '1', 10);
            performVariantSearch(Number.isNaN(page) ? 1 : page);
            return;
        }

        const addBtn = event.target.closest('.add-variant-to-cart');
        if (!addBtn) {
            return;
        }

        event.preventDefault();

        const variantId = String(addBtn.dataset.variantId || '');
        if (!variantId || cartContainer.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`)) {
            alert('Biến thể này đã có trong cấu hình.');
            return;
        }

        const variantName = addBtn.dataset.variantName || 'N/A';
        const variantSku = addBtn.dataset.variantSku || 'N/A';
        const variantPrice = parseFloat(addBtn.dataset.variantPrice || '0');
        const variantStock = parseInt(addBtn.dataset.variantStock || '0', 10);
        const variantImage = addBtn.dataset.variantImage || '{{ asset("images/no-image.png") }}';

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.setAttribute('data-variant-id', variantId);
        row.innerHTML = `
            <td>
                <div class="daily-product">
                    <img src="${variantImage}" alt="${variantName}">
                    <div class="daily-product-meta">
                        <div class="daily-product-title">${variantName}</div>
                        <div class="daily-product-sub">${variantSku}</div>
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
            </td>
            <td class="text-center">${variantSku}</td>
            <td class="text-center price" data-price="${variantPrice}">${formatMoney(variantPrice)}</td>
            <td class="text-center">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input min50" min="1" ${variantStock > 0 ? `max="${variantStock}"` : ''} value="1" required>
            </td>
            <td class="text-center row-total">${formatMoney(variantPrice)}</td>
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

    variantSearchResults.addEventListener('change', function (event) {
        const perPageSelect = event.target.closest('#per-page-select');
        if (!perPageSelect) {
            return;
        }

        const nextPerPage = parseInt(perPageSelect.value || '5', 10);
        variantPerPage = Number.isNaN(nextPerPage) ? 5 : Math.min(50, Math.max(5, nextPerPage));
        performVariantSearch(1);
    });

    cartContainer.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-cart-item');
        if (!removeBtn) {
            return;
        }

        removeBtn.closest('.cart-item-row')?.remove();
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();
    });

    cartContainer.addEventListener('input', function (event) {
        if (!event.target.classList.contains('quantity-input')) {
            return;
        }

        const quantity = parseInt(event.target.value || '1', 10);
        if (Number.isNaN(quantity) || quantity < 1) {
            event.target.value = '1';
        }

        updateCartTotal();
    });

    document.getElementById('daily-rule-edit-form').addEventListener('submit', function (event) {
        if (cartContainer.querySelectorAll('.cart-item-row').length === 0) {
            event.preventDefault();
            alert('Vui lòng thêm ít nhất một sản phẩm.');
        }
    });

    updateCartVisibility();
    updateCartTotal();
});
</script>
@endpush
