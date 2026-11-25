@extends(accounting_layout())
@section('title', 'Khóa kết nối ChatGPT')
@section('accounting_content')
<section class="card">
    <div class="card-body">
        <h2 class="h5">Sao chép khóa kết nối</h2>
        <p>Sao chép khóa này vào phần xác thực của GPT Action theo kiểu <strong>API Key → Bearer</strong>. Admin có thể sao chép lại khóa trong danh sách kết nối.</p>
        <label class="d-block mb-3"><span class="form-label">Khóa kết nối</span>
            <input class="form-control font-monospace" value="{{ $secret }}" readonly autocomplete="off" data-asset-copy-fallback-input>
        </label>
        <button type="button" class="btn btn-primary" data-asset-copy-issued>Sao chép khóa</button>
        <a class="btn btn-outline-primary" href="{{ accounting_route('assets.ai.index') }}">Về bản nháp</a>
        <p class="small mt-2 mb-0" role="status" data-asset-copy-status></p>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/company-asset-assistant-keys.js') }}?v={{ filemtime(public_path('js/company-asset-assistant-keys.js')) }}"></script>
@endpush
