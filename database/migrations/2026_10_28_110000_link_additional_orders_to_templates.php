<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedBigInteger('additional_text_order_draft_id')->nullable();
            $t->date('additional_template_date')->nullable();
            $t->index(['additional_text_order_draft_id', 'additional_template_date'], 'orders_additional_template_date_index');
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropIndex('orders_additional_template_date_index');
            $t->dropColumn(['additional_text_order_draft_id', 'additional_template_date']);
        });
    }
};
