<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('skip_auto_cancel')->default(false)->after('trash_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['skip_auto_cancel']);
            $table->dropColumn('skip_auto_cancel');
        });
    }
};
