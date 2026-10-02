<section class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white fw-semibold">TÀI LIỆU ĐÍNH KÈM</div>
    <div class="card-body">
        @php $documentCount = 0; @endphp
        @foreach(collect([$task])->concat($task->subTasks) as $documentTask)
            @foreach(($documentTask->attachments ?? []) as $path)
                @php $documentCount++; @endphp
                <div class="task-document">
                    @if(in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp']))<img src="{{ Storage::url($path) }}" alt="Tài liệu {{ $documentTask->title }}">@else<i class="ph ph-file-text fs-1 text-primary" aria-hidden="true"></i>@endif
                    <div><strong class="small">{{ basename($path) }}</strong><div class="small text-muted">Người giao: {{ $documentTask->creator?->name }}</div><a class="small d-block" href="{{ route('tasks.show', $documentTask) }}">{{ $documentTask->title }}</a><a class="small" href="{{ Storage::url($path) }}" target="_blank" rel="noopener">Mở / tải tài liệu</a></div>
                </div>
            @endforeach
            @foreach($documentTask->completionImages as $document)
                @php $documentCount++; @endphp
                <div class="task-document"><img src="{{ $document->getImageUrl() }}" alt="Tài liệu hoàn thành"><div><strong class="small">{{ $document->original_filename ?: 'Tài liệu hoàn thành' }}</strong><div class="small text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</div><a class="small d-block" href="{{ route('tasks.show', $documentTask) }}">{{ $documentTask->title }}</a><a class="small" href="{{ $document->getImageUrl() }}" target="_blank" rel="noopener">Mở / tải tài liệu</a></div></div>
            @endforeach
        @endforeach
        @if(!$documentCount)<p class="small text-muted mb-0">Chưa có tài liệu đính kèm. Tài liệu của công việc chính và việc con sẽ xuất hiện tại đây.</p>@endif
    </div>
</section>
