<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$zone_id = intval($_POST['zone_id'] ?? 0);
$month_year = trim($_POST['month_year'] ?? '');
$inspector_id = intval($_POST['inspector_id'] ?? 0);
$assignee_id = intval($_POST['assignee_id'] ?? 0);

if ($zone_id <= 0 || empty($month_year) || $inspector_id <= 0 || $assignee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn đầy đủ thông tin phân công']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO five_s_assignments (zone_id, month_year, inspector_id, assignee_id) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id), assignee_id = VALUES(assignee_id)");
$stmt->bind_param("isii", $zone_id, $month_year, $inspector_id, $assignee_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Lưu phân công trách nhiệm 5S thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu phân công']);
}