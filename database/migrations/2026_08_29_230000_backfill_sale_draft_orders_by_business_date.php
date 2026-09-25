<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE orders
            INNER JOIN text_order_drafts ON text_order_drafts.order_id = orders.id
            SET orders.created_at = TIMESTAMP(text_order_drafts.delivery_date, TIME(orders.created_at))
            WHERE text_order_drafts.draft_scope = 'sale_private'
              AND text_order_drafts.delivery_date IS NOT NULL
              AND DATE(orders.created_at) <> text_order_drafts.delivery_date
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO order_schedules (
                customer_id, text_order_draft_id, schedule_date, status,
                price_status, stock_status, created_by, generated_order_id,
                review_meta, is_active, created_at, updated_at
            )
            SELECT
                text_order_drafts.customer_id,
                text_order_drafts.id,
                DATE(orders.created_at),
                'generated', 'ok', 'ok',
                COALESCE(text_order_drafts.sale_id, text_order_drafts.created_by),
                orders.id,
                JSON_OBJECT('source', 'sale_draft_backfill'),
                1, NOW(), NOW()
            FROM text_order_drafts
            INNER JOIN orders ON orders.id = text_order_drafts.order_id
            LEFT JOIN order_schedules
                ON order_schedules.text_order_draft_id = text_order_drafts.id
               AND order_schedules.schedule_date = DATE(orders.created_at)
            WHERE text_order_drafts.draft_scope = 'sale_private'
              AND text_order_drafts.customer_id IS NOT NULL
              AND COALESCE(text_order_drafts.sale_id, text_order_drafts.created_by) IS NOT NULL
              AND order_schedules.id IS NULL
        SQL);
    }

    public function down(): void
    {
        // Historical business dates cannot be reconstructed safely.
    }
};
