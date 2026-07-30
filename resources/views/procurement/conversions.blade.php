@extends('layouts.procurement')
@section('title', 'Quy đổi sơ chế')
@section('subtitle', 'Ma trận tỷ lệ size vịt lông sang size vịt thịt; mỗi hàng phải đủ 100%')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', ''), '0'), ',');
@endphp

@section('content')
<form method="POST" action="{{ route('procurement.conversions.store') }}" id="conversionForm">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle text-center">
                    <thead><tr><th>Size lông \ Size thịt</th>@foreach($processedSizes as $size)<th>{{ $formatNumber($size) }}</th>@endforeach<th>Tổng</th></tr></thead>
                    <tbody>
                    @foreach($liveSizes as $live)
                        <tr data-rate-row>
                            <th>{{ $formatNumber($live) }}</th>
                            @foreach($processedSizes as $processed)
                                @php($rate = $rates->get(number_format($live, 1), collect())->first(fn ($item) => (float) $item->processed_size === (float) $processed))
                                <td><input type="text" inputmode="decimal" autocomplete="off" pattern="(?:100(?:[.,]0{1,3})?|\d{1,2}(?:[.,]\d{1,3})?)" title="Nhập số từ 0 đến 100, tối đa 3 chữ số thập phân" name="rates[{{ $live }}][{{ $processed }}]" value="{{ $formatNumber($rate?->percentage ?? 0) }}" class="form-control form-control-sm text-center rate-input" aria-label="Tỷ lệ size {{ $formatNumber($processed) }}"></td>
                            @endforeach
                            <td class="fw-bold row-total">0%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary">Lưu ma trận tỷ lệ</button>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white"><strong>Tính thử sản lượng sơ chế</strong></div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><label>Số lượng vịt lông</label><input id="calcQuantity" type="number" value="1000" class="form-control"></div>
            <div class="col-md-3"><label>Size vịt lông</label><select id="calcLiveSize" class="form-select">@foreach($liveSizes as $size)<option value="{{ $size }}">{{ $formatNumber($size) }}</option>@endforeach</select></div>
            <div class="col-md-2 align-self-end"><button type="button" id="calculateBtn" class="btn btn-warning w-100">Tính sản lượng</button></div>
        </div>
        <div id="conversionResult" class="mt-3"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const parseNumber = value => Number(String(value ?? '').trim().replace(',', '.')) || 0;
    const formatNumber = value => Number(value || 0).toLocaleString('vi-VN', {maximumFractionDigits: 3});
    const rateInputs = [...document.querySelectorAll('.rate-input')];

    const updateTotals = () => {
        document.querySelectorAll('[data-rate-row]').forEach(row => {
            const total = [...row.querySelectorAll('.rate-input')].reduce((sum, input) => sum + parseNumber(input.value), 0);
            const totalCell = row.querySelector('.row-total');
            totalCell.textContent = formatNumber(total) + '%';
            totalCell.className = 'fw-bold row-total ' + (total === 0 || Math.abs(total - 100) < .01 ? 'text-success' : 'text-danger');
        });
    };

    rateInputs.forEach(input => {
        input.addEventListener('input', updateTotals);
        input.addEventListener('blur', () => {
            input.value = formatNumber(parseNumber(input.value));
            updateTotals();
        });
    });
    updateTotals();

    document.getElementById('conversionForm').addEventListener('submit', () => {
        rateInputs.forEach(input => {
            const value = String(input.value).trim();
            if (value !== '') input.value = String(parseNumber(value));
        });
    });

    document.getElementById('calculateBtn').addEventListener('click', () => {
        const quantity = Number(document.getElementById('calcQuantity').value) || 0;
        const liveSize = document.getElementById('calcLiveSize').value;
        const row = [...document.querySelectorAll('[data-rate-row]')].find(item => parseNumber(item.querySelector('th').textContent) === Number(liveSize));
        let html = '<div class="row g-2">';
        if (row) {
            [...row.querySelectorAll('.rate-input')].forEach(input => {
                const size = input.name.match(/\[([^\]]+)\]$/)[1];
                const percentage = parseNumber(input.value);
                if (percentage > 0) html += `<div class="col-4 col-md-2"><div class="border rounded p-2"><small>Size ${formatNumber(size)}</small><div class="fw-bold">${Math.round(quantity * percentage / 100).toLocaleString('vi-VN')} con</div></div></div>`;
            });
        }
        html += `<div class="col-4 col-md-2"><div class="border rounded p-2"><small>Bộ lông</small><div class="fw-bold">${quantity.toLocaleString('vi-VN')} con</div></div></div><div class="col-4 col-md-2"><div class="border rounded p-2"><small>Bộ lòng</small><div class="fw-bold">${quantity.toLocaleString('vi-VN')} con</div></div></div></div>`;
        document.getElementById('conversionResult').innerHTML = html;
    });
})();
</script>
@endpush
