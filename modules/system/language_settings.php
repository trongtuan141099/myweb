<?php
// modules/system/language_settings.php
// Quản trị Thiết lập Ngôn ngữ (Language Settings) - DX Plastic Group
require_once __DIR__ . '/../../core/check_permission.php';
require_once __DIR__ . '/../../core/i18n.php';

// Kiểm tra quyền quản trị phân hệ
if (!hasPermission('role.manage') && !hasPermission('system.language')) {
    echo '<div class="alert alert-danger m-3">' . __('app.forbidden_desc', 'Bạn không có quyền truy cập vào trang Cài đặt ngôn ngữ!') . '</div>';
    return;
}

$page_title = __('lang_settings.title', 'Thiết lập ngôn ngữ hệ thống');
?>

<style>
/* CSS RIÊNG CỦA MODULE CÀI ĐẶT NGÔN NGỮ (INDUSTRIAL DESIGN) */
.lang-settings-container {
    padding: 0;
}

/* Stat Cards */
.lang-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.lang-stat-card {
    background: var(--dx-card-bg, #ffffff);
    border: 1px solid var(--dx-border, #e2e8f0);
    border-radius: var(--dx-radius-md, 8px);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.lang-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.lang-stat-info {
    flex: 1;
    min-width: 0;
}
.lang-stat-label {
    font-size: 11.5px;
    color: var(--dx-text-muted, #64748b);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.lang-stat-val {
    font-size: 20px;
    font-weight: 700;
    color: var(--dx-text-main, #1e293b);
    line-height: 1.2;
}
.lang-stat-sub {
    font-size: 11px;
    color: var(--dx-text-muted, #64748b);
    margin-top: 2px;
}

/* Filter Card */
.lang-filter-bar {
    background: var(--dx-card-bg, #ffffff);
    border: 1px solid var(--dx-border, #e2e8f0);
    border-radius: var(--dx-radius-md, 8px);
    padding: 14px 18px;
    margin-bottom: 16px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.lang-filter-left {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    flex: 1;
}
.lang-filter-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Table Style */
.lang-table-card {
    background: var(--dx-card-bg, #ffffff);
    border: 1px solid var(--dx-border, #e2e8f0);
    border-radius: var(--dx-radius-md, 8px);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.lang-table-wrap {
    overflow-x: auto;
    max-height: calc(100vh - 380px);
    min-height: 350px;
}
.lang-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.lang-table th {
    background: var(--dx-bg-subtle, #f8fafc);
    color: var(--dx-text-muted, #475569);
    font-weight: 600;
    padding: 10px 12px;
    border-bottom: 1px solid var(--dx-border, #e2e8f0);
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: left;
    white-space: nowrap;
}
.lang-table td {
    padding: 8px 12px;
    border-bottom: 1px solid var(--dx-border, #e2e8f0);
    color: var(--dx-text-main, #1e293b);
    vertical-align: middle;
}
.lang-table tr:hover td {
    background: var(--dx-bg-hover, #f1f5f9);
}
.lang-key-badge {
    font-family: monospace;
    font-size: 11.5px;
    padding: 3px 6px;
    background: var(--dx-bg-subtle, #f1f5f9);
    border-radius: 4px;
    border: 1px solid var(--dx-border, #e2e8f0);
    color: var(--dx-primary, #0ea5e9);
    font-weight: 600;
    display: inline-block;
    word-break: break-all;
}
.lang-group-tag {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    background: rgba(14, 165, 233, 0.1);
    color: var(--dx-primary, #0ea5e9);
    text-transform: uppercase;
}
.lang-cell-input {
    width: 100%;
    min-width: 150px;
    border: 1px solid transparent;
    background: transparent;
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 12.5px;
    color: var(--dx-text-main, #1e293b);
    transition: all 0.2s ease;
}
.lang-cell-input:focus {
    background: var(--dx-card-bg, #ffffff);
    border-color: var(--dx-primary, #0ea5e9);
    outline: none;
    box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
}
.lang-cell-input.is-modified {
    border-color: #f59e0b !important;
    background: rgba(245, 158, 11, 0.05) !important;
}
</style>

<div class="app-page-wrapper lang-settings-container">
    <!-- TIÊU ĐỀ TRANG -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title" data-i18n="lang_settings.title">
                <span class="material-icons" style="color: var(--dx-primary);">translate</span>
                <?= __('lang_settings.title', 'Thiết lập ngôn ngữ hệ thống') ?>
            </h1>
            <p class="app-page-subtitle" data-i18n="lang_settings.subtitle">
                <?= __('lang_settings.subtitle', 'Quản lý từ điển đa ngôn ngữ (Việt - Anh - Nhật), thêm mới hoặc tùy biến từ khóa hiển thị trên toàn hệ thống') ?>
            </p>
        </div>

        <div class="app-page-actions">
            <button type="button" class="app-btn app-btn-secondary" onclick="exportDictionaryJson()">
                <span class="material-icons">download</span>
                <span data-i18n="lang_settings.export_json"><?= __('lang_settings.export_json', 'Xuất tệp JSON') ?></span>
            </button>
            <button type="button" class="app-btn app-btn-secondary" onclick="resetDefaultDictionaries()">
                <span class="material-icons">restart_alt</span>
                <span data-i18n="lang_settings.reload_dict"><?= __('lang_settings.reload_dict', 'Tải lại mặc định') ?></span>
            </button>
            <button type="button" class="app-btn app-btn-primary" onclick="openAddKeyModal()">
                <span class="material-icons">add</span>
                <span data-i18n="lang_settings.add_key"><?= __('lang_settings.add_key', 'Thêm từ khóa mới') ?></span>
            </button>
            <button type="button" class="app-btn app-btn-success" id="btnBatchSave" onclick="saveAllModifiedKeys()" disabled>
                <span class="material-icons">save</span>
                <span data-i18n="lang_settings.save_all"><?= __('lang_settings.save_all', 'Lưu toàn bộ thay đổi') ?></span>
                <span class="badge bg-white text-success ms-1 d-none" id="modifiedCounter">0</span>
            </button>
        </div>
    </div>

    <!-- KHỐI THỐNG KÊ TỔNG QUAN -->
    <div class="lang-stat-grid">
        <!-- 1. Tổng khóa dịch -->
        <div class="lang-stat-card">
            <div class="lang-stat-icon" style="background: rgba(14, 165, 233, 0.12); color: var(--dx-primary);">
                <span class="material-icons">key</span>
            </div>
            <div class="lang-stat-info">
                <div class="lang-stat-label" data-i18n="lang_settings.total_keys"><?= __('lang_settings.total_keys', 'Tổng số khóa dịch') ?></div>
                <div class="lang-stat-val" id="statTotalKeys">0</div>
                <div class="lang-stat-sub">3 ngôn ngữ (VI, EN, JA)</div>
            </div>
        </div>

        <!-- 2. Tiếng Việt -->
        <div class="lang-stat-card">
            <div class="lang-stat-icon" style="background: rgba(239, 68, 68, 0.12);">
                <span>🇻🇳</span>
            </div>
            <div class="lang-stat-info">
                <div class="lang-stat-label" data-i18n="lang_settings.vi_keys"><?= __('lang_settings.vi_keys', 'Tiếng Việt (VI)') ?></div>
                <div class="lang-stat-val" id="statViCount">0</div>
                <div class="lang-stat-sub"><span id="statViPercent">100</span>% hoàn thiện</div>
            </div>
        </div>

        <!-- 3. Tiếng Anh -->
        <div class="lang-stat-card">
            <div class="lang-stat-icon" style="background: rgba(59, 130, 246, 0.12);">
                <span>🇬🇧</span>
            </div>
            <div class="lang-stat-info">
                <div class="lang-stat-label" data-i18n="lang_settings.en_keys"><?= __('lang_settings.en_keys', 'Tiếng Anh (EN)') ?></div>
                <div class="lang-stat-val" id="statEnCount">0</div>
                <div class="lang-stat-sub"><span id="statEnPercent">100</span>% hoàn thiện</div>
            </div>
        </div>

        <!-- 4. Tiếng Nhật -->
        <div class="lang-stat-card">
            <div class="lang-stat-icon" style="background: rgba(16, 185, 129, 0.12);">
                <span>🇯🇵</span>
            </div>
            <div class="lang-stat-info">
                <div class="lang-stat-label" data-i18n="lang_settings.ja_keys"><?= __('lang_settings.ja_keys', 'Tiếng Nhật (JA)') ?></div>
                <div class="lang-stat-val" id="statJaCount">0</div>
                <div class="lang-stat-sub"><span id="statJaPercent">100</span>% hoàn thiện</div>
            </div>
        </div>
    </div>

    <!-- THANH TÌM KIẾM & BỘ LỌC -->
    <div class="lang-filter-bar">
        <div class="lang-filter-left">
            <div style="position: relative; width: 320px; max-width: 100%;">
                <input type="text" id="langSearchInput" class="app-form-control" style="padding-left: 36px;" placeholder="<?= __('lang_settings.search_key', 'Tìm khóa dịch hoặc nội dung...') ?>" oninput="onFilterChange()">
                <span class="material-icons" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--dx-text-muted);">search</span>
            </div>

            <select id="langGroupFilter" class="app-form-select" style="min-width: 160px;" onchange="onFilterChange()">
                <option value="" data-i18n="lang_settings.filter_all_groups"><?= __('lang_settings.filter_all_groups', 'Tất cả các nhóm') ?></option>
            </select>

            <select id="langMissingFilter" class="app-form-select" style="min-width: 170px;" onchange="onFilterChange()">
                <option value="all">Tất cả trạng thái</option>
                <option value="missing_en">Còn thiếu tiếng Anh (EN)</option>
                <option value="missing_ja">Còn thiếu tiếng Nhật (JA)</option>
            </select>
        </div>

        <div class="lang-filter-right">
            <button type="button" class="app-btn app-btn-secondary app-btn-sm" onclick="resetFilters()">
                <span class="material-icons" style="font-size: 16px;">filter_alt_off</span>
                <span>Đặt lại lọc</span>
            </button>
            <div class="small text-muted" id="filterResultCount">0 khóa</div>
        </div>
    </div>

    <!-- BẢNG TỪ ĐIỂN TƯƠNG TÁC -->
    <div class="lang-table-card">
        <div class="lang-table-wrap">
            <table class="lang-table" id="langTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;" data-i18n="common.table_stt"><?= __('common.table_stt', 'STT') ?></th>
                        <th style="width: 110px;" data-i18n="lang_settings.table_group"><?= __('lang_settings.table_group', 'Nhóm') ?></th>
                        <th style="width: 220px;" data-i18n="lang_settings.table_key"><?= __('lang_settings.table_key', 'Khóa dịch (Key)') ?></th>
                        <th style="width: 28%;" data-i18n="lang_settings.table_vi"><?= __('lang_settings.table_vi', 'Tiếng Việt 🇻🇳') ?></th>
                        <th style="width: 28%;" data-i18n="lang_settings.table_en"><?= __('lang_settings.table_en', 'Tiếng Anh 🇬🇧') ?></th>
                        <th style="width: 28%;" data-i18n="lang_settings.table_ja"><?= __('lang_settings.table_ja', 'Tiếng Nhật 🇯🇵') ?></th>
                        <th style="width: 80px; text-align: center;" data-i18n="common.table_actions"><?= __('common.table_actions', 'Thao tác') ?></th>
                    </tr>
                </thead>
                <tbody id="langTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <span class="material-icons" style="font-size: 32px; vertical-align: middle; margin-right: 6px;">hourglass_empty</span>
                            <span><?= __('common.loading', 'Đang tải dữ liệu...') ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Phân trang -->
        <div id="langPagination" style="padding: 12px 18px; border-top: 1px solid var(--dx-border);"></div>
    </div>
</div>

<!-- MODAL THÊM TỪ KHÓA MỚI -->
<div class="modal fade" id="modalAddKey" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons text-primary" style="font-size: 20px;">add_circle</span>
                    <span data-i18n="lang_settings.modal_add_title"><?= __('lang_settings.modal_add_title', 'Thêm Khóa Dịch Mới') ?></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAddKey" onsubmit="event.preventDefault(); submitNewKey();">
                    <div class="mb-3">
                        <label class="app-form-label required" data-i18n="lang_settings.key_name_label"><?= __('lang_settings.key_name_label', 'Tên khóa (Key Name)') ?></label>
                        <input type="text" id="newKeyName" class="app-form-control" placeholder="<?= __('lang_settings.key_placeholder', 'VD: common.my_button') ?>" required pattern="[a-zA-Z0-9_\.]+">
                        <small class="text-muted">Định dạng khuyên dùng: <code>nhóm.tên_khóa</code> (Ví dụ: <code>production.target_rate</code>)</small>
                    </div>

                    <div class="mb-3">
                        <label class="app-form-label required" data-i18n="lang_settings.table_vi"><?= __('lang_settings.table_vi', 'Tiếng Việt 🇻🇳') ?></label>
                        <textarea id="newKeyVi" class="app-form-control" rows="2" placeholder="Nội dung dịch tiếng Việt..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="app-form-label" data-i18n="lang_settings.table_en"><?= __('lang_settings.table_en', 'Tiếng Anh 🇬🇧') ?></label>
                        <textarea id="newKeyEn" class="app-form-control" rows="2" placeholder="English translation..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="app-form-label" data-i18n="lang_settings.table_ja"><?= __('lang_settings.table_ja', 'Tiếng Nhật 🇯🇵') ?></label>
                        <textarea id="newKeyJa" class="app-form-control" rows="2" placeholder="日本語翻訳..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal" data-i18n="common.btn_cancel"><?= __('common.btn_cancel', 'Hủy bỏ') ?></button>
                <button type="button" class="app-btn app-btn-primary" onclick="submitNewKey()" data-i18n="lang_settings.save_key_btn">
                    <span class="material-icons">check</span>
                    <span><?= __('lang_settings.save_key_btn', 'Lưu khóa dịch') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Logic Quản trị Từ điển Đa ngôn ngữ (Client-side)
(function () {
    let allItems = [];
    let filteredItems = [];
    let modifiedKeys = new Map(); // key => { key, vi, en, ja }
    let paginationState = {
        currentPage: 1,
        pageSize: 50
    };

    let modalAddKeyInstance = null;

    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalAddKey');
        if (modalEl && window.bootstrap) {
            modalAddKeyInstance = new bootstrap.Modal(modalEl);
        }
        loadAllLanguageData();
    });

    async function loadAllLanguageData() {
        try {
            const tbody = document.getElementById('langTableBody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><span class="material-icons me-2" style="vertical-align:middle;">hourglass_empty</span>${t('common.loading', 'Đang tải dữ liệu...')}</td></tr>`;
            }

            const res = await fetch('api/languages_api.php?action=get_all', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Lỗi khi tải dữ liệu từ điển');
                return;
            }

            allItems = data.items || [];
            updateStatsUI(data.stats);
            populateGroupFilter(data.groups);

            modifiedKeys.clear();
            updateModifiedCounterUI();

            applyFilters();
        } catch (err) {
            console.error('Lỗi nạp từ điển:', err);
            const tbody = document.getElementById('langTableBody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><span class="material-icons me-1" style="vertical-align:middle;">error</span>Không thể nạp dữ liệu từ điển: ${err.message}</td></tr>`;
            }
        }
    }

    function updateStatsUI(stats) {
        if (!stats) return;
        document.getElementById('statTotalKeys').textContent = (stats.total_keys || 0).toLocaleString();
        document.getElementById('statViCount').textContent = (stats.vi_count || 0).toLocaleString();
        document.getElementById('statEnCount').textContent = (stats.en_count || 0).toLocaleString();
        document.getElementById('statJaCount').textContent = (stats.ja_count || 0).toLocaleString();

        document.getElementById('statViPercent').textContent = stats.vi_percent || 100;
        document.getElementById('statEnPercent').textContent = stats.en_percent || 100;
        document.getElementById('statJaPercent').textContent = stats.ja_percent || 100;
    }

    function populateGroupFilter(groups) {
        const sel = document.getElementById('langGroupFilter');
        if (!sel) return;
        const currentVal = sel.value;

        sel.innerHTML = `<option value="">${t('lang_settings.filter_all_groups', 'Tất cả các nhóm')}</option>`;
        (groups || []).sort().forEach(grp => {
            const opt = document.createElement('option');
            opt.value = grp;
            opt.textContent = grp.toUpperCase();
            if (grp === currentVal) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    window.onFilterChange = function () {
        paginationState.currentPage = 1;
        applyFilters();
    };

    window.resetFilters = function () {
        document.getElementById('langSearchInput').value = '';
        document.getElementById('langGroupFilter').value = '';
        document.getElementById('langMissingFilter').value = 'all';
        paginationState.currentPage = 1;
        applyFilters();
    };

    function applyFilters() {
        const keyword = (document.getElementById('langSearchInput').value || '').trim().toLowerCase();
        const selectedGroup = document.getElementById('langGroupFilter').value;
        const missingMode = document.getElementById('langMissingFilter').value;

        filteredItems = allItems.filter(item => {
            // Lọc theo nhóm
            if (selectedGroup && item.group !== selectedGroup) return false;

            // Lọc theo trạng thái thiếu
            if (missingMode === 'missing_en' && item.en && item.en.trim() !== '') return false;
            if (missingMode === 'missing_ja' && item.ja && item.ja.trim() !== '') return false;

            // Tìm kiếm từ khóa
            if (keyword) {
                const inKey = item.key.toLowerCase().includes(keyword);
                const inVi = (item.vi || '').toLowerCase().includes(keyword);
                const inEn = (item.en || '').toLowerCase().includes(keyword);
                const inJa = (item.ja || '').toLowerCase().includes(keyword);
                if (!inKey && !inVi && !inEn && !inJa) return false;
            }

            return true;
        });

        document.getElementById('filterResultCount').textContent = `${filteredItems.length.toLocaleString()} khóa`;
        renderTablePage();
    }

    function renderTablePage() {
        const tbody = document.getElementById('langTableBody');
        const container = document.getElementById('langPagination');
        if (!tbody) return;

        const totalRecords = filteredItems.length;
        const size = paginationState.pageSize;
        const totalPages = Math.max(1, Math.ceil(totalRecords / size));

        if (paginationState.currentPage > totalPages) paginationState.currentPage = totalPages;
        if (paginationState.currentPage < 1) paginationState.currentPage = 1;

        const current = paginationState.currentPage;
        const startIdx = (current - 1) * size;
        const pageItems = filteredItems.slice(startIdx, startIdx + size);

        if (pageItems.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">${t('common.no_data', 'Không tìm thấy dữ liệu')}</td></tr>`;
        } else {
            tbody.innerHTML = pageItems.map((item, idx) => {
                const stt = startIdx + idx + 1;
                const isMod = modifiedKeys.has(item.key);
                const modClass = isMod ? 'is-modified' : '';

                return `
                <tr data-key="${escapeHtml(item.key)}">
                    <td style="text-align: center; color: var(--dx-text-muted); font-size: 12px;">${stt}</td>
                    <td><span class="lang-group-tag">${escapeHtml(item.group)}</span></td>
                    <td><span class="lang-key-badge">${escapeHtml(item.key)}</span></td>
                    <td>
                        <input type="text" class="lang-cell-input ${modClass}" data-lang="vi" value="${escapeHtml(item.vi)}" onchange="onCellChange('${escapeHtml(item.key)}', 'vi', this.value)">
                    </td>
                    <td>
                        <input type="text" class="lang-cell-input ${modClass}" data-lang="en" value="${escapeHtml(item.en)}" onchange="onCellChange('${escapeHtml(item.key)}', 'en', this.value)">
                    </td>
                    <td>
                        <input type="text" class="lang-cell-input ${modClass}" data-lang="ja" value="${escapeHtml(item.ja)}" onchange="onCellChange('${escapeHtml(item.key)}', 'ja', this.value)">
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <button type="button" class="btn btn-sm btn-outline-primary p-1 me-1" title="Lưu dòng này" onclick="saveSingleKey('${escapeHtml(item.key)}')">
                            <span class="material-icons" style="font-size: 16px;">save</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger p-1" title="Xóa khóa dịch" onclick="deleteSingleKey('${escapeHtml(item.key)}')">
                            <span class="material-icons" style="font-size: 16px;">delete</span>
                        </button>
                    </td>
                </tr>
                `;
            }).join('');
        }

        // Render phân trang chuẩn
        if (typeof renderStandardPagination === 'function') {
            renderStandardPagination('langPagination', {
                currentPage: current,
                totalPages: totalPages,
                totalRecords: totalRecords,
                pageSize: size,
                pageSizeOptions: [25, 50, 100, 200],
                onPageChange: (p) => {
                    paginationState.currentPage = p;
                    renderTablePage();
                },
                onPageSizeChange: (s) => {
                    paginationState.pageSize = s;
                    paginationState.currentPage = 1;
                    renderTablePage();
                }
            });
        }
    }

    window.onCellChange = function (key, lang, value) {
        const item = allItems.find(it => it.key === key);
        if (!item) return;

        item[lang] = value;

        // Lưu vào modified map
        modifiedKeys.set(key, {
            key: key,
            vi: item.vi,
            en: item.en,
            ja: item.ja
        });

        // Đánh dấu input là modified
        const row = document.querySelector(`tr[data-key="${CSS.escape(key)}"]`);
        if (row) {
            row.querySelectorAll('.lang-cell-input').forEach(inp => inp.classList.add('is-modified'));
        }

        updateModifiedCounterUI();
    };

    function updateModifiedCounterUI() {
        const btn = document.getElementById('btnBatchSave');
        const badge = document.getElementById('modifiedCounter');
        const count = modifiedKeys.size;

        if (btn) btn.disabled = (count === 0);
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count === 0);
        }
    }

    window.saveSingleKey = async function (key) {
        const item = allItems.find(it => it.key === key);
        if (!item) return;

        try {
            const res = await fetch('api/languages_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'save_key',
                    key: item.key,
                    vi: item.vi,
                    en: item.en,
                    ja: item.ja
                })
            });
            const data = await res.json();
            if (data.success) {
                modifiedKeys.delete(key);
                updateModifiedCounterUI();

                const row = document.querySelector(`tr[data-key="${CSS.escape(key)}"]`);
                if (row) {
                    row.querySelectorAll('.lang-cell-input').forEach(inp => inp.classList.remove('is-modified'));
                }
                alert(data.message || 'Lưu thành công!');
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            alert('Lỗi mạng hoặc server: ' + err.message);
        }
    };

    window.saveAllModifiedKeys = async function () {
        if (modifiedKeys.size === 0) return;

        const itemsToSave = Array.from(modifiedKeys.values());
        const btn = document.getElementById('btnBatchSave');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch('api/languages_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'batch_save',
                    items: itemsToSave
                })
            });
            const data = await res.json();
            if (data.success) {
                modifiedKeys.clear();
                updateModifiedCounterUI();
                alert(data.message || 'Đã lưu toàn bộ thay đổi thành công!');
                loadAllLanguageData();
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            alert('Lỗi khi lưu: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    window.deleteSingleKey = async function (key) {
        if (!confirm(t('lang_settings.delete_key_confirm', `Bạn có chắc chắn muốn xóa khóa '${key}' khỏi cả 3 từ điển?`))) {
            return;
        }

        try {
            const res = await fetch('api/languages_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'delete_key',
                    key: key
                })
            });
            const data = await res.json();
            if (data.success) {
                modifiedKeys.delete(key);
                loadAllLanguageData();
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            alert('Lỗi khi xóa: ' + err.message);
        }
    };

    window.openAddKeyModal = function () {
        document.getElementById('formAddKey').reset();
        if (modalAddKeyInstance) {
            modalAddKeyInstance.show();
        }
    };

    window.submitNewKey = async function () {
        const key = (document.getElementById('newKeyName').value || '').trim();
        const vi = (document.getElementById('newKeyVi').value || '').trim();
        const en = (document.getElementById('newKeyEn').value || '').trim();
        const ja = (document.getElementById('newKeyJa').value || '').trim();

        if (!key || !vi) {
            alert('Vui lòng nhập Tên khóa và bản dịch Tiếng Việt!');
            return;
        }

        try {
            const res = await fetch('api/languages_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'save_key',
                    key: key,
                    vi: vi,
                    en: en,
                    ja: ja
                })
            });
            const data = await res.json();
            if (data.success) {
                if (modalAddKeyInstance) modalAddKeyInstance.hide();
                alert(data.message || 'Thêm mới thành công!');
                loadAllLanguageData();
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            alert('Lỗi gửi dữ liệu: ' + err.message);
        }
    };

    window.exportDictionaryJson = async function () {
        try {
            const res = await fetch('api/languages_api.php?action=export_json', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.success && data.dictionaries) {
                const blob = new Blob([JSON.stringify(data.dictionaries, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `dx_languages_backup_${new Date().toISOString().slice(0, 10)}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        } catch (err) {
            alert('Lỗi xuất file JSON: ' + err.message);
        }
    };

    window.resetDefaultDictionaries = async function () {
        if (!confirm('Khôi phục từ điển sẽ ghi đè toàn bộ từ điển về cấu hình gốc mặc định. Bạn có chắc chắn muốn tiếp tục?')) {
            return;
        }

        try {
            const res = await fetch('api/languages_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'reset_defaults' })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                loadAllLanguageData();
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            alert('Lỗi kết nối: ' + err.message);
        }
    };

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}());
</script>
