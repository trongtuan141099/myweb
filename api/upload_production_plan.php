<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/db.php';
    
    $planMonth = $_POST['plan_month'] ?? '';
    if (empty($planMonth)) {
        throw new Exception('Vui lòng chọn tháng cần áp dụng kế hoạch!');
    }

    if (!isset($_FILES['plan_excel']) || $_FILES['plan_excel']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Vui lòng chọn file Kế hoạch hợp lệ!');
    }

    $tmpPath = $_FILES['plan_excel']['tmp_name'];
    $fileName = $_FILES['plan_excel']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $rows = [];

    // Xử lý đọc file CSV
    if ($ext === 'csv') {
        if (($handle = fopen($tmpPath, "r")) !== FALSE) {
            // Đọc và bỏ qua BOM UTF-8 nếu có
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    } else {
        // Xử lý đọc file Excel XLSX bằng SimpleXLSX
        $xlsxPath = __DIR__ . '/../vendor/SimpleXLSX.php';
        if (file_exists($xlsxPath)) {
            require_once $xlsxPath;
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($tmpPath)) {
                $rows = $xlsx->rows();
            } else {
                throw new Exception('Lỗi đọc file Excel: ' . \Shuchkin\SimpleXLSX::parseError());
            }
        } else {
            throw new Exception('Không tìm thấy thư viện SimpleXLSX!');
        }
    }

    if (count($rows) <= 1) {
        throw new Exception('File rỗng hoặc không có dữ liệu kế hoạch!');
    }

    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO `production_plans` (`year_month`, `pipe_size`, `day`, `plan_qty`) 
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `plan_qty` = VALUES(`plan_qty`)");

    if (!$stmt) {
        throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
    }

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $pipeSize = strtoupper(trim((string)($row[0] ?? '')));
        if (empty($pipeSize)) continue;

        for ($d = 1; $d <= 31; $d++) {
            $qty = (float)($row[$d] ?? 0);
            $stmt->bind_param("ssid", $planMonth, $pipeSize, $d, $qty);
            $stmt->execute();
        }
    }

    $conn->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => "Upload thành công kế hoạch tháng $planMonth!"]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>