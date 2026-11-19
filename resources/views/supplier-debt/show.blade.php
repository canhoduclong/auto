@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.accounting')
@section('title', 'Đối chiếu công nợ nhà cung cấp')

@section('content')
<div class="debt-page debt-collection-page">
    <header class="debt-page-header">
        <div><h1>{{ $name }}</h1><div class="debt-updated">Đối chiếu công nợ nhà cung cấp · nhatkymuahang</div></div>
        <a href="{{ route('customer-debt.suppliers.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-light">Danh sách nhà cung cấp</a>
    </header>
    @include('supplier-debt.filters')
    <div class="collection-summary-panel mb-3">
        <div class="collection-summary-metrics supplier-summary-metrics">
            @foreach(['Tổng mua' => $allTotals['amount'], 'Tổng thanh toán' => $allTotals['paid'], 'Mua phát sinh trong kỳ' => $periodTotals['amount'], 'Thanh toán trong kỳ' => $filtered ? null : $allTotals['paid'], 'Công nợ phát sinh tạm tính' => $periodTotals['balance']] as $label => $amount)
                <div><span>{{ $label }}</span><strong>{{ $amount === null ? 'Chưa xác định' : number_format($amount, 0, ',', '.').' đ' }}</strong></div>
            @endforeach
        </div>
        <p>Thanh toán lấy theo các dòng mua đã được đánh dấu trả đủ. Sheet chưa ghi ngày trả. Công nợ phát sinh tạm tính bằng số tiền của các dòng mua trong kỳ trừ phần đã đánh dấu trả trên chính các dòng đó.</p>
        @if($periodTotals['unverified'] !== 0)<p class="text-warning-emphasis">Tiền mua trong kỳ chưa rõ trạng thái thanh toán <strong>{{ number_format($periodTotals['unverified'], 0, ',', '.') }} đ</strong></p>@endif
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">Thống kê mặt hàng trong kỳ</div>
        <div class="table-responsive">
            <table class="table table-hover collection-table">
                <thead><tr><th>Mặt hàng</th><th>Biến thể</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng (kg)</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr><td>{{ $item['item'] }}</td><td>{{ $item['variant'] ?: '—' }}</td>
                        @foreach(['quantity', 'weight', 'price'] as $field)
                            <td class="text-end text-nowrap">{{ $item[$field] === null ? '—' : rtrim(rtrim(number_format($item[$field], 3, ',', '.'), '0'), ',') }}{{ $field === 'price' && $item[$field] !== null ? ' đ' : '' }}</td>
                        @endforeach
                        <td class="text-end text-nowrap">{{ number_format($item['amount'], 0, ',', '.') }} đ</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Không có mặt hàng trong kỳ.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="card-body">{{ $items->links() }}</div>@endif
    </div>

    <div class="row g-3 align-items-start">
        <section class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between"><strong>Mua</strong><span class="text-muted">{{ $purchases->total() }} dòng</span></div>
                <div class="table-responsive"><table class="table table-hover collection-table">
                    <thead><tr><th>Ngày mua</th><th>Mặt hàng</th><th>Trạng thái</th><th class="text-end">Thành tiền</th></tr></thead>
                    <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($purchase['date'])->format('d/m/Y') }}<div class="small text-muted">Dòng {{ $purchase['row'] }}</div></td>
                            <td>{{ $purchase['item'] }} @if($purchase['variant'])<small class="text-muted">{{ $purchase['variant'] }}</small>@endif</td>
                            <td>{{ ['paid' => 'Đã đánh dấu trả', 'unpaid' => 'Chưa thanh toán', 'unknown' => 'Chưa đối soát'][$purchase['payment_status']] }}</td>
                            <td class="text-end text-nowrap">{{ number_format($purchase['amount'], 0, ',', '.') }} đ</td>
                        </tr>
                    @empty<tr><td colspan="4" class="text-center text-muted">Không có dòng mua trong kỳ.</td></tr>@endforelse
                    </tbody>
                </table></div>
                @if($purchases->hasPages())<div class="card-body">{{ $purchases->links() }}</div>@endif
            </div>
        </section>
        <section class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between"><strong>Thanh toán</strong><span class="text-muted">{{ $payments->total() }} dòng đã đánh dấu</span></div>
                <div class="px-3 pt-2 text-muted small">Toàn bộ các dòng đã đánh dấu thanh toán. Ngày bên dưới là ngày mua để đối chiếu.</div>
                <div class="table-responsive"><table class="table table-hover collection-table">
                    <thead><tr><th>Ngày mua tham chiếu</th><th>Mặt hàng / hóa đơn</th><th class="text-end">Đã thanh toán</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($payment['date'])->format('d/m/Y') }}<div class="small text-muted">Dòng {{ $payment['row'] }}</div></td>
                            <td>{{ $payment['item'] }} @if($payment['invoice'])<div class="small text-muted">HĐ {{ $payment['invoice'] }}</div>@endif</td>
                            <td class="text-end text-success text-nowrap">{{ number_format($payment['amount'], 0, ',', '.') }} đ</td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted">Chưa có dòng được đánh dấu thanh toán.</td></tr>@endforelse
                    </tbody>
                </table></div>
                @if($payments->hasPages())<div class="card-body">{{ $payments->links() }}</div>@endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
<link rel="stylesheet" href="{{ asset('css/customer-debt-report.css') }}?v={{ filemtime(public_path('css/customer-debt-report.css')) }}">
<style>@media (min-width: 1200px) { .supplier-summary-metrics { grid-template-columns: repeat(5, minmax(0, 1fr)); } }</style>
@endpush
