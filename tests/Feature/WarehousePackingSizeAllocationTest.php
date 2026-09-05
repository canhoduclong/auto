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

    public function test_it_rejects_a_mix_when_the_main_size_is_below_75_percent(): void
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

    public function test_actual_weight_uses_the_wider_quarter_kg_range_for_every_size(): void
    {
        [$user, $order, $item] = $this->fixture(100, 2.3);
        $order->update(['status' => Order::STATUS_PACKING]);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 204,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false);

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 205,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 255,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('warehouse.orders.logistics', $order), [
                'item_id' => $item->id,
                'item_actual_weight' => 256,
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

    public function test_shortage_for_an_arbitrary_size_shows_and_accepts_adjacent_sizes(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(100, 2.3);
        $inventories['2.3']->update(['quantity' => 75]);

        $this->actingAs($user)
            ->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Không đủ tồn size 2,3 — chọn size liền kề')
            ->assertSee('data-main-size="2.3"', false);

        $this->actingAs($user)
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.2']->id => 12,
                    $variants['2.3']->id => 75,
                    $variants['2.4']->id => 13,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id,
            'product_variant_id' => $variants['2.3']->id,
            'quantity' => 75,
        ]);
    }

    public function test_disabled_product_hides_adjacent_sizes_and_rejects_new_allocations(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(100, 2.3);
        $variants['2.3']->product->update(['allow_adjacent_packing_sizes' => false]);
        $inventories['2.3']->update(['quantity' => 75]);
        $this->actingAs($user)->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()->assertDontSee('Không đủ tồn size 2,3 — chọn size liền kề');
        $this->post(route('warehouse.orders.packing-size-allocation', $order), [
            'order_item_id' => $item->id,
            'allocations' => [$variants['2.2']->id => 25, $variants['2.3']->id => 75],
        ])->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
    }

    public function test_cut_product_shortage_uses_bill_weight_instead_of_piece_count(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(40, 2.3);
        $variants['2.3']->product->update(['product_type' => Product::TYPE_CUT]);
        $item->update(['unit_weight' => 2.3, 'is_priced_by_kg' => true]);
        $inventories['2.3']->update(['quantity' => 0]);
        $controller = app(\App\Http\Controllers\WarehouseDashboardController::class);
        $method = new \ReflectionMethod($controller, 'buildPackingQueueStockGuards');
        $result = $method->invoke($controller, collect([$order->fresh()]), $order->warehouse_id, now()->toDateString());
        $shortage = $result['guards'][$order->id]['shortages'][0];
        $this->assertSame(92.0, $shortage['required_qty']);
        $this->assertSame(92.0, $shortage['short_qty']);
        $this->assertSame('kg', $shortage['unit']);
        $inventories['2.3']->update(['quantity' => 42.06]);
        $result = $method->invoke($controller, collect([$order->fresh()]), $order->warehouse_id, now()->toDateString());
        $this->assertSame(49.94, $result['guards'][$order->id]['shortages'][0]['short_qty']);
    }

    public function test_cut_items_can_pack_fewer_pieces_when_bill_weight_is_met(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(40, 2.3);
        $variants['2.3']->product->update(['product_type' => Product::TYPE_CUT]);
        $item->update(['unit_weight' => 2.3, 'is_priced_by_kg' => true]);
        $order->update(['status' => Order::STATUS_PACKING]);
        $this->actingAs($user)->postJson(route('warehouse.orders.logistics', $order), [
            'item_id' => $item->id, 'item_packed_quantity' => 35, 'item_actual_weight' => 92,
        ])->assertOk();
        $this->assertSame(35, (int) $item->fresh()->packed_quantity);
        $this->assertSame(40, (int) $item->fresh()->quantity);
        $this->postJson(route('warehouse.orders.logistics', $order), [
            'item_id' => $item->id, 'item_packed_quantity' => 34, 'item_actual_weight' => 91,
        ])->assertUnprocessable();
        $this->assertSame(35, (int) $item->fresh()->packed_quantity);
        $variants['2.3']->product->update(['product_type' => Product::TYPE_WHOLE]);
        $this->postJson(route('warehouse.orders.logistics', $order), [
            'item_id' => $item->id, 'item_packed_quantity' => 35, 'item_actual_weight' => 92,
        ])->assertUnprocessable();
    }

    private function fixture(int $quantity, float $mainSize = 2.5): array
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

        $sizes = [$mainSize - 0.1, $mainSize, $mainSize + 0.1];
        $variants = collect($sizes)->mapWithKeys(function (float $size) use ($product) {
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
            'product_variant_id' => $variants[number_format($mainSize, 1, '.', '')]->id,
            'quantity' => $quantity,
            'unit_weight' => $mainSize,
            'is_priced_by_kg' => true,
            'total_weight' => $quantity * $mainSize,
            'price' => 70000,
            'total' => $quantity * $mainSize * 70000,
        ]);

        return [$user, $order, $item, $variants, $inventories];
    }
}
