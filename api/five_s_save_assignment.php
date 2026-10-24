<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.settings', 'api.five_s.save_assignment']);

$action = trim($_POST['action'] ?? 'assign_plan');
$zone_id = intval($_POST['zone_id'] ?? 0); // 0 = all zones
$month_year = trim($_POST['month_year'] ?? date('Y-m'));
$plan_type = trim($_POST['plan_type'] ?? 'daily');
$inspector_id = intval($_POST['inspector_id'] ?? 0);
$assignee_id = intval($_POST['assignee_id'] ?? 0);
$specific_date = trim($_POST['specific_date'] ?? '');
$weekly_days = $_POST['weekly_days'] ?? [];
if (!is_array($weekly_days)) {
    $weekly_days = !empty($weekly_days) ? explode(',', $weekly_days) : [1, 2, 3, 4, 5, 6];
}
$auto_assign = !empty($_POST['auto_assign']) && $_POST['auto_assign'] == '1';

// Multiple inspectors, assignees, approvers
$inspectors = $_POST['inspectors'] ?? [];
if (!is_array($inspectors) && !empty($inspectors)) $inspectors = explode(',', $inspectors);
$assignees = $_POST['assignees'] ?? [];
if (!is_array($assignees) && !empty($assignees)) $assignees = explode(',', $assignees);
$approvers = $_POST['approvers'] ?? [];
if (!is_array($approvers) && !empty($approvers)) $approvers = explode(',', $approvers);

if (!in_array($plan_type, ['daily', 'weekly', 'monthly', 'ad_hoc'], true)) {
    $plan_type = 'daily';
}

$monthStart = DateTime::createFromFormat('Y-m-d', $month_year . '-01');
if (!$monthStart || $monthStart->format('Y-m') !== $month_year) {
    echo json_encode(['success' => false, 'message' => 'Tháng phân công không hợp lệ']);
    exit;
}
$monthEnd = (clone $monthStart)->modify('last day of this month');

try {
    // 1. Determine target zones
    $targetZones = [];
    if ($zone_id > 0) {
        $stmtZ = $conn->prepare("SELECT id, zone_name, zone_code, manager_id, inspectors_json, assignees_json, approvers_json FROM five_s_zones WHERE id = ?");
        $stmtZ->bind_param("i", $zone_id);
        $stmtZ->execute();
        if ($z = $stmtZ->get_result()->fetch_assoc()) {
            $targetZones[] = $z;
        }
    } else {
        $resAll = $conn->query("SELECT id, zone_name, zone_code, manager_id, inspectors_json, assignees_json, approvers_json FROM five_s_zones WHERE is_active = 1 ORDER BY id ASC");
        while ($z = $resAll->fetch_assoc()) {
            $targetZones[] = $z;
        }
    }

    if (empty($targetZones)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy khu vực hoạt động để lập kế hoạch']);
        exit;
    }

    $totalScheduled = 0;
    $notifiedInspectors = [];

    $stmtAssign = $conn->prepare("INSERT INTO five_s_assignments (zone_id, month_year, inspector_id, assignee_id) 
                                 VALUES (?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id), assignee_id = VALUES(assignee_id)");

    $stmtSched = $conn->prepare("INSERT INTO five_s_schedules (zone_id, inspector_id, schedule_date, plan_type, status)
                                 VALUES (?, ?, ?, ?, 'pending')
                                 ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id), plan_type = VALUES(plan_type)");

    foreach ($targetZones as $zone) {
        $curZoneId = intval($zone['id']);

        // Determine list of inspectors for this zone
        $zoneInspectors = [];
        if (!empty($inspectors)) {
            $zoneInspectors = array_map('intval', $inspectors);
        } elseif ($auto_assign) {
            if (!empty($zone['inspectors_json'])) {
                $dec = json_decode($zone['inspectors_json'], true);
                if (is_array($dec)) $zoneInspectors = array_map('intval', $dec);
            }
            if (empty($zoneInspectors) && !empty($zone['manager_id'])) {
                $zoneInspectors[] = intval($zone['manager_id']);
            }
        } elseif ($inspector_id > 0) {
            $zoneInspectors[] = $inspector_id;
        }

        if (empty($zoneInspectors)) {
            $zoneInspectors[] = $_SESSION['user_id'] ?? 1;
        }

        // Determine assignee
        $curAssignee = $assignee_id;
        if (!empty($assignees)) {
            $curAssignee = intval($assignees[0]);
        } elseif ($curAssignee <= 0 && !empty($zone['manager_id'])) {
            $curAssignee = intval($zone['manager_id']);
        }
        if ($curAssignee <= 0) $curAssignee = $zoneInspectors[0];

        // Update multi-assignees on five_s_zones if provided
        if (!empty($inspectors) || !empty($assignees) || !empty($approvers)) {
            $updZone = $conn->prepare("UPDATE five_s_zones SET 
                                        inspectors_json = COALESCE(?, inspectors_json),
                                        assignees_json = COALESCE(?, assignees_json),
                                        approvers_json = COALESCE(?, approvers_json)
                                       WHERE id = ?");
            $iJson = !empty($inspectors) ? json_encode(array_map('intval', $inspectors)) : null;
            $aJson = !empty($assignees) ? json_encode(array_map('intval', $assignees)) : null;
            $apJson = !empty($approvers) ? json_encode(array_map('intval', $approvers)) : null;
            $updZone->bind_param("sssi", $iJson, $aJson, $apJson, $curZoneId);
            $updZone->execute();
        }

        // Save legacy assignment record for first inspector
        $primaryInspector = $zoneInspectors[0];
        $stmtAssign->bind_param("isii", $curZoneId, $month_year, $primaryInspector, $curAssignee);
        $stmtAssign->execute();

        // Calculate schedule dates
        $datesToSchedule = [];
        if ($plan_type === 'ad_hoc') {
            $adHocDate = !empty($specific_date) ? $specific_date : date('Y-m-d');
            $datesToSchedule[] = $adHocDate;
        } elseif ($plan_type === 'monthly') {
            $datesToSchedule[] = $monthStart->format('Y-m-01');
            $datesToSchedule[] = $monthStart->format('Y-m-15');
        } elseif ($plan_type === 'weekly') {
            $wDays = array_map('intval', $weekly_days);
            if (empty($wDays)) $wDays = [1, 3, 5];
            for ($d = clone $monthStart; $d <= $monthEnd; $d->modify('+1 day')) {
                $dayOfWeek = (int)$d->format('N');
                if (in_array($dayOfWeek, $wDays, true)) {
                    $datesToSchedule[] = $d->format('Y-m-d');
                }
            }
        } else {
            // Daily
            for ($d = clone $monthStart; $d <= $monthEnd; $d->modify('+1 day')) {
                $datesToSchedule[] = $d->format('Y-m-d');
            }
        }

        // Schedule work items for all inspectors
        foreach ($datesToSchedule as $sDate) {
            foreach ($zoneInspectors as $inspId) {
                $stmtSched->bind_param("iiss", $curZoneId, $inspId, $sDate, $plan_type);
                if ($stmtSched->execute()) {
                    $totalScheduled++;
                }
                $notifiedInspectors[$inspId] = true;
            }
        }
    }

    // Send notifications to inspectors
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $notifTitle = "Kế hoạch kiểm tra 5S ($plan_type)";
    $notifMsg = "Bạn được phân công trong kế hoạch kiểm tra 5S mới. Vui lòng quét QR tại hiện trường khi đến lượt.";
    $notifLink = "index.php?mainpage=five_s&tab=audit";

    foreach (array_keys($notifiedInspectors) as $uId) {
        $notifStmt->bind_param("isss", $uId, $notifTitle, $notifMsg, $notifLink);
        $notifStmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => "Lập kế hoạch thành công! Đã sinh {$totalScheduled} lượt kiểm tra.",
        'total_scheduled' => $totalScheduled
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi lập kế hoạch: ' . $e->getMessage()]);
}
