<?php
/**
 * Test Suite V5: API Actions, Issue Deletion, UPSERT Material Creation & Import Template
 * DX Plastic Group - Factory Management System
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/warehouse_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
}

$passed = 0;
$total = 0;

function assertV5($name, $condition, $msg = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name: $msg\n";
    }
}

echo "=== KIỂM THỬ V5: BACKEND API ACTIONS, XÓA PHIẾU, UPSERT & IMPORT TEMPLATE ===\n\n";

// 1. Kiểm thử action create_stock_request
$testMat = $conn->query("SELECT id, item_code, item_name_vn FROM warehouse_materials LIMIT 1")->fetch_assoc();
$stockReqRes = createStockCheckRequest($conn, $testMat['id'], 'stock_check', 25.0, 'Kiểm tra tồn kho thử nghiệm V5', [
    'id' => 1,
    'username' => 'admin',
    'fullname' => 'Administrator'
]);
assertV5(
    "1. Báo thủ kho (createStockCheckRequest) lưu bản ghi cảnh báo thành công",
    $stockReqRes['success'] === true && !empty($stockReqRes['alert_id']),
    $stockReqRes['message'] ?? 'Lỗi không xác định'
);
if (!empty($stockReqRes['alert_id'])) {
    $conn->query("DELETE FROM warehouse_reorder_alerts WHERE id = " . intval($stockReqRes['alert_id']));
}

// 2. Kiểm thử UPSERT tạo mới vật tư (id <= 0)
$newCode = 'VT-V5-NEW-' . time();
$createRes = saveWarehouseMaterial($conn, [
    'id' => 0,
    'item_code' => $newCode,
    'item_name_vn' => 'Vật tư kiểm thử V5 Mới',
    'item_name_en' => 'Test Material V5 New',
    'group_name' => 'Thiết bị',
    'applicable_groups' => ['Thiết bị', 'Sản xuất'],
    'unit' => 'Cái',
    'stock_current' => 50,
    'reorder_point' => 10,
    'reorder_qty' => 30
]);
assertV5(
    "2. Đăng ký mới vật tư (saveWarehouseMaterial with id=0) thành công",
    $createRes['success'] === true && !empty($createRes['id']),
    $createRes['message'] ?? 'Thất bại'
);

// 3. Kiểm thử UPSERT cập nhật vật tư trùng mã (id <= 0, item_code đã có)
$upsertRes = saveWarehouseMaterial($conn, [
    'id' => 0,
    'item_code' => $newCode,
    'item_name_vn' => 'Vật tư kiểm thử V5 Đã Cập Nhật',
    'group_name' => 'Thiết bị',
    'stock_current' => 88
]);
assertV5(
    "3. Cập nhật vật tư trùng mã theo cơ chế UPSERT",
    $upsertRes['success'] === true && $upsertRes['id'] == $createRes['id'],
    $upsertRes['message'] ?? 'Thất bại'
);

// Kiểm tra lại tồn kho trong DB sau UPSERT
$checkStock = $conn->query("SELECT stock_current FROM warehouse_materials WHERE item_code = '{$newCode}'")->fetch_assoc();
assertV5(
    "4. Số lượng tồn kho sau UPSERT chính xác (88)",
    floatval($checkStock['stock_current']) == 88.0,
    "Giá trị tồn kho: " . ($checkStock['stock_current'] ?? 'null')
);

// Xóa vật tư thử nghiệm
$conn->query("DELETE FROM warehouse_materials WHERE item_code = '{$newCode}'");

// 5. Kiểm thử Tạo phiếu xuất kho & Xóa phiếu (deleteWarehouseIssue)
$matTb = $conn->query("SELECT id, item_code, item_name_vn FROM warehouse_materials WHERE group_name = 'Thiết bị' AND stock_current > 5 LIMIT 1")->fetch_assoc();
$createIssueRes = createIssueRequest($conn, [
    'group_name' => 'Thiết bị',
    'issue_type' => 'consumable',
    'month' => 8,
    'year' => 2026,
    'purpose' => 'Kiểm thử xóa phiếu V5',
    'items' => [
        [
            'material_id' => $matTb['id'],
            'item_code' => $matTb['item_code'],
            'item_name_vn' => $matTb['item_name_vn'],
            'unit' => 'Ea',
            'machines_count' => 1,
            'uses_per_machine' => 1,
            'norm_per_use' => 1,
            'field_stock' => 0,
            'reusable_stock' => 0,
            'actual_qty' => 1
        ]
    ]
], [
    'id' => 1,
    'username' => 'admin',
    'fullname' => 'Administrator',
    'role' => 'admin'
]);
$testIssueId = $createIssueRes['issue_id'] ?? 0;
assertV5("5. Tạo phiếu xuất kho thử nghiệm thành công (Issue #$testIssueId)", $testIssueId > 0, $createIssueRes['message'] ?? '');

if ($testIssueId > 0) {
    $delRes = deleteWarehouseIssue($conn, $testIssueId, ['id' => 1, 'username' => 'admin', 'role' => 'admin']);
    assertV5(
        "6. Xóa phiếu yêu cầu xuất kho (deleteWarehouseIssue) thành công",
        $delRes['success'] === true,
        $delRes['message'] ?? 'Lỗi xóa'
    );

    // Kiểm tra phiếu đã biến mất khỏi DB
    $checkDel = $conn->query("SELECT id FROM warehouse_issues WHERE id = {$testIssueId}")->num_rows;
    assertV5("7. Phiếu xuất và các bảng liên quan đã được dọn sạch khỏi CSDL", $checkDel === 0);
}

// 8. Kiểm thử Import CSV hàng loạt (UPSERT)
$tmpCsv = tempnam(sys_get_temp_dir(), 'wh_import_') . '.csv';
$csvData = "\xEF\xBB\xBF" . "STT,Mã Vật Tư (*),Tên Tiếng Việt (*),Tên Tiếng Anh,Nhóm Chính (*),Các Nhóm Áp Dụng,Phân Loại,Đơn Vị Tính (*),Kệ BIN,Quy Cách Đóng Gói,SL Quy Đổi/Gói,Tồn Kho Hiện Tại (*),Điểm Đặt Hàng (ROP),Lượng Đặt Tối Thiểu (MOQ),Định Mức Máy (A),Định Mức Lần (B),Tiêu Hao/Lần (C)\n";
$codeImportNew = 'VT-IMP-NEW-' . time();
$csvData .= "1,{$codeImportNew},Vật tư Import Mới,Import Material New,Sản xuất,Sản xuất,consumable,Bao,BIN-IMP-01,25kg/bao,1,150.0,30.0,50.0,5,1,1.0\n";
file_put_contents($tmpCsv, $csvData);

$importRes = importMaterialsStock($conn, $tmpCsv, ['username' => 'admin']);
assertV5(
    "8. Import dữ liệu tồn kho hàng loạt theo cơ chế UPSERT",
    $importRes['success'] === true && $importRes['inserted_count'] >= 1,
    $importRes['message'] ?? 'Import thất bại'
);

// Dọn dẹp file và bản ghi import test
unlink($tmpCsv);
$conn->query("DELETE FROM warehouse_materials WHERE item_code = '{$codeImportNew}'");

// 9. Kiểm thử API endpoint action=download_material_import_template qua CLI subprocess
$cmd = 'd:\myweb\php\php.exe -r "session_start(); $_SESSION[\'user_id\'] = 1; $_SESSION[\'username\'] = \'admin\'; $_GET[\'action\'] = \'download_material_import_template\'; include \'api/warehouse.php\';"';
$csvTemplate = shell_exec($cmd);
assertV5(
    "9. API action=download_material_import_template trả về file CSV mẫu chuẩn",
    $csvTemplate !== null && strpos($csvTemplate, 'Mã Vật Tư (*)') !== false && strpos($csvTemplate, 'VT-TB-001') !== false
);

// 10. Kiểm thử API action=export_materials_excel qua CLI subprocess
$cmdExp = 'd:\myweb\php\php.exe -r "session_start(); $_SESSION[\'user_id\'] = 1; $_SESSION[\'username\'] = \'admin\'; $_GET[\'action\'] = \'export_materials_excel\'; include \'api/warehouse.php\';"';
$csvMat = shell_exec($cmdExp);
assertV5(
    "10. API action=export_materials_excel xuất danh mục vật tư thành công",
    $csvMat !== null && strpos($csvMat, 'DANH MỤC VẬT TƯ & TỒN KHO HIỆN TẠI') !== false
);

echo "\n=== KẾT QUẢ KIỂM THỬ V5: $passed/$total THÀNH CÔNG ===\n";
exit($passed === $total ? 0 : 1);
