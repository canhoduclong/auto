<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperWarehouseTransferVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_shipper_sees_active_transfer_even_when_order_delivery_date_is_different(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Ship Dương kiểm thử']);
        $shipper->roles()->attach($shipperRole);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $customer = Customer::create([
            'name' => 'Khách điều chuyển khác ngày',
            'phone' => '0900999888',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'warehouse_id' => $source->id,
            'code' => 'TRANSFER-DIFFERENT-DATE',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->subDay()->toDateString(),
            'total' => 0,
        ]);
        WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('TRANSFER-DIFFERENT-DATE')
            ->assertSee('Khách điều chuyển khác ngày');
    }
}
