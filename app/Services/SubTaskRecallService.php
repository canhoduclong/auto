<?php
namespace App\Services;

use App\Models\{TaskAssignment, TaskStatusLog, User};
use Illuminate\Support\Facades\DB;

class SubTaskRecallService
{
    public function recall(int $parentId, int $childId, User $actor, string $reason): int
    {
        return DB::transaction(function () use ($parentId, $childId, $actor, $reason) {
            $parent = TaskAssignment::whereKey($parentId)->lockForUpdate()->firstOrFail();
            $child = TaskAssignment::whereKey($childId)->lockForUpdate()->firstOrFail();
            abort_unless((int)$child->parent_id === $parentId,404);
            abort_unless($actor->hasRole('admin') || (int)$child->created_by === (int)$actor->id || (int)$parent->created_by === (int)$actor->id,403);
            abort_if(in_array($child->status,['done','cancelled'],true),422,'Công việc con đã hoàn thành hoặc đã thu hồi / hủy.');
            abort_if(trim($reason) === '',422,'Vui lòng nhập lý do thu hồi.');
            $pending = collect([$child]);
            $seen = [];
            $count = 0;
            while ($pending->isNotEmpty()) {
                $current = $pending->shift();
                if (isset($seen[$current->id])) continue;
                $seen[$current->id] = true;
                $pending = $pending->concat(TaskAssignment::where('parent_id',$current->id)->orderBy('id')->lockForUpdate()->get());
                if (in_array($current->status,['done','cancelled'],true)) continue;
                TaskStatusLog::log($current,TaskAssignment::STATUS_CANCELLED,$actor,'Thu hồi công việc con: '.$reason);
                $current->update(['status'=>TaskAssignment::STATUS_CANCELLED]);
                $current->assignees()->update(['status'=>'cancelled']);
                $count++;
            }
            TaskStatusLog::log($parent,$parent->status,$actor,'Thu hồi công việc con '.$child->code.' — '.$child->title.' ('.$count.' công việc). Lý do: '.$reason);
            return $count;
        });
    }
}
