@php
    $types = ['success', 'error', 'warning', 'info'];
@endphp

<div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    @foreach ($types as $type)
        @if (session()->has($type))
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">{{ ucfirst($type) }}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {!! session($type) !!}
                </div>
            </div>
        @endif
    @endforeach
</div>