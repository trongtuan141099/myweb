<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.view', 'five_s.audit', 'five_s.settings', 'api.five_s.dashboard']);

$action = trim($_POST['action'] ?? $_GET['action'] ?? 'list');

// Enforce permission for mutations (Create / Edit / Pause / Delete)
if ($action !== 'list') {
    if (!hasPermission(['five_s.settings', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'code' => 403, 'message' => 'Bạn không có quyền quản lý kế hoạch kiểm tra 5S!']);
        exit;
    }
}

try {
    if ($action === 'list') {
        $plans = [];
        $res = $conn->query("SELECT * FROM five_s_plans ORDER BY id DESC");
        
        // Fetch all zones and users map
        $zonesMap = [];
        $rz = $conn->query("SELECT id, zone_code, zone_name FROM five_s_zones");
        while ($z = $rz->fetch_assoc()) $zonesMap[$z['id']] = $z;

        $usersMap = [];
        $ru = $conn->query("SELECT id, username, fullname FROM users");
        while ($u = $ru->fetch_assoc()) $usersMap[$u['id']] = $u;

        while ($row = $res->fetch_assoc()) {
            $zIds = json_decode($row['zone_ids_json'] ?? '[]', true) ?: [];
            $inspIds = json_decode($row['inspectors_json'] ?? '[]', true) ?: [];
            $assIds = json_decode($row['assignees_json'] ?? '[]', true) ?: [];
            $appIds = json_decode($row['approvers_json'] ?? '[]', true) ?: [];

            $row['zones_list'] = array_values(array_filter(array_map(fn($id) => $zonesMap[$id] ?? null, $zIds)));
            $row['inspectors_list'] = array_values(array_filter(array_map(fn($id) => $usersMap[$id] ?? null, $inspIds)));
            $row['assignees_list'] = array_values(array_filter(array_map(fn($id) => $usersMap[$id] ?? null, $assIds)));
            $row['approvers_list'] = array_values(array_filter(array_map(fn($id) => $usersMap[$id] ?? null, $appIds)));

            $plans[] = $row;
        }

        echo json_encode(['success' => true, 'plans' => $plans]);
        exit;
    }

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $plan_name = trim($_POST['plan_name'] ?? '');
        $frequency = trim($_POST['frequency'] ?? 'daily');
        $zone_ids = $_POST['zone_ids'] ?? [];
        if (!is_array($zone_ids)) $zone_ids = !empty($zone_ids) ? explode(',', $zone_ids) : [];
        $zone_ids = array_map('intval', array_filter($zone_ids));

        $inspectors = $_POST['inspectors'] ?? [];
        if (!is_array($inspectors)) $inspectors = !empty($inspectors) ? explode(',', $inspectors) : [];
        $inspectors = array_map('intval', array_filter($inspectors));

        $assignees = $_POST['assignees'] ?? [];
        if (!is_array($assignees)) $assignees = !empty($assignees) ? explode(',', $assignees) : [];
        $assignees = array_map('intval', array_filter($assignees));

        $approvers = $_POST['approvers'] ?? [];
        if (!is_array($approvers)) $approvers = !empty($approvers) ? explode(',', $approvers) : [];
        $approvers = array_map('intval', array_filter($approvers));

        $status = trim($_POST['status'] ?? 'active');
        $start_date = trim($_POST['start_date'] ?? date('Y-m-d'));
        $end_date = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
        $weekly_days = $_POST['weekly_days'] ?? [];
        if (!is_array($weekly_days)) $weekly_days = !empty($weekly_days) ? explode(',', $weekly_days) : [1, 2, 3, 4, 5, 6];
        $notes = trim($_POST['notes'] ?? '');
        $userId = $_SESSION['user_id'] ?? 1;

        if (empty($plan_name)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên kế hoạch']);
            exit;
        }
        if (empty($zone_ids)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ít nhất một khu vực áp dụng']);
            exit;
        }
        if (empty($inspectors)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng phân công ít nhất một người kiểm tra']);
            exit;
        }

        $zoneIdsJson = json_encode(array_values($zone_ids));
        $inspectorsJson = json_encode(array_values($inspectors));
        $assigneesJson = json_encode(array_values($assignees));
        $approversJson = json_encode(array_values($approvers));
        $weeklyDaysJson = json_encode(array_values($weekly_days));

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE five_s_plans SET 
                                        plan_name = ?, frequency = ?, zone_ids_json = ?, 
                                        inspectors_json = ?, assignees_json = ?, approvers_json = ?,
                                        status = ?, start_date = ?, end_date = ?, weekly_days_json = ?, notes = ?
                                    WHERE id = ?");
            $stmt->bind_param("sssssssssssi", $plan_name, $frequency, $zoneIdsJson, $inspectorsJson, $assigneesJson, $approversJson, $status, $start_date, $end_date, $weeklyDaysJson, $notes, $id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO five_s_plans (
                                        plan_name, frequency, zone_ids_json, 
                                        inspectors_json, assignees_json, approvers_json, 
                                        status, start_date, end_date, weekly_days_json, notes, created_by
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssi", $plan_name, $frequency, $zoneIdsJson, $inspectorsJson, $assigneesJson, $approversJson, $status, $start_date, $end_date, $weeklyDaysJson, $notes, $userId);
            $stmt->execute();
            $id = $conn->insert_id;
        }

        // Generate schedule items in five_s_schedules for active plan
        if ($status === 'active') {
            $startDateObj = new DateTime($start_date);
            $endDateObj = $end_date ? new DateTime($end_date) : (clone $startDateObj)->modify('+30 days');
            
            $stmtSched = $conn->prepare("INSERT INTO five_s_schedules (zone_id, inspector_id, schedule_date, plan_type, status)
                                         VALUES (?, ?, ?, ?, 'pending')
                                         ON DUPLICATE KEY UPDATE inspector_id = VALUES(inspector_id), plan_type = VALUES(plan_type)");

            for ($d = clone $startDateObj; $d <= $endDateObj; $d->modify('+1 day')) {
                $curDateStr = $d->format('Y-m-d');
                $dow = (int)$d->format('N'); // 1 = Mon, 7 = Sun

                $shouldSchedule = false;
                if ($frequency === 'daily') {
                    $shouldSchedule = true;
                } elseif ($frequency === 'weekly') {
                    $shouldSchedule = in_array($dow, $weekly_days, true);
                } elseif ($frequency === 'monthly') {
                    $shouldSchedule = ($d->format('j') === '1' || $d->format('j') === '15');
                } elseif ($frequency === 'ad_hoc') {
                    $shouldSchedule = ($curDateStr === $start_date);
                }

                if ($shouldSchedule) {
                    foreach ($zone_ids as $zId) {
                        foreach ($inspectors as $inspId) {
                            $stmtSched->bind_param("iiss", $zId, $inspId, $curDateStr, $frequency);
                            $stmtSched->execute();
                        }
                    }
                }
            }

            // Send notification to inspectors
            $notifTitle = "Kế hoạch kiểm tra 5S: " . $plan_name;
            $notifMsg = "Bạn đã được phân công trong kế hoạch kiểm tra 5S [$plan_name]. Vui lòng quét QR tại hiện trường.";
            $notifLink = "index.php?mainpage=five_s&tab=audit";
            $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
            foreach ($inspectors as $inspId) {
                $stmtNoti->bind_param("isss", $inspId, $notifTitle, $notifMsg, $notifLink);
                $stmtNoti->execute();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Lưu kế hoạch kiểm tra 5S thành công', 'id' => $id]);
        exit;
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kế hoạch không hợp lệ']);
            exit;
        }

        $res = $conn->query("SELECT status FROM five_s_plans WHERE id = $id")->fetch_assoc();
        $newStatus = ($res['status'] === 'active') ? 'paused' : 'active';

        $stmt = $conn->prepare("UPDATE five_s_plans SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => $newStatus === 'active' ? 'Đã kích hoạt lại kế hoạch' : 'Đã tạm dừng kế hoạch',
            'status' => $newStatus
        ]);
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kế hoạch không hợp lệ']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM five_s_plans WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Đã xóa kế hoạch kiểm tra 5S']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

