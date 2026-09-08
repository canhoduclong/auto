<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
