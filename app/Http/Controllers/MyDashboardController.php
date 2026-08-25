<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\Customer;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use App\Notifications\OrderWorkflowNotification;
use App\Notifications\WarehouseNewOrderApproved;
use App\Notifications\WarehouseOrderAdjustmentConfirmed;
use App\Notifications\WarehouseOrderAdjustmentRejected;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MyDashboardController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $payload = $this->buildPayload($user, $request);
        $settings   = $this->settings;
        return view('site.my_dashboard_sales', array_merge($payload, ['settings' => $settings]));
    }

    public function stats(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        return response()->json($this->buildPayload($user, $request));
    }

    public function notifications(): View
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $notificationsQuery = $user->notifications()
            ->where('type', '!=', \App\Notifications\DepartmentBroadcastNotification::class);

        if (function_exists('userHasActiveSalesRole') && userHasActiveSalesRole($user)) {
            $notificationsQuery->whereNotIn('type', $this->packingNotificationClasses());
        }

        $notifications = $notificationsQuery
            ->latest()
            ->paginate(20);

        return view('site.my_dashboard_notifications', [
            'notifications' => $notifications,
            'settings' => $this->settings,
        ]);
    }

    public function openNotification(string $notificationId): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if (function_exists('userHasActiveSalesRole') && userHasActiveSalesRole($user)) {
            $notificationClass = (string) $notification->type;
            $businessType = (string) ($notification->data['type'] ?? '');

            if (in_array($notificationClass, $this->packingNotificationClasses(), true)
                || str_starts_with($businessType, 'order_workflow_')
                || $notificationClass === OrderWorkflowNotification::class) {
                return redirect()->to($this->salesOrderNotificationUrl($user, $notification->data ?? []));
            }
        }

        $url = (string) ($notification->data['url'] ?? '');

        return redirect()->to($url !== '' ? $url : route('pages.my_dashboard.notifications'));
    }

    private function packingNotificationClasses(): array
    {
        return [
            WarehouseNewOrderApproved::class,
            WarehouseOrderAdjustmentConfirmed::class,
            WarehouseOrderAdjustmentRejected::class,
        ];
    }

    private function salesOrderNotificationUrl(User $user, array $data): string
    {
        $activeRole = strtolower(trim((string) session('active_role', '')));
        $orderId = max(0, (int) ($data['order_id'] ?? 0));

        if (in_array($activeRole, ['leader', 'leader_sale', 'sale_manager'], true)) {
            return route('pages.my_team_orders', ['highlight' => $orderId ?: null]);
        }

        if (in_array($activeRole, ['manager', 'manager_sale'], true)) {
            return route('pages.all_team_orders', ['highlight' => $orderId ?: null]);
        }

        $order = $orderId > 0 ? Order::query()->find($orderId) : null;
        $date = $order?->business_date
            ?? $order?->created_at?->toDateString()
            ?? now()->toDateString();

        return route('pages.my_orders.monitoring', [
            'tab' => 'today',
            'view' => 'cards',
            'date_field' => 'business_date',
            'date' => $date,
            'highlight' => $orderId ?: null,
        ]);
    }

    public function markAllNotificationsAsRead(): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $user->unreadNotifications()
            ->where('type', '!=', \App\Notifications\DepartmentBroadcastNotification::class)
            ->get()
            ->each->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
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

        $productTotal = max(0, $subtotalAmount - $totalDiscount);
        $vatPercent = (bool) ($order->charge_vat ?? false)
            ? min(max((float) ($order->vat_percent ?? 0), 0), 100)
            : 0;
        $vatAmount = round($productTotal * $vatPercent / 100, 2);
        $customerShippingFee = (bool) ($order->collect_customer_shipping_fee ?? false)
            ? max(0, (float) ($order->customer_shipping_fee ?? 0))
            : 0;

        $totalWeight = (float) $order->items->sum(function ($item) {
            return (float) ($item->total_weight ?? ((float) ($item->quantity ?? 0) * (float) ($item->unit_weight ?? 0)));
        });

        $newTotal = round($productTotal + $vatAmount + $customerShippingFee + $shippingFee + $foamBoxFee, 2);
        $amountPaid = (float) ($order->amount_paid ?? 0);

        $order->update([
            'subtotal_amount' => round($subtotalAmount, 2),
            'item_discount_total' => round($itemDiscountTotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_weight' => round($totalWeight, 3),
            'vat_amount' => $vatAmount,
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

    private function buildPayload(User $user, ?Request $request = null): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $memberIds = $this->resolveScopedUserIds($user);
        $dashboardRole = $this->resolveDashboardRole($user);
        $isManagerDashboard = in_array($dashboardRole, ['manager', 'manager_sale', 'sale_manager'], true);

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
        $weeklyCustomerProduction = $this->buildWeeklyCustomerProduction($memberIds, $now);

        $pendingWarehouseAdjustments = Order::query()
            ->with(['customer', 'warehouse', 'items.variant.product'])
            ->whereIn('user_id', $memberIds)
            ->where('warehouse_adjustment_status', Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION)
            ->orderByDesc('warehouse_adjustment_requested_at')
            ->limit(10)
            ->get();

        $productPriceBoard = $this->buildProductPriceBoard();
        $productPriceAppliedDates = $productPriceBoard
            ->pluck('applied_dates')
            ->flatten()
            ->filter()
            ->unique()
            ->values();

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
            'isManagerDashboard' => $isManagerDashboard,
            'managerDashboard' => $isManagerDashboard
                ? $this->buildManagerDashboard($memberIds, $request)
                : null,
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
            'weeklyCustomerProduction' => $weeklyCustomerProduction,
            'timeline' => $timeline,
            'assignedCustomers' => $assignedCustomers,
            'pendingWarehouseAdjustments' => $pendingWarehouseAdjustments,
            'productPriceBoard' => $productPriceBoard,
            'productPriceAppliedDates' => $productPriceAppliedDates,
        ];
    }

    /**
     * Ma trận sản lượng khách nhận trong tuần hiện tại (thứ Hai - Chủ nhật).
     * Sản lượng được tính theo tổng số lượng của các dòng hàng trên đơn hợp lệ.
     */
    private function buildWeeklyCustomerProduction(array $memberIds, Carbon $referenceDate): array
    {
        $weekStart = $referenceDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'];

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $dayNames, $referenceDate) {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'label' => $dayNames[$offset],
                'display_date' => $date->format('d/m'),
                'is_today' => $date->isSameDay($referenceDate),
            ];
        })->values();

        $rows = DB::table('orders as o')
            ->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereIn('o.user_id', $memberIds)
            ->whereBetween('o.delivery_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereNotIn('o.status', ['draft', 'cancelled', 'rejected'])
            ->when(Schema::hasColumn('orders', 'is_return_order'), fn ($query) => $query->where(function ($nested) {
                $nested->whereNull('o.is_return_order')->orWhere('o.is_return_order', false);
            }))
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.name', 'o.delivery_date')
            ->orderBy('c.name')
            ->selectRaw('c.id as customer_id, c.name as customer_name, o.delivery_date, COALESCE(SUM(oi.quantity), 0) as quantity')
            ->get();

        $quantitiesByCustomer = $rows->groupBy('customer_id');
        $customers = $quantitiesByCustomer->map(function (Collection $customerRows) use ($days) {
            $first = $customerRows->first();
            $quantitiesByDate = $customerRows->keyBy(fn ($row) => Carbon::parse($row->delivery_date)->toDateString());

            return [
                'customer_id' => (int) $first->customer_id,
                'customer_name' => (string) $first->customer_name,
                'quantities' => $days->mapWithKeys(fn (array $day) => [
                    $day['date'] => (int) ($quantitiesByDate->get($day['date'])->quantity ?? 0),
                ])->all(),
            ];
        })->sortBy('customer_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return [
            'week_label' => $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
            'days' => $days->all(),
            'customers' => $customers->all(),
        ];
    }

    /**
     * Tổng hợp các chỉ số điều hành của manager từ dữ liệu nghiệp vụ thật.
     * Khoảng ngày mặc định là tuần hiện tại và luôn được giới hạn tối đa 93 ngày.
     */
    private function buildManagerDashboard(array $memberIds, ?Request $request): array
    {
        $today = now()->startOfDay();
        try {
            $from = $request?->filled('from')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->input('from'))->startOfDay()
                : $today->copy()->startOfWeek();
            $to = $request?->filled('to')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->input('to'))->endOfDay()
                : $today->copy()->endOfWeek();
        } catch (\Throwable) {
            $from = $today->copy()->startOfWeek();
            $to = $today->copy()->endOfWeek();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        if ($from->diffInDays($to) > 92) {
            $from = $to->copy()->subDays(92)->startOfDay();
        }

        $periodDays = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($periodDays - 1)->startOfDay();

        $current = $this->managerPeriodSnapshot($memberIds, $from, $to);
        $previous = $this->managerPeriodSnapshot($memberIds, $previousFrom, $previousTo, false);

        foreach (['customers', 'quantity', 'defect_rate', 'receivables', 'shipping_cost'] as $key) {
            $current['changes'][$key] = $this->percentageChange(
                (float) ($current['summary'][$key] ?? 0),
                (float) ($previous['summary'][$key] ?? 0)
            );
        }

        return array_merge($current, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'range_label' => $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y'),
        ]);
    }

    private function managerPeriodSnapshot(array $memberIds, Carbon $from, Carbon $to, bool $withDetails = true): array
    {
        $excludedStatuses = ['draft', 'cancelled', 'rejected'];
        $orders = Order::query()
            ->with($withDetails ? ['items.variant', 'user:id,name', 'customer:id,created_at'] : [])
            ->whereIn('user_id', $memberIds)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', $excludedStatuses)
            ->when(Schema::hasColumn('orders', 'is_return_order'), fn ($q) => $q->where(function ($nested) {
                $nested->whereNull('is_return_order')->orWhere('is_return_order', false);
            }))
            ->get();

        $orderIds = $orders->pluck('id');
        $items = $withDetails ? $orders->pluck('items')->flatten() : collect();
        $quantity = $withDetails
            ? (int) $items->sum(fn ($item) => (int) ($item->quantity ?? 0))
            : (int) DB::table('order_items')->whereIn('order_id', $orderIds)->sum('quantity');

        $returnRows = collect();
        $returnedQuantityByOrder = collect();
        $returnedQuantityByUser = collect();
        $returnShippingByUser = collect();
        $returnedQuantity = 0;
        $returnedValue = 0.0;
        if (Schema::hasTable('order_returns')) {
            $returnRows = DB::table('order_returns as ore')
                ->join('orders as return_orders', 'return_orders.id', '=', 'ore.order_id')
                ->whereIn('return_orders.user_id', $memberIds)
                ->whereBetween('ore.created_at', [$from, $to])
                ->get(['ore.*', 'return_orders.user_id as sale_user_id']);
            $returnIds = $returnRows->pluck('id');
            if (Schema::hasTable('return_items') && $returnIds->isNotEmpty()) {
                $returnedQuantityByOrder = DB::table('return_items as ri')
                    ->join('order_returns as ore', 'ore.id', '=', 'ri.order_return_id')
                    ->whereIn('ri.order_return_id', $returnIds)
                    ->groupBy('ore.order_id')
                    ->selectRaw('ore.order_id, SUM(ri.quantity) as quantity')
                    ->pluck('quantity', 'ore.order_id');
                $returnedQuantity = (int) $returnedQuantityByOrder->sum();
                $returnedQuantityByUser = $returnRows->groupBy('sale_user_id')->map(function (Collection $userReturns) use ($returnedQuantityByOrder) {
                    return (int) $userReturns->pluck('order_id')->unique()->sum(fn ($orderId) => (int) ($returnedQuantityByOrder[$orderId] ?? 0));
                });
            }
            if (Schema::hasColumn('order_returns', 'refund_amount')) {
                $returnedValue = (float) $returnRows->sum('refund_amount');
            }
            if (Schema::hasColumn('order_returns', 'return_shipping_fee')) {
                $returnShippingByUser = $returnRows->groupBy('sale_user_id')
                    ->map(fn (Collection $userReturns) => (float) $userReturns->sum('return_shipping_fee'));
            }
        }

        $receivables = Schema::hasColumn('orders', 'amount_due')
            ? (float) $orders->sum('amount_due')
            : 0.0;
        $overdue = Schema::hasColumn('orders', 'amount_due')
            ? (float) $orders->filter(fn ($order) => (float) $order->amount_due > 0 && $order->created_at->lt(now()->subDays(7)))->sum('amount_due')
            : 0.0;
        $deliveryShippingCost = Schema::hasColumn('orders', 'shipping_fee')
            ? (float) $orders->sum(fn ($order) => ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0)
            : 0.0;
        $returnShippingCost = Schema::hasTable('order_returns') && Schema::hasColumn('order_returns', 'return_shipping_fee')
            ? (float) $returnRows->sum('return_shipping_fee')
            : 0.0;
        $shippingCost = $deliveryShippingCost + $returnShippingCost;

        $summary = [
            'customers' => (int) $orders->pluck('customer_id')->filter()->unique()->count(),
            'quantity' => $quantity,
            'defect_rate' => $quantity > 0 ? round($returnedQuantity * 100 / $quantity, 2) : 0.0,
            'receivables' => $receivables,
            'shipping_cost' => $shippingCost,
        ];

        if (!$withDetails) {
            return ['summary' => $summary];
        }

        $sizeDefinitions = [
            ['key' => 'size_1', 'label' => 'Size 1', 'range' => '1,5 – <2,0 kg', 'min' => 1.5, 'max' => 2.0, 'color' => '#1d6fbb'],
            ['key' => 'size_2', 'label' => 'Size 2', 'range' => '2,0 – <2,5 kg', 'min' => 2.0, 'max' => 2.5, 'color' => '#20875b'],
            ['key' => 'size_3', 'label' => 'Size 3', 'range' => '2,5 – 3,0 kg', 'min' => 2.5, 'max' => 3.001, 'color' => '#f59e0b'],
            ['key' => 'size_4', 'label' => 'Size 4', 'range' => '>3,0 kg', 'min' => 3.001, 'max' => PHP_FLOAT_MAX, 'color' => '#dc2638'],
        ];
        $sizes = collect($sizeDefinitions)->map(function (array $definition) use ($items, $quantity) {
            $sizeQuantity = (int) $items->sum(function ($item) use ($definition) {
                $weight = (float) ($item->unit_weight ?? 0);
                if ($weight <= 0) {
                    $weight = (float) ($item->variant?->size ?? 0);
                }
                return $weight >= $definition['min'] && $weight < $definition['max']
                    ? (int) ($item->quantity ?? 0)
                    : 0;
            });
            return array_merge($definition, [
                'quantity' => $sizeQuantity,
                'percentage' => $quantity > 0 ? round($sizeQuantity * 100 / $quantity, 1) : 0,
            ]);
        })->values();
        $bestSize = $sizes->sortByDesc('quantity')->first();

        $confirmedRevenueByUser = $this->confirmedRevenueByUser($memberIds, $from, $to);

        $employeeRows = $orders->groupBy('user_id')->map(function (Collection $userOrders, $userId) use ($confirmedRevenueByUser, $returnedQuantityByUser, $returnShippingByUser, $from, $to) {
            $employeeQuantity = (int) $userOrders->pluck('items')->flatten()->sum('quantity');
            $employeeReturns = (int) ($returnedQuantityByUser[$userId] ?? 0);
            $completed = (int) $userOrders->whereIn('status', ['delivered', 'completed'])->count();
            return [
                'name' => (string) ($userOrders->first()?->user?->name ?? ('NV #' . $userId)),
                'quantity' => $employeeQuantity,
                'revenue' => (float) ($confirmedRevenueByUser[$userId] ?? 0),
                'new_customers' => $userOrders->filter(fn ($order) => $order->customer && $order->customer->created_at->between($from, $to))->pluck('customer_id')->unique()->count(),
                'defect_rate' => $employeeQuantity > 0 ? round($employeeReturns * 100 / $employeeQuantity, 1) : 0,
                'debt' => (float) $userOrders->sum('amount_due'),
                'shipping_cost' => (float) $userOrders->sum(fn ($order) => ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0)
                    + (float) ($returnShippingByUser[$userId] ?? 0),
                'completion_rate' => $userOrders->count() > 0 ? round($completed * 100 / $userOrders->count(), 1) : 0,
            ];
        })->sortByDesc('revenue')->values();

        $completedOrders = $orders->whereIn('status', ['delivered', 'completed']);
        $onTimeOrders = $completedOrders->filter(function ($order) {
            if (!$order->delivery_date || !$order->delivered_at) return false;
            return $order->delivered_at->endOfDay()->lte(Carbon::parse($order->delivery_date)->endOfDay());
        })->count();

        return [
            'summary' => array_merge($summary, [
                'best_size' => $bestSize,
                'returned_quantity' => $returnedQuantity,
                'returned_value' => $returnedValue,
                'overdue_receivables' => $overdue,
                'overdue_rate' => $receivables > 0 ? round($overdue * 100 / $receivables, 2) : 0,
                'shipping_per_unit' => $quantity > 0 ? round($shippingCost / $quantity) : 0,
                'shipping_orders' => $orders->filter(fn ($order) => (float) ($order->shipping_fee ?? 0) > 0)->count(),
            ]),
            'changes' => [],
            'sizes' => $sizes,
            'return_reasons' => $returnRows->groupBy(fn ($row) => trim((string) ($row->reason ?? '')) ?: 'Khác')
                ->map->count()->sortDesc()->take(4),
            'employees' => $employeeRows,
            'kpis' => [
                'completion_rate' => $orders->count() > 0 ? round($completedOrders->count() * 100 / $orders->count(), 1) : 0,
                'on_time_rate' => $completedOrders->count() > 0 ? round($onTimeOrders * 100 / $completedOrders->count(), 1) : 0,
            ],
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return abs($current) < 0.00001 ? 0.0 : null;
        }

        return round(($current - $previous) * 100 / abs($previous), 1);
    }

    /**
     * Doanh thu chỉ được ghi nhận từ đối soát đã xác nhận. Một số database cũ
     * có thể đã có bảng nhưng chưa có đủ các cột của migration mới, nên không
     * thể chỉ kiểm tra hasTable() rồi mặc định truy vấn confirmed_at/sale_id.
     */
    private function confirmedRevenueByUser(array $memberIds, Carbon $from, Carbon $to): Collection
    {
        if (empty($memberIds)) {
            return collect();
        }

        if (!Schema::hasTable('accounting_reconciliations')) {
            return $this->importedSalesRevenueByUser($memberIds, $from, $to);
        }

        $table = 'accounting_reconciliations';
        $requiredColumns = ['status', 'recognized_revenue'];
        if (!Schema::hasColumns($table, $requiredColumns)) {
            return $this->importedSalesRevenueByUser($memberIds, $from, $to);
        }

        $dateColumn = Schema::hasColumn($table, 'confirmed_at')
            ? 'confirmed_at'
            : (Schema::hasColumn($table, 'updated_at') ? 'updated_at' : null);
        if ($dateColumn === null) {
            return $this->importedSalesRevenueByUser($memberIds, $from, $to);
        }

        try {
            if (Schema::hasColumn($table, 'sale_id')) {
                $confirmed = DB::table($table)
                    ->selectRaw('sale_id, COALESCE(SUM(recognized_revenue), 0) as revenue')
                    ->whereIn('sale_id', $memberIds)
                    ->where('status', 'confirmed')
                    ->whereBetween($dateColumn, [$from, $to])
                    ->whereNotNull('sale_id')
                    ->groupBy('sale_id')
                    ->pluck('revenue', 'sale_id');
                return $this->mergeImportedSalesRevenue($confirmed, $memberIds, $from, $to);
            }

            // Tương thích database cũ chưa có sale_id: lấy sale theo đơn hàng.
            if (Schema::hasColumn($table, 'order_id')) {
                $confirmed = DB::table($table . ' as ar')
                    ->join('orders as revenue_orders', 'revenue_orders.id', '=', 'ar.order_id')
                    ->selectRaw('revenue_orders.user_id as sale_id, COALESCE(SUM(ar.recognized_revenue), 0) as revenue')
                    ->whereIn('revenue_orders.user_id', $memberIds)
                    ->where('ar.status', 'confirmed')
                    ->whereBetween('ar.' . $dateColumn, [$from, $to])
                    ->groupBy('revenue_orders.user_id')
                    ->pluck('revenue', 'sale_id');
                return $this->mergeImportedSalesRevenue($confirmed, $memberIds, $from, $to);
            }
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);
        }

        return $this->importedSalesRevenueByUser($memberIds, $from, $to);
    }

    private function importedSalesRevenueByUser(array $memberIds, Carbon $from, Carbon $to): Collection
    {
        if (!Schema::hasTable('accounting_sales_entries')) {
            return collect();
        }

        return DB::table('accounting_sales_entries')
            ->selectRaw('sale_id, COALESCE(SUM(total_amount), 0) as revenue')
            ->where('source', 'import')
            ->whereNull('order_id')
            ->whereIn('sale_id', $memberIds)
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('sale_id')
            ->groupBy('sale_id')
            ->pluck('revenue', 'sale_id');
    }

    private function mergeImportedSalesRevenue(Collection $confirmed, array $memberIds, Carbon $from, Carbon $to): Collection
    {
        $imported = $this->importedSalesRevenueByUser($memberIds, $from, $to);
        foreach ($imported as $saleId => $revenue) {
            $confirmed[$saleId] = (float) ($confirmed[$saleId] ?? 0) + (float) $revenue;
        }
        return $confirmed;
    }

    private function buildProductPriceBoard(): Collection
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
            return collect();
        }

        return Product::query()
            ->with(['variants' => function ($query) {
                $query->with('latestPriceRule')
                    ->when(Schema::hasColumn('product_variants', 'status'), fn ($q) => $q->where('status', true))
                    ->when(Schema::hasColumn('product_variants', 'sort_order'), fn ($q) => $q->orderBy('sort_order'))
                    ->orderByDesc('created_at')
                    ->orderBy('id');
            }])
            ->when(Schema::hasColumn('products', 'status'), fn ($query) => $query->where('status', true))
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (Product $product) {
                $variants = $product->variants
                    ->map(function (ProductVariant $variant) use ($product) {
                        $rule = $variant->latestPriceRule;
                        $publishedRule = $this->resolvePublishedPriceRule($variant, $rule);
                        $price = (float) ($publishedRule?->price ?? $rule?->price ?? $variant->final_price ?? $product->price ?? 0);

                        if ($price <= 0) {
                            return null;
                        }

                        $sizeLabel = $this->formatVariantSizeLabel($variant->size);
                        $priceUnit = $variant->effective_priced_by_kg
                            ? 'kg'
                            : strtolower((string) ($product->unit_label ?: 'cái'));

                        return [
                            'id' => $variant->id,
                            'name' => $variant->name ?: ($sizeLabel ? "{$sizeLabel} kg" : ($variant->sku ?: 'Biến thể')),
                            'size_label' => $sizeLabel,
                            'sku' => $variant->sku,
                            'price' => $price,
                            'price_key' => number_format($price, 2, '.', ''),
                            'price_unit' => $priceUnit,
                            'price_group_key' => number_format($price, 2, '.', '') . '|' . $priceUnit,
                            'start_date' => $publishedRule?->start_date ?? $rule?->start_date,
                        ];
                    })
                    ->filter()
                    ->values();

                if ($variants->isEmpty()) {
                    return null;
                }

                $priceGroups = $variants->groupBy('price_group_key');
                $representativeKey = $priceGroups
                    ->map(fn (Collection $items) => $items->count())
                    ->sortDesc()
                    ->keys()
                    ->first();
                $representativeVariant = $priceGroups->get($representativeKey)->first();
                $hasMixedPrices = $priceGroups->count() > 1;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name ?: 'Sản phẩm',
                    'representative_price' => (float) ($representativeVariant['price'] ?? 0),
                    'representative_price_key' => $representativeKey,
                    'representative_price_unit' => $representativeVariant['price_unit'] ?? 'kg',
                    'has_mixed_prices' => $hasMixedPrices,
                    'variants' => $hasMixedPrices ? $variants : collect(),
                    'applied_dates' => $variants->pluck('start_date')->filter()->unique()->values(),
                ];
            })
            ->filter()
            ->values();
    }

    private function resolvePublishedPriceRule(ProductVariant $variant, mixed $latestRule): mixed
    {
        if ($latestRule && (float) $latestRule->price > 0) {
            return $latestRule;
        }

        if (!Schema::hasTable('product_price_rules')) {
            return $latestRule;
        }

        return $variant->priceRules()
            ->where('price', '>', 0)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function formatVariantSizeLabel(mixed $size): ?string
    {
        if (!is_numeric($size) || (float) $size <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $size, 2, ',', '.'), '0'), ',');
    }

    private function resolveScopedUserIds(User $user): array
    {
        $dashboardRole = $this->resolveDashboardRole($user);

        if ($dashboardRole === 'sale') {
            return [$user->id];
        }

        if (in_array($dashboardRole, ['leader', 'leader_sale', 'sale_manager'], true)) {
            $memberIds = User::query()
                ->where('team_id', $user->team_id)
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        if (in_array($dashboardRole, ['manager', 'manager_sale'], true)) {
            $memberIds = User::query()
                ->when($user->team_id, fn ($q) => $q->where('team_id', $user->team_id))
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        return [$user->id];
    }

    private function resolveDashboardRole(User $user): string
    {
        $activeRole = strtolower(trim((string) session('active_role', '')));
        if ($activeRole !== '' && $user->hasRole($activeRole)) {
            return $activeRole;
        }

        foreach (['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return '';
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
