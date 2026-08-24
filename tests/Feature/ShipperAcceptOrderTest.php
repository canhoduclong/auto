<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAcceptOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipper_can_accept_imported_order_with_non_stock_fee_item(): void
    {
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $customer = Customer::query()->create([
            'name' => 'Khách có phí giao hàng',
            'status' => 'active',
        ]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho nhận đơn',
            'status' => true,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'ORDER-WITH-NON-STOCK-FEE',
            'status' => Order::STATUS_READY_TO_SHIP,
            'skip_auto_cancel' => true,
        ]);
        $order->items()->create([
            'imported_name' => 'Phí ship',
            'product_id' => null,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 30000,
            'total' => 30000,
            'is_priced_by_kg' => false,
        ]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.accept', $order))
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_DELIVERING);

        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
        $document = InventoryDocument::query()->where('notes', 'Xuất kho cho đơn #'.$order->code)->sole();
        $this->assertCount(0, $document->items);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'shipper_accepted',
        ]);
    }
}
