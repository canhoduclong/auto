<?php namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductComponentRatio;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function duplicate(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);
        if (!Gate::allows('duplicate', $variant)) {
            abort(403, 'Bạn không có quyền nhân bản biến thể này.');
        }
        // Tạo bản sao
        $new = $variant->replicate(['sku']);
        // Tạo SKU mới (nếu trùng)
        $new->sku = $variant->sku . '-COPY-' . strtoupper(Str::random(4));
        $new->push();
        // Copy media nếu có
        if ($variant->mediaLink) {
            $new->mediaLink()->create([
                'media_id' => $variant->mediaLink->media_id,
                'role' => 'variant',
            ]);
        }
        // Copy các giá trị thuộc tính (nếu có)
        if (method_exists($variant, 'values')) {
            $new->values()->sync($variant->values->pluck('id')->toArray());
        }
        // Copy price rule cuối cùng
        $latestPrice = $variant->latestPriceRule?->price;
        if ($latestPrice) {
            $new->priceRules()->create([
                'price' => $latestPrice,
                'start_date' => now(),
                'reason' => 'Nhân bản từ biến thể #' . $variant->id,
            ]);
        }
        return redirect()->route('product-variants.index')->with('success', 'Đã nhân bản biến thể thành công!');
    }
    public function edit($id)
    {
        $variant = \App\Models\ProductVariant::with([
            'mediaLink.media',
            'componentRatios',
            'product.cuttingComponents.componentVariant.product',
        ])->findOrFail($id);
        
        $products = \App\Models\Product::orderBy('sort_order')->orderBy('name')->get();
        return view('product_variants.edit', compact('variant', 'products'));
    }

    public function update(Request $request, $id)
    {
        $variant = \App\Models\ProductVariant::findOrFail($id);
        $data = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'name' => 'nullable|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku,' . $variant->id,
            'size' => 'nullable',
            'kg' => 'nullable|numeric|gt:0',
            'is_priced_by_kg' => 'nullable|boolean',
            'quality' => 'nullable|string',
            'production_date' => 'nullable|date',
            'stock' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'media_id' => 'nullable|integer|exists:media,id',
            'price' => 'nullable|numeric|min:0',
            'component_weights' => 'nullable|array',
            'component_percentages' => 'nullable|array',
            'component_weights.*' => 'nullable|numeric|min:0',
            'component_percentages.*' => 'nullable|numeric|min:0|max:100',
        ]);
        // Chuyển size từ 2,5 thành 2.5 nếu là string
        if (isset($data['size']) && is_string($data['size'])) {
            $data['size'] = str_replace(',', '.', $data['size']);
        }
        if ($request->has('is_priced_by_kg')) {
            $data['is_priced_by_kg'] = $request->boolean('is_priced_by_kg');
        } else {
            unset($data['is_priced_by_kg']);
        }
        unset($data['component_weights'], $data['component_percentages']);
        // Ensure SKU is updated correctly
        if (isset($data['sku'])) {
            $variant->sku = $data['sku'];
            $variant->save();
        }
        $variant->update($data);
        // Cập nhật giá nếu có thay đổi
        if (isset($data['price'])) {
            $latestPrice = $variant->latestPriceRule?->price;
            if ($latestPrice != $data['price']) {
                $variant->priceRules()->create([
                    'price' => $data['price'],
                    'start_date' => now(),
                    'reason' => 'Cập nhật nhanh',
                    'created_by' => auth()->id(),
                ]);
            }
        }
        // Gán lại media cho biến thể
        if (!empty($data['media_id'])) {
            \App\Models\MediaLink::updateOrCreate(
                [
                    'model_type' => $variant::class,
                    'model_id'   => $variant->id,
                    'role'       => 'variant',
                ],
                [
                    'media_id'   => $data['media_id'],
                ]
            );
        } else {
            \App\Models\MediaLink::where([
                'model_type' => $variant::class,
                'model_id'   => $variant->id,
                'role'       => 'variant',
            ])->delete();
        }
        $variant->refresh()->load('product.cuttingComponents');
        $this->validateCuttingComponentTotals($request, $variant);
        $this->syncCuttingComponentRatios($request, $variant);
        return redirect()->route('product-variants.index')->with('success', 'Đã cập nhật biến thể thành công!');
    }

    private function validateCuttingComponentTotals(Request $request, ProductVariant $variant): void
    {
        if (!$request->has('component_weights') && !$request->has('component_percentages')) {
            return;
        }

        if ((string) ($variant->product?->product_type ?? '') !== Product::TYPE_WHOLE) {
            return;
        }

        $variant->loadMissing('product.cuttingComponents');

        $templateComponentIds = $variant->product->cuttingComponents
            ->pluck('component_product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($templateComponentIds->isEmpty()) {
            return;
        }

        $weights = collect($request->input('component_weights', []));
        $percentages = collect($request->input('component_percentages', []));

        $totalWeight = $templateComponentIds->sum(fn ($componentId) => (float) $weights->get((string) $componentId, 0));
        $totalPercentage = $templateComponentIds->sum(fn ($componentId) => (float) $percentages->get((string) $componentId, 0));
        $variantKg = (float) $variant->effective_kg;

        $errors = [];
        if (abs($totalPercentage - 100) > 0.001) {
            $errors['component_percentages'] = 'Tổng tỷ lệ thành phần pha lóc phải bằng 100%.';
        }

        if ($totalWeight - $variantKg > 0.001) {
            $errors['component_weights'] = 'Tổng kg thành phần pha lóc không được lớn hơn ' . rtrim(rtrim(number_format($variantKg, 3, '.', ''), '0'), '.') . ' kg của biến thể.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function syncCuttingComponentRatios(Request $request, ProductVariant $variant): void
    {
        if (!$request->has('component_weights') && !$request->has('component_percentages')) {
            return;
        }

        if ((string) ($variant->product?->product_type ?? '') !== Product::TYPE_WHOLE) {
            return;
        }

        $templateComponentIds = $variant->product->cuttingComponents
            ->pluck('component_product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        ProductComponentRatio::query()
            ->where('source_product_variant_id', $variant->id)
            ->whereNotIn('component_product_variant_id', $templateComponentIds->all())
            ->delete();

        foreach ($templateComponentIds as $componentId) {
            ProductComponentRatio::updateOrCreate(
                [
                    'source_product_variant_id' => $variant->id,
                    'component_product_variant_id' => $componentId,
                ],
                [
                    'standard_weight' => (float) data_get($request->input('component_weights', []), (string) $componentId, 0),
                    'percentage' => (float) data_get($request->input('component_percentages', []), (string) $componentId, 0),
                ]
            );
        }
    }

    public function quickUpdateCuttingComponents(Request $request, ProductVariant $variant)
    {
        $data = $request->validate([
            'component_weights' => 'nullable|array',
            'component_percentages' => 'nullable|array',
            'component_weights.*' => 'nullable|numeric|min:0',
            'component_percentages.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $variant->load('product.cuttingComponents');
        if ((string) ($variant->product?->product_type ?? '') !== Product::TYPE_WHOLE) {
            abort(422, 'Chỉ biến thể thuộc sản phẩm nguyên con mới có thành phần pha lóc.');
        }

        if ($variant->product->cuttingComponents->isEmpty()) {
            abort(422, 'Sản phẩm này chưa có mẫu thành phần pha lóc.');
        }

        $this->validateCuttingComponentTotals($request->merge($data), $variant);
        $this->syncCuttingComponentRatios($request->merge($data), $variant);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Đã cập nhật thành phần pha lóc.']);
        }

        return back()->with('success', 'Đã cập nhật thành phần pha lóc.');
    }

    public function index(Request $request)
    {
        $query = ProductVariant::with([
            'product.avatar.media',
            'product.cuttingComponents.componentVariant.product',
            'componentRatios',
            'mediaLink.media',
        ]);
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('sku', 'like', "%$q%")
                  ->orWhere('size', 'like', "%$q%")
                  ->orWhere('quality', 'like', "%$q%")
                  ->orWhereHas('product', function($sub) use ($q) {
                      $sub->where('name', 'like', "%$q%") ;
                  });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('production_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('production_date', '<=', $request->input('to_date'));
        }

        if ($request->filled('min_stock')) {
            $query->where('stock', '>=', $request->input('min_stock'));
        }

        if ($request->filled('max_stock')) {
            $query->where('stock', '<=', $request->input('max_stock'));
        }

        $perPage = $request->input('per_page', 20);
        $sortBy = $request->input('sort');
        $sortDir = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        if ($sortBy === 'stock') {
            $query->orderBy('product_id')->orderBy('stock', $sortDir);
        } else {
            $query
                ->join('products as sort_products', 'sort_products.id', '=', 'product_variants.product_id')
                ->select('product_variants.*')
                ->orderBy('sort_products.sort_order')
                ->orderBy('product_variants.sort_order')
                ->orderBy('product_variants.id');
        }
        $variants = $query->paginate($perPage)->appends($request->query());
        $groupedVariants = $variants->getCollection()->groupBy('product_id');

        if ($request->ajax()) {
            return view('product_variants._variants_table', compact('variants'))->render();
        }

        $products = \App\Models\Product::orderBy('sort_order')->orderBy('name')->get();
        return view('product_variants.index', compact('groupedVariants', 'variants', 'products'));
    }

    public function bulkDelete(Request $request)
    {
        Gate::authorize('bulk-delete', ProductVariant::class);
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:product_variants,id',
        ]);

        ProductVariant::whereIn('id', $request->input('ids'))->delete();

        return response()->json(['success' => 'Đã xoá thành công các biến thể đã chọn.']);
    }

    public function create()
    {
        $products = \App\Models\Product::orderBy('sort_order')->orderBy('name')->get();
        return view('product_variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'nullable|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku',
            'size' => 'nullable|string',
            'kg' => 'nullable|numeric|gt:0',
            'is_priced_by_kg' => 'nullable|boolean',
            'quality' => 'nullable|string',
            'production_date' => 'nullable|date',
            'stock' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'media_id' => 'nullable|integer|exists:media,id',
            'price' => 'nullable|numeric|min:0',
        ]);
        $data['kg'] = $data['kg'] ?? 1;
        $data['is_priced_by_kg'] = $request->boolean('is_priced_by_kg');
        $variant = ProductVariant::create($data);
        // Gán media nếu có
        if (!empty($data['media_id'])) {
            \App\Models\MediaLink::updateOrCreate([
                'model_type' => $variant::class,
                'model_id'   => $variant->id,
                'role'       => 'variant',
            ], [
                'media_id'   => $data['media_id'],
            ]);
        }
        // Tạo price rule đầu tiên
        $price = $data['price'] ?? null;
        if (!$price) {
            $product = \App\Models\Product::find($data['product_id']);
            $price = $product?->default_price ?? 0;
        }
        $variant->priceRules()->create([
            'price' => $price,
            'start_date' => now(),
            'reason' => 'Giá khởi tạo',
        ]);
        return redirect()->route('product-variants.index')->with('success', 'Đã thêm biến thể thành công!');
    }
}
