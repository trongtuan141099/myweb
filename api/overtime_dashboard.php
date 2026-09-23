<?php
/**
 * API Dashboard Tổng Quan Quản Lý Tăng Ca
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

try {
    $month = !empty($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year = !empty($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

    // 1. KPI Cards
    // a) Số nhân viên có OT trong tháng
    $resEmpCount = $conn->query("SELECT COUNT(DISTINCT employee_code) FROM ot_actuals WHERE MONTH(ot_date) = {$month} AND YEAR(ot_date) = {$year}");
    $empCountMonth = $resEmpCount ? intval($resEmpCount->fetch_row()[0]) : 0;

    // b) Tổng giờ OT tháng hiện tại
    $resHoursMonth = $conn->query("SELECT COALESCE(SUM(total_hours_actual), 0) FROM ot_actuals WHERE MONTH(ot_date) = {$month} AND YEAR(ot_date) = {$year}");
    $hoursMonth = $resHoursMonth ? floatval($resHoursMonth->fetch_row()[0]) : 0;

    // c) Tổng giờ OT cả năm
    $resHoursYear = $conn->query("SELECT COALESCE(SUM(total_hours_actual), 0) FROM ot_actuals WHERE YEAR(ot_date) = {$year}");
    $hoursYear = $resHoursYear ? floatval($resHoursYear->fetch_row()[0]) : 0;

    // d) Số ca cần giải trình (toàn bộ hoặc theo tháng)
    $resExpCount = $conn->query("SELECT COUNT(*) FROM ot_explanations WHERE approval_status IN ('pending', 'submitted')");
    $pendingExpCount = $resExpCount ? intval($resExpCount->fetch_row()[0]) : 0;

    // e) Số nhân sự Mức Vàng (160 - 199.9h) và Mức Đỏ (>= 200h)
    $resWarnings = $conn->query("
        SELECT 
            SUM(CASE WHEN warning_level = 'yellow' THEN 1 ELSE 0 END) AS yellow_count,
            SUM(CASE WHEN warning_level = 'red' THEN 1 ELSE 0 END) AS red_count
        FROM ot_yearly_accumulations
        WHERE year = {$year}
    ");
    $warningStats = $resWarnings ? $resWarnings->fetch_assoc() : ['yellow_count' => 0, 'red_count' => 0];

    // 2. Biểu đồ Xu hướng OT 12 Tháng trong năm
    $monthlyTrend = array_fill(1, 12, 0.0);
    $resTrend = $conn->query("
        SELECT MONTH(ot_date) as m, COALESCE(SUM(total_hours_actual), 0) as total_h
        FROM ot_actuals
        WHERE YEAR(ot_date) = {$year}
        GROUP BY MONTH(ot_date)
    ");
    if ($resTrend) {
        while ($t = $resTrend->fetch_assoc()) {
            $monthlyTrend[intval($t['m'])] = round(floatval($t['total_h']), 1);
        }
    }

    // 3. Biểu đồ Phân bổ Giờ OT theo Bộ phận / Tổ đội
    $deptLabels = [];
    $deptValues = [];
    $resDept = $conn->query("
        SELECT 
            COALESCE(NULLIF(a.team_name, ''), NULLIF(a.group_name, ''), e.cost_center, 'Khác') AS dept_name,
            ROUND(SUM(a.total_hours_actual), 1) AS total_h
        FROM ot_actuals a
        LEFT JOIN employees e ON a.employee_code = e.employee_code
        WHERE YEAR(a.ot_date) = {$year}
        GROUP BY dept_name
        ORDER BY total_h DESC
        LIMIT 6
    ");
    if ($resDept) {
        while ($d = $resDept->fetch_assoc()) {
            $deptLabels[] = $d['dept_name'];
            $deptValues[] = floatval($d['total_h']);
        }
    }

    // 4. Biểu đồ Top 10 Nhân Viên OT Cao Nhất Năm
    $top10 = [];
    $resTop = $conn->query("
        SELECT 
            y.employee_code,
            COALESCE(e.full_name, y.employee_code) AS full_name,
            COALESCE(e.cost_center, '-') AS department,
            y.total_hours_year,
            y.usage_percent,
            y.warning_level
        FROM ot_yearly_accumulations y
        LEFT JOIN employees e ON y.employee_code = e.employee_code
        WHERE y.year = {$year}
        ORDER BY y.total_hours_year DESC
        LIMIT 10
    ");
    if ($resTop) {
        while ($r = $resTop->fetch_assoc()) {
            $top10[] = $r;
        }
    }

    // 5. Thống Kê Đối Soát (Reconciliation Pie/Donut)
    $resRecPie = $conn->query("
        SELECT 
            reconcile_status,
            COUNT(*) as count
        FROM ot_reconciliations
        WHERE YEAR(ot_date) = {$year}
        GROUP BY reconcile_status
    ");
    $recBreakdown = [
        'matched' => 0,
        'plan_only' => 0,
        'actual_only' => 0,
        'time_diff' => 0,
        'overdue' => 0
    ];
    if ($resRecPie) {
        while ($rp = $resRecPie->fetch_assoc()) {
            $st = $rp['reconcile_status'];
            if (isset($recBreakdown[$st])) {
                $recBreakdown[$st] = intval($rp['count']);
            }
        }
    }

    // 6. Danh Sách Nhân Sự Cần Giám Sát Khẩn Cấp (Mức Vàng & Đỏ)
    $criticalList = [];
    $resCritical = $conn->query("
        SELECT 
            y.*,
            COALESCE(e.full_name, y.employee_code) AS full_name,
            COALESCE(e.cost_center, '-') AS department,
            COALESCE(e.job_level, '-') AS job_level
        FROM ot_yearly_accumulations y
        LEFT JOIN employees e ON y.employee_code = e.employee_code
        WHERE y.year = {$year} AND y.warning_level IN ('yellow', 'red')
        ORDER BY y.total_hours_year DESC
        LIMIT 10
    ");
    if ($resCritical) {
        while ($c = $resCritical->fetch_assoc()) {
            $criticalList[] = $c;
        }
    }

    echo json_encode([
        'success' => true,
        'month' => $month,
        'year' => $year,
        'kpis' => [
            'emp_count_month' => $empCountMonth,
            'hours_month' => round($hoursMonth, 1),
            'hours_year' => round($hoursYear, 1),
            'pending_explanations' => $pendingExpCount,
            'yellow_warnings' => intval($warningStats['yellow_count'] ?? 0),
            'red_warnings' => intval($warningStats['red_count'] ?? 0)
        ],
        'monthly_trend' => array_values($monthlyTrend),
        'department_distribution' => [
            'labels' => $deptLabels,
            'values' => $deptValues
        ],
        'top_10' => $top10,
        'reconciliation_breakdown' => $recBreakdown,
        'critical_list' => $criticalList
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

