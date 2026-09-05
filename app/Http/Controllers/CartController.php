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
            $kg = isset($details['unit_weight']) && $details['unit_weight'] > 0 ? (float) $details['unit_weight'] : 1;
            $isPricedByKg = array_key_exists('is_priced_by_kg', $details)
                ? (bool) $details['is_priced_by_kg']
                : true;
            $pricingFactor = $isPricedByKg ? $kg : 1;

            $total += $price * $quantity * $pricingFactor;
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

    public function updateDiscount(Request $request)
    {
        $cart = session()->get('cart', []);

        $itemDiscounts = $request->input('item_discount', []);
        $itemDiscountTypes = $request->input('item_discount_type', []);
        $orderDiscount = (float) $request->input('order_discount', 0);
        $orderDiscountType = strtolower((string) $request->input('order_discount_type', 'decrease')) === 'increase'
            ? 'increase'
            : 'decrease';

        $subtotal = 0;
        $itemAdjustmentTotal = 0;
        $totalWeight = 0;

        foreach ($cart as $id => &$item) {
            $price = $item['price'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $kg = isset($item['unit_weight']) && (float) $item['unit_weight'] > 0 ? (float) $item['unit_weight'] : 1;
            $isPricedByKg = array_key_exists('is_priced_by_kg', $item)
                ? (bool) $item['is_priced_by_kg']
                : true;
            $pricingFactor = $isPricedByKg ? $kg : 1;

            $discount = isset($itemDiscounts[$id]) ? (float) $itemDiscounts[$id] : 0;
            $discount = max(0, $discount);
            $discountType = strtolower((string) ($itemDiscountTypes[$id] ?? 'decrease')) === 'increase'
                ? 'increase'
                : 'decrease';
            $minPrice = \App\Support\OrderPriceBounds::minimum((float) ($item['min_price'] ?? 0));

            if ($discountType === 'decrease') {
                $maxAllowedDecrease = max($price - $minPrice, 0);
                $discount = min($discount, $maxAllowedDecrease);
            }

            // Lưu discount vào cart session
            $item['discount'] = $discount;
            $item['discount_type'] = $discountType;

            $lineSubtotal = $price * $quantity * $pricingFactor;
            $lineAdjustment = ($discountType === 'increase' ? -1 : 1) * $discount * $quantity * $pricingFactor;

            $subtotal += $lineSubtotal;
            $itemAdjustmentTotal += $lineAdjustment;
            $totalWeight += $kg * $quantity;
        }

        unset($item);

        $afterItemDiscount = max($subtotal - $itemAdjustmentTotal, 0);

        if ($orderDiscountType === 'decrease') {
            $orderDiscount = max(0, min($orderDiscount, $afterItemDiscount));
        } else {
            $orderDiscount = max(0, $orderDiscount);
        }

        $orderAdjustment = $orderDiscountType === 'increase'
            ? -1 * $orderDiscount
            : $orderDiscount;

        // Lưu order discount session
        session()->put('cart', $cart);
        session()->put('order_discount', $orderDiscount);
        session()->put('order_discount_type', $orderDiscountType);

        $total = max($afterItemDiscount - $orderAdjustment, 0);

        $formatSignedMoney = static function (float $amount): string {
            $prefix = $amount < 0 ? '+' : '-';

            return $prefix . number_format(abs($amount), 0, ',', '.') . 'đ';
        };
        $formatKg = function ($value) {
            $num = floatval($value);
            $formatted = number_format($num, 3, '.', '');
            $formatted = rtrim(rtrim($formatted, '0'), '.');
            return str_replace('.', ',', $formatted) . ' kg';
        };

        return response()->json([
            'success' => true,
            'summary' => [
                'formatted_subtotal' => number_format($subtotal, 0, ',', '.') . 'đ',
                'formatted_item_discount' => $formatSignedMoney($itemAdjustmentTotal),
                'formatted_order_discount' => $formatSignedMoney($orderAdjustment),
                'formatted_discount' => $formatSignedMoney($itemAdjustmentTotal + $orderAdjustment),
                'formatted_total' => number_format($total, 0, ',', '.') . 'đ',
                'formatted_weight' => $formatKg($totalWeight),
            ]
        ]);
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
                ->with('product:id,kg,is_priced_by_kg')
                ->get(['id', 'product_id', 'size', 'kg', 'is_priced_by_kg'])
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
                    $variantKg = (float) ($variant->kg ?? 0);
                    $productKg = (float) ($variant->product?->kg ?? 0);
                    $resolvedKg = $variantKg > 0
                        ? $variantKg
                        : ($productKg > 0 ? $productKg : $this->parseWeightToKg($variant->size));
                    $cart[$variantId]['unit_weight'] = max(0.01, round($resolvedKg, 3));
                }

                if (!array_key_exists('is_priced_by_kg', $details)) {
                    $cart[$variantId]['is_priced_by_kg'] = $variant->is_priced_by_kg !== null
                        ? (bool) $variant->is_priced_by_kg
                        : (bool) ($variant->product?->is_priced_by_kg ?? true);
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

        // Xử lý sắp xếp
        $sortField = $request->input('sort_field', 'name');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'name' => 'name',
            'created_at' => 'created_at',
            'priority' => 'priority',
        ];
        $sortFieldDb = $allowedSorts[$sortField] ?? 'name';

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
            ->select(array_unique($selectColumns));

        // Nếu sắp xếp theo priority mà không có cột priority thì fallback về name
        if ($sortFieldDb === 'priority' && !$this->hasCustomerColumn('priority')) {
            $sortFieldDb = 'name';
        }
        // Nếu sắp xếp theo created_at mà không có cột created_at thì fallback về name
        if ($sortFieldDb === 'created_at' && !$this->hasCustomerColumn('created_at')) {
            $sortFieldDb = 'name';
        }

        $customers = $query
            ->orderBy($sortFieldDb, $sortOrder)
            ->orderBy('id', 'desc') // phụ để ổn định
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
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::query()
            ->withAvailableStock()
            ->with('latestPriceRule', 'product.avatar.media')
            ->findOrFail($validated['variant_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);

        $cart = session()->get('cart', []);

        $resolvedKg = (float) ($variant->kg ?? 0);
        if ($resolvedKg <= 0) {
            $resolvedKg = (float) ($variant->product?->kg ?? 0);
        }
        if ($resolvedKg <= 0) {
            $resolvedKg = $this->parseWeightToKg($variant->size);
        }
        $resolvedKg = max(0.01, round($resolvedKg, 3));
        $isPricedByKg = $variant->is_priced_by_kg !== null
            ? (bool) $variant->is_priced_by_kg
            : (bool) ($variant->product?->is_priced_by_kg ?? true);

        if (isset($cart[$variant->id])) {
            $cart[$variant->id]['quantity'] += $quantity;
            $cart[$variant->id]['min_price'] = (float) ($variant->latestPriceRule?->min_price ?? ($cart[$variant->id]['min_price'] ?? 0));
            $cart[$variant->id]['unit_weight'] = $resolvedKg;
            $cart[$variant->id]['is_priced_by_kg'] = $isPricedByKg;
        } else {
            $cart[$variant->id] = [
                'name' => $variant->product->name,
                'quantity' => $quantity,
                'price' => $variant->final_price ?? 0,
                'image' => $variant->product->avatar?->media?->file_path ?? null,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'unit' => $variant->product->unit,
                'unit_label' => $variant->product->unit_label ?? 'Cái',
                'don_vi_tinh' => $variant->product->unit_label ?? 'Cái',
                'unit_weight' => $resolvedKg,
                'is_priced_by_kg' => $isPricedByKg,
                'min_price' => (float) ($variant->latestPriceRule?->min_price ?? 0),
            ];
        }

        session()->put('cart', $cart);
        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully!',
            'cart_count' => count($cart),
            'quantity' => (int) ($cart[$variant->id]['quantity'] ?? $quantity),
            'unit' => $variant->product->unit,
            'unit_label' => $variant->product->unit_label ?? 'Cái',
            'don_vi_tinh' => $variant->product->unit_label ?? 'Cái',
            'weight' => (float) ($cart[$variant->id]['unit_weight'] ?? 0),
            'is_priced_by_kg' => (bool) ($cart[$variant->id]['is_priced_by_kg'] ?? true),
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
        if (!session()->has('cart')) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
                'cart_count' => 0,
            ], 440);
        }

        $cart = session()->get('cart', []);
        $itemId = $id ?: $request->input('id');

        if ($itemId && isset($cart[$itemId])) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);
        }
        $summary = $this->buildCartSummary($cart);

        

        return response()->json([
            'success' => true,
            'message' => 'Product removed successfully!',
            'cart_count' => $summary['item_count'],
            'summary' => [
                'formatted_total' => number_format($summary['total'], 0, ',', '.') . '₫',
                'item_count' => $summary['item_count'],
                'line_count' => $summary['line_count'],
            ]
        ]);
    } 

    public function updateQuantity(Request $request, $id)
    {
        // Nếu session hết hạn hoặc không có cart, trả về JSON lỗi
        if (!session()->has('cart')) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
                'item' => null,
                'summary' => null,
            ], 440); // 440: Login Timeout (custom)
        }
        $cart = session()->get('cart', []);
        $itemId = $id ?: $request->input('id');
        $quantity = (int) $request->input('quantity', 0);
        $unitWeight = $request->has('unit_weight') ? (float) $request->input('unit_weight') : null;

        if (!$itemId || !isset($cart[$itemId])) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart.',
            ], 404);
        }

        if ($quantity < 1) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);

            $summary = $this->buildCartSummary($cart);

            return response()->json([
                'success' => true,
                'removed' => true,
                'removed_id' => (string) $itemId,
                'message' => 'Product removed successfully!',
                'summary' => $summary,
            ]);
        }

        $cart[$itemId]['quantity'] = $quantity;
        if ($unitWeight !== null && $unitWeight > 0) {
            $cart[$itemId]['unit_weight'] = $unitWeight;
        } elseif (!isset($cart[$itemId]['unit_weight']) || $cart[$itemId]['unit_weight'] <= 0) {
            $cart[$itemId]['unit_weight'] = 1;
        }
        session()->put('cart', $cart);

        $price = (float) ($cart[$itemId]['price'] ?? 0);
        $uw = isset($cart[$itemId]['unit_weight']) && $cart[$itemId]['unit_weight'] > 0 ? (float) $cart[$itemId]['unit_weight'] : 1;
        $isPricedByKg = (bool) ($cart[$itemId]['is_priced_by_kg'] ?? true);
        $pricingFactor = $isPricedByKg ? $uw : 1;
        $itemSubtotal = $price * $quantity * $pricingFactor;
        $summary = $this->buildCartSummary($cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully!',
            'item' => [
                'id' => (string) $itemId,
                'quantity' => $quantity,
                'unit' => $cart[$itemId]['unit'] ?? null,
                'unit_label' => $cart[$itemId]['unit_label'] ?? 'Cái',
                'don_vi_tinh' => $cart[$itemId]['unit_label'] ?? 'Cái',
                'weight' => $uw,
                'unit_weight' => $uw,
                'is_priced_by_kg' => $isPricedByKg,
                'unit_price' => number_format($price),
                'subtotal' => $itemSubtotal,
                'formatted_subtotal' => number_format($itemSubtotal) . 'd',
            ],
            'summary' => $summary,
        ]);
    }
}
