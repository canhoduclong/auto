@foreach($detailQuery as $field => $value)
    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="revision" value="{{ $allocationRevision }}">
@if($allocationInBillTab ?? false)
    <input type="hidden" name="debt_section" value="bill">
@endif
