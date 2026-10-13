<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $task->title }} — Trình chiếu</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eaf0f4;color:#172b36;font-family:Arial,sans-serif}header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 24px;background:#123e43;color:white;font-size:14px}header span{overflow-wrap:anywhere}button,a{font:inherit}button{cursor:pointer;border:1px solid #c7d8df;border-radius:8px;background:white;color:#164b50;padding:10px 16px;min-height:44px}header button{white-space:nowrap}.stage{max-width:1280px;margin:24px auto;padding:0 24px}.slide{display:none;background:white;border-radius:16px;padding:clamp(24px,4vw,64px);box-shadow:0 10px 35px #183b4412;min-height:70vh;overflow-wrap:anywhere;font-size:clamp(18px,2vw,28px);line-height:1.65}.slide.active{display:block}.slide h1{font-size:clamp(28px,4vw,52px);line-height:1.25;color:#164b50}.slide h2,.slide h3,.slide h4{color:#164b50;line-height:1.3}.slide table{border-collapse:collapse;width:100%;max-width:100%;font-size:.8em}.slide td,.slide th{border:1px solid #cbd5e1;padding:10px}.slide p{margin:0 0 1em}.slide li{margin-bottom:.4em}.eyebrow{font-size:16px;color:#617c84;text-transform:uppercase;letter-spacing:1px}.metadata{font-size:20px;color:#607782}nav{display:flex;justify-content:center;align-items:center;gap:16px;padding:0 16px 24px}#source{display:none}a{color:inherit}@media(max-width:600px){header{padding:10px 12px}.stage{padding:0 12px;margin:12px auto}.slide{min-height:65vh}.slide table{display:block;overflow-x:auto}.metadata{font-size:16px}}@media print{header,nav{display:none}.slide{display:block!important;break-after:page;box-shadow:none;min-height:0}.stage{margin:0;padding:0}}
</style>
</head>
<body>
<header><span>{{ $task->code }} · {{ $task->title }}</span><button id="fullscreen" type="button">Toàn màn hình</button><a href="{{ url()->current() }}">Về công việc</a></header>
<main class="stage" id="slides">
<section class="slide active"><div class="eyebrow">Hoàng Long TNT · Công việc</div><h1>{{ $task->title }}</h1><div class="metadata"><p>{{ $task->code }} · {{ $task->operatingStatusLabel() }}</p><p>Người giao: {{ $task->creator?->name ?? '—' }}</p><p>Hạn hoàn thành: {{ $task->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</p><p>Thực hiện: {{ $task->assignees->map(fn($assignee) => $assignee->user?->name)->filter()->join(', ') ?: 'Chưa phân công' }}</p></div></section>
</main>
<div id="source">{!! \App\Support\TaskDescription::render($task->description) !!}</div>
<nav aria-label="Điều khiển trình chiếu"><button id="prev" type="button">← Trước</button><span id="counter" aria-live="polite"></span><button id="next" type="button">Tiếp →</button></nav>
<script>
const source=document.getElementById('source'),stage=document.getElementById('slides');
let slide=null,blocks=0;
function newSlide(){slide=document.createElement('section');slide.className='slide';stage.append(slide);blocks=0;}
for(const node of [...source.childNodes]){if(node.nodeType===3&&!node.textContent.trim())continue;const heading=/^H[2-4]$/.test(node.nodeName);if(!slide||(heading&&slide.childNodes.length)||blocks>=6)newSlide();slide.append(node.cloneNode(true));blocks++;}
const slides=[...stage.children];let current=0;
function show(index){current=Math.max(0,Math.min(slides.length-1,index));slides.forEach((s,i)=>s.classList.toggle('active',i===current));document.getElementById('counter').textContent=`${current+1} / ${slides.length}`;document.getElementById('prev').disabled=current===0;document.getElementById('next').disabled=current===slides.length-1;window.scrollTo(0,0);}
document.getElementById('prev').onclick=()=>show(current-1);document.getElementById('next').onclick=()=>show(current+1);
document.addEventListener('keydown',e=>{if(['ArrowRight','PageDown',' '].includes(e.key)){e.preventDefault();show(current+1)}if(['ArrowLeft','PageUp'].includes(e.key)){e.preventDefault();show(current-1)}});
document.getElementById('fullscreen').onclick=async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await document.documentElement.requestFullscreen()}catch{document.getElementById('fullscreen').textContent='Trình duyệt không hỗ trợ'}};
document.addEventListener('fullscreenchange',()=>document.getElementById('fullscreen').textContent=document.fullscreenElement?'Thoát toàn màn hình':'Toàn màn hình');show(0);
</script>
</body></html>
