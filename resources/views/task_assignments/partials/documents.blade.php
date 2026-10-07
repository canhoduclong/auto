<section class="card shadow-sm mb-3 task-documents-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 fw-semibold"><span>Tài liệu đính kèm</span>
        @if(auth()->user()->hasRole('admin') || (int)$task->created_by===(int)auth()->id() || $task->assignees->contains('user_id',auth()->id()))
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#taskDocumentUpload" aria-expanded="false">+ Upload tài liệu &amp; diễn giải</button>
        @endif
    </div>
    <div class="card-body">
        @if(auth()->user()->hasRole('admin') || (int)$task->created_by===(int)auth()->id() || $task->assignees->contains('user_id',auth()->id()))
        <div class="collapse mb-3" id="taskDocumentUpload">
            <form method="POST" action="{{ route('tasks.documents.store',$task) }}" enctype="multipart/form-data" class="border rounded bg-light p-3">
                @csrf
                <label class="form-label small" for="task-document-files">Tài liệu *</label>
                <input id="task-document-files" type="file" name="documents[]" multiple required class="form-control mb-2" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                <div class="small text-muted mb-2">Tối đa 10 tệp, 20MB/tệp.</div>
                <label class="form-label small" for="task-document-note">Diễn giải *</label>
                <textarea id="task-document-note" name="explanation" rows="3" maxlength="2000" required class="form-control mb-2">{{ old('explanation') }}</textarea>
                <button class="btn btn-primary btn-sm" type="submit">Upload tài liệu</button>
            </form>
        </div>
        @endif
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
                <div class="task-document">@if(in_array(strtolower(pathinfo($document->image_path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp']))<img src="{{ $document->getImageUrl() }}" alt="Tài liệu công việc">@else<i class="ph ph-file-text fs-1 text-primary" aria-hidden="true"></i>@endif<div><strong class="small">{{ $document->original_filename ?: 'Tài liệu hoàn thành' }}</strong><div class="small text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</div><a class="small d-block" href="{{ route('tasks.show', $documentTask) }}">{{ $documentTask->title }}</a><a class="small" href="{{ $document->getImageUrl() }}" target="_blank" rel="noopener">Mở / tải tài liệu</a></div></div>
            @endforeach
        @endforeach
        @if(!$documentCount)<p class="small text-muted mb-0">Chưa có tài liệu đính kèm. Tài liệu của công việc chính và việc con sẽ xuất hiện tại đây.</p>@endif
    </div>
</section>
