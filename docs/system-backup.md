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
5. Drive cá nhân: `BACKUP_GOOGLE_CREDENTIALS` trỏ tới JSON riêng tư ngoài public, dạng `{ "type": "authorized_user", "client_id": "...", "client_secret": "...", "refresh_token": "..." }`. Refresh token OAuth phải cấp scope Drive và Sheets. Không commit khóa/token. Không có giao diện cấp OAuth trong module này.
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
