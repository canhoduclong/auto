@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.accounting')
@section('title', 'Công nợ nhà cung cấp')

@section('content')
<div class="debt-page debt-collection-page">
    <header class="debt-page-header">
        <div><h1>Công nợ nhà cung cấp</h1>
            <div class="debt-updated">Google Sheet · nhatkymuahang @if($loadedAt) · Cập nhật {{ \Carbon\Carbon::parse($loadedAt)->format('d/m/Y H:i') }} @endif</div>
        </div>
        <div class="debt-header-actions">
            @if(auth()->user()->hasRole(['admin', 'account', 'accountant', 'accounting']))
                <a class="btn btn-light" href="{{ route('accounting.supplier-debts') }}">Công nợ hệ thống</a>
            @endif
            <form method="POST" action="{{ route('customer-debt.suppliers.refresh') }}">
                @csrf
                <button class="btn btn-primary">Cập nhật Google Sheet</button>
            </form>
        </div>
    </header>
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
    @include('supplier-debt.filters', ['includeSearch' => true])
    @if($loadedAt)
        <p class="small text-muted">Công nợ tạm tính bằng tiền mua trừ giá trị các dòng đã đánh dấu thanh toán. Cột chưa đối soát là tiền mua chưa rõ trạng thái thanh toán. Phát sinh được tính theo ngày mua. Số thanh toán trong nhóm này là giá trị các dòng mua trong kỳ đã đánh dấu trả, vì Sheet chưa có ngày trả tiền.</p>
    @endif
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h2 class="h6 mb-0">Danh sách nhà cung cấp</h2>
            <span class="small text-muted">{{ number_format($suppliers->total(), 0, ',', '.') }} nhà cung cấp</span>
        </div>
        <div class="table-responsive">
        <table class="table table-hover collection-table align-middle">
            <thead>
                <tr>
                    <th class="text-center" rowspan="2" scope="col">STT</th>
                    @include('supplier-debt.sort-header', ['field' => 'name', 'label' => 'Nhà cung cấp', 'rowspan' => 2])
                    <th colspan="5" class="text-center" scope="colgroup">Tổng lịch sử</th>
                    <th colspan="3" class="text-center" scope="colgroup">
                        Phát sinh {{ $from || $to ? ($from ? 'từ '.\Carbon\Carbon::parse($from)->format('d/m/Y') : '').($to ? ' đến '.\Carbon\Carbon::parse($to)->format('d/m/Y') : '') : 'toàn bộ kỳ' }}
                    </th>
                    <th rowspan="2" scope="col">Chi tiết</th>
                    <th class="text-center" rowspan="2" scope="col">Ghim</th>
                </tr>
                <tr>
                    @foreach(['count' => 'Số dòng mua', 'amount' => 'Tiền mua', 'paid' => 'Thanh toán', 'balance' => 'Công nợ tạm tính', 'unverified' => 'Chưa đối soát', 'period_amount' => 'Mua', 'period_paid' => 'Thanh toán theo dòng mua', 'period_balance' => 'Công nợ tạm tính'] as $field => $label)
                        @include('supplier-debt.sort-header', ['field' => $field, 'label' => $label])
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="text-center text-muted">{{ $suppliers->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $supplier['name'] }}</strong></td>
                    <td class="text-end">{{ $supplier['count'] }} dòng</td>
                    @foreach(['amount', 'paid', 'balance', 'unverified', 'period_amount', 'period_paid', 'period_balance'] as $field)
                        <td class="text-end text-nowrap {{ in_array($field, ['balance', 'period_balance'], true) && $supplier[$field] > 0 ? 'fw-semibold text-danger' : '' }}">{{ number_format($supplier[$field], 0, ',', '.') }} đ</td>
                    @endforeach
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('customer-debt.suppliers.show', ['supplier' => $supplier['key'], 'from' => $from, 'to' => $to]) }}">Chi tiết</a></td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('customer-debt.suppliers.pin', ['supplier' => $supplier['key']] + request()->only(['range', 'from', 'to', 'search', 'per_page', 'sort', 'direction'])) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="pinned" value="{{ $supplier['pinned'] ? '0' : '1' }}">
                            <button type="submit" class="btn btn-sm {{ $supplier['pinned'] ? 'btn-warning' : 'btn-light' }}"
                                aria-pressed="{{ $supplier['pinned'] ? 'true' : 'false' }}"
                                aria-label="{{ $supplier['pinned'] ? 'Bỏ ghim' : 'Ghim' }} {{ $supplier['name'] }}"
                                title="{{ $supplier['pinned'] ? 'Bỏ ghim' : 'Ghim lên đầu danh sách' }}">{{ $supplier['pinned'] ? '★' : '☆' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="text-muted text-center py-4">{{ $loadedAt ? 'Không có nhà cung cấp phù hợp bộ lọc.' : 'Bấm Cập nhật Google Sheet để tải dữ liệu nhà cung cấp.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="card-body collection-pagination">
            <span class="small text-muted">
                @if($suppliers->total()) Hiển thị {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} / {{ $suppliers->total() }} @endif
            </span>
            @if($suppliers->hasPages()){{ $suppliers->links() }}@endif
            <form method="GET">
                @foreach(request()->except(['page', 'per_page']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <select class="form-select form-select-sm" name="per_page" aria-label="Nhà cung cấp mỗi trang" onchange="this.form.submit()">
                    @foreach([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($suppliers->perPage() === $size)>{{ $size }} nhà cung cấp/trang</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
<link rel="stylesheet" href="{{ asset('css/customer-debt-report.css') }}?v={{ filemtime(public_path('css/customer-debt-report.css')) }}">
@endpush
