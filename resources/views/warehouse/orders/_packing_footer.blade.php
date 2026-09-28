<style>
    .packing-guide-footer {
        box-sizing: border-box;
        margin-top: 1.5rem;
        height: 120px;
        padding: .75rem 1.5rem;
        color: #334155;
        background: #fff;
        border-top: 1px solid #dbe7e5;
        box-shadow: 0 -4px 18px rgba(15, 23, 42, .04);
    }
    .packing-guide-footer__inner {
        display: grid;
        grid-template-columns: 245px minmax(0, 1fr);
        align-items: center;
        gap: 1.25rem;
        width: min(1400px, 100%);
        height: 100%;
        margin: 0 auto;
    }
    .packing-guide-footer__title {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .4rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }
    .packing-guide-footer__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .5rem 1rem;
    }
    .packing-guide-item {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        min-width: 0;
        font-size: .74rem;
        line-height: 1.25;
    }
    .packing-guide-item strong {
        display: block;
        color: #1e293b;
        font-size: .78rem;
    }
    .packing-guide-swatch {
        flex: 0 0 18px;
        width: 18px;
        height: 18px;
        margin-top: 1px;
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: 50%;
    }
    .packing-guide-swatch.is-waiting { background: #64748b; }
    .packing-guide-swatch.is-packing { background: #ffc107; }
    .packing-guide-swatch.is-done { background: #198754; }
    .packing-guide-swatch.is-sale-pending { background: #fff3cd; border-color: #f59e0b; border-radius: 5px; }
    .packing-guide-swatch.is-sale-confirmed { background: #d1e7dd; border-color: #198754; border-radius: 5px; }
    .packing-guide-swatch.is-cutting { background: #f5f3ff; border: 2px solid #7c3aed; border-radius: 5px; }
    .packing-guide-footer__note {
        margin-top: .35rem;
        color: #64748b;
        font-size: .72rem;
        line-height: 1.3;
    }
    @media (max-width: 991.98px) {
        .packing-guide-footer {
            height: auto;
            min-height: 120px;
        }
        .packing-guide-footer__inner {
            grid-template-columns: 1fr;
            gap: .75rem;
        }
        .packing-guide-footer__list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .packing-guide-footer { padding: 1rem; }
        .packing-guide-footer__list { grid-template-columns: 1fr; }
    }
</style>

<footer class="packing-guide-footer" aria-label="Chú giải màu sắc đơn đóng hàng">
    <div class="packing-guide-footer__inner">
        <div class="packing-guide-footer__intro">
            <div class="packing-guide-footer__title">
                <i class="bi bi-info-circle-fill text-primary" aria-hidden="true"></i>
                Màu đơn đóng hàng
            </div>
            <div class="packing-guide-footer__note">
                <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                Màu thể hiện tiến độ:
            </div>
            <div class="packing-guide-footer__note">
               <span class="text-muted pl-4 ml-4"> Xám → Vàng → Xanh lá.</span>
            </div>
        </div>
        <div class="packing-guide-footer__list">
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-waiting" aria-hidden="true"></span>
                <div><strong>Xám — Chờ đóng hàng</strong>Đơn chưa được kho nhận xử lý.</div>
            </div>
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-packing" aria-hidden="true"></span>
                <div><strong>Vàng — Đang đóng hàng</strong>Kho đã nhận đơn và đang cân, đóng gói.</div>
            </div>
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-done" aria-hidden="true"></span>
                <div><strong>Xanh lá — Đã hoàn thành</strong>Đơn đã đóng xong hoặc đã giao; mặc định được thu gọn.</div>
            </div>
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-sale-pending" aria-hidden="true"></span>
                <div><strong>Vàng nhạt — Sale vừa thay đổi</strong>Kho cần xem nội dung và xác nhận trước khi tiếp tục.</div>
            </div>
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-sale-confirmed" aria-hidden="true"></span>
                <div><strong>Xanh nhạt — Đã xác nhận thay đổi</strong>Kho đã ghi nhận nội dung điều chỉnh từ sale.</div>
            </div>
            <div class="packing-guide-item">
                <span class="packing-guide-swatch is-cutting" aria-hidden="true"></span>
                <div><strong>Viền tím — Đơn pha lóc</strong>Đơn đang có công đoạn lấy hàng hoặc pha lóc cần hoàn thiện.</div>
            </div>
        </div>
    </div>
</footer>
