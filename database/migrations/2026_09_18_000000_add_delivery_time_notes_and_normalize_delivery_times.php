<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('delivery_time_note')->nullable()->after('delivery_time');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->text('delivery_time_note')->nullable()->after('delivery_time');
        });

        $this->migrateExistingValues('customers');
        $this->migrateExistingValues('orders');
    }

    private function migrateExistingValues(string $table): void
    {
        DB::table($table)->select('id', 'delivery_time')->orderBy('id')->chunkById(500, function ($rows) use ($table): void {
            foreach ($rows as $row) {
                $original = trim((string) $row->delivery_time);
                if ($original === '') {
                    continue;
                }

                DB::table($table)->where('id', $row->id)->update([
                    'delivery_time_note' => $original,
                    'delivery_time' => $this->extractTime($original),
                ]);
            }
        });
    }

    private function extractTime(string $value): ?string
    {
        if (preg_match('/(?<!\d)([01]?\d|2[0-3])\s*(?:[:hH\.])\s*([0-5]\d)(?!\d)/u', $value, $match)) {
            return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
        }
        if (preg_match('/(?<!\d)([01]?\d|2[0-3])\s*(?:h|giờ)(?!\s*\d)/iu', $value, $match)) {
            return sprintf('%02d:00', (int) $match[1]);
        }

        return null;
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('delivery_time_note'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('delivery_time_note'));
    }
};
