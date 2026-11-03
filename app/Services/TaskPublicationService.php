<?php

namespace App\Services;

use App\Models\{TaskAssignment, TaskStatusLog, User};
use App\Notifications\OperatingNotification;
use Illuminate\Support\Facades\{DB, Notification};

class TaskPublicationService
{
    public function decide(TaskAssignment $task, User $actor, string $decision, ?string $reason = null): TaskAssignment
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 422);
        abort_if($decision === 'reject' && trim((string) $reason) === '', 422, 'Vui lòng ghi nội dung cần chỉnh sửa.');
        return DB::transaction(function () use ($task, $actor, $decision, $reason) {
            $task = TaskAssignment::whereKey($task->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $task->publication_approver_id === (int) $actor->id, 403);
            abort_unless($task->awaitsPublication() && $task->publication_status === 'pending' && $task->status === 'draft', 422, 'Bản gửi duyệt không còn chờ xử lý.');
            if ($decision === 'reject') {
                TaskStatusLog::log($task, 'draft', $actor, 'Yêu cầu chỉnh sửa trước khi công bố: '.$reason);
                $task->update(['publication_status' => 'rejected']);
                DB::afterCommit(fn () => $task->creator?->notify(new OperatingNotification('Công việc cần chỉnh sửa', $reason, route('tasks.show', $task))));
                return $task;
            }

            abort_if(empty($task->publication_assignee_ids), 422, 'Bản gửi duyệt chưa có người nhận.');
            foreach ($task->publication_assignee_ids ?? [] as $id) {
                $task->assignees()->firstOrCreate(['user_id' => $id], ['status' => 'pending']);
            }
            if ($task->approval_flow_id) app(ApprovalService::class)->initTaskApproval($task);
            TaskStatusLog::log($task, 'pending', $actor, 'Đã duyệt công bố và giao việc cho những người nhận được chọn.');
            $task->update(['publication_status' => 'approved', 'published_at' => now(), 'status' => 'pending']);
            DB::afterCommit(function () use ($task) {
                Notification::send(User::whereIn('id', $task->publication_assignee_ids ?? [])->get(), new OperatingNotification('Công việc mới cần tiếp nhận', $task->title, route('tasks.show', $task)));
                $task->creator?->notify(new OperatingNotification('Công việc đã được duyệt công bố', $task->title, route('tasks.show', $task)));
            });
            return $task;
        });
    }

    public function resubmit(TaskAssignment $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $task = TaskAssignment::whereKey($task->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $task->created_by === (int) $actor->id, 403);
            abort_unless($task->awaitsPublication() && $task->publication_status === 'rejected' && $task->status === 'draft', 422);
            $task->update(['publication_status' => 'pending']);
            TaskStatusLog::log($task, 'draft', $actor, 'Đã gửi duyệt công bố lại.');
            DB::afterCommit(fn () => $task->publicationApprover?->notify(new OperatingNotification('Công việc chờ duyệt công bố', $task->title, route('tasks.show', $task))));
        });
    }
}
