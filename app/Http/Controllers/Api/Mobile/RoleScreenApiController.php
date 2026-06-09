<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\AccountingReconciliation;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\SupplierProductPrice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleScreenApiController extends BaseApiController
{
    public function show(Request $request, string $layout, string $key): JsonResponse
    {
        $this->authorizeLayout($request, $layout);

        return match ($layout) {
            'warehouse' => $this->warehouse($request, $key),
            'manager_shipper' => $this->managerShipper($request, $key),
            'sale' => $this->sale($request, $key),
            'accounting' => $this->accounting($request, $key),
            'ceo' => $this->ceo($request, $key),
            default => $this->fail('Layout khong duoc ho tro.', 404),
        };
    }

    private function warehouse(Request $request, string $key): JsonResponse
    {
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;
        $date = (string) $request->query('date', now()->toDateString());

        return match ($key) {
            'orders' => $this->orders([
                'approved',
                Order::STATUS_READY_TO_PACK,
                Order::STATUS_PACKING,
                Order::STATUS_READY_TO_SHIP,
            ], $warehouseId, $date),
            'supplier_prices' => $this->supplierPrices(),
            'incoming_transfers' => $this->incomingOrderTransfers($warehouseId),
            'incoming_inventory_transfers' => $this->incomingInventoryTransfers($warehouseId),
            'stock_in_create' => $this->documents('import', $warehouseId),
            'order_transfers' => $this->orderTransfers($warehouseId),
            'inventory_transfers' => $this->inventoryTransfers($warehouseId),
            'stock_out' => $this->documents('export', $warehouseId),
            'stock_out_orders' => $this->orders([Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], $warehouseId, null),
            default => $this->fail('Man hinh kho khong duoc ho tro.', 404),
        };
    }

    private function managerShipper(Request $request, string $key): JsonResponse
    {
        $today = now()->toDateString();

        return match ($key) {
            'manage_assignments' => $this->ok([
                'cards' => [
                    ['label' => 'Tổng đơn điều phối', 'value' => Order::query()->whereDate('created_at', $today)->count()],
                    ['label' => 'Chưa phân công', 'value' => Order::query()->whereDate('created_at', $today)->whereNull('shipper_id')->count()],
                    ['label' => 'Đã phân công', 'value' => Order::query()->whereDate('created_at', $today)->whereNotNull('shipper_id')->count()],
                    ['label' => 'Đang giao', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERING, 'shipping'])->count()],
                ],
                'items' => $this->latestOrders()->take(20)->values(),
            ]),
            'shipper_team', 'team_report' => $this->ok([
                'cards' => [
                    ['label' => 'Tổng shipper', 'value' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))->count()],
                    ['label' => 'Đang có đơn', 'value' => Order::query()->whereNotNull('shipper_id')->whereIn('status', [Order::STATUS_DELIVERING, 'shipping', Order::STATUS_READY_TO_SHIP])->distinct('shipper_id')->count('shipper_id')],
                    ['label' => 'Đã giao hôm nay', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->whereDate('updated_at', $today)->count()],
                    ['label' => 'Lịch trình hôm nay', 'value' => Order::query()->whereNotNull('shipper_id')->whereDate('created_at', $today)->count()],
                ],
                'items' => User::query()
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))
                    ->orderBy('name')
                    ->limit(30)
                    ->get()
                    ->map(fn (User $shipper) => [
                        'id' => (int) $shipper->id,
                        'title' => (string) $shipper->name,
                        'subtitle' => (string) ($shipper->phone ?? $shipper->email ?? ''),
                        'status' => 'shipper',
                        'updated_at' => optional($shipper->updated_at)->toIso8601String(),
                    ])
                    ->values(),
            ]),
            'manage_fees', 'shipping_fee_report' => $this->ok([
                'cards' => [
                    ['label' => 'Tổng phí ship', 'value' => (float) Order::query()->sum('shipping_fee')],
                    ['label' => 'Phí ship hôm nay', 'value' => (float) Order::query()->whereDate('created_at', $today)->sum('shipping_fee')],
                    ['label' => 'Đơn có phí ship', 'value' => Order::query()->where('shipping_fee', '>', 0)->count()],
                    ['label' => 'Đơn chưa có phí', 'value' => Order::query()->where(function ($q) {
                        $q->whereNull('shipping_fee')->orWhere('shipping_fee', 0);
                    })->count()],
                ],
                'items' => $this->latestOrders()->take(20)->values(),
            ]),
            'route_planning' => $this->ok([
                'cards' => [
                    ['label' => 'Đơn cần lập lịch', 'value' => Order::query()->whereIn('status', [Order::STATUS_READY_TO_SHIP, 'packed'])->whereDate('updated_at', $today)->count()],
                    ['label' => 'Shipper có lịch', 'value' => Order::query()->whereNotNull('shipper_id')->whereDate('updated_at', $today)->distinct('shipper_id')->count('shipper_id')],
                    ['label' => 'Đang giao', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERING, 'shipping'])->count()],
                    ['label' => 'Hoàn thành hôm nay', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->whereDate('updated_at', $today)->count()],
                ],
                'items' => $this->latestOrders()->take(20)->values(),
            ]),
            default => $this->fail('Man hinh Shipper Manager khong duoc ho tro.', 404),
        };
    }

    private function sale(Request $request, string $key): JsonResponse
    {
        if ($key !== 'my_orders') {
            return $this->fail('Man hinh sale khong duoc ho tro.', 404);
        }

        return $this->paginated(
            Order::query()
                ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
                ->where('user_id', (int) $request->user()->id)
                ->latest('updated_at')
                ->paginate(20)
        );
    }

    private function accounting(Request $request, string $key): JsonResponse
    {
        if ($key !== 'dashboard') {
            return $this->fail('Man hinh accounting khong duoc ho tro.', 404);
        }

        $today = now()->toDateString();
        $transactions = Transaction::query();

        return $this->ok([
            'cards' => [
                ['label' => 'Don hang', 'value' => Order::query()->count()],
                ['label' => 'Don chua doi soat', 'value' => Schema::hasTable('accounting_reconciliations') ? AccountingReconciliation::query()->where('status', AccountingReconciliation::STATUS_PENDING)->count() : 0],
                ['label' => 'Thu chi hom nay', 'value' => (float) (clone $transactions)->whereDate('created_at', $today)->sum('amount')],
                ['label' => 'Cong no khach hang', 'value' => (float) Order::query()->sum('amount_due')],
            ],
            'items' => $this->latestOrders()->take(20)->values(),
        ]);
    }

    private function ceo(Request $request, string $key): JsonResponse
    {
        if ($key !== 'dashboard') {
            return $this->fail('Man hinh CEO khong duoc ho tro.', 404);
        }

        $today = now()->toDateString();
        $todayOrders = Order::query()
            ->with([
                'customer:id,name,phone,address',
                'user:id,name,team_id',
                'user.team:id,name',
                'items.product:id,name,unit',
                'items.variant:id,name,sku,size,product_id',
            ])
            ->whereDate('created_at', $today)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REJECTED])
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->get();
        $revenue = Schema::hasTable('accounting_reconciliations')
            ? (float) AccountingReconciliation::query()->where('status', AccountingReconciliation::STATUS_CONFIRMED)->sum('recognized_revenue')
            : (float) Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->sum('total');

        return $this->ok([
            'cards' => [
                ['label' => 'Doanh thu', 'value' => $revenue],
                ['label' => 'Don hom nay', 'value' => Order::query()->whereDate('created_at', $today)->count()],
                ['label' => 'Dang dong goi', 'value' => Order::query()->where('status', Order::STATUS_PACKING)->count()],
                ['label' => 'Dang giao', 'value' => Order::query()->where('status', Order::STATUS_DELIVERING)->count()],
            ],
            'items' => $todayOrders->values(),
        ]);
    }

    private function orders(array $statuses, ?int $warehouseId, ?string $date): JsonResponse
    {
        $query = Order::query()
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
            ->whereIn('status', $statuses)
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->when($date, fn ($q) => $q->whereDate('created_at', $date))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function supplierPrices(): JsonResponse
    {
        $items = SupplierProductPrice::query()
            ->with(['supplier:id,name', 'product:id,name'])
            ->latest('created_at')
            ->paginate(20)
            ->through(fn (SupplierProductPrice $price) => [
                'id' => (int) $price->id,
                'title' => (string) ($price->product?->name ?? 'San pham'),
                'subtitle' => (string) ($price->supplier?->name ?? 'Nha cung cap'),
                'status' => optional($price->created_at)->format('d/m/Y H:i'),
                'amount' => (float) ($price->purchase_price ?? $price->min_price ?? 0),
            ]);

        return $this->paginated($items);
    }

    private function incomingOrderTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseTransfer::query()
            ->with(['order.customer', 'sourceWarehouse', 'targetWarehouse', 'shipper'])
            ->when($warehouseId, fn ($q) => $q->where('target_warehouse_id', $warehouseId))
            ->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
            ->latest('delivered_at');

        return $this->paginated($query->paginate(20));
    }

    private function orderTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseTransfer::query()
            ->with(['order.customer', 'sourceWarehouse', 'targetWarehouse', 'shipper'])
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('source_warehouse_id', $warehouseId)->orWhere('target_warehouse_id', $warehouseId);
            }))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function incomingInventoryTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseInventoryTransfer::query()
            ->with(['sourceWarehouse', 'targetWarehouse', 'requester', 'items.productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where('target_warehouse_id', $warehouseId))
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->latest('requested_at');

        return $this->paginated($query->paginate(20));
    }

    private function inventoryTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseInventoryTransfer::query()
            ->with(['sourceWarehouse', 'targetWarehouse', 'requester', 'items.productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('source_warehouse_id', $warehouseId)->orWhere('target_warehouse_id', $warehouseId);
            }))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function documents(string $type, ?int $warehouseId): JsonResponse
    {
        $query = InventoryDocument::query()
            ->with(['warehouse:id,name', 'supplier:id,name', 'user:id,name', 'items'])
            ->where('type', $type)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('document_date');

        return $this->paginated($query->paginate(20));
    }

    private function latestOrders()
    {
        return Order::query()
            ->with('customer:id,name,phone,address')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => [
                'id' => (int) $order->id,
                'title' => (string) ($order->code ?: '#' . $order->id),
                'subtitle' => (string) ($order->customer?->name ?? 'Khach hang'),
                'status' => (string) $order->status,
                'amount' => (float) ($order->total ?? 0),
                'updated_at' => optional($order->updated_at)->toIso8601String(),
            ]);
    }

    private function authorizeLayout(Request $request, string $layout): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $allowed = match ($layout) {
            'warehouse' => $user->hasRole('warehouse') || $user->hasRole('admin'),
            'sale' => $user->hasRole('sale') || $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('admin'),
            'accounting' => $user->hasRole('accounting') || $user->hasRole('accountant') || $user->hasRole('admin'),
            'ceo' => $user->hasRole('ceo') || $user->hasRole('admin'),
            'manager_shipper' => $user->hasRole('manager_shipper') || $user->hasRole('admin'),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Role khong duoc phep truy cap man hinh nay');
        }
    }
}
