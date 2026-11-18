# Verification report

Kiểm tra tĩnh/thành phần trên môi trường đóng gói dự án:

| Hạng mục | Kết quả |
| --- | --- |
| `php scripts/lint.php` | **PASS** – 23 PHP files, không lỗi cú pháp |
| `php tests/scheduler_test.php` | **PASS** – 20/20 test cho quy tắc ngày, tiết, trùng lịch, di chuyển, chuyên môn, điểm xếp hạng |
| `node --check assets/app.js` | **PASS** – JavaScript hợp lệ |
| Parse `assets/app.css` bằng tinycss2 | **PASS** – 279 rules, 0 parse errors |
| Đối chiếu 12 lịch demo trong `database/huit_db.sql` | **PASS** – không trùng giảng viên/phòng hoặc liền tiết giữa hai cơ sở |
| Đăng nhập / CRUD thực tế trên Laragon + MySQL | **CHƯA KIỂM THỬ TRÊN MÁY NGƯỜI DÙNG** – cần import SQL, Composer install và seed |

Các PASS ở trên **không thay thế kiểm thử E2E**. Xem `docs/TEST_CASES.md` để thực hiện kiểm thử có bằng chứng trên môi trường Laragon.
