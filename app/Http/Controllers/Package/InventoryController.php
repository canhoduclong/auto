<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        // Thống kê tồn kho
        return view('package.inventory');
    }
}
