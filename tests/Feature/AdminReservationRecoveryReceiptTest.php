<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReservationRecoveryReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_page_presents_customer_sale_shipper_and_order_status(): void
    {
        [$admin] = $this->fixture();

        $this->actingAs($admin)->get(route('inventory-reservations.index'))
            ->assertOk()
            ->assertSee('Đơn hàng của khách hàng')
            ->assertSee('Khách reservation')
            ->assertSee('RECOVERY-ORDER')
            ->assertSee('Sale Nguyễn')
            ->assertSee('Ship Minh')
            ->assertSee('Chờ lấy hàng')
            ->assertSee('Sản phẩm giữ chỗ');
    }

    public function test_admin_can_create_import_receipt_without_releasing_reservation(): void
    {
        [$admin, $inventory, $reservation] = $this->fixture();

        $response = $this->actingAs($admin)->post(
            route('inventory-reservations.recovery-receipt.store', $reservation),
            ['quantity' => 3, 'reason' => 'Kiểm kê nhầm hàng đã đóng']
        );

        $document = \App\Models\InventoryDocument::query()->firstOrFail();
        $response->assertRedirect(route('inventory-documents.show', $document));
        $this->assertSame(8.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(4.0, (float) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseHas('inventory_reservations', ['id' => $reservation->id, 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_documents', [
            'id' => $document->id,
            'type' => 'import',
            'inventory_reservation_id' => $reservation->id,
            'reservation_recovery_quantity' => 3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'type' => 'reservation_recovery_import',
        ]);
    }

    public function test_recovery_cannot_exceed_reservation_or_be_created_by_warehouse_user(): void
    {
        [$admin, $inventory, $reservation, $warehouseUser] = $this->fixture();

        $this->actingAs($admin)->post(route('inventory-reservations.recovery-receipt.store', $reservation), [
            'quantity' => 5, 'reason' => 'Quá số giữ chỗ',
        ])->assertSessionHasErrors('quantity');
        $this->actingAs($warehouseUser)->post(route('inventory-reservations.recovery-receipt.store', $reservation), [
            'quantity' => 1, 'reason' => 'Không đủ quyền',
        ])->assertRedirect(route('home'))
            ->assertSessionHas('error');

        $this->assertSame(5.0, (float) $inventory->fresh()->quantity);
        $this->assertDatabaseCount('inventory_documents', 0);
    }

    private function fixture(): array
    {
        $warehouse = Warehouse::factory()->create();
        $admin = User::factory()->create(['name' => 'Sale Nguyễn']);
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $shipper = User::factory()->create(['name' => 'Ship Minh']);
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach(Role::create(['name' => 'warehouse']));
        $product = Product::factory()->create();
        $variant = $product->variants()->create(['name' => 'Cứu hộ', 'sku' => 'RECOVERY-RES']);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 4,
        ]);
        $customer = Customer::query()->create(['user_id' => $admin->id, 'name' => 'Khách reservation', 'status' => 'active']);
        $order = Order::query()->create([
            'customer_id' => $customer->id, 'user_id' => $admin->id, 'shipper_id' => $shipper->id, 'warehouse_id' => $warehouse->id,
            'code' => 'RECOVERY-ORDER', 'status' => Order::STATUS_READY_TO_SHIP, 'total' => 0,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_variant_id' => $variant->id,
            'quantity' => 4, 'price' => 0, 'total' => 0, 'is_priced_by_kg' => false,
        ]);
        $reservation = InventoryReservation::query()->create([
            'order_item_id' => $item->id, 'inventory_id' => $inventory->id,
            'quantity' => 4, 'reserved_at' => now(),
        ]);

        return [$admin, $inventory, $reservation, $warehouseUser];
    }
}
