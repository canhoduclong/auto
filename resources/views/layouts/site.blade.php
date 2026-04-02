@php
use App\Models\Setting;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Setting::get('brand_name', 'Hoàng Long TNT') }}</title>


    <meta name="description" content="{{ __('common.meta.default_description') }}">
    <meta name="keywords" content="{{ __('common.meta.default_keywords') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/nice-select.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/nice-select.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/slicknav.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" type="text/css">
    <!--link rel="stylesheet" href="{{ asset('css/mixitup.min.css') }}" type="text/css"-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --theme-primary: #0f766e;
            --theme-primary-hover: #115e59;
            --theme-primary-soft: #ccfbf1;
            --theme-accent: #ffc107;
            --theme-accent-hover: #e0a800;
            --theme-ink: #0f172a;
        }

        .btn-primary {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--theme-primary-hover);
            border-color: var(--theme-primary-hover);
        }

        .btn-outline-primary {
            color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
            color: #fff;
        }

        .btn-warning {
            background-color: var(--theme-accent);
            border-color: var(--theme-accent);
            color: var(--theme-ink);
        }

        .btn-warning:hover,
        .btn-warning:focus,
        .btn-warning:active {
            background-color: var(--theme-accent-hover);
            border-color: var(--theme-accent-hover);
            color: var(--theme-ink);
        }
 
        .text-primary {
            color: var(--theme-primary) !important;
        }

        .bg-primary {
            background-color: var(--theme-primary) !important;
        }

        .badge.text-bg-primary {
            background-color: var(--theme-primary) !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(15, 118, 110, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.18);
        }
    </style>
    @stack('styles')

</head>


<body>

    <div id="preloder">
        <div class="loader"></div>
    </div>
    @include('layouts.partials.site_header') 
    @include('layouts.notifications')

    @yield('breadcrumb')
    @yield('content')

    @include('layouts.partials.site_footer')

    


    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/mixitup.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    
    <script src="{{ asset('js/main.js') }}"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('notification-container');
            if (!container) {
                window.alert(message);
                return;
            }

            const toastEl = document.createElement('div');
            toastEl.classList.add('toast', 'border-0', 'shadow-lg', 'overflow-hidden');
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            let headerClass = 'bg-success text-white';
            if (type === 'error') {
                headerClass = 'bg-danger text-white';
            } else if (type === 'warning') {
                headerClass = 'bg-warning text-dark';
            } else if (type === 'info') {
                headerClass = 'bg-info text-dark';
            }

            const toastLabels = @json(__('common.toast_types'));
            const closeLabel = @json(__('common.actions.close'));
            const typeLabel = toastLabels[type] || type;

            toastEl.innerHTML = `
                <div class="toast-header ${headerClass}">
                    <strong class="me-auto">${typeLabel}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="${closeLabel}"></button>
                </div>
                <div class="toast-body bg-white">
                    ${message}
                </div>
            `;

            container.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();

            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('notification-container');
            if (!container) {
                return;
            }

            container.querySelectorAll('.toast').forEach(function(toastEl) {
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();

                toastEl.addEventListener('hidden.bs.toast', function () {
                    toastEl.remove();
                });
            });
        });
    </script>
    @include('site._cart_scripts')
    @stack('scripts')

 
</body>
</html>
