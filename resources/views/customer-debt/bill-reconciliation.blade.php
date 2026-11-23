@php
    $detailQuery = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $money = fn ($amount) => number_format($amount, 0, ',', '.').' đ';
    $reopenedBatch = session('bill_reopened') ?? [];
    $date = fn ($value) => \Carbon\Carbon::parse($value)->format('d/m/Y');
@endphp
@if($billGroups && $billGroups->isNotEmpty())
    <details class="debt-cycle-card debt-bill-history mb-3" open>
        <summary class="debt-bill-history-title">
            <strong>Hoàn thành</strong>
            <span class="small text-muted ms-2">{{ $billGroups->count() }} lần ghép đã lưu</span>
        </summary>
        <div class="table-responsive border rounded mt-3">
            <table class="table table-sm align-middle mb-0 debt-bill-history-table">
                <caption class="visually-hidden">Danh sách các lần ghép Bill đã lưu</caption>
                <thead>
                    <tr>
                        <th scope="col">Lần ghép</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Bill</th>
                        <th scope="col">Khoản thu</th>
                        <th scope="col" class="text-end">Đã phân bổ</th>
                        <th scope="col" class="text-end">Thu thiếu</th>
                        <th scope="col" class="text-end">Thu dư</th>
                        @if($canManageAllocation)<th scope="col" class="text-end">Thao tác</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($billGroups->sortByDesc('number') as $id => $group)
                        <tr>
                            <th scope="row" class="text-nowrap">
                                #{{ $group['number'] }}
                                @if($group['recorded_at'])
                                    <time datetime="{{ $group['recorded_at'] }}" class="small fw-normal text-muted d-block">{{ \Carbon\Carbon::parse($group['recorded_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</time>
                                @endif
                            </th>
                            <td>
                                <span class="badge rounded-pill {{ $group['valid'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                    {{ $group['valid'] ? 'Đã phân bổ' : 'Cần kiểm tra lại' }}
                                </span>
                            </td>
                            @foreach(['bill_labels' => 'Bill', 'payment_keys' => 'khoản thu'] as $field => $label)
                                <td class="debt-bill-history-references">
                                    @if(count($group[$field]) > 1)
                                        <details>
                                            <summary class="text-primary text-nowrap">{{ count($group[$field]) }} {{ $label }}</summary>
                                            <ul class="list-unstyled mb-0 mt-1 small">
                                                @foreach($group[$field] as $reference)<li>{{ $reference }}</li>@endforeach
                                            </ul>
                                        </details>
                                    @else
                                        <span class="small">{{ $group[$field][0] ?? '—' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end text-nowrap fw-semibold">{{ $money($group['amount']) }}</td>
                            <td class="text-end text-nowrap {{ ($group['shortfall'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ isset($group['shortfall']) ? $money($group['shortfall']) : '—' }}
                            </td>
                            <td class="text-end text-nowrap {{ ($group['surplus'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                                {{ isset($group['surplus']) ? $money($group['surplus']) : '—' }}
                            </td>
                            @if($canManageAllocation)
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('customer-debt.bill-allocations') }}" onsubmit="return confirm('Gỡ lần ghép này và trả số tiền về danh sách chờ đối soát?')">
                                        @csrf
                                        @include('customer-debt.payment-allocation-fields')
                                        <input type="hidden" name="cancel_group" value="{{ $id }}">
                                        <button class="btn btn-sm btn-outline-danger" aria-label="Gỡ lần ghép số {{ $group['number'] }}">Gỡ ghép</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
@foreach(array_reverse($billPeriods, true) as $period)
    @php
        $bills = $period['bills'];
        $receipts = $period['payments'];
        $open = $bills->where('remaining', '>', 0);
        $matched = $bills->where('remaining', 0);
        $matchedReceipts = $receipts->where('matched', true);
        $pending = $receipts->filter(fn ($receipt) => $receipt['pending'] !== 0 || $receipt['issue']);
        $exactPairs = $period['exact_pairs'];
        $residualBills = $open->filter(fn ($bill) => $bill['paid'] > 0 || $bill['in_batch']);
        $residualReceipts = $pending->where('pending', '>', 0)->filter(fn ($receipt) => $receipt['allocated'] > 0 || $receipt['in_batch']);
        $restoreBatch = isset($reopenedBatch['period_from'])
            ? $reopenedBatch['period_from'] === $period['from']
            : (bool) array_intersect($reopenedBatch['payment_keys'] ?? [], $receipts->keys()->all());
        $selectedBills = $restoreBatch ? ($reopenedBatch['bill_keys'] ?? $open
            ->filter(fn ($bill) => array_intersect(array_keys($bill['lines']->all()), $reopenedBatch['line_keys'] ?? []))
            ->keys()->all()) : [];
        $selectedPayments = $restoreBatch ? $reopenedBatch['payment_keys'] : [];
        if (old('period_from') === $period['from']) {
            $selectedBills = (array) old('bill_keys', []);
            $selectedPayments = (array) old('payment_keys', []);
        }
        $canMatch = $canManageAllocation && $open->isNotEmpty() && $pending->contains('can_allocate', true);
    @endphp
    @if($matched->isNotEmpty() || $matchedReceipts->isNotEmpty())
        <details class="debt-cycle-card mb-3">
            <summary>Lịch sử Bill và khoản thu đã ghép đủ · {{ $date($period['from']) }}</summary>
        @if($matchedReceipts->isNotEmpty())
            <details class="mb-2">
                <summary>Thanh toán đã khớp ({{ $matchedReceipts->count() }}) · {{ $money($matchedReceipts->sum('amount')) }}</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-sm mb-0">
                        <thead><tr><th scope="col">Khoản thu</th><th scope="col">Bill đã ghép</th><th scope="col" class="text-end">Số tiền</th></tr></thead>
                        <tbody>
                            @foreach($matchedReceipts as $key => $receipt)
                                <tr>
                                    <td>
                                        <span class="badge text-bg-success">Đã khớp</span>
                                        {{ $date($receipt['date']) }} · {{ $key }}
                                        @if(!empty($receipt['note']))<div class="small">{{ $receipt['note'] }}</div>@endif
                                        @if($canManageAllocation && $receipt['saved'] && !$receipt['confirmed'])
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['allocate' => $key]) }}#debt-manual-allocation">Sửa phân bổ</a>
                                                <form method="POST" action="{{ route('customer-debt.payment-allocations') }}" onsubmit="return confirm('Gỡ phân bổ đã lưu cho khoản thu này?')">
                                                    @csrf
                                                    @include('customer-debt.payment-allocation-fields', ['allocationInBillTab' => true])
                                                    <input type="hidden" name="payment_key" value="{{ $key }}">
                                                    <input type="hidden" name="cancel" value="1">
                                                    <button class="btn btn-sm btn-outline-danger">Gỡ ghép</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ implode(', ', $receipt['matched_bills']) }}</td>
                                    <td class="text-end text-nowrap">{{ $money($receipt['amount']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
        @if($matched->isNotEmpty())
            <details class="mb-2">
                <summary>{{ $matched->count() }} Bill đã khớp đủ · {{ $money($matched->sum('amount')) }}</summary>
                <ul class="list-unstyled small mt-2 mb-0">
                    @foreach($matched as $bill)
                        <li><span class="text-success">Đã khớp</span> · {{ $bill['label'] }} · {{ $date($bill['date']) }} · {{ $money($bill['amount']) }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
        </details>
    @endif
    <section class="debt-cycle-card mb-3">
        <h3 class="h6 mb-3">Đối soát Bill từ {{ $date($period['from']) }} đến {{ $date(min($period['to'] ?? today()->toDateString(), today()->toDateString())) }}</h3>
        <dl class="d-flex flex-wrap gap-3 small mb-3">
            <div><dt>Công nợ cuối giai đoạn</dt><dd>{{ $money($period['closing']) }}</dd></div>
            <div><dt>Tổng phải thu theo Bill</dt><dd>{{ $money($bills->sum('amount')) }}</dd></div>
            <div><dt>Tổng thanh toán trong giai đoạn</dt><dd>{{ $money($receipts->sum('amount')) }}</dd></div>
            <div><dt>Đã phân bổ vào các Bill</dt><dd>{{ $money($bills->sum('paid')) }}</dd></div>
            <div><dt>{{ $open->count() }} Bill chưa khớp đủ</dt><dd>{{ $money($open->sum('remaining')) }}</dd></div>
            <div><dt>{{ $matchedReceipts->count() }} khoản thu đã khớp Bill</dt><dd>{{ $money($matchedReceipts->sum('amount')) }}</dd></div>
            <div><dt>Tiền thu chờ ghép</dt><dd>{{ $money($pending->where('pending', '>', 0)->sum('pending')) }}</dd></div>
        </dl>
        @if($canMatch)
            <form method="POST" action="{{ route('customer-debt.bill-allocations') }}" data-bill-match>
                @csrf
                @include('customer-debt.payment-allocation-fields')
                <input type="hidden" name="period_from" value="{{ $period['from'] }}">
                <input type="hidden" name="match_exact" value="0" data-bill-match-exact>
                <section class="border rounded p-3 mb-3" aria-label="Khu vực ghép" @if($restoreBatch) id="debt-bill-reopened" @endif>
                    <h4 class="h6">Khu vực ghép</h4>
                    @if($restoreBatch)
                        <p class="small mb-2">Đã gỡ lần ghép #{{ $reopenedBatch['number'] }}. Bạn có thể ghép lại hoặc xác nhận để trống sau khi bỏ chọn tất cả.</p>
                    @endif
                    <p class="small text-muted">Tích dòng bên dưới để đưa vào khu vực ghép. Hệ thống phân bổ theo ngày từ cũ đến mới và giữ phần chênh lệch cho lần kế tiếp.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-lg-6"><strong class="small">Bill đã chọn</strong><ul class="list-unstyled small mb-0" data-bill-staged></ul></div>
                        <div class="col-lg-6"><strong class="small">Khoản thu đã chọn</strong><ul class="list-unstyled small mb-0" data-payment-staged></ul></div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <output class="small" aria-live="polite" data-bill-totals></output>
                        <button class="btn btn-sm btn-primary" type="submit">Hoàn thành lần ghép</button>
                        @if($restoreBatch)
                            <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['allocate' => null]) }}#debt-payment-periods"
                                data-bill-confirm-empty hidden onclick="return confirm('Xác nhận để trống khu vực ghép? Các Bill và khoản thu vẫn ở danh sách chờ đối soát.')">Xác nhận để trống</a>
                        @endif
                    </div>
                </section>
        @elseif($restoreBatch && $canManageAllocation)
            <section class="border rounded p-3 mb-3" id="debt-bill-reopened" aria-label="Khu vực ghép">
                <h4 class="h6">Khu vực ghép</h4>
                <p class="small">Đã gỡ lần ghép #{{ $reopenedBatch['number'] }}. Khu vực ghép đang trống.</p>
                <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['allocate' => null]) }}#debt-payment-periods"
                    onclick="return confirm('Xác nhận để trống khu vực ghép? Các Bill và khoản thu vẫn ở danh sách chờ đối soát.')">Xác nhận để trống</a>
            </section>
        @endif
        @foreach([true, false] as $showResidual)
            @if($showResidual)
                @continue($residualBills->isEmpty() && $residualReceipts->isEmpty())
                <details class="border rounded p-3 mb-3" open>
                    <summary class="fw-semibold">Phần thiếu hoặc dư chờ ghép tiếp · Thiếu {{ $money($residualBills->sum('remaining')) }} · Dư {{ $money($residualReceipts->sum('pending')) }}</summary>
            @endif
        <div class="row g-3{{ $showResidual ? ' mt-2' : '' }}">
            <div class="col-lg-6">
                <h4 class="h6">{{ $showResidual ? 'Bill còn thiếu' : 'Bill chưa phân bổ' }}</h4>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th scope="col">Bill / ngày giao</th><th scope="col" class="text-end">Phải thu</th><th scope="col" class="text-end">Còn thiếu</th></tr></thead>
                        <tbody>
                            @forelse(($showResidual ? $residualBills : $open->diffKeys($residualBills)) as $key => $bill)
                                <tr>
                                    <td>
                                        <label>
                                            @if($canMatch)<input class="form-check-input me-1" type="checkbox" name="bill_keys[]" value="{{ $key }}" data-amount="{{ $bill['remaining'] }}" @if(in_array($key, $exactPairs, true)) data-exact-pair @endif data-label="{{ $bill['label'] }} · {{ $date($bill['date']) }}" @checked(in_array($key, $selectedBills, true))>@endif
                                            {{ $bill['label'] }} · {{ $date($bill['date']) }}
                                        </label>
                                    </td>
                                    <td class="text-end text-nowrap">{{ $money($bill['amount']) }}</td>
                                    <td class="text-end text-nowrap">{{ $money($bill['remaining']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Không có Bill trong danh sách này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <h4 class="h6 mb-0">{{ $showResidual ? 'Tiền thu còn dư' : 'Khoản thu cần đối chiếu' }}</h4>
                    @if($canMatch && !$showResidual)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bill-select-exact @disabled(!$exactPairs)>
                            Chọn tất cả cặp khớp số tiền ({{ count($exactPairs) }})
                        </button>
                        <div class="small text-muted w-100">Chọn các cặp duy nhất có cùng ngày và khớp đúng số tiền.</div>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th scope="col">Khoản thu</th><th scope="col" class="text-end">Đã thu</th><th scope="col" class="text-end">Chênh lệch</th></tr></thead>
                        <tbody>
                            @forelse(($showResidual ? $residualReceipts : $pending->diffKeys($residualReceipts)) as $key => $receipt)
                                <tr>
                                    <td>
                                        <label>
                                            @if($canMatch && $receipt['can_allocate'])
                                                <input class="form-check-input me-1" type="checkbox" name="payment_keys[]" value="{{ $key }}" data-amount="{{ $receipt['pending'] }}" @if(isset($exactPairs[$key])) data-exact-pair @endif data-label="{{ $date($receipt['date']) }} · {{ $key }}" @checked(in_array($key, $selectedPayments, true))>
                                            @endif
                                            {{ $date($receipt['date']) }} · {{ $key }}
                                        </label>
                                        @if(!empty($receipt['note']))<div class="small">{{ $receipt['note'] }}</div>@endif
                                        @if($receipt['issue'])<div class="small text-muted">{{ $receipt['issue'] }}</div>@endif
                                        @if($canManageAllocation && $receipt['saved'] && !$receipt['confirmed'])
                                            <a class="btn btn-sm btn-outline-primary mt-1" href="{{ request()->fullUrlWithQuery(['allocate' => $key]) }}#debt-manual-allocation">Sửa phân bổ đã lưu</a>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">{{ $money($receipt['amount']) }}</td>
                                    <td class="text-end text-nowrap">{{ $money($receipt['pending']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Không có khoản thu cần đối chiếu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            @if($showResidual)</details>@endif
        @endforeach
        @if($canMatch)</form>@endif
    </section>
@endforeach
@once
    @push('scripts')
        <script src="{{ asset('js/customer-debt-bill-reconciliation.js') }}?v={{ filemtime(public_path('js/customer-debt-bill-reconciliation.js')) }}"></script>
    @endpush
@endonce
