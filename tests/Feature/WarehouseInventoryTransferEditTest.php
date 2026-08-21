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
