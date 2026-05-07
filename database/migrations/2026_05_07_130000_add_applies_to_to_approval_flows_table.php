<?php

use App\Models\ApprovalWorkflow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->json('applies_to')->nullable()->after('is_active');
        });

        DB::table('approval_flows')
            ->whereNull('applies_to')
            ->update([
                'applies_to' => json_encode([ApprovalWorkflow::ACTIVITY_ORDER_CREATE], JSON_UNESCAPED_UNICODE),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};
