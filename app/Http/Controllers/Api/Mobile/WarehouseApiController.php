<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\TaskAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseApiController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $today = now()->toDateString();

        $stats = [
            'ready_to_pack' => Order::query()->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK])->count(),
            'packing' => Order::query()->where('status', Order::STATUS_PACKING)->count(),
            'packed_waiting_pickup' => Order::query()->where('status', Order::STATUS_READY_TO_SHIP)->count(),
            'returning' => Order::query()->where('status', Order::STATUS_RETURNING)->count(),
            'returns_today' => OrderReturn::query()->whereDate('updated_at', $today)->count(),
            'tasks_pending' => TaskAssignment::query()->where('status', TaskAssignment::STATUS_PENDING)->count(),
        ];

        return $this->ok($stats);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $status = (string) $request->query('status', '');
        $date = (string) $request->query('date', now()->toDateString());

        $query = Order::query()
            ->with(['customer:id,name,phone,address', 'items.variant.product'])
            ->whereDate('created_at', $date)
            ->latest('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING, Order::STATUS_READY_TO_SHIP]);
        }

        return $this->paginated($query->paginate(20));
    }

    public function startPacking(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        if (!in_array((string) $order->status, ['approved', Order::STATUS_READY_TO_PACK], true)) {
            return $this->fail('Don khong o trang thai cho dong goi', 422);
        }

        $before = (string) $order->status;
        $order->update(['status' => Order::STATUS_PACKING]);

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'mobile_start_packing',
            'user_id' => $request->user()->id,
            'role' => 'warehouse',
            'status_before' => $before,
            'status_after' => Order::STATUS_PACKING,
            'note' => 'Bat dau dong goi tu mobile app',
        ]);

        return $this->ok(null, 'Da bat dau dong goi');
    }

    public function completePacking(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        if ((string) $order->status !== Order::STATUS_PACKING) {
            return $this->fail('Don khong o trang thai dang dong goi', 422);
        }

        $before = (string) $order->status;
        $order->update(['status' => Order::STATUS_READY_TO_SHIP]);

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'mobile_complete_packing',
            'user_id' => $request->user()->id,
            'role' => 'warehouse',
            'status_before' => $before,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Hoan tat dong goi tu mobile app',
        ]);

        return $this->ok(null, 'Da hoan tat dong goi');
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;

        $query = Inventory::query()
            ->with(['productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('updated_at');

        $items = $query->paginate(30)->through(function (Inventory $inventory) {
            $variant = $inventory->productVariant;
            $product = $variant?->product;

            return [
                'id' => (int) $inventory->id,
                'variant_id' => (int) ($inventory->product_variant_id ?? 0),
                'sku' => (string) ($variant?->sku ?? ''),
                'product_name' => (string) ($product?->name ?? 'San pham'),
                'variant_name' => (string) ($variant?->name ?? ''),
                'quantity' => (int) ($inventory->quantity ?? 0),
                'reserved' => (int) ($inventory->reserved_quantity ?? 0),
                'available' => max(0, (int) ($inventory->quantity ?? 0) - (int) ($inventory->reserved_quantity ?? 0)),
                'updated_at' => optional($inventory->updated_at)->toIso8601String(),
            ];
        });

        return $this->paginated($items);
    }

    public function returns(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;

        $query = OrderReturn::query()
            ->with(['order:id,code,status', 'customer:id,name,phone', 'warehouse:id,name'])
            ->latest('updated_at');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $this->paginated($query->paginate(20));
    }

    public function tasks(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();

        $tasks = TaskAssignment::query()
            ->with(['creator:id,name', 'assignees.user:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
            })
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($tasks);
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $query = ProductVariant::query()->with('product:id,name')->latest('id');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->query('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%');
            });
        }

        return $this->paginated($query->paginate(30));
    }

    public function scanLookup(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $code = trim((string) $validated['code']);
        $variant = ProductVariant::query()
            ->with('product:id,name')
            ->where('sku', $code)
            ->orWhere('name', 'like', '%' . $code . '%')
            ->first();

        if (!$variant) {
            return $this->fail('Khong tim thay san pham theo ma scan', 404);
        }

        return $this->ok([
            'id' => (int) $variant->id,
            'sku' => (string) ($variant->sku ?? ''),
            'name' => (string) ($variant->name ?? ''),
            'product_name' => (string) ($variant->product?->name ?? ''),
            'is_priced_by_kg' => (bool) $variant->effective_priced_by_kg,
            'kg' => (float) $variant->effective_kg,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok($items);
    }

    private function ensureWarehouseRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('warehouse') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep truy cap API warehouse');
        }
    }
}
