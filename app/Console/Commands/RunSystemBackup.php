<?php
namespace App\Console\Commands;
use App\Models\Setting;
use App\Services\Backup\{SystemBackupService,BackupGoogleStorage,BackupArchive};
use Illuminate\Console\Command;
class RunSystemBackup extends Command {
    protected $signature='system-backup:run {--enqueue : Tạo bản sao thủ công} {--monthly : Tạo bản sao tự động tháng hiện tại nếu bật lịch}';
    protected $description='Xử lý hàng đợi sao lưu toàn bộ dữ liệu và tài liệu lên Google';
    public function handle(SystemBackupService $service,BackupGoogleStorage $google,BackupArchive $archive): int {
        try {
            if ($this->option('monthly') && Setting::enabled('backup_monthly_enabled',(bool)config('system_backup.monthly_enabled'))) $service->enqueue('monthly');
            if ($this->option('enqueue')) $service->enqueue();
            $backup=$service->runPending($google,$archive);
            if ($backup && $backup->status==='failed') { $this->error($backup->error); return self::FAILURE; }
            if ($backup) $this->info($backup->code.': '.$backup->stage);
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
