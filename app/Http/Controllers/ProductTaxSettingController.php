<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
class ProductTaxSettingController extends Controller
{
    public function index(Request $request)
    {
        $prefix = $request->routeIs('ceo.*') ? 'ceo' : 'accounting';
        $search = trim((string)$request->query('search',''));
        $products = Product::query()->withCount('variants')->when($search !== '', fn($q) => $q->where('name','like','%'.$search.'%'))->orderBy('name')->paginate(20)->withQueryString();
        return view('products.tax-settings', compact('products','prefix','search'));
    }
    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['tax_percent'=>['nullable','numeric','min:0','max:100','decimal:0,2']]);
        $product->update(['tax_percent'=>$data['tax_percent'] ?? null]);
        return back()->with('success','Đã cập nhật mức thuế cho '.$product->name.' và toàn bộ biến thể.');
    }
}
