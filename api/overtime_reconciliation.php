<?php
/**
 * API Đối Soát Tăng Ca (Reconciliation Engine)
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/overtime_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bắt buộc quyền api.overtime.reconcile
requireApiPermission('api.overtime.reconcile');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$currentUser = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'admin');

try {
    switch ($action) {
        // =====================================================================
        // 1. CHẠY ENGINE ĐỐI SOÁT TỰ ĐỘNG
        // =====================================================================
        case 'run_reconcile':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền chạy đối soát tự động!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $filterMonth = !empty($_POST['month']) ? intval($_POST['month']) : null;
            $filterYear = !empty($_POST['year']) ? intval($_POST['year']) : null;

            $summary = runReconciliationInternal($conn, $filterMonth, $filterYear);

            echo json_encode([
                'success' => true,
                'message' => "Đã đối soát xong {$summary['total']} ca tăng ca!",
                'summary' => $summary
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LẤY DANH SÁCH ĐỐI SOÁT & BỘ LỌC
        // =====================================================================
        case 'get_reconciliations':
            $status = trim($_GET['status'] ?? '');
            $hideCompleted = (!empty($_GET['hide_completed']) && $_GET['hide_completed'] === '1');
            $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;
            $year = !empty($_GET['year']) ? intval($_GET['year']) : 0;
            $otDate = trim($_GET['ot_date'] ?? '');
            $search = trim($_GET['search'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(100, intval($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;

            // Đọc thông tin đồng bộ HRM gần nhất từ cấu hình & CSDL ot_hrm_accounts
            $hrmConfigFile = __DIR__ . '/../config/hrm_sync_config.json';
            $hrmSyncInfo = [
                'last_sync_time' => null,
                'last_sync_time_formatted' => '-',
                'last_sync_status' => 'never_run',
                'last_sync_message' => 'Chưa có thông tin đồng bộ.'
            ];
            if (file_exists($hrmConfigFile)) {
                $cfgData = json_decode(file_get_contents($hrmConfigFile), true);
                if (is_array($cfgData)) {
                    $hrmSyncInfo['last_sync_time'] = $cfgData['last_sync_time'] ?? null;
                    $hrmSyncInfo['last_sync_status'] = $cfgData['last_sync_status'] ?? 'never_run';
                    $hrmSyncInfo['last_sync_message'] = $cfgData['last_sync_message'] ?? '';
                }
            }

            // Kiểm tra thêm từ ot_hrm_accounts để lấy thời gian mới nhất nếu có
            $resAccMax = $conn->query("SELECT MAX(last_sync_time) as max_time FROM ot_hrm_accounts WHERE last_sync_time IS NOT NULL");
            if ($resAccMax && $rM = $resAccMax->fetch_assoc()) {
                if (!empty($rM['max_time']) && (empty($hrmSyncInfo['last_sync_time']) || strtotime($rM['max_time']) > strtotime($hrmSyncInfo['last_sync_time']))) {
                    $hrmSyncInfo['last_sync_time'] = $rM['max_time'];
                }
            }
            if (!empty($hrmSyncInfo['last_sync_time'])) {
                $hrmSyncInfo['last_sync_time_formatted'] = date('d/m/Y H:i:s', strtotime($hrmSyncInfo['last_sync_time']));
            }

            $where = "WHERE 1=1";

            // Lọc theo ngày tăng ca cụ thể (ot_date)
            if (!empty($otDate)) {
                $d = $conn->real_escape_string($otDate);
                $where .= " AND r.ot_date = '{$d}'";
            } else {
                if ($month > 0) {
                    $where .= " AND MONTH(r.ot_date) = {$month}";
                }
                if ($year > 0) {
                    $where .= " AND YEAR(r.ot_date) = {$year}";
                }
            }

            // Lọc theo trạng thái đối soát
            if (!empty($status)) {
                if ($status === 'completed') {
                    // Đã hoàn thành đủ 2 bước
                    $where .= " AND r.plan_id IS NOT NULL AND r.actual_id IS NOT NULL";
                } else if ($status === 'uncompleted_actual') {
                    // Ca chưa hoàn thành Bước 2 (Thực tế)
                    $where .= " AND (r.actual_id IS NULL OR r.reconcile_status = 'plan_only')";
                } else if ($status === 'overdue_3days') {
                    // Thiếu 1 trong 2 bước và quá 3 ngày
                    $where .= " AND (r.actual_id IS NULL OR r.plan_id IS NULL) AND DATEDIFF(CURRENT_DATE, r.ot_date) > 3";
                } else if ($status === 'needs_explanation') {
                    $where .= " AND (r.explanation_requested = 1 OR r.needs_explanation = 1) AND (r.is_explained = 0 OR r.is_explained IS NULL) AND (r.is_dismissed = 0 OR r.is_dismissed IS NULL)";
                } else if ($status === 'dismissed') {
                    $where .= " AND r.is_dismissed = 1";
                } else if ($status === 'explained') {
                    // Đã chuyển giải trình
                    $where .= " AND r.is_explained = 1";
                } else {
                    $where .= " AND r.reconcile_status = '" . $conn->real_escape_string($status) . "'";
                }
            } else if ($hideCompleted) {
                // Tùy chọn tự động tạm ẩn các dòng hoàn thành khi đang ở tab Tất cả
                $where .= " AND (r.actual_id IS NULL OR r.plan_id IS NULL OR r.is_dismissed = 1 OR r.is_explained = 1)";
            }

            // Tìm kiếm theo tên hoặc mã nhân viên
            if (!empty($search)) {
                $s = $conn->real_escape_string($search);
                $where .= " AND (r.employee_code LIKE '%{$s}%' OR COALESCE(p.full_name, a.full_name, e.full_name) LIKE '%{$s}%' OR r.id LIKE '%{$s}%')";
            }

            // Đếm tổng số bản ghi
            $sqlCount = "
                SELECT COUNT(*) FROM ot_reconciliations r
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON r.employee_code = e.employee_code
                {$where}
            ";
            $resCount = $conn->query($sqlCount);
            $totalRows = $resCount ? intval($resCount->fetch_row()[0]) : 0;

            // Đếm theo từng trạng thái để làm thống kê badge (theo năm hoặc ngày)
            $badgeWhere = "WHERE 1=1";
            if (!empty($otDate)) {
                $badgeWhere .= " AND r.ot_date = '" . $conn->real_escape_string($otDate) . "'";
            } else if ($year > 0) {
                $badgeWhere .= " AND YEAR(r.ot_date) = {$year}";
            }

            $sqlBadges = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN r.plan_id IS NOT NULL AND r.actual_id IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN r.actual_id IS NULL OR r.reconcile_status = 'plan_only' THEN 1 ELSE 0 END) as uncompleted_actual,
                    SUM(CASE WHEN (r.actual_id IS NULL OR r.plan_id IS NULL) AND DATEDIFF(CURRENT_DATE, r.ot_date) > 3 THEN 1 ELSE 0 END) as overdue_3days,
                    SUM(CASE WHEN (r.explanation_requested = 1 OR r.needs_explanation = 1) AND (r.is_explained = 0 OR r.is_explained IS NULL) AND (r.is_dismissed = 0 OR r.is_dismissed IS NULL) THEN 1 ELSE 0 END) as needs_explanation,
                    SUM(CASE WHEN r.is_dismissed = 1 THEN 1 ELSE 0 END) as dismissed_count,
                    SUM(CASE WHEN r.is_explained = 1 THEN 1 ELSE 0 END) as explained_count
                FROM ot_reconciliations r
                {$badgeWhere}
            ";
            $resBadges = $conn->query($sqlBadges);
            $badgeStats = $resBadges ? $resBadges->fetch_assoc() : [];

            // Lấy danh sách phân trang: Ưu tiên lệnh chưa hoàn thành thực tế hoặc quá hạn lên trên cùng
            $sqlData = "
                SELECT 
                    r.*,
                    COALESCE(p.full_name, a.full_name, e.full_name) AS full_name,
                    COALESCE(p.group_name, a.group_name, e.cost_center) AS group_name,
                    COALESCE(p.team_name, a.team_name) AS team_name,
                    p.start_time AS plan_start_time,
                    p.end_time AS plan_end_time,
                    p.reason AS plan_reason,
                    p.direct_manager AS plan_dm,
                    p.indirect_manager AS plan_idm,
                    p.approval_status AS plan_approval_status,
                    a.start_time_actual AS actual_start_time,
                    a.end_time_actual AS actual_end_time,
                    a.reason AS actual_reason,
                    a.direct_manager AS act_dm,
                    a.indirect_manager AS act_idm,
                    a.approval_status AS actual_approval_status,
                    exp.id AS exp_ticket_id,
                    exp.approval_status AS exp_approval_status,
                    exp.explanation_content,
                    exp.violation_type,
                    exp.approver_username,
                    exp.approver_notes
                FROM ot_reconciliations r
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON r.employee_code = e.employee_code
                LEFT JOIN ot_explanations exp ON r.id = exp.reconciliation_id
                {$where}
                ORDER BY 
                    (CASE 
                        WHEN r.is_dismissed = 1 THEN 4
                        WHEN r.is_explained = 1 THEN 3
                        WHEN (r.actual_id IS NULL OR r.plan_id IS NULL) AND DATEDIFF(CURRENT_DATE, r.ot_date) > 3 THEN 0
                        WHEN r.actual_id IS NULL OR r.reconcile_status = 'plan_only' THEN 1
                        ELSE 2
                    END) ASC,
                    r.ot_date DESC,
                    r.id DESC
                LIMIT {$offset}, {$limit}
            ";
            $resData = $conn->query($sqlData);
            $items = [];
            $todayTimestamp = strtotime(date('Y-m-d'));

            while ($r = $resData->fetch_assoc()) {
                // Kiểm tra hoàn thành 2 bước
                $r['step1_done'] = !empty($r['plan_id']);
                $r['step2_done'] = !empty($r['actual_id']);
                $r['is_fully_completed'] = ($r['step1_done'] && $r['step2_done']);

                // Tính toán số ngày trôi qua so với ot_date
                $otTimestamp = strtotime($r['ot_date']);
                $daysDiff = max(0, floor(($todayTimestamp - $otTimestamp) / 86400));
                $r['days_diff'] = $daysDiff;
                $r['is_overdue_3days'] = (!$r['is_fully_completed'] && $daysDiff > 3);

                $r['order_code'] = '#OT-' . str_pad($r['id'], 6, '0', STR_PAD_LEFT);
                $items[] = $r;
            }

            echo json_encode([
                'success' => true,
                'total' => $totalRows,
                'page' => $page,
                'limit' => $limit,
                'badges' => $badgeStats,
                'hrm_sync' => $hrmSyncInfo,
                'data' => $items
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. YÊU CẦU GIẢI TRÌNH CHỦ ĐỘNG TỪ ADMIN (REQUEST EXPLANATION)
        // Dữ liệu giải trình CHỈ được sinh ra khi Admin bấm nút này
        // =====================================================================
        case 'request_explanation':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền yêu cầu giải trình!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $recId = intval($_POST['rec_id'] ?? ($_GET['rec_id'] ?? 0));
            $violationType = trim($_POST['violation_type'] ?? ($_GET['violation_type'] ?? 'Yêu cầu giải trình vi phạm tăng ca'));
            $adminNote = trim($_POST['admin_note'] ?? ($_GET['admin_note'] ?? ''));

            if ($recId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ca đối soát không hợp lệ']);
                exit;
            }

            $stmtRec = $conn->prepare("SELECT * FROM ot_reconciliations WHERE id = ?");
            $stmtRec->bind_param("i", $recId);
            $stmtRec->execute();
            $recRow = $stmtRec->get_result()->fetch_assoc();
            $stmtRec->close();

            if (!$recRow) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy ca đối soát tương ứng']);
                exit;
            }

            $conn->begin_transaction();

            // 1. Cập nhật bảng đối soát: đánh dấu đã yêu cầu giải trình
            $stmtUp = $conn->prepare("
                UPDATE ot_reconciliations 
                SET explanation_requested = 1,
                    explanation_requested_at = CURRENT_TIMESTAMP,
                    explanation_requested_by = ?,
                    needs_explanation = 1,
                    is_dismissed = 0,
                    dismissed_at = NULL,
                    dismissed_by = NULL,
                    dismiss_reason = NULL
                WHERE id = ?
            ");
            $stmtUp->bind_param("si", $currentUser, $recId);
            $stmtUp->execute();
            $stmtUp->close();

            // 2. Tạo hoặc kích hoạt bản ghi trong ot_explanations để nhân viên / tổ trưởng nạp lý do
            $stmtExpCheck = $conn->prepare("SELECT id FROM ot_explanations WHERE reconciliation_id = ?");
            $stmtExpCheck->bind_param("i", $recId);
            $stmtExpCheck->execute();
            $expRow = $stmtExpCheck->get_result()->fetch_assoc();
            $stmtExpCheck->close();

            if ($expRow) {
                $expId = intval($expRow['id']);
                $stmtExpUp = $conn->prepare("
                    UPDATE ot_explanations 
                    SET violation_type = ?,
                        approver_notes = ?,
                        approval_status = 'pending',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmtExpUp->bind_param("ssi", $violationType, $adminNote, $expId);
                $stmtExpUp->execute();
                $stmtExpUp->close();
            } else {
                $stmtExpIn = $conn->prepare("
                    INSERT INTO ot_explanations (
                        reconciliation_id, employee_code, ot_date, violation_type, approver_notes, approval_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
                ");
                $stmtExpIn->bind_param("issss", $recId, $recRow['employee_code'], $recRow['ot_date'], $violationType, $adminNote);
                $stmtExpIn->execute();
                $expId = $stmtExpIn->insert_id;
                $stmtExpIn->close();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Đã gửi yêu cầu giải trình cho nhân viên thành công! Lệnh đã được lưu vào hồ sơ giải trình.',
                'rec_id' => $recId
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. TẠM HỦY YÊU CẦU GIẢI TRÌNH (DISMISS REQUEST)
        // Bỏ qua giải trình khi có sự đồng ý của Admin hoặc trường hợp ngoại lệ
        // =====================================================================
        case 'dismiss_request':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền tạm hủy!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $recId = intval($_POST['rec_id'] ?? ($_GET['rec_id'] ?? 0));
            $reason = trim($_POST['reason'] ?? ($_GET['reason'] ?? 'Bỏ qua yêu cầu giải trình'));

            if ($recId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ca đối soát không hợp lệ']);
                exit;
            }

            $conn->begin_transaction();

            $stmtUp = $conn->prepare("
                UPDATE ot_reconciliations 
                SET is_dismissed = 1,
                    dismissed_at = CURRENT_TIMESTAMP,
                    dismissed_by = ?,
                    dismiss_reason = ?,
                    needs_explanation = 0
                WHERE id = ?
            ");
            $stmtUp->bind_param("ssi", $currentUser, $reason, $recId);
            $stmtUp->execute();
            $stmtUp->close();

            // Nếu có ticket giải trình chưa có nội dung, dọn dẹp để không xuất hiện bên trang giải trình
            $stmtDelExp = $conn->prepare("
                DELETE FROM ot_explanations 
                WHERE reconciliation_id = ? 
                  AND approval_status = 'pending' 
                  AND (explanation_content IS NULL OR explanation_content = '')
            ");
            $stmtDelExp->bind_param("i", $recId);
            $stmtDelExp->execute();
            $stmtDelExp->close();

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Đã tạm hủy yêu cầu giải trình thành công. Lệnh đã được bỏ qua và ngừng gửi cảnh báo.',
                'rec_id' => $recId
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. KHÔI PHỤC LỆNH ĐÃ TẠM HỦY (RESTORE DISMISSED)
        // =====================================================================
        case 'restore_request':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền khôi phục!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $recId = intval($_POST['rec_id'] ?? ($_GET['rec_id'] ?? 0));
            if ($recId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ca đối soát không hợp lệ']);
                exit;
            }

            $stmtUp = $conn->prepare("
                UPDATE ot_reconciliations 
                SET is_dismissed = 0,
                    dismissed_at = NULL,
                    dismissed_by = NULL,
                    dismiss_reason = NULL
                WHERE id = ?
            ");
            $stmtUp->bind_param("i", $recId);
            $stmtUp->execute();
            $stmtUp->close();

            echo json_encode([
                'success' => true,
                'message' => 'Đã khôi phục lệnh tăng ca thành công!',
                'rec_id' => $recId
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. CHUYỂN TRẠNG THÁI GIẢI TRÌNH TRỰC TIẾP (SWITCH EXPLANATION)
        // =====================================================================
        case 'switch_explanation':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền chuyển giải trình!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $recId = intval($_POST['rec_id'] ?? ($_GET['rec_id'] ?? 0));
            $content = trim($_POST['explanation_content'] ?? ($_GET['explanation_content'] ?? ''));
            $violationType = trim($_POST['violation_type'] ?? ($_GET['violation_type'] ?? 'Giải trình quá hạn / chênh lệch'));

            if ($recId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ca đối soát không hợp lệ']);
                exit;
            }

            // Lấy thông tin bản ghi đối soát
            $stmtRec = $conn->prepare("SELECT * FROM ot_reconciliations WHERE id = ?");
            $stmtRec->bind_param("i", $recId);
            $stmtRec->execute();
            $recRow = $stmtRec->get_result()->fetch_assoc();
            $stmtRec->close();

            if (!$recRow) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy ca đối soát tương ứng']);
                exit;
            }

            $conn->begin_transaction();

            // 1. Cập nhật bảng ot_reconciliations: is_explained = 1, tắt cờ nhắc nhở, lưu vết người và thời gian
            $stmtUp = $conn->prepare("
                UPDATE ot_reconciliations 
                SET is_explained = 1,
                    explanation_requested = 1,
                    needs_explanation = 0,
                    explained_at = CURRENT_TIMESTAMP,
                    explained_by = ?,
                    explanation_note = ?
                WHERE id = ?
            ");
            $stmtUp->bind_param("ssi", $currentUser, $content, $recId);
            $stmtUp->execute();
            $stmtUp->close();

            // 2. Cập nhật hoặc thêm mới vào ot_explanations
            $stmtExp = $conn->prepare("SELECT id FROM ot_explanations WHERE reconciliation_id = ?");
            $stmtExp->bind_param("i", $recId);
            $stmtExp->execute();
            $existingExp = $stmtExp->get_result()->fetch_assoc();
            $stmtExp->close();

            if ($existingExp) {
                $expId = intval($existingExp['id']);
                $stmtExpUp = $conn->prepare("
                    UPDATE ot_explanations 
                    SET explanation_content = ?,
                        submitted_by = ?,
                        submitted_at = CURRENT_TIMESTAMP,
                        approval_status = 'submitted'
                    WHERE id = ?
                ");
                $stmtExpUp->bind_param("ssi", $content, $currentUser, $expId);
                $stmtExpUp->execute();
                $stmtExpUp->close();
            } else {
                $stmtExpIn = $conn->prepare("
                    INSERT INTO ot_explanations (
                        reconciliation_id, employee_code, ot_date, violation_type,
                        explanation_content, submitted_by, submitted_at, approval_status
                    ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 'submitted')
                ");
                $stmtExpIn->bind_param("isssss", $recId, $recRow['employee_code'], $recRow['ot_date'], $violationType, $content, $currentUser);
                $stmtExpIn->execute();
                $expId = $stmtExpIn->insert_id;
                $stmtExpIn->close();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Đã chuyển trạng thái giải trình thành công. Lệnh đã được lưu trữ, tạm tô xám và ngừng gửi thông báo nhắc nhở.',
                'rec_id' => $recId,
                'is_explained' => 1
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. CHI TIẾT ĐỐI SOÁT
        // =====================================================================
        case 'get_detail':
            $recId = intval($_GET['id'] ?? 0);
            $stmt = $conn->prepare("
                SELECT 
                    r.*,
                    COALESCE(p.full_name, a.full_name, e.full_name) AS full_name,
                    COALESCE(p.group_name, a.group_name, e.cost_center) AS group_name,
                    COALESCE(p.team_name, a.team_name) AS team_name,
                    p.start_time AS plan_start, p.end_time AS plan_end, p.total_minutes AS plan_min, p.reason AS plan_reason, p.direct_manager AS plan_dm, p.indirect_manager AS plan_idm, p.approval_status AS plan_status,
                    a.start_time_actual AS act_start, a.end_time_actual AS act_end, a.total_minutes_actual AS act_min, a.reason AS act_reason, a.direct_manager AS act_dm, a.indirect_manager AS act_idm, a.approval_status AS act_status,
                    exp.id AS exp_id, exp.explanation_content, exp.approval_status AS exp_status, exp.approver_username, exp.approver_notes, exp.violation_type
                FROM ot_reconciliations r
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON r.employee_code = e.employee_code
                LEFT JOIN ot_explanations exp ON r.id = exp.reconciliation_id
                WHERE r.id = ?
            ");
            $stmt->bind_param("i", $recId);
            $stmt->execute();
            $detail = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$detail) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy dữ liệu đối soát']);
                exit;
            }

            $detail['order_code'] = '#OT-' . str_pad($detail['id'], 6, '0', STR_PAD_LEFT);
            $detail['step1_done'] = !empty($detail['plan_id']);
            $detail['step2_done'] = !empty($detail['actual_id']);

            echo json_encode(['success' => true, 'detail' => $detail], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. SO KHỚP LẠI RIÊNG 01 LỆNH (RECONCILE SINGLE ROW)
        // =====================================================================
        case 'reconcile_single':
            $recId = intval($_POST['id'] ?? ($_GET['id'] ?? 0));
            $stmt = $conn->prepare("SELECT id, employee_code, ot_date, plan_id, actual_id FROM ot_reconciliations WHERE id = ?");
            $stmt->bind_param("i", $recId);
            $stmt->execute();
            $recRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$recRow) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bản ghi đối soát']);
                exit;
            }

            $empCode = $recRow['employee_code'];
            $otDate = $recRow['ot_date'];

            // Lấy kế hoạch và thực tế mới nhất của nhân viên trong ngày đó
            $planRes = $conn->query("SELECT * FROM ot_plans WHERE employee_code = '{$empCode}' AND ot_date = '{$otDate}' LIMIT 1");
            $plan = $planRes ? $planRes->fetch_assoc() : null;

            $actRes = $conn->query("SELECT * FROM ot_actuals WHERE employee_code = '{$empCode}' AND ot_date = '{$otDate}' LIMIT 1");
            $act = $actRes ? $actRes->fetch_assoc() : null;

            $planId = $plan ? intval($plan['id']) : null;
            $actId = $act ? intval($act['id']) : null;
            $planMin = $plan ? intval($plan['total_minutes']) : 0;
            $actMin = $act ? intval($act['total_minutes_actual']) : 0;
            $diffMin = $actMin - $planMin;

            // Xác định trạng thái mới
            $newStatus = 'matched';
            if ($plan && !$act) {
                $newStatus = 'plan_only';
            } else if (!$plan && $act) {
                $newStatus = 'actual_only';
            } else if ($plan && $act) {
                if ($diffMin !== 0 || $plan['start_time'] !== $act['start_time_actual'] || $plan['end_time'] !== $act['end_time_actual']) {
                    $newStatus = 'time_diff';
                } else {
                    $newStatus = 'matched';
                }
            }

            $isOverdue = 0;
            $daysDiff = (time() - strtotime($otDate)) / 86400;
            if ($daysDiff > 3 && (!$plan || !$act)) {
                $isOverdue = 1;
            }

            $stmtUp = $conn->prepare("
                UPDATE ot_reconciliations 
                SET plan_id = ?, actual_id = ?, reconcile_status = ?, plan_minutes = ?, actual_minutes = ?, diff_minutes = ?, is_overdue = ?, last_reconciled_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmtUp->bind_param("iisiiiii", $planId, $actId, $newStatus, $planMin, $actMin, $diffMin, $isOverdue, $recId);
            $stmtUp->execute();
            $stmtUp->close();

            echo json_encode([
                'success' => true,
                'message' => 'Đã so khớp lại lệnh đối soát thành công!',
                'reconcile_status' => $newStatus,
                'diff_minutes' => $diffMin,
                'is_overdue' => $isOverdue
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. XÓA BẢN GHI ĐỐI SOÁT
        // =====================================================================
        case 'delete_reconciliation':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền xóa bản ghi đối soát!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $recId = intval($_POST['id'] ?? ($_GET['id'] ?? 0));
            if ($recId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã bản ghi không hợp lệ.']);
                exit;
            }

            $conn->query("DELETE FROM ot_explanations WHERE reconciliation_id = {$recId}");
            $delRes = $conn->query("DELETE FROM ot_reconciliations WHERE id = {$recId}");

            if ($delRes) {
                echo json_encode(['success' => true, 'message' => "Đã xóa bản ghi đối soát #OT-" . str_pad($recId, 6, '0', STR_PAD_LEFT) . " thành công!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa bản ghi: ' . $conn->error]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
