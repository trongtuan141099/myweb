<?php
/**
 * API Sơ Đồ Tổ Chức Nhân Sự Kiểm Kê (Inventory Org Chart API)
 * DX Plastic Group
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$writeActions = ['save_node', 'delete_node', 'save_campaign', 'reorder_nodes'];
if (in_array($action, $writeActions, true)) {
    requireApiPermission(['hrm.manage', 'api.hrm.inventory_org_chart']);
} else {
    requireApiPermission(['hrm.view', 'hrm.manage', 'api.hrm.inventory_org_chart']);
}

try {
    switch ($action) {
        // ======================================================================
        // 1. LẤY CÂY SƠ ĐỒ VÀ DỮ LIỆU TỔNG QUAN
        // ======================================================================
        case 'get_tree':
            $campaign_id = intval($_GET['campaign_id'] ?? 1);
            if ($campaign_id <= 0) $campaign_id = 1;

            // Lấy thông tin đợt kiểm kê
            $stmtCamp = $conn->prepare("SELECT * FROM inventory_campaigns WHERE id = ?");
            $stmtCamp->bind_param("i", $campaign_id);
            $stmtCamp->execute();
            $campaign = $stmtCamp->get_result()->fetch_assoc();
            $stmtCamp->close();

            if (!$campaign) {
                // Nếu không thấy, lấy campaign đầu tiên
                $resFirstCamp = $conn->query("SELECT * FROM inventory_campaigns ORDER BY id ASC LIMIT 1");
                $campaign = $resFirstCamp ? $resFirstCamp->fetch_assoc() : null;
                if ($campaign) {
                    $campaign_id = intval($campaign['id']);
                }
            }

            // Lấy danh sách cấp bậc cấu hình
            $levelsMap = [];
            $resLevels = $conn->query("SELECT * FROM inventory_org_levels ORDER BY sort_order ASC, level_number ASC");
            if ($resLevels) {
                while ($lvl = $resLevels->fetch_assoc()) {
                    $levelsMap[intval($lvl['level_number'])] = $lvl;
                }
            }

            // Lấy danh sách tất cả các node thuộc campaign, kết hợp với employees
            $sqlNodes = "
                SELECT 
                    n.*,
                    e.full_name AS emp_full_name,
                    e.gender AS emp_gender,
                    e.cost_center AS emp_cost_center,
                    e.job_level AS emp_job_level,
                    e.hire_date AS emp_hire_date
                FROM inventory_org_nodes n
                LEFT JOIN employees e ON n.employee_code = e.employee_code
                WHERE n.campaign_id = ?
                ORDER BY n.level_number ASC, n.sort_order ASC, n.id ASC
            ";
            $stmtNodes = $conn->prepare($sqlNodes);
            $stmtNodes->bind_param("i", $campaign_id);
            $stmtNodes->execute();
            $resNodes = $stmtNodes->get_result();

            $nodes = [];
            $areasMap = [];
            $nodesByParent = [];

            while ($row = $resNodes->fetch_assoc()) {
                // Ưu tiên thông tin thực tế từ bảng employees nếu có mã NV
                if (!empty($row['employee_code']) && !empty($row['emp_full_name'])) {
                    $row['full_name'] = $row['emp_full_name'];
                    if (empty($row['department'])) {
                        $row['department'] = $row['emp_cost_center'];
                    }
                    if (empty($row['job_level'])) {
                        $row['job_level'] = $row['emp_job_level'];
                    }
                }

                $lvlNum = intval($row['level_number']);
                $lvlInfo = $levelsMap[$lvlNum] ?? [
                    'level_name' => "Cấp " . $lvlNum,
                    'badge_color' => 'primary'
                ];
                $row['level_name'] = $lvlInfo['level_name'];
                $row['badge_color'] = $lvlInfo['badge_color'] ?? 'primary';

                $areasList = [];
                if (!empty($row['area_assigned'])) {
                    $rawAreas = explode(',', $row['area_assigned']);
                    foreach ($rawAreas as $ra) {
                        $cleanArea = trim($ra);
                        if ($cleanArea !== '') {
                            $areasList[] = $cleanArea;
                            $areasMap[$cleanArea] = true;
                        }
                    }
                }
                $row['areas_list'] = $areasList;

                $parentId = ($row['parent_id'] !== null && $row['parent_id'] !== '') ? intval($row['parent_id']) : null;
                $row['parent_id'] = $parentId;
                $row['children'] = [];

                $nodes[$row['id']] = $row;
                $nodesByParent[$parentId ?? 0][] = $row['id'];
            }
            $stmtNodes->close();

            // Tính số lượng cấp dưới (subordinate count) cho từng node
            foreach ($nodes as $id => &$node) {
                $node['children_count'] = isset($nodesByParent[$id]) ? count($nodesByParent[$id]) : 0;
            }
            unset($node);

            // Hàm đệ quy dựng cây đa tầng
            function buildNestedTree($parentId, &$nodes, &$nodesByParent) {
                $branch = [];
                $pidKey = $parentId ?? 0;
                if (!empty($nodesByParent[$pidKey])) {
                    foreach ($nodesByParent[$pidKey] as $childId) {
                        if (isset($nodes[$childId])) {
                            $child = $nodes[$childId];
                            $child['children'] = buildNestedTree($childId, $nodes, $nodesByParent);
                            $branch[] = $child;
                        }
                    }
                }
                return $branch;
            }

            $tree = buildNestedTree(null, $nodes, $nodesByParent);

            // Tính toán số liệu thống kê KPI
            $totalMembers = count($nodes);
            $totalLevels = count($levelsMap);
            $totalAreas = count($areasMap);
            $rootCount = count($tree);

            echo json_encode([
                'success' => true,
                'campaign' => $campaign,
                'kpi' => [
                    'total_members' => $totalMembers,
                    'total_levels' => $totalLevels,
                    'total_areas' => $totalAreas,
                    'root_nodes' => $rootCount
                ],
                'levels' => array_values($levelsMap),
                'flat_nodes' => array_values($nodes),
                'tree' => $tree
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ======================================================================
        // 2. TÌM KIẾM NHÂN VIÊN TỪ BẢNG EMPLOYEES
        // ======================================================================
        case 'search_employees':
            $keyword = trim($_GET['q'] ?? '');
            if ($keyword === '') {
                $sql = "SELECT employee_code, full_name, gender, cost_center, job_level FROM employees ORDER BY full_name ASC LIMIT 25";
                $stmt = $conn->prepare($sql);
            } else {
                $term = "%{$keyword}%";
                $sql = "SELECT employee_code, full_name, gender, cost_center, job_level FROM employees 
                        WHERE employee_code LIKE ? OR full_name LIKE ? OR cost_center LIKE ? 
                        ORDER BY full_name ASC LIMIT 25";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $term, $term, $term);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $employees = [];
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
            $stmt->close();

            echo json_encode([
                'success' => true,
                'employees' => $employees
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ======================================================================
        // 3. THÊM MỚI HOẶC CẬP NHẬT VỊ TRÍ KIỂM KÊ (SAVE NODE)
        // ======================================================================
        case 'save_node':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $campaign_id = intval($_POST['campaign_id'] ?? 1);
            $parent_id = (!empty($_POST['parent_id']) && intval($_POST['parent_id']) > 0) ? intval($_POST['parent_id']) : null;
            $level_number = intval($_POST['level_number'] ?? 1);
            $position_title = trim($_POST['position_title'] ?? '');
            $employee_code = trim($_POST['employee_code'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $job_level = trim($_POST['job_level'] ?? '');
            $rawArea = $_POST['area_assigned'] ?? '';
            if (is_array($rawArea)) {
                $cleanList = array_filter(array_map('trim', $rawArea));
                $area_assigned = implode(', ', $cleanList);
            } else {
                $parts = explode(',', strval($rawArea));
                $cleanList = array_filter(array_map('trim', $parts));
                $area_assigned = implode(', ', $cleanList);
            }
            $duties = trim($_POST['duties'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $sort_order = intval($_POST['sort_order'] ?? 0);
            $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';

            if ($position_title === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập chức danh / vai trò kiểm kê']);
                exit;
            }

            // Nếu có mã nhân viên, đồng bộ lại thông tin từ bảng employees
            if ($employee_code !== '') {
                $stmtEmp = $conn->prepare("SELECT full_name, cost_center, job_level FROM employees WHERE employee_code = ?");
                $stmtEmp->bind_param("s", $employee_code);
                $stmtEmp->execute();
                $empData = $stmtEmp->get_result()->fetch_assoc();
                $stmtEmp->close();

                if ($empData) {
                    $full_name = $empData['full_name'];
                    if (empty($department)) $department = $empData['cost_center'];
                    if (empty($job_level)) $job_level = $empData['job_level'];
                }
            }

            if ($id > 0) {
                // Kiểm tra chống vòng lặp phân cấp (cyclic dependency)
                if ($parent_id === $id) {
                    echo json_encode(['success' => false, 'message' => 'Vị trí cấp trên không thể là chính vị trí này!']);
                    exit;
                }

                if ($parent_id !== null) {
                    // Kiểm tra parent_id không được nằm trong danh sách con cháu của id này
                    $descendants = [];
                    $queue = [$id];
                    while (!empty($queue)) {
                        $curr = array_shift($queue);
                        $stmtCheck = $conn->prepare("SELECT id FROM inventory_org_nodes WHERE parent_id = ? AND campaign_id = ?");
                        $stmtCheck->bind_param("ii", $curr, $campaign_id);
                        $stmtCheck->execute();
                        $resCheck = $stmtCheck->get_result();
                        while ($r = $resCheck->fetch_assoc()) {
                            $descendants[] = intval($r['id']);
                            $queue[] = intval($r['id']);
                        }
                        $stmtCheck->close();
                    }

                    if (in_array($parent_id, $descendants)) {
                        echo json_encode(['success' => false, 'message' => 'Không thể chọn cấp trên là vị trí cấp dưới trực thuộc của vị trí này!']);
                        exit;
                    }
                }

                // Cập nhật node
                $sql = "UPDATE inventory_org_nodes SET 
                            campaign_id = ?, 
                            parent_id = ?, 
                            level_number = ?, 
                            position_title = ?, 
                            employee_code = ?, 
                            full_name = ?, 
                            department = ?, 
                            job_level = ?, 
                            area_assigned = ?, 
                            duties = ?, 
                            phone = ?, 
                            sort_order = ?, 
                            status = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "iiisssssssiisi",
                    $campaign_id,
                    $parent_id,
                    $level_number,
                    $position_title,
                    $employee_code,
                    $full_name,
                    $department,
                    $job_level,
                    $area_assigned,
                    $duties,
                    $phone,
                    $sort_order,
                    $status,
                    $id
                );
                $stmt->execute();
                $stmt->close();

                echo json_encode(['success' => true, 'message' => 'Đã cập nhật vị trí kiểm kê thành công!', 'node_id' => $id]);
            } else {
                // Thêm mới node
                $sql = "INSERT INTO inventory_org_nodes (
                            campaign_id, parent_id, level_number, position_title, employee_code, 
                            full_name, department, job_level, area_assigned, duties, phone, sort_order, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "iiisssssssiis",
                    $campaign_id,
                    $parent_id,
                    $level_number,
                    $position_title,
                    $employee_code,
                    $full_name,
                    $department,
                    $job_level,
                    $area_assigned,
                    $duties,
                    $phone,
                    $sort_order,
                    $status
                );
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                echo json_encode(['success' => true, 'message' => 'Đã thêm vị trí kiểm kê mới thành công!', 'node_id' => $newId]);
            }
            break;

        // ======================================================================
        // 4. XÓA VỊ TRÍ KIỂM KÊ (DELETE NODE)
        // ======================================================================
        case 'delete_node':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $mode = trim($_POST['mode'] ?? 'reparent'); // 'reparent' hoặc 'cascade'

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID vị trí không hợp lệ']);
                exit;
            }

            // Lấy thông tin node hiện tại
            $stmtCur = $conn->prepare("SELECT parent_id, campaign_id FROM inventory_org_nodes WHERE id = ?");
            $stmtCur->bind_param("i", $id);
            $stmtCur->execute();
            $curNode = $stmtCur->get_result()->fetch_assoc();
            $stmtCur->close();

            if (!$curNode) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy vị trí cần xóa']);
                exit;
            }

            $currentParentId = $curNode['parent_id'];
            $campaign_id = $curNode['campaign_id'];

            if ($mode === 'cascade') {
                // Xóa toàn bộ con cháu đệ quy
                $idsToDelete = [$id];
                $queue = [$id];
                while (!empty($queue)) {
                    $curr = array_shift($queue);
                    $stmtCh = $conn->prepare("SELECT id FROM inventory_org_nodes WHERE parent_id = ?");
                    $stmtCh->bind_param("i", $curr);
                    $stmtCh->execute();
                    $resCh = $stmtCh->get_result();
                    while ($r = $resCh->fetch_assoc()) {
                        $chId = intval($r['id']);
                        $idsToDelete[] = $chId;
                        $queue[] = $chId;
                    }
                    $stmtCh->close();
                }

                $inList = implode(',', $idsToDelete);
                $conn->query("DELETE FROM inventory_org_nodes WHERE id IN ({$inList})");
                echo json_encode(['success' => true, 'message' => 'Đã xóa vị trí và toàn bộ ' . count($idsToDelete) . ' vị trí trực thuộc!']);
            } else {
                // Chuyển các con trực tiếp sang cho parent_id của nút đang xóa
                if ($currentParentId === null) {
                    $stmtUp = $conn->prepare("UPDATE inventory_org_nodes SET parent_id = NULL WHERE parent_id = ?");
                    $stmtUp->bind_param("i", $id);
                    $stmtUp->execute();
                    $stmtUp->close();
                } else {
                    $stmtUp = $conn->prepare("UPDATE inventory_org_nodes SET parent_id = ? WHERE parent_id = ?");
                    $stmtUp->bind_param("ii", $currentParentId, $id);
                    $stmtUp->execute();
                    $stmtUp->close();
                }

                // Xóa chính node đó
                $stmtDel = $conn->prepare("DELETE FROM inventory_org_nodes WHERE id = ?");
                $stmtDel->bind_param("i", $id);
                $stmtDel->execute();
                $stmtDel->close();

                echo json_encode(['success' => true, 'message' => 'Đã xóa vị trí thành công, các cấp dưới đã được chuyển lên cấp trên!']);
            }
            break;

        // ======================================================================
        // 5. QUẢN LÝ CẤP BẬC (LEVELS CRUD)
        // ======================================================================
        case 'get_levels':
            $res = $conn->query("SELECT * FROM inventory_org_levels ORDER BY sort_order ASC, level_number ASC");
            $levels = [];
            while ($row = $res->fetch_assoc()) {
                // Đếm số lượng node đang dùng cấp bậc này
                $lvlNum = intval($row['level_number']);
                $stmtCount = $conn->prepare("SELECT COUNT(*) FROM inventory_org_nodes WHERE level_number = ?");
                $stmtCount->bind_param("i", $lvlNum);
                $stmtCount->execute();
                $row['node_count'] = $stmtCount->get_result()->fetch_row()[0] ?? 0;
                $stmtCount->close();

                $levels[] = $row;
            }
            echo json_encode(['success' => true, 'levels' => $levels], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_level':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $level_number = intval($_POST['level_number'] ?? 0);
            $level_name = trim($_POST['level_name'] ?? '');
            $badge_color = trim($_POST['badge_color'] ?? 'primary');
            $description = trim($_POST['description'] ?? '');
            $sort_order = intval($_POST['sort_order'] ?? $level_number);

            if ($level_number <= 0 || $level_name === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập số cấp hợp lệ và tên cấp bậc']);
                exit;
            }

            // Kiểm tra trùng level_number
            $stmtCheck = $conn->prepare("SELECT id FROM inventory_org_levels WHERE level_number = ? AND id != ?");
            $stmtCheck->bind_param("ii", $level_number, $id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $stmtCheck->close();
                echo json_encode(['success' => false, 'message' => "Số cấp bậc {$level_number} đã tồn tại trong hệ thống!"]);
                exit;
            }
            $stmtCheck->close();

            if ($id > 0) {
                $sql = "UPDATE inventory_org_levels SET level_number=?, level_name=?, badge_color=?, description=?, sort_order=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssii", $level_number, $level_name, $badge_color, $description, $sort_order, $id);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật cấp bậc thành công!']);
            } else {
                $sql = "INSERT INTO inventory_org_levels (level_number, level_name, badge_color, description, sort_order) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", $level_number, $level_name, $badge_color, $description, $sort_order);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Đã thêm cấp bậc mới thành công!']);
            }
            break;

        case 'delete_level':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID cấp bậc không hợp lệ']);
                exit;
            }

            $stmtLvl = $conn->prepare("SELECT level_number FROM inventory_org_levels WHERE id = ?");
            $stmtLvl->bind_param("i", $id);
            $stmtLvl->execute();
            $lvlRow = $stmtLvl->get_result()->fetch_assoc();
            $stmtLvl->close();

            if (!$lvlRow) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy cấp bậc']);
                exit;
            }

            $lvlNum = intval($lvlRow['level_number']);
            // Kiểm tra xem có node nào đang dùng không
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM inventory_org_nodes WHERE level_number = ?");
            $stmtCheck->bind_param("i", $lvlNum);
            $stmtCheck->execute();
            $inUse = $stmtCheck->get_result()->fetch_row()[0] ?? 0;
            $stmtCheck->close();

            if ($inUse > 0) {
                echo json_encode(['success' => false, 'message' => "Không thể xóa cấp bậc này vì đang có {$inUse} vị trí trong sơ đồ đang sử dụng!"]);
                exit;
            }

            $stmtDel = $conn->prepare("DELETE FROM inventory_org_levels WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();

            echo json_encode(['success' => true, 'message' => 'Đã xóa cấp bậc thành công!']);
            break;

        // ======================================================================
        // 6. QUẢN LÝ ĐỢT KIỂM KÊ (CAMPAIGNS)
        // ======================================================================
        case 'get_campaigns':
            $res = $conn->query("
                SELECT c.*, COUNT(n.id) as node_count 
                FROM inventory_campaigns c
                LEFT JOIN inventory_org_nodes n ON c.id = n.campaign_id
                GROUP BY c.id
                ORDER BY c.id DESC
            ");
            $campaigns = [];
            while ($row = $res->fetch_assoc()) {
                $campaigns[] = $row;
            }
            echo json_encode(['success' => true, 'campaigns' => $campaigns], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_campaign':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $id = intval($_POST['id'] ?? 0);
            $campaign_name = trim($_POST['campaign_name'] ?? '');
            $audit_date = !empty($_POST['audit_date']) ? $_POST['audit_date'] : null;
            $status = in_array($_POST['status'] ?? 'active', ['planning', 'active', 'completed']) ? $_POST['status'] : 'active';
            $notes = trim($_POST['notes'] ?? '');
            $copy_from_id = intval($_POST['copy_from_id'] ?? 0);

            if ($campaign_name === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên đợt kiểm kê']);
                exit;
            }

            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE inventory_campaigns SET campaign_name = ?, audit_date = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $campaign_name, $audit_date, $status, $notes, $id);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật đợt kiểm kê thành công!', 'campaign_id' => $id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO inventory_campaigns (campaign_name, audit_date, status, notes) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $campaign_name, $audit_date, $status, $notes);
                $stmt->execute();
                $newCampaignId = $stmt->insert_id;
                $stmt->close();

                // Nếu người dùng chọn sao chép sơ đồ từ đợt kiểm kê khác
                if ($copy_from_id > 0) {
                    $resSrc = $conn->query("SELECT * FROM inventory_org_nodes WHERE campaign_id = {$copy_from_id} ORDER BY id ASC");
                    if ($resSrc && $resSrc->num_rows > 0) {
                        $idMap = []; // old_id => new_id
                        $srcNodes = [];
                        while ($r = $resSrc->fetch_assoc()) {
                            $srcNodes[] = $r;
                        }

                        // Lượt 1: Tạo các node
                        foreach ($srcNodes as $src) {
                            $stmtCopy = $conn->prepare("
                                INSERT INTO inventory_org_nodes (
                                    campaign_id, parent_id, level_number, position_title, employee_code, 
                                    full_name, department, job_level, area_assigned, duties, phone, sort_order, status
                                ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmtCopy->bind_param(
                                "iisssssssiis",
                                $newCampaignId,
                                $src['level_number'],
                                $src['position_title'],
                                $src['employee_code'],
                                $src['full_name'],
                                $src['department'],
                                $src['job_level'],
                                $src['area_assigned'],
                                $src['duties'],
                                $src['phone'],
                                $src['sort_order'],
                                $src['status']
                            );
                            $stmtCopy->execute();
                            $idMap[$src['id']] = $stmtCopy->insert_id;
                            $stmtCopy->close();
                        }

                        // Lượt 2: Cập nhật parent_id mới
                        foreach ($srcNodes as $src) {
                            if (!empty($src['parent_id']) && isset($idMap[$src['parent_id']])) {
                                $newParentId = $idMap[$src['parent_id']];
                                $newNodeId = $idMap[$src['id']];
                                $conn->query("UPDATE inventory_org_nodes SET parent_id = {$newParentId} WHERE id = {$newNodeId}");
                            }
                        }
                    }
                }

                echo json_encode([
                    'success' => true, 
                    'message' => 'Đã tạo đợt kiểm kê mới thành công' . ($copy_from_id > 0 ? ' kèm bản sao sơ đồ!' : '!'), 
                    'campaign_id' => $newCampaignId
                ]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>

