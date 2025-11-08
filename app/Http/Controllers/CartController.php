<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function checkout()
    {
        if (!session()->has('cart') || empty(session('cart'))) {
            return redirect()->route('cart.show')->with('error', 'Giỏ hàng của bạn đang trống');
        }
        
        return view('site.checkout');
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
                'price' => $variant->latestPriceRule?->price ?? 0,
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
        $cart = session()->get('cart', []);
        return view('site.cart', compact('cart'));
    }

    public function remove(Request $request)
    {
        if($request->id) {
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
            }
            return response()->json([
                'success' => true,
                'message' => 'Product removed successfully!',
                'cart_count' => count($cart)
            ]);
        }
    }

    public function updateQuantity(Request $request)
    {
        if($request->id && $request->quantity) {
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                $cart[$request->id]['quantity'] = $request->quantity;
                session()->put('cart', $cart);
            }
            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully!'
            ]);
        }
    }
}