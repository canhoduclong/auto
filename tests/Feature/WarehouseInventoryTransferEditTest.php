<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseInventoryTransferEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_uses_actual_reservations_when_cached_quantity_is_stale(): void
    {
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create(['name' => '2.3 kg']);
        $inventory = Inventory::create([
            'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id,
            'quantity' => 7,
            'reserved_quantity' => 7,
        ]);
        $this->actingAs($user)->get(route('warehouse.inventory-transfers.index'))
            ->assertOk()
            ->assertViewHas('availableVariants', fn ($rows) => $rows->firstWhere('variant_id', $variant->id)['available'] == 7);

        $payload = [
            'target_warehouse_id' => $target->id,
            'items' => [[
                'product_variant_id' => $variant->id,
                'quantity' => 3,
                'weight_kg' => 6.9,
            ]],
        ];
        $this->post(route('warehouse.inventory-transfers.store'), $payload)
            ->assertSessionHas('success');
        $transfer = WarehouseInventoryTransfer::query()->firstOrFail();
        $this->get(route('warehouse.inventory-transfers.edit', $transfer))
            ->assertOk()
            ->assertViewHas('availableVariants', fn ($rows) => $rows->firstWhere('variant_id', $variant->id)['available'] == 7);
        $payload['items'][0]['quantity'] = 7;
        $payload['items'][0]['weight_kg'] = 16.1;
        $this->put(route('warehouse.inventory-transfers.update', $transfer), $payload)
            ->assertSessionHas('success');
        $this->assertEquals(0, $inventory->fresh()->quantity);
    }

    public function test_actual_reservations_block_transfer_even_when_cached_quantity_is_zero(): void
    {
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create();
        $inventory = Inventory::create([
            'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id,
            'quantity' => 7,
            'reserved_quantity' => 0,
        ]);
        $customer = \App\Models\Customer::create(['user_id' => $user->id, 'name' => 'Test', 'status' => 'active']);
        $order = \App\Models\Order::create([
            'customer_id' => $customer->id, 'user_id' => $user->id,
            'warehouse_id' => $source->id, 'code' => 'RESERVED-TRANSFER',
            'status' => \App\Models\Order::STATUS_READY_TO_PACK, 'total' => 0,
        ]);
        $item = $order->items()->create([
            'product_id' => $variant->product_id, 'product_variant_id' => $variant->id,
            'quantity' => 7, 'price' => 0, 'total' => 0,
        ]);
        \App\Models\InventoryReservation::create([
            'order_item_id' => $item->id, 'inventory_id' => $inventory->id, 'quantity' => 7,
        ]);
        $this->actingAs($user)->get(route('warehouse.inventory-transfers.index'))
            ->assertOk()
            ->assertViewHas('availableVariants', fn ($rows) => ! $rows->contains('variant_id', $variant->id));
        $this->post(route('warehouse.inventory-transfers.store'), [
            'target_warehouse_id' => $target->id,
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1, 'weight_kg' => 2.3]],
        ])->assertSessionHasErrors('items');
        $this->assertEquals(7, $inventory->fresh()->quantity);
        $this->assertDatabaseCount('warehouse_inventory_transfers', 0);
    }

    public function test_new_transfer_saves_measured_weight_for_loss_baseline(): void
    {
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach(\App\Models\Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create(['kg' => 2.5, 'stock' => 10]);
        Inventory::create([
            'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('warehouse.inventory-transfers.store'), [
                'target_warehouse_id' => $target->id,
                'items' => [[
                    'product_variant_id' => $variant->id,
                    'quantity' => 3,
                    'weight_kg' => 7.35,
                    'unit_cost' => 10000,
                ]],
            ])
            ->assertRedirect(route('warehouse.inventory-transfers.index'));

        $this->assertDatabaseHas('warehouse_inventory_transfer_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'weight_kg' => 7.35,
        ]);
    }

    public function test_source_warehouse_can_edit_pending_transfer_and_stock_is_adjusted(): void
    {
        $source = Warehouse::factory()->create();
        $oldTarget = Warehouse::factory()->create();
        $newTarget = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create(['stock' => 7]);
        $inventory = Inventory::create([
            'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id,
            'quantity' => 7,
            'reserved_quantity' => 1,
        ]);
        $document = InventoryDocument::create([
            'type' => 'export',
            'document_date' => now()->toDateString(),
            'warehouse_id' => $source->id,
            'shipping_fee' => 0,
            'user_id' => $user->id,
        ]);
        $document->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_cost' => 10000,
        ]);
        $transfer = WarehouseInventoryTransfer::create([
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $oldTarget->id,
            'requested_by' => $user->id,
            'status' => WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
            'export_document_id' => $document->id,
            'requested_at' => now(),
        ]);
        $transfer->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'weight_kg' => 7.5,
            'unit_cost' => 10000,
        ]);

        $response = $this->actingAs($user)->put(
            route('warehouse.inventory-transfers.update', $transfer),
            [
                'target_warehouse_id' => $newTarget->id,
                'note' => 'Đã sửa nội dung',
                'items' => [[
                    'product_variant_id' => $variant->id,
                    'quantity' => 5,
                    'weight_kg' => 12.25,
                    'unit_cost' => 12000,
                ]],
            ]
        );

        $response->assertRedirect(route('warehouse.inventory-transfers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('warehouse_inventory_transfers', [
            'id' => $transfer->id,
            'target_warehouse_id' => $newTarget->id,
            'note' => 'Đã sửa nội dung',
        ]);
        $this->assertDatabaseHas('warehouse_inventory_transfer_items', [
            'transfer_id' => $transfer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'weight_kg' => 12.25,
            'unit_cost' => 12000,
        ]);
        $this->assertDatabaseHas('inventory_document_items', [
            'inventory_document_id' => $document->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_cost' => 12000,
        ]);
        $this->assertSame(5, (int) $inventory->fresh()->quantity);
        $this->assertSame(5, (int) $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'quantity' => -2,
            'type' => 'adjustment',
            'reference_id' => $transfer->id,
            'reference_type' => WarehouseInventoryTransfer::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_completed_transfer_cannot_be_edited(): void
    {
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create();
        $transfer = WarehouseInventoryTransfer::create([
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'requested_by' => $user->id,
            'status' => WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED,
            'requested_at' => now(),
            'received_at' => now(),
        ]);
        $transfer->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'weight_kg' => 7.5,
            'unit_cost' => 10000,
        ]);

        $response = $this->actingAs($user)->put(
            route('warehouse.inventory-transfers.update', $transfer),
            [
                'target_warehouse_id' => $target->id,
                'items' => [[
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                    'weight_kg' => 2.5,
                    'unit_cost' => 10000,
                ]],
            ]
        );

        $response->assertRedirect(route('warehouse.inventory-transfers.index'));
        $response->assertSessionHas('error');
        $this->assertSame(3, (int) $transfer->fresh()->items()->first()->quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }
}
