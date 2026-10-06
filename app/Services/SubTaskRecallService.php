<?php
namespace App\Services;

use App\Models\{TaskAssignment, TaskStatusLog, User};
use Illuminate\Support\Facades\DB;

class SubTaskRecallService
{
    public function canRecall(TaskAssignment $parent, TaskAssignment $child, User $actor): bool
    {
        return (int) $child->parent_id === (int) $parent->id
            && ($actor->hasRole('admin')
                || (int) $child->created_by === (int) $actor->id
                || (int) $parent->created_by === (int) $actor->id);
    }

    public function deleteRecalled(int $parentId, int $childId, User $actor): int
    {
        return DB::transaction(function () use ($parentId, $childId, $actor) {
            $parent = TaskAssignment::whereKey($parentId)->lockForUpdate()->firstOrFail();
            $child = TaskAssignment::whereKey($childId)->lockForUpdate()->firstOrFail();
            abort_unless((int) $child->parent_id === $parentId, 404);
            abort_unless($this->canRecall($parent, $child, $actor), 403);
            abort_unless($child->status === TaskAssignment::STATUS_CANCELLED, 422, 'Chỉ được xóa công việc con đã thu hồi / hủy.');
            $pending = collect([$child]);
            $branch = collect();
            $seen = [];
            while ($pending->isNotEmpty()) {
                $current = $pending->shift();
                if (isset($seen[$current->id])) continue;
                $seen[$current->id] = true;
                $branch->push($current);
                $pending = $pending->concat(TaskAssignment::where('parent_id', $current->id)->lockForUpdate()->get());
            }
            abort_if($branch->contains(fn ($task) => $task->status !== TaskAssignment::STATUS_CANCELLED), 422,
                'Việc con còn chứa công việc chưa thu hồi hoặc đã hoàn thành. Hãy xử lý các việc bên dưới trước khi xóa.');
            foreach ($branch as $task) {
                TaskStatusLog::log($task, $task->status, $actor, 'Xóa công việc con sau thu hồi.');
                $task->delete();
            }
            TaskStatusLog::log($parent, $parent->status, $actor, 'Đã xóa công việc con '.$child->code.' — '.$child->title.' sau thu hồi ('.$branch->count().' việc).');
            return $branch->count();
        });
    }

    public function recall(int $parentId, int $childId, User $actor, string $reason): int
    {
        return DB::transaction(function () use ($parentId, $childId, $actor, $reason) {
            $parent = TaskAssignment::whereKey($parentId)->lockForUpdate()->firstOrFail();
            $child = TaskAssignment::whereKey($childId)->lockForUpdate()->firstOrFail();
            abort_unless((int)$child->parent_id === $parentId,404);
            abort_unless($this->canRecall($parent, $child, $actor),403);
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
