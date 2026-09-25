        @if(($deferredComponentImportRequests ?? collect())->isNotEmpty())
            <div class="card border-warning shadow-sm mb-4">
                <div class="card-header bg-warning-subtle justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold">
                            <i class="bi bi-hourglass-split me-1"></i>Yêu cầu nhập kho thành phần pha lóc
                        </div>
                        <div class="small text-muted">Các thành phần được chọn "nhập sau" trong ngày sẽ gom vào phiếu đang mở.</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($deferredComponentImportRequests as $request)
                        @php
                            $groupedItems = $request->items
                                ->groupBy('product_variant_id')
                                ->map(function ($items) {
                                    $first = $items->first();
                                    return [
                                        'variant' => $first?->productVariant,
                                        'quantity' => (float) $items->sum('quantity'),
                                        'orders' => $items->pluck('source_order_code')->filter()->unique()->values(),
                                    ];
                                })
                                ->values();
                        @endphp
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                <div>
                                    <div class="fw-semibold">Phiếu yêu cầu #{{ $request->id }} - {{ $request->warehouse?->name ?? 'Kho' }}</div>
                                    <div class="small text-muted">
                                        Ngày {{ optional($request->request_date)->format('d/m/Y') }} · {{ $request->items->count() }} dòng từ pha lóc
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('warehouse.cutting-component-import-requests.receive', $request) }}" onsubmit="return confirm('Xác nhận nhập kho các thành phần còn lại trong phiếu này?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho
                                    </button>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thành phần</th>
                                            <th class="text-end" style="width:130px;">Khối lượng</th>
                                            <th style="width:180px;">Đơn liên quan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groupedItems as $row)
                                            <tr>
                                                <td class="fw-semibold">
                                                    {{ trim(($row['variant']?->product?->name ?? 'Sản phẩm') . ' ' . ($row['variant']?->name ?: '')) }}
                                                </td>
                                                <td class="text-end">{{ format_kg($row['quantity']) }}</td>
                                                <td class="small text-muted">{{ $row['orders']->isNotEmpty() ? $row['orders']->implode(', ') : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
