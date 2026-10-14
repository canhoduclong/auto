<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('products',function(Blueprint $t){$t->decimal('household_tax_percent',5,2)->nullable();$t->decimal('company_tax_percent',5,2)->nullable();});
  Schema::table('product_tax_rates',function(Blueprint $t){$t->string('entity_type',30)->default('other');$t->index(['product_id','entity_type','status','effective_from'],'product_tax_entity_effective');});
 }
 public function down(): void {Schema::table('product_tax_rates',function(Blueprint $t){$t->dropIndex('product_tax_entity_effective');$t->dropColumn('entity_type');});Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['household_tax_percent','company_tax_percent']));}
};
