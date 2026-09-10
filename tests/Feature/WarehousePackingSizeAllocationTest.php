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

    public function test_product_permissions_allow_quantity_changes_while_packing_and_keep_other_products_locked(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(10);
        $otherProduct = Product::factory()->create();
        $otherVariant = ProductVariant::factory()->create(['product_id' => $otherProduct->id]);
        $otherItem = $order->items()->create(['product_id' => $otherProduct->id, 'product_variant_id' => $otherVariant->id,
            'quantity' => 1, 'price' => 70000, 'total' => 70000]);
        $order->update(['status' => Order::STATUS_PACKING, 'warehouse_product_permissions' => [
            $item->product_id => ['quantity' => true, 'sizes' => ['2.6']],
            $otherProduct->id => ['quantity' => false, 'sizes' => []],
        ]]);
        $this->assertTrue($order->allowsPackingSize(2.6, $item->product_id));
        $this->assertFalse($order->allowsPackingSize(2.6, $otherProduct->id));
        $this->actingAs($user)->get(route('warehouse.orders'))->assertOk()->assertSee('Lưu SL');
        $url = route('warehouse.orders.request-adjustment', $order);
        $this->postJson($url, ['reason' => 'Đóng linh động', 'items' => [['order_item_id' => $item->id, 'quantity' => 8]]])
            ->assertOk()->assertJsonPath('ok', true);
        $this->assertEquals(8, $item->fresh()->quantity);
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
        $this->assertEquals(8, InventoryReservation::where('order_item_id', $item->id)->sum('quantity'));
        $this->postJson($url, ['reason' => 'Không được phép', 'items' => [['order_item_id' => $otherItem->id, 'quantity' => 2]]])
            ->assertUnprocessable();
        $this->assertEquals(1, $otherItem->fresh()->quantity);
        $this->postJson($url, ['reason' => 'Thiếu hàng', 'items' => [['order_item_id' => $item->id, 'quantity' => 100000]]])
            ->assertUnprocessable();
        $this->assertEquals(8, $item->fresh()->quantity);
        $this->assertEquals(8, InventoryReservation::where('order_item_id', $item->id)->sum('quantity'));
    }

    public function test_authorized_warehouse_quantity_edit_updates_order_without_sale_confirmation(): void
    {
        [$user, $order, $item] = $this->fixture(100);
        $order->update(['warehouse_can_adjust' => true]);
        $this->actingAs($user)->postJson(route('warehouse.orders.request-adjustment', $order), [
            'reason' => 'Cập nhật số lượng trên đơn',
            'items' => [['order_item_id' => $item->id, 'quantity' => 80]],
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(80, (int) $item->fresh()->quantity);
        $this->assertSame(Order::WAREHOUSE_ADJUSTMENT_STATUS_NONE, $order->fresh()->warehouse_adjustment_status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id, 'action' => 'warehouse_direct_adjustment',
        ]);
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

    public function test_zero_percent_main_size_can_be_saved_and_started_with_a_distant_size(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(20, 2.2);
        $inventories['2.2']->update(['quantity' => 1]);
        $otherSize = $variants['2.3'];
        $otherSize->update(['size' => 2.8, 'kg' => 2.8]);
        $inventories['2.3']->update(['quantity' => 52]);

        $this->actingAs($user)->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()->assertSee('cho phép 0%');
        $this->post(route('warehouse.orders.packing-size-allocation', $order), [
            'order_item_id' => $item->id,
            'allocations' => [$variants['2.2']->id => 0, $otherSize->id => 20],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id, 'product_variant_id' => $otherSize->id, 'quantity' => 20,
        ]);
        $this->assertDatabaseMissing('order_item_packing_size_allocations', [
            'order_item_id' => $item->id, 'product_variant_id' => $variants['2.2']->id,
        ]);
        $this->assertSame(0, (int) $inventories['2.2']->fresh()->reserved_quantity);
        $this->assertSame(20, (int) $inventories['2.3']->fresh()->reserved_quantity);
        $this->post(route('warehouse.orders.start-packing', $order), [
            'packing_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
    }

    public function test_half_main_size_accepts_other_sizes_and_respects_prior_fifo_orders(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(50);
        $inventories['2.5']->update(['quantity' => 60]);
        $otherSize = $variants['2.6'];
        $otherSize->update(['size' => 2.8]);
        $prior = Order::create([
            'customer_id' => $order->customer_id, 'warehouse_id' => $order->warehouse_id,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $prior->forceFill(['created_at' => now()->subHour()])->saveQuietly();
        $prior->items()->create([
            'product_id' => $item->product_id, 'product_variant_id' => $variants['2.5']->id,
            'quantity' => 30, 'price' => 70000, 'total' => 2100000,
        ]);
        $controller = app(\App\Http\Controllers\WarehouseDashboardController::class);
        $options = (new \ReflectionMethod($controller, 'buildPackingSizeOptions'))->invoke(
            $controller, collect([$order->load('items.variant.product', 'items.packingSizeAllocations')]),
            [$order->id => ['shortages' => [['order_item_id' => $item->id]]]], $order->warehouse_id
        );
        $this->assertSame(30, collect($options[$item->id])->firstWhere('variant_id', $variants['2.5']->id)['available']);
        $url = route('warehouse.orders.packing-size-allocation', $order);
        $this->actingAs($user)->post($url, [
            'order_item_id' => $item->id,
            'allocations' => [$variants['2.5']->id => 31, $otherSize->id => 19],
        ])->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
        $this->actingAs($user)->post($url, [
            'order_item_id' => $item->id,
            'allocations' => [$variants['2.5']->id => 25, $otherSize->id => 25],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id, 'product_variant_id' => $variants['2.5']->id, 'quantity' => 25,
        ]);
    }

    public function test_it_accepts_a_mix_when_the_main_size_is_below_50_percent(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(100);

        $this->actingAs($user)
            ->post(route('warehouse.orders.packing-size-allocation', $order), [
                'order_item_id' => $item->id,
                'allocations' => [
                    $variants['2.4']->id => 25,
                    $variants['2.5']->id => 49,
                    $variants['2.6']->id => 26,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id,
            'product_variant_id' => $variants['2.5']->id,
            'quantity' => 49,
        ]);
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
            ->assertSee('Không đủ tồn size 2,3 — chọn size khác')
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
            ->assertOk()->assertDontSee('Không đủ tồn size 2,3 — chọn size khác');
        $this->post(route('warehouse.orders.packing-size-allocation', $order), [
            'order_item_id' => $item->id,
            'allocations' => [$variants['2.2']->id => 25, $variants['2.3']->id => 75],
        ])->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
    }

    public function test_cut_product_stock_guard_compares_piece_counts_and_can_start_packing(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(236, 2.5);
        $variants['2.5']->product->update(['product_type' => Product::TYPE_CUT]);
        $item->update(['unit_weight' => 2.5, 'is_priced_by_kg' => true]);
        $inventories['2.5']->update(['quantity' => 200]);
        $controller = app(\App\Http\Controllers\WarehouseDashboardController::class);
        $method = new \ReflectionMethod($controller, 'buildPackingQueueStockGuards');
        $result = $method->invoke($controller, collect([$order->fresh()]), $order->warehouse_id, now()->toDateString());
        $shortage = $result['guards'][$order->id]['shortages'][0];
        $this->assertSame(236.0, $shortage['required_qty']);
        $this->assertSame(36.0, $shortage['short_qty']);
        $this->assertSame('quantity', $shortage['unit']);

        $inventories['2.5']->update(['quantity' => 356]);
        $result = $method->invoke($controller, collect([$order->fresh()]), $order->warehouse_id, now()->toDateString());
        $this->assertTrue($result['guards'][$order->id]['can_start_packing']);
        $this->assertSame(120.0, $result['remaining_by_variant'][$variants['2.5']->id]);
        $this->actingAs($user)->post(route('warehouse.orders.start-packing', $order), [
            'packing_date' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
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

    public function test_mobile_lists_note_and_size_options_and_validates_saved_mix(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(10);
        $order->update(['note' => "Đóng kỹ\nGiao buổi sáng"]);
        $inventories['2.5']->update(['quantity' => 3]);
        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateMobileApiToken::class);
        $this->actingAs($user)->getJson('/api/mobile/warehouse/orders?date=2026-08-31')
            ->assertOk()->assertJsonPath('data.0.note', "Đóng kỹ\nGiao buổi sáng")
            ->assertJsonPath('data.0.stock_guard.has_shortage', true)
            ->assertJsonCount(3, 'data.0.items.0.packing_size_options');
        $url = '/api/mobile/warehouse/orders/'.$order->id.'/packing-size-allocation';
        $this->postJson($url, ['order_item_id' => $item->id, 'allocations' => [$variants['2.5']->id => 10]])
            ->assertUnprocessable();
        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
        $this->postJson($url, ['order_item_id' => $item->id, 'allocations' => [$variants['2.6']->id => 10]])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('order_item_packing_size_allocations', [
            'order_item_id' => $item->id, 'product_variant_id' => $variants['2.6']->id, 'quantity' => 10,
        ]);
        $variants['2.5']->product->update(['allow_adjacent_packing_sizes' => false]);
        $this->getJson('/api/mobile/warehouse/orders?date=2026-08-31')->assertOk()
            ->assertJsonCount(0, 'data.0.items.0.packing_size_options');
    }

    public function test_sale_selected_size_is_visible_without_shortage_and_other_sizes_are_rejected(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(10);
        $order->update(['warehouse_allowed_sizes' => ['2.6']]);
        $this->actingAs($user)->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('name="allocations['.$variants['2.6']->id.']"', false)
            ->assertDontSee('name="allocations['.$variants['2.5']->id.']"', false);
        $this->post(route('warehouse.orders.start-packing', $order))->assertSessionHasErrors('allocations');
        $url = route('warehouse.orders.packing-size-allocation', $order);
        $this->post($url, ['order_item_id' => $item->id, 'allocations' => [$variants['2.5']->id => 10]])
            ->assertSessionHasErrors('allocations');
        $this->post($url, ['order_item_id' => $item->id, 'allocations' => [$variants['2.6']->id => 10]])
            ->assertSessionHas('success');
        $order->update(['status' => Order::STATUS_PACKING]);
        $this->postJson(route('warehouse.orders.logistics', $order), [
            'item_id' => $item->id, 'item_actual_weight' => 28,
        ])->assertOk();
    }

    public function test_empty_sale_size_selection_hides_mix_and_rejects_allocations(): void
    {
        [$user, $order, $item, $variants, $inventories] = $this->fixture(10);
        $order->update(['warehouse_allowed_sizes' => []]);
        $inventories['2.5']->update(['quantity' => 0]);
        $this->actingAs($user)->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()->assertDontSee('name="allocations[', false);
        $this->post(route('warehouse.orders.packing-size-allocation', $order), [
            'order_item_id' => $item->id, 'allocations' => [$variants['2.6']->id => 10],
        ])->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('order_item_packing_size_allocations', 0);
    }

    public function test_latest_actual_weight_is_used_on_export_and_print_while_warehouse_keeps_packed_weight(): void
    {
        [$user, $order, $item, $variants] = $this->fixture(10);
        $order->update(['status' => Order::STATUS_PACKING, 'code' => 'ORD-PACKED-WEIGHT']);
        $boxProduct = Product::create(['user_id' => $user->id, 'name' => 'Thùng xốp', 'unit' => 'cai', 'is_priced_by_kg' => false]);
        $boxVariant = ProductVariant::create(['product_id' => $boxProduct->id, 'name' => 'Thùng', 'sku' => 'BOX']);
        $order->items()->create([
            'product_id' => $boxProduct->id, 'product_variant_id' => $boxVariant->id,
            'quantity' => 1, 'is_priced_by_kg' => false, 'price' => 70000, 'total' => 70000,
        ]);
        $url = route('warehouse.orders.logistics', $order);
        $this->actingAs($user)->postJson($url, ['item_id' => $item->id, 'item_actual_weight' => 24])->assertOk();
        $this->postJson($url, ['item_id' => $item->id, 'item_actual_weight' => 26])->assertOk();
        $this->assertSame(26.0, (float) $item->fresh()->packed_weight);
        $this->assertSame(1820000.0, (float) $item->fresh()->total);
        $this->assertSame(1890000.0, (float) $order->fresh()->total);

        $document = \App\Models\InventoryDocument::create([
            'type' => 'export', 'document_date' => now()->toDateString(),
            'warehouse_id' => $order->warehouse_id, 'user_id' => $user->id,
            'notes' => 'Xuất kho cho đơn #'.$order->code,
        ]);
        $document->items()->create(['product_variant_id' => $variants['2.5']->id, 'quantity' => 10, 'unit_cost' => 70000]);
        $document->items()->create(['product_variant_id' => $boxVariant->id, 'quantity' => 1, 'unit_cost' => 70000]);
        // Export documents use the latest actual weight; the packing screen retains the warehouse measurement.
        $item->fresh()->update(['actual_weight' => 27]);
        $order->update(['status' => Order::STATUS_DELIVERED, 'actual_weight' => 27]);
        $this->get(route('warehouse.stock-out.orders'))->assertOk()->assertSee('27kg')->assertSee('1,960,000')->assertDontSee('26kg');
        $this->get(route('warehouse.stock-out.show', $document))->assertOk()
            ->assertSee('27kg')->assertSee('1.890.000')->assertSee('1.960.000')->assertDontSee('26kg');
        $this->get(route('warehouse.orders', ['date' => now()->toDateString()]))->assertOk()
            ->assertSee('26 kg')->assertSee('1,890,000');

        $order->update(['status' => Order::STATUS_PACKING]);
        $this->postJson($url, ['item_id' => $item->id, 'clear_item_weight' => true])->assertOk();
        $this->assertNull($item->fresh()->packed_weight);
        $this->assertSame(1820000.0, (float) $order->fresh()->total);
    }

    public function test_packed_weight_repricing_preserves_vat_discounts_and_customer_fees(): void
    {
        [$user, $order, $item] = $this->fixture(10);
        $item->update(['base_price' => 75000]);
        $order->update([
            'status' => Order::STATUS_PACKING, 'order_discount' => 10000, 'order_discount_type' => 'decrease',
            'charge_vat' => true, 'vat_percent' => 10,
            'charge_shipping_fee' => true, 'shipping_fee' => 20000,
            'collect_customer_shipping_fee' => true, 'customer_shipping_fee' => 15000,
            'charge_foam_box_fee' => true, 'foam_box_price' => 30000,
        ]);
        $this->actingAs($user)->postJson(route('warehouse.orders.logistics', $order), [
            'item_id' => $item->id, 'item_actual_weight' => 26,
        ])->assertOk();
        $order->refresh();
        $this->assertSame(1950000.0, (float) $order->subtotal_amount);
        $this->assertSame(130000.0, (float) $order->item_discount_total);
        $this->assertSame(181000.0, (float) $order->vat_amount);
        $this->assertSame(2056000.0, (float) $order->total);
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
