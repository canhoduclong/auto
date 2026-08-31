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

    public function test_packed_historical_exception_is_accepted_without_rechecking_current_stock(): void
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
            'skip_auto_cancel' => true,
        ]);
        $order->forceFill(['created_at' => '2026-08-23 09:00:00'])->saveQuietly();
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
    }
}
