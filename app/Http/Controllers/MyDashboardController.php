<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\Customer;
use App\Models\OrderHistory;
use App\Models\ProductPriceLog;
use App\Models\ProductVariant;
use App\Notifications\WarehouseOrderAdjustmentConfirmed;
use App\Notifications\WarehouseOrderAdjustmentRejected;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class MyDashboardController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function index()
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $payload = $this->buildPayload($user);
        $settings   = $this->settings;
        return view('site.my_dashboard_sales', array_merge($payload, ['settings' => $settings]));
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        return response()->json($this->buildPayload($user));
    }

    public function acceptCustomer(Customer $customer): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Check if customer is assigned to this user
        if ((int) $customer->assigned_to !== (int) $user->id) {
            return response()->json(['error' => 'Khách hàng này không được giao cho bạn'], 403);
        }

        // Update current owner
        $customer->update([
            'current_owner_sale_id' => $user->id,
        ]);

        // Log the action
        if (Schema::hasTable('admin_events')) {
            DB::table('admin_events')->insert([
                'user_id' => $user->id,
                'event_type' => 'customer_accepted',
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'metadata' => json_encode([
                    'customer_name' => $customer->name,
                    'customer_id' => $customer->id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã nhận khách hàng: ' . $customer->name,
        ]);
    }

    public function confirmWarehouseAdjustment(Order $order)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $memberIds = $this->resolveScopedUserIds($user);
        if (!in_array((int) $order->user_id, array_map('intval', $memberIds), true)) {
            abort(403);
        }

        if ($order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            return back()->with('error', 'Yêu cầu điều chỉnh không còn ở trạng thái chờ xác nhận.');
        }

        $changes = collect($order->warehouse_adjustment_changes ?? []);
        if ($changes->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu snapshot để áp dụng thay đổi.');
        }

        DB::transaction(function () use ($order, $user, $changes): void {
            $order->loadMissing(['items']);
            $itemsById = $order->items->keyBy('id');
            $variantsToLoad = $changes
                ->pluck('product_variant_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $variantsById = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $variantsToLoad->all())
                ->get()
                ->keyBy('id');

            foreach ($changes as $change) {
                $orderItemId = (int) ($change['order_item_id'] ?? 0);
                $variantId = (int) ($change['product_variant_id'] ?? 0);
                $newQty = (int) ($change['new_quantity'] ?? 0);
                $oldQty = (int) ($change['old_quantity'] ?? 0);

                $existingItem = $orderItemId > 0 ? $itemsById->get($orderItemId) : null;
                if (!$existingItem && $variantId > 0) {
                    $existingItem = $order->items()->where('product_variant_id', $variantId)->first();
                }

                if ($existingItem) {
                    if ($newQty <= 0) {
                        $existingItem->delete();
                        continue;
                    }

                    $unitWeight = (float) ($existingItem->unit_weight ?? 1);
                    $isPricedByKg = (bool) ($existingItem->is_priced_by_kg ?? true);
                    $factor = $isPricedByKg ? max(0.01, $unitWeight) : 1;

                    $existingItem->update([
                        'quantity' => $newQty,
                        'total_weight' => round($newQty * $unitWeight, 3),
                        'total' => round((float) ($existingItem->price ?? 0) * $newQty * $factor, 2),
                    ]);

                    continue;
                }

                if ($variantId <= 0 || $newQty <= 0 || $oldQty > 0) {
                    continue;
                }

                $variant = $variantsById->get($variantId);
                if (!$variant) {
                    continue;
                }

                $unitWeight = (float) ($variant->effective_kg ?? 1);
                $isPricedByKg = (bool) ($variant->effective_priced_by_kg ?? true);
                $price = (float) ($variant->final_price ?? 0);
                $factor = $isPricedByKg ? max(0.01, $unitWeight) : 1;

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $newQty,
                    'price' => $price,
                    'base_price' => $price,
                    'unit_discount' => 0,
                    'discount_type' => 'decrease',
                    'discount_total' => 0,
                    'unit_weight' => $unitWeight,
                    'is_priced_by_kg' => $isPricedByKg,
                    'total_weight' => round($newQty * $unitWeight, 3),
                    'total' => round($price * $newQty * $factor, 2),
                ]);
            }

            $this->recalculateOrderTotalsAfterWarehouseAdjustment($order->fresh('items'));

            $order->update([
                'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED,
                'warehouse_adjustment_confirmed_by' => $user->id,
                'warehouse_adjustment_confirmed_at' => now(),
                'warehouse_adjustment_rejected_by' => null,
                'warehouse_adjustment_rejected_at' => null,
                'warehouse_adjustment_rejected_reason' => null,
            ]);

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'sale_confirm_warehouse_adjustment',
                'user_id' => $user->id,
                'role' => 'sale',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Sale đã xác nhận và áp dụng thay đổi đơn từ kho.',
            ]);
        });

        $warehouseReceivers = User::query()
            ->when($order->warehouse_id, fn ($query) => $query->where('warehouse_id', $order->warehouse_id))
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['warehouse', 'admin']);
            })
            ->get();

        if ($warehouseReceivers->isNotEmpty()) {
            foreach ($warehouseReceivers as $receiver) {
                $receiver->notify(new WarehouseOrderAdjustmentConfirmed($order));
            }
        }

        return back()->with('success', 'Đã xác nhận thay đổi đơn. Kho có thể tiếp tục đóng hàng.');
    }

    private function recalculateOrderTotalsAfterWarehouseAdjustment(Order $order): void
    {
        $subtotalAmount = (float) $order->items->sum(function ($item) {
            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;

            return (float) ($item->base_price ?? $item->price ?? 0) * (int) $item->quantity * $factor;
        });

        $itemDiscountTotal = (float) $order->items->sum(function ($item) {
            if ($item->discount_total !== null) {
                return (float) $item->discount_total;
            }

            return (float) (($item->unit_discount ?? 0) * ($item->quantity ?? 0));
        });

        $extraDiscount = (float) ($order->extra_discount_total ?? 0);
        $totalDiscount = $itemDiscountTotal + $extraDiscount;

        $shippingFee = (bool) ($order->charge_shipping_fee ?? true)
            ? (float) ($order->shipping_fee ?? 0)
            : 0;

        $foamBoxFee = (bool) ($order->charge_foam_box_fee ?? false)
            ? (float) ($order->foam_box_price ?? 0)
            : 0;

        $totalWeight = (float) $order->items->sum(function ($item) {
            return (float) ($item->total_weight ?? ((float) ($item->quantity ?? 0) * (float) ($item->unit_weight ?? 0)));
        });

        $newTotal = max(0, round($subtotalAmount - $totalDiscount + $shippingFee + $foamBoxFee, 2));
        $amountPaid = (float) ($order->amount_paid ?? 0);

        $order->update([
            'subtotal_amount' => round($subtotalAmount, 2),
            'item_discount_total' => round($itemDiscountTotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_weight' => round($totalWeight, 3),
            'total' => $newTotal,
            'amount_due' => max(0, round($newTotal - $amountPaid, 2)),
        ]);
    }

    public function rejectWarehouseAdjustment(Request $request, Order $order)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $memberIds = $this->resolveScopedUserIds($user);
        if (!in_array((int) $order->user_id, array_map('intval', $memberIds), true)) {
            abort(403);
        }

        if ($order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            return back()->with('error', 'Yêu cầu điều chỉnh không còn ở trạng thái chờ xác nhận.');
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:2000'],
        ]);

        $rejectReason = trim((string) ($validated['reject_reason'] ?? ''));

        $order->update([
            'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'warehouse_adjustment_rejected_by' => $user->id,
            'warehouse_adjustment_rejected_at' => now(),
            'warehouse_adjustment_rejected_reason' => $rejectReason,
            'warehouse_adjustment_confirmed_by' => null,
            'warehouse_adjustment_confirmed_at' => null,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'sale_reject_warehouse_adjustment',
            'user_id' => $user->id,
            'role' => 'sale',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Sale từ chối thay đổi đơn từ kho. Lý do: ' . $rejectReason,
        ]);

        $warehouseReceivers = User::query()
            ->when($order->warehouse_id, fn ($query) => $query->where('warehouse_id', $order->warehouse_id))
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['warehouse', 'admin']);
            })
            ->get();

        if ($warehouseReceivers->isNotEmpty()) {
            foreach ($warehouseReceivers as $receiver) {
                $receiver->notify(new WarehouseOrderAdjustmentRejected($order));
            }
        }

        return back()->with('success', 'Đã từ chối yêu cầu điều chỉnh và gửi thông báo cho kho xử lý lại.');
    }

    private function buildPayload(User $user): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $memberIds = $this->resolveScopedUserIds($user);

        $ordersBaseQuery = Order::query()->whereIn('user_id', $memberIds);

        $totalRevenue = 0.0;
        if (Schema::hasTable('accounting_reconciliations')) {
            $totalRevenue = (float) DB::table('accounting_reconciliations')
                ->whereIn('sale_id', $memberIds)
                ->where('status', 'confirmed')
                ->sum('recognized_revenue');
        }

        $ordersThisMonth = (int) (clone $ordersBaseQuery)
            ->whereBetween('created_at', [$monthStart, $now])
            ->count();

        $customerIdsQuery = (clone $ordersBaseQuery)
            ->select('customer_id')
            ->whereNotNull('customer_id')
            ->distinct();

        $totalCustomers = (int) (clone $customerIdsQuery)->count('customer_id');

        $activeCustomerCount = (int) (clone $ordersBaseQuery)
            ->whereNotNull('customer_id')
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->distinct('customer_id')
            ->count('customer_id');

        $taskBaseQuery = TaskAssignment::query()
            ->whereHas('assignees', fn ($q) => $q->whereIn('user_id', $memberIds));

        $taskProcessingCount = (int) (clone $taskBaseQuery)
            ->whereIn('status', ['processing', 'in_progress'])
            ->count();

        $taskUnfinishedCount = (int) (clone $taskBaseQuery)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $commissionThisMonth = 0.0;
        $commissionFeed = collect();
        if (Schema::hasTable('order_commissions')) {
            $commissionBase = DB::table('order_commissions as oc')
                ->leftJoin('orders as o', 'o.id', '=', 'oc.order_id')
                ->leftJoin('customers as c', 'c.id', '=', 'oc.customer_id')
                ->whereIn('oc.sale_user_id', $memberIds);

            $commissionThisMonth = (float) (clone $commissionBase)
                ->whereBetween('oc.confirmed_at', [$monthStart, $now])
                ->sum('oc.commission_amount');

            $commissionFeed = (clone $commissionBase)
                ->select([
                    'oc.order_id',
                    'oc.order_total',
                    'oc.commission_percent',
                    'oc.commission_amount',
                    'oc.confirmed_at',
                    'o.code as order_code',
                    'c.name as customer_name',
                ])
                ->whereNotNull('oc.confirmed_at')
                ->orderByDesc('oc.confirmed_at')
                ->limit(12)
                ->get();
        }

        $salesChart = $this->buildSalesChart($memberIds, $monthStart, $now);

        $pendingWarehouseAdjustments = Order::query()
            ->with(['customer', 'warehouse', 'items.variant.product'])
            ->whereIn('user_id', $memberIds)
            ->where('warehouse_adjustment_status', Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION)
            ->orderByDesc('warehouse_adjustment_requested_at')
            ->limit(10)
            ->get();

        $recentPriceUpdates = $this->buildRecentPriceUpdates();

        $timeline = DB::table('task_status_logs as l')
            ->join('task_assignments as t', 't.id', '=', 'l.task_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.changed_by')
            ->where(function ($query) use ($memberIds) {
                $query->whereIn('t.created_by', $memberIds)
                    ->orWhereExists(function ($sub) use ($memberIds) {
                        $sub->select(DB::raw(1))
                            ->from('task_assignees as ta')
                            ->whereColumn('ta.task_id', 't.id')
                            ->whereIn('ta.user_id', $memberIds);
                    });
            })
            ->orderByDesc('l.created_at')
            ->limit(15)
            ->get([
                'l.from_status',
                'l.to_status',
                'l.reason',
                'l.created_at',
                't.code as task_code',
                't.title as task_title',
                'u.name as changed_by_name',
            ]);

        // Get assigned customers (for sales-flow roles only)
        $assignedCustomers = collect();
        if ($user->isSalesFlowRole()) {
            $assignedCustomers = Customer::query()
                ->where('assigned_to', $user->id)
                ->where(function ($query) use ($user) {
                    $query->whereNull('current_owner_sale_id')
                        ->orWhere('current_owner_sale_id', '!=', $user->id);
                })
                ->orderByDesc('assigned_at')
                ->limit(10)
                ->select('id', 'name', 'phone', 'address', 'assigned_at', 'current_owner_sale_id')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'address' => $customer->address,
                        'assigned_at' => $customer->assigned_at,
                        'is_accepted' => (int) $customer->current_owner_sale_id === (int) auth()->id(),
                    ];
                });
        }

        return [
            'dashboardStats' => [
                'total_revenue' => $totalRevenue,
                'commission_this_month' => $commissionThisMonth,
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomerCount,
                'orders_this_month' => $ordersThisMonth,
                'tasks_processing' => $taskProcessingCount,
                'tasks_unfinished' => $taskUnfinishedCount,
            ],
            'commissionFeed' => $commissionFeed,
            'salesChart' => $salesChart,
            'timeline' => $timeline,
            'assignedCustomers' => $assignedCustomers,
            'pendingWarehouseAdjustments' => $pendingWarehouseAdjustments,
            'recentPriceUpdates' => $recentPriceUpdates,
        ];
    }

    private function buildRecentPriceUpdates(): Collection
    {
        if (!Schema::hasTable('product_price_logs') || !Schema::hasTable('product_price_rules')) {
            return collect();
        }

        return ProductPriceLog::query()
            ->with(['variant.product', 'priceRule'])
            ->whereNotNull('product_variant_id')
            ->where('applied_at', '>=', now()->subDays(7))
            ->orderByDesc('applied_at')
            ->limit(12)
            ->get()
            ->map(function (ProductPriceLog $log) {
                $variant = $log->variant;
                $product = $variant?->product;
                $startDate = $log->priceRule?->start_date ?: optional($log->applied_at)->toDateString();

                return [
                    'product_name' => $product?->name ?: 'Sản phẩm',
                    'variant_name' => $variant?->name ?: null,
                    'sku' => $variant?->sku,
                    'size' => $variant?->size,
                    'price' => (float) $log->new_price,
                    'start_date' => $startDate,
                    'applied_at' => $log->applied_at,
                ];
            });
    }

    private function resolveScopedUserIds(User $user): array
    {
        if ($user->hasRole('sale')) {
            return [$user->id];
        }

        if ($user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager')) {
            $memberIds = User::query()
                ->where('team_id', $user->team_id)
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        if ($user->hasRole('manager') || $user->hasRole('manager_sale')) {
            $memberIds = User::query()
                ->when($user->team_id, fn ($q) => $q->where('team_id', $user->team_id))
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        return [$user->id];
    }

    private function buildSalesChart(array $memberIds, Carbon $from, Carbon $to): array
    {
        $rows = collect();
        if (Schema::hasTable('accounting_reconciliations')) {
            $rows = DB::table('accounting_reconciliations')
                ->selectRaw('DATE(confirmed_at) as day, COALESCE(SUM(recognized_revenue), 0) as total_amount')
                ->whereIn('sale_id', $memberIds)
                ->where('status', 'confirmed')
                ->whereBetween('confirmed_at', [$from, $to])
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');
        }

        $labels = [];
        $values = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $day = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $values[] = (float) ($rows[$day]->total_amount ?? 0);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
