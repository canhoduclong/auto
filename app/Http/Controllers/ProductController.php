<?php

namespace App\Http\Controllers;

use App\Enums\ProductUnit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaLink;
use App\Models\ProductPriceLog;
use App\Models\ProductPriceRule;
use App\Models\ProductCuttingComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        // Kiêm tra quyền xem có được view không
        $this->authorize('viewAny', Product::class);
        
        // Bắt đầu với một query cơ bản
        $query = Product::with(['brand', 'variants.latestPriceRule']);

        // Tìm kiếm theo tên sản phẩm
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // Lọc theo danh mục
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Lọc theo trạng thái sản phẩm (active/deleted)
        $statusFilter = $request->input('status_filter', 'active');
        if ($statusFilter === 'active') {
            $query->where('status', true);
        } elseif ($statusFilter === 'deleted') {
            $query->where('status', false);
        }

        // Sắp xếp sản phẩm
        $sort_by = $request->get('sort_by', 'name'); // mặc định là 'name'
        $sort_direction = $request->get('sort_direction', 'asc'); // mặc định là asc

        if ($request->filled('sort')) {
            $sortOrder = $request->input('order', 'asc');
            $query->orderBy($request->input('sort'), $sortOrder);
        } else {
            $query->orderBy('sort_order')->orderBy('created_at', 'desc'); // Sắp xếp mặc định
        }

        $page =(int) $request->get('page', 1);
        $perPage = (int) $request->get('perPage', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $products = $query->paginate($perPage)->appends($request->query());
        $pageCount = $products->lastPage();

        $categories = Category::all();

        return view('products.index', compact('products', 'categories', 'sort_by', 'sort_direction','perPage', 'page','pageCount', 'statusFilter'));
    }
    public function create()
    {
        $this->authorize('create', Product::class);
        $categories = Category::all();
        $brands = Brand::all();
        $unitOptions = ProductUnit::options();
        $typeOptions = Product::typeOptions();

        return view('products.create', compact('categories', 'brands', 'unitOptions', 'typeOptions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);
        $data = $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable', 
            'category_id' => 'required|numeric',
            'brand_id' => 'nullable|numeric',
            'unit' => ['required', Rule::in(ProductUnit::values())],
            'product_type' => ['required', Rule::in(array_keys(Product::typeOptions()))],
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);
        $data['user_id'] = Auth::id();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['product_type'] = $data['product_type'] ?? Product::TYPE_WHOLE;

        $product = Product::create($data);

        if ($request->filled('media_id')) {
            MediaLink::where('model_id', $product->id)
                ->whereIn('model_type', [$product->getMorphClass(), Product::class])
                ->where('role', 'avatar')
                ->delete();

            MediaLink::create([
                'model_type' => $product->getMorphClass(),
                'model_id'   => $product->id,
                'role'       => 'avatar',
                'media_id'   => $request->media_id,
            ]);
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 10);
        return redirect()->route('products.index', ['page' => $page, 'perPage' => $perPage])
            ->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);
        $product->load('variants.avatar.media', 'category', 'brand', 'avatar.media', 'gallery.media');
        $categories = Category::all();
        $brands = Brand::all();
        $unitOptions = ProductUnit::options();
        $typeOptions = Product::typeOptions();

        return view('products.show', compact('product', 'categories', 'brands', 'unitOptions', 'typeOptions'));
    }

    public function edit(Request $request, $id)
    {
        $page =(int) $request->get('page', 1);
        $perPage = (int) $request->get('perPage', 10);
        
        $product = Product::with([
            'category',
            'brand',
            'avatar.media',
            'gallery.media',
            'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'variants.avatar.media',
            'variants.componentRatios',
            'cuttingComponents.componentVariant.product',
        ])->findOrFail($id);
        $categories = Category::all();
        $brands = Brand::all();
        $unitOptions = ProductUnit::options();
        $typeOptions = Product::typeOptions();
        $cutComponentVariants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->where('product_type', Product::TYPE_CUT))
            ->where('status', true)
            ->orderBy('product_id')
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product','page','perPage','categories', 'brands', 'unitOptions', 'typeOptions', 'cutComponentVariants'));

    }

    public function getQuickEditForm(Product $product)
    {
        $unitOptions = ProductUnit::options();

        return view('products._quick-edit-form', compact('product', 'unitOptions'));
    }
    
    
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        if ($request->ajax()) {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'price' => 'nullable|numeric',
                'stock' => 'nullable|numeric',
                'unit'  => ['required', Rule::in(ProductUnit::values())],
                'product_type' => ['nullable', Rule::in(array_keys(Product::typeOptions()))],
                'sort_order' => 'nullable|integer|min:0|max:999999',
                'media_id' => 'nullable|integer|exists:media,id',
            ]);

            $product->name = $validated['name'];
            if(isset($validated['price'])) {
                $product->price = $validated['price'];
            }
            if(isset($validated['stock'])) {
                $product->stock = $validated['stock'];
            }
            $product->unit = $validated['unit'];
            if (isset($validated['product_type'])) {
                $product->product_type = $validated['product_type'];
            }
            $product->sort_order = (int) ($validated['sort_order'] ?? 0);

            if ($request->filled('media_id')) {
                MediaLink::where('model_id', $product->id)
                    ->whereIn('model_type', [$product->getMorphClass(), Product::class])
                    ->where('role', 'avatar')
                    ->delete();

                MediaLink::create([
                    'model_type' => $product->getMorphClass(),
                    'model_id'   => $product->id,
                    'role'       => 'avatar',
                    'media_id'   => $validated['media_id'],
                ]);

                $product->load('avatar.media'); // Reload the relationship
            }

            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công!',
                'product' => [
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'unit' => $product->unit,
                    'unit_label' => $product->unit_label,
                    'image_url' => $product->avatar && $product->avatar->media ? asset('storage/' . $product->avatar->media->file_path) : null,
                ]
            ]);
        }

        DB::beginTransaction();
        try {
            // ===== Validate dữ liệu product =====
            $validated = $request->validate([
                'name'        => 'required',
                'description' => 'nullable',
                'category_id' => 'required|numeric',
                'brand_id' => 'nullable|numeric',
                'unit' => ['required', Rule::in(ProductUnit::values())],
                'product_type' => ['required', Rule::in(array_keys(Product::typeOptions()))],
                'sort_order' => 'nullable|integer|min:0|max:999999',
                'media_id'    => 'nullable|integer|exists:media,id',
                'gallery'     => 'nullable|array',
                'gallery.*'   => 'integer|exists:media,id',
                'variants'    => 'nullable|array',
                'variants.*.sku' => 'nullable|string|max:255',
                'variants.*.size' => 'nullable|string|max:255',
                'variants.*.kg' => 'nullable|numeric|gt:0',
                'variants.*.is_priced_by_kg' => 'nullable|boolean',
                'variants.*.quality' => 'nullable|string|max:255',
                'variants.*.production_date' => 'nullable|date',
                'variants.*.stock' => 'nullable|integer|min:0',
                'variants.*.sort_order' => 'nullable|integer|min:0|max:999999',
                'variants.*.media_id' => 'nullable|integer|exists:media,id',
                'cutting_component_variant_ids' => 'nullable|array',
                'cutting_component_variant_ids.*' => 'integer|exists:product_variants,id',
            ]);

            // ===== Cập nhật thông tin cơ bản =====
            $product->update([
                'name'        => $validated['name'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'],
                'unit' => $validated['unit'],
                'product_type' => $validated['product_type'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'description' => $validated['description'] ?? $product->description,
            ]);

            $this->syncCuttingComponentTemplate($validated, $product);

            // ===== Cập nhật avatar =====
            if (!empty($validated['media_id'])) {
                MediaLink::where('model_id', $product->id)
                        ->whereIn('model_type', [$product->getMorphClass(), Product::class])
                    ->where('role', 'avatar')
                    ->delete();

                MediaLink::create([
                    'media_id'   => $validated['media_id'],
                    'model_id'   => $product->id,
                        'model_type' => $product->getMorphClass(),
                    'role'       => 'avatar',
                ]);
            }

            // ===== Cập nhật gallery =====
            if ($request->filled('gallery')) {
                MediaLink::where('model_id', $product->id)
                        ->whereIn('model_type', [$product->getMorphClass(), Product::class])
                    ->where('role', 'gallery')
                    ->delete();

                foreach ($validated['gallery'] as $mediaId) {
                    MediaLink::create([
                        'media_id'   => $mediaId,
                        'model_id'   => $product->id,
                            'model_type' => $product->getMorphClass(),
                        'role'       => 'gallery',
                    ]);
                }
            }
           
            // ===== Cập nhật biến thể (variants) =====
            $inputVariants = $validated['variants'] ?? [];
            $keepIds = [];

            foreach ($inputVariants as $variantId => $variantData) {
                $rawSku = isset($variantData['sku']) ? trim((string) $variantData['sku']) : '';
                $skuFromInput = $rawSku !== '' ? $rawSku : null;

                if (is_numeric($variantId)) {
                    // Biến thể cũ
                    $variant = ProductVariant::updateOrCreate(
                        ['id' => $variantId, 'product_id' => $product->id],
                        [
                            'sku'             => $skuFromInput ?: ProductVariant::where('id', $variantId)->value('sku') ?: Str::upper(Str::random(10)),
                            'size'            => $variantData['size'] ?? null,
                            'kg'              => isset($variantData['kg']) ? (float) $variantData['kg'] : 1,
                            'is_priced_by_kg' => (bool) ($variantData['is_priced_by_kg'] ?? true),
                            'quality'         => array_key_exists('quality', $variantData) ? $variantData['quality'] : ProductVariant::where('id', $variantId)->value('quality'),
                            'production_date' => array_key_exists('production_date', $variantData) ? $variantData['production_date'] : ProductVariant::where('id', $variantId)->value('production_date'),
                            'stock'           => $variantData['stock'] ?? 0,
                            'sort_order'      => (int) ($variantData['sort_order'] ?? 0),
                        ]
                    );
                    $keepIds[] = $variant->id;
                } else {
                    // Biến thể mới
                    $variant = ProductVariant::create([
                        'product_id'       => $product->id,
                        'sku'              => $skuFromInput ?? Str::upper(Str::random(10)),
                        'size'             => $variantData['size'] ?? null,
                        'kg'               => isset($variantData['kg']) ? (float) $variantData['kg'] : 1,
                        'is_priced_by_kg'  => (bool) ($variantData['is_priced_by_kg'] ?? true),
                        'quality'          => $variantData['quality'] ?? null,
                        'production_date'  => $variantData['production_date'] ?? null,
                        'stock'            => $variantData['stock'] ?? 0,
                        'sort_order'       => (int) ($variantData['sort_order'] ?? 0),
                    ]);
                    $keepIds[] = $variant->id;
                }

                // ===== Gán media cho biến thể =====
                if (!empty($variantData['media_id'])) {
                    MediaLink::where('model_id', $variant->id)
                        ->whereIn('model_type', [$variant->getMorphClass(), ProductVariant::class])
                        ->where('role', 'variant')
                        ->delete();

                    MediaLink::create([
                        'model_type' => $variant->getMorphClass(),
                        'model_id'   => $variant->id,
                        'role'       => 'variant',
                        'media_id'   => $variantData['media_id'],
                    ]);
                } else {
                    // Nếu không có media_id thì xóa link cũ (nếu có)
                    MediaLink::where([
                        'model_id'   => $variant->id,
                        'role'       => 'variant',
                        ])
                        ->whereIn('model_type', [$variant->getMorphClass(), ProductVariant::class])
                        ->delete();
                }

                // ===== Xử lý giá biến thể =====
                $newPrice = $variantData['price'] ?? null;
                if (!$newPrice) {
                    $newPrice = $product->default_price ?? 0;
                }
                $currentRule = $variant->priceRules()
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->latest('start_date')
                    ->first();
                if (!$currentRule || $currentRule->price != $newPrice) {
                    $rule = $variant->priceRules()->create([
                        'reason'     => $variantData['reason'] ?? 'Cập nhật giá',
                        'price'      => $newPrice,
                        'start_date' => now(),
                        'created_by' => Auth::id(),
                    ]);
                    $variant->priceLogs()->create([
                        'product_variant_id' => $variant->id,
                        'user_id'            => Auth::id(),
                        'price_rule_id'      => $rule->id,
                        'old_price'          => $currentRule->price ?? 0,
                        'new_price'          => $newPrice,
                        'applied_at'         => now(),
                        'applied_by'         => Auth::id(),
                    ]);
                }
            }
            // Xóa các biến thể không còn trong request
            if (!empty($keepIds)) {
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }

            DB::commit();

            $page = $request->input('page', 1);
            $perPage = $request->input('perPage', 10);
            return redirect()->route('products.index', ['page' => $page, 'perPage' => $perPage])
                ->with('success', 'Cập nhật sản phẩm thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Sinh SKU cho variant mới
     */
    protected function generateVariantSku(Product $product)
    {
        $base = Str::upper(Str::slug($product->name, '-'));
        $rand = strtoupper(Str::random(5));
        $sku  = $base . '-' . $rand;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $rand = strtoupper(Str::random(5));
            $sku  = $base . '-' . $rand;
        }

        return $sku;
    }

    private function syncCuttingComponentTemplate(array $validated, Product $product): void
    {
        if ((string) $product->product_type !== Product::TYPE_WHOLE) {
            ProductCuttingComponent::query()->where('product_id', $product->id)->delete();
            return;
        }

        $componentIds = collect($validated['cutting_component_variant_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        ProductCuttingComponent::query()
            ->where('product_id', $product->id)
            ->whereNotIn('component_product_variant_id', $componentIds->all())
            ->delete();

        foreach ($componentIds as $componentId) {
            ProductCuttingComponent::firstOrCreate([
                'product_id' => $product->id,
                'component_product_variant_id' => $componentId,
            ]);
        }
    }


    public function updateSortOrder(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'top' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('top')) {
            DB::transaction(function () use ($product): void {
                Product::query()
                    ->whereKeyNot($product->id)
                    ->increment('sort_order');

                $product->forceFill(['sort_order' => 0])->save();
            });
        } else {
            $product->forceFill([
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật thứ tự sản phẩm.',
            'sort_order' => (int) $product->fresh()->sort_order,
        ]);
    }


    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Chỉ admin mới được quyền thực hiện xóa mềm sản phẩm.
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Chỉ admin mới được phép xóa sản phẩm.');
        }

        try {
            DB::transaction(function () use ($product): void {
                $product->update(['status' => false]);

                // Đồng bộ trạng thái biến thể, tránh hiển thị ngoài frontend.
                ProductVariant::where('product_id', $product->id)->update(['status' => false]);
            });

            if (request()->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Sản phẩm đã được chuyển sang trạng thái đã xóa.',
                ]);
            }

            $page = request('page', 1);
            $perPage = request('perPage', 10);
            return redirect()
                ->route('products.index', ['page' => $page, 'perPage' => $perPage])
                ->with('success', 'Sản phẩm đã được chuyển sang trạng thái đã xóa.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không thể cập nhật trạng thái sản phẩm. Lỗi: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('products.index')
                ->with('error', 'Không thể cập nhật trạng thái sản phẩm!');
        }
    }

    public function restore(Product $product)
    {
        $this->authorize('update', $product);

        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Chỉ admin mới được phép khôi phục sản phẩm.');
        }

        try {
            DB::transaction(function () use ($product): void {
                $product->update(['status' => true]);

                // Khôi phục lại trạng thái biến thể đã xóa mềm cùng sản phẩm.
                ProductVariant::where('product_id', $product->id)->update(['status' => true]);
            });

            $page = request('page', 1);
            $perPage = request('perPage', 10);
            return redirect()
                ->route('products.index', ['page' => $page, 'perPage' => $perPage])
                ->with('success', 'Sản phẩm đã được khôi phục thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->route('products.index')
                ->with('error', 'Không thể khôi phục sản phẩm: ' . $e->getMessage());
        }
    }

    public function search(Request $request)
    {
        $keyword = $request->input('keyword');

        $variants = ProductVariant::with(['product.avatar.media', 'latestPriceRule', 'avatar.media'])
            ->where(function($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            })
            ->orWhereHas('product', function($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->paginate(12);

        $settings = \App\Models\Setting::all()->keyBy('key');
        $categories = \App\Models\Category::all();

        return view('products.search-results', compact('variants', 'keyword', 'settings', 'categories'));
    }
}
