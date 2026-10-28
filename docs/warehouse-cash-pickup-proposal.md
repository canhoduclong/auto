# Hàng đặc biệt: giao tại kho, chờ kế toán xác nhận

## Cấu hình

Admin bật Hàng đặc biệt ở cả sản phẩm và khách hàng, đồng thời cấu hình shipper mặc định của khách qua chức năng hiện có. Các công tắc mặc định tắt. Chỉ khi khách và mọi sản phẩm đều bật thì nút **Tạo và hoàn tất tại kho** xuất hiện trên màn hình tạo đơn trong theo dõi đơn và màn hình lên đơn theo khách.

## Luồng xử lý hiện tại

1. Người lên đơn chọn kho và nguồn hàng, kiểm tra số lượng/khối lượng thực giao, đơn giá, VAT và xác nhận khách đã nhận hàng.
2. Nếu xuất từ tồn hiện có: tạo phiếu xuất và trừ tồn khả dụng. Không sử dụng hàng giữ cho đơn khác.
3. Nếu hàng vừa nhập thực tế: ghi nguồn nhập, giá vốn từng dòng, tạo phiếu nhập rồi phiếu xuất liên kết cùng đơn.
4. Hoàn tất **giao hàng** với `status=delivered`, `delivery_status=delivered`; giữ shipper mặc định và sale phụ trách. Đơn hiển thị **chờ kế toán xác nhận doanh thu**, `payment_status=unpaid`, `amount_paid=0`, `collected_amount=0`.
5. Kế toán cập nhật thanh toán bằng nghiệp vụ kế toán hiện có, chọn quỹ/tài khoản và xác nhận tiền thực thu. Tạo đơn đặc biệt không yêu cầu quỹ, không tạo phiếu thu và không đổi số dư.
6. Kế toán xác nhận doanh thu ở màn hình đối soát. Lúc đó đơn mới chuyển `completed`, ghi doanh thu, hoa hồng và công nợ theo thực tế đã thu. Doanh số ship thuộc shipper mặc định trên đơn/đối soát.

Kế toán có thể thu tiền trước hoặc sau khi xác nhận doanh thu. Thu đủ tiền trước không tự chuyển đơn đặc biệt sang `completed` nếu doanh thu chưa được xác nhận. Doanh thu đã được kế toán xác nhận nhưng chưa thu tiền vẫn là đơn hoàn tất có công nợ, không phải đã thanh toán.

Trước xác nhận doanh thu, `amount_due=0` tuân theo quy tắc hệ thống: công nợ chỉ ghi nhận khi kế toán xác nhận; giá trị đơn vẫn nằm ở `total` để đối chiếu.

## Quyền và kiểm soát

Sale chỉ thao tác với khách đang quản lý; admin có thể làm thay nhưng giữ sale trong hồ sơ khách. Kiểm tra công tắc, shipper mặc định, kho hoạt động và giá tối thiểu ở máy chủ. Endpoint giao hàng từ chối các trường thu tiền của giao diện cũ; cập nhật phiên bản script để tải giao diện mới.

Đơn và chứng từ kho được xử lý trong transaction, lỗi sẽ hoàn tác toàn bộ. Creation token chống gửi lặp. Sao chép đơn xóa trạng thái đặc biệt và liên kết chứng từ cũ. Nhật ký ghi đã giao và chờ kế toán, không ghi kế toán đã xác nhận khi chưa thực hiện.

Các đơn/chứng từ cũ đã ghi nhận trước khi đổi luồng không tự bị đảo hoặc xóa. Lịch đơn mẫu tự động vẫn dùng luồng tạo đơn chuẩn.
