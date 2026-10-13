<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $task->title }} — Trình chiếu</title>
<style>
@font-face{font-family:PresentationInter;font-style:normal;font-weight:400;font-display:swap;src:url("{{ asset('assets/fonts/inter/Inter-Regular.woff2') }}") format('woff2')}
@font-face{font-family:PresentationInter;font-style:normal;font-weight:600;font-display:swap;src:url("{{ asset('assets/fonts/inter/Inter-SemiBold.woff2') }}") format('woff2')}
@font-face{font-family:PresentationInter;font-style:normal;font-weight:700;font-display:swap;src:url("{{ asset('assets/fonts/inter/Inter-Bold.woff2') }}") format('woff2')}

*{box-sizing:border-box}body{margin:0;background:#eaf0f4;color:#172b36;font-family:PresentationInter,"Segoe UI",Arial,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 24px;background:#123e43;color:white;font-size:14px}header span{overflow-wrap:anywhere}button,a{font:inherit}button{cursor:pointer;border:1px solid #c7d8df;border-radius:8px;background:white;color:#164b50;padding:10px 16px;min-height:44px}header button{white-space:nowrap}.stage{max-width:1280px;margin:24px auto;padding:0 24px}.slide{display:none;background:white;border-radius:16px;padding:clamp(20px,3vw,40px);box-shadow:0 10px 35px #183b4412;min-height:70vh;overflow-wrap:anywhere;font-size:clamp(16px,1.6vw,22px);line-height:1.65}.slide.active{display:block}.slide h1{font-size:clamp(24px,3vw,38px);line-height:1.25;color:#164b50}.slide h2,.slide h3,.slide h4{color:#164b50;line-height:1.3}.slide table{border-collapse:collapse;width:100%;max-width:100%;font-size:.8em}.slide td,.slide th{border:1px solid #cbd5e1;padding:10px}.slide p{margin:0 0 1em}.slide li{margin-bottom:.4em}.eyebrow{font-size:16px;color:#617c84;text-transform:uppercase;letter-spacing:1px}.metadata{display:flex;flex-wrap:wrap;gap:6px 24px;font-size:14px;color:#607782;border-bottom:1px solid #dce6eb;padding-bottom:16px;margin-bottom:24px}.metadata p{margin:0}.presentation-content h2,.presentation-content h3,.presentation-content h4{margin:1.2em 0 .5em}.presentation-content>:first-child{margin-top:0}nav{display:flex;justify-content:center;align-items:center;gap:16px;padding:0 16px 24px}#source{display:none}a{color:inherit}@media(max-width:600px){header{padding:10px 12px}.stage{padding:0 12px;margin:12px auto}.slide{min-height:65vh}.slide table{display:block;overflow-x:auto}.metadata{font-size:16px}}@media print{header,nav{display:none}.slide{display:block!important;box-shadow:none;min-height:0}.stage{margin:0;padding:0}}
.progress-person{border-bottom:1px solid #dce6eb;padding:20px 0}.person-heading{display:flex;flex-wrap:wrap;align-items:center;gap:12px;font-size:1em}.status-pill{font-size:14px;background:#edf7f4;color:#164b50;border-radius:6px;padding:4px 10px}.activity{border-left:2px solid #dce6eb;padding-left:12px;margin:10px 0;font-size:.8em}.documents{display:flex;flex-wrap:wrap;gap:10px;margin:12px 0}.documents a{border:1px solid #cbd5e1;border-radius:6px;padding:10px;max-width:240px;font-size:14px;text-decoration:none}.person-note{font-size:.8em;color:#607782;font-style:italic}nav button:disabled{opacity:.4;cursor:default}@media print{.slide{break-after:page}}
/* Normalize editor typography for presentation without changing saved content. */
.presentation-content,.presentation-content *{font-family:PresentationInter,"Segoe UI",Arial,sans-serif!important;text-shadow:none!important;letter-spacing:normal!important}
.presentation-content p,.presentation-content li,.presentation-content td,.presentation-content th,.presentation-content span{font-size:inherit!important;line-height:1.65!important}
.presentation-content h2{font-size:1.35em!important;font-weight:700!important;line-height:1.45!important}
.presentation-content h3{font-size:1.2em!important;font-weight:600!important;line-height:1.45!important}
.presentation-content h4{font-size:1.1em!important;font-weight:600!important;line-height:1.45!important}
.presentation-content strong,.presentation-content b{font-weight:700!important}
.presentation-header{padding:0;background:#123e43;border-bottom:1px solid #ffffff20}
.presentation-header-inner{width:100%;max-width:1280px;margin:auto;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.presentation-brand{display:flex;align-items:center;gap:12px;min-width:0}
.brand-mark{display:grid;place-items:center;flex-shrink:0;width:42px;height:42px;border:1px solid #ffffff60;border-radius:12px;background:#ffffff10;color:#fff;font-weight:700;font-size:17px}
.brand-caption{display:block;font-size:10px;font-weight:600;letter-spacing:1px;color:#b9d8d9;margin-bottom:4px}
.header-task-title{display:block;font-size:16px;font-weight:600;line-height:1.4;overflow-wrap:anywhere;color:#fff}
.presentation-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;font-size:13px}
.presentation-actions button{border:1px solid #ffffff40;border-radius:8px;background:#fff;color:#123e43;padding:9px 14px}
.presentation-actions .back-to-task{display:inline-flex;align-items:center;min-height:44px;padding:9px 14px;border:1px solid #ffffff40;border-radius:8px;text-decoration:none;color:#fff;white-space:nowrap}
.presentation-actions .back-to-task:hover{background:#ffffff15}
.slide h1{margin:12px 0 20px}.metadata{gap:8px 22px;line-height:1.6}.eyebrow{font-size:12px;letter-spacing:.6px}
@media(max-width:600px){.presentation-header-inner{padding:12px;flex-wrap:wrap;gap:12px}.presentation-brand{width:100%}.presentation-actions{width:100%;justify-content:flex-end}.brand-mark{width:36px;height:36px}.header-task-title{font-size:15px}.metadata{font-size:13px}}
</style>
</head>
<body>
<header class="presentation-header">
    <div class="presentation-header-inner">
        <div class="presentation-brand"><span class="brand-mark" aria-hidden="true">HL</span><div><span class="brand-caption">HOÀNG LONG TNT · TRÌNH CHIẾU</span><strong class="header-task-title">{{ $task->title }}</strong></div></div>
        <div class="presentation-actions"><button id="fullscreen" type="button">⛶ Toàn màn hình</button><a class="back-to-task" href="{{ url()->current() }}">← Về công việc</a></div>
    </div>
</header>
<main class="stage" id="slides">
<section class="slide active"><div class="eyebrow">Hoàng Long TNT · Công việc</div><h1>{{ $task->title }}</h1><div class="metadata"><p>{{ $task->code }} · {{ $task->operatingStatusLabel() }}</p><p>Người giao: {{ $task->creator?->name ?? '—' }}</p><p>Hạn hoàn thành: {{ $task->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</p><p>Thực hiện: {{ $task->assignees->map(fn($assignee) => $assignee->user?->name)->filter()->join(', ') ?: 'Chưa phân công' }}</p></div><div class="presentation-content">{!! \App\Support\TaskDescription::render($task->description) !!}</div></section>
@if($checklistProgress ?? null)
<section class="slide"><div class="eyebrow">Tiến độ theo hạng mục</div><h2>{{ $checklistProgress['percent'] }}% hoàn thành chung</h2><p>{{ $checklistProgress['done'] }}/{{ $checklistProgress['total'] }} hạng mục hoàn thành · {{ $checklistProgress['awaiting'] }} chờ xác nhận</p>
@foreach($checklistProgress['people'] as $person)<div class="progress-person"><strong>{{ $person['name'] }}</strong> · {{ $person['department'] }}<p>{{ $person['percent'] }}% · {{ $person['done'] }}/{{ $person['total'] }} hạng mục</p></div>@endforeach
</section>
@endif
@foreach($task->assignees->where('status','!=','cancelled')->chunk(3) as $people)
<section class="slide"><div class="eyebrow">Tiến độ công việc · {{ $loop->iteration }}</div><h2>Người thực hiện & cập nhật</h2>
@foreach($people as $assignment)
<article class="progress-person"><div class="person-heading"><strong>{{ $assignment->user?->name ?? 'Người dùng đã xóa' }}</strong><span class="status-pill">{{ ['pending'=>'Chờ thực hiện','in_progress'=>'Đang thực hiện','processing'=>'Đang thực hiện','completed'=>'Chờ xác nhận hoàn thành','done'=>'Đã hoàn thành','rejected'=>'Không thể thực hiện'][$assignment->status] ?? $assignment->status }}</span><span>Đánh giá {{ $assignment->evaluation_score ?? 0 }}%</span></div>
@forelse($task->statusLogs->where('changed_by',$assignment->user_id)->sortBy('created_at') as $activity)
<div class="activity">{{ $activity->created_at?->format('d/m/Y H:i') }} — {{ $activity->reason ?: (\App\Models\TaskAssignment::STATUS_LABELS[$activity->to_status] ?? $activity->to_status) }}</div>
@empty<div class="activity">Chưa có cập nhật hoạt động.</div>@endforelse
@php $documents=$task->completionImages->where('uploaded_by',$assignment->user_id)->merge($task->statusLogs->where('changed_by',$assignment->user_id)->flatMap(fn($log)=>$log->documents))->unique('id'); @endphp
@if($documents->isNotEmpty())<div class="documents">@foreach($documents as $document)<a href="{{ $document->getImageUrl() }}" target="_blank" rel="noopener">Mở / tải tài liệu · {{ $document->original_filename ?: 'Tài liệu' }}</a>@endforeach</div>@endif
@if($assignment->note)<div class="person-note">Ghi chú: {{ $assignment->note }}</div>@endif
</article>
@endforeach
</section>
@endforeach
</main>
<nav aria-label="Điều khiển trình chiếu"><button id="prev" type="button">← Trước</button><span id="counter" aria-live="polite"></span><button id="next" type="button">Tiếp →</button></nav>
<script>
const slides=[...document.querySelectorAll('#slides>.slide')];let current=0;
function show(index){current=Math.max(0,Math.min(slides.length-1,index));slides.forEach((slide,i)=>slide.classList.toggle('active',i===current));document.getElementById('counter').textContent=`${current+1} / ${slides.length}`;document.getElementById('prev').disabled=current===0;document.getElementById('next').disabled=current===slides.length-1;window.scrollTo(0,0);}
document.getElementById('prev').onclick=()=>show(current-1);document.getElementById('next').onclick=()=>show(current+1);
document.addEventListener('keydown',event=>{if(event.target.closest('button,a,input,textarea,select'))return;if(['ArrowRight','PageDown'].includes(event.key)){event.preventDefault();show(current+1)}if(['ArrowLeft','PageUp'].includes(event.key)){event.preventDefault();show(current-1)}});show(0);

document.getElementById('fullscreen').onclick=async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await document.documentElement.requestFullscreen()}catch{document.getElementById('fullscreen').textContent='Trình duyệt không hỗ trợ'}};
document.addEventListener('fullscreenchange',()=>document.getElementById('fullscreen').textContent=document.fullscreenElement?'⛶ Thoát toàn màn hình':'⛶ Toàn màn hình');
</script>
</body></html>
