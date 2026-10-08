@php
    $timelineNodes = \App\Support\ShippingExpenseTimeline::nodes($claim, $timelineUsers);
@endphp
<div class="shipping-timeline {{ ($compact ?? false) ? 'shipping-timeline-compact' : '' }}">
    <div class="shipping-timeline-heading">
        <span class="small text-muted">{{ $claim->run->status === 'confirmed' ? 'Đã hoàn tất xét duyệt' : ('Bước '.($claim->run->current_step + 1).'/'.count($claim->run->configuration['steps'])) }}</span>
    </div>
    <ol class="shipping-timeline-nodes" aria-label="Tiến trình yêu cầu phí ship #{{ $claim->id }}">
        @foreach($timelineNodes as $node)
            <li class="shipping-timeline-node is-{{ $node['state'] }}" @if(in_array($node['state'], ['current', 'revision'])) aria-current="step" @endif>
                @if($node['person'])
                    <div class="shipping-timeline-person">{{ $node['person'] }}</div>
                @endif
                <span class="shipping-timeline-dot" aria-hidden="true">{{ $node['state'] === 'done' ? '✓' : ($node['state'] === 'rejected' ? '×' : '') }}</span>
                <div>
                    @foreach($node['entries'] as $entry)
                        <div class="shipping-timeline-entry">
                            <div class="shipping-timeline-entry-row">
                            @if($entry['time'])
                                <time class="small text-muted" datetime="{{ $entry['time']->toIso8601String() }}">{{ $entry['time']->format('d/m/Y H:i') }}</time>
                            @endif
                                <span class="shipping-timeline-entry-action {{ $loop->last && $node['state']==='requested'?'is-highlighted':'' }}">{{ $entry['label'] }}</span>
                            </div>
                            @if($entry['note'])
                                <div class="shipping-timeline-note">{{ $entry['note'] }}</div>
                            @endif
                        </div>
                    @endforeach
                    @if(empty($node['entries']))
                        <div class="shipping-timeline-label">{{ $node['label'] }}</div>
                    @endif
                    @if(empty($node['entries']) || in_array($node['state'],['current','revision']))
                        <div class="shipping-timeline-description">{{ $node['state']==='revision'?'Cần điều chỉnh & nộp lại':$node['description'] }}</div>
                    @endif
                    @if(!empty($node['note']))
                        <div class="shipping-timeline-note">{{ $node['note'] }}</div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
