<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Http\Controllers\OrderController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRestoreCancelledOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_restore_cancelled_order_with_previous_status_and_inventory_booking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 20:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 10);

        $this->actingAs($admin)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('packing', $order->status);
        $this->assertTrue($order->skip_auto_cancel);
        $this->assertNull($order->cancelled_by);
        $this->assertNull($order->cancelled_at);
        $this->assertNull($order->cancel_reason);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $inventory->id,
            'quantity' => 10,
        ]);
        $this->assertSame(10, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'restore_cancelled_order',
            'status_before' => Order::STATUS_CANCELLED,
            'status_after' => 'packing',
            'user_id' => $admin->id,
        ]);

        // The restored overdue order is an explicit admin exception and must not be cancelled again.
        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();
        $this->assertSame('packing', $order->fresh()->status);
    }

    public function test_admin_orders_page_can_restore_and_mark_cancelled_order_as_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 4);

        $this->actingAs($admin)
            ->get(route('orders.index', ['status' => Order::STATUS_CANCELLED]))
            ->assertOk()
            ->assertSee(route('orders.restore-cancelled', $order), false)
            ->assertSee('Phục hồi &amp; đánh dấu ngoại lệ', false);

        $this->actingAs($admin)
            ->post(route('orders.restore-cancelled', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('packing', $order->status);
        $this->assertTrue($order->skip_auto_cancel);
        $this->assertNull($order->cancelled_at);
        $this->assertSame(4, (int) $inventory->fresh()->reserved_quantity);

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Đơn ngoại lệ');
    }

    public function test_non_admin_cannot_restore_cancelled_order(): void
    {
        [, $order] = $this->createCancelledOrder(10, 10);
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));

        $this->actingAs($sale)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_admin_can_restore_when_available_stock_is_insufficient(): void
    {
        [$admin, $order, $inventory] = $this->createCancelledOrder(5, 10);

        $this->actingAs($admin)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('packing', $order->status);
        $this->assertTrue($order->skip_auto_cancel);
        $this->assertFalse($order->stock_sufficient);
        $this->assertSame('waiting_stock', $order->stock_alert_status);
        $this->assertSame(5, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $inventory->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'restore_cancelled_order',
        ]);

        // After stock-in, the missing reservation is rebuilt before packing/shipping completes.
        $inventory->update(['quantity' => 10]);
        app(OrderController::class)->rebuildRestoredOrderStockReservation($order, null);
        $this->assertSame(10, (int) $inventory->fresh()->reserved_quantity);
        $this->assertSame(10, (int) InventoryReservation::query()
            ->where('order_item_id', $order->items()->value('id'))
            ->sum('quantity'));
    }

    public function test_admin_can_restore_all_cancelled_orders_for_selected_monitoring_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 20:00:00', 'Asia/Bangkok'));
        [$admin, $firstOrder, $inventory] = $this->createCancelledOrder(20, 4);
        $firstOrder->update(['daily_sequence' => 7]);
        $secondOrder = $this->duplicateCancelledOrder($firstOrder, '2026-08-22 14:00:00');
        $secondOrder->update(['daily_sequence' => 3]);
        $otherDayOrder = $this->duplicateCancelledOrder($firstOrder, '2026-08-21 14:00:00');

        $this->actingAs($admin)
            ->post(route('pages.my_orders.monitoring.restore_all'), [
                'date' => '2026-08-22',
                'date_field' => 'created_at',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã phục hồi 2/2 đơn đã hủy trong ngày 2026-08-22.');

        $this->assertSame('packing', $firstOrder->fresh()->status);
        $this->assertSame('packing', $secondOrder->fresh()->status);
        $this->assertSame(7, (int) $firstOrder->fresh()->daily_sequence);
        $this->assertSame(3, (int) $secondOrder->fresh()->daily_sequence);
        $this->assertSame(Order::STATUS_CANCELLED, $otherDayOrder->fresh()->status);
        $this->assertSame(8, (int) $inventory->fresh()->reserved_quantity);
        $this->assertSame(2, OrderHistory::query()
            ->whereIn('order_id', [$firstOrder->id, $secondOrder->id])
            ->where('action', 'restore_cancelled_order')
            ->count());
    }

    public function test_monitoring_places_restore_all_button_immediately_after_filter_for_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 20:00:00', 'Asia/Bangkok'));
        [$admin] = $this->createCancelledOrder(10, 4);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => '2026-08-22',
                'date_field' => 'created_at',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Lọc', 'Phục hồi tất cả'])
            ->assertSee(route('pages.my_orders.monitoring.restore_all'), false);
    }

    public function test_admin_can_cancel_an_eligible_order_from_a_past_monitoring_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 4);

        $this->actingAs($admin)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertSessionHas('success');

        $order->forceFill(['created_at' => Carbon::parse('2026-08-23 09:00:00', 'Asia/Bangkok')])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'view' => 'cards',
                'date' => '2026-08-23',
                'date_field' => 'business_date',
            ]))
            ->assertOk()
            ->assertSee(route('site.orders.cancel', $order), false)
            ->assertSee('Hủy đơn hàng');

        $this->actingAs($admin)
            ->post(route('site.orders.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame($admin->id, $order->cancelled_by);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'cancel_order',
            'status_before' => 'packing',
            'status_after' => Order::STATUS_CANCELLED,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_cancel_any_eligible_order_from_orders_page_for_a_past_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 4);

        $this->actingAs($admin)
            ->post(route('orders.restore-cancelled', $order))
            ->assertSessionHas('success');

        $sale = User::factory()->create();
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));
        $order->forceFill([
            'user_id' => $sale->id,
            'created_at' => Carbon::parse('2026-08-23 09:00:00', 'Asia/Bangkok'),
        ])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('orders.index', [
                'from_date' => '2026-08-23',
                'to_date' => '2026-08-23',
            ]))
            ->assertOk()
            ->assertSee(route('orders.cancel', $order), false)
            ->assertSee('Hủy đơn hàng');

        $this->actingAs($sale)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('home'));
        $this->assertSame('packing', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('orders.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame($admin->id, $order->cancelled_by);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
        ]);
    }

    public function test_restored_exception_order_can_be_packed_delivered_and_recognized_as_revenue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 10:00:00', 'Asia/Bangkok'));
        [$admin, $order] = $this->createCancelledOrder(10, 10);
        $order->forceFill([
            'code' => 'RESTORED-EXCEPTION-FLOW',
            'created_at' => now()->subDay(),
        ])->saveQuietly();

        $this->actingAs($admin)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertTrue($order->skip_auto_cancel);
        $this->assertSame(Order::STATUS_PACKING, $order->status);

        $this->actingAs($admin)
            ->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee('order-card-'.$order->id, false);

        $this->actingAs($admin)
            ->get(route('warehouse.orders', ['date' => $order->created_at->toDateString()]))
            ->assertOk()
            ->assertSee('order-card-'.$order->id, false)
            ->assertSee(route('warehouse.orders.complete-packing', $order), false);

        $order->update(['actual_weight' => 25]);
        $this->actingAs($admin)
            ->post(route('warehouse.orders.complete-packing', $order))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(Order::STATUS_READY_TO_SHIP, $order->fresh()->status);

        $shipper = User::factory()->create(['name' => 'Shipper đơn ngoại lệ']);
        $shipper->roles()->attach(Role::query()->create(['name' => 'shipper']));
        $order->update(['shipper_id' => $shipper->id]);
        // The manager has sent the restored exception route, but the shipper
        // has not confirmed it again. Explicit exception orders must still be
        // receivable so their operational workflow can continue.
        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'schedule_created',
            'user_id' => $admin->id,
            'role' => 'manager_shipper',
            'status_before' => Order::STATUS_READY_TO_SHIP,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Gửi lịch giao cho đơn ngoại lệ',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.available', ['date' => $order->created_at->toDateString()]))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Nhận đơn này');

        $this->actingAs($shipper)
            ->post(route('shipper.accept', $order))
            ->assertRedirect(route('shipper.my-orders'));
        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);

        $this->actingAs($shipper)
            ->post(route('shipper.mark-delivered', $order), [
                'collected_amount' => 500000,
                'has_partial_return' => 0,
            ])
            ->assertRedirect(route('shipper.my-orders'))
            ->assertSessionHas('success');
        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);

        $this->actingAs($admin)
            ->postJson(route('accounting.reconciliation.confirm', $order), [
                'note' => 'Xác nhận doanh thu đơn khôi phục ngoại lệ',
            ])
            ->assertOk()
            ->assertJsonPath('reconciliation.status', 'confirmed');

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertDatabaseHas('accounting_reconciliations', [
            'order_id' => $order->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('accounting_sales_entries', [
            'order_id' => $order->id,
        ]);
    }

    /**
     * @return array{User, Order, Inventory}
     */
    private function createCancelledOrder(int $stockQuantity, int $orderQuantity): array
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->create(['name' => 'admin']));
        $customer = Customer::query()->create([
            'name' => 'Khách phục hồi đơn',
            'status' => 'active',
        ]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho phục hồi đơn',
            'status' => true,
        ]);
        $product = Product::query()->create([
            'user_id' => $admin->id,
            'name' => 'Sản phẩm phục hồi',
            'unit' => 'con',
            'status' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '2.5 kg',
            'sku' => 'RESTORE-' . $stockQuantity . '-' . $orderQuantity,
            'kg' => 2.5,
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => $stockQuantity,
            'reserved_quantity' => 0,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_CANCELLED,
            'delivery_date' => '2026-08-22',
            'delivery_time' => '8h',
            'cancelled_by' => $admin->id,
            'cancelled_at' => now(),
            'cancel_reason' => 'Tự động hủy do quá hạn',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $orderQuantity,
            'unit_weight' => 2.5,
            'total_weight' => $orderQuantity * 2.5,
            'price' => 50000,
            'total' => $orderQuantity * 50000,
        ]);
        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'auto_cancel_overdue',
            'role' => 'system',
            'status_before' => 'packing',
            'status_after' => Order::STATUS_CANCELLED,
            'note' => 'Hủy tự động',
        ]);

        return [$admin, $order, $inventory];
    }

    private function duplicateCancelledOrder(Order $source, string $createdAt): Order
    {
        $order = $source->replicate();
        $order->created_at = Carbon::parse($createdAt, 'Asia/Bangkok');
        $order->updated_at = Carbon::parse($createdAt, 'Asia/Bangkok');
        $order->save();

        foreach ($source->items as $item) {
            $copy = $item->replicate();
            $copy->order_id = $order->id;
            $copy->save();
        }

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'auto_cancel_overdue',
            'role' => 'system',
            'status_before' => 'packing',
            'status_after' => Order::STATUS_CANCELLED,
            'note' => 'Hủy tự động',
        ]);

        return $order;
    }
}
