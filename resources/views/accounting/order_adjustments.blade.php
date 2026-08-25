@extends('layouts.accounting')

@section('title', 'Duyệt điều chỉnh đơn')
@section('subtitle', 'Danh sách yêu cầu thuộc bước phê duyệt của Kế toán')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1"><i class="bi bi-clipboard-check text-primary me-2"></i>Danh sách Kế toán duyệt</h5>
                <div class="text-muted small">Yêu cầu chờ xử lý và lịch sử Kế toán đã duyệt hoặc từ chối.</div>
            </div>
            <span class="badge {{ $status === 'pending' ? 'text-bg-warning' : 'text-bg-primary' }} fs-6">{{ $adjustments->total() }} yêu cầu</span>
        </div>

        <div class="nav nav-pills gap-2 mt-3">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}">
                Chờ duyệt <span class="badge {{ $status === 'pending' ? 'text-bg-light text-dark' : 'text-bg-warning' }} ms-1">{{ $pendingCount }}</span>
            </a>
            <a class="nav-link {{ $status === 'processed' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['status' => 'processed', 'page' => 1]) }}">
                Đã xử lý <span class="badge {{ $status === 'processed' ? 'text-bg-light text-dark' : 'text-bg-secondary' }} ms-1">{{ $processedCount }}</span>
            </a>
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}">
                Tất cả
            </a>
        </div>

        <form method="GET" class="row g-2 mt-2">
            <input type="hidden" name="status" value="{{ $status }}">
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
    @php
        $accountingReview = $adjustment->relationLoaded('accountingReview') ? $adjustment->accountingReview : null;
        $isPendingAccounting = $accountingReview === null;
    @endphp
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
                    @if($isPendingAccounting)
                        <span class="badge text-bg-warning">Chờ Kế toán duyệt</span>
                        <div class="small text-muted mt-1">Gửi {{ optional($adjustment->submitted_at)->format('d/m/Y H:i') }}</div>
                    @else
                        <span class="badge {{ $accountingReview->status === 'approved' ? 'text-bg-success' : 'text-bg-danger' }}">
                            {{ $accountingReview->status === 'approved' ? 'Kế toán đã duyệt' : 'Kế toán đã từ chối' }}
                        </span>
                        <div class="small text-muted mt-1">
                            {{ $accountingReview->approver?->name ?? 'Kế toán' }} · {{ optional($accountingReview->approved_at)->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>

            @if($accountingReview?->note)
                <div class="alert {{ $accountingReview->status === 'approved' ? 'alert-success' : 'alert-danger' }} py-2">
                    <strong>Ý kiến Kế toán:</strong> {{ $accountingReview->note }}
                </div>
            @endif

            @if($adjustment->adjustment_note)
                <div class="alert alert-light border py-2"><strong>Lý do điều chỉnh:</strong> {{ $adjustment->adjustment_note }}</div>
            @endif
            @include('site.orders.adjustments._fee_changes', ['adjustment' => $adjustment])

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
                @if($isPendingAccounting)
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#rejectAccountingAdjustment{{ $adjustment->id }}">Từ chối</button>
                    <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" onclick="return confirm('Kế toán xác nhận duyệt yêu cầu #{{ $adjustment->id }}?')"><i class="bi bi-check2-circle me-1"></i>Xác nhận và duyệt</button>
                    </form>
                @endif
            </div>
            @if($isPendingAccounting)
                <div class="collapse mt-3" id="rejectAccountingAdjustment{{ $adjustment->id }}">
                    <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="border rounded p-3 bg-light">
                        @csrf
                        <label class="form-label fw-semibold">Lý do từ chối</label>
                        <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                        <button class="btn btn-danger btn-sm">Xác nhận từ chối</button>
                    </form>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="acc-card"><div class="card-body text-center text-muted py-5"><i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>{{ $status === 'pending' ? 'Không có yêu cầu điều chỉnh nào đang chờ Kế toán.' : 'Chưa có yêu cầu nào trong danh sách này.' }}</div></div>
@endforelse
</div>

<div class="mt-3">{{ $adjustments->links() }}</div>
@endsection
