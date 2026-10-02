@php $evaluationScore = $ratedAssignment->evaluation_score ?? 0; @endphp
<div class="evaluation-widget small">
    @if((int) $ratedTask->created_by === (int) auth()->id())
        <button type="button" class="evaluation-toggle" aria-expanded="false" aria-controls="evaluation-panel-{{ $ratedAssignment->id }}" aria-label="Đánh giá {{ $ratedAssignment->user?->name }}">Đánh giá <span class="evaluation-value">{{ $evaluationScore }}%</span></button>
        <form id="evaluation-panel-{{ $ratedAssignment->id }}" hidden method="POST" action="{{ route('tasks.assignees.evaluate', [$ratedTask, $ratedAssignment]) }}" class="evaluation-options">
            @csrf
            <div class="d-flex align-items-center justify-content-between mb-2"><span>Đánh giá {{ $ratedAssignment->user?->name }}</span><output class="evaluation-value">{{ $evaluationScore }}%</output></div>
            <div class="evaluation-scale">
                <div class="evaluation-ticks">@foreach(range(0,100,10) as $tick)<button type="button" data-evaluation-tick="{{ $tick }}" class="{{ $evaluationScore === $tick ? 'selected' : '' }}" aria-label="{{ $tick }} phần trăm">{{ $tick }}<span></span></button>@endforeach</div>
                <input aria-label="Điểm đánh giá {{ $ratedAssignment->user?->name }}" class="evaluation-range" type="range" name="evaluation_score" min="0" max="100" step="10" value="{{ $evaluationScore }}">
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-evaluation-close>Đóng</button><button class="btn btn-sm btn-primary" type="submit">Lưu đánh giá</button></div>
        </form>
    @else
        <span class="evaluation-readonly" title="Chỉ người giao công việc này được đánh giá">{{ $evaluationScore }}%</span>
    @endif
</div>
@once
@push('scripts')
<script>
document.addEventListener('click', event => {
    const toggle = event.target.closest('.evaluation-toggle');
    if (toggle) {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
    }
    const tick = event.target.closest('[data-evaluation-tick]');
    if (tick) {
        const input = tick.closest('form').querySelector('.evaluation-range');
        input.value = tick.dataset.evaluationTick;
        input.dispatchEvent(new Event('input', {bubbles:true}));
    }
    const close = event.target.closest('[data-evaluation-close]');
    if (close) {
        close.closest('form').hidden = true;
        const button = close.closest('.evaluation-widget').querySelector('.evaluation-toggle');
        button.setAttribute('aria-expanded', 'false'); button.focus();
    }
});
document.addEventListener('input', event => {
    if (!event.target.matches('.evaluation-range')) return;
    const form = event.target.closest('form');
    form.querySelector('output').value = event.target.value + '%';
    form.querySelectorAll('[data-evaluation-tick]').forEach(tick => tick.classList.toggle('selected', tick.dataset.evaluationTick === event.target.value));
});
</script>
@endpush
@endonce
