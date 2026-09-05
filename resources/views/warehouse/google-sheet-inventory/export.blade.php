@extends('layouts.warehouse')

@section('title', 'Ghi tồn kho Google Sheet')

@push('styles')
<style>
    .sheet-export-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.07)}
    .sheet-export-summary{border-radius:12px;background:linear-gradient(135deg,#eff6ff,#eef2ff);border:1px solid #bfdbfe}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1"><i class="bi bi-cloud-upload text-primary me-2"></i>Ghi tồn kho Google Sheet</h3>
            <div class="text-muted">Ghi tồn cuối theo ngày ra file đích riêng và lưu lại lịch sử thực hiện.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('warehouse.google-sheet-inventory.index', ['date' => $selectedDate, 'warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-success"><i class="bi bi-cloud-download me-1"></i>Sang trang Load tồn kho</a>
            <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Về Dashboard kho</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if(auth()->user()?->isAdmin() && !auth()->user()?->warehouse_id)
        <div class="card sheet-export-card mb-3"><div class="card-body">
            <form method="GET" action="{{ route('warehouse.google-sheet-inventory.export.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5"><label class="form-label">Kho cần ghi tồn</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $warehouseOption)<option value="{{ $warehouseOption->id }}" @selected($warehouseOption->id === $warehouse->id)>{{ $warehouseOption->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Ngày</label><input type="date" name="date" class="form-control" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" required></div>
                <div class="col-md-3"><button class="btn btn-outline-primary w-100">Chọn kho</button></div>
            </form>
        </div></div>
    @endif

    <div class="card sheet-export-card mb-3 border border-primary-subtle"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h5 class="mb-1"><i class="bi bi-link-45deg text-primary me-1"></i>File đích ghi tồn kho</h5>
                <div class="small text-muted">Lưu riêng cho {{ $warehouse->name }}, không dùng chung với file nguồn Load tồn kho.</div>
                @if($serviceAccountEmail)<div class="small text-muted mt-1">Bắt buộc chia sẻ quyền <strong>Người chỉnh sửa</strong> cho: <code>{{ $serviceAccountEmail }}</code></div>@endif
            </div>
            @if($sheetConfiguration['spreadsheet_url'])<a href="{{ $sheetConfiguration['spreadsheet_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Mở file đích <i class="bi bi-box-arrow-up-right ms-1"></i></a>@endif
        </div>
        <form method="POST" action="{{ route('warehouse.google-sheet-inventory.export.configuration') }}" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <div class="col-lg-7"><label class="form-label">Link file đích hoặc Spreadsheet ID</label><input type="text" name="spreadsheet_source" class="form-control" maxlength="500" required value="{{ old('spreadsheet_source', $sheetConfiguration['spreadsheet_id']) }}" placeholder="https://docs.google.com/spreadsheets/d/... hoặc Spreadsheet ID"></div>
            <div class="col-lg-3 col-md-6"><label class="form-label">ID trang tính (gid)</label><input type="number" name="sheet_id" class="form-control" min="0" required value="{{ old('sheet_id', $sheetConfiguration['sheet_id']) }}" placeholder="Ví dụ: 943551638"></div>
            <div class="col-lg-2 col-md-6"><button class="btn btn-primary w-100"><i class="bi bi-floppy me-1"></i>Lưu file đích</button></div>
        </form>
    </div></div>

    <div class="sheet-export-summary p-3 mb-3">
        <form method="GET" action="{{ route('warehouse.google-sheet-inventory.export.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <div class="col-lg-7"><h5 class="mb-1">Tra soát tồn kho theo ngày</h5><div class="small text-muted">Chọn ngày để xem tồn cuối của {{ $warehouse->name }} trước khi ghi lên Google Sheet.</div></div>
            <div class="col-lg-3 col-md-6"><label class="form-label" for="inventoryPreviewDate">Ngày ghi tồn</label><input id="inventoryPreviewDate" type="date" name="date" class="form-control" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" required onchange="if (this.form.reportValidity()) this.form.requestSubmit();"></div>
            <div class="col-lg-2 col-md-6"><button class="btn btn-outline-primary w-100">Xem tồn kho</button></div>
        </form>
    </div>

    <div class="card sheet-export-card mb-3">
        <div class="card-body border-bottom">
            <h5>Tồn cuối ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} — {{ $warehouse->name }}</h5>
            <div class="small text-muted">{{ $inventoryPreview->count() }} sản phẩm/SKU. Tồn cuối được tính từ số tồn hiện tại và hoàn tác các biến động sau ngày đã chọn. Khi ghi, hệ thống chỉ cập nhật sản phẩm khớp trên file đích.</div>
        </div>
        <div class="table-responsive" style="max-height: 520px; overflow: auto;">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light" style="position: sticky; top: 0;"><tr><th>Sản phẩm</th><th>SKU</th><th>Size</th><th>Tên tồn kho</th><th class="text-end">Tồn cuối</th></tr></thead>
                <tbody>
                    @forelse($inventoryPreview as $row)
                        <tr><td>{{ $row['product_name'] }}</td><td>{{ $row['sku'] ?: '—' }}</td><td>{{ $row['size'] ?: '—' }}</td><td>{{ $row['inventory_name'] ?: '—' }}</td><td class="text-end fw-bold {{ $row['closing'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['closing'], 3, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu tồn kho để tra soát.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div class="small text-muted">Số liệu được tính lại khi ghi nếu có biến động kho mới.</div>
            <form method="POST" action="{{ route('warehouse.google-sheet-inventory.export.write-daily') }}" onsubmit="return confirm('Ghi tồn kho ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} lên file đích? Các ô tại cột Tồn tương ứng sẽ được cập nhật.');">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <input type="hidden" name="confirm_write" value="1">
                <button class="btn btn-primary" @disabled(!$sheetConfiguration['spreadsheet_url'] || $inventoryPreview->isEmpty())><i class="bi bi-cloud-upload me-1"></i>Ghi tồn kho ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</button>
            </form>
        </div>
    </div>

    <div class="card sheet-export-card"><div class="card-body p-0">
        <div class="p-3 border-bottom"><h5 class="mb-1"><i class="bi bi-clock-history me-1"></i>Lịch sử ghi tồn kho</h5><div class="small text-muted">30 lần ghi gần nhất của {{ $warehouse->name }}.</div></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Thời gian ghi</th><th>Ngày tồn</th><th>Trang tính</th><th class="text-end">Số dòng</th><th>Người thực hiện</th><th></th></tr></thead>
            <tbody>
            @forelse($writeHistory as $history)
                <tr>
                    <td>{{ optional($history->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ optional($history->inventory_date)->format('d/m/Y') }}</td>
                    <td>{{ $history->sheet_name }} <span class="small text-muted">(gid={{ $history->sheet_id }})</span></td>
                    <td class="text-end fw-semibold">{{ number_format($history->written_rows_count, 0, ',', '.') }}</td>
                    <td>{{ $history->creator?->name ?? '—' }}</td>
                    <td class="text-end"><a href="https://docs.google.com/spreadsheets/d/{{ $history->spreadsheet_id }}/edit?gid={{ $history->sheet_id }}#gid={{ $history->sheet_id }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Mở file</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có lần ghi tồn kho nào.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div></div>
</div>
@endsection
