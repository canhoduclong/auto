<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackageDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Dashboard: tổng quan đóng hàng
        return view('package.dashboard');
    }
}
