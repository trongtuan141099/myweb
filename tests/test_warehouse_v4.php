<?php
/**
 * Test Suite V4: PDF Preview, ROP Alerts, Multi-Group, and Excel Export
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

function assertTest($name, $condition, $msg = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name: $msg\n";
    }
}

echo "=== KIỂM THỬ V4: PREVIEW PDF, ROP ALERTS, MULTI-GROUP & EXCEL EXPORTS ===\n\n";

// 1. Kiểm thử renderIssuePdfHtml
$res = $conn->query("SELECT id FROM warehouse_issues ORDER BY id DESC LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $issueId = $row['id'];
    $html = renderIssuePdfHtml($conn, $issueId);
    assertTest(
        "1. Render giao diện PDF HTML chuẩn A4 (Biểu mẫu BM-WH-XK-05)",
        strpos($html, 'BM-WH-XK-05') !== false && strpos($html, 'PHIẾU YÊU CẦU & XUẤT KHO VẬT TƯ') !== false,
        "Nội dung PDF HTML thiếu mã biểu mẫu hoặc tiêu đề phiếu"
    );
    assertTest(
        "2. Kiểm tra đủ 5 ô chữ ký mộc trong template PDF",
        strpos($html, '1. NGƯỜI LẬP PHIẾU') !== false &&
        strpos($html, '2. NGƯỜI KIỂM TRA') !== false &&
        strpos($html, '3. QUẢN LÝ PHÊ DUYỆT') !== false &&
        strpos($html, '4. THỦ KHO XUẤT HÀNG') !== false &&
        strpos($html, '5. NHẬN BÀN GIAO') !== false,
        "Thiếu một trong 5 ô chữ ký / dấu mộc"
    );
} else {
    assertTest("1. Render giao diện PDF HTML", false, "Không có phiếu xuất nào trong DB để kiểm tra");
}

// 2. Kiểm thử checkAndGenerateRopAlerts
$ropCount = checkAndGenerateRopAlerts($conn);
assertTest(
    "3. Quét tự động ROP (Reorder Point Alerts)",
    is_numeric($ropCount) && $ropCount >= 0,
    "Không thể chạy hàm checkAndGenerateRopAlerts"
);

// 3. Kiểm thử Đa nhóm (applicable_groups)
$testMat = $conn->query("SELECT id, item_code, group_name FROM warehouse_materials LIMIT 1")->fetch_assoc();
if ($testMat) {
    $saveRes = saveWarehouseMaterial($conn, [
        'id' => $testMat['id'],
        'group_name' => $testMat['group_name'],
        'applicable_groups' => ['Thiết bị', 'Bảo trì khuôn', 'Sản xuất']
    ]);
    assertTest("4. Lưu vật tư với đa nhóm (applicable_groups dạng array)", $saveRes['success'] === true);

    $checkMat = $conn->query("SELECT applicable_groups FROM warehouse_materials WHERE id = " . intval($testMat['id']))->fetch_assoc();
    $groups = array_filter(array_map('trim', explode(',', $checkMat['applicable_groups'] ?? '')));
    assertTest(
        "5. Cấu trúc dữ liệu đa nhóm lưu trữ chính xác",
        in_array('Thiết bị', $groups) && in_array('Bảo trì khuôn', $groups) && in_array('Sản xuất', $groups),
        "applicable_groups không chứa đủ các nhóm đã lưu: " . ($checkMat['applicable_groups'] ?? '')
    );
}

// 4. Kiểm thử API action=render_issue_pdf_html qua CLI subprocess có session
$cmd = 'd:\myweb\php\php.exe -r "session_start(); $_SESSION[\'user_id\'] = 1; $_SESSION[\'username\'] = \'admin\'; $_GET[\'action\'] = \'render_issue_pdf_html\'; $_GET[\'issue_id\'] = ' . ($issueId ?? 1) . '; include \'api/warehouse.php\';"';
$apiHtml = shell_exec($cmd);
assertTest(
    "6. API endpoint action=render_issue_pdf_html trả về HTML trực tiếp",
    $apiHtml !== null && strpos($apiHtml, '<!DOCTYPE html>') !== false && strpos($apiHtml, 'BM-WH-XK-05') !== false,
    "API render_issue_pdf_html không trả về mã HTML hợp lệ"
);

echo "\n=== KẾT QUẢ KIỂM THỬ: $passed/$total THÀNH CÔNG ===\n";
exit($passed === $total ? 0 : 1);
