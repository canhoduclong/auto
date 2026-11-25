@extends(accounting_layout())

@section('title', 'Báo cáo tài sản')

@section('accounting_content')
@php
    $money = fn ($value) => number_format($value, 0, ',', '.').' đ';
    $categories = \App\Http\Controllers\CompanyAssetController::CATEGORIES;
    $statuses = \App\Http\Controllers\CompanyAssetController::ASSET_STATUSES;
    $sharedFilters = request()->only(['from', 'to', 'partner_id', 'contract_id', 'q']);
@endphp
@include('accounting.assets.feedback')

<section class="card rounded-3">
    <div class="card-body">
        <nav class="nav nav-tabs mb-3" aria-label="Loại báo cáo tài sản">
            @foreach(['assets' => 'Giá trị ghi nhận', 'payments' => 'Thực chi theo hợp đồng'] as $key => $label)
                <a href="{{ accounting_route('assets.report', ['mode' => $key] + $sharedFilters) }}"
                   class="nav-link{{ $mode === $key ? ' active' : '' }}" @if($mode === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ accounting_route('assets.report') }}" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <label class="col-md-6 col-xl-4"><span class="form-label">Tìm kiếm</span>
                <input class="form-control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150"
                    placeholder="{{ $mode === 'assets' ? 'Tên, mã tài sản hoặc số hợp đồng' : 'Nội dung chi, mã tham chiếu hoặc số hợp đồng' }}">
            </label>
            <label class="col-md-6 col-xl-4"><span class="form-label">Đối tác</span>
                <select class="form-select" name="partner_id">
                    <option value="">Tất cả đối tác</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((int) ($filters['partner_id'] ?? 0) === (int) $partner->id)>{{ $partner->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="col-md-6 col-xl-4"><span class="form-label">Hợp đồng</span>
                <select class="form-select" name="contract_id">
                    <option value="">Tất cả hợp đồng</option>
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->id }}" @selected((int) ($filters['contract_id'] ?? 0) === (int) $contract->id)>{{ $contract->number }} · {{ $contract->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="col-sm-6 col-xl-3"><span class="form-label">{{ $mode === 'assets' ? 'Ghi sổ từ ngày' : 'Thanh toán từ ngày' }}</span>
                <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </label>
            <label class="col-sm-6 col-xl-3"><span class="form-label">Đến ngày</span>
                <input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </label>
            @if($mode === 'assets')
                <label class="col-sm-6 col-xl-3"><span class="form-label">Nhóm tài sản</span>
                    <select class="form-select" name="category">
                        <option value="">Tất cả nhóm</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-sm-6 col-xl-3"><span class="form-label">Trạng thái</span>
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <label class="col-sm-6 col-xl-3"><span class="form-label">Loại khoản chi</span>
                    <select class="form-select" name="kind">
                        <option value="">Tất cả khoản chi</option>
                        @foreach(['deposit' => 'Tiền cọc', 'installment' => 'Đợt thanh toán', 'unassigned' => 'Chưa phân đợt'] as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['kind'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary">Lọc báo cáo</button>
                <a href="{{ accounting_route('assets.report', ['mode' => $mode]) }}" class="btn btn-light">Xóa lọc</a>
            </div>
        </form>

        <div class="border rounded-3 overflow-hidden">
            <div class="px-3 py-3 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-1">{{ $title }}</h2>
                    <span class="small text-muted">{{ $rows->total() }} dòng · Tổng theo bộ lọc</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <strong class="fs-5">{{ $money($total) }}</strong>
                    <a class="btn btn-sm btn-outline-primary" href="{{ accounting_route('assets.report', ['export' => 'xlsx'] + $filters) }}">Xuất Excel</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th scope="col" class="text-center">STT</th><th scope="col">Nội dung</th><th scope="col" class="text-end">Số tiền (VNĐ)</th>
                    </tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>
                                <td>
                                    <a href="{{ $mode === 'assets'
                                        ? accounting_route('assets.items.show', ['asset' => $row->id])
                                        : accounting_route('assets.contracts.show', ['contract' => $row->contract_id]).'#asset-payments' }}">{{ $row->content }}</a>
                                    <div class="small text-muted">
                                        {{ implode(' · ', array_filter([
                                            $row->reference,
                                            $row->report_date ? \Carbon\Carbon::parse($row->report_date)->format('d/m/Y') : null,
                                            $row->contract_number,
                                            $row->partner_name,
                                            $mode === 'assets' ? ($categories[$row->category] ?? null) : null,
                                        ])) }}
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">{{ $row->amount === null ? '—' : $money($row->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Không có dữ liệu phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
    </div>
</section>
@endsection
