<div class="row g-2 align-items-start mb-2" data-allocation-item>
    <div class="col-sm">
        <select class="form-select form-select-sm" name="items[{{ $itemIndex }}][target]" aria-label="Khoản nợ nhận tiền" required>
            <option value="">Chọn nợ cũ hoặc bill</option>
            @foreach($oldDebtDates as $date)
                @php($remaining = $allocationForm['targets']->where('date', '<', $date)->sum('remaining'))
                @if($remaining > 0)
                    <option value="legacy:{{ $date }}" @selected(($item['target'] ?? null) === 'legacy:'.$date)>Nợ cũ trước {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · Còn {{ number_format($remaining, 0, ',', '.') }} đ</option>
                @endif
            @endforeach
            @foreach($allocationForm['targets'] as $key => $line)
                <option value="{{ $key }}" @selected(($item['target'] ?? null) === $key)>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }} · {{ $line['code'] ?: $key }} · Còn {{ number_format($line['remaining'], 0, ',', '.') }} đ</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-3">
        <input class="form-control form-control-sm" name="items[{{ $itemIndex }}][amount]" type="number" min="1" max="{{ $allocationForm['available'] }}" step="1" inputmode="numeric" aria-label="Số tiền phân bổ" value="{{ $item['amount'] ?? '' }}" required>
    </div>
    <div class="col-auto"><button type="button" class="btn btn-sm btn-light" data-allocation-remove aria-label="Bỏ dòng ghép">×</button></div>
</div>
