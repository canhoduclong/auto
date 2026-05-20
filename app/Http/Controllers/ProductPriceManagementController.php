<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPriceLog;
use App\Models\ProductPriceRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductPriceManagementController extends Controller
{
    private function isCeoPriceRoute(Request $request): bool
    {
        return $request->routeIs('ceo.price-management.*');
    }

    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['variants.latestPriceRule', 'avatar.media'])
            ->where('status', true)
            ->orderByDesc('created_at');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $products = $query->paginate(12)->appends($request->query());

        $products->getCollection()->transform(function (Product $product) {
            [$minPrice, $maxPrice] = $this->resolvePriceRange($product);
            [$minAllowedPrice, $maxAllowedPrice] = $this->resolveMinPriceRange($product);
            $product->current_price_min = $minPrice;
            $product->current_price_max = $maxPrice;
            $product->current_min_price_min = $minAllowedPrice;
            $product->current_min_price_max = $maxAllowedPrice;

            return $product;
        });

        $view = $this->isCeoPriceRoute($request)
            ? 'ceo.price-management.index'
            : 'products.price-management.index';

        return view($view, compact('products'));
    }

    public function show(Request $request, Product $product)
    {
        $product->load(['variants.latestPriceRule', 'avatar.media']);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $historyQuery = ProductPriceLog::query()
            ->with(['variant', 'appliedBy', 'priceRule'])
            ->whereHas('variant', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->orderByDesc('applied_at');

        if (!empty($fromDate)) {
            $historyQuery->whereDate('applied_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $historyQuery->whereDate('applied_at', '<=', $toDate);
        }

        $priceHistory = $historyQuery->paginate(20)->appends($request->query());

        [$minPrice, $maxPrice] = $this->resolvePriceRange($product);

        $view = $this->isCeoPriceRoute($request)
            ? 'ceo.price-management.show'
            : 'products.price-management.show';

        return view($view, [
            'product' => $product,
            'priceHistory' => $priceHistory,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'currentPriceMin' => $minPrice,
            'currentPriceMax' => $maxPrice,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0|lte:price',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $newPrice = (float) $validated['price'];
        $newMinPrice = (float) ($validated['min_price'] ?? 0);
        $effectiveDate = Carbon::parse($validated['effective_date'])->toDateString();
        $reason = $validated['reason'] ?? 'Cập nhật giá theo sản phẩm';

        $variants = $product->variants()->get();

        if ($variants->isEmpty()) {
            return back()->with('error', 'Sản phẩm chưa có biến thể để cập nhật giá.');
        }

        $updatedCount = 0;

        DB::transaction(function () use ($variants, $newPrice, $newMinPrice, $effectiveDate, $reason, &$updatedCount) {
            foreach ($variants as $variant) {
                $currentRule = $variant->priceRules()
                    ->where(function ($query) use ($effectiveDate) {
                        $query->whereNull('start_date')
                            ->orWhereDate('start_date', '<=', $effectiveDate);
                    })
                    ->where(function ($query) use ($effectiveDate) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $effectiveDate);
                    })
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->first();

                $oldPrice = (float) ($currentRule?->price ?? $variant->final_price ?? 0);

                if (
                    $currentRule
                    && (float) $currentRule->price === $newPrice
                    && (float) ($currentRule->min_price ?? 0) === $newMinPrice
                ) {
                    continue;
                }

                $variant->priceRules()
                    ->where(function ($query) use ($effectiveDate) {
                        $query->whereNull('start_date')
                            ->orWhereDate('start_date', '<=', $effectiveDate);
                    })
                    ->where(function ($query) use ($effectiveDate) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $effectiveDate);
                    })
                    ->update([
                        'end_date' => Carbon::parse($effectiveDate)->subDay()->toDateString(),
                    ]);

                $nextRule = $variant->priceRules()
                    ->whereDate('start_date', '>', $effectiveDate)
                    ->orderBy('start_date')
                    ->first();

                $endDate = null;
                if ($nextRule && !empty($nextRule->start_date)) {
                    $endDate = Carbon::parse($nextRule->start_date)->subDay()->toDateString();
                }

                $newRule = ProductPriceRule::create([
                    'product_variant_id' => $variant->id,
                    'reason' => $reason,
                    'price' => $newPrice,
                    'min_price' => $newMinPrice,
                    'start_date' => $effectiveDate,
                    'end_date' => $endDate,
                    'created_by' => Auth::id(),
                ]);

                ProductPriceLog::create([
                    'product_variant_id' => $variant->id,
                    'price_rule_id' => $newRule->id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'applied_at' => now(),
                    'applied_by' => Auth::id(),
                    'user_id' => Auth::id(),
                ]);

                $updatedCount++;
            }
        });

        if ($updatedCount === 0) {
            return back()->with('success', 'Không có biến thể nào thay đổi giá vì giá mới trùng với giá hiện tại.');
        }

        $redirectRoute = $this->isCeoPriceRoute($request)
            ? 'ceo.price-management.show'
            : 'products.price-management.show';

        return redirect()
            ->route($redirectRoute, $product)
            ->with('success', "Đã cập nhật giá cho {$updatedCount} biến thể từ ngày {$effectiveDate}.");
    }

    private function resolvePriceRange(Product $product): array
    {
        $prices = $product->variants
            ->map(function ($variant) {
                if ($variant->latestPriceRule?->price !== null) {
                    return (float) $variant->latestPriceRule->price;
                }

                return (float) ($variant->final_price ?? 0);
            })
            ->filter(function ($price) {
                return $price >= 0;
            })
            ->values();

        if ($prices->isEmpty()) {
            return [0, 0];
        }

        return [$prices->min(), $prices->max()];
    }

    private function resolveMinPriceRange(Product $product): array
    {
        $minPrices = $product->variants
            ->map(function ($variant) {
                return (float) ($variant->latestPriceRule?->min_price ?? 0);
            })
            ->filter(function ($price) {
                return $price >= 0;
            })
            ->values();

        if ($minPrices->isEmpty()) {
            return [0, 0];
        }

        return [$minPrices->min(), $minPrices->max()];
    }
}
