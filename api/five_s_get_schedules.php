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

// Các bản database cũ chưa có ảnh mẫu; chỉ thêm cột khi chúng thực sự tồn tại.
$zoneColumns = [];
$columnResult = $conn->query("SHOW COLUMNS FROM five_s_zones");
while ($columnResult && ($column = $columnResult->fetch_assoc())) {
    $zoneColumns[$column['Field']] = true;
}

$okImageSelect = isset($zoneColumns['ok_reference_image'])
    ? 'z.ok_reference_image'
    : 'NULL';
$ngImageSelect = isset($zoneColumns['ng_reference_image'])
    ? 'z.ng_reference_image'
    : 'NULL';

// Hiển thị toàn bộ công việc, ưu tiên việc chưa làm và ngày gần nhất.
$stmt = $conn->prepare("SELECT s.*, z.zone_name, z.zone_code, z.layout_image,
                               {$okImageSelect} AS ok_reference_image,
                               {$ngImageSelect} AS ng_reference_image,
                               CASE WHEN s.schedule_date = ? AND s.status = 'pending' THEN 1 ELSE 0 END AS can_audit,
                               CASE WHEN s.schedule_date < ? AND s.status = 'pending' THEN 'expired'
                                    WHEN s.schedule_date > ? AND s.status = 'pending' THEN 'upcoming'
                                    ELSE s.status END AS work_state
                        FROM five_s_schedules s
                        JOIN five_s_zones z ON s.zone_id = z.id
                        WHERE s.inspector_id = ?
                        ORDER BY (s.status = 'pending') DESC,
                                 CASE WHEN s.status = 'pending' THEN s.schedule_date END ASC,
                                 CASE WHEN s.status = 'completed' THEN s.schedule_date END DESC,
                                s.id DESC");
        $stmt->bind_param("sssi", $today, $today, $today, $user_id);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy thông báo chưa đọc
$stmtNoti = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 AND DATE(created_at) = ? ORDER BY id DESC");
$stmtNoti->bind_param("is", $user_id, $today);
$stmtNoti->execute();
$notifications = $stmtNoti->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'schedules' => $schedules,
    'notifications' => $notifications
]);