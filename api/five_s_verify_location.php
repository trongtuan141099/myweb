<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

$zone_id = intval($_POST['zone_id'] ?? 0);
$qr_code = trim($_POST['qr_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $zone_id <= 0 || $qr_code === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã khu vực cần xác thực']);
    exit;
}

$stmt = $conn->prepare("SELECT id, zone_name FROM five_s_zones WHERE id = ? AND qr_code_hash = ?");
$stmt->bind_param("is", $zone_id, $qr_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->fetch_assoc()) {
    echo json_encode(['success' => true, 'message' => 'Xác thực vị trí thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Mã QR/NFC không hợp lệ hoặc sai vị trí!']);
}