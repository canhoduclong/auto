<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $task->title }} — Trình chiếu</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eaf0f4;color:#172b36;font-family:Arial,sans-serif}header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 24px;background:#123e43;color:white;font-size:14px}header span{overflow-wrap:anywhere}button,a{font:inherit}button{cursor:pointer;border:1px solid #c7d8df;border-radius:8px;background:white;color:#164b50;padding:10px 16px;min-height:44px}header button{white-space:nowrap}.stage{max-width:1280px;margin:24px auto;padding:0 24px}.slide{display:block;background:white;border-radius:16px;padding:clamp(20px,3vw,40px);box-shadow:0 10px 35px #183b4412;min-height:70vh;overflow-wrap:anywhere;font-size:clamp(16px,1.6vw,22px);line-height:1.65}.slide.active{display:block}.slide h1{font-size:clamp(24px,3vw,38px);line-height:1.25;color:#164b50}.slide h2,.slide h3,.slide h4{color:#164b50;line-height:1.3}.slide table{border-collapse:collapse;width:100%;max-width:100%;font-size:.8em}.slide td,.slide th{border:1px solid #cbd5e1;padding:10px}.slide p{margin:0 0 1em}.slide li{margin-bottom:.4em}.eyebrow{font-size:16px;color:#617c84;text-transform:uppercase;letter-spacing:1px}.metadata{display:flex;flex-wrap:wrap;gap:6px 24px;font-size:14px;color:#607782;border-bottom:1px solid #dce6eb;padding-bottom:16px;margin-bottom:24px}.metadata p{margin:0}.presentation-content h2,.presentation-content h3,.presentation-content h4{margin:1.2em 0 .5em}.presentation-content>:first-child{margin-top:0}nav{display:flex;justify-content:center;align-items:center;gap:16px;padding:0 16px 24px}#source{display:none}a{color:inherit}@media(max-width:600px){header{padding:10px 12px}.stage{padding:0 12px;margin:12px auto}.slide{min-height:65vh}.slide table{display:block;overflow-x:auto}.metadata{font-size:16px}}@media print{header,nav{display:none}.slide{display:block!important;box-shadow:none;min-height:0}.stage{margin:0;padding:0}}
</style>
</head>
<body>
<header><span>{{ $task->code }} · {{ $task->title }}</span><button id="fullscreen" type="button">Toàn màn hình</button><a href="{{ url()->current() }}">Về công việc</a></header>
<main class="stage" id="slides">
<section class="slide active"><div class="eyebrow">Hoàng Long TNT · Công việc</div><h1>{{ $task->title }}</h1><div class="metadata"><p>{{ $task->code }} · {{ $task->operatingStatusLabel() }}</p><p>Người giao: {{ $task->creator?->name ?? '—' }}</p><p>Hạn hoàn thành: {{ $task->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</p><p>Thực hiện: {{ $task->assignees->map(fn($assignee) => $assignee->user?->name)->filter()->join(', ') ?: 'Chưa phân công' }}</p></div><div class="presentation-content">{!! \App\Support\TaskDescription::render($task->description) !!}</div></section>
</main>
<script>
document.getElementById('fullscreen').onclick=async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await document.documentElement.requestFullscreen()}catch{document.getElementById('fullscreen').textContent='Trình duyệt không hỗ trợ'}};
document.addEventListener('fullscreenchange',()=>document.getElementById('fullscreen').textContent=document.fullscreenElement?'Thoát toàn màn hình':'Toàn màn hình');
</script>
</body></html>
