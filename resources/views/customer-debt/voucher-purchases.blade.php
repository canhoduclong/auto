<section class="border rounded h-100" id="debt-voucher-purchases" aria-labelledby="debt-voucher-purchases-title">
    <header class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="h6 mb-0" id="debt-voucher-purchases-title">Mua hàng</h4>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small text-muted">{{ $purchases->total() }} ngày</span>
            <select class="form-select form-select-sm w-auto" aria-label="Số ngày mua hàng mỗi trang" data-voucher-purchase-size>
                @foreach([20, 50, 100] as $size)
                    <option value="{{ request()->fullUrlWithQuery(['voucher_purchases_size' => $size, 'voucher_purchases_page' => 1]) }}#debt-voucher-purchases" @selected($purchases->perPage() === $size)>{{ $size }} ngày/trang</option>
                @endforeach
            </select>
        </div>
    </header>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr>
                <th scope="col">Ngày mua</th>
                <th scope="col" class="text-end">Tiền hàng</th>
                <th scope="col" class="text-end">Phải thu</th>
                <th scope="col" class="text-end">Đã phân bổ</th>
                <th scope="col" class="text-end">Còn phải thu</th>
                <th scope="col">Chọn</th>
            </tr></thead>
            <tbody>
            @forelse($purchases as $day)
                @php($label = \Carbon\Carbon::parse($day['date'])->format('d/m/Y'))
                <tr>
                    <td class="text-nowrap">{{ $label }}<div class="small text-muted">{{ $day['count'] }} dòng mua</div></td>
                    <td class="text-end text-nowrap">{{ $currency($day['sales']) }}</td>
                    <td class="text-end text-nowrap">{{ $currency($day['due']) }}</td>
                    <td class="text-end text-nowrap">{{ $currency($day['paid']) }}</td>
                    <td class="text-end text-nowrap {{ $day['remaining'] > 0 ? 'text-danger' : '' }}">{{ $currency($day['remaining']) }}</td>
                    <td class="text-nowrap">
                        @if($day['covered'])
                            <span class="small text-muted">Đã đưa vào phiếu</span>
                        @elseif($day['selectable'] && $selectPurchases)
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                data-voucher-date="{{ $day['date'] }}" data-amount="{{ $day['remaining'] }}"
                                aria-label="Thêm ngày {{ $label }} vào phiếu" aria-pressed="false">Thêm</button>
                        @elseif(!$day['selectable'])
                            <span class="small text-muted">Đã đủ</span>
                            @if(!$editing && $day['paid'] > 0)
                                <button type="button" class="btn btn-sm btn-link" data-voucher-transfer="{{ $day['date'] }}" data-paid="{{ $day['paid'] }}">Hoán đổi</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Chưa có giao dịch mua hàng.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($purchases->hasPages())
        <div class="p-3" data-voucher-purchase-pagination>{{ $purchases->links() }}</div>
    @endif
</section>
