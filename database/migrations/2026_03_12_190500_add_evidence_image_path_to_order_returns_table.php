<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'evidence_image_path')) {
                $table->string('evidence_image_path')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'evidence_image_path')) {
                $table->dropColumn('evidence_image_path');
            }
        });
    }
};
