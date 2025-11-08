@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Tạo đơn hàng mới</h1>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store_a_new') }}" method="POST" id="create-order-form">4
        @csrf

        {{-- Product List (Cart) --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Danh sách sản phẩm trong đơn</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Hình ảnh</th>
                            <th style="width: 30%;">Sản phẩm</th>
                            <th>SKU</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-container">
                        {{-- Initial product from URL --}}
                        <tr class="cart-item-row" data-variant-id="{{ $variant->id }}">
                            <td>
                                @php
                                    $imageUrl = 'https://via.placeholder.com/60'; // Default placeholder
                                    if ($variant->media) {
                                        $imageUrl = asset('storage/' . $variant->media->file_path);
                                    } elseif ($variant->product->avatar && $variant->product->avatar->media) {
                                        $imageUrl = asset('storage/' . $variant->product->avatar->media->file_path);
                                    }
                                @endphp
                                <img src="{{ $imageUrl }}" alt="{{ $variant->product->name }}" width="60" class="rounded">
                            </td>
                            <td>
                                <input type="hidden" name="items[0][variant_id]" value="{{ $variant->id }}">
                                {{ $variant->product->name }}
                            </td>
                            <td>{{ $variant->sku }}</td>
                            <td class="price" data-price="{{ $variant->latestPriceRule?->price ?? 0 }}">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control quantity-input" value="1" min="1" max="{{ $variant->stock }}" required>
                            </td>
                            <td class="row-total">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-cart-item">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <h5>Tổng cộng: <span id="cart-total">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</span></h5>
            </div>
        </div>

        {{-- Add More Products --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Thêm sản phẩm</h5>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="variant-search" class="form-control" placeholder="Tìm sản phẩm theo tên hoặc SKU...">
                    <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
                </div>
                <div id="variant-search-results" class="mt-3"></div>
            </div>
        </div>

        {{-- Customer Selection --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin khách hàng</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <h6>Chọn khách hàng</h6>
                    <div class="input-group mb-3">
                        <input type="text" id="customer-search" class="form-control" placeholder="Tìm kiếm khách hàng...">
                        <button class="btn btn-outline-secondary" type="button" id="customer-search-button">Tìm</button>
                    </div>
                    <div id="customer-list-container">
                        {{-- Customer list will be loaded here by AJAX --}}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-lg">Tạo Đơn Hàng</button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ---=== Customer Search ===---
    let customerSearchTimeout;
    const customerListContainer = $('#customer-list-container');
    const customerSearchInput = $('#customer-search');
    const customerSearchButton = $('#customer-search-button');
    const customerAjaxUrl = "{{ route('orders.ajax_customer_search') }}";

    function fetch_customer_data(url, data) {
        customerListContainer.html('<div class="text-center my-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $.ajax({
            url: url,
            data: data,
            success: function(response) { customerListContainer.html(response.html); },
            error: function() { customerListContainer.html('<p class="text-danger text-center my-3">Lỗi khi tải danh sách khách hàng.</p>'); }
        });
    }

    function performCustomerSearch() {
        fetch_customer_data(customerAjaxUrl, { page: 1, search: customerSearchInput.val() });
    }

    customerSearchInput.on('keyup', function() { clearTimeout(customerSearchTimeout); customerSearchTimeout = setTimeout(performCustomerSearch, 300); });
    customerSearchButton.on('click', performCustomerSearch);
    customerListContainer.on('click', '.pagination a', function(e) { e.preventDefault(); const url = $(this).attr('href'); if (url) fetch_customer_data(url, {}); });
    customerListContainer.on('click', 'tbody tr', function() { $(this).find('input[type=radio]').prop('checked', true); customerListContainer.find('tbody tr').removeClass('table-active'); $(this).addClass('table-active'); });
    fetch_customer_data(customerAjaxUrl, { page: 1, search: '' });

    // ---=== Variant Search & Cart Management ===---
    let variantSearchTimeout;
    const cartContainer = $('#cart-items-container');
    const variantSearchInput = $('#variant-search');
    const variantSearchButton = $('#variant-search-button');
    const variantSearchResults = $('#variant-search-results');
    const variantAjaxUrl = "{{ route('orders.ajax_variant_search') }}";
    let cartItemIndex = 1;

    function getCartItemIds() {
        const ids = [];
        $('.cart-item-row').each(function() {
            ids.push($(this).data('variant-id'));
        });
        return ids;
    }

    function formatNumber(num) { return new Intl.NumberFormat('vi-VN').format(num); }

    function updateCartTotal() {
        let total = 0;
        $('.cart-item-row').each(function() {
            const price = parseFloat($(this).find('.price').data('price')) || 0;
            const quantity = parseInt($(this).find('.quantity-input').val()) || 0;
            const rowTotal = price * quantity;
            $(this).find('.row-total').text(formatNumber(rowTotal));
            total += rowTotal;
        });
        $('#cart-total').text(formatNumber(total));
    }

    function fetch_variant_data(url, data) {
        variantSearchResults.html('<div class="text-center my-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        data.exclude_ids = getCartItemIds(); // Always add excluded IDs

        $.ajax({
            url: url,
            data: data,
            success: function(response) { variantSearchResults.html(response.html); },
            error: function() { variantSearchResults.html('<p class="text-danger text-center my-3">Lỗi khi tải sản phẩm.</p>'); }
        });
    }

    function performVariantSearch(page = 1) {
        const query = variantSearchInput.val();
        const perPage = $('#per-page-select').val() || 5;
        if (query.length < 2) { variantSearchResults.empty(); return; }
        fetch_variant_data(variantAjaxUrl, { page: page, search: query, per_page: perPage });
    }

    variantSearchInput.on('keyup', function() { clearTimeout(variantSearchTimeout); variantSearchTimeout = setTimeout(function() { performVariantSearch(1); }, 300); });
    variantSearchButton.on('click', function() { performVariantSearch(1); });
    
    variantSearchResults.on('click', '.pagination a', function(e) { 
        e.preventDefault(); 
        const url = $(this).attr('href'); 
        if (url) {
             $.ajax({
                url: url,
                data: { exclude_ids: getCartItemIds() }, // Add excluded IDs to pagination
                success: function(response) { variantSearchResults.html(response.html); },
                error: function() { variantSearchResults.html('<p class="text-danger text-center my-3">Lỗi khi tải sản phẩm.</p>'); }
            });
        }
    });

    variantSearchResults.on('change', '#per-page-select', function() {
        const perPage = $(this).val();
        const query = variantSearchInput.val();
        fetch_variant_data(variantAjaxUrl, { page: 1, search: query, per_page: perPage });
    });

    variantSearchResults.on('click', '.add-variant-to-cart', function() {
        const button = $(this);
        const variantId = button.data('variant-id');
        if ($('.cart-item-row[data-variant-id="' + variantId + '"]').length > 0) { alert('Sản phẩm này đã có trong giỏ hàng.'); return; }

        const newRow = `
            <tr class="cart-item-row" data-variant-id="${variantId}">
                <td><img src="${button.data('variant-image')}" alt="${button.data('variant-name')}" width="60" class="rounded"></td>
                <td><input type="hidden" name="items[${cartItemIndex}][variant_id]" value="${variantId}">${button.data('variant-name')}</td>
                <td>${button.data('variant-sku')}</td>
                <td class="price" data-price="${button.data('variant-price')}">${formatNumber(button.data('variant-price'))}</td>
                <td><input type="number" name="items[${cartItemIndex}][quantity]" class="form-control quantity-input" value="1" min="1" max="${button.data('variant-stock')}" required></td>
                <td class="row-total">${formatNumber(button.data('variant-price'))}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-cart-item">&times;</button></td>
            </tr>`;
        
        cartContainer.append(newRow);
        cartItemIndex++;
        updateCartTotal();
        // After adding, re-run the search to refresh the list with the added item now excluded.
        performVariantSearch(1);
    });

    cartContainer.on('click', '.remove-cart-item', function() {
        if ($('.cart-item-row').length <= 1) { alert('Không thể xóa sản phẩm cuối cùng.'); return; }
        $(this).closest('tr').remove();
        updateCartTotal();
        // After removing, re-run the search in case the user wants to add the item back.
        if (variantSearchInput.val().length >= 2) {
            performVariantSearch(1);
        }
    });

    cartContainer.on('change', '.quantity-input', function() { updateCartTotal(); });

    updateCartTotal();
});44
</script>
@endpush

