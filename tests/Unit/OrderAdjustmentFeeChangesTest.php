<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderFee;
use App\Models\OrderFeeType;
use App\Services\OrderFeeService;
use PHPUnit\Framework\TestCase;

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

        $types = collect([
            new OrderFeeType(['name' => 'Phí VAT', 'code' => 'vat', 'calculation_type' => 'percent', 'direction' => 'charge', 'is_system' => true]),
            new OrderFeeType(['name' => 'Phí Ship', 'code' => 'shipping', 'calculation_type' => 'fixed', 'direction' => 'charge', 'is_system' => true]),
            new OrderFeeType(['name' => 'Chiết khấu', 'code' => 'discount', 'calculation_type' => 'fixed', 'direction' => 'discount', 'is_system' => true]),
            new OrderFeeType(['name' => 'Phí thùng xốp', 'code' => 'foam_box', 'calculation_type' => 'fixed', 'direction' => 'charge', 'is_system' => true]),
        ]);
        $types->each(fn (OrderFeeType $type, int $index) => $type->setAttribute('id', $index + 1));
        $order->setRelation('additionalFees', collect());

        $changes = (new OrderFeeService())->prepareChanges($order, $types, [
            1 => ['enabled' => '1', 'value' => '10'],
            2 => ['enabled' => '0', 'value' => '30000'],
            3 => ['enabled' => '1', 'value' => '25000'],
            4 => ['enabled' => '1', 'value' => '15000'],
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

        $this->assertStringContainsString('@forelse($feeTypes as $feeType)', $view);
        $this->assertStringContainsString('fees[{{ $feeType->id }}][type_id]', $view);
        $this->assertStringContainsString('fees[{{ $feeType->id }}][enabled]', $view);
        $this->assertStringContainsString("\$feeType->direction === 'discount'", $view);
        $this->assertStringContainsString('id="order-fee-picker"', $view);
        $this->assertStringContainsString('class="adjustment-fee-enabled"', $view);
        $this->assertStringContainsString('remove-order-fee', $view);
        $this->assertStringNotContainsString('adjustment-fee-toggle', $view);
    }

    public function test_inline_adjustment_fee_form_has_readable_selectable_controls(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/adjustments/_inline_form.blade.php');

        $this->assertStringContainsString('monitor-adjustment-fee-identity', $view);
        $this->assertStringContainsString('monitor-adjustment-fee-control', $view);
        $this->assertStringContainsString('Giá trị mới', $view);
        $this->assertStringContainsString("array_key_exists('value', \$state)", $view);
        $this->assertStringContainsString('@disabled(!$enabled)', $view);
        $this->assertStringNotContainsString("\$state['value'] ?: \$feeType->default_value", $view);
    }

    public function test_custom_percentage_discount_keeps_snapshot_metadata(): void
    {
        $order = new Order();
        $order->setRelation('additionalFees', collect([
            new OrderFee(['order_fee_type_id' => 9, 'fee_code' => 'vip_discount', 'rate' => 5, 'amount' => 5000]),
        ]));
        $type = new OrderFeeType([
            'name' => 'Ưu đãi VIP', 'code' => 'vip_discount', 'calculation_type' => 'percent',
            'direction' => 'discount', 'default_value' => 0, 'is_system' => false,
        ]);
        $type->setAttribute('id', 9);

        $changes = (new OrderFeeService())->prepareChanges($order, collect([$type]), [
            9 => ['enabled' => '1', 'value' => '7.5'],
        ]);

        $this->assertSame('Ưu đãi VIP', $changes['vip_discount']['name']);
        $this->assertSame('percent', $changes['vip_discount']['calculation_type']);
        $this->assertSame('discount', $changes['vip_discount']['direction']);
        $this->assertSame(5.0, $changes['vip_discount']['original']['value']);
        $this->assertSame(7.5, $changes['vip_discount']['adjusted']['value']);
    }
}
