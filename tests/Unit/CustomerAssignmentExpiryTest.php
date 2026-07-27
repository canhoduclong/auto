<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Tests\TestCase;

class CustomerAssignmentExpiryTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_recent_takeover_keeps_customer_managed_when_last_order_is_old(): void
    {
        Carbon::setTestNow('2026-07-27 12:00:00');

        $customer = new class extends Customer
        {
            public static function freeCustomerDays(): int
            {
                return 7;
            }
        };
        $customer->forceFill([
            'customer_status' => 'active',
            'assigned_to' => 10,
            'assigned_at' => now(),
            'is_employee' => false,
        ]);

        $lastOrder = new Order;
        $lastOrder->created_at = now()->subDays(30);
        $customer->setRelation('lastOrder', $lastOrder);

        $this->assertSame('2026-08-03 12:00:00', $customer->assignmentExpiresAt()->toDateTimeString());
        $this->assertFalse($customer->isFree());
    }
}
