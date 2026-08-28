@extends('layouts.warehouse')

@section('title', 'Load tồn kho Google Sheet')

@push('styles')
<style>
    .sheet-import-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.07)}
    .sheet-import-table th{white-space:nowrap;font-size:.76rem;text-transform:uppercase;color:#64748b;background:#f8fafc}
    .sheet-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:700}
    .sheet-summary{border-radius:12px;background:linear-gradient(135deg,#ecfdf5,#f0fdf4);border:1px solid #bbf7d0}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h3 class="mb-1"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Load tồn kho Google Sheet</h3><div class="text-muted">Đọc cột <strong>Tồn</strong> theo ngày, kiểm tra ánh xạ rồi tạo phiếu nhập kho.</div></div>
        <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Về Dashboard kho</a>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card sheet-import-card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('warehouse.google-sheet-inventory.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Ngày lấy tồn kho</label><input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required></div>
            @if(auth()->user()?->isAdmin() && !auth()->user()?->warehouse_id)<div class="col-md-4"><label class="form-label">Kho nhận dữ liệu</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $warehouseOption)<option value="{{ $warehouseOption->id }}" @selected($warehouseOption->id === $warehouse->id)>{{ $warehouseOption->name }}</option>@endforeach</select></div>@endif
            <div class="col-md-4"><button class="btn btn-success"><i class="bi bi-cloud-download me-1"></i>Load và xem trước</button></div>
        </form>
    </div></div>

    @if($loadError)
        <div class="alert alert-danger"><strong>Không tải được dữ liệu.</strong><div class="mt-1">{{ $loadError }}</div></div>
    @elseif($preview)
        <div class="sheet-summary p-3 mb-3 d-flex flex-wrap justify-content-between gap-3">
            <div><div class="small text-muted">Nguồn dữ liệu</div><strong>{{ $preview['sheet_name'] }}</strong> · {{ $preview['warehouse_section_label'] }} · cột {{ $preview['stock_column'] }}<div><a href="{{ $preview['spreadsheet_url'] }}" target="_blank" rel="noopener" class="small">Mở Google Sheet <i class="bi bi-box-arrow-up-right"></i></a></div></div>
            <div><div class="small text-muted">Ngày tồn</div><strong>{{ \Carbon\Carbon::parse($preview['selected_date'])->format('d/m/Y') }}</strong></div>
            <div><div class="small text-muted">SKU sẽ nhập</div><strong>{{ $preview['import_rows']->count() }}</strong></div>
            <div class="text-end"><div class="small text-muted">Tổng số lượng sẽ nhập</div><strong class="fs-3 text-success">{{ number_format($preview['total_quantity'], 0, ',', '.') }}</strong></div>
        </div>

        @if($existingDocuments->isNotEmpty())
            <div class="alert alert-warning">
                <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Tồn kho ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} đã được nhập {{ $existingDocuments->count() }} lần vào {{ $warehouse->name }}.</div>
                <div class="d-flex flex-wrap gap-2">@foreach($existingDocuments as $importedDocument)<a href="{{ route('warehouse.stock-in.show', $importedDocument) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-receipt me-1"></i>{{ $importedDocument->document_number }}</a>@endforeach</div>
            </div>
        @endif
        @if($preview['has_blocking_errors'])
            <div class="alert alert-danger"><strong>Có mã tồn chưa ánh xạ được.</strong> Hệ thống sẽ không tự đoán biến thể. Hãy tạo/điều chỉnh biến thể hoặc xác nhận bỏ qua rõ ràng ở cuối trang.</div>
        @endif

        <div class="card sheet-import-card mb-3"><div class="table-responsive"><table class="table table-hover align-middle mb-0 sheet-import-table">
            <thead><tr><th>Dòng sheet</th><th>Mã Sheet</th><th>Ánh xạ hệ thống</th><th class="text-end">Tồn Sheet</th><th class="text-end">Tồn hiện tại</th><th class="text-end">Sau khi nhập</th><th>Trạng thái</th></tr></thead>
            <tbody>@foreach($preview['rows'] as $row)<tr class="{{ !$row['matched'] && $row['quantity'] > 0 ? 'table-danger' : ($row['quantity'] > 0 ? '' : 'text-muted') }}">
                <td>{{ $row['sheet_row'] }}</td><td><span class="sheet-code">{{ $row['sheet_code'] }}</span><div class="small text-muted">{{ $row['normalized_code'] }}</div></td><td>@if($row['matched'])<strong>{{ $row['variant_name'] }}</strong><div class="small text-muted">{{ $row['variant_sku'] }}</div>@else<span class="text-danger">Chưa có biến thể tương ứng</span>@endif</td>
                <td class="text-end fw-semibold">{{ number_format($row['quantity'], 0, ',', '.') }}</td><td class="text-end">{{ $row['current_quantity'] === null ? '—' : number_format($row['current_quantity'], 0, ',', '.') }}</td><td class="text-end fw-semibold">{{ $row['projected_quantity'] === null ? '—' : number_format($row['projected_quantity'], 0, ',', '.') }}</td><td>@if(!$row['matched'] && $row['quantity'] > 0)<span class="badge bg-danger">Cần xử lý</span>@if($row['match_error'])<div class="small text-danger mt-1">{{ $row['match_error'] }}</div>@endif @elseif($row['quantity'] > 0)<span class="badge bg-success">Sẵn sàng nhập</span><div class="small text-muted mt-1">{{ $row['match_method'] === 'inventory_name' ? 'Khớp Tên tồn kho' : 'Suy luận từ size/SKU' }}</div>@elseif($row['matched'])<span class="badge bg-light text-muted border">Không có tồn</span>@else<span class="badge bg-light text-muted border">Không phát sinh</span>@endif</td>
            </tr>@endforeach</tbody>
        </table></div></div>

        <div class="card sheet-import-card"><div class="card-body">
            <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>Thao tác này tạo <strong>phiếu nhập kho</strong> với ngày chứng từ {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} và cộng số lượng cột Tồn vào tồn hiện tại của <strong>{{ $warehouse->name }}</strong>.</div>
            <form method="POST" action="{{ route('warehouse.google-sheet-inventory.store') }}" onsubmit="return confirm('Xác nhận nhập tồn Google Sheet ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} và cộng {{ number_format($preview['total_quantity'], 0, ',', '.') }} vào {{ $warehouse->name }}?')">@csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}"><input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                @if($preview['has_blocking_errors'])<label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="ignore_unmatched" value="1"><span class="form-check-label text-danger">Tôi xác nhận bỏ qua {{ $preview['unmatched_positive_rows']->count() }} mã chưa ánh xạ và chỉ nhập các mã hợp lệ.</span></label>@endif
                @if($existingDocuments->isNotEmpty())<label class="form-check mb-3 p-3 border border-warning rounded bg-warning-subtle"><input class="form-check-input" type="checkbox" name="allow_duplicate" value="1" required><span class="form-check-label text-danger fw-semibold">Tôi xác nhận tồn ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} đã được nhập {{ $existingDocuments->count() }} lần và vẫn muốn tiếp tục nhập thêm lần nữa.</span></label>@endif
                <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm_import" value="1" required><span class="form-check-label">Tôi đã kiểm tra ngày, kho, mã biến thể và số lượng nhập.</span></label>
                <button class="btn btn-success btn-lg" @disabled($preview['import_rows']->isEmpty())><i class="bi bi-box-arrow-in-down me-1"></i>Thực hiện nhập kho</button>
                @if($existingDocuments->isNotEmpty())<div class="text-warning-emphasis small fw-semibold mt-2"><i class="bi bi-info-circle me-1"></i>Nếu tiếp tục, đây sẽ là lần nhập thứ {{ $existingDocuments->count() + 1 }} cho đúng ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}.</div>@endif
            </form>
        </div></div>
    @endif
</div>
@endsection
