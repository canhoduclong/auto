<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseAssignmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_can_review_and_print_orders_from_all_warehouses(): void
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
            'recipient_name' => 'Khách hàng nổi bật',
            'recipient_phone' => '0909123456',
            'recipient_address' => '123 Đường kiểm thử',
            'daily_sequence' => 12,
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $product = Product::query()->create(['user_id' => $warehouseUser->id, 'name' => 'Vịt nguyên con', 'status' => true]);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'Size 2.5', 'size' => 2.5, 'kg' => 2.5]);
        OrderItem::query()->create([
            'order_id' => $ownOrder->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'total_weight' => 7.5,
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
            ->assertSee('warehouse-print-own')
            ->assertSee('warehouse-print-other')
            ->assertSee('Khách hàng nổi bật')
            ->assertSee('0909123456')
            ->assertSee('123 Đường kiểm thử')
            ->assertSee('Vịt nguyên con')
            ->assertSee('STT ưu tiên')
            ->assertDontSee('<th>Lộ trình</th>', false)
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
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG');
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
            ->assertSee('warehouse-not-in-dispatch');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$order->id],
            ])
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG');
    }

    public function test_review_matches_either_order_creation_date_or_delivery_date(): void
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho theo ngày', 'status' => true]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::query()->create(['name' => 'Khách theo ngày', 'status' => 'active']);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'CREATED-TODAY-DELIVER-TOMORROW',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('created-today-deliver-tomorrow');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index', ['date' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('created-today-deliver-tomorrow');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$order->id],
            ])
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG');
    }

    public function test_review_only_includes_orders_ready_for_or_already_in_delivery(): void
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho lọc đóng hàng', 'status' => true]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::query()->create(['name' => 'Khách lọc', 'status' => 'active']);

        $orders = collect([
            ['code' => 'ORDER-NEW-PENDING', 'status' => 'pending'],
            ['code' => 'ORDER-APPROVED-NOT-PACKED', 'status' => Order::STATUS_APPROVED],
            ['code' => 'PACKING-NOT-DONE', 'status' => Order::STATUS_PACKING],
            ['code' => 'PACKING-DONE-LEGACY', 'status' => Order::STATUS_PACKED],
            ['code' => 'PACKING-DONE', 'status' => Order::STATUS_READY_TO_SHIP],
            ['code' => 'ORDER-PICKED-UP', 'status' => 'picked_up'],
            ['code' => 'ORDER-DELIVERING', 'status' => Order::STATUS_DELIVERING],
            ['code' => 'PACKING-CANCELLED', 'status' => Order::STATUS_CANCELLED],
            ['code' => 'TRANSFER-RECEIVED', 'status' => Order::STATUS_COMPLETED],
        ])->mapWithKeys(function (array $data) use ($customer, $warehouseUser, $warehouse) {
            $order = Order::query()->create($data + [
                'customer_id' => $customer->id,
                'user_id' => $warehouseUser->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => now()->toDateString(),
            ]);

            return [$data['code'] => $order];
        });

        WarehouseTransfer::query()->create([
            'order_id' => $orders['TRANSFER-RECEIVED']->id,
            'source_warehouse_id' => $warehouse->id,
            'target_warehouse_id' => $warehouse->id,
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            'received_at' => now(),
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.assignment-review.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('packing-done-legacy')
            ->assertSee('packing-done')
            ->assertSee('order-picked-up')
            ->assertSee('order-delivering')
            ->assertSee('transfer-received')
            ->assertDontSee('order-new-pending')
            ->assertDontSee('order-approved-not-packed')
            ->assertDontSee('packing-not-done')
            ->assertDontSee('packing-cancelled');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.assignment-review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$orders['PACKING-NOT-DONE']->id],
            ])
            ->assertForbidden();
    }
}
