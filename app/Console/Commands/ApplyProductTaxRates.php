<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Product;
class ApplyProductTaxRates extends Command {
 protected $signature='products:apply-tax-rates';
 protected $description='Cập nhật thuế suất VAT theo ngày hiệu lực đã phê duyệt';
 public function handle(): int {Product::whereHas('taxRates',fn($q)=>$q->where('status','approved')->whereDate('effective_from','<=',today()))->chunkById(200,function($products){foreach($products as $p)\Illuminate\Support\Facades\DB::transaction(function()use($p){$locked=Product::whereKey($p->id)->lockForUpdate()->firstOrFail();app(\App\Services\ProductTaxRateService::class)->refresh($locked);});});return self::SUCCESS;}
}
