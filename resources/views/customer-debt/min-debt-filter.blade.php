<div class="debt-filter-field">
    <label class="form-label" for="min-debt-filter">Công nợ lớn hơn (đ)</label>
    <input id="min-debt-filter" class="form-control" name="min_debt" type="number" min="0" max="999999999999999" step="1"
        value="{{ request('min_debt') ?? 100000 }}" title="Áp dụng khi chọn Đang có nợ">
</div>
