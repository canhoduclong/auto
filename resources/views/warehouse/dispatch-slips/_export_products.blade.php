<table style="font-size:10px">
    <thead><tr><th style="width:4%">STT</th><th style="width:20%">Sản phẩm</th><th style="width:11%">SKU</th><th style="width:7%">Size</th><th class="center" style="width:8%">ĐVT</th><th class="num" style="width:8%">Số lượng</th><th class="num" style="width:12%">Khối lượng</th><th class="num" style="width:15%">Đơn giá</th><th class="num" style="width:15%">Thành tiền</th></tr></thead>
    <tbody>
        @forelse($rows as $row)
            <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $row['product_name'] }}</td><td>{{ $row['sku'] ?: '—' }}</td><td>{{ $row['size'] ?: '—' }}</td><td class="center">{{ $row['unit'] }}</td><td class="num">{{ number_format($row['quantity']) }}</td><td class="num">{{ $row['is_piece_unit'] ? '-' : $formatKg($row['weight']) }}</td><td class="num">{{ $formatMoney($row['price']) }}/{{ $row['priced_by_kg'] ? 'kg' : 'đv' }}</td><td class="num">{{ $formatMoney($row['amount']) }}</td></tr>
        @empty
            <tr><td colspan="9" class="center">Không có dữ liệu hàng hóa.</td></tr>
        @endforelse
    </tbody>
    @php($unitGroups = collect(\App\Support\QuantityByUnit::groups($rows)))
    <tfoot>
        @foreach($unitGroups as $group)
        <tr class="summary"><th colspan="4">Tổng {{ $group['unit'] }}</th><th class="center">{{ $group['unit'] }}</th><th class="num">{{ number_format($group['quantity']) }}</th><th class="num">{{ $group['weight'] === null ? '—' : $formatKg($group['weight']) }}</th><th></th><th class="num">{{ $formatMoney($group['amount']) }}</th></tr>
        @endforeach
        <tr class="summary"><th colspan="8">TỔNG CỘNG</th><th class="num">{{ $formatMoney($rows->sum('amount')) }}</th></tr>
    </tfoot>
</table>
