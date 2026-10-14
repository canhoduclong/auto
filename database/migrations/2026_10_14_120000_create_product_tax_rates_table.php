<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('products',fn(Blueprint $t)=>$t->boolean('tax_priority')->default(false));
  Schema::create('product_tax_rates',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->decimal('tax_percent',5,2)->nullable();$t->date('effective_from');$t->string('status')->default('draft');$t->text('note')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->unsignedBigInteger('approved_by')->nullable();$t->timestamp('approved_at')->nullable();$t->timestamps();$t->index(['product_id','status','effective_from']);});
  // Preserve the known configuration without inventing an earlier effective date.
  \Illuminate\Support\Facades\DB::table('products')->whereNotNull('tax_percent')->orderBy('id')->chunkById(200,function($products){foreach($products as $p)\Illuminate\Support\Facades\DB::table('product_tax_rates')->insert(['product_id'=>$p->id,'tax_percent'=>$p->tax_percent,'effective_from'=>now()->toDateString(),'status'=>'approved','note'=>'Mốc cấu hình ban đầu khi bật lịch sử thuế; không xác định hiệu lực trước mốc này.','approved_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);});
 }
 public function down(): void {Schema::dropIfExists('product_tax_rates');Schema::table('products',fn(Blueprint $t)=>$t->dropColumn('tax_priority'));}
};
