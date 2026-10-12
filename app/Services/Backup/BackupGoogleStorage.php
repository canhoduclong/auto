<?php
namespace App\Services\Backup;
use App\Models\SystemBackup;
use Google\Client;
use Google\Http\MediaFileUpload;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ValueRange;
use RuntimeException;

class BackupGoogleStorage
{
    public static function errorMessage(\Google\Service\Exception $e): string
    {
        foreach ($e->getErrors() as $error) {
            if (($error['reason'] ?? '') === 'accessNotConfigured') return 'Google Drive/Sheets API chưa bật trong Google Cloud. Bật API tương ứng rồi bấm Thử lại.';
            if (($error['reason'] ?? '') === 'storageQuotaExceeded') return 'Không đủ dung lượng sở hữu tệp trên Drive. Service account cần Shared Drive; Drive cá nhân cần OAuth.';
        }
        return 'Google từ chối kết nối/tải lên (HTTP '.$e->getCode().'). Kiểm tra quyền chia sẻ, API Drive/Sheets và dung lượng.';
    }

    private function client(): Client
    {
        $path=config('system_backup.credentials') ?: config('services.google_sheets.credentials');
        if (!$path) throw new RuntimeException('Chưa cấu hình tài khoản Google.');
        if (!str_starts_with($path,'/')) $path=base_path($path);
        if (!is_readable($path)) throw new RuntimeException('Không đọc được thông tin kết nối Google.');
        $credentials=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        $client=new Client;
        $client->setHttpClient(new \GuzzleHttp\Client(['timeout'=>120,'connect_timeout'=>20]));
        $client->setScopes([Drive::DRIVE,Sheets::SPREADSHEETS]);
        if (($credentials['type']??'')==='authorized_user') {
            $client->setClientId($credentials['client_id']); $client->setClientSecret($credentials['client_secret']);
            $token=$client->fetchAccessTokenWithRefreshToken($credentials['refresh_token']);
            if (isset($token['error'])) throw new RuntimeException('Không làm mới được kết nối OAuth Google.');
        } else {
            $client->setAuthConfig($path);
            if (config('system_backup.subject')) $client->setSubject(config('system_backup.subject'));
        }
        return $client;
    }

    public function check(): array
    {
        $client=$this->client(); $drive=new Drive($client);
        $folder=$drive->files->get(config('system_backup.drive_folder_id'), ['fields'=>'id,mimeType,driveId,capabilities(canAddChildren)','supportsAllDrives'=>true]);
        if ($folder->getMimeType()!=='application/vnd.google-apps.folder' || !$folder->getCapabilities()?->getCanAddChildren()) {
            throw new RuntimeException('Thư mục Drive không hợp lệ hoặc tài khoản chưa có quyền thêm tệp.');
        }
        if ($client->isUsingApplicationDefaultCredentials() && !$folder->getDriveId() && !config('system_backup.subject')) {
            throw new RuntimeException('Service account cần thư mục trong Shared Drive, hoặc kết nối OAuth để tải lên Drive cá nhân.');
        }
        $sheets=new Sheets($client);
        $sheet=$sheets->spreadsheets->get(config('system_backup.spreadsheet_id'),['fields'=>'sheets.properties']);
        $found=false;
        foreach($sheet->getSheets() as $tab) if ($tab->getProperties()->getTitle()==='Backup_Index') $found=true;
        if (!$found) $sheets->spreadsheets->batchUpdate(config('system_backup.spreadsheet_id'),new BatchUpdateSpreadsheetRequest(['requests'=>[['addSheet'=>['properties'=>['title'=>'Backup_Index']]]]]));
        $sheets->spreadsheets_values->update(config('system_backup.spreadsheet_id'),"'Backup_Index'!A1:J1",new ValueRange(['values'=>[['Mã bản sao','Loại','Trạng thái','Bắt đầu','Hoàn tất','Dung lượng byte','SHA256','Drive file ID','Phiên bản','Thông báo']]]),['valueInputOption'=>'RAW']);
        return ['folder_id'=>$folder->getId()];
    }

    public function upload(SystemBackup $backup, string $path): string
    {
        $client=$this->client(); $drive=new Drive($client); $client->setDefer(true);
        try {
            $request=$drive->files->create(new DriveFile(['name'=>$backup->code.'.hlbackup','parents'=>[config('system_backup.drive_folder_id')]]),['supportsAllDrives'=>true,'fields'=>'id,size,md5Checksum']);
            $media=new MediaFileUpload($client,$request,'application/octet-stream',null,true,1024*1024);
            $media->setFileSize(filesize($path)); $stream=fopen($path,'rb'); $result=false;
            try { while (!$result && !feof($stream)) $result=$media->nextChunk(fread($stream,1024*1024)); }
            finally { fclose($stream); }
            if (!$result || (int)$result->getSize()!==filesize($path) || !hash_equals(hash_file('md5',$path),(string)$result->getMd5Checksum())) {
                throw new RuntimeException('Tệp trên Drive chưa đạt kiểm tra dung lượng/mã kiểm tra.');
            }
            return $result->getId();
        } finally { $client->setDefer(false); }
    }

    public function record(SystemBackup $backup, string $status): void
    {
        $sheets=new Sheets($this->client());
        $values=[[$backup->code,$backup->type,$status,$backup->started_at?->toIso8601String(),$status==='completed'?now()->toIso8601String():'',$backup->bytes,$backup->sha256,$backup->drive_file_id,$backup->manifest['format_version']??1,$backup->error??'']];
        if ($backup->sheet_row) {
            $sheets->spreadsheets_values->update(config('system_backup.spreadsheet_id'),"'Backup_Index'!A{$backup->sheet_row}:J{$backup->sheet_row}",new ValueRange(['values'=>$values]),['valueInputOption'=>'RAW']);
        } else {
            // Recover a previously appended row after a network interruption.
            $rows=$sheets->spreadsheets_values->get(config('system_backup.spreadsheet_id'),"'Backup_Index'!A:A")->getValues()??[];
            foreach($rows as $i=>$row) if (($row[0]??null)===$backup->code) { $backup->update(['sheet_row'=>$i+1]); $this->record($backup,$status); return; }
            $response=$sheets->spreadsheets_values->append(config('system_backup.spreadsheet_id'),"'Backup_Index'!A:J",new ValueRange(['values'=>$values]),['valueInputOption'=>'RAW','insertDataOption'=>'INSERT_ROWS']);
            if (!preg_match('/![A-Z]+(\d+):/', $response->getUpdates()->getUpdatedRange(),$matches)) throw new RuntimeException('Không xác định được dòng Google Sheets đã ghi.');
            $backup->update(['sheet_row'=>(int)$matches[1]]);
        }
    }
}
