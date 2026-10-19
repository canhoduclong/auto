<?php

namespace App\Services;

use App\Models\Setting;

class LoginNoticeService
{
    public function configuration(): array
    {
        $defaults = [
            'enabled' => true,
            'title' => 'Thông báo giai đoạn kiểm thử hệ thống',
            'content' => "Thời gian kiểm thử dự kiến: từ 15/09/2026 đến hết 30/11/2026.\nChế độ kiểm thử tiếp tục áp dụng cho đến khi có quyết định chính thức đưa hệ thống vào vận hành.\n\nCác đơn hàng, phiếu xuất/nhập kho, doanh thu, công nợ và báo cáo trên hệ thống đang ở trạng thái kiểm thử / bản nháp (TEST / DRAFT), chỉ phục vụ tham khảo, kiểm tra và đối chiếu nội bộ; chưa được xác nhận là số liệu chính thức.\n\nGiao dịch thực tế vẫn phải được xử lý theo chứng từ và quy trình chính thức. Vui lòng báo lại các sai lệch hoặc lỗi phát sinh để được kiểm tra, xử lý.",
            'footer' => 'Dữ liệu kiểm thử chưa dùng làm căn cứ cho báo cáo chính thức, hạch toán kế toán, lập hóa đơn hoặc kê khai, quyết toán thuế. Số liệu cần được đối chiếu với thực tế, chứng từ gốc và phê duyệt trước khi sử dụng chính thức.',
        ];
        $saved = json_decode((string) Setting::get('system_login_notice', ''), true);

        return array_replace($defaults, is_array($saved) ? array_intersect_key($saved, $defaults) : []);
    }
}
