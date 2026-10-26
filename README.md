# Thần Số Học
Plugin WordPress dùng để tra cứu và phân tích Thần Số Học dựa trên họ tên và ngày sinh.

## Tính năng
* Tính các chỉ số Thần Số Học từ họ tên và ngày sinh.
* Hiển thị kết quả trực tiếp trên giao diện WordPress.
* Hỗ trợ REST API cho tính toán và phân tích.
* Hỗ trợ nhiều ngôn ngữ.
* Có chế độ phân tích chuyên sâu bằng AI khi được bật trong phần quản trị.
* Có cơ chế cache kết quả phân tích.

## Yêu cầu
* WordPress.
* PHP 8.2 trở lên + mbstring extension được bật
* jQuery được WordPress cung cấp.
* Các tính năng AI cần hệ thống AI/credit liên quan của website cần cài thêm plugin fortune-tools và vibhh-pay.

## Cài đặt
1. Chép thư mục `thansohoc` vào:
   ```text
   wp-content/plugins/
   ```
2. Vào **WordPress Admin → Plugins**.
3. Kích hoạt plugin **Thần Số Học**.

## Sử dụng
Tạo một trang WordPress và chèn shortcode:

```text
[than_so_hoc_landing]
```

Plugin sẽ tự tải CSS và JavaScript cần thiết trên trang có shortcode này.

## REST API
Plugin đăng ký hai endpoint:

```text
POST /wp-json/ths/v1/calculate
POST /wp-json/ths/v1/analyze
```

Endpoint `calculate` dùng để tính và hiển thị các chỉ số cơ bản.
Endpoint `analyze` dùng cho phần phân tích chuyên sâu/AI khi tính năng này được bật.

## Cấu trúc chính

```text
thansohoc/
├── admin/          # Trang cài đặt trong WordPress Admin
├── assets/         # CSS và JavaScript
├── data/           # Dữ liệu Thần Số Học theo ngôn ngữ
├── i18n/           # Chuỗi giao diện đa ngôn ngữ
├── includes/       # Xử lý, tính toán, helper, prompt...
├── lib/            # Thư viện bên thứ ba
├── template/       # Form và giao diện kết quả
└── than-so-hoc.php # File khởi tạo plugin
```

## Tác giả
Loibv

## Demo
[https://solqi.net/](https://solqi.net/)

## License
GPL v2 or later.
