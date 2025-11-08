<?php

use App\Enums\OrderStatus;

return [
    'role_permissions' => [
        OrderStatus::Draft->value            => ['sale'],
        OrderStatus::Pending->value          => ['leader'],
        OrderStatus::LeaderConfirmed->value  => ['accountant'],
        OrderStatus::AccountingPlanned->value => ['manager'],
        OrderStatus::ManagerConfirmed->value => ['warehouse', 'factory'],
        OrderStatus::WarehouseConfirmed->value => ['warehouse'],
        OrderStatus::FactoryConfirmed->value => ['factory'],
        OrderStatus::Shipping->value         => ['warehouse', 'factory'],
        OrderStatus::Delivered->value        => ['warehouse'],
        OrderStatus::Returned->value         => ['warehouse', 'accountant'],
    ],
];
