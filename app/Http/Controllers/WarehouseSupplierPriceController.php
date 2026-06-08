<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPriceLog;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierProductPrice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseSupplierPriceController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $productId = $request->integer('product_id') ?: null;
        $status = $request->input('status');
        $from = $request->input('from');
        $to = $request->input('to');

        $supplierProducts = SupplierProduct::query()
            ->with(['supplier', 'product.variants.latestPriceRule'])
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $latestPrices = SupplierProductPrice::query()
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('supplier_product_prices')
                    ->groupBy('supplier_id', 'product_id');
            })
            ->when($from, fn ($query) => $query->whereDate('effective_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('effective_date', '<=', $to))
            ->get()
            ->keyBy(fn ($price) => $price->supplier_id . ':' . $price->product_id);

        $saleSyncStatus = $supplierProducts->getCollection()
            ->mapWithKeys(function (SupplierProduct $row) use ($latestPrices) {
                $key = $row->supplier_id . ':' . $row->product_id;
                $latest = $latestPrices->get($key);
                $variants = $row->product?->variants ?? collect();

                $isSynced = false;
                if ($latest && $variants->isNotEmpty()) {
                    $targetPrice = (float) $latest->today_sale_price;
                    $targetMinPrice = (float) $latest->min_price;

                    $isSynced = $variants->every(function (ProductVariant $variant) use ($targetPrice, $targetMinPrice) {
                        $latestRule = $variant->latestPriceRule;

                        return $latestRule
                            && (float) $latestRule->price === $targetPrice
                            && (float) ($latestRule->min_price ?? 0) === $targetMinPrice;
                    });
                }

                return [$key => $isSynced];
            });

        $suppliers = Supplier::query()->orderBy('name')->get();
        $products = Product::query()->where('status', true)->orderBy('name')->get();

        return view('warehouse.supplier-prices.index', compact(
            'supplierProducts',
            'latestPrices',
            'suppliers',
            'products',
            'supplierId',
            'productId',
            'status',
            'from',
            'to',
            'saleSyncStatus'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePricePayload($request);

        $product = Product::query()
            ->with(['variants' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($validated['product_id']);

        if ($product->variants->isEmpty()) {
            return back()->with('error', 'Sản phẩm chưa có biến thể để cập nhật giá bán.');
        }

        $salePriceReason = trim((string) ($validated['note'] ?? ''));
        if ($salePriceReason === '') {
            $salePriceReason = 'Cập nhật giá bán từ bảng giá thu mua';
        }

        $updatedVariants = 0;

        DB::transaction(function () use ($validated, $product, $salePriceReason, &$updatedVariants) {
            SupplierProduct::query()->updateOrCreate(
                [
                    'supplier_id' => $validated['supplier_id'],
                    'product_id' => $validated['product_id'],
                ],
                [
                    'active' => true,
                    'price_calculation_type' => $validated['price_calculation_type'],
                ]
            );

            SupplierProductPrice::query()->create($validated + [
                'created_by' => Auth::id(),
            ]);

            $updatedVariants = $this->applySalePriceToVariants(
                $product->variants,
                (float) $validated['today_sale_price'],
                (float) $validated['min_price'],
                (string) $validated['effective_date'],
                $salePriceReason
            );
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $updatedVariants > 0
                    ? 'Đã cập nhật bảng giá và giá bán hôm nay.'
                    : 'Đã cập nhật bảng giá. Giá bán hôm nay không thay đổi.',
                'updated_variants' => $updatedVariants,
            ]);
        }

        $message = $updatedVariants > 0
            ? "Đã cập nhật bảng giá thu mua và giá bán cho {$updatedVariants} biến thể."
            : 'Đã cập nhật bảng giá thu mua. Giá bán hôm nay không thay đổi.';

        return back()->with('success', $message);
    }

    public function supplierProducts(Supplier $supplier): JsonResponse
    {
        $rows = SupplierProduct::query()
            ->with(['product.variants' => fn ($query) => $query->where('status', true)->orderBy('name')])
            ->where('supplier_id', $supplier->id)
            ->where('active', true)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (SupplierProduct $row) use ($supplier) {
                $latestPrice = $this->latestPriceFor($supplier->id, $row->product_id);

                return [
                    'product_id' => (int) $row->product_id,
                    'name' => (string) ($row->product?->name ?? ''),
                    'unit_label' => (string) ($row->product?->unit_label ?? 'Cái'),
                    'price_calculation_type' => (string) ($row->price_calculation_type ?? SupplierProduct::TYPE_COMPONENT_BASED),
                    'latest_price' => $latestPrice ? (float) $latestPrice->stock_in_unit_cost : null,
                    'today_sale_price' => $latestPrice ? (float) $latestPrice->today_sale_price : null,
                    'latest_price_date' => optional($latestPrice?->effective_date)->toDateString(),
                    'price_id' => $latestPrice?->id,
                    'variants' => ($row->product?->variants ?? collect())->map(function (ProductVariant $variant) use ($latestPrice, $row) {
                        return [
                            'variant_id' => (int) $variant->id,
                            'product_id' => (int) $row->product_id,
                            'sku' => (string) ($variant->sku ?? ''),
                            'name' => (string) ($variant->name ?? ''),
                            'label' => trim(($row->product?->name ?? 'Sản phẩm') . ' - ' . ($variant->name ?? 'Biến thể')),
                            'unit_label' => (string) ($row->product?->unit_label ?? 'Cái'),
                            'weight_per_unit' => (float) ($variant->effective_kg ?? 1),
                            'price_calculation_type' => (string) ($row->price_calculation_type ?? SupplierProduct::TYPE_COMPONENT_BASED),
                            'latest_price' => $latestPrice ? (float) $latestPrice->stock_in_unit_cost : null,
                            'today_sale_price' => $latestPrice ? (float) $latestPrice->today_sale_price : null,
                            'latest_price_date' => optional($latestPrice?->effective_date)->toDateString(),
                            'price_id' => $latestPrice?->id,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function latestPrice(Supplier $supplier, Product $product): JsonResponse
    {
        $price = $this->latestPriceFor($supplier->id, $product->id);

        if (!$price) {
            return response()->json([
                'ok' => false,
                'message' => 'Sản phẩm chưa có bảng giá hiện hành.',
                'data' => null,
            ], 404);
        }

        return response()->json(['ok' => true, 'data' => $this->pricePayload($price)]);
    }

    public function priceHistory(Supplier $supplier, Product $product): JsonResponse
    {
        $rows = SupplierProductPrice::query()
            ->with('creator:id,name')
            ->where('supplier_id', $supplier->id)
            ->where('product_id', $product->id)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'ok' => true,
            'data' => $rows->getCollection()->map(fn ($price) => $this->pricePayload($price))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function attachProduct(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price_calculation_type' => ['nullable', 'in:component_based,direct_purchase'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        SupplierProduct::query()->updateOrCreate(
            ['supplier_id' => $supplier->id, 'product_id' => $validated['product_id']],
            [
                'active' => true,
                'price_calculation_type' => $validated['price_calculation_type'] ?? SupplierProduct::TYPE_COMPONENT_BASED,
                'note' => $validated['note'] ?? null,
            ]
        );

        return back()->with('success', 'Đã thêm sản phẩm cho nhà cung cấp.');
    }

    public function detachProduct(Supplier $supplier, Product $product)
    {
        SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->where('product_id', $product->id)
            ->update(['active' => false]);

        return back()->with('success', 'Đã tắt sản phẩm của nhà cung cấp.');
    }

    public function applyTodaySalePrice(int $supplier, int $product)
    {
        $productModel = Product::query()->findOrFail($product);
        $latestPrice = $this->latestPriceFor($supplier, $productModel->id);

        if (!$latestPrice) {
            return back()->with('error', 'Sản phẩm chưa có bảng giá để áp dụng.');
        }

        $variants = $productModel->variants()->orderBy('id')->get();
        if ($variants->isEmpty()) {
            return back()->with('error', 'Sản phẩm chưa có biến thể để cập nhật giá bán.');
        }

        $effectiveDate = now()->toDateString();
        $reason = 'Dùng giá NCC ngày ' . optional($latestPrice->effective_date)->format('d/m/Y');

        $updatedVariants = DB::transaction(function () use ($variants, $latestPrice, $effectiveDate, $reason) {
            return $this->applySalePriceToVariants(
                $variants,
                (float) $latestPrice->today_sale_price,
                (float) $latestPrice->min_price,
                $effectiveDate,
                $reason
            );
        });

        if ($updatedVariants === 0) {
            return back()->with('success', 'Giá bán hiện tại đã trùng với "Giá bán hôm nay" của nhà cung cấp.');
        }

        return back()->with('success', "Đã dùng giá này và cập nhật {$updatedVariants} biến thể.");
    }

    private function validatePricePayload(Request $request): array
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'effective_date' => ['required', 'date'],
            'price_calculation_type' => ['required', 'in:component_based,direct_purchase'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'material_price' => ['nullable', 'numeric', 'min:0'],
            'processing_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'suggested_margin' => ['nullable', 'numeric', 'min:0'],
            'today_sale_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $type = $validated['price_calculation_type'];
        $purchase = (float) ($validated['purchase_price'] ?? 0);
        $material = (float) ($validated['material_price'] ?? 0);
        $processing = $type === SupplierProductPrice::TYPE_COMPONENT_BASED ? (float) ($validated['processing_cost'] ?? 0) : 0.0;
        $other = $type === SupplierProductPrice::TYPE_COMPONENT_BASED ? (float) ($validated['other_cost'] ?? 0) : 0.0;
        $margin = (float) ($validated['suggested_margin'] ?? 2000);
        $minPrice = $type === SupplierProductPrice::TYPE_DIRECT_PURCHASE
            ? $purchase
            : ($material + $processing + $other);
        $todaySalePrice = (float) ($validated['today_sale_price'] ?? ($minPrice + $margin));

        if ($todaySalePrice < $minPrice) {
            abort(422, 'Giá bán hôm nay phải lớn hơn hoặc bằng giá min.');
        }

        $validated['purchase_price'] = $type === SupplierProductPrice::TYPE_DIRECT_PURCHASE ? $purchase : 0;
        $validated['material_price'] = $material;
        $validated['processing_cost'] = $processing;
        $validated['other_cost'] = $other;
        $validated['min_price'] = $minPrice;
        $validated['suggested_margin'] = $margin;
        $validated['today_sale_price'] = $todaySalePrice;

        return $validated;
    }

    private function latestPriceFor(int $supplierId, int $productId): ?SupplierProductPrice
    {
        return SupplierProductPrice::query()
            ->with('creator:id,name')
            ->where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     */
    private function applySalePriceToVariants($variants, float $newPrice, float $newMinPrice, string $effectiveDate, string $reason): int
    {
        $updatedCount = 0;

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

        return $updatedCount;
    }

    private function pricePayload(SupplierProductPrice $price): array
    {
        return [
            'price_id' => (int) $price->id,
            'supplier_id' => (int) $price->supplier_id,
            'product_id' => (int) $price->product_id,
            'effective_date' => optional($price->effective_date)->toDateString(),
            'price_calculation_type' => (string) ($price->price_calculation_type ?? SupplierProductPrice::TYPE_COMPONENT_BASED),
            'purchase_price' => (float) $price->purchase_price,
            'material_price' => (float) $price->material_price,
            'processing_cost' => (float) $price->processing_cost,
            'other_cost' => (float) $price->other_cost,
            'min_price' => (float) $price->min_price,
            'suggested_margin' => (float) $price->suggested_margin,
            'today_sale_price' => (float) $price->today_sale_price,
            'stock_in_unit_cost' => (float) $price->stock_in_unit_cost,
            'note' => (string) ($price->note ?? ''),
            'created_by_name' => (string) ($price->creator?->name ?? ''),
            'created_at' => optional($price->created_at)->toDateTimeString(),
        ];
    }
}
