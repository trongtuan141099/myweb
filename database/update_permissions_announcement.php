<?php
/**
 * Script cập nhật phân quyền Announcement vào config/permission.php
 */
$permFile = __DIR__ . '/../config/permission.php';
if (!file_exists($permFile)) {
    die("File permission.php không tồn tại!\n");
}

$config = require $permFile;

// 1. Cập nhật permission_catalog
if (!isset($config['permission_catalog']['announcement'])) {
    $config['permission_catalog']['announcement'] = [
        'name' => 'Thông Báo Nội Bộ (Announcements)',
        'icon' => 'campaign',
        'permissions' => [
            'Announcement.View' => 'Xem danh sách thông báo và chi tiết thông báo',
            'Announcement.Create' => 'Tạo mới thông báo nội bộ',
            'Announcement.Edit' => 'Chỉnh sửa nội dung & đánh dấu thông báo quan trọng',
            'Announcement.Delete' => 'Xóa thông báo khỏi hệ thống',
            'Announcement.Publish' => 'Bật/Tắt xuất bản (Ẩn/Hiện thông báo)',
            'Announcement.ViewStatistics' => 'Xem thống kê số người xem và danh sách người đã xem',
        ]
    ];
    echo "1. Đã thêm nhóm 'announcement' vào permission_catalog\n";
}

// 2. Cập nhật api_catalog
if (!isset($config['api_catalog']['announcement'])) {
    $config['api_catalog']['announcement'] = [
        'name' => 'Thông Báo Nội Bộ API (Announcement API)',
        'icon' => 'campaign',
        'apis' => [
            'api.announcement.list' => [
                'name' => 'Lấy danh sách thông báo công khai cho Dashboard',
                'endpoint' => 'api/announcements.php?action=list',
                'method' => 'GET',
            ],
            'api.announcement.detail' => [
                'name' => 'Xem chi tiết thông báo & tự động ghi nhận lượt xem',
                'endpoint' => 'api/announcements.php?action=get_detail',
                'method' => 'GET',
            ],
            'api.announcement.manage' => [
                'name' => 'Thêm mới, sửa, xóa, ẩn/hiện, ghim thông báo',
                'endpoint' => 'api/announcements.php',
                'method' => 'POST',
            ],
            'api.announcement.statistics' => [
                'name' => 'Xem danh sách người dùng đã xem thông báo',
                'endpoint' => 'api/announcements.php?action=viewers_list',
                'method' => 'GET',
            ],
        ]
    ];
    echo "2. Đã thêm nhóm 'announcement' vào api_catalog\n";
}

// 3. Cập nhật roles_map
$adminPerms = [
    'Announcement.View',
    'Announcement.Create',
    'Announcement.Edit',
    'Announcement.Delete',
    'Announcement.Publish',
    'Announcement.ViewStatistics',
    'api.announcement.list',
    'api.announcement.detail',
    'api.announcement.manage',
    'api.announcement.statistics'
];

$editorPerms = [
    'Announcement.View',
    'Announcement.Create',
    'Announcement.Edit',
    'Announcement.Publish',
    'Announcement.ViewStatistics',
    'api.announcement.list',
    'api.announcement.detail',
    'api.announcement.manage',
    'api.announcement.statistics'
];

$viewerPerms = [
    'Announcement.View',
    'api.announcement.list',
    'api.announcement.detail'
];

foreach (['admin' => $adminPerms, 'editor' => $editorPerms, 'viewer' => $viewerPerms] as $role => $perms) {
    if (isset($config['roles_map'][$role])) {
        foreach ($perms as $p) {
            if (!in_array($p, $config['roles_map'][$role], true)) {
                $config['roles_map'][$role][] = $p;
            }
        }
        echo "3. Đã cập nhật quyền cho role '{$role}'\n";
    }
}

// Ghi lại vào file config/permission.php
$export = "<?php\n// config/permission.php\n// Cấu hình phân quyền hệ thống DX Plastic Group\n\nreturn " . var_export($config, true) . ";\n";

if (file_put_contents($permFile, $export) !== false) {
    echo "-> Cập nhật config/permission.php THÀNH CÔNG!\n";
} else {
    echo "-> LỖI ghi file config/permission.php!\n";
}
