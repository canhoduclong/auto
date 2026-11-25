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
                <a href="{{ route('asset-assistant.schema') }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Mở cấu hình OpenAPI</a>
            </div>
            <p class="small text-muted">Khóa cấp quyền tìm hồ sơ và lưu bản nháp. Nhập khóa trong cấu hình Actions của GPT nội bộ.</p>
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
                            <form method="POST" action="{{ accounting_route('assets.ai.keys.revoke', ['key' => $key->id]) }}" onsubmit="return confirm('Thu hồi khóa kết nối này?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Thu hồi</button>
                            </form>
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
