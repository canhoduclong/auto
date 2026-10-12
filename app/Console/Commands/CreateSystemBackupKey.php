<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class CreateSystemBackupKey extends Command {
    protected $signature='system-backup:key';
    protected $description='Tạo khóa mã hóa sao lưu; không ghi đè khóa đã có';
    public function handle(): int {
        $path=config('system_backup.key_file');
        if (config('system_backup.key') || is_file($path)) { $this->info('Đã có khóa; giữ nguyên để đọc các bản sao cũ.'); return self::SUCCESS; }
        $stream=@fopen($path,'x');
        if (!$stream) { $this->error('Không tạo được tệp khóa. Kiểm tra quyền thư mục storage/app.'); return self::FAILURE; }
        chmod($path,0640);
        fwrite($stream,base64_encode(random_bytes(32)).PHP_EOL); fclose($stream);
        $this->info('Đã tạo khóa trong vùng riêng tư. Tải khóa từ admin và lưu riêng trên server dự phòng.');
        return self::SUCCESS;
    }
}
