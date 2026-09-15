<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Lấy danh sách lịch tuần tra hôm nay
$stmt = $conn->prepare("SELECT s.*, z.zone_name, z.zone_code 
                        FROM five_s_schedules s 
                        JOIN five_s_zones z ON s.zone_id = z.id 
                        WHERE s.inspector_id = ? AND s.schedule_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy thông báo chưa đọc
$stmtNoti = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC");
$stmtNoti->bind_param("i", $user_id);
$stmtNoti->execute();
$notifications = $stmtNoti->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'schedules' => $schedules,
    'notifications' => $notifications
]);