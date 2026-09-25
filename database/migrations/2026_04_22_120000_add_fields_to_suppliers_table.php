<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('name', 200)->after('id');
            $table->string('phone', 30)->nullable()->after('name');
            $table->string('address', 500)->nullable()->after('phone');
            $table->text('notes')->nullable()->after('address');
            $table->boolean('is_active')->default(true)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'address', 'notes', 'is_active']);
        });
    }
};
