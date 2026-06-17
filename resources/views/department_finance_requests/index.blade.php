@extends($config['layout'])

@section('title', 'Phiếu yêu cầu')
@section('subtitle', 'Tạo yêu cầu thu/chi gửi phòng Kế toán duyệt')

@section('content')
@php
    $statusLabels = [
        \App\Models\Transaction::STATUS_PENDING_APPROVAL => ['label' => 'Chờ duyệt', 'class' => 'warning text-dark'],
        \App\Models\Transaction::STATUS_APPROVED => ['label' => 'Đã duyệt', 'class' => 'success'],
        \App\Models\Transaction::STATUS_REJECTED => ['label' => 'Từ chối', 'class' => 'danger'],
    ];
    $incomeCategories = $categories->where('flow_direction', 'in')->values();
    $expenseCategories = $categories->where('flow_direction', 'out')->values();
    $selectedCategory = $categories->firstWhere('id', (int) old('transaction_category_id'));
    $oldItems = old('items', [['content' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => 0]]);
@endphp

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-bold">Tạo phiếu yêu cầu</div>
                <div class="small text-muted">{{ $config['label'] }} gửi Kế toán duyệt thu/chi</div>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route($config['route_prefix'] . '.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại phiếu <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="Loại phiếu">
                            <input type="radio" class="btn-check" name="flow_direction" id="requestIn" value="in" @checked(old('flow_direction') === 'in')>
                            <label class="btn btn-outline-success" for="requestIn"><i class="bi bi-arrow-down-circle me-1"></i>Thu</label>

                            <input type="radio" class="btn-check" name="flow_direction" id="requestOut" value="out" @checked(old('flow_direction', 'out') === 'out')>
                            <label class="btn btn-outline-danger" for="requestOut"><i class="bi bi-arrow-up-circle me-1"></i>Chi</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề phiếu <span class="text-danger">*</span></label>
                        <input type="text" name="request_title" class="form-control" value="{{ old('request_title') }}" placeholder="VD: Mua vật tư đóng gói">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Danh mục kế toán <span class="text-danger">*</span></label>
                        <input type="hidden" name="transaction_category_id" id="transactionCategoryId" value="{{ old('transaction_category_id') }}" required>
                        <button type="button" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#categoryPickerModal">
                            <span id="selectedCategoryText">
                                @if($selectedCategory)
                                    {{ $selectedCategory->code }} - {{ $selectedCategory->name }}
                                @else
                                    Chọn danh mục
                                @endif
                            </span>
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức dự kiến</label>
                        <select name="method" class="form-select">
                            <option value="">-- Chọn --</option>
                            @foreach(['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'other' => 'Khác'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung/Lý do <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control" rows="4" maxlength="1000" placeholder="Mô tả rõ lý do thu/chi, nhà cung cấp, vật tư, ghi chú kế toán...">{{ old('note') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Danh sách nội dung <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addRequestLine">
                                <i class="bi bi-plus-circle me-1"></i>Thêm dòng
                            </button>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0" id="requestItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:48px">STT</th>
                                        <th style="min-width:180px">Nội dung</th>
                                        <th style="width:80px">ĐVT</th>
                                        <th style="width:110px">Số lượng</th>
                                        <th style="width:130px">Đơn giá</th>
                                        <th style="width:130px">Thành tiền</th>
                                        <th style="width:42px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($oldItems as $itemIndex => $item)
                                        <tr class="request-line">
                                            <td class="line-index text-center">{{ $itemIndex + 1 }}</td>
                                            <td>
                                                <input type="text" name="items[{{ $itemIndex }}][content]" class="form-control form-control-sm line-content" value="{{ $item['content'] ?? '' }}" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $itemIndex }}][unit]" class="form-control form-control-sm" value="{{ $item['unit'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $itemIndex }}][quantity]" class="form-control form-control-sm line-quantity" min="0.01" step="0.01" value="{{ $item['quantity'] ?? 1 }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $itemIndex }}][unit_price]" class="form-control form-control-sm line-price" min="0" step="1000" value="{{ $item['unit_price'] ?? 0 }}" required>
                                            </td>
                                            <td class="line-total text-end fw-semibold">0đ</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-request-line" title="Xóa dòng">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tổng tiền</span>
                                <strong id="requestSubtotalText">0đ</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0" for="requestVat">VAT</label>
                                <div class="input-group input-group-sm" style="max-width: 180px;">
                                    <input type="number" name="request_vat" id="requestVat" class="form-control text-end" min="0" step="1000" value="{{ old('request_vat', 0) }}">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between fs-5">
                                <span class="fw-bold">Tổng cộng</span>
                                <strong id="requestTotalText" class="text-primary">0đ</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chứng từ đính kèm</label>
                        <input type="file" name="receipt_image" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i>Gửi Kế toán duyệt
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-end justify-content-between">
                    <div>
                        <div class="fw-bold">Phiếu đã gửi</div>
                        <div class="small text-muted">Theo dõi trạng thái duyệt từ phòng Kế toán</div>
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <div>
                            <label class="form-label small mb-1">Trạng thái</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="all" @selected($status === 'all')>Tất cả</option>
                                <option value="{{ \App\Models\Transaction::STATUS_PENDING_APPROVAL }}" @selected($status === \App\Models\Transaction::STATUS_PENDING_APPROVAL)>Chờ duyệt</option>
                                <option value="{{ \App\Models\Transaction::STATUS_APPROVED }}" @selected($status === \App\Models\Transaction::STATUS_APPROVED)>Đã duyệt</option>
                                <option value="{{ \App\Models\Transaction::STATUS_REJECTED }}" @selected($status === \App\Models\Transaction::STATUS_REJECTED)>Từ chối</option>
                            </select>
                        </div>
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-funnel me-1"></i>Lọc</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Phiếu</th>
                            <th>Dòng tiền</th>
                            <th class="text-end">Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Người xử lý</th>
                            <th>Ngày gửi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            @php
                                $flow = $requestItem->transactionCategory?->flow_direction === 'in' ? 'in' : 'out';
                                $statusMeta = $statusLabels[$requestItem->status] ?? ['label' => $requestItem->status, 'class' => 'secondary'];
                            @endphp
                            <tr>
                                <td class="text-muted">#{{ $requestItem->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $requestItem->request_title ?: 'Phiếu yêu cầu' }}</div>
                                    <div class="small text-muted">{{ $requestItem->transactionCategory?->name ?: '-' }}</div>
                                    @if($requestItem->note)
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($requestItem->note, 90) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $flow === 'in' ? 'success' : 'danger' }}">
                                        {{ $flow === 'in' ? 'Thu' : 'Chi' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format((float) $requestItem->amount) }}đ</td>
                                <td>
                                    <span class="badge bg-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                    @if($requestItem->status === \App\Models\Transaction::STATUS_REJECTED && $requestItem->reject_reason)
                                        <div class="small text-danger mt-1">{{ \Illuminate\Support\Str::limit($requestItem->reject_reason, 80) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($requestItem->status === \App\Models\Transaction::STATUS_APPROVED)
                                        {{ $requestItem->approver?->name ?: '-' }}
                                    @elseif($requestItem->status === \App\Models\Transaction::STATUS_REJECTED)
                                        {{ $requestItem->rejecter?->name ?: '-' }}
                                    @else
                                        <span class="text-muted">Kế toán</span>
                                    @endif
                                </td>
                                <td>{{ optional($requestItem->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route($config['route_prefix'] . '.print', $requestItem) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Chưa có phiếu yêu cầu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryPickerModal" tabindex="-1" aria-labelledby="categoryPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryPickerModalLabel">Danh mục kế toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="categoryPickerList">
                    @foreach($categories as $category)
                        <button type="button"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start category-picker-item"
                            data-category-id="{{ $category->id }}"
                            data-category-label="{{ $category->code }} - {{ $category->name }}"
                            data-flow="{{ $category->flow_direction }}">
                            <span>
                                <span class="fw-semibold">{{ $category->name }}</span>
                                <span class="d-block small text-muted">{{ $category->code }}</span>
                            </span>
                            <span class="badge bg-{{ $category->flow_direction === 'in' ? 'success' : 'danger' }}">
                                {{ $category->flow_direction === 'in' ? 'Thu' : 'Chi' }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryInput = document.getElementById('transactionCategoryId');
    const categoryText = document.getElementById('selectedCategoryText');
    const categoryItems = Array.from(document.querySelectorAll('.category-picker-item'));
    const flowInputs = Array.from(document.querySelectorAll('input[name="flow_direction"]'));
    const modalElement = document.getElementById('categoryPickerModal');
    const requestItemsTable = document.getElementById('requestItemsTable');
    const addLineButton = document.getElementById('addRequestLine');
    const requestVatInput = document.getElementById('requestVat');
    const requestSubtotalText = document.getElementById('requestSubtotalText');
    const requestTotalText = document.getElementById('requestTotalText');

    function formatMoney(value) {
        return Math.round(Number(value) || 0).toLocaleString('vi-VN') + 'đ';
    }

    function currentFlow() {
        return document.querySelector('input[name="flow_direction"]:checked')?.value || 'out';
    }

    function syncCategoryList() {
        const flow = currentFlow();
        const selectedItem = categoryItems.find((item) => item.dataset.categoryId === categoryInput.value);

        if (selectedItem && selectedItem.dataset.flow !== flow) {
            categoryInput.value = '';
            categoryText.textContent = 'Chọn danh mục';
        }

        categoryItems.forEach((item) => {
            item.classList.toggle('d-none', item.dataset.flow !== flow);
            item.classList.toggle('active', item.dataset.categoryId === categoryInput.value);
        });
    }

    categoryItems.forEach((item) => {
        item.addEventListener('click', function () {
            categoryInput.value = item.dataset.categoryId;
            categoryText.textContent = item.dataset.categoryLabel;
            syncCategoryList();

            if (window.bootstrap && modalElement) {
                const modal = window.bootstrap.Modal.getInstance(modalElement) || new window.bootstrap.Modal(modalElement);
                modal.hide();
            }
        });
    });

    flowInputs.forEach((input) => input.addEventListener('change', syncCategoryList));
    syncCategoryList();

    function requestRows() {
        return Array.from(requestItemsTable.querySelectorAll('tbody tr.request-line'));
    }

    function recalculateRequestItems() {
        let subtotal = 0;

        requestRows().forEach((row, index) => {
            row.querySelector('.line-index').textContent = index + 1;
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });

            const quantity = Number(row.querySelector('.line-quantity')?.value || 0);
            const unitPrice = Number(row.querySelector('.line-price')?.value || 0);
            const lineTotal = quantity * unitPrice;
            subtotal += lineTotal;
            row.querySelector('.line-total').textContent = formatMoney(lineTotal);
        });

        const vat = Number(requestVatInput?.value || 0);
        requestSubtotalText.textContent = formatMoney(subtotal);
        requestTotalText.textContent = formatMoney(subtotal + vat);
    }

    function bindRequestLine(row) {
        row.querySelectorAll('.line-quantity, .line-price').forEach((input) => {
            input.addEventListener('input', recalculateRequestItems);
        });
        row.querySelector('.remove-request-line')?.addEventListener('click', function () {
            if (requestRows().length <= 1) {
                row.querySelectorAll('input').forEach((input) => {
                    input.value = input.classList.contains('line-quantity') ? '1' : '';
                });
                row.querySelector('.line-price').value = '0';
            } else {
                row.remove();
            }
            recalculateRequestItems();
        });
    }

    addLineButton?.addEventListener('click', function () {
        const rows = requestRows();
        const newRow = rows[rows.length - 1].cloneNode(true);
        newRow.querySelectorAll('input').forEach((input) => {
            input.value = input.classList.contains('line-quantity') ? '1' : '';
        });
        newRow.querySelector('.line-price').value = '0';
        requestItemsTable.querySelector('tbody').appendChild(newRow);
        bindRequestLine(newRow);
        recalculateRequestItems();
        newRow.querySelector('.line-content')?.focus();
    });

    requestRows().forEach(bindRequestLine);
    requestVatInput?.addEventListener('input', recalculateRequestItems);
    recalculateRequestItems();
});
</script>
@endsection
