<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MonitoringOrderAdditionalChargesViewTest extends TestCase
{
    public function test_quick_create_contains_separate_vat_and_customer_shipping_controls(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/site/orders/monitoring.blade.php'
        );

        $this->assertStringContainsString('id="monitorChargeVat"', $template);
        $this->assertStringContainsString('id="monitorVatPercent"', $template);
        $this->assertStringContainsString('id="monitorCollectCustomerShippingFee"', $template);
        $this->assertStringContainsString('id="monitorCustomerShippingFee"', $template);
        $this->assertStringContainsString('customer_shipping_fee: collectCustomerShippingFee ? customerShippingFee : null', $template);
    }
}
