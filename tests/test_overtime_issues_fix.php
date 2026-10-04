<?php
/**
 * Test Suite: Verification for Overtime Issues 1 & 2 Fixes
 * DX Plastic Group - Overtime Module
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/overtime_service.php';

echo "=== TEST SUITE: OVERTIME ISSUES FIX VERIFICATION ===\n\n";

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
// 1. KIỂM TRA LỖI 1: NHÂN VIÊN KHÔNG ĐĂNG KÝ KẾ HOẠCH TĂNG CA
// =========================================================================
echo "[1] Testing Issue 1: Employee with actual OT but NO plan registered:\n";

$testEmp = 'TEST_NOPLAN_01';
$testDate = '2026-09-25';
$testYear = 2026;
$testMonth = 9;

// Đảm bảo nhân viên test có trong bảng employees
$conn->query("INSERT IGNORE INTO employees (employee_code, full_name, cost_center) VALUES ('{$testEmp}', 'Nguyen Van NoPlan', 'Plastic Extrusion')");

// Xóa dữ liệu cũ nếu có
$conn->query("DELETE FROM ot_plans WHERE employee_code = '{$testEmp}' AND ot_date = '{$testDate}'");
$conn->query("DELETE FROM ot_actuals WHERE employee_code = '{$testEmp}' AND ot_date = '{$testDate}'");
$conn->query("DELETE FROM ot_reconciliations WHERE employee_code = '{$testEmp}' AND ot_date = '{$testDate}'");

// Giả lập nạp 1 bản ghi thực tế phát sinh ngoài kế hoạch (480 phút = 8 giờ)
$stmtAct = $conn->prepare("
    INSERT INTO ot_actuals (
        employee_code, full_name, group_name, team_name, reason,
        ot_date, start_time_actual, end_time_actual, total_minutes_actual,
        start_time_plan, end_time_plan, diff_minutes, diff_status, approval_status
    ) VALUES (?, 'Nguyen Van NoPlan', 'Plastic Extrusion', 'TU Extrusion', 'Tăng ca đột xuất ngoài KH',
        ?, '2026-09-25 06:00:00', '2026-09-25 14:00:00', 480,
        NULL, NULL, 0, 'Ngoài kế hoạch', 'Đã duyệt')
");
$stmtAct->bind_param("ss", $testEmp, $testDate);
$stmtAct->execute();
$actId = $stmtAct->insert_id;
$stmtAct->close();
assertTest($actId > 0, "Inserted actual OT without plan for {$testEmp}");

// Chạy đối soát nội bộ cho ngày test
$summary = runReconciliationInternal($conn, $testMonth, $testYear);

// Kiểm tra bản ghi trong ot_reconciliations
$stmtCheckRec = $conn->prepare("SELECT * FROM ot_reconciliations WHERE employee_code = ? AND ot_date = ?");
$stmtCheckRec->bind_param("ss", $testEmp, $testDate);
$stmtCheckRec->execute();
$recRow = $stmtCheckRec->get_result()->fetch_assoc();
$stmtCheckRec->close();

assertTest(!empty($recRow), "Reconciliation record created for unplanned case");
assertTest($recRow['reconcile_status'] === 'unplanned', "reconcile_status is 'unplanned' (got '{$recRow['reconcile_status']}')");
assertTest(intval($recRow['diff_minutes']) === 0, "diff_minutes is 0 (NO difference recorded for un-planned employee, got {$recRow['diff_minutes']})");
assertTest(intval($recRow['needs_explanation']) === 0, "needs_explanation is 0 (unplanned case does NOT trigger explanation)");
assertTest(empty($recRow['plan_id']), "plan_id is NULL as employee had no registered plan");
assertTest(intval($recRow['actual_id']) === $actId, "actual_id correctly references actual record");

// =========================================================================
// 2. KIỂM TRA LỖI 2: VÉT CẠN TOÀN BỘ 133+ NHÂN VIÊN TRONG GIỚI HẠN 200H/NĂM
// =========================================================================
echo "\n[2] Testing Issue 2: Exhaustive 200h limit calculation for ALL employees:\n";

// 2.1 Tính lại lũy kế năm
recalculateYearlyAccumulations($conn, $testYear);

$cntEmp = $conn->query("SELECT COUNT(DISTINCT employee_code) FROM employees WHERE employee_code IS NOT NULL AND employee_code != ''")->fetch_row()[0];
$cntYc = $conn->query("SELECT COUNT(*) FROM ot_yearly_accumulations WHERE year = {$testYear}")->fetch_row()[0];
assertTest($cntYc >= $cntEmp, "ot_yearly_accumulations contains ALL employees ({$cntYc} rows >= {$cntEmp} employees)");

// 2.2 Kiểm tra tính duy nhất (Không có nhân viên nào bị trùng lặp dòng)
$resDup = $conn->query("SELECT employee_code, COUNT(*) as c FROM ot_yearly_accumulations WHERE year = {$testYear} GROUP BY employee_code HAVING c > 1");
assertTest($resDup->num_rows === 0, "Zero duplicate (employee_code, year) rows in ot_yearly_accumulations");

// 2.3 Kiểm tra nhân viên test có được cộng 8h vào T9 và tổng năm không
$resTestEmp = $conn->query("SELECT total_hours_m9, total_hours_year, remaining_hours, usage_percent, warning_level FROM ot_yearly_accumulations WHERE employee_code = '{$testEmp}' AND year = {$testYear}")->fetch_assoc();
assertTest(floatval($resTestEmp['total_hours_m9']) === 8.00, "Test employee has 8.00h in T9");
assertTest(floatval($resTestEmp['total_hours_year']) === 8.00, "Test employee has 8.00h in Total Year");
assertTest(floatval($resTestEmp['remaining_hours']) === 192.00, "Remaining hours correctly 192.00h");
assertTest(floatval($resTestEmp['usage_percent']) === 4.00, "Usage percent correctly 4.00%");
assertTest($resTestEmp['warning_level'] === 'green', "Warning level is 'green'");

// 2.4 Kiểm tra nhân viên chưa có giờ OT nào vẫn xuất hiện đầy đủ với 0h
$resZeroEmp = $conn->query("SELECT employee_code, total_hours_year, warning_level FROM ot_yearly_accumulations WHERE year = {$testYear} AND total_hours_year = 0 LIMIT 1")->fetch_assoc();
assertTest(!empty($resZeroEmp), "Employees with 0h OT appear in ot_yearly_accumulations (e.g. {$resZeroEmp['employee_code']} with 0h)");

// =========================================================================
// 3. DỌN DẸP DỮ LIỆU TEST
// =========================================================================
echo "\n[3] Cleaning up test data:\n";
$conn->query("DELETE FROM ot_actuals WHERE employee_code = '{$testEmp}' AND ot_date = '{$testDate}'");
$conn->query("DELETE FROM ot_reconciliations WHERE employee_code = '{$testEmp}' AND ot_date = '{$testDate}'");
$conn->query("DELETE FROM employees WHERE employee_code = '{$testEmp}'");
recalculateYearlyAccumulations($conn, $testYear);
assertTest(true, "Cleaned up test employee {$testEmp} and recalculated accumulations");

echo "\n=== RESULT: {$pass} PASSED, {$fail} FAILED ===\n";
if ($fail === 0) {
    echo "✓ ALL TESTS FOR ISSUES 1 & 2 PASSED 100%!\n";
} else {
    echo "✗ SOME TESTS FAILED!\n";
}
