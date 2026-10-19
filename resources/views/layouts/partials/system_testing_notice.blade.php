@if(session()->pull('show_system_testing_notice', false))
<style>
    #system-testing-notice { width:min(640px,calc(100% - 32px)); max-height:calc(100dvh - 32px); overflow-y:auto; margin:auto; padding:0; border:0; border-radius:16px; color:#163c3a; background:#fff; box-shadow:0 20px 70px #0004; }
    #system-testing-notice::backdrop { background:rgba(15,23,42,.55); }
    #system-testing-notice .testing-notice-content { padding:28px; }
    #system-testing-notice h2 { margin:0 0 14px; font-size:21px; font-weight:700; }
    #system-testing-notice p { margin:0 0 14px; font-size:15px; line-height:1.6; }
    #system-testing-notice .testing-notice-period { padding:12px 14px; background:#fff7e6; border-left:4px solid #d97706; border-radius:6px; }
    #system-testing-notice .testing-notice-label { display:inline-block; margin-bottom:12px; padding:4px 9px; border-radius:5px; background:#fff1d6; color:#92400e; font-size:12px; font-weight:700; }
    #system-testing-notice .testing-notice-footer { display:flex; align-items:center; gap:16px; border-top:1px solid #e2e8f0; padding-top:16px; margin-top:18px; }
    #system-testing-notice .testing-notice-footnote { flex:1; min-width:0; margin:0; text-align:left; font-size:14px; line-height:1.65; color:#64748b; font-weight:400; font-style:italic; }
    @media(max-width:480px) { #system-testing-notice .testing-notice-content { padding:22px; } }
    #system-testing-notice .testing-notice-footer form { flex-shrink:0; margin:0; }
    #system-testing-notice button { display:block; margin-left:auto; padding:10px 22px; border:0; border-radius:8px; background:#0f766e; color:white; font:inherit; cursor:pointer; }
    #system-testing-notice button:focus-visible { outline:3px solid #f59e0b; outline-offset:3px; }
</style>
<dialog id="system-testing-notice" aria-labelledby="system-testing-notice-title" aria-describedby="system-testing-notice-description system-testing-notice-footnote">
    <div class="testing-notice-content">
        <span class="testing-notice-label">TEST / DRAFT — DỮ LIỆU THAM KHẢO</span>
        <h2 id="system-testing-notice-title">Thông báo giai đoạn kiểm thử hệ thống</h2>
        <div id="system-testing-notice-description">
            <p class="testing-notice-period"><strong>Thời gian kiểm thử dự kiến: từ 15/09/2026 đến hết 30/11/2026.</strong><br>Chế độ kiểm thử tiếp tục áp dụng cho đến khi có quyết định chính thức đưa hệ thống vào vận hành.</p>
            <p>Các đơn hàng, phiếu xuất/nhập kho, doanh thu, công nợ và báo cáo trên hệ thống đang ở trạng thái <strong>kiểm thử / bản nháp (TEST / DRAFT)</strong>, chỉ phục vụ tham khảo, kiểm tra và đối chiếu nội bộ; chưa được xác nhận là số liệu chính thức.</p>
            <p>Giao dịch thực tế vẫn phải được xử lý theo chứng từ và quy trình chính thức. Vui lòng báo lại các sai lệch hoặc lỗi phát sinh để được kiểm tra, xử lý.</p>
        </div>
        <footer class="testing-notice-footer">
            <p class="testing-notice-footnote" id="system-testing-notice-footnote">Dữ liệu kiểm thử chưa dùng làm căn cứ cho báo cáo chính thức, hạch toán kế toán, lập hóa đơn hoặc kê khai, quyết toán thuế. Số liệu cần được đối chiếu với thực tế, chứng từ gốc và phê duyệt trước khi sử dụng chính thức.</p>
            <form method="dialog"><button type="submit" autofocus>Đã hiểu</button></form>
        </footer>
    </div>
</dialog>
<script>
(function () {
    function showTestingNotice() {
        const dialog = document.getElementById('system-testing-notice');
        if (dialog && !dialog.open) dialog.showModal();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showTestingNotice, { once: true });
    } else {
        showTestingNotice();
    }
})();
</script>
@endif
