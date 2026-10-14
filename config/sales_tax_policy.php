<?php

return [
    'key' => 'sales_tax_operations',
    'version' => '2026-10-14-v4',
    'roles' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'],
    'entity_tax_guidance' => [
        ['label' => 'Hộ kinh doanh', 'rate' => 5],
        ['label' => 'Công ty, hợp tác xã', 'rate' => 0],
        ['label' => 'Cá nhân và pháp nhân khác', 'rate' => 10],
    ],
];
