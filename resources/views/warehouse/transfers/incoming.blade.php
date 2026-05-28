@extends('layouts.warehouse')

@section('title', 'Tiếp nhận hàng điều chuyển')
@section('subtitle', 'Đơn hàng điều chuyển từ kho khác qua shipper')

@push('styles')
<style>
    .wh-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .wh-item-table-wrap {
        overflow-x: auto;
    }
    .wh-item-table-head,
    .wh-item-table-row {
        display: grid;
        grid-template-columns: 48px minmax(50px, 1fr) 42px 52px 86px 86px;
        gap: 8px;
        align-items: center;
    }
    .wh-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 6px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .wh-item-row {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .wh-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .wh-item-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }
    .wh-item-cell strong {
        color: #0f172a;
    }
    @media (max-width: 767.98px) {
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 44px minmax(120px, 1fr) 42px 52px 70px 70px;
        }
    }
    /* --- Order Sequence Navigation --- */
    .wh-order-nav-area {
        position: sticky;
        top: 75px;
        z-index: 95;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .wh-order-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        color: #fff !important;
        background-color: #6c757d;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 2px solid transparent;
    }
    .wh-order-nav-pill.is-unpacked {
        background-color: #38bdf8 !important; /* xanh da trời */
        color: #fff !important;
    }
    .wh-order-nav-pill.is-packed {
        background-color: var(--theme-primary) !important;
        color: #fff !important;
    }
    .wh-order-index {
        border-radius: 50px;
        width: 35px;
        z-index: 2;
        font-weight: 700;
        padding: 3px 8px;
        background: var(--theme-primary) !important;
        color: #fff;
        margin-right: 12px;
    }
    .wh-order-nav-pill.active, .wh-order-nav-pill:focus {
        border: 2px solid #2563eb !important;
        color: #2563eb !important;
        background: #e0e7ff !important;
    }
    .wh-order-nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .wh-order-index {
        border-radius: 50px;
        width: 35px;
        z-index: 2;
        font-weight: 700;
        padding: 3px 8px;
        background: #0f172a;
        color: #fff;
        margin-right: 12px;
    }
</style>
@endpush

@section('content')

@php
    $receivedTransfers = $transfers->where('status', 'received_completed');
    $pendingTransfers = $transfers->where('status', 'delivered_waiting_receive');
@endphp

<div class="wh-order-nav-area mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-bold text-muted me-1"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
        @foreach($transfers as $navTransfer)
            @php
                $sequenceNumber = $navTransfer->sequence_number ?? $loop->iteration;
                $isReceived = $navTransfer->status === 'received_completed';
            @endphp
            <a href="javascript:void(0);"
               onclick="scrollToTransferCard({{ $navTransfer->id }}, this)"
               class="wh-order-nav-pill {{ $isReceived ? 'is-packed' : 'is-unpacked' }}"
               id="nav-pill-{{ $navTransfer->id }}"
               title="{{ $navTransfer->order?->customer?->name ?? 'Đơn hàng' }}">
                {{ $sequenceNumber }}
            </a>
        @endforeach
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold" style="color: var(--theme-primary) !important;">
                <i class="bi bi-check-circle me-2"></i>Đã nhận hàng
            </h5>
            <span class="badge rounded-pill" style="background: var(--theme-primary) !important; color: #fff;">{{ $receivedTransfers->count() }} đơn</span>
        </div>
        <div class="row g-3">
            @forelse($receivedTransfers as $transfer)
                @include('warehouse.transfers._transfer_card', ['transfer' => $transfer, 'isReceived' => true])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn đã nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="col-12 col-lg-6 border-start">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold" style="color:#38bdf8">
                <i class="bi bi-truck me-2"></i>Hàng được điều chuyển tới - Cần tiếp nhận
            </h5>
            <span class="badge rounded-pill" style="background:#38bdf8; color:#fff;">{{ $pendingTransfers->count() }} đơn</span>
        </div>
        <div class="row g-3">
            @forelse($pendingTransfers as $transfer)
                @include('warehouse.transfers._transfer_card', ['transfer' => $transfer, 'isReceived' => false])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn cần tiếp nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function scrollToTransferCard(id, el) {
    const card = document.getElementById('transfer-card-' + id);
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            document.querySelectorAll('.wh-order-nav-pill').forEach(pill => pill.classList.remove('active'));
            if (el) el.classList.add('active');
        }, 100);
    }
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const accepted = window.confirm('Xác nhận hoàn lại phiếu điều chuyển này trước khi nhập kho?');
            if (!accepted) {
                return;
            }

            const reason = window.prompt('Nhập lý do hoàn lại (không bắt buộc):', '');
            if (reason === null) {
                return;
            }

            const noteInput = form.querySelector('input[name="rollback_note"]');
            if (noteInput) {
                noteInput.value = reason.trim();
            }

            form.submit();
        });
    });
});
</script>
@endpush
@endsection
