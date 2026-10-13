<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{SystemBackup,Setting};
use App\Services\Backup\{SystemBackupService,BackupGoogleStorage};
use Illuminate\Http\Request;

class SystemBackupController extends Controller
{
    public function index()
    {
        return $this->page('backup');
    }

    public function restore()
    {
        return $this->page('restore');
    }

    public function store(Request $request, SystemBackupService $service)
    {
        try { $service->enqueue('manual', $request->user()->id); }
        catch (\RuntimeException $e) { return back()->with('error',$e->getMessage()); }
        return back()->with('success','Đã đưa yêu cầu sao lưu vào hàng đợi. Lịch hệ thống sẽ xử lý trong lần chạy tiếp theo.');
    }

    public function retry(SystemBackup $backup, SystemBackupService $service)
    {
        try { $service->retry($backup); }
        catch (\RuntimeException $e) { return back()->with('error',$e->getMessage()); }
        return back()->with('success','Đã đưa bản sao vào hàng đợi thử lại.');
    }

    public function schedule(Request $request)
    {
        $request->validate(['enabled'=>'required|boolean']);
        try {
            app(\App\Services\EnvironmentFileEditor::class)->updateValues(['BACKUP_MONTHLY_ENABLED'=>$request->boolean('enabled')?'true':'false']);
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            Setting::set('backup_monthly_enabled',$request->boolean('enabled')?'1':'0');
        } catch (\RuntimeException $e) { return back()->with('error',$e->getMessage()); }
        return back()->with('success','Đã cập nhật lịch sao lưu hằng tháng.');
    }

    public function check(BackupGoogleStorage $google)
    {
        try { $google->check(); return back()->with('success','Đã kiểm tra quyền truy cập Drive và quyền ghi tab Backup_Index trên Google Sheets.'); }
        catch (\Throwable $e) {
            report($e);
            return back()->with('error',$e instanceof \Google\Service\Exception ? BackupGoogleStorage::errorMessage($e) : $e->getMessage());
        }
    }

    public function key()
    {
        $key=app(\App\Services\Backup\BackupArchive::class)->key();
        return response(base64_encode($key).PHP_EOL)->header('Content-Type','text/plain')->header('Content-Disposition','attachment; filename=backup-encryption.key')->header('Cache-Control','no-store, private');
    }

    public function download(SystemBackup $backup)
    {
        abort_unless($backup->local_path && is_file(storage_path('app/'.$backup->local_path)),404);
        return response()->download(storage_path('app/'.$backup->local_path),$backup->code.'.hlbackup');
    }

    private function page(string $mode)
    {
        $path = (string) (config('system_backup.credentials') ?: config('services.google_sheets.credentials'));
        if ($path && !str_starts_with($path, '/')) $path = base_path($path);
        $credentials = $path && is_readable($path) ? json_decode(file_get_contents($path), true) : [];
        $serviceAccount = ($credentials['type'] ?? null) === 'service_account'
            ? ($credentials['client_email'] ?? null) : null;

        return view('admin.backups.index', ['mode'=>$mode,'serviceAccount'=>$serviceAccount, 'googleProjectId'=>$credentials['project_id'] ?? null, 'backups'=>SystemBackup::latest()->paginate(20)->withQueryString(), 'monthlyEnabled'=>Setting::enabled('backup_monthly_enabled',(bool)config('system_backup.monthly_enabled'))]);
    }
}
