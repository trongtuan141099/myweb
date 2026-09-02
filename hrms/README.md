# HRMS - Hệ Thống Quản Lý Nhân Sự

Một hệ thống quản lý nhân sự (HRMS) đầy đủ chức năng được xây dựng bằng PHP, Bootstrap, jQuery và MySQL.

## Tính năng chính

### 1. **Dashboard**
- Tổng quan số liệu chính (nhân viên hoạt động, phòng ban, điểm danh hôm nay, chờ phê duyệt)
- Thống kê chi tiết theo phòng ban, vị trí
- Nhân viên mới và thống kê chuyên cần

### 2. **Quản lý Nhân Viên**
- Danh sách nhân viên với tìm kiếm, lọc theo phòng ban và trạng thái
- Thêm nhân viên mới với form đầy đủ
- Chỉnh sửa thông tin nhân viên
- Xóa nhân viên (kèm cascade delete các dữ liệu liên quan)
- Nhập dữ liệu nhân viên từ Excel
- Xem chi tiết nhân viên

### 3. **Quản lý Chuyên Cần**
- Danh sách chuyên cần hàng ngày
- Điểm danh (check-in/check-out)
- Quản lý trạng thái: Có mặt, Vắng, Muộn, Nửa ngày
- Báo cáo chuyên cần

### 4. **Quản lý Lương**
- Tạo bảng lương tháng
- Quản lý lương cơ bản, phụ cấp, khấu trừ
- Tính toán lương ròng tự động
- Báo cáo lương

### 5. **Quản lý Nghỉ Phép**
- Nhân viên đăng ký xin nghỉ
- Phê duyệt/Từ chối đơn xin nghỉ
- Danh sách lịch sử nghỉ phép

### 6. **Master Data**
- Quản lý phòng ban
- Quản lý chức vụ
- Cài đặt hệ thống

### 7. **Báo Cáo**
- Báo cáo nhân viên
- Báo cáo chuyên cần
- Báo cáo lương

## Cài đặt

### Yêu cầu
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx Web Server

### Bước 1: Sao chép tệp
```bash
cd d:/xampp/htdocs/hrms
```

### Bước 2: Cấu hình Database
Mở file `config/db.php` và cập nhật thông tin kết nối:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hrms";
```

### Bước 3: Tạo Database và Tables
Truy cập `http://localhost/hrms/` trong trình duyệt. Database và tables sẽ tự động được tạo.

### Bước 4: Thêm Dữ Liệu Mẫu (Tùy chọn)
Truy cập `http://localhost/hrms/setup_sample_data.php` để thêm dữ liệu mẫu.

## Cấu trúc Thư Mục

```
hrms/
├── config/
│   └── db.php              # Cấu hình Database
├── includes/
│   ├── header.php          # Header/Navbar
│   ├── sidebar.php         # Sidebar Navigation
│   └── footer.php          # Footer
├── modules/
│   ├── dashboard/          # Dashboard
│   ├── employees/          # Quản lý Nhân viên
│   ├── attendance/         # Quản lý Chuyên cần
│   ├── payroll/            # Quản lý Lương
│   ├── leave/              # Quản lý Nghỉ phép
│   ├── masterdata/         # Master Data
│   └── reports/            # Báo cáo
├── api/                    # API endpoints
├── index.php               # Trang chính
├── setup_sample_data.php   # Thêm dữ liệu mẫu
└── README.md              # File này
```

## Hướng Dẫn Sử Dụng

### Sidebar Navigation
Sidebar sử dụng hệ thống 2 cấp:
- **Mainpage**: Danh mục chính (employees, attendance, payroll, leave, etc.)
- **Subpage**: Trang con (list, add, edit, etc.)

URL format: `index.php?mainpage=MODULE&subpage=ACTION`

Ví dụ:
- `?mainpage=employees&subpage=list` - Danh sách nhân viên
- `?mainpage=employees&subpage=add` - Thêm nhân viên
- `?mainpage=attendance&subpage=checkin` - Điểm danh

### Form Nhập Liệu

#### Form Thêm Nhân Viên
- Mã nhân viên (bắt buộc, duy nhất)
- Tên đầy đủ (bắt buộc)
- Email (bắt buộc)
- Điện thoại
- Ngày sinh
- Giới tính
- Phòng ban
- Vị trí
- Ngày tuyển
- Lương cơ bản
- Trạng thái

#### Form Điểm Danh
- Ngày
- Nhân viên (tự động lấy danh sách nhân viên hoạt động)
- Giờ vào
- Giờ ra
- Trạng thái (Có mặt/Vắng/Muộn/Nửa ngày)
- Ghi chú

#### Form Đăng Ký Nghỉ Phép
- Nhân viên
- Loại nghỉ (Thường niên/Ốm/Cá nhân/Thai sản/Không lương)
- Từ ngày
- Đến ngày
- Lý do

### Bảng Hiển Thị

#### Bảng Nhân Viên
Hiển thị: Mã NV, Tên, Email, Phòng ban, Vị trí, Lương, Trạng thái
- Tìm kiếm theo tên/mã
- Lọc theo phòng ban
- Lọc theo trạng thái
- Chỉnh sửa/Xóa

#### Bảng Chuyên Cần
Hiển thị: Tên, Mã NV, Ngày, Giờ vào, Giờ ra, Trạng thái, Ghi chú
- Lọc theo ngày
- Lọc theo nhân viên
- Lọc theo trạng thái

#### Bảng Lương
Hiển thị: Tên, Mã NV, Tháng/Năm, Lương cơ bản, Phụ cấp, Khấu trừ, Lương ròng
- Lọc theo tháng/năm
- Lọc theo nhân viên

## Giao Diện

### Material UI Design
- Hiệu ứng gradient trên header
- Sidebar tối màu với hover effect
- Card design cho nội dung
- Badge cho trạng thái
- Responsive design cho mobile

### Màu Sắc
- Màu xanh dương chính: `#1976d2`
- Sidebar: `#263238`
- Success (Xanh lá): `#4caf50`
- Danger (Đỏ): `#d32f2f`
- Warning (Cam): `#ff9800`

## API Endpoints

| Endpoint | Phương thức | Chức năng |
|----------|------------|----------|
| `/api/check_email.php` | POST | Kiểm tra email tồn tại |
| `/api/delete_employee.php` | GET | Xóa nhân viên |
| `/api/delete_attendance.php` | GET | Xóa điểm danh |
| `/api/delete_payroll.php` | GET | Xóa bảng lương |
| `/api/approve_leave.php` | GET | Phê duyệt/Từ chối nghỉ phép |
| `/api/add_department.php` | POST | Thêm phòng ban |

## Database Schema

### Bảng: employees
```sql
- id (Primary Key)
- employee_id (Unique)
- full_name
- email
- phone
- dob (Date of Birth)
- gender
- position
- department
- join_date
- salary
- status (Active/Inactive/On Leave)
- created_at, updated_at
```

### Bảng: attendance
```sql
- id (Primary Key)
- employee_id (Foreign Key)
- date
- check_in
- check_out
- status
- notes
- created_at
```

### Bảng: leave_requests
```sql
- id (Primary Key)
- employee_id (Foreign Key)
- leave_type
- start_date
- end_date
- reason
- status (Pending/Approved/Rejected)
- approved_by
- created_at
```

### Bảng: payroll
```sql
- id (Primary Key)
- employee_id (Foreign Key)
- month
- year
- basic_salary
- allowances
- deductions
- net_salary
- created_at
```

### Bảng: departments
```sql
- id (Primary Key)
- name (Unique)
- description
- manager_id
- created_at
```

## Lưu Ý An Ninh

- Luôn sử dụng Prepared Statements để tránh SQL Injection
- Xác thực đầu vào từ người dùng
- Hash password khi lưu trữ (nên thêm tính năng xác thực)
- Sử dụng HTTPS trong production
- Giới hạn quyền truy cập Database

## Tính Năng Trong Tương Lai

- [ ] Authentication & Authorization
- [ ] Quản lý người dùng và quyền hạn
- [ ] Export báo cáo to PDF/Excel
- [ ] Email notification
- [ ] Mobile app
- [ ] Advanced search & filtering
- [ ] Audit log
- [ ] Performance optimization

## Hỗ Trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. Kết nối Database có chính xác không
2. Các thư mục cần có quyền ghi (write permission)
3. PHP version phù hợp
4. Enable JavaScript trong trình duyệt

## License

Dự án này được cấp phép dưới MIT License.

---

**Tạo bởi**: System Administrator  
**Phiên bản**: 1.0  
**Cập nhật lần cuối**: 2026
