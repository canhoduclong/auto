<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-2">
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-muted mb-1">Đơn trong ngày</div>
                <h4 class="mb-0">{{ number_format($stats['today_total_orders'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-muted mb-1">Đơn chờ duyệt</div>
                <h4 class="mb-0 text-warning">{{ number_format($stats['today_pending_orders'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-muted mb-1">Đơn đã duyệt</div>
                <h4 class="mb-0 text-success">{{ number_format($stats['today_approved_orders'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-muted mb-1">Đơn bị từ chối</div>
                <h4 class="mb-0 text-danger">{{ number_format($stats['today_rejected_orders'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-muted mb-1">Lượt xử lý duyệt hôm nay</div>
                <h4 class="mb-0 text-primary">{{ number_format($stats['today_processed_approvals'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
</div>
