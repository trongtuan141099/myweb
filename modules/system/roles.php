<?php
// modules/system/roles.php
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền truy cập trang quản lý Role
if (!hasPermission('role.manage')) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập vào trang Quản lý Phân quyền!</div>';
    return;
}

$permConfig = require __DIR__ . '/../../config/permission.php';
$rolesMap = $permConfig['roles_map'] ?? [];
$permCatalog = $permConfig['permission_catalog'] ?? [];
?>

<style>
/* Module-specific styles for Role & Permission Management */
.role-select-box {
    max-width: 320px;
}

.perm-category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}

.perm-cat-card {
    background: var(--dx-card-bg);
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-md);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.perm-cat-card:hover {
    border-color: var(--dx-border-strong);
    box-shadow: var(--dx-shadow-sm);
}

.perm-cat-header {
    padding: 12px 16px;
    background: var(--dx-bg-subtle);
    border-bottom: 1px solid var(--dx-border);
    font-weight: 700;
    font-size: 13px;
    color: var(--dx-text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}

.perm-cat-body {
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

.perm-item-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13px;
    color: var(--dx-text-main);
    cursor: pointer;
    margin: 0;
    line-height: 1.4;
    transition: color 0.15s ease;
}
.perm-item-label:hover {
    color: var(--dx-primary);
}

.perm-item-label input[type="checkbox"] {
    margin-top: 2px;
    accent-color: var(--dx-primary);
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.perm-code-badge {
    display: inline-block;
    font-family: monospace;
    font-size: 11px;
    color: var(--dx-text-muted);
    background: var(--dx-bg-subtle);
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid var(--dx-border);
    margin-top: 2px;
}
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div class="app-page-title">
            <span class="material-icons text-primary">admin_panel_settings</span>
            <div>
                <h1 style="font-size: 18px; margin: 0;">QUẢN LÝ PHÂN QUYỀN HỆ THỐNG</h1>
                <p class="text-muted small mb-0">Thiết lập quyền truy cập cho từng vai trò người dùng trong nhà máy</p>
            </div>
        </div>
        <div class="app-page-actions">
            <button type="button" class="app-btn app-btn-primary" onclick="saveRolePermissions()">
                <span class="material-icons">save</span> Lưu Cấu Hình Vai Trò
            </button>
        </div>
    </div>

    <!-- Role Selector Card -->
    <div class="app-filter-card mb-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-bold small text-nowrap text-muted mb-0">
                <span class="material-icons fs-6 align-middle text-primary">groups</span> Chọn Vai trò / Nhóm người dùng:
            </label>
            <select id="roleSelect" class="app-form-control role-select-box" onchange="onRoleChange()">
                <option value="admin">Quản trị viên (Admin)</option>
                <option value="editor">Quản lý Xưởng / Giám sát (Editor)</option>
                <option value="viewer">Công nhân / Vận hành (Viewer)</option>
            </select>
            <span class="small text-muted" id="roleSummaryText">Quyền hạn toàn diện trên mọi module và thiết bị.</span>
        </div>
    </div>

    <!-- Permission Matrix -->
    <form id="roleForm" onsubmit="event.preventDefault(); saveRolePermissions();">
        <div class="perm-category-grid">
            <?php foreach ($permCatalog as $groupKey => $group): ?>
                <div class="perm-cat-card">
                    <div class="perm-cat-header">
                        <span class="material-icons text-primary fs-5"><?= htmlspecialchars($group['icon'] ?? 'folder') ?></span>
                        <span><?= htmlspecialchars($group['name']) ?></span>
                    </div>
                    <div class="perm-cat-body">
                        <?php foreach ($group['permissions'] as $code => $label): ?>
                            <label class="perm-item-label">
                                <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($code) ?>" class="perm-checkbox">
                                <div>
                                    <div><?= htmlspecialchars($label) ?></div>
                                    <span class="perm-code-badge"><?= htmlspecialchars($code) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<script>
const ROLES_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;

const ROLE_DESCRIPTIONS = {
    'admin': 'Toàn quyền cấu hình, vận hành, kiểm tra và quản trị tài khoản hệ thống.',
    'editor': 'Có quyền tạo, sửa, duyệt nội dung và giám sát vận hành xưởng.',
    'viewer': 'Chỉ có quyền xem tra cứu dữ liệu, sơ đồ và thực hiện kiểm tra 5S.'
};

document.addEventListener("DOMContentLoaded", () => {
    onRoleChange();
});

function onRoleChange() {
    const selectedRole = document.getElementById("roleSelect").value;
    const descEl = document.getElementById("roleSummaryText");
    if (descEl && ROLE_DESCRIPTIONS[selectedRole]) {
        descEl.textContent = ROLE_DESCRIPTIONS[selectedRole];
    }

    const currentPerms = ROLES_MAP[selectedRole] || [];
    const checkboxes = document.querySelectorAll(".perm-checkbox");

    checkboxes.forEach(cb => {
        cb.checked = currentPerms.includes(cb.value);
    });
}

function saveRolePermissions() {
    const selectedRole = document.getElementById("roleSelect").value;
    const checkedPerms = Array.from(document.querySelectorAll(".perm-checkbox:checked")).map(cb => cb.value);

    // Cập nhật bộ nhớ cục bộ
    ROLES_MAP[selectedRole] = checkedPerms;

    alert(`Đã lưu cấu hình phân quyền cho vai trò [${selectedRole.toUpperCase()}] thành công!\nTổng cộng: ${checkedPerms.length} quyền.`);
}
</script>