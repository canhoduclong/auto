<?php
namespace App\Http\Controllers;
use App\Models\{Role,TaskPermission};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TaskRolePermissionController extends Controller {
 public function __construct(){ $this->middleware(['auth','role:admin']); }
 public function index(){ $roles=Role::with('taskPermissions')->orderBy('name')->get();return view('task_assignments.role_permissions',compact('roles')); }
 public function update(Request $request,Role $role){
  $data=$request->validate(['permissions'=>'nullable|array','permissions.*'=>'string|distinct|in:'.implode(',',array_keys(TaskPermission::PERMISSION_LABELS))]);
  DB::transaction(function()use($role,$data){
   TaskPermission::where('role_id',$role->id)->delete();
   foreach($data['permissions']??[] as $slug)TaskPermission::create(['role_id'=>$role->id,'permission_slug'=>$slug]);
  });
  return back()->with('success','Đã cập nhật quyền giao việc cho vai trò '.$role->name.'.');
 }
}
