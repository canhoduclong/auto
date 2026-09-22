<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStocktakeWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_choose_a_warehouse_and_complete_stocktake_from_accounting_menu(): void
    {
        Warehouse::factory()->create(['name' => 'Kho Kế Toán A', 'status' => true]);
        $warehouse = Warehouse::factory()->create(['name' => 'Kho Kế Toán B', 'status' => true]);
        $accountant = User::factory()->create(['warehouse_id' => null]);
        $accountant->roles()->attach(Role::create(['name' => 'accounting']));
        $variant = ProductVariant::factory()->create(['stock' => 10, 'kg' => 2.5]);
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'weight_kg' => 25,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($accountant)
            ->get(route('accounting.stocktakes.index', ['warehouse_id' => $warehouse->id]))
            ->assertOk()
            ->assertSee('Kiểm Kê Kho')
            ->assertSee('value="'.$warehouse->id.'"', false)
            ->assertSee(route('accounting.stocktakes.store'), false);

        $this->actingAs($accountant)
            ->post(route('accounting.stocktakes.store'), [
                'warehouse_id' => $warehouse->id,
                'counted_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'items' => [$inventory->id => [
                    'expected_quantity' => 10,
                    'expected_weight_kg' => 25,
                    'counted_quantity' => 9,
                    'counted_weight_kg' => 22.5,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('accounting.stocktakes.index', ['warehouse_id' => $warehouse->id]));

        $this->assertDatabaseHas('inventory_stocktakes', [
            'warehouse_id' => $warehouse->id,
            'created_by' => $accountant->id,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 9,
            'weight_kg' => 22.5,
        ]);
    }

    public static function wholeQuantityInputs(): array
    {
        return [['9'], ['9.000']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('wholeQuantityInputs')]
    public function test_stocktake_updates_quantity_and_weight_with_audit_history(string $quantity): void
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
                        'counted_quantity' => $quantity,
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

    public function test_stocktake_rejects_fractional_counts_without_changing_inventory(): void
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
            'items' => [$inventory->id => [
                'expected_quantity' => '10.000',
                'expected_weight_kg' => '25.000',
                'counted_quantity' => '9.500',
            ]],
        ])->assertSessionHasErrors('items.'.$inventory->id.'.counted_quantity');

        $this->assertSame(10.0, (float) $inventory->fresh()->quantity);
        $this->assertDatabaseCount('inventory_stocktakes', 0);
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

    public function test_stocktake_preserves_packed_goods_waiting_for_shipper(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $product = Product::factory()->create();
        $variant = $product->variants()->create(['name' => 'Đã đóng', 'sku' => 'PACKED-STOCKTAKE']);
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'weight_kg' => 0,
            'reserved_quantity' => 4,
        ]);
        $customer = Customer::query()->create([
            'user_id' => $user->id,
            'name' => 'Khách chờ shipper',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'PACKED-STOCKTAKE-1',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 0,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'price' => 0,
            'total' => 0,
            'is_priced_by_kg' => false,
        ]);
        $reservation = InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventory->id,
            'quantity' => 4,
            'reserved_at' => now(),
        ]);

        $this->actingAs($user)->post(route('warehouse.stocktakes.store'), [
            'warehouse_id' => $warehouse->id,
            'counted_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'items' => [$inventory->id => [
                'expected_quantity' => 10,
                'expected_weight_kg' => 0,
                // Only six unpacked units are physically left on the shelf.
                'counted_quantity' => 6,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(10.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(4.0, (float) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseHas('inventory_reservations', ['id' => $reservation->id, 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_stocktake_items', [
            'inventory_id' => $inventory->id,
            'system_quantity' => 10,
            'counted_quantity' => 10,
            'physical_counted_quantity' => 6,
            'packed_reserved_quantity' => 4,
            'difference' => 0,
        ]);
    }

    public function test_past_stocktake_loads_historical_balance_and_only_applies_difference_to_current_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $variant = ProductVariant::factory()->create(['stock' => 15, 'kg' => 2.5]);
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 15,
            'weight_kg' => 37.5,
            'reserved_quantity' => 0,
        ]);
        $countedAt = now()->subDays(5)->startOfHour();

        $movement = new InventoryMovement;
        $movement->forceFill([
            'inventory_id' => $inventory->id,
            'quantity' => 5,
            'weight_kg' => 12.5,
            'type' => 'import',
            'reference_id' => $inventory->id,
            'reference_type' => Inventory::class,
            'user_id' => $user->id,
            'created_at' => $countedAt->copy()->addDay(),
            'updated_at' => $countedAt->copy()->addDay(),
        ])->save();

        $this->actingAs($user)
            ->get(route('warehouse.stocktakes.index', [
                'warehouse_id' => $warehouse->id,
                'counted_at' => $countedAt->format('Y-m-d H:i:s'),
            ]))
            ->assertOk()
            ->assertSee('value="10.000"', false)
            ->assertSee('data-system="25.000"', false);

        $this->actingAs($user)->post(route('warehouse.stocktakes.store'), [
            'warehouse_id' => $warehouse->id,
            'counted_at' => $countedAt->format('Y-m-d H:i:s'),
            'items' => [
                $inventory->id => [
                    'expected_quantity' => 10,
                    'expected_weight_kg' => 25,
                    'counted_quantity' => 8,
                    'counted_weight_kg' => 20,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 13,
            'weight_kg' => 32.5,
        ]);
        $this->assertDatabaseHas('inventory_stocktake_items', [
            'inventory_id' => $inventory->id,
            'system_quantity' => 10,
            'counted_quantity' => 8,
            'difference' => -2,
            'system_weight_kg' => 25,
            'counted_weight_kg' => 20,
            'weight_difference' => -5,
        ]);

        $adjustment = InventoryMovement::query()
            ->where('inventory_id', $inventory->id)
            ->where('type', 'stocktake_adjustment')
            ->sole();
        $this->assertSame($countedAt->format('Y-m-d H:i:s'), $adjustment->created_at->format('Y-m-d H:i:s'));
    }

    public function test_stocktake_can_choose_opening_or_closing_balance_for_a_day(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::create(['name' => 'warehouse']));
        $inventoryDate = now()->subDays(3)->startOfDay();
        $inventory = Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
            'weight_kg' => 15,
            'reserved_quantity' => 0,
        ]);

        $movement = new InventoryMovement;
        $movement->forceFill([
            'inventory_id' => $inventory->id,
            'quantity' => 5,
            'weight_kg' => 5,
            'type' => 'import',
            'reference_id' => $inventory->id,
            'reference_type' => Inventory::class,
            'user_id' => $user->id,
            'created_at' => $inventoryDate->copy()->addHours(10),
            'updated_at' => $inventoryDate->copy()->addHours(10),
        ])->save();

        $this->actingAs($user)
            ->get(route('warehouse.stocktakes.index', [
                'warehouse_id' => $warehouse->id,
                'inventory_date' => $inventoryDate->toDateString(),
                'stocktake_type' => 'opening',
            ]))
            ->assertOk()
            ->assertSee('Tồn đầu')
            ->assertSee('value="10.000"', false);

        $this->actingAs($user)
            ->get(route('warehouse.stocktakes.index', [
                'warehouse_id' => $warehouse->id,
                'inventory_date' => $inventoryDate->toDateString(),
                'stocktake_type' => 'closing',
            ]))
            ->assertOk()
            ->assertSee('Tồn cuối')
            ->assertSee('value="15.000"', false);

        $this->actingAs($user)
            ->post(route('warehouse.stocktakes.store'), [
                'warehouse_id' => $warehouse->id,
                'counted_at' => $inventoryDate->copy()->endOfDay()->format('Y-m-d H:i:s'),
                'stocktake_type' => 'closing',
                'items' => [
                    $inventory->id => [
                        'expected_quantity' => 15,
                        'expected_weight_kg' => 15,
                        'counted_quantity' => 14,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inventory_stocktakes', [
            'warehouse_id' => $warehouse->id,
            'stocktake_type' => 'closing',
        ]);
        $stocktake = \App\Models\InventoryStocktake::query()
            ->where('warehouse_id', $warehouse->id)
            ->sole();
        $this->assertDatabaseHas('inventory_adjustments', [
            'inventory_id' => $inventory->id,
            'quantity' => -1,
            'reason' => 'Kiểm kê tồn cuối '.$stocktake->code,
        ]);
    }
}
