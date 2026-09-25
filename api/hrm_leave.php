<?php
/**
 * API Quản Lý Phép Nghỉ (Kế hoạch, Trưởng nhóm phê duyệt & Ma trận Timeline)
 * DX Plastic Group - Factory Management System
 */
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/leave_service.php';

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

$curUser = $_SESSION['username'] ?? 'USER';

// Kiểm tra quyền truy cập cơ bản
if (!hasPermission(['hrm.leave_view', 'hrm.view', 'hrm.manage', 'api.hrm.leave', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập dữ liệu phép nghỉ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_timeline');

switch ($action) {
    case 'get_timeline':
        $month  = intval($_GET['month'] ?? date('m'));
        $year   = intval($_GET['year'] ?? date('Y'));
        $group  = trim($_GET['group_name'] ?? '');
        $shift  = trim($_GET['shift'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $mode   = trim($_GET['mode'] ?? 'standard');

        $data = getLeaveTimelineMatrix($conn, $month, $year, $group, $search, $mode, $shift);
        echo json_encode([
            'success' => true,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_dashboard':
        $month = intval($_GET['month'] ?? date('m'));
        $year  = intval($_GET['year'] ?? date('Y'));
        $data = getLeaveDashboardData($conn, $month, $year);
        echo json_encode([
            'success' => true,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_hrm_summary':
        $year   = intval($_GET['year'] ?? date('Y'));
        $month  = intval($_GET['month'] ?? 0);
        $group  = trim($_GET['group_name'] ?? '');
        $shift  = trim($_GET['shift'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, intval($_GET['page'] ?? 1));
        $limit  = max(10, min(200, intval($_GET['limit'] ?? 50)));

        $res = getHrmLeavesSummaryData($conn, $year, $month, $group, $shift, $search, $page, $limit);
        echo json_encode(array_merge(['success' => true], $res), JSON_UNESCAPED_UNICODE);
        break;

    case 'get_monthly_matrix':
        $year   = intval($_GET['year'] ?? date('Y'));
        $group  = trim($_GET['group_name'] ?? '');
        $shift  = trim($_GET['shift'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $res = getMonthlyLeaveMatrixByEmployee($conn, $year, $group, $shift, $search);
        echo json_encode(array_merge(['success' => true], $res), JSON_UNESCAPED_UNICODE);
        break;

    case 'get_work_groups':
        $groups = getEstablishedWorkGroups($conn);
        echo json_encode(['success' => true, 'work_groups' => $groups], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_plans':
        $month  = intval($_GET['month'] ?? date('m'));
        $year   = intval($_GET['year'] ?? date('Y'));
        $status = trim($_GET['status'] ?? 'all');
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, intval($_GET['page'] ?? 1));
        $limit  = max(10, min(200, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $where = "WHERE (p.leave_date BETWEEN '{$startDate}' AND '{$endDate}')";
        if (!empty($status) && $status !== 'all') {
            $sStatus = $conn->real_escape_string($status);
            $where .= " AND p.status = '{$sStatus}'";
        }
        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            $where .= " AND (p.employee_code LIKE '%{$s}%' OR p.full_name LIKE '%{$s}%' OR p.reason LIKE '%{$s}%')";
        }

        $countSql = "SELECT COUNT(*) as cnt FROM leave_plans p {$where}";
        $cRes = $conn->query($countSql);
        $totalRows = $cRes ? intval($cRes->fetch_assoc()['cnt']) : 0;
        $totalPages = ceil($totalRows / $limit);

        $sql = "
            SELECT p.* 
            FROM leave_plans p 
            {$where}
            ORDER BY p.leave_date DESC, p.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ";
        $res = $conn->query($sql);
        $list = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'data'    => $list,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_rows'   => $totalRows,
                'limit'        => $limit
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'create_plan':
        $data = [
            'employee_code' => trim($_POST['employee_code'] ?? ''),
            'full_name'     => trim($_POST['full_name'] ?? ''),
            'group_name'    => trim($_POST['group_name'] ?? 'Plastic Extrusion'),
            'work_group'    => trim($_POST['work_group'] ?? ''),
            'team_name'     => trim($_POST['team_name'] ?? ''),
            'start_date'    => trim($_POST['start_date'] ?? ''),
            'end_date'      => trim($_POST['end_date'] ?? ''),
            'session_type'  => trim($_POST['session_type'] ?? 'all_day'),
            'leave_type'    => trim($_POST['leave_type'] ?? 'Phép năm'),
            'reason'        => trim($_POST['reason'] ?? '')
        ];

        $res = createLeavePlanRecord($conn, $data, $curUser);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    case 'approve_plan':
        $planId = intval($_POST['plan_id'] ?? 0);
        $planIds = $_POST['plan_ids'] ?? [];
        $note = trim($_POST['note'] ?? '');

        if ($planId > 0) {
            $res = approveLeavePlanRecord($conn, $planId, $curUser, $note);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
        } else if (!empty($planIds) && is_array($planIds)) {
            $approvedCount = 0;
            foreach ($planIds as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $r = approveLeavePlanRecord($conn, $id, $curUser, $note);
                    if ($r['success']) $approvedCount++;
                }
            }
            echo json_encode([
                'success' => true,
                'message' => "Đã phê duyệt thành công {$approvedCount} đơn phép kế hoạch.",
                'approved_count' => $approvedCount
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy ID đơn phép cần duyệt.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'reject_plan':
        $planId = intval($_POST['plan_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($planId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID đơn phép không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp lý do từ chối.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = rejectLeavePlanRecord($conn, $planId, $curUser, $reason);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    case 'delete_plan':
        $planId = intval($_POST['plan_id'] ?? 0);
        if ($planId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID đơn phép không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = deleteLeavePlanRecord($conn, $planId);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    case 'get_employees':
        $search = trim($_GET['search'] ?? '');
        $limit  = max(10, min(100, intval($_GET['limit'] ?? 30)));
        $where = "WHERE (e.resignation_date IS NULL OR e.resignation_date = '0000-00-00')";
        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            $where .= " AND (e.employee_code LIKE '%{$s}%' OR e.full_name LIKE '%{$s}%' OR e.cost_center LIKE '%{$s}%')";
        }

        $sql = "
            SELECT 
                e.employee_code,
                e.full_name,
                e.cost_center,
                COALESCE(NULLIF(e.work_group, ''), l.work_group, p.work_group, '') AS saved_work_group,
                COALESCE(l.team_name, a.team_name, p.team_name, '') AS team_name
            FROM employees e
            LEFT JOIN (SELECT employee_code, work_group, team_name FROM leave_actuals WHERE team_name IS NOT NULL AND team_name != '' GROUP BY employee_code) l ON e.employee_code = l.employee_code
            LEFT JOIN (SELECT employee_code, team_name FROM ot_actuals WHERE team_name IS NOT NULL AND team_name != '' GROUP BY employee_code) a ON e.employee_code = a.employee_code
            LEFT JOIN (SELECT employee_code, work_group, team_name FROM leave_plans WHERE team_name IS NOT NULL AND team_name != '' GROUP BY employee_code) p ON e.employee_code = p.employee_code
            {$where}
            ORDER BY e.employee_code ASC
            LIMIT {$limit}
        ";
        $res = $conn->query($sql);
        $emps = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $wg = !empty($r['saved_work_group']) 
                    ? $r['saved_work_group'] 
                    : resolveWorkGroup($r['team_name'], $r['cost_center'], $r['employee_code']);
                $emps[] = [
                    'employee_code' => $r['employee_code'],
                    'full_name'     => $r['full_name'],
                    'cost_center'   => $r['cost_center'],
                    'work_group'    => $wg,
                    'team_name'     => $r['team_name'] ?: $wg
                ];
            }
        }
        echo json_encode(['success' => true, 'employees' => $emps], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_group_allocations':
        $groupFilter = trim($_GET['group_name'] ?? '');
        $shiftFilter = trim($_GET['shift'] ?? '');
        $search      = trim($_GET['search'] ?? '');

        $definedGroups = getEstablishedWorkGroups($conn);
        $groups = [];
        foreach ($definedGroups as $dg) {
            $groups[$dg['group_name']] = [
                'group_code' => $dg['group_code'],
                'group_name' => $dg['group_name'],
                'color_code' => $dg['color_code'],
                'sort_order' => intval($dg['sort_order']),
                'employees'  => []
            ];
        }

        $where = "WHERE (e.resignation_date IS NULL OR e.resignation_date = '0000-00-00')";
        if (!empty($groupFilter)) {
            $sGrp = $conn->real_escape_string($groupFilter);
            $where .= " AND (COALESCE(NULLIF(e.work_group, ''), 'Khác') = '{$sGrp}')";
        }
        if (!empty($shiftFilter)) {
            $sShf = $conn->real_escape_string($shiftFilter);
            $where .= " AND (COALESCE(NULLIF(e.work_shift, ''), 'Ca 1') = '{$sShf}')";
        }
        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            $where .= " AND (e.employee_code LIKE '%{$s}%' OR e.full_name LIKE '%{$s}%' OR e.cost_center LIKE '%{$s}%')";
        }

        $sql = "
            SELECT 
                e.employee_code,
                e.full_name,
                e.cost_center,
                COALESCE(NULLIF(e.work_shift, ''), 'Ca 1') AS work_shift,
                COALESCE(NULLIF(e.work_group, ''), 'Khác') AS work_group
            FROM employees e
            {$where}
            ORDER BY e.work_group ASC, e.employee_code ASC
        ";
        $res = $conn->query($sql);
        $totalEmps = 0;
        $tableEmployees = [];

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $wg  = $r['work_group'];
                $shf = $r['work_shift'];

                $empItem = [
                    'employee_code' => $r['employee_code'],
                    'full_name'     => $r['full_name'],
                    'cost_center'   => $r['cost_center'],
                    'work_group'    => $wg,
                    'work_shift'    => $shf
                ];

                if (!isset($groups[$wg])) {
                    $groups[$wg] = [
                        'group_code' => 'other',
                        'group_name' => $wg,
                        'color_code' => '#64748b',
                        'sort_order' => 99,
                        'employees'  => []
                    ];
                }
                $groups[$wg]['employees'][] = $empItem;
                $tableEmployees[] = $empItem;
                $totalEmps++;
            }
        }

        // Đếm số lượng nhân viên từng nhóm
        foreach ($groups as &$g) {
            $g['count'] = count($g['employees']);
        }

        echo json_encode([
            'success'         => true,
            'groups'          => array_values($groups),
            'table_employees' => $tableEmployees,
            'all_shifts'      => ['Ca 1', 'Ca 2', 'Ca 3', 'Hành chính'],
            'total_employees' => $totalEmps
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'update_employee_work_group':
        if (!hasPermission(['hrm.leave_group_manage', 'hrm.leave_manage', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thay đổi phân bổ nhóm/ca!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $empCode  = trim($_POST['employee_code'] ?? '');
        $empCodes = $_POST['employee_codes'] ?? [];
        $newGroup = trim($_POST['work_group'] ?? '');
        $newShift = trim($_POST['work_shift'] ?? '');

        if (empty($newGroup) && empty($newShift)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhóm làm việc hoặc ca làm việc mới.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $targets = [];
        if (!empty($empCode)) {
            $targets[] = $empCode;
        }
        if (!empty($empCodes) && is_array($empCodes)) {
            $targets = array_unique(array_merge($targets, $empCodes));
        }

        if (empty($targets)) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn nhân viên nào để phân bổ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updatedCount = 0;
        foreach ($targets as $code) {
            $code = trim($code);
            if (empty($code)) continue;

            if (!empty($newGroup) && !empty($newShift)) {
                $stmtEmp = $conn->prepare("UPDATE employees SET work_group = ?, work_shift = ? WHERE employee_code = ?");
                $stmtEmp->bind_param("sss", $newGroup, $newShift, $code);
                $stmtEmp->execute();
                $stmtEmp->close();

                $stmtPlan = $conn->prepare("UPDATE leave_plans SET work_group = ?, work_shift = ? WHERE employee_code = ?");
                $stmtPlan->bind_param("sss", $newGroup, $newShift, $code);
                $stmtPlan->execute();
                $stmtPlan->close();

                $stmtAct = $conn->prepare("UPDATE leave_actuals SET work_group = ?, work_shift = ? WHERE employee_code = ?");
                $stmtAct->bind_param("sss", $newGroup, $newShift, $code);
                $stmtAct->execute();
                $stmtAct->close();
            } else if (!empty($newGroup)) {
                $stmtEmp = $conn->prepare("UPDATE employees SET work_group = ? WHERE employee_code = ?");
                $stmtEmp->bind_param("ss", $newGroup, $code);
                $stmtEmp->execute();
                $stmtEmp->close();

                $stmtPlan = $conn->prepare("UPDATE leave_plans SET work_group = ? WHERE employee_code = ?");
                $stmtPlan->bind_param("ss", $newGroup, $code);
                $stmtPlan->execute();
                $stmtPlan->close();

                $stmtAct = $conn->prepare("UPDATE leave_actuals SET work_group = ? WHERE employee_code = ?");
                $stmtAct->bind_param("ss", $newGroup, $code);
                $stmtAct->execute();
                $stmtAct->close();
            } else if (!empty($newShift)) {
                $stmtEmp = $conn->prepare("UPDATE employees SET work_shift = ? WHERE employee_code = ?");
                $stmtEmp->bind_param("ss", $newShift, $code);
                $stmtEmp->execute();
                $stmtEmp->close();

                $stmtPlan = $conn->prepare("UPDATE leave_plans SET work_shift = ? WHERE employee_code = ?");
                $stmtPlan->bind_param("ss", $newShift, $code);
                $stmtPlan->execute();
                $stmtPlan->close();

                $stmtAct = $conn->prepare("UPDATE leave_actuals SET work_shift = ? WHERE employee_code = ?");
                $stmtAct->bind_param("ss", $newShift, $code);
                $stmtAct->execute();
                $stmtAct->close();
            }

            $updatedCount++;
        }

        $msgParts = [];
        if (!empty($newGroup)) $msgParts[] = "Nhóm [{$newGroup}]";
        if (!empty($newShift)) $msgParts[] = "Ca [{$newShift}]";

        echo json_encode([
            'success'    => true,
            'message'    => "Đã cập nhật phân bổ " . implode(' & ', $msgParts) . " cho {$updatedCount} nhân sự thành công!",
            'work_group' => $newGroup,
            'work_shift' => $newShift,
            'count'      => $updatedCount
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'update_single_employee_allocation':
        if (!hasPermission(['hrm.leave_group_manage', 'hrm.leave_manage', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa phân bổ nhóm và ca!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $empCode  = trim($_POST['employee_code'] ?? '');
        $newGroup = trim($_POST['work_group'] ?? '');
        $newShift = trim($_POST['work_shift'] ?? '');

        if (empty($empCode)) {
            echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (empty($newGroup) || empty($newShift)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ nhóm làm việc và ca làm việc.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtEmp = $conn->prepare("UPDATE employees SET work_group = ?, work_shift = ? WHERE employee_code = ?");
        $stmtEmp->bind_param("sss", $newGroup, $newShift, $empCode);
        $ok = $stmtEmp->execute();
        $stmtEmp->close();

        $stmtPlan = $conn->prepare("UPDATE leave_plans SET work_group = ?, work_shift = ? WHERE employee_code = ?");
        $stmtPlan->bind_param("sss", $newGroup, $newShift, $empCode);
        $stmtPlan->execute();
        $stmtPlan->close();

        $stmtAct = $conn->prepare("UPDATE leave_actuals SET work_group = ?, work_shift = ? WHERE employee_code = ?");
        $stmtAct->bind_param("sss", $newGroup, $newShift, $empCode);
        $stmtAct->execute();
        $stmtAct->close();

        if ($ok) {
            echo json_encode([
                'success'       => true,
                'message'       => "Đã cập nhật thông tin nhân viên {$empCode} sang nhóm [{$newGroup}], ca [{$newShift}] thành công!",
                'employee_code' => $empCode,
                'work_group'    => $newGroup,
                'work_shift'    => $newShift
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thông tin nhân viên.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // Đồng bộ HRM & Lịch trình tự động (Chuyển tiếp sang hrm_leave_sync.php)
    case 'sync':
    case 'trigger_sync':
    case 'check_schedule':
    case 'get_config':
    case 'save_config':
    case 'reset_config':
    case 'get_accounts':
    case 'save_account':
    case 'delete_account':
    case 'test_account':
    case 'upload_excel':
        require __DIR__ . '/hrm_leave_sync.php';
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        break;
}
