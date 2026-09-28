<?php
/**
 * Core Warehouse Service - Quản lý Kho & Xuất Vật Tư
 * DX Plastic Group - Factory Management System
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Sinh mã phiếu xuất tự động: XK-YYYYMM-XXXX
 */
function generateWarehouseIssueCode($conn, $year, $month) {
    $prefix = sprintf("XK-%04d%02d-", $year, $month);
    $sql = "SELECT issue_code FROM warehouse_issues WHERE issue_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $res = $conn->query($sql);
    $nextNum = 1;
    if ($res && $row = $res->fetch_assoc()) {
        $lastCode = $row['issue_code'];
        $parts = explode('-', $lastCode);
        if (isset($parts[2])) {
            $nextNum = intval($parts[2]) + 1;
        }
    }
    return sprintf("%s%04d", $prefix, $nextNum);
}

/**
 * Lấy danh sách vật tư kho
 */
function getWarehouseMaterials($conn, $filters = []) {
    $where = "WHERE 1=1";
    if (isset($filters['is_active']) && $filters['is_active'] !== 'ALL') {
        $act = intval($filters['is_active']);
        $where .= " AND m.is_active = {$act}";
    } elseif (empty($filters['include_inactive'])) {
        $where .= " AND m.is_active = 1";
    }
    if (!empty($filters['group_name']) && $filters['group_name'] !== 'ALL') {
        $grp = $conn->real_escape_string($filters['group_name']);
        $where .= " AND (m.group_name = '{$grp}' OR FIND_IN_SET('{$grp}', m.applicable_groups) OR m.applicable_groups LIKE '%{$grp}%')";
    }
    if (!empty($filters['category_type']) && $filters['category_type'] !== 'ALL') {
        $cat = $conn->real_escape_string($filters['category_type']);
        $where .= " AND m.category_type = '{$cat}'";
    }
    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $where .= " AND (m.item_code LIKE '%{$s}%' OR m.item_name_vn LIKE '%{$s}%' OR m.item_name_en LIKE '%{$s}%' OR m.bin_location LIKE '%{$s}%')";
    }
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'below_rop') {
            $where .= " AND m.stock_current <= m.reorder_point";
        } elseif ($filters['status'] === 'in_stock') {
            $where .= " AND m.stock_current > 0";
        } elseif ($filters['status'] === 'out_of_stock') {
            $where .= " AND m.stock_current <= 0";
        }
    }

    $sql = "
        SELECT m.*,
               CASE WHEN m.stock_current <= m.reorder_point THEN 1 ELSE 0 END AS is_below_rop,
               CASE 
                   WHEN m.avg_monthly_consumption > 0 THEN ROUND(m.stock_current / m.avg_monthly_consumption, 1)
                   ELSE 99.0
               END AS runway_months
        FROM warehouse_materials m
        {$where}
        ORDER BY m.group_name ASC, m.item_name_vn ASC
    ";
    $res = $conn->query($sql);
    $materials = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $mid = intval($r['id']);
            $histRes = $conn->query("
                SELECT year, month, issued_qty 
                FROM warehouse_material_history 
                WHERE material_id = {$mid} 
                ORDER BY year DESC, month DESC 
                LIMIT 3
            ");
            $m1 = 0.0; $m2 = 0.0; $m3 = 0.0;
            $hIdx = 0;
            if ($histRes) {
                while ($h = $histRes->fetch_assoc()) {
                    if ($hIdx === 0) $m3 = floatval($h['issued_qty']);     // T7
                    elseif ($hIdx === 1) $m2 = floatval($h['issued_qty']); // T6
                    elseif ($hIdx === 2) $m1 = floatval($h['issued_qty']); // T5
                    $hIdx++;
                }
            }
            $r['history_m1'] = $m1;
            $r['history_m2'] = $m2;
            $r['history_m3'] = $m3;
            $r['avg_3months_consumption'] = floatval($r['avg_monthly_consumption'] ?? 0);
            // Chuẩn hóa alias tương thích dữ liệu giao diện
            $r['material_code'] = $r['item_code'];
            $r['material_name'] = $r['item_name_vn'];
            $r['ma_vt']         = $r['item_code'];
            $r['ten_vt']        = $r['item_name_vn'];
            $materials[] = $r;
        }
    }
    return $materials;
}

/**
 * Lấy chi tiết 1 vật tư kèm lịch sử xuất 3 tháng gần nhất
 */
function getMaterialDetail($conn, $materialId) {
    $mid = intval($materialId);
    $stmt = $conn->prepare("SELECT * FROM warehouse_materials WHERE id = ?");
    $stmt->bind_param("i", $mid);
    $stmt->execute();
    $mat = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$mat) return null;
    $mat['material_code'] = $mat['item_code'];
    $mat['material_name'] = $mat['item_name_vn'];
    $mat['ma_vt']         = $mat['item_code'];
    $mat['ten_vt']        = $mat['item_name_vn'];

    // Lấy 3 tháng gần nhất
    $curYear = intval(date('Y'));
    $curMonth = intval(date('m'));
    $history = [];
    $resHist = $conn->query("
        SELECT year, month, issued_qty 
        FROM warehouse_material_history 
        WHERE material_id = {$mid} 
        ORDER BY year DESC, month DESC 
        LIMIT 6
    ");
    if ($resHist) {
        while ($h = $resHist->fetch_assoc()) {
            $history[] = $h;
        }
    }
    $mat['history'] = $history;
    $mat['is_below_rop'] = ($mat['stock_current'] <= $mat['reorder_point']) ? 1 : 0;
    $mat['runway_months'] = ($mat['avg_monthly_consumption'] > 0) 
        ? round($mat['stock_current'] / $mat['avg_monthly_consumption'], 1) 
        : 99.0;

    return $mat;
}

/**
 * Đề xuất tự động (Auto-suggestion) danh sách vật tư tiêu hao chuẩn theo Nhóm làm việc
 */
function getConsumableSuggestionsByGroup($conn, $groupName, $month = 0, $year = 0) {
    $grp = $conn->real_escape_string($groupName);
    $where = "WHERE m.is_active = 1";
    if (!empty($groupName) && $groupName !== 'ALL') {
        $where .= " AND (m.group_name = '{$grp}' OR FIND_IN_SET('{$grp}', m.applicable_groups) OR m.applicable_groups LIKE '%{$grp}%')";
    }

    $sql = "
        SELECT m.*,
               CASE WHEN m.stock_current <= m.reorder_point THEN 1 ELSE 0 END AS is_below_rop,
               CASE 
                   WHEN m.avg_monthly_consumption > 0 THEN ROUND(m.stock_current / m.avg_monthly_consumption, 1)
                   ELSE 99.0
               END AS runway_months
        FROM warehouse_materials m
        {$where}
        ORDER BY m.group_name ASC, m.id ASC
    ";
    $res = $conn->query($sql);
    $items = [];

    // Map lịch sử gần nhất cho từng vật tư
    while ($m = $res->fetch_assoc()) {
        $mid = intval($m['id']);
        $histSql = "
            SELECT year, month, issued_qty 
            FROM warehouse_material_history 
            WHERE material_id = {$mid} 
            ORDER BY year DESC, month DESC 
            LIMIT 3
        ";
        $rHist = $conn->query($histSql);
        $m1 = 0.0; $m2 = 0.0; $m3 = 0.0;
        $idx = 0;
        if ($rHist) {
            while ($h = $rHist->fetch_assoc()) {
                if ($idx === 0) $m3 = floatval($h['issued_qty']);     // T7
                elseif ($idx === 1) $m2 = floatval($h['issued_qty']); // T6
                elseif ($idx === 2) $m1 = floatval($h['issued_qty']); // T5
                $idx++;
            }
        }
        $m['history_m1'] = $m1;
        $m['history_m2'] = $m2;
        $m['history_m3'] = $m3;

        // Tính toán thông số đề xuất theo công thức
        $machines = floatval($m['default_machines']);
        $uses = floatval($m['default_uses_per_machine']);
        $norm = floatval($m['norm_per_use']);
        $totalUses = round($machines * $uses, 2);
        $theoretical = round($totalUses * $norm, 2);
        $packQty = max(1.0, floatval($m['pack_quantity']));

        // Quy đổi thực xuất theo đóng gói
        if ($theoretical > 0) {
            if ($packQty > 1.0) {
                $actual = ceil($theoretical / $packQty) * $packQty;
            } else {
                $actual = $theoretical;
            }
        } else {
            $actual = 0;
        }

        $m['suggested_machines'] = $machines;
        $m['suggested_uses'] = $uses;
        $m['suggested_total_uses'] = $totalUses;
        $m['suggested_norm'] = $norm;
        $m['suggested_theoretical_qty'] = $theoretical;
        $m['suggested_field_stock'] = 0;
        $m['suggested_reusable_stock'] = 0;
        $m['suggested_actual_qty'] = $actual;
        $m['stock_after_issue'] = max(0, floatval($m['stock_current']) - $actual);

        $items[] = $m;
    }

    return $items;
}

/**
 * Tính toán đơn dòng vật tư theo công thức định mức & quy cách đóng gói
 */
function calculateItemQuantities($material, $machines, $usesPerMachine, $normPerUse, $fieldStock = 0, $reusableStock = 0) {
    $machines = max(0, floatval($machines));
    $usesPerMachine = max(0, floatval($usesPerMachine));
    $normPerUse = max(0, floatval($normPerUse));
    $fieldStock = max(0, floatval($fieldStock));
    $reusableStock = max(0, floatval($reusableStock));

    $totalUses = round($machines * $usesPerMachine, 2);
    $theoretical = round($totalUses * $normPerUse, 2);
    $netTheoretical = max(0, round($theoretical - $fieldStock - $reusableStock, 2));

    $packQty = max(1.0, floatval($material['pack_quantity'] ?? 1));
    if ($netTheoretical > 0) {
        if ($packQty > 1.0) {
            $actual = ceil($netTheoretical / $packQty) * $packQty;
        } else {
            $actual = $netTheoretical;
        }
    } else {
        $actual = 0;
    }

    $stockBefore = floatval($material['stock_current'] ?? 0);
    $stockAfter = round($stockBefore - $actual, 2);
    $rop = floatval($material['reorder_point'] ?? 0);
    $isBelowRop = ($stockAfter <= $rop) ? 1 : 0;

    $avg3m = floatval($material['avg_monthly_consumption'] ?? 0);
    $runway = ($avg3m > 0) ? round($stockAfter / $avg3m, 1) : 99.0;

    return [
        'machines_count'      => $machines,
        'uses_per_machine'    => $usesPerMachine,
        'total_uses'          => $totalUses,
        'norm_per_use'        => $normPerUse,
        'theoretical_qty'     => $theoretical,
        'field_stock'         => $fieldStock,
        'reusable_stock'      => $reusableStock,
        'net_theoretical_qty' => $netTheoretical,
        'actual_qty'          => $actual,
        'stock_before_issue'  => $stockBefore,
        'stock_after_issue'   => $stockAfter,
        'is_below_rop'        => $isBelowRop,
        'runway_months'       => $runway
    ];
}

/**
 * Tạo phiếu yêu cầu xuất vật tư (Bắt buộc kiểm tra logic tiêu hao vs bất thường)
 */
function createIssueRequest($conn, $data, $currentUser) {
    $groupName = trim($data['group_name'] ?? '');
    $issueType = trim($data['issue_type'] ?? 'consumable');
    $month     = intval($data['month'] ?? date('m'));
    $year      = intval($data['year'] ?? date('Y'));
    $purpose   = trim($data['purpose'] ?? '');
    $reasonIrr = trim($data['reason_for_irregular'] ?? '');
    $notes     = trim($data['notes'] ?? '');
    $items     = $data['items'] ?? [];

    if (empty($groupName)) {
        return ['success' => false, 'message' => 'Vui lòng chọn Nhóm làm việc yêu cầu xuất kho.'];
    }
    if (empty($items) || !is_array($items)) {
        return ['success' => false, 'message' => 'Phiếu yêu cầu phải có ít nhất 1 mặt hàng vật tư.'];
    }

    // Kiểm tra tính hợp lệ của vật tư bất thường: BẮT BUỘC có lý do xuất
    $hasIrregularItem = ($issueType === 'irregular');
    foreach ($items as $it) {
        $cat = trim($it['category_type'] ?? ($issueType === 'irregular' ? 'irregular' : 'consumable'));
        if ($cat === 'irregular' || $issueType === 'irregular') {
            $hasIrregularItem = true;
            $irrReason = trim($it['irregular_reason'] ?? ($it['reason'] ?? $reasonIrr));
            if (empty($irrReason)) {
                $codeName = $it['item_code'] ?? ($it['material_code'] ?? 'Mặt hàng');
                return [
                    'success' => false,
                    'message' => "Vật tư bất thường [{$codeName}] bắt buộc phải điền Lý do và Mục đích xuất."
                ];
            }
        }
    }

    $issueCode = generateWarehouseIssueCode($conn, $year, $month);
    $creatorName = $currentUser['fullname'] ?? ($currentUser['username'] ?? 'Nhân viên');
    $creatorId   = $currentUser['id'] ?? null;
    $creatorCode = $currentUser['employee_code'] ?? ($currentUser['username'] ?? '');

    $totItems = count($items);
    $totTheo = 0.0;
    $totAct = 0.0;
    $hasRopWarning = 0;

    // Validate and prepare item rows
    $preparedItems = [];
    foreach ($items as $it) {
        $matId = intval($it['material_id'] ?? 0);
        $mat = getMaterialDetail($conn, $matId);
        if (!$mat) {
            return ['success' => false, 'message' => "Không tìm thấy vật tư có ID {$matId}."];
        }

        $catType = trim($it['category_type'] ?? ($issueType === 'irregular' ? 'irregular' : $mat['category_type']));
        if ($catType === 'consumable') {
            $machines = floatval($it['machines_count'] ?? $mat['default_machines']);
            $uses = floatval($it['uses_per_machine'] ?? $mat['default_uses_per_machine']);
            $norm = floatval($it['norm_per_use'] ?? $mat['norm_per_use']);
            $fieldStock = floatval($it['field_stock'] ?? 0);
            $reusable = floatval($it['reusable_stock'] ?? 0);

            $calc = calculateItemQuantities($mat, $machines, $uses, $norm, $fieldStock, $reusable);
            $actualQty = $calc['actual_qty'];
        } else {
            // Vật tư bất thường
            $actualQty = max(0, floatval($it['actual_qty'] ?? ($it['requested_qty'] ?? 1)));
            $calc = [
                'machines_count'      => 0,
                'uses_per_machine'    => 0,
                'total_uses'          => 0,
                'norm_per_use'        => 0,
                'theoretical_qty'     => $actualQty,
                'field_stock'         => floatval($it['field_stock'] ?? 0),
                'reusable_stock'      => 0,
                'net_theoretical_qty' => $actualQty,
                'actual_qty'          => $actualQty,
                'stock_before_issue'  => floatval($mat['stock_current']),
                'stock_after_issue'   => round(floatval($mat['stock_current']) - $actualQty, 2),
                'is_below_rop'        => ((floatval($mat['stock_current']) - $actualQty) <= floatval($mat['reorder_point'])) ? 1 : 0,
                'runway_months'       => (floatval($mat['avg_monthly_consumption']) > 0) ? round((floatval($mat['stock_current']) - $actualQty) / floatval($mat['avg_monthly_consumption']), 1) : 99.0
            ];
        }

        // Ràng buộc số lượng xuất thực tế không vượt quá số lượng tồn kho khả dụng
        if ($actualQty > floatval($mat['stock_current'])) {
            return [
                'success' => false,
                'message' => "Vật tư [{$mat['item_code']} - {$mat['item_name_vn']}] yêu cầu xuất {$actualQty} {$mat['unit']}, vượt quá số lượng tồn kho khả dụng hiện có ({$mat['stock_current']} {$mat['unit']}). Vui lòng điều chỉnh lại hoặc gửi 'Yêu Cầu Kiểm Tra Tồn Kho' cho Thủ kho."
            ];
        }

        if ($calc['is_below_rop']) {
            $hasRopWarning = 1;
        }

        $totTheo += $calc['theoretical_qty'];
        $totAct  += $calc['actual_qty'];

        $preparedItems[] = [
            'material_id'         => $matId,
            'item_code'           => $mat['item_code'],
            'item_name_vn'        => $mat['item_name_vn'],
            'item_name_en'        => $mat['item_name_en'],
            'group_name'          => $mat['group_name'],
            'unit'                => $mat['unit'],
            'packaging_spec'      => $mat['packaging_spec'],
            'pack_quantity'       => $mat['pack_quantity'],
            'category_type'       => $catType,
            'bin_location'        => $mat['bin_location'],
            'image_url'           => $mat['image_url'],
            'machines_count'      => $calc['machines_count'],
            'uses_per_machine'    => $calc['uses_per_machine'],
            'total_uses'          => $calc['total_uses'],
            'norm_per_use'        => $calc['norm_per_use'],
            'theoretical_qty'     => $calc['theoretical_qty'],
            'field_stock'         => $calc['field_stock'],
            'reusable_stock'      => $calc['reusable_stock'],
            'net_theoretical_qty' => $calc['net_theoretical_qty'],
            'actual_qty'          => $calc['actual_qty'],
            'stock_before_issue'  => $calc['stock_before_issue'],
            'stock_after_issue'   => $calc['stock_after_issue'],
            'reorder_point'       => floatval($mat['reorder_point']),
            'is_below_rop'        => $calc['is_below_rop'],
            'avg_3months_consumption' => floatval($mat['avg_monthly_consumption']),
            'runway_months'       => $calc['runway_months'],
            'history_m1'          => floatval($it['history_m1'] ?? 0),
            'history_m2'          => floatval($it['history_m2'] ?? 0),
            'history_m3'          => floatval($it['history_m3'] ?? 0),
            'irregular_reason'    => trim($it['irregular_reason'] ?? $reasonIrr)
        ];
    }

    $finalIssueType = $hasIrregularItem ? ($issueType === 'consumable' ? 'mixed' : 'irregular') : 'consumable';

    $conn->begin_transaction();
    try {
        $stmtIssue = $conn->prepare("
            INSERT INTO warehouse_issues (
                issue_code, group_name, issue_type, month, year, status,
                creator_id, creator_name, creator_code, purpose, reason_for_irregular,
                total_items, total_theoretical_qty, total_actual_qty, notes, has_rop_warning
            ) VALUES (?, ?, ?, ?, ?, 'pending_checker', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtIssue->bind_param(
            "sssiiissssiddsi",
            $issueCode, $groupName, $finalIssueType, $month, $year,
            $creatorId, $creatorName, $creatorCode, $purpose, $reasonIrr,
            $totItems, $totTheo, $totAct, $notes, $hasRopWarning
        );
        $stmtIssue->execute();
        $issueId = $conn->insert_id;
        $stmtIssue->close();

        // Insert items
        $stmtIt = $conn->prepare("
            INSERT INTO warehouse_issue_items (
                issue_id, material_id, item_code, item_name_vn, item_name_en, group_name,
                unit, packaging_spec, pack_quantity, category_type, bin_location, image_url,
                machines_count, uses_per_machine, total_uses, norm_per_use, theoretical_qty,
                field_stock, reusable_stock, net_theoretical_qty, actual_qty,
                stock_before_issue, stock_after_issue, reorder_point, is_below_rop,
                avg_3months_consumption, runway_months, history_m1, history_m2, history_m3, irregular_reason
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ");

        foreach ($preparedItems as $p) {
            $stmtIt->bind_param(
                "iissssssdsssddddddddddddiddddds",
                $issueId, $p['material_id'], $p['item_code'], $p['item_name_vn'], $p['item_name_en'], $p['group_name'],
                $p['unit'], $p['packaging_spec'], $p['pack_quantity'], $p['category_type'], $p['bin_location'], $p['image_url'],
                $p['machines_count'], $p['uses_per_machine'], $p['total_uses'], $p['norm_per_use'], $p['theoretical_qty'],
                $p['field_stock'], $p['reusable_stock'], $p['net_theoretical_qty'], $p['actual_qty'],
                $p['stock_before_issue'], $p['stock_after_issue'], $p['reorder_point'], $p['is_below_rop'],
                $p['avg_3months_consumption'], $p['runway_months'], $p['history_m1'], $p['history_m2'], $p['history_m3'], $p['irregular_reason']
            );
            $stmtIt->execute();
        }
        $stmtIt->close();

        // Workflow log step 1: create
        $stmtLog = $conn->prepare("
            INSERT INTO warehouse_workflow_logs (issue_id, step, actor_id, actor_name, actor_role, action, comment)
            VALUES (?, 'create', ?, ?, ?, 'Tạo phiếu', ?)
        ");
        $userRole = $currentUser['role'] ?? 'user';
        $logComment = "Khởi tạo phiếu xuất kho {$issueCode} với {$totItems} mặt hàng";
        $stmtLog->bind_param("iisss", $issueId, $creatorId, $creatorName, $userRole, $logComment);
        $stmtLog->execute();
        $stmtLog->close();

        // Gửi thông báo đến người kiểm tra / quản lý
        $notiTitle = "Phiếu xuất kho {$issueCode} chờ duyệt";
        $notiMsg   = "Phiếu xuất kho {$issueCode} (Nhóm {$groupName}) đang chờ kiểm tra duyệt.";
        $notiLink  = "index.php?mainpage=warehouse&subpage=approval&issue_id={$issueId}";

        $grpEsc = $conn->real_escape_string($groupName);
        $checkersRes = $conn->query("
            SELECT DISTINCT u.id 
            FROM users u
            WHERE u.role IN ('admin', 'editor')
               OR LOWER(u.username) IN (
                   SELECT LOWER(username) FROM warehouse_approvers 
                   WHERE is_active = 1 AND role_type = 'checker' AND (group_name = '{$grpEsc}' OR group_name = 'ALL')
               )
        ");
        if ($checkersRes) {
            $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            while ($uRow = $checkersRes->fetch_assoc()) {
                $uId = intval($uRow['id']);
                $stmtNoti->bind_param("isss", $uId, $notiTitle, $notiMsg, $notiLink);
                $stmtNoti->execute();
            }
            $stmtNoti->close();
        }

        $conn->commit();

        return [
            'success'        => true,
            'message'        => "Đã tạo phiếu yêu cầu xuất vật tư {$issueCode} thành công!",
            'issue_id'       => $issueId,
            'issue_code'     => $issueCode,
            'has_rop_warning'=> $hasRopWarning
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Lỗi tạo phiếu xuất kho: ' . $e->getMessage()];
    }
}

/**
 * Xử lý các bước phê duyệt & xuất kho & bàn giao (5 bước)
 */
function processApprovalStep($conn, $issueId, $step, $action, $comment, $currentUser, $extraData = []) {
    $issueId = intval($issueId);
    $res = $conn->query("SELECT * FROM warehouse_issues WHERE id = {$issueId}");
    $issue = $res ? $res->fetch_assoc() : null;
    if (!$issue) {
        return ['success' => false, 'message' => 'Không tìm thấy phiếu yêu cầu xuất kho.'];
    }

    $currentStatus = $issue['status'];
    $actorName = $currentUser['fullname'] ?? ($currentUser['username'] ?? 'User');
    $actorId   = $currentUser['id'] ?? null;
    $actorRole = $currentUser['role'] ?? 'user';

    $newStatus = $currentStatus;
    $actionName = '';

    if ($step === 'checker') {
        if ($currentStatus !== 'pending_checker') {
            return ['success' => false, 'message' => 'Phiếu không ở trạng thái Chờ người kiểm tra duyệt.'];
        }
        if ($action === 'approve') {
            $newStatus = 'pending_manager';
            $actionName = 'Người kiểm tra phê duyệt';
        } else {
            $newStatus = 'rejected';
            $actionName = 'Người kiểm tra từ chối';
        }
    } elseif ($step === 'manager') {
        if ($currentStatus !== 'pending_manager') {
            return ['success' => false, 'message' => 'Phiếu không ở trạng thái Chờ quản lý duyệt.'];
        }
        if ($action === 'approve') {
            $newStatus = 'pending_admin_issue';
            $actionName = 'Quản lý phê duyệt';
        } else {
            $newStatus = 'rejected';
            $actionName = 'Quản lý từ chối';
        }
    } elseif ($step === 'admin_issue') {
        if ($currentStatus !== 'pending_admin_issue') {
            return ['success' => false, 'message' => 'Phiếu không ở trạng thái Chờ Admin làm thủ tục xuất kho.'];
        }
        if ($action === 'issue' || $action === 'approve') {
            $newStatus = 'pending_handover';
            $actionName = 'Admin xuất kho & trừ tồn kho';

            // Trừ tồn kho thực tế trong warehouse_materials
            $itemsRes = $conn->query("SELECT material_id, item_code, item_name_vn, actual_qty, stock_after_issue, reorder_point FROM warehouse_issue_items WHERE issue_id = {$issueId}");
            if ($itemsRes) {
                while ($it = $itemsRes->fetch_assoc()) {
                    $mId = intval($it['material_id']);
                    $actQty = floatval($it['actual_qty']);
                    $conn->query("UPDATE warehouse_materials SET stock_current = GREATEST(0, stock_current - {$actQty}) WHERE id = {$mId}");
                }
            }
            // Tự động quét và kích hoạt cảnh báo ROP cho tất cả vật tư bị giảm dưới điểm đặt hàng
            checkAndGenerateRopAlerts($conn);
        } else {
            $newStatus = 'rejected';
            $actionName = 'Admin kho từ chối xuất';
        }
    } elseif ($step === 'handover') {
        if ($currentStatus !== 'pending_handover') {
            return ['success' => false, 'message' => 'Phiếu không ở trạng thái Chờ bàn giao hiện trường.'];
        }
        if ($action === 'confirm' || $action === 'approve') {
            $newStatus = 'completed';
            $actionName = 'Xác nhận hoàn tất nhận bàn giao';
        } else {
            $newStatus = 'rejected';
            $actionName = 'Từ chối nhận bàn giao';
        }
    } elseif ($action === 'cancel') {
        $newStatus = 'cancelled';
        $actionName = 'Hủy phiếu yêu cầu';
    } else {
        return ['success' => false, 'message' => 'Bước phê duyệt không hợp lệ.'];
    }

    $receiverName = trim($extraData['receiver_name'] ?? '');
    $receiverCode = trim($extraData['receiver_code'] ?? '');

    // Cập nhật trạng thái phiếu
    $stmtUp = $conn->prepare("UPDATE warehouse_issues SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmtUp->bind_param("si", $newStatus, $issueId);
    $stmtUp->execute();
    $stmtUp->close();

    // Ghi log
    $stmtLog = $conn->prepare("
        INSERT INTO warehouse_workflow_logs (issue_id, step, actor_id, actor_name, actor_role, action, comment, handover_receiver_name, handover_receiver_code)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtLog->bind_param("isissssss", $issueId, $step, $actorId, $actorName, $actorRole, $actionName, $comment, $receiverName, $receiverCode);
    $stmtLog->execute();
    $stmtLog->close();

    // Gửi thông báo chuyển tiếp tiến trình
    $issueCode = $issue['issue_code'];
    $grpEsc = $conn->real_escape_string($issue['group_name']);
    $notiTitle = "";
    $notiMsg = "";
    $targetRoleType = "";

    if ($newStatus === 'pending_manager') {
        $notiTitle = "Phiếu xuất kho {$issueCode} chờ quản lý duyệt";
        $notiMsg   = "Phiếu {$issueCode} ({$issue['group_name']}) đã qua bước kiểm tra, đang chờ quản lý phê duyệt.";
        $targetRoleType = 'manager';
    } elseif ($newStatus === 'pending_admin_issue') {
        $notiTitle = "Phiếu xuất kho {$issueCode} chờ làm thủ tục xuất";
        $notiMsg   = "Phiếu {$issueCode} ({$issue['group_name']}) đã được quản lý duyệt, chờ thủ kho xuất hàng.";
        $targetRoleType = 'admin_warehouse';
    } elseif ($newStatus === 'pending_handover') {
        $notiTitle = "Phiếu xuất kho {$issueCode} chờ bàn giao";
        $notiMsg   = "Vật tư trong phiếu {$issueCode} đã được xuất kho, sẵn sàng bàn giao hiện trường.";
        $targetRoleType = 'receiver';
    } elseif ($newStatus === 'completed') {
        $notiTitle = "Phiếu xuất kho {$issueCode} đã hoàn tất";
        $notiMsg   = "Phiếu {$issueCode} đã được bàn giao và hoàn tất toàn bộ quy trình.";
    } elseif ($newStatus === 'rejected') {
        $notiTitle = "Phiếu xuất kho {$issueCode} bị từ chối";
        $notiMsg   = "Phiếu {$issueCode} đã bị từ chối phê duyệt. Lý do: {$comment}";
    }

    if ($notiTitle) {
        $notiLink = "index.php?mainpage=warehouse&subpage=approval&issue_id={$issueId}";
        $targetUsers = [];
        if (!empty($issue['creator_id'])) {
            $targetUsers[] = intval($issue['creator_id']);
        }
        if ($targetRoleType) {
            $uRes = $conn->query("
                SELECT DISTINCT u.id 
                FROM users u
                WHERE u.role IN ('admin', 'editor')
                   OR LOWER(u.username) IN (
                       SELECT LOWER(username) FROM warehouse_approvers 
                       WHERE is_active = 1 AND role_type = '{$targetRoleType}' AND (group_name = '{$grpEsc}' OR group_name = 'ALL')
                   )
            ");
            if ($uRes) {
                while ($ur = $uRes->fetch_assoc()) {
                    $targetUsers[] = intval($ur['id']);
                }
            }
        }
        $targetUsers = array_unique($targetUsers);
        if (!empty($targetUsers)) {
            $stmtNoti = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            foreach ($targetUsers as $tUid) {
                $stmtNoti->bind_param("isss", $tUid, $notiTitle, $notiMsg, $notiLink);
                $stmtNoti->execute();
            }
            $stmtNoti->close();
        }
    }

    return [
        'success'    => true,
        'message'    => "Đã xử lý: {$actionName} thành công!",
        'new_status' => $newStatus
    ];
}

/**
 * Lấy danh sách phiếu yêu cầu xuất kho
 */
function getIssueList($conn, $filters = [], $page = 1, $limit = 20) {
    $where = "WHERE 1=1";
    if (!empty($filters['group_name']) && $filters['group_name'] !== 'ALL') {
        $grp = $conn->real_escape_string($filters['group_name']);
        $where .= " AND i.group_name = '{$grp}'";
    }
    if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
        $st = $conn->real_escape_string($filters['status']);
        $where .= " AND i.status = '{$st}'";
    }
    if (!empty($filters['issue_type']) && $filters['issue_type'] !== 'ALL') {
        $t = $conn->real_escape_string($filters['issue_type']);
        $where .= " AND i.issue_type = '{$t}'";
    }
    if (!empty($filters['month'])) {
        $m = intval($filters['month']);
        $where .= " AND i.month = {$m}";
    }
    if (!empty($filters['year'])) {
        $y = intval($filters['year']);
        $where .= " AND i.year = {$y}";
    }
    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $where .= " AND (i.issue_code LIKE '%{$s}%' OR i.creator_name LIKE '%{$s}%' OR i.purpose LIKE '%{$s}%')";
    }

    $sqlCount = "SELECT COUNT(*) as tot FROM warehouse_issues i {$where}";
    $resCount = $conn->query($sqlCount);
    $totalRows = $resCount ? intval($resCount->fetch_assoc()['tot']) : 0;
    $totalPages = max(1, ceil($totalRows / $limit));

    $offset = ($page - 1) * $limit;
    $sql = "
        SELECT i.*,
               (SELECT COUNT(*) FROM warehouse_issue_items WHERE issue_id = i.id) as items_count,
               (SELECT COUNT(*) FROM warehouse_issue_items WHERE issue_id = i.id AND is_below_rop = 1) as rop_items_count
        FROM warehouse_issues i
        {$where}
        ORDER BY i.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $res = $conn->query($sql);
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
    }

    return [
        'data'       => $list,
        'total_rows' => $totalRows,
        'total_pages'=> $totalPages,
        'page'       => $page,
        'limit'      => $limit
    ];
}

/**
 * Lấy chi tiết toàn bộ phiếu yêu cầu kèm các mặt hàng và lịch sử duyệt
 */
function getIssueDetail($conn, $issueId, $currentUser = null) {
    $issueId = intval($issueId);
    $res = $conn->query("SELECT * FROM warehouse_issues WHERE id = {$issueId}");
    $issue = $res ? $res->fetch_assoc() : null;
    if (!$issue) return null;

    // Items
    $itemsRes = $conn->query("SELECT * FROM warehouse_issue_items WHERE issue_id = {$issueId} ORDER BY id ASC");
    $items = [];
    if ($itemsRes) {
        while ($it = $itemsRes->fetch_assoc()) {
            $items[] = $it;
        }
    }
    $issue['items'] = $items;

    // Workflow logs
    $logsRes = $conn->query("SELECT * FROM warehouse_workflow_logs WHERE issue_id = {$issueId} ORDER BY id ASC");
    $logs = [];
    if ($logsRes) {
        while ($l = $logsRes->fetch_assoc()) {
            $logs[] = $l;
        }
    }
    $issue['logs'] = $logs;

    // Trích xuất thông tin người duyệt và mộc xác nhận từng bước
    $issue['checker_name'] = null;
    $issue['checker_approved_at'] = null;
    $issue['checker_comment'] = null;
    $issue['manager_name'] = null;
    $issue['manager_approved_at'] = null;
    $issue['manager_comment'] = null;
    $issue['admin_issuer_name'] = null;
    $issue['admin_issued_at'] = null;
    $issue['admin_issue_comment'] = null;
    $issue['handover_receiver_name'] = null;
    $issue['handover_receiver_code'] = null;
    $issue['handover_completed_at'] = null;
    $issue['handover_comment'] = null;

    foreach ($logs as $l) {
        $step = $l['step'] ?? '';
        $action = mb_strtolower($l['action'] ?? '', 'UTF-8');
        if ($step === 'checker' && (strpos($action, 'duyệt') !== false || strpos($action, 'approve') !== false)) {
            $issue['checker_name'] = $l['actor_name'];
            $issue['checker_approved_at'] = $l['created_at'];
            $issue['checker_comment'] = $l['comment'];
        } elseif ($step === 'manager' && (strpos($action, 'duyệt') !== false || strpos($action, 'approve') !== false)) {
            $issue['manager_name'] = $l['actor_name'];
            $issue['manager_approved_at'] = $l['created_at'];
            $issue['manager_comment'] = $l['comment'];
        } elseif ($step === 'admin_issue' && (strpos($action, 'xuất') !== false || strpos($action, 'approve') !== false)) {
            $issue['admin_issuer_name'] = $l['actor_name'];
            $issue['admin_issued_at'] = $l['created_at'];
            $issue['admin_issue_comment'] = $l['comment'];
        } elseif ($step === 'handover' && (strpos($action, 'bàn giao') !== false || strpos($action, 'nhận') !== false || strpos($action, 'hoàn tất') !== false)) {
            $issue['handover_receiver_name'] = !empty($l['handover_receiver_name']) ? $l['handover_receiver_name'] : $l['actor_name'];
            $issue['handover_receiver_code'] = $l['handover_receiver_code'] ?? '';
            $issue['handover_completed_at'] = $l['created_at'];
            $issue['handover_comment'] = $l['comment'];
        }
    }

    // Xác định bước phê duyệt hiện tại và quyền duyệt của user
    $status = $issue['status'] ?? '';
    $step = '';
    if ($status === 'pending_checker') $step = 'checker';
    elseif ($status === 'pending_manager') $step = 'manager';
    elseif ($status === 'pending_admin_issue') $step = 'admin_issue';
    elseif ($status === 'pending_handover') $step = 'handover';

    $issue['current_step'] = $step;
    $issue['can_approve']  = false;
    if ($step && !empty($currentUser)) {
        $issue['can_approve'] = canUserApproveWarehouseStep($conn, $currentUser, $step, $issue['group_name'] ?? '');
    }

    return $issue;
}

/**
 * Thống kê Dashboard Kho & Xuất Vật Tư
 */
function getWarehouseDashboardStats($conn, $month = 0, $year = 0) {
    if ($month <= 0) $month = intval(date('m'));
    if ($year <= 0) $year = intval(date('Y'));

    // 1. KPI tổng quan tháng
    $sqlKpi = "
        SELECT 
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'pending_checker' THEN 1 ELSE 0 END) as pending_checker,
            SUM(CASE WHEN status = 'pending_manager' THEN 1 ELSE 0 END) as pending_manager,
            SUM(CASE WHEN status = 'pending_admin_issue' THEN 1 ELSE 0 END) as pending_admin_issue,
            SUM(CASE WHEN status = 'pending_handover' THEN 1 ELSE 0 END) as pending_handover,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_issues,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
            SUM(CASE WHEN status IN ('pending_handover', 'completed') THEN total_actual_qty ELSE 0 END) as total_issued_qty
        FROM warehouse_issues
        WHERE month = {$month} AND year = {$year}
    ";
    $kpiRes = $conn->query($sqlKpi);
    $kpi = $kpiRes ? $kpiRes->fetch_assoc() : [];

    // Tồn kho & Cảnh báo ROP
    $stockStatsRes = $conn->query("
        SELECT 
            COUNT(*) as total_materials,
            SUM(CASE WHEN stock_current <= reorder_point THEN 1 ELSE 0 END) as below_rop_count,
            SUM(CASE WHEN stock_current <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(stock_current) as total_stock_items
        FROM warehouse_materials
        WHERE is_active = 1
    ");
    $stockStats = $stockStatsRes ? $stockStatsRes->fetch_assoc() : [];

    // 2. Xu hướng tiêu hao 6 tháng gần nhất
    $trendCategories = [];
    $trendConsumable = [];
    $trendIrregular  = [];
    for ($i = 5; $i >= 0; $i--) {
        $dt = strtotime("-{$i} month", strtotime("{$year}-{$month}-01"));
        $tM = intval(date('m', $dt));
        $tY = intval(date('Y', $dt));
        $trendCategories[] = "T{$tM}/{$tY}";

        // Tổng xuất hoàn tất
        $qTrend = "
            SELECT 
                SUM(CASE WHEN issue_type = 'consumable' THEN total_actual_qty ELSE 0 END) as qty_cons,
                SUM(CASE WHEN issue_type != 'consumable' THEN total_actual_qty ELSE 0 END) as qty_irr
            FROM warehouse_issues
            WHERE month = {$tM} AND year = {$tY} AND status IN ('pending_handover', 'completed')
        ";
        $rTrend = $conn->query($qTrend)->fetch_assoc();
        $trendConsumable[] = round(floatval($rTrend['qty_cons'] ?? 0), 1);
        $trendIrregular[]  = round(floatval($rTrend['qty_irr'] ?? 0), 1);
    }

    // 3. Phân bổ theo Nhóm làm việc (4 nhóm chuẩn nhà máy)
    $groupData = [
        'Thiết bị'       => 0.0,
        'Bảo trì khuôn' => 0.0,
        'Sản xuất'       => 0.0,
        'Nghiền'         => 0.0
    ];
    $hasAnyGroupIssued = false;
    $resGrp = $conn->query("
        SELECT group_name, SUM(total_actual_qty) as total_qty
        FROM warehouse_issues
        WHERE month = {$month} AND year = {$year} AND status IN ('pending_handover', 'completed')
        GROUP BY group_name
        ORDER BY total_qty DESC
    ");
    if ($resGrp && $resGrp->num_rows > 0) {
        while ($g = $resGrp->fetch_assoc()) {
            $grpName = $g['group_name'];
            $val = round(floatval($g['total_qty']), 1);
            $groupData[$grpName] = $val;
            if ($val > 0) $hasAnyGroupIssued = true;
        }
    }
    if (!$hasAnyGroupIssued) {
        // Fallback tỷ trọng phân bổ danh mục vật tư giữa các nhóm
        $resDefGrp = $conn->query("SELECT group_name, COUNT(*) as cnt FROM warehouse_materials GROUP BY group_name");
        if ($resDefGrp) {
            while ($dg = $resDefGrp->fetch_assoc()) {
                $groupData[$dg['group_name']] = intval($dg['cnt']);
            }
        }
    }

    // 4. Top 10 vật tư tiêu hao nhiều nhất
    $topMaterials = [];
    $resTop = $conn->query("
        SELECT m.item_code, m.item_name_vn, m.group_name, m.unit, m.stock_current, m.reorder_point,
               COALESCE(SUM(i.actual_qty), m.avg_monthly_consumption) as month_consumed
        FROM warehouse_materials m
        LEFT JOIN warehouse_issue_items i ON m.id = i.material_id
        LEFT JOIN warehouse_issues wi ON i.issue_id = wi.id AND wi.month = {$month} AND wi.year = {$year} AND wi.status IN ('pending_handover', 'completed')
        WHERE m.is_active = 1
        GROUP BY m.id
        ORDER BY month_consumed DESC
        LIMIT 10
    ");
    if ($resTop) {
        while ($t = $resTop->fetch_assoc()) {
            $topMaterials[] = $t;
        }
    }

    // 5. Danh sách mặt hàng chạm ngưỡng đặt hàng (Below ROP)
    $lowStockList = [];
    $resLow = $conn->query("
        SELECT id, item_code, item_name_vn, group_name, unit, stock_current, reorder_point, reorder_qty, bin_location, image_url,
               CASE WHEN avg_monthly_consumption > 0 THEN ROUND(stock_current / avg_monthly_consumption, 1) ELSE 0 END AS runway_months
        FROM warehouse_materials
        WHERE is_active = 1 AND stock_current <= reorder_point
        ORDER BY stock_current ASC
        LIMIT 15
    ");
    if ($resLow) {
        while ($l = $resLow->fetch_assoc()) {
            $lowStockList[] = $l;
        }
    }

    return [
        'month'             => $month,
        'year'              => $year,
        'kpi'               => array_merge($kpi, $stockStats),
        'trend'             => [
            'categories' => $trendCategories,
            'months'     => $trendCategories,
            'series'     => $trendConsumable,
            'consumable' => $trendConsumable,
            'irregular'  => $trendIrregular
        ],
        'group_distribution'=> [
            'labels' => array_keys($groupData),
            'series' => array_values($groupData)
        ],
        'group_breakdown'   => [
            'labels' => array_keys($groupData),
            'series' => array_values($groupData)
        ],
        'top_materials'     => $topMaterials,
        'low_stock_list'    => $lowStockList
    ];
}

/**
 * Lấy cấu hình các tài khoản phê duyệt ở từng cấp
 */
function getWarehouseApprovers($conn) {
    $res = $conn->query("SELECT * FROM warehouse_approvers WHERE is_active = 1 ORDER BY role_type ASC, group_name ASC");
    $approvers = [
        'checker'        => [],
        'manager'        => [],
        'admin_warehouse'=> [],
        'receiver'       => []
    ];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $type = $r['role_type'];
            if (isset($approvers[$type])) {
                $approvers[$type][] = $r;
            }
        }
    }
    return $approvers;
}

/**
 * Kiểm tra xem người dùng có quyền phê duyệt bước hiện tại hay không
 */
function canUserApproveWarehouseStep($conn, $currentUser, $step, $groupName) {
    $role = $currentUser['role'] ?? 'viewer';
    $uname = $currentUser['username'] ?? '';

    // 1. Admin hoặc Editor có toàn quyền xử lý các bước
    if ($role === 'admin' || $role === 'editor') return true;

    // 2. Kiểm tra quyền chức năng hệ thống theo vai trò người dùng
    $permMap = [
        'checker'     => ['warehouse.check', 'warehouse.manage'],
        'manager'     => ['warehouse.approve', 'warehouse.manage'],
        'admin_issue' => ['warehouse.issue', 'warehouse.manage'],
        'handover'    => ['warehouse.handover', 'warehouse.manage']
    ];
    if (isset($permMap[$step])) {
        if (function_exists('hasPermission') && hasPermission($permMap[$step])) {
            return true;
        }
    }

    // 3. Kiểm tra danh sách người phê duyệt được cấu hình chi tiết theo nhóm
    $roleTypeMap = [
        'checker'     => 'checker',
        'manager'     => 'manager',
        'admin_issue' => 'admin_warehouse',
        'handover'    => 'receiver'
    ];
    $neededType = $roleTypeMap[$step] ?? '';
    if (!$neededType) return false;

    $uEsc = $conn->real_escape_string($uname);
    $gEsc = $conn->real_escape_string($groupName);

    $sql = "
        SELECT id FROM warehouse_approvers 
        WHERE is_active = 1 
          AND role_type = '{$neededType}' 
          AND (LOWER(username) = LOWER('{$uEsc}')) 
          AND (group_name = '{$gEsc}' OR group_name = 'ALL')
        LIMIT 1
    ";
    $res = $conn->query($sql);
    return ($res && $res->num_rows > 0);
}

/**
 * Tạo yêu cầu kiểm tra tồn kho hoặc báo kỳ hạn giao hàng gửi đến Thủ kho
 */
function createStockCheckRequest($conn, $materialId, $requestType, $neededQty, $notes, $currentUser) {
    $mat = getMaterialDetail($conn, $materialId);
    if (!$mat) {
        return ['success' => false, 'message' => 'Không tìm thấy thông tin vật tư.'];
    }

    $requester = $currentUser['fullname'] ?? ($currentUser['username'] ?? 'Nhân viên');
    $matId = intval($mat['id']);
    $itemCode = $mat['item_code'];
    $itemName = $mat['item_name_vn'];
    $stockRemain = floatval($mat['stock_current']);
    $rop = floatval($mat['reorder_point']);
    $reorderQty = $neededQty > 0 ? floatval($neededQty) : max(10.0, $rop * 2);
    $reqType = in_array($requestType, ['delivery_deadline', 'bao_ky_han']) ? 'delivery_deadline' : 'stock_check';
    $status = 'pending';
    $orderStatus = 'cho_dat_hang';

    $adminNote = sprintf(
        "[%s] Yêu cầu từ %s (%s): %s. Số lượng cần: %.1f %s.",
        date('d/m/Y H:i'),
        $requester,
        ($reqType === 'delivery_deadline' ? 'Báo kỳ hạn giao hàng' : 'Kiểm tra tồn kho'),
        $notes,
        $reorderQty,
        $mat['unit']
    );

    $stmt = $conn->prepare("INSERT INTO warehouse_reorder_alerts 
        (issue_id, material_id, item_code, item_name_vn, stock_remain, reorder_point, reorder_qty, status, order_status, admin_notes, requested_by_user, request_type) 
        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdddsssss", $matId, $itemCode, $itemName, $stockRemain, $rop, $reorderQty, $status, $orderStatus, $adminNote, $requester, $reqType);
    $ok = $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();

    if ($ok) {
        return [
            'success'  => true,
            'message'  => 'Đã gửi yêu cầu kiểm tra đến Thủ kho thành công!',
            'alert_id' => $insertId
        ];
    }
    return ['success' => false, 'message' => 'Lỗi lưu yêu cầu: ' . $conn->error];
}

/**
 * Cập nhật hoặc tạo mới thông tin chi tiết vật tư (UPSERT)
 * (Nhóm, Nhóm áp dụng, Loại, Định mức, Tồn kho & ROP)
 */
function saveWarehouseMaterial($conn, $data) {
    $id = intval($data['id'] ?? ($data['material_id'] ?? 0));
    $itemCode = trim($data['item_code'] ?? ($data['material_code'] ?? ($data['ma_vt'] ?? '')));
    $itemNameVn = trim($data['item_name_vn'] ?? ($data['material_name'] ?? ($data['ten_vt'] ?? '')));
    $itemNameEn = trim($data['item_name_en'] ?? '');
    $groupName = trim($data['group_name'] ?? 'Thiết bị');

    if (isset($data['applicable_groups']) && is_array($data['applicable_groups'])) {
        $applicableGroups = implode(',', array_filter(array_map('trim', $data['applicable_groups'])));
    } else {
        $applicableGroups = trim($data['applicable_groups'] ?? '');
    }
    $categoryType = trim($data['category_type'] ?? 'consumable');
    $bin = trim($data['bin_location'] ?? '');
    $unit = trim($data['unit'] ?? 'Ea');
    $packSpec = trim($data['packaging_spec'] ?? ($data['pack_spec'] ?? ''));
    $packQty = max(1.0, floatval($data['pack_quantity'] ?? 1.0));
    $stock = max(0, floatval($data['stock_current'] ?? 0));
    $rop = max(0, floatval($data['reorder_point'] ?? 0));
    $moq = max(0, floatval($data['reorder_qty'] ?? 0));
    $normA = max(0, floatval($data['default_machines'] ?? ($data['norm_machines_count'] ?? 1)));
    $normB = max(0, floatval($data['default_uses_per_machine'] ?? ($data['norm_uses_per_machine'] ?? 1)));
    $normC = max(0, floatval($data['norm_per_use'] ?? 1));

    if (empty($applicableGroups) && !empty($groupName)) {
        $applicableGroups = $groupName;
    }

    // Nếu ID <= 0, kiểm tra xem item_code đã tồn tại hay chưa
    if ($id <= 0) {
        if (empty($itemCode) || empty($itemNameVn)) {
            return ['success' => false, 'message' => 'Vui lòng nhập Mã vật tư và Tên vật tư (Tiếng Việt).'];
        }
        $codeEsc = $conn->real_escape_string($itemCode);
        $checkExisting = $conn->query("SELECT id FROM warehouse_materials WHERE item_code = '{$codeEsc}' LIMIT 1");
        if ($checkExisting && $exRow = $checkExisting->fetch_assoc()) {
            $id = intval($exRow['id']);
        }
    }

    if ($id > 0) {
        $nameVnSql = !empty($itemNameVn) ? ", item_name_vn = '{$conn->real_escape_string($itemNameVn)}'" : "";
        $nameEnSql = !empty($itemNameEn) ? ", item_name_en = '{$conn->real_escape_string($itemNameEn)}'" : "";

        $sql = "UPDATE warehouse_materials SET 
            group_name = ?, 
            applicable_groups = ?, 
            category_type = ?, 
            bin_location = ?, 
            unit = ?, 
            packaging_spec = ?, 
            pack_quantity = ?, 
            stock_current = ?, 
            reorder_point = ?, 
            reorder_qty = ?, 
            default_machines = ?, 
            default_uses_per_machine = ?, 
            norm_per_use = ?
            {$nameVnSql}
            {$nameEnSql}
            WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssdddddddi", 
            $groupName, 
            $applicableGroups, 
            $categoryType, 
            $bin, 
            $unit, 
            $packSpec, 
            $packQty, 
            $stock, 
            $rop, 
            $moq, 
            $normA, 
            $normB, 
            $normC, 
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            checkAndGenerateRopAlerts($conn);
            return ['success' => true, 'message' => 'Đã cập nhật thông tin vật tư thành công.', 'id' => $id];
        }
        return ['success' => false, 'message' => 'Lỗi khi cập nhật vật tư: ' . $conn->error];
    } else {
        // Tạo mới vật tư (INSERT)
        $stmt = $conn->prepare("INSERT INTO warehouse_materials (
            item_code, item_name_vn, item_name_en, group_name, applicable_groups, 
            category_type, bin_location, unit, packaging_spec, pack_quantity, 
            stock_initial, stock_current, reorder_point, reorder_qty, 
            default_machines, default_uses_per_machine, norm_per_use, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        $stmt->bind_param("sssssssssdddddddd",
            $itemCode,
            $itemNameVn,
            $itemNameEn,
            $groupName,
            $applicableGroups,
            $categoryType,
            $bin,
            $unit,
            $packSpec,
            $packQty,
            $stock,
            $stock,
            $rop,
            $moq,
            $normA,
            $normB,
            $normC
        );
        $ok = $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();

        if ($ok) {
            checkAndGenerateRopAlerts($conn);
            return ['success' => true, 'message' => "Đã đăng ký vật tư mới [{$itemCode}] thành công.", 'id' => $newId];
        }
        return ['success' => false, 'message' => 'Lỗi khi thêm vật tư mới: ' . $conn->error];
    }
}

/**
 * Tự động kiểm tra và đồng bộ cảnh báo ROP (Reorder Point):
 * Khi tồn kho hiện tại (stock_current) <= reorder_point và chưa có yêu cầu/đơn hàng đang xử lý,
 * hệ thống tự động sinh và gửi bản ghi cảnh báo đến Thủ kho / Admin trong warehouse_reorder_alerts.
 */
function checkAndGenerateRopAlerts($conn) {
    $sql = "
        SELECT m.id, m.item_code, m.item_name_vn, m.unit, m.stock_current, m.reorder_point, m.reorder_qty, m.group_name
        FROM warehouse_materials m
        WHERE m.is_active = 1
          AND m.reorder_point > 0
          AND m.stock_current <= m.reorder_point
    ";
    $res = $conn->query($sql);
    $generatedCount = 0;

    if ($res) {
        while ($m = $res->fetch_assoc()) {
            $mId = intval($m['id']);
            // Kiểm tra xem đã có cảnh báo đang chờ xử lý hay đơn đang đặt/giao
            $checkActive = $conn->query("
                SELECT id FROM warehouse_reorder_alerts 
                WHERE material_id = {$mId} 
                  AND order_status IN ('cho_dat_hang', 'da_dat_hang', 'cho_giao_hang')
                LIMIT 1
            ");
            if ($checkActive && $checkActive->num_rows > 0) {
                continue;
            }

            $stockCurr = floatval($m['stock_current']);
            $rop = floatval($m['reorder_point']);
            $moq = floatval($m['reorder_qty'] > 0 ? $m['reorder_qty'] : max(10, $rop * 2));
            $codeEsc = $conn->real_escape_string($m['item_code']);
            $nameEsc = $conn->real_escape_string($m['item_name_vn']);
            $unit = !empty($m['unit']) ? $m['unit'] : 'Ea';

            $note = sprintf(
                "[%s] CẢNH BÁO TỰ ĐỘNG (ROP MONITOR): Tồn kho hiện tại (%.1f %s) đã xuống dưới hoặc bằng điểm đặt hàng (%.1f %s). Hệ thống tự động tạo yêu cầu xác nhận đặt hàng với số lượng đề xuất: %.1f %s.",
                date('d/m/Y H:i'),
                $stockCurr, $unit,
                $rop, $unit,
                $moq, $unit
            );

            $stmt = $conn->prepare("
                INSERT INTO warehouse_reorder_alerts 
                (issue_id, material_id, item_code, item_name_vn, stock_remain, reorder_point, reorder_qty, status, order_status, admin_notes, requested_by_user, request_type)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, 'pending', 'cho_dat_hang', ?, 'Hệ thống tự động (ROP Monitor)', 'rop_alert')
            ");
            if ($stmt) {
                $stmt->bind_param("issddds", $mId, $codeEsc, $nameEsc, $stockCurr, $rop, $moq, $note);
                if ($stmt->execute()) {
                    $generatedCount++;
                }
                $stmt->close();
            }
        }
    }

    return $generatedCount;
}

/**
 * Lấy danh sách theo dõi đặt hàng ROP và yêu cầu tồn kho
 */
function getReorderTrackingList($conn, $filters = []) {
    $where = "WHERE 1=1";
    if (!empty($filters['order_status']) && $filters['order_status'] !== 'ALL') {
        $st = $conn->real_escape_string($filters['order_status']);
        $where .= " AND a.order_status = '{$st}'";
    }
    if (!empty($filters['request_type']) && $filters['request_type'] !== 'ALL') {
        $rt = $conn->real_escape_string($filters['request_type']);
        $where .= " AND a.request_type = '{$rt}'";
    }
    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $where .= " AND (a.item_code LIKE '%{$s}%' OR a.item_name_vn LIKE '%{$s}%' OR a.po_code LIKE '%{$s}%' OR a.supplier_name LIKE '%{$s}%')";
    }

    $sql = "
        SELECT a.*, m.unit, m.group_name, m.bin_location, m.image_url, m.stock_current as current_stock_realtime,
               CASE 
                   WHEN m.avg_monthly_consumption > 0 THEN ROUND(m.stock_current / m.avg_monthly_consumption, 1)
                   ELSE 99.0 
               END AS runway_months
        FROM warehouse_reorder_alerts a
        LEFT JOIN warehouse_materials m ON a.material_id = m.id
        {$where}
        ORDER BY 
            CASE a.order_status 
                WHEN 'cho_dat_hang' THEN 1 
                WHEN 'da_dat_hang' THEN 2 
                WHEN 'cho_giao_hang' THEN 3 
                WHEN 'da_giao_hang' THEN 4 
                ELSE 5 
            END ASC,
            a.id DESC
    ";
    $res = $conn->query($sql);
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
    }
    return $list;
}

/**
 * Cập nhật tiến độ đơn hàng ROP
 */
function updateReorderOrderStatus($conn, $alertId, $orderStatus, $data, $currentUser) {
    $id = intval($alertId);
    $st = trim($orderStatus);
    $validStatuses = ['cho_dat_hang', 'da_dat_hang', 'cho_giao_hang', 'da_giao_hang'];
    if (!in_array($st, $validStatuses)) {
        return ['success' => false, 'message' => 'Trạng thái đơn hàng không hợp lệ.'];
    }

    $poCode = trim($data['po_code'] ?? '');
    $supplier = trim($data['supplier_name'] ?? '');
    $orderedQty = floatval($data['ordered_qty'] ?? 0);
    if ($orderedQty <= 0) {
        $exRes = $conn->query("SELECT ordered_qty, reorder_qty FROM warehouse_reorder_alerts WHERE id = {$id}");
        if ($exRes && $exRow = $exRes->fetch_assoc()) {
            $orderedQty = floatval($exRow['ordered_qty'] > 0 ? $exRow['ordered_qty'] : $exRow['reorder_qty']);
        }
    }
    $orderedAt = !empty($data['ordered_at']) ? trim($data['ordered_at']) : null;
    $eta = !empty($data['expected_delivery_date']) ? trim($data['expected_delivery_date']) : null;
    $notes = trim($data['admin_notes'] ?? '');

    $adminName = $currentUser['fullname'] ?? ($currentUser['username'] ?? 'Thủ kho');
    $deliveredAt = null;
    $statusCol = ($st === 'da_giao_hang') ? 'completed' : 'pending';

    if ($st === 'da_giao_hang') {
        $deliveredAt = date('Y-m-d H:i:s');
    }

    $stmt = $conn->prepare("UPDATE warehouse_reorder_alerts SET 
        order_status = ?,
        po_code = ?,
        supplier_name = ?,
        ordered_qty = ?,
        ordered_at = ?,
        expected_delivery_date = ?,
        delivered_at = ?,
        admin_notes = ?,
        status = ?
        WHERE id = ?");
    $stmt->bind_param("sssddssssi", 
        $st, $poCode, $supplier, $orderedQty, $orderedAt, $eta, $deliveredAt, $notes, $statusCol, $id
    );
    $ok = $stmt->execute();
    $stmt->close();

    // Nếu đã nhận hàng và người dùng chọn cộng dồn vào tồn kho thực tế
    if ($ok && $st === 'da_giao_hang' && !empty($data['auto_add_stock'])) {
        $alertRes = $conn->query("SELECT material_id FROM warehouse_reorder_alerts WHERE id = {$id}");
        if ($alertRes && $aRow = $alertRes->fetch_assoc()) {
            $mId = intval($aRow['material_id']);
            $addQty = $orderedQty > 0 ? $orderedQty : 0;
            if ($addQty > 0) {
                $conn->query("UPDATE warehouse_materials SET stock_current = stock_current + {$addQty} WHERE id = {$mId}");
            }
        }
    }

    if ($ok) {
        return ['success' => true, 'message' => 'Cập nhật tiến độ đơn hàng thành công!'];
    }
    return ['success' => false, 'message' => 'Lỗi khi cập nhật tiến độ: ' . $conn->error];
}

/**
 * Render bản in PDF chuẩn A4 (HTML Print View) với đầy đủ 5 ô đóng mộc / chữ ký xác nhận
 */
function renderIssuePdfHtml($conn, $issueId) {
    $issue = getIssueDetail($conn, $issueId);
    if (!$issue) {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lỗi</title></head><body style="font-family:sans-serif; text-align:center; padding:50px;"><h2>Không tìm thấy phiếu yêu cầu xuất kho #' . intval($issueId) . '</h2><p><a href="javascript:window.close()">Đóng cửa sổ</a></p></body></html>';
    }

    $issueCode = htmlspecialchars($issue['issue_code']);
    $groupName = htmlspecialchars($issue['group_name']);
    $issueTypeDesc = ($issue['issue_type'] === 'consumable') ? 'Vật tư tiêu hao (Định mức)' : (($issue['issue_type'] === 'irregular') ? 'Vật tư bất thường' : 'Vật tư hỗn hợp');
    $month = intval($issue['month']);
    $year  = intval($issue['year']);
    $creatorName = htmlspecialchars($issue['creator_name']);
    $creatorCode = htmlspecialchars($issue['creator_code'] ?? '');
    $createdDate = date('d/m/Y H:i', strtotime($issue['created_at']));
    $purpose = htmlspecialchars($issue['purpose'] ?? 'Xuất vật tư sản xuất');
    $notes = htmlspecialchars($issue['notes'] ?? '');

    // Trạng thái phiếu
    $statusMap = [
        'pending_checker'     => ['Chờ người kiểm tra duyệt', '#f59e0b', '#fffbeb'],
        'pending_manager'     => ['Chờ quản lý phê duyệt', '#8b5cf6', '#f5f3ff'],
        'pending_admin_issue' => ['Chờ thủ kho xuất hàng', '#06b6d4', '#ecfeff'],
        'pending_handover'    => ['Chờ bàn giao hiện trường', '#3b82f6', '#eff6ff'],
        'completed'           => ['Hoàn tất - Đã nhận bàn giao', '#10b981', '#ecfdf5'],
        'rejected'            => ['Bị từ chối phê duyệt', '#ef4444', '#fef2f2'],
        'cancelled'           => ['Đã hủy bỏ', '#6b7280', '#f3f4f6']
    ];
    $stInfo = $statusMap[$issue['status']] ?? ['Đang xử lý', '#3b82f6', '#eff6ff'];

    $items = $issue['items'] ?? [];
    $rowsHtml = '';
    $totTheo = 0.0;
    $totAct = 0.0;

    foreach ($items as $idx => $it) {
        $stt = $idx + 1;
        $code = htmlspecialchars($it['item_code']);
        $name = htmlspecialchars($it['item_name_vn']);
        $unit = htmlspecialchars($it['unit']);
        $spec = htmlspecialchars($it['packaging_spec'] ?? ($it['pack_spec'] ?? ''));
        $bin  = htmlspecialchars($it['bin_location'] ?? '-');

        $mA = number_format(floatval($it['machines_count']), 1, '.', '');
        $uB = number_format(floatval($it['uses_per_machine']), 1, '.', '');
        $nC = number_format(floatval($it['norm_per_use']), 1, '.', '');
        $totUses = number_format(floatval($it['total_uses']), 1, '.', '');
        $theor = number_format(floatval($it['theoretical_qty']), 1, '.', '');
        $fStock = number_format(floatval($it['field_stock']), 1, '.', '');
        $rStock = number_format(floatval($it['reusable_stock']), 1, '.', '');
        $netTheor = number_format(floatval($it['net_theoretical_qty']), 1, '.', '');
        $actQty = number_format(floatval($it['actual_qty']), 1, '.', '');
        $stAfter = number_format(floatval($it['stock_after_issue']), 1, '.', '');
        $rwMonth = number_format(floatval($it['runway_months']), 1, '.', '');
        $itemNote = htmlspecialchars($it['irregular_reason'] ?? '');

        $totTheo += floatval($it['theoretical_qty']);
        $totAct  += floatval($it['actual_qty']);

        $rowsHtml .= "
        <tr>
            <td style=\"text-align: center;\">{$stt}</td>
            <td style=\"font-family: monospace; font-weight: bold; text-align: center;\">{$code}</td>
            <td>
                <strong>{$name}</strong>" . ($spec ? "<div style=\"font-size:10px; color:#555;\">QC: {$spec}</div>" : "") . "
            </td>
            <td style=\"text-align: center;\">{$unit}</td>
            <td style=\"text-align: center;\">{$bin}</td>
            <td style=\"text-align: center; background: #fffbeb;\">{$mA}</td>
            <td style=\"text-align: center; background: #fffbeb;\">{$uB}</td>
            <td style=\"text-align: right; background: #fffbeb;\">{$nC}</td>
            <td style=\"text-align: center; background: #eff6ff;\">{$totUses}</td>
            <td style=\"text-align: right; background: #eff6ff;\">{$theor}</td>
            <td style=\"text-align: right; background: #fffbeb;\">{$fStock}</td>
            <td style=\"text-align: right; background: #fffbeb;\">{$rStock}</td>
            <td style=\"text-align: right; background: #eff6ff;\">{$netTheor}</td>
            <td style=\"text-align: right; font-weight: bold; background: #e2efda; color: #166534;\">{$actQty}</td>
            <td style=\"text-align: right; background: #eff6ff;\">{$stAfter}</td>
            <td style=\"text-align: center; background: #eff6ff;\">{$rwMonth}</td>
            <td style=\"font-size: 10px;\">{$itemNote}</td>
        </tr>";
    }

    $totTheorStr = number_format($totTheo, 1, '.', '');
    $totActStr   = number_format($totAct, 1, '.', '');
    $itemsCount  = count($items);

    // Mộc & Dấu ký 5 bước
    // 1. Người lập phiếu
    $s1 = [
        'title'    => '1. NGƯỜI LẬP PHIẾU',
        'sub'      => '(Ký & ghi rõ họ tên)',
        'signed'   => true,
        'name'     => $creatorName . ($creatorCode ? " ({$creatorCode})" : ''),
        'date'     => $createdDate,
        'badge'    => 'ĐÃ LẬP PHIẾU',
        'color'    => '#16a34a'
    ];

    // 2. Người kiểm tra
    $chkSigned = !empty($issue['checker_approved_at']);
    $s2 = [
        'title'    => '2. NGƯỜI KIỂM TRA',
        'sub'      => '(Ký & thẩm định)',
        'signed'   => $chkSigned,
        'name'     => $chkSigned ? htmlspecialchars($issue['checker_name']) : '(Chưa kiểm tra)',
        'date'     => $chkSigned ? date('d/m/Y H:i', strtotime($issue['checker_approved_at'])) : '--',
        'badge'    => $chkSigned ? 'ĐÃ KIỂM TRA' : 'CHỜ KIỂM TRA',
        'color'    => $chkSigned ? '#16a34a' : '#94a3b8'
    ];

    // 3. Quản lý phê duyệt
    $mgrSigned = !empty($issue['manager_approved_at']);
    $s3 = [
        'title'    => '3. QUẢN LÝ PHÊ DUYỆT',
        'sub'      => '(Ký & phê chuẩn)',
        'signed'   => $mgrSigned,
        'name'     => $mgrSigned ? htmlspecialchars($issue['manager_name']) : '(Chưa duyệt)',
        'date'     => $mgrSigned ? date('d/m/Y H:i', strtotime($issue['manager_approved_at'])) : '--',
        'badge'    => $mgrSigned ? 'ĐÃ PHÊ DUYỆT' : 'CHỜ DUYỆT',
        'color'    => $mgrSigned ? '#2563eb' : '#94a3b8'
    ];

    // 4. Thủ kho xuất hàng
    $admSigned = !empty($issue['admin_issued_at']);
    $s4 = [
        'title'    => '4. THỦ KHO XUẤT HÀNG',
        'sub'      => '(Ký & xuất kho)',
        'signed'   => $admSigned,
        'name'     => $admSigned ? htmlspecialchars($issue['admin_issuer_name']) : '(Chưa xuất)',
        'date'     => $admSigned ? date('d/m/Y H:i', strtotime($issue['admin_issued_at'])) : '--',
        'badge'    => $admSigned ? 'ĐÃ XUẤT KHO' : 'CHỜ XUẤT',
        'color'    => $admSigned ? '#0891b2' : '#94a3b8'
    ];

    // 5. Bàn giao hiện trường
    $recSigned = !empty($issue['handover_completed_at']);
    $s5 = [
        'title'    => '5. NHẬN BÀN GIAO',
        'sub'      => '(Ký & nhận đủ)',
        'signed'   => $recSigned,
        'name'     => $recSigned ? htmlspecialchars($issue['handover_receiver_name'] . ($issue['handover_receiver_code'] ? " ({$issue['handover_receiver_code']})" : '')) : '(Chưa nhận)',
        'date'     => $recSigned ? date('d/m/Y H:i', strtotime($issue['handover_completed_at'])) : '--',
        'badge'    => $recSigned ? 'ĐÃ NHẬN ĐỦ' : 'CHỜ BÀN GIAO',
        'color'    => $recSigned ? '#16a34a' : '#94a3b8'
    ];

    $stampBoxes = [$s1, $s2, $s3, $s4, $s5];
    $stampsHtml = '';
    foreach ($stampBoxes as $st) {
        $sealHtml = $st['signed']
            ? "<div style=\"border: 2px solid {$st['color']}; color: {$st['color']}; border-radius: 4px; padding: 2px 5px; font-weight: bold; font-size: 10px; display: inline-block; text-transform: uppercase; margin: 4px auto; letter-spacing: 0.5px;\">✓ {$st['badge']}</div>"
            : "<div style=\"border: 1px dashed {$st['color']}; color: {$st['color']}; border-radius: 4px; padding: 2px 5px; font-size: 9.5px; display: inline-block; margin: 4px auto;\">{$st['badge']}</div>";

        $stampsHtml .= "
        <div style=\"border: 1px solid #cbd5e1; border-radius: 5px; padding: 8px 6px; text-align: center; min-height: 125px; display: flex; flex-direction: column; justify-content: space-between; background: #fafafa;\">
            <div style=\"font-weight: bold; font-size: 11px; text-transform: uppercase; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;\">
                {$st['title']}
                <div style=\"font-size: 9.5px; font-weight: normal; color: #64748b;\">{$st['sub']}</div>
            </div>
            <div style=\"margin: 4px 0;\">
                {$sealHtml}
            </div>
            <div style=\"border-top: 1px dashed #cbd5e1; padding-top: 4px;\">
                <div style=\"font-weight: bold; font-size: 11px; color: #0f172a;\">{$st['name']}</div>
                <div style=\"font-size: 9.5px; color: #64748b; font-family: monospace;\">{$st['date']}</div>
            </div>
        </div>";
    }

    $autoPrintJs = (isset($_GET['autoprint']) && $_GET['autoprint'] == '1')
        ? "<script>window.addEventListener('DOMContentLoaded', () => setTimeout(() => window.print(), 500));</script>"
        : "";

    return "<!DOCTYPE html>
<html lang=\"vi\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Phiếu Xuất Kho {$issueCode} - DX Plastic Group</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.35;
            color: #111827;
            background: #f1f5f9;
            margin: 0;
            padding: 20px 10px;
        }
        .page-container {
            max-width: 1050px;
            margin: 0 auto;
            background: #ffffff;
            padding: 28px 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 6px;
        }
        .top-toolbar {
            max-width: 1050px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-excel { background: #16a34a; color: #fff; }
        .btn-excel:hover { background: #15803d; }
        .btn-close-win { background: #475569; color: #fff; }
        .btn-close-win:hover { background: #334155; }

        table.tbl-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 12px 0 16px 0;
        }
        table.tbl-data th, table.tbl-data td {
            border: 1px solid #64748b;
            padding: 4px 5px;
            vertical-align: middle;
        }
        table.tbl-data thead th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: bold;
        }
        .th-input { background-color: #fef3c7 !important; color: #92400e; }
        .th-calc { background-color: #eff6ff !important; color: #1e40af; }
        .grid-stamps {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 18px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .page-container {
                max-width: 100% !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 8mm;
            }
            th, td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .grid-stamps > div {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Top Toolbar (Ẩn khi In) -->
    <div class=\"top-toolbar no-print\">
        <div style=\"font-size: 14px; font-weight: bold;\">
            📄 Xem Trước & In Bản Phiếu A4 (Mã: {$issueCode})
        </div>
        <div style=\"display: flex; gap: 8px;\">
            <button class=\"btn-action btn-print\" onclick=\"window.print()\">
                🖨️ In Phiếu / Lưu PDF (A4)
            </button>
            <a href=\"api/warehouse.php?action=export_issue_excel&issue_id={$issue['id']}\" class=\"btn-action btn-excel\">
                📊 Xuất File Excel
            </a>
            <button class=\"btn-action btn-close-win\" onclick=\"window.close()\">
                ✕ Đóng
            </button>
        </div>
    </div>

    <div class=\"page-container\">
        <!-- Header Công ty & Biểu mẫu -->
        <table style=\"width: 100%; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px;\">
            <tr>
                <td style=\"width: 65%; vertical-align: top;\">
                    <div style=\"font-size: 15px; font-weight: bold; text-transform: uppercase; color: #000;\">
                        DX PLASTIC GROUP - BỘ PHẬN KHO & SẢN XUẤT
                    </div>
                    <div style=\"font-size: 11px; color: #475569;\">Hệ Thống Quản Lý Xuất Nhập Tồn Vật Tư Tiêu Hao & Định Mức</div>
                    <div style=\"font-size: 11px; color: #475569;\">Địa chỉ: Nhà máy DX Plastic, Đường số 3, KCN Long Thành, Đồng Nai</div>
                </td>
                <td style=\"width: 35%; text-align: right; vertical-align: top; font-size: 11px;\">
                    <div>Mã Biểu Mẫu: <strong>BM-WH-XK-05</strong></div>
                    <div>Ban hành: 01/2026 | Lần sửa: 03</div>
                    <div style=\"margin-top: 3px;\">
                        Trạng thái: <span style=\"font-weight:bold; color:{$stInfo[1]}; background:{$stInfo[2]}; padding:2px 6px; border-radius:3px; border:1px solid {$stInfo[1]};\">{$stInfo[0]}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tiêu đề Phiếu -->
        <div style=\"text-align: center; margin: 10px 0 14px 0;\">
            <h2 style=\"margin: 0; font-size: 19px; text-transform: uppercase; letter-spacing: 0.5px;\">
                PHIẾU YÊU CẦU & XUẤT KHO VẬT TƯ
            </h2>
            <div style=\"font-size: 12px; margin-top: 3px; font-style: italic;\">
                Mã phiếu: <strong style=\"font-family: monospace; font-size: 13px; color: #1e3a8a;\">{$issueCode}</strong> 
                | Phân loại: <strong>{$issueTypeDesc}</strong> 
                | Kỳ xuất: <strong>Tháng {$month}/{$year}</strong>
            </div>
        </div>

        <!-- Thông tin hành chính của phiếu -->
        <table style=\"width: 100%; font-size: 11.5px; margin-bottom: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px;\">
            <tr>
                <td style=\"width: 50%; padding: 3px 6px;\">
                    • <strong>Nhóm công việc:</strong> {$groupName}<br>
                    • <strong>Người lập phiếu:</strong> {$creatorName}" . ($creatorCode ? " (Mã NV: {$creatorCode})" : "") . "<br>
                    • <strong>Ngày tạo phiếu:</strong> {$createdDate}
                </td>
                <td style=\"width: 50%; padding: 3px 6px;\">
                    • <strong>Mục đích xuất:</strong> {$purpose}<br>
                    • <strong>Ghi chú:</strong> " . ($notes ?: 'Không có') . "
                </td>
            </tr>
        </table>

        <!-- Bảng Chi Tiết Vật Tư Cần Xuất -->
        <table class=\"tbl-data\">
            <thead>
                <tr>
                    <th rowspan=\"2\" style=\"width: 25px;\">STT</th>
                    <th rowspan=\"2\" style=\"width: 75px;\">Mã VT</th>
                    <th rowspan=\"2\" style=\"min-width: 130px; text-align: left;\">Tên Vật Tư & Quy Cách</th>
                    <th rowspan=\"2\" style=\"width: 35px;\">ĐVT</th>
                    <th rowspan=\"2\" style=\"width: 45px;\">Kệ BIN</th>
                    <th colspan=\"3\" class=\"th-input\">CỘT NHẬP DỮ LIỆU</th>
                    <th colspan=\"2\" class=\"th-calc\">TÍNH TOÁN</th>
                    <th colspan=\"2\" class=\"th-input\">TỒN HIỆN TRƯỜNG</th>
                    <th colspan=\"4\" class=\"th-calc\">QUY ĐỔI & TỒN KHẢ DỤNG</th>
                    <th rowspan=\"2\" style=\"width: 65px;\">Ghi chú</th>
                </tr>
                <tr>
                    <th class=\"th-input\" style=\"width: 45px;\" title=\"Số máy / Người (A)\">Số máy<br>(A)</th>
                    <th class=\"th-input\" style=\"width: 45px;\" title=\"Số lần / máy (B)\">Số lần<br>(B)</th>
                    <th class=\"th-input\" style=\"width: 55px;\" title=\"Định mức / lần (C)\">Định mức<br>(C)</th>
                    <th class=\"th-calc\" style=\"width: 50px;\" title=\"Tổng lần = A x B\">Tổng lần<br>(AxB)</th>
                    <th class=\"th-calc\" style=\"width: 55px;\" title=\"Tổng lý thuyết = A x B x C\">Tổng LT<br>(AxBxC)</th>
                    <th class=\"th-input\" style=\"width: 50px;\" title=\"Tồn hiện trường còn dư\">Tồn HT</th>
                    <th class=\"th-input\" style=\"width: 45px;\" title=\"Tái sử dụng\">Tái SD</th>
                    <th class=\"th-calc\" style=\"width: 55px;\" title=\"Thực xuất lý thuyết\">Thực xuất<br>LT</th>
                    <th class=\"th-calc\" style=\"width: 65px; background: #c6e0b4 !important; color: #166534; font-weight: bold;\">Xuất Thực<br>Tế</th>
                    <th class=\"th-calc\" style=\"width: 55px;\">Tồn sau<br>xuất</th>
                    <th class=\"th-calc\" style=\"width: 45px;\">Tháng<br>tồn</th>
                </tr>
            </thead>
            <tbody>
                {$rowsHtml}
                <tr style=\"font-weight: bold; background: #f8fafc;\">
                    <td colspan=\"5\" style=\"text-align: center; text-transform: uppercase;\">
                        TỔNG CỘNG ({$itemsCount} MẶT HÀNG)
                    </td>
                    <td colspan=\"4\"></td>
                    <td style=\"text-align: right;\">{$totTheorStr}</td>
                    <td colspan=\"3\"></td>
                    <td style=\"text-align: right; background: #c6e0b4; color: #166534; font-size: 12px;\">{$totActStr}</td>
                    <td colspan=\"3\"></td>
                </tr>
            </tbody>
        </table>

        <!-- Khung 5 Chữ Ký & Mộc Xác Nhận -->
        <div style=\"margin-top: 14px;\">
            <div style=\"font-weight: bold; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; color: #334155;\">
                QUY TRÌNH DUYỆT & ĐÓNG MỘC XÁC NHẬN (5 CẤP CHUẨN):
            </div>
            <div class=\"grid-stamps\">
                {$stampsHtml}
            </div>
        </div>

        <!-- Footer Chú Thích Bắt Buộc -->
        <div style=\"margin-top: 20px; border-top: 1px dotted #94a3b8; padding-top: 6px; font-size: 10px; color: #64748b; display: flex; justify-content: space-between;\">
            <span>Hệ thống Quản lý Kho Nhà Máy DX Plastic Group - Bản in chuẩn ISO</span>
            <span>In ngày: " . date('d/m/Y H:i:s') . " | Trang 1/1</span>
        </div>
    </div>

    {$autoPrintJs}
</body>
</html>";
}

/**
 * Xuất dữ liệu 1 phiếu xuất kho ra file Excel (CSV UTF-8 BOM chuẩn)
 */
function exportIssueToExcel($conn, $issueId) {
    $issue = getIssueDetail($conn, $issueId);
    if (!$issue) {
        die("Không tìm thấy phiếu yêu cầu xuất kho #{$issueId}");
    }

    $issueCode = $issue['issue_code'];
    $filename = "Phieu_Xuat_Kho_{$issueCode}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Header thông tin phiếu
    fputcsv($out, ['DX PLASTIC GROUP - PHIẾU YÊU CẦU & XUẤT KHO VẬT TƯ']);
    fputcsv($out, ['Mã Phiếu:', $issue['issue_code'], 'Nhóm:', $issue['group_name'], 'Kỳ Xuất:', "Tháng {$issue['month']}/{$issue['year']}"]);
    fputcsv($out, ['Người Tạo:', $issue['creator_name'], 'Ngày Tạo:', $issue['created_at'], 'Trạng Thái:', $issue['status']]);
    fputcsv($out, ['Mục Đích:', $issue['purpose'], 'Ghi Chú:', $issue['notes'] ?? '']);
    fputcsv($out, []); // Dòng trống

    // Header bảng
    fputcsv($out, [
        'STT', 'Mã Vật Tư', 'Tên Vật Tư', 'ĐVT', 'Kệ BIN', 
        'Số Máy (A)', 'Số Lần (B)', 'Định Mức (C)', 
        'Tổng Lần (AxB)', 'Tổng LT (AxBxC)', 'Tồn HT', 'Tái SD', 
        'Thực Xuất LT', 'Xuất Thực Tế', 'Tồn Sau Xuất', 'Tháng Tồn', 'Ghi Chú'
    ]);

    $items = $issue['items'] ?? [];
    $totTheor = 0.0;
    $totAct = 0.0;

    foreach ($items as $idx => $it) {
        $stt = $idx + 1;
        $totTheor += floatval($it['theoretical_qty']);
        $totAct   += floatval($it['actual_qty']);

        fputcsv($out, [
            $stt,
            $it['item_code'],
            $it['item_name_vn'],
            $it['unit'],
            $it['bin_location'] ?? '-',
            number_format(floatval($it['machines_count']), 1, '.', ''),
            number_format(floatval($it['uses_per_machine']), 1, '.', ''),
            number_format(floatval($it['norm_per_use']), 1, '.', ''),
            number_format(floatval($it['total_uses']), 1, '.', ''),
            number_format(floatval($it['theoretical_qty']), 1, '.', ''),
            number_format(floatval($it['field_stock']), 1, '.', ''),
            number_format(floatval($it['reusable_stock']), 1, '.', ''),
            number_format(floatval($it['net_theoretical_qty']), 1, '.', ''),
            number_format(floatval($it['actual_qty']), 1, '.', ''),
            number_format(floatval($it['stock_after_issue']), 1, '.', ''),
            number_format(floatval($it['runway_months']), 1, '.', ''),
            $it['irregular_reason'] ?? ''
        ]);
    }

    // Dòng tổng cộng
    fputcsv($out, [
        'TỔNG CỘNG', count($items) . ' MẶT HÀNG', '', '', '',
        '', '', '', '',
        number_format($totTheor, 1, '.', ''),
        '', '', '',
        number_format($totAct, 1, '.', ''),
        '', '', ''
    ]);

    fputcsv($out, []); // Dòng trống
    fputcsv($out, ['THÔNG TIN DUYỆT & ĐÓNG MỘC:']);
    fputcsv($out, ['1. Người Lập Phiếu:', $issue['creator_name'], 'Ngày:', $issue['created_at']]);
    fputcsv($out, ['2. Người Kiểm Tra:', $issue['checker_name'] ?? 'Chưa duyệt', 'Ngày:', $issue['checker_approved_at'] ?? '--']);
    fputcsv($out, ['3. Quản Lý Phê Duyệt:', $issue['manager_name'] ?? 'Chưa duyệt', 'Ngày:', $issue['manager_approved_at'] ?? '--']);
    fputcsv($out, ['4. Thủ Kho Xuất Hàng:', $issue['admin_issuer_name'] ?? 'Chưa xuất', 'Ngày:', $issue['admin_issued_at'] ?? '--']);
    fputcsv($out, ['5. Người Nhận Bàn Giao:', $issue['handover_receiver_name'] ?? 'Chưa nhận', 'Ngày:', $issue['handover_completed_at'] ?? '--']);

    fclose($out);
    exit;
}

/**
 * Xuất dữ liệu các phiếu xuất kho theo tháng ra file Excel
 */
function exportIssuesMonthlyToExcel($conn, $month, $year, $groupName = '', $status = '') {
    $where = "WHERE 1=1";
    if ($month > 0) $where .= " AND i.month = " . intval($month);
    if ($year > 0)  $where .= " AND i.year = " . intval($year);
    if (!empty($groupName) && $groupName !== 'ALL') {
        $grp = $conn->real_escape_string($groupName);
        $where .= " AND i.group_name = '{$grp}'";
    }
    if (!empty($status) && $status !== 'ALL') {
        $st = $conn->real_escape_string($status);
        $where .= " AND i.status = '{$st}'";
    }

    $filename = "Tong_Hop_Xuat_Kho_T{$month}_{$year}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    fputcsv($out, ["BÁO CÁO TỔNG HỢP PHIẾU XUẤT KHO THÁNG {$month}/{$year} - DX PLASTIC GROUP"]);
    fputcsv($out, []);

    fputcsv($out, [
        'STT', 'Mã Phiếu', 'Nhóm', 'Phân Loại', 'Kỳ Xuất', 
        'Mục Đích Xuất', 'Số Mặt Hàng', 'Tổng Lượng LT', 'Tổng Xuất Thực Tế', 
        'Trạng Thái', 'Người Tạo', 'Ngày Tạo', 'Ghi Chú'
    ]);

    $sql = "SELECT i.* FROM warehouse_issues i {$where} ORDER BY i.id DESC";
    $res = $conn->query($sql);
    $stt = 1;
    $sumItems = 0;
    $sumTheo = 0.0;
    $sumAct = 0.0;

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $sumItems += intval($r['total_items']);
            $sumTheo  += floatval($r['total_theoretical_qty']);
            $sumAct   += floatval($r['total_actual_qty']);

            fputcsv($out, [
                $stt++,
                $r['issue_code'],
                $r['group_name'],
                $r['issue_type'],
                "Tháng {$r['month']}/{$r['year']}",
                $r['purpose'],
                intval($r['total_items']),
                number_format(floatval($r['total_theoretical_qty']), 1, '.', ''),
                number_format(floatval($r['total_actual_qty']), 1, '.', ''),
                $r['status'],
                $r['creator_name'],
                $r['created_at'],
                $r['notes'] ?? ''
            ]);
        }
    }

    fputcsv($out, [
        'TỔNG CỘNG', ($stt - 1) . ' PHIẾU', '', '', '', '',
        $sumItems,
        number_format($sumTheo, 1, '.', ''),
        number_format($sumAct, 1, '.', ''),
        '', '', '', ''
    ]);

    fclose($out);
    exit;
}

/**
 * Xuất toàn bộ danh mục vật tư & tồn kho hiện tại ra file Excel
 */
function exportMaterialsToExcel($conn) {
    $filename = "Danh_Muc_Ton_Kho_Vat_Tu_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    fputcsv($out, ['DANH MỤC VẬT TƯ & TỒN KHO HIỆN TẠI - DX PLASTIC GROUP']);
    fputcsv($out, ['Ngày xuất file:', date('d/m/Y H:i:s')]);
    fputcsv($out, []);

    fputcsv($out, [
        'STT', 'Mã Vật Tư', 'Tên Vật Tư VN', 'Tên Vật Tư EN', 
        'Nhóm Chính', 'Các Nhóm Áp Dụng (Tiêu Hao)', 'Loại Vật Tư', 'ĐVT', 
        'Quy Cách Đóng Gói', 'Số Lượng/Gói', 'Kệ BIN', 
        'Số Máy (A)', 'Số Lần (B)', 'Định Mức (C)', 
        'Tồn Hiện Tại', 'Điểm Đặt Hàng (ROP)', 'Số Lượng Đặt (MOQ)', 
        'Tiêu Hao TB/Tháng', 'Trạng Thái Sử Dụng'
    ]);

    $sql = "SELECT * FROM warehouse_materials ORDER BY group_name ASC, item_code ASC";
    $res = $conn->query($sql);
    $stt = 1;

    if ($res) {
        while ($m = $res->fetch_assoc()) {
            fputcsv($out, [
                $stt++,
                $m['item_code'],
                $m['item_name_vn'],
                $m['item_name_en'] ?? '',
                $m['group_name'],
                $m['applicable_groups'] ?? $m['group_name'],
                $m['category_type'],
                $m['unit'],
                $m['packaging_spec'] ?? '',
                number_format(floatval($m['pack_quantity']), 1, '.', ''),
                $m['bin_location'] ?? '-',
                number_format(floatval($m['default_machines']), 1, '.', ''),
                number_format(floatval($m['default_uses_per_machine']), 1, '.', ''),
                number_format(floatval($m['norm_per_use']), 1, '.', ''),
                number_format(floatval($m['stock_current']), 1, '.', ''),
                number_format(floatval($m['reorder_point']), 1, '.', ''),
                number_format(floatval($m['reorder_qty']), 1, '.', ''),
                number_format(floatval($m['avg_monthly_consumption']), 1, '.', ''),
                intval($m['is_active']) ? 'Đang dùng' : 'Đã khóa'
            ]);
        }
    }

    fclose($out);
    exit;
}

/**
 * Import dữ liệu tồn kho hàng loạt từ file Excel (.xlsx) hoặc CSV
 */
function importMaterialsStock($conn, $fileTmp, $currentUser) {
    if (!file_exists($fileTmp)) {
        return ['success' => false, 'message' => 'File không tồn tại trên máy chủ.'];
    }

    $updatedCount = 0;
    $insertedCount = 0;
    $rowsProcessed = 0;
    $errors = [];

    // Kiểm tra SimpleXLSX
    $isXlsx = false;
    if (file_exists(__DIR__ . '/../vendor/SimpleXLSX.php')) {
        require_once __DIR__ . '/../vendor/SimpleXLSX.php';
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp)) {
            $isXlsx = true;
            $rows = $xlsx->rows();
        }
    }

    if (!$isXlsx) {
        // Fallback đọc dạng CSV
        $rows = [];
        if (($handle = fopen($fileTmp, "r")) !== false) {
            while (($data = fgetcsv($handle, 2000, ",")) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    }

    if (empty($rows) || count($rows) <= 1) {
        return ['success' => false, 'message' => 'File rỗng hoặc không có dữ liệu hợp lệ.'];
    }

    // Tìm vị trí cột dựa trên dòng tiêu đề
    $header = $rows[0];
    $colCode = -1;
    $colNameVn = -1;
    $colNameEn = -1;
    $colGroup = -1;
    $colAppGroups = -1;
    $colCat = -1;
    $colUnit = -1;
    $colBin = -1;
    $colPackSpec = -1;
    $colPackQty = -1;
    $colStock = -1;
    $colRop = -1;
    $colMoq = -1;
    $colBin = -1;
    $colNormA = -1;
    $colNormB = -1;
    $colNormC = -1;

    foreach ($header as $idx => $val) {
        $v = mb_strtolower(trim((string)$val), 'UTF-8');
        if (strpos($v, 'mã') !== false && (strpos($v, 'vật tư') !== false || strpos($v, 'vt') !== false || strpos($v, 'code') !== false)) {
            $colCode = $idx;
        } elseif (strpos($v, 'tên tiếng việt') !== false || strpos($v, 'tên vật tư') !== false || strpos($v, 'tên vn') !== false) {
            $colNameVn = $idx;
        } elseif (strpos($v, 'tên tiếng anh') !== false || strpos($v, 'tên en') !== false) {
            $colNameEn = $idx;
        } elseif (strpos($v, 'các nhóm áp dụng') !== false || strpos($v, 'nhóm áp dụng') !== false) {
            $colAppGroups = $idx;
        } elseif (strpos($v, 'nhóm chính') !== false || strpos($v, 'nhóm') !== false) {
            $colGroup = $idx;
        } elseif (strpos($v, 'phân loại') !== false || strpos($v, 'loại vật tư') !== false) {
            $colCat = $idx;
        } elseif (strpos($v, 'đơn vị tính') !== false || strpos($v, 'đvt') !== false || strpos($v, 'unit') !== false) {
            $colUnit = $idx;
        } elseif (strpos($v, 'kệ bin') !== false || strpos($v, 'bin') !== false || strpos($v, 'kệ') !== false) {
            $colBin = $idx;
        } elseif (strpos($v, 'quy cách') !== false) {
            $colPackSpec = $idx;
        } elseif (strpos($v, 'sl quy đổi') !== false || strpos($v, 'sl/gói') !== false) {
            $colPackQty = $idx;
        } elseif (strpos($v, 'tồn hiện tại') !== false || strpos($v, 'stock_current') !== false || (strpos($v, 'tồn') !== false && strpos($v, 'sau') === false)) {
            $colStock = $idx;
        } elseif (strpos($v, 'rop') !== false || strpos($v, 'đặt hàng') !== false) {
            $colRop = $idx;
        } elseif (strpos($v, 'moq') !== false || strpos($v, 'lượng đặt') !== false) {
            $colMoq = $idx;
        } elseif (strpos($v, 'bin') !== false || strpos($v, 'kệ') !== false) {
            $colBin = $idx;
        } elseif (strpos($v, 'định mức máy') !== false || strpos($v, 'số máy') !== false || strpos($v, '(a)') !== false) {
            $colNormA = $idx;
        } elseif (strpos($v, 'định mức lần') !== false || strpos($v, 'số lần') !== false || strpos($v, '(b)') !== false) {
            $colNormB = $idx;
        } elseif (strpos($v, 'tiêu hao/lần') !== false || strpos($v, 'tiêu hao') !== false || strpos($v, '(c)') !== false) {
            $colNormC = $idx;
        }
    }

    // Nếu không khớp tên tiêu đề, dùng index mặc định theo mẫu chuẩn
    if ($colCode === -1)      $colCode = (count($header) > 1) ? 1 : 0;
    if ($colNameVn === -1)    $colNameVn = (count($header) > 2) ? 2 : -1;
    if ($colNameEn === -1)    $colNameEn = (count($header) > 3) ? 3 : -1;
    if ($colGroup === -1)     $colGroup = (count($header) > 4) ? 4 : -1;
    if ($colAppGroups === -1) $colAppGroups = (count($header) > 5) ? 5 : -1;
    if ($colCat === -1)       $colCat = (count($header) > 6) ? 6 : -1;
    if ($colUnit === -1)      $colUnit = (count($header) > 7) ? 7 : -1;
    if ($colBin === -1)       $colBin = (count($header) > 8) ? 8 : -1;
    if ($colPackSpec === -1)  $colPackSpec = (count($header) > 9) ? 9 : -1;
    if ($colPackQty === -1)   $colPackQty = (count($header) > 10) ? 10 : -1;
    if ($colStock === -1)     $colStock = (count($header) > 11) ? 11 : -1;
    if ($colRop === -1)       $colRop = (count($header) > 12) ? 12 : -1;
    if ($colMoq === -1)       $colMoq = (count($header) > 13) ? 13 : -1;
    if ($colNormA === -1)     $colNormA = (count($header) > 14) ? 14 : -1;
    if ($colNormB === -1)     $colNormB = (count($header) > 15) ? 15 : -1;
    if ($colNormC === -1)     $colNormC = (count($header) > 16) ? 16 : -1;

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty($row) || !isset($row[$colCode])) continue;

        $itemCode = trim((string)$row[$colCode]);
        if (empty($itemCode) || $itemCode === 'STT' || $itemCode === 'TỔNG CỘNG') continue;

        $rowsProcessed++;
        $codeEsc = $conn->real_escape_string($itemCode);
        // Đọc các giá trị dữ liệu từ file
        $nameVn = ($colNameVn >= 0 && isset($row[$colNameVn])) ? trim((string)$row[$colNameVn]) : '';
        $nameEn = ($colNameEn >= 0 && isset($row[$colNameEn])) ? trim((string)$row[$colNameEn]) : '';
        $groupName = ($colGroup >= 0 && isset($row[$colGroup])) ? trim((string)$row[$colGroup]) : 'Thiết bị';
        $appGroups = ($colAppGroups >= 0 && isset($row[$colAppGroups])) ? trim((string)$row[$colAppGroups]) : '';
        $catType = ($colCat >= 0 && isset($row[$colCat])) ? trim((string)$row[$colCat]) : 'consumable';
        if (!in_array($catType, ['consumable', 'irregular'])) $catType = 'consumable';
        $unit = ($colUnit >= 0 && isset($row[$colUnit])) ? trim((string)$row[$colUnit]) : 'Ea';
        $bin = ($colBin >= 0 && isset($row[$colBin])) ? trim((string)$row[$colBin]) : 'KHO';
        $packSpec = ($colPackSpec >= 0 && isset($row[$colPackSpec])) ? trim((string)$row[$colPackSpec]) : '';
        $packQty = ($colPackQty >= 0 && isset($row[$colPackQty]) && is_numeric(str_replace(',', '', trim((string)$row[$colPackQty])))) ? floatval(str_replace(',', '', trim((string)$row[$colPackQty]))) : 1.0;

        $stockCurrent = ($colStock >= 0 && isset($row[$colStock]) && is_numeric(str_replace(',', '', trim((string)$row[$colStock])))) ? floatval(str_replace(',', '', trim((string)$row[$colStock]))) : 0.0;
        $rop = ($colRop >= 0 && isset($row[$colRop]) && is_numeric(str_replace(',', '', trim((string)$row[$colRop])))) ? floatval(str_replace(',', '', trim((string)$row[$colRop]))) : 0.0;
        $moq = ($colMoq >= 0 && isset($row[$colMoq]) && is_numeric(str_replace(',', '', trim((string)$row[$colMoq])))) ? floatval(str_replace(',', '', trim((string)$row[$colMoq]))) : 0.0;
        $normA = ($colNormA >= 0 && isset($row[$colNormA]) && is_numeric(str_replace(',', '', trim((string)$row[$colNormA])))) ? floatval(str_replace(',', '', trim((string)$row[$colNormA]))) : 1.0;
        $normB = ($colNormB >= 0 && isset($row[$colNormB]) && is_numeric(str_replace(',', '', trim((string)$row[$colNormB])))) ? floatval(str_replace(',', '', trim((string)$row[$colNormB]))) : 1.0;
        $normC = ($colNormC >= 0 && isset($row[$colNormC]) && is_numeric(str_replace(',', '', trim((string)$row[$colNormC])))) ? floatval(str_replace(',', '', trim((string)$row[$colNormC]))) : 1.0;

        if (empty($appGroups) && !empty($groupName)) {
            $appGroups = $groupName;
        }

        // Kiểm tra xem mã vật tư đã có trong hệ thống chưa
        $checkRes = $conn->query("SELECT id FROM warehouse_materials WHERE item_code = '{$codeEsc}' LIMIT 1");
        if ($checkRes && $ex = $checkRes->fetch_assoc()) {
            // ĐÃ TỒN TẠI -> UPDATE (Cập nhật tồn kho và các trường)
            $matId = intval($ex['id']);
            $updates = ["stock_current = {$stockCurrent}"];
            if ($rop !== null && $rop >= 0) $updates[] = "reorder_point = {$rop}";
            if ($moq !== null && $moq >= 0) $updates[] = "reorder_qty = {$moq}";
            if (!empty($bin)) {
                $binEsc = $conn->real_escape_string($bin);
                $updates[] = "bin_location = '{$binEsc}'";
            }
            $upSql = "UPDATE warehouse_materials SET " . implode(', ', $updates) . " WHERE item_code = '{$conn->real_escape_string($itemCode)}'";
            if ($rop >= 0) $updates[] = "reorder_point = {$rop}";
            if ($moq >= 0) $updates[] = "reorder_qty = {$moq}";
            if (!empty($bin)) $updates[] = "bin_location = '{$conn->real_escape_string($bin)}'";
            if (!empty($unit)) $updates[] = "unit = '{$conn->real_escape_string($unit)}'";
            if (!empty($nameVn)) $updates[] = "item_name_vn = '{$conn->real_escape_string($nameVn)}'";
            if (!empty($nameEn)) $updates[] = "item_name_en = '{$conn->real_escape_string($nameEn)}'";
            if (!empty($groupName)) $updates[] = "group_name = '{$conn->real_escape_string($groupName)}'";
            if (!empty($appGroups)) $updates[] = "applicable_groups = '{$conn->real_escape_string($appGroups)}'";
            if (!empty($packSpec)) $updates[] = "packaging_spec = '{$conn->real_escape_string($packSpec)}'";
            if ($packQty > 0) $updates[] = "pack_quantity = {$packQty}";
            if ($normA >= 0) $updates[] = "default_machines = {$normA}";
            if ($normB >= 0) $updates[] = "default_uses_per_machine = {$normB}";
            if ($normC >= 0) $updates[] = "norm_per_use = {$normC}";

            $upSql = "UPDATE warehouse_materials SET " . implode(', ', $updates) . " WHERE id = {$matId}";
            if ($conn->query($upSql)) {
                if ($conn->affected_rows > 0) {
                    $updatedCount++;
                }
                $updatedCount++;
            } else {
                $errors[] = "Lỗi dòng {$i} ({$itemCode}): " . $conn->error;
                $errors[] = "Lỗi cập nhật dòng {$i} ({$itemCode}): " . $conn->error;
            }
        } else {
            // CHƯA CÓ -> INSERT (Tạo mới vật tư theo cơ chế UPSERT)
            $stmt = $conn->prepare("INSERT INTO warehouse_materials (
                item_code, item_name_vn, item_name_en, group_name, applicable_groups, 
                category_type, bin_location, unit, packaging_spec, pack_quantity, 
                stock_initial, stock_current, reorder_point, reorder_qty, 
                default_machines, default_uses_per_machine, norm_per_use, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            $insNameVn = !empty($nameVn) ? $nameVn : $itemCode;
            $insNameEn = $nameEn;
            $stmt->bind_param("sssssssssdddddddd",
                $itemCode,
                $insNameVn,
                $insNameEn,
                $groupName,
                $appGroups,
                $catType,
                $bin,
                $unit,
                $packSpec,
                $packQty,
                $stockCurrent,
                $stockCurrent,
                $rop,
                $moq,
                $normA,
                $normB,
                $normC
            );
            if ($stmt->execute()) {
                $insertedCount++;
            } else {
                $errors[] = "Lỗi thêm mới dòng {$i} ({$itemCode}): " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Tự động quét và cập nhật cảnh báo ROP sau khi import
    checkAndGenerateRopAlerts($conn);

    $msg = "Đã xử lý {$rowsProcessed} dòng dữ liệu: Cập nhật thành công {$updatedCount} mặt hàng, Đăng ký mới {$insertedCount} mặt hàng.";

    return [
        'success'        => true,
        'message'        => $msg,
        'updated_count'  => $updatedCount,
        'inserted_count' => $insertedCount,
        'processed'      => $rowsProcessed,
        'errors'         => $errors
    ];
}

/**
 * Tải file Excel/CSV mẫu chuẩn để Import tồn kho và danh mục vật tư
 */
function downloadMaterialImportTemplate() {
    $filename = "Mau_Import_Ton_Kho_Vat_Tu_" . date('Ymd') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Dòng 1: Tiêu đề các cột dữ liệu chuẩn
    fputcsv($out, [
        'STT', 'Mã Vật Tư (*)', 'Tên Tiếng Việt (*)', 'Tên Tiếng Anh', 
        'Nhóm Chính (*)', 'Các Nhóm Áp Dụng', 'Phân Loại', 'Đơn Vị Tính (*)', 
        'Kệ BIN', 'Quy Cách Đóng Gói', 'SL Quy Đổi/Gói', 
        'Tồn Kho Hiện Tại (*)', 'Điểm Đặt Hàng (ROP)', 'Lượng Đặt Tối Thiểu (MOQ)', 
        'Định Mức Máy (A)', 'Định Mức Lần (B)', 'Tiêu Hao/Lần (C)'
    ]);

    // Các dòng mẫu chuẩn cho nhà máy DX Plastic
    $samples = [
        [1, 'VT-TB-001', 'Dầu bôi trơn máy ép VG68', 'Lubricating Oil VG68', 'Thiết bị', 'Thiết bị,Bảo trì khuôn', 'consumable', 'Thùng', 'K01-A1', '18 lít/thùng', 1, 15.0, 5.0, 10.0, 10, 1, 0.5],
        [2, 'VT-BT-002', 'Băng keo dán khuôn nhiệt 3M', 'Heat Resistant Tape 3M', 'Bảo trì khuôn', 'Bảo trì khuôn,Sản xuất', 'consumable', 'Cuộn', 'K02-B3', '50m/cuộn', 1, 45.0, 20.0, 50.0, 6, 2, 1.0],
        [3, 'VT-SX-003', 'Găng tay sợi phủ hạt nhựa chống trượt', 'Safety Gloves', 'Sản xuất', 'Sản xuất,Nghiền', 'consumable', 'Đôi', 'K03-C1', '12 đôi/bịch', 12, 120.0, 50.0, 100.0, 15, 1, 1.0],
        [4, 'VT-NG-004', 'Lưỡi dao máy băm nghiền hạt nhựa', 'Crusher Blade', 'Nghiền', 'Nghiền', 'irregular', 'Bộ', 'K04-D2', '4 lưỡi/bộ', 1, 8.0, 3.0, 5.0, 2, 1, 1.0],
        [5, 'VT-TB-005', 'Mỡ chịu nhiệt cao áp SKF LGHP 2', 'High Temp Grease SKF', 'Thiết bị', 'Thiết bị', 'consumable', 'Tuýp', 'K01-A4', '420ml/tuýp', 1, 25.0, 10.0, 20.0, 8, 1, 0.2]
    ];

    foreach ($samples as $s) {
        fputcsv($out, $s);
    }

    fclose($out);
    exit;
}

/**
 * Xóa phiếu yêu cầu xuất kho và hoàn trả tồn kho nếu phiếu đã xuất
 */
function deleteWarehouseIssue($conn, $issueId, $currentUser) {
    $issueId = intval($issueId);
    if ($issueId <= 0) {
        return ['success' => false, 'message' => 'ID phiếu không hợp lệ.'];
    }

    $res = $conn->query("SELECT * FROM warehouse_issues WHERE id = {$issueId}");
    $issue = $res ? $res->fetch_assoc() : null;
    if (!$issue) {
        return ['success' => false, 'message' => 'Không tìm thấy phiếu yêu cầu xuất kho #' . $issueId];
    }

    // Kiểm tra quyền xóa: Admin, người có quyền warehouse.manage, hoặc chính người tạo phiếu khi chưa duyệt
    $userRole = $currentUser['role'] ?? 'viewer';
    $userId   = $currentUser['id'] ?? 0;
    $username = $currentUser['username'] ?? '';
    $canDelete = false;

    if ($userRole === 'admin' || hasPermission('warehouse.manage') || hasPermission('admin')) {
        $canDelete = true;
    } elseif ($issue['status'] === 'pending_checker') {
        if ($issue['creator_id'] == $userId || $issue['creator_name'] == ($currentUser['fullname'] ?? '') || $issue['creator_code'] == $username) {
            $canDelete = true;
        }
    }

    if (!$canDelete) {
        return ['success' => false, 'message' => 'Bạn không có quyền xóa phiếu yêu cầu xuất kho này.'];
    }

    $issueCode = $issue['issue_code'];
    $status = $issue['status'];

    $conn->begin_transaction();
    try {
        // Nếu phiếu đã ở bước xuất kho hoặc hoàn tất (đã trừ tồn kho thực tế), hoàn trả số lượng lại cho kho
        if (in_array($status, ['pending_handover', 'completed'])) {
            $itemsRes = $conn->query("SELECT material_id, actual_qty FROM warehouse_issue_items WHERE issue_id = {$issueId}");
            if ($itemsRes) {
                while ($it = $itemsRes->fetch_assoc()) {
                    $mId = intval($it['material_id']);
                    $actQty = floatval($it['actual_qty']);
                    if ($actQty > 0 && $mId > 0) {
                        $conn->query("UPDATE warehouse_materials SET stock_current = stock_current + {$actQty} WHERE id = {$mId}");
                    }
                }
            }
        }

        // Xóa cảnh báo đặt hàng liên quan tới phiếu này
        $conn->query("DELETE FROM warehouse_reorder_alerts WHERE issue_id = {$issueId}");

        // Xóa nhật ký tiến trình (workflow logs)
        $conn->query("DELETE FROM warehouse_workflow_logs WHERE issue_id = {$issueId}");

        // Xóa các dòng mặt hàng trong phiếu
        $conn->query("DELETE FROM warehouse_issue_items WHERE issue_id = {$issueId}");

        // Xóa phiếu chính
        $conn->query("DELETE FROM warehouse_issues WHERE id = {$issueId}");

        $conn->commit();

        // Tự động kiểm tra và đồng bộ lại cảnh báo ROP nếu có hoàn trả số lượng tồn kho
        checkAndGenerateRopAlerts($conn);

        return [
            'success' => true,
            'message' => "Đã xóa thành công phiếu xuất kho [{$issueCode}]."
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Lỗi khi xóa phiếu: ' . $e->getMessage()];
    }
}


