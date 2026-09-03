@extends('layouts.shipper')

@section('title', 'Danh sách phiếu điều chuyển')
@section('subtitle', 'Tra cứu lịch sử vận chuyển hàng giữa các kho')

@section('content')
@php
    $slipsByDate = $dispatchSlips->getCollection()->groupBy(fn ($slip) => $slip->business_date->toDateString());
@endphp

<style>
    .dispatch-search-card, .dispatch-history-panel { border: 1px solid #dbe5e3; border-radius: 12px; background: #fff; }
    .dispatch-history-date { padding: 14px 16px; border-bottom: 1px solid #eef2f7; }
    .dispatch-history-date:last-child { border-bottom: 0; }
    .dispatch-history-slips { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 10px; margin-top: 10px; }
    .dispatch-history-slip { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 9px; color: #334155; background: #fff; text-decoration: none; }
    .dispatch-history-slip:hover { border-color: #0f766e; background: #ecfdf5; color: #0f766e; }
    .dispatch-slip-main { min-width: 0; }
    .dispatch-slip-route { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 576px) {
        .dispatch-history-slips { grid-template-columns: minmax(0, 1fr); }
        .dispatch-history-slip { align-items: flex-start; }
    }
</style>

<div class="dispatch-search-card p-3 mb-3">
    <form method="GET" action="{{ route('shipper.warehouse-transfers') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div>
            <label for="transfer-date" class="form-label fw-semibold mb-1">Tìm phiếu theo ngày</label>
            <input id="transfer-date" type="date" name="date" value="{{ $selectedDate }}" class="form-control">
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Tìm kiếm</button>
        @if($selectedDate)
            <a href="{{ route('shipper.warehouse-transfers') }}" class="btn btn-outline-secondary">Xem tất cả</a>
        @endif
    </form>
</div>

<div class="dispatch-history-panel">
    <div class="d-flex justify-content-between align-items-center gap-2 p-3 border-bottom">
        <div>
            <div class="fw-bold"><i class="bi bi-journal-text me-1"></i>Danh sách phiếu điều chuyển</div>
            <div class="small text-muted">Phiếu mới nhất hiển thị trước. Bấm vào phiếu để xem nội dung và trạng thái vận chuyển.</div>
        </div>
        <span class="badge bg-secondary">{{ $dispatchSlips->total() }} phiếu</span>
    </div>

    @forelse($slipsByDate as $slipDate => $dateSlips)
        <section class="dispatch-history-date">
            <div class="fw-semibold text-muted"><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($slipDate)->format('d/m/Y') }}</div>
            <div class="dispatch-history-slips">
                @foreach($dateSlips as $slip)
                    <a class="dispatch-history-slip" href="{{ route('shipper.warehouse-transfers.show', $slip) }}">
                        <span class="dispatch-slip-main">
                            <strong class="d-block">{{ $slip->code }}</strong>
                            <span class="small dispatch-slip-route d-block">{{ $slip->sourceWarehouse?->name ?? '—' }} → {{ $slip->targetWarehouse?->name ?? '—' }}</span>
                            <span class="small text-muted">Shipper: {{ $slip->shipper?->short_name ?: ($slip->shipper?->name ?? '—') }}</span>
                        </span>
                        <span class="d-flex align-items-center gap-1 flex-shrink-0">
                            <span class="badge {{ $slip->status === 'finalized' ? 'bg-success' : ($slip->status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $slip->status === 'finalized' ? 'Đã chốt' : ($slip->status === 'cancelled' ? 'Đã hủy' : 'Đang mở') }}</span>
                            <span class="badge bg-light text-dark border">{{ $slip->entries_count }} mục</span>
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="p-5 text-center text-muted">
            <i class="bi bi-truck fs-1 d-block mb-2"></i>
            {{ $selectedDate ? 'Không có phiếu điều chuyển trong ngày đã chọn.' : 'Chưa có phiếu điều chuyển trong lịch sử.' }}
        </div>
    @endforelse
</div>

@if($dispatchSlips->hasPages())
    <div class="mt-3">{{ $dispatchSlips->links() }}</div>
@endif
@endsection
