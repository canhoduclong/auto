<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;
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
}
