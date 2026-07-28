@extends(accounting_layout())

@section('title', 'Quản trị hoa hồng')
@section('subtitle', 'Thiết lập mức hoa hồng sale cho một hoặc nhiều khách hàng')

@section('accounting_content')
<style>
    .commission-card { border: 0; border-radius: 12px; box-shadow: 0 3px 16px rgba(15, 23, 42, .07); }
    .commission-toolbar { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; }
    .commission-table th { white-space: nowrap; font-size: .78rem; color: #475569; }
    .commission-table td { vertical-align: middle; }
    .commission-current { min-width: 78px; display: inline-block; text-align: center; }
    .commission-sticky-actions { position: sticky; bottom: 0; z-index: 3; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -5px 14px rgba(15, 23, 42, .07); }
</style>

@if($missingTable)
    <div class="alert alert-warning">Chưa có bảng lịch sử hoa hồng. Mức hiện tại của khách hàng vẫn có thể được cập nhật.</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
@endif

<div class="card commission-card mb-3">
    <div class="card-header bg-white py-3">
        <div class="fw-bold"><i class="bi bi-funnel me-1"></i>Lọc danh sách khách hàng</div>
        <div class="small text-muted">Lọc theo sale trước, sau đó chọn các khách hàng cần áp dụng cùng một mức hoa hồng.</div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ accounting_route('commissions') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Nhân viên kinh doanh</label>
                <select class="form-select" name="sale_id">
                    <option value="">Tất cả sale</option>
                    @foreach($salesUsers as $sale)
                        <option value="{{ $sale->id }}" @selected((string)request('sale_id') === (string)$sale->id)>
                            {{ $sale->short_name ? $sale->short_name.' — ' : '' }}{{ $sale->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái hoa hồng</label>
                <select class="form-select" name="commission_status">
                    <option value="">Tất cả</option>
                    <option value="configured" @selected(request('commission_status') === 'configured')>Đã thiết lập (&gt; 0%)</option>
                    <option value="not_configured" @selected(request('commission_status') === 'not_configured')>Chưa thiết lập (0%)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tên hoặc mã khách hàng</label>
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Nhập tên hoặc mã KH...">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary"><i class="bi bi-search"></i> Lọc</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ accounting_route('commissions.bulk-update') }}" id="bulkCommissionForm">
    @csrf
    <div class="card commission-card mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-people me-1"></i>Chọn khách hàng áp dụng</div>
                <div class="small text-muted">Đang hiển thị {{ $customerRows->count() }} / {{ $customerRows->total() }} khách hàng phù hợp bộ lọc.</div>
            </div>
            <span class="badge bg-primary rounded-pill" id="selectedCustomerCount">Đã chọn 0</span>
        </div>

        <div class="table-responsive" style="max-height:480px;overflow:auto;">
            <table class="table table-hover commission-table mb-0">
                <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                    <tr>
                        <th class="text-center" style="width:54px;">
                            <input type="checkbox" class="form-check-input" id="commissionCheckAll" title="Chọn tất cả trên trang này">
                        </th>
                        <th>Mã KH</th>
                        <th>Khách hàng</th>
                        <th>Sale đang phụ trách</th>
                        <th class="text-center">Mức hiện tại</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customerRows as $customer)
                        @php($owner = $customer->currentOwner ?: $customer->assignedTo)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input js-commission-customer" name="customer_ids[]" value="{{ $customer->id }}">
                            </td>
                            <td class="text-nowrap fw-semibold">{{ $customer->customer_code ?: '—' }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>
                                @if($owner)
                                    <span class="badge bg-light text-dark border">{{ $owner->short_name ?: $owner->name }}</span>
                                @else
                                    <span class="text-muted">Chưa gắn sale</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ (float)$customer->commission_percent > 0 ? 'bg-success' : 'bg-secondary' }} commission-current">
                                    {{ number_format((float)$customer->commission_percent, 2, ',', '.') }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Không có khách hàng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customerRows->hasPages())
            <div class="card-footer bg-white">{{ $customerRows->links() }}</div>
        @endif

        <div class="card-body commission-sticky-actions">
            <div class="commission-toolbar p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mức hoa hồng mới (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="commission_percent" min="0" max="100" step="0.01" value="{{ old('commission_percent') }}" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ghi chú thay đổi</label>
                        <input class="form-control" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Ví dụ: Chính sách hoa hồng tháng 7">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="recalculate_existing" value="0">
                            <input class="form-check-input" type="checkbox" name="recalculate_existing" value="1" id="recalculateExisting" @checked(old('recalculate_existing'))>
                            <label class="form-check-label fw-semibold" for="recalculateExisting">Tính lại các đơn đã có hoa hồng</label>
                        </div>
                        <div class="small text-muted mt-1">Dùng để cập nhật các đơn <code>HIS-*</code> đang có hoa hồng 0đ.</div>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success fw-semibold" id="applyCommissionButton">
                            <i class="bi bi-check2-circle me-1"></i>Áp dụng đã chọn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@if(!$missingTable)
<div class="card commission-card">
    <div class="card-header bg-white py-3">
        <div class="fw-bold"><i class="bi bi-clock-history me-1"></i>Lịch sử thiết lập gần đây</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Khách hàng</th><th>Loại</th><th>Giá trị</th><th>Ngày áp dụng</th><th>Ghi chú</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->customer_name ?? '—' }}</td>
                    <td>{{ $row->type === 'percent' ? 'Phần trăm' : 'Số tiền cố định' }}</td>
                    <td class="fw-semibold">{{ number_format((float)$row->value, $row->type === 'percent' ? 2 : 0, ',', '.') }}{{ $row->type === 'percent' ? '%' : 'đ' }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->effective_date)->format('d/m/Y') }}</td>
                    <td>{{ $row->note ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có lịch sử thiết lập hoa hồng.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows, 'links'))<div class="card-footer bg-white">{{ $rows->links() }}</div>@endif
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('commissionCheckAll');
    const checkboxes = Array.from(document.querySelectorAll('.js-commission-customer'));
    const counter = document.getElementById('selectedCustomerCount');
    const form = document.getElementById('bulkCommissionForm');

    function refreshSelection() {
        const count = checkboxes.filter(checkbox => checkbox.checked).length;
        counter.textContent = 'Đã chọn ' + count;
        if (checkAll) {
            checkAll.checked = checkboxes.length > 0 && count === checkboxes.length;
            checkAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    }

    checkAll?.addEventListener('change', function () {
        checkboxes.forEach(checkbox => { checkbox.checked = checkAll.checked; });
        refreshSelection();
    });
    checkboxes.forEach(checkbox => checkbox.addEventListener('change', refreshSelection));

    form?.addEventListener('submit', function (event) {
        const count = checkboxes.filter(checkbox => checkbox.checked).length;
        if (count === 0) {
            event.preventDefault();
            alert('Vui lòng chọn ít nhất một khách hàng.');
            return;
        }
        const percent = form.querySelector('[name="commission_percent"]').value;
        const recalculate = document.getElementById('recalculateExisting')?.checked;
        const message = 'Áp dụng mức ' + percent + '% cho ' + count + ' khách hàng?'
            + (recalculate ? '\nCác đơn đã có hoa hồng cũng sẽ được tính lại.' : '');
        if (!confirm(message)) event.preventDefault();
    });

    refreshSelection();
});
</script>
@endpush
