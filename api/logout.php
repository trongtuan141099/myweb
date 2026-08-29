<?php
/**
 * Xử lý đăng xuất
 */

session_start();

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

// Xóa session
session_destroy();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
?>
