<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ProductVariant;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProductVariantPreference;
use App\Models\Warehouse;
use App\Support\ProductVariantSorter;
use App\Models\Setting;
use App\Services\ApprovalService;
use App\Services\CustomerPriorityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
 

class OrderController extends Controller
{
    private static array $tableColumnsCache = [];
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    private function tableColumns(string $table): array
    {
        if (!array_key_exists($table, self::$tableColumnsCache)) {
            self::$tableColumnsCache[$table] = Schema::getColumnListing($table);
        }

        return self::$tableColumnsCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tableColumns($table), true);
    }

    private function filterExistingColumns(string $table, array $data): array
    {
        $columns = array_flip($this->tableColumns($table));

        return array_filter(
            $data,
            static fn ($key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function resolveCustomerShippingFee(?Customer $customer): ?float
    {
        if (!$customer || $customer->shipping_fee === null) {
            return null;
        }

        return round((float) $customer->shipping_fee, 2);
    }

    private function hasAnyRole(User $user, array $roles): bool
    {
        $roleNames = $user->roles->pluck('name')->map(fn ($name) => strtolower((string) $name))->all();
        foreach ($roles as $role) {
            if (in_array(strtolower($role), $roleNames, true)) {
                return true;
            }
        }

        return false;
    }

    private function currentRoleLabel(?User $user = null): ?string
    {
        $actor = $user ?: auth()->user();
        if (!$actor) {
            return null;
        }

        return $actor->roles->pluck('name')->first();
    }

    private function resolveOrderDeliveryTime(?int $customerId, ?string $requestedDeliveryTime): ?string
    {
        $requested = trim((string) ($requestedDeliveryTime ?? ''));
        if ($requested !== '') {
            return $requested;
        }

        if (!$customerId) {
            return null;
        }

        return Customer::query()->where('id', $customerId)->value('delivery_time');
    }

    private function normalizeDiscountType(?string $type): string
    {
        return strtolower((string) $type) === 'increase' ? 'increase' : 'decrease';
    }

    private function resolveVariantKg(ProductVariant $variant): float
    {
        $variantKg = (float) ($variant->kg ?? 0);
        if ($variantKg > 0) {
            return $variantKg;
        }

        $productKg = (float) ($variant->product?->kg ?? 0);
        if ($productKg > 0) {
            return $productKg;
        }

        $sizeKg = $this->parseWeightToKg($variant->size);
        if ($sizeKg > 0) {
            return $sizeKg;
        }

        return 1.0;
    }

    private function resolveVariantPricedByKg(ProductVariant $variant): bool
    {
        if ($variant->is_priced_by_kg !== null) {
            return (bool) $variant->is_priced_by_kg;
        }

        return (bool) ($variant->product?->is_priced_by_kg ?? true);
    }

    private function logOrderHistory(Order $order, string $action, ?string $statusBefore, ?string $statusAfter, ?string $note = null, ?User $user = null): void
    {
        $actor = $user ?: auth()->user();

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => $actor?->id,
            'role' => $this->currentRoleLabel($actor),
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'note' => $note,
        ]);
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($this->hasAnyRole($user, ['admin'])) {
            return;
        }

        if ($this->hasAnyRole($user, ['sale'])) {
            $query->where('user_id', $user->id);
            return;
        }

        if ($this->hasAnyRole($user, ['leader_sale', 'leader', 'sale_manager'])) {
            $teamId = $user->team_id;
            if (!$teamId) {
                $query->where(function (Builder $sub) use ($user) {
                    $sub->where('status', 'pending_leader_approval')
                        ->orWhereHas('approvals', function (Builder $q) use ($user) {
                            $q->where('approved_by', $user->id);
                        });
                });
                return;
            }

            $query->where(function (Builder $sub) use ($user) {
                $sub->where('status', 'pending_leader_approval')
                    ->orWhereHas('approvals', function (Builder $q) use ($user) {
                        $q->where('approved_by', $user->id);
                    })
                    ->orWhereHas('user.roles', function (Builder $q) {
                        $q->where('name', 'sale');
                    });
            });

            $query->whereHas('user', function (Builder $q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
            return;
        }

        if ($this->hasAnyRole($user, ['manager_sale', 'manager', 'director'])) {
            $teamId = $user->team_id;
            if (!$teamId) {
                $query->where(function (Builder $sub) use ($user) {
                    $sub->whereIn('status', ['pending_manager_approval', 'approved', 'packing', 'packed', 'shipping', 'delivered', 'completed'])
                        ->orWhereHas('approvals', function (Builder $q) use ($user) {
                            $q->where('approved_by', $user->id);
                        });
                });
                return;
            }

            $query->where(function (Builder $sub) use ($user) {
                $sub->whereIn('status', ['pending_manager_approval', 'approved', 'packing', 'packed', 'shipping', 'delivered', 'completed'])
                    ->orWhereHas('approvals', function (Builder $q) use ($user) {
                        $q->where('approved_by', $user->id);
                    });
            });

            $query->whereHas('user', function (Builder $q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
            return;
        }

        if ($this->hasAnyRole($user, ['warehouse'])) {
            $query->whereIn('status', ['pending_warehouse_approval', 'approved', 'packing', 'packed']);
            return;
        }

        if ($this->hasAnyRole($user, ['shipper'])) {
            $query->whereIn('status', ['pending_shipper_approval', 'packed', 'shipping', 'delivered', 'completed', 'returned']);
            return;
        }

        $query->where('user_id', $user->id);
    }

    public function createNewOrderForm(Request $request)
    {
        $settings   = $this->settings;
        $variantId = $request->input('variant_id');
        
        if (!$variantId) {
            return redirect()->route('orders.index')->with('error', __('orders.messages.no_variant_id'));
        }

        
        $variant = ProductVariant::query()
            ->withAvailableStock()
            ->with(['product.avatar.media', 'mediaLink.media'])
            ->find($variantId);

        if (!$variant) {
            return redirect()->route('orders.index')->with('error', __('orders.messages.variant_not_found'));
        }

        if ($variant->available_stock < 1) {
            return redirect()->route('orders.index')->with('error', __('orders.runtime.insufficient_stock', [
                'sku' => $variant->sku,
                'available' => 0,
            ]));
        }

        $customerQuery = Customer::query();
        if (Schema::hasColumn('customers', 'is_pinned')) {
            $customerQuery->orderByDesc('is_pinned');
        }
        if (Schema::hasColumn('customers', 'sort_order')) {
            $customerQuery->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order');
        }
        $customers = $customerQuery->orderBy('name')->paginate(10);

        // IMPORTANT: Set the base path for the pagination links to our AJAX endpoint.
        $customers->setPath(route('orders.ajax_customer_search'));

        return view('orders.create_new', compact('variant', 'customers', 'settings' ));
    }

    public function ajaxCustomerSearch(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        if (Schema::hasColumn('customers', 'is_pinned')) {
            $query->orderByDesc('is_pinned');
        }
        if (Schema::hasColumn('customers', 'sort_order')) {
            $query->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order');
        }

        $customers = $query->orderBy('name')->paginate(10);

        return response()->json([
            'html' => view('orders._customer_list', compact('customers'))->render()
        ]);
    }

    public function ajaxVariantSearch(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        // Safeguard against excessively large values
        if ($perPage > 50) {
            $perPage = 50;
        }

        $query = ProductVariant::query()
            ->withAvailableStock()
            ->with(['product.avatar.media', 'latestPriceRule', 'mediaLink.media'])
            ->where('product_variants.status', true)
            ->whereHas('product', function ($productQuery) {
                $productQuery->where('products.status', true);
            });

        if (!$request->boolean('allow_backorder')) {
            $query->inStock();
        }

        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where(function ($searchQuery) use ($searchTerm) {
                $searchQuery->where('product_variants.sku', 'like', "%{$searchTerm}%")
                    ->orWhere('product_variants.name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Exclude variants that are already in the cart.
        $excludeIds = $request->input('exclude_ids', []);
        if (is_string($excludeIds)) {
            $excludeIds = array_filter(explode(',', $excludeIds));
        }
        if (is_array($excludeIds) && !empty($excludeIds)) {
            $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));
            if (!empty($excludeIds)) {
                $query->whereNotIn('product_variants.id', $excludeIds);
            }
        }

        $sortBy = (string) $request->input('sort_by', 'preferred');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        ProductVariantSorter::joinProductSort($query, auth()->id());
        ProductVariantSorter::applyUserPreferencePrefix($query, auth()->id());

        if ($sortBy === 'sku') {
            $query->orderBy('product_variants.sku', $sortDir)->orderBy('product_variants.id', 'desc');
        } elseif ($sortBy === 'stock') {
            $query->orderBy('available_stock', $sortDir)->orderBy('product_variants.id', 'desc');
        } else {
            ProductVariantSorter::applyAdminFallback($query)
                ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
                ->orderBy('product_variants.id');
        }

        $variants = $query->paginate($perPage);

        return response()->json([
            'html' => view('orders._variant_search_results', compact('variants'))->render()
        ]);
    }

    public function updateVariantPreference(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $preference = UserProductVariantPreference::query()->firstOrNew([
            'user_id' => (int) auth()->id(),
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
            'success' => true,
            'variant_id' => (int) $variant->id,
            'is_pinned' => (bool) $preference->is_pinned,
            'sort_order' => $preference->sort_order,
        ]);
    }

    public function storeANewOrder(Request $request, ApprovalService $approvalService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
            'delivery_time' => 'nullable|string|max:255',
            'item_discount' => 'nullable|array',
            'item_discount.*' => 'nullable|numeric|min:0',
            'item_discount_type' => 'nullable|array',
            'item_discount_type.*' => 'nullable|in:decrease,increase',
            'item_weight' => 'nullable|array',
            'item_weight.*' => 'nullable|numeric|min:0',
            'order_discount' => 'nullable|numeric|min:0',
            'order_discount_type' => 'nullable|in:decrease,increase',
            'warehouse_can_adjust' => 'nullable|boolean',
        ]);

        $customerId = (int) $request->input('customer_id');
        $orderDeliveryTime = $this->resolveOrderDeliveryTime(
            $customerId,
            $request->input('delivery_time')
        );

        $items = collect($request->input('items'))->map(function ($item) use ($request) {
            $variantId = (int) ($item['variant_id'] ?? 0);

            return [
                'variant_id' => $variantId,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'price' => isset($item['price']) ? (float) $item['price'] : null,
                'base_price' => isset($item['base_price']) ? (float) $item['base_price'] : null,
                'unit_discount' => (float) $request->input('item_discount.' . $variantId, 0),
                'unit_discount_type' => $this->normalizeDiscountType($request->input('item_discount_type.' . $variantId)),
                'unit_weight' => $request->input('item_weight.' . $variantId) !== null ? (float) $request->input('item_weight.' . $variantId) : null,
            ];
        })->values()->all();

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => $customerId,
                    'user_id' => auth()->id(),
                    'delivery_time' => $orderDeliveryTime,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                    'allow_backorder' => $request->boolean('allow_backorder'),
                    'order_discount' => max(0, (float) $request->input('order_discount', 0)),
                    'order_discount_type' => $this->normalizeDiscountType($request->input('order_discount_type')),
                    'warehouse_can_adjust' => $request->boolean('warehouse_can_adjust'),
                ],
                approvalService: $approvalService
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('site.orders.show', $order)->with('success', __('orders.messages.created'));
    }
    public function storeNewOrder(Request $request, ApprovalService $approvalService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
            'delivery_time' => 'nullable|string|max:255',
            'item_discount' => 'nullable|array',
            'item_discount.*' => 'nullable|numeric|min:0',
            'item_discount_type' => 'nullable|array',
            'item_discount_type.*' => 'nullable|in:decrease,increase',
            'item_weight' => 'nullable|array',
            'item_weight.*' => 'nullable|numeric|min:0',
            'order_discount' => 'nullable|numeric|min:0',
            'order_discount_type' => 'nullable|in:decrease,increase',
        ]);

        $customerId = (int) $request->input('customer_id');
        $orderDeliveryTime = $this->resolveOrderDeliveryTime(
            $customerId,
            $request->input('delivery_time')
        );

        $items = collect($request->input('items'))->map(function ($item) use ($request) {
            $variantId = (int) ($item['variant_id'] ?? 0);

            return [
                'variant_id' => $variantId,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'price' => isset($item['price']) ? (float) $item['price'] : null,
                'base_price' => isset($item['base_price']) ? (float) $item['base_price'] : null,
                'unit_discount' => (float) $request->input('item_discount.' . $variantId, 0),
                'unit_discount_type' => $this->normalizeDiscountType($request->input('item_discount_type.' . $variantId)),
                'unit_weight' => $request->input('item_weight.' . $variantId) !== null ? (float) $request->input('item_weight.' . $variantId) : null,
            ];
        })->values()->all();

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => $customerId,
                    'user_id' => auth()->id(),
                    'delivery_time' => $orderDeliveryTime,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                    'allow_backorder' => $request->boolean('allow_backorder'),
                    'order_discount' => max(0, (float) $request->input('order_discount', 0)),
                    'order_discount_type' => $this->normalizeDiscountType($request->input('order_discount_type')),
                ],
                approvalService: $approvalService
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('orders.show', $order)->with('success', __('orders.messages.created'));
    }

    public function storeFromCart(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', __('orders.messages.login_required'));
        }
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'recipient_address' => 'required|string|max:1000',
            'recipient_email' => 'nullable|email|max:255',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'delivery_time' => 'nullable|string|max:255',
            'item_discount' => 'nullable|array',
            'item_discount.*' => 'nullable|numeric|min:0',
            'item_discount_type' => 'nullable|array',
            'item_discount_type.*' => 'nullable|in:decrease,increase',
            'item_weight' => 'nullable|array',
            'item_weight.*' => 'nullable|numeric|min:0',
            'order_discount' => 'nullable|numeric|min:0',
            'order_discount_type' => 'nullable|in:decrease,increase',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', __('orders.messages.cart_empty'));
        }

        if (auth()->user()->hasRole('warehouse') && !auth()->user()->warehouse_id) {
            return redirect()->route('cart.show')->with('error', __('orders.messages.warehouse_unassigned'));
        }

        $authUser = auth()->user();
        $customer = null;

        $selectedCustomerId = (int) $request->input('customer_id', 0);
        if ($selectedCustomerId > 0) {
            $selectedCustomer = Customer::query()
                ->where('id', $selectedCustomerId)
                ->where(function ($q) use ($authUser) {
                    $q->where('user_id', $authUser->id)
                        ->orWhere('assigned_to', $authUser->id);
                })
                ->first();

            if ($selectedCustomer) {
                // If user selected a valid customer from their own list, always use it.
                $customer = $selectedCustomer;
            }
        }

        if (!$customer) {
            // user_id is unique on customers table, so fallback to one customer per user.
            $customer = Customer::firstOrNew(['user_id' => $authUser->id]);

            $incomingEmail = trim((string) ($request->input('recipient_email') ?: $authUser->email ?: ''));
            if ($incomingEmail !== '') {
                $emailTakenByOther = Customer::query()
                    ->where('email', $incomingEmail)
                    ->when($customer->exists, function ($q) use ($customer) {
                        $q->where('id', '!=', $customer->id);
                    })
                    ->exists();

                if (!$emailTakenByOther) {
                    $customer->email = $incomingEmail;
                }
            }

            $customer->user_id = $authUser->id;
            $customer->assigned_to = $authUser->id;
            $customer->name = $request->input('recipient_name');
            $customer->phone = $request->input('recipient_phone');
            $customer->address = $request->input('recipient_address');
            $customer->note = $request->input('note');
            if ($request->filled('delivery_time')) {
                $customer->delivery_time = $request->input('delivery_time');
            }
            $customer->save();
        }

        $orderDeliveryTime = $this->resolveOrderDeliveryTime(
            (int) $customer->id,
            $request->input('delivery_time')
        );

        $items = [];
        $variantDefaults = ProductVariant::query()
            ->whereIn('id', array_map('intval', array_keys($cart)))
            ->get(['id', 'size'])
            ->keyBy('id');

        foreach ($cart as $variantId => $details) {
            $basePrice = (float) ($details['price'] ?? 0);
            $unitDiscount = (float) $request->input('item_discount.' . $variantId, 0);
            $unitDiscount = max(0, $unitDiscount);
            $unitDiscountType = $this->normalizeDiscountType($request->input('item_discount_type.' . $variantId));

            $defaultWeight = (float) ($details['unit_weight'] ?? 0);
            if ($defaultWeight <= 0) {
                $defaultWeight = $this->parseWeightToKg($variantDefaults->get((int) $variantId)?->size);
            }

            $unitWeight = (float) $request->input('item_weight.' . $variantId, $defaultWeight);
            $unitWeight = max(0, round($unitWeight, 3));

            $items[] = [
                'variant_id' => (int) $variantId,
                'quantity' => (int) ($details['quantity'] ?? 0),
                'base_price' => $basePrice,
                'unit_discount' => $unitDiscount,
                'unit_discount_type' => $unitDiscountType,
                'price' => null,
                'unit_weight' => $unitWeight,
            ];
        }

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => $customer->id,
                    'user_id' => auth()->id() ?? null,
                    'recipient_name' => $request->recipient_name,
                    'recipient_phone' => $request->recipient_phone,
                    'recipient_address' => $request->recipient_address,
                    'delivery_time' => $orderDeliveryTime,
                    'note' => $request->note,
                    'order_discount' => (float) $request->input('order_discount', 0),
                    'order_discount_type' => $this->normalizeDiscountType($request->input('order_discount_type')),
                    'allow_backorder' => true,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                ],
                approvalService: $approvalService
            );
            
        } catch (\Throwable $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        session()->forget('cart');

        return redirect()->route('site.orders.show', $order->id)->with('success', __('orders.messages.created'));
        
    }

    public function updateDeliveryTime(Request $request, Order $order)
    {
        if (!$this->hasColumn('orders', 'delivery_time')) {
            return back()->with('error', 'Hệ thống chưa có cột giờ giao hàng cho đơn hàng.');
        }

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $this->hasAnyRole($user, ['admin']);
        $isSale = $this->hasAnyRole($user, ['sale']);
        $isLeaderOrManager = $this->hasAnyRole($user, ['leader_sale', 'leader', 'sale_manager', 'manager_sale', 'manager', 'director']);

        if (!$isAdmin && !$isLeaderOrManager && !($isSale && (int) $order->user_id === (int) $user->id)) {
            abort(403, 'Bạn không có quyền cập nhật giờ giao hàng cho đơn này.');
        }

        if (in_array((string) $order->status, ['completed', 'cancelled', 'returned', 'returned_completed'], true)) {
            return back()->with('error', 'Đơn hàng đã kết thúc, không thể chỉnh giờ giao.');
        }

        $request->validate([
            'delivery_time' => 'nullable|string|max:255',
        ]);

        $statusBefore = (string) $order->status;
        $order->update($this->filterExistingColumns('orders', [
            'delivery_time' => $request->input('delivery_time'),
        ]));

        $this->logOrderHistory(
            $order,
            'update_delivery_time',
            $statusBefore,
            $statusBefore,
            'Cap nhat gio giao hang: ' . ((string) ($request->input('delivery_time') ?: 'de trong'))
        );

        return back()->with('success', 'Đã cập nhật giờ giao hàng cho đơn.');
    }
    public function test(Request $request)
    {
        echo "oks";
    }
    public function index(Request $request)
    {
        $query = Order::with('customer', 'user', 'transactions', 'approvals.step');

        if (auth()->check()) {
            $this->applyRoleScope($query, auth()->user());
        }

        if ($request->boolean('my_pending_approval') && auth()->check()) {
            $roleNames = auth()->user()->roles()->pluck('name')->map(fn ($role) => strtolower((string) $role))->values();

            if ($roleNames->isNotEmpty()) {
                $query->whereExists(function ($sub) use ($roleNames) {
                    $sub->select(DB::raw(1))
                        ->from('approval_orders as ao')
                        ->join('approval_steps as aps', 'aps.id', '=', 'ao.approval_step_id')
                        ->whereColumn('ao.order_id', 'orders.id')
                        ->where('ao.status', 'pending')
                        ->whereIn(DB::raw('LOWER(aps.role_slug)'), $roleNames->toArray())
                        ->whereNotExists(function ($prev) {
                            $prev->select(DB::raw(1))
                                ->from('approval_orders as ao_prev')
                                ->join('approval_steps as aps_prev', 'aps_prev.id', '=', 'ao_prev.approval_step_id')
                                ->whereColumn('ao_prev.order_id', 'ao.order_id')
                                ->where('ao_prev.status', 'pending')
                                ->whereColumn('aps_prev.step_order', '<', 'aps.step_order');
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filtering
        if ($request->filled('customer_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('customer_name') . '%');
            });
        }

        if ($request->filled('phone_number')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->input('phone_number') . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function (Builder $sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }
        
        // Statistics
        $statsQuery = clone $query;
        $totalInvoiceAmount = $statsQuery->sum('total');
        $totalPaidAmount = $statsQuery->sum('amount_paid');
        $totalOutstandingAmount = $totalInvoiceAmount - $totalPaidAmount;
        $fullyPaidOrders = (clone $statsQuery)->where('payment_status', 'paid')->count();
        $unpaidOrders = (clone $statsQuery)->where('payment_status', 'unpaid')->count();
        $partiallyPaidOrders = (clone $statsQuery)->where('payment_status', 'partially_paid')->count();

        // Product stats aggregated by filter
        $productStats = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->whereIn('oi.order_id', (clone $statsQuery)->select('id'))
            ->select(
                'oi.product_id',
                'p.name as product_name',
                'p.unit as product_unit',
                DB::raw('SUM(oi.quantity) as total_qty'),
                DB::raw('SUM(oi.total) as total_amount')
            )
            ->groupBy('oi.product_id', 'p.name', 'p.unit')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($row) {
                $unit = \App\Enums\ProductUnit::tryFrom((string) $row->product_unit);
                $row->unit_label = $unit?->label() ?? \App\Enums\ProductUnit::CAI->label();
                return $row;
            });

        $orders = $query->latest()->paginate(15);

        $currentStepByOrder = [];
        $canApproveByOrder = [];
        $authUser = auth()->user();
        $authUser?->loadMissing('roles');

        foreach ($orders as $order) {
            $currentStep = $order->approvals
                ->where('status', 'pending')
                ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
                ->first();

            $currentStepByOrder[$order->id] = $currentStep;

            $canApprove = false;
            if ($authUser && $currentStep?->step) {
                $requiredRole = strtolower((string) $currentStep->step->role_slug);
                $canApprove = $authUser->roles->contains(
                    fn ($role) => strtolower((string) $role->name) === $requiredRole
                );
            }

            $canApproveByOrder[$order->id] = $canApprove;
        }

        $users = User::orderBy('name')->get();
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => __('orders.statuses.' . $case->value)];
        });

        return view('orders.index', compact(
            'orders',
            'users',
            'teams',
            'statusOptions',
            'totalInvoiceAmount',
            'totalPaidAmount',
            'totalOutstandingAmount',
            'fullyPaidOrders',
            'unpaidOrders',
            'partiallyPaidOrders',
            'currentStepByOrder',
            'canApproveByOrder',
            'productStats'
        ));
    }

    public function show(Order $order)
    {
        $settings   = $this->settings;
         $order->load('items.variant.product', 'customer', 'approvals.step', 'approvals.approver', 'transactions', 'histories.user');
        $order->load('items.variant.product', 'customer', 'approvals.step', 'approvals.approver', 'transactions', 'histories.user');

        $approvalService = app(ApprovalService::class);
        $currentPendingApproval = $approvalService->getCurrentPendingStep($order);
        $canApproveCurrentStep = auth()->check()
            ? $approvalService->canApproveCurrentStep($order, auth()->user())
            : false;

        $authUser = auth()->user();
        $canWarehouse = $authUser ? $this->hasAnyRole($authUser, ['warehouse', 'admin']) : false;
        $canShipper = $authUser ? $this->hasAnyRole($authUser, ['shipper', 'admin']) : false;

        $statusLabels = [
            'pending_leader_approval' => __('orders.statuses.pending_leader_approval'),
            'pending_manager_approval' => __('orders.statuses.pending_manager_approval'),
            'approved' => __('orders.statuses.approved'),
            'packing' => __('orders.statuses.packing'),
            'packed' => __('orders.statuses.packed'),
            'shipping' => __('orders.statuses.shipping'),
            'delivered' => __('orders.statuses.delivered'),
            'completed' => __('orders.statuses.completed'),
            'rejected' => __('orders.statuses.rejected'),
            'returned' => __('orders.statuses.returned'),
            'cancelled' => __('orders.statuses.cancelled'),
        ];

        return view('orders.show', compact('order', 'currentPendingApproval', 'canApproveCurrentStep', 'canWarehouse', 'canShipper', 'statusLabels','settings'));
    }

    public function edit(Order $order)
    {
        $order->load([
            'customer.assignedTo',
            'customer.currentOwner',
            'customer.defaultShipper',
            'items.product',
            'items.variant.product',
            'user.roles',
            'shipper',
            'warehouse',
            'returnWarehouse',
            'histories.user',
            'accountingReconciliation',
        ]);

        $authUser = auth()->user();
        $customerQuery = Customer::query()->orderBy('name');

        if ($authUser && !$this->hasAnyRole($authUser, ['admin', 'manager', 'leader'])) {
            $customerQuery->where(function ($query) use ($authUser) {
                $query->where('assigned_to', $authUser->id)
                    ->orWhere(function ($fallbackQuery) use ($authUser) {
                        $fallbackQuery->whereNull('assigned_to')
                            ->where('user_id', $authUser->id);
                    });
            });
        }

        $customers = $customerQuery->get();
        $users = User::orderBy('name')->get();
        $shippers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['shipper', 'manager_shipper']))
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => __('orders.statuses.' . $case->value)];
        });
        $paymentStatusOptions = collect(PaymentStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => __('orders.payment_statuses.' . $case->value)];
        });
        $deliveryStatusOptions = collect(DeliveryStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => __('orders.delivery_statuses.' . $case->value)];
        });

        return view('orders.edit', compact(
            'order',
            'customers',
            'users',
            'shippers',
            'warehouses',
            'statusOptions',
            'paymentStatusOptions',
            'deliveryStatusOptions'
        ));
    }

    public function update(Request $request, Order $order)
    {
        $authUser = auth()->user();
        $customerRule = Rule::exists('customers', 'id');

        if ($authUser && !$this->hasAnyRole($authUser, ['admin', 'manager', 'leader'])) {
            $customerRule = $customerRule->where(function ($query) use ($authUser) {
                $query->where('assigned_to', $authUser->id)
                    ->orWhere(function ($fallbackQuery) use ($authUser) {
                        $fallbackQuery->whereNull('assigned_to')
                            ->where('user_id', $authUser->id);
                    });
            });
        }

        $validated = $request->validate([
            'customer_id' => ['required', $customerRule],
            'user_id' => ['required', 'exists:users,id'],
            'shipper_id' => ['nullable', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'return_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
            'delivery_status' => ['nullable', Rule::in(array_column(DeliveryStatus::cases(), 'value'))],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_address' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'actual_weight' => ['nullable', 'numeric', 'min:0'],
            'charge_shipping_fee' => ['nullable', 'boolean'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'charge_foam_box_fee' => ['nullable', 'boolean'],
            'foam_box_price' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'return_reason' => ['nullable', 'string', 'max:500'],
            'shipper_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusBefore = (string) $order->status;

        $updates = [
            'customer_id' => (int) $validated['customer_id'],
            'user_id' => (int) $validated['user_id'],
            'shipper_id' => $validated['shipper_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'return_warehouse_id' => $validated['return_warehouse_id'] ?? null,
            'status' => $validated['status'] ?? $order->status,
            'payment_status' => $validated['payment_status'] ?? $order->payment_status,
            'delivery_status' => $validated['delivery_status'] ?? $order->delivery_status,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'recipient_phone' => $validated['recipient_phone'] ?? null,
            'recipient_email' => $validated['recipient_email'] ?? null,
            'recipient_address' => $validated['recipient_address'] ?? null,
            'note' => $validated['note'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'delivery_time' => $validated['delivery_time'] ?? null,
            'actual_weight' => $validated['actual_weight'] ?? null,
            'charge_shipping_fee' => $request->boolean('charge_shipping_fee'),
            'shipping_fee' => $validated['shipping_fee'] ?? null,
            'charge_foam_box_fee' => $request->boolean('charge_foam_box_fee'),
            'foam_box_price' => $validated['foam_box_price'] ?? null,
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'collected_amount' => $validated['collected_amount'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'return_reason' => $validated['return_reason'] ?? null,
            'shipper_note' => $validated['shipper_note'] ?? null,
        ];

        $order->forceFill($this->filterExistingColumns('orders', $updates))->save();

        $this->recalculateOrderTotals($order->fresh('items.variant.product'));
        $this->logOrderHistory($order->fresh(), 'update_order', $statusBefore, (string) $order->fresh()->status, 'Cap nhat thong tin don hang');

        return redirect()->route('orders.edit', $order)->with('success', __('orders.buttons.update'));
    }

    public function listVariant(Order $order)
    {
        $order->load('items.variant.product');
        $items = $order->items;
        $total = (float) $items->sum(function ($item) {
            return (float) ($item->total ?? ((float) $item->quantity * (float) $item->price));
        });

        return view('orders.list_variant', compact('items', 'total'));
    }

    public function addVariant(Request $request, Order $order)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
        ]);

        $variant = ProductVariant::with(['product', 'latestPriceRule'])->findOrFail((int) $validated['variant_id']);
        $existingItem = $order->items()->where('product_variant_id', $variant->id)->first();

        if ($existingItem) {
            $existingItem->increment('quantity');
            $pricingFactor = (bool) ($existingItem->is_priced_by_kg ?? true)
                ? max(0.01, (float) ($existingItem->unit_weight ?? 1))
                : 1;
            $existingItem->update([
                'total_weight' => round((float) ($existingItem->unit_weight ?? 0) * (int) $existingItem->quantity, 3),
                'total' => (float) $existingItem->price * (int) $existingItem->quantity * $pricingFactor,
            ]);
        } else {
            $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? $variant->product?->default_price ?? 0);
            $unitWeight = round(max(0.01, $this->resolveVariantKg($variant)), 3);
            $isPricedByKg = $this->resolveVariantPricedByKg($variant);
            $pricingFactor = $isPricedByKg ? $unitWeight : 1;

            $order->items()->create($this->filterExistingColumns('order_items', [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'price' => $price,
                'base_price' => $price,
                'unit_discount' => 0,
                'discount_type' => 'decrease',
                'discount_total' => 0,
                'unit_weight' => $unitWeight,
                'is_priced_by_kg' => $isPricedByKg,
                'total_weight' => round($unitWeight, 3),
                'total' => $price * $pricingFactor,
            ]));
        }

        $this->recalculateOrderTotals($order->fresh('items.variant.product'));

        return response()->noContent();
    }

    public function removeVariant(Request $request, Order $order)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
        ]);

        $item = $order->items()->where('product_variant_id', (int) $validated['variant_id'])->first();
        if ($item) {
            $item->delete();
            $this->recalculateOrderTotals($order->fresh('items.variant.product'));
        }

        return response()->noContent();
    }

    public function confirm(Order $order)
    {
        $this->assertValidTransition($order, ['pending'], 'confirmed');
        $order->update(['status' => 'confirmed']);

        return back()->with('success', __('orders.messages.confirmed'));
    }

    public function picking(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['warehouse', 'admin'])) {
            abort(403, __('orders.messages.forbidden_warehouse_picking'));
        }

        $this->assertValidTransition($order, ['approved'], 'packing');
        $statusBefore = (string) $order->status;
        $order->update(['status' => 'packing']);
        $this->logOrderHistory($order, 'warehouse_confirm_pack', $statusBefore, 'packing', 'Kho xac nhan bat dau dong hang');


        return back()->with('success', __('orders.messages.picking_started'));
    }

    public function completePacking(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['warehouse', 'admin'])) {
            abort(403, __('orders.messages.forbidden_warehouse_complete_packing'));
        }

        $request->validate([
            'packed_image' => 'required|image|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->assertValidTransition($order, ['packing'], 'packed');
        $statusBefore = (string) $order->status;
        $packedImagePath = $request->file('packed_image')->store('orders/packed', 'public');

        $order->update([
            'status' => 'packed',
            'packed_image_path' => $packedImagePath,
        ]);

        $this->logOrderHistory(
            $order,
            'warehouse_complete_packing',
            $statusBefore,
            'packed',
            $request->input('note')
        );

        return back()->with('success', __('orders.messages.packing_completed'));
    }

    public function pickup(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, __('orders.messages.forbidden_shipper_pickup'));
        }

        $this->assertValidTransition($order, ['packed'], 'shipping');
        $statusBefore = (string) $order->status;

        DB::transaction(function () use ($order) {
            $this->deductReservedStockForOrder($order);
            $order->update([
                'status' => 'shipping',
                'delivery_status' => DeliveryStatus::Shipping->value,
            ]);
        });

        $this->logOrderHistory($order, 'shipper_pickup', $statusBefore, 'shipping', 'Shipper da lay hang');

        return back()->with('success', __('orders.messages.pickup_confirmed'));
    }

    public function ship(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, __('orders.messages.forbidden_shipper_shipping'));
        }

        $this->assertValidTransition($order, ['packed'], 'shipping');
        $statusBefore = (string) $order->status;
        $order->update([
            'status' => 'shipping',
            'delivery_status' => DeliveryStatus::Shipping->value,
        ]);
        $this->logOrderHistory($order, 'shipper_start_shipping', $statusBefore, 'shipping', 'Shipper bat dau giao hang');

        return back()->with('success', __('orders.messages.shipping_started'));
    }

    public function markDelivered(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, __('orders.messages.forbidden_shipper_delivered'));
        }

        $request->validate([
            'delivered_image' => 'required|image|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->assertValidTransition($order, ['shipping'], 'delivered');
        $statusBefore = (string) $order->status;
        $deliveredImagePath = $request->file('delivered_image')->store('orders/delivered', 'public');

        $order->update([
            'status' => 'delivered',
            'delivery_status' => DeliveryStatus::Delivered->value,
            'delivered_image_path' => $deliveredImagePath,
        ]);

        $this->logOrderHistory($order, 'shipper_delivered', $statusBefore, 'delivered', $request->input('note'));

        return back()->with('success', __('orders.messages.delivered_confirmed'));
    }

    public function completePayment(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, __('orders.messages.forbidden_shipper_complete_payment'));
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'receipt_image' => 'required|image|max:5120',
            'delivery_image' => 'nullable|image|max:5120',
            'note' => 'nullable|string|max:255',
        ]);

        $this->assertValidTransition($order, ['delivered'], 'completed');

        $netPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
            - (float) $order->transactions()->where('type', 'refund')->sum('amount');
        $amount = (float) $request->input('amount');
        $totalAfterPayment = $netPaid + $amount;

        if ($totalAfterPayment + 0.0001 < (float) $order->total) {
            return back()->with('error', __('orders.messages.insufficient_payment_to_complete'));
        }

        $receiptImagePath = $request->file('receipt_image')->store('orders/receipts', 'public');
        $deliveryImagePath = $request->hasFile('delivery_image')
            ? $request->file('delivery_image')->store('orders/delivery-proof', 'public')
            : $order->delivered_image_path;

        $statusBefore = (string) $order->status;

        DB::transaction(function () use ($order, $amount, $request, $receiptImagePath, $deliveryImagePath) {
            Transaction::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'amount' => $amount,
                'type' => 'payment',
                'method' => 'shipper_collection',
                'note' => $request->input('note'),
                'receipt_image_path' => $receiptImagePath,
                'delivery_image_path' => $deliveryImagePath,
            ]);

            $netPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                - (float) $order->transactions()->where('type', 'refund')->sum('amount');

            $order->update([
                'status' => 'completed',
                'payment_status' => PaymentStatus::Paid->value,
                'amount_paid' => $netPaid,
                'amount_due' => max((float) $order->total - $netPaid, 0),
                'delivered_image_path' => $deliveryImagePath,
            ]);
        });

        $this->logOrderHistory($order->fresh(), 'shipper_complete_payment', $statusBefore, 'completed', $request->input('note'));

        return back()->with('success', __('orders.messages.payment_completed'));
    }

    public function refund(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, __('orders.messages.forbidden_shipper_refund'));
        }

        if (!in_array((string) $order->status, ['delivered', 'shipping'], true)) {
            return back()->with('error', __('orders.messages.refund_only_after_shipping_or_delivered'));
        }

        $statusBefore = (string) $order->status;
        $order->update(['has_return_order' => true]);
        $this->logOrderHistory($order, 'shipper_refund_request', $statusBefore, $statusBefore, 'Tao don hoan tra tu don goc');

        return redirect()->route('order-returns.create', ['order_id' => $order->id]);
    }

    public function complete(Order $order)
    {
        $this->assertValidTransition($order, ['shipping', 'delivered'], 'completed');
        $statusBefore = (string) $order->status;
        $order->update(['status' => 'completed']);
        $this->logOrderHistory($order, 'manual_complete', $statusBefore, 'completed', 'Cap nhat hoan tat thu cong');

        return back()->with('success', __('orders.messages.completed'));
    }

    public function cancel(Order $order)
    {
        $request = request();
        $user = auth()->user();

        if ($request->routeIs('site.orders.cancel')) {
            if (!$user) {
                abort(403);
            }

            $isAdmin = $this->hasAnyRole($user, ['admin']);
            $isOwner = (int) $order->user_id === (int) $user->id;

            if (!$isAdmin && !$isOwner) {
                abort(403);
            }

            if (!$order->created_at?->isToday()) {
                return back()->with('error', 'Chi duoc huy don duoc tao trong ngay.');
            }
        }

        $validated = $request->validate([
            'cancel_reason' => 'nullable|string|max:2000',
            'cancel_images' => 'nullable|array|max:5',
            'cancel_images.*' => 'image|max:5120',
        ]);

        $this->assertValidTransition($order, Order::CANCELLABLE_STATUSES, Order::STATUS_CANCELLED);
        $statusBefore = (string) $order->status;
        $reason = trim((string) ($validated['cancel_reason'] ?? ''));

        $imagePaths = [];
        foreach ($request->file('cancel_images', []) as $file) {
            $imagePaths[] = $file->store('orders/cancel', 'public');
        }

        DB::transaction(function () use ($order, $user, $reason, $imagePaths) {
            $this->releaseReservedStockForOrder($order);
            $order->update([
                'status' => 'cancelled',
                'cancelled_by' => $user?->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason !== '' ? $reason : null,
                'cancel_images' => $imagePaths ?: null,
            ]);
        });

        $this->logOrderHistory(
            $order,
            'cancel_order',
            $statusBefore,
            'cancelled',
            $reason !== '' ? ('Huy don hang: ' . $reason) : 'Huy don hang va giai phong booking'
        );

        return back()->with('success', __('orders.messages.cancelled_and_released'));
    }

    public function toggleStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'nullable|string',
        ]);

        $targetStatus = $request->input('status');
        if (!$targetStatus) {
            $targetStatus = match ($order->status) {
                'pending' => 'confirmed',
                'confirmed' => 'picking',
                'picking' => 'picked_up',
                'picked_up' => 'shipping',
                'shipping' => 'completed',
                default => $order->status,
            };
        }

        return match ($targetStatus) {
            'confirmed' => $this->confirm($order),
            'picking' => $this->picking($order),
            'picked_up' => $this->pickup($order),
            'shipping' => $this->ship($order),
            'completed' => $this->complete($order),
            'cancelled' => $this->cancel($order),
            default => back()->with('error', __('orders.messages.transition_not_supported')),
        };
    }

    private function getManagedWarehouseId(): ?int
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('warehouse')) {
            return null;
        }

        return $user->warehouse_id ? (int) $user->warehouse_id : null;
    }

    private function getAvailableStock(int $variantId, ?int $warehouseId): int
    {
        if ($warehouseId) {
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$inventory) {
                return 0;
            }

            return max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
        }

        return (int) Inventory::where('product_variant_id', $variantId)
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) as available_sum')
            ->value('available_sum');
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::where('product_variant_id', $variantId)->sum('quantity');
        ProductVariant::where('id', $variantId)->update(['stock' => $totalStock]);
    }

    public function createOrderFromSchedule(array $items, array $orderData, ApprovalService $approvalService): Order
    {
        return $this->createOrderWithUnifiedStockFlow($items, $orderData, $approvalService);
    }

    private function createOrderWithUnifiedStockFlow(array $items, array $orderData, ApprovalService $approvalService): Order
    {
        if (auth()->check() && auth()->user()->hasRole('warehouse') && !auth()->user()->warehouse_id) {
            throw new \RuntimeException(__('orders.runtime.warehouse_unassigned'));
        }

        $allowBackorder = (bool) ($orderData['allow_backorder'] ?? false);
        $actorUserId = (int) ($orderData['actor_user_id'] ?? auth()->id() ?? 0);
        $actorUser = $actorUserId > 0 ? User::query()->find($actorUserId) : null;

        return DB::transaction(function () use ($items, $orderData, $approvalService, $allowBackorder, $actorUserId, $actorUser) {
            $customer = null;
            $customerId = (int) ($orderData['customer_id'] ?? 0);
            if ($customerId > 0) {
                $customer = Customer::query()->find($customerId);
                if ($customer && $actorUserId > 0) {
                    app(CustomerPriorityService::class)->assertCanCreateOrder($customer, $actorUserId);
                }
            }

            $items = collect($items)
                ->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('orders.runtime.no_valid_items'));
            }

            $managedWarehouseId = $this->getManagedWarehouseId();
            $variantsData = [];
            $subtotalBeforeDiscount = 0;
            $subtotalAfterItemAdjustment = 0;
            $itemDiscountTotal = 0;
            $totalWeight = 0;

            foreach ($items as $item) {
                $variantId = (int) $item['variant_id'];
                $quantity = (int) $item['quantity'];

                $variant = ProductVariant::with('product')->lockForUpdate()->find($variantId);
                if (!$variant) {
                    throw new \RuntimeException(__('orders.runtime.product_not_found'));
                }

                $availableQty = $this->getAvailableStock($variantId, $managedWarehouseId);
                if (!$allowBackorder && $availableQty < $quantity) {
                    throw new \RuntimeException(__('orders.runtime.insufficient_stock', [
                        'sku' => $variant->sku,
                        'available' => $availableQty,
                    ]));
                }

                $basePrice = isset($item['base_price']) && $item['base_price'] !== null
                    ? (float) $item['base_price']
                    : (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);

                $unitDiscount = isset($item['unit_discount']) && $item['unit_discount'] !== null
                    ? (float) $item['unit_discount']
                    : 0;

                $unitDiscountType = $this->normalizeDiscountType($item['unit_discount_type'] ?? null);
                $minPrice = max(0, (float) ($variant->latestPriceRule?->min_price ?? 0));

                $unitDiscount = max(0, $unitDiscount);
                if ($unitDiscountType === 'decrease') {
                    $maxAllowedDecrease = max($basePrice - $minPrice, 0);
                    if ($unitDiscount > $maxAllowedDecrease) {
                        throw new \RuntimeException('Gia ban SKU ' . ($variant->sku ?: $variant->id) . ' khong duoc nho hon gia Min.');
                    }
                }

                $price = $unitDiscountType === 'increase'
                    ? ($basePrice + $unitDiscount)
                    : ($basePrice - $unitDiscount);

                $unitDiscount = max(0, $unitDiscount);

                $unitWeight = isset($item['unit_weight']) && (float) $item['unit_weight'] > 0
                    ? round((float) $item['unit_weight'], 3)
                    : round(max(0.01, $this->resolveVariantKg($variant)), 3);
                $isPricedByKg = $this->resolveVariantPricedByKg($variant);
                $pricingFactor = $isPricedByKg ? $unitWeight : 1;
                $lineDiscount = ($unitDiscountType === 'increase' ? -1 : 1) * $unitDiscount * $quantity * $pricingFactor;
                $lineWeight = $unitWeight * $quantity;

                $variantsData[] = [
                    'variant' => $variant,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'base_price' => $basePrice,
                    'unit_discount' => $unitDiscount,
                    'discount_type' => $unitDiscountType,
                    'discount_total' => $lineDiscount,
                    'unit_weight' => $unitWeight,
                    'is_priced_by_kg' => $isPricedByKg,
                    'total_weight' => $lineWeight,
                    'price' => $price,
                    'pricing_factor' => $pricingFactor,
                ];

                $subtotalBeforeDiscount += $basePrice * $quantity * $pricingFactor;
                $subtotalAfterItemAdjustment += $price * $quantity * $pricingFactor;
                $itemDiscountTotal += $lineDiscount;
                $totalWeight += $lineWeight;
            }

            $orderDiscountType = $this->normalizeDiscountType($orderData['order_discount_type'] ?? null);
            $orderLevelDiscountAmount = max(0, (float) ($orderData['order_discount'] ?? 0));
            if ($orderDiscountType === 'decrease') {
                $orderLevelDiscountAmount = min($orderLevelDiscountAmount, $subtotalAfterItemAdjustment);
            }
            $orderLevelDiscount = $orderDiscountType === 'increase'
                ? -1 * $orderLevelDiscountAmount
                : $orderLevelDiscountAmount;

            $customerShippingFee = $this->resolveCustomerShippingFee($customer);
            $shippingFee = array_key_exists('shipping_fee', $orderData)
                ? round(max(0, (float) $orderData['shipping_fee']), 2)
                : ($customerShippingFee ?? 0.0);
            $chargeShippingFee = array_key_exists('charge_shipping_fee', $orderData)
                ? (bool) $orderData['charge_shipping_fee']
                : true;
            if ($customerShippingFee !== null) {
                $chargeShippingFee = true;
            }

            $total = max($subtotalAfterItemAdjustment - $orderLevelDiscount + ($chargeShippingFee ? $shippingFee : 0), 0);
            $totalDiscount = $itemDiscountTotal + $orderLevelDiscount;

            $commissionPercentSnapshot = $customer
                ? (float) ($customer->commission_percent ?? 0)
                : 0.0;
            $commissionAmountSnapshot = round(($total * $commissionPercentSnapshot) / 100, 2);

            $orderInsert = $this->filterExistingColumns('orders', [
                'customer_id' => $orderData['customer_id'] ?? null,
                'user_id' => $orderData['user_id'] ?? auth()->id(),
                'shipper_id' => $orderData['shipper_id'] ?? $customer?->default_shipper_id,
                'warehouse_can_adjust' => (bool) ($orderData['warehouse_can_adjust'] ?? false),
                'recipient_name' => $orderData['recipient_name'] ?? null,
                'recipient_phone' => $orderData['recipient_phone'] ?? null,
                'recipient_address' => $orderData['recipient_address'] ?? null,
                'delivery_time' => $orderData['delivery_time'] ?? null,
                'delivery_date' => $orderData['delivery_date'] ?? now()->addDay()->toDateString(),
                'note' => $orderData['note'] ?? null,
                'status' => $orderData['status'] ?? OrderStatus::Pending->value,
                'payment_status' => $orderData['payment_status'] ?? PaymentStatus::Unpaid->value,
                'delivery_status' => $orderData['delivery_status'] ?? DeliveryStatus::NotShipped->value,
                'charge_shipping_fee' => $chargeShippingFee,
                'shipping_fee' => $chargeShippingFee ? $shippingFee : 0,
                'total' => $total,
                'commission_percent_snapshot' => $commissionPercentSnapshot,
                'commission_amount_snapshot' => $commissionAmountSnapshot,
                'subtotal_amount' => $subtotalBeforeDiscount,
                'item_discount_total' => $itemDiscountTotal,
                'extra_discount_total' => $orderLevelDiscount,
                'total_discount' => $totalDiscount,
                'order_discount' => $orderLevelDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'total_weight' => round($totalWeight, 3),
            ]);

            if ($this->hasColumn('orders', 'code')) {
                $orderInsert['code'] = 'ORD-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            }

            $order = new Order();
            $order->forceFill($orderInsert);
            $order->save();

            foreach ($variantsData as $info) {
                $itemInsert = $this->filterExistingColumns('order_items', [
                    'product_id' => $info['variant']->product_id,
                    'product_variant_id' => $info['variant_id'],
                    'quantity' => $info['quantity'],
                    'price' => $info['price'],
                    'base_price' => $info['base_price'],
                    'unit_discount' => $info['unit_discount'],
                    'discount_type' => $info['discount_type'],
                    'discount_total' => $info['discount_total'],
                    'unit_weight' => $info['unit_weight'],
                    'is_priced_by_kg' => $info['is_priced_by_kg'],
                    'total_weight' => round($info['total_weight'], 3),
                    'total' => $info['quantity'] * $info['price'] * $info['pricing_factor'],
                ]);

                $order->items()->create($itemInsert);
            }

            // OMS flow: create order => reserve stock only, not deduct on-hand yet.
            $this->reserveStockForOrder($order, $managedWarehouseId, $allowBackorder);

            $approvalService->initOrderApproval($order);

            $order->refresh();
            $this->logOrderHistory($order, 'create_order', null, (string) $order->status, 'Sale tao don hang', $actorUser);

            if ($order->shipper_id) {
                $this->logOrderHistory(
                    $order,
                    'shipper_auto_assigned',
                    (string) $order->status,
                    (string) $order->status,
                    'Tự động gán shipper cố định của khách hàng',
                    $actorUser
                );
            }

            if ($customer && $actorUserId > 0) {
                app(CustomerPriorityService::class)->onOrderCreated($customer, $order, $actorUserId);
            }

            // Cập nhật thứ tự ưu tiên trong ngày + trạng thái đủ/thiếu hàng cho toàn bộ đơn cùng ngày.
            $this->syncDailySequenceAndStockSufficiency($order->created_at ?: now());

            return $order;
        });
    }

    /**
     * Recompute FIFO queue by day to persist:
     * - orders.daily_sequence
     * - orders.stock_sufficient (1/0)
     * - orders.stock_shortage_detail (nullable json)
     */
    public function syncDailySequenceAndStockSufficiency(Carbon|string $date): void
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $queueStatuses = [
            'pending',
            'draft',
            'pending_leader_approval',
            'pending_manager_approval',
            'pending_warehouse_approval',
            Order::STATUS_ORDER_PLACED,
            'approved',
            Order::STATUS_READY_TO_PACK,
            Order::STATUS_PACKING,
            'packed',
            Order::STATUS_READY_TO_SHIP,
        ];

        $orders = Order::query()
            ->with(['items.variant.product', 'items.product'])
            ->whereDate('created_at', $dateString)
            ->whereIn('status', $queueStatuses)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $variantIds = $orders
            ->flatMap(fn (Order $order) => $order->items->pluck('product_variant_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($variantIds->isEmpty()) {
            return;
        }

        $orderItemIds = $orders
            ->flatMap(fn (Order $order) => $order->items->pluck('id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $availableByVariant = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity - reserved_quantity), 0) as available_qty')
            ->whereIn('product_variant_id', $variantIds->all())
            ->groupBy('product_variant_id')
            ->pluck('available_qty', 'product_variant_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        $todayReservedByVariant = [];
        if ($orderItemIds->isNotEmpty()) {
            $todayReservedByVariant = InventoryReservation::query()
                ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->whereIn('inventory_reservations.order_item_id', $orderItemIds->all())
                ->selectRaw('inventories.product_variant_id, COALESCE(SUM(inventory_reservations.quantity), 0) as qty')
                ->groupBy('inventories.product_variant_id')
                ->pluck('qty', 'inventories.product_variant_id')
                ->map(fn ($v) => (float) $v)
                ->all();
        }

        $remainingByVariant = [];
        foreach ($variantIds as $variantId) {
            $variantId = (int) $variantId;
            $remainingByVariant[$variantId] = max(
                0,
                (float) ($availableByVariant[$variantId] ?? 0) + (float) ($todayReservedByVariant[$variantId] ?? 0)
            );
        }

        $sequence = 0;
        foreach ($orders as $order) {
            $sequence++;
            $shortages = [];
            $pendingDeductions = [];

            foreach ($order->items as $item) {
                $variantId = (int) $item->product_variant_id;
                $requiredQty = (float) ($item->quantity ?? 0);

                if ($variantId <= 0 || $requiredQty <= 0) {
                    continue;
                }

                $availableQty = (float) ($remainingByVariant[$variantId] ?? 0);

                if ($availableQty < $requiredQty) {
                    $shortages[] = [
                        'order_id' => (int) $order->id,
                        'order_code' => (string) ($order->code ?? ('#' . $order->id)),
                        'order_item_id' => (int) $item->id,
                        'variant_id' => $variantId,
                        'variant_name' => (string) ($item->variant?->name ?? $item->product?->name ?? ('SP #' . $variantId)),
                        'required_qty' => $requiredQty,
                        'available_qty' => $availableQty,
                        'short_qty' => round($requiredQty - $availableQty, 3),
                    ];
                } else {
                    $pendingDeductions[$variantId] = ($pendingDeductions[$variantId] ?? 0) + $requiredQty;
                }
            }

            $isSufficient = empty($shortages);
            if ($isSufficient) {
                foreach ($pendingDeductions as $variantId => $consumeQty) {
                    $remainingByVariant[$variantId] = max(0, (float) ($remainingByVariant[$variantId] ?? 0) - (float) $consumeQty);
                }
            }

            $order->update($this->filterExistingColumns('orders', [
                'daily_sequence' => $sequence,
                'stock_sufficient' => $isSufficient ? 1 : 0,
                'stock_shortage_detail' => $isSufficient ? null : $shortages,
                'stock_alert_status' => $isSufficient ? 'ready' : 'waiting_stock',
            ]));
        }
    }

    private function parseWeightToKg(?string $value): float
    {
        if (!$value) {
            return 0.0;
        }

        if (!preg_match('/([0-9]+(?:[\.,][0-9]+)?)/', $value, $matches)) {
            return 0.0;
        }

        $weight = (float) str_replace(',', '.', $matches[1]);
        $normalized = mb_strtolower($value);

        if (str_contains($normalized, 'g') && !str_contains($normalized, 'kg')) {
            return round($weight / 1000, 3);
        }

        return round($weight, 3);
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->loadMissing('items');

        $subtotalAmount = (float) $order->items->sum(function ($item) {
            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;
            return (float) ($item->base_price ?? $item->price ?? 0) * (int) $item->quantity * $factor;
        });

        $itemDiscountTotal = (float) $order->items->sum(function ($item) {
            if ($item->discount_total !== null) {
                return (float) $item->discount_total;
            }

            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;
            $amount = (float) ($item->unit_discount ?? 0) * (int) $item->quantity * $factor;
            $type = strtolower((string) ($item->discount_type ?? 'decrease'));

            return $type === 'increase' ? -1 * $amount : $amount;
        });

        $subtotalAfterItemDiscount = (float) $order->items->sum(function ($item) {
            if ($item->total !== null) {
                return (float) $item->total;
            }

            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;
            return (float) $item->price * (int) $item->quantity * $factor;
        });

        $orderLevelDiscountAmount = (float) ($order->order_discount ?? 0);
        $orderLevelDiscountType = $this->normalizeDiscountType($order->order_discount_type ?? null);
        $orderLevelDiscount = $orderLevelDiscountType === 'increase'
            ? -1 * $orderLevelDiscountAmount
            : $orderLevelDiscountAmount;

        if ($orderLevelDiscountAmount <= 0 && $order->extra_discount_total !== null) {
            $orderLevelDiscount = (float) $order->extra_discount_total;
            $orderLevelDiscountType = $orderLevelDiscount < 0 ? 'increase' : 'decrease';
            $orderLevelDiscountAmount = abs($orderLevelDiscount);
        }

        $totalWeight = (float) $order->items->sum(function ($item) {
            return (float) ($item->total_weight ?? 0);
        });

        $order->update($this->filterExistingColumns('orders', [
            'subtotal_amount' => $subtotalAmount,
            'item_discount_total' => $itemDiscountTotal,
            'extra_discount_total' => $orderLevelDiscount,
            'order_discount' => $orderLevelDiscountAmount,
            'order_discount_type' => $orderLevelDiscountType,
            'total_discount' => $itemDiscountTotal + $orderLevelDiscount,
            'total_weight' => round($totalWeight, 3),
            'total' => max($subtotalAfterItemDiscount - $orderLevelDiscount, 0),
        ]));
    }

    private function reserveStockForOrder(Order $order, ?int $managedWarehouseId, bool $allowBackorder = false): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            $variantId = (int) $item->product_variant_id;
            $reserveQty = (int) $item->quantity;

            if ($managedWarehouseId) {
                $inventory = Inventory::lockForUpdate()
                    ->where('product_variant_id', $variantId)
                    ->where('warehouse_id', $managedWarehouseId)
                    ->first();

                if (!$inventory) {
                    if ($allowBackorder) {
                        continue;
                    }

                    throw new \RuntimeException(__('orders.runtime.no_inventory_for_booking', [
                        'sku' => $item->variant->sku ?? $variantId,
                    ]));
                }

                $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                if ($available < $reserveQty) {
                    if ($allowBackorder) {
                        $reserveQty = $available;
                    } else {
                        throw new \RuntimeException(__('orders.runtime.no_available_stock_for_booking', [
                            'sku' => $item->variant->sku ?? $variantId,
                        ]));
                    }
                }

                if ($reserveQty <= 0) {
                    continue;
                }

                $inventory->reserved_quantity += $reserveQty;
                $inventory->save();

                InventoryReservation::create([
                    'order_item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $reserveQty,
                ]);
            } else {
                $inventories = Inventory::lockForUpdate()
                    ->where('product_variant_id', $variantId)
                    ->orderByDesc('quantity')
                    ->get();

                if ($inventories->isEmpty()) {
                    if ($allowBackorder) {
                        continue;
                    }

                    throw new \RuntimeException(__('orders.runtime.no_available_stock_for_booking', [
                        'sku' => $item->variant->sku ?? $variantId,
                    ]));
                }

                $remaining = $reserveQty;
                foreach ($inventories as $inventory) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                    if ($available <= 0) {
                        continue;
                    }

                    $reservedNow = min($available, $remaining);
                    $inventory->reserved_quantity += $reservedNow;
                    $inventory->save();

                    InventoryReservation::create([
                        'order_item_id' => $item->id,
                        'inventory_id' => $inventory->id,
                        'quantity' => $reservedNow,
                    ]);

                    $remaining -= $reservedNow;
                }

                if (!$allowBackorder && $remaining > 0) {
                    throw new \RuntimeException(__('orders.runtime.no_available_stock_for_booking', [
                        'sku' => $item->variant->sku ?? $variantId,
                    ]));
                }
            }
        }
    }

    private function deductReservedStockForOrder(Order $order): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            $reservations = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();

            foreach ($reservations as $reservation) {
                $inventory = Inventory::lockForUpdate()->find($reservation->inventory_id);
                if (!$inventory) {
                    continue;
                }

                if ($inventory->quantity < $reservation->quantity || $inventory->reserved_quantity < $reservation->quantity) {
                    throw new \RuntimeException(__('orders.runtime.invalid_booking_data'));
                }

                $inventory->quantity -= $reservation->quantity;
                $inventory->reserved_quantity -= $reservation->quantity;
                $inventory->save();

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => -$reservation->quantity,
                    'type' => 'export',
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'user_id' => auth()->id(),
                ]);

                $this->syncVariantStockFromInventories((int) $inventory->product_variant_id);
            }

            InventoryReservation::where('order_item_id', $item->id)->delete();
        }
    }

    private function releaseReservedStockForOrder(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $reservations = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();
            foreach ($reservations as $reservation) {
                $inventory = Inventory::lockForUpdate()->find($reservation->inventory_id);
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, (int) $inventory->reserved_quantity - (int) $reservation->quantity);
                    $inventory->save();
                }
            }

            InventoryReservation::where('order_item_id', $item->id)->delete();
        }
    }

    private function assertValidTransition(Order $order, array $allowedCurrentStatuses, string $targetStatus): void
    {
        if (!in_array((string) $order->status, $allowedCurrentStatuses, true)) {
            abort(422, __('orders.messages.invalid_transition', ['from' => $order->status, 'to' => $targetStatus]));
        }
    }
}
