<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\EnvironmentFileEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan,Validator,Log};
class EnvironmentEditorController extends Controller
{
    public function edit(EnvironmentFileEditor $editor)
    {
        try { $content=$editor->read(); }
        catch (\RuntimeException $e) { return $this->page('', '', $e->getMessage(),503); }
        return $this->page($content,hash('sha256',$content));
    }
    public function update(Request $request,EnvironmentFileEditor $editor)
    {
        $validator=Validator::make($request->all(),['env_content'=>'required|string|max:1048576','env_hash'=>'required|string|size:64']);
        $content=(string)$request->input('env_content',''); $hash=(string)$request->input('env_hash','');
        if ($validator->fails()) return $this->page($content,$hash,'Nội dung .env và mã phiên bản là bắt buộc; dung lượng tối đa 1 MB.',422);
        try { $editor->save($content,$hash); }
        catch (\RuntimeException $e) { return $this->page($content,$hash,$e->getMessage(),409); }
        Log::info('Admin updated environment file',['user_id'=>$request->user()->id]);
        try { Artisan::call('config:clear'); }
        catch (\Throwable $e) { return $this->page($content,hash('sha256',$content),'Đã lưu .env nhưng chưa xóa được cache cấu hình. Chạy php artisan config:clear trên server.',500); }
        return redirect()->route('admin.environment.edit')->with('success','Đã lưu .env và xóa cache cấu hình. Tiến trình chạy lâu như queue cần khởi động lại để nhận cấu hình mới.');
    }
    private function page(string $content,string $hash,?string $error=null,int $status=200)
    {
        return response()->view('admin.settings.environment',compact('content','hash','error'),$status)->header('Cache-Control','no-store, private')->header('Pragma','no-cache');
    }
}
