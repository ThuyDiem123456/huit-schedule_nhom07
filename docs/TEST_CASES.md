# Kịch bản kiểm thử chức năng (manual E2E)

Sau khi import SQL, chạy `php database/seed.php`, đăng nhập `admin / Admin@123`.

| Mã | Vai trò | Thao tác | Kết quả mong đợi |
| --- | --- | --- | --- |
| AUTH-01 | Admin | Đăng nhập bằng `admin / Admin@123` | Chuyển tới Tổng quan, hiển thị số liệu |
| AUTH-02 | Giảng viên | Đăng nhập bằng `minhanh / Teacher@123` | Chuyển tới lịch dạy cá nhân |
| AUTH-03 | Giảng viên | Nhập `index.php?page=teachers` | Bị chuyển về trang lịch cá nhân |
| AUTH-04 | Khách | Truy cập trang quản trị khi chưa đăng nhập | Chuyển tới đăng nhập |
| CRUD-01 | Admin | Thêm, sửa cơ sở | Bản ghi hiển thị trong danh sách |
| CRUD-02 | Admin | Thêm giảng viên kèm tài khoản | Giảng viên có thể đăng nhập và xem lịch |
| CRUD-03 | Admin | Tạo lịch mới cho giảng viên trống | Lịch được lưu; dashboard cập nhật |
| SLOT-01 | Admin | Phân thêm giảng viên 1, T2, tiết 2–4 | Từ chối vì chồng tiết lịch hiện có |
| SLOT-02 | Admin | Phân lịch dùng phòng A201, cơ sở 1, T2, tiết 2–4 với giảng viên khác | Từ chối vì trùng phòng |
| SLOT-03 | Admin | Giảng viên 1, T2, tiết 4–6 tại cơ sở 2 | Từ chối vì chuyển cơ sở không có tiết trống |
| SLOT-04 | Admin | Giảng viên 1, T2, tiết 5–7 tại cơ sở 2, phòng trống | Cho phép (tiết 4 dành để di chuyển) |
| SLOT-05 | Admin | Giảng viên 1, T2, tiết 4–6 tại cơ sở 1, phòng trống | Cho phép cùng cơ sở, liền tiết |
| SUG-01 | Admin | Học phần `Lập trình PHP`, T2, tiết 7–9 | Gợi ý giảng viên có chuyên môn phù hợp và còn trống |
| SUG-02 | Admin | Chọn giảng viên, cơ sở, thứ | Hiện 12 tiết với màu trống/bận |
| SEARCH-01 | Admin | Lọc lịch theo giảng viên / cơ sở / thứ | Bảng lịch phản ánh bộ lọc |
| SEARCH-02 | Admin | Lọc tại Tổng quan | Biểu đồ và KPI cập nhật theo bộ lọc |
| CAL-01 | Giảng viên | Chọn `Theo tuần`, chuyển tuần | Lịch giảng dạy lặp lại ở thứ tương ứng |
| CAL-02 | Giảng viên | Chọn `Theo tháng`, chuyển tháng | Lịch tuần lặp lại trong các tuần của tháng |
| PROFILE-01 | Cả hai | Đổi mật khẩu từ trang Tài khoản | Mật khẩu mới sử dụng được khi đăng nhập |

> Những trường hợp trên là **kịch bản để chạy thủ công**, không phải kết quả PASS đã được xác minh bằng trình duyệt và MySQL thực tế. Kiểm thử thuật toán chạy độc lập bằng `php tests/scheduler_test.php`.
