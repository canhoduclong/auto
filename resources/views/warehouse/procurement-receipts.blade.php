@extends('layouts.warehouse')
@section('title', 'Nhập kho từ thu mua')
@section('subtitle', 'Tiếp nhận chuyến thu mua, đối chiếu thực nhập và đánh giá chất lượng')
@push('styles')
<style>
    .trip-summary{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px}.rating-select{color:#b45309;font-weight:700}.cost-pill{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}.received-summary{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px}
</style>
@endpush
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div><strong>Yêu cầu nhập kho từ phòng thu mua</strong><div class="small text-muted">Kho đánh giá trực tiếp từng chuyến sau khi kiểm đếm.</div></div>
        <span class="badge bg-primary">{{ $purchases->total() }} chuyến</span>
    </div>
    <div class="accordion" id="receipts">
        @forelse($purchases as $p)
            @php
                $receivedItems = $p->items->where('stage', 'received');
                $receivedQty = $receivedItems->whereIn('item_type', ['processed_duck', 'reject'])->sum('quantity');
                $rejectQty = $receivedItems->where('item_type', 'reject')->sum('quantity');
            @endphp
            <div class="accordion-item border-start-0 border-end-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#p{{ $p->id }}">
                        <span class="fw-bold me-3">{{ $p->code }}</span>
                        <span>{{ $p->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt thịt' }} · {{ $p->farm?->name ?? $p->supplier?->name }} · {{ number_format($p->quantity) }} con / {{ number_format($p->total_weight, 1) }}kg</span>
                        <span class="badge ms-auto me-3 {{ $p->status === 'received' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $p->status === 'received' ? 'Đã nhập & đánh giá' : 'Chờ tiếp nhận' }}</span>
                    </button>
                </h2>
                <div id="p{{ $p->id }}" class="accordion-collapse collapse" data-bs-parent="#receipts">
                    <div class="accordion-body">
                        <div class="trip-summary p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-md-3"><div class="small text-muted">Thời gian thu mua</div><strong>{{ $p->purchased_at->format('d/m/Y H:i') }}</strong></div>
                                <div class="col-md-3"><div class="small text-muted">Nguồn hàng</div><strong>{{ $p->farm?->name ?? $p->supplier?->name }}</strong></div>
                                <div class="col-md-3"><div class="small text-muted">Chi phí thu mua chuyến</div><span class="badge cost-pill">{{ number_format($p->procurement_fee) }}đ</span></div>
                                <div class="col-md-3"><div class="small text-muted">Chi phí vận chuyển</div><span class="badge cost-pill">{{ number_format($p->transportation_fee) }}đ</span></div>
                                <div class="col-12"><div class="small text-muted">Ghi chú từ thu mua</div>{{ $p->notes ?: 'Không có ghi chú.' }}</div>
                            </div>
                        </div>
                        <div class="mb-3"><strong>Dự kiến từ thu mua:</strong>
                            @forelse($p->items->where('stage', 'expected') as $i)
                                <span class="badge bg-light text-dark border me-1">{{ $i->item_type === 'processed_duck' ? 'Size '.$i->size : ($i->item_type === 'feathers' ? 'Lông' : 'Lòng') }}: {{ number_format($i->quantity) }}</span>
                            @empty <span class="text-muted">Chưa có phân loại dự kiến.</span> @endforelse
                        </div>
                        @if($p->status === 'sent_to_warehouse')
                            <form method="POST" action="{{ route('warehouse.procurement-receipts.receive', $p) }}">@csrf
                                <div class="border rounded-3 p-3 mb-3">
                                    <h6 class="fw-bold"><i class="bi bi-star me-1"></i>Đánh giá chuyến thu mua</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3"><label class="form-label">Mức đánh giá</label><select name="warehouse_rating" class="form-select rating-select">@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }} ({{ $i }}/5)</option>@endfor</select></div>
                                        <div class="col-md-4"><label class="form-label">Tình trạng tổng thể</label><input name="warehouse_condition" class="form-control" placeholder="Tươi, đồng đều / gầy, lỗi nhiều..." required></div>
                                        <div class="col-md-5"><label class="form-label">Nhận xét & kiến nghị</label><input name="warehouse_comment" class="form-control" placeholder="Phản hồi cho bộ phận thu mua"></div>
                                    </div>
                                </div>
                                <h6 class="fw-bold">Chi tiết thực nhập vịt sơ chế</h6>
                                <div id="items{{ $p->id }}">
                                    @foreach([2.0,2.2,2.3,2.4,2.5,2.6,2.7,2.8,2.9,3.0,3.1,3.2] as $idx=>$size)
                                        <div class="row g-2 mb-1"><input type="hidden" name="items[{{ $idx }}][item_type]" value="processed_duck"><input type="hidden" name="items[{{ $idx }}][size]" value="{{ $size }}"><div class="col-2"><span class="form-control-plaintext">Size {{ $size }}</span></div><div class="col-3"><input type="number" min="0" value="0" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" placeholder="Số con"></div><div class="col-3"><input type="number" min="0" step=".001" value="0" name="items[{{ $idx }}][weight]" class="form-control form-control-sm" placeholder="Kg"></div><div class="col-4"><input name="items[{{ $idx }}][condition]" class="form-control form-control-sm" placeholder="Tình trạng"></div></div>
                                    @endforeach
                                    @foreach(['feathers'=>'Bộ lông','offal'=>'Bộ lòng','reject'=>'Vịt loại / lỗi'] as $type=>$label)@php $idx=20+$loop->index; @endphp
                                        <div class="row g-2 mb-1"><input type="hidden" name="items[{{ $idx }}][item_type]" value="{{ $type }}"><div class="col-2"><span class="form-control-plaintext">{{ $label }}</span></div><div class="col-3"><input type="number" min="0" value="0" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm"></div><div class="col-3"><input type="number" min="0" step=".001" value="0" name="items[{{ $idx }}][weight]" class="form-control form-control-sm"></div><div class="col-4"><input name="items[{{ $idx }}][condition]" class="form-control form-control-sm"></div></div>
                                    @endforeach
                                </div>
                                <button class="btn btn-success mt-3"><i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho & gửi đánh giá</button>
                            </form>
                        @else
                            <div class="received-summary p-3">
                                <div class="d-flex justify-content-between gap-3 flex-wrap"><strong>Kho đã đánh giá: <span class="text-warning">{{ str_repeat('★', (int) $p->warehouse_rating) }}</span></strong><span>{{ $p->received_at?->format('d/m/Y H:i') }}</span></div>
                                <div class="mt-2">{{ $p->warehouse_condition }} @if($p->warehouse_comment) — {{ $p->warehouse_comment }} @endif</div>
                                <div class="small text-muted mt-2">Thực nhập {{ number_format($receivedQty) }} đơn vị · Hàng loại {{ number_format($rejectQty) }} con</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-5 text-center text-muted">Chưa có phiếu thu mua gửi đến kho.</div>
        @endforelse
    </div>
    <div class="card-footer">{{ $purchases->links() }}</div>
</div>
@endsection
