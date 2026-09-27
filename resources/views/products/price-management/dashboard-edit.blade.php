@extends('layouts.site')

@section('title', 'Điều chỉnh giá bán')

@push('styles')
<style>
    .bulk-price-page { min-height: calc(100vh - 80px); padding: 28px 16px 60px; background: #f5f8fb; }
    .bulk-price-card { max-width: 920px; margin: 0 auto; padding: 20px; border: 1px solid #d77742; background: #fff; }
    .bulk-price-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; }
    .bulk-price-head h1 { margin:0; color:#c65b00; font-size:1.1rem; text-transform:uppercase; }
    .bulk-price-head p { margin:4px 0 0; color:#64748b; font-size:.8rem; }
    .bulk-price-table { width:100%; border-collapse:collapse; }
    .bulk-price-table th { padding:9px 10px; background:#ffc400; color:#211700; font-size:.8rem; text-align:center; }
    .bulk-price-table th:first-child { background:transparent; color:#c65b00; text-align:left; }
    .bulk-price-table td { padding:8px 10px; border-bottom:1px solid #ecd5ab; font-size:.82rem; }
    .bulk-price-name { color:#172033; font-weight:750; }
    .bulk-price-group td { padding-top:13px; padding-bottom:3px; border-bottom:0; font-weight:800; }
    .bulk-price-child { padding-left:28px !important; font-weight:400; }
    .bulk-price-child::before { content:'–'; margin-right:8px; color:#c65b00; }
    .bulk-price-current { color:#c65b00; font-weight:800; text-align:right; white-space:nowrap; }
    .bulk-price-input-wrap { display:flex; align-items:center; justify-content:flex-end; gap:5px; white-space:nowrap; }
    .bulk-price-input { width:120px; padding:6px 8px; border:1px solid #cbd5e1; border-radius:3px; text-align:right; font-weight:750; }
    .bulk-price-input:focus { outline:2px solid rgba(255,196,0,.35); border-color:#d99f00; }
    .bulk-price-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
    .bulk-price-save { border:0; border-radius:6px; padding:9px 18px; background:#087f72; color:#fff; font-weight:800; }
    .bulk-price-back { padding:9px 18px; border:1px solid #cbd5e1; border-radius:6px; color:#334155; text-decoration:none; }
    @media(max-width:700px) { .bulk-price-card{padding:12px}.bulk-price-table th,.bulk-price-table td{padding:7px 5px}.bulk-price-input{width:92px}.bulk-price-head{align-items:flex-start;flex-direction:column} }
</style>
@endpush

@section('content')
<div class="bulk-price-page">
    <section class="bulk-price-card">
        <div class="bulk-price-head">
            <div>
                <h1>Giá sản phẩm</h1>
                <p>Chỉ các sản phẩm có giá thay đổi mới được cập nhật.</p>
            </div>
            <a href="{{ route('pages.my_dashboard') }}" class="bulk-price-back">Quay lại</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('pages.my_dashboard.product_prices.update') }}">
            @csrf
            @method('PUT')
            <div class="table-responsive">
                <table class="bulk-price-table">
                    <thead><tr><th>Sản phẩm</th><th>Giá hiện tại</th><th>Điều chỉnh</th><th>Giá min</th></tr></thead>
                    <tbody>
                    @php($rowIndex = 0)
                    @forelse($products as $product)
                        @if($product['is_mixed'])
                            <tr class="bulk-price-group"><td colspan="4">{{ $product['name'] }}</td></tr>
                        @endif
                        @foreach($product['rows'] as $row)
                            @php($variantIds = $row['variant_ids'] ?? [$row['id']])
                            <tr>
                                <td class="bulk-price-name {{ $product['is_mixed'] ? 'bulk-price-child' : '' }}">{{ $row['name'] }}</td>
                                <td class="bulk-price-current">{{ number_format($row['price'], 0, ',', '.') }}đ/{{ $row['unit'] }}</td>
                                <td>
                                    @foreach($variantIds as $variantId)
                                        <input type="hidden" name="rows[{{ $rowIndex }}][variant_ids][]" value="{{ $variantId }}">
                                    @endforeach
                                    <input type="hidden" name="rows[{{ $rowIndex }}][original_min_price]" value="{{ $row['min_price'] }}">
                                    <div class="bulk-price-input-wrap">
                                        <input class="bulk-price-input" type="text" inputmode="numeric" data-money-input name="rows[{{ $rowIndex }}][price]" value="{{ number_format((float) old("rows.$rowIndex.price", $row['price']), 0, ',', '.') }}" required>
                                        <span>đ/{{ $row['unit'] }}</span>
                                    </div>
                                </td>
                                <td><div class="bulk-price-input-wrap"><input class="bulk-price-input" type="text" inputmode="numeric" data-money-input name="rows[{{ $rowIndex }}][min_price]" value="{{ number_format((float) old("rows.$rowIndex.min_price", $row['min_price']), 0, ',', '.') }}" required><span>đ/{{ $row['unit'] }}</span></div></td>
                            </tr>
                            @php($rowIndex++)
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Chưa có sản phẩm có giá bán.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($rowIndex > 0)
                <div class="bulk-price-actions"><button class="bulk-price-save" type="submit">Lưu lại</button></div>
            @endif
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const moneyInputs = document.querySelectorAll('[data-money-input]');
    const formatMoney = (value) => {
        const digits = String(value || '').replace(/\D/g, '');
        return digits === '' ? '' : new Intl.NumberFormat('vi-VN').format(Number(digits));
    };

    moneyInputs.forEach((input) => {
        input.value = formatMoney(input.value);
        input.addEventListener('input', function () {
            const cursorAtEnd = this.selectionStart === this.value.length;
            this.value = formatMoney(this.value);
            if (cursorAtEnd) this.setSelectionRange(this.value.length, this.value.length);
        });
    });

    document.querySelector('.bulk-price-card form')?.addEventListener('submit', function () {
        moneyInputs.forEach((input) => input.value = input.value.replace(/\D/g, ''));
    });
});
</script>
@endpush
