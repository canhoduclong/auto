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
        padding: 2rem 0 2.5rem;
    }
    .cart-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .cart-title {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }
    .cart-subtitle {
        margin: 0.35rem 0 0;
        color: #64748b;
    }
    .cart-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        background: #dbeafe;
        color: #1e40af;
    }
    .cart-main-card,
    .cart-summary-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        background: #fff;
    }
    .cart-main-card .card-body,
    .cart-summary-card .card-body {
        padding: 1.2rem;
    }
    .cart-table th {
        border-top: 0;
        color: #475569;
        text-transform: uppercase;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        font-weight: 700;
        background: #f8fafc;
    }
    .cart-table td {
        vertical-align: middle;
        border-color: #eef2f7;
    }
    .cart-item-name {
        font-weight: 700;
        color: #0f172a;
    }
    .cart-item-sku {
        color: #64748b;
        font-size: 0.82rem;
    }
    .cart-thumb {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .cart-qty {
        width: 90px;
        border-radius: 10px;
        border-color: #cbd5e1;
    }
    .cart-mobile-list {
        display: none;
    }
    .cart-mobile-item {
        border: 1px solid #e5eaf3;
        border-radius: 14px;
        padding: 0.9rem;
        margin-bottom: 0.8rem;
        background: #fff;
    }
    .cart-mobile-top {
        display: flex;
        gap: 0.75rem;
    }
    .cart-mobile-meta {
        display: grid;
        grid-template-columns: 1fr auto;
        row-gap: 0.3rem;
        margin-top: 0.65rem;
        color: #475569;
        font-size: 0.9rem;
    }
    .cart-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.65rem;
        color: #334155;
    }
    .cart-summary-total {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
    }
    .cart-empty {
        text-align: center;
        padding: 3rem 1.2rem;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
    }
    .cart-empty i {
        font-size: 2.7rem;
        color: #94a3b8;
    }
    @media (max-width: 991.98px) {
        .cart-main-table {
            display: none;
        }
        .cart-mobile-list {
            display: block;
        }
    }
    @media (max-width: 767.98px) {
        .cart-title {
            font-size: 1.6rem;
        }
        .cart-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="cart-page">
    <div class="container">
        <div class="cart-head">
            <div>
                <span class="cart-chip"><i class="bi bi-bag-check"></i> Gio hang cua ban</span>
                <h1 class="cart-title">Shopping Cart</h1>
                <p class="cart-subtitle">Kiem tra san pham va xac nhan don hang truoc khi dat.</p>
            </div>
            @if(session('cart') && count(session('cart')) > 0)
                <a href="{{ route('cart.checkout') }}" class="btn btn-success">
                    <i class="bi bi-credit-card me-1"></i>Tao don hang
                </a>
            @endif
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
                        $total += $details['price'] * $details['quantity'];
                        $itemCount += (int) $details['quantity'];
                    }
                @endphp

                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card cart-main-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 fw-bold">San pham trong gio</h5>
                                    <span class="badge text-bg-light border summary-line-badge">{{ count(session('cart')) }} dong san pham</span>
                                </div>

                                <div class="table-responsive cart-main-table">
                                    <table class="table cart-table">
                                        <thead>
                                            <tr>
                                                <th>San pham</th>
                                                <th>Don gia</th>
                                                <th>So luong</th>
                                                <th>Tam tinh</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(session('cart') as $id => $details)
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
                                                    <td>{{ number_format($details['price']) }}d</td>
                                                    <td>
                                                        <input type="number" value="{{ $details['quantity'] }}" class="form-control cart-qty update-cart" min="1">
                                                    </td>
                                                    <td class="fw-semibold cart-line-subtotal">{{ number_format($details['price'] * $details['quantity']) }}d</td>
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
                                                </div>
                                                <button class="btn btn-outline-danger btn-sm remove-from-cart">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="cart-mobile-meta">
                                                <span>Don gia</span>
                                                <strong>{{ number_format($details['price']) }}d</strong>
                                                <span>So luong</span>
                                                <input type="number" value="{{ $details['quantity'] }}" class="form-control cart-qty update-cart" min="1">
                                                <span>Tam tinh</span>
                                                <strong class="cart-line-subtotal">{{ number_format($details['price'] * $details['quantity']) }}d</strong>
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
                                <h5 class="fw-bold mb-3">Tong quan don hang</h5>
                                <div class="cart-summary-row">
                                    <span>So luong san pham</span>
                                    <strong class="summary-item-count">{{ $itemCount }}</strong>
                                </div>
                                <div class="cart-summary-row">
                                    <span>So dong gio hang</span>
                                    <strong class="summary-line-count">{{ count(session('cart')) }}</strong>
                                </div>
                                <hr>
                                <div class="cart-summary-row cart-summary-total">
                                    <span>Tong tam tinh</span>
                                    <span class="summary-total">{{ number_format($total) }}d</span>
                                </div>
                                <a href="{{ route('cart.checkout') }}" class="btn btn-success w-100 mt-2">
                                    <i class="bi bi-credit-card me-1"></i>Tien hanh dat hang
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="bi bi-arrow-left me-1"></i>Tiep tuc mua sam
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="cart-empty">
                    <i class="bi bi-cart-x"></i>
                    <h4 class="mt-3 mb-2">Gio hang dang trong</h4>
                    <p class="text-muted mb-3">Hay them san pham vao gio de bat dau tao don hang.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="bi bi-bag-plus me-1"></i>Kham pha san pham
                    </a>
                </div>
            @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function getContainer(element) {
        return element.closest('[data-id]');
    }

    // Update quantity
    document.querySelectorAll('.update-cart').forEach(function(element) {
        element.addEventListener('change', function(e) {
            const container = getContainer(e.target);
            if (!container) {
                return;
            }

            let id = container.dataset.id;
            let quantity = e.target.value;

            fetch(`/cart/update/${id}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: id,
                    quantity: quantity
                })
            })
            .then(async response => {
                let data;
                try {
                    data = await response.json();
                } catch (err) {
                    showReloadPopup();
                    throw new Error('Session expired or server error.');
                }
                // Nếu lỗi session hết hạn (status 440 hoặc message chứa "hết hạn")
                if (response.status === 440 || (data && data.message && data.message.toLowerCase().includes('hết hạn'))) {
                    showReloadPopup();
                    throw new Error(data.message || 'Phiên làm việc đã hết hạn.');
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Khong the cap nhat so luong.');
                }
                return data;
            })
            .then(data => {
                // Cập nhật số lượng và tạm tính từng dòng
                const sameItems = document.querySelectorAll(`[data-id="${id}"]`);
                sameItems.forEach(function(itemContainer) {
                    const qtyInput = itemContainer.querySelector('.update-cart');
                    if (qtyInput) {
                        qtyInput.value = data.item.quantity;
                    }
                    const lineSubtotal = itemContainer.querySelector('.cart-line-subtotal');
                    if (lineSubtotal) {
                        lineSubtotal.textContent = data.item.formatted_subtotal;
                    }
                });
                // Cập nhật tổng tiền, số lượng sản phẩm, số dòng
                const summaryItemCount = document.querySelector('.summary-item-count');
                if (summaryItemCount) {
                    summaryItemCount.textContent = data.summary.item_count;
                }
                const summaryLineCount = document.querySelector('.summary-line-count');
                if (summaryLineCount) {
                    summaryLineCount.textContent = data.summary.line_count;
                }
                const summaryTotal = document.querySelector('.summary-total');
                if (summaryTotal) {
                    summaryTotal.textContent = data.summary.formatted_total;
                }
                const summaryLineBadge = document.querySelector('.summary-line-badge');
                if (summaryLineBadge) {
                    summaryLineBadge.textContent = `${data.summary.line_count} dong san pham`;
                }
            })
            .catch(function(error) {
                showReloadPopup();
            });
        });
    });

    function showReloadPopup() {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Phiên đăng nhập đã hết hạn hoặc có lỗi hệ thống',
                text: 'Vui lòng tải lại trang để tiếp tục sử dụng.',
                confirmButtonText: 'Tải lại trang',
                allowOutsideClick: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert('Phiên đăng nhập đã hết hạn hoặc có lỗi hệ thống. Trang sẽ được tải lại.');
            window.location.reload();
        }
    }


    // Remove item
    document.querySelectorAll('.remove-from-cart').forEach(function(element) {
        element.addEventListener('click', function(e) {
            const container = getContainer(e.target);
            if (!container) {
                return;
            }

            let id = container.dataset.id;
            
            fetch(`/cart/remove/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(async response => {
                let data;
                try {
                    data = await response.json();
                } catch (err) {
                    showReloadPopup();
                    throw new Error('Session expired or server error.');
                }
                if (response.status === 440 || (data && data.message && data.message.toLowerCase().includes('hết hạn'))) {
                    showReloadPopup();
                    throw new Error(data.message || 'Phiên làm việc đã hết hạn.');
                }
                if (data.success) {
                    location.reload();
                }
            });
        });
    });
});
</script>
@endpush