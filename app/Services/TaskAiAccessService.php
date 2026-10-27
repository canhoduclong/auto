<?php
namespace App\Services;

use App\Models\{TaskAiLink, TaskAssignment, TaskAssignee, TaskCompletionImage, TaskStatusLog};
use App\Support\TaskDocumentStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class TaskAiAccessService
{
    public function resolve(string $token, bool $lock = false): array
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token), 404, 'Link không hợp lệ.');
        $link = TaskAiLink::where('token_hash', hash('sha256', $token))->first();
        abort_unless($link && !$link->revoked_at && $link->expires_at->isFuture(), 404, 'Link không hợp lệ hoặc đã hết hạn.');
        $assignee = TaskAssignee::whereKey($link->task_assignee_id)->first();
        abort_unless($link && $assignee && $assignee->user && $assignee->status !== 'cancelled' && (int) $link->user_id === (int) $assignee->user_id && (int) $link->task_id === (int) $assignee->task_id, 404, 'Link không còn hiệu lực.');
        $task = TaskAssignment::whereKey($assignee->task_id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        abort_unless($task && !in_array($task->status, ['draft', 'done', 'cancelled'], true), 404, 'Công việc không còn nhận cập nhật.');
        if ($lock) {
            // Match task -> assignee -> capability lock order used by issuance/revocation.
            $assignee = TaskAssignee::whereKey($assignee->id)->lockForUpdate()->first();
            $link = TaskAiLink::whereKey($link->id)->lockForUpdate()->first();
            abort_unless($link && $assignee && $assignee->user && $assignee->status !== 'cancelled' && (int) $link->user_id === (int) $assignee->user_id && (int) $link->task_id === (int) $assignee->task_id
                && $link && hash_equals($link->token_hash, hash('sha256', $token))
                && !$link->revoked_at && $link->expires_at->isFuture(), 404, 'Link không còn hiệu lực.');
        }

        return [$link, $assignee, $task];
    }

    public function summary(TaskAssignment $task, TaskAssignee $assignee): array
    {
        // Explicit allowlist: no people, customer/debt records, attachments or other tasks.
        return [
            'code' => $task->code, 'title' => $task->title,
            'description' => html_entity_decode(strip_tags((string) $task->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'due_at' => $task->due_date?->toIso8601String(),
            'task_status' => $task->status, 'your_status' => $assignee->status,
            'accepted' => $assignee->accepted_at !== null, 'started' => $assignee->started_at !== null,
        ];
    }

    public function update(Request $request, TaskAiLink $link, TaskAssignee $assignee, TaskAssignment $task): array
    {
        $allowed = ['action', 'note', 'completion_content', 'completion_notes', 'images'];
        abort_if(array_diff(array_keys($request->all()), $allowed) !== [], 422, 'Chỉ được gửi các trường cập nhật công việc đã cho phép.');
        $data = $request->validate([
            'action' => 'required|in:accept,start,progress,complete',
            'note' => 'nullable|string|max:1000|required_if:action,progress',
            'completion_content' => 'required_if:action,complete|nullable|string|min:10|max:5000',
            'completion_notes' => 'required_if:action,complete|nullable|string|min:5|max:2000',
            'images' => 'required_if:action,complete|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);
        abort_if(in_array($task->status, ['completed', 'done', 'cancelled', 'draft'], true) || in_array($assignee->status, ['completed', 'cancelled'], true), 409, 'Phần việc đã kết thúc hoặc đang chờ xác nhận.');
        $action = $data['action'];
        $before = $task->status;
        if ($action === 'accept') {
            abort_unless($assignee->status === 'pending', 409, 'Phần việc không còn chờ tiếp nhận.');
            $assignee->update(['accepted_at' => $assignee->accepted_at ?? now()]);
        } else {
            abort_unless($assignee->accepted_at, 422, 'Cần tiếp nhận công việc trước.');
            abort_if($task->approvalSteps()->where('status', 'pending')->exists(), 422, 'Công việc còn chờ duyệt giao.');
            if ($action !== 'start') abort_unless($assignee->started_at, 422, 'Cần bắt đầu thực hiện trước.');
            if ($action === 'start') {
                $assignee->update(['status' => 'processing', 'started_at' => $assignee->started_at ?? now(), 'note' => $data['note'] ?? $assignee->note]);
                if (in_array($task->status, ['pending', 'rejected'], true)) $task->update(['status' => 'processing', 'rejected_reason' => null]);
            } elseif ($action === 'progress') {
                $assignee->update(['note' => $data['note']]);
            } else {
                $assignee->update(['status' => 'completed', 'note' => $data['completion_content']."\n\n".$data['completion_notes'], 'completed_at' => now()]);
                $allDone = $task->assignees()->where('status', '!=', 'completed')->doesntExist();
                $task->update([
                    'status' => $allDone ? 'completed' : 'processing',
                    'completion_content' => $data['completion_content'], 'completion_notes' => $data['completion_notes'],
                    'completed_at' => $allDone ? now() : null, 'rejected_reason' => null,
                ]);
            }
        }
        $labels = ['accept' => 'Tiếp nhận', 'start' => 'Bắt đầu', 'progress' => 'Cập nhật tiến độ', 'complete' => 'Gửi báo cáo hoàn thành'];
        $log = TaskStatusLog::create([
            'task_id' => $task->id, 'from_status' => $before, 'to_status' => $task->status,
            'changed_by' => $assignee->user_id, 'reason' => '[AI link #'.$link->id.'] '.$labels[$action].': '.($data['note'] ?? ($data['completion_content'] ?? '')),
            'created_at' => now(),
        ]);
        $paths = [];
        try {
            foreach ($request->file('images', []) as $index => $image) {
                $paths[] = $path = TaskDocumentStorage::store($image, 'task-completions/'.$task->id.'/'.now()->format('Y/m/d'));
                TaskCompletionImage::create(['task_id' => $task->id, 'image_path' => $path, 'original_filename' => $image->getClientOriginalName(), 'uploaded_by' => $assignee->user_id, 'status_log_id' => $log->id, 'explanation' => $data['completion_content'] ?? null, 'sort_order' => $index]);
            }
            $link->update(['last_used_at' => now()]);
        } catch (\Throwable $e) {
            foreach ($paths as $path) Storage::disk('public')->delete($path);
            throw $e;
        }
        return $this->summary($task, $assignee);
    }
}
