<?php
// tests/test_leave_module.php
// Verification suite for HRM Leave Management System

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/leave_service.php';
require_once __DIR__ . '/../core/check_permission.php';

echo "=== START LEAVE MODULE TEST SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $testName\n";
        $passCount++;
    } else {
        echo "  [FAIL] $testName\n";
        $failCount++;
    }
}

// 1. Database schema verification
echo "1. Checking Database Columns:\n";
$colCheck = $conn->query("SHOW COLUMNS FROM employees LIKE 'work_shift'");
assertTest($colCheck && $colCheck->num_rows > 0, "employees.work_shift exists");

$colCheck2 = $conn->query("SHOW COLUMNS FROM leave_plans LIKE 'work_shift'");
assertTest($colCheck2 && $colCheck2->num_rows > 0, "leave_plans.work_shift exists");

$colCheck3 = $conn->query("SHOW COLUMNS FROM leave_actuals LIKE 'work_shift'");
assertTest($colCheck3 && $colCheck3->num_rows > 0, "leave_actuals.work_shift exists");

// 2. Core Service Tests
echo "\n2. Testing Leave Service Functions:\n";
$matrix = getLeaveTimelineMatrix($conn, 9, 2026, '', 'Ca 1');
assertTest(isset($matrix['groups']) && isset($matrix['total_days']), "getLeaveTimelineMatrix returns groups and total_days");
assertTest(isset($matrix['summary']['daily_actual_leaves']) && isset($matrix['summary']['daily_planned_leaves']), "getLeaveTimelineMatrix returns daily_actual_leaves and daily_planned_leaves summary");

// Check group structure for 2 color chips
if (!empty($matrix['groups'])) {
    $firstGroup = $matrix['groups'][0];
    assertTest(isset($firstGroup['daily_actual_leaves']) && isset($firstGroup['daily_planned_leaves']), "Group contains daily_actual_leaves and daily_planned_leaves");
}

$dashData = getLeaveDashboardData($conn, 9, 2026);
assertTest(isset($dashData['kpi']) && isset($dashData['monthly_trend']), "getLeaveDashboardData returns KPI and monthly_trend");
assertTest(isset($dashData['dept_distribution']) && isset($dashData['top_employees']), "getLeaveDashboardData returns dept_distribution and top_employees");
assertTest(isset($dashData['leave_types']) && isset($dashData['critical_employees']), "getLeaveDashboardData returns leave_types and critical_employees");

$summaryData = getHrmLeavesSummaryData($conn, 2026, 9, '', '', '', 1, 20);
assertTest(isset($summaryData['data']) && isset($summaryData['total']), "getHrmLeavesSummaryData returns data and total");

$monthlyMatrix = getMonthlyLeaveMatrixByEmployee($conn, 2026, '', '', '');
assertTest(isset($monthlyMatrix['employees']), "getMonthlyLeaveMatrixByEmployee returns employees");

// 3. Permission and Roles Config
echo "\n3. Testing Roles & Permissions Config:\n";
$permConfig = require __DIR__ . '/../config/permission.php';
$rolesMap = $permConfig['roles_map'];
assertTest(isset($rolesMap['admin']) && in_array('hrm.leave_view', $rolesMap['admin']), "Admin role has hrm.leave_view");
assertTest(isset($rolesMap['admin']) && in_array('hrm.leave_group_manage', $rolesMap['admin']), "Admin role has hrm.leave_group_manage");
assertTest(isset($rolesMap['admin']) && in_array('hrm.leave_sync', $rolesMap['admin']), "Admin role has hrm.leave_sync");
assertTest(isset($rolesMap['editor']) && in_array('hrm.leave_view', $rolesMap['editor']), "Editor role has hrm.leave_view");
assertTest(isset($rolesMap['viewer']) && in_array('hrm.leave_view', $rolesMap['viewer']), "Viewer role has hrm.leave_view");
assertTest(isset($rolesMap['viewer']) && !in_array('hrm.leave_sync', $rolesMap['viewer']), "Viewer role DOES NOT have hrm.leave_sync");
assertTest(isset($rolesMap['viewer']) && !in_array('hrm.leave_group_manage', $rolesMap['viewer']), "Viewer role DOES NOT have hrm.leave_group_manage");

// 4. UI Structure and Sticky Header & Color chips verification
echo "\n4. Testing UI Markup and CSS Features:\n";
$leavePhp = file_get_contents(__DIR__ . '/../modules/hrm/leave_management.php');

assertTest(strpos($leavePhp, '.timeline-table thead tr:first-child th') !== false, "Sticky header row 1 (Date) CSS exists");
assertTest(strpos($leavePhp, '.timeline-table thead tr:nth-child(2) th') !== false, "Sticky header row 2 (Day of Week) CSS exists");
assertTest(strpos($leavePhp, 'border-collapse: separate') !== false, "Timeline table uses separate border-collapse for sticky support");
assertTest(strpos($leavePhp, 'col-today-highlight') !== false, "Today's column highlight class exists");
assertTest(strpos($leavePhp, 'manpower-chip-act') !== false, "Actual leave chip class exists");
assertTest(strpos($leavePhp, 'manpower-chip-plan') !== false, "Planned leave chip class exists");
assertTest(strpos($leavePhp, 'filterShift') !== false, "Shift filter select exists");
assertTest(strpos($leavePhp, 'filterGroup') !== false, "Group filter select exists");
assertTest(strpos($leavePhp, 'openEditSingleEmployeeModal') !== false, "Single employee allocation edit function exists");
assertTest(strpos($leavePhp, 'modalWorkGroupMatrix') !== false, "Allocation & direct shift config modal exists");
assertTest(strpos($leavePhp, 'matrixBulkTargetShift') !== false, "Direct shift config in allocation modal exists");
assertTest(strpos($leavePhp, 'tabBtnDashboard') !== false, "Leave Dashboard tab exists");
assertTest(strpos($leavePhp, 'tabBtnSummary') !== false, "HRM Summary tab exists");
assertTest(strpos($leavePhp, 'loadMonthlyMatrixData') !== false, "Monthly 12-month matrix loader exists");

echo "\n=== SUMMARY: $passCount Passed, $failCount Failed ===\n";
