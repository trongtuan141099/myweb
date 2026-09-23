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

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.']);
    exit;
}

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

            $where = "WHERE 1=1";
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

            // Thống kê Badge theo trạng thái
            $sqlBadges = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN approval_status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM ot_explanations exp
                " . ($year > 0 ? "WHERE YEAR(exp.ot_date) = {$year}" : "") . "
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

            $stmt = $conn->prepare("UPDATE ot_explanations SET approval_status = ?, approver_username = ?, approver_notes = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $decision, $current_user, $notes, $id);
            $stmt->execute();
            $stmt->close();

            $statusText = ($decision === 'approved') ? 'phê duyệt chấp nhận' : 'từ chối';
            echo json_encode(['success' => true, 'message' => "Đã {$statusText} giải trình thành công!"]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

