<div class="inv-summary-list mb-4">
    <div class="inv-summary-head my-2">{{ $title }}</div>
    <div class="table-responsive">
        <div class="inv-summary-table-div">
            <div class="inv-summary-header d-flex fw-bold" style="background:#6b3f19;color:#fff;">
                <div class="inv-col-name flex-grow-1 px-2 py-2">Tên sản phẩm / biến thể</div>
                <div class="inv-col-unit px-2 py-2" style="min-width:80px">DVT</div>
                <div class="inv-col-opening px-2 py-2 num" style="min-width:90px">Tồn đầu</div>
                <div class="inv-col-import px-2 py-2 num" style="min-width:70px">Nhập</div>
                <div class="inv-col-reserved px-2 py-2 num" style="min-width:90px">Book</div>
                <div class="inv-col-available px-2 py-2 num" style="min-width:90px">Khả dụng</div>
                <div class="inv-col-export px-2 py-2 num" style="min-width:70px">Xuất</div>
                <div class="inv-col-closing px-2 py-2 num" style="min-width:80px">Tồn cuối</div>
            </div>
            @php
                $filteredRows = $rows->filter(fn ($row) => $row['closing'] > 0);
            @endphp
            @forelse($filteredRows as $row)
                @php
                    $targetId = 'inv-child-' . $targetPrefix . '-' . $row['product_id'];
                @endphp
                <div class="product-row-div border-bottom" style="background:#fffbe7;">
                    <div class="d-flex align-items-center px-2 py-2">
                        <div class="inv-col-name flex-grow-1">
                            <button type="button" class="inv-toggle js-inv-toggle border-0 bg-transparent p-0" data-target="{{ $targetId }}">
                                <span class="icon-plus" style="display:inline;">+</span>
                                <span class="icon-minus" style="display:none;">&minus;</span>
                                <span class="inv-product-name text-capitalize">{{ $row['name'] }}</span>
                            </button>
                        </div>
                        <div class="inv-col-unit" style="min-width:80px">{{ $row['unit'] }}</div>
                        <div class="inv-col-opening num" style="min-width:90px"><strong>{{ number_format($row['opening']) }}</strong></div>
                        <div class="inv-col-import num" style="min-width:70px">{{ number_format($row['import']) }}</div>
                        <div class="inv-col-reserved num" style="min-width:90px;color:#1d4ed8;">{{ number_format($row['reserved']) }}</div>
                        <div class="inv-col-available num" style="min-width:90px;color:#047857;font-weight:700;">{{ number_format($row['available']) }}</div>
                        <div class="inv-col-export num" style="min-width:70px">{{ number_format($row['export']) }}</div>
                        <div class="inv-col-closing num" style="min-width:80px">{{ number_format($row['closing']) }}</div>
                    </div>
                </div>
                <div id="{{ $targetId }}" class="inv-child-row-div" style="display:none;">
                    @foreach($row['variants'] as $variantRow)
                        @if($variantRow['closing'] > 0)
                            <div class="variant-row-div px-2 py-1 border-bottom">
                                <div class="d-flex align-items-center inv-variant-block">
                                    <div class="inv-indent flex-grow-1 subname">{{ $variantRow['name'] }}</div>
                                    <div class="inv-col-unit" style="min-width:80px">{{ $variantRow['unit'] }}</div>
                                    <div class="inv-col-opening num" style="min-width:90px">{{ number_format($variantRow['opening']) }}</div>
                                    <div class="inv-col-import num" style="min-width:70px">{{ number_format($variantRow['import']) }}</div>
                                    <div class="inv-col-reserved num" style="min-width:90px;color:#1d4ed8;">{{ number_format($variantRow['reserved']) }}</div>
                                    <div class="inv-col-available num" style="min-width:90px;color:#047857;font-weight:700;">{{ number_format($variantRow['available']) }}</div>
                                    <div class="inv-col-export num" style="min-width:70px">{{ number_format($variantRow['export']) }}</div>
                                    <div class="inv-col-closing num" style="min-width:80px">{{ number_format($variantRow['closing']) }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <div class="text-center text-muted py-3">Không có dữ liệu sản phẩm trong danh sách hiện tại.</div>
            @endforelse
        </div>
    </div>
</div>
