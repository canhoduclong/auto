<nav class="monitor-tab-nav" aria-label="Nhóm đơn hàng">
    @include('site.orders.partials.order_navigation_links', [
        'activeTab' => $activeTab ?? null,
        'selectedDate' => $selectedDate ?? now()->toDateString(),
        'selectedDateField' => $selectedDateField ?? 'business_date',
        'linkClass' => 'monitor-tab-link',
        'activeClass' => 'active',
    ])
</nav>
