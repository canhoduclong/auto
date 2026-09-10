<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAcceptOrderTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('transferredPickupCases')]
    public function test_transferred_pickup_uses_target_stock_and_actual_reservations(bool $sourceReservation, int $targetQuantity, int $otherReserved, bool $succeeds): void
    {
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $customer = Customer::create(['name' => 'Khách chuyển tiếp', 'status' => 'active']);
        $variant = \App\Models\ProductVariant::factory()->create();
        $order = Order::create([
            'customer_id' => $customer->id, 'user_id' => $shipper->id,
            'shipper_id' => $shipper->id, 'warehouse_id' => $target->id,
            'status' => Order::STATUS_READY_TO_SHIP, 'code' => 'TRANSFERRED-PICKUP',
        ]);
        $item = $order->items()->create([
            'product_id' => $variant->product_id, 'product_variant_id' => $variant->id,
            'quantity' => 2, 'price' => 10000, 'total' => 20000,
        ]);
        $sourceStock = Inventory::create([
            'warehouse_id' => $source->id, 'product_variant_id' => $variant->id,
            'quantity' => 0, 'reserved_quantity' => $sourceReservation ? 2 : 0,
        ]);
        $targetStock = Inventory::create([
            'warehouse_id' => $target->id, 'product_variant_id' => $variant->id,
            'quantity' => $targetQuantity, 'reserved_quantity' => 0,
        ]);
        InventoryReservation::create([
            'order_item_id' => $item->id,
            'inventory_id' => $sourceReservation ? $sourceStock->id : $targetStock->id,
            'quantity' => 2,
        ]);
        if ($otherReserved > 0) {
            $otherOrder = Order::create([
                'customer_id' => $customer->id, 'user_id' => $shipper->id,
                'warehouse_id' => $target->id, 'status' => Order::STATUS_PACKING,
            ]);
            $otherItem = $otherOrder->items()->create([
                'product_id' => $variant->product_id, 'product_variant_id' => $variant->id,
                'quantity' => $otherReserved, 'price' => 10000, 'total' => $otherReserved * 10000,
            ]);
            InventoryReservation::create([
                'order_item_id' => $otherItem->id, 'inventory_id' => $targetStock->id, 'quantity' => $otherReserved,
            ]);
        }
        \App\Models\WarehouseTransfer::create([
            'order_id' => $order->id, 'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id, 'shipper_id' => $shipper->id,
            'status' => \App\Models\WarehouseTransfer::STATUS_RECEIVED_COMPLETED, 'received_at' => now(),
        ]);
        $order->histories()->create([
            'action' => 'schedule_confirmed', 'user_id' => $shipper->id,
            'role' => 'shipper', 'status_after' => Order::STATUS_READY_TO_SHIP,
        ]);

        $response = $this->actingAs($shipper)->postJson(route('shipper.accept', $order));
        if ($succeeds) {
            $response->assertOk();
            $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
            $this->assertEquals($targetQuantity - 2, $targetStock->fresh()->quantity);
            $this->assertEquals($otherReserved, $targetStock->fresh()->reserved_quantity);
            $this->assertDatabaseMissing('inventory_reservations', ['order_item_id' => $item->id]);
            $document = InventoryDocument::where('notes', 'Xuất kho cho đơn #'.$order->code)->sole();
            $this->assertEquals($target->id, $document->warehouse_id);
            $this->assertEquals(-2, InventoryMovement::where('reference_id', $document->id)
                ->where('reference_type', InventoryDocument::class)->where('inventory_id', $targetStock->id)->sum('quantity'));
            $this->postJson(route('shipper.accept', $order))->assertStatus(409);
            $this->assertEquals($targetQuantity - 2, $targetStock->fresh()->quantity);
        } else {
            $response->assertStatus(422);
            $this->assertSame(Order::STATUS_READY_TO_SHIP, $order->fresh()->status);
            $this->assertEquals($targetQuantity, $targetStock->fresh()->quantity);
            $this->assertDatabaseHas('inventory_reservations', ['order_item_id' => $item->id, 'quantity' => 2]);
            $this->assertDatabaseMissing('inventory_documents', ['notes' => 'Xuất kho cho đơn #'.$order->code]);
        }
        $this->assertEquals(0, $sourceStock->fresh()->quantity);
    }

    public static function transferredPickupCases(): array
    {
        return [
            'stale target reserved total' => [false, 5, 1, true],
            'legacy reservation at source' => [true, 5, 1, true],
            'insufficient physical stock' => [false, 1, 0, false],
            'target stock reserved for another order' => [true, 2, 2, false],
        ];
    }

    public function test_shipper_can_rollback_an_accepted_order_and_restore_exported_stock(): void
    {
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $customer = Customer::query()->create(['name' => 'Khách hoàn lại đơn', 'status' => 'active']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho hoàn lại đơn', 'status' => true]);
        $product = Product::query()->create([
            'user_id' => $shipper->id,
            'name' => 'Sản phẩm hoàn lại',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Tiêu chuẩn',
            'sku' => 'ROLLBACK-ACCEPTED-ORDER',
            'kg' => 1,
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 2,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'ORDER-ROLLBACK-ACCEPT',
            'status' => Order::STATUS_READY_TO_SHIP,
            'skip_auto_cancel' => true,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 50000,
            'total' => 100000,
            'is_priced_by_kg' => false,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'reserved_at' => now(),
        ]);

        // A confirmed, ordinary order remains receivable after its packing day.
        $order->forceFill([
            'skip_auto_cancel' => false,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();
        $order->histories()->create([
            'action' => 'schedule_confirmed', 'user_id' => $shipper->id,
            'role' => 'shipper', 'status_after' => Order::STATUS_READY_TO_SHIP,
        ]);
        $this->actingAs($shipper)->get(route('shipper.available', ['date' => $order->created_at->toDateString()]))
            ->assertOk()->assertSee('Nhận đơn này')->assertDontSee('Chỉ nhận đơn có ngày hôm nay');

        $this->actingAs($shipper)->postJson(route('shipper.accept', $order))->assertOk();
        $exportDocument = InventoryDocument::query()
            ->where('notes', 'Xuất kho cho đơn #'.$order->code)
            ->sole();
        $this->assertSame(3, (int) $inventory->fresh()->quantity);

        $this->actingAs($shipper)
            ->post(route('shipper.accept.rollback', $order))
            ->assertRedirect(route('shipper.available', ['date' => $order->created_at->toDateString()]));

        $order->refresh();
        $this->assertSame(Order::STATUS_READY_TO_SHIP, $order->status);
        $this->assertSame($shipper->id, (int) $order->shipper_id);
        $this->assertSame(5, (int) $inventory->fresh()->quantity);
        $this->assertDatabaseMissing('inventory_documents', ['id' => $exportDocument->id]);
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => InventoryDocument::class,
            'reference_id' => $exportDocument->id,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'shipper_acceptance_rolled_back',
            'status_after' => Order::STATUS_READY_TO_SHIP,
        ]);
    }

    public function test_shipper_can_accept_imported_order_with_non_stock_fee_item(): void
    {
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $customer = Customer::query()->create([
            'name' => 'Khách có phí giao hàng',
            'status' => 'active',
        ]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho nhận đơn',
            'status' => true,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'ORDER-WITH-NON-STOCK-FEE',
            'status' => Order::STATUS_READY_TO_SHIP,
            'skip_auto_cancel' => true,
        ]);
        $order->items()->create([
            'imported_name' => 'Phí ship',
            'product_id' => null,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 30000,
            'total' => 30000,
            'is_priced_by_kg' => false,
        ]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.accept', $order))
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_DELIVERING);

        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
        $document = InventoryDocument::query()->where('notes', 'Xuất kho cho đơn #'.$order->code)->sole();
        $this->assertCount(0, $document->items);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'shipper_accepted',
        ]);
    }

    public function test_inventory_problem_is_returned_as_a_business_error_instead_of_server_error(): void
    {
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $customer = Customer::query()->create([
            'name' => 'Khách thiếu tồn kho',
            'status' => 'active',
        ]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho đang thiếu hàng',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'user_id' => $shipper->id,
            'name' => 'Sản phẩm chưa có tồn',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Tiêu chuẩn',
            'sku' => 'NO-STOCK-FOR-ACCEPT',
            'kg' => 1,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'ORDER-WITHOUT-STOCK',
            'status' => Order::STATUS_READY_TO_SHIP,
            'skip_auto_cancel' => true,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 50000,
            'total' => 50000,
            'is_priced_by_kg' => false,
        ]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.accept', $order))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Không đủ tồn kho khả dụng để xuất cho đơn #'.$order->code);

        $this->assertSame(Order::STATUS_READY_TO_SHIP, $order->fresh()->status);
        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $order->id,
            'action' => 'shipper_accepted',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('historicalPickupCases')]
    public function test_packed_historical_exception_is_accepted_without_rechecking_current_stock(bool $restored): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $customer = Customer::query()->create([
            'name' => 'Khách đơn ngoại lệ ngày trước',
            'status' => 'active',
        ]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho đơn ngoại lệ',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'user_id' => $shipper->id,
            'name' => 'Sản phẩm ngoại lệ',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Tiêu chuẩn',
            'sku' => 'PAST-DATE-ACCEPT',
            'kg' => 1,
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 0,
            'reserved_quantity' => 1,
        ]);

        // Current stock is zero after a movement on the 24th. The order was
        // already packed against the 23rd snapshot, so acceptance must not be
        // blocked by this later movement or deduct current stock a second time.
        $laterDocument = InventoryDocument::query()->create([
            'type' => 'export',
            'document_date' => '2026-08-24',
            'warehouse_id' => $warehouse->id,
            'user_id' => $shipper->id,
        ]);
        InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'quantity' => -1,
            'type' => 'export',
            'reference_id' => $laterDocument->id,
            'reference_type' => InventoryDocument::class,
            'user_id' => $shipper->id,
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'PAST-DATE-EXCEPTION',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => '2026-08-23',
            'skip_auto_cancel' => $restored,
        ]);
        $order->forceFill(['created_at' => '2026-08-23 09:00:00'])->saveQuietly();
        if (! $restored) {
            \App\Models\WarehouseTransfer::create([
                'order_id' => $order->id,
                'source_warehouse_id' => Warehouse::factory()->create()->id,
                'target_warehouse_id' => $warehouse->id,
                'shipper_id' => $shipper->id,
                'status' => \App\Models\WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                'received_at' => '2026-08-23 16:00:00',
            ]);
            $order->histories()->create([
                'action' => 'schedule_confirmed', 'user_id' => $shipper->id,
                'role' => 'shipper', 'status_after' => Order::STATUS_READY_TO_SHIP,
            ]);
        }
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 50000,
            'total' => 50000,
            'is_priced_by_kg' => false,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventory->id,
            'quantity' => 1,
            'reserved_at' => '2026-08-23 09:00:00',
        ]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.accept', $order))
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_DELIVERING);

        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
        $this->assertSame(0, (int) $inventory->fresh()->quantity);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', ['order_item_id' => $item->id]);
        $this->assertDatabaseMissing('inventory_documents', [
            'notes' => 'Xuất kho cho đơn #'.$order->code,
        ]);
        $this->postJson(route('shipper.accept', $order))->assertStatus(409);
    }

    public static function historicalPickupCases(): array
    {
        return ['restored order' => [true], 'received transfer without exception flag' => [false]];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
