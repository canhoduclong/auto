@extends(accounting_layout())
@section('title', 'Bản nháp ChatGPT')
@section('accounting_content')
@include('accounting.assets.feedback')
<a class="small d-inline-block mb-3" href="{{ accounting_route('assets.index') }}">← Quản lý tài sản</a>

<section class="card mb-3">
    <div class="card-body">
        <h2 class="h5">Bản nháp hồ sơ</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Bản nháp</th><th scope="col">Thao tác đề xuất</th><th scope="col">Nguồn</th><th scope="col">Ngày nhận</th></tr></thead>
                <tbody>
                @forelse($drafts as $draft)
                    <tr>
                        <td><a href="{{ accounting_route('assets.ai.show', ['draft' => $draft->id]) }}">{{ $draft->title }}</a></td>
                        <td>{{ ['create' => 'Tạo mới', 'update' => 'Cập nhật', 'add_document' => 'Bổ sung chứng từ'][$draft->operation] }}</td>
                        <td>{{ $draft->source_name ?: '—' }}</td>
                        <td>{{ \Carbon\Carbon::parse($draft->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center py-3">Chưa có bản nháp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($drafts->hasPages())<div class="mt-3">{{ $drafts->links() }}</div>@endif
    </div>
</section>

@if(auth()->user()->isAdmin())
    <section class="card">
        <div class="card-body">
            <div class="d-flex gap-2 justify-content-between flex-wrap">
                <h2 class="h5">Khóa kết nối</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-asset-copy-text="{{ route('asset-assistant.schema') }}">Sao chép URL OpenAPI</button>
                    <a href="{{ route('asset-assistant.schema') }}" class="btn btn-sm btn-light" target="_blank" rel="noopener">Xem JSON</a>
                </div>
            </div>
            <details class="mb-3" open>
                <summary class="fw-semibold">Cách kết nối trong ChatGPT</summary>
                <ol class="small mt-2 mb-1">
                    <li>Mở GPT nội bộ trong trình chỉnh sửa, vào <strong>Configure → Actions → Create new action</strong>.</li>
                    <li>Chọn <strong>Import from URL</strong> và dán URL OpenAPI. Bạn cũng có thể dán nội dung JSON vào ô <strong>Schema</strong>.</li>
                    <li>Trong <strong>Authentication</strong>, chọn <strong>API Key → Bearer</strong> và dán khóa kết nối tài sản bên dưới. Dán khóa nguyên vẹn, bắt đầu bằng <code>asset_</code>.</li>
                    <li>Lưu rồi bấm <strong>Test</strong> ở thao tác <strong>searchAssetRecords</strong> để thử tìm một mã tài sản.</li>
                </ol>
                <p class="small text-muted mb-0">JSON mô tả các thao tác được phép. Khóa được nhập riêng trong phần xác thực của GPT.</p>
            </details>
            <p class="small" role="status" data-asset-copy-status></p>
            <label class="mb-3" data-asset-copy-fallback hidden>
                <span class="form-label">Sao chép thủ công</span>
                <input class="form-control font-monospace" readonly autocomplete="off">
            </label>
            <form method="POST" action="{{ accounting_route('assets.ai.keys.create') }}" class="d-flex align-items-end gap-2 flex-wrap mb-3">
                @csrf
                <label><span class="form-label">Tên kết nối</span><input class="form-control" name="label" maxlength="100" required placeholder="GPT hồ sơ tài sản"></label>
                <button class="btn btn-primary">Tạo khóa</button>
            </form>
            <ul class="list-group list-group-flush">
                @forelse($keys as $key)
                    <li class="list-group-item d-flex align-items-center justify-content-between gap-2 px-0">
                        <span>{{ $key->label }} @if($key->revoked_at)<span class="small text-muted">· Đã thu hồi</span>@endif</span>
                        @unless($key->revoked_at)
                            <div class="d-flex flex-wrap gap-2">
                                @if($key->copyable)
                                    <form method="POST" action="{{ accounting_route('assets.ai.keys.copy', ['key' => $key->id]) }}" data-asset-copy-key>
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Sao chép khóa</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ accounting_route('assets.ai.keys.rotate', ['key' => $key->id]) }}"
                                        onsubmit="return confirm('Tạo lại khóa này? Khóa cũ sẽ hết hiệu lực và bạn cần cập nhật khóa mới trong GPT.')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Tạo lại để sao chép</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ accounting_route('assets.ai.keys.revoke', ['key' => $key->id]) }}" onsubmit="return confirm('Thu hồi khóa kết nối này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Thu hồi</button>
                                </form>
                            </div>
                        @endunless
                    </li>
                @empty
                    <li class="list-group-item px-0 text-muted">Chưa có khóa kết nối.</li>
                @endforelse
            </ul>
        </div>
    </section>
@endif
@endsection

@push('scripts')
    <script src="{{ asset('js/company-asset-assistant-keys.js') }}?v={{ filemtime(public_path('js/company-asset-assistant-keys.js')) }}"></script>
@endpush
