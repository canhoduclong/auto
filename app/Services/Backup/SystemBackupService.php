<?php
namespace App\Services\Backup;
use App\Models\{Setting,SystemBackup};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemBackupService
{
    public function enqueue(string $type='manual', ?int $userId=null): SystemBackup
    {
        return DB::transaction(function () use ($type,$userId) {
            // Serialize creation using an existing persistent settings row.
            Setting::firstOrCreate(['key'=>'backup_enqueue_lock'],['value'=>'1']);
            Setting::where('key','backup_enqueue_lock')->lockForUpdate()->first();
            if (SystemBackup::whereIn('status',['pending','running'])->exists()) throw new RuntimeException('Đang có bản sao chờ xử lý hoặc đang chạy.');
            $month=$type==='monthly'?now()->format('Y-m'):null;
            if ($month && ($existing=SystemBackup::where('month',$month)->first())) return $existing;
            return SystemBackup::create(['code'=>'BACKUP-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6)), 'type'=>$type,'month'=>$month,'created_by'=>$userId,'status'=>'pending','stage'=>'Chờ xử lý']);
        });
    }

    public function retry(SystemBackup $backup): void
    {
        DB::transaction(function () use ($backup) {
            Setting::where('key','backup_enqueue_lock')->lockForUpdate()->first();
            $backup=SystemBackup::whereKey($backup->id)->lockForUpdate()->firstOrFail();
            if ($backup->status!=='failed') throw new RuntimeException('Chỉ thử lại bản sao bị lỗi.');
            if (SystemBackup::whereIn('status',['pending','running'])->exists()) throw new RuntimeException('Có bản sao khác đang chờ hoặc đang chạy.');
            $backup->update(['status'=>'pending','stage'=>'Chờ thử lại','error'=>null]);
        });
    }

    public function runPending(BackupGoogleStorage $google, BackupArchive $archive): ?SystemBackup
    {
        $root=storage_path('app/system-backups'); File::ensureDirectoryExists($root,0770); chmod($root,02770);
        $lock=fopen($root.'/worker.lock','c'); chmod($root.'/worker.lock',0660);
        if (!flock($lock,LOCK_EX|LOCK_NB)) { fclose($lock); return null; }
        try {
            // A previous process was killed; require an explicit retry, preserving any completed local package.
            SystemBackup::where('status','running')->update(['status'=>'failed','stage'=>'Bị gián đoạn','error'=>'Tiến trình sao lưu bị gián đoạn. Hãy bấm Thử lại.']);
            $backup=SystemBackup::where('status','pending')->oldest()->first();
            if (!$backup) return null;
            $backup->update(['status'=>'running','stage'=>'Kiểm tra cấu hình','error'=>null,'started_at'=>now()]);
            $work=$root.'/'.$backup->code; File::ensureDirectoryExists($work,0750); chmod($work,02750);
            try {
                $archive->key();
                if (!$backup->local_path || !is_file(storage_path('app/'.$backup->local_path))) {
                    $this->build($backup,$work,$archive);
                } elseif (!hash_equals($backup->sha256,hash_file('sha256',storage_path('app/'.$backup->local_path)))) {
                    throw new RuntimeException('Bản sao local bị thay đổi; không thể thử lại tải lên.');
                }
                $backup->update(['stage'=>'Kiểm tra kết nối Google']); $google->check();
                $backup->update(['stage'=>'Tải bản sao lên Drive']);
                if (!$backup->drive_file_id) $backup->update(['drive_file_id'=>$google->upload($backup,storage_path('app/'.$backup->local_path))]);
                $backup->update(['stage'=>'Ghi danh mục Google Sheets']);
                $google->record($backup,'completed');
                $backup->update(['status'=>'completed','stage'=>'Hoàn tất','completed_at'=>now(),'error'=>null]);
            } catch (\Throwable $e) {
                report($e);
                $message=$e instanceof \Google\Service\Exception ? BackupGoogleStorage::errorMessage($e) : $e->getMessage();
                $backup->update(['status'=>'failed','error'=>Str::limit($message,2000),'stage'=>'Thất bại']);
                try { $google->record($backup,'failed'); } catch (\Throwable $ignored) { /* Preserve original failure. */ }
            } finally {
                foreach(['database.sql','backup.zip','verify.zip','mysql.cnf'] as $name) @unlink($work.'/'.$name);
            }
            return $backup->fresh();
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }

    public function build(SystemBackup $backup, string $work, BackupArchive $archive): void
    {
        $connection=config('database.default'); $config=config('database.connections.'.$connection);
        if (($config['driver']??null)!=='mysql') throw new RuntimeException('Sao lưu hiện hỗ trợ MySQL/MariaDB.');
        $nonTransactional=DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' AND ENGINE <> 'InnoDB'",[$config['database']]);
        if ($nonTransactional) throw new RuntimeException('Có bảng không dùng InnoDB; chưa thể tạo bản dữ liệu nhất quán bằng giao dịch.');
        $backup->update(['stage'=>'Xuất toàn bộ cơ sở dữ liệu']);
        $quote=fn($v)=>'"'.str_replace(['\\','"',"\n","\r"],['\\\\','\\"','\\n','\\r'],(string)$v).'"';
        $cnf="[client]\nuser=".$quote($config['username'])."\npassword=".$quote($config['password'])."\nhost=".$quote($config['host'])."\nport=".(int)($config['port']??3306)."\n";
        if (!empty($config['unix_socket'])) $cnf.='socket='.$quote($config['unix_socket'])."\n";
        file_put_contents($work.'/mysql.cnf',$cnf); chmod($work.'/mysql.cnf',0600);
        $process=new Process([config('system_backup.dump_binary'),'--defaults-extra-file='.$work.'/mysql.cnf','--single-transaction','--quick','--hex-blob','--no-tablespaces','--skip-lock-tables','--default-character-set=utf8mb4','--result-file='.$work.'/database.sql',$config['database']]);
        $process->setTimeout(3600); $process->run(); @unlink($work.'/mysql.cnf');
        if (!$process->isSuccessful()) throw new RuntimeException('Không xuất được cơ sở dữ liệu. Kiểm tra mysqldump và quyền SELECT/SHOW VIEW/TRIGGER của tài khoản DB.');
        chmod($work.'/database.sql',0600);
        $manifest=['format_version'=>1,'code'=>$backup->code,'database_snapshot_started_at'=>$backup->started_at->toIso8601String(),'database_dump_finished_at'=>now()->toIso8601String(),'database_engine'=>'mysql','php_version'=>PHP_VERSION,'laravel_version'=>app()->version(),'tables'=>array_map(fn($table)=>array_values((array)$table)[0],DB::select('SHOW TABLES')),'files'=>[], 'note'=>'SQL chứa cấu trúc và toàn bộ bảng. Tài liệu được kiểm tra khi đóng gói; tránh xóa/sửa tài liệu và chạy migration trong lúc sao lưu.'];
        $zip=new ZipArchive;
        if ($zip->open($work.'/backup.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Không tạo được gói sao lưu.');
        try {
            $add=function($path,$name) use ($zip,&$manifest) {
                if (!is_readable($path) || is_link($path)) throw new RuntimeException('Tệp không đọc được hoặc là symlink: '.$name);
                $manifest['files'][]=['path'=>$name,'bytes'=>filesize($path),'sha256'=>hash_file('sha256',$path)];
                if (!$zip->addFile($path,$name)) throw new RuntimeException('Không thêm được tệp: '.$name);
            };
            $add($work.'/database.sql','database.sql');
            if (is_file(base_path('composer.lock'))) $add(base_path('composer.lock'),'composer.lock');
            $backup->update(['stage'=>'Đóng gói tài liệu và mã kiểm tra']);
            foreach(config('system_backup.directories') as $directory) {
                $base=base_path($directory);
                if (!is_dir($base)) continue;
                foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base,\FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file->isFile()) $add($file->getPathname(),$directory.'/'.substr($file->getPathname(),strlen($base)+1));
                }
            }
            $zip->addFromString('manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        } finally { if (!$zip->close()) throw new RuntimeException('Không hoàn tất được gói ZIP.'); }
        chmod($work.'/backup.zip',0600); $archive->verify($work.'/backup.zip',$manifest);
        $backup->update(['stage'=>'Mã hóa và kiểm tra bản sao']);
        $path=$work.'/archive.hlbackup'; $archive->encrypt($work.'/backup.zip',$path);
        $archive->decrypt($path,$work.'/verify.zip'); $archive->verify($work.'/verify.zip',$manifest);
        chmod($path,0640);
        $backup->update(['local_path'=>'system-backups/'.$backup->code.'/archive.hlbackup','sha256'=>hash_file('sha256',$path),'bytes'=>filesize($path),'manifest'=>$manifest]);
    }
}
