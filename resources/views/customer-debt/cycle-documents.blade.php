@php
    $documentRoute = ['ledger' => $item['ledger'], 'date' => $item['to']];
@endphp
<section class="debt-cycle-panel mt-3" aria-label="Chứng từ của kỳ">
    <h5 class="h6 mb-2">Chứng từ của kỳ</h5>
    @if($documents)
        <ul class="list-unstyled small mb-2">
            @foreach($documents as $document)
                <li class="mb-1">
                    <a href="{{ route('customer-debt.cycles.documents.download', $documentRoute + ['document' => $document['id']]) }}">{{ $document['name'] }}</a>
                    <span class="text-muted">· {{ \Carbon\Carbon::parse($document['uploaded_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</span>
                </li>
            @endforeach
        </ul>
    @endif
    <form method="POST" action="{{ route('customer-debt.cycles.documents.upload', $documentRoute) }}" enctype="multipart/form-data" class="d-flex align-items-end flex-wrap gap-2">
        @csrf
        <div>
            <label class="form-label" for="cycle-documents-{{ $item['to'] }}">Biên bản đã ký hoặc chứng từ đối chiếu</label>
            <input type="file" class="form-control form-control-sm" id="cycle-documents-{{ $item['to'] }}" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple required>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-primary">Tải chứng từ lên</button>
    </form>
    <small class="text-muted">PDF hoặc ảnh, tối đa 10 MB mỗi tệp.</small>
</section>
