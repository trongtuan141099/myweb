<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    $configPath = __DIR__ . '/../config/db.php';
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file config/db.php!');
    }
    require_once $configPath;

    if (!$conn) {
        throw new Exception('Kết nối CSDL thất bại!');
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $month = $data['month'] ?? '';
    $matrix = $data['matrix'] ?? [];

    if (empty($month) || empty($matrix)) {
        throw new Exception('Tháng hoặc dữ liệu ma trận không được để rỗng!');
    }

    $conn->begin_transaction();

    // Chuẩn bị câu lệnh UPSERT
    // $stmt = $conn->prepare("INSERT INTO production_plans (year_month, pipe_size, day, plan_qty) 
    //     VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE plan_qty = VALUES(plan_qty)");

    $stmt = $conn->prepare("INSERT INTO `production_plans` (`year_month`, `pipe_size`, `day`, `plan_qty`) 
    VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `plan_qty` = VALUES(`plan_qty`)");

    if (!$stmt) {
        throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
    }

    foreach ($matrix as $row) {
        $size = $row['pipe_size'] ?? '';
        if (empty($size)) continue;

        if (isset($row['days']) && is_array($row['days'])) {
            foreach ($row['days'] as $day => $qty) {
                $d = (int)$day;
                $q = (float)$qty;
                $stmt->bind_param("ssid", $month, $size, $d, $q);
                $stmt->execute();
            }
        }
    }

    $conn->commit();
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Đã lưu thành công kế hoạch cho tháng $month!"
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi Server: ' . $e->getMessage()
    ]);
}
?>