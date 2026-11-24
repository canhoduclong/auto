@extends(accounting_layout())

@section('title', 'Chi tiết hợp đồng')
@section('subtitle', $row->number)

@section('accounting_content')
@php
    $money = fn ($value) => number_format($value, 0, ',', '.').' đ';
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $methods = \App\Http\Controllers\CompanyAssetController::PAYMENT_METHODS;
    $remaining = (int) $row->value - $paid;
@endphp
@include('accounting.assets.feedback')

<section class="card rounded-3 mb-3">
    <div class="card-body">
        <a href="{{ accounting_route('assets.index', ['tab' => 'contracts']) }}" class="small">← Danh sách hợp đồng</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3">
            <div>
                <div class="small text-muted fw-semibold">{{ $row->number }}</div>
                <h2 class="h4 mb-2">{{ $row->title }}</h2>
                <span class="badge text-bg-light">{{ \App\Http\Controllers\CompanyAssetController::CONTRACT_STATUSES[$row->status] }}</span>
            </div>
            <a href="{{ accounting_route('assets.edit', ['kind' => 'contracts', 'record' => $row->id]) }}" class="btn btn-outline-primary">Cập nhật hợp đồng</a>
        </div>
        <dl class="row g-2 mt-3 mb-0">
            <div class="col-md-6"><dt>Đơn vị đối tác</dt><dd class="mb-0"><a href="{{ accounting_route('assets.edit', ['kind' => 'partners', 'record' => $row->partner_id]) }}">{{ $row->partner_name }}</a>
                @if($row->partner_phone || $row->partner_email)
                    <span class="small text-muted d-block">{{ implode(' · ', array_filter([$row->partner_phone, $row->partner_email])) }}</span>
                @endif
            </dd></div>
            <div class="col-sm-6 col-md-3"><dt>Ngày ký</dt><dd class="mb-0">{{ $date($row->signed_on) }}</dd></div>
            <div class="col-sm-6 col-md-3"><dt>Ngày hết hạn</dt><dd class="mb-0">{{ $date($row->expires_on) }}</dd></div>
        </dl>
        @if($row->note)<p class="small mt-3 mb-0" style="white-space: pre-wrap">{{ $row->note }}</p>@endif
    </div>
</section>

<div class="row g-3 mb-3">
    @foreach(['Giá trị hợp đồng' => (int) $row->value, 'Đã thanh toán' => $paid, ($remaining < 0 ? 'Chi vượt giá trị' : 'Còn phải thanh toán') => abs($remaining)] as $label => $amount)
        <div class="col-sm-4">
            <div class="card rounded-3 h-100"><div class="card-body py-3">
                <div class="small text-muted">{{ $label }}</div>
                <strong class="fs-5">{{ $money($amount) }}</strong>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-7" id="asset-payments">
        <section class="card rounded-3 h-100">
            <div class="card-body">
                <h3 class="h5 mb-3">Thanh toán</h3>
                <details class="border rounded p-3 mb-3" @if(old('paid_on') || old('amount')) open @endif>
                    <summary class="fw-semibold">Ghi nhận thanh toán</summary>
                    <form method="POST" action="{{ accounting_route('assets.payments.store', ['contract' => $row->id]) }}" class="row g-2 mt-2">
                        @csrf
                        <label class="col-sm-6"><span class="form-label">Ngày thanh toán</span>
                            <input class="form-control" name="paid_on" type="date" value="{{ old('paid_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                        </label>
                        <label class="col-sm-6"><span class="form-label">Số tiền (đ)</span>
                            <input class="form-control" name="amount" type="number" min="1" max="999999999999999" value="{{ old('amount') }}" required>
                        </label>
                        <label class="col-sm-6"><span class="form-label">Hình thức</span>
                            <select class="form-select" name="method" required>
                                @foreach($methods as $key => $label)<option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="col-sm-6"><span class="form-label">Mã tham chiếu</span>
                            <input class="form-control" name="reference" maxlength="100" value="{{ old('reference') }}">
                        </label>
                        <label class="col-12"><span class="form-label">Ghi chú thanh toán</span>
                            <textarea name="note" class="form-control" maxlength="4000" rows="2">{{ old('note') }}</textarea>
                        </label>
                        <div class="col-12"><button class="btn btn-primary btn-sm">Lưu thanh toán</button></div>
                    </form>
                </details>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th scope="col">Ngày thanh toán</th><th scope="col">Hình thức</th><th scope="col">Tham chiếu / ghi chú</th><th scope="col" class="text-end">Số tiền</th></tr></thead>
                        <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="text-nowrap">{{ $date($payment->paid_on) }}</td><td>{{ $methods[$payment->method] }}</td>
                                <td>{{ $payment->reference ?: '—' }}@if($payment->note)<div class="small text-muted">{{ $payment->note }}</div>@endif</td>
                                <td class="text-end text-nowrap fw-semibold">{{ $money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">Chưa ghi nhận thanh toán.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())<div class="mt-3">{{ $payments->links() }}</div>@endif
            </div>
        </section>
    </div>
    <div class="col-xl-5" id="asset-documents">
        @include('accounting.assets.documents', [
            'uploadUrl' => accounting_route('assets.documents.store', ['contract' => $row->id]),
            'downloadUrl' => fn ($document) => accounting_route('assets.documents.download', ['contract' => $row->id, 'document' => $document]),
        ])
    </div>
</div>

<section class="card rounded-3 mt-3" id="contract-assets">
    <div class="card-body">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h3 class="h5 mb-0">Tài sản theo hợp đồng</h3>
            <a href="{{ accounting_route('assets.create', ['kind' => 'assets', 'contract_id' => $row->id]) }}" class="btn btn-sm btn-outline-primary">Bổ sung tài sản</a>
        </header>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Mã tài sản</th><th scope="col">Tên tài sản</th><th scope="col">Vị trí</th><th scope="col">Trạng thái</th></tr></thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr><td><a href="{{ accounting_route('assets.items.show', ['asset' => $asset->id]) }}">{{ $asset->code }}</a></td>
                            <td>{{ $asset->name }}</td><td>{{ $asset->location ?: '—' }}</td><td>{{ \App\Http\Controllers\CompanyAssetController::ASSET_STATUSES[$asset->status] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-3">Chưa có tài sản liên kết.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assets->hasPages())<div class="mt-3">{{ $assets->links() }}</div>@endif
    </div>
</section>
@endsection
