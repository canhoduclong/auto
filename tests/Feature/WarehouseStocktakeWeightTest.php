<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStocktakeWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_stocktake_updates_quantity_and_weight_with_audit_history(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create(['stock' => 10, 'kg' => 2.5]);
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'weight_kg' => 25,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('warehouse.stocktakes.store'), [
                'warehouse_id' => $warehouse->id,
                'counted_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'items' => [
                    $inventory->id => [
                        'expected_quantity' => 10,
                        'expected_weight_kg' => 25,
                        'counted_quantity' => 9,
                        'counted_weight_kg' => 21.75,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('warehouse.stocktakes.index', ['warehouse_id' => $warehouse->id]));

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 9,
            'weight_kg' => 21.75,
        ]);
        $this->assertDatabaseHas('inventory_stocktake_items', [
            'inventory_id' => $inventory->id,
            'system_quantity' => 10,
            'counted_quantity' => 9,
            'difference' => -1,
            'system_weight_kg' => 25,
            'counted_weight_kg' => 21.75,
            'weight_difference' => -3.25,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'quantity' => -1,
            'weight_kg' => -3.25,
            'type' => 'stocktake_adjustment',
        ]);
    }

    public function test_weight_only_stocktake_keeps_quantity_unchanged(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'weight_kg' => 25,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user)->post(route('warehouse.stocktakes.store'), [
            'warehouse_id' => $warehouse->id,
            'counted_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'items' => [
                $inventory->id => [
                    'expected_quantity' => 10,
                    'expected_weight_kg' => 25,
                    'counted_weight_kg' => 24.4,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 10,
            'weight_kg' => 24.4,
        ]);
    }
}
