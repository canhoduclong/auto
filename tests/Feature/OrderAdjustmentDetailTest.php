<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdjustmentDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_approval_actions_while_pending(): void
    {
        [$user, $adjustment] = $this->fixture('admin');
        $this->actingAs($user);
        foreach (['draft', 'approved', 'rejected', 'completed', 'pending_approval'] as $status) {
            $adjustment->update(['status' => $status]);
            $response = $this->get(route('site.order-adjustments.show', $adjustment))->assertOk();
            if ($status === 'pending_approval') {
                $response->assertSee('Thao tác phê duyệt');
            } else {
                $response->assertDontSee('Thao tác phê duyệt');
                $this->postJson(route('site.order-adjustments.approve', $adjustment))->assertForbidden();
                $this->postJson(route('site.order-adjustments.reject', $adjustment), ['reason' => 'Test'])->assertForbidden();
            }
            if ($status === 'approved') {
                $response->assertSee('Các thay đổi chưa được áp dụng');
            }
        }
    }

    public function test_accounting_approval_displays_full_updated_order_including_unchanged_items(): void
    {
        [$user, $adjustment, $item] = $this->fixture('accountant');
        $adjustment->items()->create([
            'order_item_id' => $item->id, 'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'original_quantity' => 2, 'adjusted_quantity' => 2,
            'original_price' => 10000, 'adjusted_price' => 20000,
            'original_weight' => 2, 'adjusted_weight' => 2,
        ]);
        $adjustment->update(['order_changes' => ['recipient_name' => ['original' => 'Old', 'adjusted' => 'Người nhận đã xác nhận']]]);
        $this->actingAs($user)->post(route('site.order-adjustments.approve', $adjustment))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('completed', $adjustment->fresh()->status);
        $this->get(route('site.order-adjustments.show', $adjustment))->assertOk()
            ->assertSee('Đơn hàng sau xác nhận')
            ->assertSee('Người nhận đã xác nhận')
            ->assertSee('UNCHANGED-SKU')
            ->assertSee('50.000đ')
            ->assertSee('Còn phải thu')
            ->assertDontSee('Thao tác phê duyệt');
    }

    private function fixture(string $role): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::create(['name' => $role]));
        $customer = Customer::create(['user_id' => $user->id, 'name' => 'Test customer', 'status' => 'active']);
        $order = Order::create([
            'user_id' => $user->id, 'customer_id' => $customer->id,
            'code' => 'ADJUSTMENT-DETAIL', 'status' => Order::STATUS_COMPLETED,
            'total' => 30000, 'recipient_name' => 'Old',
        ]);
        $items = collect();
        foreach (['CHANGED-SKU' => 2, 'UNCHANGED-SKU' => 1] as $sku => $qty) {
            $variant = ProductVariant::factory()->create(['sku' => $sku, 'kg' => 1, 'is_priced_by_kg' => false]);
            $items->push($order->items()->create([
                'product_id' => $variant->product_id, 'product_variant_id' => $variant->id,
                'quantity' => $qty, 'price' => 10000, 'total' => $qty * 10000,
                'unit_weight' => 1, 'total_weight' => $qty, 'is_priced_by_kg' => false,
            ]));
        }
        $adjustment = OrderAdjustment::create([
            'order_id' => $order->id, 'requested_by' => $user->id,
            'status' => 'pending_approval', 'warehouse_confirmation_status' => 'not_required',
        ]);

        return [$user, $adjustment, $items->first()];
    }
}
