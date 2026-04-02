<?php
namespace App\Http\Controllers;


use App\Models\Contact;
use App\Models\Setting;
use App\Models\Page;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Team;
use App\Services\OrderService;
use App\Services\ApprovalService;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\AdminActivityService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

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

    private function myOrderCustomersBaseQuery(int $userId): Builder
    {
        return Customer::query()->whereIn('id', function ($q) use ($userId) {
            $q->select('customer_id')
                ->from('orders')
                ->where('user_id', $userId)
                ->whereNotNull('customer_id');
        });
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

        $products = $query->with([
            'avatar.media',
            'variants.values.attribute',
            'variants.mediaLink.media',
            'variants.latestPriceRule',
        ])->paginate(10);

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
            'brand',
            'gallery.media', 
            'variants.values.attribute', 
            'variants.mediaLink.media', 
            'variants.latestPriceRule',
            'variants.inventories'
        ]);

        $product->variants->each(function ($variant) {
            $variant->setAttribute('available_stock', $variant->available_stock);
        });

        $attributes = $product->variants
            ->flatMap(fn($variant) => $variant->values)
            ->unique('id')
            ->groupBy('attribute.name');

        return view('site.product_detail', [
            'product' => $product,
            'settings' => $this->settings,
            'attributes' => $attributes
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
        $user->save();

        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $user->update(['avatar' => 'avatars/' . $avatarName]);
        }

        return redirect()->route('pages.my_dashboard')->with('success', 'Profile updated successfully.');
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

        $query = Order::with('customer')->where('user_id', $user->id);

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

        $allowedPerPage = [10, 20, 50, 100];
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

        if ($sortBy === 'customer_name') {
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

        if ($request->ajax() || $request->boolean('ajax')) {
            $html = view('site.orders.partials.orders_listing', [
                'orders' => $orders,
                'user' => $user,
                'sortBy' => $sortBy,
                'sortDir' => $sortDir,
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
        ]);
    }

    public function myOrdersMonitoring(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để theo dõi đơn hàng.');
        }

        $user = auth()->user();

        $query = Order::query()
            ->with(['customer', 'user', 'shipper'])
            ->latest('created_at');

        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
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
            $query->where('status', (string) $request->input('status'));
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

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $statsQuery = clone $query;
        $stats = [
            'total_orders' => (clone $statsQuery)->count(),
            'delivering_orders' => (clone $statsQuery)->where('status', Order::STATUS_DELIVERING)->count(),
            'returning_orders' => (clone $statsQuery)->where('status', Order::STATUS_RETURNING)->count(),
            'completed_orders' => (clone $statsQuery)
                ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                ->count(),
            'total_value' => (clone $statsQuery)->sum('total'),
            'today_orders' => (clone $statsQuery)->whereDate('created_at', now()->toDateString())->count(),
        ];

        $orders = $query
            ->paginate($perPage)
            ->appends($request->query());

        return view('site.orders.monitoring', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
            'perPage' => $perPage,
            'keyword' => $keyword,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedStatus' => (string) $request->input('status', ''),
        ]);
    }

    public function myOrderCustomersAjax(Request $request)
    {
        if (!auth()->check()) {
            abort(401);
        }

        $user = auth()->user();
        $search = trim((string) $request->input('q', ''));
        $selectedCustomerIds = collect(explode(',', (string) $request->input('selected_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $customers = $this->myOrderCustomersBaseQuery($user->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'page');

        $html = view('site.orders.partials.customer_listing', [
            'customers' => $customers,
            'selectedCustomerIds' => $selectedCustomerIds,
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

        $query = Order::with(['customer', 'user.roles', 'user.team', 'approvals.step'])
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

        if ($request->filled('status')) {
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
            'pending' => (clone $query)->where('status', 'pending_leader_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
        ];

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
            });

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

        if ($request->filled('status')) {
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

    public function teamOrderDetail(Order $order)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem chi tiết đơn hàng.');
        }

        $user = auth()->user();

        $order->load([
            'customer',
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

        return view('site.orders.team_detail', [
            'settings' => $this->settings,
            'order' => $order,
            'currentStep' => $currentStep,
            'canApprove' => $canApprove,
        ]);
    }

    public function myCustomer(Request $request)
    {
        $userId = auth()->id();

        $customers = Customer::withCount('orders')
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->when($request->search, function ($q, $s) {
                $q->where(function ($searchQuery) use ($s) {
                    $searchQuery->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));

        return view('site.my_customer.index', [
            'customers' => $customers,
            'search' => $request->search,
            'settings' => $this->settings
        ]);
    }

    public function myCustomerCreate()
    {
        return view('site.my_customer.create', ['settings' => $this->settings]);
    }

    private function ensureManagedCustomer(Customer $customer): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('admin')) {
            return;
        }

        $canManage = (int) $customer->user_id === (int) $user->id
            || (int) $customer->assigned_to === (int) $user->id;

        if (!$canManage) {
            abort(403, 'Bạn không có quyền cập nhật khách hàng này.');
        }
    }

    public function myCustomerEdit(Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        return view('site.my_customer.edit', [
            'customer' => $customer,
            'settings' => $this->settings
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
            $customer->careLogs()->create([
                'user_id' => auth()->id(),
                'note' => $request->input('care_note'),
            ]);
            return redirect()->route('my_customer.show', $customer)->with('success', 'Đã thêm tình trạng/nhật ký chăm sóc!');
        }

        // Nếu không, cập nhật thông tin cơ bản
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'production' => ['nullable', 'numeric'],
        ]);
        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'delivery_time' => $validated['delivery_time'] ?? null,
            'size' => $validated['size'] ?? null,
            'production' => $validated['production'] ?? null,
        ]);
        return redirect()->route('pages.my_customer')->with('success', 'Đã cập nhật thông tin khách hàng thành công.');
    }

    public function myCustomerImportForm()
    {
        return view('site.my_customer.import', ['settings' => $this->settings]);
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
            'orders_per_page' => 'nullable|integer|min:5|max:100',
            'debt_per_page' => 'nullable|integer|min:5|max:100',
            'payments_per_page' => 'nullable|integer|min:5|max:100',
        ]);

        [$fromDate, $toDate] = $this->resolveMyCustomerDateRange(
            (string) ($validated['period'] ?? 'month'),
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        $customer->load(['type', 'assignedTo', 'addresses', 'reminders']);
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
        $products = Product::with('variants.latestPriceRule')->get();

        return view('site.my_customer.order_create', [
            'customer' => $customer,
            'products' => $products,
            'settings' => $this->settings
        ]);
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

        $isEditable = $order->status === Order::STATUS_PENDING_LEADER_APPROVAL
            && $order->created_at?->isToday();

        if (!$isEditable) {
            return redirect()->route('pages.my_orders')
                ->with('error', 'Chi duoc sua don cho duyet leader duoc tao trong ngay.');
        }

        $customerIds = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique()
            ->values();

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->orderBy('name')
            ->get();

        $order->load('items.variant.product', 'customer');

        return view('site.orders.edit', [
            'settings' => $this->settings,
            'order' => $order,
            'customers' => $customers,
        ]);
    }

    public function myOrderUpdate(Request $request, Order $order)
    {
        $user = auth()->user();

        if (!$user || $order->user_id !== $user->id) {
            abort(403);
        }

        $isEditable = $order->status === Order::STATUS_PENDING_LEADER_APPROVAL
            && $order->created_at?->isToday();

        if (!$isEditable) {
            return redirect()->route('pages.my_orders')
                ->with('error', 'Don hang khong con du dieu kien de sua.');
        }

        $customerIds = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
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
            'item_discount' => ['nullable', 'array'],
            'item_discount.*' => ['nullable', 'numeric', 'min:0'],
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

            if ($variant->available_stock < $item['quantity']) {
                return back()->withErrors([
                    'items' => 'Ton kho khong du cho SKU ' . ($variant->sku ?: $variant->id) . '.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($order, $validated, $itemsInput, $variants): void {
            $order->items()->delete();

            $subtotalAmount = 0;
            $itemDiscountTotal = 0;
            $totalBeforeOrderDiscount = 0;
            $orderDiscountInput = max(0, (float) ($validated['order_discount'] ?? 0));
            $itemDiscountInput = collect($validated['item_discount'] ?? []);

            foreach ($itemsInput as $item) {
                $variant = $variants->get($item['variant_id']);
                $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                $quantity = (int) $item['quantity'];
                $lineSubtotal = round($price * $quantity, 2);
                $requestedUnitDiscount = (float) $itemDiscountInput->get((string) $variant->id, 0);
                $unitDiscount = max(0, min($requestedUnitDiscount, $price));
                $lineDiscount = round($unitDiscount * $quantity, 2);
                $lineTotal = max($lineSubtotal - $lineDiscount, 0);

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'base_price' => $price,
                    'unit_discount' => $unitDiscount,
                    'discount_total' => $lineDiscount,
                    'unit_weight' => 0,
                    'total_weight' => 0,
                    'total' => $lineTotal,
                ]);

                $subtotalAmount += $lineSubtotal;
                $itemDiscountTotal += $lineDiscount;
                $totalBeforeOrderDiscount += $lineTotal;
            }

            $orderDiscount = min($orderDiscountInput, $totalBeforeOrderDiscount);
            $newTotal = max($totalBeforeOrderDiscount - $orderDiscount, 0);
            $totalDiscount = $itemDiscountTotal + $orderDiscount;

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
                'extra_discount_total' => $orderDiscount,
                'order_discount' => $orderDiscount,
                'total_discount' => $totalDiscount,
                'total' => $newTotal,
                'amount_due' => max($newTotal - $paid, 0),
            ];

            $order->update(array_filter(
                $orderUpdateData,
                fn (string $column): bool => $this->hasOrderColumn($column),
                ARRAY_FILTER_USE_KEY
            ));

            // Sau khi sửa đơn, reset lại luồng duyệt tương tự tạo mới.
            $order->approvals()->delete();
            app(ApprovalService::class)->initOrderApproval($order->fresh());
        });

        return redirect()->route('site.orders.show', $order)
            ->with('success', 'Da cap nhat don hang thanh cong.');
    }
    
    public function copyOrder($id)
    {
       $user = auth()->user();

        $oldOrder = Order::with('items')
            ->where('user_id', $user->id) // bảo mật
            ->findOrFail($id);

        // clone order
        $newOrder = $oldOrder->replicate();

 
        // reset các field quan trọng
        $newOrder->customer_id = $oldOrder->customer_id;
        $newOrder->code = 'OD' . time();
        $newOrder->status = Order::STATUS_PENDING_LEADER_APPROVAL;
        $newOrder->payment_status = 'unpaid';
        $newOrder->created_at = now();
        $newOrder->updated_at = now();
        if ($this->hasOrderColumn('copied_from_order_id')) {
            $newOrder->copied_from_order_id = $oldOrder->id;
        }
        //echo '<pre>'; print_r($newOrder->toArray()); echo '</pre>'; die('---');
        $newOrder->save();

        // clone items
        foreach ($oldOrder->items as $item) {
            $newItem = $item->replicate();
            $newItem->order_id = $newOrder->id;
            $newItem->save();
        }

        // Khởi tạo lại workflow duyệt để đơn copy có bước pending rõ ràng.
        app(ApprovalService::class)->initOrderApproval($newOrder);

        return redirect()->route('pages.my_orders')
            ->with('success', 'Đã copy đơn #' . $oldOrder->code);
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
    public function myCustomerStore(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'production' => 'nullable|numeric',
        ]);

        $customer = new \App\Models\Customer();
        $customer->user_id = $user->id;
        $customer->name = $validated['name'];
        $customer->email = $validated['email'] ?? null;
        $customer->phone = $validated['phone'] ?? null;
        $customer->address = $validated['address'] ?? null;
        $customer->delivery_time = $validated['delivery_time'] ?? null;
        $customer->size = $validated['size'] ?? null;
        $customer->production = $validated['production'] ?? null;
        $customer->save();

        return redirect()->route('pages.my_customer')->with('success', 'Đã thêm khách hàng thành công!');
    }
}