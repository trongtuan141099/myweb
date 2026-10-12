<?php
/**
 * Xử lý đăng xuất
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cập nhật thời gian đăng xuất
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Ghi lại thời gian logout
    $sql = "UPDATE login_logs SET logout_time = NOW() 
            WHERE user_id = ? AND logout_time IS NULL
            ORDER BY login_time DESC LIMIT 1";

    if (isset($conn) && $conn) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

// Xóa sạch dữ liệu trong mảng $_SESSION
$_SESSION = [];

// Xóa cookie định danh phiên (PHPSESSID) trên trình duyệt
if (ini_get('session.use_cookies') && !headers_sent()) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hủy phiên làm việc trên Server
session_destroy();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
?>
