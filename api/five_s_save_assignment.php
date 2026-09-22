<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.settings', 'api.five_s.save_assignment']);

$zone_id = intval($_POST['zone_id'] ?? 0);
$month_year = trim($_POST['month_year'] ?? '');
$inspector_id = intval($_POST['inspector_id'] ?? 0);
$assignee_id = intval($_POST['assignee_id'] ?? 0);

if ($zone_id <= 0 || empty($month_year) || $inspector_id <= 0 || $assignee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn đầy đủ thông tin phân công']);
    exit;
}

$monthStart = DateTime::createFromFormat('Y-m-d', $month_year . '-01');
if (!$monthStart || $monthStart->format('Y-m') !== $month_year) {
    echo json_encode(['success' => false, 'message' => 'Tháng phân công không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO five_s_assignments (zone_id, month_year, inspector_id, assignee_id) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id), assignee_id = VALUES(assignee_id)");
$stmt->bind_param("isii", $zone_id, $month_year, $inspector_id, $assignee_id);

if ($stmt->execute()) {
    // Expand the monthly assignment into daily work items for the selected month.
    $monthEnd = (clone $monthStart)->modify('last day of this month');
    $stmtSchedule = $conn->prepare("INSERT INTO five_s_schedules (zone_id, inspector_id, schedule_date)
                                    VALUES (?, ?, ?)
                                    ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id)");
    for ($date = clone $monthStart; $date <= $monthEnd; $date->modify('+1 day')) {
        $scheduleDate = $date->format('Y-m-d');
        $stmtSchedule->bind_param("iis", $zone_id, $inspector_id, $scheduleDate);
        $stmtSchedule->execute();
    }

    $message = "Bạn có lịch kiểm tra 5S tại khu vực trong tháng {$month_year}.";
    $link = "index.php?mainpage=five_s&subpage=mobile_audit";
    $stmtNotification = $conn->prepare("INSERT INTO notifications (user_id, title, message, link)
                                        VALUES (?, 'Lịch tuần tra 5S', ?, ?)");
    $stmtNotification->bind_param("iss", $inspector_id, $message, $link);
    $stmtNotification->execute();

    echo json_encode(['success' => true, 'message' => 'Lưu phân công trách nhiệm 5S thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu phân công']);
}