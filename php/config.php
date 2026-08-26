<?php
/**
 * Cấu hình kết nối cơ sở dữ liệu
 */

// Thông tin kết nối Database
define('DB_HOST', 'localhost');      // Địa chỉ máy chủ
define('DB_USER', 'root');           // Người dùng MySQL
define('DB_PASS', '');               // Mật khẩu MySQL
define('DB_NAME', 'myweb');          // Tên database

// Tạo kết nối MySQL
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Kiểm tra kết nối
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    // Thiết lập charset UTF-8
    $conn->set_charset("utf8");

    // Báo hiệu thành công (tùy chọn)
     //echo "Kết nối database thành công!";
    
} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
