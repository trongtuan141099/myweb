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
$apiCatalog = $permConfig['api_catalog'] ?? [];

// Đếm tổng số quyền UI và API
$totalUiPerms = 0;
foreach ($permCatalog as $grp) {
    $totalUiPerms += count($grp['permissions'] ?? []);
}
$totalApiPerms = 0;
foreach ($apiCatalog as $grp) {
    $totalApiPerms += count($grp['apis'] ?? []);
}
$grandTotal = $totalUiPerms + $totalApiPerms;
?>

<style>
/* CSS Styling cho Module Quản lý Phân quyền */
.roles-container {
    padding: 0;
}

/* Header & Search Bar */
.role-header-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: var(--dx-text-muted);
}
.role-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: var(--dx-bg-subtle);
    border: 1px solid var(--dx-border);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--dx-text-main);
}

/* Filter Card */
.roles-filter-bar {
    background: var(--dx-card-bg);
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-md);
    padding: 14px 18px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: var(--dx-shadow-sm);
}
.role-select-box {
    min-width: 280px;
    font-weight: 600;
}

/* Tabs Navigation */
.perm-nav-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--dx-border);
    margin-bottom: 18px;
    padding-bottom: 0;
}
.perm-tab-btn {
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    color: var(--dx-text-muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    margin-bottom: -2px;
}
.perm-tab-btn:hover {
    color: var(--dx-primary);
    background: var(--dx-bg-subtle);
    border-radius: 6px 6px 0 0;
}
.perm-tab-btn.active {
    color: var(--dx-primary);
    border-bottom-color: var(--dx-primary);
    background: transparent;
}
.perm-tab-badge {
    background: var(--dx-bg-subtle);
    color: var(--dx-text-muted);
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 12px;
    font-weight: 700;
    border: 1px solid var(--dx-border);
}
.perm-tab-btn.active .perm-tab-badge {
    background: rgba(14, 165, 233, 0.15);
    color: var(--dx-primary);
    border-color: rgba(14, 165, 233, 0.3);
}

/* Grid Layout for Cards */
.perm-category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 16px;
}

.perm-cat-card {
    background: var(--dx-card-bg);
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-md);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
}
.perm-cat-card:hover {
    border-color: var(--dx-border-strong);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.perm-cat-header {
    padding: 10px 16px;
    background: var(--dx-bg-subtle);
    border-bottom: 1px solid var(--dx-border);
    font-weight: 700;
    font-size: 13px;
    color: var(--dx-text-main);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.perm-cat-title {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.perm-cat-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}
.perm-quick-btn {
    background: transparent;
    border: 1px solid var(--dx-border);
    color: var(--dx-text-muted);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}
.perm-quick-btn:hover {
    background: var(--dx-primary);
    color: #fff;
    border-color: var(--dx-primary);
}

.perm-cat-body {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

/* Individual Permission Item */
.perm-item-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid transparent;
    transition: background-color 0.15s ease, border-color 0.15s ease;
    cursor: pointer;
}
.perm-item-row:hover {
    background: var(--dx-bg-subtle);
    border-color: var(--dx-border);
}
.perm-item-row input[type="checkbox"] {
    margin-top: 3px;
    accent-color: var(--dx-primary);
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}
.perm-item-info {
    flex: 1;
    min-width: 0;
}
.perm-item-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--dx-text-main);
    line-height: 1.35;
    margin-bottom: 3px;
}
.perm-item-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

/* Badges */
.perm-code-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    color: var(--dx-text-muted);
    background: var(--dx-bg-subtle);
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid var(--dx-border);
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.method-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    display: inline-block;
    flex-shrink: 0;
}
.method-badge-get {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
.method-badge-post {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.35);
}
.endpoint-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    color: var(--dx-text-muted);
    opacity: 0.85;
}

/* Search bar inside header */
.perm-search-box {
    position: relative;
    max-width: 320px;
    width: 100%;
}
.perm-search-box input {
    padding-left: 32px;
    height: 36px;
    font-size: 13px;
}
.perm-search-box .material-icons {
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: var(--dx-text-muted);
    pointer-events: none;
}

/* Notification Toast */
.roles-alert {
    position: fixed;
    top: 20px;
    right: 24px;
    z-index: 9999;
    min-width: 320px;
    max-width: 480px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    display: none;
}
</style>

<div class="app-page-wrapper roles-container">
    <!-- Success/Error Alert Toast -->
    <div id="rolesAlert" class="alert alert-success alert-dismissible fade show roles-alert" role="alert">
        <span id="rolesAlertText">Đã lưu cấu hình thành công</span>
        <button type="button" class="btn-close" onclick="hideRolesAlert()"></button>
    </div>

    <!-- Header Section -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">admin_panel_settings</span>
                Quản Lý Phân Quyền Hệ Thống
            </h1>
            <p class="app-page-subtitle">Thiết lập quyền truy cập giao diện & toàn bộ API endpoints cho từng vai trò người dùng trong nhà máy</p>
        </div>
        <div class="app-page-actions">
            <!-- Search Filter Box -->
            <div class="perm-search-box">
                <span class="material-icons">search</span>
                <input type="text" id="permSearchInput" class="app-form-control" placeholder="Tìm quyền, mã, endpoint API..." oninput="filterPermissions(this.value)">
            </div>
            <!-- Global Save Button -->
            <button type="button" id="saveRoleBtn" class="app-btn app-btn-primary" onclick="saveRolePermissions()">
                <span class="material-icons">save</span>
                <span id="saveRoleBtnText">Lưu Cấu Hình Vai Trò</span>
            </button>
        </div>
    </div>

    <!-- Role Selector & Stats Bar -->
    <div class="roles-filter-bar">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-bold small text-nowrap text-muted mb-0 d-flex align-items-center gap-1">
                <span class="material-icons fs-6 text-primary">groups</span> Vai trò người dùng:
            </label>
            <select id="roleSelect" class="app-form-control role-select-box" onchange="onRoleChange()">
                <option value="admin">Quản trị viên toàn quyền (Admin)</option>
                <option value="editor">Quản lý Xưởng / Giám sát (Editor)</option>
                <option value="viewer">Công nhân / Vận hành viên (Viewer)</option>
            </select>
            <span class="small text-muted" id="roleSummaryText" style="max-width: 450px;">Quyền hạn toàn diện trên mọi module và thiết bị.</span>
        </div>
        
        <div class="role-header-stats">
            <span class="role-stat-chip">
                <span class="material-icons fs-6 text-primary">checklist</span>
                Tổng quyền: <strong id="totalSelectedPermCount" class="text-primary">0</strong> / <?= $grandTotal ?>
            </span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="checkAllCurrentTab(true)" title="Chọn tất cả quyền trong tab hiện hành">
                    <span class="material-icons fs-6 align-middle">done_all</span> Chọn hết
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="checkAllCurrentTab(false)" title="Bỏ chọn tất cả quyền trong tab hiện hành">
                    <span class="material-icons fs-6 align-middle">remove_done</span> Bỏ hết
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (UI Modules vs API Services) -->
    <div class="perm-nav-tabs">
        <button type="button" class="perm-tab-btn active" id="tabBtnUi" onclick="switchPermTab('ui')">
            <span class="material-icons fs-5">web</span>
            <span>Quyền Giao Diện (UI Modules)</span>
            <span class="perm-tab-badge" id="badgeUiCount">0 / <?= $totalUiPerms ?></span>
        </button>
        <button type="button" class="perm-tab-btn" id="tabBtnApi" onclick="switchPermTab('api')">
            <span class="material-icons fs-5">api</span>
            <span>Quyền Toàn Bộ API Hệ Thống (API Services)</span>
            <span class="perm-tab-badge" id="badgeApiCount">0 / <?= $totalApiPerms ?></span>
        </button>
    </div>

    <!-- Main Content Form -->
    <form id="roleForm" onsubmit="event.preventDefault(); saveRolePermissions();">
        <!-- TAB 1: UI Permissions -->
        <div id="tabContentUi" class="perm-tab-pane">
            <div class="perm-category-grid">
                <?php foreach ($permCatalog as $groupKey => $group): ?>
                    <div class="perm-cat-card" data-cat="<?= htmlspecialchars($groupKey) ?>">
                        <div class="perm-cat-header">
                            <div class="perm-cat-title">
                                <span class="material-icons text-primary fs-5"><?= htmlspecialchars($group['icon'] ?? 'folder') ?></span>
                                <span><?= htmlspecialchars($group['name']) ?></span>
                            </div>
                            <div class="perm-cat-actions">
                                <button type="button" class="perm-quick-btn" onclick="toggleCardPerms(this, true)" title="Chọn tất cả mục này">Chọn hết</button>
                                <button type="button" class="perm-quick-btn" onclick="toggleCardPerms(this, false)" title="Bỏ chọn mục này">Bỏ</button>
                            </div>
                        </div>
                        <div class="perm-cat-body">
                            <?php foreach ($group['permissions'] as $code => $label): ?>
                                <label class="perm-item-row" data-search="<?= strtolower(htmlspecialchars($code . ' ' . $label)) ?>">
                                    <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($code) ?>" class="perm-checkbox perm-checkbox-ui" onchange="updatePermCounters()">
                                    <div class="perm-item-info">
                                        <div class="perm-item-name"><?= htmlspecialchars($label) ?></div>
                                        <div class="perm-item-meta">
                                            <span class="perm-code-badge"><?= htmlspecialchars($code) ?></span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 2: API Permissions -->
        <div id="tabContentApi" class="perm-tab-pane" style="display: none;">
            <div class="perm-category-grid">
                <?php foreach ($apiCatalog as $groupKey => $group): ?>
                    <div class="perm-cat-card" data-cat="<?= htmlspecialchars($groupKey) ?>">
                        <div class="perm-cat-header">
                            <div class="perm-cat-title">
                                <span class="material-icons text-primary fs-5"><?= htmlspecialchars($group['icon'] ?? 'api') ?></span>
                                <span><?= htmlspecialchars($group['name']) ?></span>
                            </div>
                            <div class="perm-cat-actions">
                                <button type="button" class="perm-quick-btn" onclick="toggleCardPerms(this, true)" title="Chọn tất cả API nhóm này">Chọn hết</button>
                                <button type="button" class="perm-quick-btn" onclick="toggleCardPerms(this, false)" title="Bỏ chọn API nhóm này">Bỏ</button>
                            </div>
                        </div>
                        <div class="perm-cat-body">
                            <?php foreach ($group['apis'] as $code => $apiInfo): 
                                $method = strtoupper($apiInfo['method'] ?? 'GET');
                                $methodClass = ($method === 'POST') ? 'method-badge-post' : 'method-badge-get';
                            ?>
                                <label class="perm-item-row" data-search="<?= strtolower(htmlspecialchars($code . ' ' . $apiInfo['name'] . ' ' . $apiInfo['endpoint'])) ?>">
                                    <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($code) ?>" class="perm-checkbox perm-checkbox-api" onchange="updatePermCounters()">
                                    <div class="perm-item-info">
                                        <div class="perm-item-name"><?= htmlspecialchars($apiInfo['name']) ?></div>
                                        <div class="perm-item-meta">
                                            <span class="method-badge <?= $methodClass ?>"><?= $method ?></span>
                                            <span class="endpoint-badge"><?= htmlspecialchars($apiInfo['endpoint']) ?></span>
                                            <span class="perm-code-badge"><?= htmlspecialchars($code) ?></span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</div>

<script>
const ROLES_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;
const TOTAL_UI_PERMS = <?= (int)$totalUiPerms ?>;
const TOTAL_API_PERMS = <?= (int)$totalApiPerms ?>;
let CURRENT_TAB = 'ui';

const ROLE_DESCRIPTIONS = {
    'admin': 'Toàn quyền cấu hình, vận hành, kiểm tra, quản trị tài khoản và truy xuất toàn bộ API hệ thống.',
    'editor': 'Có quyền tạo, sửa, duyệt nội dung vận hành xưởng và gọi các API chức năng sản xuất/kiểm kê.',
    'viewer': 'Chỉ có quyền xem tra cứu dữ liệu, kiểm tra 5S và thực hiện các API dạng GET/Read-only.'
};

document.addEventListener("DOMContentLoaded", () => {
    onRoleChange();
});

// Chuyển tab giữa UI Modules và API Services
function switchPermTab(tab) {
    CURRENT_TAB = tab;
    const btnUi = document.getElementById("tabBtnUi");
    const btnApi = document.getElementById("tabBtnApi");
    const paneUi = document.getElementById("tabContentUi");
    const paneApi = document.getElementById("tabContentApi");

    if (tab === 'ui') {
        btnUi.classList.add("active");
        btnApi.classList.remove("active");
        paneUi.style.display = "block";
        paneApi.style.display = "none";
    } else {
        btnApi.classList.add("active");
        btnUi.classList.remove("active");
        paneApi.style.display = "block";
        paneUi.style.display = "none";
    }

    // Re-apply filter if search box has value
    const searchVal = document.getElementById("permSearchInput").value;
    if (searchVal) {
        filterPermissions(searchVal);
    }
}

// Khi thay đổi vai trò trong dropdown
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

    updatePermCounters();
}

// Cập nhật số lượng quyền đã chọn trên các badge và stats chip
function updatePermCounters() {
    const uiChecked = document.querySelectorAll(".perm-checkbox-ui:checked").length;
    const apiChecked = document.querySelectorAll(".perm-checkbox-api:checked").length;
    const totalChecked = uiChecked + apiChecked;

    document.getElementById("totalSelectedPermCount").textContent = totalChecked;
    document.getElementById("badgeUiCount").textContent = `${uiChecked} / ${TOTAL_UI_PERMS}`;
    document.getElementById("badgeApiCount").textContent = `${apiChecked} / ${TOTAL_API_PERMS}`;
}

// Chọn hoặc bỏ chọn tất cả các quyền trong 1 thẻ phân hệ
function toggleCardPerms(btn, check) {
    const card = btn.closest(".perm-cat-card");
    if (!card) return;
    const checkboxes = card.querySelectorAll(".perm-checkbox");
    checkboxes.forEach(cb => {
        if (cb.closest(".perm-item-row").style.display !== "none") {
            cb.checked = check;
        }
    });
    updatePermCounters();
}

// Chọn hoặc bỏ chọn tất cả quyền trong tab hiện tại
function checkAllCurrentTab(check) {
    const activePane = CURRENT_TAB === 'ui' ? document.getElementById("tabContentUi") : document.getElementById("tabContentApi");
    if (!activePane) return;
    const checkboxes = activePane.querySelectorAll(".perm-checkbox");
    checkboxes.forEach(cb => {
        if (cb.closest(".perm-item-row").style.display !== "none") {
            cb.checked = check;
        }
    });
    updatePermCounters();
}

// Tìm kiếm lọc real-time trên cả UI và API
function filterPermissions(query) {
    const q = (query || "").trim().toLowerCase();
    const rows = document.querySelectorAll(".perm-item-row");

    rows.forEach(row => {
        const text = row.getAttribute("data-search") || "";
        if (!q || text.includes(q)) {
            row.style.display = "flex";
        } else {
            row.style.display = "none";
        }
    });

    // Ẩn các thẻ phân hệ nếu không có quyền nào khớp tìm kiếm
    const cards = document.querySelectorAll(".perm-cat-card");
    cards.forEach(card => {
        const visibleRows = card.querySelectorAll(".perm-item-row:not([style*='display: none'])");
        if (visibleRows.length === 0 && q) {
            card.style.display = "none";
        } else {
            card.style.display = "flex";
        }
    });
}

// Lưu cấu hình phân quyền vào Backend qua API save_role_permissions.php
async function saveRolePermissions() {
    const selectedRole = document.getElementById("roleSelect").value;
    const checkedPerms = Array.from(document.querySelectorAll(".perm-checkbox:checked")).map(cb => cb.value);
    const saveBtn = document.getElementById("saveRoleBtn");
    const saveBtnText = document.getElementById("saveRoleBtnText");

    // UI Loading state
    saveBtn.disabled = true;
    saveBtnText.textContent = "Đang lưu cấu hình...";

    try {
        const response = await fetch("api/save_role_permissions.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                role: selectedRole,
                permissions: checkedPerms
            })
        });

        const data = await response.json();

        if (data.success) {
            // Cập nhật bộ nhớ cục bộ
            ROLES_MAP[selectedRole] = checkedPerms;
            showRolesAlert(`Đã lưu cấu hình phân quyền cho vai trò [${selectedRole.toUpperCase()}] thành công!\n(Tổng cộng: ${checkedPerms.length} quyền)`, "success");
        } else {
            showRolesAlert(data.message || "Lỗi khi lưu phân quyền!", "danger");
        }
    } catch (err) {
        console.error("Save role error:", err);
        showRolesAlert("Lỗi kết nối máy chủ khi lưu phân quyền!", "danger");
    } finally {
        saveBtn.disabled = false;
        saveBtnText.textContent = "Lưu Cấu Hình Vai Trò";
    }
}

// Hiển thị Alert Thông báo Toast
function showRolesAlert(msg, type = "success") {
    const alertEl = document.getElementById("rolesAlert");
    const textEl = document.getElementById("rolesAlertText");
    if (!alertEl || !textEl) {
        alert(msg);
        return;
    }
    alertEl.className = `alert alert-${type} alert-dismissible fade show roles-alert`;
    textEl.innerHTML = msg.replace(/\n/g, "<br>");
    alertEl.style.display = "block";

    setTimeout(() => {
        hideRolesAlert();
    }, 4500);
}

function hideRolesAlert() {
    const alertEl = document.getElementById("rolesAlert");
    if (alertEl) alertEl.style.display = "none";
}
</script>