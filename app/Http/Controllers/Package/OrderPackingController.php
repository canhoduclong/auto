<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function index(Request $request)
    {
        // Danh sách đơn cần đóng hàng
        return view('package.orders');
    }

    public function show($orderId)
    {
        // Chi tiết đơn đóng hàng
        return view('package.order_detail', compact('orderId'));
    }
}
