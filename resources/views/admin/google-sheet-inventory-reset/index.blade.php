@extends('layouts.admin')

@section('title', 'Reset tồn kho Google Sheet')

@section('content')
<div class="container-fluid py-3 py-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="mb-1"><i class="ph-arrow-counter-clockwise me-2 text-danger"></i>Reset dữ liệu tồn kho Google Sheet</h3>
            <div class="text-muted">Chức năng quản trị dành riêng cho Admin, không thực hiện trong màn hình nghiệp vụ kho.</div>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class="ph-arrow-left me-1"></i>Về Dashboard Admin</a>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="ph-check-circle me-1"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Không thể thực hiện reset</div>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" action="{{ route('admin.google-sheet-inventory-reset.index') }}" class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label">Kho cần kiểm tra</label>
                <select name="warehouse_id" class="form-select" required>
                    @foreach($warehouses as $warehouseOption)
                        <option value="{{ $warehouseOption->id }}" @selected($warehouseOption->id === $warehouse->id)>{{ $warehouseOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4"><label class="form-label">Từ ngày</label><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required></div>
            <div class="col-lg-2 col-md-4"><label class="form-label">Đến ngày</label><input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required></div>
            <div class="col-lg-3 col-md-4"><button class="btn btn-primary w-100"><i class="ph-magnifying-glass me-1"></i>Kiểm tra dữ liệu</button></div>
        </form>
    </div></div>

    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Kho áp dụng</div><div class="fs-4 fw-semibold">{{ $warehouse->name }}</div></div></div></div>
        <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Lần đồng bộ có thể reset trong khoảng ngày</div><div class="display-6 fw-bold text-danger">{{ number_format($completedSyncCount, 0, ',', '.') }}</div></div></div></div>
    </div>

    <div class="alert alert-warning">
        <strong><i class="ph-warning me-1"></i>Lưu ý:</strong> Hệ thống sẽ hoàn tác ảnh hưởng tồn kho của các lần đồng bộ Google Sheet đã hoàn thành trong khoảng được chọn. Chứng từ và lịch sử đồng bộ vẫn được giữ để kiểm tra; sau đó kho có thể load và nhập lại theo thứ tự ngày cũ đến ngày mới.
    </div>

    <div class="card border-danger shadow-sm"><div class="card-body">
        <h5 class="text-danger mb-3">Xác nhận reset theo khoảng ngày</h5>
        <form method="POST" action="{{ route('admin.google-sheet-inventory-reset.destroy') }}" onsubmit="return confirm('Xác nhận hoàn tác tồn kho Google Sheet trong toàn bộ khoảng ngày đã chọn?');">
            @csrf
            @method('DELETE')
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Lý do reset</label><input type="text" name="reset_reason" class="form-control" maxlength="500" value="{{ old('reset_reason') }}" placeholder="Ví dụ: Nhập sai số liệu ngày chốt"></div>
                <div class="col-12">
                    <label class="form-check p-3 rounded border border-danger bg-danger-subtle">
                        <input class="form-check-input" type="checkbox" name="confirm_reset" value="1" required>
                        <span class="form-check-label text-danger">Tôi hiểu hệ thống sẽ hoàn tác ảnh hưởng tồn kho của tất cả lần đồng bộ trong khoảng {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }} tại {{ $warehouse->name }}.</span>
                    </label>
                </div>
                <div class="col-12"><button class="btn btn-danger btn-lg" @disabled($completedSyncCount === 0)><i class="ph-arrow-counter-clockwise me-1"></i>Reset dữ liệu trong khoảng ngày</button></div>
            </div>
        </form>
    </div></div>
</div>
@endsection
