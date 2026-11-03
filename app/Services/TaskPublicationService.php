<?php

namespace App\Services;

use App\Models\{TaskAssignment, TaskStatusLog, User};
use App\Notifications\OperatingNotification;
use Illuminate\Support\Facades\{DB, Notification};

class TaskPublicationService
{
    public function configureForEdit(TaskAssignment $task, User $actor, ?int $approverId, array $recipientIds): void
    {
        DB::transaction(function () use ($task, $actor, $approverId, $recipientIds) {
            $task = TaskAssignment::whereKey($task->id)->lockForUpdate()->firstOrFail();
            abort_unless($task->canBeEditedBy($actor), 403);
            abort_if($approverId === (int) $actor->id, 422, 'Vui lòng chọn người duyệt khác người tạo.');
            $wasUnpublished = $task->awaitsPublication();
            $recipientIds = array_values(array_unique(array_map('intval', $recipientIds)));
            abort_if(empty($recipientIds), 422, 'Vui lòng chọn người nhận.');

            if ($approverId !== null) {
                abort_if($task->assignees()->whereNotNull('accepted_at')->exists(), 422, 'Công việc đã được tiếp nhận, không thể chuyển lại thành bản chờ duyệt.');
                $task->assignees()->delete();
                $task->update(['publication_approver_id' => $approverId, 'publication_status' => 'pending',
                    'publication_assignee_ids' => $recipientIds, 'published_at' => null, 'status' => 'draft']);
                TaskStatusLog::log($task, 'draft', $actor, 'Đã lưu nội dung và gửi duyệt công bố.');
                DB::afterCommit(fn () => $task->publicationApprover?->notify(new OperatingNotification('Công việc chờ duyệt công bố', $task->title, route('tasks.show', $task))));
                return;
            }

            $existingIds = $task->assignees()->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            $task->assignees()->whereNotIn('user_id', $recipientIds)->delete();
            foreach ($recipientIds as $id) $task->assignees()->firstOrCreate(['user_id' => $id], ['status' => 'pending']);
            $task->update(['publication_approver_id' => null, 'publication_status' => null,
                'publication_assignee_ids' => $recipientIds, 'published_at' => $task->published_at ?? now(),
                'status' => $wasUnpublished ? 'pending' : $task->status]);
            TaskStatusLog::log($task, $task->status, $actor, 'Đã lưu công việc — không cần duyệt công bố.');
            $newIds = array_values(array_diff($recipientIds, $existingIds));
            if ($newIds) DB::afterCommit(fn () => Notification::send(User::whereIn('id', $newIds)->get(), new OperatingNotification('Công việc mới cần tiếp nhận', $task->title, route('tasks.show', $task))));
        });
    }

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
