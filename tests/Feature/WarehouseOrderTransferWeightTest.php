<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseOrderTransferWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_order_transfer_snapshots_packed_weight_for_future_loss_calculation(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create();
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách kiểm tra kg điều chuyển',
            'phone' => '0900777666',
            'status' => 'active',
        ]);
        $variant = ProductVariant::factory()->create(['kg' => 2.5]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'code' => 'ORDER-TRANSFER-WEIGHT',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 0,
        ]);
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'price' => 0,
            'packed_weight' => 7.35,
            'total_weight' => 7.5,
            'unit_weight' => 2.5,
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.order-transfers.store'), [
                'shipper_id' => $shipper->id,
                'warehouse_id' => $target->id,
                'order_ids' => (string) $order->id,
            ])
            ->assertRedirect(route('warehouse.order-transfers'));

        $this->assertDatabaseHas('warehouse_transfers', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 7.35,
        ]);
    }
}
