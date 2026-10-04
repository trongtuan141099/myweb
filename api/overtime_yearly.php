<?php
/**
 * API Kiểm Soát Giới Hạn 200 Giờ/Năm
 * DX Plastic Group - Overtime Management System
 */
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

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
requireApiPermission('api.overtime.yearly');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        // =====================================================================
        // 1. TỔNG HỢP LŨY KẾ NĂM & MA TRẬN CẢNH BÁO
        // =====================================================================
        case 'get_yearly_summary':
            $year = !empty($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
            $warningLevel = trim($_GET['warning_level'] ?? ''); // 'all', 'green', 'yellow', 'red', 'month_warning_36h', 'month_exceeded_40h'
            $search = trim($_GET['search'] ?? '');
            $department = trim($_GET['department'] ?? '');

            // Đảm bảo dữ liệu tích lũy năm được vét cạn toàn bộ danh sách nhân viên hiện tại
            require_once __DIR__ . '/../core/overtime_service.php';
            $checkEmpCount = $conn->query("SELECT COUNT(DISTINCT employee_code) FROM employees WHERE employee_code IS NOT NULL AND employee_code != ''")->fetch_row()[0];
            $checkYcCount = $conn->query("SELECT COUNT(*) FROM ot_yearly_accumulations WHERE year = {$year}")->fetch_row()[0];
            if ($checkYcCount < $checkEmpCount) {
                recalculateYearlyAccumulations($conn, $year);
            }

            $where = "WHERE y.year = {$year}";
            if (!empty($warningLevel)) {
                if (in_array($warningLevel, ['green', 'yellow', 'red'])) {
                    $where .= " AND y.warning_level = '{$warningLevel}'";
                } else if ($warningLevel === 'month_warning_36h' || $warningLevel === 'month_warning') {
                    // Cảnh báo: Tăng ca trong tháng vượt 36h (ngưỡng 90% của trần 40h/tháng theo Điều 107 BLLĐ)
                    $where .= " AND (y.total_hours_m1 > 36 OR y.total_hours_m2 > 36 OR y.total_hours_m3 > 36 OR y.total_hours_m4 > 36 OR y.total_hours_m5 > 36 OR y.total_hours_m6 > 36 OR y.total_hours_m7 > 36 OR y.total_hours_m8 > 36 OR y.total_hours_m9 > 36 OR y.total_hours_m10 > 36 OR y.total_hours_m11 > 36 OR y.total_hours_m12 > 36)";
                } else if ($warningLevel === 'month_exceeded_40h') {
                    // Báo động: Đã vượt trần 40h/tháng
                    $where .= " AND (y.total_hours_m1 > 40 OR y.total_hours_m2 > 40 OR y.total_hours_m3 > 40 OR y.total_hours_m4 > 40 OR y.total_hours_m5 > 40 OR y.total_hours_m6 > 40 OR y.total_hours_m7 > 40 OR y.total_hours_m8 > 40 OR y.total_hours_m9 > 40 OR y.total_hours_m10 > 40 OR y.total_hours_m11 > 40 OR y.total_hours_m12 > 40)";
                }
            }
            if (!empty($search)) {
                $s = $conn->real_escape_string($search);
                $where .= " AND (y.employee_code LIKE '%{$s}%' OR COALESCE(e.full_name, '') LIKE '%{$s}%')";
            }
            if (!empty($department)) {
                $d = $conn->real_escape_string($department);
                $where .= " AND COALESCE(e.cost_center, '') = '{$d}'";
            }

            // Đếm số lượng theo mức cảnh báo năm và cảnh báo tháng (> 36h)
            $deptFilterCount = !empty($department) ? " AND COALESCE(e.cost_center, '') = '" . $conn->real_escape_string($department) . "'" : "";
            $sqlCounts = "
                SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN y.warning_level = 'green' THEN 1 ELSE 0 END) as count_green,
                    SUM(CASE WHEN y.warning_level = 'yellow' THEN 1 ELSE 0 END) as count_yellow,
                    SUM(CASE WHEN y.warning_level = 'red' THEN 1 ELSE 0 END) as count_red,
                    SUM(CASE WHEN (y.total_hours_m1 > 36 OR y.total_hours_m2 > 36 OR y.total_hours_m3 > 36 OR y.total_hours_m4 > 36 OR y.total_hours_m5 > 36 OR y.total_hours_m6 > 36 OR y.total_hours_m7 > 36 OR y.total_hours_m8 > 36 OR y.total_hours_m9 > 36 OR y.total_hours_m10 > 36 OR y.total_hours_m11 > 36 OR y.total_hours_m12 > 36) THEN 1 ELSE 0 END) as count_month_warning_36h,
                    SUM(CASE WHEN (y.total_hours_m1 > 40 OR y.total_hours_m2 > 40 OR y.total_hours_m3 > 40 OR y.total_hours_m4 > 40 OR y.total_hours_m5 > 40 OR y.total_hours_m6 > 40 OR y.total_hours_m7 > 40 OR y.total_hours_m8 > 40 OR y.total_hours_m9 > 40 OR y.total_hours_m10 > 40 OR y.total_hours_m11 > 40 OR y.total_hours_m12 > 40) THEN 1 ELSE 0 END) as count_month_exceeded_40h,
                    COALESCE(SUM(y.total_hours_year), 0) as grand_total_hours
                FROM ot_yearly_accumulations y
                LEFT JOIN employees e ON y.employee_code = e.employee_code
                WHERE y.year = {$year}{$deptFilterCount}
            ";
            $resCounts = $conn->query($sqlCounts);
            $stats = $resCounts ? $resCounts->fetch_assoc() : [];

            // Lấy danh sách nhân viên
            $sqlData = "
                SELECT 
                    y.*,
                    COALESCE(e.full_name, y.employee_code) AS full_name,
                    COALESCE(e.cost_center, '-') AS department,
                    COALESCE(e.job_level, '-') AS job_level,
                    COALESCE(e.gender, '-') AS gender,
                    COALESCE(e.resignation_date, '') AS resignation_date
                FROM ot_yearly_accumulations y
                LEFT JOIN employees e ON y.employee_code = e.employee_code
                {$where}
                ORDER BY y.total_hours_year DESC, y.employee_code ASC
            ";
            $resData = $conn->query($sqlData);
            $data = [];
            while ($r = $resData->fetch_assoc()) {
                $hasResigned = !empty($r['resignation_date']) && $r['resignation_date'] !== '0000-00-00';
                $r['has_resigned'] = $hasResigned;
                $r['resignation_date'] = $hasResigned ? $r['resignation_date'] : '';

                // Kiểm tra các tháng có giờ OT vượt ngưỡng 36h hoặc vượt trần 40h
                $monthWarnings = [];
                $hasWarning36h = false;
                $hasExceeded40h = false;

                for ($m = 1; $m <= 12; $m++) {
                    $val = floatval($r["total_hours_m{$m}"]);
                    if ($val > 40.0) {
                        $monthWarnings[] = [
                            'month' => $m,
                            'label' => "T{$m}",
                            'hours' => $val,
                            'level' => 'red',
                            'title' => "Tháng {$m} vượt trần quy định 40h ({$val}h)"
                        ];
                        $hasExceeded40h = true;
                        $hasWarning36h = true;
                    } else if ($val > 36.0) {
                        $monthWarnings[] = [
                            'month' => $m,
                            'label' => "T{$m}",
                            'hours' => $val,
                            'level' => 'yellow',
                            'title' => "Tháng {$m} vượt ngưỡng cảnh báo 36h ({$val}h/40h)"
                        ];
                        $hasWarning36h = true;
                    }
                }

                $r['month_warnings'] = $monthWarnings;
                $r['has_month_warning_36h'] = $hasWarning36h;
                $r['has_month_exceeded_40h'] = $hasExceeded40h;

                $data[] = $r;
            }

            // Lấy danh sách các năm có trong hệ thống
            $yearsRes = $conn->query("SELECT DISTINCT year FROM ot_yearly_accumulations ORDER BY year DESC");
            $availableYears = [];
            while ($yr = $yearsRes->fetch_assoc()) {
                $availableYears[] = intval($yr['year']);
            }
            if (empty($availableYears)) $availableYears[] = $year;

            // Lấy danh sách phòng ban / Cost center
            $deptRes = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center ASC");
            $departments = [];
            while ($d = $deptRes->fetch_assoc()) {
                $departments[] = $d['cost_center'];
            }

            echo json_encode([
                'success' => true,
                'year' => $year,
                'available_years' => $availableYears,
                'departments' => $departments,
                'stats' => $stats,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LỊCH SỬ CÁC CA TĂNG CA CỦA 1 NHÂN VIÊN TRONG NĂM
        // =====================================================================
        case 'get_employee_history':
            $empCode = trim($_GET['employee_code'] ?? '');
            $year = !empty($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

            if (empty($empCode)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu mã nhân viên']);
                exit;
            }

            // Lấy thông tin nhân viên
            $stmtEmp = $conn->prepare("SELECT employee_code, full_name, cost_center, job_level FROM employees WHERE employee_code = ?");
            $stmtEmp->bind_param("s", $empCode);
            $stmtEmp->execute();
            $empInfo = $stmtEmp->get_result()->fetch_assoc();
            $stmtEmp->close();

            // Lấy dữ liệu lũy kế năm
            $stmtAcc = $conn->prepare("SELECT * FROM ot_yearly_accumulations WHERE employee_code = ? AND year = ?");
            $stmtAcc->bind_param("si", $empCode, $year);
            $stmtAcc->execute();
            $accInfo = $stmtAcc->get_result()->fetch_assoc();
            $stmtAcc->close();

            // Lấy toàn bộ ca làm thực tế trong năm (bao gồm cả ca quẹt thẻ HRM và ca giải trình thủ công đã duyệt)
            $stmtAct = $conn->prepare("
                SELECT 
                    a.id,
                    a.employee_code,
                    a.ot_date,
                    a.start_time_actual,
                    a.end_time_actual,
                    a.total_minutes_actual,
                    a.total_hours_actual,
                    a.reason,
                    a.approval_status,
                    'actual' AS source_type,
                    r.reconcile_status,
                    r.needs_explanation,
                    exp.approval_status as explanation_status
                FROM ot_actuals a
                LEFT JOIN ot_reconciliations r ON a.id = r.actual_id
                LEFT JOIN ot_explanations exp ON r.id = exp.reconciliation_id
                WHERE a.employee_code = ? AND YEAR(a.ot_date) = ?

                UNION ALL

                SELECT 
                    exp.id,
                    exp.employee_code,
                    exp.ot_date,
                    exp.start_time AS start_time_actual,
                    exp.end_time AS end_time_actual,
                    exp.total_minutes AS total_minutes_actual,
                    exp.total_hours AS total_hours_actual,
                    exp.explanation_content AS reason,
                    'Đã duyệt' AS approval_status,
                    'manual_explanation' AS source_type,
                    'manual_approved' AS reconcile_status,
                    0 AS needs_explanation,
                    exp.approval_status AS explanation_status
                FROM ot_explanations exp
                WHERE exp.employee_code = ? AND YEAR(exp.ot_date) = ?
                  AND exp.is_manual = 1 AND exp.approval_status = 'approved'

                ORDER BY ot_date ASC, start_time_actual ASC
            ");
            $stmtAct->bind_param("sisi", $empCode, $year, $empCode, $year);
            $stmtAct->execute();
            $resAct = $stmtAct->get_result();
            $history = [];
            while ($r = $resAct->fetch_assoc()) {
                $history[] = $r;
            }
            $stmtAct->close();

            echo json_encode([
                'success' => true,
                'employee' => $empInfo ?: ['employee_code' => $empCode, 'full_name' => $empCode],
                'accumulation' => $accInfo,
                'history' => $history
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

