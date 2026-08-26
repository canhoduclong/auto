<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePackingDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_can_save_packing_specification_when_package_count_is_unknown(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $warehouse = Warehouse::create(['name' => 'Kho đóng hàng']);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::create(['name' => 'Khách đóng hàng', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'PACKING-DETAILS-1',
            'status' => Order::STATUS_PACKING,
        ]);

        $this->actingAs($warehouseUser)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'packing_details' => true,
                'package_count' => null,
                'packing_specification' => '2 bọc lớn, mỗi bọc khoảng 8 kg',
            ])
            ->assertOk()
            ->assertJsonPath('order.package_count', null)
            ->assertJsonPath('order.packing_specification', '2 bọc lớn, mỗi bọc khoảng 8 kg');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'package_count' => null,
            'packing_specification' => '2 bọc lớn, mỗi bọc khoảng 8 kg',
        ]);

        $this->actingAs($warehouseUser)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'packing_details' => true,
                'package_count' => null,
                'packing_specification' => '',
            ])
            ->assertJsonValidationErrors('packing_specification');
    }
}
