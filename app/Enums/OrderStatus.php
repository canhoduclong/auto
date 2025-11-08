<?php

namespace App\Enums;

enum OrderStatus: string
{
 /**
     * draft = Nháp
     * - Trạng thái đơn hàng mới tạo bởi Sale
     * - Chưa được duyệt, chưa chính thức
     */
    case Draft = 'draft';

    /**
     * pending = Chờ duyệt
     * - Đơn đã được Sale gửi đi
     * - Đang chờ Leader duyệt
     */
    case Pending = 'pending';

    /**
     * leader_confirmed = Leader đã duyệt
     * - Leader đã kiểm tra và chấp thuận đơn hàng
     * - Chuyển sang bước duyệt của Kế toán
     */
    case LeaderConfirmed = 'leader_confirmed';

    /**
     * accounting_planned = Kế toán duyệt
     * - Kế toán đã kiểm tra giá, công nợ, chi phí
     * - Nếu hợp lệ thì chuyển sang Giám đốc duyệt
     */
    case AccountingPlanned = 'accounting_planned';

    /**
     * manager_confirmed = Giám đốc duyệt
     * - Giám đốc (Manager/CEO) đã phê duyệt đơn hàng
     * - Sau bước này đơn mới có hiệu lực để giao cho Kho hoặc Nhà máy
     */
    case ManagerConfirmed = 'manager_confirmed';

    /**
     * warehouse_confirmed = Kho xác nhận
     * - Bộ phận Kho đã kiểm tra hàng hóa sẵn sàng để xuất kho
     */
    case WarehouseConfirmed = 'warehouse_confirmed';

    /**
     * factory_confirmed = Nhà máy xác nhận
     * - Nhà máy xác nhận đã sản xuất/chuẩn bị hàng xong
     * - Đơn có thể chuyển sang trạng thái giao hàng
     */
    case FactoryConfirmed = 'factory_confirmed';

    /**
     * shipping = Đang giao
     * - Đơn đã rời kho hoặc nhà máy
     * - Đang trong quá trình vận chuyển tới khách hàng
     */
    case Shipping = 'shipping';

    /**
     * delivered = Đã giao
     * - Đơn đã được giao thành công cho khách hàng
     * - Hoàn tất quy trình đơn hàng
     */
    case Delivered = 'delivered';

    /**
     * returned = Bị trả lại
     * - Khách hàng từ chối nhận hoặc có vấn đề phát sinh
     * - Đơn hàng bị trả về công ty
     */
    case Returned = 'returned';
}
