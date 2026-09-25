<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::table('products', fn (Blueprint $table) => $table->decimal('cutting_percentage', 8, 3)->nullable());
        $values = [];
        foreach (DB::table('products')->whereNotNull('cutting_product_targets')->get(['cutting_product_targets','cutting_percentages']) as $source) {
            $percentages = json_decode($source->cutting_percentages ?? '{}', true) ?? [];
            foreach (json_decode($source->cutting_product_targets, true) ?? [] as $main => $sides) {
                foreach ($sides as $side) {
                    if (isset($percentages[$main][$side])) $values[$side][] = (float) $percentages[$main][$side];
                }
            }
        }
        foreach ($values as $id => $rates) {
            if (count(array_unique($rates)) === 1) DB::table('products')->where('id', $id)->where('product_type', 'cut')->update(['cutting_percentage' => $rates[0]]);
        }
    }
    public function down(): void { Schema::table('products', fn (Blueprint $table) => $table->dropColumn('cutting_percentage')); }
};
