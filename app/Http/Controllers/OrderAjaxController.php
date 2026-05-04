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
        $keyword = $request->input('keyword', $request->input('q'));
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 50)); // giới hạn tối đa 50
        $query = \App\Models\ProductVariant::with(['product', 'mediaLink.media']);

        if ($keyword) {
            $query->where(function($sub) use ($keyword) {
                $sub->where('sku', 'like', "%$keyword%")
                     ->orWhere('size', 'like', "%$keyword%")
                     ->orWhere('quality', 'like', "%$keyword%")
                     ->orWhereHas('product', function($p) use ($keyword) {
                         $p->where('name', 'like', "%$keyword%") ;
                     });
            });
        }

        // Nếu không có từ khóa, chỉ lấy tối đa 50 bản ghi (hoặc phân trang nếu cần)
        if (!$keyword) {
            $variants = $query->orderByDesc('id')->paginate($perPage);
        } else {
            $variants = $query->orderByDesc('id')->paginate($perPage);
        }

        // Render partial Blade view for AJAX (reuse product_variants._variants_table if available, else return JSON)
        if ($request->ajax()) {
            $viewFile = $keyword
                ? 'product_variants._variants_table'
                : 'product_variants._variants_table_select';
            if (view()->exists($viewFile)) {
                $html = view($viewFile, ['variants' => $variants])->render();
                return response()->json(['success' => true, 'html' => $html]);
            }
        }
        // Fallback: return JSON data
        return response()->json([
            'success' => true,
            'variants' => $variants
        ]);
    }
}
