<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('auth.login_title'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Limitless / Bootstrap core --}}
    <link href="{{ asset('assets/css/bootstrap.min.css?v=1.1') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap_limitless.min.css?v=1.1') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/layout.min.css?v=1.1') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/components.min.css?v=1.1') }}" rel="stylesheet">
    <!--link href="{{ asset('assets/css/colors.min.css?v=1.1') }}" rel="stylesheet"-->
    <style type="text/css">
        .bg-light {
            background: url(https://hoanglongtnt.com/storage/media/bg-cover.png);
            background-size: cover;
            background-position: center;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-light">

    {{-- Nội dung trang con --}}
    @yield('content')

    {{-- Core JS --}}
    <script src="{{ asset('assets/js/jquery.min.js?v=1.1') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js?v=1.1') }}"></script>
    <script src="{{ asset('assets/js/app.js?v=1.1') }}"></script>

    {{-- Plugins thường dùng của Limitless (tùy gói bạn có) --}}
    {{-- <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script> --}}
    {{-- <script> $('.form-input-styled').uniform(); </script> --}}

    @stack('scripts')
</body>
</html>
