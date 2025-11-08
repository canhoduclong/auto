<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        // Kiêm tra quyền xem có được view không 
        $this->authorize('viewAny', Product::class);
        
        // Bắt đầu với một query cơ bản
        $query = Product::with('brand');

        // Tìm kiếm theo tên sản phẩm
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // Lọc theo danh mục
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Sắp xếp sản phẩm
        $sort_by = $request->get('sort_by', 'name'); // mặc định là 'name'
        $sort_direction = $request->get('sort_direction', 'asc'); // mặc định là asc 

        if ($request->filled('sort')) {
            $sortOrder = $request->input('order', 'asc');
            $query->orderBy($request->input('sort'), $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc'); // Sắp xếp mặc định
        }

        $page =(int) $request->get('page', 1);
        $perPage = (int) $request->get('perPage', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;      
        $products = $query->paginate($perPage);
        $pageCount = $products->lastPage(); 

        $categories = Category::all(); 

        return view('products.index', compact('products', 'categories', 'sort_by', 'sort_direction','perPage', 'page','pageCount'));
    }
    public function create()
    {
        $this->authorize('create', Product::class);
        $categories = Category::all();
        $brands = Brand::all();
        return view('products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $this->authorize('store', Product::class);
        $data = $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'category_id' => 'required|numeric',
            'brand_id' => 'nullable|numeric',
            'stock' => 'required|numeric',
        ]);
        $data['user_id'] = Auth::id();

        if ($request->filled('media_id')) {
            MediaLink::updateOrCreate(
                [
                    'model_type' => Product::class,
                    'model_id'   => $product->id,
                    'role'       => 'thumbnail',
                ],
                [
                    'media_id'   => $request->media_id,
                ]
            );
        }
        
        if (isset($data['price'])) {
            $data['price'] = str_replace(',', '', $data['price']);
        }

        Product::create($data);
        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    public function edit(Request $request, $id)
    {
        $page =(int) $request->get('page', 1);
        $perPage = (int) $request->get('perPage', 10);
        
        $product = Product::with(['category'])->findOrFail($id);
        $categories = Category::all();
        $brands = Brand::all();
        return view('products.edit', compact('product','page','perPage','categories', 'brands'));

    }

    public function getQuickEditForm(Product $product)
    {
        return view('products._quick-edit-form', compact('product'));
    } 
    
    
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        if ($request->ajax()) {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'price' => 'nullable|numeric',
                'stock' => 'nullable|numeric',
                'media_id' => 'nullable|integer|exists:media,id',
            ]);

            $product->name = $validated['name'];
            if(isset($validated['price'])) {
                $product->price = $validated['price'];
            }
            if(isset($validated['stock'])) {
                $product->stock = $validated['stock'];
            }

            if ($request->filled('media_id')) {
                // Update or create the media link
                MediaLink::updateOrCreate(
                    [
                        'model_type' => Product::class,
                        'model_id'   => $product->id,
                        'role'       => 'avatar',
                    ],
                    [
                        'media_id'   => $validated['media_id'],
                    ]
                );
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
                'media_id'    => 'nullable|integer|exists:media,id',
                'gallery'     => 'nullable|array',
                'gallery.*'   => 'integer|exists:media,id',
                'variants'    => 'nullable|array',
            ]);

            // ===== Cập nhật thông tin cơ bản =====
            $product->update([
                'name'        => $validated['name'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'],
                'description' => $validated['description'] ?? $product->description,
            ]);

            // ===== Cập nhật avatar =====
            if (!empty($validated['media_id'])) {
                MediaLink::where('model_id', $product->id)
                    ->where('model_type', 'product')
                    ->where('role', 'avatar')
                    ->delete();

                MediaLink::create([
                    'media_id'   => $validated['media_id'],
                    'model_id'   => $product->id,
                    'model_type' => 'product',
                    'role'       => 'avatar',
                ]);
            }

            // ===== Cập nhật gallery =====
            if ($request->filled('gallery')) {
                MediaLink::where('model_id', $product->id)
                    ->where('model_type', 'product')
                    ->where('role', 'gallery')
                    ->delete();

                foreach ($validated['gallery'] as $mediaId) {
                    MediaLink::create([
                        'media_id'   => $mediaId,
                        'model_id'   => $product->id,
                        'model_type' => 'product',
                        'role'       => 'gallery',
                    ]);
                }
            }
           
            // ===== Cập nhật biến thể (variants) =====
            $inputVariants = $validated['variants'] ?? [];
            $keepIds = [];

            foreach ($inputVariants as $variantId => $variantData) {
                if (is_numeric($variantId)) {
                    // Biến thể cũ
                    $variant = ProductVariant::updateOrCreate(
                        ['id' => $variantId, 'product_id' => $product->id],
                        [
                            'sku'             => $variantData['sku'] ?? Str::upper(Str::random(10)),
                            'size'            => $variantData['size'] ?? null,
                            'quality'         => $variantData['quality'] ?? null,
                            'production_date' => $variantData['production_date'] ?? null,
                            'stock'           => $variantData['stock'] ?? 0,
                        ]
                    );
                    $keepIds[] = $variant->id;
                } else {
                    // Biến thể mới
                    $variant = ProductVariant::create([
                        'product_id'       => $product->id,
                        'sku'              => $variantData['sku'] ?? Str::upper(Str::random(10)),
                        'size'             => $variantData['size'] ?? null,
                        'quality'          => $variantData['quality'] ?? null,
                        'production_date'  => $variantData['production_date'] ?? null,
                        'stock'            => $variantData['stock'] ?? 0,
                    ]);
                    $keepIds[] = $variant->id;
                }

                // ===== Gán media cho biến thể =====
                if (!empty($variantData['media_id'])) {
                    MediaLink::updateOrCreate(
                        [
                            'model_type' => $variant::class,
                            'model_id'   => $variant->id,
                            'role'       => 'variant',
                        ],
                        [
                            'media_id'   => $variantData['media_id'],
                        ]
                    );
                } else {
                    // Nếu không có media_id thì xóa link cũ (nếu có)
                    MediaLink::where([
                        'model_type' => $variant::class,
                        'model_id'   => $variant->id,
                        'role'       => 'variant',
                    ])->delete();
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

            return redirect()->route('products.index')->with('success', 'Cập nhật sản phẩm thành công!');
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


    public function destroy(Product $product)
    {
        // 1. Kiểm tra quyền xoá bằng Policy
        // => sẽ gọi ProductPolicy::delete($user, $product)
        $this->authorize('delete', $product);

        try {
            // 2. Nếu có ảnh chính => xoá file trong storage
            if ($product->image) {
                \Storage::disk('public')->delete($product->image);
            }

            // 3. Nếu có album ảnh => xoá luôn
            if ($product->album && is_array($product->album)) {
                foreach ($product->album as $img) {
                    \Storage::disk('public')->delete($img);
                }
            }

            // 4. Xoá record trong DB
            $product->delete();

            // 5. Trả về redirect hoặc JSON
            if (request()->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Sản phẩm đã được xoá thành công!',
                ]);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Sản phẩm đã được xoá thành công!');
        } catch (\Exception $e) {
            // Nếu có lỗi xảy ra
            if (request()->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không thể xoá sản phẩm. Lỗi: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('products.index')
                ->with('error', 'Không thể xoá sản phẩm!');
        }
    }



    
}
