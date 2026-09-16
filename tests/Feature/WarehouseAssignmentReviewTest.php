<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseAssignmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_review_and_print_are_scoped_and_tracked_independently(): void
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho A', 'status' => true]);
        $otherWarehouse = Warehouse::query()->create(['name' => 'Kho B', 'status' => true]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::query()->create(['name' => 'Khách kho', 'status' => 'active']);
        $ownOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'WAREHOUSE-PRINT-OWN',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $otherOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $otherWarehouse->id,
            'code' => 'WAREHOUSE-PRINT-OTHER',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        ShipperDispatchHistory::query()->create([
            'schedule_date' => now()->toDateString(),
            'version' => 1,
            'route_plan' => [[
                'shipper_id' => $warehouseUser->id,
                'shipper_name' => 'Shipper A',
                'routes' => [[
                    'name' => 'Chuyến kho',
                    'orders' => [
                        ['order_id' => $ownOrder->id],
                        ['order_id' => $otherOrder->id],
                    ],
                ]],
            ]],
            'orders_count' => 2,
            'created_by' => $warehouseUser->id,
            'published_at' => now(),
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index'))
            ->assertOk()
            ->assertSee('Review &amp; In ấn của Kho', false)
            ->assertSee('WAREHOUSE-PRINT-OWN')
            ->assertDontSee('WAREHOUSE-PRINT-OTHER')
            ->assertSee('Kho chưa in');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$ownOrder->id],
            ])
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG');

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $ownOrder->id,
            'action' => 'warehouse_delivery_note_printed',
            'user_id' => $warehouseUser->id,
        ]);
        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $ownOrder->id,
            'action' => 'delivery_note_printed',
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$otherOrder->id],
            ])
            ->assertForbidden();
    }

    public function test_review_includes_warehouse_orders_not_present_in_latest_dispatch(): void
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho nguồn', 'status' => true]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::query()->create(['name' => 'Khách nhận', 'status' => 'active']);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => now()->toDateString(),
            'code' => 'WAREHOUSE-NOT-IN-DISPATCH',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('WAREHOUSE-NOT-IN-DISPATCH');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$order->id],
            ])
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG');
    }

    public function test_review_only_shows_orders_that_finished_packing_and_are_not_cancelled(): void
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho lọc đóng hàng', 'status' => true]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::query()->create(['name' => 'Khách lọc', 'status' => 'active']);

        foreach ([
            ['code' => 'PACKING-NOT-DONE', 'status' => Order::STATUS_PACKING],
            ['code' => 'PACKING-DONE', 'status' => Order::STATUS_READY_TO_SHIP],
            ['code' => 'PACKING-CANCELLED', 'status' => Order::STATUS_CANCELLED],
        ] as $data) {
            Order::query()->create($data + [
                'customer_id' => $customer->id,
                'user_id' => $warehouseUser->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => now()->toDateString(),
            ]);
        }

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('PACKING-DONE')
            ->assertDontSee('PACKING-NOT-DONE')
            ->assertDontSee('PACKING-CANCELLED');
    }
}
