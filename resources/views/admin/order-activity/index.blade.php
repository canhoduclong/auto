@extends('layouts.app')

@section('title', 'Export / Import hoạt động đơn hàng')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h3 class="mb-1">Export / Import hoạt động đơn hàng</h3>
        <p class="text-muted mb-0">Sao lưu các mốc lên đơn, đóng hàng, vận chuyển, giao hàng và xác nhận theo ngày.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-database-gear text-primary me-2"></i>Backup toàn bộ hoạt động server</h5>
                    <p class="text-muted small">Gồm đơn hàng, dòng hàng, lịch sử, vận chuyển/điều chuyển, tồn kho, giữ hàng và biến động tồn kho trong phạm vi ngày chọn. Import sang local theo dạng upsert, không chạy lại nghiệp vụ.</p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Ngày hoạt động</label>
                            <input type="date" id="full-backup-date" class="form-control" value="{{ $date }}">
                        </div>
                        <div class="col-md-8 d-flex gap-2 flex-wrap">
                            <a id="full-export-link" href="{{ route('admin.order-activity.export-full', ['date' => $date]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-zip me-1"></i>Export toàn bộ server</a>
                            <form method="POST" action="{{ route('admin.order-activity.import-full') }}" enctype="multipart/form-data" class="d-flex gap-2">
                                @csrf
                                <input type="file" name="file" class="form-control" accept=".json,.txt" required>
                                <button class="btn btn-outline-primary text-nowrap"><i class="bi bi-database-up me-1"></i>Import vào local</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-download text-success me-2"></i>Export hoạt động trong ngày</h5>
                    <p class="text-muted small">File CSV gồm mã đơn, khách hàng, thao tác, người thực hiện, trạng thái trước/sau và thời điểm phát sinh.</p>
                    <form method="GET" action="{{ route('admin.order-activity.export') }}" class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1"><label class="form-label small">Ngày hoạt động</label><input type="date" name="date" class="form-control" value="{{ $date }}" required></div>
                        <button class="btn btn-success" type="submit"><i class="bi bi-file-earmark-arrow-down me-1"></i>Tải CSV</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-upload text-primary me-2"></i>Import hoạt động</h5>
                    <p class="text-muted small">Chỉ phục hồi nhật ký vào đơn đã tồn tại. Dòng trùng được bỏ qua; không tự chạy lại thao tác kho, giao hàng hoặc trừ tồn.</p>
                    <form method="POST" action="{{ route('admin.order-activity.import') }}" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
                        @csrf
                        <div class="flex-grow-1"><label class="form-label small">File CSV đã export</label><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Import</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('full-backup-date')?.addEventListener('change', function () {
    const link = document.getElementById('full-export-link');
    if (!link) return;
    const url = new URL(link.href);
    url.searchParams.set('date', this.value);
    link.href = url.toString();
});
</script>
@endpush
@endsection
