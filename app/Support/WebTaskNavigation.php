<?php
namespace App\Support;
use App\Models\{User,TaskDelegateConfig};
use App\Services\TaskMenuService;
class WebTaskNavigation {
    public static function items(User $user): array {
        $items=[];
        $add=function($label,$filter,$icon)use(&$items){$items[]=['label'=>$label,'url'=>route('operating.index',['filter'=>$filter]),'active'=>request()->routeIs('operating.index') && request('filter','mine')===$filter,'icon'=>$icon];};
        $add('Tổng quan công việc','mine','grid');
        $add('Nhận việc / Chưa tiếp nhận','unaccepted','inbox');
        $add('Việc tôi nhận','received','list-task');
        $add('Đang thực hiện','working','play-circle');
        $add('Báo cáo đã gửi / Chờ xác nhận','reported','send-check');
        $add('Lịch sử công việc','history','clock-history');
        if(TaskMenuService::canAssignTasks($user) || TaskDelegateConfig::canAssignTasks($user)) {
            $items[]=['label'=>'Tạo công việc / Giao việc','url'=>route('tasks.create'),'active'=>request()->routeIs('tasks.create','task-assignments.create'),'icon'=>'plus-circle'];
            $add('Việc tôi giao','assigned','send');
            $add('Chờ nghiệm thu','verification','check2-circle');
            $add('Yêu cầu phối hợp','coordination','people');
            $items[]=['label'=>'Mở đề xuất biểu quyết','url'=>route('operating.proposals.create'),'active'=>request()->routeIs('operating.proposals.create'),'icon'=>'ui-checks'];
        }
        if (!TaskMenuService::isReceiptOnlyShipper($user)) $items[]=['label'=>'Biểu quyết liên quan đến tôi','url'=>route('operating.index',['filter'=>'votes']).'#bieu-quyet','active'=>(request()->routeIs('operating.index') && request('filter')==='votes') || request()->routeIs('operating.proposals.show'),'icon'=>'check2-square'];
        if($user->hasRole('admin')) {
            $add('Quản trị tất cả công việc','all','kanban');
            $add('Công việc đã xóa','deleted','trash');
            foreach(['task-role-permissions.index'=>['Quyền công việc theo vai trò','shield-check'],'task-delegate-configs.index'=>['Quyền giao việc cho từng người','diagram-3'],'approval-workflows.index'=>['Cấu hình quy trình phê duyệt','signpost-split'],'roles.index'=>['Vai trò & quyền sử dụng','shield-lock'],'permissions.index'=>['Danh mục quyền truy cập','key'],'users.index'=>['Người dùng / Người nhận việc','person-gear']] as $route=>$meta) {
                $items[]=['label'=>$meta[0],'url'=>route($route),'active'=>request()->routeIs(str_replace('.index','.*',$route)),'icon'=>$meta[1]];
            }
        }
        return $items;
    }
}
