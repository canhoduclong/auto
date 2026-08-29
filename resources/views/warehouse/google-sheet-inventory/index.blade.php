@extends('layouts.warehouse')

@section('title', 'Load tồn kho Google Sheet')

@push('styles')
<style>
    .sheet-import-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.07)}
    .sheet-import-table th{white-space:nowrap;font-size:.76rem;text-transform:uppercase;color:#64748b;background:#f8fafc}
    .sheet-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:700}
    .sheet-summary{border-radius:12px;background:linear-gradient(135deg,#ecfdf5,#f0fdf4);border:1px solid #bbf7d0}
    .change-stat{min-width:130px;border-radius:12px;padding:.75rem 1rem;background:#fff;border:1px solid #e2e8f0}
    .delta-positive{color:#047857}.delta-negative{color:#b91c1c}.delta-zero{color:#64748b}
    .sync-product-row.is-disabled{opacity:.62;background:#f8fafc}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Load tồn kho Google Sheet</h3>
            <div class="text-muted">So sánh với lần đồng bộ gần nhất, sau đó chọn sản phẩm cần nhập thêm hoặc điều chỉnh giảm.</div>
        </div>
        <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Về Dashboard kho</a>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card sheet-import-card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('warehouse.google-sheet-inventory.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Ngày lấy tồn kho</label><input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required></div>
            @if(auth()->user()?->isAdmin() && !auth()->user()?->warehouse_id)
                <div class="col-md-4"><label class="form-label">Kho nhận dữ liệu</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $warehouseOption)<option value="{{ $warehouseOption->id }}" @selected($warehouseOption->id === $warehouse->id)>{{ $warehouseOption->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-4"><button class="btn btn-success"><i class="bi bi-cloud-download me-1"></i>Load và so sánh</button></div>
        </form>
    </div></div>

    @if(auth()->user()?->isAdmin())
        <div class="card sheet-import-card mb-3 border border-danger-subtle"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="text-danger mb-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Admin · Reset dữ liệu theo khoảng ngày</h5>
                    <div class="text-muted small">Hoàn tác các lần đồng bộ Google Sheet trong khoảng được chọn. Chứng từ và lịch sử vẫn được giữ để kiểm tra; sau đó có thể load và nhập lại theo thứ tự ngày cũ đến ngày mới.</div>
                </div>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#sheet-reset-range" aria-expanded="false" aria-controls="sheet-reset-range">Mở chức năng reset</button>
            </div>
            <div class="collapse mt-3" id="sheet-reset-range">
                <form method="POST" action="{{ route('warehouse.google-sheet-inventory.reset') }}" onsubmit="return confirm('Xác nhận hoàn tác tồn kho Google Sheet trong toàn bộ khoảng ngày đã chọn?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="from_date" class="form-control" value="{{ old('from_date', $selectedDate) }}" required></div>
                        <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="to_date" class="form-control" value="{{ old('to_date', $selectedDate) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Lý do reset</label><input type="text" name="reset_reason" class="form-control" maxlength="500" value="{{ old('reset_reason') }}" placeholder="Ví dụ: Nhập sai số liệu ngày chốt"></div>
                        <div class="col-12">
                            <label class="form-check p-3 rounded border border-danger-subtle bg-danger-subtle">
                                <input class="form-check-input" type="checkbox" name="confirm_reset" value="1" required>
                                <span class="form-check-label text-danger">Tôi hiểu hệ thống sẽ hoàn tác ảnh hưởng tồn kho của tất cả lần đồng bộ trong khoảng ngày này.</span>
                            </label>
                        </div>
                        <div class="col-12"><button class="btn btn-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset dữ liệu trong khoảng ngày</button></div>
                    </div>
                </form>
            </div>
        </div></div>
    @endif

    @if($loadError)
        <div class="alert alert-danger"><strong>Không tải được dữ liệu.</strong><div class="mt-1">{{ $loadError }}</div></div>
    @elseif($preview && $comparison)
        <div class="sheet-summary p-3 mb-3 d-flex flex-wrap justify-content-between gap-3">
            <div><div class="small text-muted">Nguồn dữ liệu</div><strong>{{ $preview['sheet_name'] }}</strong> · {{ $preview['warehouse_section_label'] }} · cột {{ $preview['stock_column'] }}<div><a href="{{ $preview['spreadsheet_url'] }}" target="_blank" rel="noopener" class="small">Mở Google Sheet <i class="bi bi-box-arrow-up-right"></i></a></div></div>
            <div><div class="small text-muted">Ngày tồn</div><strong>{{ \Carbon\Carbon::parse($preview['selected_date'])->format('d/m/Y') }}</strong></div>
            <div><div class="small text-muted">Kho áp dụng</div><strong>{{ $warehouse->name }}</strong></div>
            <div class="text-end"><div class="small text-muted">Lần đồng bộ kế tiếp</div><strong class="fs-3 text-success">#{{ $comparison['next_sync_number'] }}</strong></div>
        </div>

        @if($comparison['has_previous'])
            <div class="alert alert-warning">
                <div class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} đã được xử lý trước đó.</div>
                <div class="small mt-1">Số lượng dưới đây là chênh lệch so với dữ liệu đã áp dụng gần nhất; hệ thống không cộng lại toàn bộ tồn Sheet.</div>
            </div>
        @else
            <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Đây là lần đầu xử lý ngày này. Các sản phẩm có tồn sẽ được xem là hàng mới cần nhập.</div>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="change-stat"><div class="small text-muted">Sản phẩm mới</div><strong class="fs-5 text-primary">{{ $comparison['new_count'] }}</strong></div>
            <div class="change-stat"><div class="small text-muted">Tăng tồn</div><strong class="fs-5 text-success">{{ $comparison['increase_count'] }}</strong></div>
            <div class="change-stat"><div class="small text-muted">Giảm tồn</div><strong class="fs-5 text-danger">{{ $comparison['decrease_count'] }}</strong></div>
            <div class="change-stat"><div class="small text-muted">Không đổi</div><strong class="fs-5 text-secondary">{{ $comparison['unchanged_count'] }}</strong></div>
            <div class="change-stat"><div class="small text-muted">Dự kiến nhập thêm</div><strong class="fs-5 text-success">+{{ number_format($comparison['positive_delta'], 0, ',', '.') }}</strong></div>
            <div class="change-stat"><div class="small text-muted">Dự kiến điều chỉnh</div><strong class="fs-5 text-danger">-{{ number_format($comparison['negative_delta'], 0, ',', '.') }}</strong></div>
        </div>

        @if($preview['has_blocking_errors'])
            <div class="alert alert-danger"><strong>Có mã tồn chưa ánh xạ được.</strong> Hệ thống không tự đoán biến thể. Hãy sửa Tên tồn kho của biến thể hoặc xác nhận bỏ qua trong cửa sổ thực hiện.</div>
        @endif

        <div class="card sheet-import-card mb-3"><div class="table-responsive"><table class="table table-hover align-middle mb-0 sheet-import-table">
            <thead><tr><th>Dòng</th><th>Mã Sheet</th><th>Sản phẩm hệ thống</th><th class="text-end">Lần trước</th><th class="text-end">Sheet hiện tại</th><th class="text-end">Chênh lệch</th><th class="text-end">Tồn hệ thống</th><th class="text-end">Sau áp dụng</th><th>Trạng thái</th></tr></thead>
            <tbody>
            @foreach($preview['rows'] as $row)
                @php($isProblem = !$row['matched'] && $row['quantity'] > 0)
                <tr class="{{ $isProblem ? 'table-danger' : ($row['change_type'] === 'unchanged' ? 'text-muted' : '') }}">
                    <td>{{ $row['sheet_row'] }}</td>
                    <td><span class="sheet-code">{{ $row['sheet_code'] }}</span><div class="small text-muted">{{ $row['normalized_code'] }}</div></td>
                    <td>@if($row['matched'])<strong>{{ $row['variant_name'] }}</strong><div class="small text-muted">{{ $row['variant_sku'] }}</div>@else<span class="text-danger">Chưa có biến thể tương ứng</span>@endif</td>
                    <td class="text-end">{{ $row['matched'] ? number_format($row['previous_sheet_quantity'], 0, ',', '.') : '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                    <td class="text-end fw-bold {{ $row['delta'] > 0 ? 'delta-positive' : ($row['delta'] < 0 ? 'delta-negative' : 'delta-zero') }}">@if($row['matched']){{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 0, ',', '.') }}@else—@endif</td>
                    <td class="text-end">{{ $row['matched'] ? number_format($row['current_quantity'], 0, ',', '.') : '—' }}</td>
                    <td class="text-end fw-semibold">{{ $row['matched'] ? number_format($row['projected_quantity'], 0, ',', '.') : '—' }}</td>
                    <td>
                        @if($isProblem)<span class="badge bg-danger">Cần ánh xạ</span>@if($row['match_error'])<div class="small text-danger mt-1">{{ $row['match_error'] }}</div>@endif
                        @elseif(!$row['matched'])<span class="badge bg-light text-muted border">Không phát sinh</span>
                        @elseif($row['change_type'] === 'new')<span class="badge bg-primary">Sản phẩm mới</span>
                        @elseif($row['change_type'] === 'increase')<span class="badge bg-success">Tăng tồn</span>
                        @elseif($row['change_type'] === 'decrease')<span class="badge bg-danger">Giảm tồn</span>
                        @else<span class="badge bg-light text-muted border">Không thay đổi</span>@endif
                        @if($row['apply_error'])<div class="small text-danger mt-1">{{ $row['apply_error'] }}</div>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div></div>

        <div class="card sheet-import-card mb-3"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div><div class="fw-semibold">Có {{ $comparison['changed_rows']->count() }} sản phẩm khác lần áp dụng trước.</div><div class="small text-muted">Mở quản trị để chọn riêng hàng mới/tăng tồn và hàng cần điều chỉnh giảm.</div></div>
            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#sheetSyncModal" @disabled($comparison['applicable_rows']->isEmpty())><i class="bi bi-ui-checks-grid me-1"></i>Quản trị sản phẩm & Thực hiện nhập kho</button>
        </div></div>

        @if($comparison['syncs']->isNotEmpty() || $comparison['legacy_documents']->isNotEmpty())
            <div class="card sheet-import-card mb-3"><div class="card-body">
                <h6 class="mb-3"><i class="bi bi-clock-history me-1"></i>Lịch sử xử lý đúng ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($comparison['syncs']->sortByDesc('id') as $historySync)
                        <span class="badge {{ $historySync->status === 'reset' ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-light text-dark border' }} p-2">Lần #{{ $historySync->sync_number }} · {{ $historySync->applied_rows_count }} dòng · +{{ number_format($historySync->total_positive_delta, 0, ',', '.') }} / -{{ number_format($historySync->total_negative_delta, 0, ',', '.') }} · {{ optional($historySync->created_at)->format('H:i d/m/Y') }}{{ $historySync->status === 'reset' ? ' · ĐÃ RESET' : '' }}</span>
                        @if($historySync->importDocument)<a class="btn btn-sm btn-outline-success" href="{{ route('warehouse.stock-in.show', $historySync->importDocument) }}">{{ $historySync->importDocument->document_number }}</a>@endif
                    @endforeach
                    @foreach($comparison['legacy_documents'] as $legacyDocument)<a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $legacyDocument) }}">Phiếu cũ {{ $legacyDocument->document_number }}</a>@endforeach
                </div>
            </div></div>
        @endif

        <div class="modal fade" id="sheetSyncModal" tabindex="-1" aria-labelledby="sheetSyncModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
                <form method="POST" action="{{ route('warehouse.google-sheet-inventory.store') }}" id="sheet-sync-form">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}"><input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <div class="modal-header"><div><h5 class="modal-title" id="sheetSyncModalLabel">Quản trị thay đổi tồn kho</h5><div class="small text-muted">{{ $warehouse->name }} · ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                    <div class="modal-body">
                        @if($comparison['has_previous'])<div class="alert alert-warning"><strong>Đã có lần nhập cho đúng ngày này.</strong> Chỉ các chênh lệch được chọn bên dưới mới được áp dụng. Dòng không chọn sẽ tiếp tục xuất hiện ở lần kiểm tra sau.</div>@endif
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-dark" data-select-sync="all">Chọn tất cả hợp lệ</button><button type="button" class="btn btn-sm btn-outline-success" data-select-sync="positive">Chỉ hàng mới / tăng</button><button type="button" class="btn btn-sm btn-outline-danger" data-select-sync="negative">Chỉ điều chỉnh giảm</button><button type="button" class="btn btn-sm btn-outline-secondary" data-select-sync="none">Bỏ chọn</button>
                        </div>
                        <div class="table-responsive border rounded"><table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th style="width:46px"></th><th>Sản phẩm</th><th class="text-end">Lần trước</th><th class="text-end">Sheet mới</th><th class="text-end">Chênh lệch</th><th>Nghiệp vụ</th></tr></thead>
                            <tbody>
                            @forelse($comparison['changed_rows'] as $row)
                                <tr class="sync-product-row {{ !$row['can_apply'] ? 'is-disabled' : '' }}">
                                    <td><input class="form-check-input sync-row-checkbox" type="checkbox" name="selected_variant_ids[]" value="{{ $row['variant_id'] }}" data-delta="{{ $row['delta'] > 0 ? 'positive' : 'negative' }}" @checked(old('selected_variant_ids') ? in_array($row['variant_id'], array_map('intval', (array) old('selected_variant_ids', [])), true) : $row['can_apply']) @disabled(!$row['can_apply'])></td>
                                    <td><strong>{{ $row['variant_name'] }}</strong><div class="small text-muted">{{ $row['variant_sku'] }} · Sheet: {{ $row['sheet_code'] }}</div></td>
                                    <td class="text-end">{{ number_format($row['previous_sheet_quantity'], 0, ',', '.') }}</td><td class="text-end fw-semibold">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $row['delta'] > 0 ? 'delta-positive' : 'delta-negative' }}">{{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 0, ',', '.') }}</td>
                                    <td>@if($row['delta'] > 0)<span class="badge bg-success">Tạo phiếu nhập +{{ number_format($row['delta'], 0, ',', '.') }}</span>@else<span class="badge bg-danger">Điều chỉnh giảm {{ number_format(abs($row['delta']), 0, ',', '.') }}</span>@endif @if($row['apply_error'])<div class="small text-danger mt-1">{{ $row['apply_error'] }}</div>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Dữ liệu Sheet đang trùng khớp với lần áp dụng trước, không có thay đổi cần xử lý.</td></tr>
                            @endforelse
                            </tbody>
                        </table></div>
                        @if($preview['has_blocking_errors'])<label class="form-check mt-3 p-3 border border-danger rounded bg-danger-subtle"><input class="form-check-input" type="checkbox" name="ignore_unmatched" value="1" @checked(old('ignore_unmatched'))><span class="form-check-label text-danger">Tôi xác nhận bỏ qua {{ $preview['unmatched_positive_rows']->count() }} mã chưa ánh xạ và chỉ xử lý các mã hợp lệ đã chọn.</span></label>@endif
                        <label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="confirm_import" value="1" required @checked(old('confirm_import'))><span class="form-check-label">Tôi đã kiểm tra sản phẩm, số lần trước, số Sheet mới và đồng ý áp dụng các dòng đã chọn.</span></label>
                    </div>
                    <div class="modal-footer"><div class="me-auto small text-muted" id="selected-sync-count"></div><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-success" @disabled($comparison['applicable_rows']->isEmpty()) onclick="return confirm('Xác nhận áp dụng các chênh lệch đã chọn cho ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}?')"><i class="bi bi-check2-circle me-1"></i>Áp dụng các thay đổi đã chọn</button></div>
                </form>
            </div></div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('sheetSyncModal');
    if (!modalElement) return;
    const checkboxes = Array.from(modalElement.querySelectorAll('.sync-row-checkbox:not(:disabled)'));
    const countLabel = document.getElementById('selected-sync-count');
    const updateCount = () => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        const positive = selected.filter((checkbox) => checkbox.dataset.delta === 'positive').length;
        const negative = selected.filter((checkbox) => checkbox.dataset.delta === 'negative').length;
        countLabel.textContent = `Đã chọn ${selected.length} sản phẩm (${positive} nhập thêm, ${negative} điều chỉnh giảm)`;
    };
    modalElement.querySelectorAll('[data-select-sync]').forEach((button) => button.addEventListener('click', function () {
        const mode = button.dataset.selectSync;
        checkboxes.forEach((checkbox) => { checkbox.checked = mode === 'all' || checkbox.dataset.delta === mode; });
        updateCount();
    }));
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
    updateCount();
    @if($errors->any() && old('selected_variant_ids'))
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    @endif
});
</script>
@endpush
