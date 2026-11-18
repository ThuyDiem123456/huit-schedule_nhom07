# HUIT Schedule Hub

> **Web App quản lý và phân lịch giảng dạy đa cơ sở thông minh** · Bài thực hành học phần Lập trình mã nguồn mở.

Ứng dụng PHP thuần + MySQL, phát triển để chạy trên **Laragon** bằng địa chỉ `http://huit-schedule.test`. Giao diện tiếng Việt, tương thích điện thoại/máy tính; giao diện tự thiết kế bằng CSS, **không yêu cầu Node/npm hoặc kết nối CDN**.

## 1. Chức năng

| Vai trò | Chức năng |
| --- | --- |
| Quản trị | Dashboard: số buổi, số tiết, giảng viên, cơ sở, biểu đồ theo ngày/cơ sở, lọc nhanh |
| Quản trị | CRUD **cơ sở**, **giảng viên + tài khoản**, **lịch dạy** |
| Quản trị | Lọc lịch theo từ khóa, cơ sở, giảng viên, thứ |
| Quản trị | Gợi ý giảng viên đúng chuyên môn, còn tiết trống, sắp xếp theo mức độ phù hợp |
| Quản trị | Xem ma trận 12 tiết trống/bận của giảng viên khi phân lịch |
| Giảng viên | Đăng nhập riêng, xem thời khóa biểu **theo tuần / tháng**, cơ sở, địa chỉ, phòng |
| Cả hai | Đăng nhập/đăng xuất, đổi mật khẩu cá nhân |

### Quy tắc phân lịch

- Mỗi lịch có **thứ trong tuần** `1..7` (`1 = Thứ Hai`, `7 = Chủ Nhật`) và **tiết** `1..12`.
- Khoảng tiết **bao gồm** cả tiết bắt đầu lẫn tiết kết thúc, ví dụ `1-3` chiếm ba tiết.
- Chặn **trùng tiết giảng viên**, **trùng phòng cùng cơ sở**, và **hai lịch liên tiếp tại hai cơ sở khác nhau** mà không có ít nhất một tiết trống để di chuyển.
- Hai buổi liên tiếp **trong cùng cơ sở** được cho phép; hai cơ sở khác nhau cần **ít nhất một tiết không dạy** giữa hai buổi.
- Đề xuất giảng viên theo chuyên môn nhập trong `teachers.expertise` (các chuyên môn phân cách bằng dấu phẩy), loại giảng viên bị xung đột rồi xếp hạng có ưu tiên cùng cơ sở và cân bằng số tiết.
- Đây là **heuristic** cho mục tiêu giảm di chuyển/khoảng trống; **không tính thời gian đi đường theo bản đồ thực tế**.
- Lịch mẫu lặp lại **mỗi tuần**, không có ngày bắt đầu/kết thúc học kỳ. Chế độ xem tháng hiển thị sự lặp lại này.

## 2. Công nghệ & thư viện mã nguồn mở

- PHP **8.1+**; extensions `pdo_mysql`, `mbstring`, `iconv`; MySQL/MariaDB sử dụng bảng InnoDB và UTF8MB4.
- Composer với `vlucas/phpdotenv` để nạp biến môi trường từ `.env`.
- Composer với `fakerphp/faker` để tạo thêm giảng viên và lịch mẫu bằng `database/seed.php`.
- Vanilla JavaScript (`fetch`) cho gợi ý giảng viên và ma trận tiết trống; CSS riêng.
- Git, **MIT License**, và GitHub Actions lint PHP + test logic khi `push` / `pull_request`.

## 3. Cài đặt trên Windows + Laragon

### Bước 1 · Đưa source vào Laragon

Giải nén ZIP, đặt thư mục `huit-schedule` vào thư mục `www` của Laragon, ví dụ:

```text
C:\laragon\www\huit-schedule\
```

Bật **Start All** trong Laragon. Bật *Auto Virtual Hosts* nếu chưa bật, và nhấn **Reload** hoặc **Restart All**. Tên miền tự động thông thường:

```text
http://huit-schedule.test
```

Trong trường hợp chưa cấu hình virtual host, có thể thử `http://localhost/huit-schedule/`.

### Bước 2 · Cài thư viện qua Composer

Mở Terminal của Laragon trong thư mục dự án:

```powershell
cd C:\laragon\www\huit-schedule
composer install
```

Composer sẽ tạo `vendor/` và `vendor/autoload.php`. **Không cần tải `vendor/` trong file ZIP** vì có thể cài lại bằng Composer.

### Bước 3 · Cấu hình biến môi trường

```powershell
Copy-Item .env.example .env
```

Mở `.env` để chỉnh nếu cấu hình MySQL khác mặc định:

```dotenv
APP_NAME="HUIT Schedule Hub"
APP_URL=http://huit-schedule.test
APP_DEBUG=false
APP_TIMEZONE=Asia/Ho_Chi_Minh
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=huit_db
DB_USER=root
DB_PASS=
```

**Chú ý:** Laragon thường dùng tài khoản MySQL `root`, mật khẩu trống, nhưng có thể khác tuỳ máy. Không commit `.env` lên GitHub.

### Bước 4 · Import cơ sở dữ liệu

Trong **HeidiSQL** (đi cùng Laragon), kết nối MySQL rồi mở và chạy toàn bộ nội dung:

```text
database/huit_db.sql
```

Tệp SQL này **tạo database `huit_db`**, đủ bốn bảng `branches`, `teachers`, `schedules`, `users` và dữ liệu minh hoạ: **3 cơ sở, 8 giảng viên, 12 lịch dạy**.

Cách dùng MySQL CLI (nếu đã có trong PATH):

```powershell
mysql -u root < database/huit_db.sql
```

Nếu MySQL có mật khẩu, dùng `mysql -u root -p < database/huit_db.sql`.

**Quan trọng:** `huit_db.sql` dùng cho **database mới/đang trống**; không import lại nhiều lần lên dữ liệu đã chỉnh vì các ID mẫu có thể bị trùng. `schema.sql` dành cho trường hợp chỉ muốn tự tạo bảng mà không thêm dữ liệu mẫu.

### Bước 5 · Tạo tài khoản và Faker demo

```powershell
php database/seed.php
```

Script này dùng `password_hash()` sinh tài khoản cho admin/giảng viên; sử dụng **Faker** sinh thêm **4 hồ sơ giảng viên**, tài khoản và lịch giảng dạy minh họa. Có thể chạy lại, script bỏ qua dữ liệu demo đã tồn tại.

| Vai trò | Tên đăng nhập | Mật khẩu ban đầu |
| --- | --- | --- |
| Admin | `admin` | `Admin@123` |
| Giảng viên | `minhanh` | `Teacher@123` |
| Giảng viên Faker | `demo_gv_1` | `Teacher@123` |

Sau đó vào **http://huit-schedule.test**, đăng nhập và thử các tính năng. **Đổi mật khẩu demo** tại trang **Tài khoản** trước khi dùng ngoài môi trường bài tập.

### Bước 6 · Tự kiểm tra máy và kiểm thử

```powershell
php scripts/check_environment.php
php scripts/lint.php
php tests/scheduler_test.php
```

Hoặc khi Composer đã cài đầy đủ:

```powershell
composer run lint
composer run test
```

Tài liệu kịch bản kiểm thử thao tác giao diện: [docs/TEST_CASES.md](docs/TEST_CASES.md).

## 4. Bảng dữ liệu / ERD

```mermaid
erDiagram
    branches ||--o{ schedules : "dia diem"
    teachers ||--o{ schedules : "duoc phan cong"
    teachers ||--o| users : "tai khoan"
    branches { int id PK; varchar branch_name; varchar address }
    teachers { int id PK; varchar full_name; varchar email UK; varchar phone; varchar expertise }
    schedules { int id PK; int teacher_id FK; int branch_id FK; varchar subject_name; varchar room; tinyint day_of_week; varchar shift_or_periods; tinyint period_start; tinyint period_end }
    users { int id PK; varchar username UK; varchar password_hash; enum role; int teacher_id FK }
```

Bảng `users` bổ sung cho **phân quyền**; trường `period_start` / `period_end` bổ sung cho **phát hiện trùng tiết**, đồng thời vẫn giữ trường `shift_or_periods` theo yêu cầu đề thực hành.

## 5. Cấu trúc thư mục

```text
huit-schedule/
├── index.php                    # Front controller / phân quyền
├── .htaccess                    # Chặn truy cập file nội bộ khi chạy Apache
├── .env.example                 # Biến môi trường mẫu
├── composer.json                # phpdotenv + faker
├── app/
│   ├── bootstrap.php            # Composer, .env, session
│   ├── helpers.php              # PDO prepared statements, CSRF, helper
│   ├── actions.php              # Login, CRUD, kiểm tra xung đột
│   └── Scheduler.php            # Thuật toán độc lập với database
├── api/
│   ├── suggestions.php          # API gợi ý giảng viên
│   └── availability.php         # API ma trận tiết trống
├── views/                       # 11 trang UI và layout
├── assets/                      # CSS, JS, favicon
├── database/
│   ├── huit_db.sql              # Import DB + dữ liệu mẫu
│   ├── schema.sql               # Chỉ tạo schema
│   └── seed.php                 # Tài khoản + Faker
├── scripts/
│   ├── lint.php                 # php -l cho toàn dự án
│   └── check_environment.php    # Kiểm tra yêu cầu môi trường
├── tests/scheduler_test.php     # 20 unit checks, không cần DB
├── docs/TEST_CASES.md           # Kịch bản E2E thủ công
├── .github/workflows/php-lint.yml
├── .gitignore
└── LICENSE                      # MIT
```

## 6. Đưa dự án lên GitHub (Public Repository)

Tạo repo GitHub **Public** tên `huit-schedule`, sau đó chạy tại thư mục dự án:

```powershell
git init
git add .
git commit -m "feat: build HUIT Schedule Hub"
git branch -M main
git remote add origin https://github.com/<TEN_TAI_KHOAN>/huit-schedule.git
git push -u origin main
```

Thay `<TEN_TAI_KHOAN>` bằng GitHub username. Workflow `.github/workflows/php-lint.yml` được kích hoạt khi push và khi tạo pull request. Xem mục **Actions** trên GitHub để kiểm tra kết quả.

## 7. Bảo mật và lưu ý phạm vi

- Mật khẩu trong DB luôn dùng **`password_hash` / `password_verify`**; session đăng nhập có `httponly`, `SameSite=Lax`, regenerate session ID.
- CRUD và đăng nhập dùng CSRF token; lệnh SQL sử dụng PDO prepared statements; nội dung hiển thị qua HTML escape.
- Có giới hạn 5 lần đăng nhập thất bại trong 60 giây theo session (mức demo).
- Chỉ quản trị viên được xem/chỉnh dữ liệu toàn hệ thống và gọi API gợi ý. Giảng viên chỉ xem lịch gắn với `teacher_id` của họ.
- `.htaccess` bảo vệ source, cấu hình và thư viện khi phục vụ bằng **Apache**. Nếu dùng Nginx cần thêm quy tắc cấm truy cập file riêng tư hoặc cấu hình DocumentRoot cho phép phục vụ file public.
- Đây là **ứng dụng minh họa phục vụ học phần**, chưa có học kỳ, dữ liệu học sinh/sinh viên, ngày nghỉ, lịch thi, SMTP đặt lại mật khẩu hay tối ưu tuyến đường theo bản đồ; không nên triển khai Internet công khai mà không bổ sung hardening và kiểm thử tích hợp.

## 8. Xử lý lỗi thường gặp

| Hiện tượng | Cách xử lý |
| --- | --- |
| `Thiếu thư viện Composer` | Chạy `composer install` đúng thư mục |
| `Không kết nối được MySQL` | Start All Laragon; kiểm tra `.env`; import `huit_db.sql` |
| `could not find driver` | Bật extension **pdo_mysql** của phiên bản PHP đang chạy |
| `Call to undefined function mb_strlen()` | Bật extension **mbstring** |
| `huit-schedule.test` không mở được | Reload Laragon, bật Auto Virtual Hosts; thử `localhost/huit-schedule` |
| Không đăng nhập được | Chạy `php database/seed.php` để tạo tài khoản có hash |
| Không lưu được lịch | Kiểm tra giảng viên, phòng, khung tiết và quy tắc di chuyển; thông báo đỏ sẽ nêu lý do |
| `403` khi truy cập trang admin | Đăng xuất rồi đăng nhập bằng tài khoản `admin` |

---

**Giấy phép:** MIT · **Nguồn dữ liệu mặc định:** dữ liệu minh hoạ, không phải dữ liệu thời khóa biểu chính thức của HUIT.
