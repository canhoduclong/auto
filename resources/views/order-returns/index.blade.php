@extends('layouts.app')

@push('styles')
<style>
.return-page {
    padding: 24px 0 44px;
}
.return-hero {
    border-radius: 12px;
    padding: 20px 22px;
    color: #f8fafc;
    background: linear-gradient(125deg, #0f172a 0%, #1d4e89 55%, #2563eb 100%);
    box-shadow: 0 10px 25px rgba(15, 23, 42, .16);
}
.return-stat {
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 10px;
    padding: 10px 12px;
    min-width: 150px;
}
.return-stat .label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    opacity: .82;
}
.return-stat .value {
    font-size: 1.3rem;
    font-weight: 700;
    line-height: 1.15;
}
.return-panel {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
}
.return-table thead th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    white-space: nowrap;
    background: #f8fafc;
}
.return-table tbody td {
    vertical-align: middle;
    font-size: .88rem;
}
.sync-list {
    margin: 0;
    padding-left: 18px;
    font-size: .82rem;
}
.sync-list li {
    margin-bottom: 4px;
}
</style>
@endpush

@section('content')
@php
    $canCreate = auth()->check() && (auth()->user()->hasRole('sale') || auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin'));
    $canSync = auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin'));
    $warehouseReceivedOnPage = $returns->getCollection()->where('status', 'warehouse_received')->count();
@endphp

<div class="container-fluid return-page">
    <div class="return-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1 fw-bold">{{ __('order_returns.titles.index') }}</h4>
                <div style="opacity:.86;font-size:.9rem;">Theo dõi trạng thái trả hàng và đồng bộ với phiếu nhập kho.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="return-stat">
                    <div class="label">Đơn trả (trang)</div>
                    <div class="value">{{ $returns->count() }}</div>
                </div>
                <div class="return-stat">
                    <div class="label">Đã kho xác nhận</div>
                    <div class="value">{{ $warehouseReceivedOnPage }}</div>
                </div>
                <div class="return-stat">
                    <div class="label">Lệch phiếu nhập</div>
                    <div class="value">{{ $mismatchCount ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        @if($canCreate)
            <a href="{{ route('order-returns.create') }}" class="btn btn-primary">
                <i class="ph ph-plus me-1"></i>{{ __('order_returns.buttons.create') }}
            </a>
        @endif

        @if($canSync)
            <form action="{{ route('order-returns.sync-warehouse-receipts') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Chạy kiểm tra và đồng bộ phiếu nhập kho cho các đơn trả đã được kho xác nhận?')">
                    <i class="ph ph-arrows-clockwise me-1"></i>Refresh đồng bộ kho
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    @php $syncResult = session('sync_result'); @endphp
    @if(is_array($syncResult))
        <div class="alert alert-info border-0 shadow-sm">
            <div class="fw-semibold mb-1">Kết quả đồng bộ gần nhất</div>
            <div style="font-size:.9rem;">
                Đã kiểm tra {{ $syncResult['checked'] ?? 0 }} đơn, tạo mới {{ $syncResult['created_count'] ?? 0 }} phiếu, cập nhật {{ $syncResult['updated_count'] ?? 0 }} phiếu, không đổi {{ $syncResult['unchanged_count'] ?? 0 }}.
            </div>
            @if(!empty($syncResult['receipts']) && is_array($syncResult['receipts']))
                <ul class="sync-list mt-2">
                    @foreach($syncResult['receipts'] as $receipt)
                        <li>
                            Đơn trả #{{ $receipt['order_return_id'] ?? '-' }}
                            ->
                            <a href="{{ route('inventory-documents.show', $receipt['document_id']) }}" class="text-decoration-none" target="_blank">
                                {{ $receipt['document_number'] ?? ('#' . ($receipt['document_id'] ?? '')) }}
                            </a>
                            ({{ $receipt['action'] ?? 'updated' }}, chỉnh {{ $receipt['items_adjusted'] ?? 0 }} dòng)
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="return-panel mb-3">
        <div class="table-responsive">
            <table class="table return-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Đơn / Khách</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th>Phạm vi</th>
                        <th>Hoàn tiền</th>
                        <th>Phiếu nhập kho đồng bộ</th>
                        <th>Lý do</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                        @php
                            $statusLabel = [
                                'requested' => __('order_returns.statuses.requested'),
                                'ship_confirmed' => __('order_returns.statuses.ship_confirmed'),
                                'warehouse_received' => __('order_returns.statuses.warehouse_received'),
                                'cancelled' => __('order_returns.statuses.cancelled'),
                            ][$return->status] ?? $return->status;

                            $statusClass = [
                                'requested' => 'bg-warning text-dark',
                                'ship_confirmed' => 'bg-info text-dark',
                                'warehouse_received' => 'bg-success',
                                'cancelled' => 'bg-secondary',
                            ][$return->status] ?? 'bg-secondary';

                            $receipt = $receiptMap[$return->id] ?? null;
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $return->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $return->order->code ?? __('order_returns.default.na') }}</div>
                                <div class="text-muted small">{{ $return->customer->name ?? __('order_returns.default.na') }}</div>
                                <div class="text-muted" style="font-size:.74rem;">{{ $return->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>{{ $return->warehouse->name ?? __('order_returns.default.na') }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($return->return_scope === 'full')
                                    <span class="badge bg-danger">{{ __('order_returns.scopes.full') }}</span>
                                @elseif($return->return_scope === 'partial')
                                    <span class="badge bg-warning text-dark">{{ __('order_returns.scopes.partial') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                {{ number_format((float) ($return->refund_amount ?? 0), 0, ',', '.') }}
                                @if($return->refundTransaction)
                                    <div class="text-muted small">TX#{{ $return->refundTransaction->id }}</div>
                                @endif
                            </td>
                            <td>
                                @if($receipt)
                                    <a href="{{ route('inventory-documents.show', $receipt) }}" class="fw-semibold text-decoration-none" target="_blank">
                                        {{ $receipt->document_number ?? ('#' . $receipt->id) }}
                                    </a>
                                    <div class="text-muted" style="font-size:.74rem;">Cập nhật: {{ $receipt->updated_at?->format('d/m H:i') }}</div>
                                @else
                                    @if($return->status === 'warehouse_received')
                                        <span class="badge bg-danger">Lệch dữ liệu</span>
                                        <div class="text-muted" style="font-size:.74rem;">Chưa có phiếu nhập tương ứng</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-muted">{{ $return->reason ?: '-' }}</td>
                            <td>
                                <a href="{{ route('order-returns.show', $return) }}" class="btn btn-sm btn-outline-secondary mb-1">{{ __('order_returns.buttons.view') }}</a>

                                @if(auth()->check() && (auth()->user()->hasRole('ship') || auth()->user()->hasRole('admin')) && $return->status === 'requested')
                                    <form action="{{ route('order-returns.ship-confirm', $return) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info mb-1" onclick="return confirm('{{ __('order_returns.confirms.ship_confirm') }}')">{{ __('order_returns.buttons.ship_confirm') }}</button>
                                    </form>
                                @endif

                                @if(auth()->check() && (auth()->user()->hasRole('warehouse') || auth()->user()->hasRole('admin')) && $return->status === 'ship_confirmed')
                                    <form action="{{ route('order-returns.warehouse-confirm', $return) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success mb-1" onclick="return confirm('{{ __('order_returns.confirms.warehouse_confirm') }}')">{{ __('order_returns.buttons.warehouse_confirm') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Chưa có đơn trả hàng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $returns->links() }}</div>
    </div>

    <div class="return-panel">
        <div class="p-3 border-bottom fw-semibold">Lịch sử đồng bộ gần đây</div>
        <div class="p-3">
            @forelse($syncHistory as $event)
                @php
                    $meta = is_array($event->metadata) ? $event->metadata : [];
                    $receipts = data_get($meta, 'receipts', []);
                @endphp
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex flex-wrap justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $event->title }}</div>
                            <div class="text-muted" style="font-size:.78rem;">
                                {{ $event->actor->name ?? 'System' }} - {{ $event->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="text-muted" style="font-size:.78rem;">
                            Kiem tra {{ data_get($meta, 'checked', 0) }} | Tao moi {{ data_get($meta, 'created_count', 0) }} | Cap nhat {{ data_get($meta, 'updated_count', 0) }}
                        </div>
                    </div>
                    @if(is_array($receipts) && count($receipts) > 0)
                        <ul class="sync-list mt-2">
                            @foreach($receipts as $receipt)
                                <li>
                                    Don tra #{{ $receipt['order_return_id'] ?? '-' }} ->
                                    <a href="{{ route('inventory-documents.show', $receipt['document_id']) }}" target="_blank" class="text-decoration-none">
                                        {{ $receipt['document_number'] ?? ('#' . ($receipt['document_id'] ?? '')) }}
                                    </a>
                                    ({{ $receipt['action'] ?? 'updated' }})
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div class="text-muted">Chưa có lịch sử đồng bộ.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection