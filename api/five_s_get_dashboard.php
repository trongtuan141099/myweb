<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.view', 'five_s.audit', 'five_s.settings', 'api.five_s.dashboard']);

$month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');
$zoneFilter = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : 0;
$userFilter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$deptFilter = isset($_GET['department']) ? trim($_GET['department']) : '';
$factoryFilter = isset($_GET['factory']) ? trim($_GET['factory']) : '';
$todayStr = date('Y-m-d');

try {
    // 1. Lấy danh sách khu vực 5S kèm người phụ trách
    $zones = [];
    $departments = [];
    $factories = [];
    $areas = [];

    $sqlZones = "SELECT z.*, u.fullname AS manager_name, u.username AS manager_username 
                 FROM five_s_zones z 
                 LEFT JOIN users u ON z.manager_id = u.id 
                 ORDER BY z.id ASC";
    $resZones = $conn->query($sqlZones);
    while ($row = $resZones->fetch_assoc()) {
        $zones[] = $row;
        if (!empty($row['department']) && !in_array($row['department'], $departments, true)) {
            $departments[] = $row['department'];
        }
        if (!empty($row['factory']) && !in_array($row['factory'], $factories, true)) {
            $factories[] = $row['factory'];
        }
        if (!empty($row['area']) && !in_array($row['area'], $areas, true)) {
            $areas[] = $row['area'];
        }
    }

    // 2. Lấy danh sách nhân viên phục vụ phân công / gán lỗi / lọc
    $users = [];
    $resUsers = $conn->query("SELECT id, fullname, username, role FROM users ORDER BY fullname ASC");
    if ($resUsers) {
        while ($u = $resUsers->fetch_assoc()) {
            $users[] = $u;
        }
    }

    // 3. Lấy danh sách cây tiêu chuẩn 5S
    $standardsTree = [];
    $resStd = $conn->query("SELECT * FROM five_s_standards ORDER BY group_name ASC, sort_order ASC");
    if ($resStd) {
        while ($s = $resStd->fetch_assoc()) {
            $g = $s['group_name'] ?: 'Khác';
            if (!isset($standardsTree[$g])) $standardsTree[$g] = [];
            $standardsTree[$g][] = $s;
        }
    }

    // 4. Lấy toàn bộ lịch kiểm tra và kết quả kiểm tra trong tháng để xây dựng Gantt Matrix & KPIs
    $monthStart = DateTime::createFromFormat('Y-m-d', $month . '-01');
    if (!$monthStart) {
        $monthStart = new DateTime(date('Y-m-01'));
        $month = $monthStart->format('Y-m');
    }
    $daysInMonth = (int)$monthStart->format('t');
    $monthEnd = (clone $monthStart)->modify('last day of this month');

    $startDateStr = $monthStart->format('Y-m-d');
    $endDateStr = $monthEnd->format('Y-m-d');

    // Query schedules in month
    $schedSql = "SELECT s.*, z.zone_name, z.zone_code, z.department, z.factory, z.area,
                        u.fullname AS inspector_name, u.username AS inspector_username
                 FROM five_s_schedules s
                 JOIN five_s_zones z ON s.zone_id = z.id
                 LEFT JOIN users u ON s.inspector_id = u.id
                 WHERE s.schedule_date BETWEEN ? AND ?";
    $schedStmt = $conn->prepare($schedSql);
    $schedStmt->bind_param("ss", $startDateStr, $endDateStr);
    $schedStmt->execute();
    $resSched = $schedStmt->get_result();

    $scheduleMap = [];
    $totalScheduled = 0;
    while ($sRow = $resSched->fetch_assoc()) {
        $zId = (int)$sRow['zone_id'];
        $dStr = $sRow['schedule_date'];
        $scheduleMap[$zId][$dStr] = $sRow;
        $totalScheduled++;
    }

    // Query audits in month
    $auditSql = "SELECT a.*, z.zone_name, z.zone_code,
                        u.fullname AS inspector_name, u.username AS inspector_username
                 FROM five_s_audits a
                 JOIN five_s_zones z ON a.zone_id = z.id
                 LEFT JOIN users u ON a.inspector_id = u.id
                 WHERE a.audit_date BETWEEN ? AND ?
                 ORDER BY a.id ASC";
    $auditStmt = $conn->prepare($auditSql);
    $auditStmt->bind_param("ss", $startDateStr, $endDateStr);
    $auditStmt->execute();
    $resAudit = $auditStmt->get_result();

    $auditMap = [];
    while ($aRow = $resAudit->fetch_assoc()) {
        $zId = (int)$aRow['zone_id'];
        $dStr = $aRow['audit_date'];
        $auditMap[$zId][$dStr] = $aRow;
    }

    // Query issues in month
    $issueAuditIds = [];
    $resIssueAudits = $conn->query("SELECT audit_id FROM five_s_issues WHERE audit_id IS NOT NULL AND audit_id > 0");
    if ($resIssueAudits) {
        while ($ia = $resIssueAudits->fetch_assoc()) {
            $issueAuditIds[(int)$ia['audit_id']] = true;
        }
    }

    // Build Gantt Matrix & KPIs
    $ganttMatrix = [];
    $totalOk = 0;
    $totalNg = 0;
    $totalPending = 0;
    $uncheckedZones = [];

    foreach ($zones as $zone) {
        $zId = (int)$zone['id'];
        if ($zoneFilter > 0 && $zId !== $zoneFilter) continue;
        if (!empty($deptFilter) && ($zone['department'] ?? '') !== $deptFilter) continue;
        if (!empty($factoryFilter) && ($zone['factory'] ?? '') !== $factoryFilter) continue;

        $hasAnyAudit = false;
        $zoneRow = [
            'zone_id' => $zId,
            'zone_code' => $zone['zone_code'],
            'zone_name' => $zone['zone_name'],
            'factory' => $zone['factory'] ?? 'Nhà Máy 1',
            'area' => $zone['area'] ?? 'Khu vực',
            'department' => $zone['department'] ?? 'Phân xưởng',
            'manager_name' => $zone['manager_name'] ?? 'Chưa gán',
            'frequency' => $zone['frequency'] ?? 'daily',
            'color_ok' => $zone['color_ok'] ?? '#10b981',
            'color_ng' => $zone['color_ng'] ?? '#ef4444',
            'color_pending' => $zone['color_pending'] ?? '#94a3b8',
            'is_active' => (int)($zone['is_active'] ?? 1),
            'days' => []
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dStr = sprintf('%s-%02d', $month, $day);
            $hasSchedule = isset($scheduleMap[$zId][$dStr]);
            $hasAudit = isset($auditMap[$zId][$dStr]);

            $cellStatus = 'none';
            $auditInfo = null;
            $inspectorName = '';

            if ($hasAudit) {
                $hasAnyAudit = true;
                $auditData = $auditMap[$zId][$dStr];
                $auditId = (int)$auditData['id'];
                $isNg = ($auditData['audit_status'] === 'NG') || isset($issueAuditIds[$auditId]);
                $cellStatus = $isNg ? 'ng' : 'ok';
                $auditInfo = [
                    'id' => $auditId,
                    'status' => $cellStatus,
                    'inspector_name' => $auditData['inspector_name'] ?? 'NV',
                    'scanned_qr' => $auditData['scanned_qr'] ?? '',
                    'scanned_at' => $auditData['scanned_at'] ?? '',
                    'actual_image' => $auditData['actual_image'] ?? '',
                    'note' => $auditData['note'] ?? ''
                ];
                if ($cellStatus === 'ok') $totalOk++;
                if ($cellStatus === 'ng') $totalNg++;
            } elseif ($hasSchedule) {
                $schedData = $scheduleMap[$zId][$dStr];
                $inspectorName = $schedData['inspector_name'] ?? '';
                if ($schedData['status'] === 'completed') {
                    $cellStatus = 'ok';
                    $totalOk++;
                } else {
                    $cellStatus = 'pending';
                    $totalPending++;
                }
            }

            $zoneRow['days'][$dStr] = [
                'date' => $dStr,
                'day' => $day,
                'status' => $cellStatus,
                'has_schedule' => $hasSchedule,
                'has_audit' => $hasAudit,
                'inspector_name' => $inspectorName,
                'audit_info' => $auditInfo
            ];
        }

        if (!$hasAnyAudit && $zoneRow['is_active']) {
            $uncheckedZones[] = $zone;
        }

        $ganttMatrix[] = $zoneRow;
    }

    $totalAudited = $totalOk + $totalNg;
    $completionRate = ($totalScheduled > 0) ? round(($totalAudited / $totalScheduled) * 100, 1) : 0;

    // 5. Lấy danh sách lỗi vi phạm với 5 trạng thái
    $issues = [];
    $sqlIssues = "SELECT i.*, z.zone_name, z.zone_code, z.layout_image, z.department, z.factory, z.area,
                         u1.fullname AS inspector_name, 
                         u2.fullname AS assignee_name,
                         u3.fullname AS approver_name,
                         a.audit_date, a.scanned_at
                  FROM five_s_issues i
                  JOIN five_s_zones z ON i.zone_id = z.id
                  JOIN five_s_audits a ON i.audit_id = a.id
                  LEFT JOIN users u1 ON a.inspector_id = u1.id
                  LEFT JOIN users u2 ON i.assignee_id = u2.id
                  LEFT JOIN users u3 ON i.approver_id = u3.id
                  WHERE DATE_FORMAT(i.created_at, '%Y-%m') = ?
                  ORDER BY i.id DESC";

    $stmtIssues = $conn->prepare($sqlIssues);
    $stmtIssues->bind_param("s", $month);
    $stmtIssues->execute();
    $resIssues = $stmtIssues->get_result();

    $issuePending = 0;
    $issueInProgress = 0;
    $issuePendingApproval = 0;
    $issueResolved = 0;
    $issueOverdue = 0;
    $catCounts = ['S1' => 0, 'S2' => 0, 'S3' => 0, 'S4' => 0, 'S5' => 0];

    while ($row = $resIssues->fetch_assoc()) {
        // Check overdue condition
        $isOverdue = (!empty($row['due_date']) && $row['due_date'] < $todayStr && $row['status'] !== 'resolved');
        if ($isOverdue && $row['status'] !== 'overdue') {
            $row['status'] = 'overdue';
        }

        $issues[] = $row;

        if ($row['status'] === 'resolved') {
            $issueResolved++;
        } elseif ($row['status'] === 'in_progress') {
            $issueInProgress++;
        } elseif ($row['status'] === 'pending_approval') {
            $issuePendingApproval++;
        } elseif ($row['status'] === 'overdue') {
            $issueOverdue++;
        } else {
            $issuePending++;
        }

        if (isset($catCounts[$row['s_category']])) {
            $catCounts[$row['s_category']]++;
        }
    }

    $totalIssues = count($issues);
    $recoveryRate = $totalIssues > 0 ? round(($issueResolved / $totalIssues) * 100, 1) : 0;

    echo json_encode([
        'success' => true,
        'month' => $month,
        'days_in_month' => $daysInMonth,
        'kpi' => [
            'total_scheduled' => $totalScheduled,
            'total_audited' => $totalAudited,
            'total_ok' => $totalOk,
            'total_ng' => $totalNg,
            'total_pending' => $totalPending,
            'completion_rate' => $completionRate,
            // Issue KPIs with 5 states
            'total_issues' => $totalIssues,
            'issue_pending' => $issuePending,
            'issue_in_progress' => $issueInProgress,
            'issue_pending_approval' => $issuePendingApproval,
            'issue_resolved' => $issueResolved,
            'issue_overdue' => $issueOverdue,
            'recovery_rate' => $recoveryRate,
            'unchecked_zones_count' => count($uncheckedZones)
        ],
        'gantt_matrix' => $ganttMatrix,
        'chart_data' => [
            'series' => array_values($catCounts)
        ],
        'zones' => $zones,
        'factories' => $factories,
        'areas' => $areas,
        'departments' => $departments,
        'unchecked_zones' => $uncheckedZones,
        'standards_tree' => $standardsTree,
        'issues' => $issues,
        'users' => $users
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
