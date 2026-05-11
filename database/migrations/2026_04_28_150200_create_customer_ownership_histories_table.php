<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_ownership_histories')) {
            Schema::create('customer_ownership_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('cycle_no')->default(1);
                $table->foreignId('sale_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('priority_level')->default(1);
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->string('transfer_reason', 64)->default('created');
                $table->unsignedInteger('final_score')->default(0);
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'cycle_no'], 'cust_owner_hist_cycle_idx');
                $table->index(['customer_id', 'sale_id', 'started_at'], 'cust_owner_hist_sale_start_idx');
            });

            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM customer_ownership_histories'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        Schema::table('customer_ownership_histories', function (Blueprint $table) use ($indexes) {
            if (!in_array('cust_owner_hist_sale_start_idx', $indexes, true)
                && !in_array('customer_ownership_histories_customer_id_sale_id_started_at_index', $indexes, true)) {
                $table->index(['customer_id', 'sale_id', 'started_at'], 'cust_owner_hist_sale_start_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ownership_histories');
    }
};
