<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderAjaxController extends Controller
{
    public function total(Request $request)
    {
        $order = Order::find($request->order_id);
        if (!$order) return response()->json(['success'=>false]);
        return response()->json([
            'success' => true,
            'total' => $order->total
        ]);
    }
}
