<?php
/**
 * API Lưu cấu hình phân quyền vai trò (Save Role Permissions API)
 * Phân hệ: Quản trị hệ thống - DX Plastic Group
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../core/check_permission.php';

// Kiểm tra phiên đăng nhập
checkAuth();

// Kiểm tra quyền quản trị
if (!hasPermission('role.manage') && ($_SESSION['user']['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác quản lý phân quyền!']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$role = trim($input['role'] ?? '');
$permissions = $input['permissions'] ?? [];

if (!in_array($role, ['admin', 'editor', 'viewer'])) {
    echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ: ' . htmlspecialchars($role)]);
    exit;
}

if (!is_array($permissions)) {
    echo json_encode(['success' => false, 'message' => 'Danh sách quyền không hợp lệ']);
    exit;
}

// Làm sạch danh sách quyền
$cleanPermissions = array_values(array_unique(array_filter(array_map('trim', $permissions))));

// Đọc file cấu hình hiện tại
$configFile = __DIR__ . '/../config/permission.php';
$config = require $configFile;

// Cập nhật mảng roles_map cho role tương ứng
$config['roles_map'][$role] = $cleanPermissions;

// Ghi lại vào file config/permission.php
$phpContent = "<?php\n// config/permission.php\n// Cấu hình phân quyền hệ thống DX Plastic Group\n\nreturn " . var_export($config, true) . ";\n";

if (file_put_contents($configFile, $phpContent, LOCK_EX) === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể ghi dữ liệu vào file cấu hình config/permission.php']);
    exit;
}

// Nếu người dùng hiện tại có vai trò vừa sửa, đồng bộ ngay Session
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role) {
    $_SESSION['user']['permissions'] = $cleanPermissions;
}

echo json_encode([
    'success' => true,
    'message' => "Đã cập nhật phân quyền cho vai trò [{$role}] thành công với " . count($cleanPermissions) . " quyền!",
    'role' => $role,
    'permissions_count' => count($cleanPermissions)
], JSON_UNESCAPED_UNICODE);

