<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductVariantSorter;

class OrderAjaxController extends Controller
{
    public function total(Request $request)
    {
        $order = Order::find($request->order_id);
        if (!$order) return response()->json(['success'=>false]);
        return response()->json([
            'success' => true,
            'total' => $order->total
        ]);
    }

    // AJAX: Trả về danh sách biến thể cho popup thêm sản phẩm vào đơn
    public function variantsAjax(Request $request)
    {
        $keyword = trim((string) $request->input('search', $request->input('keyword', $request->input('q', ''))));
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 50)); // giới hạn tối đa 50
        $userId = auth()->id();

        if ($request->input('view') === 'products') {
            return $this->productsWithVariants($request, $keyword, $perPage);
        }

        $query = ProductVariant::query()
            ->withAvailableStock()
            ->with(['product.avatar.media', 'latestPriceRule', 'mediaLink.media'])
            ->where('product_variants.status', true)
            ->whereHas('product', function ($productQuery): void {
                $productQuery->where('products.status', true);
            });

        if ($keyword) {
            $query->where(function($sub) use ($keyword) {
                $sub->where('product_variants.sku', 'like', "%$keyword%")
                     ->orWhere('product_variants.name', 'like', "%$keyword%")
                     ->orWhere('product_variants.size', 'like', "%$keyword%")
                     ->orWhere('product_variants.quality', 'like', "%$keyword%")
                     ->orWhereHas('product', function($p) use ($keyword) {
                         $p->where('name', 'like', "%$keyword%") ;
                     });
            });
        }

        $excludeIds = $request->input('exclude_ids', []);
        if (is_string($excludeIds)) {
            $excludeIds = array_filter(explode(',', $excludeIds));
        }
        if (is_array($excludeIds) && !empty($excludeIds)) {
            $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));
            if (!empty($excludeIds)) {
                $query->whereNotIn('product_variants.id', $excludeIds);
            }
        }

        ProductVariantSorter::joinProductSort($query, $userId ? (int) $userId : null);
        ProductVariantSorter::applyUserPreferencePrefix($query, $userId ? (int) $userId : null);
        ProductVariantSorter::applyAdminFallback($query)
            ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
            ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
            ->orderBy('product_variants.id');

        $variants = $query->paginate($perPage)->appends($request->query());

        if ($request->ajax()) {
            $html = view('orders._variant_search_results', ['variants' => $variants])->render();
            return response()->json(['success' => true, 'html' => $html]);
        }

        return response()->json([
            'success' => true,
            'variants' => $variants
        ]);
    }

    private function productsWithVariants(Request $request, string $keyword, int $perPage)
    {
        $excludeIds = $request->input('exclude_ids', []);
        if (is_string($excludeIds)) {
            $excludeIds = array_filter(explode(',', $excludeIds));
        }
        $excludeIds = is_array($excludeIds)
            ? array_values(array_filter(array_map('intval', $excludeIds)))
            : [];

        $activeVariants = static function ($query) use ($excludeIds): void {
            $query->withAvailableStock()
                ->withSum('inventories as on_hand_stock', 'quantity')
                ->withSum('inventories as reserved_stock', 'reserved_quantity')
                ->with(['latestPriceRule', 'mediaLink.media'])
                ->where('product_variants.status', true)
                ->when($excludeIds !== [], fn ($variantQuery) => $variantQuery->whereNotIn('product_variants.id', $excludeIds))
                ->orderByRaw('COALESCE(product_variants.sort_order, 999999)')
                ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.size, ''), NULLIF(product_variants.name, ''), product_variants.sku, ''))")
                ->orderBy('product_variants.id');
        };

        $products = Product::query()
            ->with(['avatar.media', 'variants' => $activeVariants])
            ->where('products.status', true)
            ->whereHas('variants', function ($query) use ($excludeIds): void {
                $query->where('product_variants.status', true)
                    ->when($excludeIds !== [], fn ($variantQuery) => $variantQuery->whereNotIn('product_variants.id', $excludeIds));
            })
            ->when($keyword !== '', function ($query) use ($keyword, $excludeIds): void {
                $query->where(function ($search) use ($keyword, $excludeIds): void {
                    $search->where('products.name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('variants', function ($variants) use ($keyword, $excludeIds): void {
                            $variants->where('product_variants.status', true)
                                ->when($excludeIds !== [], fn ($variantQuery) => $variantQuery->whereNotIn('product_variants.id', $excludeIds))
                                ->where(function ($variantSearch) use ($keyword): void {
                                    $variantSearch->where('product_variants.sku', 'like', '%' . $keyword . '%')
                                        ->orWhere('product_variants.name', 'like', '%' . $keyword . '%')
                                        ->orWhere('product_variants.size', 'like', '%' . $keyword . '%')
                                        ->orWhere('product_variants.quality', 'like', '%' . $keyword . '%');
                                });
                        });
                });
            })
            ->orderByRaw('COALESCE(products.sort_order, 999999)')
            ->orderByRaw("LOWER(COALESCE(products.name, ''))")
            ->orderBy('products.id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'html' => view('orders._product_variant_search_results', ['products' => $products])->render(),
        ]);
    }
}
