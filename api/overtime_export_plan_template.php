<?php
/**
 * API Xuất Dữ Liệu Tăng Ca Kế Hoạch Theo File Mẫu Chuẩn overtime.xlsx & Xuất CSV Tùy Chỉnh Chuẩn Công Nghiệp
 * DX Plastic Group - Overtime Management System
 */

// Cấu hình tăng cường hiệu năng & chống timeout khi xuất file lớn
@set_time_limit(300);
@ini_set('memory_limit', '512M');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/shift_service.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền truy cập
requireApiPermission(['api.overtime.export', 'overtime.export', 'overtime.view', 'admin']);

$action = trim($_REQUEST['action'] ?? 'export');

// Nhận tham số bộ lọc thời gian
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

// Các tham số cấu hình tùy chỉnh cho mẫu chuẩn công nghiệp (CSV / OT)
$otPosition = trim($_REQUEST['ot_position'] ?? 'B021');
if (empty($otPosition)) $otPosition = 'B021';

$destCostCenter = trim($_REQUEST['destination_cost_center'] ?? $_REQUEST['dest_cost_center'] ?? '');
$changeAttendancePos = trim($_REQUEST['change_attendance_pos'] ?? 'Không');
$mealRegistration = trim($_REQUEST['meal_registration'] ?? 'auto_rice');
$busRequest = trim($_REQUEST['bus_request'] ?? 'auto');
$chillerRequest = trim($_REQUEST['chiller_request'] ?? 'Không');
$generatorRequest = trim($_REQUEST['generator_request'] ?? 'Không');
$customNote = trim($_REQUEST['custom_note'] ?? '');

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
        $startDate = date('Y-m-d');
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
$where = ["p.approval_status IN ('Chấp Nhận', 'Đã duyệt', 'Approved', 'Chưa duyệt', 'Chờ cấp trên gián tiếp')"];
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
            'full_name' => $fullName,
            'ot_position' => $otPosition
        ],
        'message' => $total > 0 
            ? "Tìm thấy {$total} bản ghi tăng ca kế hoạch đã được phê duyệt." 
            : "Không có bản ghi tăng ca kế hoạch nào khớp với bộ lọc."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================================
// 2. ACTION: EXPORT (Xuất file Excel .xlsx hoặc CSV theo chuẩn)
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
            p.total_minutes,
            p.total_hours,
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

    // ---------------------------------------------------------------------
    // ĐỊNH DẠNG CSV CHUẨN CÔNG NGHIỆP (16 CỘT TIÊU CHUẨN)
    // ---------------------------------------------------------------------
    if ($format === 'csv') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $csvFileName = "DanhSachTangCaKeHoach_{$factory}_{$rangeStr}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $csvFileName . '"; filename*=UTF-8\'\'' . rawurlencode($csvFileName));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Thêm UTF-8 BOM để Excel hiển thị tiếng Việt chính xác 100%
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        // Tiêu đề 16 cột theo chuẩn công nghiệp
        fputcsv($out, [
            'STT',
            'Mã số nhân viên',
            'Họ và tên',
            'Costcenter - NƠI ĐẾN',
            'Ngày OT',
            'Từ giờ (A)',
            'Đến giờ (B)',
            'Lý do tăng ca',
            'Ca',
            'Vị trí OT',
            'Đổi vị trí chấm công',
            'Đăng ký phần ăn',
            'Yêu cầu xe',
            'Yêu cầu chiller',
            'Yêu cầu máy phát',
            'Note'
        ]);

        $stt = 1;
        foreach ($rows as $data) {
            // 1. Mã số nhân viên: chuỗi 8 con số bắt đầu bằng số 0
            $rawEmpCode = trim((string)$data['employee_code']);
            if (preg_match('/^\d+$/', $rawEmpCode)) {
                $fmtEmpCode = str_pad($rawEmpCode, 8, '0', STR_PAD_LEFT);
            } else {
                $fmtEmpCode = $rawEmpCode;
            }

            // 2. Costcenter Nơi Đến
            $costCenterDest = !empty($destCostCenter) 
                ? $destCostCenter 
                : (!empty($data['cost_center']) ? $data['cost_center'] : 'A00330');

            // 3. Ca làm việc (1, 2, 3 hoặc HC)
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

            // 4. Số giờ OT để xét phần ăn
            $otHours = 0.0;
            if (isset($data['total_hours']) && is_numeric($data['total_hours']) && floatval($data['total_hours']) > 0) {
                $otHours = floatval($data['total_hours']);
            } else if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $otHours = round((strtotime($data['end_time']) - strtotime($data['start_time'])) / 3600, 2);
            }

            // Đăng ký phần ăn (Đăng ký Cơm / Bánh sữa nếu giờ OT >= 2)
            $mealVal = 'Không';
            if ($mealRegistration === 'auto_rice') {
                $mealVal = ($otHours >= 2.0) ? 'Cơm' : 'Không';
            } else if ($mealRegistration === 'auto_bread') {
                $mealVal = ($otHours >= 2.0) ? 'Bánh sữa' : 'Không';
            } else if ($mealRegistration === 'rice' || $mealRegistration === 'Cơm') {
                $mealVal = 'Cơm';
            } else if ($mealRegistration === 'bread' || $mealRegistration === 'Bánh sữa') {
                $mealVal = 'Bánh sữa';
            } else if ($mealRegistration === 'none' || $mealRegistration === 'Không') {
                $mealVal = 'Không';
            } else {
                $mealVal = ($otHours >= 2.0) ? 'Cơm' : 'Không';
            }

            // 5. Yêu cầu xe (Có / Không)
            $busVal = 'Không';
            if ($busRequest === 'yes' || $busRequest === 'Có') {
                $busVal = 'Có';
            } else if ($busRequest === 'no' || $busRequest === 'Không') {
                $busVal = 'Không';
            } else {
                $busVal = (!empty($data['use_shuttle_bus']) && intval($data['use_shuttle_bus']) === 1) ? 'Có' : 'Không';
            }

            // 6. Đổi vị trí chấm công (Có / Không)
            $chgPosVal = ($changeAttendancePos === 'Có' || $changeAttendancePos === 'yes') ? 'Có' : 'Không';

            // 7. Yêu cầu chiller & máy phát
            $chillerVal = ($chillerRequest === 'Có' || $chillerRequest === 'yes') ? 'Có' : 'Không';
            $genVal = ($generatorRequest === 'Có' || $generatorRequest === 'yes') ? 'Có' : 'Không';

            // 8. Vị trí OT (mặc định B021)
            $posVal = !empty($otPosition) ? $otPosition : 'B021';

            // 9. Giờ bắt đầu & kết thúc (định dạng H:i)
            $startTimeFmt = !empty($data['start_time']) ? date('H:i', strtotime($data['start_time'])) : '';
            $endTimeFmt = !empty($data['end_time']) ? date('H:i', strtotime($data['end_time'])) : '';

            // 10. Note
            $noteVal = !empty($customNote) ? $customNote : trim((string)$data['reason']);

            fputcsv($out, [
                $stt++,
                $fmtEmpCode,
                $data['full_name'],
                $costCenterDest,
                $data['ot_date'],
                $startTimeFmt,
                $endTimeFmt,
                trim((string)$data['reason']),
                $shiftVal,
                $posVal,
                $chgPosVal,
                $mealVal,
                $busVal,
                $chillerVal,
                $genVal,
                $noteVal
            ]);
        }

        fclose($out);
        exit;
    }

    // ---------------------------------------------------------------------
    // ĐỊNH DẠNG EXCEL (.XLSX) THEO MẪU GỐC OVERTIME.XLSX
    // ---------------------------------------------------------------------
    // Dò tìm file template đa tầng bảo đảm an toàn trên mọi hệ điều hành
    $templateCandidates = [
        __DIR__ . '/../data/overtime.xlsx',
        __DIR__ . '/../Data/overtime.xlsx',
        __DIR__ . '/../data/File cu/overtime.xlsx',
        __DIR__ . '/../data/File cũ/overtime.xlsx',
        dirname(__DIR__) . '/data/overtime.xlsx',
        dirname(__DIR__) . '/Data/overtime.xlsx'
    ];

    $templatePath = null;
    foreach ($templateCandidates as $candidate) {
        if (file_exists($candidate)) {
            $templatePath = $candidate;
            break;
        }
    }

    // Nếu các đường dẫn cứng chưa thấy, thử dùng glob tìm file khớp tên trong data
    if (!$templatePath) {
        $globMatches = glob(__DIR__ . '/../data/*overtime*.xlsx');
        if (!empty($globMatches)) {
            $templatePath = $globMatches[0];
        }
    }

    $spreadsheet = null;
    if ($templatePath && file_exists($templatePath)) {
        try {
            $spreadsheet = IOFactory::load($templatePath);
        } catch (\Throwable $loadErr) {
            $spreadsheet = null;
        }
    }

    // Nếu không tìm thấy file template hoặc nạp thất bại, tự động sinh Spreadsheet dự phòng chuẩn
    if (!$spreadsheet) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("DX Plastic Group")
            ->setTitle("Overtime Export Plan");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ListOT');

        // Tạo sheet option
        $optSheet = $spreadsheet->createSheet();
        $optSheet->setTitle('option');
        $reasons = ['Sản xuất / Đơn hàng', 'Thiếu nhân lực', 'Máy móc / Bảo trì', 'Chất lượng', 'LOG', 'Dự án / Model mới', 'Công việc khác'];
        foreach ($reasons as $k => $v) $optSheet->setCellValue('A' . ($k + 1), $v);
        $factories = ['SMC1', 'SMC2', 'SMC3', 'SMC4'];
        foreach ($factories as $k => $v) $optSheet->setCellValue('B' . ($k + 1), $v);
        $shifts = ['1', '2', '3', 'HC'];
        foreach ($shifts as $k => $v) $optSheet->setCellValue('C' . ($k + 1), $v);
        $buses = ['Không sử dụng xe đưa rước', 'Có sử dụng xe đưa rước'];
        foreach ($buses as $k => $v) $optSheet->setCellValue('D' . ($k + 1), $v);
        
        // Thiết lập header hàng 1 cho ListOT
        $headers = [
            'B' => 'No.', 'C' => 'msnv', 'D' => 'họ tên', 'E' => 'nhà máy', 'F' => 'costcenter',
            'G' => 'ca làm việc', 'H' => 'lý do', 'I' => 'ngày ot', 'J' => 'từ giờ', 'K' => 'đến giờ',
            'L' => 'sắp xe', 'M' => 'cần điện -khí', 'N' => 'Ghi chú'
        ];
        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col . '1', $title);
        }
    } else {
        $sheet = $spreadsheet->getSheetByName('ListOT') ?? $spreadsheet->getActiveSheet();
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

    // Xóa dữ liệu mẫu ban đầu từ dòng 2 đến dòng 4 nếu có
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

        // Col C: msnv (Mã nhân viên, 8 chữ số)
        $rawCode = trim((string)$data['employee_code']);
        $codeStr = preg_match('/^\d+$/', $rawCode) ? str_pad($rawCode, 8, '0', STR_PAD_LEFT) : $rawCode;
        $sheet->setCellValueExplicit('C' . $rowIndex, $codeStr, DataType::TYPE_STRING);
        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col D: họ tên
        $sheet->setCellValueExplicit('D' . $rowIndex, (string)$data['full_name'], DataType::TYPE_STRING);
        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col E: nhà máy (Mặc định SMC2)
        $factVal = !empty($factory) ? $factory : 'SMC2';
        $sheet->setCellValueExplicit('E' . $rowIndex, $factVal, DataType::TYPE_STRING);
        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode('@');
        $sheet->getCell('E' . $rowIndex)->setDataValidation(clone $dvFactory);

        // Col F: costcenter
        $ccVal = !empty($destCostCenter) 
            ? $destCostCenter 
            : (!empty($data['cost_center']) ? $data['cost_center'] : 'A00330');
        $sheet->setCellValueExplicit('F' . $rowIndex, (string)$ccVal, DataType::TYPE_STRING);
        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col G: ca làm việc (1, 2, 3, HC)
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

        // Col H: lý do
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

        // Col J: từ giờ
        $startTime = strtotime($data['start_time']);
        if ($startTime) {
            $h = intval(date('H', $startTime));
            $m = intval(date('i', $startTime));
            $s = intval(date('s', $startTime));
            $sheet->setCellValue('J' . $rowIndex, ($h * 3600 + $m * 60 + $s) / 86400);
            $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode('[$-F400]h:mm:ss\\ AM/PM');
        }

        // Col K: đến giờ
        $endTime = strtotime($data['end_time']);
        if ($endTime) {
            $h = intval(date('H', $endTime));
            $m = intval(date('i', $endTime));
            $s = intval(date('s', $endTime));
            $sheet->setCellValue('K' . $rowIndex, ($h * 3600 + $m * 60 + $s) / 86400);
            $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode('[$-F400]h:mm:ss\\ AM/PM');
        }

        // Col L: sắp xe
        $shuttleText = 'Không sử dụng xe đưa rước';
        if ($busRequest === 'yes' || $busRequest === 'Có') {
            $shuttleText = 'Có sử dụng xe đưa rước';
        } else if ($busRequest === 'no' || $busRequest === 'Không') {
            $shuttleText = 'Không sử dụng xe đưa rước';
        } else {
            $shuttleText = (!empty($data['use_shuttle_bus']) && $data['use_shuttle_bus'] == 1)
                ? 'Có sử dụng xe đưa rước'
                : 'Không sử dụng xe đưa rước';
        }
        $sheet->setCellValueExplicit('L' . $rowIndex, $shuttleText, DataType::TYPE_STRING);
        $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode('@');
        $sheet->getCell('L' . $rowIndex)->setDataValidation(clone $dvBus);

        // Col M: cần điện -khí
        $sheet->setCellValueExplicit('M' . $rowIndex, 'SMC2 - B2 - F1', DataType::TYPE_STRING);
        $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode('@');

        // Col N: Ghi chú
        $note = !empty($customNote) ? $customNote : trim((string)$data['reason']);
        $sheet->setCellValue('N' . $rowIndex, $note);
        $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);

        $sheet->getRowDimension($rowIndex)->setRowHeight(20);

        $rowIndex++;
        $stt++;
    }

    $outFileName = "DanhSachTangCaKeHoach_{$factory}_{$rangeStr}.xlsx";

    // KỸ THUẬT XUẤT FILE AN TOÀN TRÁNH "SITE WASN'T AVAILABLE":
    // 1. Tạo file trong thư mục temp trước
    $tempDir = is_writable(sys_get_temp_dir()) ? sys_get_temp_dir() : (__DIR__ . '/../uploads/temp');
    if (!is_dir($tempDir)) @mkdir($tempDir, 0777, true);
    $tempFilePath = tempnam($tempDir, 'ot_xlsx_');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFilePath);

    $fileSize = filesize($tempFilePath);

    // 2. Dọn sạch toàn bộ buffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // 3. Thiết lập các header HTTP chính xác
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $outFileName . '"; filename*=UTF-8\'\'' . rawurlencode($outFileName));
    header('Content-Length: ' . $fileSize);
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 4. Đọc file gửi về client và dọn file tạm
    readfile($tempFilePath);
    @unlink($tempFilePath);
    exit;

} catch (\Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi xuất file: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
