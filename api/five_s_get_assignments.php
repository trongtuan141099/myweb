<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$stmt = $conn->prepare("SELECT a.id, a.zone_id, a.month_year, a.inspector_id, a.assignee_id,
                               z.zone_code, z.zone_name,
                               i.fullname AS inspector_name, i.username AS inspector_username,
                               u.fullname AS assignee_name, u.username AS assignee_username,
                               COUNT(s.id) AS total_days,
                               SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) AS completed_days,
                               MIN(s.schedule_date) AS first_date, MAX(s.schedule_date) AS last_date
                        FROM five_s_assignments a
                        JOIN five_s_zones z ON z.id = a.zone_id
                        LEFT JOIN users i ON i.id = a.inspector_id
                        LEFT JOIN users u ON u.id = a.assignee_id
                        LEFT JOIN five_s_schedules s ON s.zone_id = a.zone_id
                            AND s.inspector_id = a.inspector_id
                            AND DATE_FORMAT(s.schedule_date, '%Y-%m') = a.month_year
                        WHERE a.month_year = ?
                        GROUP BY a.id, a.zone_id, a.month_year, a.inspector_id, a.assignee_id,
                                 z.zone_code, z.zone_name, i.fullname, i.username, u.fullname, u.username
                        ORDER BY a.zone_id ASC");
$stmt->bind_param('s', $month);
$stmt->execute();

echo json_encode([
    'success' => true,
    'month' => $month,
    'assignments' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
]);