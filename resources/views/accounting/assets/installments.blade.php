@php
    $installmentErrors = $errors->getBag('installment');
    $scheduleInput = fn ($key, $default = null) => $installmentErrors->any() ? old($key, $default) : $default;
    $scheduleRoute = ['contract' => $row->id] + ($editInstallment ? ['installment' => $editInstallment->id] : []);
@endphp
<section class="card rounded-3 mb-3" id="asset-installments">
    <div class="card-body">
        <h3 class="h5 mb-2">Đợt thanh toán</h3>
        <p class="small text-muted mb-3">
            Theo lịch {{ $money($installments->sum('amount')) }} ·
            @if($deposit)
                Tiền cọc {{ $money($deposit->amount) }} · Đã trả cọc {{ $money($deposit->paid) }}
            @else
                Chưa thiết lập tiền cọc
            @endif
            @if($unallocated > 0) · Đã chi chưa phân đợt {{ $money($unallocated) }} @endif
        </p>
        <details class="border rounded p-3 mb-3" @if($editInstallment || $installmentErrors->any()) open @endif>
            <summary class="fw-semibold">{{ $editInstallment ? 'Cập nhật đợt thanh toán' : 'Thêm đợt hoặc tiền cọc' }}</summary>
            @if($installmentErrors->any())
                <div class="alert alert-danger mt-2 mb-0" role="alert">{{ $installmentErrors->first() }}</div>
            @endif
            <form method="POST" action="{{ accounting_route($editInstallment ? 'assets.installments.update' : 'assets.installments.store', $scheduleRoute) }}" class="row g-2 mt-2">
                @csrf
                @if($editInstallment) @method('PATCH') @endif
                <label class="col-md-3"><span class="form-label">Loại khoản</span>
                    <select class="form-select" name="kind" required>
                        <option value="installment" @selected($scheduleInput('kind', $editInstallment->kind ?? '') === 'installment')>Đợt thanh toán</option>
                        @if(!$deposit || $editInstallment?->id === $deposit->id)
                            <option value="deposit" @selected($scheduleInput('kind', $editInstallment->kind ?? '') === 'deposit')>Tiền cọc</option>
                        @endif
                    </select>
                </label>
                <label class="col-md-3"><span class="form-label">Tên đợt</span>
                    <input class="form-control" name="label" maxlength="150" value="{{ $scheduleInput('label', $editInstallment->label ?? '') }}" placeholder="Ví dụ Đợt 1">
                </label>
                <label class="col-md-3"><span class="form-label">Số tiền theo đợt (đ)</span>
                    <input class="form-control" name="amount" type="number" min="1" max="999999999999999" value="{{ $scheduleInput('amount', $editInstallment->amount ?? '') }}" required>
                </label>
                <label class="col-md-3"><span class="form-label">Hạn thanh toán</span>
                    <input class="form-control" name="due_on" type="date" value="{{ $scheduleInput('due_on', $editInstallment->due_on ?? '') }}">
                </label>
                <label class="col-12"><span class="form-label">Mốc hoặc điều kiện thanh toán</span>
                    <input class="form-control" name="milestone" maxlength="500" value="{{ $scheduleInput('milestone', $editInstallment->milestone ?? '') }}" placeholder="Ví dụ trong 7 ngày sau nghiệm thu">
                </label>
                <div class="col-12 d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary btn-sm">Lưu đợt</button>
                    @if($editInstallment)<a class="btn btn-light btn-sm" href="{{ $contractUrl }}#asset-installments">Hủy cập nhật</a>@endif
                </div>
            </form>
        </details>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Đợt</th><th scope="col">Hạn / mốc thanh toán</th>
                    <th scope="col" class="text-end">Theo lịch</th><th scope="col" class="text-end">Đã trả</th><th scope="col" class="text-end">Còn lại</th>
                    <th scope="col">Trạng thái</th><th scope="col" class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse($installments as $entry)
                        <tr>
                            <th scope="row">{{ $entry->label }}</th>
                            <td>@if($entry->due_on)<div class="text-nowrap">{{ $date($entry->due_on) }}</div>@endif{{ $entry->milestone ?: ($entry->due_on ? '' : '—') }}</td>
                            <td class="text-end text-nowrap">{{ $money($entry->amount) }}</td>
                            <td class="text-end text-nowrap">{{ $money($entry->paid) }}</td>
                            <td class="text-end text-nowrap">
                                @if($entry->remaining < 0)<span class="text-danger">Vượt {{ $money(abs($entry->remaining)) }}</span>
                                @else{{ $money($entry->remaining) }}@endif
                            </td>
                            @php
                                $overdue = $entry->remaining > 0 && $entry->due_on && $entry->due_on < today()->toDateString();
                            @endphp
                            <td>
                                <span class="badge {{ $entry->remaining <= 0 ? 'text-bg-success' : 'text-bg-light' }}">
                                    {{ $entry->remaining <= 0 ? 'Đã trả đủ' : ($entry->paid > 0 ? 'Trả một phần' : 'Chưa thanh toán') }}
                                </span>
                                @if($overdue)<span class="badge text-bg-warning">Quá hạn</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-light" href="{{ accounting_route('assets.contracts.show', ['contract' => $row->id, 'edit_installment' => $entry->id]) }}#asset-installments">Cập nhật</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ accounting_route('assets.contracts.show', ['contract' => $row->id, 'pay_installment' => $entry->id]) }}#asset-payments">{{ $entry->kind === 'deposit' ? 'Thanh toán cọc' : 'Ghi nhận chi' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center py-3">Chưa có đợt thanh toán.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deposit)
            <p class="small text-muted mt-3 mb-0">Tiền cọc đã trả được tính trong tổng đã thanh toán của hợp đồng.</p>
        @endif
    </div>
</section>
