@php
$tabs = [
    [
        'key' => 'total-customers',
        'label' => 'Tổng khách hàng',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6 0A4 4 0 0012 4a4 4 0 00-1 7.87m6 0A4 4 0 0012 4a4 4 0 00-1 7.87" /></svg>',
        'value' => 1200,
        'permission' => 'view-customers',
    ],
    [
        'key' => 'new-customers',
        'label' => 'Khách hàng mới',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>',
        'value' => 30,
        'permission' => 'view-customers',
    ],
    [
        'key' => 'active-customers',
        'label' => 'Khách hàng đang tương tác',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>',
        'value' => 80,
        'permission' => 'view-customers',
    ],
    [
        'key' => 'total-orders',
        'label' => 'Tổng đơn hàng',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" /></svg>',
        'value' => 120,
        'permission' => 'view-orders',
    ],
    [
        'key' => 'total-revenue',
        'label' => 'Tổng doanh thu',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 16v-4" /></svg>',
        'value' => '2 tỷ',
        'permission' => 'view-revenue',
    ],
    [
        'key' => 'total-debt',
        'label' => 'Tổng công nợ',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2z" /></svg>',
        'value' => '1.2 tỷ',
        'permission' => 'view-debt',
    ],
];
$activeTab = $tabs[0]['key'];
@endphp

<x-tab-dashboard :tabs="$tabs" :active-tab="$activeTab" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Tự động load nội dung tab đầu tiên khi vào trang
window.addEventListener('DOMContentLoaded', function() {
    const firstTab = document.querySelector('.card[data-tab]');
    if (firstTab) firstTab.click();
});
</script>
