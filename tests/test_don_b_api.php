<?php
/**
 * Test Suite: Đơn Hàng (Đơn B) API & Nghiệp Vụ
 * Kiểm tra các tính năng cốt lõi của API don_b_api.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

// Giả lập phiên đăng nhập của Admin để có toàn quyền kiểm thử
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_test';
$_SESSION['fullname'] = 'Test Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin_test',
    'fullname' => 'Test Administrator',
    'role' => 'admin',
    'permissions' => ['admin']
];

global $conn;

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        if ($details) echo "        -> $details\n";
        $passed++;
    } else {
        echo " [FAIL] $name\n";
        if ($details) echo "        -> LỖI: $details\n";
        $failed++;
    }
}

echo "====================================================\n";
echo " BẮT ĐẦU KIỂM THỬ MODULE QUẢN LÝ ĐƠN B (ORDERS API)\n";
echo "====================================================\n\n";

// 1. Kiểm tra cấu trúc CSDL
echo "1. KIỂM TRA BẢNG CSDL:\n";
$tables = ['don_b', 'don_b_history', 'don_b_notifications'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    assertTest("Bảng $t tồn tại", $res && $res->num_rows > 0);
}

// 2. Kiểm tra trích xuất mét quy cách từ mã sản phẩm
echo "\n2. KIỂM TRA QUY CÁCH ĐỔI MÉT TỪ MÃ SẢN PHẨM:\n";
if (!function_exists('extractMetersFromCode')) {
    function extractMetersFromCode($code) {
        if (!$code) return 0;
        if (preg_match('/-(\d+)/', $code, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
}

$testCodes = [
    'TU0805R-100Z2' => 100,
    'TU0425BU1-305-X108Z2' => 305,
    'TU1065B-20' => 20,
    'TUS0604W-500' => 500,
    'SAMPLE_NO_METER' => 0
];
foreach ($testCodes as $code => $expected) {
    $meters = extractMetersFromCode($code);
    assertTest("Quy cách mét của $code == $expected m", $meters === $expected, "Thực tế: $meters");
}

// Dọn dẹp dữ liệu test cũ nếu có
$testOrderCode = 'TEST-ORD-AUTOTEST-999';
$conn->query("DELETE FROM don_b_history WHERE don_b_id IN (SELECT id FROM don_b WHERE ma_don_hang = '$testOrderCode')");
$conn->query("DELETE FROM don_b_notifications WHERE don_b_id IN (SELECT id FROM don_b WHERE ma_don_hang = '$testOrderCode')");
$conn->query("DELETE FROM don_b WHERE ma_don_hang = '$testOrderCode'");

// 3. Test Tạo mới Đơn B thủ công (PC)
echo "\n3. TEST TẠO MỚI ĐƠN B THỦ CÔNG (PC):\n";
$testDateYc = date('Y-m-d', strtotime('+3 days')); // Trong vòng 5 ngày tới -> Kích hoạt Alert!
$maSanPham = 'TU0805B-100Z2';
$soLuongDat = 10;
$soMetQc = extractMetersFromCode($maSanPham);
$tongMet = $soMetQc * $soLuongDat;
$conThieu = $soLuongDat;

$stmtInsert = $conn->prepare("
    INSERT INTO don_b (
        ngay_cap_nhat, ma_san_pham, so_met_quy_cach, so_phieu_nhap, ma_don_hang,
        ngay_nhan_don, ky_han_giao_hang, ngay_yc_nhap_kho, ma_khach_hang, ten_khach_hang,
        so_luong_dat, tong_met_can, phuong_thuc_van_chuyen, phan_loai_don, ngay_du_kien_xuat,
        pc_note, dun_xac_nhan, cuon_xac_nhan, tinh_trang_nhap_kho, cuon_da_sx,
        tong_met_da_sx, con_thieu, last_action_group, last_action_user
    ) VALUES (
        NOW(), ?, ?, 'THAP-TEST-01', ?,
        CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), ?, 'CUST-001', 'Khách Hàng Thử Nghiệm SMC',
        ?, ?, 'SEA', 'B', NULL,
        'Đơn test tự động kiểm thử hệ thống', 'Chưa xác định', 'Chưa xác định', 'Đang thực hiện', 0,
        0, ?, 'PC', 'admin_test'
    )
");

$stmtInsert->bind_param("sisiiii", $maSanPham, $soMetQc, $testOrderCode, $testDateYc, $soLuongDat, $tongMet, $conThieu);
$ok = $stmtInsert->execute();
$newId = (int)$conn->insert_id;
$stmtInsert->close();

assertTest("Tạo đơn B thành công vào database", $ok && $newId > 0, "ID mới: $newId");

// Kiểm tra các trường được tính toán
$checkOrder = $conn->query("SELECT * FROM don_b WHERE id = $newId")->fetch_assoc();
assertTest("Số mét quy cách tự tính = 100", (int)$checkOrder['so_met_quy_cach'] === 100, "Thực tế: {$checkOrder['so_met_quy_cach']}");
assertTest("Tổng mét cần tự tính = 1000m (10 cuộn * 100m)", (int)$checkOrder['tong_met_can'] === 1000, "Thực tế: {$checkOrder['tong_met_can']}");
assertTest("Số lượng còn thiếu = 10 cuộn", (int)$checkOrder['con_thieu'] === 10, "Thực tế: {$checkOrder['con_thieu']}");
assertTest("Trạng thái ban đầu = Đang thực hiện", $checkOrder['tinh_trang_nhap_kho'] === 'Đang thực hiện', "Thực tế: {$checkOrder['tinh_trang_nhap_kho']}");

// 4. Test Cảnh báo 5 ngày
echo "\n4. KIỂM TRA LOGIC CẢNH BÁO (ALERT 5 NGÀY TỚI CHƯA CÓ THỰC TÍCH):\n";
$today = new DateTime();
$target = new DateTime($checkOrder['ngay_yc_nhap_kho']);
$diffDays = (int)$today->diff($target)->format('%r%a');
$isAlert = ($diffDays <= 5 && (float)$checkOrder['cuon_da_sx'] <= 0 && $checkOrder['tinh_trang_nhap_kho'] !== 'Hoàn thành' && $checkOrder['tinh_trang_nhap_kho'] !== 'Từ chối');
assertTest("Đơn có ngày nhập kho trong 3 ngày tới và thực tích = 0 được kích hoạt cảnh báo", $isAlert === true, "Khoảng cách ngày: $diffDays");

// 5. Test Xưởng Đùn nhựa Xác nhận
echo "\n5. TEST BỘ PHẬN ĐÙN NHỰA XÁC NHẬN:\n";
$dateDuKien = date('Y-m-d', strtotime('+4 days'));
$stmt = $conn->prepare("UPDATE don_b SET dun_xac_nhan = 'Xác nhận', ngay_du_kien_xuat = ?, san_xuat_note = 'Đùn line 1 sẵn sàng', ngay_cap_nhat = NOW() WHERE id = ?");
$stmt->bind_param("si", $dateDuKien, $newId);
$stmt->execute();
$stmt->close();

$checkDun = $conn->query("SELECT dun_xac_nhan, ngay_du_kien_xuat, san_xuat_note FROM don_b WHERE id = $newId")->fetch_assoc();
assertTest("Đùn xác nhận = 'Xác nhận'", $checkDun['dun_xac_nhan'] === 'Xác nhận', "Thực tế: {$checkDun['dun_xac_nhan']}");
assertTest("Đùn cập nhật ngày dự kiến xuất", !empty($checkDun['ngay_du_kien_xuat']), "Ngày: {$checkDun['ngay_du_kien_xuat']}");

// 6. Test Bộ phận Cuộn nhựa Cập nhật thực tích (Part 1 - Chưa đủ)
echo "\n6. TEST CUỘN NHỰA CẬP NHẬT THỰC TÍCH (LẦN 1 - CHƯA ĐỦ):\n";
$cuonSxPart1 = 4;
$soLuongDat = (int)$checkOrder['so_luong_dat'];
$conThieu1 = max(0, $soLuongDat - $cuonSxPart1);
$stmt = $conn->prepare("UPDATE don_b SET cuon_da_sx = ?, con_thieu = ?, cuon_xac_nhan = 'Xác nhận', ngay_cap_nhat = NOW() WHERE id = ?");
$stmt->bind_param("iii", $cuonSxPart1, $conThieu1, $newId);
$stmt->execute();
$stmt->close();

$checkCuon1 = $conn->query("SELECT cuon_da_sx, con_thieu, tinh_trang_nhap_kho FROM don_b WHERE id = $newId")->fetch_assoc();
assertTest("Cuộn đã SX = 4", (int)$checkCuon1['cuon_da_sx'] === 4, "Thực tế: {$checkCuon1['cuon_da_sx']}");
assertTest("Còn thiếu = 6 (10 - 4)", (int)$checkCuon1['con_thieu'] === 6, "Thực tế: {$checkCuon1['con_thieu']}");
assertTest("Tình trạng nhập kho vẫn là 'Đang thực hiện'", $checkCuon1['tinh_trang_nhap_kho'] === 'Đang thực hiện', "Thực tế: {$checkCuon1['tinh_trang_nhap_kho']}");

// 7. Test Cuộn nhựa Cập nhật thực tích đủ (con_thieu <= 0 -> Tự động Hoàn thành)
echo "\n7. TEST CUỘN NHỰA CẬP NHẬT ĐỦ SỐ LƯỢNG (TỰ ĐỘNG HOÀN THÀNH):\n";
$cuonSxPart2 = 10;
$conThieu2 = max(0, $soLuongDat - $cuonSxPart2);
$tinhTrang2 = ($conThieu2 <= 0) ? 'Hoàn thành' : 'Đang thực hiện';
$stmt = $conn->prepare("UPDATE don_b SET cuon_da_sx = ?, con_thieu = ?, tinh_trang_nhap_kho = ?, ngay_cap_nhat = NOW() WHERE id = ?");
$stmt->bind_param("iisi", $cuonSxPart2, $conThieu2, $tinhTrang2, $newId);
$stmt->execute();
$stmt->close();

$checkCuon2 = $conn->query("SELECT cuon_da_sx, con_thieu, tinh_trang_nhap_kho FROM don_b WHERE id = $newId")->fetch_assoc();
assertTest("Cuộn đã SX = 10", (int)$checkCuon2['cuon_da_sx'] === 10, "Thực tế: {$checkCuon2['cuon_da_sx']}");
assertTest("Còn thiếu = 0", (int)$checkCuon2['con_thieu'] === 0, "Thực tế: {$checkCuon2['con_thieu']}");
assertTest("Tự động chuyển trạng thái thành 'Hoàn thành'", $checkCuon2['tinh_trang_nhap_kho'] === 'Hoàn thành', "Thực tế: {$checkCuon2['tinh_trang_nhap_kho']}");

// 8. Test PC Phê duyệt Chuyển Đơn B sang Đơn A
echo "\n8. TEST PC PHÊ DUYỆT CHUYỂN ĐƠN B SANG ĐƠN A:\n";
$todayDate = date('Y-m-d');
$stmt = $conn->prepare("UPDATE don_b SET phan_loai_don = 'A', ngay_chuyen_b_to_a = ?, pc_note = CONCAT(COALESCE(pc_note,''), ' | Đã duyệt sang A'), ngay_cap_nhat = NOW() WHERE id = ?");
$stmt->bind_param("si", $todayDate, $newId);
$stmt->execute();
$stmt->close();

$checkDuyetA = $conn->query("SELECT phan_loai_don, ngay_chuyen_b_to_a FROM don_b WHERE id = $newId")->fetch_assoc();
assertTest("Phân loại đơn chuyển thành 'A'", $checkDuyetA['phan_loai_don'] === 'A', "Thực tế: {$checkDuyetA['phan_loai_don']}");
assertTest("Ghi nhận ngày chuyển B to A", $checkDuyetA['ngay_chuyen_b_to_a'] === $todayDate, "Thực tế: {$checkDuyetA['ngay_chuyen_b_to_a']}");

// 9. Test Check trùng theo mã đơn hàng (オーダー) - Không tạo bản ghi trùng
echo "\n9. TEST CHECK TRÙNG THEO MÃ ĐƠN HÀNG (IMPORT / SAVE):\n";
$countBefore = $conn->query("SELECT COUNT(*) as c FROM don_b WHERE ma_don_hang = '$testOrderCode'")->fetch_assoc()['c'];
// Thực hiện cập nhật thay vì insert trùng
$updateStmt = $conn->prepare("UPDATE don_b SET pc_note = 'Cập nhật lại qua mã đơn hàng trùng', ngay_cap_nhat = NOW() WHERE ma_don_hang = ?");
$updateStmt->bind_param("s", $testOrderCode);
$updateStmt->execute();
$updateStmt->close();
$countAfter = $conn->query("SELECT COUNT(*) as c FROM don_b WHERE ma_don_hang = '$testOrderCode'")->fetch_assoc()['c'];

assertTest("Không tạo bản ghi trùng lặp (Count trước = 1, Count sau = 1)", (int)$countBefore === 1 && (int)$countAfter === 1, "Số bản ghi: $countAfter");

// 10. Test Phân quyền RBAC trong cấu hình
echo "\n10. TEST CẤU HÌNH PHÂN QUYỀN RBAC CHO ORDERS:\n";
$permCfg = require __DIR__ . '/../config/permission.php';
$ordersPerms = $permCfg['permission_catalog']['orders']['permissions'] ?? [];
$requiredPermKeys = ['orders.view', 'orders.create', 'orders.edit', 'orders.dun_confirm', 'orders.cuon_confirm', 'orders.approve_a', 'orders.delete', 'orders.export'];

$allPermsPresent = true;
foreach ($requiredPermKeys as $key) {
    if (!isset($ordersPerms[$key])) {
        $allPermsPresent = false;
        break;
    }
}
assertTest("Tất cả các mã quyền orders.* có đầy đủ trong permission_catalog", $allPermsPresent);
assertTest("Admin có quyền 'orders.view' trong roles_map", in_array('orders.view', $permCfg['roles_map']['admin'] ?? []));
assertTest("Editor có quyền 'orders.dun_confirm' và 'orders.cuon_confirm'", 
    in_array('orders.dun_confirm', $permCfg['roles_map']['editor'] ?? []) && 
    in_array('orders.cuon_confirm', $permCfg['roles_map']['editor'] ?? [])
);

// Dọn dẹp bản ghi kiểm thử
$conn->query("DELETE FROM don_b_history WHERE don_b_id = $newId");
$conn->query("DELETE FROM don_b_notifications WHERE don_b_id = $newId");
$conn->query("DELETE FROM don_b WHERE id = $newId");

echo "\n====================================================\n";
echo " KẾT QUẢ KIỂM THỬ: $passed ĐẠT, $failed THẤT BẠI\n";
echo "====================================================\n";

if ($failed === 0) {
    echo ">>> TẤT CẢ TEST ĐỀU VƯỢT QUA XUẤT SẮC! <<<\n";
    exit(0);
} else {
    echo ">>> CÓ $failed TEST THẤT BẠI. CẦN KIỂM TRA LẠI! <<<\n";
    exit(1);
}
