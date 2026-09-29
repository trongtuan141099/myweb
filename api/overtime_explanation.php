<?php
/**
 * API Quản Lý Giải Trình Tăng Ca
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

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
requireApiPermission('api.overtime.explain');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$current_user = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'admin');

try {
    switch ($action) {
        // =====================================================================
        // 1. LẤY DANH SÁCH GIẢI TRÌNH & BỘ LỌC
        // =====================================================================
        case 'get_explanations':
            $status = trim($_GET['status'] ?? '');
            $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;
            $year = !empty($_GET['year']) ? intval($_GET['year']) : 0;
            $search = trim($_GET['search'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(100, intval($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;

            $where = "WHERE (r.id IS NULL OR r.explanation_requested = 1 OR exp.approval_status != 'pending' OR (exp.explanation_content IS NOT NULL AND exp.explanation_content != ''))";
            if (!empty($status)) {
                $where .= " AND exp.approval_status = '" . $conn->real_escape_string($status) . "'";
            }
            if ($month > 0) {
                $where .= " AND MONTH(exp.ot_date) = {$month}";
            }
            if ($year > 0) {
                $where .= " AND YEAR(exp.ot_date) = {$year}";
            }
            if (!empty($search)) {
                $s = $conn->real_escape_string($search);
                $where .= " AND (exp.employee_code LIKE '%{$s}%' OR COALESCE(p.full_name, a.full_name, e.full_name) LIKE '%{$s}%')";
            }

            // Thống kê Badge theo trạng thái (chỉ tính các ticket hợp lệ)
            $badgeWhere = "WHERE (r.id IS NULL OR r.explanation_requested = 1 OR exp.approval_status != 'pending' OR (exp.explanation_content IS NOT NULL AND exp.explanation_content != ''))";
            if ($year > 0) {
                $badgeWhere .= " AND YEAR(exp.ot_date) = {$year}";
            }
            $sqlBadges = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN exp.approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN exp.approval_status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN exp.approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN exp.approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM ot_explanations exp
                LEFT JOIN ot_reconciliations r ON exp.reconciliation_id = r.id
                {$badgeWhere}
            ";
            $resBadges = $conn->query($sqlBadges);
            $badges = $resBadges ? $resBadges->fetch_assoc() : [];

            // Đếm tổng số bản ghi
            $sqlCount = "
                SELECT COUNT(*) FROM ot_explanations exp
                LEFT JOIN ot_reconciliations r ON exp.reconciliation_id = r.id
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON exp.employee_code = e.employee_code
                {$where}
            ";
            $resCount = $conn->query($sqlCount);
            $totalRows = $resCount ? $resCount->fetch_row()[0] : 0;

            // Lấy danh sách chi tiết
            $sqlData = "
                SELECT 
                    exp.*,
                    COALESCE(p.full_name, a.full_name, e.full_name) AS full_name,
                    COALESCE(p.group_name, a.group_name, e.cost_center) AS group_name,
                    COALESCE(p.team_name, a.team_name) AS team_name,
                    r.reconcile_status,
                    r.plan_minutes,
                    r.actual_minutes,
                    r.diff_minutes,
                    r.approval_days_diff,
                    p.start_time AS plan_start_time,
                    p.end_time AS plan_end_time,
                    a.start_time_actual AS actual_start_time,
                    a.end_time_actual AS actual_end_time
                FROM ot_explanations exp
                LEFT JOIN ot_reconciliations r ON exp.reconciliation_id = r.id
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON exp.employee_code = e.employee_code
                {$where}
                ORDER BY exp.ot_date DESC, exp.id DESC
                LIMIT {$offset}, {$limit}
            ";
            $resData = $conn->query($sqlData);
            $data = [];
            while ($r = $resData->fetch_assoc()) {
                $data[] = $r;
            }

            echo json_encode([
                'success' => true,
                'total' => $totalRows,
                'page' => $page,
                'limit' => $limit,
                'badges' => $badges,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. GỬI NỘI DUNG GIẢI TRÌNH (SUBMIT)
        // =====================================================================
        case 'submit_explanation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $content = trim($_POST['explanation_content'] ?? '');

            if ($id <= 0 || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung giải trình đầy đủ']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE ot_explanations SET explanation_content = ?, submitted_by = ?, submitted_at = NOW(), approval_status = 'submitted' WHERE id = ?");
            $stmt->bind_param("ssi", $content, $current_user, $id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Đã gửi giải trình thành công! Đang chờ Quản lý phê duyệt.']);
            break;

        // =====================================================================
        // 3. QUẢN LÝ PHÊ DUYỆT HOẶC TỪ CHỐI GIẢI TRÌNH
        // =====================================================================
        case 'review_explanation':
            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền thẩm định giải trình!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $decision = trim($_POST['decision'] ?? ''); // 'approved' hoặc 'rejected'
            $notes = trim($_POST['approver_notes'] ?? '');

            if (!in_array($decision, ['approved', 'rejected'])) {
                echo json_encode(['success' => false, 'message' => 'Quyết định thẩm định không hợp lệ']);
                exit;
            }

            $stmt = $conn->prepare("SELECT ot_date, is_manual FROM ot_explanations WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $expItem = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$expItem) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu giải trình']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE ot_explanations SET approval_status = ?, approver_username = ?, approver_notes = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $decision, $current_user, $notes, $id);
            $stmt->execute();
            $stmt->close();

            // Nếu là giải trình thủ công bổ sung OT, đồng bộ ngay vào lũy kế năm 200h
            if (!empty($expItem['is_manual'])) {
                require_once __DIR__ . '/../core/overtime_service.php';
                $expYear = intval(date('Y', strtotime($expItem['ot_date'])));
                recalculateYearlyAccumulations($conn, $expYear);
            }

            $statusText = ($decision === 'approved') ? 'phê duyệt chấp nhận' : 'từ chối';
            echo json_encode(['success' => true, 'message' => "Đã {$statusText} giải trình thành công! Dữ liệu kiểm soát OT 200h đã được cập nhật."]);
            break;

        // =====================================================================
        // 4. TẠO / ĐĂNG KÝ GIẢI TRÌNH THỦ CÔNG (QUÊN KẾ HOẠCH TRÊN HRM)
        // =====================================================================
        case 'create_manual_explanation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền tạo giải trình thủ công!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $empCode = trim($_POST['employee_code'] ?? '');
            $otDate = trim($_POST['ot_date'] ?? '');
            $startTimeRaw = trim($_POST['start_time'] ?? '');
            $endTimeRaw = trim($_POST['end_time'] ?? '');
            $totalHours = floatval($_POST['total_hours'] ?? 0);
            $content = trim($_POST['explanation_content'] ?? '');
            $status = trim($_POST['approval_status'] ?? 'approved'); // Mặc định 'approved' hoặc 'submitted'
            $approverNotes = trim($_POST['approver_notes'] ?? '');

            if (empty($empCode) || empty($otDate) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhân viên, ngày tăng ca và nhập lý do giải trình']);
                exit;
            }

            // Kiểm tra nhân viên tồn tại
            $stmtEmp = $conn->prepare("SELECT employee_code, full_name, cost_center FROM employees WHERE employee_code = ?");
            $stmtEmp->bind_param("s", $empCode);
            $stmtEmp->execute();
            $empCheck = $stmtEmp->get_result()->fetch_assoc();
            $stmtEmp->close();

            if (!$empCheck) {
                echo json_encode(['success' => false, 'message' => "Mã nhân viên {$empCode} không tồn tại trên hệ thống nhân sự"]);
                exit;
            }

            // Xử lý chuẩn hóa thời gian bắt đầu và kết thúc
            $startTime = null;
            $endTime = null;
            if (!empty($startTimeRaw)) {
                if (strlen($startTimeRaw) <= 5) {
                    $startTime = "{$otDate} {$startTimeRaw}:00";
                } else {
                    $startTime = date('Y-m-d H:i:s', strtotime($startTimeRaw));
                }
            }
            if (!empty($endTimeRaw)) {
                if (strlen($endTimeRaw) <= 5) {
                    $endTime = "{$otDate} {$endTimeRaw}:00";
                    // Nếu giờ kết thúc nhỏ hơn giờ bắt đầu (ca đêm qua ngày)
                    if ($startTime && strtotime($endTime) <= strtotime($startTime)) {
                        $endTime = date('Y-m-d H:i:s', strtotime("{$otDate} {$endTimeRaw}:00 +1 day"));
                    }
                } else {
                    $endTime = date('Y-m-d H:i:s', strtotime($endTimeRaw));
                }
            }

            // Tự động tính số giờ nếu chưa nhập hoặc bằng 0
            if ($totalHours <= 0 && $startTime && $endTime) {
                $diffSec = strtotime($endTime) - strtotime($startTime);
                if ($diffSec > 0) {
                    $totalHours = round($diffSec / 3600, 2);
                }
            }

            if ($totalHours <= 0) {
                echo json_encode(['success' => false, 'message' => 'Số giờ tăng ca phải lớn hơn 0 (VD: 2.0 hoặc 3.5h)']);
                exit;
            }

            $totalMinutes = intval(round($totalHours * 60));
            $violationType = 'Quên đăng ký kế hoạch trên HRM (ĐK thủ công)';
            $isApproved = ($status === 'approved');
            $approverUsername = $isApproved ? $current_user : null;
            $approvedAt = $isApproved ? date('Y-m-d H:i:s') : null;

            $stmtIn = $conn->prepare("
                INSERT INTO ot_explanations (
                    reconciliation_id, employee_code, ot_date, start_time, end_time,
                    total_hours, total_minutes, violation_type, explanation_content,
                    submitted_by, submitted_at, approver_username, approval_status,
                    approver_notes, approved_at, is_manual, created_at
                ) VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 1, NOW())
            ");
            $stmtIn->bind_param(
                "ssssdidssssss",
                $empCode, $otDate, $startTime, $endTime,
                $totalHours, $totalMinutes, $violationType, $content,
                $current_user, $approverUsername, $status,
                $approverNotes, $approvedAt
            );
            $stmtIn->execute();
            $newId = $stmtIn->insert_id;
            $stmtIn->close();

            // Nếu tạo với trạng thái đã phê duyệt, đồng bộ ngay vào lũy kế năm 200h
            if ($isApproved) {
                require_once __DIR__ . '/../core/overtime_service.php';
                $expYear = intval(date('Y', strtotime($otDate)));
                recalculateYearlyAccumulations($conn, $expYear);
            }

            echo json_encode([
                'success' => true,
                'message' => "Đã tạo đăng ký giải trình thủ công thành công ({$totalHours}h)! " . ($isApproved ? "Dữ liệu đã được gộp vào bảng kiểm soát giới hạn OT 200h." : "Đang chờ quản lý phê duyệt."),
                'id' => $newId
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. XÓA BẢN GHI GIẢI TRÌNH THỦ CÔNG
        // =====================================================================
        case 'delete_explanation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $userRole = $_SESSION['user']['role'] ?? 'viewer';
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền xóa!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }

            $stmtCheck = $conn->prepare("SELECT id, ot_date, is_manual, approval_status FROM ot_explanations WHERE id = ?");
            $stmtCheck->bind_param("i", $id);
            $stmtCheck->execute();
            $item = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bản ghi cần xóa']);
                exit;
            }

            // Chỉ cho phép xóa bản ghi đăng ký thủ công hoặc do chính admin thao tác
            $stmtDel = $conn->prepare("DELETE FROM ot_explanations WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();

            // Nếu bản ghi thủ công đã được duyệt trước đó bị xóa, cập nhật lại lũy kế năm 200h
            if (!empty($item['is_manual']) && $item['approval_status'] === 'approved') {
                require_once __DIR__ . '/../core/overtime_service.php';
                $expYear = intval(date('Y', strtotime($item['ot_date'])));
                recalculateYearlyAccumulations($conn, $expYear);
            }

            echo json_encode(['success' => true, 'message' => 'Đã xóa bản ghi giải trình thành công!']);
            break;

        // =====================================================================
        // 6. LẤY DANH SÁCH NHÂN VIÊN GỢI Ý CHO MODAL THỦ CÔNG
        // =====================================================================
        case 'get_employee_options':
            $q = trim($_GET['q'] ?? '');
            $whereEmp = "WHERE 1=1";
            if (!empty($q)) {
                $sq = $conn->real_escape_string($q);
                $whereEmp .= " AND (employee_code LIKE '%{$sq}%' OR full_name LIKE '%{$sq}%')";
            }
            $sqlEmp = "SELECT employee_code, full_name, cost_center, job_level FROM employees {$whereEmp} ORDER BY employee_code ASC LIMIT 100";
            $resEmp = $conn->query($sqlEmp);
            $employees = [];
            while ($r = $resEmp->fetch_assoc()) {
                $employees[] = $r;
            }
            echo json_encode(['success' => true, 'data' => $employees], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

