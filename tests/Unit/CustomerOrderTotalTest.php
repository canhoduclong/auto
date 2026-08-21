<?php

namespace Tests\Unit;

use App\Http\Controllers\ShipperDashboardController;
use App\Models\Order;
use ReflectionMethod;
use Tests\TestCase;

class CustomerOrderTotalTest extends TestCase
{
    public function test_internal_shipping_fee_is_separate_from_vat_and_customer_shipping_charge(): void
    {
        $order = new Order();
        $order->forceFill([
            'extra_discount_total' => 0,
            'charge_vat' => true,
            'vat_percent' => 10,
            'collect_customer_shipping_fee' => true,
            'customer_shipping_fee' => 15000,
            'charge_shipping_fee' => false,
        ]);

        $method = new ReflectionMethod(ShipperDashboardController::class, 'customerOrderTotal');
        [$total, $vatAmount] = $method->invoke(
            app(ShipperDashboardController::class),
            $order,
            200000,
            99000,
            0
        );

        $this->assertSame(235000.0, $total);
        $this->assertSame(20000.0, $vatAmount);
    }
}
