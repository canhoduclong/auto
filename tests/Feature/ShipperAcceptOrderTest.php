<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAcceptOrderTest extends TestCase
{
    use RefreshDatabase;

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
}
