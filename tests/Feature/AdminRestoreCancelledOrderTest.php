<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
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

    public function test_restore_is_rolled_back_when_stock_is_no_longer_available(): void
    {
        [$admin, $order, $inventory] = $this->createCancelledOrder(5, 10);

        $this->actingAs($admin)
            ->post(route('site.orders.restore-cancelled', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertFalse($order->fresh()->skip_auto_cancel);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
        ]);
        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $order->id,
            'action' => 'restore_cancelled_order',
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
}
