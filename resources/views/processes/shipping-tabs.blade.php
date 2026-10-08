@if(app(\App\Services\ProcessEngine::class)->hasRole(auth()->user(), 'shipper'))
<nav class="nav nav-pills gap-2 mb-3" aria-label="Quản lý phí ship">
    <a class="nav-link {{ $activeTab === 'orders' ? 'active' : '' }}" href="{{ route('shipping-expenses.index') }}">Đơn hàng đã giao</a>
    <a class="nav-link {{ $activeTab === 'requests' ? 'active' : '' }}" href="{{ route('shipping-expenses.index', ['tab' => 'requests']) }}">Danh sách các yêu cầu</a>
</nav>
@endif
