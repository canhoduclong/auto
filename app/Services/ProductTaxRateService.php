<?php
namespace App\Services;
use App\Models\{Product,ProductTaxRate};
use Illuminate\Support\Facades\DB;
class ProductTaxRateService {
 public function refresh(Product $product): void {
  $rate=ProductTaxRate::where('product_id',$product->id)->where('status','approved')->whereDate('effective_from','<=',today())->orderByDesc('effective_from')->orderByDesc('id')->first();
  if($rate)$product->update(['tax_percent'=>$rate->tax_percent]);
 }
 public function approve(Product $product,ProductTaxRate $rate,int $userId): void {
  DB::transaction(function()use($product,$rate,$userId){$p=Product::whereKey($product->id)->lockForUpdate()->firstOrFail();$r=ProductTaxRate::whereKey($rate->id)->where('product_id',$p->id)->lockForUpdate()->firstOrFail();
   abort_unless($r->status==='draft',422,'Mức thuế này đã được áp dụng.');
   if(ProductTaxRate::where('product_id',$p->id)->where('status','approved')->whereDate('effective_from',$r->effective_from)->exists())throw \Illuminate\Validation\ValidationException::withMessages(['tax_percent'=>'Đã có mức thuế áp dụng tại ngày này. Chọn ngày hiệu lực khác để giữ lịch sử.']);
   $r->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]);$this->refresh($p);
  });
 }
}
