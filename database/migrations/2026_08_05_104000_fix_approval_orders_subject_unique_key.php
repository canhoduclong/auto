<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'approval_orders_order_id_approval_step_id_unique';
    private const ORDER_INDEX = 'approval_orders_order_id_index';
    private const ADJUSTMENT_UNIQUE = 'approval_orders_adjustment_step_unique';

    public function up(): void
    {
        if (!Schema::hasTable('approval_orders')) {
            return;
        }

        $indexes = $this->indexNames();

        // MySQL dùng khóa duy nhất cũ làm index cho foreign key order_id.
        // Phải tạo index riêng trước khi bỏ khóa cũ.
        if (in_array(self::OLD_UNIQUE, $indexes, true)) {
            if (!in_array(self::ORDER_INDEX, $indexes, true)) {
                Schema::table('approval_orders', function (Blueprint $table): void {
                    $table->index('order_id', self::ORDER_INDEX);
                });
            }

            Schema::table('approval_orders', function (Blueprint $table): void {
                $table->dropUnique(self::OLD_UNIQUE);
            });
        }

        // Mỗi yêu cầu điều chỉnh có bộ bước duyệt riêng. Với order_adjustment_id
        // bằng NULL (đơn gốc/giao dịch/tác vụ), MySQL vẫn cho phép nhiều dòng;
        // các luồng đó tiếp tục chống trùng bằng updateOrCreate hiện có.
        if (!in_array(self::ADJUSTMENT_UNIQUE, $this->indexNames(), true)) {
            Schema::table('approval_orders', function (Blueprint $table): void {
                $table->unique(
                    ['order_adjustment_id', 'approval_step_id'],
                    self::ADJUSTMENT_UNIQUE
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_orders')) {
            return;
        }

        $indexes = $this->indexNames();

        if (in_array(self::ADJUSTMENT_UNIQUE, $indexes, true)) {
            Schema::table('approval_orders', function (Blueprint $table): void {
                $table->dropUnique(self::ADJUSTMENT_UNIQUE);
            });
        }

        if (!in_array(self::OLD_UNIQUE, $this->indexNames(), true)) {
            Schema::table('approval_orders', function (Blueprint $table): void {
                $table->unique(['order_id', 'approval_step_id'], self::OLD_UNIQUE);
            });
        }

        if (in_array(self::ORDER_INDEX, $this->indexNames(), true)) {
            Schema::table('approval_orders', function (Blueprint $table): void {
                $table->dropIndex(self::ORDER_INDEX);
            });
        }
    }

    /** @return array<int, string> */
    private function indexNames(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $index): ?string => $index['name'] ?? null,
            Schema::getIndexes('approval_orders')
        )));
    }
};
