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
