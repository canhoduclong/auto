@php
    $orderNavigationActiveTab = $activeTab ?? null;
    $orderNavigationLinkClass = $linkClass ?? 'monitor-tab-link';
    $orderNavigationActiveClass = $activeClass ?? 'active';
    $orderNavigationSelectedDate = $selectedDate ?? now()->toDateString();
    $orderNavigationDateField = $selectedDateField ?? 'business_date';
    $orderNavigationCanManageAdjustments = auth()->user()?->canManageOrderAdjustments() ?? false;

    $orderNavigationItems = [
        [
            'key' => 'dashboard',
            'label' => 'Bảng điều khiển',
            'icon' => 'bi-house-door',
            'url' => route('pages.my_dashboard'),
        ],
        [
            'key' => 'today',
            'label' => 'Đơn hôm nay',
            'icon' => 'bi-file-earmark-text',
            'url' => route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => $orderNavigationSelectedDate,
                'date_field' => $orderNavigationDateField,
            ]),
        ],
        [
            'key' => 'drafts',
            'label' => 'Đơn hàng Mẫu',
            'icon' => 'bi-file-earmark-text',
            'url' => route('pages.my_orders.monitoring', ['tab' => 'drafts']),
        ],
        [
            'key' => 'my_orders',
            'label' => 'Đơn của tôi',
            'icon' => 'bi-bag-check',
            'url' => route('pages.my_orders.monitoring', ['tab' => 'my_orders']),
        ],
        [
            'key' => 'fix_data',
            'label' => 'Fix số liệu',
            'icon' => 'bi-arrow-left-right',
            'url' => route('site.order-adjustments.index'),
            'visible' => $orderNavigationCanManageAdjustments,
        ],
        [
            'key' => 'customers',
            'label' => 'Khách hàng',
            'icon' => 'bi-person-check',
            'url' => route('pages.my_orders.monitoring', ['tab' => 'customers']),
        ],
    ];
@endphp

@foreach($orderNavigationItems as $orderNavigationItem)
    @continue(($orderNavigationItem['visible'] ?? true) === false)
    @php $isOrderNavigationActive = $orderNavigationActiveTab === $orderNavigationItem['key']; @endphp
    <a
        class="{{ $orderNavigationLinkClass }}{{ $isOrderNavigationActive ? ' ' . $orderNavigationActiveClass : '' }}"
        href="{{ $orderNavigationItem['url'] }}"
        @if($isOrderNavigationActive) aria-current="page" @endif
    >
        <i class="bi {{ $orderNavigationItem['icon'] }}"></i><span>{{ $orderNavigationItem['label'] }}</span>
    </a>
@endforeach
