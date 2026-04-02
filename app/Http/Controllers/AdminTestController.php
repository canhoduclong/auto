<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminTestController extends Controller
{
    // Hiển thị form nhập kho và tạo đơn hàng test
    public function showForm()
    {
        $products = Product::with('variants')->get();
        $sales = User::all()->filter(function($user) {
            return $user->hasRole('sale');
        });
        return view('admin.test-inventory-orders', compact('products', 'sales'));
    }

    // Xử lý nhập kho hôm nay
    public function stockToday(Request $request)
    {
        $data = $request->input('stock', []);
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            foreach ($data as $variantId => $qty) {
                if ($qty > 0) {
                    Inventory::create([
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'type' => 'import',
                        'note' => 'Nhập kho test ngày ' . $now->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            DB::commit();
            return back()->with('success', 'Đã nhập kho thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // Tạo 10 đơn hàng test cho các sale
    public function createOrders(Request $request)
    {
        $sales = User::all()->filter(function($user) {
            return $user->hasRole('sale');
        });
        $variants = ProductVariant::inRandomOrder()->take(5)->get();
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $customer = \App\Models\Customer::first();
            if (!$customer) {
                return back()->with('error', 'Không tìm thấy khách hàng test. Hãy tạo ít nhất 1 khách hàng.');
            }
            if ($sales->isEmpty()) {
                return back()->with('error', 'Không tìm thấy user nào có vai trò sale.');
            }
            for ($i = 0; $i < 10; $i++) {
                $sale = $sales->random();
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'user_id' => $sale->id,
                    'status' => 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                foreach ($variants as $variant) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => rand(1, 5),
                        'price' => $variant->price ?? 10000,
                    ]);
                }
            }
            DB::commit();
            return back()->with('success', 'Đã tạo 10 đơn hàng test!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
