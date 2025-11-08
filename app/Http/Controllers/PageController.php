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

class PageController extends Controller
{
    public function about()
    {
        $settings = Setting::all()->keyBy('key'); 
        return view('pages.about', compact('settings'));
    }

    public function contact()
    {
        $settings = Setting::all()->keyBy('key'); 
        return view('pages.contact', compact('settings'));
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = Page::all();
        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            //'slug' => 'required|unique:pages',
            'content' => 'required',
        ]);

        Page::create($request->all());

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }
    
    public function show(Request $request)
    {
        $slug = $request->slug;
        $page = Page::where('slug', $slug)->firstOrFail();    
        return view('pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function productsByCategory(Request $request, Category $category = null)
    {
        $settings = Setting::all()->keyBy('key');
        $categories = Category::all();
        $query = ProductVariant::query()->where('stock', '>', 0);

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('min_price')) {
            $query->whereHas('latestPriceRule', function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price);
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('latestPriceRule', function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price);
            });
        }

        if ($category) {
            $query->whereHas('product', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            });
        }
        $variants = $query->with('product', 'latestPriceRule')->paginate(10);
        return view('site.products_by_category', compact('variants', 'settings', 'categories', 'category'));
    }

    public function productList(Request $request, Category $category = null)
    {
        $settings = Setting::all()->keyBy('key');
        $categories = Category::all();
        $query = Product::query();

        if ($category) {
            $query->where('category_id', $category->id);
        }

        $products = $query->with('avatar.media')->paginate(10);

        return view('site.product_list', compact('products', 'settings', 'categories', 'category'));
    }

    public function productDetail(Product $product)
    {
        $settings = Setting::all()->keyBy('key');

        // Eager load all the necessary relationships for the view and JS module
        $product->load([
            'brand',
            'gallery.media', 
            'variants.values.attribute', 
            'variants.media', 
            'variants.latestPriceRule'
        ]);

        // Group variants by their attributes for easier handling in the view
        $attributes = $product->variants
            ->flatMap(fn($variant) => $variant->values)
            ->unique('id')
            ->groupBy('attribute.name');

        return view('site.product_detail', compact('product', 'settings', 'attributes'));
    }

    public function myDashboard(Request $request)
    {
        $settings = Setting::all()->keyBy('key');
        $user = auth()->user();
        
        $customer = Customer::updateOrCreate(
            ['email' => $user->email],
            ['user_id' => $user->id, 'name' => $user->name]
        );

        return view('site.my_dashboard', compact('settings', 'user', 'customer'));
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
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $customerData = $request->only(['name', 'email', 'phone', 'dob', 'gender', 'note']);

        if ($customer) {
            $customer->update($customerData);
        }

        $userData = [];
        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $userData['avatar'] = 'avatars/' . $avatarName;
        }

        if(count($userData) > 0){
            $user->update($userData);
        }

        return redirect()->route('pages.my_dashboard')->with('success', 'Profile updated successfully.');
    }

    public function variantDetail(ProductVariant $variant)
    {
        $settings = Setting::all()->keyBy('key');
        $product = $variant->product;
        $other_variants = $product->variants()->where('id', '!=', $variant->id)->get();

        return view('site.variant_detail', compact('variant', 'product', 'other_variants', 'settings'));
    }

    public function myOrders(Request $request)
    {
        $settings = Setting::all()->keyBy('key');
        $user = auth()->user();
        $orders = \App\Models\Order::with('customer')->where('user_id', $user->id)->latest()->paginate(10);

        return view('site.my_orders', compact('settings', 'user', 'orders'));
    }

    public function myCustomer(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $customers = Customer::withCount('orders')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate($perPage);

        return view('site.my_customer.index', compact('customers', 'search'));
    }

    public function myCustomerCreate()
    {
        return view('site.my_customer.create');
    }

    public function myCustomerEdit(Customer $customer)
    {
        return view('site.my_customer.edit', compact('customer'));
    }

    public function myCustomerImportForm()
    {
        return view('site.my_customer.import');
    }

    public function myCustomerStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
        ]);

        Customer::create($request->all());

        return back()->with('success', 'Customer created successfully.');
    }

    public function myCustomerUpdate(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $customer->update($request->all());

        return back()->with('success', 'Customer updated successfully.');
    }

    public function myCustomerDestroy(Customer $customer)
    {
        $customer->delete();
        return back()->with('success', 'Customer deleted successfully.');
    }

    public function myCustomerBulkDelete(Request $request)
    {
        $ids = explode(',', $request->input('_ids'));

        if (empty($ids)) {
            return back()->with('error', 'Không có khách hàng nào được chọn.');
        }

        Customer::whereIn('id', $ids)->delete();

        return back()->with('success', 'Đã xóa thành công các khách hàng đã chọn.');
    }

    public function myCustomerImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        $file = $request->file('file');

        $import = new \App\Imports\CustomerImport;
        Excel::import($import, $file);

        $importedCount = $import->getImportedCount();
        $failedCount = $import->getFailedCount();
        $failedRows = $import->getFailedRows();

        $message = "Import completed. {$importedCount} rows imported successfully.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} rows failed to import.";
        }

        return back()
            ->with('success', $message)
            ->with('importedCount', $importedCount)
            ->with('failedCount', $failedCount)
            ->with('failedRows', $failedRows);
    }

    public function myCustomerShow(Customer $customer)
    {
        return view('site.my_customer.show', compact('customer'));
    }

    public function myCustomerOrderCreate(Customer $customer)
    {        
        $products = Product::with('variants.latestPriceRule')->get();
        return view('site.my_customer.order_create', compact('customer', 'products'));
    }

    public function myCustomerOrderStore(Request $request, Customer $customer, OrderService $orderService)
    {
        $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.quantity' => 'nullable|integer|min:0',
        ]);

        $order = $orderService->createOrder([
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'delivery_status' => DeliveryStatus::NotShipped->value,
            'total_amount' => 0, // Will be calculated by the service
        ], $request->variants);

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function myOrderDetail(\App\Models\Order $order)
    {
        // Add authorization check if needed
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        $order->load('items.variant.product', 'customer');
        return view('site.orders.show', compact('order'));
    }
}