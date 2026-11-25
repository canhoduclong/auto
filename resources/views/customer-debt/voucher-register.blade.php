@php
    $currency = fn ($amount) => number_format($amount, 0, ',', '.').' đ';
    $types = \App\Services\CustomerDebtVouchers::TYPES;
    $statuses = \App\Services\CustomerDebtVouchers::STATUSES;
    $identity = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $voucherUrl = fn ($row, $extra = []) => ['voucher' => $row['id']] + $extra + $identity;
    $editing = $voucherRegister['editing'];
    $latest = $voucherRegister['latest'];
    $rows = $voucherRegister['rows'];
    $purchases = $voucherRegister['purchases'];
    $selectedPurchases = $voucherRegister['selectedPurchases'];
    $today = today()->toDateString();
@endphp
<section class="debt-cycle-card mb-3" id="debt-vouchers" aria-labelledby="debt-vouchers-title">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="h6 mb-0" id="debt-vouchers-title">Phiếu thu và đề nghị thanh toán</h3>
        <a class="btn btn-sm btn-primary" href="{{ request()->fullUrlWithQuery(['new_voucher' => 'receipt', 'edit_voucher' => null, 'voucher_selected' => null]) }}#debt-voucher-editor">Tạo phiếu mới</a>
    </div>
    @if($latest)
        <p class="small">Số còn phải thu theo phiếu mới nhất <strong>{{ $currency($latest['remaining']) }}</strong> · {{ $latest['number'] }}</p>
    @endif

    @error('payment_transfer')
        <p class="alert alert-warning" role="alert">{{ $message }}</p>
    @enderror
    <div class="row g-3 mb-4" data-voucher-workspace>
        @unless($editing)
            <dialog class="border rounded shadow p-3" style="width:min(36rem, 95vw); max-height:85vh" data-voucher-picker aria-labelledby="debt-voucher-picker-title">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="h6 mb-0" id="debt-voucher-picker-title">Chọn Bill theo ngày</h4>
                    <form method="dialog"><button class="btn btn-sm btn-outline-secondary">Đóng</button></form>
                </div>
                <p class="small text-muted" data-voucher-picker-context></p>
                <label class="d-block mb-2">
                    <span class="form-label">Tìm theo ngày mua</span>
                    <input type="search" class="form-control form-control-sm" placeholder="dd/mm/yyyy" data-voucher-picker-search>
                </label>
                <div class="list-group" data-voucher-picker-results></div>
                <form method="POST" action="{{ route('customer-debt.vouchers.transfer-payment') }}" data-voucher-transfer-form hidden>
                    @csrf
                    @foreach($identity as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="revision" value="{{ $voucherRegister['revision'] }}">
                    <input type="hidden" name="paid_date">
                    <input type="hidden" name="open_date">
                </form>
            </dialog>
            <script type="application/json" data-voucher-picker-data>@json($voucherRegister['purchaseChoices'])</script>
        @endunless

        <div class="col-xl-6">@include('customer-debt.voucher-purchases')</div>
        <div class="col-xl-6">
    <details class="border rounded p-3" id="debt-voucher-editor" open>
        <summary class="fw-semibold">{{ $editing ? 'Cập nhật '.$editing['number'] : 'Lập phiếu mới' }}</summary>
        <form method="POST" action="{{ route('customer-debt.vouchers.save') }}" enctype="multipart/form-data" class="row g-3 mt-1" @unless($editing) data-voucher-create @endunless>
            @csrf
            @foreach($identity as $field => $value)
                <input type="hidden" name="{{ $field }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="revision" value="{{ $voucherRegister['revision'] }}">
            @if($editing)
                <input type="hidden" name="voucher_id" value="{{ $editing['id'] }}">
                <p class="small mb-0">{{ $types[$editing['type']] }} @if(empty($editing['purchase_days'])) · Chốt đến {{ \Carbon\Carbon::parse($editing['through'])->format('d/m/Y') }} @endif · Nợ chuyển sang {{ $currency($editing['carry']) }}</p>
            @else
                <label class="col-sm-6">
                    <span class="form-label">Loại phiếu</span>
                    <select name="type" class="form-select" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', request('new_voucher', 'receipt')) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-sm-6" data-voucher-cutoff @if($selectedPurchases->isNotEmpty()) hidden @endif>
                    <span class="form-label">Chốt công nợ đến ngày</span>
                    <input name="through" type="date" class="form-control" value="{{ old('through', $today) }}" min="{{ $latest['through'] ?? '' }}" max="{{ $today }}" required @disabled($selectedPurchases->isNotEmpty())>
                </label>
            @endif
            <div class="col-12">
                @if($editing && !empty($editing['purchase_days']))
                    <strong class="small">Ngày mua trên phiếu</strong>
                    <ul class="small mb-0">
                        @foreach($editing['purchase_days'] as $date => $amount)
                            <li>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · {{ $currency($amount) }}</li>
                        @endforeach
                    </ul>
                @elseif(!$editing)
                    <strong class="small">Ngày mua đã chọn</strong>
                    <ul class="list-unstyled small mb-0 mt-1" data-voucher-selected-list></ul>
                    <div data-voucher-purchase-fields></div>
                    <output class="small" aria-live="polite" data-voucher-purchase-total></output>
                @endif
            </div>
            <label class="col-sm-6">
                <span class="form-label">Số phiếu</span>
                <input name="number" class="form-control" maxlength="50" value="{{ old('number', $editing['number'] ?? '') }}" placeholder="Tự cấp nếu để trống">
            </label>
            <label class="col-sm-6">
                <span class="form-label">{{ $latest && (!$editing || $editing['sequence'] > 1) ? 'Công nợ phát sinh mới (đ)' : 'Công nợ đưa vào phiếu (đ)' }}</span>
                <input name="new_amount" type="number" min="0" max="999999999999999" step="1" class="form-control"
                    value="{{ old('new_amount', $editing['new_amount'] ?? '') }}" @required($editing) @readonly(!empty($editing['purchase_days'])) placeholder="{{ $voucherRegister['suggested'] }}" data-voucher-amount>
            </label>
            <label class="col-sm-6">
                <span class="form-label">Tiền đã thu cho phiếu này (đ)</span>
                <input name="paid" type="number" min="0" max="999999999999999" step="1" class="form-control" value="{{ old('paid', $editing['paid'] ?? 0) }}" required>
            </label>
            <label class="col-sm-6">
                <span class="form-label">Hình thức thu</span>
                <select name="method" class="form-select">
                    <option value="">Chưa ghi nhận</option>
                    <option value="cash" @selected(old('method', $editing['method'] ?? null) === 'cash')>Tiền mặt</option>
                    <option value="transfer" @selected(old('method', $editing['method'] ?? null) === 'transfer')>Chuyển khoản</option>
                </select>
            </label>
            <label class="col-md-6">
                <span class="form-label">Chứng từ thanh toán</span>
                <input name="documents[]" class="form-control" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple>
                <span class="small text-muted">Cần chứng từ khi ghi nhận đã thu đủ. Tối đa 10 tệp mỗi lần, 10 MB/tệp.</span>
            </label>
            <label class="col-md-6">
                <span class="form-label">Ghi chú</span>
                <textarea name="note" class="form-control" rows="2" maxlength="1500">{{ old('note', $editing['note'] ?? '') }}</textarea>
            </label>
            @if(!$editing)
                <div class="col-12 small text-muted">
                    Phiếu cộng số còn phải thu của các ngày đã chọn và số dư chuyển từ phiếu trước. Khi chưa chọn ngày mua, bạn có thể nhập công nợ hoặc để trống để lấy số liệu đến ngày chốt.
                    Tiền thu sau ngày chốt phiếu đầu cần ghi vào sổ phiếu một lần để tính số còn phải thu, kể cả khi khoản thu đã có trên Google Sheet.
                </div>
            @endif
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-primary">{{ $editing ? 'Lưu cập nhật' : 'Lưu phiếu mới' }}</button>
                @if($editing)
                    <a class="btn btn-sm btn-light" href="{{ request()->fullUrlWithQuery(['edit_voucher' => null, 'new_voucher' => null]) }}#debt-vouchers">Đóng cập nhật</a>
                @endif
            </div>
        </form>
    </details>
        </div>
    </div>
    @unless($editing)
        <script type="application/json" data-voucher-selected-data>{!! json_encode($selectedPurchases, JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
    @endunless

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr>
                <th scope="col">Số phiếu</th><th scope="col">Ngày mua / chốt</th><th scope="col">Trạng thái</th>
                <th scope="col" class="text-end">Nợ chuyển sang</th><th scope="col" class="text-end">Phải thu</th>
                <th scope="col" class="text-end">Đã thu</th><th scope="col" class="text-end">Còn lại</th>
                <th scope="col">Chứng từ</th><th scope="col">Thao tác</th>
            </tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td><strong>{{ $row['number'] }}</strong><div class="small text-muted">{{ $types[$row['type']] }}</div></td>
                    <td class="text-nowrap">
                        @if(count($row['purchase_days'] ?? []) > 1)
                            {{ count($row['purchase_days']) }} ngày mua
                        @else
                            {{ \Carbon\Carbon::parse(empty($row['purchase_days']) ? $row['through'] : array_key_first($row['purchase_days']))->format('d/m/Y') }}
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ ['paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'settled' => 'bg-secondary'][$row['status']] ?? 'bg-light text-dark' }}">{{ $statuses[$row['status']] }}</span>
                        @if($row['id'] !== $latest['id'] && $row['remaining'] > 0)<div class="small text-muted">Số dư đã chuyển tiếp</div>@endif
                    </td>
                    <td class="text-end text-nowrap">{{ $currency($row['carry']) }}</td>
                    <td class="text-end text-nowrap">{{ $currency($row['total']) }}</td>
                    <td class="text-end text-nowrap">{{ $currency($row['paid']) }}</td>
                    <td class="text-end text-nowrap">{{ $currency($row['remaining']) }}</td>
                    <td>
                        @forelse($row['documents'] as $file)
                            <div><a href="{{ route('customer-debt.vouchers.documents.download', $voucherUrl($row, ['document' => $file['id']])) }}">{{ $file['name'] }}</a></div>
                        @empty
                            <span class="text-muted">Chưa có</span>
                        @endforelse
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['edit_voucher' => $row['id'], 'new_voucher' => null]) }}#debt-voucher-editor">Cập nhật</a>
                        <a class="btn btn-sm btn-light" target="_blank" rel="noopener" href="{{ route('customer-debt.vouchers.print', $voucherUrl($row)) }}">In phiếu</a>
                        <form class="d-inline" method="POST" action="{{ route('customer-debt.vouchers.void', ['voucher' => $row['id']]) }}"
                            onsubmit="return confirm('Gỡ phiếu này khỏi sổ phiếu? Các ngày mua đủ điều kiện sẽ được chọn lại để lập phiếu mới.')">
                            @csrf
                            @method('DELETE')
                            @foreach($identity as $field => $value)
                                <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="revision" value="{{ $voucherRegister['revision'] }}">
                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Gỡ phiếu {{ $row['number'] }}">Gỡ phiếu</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">Chưa có phiếu trong danh sách này.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</section>

@once
    @push('scripts')
        <script src="{{ asset('js/customer-debt-voucher-selector.js') }}?v={{ filemtime(public_path('js/customer-debt-voucher-selector.js')) }}"></script>
    @endpush
@endonce
