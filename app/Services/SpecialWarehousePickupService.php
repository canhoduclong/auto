<?php
namespace App\Services;

use App\Http\Controllers\OrderController;
use App\Models\{Customer, Inventory, InventoryDocument, InventoryMovement, Order, OrderHistory, Product, ProductVariant, User, Warehouse};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpecialWarehousePickupService
{
    private function fail(string $message): never { throw ValidationException::withMessages(['special_pickup' => $message]); }

    public function eligible(Customer $customer, array $variantIds): bool
    {
        $ids = array_values(array_unique(array_map('intval', $variantIds)));
        return $customer->special_pickup_enabled && count($ids) > 0
            && ProductVariant::whereIn('id', $ids)->whereHas('product', fn ($q) => $q->where('special_pickup_enabled', true)->where('status', true))->count() === count($ids);
    }

    public function authorizeCustomer(Customer $customer, User $actor): void
    {
        if (!$actor->isAdmin()) {
            if (!$customer->isManagedBy($actor)) abort(403, 'Bạn chỉ được hoàn tất tại kho cho khách đang quản lý.');
        }
    }

    public function warehouses(User $actor)
    {
        return Warehouse::where('status', true)->when($actor->warehouse_id && !$actor->isAdmin(), fn ($q) => $q->whereKey($actor->warehouse_id));
    }

    public function create(array $data, User $actor): Order
    {
        if (empty($data['goods_received'])) $this->fail('Cần xác nhận khách đã nhận hàng tại kho.');
        return DB::transaction(function () use ($data, $actor) {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', json_encode($data));
            $existing = Order::where('creation_token', $data['creation_token'])->where(fn ($q) => $q->where('user_id', $actor->id)->orWhereHas('histories', fn ($q) => $q->where('user_id', $actor->id)->where('action', 'special_pickup_completed')))->first();
            if ($existing) {
                if (!$existing->is_special_pickup || $existing->creation_payload_hash !== $hash) $this->fail('Mã gửi đã dùng cho nội dung khác. Hãy mở lại form.');
                return $existing;
            }
            $customer = Customer::lockForUpdate()->findOrFail($data['customer_id']);
            $this->authorizeCustomer($customer, $actor);
            $ids = array_column($data['items'], 'variant_id');
            // Lock the flags as well as stock: changing either switch cannot race checkout.
            $variants = ProductVariant::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            Product::whereIn('id', $variants->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            if (!$this->eligible($customer, $ids)) $this->fail('Khách hàng và toàn bộ sản phẩm phải bật Hàng đặc biệt.');
            $shipper = User::whereKey($customer->default_shipper_id)->whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) IN (?,?)', ['shipper', 'manager_shipper']))->first();
            if (!$shipper) $this->fail('Chưa cấu hình shipper mặc định hợp lệ cho khách.');
            if (!$this->warehouses($actor)->whereKey($data['warehouse_id'])->exists()) $this->fail('Kho không thuộc phạm vi được phép sử dụng.');
            $items = [];
            foreach ($data['items'] as $line) {
                $variant = $variants->firstWhere('id', $line['variant_id']);
                $minimum = \App\Support\OrderPriceBounds::minimum((float) ($variant->latestPriceRule?->min_price ?? 0));
                if ((float) $line['unit_price'] < $minimum) $this->fail('Giá bán thấp hơn mức giá tối thiểu của sản phẩm.');
                $items[] = ['variant_id' => $line['variant_id'], 'quantity' => $line['quantity'], 'base_price' => $line['unit_price'], 'unit_weight' => round($line['actual_weight'] / $line['quantity'], 6), 'total_weight' => (float) $line['actual_weight']];
            }
            return app(OrderController::class)->createOrderFromSchedule($items, [
                'creation_token' => $data['creation_token'], 'creation_payload_hash' => $hash,
                'customer_id' => $customer->id, 'user_id' => $actor->isAdmin() ? ($customer->current_owner_sale_id ?: $customer->assigned_to ?: $actor->id) : $actor->id, 'actor_user_id' => $actor->id,
                'shipper_id' => $shipper->id, 'recipient_name' => $customer->name, 'recipient_phone' => $customer->phone,
                'recipient_address' => $customer->address, 'delivery_time' => now()->format('H:i'),
                'charge_shipping_fee' => false, 'shipping_fee' => 0, 'collect_customer_shipping_fee' => false,
                'charge_vat' => $data['charge_vat'] ?? false, 'vat_percent' => $data['vat_percent'] ?? 0,
                'note' => $data['note'] ?? 'Khách tự đến kho lấy hàng đặc biệt; chờ kế toán xác nhận doanh thu và cập nhật thanh toán.',
                'use_truck_station' => false, 'allow_backorder' => true,
                'special_pickup' => $data,
            ], app(ApprovalService::class));
        });
    }

    /** Called only while the newly created order and configuration are locked. */
    public function completeCreatedOrder(Order $order, array $data, User $actor): void
    {
        $order->load('items');
        $warehouseId = (int) $data['warehouse_id'];
        $import = null;
        if ($data['stock_source'] === 'new_receipt') {
            $import = InventoryDocument::create(['type' => 'import', 'warehouse_id' => $warehouseId, 'document_date' => today(), 'user_id' => $actor->id, 'notes' => 'Nhập hàng thực tế để bán tại kho #'.$order->code.'. Nguồn: '.$data['receipt_source'], 'shipping_fee' => 0]);
        }
        $export = InventoryDocument::create(['type' => 'export', 'warehouse_id' => $warehouseId, 'document_date' => today(), 'user_id' => $actor->id, 'notes' => 'Xuất kho cho đơn #'.$order->code.' [SPECIAL_PICKUP]', 'shipping_fee' => 0]);
        $lines = collect($data['items'])->keyBy('variant_id');
        $totalWeight = 0;
        foreach ($order->items->sortBy('product_variant_id') as $item) {
            $line = $lines->get($item->product_variant_id);
            $weight = round((float) $line['actual_weight'], 3);
            // Keep measured weight authoritative; avoid quantity x rounded size drift.
            $item->forceFill(['total_weight' => $weight, 'packed_weight' => $weight, 'actual_weight' => $weight, 'packed_quantity' => $item->quantity])->save();
            $totalWeight += $weight;
            $inventory = Inventory::firstOrCreate(['warehouse_id' => $warehouseId, 'product_variant_id' => $item->product_variant_id], ['quantity' => 0, 'reserved_quantity' => 0, 'weight_kg' => 0]);
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            // Respect all live reservations, including packed goods owned by other orders.
            $reserved = max((float) $inventory->reserved_quantity, (float) $inventory->reservations()->sum('quantity'));
            if ($import) {
                if (!isset($line['unit_cost'])) $this->fail('Vui lòng nhập giá vốn cho từng sản phẩm vừa nhập kho.');
                $import->items()->create(['product_variant_id' => $item->product_variant_id, 'quantity' => $item->quantity, 'weight_kg' => $weight, 'unit_cost' => $line['unit_cost']]);
                $inventory->quantity += $item->quantity;
                $inventory->weight_kg = (float) $inventory->weight_kg + $weight;
                $inventory->save();
                InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => $item->quantity, 'type' => 'import', 'reference_type' => InventoryDocument::class, 'reference_id' => $import->id, 'user_id' => $actor->id]);
            }
            if ((float) $inventory->quantity - $reserved + 0.00001 < $item->quantity) $this->fail('Không đủ tồn kho khả dụng cho sản phẩm #'.$item->product_variant_id.'.');
            $inventory->quantity -= $item->quantity;
            $inventory->weight_kg = max(0, (float) $inventory->weight_kg - $weight);
            $inventory->save();
            $export->items()->create(['product_variant_id' => $item->product_variant_id, 'quantity' => $item->quantity, 'weight_kg' => $weight, 'unit_cost' => $item->price]);
            InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => -$item->quantity, 'type' => 'export', 'reference_type' => InventoryDocument::class, 'reference_id' => $export->id, 'user_id' => $actor->id]);
            ProductVariant::whereKey($item->product_variant_id)->update(['stock' => Inventory::where('product_variant_id', $item->product_variant_id)->sum('quantity')]);
        }
        $order->forceFill([
            'is_special_pickup' => true, 'warehouse_id' => $warehouseId,
            'delivery_date' => today(), 'delivered_at' => now(),
            'status' => Order::STATUS_DELIVERED, 'delivery_status' => 'delivered',
            'payment_status' => 'unpaid', 'payment_method' => null,
            'amount_paid' => 0, 'amount_due' => 0, 'collected_amount' => 0,
            'total_weight' => $totalWeight, 'skip_auto_cancel' => true,
            'special_import_document_id' => $import?->id,
            'special_export_document_id' => $export->id, 'special_payment_id' => null,
            'shipper_note' => 'Khách tự lấy tại kho; shipper mặc định được giữ để kế toán xác nhận doanh số sau.',
        ])->save();
        OrderHistory::create([
            'order_id' => $order->id, 'user_id' => $actor->id, 'role' => 'system',
            'action' => 'special_pickup_completed', 'status_before' => null,
            'status_after' => Order::STATUS_DELIVERED,
            'note' => 'Hoàn tất giao hàng đặc biệt tại kho #'.$warehouseId.'; shipper mặc định #'.$order->shipper_id.'. Chờ kế toán xác nhận doanh thu và cập nhật tiền thu; không tạo phiếu thu tại bước giao hàng.',
        ]);
    }
}
