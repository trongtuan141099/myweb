<?php
/**
 * Test Suite: HRM Employee Management, Export, and Resignation Date Grey-Out
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/leave_service.php';

echo "=== START TESTING HRM UPDATES ===\n\n";

// Mock admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_test';
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin_test',
    'fullname' => 'Admin Tester',
    'role' => 'admin',
    'permissions' => ['hrm.manage', 'hrm.view', 'hrm.leave_view', 'hrm.leave_manage', 'overtime.yearly', 'api.overtime.yearly']
];

$testCode = 'TEST999';

// Cleanup any old test record
$conn->query("DELETE FROM employees WHERE employee_code = '{$testCode}'");

// TEST 1: Thêm mới nhân viên với ngày nghỉ việc
echo "[TEST 1] Testing employee registration with resignation_date...\n";
$_POST = [
    'action'           => 'save',
    'is_edit'          => '0',
    'employee_code'    => $testCode,
    'full_name'        => 'Nguyễn Văn Test Nghỉ Việc',
    'gender'           => 'Nam',
    'job_level'        => 'W2',
    'cost_center'      => 'A00330',
    'work_group'       => 'Đùn TU',
    'work_shift'       => 'Ca 1',
    'hire_date'        => '2024-05-10',
    'resignation_date' => '2026-08-15'
];

ob_start();
include __DIR__ . '/../api/employee.php';
$saveOutput = ob_get_clean();
$saveRes = json_decode($saveOutput, true);

if ($saveRes && !empty($saveRes['success'])) {
    echo "  -> SUCCESS: Created test employee {$testCode}\n";
} else {
    echo "  -> FAILED to create test employee: " . $saveOutput . "\n";
    exit(1);
}

// TEST 2: Lấy thông tin chi tiết nhân viên (GET)
echo "\n[TEST 2] Testing employee detail fetch (action=get)...\n";
$_GET = ['action' => 'get', 'code' => $testCode];
$_POST = [];
ob_start();
include __DIR__ . '/../api/employee.php';
$getOutput = ob_get_clean();
$getRes = json_decode($getOutput, true);

if ($getRes && !empty($getRes['success']) && $getRes['data']['employee_code'] === $testCode) {
    echo "  -> SUCCESS: Retrieved employee info.\n";
    echo "     Full Name: " . $getRes['data']['full_name'] . "\n";
    echo "     Resignation Date: " . $getRes['data']['resignation_date'] . "\n";
    if ($getRes['data']['resignation_date'] !== '2026-08-15') {
        echo "  -> FAILED: Expected resignation_date '2026-08-15' but got '" . $getRes['data']['resignation_date'] . "'\n";
        exit(1);
    }
} else {
    echo "  -> FAILED to get employee detail: " . $getOutput . "\n";
    exit(1);
}

// TEST 3: Cập nhật thông tin nhân viên (Chỉnh sửa)
echo "\n[TEST 3] Testing employee edit (action=save, is_edit=1)...\n";
$_POST = [
    'action'           => 'save',
    'is_edit'          => '1',
    'employee_code'    => $testCode,
    'full_name'        => 'Nguyễn Văn Test Đã Cập Nhật',
    'gender'           => 'Nam',
    'job_level'        => 'M1',
    'cost_center'      => 'A00430',
    'work_group'       => 'Shotblast',
    'work_shift'       => 'Ca 2',
    'hire_date'        => '2024-05-10',
    'resignation_date' => '2026-09-01'
];
$_GET = [];

ob_start();
include __DIR__ . '/../api/employee.php';
$updateOutput = ob_get_clean();
$updateRes = json_decode($updateOutput, true);

if ($updateRes && !empty($updateRes['success'])) {
    echo "  -> SUCCESS: Updated employee {$testCode}\n";
} else {
    echo "  -> FAILED to update employee: " . $updateOutput . "\n";
    exit(1);
}

// Kiểm tra lại dữ liệu trong DB
$chk = $conn->query("SELECT * FROM employees WHERE employee_code = '{$testCode}'")->fetch_assoc();
if ($chk['full_name'] === 'Nguyễn Văn Test Đã Cập Nhật' && $chk['resignation_date'] === '2026-09-01' && $chk['job_level'] === 'M1') {
    echo "  -> DB Verification: PASSED! Values correctly updated in database.\n";
} else {
    echo "  -> DB Verification: FAILED! " . print_r($chk, true) . "\n";
    exit(1);
}

// TEST 4: Xuất Excel/CSV danh sách nhân viên
echo "\n[TEST 4] Testing Employee Excel Export (api/employee_export.php)...\n";
$exportScript = 'session_start(); $_SESSION[\'user\']=[\'role\'=>\'admin\']; $_GET=[\'status\'=>\'all\']; require \'api/employee_export.php\';';
$exportOutput = shell_exec('D:\myweb\php\php.exe -r "' . $exportScript . '"');

// Kiểm tra UTF-8 BOM
$hasBom = (substr($exportOutput, 0, 3) === "\xEF\xBB\xBF");
echo "  -> UTF-8 BOM Header Present: " . ($hasBom ? "YES (PASSED)" : "NO (FAILED)") . "\n";

// Kiểm tra dòng tiêu đề
if (strpos($exportOutput, 'Mã Nhân Viên') !== false && strpos($exportOutput, 'Ngày Nghỉ Việc') !== false && strpos($exportOutput, 'Trạng Thái Lao Động') !== false) {
    echo "  -> Header columns validation: PASSED\n";
} else {
    echo "  -> Header columns validation: FAILED\n";
    exit(1);
}

// Kiểm tra dòng chứa test employee
if (strpos($exportOutput, $testCode) !== false && strpos($exportOutput, '2026-09-01') !== false && strpos($exportOutput, 'Đã nghỉ việc') !== false) {
    echo "  -> Test employee exported with status 'Đã nghỉ việc': PASSED\n";
} else {
    echo "  -> Test employee export validation: FAILED\n";
    exit(1);
}

// TEST 5: Kiểm tra trả về resignation_date trong getMonthlyLeaveMatrixByEmployee (Tổng Hợp Phép HRM & 12 Tháng)
echo "\n[TEST 5] Testing Monthly Leave Matrix (getMonthlyLeaveMatrixByEmployee)...\n";
$matrixData = getMonthlyLeaveMatrixByEmployee($conn, 2026, '', '', $testCode);
if (!empty($matrixData['employees'])) {
    $empInMatrix = null;
    foreach ($matrixData['employees'] as $e) {
        if ($e['employee_code'] === $testCode) {
            $empInMatrix = $e;
            break;
        }
    }
    if ($empInMatrix) {
        echo "  -> Test employee found in monthly matrix.\n";
        echo "     resignation_date: " . $empInMatrix['resignation_date'] . "\n";
        echo "     has_resigned: " . ($empInMatrix['has_resigned'] ? 'true' : 'false') . "\n";
        if ($empInMatrix['has_resigned'] && $empInMatrix['resignation_date'] === '2026-09-01') {
            echo "  -> Resigned flag & date in Monthly Matrix: PASSED!\n";
        } else {
            echo "  -> Resigned flag validation: FAILED!\n";
            exit(1);
        }
    } else {
        echo "  -> Test employee not found in matrix results.\n";
        exit(1);
    }
} else {
    echo "  -> Matrix query returned empty.\n";
    exit(1);
}

// TEST 6: Kiểm tra trả về resignation_date trong getHrmLeavesSummaryData
echo "\n[TEST 6] Testing HRM Leave Summary Data (getHrmLeavesSummaryData)...\n";
// Tạo 1 bản ghi phép nghỉ thử nghiệm cho TEST999
$conn->query("INSERT INTO leave_actuals (employee_code, full_name, work_group, work_shift, leave_date, leave_days, leave_type, reason, synced_at)
              VALUES ('{$testCode}', 'Nguyễn Văn Test Đã Cập Nhật', 'Shotblast', 'Ca 2', '2026-09-01', 1.0, 'Phép năm', 'Test nghỉ phép', NOW())");
$leaveActualId = $conn->insert_id;

$summaryData = getHrmLeavesSummaryData($conn, 2026, 9, '', '', $testCode);
if (!empty($summaryData['data'])) {
    $rowInSummary = $summaryData['data'][0];
    echo "  -> Found record in leave summary.\n";
    echo "     resignation_date: " . ($rowInSummary['resignation_date'] ?? 'N/A') . "\n";
    if (!empty($rowInSummary['resignation_date']) && $rowInSummary['resignation_date'] === '2026-09-01') {
        echo "  -> Resigned date in Leave Summary Data: PASSED!\n";
    } else {
        echo "  -> Resigned date in Leave Summary Data: FAILED!\n";
        exit(1);
    }
} else {
    echo "  -> Summary query returned empty.\n";
    exit(1);
}

// TEST 7: Kiểm tra trả về resignation_date trong Kiểm Soát Giới Hạn Tăng Ca 200 Giờ/Năm
echo "\n[TEST 7] Testing Overtime Yearly Summary (api/overtime_yearly.php)...\n";
// Tạo 1 bản ghi lũy kế OT thử nghiệm cho TEST999 trong ot_yearly_accumulations
$conn->query("INSERT INTO ot_yearly_accumulations (year, employee_code, total_hours_year, remaining_hours, warning_level, usage_percent)
              VALUES (2026, '{$testCode}', 38.0, 162.0, 'green', 19.0)
              ON DUPLICATE KEY UPDATE total_hours_year = 38.0");

$_GET = ['action' => 'get_yearly_summary', 'year' => 2026, 'search' => $testCode];
ob_start();
include __DIR__ . '/../api/overtime_yearly.php';
$otOutput = ob_get_clean();
$otRes = json_decode($otOutput, true);

if ($otRes && !empty($otRes['success']) && !empty($otRes['data'])) {
    $empInOt = null;
    foreach ($otRes['data'] as $r) {
        if ($r['employee_code'] === $testCode) {
            $empInOt = $r;
            break;
        }
    }
    if ($empInOt) {
        echo "  -> Test employee found in OT yearly summary.\n";
        echo "     resignation_date: " . $empInOt['resignation_date'] . "\n";
        echo "     has_resigned: " . ($empInOt['has_resigned'] ? 'true' : 'false') . "\n";
        if ($empInOt['has_resigned'] && $empInOt['resignation_date'] === '2026-09-01') {
            echo "  -> Resigned flag & date in Overtime Yearly Summary: PASSED!\n";
        } else {
            echo "  -> Resigned flag in OT validation: FAILED!\n";
            exit(1);
        }
    } else {
        echo "  -> Test employee not found in OT data: FAILED!\n";
        exit(1);
    }
} else {
    echo "  -> OT API returned invalid response: " . $otOutput . "\n";
    exit(1);
}

// TEST 8: Xóa nhân viên thử nghiệm
echo "\n[TEST 8] Testing employee deletion (action=delete)...\n";
$_POST = ['action' => 'delete', 'employee_code' => $testCode];
$_GET = [];
ob_start();
include __DIR__ . '/../api/employee.php';
$delOutput = ob_get_clean();
$delRes = json_decode($delOutput, true);

if ($delRes && !empty($delRes['success'])) {
    echo "  -> SUCCESS: Deleted test employee {$testCode}\n";
} else {
    echo "  -> FAILED to delete employee: " . $delOutput . "\n";
    exit(1);
}

// Clean up extra test records
if ($leaveActualId) {
    $conn->query("DELETE FROM leave_actuals WHERE id = {$leaveActualId}");
}
$conn->query("DELETE FROM ot_yearly_accumulations WHERE employee_code = '{$testCode}'");

echo "\n=== ALL 8 TESTS PASSED SUCCESSFULLY! ===\n";
