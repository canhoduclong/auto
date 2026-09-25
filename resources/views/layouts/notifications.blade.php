@php
    $types = ['success', 'error', 'warning', 'info'];
@endphp

<div id="notification-container" class="position-fixed top-0 end-0 p-3 d-flex flex-column gap-2" style="z-index: 9999; min-width: 320px; max-width: 420px;">
    @foreach ($types as $type)
        @if (session()->has($type))
            <div class="toast border-0 shadow-lg overflow-hidden" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="toast-header {{ $type === 'error' ? 'bg-danger text-white' : '' }} {{ $type === 'success' ? 'bg-success text-white' : '' }} {{ $type === 'warning' ? 'bg-warning' : '' }} {{ $type === 'info' ? 'bg-info text-dark' : '' }}">
                    <strong class="me-auto">{{ __('common.toast_types.' . $type) }}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="toast-body bg-white">
                    {!! session($type) !!}
                </div>
            </div>
        @endif
    @endforeach
</div>