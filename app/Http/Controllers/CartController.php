<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    protected $settings;
    private static ?array $customerColumns = null;

    private function customerColumns(): array
    {
        if (self::$customerColumns === null) {
            self::$customerColumns = Schema::getColumnListing('customers');
        }

        return self::$customerColumns;
    }

    private function hasCustomerColumn(string $column): bool
    {
        return in_array($column, $this->customerColumns(), true);
    }

    private function parseWeightToKg(?string $value): float
    {
        if (!$value) {
            return 0.0;
        }

        if (!preg_match('/([0-9]+(?:[\.,][0-9]+)?)/', $value, $matches)) {
            return 0.0;
        }

        $weight = (float) str_replace(',', '.', $matches[1]);
        $normalized = mb_strtolower($value);

        if (str_contains($normalized, 'g') && !str_contains($normalized, 'kg')) {
            return round($weight / 1000, 3);
        }

        return round($weight, 3);
    }

    private function buildCartSummary(array $cart): array
    {
        $total = 0;
        $itemCount = 0;

        foreach ($cart as $details) {
            $quantity = (int) ($details['quantity'] ?? 0);
            $price = (float) ($details['price'] ?? 0);

            $total += $price * $quantity;
            $itemCount += $quantity;
        }

        return [
            'total' => $total,
            'item_count' => $itemCount,
            'line_count' => count($cart),
            'formatted_total' => number_format($total) . 'd',
        ];
    }

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function checkout()
    {
        $settings = $this->settings;
        if (!session()->has('cart') || empty(session('cart'))) {
            return redirect()->route('cart.show')->with('error', 'Giỏ hàng của bạn đang trống');
        }

        $cart = session()->get('cart', []);
        $variantIds = array_map('intval', array_keys($cart));

        if (!empty($variantIds)) {
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->get(['id', 'size'])
                ->keyBy('id');

            foreach ($cart as $variantId => $details) {
                $variant = $variants->get((int) $variantId);
                if (!$variant) {
                    continue;
                }

                if (!array_key_exists('size', $details) || $details['size'] === null) {
                    $cart[$variantId]['size'] = $variant->size;
                }

                if (!array_key_exists('unit_weight', $details) || (float) $details['unit_weight'] <= 0) {
                    $cart[$variantId]['unit_weight'] = $this->parseWeightToKg($variant->size);
                }
            }

            session()->put('cart', $cart);
        }
        
        return view('site.checkout', compact('settings'));
    }

    public function searchCustomers(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $keyword = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 20) {
            $perPage = 20;
        }

        $query = Customer::query();

        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);

            if ($this->hasCustomerColumn('assigned_to')) {
                $q->orWhere('assigned_to', $user->id);
            }

            // Fallback ownership by order creator for legacy customer data.
            $q->orWhereHas('orders', function ($orderQ) use ($user) {
                $orderQ->where('user_id', $user->id);
            });
        });

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        $selectColumns = ['id', 'name', 'email', 'phone', 'note', 'user_id'];
        if ($this->hasCustomerColumn('address')) {
            $selectColumns[] = 'address';
        }
        if ($this->hasCustomerColumn('delivery_time')) {
            $selectColumns[] = 'delivery_time';
        }
        if ($this->hasCustomerColumn('assigned_to')) {
            $selectColumns[] = 'assigned_to';
        }

        $customers = $query
            ->select(array_unique($selectColumns))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
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
                'sku' => $variant->sku,
                'size' => $variant->size,
                'unit_weight' => $this->parseWeightToKg($variant->size),
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
        $settings = $this->settings;
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

        if (!$itemId || !isset($cart[$itemId])) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart.',
            ], 404);
        }

        $cart[$itemId]['quantity'] = $quantity;
        session()->put('cart', $cart);

        $price = (float) ($cart[$itemId]['price'] ?? 0);
        $itemSubtotal = $price * $quantity;
        $summary = $this->buildCartSummary($cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully!',
            'item' => [
                'id' => (string) $itemId,
                'quantity' => $quantity,
                'subtotal' => $itemSubtotal,
                'formatted_subtotal' => number_format($itemSubtotal) . 'd',
            ],
            'summary' => $summary,
        ]);
    }
}