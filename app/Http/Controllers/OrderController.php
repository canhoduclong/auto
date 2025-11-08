<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function createNewOrderForm(Request $request)
    {
        
        $variantId = $request->input('variant_id');
        
        if (!$variantId) {
            return redirect()->route('orders.index')->with('error', 'No variant ID provided.');
        }

        
        $variant = ProductVariant::with(['product', 'media'])->find($variantId);

        if (!$variant) {
            return redirect()->route('orders.index')->with('error', 'Variant not found.');
        }
        $customers = Customer::paginate(10);

        // IMPORTANT: Set the base path for the pagination links to our AJAX endpoint.
        $customers->setPath(route('orders.ajax_customer_search'));

        return view('orders.create_new', compact('variant', 'customers'));
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

        $customers = $query->paginate(10);

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

        $query = ProductVariant::with('product');

        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where('sku', 'like', "%{$searchTerm}%")
                  ->orWhereHas('product', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
        }

        // Exclude variants that are already in the cart
        if ($request->has('exclude_ids') && is_array($request->input('exclude_ids'))) {
            $query->whereNotIn('id', $request->input('exclude_ids'));
        }

        $variants = $query->paginate($perPage);

        return response()->json([
            'html' => view('orders._variant_search_results', compact('variants'))->render()
        ]);
    }

    public function storeANewOrder(Request $request, OrderService $orderService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $orderItems = [];
        foreach ($request->input('items') as $item) {
            $variant = ProductVariant::find($item['variant_id']);
            if ($variant->stock < $item['quantity']) {
                return back()->with('error', "Not enough stock for variant {$variant->sku}. Only {$variant->stock} left.")
                             ->withInput();
            }
            $orderItems[] = [
                'id' => $item['variant_id'],
                'quantity' => $item['quantity']
            ];
        }

        $order = $orderService->createOrder([
            'customer_id' => $request->input('customer_id'),
            'user_id' => auth()->id(),
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'delivery_status' => DeliveryStatus::NotShipped->value,
            'total_amount' => 0, // Service will calculate
        ], $orderItems);

        return redirect()->route('site.orders.show', $order)->with('success', 'Order created successfully.');
    }
    public function storeNewOrder(Request $request, OrderService $orderService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $orderItems = [];
        foreach ($request->input('items') as $item) {
            $variant = ProductVariant::find($item['variant_id']);
            if ($variant->stock < $item['quantity']) {
                return back()->with('error', "Not enough stock for variant {$variant->sku}. Only {$variant->stock} left.")
                             ->withInput();
            }
            $orderItems[] = [
                'id' => $item['variant_id'],
                'quantity' => $item['quantity']
            ];
        }

        $order = $orderService->createOrder([
            'customer_id' => $request->input('customer_id'),
            'user_id' => auth()->id(),
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'delivery_status' => DeliveryStatus::NotShipped->value,
            'total_amount' => 0, // Service will calculate
        ], $orderItems);

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function storeFromCart(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để tạo đơn hàng.');
        }
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'recipient_address' => 'required|string|max:1000',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Giỏ hàng trống');
        }

        

            $total = 0;
            $variants = [];
            $stockErrors = [];

            // Kiểm tra tồn kho trước khi bắt đầu giao dịch
            foreach ($cart as $variantId => $details) {
                $variant = ProductVariant::find($variantId);
                if (!$variant) {
                    return redirect()->route('cart.show')->with('error', "Sản phẩm không tồn tại");
                }

                // Kiểm tra tồn kho ngay từ đầu
                if ($variant->stock < $details['quantity']) {
                    $stockErrors[] = "Sản phẩm {$variant->product->name} (SKU: {$variant->sku}) không đủ số lượng. Hiện chỉ còn {$variant->stock} sản phẩm.";
                }
            }

            // Nếu có lỗi tồn kho, return ngay
            if (!empty($stockErrors)) {
                return redirect()->route('cart.show')->with('error', implode("<br>", $stockErrors));
            }

            // Nếu đủ tồn kho, bắt đầu xử lý với lock
            foreach ($cart as $variantId => $details) {
                $variant = ProductVariant::lockForUpdate()->find($variantId);
                
                // Kiểm tra lại một lần nữa sau khi lock
                if ($variant->stock < $details['quantity']) {
                    DB::rollBack();
                    return redirect()->route('cart.show')->with('error', "Số lượng đã thay đổi cho {$variant->product->name} (SKU: {$variant->sku}). Vui lòng thử lại.");
                }

                $price = $variant->latestPriceRule?->price ?? $details['price'];
                $variants[$variantId] = [
                    'variant' => $variant,
                    'quantity' => $details['quantity'],
                    'price' => $price,
                ];

                $total += $price * $details['quantity'];
            }

            // create order
            $order = new \App\Models\Order();
            $order->customer_id = auth()->id() ?? null;
            $order->user_id = auth()->id() ?? null;
            $order->recipient_name = $request->recipient_name;
            $order->recipient_phone = $request->recipient_phone;
            $order->recipient_address = $request->recipient_address;
            $order->note = $request->note;
            $order->status = \App\Enums\OrderStatus::Pending->value;
            $order->payment_status = \App\Enums\PaymentStatus::Unpaid->value;
            $order->delivery_status = \App\Enums\DeliveryStatus::NotShipped->value;
            $order->total = $total;
            $order->save();

            // Tạo order items và cập nhật tồn kho
            foreach ($variants as $variantId => $info) {
                 
                    // Tạo order item
                    $orderItem = new \App\Models\OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_variant_id = $variantId;
                    $orderItem->quantity = $info['quantity'];
                    $orderItem->price = $info['price'];
                    $orderItem->save();

                    // Cập nhật tồn kho với điều kiện kiểm tra
                    $updated = \App\Models\ProductVariant::where('id', $variantId)
                        ->where('stock', '>=', $info['quantity'])
                        ->update(['stock' => DB::raw('stock - ' . $info['quantity'])]);

                    if (!$updated) {
                        throw new \Exception("Không thể cập nhật tồn kho cho sản phẩm SKU: " . $info['variant']->sku);
                    }
                 
            }

            DB::commit();

            // Clear cart
            session()->forget('cart');

            return redirect()->route('site.orders.show', $order->id)->with('success', 'Đơn hàng đã tạo thành công');
        
    }
    public function test(Request $request)
    {
        echo "oks";
    }
    public function index(Request $request)
    {
        $query = Order::with('customer', 'user');

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
        
        // Statistics
        $statsQuery = clone $query;
        $totalInvoiceAmount = $statsQuery->sum('total');
        $totalPaidAmount = $statsQuery->sum('amount_paid');
        $totalOutstandingAmount = $totalInvoiceAmount - $totalPaidAmount;
        $fullyPaidOrders = (clone $statsQuery)->where('payment_status', 'paid')->count();
        $unpaidOrders = (clone $statsQuery)->where('payment_status', 'unpaid')->count();
        $partiallyPaidOrders = (clone $statsQuery)->where('payment_status', 'partially_paid')->count();

        $orders = $query->latest()->paginate(15);

        $users = \App\Models\User::all();
        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->name];
        });

        return view('orders.index', compact(
            'orders',
            'users',
            'statusOptions',
            'totalInvoiceAmount',
            'totalPaidAmount',
            'totalOutstandingAmount',
            'fullyPaidOrders',
            'unpaidOrders',
            'partiallyPaidOrders'
        ));
    }

    public function show(Order $order)
    {
        $order->load('items.variant.product', 'customer');
        return view('orders.show', compact('order'));
    }
}