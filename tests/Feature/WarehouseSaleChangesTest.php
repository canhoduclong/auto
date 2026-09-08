<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseSaleChangeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseSaleChangesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_changes_are_visible_and_acknowledgement_does_not_hide_newer_changes(): void
    {
        Carbon::setTestNow('2026-09-08 12:00:00');
        $warehouse = Warehouse::create(['name' => 'Kho nhận thay đổi', 'status' => true]);
        $operator = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $operator->roles()->attach(Role::create(['name' => 'warehouse']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $customer = Customer::create(['name' => 'Khách thay đổi', 'status' => 'active']);
        $order = Order::create([
            'user_id' => $sale->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'status' => Order::STATUS_PACKING, 'note' => 'Đóng 2 túi', 'daily_sequence' => 3,
        ]);
        $this->actingAs($sale);
        $order->update(['note' => 'Đóng 4 túi']);
        $firstId = $order->histories()->where('action', WarehouseSaleChangeService::CHANGED)->sole()->id;
        $this->actingAs($operator)->get(route('warehouse.orders'))
            ->assertOk()->assertSee('Thay đổi từ sale — chờ xác nhận')->assertSee('Đóng 2 túi → Đóng 4 túi')
            ->assertSee('Xác Nhận')->assertSee('sale-change-pending', false);

        // A second edit arrives after the operator loaded the first edit.
        request()->setRouteResolver(fn () => null);
        $this->actingAs($sale);
        $order->update(['note' => 'Đóng 6 túi']);
        $latestId = $order->histories()->where('action', WarehouseSaleChangeService::CHANGED)->max('id');
        $this->actingAs($operator)->post(route('warehouse.orders.confirm-sale-changes', $order), ['through_id' => $firstId])
            ->assertRedirect()->assertSessionHas('success');
        $this->get(route('warehouse.orders'))->assertOk()->assertViewHas('orders', function ($orders) use ($order) {
            $entry = $orders->firstWhere('id', $order->id);
            return $entry->sale_changes_pending->count() === 1 && ! $entry->sale_changes_confirmed;
        });
        $this->post(route('warehouse.orders.confirm-sale-changes', $order), ['through_id' => $latestId])->assertRedirect();
        $this->get(route('warehouse.orders'))->assertOk()->assertSee('Đã xác nhận thay đổi từ sale')
            ->assertViewHas('orders', fn ($orders) => $orders->firstWhere('id', $order->id)->sale_changes_confirmed);
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);
        $this->post(route('warehouse.orders.confirm-sale-changes', $order), ['through_id' => $latestId])->assertRedirect();
        $this->assertSame(2, $order->histories()->where('action', WarehouseSaleChangeService::CONFIRMED)->count());

        request()->setRouteResolver(fn () => null);
        $this->actingAs($sale);
        $order->update(['shipping_fee' => 12000]);
        $this->actingAs($operator)->get(route('warehouse.orders'))->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->firstWhere('id', $order->id)->sale_changes_pending->count() === 1);

        $operator->update(['warehouse_id' => Warehouse::create(['name' => 'Kho khác', 'status' => true])->id]);
        $this->actingAs($operator)->postJson(route('warehouse.orders.confirm-sale-changes', $order), ['through_id' => $latestId])->assertForbidden();
    }

    public function test_edits_before_packing_and_warehouse_edits_do_not_create_sale_alerts(): void
    {
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $customer = Customer::create(['name' => 'Khách', 'status' => 'active']);
        $order = Order::create(['user_id' => $sale->id, 'customer_id' => $customer->id, 'status' => Order::STATUS_READY_TO_PACK]);
        $this->actingAs($sale);
        $order->update(['note' => 'Chưa đóng hàng']);
        $this->assertSame(0, $order->histories()->where('action', WarehouseSaleChangeService::CHANGED)->count());
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::create(['name' => 'warehouse']));
        $this->actingAs($operator);
        $order->update(['status' => Order::STATUS_PACKING, 'note' => 'Kho sửa']);
        $this->assertSame(0, $order->histories()->where('action', WarehouseSaleChangeService::CHANGED)->count());
    }
    public function test_sale_item_add_edit_and_removal_are_recorded_after_packing(): void
    {
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $customer = Customer::create(['name' => 'Khách sửa hàng', 'status' => 'active']);
        $product = \App\Models\Product::factory()->create();
        $variant = $product->variants()->create(['name' => 'Size 2.5', 'sku' => 'SALE-CHANGE-25']);
        $order = Order::create(['user_id' => $sale->id, 'customer_id' => $customer->id, 'status' => Order::STATUS_PACKING]);
        $this->actingAs($sale);
        $item = $order->items()->create(['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 10, 'price' => 10000, 'total' => 100000]);
        $item->update(['quantity' => 15]);
        $item->delete();
        $changes = $order->histories()->where('action', WarehouseSaleChangeService::CHANGED)->orderBy('id')->get();
        $this->assertCount(3, $changes);
        $this->assertStringContainsString('Thêm hàng', $changes[0]->note);
        $this->assertStringContainsString('Số lượng: 10 → 15', $changes[1]->note);
        $this->assertStringContainsString('Bỏ hàng', $changes[2]->note);
    }

}
