@extends(accounting_layout())

@section('title', 'Chi tiết tài sản')

@section('accounting_content')
@include('accounting.assets.feedback')
<div class="row g-3">
    <div class="col-lg-6">
        <section class="card rounded-3">
            <div class="card-body">
                <a class="small" href="{{ accounting_route('assets.index', ['tab' => 'assets']) }}">← Danh sách tài sản</a>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 my-3">
                    <div>
                        <div class="small text-muted">{{ $row->code }}</div>
                        <h2 class="h4 mb-1">{{ $row->name }}</h2>
                    </div>
                    <a class="btn btn-outline-primary" href="{{ accounting_route('assets.edit', ['kind' => 'assets', 'record' => $row->id]) }}">Cập nhật</a>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nhóm quản lý</dt><dd class="col-sm-7">{{ \App\Http\Controllers\CompanyAssetController::CATEGORIES[$row->category] ?? '—' }}</dd>
                    <dt class="col-sm-5">Giá trị ghi nhận</dt><dd class="col-sm-7">{{ $row->value !== null ? number_format($row->value, 0, ',', '.').' đ' : '—' }}</dd>
                    <dt class="col-sm-5">Trạng thái</dt><dd class="col-sm-7">{{ \App\Http\Controllers\CompanyAssetController::ASSET_STATUSES[$row->status] }}</dd>
                    <dt class="col-sm-5">Vị trí</dt><dd class="col-sm-7">{{ $row->location ?: '—' }}</dd>
                    <dt class="col-sm-5">Hợp đồng</dt><dd class="col-sm-7">
                        @if($contract)
                            <a href="{{ accounting_route('assets.contracts.show', ['contract' => $contract->id]) }}">{{ $contract->number }} · {{ $contract->title }}</a>
                        @else — @endif
                    </dd>
                </dl>
                @if($row->note)<p class="small mt-2 mb-0" style="white-space: pre-wrap">{{ $row->note }}</p>@endif
            </div>
        </section>
    </div>
    <div class="col-lg-6" id="asset-documents">
        @include('accounting.assets.documents', [
            'uploadUrl' => accounting_route('assets.items.documents.store', ['asset' => $row->id]),
            'downloadUrl' => fn ($document) => accounting_route('assets.items.documents.download', ['asset' => $row->id, 'document' => $document]),
        ])
    </div>
</div>
@endsection
