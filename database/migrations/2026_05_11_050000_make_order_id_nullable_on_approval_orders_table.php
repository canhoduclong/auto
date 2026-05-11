<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // approval_orders is reused for order/adjustment/transaction approvals.
        // Transaction approvals may not have a linked order, so order_id must be nullable.
        DB::statement('ALTER TABLE approval_orders DROP FOREIGN KEY approval_orders_order_id_foreign');
        DB::statement('ALTER TABLE approval_orders MODIFY order_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE approval_orders ADD CONSTRAINT approval_orders_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Remove rows that depend on nullable order_id before restoring NOT NULL.
        DB::statement('DELETE FROM approval_orders WHERE order_id IS NULL');
        DB::statement('ALTER TABLE approval_orders DROP FOREIGN KEY approval_orders_order_id_foreign');
        DB::statement('ALTER TABLE approval_orders MODIFY order_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE approval_orders ADD CONSTRAINT approval_orders_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE');
    }
};
