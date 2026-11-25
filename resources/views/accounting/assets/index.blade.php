@extends(accounting_layout())

@section('title', 'Quản lý tài sản')

@section('accounting_content')
@php
    $tabs = ['contracts' => 'Hợp đồng', 'partners' => 'Đơn vị đối tác', 'assets' => 'Tài sản'];
    $status = $tab === 'contracts'
        ? \App\Http\Controllers\CompanyAssetController::CONTRACT_STATUSES
        : \App\Http\Controllers\CompanyAssetController::ASSET_STATUSES;
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $money = fn ($value) => number_format($value, 0, ',', '.').' đ';
@endphp
@include('accounting.assets.feedback')

<section class="card rounded-3">
    <header class="card-body pb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $tabs[$tab] }}</h2>
                <p class="small text-muted mb-0">{{ $rows->total() }} kết quả</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ accounting_route('assets.ai.index') }}">Bản nháp ChatGPT</a>
                <a href="{{ $tab === 'partners' ? accounting_route('assets.create', ['kind' => 'partners']) : accounting_route('assets.new') }}" class="btn btn-primary">
                    {{ $tab === 'partners' ? 'Thêm đối tác' : 'Tạo tài sản / HĐ' }}
                </a>
            </div>
        </div>
        <nav class="nav nav-tabs" aria-label="Danh mục quản lý tài sản">
            @foreach($tabs as $key => $label)
                <a href="{{ accounting_route('assets.index', ['tab' => $key]) }}" class="nav-link{{ $tab === $key ? ' active' : '' }}"
                    @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>
    <div class="card-body">
        <form method="GET" action="{{ accounting_route('assets.index') }}" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="{{ $tab === 'contracts' ? 'col-md-6 col-xl-3' : 'col-md-6' }}">
                <span class="form-label">Tìm kiếm</span>
                <input type="search" name="q" maxlength="150" value="{{ $filters['q'] ?? '' }}" class="form-control"
                    placeholder="{{ ['contracts' => 'Số, tên hợp đồng hoặc đối tác', 'partners' => 'Tên, mã số thuế, điện thoại', 'assets' => 'Mã, tên hoặc vị trí tài sản'][$tab] }}">
            </label>
            @if($tab === 'contracts')
                <label class="col-md-6 col-xl-3">
                    <span class="form-label">Đối tác</span>
                    <select class="form-select" name="partner_id">
                        <option value="">Tất cả đối tác</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((int) ($filters['partner_id'] ?? 0) === $partner->id)>{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-sm-6 col-xl-2"><span class="form-label">Ký từ ngày</span>
                    <input name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </label>
                <label class="col-sm-6 col-xl-2"><span class="form-label">Ký đến ngày</span>
                    <input name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </label>
            @endif
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-primary">Lọc</button>
                @if(array_filter($filters, fn ($key) => $key !== 'tab', ARRAY_FILTER_USE_KEY))
                    <a href="{{ accounting_route('assets.index', ['tab' => $tab]) }}" class="btn btn-light">Xóa lọc</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    @if($tab === 'contracts')
                        <th scope="col">Số hợp đồng</th><th scope="col">Tên hợp đồng</th><th scope="col">Đối tác</th>
                        <th scope="col">Ngày ký</th><th scope="col">Ngày hết hạn</th><th scope="col" class="text-end">Giá trị</th><th scope="col">Trạng thái</th>
                    @elseif($tab === 'partners')
                        <th scope="col">Đơn vị đối tác</th><th scope="col">Mã số thuế</th><th scope="col">Điện thoại</th><th scope="col">Email</th><th scope="col">Địa chỉ</th>
                    @else
                        <th scope="col">Mã tài sản</th><th scope="col">Tên tài sản / khoản chi</th><th scope="col">Vị trí</th><th scope="col">Hợp đồng</th><th scope="col" class="text-end">Giá trị ghi nhận</th><th scope="col">Trạng thái</th>
                    @endif
                    <th scope="col" class="text-end">Thao tác</th>
                </tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @if($tab === 'contracts')
                                <th scope="row" class="text-nowrap"><a href="{{ accounting_route('assets.contracts.show', ['contract' => $row->id]) }}">{{ $row->number }}</a></th>
                                <td>{{ $row->title }}</td><td>{{ $row->partner_name }}</td>
                                <td class="text-nowrap">{{ $date($row->signed_on) }}</td><td class="text-nowrap">{{ $date($row->expires_on) }}</td>
                                <td class="text-end text-nowrap">{{ $money($row->value) }}</td>
                                <td><span class="badge text-bg-light">{{ $status[$row->status] }}</span></td>
                            @elseif($tab === 'partners')
                                <th scope="row">{{ $row->name }}</th><td>{{ $row->tax_code ?: '—' }}</td>
                                <td>{{ $row->phone ?: '—' }}</td><td>{{ $row->email ?: '—' }}</td><td>{{ $row->address ?: '—' }}</td>
                            @else
                                <th scope="row" class="text-nowrap"><a href="{{ accounting_route('assets.items.show', ['asset' => $row->id]) }}">{{ $row->code }}</a></th>
                                <td>{{ $row->name }}
                                    @if($row->category)<div class="small text-muted">{{ \App\Http\Controllers\CompanyAssetController::CATEGORIES[$row->category] }}</div>@endif
                                </td>
                                <td>{{ $row->location ?: '—' }}</td>
                                <td>@if($row->contract_id)<a href="{{ accounting_route('assets.contracts.show', ['contract' => $row->contract_id]) }}">{{ $row->contract_number }}</a>@else — @endif</td>
                                <td class="text-end text-nowrap">{{ $row->value === null ? '—' : $money($row->value) }}</td>
                                <td><span class="badge text-bg-light">{{ $status[$row->status] }}</span></td>
                            @endif
                            <td class="text-end text-nowrap">
                                @if($tab === 'contracts')
                                    <a class="btn btn-sm btn-outline-primary" href="{{ accounting_route('assets.contracts.show', ['contract' => $row->id]) }}">Chi tiết</a>
                                @else
                                    @if($tab === 'partners')
                                        <a class="btn btn-sm btn-light" href="{{ accounting_route('assets.index', ['tab' => 'contracts', 'partner_id' => $row->id]) }}">Hợp đồng</a>
                                    @else
                                        <a class="btn btn-sm btn-light" href="{{ accounting_route('assets.items.show', ['asset' => $row->id]) }}">Chi tiết</a>
                                    @endif
                                    <a class="btn btn-sm btn-outline-primary" href="{{ accounting_route('assets.edit', ['kind' => $tab, 'record' => $row->id]) }}">Cập nhật</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ ['contracts' => 8, 'assets' => 7, 'partners' => 6][$tab] }}" class="text-center text-muted py-4">Chưa có kết quả.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
    </div>
</section>
@endsection
