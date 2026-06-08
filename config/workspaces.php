<?php

return [
    'catalog' => [
        'website_admin' => [
            'label' => 'Website / Admin',
            'description' => 'Dashboard quan tri tong hop.',
            'platform' => 'website',
            'route' => 'dashboard',
            'role_hints' => ['admin'],
        ],
        'website_ceo' => [
            'label' => 'Website / CEO',
            'description' => 'Dashboard dieu hanh CEO.',
            'platform' => 'website',
            'route' => 'ceo.dashboard',
            'role_hints' => ['ceo'],
        ],
        'website_accounting' => [
            'label' => 'Website / Accounting',
            'description' => 'Dashboard ke toan.',
            'platform' => 'website',
            'route' => 'accounting.dashboard',
            'role_hints' => ['accountant', 'accounting'],
        ],
        'website_warehouse' => [
            'label' => 'Website / Warehouse',
            'description' => 'Dashboard kho.',
            'platform' => 'website',
            'route' => 'warehouse.dashboard',
            'role_hints' => ['warehouse'],
        ],
        'website_shipper' => [
            'label' => 'Website / Shipper',
            'description' => 'Dashboard shipper va dieu phoi ship.',
            'platform' => 'website',
            'route' => 'shipper.dashboard',
            'role_hints' => ['shipper', 'ship', 'manager_shipper'],
        ],
        'website_sales' => [
            'label' => 'Website / Sales',
            'description' => 'Dashboard kinh doanh.',
            'platform' => 'website',
            'route' => 'pages.my_dashboard',
            'role_hints' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'],
        ],
        'my_app_home' => [
            'label' => 'My_app / Home',
            'description' => 'Dieu huong my_app theo role mobile.',
            'platform' => 'my_app',
            'route' => 'mobile.home',
            'role_hints' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale', 'warehouse', 'shipper', 'ship', 'admin'],
        ],
        'my_app_warehouse' => [
            'label' => 'My_app / Warehouse',
            'description' => 'Giao dien my_app cho kho.',
            'platform' => 'my_app',
            'route' => 'mobile.warehouse.home',
            'role_hints' => ['warehouse', 'admin'],
        ],
        'my_app_shipper' => [
            'label' => 'My_app / Shipper',
            'description' => 'Giao dien my_app cho shipper.',
            'platform' => 'my_app',
            'route' => 'mobile.shipper.home',
            'role_hints' => ['shipper', 'ship', 'admin'],
        ],
        'my_app_sales' => [
            'label' => 'My_app / Sales',
            'description' => 'Giao dien my_app cho sales.',
            'platform' => 'my_app',
            'route' => 'mobile.sale.home',
            'role_hints' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale', 'admin'],
        ],
    ],
];