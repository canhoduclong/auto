<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('text_order_drafts', fn (Blueprint $table) => $table->decimal('order_discount', 15, 2)->default(0));
    }
    public function down(): void
    {
        Schema::table('text_order_drafts', fn (Blueprint $table) => $table->dropColumn('order_discount'));
    }
};
