<?php
namespace App\Http\Controllers;

use App\Models\{TaskAiLink, TaskAssignment, TaskAssignee};
use App\Services\TaskAiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskAiLinkController extends Controller
{
    public function issue(Request $request, TaskAssignment $taskAssignment)
    {
        $data = $request->validate(['days' => 'required|integer|in:1,7,30']);
        $token = bin2hex(random_bytes(32));
        $link = DB::transaction(function () use ($request, $taskAssignment, $data, $token) {
            $task = TaskAssignment::whereKey($taskAssignment->id)->lockForUpdate()->firstOrFail();
            $assignee = $task->assignees()->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($assignee && !in_array($assignee->status, ['completed', 'cancelled'], true), 403, 'Chỉ người đang được giao việc mới được tạo link cho phần việc của mình.');
            abort_if(in_array($task->status, ['draft', 'completed', 'done', 'cancelled'], true), 422, 'Công việc không còn nhận cập nhật.');
            return TaskAiLink::updateOrCreate(['task_assignee_id' => $assignee->id], ['task_id' => $task->id, 'user_id' => $request->user()->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays($data['days']), 'revoked_at' => null, 'last_used_at' => null]);
        });
        return response()->json(['url' => route('task-ai.show', ['token' => $token]), 'expires_at' => $link->expires_at->format('d/m/Y H:i'), 'message' => 'Link chỉ hiển thị một lần. Tạo lại sẽ vô hiệu hóa link cũ.'])->header('Cache-Control', 'no-store');
    }

    public function revoke(Request $request, TaskAssignment $taskAssignment)
    {
        DB::transaction(function () use ($request, $taskAssignment) {
            $task = TaskAssignment::whereKey($taskAssignment->id)->lockForUpdate()->firstOrFail();
            $assignee = $task->assignees()->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($assignee, 403);
            TaskAiLink::where('task_assignee_id', $assignee->id)->update(['revoked_at' => now()]);
        });
        return response()->json(['message' => 'Đã thu hồi link AI của phần việc này.']);
    }

    public function show(Request $request, string $token, TaskAiAccessService $service)
    {
        $request->headers->set('Accept', 'application/json');
        [$link, $assignee, $task] = $service->resolve($token);
        return $this->privateResponse([
            'scope' => 'Chỉ đọc công việc này và cập nhật phần việc của người cấp link. Không có quyền truy cập dữ liệu, tệp đính kèm, người dùng hoặc công việc khác.',
            'expires_at' => $link->expires_at->toIso8601String(),
            'task' => $service->summary($task, $assignee),
            'instructions' => [
                'method' => 'POST', 'update_url' => route('task-ai.update', ['token' => $token]),
                'actions' => [
                    'accept' => ['action' => 'accept'],
                    'start' => ['action' => 'start', 'note' => 'Bắt đầu thực hiện'],
                    'progress' => ['action' => 'progress', 'note' => 'Nội dung tiến độ (tối đa 1000 ký tự)'],
                    'complete' => ['action' => 'complete', 'completion_content' => 'Kết quả thực hiện (10–5000 ký tự)', 'completion_notes' => 'Ghi chú kết quả (5–2000 ký tự)', 'images[]' => 'multipart/form-data: 1–10 ảnh minh chứng JPEG/PNG/GIF/WebP, mỗi ảnh tối đa 5MB'],
                ],
                'rules' => ['Tiếp nhận trước, bắt đầu sau khi duyệt giao đã hoàn tất, rồi cập nhật tiến độ hoặc gửi hoàn thành.', 'Chỉ gửi các trường được hướng dẫn. Không thể sửa mô tả, người nhận, thời hạn, tự duyệt hoặc xác nhận hoàn thành.', 'Gửi hoàn thành chỉ chuyển sang chờ người giao xác nhận; không được tạo minh chứng giả. Nếu AI không gửi được POST hoặc ảnh, người dùng cần hoàn thành bước đó trên giao diện.'],
            ],
        ]);
    }

    public function update(Request $request, string $token, TaskAiAccessService $service)
    {
        $request->headers->set('Accept', 'application/json');
        $summary = DB::transaction(function () use ($service, $token, $request) {
            [$link, $assignee, $task] = $service->resolve($token, true);
            return $service->update($request, $link, $assignee, $task);
        });
        return $this->privateResponse(['message' => 'Đã ghi nhận cập nhật công việc qua AI.', 'task' => $summary]);
    }

    private function privateResponse(array $data)
    {
        return response()->json($data)->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow, noarchive']);
    }
}
