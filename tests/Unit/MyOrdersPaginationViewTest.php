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
        $this->assertStringContainsString('id="ordersCustomerQuickSearch"', $template);
        $this->assertStringContainsString('id="ordersCustomerPickerModal"', $template);
        $this->assertStringContainsString('openCustomerPicker();', $template);
        $this->assertStringContainsString("scope: 'orders'", $template);
        $this->assertStringContainsString('window.refreshOrdersList?.(1);', $template);
        $this->assertStringContainsString('const getModal = () => window.bootstrap?.Modal', $template);
        $this->assertStringNotContainsString(
            'const modal = bootstrap.Modal.getOrCreateInstance(modalElement);',
            $template
        );
    }

    public function test_missing_phone_result_offers_ajax_customer_creation(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PageController.php'));
        $page = file_get_contents(resource_path('views/site/my_orders.blade.php'));
        $picker = file_get_contents(resource_path('views/site/orders/partials/customer_picker_single.blade.php'));

        $this->assertStringContainsString('!$phoneAlreadyExists', $controller);
        $this->assertStringContainsString('? $search', $controller);
        $this->assertStringContainsString('customer-quick-create-submit', $page);
        $this->assertStringContainsString("body.set('phone', phone);", $page);
        $this->assertStringContainsString('Thêm khách hàng mới', $picker);
        $this->assertStringContainsString('value="{{ $searchedPhone }}"', $picker);
    }

    public function test_standalone_orders_page_is_canonicalized_to_monitoring_layout(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PageController.php'));

        $this->assertStringContainsString("\$request->input('tab') !== 'my_orders'", $controller);
        $this->assertStringContainsString("['tab' => 'my_orders']", $controller);
        $this->assertStringContainsString("redirect()->route('pages.my_orders.monitoring'", $controller);
    }
}
