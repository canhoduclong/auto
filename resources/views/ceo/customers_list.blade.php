@extends('layouts.ceo')

@section('title', 'Danh sách khách hàng')
@section('subtitle', 'Danh sách khách hàng theo phong cách quản trị')

@push('styles')
<style>
.cust-page { padding: 4px 0 30px; }
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
    width: 180px;
    height: 180px;
    right: -50px;
    top: -50px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.cust-stat {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 9px;
    padding: 14px 20px;
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
.badge-employee { background: #e0f2fe; color: #0c4a6e; border: 1px solid #7dd3fc; font-size: .72rem; font-weight: 700; padding: 3px 8px; border-radius: 5px; }
.sale-name { font-weight: 600; color: #1e40af; }
.sale-prev { font-size: .72rem; color: #94a3b8; }
.expires-date { font-size: .8rem; color: #374151; }
.expires-note { font-size: .7rem; color: #94a3b8; }
.cust-empty { padding: 52px 0; text-align: center; color: #94a3b8; }
.cust-pagination { padding: 16px 20px; border-top: 1px solid #eef2f7; }
</style>
@endpush

@section('content')
@php
    $totalCount = $customers->total();
    $freeCount  = $customers->getCollection()->filter(fn($c) => $c->isFree())->count();
@endphp

<div class="container-fluid cust-page">
    <div class="cust-header mb-3">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="letter-spacing:-.01em;">Danh sách khách hàng</h4>
                <div style="font-size:.85rem;color:rgba(255,255,255,.7);">Copy layout quản trị khách hàng từ Admin cho khu vực CEO</div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                <div class="cust-stat">
                    <div class="cust-stat-label">Tổng khách</div>
                    <div class="cust-stat-value">{{ number_format($totalCount) }}</div>
                </div>
                <div class="cust-stat">
                    <div class="cust-stat-label">Tự do (trang này)</div>
                    <div class="cust-stat-value">{{ $freeCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="cust-panel">
        <div class="cust-filter-bar">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('ceo.customers-list') }}">
                <div class="col-auto">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm theo tên / số điện thoại / email" style="min-width:220px;">
                </div>
                <div class="col-auto">
                    <select name="type_id" class="form-select">
                        <option value="">Tất cả loại</option>
                        @foreach($types as $t)
                            <option value="{{ $t->id }}" {{ (string)$t->id === request('type_id') ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="assigned_to" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả sale</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string)$u->id === request('assigned_to') ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="user_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Người tạo (tất cả)</option>
                        @foreach($creatorUsers as $creator)
                            <option value="{{ $creator->id }}" {{ (string)$creator->id === request('user_id') ? 'selected' : '' }}>{{ $creator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="ownership_status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="managed" {{ request('ownership_status') === 'managed' ? 'selected' : '' }}>Đang thuộc sale</option>
                        <option value="free" {{ request('ownership_status') === 'free' ? 'selected' : '' }}>Khách tự do</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" placeholder="Từ ngày">
                </div>
                <div class="col-auto">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" placeholder="Đến ngày">
                </div>
                <div class="col-12">
                    <div class="p-2 rounded" style="background: #f8faff; border: 1px solid #d8deea;">
                        <div class="small fw-semibold text-muted mb-2" style="font-size:.8rem;">LOẠI BỎ NHÂN VIÊN KHỎI THỐNG KÊ</div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px;">
                            @php $excludeIds = request('exclude_users', []); @endphp
                            @foreach($users as $u)
                                <label class="d-flex align-items-center gap-2" style="cursor:pointer;margin-bottom:0;font-size:.875rem;">
                                    <input type="checkbox" name="exclude_users[]" value="{{ $u->id }}" 
                                        class="form-check-input" style="margin:0;cursor:pointer;"
                                        {{ (is_array($excludeIds) ? in_array($u->id, $excludeIds) : false) ? 'checked' : '' }}
                                        onchange="this.form.submit()">
                                    <span style="user-select:none;">{{ $u->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([10,15,25,50,100] as $pp)
                            <option value="{{ $pp }}" {{ (int)request('per_page',15) === $pp ? 'selected' : '' }}>{{ $pp }} / trang</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-primary">Lọc</button>
                    <a href="{{ route('ceo.customers-list') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('ceo.customers-list', array_merge(request()->except(['page', 'is_employee']), ['is_employee' => 1])) }}" class="btn {{ request()->boolean('is_employee') ? 'btn-info' : 'btn-outline-info' }}">Khách nhân viên</a>
                </div>
            </form>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="table cust-table mb-0">
                        <thead>
                            <tr>
                                <th style="width:48px;">#</th>
                                <th style="min-width:360px;">Khách hàng</th>
                                <th>Mã Code cũ</th>
                                <th>Phụ trách và hạn giữ</th>
                                <th>Thông tin tạo</th>
                                <th style="width:140px;">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td class="text-muted" style="font-size:.78rem;">{{ $customer->id }}</td>
                                <td>
                                    <div class="cust-name">{{ $customer->name }}</div>
                                    @if($customer->is_employee)
                                        <div class="mt-1"><span class="badge-employee">Khách nhân viên</span></div>
                                    @endif
                                    @if($customer->dob)
                                        <div class="cust-dob">{{ $customer->dob->format('d/m/Y') }} - {{ $customer->dob->age }} tuổi</div>
                                    @endif
                                    @php
                                        $defaultAddr = $customer->addresses->firstWhere('is_default', 1) ?? $customer->addresses->first();
                                    @endphp
                                    <div class="cust-dob mt-1" style="font-size:.78rem;color:#64748b;">{{ $defaultAddr?->note ?: 'Chưa có địa chỉ mặc định' }}</div>
                                    <div class="cust-phone text-nowrap mt-1">{{ $customer->phone ?: '-' }}</div>
                                    <div class="cust-email text-truncate" style="max-width:220px;">{{ $customer->email ?: '-' }}</div>
                                </td>
                                <td>
                                    @if($customer->legacy_code)
                                        <span class="badge rounded-pill" style="background:#f0fdf4;color:#166534;font-size:.72rem;font-weight:600;">{{ $customer->legacy_code }}</span>
                                    @else
                                        <span class="text-muted" style="font-size:.78rem;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($customer->is_employee)
                                        <span class="text-muted" style="font-size:.78rem;">Không áp dụng cho khách nhân viên</span>
                                    @elseif($customer->isFree())
                                        <span class="badge-free" style="font-size:.7rem;">Có thể gán</span>
                                        @if($customer->assignedTo)
                                            <div class="sale-prev">Sale gần nhất: {{ $customer->assignedTo->name }}</div>
                                        @endif
                                    @else
                                        <div>Sale: <span class="sale-name">{{ optional($customer->assignedTo)->name ?? '-' }}</span></div>
                                        @if(($expiresAt = $customer->assignmentExpiresAt()))
                                            <div class="expires-date">Hạn: {{ $expiresAt->format('d/m/Y H:i') }}</div>
                                            <div class="expires-note">Chu kỳ {{ $customerFreeDays }} ngày</div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted">Người tạo: {{ optional($customer->user)->name ?? '-' }}</div>
                                    <div class="small text-muted">Ngày tạo: {{ optional($customer->created_at)?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', array_merge(['customer' => $customer->id], request()->query(), ['tab' => 'reports'])) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-bar-chart"></i> Báo cáo
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="cust-empty">
                                        <div style="font-weight:600;">Chưa có khách hàng phù hợp bộ lọc</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="cust-pagination d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div style="font-size:.8rem;color:#64748b;">
                        Hiển thị {{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }} / {{ number_format($customers->total()) }} khách hàng
                    </div>
                    <div>{{ $customers->appends(request()->except('page'))->links() }}</div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="position: sticky; top: 20px;">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                        <div class="fw-semibold text-muted small mb-0">THỐNG KÊ NHÂN VIÊN</div>
                        <div class="small text-muted" style="font-size:.8rem;">
                            Từ: <strong>{{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d/m/Y') : '—' }}</strong> 
                            — <strong>{{ request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d/m/Y') : '—' }}</strong>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: calc(100vh - 360px); overflow-y: auto;">
                        @forelse($employeeStats as $idx => $stat)
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:{{ $loop->odd ? '#fff' : '#f8faff' }};">
                                <div class="text-muted small fw-semibold" style="width:24px;text-align:center;">{{ $idx + 1 }}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" style="font-size:.9rem;color:#1e293b;">{{ $stat['employee_name'] }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary" style="font-size:.95rem;">{{ number_format($stat['customer_count']) }}</div>
                                    <div class="small text-muted" style="font-size:.7rem;">khách</div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted small">
                                Chưa có dữ liệu nhân viên
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
