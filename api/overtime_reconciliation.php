<?php
/**
 * API Đối Soát Tăng Ca (Reconciliation Engine)
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

try {
    switch ($action) {
        // =====================================================================
        // 1. CHẠY ENGINE ĐỐI SOÁT TỰ ĐỘNG
        // =====================================================================
        case 'run_reconcile':
            $filterMonth = !empty($_POST['month']) ? intval($_POST['month']) : null;
            $filterYear = !empty($_POST['year']) ? intval($_POST['year']) : null;

            // Xây dựng điều kiện lọc theo tháng/năm nếu có
            $wherePlan = "WHERE 1=1";
            $whereActual = "WHERE 1=1";
            if ($filterYear) {
                $wherePlan .= " AND YEAR(ot_date) = {$filterYear}";
                $whereActual .= " AND YEAR(ot_date) = {$filterYear}";
            }
            if ($filterMonth) {
                $wherePlan .= " AND MONTH(ot_date) = {$filterMonth}";
                $whereActual .= " AND MONTH(ot_date) = {$filterMonth}";
            }

            // Lấy toàn bộ Plans
            $resPlans = $conn->query("SELECT * FROM ot_plans {$wherePlan} ORDER BY ot_date ASC, employee_code ASC");
            $plansByEmpDate = [];
            while ($p = $resPlans->fetch_assoc()) {
                $key = $p['employee_code'] . '_' . $p['ot_date'];
                $plansByEmpDate[$key][] = $p;
            }

            // Lấy toàn bộ Actuals
            $resActuals = $conn->query("SELECT * FROM ot_actuals {$whereActual} ORDER BY ot_date ASC, employee_code ASC");
            $actualsByEmpDate = [];
            while ($a = $resActuals->fetch_assoc()) {
                $key = $a['employee_code'] . '_' . $a['ot_date'];
                $actualsByEmpDate[$key][] = $a;
            }

            // Tập hợp tất cả các cặp (employee_code, ot_date)
            $allKeys = array_unique(array_merge(array_keys($plansByEmpDate), array_keys($actualsByEmpDate)));

            $reconciledCount = 0;
            $matchedCount = 0;
            $planOnlyCount = 0;
            $actualOnlyCount = 0;
            $diffCount = 0;
            $overdueCount = 0;

            $conn->begin_transaction();

            foreach ($allKeys as $key) {
                $plans = $plansByEmpDate[$key] ?? [];
                $actuals = $actualsByEmpDate[$key] ?? [];

                list($empCode, $otDate) = explode('_', $key);

                // TH1: Có Kế hoạch nhưng KHÔNG CÓ Thực tế
                if (!empty($plans) && empty($actuals)) {
                    foreach ($plans as $p) {
                        $reconcileStatus = 'plan_only';
                        $planMinutes = intval($p['total_minutes']);
                        $actualMinutes = 0;
                        $diffMinutes = -$planMinutes;
                        $needsExplanation = 1;
                        $violationType = 'Có kế hoạch nhưng không có thực tế';

                        saveReconciliationRecord(
                            $conn, $empCode, $otDate, $p['id'], null, $reconcileStatus,
                            $planMinutes, $actualMinutes, $diffMinutes, 0, 0, $needsExplanation, $violationType
                        );
                        $planOnlyCount++;
                        $reconciledCount++;
                    }
                }
                // TH2: Có Thực tế nhưng KHÔNG CÓ Kế hoạch
                else if (empty($plans) && !empty($actuals)) {
                    foreach ($actuals as $a) {
                        $reconcileStatus = 'actual_only';
                        $planMinutes = 0;
                        $actualMinutes = intval($a['total_minutes_actual']);
                        $diffMinutes = $actualMinutes;
                        $needsExplanation = 1;
                        $violationType = 'Có thực tế nhưng không có kế hoạch';

                        saveReconciliationRecord(
                            $conn, $empCode, $otDate, null, $a['id'], $reconcileStatus,
                            $planMinutes, $actualMinutes, $diffMinutes, 0, 0, $needsExplanation, $violationType
                        );
                        $actualOnlyCount++;
                        $reconciledCount++;
                    }
                }
                // TH3: CÓ CẢ HAI
                else {
                    // Ghép cặp từng plan với actual gần nhau nhất
                    $usedActualIds = [];

                    foreach ($plans as $p) {
                        $bestActual = null;
                        $minDiffTime = PHP_INT_MAX;

                        foreach ($actuals as $a) {
                            if (in_array($a['id'], $usedActualIds)) continue;
                            $diffT = abs(strtotime($p['start_time']) - strtotime($a['start_time_actual']));
                            if ($diffT < $minDiffTime) {
                                $minDiffTime = $diffT;
                                $bestActual = $a;
                            }
                        }

                        if ($bestActual) {
                            $usedActualIds[] = $bestActual['id'];
                            $planMin = intval($p['total_minutes']);
                            $actMin = intval($bestActual['total_minutes_actual']);
                            $diffMin = $actMin - $planMin;

                            // Kiểm tra thời gian bắt đầu và kết thúc
                            $isTimeMatch = ($p['start_time'] === $bestActual['start_time_actual']) && ($p['end_time'] === $bestActual['end_time_actual']) && ($diffMin === 0);

                            // Kiểm tra hạn duyệt 3 ngày (tính từ ngày tăng ca đến ngày duyệt nếu có)
                            $approvalDaysDiff = 0;
                            $isOverdue = 0;
                            if (!empty($bestActual['approval_date'])) {
                                $otTimestamp = strtotime($otDate);
                                $appTimestamp = strtotime($bestActual['approval_date']);
                                $approvalDaysDiff = floor(($appTimestamp - $otTimestamp) / 86400);
                                if ($approvalDaysDiff > 3) {
                                    $isOverdue = 1;
                                }
                            }

                            if ($isTimeMatch && !$isOverdue) {
                                $reconcileStatus = 'matched';
                                $needsExplanation = 0;
                                $violationType = '';
                                $matchedCount++;
                            } else if (!$isTimeMatch) {
                                $reconcileStatus = 'time_diff';
                                $needsExplanation = 1;
                                $violationType = $diffMin > 0 ? "Thực tế lớn hơn kế hoạch ({$diffMin} phút)" : "Thực tế nhỏ hơn kế hoạch (" . abs($diffMin) . " phút)";
                                $diffCount++;
                            } else {
                                $reconcileStatus = 'overdue';
                                $needsExplanation = 1;
                                $violationType = "Phê duyệt quá thời hạn 03 ngày ({$approvalDaysDiff} ngày)";
                                $overdueCount++;
                            }

                            saveReconciliationRecord(
                                $conn, $empCode, $otDate, $p['id'], $bestActual['id'], $reconcileStatus,
                                $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation, $violationType
                            );
                            $reconciledCount++;
                        } else {
                            // Không tìm thấy actual ghép cặp
                            $planMin = intval($p['total_minutes']);
                            saveReconciliationRecord(
                                $conn, $empCode, $otDate, $p['id'], null, 'plan_only',
                                $planMin, 0, -$planMin, 0, 0, 1, 'Có kế hoạch nhưng không có thực tế'
                            );
                            $planOnlyCount++;
                            $reconciledCount++;
                        }
                    }

                    // Các actual còn sót lại chưa ghép cặp với plan nào
                    foreach ($actuals as $a) {
                        if (!in_array($a['id'], $usedActualIds)) {
                            $actMin = intval($a['total_minutes_actual']);
                            saveReconciliationRecord(
                                $conn, $empCode, $otDate, null, $a['id'], 'actual_only',
                                0, $actMin, $actMin, 0, 0, 1, 'Có thực tế nhưng không có kế hoạch'
                            );
                            $actualOnlyCount++;
                            $reconciledCount++;
                        }
                    }
                }
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => "Đã đối soát xong {$reconciledCount} ca tăng ca!",
                'summary' => [
                    'total' => $reconciledCount,
                    'matched' => $matchedCount,
                    'plan_only' => $planOnlyCount,
                    'actual_only' => $actualOnlyCount,
                    'time_diff' => $diffCount,
                    'overdue' => $overdueCount,
                    'needs_explanation' => ($planOnlyCount + $actualOnlyCount + $diffCount + $overdueCount)
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LẤY DANH SÁCH ĐỐI SOÁT & BỘ LỌC
        // =====================================================================
        case 'get_reconciliations':
            $status = $_GET['status'] ?? '';
            $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;
            $year = !empty($_GET['year']) ? intval($_GET['year']) : 0;
            $search = trim($_GET['search'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(100, intval($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;

            $where = "WHERE 1=1";
            if (!empty($status)) {
                if ($status === 'needs_explanation') {
                    $where .= " AND r.needs_explanation = 1";
                } else {
                    $where .= " AND r.reconcile_status = '" . $conn->real_escape_string($status) . "'";
                }
            }
            if ($month > 0) {
                $where .= " AND MONTH(r.ot_date) = {$month}";
            }
            if ($year > 0) {
                $where .= " AND YEAR(r.ot_date) = {$year}";
            }
            if (!empty($search)) {
                $s = $conn->real_escape_string($search);
                $where .= " AND (r.employee_code LIKE '%{$s}%' OR COALESCE(p.full_name, a.full_name, e.full_name) LIKE '%{$s}%')";
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
            $totalRows = $resCount ? $resCount->fetch_row()[0] : 0;

            // Đếm theo từng trạng thái để làm thống kê badge
            $sqlBadges = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN reconcile_status = 'matched' THEN 1 ELSE 0 END) as matched,
                    SUM(CASE WHEN reconcile_status = 'plan_only' THEN 1 ELSE 0 END) as plan_only,
                    SUM(CASE WHEN reconcile_status = 'actual_only' THEN 1 ELSE 0 END) as actual_only,
                    SUM(CASE WHEN reconcile_status = 'time_diff' THEN 1 ELSE 0 END) as time_diff,
                    SUM(CASE WHEN reconcile_status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN needs_explanation = 1 THEN 1 ELSE 0 END) as needs_explanation
                FROM ot_reconciliations r
                " . ($year > 0 ? "WHERE YEAR(r.ot_date) = {$year}" : "") . "
            ";
            $resBadges = $conn->query($sqlBadges);
            $badgeStats = $resBadges ? $resBadges->fetch_assoc() : [];

            // Lấy danh sách phân trang
            $sqlData = "
                SELECT 
                    r.*,
                    COALESCE(p.full_name, a.full_name, e.full_name) AS full_name,
                    COALESCE(p.group_name, a.group_name, e.cost_center) AS group_name,
                    COALESCE(p.team_name, a.team_name) AS team_name,
                    p.start_time AS plan_start_time,
                    p.end_time AS plan_end_time,
                    p.reason AS plan_reason,
                    p.approval_status AS plan_approval_status,
                    a.start_time_actual AS actual_start_time,
                    a.end_time_actual AS actual_end_time,
                    a.reason AS actual_reason,
                    a.approval_status AS actual_approval_status,
                    exp.id AS explanation_id,
                    exp.approval_status AS exp_approval_status,
                    exp.explanation_content,
                    exp.violation_type
                FROM ot_reconciliations r
                LEFT JOIN ot_plans p ON r.plan_id = p.id
                LEFT JOIN ot_actuals a ON r.actual_id = a.id
                LEFT JOIN employees e ON r.employee_code = e.employee_code
                LEFT JOIN ot_explanations exp ON r.id = exp.reconciliation_id
                {$where}
                ORDER BY r.ot_date DESC, r.id DESC
                LIMIT {$offset}, {$limit}
            ";
            $resData = $conn->query($sqlData);
            $items = [];
            while ($r = $resData->fetch_assoc()) {
                $items[] = $r;
            }

            echo json_encode([
                'success' => true,
                'total' => $totalRows,
                'page' => $page,
                'limit' => $limit,
                'badges' => $badgeStats,
                'data' => $items
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. CHI TIẾT ĐỐI SOÁT
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

            echo json_encode(['success' => true, 'detail' => $detail], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

/**
 * Hàm lưu hoặc cập nhật bản ghi đối soát và tạo ticket giải trình
 */
function saveReconciliationRecord(
    $conn, $empCode, $otDate, $planId, $actualId, $status,
    $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation, $violationType
) {
    $stmt = $conn->prepare("
        INSERT INTO ot_reconciliations (
            employee_code, ot_date, plan_id, actual_id, reconcile_status,
            plan_minutes, actual_minutes, diff_minutes, approval_days_diff,
            is_overdue, needs_explanation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            reconcile_status = VALUES(reconcile_status),
            plan_minutes = VALUES(plan_minutes),
            actual_minutes = VALUES(actual_minutes),
            diff_minutes = VALUES(diff_minutes),
            approval_days_diff = VALUES(approval_days_diff),
            is_overdue = VALUES(is_overdue),
            needs_explanation = VALUES(needs_explanation),
            last_reconciled_at = CURRENT_TIMESTAMP
    ");

    $stmt->bind_param(
        "ssiisiiiiii",
        $empCode, $otDate, $planId, $actualId, $status,
        $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation
    );
    $stmt->execute();
    $recId = $stmt->insert_id;
    if ($recId === 0) {
        // Nếu là update, lấy ID hiện có
        $stmtGet = $conn->prepare("SELECT id FROM ot_reconciliations WHERE employee_code = ? AND ot_date = ? AND ((plan_id IS NULL AND ? IS NULL) OR plan_id = ?) AND ((actual_id IS NULL AND ? IS NULL) OR actual_id = ?)");
        $stmtGet->bind_param("ssiiii", $empCode, $otDate, $planId, $planId, $actualId, $actualId);
        $stmtGet->execute();
        $row = $stmtGet->get_result()->fetch_assoc();
        $recId = $row ? intval($row['id']) : 0;
        $stmtGet->close();
    }
    $stmt->close();

    // Nếu cần giải trình và chưa có trong ot_explanations -> Tự động sinh ticket
    if ($needsExplanation && $recId > 0) {
        $stmtExpCheck = $conn->prepare("SELECT id FROM ot_explanations WHERE reconciliation_id = ?");
        $stmtExpCheck->bind_param("i", $recId);
        $stmtExpCheck->execute();
        $expExists = $stmtExpCheck->get_result()->fetch_assoc();
        $stmtExpCheck->close();

        if (!$expExists) {
            $stmtExp = $conn->prepare("INSERT INTO ot_explanations (reconciliation_id, employee_code, ot_date, violation_type, approval_status) VALUES (?, ?, ?, ?, 'pending')");
            $stmtExp->bind_param("isss", $recId, $empCode, $otDate, $violationType);
            $stmtExp->execute();
            $stmtExp->close();
        }
    }
}
?>

