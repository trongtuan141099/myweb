<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/db.php';

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Vui lòng chọn file nhập liệu hợp lệ!');
    }

    $tmpPath  = $_FILES['excel_file']['tmp_name'];
    $fileName = $_FILES['excel_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $rows = [];
    if ($ext === 'csv') {
        if (($handle = fopen($tmpPath, "r")) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    } else {
        $xlsxPath = __DIR__ . '/../vendor/SimpleXLSX.php';
        if (file_exists($xlsxPath)) {
            require_once $xlsxPath;
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($tmpPath)) {
                $rows = $xlsx->rows();
            } else {
                throw new Exception('Lỗi đọc file Excel: ' . \Shuchkin\SimpleXLSX::parseError());
            }
        } else {
            throw new Exception('Chưa cài đặt thư viện đọc Excel!');
        }
    }

    if (count($rows) <= 1) {
        throw new Exception('File rỗng hoặc không đúng định dạng!');
    }

    $conn->begin_transaction();
    $sql = "INSERT INTO material_viscosity_logs (lvf, received_date, category, part_code, lot_no, viscosity, yi_val, quantity, note) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $count = 0;
    for ($i = 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        $lvf          = trim((string)($r[0] ?? ''));
        $dateRaw      = trim((string)($r[1] ?? ''));
        $category     = trim((string)($r[2] ?? ''));
        $partCode     = trim((string)($r[3] ?? ''));
        $lotNo        = trim((string)($r[4] ?? ''));
        $viscosity    = trim((string)($r[5] ?? ''));
        $yiVal        = (float)($r[6] ?? 0);
        $qty          = (float)str_replace(',', '', (string)($r[7] ?? 0));
        $note         = trim((string)($r[8] ?? ''));

        if (empty($lvf) || empty($partCode)) continue;

        // Định dạng lại ngày (YYYY-MM-DD)
        $receivedDate = date('Y-m-d', strtotime(str_replace('/', '-', $dateRaw)));

        $stmt->bind_param("ssssssdds", $lvf, $receivedDate, $category, $partCode, $lotNo, $viscosity, $yiVal, $qty, $note);
        $stmt->execute();
        $count++;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Đã import thành công $count bản ghi!"]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>