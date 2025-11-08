@props(['cartCount'])

<div class="position-relative d-inline-block">
    <a href="{{ route('cart.show') }}" class="btn btn-outline-primary position-relative">
        <i class="bi bi-cart"></i>
        @if($cartCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $cartCount }}
            </span>
        @endif
    </a>
</div>