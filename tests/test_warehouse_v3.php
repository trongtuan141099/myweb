<?php
/**
 * Test Suite: Warehouse v3 Enhancements Verification
 * DX Plastic Group - Automated Verification Script
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/warehouse_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
}

echo "=== BẮT ĐẦU KIỂM THỬ NÂNG CẤP V3 MODULE QUẢN LÝ KHO (XUẤT VẬT TƯ) ===\n\n";

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

$sampleUser = [
    'id' => 1,
    'username' => 'admin',
    'fullname' => 'Quản trị viên',
    'role' => 'admin'
];

// 1. Kiểm tra lưu vật tư hỗ trợ nhiều nhóm (applicable_groups)
$resMat = $conn->query("SELECT id, item_code, group_name FROM warehouse_materials LIMIT 1");
if (!$resMat) {
    die("Query error: " . $conn->error . "\n");
}
$matRow = $resMat->fetch_assoc();
$testMatId = $matRow['id'];

$saveResult = saveWarehouseMaterial($conn, [
    'id' => $testMatId,
    'item_code' => $matRow['item_code'],
    'group_name' => 'Thiết bị',
    'applicable_groups' => 'Thiết bị,Sản xuất,Khuôn',
    'stock_current' => 100.0,
    'reorder_point' => 15.0,
    'reorder_qty' => 50.0,
    'norm_per_use' => 1.5,
    'packaging_spec' => 'Chai 1L',
    'pack_quantity' => 1.0,
    'unit' => 'Pcs'
], $sampleUser);
assertTest("1. Cập nhật thông tin vật tư & nhiều nhóm (applicable_groups)", $saveResult['success'] === true, $saveResult['message'] ?? '');

// 2. Kiểm tra truy vấn đề xuất theo nhóm phụ (Multi-group suggestion)
$suggSx = getConsumableSuggestionsByGroup($conn, 'Sản xuất', 8, 2026);
$foundInSx = false;
foreach ($suggSx as $s) {
    if ($s['id'] == $testMatId) {
        $foundInSx = true;
        break;
    }
}
assertTest("2. Vật tư đa nhóm hiển thị đúng trong danh sách đề xuất của nhóm phụ (Sản xuất)", $foundInSx, "ID {$testMatId} found: " . ($foundInSx ? 'YES' : 'NO'));

// 3. Kiểm tra ràng buộc tồn kho thực tế: Không cho phép tạo phiếu khi vượt quá tồn kho khả dụng
$issueOverStock = [
    'group_name' => 'Thiết bị',
    'issue_type' => 'consumable',
    'month' => 8,
    'year' => 2026,
    'department' => 'Xưởng Ép nhựa',
    'purpose' => 'Test overstock constraint',
    'notes' => 'Should fail',
    'items' => [
        [
            'material_id' => $testMatId,
            'machines_count' => 100, // 100 * 10 * 1.5 = 1500 > 100.0
            'uses_per_machine' => 10,
            'norm_per_use' => 1.5,
            'field_stock' => 0,
            'reusable_stock' => 0,
            'reason' => 'Vượt tồn kho'
        ]
    ]
];
$resOver = createIssueRequest($conn, $issueOverStock, $sampleUser);
assertTest("3. Hệ thống chặn tạo phiếu khi số lượng xuất vượt tồn kho khả dụng", $resOver['success'] === false, $resOver['message'] ?? '');

// 4. Kiểm tra tính năng 'Báo Thủ Kho' (createStockCheckRequest)
$reqStock = createStockCheckRequest($conn, $testMatId, 'stock_check', 500.0, 'Cần gấp cho dây chuyền ép số 2', $sampleUser);
assertTest("4. Gửi yêu cầu kiểm tra/bổ sung kho ('Báo Thủ Kho') thành công", $reqStock['success'] === true, $reqStock['message'] ?? '');
$alertId = $reqStock['alert_id'] ?? 0;

// 5. Kiểm tra danh sách theo dõi đặt hàng ROP (getReorderTrackingList)
$trackPending = getReorderTrackingList($conn, ['order_status' => 'cho_dat_hang']);
$foundAlert = false;
foreach ($trackPending as $item) {
    if ($item['id'] == $alertId) {
        $foundAlert = true;
        break;
    }
}
assertTest("5. Yêu cầu báo thủ kho xuất hiện trong Tab 'Chờ đặt hàng'", $foundAlert, "Alert ID: {$alertId}");

// 6. Cập nhật sang trạng thái 'da_dat_hang' (Đã đặt hàng) kèm PO code, NCC, ngày giao dự kiến
$updateOrdered = updateReorderOrderStatus($conn, $alertId, 'da_dat_hang', [
    'po_code' => 'PO-2026-TEST-001',
    'supplier_name' => 'Công ty TNHH Nhựa & Thiết bị Hà Nội',
    'ordered_qty' => 500.0,
    'expected_delivery_date' => '2026-09-30'
], $sampleUser);
assertTest("6. Chuyển trạng thái sang 'Đã đặt hàng' và lưu thông tin PO, NCC", $updateOrdered['success'] === true, $updateOrdered['message'] ?? '');

// 7. Cập nhật sang trạng thái 'da_giao_hang' (Đã nhận hàng) và Tự động cộng tồn kho
$matBeforeDelivery = getMaterialDetail($conn, $testMatId);
$stockBeforeDelivery = floatval($matBeforeDelivery['stock_current']);

$updateDelivered = updateReorderOrderStatus($conn, $alertId, 'da_giao_hang', [
    'auto_add_stock' => 1
], $sampleUser);
assertTest("7. Cập nhật trạng thái 'Đã nhận hàng' thành công", $updateDelivered['success'] === true, $updateDelivered['message'] ?? '');

$matAfterDelivery = getMaterialDetail($conn, $testMatId);
$stockAfterDelivery = floatval($matAfterDelivery['stock_current']);
assertTest("8. Tự động cộng tồn kho khi chọn 'Cộng số lượng vào tồn kho hiện tại'", $stockAfterDelivery == ($stockBeforeDelivery + 500.0), "Trước: {$stockBeforeDelivery}, Sau: {$stockAfterDelivery} (Dự kiến: " . ($stockBeforeDelivery + 500.0) . ")");

// 8. Tạo phiếu xuất kho thành công sau khi đã có thêm tồn kho
$issueSuccessData = [
    'group_name' => 'Thiết bị',
    'issue_type' => 'consumable',
    'month' => 8,
    'year' => 2026,
    'department' => 'Xưởng Ép nhựa',
    'purpose' => 'Kiểm tra phiếu xuất kho v3',
    'notes' => 'Đầy đủ tồn kho sau khi nhập',
    'items' => [
        [
            'material_id' => $testMatId,
            'machines_count' => 10,
            'uses_per_machine' => 2,
            'norm_per_use' => 1.0,
            'field_stock' => 0,
            'reusable_stock' => 0,
            'reason' => 'Định mức sản xuất'
        ]
    ]
];
$resValidIssue = createIssueRequest($conn, $issueSuccessData, $sampleUser);
assertTest("9. Tạo phiếu xuất kho thành công khi tồn kho đầy đủ", $resValidIssue['success'] === true, $resValidIssue['message'] ?? '');
$newIssueId = $resValidIssue['issue_id'] ?? 0;

// 9. Duyệt qua các bước phê duyệt
$resStep1 = processApprovalStep($conn, $newIssueId, 'checker', 'approve', 'Checker OK', $sampleUser);
$resStep2 = processApprovalStep($conn, $newIssueId, 'manager', 'approve', 'Manager OK', $sampleUser);
$resStep3 = processApprovalStep($conn, $newIssueId, 'admin_issue', 'approve', 'Admin Issue OK', $sampleUser);
assertTest("10. Thực hiện quy trình phê duyệt 3 cấp thành công", $resStep3['success'] === true, $resStep3['message'] ?? '');

// 10. Kiểm tra thông tin chữ ký / dấu phê duyệt trong getIssueDetail
$detailIssue = getIssueDetail($conn, $newIssueId);
assertTest("11. Chi tiết phiếu ghi nhận đầy đủ logs và thông tin ký duyệt", !empty($detailIssue['logs']), "Logs count: " . count($detailIssue['logs']));

echo "\n=== KẾT QUẢ KIỂM THỬ: {$testsPassed}/{$totalTests} KIỂM THỬ THÀNH CÔNG ===\n";
if ($testsPassed === $totalTests) {
    echo ">>> TẤT CẢ TÍNH NĂNG V3 MODULE KHO ĐÃ ĐẠT CHUẨN 100%! <<<\n";
}

