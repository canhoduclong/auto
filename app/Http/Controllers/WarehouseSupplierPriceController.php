<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierProductPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->with(['supplier', 'product'])
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
            'to'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePricePayload($request);

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

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Đã cập nhật bảng giá.']);
        }

        return back()->with('success', 'Đã cập nhật bảng giá thu mua.');
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
