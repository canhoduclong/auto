@props(['cartCount'])

<div {{ $attributes->class(['position-relative d-inline-block js-cart-widget']) }}>
    <a href="{{ route('cart.show') }}" class="btn btn-outline-primary position-relative">
        <i class="bi bi-cart"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger js-cart-count {{ $cartCount > 0 ? '' : 'd-none' }}">
            {{ $cartCount }}
        </span>
    </a>
</div>