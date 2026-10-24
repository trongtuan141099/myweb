<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.view', 'five_s.audit', 'five_s.settings']);

$action = trim($_POST['action'] ?? $_GET['action'] ?? 'list');
$zoneId = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : (isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0);

// Enforce permission for mutations (Create / Edit / Copy / Delete)
if ($action !== 'list') {
    if (!hasPermission(['five_s.settings', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'code' => 403, 'message' => 'Bạn không có quyền quản lý tiêu chuẩn đánh giá 5S!']);
        exit;
    }
}

try {
    if ($action === 'list') {
        $standards = [];
        if ($zoneId > 0) {
            $stmt = $conn->prepare("SELECT s.*, z.zone_name, z.zone_code 
                                    FROM five_s_standards s
                                    LEFT JOIN five_s_zones z ON s.zone_id = z.id
                                    WHERE s.zone_id = ?
                                    ORDER BY s.group_name ASC, s.sort_order ASC, s.id ASC");
            $stmt->bind_param("i", $zoneId);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query("SELECT s.*, z.zone_name, z.zone_code 
                                 FROM five_s_standards s
                                 LEFT JOIN five_s_zones z ON s.zone_id = z.id
                                 ORDER BY s.zone_id ASC, s.group_name ASC, s.sort_order ASC, s.id ASC");
        }

        while ($row = $res->fetch_assoc()) {
            $standards[] = $row;
        }

        // Group by zone or group_name
        $grouped = [];
        foreach ($standards as $s) {
            $g = $s['group_name'] ?: 'Tiêu chuẩn chung';
            if (!isset($grouped[$g])) {
                $grouped[$g] = [];
            }
            $grouped[$g][] = $s;
        }

        echo json_encode([
            'success' => true,
            'zone_id' => $zoneId,
            'standards' => $standards,
            'tree' => $grouped
        ]);
        exit;
    }

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $zone_id = intval($_POST['zone_id'] ?? 0);
        $group_name = trim($_POST['group_name'] ?? '');
        $standard_code = trim($_POST['standard_code'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $evaluation_criteria = trim($_POST['evaluation_criteria'] ?? '');
        $severity_level = trim($_POST['severity_level'] ?? 'medium');
        $note = trim($_POST['note'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 1);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

        if (empty($item_name)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên tiêu chuẩn']);
            exit;
        }

        if (empty($group_name)) {
            $group_name = 'S1 - Sàng lọc';
        }

        if (empty($standard_code)) {
            $standard_code = 'STD-' . rand(100, 999);
        }

        $uploadDir = __DIR__ . '/../documents/5s_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        function uploadStdImage($field, $dir) {
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Lỗi upload file ' . $field);
            }
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                throw new Exception('Định dạng ảnh không hợp lệ (hỗ trợ JPG, PNG, WEBP)');
            }
            $filename = 'std_' . $field . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename)) {
                throw new Exception('Không thể lưu ảnh tiêu chuẩn');
            }
            return 'documents/5s_images/' . $filename;
        }

        $okImg = uploadStdImage('ok_reference_image', $uploadDir);
        $ngImg = uploadStdImage('ng_reference_image', $uploadDir);

        if ($id > 0) {
            $query = "UPDATE five_s_standards SET 
                        zone_id = ?, group_name = ?, standard_code = ?, item_name = ?, 
                        description = ?, evaluation_criteria = ?, note = ?, 
                        severity_level = ?, sort_order = ?, is_active = ?";
            $params = [$zone_id, $group_name, $standard_code, $item_name, $description, $evaluation_criteria, $note, $severity_level, $sort_order, $is_active];
            $types = "isssssssii";

            if ($okImg !== null) {
                $query .= ", ok_reference_image = ?";
                $params[] = $okImg;
                $types .= "s";
            }
            if ($ngImg !== null) {
                $query .= ", ng_reference_image = ?";
                $params[] = $ngImg;
                $types .= "s";
            }

            $query .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO five_s_standards (
                zone_id, group_name, standard_code, item_name, description, 
                evaluation_criteria, note, severity_level, sort_order, is_active, 
                ok_reference_image, ng_reference_image
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssiiss", $zone_id, $group_name, $standard_code, $item_name, $description, $evaluation_criteria, $note, $severity_level, $sort_order, $is_active, $okImg, $ngImg);
            $stmt->execute();
            $id = $conn->insert_id;
        }

        echo json_encode(['success' => true, 'message' => 'Lưu tiêu chuẩn 5S thành công', 'id' => $id]);
        exit;
    }

    if ($action === 'copy_from_zone') {
        $sourceZoneId = intval($_POST['source_zone_id'] ?? 0);
        $targetZoneId = intval($_POST['target_zone_id'] ?? 0);

        if ($sourceZoneId <= 0 || $targetZoneId <= 0 || $sourceZoneId === $targetZoneId) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn khu vực nguồn và khu vực đích hợp lệ']);
            exit;
        }

        $stmtSrc = $conn->prepare("SELECT * FROM five_s_standards WHERE zone_id = ?");
        $stmtSrc->bind_param("i", $sourceZoneId);
        $stmtSrc->execute();
        $srcStandards = $stmtSrc->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($srcStandards)) {
            echo json_encode(['success' => false, 'message' => 'Khu vực nguồn chưa có tiêu chuẩn nào để sao chép']);
            exit;
        }

        $stmtIns = $conn->prepare("INSERT INTO five_s_standards (
            zone_id, group_name, standard_code, item_name, description, 
            evaluation_criteria, note, severity_level, ok_reference_image, ng_reference_image, sort_order, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $copiedCount = 0;
        foreach ($srcStandards as $std) {
            $stmtIns->bind_param(
                "isssssssssii", 
                $targetZoneId, $std['group_name'], $std['standard_code'], 
                $std['item_name'], $std['description'], $std['evaluation_criteria'], 
                $std['note'], $std['severity_level'], $std['ok_reference_image'], 
                $std['ng_reference_image'], $std['sort_order'], $std['is_active']
            );
            if ($stmtIns->execute()) $copiedCount++;
        }

        echo json_encode(['success' => true, 'message' => "Đã sao chép thành công {$copiedCount} tiêu chuẩn sang khu vực đích."]);
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tiêu chuẩn không hợp lệ']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM five_s_standards WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Đã xóa tiêu chuẩn 5S']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
