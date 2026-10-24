<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.settings', 'api.five_s.save_zone']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$action = trim($_POST['action'] ?? 'save');
$zoneId = intval($_POST['zone_id'] ?? 0);

try {
    // 1. Toggle Active status
    if ($action === 'toggle_active') {
        if ($zoneId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID vị trí không hợp lệ']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE five_s_zones SET is_active = NOT COALESCE(is_active, 1) WHERE id = ?");
        $stmt->bind_param("i", $zoneId);
        $stmt->execute();
        
        $res = $conn->query("SELECT is_active FROM five_s_zones WHERE id = $zoneId")->fetch_assoc();
        $newStatus = (int)($res['is_active'] ?? 1);
        echo json_encode([
            'success' => true, 
            'message' => $newStatus ? 'Đã kích hoạt vị trí' : 'Đã tạm ngừng sử dụng vị trí',
            'is_active' => $newStatus
        ]);
        exit;
    }

    // 2. Delete Zone
    if ($action === 'delete') {
        if ($zoneId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID vị trí không hợp lệ']);
            exit;
        }
        $chkAudit = $conn->prepare("SELECT COUNT(*) AS c FROM five_s_audits WHERE zone_id = ?");
        $chkAudit->bind_param("i", $zoneId);
        $chkAudit->execute();
        $auditCount = (int)$chkAudit->get_result()->fetch_assoc()['c'];

        if ($auditCount > 0) {
            $stmt = $conn->prepare("UPDATE five_s_zones SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $zoneId);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Vị trí đã có lịch sử kiểm tra nên hệ thống chuyển sang trạng thái "Ngừng sử dụng" để lưu trữ dữ liệu.']);
            exit;
        }

        $stmtDel = $conn->prepare("DELETE FROM five_s_zones WHERE id = ?");
        $stmtDel->bind_param("i", $zoneId);
        $stmtDel->execute();
        echo json_encode(['success' => true, 'message' => 'Đã xóa vị trí 5S thành công']);
        exit;
    }

    // 3. Save / Update Zone (ONLY Position Info, NO Personnel Configuration)
    $zoneCode = trim($_POST['zone_code'] ?? '');
    $zoneName = trim($_POST['zone_name'] ?? '');
    $factory = trim($_POST['factory'] ?? 'Nhà Máy 1');
    $area = trim($_POST['area'] ?? 'Khu Vực Sản Xuất');
    $description = trim($_POST['description'] ?? '');
    $qrCode = trim($_POST['qr_code'] ?? '');
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    if ($zoneCode === '' || $zoneName === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã và tên vị trí 5S']);
        exit;
    }

    if ($qrCode === '') {
        $qrCode = '5S-' . $zoneCode;
    }

    if ($zoneId > 0) {
        $query = "UPDATE five_s_zones SET 
                    zone_code = ?, 
                    zone_name = ?, 
                    factory = ?,
                    area = ?,
                    department = ?,
                    qr_code_hash = ?, 
                    description = ?,
                    is_active = ?
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssii", $zoneCode, $zoneName, $factory, $area, $area, $qrCode, $description, $isActive, $zoneId);
        $stmt->execute();
    } else {
        $query = "INSERT INTO five_s_zones (
                    zone_code, zone_name, factory, area, department, qr_code_hash, description, is_active
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $zoneCode, $zoneName, $factory, $area, $area, $qrCode, $description, $isActive);
        $stmt->execute();
        $zoneId = $conn->insert_id;
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Lưu thông tin vị trí 5S thành công',
        'zone_id' => $zoneId
    ]);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
