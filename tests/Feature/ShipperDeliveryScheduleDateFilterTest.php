<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\ShipperApiController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShipperDeliveryScheduleDateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_show_all_dates_by_default_and_filter_by_requested_date(): void
    {
        Carbon::setTestNow('2026-09-08 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách tuyến', 'status' => 'active']);
        foreach (['2026-09-06', '2026-09-07'] as $date) {
            $order = Order::create([
                'user_id' => $shipper->id, 'shipper_id' => $shipper->id,
                'customer_id' => $customer->id, 'code' => 'ROUTE-'.$date,
                'status' => Order::STATUS_READY_TO_SHIP,
            ]);
            $order->forceFill(['created_at' => $date.' 08:00:00'])->saveQuietly();
            ShipperDispatchHistory::create([
                'schedule_date' => $date, 'published_at' => now(), 'version' => 1, 'created_by' => $shipper->id,
                'route_plan' => [['shipper_id' => $shipper->id, 'routes' => [
                    ['name' => 'Tuyến '.$date, 'orders' => [['order_id' => $order->id]]],
                ]]],
            ]);
        }

        $this->actingAs($shipper)->get(route('shipper.delivery-schedules'))
            ->assertOk()->assertSee('Tuyến 2026-09-06')->assertSee('Tuyến 2026-09-07')
            ->assertSee('06/09/2026')->assertSee('07/09/2026')
            ->assertSee('name="date" value="2026-09-06"', false)
            ->assertSee('name="date" value="2026-09-07"', false)
            ->assertViewHas('deliveryRoutes', fn ($routes) => count($routes) === 2 && $routes[0]['date'] === '2026-09-07');
        $this->get(route('shipper.delivery-schedules', ['date' => '2026-09-07']))
            ->assertOk()->assertSee('Tuyến 2026-09-07')->assertDontSee('Tuyến 2026-09-06')
            ->assertViewHas('deliveryRoutes', fn ($routes) => count($routes) === 1);
        $this->get(route('shipper.delivery-schedules', ['date' => '2026-09-05']))
            ->assertOk()->assertViewHas('deliveryRoutes', []);
        $this->getJson(route('shipper.delivery-schedules', ['date' => 'invalid']))->assertUnprocessable();
    }
    public function test_available_defaults_to_latest_route_with_eligible_orders_and_respects_filter(): void
    {
        Carbon::setTestNow('2026-09-08 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách nhận đơn', 'status' => 'active']);
        foreach (['2026-09-06', '2026-09-07', '2026-09-08'] as $date) {
            $order = Order::create([
                'user_id' => $shipper->id, 'shipper_id' => $shipper->id,
                'customer_id' => $customer->id, 'code' => 'AVAILABLE-'.$date,
                'status' => $date === '2026-09-08' ? Order::STATUS_DELIVERED : Order::STATUS_READY_TO_SHIP,
                'skip_auto_cancel' => true,
            ]);
            $order->forceFill(['created_at' => $date.' 08:00:00'])->saveQuietly();
            ShipperDispatchHistory::create([
                'schedule_date' => $date, 'published_at' => now(), 'version' => 1, 'created_by' => $shipper->id,
                'route_plan' => [['shipper_id' => $shipper->id, 'routes' => [
                    ['orders' => [['order_id' => $order->id]]],
                ]]],
            ]);
        }
        $this->actingAs($shipper)->get(route('shipper.available'))
            ->assertOk()->assertViewHas('selectedDate', '2026-09-07')->assertSee('AVAILABLE-2026-09-07');
        $this->get(route('shipper.available', ['date' => '2026-09-06']))
            ->assertOk()->assertViewHas('selectedDate', '2026-09-06')->assertSee('AVAILABLE-2026-09-06');
        $this->get(route('shipper.available', ['date' => '2026-09-05']))
            ->assertOk()->assertViewHas('selectedDate', '2026-09-05')->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
    }

    public function test_published_route_keeps_order_created_on_another_date_visible(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách lệch ngày', 'status' => 'active']);
        $order = Order::create([
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'customer_id' => $customer->id,
            'code' => 'ROUTE-CROSS-DATE',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $order->forceFill(['created_at' => '2026-09-08 08:00:00'])->saveQuietly();

        ShipperDispatchHistory::create([
            'schedule_date' => '2026-09-09',
            'published_at' => now(),
            'version' => 1,
            'created_by' => $shipper->id,
            'route_plan' => [[
                'shipper_id' => $shipper->id,
                'routes' => [[
                    'name' => 'Lộ trình lệch ngày',
                    'orders' => [['order_id' => $order->id]],
                ]],
            ]],
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.delivery-schedules', ['date' => '2026-09-09']))
            ->assertOk()
            ->assertSee('Lộ trình lệch ngày')
            ->assertSee('ROUTE-CROSS-DATE');
    }

    public function test_mobile_route_list_includes_orders_for_each_route(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách mobile lộ trình', 'status' => 'active']);
        $order = Order::create([
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'customer_id' => $customer->id,
            'code' => 'MOBILE-ROUTE-ORDER',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 125000,
        ]);

        $request = Request::create('/api/mobile/shipper/delivery-schedules/list', 'GET');
        $request->setUserResolver(fn () => $shipper->load('roles'));
        $payload = app(ShipperApiController::class)->deliveryScheduleList($request)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame($order->id, $payload['data'][0]['order_ids'][0]);
        $this->assertSame('MOBILE-ROUTE-ORDER', $payload['data'][0]['orders'][0]['code']);
        $this->assertSame('Khách mobile lộ trình', $payload['data'][0]['orders'][0]['customer']['name']);
    }

    public function test_mobile_available_orders_use_the_confirmed_schedule_workflow_date(): void
    {
        Carbon::setTestNow('2026-09-09 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách nhận đơn mobile', 'status' => 'active']);
        $order = Order::create([
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'customer_id' => $customer->id,
            'code' => 'MOBILE-AVAILABLE-ORDER',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => '2026-09-12',
        ]);
        $order->forceFill(['created_at' => '2026-09-09 08:00:00'])->saveQuietly();
        $order->histories()->create([
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_READY_TO_SHIP,
            'status_after' => Order::STATUS_READY_TO_SHIP,
        ]);

        $request = Request::create('/api/mobile/shipper/available-orders', 'GET');
        $request->setUserResolver(fn () => $shipper->load('roles'));
        $payload = app(ShipperApiController::class)->availableOrders($request)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('MOBILE-AVAILABLE-ORDER', $payload['data'][0]['code']);
    }

    public function test_available_page_keeps_delivered_orders_at_the_bottom_for_the_selected_date(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách trang nhận đơn', 'status' => 'active']);
        $readyOrder = Order::create([
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'customer_id' => $customer->id,
            'code' => 'AVAILABLE-FIRST',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $readyOrder->forceFill(['created_at' => '2026-09-10 08:00:00'])->saveQuietly();
        $readyOrder->histories()->create([
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_READY_TO_SHIP,
            'status_after' => Order::STATUS_READY_TO_SHIP,
        ]);
        $deliveredOrder = Order::create([
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'customer_id' => $customer->id,
            'code' => 'DELIVERED-LAST',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => '2026-09-10 09:00:00',
        ]);
        $deliveredOrder->forceFill(['created_at' => '2026-09-09 08:00:00'])->saveQuietly();

        $response = $this->actingAs($shipper)
            ->get(route('shipper.available', ['date' => '2026-09-10']));

        $response->assertOk()->assertSee('AVAILABLE-FIRST')->assertSee('DELIVERED-LAST');
        $response->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [
            $readyOrder->id,
            $deliveredOrder->id,
        ]);
    }

}
