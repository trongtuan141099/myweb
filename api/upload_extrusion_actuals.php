<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../vendor/autoload.php'; // Đã cài PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (!isset($_FILES['excel_file'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel báo cáo!']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false);

    if (count($rows) <= 1) {
        echo json_encode(['success' => false, 'message' => 'File Excel rỗng hoặc chỉ có dòng tiêu đề!']);
        exit;
    }

    function extractPipeSize($productCode) {
        $p = strtoupper(trim((string)$productCode));
        $sizes = ['TIUB13', 'TIUB11', 'TIUB07', 'TIUB05', 'TIUB01', 'TU16', 'TU12', 'TU10', 'TU08', 'TU06', 'TU04'];
        foreach ($sizes as $s) {
            if (strpos($p, $s) === 0) return $s;
        }
        return 'OTHER';
    }

    function parseExcelDate($val) {
        if (empty($val)) return date('Y-m-d');
        if (is_numeric($val)) {
            return date('Y-m-d', Date::excelToTimestamp($val));
        }
        return date('Y-m-d', strtotime($val));
    }

    $sqlInsertLog = "INSERT INTO extrusion_actual_logs (
        import_date, production_date, employee_code, employee_name, shift, 
        mfg_order_code, product_code, pipe_size, cost_center, process_name, 
        device_code, finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg, 
        total_weight_kg, material_code, regrind_count, regrind_package_code, lot_in, 
        total_downtime, total_runtime, cycle_time, machine_efficiency, mold_code, 
        spider_code, production_order_code, is_test, material_type, material_ng_qty, 
        lot_material_ng, hdpe_qty, lio_clean_qty, ti_clean_qty, bobbin_pl7_3_count, 
        bobbin_pl7_3_meters, bobbin_pl4_7_count, bobbin_pl4_7_meters, printer_type, ink_type, 
        waiting_machine_count
    ) VALUES (" . implode(',', array_fill(0, 41, '?')) . ")";

    $stmtLog = $conn->prepare($sqlInsertLog);
    $dailyTotals = [];

    foreach ($rows as $index => $row) {
        if ($index === 0 || empty($row[1])) continue; // Bỏ qua Header

        $importDate     = parseExcelDate($row[0]);
        $productionDate = parseExcelDate($row[1]);
        $employeeCode   = (string)($row[2] ?? '');
        $employeeName   = (string)($row[3] ?? '');
        $shift          = (string)($row[4] ?? '');
        $mfgOrderCode   = (string)($row[5] ?? '');
        $productCode    = (string)($row[6] ?? '');
        $pipeSize       = extractPipeSize($productCode);
        $costCenter     = (string)($row[7] ?? '');
        $processName    = (string)($row[8] ?? '');
        $deviceCode     = (string)($row[9] ?? '');
        $finishedQtyM   = (float)($row[10] ?? 0);
        $finishedQtyKg  = (float)($row[11] ?? 0);
        $ngQtyKg        = (float)($row[12] ?? 0);
        $hardWasteQtyKg = (float)($row[13] ?? 0);
        $totalWeightKg  = (float)($row[14] ?? 0);
        $materialCode   = (string)($row[15] ?? '');
        $regrindCount   = (int)($row[16] ?? 0);
        $regrindPkgCode = (string)($row[17] ?? '');
        $lotIn          = (string)($row[18] ?? '');
        $totalDowntime  = (float)($row[19] ?? 0);
        $totalRuntime   = (float)($row[20] ?? 0);
        $cycleTime      = (int)($row[21] ?? 0);
        $efficiency     = (float)($row[22] ?? 0);
        $moldCode       = (string)($row[23] ?? '');
        $spiderCode     = (string)($row[24] ?? '');
        $prodOrderCode  = (string)($row[25] ?? '');
        $isTest         = (int)($row[26] ?? 0);
        $materialType   = (string)($row[27] ?? '');
        $materialNgQty  = (float)($row[28] ?? 0);
        $lotMaterialNg  = (string)($row[29] ?? '');
        $hdpeQty        = (float)($row[30] ?? 0);
        $lioCleanQty    = (float)($row[31] ?? 0);
        $tiCleanQty     = (float)($row[32] ?? 0);
        $bobbinPl73Cnt  = (int)($row[33] ?? 0);
        $bobbinPl73M    = (float)($row[34] ?? 0);
        $bobbinPl47Cnt  = (int)($row[35] ?? 0);
        $bobbinPl47M    = (float)($row[36] ?? 0);
        $printerType    = (string)($row[37] ?? '');
        $inkType        = (string)($row[38] ?? '');
        $waitingMchCnt  = (int)($row[39] ?? 0);

        $stmtLog->bind_param("sssssssssssdddddsissddisssisddddididssi",
            $importDate, $productionDate, $employeeCode, $employeeName, $shift,
            $mfgOrderCode, $productCode, $pipeSize, $costCenter, $processName,
            $deviceCode, $finishedQtyM, $finishedQtyKg, $ngQtyKg, $hardWasteQtyKg,
            $totalWeightKg, $materialCode, $regrindCount, $regrindPkgCode, $lotIn,
            $totalDowntime, $totalRuntime, $cycleTime, $efficiency, $moldCode,
            $spiderCode, $prodOrderCode, $isTest, $materialType, $materialNgQty,
            $lotMaterialNg, $hdpeQty, $lioCleanQty, $tiCleanQty, $bobbinPl73Cnt,
            $bobbinPl73M, $bobbinPl47Cnt, $bobbinPl47M, $printerType, $inkType,
            $waitingMchCnt
        );
        $stmtLog->execute();

        // Cộng dồn sản lượng theo Ngày & Size ống
        $yearMonth = date('Y-m', strtotime($productionDate));
        $day       = (int)date('d', strtotime($productionDate));
        $dailyTotals[$yearMonth][$pipeSize][$day] = ($dailyTotals[$yearMonth][$pipeSize][$day] ?? 0) + $finishedQtyM;
    }

    // Cập nhật bảng tổng hợp production_actuals
    $stmtAct = $conn->prepare("INSERT INTO production_actuals (year_month, pipe_size, day, actual_qty) 
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE actual_qty = VALUES(actual_qty)");

    foreach ($dailyTotals as $ym => $sizeGroup) {
        foreach ($sizeGroup as $size => $days) {
            foreach ($days as $d => $qty) {
                $stmtAct->bind_param("ssid", $ym, $size, $d, $qty);
                $stmtAct->execute();
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Import trọn vẹn 40 trường dữ liệu thành công!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi Import: ' . $e->getMessage()]);
}
?>