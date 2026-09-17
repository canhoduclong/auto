<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->boolean('expand_packing_size_bounds')->default(false)
                ->after('status')
                ->comment('Thêm một biến thể trước và sau dải size Sale cho phép khi đóng hàng');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', fn (Blueprint $table) => $table->dropColumn('expand_packing_size_bounds'));
    }
};
