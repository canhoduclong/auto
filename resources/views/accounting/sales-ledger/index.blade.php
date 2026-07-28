@extends('layouts.accounting')

@section('title', 'Sổ doanh số kế toán')
@section('subtitle', 'Import, đối chiếu và quản trị doanh số đã xác nhận')

@section('accounting_content')
<style>
.ledger-card{border:0;border-radius:12px;box-shadow:0 3px 16px rgba(15,23,42,.07)}
.ledger-text{min-height:230px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;white-space:pre}
.ledger-table th{font-size:12px;white-space:nowrap}.ledger-table td{font-size:13px;vertical-align:middle}
.ledger-stat{border-left:4px solid #2563eb}.ledger-stat.amount{border-color:#16a34a}.ledger-stat.qty{border-color:#f59e0b}
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h3 class="mb-1">Sổ doanh số kế toán</h3><div class="text-muted">Dữ liệu lịch sử và đơn hàng đã được kế toán xác nhận doanh thu.</div></div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('accounting.sales-ledger.repair-items') }}">@csrf
                <button class="btn btn-outline-warning" onclick="return confirm('Bổ sung và đồng bộ sản phẩm cho toàn bộ đơn lịch sử HIS-*?')"><i class="bi bi-tools me-1"></i>Sửa sản phẩm đơn HIS</button>
            </form>
            <form method="POST" action="{{ route('accounting.sales-ledger.sync') }}">@csrf
                <button class="btn btn-outline-primary" onclick="return confirm('Đồng bộ toàn bộ đơn đã xác nhận vào sổ doanh số?')"><i class="bi bi-arrow-repeat me-1"></i>Đồng bộ đơn xác nhận</button>
            </form>
            <a class="btn btn-success" href="{{ route('accounting.sales-ledger.export', request()->query()) }}"><i class="bi bi-file-earmark-excel me-1"></i>Xuất Excel</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card ledger-card ledger-stat"><div class="card-body"><div class="text-muted small">Số dòng</div><div class="fs-4 fw-bold">{{ number_format($summary['rows']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card ledger-card ledger-stat qty"><div class="card-body"><div class="text-muted small">Tổng sản lượng</div><div class="fs-4 fw-bold">{{ number_format($summary['quantity'], 1, ',', '.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card ledger-card ledger-stat amount"><div class="card-body"><div class="text-muted small">Doanh số</div><div class="fs-4 fw-bold text-success">{{ number_format($summary['amount'], 0, ',', '.') }} đ</div></div></div></div>
    </div>

    <div class="card ledger-card mb-3">
        <div class="card-header bg-white py-3"><b>Import dữ liệu lịch sử qua văn bản</b><div class="text-muted small">Copy nguyên bảng từ Excel/Google Sheets, bao gồm hàng tiêu đề.</div></div>
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.sales-ledger.import') }}">
                @csrf
                <textarea class="form-control ledger-text" name="text_data" placeholder="Ngày tháng&#9;Tháng&#9;Mã KH&#9;Khách hàng&#9;NVKD&#9;DVT&#9;SL&#9;Kg/con&#9;Tổng&#9;Đơn giá&#9;Tổng tiền">{{ old('text_data', $textData) }}</textarea>

                @if(!empty($importResult['sale_mappings'] ?? []))
                <div class="border rounded mt-3 overflow-hidden">
                    <div class="bg-light px-3 py-2"><b>Ánh xạ tên NVKD cũ</b><div class="small text-muted">Chọn tài khoản hệ thống rồi bấm kiểm tra lại.</div></div>
                    <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
                        <thead><tr><th class="ps-3">Tên dữ liệu cũ</th><th>Nhân viên hệ thống</th><th>Trạng thái</th></tr></thead><tbody>
                        @foreach($importResult['sale_mappings'] as $mapping)
                        <tr><td class="ps-3 fw-semibold">{{ $mapping['imported_name'] }}</td><td>
                            <select class="form-select form-select-sm" name="sale_mapping[{{ $mapping['key'] }}]">
                                <option value="">-- Chọn NVKD --</option>
                                @foreach($salesUsers as $saleUser)
                                <option value="{{ $saleUser->id }}" @selected((int)($mapping['selected_user_id'] ?? 0)===(int)$saleUser->id)>{{ $saleUser->short_name ? $saleUser->short_name.' — ' : '' }}{{ $saleUser->name }}</option>
                                @endforeach
                            </select>
                        </td><td>@if($mapping['selected_user_id'])<span class="badge bg-success">Đã gắn {{ $mapping['selected_user_name'] }}</span>@else<span class="badge bg-danger">Chưa gắn</span>@endif</td></tr>
                        @endforeach
                    </tbody></table></div>
                </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-primary" name="text_action" value="preview"><i class="bi bi-eye me-1"></i>Kiểm tra dữ liệu</button>
                    @if($importResult && ($importResult['counts']['error'] ?? 0) === 0 && !($importResult['imported'] ?? false))
                    <button class="btn btn-success" name="text_action" value="import" onclick="return confirm('Xác nhận ghi nhận các dòng này vào doanh số sale?')"><i class="bi bi-upload me-1"></i>Import {{ count($importResult['rows']) }} dòng</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($importResult && !empty($importResult['rows']))
    <div class="card ledger-card mb-3">
        <div class="card-header bg-white py-3 d-flex gap-2 align-items-center"><b>Kết quả kiểm tra</b>
            <span class="badge bg-dark">Số đơn: {{ $importResult['order_count'] ?? 0 }}</span>
            <span class="badge bg-success">Nhập: {{ $importResult['counts']['import'] ?? 0 }}</span>
            <span class="badge bg-info text-dark">Tạo KH: {{ $importResult['counts']['create_customer'] ?? 0 }}</span>
            <span class="badge bg-danger">Lỗi: {{ $importResult['counts']['error'] ?? 0 }}</span>
        </div>
        <div class="table-responsive"><table class="table table-sm ledger-table mb-0"><thead class="table-light"><tr><th>Dòng</th><th>Ngày</th><th>Mã KH</th><th>Khách hàng</th><th>NVKD</th><th>DVT</th><th>SL</th><th>Kg/con</th><th>Tổng</th><th>Đơn giá</th><th>Tổng tiền</th><th>Kết quả</th></tr></thead><tbody>
            @foreach($importResult['rows'] as $row)
            @php
                $data = $row['data'];
                $actionLabels = ['import'=>'Sẵn sàng', 'create_customer'=>'Tạo KH mới', 'error'=>'Lỗi'];
            @endphp
            <tr><td>{{ $row['line'] }}</td><td>{{ $data['entry_date'] ? \Carbon\Carbon::parse($data['entry_date'])->format('d/m/Y') : '—' }}</td><td>{{ $data['customer_code'] ?: '—' }}</td><td>{{ $data['customer_name'] ?: '—' }}</td><td>{{ $data['sale_name'] ?: '—' }}</td><td>{{ $data['unit'] ?: '—' }}</td><td>{{ $data['quantity'] }}</td><td>{{ $data['unit_weight'] }}</td><td>{{ $data['total_quantity'] }}</td><td>{{ $data['unit_price'] === null ? '—' : number_format($data['unit_price']) }}</td><td>{{ number_format($data['total_amount']) }}</td><td class="{{ $row['action']==='error'?'text-danger':'text-success' }}"><b>{{ $actionLabels[$row['action']] }}</b>@foreach($row['errors'] as $message)<div>{{ $message }}</div>@endforeach @foreach($row['warnings'] as $message)<div class="text-warning">{{ $message }}</div>@endforeach</td></tr>
            @endforeach
        </tbody></table></div>
    </div>
    @endif

    <div class="card ledger-card mb-3">
        <div class="card-body py-3"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label small">Từ ngày</label><input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}"></div>
            <div class="col-md-2"><label class="form-label small">Đến ngày</label><input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}"></div>
            <div class="col-md-2"><label class="form-label small">NVKD</label><select class="form-select" name="sale_id"><option value="">Tất cả</option>@foreach($salesUsers as $saleUser)<option value="{{ $saleUser->id }}" @selected((string)request('sale_id')===(string)$saleUser->id)>{{ $saleUser->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Nguồn</label><select class="form-select" name="source"><option value="">Tất cả</option><option value="import" @selected(request('source')==='import')>Lịch sử import</option><option value="order" @selected(request('source')==='order')>Đơn xác nhận</option></select></div>
            <div class="col-md-3"><label class="form-label small">Khách hàng/Mã KH</label><input class="form-control" name="q" value="{{ request('q') }}"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Lọc</button></div>
        </form></div>
    </div>

    <div class="card ledger-card mb-3"><div class="table-responsive"><table class="table table-hover ledger-table mb-0"><thead class="table-light"><tr><th>Ngày tháng</th><th>Tháng</th><th>Mã KH</th><th>Khách hàng</th><th>NVKD</th><th>DVT</th><th>SL</th><th>Kg/con</th><th>Tổng</th><th>Đơn giá</th><th>Tổng tiền</th><th>Nguồn</th><th></th></tr></thead><tbody>
        @forelse($entries as $entry)
        <tr><td>{{ $entry->entry_date->format('d/m/Y') }}</td><td>{{ $entry->entry_month }}</td><td>{{ $entry->customer_code ?: '—' }}</td><td>{{ $entry->customer_name }}</td><td>{{ $entry->sale_name ?: '—' }}</td><td>{{ $entry->unit }}</td><td>{{ number_format((float)$entry->quantity,1,',','.') }}</td><td>{{ number_format((float)$entry->unit_weight,2,',','.') }}</td><td>{{ number_format((float)$entry->total_quantity,1,',','.') }}</td><td>{{ $entry->unit_price===null?'—':number_format((float)$entry->unit_price,0,',','.') }}</td><td class="fw-semibold {{ $entry->total_amount<0?'text-danger':'' }}">{{ number_format((float)$entry->total_amount,0,',','.') }}</td><td>@if($entry->source==='order')<span class="badge bg-primary">Đơn {{ $entry->order?->code ?: '#'.$entry->order_id }}</span>@else<span class="badge bg-secondary">Import #{{ $entry->import_batch_id }}</span>@endif</td><td class="text-nowrap">@if($entry->source==='import')<a class="btn btn-sm btn-outline-primary" href="{{ route('accounting.sales-ledger.edit',$entry) }}">Sửa</a><form class="d-inline" method="POST" action="{{ route('accounting.sales-ledger.destroy',$entry) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa dòng doanh số này?')">Xóa</button></form>@endif</td></tr>
        @empty<tr><td colspan="13" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer bg-white">{{ $entries->links() }}</div></div>

    @if($batches->isNotEmpty())
    <div class="card ledger-card"><div class="card-header bg-white"><b>10 phiên import gần nhất</b></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Thời gian</th><th>Người nhập</th><th>Số dòng</th><th>Tổng tiền</th><th></th></tr></thead><tbody>@foreach($batches as $batch)<tr><td>{{ $batch->id }}</td><td>{{ $batch->created_at->format('d/m/Y H:i') }}</td><td>{{ $batch->importer?->name ?: '—' }}</td><td>{{ $batch->row_count }}</td><td>{{ number_format((float)$batch->total_amount) }}</td><td><form method="POST" action="{{ route('accounting.sales-ledger.batches.destroy',$batch) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa toàn bộ phiên import #{{ $batch->id }}?')">Xóa phiên</button></form></td></tr>@endforeach</tbody></table></div></div>
    @endif
</div>
@endsection
