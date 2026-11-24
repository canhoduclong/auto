@php
    $types = \App\Http\Controllers\CompanyAssetController::DOCUMENT_TYPES;
    $fields = \App\Http\Controllers\CompanyAssetController::DOCUMENT_FIELDS;
    $moneyFields = \App\Http\Controllers\CompanyAssetController::DOCUMENT_MONEY_FIELDS;
    $money = fn ($amount) => number_format($amount, 0, ',', '.').' đ';
    $date = fn ($value) => \Carbon\Carbon::parse($value)->format('d/m/Y');
@endphp
<section class="card rounded-3 h-100">
    <div class="card-body">
        <h3 class="h5 mb-3">Chứng từ</h3>
        <details class="border rounded p-3 mb-3" @if(old('document_type') || $errors->has('files') || $errors->has('files.*')) open @endif>
            <summary class="fw-semibold">Bổ sung chứng từ</summary>
            <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                @include('accounting.assets.document-fields', ['required' => true])
                <button class="btn btn-primary btn-sm mt-3">Lưu chứng từ</button>
            </form>
        </details>
        <ul class="list-group list-group-flush">
            @forelse($documents as $document)
                @php
                    $type = $document->document_type ?? 'other';
                    $details = json_decode($document->details ?? '{}', true);
                @endphp
                <li class="list-group-item px-0">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="text-break">
                            <span class="badge text-bg-light mb-1">{{ $types[$type] }}</span>
                            <div><strong>{{ $document->title ?: $document->name }}</strong></div>
                            @if($document->document_number || $document->issued_on)
                                <div class="small text-muted">
                                    @if($document->document_number) Số {{ $document->document_number }} @endif
                                    @if($document->issued_on) · {{ $date($document->issued_on) }} @endif
                                </div>
                            @endif
                            @if($document->title)<div class="small text-muted">{{ $document->name }}</div>@endif
                        </div>
                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ $downloadUrl($document->id) }}">Tải xuống</a>
                    </div>
                    @if($details)
                        <details class="small mt-2">
                            <summary>Thông tin chứng từ</summary>
                            <dl class="row mb-0 mt-2">
                                @foreach($details as $key => $value)
                                    <dt class="col-sm-6">{{ $fields[$type][$key] }}</dt>
                                    <dd class="col-sm-6 mb-1">{{ in_array($key, $moneyFields, true) ? $money($value) : ($key === 'valid_until' ? $date($value) : $value) }}</dd>
                                @endforeach
                            </dl>
                        </details>
                    @endif
                </li>
            @empty
                <li class="list-group-item px-0 text-muted">Chưa có chứng từ.</li>
            @endforelse
        </ul>
        @if($documents->hasPages())<div class="mt-3">{{ $documents->links() }}</div>@endif
    </div>
</section>
