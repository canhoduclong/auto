@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --acc-bg: #f4f6fb;
            --acc-panel: #ffffff;
            --acc-line: #e2e8f0;
            --acc-text: #0f172a;
            --acc-muted: #64748b;
            --acc-brand: #0ea5e9;
        }

        .content-wrapper {
            background: var(--acc-bg);
        }

        .acc-topbar {
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--acc-line);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .acc-content {
            padding: 0;
        }

        .acc-card {
            border: 1px solid var(--acc-line);
            border-radius: 14px;
            background: var(--acc-panel);
            box-shadow: 0 8px 20px rgba(15,23,42,0.04);
        }

        .acc-card .card-body {
            padding: 16px;
        }

        .acc-card .table {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }

        .table thead th {
            text-transform: uppercase;
            font-size: 12px;
            color: var(--acc-muted);
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .acc-content .form-label {
            margin-bottom: 0.35rem;
        }

        .acc-content .form-control,
        .acc-content .form-select {
            border-color: #dbe3f0;
        }

        @media (max-width: 992px) {
            .acc-topbar {
                padding: .65rem .85rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content pt-3 px-3 px-lg-4">
        <header class="acc-topbar">
            <div>
                <strong>@yield('title', 'Tài chính')</strong>
                <div class="text-muted small">@yield('subtitle', 'Khu vực tài chính dành cho quản trị viên')</div>
            </div>
            <div class="text-muted small">{{ auth()->user()->name ?? 'Admin' }} | {{ now()->format('d/m/Y H:i') }}</div>
        </header>

        <section class="acc-content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @yield('accounting_content')
        </section>
    </div>
@endsection