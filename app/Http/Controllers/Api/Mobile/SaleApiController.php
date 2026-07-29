<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MyDashboardController;
use App\Http\Controllers\Admin\TextOrderImportController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\Team;
use App\Models\TextOrderDraft;
use App\Models\TruckRoute;
use App\Models\TruckStation;
use App\Models\UserProductVariantPreference;
use App\Models\Ward;
use App\Services\ApprovalService;
use App\Services\CustomerPriorityService;
use App\Services\ZaloOrderTextParser;
use App\Support\ProductVariantSorter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SaleApiController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        $payload = app(MyDashboardController::class)->stats($request)->getData(true);
        $adjustments = collect($payload['pendingWarehouseAdjustments'] ?? [])
            ->map(function ($order) {
                $changes = collect($order['warehouse_adjustment_changes'] ?? [])
                    ->map(function ($change) {
                        $oldQty = (int) ($change['old_quantity'] ?? 0);
                        $newQty = (int) ($change['new_quantity'] ?? 0);
                        $delta = $newQty - $oldQty;
                        $changeLabel = $oldQty <= 0 && $newQty > 0
                            ? 'Thêm +' . $newQty
                            : ($newQty <= 0 && $oldQty > 0
                                ? 'Xóa -' . $oldQty
                                : ($delta > 0 ? 'Tăng +' . $delta : ($delta < 0 ? 'Giảm ' . $delta : 'Không đổi')));

                        return [
                            'product_name' => (string) ($change['product_name'] ?? 'Sản phẩm'),
                            'sku' => (string) ($change['sku'] ?? ''),
                            'size' => $change['size'] ?? null,
                            'old_quantity' => $oldQty,
                            'new_quantity' => $newQty,
                            'change_label' => $changeLabel,
                        ];
                    })
                    ->values();

                return [
                    'id' => (int) ($order['id'] ?? 0),
                    'code' => (string) ($order['code'] ?? ('#' . ($order['id'] ?? ''))),
                    'customer_name' => (string) data_get($order, 'customer.name', 'Khách hàng'),
                    'warehouse_name' => (string) data_get($order, 'warehouse.name', 'Kho'),
                    'note' => (string) ($order['warehouse_adjustment_note'] ?? ''),
                    'requested_at' => $order['warehouse_adjustment_requested_at'] ?? null,
                    'changes' => $changes,
                ];
            })
            ->values();

        $ordersQuery = Order::query()
            ->with(['user:id,name,team_id', 'customer:id,name,phone,address', 'items.product:id,name', 'items.variant:id,name,sku,size,product_id', 'approvals.step', 'histories.user:id,name'])
            ->when(Schema::hasColumn('orders', 'trash_at'), fn ($query) => $query->whereNull('trash_at'));
        $this->applySaleOrderVisibility($request, $ordersQuery);

        $todayOrders = (clone $ordersQuery)
            ->whereDate('created_at', today())
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => $this->orderPayload($order, true))
            ->values();

        $recentOrders = (clone $ordersQuery)
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => $this->orderPayload($order, true))
            ->values();

        return $this->ok([
            'stats' => $payload['dashboardStats'] ?? [],
            'pending_adjustments' => $adjustments,
            'today_orders' => $todayOrders,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function confirmWarehouseAdjustment(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        if ($order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            return $this->fail('Yêu cầu điều chỉnh không còn ở trạng thái chờ xác nhận.', 422);
        }
        if (collect($order->warehouse_adjustment_changes ?? [])->isEmpty()) {
            return $this->fail('Không có dữ liệu thay đổi để áp dụng.', 422);
        }

        app(MyDashboardController::class)->confirmWarehouseAdjustment($order);

        return $this->ok(null, 'Đã duyệt và áp dụng yêu cầu điều chỉnh từ kho.');
    }

    public function rejectWarehouseAdjustment(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        if ($order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            return $this->fail('Yêu cầu điều chỉnh không còn ở trạng thái chờ xác nhận.', 422);
        }

        app(MyDashboardController::class)->rejectWarehouseAdjustment($request, $order);

        return $this->ok(null, 'Đã từ chối yêu cầu điều chỉnh và thông báo cho kho.');
    }

    public function customers(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $userId = (int) $request->user()->id;
        $tab = in_array($request->query('tab'), ['all', 'processing', 'trash'], true)
            ? (string) $request->query('tab')
            : 'all';

        $query = Customer::query()
            ->where(function ($scope) use ($userId) {
                $scope->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhere('current_owner_sale_id', $userId)
                    ->orWhereHas('priorities', fn ($priority) => $priority->where('sale_id', $userId)->where('is_active', true));
            })
            ->where(fn ($scope) => $scope->where('is_employee', '<>', 1)->orWhereNull('is_employee'));

        if ($tab === 'trash') {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
            if ($tab === 'processing') {
                $query->whereIn('status', ['active', 'processing']);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($scope) => $scope
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }
        foreach (['city', 'ward', 'street'] as $field) {
            if ($request->filled($field)) {
                $value = (string) $request->query($field);
                $query->whereHas('addresses', fn ($address) => $address->where($field, $value));
            }
        }

        $sortBy = in_array($request->query('sort_by'), ['production', 'size', 'delivery_time', 'name', 'created_at'], true)
            ? (string) $request->query('sort_by')
            : 'id';
        $sortDir = strtolower((string) $request->query('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $customers = $query
            ->with(['addresses', 'currentOwner:id,name', 'truckStation:id,name', 'truckRoute:id,name'])
            ->withCount('orders')
            ->withSum('orders as total_debt', 'amount_due')
            ->orderBy($sortBy, $sortDir)
            ->paginate(min(50, max(10, (int) $request->query('per_page', 20))));

        $customers->getCollection()->transform(fn (Customer $customer) => $this->customerPayload($customer));

        return $this->salePaginated($customers, [
            'tab' => $tab,
            'tab_counts' => $this->customerTabCounts($userId),
        ]);
    }

    public function customer(Request $request, int $customerId): JsonResponse
    {
        $this->ensureSaleRole($request);
        $customer = Customer::withTrashed()
            ->with(['addresses', 'currentOwner:id,name', 'truckStation:id,name', 'truckRoute:id,name', 'orders.items.variant', 'careLogs', 'reminders'])
            ->findOrFail($customerId);
        $this->ensureManagedCustomer($request, $customer);

        return $this->ok($this->customerPayload($customer, true));
    }

    public function customerFormOptions(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);

        return $this->ok([
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'wards' => Ward::query()
                ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', (int) $request->query('province_id')))
                ->orderBy('name')->limit(1000)->get(['id', 'province_id', 'name']),
            'truck_stations' => TruckStation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'truck_routes' => TruckRoute::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function checkCustomerDuplicate(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        return app(PageController::class)->myCustomerCheckDuplicate($request);
    }

    public function storeCustomer(Request $request, CustomerPriorityService $priorityService): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $before = Customer::withTrashed()->max('id') ?? 0;
        $response = app(PageController::class)->myCustomerStore($request, $priorityService);
        $customer = Customer::withTrashed()->where('id', '>', $before)->latest('id')->first();

        return $this->webActionResult($response, 'Da them khach hang', $customer ? ['customer_id' => (int) $customer->id] : null);
    }

    public function updateCustomer(Request $request, Customer $customer): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $response = app(PageController::class)->myCustomerUpdate($request, $customer);

        return $this->webActionResult($response, 'Da cap nhat khach hang', ['customer_id' => (int) $customer->id]);
    }

    public function deleteCustomer(Request $request, Customer $customer): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        return $this->webActionResult(app(PageController::class)->myCustomerDestroy($customer), 'Da dua khach hang vao thung rac');
    }

    public function restoreCustomer(Request $request, int $customerId): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);

        return $this->webActionResult(app(PageController::class)->myCustomerRestore($customerId), 'Da khoi phuc khach hang');
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $userId = (int) $request->user()->id;
        $search = trim((string) $request->query('search', ''));
        $sortBy = (string) $request->query('sort_by', 'preferred');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $inStock = $request->boolean('in_stock');
        $variants = ProductVariant::query()
            ->with(['product:id,name,unit,is_priced_by_kg,sort_order', 'latestPriceRule'])
            ->withAvailableStock()
            ->when($search !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('product_variants.sku', 'like', "%{$search}%")
                ->orWhere('product_variants.name', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"))))
            ->when($inStock, fn ($query) => $query->having('available_stock', '>', 0));

        ProductVariantSorter::joinProductSort($variants, $userId);
        ProductVariantSorter::applyUserPreferencePrefix($variants, $userId);

        match ($sortBy) {
            'name' => $variants
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) {$sortDir}")
                ->orderBy('product_variants.id', 'desc'),
            'sku' => $variants->orderBy('product_variants.sku', $sortDir)->orderBy('product_variants.id', 'desc'),
            'newest' => $variants->orderByDesc('product_variants.id'),
            default => $variants
                ->when($sortBy === 'stock', fn ($query) => $query
                    ->orderByRaw('CASE WHEN available_stock > 0 THEN 0 ELSE 1 END')
                    ->orderBy('available_stock', $sortDir))
                ->orderByRaw('COALESCE(sort_products.sort_order, 0) ASC')
                ->orderByRaw('COALESCE(product_variants.sort_order, 0) ASC')
                ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
                ->orderBy('product_variants.id'),
        };

        $variants = $variants
            ->paginate(30);

        $variants->getCollection()->transform(fn ($variant) => [
            'id' => (int) $variant->id,
            'name' => (string) ($variant->name ?: $variant->product?->name ?: 'Sản phẩm'),
            'product_name' => (string) ($variant->product?->name ?? ''),
            'sku' => (string) ($variant->sku ?? ''),
            'size' => (string) ($variant->size ?? ''),
            'price' => (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0),
            'min_price' => (float) ($variant->latestPriceRule?->min_price ?? 0),
            'available_stock' => (int) ($variant->available_stock ?? 0),
            'is_priced_by_kg' => (bool) ($variant->effective_priced_by_kg ?? false),
            'kg' => (float) ($variant->effective_kg ?? 0),
            'is_pinned' => (bool) ($variant->is_pinned ?? false),
            'user_sort_order' => $variant->user_sort_order !== null ? (int) $variant->user_sort_order : null,
            'sort_order' => (int) ($variant->sort_order ?? 0),
            'product_sort_order' => (int) ($variant->product?->sort_order ?? 0),
        ]);

        return $this->paginated($variants);
    }

    public function productGroups(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $search = trim((string) $request->query('search', ''));
        $inStock = $request->boolean('in_stock', true);

        $availableVariants = fn ($query) => $query
            ->when($inStock, fn ($variantQuery) => $variantQuery->inStock());
        $matchingVariantSearch = fn ($query) => $query
            ->where(fn ($scope) => $scope
                ->where('product_variants.sku', 'like', "%{$search}%")
                ->orWhere('product_variants.name', 'like', "%{$search}%"))
            ->when($inStock, fn ($variantQuery) => $variantQuery->inStock());

        $products = Product::query()
            ->with([
                'avatar.media',
                'variants' => function ($query) use ($availableVariants) {
                    $availableVariants($query);
                    $query
                        ->withAvailableStock()
                        ->with(['latestPriceRule', 'avatar.media'])
                        ->orderByRaw('CAST(COALESCE(size, 0) AS DECIMAL(12, 3))')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->whereHas('variants', $availableVariants)
            ->when($search !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('products.name', 'like', "%{$search}%")
                ->orWhereHas('variants', $matchingVariantSearch)))
            ->orderByRaw('COALESCE(sort_order, 0) ASC')
            ->orderBy('name')
            ->paginate(min(50, max(10, (int) $request->query('per_page', 30))));

        $products->getCollection()->transform(function (Product $product) {
            $variants = $product->variants->map(function (ProductVariant $variant) use ($product) {
                $variantLabel = trim((string) ($variant->size ?: $variant->name ?: $variant->sku));

                return [
                    'id' => (int) $variant->id,
                    'name' => trim($product->name.' - '.$variantLabel, ' -'),
                    'variant_name' => (string) ($variant->name ?? ''),
                    'sku' => (string) ($variant->sku ?? ''),
                    'size' => (string) ($variant->size ?? ''),
                    'price' => (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0),
                    'min_price' => (float) ($variant->latestPriceRule?->min_price ?? 0),
                    'available_stock' => (int) ($variant->available_stock ?? 0),
                    'is_priced_by_kg' => (bool) ($variant->effective_priced_by_kg ?? false),
                    'kg' => (float) ($variant->effective_kg ?? 0),
                ];
            })->values();

            $imageUrl = $product->avatar?->media?->url
                ?? $product->variants->first(fn (ProductVariant $variant) => $variant->avatar?->media)?->avatar?->media?->url;

            return [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'unit' => (string) $product->unit_label,
                'image_url' => $imageUrl,
                'variant_count' => $variants->count(),
                'variants' => $variants,
            ];
        });

        return $this->paginated($products);
    }

    public function updateProductPreference(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->ensureSaleRole($request);
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

        return $this->ok([
            'variant_id' => (int) $variant->id,
            'is_pinned' => (bool) $preference->is_pinned,
            'sort_order' => $preference->sort_order,
        ], 'Đã cập nhật sắp xếp sản phẩm.');
    }

    public function draftOrders(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $drafts = TextOrderDraft::query()
            ->with(['customer:id,name,phone,address', 'sale:id,name', 'order:id,code'])
            ->where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE)
            ->where('sale_id', (int) $request->user()->id)
            ->latest()
            ->paginate(min(50, max(10, (int) $request->query('per_page', 20))));
        $drafts->getCollection()->transform(fn (TextOrderDraft $draft) => $this->draftPayload($draft));

        return $this->salePaginated($drafts);
    }

    public function parseDraftOrders(Request $request, ZaloOrderTextParser $parser): JsonResponse
    {
        $this->ensureSaleRole($request);
        $validated = $request->validate(['text' => ['required', 'string', 'max:200000']]);
        $saleId = (int) $request->user()->id;
        $parsed = $parser->parse($validated['text']);
        foreach ($parsed as $data) {
            TextOrderDraft::query()->create(array_merge($data, [
                'sale_id' => $saleId,
                'created_by' => $saleId,
                'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            ]));
        }

        return $this->ok(['count' => $parsed->count()], 'Đã tạo đơn nháp.');
    }

    public function confirmDraftOrder(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->bindWebAuth($request);
        return app(TextOrderImportController::class)->saleConfirm($request, $draft, $approvalService);
    }

    public function copyDraftOrder(Request $request, TextOrderDraft $draft): JsonResponse
    {
        $this->bindWebAuth($request);
        return app(TextOrderImportController::class)->saleCopy($request, $draft);
    }

    public function copyConfirmDraftOrder(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->bindWebAuth($request);
        return app(TextOrderImportController::class)->saleCopyConfirm($request, $draft, $approvalService);
    }

    public function deleteDraftOrder(Request $request, TextOrderDraft $draft): JsonResponse
    {
        $this->bindWebAuth($request);
        return app(TextOrderImportController::class)->saleDestroy($request, $draft);
    }

    public function storeOrder(Request $request, Customer $customer, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->ensureManagedCustomer($request, $customer);
        $this->bindWebAuth($request);
        $before = Order::query()->max('id') ?? 0;
        $response = app(PageController::class)->myCustomerOrderStore($request, $customer, $approvalService);
        $order = Order::query()->where('id', '>', $before)->where('user_id', $request->user()->id)->latest('id')->first();
        if (!$order) {
            return $this->fail('Khong the tao don hang. Vui long kiem tra ton kho va du lieu san pham.', 422);
        }

        return $this->webActionResult($response, 'Da tao don hang', ['order_id' => (int) $order->id]);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->ensureSaleRole($request);
        $trash = $request->boolean('trash');
        $query = Order::query()
            ->with(['user:id,name,team_id', 'customer:id,name,phone,address', 'items.product:id,name', 'items.variant:id,name,sku,size,product_id', 'approvals.step', 'histories.user:id,name']);
        $this->applySaleOrderVisibility($request, $query);
        if (Schema::hasColumn('orders', 'trash_at')) {
            $trash ? $query->whereNotNull('trash_at') : $query->whereNull('trash_at');
        }
        $this->applyOrderFilters($request, $query);

        $orders = $query->paginate(min(50, max(10, (int) $request->query('per_page', 20))));
        $orders->getCollection()->transform(fn (Order $order) => $this->orderPayload($order, true));

        return $this->salePaginated($orders, ['trash' => $trash]);
    }

    public function order(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $user = $request->user();
        $isOwner = (int) $order->user_id === (int) $user->id;
        $isLeaderInTeam = ($user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager'))
            && (int) $order->user?->team_id === (int) $user->team_id;
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director');
        if (!$isOwner && !$isLeaderInTeam && !$isManager && !$user->hasRole('admin')) {
            abort(403);
        }
        $order->load(['user:id,name,team_id', 'customer', 'items.product', 'items.variant', 'approvals.step', 'approvals.approver', 'histories.user']);

        return $this->ok($this->orderPayload($order, true));
    }

    public function updateOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $this->ensureEditableOrder($request, $order);

        return $this->webActionResult(app(PageController::class)->myOrderUpdate($request, $order), 'Da cap nhat don hang', ['order_id' => (int) $order->id]);
    }

    public function updateOrderCustomerFeedback(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $user = $request->user();
        if ((int) $order->user_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!Schema::hasColumn('orders', 'customer_feedback_status')) {
            return $this->fail('Chuc nang phan hoi khach hang chua duoc khoi tao.', 422);
        }

        if (!$order->canReceiveCustomerFeedback()) {
            return $this->fail('Chi nhap phan hoi cho don da hoan thanh, tra hang hoac tra mot phan.', 422);
        }

        if ($request->boolean('reset_feedback')) {
            return $this->fail('Phan hoi khach hang chi duoc tao mot lan, khong the reset.', 422);
        }

        if ($order->hasCustomerFeedback()) {
            return $this->fail('Don hang nay da co phan hoi khach hang.', 422);
        }

        $validated = $request->validate([
            'customer_feedback_status' => ['required', 'in:' . implode(',', array_keys(Order::customerFeedbackOptions()))],
            'customer_feedback_note' => ['required', 'string', 'max:2000'],
            'customer_feedback_sale_review' => ['nullable', 'string', 'max:2000'],
            'customer_feedback_images' => ['nullable', 'array', 'max:6'],
            'customer_feedback_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $feedbackImages = collect($order->customer_feedback_images ?? [])
            ->filter()
            ->values()
            ->all();

        foreach ($request->file('customer_feedback_images', []) as $image) {
            $feedbackImages[] = $image->store('customer-feedback', 'public');
        }

        $order->update([
            'customer_feedback_status' => $validated['customer_feedback_status'],
            'customer_feedback_note' => trim((string) $validated['customer_feedback_note']),
            'customer_feedback_sale_review' => trim((string) ($validated['customer_feedback_sale_review'] ?? '')),
            'customer_feedback_images' => $feedbackImages ?: null,
            'customer_feedback_by' => $user->id,
            'customer_feedback_at' => now(),
        ]);

        $order->refresh()->load(['customer:id,name,phone,address', 'items.product:id,name', 'items.variant:id,name,sku,size,product_id', 'approvals.step', 'histories.user:id,name']);

        return $this->ok($this->orderPayload($order, true), 'Da luu phan hoi khach hang');
    }

    public function copyOrder(Request $request, int $orderId): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $before = Order::query()->max('id') ?? 0;
        $response = app(PageController::class)->copyOrder($orderId);
        $copy = Order::query()->where('id', '>', $before)->where('user_id', $request->user()->id)->latest('id')->first();
        if (!$copy) {
            return $this->fail('Khong the copy don hang.', 422);
        }

        return $this->webActionResult($response, 'Da copy don hang', ['order_id' => (int) $copy->id]);
    }

    public function confirmCopy(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        if ((int) $order->user_id !== (int) $request->user()->id || empty($order->getAttribute('copied_from_order_id'))) {
            throw ValidationException::withMessages(['order' => 'Don hang khong du dieu kien xac nhan copy.']);
        }

        return $this->webActionResult(app(PageController::class)->confirmCopyOrder($order), 'Da xac nhan don copy');
    }

    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $user = $request->user();
        if (!$user->hasRole('admin') && (int) $order->user_id !== (int) $user->id) {
            abort(403);
        }
        if (!$order->created_at?->isToday()) {
            throw ValidationException::withMessages([
                'order' => 'Chi duoc huy don duoc tao trong ngay.',
            ]);
        }

        return $this->webActionResult(app(OrderController::class)->cancel($order), 'Da huy don hang');
    }

    public function trashOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        if (!$request->user()->hasRole('admin') && (int) $order->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if (!in_array((string) $order->status, [Order::STATUS_REJECTED, Order::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['order' => 'Chi duoc dua vao thung rac don bi tu choi hoac da huy.']);
        }

        return $this->webActionResult(app(PageController::class)->moveOrderToTrash($order), 'Da dua don hang vao thung rac');
    }

    public function approvals(Request $request, string $scope): JsonResponse
    {
        $this->ensureSaleRole($request);
        $user = $request->user();
        $isLeader = $this->ensureApprovalScope($request, $scope);

        $roles = $user->roles->pluck('name')->map(fn ($role) => strtolower((string) $role))->values();
        $query = $this->approvalQuery($request, $isLeader)
            ->with(['customer', 'user.team', 'items.product:id,name', 'items.variant:id,name,sku,size,product_id', 'approvals.step', 'approvals.approver', 'histories.user']);
        if (!$request->filled('from_date') && !$request->filled('to_date')) {
            $query->whereDate('created_at', today());
        }
        $this->applyOrderFilters($request, $query);
        $orders = $query->paginate(20);
        $orders->getCollection()->transform(function (Order $order) use ($roles) {
            $payload = $this->orderPayload($order, true);
            $current = $order->approvals->where('status', 'pending')->sortBy(fn ($approval) => $approval->step?->step_order ?? PHP_INT_MAX)->first();
            $payload['can_approve'] = $current?->step ? $roles->contains(strtolower((string) $current->step->role_slug)) : false;
            $payload['current_approval_step'] = $current?->step?->name ?? $current?->step?->role_slug;
            return $payload;
        });

        return $this->salePaginated($orders, ['teams' => Team::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function approveAll(Request $request, string $scope, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $user = $request->user();
        $isLeader = $this->ensureApprovalScope($request, $scope);
        $roles = $user->roles->pluck('name')->map(fn ($role) => strtolower((string) $role))->values();

        $query = $this->approvalQuery($request, $isLeader)
            ->with(['approvals.step'])
            ->whereExists(function ($sub) use ($roles) {
                $sub->select(DB::raw(1))
                    ->from('approval_orders as ao')
                    ->join('approval_steps as aps', 'aps.id', '=', 'ao.approval_step_id')
                    ->whereColumn('ao.order_id', 'orders.id')
                    ->where('ao.status', 'pending')
                    ->whereIn(DB::raw('LOWER(aps.role_slug)'), $roles->toArray())
                    ->whereNotExists(function ($prev) {
                        $prev->select(DB::raw(1))
                            ->from('approval_orders as ao_prev')
                            ->join('approval_steps as aps_prev', 'aps_prev.id', '=', 'ao_prev.approval_step_id')
                            ->whereColumn('ao_prev.order_id', 'ao.order_id')
                            ->where('ao_prev.status', 'pending')
                            ->whereColumn('aps_prev.step_order', '<', 'aps.step_order');
                    });
            });

        if (!$request->filled('from_date') && !$request->filled('to_date')) {
            $query->whereDate('created_at', today());
        }
        $this->applyOrderFilters($request, $query);

        $approved = 0;
        $failed = 0;
        $note = $request->input('note') ?: ($isLeader ? 'Leader duyệt tất cả từ mobile' : 'Manager duyệt tất cả từ mobile');
        foreach ($query->latest()->get() as $order) {
            $request->merge(['note' => $note]);
            $response = app(OrderApprovalController::class)->approve($request, $order, $approvalService);
            $payload = $response instanceof JsonResponse ? $response->getData(true) : [];
            if (($payload['success'] ?? false) === true) {
                $approved++;
            } else {
                $failed++;
            }
        }

        if ($approved === 0) {
            return $this->fail('Không có đơn nào đang tới lượt bạn duyệt.', 422, ['approved' => 0, 'failed' => $failed]);
        }

        return $this->ok([
            'approved' => $approved,
            'failed' => $failed,
        ], $failed > 0 ? "Đã duyệt {$approved} đơn, {$failed} đơn không thể duyệt." : "Đã duyệt tất cả {$approved} đơn.");
    }

    public function approve(Request $request, Order $order, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $response = app(OrderApprovalController::class)->approve($request, $order, $approvalService);

        return $this->webActionResult($response, 'Da duyet don hang');
    }

    public function reject(Request $request, Order $order, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleRole($request);
        $this->bindWebAuth($request);
        $response = app(OrderApprovalController::class)->reject($request, $order, $approvalService);

        return $this->webActionResult($response, 'Da tu choi don hang');
    }

    private function applyOrderFilters(Request $request, $query): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($scope) => $scope->where('code', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")));
        }
        foreach (['status', 'payment_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }
        if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->query('from_date'));
        if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->query('to_date'));
        $sortBy = in_array($request->query('sort_by'), ['code', 'total', 'status', 'payment_status', 'created_at'], true) ? $request->query('sort_by') : 'created_at';
        $query->orderBy($sortBy, strtolower((string) $request->query('sort_dir')) === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Keep the Sale workspace useful for every role that is allowed to open it.
     * A sale only sees their own orders, a leader sees their team, while sales
     * management and admins can monitor all orders.
     */
    private function applySaleOrderVisibility(Request $request, $query): void
    {
        $user = $request->user();

        if ($user->hasRole(['admin', 'manager', 'manager_sale', 'director'])) {
            return;
        }

        if ($user->hasRole(['leader', 'leader_sale', 'sale_manager'])) {
            $query->where(function ($scope) use ($user) {
                $scope->where('user_id', (int) $user->id);

                if ($user->team_id) {
                    $scope->orWhereHas('user', fn ($sale) => $sale->where('team_id', (int) $user->team_id));
                }
            });

            return;
        }

        $query->where('user_id', (int) $user->id);
    }

    private function ensureApprovalScope(Request $request, string $scope): bool
    {
        $user = $request->user();
        $isLeader = $scope === 'leader';
        if ($isLeader && !($user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('admin'))) {
            abort(403);
        }
        if (!$isLeader && !($user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director') || $user->hasRole('admin'))) {
            abort(403);
        }

        return $isLeader;
    }

    private function approvalQuery(Request $request, bool $isLeader)
    {
        $user = $request->user();
        $query = Order::query();
        if ($isLeader && !$user->hasRole('admin')) {
            $query->whereHas('user', fn ($sale) => $sale->where(fn ($owner) => $owner
                ->where(fn ($teamSale) => $teamSale
                    ->where('team_id', $user->team_id)
                    ->whereHas('roles', fn ($role) => $role->whereRaw('LOWER(name) = ?', ['sale'])))
                ->orWhere('id', $user->id)));
        }
        if (!$isLeader) {
            $query->whereHas('user.roles', fn ($role) => $role->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager']));
            if ($request->filled('team_id')) {
                $query->whereHas('user', fn ($sale) => $sale->where('team_id', (int) $request->query('team_id')));
            }
        }

        return $query;
    }

    private function customerPayload(Customer $customer, bool $details = false): array
    {
        $address = $customer->addresses->firstWhere('is_default', true) ?: $customer->addresses->first();
        $payload = [
            'id' => (int) $customer->id,
            'name' => (string) $customer->name,
            'phone' => (string) ($customer->phone ?? ''),
            'email' => (string) ($customer->email ?? ''),
            'address' => (string) ($address?->note ?: $customer->address ?: ''),
            'province_id' => $address?->province_id,
            'ward_id' => $address?->ward_id,
            'status' => (string) ($customer->status ?? ''),
            'customer_status' => (string) ($customer->customer_status ?? ''),
            'delivery_time' => (string) ($customer->delivery_time ?? ''),
            'size' => (string) ($customer->size ?? ''),
            'production' => (string) ($customer->production ?? ''),
            'company_name' => (string) ($customer->company_name ?? ''),
            'tax_code' => (string) ($customer->tax_code ?? ''),
            'company_address' => (string) ($customer->company_address ?? ''),
            'company_email' => (string) ($customer->company_email ?? ''),
            'use_truck_station' => (bool) ($customer->use_truck_station ?? false),
            'truck_station_id' => $customer->truck_station_id,
            'truck_route_id' => $customer->truck_route_id,
            'truck_station_address' => (string) ($customer->truck_station_address ?? ''),
            'truck_station_phone' => (string) ($customer->truck_station_phone ?? ''),
            'truck_receive_time' => (string) ($customer->truck_receive_time ?? ''),
            'truck_return_time' => (string) ($customer->truck_return_time ?? ''),
            'truck_fee' => (float) ($customer->truck_fee ?? 0),
            'orders_count' => (int) ($customer->orders_count ?? $customer->orders?->count() ?? 0),
            'total_debt' => (float) ($customer->total_debt ?? 0),
            'deleted_at' => optional($customer->deleted_at)->toIso8601String(),
            'updated_at' => optional($customer->updated_at)->toIso8601String(),
        ];
        if ($details) {
            $payload['orders'] = $customer->orders?->map(fn (Order $order) => $this->orderPayload($order))->values() ?? [];
            $payload['care_logs'] = $customer->careLogs ?? [];
            $payload['reminders'] = $customer->reminders ?? [];
        }
        return $payload;
    }

    private function orderPayload(Order $order, bool $details = false): array
    {
        $payload = [
            'id' => (int) $order->id,
            'code' => (string) ($order->code ?: '#' . $order->id),
            'sale_id' => (int) $order->user_id,
            'sale_name' => (string) ($order->user?->name ?? ''),
            'daily_sequence' => $order->daily_sequence ? (int) $order->daily_sequence : null,
            'status' => (string) $order->status,
            'payment_status' => (string) ($order->payment_status ?? ''),
            'total' => (float) ($order->total ?? 0),
            'amount_due' => (float) ($order->amount_due ?? 0),
            'trash_at' => optional($order->getAttribute('trash_at'))->toIso8601String(),
            'copied_from_order_id' => $order->getAttribute('copied_from_order_id'),
            'customer' => $order->customer,
            'delivery_date' => optional($order->delivery_date)->toDateString(),
            'created_at' => optional($order->created_at)->toIso8601String(),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
            'can_edit' => $this->isEditableOrder($order),
            'can_cancel' => $order->created_at?->isToday() === true
                && in_array((string) $order->status, ['pending_leader_approval', 'pending_manager_approval', 'approved', 'packing', 'pending', 'confirmed', 'picking', Order::STATUS_ORDER_PLACED], true),
            'can_trash' => in_array((string) $order->status, [Order::STATUS_REJECTED, Order::STATUS_CANCELLED], true)
                && empty($order->getAttribute('trash_at')),
            'has_customer_feedback' => $order->hasCustomerFeedback(),
            'can_customer_feedback' => $order->canReceiveCustomerFeedback() && !$order->hasCustomerFeedback(),
            'customer_feedback_status' => (string) ($order->customer_feedback_status ?? ''),
            'customer_feedback_note' => (string) ($order->customer_feedback_note ?? ''),
            'customer_feedback_sale_review' => (string) ($order->customer_feedback_sale_review ?? ''),
            'customer_feedback_images' => collect($order->customer_feedback_images ?? [])->map(fn ($path) => [
                'path' => (string) $path,
                'url' => asset('storage/' . ltrim((string) $path, '/')),
            ])->values(),
            'customer_feedback_meta' => Order::customerFeedbackMeta($order->customer_feedback_status),
            'customer_feedback_at' => optional($order->customer_feedback_at)->toIso8601String(),
        ];
        if ($details) {
            $payload['items'] = $order->items;
            $payload['approvals'] = $order->approvals;
            $payload['histories'] = $order->histories;
            $payload['recipient_name'] = (string) ($order->recipient_name ?? $order->customer?->name ?? '');
            $payload['recipient_phone'] = (string) ($order->recipient_phone ?? $order->customer?->phone ?? '');
            $payload['recipient_email'] = (string) ($order->recipient_email ?? $order->customer?->email ?? '');
            $payload['recipient_address'] = (string) ($order->recipient_address ?? $order->customer?->address ?? '');
            $payload['delivery_time'] = (string) ($order->delivery_time ?? '');
            $payload['note'] = (string) ($order->note ?? '');
            $payload['shipper_note'] = (string) ($order->shipper_note ?? '');
            $payload['order_discount'] = (float) ($order->order_discount ?? 0);
            $payload['order_discount_type'] = (string) ($order->order_discount_type ?? 'decrease');
        }
        return $payload;
    }

    private function draftPayload(TextOrderDraft $draft): array
    {
        $items = collect($draft->parsed_items ?: [[
            'product_text' => $draft->product_text,
            'quantity' => $draft->quantity,
            'size_kg' => $draft->size_kg,
            'unit_price' => $draft->unit_price,
        ]])->map(fn ($item) => [
            'name' => (string) ($item['product_text'] ?? 'Sản phẩm'),
            'size' => (string) ($item['size_kg'] ?? ''),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'size_kg' => (float) ($item['size_kg'] ?? 0),
            'price' => (float) ($item['unit_price'] ?? 0),
        ])->values();

        return [
            'id' => (int) $draft->id,
            'code' => 'Nháp #' . $draft->id,
            'status' => (string) $draft->status,
            'customer' => [
                'name' => (string) ($draft->customer_name ?: $draft->customer?->name ?: 'Khách hàng'),
                'phone' => (string) ($draft->phone ?: $draft->customer?->phone ?: ''),
                'address' => (string) ($draft->address ?: $draft->customer?->address ?: ''),
            ],
            'items' => $items,
            'total' => $items->sum(fn ($item) => $item['quantity'] * $item['price']),
            'delivery_date' => optional($draft->delivery_date)->toDateString(),
            'created_at' => optional($draft->created_at)->toIso8601String(),
            'note' => (string) ($draft->note ?? ''),
            'truck_brand_name' => (string) ($draft->truck_brand_name ?? ''),
            'truck_station_address' => (string) ($draft->truck_station_address ?? ''),
        ];
    }

    private function customerTabCounts(int $userId): array
    {
        $base = Customer::query()->where(fn ($scope) => $scope->where('user_id', $userId)->orWhere('assigned_to', $userId)->orWhere('current_owner_sale_id', $userId));
        return [
            'all' => (clone $base)->whereNull('deleted_at')->count(),
            'processing' => (clone $base)->whereNull('deleted_at')->whereIn('status', ['active', 'processing'])->count(),
            'trash' => (clone $base)->onlyTrashed()->count(),
        ];
    }

    private function ensureManagedCustomer(Request $request, Customer $customer): void
    {
        $user = $request->user();
        $isPrioritySale = $customer->priorities()
            ->where('sale_id', (int) $user->id)
            ->where('is_active', true)
            ->exists();
        if (!$user->hasRole('admin')
            && !$isPrioritySale
            && !in_array((int) $user->id, [(int) $customer->user_id, (int) $customer->assigned_to, (int) $customer->current_owner_sale_id], true)) {
            abort(403, 'Ban khong co quyen thao tac khach hang nay.');
        }
    }

    private function ensureEditableOrder(Request $request, Order $order): void
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if (!$this->isEditableOrder($order)) {
            throw ValidationException::withMessages(['order' => 'Don hang khong con du dieu kien de sua.']);
        }
    }

    private function isEditableOrder(Order $order): bool
    {
        return !empty($order->getAttribute('copied_from_order_id'))
            || ((string) $order->status === Order::STATUS_PENDING_LEADER_APPROVAL && $order->created_at?->isToday());
    }

    private function ensureSaleRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->roles->pluck('name')->map(fn ($role) => strtolower((string) $role))->intersect(['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale', 'director', 'admin'])->isNotEmpty()) {
            abort(403, 'Role khong duoc phep truy cap Sale mobile.');
        }
    }

    private function bindWebAuth(Request $request): void
    {
        $user = $request->user();
        Auth::setUser($user);
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    }

    private function webActionResult($response, string $fallback, ?array $data = null): JsonResponse
    {
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (($payload['success'] ?? true) === false) {
                return $this->fail((string) ($payload['message'] ?? $fallback), $response->getStatusCode(), $payload);
            }
            return $this->ok($data ?? $payload, (string) ($payload['message'] ?? $fallback));
        }

        return $this->ok($data, $fallback);
    }

    private function salePaginated($paginator, array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $paginator->items(),
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $extraMeta),
        ]);
    }
}
