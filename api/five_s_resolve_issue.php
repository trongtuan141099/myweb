<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.audit', 'five_s.settings', 'api.five_s.resolve_issue']);

$issue_id = intval($_POST['issue_id'] ?? 0);
$status = trim($_POST['status'] ?? 'in_progress'); 
$root_cause = trim($_POST['root_cause'] ?? '');
$countermeasure = trim($_POST['countermeasure'] ?? '');
$resolution_note = trim($_POST['resolution_note'] ?? '');
$assignee_id = !empty($_POST['assignee_id']) ? intval($_POST['assignee_id']) : 0;
$due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
$approver_id = !empty($_POST['approver_id']) ? intval($_POST['approver_id']) : ($_SESSION['user_id'] ?? 1);

if ($issue_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã phiếu khắc phục không hợp lệ']);
    exit;
}

$validStatuses = ['pending', 'in_progress', 'pending_approval', 'resolved', 'overdue'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'in_progress';
}

try {
    // 1. Get current issue details
    $stmtCur = $conn->prepare("SELECT i.*, z.zone_name, a.inspector_id 
                               FROM five_s_issues i 
                               JOIN five_s_zones z ON i.zone_id = z.id
                               LEFT JOIN five_s_audits a ON i.audit_id = a.id
                               WHERE i.id = ?");
    $stmtCur->bind_param("i", $issue_id);
    $stmtCur->execute();
    $issue = $stmtCur->get_result()->fetch_assoc();

    if (!$issue) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu vi phạm 5S']);
        exit;
    }

    $after_image_url = $issue['after_image'];

    // Handle Upload After Image if provided
    if (isset($_FILES['after_image']) && $_FILES['after_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../documents/5s_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            echo json_encode(['success' => false, 'message' => 'Ảnh khắc phục chỉ hỗ trợ JPG, PNG, WEBP']);
            exit;
        }

        $file_name = 'after_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['after_image']['tmp_name'], $target_file)) {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu trữ ảnh khắc phục']);
            exit;
        }
        $after_image_url = 'documents/5s_images/' . $file_name;
    }

    // Kiểm tra quyền phê duyệt / nghiệm thu hoàn thành phiếu đối sách
    if ($status === 'resolved') {
        $currentUid = $_SESSION['user_id'] ?? 0;
        $currentRole = $_SESSION['user']['role'] ?? '';
        $canApprove = ($currentRole === 'admin') || hasPermission('five_s.settings') || ($currentUid && $currentUid == $issue['approver_id']);
        if (!$canApprove) {
            http_response_code(403);
            echo json_encode(['success' => false, 'code' => 403, 'message' => 'Bạn không có quyền phê duyệt nghiệm thu phiếu đối sách này!']);
            exit;
        }
    }

    // Determine timestamp updates
    $now = date('Y-m-d H:i:s');
    $in_progress_at = $issue['in_progress_at'];
    $resolved_at = $issue['resolved_at'];
    $approved_at = $issue['approved_at'];

    if ($status === 'in_progress' && empty($in_progress_at)) {
        $in_progress_at = $now;
    } elseif ($status === 'resolved') {
        $resolved_at = $now;
        $approved_at = $now;
    }

    $newAssignee = $assignee_id > 0 ? $assignee_id : $issue['assignee_id'];
    $newDueDate = !empty($due_date) ? $due_date : $issue['due_date'];

    $stmtUpdate = $conn->prepare("UPDATE five_s_issues SET 
                                    status = ?, 
                                    root_cause = ?, 
                                    countermeasure = ?, 
                                    resolution_note = ?, 
                                    after_image = ?, 
                                    in_progress_at = ?,
                                    resolved_at = ?,
                                    approved_at = ?,
                                    approver_id = ?,
                                    due_date = ?,
                                    assignee_id = ?
                                  WHERE id = ?");
    $stmtUpdate->bind_param(
        "ssssssssisii", 
        $status, $root_cause, $countermeasure, $resolution_note, 
        $after_image_url, $in_progress_at, $resolved_at, $approved_at,
        $approver_id, $newDueDate, $newAssignee, $issue_id
    );

    if ($stmtUpdate->execute()) {
        $labels = [
            'pending' => 'Mới phát sinh',
            'in_progress' => 'Đang xử lý',
            'pending_approval' => 'Chờ xác nhận',
            'resolved' => 'Hoàn thành đối sách',
            'overdue' => 'Quá hạn xử lý'
        ];
        $statusLabel = $labels[$status] ?? $status;

        // Notification if resolved or pending_approval
        if ($status === 'resolved' || $status === 'pending_approval') {
            $notifyUser = ($status === 'pending_approval') ? $approver_id : ($issue['inspector_id'] ?? $issue['assignee_id']);
            if (!empty($notifyUser)) {
                $msg = "Phiếu đối sách 5S tại [{$issue['zone_name']}] đã chuyển trạng thái: {$statusLabel}.";
                $link = "index.php?mainpage=five_s&tab=issues";
                $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Tiến độ đối sách 5S', ?, ?)");
                $stmtNoti->bind_param("iss", $notifyUser, $msg, $link);
                $stmtNoti->execute();
            }
        }

        echo json_encode([
            'success' => true, 
            'message' => "Cập nhật thành công: $statusLabel",
            'status' => $status,
            'status_label' => $statusLabel
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL: ' . $stmtUpdate->error]);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
