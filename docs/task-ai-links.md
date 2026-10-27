# Link AI cập nhật công việc

Trong **Công việc được giao cho tôi**, chọn **Link AI** (hoặc mở chi tiết công việc → **Link AI cập nhật phần việc của tôi**).
Chọn hạn dùng 1, 7 hoặc 30 ngày, bấm **Tạo / thay link AI**, sao chép hướng dẫn và link để đưa cho AI.

- Chỉ người được giao việc mới tạo/thu hồi link cho phần việc của chính mình. Admin không tạo thay người khác.
- Khóa ngẫu nhiên 256 bit, database chỉ lưu SHA-256. Link gốc chỉ hiển thị khi cấp; tạo lại vô hiệu hóa link cũ ngay.
- Link chỉ đọc mã, tên, mô tả, hạn và trạng thái của đúng công việc; không cung cấp thông tin người khác, tệp đính kèm, danh sách công việc, dữ liệu khách hàng/công nợ.
- API không đăng nhập, không cấp cookie phiên, không dùng mobile API token và không có endpoint liệt kê công việc. Khóa này không cấp quyền cho các API khác.
- Người cầm link được cập nhật phần việc đã cấp. Giữ kín link và thu hồi khi không dùng nữa. Link hết hiệu lực khi hết hạn, thu hồi, thay khóa, xóa/phân công lại người nhận hoặc đóng/hủy công việc.
- Mọi cập nhật được lưu lịch sử với nhãn `[AI link #...]` theo user cấp link. Không sửa mô tả, người được giao, deadline, quyền truy cập hoặc bước duyệt.

## Gọi từ AI có công cụ HTTP

GET link `/api/task-links/{token}` để lấy nội dung công việc và hướng dẫn có cấu trúc JSON. GET không thay đổi công việc.
Gửi POST tới cùng link. Mọi payload có trường ngoài danh sách cho phép sẽ bị từ chối.

```sh
# Thay URL bằng link riêng được cấp trong giao diện; không dùng tài khoản/mật khẩu.
curl --request POST "$TASK_AI_URL" --header 'Content-Type: application/json' \
  --data '{"action":"accept"}'
curl --request POST "$TASK_AI_URL" --header 'Content-Type: application/json' \
  --data '{"action":"start","note":"Bắt đầu thực hiện"}'
curl --request POST "$TASK_AI_URL" --header 'Content-Type: application/json' \
  --data '{"action":"progress","note":"Tiến độ thực tế của công việc"}'
```

Hoàn thành cần báo cáo 10–5000 ký tự, ghi chú 5–2000 ký tự và 1–10 ảnh minh chứng thật, mỗi ảnh tối đa 5MB, như luồng hiện có:

```sh
curl --request POST "$TASK_AI_URL" \
  --form 'action=complete' \
  --form 'completion_content=Nội dung kết quả thực tế đã thực hiện' \
  --form 'completion_notes=Ghi chú và thông tin minh chứng' \
  --form 'images[]=@/path/to/real-proof.png'
```

Phải tiếp nhận trước; chỉ bắt đầu khi không còn bước duyệt giao đang chờ. Không thể gửi hoàn thành trước khi bắt đầu.
Chỉ phần việc của user được cấp link chuyển sang hoàn thành; công việc chung vẫn chờ các thành viên khác.
Khi mọi người hoàn thành, công việc chuyển sang **Chờ xác nhận hoàn thành**, không tự chuyển thành **Đã hoàn thành**.
AI không có công cụ POST/upload tệp sẽ chỉ đọc được hướng dẫn; người dùng cần cập nhật hoặc gửi minh chứng trên giao diện. Link không tự cấp thêm công cụ cho AI.

Giới hạn 30 yêu cầu/phút/IP, tạo link 10 lần/phút. Phản hồi thành công có `Cache-Control: no-store` và `X-Robots-Tag: noindex`.
Triển khai cần hai migration `2026_10_27_120000_create_task_ai_links_table.php` và `2026_10_27_121000_bind_task_ai_links_to_owner.php`.
