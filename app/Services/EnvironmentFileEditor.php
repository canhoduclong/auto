<?php
namespace App\Services;
use Dotenv\Parser\Parser;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvironmentFileEditor
{
    public function __construct(private ?string $path = null, private ?string $backupDirectory = null)
    {
        $this->path ??= app()->environmentFilePath();
        $this->backupDirectory ??= storage_path('app/private/env-editor');
    }

    public function read(): string
    {
        if (is_link($this->path) || !is_file($this->path) || !is_readable($this->path)) throw new RuntimeException('Không đọc được tệp .env. Kiểm tra tệp và quyền truy cập của server.');
        $content=file_get_contents($this->path);
        if ($content===false) throw new RuntimeException('Không đọc được nội dung .env.');
        return $content;
    }

    public function save(string $content, string $expectedHash): void
    {
        if (strlen($content)>1048576 || str_contains($content,"\0")) throw new RuntimeException('Nội dung không hợp lệ hoặc vượt quá 1 MB.');
        try {
            $sentinel='ENV_EDITOR_CHECK_'.strtoupper(bin2hex(random_bytes(8)));
            $entries=(new Parser)->parse($content."\n".$sentinel."=ok\n");
            if (!$entries || end($entries)->getName()!==$sentinel) throw new \Dotenv\Exception\InvalidFileException('Unclosed multiline value');
        }
        catch (\Dotenv\Exception\InvalidFileException $e) { throw new RuntimeException('Định dạng .env không hợp lệ. Kiểm tra tên biến, dấu nháy và giá trị có khoảng trắng.'); }
        File::ensureDirectoryExists($this->backupDirectory,0700);
        $lock=fopen($this->backupDirectory.'/editor.lock','c');
        if (!$lock) throw new RuntimeException('Không tạo được khóa ghi .env.');
        chmod($this->backupDirectory.'/editor.lock',0600);
        $temporary=null;
        try {
            if (!flock($lock,LOCK_EX)) throw new RuntimeException('Không khóa được tệp để lưu.');
            $previous=$this->read();
            if (!hash_equals(hash('sha256',$previous),$expectedHash)) throw new RuntimeException('Tệp .env đã được thay đổi từ lần mở trang. Tải lại nội dung trước khi lưu để tránh ghi đè.');
            if ($content===$previous) return;
            $backup=$this->backupDirectory.'/env-'.date('Ymd-His').'-'.bin2hex(random_bytes(6)).'.bak';
            $handle=fopen($backup,'x');
            if (!$handle) throw new RuntimeException('Không tạo được bản dự phòng .env.');
            chmod($backup,0600);
            try { if(fwrite($handle,$previous)!==strlen($previous)) throw new RuntimeException('Không ghi đủ bản dự phòng .env.'); }
            finally { fclose($handle); }
            $temporary=tempnam(dirname($this->path),'.env-edit-');
            if (!$temporary) throw new RuntimeException('Không tạo được tệp tạm. Kiểm tra quyền ghi thư mục ứng dụng.');
            chmod($temporary,0640);
            if (file_put_contents($temporary,$content)!==strlen($content)) throw new RuntimeException('Không ghi đủ nội dung .env.');
            if (!rename($temporary,$this->path)) throw new RuntimeException('Không thay thế được .env. Bản hiện tại được giữ nguyên.');
            $temporary=null;
        } finally {
            if ($temporary) @unlink($temporary);
            flock($lock,LOCK_UN); fclose($lock);
        }
    }
}
