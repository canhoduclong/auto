@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.accounting')
@section('title', 'Đồng bộ Google Sheet')
@section('content')
<div class="container-fluid py-3">
    <header class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Đồng bộ Google Sheet</h1>
            <p class="text-muted small mb-0">Doanh thu đầu kỳ lấy từ Sheet trước 15/09/2026. Từ 15/09/2026 lấy Bill hệ thống đã được kế toán xác nhận theo ngày giao.</p>
            <p class="text-muted small mb-0">Thanh toán và giảm trừ lấy từ Google Sheet. Công nợ được tính trên các nguồn đã ghép.</p>
        </div>
        <form method="POST" action="{{ route('customer-debt.sheet-opening.refresh') }}">
            @csrf
            <button class="btn btn-primary">Cập nhật từ Google Sheet</button>
        </form>
    </header>

    @foreach(['success' => 'success', 'error' => 'danger'] as $message => $color)
        @if(session($message))<div class="alert alert-{{ $color }}">{{ session($message) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if($snapshot)
        <details class="mb-3 small">
            <summary>Lịch sử đồng bộ · Gần nhất {{ \Carbon\Carbon::parse($snapshot['loaded_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</summary>
            <ul class="mt-2">
                @foreach($snapshot['runs'] as $run)
                    <li>{{ \Carbon\Carbon::parse($run['at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }} · {{ $run['sales'] }} dòng mua · {{ $run['payments'] }} khoản thu · {{ $run['unchanged'] ? 'Không thay đổi' : 'Đã cập nhật' }}</li>
                @endforeach
            </ul>
        </details>
    @else
        <p class="small text-muted">Bấm Cập nhật từ Google Sheet để tải dữ liệu đầu kỳ và thanh toán.</p>
    @endif

    <nav class="nav nav-tabs mb-3" aria-label="Dữ liệu đồng bộ">
        @foreach(['purchases' => 'Mua hàng', 'payments' => 'Thanh toán', 'customers' => 'Khách hàng'] as $key => $label)
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif
                href="{{ route('customer-debt.sheet-opening.index', ['tab' => $key, 'search' => request('search')]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input class="form-control w-auto" name="search" maxlength="150" value="{{ request('search') }}" aria-label="Tên hoặc mã khách" placeholder="Tên hoặc mã khách">
        <button class="btn btn-outline-primary">Lọc</button>
    </form>

    @if($tab === 'customers')
        <div class="row g-3">
            <section class="col-12 col-xl-7">
                <div class="card h-100 p-3">
                    <h2 class="h6">Khách hàng hệ thống</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Khách hàng</th><th class="text-end">Doanh thu đầu kỳ</th><th class="text-end">Doanh thu từ 15/09</th><th class="text-end">Thanh toán</th><th class="text-end">Giảm trừ</th><th class="text-end">Công nợ</th></tr></thead>
                            <tbody>
                                @forelse($rows as $customer)
                                    <tr>
                                        <td>{{ $customer->name }} @if($customer->customer_code)<small class="text-muted d-block">{{ $customer->customer_code }}</small>@endif</td>
                                        @foreach([($opening[$customer->id] ?? 0), ($systemSales[$customer->id] ?? 0), ($paid[$customer->id] ?? 0), ($gifts[$customer->id] ?? 0), ($opening[$customer->id] ?? 0) + ($systemSales[$customer->id] ?? 0) - ($paid[$customer->id] ?? 0) - ($gifts[$customer->id] ?? 0)] as $amount)
                                            <td class="text-end text-nowrap">{{ number_format($amount, 0, ',', '.') }} đ</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="6">Không tìm thấy khách hàng.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $rows->links() }}
                </div>
            </section>
            <section class="col-12 col-xl-5">
                <div class="card h-100 p-3">
                    <h2 class="h6">Khách Google Sheet chờ ghép · {{ $pending->total() }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Khách Google Sheet</th><th>Ghép hoặc tạo khách</th></tr></thead>
                            <tbody>
                                @forelse($pending as $source)
                                    <tr>
                                        <td>{{ $source['name'] }} @if($source['code'])<small class="text-muted d-block">{{ $source['code'] }}</small>@endif</td>
                                        <td>
                                            <form method="POST" action="{{ route('customer-debt.sheet-opening.link') }}" class="d-flex flex-wrap gap-1">
                                                @csrf
                                                <input type="hidden" name="source_key" value="{{ $source['key'] }}">
                                                <select name="customer_id" class="form-select form-select-sm" aria-label="Khách hệ thống để ghép với {{ $source['name'] }}" required>
                                                    <option value="">Chọn khách hệ thống</option>
                                                    @foreach($customers as $target)
                                                        <option value="{{ $target->id }}">{{ $target->name }} · {{ $target->customer_code ?: ($target->phone ?: '#'.$target->id) }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-outline-primary" name="action" value="link">Ghép khách</button>
                                                @can('create', \App\Models\Customer::class)
                                                    <button class="btn btn-sm btn-outline-secondary" name="action" value="create" formnovalidate>Tạo khách mới</button>
                                                @endcan
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2">Không có khách nguồn chờ ghép trong bộ lọc.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $pending->links() }}
                </div>
            </section>
        </div>
    @else
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Ngày</th><th>Khách hàng</th><th>Nội dung</th><th>Nguồn</th><th class="text-end">Số tiền</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row['date'] }}</td>
                                <td>{{ $row['customer'] }} @unless($row['linked'])<small class="d-block text-muted">Chờ ghép hoặc đối chiếu</small>@endunless</td>
                                <td>{{ $row['note'] ?? '' }}</td>
                                <td>{{ $row['source_sheet'] ?? '' }} · {{ $row['row'] }}</td>
                                <td class="text-end text-nowrap">{{ number_format($row['amount'], 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Không có giao dịch phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
