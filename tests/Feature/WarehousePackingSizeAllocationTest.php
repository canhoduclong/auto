<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePackingSizeAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-31 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_saves_a_valid_adjacent_size_mix_and_moves_reservations(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(100);

        $inventories['2.5']->update(['quantity' => 80]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventories['2.5']->id,
            'quantity' => 80,
        ]);
        $inventories['2.5']->update(['reserved_quantity' => 80]);

        $this->actingAs($user)
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.4']->id => 10,
                    $variants['2.5']->id => 80,
                    $variants['2.6']->id => 10,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id,
            'product_variant_id' => $variants['2.4']->id,
            'quantity' => 10,
        ]);
        $this->assertSame(100, (int) InventoryReservation::query()->where('order_item_id', $item->id)->sum('quantity'));
        $this->assertSame(10, (int) $inventories['2.4']->fresh()->reserved_quantity);
        $this->assertSame(80, (int) $inventories['2.5']->fresh()->reserved_quantity);
        $this->assertSame(10, (int) $inventories['2.6']->fresh()->reserved_quantity);

        $this->actingAs($user)
            ->post(route('warehouse.orders.start-packing', $order), [
                'packing_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
    }

    public function test_it_rejects_a_mix_when_size_25_is_not_more_than_70_percent(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(100);

        $this->actingAs($user)
            ->from(route('warehouse.orders'))
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.4']->id => 15,
                    $variants['2.5']->id => 70,
                    $variants['2.6']->id => 15,
                ],
            ])
            ->assertRedirect(route('warehouse.orders'))
            ->assertSessionHasErrors('allocations');

        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
    }

    public function test_size_25_actual_weight_must_average_between_247_and_257(): void
    {
        [$user, $order, $item] = $this->fixture(100);
        $order->update(['status' => Order::STATUS_PACKING]);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 246,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 250,
            ])
            ->assertOk();
    }

    private function fixture(int $quantity): array
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho size mix', 'status' => true]);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::query()->create(['name' => 'warehouse']));
        $customer = Customer::query()->create(['name' => 'Khách size mix', 'status' => 'active']);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'name' => 'Vịt mốc',
            'unit' => 'con',
            'status' => true,
        ]);

        $variants = collect([2.4, 2.5, 2.6])->mapWithKeys(function (float $size) use ($product) {
            $key = number_format($size, 1, '.', '');
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'name' => $key.' kg',
                'sku' => 'SIZE-'.$key,
                'size' => $size,
                'kg' => $size,
            ]);

            return [$key => $variant];
        });
        $inventories = $variants->mapWithKeys(fn (ProductVariant $variant, string $key) => [
            $key => Inventory::query()->create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'reserved_quantity' => 0,
            ]),
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => Order::STATUS_READY_TO_PACK,
            'delivery_date' => now()->toDateString(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variants['2.5']->id,
            'quantity' => $quantity,
            'unit_weight' => 2.5,
            'is_priced_by_kg' => true,
            'total_weight' => $quantity * 2.5,
            'price' => 70000,
            'total' => $quantity * 2.5 * 70000,
        ]);

        return [$user, $order, $item, $variants, $inventories];
    }
}
