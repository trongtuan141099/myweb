<?php
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền truy cập trang quản lý Role
if (!hasPermission('role.manage')) {
    die("Bạn không có quyền truy cập vào trang Quản lý Phân quyền!");
}

$allPermissions = require __DIR__ . '/../../config/permissions.php';
?>

<div class="role-management-wrapper">
    <h2>QUẢN LÝ PHÂN QUYỀN HỆ THỐNG</h2>
    <form id="roleForm">
        <label>Chọn Vai trò / Nhóm người dùng:</label>
        <select id="roleSelect">
            <option value="admin">Quản trị viên (Admin)</option>
            <option value="editor">Quản lý Xưởng (Editor)</option>
            <option value="viewer">Công nhân (Viewer)</option>
        </select>

        <div class="permission-matrix">
            <?php foreach ($allPermissions as $groupKey => $group): ?>
                <fieldset style="border: 1px solid #ccc; padding: 10px; margin-top: 15px;">
                    <legend><strong><?= $group['name'] ?></strong></legend>
                    <?php foreach ($group['permissions'] as $code => $label): ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="permissions[]" value="<?= $code ?>">
                            <?= $label ?> (<code><?= $code ?></code>)
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
        </div>

        <button type="submit" style="margin-top: 15px; padding: 8px 16px;">Lưu Cấu Hình Phân Quyền</button>
    </form>
</div>