# Memory Box – Local Gift Shop

Website bán hộp quà và quà tặng thủ công chạy **local**, dùng **HTML/CSS/JavaScript + PHP + MySQL**, không dùng framework và không tích hợp thanh toán online.

## Tính năng

- Đăng ký, đăng nhập, đăng xuất, quên mật khẩu (demo local).
- Trang chủ hiển thị toàn bộ sản phẩm.
- Mỗi sản phẩm có 3 hành động: **Thêm vào giỏ / Đặt hàng / Feedback**.
- Giỏ hàng ở navbar, cập nhật số lượng và đặt toàn bộ giỏ.
- Hồ sơ cá nhân: avatar chữ cái, thông tin và sửa thông tin.
- Trang **Tự thiết kế hộp quà** gồm đúng 2 phần:
  1. Chọn hộp: hình dáng + màu.
  2. Chọn quà bên trong: nến thơm, scrapbook, thiệp, hoa khô…
- Đặt hàng không cần thanh toán; sau khi đặt sẽ hiện trạng thái **Đã đặt hàng**.
- Lịch sử đơn hàng.
- Feedback theo sản phẩm.
- Dữ liệu lưu bằng MySQL local.

## Chạy nhanh bằng XAMPP trên Windows

1. Cài XAMPP và bật **Apache** + **MySQL**.
2. Clone repository vào thư mục htdocs:
   ```bash
   git clone https://github.com/nphi1410/memory-box-shop.git C:/xampp/htdocs/memory-box-shop-main
   ```
3. Mở `http://localhost/phpmyadmin`.
4. Chọn **Import** và import file `database/schema.sql` trong thư mục project.
   Schema tạo lại database `memory_box` và chèn dữ liệu mẫu. Mỗi lần import sẽ xóa các bảng hiện có trong database này trước khi tạo lại; chỉ chạy khi muốn bắt đầu lại từ đầu.
5. Mở trình duyệt tại:
   `http://localhost/memory-box-shop-main/`

Mặc định project kết nối MySQL bằng:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `memory_box`
- User: `root`
- Password: rỗng

Nếu MySQL của bạn dùng tài khoản khác, sửa `config/database.php` hoặc đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## Tài khoản demo

- Email: `demo@memorybox.local`
- Mật khẩu: `Demo@123`

## Cấu trúc chính

```text
assets/              CSS, JS, SVG hình sản phẩm
config/              Kết nối MySQL
includes/            Header, footer, helper, auth/CSRF
database/schema.sql  Schema + dữ liệu mẫu
index.php             Trang chủ
custom-box.php        Tự thiết kế hộp quà
cart.php              Giỏ hàng
orders.php            Lịch sử đơn hàng
profile.php           Hồ sơ cá nhân
```

## Ghi chú

Đây là bản demo local cho bài tập / prototype, chưa có email reset password, thanh toán, phân quyền quản trị hoặc upload ảnh thật. Mã nguồn đã dùng PDO prepared statements, password hashing, session authentication và CSRF token cho các form POST.
