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

    public function test_review_table_is_grouped_by_route_and_shows_delivery_columns(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments-review.blade.php'));

        foreach (['Giờ giao', 'Khách hàng', 'Số lượng', 'Điểm đi', 'Điểm đến', 'Số tiền', 'Ghi chú'] as $heading) {
            $this->assertStringContainsString("<th>{$heading}</th>", $template);
        }

        $this->assertStringContainsString('class="route-group-row"', $template);
        $this->assertStringContainsString("{{ \$order['product_summary'] }}", $template);
        $this->assertStringContainsString('>Tổng phí</td>', $template);
    }

    public function test_review_page_can_switch_between_grouped_and_compact_views(): void
    {
        $template = file_get_contents(resource_path('views/shipper/manage-assignments-review.blade.php'));

        $this->assertStringContainsString('data-review-view-switch="grouped"', $template);
        $this->assertStringContainsString('data-review-view-switch="compact"', $template);
        $this->assertStringContainsString('data-review-view-panel="grouped"', $template);
        $this->assertStringContainsString('data-review-view-panel="compact"', $template);
        $this->assertStringContainsString('class="compact-route-table mb-0"', $template);
        $this->assertStringContainsString("{{ \$compactShipperName }}", $template);
        $this->assertStringContainsString("{{ \$compactRouteLabel }}", $template);
    }
}
