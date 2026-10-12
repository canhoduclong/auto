<?php
// Run: php tests/Manual/SystemBackup.php. No external Google writes; temporary backup rows/files are removed.
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\SystemBackup;
use App\Services\Backup\{SystemBackupService,BackupArchive,BackupGoogleStorage};
use Illuminate\Support\Facades\File;
function checkBackup(bool $value,string $message): void { if (!$value) throw new RuntimeException($message); echo "PASS: $message\n"; }
$google=new class extends BackupGoogleStorage {
    public bool $fail=true; public int $uploads=0;
    public function check(): array { return []; }
    public function upload(SystemBackup $backup,string $path): string { $this->uploads++; return 'TEST-REMOTE-FILE'; }
    public function record(SystemBackup $backup,string $status): void { if ($this->fail) throw new RuntimeException('Test Sheets unavailable'); }
};
$service=$app->make(SystemBackupService::class); $archive=new BackupArchive;
$backup=null;
if(SystemBackup::whereIn('status',['pending','running'])->exists()) throw new RuntimeException('Wait until active backups finish before running this test.');
try {
    $backup=$service->enqueue('manual');
    try { $service->enqueue(); throw new RuntimeException('Duplicate accepted'); }
    catch(RuntimeException $e) { checkBackup(str_contains($e->getMessage(),'Đang có'),'Duplicate enqueue is rejected'); }
    $service->runPending($google,$archive); $backup->refresh();
    checkBackup($backup->status==='failed' && $backup->local_path!==null,'Sheets failure retains verified encrypted package and is not completed');
    $path=storage_path('app/'.$backup->local_path);
    checkBackup(is_file($path) && hash_file('sha256',$path)===$backup->sha256,'Encrypted package checksum matches');
    $zip=dirname($path).'/test-decrypted.zip'; $archive->decrypt($path,$zip); $archive->verify($zip,$backup->manifest); unlink($zip);
    checkBackup(count($backup->manifest['files'])>1,'Database and attachments are included and verified');
    $hash=$backup->sha256; $service->retry($backup); $google->fail=false; $service->runPending($google,$archive); $backup->refresh();
    checkBackup($backup->status==='completed' && $backup->completed_at!==null,'Retry completes when Sheets recovers');
    checkBackup($google->uploads===1 && $hash===$backup->sha256,'Retry reuses original package and uploaded Drive file');
    $bad=dirname($path).'/tampered.hlbackup'; copy($path,$bad); $f=fopen($bad,'r+b'); fseek($f,100); fwrite($f,'TAMPER'); fclose($f);
    try { $archive->decrypt($bad,dirname($path).'/bad.zip'); throw new LogicException('Tampering accepted'); }
    catch(RuntimeException $e) { checkBackup(true,'Tampered archive is rejected'); }
    $compiled=$app['blade.compiler']->compileString(file_get_contents(resource_path('views/admin/backups/index.blade.php'))); file_put_contents('/tmp/system-backup-view.php',$compiled);
} finally { if($backup){File::deleteDirectory(storage_path('app/system-backups/'.$backup->code)); $backup->delete();} }
