<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\AdminActivityService;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        AdminActivityService::record(
            'customer',
            'created',
            $customer,
            'Tao moi khach hang',
            'Khach hang "' . $customer->name . '" vua duoc tao.',
            ['customer_id' => $customer->id, 'name' => $customer->name],
            route('customers.edit', $customer)
        );
    }

    public function updated(Customer $customer): void
    {
        AdminActivityService::record(
            'customer',
            'updated',
            $customer,
            'Cap nhat khach hang',
            'Khach hang "' . $customer->name . '" da duoc cap nhat.',
            ['customer_id' => $customer->id, 'changes' => $customer->getChanges()],
            route('customers.edit', $customer)
        );
    }

    public function deleted(Customer $customer): void
    {
        AdminActivityService::record(
            'customer',
            'deleted',
            $customer,
            'Xoa khach hang',
            'Khach hang "' . $customer->name . '" da bi xoa.',
            ['customer_id' => $customer->id, 'name' => $customer->name],
            route('customers.index')
        );
    }
}
