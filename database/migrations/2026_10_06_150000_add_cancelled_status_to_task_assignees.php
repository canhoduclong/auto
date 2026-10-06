<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_assignees') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM task_assignees WHERE Field = 'status'");
        // Keep all existing enum values, including values introduced by other releases.
        if ($column && str_starts_with($column->Type, 'enum(') && !str_contains($column->Type, "'cancelled'")) {
            $type = substr($column->Type, 0, -1).",'cancelled')";
            $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column->Default === null ? '' : ' DEFAULT '.DB::getPdo()->quote($column->Default);
            DB::statement("ALTER TABLE task_assignees MODIFY status {$type} {$nullable}{$default}");
        }
    }

    public function down(): void
    {
        // Keep the added value so rolling back does not corrupt recalled task history.
    }
};
