<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_reminders', 'image_path')) {
                $table->string('image_path')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('customer_reminders', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
