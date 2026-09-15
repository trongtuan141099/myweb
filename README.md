# MyWeb - Hệ thống quản lý sản xuất, nhân sự, tài liệu và thiết bị

## 1. Mục đích tài liệu

Mọi thay đổi phải ưu tiên mã nguồn hiện hành ở thư mục gốc và giữ đúng mô hình `mainpage/subpage`.

Dự án hiện gồm nhiều nhánh phát triển trong cùng workspace:

- **Ứng dụng chính**: mã PHP ở thư mục gốc, dùng database `myweb`.
- **Firmware IoT**: `esp32/iot_device/` và `esp32/sample/`.

## 2. Công nghệ bắt buộc

Ứng dụng chính sử dụng:

- PHP thuần, không dùng framework.
- MySQL/MariaDB qua `mysqli`.
- HTML5, CSS, JavaScript thuần.
- Bootstrap 5 và ApexCharts cho giao diện/biểu đồ.
- `fetch()` để gọi API AJAX.
- Composer autoload, PhpSpreadsheet và SimpleXLSX cho một số thao tác file Excel.
- Apache/XAMPP là môi trường chạy dự kiến.

Khi lập trình:

1. Dùng PHP, HTML, CSS, JavaScript và MySQL theo các pattern đã có.
2. Ưu tiên prepared statement với `mysqli` cho mọi truy vấn có dữ liệu đầu vào.
3. Escape dữ liệu khi xuất HTML bằng `htmlspecialchars()`; validate dữ liệu ở cả frontend và backend.
4. API trả JSON UTF-8, có trường `success` và `message` khi phù hợp.
5. Không đặt mật khẩu, thông tin kết nối hoặc dữ liệu nhạy cảm cố định trong mã nguồn.
6. Không tạo framework/router mới khi có thể dùng router hiện tại.
7. Giữ giao diện responsive và tái sử dụng `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`.

## 3. Kiến trúc ứng dụng chính

Request của ứng dụng chính đi qua `index.php`:

```text
index.php
  -> đọc ?mainpage=...
  -> đọc ?subpage=...
  -> nạp config/db.php và core/check_permission.php
  -> kiểm tra đăng nhập nếu không phải authentication/login
  -> nạp includes/sidebar.php và includes/header.php
  -> include modules/{mainpage}/{subpage}.php
  -> nạp includes/footer.php
```

Giá trị mặc định:

```text
mainpage=authentication
subpage=login
```

URL mẫu:

```text
http://localhost/myweb/
http://localhost/myweb/index.php?mainpage=production&subpage=production_planing
```

`modules/{mainpage}/{subpage}.php` là nội dung được nhúng trực tiếp vào layout chung. Khi thêm trang mới, tạo đúng thư mục `mainpage` và file `subpage.php`, sau đó cập nhật sidebar nếu trang cần hiển thị trên menu.

## 4. Quy tắc Sidebar hai cấp

Sidebar bắt buộc sử dụng mô hình hai cấp:

- **Mainpage**: danh mục chính, ví dụ `employees`, `attendance`, `payroll`, `leave`, `production`.
- **Subpage**: trang con, ví dụ `list`, `add`, `edit`, `report`, `approval`.

Định dạng liên kết:

```text
index.php?mainpage=MODULE&subpage=ACTION
```

Ví dụ:

```text
?mainpage=authentication&subpage=login
?mainpage=dashboard&subpage=overview
?mainpage=production&subpage=production_planing
?mainpage=hrm&subpage=list
?mainpage=hrm&subpage=add_employee
?mainpage=document&subpage=viewer
?mainpage=iot&subpage=device_status
?mainpage=iot&subpage=device_history
?mainpage=system&subpage=roles
```

Không tạo URL dạng router thứ ba cho ứng dụng chính nếu không có lý do bắt buộc. `mainpage` phải tương ứng với thư mục trong `modules/`; `subpage` phải tương ứng với file PHP trong thư mục đó. Menu trỏ tới `#` hoặc file chưa tồn tại phải được xem là placeholder, không được giả định là chức năng đã hoàn thành.

## 5. Các module hiện hành ở root

| Mainpage | Subpage hiện có | Chức năng |
|---|---|---|
| `authentication` | `login` | Đăng nhập và khởi tạo session |
| `dashboard` | `overview` | Tổng quan hệ thống |
| `production` | `production_planing` | Kế hoạch, sản lượng thực tế và báo cáo theo size ống |
| `hrm` | `list`, `add_employee` | Quản lý nhân viên cơ bản |
| `document` | `viewer` | Xem và quản lý tài liệu |
| `iot` | `device_status`, `device_history` | Trạng thái và lịch sử thiết bị |
| `system` | `roles` | Vai trò/quyền, hiện còn sơ khai |
| `sample` | `format` | Mẫu/thử nghiệm |

### Nghiệp vụ chính

- Đăng nhập, đăng xuất và quản lý session.
- Theo dõi dashboard.
- Lập kế hoạch sản xuất theo tháng, ngày và size ống.
- Import dữ liệu thực tế đùn từ Excel.
- Báo cáo sản lượng ngày, lũy kế, hiệu suất và tỷ lệ hoàn thành.
- Quản lý nhân viên.
- Upload, sửa, xóa và xem tài liệu PDF.
- Theo dõi trạng thái line/thiết bị từ ESP32.
- Lưu lịch sử thay đổi trạng thái thiết bị.

## 6. API ứng dụng chính

| Endpoint | Mục đích |
|---|---|
| `api/login_process.php` | Đăng nhập bằng JSON POST |
| `api/check_auth.php` | Kiểm tra session |
| `api/logout.php` | Đăng xuất và hủy session |
| `api/get_production_report.php` | Tổng hợp dữ liệu sản xuất |
| `api/save_production_plan.php` | Lưu ma trận kế hoạch sản xuất |
| `api/upload_extrusion_actuals.php` | Import dữ liệu đùn từ Excel |
| `api/iot_status.php` | Nhận heartbeat/event từ ESP32 |
| `api/add_employee.php` | Thêm nhân viên |
| `api/get_documents.php` | Đọc danh sách tài liệu JSON |
| `api/upload_document.php` | Upload tài liệu |
| `api/edit_document.php` | Sửa metadata/tài liệu |
| `api/delete_document.php` | Xóa metadata và file tài liệu |

API thường được gọi bằng `fetch()`. Trước khi đổi contract JSON, phải kiểm tra cả module frontend gọi API và các thiết bị/client đang gửi dữ liệu.

### Lưu ý riêng về production

`modules/production/production_planing.php` hiện đang chờ các trường như `plan_matrix`, `plan_daily`, `plan_cum`, `summary_table`, `sizes_data`. Trong khi đó `api/get_production_report.php` hiện trả chủ yếu `actual_daily`, `actual_cum` và `size_summary`. Đây là điểm không đồng bộ cần xử lý có chủ đích, không được âm thầm đổi tên dữ liệu ở một phía.

## 7. Xác thực và phân quyền

Các file liên quan:

- `core/check_permission.php`: `checkAuth()`, `hasPermission()`, `renderPermissionScript()`.
- `config/permission.php`: map role sang permission.
- `api/login_process.php`: xác thực tài khoản và tạo session.

Session hiện sử dụng các thông tin như:

```php
$_SESSION['user_id'];
$_SESSION['user'];
$_SESSION['user']['role'];
$_SESSION['user']['permissions'];
```

Role hiện có: `admin`, `editor`, `viewer`.

Permission hiện có:

```text
document.view
document.upload
document.edit
document.delete
device.view
device.edit
user.view
```

Khi thêm chức năng cần kiểm tra quyền ở backend, không chỉ ẩn nút ở frontend. API IoT, production và document cần được rà soát quyền truy cập trước khi dùng trong môi trường thật.

## 8. Database và dữ liệu

### Database chính `myweb`

Cấu hình nằm ở `config/db.php`.

Các bảng/nhóm dữ liệu quan trọng:

- `users`, `login_logs`: tài khoản và lịch sử đăng nhập.
- `employees`, `nhanvien`: nhân viên hiện hành và legacy.
- `production_plans`: kế hoạch theo tháng, size, ngày.
- `production_actuals`, `extrusion_actual_logs`: sản lượng thực tế và log import.
- `devices`, `device_status`, `device_history`, `device_historys`: trạng thái thiết bị và các thiết kế lịch sử khác nhau.
- `documents`, `document`, `categories`: các thiết kế quản lý tài liệu khác nhau.

Khi tạo hoặc sửa schema:

1. Xác định bảng đang được API/module sử dụng thực tế.
2. Bảo đảm index/unique key phù hợp, đặc biệt với khóa kế hoạch sản xuất.
3. Dùng migration hoặc SQL có kiểm soát; không tự ý sửa một dump cũ rồi coi đó là schema duy nhất.
4. Dùng transaction cho thao tác nhiều bảng nếu nghiệp vụ yêu cầu tính toàn vẹn.

## 9. Cấu trúc thư mục quan trọng

```text
index.php                 # Front controller của ứng dụng chính
config/                   # Kết nối DB và map quyền
core/                     # Logic dùng chung, xác thực/phân quyền
includes/                 # Header, sidebar, footer
modules/                  # Trang theo mainpage/subpage
api/                      # Endpoint JSON/AJAX
css/                      # CSS giao diện chính
js/                       # JavaScript dùng chung
resources/                # Bootstrap, ApexCharts, font và tài nguyên frontend
data/                    # SQL dump, CSV, JSON tài liệu
documents/               # File tài liệu upload
hrms/                    # Ứng dụng HRMS độc lập
hrm_test/                # Bản thử nghiệm HRM
pages/                   # Trang legacy
Ref/                     # Mã tham chiếu/thử nghiệm
esp32/                   # Firmware IoT ESP-IDF
vendor/                  # Dependency Composer/thư viện bên thứ ba
```
## 11. ESP32/IoT

`esp32/iot_device/` là firmware gửi trạng thái tới:

```text
http://{server_ip}/myweb/api/iot_status.php
```

Thiết bị hỗ trợ các event/heartbeat như `ON`, `OFF`, `ERROR` và `HEARTBEAT`, dùng Wi-Fi, HTTP, NVS, GPIO và mDNS.

`esp32/sample/` là firmware thử nghiệm riêng cho captive portal cấu hình Wi-Fi và điều khiển LED. Không mô tả hai firmware như một sản phẩm duy nhất.

## 12. Cài đặt và chạy ứng dụng chính

Yêu cầu:

- Apache và MySQL/MariaDB, thường chạy qua XAMPP.
- PHP phù hợp với dependency hiện có; kiểm tra Composer trước khi nâng/hạ phiên bản.
- Workspace đặt dưới web root, ví dụ `d:/xampp/htdocs/myweb`.

Các bước:

1. Bật Apache và MySQL.
2. Tạo database `myweb`.
3. Import schema SQL đã được thống nhất trong `data/`.
4. Chạy thêm `data/five_s_workflow.sql` để tạo lịch tuần tra, thông báo, checklist và mã khu vực 5S.
5. Cập nhật `config/db.php` theo môi trường cục bộ.
6. Truy cập `http://localhost/myweb/`.
7. Kiểm tra log PHP, console trình duyệt và response JSON khi một module không tải được.

Đường dẫn `/myweb/` đang được hard-code ở một số JavaScript/API client; nếu đổi tên virtual directory, phải rà soát các đường dẫn này.

### Chạy firmware

```text
cd esp32/iot_device
idf.py build
idf.py flash monitor
```

ESP32 phải kết nối được cùng mạng LAN với máy chạy Apache và được cấu hình đúng server IP, API path, Wi-Fi và device code.

## 13. Quy trình làm việc khi sửa code

1. Xác định ứng dụng đang sửa: root app, `hrms`, legacy hay ESP32.
2. Xác định URL `mainpage/subpage` và file module sở hữu giao diện.
3. Xác định API/database mà module đang sử dụng.
4. Kiểm tra quyền truy cập và session.
5. Sửa nhỏ, giữ public contract nếu không có yêu cầu đổi API.
6. Kiểm tra PHP syntax, SQL, response JSON và console JavaScript.
7. Kiểm tra trên desktop/mobile nếu thay đổi giao diện.
8. Không xóa hoặc ghi đè các file `copy`, legacy hay thay đổi người dùng khi chưa xác định phạm vi.

### Lưu trình kiểm tra 5S
1. Nhân viên được phân công sẽ định kỳ đi kiểm tra 5S theo kế hoạch đã được thiết lập hằng ngày, thông báo công việc sẽ được hiển thị tại nút thông báo và hiển thị popup messege khi nhân viên đăng nhập, khi nhấn vào message sẽ dẫn hiển thị tổng quan các khu vực cần thực hiện kiểm tra. 
2. Nhân viên tiến hành đi kiểm tra tại các vị trí chỉ định, để đảm bảo nhân viên đã đến vị trí cần kiểm tra, khi đến vị trí kiểm tra nhân viên phải thực hiện quét mã QR hoặc NFC tại khu vực đó. 
3. Sau khi xác thực có mặt tại vị trí kiểm tra nhân viên phải thực hiện cập nhật form báo cáo nhân viên khi truy cập form sẽ thấy được ngay hình ảnh nhận biết OK và NG, nếu khu vực không có vi phạm chỉ cần thực hiện checklist các hạng mục cần kiểm tra sau đó chụp hình ghi nhận tại vị trí đó và công việc tuần tra tại khu vực đó thể hiện đã kiểm tra. nếu có vi phạm nhân viên sẽ tích chọn vào báo cáo vi phạm và nhập nội dung mô tả lỗi vi phạm, nhấn gửi lỗi vi phạm, ngay lập tức lỗi vi phạm sẽ được ghi nhận
4. Khi có phát sinh vi phạm lỗi vi phạm ngay lập tức vị trí có lỗi sẽ được hiển thị trên dashboard đồng thời gửi thông báo đến người chịu trách nhiệm khu vực đó
5. Người chịu trách nhiệm khu vực đó sẽ nhận được thông báo và sắp xếp nhân viên thực hiện dọn dẹp, sau khi vệ sinh hoàn tất thì cập nhật hình ảnh sau khi đối ứng hoàn tất.
6. Trang dashboard bao gồm Toàn bộ dữ liệu vi phạm sẽ được tổng hợp lại để thống kê các lỗi thường xuyên phát sinh, khu vực hay phát sinh, tần suất khu vực nhân viên chịu trách nhiệm quản lý vi phạm và hình ảnh biểu ngữ các hoạt động 5S đang được duy trì thực hiện

# LƯU TRÌNH KIỂM TRA & XỬ LÝ VI PHẠM 5S

### 1. Nhận thông báo lịch tuần tra
* **Hệ thống:** Tự động gửi thông báo (Notification/Popup) cho nhân viên phụ trách khi đăng nhập.
* **Thao tác:** Nhấn vào thông báo để xem tổng quan danh sách các khu vực cần kiểm tra trong ngày.

### 2. Xác thực vị trí kiểm tra
* **Thao tác:** Nhân viên đến trực tiếp khu vực chỉ định và quét **mã QR** hoặc **NFC** để xác nhận có mặt.

### 3. Thực hiện kiểm tra & Gửi báo cáo
* **Giao diện:** Màn hình hiển thị ảnh mẫu chuẩn **OK / NG** và checklist hạng mục.
* **Trường hợp ĐẠT (OK):** Tick checklist -> Chụp ảnh thực tế -> Hoàn thành kiểm tra khu vực.
* **Trường hợp VI PHẠM (NG):** Tick "Báo cáo vi phạm" -> Nhập mô tả lỗi -> Nhấn "Gửi" để ghi nhận tức thì.

### 4. Cảnh báo & Giao việc tự động
* **Hệ thống:** 
  * Cập nhật ngay vị trí lỗi lên **Dashboard trực quan**.
  * Tự động gửi thông báo vi phạm đến **Người chịu trách nhiệm khu vực**.

### 5. Khắc phục & Nghiệm thu
* **Người chịu trách nhiệm:** Nhận thông báo -> Phân công xử lý/dọn dẹp.
* **Thao tác:** Chụp và tải ảnh kết quả sau khi đối ứng để hoàn tất quy trình.

### 6. Thống kê & Quản lý tập trung (Dashboard)
* **Dữ liệu tổng hợp:**
  * Thống kê loại lỗi/khu vực phát sinh vi phạm nhiều nhất.
  * Tần suất vi phạm theo từng người chịu trách nhiệm.
* **Truyền thông:** Hiển thị hình ảnh/biểu ngữ các hoạt động 5S đang duy trì tại nhà xưởng.

