@php
    $differences = $voucherRegister['transferDifferences'];
    $handlingTypes = \App\Services\CustomerDebtManualAllocationService::TRANSFER_DIFFERENCE_TYPES;
    $allocator = app(\App\Services\CustomerDebtPaymentAllocation::class);
@endphp
<details class="border rounded p-3 mt-3" id="debt-voucher-differences" open>
    <summary class="fw-semibold">Chênh lệch hoán đổi</summary>
    <p class="small text-muted my-2">Ghi nhận riêng để theo dõi. Gán đơn hoặc phân loại tại đây chỉ lưu thông tin xử lý, không điều chỉnh công nợ hay phân bổ.</p>
    @error('difference')
        <p class="alert alert-warning" role="alert">{{ $message }}</p>
    @enderror
    @if($differences->isEmpty())
        <p class="small mb-0">Chưa ghi nhận chênh lệch.</p>
    @else
        @if($differences->contains(fn ($row) => empty($row['handling'])))
            <datalist id="debt-difference-bill-options">
                @foreach($selected['entries'] as $entry)
                    @if($entry['type'] === 'sale')
                        <option value="{{ $allocator->key($entry) }}">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }} · {{ $entry['order_code'] ?? '' }} · {{ $currency($entry['amount']) }}</option>
                    @endif
                @endforeach
            </datalist>
        @endif
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Ngày chuyển → Ngày nhận</th>
                        <th scope="col" class="text-end">Đã chuyển</th>
                        <th scope="col" class="text-end">Chênh lệch</th>
                        <th scope="col">Xử lý</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($differences as $difference)
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($difference['paid_date'])->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($difference['open_date'])->format('d/m/Y') }}</td>
                        <td class="text-end text-nowrap">{{ $currency($difference['transferred']) }}</td>
                        <td class="text-end text-nowrap">{{ $difference['difference'] > 0 ? 'Dư' : 'Thiếu' }} {{ $currency(abs($difference['difference'])) }}</td>
                        <td>
                            @if(!empty($difference['handling']))
                                <strong>{{ $handlingTypes[$difference['handling']] }}</strong>
                                @if($difference['bill_key']) <span>· {{ $difference['bill_key'] }}</span> @endif
                                @if($difference['note']) <div class="small">{{ $difference['note'] }}</div> @endif
                            @else
                                <details>
                                    <summary class="small">Chờ xử lý</summary>
                                    <form class="d-flex flex-wrap gap-2 mt-2" method="POST" action="{{ route('customer-debt.vouchers.classify-difference', $difference['id']) }}">
                                        @csrf
                                        @foreach($identity as $field => $value)
                                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                                        @endforeach
                                        <select class="form-select form-select-sm w-auto" name="handling" aria-label="Cách xử lý" required>
                                            <option value="">Chọn cách xử lý</option>
                                            @foreach($handlingTypes as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input class="form-control form-control-sm w-auto" name="bill_key" list="debt-difference-bill-options" maxlength="160" placeholder="Chọn đơn nếu gán vào đơn" aria-label="Đơn nhận chênh lệch">
                                        <input class="form-control form-control-sm w-auto" name="note" maxlength="500" placeholder="Ghi chú" aria-label="Ghi chú xử lý">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Lưu xử lý</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</details>
