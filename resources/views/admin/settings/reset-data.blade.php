@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 py-lg-4">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
        <div>
            <h4 class="mb-1">Reset Dữ Liệu Hệ Thống</h4>
            <p class="text-muted mb-0">Chỉ xóa dữ liệu trong bảng đã chọn, không xóa cấu trúc bảng.</p>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Về Settings
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            <div class="fw-semibold mb-1">Không thể thực hiện thao tác</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('reset_result'))
        <div class="card border-0 shadow-sm mb-3 border-success">
            <div class="card-header bg-success bg-opacity-10 border-0">
                <strong><i class="bi bi-arrow-clockwise me-1"></i>Kết quả reset dữ liệu</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Bảng</th>
                                <th class="text-end">Số bản ghi đã xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('reset_result', []) as $item)
                                <tr>
                                    <td>{{ $item['table'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((int) ($item['rows'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm border-danger">
        <div class="card-header bg-danger bg-opacity-10 border-0 pt-3 pb-2">
            <h5 class="mb-1 text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Reset số liệu hệ thống</h5>
            <p class="text-muted small mb-0">Chức năng nguy hiểm: chỉ xóa dữ liệu, không xóa cấu trúc bảng. Không thể hoàn tác.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.reset-data') }}" id="resetDataForm" onsubmit="return confirm('Bạn chắc chắn muốn reset dữ liệu đã chọn? Hành động này không thể hoàn tác.');">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Chế độ reset</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="mode-groups" value="groups" {{ old('mode', 'groups') === 'groups' ? 'checked' : '' }}>
                            <label class="form-check-label" for="mode-groups">Theo nhóm dữ liệu</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="mode-tables" value="tables" {{ old('mode') === 'tables' ? 'checked' : '' }}>
                            <label class="form-check-label" for="mode-tables">Chọn bảng thủ công</label>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <label for="reset_key" class="form-label fw-semibold">Key xác nhận</label>
                        <input type="password" class="form-control" id="reset_key" name="key" placeholder="Nhập key admin" required>
                    </div>

                    <div class="col-lg-4">
                        <label for="confirm_text" class="form-label fw-semibold">Nhập RESET để xác nhận</label>
                        <input type="text" class="form-control" id="confirm_text" name="confirm_text" placeholder="RESET" required>
                    </div>
                </div>

                <div class="mt-3" id="groups-panel">
                    <label class="form-label fw-semibold">Chọn nhóm dữ liệu cần reset</label>
                    <div class="row g-2">
                        @foreach(($resetGroups ?? []) as $groupKey => $group)
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <label class="border rounded p-2 d-block h-100">
                                    <input class="form-check-input me-1" type="checkbox" name="groups[]" value="{{ $groupKey }}" {{ in_array($groupKey, old('groups', []), true) ? 'checked' : '' }}>
                                    <span class="fw-semibold">{{ $group['label'] }}</span>
                                    <div class="text-muted" style="font-size:.75rem;">{{ implode(', ', $group['tables']) }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3" id="tables-panel" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                        <label class="form-label fw-semibold mb-0">Chọn bảng muốn làm mới</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAllTables">Chọn tất cả</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAllTables">Bỏ chọn tất cả</button>
                        </div>
                    </div>
                    <div class="border rounded p-2" style="max-height:260px;overflow:auto;">
                        <div class="row g-1">
                            @foreach(($resettableTables ?? []) as $table)
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <label class="small d-flex align-items-center gap-1">
                                        <input class="form-check-input reset-table-checkbox" type="checkbox" name="tables[]" value="{{ $table }}" {{ in_array($table, old('tables', []), true) ? 'checked' : '' }}>
                                        <span>{{ $table }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Thực thi reset dữ liệu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modeGroups = document.getElementById('mode-groups');
    var modeTables = document.getElementById('mode-tables');
    var groupsPanel = document.getElementById('groups-panel');
    var tablesPanel = document.getElementById('tables-panel');
    var btnSelectAllTables = document.getElementById('btnSelectAllTables');
    var btnClearAllTables = document.getElementById('btnClearAllTables');

    function updateResetModePanels() {
        if (!modeGroups || !modeTables || !groupsPanel || !tablesPanel) {
            return;
        }

        if (modeTables.checked) {
            groupsPanel.style.display = 'none';
            tablesPanel.style.display = '';
        } else {
            groupsPanel.style.display = '';
            tablesPanel.style.display = 'none';
        }
    }

    if (modeGroups && modeTables) {
        modeGroups.addEventListener('change', updateResetModePanels);
        modeTables.addEventListener('change', updateResetModePanels);
        updateResetModePanels();
    }

    if (btnSelectAllTables) {
        btnSelectAllTables.addEventListener('click', function () {
            document.querySelectorAll('.reset-table-checkbox').forEach(function (cb) {
                cb.checked = true;
            });
        });
    }

    if (btnClearAllTables) {
        btnClearAllTables.addEventListener('click', function () {
            document.querySelectorAll('.reset-table-checkbox').forEach(function (cb) {
                cb.checked = false;
            });
        });
    }
});
</script>
@endpush
