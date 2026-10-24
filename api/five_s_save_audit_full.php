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

    $inspector_id = $_SESSION['user_id'] ?? 1;
    $zone_id = intval($_POST['zone_id'] ?? 0);
    $audit_result = trim($_POST['audit_result'] ?? 'OK'); 
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    $audit_date = date('Y-m-d');
    $current_month = date('Y-m');
    $checklist_json = trim($_POST['checklist_json'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $now = date('Y-m-d H:i:s');

    if ($zone_id <= 0 || !in_array($audit_result, ['OK', 'NG'], true) || empty($checklist_json)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phiếu kiểm tra hoặc danh mục đánh giá']);
        exit;
    }

    // 1. BẮT BUỘC xác thực mã QR Code tại vị trí thực tế
    if (empty($qr_code)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Bắt buộc phải quét mã QR tại vị trí kiểm tra thực tế!']);
        exit;
    }

    $stmtZone = $conn->prepare("SELECT id, zone_code, zone_name, qr_code_hash, manager_id FROM five_s_zones WHERE id = ?");
    $stmtZone->bind_param("i", $zone_id);
    $stmtZone->execute();
    $zoneData = $stmtZone->get_result()->fetch_assoc();

    if (!$zoneData) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy khu vực kiểm tra']);
        exit;
    }

    $validQr = trim($zoneData['qr_code_hash'] ?? '');
    if (empty($validQr)) {
        $validQr = '5S-' . $zoneData['zone_code'];
    }

    // Compare QR code (exact match or standard token)
    if ($qr_code !== $validQr && $qr_code !== $zoneData['zone_code']) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Mã QR không khớp với vị trí này! Vui lòng quét đúng mã QR tại hiện trường.']);
        exit;
    }

    // 2. Xử lý tải ảnh minh chứng hiện trường
    $image_field = ($audit_result === 'NG') ? 'before_image' : 'actual_image';
    $hasImage = isset($_FILES[$image_field]) && $_FILES[$image_field]['error'] === UPLOAD_ERR_OK;

    // Ảnh thực tế là bắt buộc nếu vi phạm NG, hoặc khuyến khích khi OK
    if ($audit_result === 'NG' && !$hasImage) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Bắt buộc chụp ảnh hiện trạng khi có vi phạm (NG)']);
        exit;
    }

    $image_url = null;
    if ($hasImage) {
        $upload_dir = __DIR__ . '/../documents/5s_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES[$image_field]['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Định dạng ảnh không hợp lệ (hỗ trợ JPG, PNG, WEBP)']);
            exit;
        }

        $file_name = ($audit_result === 'NG' ? 'before_' : 'actual_') . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        if (!move_uploaded_file($_FILES[$image_field]['tmp_name'], $upload_dir . $file_name)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không thể lưu hình ảnh minh chứng']);
            exit;
        }
        $image_url = 'documents/5s_images/' . $file_name;
    }

    $conn->begin_transaction();
    $transactionStarted = true;

    // 3. Lưu bản ghi kiểm tra five_s_audits kèm ghi nhận quét QR
    $stmtAudit = $conn->prepare("INSERT INTO five_s_audits (
                                    zone_id, inspector_id, audit_date, checklist_json, 
                                    actual_image, note, scanned_qr, scanned_at, audit_status
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtAudit->bind_param(
        "iisssssss", 
        $zone_id, $inspector_id, $audit_date, $checklist_json, 
        $image_url, $note, $qr_code, $now, $audit_result
    );

    if (!$stmtAudit->execute()) {
        throw new Exception("Không thể lưu kết quả kiểm tra vào cơ sở dữ liệu: " . $stmtAudit->error);
    }
    $audit_id = $conn->insert_id;

    // 4. Nếu phát hiện vi phạm NG -> Tự động tạo phiếu khắc phục vào five_s_issues
    if ($audit_result === 'NG') {
        $s_category = trim($_POST['s_category'] ?? 'S1');
        $pos_x = floatval($_POST['pos_x'] ?? 50.0);
        $pos_y = floatval($_POST['pos_y'] ?? 50.0);
        $description = trim($_POST['description'] ?? '');

        if (empty($description)) {
            $description = "Phát hiện lỗi không đạt chuẩn 5S tại khu vực " . $zoneData['zone_name'];
        }

        // Lấy người phụ trách xử lý: ưu tiên phân công tháng, sau đó manager của zone
        $assignee_id = $zoneData['manager_id'] ?? null;
        $stmtAss = $conn->prepare("SELECT assignee_id FROM five_s_assignments WHERE zone_id = ? AND month_year = ?");
        $stmtAss->bind_param("is", $zone_id, $current_month);
        $stmtAss->execute();
        if ($rowAss = $stmtAss->get_result()->fetch_assoc()) {
            $assignee_id = $rowAss['assignee_id'];
        }
        if (empty($assignee_id)) {
            $assignee_id = $inspector_id;
        }

        $stmtIssue = $conn->prepare("INSERT INTO five_s_issues (
                                        audit_id, zone_id, s_category, pos_x, pos_y, 
                                        description, before_image, assignee_id, status
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmtIssue->bind_param(
            "iisddssi", 
            $audit_id, $zone_id, $s_category, $pos_x, $pos_y, 
            $description, $image_url, $assignee_id
        );
        $stmtIssue->execute();

        // Gửi thông báo đến người phụ trách
        $msg = "Phát hiện lỗi vi phạm 5S tại [{$zoneData['zone_name']}]. Yêu cầu phân tích nguyên nhân và lập đối sách.";
        $link = "index.php?mainpage=five_s&subpage=list";
        $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Cảnh báo vi phạm 5S', ?, ?)");
        $stmtNoti->bind_param("iss", $assignee_id, $msg, $link);
        $stmtNoti->execute();
    }

    // 5. Cập nhật trạng thái lịch kiểm tra
    if ($schedule_id > 0) {
        $stmtSched = $conn->prepare("UPDATE five_s_schedules SET status = 'completed' WHERE id = ?");
        $stmtSched->bind_param("i", $schedule_id);
        $stmtSched->execute();
    } else {
        // Cập nhật bất kỳ lịch pending nào của zone trong ngày hôm nay thành completed
        $stmtSchedToday = $conn->prepare("UPDATE five_s_schedules SET status = 'completed' WHERE zone_id = ? AND schedule_date = ? AND status = 'pending'");
        $stmtSchedToday->bind_param("is", $zone_id, $audit_date);
        $stmtSchedToday->execute();
    }

    $conn->commit();
    ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'Gửi kết quả kiểm tra 5S thành công! Đã ghi nhận thời gian quét QR thực tế.',
        'audit_id' => $audit_id
    ]);

} catch (Exception $e) {
    if (isset($conn) && !empty($transactionStarted)) {
        $conn->rollback();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý: ' . $e->getMessage()]);
}
