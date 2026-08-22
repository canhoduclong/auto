<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperDispatchHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sent_route_is_archived_and_can_be_viewed_by_date(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $manager = User::factory()->create(['name' => 'Quản lý điều phối']);
        $manager->roles()->attach($managerRole);
        $shipper = User::factory()->create(['name' => 'Shipper lịch sử']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách lịch sử',
            'phone' => '0901234567',
            'status' => 'active',
        ]);
        $date = now()->toDateString();
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-HISTORY-1',
            'total' => 50000,
            'shipping_fee' => 50000,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $routePlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [[
                'name' => 'Lộ trình 1',
                'orders' => [[
                    'order_id' => $order->id,
                    'sequence' => 1,
                    'customer_name' => $customer->name,
                    'delivery_time' => 'Trước 8 giờ sáng',
                    'product_summary' => 'Sản phẩm mẫu - 10 con',
                    'quantity' => 10,
                    'origin' => 'Kho chính',
                    'destination' => 'Cần Thơ',
                    'final_fee' => 50000,
                    'note' => 'Gọi trước khi giao',
                ]],
            ]],
        ]];

        $this->actingAs($manager)
            ->postJson(route('shipper.create-delivery-schedule'), [
                'date' => $date,
                'notes' => 'Ca sáng',
                'route_plan' => json_encode($routePlan, JSON_UNESCAPED_UNICODE),
            ])
            ->assertOk();

        $this->assertDatabaseHas('shipper_dispatch_histories', [
            'schedule_date' => $date,
            'version' => 1,
            'orders_count' => 1,
            'total_fee' => 50000,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments.history', ['date' => $date]))
            ->assertOk()
            ->assertSee('Lịch sử điều phối giao hàng')
            ->assertSee('Shipper lịch sử')
            ->assertSee('Khách lịch sử')
            ->assertSee('Gọi trước khi giao');
    }

    public function test_restored_exception_can_be_published_and_seen_on_its_delivery_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 09:00:00', 'Asia/Bangkok'));
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $manager = User::factory()->create(['name' => 'Quản lý đơn ngoại lệ']);
        $manager->roles()->attach($managerRole);
        $shipper = User::factory()->create(['name' => 'Shipper đơn ngoại lệ']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách đơn ngoại lệ',
            'phone' => '0901234567',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'RESTORED-ROUTE-21',
            'total' => 50000,
            'shipping_fee' => 50000,
            // The order was entered late and its stored dates do not match the
            // route date selected by the manager.
            'delivery_date' => '2026-08-20',
            'status' => Order::STATUS_READY_TO_SHIP,
            'skip_auto_cancel' => true,
        ]);
        $order->forceFill(['created_at' => Carbon::parse('2026-08-19 08:00:00')])->saveQuietly();

        $routePlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [[
                'name' => 'Lộ trình ngoại lệ 21/08',
                'orders' => [[
                    'order_id' => $order->id,
                    'sequence' => 1,
                    'customer_name' => $customer->name,
                    'final_fee' => 50000,
                ]],
            ]],
        ]];

        $this->actingAs($manager)
            ->postJson(route('shipper.create-delivery-schedule'), [
                'date' => '2026-08-21',
                'route_plan' => json_encode($routePlan, JSON_UNESCAPED_UNICODE),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Đã gửi lịch trình giao hàng cho 1 shipper (1 đơn): Shipper đơn ngoại lệ (1 đơn). Các shipper sẽ nhận được thông báo xác nhận.');

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'schedule_created',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.delivery-schedules', ['date' => '2026-08-21']))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Xác nhận lịch trình & nhận đơn');
    }
}
