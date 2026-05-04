<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;

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
        $q = $request->input('q');
        $perPage = $request->input('per_page', 20);
        $query = \App\Models\ProductVariant::with(['product', 'mediaLink.media']);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('sku', 'like', "%$q%")
                     ->orWhere('size', 'like', "%$q%")
                     ->orWhere('quality', 'like', "%$q%")
                     ->orWhereHas('product', function($p) use ($q) {
                         $p->where('name', 'like', "%$q%") ;
                     });
            });
        }
        $variants = $query->orderByDesc('id')->paginate($perPage);

        // Render partial Blade view for AJAX (reuse product_variants._variants_table if available, else return JSON)
        if ($request->ajax() && view()->exists('product_variants._variants_table')) {
            $html = view('product_variants._variants_table', ['variants' => $variants])->render();
            return response()->json(['success' => true, 'html' => $html]);
        }
        // Fallback: return JSON data
        return response()->json([
            'success' => true,
            'variants' => $variants
        ]);
    }
}
