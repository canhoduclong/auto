@extends('layouts.site')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Giỏ hàng', 'url' => route('cart.show')], 
    ['label' => 'Giỏ hàng', 'url' => '']
]"/>
@endsection

@section('content')
<style>
    .cart-page {
        background: radial-gradient(circle at 20% -20%, #e6f0ff 0%, #f8fbff 45%, #f4f7fb 100%);
    }
    .cart-mobile-list {
        display: none;
    }
    .cart-main-table {
        display: block;
    }
    @media (max-width: 767.98px) {
        .cart-title {
            font-size: 1.6rem;
        }
        .cart-head {
            flex-direction: column;
            align-items: flex-start;
        }
        .cart-main-table {
            display: none !important;
        }
        .cart-mobile-list {
            display: block !important;
            margin: 0 -10px;
        }
        .cart-mobile-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            margin-bottom: 16px;
            padding: 14px 10px 10px 10px;
        }
        .cart-mobile-top {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .cart-mobile-top .cart-thumb {
            width: 60px;
            height: 60px;
        }
        .cart-mobile-top .cart-item-name {
            font-size: 1.1rem;
        }
        .cart-mobile-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
            font-size: 0.98rem;
            margin-top: 8px;
        }
        .cart-mobile-meta span {
            color: #888;
        }
        .cart-mobile-meta input[type="number"] {
            width: 60px;
            font-size: 1rem;
            padding: 2px 6px;
        }
        .cart-mobile-meta strong {
            font-size: 1.08rem;
            color: #e53935;
        }
        .cart-mobile-item .btn-sm {
            padding: 4px 10px;
            font-size: 1.1rem;
        }
        .cart-summary-card {
            margin-top: 18px;
        }
    }
    .cart-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
    }
    .cart-item-name {
        font-weight: 500;
    }
    .cart-item-sku {
        font-size: 0.875rem;
        color: #555;
    }
    .cart-item-weight input {
        width: 80px;
    }
    .cart-line-subtotal {
        font-size: 1.1rem;
        color: #333;
    }
    .cart-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    .cart-summary-total {
        font-size: 1.25rem;
        font-weight: 500;
        color: #e53935;
    }
    .cart-empty {
        text-align: center;
        padding: 60px 20px;
        color: #555;
    }
    .cart-empty i {
        font-size: 3rem;
        color: #ccc;
    }
    
</style>
<script>  
document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenTag ? csrfTokenTag.content : '';

    function getContainer(element) {
        return element.closest('[data-id]');
    }

    function showReloadPopup() {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Phiên làm việc hết hạn hoặc có lỗi',
                text: 'Vui lòng tải lại trang.',
                confirmButtonText: 'Tải lại trang',
                allowOutsideClick: false
            }).then(() => window.location.reload());
        } else {
            alert('Có lỗi xảy ra. Trang sẽ được tải lại.');

            // window.location.reload();
        }
    }

 
    function removeItem(id) {
        return fetch(`/cart/remove/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                // Xóa tất cả item cùng data-id
                const sameItems = document.querySelectorAll(`[data-id="${id}"]`);
                sameItems.forEach(item => item.remove());

                // Update summary
                const summaryTotal = document.querySelector('.summary-total');
                if (summaryTotal) {
                    summaryTotal.textContent = data.summary.formatted_total;
                }

                const summaryItemCount = document.querySelector('.summary-item-count');
                if (summaryItemCount) {
                    summaryItemCount.textContent = data.summary.item_count;
                }

                const summaryLineCount = document.querySelector('.summary-line-count');
                if (summaryLineCount) {
                    summaryLineCount.textContent = data.summary.line_count;
                }

                // Nếu giỏ hàng rỗng
                const remainingItems = document.querySelectorAll('[data-id]');
                if (remainingItems.length === 0) {
                    const cartContainer = document.querySelector('.cart-container');
                    if (cartContainer) {
                        cartContainer.innerHTML =
                            '<div class="text-center py-5">Giỏ hàng trống</div>';
                    }
                }

            } else {
                showReloadPopup();
            }
        })
        .catch(showReloadPopup);
    }
 


    function updateCartItem(id, quantity) {
        fetch(`/cart/update/${id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _method: 'PATCH',
                id: id,
                quantity: quantity
            })
        })
        .then(async response => {
            let data;
            try {
                data = await response.json();
            } catch (err) {
                throw new Error('JSON error');
            }

            if (!response.ok || response.status === 440) {
                throw new Error(data.message || 'Update failed');
            }

            return data;
        })
        .then(data => {
            if (data.removed) {
                const sameItems = document.querySelectorAll(`[data-id="${id}"]`);
                sameItems.forEach(item => item.remove());

                const summaryTotal = document.querySelector('.summary-total');
                if (summaryTotal) summaryTotal.textContent = data.summary.formatted_total;

                const summaryItemCount = document.querySelector('.summary-item-count');
                if (summaryItemCount) summaryItemCount.textContent = data.summary.item_count;

                const summaryLineCount = document.querySelector('.summary-line-count');
                if (summaryLineCount) summaryLineCount.textContent = data.summary.line_count;

                const remainingItems = document.querySelectorAll('[data-id]');
                if (remainingItems.length === 0) {
                    const cartContainer = document.querySelector('.cart-container');
                    if (cartContainer) {
                        cartContainer.innerHTML =
                            '<div class="text-center py-5">Giỏ hàng trống</div>';
                    }
                }
                return;
            }

            const sameItems = document.querySelectorAll(`[data-id="${id}"]`);

            sameItems.forEach(itemContainer => {
                const qtyInput = itemContainer.querySelector('.update-cart');
                if (qtyInput) qtyInput.value = data.item.quantity;

                const lineSubtotal = itemContainer.querySelector('.cart-line-total-money');
                if (lineSubtotal) {
                    lineSubtotal.textContent = data.item.formatted_subtotal;
                }

                const formula = itemContainer.querySelector('.cart-line-formula');
                if (formula) {
                    const pricingLabel = data.item.is_priced_by_kg ? 'kg' : 'đơn vị';
                    formula.textContent =
                        `${data.item.quantity} × ${data.item.unit_weight}${pricingLabel} × ${data.item.unit_price} = `;
                }
            });

            const summaryTotal = document.querySelector('.summary-total');
            if (summaryTotal) summaryTotal.textContent = data.summary.formatted_total;

            const summaryItemCount = document.querySelector('.summary-item-count');
            if (summaryItemCount) summaryItemCount.textContent = data.summary.item_count;

            const summaryLineCount = document.querySelector('.summary-line-count');
            if (summaryLineCount) summaryLineCount.textContent = data.summary.line_count;
        })
        .catch(error => {
            console.error(error);
            showReloadPopup();
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('update-cart')) {
            const container = getContainer(e.target);
            if (!container) return;

            const id = container.dataset.id;
            let quantity = parseInt(e.target.value, 10);

            if (quantity <= 0) {
                removeItem(id);
                return;
            }

            updateCartItem(id, quantity);
        }
    });

    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-from-cart');
        if (removeBtn) {
            const container = getContainer(removeBtn);
            if (container) {
                removeItem(container.dataset.id);
            }
        }
    });
}); 

</script>


<div class="cart-page py-5">
    <div class="container">
        <div class="cart-head">
            <div>
                <span class="cart-chip  text-uppercase"><i class="bi bi-bag-check"></i> Giỏ hàng</span>
                <h1 class="cart-title text-uppercase">Giỏ hàng của bạn</h1>
                <p class="cart-subtitle">Kiểm tra sản phẩm và xác nhận đơn hàng trước khi đặt.</p>
            </div>
        </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('cart') && count(session('cart')) > 0)
                @php
                    $total = 0;
                    $itemCount = 0;
                    foreach (session('cart') as $details) {
                        $size = isset($details['size']) && $details['size'] > 0 ? $details['size'] : 1;
                        $total += (int)$details['price'] * (int)$details['quantity'] * (int) $size;
                        $itemCount += (int) $details['quantity'];
                    }
                @endphp

                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card cart-main-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 fw-bold  text-uppercase">Sản phẩm trong giỏ</h5>
                                    <span class="badge text-bg-light border summary-line-badge">{{ count(session('cart')) }} dòng sản phẩm</span>
                                </div>

                                <div class="table-responsive cart-main-table">
                                    <table class="table cart-table">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>Đơn giá</th>
                                                <th>Số lượng</th>
                                                <td>Size</td>
                                                <th>Tạm tính</th>
                                                <th>Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(session('cart') as $id => $details)
                                                @php
                                                    $unitWeight = isset($details['unit_weight']) && $details['unit_weight'] > 0 ?  (float) $details['unit_weight'] : 1;
                                                    $isPricedByKg = (bool) ($details['is_priced_by_kg'] ?? true);
                                                    $pricingFactor = $isPricedByKg ? $unitWeight : 1;
                                                    $quantity = (int) $details['quantity'];
                                                    $price = (float) $details['price'];
                                                    $lineTotal = $quantity * $pricingFactor * $price;
                                                @endphp
                                                <tr data-id="{{ $id }}">
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            @if($details['image'])
                                                                <img src="{{ asset('storage/' . $details['image']) }}" alt="{{ $details['name'] }}" class="cart-thumb">
                                                            @else
                                                                <div class="cart-thumb d-flex align-items-center justify-content-center">
                                                                    <i class="bi bi-image text-muted"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <div class="cart-item-name">{{ $details['name'] }}</div>
                                                                <div class="cart-item-sku">SKU: {{ $details['sku'] }}</div> 
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ number_format($price) }}d</td>
                                                    <td>
                                                        <input type="number" value="{{ $quantity }}" class="form-control cart-qty update-cart" min="1">
                                                    </td>
                                                    <td>
                                                        <div class="cart-item-weight text-muted small">{{ number_format($unitWeight, 3, ',', '.') }} {{ $details['unit_label'] ?? 'Cái' }} | {{ $isPricedByKg ? 'Theo kg' : 'Theo đơn vị' }}</div>
                                                    </td>
                                                    <td class="fw-semibold cart-line-subtotal"> 
                                                        <span class="cart-line-total-money">{{ number_format($lineTotal) }}d</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-outline-danger btn-sm remove-from-cart" title="Xoa san pham">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="cart-mobile-list">
                                    @foreach(session('cart') as $id => $details)
                                        @php
                                            $unitWeight = isset($details['unit_weight']) && $details['unit_weight'] > 0 ? $details['unit_weight'] : 1;
                                            $isPricedByKg = (bool) ($details['is_priced_by_kg'] ?? true);
                                            $pricingFactor = $isPricedByKg ? $unitWeight : 1;
                                            $quantity = (int) $details['quantity'];
                                            $price = (float) $details['price'];
                                            $lineTotal = $quantity * $pricingFactor * $price;
                                        @endphp
                                        <div class="cart-mobile-item" data-id="{{ $id }}">
                                            <div class="cart-mobile-top">
                                                @if($details['image'])
                                                    <img src="{{ asset('storage/' . $details['image']) }}" alt="{{ $details['name'] }}" class="cart-thumb">
                                                @else
                                                    <div class="cart-thumb d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <div class="cart-item-name">{{ $details['name'] }}</div>
                                                    <div class="cart-item-sku">SKU: {{ $details['sku'] }}</div>
                                                    <div class="cart-item-weight text-muted small" data-weight="{{ $unitWeight }}">{{ number_format((float) $unitWeight, 3, ',', '.') }} {{ $details['unit_label'] ?? 'Cái' }} | {{ $isPricedByKg ? 'Theo kg' : 'Theo đơn vị' }}</div>
                                                </div>
                                                <button class="btn btn-outline-danger btn-sm remove-from-cart">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="cart-mobile-meta">
                                                <span>Don gia</span>
                                                <strong>{{ number_format($price) }}d</strong>
                                                <span>So luong</span>
                                                <input type="number" value="{{ $quantity }}" class="form-control cart-qty update-cart" min="1">
                                                <span>Thành tiền</span>
                                                <strong class="cart-line-subtotal"> 
                                                    <span class="cart-line-total-money">{{ number_format($lineTotal) }}d</span>
                                                </strong>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card cart-summary-card sticky-lg-top" style="top: 20px;">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3 text-uppercase">Tổng quan đơn hàng</h5>
                                <div class="cart-summary-row">
                                    <span>Số lượng sản phẩm</span>
                                    <strong class="summary-item-count">{{ $itemCount }}</strong>
                                </div>
                                <div class="cart-summary-row">
                                    <span>Số dòng giỏ hàng</span>
                                    <strong class="summary-line-count">{{ count(session('cart')) }}</strong>
                                </div>
                                <hr>
                                <div class="cart-summary-row cart-summary-total">
                                    <span>Tổng tạm tính</span>
                                    <span class="summary-total">{{ number_format($total) }}d</span>
                                </div>
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                                    Khi thay đổi chiết khấu ở bước checkout, hệ thống sẽ cảnh báo ngay nếu giá bán nhỏ hơn <strong>Giá Min</strong>.
                                </div>
                                <a href="{{ route('cart.checkout') }}" class="btn btn-success w-100 mt-2">
                                    <i class="bi bi-credit-card me-1"></i>Tiến hành đặt hàng
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="bi bi-arrow-left me-1"></i>Tiếp tục mua sắm
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="cart-empty">
                    <i class="bi bi-cart-x"></i>
                    <h4 class="mt-3 mb-2">Giỏ hàng đang trống</h4>
                    <p class="text-muted mb-3">Hãy thêm sản phẩm vào giỏ để bắt đầu tạo đơn hàng.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="bi bi-bag-plus me-1"></i>Khám phá sản phẩm
                    </a>
                </div>
            @endif
    </div>
</div>
@endsection
 