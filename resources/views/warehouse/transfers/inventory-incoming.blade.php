@extends('layouts.warehouse')

@section('title', 'Tiếp nhận điều chuyển kho')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-box-arrow-in-down me-2"></i>Tiếp nhận điều chuyển kho</h4>
        <div class="text-muted small">Kho nhận xác nhận nhập kho cho các phiếu điều chuyển tồn kho.</div>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark rounded-pill">Chờ tiếp nhận: {{ number_format($pendingCount) }}</span>
        <a href="{{ route('warehouse.inventory-transfers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i>Đến trang tạo phiếu
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($transfers->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted mb-0">Không có phiếu điều chuyển tồn kho nào.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($transfers as $transfer)
            @php
                $isPending = (string) $transfer->status === 'pending_receive';
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $transfer->transfer_code ?? ('#' . $transfer->id) }}</div>
                            <div class="small text-muted">
                                Từ kho <strong>{{ $transfer->sourceWarehouse?->name ?? '—' }}</strong>
                                · {{ optional($transfer->requested_at ?? $transfer->created_at)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <span class="badge {{ $isPending ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $isPending ? 'Chờ tiếp nhận' : 'Đã tiếp nhận' }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if($transfer->note)
                            <div class="small text-muted mb-2">Ghi chú: {{ $transfer->note }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">SL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfer->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->variant?->product?->name ?? 'Sản phẩm' }} - {{ $item->variant?->name ?? 'Biến thể' }}
                                                @if($item->variant?->sku)
                                                    <span class="text-muted">({{ $item->variant->sku }})</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-semibold">{{ number_format((int) $item->quantity) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Người tạo: {{ $transfer->requester?->name ?? '—' }}
                            @if(!$isPending && $transfer->receiver)
                                <br>Người nhận: {{ $transfer->receiver->name }}
                            @endif
                        </div>
                        @if($isPending)
                            <form method="POST" action="{{ route('warehouse.inventory-transfers.confirm', $transfer) }}" onsubmit="return confirm('Xác nhận tiếp nhận và nhập kho cho phiếu này?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho
                                </button>
                            </form>
                        @else
                            <span class="small text-success">Hoàn tất lúc {{ optional($transfer->received_at)->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $transfers->links() }}
    </div>
@endif
@endsection
