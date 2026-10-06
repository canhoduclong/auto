# Đề xuất module Điều hành & Giao việc — Hoàng Long TNT

Ngày phân tích: 06/10/2026. Đây là phương án để quyết định triển khai, chưa phải chức năng đã xây dựng.

## 1. Quyết định kiến trúc đề xuất

Mở rộng module task_assignments hiện có, dùng chung backend Laravel và một API nghiệp vụ cho web/Flutter. Không tạo thêm một bảng tasks hay một hệ thống giao việc độc lập.

Có ba lớp nghiệp vụ:

1. Quản trị HĐQT: đề xuất, thảo luận, biểu quyết nếu áp dụng, nghị quyết/quyết định.
2. Điều hành: nhiệm vụ HĐQT giao BGĐ và yêu cầu phối hợp giữa các thành viên BGĐ.
3. Thực hiện: công việc BGĐ/quản lý giao nhân viên, báo cáo, vướng mắc, nghiệm thu.

Lớp 2–3 dùng cùng lõi task_assignments, phân biệt bằng loại nghiệp vụ. Lớp 1 có dữ liệu riêng vì quy trình quyết định khác quy trình thực hiện. Quyết định đã ban hành sinh nhiệm vụ điều hành có liên kết nguồn.

## 2. Kết quả đối chiếu mã nguồn hiện có

| Thành phần | Có thể tận dụng | Cần bổ sung/điều chỉnh |
|---|---|---|
| TaskAssignment, TaskAssignee | Nhiệm vụ, nhiều người được giao, cha/con, ưu tiên, deadline | Một người chủ trì, vai trò phối hợp/theo dõi, thời điểm tiếp nhận, trạng thái bổ sung |
| TaskDelegateConfig, TaskPermission, TaskMenuService | Cấu hình quyền giao việc và menu | Phạm vi phòng ban/cây nhiệm vụ; phân biệt quyền giao, phối hợp, nghiệm thu |
| TaskStatusLog | Lịch sử trạng thái, thu hồi, xóa | Lịch sử trường dữ liệu trước/sau, tiến độ theo báo cáo, thay người/deadline |
| ApprovalService, ApprovalWorkflow, ApprovalStep, ApprovalOrder | Cấu hình bước và người/role phê duyệt | Tách duyệt ban hành, duyệt kết quả; tránh duyệt đồng nghĩa với bắt đầu thực hiện |
| Controller và form hoàn thành/nghiệm thu | Người thực hiện gửi kết quả; người có quyền xác nhận | Người nghiệm thu được chỉ định, chu kỳ báo cáo, báo cáo nhiều lần có phiên bản |
| TaskCompletionImage và tệp đính kèm | Lưu file/hình ảnh | Liên kết tệp với từng báo cáo/vướng mắc, kiểm soát tải file theo quyền |
| TinyMCE | Soạn và hiển thị nội dung có định dạng trên web | App hiển thị HTML đã lọc; quyết định trình soạn thảo mobile |
| Users, roles, departments, blocks | Tài khoản và cơ cấu đơn vị | Chức danh HĐQT/BGĐ, nhiệm kỳ/phạm vi phụ trách, vai trò chủ trì |
| Flutter RoleLayout, workspace/menu, API service | Khung app nhiều layout, chuyển vai trò | Màn hình/API công việc chuyên biệt; workspace HĐQT, điều hành, công việc cá nhân |
| NotificationService và trung tâm thông báo | Hộp thư trong app | Sự kiện task, deep link, nhắc nhận việc/báo cáo/quá hạn; push cần kiểm tra và bổ sung riêng |

Điểm phải sửa trước: initTaskApproval hiện đưa task sang in_progress ngay khi tạo, kể cả không có workflow. Việc nhận được giao phải là hành động riêng có timestamp. Quyền xem/nghiệm thu hiện có các nhánh cho manager/CEO rộng; cần giới hạn theo nhiệm vụ và phạm vi phụ trách. API /warehouse/tasks là tác vụ kho, không phải API giao việc điều hành.

## 3. Layout và menu trên app

Dùng một bộ màn hình nhiệm vụ chung, thay dashboard/menu và hành động theo quyền. Layout không quyết định quyền truy cập; backend phải kiểm tra quyền trên từng bản ghi.

### HĐQT

Thanh dưới: Tổng quan — Quyết định — Nhiệm vụ — Thông báo — Cá nhân.

- Tổng quan: chờ quyết định, chỉ đạo chưa được BGĐ tiếp nhận, nhiệm vụ điều hành quá hạn/vướng mắc, kết quả chờ nghiệm thu.
- Quyết định: đề xuất của tôi, cần góp ý/biểu quyết, quyết định đã ban hành, hồ sơ và phiên bản.
- Nhiệm vụ: chỉ đạo đã giao, người BGĐ chủ trì, deadline, tình hình tổng hợp; mở cây việc để kiểm tra khi cần.
- Chi tiết: căn cứ/nguồn quyết định, kết quả yêu cầu, báo cáo BGĐ, lịch sử; nút ban hành, giao BGĐ, yêu cầu giải trình, nghiệm thu/trả lại tùy quyền.
- Thành viên HĐQT không mặc định có quyền ra chỉ đạo đơn phương hoặc xem mọi hồ sơ mật. Thẩm quyền ban hành và nhóm hồ sơ được cấu hình.

### Ban Giám đốc

Thanh dưới: Tổng quan — Được giao — Tôi giao — Phối hợp — Cá nhân. Thông báo đặt ở biểu tượng chuông.

- Tổng quan: việc từ HĐQT, chưa nhận, đội ngũ quá hạn/vướng mắc, báo cáo thiếu, chờ nghiệm thu.
- Được giao: tiếp nhận chỉ đạo, lập kế hoạch, giao việc con, báo cáo lại HĐQT.
- Tôi giao: danh sách theo người/phòng ban, việc con, tiến độ và kết quả chờ kiểm tra.
- Phối hợp: tôi gửi, tôi nhận; tiếp nhận, đề nghị điều chỉnh phạm vi/hạn, từ chối có lý do, trả kết quả.
- Báo cáo điều hành: hệ thống gom số liệu và báo cáo con thành bản nháp; BGĐ kiểm tra, bổ sung đánh giá và chủ động gửi cấp trên.

### Quản lý phòng ban

Thanh dưới: Tổng quan — Việc của tôi — Đội ngũ — Báo cáo — Cá nhân.

- Nhận công việc được giao; chia việc con cho người thuộc phạm vi cho phép.
- Theo dõi việc đội ngũ, hỗ trợ vướng mắc, nghiệm thu việc mình giao hoặc được ủy quyền nghiệm thu.
- Không có quyền duyệt/xem mọi nhiệm vụ chỉ vì role manager.

### Nhân viên

Thanh dưới: Hôm nay — Công việc — Báo cáo — Thông báo — Cá nhân.

- Hôm nay: việc chưa tiếp nhận, cần làm hôm nay, sắp đến hạn, quá hạn, báo cáo đến kỳ.
- Công việc: lọc theo trạng thái, deadline, ưu tiên, người giao.
- Nút theo giai đoạn: Tiếp nhận → Bắt đầu → Cập nhật tiến độ/Báo vướng mắc → Gửi hoàn thành.
- Sau khi gửi hoàn thành: xem phản hồi, sửa và gửi lại; không tự đóng nhiệm vụ.
- Giao việc con chỉ hiện khi có quyền ủy quyền.

Admin có màn hình cấu hình cơ cấu, quyền, luồng duyệt, nhắc việc và nhật ký; admin kỹ thuật không mặc định là người nghiệm thu kinh doanh.

Người kiêm nhiệm chuyển workspace theo nhiệm vụ. Nhân viên kho/sale/kế toán vẫn dùng layout chuyên môn và có mục Công việc của tôi; không bắt buộc đổi layout để nhận việc.

## 4. Mẫu giao việc

Thông tin bắt buộc: loại nghiệp vụ, tiêu đề, nội dung, kết quả đầu ra/tiêu chí nghiệm thu, một người chủ trì, người nghiệm thu, deadline. Hạn tiếp nhận tính theo cấu hình nhưng người giao có thể điều chỉnh trong phạm vi cho phép.

Thông tin tùy chọn: người phối hợp, người theo dõi, checklist, mốc công việc, chu kỳ báo cáo, tệp, nguồn phát sinh, nhiệm vụ liên quan.

Chọn phòng ban là chọn phạm vi tìm người, không thay thế trách nhiệm cá nhân. Nếu giao cùng một nội dung cho nhiều người độc lập, hệ thống tạo một nhóm và các nhiệm vụ con mỗi người một chủ trì. Nếu cùng thực hiện một kết quả thì giữ một nhiệm vụ, một chủ trì, các thành viên phối hợp.

Người nghiệm thu mặc định là người giao; có thể chỉ định người khác có quyền. Không cho người chủ trì tự nghiệm thu kết quả mình thực hiện.

## 5. Các luồng nghiệp vụ

### HĐQT → BGĐ

Đề xuất/thảo luận → ban hành theo cơ chế được cấu hình → tạo nhiệm vụ điều hành → BGĐ tiếp nhận → lập kế hoạch/giao con → nghiệm thu kết quả con → BGĐ tổng hợp và gửi báo cáo → người có thẩm quyền HĐQT nghiệm thu hoặc trả lại.

Việc nhân viên hoàn thành không tự động hoàn thành nhiệm vụ BGĐ; BGĐ phải chịu trách nhiệm tổng hợp và gửi kết quả.

### BGĐ/quản lý → nhân viên

Nháp → duyệt giao nếu loại nghiệp vụ yêu cầu → gửi giao → chờ tiếp nhận → nhận → bắt đầu → báo cáo tiến độ/vướng mắc → gửi hoàn thành → nghiệm thu hoặc trả lại → hoàn thành.

Tiếp nhận xác nhận trách nhiệm, chưa đồng nghĩa với bắt đầu. Tiếp nhận không tạo quyền tự sửa hạn/kết quả yêu cầu.

### BGĐ ↔ BGĐ

Yêu cầu phối hợp → bên nhận tiếp nhận, đề nghị sửa hoặc từ chối có lý do → phân công nội bộ → bên nhận kiểm tra kết quả con → gửi kết quả phối hợp → bên yêu cầu xác nhận đáp ứng hoặc trao đổi bổ sung.

Quan hệ này không sinh quyền sửa task/đánh giá nhân viên của BGĐ bên kia. Chỉ xem các phần được chia sẻ và tiến độ tổng hợp. Nếu không thống nhất phạm vi, chuyển người điều phối đã cấu hình xử lý; không biến yêu cầu ngang cấp thành mệnh lệnh.

### HĐQT ↔ HĐQT

Đề xuất → góp ý/tài liệu → phiên bản nội dung cuối → biểu quyết/thống nhất theo cấu hình → ban hành → liên kết các nhiệm vụ thực hiện.

Biểu quyết phải gắn với phiên bản cụ thể. Đổi nội dung trọng yếu sau khi biểu quyết phải mở phiên bản và vòng quyết định mới. Không dùng nút duyệt task thay cho biểu quyết. Quy tắc số phiếu, nhóm tham gia, người ký và lưu hồ sơ phải được công ty chốt riêng.

## 6. Trạng thái và điều kiện chuyển

| Trạng thái | Người/thao tác tạo | Điều kiện |
|---|---|---|
| Nháp | Người giao | Chưa gửi đến người nhận |
| Chờ duyệt giao | Người giao gửi duyệt | Chỉ với loại yêu cầu duyệt |
| Chưa tiếp nhận | Hệ thống sau khi gửi giao | Đã có chủ trì, hạn nhận, deadline |
| Đã tiếp nhận | Chủ trì bấm nhận | Ghi accepted_at, accepted_by |
| Đang thực hiện | Chủ trì bấm bắt đầu | Đã tiếp nhận |
| Chờ xử lý vướng mắc | Chủ trì báo vướng mắc chặn việc | Có nội dung, người xử lý; lưu trạng thái trước để quay lại |
| Chờ xác nhận | Chủ trì gửi báo cáo hoàn thành | Có kết quả/bằng chứng theo tiêu chí; xử lý các việc con bắt buộc |
| Hoàn thành | Người nghiệm thu | Xác nhận kết quả, ghi thời điểm và người xác nhận |
| Trả lại | Người nghiệm thu | Bắt buộc lý do; lưu phiên bản báo cáo bị trả |
| Tạm dừng | Người có quyền điều phối | Lý do và điều kiện tiếp tục |
| Thu hồi/Hủy | Người giao/người có quyền | Lý do, cập nhật người nhận và việc con chưa hoàn thành |

Mới giao là sự kiện gửi giao; trạng thái sau gửi là Chưa tiếp nhận, tránh hai trạng thái không có hành động khác nhau. Quá hạn là cờ độc lập; tạm dừng không tự dời deadline. Đổi deadline cần lý do và lịch sử. Vướng mắc chưa được xác định là chặn việc có thể được ghi nhận mà không đổi trạng thái thực hiện.

Đề xuất người nhận gửi yêu cầu đổi deadline; người giao quyết định. Tương tự, đổi chủ trì phải lưu lịch sử và chủ trì mới phải tiếp nhận lại. Người cũ không còn quyền cập nhật phần thực hiện.

Các hành động phải chạy qua TaskLifecycleService và transaction, kiểm tra trạng thái hiện tại/quyền để chống bấm hai lần và duyệt đồng thời. Web và app dùng chung service này.

## 7. Báo cáo, vướng mắc và nghiệm thu

- Báo cáo tiến độ: % thực hiện, việc đã làm, kế hoạch tiếp theo, ngày báo cáo, tệp; giữ từng báo cáo, không ghi đè một ô note.
- Vướng mắc: nội dung, nguyên nhân, đề xuất, ảnh hưởng thời hạn, mức độ, người xử lý, trạng thái xử lý, thời điểm báo/phản hồi/đóng.
- Báo cáo hoàn thành: kết quả, đối chiếu tiêu chí, vấn đề còn lại, đề xuất, bằng chứng, phiên bản.
- Nghiệm thu: chấp nhận/trả lại, lý do, người xác nhận, thời điểm. Nút Hoàn thành trên app đổi tên Gửi báo cáo hoàn thành.
- % thực hiện tự báo cáo tách khỏi điểm đánh giá của người giao. Điểm đánh giá hiện có không dùng thay cho tiến độ.
- Việc con bắt buộc chưa nghiệm thu thì chặn gửi hoàn thành việc cha, trừ quyền cho phép kết thúc ngoại lệ có lý do. Việc con bị hủy không tự được coi là đạt đầu ra.
- Tỷ lệ việc con dùng trọng số nếu có, mặc định bằng nhau. Hiển thị số đã nghiệm thu, số chờ, số hủy; không mặc định tiến độ tự khai 100% là đã hoàn thành.

## 8. Mô hình dữ liệu mở rộng

Giữ task_assignments, task_assignees, task_status_logs, task_completion_images, task_delegate_configs, task_permissions và approvals hiện tại. Không dùng đồng thời bảng Task và TaskAssignment làm hai nguồn cho cùng nghiệp vụ.

Bổ sung task_assignments: kind (execution/executive/coordination), business_scope, accountable_user_id, verifier_id, assigned_at, acceptance_due_at, accepted_at, started_at, reporting_policy, expected_result, completion_criteria, progress_percent, paused_at, source_type/source_id, version.

Task_assignees bổ sung participation_role (owner/collaborator), accepted_at, started_at. Task chủ trì là nguồn xác định người chịu trách nhiệm; đảm bảo không sinh hai owner.

Các bảng mới theo từng giai đoạn:

- task_watchers: theo dõi; quyền xem không mặc định là quyền sửa.
- task_progress_reports, task_issue_reports, task_completion_reports: báo cáo/vướng mắc/phiên bản hoàn thành.
- task_attachments: liên kết task/báo cáo/vướng mắc, quyền tải và metadata; tái sử dụng tệp cũ qua tham chiếu/chuyển đổi có kiểm soát.
- task_change_logs: actor, hành động, giá trị trước/sau, lý do, timestamp; TaskStatusLog tiếp tục cho chuyển trạng thái hoặc chuẩn hóa về cùng log.
- task_checklists, task_links/task_dependencies: checklist, liên quan, phụ thuộc; kiểm tra vòng lặp.
- organization_memberships hoặc cấu hình chức danh tương đương: người, đơn vị, chức danh HĐQT/BGĐ/quản lý, phạm vi, hiệu lực.
- board_proposals, board_comments, board_resolutions, board_votes: quyết định quản trị; liên kết phiên bản và nhiệm vụ phát sinh.
- task_reminder_rules và log gửi nhắc: cấu hình SLA tiếp nhận, kỳ báo cáo, quá hạn, chuyển cấp; chống gửi trùng.

Không cần bảng executive_assignments riêng nếu kind=executive đã đủ. Coordination dùng kind=coordination cùng lõi task, metadata riêng chỉ khi phát sinh thông tin mà task không chứa được.

## 9. API và app

API đề xuất /api/tasks: dashboard, list/detail, create/edit, accept, start, progress-reports, issues, completion-reports, verify, return, pause/resume, recall, delete-after-recall, subtasks, change-deadline/assignee.

Response trả allowed_actions theo từng task và workspace; Flutter dùng để hiện nút, backend vẫn kiểm tra lại. Có phân trang, bộ lọc, idempotency/version cho thao tác quan trọng, upload nhiều tệp và thông báo lỗi rõ.

App native Flutter cho dashboard, danh sách, tiếp nhận, báo cáo, vướng mắc, nghiệm thu. Tận dụng ApiService, RoleLayout, auth/workspace, NotificationService.

TinyMCE đã có trên web. Giai đoạn đầu có thể mở riêng trang soạn nội dung tối ưu mobile bằng phiên đăng nhập một lần; không mở toàn bộ module dưới dạng web desktop. Bản xem native hiển thị HTML đã lọc, bảng cuộn ngang và link kiểm tra. Có thể thay trình soạn mobile bằng editor native sau, vẫn dùng chung định dạng lưu.

Thông báo trong app: giao mới, đến hạn tiếp nhận, báo cáo đến kỳ, vướng mắc mới, hoàn thành chờ nghiệm thu, trả lại, đổi người/deadline, thu hồi. Nhắc chạy scheduler/queue sau khi transaction commit, chống gửi trùng, không gửi nhắc task đã hoàn thành/hủy/xóa. Push ngoài app cần xác nhận hạ tầng thiết bị/token trước, không coi trung tâm thông báo hiện tại là đã có push.

Không cho nghiệm thu/ban hành khi offline. Có thể giữ bản nháp báo cáo trên thiết bị; khi online phải đối chiếu version và trạng thái để tránh gửi vào task đã thu hồi.

## 10. Chuyển đổi dữ liệu và quyền

Không reset trạng thái các task cũ. pending cũ không tự chứng minh đã gửi giao; processing/in_progress cũ không chứng minh đã tiếp nhận. Map theo thông tin lịch sử có sẵn; nếu không có thì đánh dấu dữ liệu cũ/chưa ghi nhận, không tạo timestamp giả.

Giữ completed=chờ nghiệm thu, done=đã nghiệm thu của mã hiện có bằng lớp map trong API; không đổi mã hàng loạt ngay.

Nhiệm vụ cũ nhiều người nhận cần người giao chỉ định chủ trì; không tự chọn người đầu tiên. Giữ cơ chế báo hoàn thành từng người trong giai đoạn chuyển đổi để không mất công việc đang chạy.

Chuẩn hóa role CEO/ceo/director/Director với bảng chức danh/quyền có đối chiếu tài khoản thực tế. Không tự coi CEO hay admin là thành viên HĐQT. API cũ và giao diện web phải cùng áp dụng service/quyền mới trước khi phát hành app để không có đường cập nhật bỏ qua bước nhận việc.

Xóa sau thu hồi giữ soft delete và lịch sử; bảo vệ nhiệm vụ liên quan quyết định, báo cáo và các việc con đã nghiệm thu. Hồ sơ HĐQT ban hành chỉ được lưu trữ/đính chính theo quyền, không dùng nút xóa việc thông thường.

## 11. Các đợt triển khai và nghiệm thu

Đợt 1 — Hoàn thiện giao việc cốt lõi: quyền theo phạm vi, một chủ trì, tiếp nhận/bắt đầu, báo cáo/vướng mắc/hoàn thành, nghiệm thu, lịch sử đổi thông tin, API và app nhân viên/quản lý. Chạy thử một phòng ban.

Đợt 2 — Điều hành BGĐ: dashboard BGĐ, cây nhiệm vụ nhiều tầng, phối hợp ngang, tổng hợp báo cáo, nhắc việc/chuyển cấp. Chạy thử một nhiệm vụ liên phòng ban có chủ trì và người nghiệm thu cụ thể.

Đợt 3 — Quản trị HĐQT: đề xuất/thảo luận, quyết định, thẩm quyền ban hành và biểu quyết nếu chọn, dashboard chỉ đạo/kết quả. Quyết định phải truy vết đến nhiệm vụ BGĐ và báo cáo nghiệm thu.

Đợt 4 — Tối ưu: công việc định kỳ, phụ thuộc, dashboard hiệu suất, push, bản nháp offline, hoàn thiện soạn thảo mobile. Không dùng dữ liệu hiệu suất để đánh giá tự động trước khi quy tắc và dữ liệu được kiểm chứng.

Nghiệm thu bắt buộc: gửi không tự nhận; người nhận không tự đóng; người ngoài không xem/sửa qua URL/API; ngang cấp không tạo quyền cấp trên; hoàn thành con không tự đóng cha; đổi deadline có trước/sau; thu hồi chặn cập nhật tiếp; xóa không mất audit; bấm lặp không phát sinh báo cáo/duyệt trùng; mobile và web có kết quả tương đồng; task cũ tiếp tục hoạt động.

## 12. Những lựa chọn cần chốt trước khi triển khai

1. Danh sách thành viên HĐQT, BGĐ, quản lý và phạm vi phụ trách thực tế; vai trò kiêm nhiệm.
2. Mặc định một chủ trì; giao hàng loạt tạo việc con độc lập hay các thành viên cùng một kết quả.
3. Người giao mặc định nghiệm thu; điều kiện ủy quyền và cấm tự nghiệm thu.
4. Hạn tiếp nhận và chu kỳ báo cáo theo loại việc/ưu tiên; SLA tính theo giờ làm việc hay toàn bộ thời gian.
5. Nghiệm thu cha: mặc định yêu cầu việc con bắt buộc đã đạt; quyền ngoại lệ và cách xử lý việc hủy.
6. HĐQT: ai được đề xuất, ai được ban hành, có biểu quyết trên app không, cơ chế phiên bản/quyết định.
7. Chọn đợt 1–2 trước rồi HĐQT, hoặc làm cả ba; khuyến nghị đợt 1–2 trước để kiểm chứng lõi.

Chưa ước lượng lịch/công sức cố định khi chưa chốt các lựa chọn này, hạ tầng push, trình soạn mobile và số liệu chuyển đổi. Mỗi đợt cần báo giá/kế hoạch dựa trên phạm vi đã chốt và có tiêu chí nghiệm thu riêng.
