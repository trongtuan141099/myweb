<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.view', 'five_s.audit', 'api.five_s.schedules']);

$user_id = $_SESSION['user_id'] ?? 1;
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// 1. Tự động kiểm tra và sinh nhắc việc nếu có việc đến hạn hôm nay
$stmtCheckToday = $conn->prepare("SELECT s.id, z.zone_name, z.zone_code 
                                  FROM five_s_schedules s
                                  JOIN five_s_zones z ON s.zone_id = z.id
                                  WHERE s.inspector_id = ? AND s.schedule_date = ? AND s.status = 'pending'");
$stmtCheckToday->bind_param("is", $user_id, $today);
$stmtCheckToday->execute();
$todayPending = $stmtCheckToday->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($todayPending)) {
    // Check if notification already sent today for due inspections
    $stmtNotifExist = $conn->prepare("SELECT id FROM notifications 
                                      WHERE user_id = ? AND title = 'Lịch kiểm tra 5S hôm nay' 
                                        AND DATE(created_at) = ?");
    $stmtNotifExist->bind_param("is", $user_id, $today);
    $stmtNotifExist->execute();
    if (!$stmtNotifExist->get_result()->fetch_assoc()) {
        $msg = "Bạn có " . count($todayPending) . " vị trí 5S cần kiểm tra tại hiện trường hôm nay.";
        $link = "index.php?mainpage=five_s&subpage=mobile_audit";
        $insNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Lịch kiểm tra 5S hôm nay', ?, ?)");
        $insNotif->bind_param("iss", $user_id, $msg, $link);
        $insNotif->execute();
    }
}

// 2. Cảnh báo các lịch quá hạn chưa kiểm tra
$stmtCheckOverdue = $conn->prepare("SELECT COUNT(*) AS overdue_cnt 
                                    FROM five_s_schedules 
                                    WHERE inspector_id = ? AND schedule_date < ? AND status = 'pending'");
$stmtCheckOverdue->bind_param("is", $user_id, $today);
$stmtCheckOverdue->execute();
$overdueRes = $stmtCheckOverdue->get_result()->fetch_assoc();
$overdueCnt = intval($overdueRes['overdue_cnt'] ?? 0);

if ($overdueCnt > 0) {
    // Check if notification already sent today for overdue
    $stmtNotifOverdue = $conn->prepare("SELECT id FROM notifications 
                                        WHERE user_id = ? AND title = 'Cảnh báo 5S quá hạn' 
                                          AND DATE(created_at) = ?");
    $stmtNotifOverdue->bind_param("is", $user_id, $today);
    $stmtNotifOverdue->execute();
    if (!$stmtNotifOverdue->get_result()->fetch_assoc()) {
        $msg = "Bạn có {$overdueCnt} lượt kiểm tra 5S quá hạn chưa hoàn thành!";
        $link = "index.php?mainpage=five_s&subpage=mobile_audit";
        $insNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Cảnh báo 5S quá hạn', ?, ?)");
        $insNotif->bind_param("iss", $user_id, $msg, $link);
        $insNotif->execute();
    }
}

// 3. Cảnh báo lỗi vi phạm chưa hoàn thành đối sách cho người phụ trách
$stmtCheckIssues = $conn->prepare("SELECT COUNT(*) AS pending_issues 
                                   FROM five_s_issues 
                                   WHERE assignee_id = ? AND status IN ('pending', 'in_progress')");
$stmtCheckIssues->bind_param("i", $user_id);
$stmtCheckIssues->execute();
$issueRes = $stmtCheckIssues->get_result()->fetch_assoc();
$pendingIssues = intval($issueRes['pending_issues'] ?? 0);

if ($pendingIssues > 0) {
    $stmtNotifIssues = $conn->prepare("SELECT id FROM notifications 
                                       WHERE user_id = ? AND title = 'Nhắc việc đối sách 5S' 
                                         AND DATE(created_at) = ?");
    $stmtNotifIssues->bind_param("is", $user_id, $today);
    $stmtNotifIssues->execute();
    if (!$stmtNotifIssues->get_result()->fetch_assoc()) {
        $msg = "Bạn có {$pendingIssues} lỗi 5S chưa hoàn thành đối sách khắc phục.";
        $link = "index.php?mainpage=five_s&subpage=list";
        $insNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Nhắc việc đối sách 5S', ?, ?)");
        $insNotif->bind_param("iss", $user_id, $msg, $link);
        $insNotif->execute();
    }
}

// 4. Lấy danh sách toàn bộ lịch kiểm tra
$stmt = $conn->prepare("SELECT s.*, z.zone_name, z.zone_code, z.department, z.qr_code_hash, 
                               z.checklist_items, z.layout_image,
                               z.ok_reference_image, z.ng_reference_image,
                               z.color_ok, z.color_ng, z.color_pending,
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

// Lấy danh sách thông báo chưa đọc của user
$stmtNoti = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT 20");
$stmtNoti->bind_param("i", $user_id);
$stmtNoti->execute();
$notifications = $stmtNoti->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'today' => $today,
    'schedules' => $schedules,
    'notifications' => $notifications
]);
