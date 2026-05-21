<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_preassign_order_without_changing_warehouse_status(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']) ;
        $manager->roles()->attach($managerRole->id);

        $shipper = User::factory()->create(['name' => 'Shipper A']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer A',
            'phone' => '0900000000',
            'status' => 'active',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-PREASSIGN-1',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.assign-order', [$order, $shipper]));

        $response->assertSessionHas('success');

        $order->refresh();

        $this->assertSame($shipper->id, (int) $order->shipper_id);
        $this->assertSame(Order::STATUS_READY_TO_PACK, $order->status);
    }

    public function test_manager_can_bulk_transfer_assigned_orders(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $fromShipper = User::factory()->create(['name' => 'Shipper A']);
        $fromShipper->roles()->attach($shipperRole->id);

        $toShipper = User::factory()->create(['name' => 'Shipper B']);
        $toShipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer B',
            'phone' => '0911111111',
            'status' => 'active',
        ]);

        $orderOne = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-1',
            'total' => 0,
            'status' => Order::STATUS_PACKING,
            'shipper_id' => $fromShipper->id,
        ]);

        $orderTwo = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-2',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_SHIP,
            'shipper_id' => $fromShipper->id,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.bulk-transfer-assignments'), [
            'from_shipper_id' => $fromShipper->id,
            'to_shipper_id' => $toShipper->id,
        ]);

        $response->assertSessionHas('success');

        $orderOne->refresh();
        $orderTwo->refresh();

        $this->assertSame($toShipper->id, (int) $orderOne->shipper_id);
        $this->assertSame($toShipper->id, (int) $orderTwo->shipper_id);
        $this->assertSame(Order::STATUS_PACKING, $orderOne->status);
        $this->assertSame(Order::STATUS_READY_TO_SHIP, $orderTwo->status);
    }
}