<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Correct orders already confirmed from the Admin text-import screen.
        // Keep their original time of day and only align the business date.
        DB::statement(<<<'SQL'
            UPDATE orders
            INNER JOIN text_order_drafts ON text_order_drafts.order_id = orders.id
            SET orders.created_at = TIMESTAMP(text_order_drafts.delivery_date, TIME(orders.created_at))
            WHERE text_order_drafts.draft_scope = 'admin_import'
              AND text_order_drafts.delivery_date IS NOT NULL
              AND DATE(orders.created_at) <> text_order_drafts.delivery_date
        SQL);
    }

    public function down(): void
    {
        // The previous date cannot be reconstructed safely after alignment.
    }
};
