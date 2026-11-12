<details class="debt-tools">
<summary class="btn btn-light">Thông tin & nguồn</summary>
<div class="debt-tools-panel">
    <h6>Thông tin dữ liệu</h6>
    <p>Công nợ = Doanh thu − Tặng − Đã thanh toán; không có số dư đầu kỳ. Số liệu tổng hợp từ hai tab Sheet, không cộng thêm giá trị đơn trong hệ thống.</p>
    @if(!empty($snapshot['gift_note_count']))<p>{{ $snapshot['gift_note_count'] }} ô Tặng chứa ghi chú được giữ trong chi tiết, không tính thành tiền.</p>@endif
    @if($snapshot && !empty($snapshot['issues']))<p class="text-warning">Khoản thu chưa rõ khách hàng chưa được trừ vào công nợ từng khách. Cần đối chiếu trước khi kết luận số nợ.</p>@endif
    <details class="mb-3"><summary>Cách tính cảnh báo</summary><p class="mt-2">Một khách / một ngày giao hàng tính là một Bill. Thanh toán trừ nợ cũ nhất trước; Tặng giảm số phải thu. Trễ hạn khi tuổi khoản còn nợ lâu nhất vượt số ngày được phép nợ. Nhắc thu khi chạm / vượt hạn mức, còn tối đa 3 ngày đến hạn hoặc tuổi nợ từ 15 ngày. Rủi ro: Trung bình 15–30 ngày · Cao 31–60 ngày · Rất cao trên 60 ngày.</p></details>
    @if($canLoad)<a href="https://docs.google.com/spreadsheets/d/{{ config('services.google_sheets.debt_spreadsheet_id') }}/edit" target="_blank" rel="noopener">Mở Google Sheet nguồn</a>@endif
    @if($canLoad)
    <details class="mt-3"><summary class="fw-semibold">Nhập từ Excel / dữ liệu dán</summary><div class="pt-3">
        <p>Mỗi lần tải thay thế toàn bộ bản dữ liệu cũ. Cần đủ lịch sử của cả hai tab để tính đúng công nợ.</p>
        
        <form method="POST" action="{{ route('customer-debt.load') }}" enctype="multipart/form-data" class="mb-3">@csrf<input type="hidden" name="source" value="excel"><label for="debt-workbook" class="form-label">Hoặc tải file Excel (.xlsx) gồm hai tab nhatkybanhang và Dongtienhoatdong</label><input id="debt-workbook" type="file" name="workbook" accept=".xlsx" class="form-control mb-2" required><button class="btn btn-primary">Đọc file Excel</button></form>
        <details><summary>Dán dữ liệu từ hai tab</summary>
        <form method="POST" action="{{ route('customer-debt.load') }}" class="mt-3">@csrf<input type="hidden" name="source" value="paste">
            <label for="debt-sales" class="form-label">nhatkybanhang — sao chép cả tiêu đề</label><textarea id="debt-sales" name="sales_text" rows="5" class="form-control mb-3" required></textarea>
            <label for="debt-payments" class="form-label">Dongtienhoatdong — sao chép cả tiêu đề</label><textarea id="debt-payments" name="payments_text" rows="5" class="form-control mb-3" required></textarea>
            <button class="btn btn-primary">Đọc dữ liệu đã dán</button>
        </form></details>
    </div></details>
    @endif
</div>
</details>
