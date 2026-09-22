<?php
// Bật output buffering để ngăn warning làm hỏng JSON response
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';

    requireApiPermission(['five_s.audit', 'api.five_s.save_audit_full']);

    $inspector_id = $_SESSION['user_id'];
    $zone_id = intval($_POST['zone_id'] ?? 0);
    $audit_result = trim($_POST['audit_result'] ?? 'OK'); 
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    $audit_date = date('Y-m-d');
    $current_month = date('Y-m');
    $checklist_json = trim($_POST['checklist_json'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');

    if ($zone_id <= 0 || !in_array($audit_result, ['OK', 'NG'], true) || empty($checklist_json)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu kiểm tra']);
        exit;
    }

    // 1. Kiểm tra lịch tuần tra
    if ($schedule_id > 0) {
        $stmtSchedule = $conn->prepare("SELECT id FROM five_s_schedules
                                        WHERE id = ? AND zone_id = ? AND inspector_id = ?
                                          AND schedule_date = ? AND status = 'pending'");
        $stmtSchedule->bind_param("iiis", $schedule_id, $zone_id, $inspector_id, $audit_date);
        $stmtSchedule->execute();
        if (!$stmtSchedule->get_result()->fetch_assoc()) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Chỉ được thực hiện phiếu kiểm tra đang chờ trong ngày hôm nay']);
            exit;
        }
    }

    // 2. Kiểm tra mã QR/NFC
    if (!empty($qr_code)) {
        $stmtZone = $conn->prepare("SELECT id FROM five_s_zones WHERE id = ? AND qr_code_hash = ?");
        $stmtZone->bind_param("is", $zone_id, $qr_code);
        $stmtZone->execute();
        if (!$stmtZone->get_result()->fetch_assoc()) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Chưa xác thực đúng mã QR/NFC của khu vực']);
            exit;
        }
    }

    // 3. Xử lý tải ảnh
    $image_field = ($audit_result === 'NG') ? 'before_image' : 'actual_image';
    if (!isset($_FILES[$image_field]) || $_FILES[$image_field]['error'] !== UPLOAD_ERR_OK) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vui lòng chụp và tải ảnh hiện trạng khu vực']);
        exit;
    }

    $upload_dir = __DIR__ . '/../documents/5s_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES[$image_field]['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Định dạng ảnh không hợp lệ']);
        exit;
    }

    $file_name = ($audit_result === 'NG' ? 'before_' : 'actual_') . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
    if (!move_uploaded_file($_FILES[$image_field]['tmp_name'], $upload_dir . $file_name)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Không thể lưu hình ảnh']);
        exit;
    }
    $image_url = 'documents/5s_images/' . $file_name;

    $conn->begin_transaction();
    $transactionStarted = true;

    // 4. Kiểm tra sự tồn tại của cột actual_image trước khi lưu
    $checkCol = $conn->query("SHOW COLUMNS FROM five_s_audits LIKE 'actual_image'");
    $hasActualImgCol = ($checkCol && $checkCol->num_rows > 0);

    if ($hasActualImgCol) {
        $stmtAudit = $conn->prepare("INSERT INTO five_s_audits (zone_id, inspector_id, audit_date, checklist_json, actual_image) VALUES (?, ?, ?, ?, ?)");
        $actual_image = ($audit_result === 'OK') ? $image_url : null;
        $stmtAudit->bind_param("iisss", $zone_id, $inspector_id, $audit_date, $checklist_json, $actual_image);
    } else {
        $stmtAudit = $conn->prepare("INSERT INTO five_s_audits (zone_id, inspector_id, audit_date, checklist_json) VALUES (?, ?, ?, ?)");
        $stmtAudit->bind_param("iiss", $zone_id, $inspector_id, $audit_date, $checklist_json);
    }

    if (!$stmtAudit->execute()) {
        throw new Exception("Không thể lưu thông tin lượt kiểm tra vào CSDl");
    }
    $audit_id = $conn->insert_id;

    // 5. Nếu là VI PHẠM (NG) -> Tạo lỗi trong bảng five_s_issues
    if ($audit_result === 'NG') {
        $s_category = trim($_POST['s_category'] ?? 'S1');
        $pos_x = floatval($_POST['pos_x'] ?? 50.0);
        $pos_y = floatval($_POST['pos_y'] ?? 50.0);
        $description = trim($_POST['description'] ?? '');

        if (empty($description)) {
            throw new Exception('Vui lòng nhập mô tả vi phạm');
        }

        $before_image_url = $image_url;

        // Lấy người phụ trách khu vực trong tháng
        $assignee_id = $inspector_id;
        $stmtAss = $conn->prepare("SELECT assignee_id FROM five_s_assignments WHERE zone_id = ? AND month_year = ?");
        $stmtAss->bind_param("is", $zone_id, $current_month);
        $stmtAss->execute();
        if ($rowAss = $stmtAss->get_result()->fetch_assoc()) {
            $assignee_id = $rowAss['assignee_id'];
        }

        $stmtIssue = $conn->prepare("INSERT INTO five_s_issues (audit_id, zone_id, s_category, pos_x, pos_y, description, before_image, assignee_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmtIssue->bind_param("iisddssi", $audit_id, $zone_id, $s_category, $pos_x, $pos_y, $description, $before_image_url, $assignee_id);
        $stmtIssue->execute();

        // Phát thông báo
        $msg = "Phát hiện vi phạm 5S tại khu vực. Yêu cầu kiểm tra xử lý.";
        $link = "index.php?mainpage=five_s&subpage=overview";
        $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Cảnh báo vi phạm 5S', ?, ?)");
        $stmtNoti->bind_param("iss", $assignee_id, $msg, $link);
        $stmtNoti->execute();
    }

    // 6. Cập nhật trạng thái lịch kiểm tra
    if ($schedule_id > 0) {
        $stmtSched = $conn->prepare("UPDATE five_s_schedules SET status = 'completed' WHERE id = ?");
        $stmtSched->bind_param("i", $schedule_id);
        $stmtSched->execute();
    }

    $conn->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Lưu báo cáo kiểm tra thành công']);

} catch (Exception $e) {
    if (isset($conn) && !empty($transactionStarted)) {
        $conn->rollback();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý hệ thống: ' . $e->getMessage()]);
}