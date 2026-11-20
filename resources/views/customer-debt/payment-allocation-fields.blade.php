@foreach($detailQuery as $field => $value)
    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="revision" value="{{ $allocationRevision }}">
