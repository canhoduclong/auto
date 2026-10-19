# Điều hành & Giao việc trên app.com

Bản web đầu tiên, ngày 06/10/2026. Điểm vào: /operating, thanh Điều hành & Giao việc trong các layout Administrator, CEO, Giám đốc, Kế toán, Kinh doanh, Kho, Đóng hàng và Shipper/Điều phối.

## Chọn loại nghiệp vụ

| Loại | Khi dùng | Luồng |
|---|---|---|
| Giao thực hiện | Cần người chịu trách nhiệm làm và gửi kết quả | Giao → nhận → bắt đầu → báo cáo hoàn thành → người giao nghiệm thu/trả lại |
| Yêu cầu phối hợp | Cần người/đơn vị khác hỗ trợ | Đề nghị → chủ trì tiếp nhận hoặc từ chối có lý do → thực hiện → gửi kết quả → xác nhận |
| Đề xuất biểu quyết | Cần lấy quyết định từ một nhóm người | Mở hồ sơ → bỏ phiếu → chốt kết quả → nếu thông qua thì giao công việc triển khai |

Giao thực hiện/phối hợp có thể chọn quy trình phê duyệt sẵn có. Duyệt theo từng bước không phải biểu quyết: quy trình duyệt tuần tự xét thẩm quyền, còn biểu quyết tổng hợp phiếu của danh sách thành viên.

## Biểu quyết

1. Người có quyền giao việc hoặc CEO/Giám đốc/admin mở Đề xuất biểu quyết.
2. Điền tiêu đề, nội dung TinyMCE, chọn người tham gia, hạn bỏ phiếu, số phiếu tối thiểu, tỷ lệ đồng ý.
3. Mỗi thành viên được mời có một phiếu: Đồng ý / Không đồng ý / Không ý kiến, kèm ý kiến tùy chọn.
4. Danh sách người mời, nội dung và quy tắc không sửa sau khi mở. Phiếu đã gửi không sửa hoặc gửi lại.
5. Người mở/admin chốt khi hết hạn hoặc mọi thành viên đã bỏ phiếu. Hết hạn chỉ khóa bỏ phiếu, không tự chốt.
6. Thiếu phiếu tham gia: Không đủ số phiếu. Đủ phiếu nhưng thiếu đồng ý: Không thông qua. Đủ cả hai: Thông qua.
7. Hồ sơ thông qua có nút Giao việc từ đề xuất đã thông qua; giữ liên kết nguồn và danh sách việc triển khai.

Tỷ lệ đồng ý tính trên TẤT CẢ người được mời; người chưa bỏ phiếu không được coi là đồng ý. Không ý kiến tính tham gia nhưng không tăng số đồng ý.

Ví dụ: mời 5 người, tối thiểu 3 phiếu, tỷ lệ 51%. Cần ít nhất 3 người tham gia và ít nhất 3 phiếu đồng ý để thông qua. Chỉ có 2 đồng ý dù cả 5 đã bỏ phiếu thì không thông qua.

Biểu quyết đang công khai với những người tham gia, người mở và admin. Đây là cơ chế biểu quyết nội bộ; chưa phải hồ sơ nghị quyết HĐQT có chữ ký/biểu quyết kín.

## Giao việc

Chọn loại, người chủ trì, danh sách người nhận, hạn tiếp nhận, deadline, nội dung và quy trình duyệt nếu cần. Chủ trì phải thuộc danh sách người nhận. Một việc có một chủ trì; các thành viên được giao vẫn giữ phần việc/báo cáo riêng của cơ chế hiện có.

Việc mới chờ tiếp nhận. Mỗi người nhận bấm Tiếp nhận, rồi cập nhật Đang thực hiện. Có lưu accepted_at và started_at. Nếu đang chờ duyệt giao, chưa được bắt đầu. Người thực hiện gửi báo cáo hoàn thành; người giao/admin nghiệm thu hoặc trả lại. Giữ cơ chế bằng chứng ảnh và báo cáo hiện có.

Việc con mới cũng chọn chủ trì và hạn tiếp nhận. Người nhận chủ trì yêu cầu phối hợp có quyền từ chối trước khi tiếp nhận, bắt buộc lý do, có lưu lịch sử và thông báo người đề nghị.

## Quyền và dữ liệu

- Menu tạo chỉ hiện khi có quyền; người không có quyền vẫn có thể xem/nhận việc được giao và bỏ phiếu được mời.
- Công việc mới giới hạn theo người giao, người nhận, liên kết cha/con được phép và các bước duyệt liên quan; không mặc định mọi manager xem/nghiệm thu mọi việc.
- Chọn layout theo vai trò đang sử dụng/tài khoản. Không dùng layout thay cho kiểm tra quyền backend.
- Có thông báo trong hệ thống khi mở biểu quyết, chốt kết quả, giao việc/việc con và từ chối phối hợp.
- Công việc cũ giữ dữ liệu/trạng thái và không bị bắt buộc bổ sung thời điểm tiếp nhận giả.
- Mã công việc tính cả bản đã xóa, cấp số bằng sequence để tránh trùng khi nhiều người tạo.

## Phạm vi tiếp theo

Bản này triển khai ba loại nghiệp vụ và luồng web cốt lõi. Các đợt tiếp theo của bản kiến trúc: báo cáo tiến độ định kỳ, vướng mắc riêng có SLA, nhắc việc tự động/chuyển cấp, hồ sơ nghị quyết và biểu quyết kín nếu cần, báo cáo điều hành tổng hợp, app Flutter. Không có APK mới trong đợt web này.

## Kiểm tra và triển khai

Các migration mới: 2026_10_06_170000_create_operating_votes_and_task_acceptance, 2026_10_06_171000_create_task_code_sequences. Đã chạy trên database workspace app.com. Server khác cần chạy migration trước khi dùng code mới.

Kiểm tra: bài kiểm thử biểu quyết (quyền, hạn, quorum, phiếu trắng, mẫu số, chốt lặp), tiếp nhận/bắt đầu, hồi quy thu hồi/xóa; kiểm tra MySQL trọn luồng, dữ liệu thử được rollback; render trang điều hành trong 8 layout. Chưa có kiểm tra tự động trình duyệt.

## Xóa cứng đơn sai số liệu tại trang Tiếp nhận

Chức năng này chỉ dành cho tài khoản có quyền admin, nhằm loại bỏ đơn có số liệu sai sót. Đơn đã cũ không phải là lý do tự động xóa. Tài khoản kho, shipper và quản lý không có quyền admin không được sử dụng; máy chủ kiểm tra quyền ngay cả khi gửi yêu cầu trực tiếp.

Admin mở mục “Admin: Xóa cứng đơn sai số liệu” trên thẻ đơn, nhập lý do và xác nhận mã đơn trước khi xóa. Thao tác xóa toàn bộ đơn, không chỉ phiếu điều chuyển; hệ thống lưu lý do, người xóa và bản chụp dữ liệu.

Giới hạn hiện tại: việc hoàn tồn khi xóa chưa bao phủ đầy đủ biến động gắn với phiếu xuất/nhập và phiếu điều chuyển. Không coi thao tác xóa là xác nhận hàng thực tế đã trở về kho.

## Tiếp nhận đơn điều chuyển tại kho nhận

Đơn điều chuyển xuất hiện trong danh sách “Hàng được điều chuyển tới” ngay khi kho gửi tạo phiếu, kể cả ngày giao khác ngày đang xem. Luồng thao tác:

1. Chờ shipper nhận hàng tại kho gửi: chỉ hiển thị thông tin, chưa có nút thao tác.
2. Shipper đã nhận hàng, đang vận chuyển: hiện nút vàng “Kéo về kho”. Kho dùng khi hàng đã đến nhưng shipper chưa bấm bàn giao; thao tác chuyển đơn sang chờ xác nhận nhập kho.
3. Shipper đã bàn giao: ẩn nút “Kéo về kho”, hiện “Xác nhận nhập vào kho”.
4. Kho xác nhận nhập: đơn chuyển sang “Đã nhận hàng”; lịch sử đã nhận tuân theo bộ lọc ngày.

Chức năng xóa cứng số liệu sai vẫn chỉ dành cho admin; không hiển thị trên đơn đang chờ shipper nhận tại kho gửi.

## Bảo toàn hàng đã đóng khi điều chuyển và giao hàng

Khi chốt đóng hàng, từng dòng hàng phải có đủ tồn giữ riêng cho đơn và tổng lượng giữ không được vượt tồn thực tế. Tồn khả dụng là tồn thực tế trừ lượng giữ riêng.

Shipper lấy hàng điều chuyển phải xuất phần giữ riêng của chính đơn; không lấy hàng khả dụng khác rồi giữ lại reservation cũ. Kho nhận nhập hàng và giữ riêng lại cho đơn trong cùng giao dịch. Tiếp nhận thường, tiếp nhận Package và xử lý ngoại lệ dùng chung cơ chế này. Phiếu đã xuất không bị trừ tồn nguồn thêm lần nữa. Yêu cầu nhận hoặc nhập lặp được kiểm tra dưới khóa giao dịch.

Shipper lấy đi giao tiêu thụ lượng giữ riêng và ghi xuất kho, kể cả đơn nhận từ ngày trước có hàng đã được chốt giữ riêng. Hủy đơn khi hàng còn ở kho giải phóng lượng giữ riêng, không cộng thêm số lượng tồn thực tế. Các cập nhật tồn qua model Inventory bị chặn nếu giảm xuống dưới lượng đã đóng giữ riêng.

Dữ liệu cũ thiếu tồn hoặc thiếu chứng từ phải được đối soát; không tự cộng tồn để vượt lỗi nhận đơn. Không tìm thấy đơn ORD-A58D29DAB8 trong database workspace tại thời điểm kiểm tra, nên chưa sửa dữ liệu riêng của đơn trong ảnh.

## Danh sách hàng đã đóng trong kiểm kê

Trang kiểm kê có mục “Hàng tồn theo đơn đã đóng”, hiển thị mã đơn, khách hàng, sản phẩm/biến thể, SKU, số lượng giữ riêng, kg đã đóng và thời điểm chốt giữ hàng. Đây là dữ liệu hiện tại của kho đang chọn, có lọc theo sản phẩm và phân trang riêng, không phải ảnh chụp tồn theo ngày kiểm kê lịch sử.

Danh sách chỉ xem, nằm ngoài biểu mẫu cập nhật kiểm kê, không có ô sửa hoặc thao tác xóa. Chỉ các lượng giữ hàng còn tồn tại ở kho được hiển thị; đơn đã hủy, đã giao, hoàn tất hoặc đưa vào thùng rác được loại ra. Dữ liệu cũ thiếu kg hoặc thời điểm chốt hiển thị “Chưa ghi nhận”.

## Sale điều chỉnh đơn sau khi giao hàng

Sale có thể bấm “Gửi yêu cầu điều chỉnh” trên đơn của mình đã giao hoặc hoàn tất, không cần chờ kế toán xác nhận doanh thu trước. Biểu mẫu hiện có hỗ trợ điều chỉnh sản phẩm, số lượng, khối lượng, đơn giá, phí và bằng chứng. Khi gửi, hệ thống lưu yêu cầu riêng; chưa áp dụng thay đổi vào số liệu đơn. Không gửi thêm khi đơn còn yêu cầu chờ duyệt hoặc đang xử lý.

Trong Đối soát đơn giao hàng, mục “Yêu cầu điều chỉnh đơn đã giao — chờ Kế toán duyệt” hiển thị tất cả yêu cầu tới bước kế toán, độc lập bộ lọc ngày của danh sách đơn. Mở yêu cầu để so sánh đơn đã giao và nội dung đề nghị, xem bằng chứng, duyệt hoặc từ chối có lý do. Điều chỉnh sau giao hàng cần kế toán duyệt thủ công; không tự động duyệt. Nếu phát sinh trả hàng, vẫn phải hoàn tất xử lý kho theo luồng hiện có. Đơn còn điều chỉnh chưa xử lý xong không được xác nhận doanh thu mới.

## Badge cảnh báo công việc kho

Bảy mục Tiến độ công việc có badge đỏ biểu thị số việc cần xử lý hoặc thay đổi cần kiểm tra; không hiện badge khi bằng 0, hiển thị 99+ khi vượt 99. Đơn cần đóng tính cả phản hồi thay đổi của sale còn cần kho xem; Review & In ấn nhắc lại khi đơn cập nhật sau lần in gần nhất. Nhiệm vụ được giao lấy dữ liệu thực của người dùng đang đăng nhập.

Badge phản ánh trạng thái công việc, không phải bộ đếm thông báo chưa đọc: mở trang không làm mất badge, xử lý xong mới hết cảnh báo khi tải lại bảng điều khiển. Các nhãn thông báo mẫu cố định trước đây đã được bỏ để tránh báo thay đổi không có thật.

## Admin xóa phiếu xuất kho tổng đã xuất

Trong danh sách và chi tiết phiếu xuất kho tổng, admin có nút “Admin: Xóa phiếu đã xuất”, kể cả phiếu đã có tài xế nhận hoặc kho đích tiếp nhận. Bắt buộc nhập lý do 5–500 ký tự và xác nhận mã phiếu. Máy chủ kiểm tra quyền admin, khóa phiếu trong giao dịch và ghi nhật ký kèm dữ liệu phiếu, các dòng bàn giao trước khi xóa.

Thao tác chỉ xóa phiếu tổng và các dòng liên kết trên phiếu; giữ đơn hàng, phiếu điều chuyển, chứng từ xuất/nhập thực tế và số liệu tồn kho. Đây không phải thao tác hủy xuất hàng hoặc hoàn tồn. Chức năng xóa phiếu đang mở vẫn theo quyền hiện có.

## Quản lý vận chuyển trong admin

Menu admin có Quản lý lộ trình giao hàng, Quản lý điều chuyển, Xử lý hàng ở tài xế và Phiếu xuất kho tổng. Trang lộ trình và lịch sử dùng bố cục admin khi đăng nhập admin. Quản lý điều chuyển xem toàn bộ kho, lập nhóm, gỡ đơn theo trạng thái vận chuyển, xóa nhóm chưa xuất và chuyển tài xế.

Admin được đổi tài xế cho đơn giao khách đang thực hiện mà không xuất kho lần nữa. Đổi tài xế điều chuyển yêu cầu lý do; nếu nhóm nằm trong phiếu xuất tổng thì áp dụng cho các đơn điều chuyển chưa hoàn tất của toàn phiếu, đồng bộ tài xế nhóm và phiếu, ghi lịch sử từng đơn cùng nhật ký admin. Đơn đã nhập kho xong không bị đổi tài xế vận chuyển trong lịch sử.

Phiếu xuất tổng có các chức năng lập, xem, sửa khi đang mở, chốt, gỡ chốt trước khi tài xế nhận, in xuất/nhập, gỡ đơn còn chờ nhận, đổi tài xế và xóa phiếu tổng đã xuất dành cho admin. Hàng đã ở tài xế dùng mục Xử lý hàng ở tài xế để hoàn tất hoặc xác nhận trả kho theo thực tế; không xóa liên kết để tự cộng tồn khi hàng chưa về.

## Tab Điều chuyển đơn

Trang `/warehouse/inventory-transfers` có tab Lập điều chuyển và Điều chuyển đơn (`?tab=orders`). Tab Điều chuyển đơn dùng danh sách nhóm điều chuyển hiện có, hiển thị kho nhận, tài xế, các đơn, trạng thái và phiếu xuất tổng liên quan; hỗ trợ lọc ngày và mã đơn/khách hàng. Mặc định tab này xem lịch sử từ năm 2000 đến hôm nay để không bỏ sót phiếu cũ.

Xóa phiếu xuất tổng không hủy phiếu điều chuyển bên trong. Vì vậy đơn vẫn xuất hiện ở Tiếp nhận. Muốn bỏ điều chuyển, dùng thao tác gỡ đơn hoặc xóa phiếu điều chuyển chưa được shipper nhận; hàng đã vận chuyển hoặc đã nhập kho cần xử lý đúng luồng trạng thái và tồn kho. Không tự hủy điều chuyển khi xóa phiếu tổng.

## Hủy toàn bộ phiếu điều chuyển đã tiếp nhận (admin)

Thay thế thao tác xóa phiếu lịch sử bằng “Hủy toàn bộ phiếu & đơn”. Chỉ admin, bắt buộc lý do và xác nhận ảnh hưởng. Toàn bộ đơn trong phiếu chuyển sang Hủy, gỡ tài xế, hủy dữ liệu đóng hàng, giải phóng giữ tồn và về kho gửi ban đầu. Xóa các bản ghi tiếp nhận/điều chuyển, lịch sử giao nhận/đóng hàng, đối soát, doanh thu sale và hoa hồng liên quan; lưu bản chụp trong nhật ký admin và ghi biến động bù để giữ lịch sử tồn kho.

Hoàn tồn dựa trên biến động xuất/nhập đã ghi nhận, không cộng lại số lượng đơn một cách mù quáng. Thiếu chứng từ nhập hoặc không đủ tồn kho nhận để đảo nghiệp vụ thì toàn bộ giao dịch bị hủy. Các đơn có trả hàng hoặc điều chỉnh đã duyệt cần được đối soát chứng từ trước; hệ thống chặn hủy tự động trường hợp này. Không xóa chứng từ dòng tiền đã thực thu chỉ vì hủy doanh thu đơn.

Kiểm thử bằng bảng tạm MySQL: `php tests/Manual/CancelCompletedTransfer.php` và `php tests/Manual/CancelCompletedTransfer.php --short` (hoàn tồn/xóa đối soát-doanh thu và hoàn tác khi thiếu tồn). Bảng nghiệp vụ thật không bị thay đổi.

## Điều chỉnh phạm vi dọn dữ liệu cũ

Theo yêu cầu giữ nguyên số liệu hiện tại, thao tác “Hủy toàn bộ phiếu & đơn” đã được gỡ khỏi giao diện và chặn trên máy chủ. Chưa chạy xóa hoặc hoàn tồn trên dữ liệu thật. Người dùng chọn xóa hẳn lịch sử thay vì lưu trữ; cần chốt cụ thể các bảng lịch sử được xóa và dữ liệu tài chính/tồn kho phải giữ trước khi triển khai thao tác mới.

## Phạm vi chốt cuối: xóa hẳn phiếu cũ và toàn bộ đơn

Thao tác admin trên phiếu đã tiếp nhận nay là “Xóa phiếu cũ & toàn bộ đơn”. Xóa hẳn đơn cùng các liên kết giao nhận, lịch sử đơn, điều chỉnh, đối soát, doanh thu và hoa hồng; cập nhật tổng lô nhập doanh thu liên quan. Không dùng luồng hủy/hoàn tồn trước đó. Không hoàn thêm hoặc trừ thêm số lượng tồn thực tế; reservation cũ bị xóa và tồn giữ chỗ được tính lại, nên tồn khả dụng có thể tăng nếu đơn cũ còn giữ chỗ.

Giữ nhật ký admin, bản chụp đơn đã xóa và sổ biến động tồn kho để bảo toàn số dư, chứng từ dòng tiền thực thu vẫn theo nghiệp vụ tài chính. Chặn đơn tạo hoặc giao hôm nay và đơn có doanh thu ghi hôm nay. Lỗi liên kết nghiệp vụ sẽ hoàn tác toàn bộ giao dịch. Chức năng không tự chạy xóa dữ liệu; admin chọn phiếu, nhập lý do và xác nhận rõ phạm vi trước khi thực hiện.

Kiểm thử `php tests/Manual/DeleteHistoricalTransfer.php` dùng bảng tạm MySQL để xác minh xóa đơn/phiếu, giao nhận, đối soát/doanh thu, giải phóng giữ chỗ và giữ nguyên tồn thực tế.

## Dọn cả đối soát khi xóa dữ liệu cũ

Dọn phiếu cũ xóa thêm các dòng đối soát phí giao hàng và khóa đơn (`shipping_expense_items`, `shipping_expense_order_locks`) trước khi xóa đơn. Phiếu phí còn đơn khác được tính lại tổng; phiếu phí rỗng bị xóa và tiến trình xử lý tương ứng được kết thúc ở trạng thái hủy. Không thay đổi chứng từ thanh toán thực thu/chi đã ghi sổ.

Bản chụp trong `admin_deleted_orders` được đánh dấu `historical_cleanup`; trang đối soát không cộng các bản ghi này trở lại vào “đơn không còn tồn tại”, số đơn chờ đối soát hoặc doanh thu. Nhận diện cả dấu `preserve_current_inventory` của các lần dọn đã chạy trước đây. Nhật ký admin vẫn được giữ để truy vết, không tính doanh thu. Đây là dọn dữ liệu được chọn, chưa tự thiết lập mốc đầu kỳ; sau khi dọn cần kiểm kê/chốt số dư tại mốc nghiệp vụ đã thống nhất.

## Dọn phiếu cũ chưa tiếp nhận

Theo phạm vi dọn dữ liệu cũ đã xác nhận, admin được xóa toàn bộ phiếu và đơn kể cả đang chờ shipper, đang vận chuyển, chờ kho nhận, đã nhận hoặc thiếu bản ghi vận chuyển. Không còn điều kiện bắt buộc tiếp nhận hoàn tất. Nút “Xóa phiếu cũ & toàn bộ đơn” hiển thị cho admin ở mọi trạng thái; vẫn cần lý do và xác nhận, kiểm tra ngày dữ liệu cũ và giữ nguyên tồn kho thực tế. Quyền kho thông thường không được mở rộng.

## Sửa lỗi 1406 khi dọn phiếu cũ

Lý do xóa được giữ nguyên trong cột `admin_deleted_orders.reason` (tối đa 500 ký tự). Mã phiếu lưu riêng ở `snapshot.cleanup_group_id`, tránh ghép tiền tố làm vượt chiều dài cột. Kiểm thử bổ sung: `php tests/Manual/DeleteHistoricalTransfer.php --long-reason` với lý do 500 ký tự tiếng Việt; xác minh không cắt mất lý do và tồn thực tế không thay đổi.

## Sửa lỗi 1406 tại notifications.data

Khi nhật ký xóa phiếu có bản chụp nhiều đơn, thông báo admin trước đây sao chép toàn bộ metadata vào cột TEXT `notifications.data`, gây tràn 65.535 byte. Thông báo lớn nay chỉ lưu tóm tắt và mã `admin_event_id`; bản chụp đầy đủ vẫn ở `admin_events.metadata`. Thông báo nhỏ giữ nguyên dữ liệu tương thích.

Đã kiểm tra ghi thông báo qua database channel thật vào bảng tạm bằng `php tests/Manual/DeleteHistoricalTransfer.php --real-notifications --long-reason`, với bản chụp lớn hơn 65.535 byte. Toàn bộ thao tác dọn hoàn tất, thông báo nằm trong giới hạn, bản chụp đầy đủ được giữ và tồn thực tế không thay đổi.

## Loại đơn hủy khỏi Tiếp nhận

Đơn đã hủy có thể còn bản ghi điều chuyển chờ shipper của dữ liệu cũ. Trang Tiếp nhận, số đếm dashboard, tiếp nhận Package và API mobile dùng điều kiện chung: đơn còn tồn tại, chưa vào thùng rác, không ở trạng thái cancelled/canceled/rejected. Máy chủ chặn kéo/nhập kho cho các đơn không còn hợp lệ, kể cả gửi yêu cầu trực tiếp. Giữ nguyên dữ liệu và tồn kho; việc xóa hẳn vẫn thực hiện bằng chức năng dọn phiếu cũ đã chọn.

## Hàng đi kèm đơn khi kho nhận tiếp nhận (16/10/2026)

Kho nhận nhập số lượng hàng thực chuyển đi kèm đơn, không yêu cầu tồn có sẵn tại kho nhận để cấp lại cho đơn. Sau khi nhập, reservation được gắn `received_transfer_id`, giữ đúng số lượng và kg tiếp nhận của từng dòng. Dòng giữ hàng trùng của chính đơn được gộp về một dòng đúng lượng; không yêu cầu hàng nhập mới bù thiếu các reservation cũ của đơn khác.

Shipper nhận giao khách xuất từ lượng giữ riêng có nguồn gốc tiếp nhận; cập nhật số tồn và giảm giữ chỗ cùng nhau, không làm thay đổi phần giữ riêng của đơn khác. Hủy đơn khi hàng còn trong kho giải phóng giữ chỗ, không tăng tồn thực tế. Điều chỉnh tồn thủ công không được giảm hàng đã đóng hoặc bỏ khóa bằng cách sửa riêng bộ đếm reserved_quantity.

Migration mới: `2026_10_16_090000_link_received_stock_to_transfer_reservations`. Đã chạy ở workspace; khi triển khai sang máy chủ khác phải chạy migration trước khi dùng mã mới. Phiếu #681 không có trong database workspace nên chưa sửa dữ liệu riêng của phiếu trong ảnh.

### Quản trị thông báo đăng nhập trên web và my_app

- Admin mở **Thông báo đăng nhập** trong menu quản trị hoặc **Cài đặt → Thông báo đăng nhập — Web & my_app** (`/admin/settings/login-notice`).
- Có thể sửa tiêu đề, nội dung (bao gồm thời gian demo), footer và bật/tắt thông báo. Một cấu hình áp dụng chung cho web và my_app, không cần tạo lại APK khi đổi nội dung.
- Mặc định thông báo nêu thời gian 15/09/2026–30/11/2026 và tiếp tục kiểm thử đến khi có quyết định chính thức.
- Web hiển thị sau mỗi lần đăng nhập; my_app từ bản 1.1.33 hiển thị sau đăng nhập và khi khôi phục phiên đăng nhập lúc mở app. Nhấn **Đã hiểu** để tiếp tục.
- Cấu hình mới áp dụng ở lần đăng nhập/mở phiên tiếp theo; không đóng popup đang mở trên thiết bị khác.
