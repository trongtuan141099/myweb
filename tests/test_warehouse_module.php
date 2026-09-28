<?php
/**
 * Test Suite: Quản Lý Kho (Xuất Vật Tư)
 * DX Plastic Group - Automated Verification Script
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/warehouse_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
}

echo "=== BẮT ĐẦU KIỂM THỬ MODULE QUẢN LÝ KHO (XUẤT VẬT TƯ) ===\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest($name, $condition, $details = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] {$name}\n";
    } else {
        echo " [FAIL] {$name} - Details: {$details}\n";
    }
}

// 1. Kiểm tra vật tư và dòng lịch sử đã được seed chuẩn từ Excel T8.xlsm
$resMat = $conn->query("SELECT COUNT(*) as tot FROM warehouse_materials");
$matCount = $resMat->fetch_assoc()['tot'];
assertTest("1. Tổng số vật tư trong kho >= 40 (Sheet T8)", $matCount >= 40, "Hiện có: {$matCount}");

$resHist = $conn->query("SELECT COUNT(*) as tot FROM warehouse_material_history");
$histCount = $resHist->fetch_assoc()['tot'];
assertTest("2. Tổng số bản ghi lịch sử tiêu hao 3 tháng >= 120", $histCount >= 120, "Hiện có: {$histCount}");

// 2. Kiểm tra danh mục vật tư theo nhóm và auto-suggestion
$suggestions = getConsumableSuggestionsByGroup($conn, 'Thiết bị', 8, 2026);
assertTest("3. Đề xuất tự động (Auto-suggestion) cho Nhóm Thiết bị trả về danh sách hợp lệ", count($suggestions) > 0, "Số lượng: " . count($suggestions));

// 3. Kiểm tra công thức tính toán vật tư tiêu hao (A x B x C và quy cách đóng gói ceiling)
$testMat = getMaterialDetail($conn, $suggestions[0]['id']);
// Giả lập: A = 10 máy, B = 2 lần/máy, C = norm_per_use, Tồn HT = 1, Tái SD = 0
$calc = calculateItemQuantities($testMat, 10, 2, $testMat['norm_per_use'], 1, 0);
assertTest("4. Tính tổng số lần sử dụng A x B = 20", $calc['total_uses'] == 20, "Total uses: " . $calc['total_uses']);
$expectedNetTheo = max(0, 20 * $testMat['norm_per_use'] - 1);
assertTest("5. Tính SL lý thuyết ròng đúng công thức (A x B x C - Tồn HT)", $calc['net_theoretical_qty'] == $expectedNetTheo, "Net Theo: " . $calc['net_theoretical_qty']);
assertTest("6. Quy đổi số lượng xuất thực tế theo quy cách đóng gói (pack_spec)", $calc['actual_qty'] >= $calc['net_theoretical_qty'], "Actual: " . $calc['actual_qty']);

// 4. Kiểm tra tạo phiếu xuất kho tiêu hao (Step 1: Khởi tạo)
$conn->query("UPDATE warehouse_materials SET stock_current = 100 WHERE id = " . intval($testMat['id']));
$testMat = getMaterialDetail($conn, $testMat['id']);

$sampleUser = [
    'id' => 1,
    'username' => 'admin',
    'fullname' => 'Quản trị viên',
    'role' => 'admin'
];

$issueData = [
    'group_name' => 'Thiết bị',
    'issue_type' => 'consumable',
    'month' => 8,
    'year' => 2026,
    'department' => 'Xưởng Ép nhựa',
    'purpose' => 'Kiểm tra xuất vật tư tiêu hao định mức tự động',
    'notes' => 'Test ticket',
    'items' => [
        [
            'material_id' => $testMat['id'],
            'machines_count' => 1000,
            'uses_per_machine' => 2,
            'norm_per_use' => $testMat['norm_per_use'],
            'field_stock' => 0,
            'reusable_stock' => 0,
            'reason' => 'Định mức tháng 8'
        ]
    ]
];

$resCreate = createIssueRequest($conn, $issueData, $sampleUser);
assertTest("7. Khởi tạo phiếu xuất kho tiêu hao thành công", $resCreate['success'] === true, $resCreate['message'] ?? '');
$newIssueId = $resCreate['issue_id'] ?? 0;
$newIssueCode = $resCreate['issue_code'] ?? '';

// Kiểm tra trạng thái ban đầu là pending_checker
$issueRow = getIssueDetail($conn, $newIssueId);
assertTest("8. Trạng thái ban đầu của phiếu là 'pending_checker'", $issueRow['status'] === 'pending_checker', $issueRow['status']);

// 5. Kiểm tra Bước 2: Người kiểm tra phê duyệt (Checker)
$resChecker = processApprovalStep($conn, $newIssueId, 'checker', 'approve', 'Đã kiểm tra định mức hợp lệ', $sampleUser);
assertTest("9. Người kiểm tra phê duyệt chuyển sang 'pending_manager'", $resChecker['success'] === true && $resChecker['new_status'] === 'pending_manager', $resChecker['message'] ?? '');

// 6. Kiểm tra Bước 3: Quản lý phê duyệt (Manager)
$resManager = processApprovalStep($conn, $newIssueId, 'manager', 'approve', 'Quản lý duyệt xuất hàng', $sampleUser);
assertTest("10. Quản lý phê duyệt chuyển sang 'pending_admin_issue'", $resManager['success'] === true && $resManager['new_status'] === 'pending_admin_issue', $resManager['message'] ?? '');

// 7. Kiểm tra Bước 4: Admin làm thủ tục xuất kho & Trừ tồn kho thực tế (Admin Issue)
$stockBefore = floatval($testMat['stock_current']);
$resAdmin = processApprovalStep($conn, $newIssueId, 'admin_issue', 'approve', 'Thủ kho đã soạn và xuất hàng', $sampleUser);
assertTest("11. Admin xuất kho chuyển sang 'pending_handover'", $resAdmin['success'] === true && $resAdmin['new_status'] === 'pending_handover', $resAdmin['message'] ?? '');

// Kiểm tra tồn kho đã bị trừ
$updatedMat = getMaterialDetail($conn, $testMat['id']);
$stockAfter = floatval($updatedMat['stock_current']);
assertTest("12. Tồn kho thực tế của vật tư đã bị trừ sau khi Admin xuất kho", $stockAfter < $stockBefore, "Trước: {$stockBefore}, Sau: {$stockAfter}");

// 8. Kiểm tra Bước 5: Bàn giao hiện trường (Handover & completed)
$extraHandover = [
    'receiver_name' => 'Nguyễn Văn Nghiệm',
    'receiver_code' => 'EMP-009988'
];
$resHandover = processApprovalStep($conn, $newIssueId, 'handover', 'approve', 'Hiện trường đã nhận đủ số lượng', $sampleUser, $extraHandover);
assertTest("13. Xác nhận bàn giao hoàn tất quy trình (status: 'completed')", $resHandover['success'] === true && $resHandover['new_status'] === 'completed', $resHandover['message'] ?? '');

// Kiểm tra log ghi nhận đầy đủ người nhận bàn giao
$completedIssue = getIssueDetail($conn, $newIssueId);
$logs = $completedIssue['logs'];
$handoverLog = end($logs);
assertTest("14. Lưu trữ thông tin người nhận bàn giao hiện trường trong log", $handoverLog['handover_receiver_name'] === 'Nguyễn Văn Nghiệm', $handoverLog['handover_receiver_name'] ?? '');

// 9. Kiểm tra tạo phiếu Bất thường (Yêu cầu bắt buộc lý do)
$irregularDataNoReason = [
    'group_name' => 'Sản xuất',
    'issue_type' => 'irregular',
    'month' => 8,
    'year' => 2026,
    'department' => 'Sản xuất',
    'purpose' => 'Test',
    'items' => [
        [
            'material_id' => $testMat['id'],
            'requested_qty' => 5,
            'reason' => '' // Thiếu lý do
        ]
    ]
];
$resIrregFail = createIssueRequest($conn, $irregularDataNoReason, $sampleUser);
assertTest("15. Bắt buộc nhập lý do đối với vật tư xuất bất thường", $resIrregFail['success'] === false, "Kết quả trả về success = " . ($resIrregFail['success'] ? 'true' : 'false'));

$irregularDataValid = [
    'group_name' => 'Sản xuất',
    'issue_type' => 'irregular',
    'month' => 8,
    'year' => 2026,
    'department' => 'Sản xuất',
    'purpose' => 'Xuất thay thế khuôn vỡ khẩn cấp ca đêm',
    'items' => [
        [
            'material_id' => $testMat['id'],
            'requested_qty' => 5,
            'reason' => 'Sự cố vỡ chốt dẫn hướng khuôn ép số 4'
        ]
    ]
];
$resIrregSuccess = createIssueRequest($conn, $irregularDataValid, $sampleUser);
assertTest("16. Tạo phiếu xuất bất thường thành công khi có lý do", $resIrregSuccess['success'] === true, $resIrregSuccess['message'] ?? '');

// 10. Kiểm tra Dashboard API & Thống kê
$dash = getWarehouseDashboardStats($conn, 8, 2026);
assertTest("17. Dashboard tính toán KPI phiếu xuất trong tháng", isset($dash['kpi']['total_issues']) && $dash['kpi']['total_issues'] > 0, "Total issues: " . ($dash['kpi']['total_issues'] ?? 0));
assertTest("18. Dashboard trả về xu hướng tiêu hao qua các tháng", count($dash['trend']['months']) >= 4, "Số tháng: " . count($dash['trend']['months']));
assertTest("19. Dashboard trả về cơ cấu tiêu hao theo 4 nhóm", count($dash['group_breakdown']['labels']) === 4, "Số nhóm: " . count($dash['group_breakdown']['labels']));
assertTest("20. Dashboard trả về Top 10 mặt hàng tiêu hao", isset($dash['top_materials']), "Top materials loaded");

echo "\n=== KẾT QUẢ KIỂM THỬ: {$testsPassed}/{$totalTests} KIỂM THỬ THÀNH CÔNG ===\n";
if ($testsPassed === $totalTests) {
    echo ">>> TẤT CẢ CÁC KIỂM THỬ LOGIC NGHIỆP VỤ ĐÃ ĐẠT CHUẨN 100%! <<<\n";
}
