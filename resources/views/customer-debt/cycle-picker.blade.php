<details class="debt-cycle-picker" id="debt-cycle-picker" open>
    <summary class="fw-semibold">Thêm bill vào kỳ</summary>
    <input type="hidden" name="cycle_bills" data-cycle-selected value="{{ json_encode($cyclePreview['bill_keys']) }}">
    <fieldset data-cycle-bill-picker disabled>
        <div class="debt-cycle-picker-controls">
            <input class="form-control form-control-sm" type="search" data-cycle-search aria-label="Tìm bill"
                placeholder="Tìm ngày giao hoặc mã bill">
            <select class="form-select form-select-sm" data-cycle-filter aria-label="Lọc bill theo trạng thái">
                <option value="available">Có thể gán</option>
                <option value="all">Tất cả bill</option>
            </select>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <label class="small"><input type="checkbox" data-cycle-all> Chọn các bill đang hiển thị</label>
            <span class="small text-muted" data-cycle-count aria-live="polite"></span>
        </div>
        <div class="debt-report-scroll debt-cycle-picker-scroll">
            <table class="table table-sm small mb-0">
                <thead><tr><th scope="col">Chọn</th><th scope="col">Ngày giao</th><th scope="col">Bill / dòng Sheet</th><th scope="col" class="text-end">Phải thu</th></tr></thead>
                <tbody>
                    @foreach($cyclePreview['bill_catalog'] as $key => $bill)
                        <tr data-cycle-row @if(!empty($bill['unavailable'])) data-cycle-unavailable @endif>
                            <td>
                                @if(empty($bill['unavailable']))
                                    <input type="checkbox" value="{{ $key }}" data-cycle-bill data-sales="{{ $bill['sales'] }}" data-gifts="{{ $bill['gifts'] }}"
                                        data-date="{{ \Carbon\Carbon::parse($bill['date'])->format('d/m/Y') }}" data-label="{{ $bill['label'] }}"
                                        aria-label="Chọn {{ $bill['label'] }} ngày {{ $bill['date'] }}" @checked(in_array($key, $cyclePreview['bill_keys'], true))>
                                @else
                                    <input type="checkbox" aria-label="{{ $bill['unavailable'] }}" disabled>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($bill['date'])->format('d/m/Y') }}</td>
                            <td>
                                {{ $bill['label'] }}
                                @if(!empty($bill['unavailable']))
                                    <small class="text-muted d-block">{{ $bill['unavailable'] }}</small>
                                @elseif($bill['carried'])
                                    <small class="text-muted d-block">Chưa gán từ kỳ trước</small>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">{{ number_format($bill['sales'] - $bill['gifts'], 0, ',', '.') }} đ</td>
                        </tr>
                    @endforeach
                    <tr data-cycle-empty hidden><td colspan="4" class="text-center text-muted py-3">Không có bill phù hợp.</td></tr>
                </tbody>
            </table>
        </div>
    </fieldset>
</details>
