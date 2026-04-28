@extends('layouts.site')

@push('styles')
<style>
    .schedule-review-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .schedule-review-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .schedule-review-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        margin-bottom: 28px;
    }
    .schedule-review-title {
        margin: 0;
        font-size: 1.9rem;
        font-weight: 800;
        color: #0f172a;
    }
    .schedule-review-subtitle {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 0.95rem;
    }
    .schedule-review-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .schedule-review-panel-head {
        padding: 20px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
    }
    .schedule-review-badges {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .schedule-review-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .schedule-review-badge-pending {
        background: #fef3c7;
        color: #92400e;
    }
    .schedule-review-badge-need-review {
        background: #fee2e2;
        color: #991b1b;
    }
    .schedule-review-badge-ok {
        background: #dcfce7;
        color: #166534;
    }
    .schedule-review-badge-changed {
        background: #fed7aa;
        color: #92400e;
    }
    .schedule-review-badge-insufficient {
        background: #fecaca;
        color: #991b1b;
    }
    .schedule-review-table-wrap {
        overflow-x: auto;
    }
    .schedule-review-table {
        margin-bottom: 0;
        min-width: 100%;
    }
    .schedule-review-table th {
        background: #eef3f9;
        padding: 14px;
        font-weight: 700;
        font-size: 0.78rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #dbe4ef;
    }
    .schedule-review-table td {
        padding: 16px 14px;
        border-bottom: 1px solid #e5eaf3;
        vertical-align: middle;
    }
    .schedule-review-table tr:last-child td {
        border-bottom: none;
    }
    .schedule-review-product-name {
        font-weight: 700;
        color: #0f172a;
    }
    .schedule-review-product-sku {
        font-size: 0.82rem;
        color: #64748b;
    }
    .schedule-review-value {
        font-weight: 700;
        color: #0f172a;
        text-align: center;
    }
    .schedule-review-select {
        width: 100%;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid #d8deea;
        border-radius: 8px;
        font-size: 0.9rem;
    }
    .schedule-review-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    .schedule-review-input {
        width: 100%;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid #d8deea;
        border-radius: 8px;
        font-size: 0.9rem;
        text-align: center;
    }
    .schedule-review-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    .schedule-review-actions {
        padding: 20px 24px;
        background: #f8fafc;
        border-top: 1px solid #eef2f7;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .schedule-review-actions .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 11px 24px;
    }
    .schedule-review-text-danger {
        color: #dc2626;
        font-weight: 700;
    }
    .schedule-review-text-warning {
        color: #ca8a04;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="schedule-review-page">
    <div class="schedule-review-shell">
        <div class="schedule-review-head">
            <div>
                <h1 class="schedule-review-title">Review lịch #{{ $schedule->id }}</h1>
                <p class="schedule-review-subtitle">Ngày giao: <strong>{{ optional($schedule->schedule_date)->format('d/m/Y') }}</strong> | Khách: <strong>{{ $schedule->customer->name ?? 'N/A' }}</strong></p>
            </div>
            <a href="{{ route('my_customer.schedules.index') }}" class="btn btn-outline-secondary" style="border-radius: 12px; font-weight: 700; padding: 10px 20px;">
                <i class="bi bi-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 rounded-3 mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="schedule-review-panel">
            <div class="schedule-review-panel-head">
                <div class="schedule-review-badges">
                    <span class="schedule-review-badge schedule-review-badge-{{ str_replace('_', '-', $schedule->status) }}">Trạng thái: {{ $schedule->status }}</span>
                    <span class="schedule-review-badge {{ $schedule->price_status === 'changed' ? 'schedule-review-badge-changed' : 'schedule-review-badge-ok' }}">Giá: {{ $schedule->price_status }}</span>
                    <span class="schedule-review-badge {{ $schedule->stock_status === 'insufficient' ? 'schedule-review-badge-insufficient' : 'schedule-review-badge-ok' }}">Tồn: {{ $schedule->stock_status }}</span>
                    @if($schedule->generated_order_id)
                        <span class="schedule-review-badge" style="background: #dbeafe; color: #0c4a6e;">Đơn #{{ $schedule->generated_order_id }}</span>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('my_customer.schedules.generate', $schedule) }}">
            @csrf

            <div class="schedule-review-panel">
                <div class="schedule-review-table-wrap">
                    <table class="schedule-review-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style="text-align: center;">SL lịch</th>
                                <th style="text-align: center;">Tồn hiện tại</th>
                                <th style="text-align: right;">Giá lịch</th>
                                <th style="text-align: right;">Giá hiện tại</th>
                                <th style="text-align: center;">Quyết định</th>
                                <th style="text-align: center;">SL duyệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedule->items as $item)
                                <tr>
                                    <td>
                                        <div class="schedule-review-product-name">{{ $item->variant->product->name ?? 'N/A' }}</div>
                                        <div class="schedule-review-product-sku">{{ $item->variant->sku ?? ('#' . $item->product_variant_id) }}</div>
                                    </td>
                                    <td class="schedule-review-value">{{ $item->quantity }}</td>
                                    <td class="schedule-review-value {{ $item->stock_diff ? 'schedule-review-text-danger' : '' }}">{{ $item->stock_available }}</td>
                                    <td style="text-align: right; font-weight: 600; color: #0f172a;">{{ number_format((float) $item->scheduled_price, 0, ',', '.') }}₫</td>
                                    <td style="text-align: right; font-weight: 600;" class="{{ $item->price_diff ? 'schedule-review-text-warning' : 'text-success' }}">{{ number_format((float) ($item->current_price ?? 0), 0, ',', '.') }}₫</td>
                                    <td>
                                        <select class="schedule-review-select" name="decision[{{ $item->id }}][action]">
                                            <option value="keep">Giữ lượng</option>
                                            <option value="adjust" {{ $item->stock_diff ? 'selected' : '' }}>Giảm theo tồn</option>
                                            <option value="remove">Bỏ sản phẩm</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number"
                                               class="schedule-review-input"
                                               min="0"
                                               max="{{ max(0, $item->stock_available) }}"
                                               value="{{ min($item->quantity, max(0, $item->stock_available)) }}"
                                               name="decision[{{ $item->id }}][approved_quantity]">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                                        <p class="mb-0 mt-2">Không có sản phẩm trong lịch này</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="schedule-review-actions">
                    <button type="submit" name="no_generate" value="1" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Không tạo đơn
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Tạo đơn theo review
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
