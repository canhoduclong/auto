@extends('layouts.app')

@section('content')
<div class="content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Theo dõi đơn hàng toàn hệ thống</h4>
            <p class="text-muted mb-0">
                @if($isAutoRefresh)
                    Đang theo dõi ngày hôm nay, hệ thống tự động làm mới mỗi 10 giây.
                @else
                    Đang theo dõi ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}, tự động làm mới đã tắt.
                @endif
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark" id="monitoringLastUpdated">Cập nhật: {{ $generatedAt->format('d/m/Y H:i:s') }}</span>
            <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">Danh sách đơn hàng</a>
        </div>
    </div>

    @if(($recentOrderDates ?? collect())->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-nowrap gap-2 overflow-auto">
                    @foreach($recentOrderDates as $day)
                        <a
                            href="{{ route('orders.monitoring', ['date' => $day['value']]) }}"
                            class="btn btn-sm {{ $day['is_selected'] ? 'btn-primary' : 'btn-light' }} text-nowrap"
                        >
                            {{ $day['day_name'] }} {{ $day['label'] }}
                            <span class="ms-1 badge {{ $day['is_selected'] ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $day['total_orders'] }}</span>
                            @if($day['is_today'])
                                <span class="ms-1">Hôm nay</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('orders.monitoring') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ngày theo dõi</label>
                    <input type="date" name="date" id="monitoringDate" value="{{ $selectedDate }}" class="form-control">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Xem dữ liệu</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('orders.monitoring') }}" class="btn btn-light">Hôm nay</a>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-secondary" id="manualRefreshBtn">Làm mới thủ công</button>
                </div>
            </form>
        </div>
    </div>

    <div id="monitoringStats">
        @include('orders.monitoring._stats', ['stats' => $stats])
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Bảng theo dõi quy trình duyệt</h6>
            <span class="text-muted small">Auto refresh: 10s</span>
        </div>
        <div class="card-body p-0" id="monitoringTable">
            @include('orders.monitoring._table', ['steps' => $steps, 'orders' => $orders])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statsContainer = document.getElementById('monitoringStats');
    const tableContainer = document.getElementById('monitoringTable');
    const updatedLabel = document.getElementById('monitoringLastUpdated');
    const monitoringDateInput = document.getElementById('monitoringDate');
    const manualRefreshBtn = document.getElementById('manualRefreshBtn');
    const endpoint = "{{ route('orders.monitoring.data') }}";
    let pollInterval = null;

    async function refreshMonitoring() {
        const selectedDate = monitoringDateInput ? monitoringDateInput.value : '';

        try {
            const params = new URLSearchParams();
            if (selectedDate) {
                params.set('date', selectedDate);
            }

            const response = await fetch(endpoint + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (payload.statsHtml && statsContainer) {
                statsContainer.innerHTML = payload.statsHtml;
            }
            if (payload.tableHtml && tableContainer) {
                tableContainer.innerHTML = payload.tableHtml;
            }
            if (payload.generatedAt && updatedLabel) {
                updatedLabel.textContent = 'Cập nhật: ' + payload.generatedAt;
            }
        } catch (error) {
            // Intentionally ignore transient network/server errors to keep polling running.
        }
    }

    if (manualRefreshBtn) {
        manualRefreshBtn.addEventListener('click', function () {
            refreshMonitoring();
        });
    }

    if (@json($isAutoRefresh)) {
        pollInterval = setInterval(refreshMonitoring, 10000);
    }
});
</script>
@endpush
