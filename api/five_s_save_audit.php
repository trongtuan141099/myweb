<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$inspector_id = $_SESSION['user_id'];
$zone_id = intval($_POST['zone_id'] ?? 0);
$s_category = trim($_POST['s_category'] ?? 'S1');
$pos_x = floatval($_POST['pos_x'] ?? 0);
$pos_y = floatval($_POST['pos_y'] ?? 0);
$description = trim($_POST['description'] ?? '');
$audit_date = date('Y-m-d');
$current_month = date('Y-m');

if ($zone_id <= 0 || empty($description) || !isset($_FILES['before_image'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc và tải ảnh vi phạm']);
    exit;
}

// Xử lý upload ảnh
$upload_dir = __DIR__ . '/../documents/5s_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_ext = strtolower(pathinfo($_FILES['before_image']['name'], PATHINFO_EXTENSION));
$allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($file_ext, $allowed_exts)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng ảnh không hợp lệ']);
    exit;
}

$file_name = 'before_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['before_image']['tmp_name'], $target_file)) {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu hình ảnh upload']);
    exit;
}

$before_image_url = 'documents/5s_images/' . $file_name;

// Xác định người phụ trách khu vực trong tháng
$assignee_id = $inspector_id; // Default fallback
$stmtAss = $conn->prepare("SELECT assignee_id FROM five_s_assignments WHERE zone_id = ? AND month_year = ?");
$stmtAss->bind_param("is", $zone_id, $current_month);
$stmtAss->execute();
$resAss = $stmtAss->get_result();
if ($rowAss = $resAss->fetch_assoc()) {
    $assignee_id = $rowAss['assignee_id'];
}

$conn->begin_transaction();

try {
    // 1. Tạo mới phiếu Audit Header
    $stmtAudit = $conn->prepare("INSERT INTO five_s_audits (zone_id, inspector_id, audit_date) VALUES (?, ?, ?)");
    $stmtAudit->bind_param("iis", $zone_id, $inspector_id, $audit_date);
    $stmtAudit->execute();
    $audit_id = $conn->insert_id;

    // 2. Tạo chi tiết lỗi Issue
    $stmtIssue = $conn->prepare("INSERT INTO five_s_issues (audit_id, zone_id, s_category, pos_x, pos_y, description, before_image, assignee_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmtIssue->bind_param("iissdssi", $audit_id, $zone_id, $s_category, $pos_x, $pos_y, $description, $before_image_url, $assignee_id);
    $stmtIssue->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã ghi nhận kiểm tra 5S thành công']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu: ' . $e->getMessage()]);
}