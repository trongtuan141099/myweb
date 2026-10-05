<?php
/**
 * API Xuất Dữ Liệu Tăng Ca Kế Hoạch Theo File Mẫu Chuẩn overtime.xlsx
 * DX Plastic Group - Overtime Management System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/shift_service.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền
requireApiPermission(['api.overtime.export', 'overtime.export', 'overtime.view', 'admin']);

$action = trim($_REQUEST['action'] ?? 'export');

// Nhận tham số bộ lọc - chấp nhận linh hoạt các tên tham số: start_date, end_date, date_from, date_to, date_specific
$startDate = trim($_REQUEST['start_date'] ?? $_REQUEST['date_from'] ?? $_REQUEST['from_date'] ?? '');
$endDate = trim($_REQUEST['end_date'] ?? $_REQUEST['date_to'] ?? $_REQUEST['to_date'] ?? '');
$dateSpecific = trim($_REQUEST['date_specific'] ?? $_REQUEST['ot_date'] ?? $_REQUEST['specific_date'] ?? '');
$costCenter = trim($_REQUEST['cost_center'] ?? '');
$teamName = trim($_REQUEST['team_name'] ?? '');
$factory = trim($_REQUEST['factory'] ?? 'SMC2');
$empCode = trim($_REQUEST['employee_code'] ?? '');
$fullName = trim($_REQUEST['full_name'] ?? '');
$year = !empty($_REQUEST['year']) ? intval($_REQUEST['year']) : 0;
$month = !empty($_REQUEST['month']) ? intval($_REQUEST['month']) : 0;
$format = strtolower(trim($_REQUEST['format'] ?? 'xlsx'));

if (!function_exists('normalizeDateStr')) {
    function normalizeDateStr($d) {
        $d = trim((string)$d);
        if (empty($d)) return '';
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $d, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $d, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        $ts = strtotime($d);
        return $ts ? date('Y-m-d', $ts) : $d;
    }
}

$startDate = normalizeDateStr($startDate);
$endDate = normalizeDateStr($endDate);
$dateSpecific = normalizeDateStr($dateSpecific);

if (!empty($dateSpecific)) {
    $startDate = $dateSpecific;
    $endDate = $dateSpecific;
}

// Xử lý giá trị mặc định khi người dùng không chọn ngày
if (empty($startDate) && empty($endDate)) {
    if ($year > 0 && $month > 0) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
    } else if ($year > 0) {
        $startDate = sprintf('%04d-01-01', $year);
        $endDate = sprintf('%04d-12-31', $year);
    } else {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
    }
} else if (!empty($startDate) && empty($endDate)) {
    $endDate = $startDate;
} else if (empty($startDate) && !empty($endDate)) {
    $startDate = $endDate;
}

// Đảm bảo thứ tự ngày bắt đầu <= ngày kết thúc
if (!empty($startDate) && !empty($endDate) && $startDate > $endDate) {
    $tmp = $startDate;
    $startDate = $endDate;
    $endDate = $tmp;
}

// Đồng bộ biến để tương thích ngược
$dateFrom = $startDate;
$dateTo = $endDate;

// Xây dựng điều kiện WHERE
$where = ["p.approval_status IN ('Chấp Nhận', 'Đã duyệt', 'Approved')"];
$params = [];
$types = '';

if (!empty($startDate) && !empty($endDate)) {
    if ($startDate === $endDate) {
        $where[] = "p.ot_date = ?";
        $params[] = $startDate;
        $types .= 's';
    } else {
        $where[] = "p.ot_date >= ?";
        $params[] = $startDate;
        $types .= 's';

        $where[] = "p.ot_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
}

if (!empty($costCenter)) {
    $where[] = "(e.cost_center = ? OR p.group_name = ?)";
    $params[] = $costCenter;
    $params[] = $costCenter;
    $types .= 'ss';
}

if (!empty($teamName)) {
    $where[] = "(p.team_name = ? OR p.group_name = ?)";
    $params[] = $teamName;
    $params[] = $teamName;
    $types .= 'ss';
}

if (!empty($empCode)) {
    $where[] = "p.employee_code LIKE ?";
    $params[] = '%' . $empCode . '%';
    $types .= 's';
}

if (!empty($fullName)) {
    $where[] = "(p.full_name LIKE ? OR e.full_name LIKE ?)";
    $params[] = '%' . $fullName . '%';
    $params[] = '%' . $fullName . '%';
    $types .= 'ss';
}

$whereSql = implode(' AND ', $where);

// =========================================================================
// 1. ACTION: PREVIEW (Đếm số lượng bản ghi trước khi xuất)
// =========================================================================
if ($action === 'preview') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    $countSql = "
        SELECT COUNT(*) as total
        FROM ot_plans p
        LEFT JOIN employees e ON p.employee_code = e.employee_code
        WHERE {$whereSql}
    ";
    
    $stmt = $conn->prepare($countSql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => $conn->error], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total = intval($res['total'] ?? 0);
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => $total,
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date_from' => $startDate,
            'date_to' => $endDate,
            'date_specific' => $dateSpecific,
            'cost_center' => $costCenter,
            'team_name' => $teamName,
            'factory' => $factory,
            'employee_code' => $empCode,
            'full_name' => $fullName
        ],
        'message' => $total > 0 
            ? "Tìm thấy {$total} bản ghi tăng ca kế hoạch đã được phê duyệt." 
            : "Không có bản ghi tăng ca kế hoạch nào khớp với bộ lọc."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================================
// 2. ACTION: EXPORT (Xuất file Excel theo chuẩn mẫu overtime.xlsx)
// =========================================================================
try {
    $selectSql = "
        SELECT 
            p.id,
            p.employee_code,
            COALESCE(e.full_name, p.full_name) as full_name,
            p.team_name,
            p.group_name,
            p.ot_date,
            p.start_time,
            p.end_time,
            p.reason,
            COALESCE(e.cost_center, 'A00330') as cost_center,
            COALESCE(e.use_shuttle_bus, 0) as use_shuttle_bus,
            COALESCE(e.work_group, '') as work_group,
            COALESCE(e.job_level, '') as job_level
        FROM ot_plans p
        LEFT JOIN employees e ON p.employee_code = e.employee_code
        WHERE {$whereSql}
        ORDER BY p.ot_date ASC, p.employee_code ASC, p.start_time ASC
    ";

    $stmt = $conn->prepare($selectSql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    $rangeStr = !empty($dateSpecific) 
        ? date('Ymd', strtotime($dateSpecific)) 
        : (($startDate === $endDate) ? date('Ymd', strtotime($startDate)) : (date('Ymd', strtotime($startDate)) . '_' . date('Ymd', strtotime($endDate))));

    // Nếu người dùng chọn định dạng CSV
    if ($format === 'csv') {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="DanhSachTangCaKeHoach_' . $factory . '_' . $rangeStr . '.csv"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['STT', 'Mã Nhân Viên', 'Họ và Tên', 'Nhà Máy', 'Cost Center', 'Ca', 'Lý Do', 'Ngày OT', 'Từ Giờ', 'Đến Giờ', 'Xe Đưa Rước', 'Cần Điện Khí', 'Ghi Chú']);
        $stt = 1;
        foreach ($rows as $data) {
            // Tự động xác định Ca làm việc từ thời gian đăng ký OT kế hoạch & bảng Shift_Master
            $shiftVal = ShiftMasterService::determineShift(
                $data['start_time'],
                $data['end_time'],
                [
                    'work_group' => $data['work_group'] ?? '',
                    'job_level' => $data['job_level'] ?? '',
                    'reason' => $data['reason'] ?? '',
                    'cost_center' => $data['cost_center'] ?? ''
                ],
                $conn
            );
            $shuttleText = (!empty($data['use_shuttle_bus']) && $data['use_shuttle_bus'] == 1)
                ? 'Có sử dụng xe đưa rước' : 'Không sử dụng xe đưa rước';
            fputcsv($out, [
                $stt++,
                $data['employee_code'],
                $data['full_name'],
                !empty($factory) ? $factory : 'SMC2',
                !empty($data['cost_center']) ? $data['cost_center'] : 'A00330',
                $shiftVal,
                '',
                $data['ot_date'],
                date('H:i:s', strtotime($data['start_time'])),
                date('H:i:s', strtotime($data['end_time'])),
                $shuttleText,
                'SMC2 - B2 - F1',
                $data['reason']
            ]);
        }
        exit;
    }

    $templatePath = __DIR__ . '/../Data/overtime.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception("Không tìm thấy file mẫu: Data/overtime.xlsx");
    }

    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getSheetByName('ListOT');
    if (!$sheet) {
        $sheet = $spreadsheet->getActiveSheet();
    }

    // Định nghĩa các bộ DataValidation dropdown theo file mẫu
    $dvReason = new DataValidation();
    $dvReason->setType(DataValidation::TYPE_LIST);
    $dvReason->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $dvReason->setAllowBlank(true);
    $dvReason->setShowDropDown(true);
    $dvReason->setFormula1('option!$A$1:$A$7');

    $dvFactory = new DataValidation();
    $dvFactory->setType(DataValidation::TYPE_LIST);
    $dvFactory->setAllowBlank(true);
    $dvFactory->setShowDropDown(true);
    $dvFactory->setFormula1('option!$B$1:$B$4');

    $dvShift = new DataValidation();
    $dvShift->setType(DataValidation::TYPE_LIST);
    $dvShift->setAllowBlank(true);
    $dvShift->setShowDropDown(true);
    $dvShift->setFormula1('option!$C$1:$C$4');

    $dvBus = new DataValidation();
    $dvBus->setType(DataValidation::TYPE_LIST);
    $dvBus->setAllowBlank(true);
    $dvBus->setShowDropDown(true);
    $dvBus->setFormula1('option!$D$1:$D$2');

    // Xóa dữ liệu mẫu ban đầu từ dòng 2 đến dòng 4
    for ($r = 2; $r <= 4; $r++) {
        for ($c = 'B'; $c <= 'N'; $c++) {
            $sheet->setCellValue($c . $r, null);
        }
    }

    $rowIndex = 2;
    $stt = 1;
    $row2Style = $sheet->getStyle('B2:N2');

    foreach ($rows as $data) {
        if ($rowIndex > 87) {
            $sheet->duplicateStyle($row2Style, "B{$rowIndex}:N{$rowIndex}");
        }

        // Col B: No. (STT)
        $sheet->setCellValue('B' . $rowIndex, $stt);
        $sheet->getStyle('B' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);

        // Col C: msnv (Mã nhân viên, dạng text)
        $sheet->setCellValueExplicit('C' . $rowIndex, (string)$data['employee_code'], DataType::TYPE_STRING);
        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col D: họ tên (Họ và tên, dạng text)
        $sheet->setCellValueExplicit('D' . $rowIndex, (string)$data['full_name'], DataType::TYPE_STRING);
        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col E: nhà máy (Mặc định SMC2)
        $factVal = !empty($factory) ? $factory : 'SMC2';
        $sheet->setCellValueExplicit('E' . $rowIndex, $factVal, DataType::TYPE_STRING);
        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode('@');
        $sheet->getCell('E' . $rowIndex)->setDataValidation(clone $dvFactory);

        // Col F: costcenter (Từ hồ sơ nhân viên)
        $ccVal = !empty($data['cost_center']) ? $data['cost_center'] : 'A00330';
        $sheet->setCellValueExplicit('F' . $rowIndex, (string)$ccVal, DataType::TYPE_STRING);
        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col G: ca làm việc (1, 2, 3, HC) - Tự động xác định từ thời gian đăng ký OT kế hoạch & bảng Shift_Master
        $shiftVal = ShiftMasterService::determineShift(
            $data['start_time'],
            $data['end_time'],
            [
                'work_group' => $data['work_group'] ?? '',
                'job_level' => $data['job_level'] ?? '',
                'reason' => $data['reason'] ?? '',
                'cost_center' => $data['cost_center'] ?? ''
            ],
            $conn
        );
        $sheet->setCellValue('G' . $rowIndex, $shiftVal);
        $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode('@');
        $sheet->getCell('G' . $rowIndex)->setDataValidation(clone $dvShift);

        // Col H: lý do (Để trống ô dữ liệu nhưng giữ nguyên dropdown validation option!$A$1:$A$7)
        $sheet->setCellValue('H' . $rowIndex, '');
        $sheet->getCell('H' . $rowIndex)->setDataValidation(clone $dvReason);

        // Col I: ngày ot (m/d/yyyy)
        $otDateOnly = substr((string)$data['ot_date'], 0, 10);
        $parts = explode('-', $otDateOnly);
        if (count($parts) === 3) {
            $excelDate = Date::formattedPHPToExcel((int)$parts[0], (int)$parts[1], (int)$parts[2], 0, 0, 0);
            $sheet->setCellValue('I' . $rowIndex, $excelDate);
            $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode('m/d/yyyy');
        }

        // Col J: từ giờ ([$-F400]h:mm:ss\ AM/PM)
        $startTime = strtotime($data['start_time']);
        if ($startTime) {
            $h = intval(date('H', $startTime));
            $m = intval(date('i', $startTime));
            $s = intval(date('s', $startTime));
            $sheet->setCellValue('J' . $rowIndex, ($h * 3600 + $m * 60 + $s) / 86400);
            $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode('[$-F400]h:mm:ss\\ AM/PM');
        }

        // Col K: đến giờ ([$-F400]h:mm:ss\ AM/PM)
        $endTime = strtotime($data['end_time']);
        if ($endTime) {
            $h = intval(date('H', $endTime));
            $m = intval(date('i', $endTime));
            $s = intval(date('s', $endTime));
            $sheet->setCellValue('K' . $rowIndex, ($h * 3600 + $m * 60 + $s) / 86400);
            $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode('[$-F400]h:mm:ss\\ AM/PM');
        }

        // Col L: sắp xe ("Có sử dụng xe đưa rước" hoặc "Không sử dụng xe đưa rước", kèm dropdown)
        $shuttleText = (!empty($data['use_shuttle_bus']) && $data['use_shuttle_bus'] == 1)
            ? 'Có sử dụng xe đưa rước'
            : 'Không sử dụng xe đưa rước';
        $sheet->setCellValueExplicit('L' . $rowIndex, $shuttleText, DataType::TYPE_STRING);
        $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode('@');
        $sheet->getCell('L' . $rowIndex)->setDataValidation(clone $dvBus);

        // Col M: cần điện -khí (Mặc định SMC2 - B2 - F1)
        $sheet->setCellValueExplicit('M' . $rowIndex, 'SMC2 - B2 - F1', DataType::TYPE_STRING);
        $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col N: Ghi chú (Lấy từ lý do tăng ca kế hoạch)
        $note = trim((string)$data['reason']);
        $sheet->setCellValue('N' . $rowIndex, $note);
        $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);

        $sheet->getRowDimension($rowIndex)->setRowHeight(20);

        $rowIndex++;
        $stt++;
    }

    // Tên file xuất
    $rangeStr = !empty($dateSpecific) 
        ? date('Ymd', strtotime($dateSpecific)) 
        : (date('Ymd', strtotime($dateFrom)) . '_' . date('Ymd', strtotime($dateTo)));
    $outFileName = "DanhSachTangCaKeHoach_{$factory}_{$rangeStr}.xlsx";

    // Xóa bộ đệm đầu ra để tránh corrupt file zip/xlsx
    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $outFileName . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi xuất file Excel: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
