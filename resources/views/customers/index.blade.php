@extends('layouts.app')

@push('styles')
<style>
.cust-page { padding: 28px 0 52px; }
.cust-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 60%, #3a6ea5 100%);
    border-radius: 9px;
    color: #fff;
    padding: 24px 28px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 28px rgba(30,58,95,.18);
}
.cust-header::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    right: -50px; top: -50px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.cust-stat {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 9px;
    padding: 14px 20px;
    backdrop-filter: blur(4px);
}
.cust-stat-label { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.65); margin-bottom: 4px; }
.cust-stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.cust-panel {
    background: #fff;
    border: 1px solid rgba(15,23,42,.08);
    border-radius: 9px;
    box-shadow: 0 4px 18px rgba(15,23,42,.06);
    margin-bottom: 18px;
}
.cust-filter-bar { padding: 18px 20px; border-bottom: 1px solid #eef2f7; }
.cust-filter-bar .form-control,
.cust-filter-bar .form-select {
    height: 38px;
    border-radius: 7px;
    border-color: #d8deea;
    font-size: .875rem;
}
.cust-filter-bar .btn { height: 38px; border-radius: 7px; font-size: .875rem; }
.cust-actions-bar { padding: 14px 20px; border-bottom: 1px solid #eef2f7; background: #f8fafc; border-radius: 9px 9px 0 0; }
.cust-table { margin: 0; }
.cust-table thead th {
    background: #f1f5fb;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    padding: 11px 14px;
    white-space: nowrap;
}
.cust-table tbody td { padding: 12px 14px; vertical-align: middle; font-size: .875rem; border-color: #eef2f7; }
.cust-table tbody tr:hover { background: #f8faff; }
.cust-name { font-weight: 600; color: #1e293b; }
.cust-dob { font-size: .75rem; color: #94a3b8; margin-top: 2px; }
.cust-phone { font-weight: 500; color: #334155; }
.cust-email { color: #475569; font-size: .8rem; }
.badge-free { background: #fff3cd; color: #92400e; border: 1px solid #fcd34d; font-size: .72rem; font-weight: 700; padding: 3px 8px; border-radius: 5px; }
.badge-managed { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; font-size: .72rem; font-weight: 700; padding: 3px 8px; border-radius: 5px; }
.badge-employee { background: #e0f2fe; color: #0c4a6e; border: 1px solid #7dd3fc; font-size: .72rem; font-weight: 700; padding: 3px 8px; border-radius: 5px; }
.sale-name { font-weight: 600; color: #1e40af; }
.sale-prev { font-size: .72rem; color: #94a3b8; }
.expires-date { font-size: .8rem; color: #374151; }
.expires-note { font-size: .7rem; color: #94a3b8; }
.assign-form select { height: 32px; font-size: .8rem; border-radius: 6px; border-color: #d8deea; min-width: 160px; }
.assign-form .btn-assign { height: 32px; font-size: .78rem; padding: 0 10px; border-radius: 6px; white-space: nowrap; }
.cust-actions .btn { font-size: .75rem; padding: 4px 9px; border-radius: 5px; }
.cust-empty { padding: 52px 0; text-align: center; color: #94a3b8; }
.cust-empty i { font-size: 2.8rem; margin-bottom: 12px; opacity: .4; }
.cust-pagination { padding: 16px 20px; border-top: 1px solid #eef2f7; }
.row-check { width: 15px; height: 15px; cursor: pointer; }
</style>
@endpush

@section('content')
@php
    $isAdmin = auth()->user()?->isAdmin();
    $totalCount = $customers->total();
    $freeCount  = $customers->getCollection()->filter(fn($c) => $c->isFree())->count();
@endphp

<div class="container-fluid cust-page">

    {{-- Header --}}
    <div class="cust-header mb-3">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="letter-spacing:-.01em;">{{ __('customers.index.title') }}</h4>
                <div style="font-size:.85rem;color:rgba(255,255,255,.7);">Quản lý danh sách khách hàng &amp; phân công sale</div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                <div class="cust-stat">
                    <div class="cust-stat-label">Tổng khách</div>
                    <div class="cust-stat-value">{{ number_format($totalCount) }}</div>
                </div>
                @if($isAdmin)
                <div class="cust-stat">
                    <div class="cust-stat-label">Tự do (trang này)</div>
                    <div class="cust-stat-value">{{ $freeCount }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-3 py-2 px-3" style="font-size:.875rem;">
            <i class="ph ph-check-circle me-1"></i>{{ session('success') }}
        </div>
    @endif

    <div class="cust-panel">

        {{-- Actions bar --}}
        <div class="cust-actions-bar d-flex flex-wrap gap-2 align-items-center">
            <form id="bulkDeleteForm" action="{{ route('customers.bulkDelete') }}" method="POST" class="d-inline-flex">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('customers.index.bulk_delete_confirm') }}')">
                    <i class="ph ph-trash me-1"></i>{{ __('customers.index.bulk_delete') }}
                </button>
            </form>

            @if($isAdmin)
            <form id="bulkMarkEmployeeForm" action="{{ route('customers.bulkMarkEmployee') }}" method="POST" class="d-inline-flex">
                @csrf
                <input type="hidden" name="ids" id="bulkMarkEmployeeIds">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="type_id" value="{{ request('type_id') }}">
                <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <input type="hidden" name="ownership_status" value="{{ request('ownership_status') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
                <input type="hidden" name="is_employee" value="{{ request('is_employee') }}">
                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('{{ __('customers.index.bulk_mark_employee_confirm') }}')">
                    <i class="ph ph-identification-card me-1"></i>{{ __('customers.index.bulk_mark_employee') }}
                </button>
            </form>

            @if(request()->boolean('is_employee'))
            <form id="bulkUnmarkEmployeeForm" action="{{ route('customers.bulkUnmarkEmployee') }}" method="POST" class="d-inline-flex">
                @csrf
                <input type="hidden" name="ids" id="bulkUnmarkEmployeeIds">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="type_id" value="{{ request('type_id') }}">
                <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <input type="hidden" name="ownership_status" value="{{ request('ownership_status') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
                <input type="hidden" name="is_employee" value="1">
                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ __('customers.index.bulk_unmark_employee_confirm') }}')">
                    <i class="ph ph-identification-card-slash me-1"></i>{{ __('customers.index.bulk_unmark_employee') }}
                </button>
            </form>
            @endif
            @endif

            <div class="vr d-none d-sm-block" style="height:24px;"></div>

            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="d-inline-flex align-items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" class="form-control form-control-sm" style="max-width:210px;" required>
                <button class="btn btn-sm btn-outline-warning text-nowrap">
                    <i class="ph ph-upload me-1"></i>{{ __('customers.index.import_excel') }}
                </button>
            </form>

            <a href="{{ route('customers.export') }}" class="btn btn-sm btn-outline-info text-nowrap">
                <i class="ph ph-download me-1"></i>{{ __('customers.index.export_excel') }}
            </a>

            <a href="{{ route('customers.create') }}" class="btn btn-sm btn-success ms-auto text-nowrap">
                <i class="ph ph-plus me-1"></i>{{ __('customers.index.add') }}
            </a>
        </div>

        {{-- Filter bar --}}
        <div class="cust-filter-bar">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('customers.index') }}">
                <div class="col-auto">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                        placeholder="{{ __('customers.index.search_placeholder') }}" style="min-width:200px;">
                </div>
                <div class="col-auto">
                    <select name="type_id" class="form-select">
                        <option value="">{{ __('customers.index.all_types') }}</option>
                        @foreach($types as $t)
                            <option value="{{ $t->id }}" {{ (string)$t->id === request('type_id') ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($users)
                <div class="col-auto">
                    <select name="assigned_to" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('customers.index.all_staff') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string)$u->id === request('assigned_to') ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($isAdmin && $creatorUsers)
                <div class="col-auto">
                    <select name="user_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Người tạo (tất cả)</option>
                        @foreach($creatorUsers as $creator)
                            <option value="{{ $creator->id }}" {{ (string)$creator->id === request('user_id') ? 'selected' : '' }}>
                                {{ $creator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($isAdmin)
                <div class="col-auto">
                    <select name="ownership_status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="managed" {{ request('ownership_status') === 'managed' ? 'selected' : '' }}>Đang thuộc sale</option>
                        <option value="free"    {{ request('ownership_status') === 'free'    ? 'selected' : '' }}>Khách tự do</option>
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([10,15,25,50,100] as $pp)
                            <option value="{{ $pp }}" {{ request('per_page',15)==$pp ? 'selected' : '' }}>
                                {{ $pp }} {{ __('customers.index.per_page_suffix') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="ph ph-funnel me-1"></i>{{ __('common.actions.filter') }}
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                        <i class="ph ph-arrow-counter-clockwise me-1"></i>{{ __('common.actions.reset') }}
                    </a>
                    <a href="{{ route('customers.index', array_merge(request()->except(['page', 'is_employee']), ['is_employee' => 1])) }}"
                        class="btn {{ request()->boolean('is_employee') ? 'btn-info' : 'btn-outline-info' }}">
                        <i class="ph ph-identification-card me-1"></i>{{ __('customers.index.employee_button') }}
                    </a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table cust-table mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="checkAll" class="row-check"></th>
                        <th style="width:48px;">#</th>
                        <th>{{ __('customers.index.name') }}</th>
                        <th>{{ __('customers.index.phone') }}</th>
                        <th>{{ __('customers.index.email') }}</th>
                        <th>Loại khách</th>
                        <th>Sale phụ trách</th>
                        <th>Người tạo</th>
                        <th>Hạn giữ khách</th>
                        @if($isAdmin)
                        <th style="min-width:230px;">Gán sale</th>
                        @endif
                        <th style="width:200px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td><input type="checkbox" class="row-check" value="{{ $customer->id }}"></td>
                        <td class="text-muted" style="font-size:.78rem;">{{ $customer->id }}</td>

                        <td>
                            <div class="cust-name">{{ $customer->name }}</div>
                            @if($customer->is_employee)
                                <div class="mt-1"><span class="badge-employee">{{ __('customers.index.employee_badge') }}</span></div>
                            @endif
                            @if($customer->dob)
                                <div class="cust-dob">
                                    {{ $customer->dob->format('d/m/Y') }} &bull; {{ $customer->dob->age }} tuổi
                                </div>
                            @endif
                            @php
                                $defaultAddr = $customer->addresses->firstWhere('is_default', 1)
                                    ?? $customer->addresses->first();
                            @endphp
                            <div class="cust-dob mt-1" style="font-size:.78rem;color:#64748b;">
                                {{ $defaultAddr?->note ?: 'Chưa có địa chỉ mặc định' }}
                            </div>
                        </td>

                        <td class="cust-phone text-nowrap">{{ $customer->phone ?: '—' }}</td>

                        <td class="cust-email">{{ $customer->email ?: '—' }}</td>

                        <td>
                            @if($customer->type)
                                <span class="badge rounded-pill" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;font-weight:600;">
                                    {{ $customer->type->name }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if($customer->is_employee)
                                <span class="text-muted" style="font-size:.8rem;">Không áp dụng cho khách Nhân viên</span>
                            @elseif($customer->isFree())
                                <span class="text-muted fst-italic" style="font-size:.8rem;">Tự do</span>
                                @if($customer->assignedTo)
                                    <div class="sale-prev">Gần nhất: {{ $customer->assignedTo->name }}</div>
                                @endif
                            @else
                                <span class="sale-name">{{ optional($customer->assignedTo)->name ?? '—' }}</span>
                            @endif
                        </td>

                        <td style="font-size:.8rem;color:#64748b;">{{ optional($customer->user)->name ?? '—' }}</td>

                        <td>
                            @if($customer->is_employee)
                                <span class="text-muted" style="font-size:.78rem;">Không áp dụng</span>
                            @elseif($customer->isFree())
                                <span class="badge-free" style="font-size:.7rem;">Có thể gán</span>
                            @elseif(($expiresAt = $customer->assignmentExpiresAt()))
                                <div class="expires-date">{{ $expiresAt->format('d/m/Y') }}</div>
                                <div class="expires-note">{{ $expiresAt->format('H:i') }} &bull; {{ $customerFreeDays }}d</div>
                            @else
                                <span class="text-muted" style="font-size:.78rem;">Không giới hạn</span>
                            @endif
                        </td>

                        @if($isAdmin)
                        <td>
                            @if($customer->is_employee)
                                <span class="text-muted" style="font-size:.78rem;">Không áp dụng</span>
                            @elseif($customer->isFree())
                                <form action="{{ route('customers.assign-sale', $customer) }}" method="POST" class="assign-form d-flex gap-2 align-items-center">
                                    @csrf
                                    <input type="hidden" name="q"               value="{{ request('q') }}">
                                    <input type="hidden" name="type_id"         value="{{ request('type_id') }}">
                                    <input type="hidden" name="assigned_to"     value="{{ request('assigned_to') }}">
                                    <input type="hidden" name="user_id"         value="{{ request('user_id') }}">
                                    <input type="hidden" name="ownership_status" value="{{ request('ownership_status') }}">
                                    <input type="hidden" name="per_page"        value="{{ request('per_page', 15) }}">
                                    <input type="hidden" name="is_employee"     value="{{ request('is_employee') }}">
                                    <input type="hidden" name="page"            value="{{ request('page', 1) }}">
                                    <select name="assigned_to" class="form-select form-select-sm">
                                        <option value="">— Tự do —</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary btn-assign">Lưu</button>
                                </form>
                            @else
                                <span class="text-muted" style="font-size:.78rem;">Chưa tới hạn tự do</span>
                            @endif
                        </td>
                        @endif

                        <td class="cust-actions text-nowrap">
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-warning" title="Sửa">
                                <i class="ph ph-pencil"></i>
                            </a>
                            <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-sm btn-outline-info" title="Địa chỉ">
                                <i class="ph ph-map-pin"></i>
                            </a>
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="Báo cáo">
                                <i class="ph ph-chart-bar"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('{{ __('customers.index.delete_confirm') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Xóa">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 11 : 10 }}">
                            <div class="cust-empty">
                                <div><i class="ph ph-users"></i></div>
                                <div style="font-weight:600;">{{ __('customers.index.empty') }}</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="cust-pagination d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div style="font-size:.8rem;color:#64748b;">
                Hiển thị {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }}
                / {{ number_format($customers->total()) }} khách hàng
            </div>
            <div>
                {{ $customers->appends(request()->except('page'))->links() }}
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll  = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.row-check:not(#checkAll)');

    checkAll && checkAll.addEventListener('change', function () {
        rowChecks.forEach(cb => cb.checked = checkAll.checked);
    });

    const bulkForm = document.getElementById('bulkDeleteForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            const ids = Array.from(document.querySelectorAll('.row-check:not(#checkAll):checked')).map(cb => cb.value);
            if (ids.length === 0) {
                alert(@json(__('customers.index.choose_one_for_bulk_delete')));
                e.preventDefault();
                return false;
            }
            document.getElementById('bulkDeleteIds').value = ids.join(',');
        });
    }

    const bulkMarkEmployeeForm = document.getElementById('bulkMarkEmployeeForm');
    if (bulkMarkEmployeeForm) {
        bulkMarkEmployeeForm.addEventListener('submit', function (e) {
            const ids = Array.from(document.querySelectorAll('.row-check:not(#checkAll):checked')).map(cb => cb.value);
            if (ids.length === 0) {
                alert(@json(__('customers.index.choose_one_for_bulk_mark_employee')));
                e.preventDefault();
                return false;
            }
            document.getElementById('bulkMarkEmployeeIds').value = ids.join(',');
        });
    }

    const bulkUnmarkEmployeeForm = document.getElementById('bulkUnmarkEmployeeForm');
    if (bulkUnmarkEmployeeForm) {
        bulkUnmarkEmployeeForm.addEventListener('submit', function (e) {
            const ids = Array.from(document.querySelectorAll('.row-check:not(#checkAll):checked')).map(cb => cb.value);
            if (ids.length === 0) {
                alert(@json(__('customers.index.choose_one_for_bulk_unmark_employee')));
                e.preventDefault();
                return false;
            }
            document.getElementById('bulkUnmarkEmployeeIds').value = ids.join(',');
        });
    }
});
</script>
@endsection
