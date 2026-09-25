<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'customers_name_normalized_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'name_normalized')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->string('name_normalized')->collation('utf8mb4_bin')->nullable()->after('name');
            });
        }

        DB::transaction(function (): void {
            $customers = DB::table('customers')
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get();

            $groups = $customers->groupBy(
                fn (object $customer): string => Customer::normalizeName($customer->name)
            );
            $referenceTables = $this->customerReferenceTables();

            foreach ($groups as $normalizedName => $duplicates) {
                if ($normalizedName === '') {
                    throw new RuntimeException('Không thể chuẩn hóa khách hàng có tên rỗng.');
                }

                /** @var object $canonical */
                $canonical = $duplicates->first();
                $duplicateRows = $duplicates->slice(1);

                foreach ($duplicateRows as $duplicate) {
                    $this->mergeCustomerPriorities((int) $canonical->id, (int) $duplicate->id);

                    foreach ($referenceTables as $table) {
                        if ($table === 'customer_priorities') {
                            continue;
                        }

                        DB::table($table)
                            ->where('customer_id', $duplicate->id)
                            ->update(['customer_id' => $canonical->id]);
                    }

                    $this->fillMissingCanonicalValues($canonical, $duplicate);
                    DB::table('customers')->where('id', $duplicate->id)->delete();
                }

                DB::table('customers')->where('id', $canonical->id)->update([
                    'name_normalized' => $normalizedName,
                ]);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE customers MODIFY name_normalized VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL'
            );
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('name_normalized', self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'name_normalized')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX_NAME);
            $table->dropColumn('name_normalized');
        });
    }

    private function customerReferenceTables(): array
    {
        if (DB::getDriverName() === 'mysql') {
            return collect(DB::select(
                "SELECT TABLE_NAME AS table_name
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND COLUMN_NAME = 'customer_id'
                   AND TABLE_NAME <> 'customers'"
            ))->pluck('table_name')->unique()->values()->all();
        }

        return collect(Schema::getTables())
            ->map(fn (array $table): string => $table['name'])
            ->filter(fn (string $table): bool => $table !== 'customers' && Schema::hasColumn($table, 'customer_id'))
            ->values()
            ->all();
    }

    private function mergeCustomerPriorities(int $canonicalId, int $duplicateId): void
    {
        if (! Schema::hasTable('customer_priorities')) {
            return;
        }

        DB::table('customer_priorities')
            ->where('customer_id', $duplicateId)
            ->orderBy('id')
            ->get()
            ->each(function (object $priority) use ($canonicalId): void {
                $existing = DB::table('customer_priorities')
                    ->where('customer_id', $canonicalId)
                    ->where('sale_id', $priority->sale_id)
                    ->where('cycle_no', $priority->cycle_no)
                    ->first();

                if (! $existing) {
                    DB::table('customer_priorities')->where('id', $priority->id)->update([
                        'customer_id' => $canonicalId,
                    ]);

                    return;
                }

                DB::table('customer_priorities')->where('id', $existing->id)->update([
                    'priority_level' => min((int) $existing->priority_level, (int) $priority->priority_level),
                    'care_score' => max((int) $existing->care_score, (int) $priority->care_score),
                    'is_active' => (bool) $existing->is_active || (bool) $priority->is_active,
                    'takeover_eligible' => (bool) $existing->takeover_eligible || (bool) $priority->takeover_eligible,
                    'last_activity_at' => collect([$existing->last_activity_at, $priority->last_activity_at])->filter()->max(),
                    'updated_at' => now(),
                ]);
                DB::table('customer_priorities')->where('id', $priority->id)->delete();
            });
    }

    private function fillMissingCanonicalValues(object $canonical, object $duplicate): void
    {
        $ignored = ['id', 'name', 'name_normalized', 'created_at', 'updated_at', 'deleted_at'];
        $updates = [];

        foreach ((array) $duplicate as $column => $value) {
            if (in_array($column, $ignored, true) || $value === null || $value === '') {
                continue;
            }

            if (($canonical->{$column} ?? null) === null || ($canonical->{$column} ?? null) === '') {
                $updates[$column] = $value;
                $canonical->{$column} = $value;
            }
        }

        if ($updates !== []) {
            DB::table('customers')->where('id', $canonical->id)->update($updates);
        }
    }
};
