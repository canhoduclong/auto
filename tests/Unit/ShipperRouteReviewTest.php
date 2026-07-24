<?php

namespace Tests\Unit;

use App\Http\Controllers\ShipperDashboardController;
use ReflectionMethod;
use Tests\TestCase;

class ShipperRouteReviewTest extends TestCase
{
    public function test_review_form_does_not_double_escape_route_plan_json(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments-review.blade.php'));

        $this->assertStringContainsString('name="route_plan" value="{{ $routePlanJson }}"', $template);
        $this->assertStringNotContainsString('name="route_plan" value="{{ e($routePlanJson) }}"', $template);
    }

    public function test_route_plan_decoder_accepts_review_form_json(): void
    {
        $routePlanJson = json_encode([[
            'shipper_id' => 53,
            'routes' => [[
                'name' => 'Lộ trình 1',
                'orders' => [['order_id' => 1, 'final_fee' => 0]],
            ]],
        ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $method = new ReflectionMethod(ShipperDashboardController::class, 'decodeRoutePlan');
        $decoded = $method->invoke(new ShipperDashboardController(), $routePlanJson);

        $this->assertSame('Lộ trình 1', $decoded[0]['routes'][0]['name']);
        $this->assertSame(1, $decoded[0]['routes'][0]['orders'][0]['order_id']);
    }
}
