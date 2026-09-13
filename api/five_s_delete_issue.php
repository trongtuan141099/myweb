<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$issue_id = intval($_POST['issue_id'] ?? 0);
if ($issue_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID vi phạm không hợp lệ']);
    exit;
}

// Lấy thông tin file ảnh để xóa khỏi thư mục lưu trữ
$stmt = $conn->prepare("SELECT before_image, after_image FROM five_s_issues WHERE id = ?");
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (!empty($row['before_image']) && file_exists(__DIR__ . '/../' . $row['before_image'])) {
        @unlink(__DIR__ . '/../' . $row['before_image']);
    }
    if (!empty($row['after_image']) && file_exists(__DIR__ . '/../' . $row['after_image'])) {
        @unlink(__DIR__ . '/../' . $row['after_image']);
    }
}

$stmtDel = $conn->prepare("DELETE FROM five_s_issues WHERE id = ?");
$stmtDel->bind_param("i", $issue_id);

if ($stmtDel->execute()) {
    echo json_encode(['success' => true, 'message' => 'Xóa ghi nhận vi phạm 5S thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể xóa dữ liệu']);
}