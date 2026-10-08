@auth
@php
    $attached=($entityProcessSubjects??collect())->get($entity->id,collect());
    $engine=app(\App\Services\ProcessEngine::class);
@endphp
<div class="d-flex flex-column gap-1 mt-2">
@foreach($attached as $subject)
    @php $linkedRun=$subject->run; @endphp
    @if(in_array($position,$linkedRun->configuration['positions']??['inbox','order_list','order_detail','shipping_list']))
        <div><span class="small text-muted">{{ $linkedRun->activity==='shipping_expense'?'Phí ship':'Xét duyệt' }} · {{ $linkedRun->statusLabel() }}</span> <a href="{{ route('process-inbox.show',$linkedRun) }}" class="btn btn-sm {{ $engine->canHandle($linkedRun,auth()->user())?'btn-primary':'btn-outline-secondary' }}">{{ $engine->canHandle($linkedRun,auth()->user())?'Xử lý':'Xem hồ sơ' }}</a></div>
    @endif
@endforeach
@foreach($entityProcessDefinitions??[] as $entityDefinition)
    @if(in_array($position,$entityDefinition->configuration['positions']??[]) && \App\Support\ProcessActivities::canSubmit($entityDefinition->activity,$entity,auth()->user()) && !$attached->contains(fn($s)=>$s->run->activity===$entityDefinition->activity && in_array($s->run->status,['running','revision'])))
        <a class="btn btn-sm btn-outline-primary align-self-start" href="{{ route('process-inbox.request',[$entityDefinition,$entity->id]) }}">Gửi xét duyệt · {{ $entityDefinition->name }}</a>
    @endif
@endforeach
</div>
@endauth
