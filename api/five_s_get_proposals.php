<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập hệ thống']);
    exit;
}

$month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');

try {
    $sql = "SELECT p.*, u.fullname AS employee_name, u.username 
            FROM five_s_proposals p
            JOIN users u ON p.employee_id = u.id
            WHERE DATE_FORMAT(p.created_at, '%Y-%m') = ?
            ORDER BY p.id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $res = $stmt->get_result();

    $proposals = [];
    $kpi = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'paused' => 0,
        'completed' => 0,
        'canceled' => 0
    ];

    while ($row = $res->fetch_assoc()) {
        $proposals[] = $row;
        $kpi['total']++;
        if (isset($kpi[$row['status']])) {
            $kpi[$row['status']]++;
        }
    }

    echo json_encode([
        'success' => true,
        'kpi' => $kpi,
        'proposals' => $proposals
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}