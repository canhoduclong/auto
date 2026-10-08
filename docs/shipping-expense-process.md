# Quản lý quy trình và chi phí ship

Module mới quản lý định nghĩa quy trình, phiên bản cấu hình, hồ sơ chạy và lịch sử xử lý. Hoạt động đã tích hợp: `shipping_expense` (xác nhận phí và tạo đề nghị thanh toán), `order_review` (xét duyệt hồ sơ đơn), `product_review` (xét duyệt hồ sơ sản phẩm). Hai hoạt động xét duyệt hồ sơ ghi nhận kết quả và lịch sử duyệt; không thay thế trạng thái vận hành/giao hàng hoặc tự sửa giá, tồn kho. Các hoạt động trả hàng, điều chỉnh đơn và thu chi hiện có vẫn dùng cơ chế cũ; cần bộ tích hợp nghiệp vụ riêng trước khi chuyển sang module này.

## Sử dụng

- Admin: `/process-management` tạo quy trình theo hoạt động; cấu hình tên bước, cách giao người xử lý, nút thao tác và vị trí hiển thị. Có thể thêm từ 1 đến 20 bước. `/process-runs` theo dõi các hồ sơ.
- Shipper: `/shipping-expenses` → Ghi nhận phí ship → chọn đơn đã giao/hoàn tất, nhập phí và diễn giải → gửi.
- Điều phối: `/shipping-expenses?queue=coordination` xác nhận, yêu cầu điều chỉnh hoặc từ chối.
- Kế toán: `/shipping-expenses?queue=accounting` xác nhận và chốt số liệu, yêu cầu điều chỉnh hoặc từ chối.
- Sau khi chốt, Shipper mở hồ sơ và gửi yêu cầu thanh toán. Hệ thống tạo phiếu thu chi theo cơ chế duyệt thanh toán hiện có; gửi phiếu không có nghĩa đã thanh toán.

Seeder `ShippingExpenseProcessSeeder` chỉ khởi tạo một lần: Biệt (`Biet Nguyen`, có vai trò manager_shipper) → Chi - Kế toán (accountant). Nếu không tìm thấy đúng tài khoản/vai trò thì tạo quy trình chưa kích hoạt, không tự chọn người thay thế.

## Quy tắc

Quyền xử lý yêu cầu đúng bước và theo chế độ được cấu hình: user cụ thể (không yêu cầu vai trò), theo vai trò (một người trong nhóm xử lý), hoặc user có vai trò chỉ định (cần cả hai). Bước dùng vai trò chỉ được hoàn thành một lần dù nhiều thành viên cùng mở hồ sơ. Quyền admin cấu hình không tự cho phép duyệt thay người được chỉ định. Menu điều phối/kế toán xét duyệt chỉ xuất hiện cho người có vai trò và được chỉ định trong cấu hình đang áp dụng hoặc phiên bản hồ sơ trước đó.

Điều chỉnh quay lại người khởi tạo. Có thể chọn quay về bước đã yêu cầu sửa hoặc duyệt lại từ bước đầu. Vì vậy yêu cầu sửa từ kế toán quay lại trực tiếp kế toán. Thông báo trong hệ thống được gửi cho Shipper và các người được chỉ định khi gửi, xử lý, điều chỉnh và tạo phiếu thanh toán.

Đơn phải có lịch sử điều phối đã xuất bản, chưa thu hồi và được phân cho chính Shipper đang gửi; đơn hiện thuộc Shipper đó và đã giao/hoàn tất. Mỗi đơn chỉ có một hồ sơ đang xử lý/đã chốt. Từ chối giải phóng đơn để lập hồ sơ mới. Không thêm/bỏ đơn khi điều chỉnh, chỉ sửa phí và diễn giải.

Phí đề nghị không cập nhật `orders.shipping_fee` trước khi kế toán chốt. Khi chốt ghi nhận chi phí; không tự thay đổi số tiền đã thu khách hay đánh dấu đã trả tiền. Mỗi lần xử lý lưu bản số liệu vào lịch sử. Phiếu thanh toán dùng đúng số tiền đã chốt và chỉ tạo một lần.

Mỗi hồ sơ lưu bản cấu hình và số phiên bản khi khởi tạo. Công bố cấu hình mới không thay đổi người xử lý của hồ sơ cũ. Các bước duyệt xét duyệt cũ trong `/approval-workflows` không bị chuyển đổi tự động.

## Triển khai và kiểm tra

Chạy migration và seeder:

```sh
php artisan migrate --force
php artisan db:seed --class=ShippingExpenseProcessSeeder --force
php artisan route:clear
php artisan view:clear
```

`tests/Feature/ProcessEngineTest.php` chạy với SQLite để kiểm tra phân quyền, thứ tự, điều chỉnh, kết thúc và lưu phiên bản. `php tests/Manual/ShippingExpenseWorkflow.php` kiểm tra tích hợp với cơ sở dữ liệu đã cấu hình (cần migration/seeder, tài khoản admin và ít nhất một khách hàng). Script tạo dữ liệu kiểm tra trong transaction và luôn rollback, bao gồm thông báo và phiếu thanh toán.

## Thiết kế một quy trình mới

1. Mở `/process-management/create`, nhập mã, tên và chọn hoạt động trong danh sách đã tích hợp.
2. Chọn vai trò khởi tạo. Riêng chi phí ship cần Shipper. Hồ sơ đơn cần người sở hữu hoặc quyền xem đơn; hồ sơ sản phẩm cần quyền xem sản phẩm hoặc vai trò quản lý phù hợp.
3. Từng bước: chọn user cụ thể/theo vai trò/user có vai trò; đặt tên nút xác nhận, bổ sung, từ chối. Xác nhận là thao tác bắt buộc, chuyển bước kế tiếp và ở bước cuối hoàn tất. Có thể tắt bổ sung/từ chối. Bổ sung/từ chối luôn cần lý do; từng thao tác có thể yêu cầu tài liệu.
4. Chọn gửi bổ sung quay lại bước hiện tại hoặc duyệt lại từ đầu.
5. Chọn Trang Cần xử lý và/hoặc danh sách/chi tiết thực thể đã được tích hợp. Vị trí ngoài danh sách được phép của hoạt động sẽ bị máy chủ từ chối.
6. Kích hoạt và lưu. Mỗi hoạt động có một định nghĩa đang áp dụng; kích hoạt định nghĩa mới ngừng áp dụng định nghĩa cũ cho hồ sơ mới. Hồ sơ đang chạy giữ bản cấu hình riêng.

`/process-inbox`: Cần xử lý, Tôi khởi tạo, Đã xử lý. Nút trên thực thể và thông báo đều mở cùng hồ sơ. Vị trí hiển thị không cấp quyền xử lý. Nút Gửi xét duyệt xuất hiện ở đơn/sản phẩm cho người có quyền khởi tạo và quyền với thực thể.

Tài liệu nằm trong disk local, được tải qua `/process-documents/{id}` sau kiểm tra quyền hồ sơ. Giữ tên gốc trong lịch sử và thêm hậu tố vào tên lưu. Tối đa 10 tệp/lần, 20MB/tệp. Transaction lỗi sẽ xóa tệp mới đã lưu.

Kết thúc phí ship áp dụng số tiền đã chốt cho từng đơn và mở quyền gửi thanh toán. Kết thúc hồ sơ đơn/sản phẩm lưu kết quả xét duyệt cùng bản thông tin tại thời điểm xử lý. Muốn tự động đổi giá, xuất kho, trả hàng hoặc thay trạng thái vận hành cần hoạt động tích hợp riêng, không nhập tên hành động tùy ý để sửa dữ liệu.


### Ghi nhận phí ship khi hoàn thành đơn trên my_app

Hai màn hình hoàn thành đơn (nhanh và chi tiết) có phí ship đề nghị và diễn giải. Phí được định dạng theo Việt Nam, giới hạn 0–1.000.000.000đ; để trống thì không gửi yêu cầu.

API `POST /api/mobile/shipper/orders/{order}/complete-delivery` nhận thêm `shipping_expense_amount` (số nguyên, tùy chọn) và `shipping_expense_note` (tối đa 1.000 ký tự). Có phí thì tạo yêu cầu một đơn qua `ShippingExpenseService`, theo cấu hình quy trình đang áp dụng. Hoàn thành đơn và tạo yêu cầu nằm trong cùng giao dịch; thất bại tạo yêu cầu sẽ hoàn tác hoàn thành đơn. Phí chính thức chưa thay đổi cho đến khi kế toán xác nhận. Yêu cầu xuất hiện trong danh sách yêu cầu và hộp Cần xử lý của người được giao. Ứng dụng cũ không gửi hai trường này vẫn hoàn thành đơn như trước.

Kiểm tra API bằng `php tests/Manual/MobileShippingExpense.php` (dữ liệu kiểm tra luôn rollback). Kiểm tra trường nhập tiền bằng `flutter test test/shipping_expense_fields_test.dart` trong my_app.
