<?php

namespace Tests\Unit;

use Tests\TestCase;

class ShipperAssignmentViewTest extends TestCase
{
    public function test_assigned_order_rows_use_an_editable_absolute_shipping_fee(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments.blade.php'));
        $editor = file_get_contents(resource_path('views/shipper/partials/order-shipping-fee-editor.blade.php'));

        $this->assertStringContainsString("@include('shipper.partials.order-shipping-fee-editor'", $template);
        $this->assertStringContainsString('class="form-control form-control-sm trip-shipping-fee-input js-order-shipping-fee"', $editor);
        $this->assertStringContainsString('<th style="width: 220px;">Tiền ship</th>', $template);
        $this->assertStringContainsString('Phí mặc định:', $editor);
        $this->assertStringContainsString('class="shipping-fee-editor js-shipping-fee-editor js-shipping-fee-form"', $editor);
        $this->assertStringContainsString('name="shipping_fee"', $editor);
        $this->assertStringContainsString('type="submit" class="btn btn-success js-save-order-shipping-fee"', $editor);
        $this->assertStringNotContainsString('js-save-order-shipping-fee" disabled', $editor);
        $this->assertStringContainsString('js-save-order-shipping-fee', $editor);
        $this->assertStringContainsString("'Accept': 'application/json'", $template);
        $this->assertStringContainsString("form.classList.contains('js-shipping-fee-form')", $template);
        $this->assertStringNotContainsString('class="form-control form-control-sm js-order-extra-fee"', $template);
    }

    public function test_origin_warehouse_uses_the_value_resolved_from_order_acceptance(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments.blade.php'));

        $this->assertStringContainsString('$order->assignment_origin_warehouse_name', $template);
        $this->assertStringContainsString('data-origin="{{ e($originName) }}"', $template);
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
