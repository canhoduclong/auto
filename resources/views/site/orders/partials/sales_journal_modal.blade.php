<div class="modal fade" id="monitorSalesJournalModal" tabindex="-1" aria-labelledby="monitorSalesJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="monitorSalesJournalModalLabel">
                        <i class="bi bi-clock-history me-2"></i>Nhật ký bán hàng ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                    </h5>
                    <div class="small text-muted mt-1">Chỉ gồm các đơn đã giao hoặc hoàn tất trong ngày được chọn.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Tổng tiền</div><strong class="text-success">{{ number_format((float) $monitoringJournalSummary['amount'], 0, ',', '.') }}đ</strong></div></div>
                    <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Tổng SL/KL</div><strong class="text-primary">{{ $formatQuantity((float) $monitoringJournalSummary['quantity']) }}</strong></div></div>
                    <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Số đơn</div><strong>{{ number_format((int) $monitoringJournalSummary['orders']) }}</strong></div></div>
                    <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Số dòng</div><strong>{{ number_format((int) $monitoringJournalSummary['rows']) }}</strong></div></div>
                </div>

                @if(! $googleSheetsConfigured)
                    <div class="alert alert-warning py-2">Chưa cấu hình khóa Google Sheets trên máy chủ nên chưa thể ghi dữ liệu.</div>
                @endif

                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.78rem">
                        <thead class="table-light" style="position:sticky;top:0;z-index:1">
                            <tr>
                                <th>Ngày</th><th>Mã KH</th><th>Khách hàng</th><th>NVKD</th><th>Sản phẩm</th>
                                <th class="text-end">SL</th><th class="text-end">Kg/con</th><th class="text-end">Tổng</th><th class="text-end">Đơn giá</th><th class="text-end">Tổng tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monitoringJournalRows as $row)
                                <tr>
                                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->entry_date)->format('d/m/Y') }}</td>
                                    <td>{{ $row->customer_code !== '' ? $row->customer_code : '—' }}</td>
                                    <td>{{ $row->customer_name }}</td>
                                    <td>{{ $row->sale_name !== '' ? $row->sale_name : '—' }}</td>
                                    <td class="fw-semibold">{{ $row->unit }}@if(($row->entry_type ?? 'product') === 'fee') <span class="badge {{ ($row->direction ?? 'charge') === 'discount' ? 'text-bg-danger' : 'text-bg-success' }}">{{ ($row->direction ?? 'charge') === 'discount' ? 'Giảm' : 'Cộng' }}</span>@endif</td>
                                    <td class="text-end">{{ $formatQuantity((float) $row->quantity) }}</td>
                                    <td class="text-end">{{ $formatQuantity((float) $row->unit_weight) }}</td>
                                    <td class="text-end">{{ $formatQuantity((float) $row->total_quantity) }}</td>
                                    <td class="text-end">{{ $row->unit_price === null ? '—' : number_format((float) $row->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ (float) $row->total_amount < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">Không có đơn đã giao hoặc hoàn tất trong ngày này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-muted">Tổng {{ number_format($monitoringJournalRows->count()) }} dòng</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <form method="POST" action="{{ route('pages.my_orders.monitoring.sales_journal.google_sheets') }}" onsubmit="return confirm('Ghi các dòng chưa có của ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} lên Google Sheets?');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <button type="submit" class="btn btn-success" @disabled(! $googleSheetsConfigured)>
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Báo cáo lên File Điều Hành
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
