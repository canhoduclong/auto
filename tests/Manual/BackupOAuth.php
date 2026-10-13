<?php
require __DIR__.'/../../vendor/autoload.php';$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\EnvironmentFileEditor;
use App\Services\Backup\BackupOAuth;
use App\Http\Controllers\Admin\BackupConfigurationController;
use Illuminate\Http\Request;
$dir=sys_get_temp_dir().'/backup-oauth-'.bin2hex(random_bytes(4));mkdir($dir,0700);file_put_contents($dir.'/.env',"OTHER=keep\nBACKUP_GOOGLE_EMAIL=old\n");
$editor=new EnvironmentFileEditor($dir.'/.env',$dir.'/backups');
try {
 $editor->updateValues(['BACKUP_GOOGLE_EMAIL'=>'chi.hoanglongtnt@gmail.com','BACKUP_GOOGLE_OAUTH_CLIENT_SECRET'=>'test-secret']);
 if(!str_contains($editor->read(),"OTHER=keep\n") || !str_contains($editor->read(),'BACKUP_GOOGLE_EMAIL="chi.hoanglongtnt@gmail.com"'))throw new Exception('Env update failed');echo "PASS: backup settings persisted without changing other variables\n";
 config(['system_backup.google_email'=>'backup@example.com','system_backup.oauth_client_id'=>'test.apps.googleusercontent.com','system_backup.oauth_client_secret'=>'test-secret']);
 $oauth=new class extends BackupOAuth {
 public string $email='someone@example.com';
 public function client(): Google\Client { $email=$this->email;return new class($email) extends Google\Client {
 public function __construct(private string $email) { parent::__construct(); }
 public function fetchAccessTokenWithAuthCode($code, $codeVerifier=null) {return ['id_token'=>'TEST','refresh_token'=>'TEST-REFRESH'];}
 public function verifyIdToken($idToken=null) {return ['email'=>$this->email,'email_verified'=>true];}
 }; }
 };
 $controller=new BackupConfigurationController;$session=$app['session']->driver();
 $request=Request::create('/callback','GET',['state'=>'good','code'=>'test']);$request->setLaravelSession($session);$request->setUserResolver(fn()=>(object)['id'=>42]);
 $state=['state'=>'good','time'=>time(),'client_id'=>'test.apps.googleusercontent.com','user_id'=>42,'email'=>'backup@example.com'];
 $session->put('backup_oauth',$state);$before=$editor->read();$controller->callback($request,$oauth,$editor);
 if($before!==$editor->read())throw new Exception('Wrong account accepted');echo "PASS: wrong Google account rejected\n";
 $oauth->email='backup@example.com';$session->put('backup_oauth',$state);$controller->callback($request,$oauth,$editor);
 if(!str_contains($editor->read(),'BACKUP_GOOGLE_OAUTH_CONNECTED_EMAIL="backup@example.com"'))throw new Exception('Chi token not saved');echo "PASS: configured account token persisted\n";
 $before=$editor->read();$controller->callback($request,$oauth,$editor);if($before!==$editor->read())throw new Exception('Replay changed env');echo "PASS: callback state consumed; replay rejected\n";
 file_put_contents('/tmp/backup-oauth-view.php',app('blade.compiler')->compileString(file_get_contents(resource_path('views/admin/backups/index.blade.php'))));
}finally{Illuminate\Support\Facades\File::deleteDirectory($dir);}
