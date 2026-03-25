<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
class WorkReportController extends Controller
{
    protected $settings; 

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function index(Request $request)
    {
        // Lấy filter từ request
        $type = $request->input('type', 'month'); // month|week
        $date = $request->input('date', now()->toDateString());

        // Xác định khoảng thời gian
        if ($type === 'week') {
            $start = Carbon::parse($date)->startOfWeek();
            $end = Carbon::parse($date)->endOfWeek();
        } else {
            $start = Carbon::parse($date)->startOfMonth();
            $end = Carbon::parse($date)->endOfMonth();
        }

        // Thống kê số lượng sản phẩm
        $productCount = Product::whereBetween('created_at', [$start, $end])->count();
        // Thống kê số lượng đơn hàng
        $orders = Order::with('customer')->whereBetween('created_at', [$start, $end])->orderByDesc('created_at')->limit(30)->get();
        $orderCount = $orders->count();
        // Thống kê khách hàng mới
        $newCustomers = Customer::whereBetween('created_at', [$start, $end])->orderByDesc('created_at')->limit(30)->get();
        $newCustomerCount = $newCustomers->count();
        // Thống kê khách hàng cũ (có đơn trong khoảng này nhưng tạo trước đó)
        $oldCustomerCount = Order::whereBetween('created_at', [$start, $end])
            ->whereHas('customer', function($q) use ($start) {
                $q->where('created_at', '<', $start);
            })->distinct('customer_id')->count('customer_id');

        return view('site.work_report', [
            'type' => $type,
            'date' => $date,
            'productCount' => $productCount,
            'orderCount' => $orderCount,
            'newCustomerCount' => $newCustomerCount,
            'oldCustomerCount' => $oldCustomerCount,
            'orders' => $orders,
            'newCustomers' => $newCustomers,
            'settings' => $this->settings,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
