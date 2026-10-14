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
        $sort = (string)$request->query('sort','name_asc');
        $sorts = ['name_asc'=>['name','asc'], 'name_desc'=>['name','desc'], 'tax_asc'=>['tax_percent','asc'], 'tax_desc'=>['tax_percent','desc']];
        if (!isset($sorts[$sort])) $sort = 'name_asc';
        $perPage = (int)$request->query('per_page',20);
        if (!in_array($perPage,[10,20,50,100],true)) $perPage=20;
        $status = (string)$request->query('status','');
        if (!in_array($status,['','configured','unconfigured'],true)) $status='';
        $query = Product::query()->withCount('variants')->with(['taxRates'=>fn($q)=>$q->where('status','draft')->latest('id')])->orderByDesc('tax_priority')
            ->when($search !== '', fn($q) => $q->where('name','like','%'.$search.'%'))
            ->when($status === 'configured',fn($q)=>$q->whereNotNull('tax_percent'))
            ->when($status === 'unconfigured',fn($q)=>$q->whereNull('tax_percent'));
        if (str_starts_with($sort,'tax_')) $query->orderByRaw('tax_percent IS NULL ASC');
        $products = $query->orderBy(...$sorts[$sort])->orderBy('id')->paginate($perPage)->withQueryString();
        return view('products.tax-settings', compact('products','prefix','search','sort','perPage','status'));

    }
    public function update(Request $request, Product $product)
    {
        $data=$request->validate(['tax_percent'=>['nullable','numeric','min:0','max:100','decimal:0,2'],'effective_from'=>['required','date_format:Y-m-d','after_or_equal:today'],'note'=>['nullable','string','max:1000']]);
        $rate=$product->taxRates()->create($data+['created_by'=>$request->user()->id,'status'=>'draft']);
        return back()->with('success','Đã lưu mức thuế nháp. Bấm Áp dụng để xác nhận ngày hiệu lực.');
    }
    public function priority(Request $request, Product $product)
    {
        $data=$request->validate(['tax_priority'=>'required|boolean']);
        $product->update($data);
        return back()->with('success','Đã cập nhật ưu tiên sản phẩm.');
    }
    public function apply(Request $request, Product $product, \App\Models\ProductTaxRate $taxRate)
    {
        abort_unless((int)$taxRate->product_id===(int)$product->id,404);
        app(\App\Services\ProductTaxRateService::class)->approve($product,$taxRate,$request->user()->id);
        return back()->with('success','Đã xác nhận áp dụng thuế suất từ '.$taxRate->effective_from->format('d/m/Y').'.');
    }
    public function show(Request $request,Product $product)
    {
        $prefix=$request->routeIs('ceo.*')?'ceo':'accounting';
        $rates=$product->taxRates()->with(['creator','approver'])->orderByDesc('effective_from')->orderByDesc('id')->get();
        return view('products.tax-history',compact('product','rates','prefix'));
    }
}
