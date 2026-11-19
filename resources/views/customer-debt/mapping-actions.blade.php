@if($canLoad && empty($source['manual_only']))
    <div class="d-flex flex-wrap gap-2{{ ($mappingToolbar ?? false) ? '' : ' mt-2' }}">
        <button type="button" class="btn btn-sm btn-outline-primary debt-map-button" data-key="{{ $source['key'] }}" data-name="{{ $source['name'] }}" data-code="{{ $source['code'] }}" data-customer="{{ $source['customer_id'] }}">{{ $source['customer_id'] ? 'Đổi ghép' : 'Chọn khách để ghép' }}</button>
        @if($source['customer_id'] || $source['manual_mapping'])
            <form method="POST" action="{{ route('customer-debt.mapping.destroy') }}" onsubmit="return confirm('Gỡ ghép nguồn này khỏi khách hệ thống? Công nợ sẽ được tính lại theo liên kết còn lại.')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="source_key" value="{{ $source['key'] }}">
                <input type="hidden" name="customer_id" value="{{ $source['customer_id'] }}">
                <button type="submit" class="btn btn-sm btn-outline-danger">Gỡ ghép khách</button>
            </form>
        @endif
    </div>
@endif
