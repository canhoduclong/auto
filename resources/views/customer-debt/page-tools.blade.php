<details class="debt-tools">
<summary class="btn btn-light">Thông tin & nguồn</summary>
<div class="debt-tools-panel">
    @if($canLoad) @include('customer-debt.connection-info') @endif
    <h6>Thông tin dữ liệu</h6>
    @if($snapshot && ($snapshot['spreadsheet_id'] ?? null) !== config('services.google_sheets.debt_spreadsheet_id'))<p class="text-warning">Bản dữ liệu đang hiển thị được tải trước khi đổi nguồn. Cập nhật Google Sheet để lấy dữ liệu từ file gốc.</p>@endif
    <p>Công nợ = Doanh thu − Tặng − Đã thanh toán + Điều chỉnh; không có số dư đầu kỳ. Số liệu tổng hợp từ nhatkybanhang và 5 tab thanh toán {{ implode(', ', \App\Services\CustomerDebtSheetService::PAYMENT_TABS) }}, không cộng thêm giá trị đơn trong hệ thống.</p>
    <p>So khớp thanh toán: Code (các tab tài khoản ngân hàng) = Loại; Tên KH = Tên KH/NCC. Chỉ lấy khoản Thu thuộc TTBH; Code không phải mã khách hàng.</p>
    <p>Điều chỉnh công nợ lưu riêng, không phải khoản thanh toán. Khi tải lại Sheet, chênh lệch điều chỉnh vẫn được cộng vào số dư; các phát sinh mới tiếp tục tăng / giảm công nợ.</p>
    @if(!empty($snapshot['gift_note_count']))<p>{{ $snapshot['gift_note_count'] }} ô Tặng chứa ghi chú được giữ trong chi tiết, không tính thành tiền.</p>@endif
    @if($snapshot && !empty($snapshot['issues']))<p class="text-warning">Khoản thu chưa rõ khách hàng chưa được trừ vào công nợ từng khách. Cần đối chiếu trước khi kết luận số nợ.</p>@endif
    <details class="mb-3"><summary>Cách tính cảnh báo</summary><p class="mt-2">Một khách / một ngày giao hàng tính là một Bill. Thanh toán trừ nợ cũ nhất trước; Tặng giảm số phải thu. Hạn thanh toán tính từ ngày giao hàng gần nhất. Trễ hạn khi vượt số ngày nợ được phép. Tuổi khoản nợ cũ nhất vẫn dùng để phân loại rủi ro. Nhắc thu khi chạm / vượt hạn mức, còn tối đa 3 ngày đến hạn hoặc tuổi nợ từ 15 ngày. Rủi ro: Trung bình 15–30 ngày · Cao 31–60 ngày · Rất cao trên 60 ngày.</p></details>
    @if($canLoad)<a href="https://docs.google.com/spreadsheets/d/{{ config('services.google_sheets.debt_spreadsheet_id') }}/edit?gid={{ config('services.google_sheets.debt_sales_sheet_id') }}" target="_blank" rel="noopener">Mở Sheet doanh thu gốc</a>@foreach(\App\Services\CustomerDebtSheetService::PAYMENT_SHEET_IDS as $tab=>$gid) · <a href="https://docs.google.com/spreadsheets/d/{{ config('services.google_sheets.debt_spreadsheet_id') }}/edit?gid={{ $gid }}" target="_blank" rel="noopener">{{ $tab }}</a>@endforeach @endif
    @if($canLoad)
    <details class="mt-3"><summary class="fw-semibold">Nhập từ Excel / dữ liệu dán</summary><div class="pt-3">
        <p>Mỗi lần tải thay thế toàn bộ bản dữ liệu cũ. Cần đủ lịch sử của tab bán hàng và các tab thanh toán để tính đúng công nợ.</p>
        
        <form method="POST" action="{{ route('customer-debt.load') }}" enctype="multipart/form-data" class="mb-3">@csrf<input type="hidden" name="source" value="excel"><label for="debt-workbook" class="form-label">Hoặc tải file Excel (.xlsx) gồm nhatkybanhang, Dongtienhoatdong; thêm các tab tài khoản ngân hàng nếu có</label><input id="debt-workbook" type="file" name="workbook" accept=".xlsx" class="form-control mb-2" required><button class="btn btn-primary">Đọc file Excel</button></form>
        <details><summary>Dán dữ liệu các tab</summary>
        <form method="POST" action="{{ route('customer-debt.load') }}" class="mt-3">@csrf<input type="hidden" name="source" value="paste">
            <label for="debt-sales" class="form-label">nhatkybanhang — sao chép cả tiêu đề</label><textarea id="debt-sales" name="sales_text" rows="5" class="form-control mb-3" required></textarea>
            <label for="debt-payments" class="form-label">Dongtienhoatdong — sao chép cả tiêu đề</label><textarea id="debt-payments" name="payments_text" rows="5" class="form-control mb-3" required></textarea>
            <label class="form-label" for="debt-bank-payments">TKCNMBBDUE (nếu có)</label><textarea id="debt-bank-payments" name="bank_payments_text" rows="4" class="form-control mb-3"></textarea>
            <button class="btn btn-primary">Đọc dữ liệu đã dán</button>
        </form></details>
    </div></details>
    @endif
</div>
</details>
