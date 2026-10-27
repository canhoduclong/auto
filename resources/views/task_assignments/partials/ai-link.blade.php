@if($myAssignee && $myAssignee->status !== 'cancelled')
@php($aiLink = \App\Models\TaskAiLink::where('task_assignee_id', $myAssignee->id)->first())
<details class="card border-primary my-3" id="taskAiPanel">
    <summary class="card-header fw-bold">Link AI cập nhật phần việc của tôi</summary>
    <div class="card-body">
        <p class="small text-muted">Link chỉ mở công việc này, cho phép tiếp nhận, bắt đầu, ghi tiến độ và gửi báo cáo hoàn thành thay bạn. Người giao vẫn xác nhận kết quả. Không cấp quyền sửa nội dung giao việc hoặc truy cập dữ liệu khác. Chỉ chia sẻ link với AI bạn tin dùng.</p>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if(!in_array($task->status, ['draft','completed','done','cancelled'], true) && $myAssignee->status !== 'completed')
            <label for="taskAiDays" class="mb-0">Hạn dùng</label>
            <select id="taskAiDays" class="form-select" style="width:160px"><option value="1">1 ngày</option><option value="7" selected>7 ngày</option><option value="30">30 ngày</option></select>
            <button type="button" class="btn btn-primary" id="taskAiIssue">Tạo / thay link AI</button>
            @endif
            <button type="button" class="btn btn-outline-danger" id="taskAiRevoke">Thu hồi link</button>
        </div>
        <div class="small mt-2" id="taskAiMessage" role="status">@if($aiLink && !$aiLink->revoked_at && $aiLink->expires_at->isFuture())Link đã cấp, hết hạn {{ $aiLink->expires_at->format('d/m/Y H:i') }}. Tạo lại nếu bạn chưa lưu link.@else Chưa có link còn hiệu lực.@endif</div>
        <div id="taskAiResult" hidden class="mt-3">
            <label for="taskAiPrompt" class="form-label">Sao chép nội dung sau gửi cho AI (link chỉ hiển thị một lần)</label>
            <textarea id="taskAiPrompt" class="form-control" rows="5" readonly></textarea>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="taskAiCopy">Sao chép hướng dẫn và link</button>
            <div class="small text-muted mt-2">AI cần có công cụ gọi HTTP POST để cập nhật. Link tự chứa hướng dẫn API; nếu AI chỉ đọc được trang web, AI có thể hướng dẫn bạn cập nhật thủ công.</div>
        </div>
    </div>
</details>
<script>
(() => {
    const panel = document.getElementById('taskAiPanel');
    if (window.location.hash === '#taskAiPanel') panel.open = true;
    const message = document.getElementById('taskAiMessage');
    const result = document.getElementById('taskAiResult');
    const prompt = document.getElementById('taskAiPrompt');
    const csrf = @json(csrf_token());
    async function send(url, method, body) {
        const buttons = panel.querySelectorAll('button'); buttons.forEach(b => b.disabled = true);
        try {
            const response = await fetch(url, {method, headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf}, body:JSON.stringify(body)});
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Không thể xử lý link.');
            message.textContent = data.message + (data.expires_at ? ' Hết hạn: ' + data.expires_at : '');
            result.hidden = !data.url;
            prompt.value = data.url ? 'Bạn chỉ được làm việc với công việc tại link này: ' + data.url + '\nĐọc hướng dẫn API tại link, tiếp nhận và cập nhật đúng tiến độ thực tế. Chỉ gửi báo cáo hoàn thành khi đã thực hiện xong và có ảnh minh chứng thật. Không truy cập dữ liệu hay công việc khác.' : '';
        } catch (error) { message.textContent = error.message; }
        finally { buttons.forEach(b => b.disabled = false); }
    }
    document.getElementById('taskAiIssue')?.addEventListener('click', () => send(@json(route('task-ai.issue', $task)), 'POST', {days:Number(document.getElementById('taskAiDays').value)}));
    document.getElementById('taskAiRevoke').addEventListener('click', () => send(@json(route('task-ai.revoke', $task)), 'DELETE', {}));
    document.getElementById('taskAiCopy').addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(prompt.value); message.textContent = 'Đã sao chép hướng dẫn và link.'; }
        catch { prompt.focus(); prompt.select(); message.textContent = 'Hãy nhấn Ctrl+C để sao chép.'; }
    });
})();
</script>
@endif
