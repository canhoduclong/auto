@php
    $cycleDraft = $cycleDraft ?? null;
    $loadCycleBills = request('load_cycle_bills') === '1';
    $cycleCustomer = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $cycleStart = $cycleHistory
        ? \Carbon\Carbon::parse(array_key_last($cycleHistory))->addDay()->toDateString()
        : request('cycle_from', $cyclePreview['from'] ?? '');
@endphp
<section class="debt-cycle-workspace" id="debt-cycles" aria-labelledby="debt-cycles-title">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="h5 mb-0" id="debt-cycles-title">Kỳ công nợ</h3>
        @if(!$cycleDraft && $cyclePreview)
            <form method="POST" action="{{ route('customer-debt.cycles.auto') }}">
                @csrf
                @foreach($cycleCustomer as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <button class="btn btn-sm btn-primary">Tự tạo kỳ tiếp theo</button>
            </form>
        @endif
    </div>
    @if(isset($detailPolicyAction) && $detailPolicyAction)
        <div class="mb-3">
            @include('customer-debt.detail-policy', ['detailPolicyTitle' => 'Cấu hình chu kỳ công nợ', 'detailPolicyButtonLabel' => 'Sửa cấu hình'])
        </div>
    @endif
    @if($cycleError)
        <p class="small text-danger" role="alert">{{ $cycleError }}</p>
    @endif
    @if(!$cycleDraft && ($cyclePreview || $cycleError))
        <form class="debt-cycle-card d-flex flex-wrap gap-2 align-items-end mb-3" method="GET" action="{{ route('customer-debt.index') }}#debt-cycles">
            @foreach($cycleCustomer + ['tab' => 'all', 'window' => 'all', 'recovery' => 'all', 'debt_section' => 'cycles'] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <div>
                <label class="form-label" for="debt-cycle-from">Từ ngày</label>
                <input class="form-control form-control-sm" id="debt-cycle-from" name="cycle_from" type="date"
                    value="{{ $cycleStart }}" @readonly(!empty($cycleHistory)) required>
            </div>
            <div>
                <label class="form-label" for="debt-cycle-date">Đến ngày</label>
                <input class="form-control form-control-sm" id="debt-cycle-date" name="cycle_date" type="date"
                    value="{{ request('cycle_date', $cyclePreview['to'] ?? '') }}" required>
            </div>
            <button class="btn btn-sm btn-outline-primary">Xem trước</button>
        </form>
    @endif
    @if($cyclePreview)
        @php
            $cyclePeriod = \Carbon\Carbon::parse($cyclePreview['from'])->format('d/m/Y').' – '.\Carbon\Carbon::parse($cyclePreview['to'])->format('d/m/Y');
        @endphp
        @if($cycleDraft)
            <details class="debt-cycle-card" data-cycle-editor @if($loadCycleBills || $cycleError || (isset($errors) && $errors->any())) open @endif>
                <summary class="debt-cycle-heading">
                    <span>
                        <strong>{{ $cycleDraft['name'] }}</strong> <span class="badge bg-light text-dark">Bản nháp</span>
                        <small>{{ $cyclePeriod }} · Hạn thanh toán {{ \Carbon\Carbon::parse($cyclePreview['due'])->format('d/m/Y') }}</small>
                    </span>
                    <span class="debt-cycle-balance">
                        <small>Số dư cuối kỳ</small>
                        <strong data-cycle-total="closing_signed">{{ number_format($cyclePreview['closing'], 0, ',', '.') }} đ</strong>
                    </span>
                </summary>
        @else
            <div class="debt-cycle-card">
                <p class="small text-muted mb-2">{{ $cyclePeriod }} · Hạn thanh toán {{ \Carbon\Carbon::parse($cyclePreview['due'])->format('d/m/Y') }}</p>
        @endif

        <form method="POST" action="{{ route('customer-debt.cycles.close') }}" data-cycle-form
            data-opening="{{ $cyclePreview['opening'] + $cyclePreview['prior_correction'] }}"
            data-sales="{{ $cyclePreview['sales'] }}" data-gifts="{{ $cyclePreview['gifts'] }}"
            data-adjustments="{{ $cyclePreview['adjustments'] }}" data-payments="{{ $cyclePreview['payments'] }}">
            @csrf
            @foreach($cycleCustomer as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="cycle_from" value="{{ $cyclePreview['from'] }}">
            <input type="hidden" name="cycle_date" value="{{ $cyclePreview['to'] }}">
            <input type="hidden" name="revision" value="{{ $cyclePreview['revision'] }}">

            <div class="debt-cycle-edit-name">
                <label for="debt-cycle-name" class="form-label">Tên kỳ công nợ</label>
                <input class="form-control form-control-sm" id="debt-cycle-name" name="cycle_name" maxlength="150"
                    value="{{ old('cycle_name', $cycleDraft['name'] ?? '') }}" placeholder="Ví dụ: Kỳ công nợ 1" required>
            </div>

            @if($cycleDraft)
                <input type="hidden" name="draft_version" value="{{ $cycleDraft['version'] }}">
                @if(!empty($cycleDraft['automatic']))
                    <p class="small text-muted mb-2">Kỳ tạo theo lịch tự gom bill thuộc kỳ cho đến khi bạn lưu lựa chọn.</p>
                @endif
                <div class="debt-cycle-grid">
                    <div class="debt-cycle-panel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <h4 class="h6 mb-0">Bill trong kỳ</h4>
                            @unless($loadCycleBills)
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ request()->fullUrlWithQuery(['debt_section' => 'cycles', 'load_cycle_bills' => '1']) }}#debt-cycle-picker">Tải bill</a>
                            @endunless
                        </div>
                        @include('customer-debt.cycle-bill-list', [
                            'cycleBills' => array_intersect_key($cyclePreview['bills'], array_fill_keys($cyclePreview['bill_keys'], true)),
                            'editableCycleBills' => true,
                        ])
                        @if($loadCycleBills)
                            @if($cyclePreview['bill_catalog'])
                                @include('customer-debt.cycle-picker')
                            @else
                                <p class="small text-muted mb-0" id="debt-cycle-picker">Chưa có bill trong dữ liệu công nợ của khách.</p>
                                <input type="hidden" name="cycle_bills" value="{{ json_encode($cyclePreview['bill_keys']) }}">
                            @endif
                        @else
                            <input type="hidden" name="cycle_bills" value="{{ json_encode($cyclePreview['bill_keys']) }}">
                        @endif
                    </div>
                    <div class="debt-cycle-panel">
                        <h4 class="h6 mb-2">Tổng hợp công nợ</h4>
                        <table class="table table-sm small mb-0"><tbody>
                            @include('customer-debt.cycle-totals', ['cycle' => $cyclePreview, 'allowManualAdjustment' => true])
                        </tbody></table>
                        <details class="mt-3" @if(!empty($cyclePreview['manual_adjustment']) || (isset($errors) && $errors->has('cycle_adjustment_reason'))) open @endif>
                            <summary class="small fw-semibold">Điều chỉnh tổng hợp</summary>
                            <div class="mt-2">
                                <label for="cycle-adjustment" class="form-label">Chênh lệch tăng (+) hoặc giảm (−)</label>
                                <input type="number" step="1" class="form-control form-control-sm mb-2" id="cycle-adjustment"
                                    name="cycle_adjustment" data-cycle-adjustment value="{{ old('cycle_adjustment', $cyclePreview['manual_adjustment'] ?? 0) }}">
                                <label for="cycle-adjustment-reason" class="form-label">Lý do điều chỉnh</label>
                                <input class="form-control form-control-sm" id="cycle-adjustment-reason" name="cycle_adjustment_reason" maxlength="500"
                                    value="{{ old('cycle_adjustment_reason', $cyclePreview['adjustment_reason'] ?? '') }}">
                            </div>
                        </details>
                        @if($cyclePreview['opening_paid'] > 0)
                            <p class="small text-muted mt-2 mb-0">Đã cấn {{ number_format($cyclePreview['opening_paid'], 0, ',', '.') }} đ vào nợ cũ. Nợ cũ còn {{ number_format(max(0, $cyclePreview['opening_remaining']), 0, ',', '.') }} đ.</p>
                        @endif
                        @if($cyclePreview['prior_correction'] !== 0)
                            <p class="small text-muted mt-2 mb-0">Dữ liệu nguồn của kỳ trước có thay đổi. Biên bản đã chốt giữ số liệu đã lưu.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="debt-cycle-actions">
                @if($cycleDraft)
                    <button class="btn btn-sm {{ $cyclePreview['can_close'] ? 'btn-outline-primary' : 'btn-primary' }}" name="cycle_action" value="save">Lưu kỳ nháp</button>
                    @if($cyclePreview['can_close'])
                        <button class="btn btn-sm btn-primary" name="cycle_action" value="close">Chốt kỳ và mở biên bản</button>
                    @else
                        <span class="small text-muted">Có thể chốt từ {{ \Carbon\Carbon::parse($cyclePreview['to'])->format('d/m/Y') }}</span>
                    @endif
                    <button class="btn btn-sm btn-link text-muted ms-auto" name="cycle_action" value="discard" formnovalidate
                        onclick="return confirm('Bỏ kỳ nháp và danh sách bill đã chọn?')">Bỏ kỳ nháp</button>
                @else
                    <button class="btn btn-sm btn-primary" name="cycle_action" value="create">Tạo kỳ nháp</button>
                @endif
            </div>
        </form>
        @if($cycleDraft)
            </details>
            @push('scripts')
                <script src="{{ asset('js/customer-debt-cycle.js') }}?v={{ filemtime(public_path('js/customer-debt-cycle.js')) }}"></script>
            @endpush
        @else
            </div>
        @endif
    @endif
    @if($cycleHistory)
        @php
            $followingCycles = collect(array_values($cycleHistory))->slice(1)->keyBy('from');
            if ($cyclePreview) {
                $followingCycles->put($cyclePreview['from'], $cyclePreview);
            }
        @endphp
        <div class="mt-3">
            <h4 class="h6">Kỳ đã chốt</h4>
            @foreach(array_reverse($cycleHistory) as $item)
                <details class="debt-cycle-card mb-2" @if(session('cycle_documents_date') === $item['to']) open @endif>
                    <summary class="debt-cycle-heading">
                        <span>
                            <strong>{{ $item['name'] ?? 'Kỳ công nợ' }}</strong>
                            <small>{{ \Carbon\Carbon::parse($item['from'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($item['to'])->format('d/m/Y') }}</small>
                        </span>
                        <span class="debt-cycle-balance">
                            <small>Số dư cuối kỳ</small>
                            <strong>{{ number_format($item['closing'], 0, ',', '.') }} đ</strong>
                        </span>
                    </summary>
                    <div class="debt-cycle-grid">
                        <div class="debt-cycle-panel">
                            @include('customer-debt.cycle-bill-list', ['cycleBills' => $item['bills'] ?? null, 'cycleDaily' => $item['daily'] ?? []])
                        </div>
                        <div class="debt-cycle-panel">
                            <h4 class="h6 mb-2">Tổng hợp công nợ</h4>
                            <table class="table table-sm small mb-0"><tbody>
                                @include('customer-debt.cycle-totals', ['cycle' => $item])
                            </tbody></table>
                        </div>
                    </div>
                    @php
                        $following = $followingCycles->get(\Carbon\Carbon::parse($item['to'])->addDay()->toDateString());
                    @endphp
                    @if($following)
                        <p class="small text-muted mt-3 mb-0">Cấn ở kỳ tiếp theo {{ number_format($following['opening_paid'], 0, ',', '.') }} đ · Còn {{ number_format(max(0, $following['opening_remaining']), 0, ',', '.') }} đ</p>
                    @endif
                    <a class="btn btn-sm btn-outline-primary mt-3" href="{{ route('customer-debt.cycles.print', ['ledger' => $item['ledger'], 'date' => $item['to']]) }}">Mở / In biên bản</a>
                    @include('customer-debt.cycle-documents', ['documents' => $cycleDocuments[$item['to']] ?? []])
                </details>
            @endforeach
        </div>
    @endif
</section>
