@extends($config['layout'])

@section('title', 'Phiếu tài chính')
@section('subtitle', 'Tạo phiếu yêu cầu thu/chi hoặc phiếu đề nghị thanh toán')

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
    $selectedFormType = old('request_form_type', \App\Models\Transaction::REQUEST_FORM_CASH);
    $oldItems = old('items', [['content' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => 0]]);
    $currentUser = auth()->user();
    $currentDepartmentName = $currentUser?->department?->name;
    $currentBlockName = $currentUser?->department?->block?->name ?: $currentUser?->block?->name;
@endphp
<style>
    .finance-request-page {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 12px 24px;
    }
    .fr-panel {
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .fr-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
    }
    .fr-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
    .fr-subtitle {
        margin-top: 2px;
        color: #64748b;
        font-size: 13px;
    }
    .fr-panel-body {
        padding: 18px;
    }
    .fr-section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-weight: 800;
        color: #334155;
    }
    .fr-section-label i {
        color: var(--theme-primary, #0f766e);
    }
    .fr-meta-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .fr-note-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    .fr-summary-box {
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        background: #f8fafc;
        padding: 14px;
    }
    .fr-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        color: #334155;
    }
    .fr-summary-total {
        border-top: 1px solid #cbd5e1;
        margin-top: 6px;
        padding-top: 12px;
        font-size: 18px;
        font-weight: 800;
        color: var(--theme-primary, #0f766e);
    }
    .fr-items-table th {
        white-space: nowrap;
        font-size: 12px;
        color: #475569;
    }
    .fr-items-table td {
        vertical-align: middle;
    }
    .fr-items-table .form-control-sm {
        min-height: 34px;
    }
    .fr-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
    }
    .fr-history-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .fr-id-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        border-radius: 999px;
        padding: 4px 8px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
    }
    .fr-action-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .fr-user-card {
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        background: #f8fafc;
        padding: 10px 12px;
        color: #334155;
    }
    .fr-user-card strong {
        color: #0f172a;
    }
    .fr-creator-line {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
        font-size: 12px;
        color: #64748b;
    }
    .fr-creator-line span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    @media (max-width: 1199.98px) {
        .fr-meta-grid,
        .fr-note-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 767.98px) {
        .finance-request-page {
            padding-inline: 8px;
        }
        .fr-panel-head {
            align-items: flex-start;
            flex-direction: column;
        }
        .fr-meta-grid,
        .fr-note-grid {
            grid-template-columns: 1fr;
        }
        .fr-panel-body {
            padding: 14px;
        }
        .fr-actions {
            flex-direction: column;
        }
        .fr-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="finance-request-page">
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3 align-items-start">
        <div class="col-lg-5">
    <div class="fr-panel">
        <div class="fr-panel-head">
            <div>
                <h2 class="fr-title">Tạo phiếu tài chính</h2>
                <div class="fr-subtitle">{{ $config['label'] }} gửi Kế toán duyệt</div>
            </div>
            <span class="badge text-bg-light border px-3 py-2">{{ $config['label'] }}</span>
        </div>
        <div class="fr-panel-body">
            <form method="POST" action="{{ route($config['route_prefix'] . '.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="fr-section-label"><i class="bi bi-card-checklist"></i> Thông tin phiếu</div>
                <div class="fr-user-card mb-3">
                    <div class="small text-muted">Người tạo phiếu</div>
                    <div><strong>{{ $currentUser?->name ?: '-' }}</strong></div>
                    <div class="fr-creator-line">
                        <span><i class="bi bi-diagram-3"></i>{{ $currentBlockName ?: 'Chưa gán khối' }}</span>
                        <span><i class="bi bi-building"></i>{{ $currentDepartmentName ?: 'Chưa gán phòng ban' }}</span>
                    </div>
                </div>
                <div class="fr-meta-grid mb-4">
                    <div>
                        <label class="form-label fw-semibold">Loại chứng từ <span class="text-danger">*</span></label>
                        <select name="request_form_type" id="requestFormType" class="form-select" required>
                            <option value="{{ \App\Models\Transaction::REQUEST_FORM_CASH }}" @selected($selectedFormType === \App\Models\Transaction::REQUEST_FORM_CASH)>Phiếu yêu cầu thu/chi</option>
                            <option value="{{ \App\Models\Transaction::REQUEST_FORM_PAYMENT }}" @selected($selectedFormType === \App\Models\Transaction::REQUEST_FORM_PAYMENT)>Phiếu đề nghị thanh toán</option>
                        </select>
                    </div>
                    <div id="flowDirectionGroup">
                        <label class="form-label fw-semibold">Dòng tiền <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="Dòng tiền">
                            <input type="radio" class="btn-check" name="flow_direction" id="requestIn" value="in" @checked(old('flow_direction') === 'in')>
                            <label class="btn btn-outline-success" for="requestIn"><i class="bi bi-arrow-down-circle me-1"></i>Thu</label>

                            <input type="radio" class="btn-check" name="flow_direction" id="requestOut" value="out" @checked(old('flow_direction', 'out') === 'out')>
                            <label class="btn btn-outline-danger" for="requestOut"><i class="bi bi-arrow-up-circle me-1"></i>Chi</label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Tiêu đề phiếu <span class="text-danger">*</span></label>
                        <input type="text" name="request_title" class="form-control" value="{{ old('request_title') }}" placeholder="VD: Mua vật tư đóng gói">
                    </div>
                    <div>
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
                    <div>
                        <label class="form-label fw-semibold">Phương thức dự kiến</label>
                        <select name="method" class="form-select">
                            <option value="">-- Chọn --</option>
                            @foreach(['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'other' => 'Khác'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($managedAccounts->isNotEmpty())
                        <div id="sourceAccountGroup">
                            <label class="form-label fw-semibold">Tài khoản chi <span class="text-danger">*</span></label>
                            <select name="source_account_id" id="sourceAccountId" class="form-select">
                                @foreach($managedAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('source_account_id', $defaultManagedAccountId) === (string) $account->id)>
                                        {{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Chỉ hiển thị tài khoản bạn đang được giao quản lý.</div>
                        </div>
                    @endif
                    <div>
                        <label class="form-label fw-semibold">Nơi nhận tiền <span class="text-danger">*</span></label>
                        <select name="destination_type" id="destinationType" class="form-select" required>
                            <option value="internal" @selected(old('destination_type', 'internal') === 'internal')>Tài khoản nội bộ</option>
                            <option value="external" @selected(old('destination_type') === 'external')>Bên ngoài</option>
                        </select>
                    </div>
                    <div id="destinationAccountGroup">
                        <label class="form-label fw-semibold">Tài khoản đến <span class="text-danger">*</span></label>
                        <select name="destination_account_id" id="destinationAccountId" class="form-select">
                            <option value="">-- Chọn tài khoản đến --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('destination_account_id') === (string) $account->id)>
                                    {{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}{{ $account->bank_name ? ' (' . $account->bank_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Dùng khi tiền được luân chuyển vào một tài khoản đang quản lý.</div>
                    </div>
                    <div id="externalRecipientGroup" class="d-none">
                        <label class="form-label fw-semibold">Người/đơn vị nhận <span class="text-danger">*</span></label>
                        <input type="text" name="external_recipient" id="externalRecipient" class="form-control" maxlength="255" value="{{ old('external_recipient') }}" placeholder="VD: Công ty ABC, Nguyễn Văn A...">
                    </div>
                </div>

                <div class="fr-note-grid mb-4">
                    <div>
                        <label class="form-label fw-semibold">Nội dung/Lý do <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control" rows="5" maxlength="1000" placeholder="Mô tả rõ lý do thu/chi, nhà cung cấp, vật tư, ghi chú kế toán...">{{ old('note') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Chứng từ đính kèm</label>
                        <input type="file" name="receipt_image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fr-section-label mb-0"><i class="bi bi-list-ul"></i> Danh sách nội dung</div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addRequestLine">
                        <i class="bi bi-plus-circle me-1"></i>Thêm dòng
                    </button>
                </div>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm align-middle mb-0 fr-items-table" id="requestItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:56px">STT</th>
                                <th style="min-width:260px">Nội dung</th>
                                <th style="width:96px">ĐVT</th>
                                <th style="width:130px">Số lượng</th>
                                <th style="width:150px">Đơn giá</th>
                                <th style="width:150px">Thành tiền</th>
                                <th style="width:48px"></th>
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
                                        <button type="button" class="btn btn-outline-danger btn-sm fr-action-icon remove-request-line" title="Xóa dòng">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="fr-note-grid">
                    <div></div>
                    <div class="fr-summary-box">
                        <div class="fr-summary-row">
                            <span>Tổng tiền</span>
                            <strong id="requestSubtotalText">0đ</strong>
                        </div>
                        <div class="fr-summary-row align-items-center">
                            <label class="form-label mb-0" for="requestVat">VAT</label>
                            <div class="input-group input-group-sm" style="max-width: 190px;">
                                <input type="number" name="request_vat" id="requestVat" class="form-control text-end" min="0" step="1000" value="{{ old('request_vat', 0) }}">
                                <span class="input-group-text">đ</span>
                            </div>
                        </div>
                        <div class="fr-summary-row fr-summary-total">
                            <span>Tổng cộng</span>
                            <strong id="requestTotalText">0đ</strong>
                        </div>
                    </div>
                </div>

                <div class="fr-actions">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-send me-1"></i>Gửi Kế toán duyệt
                    </button>
                </div>
            </form>
        </div>
    </div>
        </div>

        <div class="col-lg-7">
    <div class="fr-panel">
        <div class="fr-panel-head">
            <div>
                <h2 class="fr-title">Phiếu đã gửi</h2>
                <div class="fr-subtitle">Theo dõi trạng thái duyệt từ phòng Kế toán</div>
            </div>
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Loại chứng từ</label>
                    <select name="form_type" class="form-select form-select-sm">
                        <option value="all" @selected($formType === 'all')>Tất cả</option>
                        <option value="{{ \App\Models\Transaction::REQUEST_FORM_CASH }}" @selected($formType === \App\Models\Transaction::REQUEST_FORM_CASH)>Yêu cầu thu/chi</option>
                        <option value="{{ \App\Models\Transaction::REQUEST_FORM_PAYMENT }}" @selected($formType === \App\Models\Transaction::REQUEST_FORM_PAYMENT)>Đề nghị thanh toán</option>
                    </select>
                </div>
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
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        @php
                            $flow = $requestItem->transactionCategory?->flow_direction === 'in' ? 'in' : 'out';
                            $statusMeta = $statusLabels[$requestItem->status] ?? ['label' => $requestItem->status, 'class' => 'secondary'];
                        @endphp
                        <tr>
                            <td><span class="fr-id-pill">#{{ $requestItem->id }}</span></td>
                            <td>
                                <div class="mb-1">
                                    <span class="badge text-bg-light border">
                                        {{ $requestItem->request_form_type === \App\Models\Transaction::REQUEST_FORM_PAYMENT ? 'Đề nghị thanh toán' : 'Yêu cầu thu/chi' }}
                                    </span>
                                </div>
                                <div class="fw-semibold">{{ $requestItem->request_title ?: 'Phiếu yêu cầu' }}</div>
                                <div class="small text-muted">{{ $requestItem->transactionCategory?->name ?: '-' }}</div>
                                <div class="fr-creator-line">
                                    <span><i class="bi bi-person"></i>{{ $requestItem->submitter?->name ?: '-' }}</span>
                                    <span><i class="bi bi-diagram-3"></i>{{ $requestItem->submitter?->department?->block?->name ?: $requestItem->submitter?->block?->name ?: 'Chưa gán khối' }}</span>
                                    <span><i class="bi bi-building"></i>{{ $requestItem->submitter?->department?->name ?: 'Chưa gán phòng ban' }}</span>
                                </div>
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
                                <a href="{{ route($config['route_prefix'] . '.print', $requestItem) }}" target="_blank" class="btn btn-outline-secondary btn-sm fr-action-icon" title="In phiếu">
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
        <div class="p-3 border-top bg-white">
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
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryInput = document.getElementById('transactionCategoryId');
    const categoryText = document.getElementById('selectedCategoryText');
    const categoryItems = Array.from(document.querySelectorAll('.category-picker-item'));
    const flowInputs = Array.from(document.querySelectorAll('input[name="flow_direction"]'));
    const formTypeInput = document.getElementById('requestFormType');
    const flowDirectionGroup = document.getElementById('flowDirectionGroup');
    const modalElement = document.getElementById('categoryPickerModal');
    const requestItemsTable = document.getElementById('requestItemsTable');
    const addLineButton = document.getElementById('addRequestLine');
    const requestVatInput = document.getElementById('requestVat');
    const requestSubtotalText = document.getElementById('requestSubtotalText');
    const requestTotalText = document.getElementById('requestTotalText');
    const destinationType = document.getElementById('destinationType');
    const destinationAccountGroup = document.getElementById('destinationAccountGroup');
    const destinationAccountId = document.getElementById('destinationAccountId');
    const externalRecipientGroup = document.getElementById('externalRecipientGroup');
    const externalRecipient = document.getElementById('externalRecipient');
    const sourceAccountGroup = document.getElementById('sourceAccountGroup');
    const sourceAccountId = document.getElementById('sourceAccountId');

    function syncDestinationFields() {
        const isInternal = destinationType?.value === 'internal';
        destinationAccountGroup?.classList.toggle('d-none', !isInternal);
        externalRecipientGroup?.classList.toggle('d-none', isInternal);
        if (destinationAccountId) destinationAccountId.required = isInternal;
        if (externalRecipient) externalRecipient.required = !isInternal;
    }

    function syncSourceAccount() {
        const isExpense = currentFlow() === 'out';
        sourceAccountGroup?.classList.toggle('d-none', !isExpense);
        if (sourceAccountId) sourceAccountId.required = isExpense;
    }

    destinationType?.addEventListener('change', syncDestinationFields);
    syncDestinationFields();

    function formatMoney(value) {
        return Math.round(Number(value) || 0).toLocaleString('vi-VN') + 'đ';
    }

    function currentFlow() {
        return document.querySelector('input[name="flow_direction"]:checked')?.value || 'out';
    }

    function syncFormType() {
        const isPaymentProposal = formTypeInput?.value === '{{ \App\Models\Transaction::REQUEST_FORM_PAYMENT }}';
        flowDirectionGroup?.classList.toggle('d-none', isPaymentProposal);
        if (isPaymentProposal) {
            const outInput = document.getElementById('requestOut');
            if (outInput) outInput.checked = true;
        }
        syncCategoryList();
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

    flowInputs.forEach((input) => input.addEventListener('change', function () {
        syncCategoryList();
        syncSourceAccount();
    }));
    formTypeInput?.addEventListener('change', syncFormType);
    syncFormType();
    syncSourceAccount();

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
