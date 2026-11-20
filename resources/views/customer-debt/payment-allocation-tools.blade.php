@php
    $detailQuery = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $fullDetailUrl = route('customer-debt.index', $detailQuery + ['tab' => 'all', 'window' => 'all', 'recovery' => 'all']);
@endphp
@if($missingAllocationKeys)
    <div class="small text-danger my-2">
        Dòng thu đã lưu ghép không còn trong nguồn. Xem lại:
        @foreach($missingAllocationKeys as $key)
            <a class="me-2" href="{{ $fullDetailUrl.'&'.http_build_query(['allocate' => $key]) }}#debt-manual-allocation">{{ $key }}</a>
        @endforeach
    </div>
@endif

@if($allocationForm)
    @php
        $receipt = $allocationForm['receipt'];
        $savedItems = $allocationForm['record']['items'] ?? [];
        $oldDebtDates = $allocationForm['old_debt_dates'];
        foreach ($savedItems as &$savedItem) {
            if ($savedItem['target'] === 'legacy' && $oldDebtDates) {
                $savedItem['target'] = 'legacy:'.$oldDebtDates[0];
            }
        }
        unset($savedItem);
    @endphp
    <section id="debt-manual-allocation" class="card card-body my-3" aria-labelledby="debt-manual-allocation-title">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="h6 mb-0" id="debt-manual-allocation-title">Ghép khoản thu</h3>
            <a class="btn btn-sm btn-light" href="{{ $fullDetailUrl }}#debt-detail">Đóng</a>
        </div>
        @if($receipt)
            <p class="small mb-2">{{ \Carbon\Carbon::parse($receipt['date'])->format('d/m/Y') }} · {{ $allocationForm['key'] }} · {{ number_format($receipt['amount'], 0, ',', '.') }} đ<br>{{ $receipt['note'] ?? '' }}</p>
        @else
            <p class="small text-danger">Dòng thu {{ $allocationForm['key'] }} đã bị bỏ khỏi nguồn.</p>
        @endif
        @if($allocationForm['record'])
            <p class="small text-muted">Lần ghép gần nhất {{ \Carbon\Carbon::parse($allocationForm['record']['updated_at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }} · Tài khoản #{{ $allocationForm['record']['updated_by'] }}</p>
        @endif
        @if($receipt && $allocationForm['available'] > 0 && $allocationForm['targets']->isNotEmpty())
            <p class="small mb-2">Có thể phân bổ <strong>{{ number_format($allocationForm['available'], 0, ',', '.') }} đ</strong>. Phần dư tiếp tục chờ ghép.</p>
            <form method="POST" action="{{ route('customer-debt.payment-allocations') }}" data-debt-allocation-form>
                @csrf
                @include('customer-debt.payment-allocation-fields')
                <input type="hidden" name="payment_key" value="{{ $allocationForm['key'] }}">
                <label class="form-label" for="debt-allocation-search">Tìm theo ngày giao hoặc mã đơn</label>
                <input class="form-control form-control-sm mb-2" id="debt-allocation-search" type="search" data-allocation-search>
                <div data-allocation-items>
                    @foreach(old('items', $savedItems ?: [[]]) as $item)
                        @include('customer-debt.payment-allocation-item', ['item' => $item, 'itemIndex' => $loop->index])
                    @endforeach
                </div>
                <template data-allocation-template>
                    @include('customer-debt.payment-allocation-item', ['item' => [], 'itemIndex' => '__index__'])
                </template>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-allocation-add>Thêm bill</button>
                <button class="btn btn-sm btn-primary">Lưu ghép</button>
            </form>
        @endif
        @if($allocationForm['record'])
            <form class="mt-2" method="POST" action="{{ route('customer-debt.payment-allocations') }}">
                @csrf
                @include('customer-debt.payment-allocation-fields')
                <input type="hidden" name="payment_key" value="{{ $allocationForm['key'] }}">
                <input type="hidden" name="cancel" value="1">
                <button class="btn btn-sm btn-outline-danger">Hủy ghép đã lưu</button>
            </form>
        @endif
    </section>
    @once
        @push('scripts')
            <script src="{{ asset('js/customer-debt-allocation.js') }}?v={{ filemtime(public_path('js/customer-debt-allocation.js')) }}"></script>
        @endpush
    @endonce
@endif
