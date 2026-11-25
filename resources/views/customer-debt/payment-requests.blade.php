@extends((session('active_role') === 'warehouse' || !auth()->user()->canManageCustomerDebt()) ? 'layouts.warehouse' : (auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.accounting'))
@section('title', 'Đề nghị Thanh toán, Thu')

@section('content')
<section class="card">
    <div class="card-body">
        <h1 class="h5 mb-3">Đề nghị Thanh toán, Thu</h1>
        <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Lọc theo ngày lập">
            @foreach(['all' => 'Tất cả', 'yesterday' => 'Hôm qua', 'last3' => '3 ngày gần nhất'] as $key => $label)
                @php($active = $period === $key && ($key !== 'all' || (!$from && !$to)))
                <a class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-primary' }}"
                    @if($active) aria-current="true" @endif
                    href="{{ route('customer-debt.payment-requests', ['period' => $key, 'q' => $search ?: null]) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('customer-debt.payment-requests') }}" class="row g-2 align-items-end mb-3">
            <label class="col-sm-6 col-lg-4">Tên khách hàng
                <input class="form-control mt-1" name="q" value="{{ $search }}" maxlength="150" type="search">
            </label>
            <label class="col-sm-3 col-lg-2">Từ ngày lập
                <input class="form-control mt-1" type="date" name="from" value="{{ $from }}">
            </label>
            <label class="col-sm-3 col-lg-2">Đến ngày lập
                <input class="form-control mt-1" type="date" name="to" value="{{ $to }}">
            </label>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Lọc</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Ngày lập</th>
                        <th scope="col">Số đề nghị</th>
                        <th scope="col">Khách hàng</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" class="text-end">Phải thu</th>
                        <th scope="col" class="text-end">Đã thu</th>
                        <th scope="col" class="text-end">Còn lại</th>
                        <th scope="col">In chứng từ</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php($printQuery = ['voucher' => $row['id'], 'from_register' => 1] + $row['customer_query'])
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($row['issued_date'])->format('d/m/Y') }}</td>
                        <td class="text-nowrap fw-semibold">{{ $row['number'] }}</td>
                        <td>{{ $row['customer_name'] }}</td>
                        <td>{{ \App\Services\CustomerDebtVouchers::STATUSES[$row['status']] }}</td>
                        @foreach(['total', 'paid', 'remaining'] as $key)
                            <td class="text-end text-nowrap">{{ number_format($row[$key], 0, ',', '.') }} đ</td>
                        @endforeach
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('customer-debt.vouchers.print', $printQuery) }}">In đề nghị</a>
                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('customer-debt.vouchers.print-receipt', $printQuery) }}">In phiếu thu</a>
                                @if(auth()->user()->canManageCustomerDebt() && session('active_role') !== 'warehouse')
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('customer-debt.index', $row['customer_query'] + ['debt_section' => 'vouchers', 'edit_voucher' => $row['id'], 'window' => 'all', 'tab' => 'all']) }}#debt-voucher-paid">Ghi nhận tiền về</a>
                                    @if($row['paid'] > 0)
                                        <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('customer-debt.vouchers.print-payment-confirmation', $printQuery) }}">In xác nhận đã nhận tiền</a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Không có đề nghị thanh toán phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
</section>
@endsection
