<?php
namespace App\Services\Backup;
use RuntimeException;
use ZipArchive;

class BackupArchive
{
    public function key(): string
    {
        $encoded = (string) config('system_backup.key');
        if ($encoded === '' && is_readable(config('system_backup.key_file'))) $encoded=trim(file_get_contents(config('system_backup.key_file')));
        $key = base64_decode($encoded, true);
        if (!$key || strlen($key) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('Chưa cấu hình BACKUP_ENCRYPTION_KEY (khóa base64 32 byte). Lưu khóa ở nơi an toàn trên cả server chính và dự phòng.');
        }
        return $key;
    }

    public function encrypt(string $input, string $output): void
    {
        [$state,$header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($this->key());
        $in=fopen($input,'rb'); $out=fopen($output,'wb');
        if (!$in || !$out) throw new RuntimeException('Không mở được tệp mã hóa.');
        chmod($output,0600);
        try {
            fwrite($out,"HLBACKUP1".$header);
            while (!feof($in)) {
                $chunk=fread($in,1024*1024);
                if ($chunk===false) throw new RuntimeException('Không đọc được gói sao lưu.');
                $tag=feof($in) ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : 0;
                $cipher=sodium_crypto_secretstream_xchacha20poly1305_push($state,$chunk,'',$tag);
                if (fwrite($out,pack('N',strlen($cipher)).$cipher)!==4+strlen($cipher)) throw new RuntimeException('Không ghi đủ tệp mã hóa.');
            }
        } finally { fclose($in); fclose($out); }
    }

    public function decrypt(string $input, string $output): void
    {
        $in=fopen($input,'rb'); $out=fopen($output,'wb');
        chmod($output,0600);
        try {
            if (fread($in,9)!=='HLBACKUP1') throw new RuntimeException('Sai định dạng bản sao.');
            $header=fread($in,SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            $state=sodium_crypto_secretstream_xchacha20poly1305_init_pull($header,$this->key());
            $final=false;
            while (!feof($in)) {
                $length=fread($in,4);
                if ($length==='') break;
                if (strlen($length)!==4 || $final) throw new RuntimeException('Bản sao bị cắt hoặc có dữ liệu thừa.');
                $n=unpack('N',$length)[1];
                if ($n<17 || $n>1048576+17) throw new RuntimeException('Độ dài gói không hợp lệ.');
                $cipher='';
                while(strlen($cipher)<$n && !feof($in)) $cipher.=fread($in,$n-strlen($cipher));
                $result=sodium_crypto_secretstream_xchacha20poly1305_pull($state,$cipher);
                if (!$result) throw new RuntimeException('Sai khóa hoặc bản sao đã bị thay đổi.');
                [$plain,$tag]=$result;
                if (fwrite($out,$plain)!==strlen($plain)) throw new RuntimeException('Không ghi đủ dữ liệu kiểm tra.');
                $final=$tag===SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }
            if (!$final) throw new RuntimeException('Bản sao chưa hoàn tất.');
        } finally { fclose($in); fclose($out); }
    }

    public function verify(string $zipPath, array $manifest): void
    {
        $zip=new ZipArchive;
        if ($zip->open($zipPath)!==true) throw new RuntimeException('Không mở được gói sao lưu để kiểm tra.');
        try {
            foreach($manifest['files'] as $file) {
                $stream=$zip->getStream($file['path']);
                if (!$stream) throw new RuntimeException('Thiếu tệp trong bản sao: '.$file['path']);
                $hash=hash_init('sha256'); hash_update_stream($hash,$stream); fclose($stream);
                if (!hash_equals($file['sha256'],hash_final($hash))) throw new RuntimeException('Mã kiểm tra không khớp: '.$file['path']);
            }
        } finally { $zip->close(); }
    }
}
