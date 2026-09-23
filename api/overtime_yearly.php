<?php
/**
 * API Kiểm Soát Giới Hạn 200 Giờ/Năm
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');
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

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        // =====================================================================
        // 1. TỔNG HỢP LŨY KẾ NĂM & MA TRẬN CẢNH BÁO
        // =====================================================================
        case 'get_yearly_summary':
            $year = !empty($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
            $warningLevel = trim($_GET['warning_level'] ?? ''); // 'all', 'green', 'yellow', 'red'
            $search = trim($_GET['search'] ?? '');
            $department = trim($_GET['department'] ?? '');

            $where = "WHERE y.year = {$year}";
            if (!empty($warningLevel) && in_array($warningLevel, ['green', 'yellow', 'red'])) {
                $where .= " AND y.warning_level = '{$warningLevel}'";
            }
            if (!empty($search)) {
                $s = $conn->real_escape_string($search);
                $where .= " AND (y.employee_code LIKE '%{$s}%' OR COALESCE(e.full_name, '') LIKE '%{$s}%')";
            }
            if (!empty($department)) {
                $d = $conn->real_escape_string($department);
                $where .= " AND COALESCE(e.cost_center, '') = '{$d}'";
            }

            // Đếm số lượng theo mức cảnh báo
            $sqlCounts = "
                SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN warning_level = 'green' THEN 1 ELSE 0 END) as count_green,
                    SUM(CASE WHEN warning_level = 'yellow' THEN 1 ELSE 0 END) as count_yellow,
                    SUM(CASE WHEN warning_level = 'red' THEN 1 ELSE 0 END) as count_red,
                    COALESCE(SUM(total_hours_year), 0) as grand_total_hours
                FROM ot_yearly_accumulations y
                WHERE y.year = {$year}
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
                    COALESCE(e.gender, '-') AS gender
                FROM ot_yearly_accumulations y
                LEFT JOIN employees e ON y.employee_code = e.employee_code
                {$where}
                ORDER BY y.total_hours_year DESC, y.employee_code ASC
            ";
            $resData = $conn->query($sqlData);
            $data = [];
            while ($r = $resData->fetch_assoc()) {
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

            // Lấy toàn bộ ca làm thực tế trong năm
            $stmtAct = $conn->prepare("
                SELECT 
                    a.*,
                    r.reconcile_status,
                    r.needs_explanation,
                    exp.approval_status as explanation_status
                FROM ot_actuals a
                LEFT JOIN ot_reconciliations r ON a.id = r.actual_id
                LEFT JOIN ot_explanations exp ON r.id = exp.reconciliation_id
                WHERE a.employee_code = ? AND YEAR(a.ot_date) = ?
                ORDER BY a.ot_date ASC, a.start_time_actual ASC
            ");
            $stmtAct->bind_param("si", $empCode, $year);
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

