<div class="inv-summary-list mb-4">
    <div class="inv-summary-head my-2 d-flex justify-content-between gap-2">
        <span>Tồn kho tổng hợp các kho</span>
        <a href="{{ route('warehouse.inventory') }}" class="small">Xem đầy đủ</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.78rem;min-width:max-content;">
            <thead>
                <tr class="text-center" style="background:#dff3ef;">
                    <th rowspan="2" style="min-width:190px;">Sản phẩm</th>
                    @foreach($consolidatedInventory['warehouses'] as $warehouse)
                        <th colspan="4">{{ $warehouse->name }}</th>
                    @endforeach
                    <th colspan="4">Tổng mặt bằng</th>
                </tr>
                <tr class="text-center" style="background:#f0f9f7;">
                    @foreach($consolidatedInventory['warehouses'] as $warehouse)
                        <th>Tồn đầu</th><th>Nhập</th><th>Xuất</th><th>Tồn cuối</th>
                    @endforeach
                    <th>Available</th><th>Book</th><th>Tổng xuất</th><th>Tổng tồn cuối</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consolidatedInventory['rows'] as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        @foreach($consolidatedInventory['warehouses'] as $warehouse)
                            @php
                                $values = $row['warehouses'][(string) $warehouse->id];
                            @endphp
                            <td class="text-end">{{ number_format($values['opening']) }}</td>
                            <td class="text-end">{{ number_format($values['import']) }}</td>
                            <td class="text-end">{{ number_format($values['export']) }}</td>
                            <td class="text-end">{{ number_format($values['closing']) }}</td>
                        @endforeach
                        <td class="text-end text-success fw-bold">{{ number_format($row['available']) }}</td>
                        <td class="text-end text-primary fw-bold">{{ number_format($row['book']) }}</td>
                        <td class="text-end fw-bold">{{ number_format($row['total_export']) }}</td>
                        <td class="text-end fw-bold">{{ number_format($row['total_closing']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 5 + ($consolidatedInventory['warehouses']->count() * 4) }}" class="text-center text-muted py-3">Chưa có dữ liệu tồn kho.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background:#d1fae5;" class="fw-bold">
                    <td>Tổng toàn bộ</td>
                    @foreach($consolidatedInventory['warehouses'] as $warehouse)
                        @php
                            $values = $consolidatedInventory['totals']['warehouses'][(string) $warehouse->id];
                        @endphp
                        <td class="text-end">{{ number_format($values['opening']) }}</td>
                        <td class="text-end">{{ number_format($values['import']) }}</td>
                        <td class="text-end">{{ number_format($values['export']) }}</td>
                        <td class="text-end">{{ number_format($values['closing']) }}</td>
                    @endforeach
                    <td class="text-end">{{ number_format($consolidatedInventory['totals']['available']) }}</td>
                    <td class="text-end">{{ number_format($consolidatedInventory['totals']['book']) }}</td>
                    <td class="text-end">{{ number_format($consolidatedInventory['totals']['total_export']) }}</td>
                    <td class="text-end">{{ number_format($consolidatedInventory['totals']['total_closing']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
