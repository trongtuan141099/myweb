<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['five_s.settings', 'api.five_s.save_zone']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$zoneId = intval($_POST['zone_id'] ?? 0);
$zoneCode = trim($_POST['zone_code'] ?? '');
$zoneName = trim($_POST['zone_name'] ?? '');
$qrCode = trim($_POST['qr_code'] ?? '');
$layoutImage = trim($_POST['layout_image'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($zoneCode === '' || $zoneName === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã và tên khu vực']);
    exit;
}

$columns = [];
$result = $conn->query("SHOW COLUMNS FROM five_s_zones");
while ($result && ($column = $result->fetch_assoc())) {
    $columns[$column['Field']] = true;
}
if (!isset($columns['ok_reference_image']) || !isset($columns['ng_reference_image'])) {
    echo json_encode(['success' => false, 'message' => 'Database chưa có cột ảnh mẫu. Hãy chạy migration five_s_workflow.sql']);
    exit;
}

$uploadDir = __DIR__ . '/../documents/5s_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function saveReferenceImage($field, $uploadDir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Không thể tải ảnh lên');
    }
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception('Ảnh mẫu chỉ hỗ trợ JPG, PNG hoặc WEBP');
    }
    $fileName = 'reference_' . $field . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $fileName)) {
        throw new Exception('Không thể lưu ảnh mẫu');
    }
    return 'documents/5s_images/' . $fileName;
}

try {
    $okImage = saveReferenceImage('ok_reference_image', $uploadDir);
    $ngImage = saveReferenceImage('ng_reference_image', $uploadDir);
    if ($zoneId > 0) {
        if ($okImage !== null && $ngImage !== null) {
            $stmt = $conn->prepare("UPDATE five_s_zones SET zone_code = ?, zone_name = ?, qr_code_hash = ?, layout_image = ?, description = ?, ok_reference_image = ?, ng_reference_image = ? WHERE id = ?");
            $stmt->bind_param('sssssssi', $zoneCode, $zoneName, $qrCode, $layoutImage, $description, $okImage, $ngImage, $zoneId);
        } elseif ($okImage !== null) {
            $stmt = $conn->prepare("UPDATE five_s_zones SET zone_code = ?, zone_name = ?, qr_code_hash = ?, layout_image = ?, description = ?, ok_reference_image = ? WHERE id = ?");
            $stmt->bind_param('ssssssi', $zoneCode, $zoneName, $qrCode, $layoutImage, $description, $okImage, $zoneId);
        } elseif ($ngImage !== null) {
            $stmt = $conn->prepare("UPDATE five_s_zones SET zone_code = ?, zone_name = ?, qr_code_hash = ?, layout_image = ?, description = ?, ng_reference_image = ? WHERE id = ?");
            $stmt->bind_param('ssssssi', $zoneCode, $zoneName, $qrCode, $layoutImage, $description, $ngImage, $zoneId);
        } else {
            $stmt = $conn->prepare("UPDATE five_s_zones SET zone_code = ?, zone_name = ?, qr_code_hash = ?, layout_image = ?, description = ? WHERE id = ?");
            $stmt->bind_param('sssssi', $zoneCode, $zoneName, $qrCode, $layoutImage, $description, $zoneId);
        }
    } else {
        if ($okImage === null || $ngImage === null) {
            echo json_encode(['success' => false, 'message' => 'Khu vực mới cần đủ ảnh mẫu OK và NG']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO five_s_zones (zone_code, zone_name, qr_code_hash, layout_image, description, ok_reference_image, ng_reference_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssss', $zoneCode, $zoneName, $qrCode, $layoutImage, $description, $okImage, $ngImage);
    }
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => $zoneId > 0 ? 'Đã cập nhật khu vực' : 'Đã tạo khu vực']);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
