<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$action = trim($_POST['action'] ?? 'create');

$upload_dir = __DIR__ . '/../documents/5s_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($action === 'create') {
    // 1. Nhân viên gửi Đề xuất mới
    $employee_id = $_SESSION['user_id'];
    $current_status_desc = trim($_POST['current_status_desc'] ?? '');
    $proposal_desc = trim($_POST['proposal_desc'] ?? '');

    if (empty($current_status_desc) || empty($proposal_desc) || !isset($_FILES['current_image'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ mô tả và tải ảnh hiện trạng']);
        exit;
    }

    $file_ext = strtolower(pathinfo($_FILES['current_image']['name'], PATHINFO_EXTENSION));
    $file_name = 'prop_before_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
    
    if (!move_uploaded_file($_FILES['current_image']['tmp_name'], $upload_dir . $file_name)) {
        echo json_encode(['success' => false, 'message' => 'Lỗi lưu ảnh hiện trạng']);
        exit;
    }

    $current_image_url = 'documents/5s_images/' . $file_name;

    $stmt = $conn->prepare("INSERT INTO five_s_proposals (employee_id, current_status_desc, current_image, proposal_desc, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isss", $employee_id, $current_status_desc, $current_image_url, $proposal_desc);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã gửi đề xuất 5S thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo đề xuất']);
    }

} elseif ($action === 'update_status') {
    // 2. Quản lý duyệt, xét trạng thái, nhận xét hoặc hoàn tất
    $proposal_id = intval($_POST['proposal_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');
    $manager_comment = trim($_POST['manager_comment'] ?? '');

    if ($proposal_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mã đề xuất không hợp lệ']);
        exit;
    }

    $after_image_url = null;
    $completed_at = null;

    // Nếu trạng thái là 'completed', kiểm tra ảnh hoàn thành
    if ($status === 'completed') {
        $completed_at = date('Y-m-d H:i:s');
        if (isset($_FILES['after_image']) && $_FILES['after_image']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION));
            $file_name = 'prop_after_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['after_image']['tmp_name'], $upload_dir . $file_name)) {
                $after_image_url = 'documents/5s_images/' . $file_name;
            }
        }
    }

    if ($after_image_url) {
        $stmt = $conn->prepare("UPDATE five_s_proposals SET status = ?, manager_comment = ?, after_image = ?, completed_at = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $status, $manager_comment, $after_image_url, $completed_at, $proposal_id);
    } else {
        $stmt = $conn->prepare("UPDATE five_s_proposals SET status = ?, manager_comment = ?, completed_at = IF(?='completed', NOW(), completed_at) WHERE id = ?");
        $stmt->bind_param("sssi", $status, $manager_comment, $status, $proposal_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái đề xuất thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật đề xuất']);
    }
}