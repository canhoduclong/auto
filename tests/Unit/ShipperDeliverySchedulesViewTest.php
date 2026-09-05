<?php

namespace Tests\Unit;

use Tests\TestCase;

class ShipperDeliverySchedulesViewTest extends TestCase
{
    public function test_schedule_page_uses_route_master_detail_navigation(): void
    {
        $template = file_get_contents(resource_path('views/shipper/delivery-schedules.blade.php'));

        $this->assertStringContainsString('data-route-browser', $template);
        $this->assertStringContainsString('data-route-select=', $template);
        $this->assertStringContainsString('data-route-panel=', $template);
        $this->assertStringContainsString('Chạm vào lộ trình để xem đơn', $template);
    }

    public function test_each_route_has_its_own_order_ids_and_confirmation_actions(): void
    {
        $template = file_get_contents(resource_path('views/shipper/delivery-schedules.blade.php'));

        $this->assertStringContainsString('@foreach($deliveryRoutes as $routeIndex => $deliveryRoute)', $template);
        $this->assertStringContainsString('name="order_ids[]" value="{{ $order->id }}"', $template);
        $this->assertStringContainsString("['schedule' => 'bulk']", $template);
        $this->assertStringContainsString('Xác nhận lộ trình & nhận đơn', $template);
        $this->assertStringContainsString('Từ chối lộ trình', $template);
    }

    public function test_schedule_page_keeps_delivered_orders_separate_from_routes(): void
    {
        $template = file_get_contents(resource_path('views/shipper/delivery-schedules.blade.php'));

        $this->assertStringContainsString('Danh sách đã giao', $template);
        $this->assertStringContainsString('không được đưa vào xác nhận lộ trình', $template);
    }
}
