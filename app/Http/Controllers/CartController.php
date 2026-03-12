<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    
    public function checkout()
    {
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
        if (!session()->has('cart') || empty(session('cart'))) {
            return redirect()->route('cart.show')->with('error', 'Giỏ hàng của bạn đang trống');
        }
        
        return view('site.checkout', compact('settings'));
    }

    public function add(Request $request)
    {
        $variant = ProductVariant::findOrFail($request->variant_id);
        $quantity = $request->input('quantity', 1);

        $cart = session()->get('cart', []);

        if (isset($cart[$variant->id])) {
            $cart[$variant->id]['quantity'] += $quantity;
        } else {
            $cart[$variant->id] = [
                'name' => $variant->product->name,
                'quantity' => $quantity,
                'price' => $variant->final_price ?? 0,
                'image' => $variant->product->avatar?->media?->file_path ?? null,
                'sku' => $variant->sku
            ];
        }

        session()->put('cart', $cart);
        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully!',
            'cart_count' => count($cart)
        ]);
    }

    public function show()
    {
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });

        $cart = session()->get('cart', []);

        return view('site.cart', compact('settings', 'cart'));
    }

    public function remove(Request $request, $id)
    {
        $cart = session()->get('cart', []);
        $itemId = $id ?: $request->input('id');

        if ($itemId && isset($cart[$itemId])) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product removed successfully!',
            'cart_count' => count($cart),
        ]);
    }

    public function updateQuantity(Request $request, $id)
    {
        $quantity = (int) $request->input('quantity', 0);
        if ($quantity < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity must be at least 1.',
            ], 422);
        }

        $cart = session()->get('cart', []);
        $itemId = $id ?: $request->input('id');

        if ($itemId && isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] = $quantity;
            session()->put('cart', $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully!',
        ]);
    }
}