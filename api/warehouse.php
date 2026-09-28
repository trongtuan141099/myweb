<?php
/**
 * API Quản Lý Kho & Xuất Vật Tư
 * DX Plastic Group - Factory Management System
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/warehouse_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền cơ bản: đã đăng nhập hoặc có quyền warehouse/admin
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user']) && !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = [
    'id'            => $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1),
    'username'      => $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? 'user'),
    'fullname'      => $_SESSION['user']['fullname'] ?? ($_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'User')),
    'role'          => $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? 'viewer'),
    'employee_code' => $_SESSION['employee_code'] ?? ($_SESSION['user']['employee_code'] ?? '')
];

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_materials');

switch ($action) {
    // 1. Tìm kiếm & lấy danh sách vật tư
    case 'get_materials':
        $filters = [
            'group_name'       => trim($_GET['group_name'] ?? ''),
            'category_type'    => trim($_GET['category_type'] ?? ''),
            'search'           => trim($_GET['search'] ?? ''),
            'status'           => trim($_GET['status'] ?? ''),
            'is_active'        => isset($_GET['is_active']) ? trim($_GET['is_active']) : 'ALL',
            'include_inactive' => intval($_GET['include_inactive'] ?? 0)
        ];
        $materials = getWarehouseMaterials($conn, $filters);
        echo json_encode([
            'success'   => true,
            'materials' => $materials,
            'total'     => count($materials)
        ], JSON_UNESCAPED_UNICODE);
        break;

    // 2. Chi tiết 1 vật tư kèm lịch sử
    case 'get_material_detail':
        $materialId = intval($_GET['material_id'] ?? ($_POST['material_id'] ?? 0));
        $mat = getMaterialDetail($conn, $materialId);
        if ($mat) {
            echo json_encode(['success' => true, 'material' => $mat], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy vật tư.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // 3. Đề xuất tự động (Auto-suggestion) vật tư tiêu hao theo nhóm
    case 'get_suggestions':
        $groupName = trim($_GET['group_name'] ?? '');
        $month     = intval($_GET['month'] ?? date('m'));
        $year      = intval($_GET['year'] ?? date('Y'));

        $suggestions = getConsumableSuggestionsByGroup($conn, $groupName, $month, $year);
        echo json_encode([
            'success'     => true,
            'group_name'  => $groupName,
            'month'       => $month,
            'year'        => $year,
            'suggestions' => $suggestions,
            'total'       => count($suggestions)
        ], JSON_UNESCAPED_UNICODE);
        break;

    // 4. Tính toán số lượng đơn dòng thời gian thực
    case 'calculate_item':
        $materialId     = intval($_POST['material_id'] ?? 0);
        $machines       = floatval($_POST['machines_count'] ?? 0);
        $usesPerMachine = floatval($_POST['uses_per_machine'] ?? 0);
        $normPerUse     = floatval($_POST['norm_per_use'] ?? 0);
        $fieldStock     = floatval($_POST['field_stock'] ?? 0);
        $reusableStock  = floatval($_POST['reusable_stock'] ?? 0);

        $mat = getMaterialDetail($conn, $materialId);
        if (!$mat) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy vật tư'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $calc = calculateItemQuantities($mat, $machines, $usesPerMachine, $normPerUse, $fieldStock, $reusableStock);
        echo json_encode(['success' => true, 'calc' => $calc], JSON_UNESCAPED_UNICODE);
        break;

    // 5. Tạo phiếu yêu cầu xuất kho mới
    case 'create_issue':
        // Hỗ trợ cả application/json raw body và form-data
        $raw = file_get_contents('php://input');
        $jsonData = json_decode($raw, true);
        $data = !empty($jsonData) ? $jsonData : $_POST;

        $res = createIssueRequest($conn, $data, $currentUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 6. Danh sách phiếu xuất kho
    case 'get_issues':
        $filters = [
            'group_name' => trim($_GET['group_name'] ?? ''),
            'status'     => trim($_GET['status'] ?? ''),
            'issue_type' => trim($_GET['issue_type'] ?? ''),
            'month'      => intval($_GET['month'] ?? 0),
            'year'       => intval($_GET['year'] ?? 0),
            'search'     => trim($_GET['search'] ?? '')
        ];
        $page  = max(1, intval($_GET['page'] ?? 1));
        $limit = max(5, min(100, intval($_GET['limit'] ?? 15)));

        $res = getIssueList($conn, $filters, $page, $limit);

        // Thống kê số lượng theo từng bước trạng thái (Pipeline counts)
        $countsRes = $conn->query("
            SELECT status, COUNT(*) as cnt 
            FROM warehouse_issues 
            GROUP BY status
        ");
        $counts = [
            'pending_checker'     => 0,
            'pending_manager'     => 0,
            'pending_admin_issue' => 0,
            'pending_handover'    => 0,
            'completed'           => 0,
            'rejected'            => 0
        ];
        if ($countsRes) {
            while ($cRow = $countsRes->fetch_assoc()) {
                $counts[$cRow['status']] = intval($cRow['cnt']);
            }
        }

        echo json_encode([
            'success'     => true,
            'issues'      => $res['data'],
            'data'        => $res['data'],
            'total'       => $res['total_rows'],
            'total_rows'  => $res['total_rows'],
            'total_pages' => $res['total_pages'],
            'page'        => $res['page'],
            'limit'       => $res['limit'],
            'counts'      => $counts
        ], JSON_UNESCAPED_UNICODE);
        break;

    // 7. Chi tiết phiếu xuất kho
    case 'get_issue_detail':
        $issueId = intval($_GET['issue_id'] ?? ($_POST['issue_id'] ?? 0));
        $issue = getIssueDetail($conn, $issueId, $currentUser);
        if ($issue) {
            echo json_encode(['success' => true, 'issue' => $issue], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu yêu cầu.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // 8. Xử lý bước phê duyệt & xuất kho & bàn giao
    case 'approve_step':
        $issueId   = intval($_POST['issue_id'] ?? 0);
        $step      = trim($_POST['step'] ?? '');
        $actionOpt = trim($_POST['action_type'] ?? 'approve');
        $comment   = trim($_POST['comment'] ?? '');
        $extraData = [
            'receiver_name' => trim($_POST['receiver_name'] ?? ''),
            'receiver_code' => trim($_POST['receiver_code'] ?? '')
        ];

        // Lấy thông tin phiếu để kiểm tra quyền
        $resIssue = $conn->query("SELECT group_name FROM warehouse_issues WHERE id = {$issueId}");
        $groupName = ($resIssue && $r = $resIssue->fetch_assoc()) ? $r['group_name'] : 'ALL';

        if (!canUserApproveWarehouseStep($conn, $currentUser, $step, $groupName)) {
            echo json_encode([
                'success' => false,
                'message' => 'Bạn không được phân quyền phê duyệt ở bước này cho nhóm [' . $groupName . '].'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $res = processApprovalStep($conn, $issueId, $step, $actionOpt, $comment, $currentUser, $extraData);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 9. Thống kê Dashboard
    case 'get_dashboard':
        $month = intval($_GET['month'] ?? date('m'));
        $year  = intval($_GET['year'] ?? date('Y'));

        $dash = getWarehouseDashboardStats($conn, $month, $year);
        echo json_encode(['success' => true, 'data' => $dash], JSON_UNESCAPED_UNICODE);
        break;

    // 10. Quản lý cấu hình người phê duyệt
    case 'get_approvers':
        $approvers = getWarehouseApprovers($conn);
        echo json_encode(['success' => true, 'approvers' => $approvers], JSON_UNESCAPED_UNICODE);
        break;

    case 'save_approver':
        if ($currentUser['role'] !== 'admin' && !hasPermission('role.manage')) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Admin mới có quyền cấu hình người phê duyệt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id       = intval($_POST['id'] ?? 0);
        $roleType = trim($_POST['role_type'] ?? '');
        $grp      = trim($_POST['group_name'] ?? 'ALL');
        $uname    = trim($_POST['username'] ?? '');
        $fname    = trim($_POST['full_name'] ?? '');

        if (!$roleType || !$uname || !$fname) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ vai trò, username và họ tên.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE warehouse_approvers SET role_type = ?, group_name = ?, username = ?, full_name = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $roleType, $grp, $uname, $fname, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật người phê duyệt.'], JSON_UNESCAPED_UNICODE);
        } else {
            $stmt = $conn->prepare("INSERT INTO warehouse_approvers (role_type, group_name, username, full_name, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $roleType, $grp, $uname, $fname);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Đã thêm người phê duyệt mới.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'delete_approver':
        if ($currentUser['role'] !== 'admin' && !hasPermission('role.manage')) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Admin mới có quyền xóa người phê duyệt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $conn->query("DELETE FROM warehouse_approvers WHERE id = {$id}");
        echo json_encode(['success' => true, 'message' => 'Đã xóa người phê duyệt.'], JSON_UNESCAPED_UNICODE);
        break;

    // 11. Cảnh báo điểm đặt hàng (ROP Alerts)
    case 'get_reorder_alerts':
        $status = trim($_GET['status'] ?? 'pending');
        $where = ($status !== 'ALL') ? "WHERE status = '{$conn->real_escape_string($status)}'" : "";
        $res = $conn->query("SELECT * FROM warehouse_reorder_alerts {$where} ORDER BY id DESC LIMIT 50");
        $alerts = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) $alerts[] = $r;
        }
        echo json_encode(['success' => true, 'alerts' => $alerts], JSON_UNESCAPED_UNICODE);
        break;

    case 'update_reorder_alert':
        $alertId = intval($_POST['alert_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'ordered');
        $adminNotes = trim($_POST['admin_notes'] ?? '');

        $stmt = $conn->prepare("UPDATE warehouse_reorder_alerts SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $adminNotes, $alertId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật tình trạng xử lý cảnh báo đặt hàng.'], JSON_UNESCAPED_UNICODE);
        break;

    // 12. Cập nhật tồn kho vật tư (Dành cho Quản lý / Thủ kho)
    case 'update_stock':
        if ($currentUser['role'] !== 'admin' && !hasPermission('warehouse.manage')) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Quản lý hoặc Thủ kho mới có quyền cập nhật tồn kho.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $matId    = intval($_POST['material_id'] ?? 0);
        $newStock = max(0, floatval($_POST['stock_current'] ?? 0));
        $newRop   = max(0, floatval($_POST['reorder_point'] ?? 0));
        $newMoq   = max(0, floatval($_POST['reorder_qty'] ?? 0));
        $bin      = trim($_POST['bin_location'] ?? '');

        $stmt = $conn->prepare("UPDATE warehouse_materials SET stock_current = ?, reorder_point = ?, reorder_qty = ?, bin_location = ? WHERE id = ?");
        $stmt->bind_param("dddsi", $newStock, $newRop, $newMoq, $bin, $matId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật thông tin tồn kho và điểm đặt hàng.'], JSON_UNESCAPED_UNICODE);
        break;

    // 13. Bật / Tắt trạng thái hoạt động (Vô hiệu hóa) của vật tư
    case 'toggle_material_active':
        if ($currentUser['role'] !== 'admin' && !hasPermission(['warehouse.manage', 'warehouse.materials', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Quản lý hoặc Admin mới có quyền bật/tắt trạng thái vật tư.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $matId    = intval($_POST['material_id'] ?? 0);
        $isActive = intval($_POST['is_active'] ?? 0);

        $stmt = $conn->prepare("UPDATE warehouse_materials SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $isActive, $matId);
        $stmt->execute();
        $stmt->close();

        $msg = $isActive ? 'Đã kích hoạt lại mã vật tư thành công!' : 'Đã vô hiệu hóa mã vật tư thành công!';
        echo json_encode(['success' => true, 'message' => $msg, 'is_active' => $isActive], JSON_UNESCAPED_UNICODE);
        break;

    // 14. Báo thủ kho khi hết hàng / Yêu cầu kiểm tra tồn kho & báo kỳ hạn
    case 'create_stock_request':
        $matId     = intval($_POST['material_id'] ?? 0);
        $reqType   = trim($_POST['request_type'] ?? 'stock_check');
        $neededQty = floatval($_POST['needed_qty'] ?? 0);
        $notes     = trim($_POST['notes'] ?? '');
        $res = createStockCheckRequest($conn, $matId, $reqType, $neededQty, $notes, $currentUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 15. Render bản in PDF HTML chuẩn A4 (5 dấu ký mộc)
    case 'render_issue_pdf_html':
        $issueId = intval($_GET['issue_id'] ?? ($_POST['issue_id'] ?? 0));
        header('Content-Type: text/html; charset=utf-8');
        echo renderIssuePdfHtml($conn, $issueId);
        exit;

    // 16. Xuất file Excel (CSV UTF-8 BOM) cho 1 phiếu xuất kho
    case 'export_issue_excel':
        $issueId = intval($_GET['issue_id'] ?? ($_POST['issue_id'] ?? 0));
        exportIssueToExcel($conn, $issueId);
        exit;

    // 17. Xuất file Excel báo cáo tổng hợp phiếu xuất kho theo tháng
    case 'export_issues_monthly_excel':
        $month     = intval($_GET['month'] ?? date('m'));
        $year      = intval($_GET['year'] ?? date('Y'));
        $groupName = trim($_GET['group_name'] ?? '');
        $status    = trim($_GET['status'] ?? '');
        exportIssuesMonthlyToExcel($conn, $month, $year, $groupName, $status);
        exit;

    // 18. Xuất danh mục vật tư & tồn kho hiện tại ra Excel
    case 'export_materials_excel':
        exportMaterialsToExcel($conn);
        exit;

    // 19. Tải file mẫu Import tồn kho & danh mục vật tư
    case 'download_material_import_template':
        downloadMaterialImportTemplate();
        exit;

    // 20. Lưu cài đặt chi tiết vật tư & cấu hình đa nhóm (UPSERT)
    case 'save_material_settings':
        if ($currentUser['role'] !== 'admin' && !hasPermission(['warehouse.manage', 'warehouse.materials', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ Quản lý hoặc Admin mới có quyền cập nhật cấu hình vật tư.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = saveWarehouseMaterial($conn, $_POST);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 21. Lấy danh sách theo dõi đặt hàng ROP
    case 'get_reorder_tracking':
        $filters = [
            'order_status' => trim($_GET['order_status'] ?? 'ALL'),
            'request_type' => trim($_GET['request_type'] ?? 'ALL'),
            'search'       => trim($_GET['search'] ?? '')
        ];
        $list = getReorderTrackingList($conn, $filters);
        echo json_encode(['success' => true, 'data' => $list, 'total' => count($list)], JSON_UNESCAPED_UNICODE);
        break;

    // 22. Cập nhật tiến độ đơn hàng ROP
    case 'update_reorder_order_status':
        if ($currentUser['role'] !== 'admin' && !hasPermission(['warehouse.manage', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật tiến độ đơn hàng.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $alertId     = intval($_POST['alert_id'] ?? 0);
        $orderStatus = trim($_POST['order_status'] ?? '');
        $res = updateReorderOrderStatus($conn, $alertId, $orderStatus, $_POST, $currentUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 23. Quét & đồng bộ tự động cảnh báo ROP
    case 'sync_rop_alerts':
        $count = checkAndGenerateRopAlerts($conn);
        echo json_encode([
            'success' => true,
            'message' => "Đã quét và đồng bộ thành công ({$count} cảnh báo ROP mới).",
            'count'   => $count
        ], JSON_UNESCAPED_UNICODE);
        break;

    // 24. Import dữ liệu tồn kho hàng loạt từ file Excel/CSV
    case 'import_materials_stock':
        if ($currentUser['role'] !== 'admin' && !hasPermission(['warehouse.manage', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền import dữ liệu tồn kho.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $fileTmp = $_FILES['file']['tmp_name'] ?? ($_FILES['stock_file']['tmp_name'] ?? '');
        if (empty($fileTmp) || !file_exists($fileTmp)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel hoặc CSV để tải lên.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = importMaterialsStock($conn, $fileTmp, $currentUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    // 25. Xóa phiếu yêu cầu xuất kho
    case 'delete_issue':
        $issueId = intval($_POST['issue_id'] ?? ($_GET['issue_id'] ?? 0));
        $res = deleteWarehouseIssue($conn, $issueId, $currentUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
        break;
}
