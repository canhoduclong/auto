<?php
namespace App\Http\Controllers;


use App\Models\Contact;
use App\Models\Setting;
use App\Models\Page;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\UserProductVariantPreference;
use App\Support\ProductVariantSorter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerCareLog;
use App\Models\CustomerReminder;
use App\Models\Team;
use App\Services\OrderService;
use App\Services\ApprovalService;
use App\Services\OrderAutoApprovalService;
use App\Models\Order;
use App\Models\OrderAutoApprovalRule;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\Transaction;
use App\Services\AdminActivityService;
use App\Services\CustomerPriorityService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Province;
use App\Models\District;
use App\Models\TruckBrand;
use App\Models\TruckStation;
use App\Models\Ward;
use App\Models\Company;
use App\Models\User;

class PageController extends Controller
{
    protected $settings;
    private static ?array $orderColumnsCache = null;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }

    private function orderColumns(): array
    {
        if (self::$orderColumnsCache === null) {
            self::$orderColumnsCache = Schema::getColumnListing('orders');
        }

        return self::$orderColumnsCache;
    }

    private function hasOrderColumn(string $column): bool
    {
        return in_array($column, $this->orderColumns(), true);
    }

    private function applyCustomerPinnedSort(Builder $query): Builder
    {
        if (Schema::hasColumn('customers', 'is_pinned')) {
            $query->orderByDesc('is_pinned');
        }

        if (Schema::hasColumn('customers', 'sort_order')) {
            $query->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order');
        }

        return $query;
    }

    private function myOrderCustomersBaseQuery(int $userId): Builder
    {
        return Customer::query()->whereIn('id', function ($q) use ($userId) {
            $q->select('customer_id')
                ->from('orders')
                ->where('user_id', $userId)
                ->whereNotNull('customer_id');
        });
    }

    private function myAssignedCustomersQuery(int $userId): Builder
    {
        return Customer::query()
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                    ->orWhere(function ($fallbackQuery) use ($userId) {
                        $fallbackQuery->where(function ($emptyAssignQuery) {
                            $emptyAssignQuery->whereNull('assigned_to')
                                ->orWhere('assigned_to', 0);
                        })->where('user_id', $userId);
                    });
            })
                    ->where(function ($q) {
                    $q->whereNull('is_employee')
                        ->orWhere('is_employee', false)
                        ->orWhere('is_employee', 0);
                    })
            ->whereNull('deleted_at');
    }

    private function canAccessSalesDailyPages($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->canAccessSalesDailyFeatures();
    }

    private function discountConfigFromRequest(array $validated): array
    {
        return [
            'freeship_20_amount' => (float) ($validated['freeship_20_amount'] ?? 0),
            'tier_amounts' => [
                30 => (float) ($validated['discount_30_amount'] ?? 0),
                40 => (float) ($validated['discount_40_amount'] ?? 0),
                50 => (float) ($validated['discount_50_amount'] ?? 0),
                70 => (float) ($validated['discount_70_amount'] ?? 0),
                80 => (float) ($validated['discount_80_amount'] ?? 0),
                100 => (float) ($validated['discount_100_amount'] ?? 0),
            ],
            'use_special_customer_discount' => (bool) ($validated['use_special_customer_discount'] ?? false),
            'special_customer_discount_amount' => (float) ($validated['special_customer_discount_amount'] ?? 0),
        ];
    }

    private function isSpecialCustomer(?Customer $customer): bool
    {
        if (!$customer) {
            return false;
        }

        $typeName = mb_strtolower((string) optional($customer->type)->name);
        $note = mb_strtolower((string) $customer->note);

        return str_contains($typeName, 'dac biet')
            || str_contains($typeName, 'đặc biệt')
            || str_contains($typeName, 'special')
            || str_contains($note, 'dac biet')
            || str_contains($note, 'đặc biệt')
            || str_contains($note, 'special');
    }

    private function applyAutoApproveDiscount(Order $order, array $config): array
    {
        $totalItemQty = (int) $order->items->sum('quantity');

        $freeshipDiscount = $totalItemQty >= 20 ? max(0, (float) ($config['freeship_20_amount'] ?? 0)) : 0;

        $tierDiscount = 0;
        $tierLabel = null;
        foreach ([100, 80, 70, 50, 40, 30] as $tier) {
            $amount = max(0, (float) (($config['tier_amounts'][$tier] ?? 0)));
            if ($totalItemQty >= $tier && $amount > 0) {
                $tierDiscount = $amount;
                $tierLabel = $tier;
                break;
            }
        }

        $specialDiscount = 0;
        if (($config['use_special_customer_discount'] ?? false) && $this->isSpecialCustomer($order->customer)) {
            $specialDiscount = max(0, (float) ($config['special_customer_discount_amount'] ?? 0));
        }

        $totalDiscount = $freeshipDiscount + $tierDiscount + $specialDiscount;

        if ($totalDiscount > 0) {
            $currentTotal = (float) $order->total;
            $newTotal = max($currentTotal - $totalDiscount, 0);

            $updates = [];
            if ($this->hasOrderColumn('total')) {
                $updates['total'] = $newTotal;
            }

            if ($this->hasOrderColumn('amount_due')) {
                $paid = $this->hasOrderColumn('amount_paid') ? (float) ($order->amount_paid ?? 0) : 0;
                $updates['amount_due'] = max($newTotal - $paid, 0);
            }

            if (!empty($updates)) {
                $order->update($updates);
                $order->refresh();
            }
        }

        return [
            'item_qty' => $totalItemQty,
            'freeship_discount' => $freeshipDiscount,
            'tier_discount' => $tierDiscount,
            'tier_label' => $tierLabel,
            'special_discount' => $specialDiscount,
            'total_discount' => $totalDiscount,
        ];
    }

    private function discountNoteFromResult(array $discount): string
    {
        if (($discount['total_discount'] ?? 0) <= 0) {
            return 'Khong ap dung discount.';
        }

        $parts = [];
        if (($discount['freeship_discount'] ?? 0) > 0) {
            $parts[] = 'Freeship(>=20): ' . number_format((float) $discount['freeship_discount'], 0, ',', '.') . 'd';
        }
        if (($discount['tier_discount'] ?? 0) > 0) {
            $tier = $discount['tier_label'] ?? '?';
            $parts[] = 'Discount mốc ' . $tier . ': ' . number_format((float) $discount['tier_discount'], 0, ',', '.') . 'd';
        }
        if (($discount['special_discount'] ?? 0) > 0) {
            $parts[] = 'Khach dac biet: ' . number_format((float) $discount['special_discount'], 0, ',', '.') . 'd';
        }
        $parts[] = 'Tong giam: ' . number_format((float) $discount['total_discount'], 0, ',', '.') . 'd';

        return implode(' | ', $parts);
    }

    public function about()
    {
        $pages = Page::search("gioi-thieu")->get();

        return view('pages.about', [
            'settings' => $this->settings,
            'pages' => $pages
        ]);
    }

    public function contact()
    {
        return view('pages.contact', [
            'settings' => $this->settings
        ]);
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        Contact::create($request->all());

        return redirect()->back()->with('success', 'Your message has been sent successfully!');
    }

    public function index()
    {
        $pages = Page::all();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        Page::create($request->all());

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }
    
    public function show(Request $request)
    {
        $page = Page::where('slug', $request->slug)->firstOrFail();

        return view('pages.show', [
            'page' => $page,
            'settings' => $this->settings
        ]);
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:pages,slug,'.$page->id,
            'content' => 'required',
        ]);

        $page->update($request->all());

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function productsByCategory(Request $request, Category $category = null)
    {
        $categories = Category::query()
            ->withCount(['products' => fn($q) => $q->where('status', true)])
            ->orderBy('name')
            ->get();

        $query = ProductVariant::query()
            ->withAvailableStock()
            ->inStock()
            ->where('status', true)
            ->whereHas('product', fn($q) => $q->where('status', true));

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('min_price')) {
            $query->whereHas('latestPriceRule', fn($q) => $q->where('price', '>=', $request->min_price));
        }

        if ($request->filled('max_price')) {
            $query->whereHas('latestPriceRule', fn($q) => $q->where('price', '<=', $request->max_price));
        }

        if ($category) {
            $query->whereHas('product', fn($q) =>
                $q->where('category_id', $category->id)->where('status', true)
            );
        }

        $variants = $query->with('product.avatar.media', 'product.gallery.media', 'latestPriceRule')->paginate(10);

        return view('site.products_by_category', [
            'variants' => $variants,
            'settings' => $this->settings,
            'categories' => $categories,
            'category' => $category
        ]);
    }

    public function productList(Request $request, Category $category = null)
    {
        $categories = Category::query()
            ->withCount(['products' => fn($q) => $q->where('status', true)])
            ->orderBy('name')
            ->get();

        $query = Product::where('status', true);

        if ($category) {
            $query->where('category_id', $category->id);
        }

        $products = $query
            ->with([
                'category',
                'avatar.media',
                'variants' => fn ($variantQuery) => $variantQuery
                    ->withAvailableStock()
                    ->where('status', true)
                    ->with(['values.attribute', 'latestPriceRule'])
                    ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                    ->orderBy('sort_order')
                    ->orderBy('size')
                    ->orderBy('id'),
            ])
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        return view('site.product_list', [
            'products' => $products,
            'settings' => $this->settings,
            'categories' => $categories,
            'category' => $category
        ]);
    }

    public function productDetail(Product $product)
    {
        $product->load([
            'category',
            'avatar.media',
            'brand',
            'gallery.media', 
            'variants' => fn ($variantQuery) => $variantQuery
                ->withAvailableStock()
                ->where('status', true)
                ->with(['values.attribute', 'mediaLink.media', 'latestPriceRule', 'inventories'])
                ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order')
                ->orderBy('size')
                ->orderBy('id'),
        ]);

        $product->variants->each(function ($variant) {
            $variant->setAttribute('available_stock', $variant->available_stock);
        });

        $attributes = $product->variants
            ->flatMap(fn($variant) => $variant->values)
            ->unique('id')
            ->groupBy('attribute.name');

        $relatedProducts = Product::query()
            ->where('status', true)
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with([
                'category',
                'avatar.media',
                'variants' => fn ($variantQuery) => $variantQuery
                    ->withAvailableStock()
                    ->where('status', true)
                    ->with('latestPriceRule')
                    ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                    ->orderBy('sort_order')
                    ->orderBy('size')
                    ->orderBy('id'),
            ])
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('site.product_detail', [
            'product' => $product,
            'settings' => $this->settings,
            'attributes' => $attributes,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function myDashboard()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // customers.user_id is unique, so always resolve profile by user_id first.
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            $customerByEmail = null;
            if (!empty($user->email)) {
                $customerByEmail = Customer::where('email', $user->email)->first();
            }

            if ($customerByEmail && (empty($customerByEmail->user_id) || (int) $customerByEmail->user_id === (int) $user->id)) {
                $customerByEmail->user_id = $user->id;
                $customerByEmail->name = $customerByEmail->name ?: $user->name;
                $customerByEmail->assigned_to = $customerByEmail->assigned_to ?: $user->id;
                $customerByEmail->save();
                $customer = $customerByEmail;
            } else {
                $email = null;
                if (!empty($user->email)) {
                    $emailUsed = Customer::where('email', $user->email)
                        ->where('user_id', '!=', $user->id)
                        ->exists();
                    $email = $emailUsed ? null : $user->email;
                }

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'assigned_to' => $user->id,
                    'name' => $user->name,
                    'email' => $email,
                ]);
            }
        }

        return view('site.my_dashboard', [
            'settings' => $this->settings,
            'user' => $user,
            'customer' => $customer
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $customer = $user->customer;

        $request->validate([
            'name' => 'required|string|max:255', 
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'note' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $payload = $request->only(['name', 'email', 'phone', 'dob', 'gender', 'note']);

        $customer?->update($payload);

        $user->name = $request->input('name');
        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }
        $user->save();

        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $user->update(['avatar' => 'avatars/' . $avatarName]);
        }

        return redirect()->route('pages.my_profile')->with('success', 'Profile updated successfully.');
    }

    public function variantDetail(ProductVariant $variant)
    {
        $variant->load('avatar.media', 'product.category', 'inventories');
        $variant->setAttribute('available_stock', $variant->available_stock);

        $product = $variant->product;
        $product->load('avatar.media', 'gallery.media');

        $other_variants = ProductVariant::query()
            ->withAvailableStock()
            ->inStock()
            ->where('id', '!=', $variant->id)
            ->whereHas('product', fn($q) => $q->where('category_id', $product->category_id))
            ->with('product', 'avatar.media', 'latestPriceRule')
            ->inRandomOrder()
            ->take(6)
            ->get();

        $categories = Category::withCount('products')->get();

        return view('site.variant_detail', [
            'variant' => $variant,
            'product' => $product,
            'other_variants' => $other_variants,
            'settings' => $this->settings,
            'categories' => $categories
        ]);
    }

    public function myOrders(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem đơn hàng của mình.');
        }

        $user = auth()->user();

        $query = Order::with([
            'customer.addresses',
            'customer.truckStation.province',
            'customer.truckStation.ward',
            'user',
            'customerFeedbackUser:id,name',
            'items.product.avatar.media',
            'items.variant.avatar.media',
            'items.variant.product',
        ])->where('user_id', $user->id);

        $isTrashView = $request->input('trash') === '1';
        if ($this->hasOrderColumn('trash_at')) {
            if ($isTrashView) {
                $query->whereNotNull('trash_at');
            } else {
                $query->whereNull('trash_at');
            }
        }

        $selectedCustomerIds = collect($request->input('customer_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedCustomerIds->isEmpty()) {
            $legacySelectedCustomerId = (int) $request->input('customer_id', 0);
            if ($legacySelectedCustomerId > 0) {
                $selectedCustomerIds = collect([$legacySelectedCustomerId]);
            }
        }

        $customerSearch = trim((string) $request->input('customer_query', ''));

        $customers = $this->myOrderCustomersBaseQuery($user->id)
            ->when($customerSearch !== '', function ($q) use ($customerSearch) {
                $q->where(function ($sub) use ($customerSearch) {
                    $sub->where('name', 'like', "%{$customerSearch}%")
                        ->orWhere('phone', 'like', "%{$customerSearch}%")
                        ->orWhere('email', 'like', "%{$customerSearch}%");
                });
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'customer_page')
            ->appends($request->except('customer_page'));

        if ($selectedCustomerIds->isNotEmpty()) {
            $query->whereIn('customer_id', $selectedCustomerIds->all());
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            $query->whereBetween('created_at', [$from, $to]);
        } elseif ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $isTodayOrdersView = !$isTrashView
            && $request->filled('from_date')
            && $request->filled('to_date')
            && (string) $request->input('from_date') === now()->toDateString()
            && (string) $request->input('to_date') === now()->toDateString();

        $allowedPerPage = [5, 10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $allowedSortBy = ['code', 'customer_name', 'total', 'status', 'payment_status', 'created_at'];
        $sortBy = (string) $request->input('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        if ($isTodayOrdersView) {
            $query->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
                ->orderBy('daily_sequence')
                ->orderBy('created_at')
                ->orderBy('id');
        } elseif ($sortBy === 'customer_name') {
            $query->orderBy(
                Customer::query()
                    ->select('name')
                    ->whereColumn('customers.id', 'orders.customer_id')
                    ->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $orders = $query->paginate($perPage)->appends($request->query());

        $stockWarnings = $this->buildStockWarnings($orders->getCollection());

        if ($request->ajax() || $request->boolean('ajax')) {
            $html = view('site.orders.partials.orders_listing', [
                'orders' => $orders,
                'user' => $user,
                'sortBy' => $sortBy,
                'sortDir' => $sortDir,
                'stockWarnings' => $stockWarnings,
                'isTrashView' => $isTrashView,
                'monitoringEmbedded' => (string) $request->input('tab') === 'my_orders',
            ])->render();

            return response()->json([
                'html' => $html,
            ]);
        }

        return view('site.my_orders', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'customers' => $customers,
            'customerSearch' => $customerSearch,
            'selectedCustomerIds' => $selectedCustomerIds->all(),
            'perPage' => $perPage,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'stockWarnings' => $stockWarnings,
            'isTrashView' => $isTrashView,
        ]);
    }

    public function moveOrderToTrash(Order $order)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $user = auth()->user();
        if ((int) $order->user_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if (!$this->hasOrderColumn('trash_at')) {
            return back()->with('error', 'Chuc nang thung rac chua duoc khoi tao tren he thong.');
        }

        if (!empty($order->trash_at)) {
            return back()->with('success', 'Don hang da nam trong thung rac.');
        }

        $trashableStatuses = [Order::STATUS_REJECTED, Order::STATUS_CANCELLED];
        if (!in_array((string) $order->status, $trashableStatuses, true)) {
            return back()->with('error', 'Chi duoc dua vao thung rac don bi tu choi hoac da huy.');
        }

        $order->update([
            'trash_at' => now(),
        ]);

        return back()->with('success', 'Da dua don hang vao thung rac.');
    }

    public function myProducts(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $keyword = trim((string) $request->input('q', ''));
        $pinned = $request->input('pinned', 'all');
        $perPage = max(10, min((int) $request->input('per_page', 25), 100));

        $query = ProductVariant::query()
            ->with(['product.avatar.media', 'latestPriceRule', 'mediaLink.media'])
            ->withAvailableStock()
            ->where('product_variants.status', true)
            ->whereHas('product', function ($productQuery): void {
                $productQuery->where('products.status', true);
            })
            ->when($keyword !== '', function ($variantQuery) use ($keyword): void {
                $variantQuery->where(function ($searchQuery) use ($keyword): void {
                    $searchQuery->where('product_variants.sku', 'like', '%' . $keyword . '%')
                        ->orWhere('product_variants.name', 'like', '%' . $keyword . '%')
                        ->orWhere('product_variants.size', 'like', '%' . $keyword . '%')
                        ->orWhereHas('product', function ($productQuery) use ($keyword): void {
                            $productQuery->where('name', 'like', '%' . $keyword . '%');
                        });
                });
            });

        ProductVariantSorter::joinProductSort($query, (int) $user->id);

        if ($pinned === 'yes') {
            $query->whereRaw('COALESCE(user_variant_prefs.is_pinned, 0) = 1');
        } elseif ($pinned === 'no') {
            $query->whereRaw('COALESCE(user_variant_prefs.is_pinned, 0) = 0');
        }

        ProductVariantSorter::applyUserPreferencePrefix($query, (int) $user->id);
        ProductVariantSorter::applyAdminFallback($query)
            ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
            ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
            ->orderBy('product_variants.id');

        $variants = $query->paginate($perPage)->appends($request->query());

        return view('site.sales.products', [
            'settings' => $this->settings,
            'user' => $user,
            'variants' => $variants,
            'keyword' => $keyword,
            'pinned' => $pinned,
            'perPage' => $perPage,
        ]);
    }

    public function updateMyProductPreference(Request $request, ProductVariant $variant)
    {
        if (!auth()->check()) {
            abort(403);
        }

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
            'message' => 'Đã cập nhật thứ tự hiển thị sản phẩm.',
        ]);
    }

    public function storeOrderCustomerFeedback(Request $request, Order $order)
    {
        if (!auth()->check()) {
            abort(403);
        }

        if (!Schema::hasColumn('orders', 'customer_feedback_status')) {
            return back()->with('error', 'Chuc nang phan hoi khach hang chua duoc khoi tao tren he thong.');
        }

        $user = auth()->user();
        if ((int) $order->user_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403, 'Ban khong co quyen cap nhat phan hoi cho don nay.');
        }

        if (!$order->canReceiveCustomerFeedback()) {
            return back()->with('error', 'Chi nhap phan hoi khach hang cho don da hoan thanh, dang tra hang hoac co tra mot phan.');
        }

        if ($request->boolean('reset_feedback')) {
            return back()->with('error', 'Phan hoi khach hang chi duoc tao mot lan, khong the reset.');
        }

        if ($order->hasCustomerFeedback()) {
            return back()->with('error', 'Don hang nay da co phan hoi khach hang.');
        }

        $validated = $request->validate([
            'customer_feedback_status' => ['required', Rule::in(array_keys(Order::customerFeedbackOptions()))],
            'customer_feedback_note' => ['required', 'string', 'max:2000'],
            'customer_feedback_sale_review' => ['nullable', 'string', 'max:2000'],
            'customer_feedback_images' => ['nullable', 'array', 'max:6'],
            'customer_feedback_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'customer_feedback_status.required' => 'Vui long chon tinh trang khach hang.',
            'customer_feedback_note.required' => 'Vui long nhap thong tin phan hoi tu khach hang.',
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

        return back()->with('success', 'Da luu phan hoi khach hang cho bo phan dong hang.');
    }

    public function myOrdersMonitoring(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để theo dõi đơn hàng.');
        }

        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSalesFlowRole() && !$user->hasRole('director') && !$user->hasPermission('orders.monitoring')) {
            abort(403, 'Bạn không có quyền truy cập theo dõi đơn hàng.');
        }

        $allowedTabs = ['today', 'drafts', 'my_orders', 'customers', 'schedules', 'automatic'];
        $activeTab = (string) $request->input('tab', 'today');
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'today';
        }

        if ($activeTab !== 'today' && ($request->ajax() || $request->boolean('ajax'))) {
            return $this->monitoringTabResponse($activeTab, $request);
        }

        try {
            $selectedDate = Carbon::parse(
                $request->input('date', $request->input('from_date', now()->toDateString()))
            )->toDateString();
        } catch (\Throwable) {
            $selectedDate = now()->toDateString();
        }

        $dateQuery = Order::query()
            ->with([
                'customer.addresses',
                'customer.truckStation',
                'truckStation',
                'user.roles',
                'shipper',
                'accountingReconciliation',
                'approvals.step',
                'items.product',
                'items.variant.product',
                'items.variant.latestPriceRule',
            ])
            ->whereDate('created_at', $selectedDate);

        if ($this->hasOrderColumn('trash_at')) {
            $dateQuery->whereNull('trash_at');
        }

        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $dateQuery->where(function ($sub) use ($keyword) {
                $sub->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('shipper', function ($shipperQuery) use ($keyword) {
                        $shipperQuery->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $dateQuery->where('status', (string) $request->input('status'));
        }

        $sidebarOrders = (clone $dateQuery)->get();

        $selectedSaleId = max(0, (int) $request->input('sale_id', 0));
        if ($selectedSaleId > 0) {
            $dateQuery->where('user_id', $selectedSaleId);
        }

        $selectedCustomerId = max(0, (int) $request->input('customer_id', 0));
        if ($selectedCustomerId > 0) {
            $dateQuery->where('customer_id', $selectedCustomerId);
        }

        $roleNames = $this->normalizedRoleNames($user);
        $leaderRoleNames = $roleNames->intersect($this->monitoringLeaderRoleNames())->values();
        $canApproveManagedSales = $this->canApproveManagedSalesFromMonitoring($user);
        $canApproveAllOrders = $this->canApproveAllFromMonitoring($user);
        $canConfigureAutoApproval = $canApproveManagedSales || $canApproveAllOrders;
        $autoApprovalRules = $canConfigureAutoApproval
            ? $user->orderAutoApprovalRules()->get()->keyBy('order_type')
            : collect();

        $managedSalesApprovalQuery = clone $dateQuery;
        if ($canApproveManagedSales) {
            $this->applyManagedSalesScope($managedSalesApprovalQuery, $user);
            $this->applyMonitoringApprovalScope($managedSalesApprovalQuery, $leaderRoleNames);
        }
        $canApproveManagedSalesAny = $canApproveManagedSales && $managedSalesApprovalQuery->exists();

        $allApprovalQuery = clone $dateQuery;
        $hasPendingLeaderApprovals = false;
        if ($canApproveAllOrders) {
            $hasPendingLeaderApprovals = $this->monitoringHasPendingLeaderApprovals($request);
            $this->applyMonitoringApprovalScope($allApprovalQuery, $roleNames);
        }
        $canApproveAllAny = $canApproveAllOrders
            && !$hasPendingLeaderApprovals
            && $allApprovalQuery->exists();

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $allowedSorts = ['daily_sequence', 'created_at', 'total', 'customer_name', 'status'];
        $sortBy = (string) $request->input('sort_by', 'daily_sequence');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'daily_sequence';
        }

        $sortDir = strtolower((string) $request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $filteredOrders = (clone $dateQuery)->get();
        $statsQuery = clone $dateQuery;
        $stats = [
            'total_orders' => (clone $statsQuery)->count(),
            'delivering_orders' => (clone $statsQuery)->where('status', Order::STATUS_DELIVERING)->count(),
            'returning_orders' => (clone $statsQuery)->where('status', Order::STATUS_RETURNING)->count(),
            'completed_orders' => (clone $statsQuery)
                ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                ->count(),
            'total_value' => (clone $statsQuery)->sum('total'),
            'total_quantity' => $filteredOrders->sum(fn (Order $order) => (float) $order->items->sum('quantity')),
        ];

        $dateQuery->orderByRaw(
            'CASE WHEN status = ? THEN 1 ELSE 0 END ASC',
            [Order::STATUS_CANCELLED]
        );

        if ($sortBy === 'customer_name') {
            $dateQuery->orderBy(
                Customer::query()
                    ->select('name')
                    ->whereColumn('customers.id', 'orders.customer_id')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'daily_sequence') {
            $dateQuery->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
                ->orderBy('daily_sequence', $sortDir)
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $dateQuery->orderBy($sortBy, $sortDir)->orderBy('id', $sortDir);
        }

        $orders = $dateQuery
            ->paginate($perPage)
            ->appends($request->query());

        $canApproveByOrder = [];
        foreach ($orders as $order) {
            $currentStep = $order->approvals
                ->where('status', 'pending')
                ->filter(fn ($approval) => $approval->step)
                ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
                ->first();

            $isInApprovalScope = $canApproveAllOrders || (
                $canApproveManagedSales
                && (int) ($user->team_id ?? 0) > 0
                && (int) ($order->user?->team_id ?? 0) === (int) ($user->team_id ?? 0)
                && $order->user?->roles?->contains(fn ($role) => strtolower((string) $role->name) === 'sale')
            );

            $canApproveByOrder[$order->id] = $order->status !== Order::STATUS_CANCELLED
                && $isInApprovalScope
                && $currentStep?->step
                ? $roleNames->contains(strtolower((string) $currentStep->step->role_slug))
                : false;
        }

        $monitoringItems = $filteredOrders->flatMap(fn (Order $order) => $order->items);
        $monitoringVariantIds = $monitoringItems
            ->pluck('product_variant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $monitoringWarehouses = Warehouse::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $inventoryByVariant = Inventory::query()
            ->whereIn('product_variant_id', $monitoringVariantIds)
            ->get(['product_variant_id', 'warehouse_id', 'quantity', 'reserved_quantity'])
            ->groupBy('product_variant_id')
            ->map(fn ($inventories) => $inventories->groupBy('warehouse_id')->map(function ($warehouseInventories) {
                $onHand = (float) $warehouseInventories->sum('quantity');
                $reserved = (float) $warehouseInventories->sum('reserved_quantity');

                return [
                    'on_hand' => $onHand,
                    'available' => max(0, $onHand - $reserved),
                ];
            }));

        $productRows = $monitoringItems
            ->groupBy(function ($item) {
                $product = $item->product ?: $item->variant?->product;

                return $product?->id ? 'product:' . $product->id : 'name:' . ($product?->name ?? 'Sản phẩm');
            })
            ->map(function ($productItems) use ($inventoryByVariant, $monitoringWarehouses) {
                $firstProductItem = $productItems->first();
                $product = $firstProductItem->product ?: $firstProductItem->variant?->product;
                $variants = $productItems
                    ->groupBy(function ($item) {
                        return $item->product_variant_id
                            ? 'variant:' . $item->product_variant_id
                            : 'variant-name:' . ($item->variant?->name ?? $item->variant?->size ?? 'Mặc định');
                    })
                    ->map(function ($variantItems) use ($inventoryByVariant, $monitoringWarehouses) {
                        $first = $variantItems->first();
                        $variant = $first->variant;
                        $variantId = (int) ($first->product_variant_id ?? 0);
                        $prices = $variantItems->pluck('price')->map(fn ($price) => (float) $price);
                        $minPrice = (float) ($prices->min() ?? 0);
                        $maxPrice = (float) ($prices->max() ?? 0);
                        $subtotal = (float) $variantItems->sum(function ($item) {
                            $lineTotal = (float) ($item->total ?? 0);

                            return $lineTotal > 0
                                ? $lineTotal
                                : (float) ($item->quantity ?? 0) * (float) ($item->price ?? 0);
                        });
                        $warehouseStocks = $monitoringWarehouses->mapWithKeys(function ($warehouse) use ($inventoryByVariant, $variantId) {
                            return [$warehouse->id => $inventoryByVariant->get($variantId)?->get($warehouse->id) ?? [
                                'on_hand' => 0,
                                'available' => 0,
                            ]];
                        });

                        return [
                            'variant_id' => $variantId,
                            'name' => $variant?->size ?: ($variant?->name ?: ($variant?->sku ?: 'Mặc định')),
                            'sku' => (string) ($variant?->sku ?? ''),
                            'quantity' => (float) $variantItems->sum('quantity'),
                            'total' => (float) $variantItems->sum(fn ($item) => (float) $item->display_total_value),
                            'unit' => $first->display_total_unit,
                            'price_label' => abs($maxPrice - $minPrice) < 0.01
                                ? number_format($minPrice, 0, ',', '.') . 'đ'
                                : number_format($minPrice, 0, ',', '.') . '–' . number_format($maxPrice, 0, ',', '.') . 'đ',
                            'subtotal' => $subtotal,
                            'warehouse_stocks' => $warehouseStocks,
                        ];
                    })
                    ->sortBy('name')
                    ->values();

                return [
                    'name' => $product?->name ?? 'Sản phẩm',
                    'quantity' => (float) $variants->sum('quantity'),
                    'total' => (float) $variants->sum('total'),
                    'unit' => $variants->first()['unit'] ?? '',
                    'subtotal' => (float) $variants->sum('subtotal'),
                    'warehouse_stocks' => $monitoringWarehouses->mapWithKeys(fn ($warehouse) => [
                        $warehouse->id => [
                            'on_hand' => (float) $variants->sum(fn ($variant) => $variant['warehouse_stocks']->get($warehouse->id)['on_hand'] ?? 0),
                            'available' => (float) $variants->sum(fn ($variant) => $variant['warehouse_stocks']->get($warehouse->id)['available'] ?? 0),
                        ],
                    ]),
                    'variants' => $variants,
                ];
            })
            ->sortBy('name')
            ->values();

        $dailyOrderNotes = $filteredOrders
            ->filter(fn (Order $order) => trim((string) $order->note) !== '' || trim((string) $order->shipper_note) !== '')
            ->sortBy(fn (Order $order) => $order->daily_sequence ?? PHP_INT_MAX)
            ->values();

        $saleFilters = $sidebarOrders
            ->filter(fn (Order $order) => $order->user)
            ->groupBy('user_id')
            ->map(fn ($saleOrders) => [
                'id' => (int) $saleOrders->first()->user_id,
                'name' => $saleOrders->first()->user->name,
                'count' => $saleOrders->count(),
            ])
            ->sortBy('name')
            ->values();

        $customerFilters = $sidebarOrders
            ->filter(fn (Order $order) => $order->customer)
            ->groupBy('customer_id')
            ->map(function ($customerOrders) {
                $prioritySequence = $customerOrders
                    ->pluck('daily_sequence')
                    ->filter(fn ($sequence) => $sequence !== null)
                    ->map(fn ($sequence) => (int) $sequence)
                    ->min();

                return [
                    'id' => (int) $customerOrders->first()->customer_id,
                    'name' => $customerOrders->first()->customer->name,
                    'priority_sequence' => $prioritySequence,
                ];
            })
            ->sort(function ($left, $right) {
                $leftSequence = $left['priority_sequence'] ?? PHP_INT_MAX;
                $rightSequence = $right['priority_sequence'] ?? PHP_INT_MAX;

                return $leftSequence <=> $rightSequence
                    ?: strcasecmp((string) $left['name'], (string) $right['name']);
            })
            ->values();

        $tabContentHtml = $activeTab === 'today'
            ? null
            : $this->renderMonitoringTab($activeTab, $request);
        $customerTabSales = collect();

        return view('site.orders.monitoring', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
            'perPage' => $perPage,
            'keyword' => $keyword,
            'selectedDate' => $selectedDate,
            'selectedStatus' => (string) $request->input('status', ''),
            'selectedSaleId' => $selectedSaleId,
            'selectedCustomerId' => $selectedCustomerId,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'productRows' => $productRows,
            'monitoringWarehouses' => $monitoringWarehouses,
            'dailyOrderNotes' => $dailyOrderNotes,
            'saleFilters' => $saleFilters,
            'customerFilters' => $customerFilters,
            'customerTabSales' => $customerTabSales,
            'canApproveByOrder' => $canApproveByOrder,
            'canApproveManagedSales' => $canApproveManagedSales,
            'canApproveManagedSalesAny' => $canApproveManagedSalesAny,
            'canApproveAllOrders' => $canApproveAllOrders,
            'canApproveAllAny' => $canApproveAllAny,
            'hasPendingLeaderApprovals' => $hasPendingLeaderApprovals,
            'canConfigureAutoApproval' => $canConfigureAutoApproval,
            'autoApprovalRules' => $autoApprovalRules,
            'activeTab' => $activeTab,
            'tabContentHtml' => $tabContentHtml,
            'truckStations' => TruckStation::query()
                ->where('is_active', true)
                ->with(['brand', 'province', 'ward'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function monitoringTabResponse(string $tab, Request $request)
    {
        $tabRequest = $request->duplicate(collect($request->query())->except(['date'])->all());
        $tabRequest->setUserResolver(fn () => $request->user());

        return match ($tab) {
            'drafts' => app(\App\Http\Controllers\Admin\TextOrderImportController::class)->saleIndex($tabRequest),
            'my_orders' => $this->myOrders($tabRequest),
            'customers' => $this->monitoringCustomers($tabRequest),
            'schedules', 'automatic' => app(OrderScheduleController::class)->index($tabRequest),
            default => abort(404),
        };
    }

    private function monitoringCustomers(Request $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }
        $viewMode = in_array($request->input('view'), ['compact', 'default'], true)
            ? (string) $request->input('view')
            : 'default';
        $search = trim((string) $request->input('search', ''));

        $customers = Customer::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->where(function ($query) {
                $query->where('is_employee', '<>', 1)->orWhereNull('is_employee');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->withCount('orders')
            ->withSum('orders as total_debt', 'amount_due')
            ->with([
                'addresses' => fn ($query) => $query->orderByDesc('is_default')->limit(1),
                'currentOwner:id,name',
                'assignedTo:id,name',
                'user:id,name',
            ]);

        $this->applyCustomerPinnedSort($customers);
        $customers = $customers->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('site.my_customer.monitoring-list', [
            'customers' => $customers,
            'perPage' => $perPage,
            'viewMode' => $viewMode,
            'search' => $search,
            'selectedSaleId' => 0,
            'manageableSaleIds' => [$userId],
            'monitoringEmbedded' => true,
        ]);
    }

    private function monitoringVisibleSales(User $user)
    {
        $activeRole = strtolower(trim((string) (session('active_role') ?: $user->defaultRole?->name)));
        $isSaleView = $activeRole === 'sale' || (
            $activeRole === ''
            && $user->hasRole('sale')
            && !$user->hasRole(['admin', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale', 'director'])
        );

        if ($isSaleView) {
            return User::query()->whereKey($user->id)->get(['id', 'name', 'team_id']);
        }

        $query = User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereRaw('LOWER(name) = ?', ['sale']));

        if ($user->hasRole(['leader', 'leader_sale', 'sale_manager']) && !$user->hasRole(['admin', 'manager', 'manager_sale', 'director'])) {
            $teamId = (int) ($user->team_id ?? 0);
            $teamId > 0 ? $query->where('team_id', $teamId) : $query->whereRaw('1 = 0');
        }

        return $query->orderBy('name')->get(['id', 'name', 'team_id']);
    }

    private function renderMonitoringTab(string $tab, Request $request): string
    {
        $response = $this->monitoringTabResponse($tab, $request);
        if (!$response instanceof \Illuminate\View\View) {
            return '';
        }

        return $response->with([
            'monitoringEmbedded' => true,
            'monitoringScheduleMode' => $tab === 'automatic' ? 'automatic' : 'schedules',
            'errors' => session()->get('errors', new \Illuminate\Support\ViewErrorBag()),
        ])->render();
    }

    public function myOrdersMonitoringApproveAll(Request $request, ApprovalService $approvalService)
    {
        $user = $this->monitoringUserOrFail();
        abort_unless($this->canApproveAllFromMonitoring($user), 403, 'Chỉ manager mới được duyệt tất cả đơn.');
        $roleNames = $this->normalizedRoleNames($user);

        $query = Order::query()->with(['approvals.step']);
        $this->applyMonitoringOrderFilters($query, $request);

        if ($this->monitoringHasPendingLeaderApprovals($request)) {
            return back()->with('error', 'Cần chờ các Trưởng phòng KD duyệt hết đơn PKD trước khi duyệt tất cả.');
        }

        $this->applyMonitoringApprovalScope($query, $roleNames);

        $result = $this->approveOrdersFromQuery(
            $query,
            $user,
            $approvalService,
            'Duyệt tất cả từ trang theo dõi đơn hàng'
        );

        return $this->redirectAfterApproveAll($result, 'theo bộ lọc');
    }

    public function myOrdersMonitoringApproveSales(Request $request, ApprovalService $approvalService)
    {
        $user = $this->monitoringUserOrFail();
        abort_unless($this->canApproveManagedSalesFromMonitoring($user), 403, 'Bạn không có quyền duyệt đơn của sale được quản lý.');

        $query = Order::query()->with(['approvals.step', 'user.roles']);
        $this->applyMonitoringOrderFilters($query, $request);
        $this->applyManagedSalesScope($query, $user);
        $leaderRoleNames = $this->normalizedRoleNames($user)
            ->intersect($this->monitoringLeaderRoleNames())
            ->values();
        $this->applyMonitoringApprovalScope($query, $leaderRoleNames);

        $result = $this->approveOrdersFromQuery(
            $query,
            $user,
            $approvalService,
            'Duyệt đơn PKD từ trang theo dõi đơn hàng'
        );

        return $this->redirectAfterApproveAll($result, 'sale được quản lý');
    }

    public function myOrdersMonitoringRefreshSequence(
        Request $request,
        OrderAutoApprovalService $autoApprovalService
    )
    {
        $this->monitoringUserOrFail();

        $query = Order::query()->where('status', '!=', Order::STATUS_REJECTED);
        $this->applyMonitoringOrderFilters($query, $request);

        $autoApprovedOrders = 0;
        (clone $query)
            ->whereIn('status', [
                Order::STATUS_PENDING_LEADER_APPROVAL,
                Order::STATUS_PENDING_MANAGER_APPROVAL,
                OrderStatus::Pending->value,
            ])
            ->where(function ($scope): void {
                $scope->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->where(function ($scope): void {
                $scope->whereNull('order_type')->orWhere('order_type', '!=', 'order_return');
            })
            ->with(['user', 'items.variant.latestPriceRule', 'approvals.step'])
            ->orderBy('id')
            ->get()
            ->each(function (Order $order) use ($autoApprovalService, &$autoApprovedOrders): void {
                if ($autoApprovalService->processOrder($order) > 0) {
                    $autoApprovedOrders++;
                }
            });

        $response = $this->refreshMissingDailySequencesFromQuery($query, 'theo dõi');
        if ($autoApprovedOrders > 0) {
            $sequenceMessage = (string) session('success', 'Đã cập nhật số thứ tự ưu tiên.');
            $response->with(
                'success',
                "Đã tự động duyệt {$autoApprovedOrders} đơn phù hợp. {$sequenceMessage}"
            );
        }

        return $response;
    }

    public function myOrdersMonitoringAutoApproval(Request $request, OrderAutoApprovalService $autoApprovalService)
    {
        $user = $this->monitoringUserOrFail();
        abort_unless(
            $this->canApproveManagedSalesFromMonitoring($user) || $this->canApproveAllFromMonitoring($user),
            403,
            'Bạn không có quyền cấu hình duyệt đơn tự động.'
        );

        $validated = $request->validate([
            'new_order_enabled' => ['nullable', 'boolean'],
            'new_order_require_min_price' => ['nullable', 'boolean'],
            'new_order_allow_bulk_below_min' => ['nullable', 'boolean'],
            'new_order_bulk_min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'new_order_bulk_below_min_amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'order_adjustment_enabled' => ['nullable', 'boolean'],
            'order_adjustment_require_min_price' => ['nullable', 'boolean'],
            'order_adjustment_allow_bulk_below_min' => ['nullable', 'boolean'],
            'order_adjustment_bulk_min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'order_adjustment_bulk_below_min_amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
        ]);

        foreach ([
            OrderAutoApprovalRule::TYPE_NEW_ORDER => 'new_order',
            OrderAutoApprovalRule::TYPE_ORDER_ADJUSTMENT => 'order_adjustment',
        ] as $type => $prefix) {
            OrderAutoApprovalRule::query()->updateOrCreate(
                ['user_id' => $user->id, 'order_type' => $type],
                [
                    'enabled' => $request->boolean("{$prefix}_enabled"),
                    'require_min_price' => $request->boolean("{$prefix}_require_min_price"),
                    'allow_bulk_below_min' => $request->boolean("{$prefix}_allow_bulk_below_min"),
                    'bulk_min_quantity' => (int) $validated["{$prefix}_bulk_min_quantity"],
                    'bulk_below_min_amount' => (float) $validated["{$prefix}_bulk_below_min_amount"],
                ]
            );
        }

        $scanCompleted = true;
        try {
            $result = $autoApprovalService->processPendingForUser($user);
            foreach ($result['completedAdjustments'] as [$adjustment, $approver]) {
                app(OrderAdjustmentController::class)->finalizeAutoApprovedAdjustment($adjustment, $approver);
            }
        } catch (\Throwable $exception) {
            $scanCompleted = false;
            $result = ['orderSteps' => 0, 'adjustmentSteps' => 0];
            Log::error('Đã lưu cấu hình nhưng không thể quét duyệt đơn tự động.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        $approvedSteps = $result['orderSteps'] + $result['adjustmentSteps'];
        $message = 'Đã lưu cấu hình duyệt đơn tự động.';
        if ($approvedSteps > 0) {
            $message .= " Đã tự động duyệt {$approvedSteps} bước đang chờ phù hợp.";
        } elseif (!$scanCompleted) {
            $message .= ' Cấu hình đã có hiệu lực cho đơn mới; chưa thể quét lại các đơn đang chờ.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'approved_steps' => $approvedSteps,
                'scan_completed' => $scanCompleted,
            ]);
        }

        return back()->with('success', $message);
    }

    public function dailyProductPrices(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem bảng giá sản phẩm hàng ngày.');
        }

        $user = auth()->user();
        if (
            !$user->hasPermission('pages.my_orders.daily_prices')
            && !$this->canAccessSalesDailyPages($user)
        ) {
            abort(403, 'Bạn không có quyền truy cập bảng giá sản phẩm hàng ngày.');
        }

        $keyword = trim((string) $request->input('keyword', ''));
        $showAllVariants = $request->boolean('show_all_variants');
        $selectedProductIds = collect((array) $request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $products = Product::query()
            ->with([
                'avatar.media',
                'variants.latestPriceLog',
            ])
            ->where('status', true)
            ->whereHas('variants', function ($variantQuery) {
                $variantQuery->whereHas('latestPriceLog', function ($priceLogQuery) {
                    $priceLogQuery->where('new_price', '>', 0);
                });
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($keyword) {
                            $variantQuery->where('sku', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when(!empty($selectedProductIds), function ($query) use ($selectedProductIds) {
                $query->whereIn('id', $selectedProductIds);
            })
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) {
                $variantRows = $product->variants
                    ->map(function (ProductVariant $variant) {
                        $price = (float) ($variant->latestPriceLog?->new_price ?? 0);

                        $variant->setAttribute('current_price', $price);
                        $variant->setAttribute('price_key', number_format($price, 4, '.', ''));

                        return $variant;
                    })
                    ->filter(fn (ProductVariant $variant) => (float) ($variant->current_price ?? 0) > 0)
                    ->values();

                if ($variantRows->isEmpty()) {
                    $product->setAttribute('current_price', 0);
                    $product->setRelation('priceDiffVariants', collect());
                    $product->setAttribute('has_positive_price', false);
                    return $product;
                }

                $groupedByPrice = $variantRows->groupBy(fn ($variant) => (string) $variant->price_key);
                $representativeGroup = $groupedByPrice
                    ->sortByDesc(fn ($items) => $items->count())
                    ->first();

                $representativePrice = (float) ($representativeGroup?->first()?->current_price ?? 0);
                $representativePriceKey = (string) ($representativeGroup?->first()?->price_key ?? number_format(0, 4, '.', ''));

                $differentVariants = $variantRows
                    ->filter(fn ($variant) => (string) $variant->price_key !== $representativePriceKey)
                    ->sortBy('name')
                    ->values();

                $product->setAttribute('current_price', $representativePrice);
                $product->setAttribute('total_variants_count', $variantRows->count());
                $product->setRelation('priceDiffVariants', $differentVariants);
                $product->setRelation('allVariantsByPrice', $variantRows->sortBy('name')->values());
                $product->setAttribute('has_positive_price', true);

                return $product;
            })->map(function (Product $product) use ($showAllVariants) {
                if ($showAllVariants) {
                    $product->setRelation('priceDiffVariants', $product->allVariantsByPrice ?? collect());
                }

                return $product;
            })->filter(fn (Product $product) => (bool) ($product->has_positive_price ?? false))->values()
        );

        $totalVariants = (int) ProductVariant::query()
            ->whereHas('product', function ($query) {
                $query->where('status', true);
            })
            ->whereHas('latestPriceLog', function ($priceLogQuery) {
                $priceLogQuery->where('new_price', '>', 0);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('sku', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhereHas('product', function ($productQuery) use ($keyword) {
                            $productQuery->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when(!empty($selectedProductIds), function ($query) use ($selectedProductIds) {
                $query->whereIn('product_id', $selectedProductIds);
            })
            ->count();

        $differentVariantCountOnPage = $products->getCollection()
            ->sum(fn (Product $product) => $product->priceDiffVariants->count());

        $selectableProducts = Product::query()
            ->select(['id', 'name'])
            ->where('status', true)
            ->whereHas('variants', function ($variantQuery) {
                $variantQuery->whereHas('latestPriceLog', function ($priceLogQuery) {
                    $priceLogQuery->where('new_price', '>', 0);
                });
            })
            ->orderBy('name')
            ->get();

        // Lấy thông tin công ty
        $company = Company::query()->first();

        // Lấy Trưởng phòng Kinh Doanh (Leader)
        $businessLeader = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['leader_sale', 'leader', 'sale_manager']);
            })
            ->orderBy('created_at')
            ->first();

        return view('site.sales.daily_prices', [
            'settings' => $this->settings,
            'user' => $user,
            'products' => $products,
            'totalVariants' => $totalVariants,
            'differentVariantCountOnPage' => $differentVariantCountOnPage,
            'showAllVariants' => $showAllVariants,
            'keyword' => $keyword,
            'selectedProductIds' => $selectedProductIds,
            'selectableProducts' => $selectableProducts,
            'asOfDate' => now(),
            'company' => $company,
            'businessLeader' => $businessLeader,
        ]);
    }

    public function dailyInventories(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem tồn kho hàng ngày.');
        }

        $user = auth()->user();
        if (
            !$user->hasPermission('pages.my_orders.daily_inventories')
            && !$this->canAccessSalesDailyPages($user)
        ) {
            abort(403, 'Bạn không có quyền truy cập tồn kho hàng ngày.');
        }

        $keyword = trim((string) $request->input('keyword', ''));

        $variants = ProductVariant::query()
            ->with(['product.avatar.media'])
            ->withAvailableStock()
            ->whereHas('product', function ($query) {
                $query->where('status', true);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('sku', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhereHas('product', function ($productQuery) use ($keyword) {
                            $productQuery->where('name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderByDesc('available_stock')
            ->orderBy('sku')
            ->paginate(25)
            ->appends($request->query());

        return view('site.sales.daily_inventories', [
            'settings' => $this->settings,
            'user' => $user,
            'variants' => $variants,
            'keyword' => $keyword,
            'asOfDate' => now(),
        ]);
    }

    public function myOrderCustomersAjax(Request $request)
    {
        if (!auth()->check()) {
            abort(401);
        }

        $user = auth()->user();
        $search = trim((string) $request->input('q', ''));
        $mode = $request->input('mode', 'multi'); // 'single' or 'multi'
        $perPage = min((int) $request->input('per_page', 15), 50);
        $perPage = max($perPage, 5);
        $allowedSortBy = ['manual', 'name', 'phone', 'email'];
        $sortBy = in_array($request->input('sort_by', 'manual'), $allowedSortBy, true)
            ? $request->input('sort_by', 'manual')
            : 'manual';
        $sortDir = $request->input('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $selectedCustomerIds = collect(explode(',', (string) $request->input('selected_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $scope = $request->input('scope'); // 'orders' or 'my_customers'
        if (!in_array($scope, ['orders', 'my_customers'], true)) {
            $scope = $mode === 'single' ? 'my_customers' : 'orders';
        }

        if ($scope === 'my_customers') {
            $baseQuery = $this->myAssignedCustomersQuery((int) $user->id);
        } else {
            $baseQuery = $this->myOrderCustomersBaseQuery($user->id);
        }

        $customers = $baseQuery
            ->with('truckStation')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($sortBy === 'manual', fn ($q) => $this->applyCustomerPinnedSort($q)->orderBy('name'))
            ->when($sortBy !== 'manual', fn ($q) => $this->applyCustomerPinnedSort($q)->orderBy($sortBy, $sortDir))
            ->paginate($perPage, ['*'], 'page');

        $partial = $mode === 'single'
            ? 'site.orders.partials.customer_picker_single'
            : 'site.orders.partials.customer_listing';

        $html = view($partial, [
            'customers' => $customers,
            'selectedCustomerIds' => $selectedCustomerIds,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'perPage' => $perPage,
        ])->render();

        return response()->json([
            'html' => $html,
        ]);
    }

    public function myTearmOrders(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem đơn team.');
        }

        $user = auth()->user();
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('admin');

        if (!$isLeader) {
            abort(403, 'Bạn không có quyền truy cập trang duyệt đơn của team.');
        }

        $roleNames = $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();

        $query = Order::with([
            'customer',
            'user.roles',
            'user.team',
            'approvals.step',
            'items.product',
            'items.variant',
        ])
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->whereHas('user', function ($sub) use ($user) {
                    $sub->where(function ($scope) use ($user) {
                        $scope->where(function ($teamSale) use ($user) {
                            $teamSale->where('team_id', $user->team_id)
                                ->whereHas('roles', function ($roleQuery) {
                                    $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
                                });
                        })->orWhere('id', $user->id);
                    });
                });
            });
        $query->where('status', '!=', Order::STATUS_REJECTED);

        // Mặc định giữ toàn bộ đơn trong ngày để leader theo dõi đầy đủ
        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $query->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        // Chỉ lọc theo bước chờ duyệt khi người dùng chủ động bật
        $pendingOnly = $request->boolean('pending_only');
        if ($pendingOnly) {
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

        if ($request->filled('status') && $request->input('status') !== Order::STATUS_REJECTED) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $allowedPerPage = [10, 15, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $orders = $query->latest()->paginate($perPage)->appends($request->query());

        $currentStepByOrder = [];
        $canApproveByOrder = [];

        foreach ($orders as $order) {
            $currentStep = $order->approvals
                ->where('status', 'pending')
                ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
                ->first();

            $currentStepByOrder[$order->id] = $currentStep;

            $canApprove = false;
            if ($currentStep?->step) {
                $requiredRole = strtolower((string) $currentStep->step->role_slug);
                $canApprove = $roleNames->contains($requiredRole);
            }

            $canApproveByOrder[$order->id] = $canApprove;
        }

        // Tính stats dựa trên visual status
        $allOrders = (clone $query)->with(['approvals.step'])->get();
        $stats = [
            'total' => $allOrders->count(),
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($allOrders as $order) {
            $hasPassedViewerStep = $order->approvals->contains(function ($approval) use ($roleNames) {
                $roleSlug = strtolower((string) optional($approval->step)->role_slug);
                return $approval->status === 'approved' && in_array($roleSlug, $roleNames->toArray(), true);
            });
            $visualStatus = ($order->status !== 'rejected' && $hasPassedViewerStep)
                ? 'approved'
                : (string) $order->status;

            if ($visualStatus === 'approved') {
                $stats['approved']++;
            } elseif ($visualStatus === 'rejected') {
                $stats['rejected']++;
            } else {
                $stats['pending']++;
            }
        }

        return view('site.my_team_orders', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
            'currentStepByOrder' => $currentStepByOrder,
            'canApproveByOrder' => $canApproveByOrder,
            'pendingOnly' => $pendingOnly,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'perPage' => $perPage,
        ]);
    }

    public function myTearmOrdersAutoApprove(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('admin');
        if (!$isLeader) {
            abort(403, 'Bạn không có quyền duyệt tự động.');
        }

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'condition_item_qty' => 'nullable|in:1',
            'min_item_qty' => 'nullable|integer|min:1',
            'max_item_qty' => 'nullable|integer|min:1',
            'condition_order_total' => 'nullable|in:1',
            'min_order_total' => 'nullable|numeric|min:0',
            'max_order_total' => 'nullable|numeric|min:0',
            'freeship_20_amount' => 'nullable|numeric|min:0',
            'discount_30_amount' => 'nullable|numeric|min:0',
            'discount_40_amount' => 'nullable|numeric|min:0',
            'discount_50_amount' => 'nullable|numeric|min:0',
            'discount_70_amount' => 'nullable|numeric|min:0',
            'discount_80_amount' => 'nullable|numeric|min:0',
            'discount_100_amount' => 'nullable|numeric|min:0',
            'use_special_customer_discount' => 'nullable|in:1',
            'special_customer_discount_amount' => 'nullable|numeric|min:0',
        ]);

        $discountConfig = $this->discountConfigFromRequest($validated);

        $useItemQty = $request->boolean('condition_item_qty');
        $useOrderTotal = $request->boolean('condition_order_total');

        if (!$useItemQty && !$useOrderTotal) {
            return back()->with('error', 'Vui lòng chọn ít nhất một điều kiện để duyệt tự động.')->withInput();
        }

        $minItemQty = $useItemQty ? (int) ($validated['min_item_qty'] ?? 0) : null;
        $maxItemQty = $useItemQty ? (($validated['max_item_qty'] ?? null) !== null ? (int) $validated['max_item_qty'] : null) : null;
        if ($useItemQty && $minItemQty < 1) {
            return back()->with('error', 'Vui lòng nhập số lượng sản phẩm tối thiểu hợp lệ.')->withInput();
        }
        if ($useItemQty && $maxItemQty !== null && $maxItemQty < $minItemQty) {
            return back()->with('error', 'Số lượng sản phẩm tối đa phải lớn hơn hoặc bằng tối thiểu.')->withInput();
        }

        $minOrderTotal = $useOrderTotal ? (float) ($validated['min_order_total'] ?? 0) : null;
        $maxOrderTotal = $useOrderTotal ? (($validated['max_order_total'] ?? null) !== null ? (float) $validated['max_order_total'] : null) : null;
        if ($useOrderTotal && $maxOrderTotal !== null && $maxOrderTotal < $minOrderTotal) {
            return back()->with('error', 'Giá trị đơn hàng tối đa phải lớn hơn hoặc bằng tối thiểu.')->withInput();
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $roleNames = $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();

        $query = Order::with(['items', 'approvals.step', 'user.roles', 'customer.type'])
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->whereHas('user', function ($sub) use ($user) {
                    $sub->where(function ($scope) use ($user) {
                        $scope->where(function ($teamSale) use ($user) {
                            $teamSale->where('team_id', $user->team_id)
                                ->whereHas('roles', function ($roleQuery) {
                                    $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
                                });
                        })->orWhere('id', $user->id);
                    });
                });
            })
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

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

        $candidateOrders = $query->latest()->get();

        $eligibleOrders = $candidateOrders->filter(function (Order $order) use ($useItemQty, $minItemQty, $maxItemQty, $useOrderTotal, $minOrderTotal, $maxOrderTotal) {
            $totalItemQty = (int) $order->items->sum('quantity');
            $orderTotal = (float) $order->total;

            if ($useItemQty) {
                if ($totalItemQty < $minItemQty) {
                    return false;
                }
                if ($maxItemQty !== null && $totalItemQty > $maxItemQty) {
                    return false;
                }
            }

            if ($useOrderTotal) {
                if ($orderTotal < $minOrderTotal) {
                    return false;
                }
                if ($maxOrderTotal !== null && $orderTotal > $maxOrderTotal) {
                    return false;
                }
            }

            return true;
        });

        $approvedCount = 0;
        $failedCount = 0;

        foreach ($eligibleOrders as $order) {
            try {
                if ($approvalService->canApproveCurrentStep($order, $user)) {
                    $discountResult = $this->applyAutoApproveDiscount($order, $discountConfig);
                    $note = 'Duyet tu dong theo dieu kien leader. '
                        . $this->discountNoteFromResult($discountResult);

                    $approvalService->approve($order, $user, $note);
                    app(OrderAutoApprovalService::class)->processOrder($order);
                    $approvedCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
            }
        }

        if ($approvedCount === 0) {
            return back()->with('error', 'Không có đơn nào phù hợp để duyệt tự động.')->withInput();
        }

        $message = "Đã duyệt tự động {$approvedCount} đơn";
        if ($failedCount > 0) {
            $message .= ", {$failedCount} đơn không thể duyệt.";
        } else {
            $message .= '.';
        }

        return back()->with('success', $message);
    }

    public function myTeamOrdersApproveAll(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('admin');
        if (!$isLeader) {
            abort(403, 'Bạn không có quyền duyệt tất cả đơn của team.');
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $roleNames = $this->normalizedRoleNames($user);

        $query = Order::with(['approvals.step', 'user.roles', 'customer'])
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->whereHas('user', function ($sub) use ($user) {
                    $sub->where(function ($scope) use ($user) {
                        $scope->where(function ($teamSale) use ($user) {
                            $teamSale->where('team_id', $user->team_id)
                                ->whereHas('roles', function ($roleQuery) {
                                    $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
                                });
                        })->orWhere('id', $user->id);
                    });
                });
            })
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        $this->applyTeamOrderFilters($query, $request);
        $this->applyCurrentApprovalStepScope($query, $roleNames);

        $result = $this->approveOrdersFromQuery($query, $user, $approvalService, 'Leader duyệt tất cả từ trang my-team-orders');

        return $this->redirectAfterApproveAll($result, 'team');
    }

    public function myTeamOrdersRefreshSequence(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('admin');
        if (!$isLeader) {
            abort(403, 'Bạn không có quyền cập nhật số thứ tự đơn team.');
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $query = Order::query()
            ->with(['customer', 'user.roles'])
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->whereHas('user', function ($sub) use ($user) {
                    $sub->where(function ($scope) use ($user) {
                        $scope->where(function ($teamSale) use ($user) {
                            $teamSale->where('team_id', $user->team_id)
                                ->whereHas('roles', function ($roleQuery) {
                                    $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
                                });
                        })->orWhere('id', $user->id);
                    });
                });
            })
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        $this->applyTeamOrderFilters($query, $request);

        return $this->refreshMissingDailySequencesFromQuery($query, 'team');
    }

    public function allTearmOrders(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem đơn PKD.');
        }

        $user = auth()->user();
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director') || $user->hasRole('admin');
        if (!$isManager) {
            abort(403, 'Bạn không có quyền truy cập trang duyệt đơn PKD.');
        }

        $roleNames = $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();

        $allowedCreatorRoles = ['sale', 'leader', 'leader_sale', 'sale_manager'];

        $query = Order::with(['customer', 'user.roles', 'user.team', 'approvals.step'])
            ->whereHas('user.roles', function ($q) use ($allowedCreatorRoles) {
                $q->whereIn(DB::raw('LOWER(name)'), $allowedCreatorRoles);
            })
            ->where('status', '!=', Order::STATUS_REJECTED);

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $query->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function ($sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

        $pendingOnly = $request->boolean('pending_only');
        if ($pendingOnly) {
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

        if ($request->filled('status') && $request->input('status') !== Order::STATUS_REJECTED) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(15)->appends($request->query());

        $currentStepByOrder = [];
        $canApproveByOrder = [];

        foreach ($orders as $order) {
            $currentStep = $order->approvals
                ->where('status', 'pending')
                ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
                ->first();

            $currentStepByOrder[$order->id] = $currentStep;

            $canApprove = false;
            if ($currentStep?->step) {
                $requiredRole = strtolower((string) $currentStep->step->role_slug);
                $canApprove = $roleNames->contains($requiredRole);
            }

            $canApproveByOrder[$order->id] = $canApprove;
        }

        $stats = [
            'total' => $orders->total(),
            'pending' => (clone $query)->where('status', 'pending_manager_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
        ];

        $teams = Team::orderBy('name')->get(['id', 'name']);

        return view('site.all_team_orders', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
            'currentStepByOrder' => $currentStepByOrder,
            'canApproveByOrder' => $canApproveByOrder,
            'pendingOnly' => $pendingOnly,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'teams' => $teams,
        ]);
    }

    public function allTearmOrdersAutoApprove(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director') || $user->hasRole('admin');
        if (!$isManager) {
            abort(403, 'Bạn không có quyền duyệt tự động cho PKD.');
        }

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'team_id' => 'nullable|integer|exists:teams,id',
            'condition_item_qty' => 'nullable|in:1',
            'min_item_qty' => 'nullable|integer|min:1',
            'max_item_qty' => 'nullable|integer|min:1',
            'condition_sale_price' => 'nullable|in:1',
            'min_sale_price' => 'nullable|numeric|min:0',
            'max_sale_price' => 'nullable|numeric|min:0',
            'freeship_20_amount' => 'nullable|numeric|min:0',
            'discount_30_amount' => 'nullable|numeric|min:0',
            'discount_40_amount' => 'nullable|numeric|min:0',
            'discount_50_amount' => 'nullable|numeric|min:0',
            'discount_70_amount' => 'nullable|numeric|min:0',
            'discount_80_amount' => 'nullable|numeric|min:0',
            'discount_100_amount' => 'nullable|numeric|min:0',
            'use_special_customer_discount' => 'nullable|in:1',
            'special_customer_discount_amount' => 'nullable|numeric|min:0',
        ]);

        $discountConfig = $this->discountConfigFromRequest($validated);

        $useItemQty = $request->boolean('condition_item_qty');
        $useSalePrice = $request->boolean('condition_sale_price');

        if (!$useItemQty && !$useSalePrice) {
            return back()->with('error', 'Vui lòng chọn ít nhất một điều kiện để duyệt tự động.')->withInput();
        }

        $minItemQty = $useItemQty ? (int) ($validated['min_item_qty'] ?? 0) : null;
        $maxItemQty = $useItemQty ? (($validated['max_item_qty'] ?? null) !== null ? (int) $validated['max_item_qty'] : null) : null;
        if ($useItemQty && $minItemQty < 1) {
            return back()->with('error', 'Vui lòng nhập số lượng tối thiểu hợp lệ.')->withInput();
        }
        if ($useItemQty && $maxItemQty !== null && $maxItemQty < $minItemQty) {
            return back()->with('error', 'Số lượng tối đa phải lớn hơn hoặc bằng tối thiểu.')->withInput();
        }

        $minSalePrice = $useSalePrice ? (float) ($validated['min_sale_price'] ?? 0) : null;
        $maxSalePrice = $useSalePrice ? (($validated['max_sale_price'] ?? null) !== null ? (float) $validated['max_sale_price'] : null) : null;
        if ($useSalePrice && $maxSalePrice !== null && $maxSalePrice < $minSalePrice) {
            return back()->with('error', 'Giá bán tối đa phải lớn hơn hoặc bằng tối thiểu.')->withInput();
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $roleNames = $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();

        $allowedCreatorRoles = ['sale', 'leader', 'leader_sale', 'sale_manager'];

        $query = Order::with(['items', 'approvals.step', 'user.roles', 'customer.type'])
            ->whereHas('user.roles', function ($q) use ($allowedCreatorRoles) {
                $q->whereIn(DB::raw('LOWER(name)'), $allowedCreatorRoles);
            })
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->where('status', '!=', Order::STATUS_REJECTED)
            // Bắt buộc đã qua leader và đang chờ manager duyệt
            ->where('status', 'pending_manager_approval');

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function ($sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

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

        $candidateOrders = $query->latest()->get();

        $eligibleOrders = $candidateOrders->filter(function (Order $order) use ($useItemQty, $minItemQty, $maxItemQty, $useSalePrice, $minSalePrice, $maxSalePrice) {
            $totalItemQty = (int) $order->items->sum('quantity');
            $salePrice = (float) $order->total;

            if ($useItemQty) {
                if ($totalItemQty < $minItemQty) {
                    return false;
                }
                if ($maxItemQty !== null && $totalItemQty > $maxItemQty) {
                    return false;
                }
            }

            if ($useSalePrice) {
                if ($salePrice < $minSalePrice) {
                    return false;
                }
                if ($maxSalePrice !== null && $salePrice > $maxSalePrice) {
                    return false;
                }
            }

            return true;
        });

        $approvedCount = 0;
        $failedCount = 0;

        foreach ($eligibleOrders as $order) {
            try {
                if ($approvalService->canApproveCurrentStep($order, $user)) {
                    $discountResult = $this->applyAutoApproveDiscount($order, $discountConfig);
                    $note = 'Manager duyet tu dong theo dieu kien PKD. '
                        . $this->discountNoteFromResult($discountResult);

                    $approvalService->approve($order, $user, $note);
                    app(OrderAutoApprovalService::class)->processOrder($order);
                    $approvedCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
            }
        }

        if ($approvedCount === 0) {
            return back()->with('error', 'Không có đơn nào phù hợp để duyệt tự động.')->withInput();
        }

        $message = "Đã duyệt tự động {$approvedCount} đơn";
        if ($failedCount > 0) {
            $message .= ", {$failedCount} đơn không thể duyệt.";
        } else {
            $message .= '.';
        }

        return back()->with('success', $message);
    }

    public function allTeamOrdersApproveAll(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director') || $user->hasRole('admin');
        if (!$isManager) {
            abort(403, 'Bạn không có quyền duyệt tất cả đơn PKD.');
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $roleNames = $this->normalizedRoleNames($user);
        $allowedCreatorRoles = ['sale', 'leader', 'leader_sale', 'sale_manager'];

        $query = Order::with(['approvals.step', 'user.roles', 'customer'])
            ->whereHas('user.roles', function ($q) use ($allowedCreatorRoles) {
                $q->whereIn(DB::raw('LOWER(name)'), $allowedCreatorRoles);
            })
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->whereDate('created_at', now()->toDateString());

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function ($sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

        $this->applyTeamOrderFilters($query, $request);
        $this->applyCurrentApprovalStepScope($query, $roleNames);

        $result = $this->approveOrdersFromQuery($query, $user, $approvalService, 'Manager duyệt tất cả từ trang all-team-orders');

        return $this->redirectAfterApproveAll($result, 'PKD');
    }

    public function allTeamOrdersRefreshSequence(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director') || $user->hasRole('admin');
        if (!$isManager) {
            abort(403, 'Bạn không có quyền cập nhật số thứ tự đơn PKD.');
        }

        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $allowedCreatorRoles = ['sale', 'leader', 'leader_sale', 'sale_manager'];

        $query = Order::query()
            ->with(['customer', 'user.roles'])
            ->whereHas('user.roles', function ($q) use ($allowedCreatorRoles) {
                $q->whereIn(DB::raw('LOWER(name)'), $allowedCreatorRoles);
            })
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function ($sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

        $this->applyTeamOrderFilters($query, $request);

        return $this->refreshMissingDailySequencesFromQuery($query, 'PKD');
    }

    private function normalizedRoleNames(User $user)
    {
        return $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();
    }

    private function canApproveManagedSalesFromMonitoring(User $user): bool
    {
        return $user->hasRole([
            'leader',
            'leader_sale',
            'sale_manager',
        ]);
    }

    private function canApproveAllFromMonitoring(User $user): bool
    {
        return $user->hasRole(['manager', 'manager_sale', 'director', 'admin']);
    }

    private function applyManagedSalesScope(Builder $query, User $user): Builder
    {
        $query->whereHas('user.roles', fn ($roles) => $roles->whereRaw('LOWER(name) = ?', ['sale']));

        if ($user->hasRole('admin')) {
            return $query;
        }

        $teamId = (int) ($user->team_id ?? 0);
        if ($teamId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('user', fn ($sale) => $sale->where('team_id', $teamId));
    }

    private function monitoringUserOrFail(): User
    {
        if (!auth()->check()) {
            abort(401, 'Bạn cần đăng nhập để thao tác.');
        }

        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSalesFlowRole() && !$user->hasRole('director') && !$user->hasPermission('orders.monitoring')) {
            abort(403, 'Bạn không có quyền thao tác tại trang theo dõi đơn hàng.');
        }

        return $user;
    }

    private function applyMonitoringOrderFilters(Builder $query, Request $request): void
    {
        try {
            $selectedDate = Carbon::parse(
                $request->input('date', $request->input('from_date', now()->toDateString()))
            )->toDateString();
        } catch (\Throwable) {
            $selectedDate = now()->toDateString();
        }

        $query->whereDate('created_at', $selectedDate);

        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('shipper', fn ($shipperQuery) => $shipperQuery->where('name', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ((int) $request->input('sale_id', 0) > 0) {
            $query->where('user_id', (int) $request->input('sale_id'));
        }

        if ((int) $request->input('customer_id', 0) > 0) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }
    }

    private function applyCurrentApprovalStepScope(Builder $query, $roleNames): Builder
    {
        if ($roleNames->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($sub) use ($roleNames) {
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
    }

    private function applyMonitoringApprovalScope(Builder $query, $roleNames): Builder
    {
        $query->where('status', '!=', Order::STATUS_CANCELLED);

        return $this->applyCurrentApprovalStepScope($query, $roleNames);
    }

    private function monitoringHasPendingLeaderApprovals(Request $request): bool
    {
        $leaderRequest = $request->duplicate($request->query->all(), $request->request->all());
        $leaderRequest->query->remove('status');
        $leaderRequest->request->remove('status');

        $query = Order::query();
        $this->applyMonitoringOrderFilters($query, $leaderRequest);

        $this->applyMonitoringApprovalScope($query, $this->monitoringLeaderRoleNames());

        return $query->exists();
    }

    private function monitoringLeaderRoleNames()
    {
        return collect(['leader', 'leader_sale', 'sale_manager']);
    }

    private function applyTeamOrderFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status') && $request->input('status') !== Order::STATUS_REJECTED) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }

    private function refreshMissingDailySequencesFromQuery(Builder $query, string $scope)
    {
        $missingOrders = (clone $query)
            ->whereNull('daily_sequence')
            ->get(['id', 'created_at']);

        if ($missingOrders->isEmpty()) {
            return back()->with('success', "Không có đơn {$scope} nào thiếu số thứ tự ưu tiên.");
        }

        $dates = $missingOrders
            ->map(fn (Order $order) => optional($order->created_at)->toDateString())
            ->filter()
            ->unique()
            ->values();

        foreach ($dates as $date) {
            app(OrderController::class)->syncDailySequenceAndStockSufficiency($date);
        }

        return back()->with(
            'success',
            'Đã cập nhật lại số thứ tự ưu tiên cho ' . $missingOrders->count() . " đơn {$scope} thiếu số."
        );
    }

    private function approveOrdersFromQuery(Builder $query, User $user, ApprovalService $approvalService, string $note): array
    {
        $approvedCount = 0;
        $failedCount = 0;

        foreach ($query->latest()->get() as $order) {
            try {
                if (!$approvalService->canApproveCurrentStep($order, $user)) {
                    $failedCount++;
                    continue;
                }

                $approvalService->approve($order, $user, $note);
                app(OrderAutoApprovalService::class)->processOrder($order);
                $approvedCount++;
            } catch (\Throwable) {
                $failedCount++;
            }
        }

        return ['approved' => $approvedCount, 'failed' => $failedCount];
    }

    private function redirectAfterApproveAll(array $result, string $scope)
    {
        if ($result['approved'] === 0) {
            return back()->with('error', "Không có đơn {$scope} nào đang tới lượt bạn duyệt.");
        }

        $message = "Đã duyệt tất cả {$result['approved']} đơn {$scope}";
        if ($result['failed'] > 0) {
            $message .= ", {$result['failed']} đơn không thể duyệt.";
        } else {
            $message .= '.';
        }

        return back()->with('success', $message);
    }
    /**
 * API: Get all active truck routes with brand and stops for customer create page
 */
public function apiTruckRoutes(Request $request)
{
    $routes = \App\Models\TruckRoute::with([
        'brand',
        'stops.station.province',
        'stops.station.ward',
    ])
    ->where('is_active', true)
    ->get();

    $result = $routes->map(function ($route) {
        return [
            'id' => $route->id,
            'name' => $route->name,

            'brand' => $route->brand ? [
                'id' => $route->brand->id,
                'name' => $route->brand->name,
            ] : null,

            'stops' => $route->stops->map(function ($stop) {
                return [
                    'id' => $stop->id,
                    'sort_order' => $stop->sort_order,
                    'arrival_time' => $stop->arrival_time,

                    'station' => $stop->station ? [
                        'id' => $stop->station->id,
                        'name' => $stop->station->name,
                        'address' => $stop->station->address,
                        'phone' => $stop->station->phone,

                        'province' => $stop->station->province ? [
                            'id' => $stop->station->province->id,
                            'name' => $stop->station->province->name,
                        ] : null,

                        'ward' => $stop->station->ward ? [
                            'id' => $stop->station->ward->id,
                            'name' => $stop->station->ward->name,
                        ] : null,

                    ] : null,

                    'note' => $stop->note,
                ];
            })->values(),

            'current_price' => $route->current_price,
            'description' => $route->description,
            'note' => $route->note,
        ];
    });

    return response()->json($result);
}
    public function teamOrderDetail(Order $order)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem chi tiết đơn hàng.');
        }

        $user = auth()->user();

        $order->load([
            'customer.truckStation',
            'user.roles',
            'user.team',
            'items.variant.product',
            'approvals.step',
        ]);

        $isAdmin = $user->hasRole('admin');
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('director');
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager');
        $isSale = $user->hasRole('sale');

        if (!$isAdmin && !$isManager && !$isLeader && !$isSale) {
            abort(403, 'Bạn không có quyền truy cập trang chi tiết đơn hàng này.');
        }

        $creatorRoles = $order->user?->roles
            ?->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values() ?? collect();

        $isAllowed = false;

        if ($isAdmin) {
            $isAllowed = true;
        } elseif ($isManager) {
            $allowedCreatorRoles = ['sale', 'leader', 'leader_sale', 'sale_manager'];
            $isAllowed = $creatorRoles->intersect($allowedCreatorRoles)->isNotEmpty();
        } elseif ($isLeader) {
            $sameTeam = (int) ($order->user?->team_id ?? 0) === (int) ($user->team_id ?? -1);
            $isAllowed = $sameTeam && $creatorRoles->contains('sale');
        } elseif ($isSale) {
            $isAllowed = (int) $order->user_id === (int) $user->id;
        }

        if (!$isAllowed) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        $roleNames = $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->values();

        $currentStep = $order->approvals
            ->where('status', 'pending')
            ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
            ->first();

        $canApprove = false;
        if ($currentStep?->step) {
            $requiredRole = strtolower((string) $currentStep->step->role_slug);
            $canApprove = $roleNames->contains($requiredRole);
        }

        $customer = $order->customer;
        $customerDebt = null;
        if ($customer) {
            $customerDebt = \App\Models\Order::where('customer_id', $customer->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->selectRaw('SUM(COALESCE(total,0)) as grand_total, SUM(COALESCE(amount_due,0)) as total_due')
                ->first();
            if ($customerDebt) {
                $customerDebt->total_paid = max(0, (float) $customerDebt->grand_total - (float) $customerDebt->total_due);
            }
        }

        return view('site.orders.team_detail', [
            'settings' => $this->settings,
            'order' => $order,
            'currentStep' => $currentStep,
            'canApprove' => $canApprove,
            'customerDebt' => $customerDebt,
        ]);
    }

    public function teamOrderCustomerOrders(Order $order, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $customerId = $order->customer_id;
        if (!$customerId) {
            return response()->json(['rows' => [], 'total' => 0]);
        }

        $limit  = min((int) ($request->input('limit', 10)), 50);
        $from   = $request->input('from');
        $to     = $request->input('to');

        $query = \App\Models\Order::where('customer_id', $customerId)
            ->orderByDesc('created_at');

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->limit($limit)
            ->get(['id', 'code', 'total', 'amount_due', 'payment_status', 'status', 'created_at']);

        $rows = $orders->map(function ($o) {
            $payText = match ((string) $o->payment_status) {
                'paid'    => 'Đã TT',
                'partial' => 'Một phần',
                default   => 'Chưa TT',
            };
            $payClass = match ((string) $o->payment_status) {
                'paid'    => 'success',
                'partial' => 'warning',
                default   => 'secondary',
            };
            return [
                'id'             => $o->id,
                'code'           => $o->code ?: ('#' . $o->id),
                'total'          => number_format((float) $o->total, 0, ',', '.'),
                'amount_due'     => number_format((float) $o->amount_due, 0, ',', '.'),
                'pay_text'       => $payText,
                'pay_class'      => $payClass,
                'status'         => $o->status,
                'created_at'     => optional($o->created_at)->format('d/m/Y'),
            ];
        });

        return response()->json(['rows' => $rows]);
    }

    public function myCustomer(Request $request)
    {
        $userId = auth()->id();

        $tab = (string) $request->input('tab', 'all');
        if (!in_array($tab, ['all', 'processing', 'trash'], true)) {
            $tab = 'all';
        }

        $baseQuery = Customer::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhere('current_owner_sale_id', $userId)
                    ->orWhereHas('priorities', function ($priorityQuery) use ($userId) {
                        $priorityQuery->where('sale_id', $userId)
                            ->where('is_active', true);
                    });
            })
            ->where(function ($q) {
                $q->where('is_employee', '<>', 1)->orWhereNull('is_employee');
            });

        $customerQuery = clone $baseQuery;
        if ($tab === 'trash') {
            $customerQuery->onlyTrashed();
        } else {
            $customerQuery->whereNull('deleted_at');
            if ($tab === 'processing') {
                $customerQuery->whereIn('status', ['active', 'processing']);
            }
        }

        $cityFilter = $request->input('city');
        $wardFilter = $request->input('ward');
        $streetFilter = $request->input('street');
        $allowedSorts = ['production', 'size', 'delivery_time'];
        $sortByInput = (string) $request->input('sort_by', '');
        $sortBy = in_array($sortByInput, $allowedSorts, true) ? $sortByInput : null;
        $sortDirInput = strtolower((string) $request->input('sort_dir', 'asc'));
        $sortDir = in_array($sortDirInput, ['asc', 'desc'], true) ? $sortDirInput : 'asc';

        $customers = (clone $customerQuery)
            ->withCount('orders')
            ->withSum('orders as total_debt', 'amount_due')
            ->with([
                'type',
                'currentOwner:id,name',
                'assignedTo:id,name',
                'user:id,name',
                'priorities' => function ($priorityQuery) use ($userId) {
                    $priorityQuery->where('sale_id', $userId)
                        ->where('is_active', true)
                        ->orderBy('priority_level')
                        ->orderByDesc('updated_at');
                },
                'addresses' => function ($q) {
                    $q->where('is_default', true)->orWhere('is_default', null)->limit(1);
                },
                'truckStation',
                'truckRoute.brand',
                'truckRoute.stops.station',
            ])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($searchQuery) use ($s) {
                    $searchQuery->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->when($cityFilter || $wardFilter || $streetFilter, function ($q) use ($cityFilter, $wardFilter, $streetFilter) {
                $q->whereHas('addresses', function ($addressQuery) use ($cityFilter, $wardFilter, $streetFilter) {
                    if ($cityFilter) {
                        $addressQuery->where('city', $cityFilter);
                    }
                    if ($wardFilter) {
                        $addressQuery->where('ward', $wardFilter);
                    }
                    if ($streetFilter) {
                        $addressQuery->where('street', $streetFilter);
                    }
                });
            })
            ->when($sortBy, function ($q) use ($sortBy, $sortDir) {
                $q->orderBy($sortBy, $sortDir);
            })
            ->when(!$sortBy, fn ($q) => $this->applyCustomerPinnedSort($q))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));

        $upcomingReminders = CustomerReminder::with('customer')
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->where('is_done', false)
            ->whereNotNull('remind_at')
            ->where('remind_at', '>=', now())
            ->orderBy('remind_at')
            ->limit(3)
            ->get();

        $latestCareLog = CustomerCareLog::with('customer')
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->orderByDesc('created_at')
            ->first();

        $locationAddresses = CustomerAddress::query()
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->get(['city', 'ward', 'street', 'customer_id']);

        $locationTree = $locationAddresses
            ->groupBy('city')
            ->map(function ($cityGroup) {
                $cityCount = $cityGroup->pluck('customer_id')->unique()->count();
                return [
                    'customer_count' => $cityCount,
                    'wards' => $cityGroup->groupBy('ward')->map(function ($wardGroup) {
                        $wardCount = $wardGroup->pluck('customer_id')->unique()->count();
                        return [
                            'customer_count' => $wardCount,
                            'streets' => $wardGroup->groupBy('street')->map(function ($streetGroup) {
                                return [
                                    'street' => $streetGroup->first()->street,
                                    'customer_count' => $streetGroup->pluck('customer_id')->unique()->count(),
                                ];
                            }),
                        ];
                    }),
                ];
            });

        $selectedAreaCustomerCount = (clone $customerQuery)
            ->when($cityFilter || $wardFilter || $streetFilter, function ($q) use ($cityFilter, $wardFilter, $streetFilter) {
                $q->whereHas('addresses', function ($addressQuery) use ($cityFilter, $wardFilter, $streetFilter) {
                    if ($cityFilter) {
                        $addressQuery->where('city', $cityFilter);
                    }
                    if ($wardFilter) {
                        $addressQuery->where('ward', $wardFilter);
                    }
                    if ($streetFilter) {
                        $addressQuery->where('street', $streetFilter);
                    }
                });
            })
            ->count();

        $tabCounts = [
            'all' => (clone $baseQuery)->whereNull('deleted_at')->count(),
            'processing' => (clone $baseQuery)->whereNull('deleted_at')->whereIn('status', ['active', 'processing'])->count(),
            'trash' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        return view('site.my_customer.index', [
            'customers' => $customers,
            'search' => $request->search,
            'activeTab' => $tab,
            'tabCounts' => $tabCounts,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'settings' => $this->settings,
            'locationTree' => $locationTree,
            'upcomingReminders' => $upcomingReminders,
            'latestCareLog' => $latestCareLog,
            'selectedAreaCustomerCount' => $selectedAreaCustomerCount,
        ]);
    }


    public function myCustomerAjax(Request $request)
    {
        $userId = auth()->id();

        // DEBUG: Kiểm tra khách hàng 46 có trong bảng customers không
        \Log::info('DEBUG_CUSTOMER_46', [
            'customer' => Customer::withTrashed()->find(46)
        ]);

        // DEBUG: Kiểm tra có bản ghi customer_priorities đúng không
        \Log::info('DEBUG_PRIORITY_46', [
            'priority' => \App\Models\CustomerPriority::where('customer_id', 46)
                ->where('sale_id', $userId)
                ->where('is_active', 1)
                ->first()
        ]);

        // DEBUG: Kiểm tra có lọt qua whereHas không
        \Log::info('DEBUG_WHEREHAS_46', [
            'whereHas' => Customer::whereHas('priorities', function ($q) use ($userId) {
                $q->where('sale_id', $userId)
                  ->where('is_active', 1);
            })
            ->where('id', 46)
            ->first()
        ]);

        // DEBUG: Kiểm tra có lọt qua các filter khác không
        \Log::info('DEBUG_FINAL_46', [
            'final' => Customer::whereHas('priorities', function ($q) use ($userId) {
                $q->where('sale_id', $userId)
                  ->where('is_active', 1);
            })
            ->where('id', 46)
            ->whereNull('deleted_at')
            ->first()
        ]);


        $tab = (string) $request->input('tab', 'all');
        if (!in_array($tab, ['all', 'processing', 'trash'], true)) {
            $tab = 'all';
        }

        // Đồng nhất: baseQuery là query gốc cho cả danh sách và thống kê
        $baseQuery = Customer::query()
                        ->whereHas('priorities', function ($q) use ($userId) {
                                $q->where('sale_id', $userId)
                                    ->where('is_active', 1);
                        })
                        ->where(function ($q) {
                                $q->where('is_employee', '<>', 1)->orWhereNull('is_employee');
                        });

        if ($tab === 'trash') {
            $baseQuery->onlyTrashed();
        } else {
            $baseQuery->whereNull('deleted_at');
            if ($tab === 'processing') {
                $baseQuery->whereIn('status', ['active', 'processing']);
            }
        }

        $cityFilter = $request->input('city');
        $wardFilter = $request->input('ward');
        $streetFilter = $request->input('street');
        $allowedSorts = ['production', 'size', 'delivery_time'];
        $sortByInput = (string) $request->input('sort_by', '');
        $sortBy = in_array($sortByInput, $allowedSorts, true) ? $sortByInput : null;
        $sortDirInput = strtolower((string) $request->input('sort_dir', 'asc'));
        $sortDir = in_array($sortDirInput, ['asc', 'desc'], true) ? $sortDirInput : 'asc';

        // Dùng baseQuery cho danh sách
        $customers = (clone $baseQuery)
            ->withCount('orders')
            ->withSum('orders as total_debt', 'amount_due')
            ->with([
                'type',
                'currentOwner:id,name',
                'assignedTo:id,name',
                'user:id,name',
                'priorities' => function ($priorityQuery) use ($userId) {
                    $priorityQuery->where('sale_id', $userId)
                        ->where('is_active', 1)
                        ->orderBy('priority_level')
                        ->orderByDesc('updated_at');
                },
                'addresses' => function ($q) {
                    $q->where('is_default', true)->orWhere('is_default', null)->limit(1);
                },
                'truckStation',
                'truckRoute.brand',
                'truckRoute.stops.station',
            ])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($searchQuery) use ($s) {
                    $searchQuery->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->when($cityFilter || $wardFilter || $streetFilter, function ($q) use ($cityFilter, $wardFilter, $streetFilter) {
                $q->whereHas('addresses', function ($addressQuery) use ($cityFilter, $wardFilter, $streetFilter) {
                    if ($cityFilter) {
                        $addressQuery->where('city', $cityFilter);
                    }
                    if ($wardFilter) {
                        $addressQuery->where('ward', $wardFilter);
                    }
                    if ($streetFilter) {
                        $addressQuery->where('street', $streetFilter);
                    }
                });
            })
            ->when($sortBy, function ($q) use ($sortBy, $sortDir) {
                $q->orderBy($sortBy, $sortDir);
            })
            ->when(!$sortBy, fn ($q) => $this->applyCustomerPinnedSort($q))
            // Sắp xếp theo priority_level (ưu tiên) tăng dần, sau đó theo updated_at của priority giảm dần, ưu tiên ID 46
            ->orderByRaw('CASE WHEN customers.id = 46 THEN 0 ELSE 1 END')
            ->orderByRaw('(
                SELECT priority_level FROM customer_priorities
                WHERE customer_id = customers.id AND sale_id = ? AND is_active = 1
                ORDER BY priority_level ASC, updated_at DESC LIMIT 1
            ) ASC', [$userId])
            ->orderByRaw('(
                SELECT updated_at FROM customer_priorities
                WHERE customer_id = customers.id AND sale_id = ? AND is_active = 1
                ORDER BY priority_level ASC, updated_at DESC LIMIT 1
            ) DESC', [$userId])
            ->orderByDesc('customers.id')
            ->paginate($request->input('per_page', 10));

        $customers->getCollection()->transform(function ($customer) {
            $addressText = $customer->address ?: '';
            if (!$addressText && $customer->addresses->first()) {
                $address = $customer->addresses->first();
                $parts = array_filter([$address->house_number, $address->street, $address->ward, $address->city]);
                $addressText = implode(', ', $parts);
            }
            $myPriority = $customer->priorities->first();

            // Ensure AJAX response always has route data for rendering stops on cards.
            $route = $customer->truckRoute;
            if (!$route && $customer->truck_station_id) {
                $route = $customer->truckRouteByStation;
            }

            $customer->address_text = $addressText;
            $customer->updated_at_formatted = $customer->updated_at ? $customer->updated_at->format('d/m/Y') : null;
            $customer->deleted_at_formatted = $customer->deleted_at ? $customer->deleted_at->format('d/m/Y H:i') : null;
            $customer->total_debt = $customer->total_debt ?: 0;
            $customer->my_priority_level = $myPriority?->priority_level;
            $customer->my_priority_score = $myPriority?->care_score;
            $customer->my_priority_expire_at = $myPriority?->expire_date?->format('d/m/Y');
            $customer->current_owner_name = $customer->currentOwner?->name
                ?? $customer->assignedTo?->name
                ?? $customer->user?->name;
            $customer->is_free_customer = (string) $customer->customer_status === 'free' || $customer->isFree();
            $customer->is_pinned = (bool) ($customer->is_pinned ?? false);
            $customer->sort_order = (int) ($customer->sort_order ?? 0);
            $customer->truck_route = $route ? $route->toArray() : null;
            $customer->truck_station = $customer->truckStation ? $customer->truckStation->toArray() : null;
            return $customer;
        });

        $tabCounts = [
            'all' => (clone $baseQuery)->whereNull('deleted_at')->count(),
            'processing' => (clone $baseQuery)->whereNull('deleted_at')->whereIn('status', ['active', 'processing'])->count(),
            'trash' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        return response()->json([
            'active_tab' => $tab,
            'tab_counts' => $tabCounts,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'links' => $customers->links()->toHtml(),
            ],
        ]);
    }

    public function myCustomerCreate()
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $truckStations = TruckStation::query()
            ->where('is_active', true)
            ->with(['province:id,name', 'ward:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'ward_id']);

        return view('site.my_customer.create', [
            'settings' => $this->settings,
            'provinces' => $provinces,
            'truckStations' => $truckStations,
        ]);
    }

    public function myCustomerCheckDuplicate(Request $request)
    {
        $name  = trim($request->input('name', ''));
        $phone = trim($request->input('phone', ''));

        $hasNamePhone = $name !== '' && $phone !== '';

        if (!$hasNamePhone) {
            return response()->json(['duplicate' => false]);
        }

        $duplicate = \App\Models\Customer::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('phone', $phone)
            ->with([
                'user:id,name',
                'assignedTo:id,name',
                'currentOwner:id,name',
                'addresses' => function ($query) {
                    $query->orderByDesc('is_default')->orderByDesc('id');
                },
            ])
            ->first([
                'id',
                'name',
                'phone',
                'email',
                'address',
                'delivery_time',
                'size',
                'production',
                'company_name',
                'tax_code',
                'company_address',
                'company_email',
                'use_truck_station',
                'truck_station_id',
                'truck_station_address',
                'truck_station_phone',
                'truck_receive_time',
                'truck_return_time',
                'truck_fee',
                'user_id',
                'assigned_to',
                'current_owner_sale_id',
                'customer_status',
                'free_from_date',
                'created_at',
            ]);

        if (!$duplicate) {
            return response()->json(['duplicate' => false]);
        }

        $saleName  = $duplicate->currentOwner?->name
            ?? $duplicate->assignedTo?->name
            ?? $duplicate->user?->name
            ?? 'Không rõ';
        $createdAt = $duplicate->created_at
            ? $duplicate->created_at->format('d/m/Y H:i')
            : '';
        $freeFromDate = $duplicate->free_from_date
            ? $duplicate->free_from_date->format('d/m/Y H:i')
            : '';
        $defaultAddress = $duplicate->addresses->firstWhere('is_default', 1) ?: $duplicate->addresses->first();
        $isFree = (string) $duplicate->customer_status === 'free' || $duplicate->isFree();

        return response()->json([
            'duplicate'    => true,
            'id'           => $duplicate->id,
            'name'         => $duplicate->name,
            'phone'        => $duplicate->phone,
            'email'        => $duplicate->email,
            'sale'         => $saleName,
            'created_at'   => $createdAt,
            'is_free'      => $isFree,
            'free_from_date' => $freeFromDate,
            'customer_status' => (string) $duplicate->customer_status,
            'prefill'      => [
                'name' => $duplicate->name,
                'phone' => $duplicate->phone,
                'email' => $duplicate->email,
                'address' => $defaultAddress?->note ?: $duplicate->address,
                'province_id' => $defaultAddress?->province_id,
                'ward_id' => $defaultAddress?->ward_id,
                'delivery_time' => $duplicate->delivery_time,
                'size' => $duplicate->size,
                'production' => $duplicate->production,
                'company_name' => $duplicate->company_name,
                'tax_code' => $duplicate->tax_code,
                'company_address' => $duplicate->company_address,
                'company_email' => $duplicate->company_email,
                'use_truck_station' => (bool) $duplicate->use_truck_station,
                'truck_station_id' => $duplicate->truck_station_id,
                'truck_station_address' => $duplicate->truck_station_address,
                'truck_station_phone' => $duplicate->truck_station_phone,
                'truck_receive_time' => $duplicate->truck_receive_time,
                'truck_return_time' => $duplicate->truck_return_time,
                'truck_fee' => $duplicate->truck_fee,
            ],
        ]);
    }

    private function ensureManagedCustomer(Customer $customer): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasRole(['admin', 'manager', 'manager_sale', 'director'])) {
            return;
        }

        $canManage = (int) $customer->user_id === (int) $user->id
            || (int) $customer->assigned_to === (int) $user->id
            || (int) $customer->current_owner_sale_id === (int) $user->id
            || $customer->priorities()
                ->where('sale_id', $user->id)
                ->where('is_active', true)
                ->exists();

        if (!$canManage && $user->hasRole(['leader', 'leader_sale', 'sale_manager'])) {
            $ownerIds = collect([
                $customer->current_owner_sale_id,
                $customer->assigned_to,
                $customer->user_id,
            ])->filter()->map(fn ($id) => (int) $id)->unique();
            $canManage = (int) ($user->team_id ?? 0) > 0
                && User::query()
                    ->whereIn('id', $ownerIds->all())
                    ->where('team_id', $user->team_id)
                    ->exists();
        }

        if (!$canManage) {
            abort(403, 'Bạn không có quyền cập nhật khách hàng này.');
        }
    }

    public function myCustomerEdit(Customer $customer)
    {
        $this->ensureManagedCustomer($customer);
        $customer->load('addresses');
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        // Không load nhà xe (truckStations) ở đây nữa
        return view('site.my_customer.edit', [
            'customer' => $customer,
            'settings' => $this->settings,
            'provinces' => $provinces,
        ]);
    }

    public function myCustomerUpdate(Request $request, Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        // Nếu có care_note thì tạo log mới, không validate name
        if ($request->filled('care_note')) {
            $request->validate([
                'care_note' => ['required', 'string', 'max:2000'],
            ]);
            app(CustomerPriorityService::class)->addCareAction(
                customer: $customer,
                saleId: (int) auth()->id(),
                actionType: 'note',
                note: $request->input('care_note')
            );
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Đã thêm tình trạng/nhật ký chăm sóc!']);
            }
            return redirect()->route('my_customer.show', $customer)->with('success', 'Đã thêm tình trạng/nhật ký chăm sóc!');
        }

        // Nếu không, cập nhật thông tin cơ bản
        $rules = [];
        if ($request->has('name')) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }
        if ($request->has('email')) {
            $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)];
        }
        if ($request->has('phone')) {
            $rules['phone'] = ['nullable', 'string', 'max:50'];
        }
        if ($request->has('address')) {
            $rules['address'] = ['nullable', 'string', 'max:1000'];
        }
        if ($request->has('delivery_time')) {
            $rules['delivery_time'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('size')) {
            $rules['size'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('production')) {
            $rules['production'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('brand')) {
            $rules['brand'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('tax_code')) {
            $rules['tax_code'] = ['nullable', 'string', 'max:50'];
        }
        if ($request->has('company_name')) {
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('company_address')) {
            $rules['company_address'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('company_email')) {
            $rules['company_email'] = ['nullable', 'email', 'max:255'];
        }
        if ($request->has('customer_code')) {
            $rules['customer_code'] = ['nullable', 'string', 'max:50'];
        }
        if ($request->has('use_truck_station')) {
            $rules['use_truck_station'] = ['nullable', 'boolean'];
        }
        if ($request->has('truck_station_id')) {
            $rules['truck_station_id'] = ['nullable', 'exists:truck_stations,id'];
        }
        if ($request->has('truck_route_id')) {
            $rules['truck_route_id'] = ['nullable', 'exists:truck_routes,id'];
        }
        if ($request->has('truck_station_address')) {
            $rules['truck_station_address'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('truck_station_phone')) {
            $rules['truck_station_phone'] = ['nullable', 'string', 'max:30'];
        }
        if ($request->has('truck_fee')) {
            $rules['truck_fee'] = ['nullable', 'integer'];
        }
        if ($request->has('truck_receive_time')) {
            $rules['truck_receive_time'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('truck_return_time')) {
            $rules['truck_return_time'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('province_id')) {
            $rules['province_id'] = ['nullable', 'exists:provinces,id'];
        }
        if ($request->has('ward_id')) {
            $rules['ward_id'] = ['nullable', 'exists:wards,id'];
        }

        $validated = $request->validate($rules);

        if (array_key_exists('use_truck_station', $validated) && !(bool) $validated['use_truck_station']) {
            $validated['truck_station_id'] = null;
        }

        if (!empty($validated['ward_id']) && !empty($validated['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($validated['ward_id'])
                ->where('province_id', $validated['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $selectedProvinceId = $validated['province_id'] ?? null;
        $selectedWardId = $validated['ward_id'] ?? null;
        unset($validated['province_id'], $validated['ward_id']);

        $customer->update($validated);

        if ($request->hasAny(['address', 'province_id', 'ward_id'])) {
            $province = $selectedProvinceId ? Province::find($selectedProvinceId) : null;
            $ward = $selectedWardId ? Ward::find($selectedWardId) : null;

            $customer->addresses()->updateOrCreate(
                ['is_default' => 1],
                [
                    'note' => $validated['address'] ?? $customer->address,
                    'city' => $province?->name,
                    'ward' => $ward?->name,
                    'province_id' => $province?->id,
                    'ward_id' => $ward?->id,
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật thông tin khách hàng thành công.']);
        }
        return redirect()->route('pages.my_customer')->with('success', 'Đã cập nhật thông tin khách hàng thành công.');
    }

    public function myCustomerDestroy(Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        $customer->status = 'archived';
        $customer->deleted_by = auth()->id();
        $customer->save();
        $customer->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa khách hàng.']);
        }

        return redirect()->route('pages.my_customer')
            ->with('success', 'Đã xóa khách hàng "' . $customer->name . '".');
    }

    public function myCustomerBulkDelete(Request $request)
    {
        $ids = array_filter(
            array_map('intval', explode(',', (string) $request->input('_ids', '')))
        );

        if (empty($ids)) {
            return back()->withErrors(['ids' => 'Vui lòng chọn ít nhất một khách hàng.']);
        }

        $userId = auth()->id();

        // Chỉ cho phép xóa khách hàng thuộc user hiện tại
        $customers = Customer::query()
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->get();

        $deleted = 0;
        foreach ($customers as $customer) {
            $customer->status = 'archived';
            $customer->deleted_by = $userId;
            $customer->save();
            $customer->delete();
            $deleted++;
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'deleted' => $deleted]);
        }

        return redirect()->route('pages.my_customer')
            ->with('success', "Đã xóa {$deleted} khách hàng.");
    }

    public function myCustomerRestore(int $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);
        $this->ensureManagedCustomer($customer);

        if ($customer->trashed()) {
            $customer->restore();
            $customer->status = 'active';
            $customer->deleted_by = null;
            $customer->save();
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã khôi phục khách hàng.']);
        }

        return redirect()->route('pages.my_customer', ['tab' => 'trash'])
            ->with('success', 'Đã khôi phục khách hàng "' . $customer->name . '".');
    }

    public function myCustomerForceDelete(int $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);
        $this->ensureManagedCustomer($customer);

        if (!$customer->trashed()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Chỉ xóa vĩnh viễn khách hàng trong thùng rác.'], 422);
            }

            return redirect()->route('pages.my_customer')
                ->with('error', 'Chỉ xóa vĩnh viễn khách hàng trong thùng rác.');
        }

        $name = (string) $customer->name;
        $customer->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa vĩnh viễn khách hàng.']);
        }

        return redirect()->route('pages.my_customer', ['tab' => 'trash'])
            ->with('success', 'Đã xóa vĩnh viễn khách hàng "' . $name . '".');
    }

    public function myCustomerTakeover(Request $request, Customer $customer)
    {
        $user = auth()->user();

        if (!$customer->isFree() && (string) $customer->customer_status !== 'free') {
            return response()->json(['success' => false, 'message' => 'Khách hàng này chưa tự do, không thể nhận.'], 422);
        }

        $priorityService = app(\App\Services\CustomerPriorityService::class);
        $priorityService->takeover($customer, $user->id, 'free_takeover');

        return response()->json(['success' => true, 'message' => 'Đã nhận khách hàng "' . $customer->name . '" về danh sách của bạn.']);
    }

    public function myCustomerSortSettings(Request $request, Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        $validated = $request->validate([
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $updates = [];
        if (array_key_exists('is_pinned', $validated) && Schema::hasColumn('customers', 'is_pinned')) {
            $updates['is_pinned'] = (bool) $validated['is_pinned'];
        }
        if (array_key_exists('sort_order', $validated) && Schema::hasColumn('customers', 'sort_order')) {
            $updates['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        }

        if ($updates) {
            $customer->forceFill($updates)->save();
        }

        $fresh = $customer->fresh();

        return response()->json([
            'success' => true,
            'is_pinned' => (bool) ($fresh->is_pinned ?? false),
            'sort_order' => (int) ($fresh->sort_order ?? 0),
        ]);
    }

    public function myCustomerImportForm()
    {
        $result = session('my_customer_import_result', []);

        $importedCustomerIds = collect($result['imported_customer_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $userId = auth()->id();
        $importedCustomers = collect();

        if ($importedCustomerIds->isNotEmpty()) {
            $importedCustomers = Customer::query()
                ->withCount('orders')
                ->withSum('orders as total_debt', 'amount_due')
                ->with(['type', 'addresses' => function ($q) {
                    $q->where('is_default', true)->orWhere('is_default', null)->limit(1);
                }])
                ->whereIn('id', $importedCustomerIds->all())
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhere('assigned_to', $userId);
                })
                ->orderByDesc('id')
                ->get();
        }

        return view('site.my_customer.import', [
            'settings' => $this->settings,
            'importResult' => [
                'imported_count' => (int) ($result['imported_count'] ?? 0),
                'duplicate_count' => (int) ($result['duplicate_count'] ?? 0),
                'failed_count' => (int) ($result['failed_count'] ?? 0),
                'duplicate_rows' => $result['duplicate_rows'] ?? [],
                'failed_rows' => $result['failed_rows'] ?? [],
            ],
            'importedCustomers' => $importedCustomers,
        ]);
    }

    public function myCustomerImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new \App\Imports\CustomerImportWithErrorReport();

        try {
            Excel::import($import, $request->file('file'));

            $validationFailures = collect($import->failures())->map(function ($failure) {
                return [
                    'row' => (int) $failure->row(),
                    'attribute' => (string) $failure->attribute(),
                    'errors' => (array) $failure->errors(),
                    'values' => (array) $failure->values(),
                ];
            });

            $runtimeFailures = collect($import->getImported())
                ->where('status', 'fail')
                ->values()
                ->map(function ($entry, $index) {
                    return [
                        'row' => null,
                        'attribute' => null,
                        'errors' => [(string) ($entry['error'] ?? 'Dữ liệu không hợp lệ.')],
                        'values' => (array) ($entry['row'] ?? []),
                        'runtime_index' => $index + 1,
                    ];
                });

            $allFailures = $validationFailures->concat($runtimeFailures)->values();

            $isDuplicateFailure = function (array $failure): bool {
                $messages = collect($failure['errors'] ?? [])->map(fn ($msg) => mb_strtolower((string) $msg));
                $joined = $messages->implode(' | ');

                return str_contains($joined, 'unique')
                    || str_contains($joined, 'already been taken')
                    || str_contains($joined, 'da ton tai')
                    || str_contains($joined, 'đã tồn tại')
                    || str_contains($joined, 'trùng');
            };

            $duplicateRows = $allFailures->filter($isDuplicateFailure)->values();
            $failedRows = $allFailures->reject($isDuplicateFailure)->values();

            $importedCustomerIds = collect($import->getImported())
                ->where('status', 'success')
                ->pluck('customer_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $result = [
                'imported_count' => count($importedCustomerIds),
                'duplicate_count' => $duplicateRows->count(),
                'failed_count' => $failedRows->count(),
                'imported_customer_ids' => $importedCustomerIds,
                'duplicate_rows' => $duplicateRows->all(),
                'failed_rows' => $failedRows->all(),
            ];

            $statusMessage = 'Import hoàn tất: ' . $result['imported_count'] . ' khách hàng mới';
            if ($result['duplicate_count'] > 0) {
                $statusMessage .= ', ' . $result['duplicate_count'] . ' dòng trùng';
            }
            if ($result['failed_count'] > 0) {
                $statusMessage .= ', ' . $result['failed_count'] . ' dòng lỗi';
            }

            return redirect()
                ->route('my_customer.import_form')
                ->with('success', $statusMessage)
                ->with('my_customer_import_result', $result);
        } catch (\Throwable $e) {
            return redirect()
                ->route('my_customer.import_form')
                ->with('error', 'Import thất bại: ' . $e->getMessage());
        }
    }

    public function myCustomerShow(Customer $customer, Request $request)
    {
        $this->ensureManagedCustomer($customer);

        $validated = $request->validate([
            'tab' => 'nullable|in:info,debt,orders,payments,reports',
            'period' => 'nullable|in:today,week,month,custom',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'order_status' => 'nullable|string|max:50',
            'sidebar_search' => 'nullable|string|max:100',
            'orders_per_page' => 'nullable|integer|min:5|max:100',
            'debt_per_page' => 'nullable|integer|min:5|max:100',
            'payments_per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $userId = auth()->id();
        $sidebarSearch = trim((string) ($validated['sidebar_search'] ?? ''));

        $customerList = Customer::query()
            ->withCount('orders')
            ->with(['truckStation:id,name,address', 'truckRoute:id,name'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->when($sidebarSearch !== '', function ($q) use ($sidebarSearch) {
                $q->where(function ($sub) use ($sidebarSearch) {
                    $sub->where('name', 'like', "%{$sidebarSearch}%")
                        ->orWhere('phone', 'like', "%{$sidebarSearch}%")
                        ->orWhere('email', 'like', "%{$sidebarSearch}%")
                        ->orWhere('address', 'like', "%{$sidebarSearch}%");
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        [$fromDate, $toDate] = $this->resolveMyCustomerDateRange(
            (string) ($validated['period'] ?? 'month'),
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        $customer->load(['type', 'assignedTo', 'addresses', 'reminders', 'truckStation', 'truckRoute']);
        // Load careLogs if implemented in future
        $customer->setRelation('careLogs', $customer->careLogs);

        $ordersBaseQuery = $customer->orders()->getQuery();

        if (!empty($validated['order_status'])) {
            $ordersBaseQuery->where('status', $validated['order_status']);
        }

        $ordersBaseQuery->whereBetween('created_at', [
            $fromDate->copy()->startOfDay(),
            $toDate->copy()->endOfDay(),
        ]);

        $ordersPerPage = (int) ($validated['orders_per_page'] ?? 10);
        $debtPerPage = (int) ($validated['debt_per_page'] ?? 10);
        $paymentsPerPage = (int) ($validated['payments_per_page'] ?? 10);

        $orders = (clone $ordersBaseQuery)
            ->with(['user', 'transactions'])
            ->latest()
            ->paginate($ordersPerPage, ['*'], 'orders_page')
            ->appends($request->query());

        $recentOrders = (clone $ordersBaseQuery)
            ->with(['transactions'])
            ->latest()
            ->limit(5)
            ->get();

        $debtOrders = (clone $ordersBaseQuery)
            ->with('transactions')
            ->latest()
            ->paginate($debtPerPage, ['*'], 'debt_page')
            ->appends($request->query());

        $paymentsBaseQuery = Transaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'payment')
            ->whereBetween('created_at', [
                $fromDate->copy()->startOfDay(),
                $toDate->copy()->endOfDay(),
            ]);

        $payments = (clone $paymentsBaseQuery)
            ->with('order')
            ->latest()
            ->paginate($paymentsPerPage, ['*'], 'payments_page')
            ->appends($request->query());

        $recentPayments = (clone $paymentsBaseQuery)
            ->with('order')
            ->latest()
            ->limit(5)
            ->get();

        $eventLogs = DB::table('admin_events')
            ->where('event_type', 'customer_payment')
            ->where('action', 'create')
            ->where('subject_type', $customer->getMorphClass())
            ->where('subject_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        $transactionActorIds = [];
        foreach ($eventLogs as $eventLog) {
            $metadata = json_decode((string) ($eventLog->metadata ?? '{}'), true);
            if (!empty($metadata['transaction_id'])) {
                $transactionActorIds[(int) $metadata['transaction_id']] = (int) ($eventLog->actor_id ?? 0);
            }
        }

        $actorNames = [];
        if (!empty($transactionActorIds)) {
            $actorNames = \App\Models\User::query()
                ->whereIn('id', array_values(array_unique($transactionActorIds)))
                ->pluck('name', 'id')
                ->toArray();
        }

        $filteredOrderCount = (clone $ordersBaseQuery)->count();
        $filteredOrderTotal = (float) (clone $ordersBaseQuery)->sum('total');
        $filteredSubtotalAmount = $this->hasOrderColumn('subtotal_amount')
            ? (float) (clone $ordersBaseQuery)->sum('subtotal_amount')
            : $filteredOrderTotal;
        $filteredItemDiscountTotal = $this->hasOrderColumn('item_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('item_discount_total')
            : 0.0;
        $filteredExtraDiscountTotal = $this->hasOrderColumn('extra_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('extra_discount_total')
            : ($this->hasOrderColumn('order_discount') ? (float) (clone $ordersBaseQuery)->sum('order_discount') : 0.0);
        $orderIdsSubQuery = (clone $ordersBaseQuery)->select('id');
        $filteredPaidTotal = (float) Transaction::query()
            ->whereIn('order_id', $orderIdsSubQuery)
            ->where('type', 'payment')
            ->sum('amount')
            - (float) Transaction::query()
                ->whereIn('order_id', (clone $ordersBaseQuery)->select('id'))
                ->where('type', 'refund')
                ->sum('amount');

        $allOrdersQuery = $customer->orders()->getQuery();
        $allOrderIdsSubQuery = (clone $allOrdersQuery)->select('id');
        $totalOrderAmount = (float) (clone $allOrdersQuery)->sum('total');
        $totalSubtotalAmount = $this->hasOrderColumn('subtotal_amount')
            ? (float) (clone $allOrdersQuery)->sum('subtotal_amount')
            : $totalOrderAmount;
        $totalItemDiscountAmount = $this->hasOrderColumn('item_discount_total')
            ? (float) (clone $allOrdersQuery)->sum('item_discount_total')
            : 0.0;
        $totalExtraDiscountAmount = $this->hasOrderColumn('extra_discount_total')
            ? (float) (clone $allOrdersQuery)->sum('extra_discount_total')
            : ($this->hasOrderColumn('order_discount') ? (float) (clone $allOrdersQuery)->sum('order_discount') : 0.0);
        $totalPaidAmount = (float) Transaction::query()
            ->whereIn('order_id', $allOrderIdsSubQuery)
            ->where('type', 'payment')
            ->sum('amount')
            - (float) Transaction::query()
                ->whereIn('order_id', (clone $allOrdersQuery)->select('id'))
                ->where('type', 'refund')
                ->sum('amount');
        $totalDebtAmount = max($totalOrderAmount - $totalPaidAmount, 0);

        $reportOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('created_at', [
                $fromDate->copy()->startOfDay(),
                $toDate->copy()->endOfDay(),
            ])
            ->with('transactions')
            ->orderBy('created_at')
            ->get();

        $reportByMonth = $reportOrders
            ->groupBy(fn (Order $order) => optional($order->created_at)->format('Y-m'))
            ->map(function ($monthlyOrders, $period) {
                $orderTotal = (float) $monthlyOrders->sum('total');
                $subtotalAmount = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->subtotal_amount ?? $order->total ?? 0);
                });
                $itemDiscountTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->item_discount_total ?? 0);
                });
                $extraDiscountTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->extra_discount_total ?? $order->order_discount ?? 0);
                });
                $paidTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) $order->transactions->where('type', 'payment')->sum('amount')
                        - (float) $order->transactions->where('type', 'refund')->sum('amount');
                });

                return [
                    'period' => $period,
                    'order_count' => (int) $monthlyOrders->count(),
                    'subtotal_amount' => $subtotalAmount,
                    'item_discount_total' => $itemDiscountTotal,
                    'extra_discount_total' => $extraDiscountTotal,
                    'order_total' => $orderTotal,
                    'paid_total' => $paidTotal,
                    'outstanding_total' => max($orderTotal - $paidTotal, 0),
                ];
            })
            ->sortBy('period')
            ->values();

        $orderStatuses = Order::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values();

        return view('site.my_customer.show', [
            'customer' => $customer,
            'orders' => $orders,
            'recentOrders' => $recentOrders,
            'debtOrders' => $debtOrders,
            'payments' => $payments,
            'recentPayments' => $recentPayments,
            'reportByMonth' => $reportByMonth,
            'transactionActorIds' => $transactionActorIds,
            'actorNames' => $actorNames,
            'totalOrderAmount' => $totalOrderAmount,
            'totalSubtotalAmount' => $totalSubtotalAmount,
            'totalItemDiscountAmount' => $totalItemDiscountAmount,
            'totalExtraDiscountAmount' => $totalExtraDiscountAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'totalDebtAmount' => $totalDebtAmount,
            'filteredOrderCount' => $filteredOrderCount,
            'filteredOrderTotal' => $filteredOrderTotal,
            'filteredSubtotalAmount' => $filteredSubtotalAmount,
            'filteredItemDiscountTotal' => $filteredItemDiscountTotal,
            'filteredExtraDiscountTotal' => $filteredExtraDiscountTotal,
            'filteredPaidTotal' => $filteredPaidTotal,
            'period' => (string) ($validated['period'] ?? 'month'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'activeTab' => (string) ($validated['tab'] ?? 'info'),
            'orderStatuses' => $orderStatuses,
            'customerList' => $customerList,
            'sidebarSearch' => $sidebarSearch,
            'settings' => $this->settings,
            // For Blade compatibility
            'reminders' => $customer->reminders,
            'careLogs' => $customer->careLogs,
        ]);
    }

    public function myCustomerStorePayment(Customer $customer, Request $request)
    {
        $this->ensureManagedCustomer($customer);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer',
            'note' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|image|max:5120',
            'period' => 'nullable|in:today,week,month,custom',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'order_status' => 'nullable|string|max:50',
            'orders_per_page' => 'nullable|integer|min:5|max:100',
            'debt_per_page' => 'nullable|integer|min:5|max:100',
            'payments_per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $allOrdersQuery = Order::query()->where('customer_id', $customer->id);
        $allOrderIdsSubQuery = (clone $allOrdersQuery)->select('id');
        $totalOrderAmount = (float) (clone $allOrdersQuery)->sum('total');
        $totalPaidAmount = (float) Transaction::query()
            ->whereIn('order_id', $allOrderIdsSubQuery)
            ->where('type', 'payment')
            ->sum('amount')
            - (float) Transaction::query()
                ->whereIn('order_id', (clone $allOrdersQuery)->select('id'))
                ->where('type', 'refund')
                ->sum('amount');

        $outstandingAmount = max($totalOrderAmount - $totalPaidAmount, 0);
        $amount = (float) $validated['amount'];

        if ($outstandingAmount <= 0) {
            return back()->withErrors(['amount' => 'Khach hang nay khong con cong no de thanh toan.'])->withInput();
        }

        if ($amount - $outstandingAmount > 0.0001) {
            return back()->withErrors(['amount' => 'So tien thanh toan khong duoc vuot qua cong no hien tai.'])->withInput();
        }

        $receiptImagePath = $request->hasFile('receipt_image')
            ? $request->file('receipt_image')->store('customer-payments/receipts', 'public')
            : null;

        $unpaidOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('transactions')
            ->orderBy('created_at')
            ->get();

        $remainingAmount = $amount;
        $createdTransactions = [];

        DB::transaction(function () use ($customer, $validated, $receiptImagePath, $unpaidOrders, &$remainingAmount, &$createdTransactions) {
            foreach ($unpaidOrders as $order) {
                if ($remainingAmount <= 0.0001) {
                    break;
                }

                $paid = (float) $order->transactions->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions->where('type', 'refund')->sum('amount');
                $outstanding = max((float) $order->total - $paid, 0);

                if ($outstanding <= 0.0001) {
                    continue;
                }

                $allocateAmount = min($remainingAmount, $outstanding);

                $transaction = Transaction::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'amount' => $allocateAmount,
                    'type' => 'payment',
                    'method' => $validated['method'],
                    'note' => $validated['note'] ?? null,
                    'receipt_image_path' => $receiptImagePath,
                ]);

                $createdTransactions[] = $transaction;

                $freshPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions()->where('type', 'refund')->sum('amount');

                $paymentStatus = 'unpaid';
                if ($freshPaid >= (float) $order->total) {
                    $paymentStatus = 'paid';
                } elseif ($freshPaid > 0) {
                    $paymentStatus = 'partial';
                }

                $order->update([
                    'amount_paid' => $freshPaid,
                    'amount_due' => max((float) $order->total - $freshPaid, 0),
                    'payment_status' => $paymentStatus,
                ]);

                AdminActivityService::record(
                    'customer_payment',
                    'create',
                    $customer,
                    'Tao thanh toan khach hang',
                    'Da ghi nhan thanh toan cho don #' . ($order->code ?: $order->id),
                    [
                        'transaction_id' => $transaction->id,
                        'order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'amount' => $allocateAmount,
                        'method' => $validated['method'],
                    ],
                    route('my_customer.show', ['customer' => $customer, 'tab' => 'payments'])
                );

                $remainingAmount -= $allocateAmount;
            }
        });

        if (empty($createdTransactions)) {
            return back()->withErrors(['amount' => 'Khong tim thay don hang con no de phan bo thanh toan.'])->withInput();
        }

        return redirect()->route('my_customer.show', [
            'customer' => $customer,
            'tab' => 'payments',
            'period' => $validated['period'] ?? $request->input('period', 'month'),
            'from_date' => $validated['from_date'] ?? $request->input('from_date'),
            'to_date' => $validated['to_date'] ?? $request->input('to_date'),
            'order_status' => $validated['order_status'] ?? $request->input('order_status'),
            'orders_per_page' => $validated['orders_per_page'] ?? $request->input('orders_per_page', 10),
            'debt_per_page' => $validated['debt_per_page'] ?? $request->input('debt_per_page', 10),
            'payments_per_page' => $validated['payments_per_page'] ?? $request->input('payments_per_page', 10),
        ])->with('success', 'Da ghi nhan thanh toan thanh cong.');
    }

    public function myCustomerOrderCreate(Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        $customer->load(['addresses' => function ($query) {
            $query->orderByDesc('is_default')->orderByDesc('id');
        }]);

        return view('site.my_customer.order_create', [
            'customer' => $customer,
            'settings' => $this->settings
        ]);
    }

    public function myCustomerOrderStore(Request $request, Customer $customer, ApprovalService $approvalService)
    {
        $this->ensureManagedCustomer($customer);
        $request->merge(['customer_id' => $customer->id]);

        // Đồng bộ nhanh thông tin chăm sóc khách từ form lên hồ sơ khách.
        $newAddress = trim((string) $request->input('recipient_address', ''));
        $newDeliveryTime = trim((string) $request->input('delivery_time', ''));
        $newNote = trim((string) $request->input('note', ''));

        $customerUpdates = [];

        if ($newAddress !== '' && $newAddress !== (string) ($customer->address ?? '')) {
            $customerUpdates['address'] = mb_substr($newAddress, 0, 1000);
        }

        if ($newDeliveryTime !== (string) ($customer->delivery_time ?? '')) {
            $customerUpdates['delivery_time'] = $newDeliveryTime !== '' ? mb_substr($newDeliveryTime, 0, 255) : null;
        }

        if ($newNote !== (string) ($customer->note ?? '')) {
            $customerUpdates['note'] = $newNote !== '' ? mb_substr($newNote, 0, 1000) : null;
        }

        if (!empty($customerUpdates)) {
            $customer->update($customerUpdates);
        }

        $request->merge(['allow_backorder' => true]);

        return app(\App\Http\Controllers\OrderController::class)->storeANewOrder($request, $approvalService);
    }

    public function myCustomerOrdersQuickView(Customer $customer)
    {
        $customer->load(['orders.items.product', 'orders.items.variant']);

        return view('site.my_customer._orders_quick_view', [
            'orders' => $customer->orders
        ]);
    }

    public function myOrderDetail(Order $order)
    {
        $settings = $this->settings;
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'customer',
            'items.variant.mediaLink.media',
            'items.variant.product.avatar.media',
        ]);

        return view('site.orders.show', compact('order', 'settings'));
    }

    public function myOrderEdit(Order $order)
    {
        $user = auth()->user();

        if (!$user || $order->user_id !== $user->id) {
            abort(403);
        }

        $isCopiedOrder = $this->hasOrderColumn('copied_from_order_id')
            && !empty($order->copied_from_order_id);

        $isEditable = $isCopiedOrder || $order->canBeDirectlyEditedByOwner();

        if (!$isEditable) {
            return redirect()->route('pages.my_orders.monitoring', [
                'date' => $order->created_at?->toDateString(),
            ])
                ->with('error', $order->canRequestAdjustment()
                    ? 'Đơn đã chốt doanh thu. Vui lòng dùng chức năng Gửi yêu cầu điều chỉnh.'
                    : 'Không thể sửa đơn đã giao hoặc đơn đã kết thúc.');
        }

        $order->load('items.variant.product', 'customer', 'parentOrder');

        return view('site.orders.edit', [
            'settings' => $this->settings,
            'order' => $order,
        ]);
    }

    public function myOrderUpdate(Request $request, Order $order)
    {
        $user = auth()->user();

        if (!$user || $order->user_id !== $user->id) {
            abort(403);
        }

        $isCopiedOrder = $this->hasOrderColumn('copied_from_order_id')
            && !empty($order->copied_from_order_id);
        $isReturnOrder = (bool) ($order->is_return_order ?? false)
            || (string) ($order->order_type ?? '') === 'order_return'
            || (string) ($order->workflow_code ?? '') === 'order_return';

        $isEditable = $isCopiedOrder || $order->canBeDirectlyEditedByOwner();

        if (!$isEditable) {
            return redirect()->route('pages.my_orders.monitoring', [
                'date' => $order->created_at?->toDateString(),
            ])
                ->with('error', $order->canRequestAdjustment()
                    ? 'Đơn đã chốt doanh thu. Vui lòng dùng chức năng Gửi yêu cầu điều chỉnh.'
                    : 'Không thể sửa đơn đã giao hoặc đơn đã kết thúc.');
        }

        $customerIds = $this->myAssignedCustomersQuery((int) $user->id)
            ->pluck('id')
            ->unique()
            ->values()
            ->toArray();

        $sanitizedItems = collect($request->input('items', []))
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $variantId = (int) ($item['variant_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    return null;
                }

                return [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $request->merge([
            'items' => $sanitizedItems,
        ]);

        $validated = $request->validate([
            'customer_id' => ['required', Rule::in($customerIds)],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_address' => ['required', 'string', 'max:1000'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'shipper_note' => ['nullable', 'string', 'max:1000'],
            'order_discount' => ['nullable', 'numeric', 'min:0'],
            'order_discount_type' => ['nullable', 'in:decrease,increase'],
            'warehouse_can_adjust' => ['nullable', 'boolean'],
            'item_discount' => ['nullable', 'array'],
            'item_discount.*' => ['nullable', 'numeric', 'min:0'],
            'item_discount_type' => ['nullable', 'array'],
            'item_discount_type.*' => ['nullable', 'in:decrease,increase'],
            'item_weight' => ['nullable', 'array'],
            'item_weight.*' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $itemsInput = collect($validated['items'] ?? [])->values();

        if ($itemsInput->isEmpty()) {
            return back()->withErrors([
                'items' => 'Don hang phai co it nhat 1 san pham.',
            ])->withInput();
        }

        $variantIds = $itemsInput->pluck('variant_id')->unique()->values()->all();
        $variants = ProductVariant::query()
            ->with(['latestPriceRule'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        foreach ($itemsInput as $item) {
            $variant = $variants->get($item['variant_id']);
            if (!$variant) {
                return back()->withErrors([
                    'items' => 'Co san pham khong ton tai trong don hang.',
                ])->withInput();
            }

            $basePrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
            $minPrice = max(0, (float) ($variant->latestPriceRule?->min_price ?? 0));
            $requestedUnitDiscount = max(0, (float) (($validated['item_discount'][(string) $variant->id] ?? 0)));
            $discountType = strtolower((string) ($validated['item_discount_type'][(string) $variant->id] ?? 'decrease'));

            if ($discountType !== 'increase') {
                $maxAllowedDecrease = max($basePrice - $minPrice, 0);
                if ($requestedUnitDiscount > $maxAllowedDecrease) {
                    return back()->withErrors([
                        'item_discount.' . $variant->id => 'Giá bán SKU ' . ($variant->sku ?: $variant->id) . ' không được thấp hơn giá Min (' . number_format($minPrice, 0, ',', '.') . 'đ).',
                    ])->withInput();
                }
            }
        }

        DB::transaction(function () use ($order, $validated, $itemsInput, $variants, $isCopiedOrder, $isReturnOrder): void {
            $order->items()->delete();

            $parseWeightToKg = static function ($size): float {
                $normalized = strtolower(str_replace(',', '.', trim((string) $size)));
                if ($normalized === '') {
                    return 0.0;
                }

                if (!preg_match('/([0-9]*\.?[0-9]+)/', $normalized, $matches)) {
                    return 0.0;
                }

                $weight = (float) ($matches[1] ?? 0);
                if ($weight <= 0) {
                    return 0.0;
                }

                if (str_contains($normalized, 'g') && !str_contains($normalized, 'kg')) {
                    $weight = $weight / 1000;
                }

                return round(max(0, $weight), 3);
            };

            $resolveKg = static function ($variant) use ($parseWeightToKg): float {
                $variantKg = (float) ($variant->kg ?? 0);
                if ($variantKg > 0) {
                    return $variantKg;
                }

                $productKg = (float) ($variant->product?->kg ?? 0);
                if ($productKg > 0) {
                    return $productKg;
                }

                $sizeKg = $parseWeightToKg($variant->size ?? null);
                if ($sizeKg > 0) {
                    return $sizeKg;
                }

                return 1.0;
            };

            $resolvePricedByKg = static function ($variant): bool {
                if ($variant->is_priced_by_kg !== null) {
                    return (bool) $variant->is_priced_by_kg;
                }

                return (bool) ($variant->product?->is_priced_by_kg ?? true);
            };

            $subtotalAmount = 0;
            $itemDiscountTotal = 0;
            $totalBeforeOrderDiscount = 0;
            $orderDiscountInput = max(0, (float) ($validated['order_discount'] ?? 0));
            $orderDiscountType = strtolower((string) ($validated['order_discount_type'] ?? 'decrease')) === 'increase'
                ? 'increase'
                : 'decrease';
            $itemDiscountInput = collect($validated['item_discount'] ?? []);
            $itemDiscountTypeInput = collect($validated['item_discount_type'] ?? []);
            $itemWeightInput = collect($validated['item_weight'] ?? []);

            foreach ($itemsInput as $item) {
                $variant = $variants->get($item['variant_id']);
                $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                $quantity = (int) $item['quantity'];
                $requestedUnitDiscount = max(0, (float) $itemDiscountInput->get((string) $variant->id, 0));
                $unitDiscountType = strtolower((string) $itemDiscountTypeInput->get((string) $variant->id, 'decrease')) === 'increase'
                    ? 'increase'
                    : 'decrease';
                $minPrice = max(0, (float) ($variant->latestPriceRule?->min_price ?? 0));

                $unitDiscount = $requestedUnitDiscount;
                if ($unitDiscountType === 'decrease') {
                    $unitDiscount = min($unitDiscount, max($price - $minPrice, 0));
                }

                $unitWeight = round(max(0.01, $resolveKg($variant)), 3);
                $isPricedByKg = $resolvePricedByKg($variant);
                $pricingFactor = $isPricedByKg ? $unitWeight : 1;
                $totalWeight = round($unitWeight * $quantity, 3);
                $lineSubtotal = round($price * $quantity * $pricingFactor, 2);
                $lineAdjustment = round(($unitDiscountType === 'increase' ? -1 : 1) * $unitDiscount * $quantity * $pricingFactor, 2);
                $finalUnitPrice = $unitDiscountType === 'increase'
                    ? ($price + $unitDiscount)
                    : ($price - $unitDiscount);
                $lineTotal = max(round($finalUnitPrice * $quantity * $pricingFactor, 2), 0);

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price' => $finalUnitPrice,
                    'base_price' => $price,
                    'unit_discount' => $unitDiscount,
                    'discount_type' => $unitDiscountType,
                    'discount_total' => $lineAdjustment,
                    'unit_weight' => $unitWeight,
                    'is_priced_by_kg' => $isPricedByKg,
                    'total_weight' => $totalWeight,
                    'total' => $lineTotal,
                ]);

                $subtotalAmount += $lineSubtotal;
                $itemDiscountTotal += $lineAdjustment;
                $totalBeforeOrderDiscount += $lineTotal;
            }

            $orderDiscountAmount = $orderDiscountType === 'decrease'
                ? min($orderDiscountInput, $totalBeforeOrderDiscount)
                : $orderDiscountInput;
            $orderAdjustment = $orderDiscountType === 'increase'
                ? -1 * $orderDiscountAmount
                : $orderDiscountAmount;
            $newTotal = max($totalBeforeOrderDiscount - $orderAdjustment, 0);
            $totalDiscount = $itemDiscountTotal + $orderAdjustment;

            $paid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                - (float) $order->transactions()->where('type', 'refund')->sum('amount');

            $orderUpdateData = [
                'customer_id' => (int) $validated['customer_id'],
                'copied_from_order_id' => null,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_email' => $validated['recipient_email'] ?? null,
                'recipient_address' => $validated['recipient_address'],
                'delivery_time' => $validated['delivery_time'] ?? null,
                'note' => $validated['note'] ?? null,
                'shipper_note' => $validated['shipper_note'] ?? null,
                'subtotal_amount' => $subtotalAmount,
                'item_discount_total' => $itemDiscountTotal,
                'extra_discount_total' => $orderAdjustment,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'warehouse_can_adjust' => (bool) ($validated['warehouse_can_adjust'] ?? false),
                'total_discount' => $totalDiscount,
                'total' => $newTotal,
                'amount_due' => max($newTotal - $paid, 0),
                'stock_sufficient' => true,
                'stock_shortage_detail' => null,
                'stock_alert_status' => 'ready',
            ];

            if ($isReturnOrder) {
                $orderUpdateData['order_type'] = 'order_return';
                $orderUpdateData['workflow_code'] = 'order_return';
                $orderUpdateData['is_return_order'] = true;
                $orderUpdateData['warehouse_id'] = null;
                $orderUpdateData['daily_sequence'] = null;
                $orderUpdateData['stock_sufficient'] = true;
                $orderUpdateData['stock_shortage_detail'] = null;
            }

            $order->update(array_filter(
                $orderUpdateData,
                fn (string $column): bool => $this->hasOrderColumn($column),
                ARRAY_FILTER_USE_KEY
            ));

            if ($isCopiedOrder) {
                $copyUpdates = [];
                if ($this->hasOrderColumn('created_at')) {
                    $copyUpdates['created_at'] = now();
                }
                if ($this->hasOrderColumn('copied_from_order_id')) {
                    $copyUpdates['copied_from_order_id'] = null;
                }
                if (!empty($copyUpdates)) {
                    DB::table('orders')->where('id', $order->id)->update($copyUpdates);
                }
                $order->refresh();
            }

            // Sau khi sửa đơn, reset lại luồng duyệt tương tự tạo mới.
            $order->approvals()->delete();
            app(ApprovalService::class)->initOrderApproval(
                $order->fresh(),
                $isReturnOrder
                    ? \App\Models\ApprovalWorkflow::ACTIVITY_ORDER_RETURN
                    : \App\Models\ApprovalWorkflow::ACTIVITY_ORDER_CREATE
            );
        });

        if (!$isReturnOrder) {
            app(OrderController::class)->syncDailySequenceAndStockSufficiency($order->fresh()->created_at ?: now());
        }

        if ($request->input('return_to') === 'monitoring') {
            return redirect()->route('pages.my_orders.monitoring', [
                'date' => $order->fresh()->created_at?->toDateString(),
                'highlight' => $order->id,
            ])->with('success', 'Đã cập nhật đơn hàng thành công.');
        }

        return redirect()->route('site.orders.show', $order)
            ->with('success', 'Da cap nhat don hang thanh cong.');
    }
    
    public function confirmCopyOrder(Order $order)
    {
        $user = auth()->user();

        // Bảo mật: chỉ owner mới xác nhận được
        if ((int) $order->user_id !== (int) $user->id) {
            return redirect()->route('pages.my_orders')->with('error', 'Bạn không có quyền xác nhận đơn này.');
        }

        if (empty($order->copied_from_order_id)) {
            return redirect()->route('pages.my_orders')->with('error', 'Đơn hàng này không phải đơn copy.');
        }

        $isReturnOrder = (bool) ($order->is_return_order ?? false)
            || (string) ($order->order_type ?? '') === 'order_return'
            || (string) ($order->workflow_code ?? '') === 'order_return';

        DB::transaction(function () use ($order, $isReturnOrder) {
            $this->refreshCopiedOrderPrices($order);

            // Xoá label "Đơn copy mới"
            if ($this->hasOrderColumn('copied_from_order_id')) {
                DB::table('orders')->where('id', $order->id)->update(['copied_from_order_id' => null]);
            }

            // Cập nhật created_at về hiện tại
            if ($this->hasOrderColumn('created_at')) {
                DB::table('orders')->where('id', $order->id)->update(['created_at' => now()]);
            }

            // Xoá approval cũ (nếu có) rồi khởi tạo lại
            $order->approvals()->delete();
            app(ApprovalService::class)->initOrderApproval(
                $order->fresh(),
                $isReturnOrder
                    ? \App\Models\ApprovalWorkflow::ACTIVITY_ORDER_RETURN
                    : \App\Models\ApprovalWorkflow::ACTIVITY_ORDER_CREATE
            );
        });

        if (!$isReturnOrder) {
            app(OrderController::class)->syncDailySequenceAndStockSufficiency($order->fresh()->created_at ?: now());
        }

        return redirect()->route('pages.my_orders')
            ->with('success', 'Đã xác nhận đơn #' . $order->code . ' và gửi lên quy trình duyệt.');
    }

    public function createReturnOrder($id)
    {
        $user = auth()->user();

        $oldOrder = Order::with(['items', 'customer'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        if ((string) $oldOrder->status !== Order::STATUS_DELIVERED) {
            return redirect()->route('pages.my_orders')
                ->with('error', 'Chỉ tạo đơn hoàn trả từ đơn đã giao hàng.');
        }

        DB::transaction(function () use ($oldOrder, $user): void {
            $newOrder = $oldOrder->replicate();

            do {
                $newCode = 'OD' . time() . rand(10, 99);
            } while (Order::where('code', $newCode)->exists());

            $newOrder->code = $newCode;
            $newOrder->user_id = $oldOrder->user_id ?: $user->id;
            $newOrder->customer_id = $oldOrder->customer_id;
            $newOrder->shipper_id = null;
            $newOrder->status = Order::STATUS_ORDER_PLACED;
            $newOrder->payment_status = 'unpaid';
            $newOrder->delivery_status = 'not_shipped';
            $newOrder->delivered_at = null;
            $newOrder->collected_amount = null;
            $newOrder->proof_images = null;
            $newOrder->return_reason = null;
            $newOrder->created_at = now();
            $newOrder->updated_at = now();
            $newOrder->clearWarehouseAdjustmentState();

            foreach ([
                'copied_from_order_id' => $oldOrder->id,
                'parent_order_id' => $oldOrder->id,
                'order_type' => 'order_return',
                'workflow_code' => 'order_return',
                'is_return_order' => true,
                'warehouse_id' => null,
                'return_warehouse_id' => null,
                'daily_sequence' => null,
                'stock_sufficient' => true,
                'stock_shortage_detail' => null,
            ] as $column => $value) {
                if ($this->hasOrderColumn($column)) {
                    $newOrder->{$column} = $value;
                }
            }

            $newOrder->save();

            foreach ($oldOrder->items as $item) {
                $newItem = $item->replicate();
                $newItem->order_id = $newOrder->id;
                $newItem->save();
            }

            if ($this->hasOrderColumn('has_return_order')) {
                $oldOrder->update(['has_return_order' => true]);
            }
        });

        return redirect()->route('pages.my_orders')
            ->with('success', 'Đã tạo đơn hoàn trả từ đơn #' . $oldOrder->code . '. Vui lòng kiểm tra số lượng trả và bấm Xác nhận.');
    }

    public function copyOrder($id)
    {
       $user = auth()->user();

        $oldOrder = Order::with(['items', 'customer'])
            ->where('user_id', $user->id) // bảo mật
            ->findOrFail($id);

        $copiedOrderDate = now();

        DB::transaction(function () use ($oldOrder, $user, &$copiedOrderDate) {
            $resolvedCustomerId = (int) ($oldOrder->customer_id ?? 0);

            if ($resolvedCustomerId <= 0 || !Customer::query()->whereKey($resolvedCustomerId)->exists()) {
                $recipientPhone = $oldOrder->recipient_phone ?: $oldOrder->customer?->phone;
                $recipientEmail = $oldOrder->recipient_email ?: $oldOrder->customer?->email;
                $recipientName = $oldOrder->recipient_name ?: $oldOrder->customer?->name ?: ($user->name ?? 'Khach hang');

                $customerQuery = Customer::query()->where('user_id', $user->id);
                if (!empty($recipientPhone)) {
                    $customerQuery->where('phone', $recipientPhone);
                } elseif (!empty($recipientEmail)) {
                    $customerQuery->where('email', $recipientEmail);
                } else {
                    $customerQuery->where('name', $recipientName);
                }

                $resolvedCustomer = $customerQuery->first();

                if (!$resolvedCustomer) {
                    $emailForCreate = null;
                    if (!empty($recipientEmail)) {
                        $emailUsed = Customer::query()
                            ->where('email', $recipientEmail)
                            ->where('user_id', '!=', $user->id)
                            ->exists();
                        $emailForCreate = $emailUsed ? null : $recipientEmail;
                    }

                    $resolvedCustomer = Customer::create([
                        'user_id' => $user->id,
                        'assigned_to' => $user->id,
                        'name' => $recipientName,
                        'phone' => $recipientPhone,
                        'email' => $emailForCreate,
                        'address' => $oldOrder->recipient_address ?: $oldOrder->customer?->address,
                        'delivery_time' => $oldOrder->delivery_time ?: $oldOrder->customer?->delivery_time,
                    ]);
                }

                $resolvedCustomerId = (int) $resolvedCustomer->id;
            }

            // clone order
            $newOrder = $oldOrder->replicate();

            // reset các field quan trọng
            $newOrder->customer_id = $resolvedCustomerId;
            $newOrder->shipper_id = Customer::query()
                ->whereKey($resolvedCustomerId)
                ->value('default_shipper_id');
            if ($this->hasOrderColumn('warehouse_id')) {
                $newOrder->warehouse_id = null;
            }
            do {
                $newCode = 'OD' . time() . rand(10, 99);
            } while (Order::where('code', $newCode)->exists());
            $newOrder->code = $newCode;
            $newOrder->resetForCopiedOrder($oldOrder->id);
            $newOrder->created_at = now();
            $newOrder->updated_at = now();
            $newOrder->save();

            $copiedOrderDate = $newOrder->created_at ?: now();

            // Clone items, then immediately refresh them to the current selling price.
            foreach ($oldOrder->items as $item) {
                $newItem = $item->replicate();
                $newItem->order_id = $newOrder->id;
                $newItem->save();
            }

            $this->refreshCopiedOrderPrices($newOrder);
        });

        app(OrderController::class)->syncDailySequenceAndStockSufficiency($copiedOrderDate);

        $successMsg = 'Đã copy đơn #' . $oldOrder->code . '. Vui lòng xem lại và bấm "Xác Nhận" để gửi duyệt.';

        return redirect()->route('pages.my_orders')
            ->with('success', $successMsg);
    }

    private function refreshCopiedOrderPrices(Order $order): void
    {
        $order->load(['items.variant.product', 'items.variant.latestPriceRule']);

        $subtotalAmount = 0.0;
        $itemDiscountTotal = 0.0;
        $itemsTotal = 0.0;

        foreach ($order->items as $item) {
            if (!$item->variant) {
                $subtotalAmount += (float) ($item->total ?? 0);
                $itemsTotal += (float) ($item->total ?? 0);
                continue;
            }

            $currentBasePrice = (float) ($item->variant->latestPriceRule?->price ?? $item->variant->final_price ?? 0);
            if ($currentBasePrice <= 0) {
                $currentBasePrice = (float) ($item->base_price ?? $item->price ?? 0);
            }

            $quantity = max(0, (float) ($item->quantity ?? 0));
            $pricingFactor = $item->effective_priced_by_kg ? $item->effective_unit_weight : 1;
            $discountType = 'decrease';
            $unitDiscount = 0.0;
            $finalUnitPrice = $currentBasePrice;
            $lineSubtotal = round($currentBasePrice * $quantity * $pricingFactor, 2);
            $lineAdjustment = 0.0;
            $lineTotal = max(round($finalUnitPrice * $quantity * $pricingFactor, 2), 0);

            $item->update([
                'price' => $finalUnitPrice,
                'base_price' => $currentBasePrice,
                'unit_discount' => $unitDiscount,
                'discount_type' => $discountType,
                'discount_total' => $lineAdjustment,
                'total' => $lineTotal,
            ]);

            $subtotalAmount += $lineSubtotal;
            $itemDiscountTotal += $lineAdjustment;
            $itemsTotal += $lineTotal;
        }

        $orderDiscount = 0.0;
        $orderDiscountType = 'decrease';
        $orderAdjustment = 0.0;
        $total = max(round($itemsTotal - $orderAdjustment, 2), 0);
        $paid = 0.0;

        $order->update(array_filter([
            'subtotal_amount' => round($subtotalAmount, 2),
            'item_discount_total' => round($itemDiscountTotal, 2),
            'extra_discount_total' => round($orderAdjustment, 2),
            'order_discount' => round($orderDiscount, 2),
            'total_discount' => round($itemDiscountTotal + $orderAdjustment, 2),
            'total' => $total,
            'amount_paid' => 0,
            'amount_due' => max(round($total - $paid, 2), 0),
            'payment_status' => 'unpaid',
            'delivery_status' => 'not_shipped',
        ], fn (string $column): bool => $this->hasOrderColumn($column), ARRAY_FILTER_USE_KEY));
    }

    private function resolveMyCustomerDateRange(string $period, ?string $fromDateInput, ?string $toDateInput): array
    {
        $today = Carbon::today();

        if ($period === 'today') {
            return [$today->copy(), $today->copy()];
        }

        if ($period === 'week') {
            return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
        }

        if ($period === 'custom') {
            $fromDate = $fromDateInput ? Carbon::parse($fromDateInput) : $today->copy()->startOfMonth();
            $toDate = $toDateInput ? Carbon::parse($toDateInput) : $today->copy()->endOfMonth();

            if ($fromDate->greaterThan($toDate)) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }

            return [$fromDate, $toDate];
        }

        return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
    }
      /**
     * Lưu khách hàng mới từ form /my-customer/create
     */
    public function myCustomerStore(Request $request, CustomerPriorityService $priorityService)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'production' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_id' => 'nullable|exists:truck_stations,id',
            'truck_route_id' => 'nullable|exists:truck_routes,id',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_fee' => 'nullable|integer',
            'province_id' => 'nullable|exists:provinces,id',
            'ward_id' => 'nullable|exists:wards,id',
            'duplicate_customer_id' => 'nullable|integer',
            'duplicate_priority_level' => 'nullable|in:1,2,3',
            'duplicate_takeover' => 'nullable|boolean',
        ]);

        $duplicate = null;
        $name = trim((string) ($validated['name'] ?? ''));
        $phone = trim((string) ($validated['phone'] ?? ''));

        if ($name !== '' && $phone !== '') {
            $duplicateQuery = Customer::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('phone', $phone);

            if (!empty($validated['duplicate_customer_id'])) {
                $duplicateQuery->where('id', (int) $validated['duplicate_customer_id']);
            }

            $duplicate = $duplicateQuery->first();
        }

        if (!$duplicate && !empty($validated['email'])) {
            $duplicate = Customer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $validated['email'])])
                ->first();
        }

        if ($duplicate) {
            $priority = isset($validated['duplicate_priority_level'])
                ? (int) $validated['duplicate_priority_level']
                : null;

            if ($priority === 1) {
                if (!$duplicate->isFree()) {
                    return back()->withErrors([
                        'name' => 'Khách này chưa ở trạng thái tự do, không thể nhận Priority 1 ngay.',
                    ])->withInput();
                }

                $priorityService->takeover($duplicate, (int) $user->id, 'free_customer');

                return redirect()->route('pages.my_customer')
                    ->with('success', 'Đã nhận khách trùng ở Priority 1 (khách tự do).');
            }

            if (in_array($priority, [2, 3], true)) {
                $priorityService->attachSale($duplicate, (int) $user->id, $priority, 'duplicate_join');

                return redirect()->route('pages.my_customer')
                    ->with('success', 'Khách đã tồn tại, bạn đã được thêm vào danh sách chăm sóc với Priority ' . $priority . '.');
            }

            return back()->withErrors([
                'name' => 'Khách hàng đã tồn tại. Vui lòng chọn Priority 2/3, hoặc chọn Priority 1 nếu khách đang tự do.',
            ])->withInput();
        }

        if (!empty($validated['email'])) {
            $existingEmail = Customer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $validated['email'])])
                ->exists();

            if ($existingEmail) {
                return back()->withErrors([
                    'email' => 'Email đã tồn tại trong hệ thống.',
                ])->withInput();
            }
        }

        if (!(bool) ($validated['use_truck_station'] ?? false)) {
            $validated['truck_station_id'] = null;
        }

        if (!empty($validated['ward_id']) && !empty($validated['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($validated['ward_id'])
                ->where('province_id', $validated['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $customer = new \App\Models\Customer();
        $customer->user_id = $user->id;
        $customer->name = $validated['name'];
        $customer->email = $validated['email'] ?? null;
        $customer->phone = $validated['phone'] ?? null;
        $customer->address = $validated['address'] ?? null;
        $customer->delivery_time = $validated['delivery_time'] ?? null;
        $customer->size = $validated['size'] ?? null;
        $customer->production = $validated['production'] ?? null;
        $customer->company_name = $validated['company_name'] ?? null;
        $customer->tax_code = $validated['tax_code'] ?? null;
        $customer->company_address = $validated['company_address'] ?? null;
        $customer->company_email = $validated['company_email'] ?? null;
        $customer->use_truck_station = (bool) ($validated['use_truck_station'] ?? false);
        $customer->truck_station_id = $validated['truck_station_id'] ?? null;
        $customer->truck_route_id = $validated['truck_route_id'] ?? null;
        $customer->truck_station_address = $validated['truck_station_address'] ?? null;
        $customer->truck_station_phone = $validated['truck_station_phone'] ?? null;
        $customer->truck_receive_time = $validated['truck_receive_time'] ?? null;
        $customer->truck_return_time = $validated['truck_return_time'] ?? null;
        $customer->truck_fee = $validated['truck_fee'] ?? null;
        $customer->assigned_to = $user->id;
        $customer->assigned_at = now();
        $customer->current_owner_sale_id = $user->id;
        $customer->customer_status = 'active';
        $customer->current_cycle_no = 1;
        $customer->save();

        $priorityService->attachSale($customer, (int) $user->id, 1, 'created');

        if (!empty($validated['address']) || !empty($validated['province_id']) || !empty($validated['ward_id'])) {
            $province = !empty($validated['province_id']) ? Province::find($validated['province_id']) : null;
            $ward = !empty($validated['ward_id']) ? Ward::find($validated['ward_id']) : null;

            $customer->addresses()->create([
                'note' => $validated['address'] ?? null,
                'city' => $province?->name,
                'ward' => $ward?->name,
                'province_id' => $province?->id,
                'ward_id' => $ward?->id,
                'is_default' => 1,
            ]);
        }

        return redirect()->route('pages.my_customer')->with('success', 'Đã thêm khách hàng thành công!');
    }

    public function getProvinces(Request $request)
    {
        $query = Province::query();
        
        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('name', 'like', $search);
        }
        
        $provinces = $query->orderBy('name')->get(['id', 'code', 'name', 'type']);
        
        return response()->json($provinces);
    }

    public function getDistricts(Request $request)
    {
        $request->validate(['province_id' => 'required|exists:provinces,id']);
        
        $query = District::where('province_id', $request->province_id);
        
        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('name', 'like', $search)
                  ->orWhere('old_name', 'like', $search);
        }
        
        $districts = $query->orderBy('name')->get(['id', 'code', 'name', 'type', 'old_name']);
        
        return response()->json($districts);
    }

    public function getWards(Request $request)
    {
        $request->validate([
            'district_id' => 'nullable|exists:districts,id',
            'province_id' => 'nullable|exists:provinces,id',
        ]);
        
        if ($request->filled('district_id')) {
            $query = Ward::where('district_id', $request->district_id);
        } elseif ($request->filled('province_id')) {
            $districtIds = District::where('province_id', $request->province_id)->pluck('id');
            $query = Ward::where(function ($query) use ($request, $districtIds) {
                $query->where('province_id', $request->province_id)
                      ->orWhereIn('district_id', $districtIds);
            });
        } else {
            return response()->json([], 422);
        }
        
        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('name', 'like', $search)
                  ->orWhere('old_name', 'like', $search);
        }
        
        $wards = $query->orderBy('name')->get(['id', 'code', 'name', 'type', 'old_name']);
        
        return response()->json($wards);
    }

    public function myTruckStations(Request $request)
    {
        $brands = TruckBrand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $settings =  $this->settings; 

        return view('site.my_truck_stations', compact('brands', 'provinces', 'settings'));
    }

    public function myTruckStationsRegions(Request $request)
    {
        $rows = TruckStation::selectRaw('province_id, ward_id')
            ->whereNotNull('province_id')
            ->with(['province:id,name', 'ward:id,name'])
            ->get();

        $provinces = [];
        foreach ($rows as $row) {
            $pid = $row->province_id;
            if (!isset($provinces[$pid])) {
                $provinces[$pid] = [
                    'id'    => $pid,
                    'name'  => $row->province ? $row->province->name : '',
                    'wards' => [],
                ];
            }
            if ($row->ward_id && $row->ward) {
                $wid = $row->ward_id;
                $provinces[$pid]['wards'][$wid] = ['id' => $wid, 'name' => $row->ward->name];
            }
        }

        // sort and re-index
        usort($provinces, fn($a, $b) => strcmp($a['name'], $b['name']));
        foreach ($provinces as &$prov) {
            usort($prov['wards'], fn($a, $b) => strcmp($a['name'], $b['name']));
            $prov['wards'] = array_values($prov['wards']);
        }

        return response()->json(array_values($provinces));
    }

    public function myTruckStationsAjax(Request $request)
    {
        $userId = auth()->id();
        $mode = (string) $request->input('mode', 'stations');

        if ($mode === 'routes') {
            $routeQuery = \App\Models\TruckRoute::query()->with([
                'brand',
                'stops.station',
            ]);

            $keyword = trim((string) $request->input('q', ''));
            if ($keyword !== '') {
                $routeQuery->where(function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('brand', function ($brandQuery) use ($keyword) {
                            $brandQuery->where('name', 'like', "%{$keyword}%");
                        });
                });
            }

            $brandId = $request->input('brand_id');
            if ($brandId) {
                $routeQuery->where('truck_brand_id', $brandId);
            }

            $routeKeyword = trim((string) $request->input('route', ''));
            if ($routeKeyword !== '') {
                $routeQuery->where('name', 'like', "%{$routeKeyword}%");
            }

            $destinationKeyword = trim((string) $request->input('destination', ''));
            if ($destinationKeyword !== '') {
                $routeQuery->whereHas('stops', function ($stopQuery) use ($destinationKeyword) {
                    $stopQuery->whereRaw('sort_order = (SELECT MAX(s2.sort_order) FROM truck_route_stops s2 WHERE s2.truck_route_id = truck_route_stops.truck_route_id)')
                        ->whereHas('station', function ($stationQuery) use ($destinationKeyword) {
                            $stationQuery->where('name', 'like', "%{$destinationKeyword}%");
                        });
                });
            }

            $isActive = $request->input('is_active');
            if ($isActive !== null && $isActive !== '') {
                $routeQuery->where('is_active', (bool) $isActive);
            }

            $perPage = (int) $request->input('per_page', 20);
            $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

            $sortRoute = strtolower((string) $request->input('sort_route', 'asc'));
            $sortRoute = in_array($sortRoute, ['asc', 'desc'], true) ? $sortRoute : 'asc';

            $routes = $routeQuery->orderBy('name', $sortRoute)->paginate($perPage);

            return response()->json([
                'data' => $routes->map(function ($route) use ($userId) {
                    $stops = $route->stops ?? collect();
                    $originStop = $stops->sortBy('sort_order')->first();
                    $destinationStop = $stops->sortByDesc('sort_order')->first();

                    return [
                        'id' => $route->id,
                        'name' => $route->name,
                        'brand' => $route->brand?->name,
                        'origin' => $originStop?->station?->name,
                        'destination' => $destinationStop?->station?->name,
                        'departure_time' => $originStop?->arrival_time,
                        'is_active' => (bool) $route->is_active,
                        'can_edit' => (int) $route->created_by === (int) $userId,
                    ];
                }),
                'links' => [
                    'current_page' => $routes->currentPage(),
                    'last_page' => $routes->lastPage(),
                    'per_page' => $routes->perPage(),
                    'total' => $routes->total(),
                ],
            ]);
        }

        $query = TruckStation::with([
            'brand',
            'province',
            'ward',
            'routeStops.route.brand',
            'routeStops.route.stops.station',
        ]);

        $keyword = trim((string) $request->input('q', ''));
        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        $provinceId = $request->input('province_id');
        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }

        $wardId = $request->input('ward_id');
        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        $isActive = $request->input('is_active');
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $brandId = $request->input('brand_id');
        if ($brandId) {
            $query->where(function ($sub) use ($brandId) {
                $sub->where('brand_id', $brandId)
                    ->orWhereHas('routeStops.route', function ($routeQuery) use ($brandId) {
                        $routeQuery->where('truck_brand_id', $brandId);
                    });
            });
        }

        $routeKeyword = trim((string) $request->input('route', ''));
        if ($routeKeyword !== '') {
            $query->whereHas('routeStops.route', function ($routeQuery) use ($routeKeyword) {
                $routeQuery->where('name', 'like', "%{$routeKeyword}%");
            });
        }

        $destinationKeyword = trim((string) $request->input('destination', ''));
        if ($destinationKeyword !== '') {
            $query->whereHas('routeStops.route', function ($routeQuery) use ($destinationKeyword) {
                $routeQuery->whereHas('stops', function ($stopQuery) use ($destinationKeyword) {
                    $stopQuery->whereRaw('sort_order = (SELECT MAX(s2.sort_order) FROM truck_route_stops s2 WHERE s2.truck_route_id = truck_route_stops.truck_route_id)')
                        ->whereHas('station', function ($stationQuery) use ($destinationKeyword) {
                            $stationQuery->where('name', 'like', "%{$destinationKeyword}%");
                        });
                });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $sortRoute = strtolower((string) $request->input('sort_route', 'asc'));
        $sortRoute = in_array($sortRoute, ['asc', 'desc'], true) ? $sortRoute : 'asc';

        $query->orderByRaw(
            "COALESCE((SELECT MIN(tr.name) FROM truck_route_stops trs INNER JOIN truck_routes tr ON tr.id = trs.truck_route_id WHERE trs.truck_station_id = truck_stations.id), truck_stations.name) {$sortRoute}"
        );

        $truckStations = $query->paginate($perPage);

        return response()->json([
            'data' => $truckStations->map(function ($ts) use ($userId) {
                $routes = $ts->routeStops
                    ->map(fn ($routeStop) => $routeStop->route)
                    ->filter()
                    ->unique('id')
                    ->sortBy('name')
                    ->values()
                    ->map(function ($route) {
                        $stops = $route->stops ?? collect();
                        $originStop = $stops->sortBy('sort_order')->first();
                        $destinationStop = $stops->sortByDesc('sort_order')->first();

                        return [
                            'id' => $route->id,
                            'name' => $route->name,
                            'brand' => $route->brand?->name,
                            'origin' => $originStop?->station?->name,
                            'destination' => $destinationStop?->station?->name,
                            'departure_time' => $originStop?->arrival_time,
                        ];
                    })
                    ->values();

                return [
                    'id' => $ts->id,
                    'name' => $ts->name,
                    'brand_id' => $ts->brand_id,
                    'brand' => $ts->brand?->name,
                    'address' => $ts->address,
                    'phone' => $ts->phone,
                    'note' => $ts->note,
                    'is_active' => $ts->is_active,
                    'province_id' => $ts->province_id,
                    'ward_id' => $ts->ward_id,
                    'province' => $ts->province ? $ts->province->name : null,
                    'ward' => $ts->ward ? $ts->ward->name : null,
                    'routes' => $routes,
                    'route_count' => $routes->count(),
                    'can_edit' => (int) $ts->created_by === (int) $userId,
                ];
            }),
            'links' => [
                'current_page' => $truckStations->currentPage(),
                'last_page' => $truckStations->lastPage(),
                'per_page' => $truckStations->perPage(),
                'total' => $truckStations->total(),
            ],
        ]);
    }

    public function myTruckStationsStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['nullable', 'exists:truck_brands,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'parking_fee' => ['nullable', 'numeric', 'min:0'],
            'branch_info' => ['nullable', 'string', 'max:500'],
            'has_home_delivery' => ['nullable', 'boolean'],
            'home_delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return response()->json(['errors' => ['ward_id' => ['Phường/Xã không thuộc Tỉnh/Thành đã chọn.']]], 422);
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['has_home_delivery'] = $request->boolean('has_home_delivery', false);
        $data['home_delivery_fee'] = $data['home_delivery_fee'] ?? 0;
        $data['created_by'] = auth()->id();

        $station = TruckStation::create($data);
        $station->load(['brand', 'province', 'ward']);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo trạm xe mới.',
            'data' => [
                'id' => $station->id,
                'name' => $station->name,
                'brand_id' => $station->brand_id,
                'brand' => $station->brand ? $station->brand->name : null,
                'address' => $station->address,
                'phone' => $station->phone,
                'parking_fee' => $station->parking_fee,
                'branch_info' => $station->branch_info,
                'has_home_delivery' => (bool) $station->has_home_delivery,
                'home_delivery_fee' => $station->home_delivery_fee,
                'note' => $station->note,
                'is_active' => $station->is_active,
                'province_id' => $station->province_id,
                'ward_id' => $station->ward_id,
                'province' => $station->province ? $station->province->name : null,
                'ward' => $station->ward ? $station->ward->name : null,
                'routes' => [],
                'route_count' => 0,
                'can_edit' => true,
            ],
        ]);
    }

    public function myTruckStationsUpdate(Request $request, TruckStation $truckStation)
    {
        if ((int) $truckStation->created_by !== (int) auth()->id()) {
            return response()->json(['message' => 'Bạn không có quyền sửa trạm xe này.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return response()->json(['errors' => ['ward_id' => ['Phường/Xã không thuộc Tỉnh/Thành đã chọn.']]], 422);
            }
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $truckStation->update($data);
        $truckStation->load([
            'brand',
            'province',
            'ward',
            'routeStops.route.brand',
            'routeStops.route.stops.station',
        ]);

        $routes = $truckStation->routeStops
            ->map(fn ($routeStop) => $routeStop->route)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(function ($route) {
                $stops = $route->stops ?? collect();
                $originStop = $stops->sortBy('sort_order')->first();
                $destinationStop = $stops->sortByDesc('sort_order')->first();

                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'brand' => $route->brand?->name,
                    'origin' => $originStop?->station?->name,
                    'destination' => $destinationStop?->station?->name,
                    'departure_time' => $originStop?->arrival_time,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạm xe.',
            'data' => [
                'id' => $truckStation->id,
                'name' => $truckStation->name,
                'brand_id' => $truckStation->brand_id,
                'brand' => $truckStation->brand ? $truckStation->brand->name : null,
                'address' => $truckStation->address,
                'phone' => $truckStation->phone,
                'note' => $truckStation->note,
                'is_active' => $truckStation->is_active,
                'province_id' => $truckStation->province_id,
                'ward_id' => $truckStation->ward_id,
                'province' => $truckStation->province ? $truckStation->province->name : null,
                'ward' => $truckStation->ward ? $truckStation->ward->name : null,
                'routes' => $routes,
                'route_count' => $routes->count(),
                'can_edit' => true,
            ],
        ]);
    }

    /**
     * Compute which orders lack sufficient inventory to be packed,
     * respecting FIFO order sequence and already-packed orders.
     *
     * Returns an array keyed by order_id → array of ['variant_id', 'name', 'needed', 'available'] for shortage items.
     *
     * @param  \Illuminate\Support\Collection  $pageOrders  Orders currently being displayed
     * @return array<int, list<array{variant_id:int, name:string, needed:float, available:float}>>
     */
    private function buildStockWarnings(\Illuminate\Support\Collection $pageOrders): array
    {
        // Statuses considered "queued for packing" (have not been packed yet)
        $queueStatuses = [
            Order::STATUS_APPROVED,
            Order::STATUS_ORDER_CONFIRMED,
            Order::STATUS_READY_TO_PACK,
            Order::STATUS_PACKING,
        ];

        // Load all pending orders across all users, FIFO order
        $allPending = Order::whereIn('status', $queueStatuses)
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($allPending->isEmpty()) {
            return [];
        }

        // Collect all variant IDs referenced
        $variantIds = $allPending
            ->flatMap(fn ($o) => $o->items->pluck('product_variant_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($variantIds)) {
            return [];
        }

        // Available stock per variant (sum across all warehouses)
        $stockMap = Inventory::whereIn('product_variant_id', $variantIds)
            ->selectRaw('product_variant_id, SUM(GREATEST(0, quantity - reserved_quantity)) as avail')
            ->groupBy('product_variant_id')
            ->pluck('avail', 'product_variant_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();

        // Variant name cache
        $variantNames = \App\Models\ProductVariant::whereIn('id', $variantIds)
            ->pluck('name', 'id')
            ->toArray();

        // Page order IDs we care about (for fast lookup)
        $pageOrderIds = $pageOrders->pluck('id')->flip()->toArray();

        // Walk orders in FIFO sequence
        $warnings = [];
        $remaining = $stockMap;

        foreach ($allPending as $order) {
            $shortages = [];

            foreach ($order->items as $item) {
                $vid = (int) $item->product_variant_id;
                if (!$vid) {
                    continue;
                }
                $needed   = (float) $item->quantity;
                $avail    = $remaining[$vid] ?? 0.0;

                if ($avail < $needed) {
                    $shortages[] = [
                        'variant_id' => $vid,
                        'name'       => $variantNames[$vid] ?? ("Variant #{$vid}"),
                        'needed'     => $needed,
                        'available'  => max(0.0, $avail),
                    ];
                }

                // Deduct from remaining regardless (higher-priority orders consume stock first)
                $remaining[$vid] = max(0.0, $avail - $needed);
            }

            // Only record warnings for orders visible on the current page
            if (!empty($shortages) && isset($pageOrderIds[$order->id])) {
                $warnings[$order->id] = $shortages;
            }
        }

        return $warnings;
    }
}
