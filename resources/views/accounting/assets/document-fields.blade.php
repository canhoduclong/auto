@php
    $types = \App\Http\Controllers\CompanyAssetController::DOCUMENT_TYPES;
    $fields = \App\Http\Controllers\CompanyAssetController::DOCUMENT_FIELDS;
    $moneyFields = \App\Http\Controllers\CompanyAssetController::DOCUMENT_MONEY_FIELDS;
    $documentType = old('document_type', $required ? 'invoice' : '');
@endphp
<div class="row g-2" data-asset-document>
    <label class="col-12"><span class="form-label">Loại chứng từ</span>
        <select class="form-select" name="document_type" data-document-type @required($required)>
            <option value="">{{ $required ? 'Chọn loại chứng từ' : 'Chọn nếu có chứng từ đính kèm' }}</option>
            @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected($documentType === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="col-12"><span class="form-label">Tên hoặc nội dung chứng từ</span>
        <input class="form-control" name="document_title" maxlength="150" value="{{ old('document_title') }}">
    </label>
    <label class="col-sm-6"><span class="form-label">Số chứng từ</span>
        <input class="form-control" name="document_number" maxlength="100" value="{{ old('document_number') }}">
    </label>
    <label class="col-sm-6"><span class="form-label">Ngày chứng từ</span>
        <input class="form-control" type="date" name="issued_on" value="{{ old('issued_on') }}">
    </label>
    @foreach($fields as $type => $specificFields)
        <fieldset class="col-12" data-document-fields="{{ $type }}" @if($type !== $documentType) hidden disabled @endif>
            <div class="row g-2">
                @foreach($specificFields as $key => $label)
                    <label class="col-sm-6"><span class="form-label">{{ $label }}</span>
                        @if(in_array($key, $moneyFields, true))
                            <input class="form-control" type="number" min="0" max="999999999999999" name="document_details[{{ $key }}]" value="{{ $type === $documentType ? old('document_details.'.$key) : '' }}">
                        @elseif($key === 'valid_until')
                            <input class="form-control" type="date" name="document_details[{{ $key }}]" value="{{ $type === $documentType ? old('document_details.'.$key) : '' }}">
                        @else
                            <input class="form-control" maxlength="500" name="document_details[{{ $key }}]" value="{{ $type === $documentType ? old('document_details.'.$key) : '' }}">
                        @endif
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    <label class="col-12"><span class="form-label">Bản scan hoặc tệp gốc</span>
        <input class="form-control" type="file" name="files[]" multiple @required($required)
            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
    </label>
    <p class="col-12 small text-muted mb-0">PDF, ảnh, Word hoặc Excel. Tối đa 10 tệp mỗi lần, 10 MB mỗi tệp. Các tệp trong cùng lượt tải dùng chung thông tin chứng từ.</p>
</div>
@once
    @push('scripts')
        <script src="{{ asset('js/company-asset-documents.js') }}?v={{ filemtime(public_path('js/company-asset-documents.js')) }}"></script>
    @endpush
@endonce
