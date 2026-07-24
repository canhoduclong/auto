<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperDispatchHistoryTest extends TestCase
{
    use RefreshDatabase;

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
}
