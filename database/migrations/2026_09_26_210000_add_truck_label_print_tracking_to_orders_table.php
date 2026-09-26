<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('truck_label_print_count')->default(0)->after('truck_receive_time');
            $table->timestamp('truck_label_printed_at')->nullable()->after('truck_label_print_count');
            $table->foreignId('truck_label_printed_by')->nullable()->after('truck_label_printed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('truck_label_printed_by');
            $table->dropColumn(['truck_label_print_count', 'truck_label_printed_at']);
        });
    }
};
