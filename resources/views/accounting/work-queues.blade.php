@php
    $queueCards = [
        'revenue' => ['title' => 'Đơn hoàn thành cần duyệt doanh thu', 'icon' => 'bi-check2-square', 'description' => 'Đơn đã giao, chưa xác nhận đối soát.', 'url' => accounting_route('reconciliation', ['work_queue' => 1, 'accounting_status' => 'pending'])],
        'adjustments' => ['title' => 'Yêu cầu đơn hàng', 'icon' => 'bi-pencil-square', 'description' => 'Yêu cầu điều chỉnh đang đến bước kế toán xử lý.', 'url' => accounting_route('order-adjustments', ['status' => 'pending'])],
        'finance' => ['title' => 'Yêu cầu tài chính cần xử lý', 'icon' => 'bi-cash-stack', 'description' => 'Phiếu đến lượt duyệt hoặc chờ kế toán hoàn tất thu/chi.', 'url' => accounting_route('cashflow', ['work_queue' => 1])],
        'todo' => ['title' => 'Việc cần làm', 'icon' => 'bi-list-check', 'description' => 'Việc giao cho tôi: chờ nhận, đang làm hoặc cần làm lại.', 'url' => route('operating.index', ['filter' => 'received', 'attention' => 'todo'])],
        'related' => ['title' => 'Việc liên quan tới tôi', 'icon' => 'bi-people', 'description' => 'Việc tôi giao chờ xác nhận và biểu quyết chờ tôi trả lời.', 'url' => route('operating.index', ['filter' => 'mine', 'attention' => 'related'])],
    ];
@endphp
<section class="acc-workspace mb-4" aria-labelledby="acc-work-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div><h2 id="acc-work-title" class="h5 mb-1">Công việc cần xử lý</h2><div class="small text-muted">Bao gồm việc tồn từ các ngày trước · Cập nhật khi tải lại trang</div></div>
        <a class="btn btn-outline-primary btn-sm" href="{{ url()->full() }}"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</a>
    </div>
    <div class="acc-work-grid">
    @foreach($queueCards as $key => $queueCard)
        @php
            $queue = $workQueues[$key];
            $rows = collect();
            if ($key === 'revenue') {
                $rows = $queue['items']->map(fn ($item) => ['title' => $item->code.' · '.($item->customer?->name ?: 'Khách hàng'), 'meta' => 'Chờ đối soát', 'url' => accounting_route('reconciliation.detail', $item)]);
            } elseif ($key === 'adjustments') {
                $rows = $queue['items']->map(fn ($item) => ['title' => 'Yêu cầu #'.$item->id.' · '.($item->order?->code ?: 'Đơn hàng'), 'meta' => $item->order?->customer?->name, 'url' => $queueCard['url'].'&keyword='.$item->id]);
            } elseif ($key === 'finance') {
                $rows = $queue['items']->map(fn ($item) => ['title' => '#'.$item->id.' · '.($item->request_title ?: 'Phiếu tài chính'), 'meta' => $item->status === \App\Models\Transaction::STATUS_APPROVED_PENDING_COMPLETION ? 'Chờ hoàn tất thu/chi' : 'Chờ duyệt', 'url' => accounting_route('cashflow.show', $item)]);
            } elseif ($key === 'todo') {
                $rows = $queue['items']->map(fn ($item) => ['title' => $item->title, 'meta' => $item->due_date ? 'Hạn: '.$item->due_date->format('d/m/Y') : 'Chưa đặt hạn', 'url' => route('tasks.show', $item)]);
            } else {
                $rows = $queue['tasks']->map(fn ($item) => ['title' => $item->title, 'meta' => 'Chờ xác nhận hoàn thành', 'url' => route('tasks.show', $item)])
                    ->concat($queue['votes']->map(fn ($item) => ['title' => $item->title, 'meta' => 'Chờ biểu quyết', 'url' => route('operating.proposals.show', $item)]))->take(3);
            }
        @endphp
        <article class="acc-work-card">
            <a class="acc-work-heading" href="{{ $queueCard['url'] }}">
                <i class="bi {{ $queueCard['icon'] }} acc-work-icon" aria-hidden="true"></i>
                <h3>{{ $queueCard['title'] }}</h3>
                @if($queue['count'] > 0)<span class="acc-work-count" aria-label="{{ $queue['count'] }} mục cần xử lý">{{ number_format($queue['count']) }}</span>@endif
            </a>
            <p class="acc-work-description">{{ $queueCard['description'] }}</p>
            <div class="acc-work-items">
                @forelse($rows as $row)
                    <a href="{{ $row['url'] }}" class="acc-work-item"><span class="acc-work-item-title">{{ $row['title'] }}</span><span class="acc-work-item-meta">{{ $row['meta'] }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                @empty
                    <div class="acc-work-empty"><i class="bi bi-check-circle me-1"></i>Không có công việc chờ xử lý</div>
                @endforelse
            </div>
            <a href="{{ $queueCard['url'] }}" class="acc-work-open">Mở danh sách <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </article>
    @endforeach
    </div>
</section>
@push('styles')
<style>
.acc-work-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; }
.acc-work-card { grid-column:span 2; min-width:0; display:flex; flex-direction:column; background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.acc-work-card:nth-last-child(-n+2) { grid-column:span 3; }
.acc-work-heading { display:flex; align-items:center; gap:9px; padding:16px 16px 8px; color:#163c3a; text-decoration:none; }
.acc-work-heading h3 { flex:1; min-width:0; font-size:15px; line-height:1.45; font-weight:700; margin:0; }
.acc-work-icon { color:#0f766e; font-size:19px; }
.acc-work-count { display:inline-flex; flex-shrink:0; align-items:center; justify-content:center; min-width:24px; height:24px; padding:0 6px; border-radius:999px; background:#dc3545; color:#fff; font-size:12px; font-weight:700; }
.acc-work-description { padding:0 16px; margin:0 0 12px; font-size:12px; line-height:1.5; color:#64748b; }
.acc-work-items { padding:0 16px; flex:1; }
.acc-work-item { display:grid; grid-template-columns:minmax(0,1fr) 12px; column-gap:6px; padding:9px 0; border-top:1px solid #f1f5f9; text-decoration:none; color:#334155; }
.acc-work-item-title { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px; font-weight:600; }
.acc-work-item-meta { grid-row:2; font-size:12px; color:#64748b; }
.acc-work-item > i { grid-column:2; grid-row:1 / span 2; align-self:center; font-size:11px; }
.acc-work-item:hover .acc-work-item-title { color:#0f766e; }
.acc-work-empty { padding:16px 0; font-size:13px; color:#64748b; }
.acc-work-open { display:flex; justify-content:space-between; padding:10px 16px; margin-top:8px; background:#f8fafc; border-top:1px solid #edf2f7; color:#0f766e; font-size:13px; font-weight:600; text-decoration:none; }
@media(max-width:1199.98px) { .acc-work-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .acc-work-card,.acc-work-card:nth-last-child(-n+2) { grid-column:auto; } .acc-work-card:last-child { grid-column:1 / -1; } }
@media(max-width:575.98px) { .acc-work-grid { grid-template-columns:minmax(0,1fr); gap:10px; } .acc-work-card:last-child { grid-column:auto; } }
</style>
@endpush
