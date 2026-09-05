@extends('layouts.shipper')

@section('title', 'Lộ trình giao hàng')
@section('subtitle', 'Chọn một lộ trình để kiểm tra và xác nhận các đơn')

@push('styles')
<style>
    .ds-summary-card, .ds-route-list, .ds-route-detail, .ds-delivered-card { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, .06); }
    .ds-layout { display: grid; grid-template-columns: minmax(250px, 320px) minmax(0, 1fr); gap: 1rem; align-items: start; }
    .ds-route-list { position: sticky; top: 1rem; overflow: hidden; }
    .ds-route-option { width: 100%; border: 0; border-left: 4px solid transparent; background: #fff; padding: .9rem 1rem; text-align: left; transition: background-color .15s, border-color .15s; }
    .ds-route-option + .ds-route-option { border-top: 1px solid #eef2f7; }
    .ds-route-option:hover { background: #f8fafc; }
    .ds-route-option.active { border-left-color: var(--theme-primary); background: rgba(15, 118, 110, .08); }
    .ds-route-number { width: 34px; height: 34px; border-radius: 10px; background: #e2e8f0; color: #475569; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; flex: 0 0 auto; }
    .ds-route-option.active .ds-route-number { background: var(--theme-primary); color: #fff; }
    .ds-route-panel[hidden] { display: none !important; }
    .ds-route-detail { overflow: hidden; }
    .ds-detail-header { background: linear-gradient(135deg, #0f766e, #0d9488); color: #fff; }
    .ds-order-card { border: 1px solid #e2e8f0; border-radius: 13px; padding: 1rem; background: #fff; }
    .ds-order-index { width: 34px; height: 34px; border-radius: 50%; background: #ecfdf5; color: #047857; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; flex: 0 0 auto; }
    .ds-product-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: .75rem; padding: .45rem 0; border-top: 1px dashed #e2e8f0; font-size: .86rem; }
    .ds-action-bar { background: #f8fafc; border-top: 1px solid #e2e8f0; }
    @media (max-width: 991.98px) { .ds-layout { grid-template-columns: 1fr; } .ds-route-list { position: static; } }
    @media (max-width: 575.98px) { .ds-summary-actions, .ds-summary-actions form { width: 100%; } .ds-summary-actions .form-control { flex: 1; max-width: none !important; } .ds-route-detail .card-body { padding: .75rem; } .ds-order-card { padding: .8rem; } .ds-action-bar .btn { width: 100%; } }
</style>
@endpush

@section('content')
<div class="card ds-summary-card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="fw-bold text-dark fs-5">Danh sách lộ trình</div>
            <div class="text-muted small">
                {{ count($deliveryRoutes) }} lộ trình · Đang thực hiện: <strong>{{ $orders->count() }}</strong> đơn
                · Đã giao: <strong>{{ $deliveredOrders->count() }}</strong> đơn
            </div>
        </div>
        <div class="ds-summary-actions">
            <form method="GET" action="{{ route('shipper.delivery-schedules') }}" class="d-flex gap-2 align-items-center">
                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 160px" aria-label="Ngày giao hàng">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Xem</button>
                <a href="{{ route('shipper.delivery-schedules') }}" class="btn btn-sm btn-outline-secondary" title="Về hôm nay"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>
    </div>
</div>

@if(empty($deliveryRoutes))
    <div class="card ds-route-detail">
        <div class="card-body text-center py-5">
            <i class="bi bi-signpost-split fs-1 text-muted"></i>
            <p class="mt-3 mb-0 text-muted">Không có lộ trình giao hàng nào cho ngày này.</p>
        </div>
    </div>
@else
    @php
        $requestedRoute = (string) request('route', '');
        $initialRouteKey = collect($deliveryRoutes)->contains(fn ($route) => $route['key'] === $requestedRoute) ? $requestedRoute : $deliveryRoutes[0]['key'];
    @endphp
    <div class="ds-layout" data-route-browser data-initial-route="{{ $initialRouteKey }}">
        <aside class="card ds-route-list" aria-label="Danh sách lộ trình">
            <div class="card-header bg-white border-0 px-3 pt-3 pb-2">
                <div class="fw-bold">Các lộ trình trong ngày</div>
                <div class="text-muted small">Chạm vào lộ trình để xem đơn</div>
            </div>
            <div>
                @foreach($deliveryRoutes as $routeIndex => $deliveryRoute)
                    @php
                        $routeStatusLabel = match ($deliveryRoute['status']) { 'confirmed' => 'Đã xác nhận', 'rejected' => 'Đã từ chối', default => 'Chờ xác nhận' };
                        $routeStatusClass = match ($deliveryRoute['status']) { 'confirmed' => 'bg-success', 'rejected' => 'bg-danger', default => 'bg-warning text-dark' };
                    @endphp
                    <button type="button" class="ds-route-option {{ $deliveryRoute['key'] === $initialRouteKey ? 'active' : '' }}" data-route-select="{{ $deliveryRoute['key'] }}" aria-controls="{{ $deliveryRoute['key'] }}-panel" aria-selected="{{ $deliveryRoute['key'] === $initialRouteKey ? 'true' : 'false' }}">
                        <span class="d-flex gap-2 align-items-start">
                            <span class="ds-route-number">{{ $routeIndex + 1 }}</span>
                            <span class="flex-fill min-w-0">
                                <span class="d-flex align-items-start justify-content-between gap-2"><span class="fw-bold text-dark">{{ $deliveryRoute['name'] }}</span><i class="bi bi-chevron-right text-muted"></i></span>
                                <span class="d-flex align-items-center justify-content-between mt-2 gap-2"><span class="text-muted small">{{ $deliveryRoute['orders']->count() }} đơn · {{ $deliveryRoute['quantity'] }} sp</span><span class="badge {{ $routeStatusClass }}">{{ $routeStatusLabel }}</span></span>
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>
        </aside>

        <main>
            @foreach($deliveryRoutes as $routeIndex => $deliveryRoute)
                <section id="{{ $deliveryRoute['key'] }}-panel" class="card ds-route-detail ds-route-panel" data-route-panel="{{ $deliveryRoute['key'] }}" @if($deliveryRoute['key'] !== $initialRouteKey) hidden @endif>
                    <div class="card-header ds-detail-header border-0 p-3">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div><div class="small opacity-75">LỘ TRÌNH {{ $routeIndex + 1 }}</div><h5 class="mb-1 fw-bold">{{ $deliveryRoute['name'] }}</h5><div class="small opacity-75">Kiểm tra các đơn bên dưới trước khi xác nhận nhận lộ trình.</div></div>
                            <span class="badge bg-white text-dark fs-6">{{ $deliveryRoute['orders']->count() }} đơn</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('shipper.confirm-delivery-schedule', ['schedule' => 'bulk']) }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <div class="card-body d-flex flex-column gap-3">
                            @foreach($deliveryRoute['orders'] as $orderIndex => $order)
                                <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                                <article class="ds-order-card">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="ds-order-index">{{ $orderIndex + 1 }}</span>
                                        <div class="flex-fill min-w-0">
                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                <div><div class="fw-bold text-dark">{{ $order->customer?->name ?? $order->recipient_name ?? 'Khách hàng' }}</div><div class="text-muted small">Mã đơn: {{ $order->code ?: '#'.$order->id }}</div></div>
                                                <span class="badge bg-light text-primary border"><i class="bi bi-clock me-1"></i>{{ $order->delivery_time ?: 'Chưa có giờ giao' }}</span>
                                            </div>
                                            <div class="small mt-3 d-grid gap-2">
                                                <div><i class="bi bi-geo-alt text-danger me-2"></i>{{ $order->recipient_address ?: $order->customer?->address ?: 'Chưa cập nhật địa chỉ' }}</div>
                                                @if($order->recipient_phone || $order->customer?->phone)<div><i class="bi bi-telephone text-primary me-2"></i>{{ $order->recipient_phone ?: $order->customer?->phone }}</div>@endif
                                                @if($order->shipper_note)<div class="text-warning-emphasis"><i class="bi bi-sticky me-2"></i>{{ $order->shipper_note }}</div>@endif
                                            </div>
                                            @if($order->items->isNotEmpty())
                                                <div class="mt-3">
                                                    @foreach($order->items as $item)
                                                        @php $quantity = (int) $item->quantity; $unitPrice = (float) ($item->price ?? 0); @endphp
                                                        <div class="ds-product-row"><span class="text-truncate">{{ $item->variant?->name ?? $item->variant?->sku ?? $item->product_name ?? 'Sản phẩm' }}</span><strong>x{{ $quantity }}</strong><span class="text-end">{{ number_format($quantity * $unitPrice) }}đ</span></div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="ds-action-bar p-3 d-flex justify-content-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-outline-danger" formaction="{{ route('shipper.reject-delivery-schedule', ['schedule' => 'bulk']) }}"><i class="bi bi-x-circle me-1"></i>Từ chối lộ trình</button>
                            <button type="submit" class="btn {{ $deliveryRoute['status'] === 'confirmed' ? 'btn-secondary' : 'btn-primary' }}" @disabled($deliveryRoute['status'] === 'confirmed')><i class="bi bi-check2-circle me-1"></i>{{ $deliveryRoute['status'] === 'confirmed' ? 'Đã xác nhận lộ trình' : 'Xác nhận lộ trình & nhận đơn' }}</button>
                        </div>
                    </form>
                </section>
            @endforeach
        </main>
    </div>
@endif

@if($deliveredOrders->isNotEmpty())
    <div class="card ds-delivered-card mt-4 overflow-hidden">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div><div class="fw-bold text-success"><i class="bi bi-check-circle me-1"></i>Danh sách đã giao</div><div class="text-muted small">Các đơn này chỉ để theo dõi và không được đưa vào xác nhận lộ trình.</div></div>
            <span class="badge bg-success rounded-pill">{{ $deliveredOrders->count() }} đơn</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Mã đơn</th><th>Khách hàng</th><th>Địa chỉ</th><th class="text-center">Số lượng</th><th>Thời gian giao</th><th class="text-end">Trạng thái</th></tr></thead>
                <tbody>
                    @foreach($deliveredOrders as $deliveredOrder)
                        @php $deliveryHistory = $deliveredOrder->histories->first(); $completedAt = $deliveredOrder->delivered_at ?: $deliveryHistory?->created_at; @endphp
                        <tr><td class="fw-semibold">{{ $deliveredOrder->code ?: '#'.$deliveredOrder->id }}</td><td>{{ $deliveredOrder->customer?->name ?: $deliveredOrder->recipient_name ?: '—' }}</td><td class="text-muted">{{ $deliveredOrder->recipient_address ?: $deliveredOrder->customer?->address ?: '—' }}</td><td class="text-center">{{ $deliveredOrder->items->sum('quantity') }}</td><td>{{ $completedAt ? $completedAt->format('H:i d/m/Y') : '—' }}</td><td class="text-end"><span class="badge bg-success">Đã giao</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-route-browser]').forEach(function (browser) {
            var routeButtons = Array.from(browser.querySelectorAll('[data-route-select]'));
            var routePanels = Array.from(browser.querySelectorAll('[data-route-panel]'));
            function selectRoute(routeKey, updateUrl) {
                routeButtons.forEach(function (button) { var active = button.dataset.routeSelect === routeKey; button.classList.toggle('active', active); button.setAttribute('aria-selected', active ? 'true' : 'false'); });
                routePanels.forEach(function (panel) { panel.hidden = panel.dataset.routePanel !== routeKey; });
                if (updateUrl && window.history && window.history.replaceState) { var url = new URL(window.location.href); url.searchParams.set('route', routeKey); window.history.replaceState({}, '', url); }
            }
            routeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selectRoute(button.dataset.routeSelect, true);
                    if (window.innerWidth < 992) { browser.querySelector('[data-route-panel="' + button.dataset.routeSelect + '"]').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                });
            });
            selectRoute(browser.dataset.initialRoute, false);
        });
    });
</script>
@endpush
