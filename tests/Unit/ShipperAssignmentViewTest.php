<?php

namespace Tests\Unit;

use Tests\TestCase;

class ShipperAssignmentViewTest extends TestCase
{
    public function test_assigned_order_rows_use_an_editable_absolute_shipping_fee(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments.blade.php'));

        $this->assertStringContainsString('class="form-control form-control-sm trip-shipping-fee-input js-order-shipping-fee"', $template);
        $this->assertStringContainsString('<th style="width: 116px;">Tiền ship</th>', $template);
        $this->assertStringNotContainsString('class="form-control form-control-sm js-order-extra-fee"', $template);
    }

    public function test_typing_shipping_fee_does_not_rebuild_and_move_the_order_row(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments.blade.php'));

        $this->assertStringContainsString("if (event.target.matches('.js-order-shipping-fee'))", $template);
        $this->assertStringContainsString('collectTripPlan(false);', $template);
        $this->assertStringContainsString('const extraFee = finalFee - baseFee;', $template);
        $this->assertStringContainsString('trip.combined_fee = trip.final_total;', $template);
        $this->assertStringContainsString('data-delivery-time="{{ e($deliveryTime', $template);
        $this->assertStringContainsString('product_summary: row.dataset.productSummary,', $template);
        $this->assertStringContainsString('sequence: numberValue(row.dataset.sequence),', $template);
    }
}
