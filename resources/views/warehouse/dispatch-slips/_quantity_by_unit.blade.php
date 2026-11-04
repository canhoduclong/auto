@forelse(\App\Support\QuantityByUnit::totals($quantityRows, $quantityColumn ?? 'quantity') as $unit => $quantity)
    <div style="white-space:nowrap">{{ rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',') }} {{ $unit }}</div>
@empty
    —
@endforelse
