<div class="debt-connection-info">
    <strong>Tài khoản Google Sheets đang cấu hình</strong>
    @if($connectionEmail)
        <div class="debt-connection-account">
            <input class="form-control form-control-sm" type="text" readonly value="{{ $connectionEmail }}" aria-label="Email tài khoản kết nối Google Sheets" onclick="this.select()">
            <button type="button" class="btn btn-sm btn-light" data-copy-sheet-account>Sao chép</button>
            <span class="small text-success" role="status" aria-live="polite"></span>
        </div>
        <div class="small text-muted">Nhờ quản trị mở <a href="https://docs.google.com/spreadsheets/d/{{ config('services.google_sheets.debt_spreadsheet_id') }}/edit" target="_blank" rel="noopener">file Google Sheet gốc</a> → Chia sẻ → thêm email trên với quyền <strong>Người xem</strong>, rồi bấm <strong>Cập nhật Google Sheet</strong>.</div>
    @else
        <div class="small text-muted">Chưa xác định được email tài khoản dịch vụ. Quản trị cần kiểm tra cấu hình kết nối Google Sheets.</div>
    @endif
</div>
