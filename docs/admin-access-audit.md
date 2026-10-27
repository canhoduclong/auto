# Rà soát quyền khu vực admin — 27/10/2026

## Quy tắc

- `/admin` và `/admin/*` chỉ cho tài khoản có vai trò `admin` (`User::isAdmin()`). Quyền chức năng hoặc vai trò Manager, CEO, Director, kho, kế toán, shipper không thay thế vai trò admin.
- `EnsureAdminArea` nằm trong nhóm middleware `web`, sau khi phiên đăng nhập được nạp và trước controller. Áp dụng cho cả GET, POST, PUT, PATCH, DELETE và AJAX. Khách chưa đăng nhập được chuyển đến đăng nhập (JSON: 401); tài khoản không phải admin bị từ chối 403 theo cơ chế xử lý lỗi hiện có.
- Route riêng `admin/orders/sync-daily-sequence` khai báo ngoài nhóm web vẫn có `auth` và `role:admin`.
- Composer của `layouts.admin` và `layouts.admin-accounting` kiểm tra admin độc lập, kể cả khi layout được gọi từ URL khác.

## Phát hiện và thay đổi

Một số route trước đây chỉ có đăng nhập/quyền chức năng hoặc cho phép nhiều vai trò: nội dung (pages/posts/post-categories), sự kiện, thương hiệu, tuyến/trạm xe, nhà cung cấp và hoàn chỉnh đơn lịch sử. Lớp kiểm tra chung hiện bắt buộc admin cho tất cả các URL này.

Trang giao việc dùng chung chọn layout theo `TaskWorkspace`; trang tỉnh/thành dùng layout app với người dùng không phải admin. Các quyền nghiệp vụ sẵn có vẫn được kiểm tra. Menu kế toán chỉ hiện liên kết `/admin/imported-sales-orders` cho tài khoản admin.

## Kiểm chứng

`AdminAreaAccessTest` duyệt toàn bộ 188 route `/admin` để kiểm tra middleware; kiểm tra các phương thức đọc/ghi, khách chưa đăng nhập, vai trò ngoài admin, chặn layout, và đường dẫn nghiệp vụ bên ngoài `/admin`.

Chạy cùng `AdminTransportPermissionsTest`: 7 bài kiểm thử, 248 assertions thành công. Có deprecation từ môi trường/framework hiện tại. Blade biên dịch thành công.
