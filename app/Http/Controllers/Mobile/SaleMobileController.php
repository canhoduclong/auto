<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ProductVariant;
use App\Models\UserProductVariantPreference;
use App\Support\ProductVariantSorter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleMobileController extends Controller
{
    public function index()
    {
        return view('mobile.sale.index');
    }

    public function customers(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));

        $query = Customer::query()
            ->select(['id', 'name', 'phone', 'address', 'assigned_to', 'status'])
            ->orderByDesc('id');

        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('user_id', $user->id);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $rows = $query->limit(60)->get()->map(function (Customer $customer) {
            return [
                'id' => (int) $customer->id,
                'name' => (string) $customer->name,
                'phone' => (string) ($customer->phone ?? '—'),
                'address' => (string) ($customer->address ?? '—'),
                'status' => (string) ($customer->status ?? 'active'),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = trim((string) $request->query('status', ''));

        $query = Order::query()
            ->with([
                'customer:id,name,phone',
                'items.product:id,name,unit,kg,is_priced_by_kg',
                'items.variant:id,name,sku,kg,is_priced_by_kg',
            ])
            ->select(['id', 'code', 'customer_id', 'status', 'total', 'amount_due', 'created_at', 'user_id', 'daily_sequence'])
            ->orderByDesc('id');

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->limit(60)->get()->map(function (Order $order) {
            $items = $order->items->map(function ($item) {
                $productName = (string) ($item->product?->name ?: $item->variant?->name ?: 'Sản phẩm');
                $variantName = trim((string) ($item->variant?->name ?: $item->variant?->sku ?: ''));
                $unitWeight = (float) ($item->effective_unit_weight ?? $item->unit_weight ?? 1);

                return [
                    'name' => $productName,
                    'variant_name' => $variantName,
                    'display_name' => trim($productName . ($variantName !== '' ? ' (' . $variantName . ')' : '')),
                    'quantity' => (float) ($item->quantity ?? 0),
                    'size' => $unitWeight,
                    'total_label' => (string) ($item->display_total_label ?? ''),
                    'unit_price' => (float) ($item->price ?? 0),
                    'line_total' => (float) ($item->total ?? 0),
                ];
            })->values();

            return [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'number' => (int) ($order->daily_sequence ?: $order->id),
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'status' => (string) $order->status,
                'total' => (float) ($order->total ?? 0),
                'amount_due' => (float) ($order->amount_due ?? 0),
                'created_at' => optional($order->created_at)->format('d/m/Y H:i'),
                'items' => $items,
                'detail_url' => route('site.orders.show', $order),
                'edit_url' => route('site.orders.edit', $order),
                'copy_url' => route('site.orders.copy', $order),
                'payment_url' => route('orders.complete-payment', $order),
                'feedback_url' => route('site.orders.show', $order) . '#customer-feedback',
                'return_url' => route('site.order-returns.create', $order),
                'adjustment_url' => route('site.order-adjustments.create', $order),
                'cancel_url' => route('site.orders.cancel', $order),
                'trash_url' => route('site.orders.trash', $order),
                'confirm_copy_url' => route('site.orders.confirm-copy', $order),
                'can_cancel' => $order->canBeCancelled(),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $userId = (int) $request->user()->id;
        $sortBy = (string) $request->query('sort_by', 'preferred');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $inStock = $request->boolean('in_stock');

        $query = ProductVariant::query()
            ->with(['product:id,name,unit,kg,is_priced_by_kg,sort_order'])
            ->withAvailableStock()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('product_variants.name', 'like', '%' . $search . '%')
                        ->orWhere('product_variants.sku', 'like', '%' . $search . '%')
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($inStock, fn ($query) => $query->having('available_stock', '>', 0));

        ProductVariantSorter::joinProductSort($query, $userId);
        ProductVariantSorter::applyUserPreferencePrefix($query, $userId);

        match ($sortBy) {
            'name' => $query
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) {$sortDir}")
                ->orderBy('product_variants.id', 'desc'),
            'sku' => $query->orderBy('product_variants.sku', $sortDir)->orderBy('product_variants.id', 'desc'),
            'newest' => $query->orderByDesc('product_variants.id'),
            default => $query
                ->when($sortBy === 'stock', fn ($sortQuery) => $sortQuery
                    ->orderByRaw('CASE WHEN available_stock > 0 THEN 0 ELSE 1 END')
                    ->orderBy('available_stock', $sortDir))
                ->orderByRaw('COALESCE(sort_products.sort_order, 0) ASC')
                ->orderByRaw('COALESCE(product_variants.sort_order, 0) ASC')
                ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
                ->orderBy('product_variants.id'),
        };

        $rows = $query
            ->limit(80)
            ->get()
            ->map(function (ProductVariant $variant) {
                return [
                    'id' => (int) $variant->id,
                    'name' => trim(($variant->product?->name ? $variant->product->name . ' - ' : '') . ($variant->name ?: $variant->sku ?: ('SKU #' . $variant->id))),
                    'sku' => (string) ($variant->sku ?? ''),
                    'price' => (float) $variant->final_price,
                    'kg' => (float) $variant->effective_kg,
                    'is_priced_by_kg' => (bool) $variant->effective_priced_by_kg,
                    'available_stock' => (int) $variant->available_stock,
                    'is_pinned' => (bool) ($variant->is_pinned ?? false),
                    'user_sort_order' => $variant->user_sort_order !== null ? (int) $variant->user_sort_order : null,
                    'sort_order' => (int) ($variant->sort_order ?? 0),
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function updateProductPreference(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $preference = UserProductVariantPreference::query()->firstOrNew([
            'user_id' => (int) $request->user()->id,
            'product_variant_id' => (int) $variant->id,
        ]);

        if (array_key_exists('is_pinned', $validated)) {
            $preference->is_pinned = (bool) $validated['is_pinned'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $preference->sort_order = $validated['sort_order'] !== null ? (int) $validated['sort_order'] : null;
        }

        $preference->save();

        return response()->json([
            'data' => [
                'variant_id' => (int) $variant->id,
                'is_pinned' => (bool) $preference->is_pinned,
                'sort_order' => $preference->sort_order,
            ],
            'message' => 'Đã cập nhật sắp xếp sản phẩm.',
        ]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $customer = Customer::query()->findOrFail((int) $validated['customer_id']);
        if (!$user->hasRole('admin')) {
            abort_if(!in_array((int) $user->id, [(int) $customer->assigned_to, (int) $customer->user_id], true), 403, 'Khách hàng không thuộc quyền quản lý.');
        }

        $order = DB::transaction(function () use ($validated, $customer, $user) {
            $variantIds = collect($validated['items'])->pluck('variant_id')->map(fn ($id) => (int) $id)->unique()->values();
            $variants = ProductVariant::query()->with('product')->whereIn('id', $variantIds)->get()->keyBy('id');
            $items = collect($validated['items'])->map(function (array $item) use ($variants) {
                $variant = $variants->get((int) $item['variant_id']);
                if (!$variant) {
                    throw new \RuntimeException('Không tìm thấy sản phẩm.');
                }
                $quantity = max(1, (int) $item['quantity']);
                $unitWeight = max(0.01, (float) $variant->effective_kg);
                $isPricedByKg = (bool) $variant->effective_priced_by_kg;
                $price = (float) $variant->final_price;
                $lineTotal = $price * $quantity * ($isPricedByKg ? $unitWeight : 1);

                return compact('variant', 'quantity', 'unitWeight', 'isPricedByKg', 'price', 'lineTotal');
            });

            $total = (float) $items->sum('lineTotal');
            $totalWeight = (float) $items->sum(fn ($item) => $item['unitWeight'] * $item['quantity']);
            $order = Order::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'shipper_id' => $customer->default_shipper_id,
                'code' => 'ORD-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10)),
                'status' => Order::STATUS_ORDER_PLACED,
                'payment_status' => 'unpaid',
                'delivery_date' => $validated['delivery_date'] ?? now()->addDay()->toDateString(),
                'delivery_time' => $validated['delivery_time'] ?? $customer->delivery_time,
                'recipient_name' => $customer->name,
                'recipient_phone' => $customer->phone,
                'recipient_address' => $customer->address,
                'note' => $validated['note'] ?? null,
                'total' => $total,
                'subtotal_amount' => $total,
                'amount_due' => $total,
                'total_weight' => round($totalWeight, 3),
            ]);

            foreach ($items as $item) {
                $variant = $item['variant'];
                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'base_price' => $item['price'],
                    'unit_weight' => $item['unitWeight'],
                    'is_priced_by_kg' => $item['isPricedByKg'],
                    'total_weight' => round($item['unitWeight'] * $item['quantity'], 3),
                    'total' => $item['lineTotal'],
                ]);
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'create_order_mobile',
                'user_id' => $user->id,
                'role' => 'sale',
                'status_before' => null,
                'status_after' => $order->status,
                'note' => 'Sale tạo đơn từ mobile',
            ]);

            return $order;
        });

        return response()->json([
            'message' => 'Đã tạo đơn ' . ($order->code ?: ('#' . $order->id)),
            'order_id' => (int) $order->id,
        ], 201);
    }

    public function metrics(Request $request): JsonResponse
    {
        $user = $request->user();
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $ordersQuery = Order::query()->whereBetween('created_at', [$from, $to]);

        if (!$user->hasRole('admin')) {
            $ordersQuery->where('user_id', $user->id);
        }

        $orderCount = (clone $ordersQuery)->count();
        $revenueQuery = Schema::hasTable('accounting_reconciliations')
            ? DB::table('accounting_reconciliations')
                ->where('status', 'confirmed')
                ->whereBetween('confirmed_at', [$from, $to])
            : null;

        if ($revenueQuery && !$user->hasRole('admin')) {
            $revenueQuery->where('sale_id', $user->id);
        }

        $revenue = $revenueQuery ? (float) $revenueQuery->sum('recognized_revenue') : 0.0;
        $debt = (float) (clone $ordersQuery)->sum('amount_due');

        $commissionRules = 0;
        if (Schema::hasTable('accounting_customer_commissions')) {
            $commissionRulesQuery = DB::table('accounting_customer_commissions')->where('is_active', true);

            if (!$user->hasRole('admin')) {
                $customerIds = Customer::query()
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                            ->orWhere('user_id', $user->id);
                    })
                    ->pluck('id');

                if ($customerIds->isNotEmpty()) {
                    $commissionRulesQuery->whereIn('customer_id', $customerIds->all());
                } else {
                    $commissionRulesQuery->whereRaw('1=0');
                }
            }

            $commissionRules = (int) $commissionRulesQuery->count();
        }

        return response()->json([
            'data' => [
                'order_count_month' => $orderCount,
                'revenue_month' => $revenue,
                'debt_month' => $debt,
                'active_commission_rules' => $commissionRules,
            ],
        ]);
    }
}
