<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtRecoveryService
{
    public const LABELS = ['normal' => 'Theo dõi thu hồi', 'difficult' => 'Khó thu hồi', 'unrecoverable' => 'Không có khả năng thu hồi'];

    private function prefix(): string
    {
        return 'customer_debt_recovery_'.substr(hash('sha256', (string) config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'))), 0, 16).'_';
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

    public function identity(array $group): string
    {
        return ! empty($group['customer_id']) ? 'customer_'.$group['customer_id'] : 'source_'.$group['key'];
    }

    public function effective(array $group, array $records): array
    {
        $identity = $this->identity($group);
        $sources = $group['source_keys'] ?? [$group['key'] ?? ''];
        $relevant = [$identity => $records[$identity] ?? null];
        foreach ($sources as $key) {
            $relevant['source_'.$key] = $records['source_'.$key] ?? null;
        }
        ksort($relevant);
        $record = $records[$identity] ?? null;
        if (! $record) {
            foreach ($sources as $key) {
                $candidate = $records['source_'.$key] ?? [];
                if (array_search($candidate['status'] ?? 'normal', array_keys(self::LABELS)) > array_search($record['status'] ?? 'normal', array_keys(self::LABELS))) {
                    $record = $candidate;
                }
            }
        }
        $status = $record['status'] ?? 'normal';

        return ['status' => $status, 'label' => self::LABELS[$status] ?? self::LABELS['normal'], 'note' => $record['note'] ?? '', 'version' => hash('sha256', json_encode($relevant))];
    }

    /** Caller holds the shared Sheet/mapping lock while resolving the customer. */
    public function save(array $group, array $data, User $actor): void
    {
        DB::transaction(function () use ($group, $data, $actor) {
            $before = $this->effective($group, $this->records());
            if (! hash_equals($before['version'], $data['recovery_version'])) {
                throw ValidationException::withMessages(['recovery_version' => 'Trạng thái đã thay đổi. Vui lòng tải lại trang trước khi lưu.']);
            }
            $record = ['status' => $data['recovery_status'], 'note' => $data['recovery_note'] ?? '', 'updated_by' => $actor->id, 'actor_name' => $actor->name, 'updated_at' => now()->toIso8601String(), 'revision' => (string) Str::uuid()];
            Setting::set($this->prefix().$this->identity($group), json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            Setting::set('customer_debt_recovery_audit_'.Str::uuid(), json_encode(['identity' => $this->identity($group), 'before' => $before, 'after' => $record], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        });
    }
}
