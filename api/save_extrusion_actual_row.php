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

    // Đọc dữ liệu JSON gửi lên từ JavaScript
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $id               = (int)($data['id'] ?? 0);
    $productionDate   = $data['production_date'] ?? '';
    $employeeCode     = trim($data['employee_code'] ?? '');
    $employeeName     = trim($data['employee_name'] ?? '');
    $shift            = trim($data['shift'] ?? '');
    $mfgOrderCode     = trim($data['mfg_order_code'] ?? '');
    $productCode      = trim($data['product_code'] ?? '');
    $deviceCode       = trim($data['device_code'] ?? '');
    $finishedQtyM     = (float)($data['finished_qty_m'] ?? 0);
    $finishedQtyKg    = (float)($data['finished_qty_kg'] ?? 0);
    $ngQtyKg          = (float)($data['ng_qty_kg'] ?? 0);
    $totalDowntime    = (float)($data['total_downtime'] ?? 0);
    $machineEfficiency = (float)($data['machine_efficiency'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID bản ghi không hợp lệ!');
    }

    if (empty($productionDate)) {
        throw new Exception('Ngày sản xuất không được để rỗng!');
    }

    // Hàm tự động trích xuất Pipe Size từ Product Code
    function extractPipeSize($productCode) {
        $p = strtoupper(trim((string)$productCode));
        $sizes = ['TIUB13', 'TIUB11', 'TIUB07', 'TIUB05', 'TIUB01', 'TU16', 'TU12', 'TU10', 'TU08', 'TU06', 'TU04'];
        foreach ($sizes as $s) {
            if (strpos($p, $s) === 0) return $s;
        }
        return 'OTHER';
    }

    $pipeSize = extractPipeSize($productCode);

    $conn->begin_transaction();

    // 1. Cập nhật bản ghi chi tiết trong extrusion_actual_logs
    $sqlLog = "UPDATE extrusion_actual_logs SET 
                production_date = ?, 
                employee_code = ?, 
                employee_name = ?, 
                shift = ?, 
                mfg_order_code = ?, 
                product_code = ?, 
                pipe_size = ?, 
                device_code = ?, 
                finished_qty_m = ?, 
                finished_qty_kg = ?, 
                ng_qty_kg = ?, 
                total_downtime = ?, 
                machine_efficiency = ? 
               WHERE id = ?";

    $stmtLog = $conn->prepare($sqlLog);
    if (!$stmtLog) {
        throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
    }

    $stmtLog->bind_param("ssssssssdddddi", 
        $productionDate, 
        $employeeCode, 
        $employeeName, 
        $shift, 
        $mfgOrderCode, 
        $productCode, 
        $pipeSize, 
        $deviceCode, 
        $finishedQtyM, 
        $finishedQtyKg, 
        $ngQtyKg, 
        $totalDowntime, 
        $machineEfficiency, 
        $id
    );

    if (!$stmtLog->execute()) {
        throw new Exception('Lỗi khi cập nhật dữ liệu: ' . $stmtLog->error);
    }

    // 2. Tính toán lại tổng sản lượng theo Ngày & Size cho bảng tổng hợp production_actuals
    $yearMonth = date('Y-m', strtotime($productionDate));
    $day       = (int)date('d', strtotime($productionDate));

    $sqlRecalc = "SELECT SUM(finished_qty_m) as total_m 
                  FROM extrusion_actual_logs 
                  WHERE DATE_FORMAT(production_date, '%Y-%m') = ? 
                    AND TRIM(UPPER(pipe_size)) = ? 
                    AND DAY(production_date) = ?";

    $stmtRecalc = $conn->prepare($sqlRecalc);
    $stmtRecalc->bind_param("ssi", $yearMonth, $pipeSize, $day);
    $stmtRecalc->execute();
    $resRecalc = $stmtRecalc->get_result()->fetch_assoc();
    $newTotalM = (float)($resRecalc['total_m'] ?? 0);

    // Cập nhật lại sản lượng thực tích tích lũy
    $sqlUpdAct = "INSERT INTO production_actuals (year_month, pipe_size, day, actual_qty) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE actual_qty = VALUES(actual_qty)";
    $stmtUpdAct = $conn->prepare($sqlUpdAct);
    $stmtUpdAct->bind_param("ssid", $yearMonth, $pipeSize, $day, $newTotalM);
    $stmtUpdAct->execute();

    $conn->commit();

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Cập nhật thành công bản ghi ID: $id!"
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