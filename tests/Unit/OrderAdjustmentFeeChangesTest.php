<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderAdjustmentController;
use App\Models\Order;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class OrderAdjustmentFeeChangesTest extends TestCase
{
    public function test_fee_change_payload_keeps_original_and_requested_values(): void
    {
        $order = new Order([
            'charge_vat' => false,
            'vat_percent' => 0,
            'charge_shipping_fee' => true,
            'shipping_fee' => 30000,
            'extra_discount_total' => 10000,
            'charge_foam_box_fee' => false,
            'foam_box_price' => 0,
        ]);

        $reflection = new ReflectionClass(OrderAdjustmentController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('prepareFeeChanges');
        $method->setAccessible(true);

        $changes = $method->invoke($controller, $order, [
            'vat' => ['enabled' => '1', 'value' => '10'],
            'shipping' => ['enabled' => '0', 'value' => '30000'],
            'discount' => ['enabled' => '1', 'value' => '25000'],
            'foam_box' => ['enabled' => '1', 'value' => '15000'],
        ]);

        $this->assertFalse($changes['vat']['original']['enabled']);
        $this->assertSame(10.0, $changes['vat']['adjusted']['value']);
        $this->assertTrue($changes['shipping']['original']['enabled']);
        $this->assertFalse($changes['shipping']['adjusted']['enabled']);
        $this->assertSame(10000.0, $changes['discount']['original']['value']);
        $this->assertSame(25000.0, $changes['discount']['adjusted']['value']);
        $this->assertTrue($changes['foam_box']['adjusted']['enabled']);
        $this->assertSame(15000.0, $changes['foam_box']['adjusted']['value']);
    }

    public function test_adjustment_form_lists_supported_fee_types(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/adjustments/create.blade.php');

        $this->assertStringContainsString('fees[vat][enabled]', $view);
        $this->assertStringContainsString('fees[shipping][enabled]', $view);
        $this->assertStringContainsString('fees[discount][enabled]', $view);
        $this->assertStringContainsString('fees[foam_box][enabled]', $view);
    }
}
