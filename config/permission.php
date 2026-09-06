
<?php
// config/permissions.php
return [
    // Định nghĩa danh sách quyền tương ứng từng Role
    'roles_map' => [
        'admin'  => ['document.view', 'document.upload', 'document.edit', 'document.delete', 'device.view', 'device.edit', 'user.view'],
        'editor' => ['document.view', 'document.upload', 'document.edit', 'device.view'],
        'viewer' => ['document.view', 'device.view']
    ]
];
?>