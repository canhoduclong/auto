<?php

namespace App\Support;

use App\Models\ProcessEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessFiles
{
    private array $paths = [];

    public static function transaction(callable $callback): mixed
    {
        $files = new self;
        try {
            return DB::transaction(fn () => $callback($files));
        } catch (\Throwable $e) {
            foreach ($files->paths as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
    }

    public function attach(ProcessEvent $event, User $user, array $files): void
    {
        foreach ($files as $file) {
            $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $stem = preg_replace('/[\x00-\x1f\x7f<>:"\/\\\\|?*]/u', '_', pathinfo($name, PATHINFO_FILENAME)) ?: 'Tai lieu';
            $ext = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($name, PATHINFO_EXTENSION));
            $path = $file->storeAs('process-documents', mb_strcut($stem, 0, 170, 'UTF-8').'_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(5)).($ext ? '.'.$ext : ''), 'local');
            abort_unless($path, 500, 'Không thể lưu tài liệu.');
            $this->paths[] = $path;
            $event->documents()->create(['uploaded_by' => $user->id, 'path' => $path, 'original_name' => $name]);
        }
    }
}
