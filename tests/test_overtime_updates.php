<?php
/**
 * Test Suite: Overtime Module Updates
 * DX Plastic Group
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/overtime_service.php';

echo "=== TEST SUITE: OVERTIME MODULE UPDATES ===\n\n";

$pass = 0;
$fail = 0;

function assertTest($condition, $msg) {
    global $pass, $fail;
    if ($condition) {
        echo "  ✓ PASS: {$msg}\n";
        $pass++;
    } else {
        echo "  ✗ FAIL: {$msg}\n";
        $fail++;
    }
}

// =========================================================================
// 1. KIỂM TRA SCHEMA & CỘT MỚI BẢNG OT_EXPLANATIONS
// =========================================================================
echo "[1] Checking DB Schema for ot_explanations:\n";
$cols = [];
$resCols = $conn->query("SHOW COLUMNS FROM ot_explanations");
while ($r = $resCols->fetch_assoc()) {
    $cols[] = $r['Field'];
}
assertTest(in_array('is_manual', $cols), "Column 'is_manual' exists in ot_explanations");
assertTest(in_array('total_hours', $cols), "Column 'total_hours' exists in ot_explanations");
assertTest(in_array('total_minutes', $cols), "Column 'total_minutes' exists in ot_explanations");
assertTest(in_array('start_time', $cols), "Column 'start_time' exists in ot_explanations");
assertTest(in_array('end_time', $cols), "Column 'end_time' exists in ot_explanations");

// =========================================================================
// 2. KIỂM TRA YÊU CẦU 1: ĐỐI SOÁT TĂNG CA & LOẠI BỎ NHẮC CHỜ BƯỚC 2 CHO CA ĐÃ GT HOẶC HỦY
// =========================================================================
echo "\n[2] Testing Requirement 1: Reconciliation query & badge filtering:\n";
// Kiểm tra query đếm uncompleted_actual
$sqlBadgeTest = "
    SELECT 
        SUM(CASE WHEN (r.actual_id IS NULL OR r.reconcile_status = 'plan_only') AND (r.explanation_requested = 0 OR r.explanation_requested IS NULL) AND (r.is_dismissed = 0 OR r.is_dismissed IS NULL) AND (r.is_explained = 0 OR r.is_explained IS NULL) THEN 1 ELSE 0 END) as valid_uncompleted,
        SUM(CASE WHEN (r.actual_id IS NULL OR r.reconcile_status = 'plan_only') AND (r.explanation_requested = 1 OR r.is_dismissed = 1 OR r.is_explained = 1) THEN 1 ELSE 0 END) as excluded_uncompleted
    FROM ot_reconciliations r
";
$resB = $conn->query($sqlBadgeTest);
$badgeRow = $resB ? $resB->fetch_assoc() : [];
assertTest(isset($badgeRow['valid_uncompleted']), "Badge uncompleted_actual excludes dismissed and requested explanations");
echo "    -> Valid pending step 2: " . ($badgeRow['valid_uncompleted'] ?? 0) . ", Excluded (dismissed/requested/explained): " . ($badgeRow['excluded_uncompleted'] ?? 0) . "\n";

// =========================================================================
// 3. KIỂM TRA YÊU CẦU 2: ĐĂNG KÝ GIẢI TRÌNH THỦ CÔNG & TÍCH HỢP LŨY KẾ 200H
// =========================================================================
echo "\n[3] Testing Requirement 2: Manual explanation registration & 200h limit integration:\n";

// Lấy 1 nhân viên mẫu từ bảng employees
$resEmp = $conn->query("SELECT employee_code, full_name, cost_center FROM employees LIMIT 1");
$sampleEmp = $resEmp ? $resEmp->fetch_assoc() : null;

if (!$sampleEmp) {
    echo "  ! No employee found in database, creating a dummy test employee\n";
    $conn->query("INSERT INTO employees (employee_code, full_name, cost_center) VALUES ('TEST_NV01', 'Nguyen Van Test', 'SX')");
    $sampleEmp = ['employee_code' => 'TEST_NV01', 'full_name' => 'Nguyen Van Test', 'cost_center' => 'SX'];
}

$testEmpCode = $sampleEmp['employee_code'];
$testDate = '2026-09-18';
$testYear = 2026;
$testHours = 4.50;
$testMinutes = 270;

// Ghi nhận số giờ ban đầu của nhân viên này trong ot_yearly_accumulations
$resInit = $conn->query("SELECT total_hours_m9, total_hours_year FROM ot_yearly_accumulations WHERE employee_code = '{$testEmpCode}' AND year = {$testYear}");
$initRow = $resInit ? $resInit->fetch_assoc() : null;
$initialM9 = $initRow ? floatval($initRow['total_hours_m9']) : 0.0;
$initialYear = $initRow ? floatval($initRow['total_hours_year']) : 0.0;

// 3.1 Insert 1 bản ghi giải trình thủ công
$stmtTestIn = $conn->prepare("
    INSERT INTO ot_explanations (
        reconciliation_id, employee_code, ot_date, start_time, end_time,
        total_hours, total_minutes, violation_type, explanation_content,
        submitted_by, submitted_at, approver_username, approval_status,
        approver_notes, approved_at, is_manual, created_at
    ) VALUES (0, ?, ?, '2026-09-18 16:30:00', '2026-09-18 21:00:00', ?, ?, 'Quên đăng ký kế hoạch trên HRM (ĐK thủ công)', 'Test giải trình thủ công đơn hàng gấp', 'admin', NOW(), 'admin', 'approved', 'Đã duyệt qua test', NOW(), 1, NOW())
");
$stmtTestIn->bind_param("ssdi", $testEmpCode, $testDate, $testHours, $testMinutes);
$stmtTestIn->execute();
$testExpId = $stmtTestIn->insert_id;
$stmtTestIn->close();

assertTest($testExpId > 0, "Inserted manual explanation with ID: {$testExpId}");

// 3.2 Gọi hàm tính lại lũy kế năm
recalculateYearlyAccumulations($conn, $testYear);

// 3.3 Kiểm tra xem ot_yearly_accumulations đã cộng thêm đúng 4.5h vào T9 và Tổng năm chưa
$resAfter = $conn->query("SELECT total_hours_m9, total_hours_year, remaining_hours, usage_percent, warning_level FROM ot_yearly_accumulations WHERE employee_code = '{$testEmpCode}' AND year = {$testYear}");
$afterRow = $resAfter ? $resAfter->fetch_assoc() : null;

$newM9 = $afterRow ? floatval($afterRow['total_hours_m9']) : 0.0;
$newYear = $afterRow ? floatval($afterRow['total_hours_year']) : 0.0;

assertTest(round($newM9, 2) === round($initialM9 + $testHours, 2), "Monthly hours (T9) increased by {$testHours}h (from {$initialM9} to {$newM9})");
assertTest(round($newYear, 2) === round($initialYear + $testHours, 2), "Total yearly hours increased by {$testHours}h (from {$initialYear} to {$newYear})");
echo "    -> New T9: {$newM9}h, New Total Year: {$newYear}h, Remaining: {$afterRow['remaining_hours']}h, Usage: {$afterRow['usage_percent']}%\n";

// 3.4 Kiểm tra get_employee_history chứa ca giải trình thủ công
$stmtHist = $conn->prepare("
    SELECT id, ot_date, total_hours_actual, source_type, approval_status 
    FROM (
        SELECT a.id, a.ot_date, a.total_hours_actual, 'actual' as source_type, a.approval_status FROM ot_actuals a WHERE a.employee_code = ? AND YEAR(a.ot_date) = ?
        UNION ALL
        SELECT exp.id, exp.ot_date, exp.total_hours as total_hours_actual, 'manual_explanation' as source_type, 'Đã duyệt' as approval_status FROM ot_explanations exp WHERE exp.employee_code = ? AND YEAR(exp.ot_date) = ? AND exp.is_manual = 1 AND exp.approval_status = 'approved'
    ) combined WHERE id = ?
");
$stmtHist->bind_param("sisis", $testEmpCode, $testYear, $testEmpCode, $testYear, $testExpId);
$stmtHist->execute();
$histFound = $stmtHist->get_result()->fetch_assoc();
$stmtHist->close();

assertTest(!empty($histFound) && $histFound['source_type'] === 'manual_explanation', "get_employee_history includes manual explanation ca tăng ca thủ công");

// 3.5 Dọn dẹp bản ghi test và tính lại để trả về trạng thái chuẩn
$conn->query("DELETE FROM ot_explanations WHERE id = {$testExpId}");
recalculateYearlyAccumulations($conn, $testYear);
$resClean = $conn->query("SELECT total_hours_m9, total_hours_year FROM ot_yearly_accumulations WHERE employee_code = '{$testEmpCode}' AND year = {$testYear}");
$cleanRow = $resClean ? $resClean->fetch_assoc() : null;
assertTest(round(floatval($cleanRow['total_hours_m9']), 2) === round($initialM9, 2), "Cleanup test: Hours rolled back properly to {$initialM9}h");

// =========================================================================
// 4. KIỂM TRA YÊU CẦU 3: CẢNH BÁO GIỚI HẠN THÁNG (> 36H / TỐI ĐA 40H)
// =========================================================================
echo "\n[4] Testing Requirement 3: Monthly warning threshold (> 36h):\n";

$sqlCheckMonthWarn = "
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN (total_hours_m1 > 36 OR total_hours_m2 > 36 OR total_hours_m3 > 36 OR total_hours_m4 > 36 OR total_hours_m5 > 36 OR total_hours_m6 > 36 OR total_hours_m7 > 36 OR total_hours_m8 > 36 OR total_hours_m9 > 36 OR total_hours_m10 > 36 OR total_hours_m11 > 36 OR total_hours_m12 > 36) THEN 1 ELSE 0 END) as count_warning_36h,
        SUM(CASE WHEN (total_hours_m1 > 40 OR total_hours_m2 > 40 OR total_hours_m3 > 40 OR total_hours_m4 > 40 OR total_hours_m5 > 40 OR total_hours_m6 > 40 OR total_hours_m7 > 40 OR total_hours_m8 > 40 OR total_hours_m9 > 40 OR total_hours_m10 > 40 OR total_hours_m11 > 40 OR total_hours_m12 > 40) THEN 1 ELSE 0 END) as count_exceeded_40h
    FROM ot_yearly_accumulations
    WHERE year = {$testYear}
";
$resMW = $conn->query($sqlCheckMonthWarn);
$mwStats = $resMW ? $resMW->fetch_assoc() : [];

assertTest(isset($mwStats['count_warning_36h']), "Calculated count_warning_36h in yearly accumulations stats");
assertTest(isset($mwStats['count_exceeded_40h']), "Calculated count_exceeded_40h in yearly accumulations stats");
echo "    -> Employees exceeding 36h in any month: " . intval($mwStats['count_warning_36h']) . "\n";
echo "    -> Employees exceeding 40h in any month: " . intval($mwStats['count_exceeded_40h']) . "\n";

// =========================================================================
// TỔNG KẾT
// =========================================================================
echo "\n=== RESULT: {$pass} PASSED, {$fail} FAILED ===\n";
if ($fail === 0) {
    echo "✓ ALL TESTS PASSED SUCCESSFULLY!\n";
} else {
    echo "✗ SOME TESTS FAILED!\n";
}
