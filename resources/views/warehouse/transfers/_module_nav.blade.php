<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2"></i>Điều chuyển</h5>
            <div class="small text-muted">Quản lý điều chuyển đơn, điều chuyển hàng và phiếu xuất kho theo tài xế tại một nơi.</div>
        </div>
        <div class="btn-group flex-wrap" role="group" aria-label="Chức năng điều chuyển">
            <a href="{{ route('warehouse.transfers.index') }}" class="btn btn-sm {{ request()->routeIs('warehouse.transfers.index', 'warehouse.dispatch-slips.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-ui-checks-grid me-1"></i>Tổng hợp & phiếu xuất
            </a>
            <a href="{{ route('warehouse.order-transfers') }}" class="btn btn-sm {{ request()->routeIs('warehouse.order-transfers', 'warehouse.order-transfers.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-box-seam me-1"></i>Tạo điều chuyển đơn
            </a>
            <a href="{{ route('warehouse.inventory-transfers.index') }}" class="btn btn-sm {{ request()->routeIs('warehouse.inventory-transfers.index', 'warehouse.inventory-transfers.edit') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-boxes me-1"></i>Tạo điều chuyển hàng
            </a>
        </div>
    </div>
</div>
