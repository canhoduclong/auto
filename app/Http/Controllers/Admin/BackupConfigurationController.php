<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\{EnvironmentFileEditor};
use App\Services\Backup\BackupOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
class BackupConfigurationController extends Controller {
    public function save(Request $request,EnvironmentFileEditor $editor) {
        $data=$request->validate([
            'google_email'=>'required|email|max:254',
            'folder_id'=>'required|string|regex:/^[a-zA-Z0-9_-]+$/|max:200',
            'spreadsheet_id'=>'required|string|regex:/^[a-zA-Z0-9_-]+$/|max:200',
            'oauth_redirect_uri'=>'required|url:http,https|max:1000',
            'oauth_client_id'=>'required|string|max:300|ends_with:.apps.googleusercontent.com',
            'oauth_client_secret'=>'nullable|string|max:500',
        ]);
        $data['google_email']=strtolower(trim($data['google_email']));
        $values=['BACKUP_GOOGLE_OAUTH_REDIRECT_URI'=>$data['oauth_redirect_uri'],'BACKUP_GOOGLE_MODE'=>'oauth','BACKUP_GOOGLE_EMAIL'=>$data['google_email'],'BACKUP_GOOGLE_DRIVE_FOLDER_ID'=>$data['folder_id'],'BACKUP_GOOGLE_SPREADSHEET_ID'=>$data['spreadsheet_id'],'BACKUP_GOOGLE_OAUTH_CLIENT_ID'=>$data['oauth_client_id']];
        if (!empty($data['oauth_client_secret'])) $values['BACKUP_GOOGLE_OAUTH_CLIENT_SECRET']=$data['oauth_client_secret'];
        if (!config('system_backup.oauth_client_secret') && empty($data['oauth_client_secret'])) return back()->with('error','Cần nhập OAuth Client Secret cho lần cấu hình đầu tiên.');
        if ($data['google_email']!==strtolower((string)config('system_backup.google_email')) || $data['oauth_client_id']!==config('system_backup.oauth_client_id') || !empty($data['oauth_client_secret'])) {
            $values['BACKUP_GOOGLE_OAUTH_REFRESH_TOKEN']=''; $values['BACKUP_GOOGLE_OAUTH_CONNECTED_EMAIL']='';
        }
        try { $editor->updateValues($values); Artisan::call('config:clear'); }
        catch (\RuntimeException $e) { return back()->with('error',$e->getMessage()); }
        return back()->with('success','Đã lưu cấu hình backup vào .env. Bấm Kết nối Google để cấp quyền.');
    }
    public function connect(Request $request,BackupOAuth $oauth) {
        try { $client=$oauth->client(); }
        catch (\RuntimeException $e) { return back()->with('error',$e->getMessage()); }
        $state=bin2hex(random_bytes(32));
        $request->session()->put('backup_oauth',['state'=>$state,'time'=>time(),'client_id'=>config('system_backup.oauth_client_id'),'user_id'=>$request->user()->id,'email'=>strtolower((string)config('system_backup.google_email'))]);
        $client->setState($state);
        return redirect()->away($client->createAuthUrl());
    }
    public function callback(Request $request,BackupOAuth $oauth,EnvironmentFileEditor $editor) {
        $pending=$request->session()->pull('backup_oauth');
        if (!$pending || !hash_equals($pending['state'],(string)$request->query('state')) || time()-$pending['time']>600 || $pending['user_id']!==$request->user()->id || $pending['client_id']!==config('system_backup.oauth_client_id') || ($pending['email']??null)!==strtolower((string)config('system_backup.google_email'))) return redirect()->route('admin.backups.index', ['tab'=>'configuration'])->with('error','Phiên kết nối Google không hợp lệ hoặc hết hạn. Bấm Kết nối Google lại.');
        if ($request->query('error') || !$request->query('code')) return redirect()->route('admin.backups.index', ['tab'=>'configuration'])->with('error','Bạn chưa cấp quyền kết nối Google.');
        try {
            $client=$oauth->client(); $token=$client->fetchAccessTokenWithAuthCode((string)$request->query('code'));
            if (isset($token['error']) || empty($token['id_token'])) throw new \RuntimeException('Không nhận được thông tin xác thực Google.');
            $identity=$client->verifyIdToken($token['id_token']);
            if (!$identity || empty($identity['email_verified']) || strtolower($identity['email']??'')!==strtolower((string)config('system_backup.google_email'))) throw new \RuntimeException('Tài khoản Google không khớp với email đã cấu hình: '.config('system_backup.google_email').'. Hãy chọn lại đúng tài khoản.');
            $refresh=$token['refresh_token']??null;
            if (!$refresh) throw new \RuntimeException('Google chưa cấp refresh token. Thu hồi quyền ứng dụng trong tài khoản Google rồi kết nối lại và đồng ý cấp quyền.');
            $editor->updateValues(['BACKUP_GOOGLE_MODE'=>'oauth','BACKUP_GOOGLE_OAUTH_REFRESH_TOKEN'=>$refresh,'BACKUP_GOOGLE_OAUTH_CONNECTED_EMAIL'=>$identity['email']]);
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index', ['tab'=>'configuration'])->with('error',$e instanceof \RuntimeException && !$e instanceof \Google\Service\Exception ? $e->getMessage() : 'Kết nối OAuth thất bại. Kiểm tra Client ID, Client Secret và URL chuyển hướng.');
        }
        return redirect()->route('admin.backups.index', ['tab'=>'configuration'])->with('success','Đã kết nối Google bằng '.config('system_backup.google_email').'. Bấm Kiểm tra kết nối Google để kiểm tra quyền Drive và Sheets.');
    }
}
