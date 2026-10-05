<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => 'Lỗi không xác định khi tải file kế hoạch!'
];

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';
    require_once __DIR__ . '/../core/extrusion_service.php';
    
    // Kiểm tra phân quyền truy cập API
    requireApiPermission(['production.plan', 'api.production.plan_upload']);

    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new Exception('Không thể kết nối cơ sở dữ liệu!');
    }

    $planMonth = trim($_POST['plan_month'] ?? '');
    if (empty($planMonth) || !preg_match('/^\d{4}-\d{2}$/', $planMonth)) {
        throw new Exception('Vui lòng chọn tháng cần áp dụng kế hoạch hợp lệ (định dạng YYYY-MM)!');
    }

    // Kiểm tra dung lượng request vượt quá post_max_size
    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        throw new Exception('Dung lượng file vượt quá giới hạn cho phép của máy chủ (post_max_size)!');
    }

    // Kiểm tra file upload
    if (!isset($_FILES['plan_excel']) || !is_array($_FILES['plan_excel'])) {
        throw new Exception('Vui lòng chọn file Kế hoạch cần upload!');
    }

    $fileError = $_FILES['plan_excel']['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
        switch ($fileError) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Dung lượng file vượt quá giới hạn cho phép của hệ thống!');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File chỉ được tải lên một phần. Vui lòng thử lại!');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Chưa có file nào được chọn để tải lên!');
            default:
                throw new Exception("Lỗi tải file lên máy chủ (Mã lỗi: $fileError)!");
        }
    }

    $tmpPath = $_FILES['plan_excel']['tmp_name'];
    $fileName = $_FILES['plan_excel']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
        throw new Exception("Định dạng file '.$ext' không được hỗ trợ! Vui lòng chọn file .xlsx, .xls hoặc .csv.");
    }

    $rows = [];

    // 1. Đọc file CSV
    if ($ext === 'csv') {
        if (($handle = fopen($tmpPath, "r")) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            while (($data = fgetcsv($handle, 2000, ",")) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        } else {
            throw new Exception('Không thể mở file CSV tạm trên máy chủ!');
        }
    } elseif ($ext === 'xlsx') {
        // 2. Đọc file XLSX bằng SimpleXLSX (ưu tiên nhanh nhẹn)
        $parsed = false;
        $xlsxPath = __DIR__ . '/../vendor/SimpleXLSX.php';
        if (file_exists($xlsxPath)) {
            require_once $xlsxPath;
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($tmpPath)) {
                $rows = $xlsx->rows();
                $parsed = true;
            }
        }

        // Dự phòng bằng PhpSpreadsheet nếu SimpleXLSX không đọc được
        if (!$parsed) {
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray(null, true, true, false);
                    $parsed = true;
                }
            }
        }

        if (!$parsed) {
            $err = class_exists('\Shuchkin\SimpleXLSX') ? \Shuchkin\SimpleXLSX::parseError() : 'Không thể khởi tạo bộ đọc Excel';
            throw new Exception('Lỗi đọc file Excel XLSX: ' . ($err ?: 'Định dạng file không hợp lệ!'));
        }
    } elseif ($ext === 'xls') {
        // 3. Đọc file XLS (Excel 97-2003) qua PhpSpreadsheet
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray(null, true, true, false);
            } else {
                throw new Exception('Hệ thống thiếu thư viện đọc file định dạng .XLS!');
            }
        } else {
            throw new Exception('Không tìm thấy trình nạp thư viện đọc file .XLS!');
        }
    }

    if (empty($rows) || count($rows) <= 1) {
        throw new Exception('File rỗng hoặc không có dữ liệu kế hoạch!');
    }

    // Phân tích dòng tiêu đề (Header row) để ánh xạ cột ngày
    $headerRow = $rows[0];
    $dayColMap = [];
    foreach ($headerRow as $colIdx => $colVal) {
        if ($colIdx === 0) continue;
        $cleanCol = trim((string)$colVal);
        if (preg_match('/^(\d{1,2})$/', $cleanCol, $m)) {
            $dayNum = (int)$m[1];
            if ($dayNum >= 1 && $dayNum <= 31) {
                $dayColMap[$dayNum] = $colIdx;
            }
        }
    }

    $inTransaction = false;
    $conn->begin_transaction();
    $inTransaction = true;

    $stmt = $conn->prepare("INSERT INTO `production_plans` (`year_month`, `pipe_size`, `day`, `plan_qty`) 
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `plan_qty` = VALUES(`plan_qty`)");

    if (!$stmt) {
        throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
    }

    $processedSizes = 0;
    $totalRecords = 0;
    $skipHeaders = ['SIZE', 'MÃ SIZE', 'MA SIZE', 'QUY CÁCH', 'QUY CACH', 'KÍCH THƯỚC', 'KICH THUOC', 'PIPE SIZE', 'PIPESIZE'];

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty($row)) continue;

        $rawSize = strtoupper(trim((string)($row[0] ?? '')));
        if ($rawSize === '' || in_array($rawSize, $skipHeaders, true)) {
            continue;
        }

        $pipeSize = normalizePlanPipeSize($rawSize);
        if (empty($pipeSize) || in_array($pipeSize, $skipHeaders, true)) {
            continue;
        }

        $hasDataInRow = false;
        for ($d = 1; $d <= 31; $d++) {
            $colIdx = $dayColMap[$d] ?? $d;
            $rawVal = trim((string)($row[$colIdx] ?? '0'));
            
            if ($rawVal === '' || $rawVal === '-') {
                $qty = 0.0;
            } else {
                if (strpos($rawVal, ',') !== false && strpos($rawVal, '.') !== false) {
                    $rawVal = str_replace(',', '', $rawVal);
                } elseif (strpos($rawVal, ',') !== false) {
                    $rawVal = str_replace(',', '.', $rawVal);
                }
                $qty = (float)$rawVal;
            }

            $stmt->bind_param("ssid", $planMonth, $pipeSize, $d, $qty);
            $stmt->execute();
            $totalRecords++;
            $hasDataInRow = true;
        }

        if ($hasDataInRow) {
            $processedSizes++;
        }
    }

    $stmt->close();
    $conn->commit();
    $inTransaction = false;

    if ($processedSizes === 0) {
        throw new Exception('Không tìm thấy dòng dữ liệu quy cách hợp lệ trong file!');
    }

    $response = [
        'success'       => true,
        'message'       => "Upload thành công kế hoạch tháng $planMonth! (Đã cập nhật $processedSizes quy cách ống)",
        'month'         => $planMonth,
        'total_sizes'   => $processedSizes,
        'total_records' => $totalRecords
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
        'message' => $e->getMessage()
    ];
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;