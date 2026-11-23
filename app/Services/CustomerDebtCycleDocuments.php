<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerDebtCycleDocuments
{
    private function prefix(string $ledger): string
    {
        return 'debt_cycle_evidence_'.$ledger.'_';
    }

    public function forLedger(string $ledger): array
    {
        $prefix = $this->prefix($ledger);
        $documents = [];
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->latest('id')->get(['key', 'value']) as $setting) {
            if (str_starts_with($setting->key, $prefix)) {
                $document = json_decode($setting->value, true, flags: JSON_THROW_ON_ERROR);
                $documents[$document['date']][] = $document;
            }
        }

        return $documents;
    }

    public function find(string $ledger, string $date, string $id): ?array
    {
        $value = Setting::query()->where('key', $this->prefix($ledger).$date.'_'.$id)->value('value');

        return $value === null ? null : json_decode($value, true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param list<UploadedFile> $files */
    public function attach(string $ledger, string $date, array $files, int $actor, ?string $statementId = null): void
    {
        $disk = Storage::disk('local');
        $paths = [];
        try {
            DB::transaction(function () use ($disk, $ledger, $date, $files, $actor, $statementId, &$paths) {
                foreach ($files as $file) {
                    $id = (string) Str::uuid();
                    $path = $disk->putFileAs("customer-debt-cycles/{$ledger}/{$date}", $file, $id.'.'.$file->extension(), 'private');
                    if (! $path) {
                        throw new \RuntimeException('Không thể lưu chứng từ công nợ.');
                    }
                    $paths[] = $path;
                    $name = preg_replace('/[\x00-\x1f\x7f]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName())));
                    Setting::set($this->prefix($ledger).$date.'_'.$id, json_encode([
                        'id' => $id, 'date' => $date, 'path' => $path, 'statement_id' => $statementId,
                        'name' => Str::limit($name ?: 'Chứng từ', 200, ''),
                        'uploaded_by' => $actor, 'uploaded_at' => now()->toIso8601String(),
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                }
            });
        } catch (\Throwable $exception) {
            $disk->delete($paths);

            throw $exception;
        }
    }
}
