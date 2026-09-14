<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTransferUndoReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_warehouse_can_undo_an_incorrect_transfer_receipt(): void
    {
        $role = Role::create(['name' => 'warehouse']);
        $source = Warehouse::factory()->create(['name' => 'Kho nguồn']);
        $target = Warehouse::factory()->create(['name' => 'Kho nhận']);
        $warehouseUser = User::factory()->create(['warehouse_id' => $target->id]);
        $warehouseUser->roles()->attach($role);
        $customer = Customer::create(['name' => 'Khách nhận nhầm', 'status' => 'active']);
        $variant = ProductVariant::factory()->create(['stock' => 5]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $target->id,
            'code' => 'UNDO-RECEIPT-001',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->toDateString(),
            'total' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'price' => 10000,
            'total' => 50000,
        ]);
        $targetInventory = Inventory::create([
            'warehouse_id' => $target->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 5,
        ]);
        $sourceInventory = Inventory::create([
            'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
        $document = InventoryDocument::create([
            'type' => 'import',
            'document_date' => now()->toDateString(),
            'warehouse_id' => $target->id,
            'notes' => 'Nhập kho điều chuyển nhận nhầm',
            'shipping_fee' => 0,
            'user_id' => $warehouseUser->id,
        ]);
        $document->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_cost' => 10000,
        ]);
        InventoryMovement::create([
            'inventory_id' => $targetInventory->id,
            'quantity' => 5,
            'type' => 'import',
            'reference_id' => $document->id,
            'reference_type' => InventoryDocument::class,
            'user_id' => $warehouseUser->id,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $warehouseUser->id,
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            'import_document_id' => $document->id,
            'received_by' => $warehouseUser->id,
            'received_at' => now(),
            'received_weights' => [['order_item_id' => $order->items()->value('id'), 'received_weight' => 10]],
            'received_total_weight' => 10,
            'weight_loss' => 0,
        ]);
        InventoryReservation::create([
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $targetInventory->id,
            'quantity' => 5,
            'reserved_at' => now(),
        ]);
        InventoryMovement::create([
            'inventory_id' => $sourceInventory->id,
            'quantity' => -5,
            'type' => 'transfer_out',
            'reference_id' => $transfer->id,
            'reference_type' => WarehouseTransfer::class,
            'user_id' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.transfers.incoming', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Gỡ tiếp nhận')
            ->assertSee(route('warehouse.transfers.undo-receipt', $transfer), false);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.transfers.undo-receipt', $transfer), [
                'undo_note' => 'Nhận nhầm đơn',
            ])
            ->assertSessionHas('success');

        $transfer->refresh();
        $this->assertSame(WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE, $transfer->status);
        $this->assertNull($transfer->import_document_id);
        $this->assertNull($transfer->received_at);
        $this->assertSame(0, (int) $targetInventory->fresh()->quantity);
        $this->assertSame(0, (int) $targetInventory->fresh()->reserved_quantity);
        $this->assertSame(5, (int) $sourceInventory->fresh()->quantity);
        $this->assertSame(5, (int) $sourceInventory->fresh()->reserved_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $sourceInventory->id,
            'quantity' => 5,
        ]);
        $this->assertSame((int) $source->id, (int) $order->fresh()->warehouse_id);
        $this->assertDatabaseMissing('inventory_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('inventory_movements', ['reference_id' => $document->id, 'reference_type' => InventoryDocument::class]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'warehouse_transfer_receipt_undone',
            'user_id' => $warehouseUser->id,
        ]);
    }

    public function test_receipt_cannot_be_undone_after_received_stock_was_used(): void
    {
        $role = Role::create(['name' => 'warehouse']);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $target->id]);
        $user->roles()->attach($role);
        $customer = Customer::create(['name' => 'Khách', 'status' => 'active']);
        $variant = ProductVariant::factory()->create();
        $order = Order::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $target->id,
            'code' => 'UNDO-RECEIPT-BLOCKED',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $inventory = Inventory::create([
            'warehouse_id' => $target->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'reserved_quantity' => 0,
        ]);
        $document = InventoryDocument::create([
            'type' => 'import', 'document_date' => now(), 'warehouse_id' => $target->id,
            'shipping_fee' => 0, 'user_id' => $user->id,
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id, 'quantity' => 5, 'type' => 'import',
            'reference_id' => $document->id, 'reference_type' => InventoryDocument::class,
            'user_id' => $user->id,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id, 'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id, 'shipper_id' => $user->id,
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            'import_document_id' => $document->id, 'received_by' => $user->id, 'received_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('warehouse.transfers.undo-receipt', $transfer), ['undo_note' => 'Nhận nhầm'])
            ->assertSessionHas('error', 'Không thể gỡ tiếp nhận: Tồn kho đã được sử dụng, không đủ để gỡ tiếp nhận.');

        $this->assertSame(WarehouseTransfer::STATUS_RECEIVED_COMPLETED, $transfer->fresh()->status);
        $this->assertSame(2, (int) $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_documents', ['id' => $document->id]);
        $this->assertSame(0, OrderHistory::where('action', 'warehouse_transfer_receipt_undone')->count());
    }
}
