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
