@extends('layouts.shipper')

@section('title', 'Điều chuyển kho')
@section('subtitle', 'Nhận - giao hàng điều chuyển giữa các kho')

@section('content')


@php
    // $transfers đã được lọc theo ngày và unique ở controller
    $pendingPickupList = $transfers->where('status', 'pending_shipper_pickup')->values();
    $inTransitList = $transfers->where('status', 'in_transit')->values();
    $waitingReceiveList = $transfers->where('status', 'delivered_waiting_receive')->values();
    $completedList = $transfers->where('status', 'received_completed')->values();
    $isManagerShipper = auth()->user()?->hasRole('manager_shipper') || auth()->user()?->hasRole('admin');
    $allTransfers = $pendingPickupList->concat($inTransitList)->concat($waitingReceiveList)->concat($completedList);
    $selectedDate = $today ?? null;
@endphp

@if($selectedDate)
    <div class="alert alert-info py-2 mb-2">
        <i class="bi bi-calendar-event me-1"></i>
        Đang hiển thị phiếu điều chuyển ngày <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>
    </div>
@endif

<style>
    .sticky-nav-transfer {
        position: sticky;
        top: 56px; /* height of .sp-topbar */
        z-index: 10;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 0 0 0;
        margin-bottom: 0;
    }
    .column-nav-bar {
        display: none;
    }
    @media (max-width: 991px) {
        .column-nav-bar {
            display: flex;
            position: sticky;
            top: 56px;
            z-index: 9;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem 0;
            margin-bottom: 0;
            gap: 0.5rem;
            justify-content: flex-start;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .column-nav-bar::-webkit-scrollbar { display: none; }
    }
    .column-nav-btn {
        border: none;
        color: #334155;
        font-weight: 600;
        border-radius: 20px;
        padding: 0.5rem 1.1rem;
        font-size: 1rem;
        transition: background .18s, color .18s;
        cursor: pointer;
        outline: none;
        background: #e2e8f0;
    }
    .column-nav-btn[data-col="pending"] {
        background: #d1fae5;
        color: var(--theme-primary);
    }
    .column-nav-btn[data-col="transit"] {
        background: #fff7e6;
        color: #f59e42;
    }
    .column-nav-btn[data-col="waiting"] {
        background: #e0f2fe;
        color: #38bdf8;
    }
    .column-nav-btn[data-col="completed"] {
        background: #f1f5f9;
        color: #64748b;
    }
    .column-nav-btn.active, .column-nav-btn:focus {
        background: var(--theme-primary);
        color: #fff !important;
    }
    @media (max-width: 991px) {
        .column-nav-bar { top: 104px; }
        .column-nav-btn { font-size: 0.95rem; padding: 0.45rem 0.9rem; }
    }
    .transfer-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        font-weight: 600;
        font-size: 1.1rem;
        margin-right: .5rem;
        margin-bottom: .5rem;
        border: 2px solid transparent;
        transition: border-color .2s, box-shadow .2s, background .2s;
        cursor: pointer;
    }
    .transfer-nav-pill.active {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 2px #bae6fd;
    }
    .transfer-nav-pill.status-pending {
        background: var(--theme-primary) !important; color: #fff;
    }
    .transfer-nav-pill.status-transit {
        background: #f59e42; color: #fff;
    }
    .transfer-nav-pill.status-waiting {
        background: #38bdf8; color: #fff;
    }
    .transfer-nav-pill.status-completed {
        background: #cbd5e1 !important; color: #64748b !important;
    }
    @media (max-width: 1200px) {
        .transfer-board-row { flex-wrap: wrap; }
        .transfer-board-col { flex: 0 0 100%; max-width: 100%; border-left: none !important; border-top: 1px solid #e5e7eb; }
    }
    @media (min-width: 1200px) {
        .transfer-board-row { display: flex; }
        .transfer-board-col { flex: 0 0 25%; max-width: 25%; border-left: 1px solid #e5e7eb; }
        .transfer-board-col:first-child { border-left: none !important; }
    }
</style>

<div class="sticky-nav-transfer">
    <div class="d-flex flex-wrap align-items-center">
        <span class="fw-bold text-muted me-2"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
        @foreach($allTransfers as $transfer)
            @php
                $sequence = $transfer->order?->daily_sequence ?? '—';
                $statusClass = match($transfer->status) {
                    'pending_shipper_pickup' => 'status-pending',
                    'in_transit' => 'status-transit',
                    'delivered_waiting_receive' => 'status-waiting',
                    'received_completed' => 'status-completed',
                    default => 'status-other',
                };
            @endphp
            <a href="#transfer-card-{{ $transfer->id }}"
               class="transfer-nav-pill {{ $statusClass }}"
               id="nav-pill-{{ $transfer->id }}"
               title="{{ $transfer->order?->customer?->name ?? 'Đơn hàng' }}">
                {{ $sequence }}
            </a>
        @endforeach
    </div>
    <nav class="column-nav-bar" id="columnNavBar">
        <button class="column-nav-btn" data-col="pending" type="button">Chờ nhận</button>
        <button class="column-nav-btn" data-col="transit" type="button">Đang vận chuyển</button>
        <button class="column-nav-btn" data-col="waiting" type="button">Đã giao chờ xác nhận</button>
        <button class="column-nav-btn" data-col="completed" type="button">Đã giao & kho xác nhận</button>
    </nav>
</div>

<div class="row transfer-board-row g-4">
    <div class="transfer-board-col" id="col-pending">
        <div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white" style="top:112px;z-index:8;">
            <h5 class="mb-0 fw-bold text-success"><i class="bi bi-box-arrow-in-down me-2"></i>Đơn hàng cần nhận</h5>
            <span class="badge bg-success rounded-pill">{{ $pendingPickupList->count() }} đơn</span>
        </div>
        <div class="d-flex flex-column gap-3">
            @forelse($pendingPickupList as $transfer)
                @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => 'pending'])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn cần nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="transfer-board-col border-start" id="col-transit">
        <div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white" style="top:112px;z-index:8;">
            <h5 class="mb-0 fw-bold text-warning"><i class="bi bi-truck me-2"></i>Đang vận chuyển</h5>
            <span class="badge bg-warning text-dark rounded-pill">{{ $inTransitList->count() }} đơn</span>
        </div>
        <div class="d-flex flex-column gap-3">
            @forelse($inTransitList as $transfer)
                @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => 'transit'])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn đang vận chuyển.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="transfer-board-col border-start" id="col-waiting">
        <div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white" style="top:112px;z-index:8;">
            <h5 class="mb-0 fw-bold text-info"><i class="bi bi-clipboard-check me-2"></i>Đã giao (chờ kho xác nhận)</h5>
            <span class="badge bg-info text-dark rounded-pill">{{ $waitingReceiveList->count() }} đơn</span>
        </div>
        <div class="d-flex flex-column gap-3">
            @forelse($waitingReceiveList as $transfer)
                @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => 'waiting'])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn đã giao chờ xác nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="transfer-board-col border-start" id="col-completed">
        <div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white" style="top:112px;z-index:8;">
            <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-clipboard-check me-2"></i>Đã giao &amp; kho đã xác nhận</h5>
            <span class="badge bg-secondary rounded-pill">{{ $completedList->count() }} đơn</span>
        </div>
        <div class="d-flex flex-column gap-3">
            @forelse($completedList as $transfer)
                @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => 'completed'])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn đã giao và kho xác nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Bulk action bar (chỉ hiện khi ở chế độ chọn nhiều) --}}
{{-- ĐÃ XOÁ HOÀN TOÀN PHẦN DANH SÁCH LẶP LẠI PHÍA DƯỚI --}}
@if($transfers->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-truck fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có phiếu điều chuyển nào.</p>
    </div>
@endif
{{-- ĐÃ XOÁ HOÀN TOÀN PHẦN CODE LẶP LẠI PHÍA DƯỚI, KHÔNG CÒN endforeach HOẶC else THỪA --}}


{{-- ĐÃ XOÁ HOÀN TOÀN PHẦN CODE LẶP LẠI PHÍA DƯỚI, KHÔNG CÒN endforeach HOẶC else THỪA --}}

{{-- Kết thúc layout, không còn code Blade dư thừa phía dưới --}}

@if($isManagerShipper)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btnToggleBulkMode');
    const bulkBar = document.getElementById('bulkActionBar');
    const checkboxWraps = document.querySelectorAll('.bulk-checkbox-wrap');
    const checkboxes = document.querySelectorAll('.js-transfer-checkbox');
    const selectedCount = document.getElementById('bulkSelectedCount');
    const btnBulkPickup = document.getElementById('btnBulkPickup');
    const btnBulkDeliver = document.getElementById('btnBulkDeliver');
    const bulkPickupIds = document.getElementById('bulkPickupIds');
    const bulkDeliverIds = document.getElementById('bulkDeliverIds');
    const bulkPickupForm = document.getElementById('bulkPickupForm');
    const bulkDeliverForm = document.getElementById('bulkDeliverForm');

    let bulkMode = false;

    function updateBulkState() {
        const checked = document.querySelectorAll('.js-transfer-checkbox:checked');
        selectedCount.textContent = checked.length;

        const pendingChecked = [...checked].filter(c => c.dataset.status === 'pending_shipper_pickup');
        const transitChecked = [...checked].filter(c => c.dataset.status === 'in_transit');

        // Update submit buttons
        btnBulkPickup.disabled = pendingChecked.length === 0;
        btnBulkDeliver.disabled = transitChecked.length === 0;

        // Sync hidden inputs for pickup form
        bulkPickupIds.innerHTML = '';
        pendingChecked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'transfer_ids[]';
            inp.value = c.value;
            bulkPickupIds.appendChild(inp);
        });

        // Sync hidden inputs for deliver form
        bulkDeliverIds.innerHTML = '';
        transitChecked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'transfer_ids[]';
            inp.value = c.value;
            bulkDeliverIds.appendChild(inp);
        });
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            bulkMode = !bulkMode;
            checkboxWraps.forEach(w => w.classList.toggle('d-none', !bulkMode));
            bulkBar.classList.toggle('d-none', !bulkMode);
            btnToggle.classList.toggle('btn-outline-primary', !bulkMode);
            btnToggle.classList.toggle('btn-primary', bulkMode);
            if (!bulkMode) {
                checkboxes.forEach(c => c.checked = false);
                updateBulkState();
            }
        });
    }

    checkboxes.forEach(c => c.addEventListener('change', updateBulkState));

    document.getElementById('btnSelectAllPending')?.addEventListener('click', function () {
        document.querySelectorAll('.js-transfer-checkbox[data-status="pending_shipper_pickup"]').forEach(c => c.checked = true);
        updateBulkState();
    });

    document.getElementById('btnSelectAllTransit')?.addEventListener('click', function () {
        document.querySelectorAll('.js-transfer-checkbox[data-status="in_transit"]').forEach(c => c.checked = true);
        updateBulkState();
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        checkboxes.forEach(c => c.checked = false);
        updateBulkState();
    });

    bulkPickupForm?.addEventListener('submit', function (e) {
        const ids = bulkPickupIds.querySelectorAll('input');
        if (ids.length === 0) { e.preventDefault(); alert('Vui lòng chọn ít nhất một phiếu ở trạng thái Chờ nhận.'); }
    });

    bulkDeliverForm?.addEventListener('submit', function (e) {
        const ids = bulkDeliverIds.querySelectorAll('input');
        if (ids.length === 0) { e.preventDefault(); alert('Vui lòng chọn ít nhất một phiếu ở trạng thái Đang vận chuyển.'); }
    });

    document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const accepted = window.confirm('Xác nhận hoàn lại phiếu điều chuyển này trước khi kho nhận xác nhận?');
            if (!accepted) return;
            const reason = window.prompt('Nhập lý do hoàn lại (không bắt buộc):', '');
            if (reason === null) return;
            const noteInput = form.querySelector('input[name="rollback_note"]');
            if (noteInput) noteInput.value = reason.trim();
            form.submit();
        });
    });
});
</script>
@endif
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sticky nav fix for mobile/desktop
    const navBar = document.querySelector('.sticky-nav-transfer');
    if (navBar) {
        navBar.style.top = (document.querySelector('.sp-topbar')?.offsetHeight || 56) + 'px';
    }
    // Column navigation
    const colNav = document.getElementById('columnNavBar');
    if (colNav) {
        const btns = colNav.querySelectorAll('.column-nav-btn');
        btns.forEach(btn => {
            btn.addEventListener('click', function () {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const colId = 'col-' + btn.dataset.col;
                const col = document.getElementById(colId);
                if (col) {
                    col.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        // Auto activate first tab
        btns[0].classList.add('active');
    }
});
</script>
@endpush
@endsection
