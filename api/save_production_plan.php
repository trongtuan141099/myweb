<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => 'Lỗi không xác định khi lưu kế hoạch!'
];

try {
    $configPath = __DIR__ . '/../config/db.php';
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file config/db.php!');
    }
    require_once $configPath;
    require_once __DIR__ . '/../core/check_permission.php';
    require_once __DIR__ . '/../core/extrusion_service.php';
    
    // Kiểm tra phân quyền API
    requireApiPermission(['production.plan', 'api.production.plan_save']);

    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new Exception('Kết nối CSDL thất bại!');
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $month = trim($data['month'] ?? '');
    $matrix = $data['matrix'] ?? [];

    if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        throw new Exception('Vui lòng chọn tháng hợp lệ (YYYY-MM)!');
    }

    if (empty($matrix) || !is_array($matrix)) {
        throw new Exception('Dữ liệu ma trận kế hoạch không được để rỗng!');
    }

    $inTransaction = false;
    $conn->begin_transaction();
    $inTransaction = true;

    // Chuẩn bị câu lệnh UPSERT
    $stmt = $conn->prepare("INSERT INTO `production_plans` (`year_month`, `pipe_size`, `day`, `plan_qty`) 
    VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `plan_qty` = VALUES(`plan_qty`)");

    if (!$stmt) {
        throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
    }

    foreach ($matrix as $row) {
        $rawSize = trim($row['pipe_size'] ?? '');
        $size = normalizePlanPipeSize($rawSize);
        if (empty($size)) continue;

        if (isset($row['days']) && is_array($row['days'])) {
            foreach ($row['days'] as $day => $qty) {
                $d = (int)$day;
                if ($d < 1 || $d > 31) continue;
                $q = (float)$qty;
                $stmt->bind_param("ssid", $month, $size, $d, $q);
                $stmt->execute();
            }
        }
    }

    $stmt->close();
    $conn->commit();
    $inTransaction = false;

    $response = [
        'success' => true,
        'message' => "Đã lưu thành công kế hoạch cho tháng $month!"
    ];

} catch (Throwable $e) {
    if (isset($conn) && !empty($inTransaction)) {
        try {
            $conn->rollback();
        } catch (Throwable $rbErr) {
            // Bỏ qua lỗi rollback
        }
    }
    $response = [
        'success' => false,
        'message' => 'Lỗi Server: ' . $e->getMessage()
    ];
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;