@extends(accounting_layout())
@section('title', 'Chi tiết bản nháp')
@section('accounting_content')
@php
    $labels = [
        'name' => 'Tên', 'title' => 'Tiêu đề', 'code' => 'Mã tài sản', 'number' => 'Số hợp đồng',
        'category' => 'Nhóm tài sản', 'partner_id' => 'ID đối tác', 'contract_id' => 'ID hợp đồng', 'signed_on' => 'Ngày ký',
        'expires_on' => 'Ngày hết hạn', 'location' => 'Vị trí', 'value' => 'Giá trị (đ)', 'tax_code' => 'Mã số thuế',
        'document_type' => 'Loại chứng từ', 'document_number' => 'Số chứng từ', 'issued_on' => 'Ngày chứng từ',
        'issuer' => 'Đơn vị phát hành', 'serial' => 'Ký hiệu', 'subtotal' => 'Trước thuế (đ)', 'vat' => 'VAT (đ)',
        'amount' => 'Số tiền (đ)', 'valid_until' => 'Hiệu lực đến', 'delivered_by' => 'Bên giao',
        'received_by' => 'Bên nhận', 'result' => 'Kết quả', 'note' => 'Ghi chú',
    ];
    $targetUrl = $row->target_id ? match ($row->kind) {
        'assets' => accounting_route('assets.items.show', ['asset' => $row->target_id]),
        'contracts' => accounting_route('assets.contracts.show', ['contract' => $row->target_id]),
        'partners' => accounting_route('assets.edit', ['kind' => 'partners', 'record' => $row->target_id]),
    } : null;
@endphp
<section class="card">
    <div class="card-body">
        <a class="small" href="{{ accounting_route('assets.ai.index') }}">← Bản nháp ChatGPT</a>
        <h2 class="h5 mt-3">{{ $row->title }}</h2>
        <p class="small text-muted">{{ $row->connection_name }} @if($row->source_name) · {{ $row->source_name }} @endif</p>
        @if($targetUrl)<p><a href="{{ $targetUrl }}">Mở hồ sơ được đề xuất</a></p>@endif
        @if($row->review_notes)<div class="alert alert-warning"><strong>Cần kiểm tra</strong><div style="white-space: pre-wrap">{{ $row->review_notes }}</div></div>@endif
        <dl class="row">
            @foreach($proposed as $field => $value)
                <dt class="col-sm-4">{{ $labels[$field] ?? $field }}</dt>
                <dd class="col-sm-8 text-break">@if(in_array($field, ['subtotal', 'vat', 'amount', 'value'], true) && $value !== null){{ number_format($value, 0, ',', '.') }}@else{{ $value ?? '—' }}@endif</dd>
            @endforeach
        </dl>
        <p class="small text-muted mb-0">Dữ liệu này đang ở bản nháp. Sau khi kiểm tra, bạn có thể dùng thông tin trong form quản lý tài sản hoặc hợp đồng.</p>
    </div>
</section>
@endsection
