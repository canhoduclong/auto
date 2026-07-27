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
}
