<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CustomerDebtPriorityService
{
    private function prefix(): string
    {
        return 'customer_debt_priority_'.substr(hash('sha256', (string) config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'))), 0, 16).'_';
    }

    public function records(): array
    {
        $prefix = $this->prefix();
        $records = [];
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->get(['key', 'value']) as $setting) {
            if (str_starts_with($setting->key, $prefix)) {
                $records[substr($setting->key, strlen($prefix))] = json_decode($setting->value, true) ?? [];
            }
        }

        return $records;
    }

    public function effective(array $row, array $records): array
    {
        $identity = ! empty($row['customer_id']) ? 'customer_'.$row['customer_id'] : 'source_'.$row['key'];
        $relevant = [$identity => $records[$identity] ?? null];
        foreach ($row['source_keys'] ?? [$row['key'] ?? ''] as $key) {
            $relevant['source_'.$key] = $records['source_'.$key] ?? null;
        }
        ksort($relevant);
        // Keep source priorities after mapping; an explicit customer choice (including zero) wins.
        $stars = isset($records[$identity]) ? (int) $records[$identity]['stars'] : max(array_merge([0], array_map(fn ($record) => (int) ($record['stars'] ?? 0), $relevant)));

        return ['stars' => max(0, min(5, $stars)), 'version' => hash('sha256', json_encode($relevant))];
    }

    /** Caller holds customer-debt-load while resolving the current customer mapping. */
    public function save(array $row, int $stars, string $version, User $actor): void
    {
        $before = $this->effective($row, $this->records());
        if (! hash_equals($before['version'], $version)) {
            throw ValidationException::withMessages(['priority_version' => 'Mức ưu tiên đã thay đổi. Vui lòng tải lại trang trước khi lưu.']);
        }
        $identity = ! empty($row['customer_id']) ? 'customer_'.$row['customer_id'] : 'source_'.$row['key'];
        Setting::set($this->prefix().$identity, json_encode(['stars' => $stars, 'updated_by' => $actor->id, 'updated_at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
