<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$issue_id = intval($_POST['issue_id'] ?? 0);
$resolution_note = trim($_POST['resolution_note'] ?? '');

if ($issue_id <= 0 || !isset($_FILES['after_image'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin hoặc hình ảnh khắc phục']);
    exit;
}

// Xử lý Upload Ảnh Khắc Phục
$upload_dir = __DIR__ . '/../documents/5s_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_ext = strtolower(pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION));
$file_name = 'after_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['after_image']['tmp_name'], $target_file)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi tải ảnh khắc phục']);
    exit;
}

$after_image_url = 'documents/5s_images/' . $file_name;
$resolved_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE five_s_issues SET status = 'resolved', after_image = ?, resolution_note = ?, resolved_at = ? WHERE id = ?");
$stmt->bind_param("sssi", $after_image_url, $resolution_note, $resolved_at, $issue_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật khắc phục lỗi 5S thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái lỗi']);
}