<?php

namespace Tests\Feature;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
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

    public function test_monitoring_list_uses_sale_short_name_with_full_name_fallback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-23 10:00:00', 'Asia/Bangkok'));
        [$admin, $order] = $this->createCancelledOrder(10, 4);
        $sale = User::factory()->create([
            'name' => 'Nguyễn Văn Tên Đầy Đủ Duy Nhất',
            'short_name' => 'Sale Duy',
        ]);
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));
        $order->update(['user_id' => $sale->id]);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'view' => 'list',
                'date' => '2026-08-23',
                'date_field' => 'business_date',
            ]))
            ->assertOk()
            ->assertSee('Sale Duy')
            ->assertDontSee('Nguyễn Văn Tên Đầy Đủ Duy Nhất');

        $sale->update(['short_name' => null]);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'view' => 'list',
                'date' => '2026-08-23',
                'date_field' => 'business_date',
            ]))
            ->assertOk()
            ->assertSee('Nguyễn Văn Tên Đầy Đủ Duy Nhất');
    }

    public function test_monitoring_displays_measurement_from_the_current_order_stage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order] = $this->createCancelledOrder(10, 4);
        $order->forceFill([
            'status' => Order::STATUS_READY_TO_SHIP,
            'created_at' => Carbon::parse('2026-08-23 09:00:00', 'Asia/Bangkok'),
        ])->saveQuietly();
        $order->items()->firstOrFail()->update([
            'packed_weight' => 9.75,
            'actual_weight' => 8.4,
        ]);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'date' => '2026-08-23',
                'view' => 'cards',
            ]))
            ->assertOk()
            ->assertSee('9,75 kg')
            ->assertDontSee('Kho cân');

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'date' => '2026-08-23',
                'view' => 'list',
            ]))
            ->assertOk()
            ->assertSee('8,4 kg')
            ->assertDontSee('Thực giao / khách cân');
    }

    public function test_inline_adjustment_form_uses_a_stable_submit_url_and_aligned_fee_layout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order] = $this->createCancelledOrder(10, 4);
        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'delivered_at' => now(),
        ]);
        AccountingReconciliation::query()->create([
            'order_id' => $order->id,
            'sale_id' => $admin->id,
            'total_amount' => 200000,
            'recognized_revenue' => 200000,
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('site.order-adjustments.create', $order))
            ->assertOk()
            ->assertJsonPath('success', true);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('action="'.route('site.order-adjustments.store', $order).'"', $html);
        $this->assertStringContainsString('<input type="hidden" name="action" value="submit">', $html);
        $this->assertStringContainsString('data-adjustment-action="submit"', $html);
        $this->assertStringContainsString('monitor-adjustment-fee-list', $html);
        $this->assertStringNotContainsString('type="submit" class="btn btn-warning fw-bold" name="action"', $html);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'date' => '2026-08-24',
                'view' => 'cards',
            ]))
            ->assertOk()
            ->assertSee("form.getAttribute('action')", false)
            ->assertSee("body.set('action', button.dataset.adjustmentAction || 'submit')", false)
            ->assertSee('fetch(submitUrl', false);
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

    public function test_sale_can_cancel_and_trash_own_old_order_before_shipper_pickup(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 4);
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));

        $this->actingAs($admin)
            ->post(route('orders.restore-cancelled', $order))
            ->assertSessionHas('success');

        $order->forceFill([
            'user_id' => $sale->id,
            'status' => Order::STATUS_READY_TO_SHIP,
            'created_at' => Carbon::parse('2026-08-23 09:00:00', 'Asia/Bangkok'),
        ])->saveQuietly();

        $this->actingAs($sale)
            ->get(route('pages.my_orders.monitoring', [
                'date' => '2026-08-23',
                'highlight' => $order->id,
            ]))
            ->assertOk()
            ->assertSee(route('site.orders.cancel', $order), false)
            ->assertSee('Hủy đơn hàng');

        $this->actingAs($sale)
            ->post(route('site.orders.cancel', $order), [
                'cancel_reason' => 'Hủy đơn cũ chưa giao',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);

        $this->actingAs($sale)
            ->get(route('pages.my_orders.monitoring', [
                'date' => '2026-08-23',
                'highlight' => $order->id,
            ]))
            ->assertOk()
            ->assertSee(route('site.orders.trash', $order), false)
            ->assertSee('Xóa đơn');

        $this->actingAs($sale)
            ->post(route('site.orders.trash', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($order->fresh()->trash_at);
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

    public function test_admin_can_cancel_and_see_safe_delete_action_for_ready_to_pack_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(10, 4);

        $this->actingAs($admin)
            ->post(route('orders.restore-cancelled', $order))
            ->assertSessionHas('success');

        $order->update(['status' => Order::STATUS_READY_TO_PACK]);

        $this->actingAs($admin)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(route('orders.cancel', $order), false)
            ->assertSee(route('orders.admin-delete', $order), false)
            ->assertSee('Xóa &amp; loại doanh số', false);

        $this->actingAs($admin)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Admin hủy đơn chưa đóng hàng',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'cancel_order',
            'status_before' => Order::STATUS_READY_TO_PACK,
            'status_after' => Order::STATUS_CANCELLED,
            'user_id' => $admin->id,
        ]);
    }

    public function test_owner_can_resend_cancelled_order_as_a_new_order_from_monitoring(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $cancelledOrder] = $this->createCancelledOrder(10, 4);
        $cancelledOrder->update([
            'user_id' => $admin->id,
            'code' => 'CANCELLED-TO-RESEND',
        ]);

        $this->actingAs($admin)
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => '2026-08-24',
                'date_field' => 'business_date',
            ]))
            ->assertOk()
            ->assertSee(route('site.orders.resend', $cancelledOrder), false)
            ->assertSee('Gửi lại đơn');

        $this->actingAs($admin)
            ->post(route('site.orders.resend', $cancelledOrder))
            ->assertRedirect();

        $newOrder = Order::query()->where('id', '!=', $cancelledOrder->id)->latest('id')->firstOrFail();
        $this->assertSame(Order::STATUS_CANCELLED, $cancelledOrder->fresh()->status);
        $this->assertNotSame(Order::STATUS_CANCELLED, $newOrder->status);
        $this->assertSame($admin->id, $newOrder->user_id);
        $this->assertNull($newOrder->copied_from_order_id);
        $this->assertFalse($newOrder->skip_auto_cancel);
        $this->assertSame(1, $newOrder->items()->count());
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $newOrder->id,
            'action' => 'resend_cancelled_order',
            'status_before' => Order::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $cancelledOrder->id,
            'action' => 'resend_order_created',
            'status_after' => Order::STATUS_CANCELLED,
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
            ->assertRedirect(route('shipper.available', ['date' => $order->created_at->toDateString()]))
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

    public function test_past_day_packing_uses_that_days_inventory_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 10:00:00', 'Asia/Bangkok'));
        [$admin, $order, $inventory] = $this->createCancelledOrder(4, 4);

        $order->forceFill([
            'created_at' => Carbon::parse('2026-08-23 09:00:00', 'Asia/Bangkok'),
            'delivery_date' => '2026-08-23',
        ])->saveQuietly();

        $backdatedStockIn = InventoryDocument::query()->create([
            'type' => 'import',
            'warehouse_id' => $inventory->warehouse_id,
            'document_date' => '2026-08-23',
            'user_id' => $admin->id,
        ]);
        InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'quantity' => 4,
            'type' => 'import',
            'reference_id' => $backdatedStockIn->id,
            'reference_type' => InventoryDocument::class,
            'user_id' => $admin->id,
            'created_at' => Carbon::parse('2026-08-24 08:00:00', 'Asia/Bangkok'),
            'updated_at' => Carbon::parse('2026-08-24 08:00:00', 'Asia/Bangkok'),
        ]);

        $this->actingAs($admin)
            ->post(route('orders.restore-cancelled', $order))
            ->assertSessionHas('success');
        $order->update(['status' => Order::STATUS_READY_TO_PACK]);

        $this->actingAs($admin)
            ->get(route('warehouse.orders', ['date' => '2026-08-23']))
            ->assertOk()
            ->assertSee('Đóng hàng ngày 23/08')
            ->assertSee('Hoàn thành đóng gói ngày 23/08')
            ->assertSee('name="packing_date" value="2026-08-23"', false);

        $this->actingAs($admin)
            ->post(route('warehouse.orders.start-packing', $order), [
                'packing_date' => '2026-08-23',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'start_packing',
            'status_before' => Order::STATUS_READY_TO_PACK,
            'status_after' => Order::STATUS_PACKING,
            'note' => 'Bắt đầu đóng gói đơn hàng cho ngày 23/08/2026',
        ]);

        $order->items()->update(['actual_weight' => 10]);

        $this->actingAs($admin)
            ->post(route('warehouse.orders.complete-packing', $order), [
                'packing_date' => '2026-08-22',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Đơn hàng không thuộc ngày đóng hàng đã chọn.');
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('warehouse.orders.complete-packing', $order), [
                'packing_date' => '2026-08-23',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_READY_TO_SHIP, $order->fresh()->status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'complete_packing',
            'status_before' => Order::STATUS_PACKING,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Hoàn thành đóng gói ngày 23/08/2026 – Sẵn sàng giao hàng',
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
