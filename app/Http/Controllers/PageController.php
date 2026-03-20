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
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
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
        $categories = Category::all();

        $query = ProductVariant::query()
            ->where('stock', '>', 0)
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
        $categories = Category::all();

        $query = Product::where('status', true);

        if ($category) {
            $query->where('category_id', $category->id);
        }

        $products = $query->with('avatar.media')->paginate(10);

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
            'variants.media', 
            'variants.latestPriceRule'
        ]);

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

        $customer = Customer::updateOrCreate(
            ['email' => $user->email],
            ['user_id' => $user->id, 'name' => $user->name]
        );

        return view('site.my_dashboard', [
            'settings' => $this->settings,
            'user' => $user,
            'customer' => $customer
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $customer = $user->customer;

        $request->validate([
            'name' => 'required|string|max:255', 
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'note' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $customer?->update($request->only(['name', 'email', 'phone', 'dob', 'gender', 'note']));

        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $user->update(['avatar' => 'avatars/' . $avatarName]);
        }

        return redirect()->route('pages.my_dashboard')->with('success', 'Profile updated successfully.');
    }

    public function variantDetail(ProductVariant $variant)
    {
        $variant->load('avatar.media', 'product.category');

        $product = $variant->product;
        $product->load('avatar.media', 'gallery.media');

        $other_variants = ProductVariant::where('id', '!=', $variant->id)
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

        $customers = Customer::whereIn('id', function ($q) use ($user) {
            $q->select('customer_id')->from('orders')
              ->where('user_id', $user->id)
              ->whereNotNull('customer_id');
        })->orderBy('name')->get();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->latest()->paginate(10);

        return view('site.my_orders', [
            'settings' => $this->settings,
            'user' => $user,
            'orders' => $orders,
            'customers' => $customers
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
                    $sub->where('team_id', $user->team_id)
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
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
        ]);

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

        $query = Order::with(['items', 'approvals.step', 'user.roles'])
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->whereHas('user', function ($sub) use ($user) {
                    $sub->where('team_id', $user->team_id)
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->whereRaw('LOWER(name) = ?', ['sale']);
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
                    $approvalService->approve($order, $user, 'Duyệt tự động theo điều kiện leader.');
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
        ]);

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

        $query = Order::with(['items', 'approvals.step', 'user.roles'])
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
                    $approvalService->approve($order, $user, 'Manager duyệt tự động theo điều kiện PKD.');
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'delivery_time' => $validated['delivery_time'] ?? null,
        ]);

        return redirect()
            ->route('pages.my_customer')
            ->with('success', 'Đã cập nhật thông tin khách hàng thành công.');
    }

    public function myCustomerImportForm()
    {
        return view('site.my_customer.import', ['settings' => $this->settings]);
    }

    public function myCustomerShow(Customer $customer)
    {
        $this->ensureManagedCustomer($customer);

        return view('site.my_customer.show', [
            'customer' => $customer,
            'settings' => $this->settings
        ]);
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
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load('items.variant.product', 'customer');

        return view('site.orders.show', compact('order'));
    }
}