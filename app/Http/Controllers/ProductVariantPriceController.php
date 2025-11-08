<?php
namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\ProductPriceRule;
use App\Models\ProductPriceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductVariantPriceController extends Controller
{
    // Hiển thị lịch sử giá (HTML table) cho popup
    public function priceHistory($id)
    {
        $variant = ProductVariant::with(['priceRules' => function($q) {
            $q->orderByDesc('start_date');
        }])->findOrFail($id);
        $rules = $variant->priceRules;
        return view('variants.price-history-table', compact('rules', 'variant'))->render();
    }

    // Xử lý cập nhật giá mới qua AJAX
    public function updatePrice(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $request->validate([
            'new_price' => 'required|numeric|min:0',
            'reason'    => 'nullable|string|max:255',
        ]);
        $newPrice = $request->input('new_price');
        $reason = $request->input('reason', 'Điều chỉnh giá');
        $currentRule = $variant->priceRules()->whereNull('end_date')->latest('start_date')->first();
        if (!$currentRule || $currentRule->price != $newPrice) {
            $rule = $variant->priceRules()->create([
                'reason'     => $reason,
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
        return response()->json(['success' => true]);
    }
    public function edit(ProductVariant $variant)
    {
        return view('variants.edit-price', compact('variant'));
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'price'  => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldPrice = $variant->final_price;

        // đóng rule cũ
        $variant->priceRules()
            ->whereNull('end_date')
            ->update(['end_date' => now()]);

        // tạo rule mới
        $rule = $variant->priceRules()->create([
            'price'      => $request->price,
            'reason'     => $request->reason,
            'start_date' => now(),
        ]);

        // log thay đổi
        ProductPriceLog::create([
            'product_variant_id' => $variant->id,
            'old_price'          => $oldPrice,
            'new_price'          => $request->price,
            'user_id'            => Auth::id(),
            'note'               => $request->reason,
        ]);

        // Nếu request có ?from=product-variants thì redirect về danh sách biến thể, ngược lại về trang sản phẩm
        if ($request->query('from') === 'product-variants') {
            return redirect()->route('product-variants.index')->with('success', 'Cập nhật giá thành công');
        }
        return redirect()->route('products.edit', $variant->product_id)
            ->with('success', 'Cập nhật giá thành công');
    }
}
