<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPriceLog;
use App\Models\ProductPriceRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductPriceManagementController extends Controller
{
    public function dashboardEdit(): View
    {
         $settings = \App\Models\Setting::all()->keyBy('key');
        $products = Product::query()
            ->with(['variants' => fn ($query) => $query
                ->with('latestPriceRule')
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $variants = $product->variants->map(function ($variant) use ($product) {
                    $rule = $variant->latestPriceRule;

                    return [
                        'id' => (int) $variant->id,
                        'name' => $variant->name ?: ($variant->size ?: $variant->sku ?: 'Biến thể'),
                        'price' => (float) ($rule?->price ?? $variant->final_price ?? 0),
                        'min_price' => (float) ($rule?->min_price ?? 0),
                        'unit' => $variant->effective_priced_by_kg
                            ? 'kg'
                            : strtolower((string) ($product->unit_label ?: 'cái')),
                    ];
                })->filter(fn (array $variant) => $variant['price'] > 0)->values();

                // Một lần điều chỉnh phải tác động tới toàn bộ biến thể của sản
                // phẩm đang có cùng giá bán, kể cả khi giá min hiện tại khác nhau.
                $groups = $variants->groupBy(fn (array $variant) =>
                    number_format($variant['price'], 2, '.', '')
                );
                $isMixed = $groups->count() > 1;
                $rows = $groups->map(function ($items) use ($product, $isMixed) {
                    $first = $items->first();
                    $rowName = $product->name;

                    if ($isMixed) {
                        $rowName = $first['name'];
                        if ($items->count() > 1) {
                            $rowName .= ' và ' . ($items->count() - 1) . ' biến thể cùng giá';
                        }
                    }

                    return array_merge($first, [
                        'name' => $rowName,
                        'variant_ids' => $items->pluck('id')->all(),
                    ]);
                })->values();

                return [
                    'name' => $product->name,
                    'is_mixed' => $isMixed,
                    'rows' => $rows,
                ];
            })
            ->filter(fn (array $product) => $product['rows']->isNotEmpty())
            ->values();

        return view('products.price-management.dashboard-edit', compact('products', 'settings'));
    }

    public function dashboardUpdate(Request $request)
    {
        $normalizedRows = collect($request->input('rows', []))
            ->map(function ($row) {
                foreach (['price', 'min_price'] as $field) {
                    if (array_key_exists($field, $row)) {
                        $row[$field] = preg_replace('/[^0-9]/', '', (string) $row[$field]);
                    }
                }

                return $row;
            })
            ->all();
        $request->merge(['rows' => $normalizedRows]);

        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.variant_ids' => ['required', 'array', 'min:1'],
            'rows.*.variant_ids.*' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'rows.*.price' => ['required', 'numeric', 'min:0'],
            'rows.*.min_price' => ['required', 'numeric', 'min:0', 'lte:rows.*.price'],
            'rows.*.original_min_price' => ['required', 'numeric', 'min:0'],
        ], [
            'rows.*.min_price.lte' => 'Giá min không được lớn hơn giá điều chỉnh.',
        ]);

        $variantIds = collect($validated['rows'])->pluck('variant_ids')->flatten()->map(fn ($id) => (int) $id);
        abort_if($variantIds->duplicates()->isNotEmpty(), 422, 'Một biến thể không thể xuất hiện ở nhiều dòng.');

        $variants = \App\Models\ProductVariant::query()
            ->with('latestPriceRule')
            ->whereIn('id', $variantIds->all())
            ->get()
            ->keyBy('id');
        $effectiveDate = now()->toDateString();
        $updatedCount = 0;

        DB::transaction(function () use ($validated, $variants, $effectiveDate, &$updatedCount) {
            foreach ($validated['rows'] as $row) {
                $newPrice = (float) $row['price'];
                $submittedMinPrice = (float) $row['min_price'];
                $minPriceWasChanged = abs($submittedMinPrice - (float) $row['original_min_price']) >= .001;

                foreach ($row['variant_ids'] as $variantId) {
                    $variant = $variants->get((int) $variantId);
                    $currentRule = $variant?->latestPriceRule;
                    $oldPrice = (float) ($currentRule?->price ?? $variant?->final_price ?? 0);
                    $oldMinPrice = (float) ($currentRule?->min_price ?? 0);
                    // Nếu người dùng chỉ đổi giá bán, giữ nguyên giá min riêng
                    // của từng biến thể trong nhóm cùng giá.
                    $newMinPrice = $minPriceWasChanged ? $submittedMinPrice : $oldMinPrice;

                    if ($newMinPrice > $newPrice) {
                        throw ValidationException::withMessages([
                            'rows' => 'Giá điều chỉnh không được thấp hơn giá min hiện tại của bất kỳ biến thể nào trong nhóm.',
                        ]);
                    }

                    if (abs($oldPrice - $newPrice) < .001 && abs($oldMinPrice - $newMinPrice) < .001) {
                        continue;
                    }

                    $variant->priceRules()
                        ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $effectiveDate))
                        ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $effectiveDate))
                        ->update(['end_date' => Carbon::parse($effectiveDate)->subDay()->toDateString()]);

                    $nextRule = $variant->priceRules()->whereDate('start_date', '>', $effectiveDate)->orderBy('start_date')->first();
                    $newRule = ProductPriceRule::create([
                        'product_variant_id' => $variant->id,
                        'reason' => 'Điều chỉnh từ bảng giá dashboard',
                        'price' => $newPrice,
                        'min_price' => $newMinPrice,
                        'start_date' => $effectiveDate,
                        'end_date' => $nextRule?->start_date
                            ? Carbon::parse($nextRule->start_date)->subDay()->toDateString()
                            : null,
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
            }
        });

        $message = $updatedCount > 0
            ? "Đã cập nhật giá cho {$updatedCount} biến thể."
            : 'Không có sản phẩm nào thay đổi giá.';

        return redirect()->route('pages.my_dashboard')->with('success', $message);
    }

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
