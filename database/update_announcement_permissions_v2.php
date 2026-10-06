<?php
/**
 * Cập nhật các quyền upload ảnh và file cho Announcement vào config/permission.php
 */
$permFile = __DIR__ . '/../config/permission.php';
if (!file_exists($permFile)) {
    die("File permission.php không tồn tại!\n");
}

$config = require $permFile;

// 1. Cập nhật permission_catalog
$newPermissions = [
    'Announcement.View'             => 'Xem danh sách thông báo và chi tiết thông báo',
    'Announcement.Create'           => 'Tạo mới thông báo nội bộ',
    'Announcement.Edit'             => 'Chỉnh sửa nội dung & đánh dấu thông báo quan trọng',
    'Announcement.Delete'           => 'Xóa thông báo khỏi hệ thống',
    'Announcement.Publish'          => 'Bật/Tắt xuất bản (Ẩn/Hiện thông báo)',
    'Announcement.ViewStatistics'   => 'Xem thống kê số người xem và danh sách người đã xem',
    'Announcement.UploadImage'      => 'Tải lên nhiều hình ảnh cho thông báo',
    'Announcement.DeleteImage'      => 'Xóa hoặc sắp xếp thứ tự hình ảnh thông báo',
    'Announcement.UploadAttachment' => 'Đính kèm tài liệu PDF, Word, Excel, PowerPoint',
    'Announcement.DeleteAttachment' => 'Xóa hoặc thay thế tài liệu đính kèm',
];

$config['permission_catalog']['announcement'] = [
    'name' => 'Thông Báo Nội Bộ (Announcements)',
    'icon' => 'campaign',
    'permissions' => $newPermissions
];

// 2. Cập nhật roles_map
$allAnnPerms = array_keys($newPermissions);

// admin & editor có toàn quyền
foreach (['admin', 'editor'] as $role) {
    if (isset($config['roles_map'][$role])) {
        foreach ($allAnnPerms as $p) {
            if (!in_array($p, $config['roles_map'][$role], true)) {
                $config['roles_map'][$role][] = $p;
            }
        }
        echo "Đã cấp toàn quyền Announcement cho role '{$role}'\n";
    }
}

// viewer có Announcement.View
if (isset($config['roles_map']['viewer'])) {
    if (!in_array('Announcement.View', $config['roles_map']['viewer'], true)) {
        $config['roles_map']['viewer'][] = 'Announcement.View';
    }
    echo "Đã kiểm tra quyền Announcement.View cho role 'viewer'\n";
}

$export = "<?php\n// config/permission.php\n// Cấu hình phân quyền hệ thống DX Plastic Group\n\nreturn " . var_export($config, true) . ";\n";

if (file_put_contents($permFile, $export) !== false) {
    echo "-> Cập nhật config/permission.php THÀNH CÔNG!\n";
} else {
    echo "-> LỖI ghi file config/permission.php!\n";
}

