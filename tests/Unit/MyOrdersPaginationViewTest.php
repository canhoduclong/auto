<?php

namespace Tests\Unit;

use Tests\TestCase;

class MyOrdersPaginationViewTest extends TestCase
{
    public function test_ajax_pagination_replaces_existing_endpoint_query_string(): void
    {
        $template = file_get_contents(resource_path('views/site/my_orders.blade.php'));

        $this->assertStringContainsString(
            'const requestUrl = new URL(ordersEndpoint, window.location.origin);',
            $template
        );
        $this->assertStringContainsString('requestUrl.search = ajaxParams.toString();', $template);
        $this->assertStringContainsString('fetch(requestUrl, {', $template);
        $this->assertStringNotContainsString(
            "fetch(ordersEndpoint + '?' + ajaxParams.toString(), {",
            $template
        );
    }

    public function test_monitoring_view_contains_ajax_customer_picker_controls(): void
    {
        $template = file_get_contents(resource_path('views/site/my_orders.blade.php'));

        $this->assertStringContainsString('id="openOrdersCustomerPicker"', $template);
        $this->assertStringContainsString('id="ordersCustomerPickerModal"', $template);
        $this->assertStringContainsString("scope: 'orders'", $template);
        $this->assertStringContainsString('window.refreshOrdersList?.(1);', $template);
        $this->assertStringContainsString('const getModal = () => window.bootstrap?.Modal', $template);
        $this->assertStringNotContainsString(
            'const modal = bootstrap.Modal.getOrCreateInstance(modalElement);',
            $template
        );
    }
}
