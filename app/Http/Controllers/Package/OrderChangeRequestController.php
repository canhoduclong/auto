<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        // Danh sách yêu cầu thay đổi đơn hàng
        return view('package.order_changes');
    }
}
