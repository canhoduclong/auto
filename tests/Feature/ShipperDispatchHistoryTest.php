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

    public function test_resending_one_shipper_keeps_delivered_stops_without_sending_them_again(): void
    {
        $manager = User::factory()->create(['name' => 'Quản lý']);
        $manager->roles()->attach(Role::create(['name' => 'manager_shipper']));
        $shipper = User::factory()->create(['name' => 'Shipper A']);
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách tuyến cũ', 'status' => 'active']);
        $date = now()->toDateString();
        $delivered = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'DELIVERED-STOP',
            'status' => Order::STATUS_DELIVERED,
        ]);
        $pending = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'PENDING-STOP',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $oldPlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [['name' => 'Tuyến cũ', 'orders' => [['order_id' => $delivered->id], ['order_id' => $pending->id]]]],
        ]];
        \App\Models\ShipperDispatchHistory::create([
            'schedule_date' => $date,
            'version' => 1,
            'route_plan' => $oldPlan,
            'orders_count' => 2,
            'published_at' => now(),
        ]);
        $editedPlan = $oldPlan;
        $editedPlan[0]['routes'][0]['name'] = 'Tuyến đã chỉnh';

        $this->actingAs($manager)->postJson(route('shipper.create-delivery-schedule'), [
            'date' => $date,
            'shipper_id' => $shipper->id,
            'route_plan' => json_encode($editedPlan, JSON_UNESCAPED_UNICODE),
        ])->assertOk()->assertJsonPath(
            'message',
            'Đã gửi lịch trình giao hàng cho 1 shipper (1 đơn): Shipper A (1 đơn). Các shipper sẽ nhận được thông báo xác nhận.'
        );

        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $delivered->id,
            'action' => 'schedule_created',
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $pending->id,
            'action' => 'schedule_created',
        ]);
        $latest = \App\Models\ShipperDispatchHistory::query()->latest('version')->firstOrFail();
        $this->assertSame([$delivered->id, $pending->id], collect($latest->route_plan[0]['routes'][0]['orders'])->pluck('order_id')->all());
    }

    public function test_invalid_route_message_identifies_order_customer_and_reason(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $manager = User::factory()->create(['name' => 'Quản lý điều phối']);
        $manager->roles()->attach($managerRole);
        $shipper = User::factory()->create(['name' => 'Shipper tuyến']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách cần nhận diện',
            'phone' => '0901234567',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-INVALID-ROUTE',
            'total' => 50000,
            'shipping_fee' => 50000,
            'status' => Order::STATUS_COMPLETED,
        ]);
        $routePlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [[
                'name' => 'Lộ trình 1',
                'orders' => [[
                    'order_id' => $order->id,
                    'customer_name' => $customer->name,
                    'final_fee' => 50000,
                ]],
            ]],
        ]];

        $this->actingAs($manager)
            ->postJson(route('shipper.create-delivery-schedule'), [
                'date' => now()->toDateString(),
                'route_plan' => json_encode($routePlan, JSON_UNESCAPED_UNICODE),
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Đơn không còn hợp lệ trong lộ trình: ORD-INVALID-ROUTE – Khách cần nhận diện (trạng thái hiện tại: Hoàn thành). Vui lòng quay lại trang điều phối và tải lại dữ liệu.',
            ]);
    }

    public function test_delivered_order_is_completed_and_cannot_be_sent_in_another_route(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $manager = User::factory()->create(['name' => 'Quản lý điều phối']);
        $manager->roles()->attach($managerRole);
        $shipper = User::factory()->create(['name' => 'Shipper đã giao']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách đã nhận hàng',
            'phone' => '0907654321',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-ALREADY-DELIVERED',
            'total' => 75000,
            // Reproduce stale data whose status was not advanced even though
            // the shipper had already recorded customer delivery.
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $order->histories()->create([
            'action' => 'delivered',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after' => Order::STATUS_DELIVERED,
            'note' => 'Khách đã nhận hàng.',
        ]);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee('ORD-ALREADY-DELIVERED');

        $this->actingAs($shipper)
            ->get(route('shipper.my-orders'))
            ->assertOk()
            ->assertSee('ORD-ALREADY-DELIVERED')
            ->assertSee('Hoàn thành: 1')
            ->assertSee('Đơn đã hoàn thành, không còn thao tác')
            ->assertDontSee('Nhận đơn để giao');

        $this->actingAs($shipper)
            ->get(route('shipper.delivery-schedules', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Danh sách đã giao')
            ->assertSee('ORD-ALREADY-DELIVERED')
            ->assertSee('Đang thực hiện: <strong>0</strong> đơn', false)
            ->assertSee('Đã giao: <strong>1</strong> đơn', false)
            ->assertDontSee('name="order_ids[]" value="'.$order->id.'"', false);

        $routePlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [[
                'name' => 'Lộ trình 1',
                'orders' => [[
                    'order_id' => $order->id,
                    'customer_name' => $customer->name,
                    'final_fee' => 0,
                ]],
            ]],
        ]];

        $this->actingAs($manager)
            ->postJson(route('shipper.create-delivery-schedule'), [
                'date' => now()->toDateString(),
                'route_plan' => json_encode($routePlan, JSON_UNESCAPED_UNICODE),
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Đơn không còn hợp lệ trong lộ trình: ORD-ALREADY-DELIVERED – Khách đã nhận hàng (đã giao khách, không cần gửi lại lộ trình). Vui lòng quay lại trang điều phối và tải lại dữ liệu.',
            ]);
    }

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
            ->assertSee('Xác nhận lộ trình & nhận đơn');
    }
}
