@extends('layouts.ceo')

@section('title', 'Thống Kê Bán Hàng')
@section('subtitle', 'Thống kê chi tiết từng dòng hàng hóa đã bán theo ngày')

@push('styles')
<style>
/* ── filter bar ── */
.ds-filter { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 10px; align-items: end; }
@media (max-width:1200px){ .ds-filter { grid-template-columns: repeat(3, minmax(0,1fr)); } }
@media (max-width:768px) { .ds-filter { grid-template-columns: repeat(2, minmax(0,1fr)); } }

/* ── KPI strip ── */
.ds-kpi { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
@media (max-width:992px){ .ds-kpi { grid-template-columns:repeat(2,minmax(0,1fr)); } }
.ds-kpi-item { border:1px solid var(--acc-line,#e2e8f0); border-radius:12px; background:#fff; padding:12px 14px; }
.ds-kpi-item .lbl { color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
.ds-kpi-item .val { font-size:20px; font-weight:800; color:#1e293b; margin-top:3px; }
.ds-kpi-item .sub { font-size:11px; color:#94a3b8; margin-top:1px; }

/* ── product stats ── */
.ds-prod-grid { display:grid; gap:6px; margin-top:8px; }
.ds-prod-row  { display:grid; grid-template-columns:32px 2fr 80px 80px 100px 60px; gap:6px; border-radius:8px; padding:5px 8px; font-size:.78rem; align-items:center; }
.ds-prod-head { background:#eef2f7; font-weight:700; font-size:.7rem; text-transform:uppercase; color:#64748b; letter-spacing:.03em; }
.ds-prod-body { background:#f8fafc; border:1px solid #e5edf7; }
@media (max-width:768px){
    .ds-prod-row { grid-template-columns:24px 2fr 60px 60px; }
    .ds-prod-row .hide-sm { display:none; }
}

/* ── table ── */
.ds-table { font-size:.8rem; }
.ds-table thead th { font-size:.7rem; text-transform:uppercase; color:#64748b; letter-spacing:.03em; white-space:nowrap; }
.ds-table td { vertical-align:middle; white-space:nowrap; }
.ds-adj-badge { font-size:9px; padding:1px 4px; border-radius:4px; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; vertical-align:middle; }
.ds-customer { font-weight:600; color:#1e293b; }
.ds-customer-code { font-size:10px; color:#94a3b8; }
.sort-link { color:inherit; text-decoration:none; white-space:nowrap; }
.sort-link:hover { color:#3b82f6; }
.sort-link .bi { font-size:.65rem; opacity:.5; }
.sort-link.active .bi { opacity:1; color:#3b82f6; }

/* ── toolbar ── */
.ds-toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:space-between; margin-bottom:10px; }

/* ── card ── */
.acc-card {
    border: 1px solid var(--acc-line); 
    background: var(--acc-panel);
    box-shadow: 0 8px 20px rgba(15,23,42,0.04);
}
.acc-card .card-body { padding: 16px; }
</style>
@endpush

@section('content')

{{-- ── Filter ─────────────────────────────────────────────────────── --}}
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" id="filterForm">
            <div class="ds-filter">
                <div>
                    <label class="form-label small fw-semibold">Từ ngày</label>
                    <input type="date" class="form-control form-control-sm" name="from_date"
                           value="{{ $fromDate }}" max="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label small fw-semibold">Đến ngày</label>
                    <input type="date" class="form-control form-control-sm" name="to_date"
                           value="{{ $toDate }}" max="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label small fw-semibold">Sale</label>
                    <select class="form-select form-select-sm" name="sale_id">
                        <option value="0">Tất cả sale</option>
                        @foreach($sales as $s)
                            <option value="{{ $s->id }}" {{ $saleId === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold">Khách hàng</label>
                    <select class="form-select form-select-sm" name="customer_id" id="customerSelect">
                        <option value="0">Tất cả khách</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $customerId === $c->id ? 'selected' : '' }}>
                                {{ $c->customer_code ? '[' . $c->customer_code . '] ' : '' }}{{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold">Sắp xếp</label>
                    <select class="form-select form-select-sm" name="sort">
                        @php
                            $sortOptions = [
                                'date_desc'    => 'Ngày mới nhất',
                                'date_asc'     => 'Ngày cũ nhất',
                                'product_asc'  => 'Hàng hóa A-Z',
                                'product_desc' => 'Hàng hóa Z-A',
                                'amount_desc'  => 'Thành tiền giảm dần',
                                'amount_asc'   => 'Thành tiền tăng dần',
                                'qty_desc'     => 'Số lượng giảm dần',
                                'qty_asc'      => 'Số lượng tăng dần',
                                'weight_desc'  => 'Khối lượng giảm dần',
                                'weight_asc'   => 'Khối lượng tăng dần',
                            ];
                        @endphp
                        @foreach($sortOptions as $val => $label)
                            <option value="{{ $val }}" {{ $sort === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold">Số dòng / trang</label>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" name="per_page">
                            @foreach([10, 20, 50, 100, 200] as $pp)
                                <option value="{{ $pp }}" {{ $perPage === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-sm px-3" type="submit">Lọc</button>
                    </div>
                </div>
            </div>

            {{-- Quick date shortcuts --}}
            <div class="d-flex gap-2 mt-2 flex-wrap">
                @php
                    $shortcuts = [
                        'Hôm nay'   => [now()->toDateString(), now()->toDateString()],
                        'Hôm qua'   => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                        '7 ngày'    => [now()->subDays(6)->toDateString(), now()->toDateString()],
                        'Tháng này' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                        'Tháng trước' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
                    ];
                @endphp
                @foreach($shortcuts as $label => [$f, $t])
                    <button type="button" class="btn btn-xs btn-outline-secondary"
                            onclick="setDates('{{ $f }}','{{ $t }}')">{{ $label }}</button>
                @endforeach
            </div>
        </form>
    </div>
</div>

@php
// Strips trailing decimal zeros: 100,00 → 100 | 320,20 → 320,2 | 321,21 → 321,21
$fmtN = fn(float $v, int $d = 3): string => rtrim(rtrim(number_format($v, $d, ',', '.'), '0'), ',');
@endphp

{{-- ── KPI ──────────────────────────────────────────────────────────── --}}
<div class="ds-kpi">
    <div class="ds-kpi-item">
        <div class="lbl">Tổng thành tiền</div>
        <div class="val text-success">{{ number_format((float)($summary->grand_total ?? 0), 0, ',', '.') }}đ</div>
        <div class="sub">Đã áp dụng điều chỉnh được duyệt</div>
    </div>
    <div class="ds-kpi-item">
        <div class="lbl">Tổng số lượng</div>
        <div class="val text-primary">{{ $fmtN((float)($summary->grand_qty ?? 0)) }}</div>
        <div class="sub">Tất cả sản phẩm</div>
    </div>
    <div class="ds-kpi-item">
        <div class="lbl">Tổng khối lượng</div>
        <div class="val text-info">{{ $fmtN((float)($summary->grand_weight ?? 0)) }} kg</div>
        <div class="sub">&nbsp;</div>
    </div>
    <div class="ds-kpi-item">
        <div class="lbl">Số dòng / đơn</div>
        <div class="val">{{ number_format((int)($summary->item_count ?? 0)) }}</div>
        <div class="sub">{{ number_format((int)($summary->order_count ?? 0)) }} đơn hàng</div>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-lg-8">
        {{-- ── Product stats ────────────────────────────────────────────────── --}}
        @if($productStats->isNotEmpty())
        <div class="acc-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.06em;">
                        Hàng - Số lượng
                        <span class="fw-normal">({{ $productStats->count() }} sản phẩm)</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleProdStats">
                        <i class="bi bi-chevron-expand"></i> Chi tiết
                    </button>
                </div>

                <div id="prodStatsWrap" class="d-none">
                    <div class="ds-prod-grid mt-2">
                        <div class="ds-prod-row ds-prod-head">
                            <div>STT</div>
                            <div>Sản phẩm</div>
                            <div>Số lượng</div>
                            <div class="hide-sm">Khối lượng</div>
                            <div>Thành tiền</div>
                            <div class="hide-sm">ĐVT</div>
                        </div>
                        @foreach($productStats as $i => $ps)
                        <div class="ds-prod-row ds-prod-body">
                            <div class="text-muted">{{ $i + 1 }}</div>
                            <div class="fw-semibold">{{ $ps->product_name }}</div>
                            <div class="text-primary fw-bold">
                                {{ $fmtN((float)$ps->total_qty) }}
                            </div>
                            <div class="text-muted hide-sm">
                                {{ $fmtN((float)$ps->total_weight) }}
                            </div>
                            <div class="fw-semibold">{{ number_format((float)$ps->total_amount, 0, ',', '.') }}đ</div>
                            <div class="text-muted hide-sm">{{ \App\Enums\ProductUnit::tryFrom($ps->product_unit ?? '')?->label() ?? 'Cái' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Data table ───────────────────────────────────────────────────── --}}
        <div class="acc-card">
            <div class="card-body">
                {{-- Toolbar --}}
                <div class="ds-toolbar">
                    <div class="small text-muted">
                        Trang {{ $items->currentPage() }} / {{ $items->lastPage() }} —
                        tổng <strong>{{ number_format($items->total()) }}</strong> dòng
                        @if($fromDate !== $toDate)
                            ({{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }})
                        @else
                            ({{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }})
                        @endif
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-exclamation-circle text-warning"></i>
                        <span class="ds-adj-badge">Đ/C</span> = số liệu đã được điều chỉnh duyệt
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle ds-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'date_asc' ? 'date_desc' : 'date_asc', 'page' => 1]) }}"
                                    class="sort-link {{ in_array($sort, ['date_asc','date_desc']) ? 'active' : '' }}">
                                        Ngày
                                        <i class="bi bi-{{ $sort === 'date_asc' ? 'sort-up' : ($sort === 'date_desc' ? 'sort-down' : 'sort') }}"></i>
                                    </a>
                                </th>
                            
                                <th>Sale</th>
                                <th>Khách hàng</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'product_asc' ? 'product_desc' : 'product_asc', 'page' => 1]) }}"
                                    class="sort-link {{ in_array($sort, ['product_asc','product_desc']) ? 'active' : '' }}">
                                        Hàng hóa
                                        <i class="bi bi-{{ $sort === 'product_asc' ? 'sort-up' : ($sort === 'product_desc' ? 'sort-down' : 'sort') }}"></i>
                                    </a>
                                </th>
                                <th>Size</th>
                                <th class="text-center">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'qty_asc' ? 'qty_desc' : 'qty_asc', 'page' => 1]) }}"
                                    class="sort-link {{ in_array($sort, ['qty_asc','qty_desc']) ? 'active' : '' }}">
                                        Số lượng
                                        <i class="bi bi-{{ $sort === 'qty_asc' ? 'sort-up' : ($sort === 'qty_desc' ? 'sort-down' : 'sort') }}"></i>
                                    </a>
                                </th>
                                <th>ĐVT</th>
                                <th class="text-center">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'weight_asc' ? 'weight_desc' : 'weight_asc', 'page' => 1]) }}"
                                    class="sort-link {{ in_array($sort, ['weight_asc','weight_desc']) ? 'active' : '' }}">
                                        Khối lượng
                                        <i class="bi bi-{{ $sort === 'weight_asc' ? 'sort-up' : ($sort === 'weight_desc' ? 'sort-down' : 'sort') }}"></i>
                                    </a>
                                </th>
                                <th class="text-center">Đơn giá </th>
                                <th class="text-end">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'amount_asc' ? 'amount_desc' : 'amount_asc', 'page' => 1]) }}"
                                    class="sort-link {{ in_array($sort, ['amount_asc','amount_desc']) ? 'active' : '' }}">
                                        Thành tiền
                                        <i class="bi bi-{{ $sort === 'amount_asc' ? 'sort-up' : ($sort === 'amount_desc' ? 'sort-down' : 'sort') }}"></i>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $rowNo = ($items->currentPage() - 1) * $items->perPage() + 1; @endphp
                        @forelse($items as $row)
                            @php
                                $adjFlag   = (bool) $row->has_adj;
                                $effQty    = (float) $row->eff_qty;
                                $effPrice  = (float) $row->eff_price;
                                $effWeight = (float) $row->eff_weight;
                                $effTotal  = (float) $row->eff_total;

                                $unitLabel = \App\Enums\ProductUnit::tryFrom($row->product_unit ?? '')?->label() ?? 'Cái';

                                // Display weight: show kg if priced by kg or weight > 0
                                $showWeight = $effWeight > 0 ? $fmtN($effWeight) : '—';
                                $showQty    = $fmtN($effQty);

                                // Short customer name: use customer_code if exists, else first 2 words of name
                                $custShort = trim($row->customer_code ?? '');
                                if (!$custShort && $row->customer_name) {
                                    $parts = explode(' ', trim($row->customer_name));
                                    $custShort = implode(' ', array_slice($parts, -2)); // last 2 words
                                }

                                // Variant label: size + name
                                $variantLabel = trim(($row->variant_size ?? '') . ' ' . ($row->variant_name ?? ''));
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $rowNo++ }}</td>
                                <td>
                                    <a href="{{ route('orders.show', $row->order_id_val) }}" class="text-decoration-none small" target="_blank">
                                        {{ \Carbon\Carbon::parse($row->order_date)->format('d/m') }}
                                    </a>
                                    
                                </td>
                                
                                <td class="text-muted">{{ $row->sale_name ?? '—' }}</td>
                                <td>
                                    <div class="ds-customer" title="{{ $row->customer_name }}">{{ $custShort }}</div>
                                    @if($row->customer_code && $row->customer_name !== $custShort)
                                        <div class="ds-customer-code">{{ $row->customer_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold" style="max-width:160px">{{ $row->product_name }}</div>
                                    
                                </td>
                                <td class="text-muted small">
                                    {{ $row->variant_size ?: '—' }}
                                </td>
                                <td class="text-center text-primary fw-bold">
                                    <span class="fw-semibold">{{ $showQty }}</span>
                                </td>
                                <td class="text-muted small">{{ $unitLabel }}</td>
                                <td class="text-center text-muted">{{ $showWeight }}</td>
                                <td class="text-center">
                                    {{ number_format($effPrice, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ number_format($effTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    Không có dữ liệu cho bộ lọc này.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        @if($items->isNotEmpty())
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="6" class="text-end text-muted small">Tổng trang này:</td>
                                <td class="text-end">
                                    {{ $fmtN((float)$items->sum('eff_qty')) }}
                                </td>
                                <td></td>
                                <td class="text-end">
                                    {{ $fmtN((float)$items->sum('eff_weight')) }}
                                </td>
                                <td></td>
                                <td class="text-end text-success">
                                    {{ number_format($items->sum('eff_total'), 0, ',', '.') }}đ
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Hiển thị {{ $items->firstItem() }}–{{ $items->lastItem() }} / {{ number_format($items->total()) }} dòng
                    </div>
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="position: sticky; top: 20px;">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <div class="fw-semibold text-muted small mb-0">THỐNG KÊ SALE</div>
                <div class="small text-muted" style="font-size:.8rem;">
                    Từ: <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }}</strong> 
                    — <strong>{{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</strong>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: calc(100vh - 360px); overflow-y: auto;">
                @forelse($salesStats as $idx => $stat)
                    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:{{ $loop->odd ? '#fff' : '#f8faff' }};">
                        <div class="text-muted small fw-semibold" style="width:24px;text-align:center;">{{ $idx + 1 }}</div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate" style="font-size:.9rem;color:#1e293b;">{{ $stat['sale_name'] }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted" style="font-size:.75rem;">{{ number_format($stat['order_count']) }} đơn</div>
                            <div class="fw-bold text-success" style="font-size:.9rem;">{{ number_format((int)$stat['total_value'], 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted small">
                        Chưa có dữ liệu sale
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Date shortcuts
function setDates(from, to) {
    document.querySelector('[name=from_date]').value = from;
    document.querySelector('[name=to_date]').value   = to;
    document.getElementById('filterForm').submit();
}

// Product stats toggle
document.getElementById('toggleProdStats')?.addEventListener('click', function () {
    const wrap = document.getElementById('prodStatsWrap');
    const isHidden = wrap.classList.contains('d-none');
    wrap.classList.toggle('d-none', !isHidden);
    this.innerHTML = isHidden
        ? '<i class="bi bi-chevron-contract"></i> Thu gọn'
        : '<i class="bi bi-chevron-expand"></i> Chi tiết';
});
</script>
@endpush
