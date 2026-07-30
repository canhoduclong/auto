@extends('layouts.procurement')
@section('title', 'Quy đổi sơ chế')
@section('subtitle', 'Ma trận tỷ lệ size vịt lông sang size vịt thịt; mỗi hàng phải đủ 100%')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', ''), '0'), ',');
    $calculationSizes = collect($processedSizes);
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
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Số lượng vịt lông</label><input id="calcQuantity" type="number" min="0" value="800" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Size vịt lông</label><select id="calcLiveSize" class="form-select">@foreach($liveSizes as $size)<option value="{{ $size }}" @selected((float) $size === 3.5)>{{ $formatNumber($size) }}</option>@endforeach</select></div>
            <div class="col-md-3"><button type="button" id="calculateBtn" class="btn btn-warning w-100"><i class="bi bi-calculator me-1"></i>Tính sản lượng</button></div>
        </div>
        <div class="table-responsive conversion-result-wrap mt-3">
            <table class="table table-sm align-middle mb-0 conversion-result-table">
                <thead><tr><th>Sản phẩm</th><th>ĐVT</th><th class="text-end">Số lượng</th></tr></thead>
                <tbody>
                    @foreach($calculationSizes as $size)
                        <tr data-output-size="{{ $size }}"><td>{{ $formatNumber($size) }} kg</td><td>Con</td><td class="text-end conversion-quantity">0</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .conversion-result-wrap{max-width:720px;border:1px solid #d9e1e8;border-radius:8px;overflow:hidden}.conversion-result-table thead th{background:#713d17;color:#fff;border:0;padding:.65rem .8rem}.conversion-result-table tbody td{padding:.48rem .8rem;border-color:#dce4ea;background:#fafdff}.conversion-result-table tbody tr:nth-child(even) td{background:#f5f9fb}.conversion-quantity{color:#1546e8;font-weight:700;font-variant-numeric:tabular-nums}
</style>
@endpush

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
        const ratesBySize = new Map();
        if (row) [...row.querySelectorAll('.rate-input')].forEach(input => ratesBySize.set(Number(input.name.match(/\[([^\]]+)\]$/)[1]), parseNumber(input.value)));
        document.querySelectorAll('[data-output-size]').forEach(outputRow => {
            const percentage = ratesBySize.get(Number(outputRow.dataset.outputSize)) || 0;
            outputRow.querySelector('.conversion-quantity').textContent = Math.round(quantity * percentage / 100).toLocaleString('vi-VN');
        });
    });
})();
</script>
@endpush
