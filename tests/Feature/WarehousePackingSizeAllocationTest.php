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

        $inventories['2.5']->update(['quantity' => 75]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventories['2.5']->id,
            'quantity' => 75,
        ]);
        $inventories['2.5']->update(['reserved_quantity' => 75]);

        $this->actingAs($user)
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.4']->id => 12,
                    $variants['2.5']->id => 75,
                    $variants['2.6']->id => 13,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id,
            'product_variant_id' => $variants['2.4']->id,
            'quantity' => 12,
        ]);
        $this->assertSame(100, (int) InventoryReservation::query()->where('order_item_id', $item->id)->sum('quantity'));
        $this->assertSame(12, (int) $inventories['2.4']->fresh()->reserved_quantity);
        $this->assertSame(75, (int) $inventories['2.5']->fresh()->reserved_quantity);
        $this->assertSame(13, (int) $inventories['2.6']->fresh()->reserved_quantity);

        $this->actingAs($user)
            ->post(route('warehouse.orders.start-packing', $order), [
                'packing_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
    }

    public function test_it_rejects_a_mix_when_size_25_is_below_75_percent(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(100);

        $this->actingAs($user)
            ->from(route('warehouse.orders'))
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.4']->id => 13,
                    $variants['2.5']->id => 74,
                    $variants['2.6']->id => 13,
                ],
            ])
            ->assertRedirect(route('warehouse.orders'))
            ->assertSessionHasErrors('allocations');

        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
    }

    public function test_size_25_actual_weight_allows_the_adjacent_size_range(): void
    {
        [$user, $order, $item] = $this->fixture(100);
        $order->update(['status' => Order::STATUS_PACKING]);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 239,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 240,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 260,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 261,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'clear_item_weight' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cleared', true);

        $this->assertNull($item->fresh()->actual_weight);
        $this->assertNull($item->fresh()->packed_weight);
        $this->assertNull($order->fresh()->actual_weight);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'warehouse_clear_item_weight',
        ]);
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
