@extends('layouts.accounting')

@section('title', 'Thông báo điều chỉnh đơn')
@section('subtitle', 'Các thay đổi giá và số lượng đang chờ Kế toán xác nhận')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1"><i class="bi bi-bell-fill text-warning me-2"></i>Số liệu cần duyệt</h5>
                <div class="text-muted small">Chỉ hiển thị yêu cầu đã qua Leader và Manager, hiện đang chờ bước Kế toán.</div>
            </div>
            <span class="badge text-bg-warning fs-6">{{ $adjustments->total() }} yêu cầu</span>
        </div>
        <form method="GET" class="row g-2 mt-2">
            <div class="col-md-9">
                <input class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Tìm mã đơn, khách hàng, sale hoặc mã yêu cầu">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Tìm</button>
                <a class="btn btn-outline-secondary" href="{{ route('accounting.order-adjustments') }}">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<div class="d-grid gap-3">
@forelse($adjustments as $adjustment)
    <article class="acc-card" id="adjustment-{{ $adjustment->id }}">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <div class="fw-bold fs-5">Yêu cầu #{{ $adjustment->id }} · {{ $adjustment->order?->code ?? ('Đơn #'.$adjustment->order_id) }}</div>
                    <div class="text-muted small">
                        {{ $adjustment->order?->customer?->name ?? 'Khách hàng' }} · Sale: {{ $adjustment->order?->user?->name ?? '—' }} · Gửi bởi: {{ $adjustment->requester?->name ?? '—' }}
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge text-bg-warning">Chờ Kế toán duyệt</span>
                    <div class="small text-muted mt-1">{{ optional($adjustment->submitted_at)->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            @if($adjustment->adjustment_note)
                <div class="alert alert-light border py-2"><strong>Lý do điều chỉnh:</strong> {{ $adjustment->adjustment_note }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Sản phẩm</th><th class="text-end">SL cũ</th><th class="text-end">SL mới</th><th class="text-end">Giá cũ</th><th class="text-end">Giá mới</th><th>Nội dung thay đổi</th></tr></thead>
                    <tbody>
                    @foreach($adjustment->items as $item)
                        @php
                            $quantityChanged = (float) $item->original_quantity !== (float) $item->adjusted_quantity;
                            $priceChanged = (float) $item->original_price !== (float) $item->adjusted_price;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm' }}</td>
                            <td class="text-end">{{ number_format((float) $item->original_quantity, 0, ',', '.') }}</td>
                            <td class="text-end {{ $quantityChanged ? 'text-danger fw-bold' : '' }}">{{ number_format((float) $item->adjusted_quantity, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $item->original_price, 0, ',', '.') }}đ</td>
                            <td class="text-end {{ $priceChanged ? 'text-danger fw-bold' : '' }}">{{ number_format((float) $item->adjusted_price, 0, ',', '.') }}đ</td>
                            <td>
                                @if($quantityChanged)<span class="badge text-bg-info">Số lượng</span>@endif
                                @if($priceChanged)<span class="badge text-bg-danger">Giá bán</span>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-outline-primary btn-sm">Xem chi tiết</a>
                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#rejectAccountingAdjustment{{ $adjustment->id }}">Từ chối</button>
                <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                    @csrf
                    <button class="btn btn-success btn-sm" onclick="return confirm('Kế toán xác nhận duyệt yêu cầu #{{ $adjustment->id }}?')"><i class="bi bi-check2-circle me-1"></i>Xác nhận và duyệt</button>
                </form>
            </div>
            <div class="collapse mt-3" id="rejectAccountingAdjustment{{ $adjustment->id }}">
                <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="border rounded p-3 bg-light">
                    @csrf
                    <label class="form-label fw-semibold">Lý do từ chối</label>
                    <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                    <button class="btn btn-danger btn-sm">Xác nhận từ chối</button>
                </form>
            </div>
        </div>
    </article>
@empty
    <div class="acc-card"><div class="card-body text-center text-muted py-5"><i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>Không có yêu cầu điều chỉnh nào đang chờ Kế toán.</div></div>
@endforelse
</div>

<div class="mt-3">{{ $adjustments->links() }}</div>
@endsection
