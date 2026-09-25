@extends('layouts.app')
@push('styles')
<style>
.text-import-card { border: 1px solid #dbe4ef; border-radius: 10px; box-shadow: 0 4px 16px rgba(15,23,42,.05); }
.text-import-card .card-header { background: #f8fafc; border-bottom-color: #e8eef5; }
.text-import-area { min-height: 260px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .84rem; line-height: 1.5; white-space: pre; }
.import-result-table th { white-space: nowrap; font-size: .78rem; }
.import-result-table td { font-size: .82rem; vertical-align: top; }
.status-create { color: #047857; }
.status-update { color: #1d4ed8; }
.status-skip { color: #64748b; }
.status-error { color: #b91c1c; }
</style>
@endpush
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Import khách hàng</h3>
            <div class="text-muted">Dán trực tiếp từ Excel/Google Sheets hoặc tải lên file Excel.</div>
        </div>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if(isset($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif

    <div class="card text-import-card mb-4">
        <div class="card-header py-3">
            <h5 class="mb-1">Nhập bằng văn bản</h5>
            <div class="text-muted small">Cột hỗ trợ: <b>Mã KH, Khách Hàng, NVKD, SĐT, Địa Chỉ</b>. Dòng thiếu Mã KH hoặc Địa Chỉ vẫn được nhận.</div>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small">
                Hệ thống tự bỏ dấu chấm/khoảng trắng trong SĐT và thêm số 0 đầu cho số Việt Nam bị Excel làm mất.
                Khách trùng mã hoặc tên chỉ được bổ sung trường còn thiếu; dữ liệu hiện tại không bị ghi đè.
            </div>
            <form action="{{ route('customers.import') }}" method="POST">
                @csrf
                <input type="hidden" name="import_source" value="text">
                <textarea name="text_data" class="form-control text-import-area @error('text_data') is-invalid @enderror"
                    placeholder="Mã KH&#9;Khách Hàng&#9;NVKD&#9;SĐT&#9;Địa Chỉ&#10;8999&#9;Lan Hà&#9;Huy&#9;72.768.999&#9;">{{ $textData ?? '' }}</textarea>

                @if(auth()->user()?->isAdmin() && !empty($textImportResult['sale_mappings'] ?? []))
                    <div class="border rounded mt-3 overflow-hidden">
                        <div class="bg-light px-3 py-2 border-bottom">
                            <b>Ánh xạ tên ngắn NVKD với nhân viên hệ thống</b>
                            <div class="text-muted small">Chọn đúng tài khoản cho từng tên trong dữ liệu import, sau đó bấm “Kiểm tra dữ liệu” lại.</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr><th class="ps-3">Tên trong dữ liệu cũ</th><th>Nhân viên hệ thống</th><th>Trạng thái</th></tr>
                                </thead>
                                <tbody>
                                @foreach($textImportResult['sale_mappings'] as $mapping)
                                    <tr>
                                        <td class="ps-3"><b>{{ $mapping['imported_name'] }}</b></td>
                                        <td style="min-width:320px">
                                            <select name="sale_mapping[{{ $mapping['key'] }}]" class="form-select form-select-sm">
                                                <option value="">-- Chọn nhân viên --</option>
                                                @foreach($salesUsers as $saleUser)
                                                    <option value="{{ $saleUser->id }}" @selected((int) ($mapping['selected_user_id'] ?? 0) === (int) $saleUser->id)>
                                                        {{ $saleUser->short_name ? $saleUser->short_name.' — ' : '' }}{{ $saleUser->name }}{{ $saleUser->email ? ' ('.$saleUser->email.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @if($mapping['selected_user_id'])
                                                <span class="badge bg-success">Đã gắn: {{ $mapping['selected_user_name'] }}</span>
                                                @if($mapping['automatic_user_id'])
                                                    <div class="text-muted small mt-1">Tự nhận diện theo tên/tên ngắn</div>
                                                @endif
                                            @else
                                                <span class="badge bg-danger">Chưa gắn</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" name="text_action" value="preview" class="btn btn-outline-primary">
                        <i class="ph ph-eye me-1"></i>Kiểm tra dữ liệu
                    </button>
                    @if(isset($textImportResult) && ($textImportResult['counts']['error'] ?? 0) === 0 && !($textImportResult['imported'] ?? false))
                        <button type="submit" name="text_action" value="import" class="btn btn-success"
                            onclick="return confirm('Xác nhận cập nhật dữ liệu khách hàng?')">
                            <i class="ph ph-upload-simple me-1"></i>Import {{ count($textImportResult['rows']) }} dòng
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(isset($textImportResult) && count($textImportResult['rows'] ?? []))
        @php
            $counts = $textImportResult['counts'] ?? [];
        @endphp
        <div class="card text-import-card mb-4">
            <div class="card-header d-flex flex-wrap align-items-center gap-2 py-3">
                <h5 class="mb-0 me-2">Kết quả {{ ($textImportResult['imported'] ?? false) ? 'import' : 'kiểm tra' }}</h5>
                <span class="badge bg-success">Thêm mới: {{ $counts['create'] ?? 0 }}</span>
                <span class="badge bg-primary">Bổ sung: {{ $counts['update'] ?? 0 }}</span>
                <span class="badge bg-secondary">Bỏ qua: {{ $counts['skip'] ?? 0 }}</span>
                <span class="badge bg-danger">Lỗi: {{ $counts['error'] ?? 0 }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 import-result-table">
                    <thead class="table-light">
                        <tr>
                            <th>Dòng</th><th>Mã KH</th><th>Khách hàng</th><th>NVKD</th><th>SĐT</th><th>Địa chỉ</th><th>Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($textImportResult['rows'] as $row)
                        @php
                            $labels = ['create' => 'Thêm mới', 'update' => 'Bổ sung', 'skip' => 'Không thay đổi', 'error' => 'Có lỗi'];
                            $data = $row['data'];
                        @endphp
                        <tr>
                            <td>{{ $row['line'] }}</td>
                            <td>{{ $data['customer_code'] ?: '—' }}</td>
                            <td><b>{{ $data['name'] ?: '—' }}</b>@if($row['existing_id'])<div class="text-muted">ID hiện có: {{ $row['existing_id'] }}</div>@endif</td>
                            <td>{{ $row['sale_user_name'] ?: ($data['sale'] ?: '—') }}</td>
                            <td>{{ $data['phone'] ?: '—' }}</td>
                            <td>{{ $data['address'] ?: '—' }}</td>
                            <td class="status-{{ $row['action'] }}">
                                <b>{{ $labels[$row['action']] ?? $row['action'] }}</b>
                                @foreach($row['errors'] as $message)<div>{{ $message }}</div>@endforeach
                                @foreach($row['warnings'] as $message)<div class="text-warning">{{ $message }}</div>@endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card text-import-card">
        <div class="card-header py-3"><h5 class="mb-0">Hoặc import file Excel</h5></div>
        <div class="card-body">
            <div class="text-muted small mb-3">
                File Excel dùng các cột name, phone, address, email, delivery_time, size, production.
                <a href="/sample/customer_import_sample.xlsx" target="_blank">Tải file mẫu</a>
            </div>
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
                <button class="btn btn-primary text-nowrap">{{ __('customers.index.import_excel') }}</button>
            </form>
        </div>
    </div>

    @if(isset($import_failures) && count($import_failures))
        <div class="alert alert-danger mt-4">
            <strong>{{ __('customers.import.errors_title') }}</strong>
            <ul>
                @foreach($import_failures as $err)
                    <li>
                        <b>{{ __('customers.import.row') }}:</b> {{ $err['row'] }} | <b>{{ __('customers.import.column') }}:</b> {{ $err['attribute'] }}<br>
                        <b>{{ __('customers.import.error') }}:</b> {{ implode('; ', $err['errors']) }}<br>
                        <b>{{ __('customers.import.value') }}:</b> {{ json_encode($err['values']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
