<table style="font-size:10px">
    <thead><tr><th style="width:4%">STT</th><th style="width:23%">Sản phẩm</th><th style="width:13%">SKU</th><th style="width:8%">Size</th><th class="num" style="width:9%">Số lượng</th><th class="num" style="width:13%">Khối lượng</th><th class="num" style="width:15%">Đơn giá</th><th class="num" style="width:15%">Thành tiền</th></tr></thead>
    <tbody>
        @forelse($rows as $row)
            <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $row['product_name'] }}</td><td>{{ $row['sku'] ?: '—' }}</td><td>{{ $row['size'] ?: '—' }}</td><td class="num">{{ number_format($row['quantity']) }}</td><td class="num">{{ $formatKg($row['weight']) }}</td><td class="num">{{ $formatMoney($row['price']) }}/{{ $row['priced_by_kg'] ? 'kg' : 'đv' }}</td><td class="num">{{ $formatMoney($row['amount']) }}</td></tr>
        @empty
            <tr><td colspan="8" class="center">Không có dữ liệu hàng hóa.</td></tr>
        @endforelse
    </tbody>
    <tfoot><tr class="summary"><th colspan="4">TỔNG CỘNG</th><th class="num">{{ number_format($rows->sum('quantity')) }}</th><th class="num">{{ $formatKg($rows->sum('weight')) }}</th><th></th><th class="num">{{ $formatMoney($rows->sum('amount')) }}</th></tr></tfoot>
</table>
