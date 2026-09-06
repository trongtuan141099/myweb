<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$month = $data['month'] ?? '';
$matrix = $data['matrix'] ?? [];

if (!$month || empty($matrix)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO production_plans (year_month, pipe_size, day, plan_qty) 
    VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE plan_qty = VALUES(plan_qty)");

foreach ($matrix as $row) {
    $size = $row['pipe_size'];
    foreach ($row['days'] as $day => $qty) {
        $stmt->bind_param("ssid", $month, $size, $day, $qty);
        $stmt->execute();
    }
}

echo json_encode(['success' => true, 'message' => 'Lưu kế hoạch thành công!']);
?>