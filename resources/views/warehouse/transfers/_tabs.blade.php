<nav class="nav nav-tabs mb-3" aria-label="Quản lý điều chuyển">
    <a class="nav-link {{ $activeTransferTab === 'workbench' ? 'active' : '' }}"
       @if($activeTransferTab === 'workbench') aria-current="page" @endif
       href="{{ route('warehouse.inventory-transfers.index') }}">Lập điều chuyển</a>
    <a class="nav-link {{ $activeTransferTab === 'orders' ? 'active' : '' }}"
       @if($activeTransferTab === 'orders') aria-current="page" @endif
       href="{{ route('warehouse.inventory-transfers.index', ['tab' => 'orders']) }}">Điều chuyển đơn</a>
</nav>
