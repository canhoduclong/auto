<?php namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

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
        $variant = \App\Models\ProductVariant::findOrFail($id);
        $products = \App\Models\Product::orderBy('name')->get();
        return view('product_variants.edit', compact('variant', 'products'));
    }

    public function update(Request $request, $id)
    {
        $variant = \App\Models\ProductVariant::findOrFail($id);
        $data = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'sku' => 'required|string|unique:product_variants,sku,' . $variant->id,
            'size' => 'nullable|string',
            'quality' => 'nullable|string',
            'production_date' => 'nullable|date',
            'stock' => 'nullable|integer',
            'media_id' => 'nullable|integer|exists:media,id',
            'price' => 'nullable|numeric|min:0',
        ]);
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
        return redirect()->route('product-variants.index')->with('success', 'Đã cập nhật biến thể thành công!');
    }
    public function index(Request $request)
    {
        $query = ProductVariant::with(['product', 'mediaLink.media']);
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
        $variants = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());

        if ($request->ajax()) {
            return view('product_variants._variants_table', compact('variants'))->render();
        }

        $products = \App\Models\Product::orderBy('name')->get();
        return view('product_variants.index', compact('variants', 'products'));
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
        $products = \App\Models\Product::orderBy('name')->get();
        return view('product_variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|unique:product_variants,sku',
            'size' => 'nullable|string',
            'quality' => 'nullable|string',
            'production_date' => 'nullable|date',
            'stock' => 'nullable|integer',
            'media_id' => 'nullable|integer|exists:media,id',
            'price' => 'nullable|numeric|min:0',
        ]);
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
