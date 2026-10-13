# Sao lưu hệ thống

## Phạm vi

Admin `/admin/backups`: Sao lưu ngay, bật/tắt lịch ngày 1 lúc 02:00 theo `APP_TIMEZONE`, lịch sử, tải gói mã hóa, thử lại và kiểm tra Google.

Gói chứa toàn bộ schema/dữ liệu MySQL (đơn, chi tiết, đóng hàng, lịch sử, kho/giữ hàng, khách hàng, sản phẩm/giá, user/quyền, cấu hình, lộ trình, chi phí/xác nhận/thanh toán, phiếu và công việc/tiến độ), composer.lock và tài liệu tại các thư mục `system_backup.directories`. Không xuất tài khoản/mật khẩu thành các ô công khai. Sheets `Backup_Index` lưu danh mục; dữ liệu đầy đủ nằm trong gói trên Drive. Không sao lưu .env, khóa Google, khóa mã hóa, mã nguồn, APK hoặc dữ liệu Redis; chuẩn bị mã nguồn đúng phiên bản và cấu hình server dự phòng riêng. Giữ bản gốc APP_KEY ở nơi an toàn nếu dữ liệu ứng dụng có trường được Laravel mã hóa; khóa sao lưu không thay thế APP_KEY.

MySQL dump dùng `--single-transaction --quick --hex-blob`: dữ liệu InnoDB nhất quán tại snapshot của mysqldump. Từ chối bảng không dùng InnoDB. Không chạy migrations/DDL trong lúc dump. Tệp được kiểm tra SHA256 trước/sau đóng gói; nếu bị sửa/xóa khiến mã không khớp, backup thất bại. Tài liệu và DB không có snapshot nguyên tử chung: tránh sửa/xóa tài liệu trong lúc sao lưu. Không sao lưu routine/event MySQL ngoài ứng dụng; app dùng Laravel Scheduler.

## Thiết lập server

1. `php artisan migrate --force`
2. `php artisan system-backup:key` (không ghi đè khóa cũ). Tải khóa từ admin, lưu riêng an toàn. Tệp mặc định `storage/app/backup-encryption.key`, có thể dùng `BACKUP_ENCRYPTION_KEY` (base64 32 byte) hoặc `BACKUP_KEY_FILE`. Server dự phòng cần cùng khóa. Mất khóa không giải mã được.
3. Cài mysqldump, PHP zip và sodium. Tài khoản DB cần SELECT, SHOW VIEW, TRIGGER. DB MySQL/MariaDB, mọi bảng InnoDB. Có thể đổi `BACKUP_DUMP_BINARY` nếu cần binary tương thích server.
4. Google Cloud bật Drive API và Sheets API. Chia sẻ cả thư mục và spreadsheet cho tài khoản kết nối. Service account phải dùng Shared Drive; chỉ chia sẻ thư mục My Drive cá nhân không cấp dung lượng sở hữu tệp. Có thể dùng domain-wide delegation `BACKUP_GOOGLE_SUBJECT` khi tổ chức cho phép.
5. Drive cá nhân: `BACKUP_GOOGLE_CREDENTIALS` trỏ tới JSON riêng tư ngoài public, dạng `{ "type": "authorized_user", "client_id": "...", "client_secret": "...", "refresh_token": "..." }`. Refresh token OAuth phải cấp scope Drive và Sheets. Không commit khóa/token. Cách này là cấu hình tệp thủ công trước đây; cấu hình OAuth Chi bằng giao diện mới được hướng dẫn phía dưới và dùng BACKUP_GOOGLE_MODE=oauth.
6. `BACKUP_GOOGLE_DRIVE_FOLDER_ID` và `BACKUP_GOOGLE_SPREADSHEET_ID` cấu hình độc lập bảng đồng bộ đơn hàng. Mặc định dùng hai ID người dùng cung cấp. Nút Kiểm tra kết nối tạo tab `Backup_Index` và ghi tiêu đề nếu cần.
7. Cron chạy Laravel mỗi phút bằng user ứng dụng, ví dụ:

   ```cron
   * * * * * cd /var/www/app.com && php artisan schedule:run >> /dev/null 2>&1
   ```

   Không cần queue worker cho module này. CLI xử lý hàng đợi qua Scheduler, dùng flock ngăn chạy đồng thời trên cùng server. Thư mục private `storage/app/system-backups` phải có đủ dung lượng cho SQL + ZIP + gói mã hóa + ZIP kiểm tra; quyền user CLI/web cùng nhóm ứng dụng. Không đặt thư mục backup trong public/symlink public. Không dùng nhiều server xử lý cùng DB với thư mục backup khác nhau.

## Vận hành

`php artisan system-backup:run --enqueue` tạo và chạy thủ công. `php artisan system-backup:run` xử lý yêu cầu đang chờ. Lịch tháng tắt mặc định; bật trong admin. Mỗi tháng tối đa một bản tự động, bản thất bại được thử lại bằng nút. Nếu server bỏ lỡ cron ngày 1, chạy `php artisan system-backup:run --monthly` trong tháng đó để bù.

Gói ZIP mã hóa streaming bằng sodium secretstream XChaCha20-Poly1305; kiểm tra giải mã và SHA256 toàn bộ tệp trước tải lên. Drive upload resumable 1 MB/chunk, kiểm tra kích thước và MD5 phía Drive; Sheets ghi hoàn tất sau khi upload đạt. Local chỉ hoàn tất khi Sheets ghi thành công. Lỗi Google giữ gói local để thử lại, không dump lại dữ liệu và không upload lại khi đã lưu file ID. Nếu kết nối bị cắt ngay sau upload trước khi lưu ID, có thể còn tệp trùng chưa được lập danh mục trên Drive; không dùng tệp này để phục hồi.

Nếu tiến trình bị kill, lần chạy tiếp theo khi lấy được flock đánh dấu bản chạy dở thất bại để admin thử lại. File SQL/ZIP plaintext được xóa khi kết thúc bình thường; khi máy bị kill có thể còn tạm private cho đến lần thử lại. Không tự xóa bản cũ; theo dõi dung lượng và giữ tối thiểu 12 bản tháng. Không bảo vệ các thay đổi sau snapshot gần nhất; đây không phải đồng bộ thay đổi liên tục.

## Phục hồi

`/admin/backups/restore` hiện liệt kê/tải gói local; chưa có import tự động hoặc đọc danh mục remote trên server mới. Chuẩn bị cùng khóa, cùng mã nguồn/migrations, cấu hình đích riêng. `BackupArchive::decrypt` và `verify` có thể dùng trong CLI trên server dự phòng để giải mã và xác minh manifest. Chỉ nhập SQL vào DB riêng trống, tắt Scheduler/queue/email/Google sync, phục hồi tài liệu theo đường dẫn trong manifest rồi đối chiếu trước khi kích hoạt. Không nhập vào DB đang hoạt động.

## Kiểm tra

`php tests/Manual/SystemBackup.php`: dump DB thật, đóng gói tài liệu, mã hóa/giải mã, phát hiện sửa gói, chặn yêu cầu trùng, lỗi Sheets không hoàn tất, thử lại tái dùng gói/Drive ID. Google được giả lập; không kiểm chứng upload Google thực tế. Các dòng/tệp kiểm tra được xóa sau chạy.


## Cấu hình tài khoản backup bằng giao diện admin

Mặc định module dùng OAuth riêng, chấp nhận email đã được Google xác minh và khớp tài khoản nhập trong admin. Không tự dùng service account khi chưa kết nối OAuth. Đồng bộ đơn hàng/nhật ký/tồn kho vẫn dùng cấu hình Google Sheets cũ.

1. Trong Google Cloud, bật Drive API và Sheets API cho dự án tạo OAuth Client.
2. Google Auth Platform: cấu hình Branding / Audience cho ứng dụng. Nếu External đang Testing, thêm `chi.hoanglongtnt@gmail.com` vào Test users. Refresh token ở chế độ Testing có thể hết hạn sau 7 ngày; chọn trạng thái phát hành phù hợp và hoàn tất yêu cầu Google nếu có để chạy lịch tháng lâu dài.
3. Clients → Create client → Web application. Authorized redirect URIs phải khớp chính xác URL hiển thị tại admin. Trên production:

   ```text
   https://hoanglongtnt.com/admin/backups/oauth/callback
   ```

   Đặt `APP_URL=https://hoanglongtnt.com` và cấu hình HTTPS/proxy chính xác để URL trên trang không thành HTTP. Không dùng callback đăng nhập Google thông thường của hệ thống.
4. Vào Admin → Sao lưu → Cấu hình tài khoản Google và nguồn backup. Nhập **Folder ID**, **Spreadsheet ID**, **OAuth Client ID**, **OAuth Client Secret** rồi Lưu cấu hình vào .env. Client Secret để trống ở lần sửa sau sẽ giữ nguyên; đổi Client ID/Secret yêu cầu kết nối Google lại.
5. Bấm Kết nối tài khoản Google, đăng nhập đúng `chi.hoanglongtnt@gmail.com`, cấp quyền Drive và Sheets. Callback từ chối tài khoản khác email đã cấu hình; lưu refresh token riêng vào .env, không hiển thị token/secret trong form.
6. Folder hiện do Lương Trường sở hữu: chia sẻ folder và Sheet trực tiếp cho `chi.hoanglongtnt@gmail.com` quyền Người chỉnh sửa. File backup mới được tạo bằng tài khoản Chi; tài khoản này cần đủ dung lượng Drive.
7. Bấm Kiểm tra kết nối Google → Thử lại bản sao cũ / Sao lưu ngay → bật lịch tháng.

Các biến riêng cập nhật vào .env:

```dotenv
BACKUP_GOOGLE_MODE=oauth
BACKUP_GOOGLE_EMAIL=chi.hoanglongtnt@gmail.com
BACKUP_GOOGLE_DRIVE_FOLDER_ID=13tvG3zBm-bUlazWh7EHS_qGonbhEyta0
BACKUP_GOOGLE_SPREADSHEET_ID=1OaQAehzoEbMEqH7bY4z2gjFGakaqPkoNGJtK6PJaSOg
BACKUP_GOOGLE_OAUTH_CLIENT_ID=...apps.googleusercontent.com
BACKUP_GOOGLE_OAUTH_CLIENT_SECRET=...
BACKUP_GOOGLE_OAUTH_REFRESH_TOKEN=... # ứng dụng lưu sau khi cấp quyền
BACKUP_GOOGLE_OAUTH_CONNECTED_EMAIL=chi.hoanglongtnt@gmail.com
```

Client ID/Secret phải do người quản lý Google Cloud cung cấp; module không tự tạo OAuth client hoặc tự cấp quyền Google. Không cần nhập file JSON service account cho cách này. Nếu muốn dùng cách Shared Drive trước đây, đặt `BACKUP_GOOGLE_MODE=service_account` cùng credentials tương ứng; chức năng cấu hình giao diện hiện dùng OAuth cho email đã chọn.

Có thể thay đổi ô Tài khoản Google trong admin. Sau khi lưu email mới, refresh token cũ được xóa và cần kết nối Google lại. Phiên cấp quyền đang mở trước khi đổi email cũng không còn hợp lệ. Các ví dụ email Chi phía trên có thể thay bằng tài khoản bạn lựa chọn.

### Lỗi redirect_uri_mismatch

Nhập OAuth Redirect URI trong admin bằng URL của server thực hiện đăng nhập, ví dụ `https://hoanglongtnt.com/admin/backups/oauth/callback`, rồi lưu. Giá trị được ghi vào `BACKUP_GOOGLE_OAUTH_REDIRECT_URI` trong .env và được dùng ở cả yêu cầu đăng nhập lẫn đổi mã lấy token. Thêm chính xác URL đó vào Authorized redirect URIs của OAuth Client trong Google Cloud. Khi dùng local `http://app.com`, Google không chấp nhận callback HTTP của domain này; dùng HTTPS hợp lệ hoặc môi trường localhost được Google hỗ trợ. Không đặt callback production khi đăng nhập từ local vì session xác nhận không được chia sẻ giữa hai server.
