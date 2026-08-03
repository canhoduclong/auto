@extends('layouts.accounting')

@section('title', 'Sổ doanh số kế toán')
@section('subtitle', 'Import, đối chiếu và quản trị doanh số đã xác nhận')

@section('accounting_content')
<style>
.ledger-card{border:0;border-radius:12px;box-shadow:0 3px 16px rgba(15,23,42,.07)}
.ledger-text{min-height:230px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;white-space:pre}
.ledger-table th{font-size:12px;white-space:nowrap}.ledger-table td{font-size:13px;vertical-align:middle}
.ledger-stat{border-left:4px solid #2563eb}.ledger-stat.amount{border-color:#16a34a}.ledger-stat.qty{border-color:#f59e0b}
.import-step{height:100%;border:1px solid #e2e8f0;border-radius:12px;padding:14px;background:#fff}
.import-step .step-no{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e2e8f0;color:#334155;font-weight:800}
.import-step.done{border-color:#86efac;background:#f0fdf4}.import-step.done .step-no{background:#16a34a;color:#fff}
.workflow-progress{height:7px;border-radius:10px;background:#e2e8f0;overflow:hidden}.workflow-progress>span{display:block;height:100%;background:#16a34a}
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
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card ledger-card mb-3">
        <div class="card-header bg-white py-3">
            <b>Quy trình import đơn thực tế</b>
            <div class="small text-muted">Dữ liệu import là giao dịch lịch sử đã hoàn tất; hệ thống tự xác nhận doanh số và hoa hồng, sau đó shipper bổ sung điều phối/phí ship.</div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('accounting.sales-ledger.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">1. Chọn ngày thực hiện</label>
                    <input type="date" class="form-control" name="workflow_date" value="{{ $workflowDate }}" required>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-calendar-check me-1"></i>Áp dụng ngày</button></div>
                <div class="col-md-6 text-md-end small text-muted pb-2">
                    {{ $sourceWarehouse?->name ?? 'Chưa có Kho Long An' }} <i class="bi bi-arrow-right mx-1"></i> {{ $targetWarehouse?->name ?? 'Chưa có Kho Chiến Lược' }}
                    <div>Ship điều chuyển mặc định: <b>{{ $defaultTransferShipper?->name ?? 'Chưa cấu hình tài khoản Shipper' }}</b></div>
                </div>
            </form>
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-2">
                <div class="col"><div class="import-step done"><span class="step-no">1</span><div class="fw-bold mt-2">Chọn ngày</div><div class="small text-muted">{{ \Carbon\Carbon::parse($workflowDate)->format('d/m/Y') }}</div></div></div>
                <div class="col"><div class="import-step done"><span class="step-no">2</span><div class="fw-bold mt-2">Dán dữ liệu</div><div class="small text-muted">Bảng doanh số lịch sử</div></div></div>
                <div class="col"><div class="import-step done"><span class="step-no">3</span><div class="fw-bold mt-2">Thực hiện import</div><div class="small text-muted">Tạo đơn theo ngày chọn</div></div></div>
                <div class="col"><div class="import-step"><span class="step-no">✓</span><div class="fw-bold mt-2">Tự hoàn tất quy trình</div><div class="small text-muted">Duyệt, kho, đóng hàng, giao hàng</div></div></div>
                <div class="col"><div class="import-step"><span class="step-no">✓</span><div class="fw-bold mt-2">Kế toán xác nhận</div><div class="small text-muted">Doanh số và hoa hồng</div></div></div>
                <div class="col"><div class="import-step"><span class="step-no">+</span><div class="fw-bold mt-2">Shipper bổ sung</div><div class="small text-muted">Điều phối và phí ship từng đơn</div><div class="d-flex flex-wrap gap-1 mt-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('accounting.shippers', ['date' => $workflowDate]) }}"><i class="bi bi-person-check me-1"></i>Điều phối</a><a class="btn btn-sm btn-outline-success" href="{{ route('accounting.shipping-costs', ['date' => $workflowDate]) }}"><i class="bi bi-cash-coin me-1"></i>Phí ship</a></div></div></div>
            </div>
        </div>
    </div>

    <div class="card ledger-card mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div><b>Đơn hàng ngày {{ \Carbon\Carbon::parse($workflowDate)->format('d/m/Y') }}</b><div class="small text-muted">Lấy theo ngày tạo đơn (`created_at`), gồm cả đơn import và đơn tạo trực tiếp.</div></div>
            <span class="badge bg-primary">{{ $dailyOrders->count() }} đơn</span>
        </div>
        <div class="table-responsive"><table class="table table-sm mb-0 align-middle"><thead><tr><th>Mã đơn</th><th>Giờ tạo</th><th>Khách hàng</th><th>Sale</th><th>Kho hiện tại</th><th>Trạng thái</th><th>Nguồn</th></tr></thead><tbody>
            @php
                $orderStatusLabels = [
                    'pending' => 'Chờ duyệt',
                    \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL => 'Chờ trưởng phòng duyệt',
                    \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL => 'Chờ Manager duyệt',
                    \App\Models\Order::STATUS_APPROVED => 'Đã duyệt - chờ kho',
                    \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng hàng',
                    \App\Models\Order::STATUS_PACKING => 'Đang đóng hàng',
                    \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ bàn giao ship',
                    \App\Models\Order::STATUS_DELIVERING => 'Đang giao',
                    \App\Models\Order::STATUS_DELIVERED => 'Đã giao',
                    \App\Models\Order::STATUS_COMPLETED => 'Hoàn thành',
                    \App\Models\Order::STATUS_CANCELLED => 'Đã hủy',
                ];
            @endphp
            @forelse($dailyOrders as $dailyOrder)
                <tr><td><b>{{ $dailyOrder->code ?: '#'.$dailyOrder->id }}</b></td><td>{{ $dailyOrder->created_at?->format('H:i') }}</td><td>{{ $dailyOrder->customer?->name ?: '—' }}</td><td>{{ $dailyOrder->user?->name ?: '—' }}</td><td>{{ $dailyOrder->warehouse?->name ?: '—' }}</td><td><span class="badge bg-secondary">{{ $orderStatusLabels[$dailyOrder->status] ?? $dailyOrder->status }}</span></td><td>@if($dailyOrder->accounting_sales_import_batch_id)<span class="badge bg-info text-dark">Import #{{ $dailyOrder->accounting_sales_import_batch_id }}</span>@else<span class="badge bg-light text-dark">Đơn thường</span>@endif</td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Chưa có đơn hàng trong ngày này.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card ledger-card ledger-stat"><div class="card-body"><div class="text-muted small">Số dòng</div><div class="fs-4 fw-bold">{{ number_format($summary['rows']) }}</div></div></div></div>
        <div class="col-md-4"><div class="card ledger-card ledger-stat qty"><div class="card-body"><div class="text-muted small">Tổng sản lượng</div><div class="fs-4 fw-bold">{{ number_format($summary['quantity'], 1, ',', '.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card ledger-card ledger-stat amount"><div class="card-body"><div class="text-muted small">Doanh số</div><div class="fs-4 fw-bold text-success">{{ number_format($summary['amount'], 0, ',', '.') }} đ</div></div></div></div>
    </div>

    <div class="card ledger-card mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><b>Bước 2–3: Dán và import dữ liệu hoàn tất</b><div class="text-muted small">Copy nguyên bảng từ Excel/Google Sheets, bao gồm hàng tiêu đề. Tất cả dòng phải đúng ngày đã chọn.</div></div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.sales-ledger.import') }}">
                @csrf
                <input type="hidden" name="workflow_date" value="{{ $workflowDate }}">
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Phiếu nhập kho liên quan (không bắt buộc)</label>
                        <select class="form-select" name="stock_in_document_id">
                            <option value="">-- Không cần chọn khi import dữ liệu hoàn tất --</option>
                            @foreach($stockInDocuments as $document)
                                <option value="{{ $document->id }}" @selected((string)old('stock_in_document_id') === (string)$document->id)>
                                    {{ $document->document_number ?? '#'.$document->id }} · {{ $document->items_count }} mặt hàng
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-1">Chỉ chọn khi cần đối chiếu thêm với một phiếu nhập kho đã có.</div>
                    </div>
                    <div class="col-md-5 d-flex align-items-end"><div class="alert alert-info py-2 mb-0 w-100 small"><b>Sau import:</b> đơn hoàn thành và kế toán đã xác nhận. Chặng {{ $sourceWarehouse?->name ?? 'kho nguồn' }} → {{ $targetWarehouse?->name ?? 'kho đích' }} dùng ship mặc định <b>{{ $defaultTransferShipper?->name ?? 'chưa cấu hình' }}</b>; shipper bổ sung người giao khách và phí ship sau.</div></div>
                </div>
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
                    <button class="btn btn-success" name="text_action" value="import" onclick="return confirm('Import các giao dịch đã hoàn tất, tự xác nhận doanh số và hoa hồng?')"><i class="bi bi-upload me-1"></i>Import {{ $importResult['order_count'] ?? 0 }} đơn hoàn tất</button>
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
    <div class="card ledger-card"><div class="card-header bg-white"><b>Tiến độ 10 phiên import gần nhất</b></div><div class="table-responsive"><table class="table table-sm mb-0 align-middle"><thead><tr><th># / Ngày</th><th>Nhập kho</th><th>Duyệt đơn</th><th>Đóng & nhận tại VP</th><th>Ship từ VP</th><th>Giao hàng</th><th>Kế toán</th><th></th></tr></thead><tbody>
        @foreach($batches as $batch)
        @php($progress = $batch->workflow_progress)
        @php($totalOrders = max(1, (int)$progress['total']))
        <tr>
            <td><b>#{{ $batch->id }}</b><div class="small text-muted">{{ optional($batch->business_date)->format('d/m/Y') ?: $batch->created_at->format('d/m/Y') }} · {{ $progress['total'] }} đơn</div></td>
            <td>@if($batch->stockInDocument)<span class="badge bg-success">{{ $batch->stockInDocument->document_number }}</span>@else<span class="badge bg-secondary">Dữ liệu cũ</span>@endif</td>
            <td><div class="small">TP {{ $progress['leader_approved'] }}/{{ $progress['total'] }}</div><div class="small">Manager {{ $progress['manager_approved'] }}/{{ $progress['total'] }}</div></td>
            <td style="min-width:160px"><div class="small">Đóng {{ $progress['packed'] }}/{{ $progress['total'] }} · VP nhận {{ $progress['received'] }}/{{ $progress['total'] }}</div><div class="workflow-progress"><span style="width:{{ round(($progress['received']/$totalOrders)*100) }}%"></span></div></td>
            <td><span class="badge {{ $progress['shipping']===$progress['total'] && $progress['total']>0 ? 'bg-success' : 'bg-warning text-dark' }}">{{ $progress['shipping'] }}/{{ $progress['total'] }}</span></td>
            <td><span class="badge {{ $progress['delivered']===$progress['total'] && $progress['total']>0 ? 'bg-success' : 'bg-warning text-dark' }}">{{ $progress['delivered'] }}/{{ $progress['total'] }}</span></td>
            <td><span class="badge {{ $progress['accounted']===$progress['total'] && $progress['total']>0 ? 'bg-success' : 'bg-warning text-dark' }}">{{ $progress['accounted'] }}/{{ $progress['total'] }}</span></td>
            <td class="text-nowrap">
                @if($batch->business_date)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('accounting.reconciliation', ['date' => $batch->business_date->toDateString(), 'business_date' => 1]) }}">Đối soát</a>
                @endif
                <form class="d-inline" method="POST" action="{{ route('accounting.sales-ledger.batches.destroy',$batch) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Chỉ xóa khi phiên chưa phát sinh vận hành. Tiếp tục?')">Xóa</button></form>
            </td>
        </tr>
        @if($batch->business_date)
        <tr class="table-light">
            <td colspan="8">
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span class="small fw-semibold me-1">Mở công đoạn:</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('package.orders', ['date' => $batch->business_date->toDateString()]) }}">Đóng hàng Long An</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.order-transfers', ['from_date' => $batch->business_date->toDateString(), 'to_date' => $batch->business_date->toDateString()]) }}">Tạo điều chuyển</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('shipper.warehouse-transfers', ['date' => $batch->business_date->toDateString()]) }}">Chuyển lên VP</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.transfers.incoming', ['date' => $batch->business_date->toDateString()]) }}">VP nhận đơn</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.shippers', ['date' => $batch->business_date->toDateString()]) }}">Điều phối giao</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.shipping-costs', ['date' => $batch->business_date->toDateString()]) }}">Cập nhật phí ship</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('shipper.available', ['date' => $batch->business_date->toDateString()]) }}">Ship từ VP</a>
                </div>
            </td>
        </tr>
        @endif
        @endforeach
    </tbody></table></div></div>
    @endif
</div>
@endsection
