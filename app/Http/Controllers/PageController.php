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
use App\Services\OrderService;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;

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

    public function myCustomer(Request $request)
    {
        $customers = Customer::withCount('orders')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
            )
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

    public function myCustomerEdit(Customer $customer)
    {
        return view('site.my_customer.edit', [
            'customer' => $customer,
            'settings' => $this->settings
        ]);
    }

    public function myCustomerImportForm()
    {
        return view('site.my_customer.import', ['settings' => $this->settings]);
    }

    public function myCustomerShow(Customer $customer)
    {
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