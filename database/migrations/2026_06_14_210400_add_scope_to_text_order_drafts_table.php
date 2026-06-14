<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->string('draft_scope', 30)->default('admin_import')->after('created_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropIndex(['draft_scope']);
            $table->dropColumn('draft_scope');
        });
    }
};
