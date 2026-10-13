<?php
namespace App\Services\Backup;
use Google\Client;
use Google\Service\{Drive,Sheets};
use RuntimeException;
class BackupOAuth {
    public function redirectUri(): string {
        return config('system_backup.oauth_redirect_uri') ?: route('admin.backups.oauth.callback');
    }
    public function client(): Client {
        if (!config('system_backup.oauth_client_id') || !config('system_backup.oauth_client_secret')) throw new RuntimeException('Nhập OAuth Client ID và Client Secret, lưu cấu hình trước khi kết nối Google.');
        $client=new Client;
        $client->setClientId(config('system_backup.oauth_client_id'));
        $client->setClientSecret(config('system_backup.oauth_client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes(['openid','email',Drive::DRIVE,Sheets::SPREADSHEETS]);
        $client->setAccessType('offline'); $client->setPrompt('consent select_account');
        $client->setLoginHint(config('system_backup.google_email'));
        $client->setHttpClient(new \GuzzleHttp\Client(['timeout'=>60,'connect_timeout'=>15]));
        return $client;
    }
}
