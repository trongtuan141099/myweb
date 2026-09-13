<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

// Kiểm tra authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập hệ thống']);
    exit;
}

$month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');

try {
    // 1. Lấy danh sách khu vực 5S
    $zones = [];
    $resZones = $conn->query("SELECT id, zone_code, zone_name, layout_image FROM five_s_zones ORDER BY id ASC");
    while ($row = $resZones->fetch_assoc()) {
        $zones[] = $row;
    }

    // 2. Lấy danh sách lỗi vi phạm trong tháng
    $issues = [];
    $sqlIssues = "SELECT i.*, z.zone_name, z.layout_image,
                         u1.fullname AS inspector_name, 
                         u2.fullname AS assignee_name
                  FROM five_s_issues i
                  JOIN five_s_zones z ON i.zone_id = z.id
                  JOIN five_s_audits a ON i.audit_id = a.id
                  LEFT JOIN users u1 ON a.inspector_id = u1.id
                  LEFT JOIN users u2 ON i.assignee_id = u2.id
                  WHERE DATE_FORMAT(i.created_at, '%Y-%m') = ?
                  ORDER BY i.id DESC";

    $stmt = $conn->prepare($sqlIssues);
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $resIssues = $stmt->get_result();

    $total = 0;
    $resolved = 0;
    $pending = 0;
    $catCounts = ['S1' => 0, 'S2' => 0, 'S3' => 0, 'S4' => 0, 'S5' => 0];

    while ($row = $resIssues->fetch_assoc()) {
        $issues[] = $row;
        $total++;
        if ($row['status'] === 'resolved') {
            $resolved++;
        } else {
            $pending++;
        }
        if (isset($catCounts[$row['s_category']])) {
            $catCounts[$row['s_category']]++;
        }
    }

    $rate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

    // 3. Lấy danh sách nhân viên phục vụ phân công / gán lỗi
    $users = [];
    $resUsers = $conn->query("SELECT id, fullname, username FROM users ORDER BY fullname ASC");
    if ($resUsers) {
        while ($u = $resUsers->fetch_assoc()) {
            $users[] = $u;
        }
    }

    echo json_encode([
        'success' => true,
        'kpi' => [
            'total' => $total,
            'resolved' => $resolved,
            'pending' => $pending,
            'rate' => $rate
        ],
        'chart_data' => [
            'series' => array_values($catCounts)
        ],
        'zones' => $zones,
        'issues' => $issues,
        'users' => $users
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}