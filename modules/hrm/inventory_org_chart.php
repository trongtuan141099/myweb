<?php
/**
 * Module: Sơ đồ tổ chức nhân sự kiểm kê (Inventory Org Chart)
 * Phân hệ: Quản lý nhân sự (HRM) - DX Plastic Group
 */
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền truy cập cơ bản
checkAuth();
?>

<!-- Custom CSS cho Sơ Đồ Cây Nhân Sự Kiểm Kê -->
<style>
:root {
  --tree-line-color: #94a3b8;
  --tree-line-hover: #1e40af;
}
[data-theme="dark"] {
  --tree-line-color: #334155;
  --tree-line-hover: #3b82f6;
}

/* 1. Thanh điều khiển & KPI */
.ioc-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.ioc-kpi-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--dx-shadow-sm);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.ioc-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}
.ioc-kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.ioc-kpi-val {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.1;
  color: var(--dx-text-main);
}
.ioc-kpi-lbl {
  font-size: 12px;
  font-weight: 600;
  color: var(--dx-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-top: 2px;
}

/* 2. Toolbar điều hướng & Bộ lọc */
.ioc-toolbar {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 10px 16px;
  margin-bottom: 16px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.ioc-tool-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* 3. Vùng chứa Sơ Đồ Cây (Canvas & Pan/Zoom Container) */
.ioc-canvas-container {
  background: var(--dx-bg-app);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  height: calc(100vh - 310px);
  min-height: 520px;
  overflow: hidden;
  position: relative;
  cursor: grab;
  user-select: none;
  background-image: radial-gradient(var(--dx-border) 1px, transparent 1px);
  background-size: 20px 20px;
}
.ioc-canvas-container.is-dragging {
  cursor: grabbing;
}
.ioc-tree-viewport {
  position: absolute;
  top: 0;
  left: 0;
  transform-origin: 50% 50px;
  transition: transform 0.05s ease-out;
  min-width: 100%;
  padding: 40px 60px 100px;
  display: flex;
  justify-content: center;
}

/* 4. Cấu trúc Cây Sơ Đồ Phân Cấp (CSS Flex Tree) */
.dx-org-tree, .dx-org-tree ul {
  display: flex;
  justify-content: center;
  list-style-type: none;
  margin: 0;
  padding: 0;
  position: relative;
}
.dx-org-tree ul {
  padding-top: 28px;
  gap: 20px;
}
.dx-org-tree li {
  text-align: center;
  position: relative;
  padding: 24px 10px 0 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Đường kẻ nhánh cây */
.dx-org-tree li::before, .dx-org-tree li::after {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  border-top: 2px solid var(--tree-line-color);
  width: 50%;
  height: 24px;
}
.dx-org-tree li::after {
  right: auto;
  left: 50%;
  border-left: 2px solid var(--tree-line-color);
}
.dx-org-tree li:only-child::after, .dx-org-tree li:only-child::before {
  display: none;
}
.dx-org-tree li:only-child {
  padding-top: 0;
}
.dx-org-tree li:first-child::before {
  border: 0 none;
}
.dx-org-tree li:last-child::after {
  border: 0 none;
}
.dx-org-tree li:last-child::before {
  border-right: 2px solid var(--tree-line-color);
  border-radius: 0 8px 0 0;
}
.dx-org-tree li:first-child::after {
  border-radius: 8px 0 0 0;
}
.dx-org-tree ul::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  border-left: 2px solid var(--tree-line-color);
  width: 0;
  height: 28px;
  transform: translateX(-50%);
}

/* 5. Thẻ Node Nhân Sự Kiểm Kê */
.ioc-node-card {
  width: 250px;
  background: var(--dx-bg-card);
  border: 2px solid var(--dx-border);
  border-radius: 12px;
  padding: 0;
  box-shadow: var(--dx-shadow-md);
  position: relative;
  transition: all 0.2s ease;
  text-align: left;
  z-index: 2;
}
.ioc-node-card:hover {
  border-color: var(--dx-primary);
  box-shadow: 0 10px 25px -5px rgba(30, 64, 175, 0.25);
  transform: translateY(-3px);
}
.ioc-node-card.is-highlight {
  border-color: #eab308 !important;
  box-shadow: 0 0 0 4px rgba(234, 179, 8, 0.4), 0 10px 25px rgba(234, 179, 8, 0.2) !important;
  animation: pulseHighlight 1.5s infinite;
}
@keyframes pulseHighlight {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.03); }
}

/* Header thẻ: Cấp bậc & Khu vực */
.ioc-node-header {
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  border-bottom: 1px solid var(--dx-border);
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
  background: var(--dx-bg-subtle);
}
.ioc-node-level-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  white-space: nowrap;
}
.ioc-node-area-tag {
  font-size: 11px;
  font-weight: 600;
  color: var(--dx-text-muted);
  display: flex;
  align-items: center;
  gap: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 120px;
}

/* Thân thẻ: Avatar, Tên, Chức danh */
.ioc-node-body {
  padding: 12px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
.ioc-node-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 15px;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
.ioc-node-info {
  flex: 1;
  min-width: 0;
}
.ioc-node-role {
  font-size: 13px;
  font-weight: 800;
  color: var(--dx-text-main);
  line-height: 1.25;
  margin-bottom: 4px;
}
.ioc-node-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--dx-primary);
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ioc-node-meta {
  font-size: 11px;
  color: var(--dx-text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.ioc-node-code {
  font-family: monospace;
  background: var(--dx-bg-subtle);
  padding: 1px 4px;
  border-radius: 3px;
  border: 1px solid var(--dx-border);
  font-weight: 600;
}

/* Footer thẻ: Nhiệm vụ tóm tắt & Nút thao tác */
.ioc-node-duties {
  padding: 6px 12px;
  font-size: 11px;
  color: var(--dx-text-muted);
  background: var(--dx-bg-app);
  border-top: 1px dashed var(--dx-border);
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.ioc-node-actions {
  padding: 6px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--dx-border);
  background: var(--dx-bg-card);
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
}
.ioc-action-btn {
  background: transparent;
  border: none;
  color: var(--dx-text-muted);
  padding: 4px 6px;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  transition: all 0.15s ease;
}
.ioc-action-btn:hover {
  background: var(--dx-bg-subtle);
  color: var(--dx-primary);
}
.ioc-action-btn.btn-del:hover {
  color: var(--dx-danger);
  background: var(--dx-danger-bg);
}

/* Nút Thu gọn / Mở rộng (Collapse/Expand Badge) */
.ioc-toggle-child-btn {
  position: absolute;
  bottom: -13px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--dx-bg-card);
  border: 1.5px solid var(--tree-line-color);
  color: var(--dx-text-main);
  border-radius: 12px;
  padding: 1px 8px;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
  z-index: 5;
  display: flex;
  align-items: center;
  gap: 3px;
  box-shadow: var(--dx-shadow-sm);
  transition: all 0.15s ease;
}
.ioc-toggle-child-btn:hover {
  background: var(--dx-primary);
  border-color: var(--dx-primary);
  color: #fff;
}
.dx-org-tree li.is-collapsed > ul {
  display: none !important;
}

/* Màu sắc đại diện cho các Cấp bậc */
.badge-lvl-primary   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.badge-lvl-info      { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
.badge-lvl-success   { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-lvl-warning   { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.badge-lvl-danger    { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-lvl-purple    { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.badge-lvl-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

[data-theme="dark"] .badge-lvl-primary   { background: rgba(59, 130, 246, 0.18); color: #60a5fa; border-color: rgba(59, 130, 246, 0.35); }
[data-theme="dark"] .badge-lvl-info      { background: rgba(2, 132, 199, 0.18); color: #38bdf8; border-color: rgba(2, 132, 199, 0.35); }
[data-theme="dark"] .badge-lvl-success   { background: rgba(22, 163, 74, 0.18); color: #4ade80; border-color: rgba(22, 163, 74, 0.35); }
[data-theme="dark"] .badge-lvl-warning   { background: rgba(217, 119, 6, 0.18); color: #fbbf24; border-color: rgba(217, 119, 6, 0.35); }
[data-theme="dark"] .badge-lvl-danger    { background: rgba(220, 38, 38, 0.18); color: #f87171; border-color: rgba(220, 38, 38, 0.35); }
[data-theme="dark"] .badge-lvl-purple    { background: rgba(126, 34, 206, 0.18); color: #c084fc; border-color: rgba(126, 34, 206, 0.35); }
[data-theme="dark"] .badge-lvl-secondary { background: rgba(71, 85, 105, 0.18); color: #94a3b8; border-color: rgba(71, 85, 105, 0.35); }

/* Điều khiển Zoom floating góc màn hình */
.ioc-zoom-float {
  position: absolute;
  right: 18px;
  bottom: 18px;
  display: flex;
  flex-direction: column;
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: 8px;
  box-shadow: var(--dx-shadow-md);
  overflow: hidden;
  z-index: 10;
}
.ioc-zoom-btn {
  background: transparent;
  border: none;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--dx-text-main);
  cursor: pointer;
  transition: background 0.15s ease;
}
.ioc-zoom-btn:hover {
  background: var(--dx-bg-subtle);
  color: var(--dx-primary);
}
.ioc-zoom-btn + .ioc-zoom-btn {
  border-top: 1px solid var(--dx-border);
}

/* Autocomplete Suggestion Dropdown */
.emp-suggest-box {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border-strong);
  border-radius: var(--dx-radius-sm);
  box-shadow: var(--dx-shadow-lg);
  max-height: 230px;
  overflow-y: auto;
  z-index: 1055;
  display: none;
}
.emp-suggest-item {
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--dx-border);
  transition: background 0.1s ease;
}
.emp-suggest-item:hover, .emp-suggest-item.active {
  background: var(--dx-primary-light);
}

/* Khi In ấn (Print Styles) */
@media print {
  body { background: #fff !important; }
  .sidebar, .header, .footer, .ioc-toolbar, .ioc-zoom-float, .app-page-actions {
    display: none !important;
  }
  .ioc-canvas-container {
    height: auto !important;
    overflow: visible !important;
    border: none !important;
    background: none !important;
  }
  .ioc-tree-viewport {
    position: static !important;
    transform: none !important;
    padding: 0 !important;
  }
  .ioc-node-card {
    border: 1.5px solid #000 !important;
    box-shadow: none !important;
    break-inside: avoid;
  }
}
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang & Thao Tác Chính -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">account_tree</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">SƠ ĐỒ TỔ CHỨC NHÂN SỰ KIỂM KÊ</h1>
        <p class="text-muted small mb-0">Thiết lập cấu trúc cây phân cấp chỉ đạo và phân công nhân sự kiểm kê kho bãi, vật tư, máy móc</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Chọn đợt kiểm kê -->
      <div class="dropdown">
        <button class="app-btn app-btn-secondary dropdown-toggle" type="button" id="campaignDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="material-icons fs-6">event_note</span>
          <span id="currentCampaignName">Đang tải đợt kiểm kê...</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="campaignDropdownMenu" style="min-width: 280px;">
          <li><h6 class="dropdown-header">Chọn đợt kiểm kê</h6></li>
          <div id="campaignListContainer"></div>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-primary fw-bold" href="javascript:void(0)" onclick="openCampaignModal()">
              <span class="material-icons fs-6 align-middle">add_circle_outline</span> Quản lý / Tạo đợt kiểm kê mới
            </a>
          </li>
        </ul>
      </div>

      <!-- Nút Quản lý Cấp Bậc -->
      <button class="app-btn app-btn-secondary" type="button" onclick="openLevelsModal()" title="Tùy chỉnh danh mục cấp bậc">
        <span class="material-icons">military_tech</span> Cấp Bậc
      </button>

      <!-- Nút Thêm Vị Trí Mới -->
      <button class="app-btn app-btn-primary" type="button" onclick="openAddNodeModal(null)">
        <span class="material-icons">person_add</span> Thêm Vị Trí
      </button>

      <!-- Nút In / Xuất Sơ Đồ -->
      <button class="app-btn app-btn-secondary" type="button" onclick="window.print()" title="In hoặc xuất PDF sơ đồ">
        <span class="material-icons">print</span>
      </button>
    </div>
  </div>

  <!-- 2. Thống Kê Nhanh (KPI Overview Cards) -->
  <div class="ioc-kpi-grid">
    <div class="ioc-kpi-card">
      <div class="ioc-kpi-icon" style="background: var(--dx-primary-light); color: var(--dx-primary);">
        <span class="material-icons">groups</span>
      </div>
      <div>
        <div class="ioc-kpi-val" id="kpiMembers">0</div>
        <div class="ioc-kpi-lbl">Tổng nhân sự kiểm kê</div>
      </div>
    </div>

    <div class="ioc-kpi-card">
      <div class="ioc-kpi-icon" style="background: #f0fdf4; color: #16a34a;">
        <span class="material-icons">military_tech</span>
      </div>
      <div>
        <div class="ioc-kpi-val" id="kpiLevels">0</div>
        <div class="ioc-kpi-lbl">Cấp bậc phân tầng</div>
      </div>
    </div>

    <div class="ioc-kpi-card">
      <div class="ioc-kpi-icon" style="background: #fffbeb; color: #d97706;">
        <span class="material-icons">warehouse</span>
      </div>
      <div>
        <div class="ioc-kpi-val" id="kpiAreas">0</div>
        <div class="ioc-kpi-lbl">Khu vực / Kho phụ trách</div>
      </div>
    </div>

    <div class="ioc-kpi-card">
      <div class="ioc-kpi-icon" style="background: #f0f9ff; color: #0284c7;">
        <span class="material-icons">admin_panel_settings</span>
      </div>
      <div>
        <div class="ioc-kpi-val" id="kpiRootNodes">0</div>
        <div class="ioc-kpi-lbl">Ban chỉ đạo cấp cao</div>
      </div>
    </div>
  </div>

  <!-- 3. Toolbar: Bộ lọc, Tìm kiếm và Chế độ xem -->
  <div class="ioc-toolbar">
    <!-- Nhóm tìm kiếm & Bộ lọc -->
    <div class="ioc-tool-group">
      <!-- Ô Tìm kiếm nhanh nhân sự -->
      <div class="input-group input-group-sm" style="width: 260px;">
        <span class="input-group-text bg-transparent border-end-0">
          <span class="material-icons fs-6 text-muted">search</span>
        </span>
        <input type="text" class="app-form-control border-start-0" id="treeSearchInput" placeholder="Tìm tên, mã NV, vị trí, kho..." oninput="handleSearch(this.value)">
      </div>

      <!-- Lọc theo Cấp Bậc -->
      <select class="app-form-select app-form-select-sm" id="levelFilterSelect" style="width: 180px;" onchange="handleLevelFilter(this.value)">
        <option value="">-- Tất cả cấp bậc --</option>
      </select>

      <!-- Lọc theo Khu vực -->
      <select class="app-form-select app-form-select-sm" id="areaFilterSelect" style="width: 170px;" onchange="handleAreaFilter(this.value)">
        <option value="">-- Tất cả khu vực --</option>
      </select>
    </div>

    <!-- Nhóm Chuyển đổi View & Nút Thu phóng -->
    <div class="ioc-tool-group">
      <!-- Toggle Cây / Bảng -->
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="app-btn app-btn-primary active" id="viewBtnTree" onclick="switchView('tree')">
          <span class="material-icons fs-6">account_tree</span> Sơ đồ cây
        </button>
        <button type="button" class="app-btn app-btn-secondary" id="viewBtnTable" onclick="switchView('table')">
          <span class="material-icons fs-6">table_rows</span> Danh sách
        </button>
      </div>

      <div class="vr mx-1" style="height: 24px;"></div>

      <!-- Nút Mở rộng / Thu gọn toàn bộ -->
      <button class="app-btn app-btn-secondary btn-sm" type="button" onclick="expandAllNodes()" title="Mở rộng toàn bộ cây">
        <span class="material-icons fs-6">unfold_more</span> Mở rộng
      </button>
      <button class="app-btn app-btn-secondary btn-sm" type="button" onclick="collapseAllNodes()" title="Thu gọn toàn bộ cây">
        <span class="material-icons fs-6">unfold_less</span> Thu gọn
      </button>
    </div>
  </div>

  <!-- 4. CHẾ ĐỘ XEM SƠ ĐỒ CÂY (ORG TREE CANVAS) -->
  <div id="treeViewContainer" class="ioc-canvas-container">
    <div id="treeViewport" class="ioc-tree-viewport">
      <div id="treeContent" class="dx-org-tree">
        <!-- Cây sơ đồ sẽ được JavaScript render tại đây -->
        <div class="text-muted p-5 d-flex align-items-center justify-content-center gap-2">
          <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
          <span>Đang nạp sơ đồ nhân sự kiểm kê...</span>
        </div>
      </div>
    </div>

    <!-- Bộ điều khiển Zoom Floating -->
    <div class="ioc-zoom-float">
      <button class="ioc-zoom-btn" type="button" onclick="zoomIn()" title="Phóng to (Ctrl + Cuộn chuột lên)">
        <span class="material-icons fs-5">zoom_in</span>
      </button>
      <button class="ioc-zoom-btn" type="button" onclick="zoomReset()" title="Đặt lại kích thước chuẩn 100%">
        <span class="material-icons fs-6">restart_alt</span>
      </button>
      <button class="ioc-zoom-btn" type="button" onclick="zoomOut()" title="Thu nhỏ (Ctrl + Cuộn chuột xuống)">
        <span class="material-icons fs-5">zoom_out</span>
      </button>
      <button class="ioc-zoom-btn" type="button" onclick="zoomFit()" title="Căn vừa màn hình">
        <span class="material-icons fs-6">fit_screen</span>
      </button>
    </div>
  </div>

  <!-- 5. CHẾ ĐỘ XEM BẢNG PHÂN CẤP (TABLE VIEW) -->
  <div id="tableViewContainer" class="app-card" style="display: none;">
    <div class="app-table-responsive" style="max-height: calc(100vh - 300px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 60px;">STT</th>
            <th>Vị Trí / Chức Danh Kiểm Kê</th>
            <th style="width: 140px;">Cấp Bậc</th>
            <th>Nhân Viên Phụ Trách</th>
            <th>Phòng Ban / Cost Center</th>
            <th>Khu Vực Phụ Trách</th>
            <th>Báo Cáo Cho (Cấp Trên)</th>
            <th style="width: 130px;">Liên Hệ</th>
            <th style="text-align: center; width: 140px;">Hành Động</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <!-- Dữ liệu bảng sẽ được JS đổ vào -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL 1: THÊM MỚI / CHỈNH SỬA VỊ TRÍ NHÂN SỰ KIỂM KÊ                     -->
<!-- ======================================================================= -->
<div class="modal fade" id="nodeModal" tabindex="-1" aria-labelledby="nodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2" id="nodeModalLabel">
          <span class="material-icons fs-5">person_add</span>
          <span id="nodeModalTitleText">Thêm Vị Trí Nhân Sự Kiểm Kê</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="nodeForm" onsubmit="saveNode(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="nodeId" name="id" value="0">
          <input type="hidden" id="nodeCampaignId" name="campaign_id" value="1">

          <div class="row g-3">
            <!-- 1. Vị trí cấp trên (Parent Node) -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Vị Trí Cấp Trên Trực Thuộc (Báo cáo cho)</label>
              <select class="app-form-select" id="nodeParentId" name="parent_id">
                <option value="">-- Là cấp cao nhất (Trưởng Ban / Gốc) --</option>
              </select>
            </div>

            <!-- 2. Cấp bậc kiểm kê -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Cấp Bậc Kiểm Kê <span class="text-danger">*</span></label>
              <select class="app-form-select" id="nodeLevelNumber" name="level_number" required>
                <!-- Nạp động từ danh sách cấp bậc -->
              </select>
            </div>

            <!-- 3. Chức danh / Vai trò kiểm kê -->
            <div class="col-md-12">
              <label class="form-label fw-bold small text-muted">Chức Danh / Vai Trò Kiểm Kê <span class="text-danger">*</span></label>
              <input type="text" class="app-form-control" id="nodePositionTitle" name="position_title" placeholder="VD: Trưởng ban kiểm kê, Tổ trưởng kho NVL, Cặp kiểm kê số 1..." required>
              <!-- Gợi ý nhanh -->
              <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                <span class="small text-muted me-1">Gợi ý nhanh:</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Trưởng Ban Kiểm Kê')">Trưởng Ban</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Phó Ban / Thư Ký')">Phó Ban / Thư Ký</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Tổ Trưởng Kho Nguyên Vật Liệu')">Tổ Trưởng Kho NVL</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Tổ Trưởng Kho Thành Phẩm')">Tổ Trưởng Thành Phẩm</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Cặp Kiểm Kê 1 - Đếm Thực Tế')">Cặp Kiểm Kê</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="setRoleSuggest('Kiểm Kê Viên - Ghi Thẻ Kho')">Kiểm Kê Viên</span>
              </div>
            </div>

            <!-- 4. CHỌN NHÂN VIÊN TỪ DANH SÁCH NHÂN VIÊN (BẢNG EMPLOYEES) -->
            <div class="col-md-12">
              <div class="card p-3 border-primary-subtle bg-light">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <label class="form-label fw-bold small text-primary m-0 d-flex align-items-center gap-1">
                    <span class="material-icons fs-6">badge</span> Gán Nhân Viên (Lấy từ Danh Sách Nhân Viên)
                  </label>
                  <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="clearEmployeeSelection()">
                    <span class="material-icons fs-6 align-middle">clear</span> Xóa chọn / Tự nhập
                  </button>
                </div>

                <!-- Ô tìm kiếm nhân viên live search -->
                <div class="position-relative">
                  <div class="input-group">
                    <span class="input-group-text bg-white">
                      <span class="material-icons fs-6 text-muted">person_search</span>
                    </span>
                    <input type="text" class="app-form-control bg-white" id="empSearchInput" placeholder="Gõ tên nhân viên hoặc mã NV để tìm..." autocomplete="off">
                  </div>
                  <!-- Dropdown gợi ý -->
                  <div class="emp-suggest-box" id="empSuggestBox"></div>
                </div>

                <!-- Thẻ xem trước nhân viên đã chọn -->
                <div id="selectedEmpPreview" class="mt-2 p-2 rounded border bg-white d-flex align-items-center justify-content-between" style="display: none !important;">
                  <div class="d-flex align-items-center gap-2">
                    <div class="ioc-node-avatar" id="prevEmpAvatar" style="background: var(--dx-primary); width: 34px; height: 34px; font-size: 13px;">TT</div>
                    <div>
                      <div class="fw-bold small" id="prevEmpName">Nguyễn Văn A</div>
                      <div class="text-muted" style="font-size: 11px;">
                        Mã NV: <span class="fw-bold font-monospace text-primary" id="prevEmpCode">01510000</span> |
                        Phòng ban: <span id="prevEmpDept">A00536</span> |
                        Cấp bậc: <span class="badge bg-secondary" id="prevEmpLevel">M1</span>
                      </div>
                    </div>
                  </div>
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                    <span class="material-icons fs-6 align-middle">check_circle</span> Đã liên kết
                  </span>
                </div>

                <!-- Các hidden fields lưu dữ liệu nhân viên -->
                <input type="hidden" id="nodeEmpCode" name="employee_code">
                <input type="hidden" id="nodeFullName" name="full_name">
                <input type="hidden" id="nodeDepartment" name="department">
                <input type="hidden" id="nodeJobLevel" name="job_level">
              </div>
            </div>

            <!-- 5. Khu vực kiểm kê phụ trách -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Khu Vực Kiểm Kê Phụ Trách</label>
              <input type="text" class="app-form-control" id="nodeArea" name="area_assigned" list="areaSuggestions" placeholder="VD: Kho Hạt Nhựa, Xưởng Ép Đùn...">
              <datalist id="areaSuggestions">
                <option value="Bộ phận Plastic">
                <option value="A00330 - Nhóm Đùn nhựa">
                <option value="A00442 - Nhóm Nghiền nhựa">
                <option value="A00852 - Nhóm Nylon T">
                <option value="A00330-1 - Tồn NVL">
                <option value="A00330-2 - Tồn BTP">
                <option value="A00330-3 - Kho CCDC">
                <option value="A00442-1 - Tồn NVL trước nghiền">
                <option value="A00442-2 - Tồn NVL trước nghiền">
                <option value="A00852-1 - Tồn NVL">
                <option value="A00852-2 - Tồn BTP">
                <option value="Bảo trì thiết bị">
              </datalist>
            </div>

            <!-- 6. Kênh liên lạc / Số điện thoại / Bộ đàm -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Điện Thoại / Bộ Đàm Liên Lạc</label>
              <input type="text" class="app-form-control" id="nodePhone" name="phone" placeholder="VD: 0901 234 567 hoặc Kênh 2">
            </div>

            <!-- 7. Nhiệm vụ & Trách nhiệm kiểm kê -->
            <div class="col-md-12">
              <label class="form-label fw-bold small text-muted">Nhiệm Vụ / Trách Nhiệm Cụ Thể</label>
              <textarea class="app-form-control" id="nodeDuties" name="duties" rows="2" placeholder="Ghi chú trách nhiệm kiểm kê của vị trí này (chỉ đạo, đếm hàng, dán thẻ, tổng hợp số liệu...)..."></textarea>
            </div>

            <!-- 8. Thứ tự hiển thị & Trạng thái -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Thứ Tự Sắp Xếp (Cùng cấp)</label>
              <input type="number" class="app-form-control" id="nodeSortOrder" name="sort_order" value="1" min="1">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Trạng Thái Hoạt Động</label>
              <select class="app-form-select" id="nodeStatus" name="status">
                <option value="active">Hoạt động (Chính thức)</option>
                <option value="inactive">Tạm ẩn / Dự bị</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2 px-3">
          <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary" id="btnSubmitNode">
            <span class="material-icons fs-6">save</span> Lưu Vị Trí
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL 2: TÙY CHỈNH CẤP BẬC KIỂM KÊ (LEVELS SETTINGS MODAL)              -->
<!-- ======================================================================= -->
<div class="modal fade" id="levelsModal" tabindex="-1" aria-labelledby="levelsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5 text-warning">military_tech</span>
          Cấu Hình Danh Mục Cấp Bậc Kiểm Kê
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">
        <p class="text-muted small mb-3">
          Tùy chỉnh số cấp, tên gọi phân cấp và màu sắc nhận diện cho từng bậc trong cây sơ đồ tổ chức kiểm kê.
        </p>

        <!-- Bảng danh sách cấp bậc hiện tại -->
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 70px;" class="text-center">Số Cấp</th>
                <th>Tên Cấp Bậc Phân Cấp</th>
                <th style="width: 130px;" class="text-center">Màu Huy Hiệu</th>
                <th>Mô Tả Nhiệm Vụ Cấp</th>
                <th style="width: 80px;" class="text-center">Số Vị Trí</th>
                <th style="width: 90px;" class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="levelsTableBody">
              <!-- Nạp danh sách cấp bậc -->
            </tbody>
          </table>
        </div>

        <!-- Form Thêm / Sửa Cấp Bậc -->
        <div class="card p-3 bg-light border">
          <h6 class="fw-bold small mb-2 text-primary d-flex align-items-center gap-1" id="levelFormTitle">
            <span class="material-icons fs-6">add_circle</span> Thêm Cấp Bậc Mới
          </h6>
          <form id="levelForm" onsubmit="saveLevel(event)">
            <input type="hidden" id="lvlId" value="0">
            <div class="row g-2">
              <div class="col-md-2">
                <label class="form-label small text-muted m-0">Số Cấp</label>
                <input type="number" class="app-form-control form-control-sm" id="lvlNumber" required min="1" max="20" placeholder="VD: 1">
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted m-0">Tên Cấp Bậc</label>
                <input type="text" class="app-form-control form-control-sm" id="lvlName" required placeholder="VD: Cấp 1 - Ban Chỉ Đạo">
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted m-0">Màu Huy Hiệu</label>
                <select class="app-form-select form-select-sm" id="lvlColor">
                  <option value="primary">Xanh Navy (Primary)</option>
                  <option value="info">Xanh Da Trời (Info)</option>
                  <option value="success">Xanh Lá (Success)</option>
                  <option value="warning">Vàng Cam (Warning)</option>
                  <option value="danger">Đỏ (Danger)</option>
                  <option value="purple">Tím (Purple)</option>
                  <option value="secondary">Xám (Secondary)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted m-0">Thứ tự ưu tiên</label>
                <input type="number" class="app-form-control form-control-sm" id="lvlSort" value="1">
              </div>
              <div class="col-md-10">
                <label class="form-label small text-muted m-0">Mô Tả Quyền Hạn & Trách Nhiệm</label>
                <input type="text" class="app-form-control form-control-sm" id="lvlDesc" placeholder="Mô tả tóm tắt vai trò của cấp bậc này...">
              </div>
              <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="app-btn app-btn-primary btn-sm w-100" id="btnSaveLevel">
                  <span class="material-icons fs-6">save</span> Lưu
                </button>
                <button type="button" class="app-btn app-btn-secondary btn-sm" id="btnCancelLevel" style="display: none;" onclick="resetLevelForm()">
                  Hủy
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL 3: QUẢN LÝ ĐỢT KIỂM KÊ (CAMPAIGNS MODAL)                          -->
<!-- ======================================================================= -->
<div class="modal fade" id="campaignModal" tabindex="-1" aria-labelledby="campaignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">event_note</span>
          Quản Lý Đợt Kiểm Kê
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">
        <!-- Danh sách đợt kiểm kê -->
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;" class="text-center">ID</th>
                <th>Tên Đợt Kiểm Kê</th>
                <th style="width: 120px;">Ngày Kiểm Kê</th>
                <th style="width: 110px;" class="text-center">Trạng Thái</th>
                <th style="width: 90px;" class="text-center">Nhân Sự</th>
                <th style="width: 100px;" class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="campaignsTableBody">
              <!-- Nạp danh sách đợt kiểm kê -->
            </tbody>
          </table>
        </div>

        <!-- Form Tạo Đợt Kiểm Kê Mới -->
        <div class="card p-3 bg-light border">
          <h6 class="fw-bold small mb-2 text-primary d-flex align-items-center gap-1">
            <span class="material-icons fs-6">add_circle</span> Tạo Đợt Kiểm Kê Mới
          </h6>
          <form id="campaignForm" onsubmit="saveCampaign(event)">
            <input type="hidden" id="campId" value="0">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small text-muted m-0">Tên Đợt Kiểm Kê <span class="text-danger">*</span></label>
                <input type="text" class="app-form-control form-control-sm" id="campName" required placeholder="VD: Kiểm Kê Định Kỳ Cuối Năm 2026">
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted m-0">Ngày Kiểm Kê</label>
                <input type="date" class="app-form-control form-control-sm" id="campDate">
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted m-0">Trạng Thái</label>
                <select class="app-form-select form-select-sm" id="campStatus">
                  <option value="planning">Lên kế hoạch</option>
                  <option value="active" selected>Đang thực hiện</option>
                  <option value="completed">Đã hoàn thành</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label small text-muted m-0">Ghi Chú Đợt Kiểm Kê</label>
                <input type="text" class="app-form-control form-control-sm" id="campNotes" placeholder="Ghi chú phạm vi hoặc quyết định thành lập ban kiểm kê...">
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted m-0">Sao Chép Sơ Đồ Cây Từ</label>
                <select class="app-form-select form-select-sm" id="campCopyFrom">
                  <option value="0">-- Tạo sơ đồ trống --</option>
                </select>
              </div>
              <div class="col-md-12 text-end mt-2">
                <button type="submit" class="app-btn app-btn-primary btn-sm">
                  <span class="material-icons fs-6">save</span> Lưu Đợt Kiểm Kê
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL 4: XEM CHI TIẾT NHIỆM VỤ VỊ TRÍ (DETAIL MODAL)                     -->
<!-- ======================================================================= -->
<div class="modal fade" id="nodeDetailModal" tabindex="-1" aria-labelledby="nodeDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2" id="nodeDetailLabel">
          <span class="material-icons fs-5">assignment_ind</span>
          Chi Tiết Phân Công Kiểm Kê
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="nodeDetailBody">
        <!-- Chi tiết sẽ được render bằng JS -->
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-primary btn-sm" id="btnDetailEdit">Chỉnh sửa</button>
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================================= -->
<!-- JAVASCRIPT ĐIỀU KHIỂN SƠ ĐỒ CÂY TỔ CHỨC KIỂM KÊ                         -->
<!-- ======================================================================= -->
<script>
// State Management
let currentCampaignId = 1;
let orgTreeData = null;
let flatNodesList = [];
let configuredLevels = [];
let activeView = 'tree';

// Canvas Zoom & Pan State
let zoomScale = 1;
let panX = 0;
let panY = 0;
let isDragging = false;
let startX, startY;

// Employee Search State
let employeeSearchTimeout = null;

// Khởi chạy khi tài liệu sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
  initPanAndZoom();
  initEmployeeAutocomplete();
  loadTreeData(currentCampaignId);
});

// =========================================================================
// 1. TẢI DỮ LIỆU CÂY SƠ ĐỒ VÀ RENDER
// =========================================================================
async function loadTreeData(campaignId) {
  currentCampaignId = campaignId || 1;
  const treeContainer = document.getElementById('treeContent');
  treeContainer.innerHTML = `
    <div class="text-muted p-5 d-flex align-items-center justify-content-center gap-2">
      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
      <span>Đang tải cấu trúc sơ đồ...</span>
    </div>
  `;

  try {
    const res = await fetch(`api/inventory_org_chart.php?action=get_tree&campaign_id=${currentCampaignId}`);
    const data = await res.json();

    if (!data.success) {
      treeContainer.innerHTML = `<div class="alert alert-danger">${data.message || 'Lỗi tải dữ liệu'}</div>`;
      return;
    }

    orgTreeData = data;
    flatNodesList = data.flat_nodes || [];
    configuredLevels = data.levels || [];

    // Cập nhật thông tin Đợt kiểm kê trên Header
    if (data.campaign) {
      document.getElementById('currentCampaignName').textContent = data.campaign.campaign_name;
      document.getElementById('nodeCampaignId').value = data.campaign.id;
    }

    // Cập nhật Thống kê nhanh (KPIs)
    document.getElementById('kpiMembers').textContent = data.kpi.total_members || 0;
    document.getElementById('kpiLevels').textContent = data.kpi.total_levels || 0;
    document.getElementById('kpiAreas').textContent = data.kpi.total_areas || 0;
    document.getElementById('kpiRootNodes').textContent = data.kpi.root_nodes || 0;

    // Cập nhật bộ lọc cấp bậc trên toolbar
    populateLevelFilters();

    // Cập nhật bộ lọc khu vực trên toolbar
    populateAreaFilters();

    // Render Sơ đồ cây và Bảng phân cấp
    renderOrgTree(data.tree);
    renderOrgTable(flatNodesList);

    // Tải danh sách các đợt kiểm kê cho dropdown
    loadCampaignsDropdown();

  } catch (err) {
    console.error('Lỗi loadTreeData:', err);
    treeContainer.innerHTML = `<div class="alert alert-danger">Không thể kết nối máy chủ: ${err.message}</div>`;
  }
}

// =========================================================================
// 2. RENDER SƠ ĐỒ CÂY DẠNG FLEX TREE
// =========================================================================
function renderOrgTree(tree) {
  const container = document.getElementById('treeContent');

  if (!tree || tree.length === 0) {
    container.innerHTML = `
      <div class="text-center p-5">
        <span class="material-icons text-muted" style="font-size: 56px;">account_tree</span>
        <h5 class="fw-bold mt-2">Chưa có vị trí nào trong đợt kiểm kê này</h5>
        <p class="text-muted small">Hãy bấm nút "Thêm Vị Trí" để bắt đầu thiết lập Ban Kiểm Kê.</p>
        <button class="app-btn app-btn-primary" type="button" onclick="openAddNodeModal(null)">
          <span class="material-icons">person_add</span> Thêm Vị Trí Đầu Tiên
        </button>
      </div>
    `;
    return;
  }

  // Tạo cây HTML bằng đệ quy
  let html = '<ul>';
  tree.forEach(node => {
    html += buildNodeHtml(node);
  });
  html += '</ul>';

  container.innerHTML = html;

  // Căn giữa sơ đồ
  setTimeout(() => zoomFit(), 100);
}

function buildNodeHtml(node) {
  const initials = getInitials(node.full_name || node.position_title);
  const colorClass = `badge-lvl-${node.badge_color || 'primary'}`;
  const avatarBg = getAvatarColor(node.full_name || node.position_title);
  const hasChildren = node.children && node.children.length > 0;
  const childCount = hasChildren ? node.children.length : 0;

  let html = `
    <li id="tree-li-${node.id}">
      <div class="ioc-node-card" id="tree-card-${node.id}" data-id="${node.id}" data-level="${node.level_number}" data-area="${escapeHtml(node.area_assigned || '')}">
        <!-- Header -->
        <div class="ioc-node-header">
          <span class="ioc-node-level-badge ${colorClass}">Cấp ${node.level_number}</span>
          <span class="ioc-node-area-tag" title="${escapeHtml(node.area_assigned || 'Chưa phân khu')}">
            <span class="material-icons" style="font-size: 12px;">place</span>
            ${escapeHtml(node.area_assigned || 'Toàn NM')}
          </span>
        </div>

        <!-- Body -->
        <div class="ioc-node-body">
          <div class="ioc-node-avatar" style="background: ${avatarBg};">
            ${initials}
          </div>
          <div class="ioc-node-info">
            <div class="ioc-node-role" title="${escapeHtml(node.position_title)}">${escapeHtml(node.position_title)}</div>
            <div class="ioc-node-name" title="${escapeHtml(node.full_name || 'Chưa gán NV')}">
              ${escapeHtml(node.full_name || 'Chưa chỉ định')}
            </div>
            <div class="ioc-node-meta">
              ${node.employee_code ? `<span class="ioc-node-code">${escapeHtml(node.employee_code)}</span>` : ''}
              ${node.department ? `<span title="Phòng ban / Cost Center">${escapeHtml(node.department)}</span>` : ''}
              ${node.job_level ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">${escapeHtml(node.job_level)}</span>` : ''}
            </div>
          </div>
        </div>

        <!-- Duties Snippet -->
        ${node.duties ? `
          <div class="ioc-node-duties" title="${escapeHtml(node.duties)}">
            <span class="material-icons align-middle" style="font-size: 11px;">assignment</span>
            ${escapeHtml(node.duties)}
          </div>
        ` : ''}

        <!-- Actions Footer -->
        <div class="ioc-node-actions">
          <button class="ioc-action-btn" type="button" onclick="openAddNodeModal(${node.id})" title="Thêm vị trí cấp dưới trực thuộc">
            <span class="material-icons" style="font-size: 13px;">add</span> Cấp dưới
          </button>
          <div>
            <button class="ioc-action-btn" type="button" onclick="openNodeDetail(${node.id})" title="Xem chi tiết">
              <span class="material-icons" style="font-size: 14px;">visibility</span>
            </button>
            <button class="ioc-action-btn" type="button" onclick="openEditNodeModal(${node.id})" title="Chỉnh sửa">
              <span class="material-icons" style="font-size: 14px;">edit</span>
            </button>
            <button class="ioc-action-btn btn-del" type="button" onclick="confirmDeleteNode(${node.id}, '${escapeHtml(node.position_title)}', ${childCount})" title="Xóa">
              <span class="material-icons" style="font-size: 14px;">delete</span>
            </button>
          </div>
        </div>

        <!-- Collapse / Expand Toggle Button -->
        ${hasChildren ? `
          <button class="ioc-toggle-child-btn" type="button" onclick="toggleNodeCollapse(${node.id})" id="toggle-btn-${node.id}">
            <span class="material-icons" style="font-size: 11px;">remove</span> ${childCount} trực thuộc
          </button>
        ` : ''}
      </div>
  `;

  // Render các nút con nếu có
  if (hasChildren) {
    html += '<ul>';
    node.children.forEach(child => {
      html += buildNodeHtml(child);
    });
    html += '</ul>';
  }

  html += '</li>';
  return html;
}

// Thu gọn / Mở rộng nhánh con
function toggleNodeCollapse(nodeId) {
  const li = document.getElementById(`tree-li-${nodeId}`);
  const btn = document.getElementById(`toggle-btn-${nodeId}`);
  if (!li || !btn) return;

  const isCollapsed = li.classList.toggle('is-collapsed');
  const node = findNodeById(nodeId);
  const childCount = (node && node.children) ? node.children.length : 0;

  if (isCollapsed) {
    btn.innerHTML = `<span class="material-icons" style="font-size: 11px;">add</span> ${childCount} trực thuộc`;
    btn.style.background = 'var(--dx-primary)';
    btn.style.color = '#fff';
  } else {
    btn.innerHTML = `<span class="material-icons" style="font-size: 11px;">remove</span> ${childCount} trực thuộc`;
    btn.style.background = 'var(--dx-bg-card)';
    btn.style.color = 'var(--dx-text-main)';
  }
}

function expandAllNodes() {
  document.querySelectorAll('.dx-org-tree li.is-collapsed').forEach(li => {
    li.classList.remove('is-collapsed');
  });
  document.querySelectorAll('.ioc-toggle-child-btn').forEach(btn => {
    const icon = btn.querySelector('.material-icons');
    if (icon) icon.textContent = 'remove';
    btn.style.background = 'var(--dx-bg-card)';
    btn.style.color = 'var(--dx-text-main)';
  });
}

function collapseAllNodes() {
  document.querySelectorAll('.dx-org-tree li').forEach(li => {
    if (li.querySelector('ul')) {
      li.classList.add('is-collapsed');
    }
  });
  document.querySelectorAll('.ioc-toggle-child-btn').forEach(btn => {
    const icon = btn.querySelector('.material-icons');
    if (icon) icon.textContent = 'add';
    btn.style.background = 'var(--dx-primary)';
    btn.style.color = '#fff';
  });
}

// =========================================================================
// 3. RENDER CHẾ ĐỘ XEM BẢNG PHÂN CẤP (HIERARCHY TABLE VIEW)
// =========================================================================
function renderOrgTable(nodes) {
  const tbody = document.getElementById('tableBody');
  if (!tbody) return;

  if (!nodes || nodes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted p-4">Chưa có dữ liệu vị trí kiểm kê.</td></tr>';
    return;
  }

  let html = '';
  nodes.forEach((n, idx) => {
    const indent = (n.level_number - 1) * 20;
    const parentNode = flatNodesList.find(p => p.id == n.parent_id);
    const parentTitle = parentNode ? `${parentNode.position_title} (${parentNode.full_name || 'Chưa gán'})` : '<span class="text-muted">- (Gốc / Trưởng Ban)</span>';
    const colorClass = `badge-lvl-${n.badge_color || 'primary'}`;

    html += `
      <tr>
        <td class="text-muted fw-bold text-center">#${idx + 1}</td>
        <td>
          <div style="padding-left: ${indent}px; display: flex; align-items: center; gap: 6px;">
            ${indent > 0 ? '<span class="text-muted">↳</span>' : ''}
            <strong>${escapeHtml(n.position_title)}</strong>
          </div>
        </td>
        <td>
          <span class="ioc-node-level-badge ${colorClass}">Cấp ${n.level_number}</span>
        </td>
        <td>
          <strong>${escapeHtml(n.full_name || 'Chưa chỉ định')}</strong>
          ${n.employee_code ? `<span class="badge bg-light text-dark font-monospace border ms-1">${escapeHtml(n.employee_code)}</span>` : ''}
        </td>
        <td>${escapeHtml(n.department || '-')} ${n.job_level ? `<span class="badge bg-secondary ms-1">${escapeHtml(n.job_level)}</span>` : ''}</td>
        <td>
          ${n.area_assigned ? `<span class="badge bg-light text-dark border">📍 ${escapeHtml(n.area_assigned)}</span>` : '-'}
        </td>
        <td><small>${parentTitle}</small></td>
        <td><small class="text-muted">${escapeHtml(n.phone || '-')}</small></td>
        <td class="text-center">
          <div class="d-flex align-items-center justify-content-center gap-1">
            <button class="btn btn-sm btn-outline-primary p-1" onclick="openAddNodeModal(${n.id})" title="Thêm cấp dưới">
              <span class="material-icons" style="font-size: 14px;">add</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary p-1" onclick="openEditNodeModal(${n.id})" title="Sửa">
              <span class="material-icons" style="font-size: 14px;">edit</span>
            </button>
            <button class="btn btn-sm btn-outline-danger p-1" onclick="confirmDeleteNode(${n.id}, '${escapeHtml(n.position_title)}', 0)" title="Xóa">
              <span class="material-icons" style="font-size: 14px;">delete</span>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

// Chuyển đổi giữa chế độ Cây và Bảng
function switchView(view) {
  activeView = view;
  const treeContainer = document.getElementById('treeViewContainer');
  const tableContainer = document.getElementById('tableViewContainer');
  const btnTree = document.getElementById('viewBtnTree');
  const btnTable = document.getElementById('viewBtnTable');

  if (view === 'tree') {
    treeContainer.style.display = 'block';
    tableContainer.style.display = 'none';
    btnTree.classList.add('active', 'app-btn-primary');
    btnTree.classList.remove('app-btn-secondary');
    btnTable.classList.remove('active', 'app-btn-primary');
    btnTable.classList.add('app-btn-secondary');
    setTimeout(() => zoomFit(), 50);
  } else {
    treeContainer.style.display = 'none';
    tableContainer.style.display = 'block';
    btnTable.classList.add('active', 'app-btn-primary');
    btnTable.classList.remove('app-btn-secondary');
    btnTree.classList.remove('active', 'app-btn-primary');
    btnTree.classList.add('app-btn-secondary');
  }
}

// =========================================================================
// 4. PAN VÀ ZOOM CHO SƠ ĐỒ CÂY (CANVAS CONTROLS)
// =========================================================================
function initPanAndZoom() {
  const container = document.getElementById('treeViewContainer');
  const viewport = document.getElementById('treeViewport');

  function updateTransform() {
    viewport.style.transform = `translate(${panX}px, ${panY}px) scale(${zoomScale})`;
  }

  // Kéo thả chuột (Mouse Drag Pan)
  container.addEventListener('mousedown', (e) => {
    // Không drag khi click vào card hoặc button
    if (e.target.closest('.ioc-node-card') || e.target.closest('button')) return;
    isDragging = true;
    container.classList.add('is-dragging');
    startX = e.clientX - panX;
    startY = e.clientY - panY;
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    panX = e.clientX - startX;
    panY = e.clientY - startY;
    updateTransform();
  });

  window.addEventListener('mouseup', () => {
    if (isDragging) {
      isDragging = false;
      container.classList.remove('is-dragging');
    }
  });

  // Cuộn chuột để Zoom (Ctrl + Wheel hoặc bình thường trên canvas)
  container.addEventListener('wheel', (e) => {
    if (e.ctrlKey || e.metaKey || e.altKey) {
      e.preventDefault();
      const zoomFactor = e.deltaY < 0 ? 1.1 : 0.9;
      setZoom(zoomScale * zoomFactor);
    }
  }, { passive: false });

  window.applyViewportTransform = updateTransform;
}

function setZoom(newScale) {
  zoomScale = Math.min(Math.max(0.3, newScale), 2.5);
  if (window.applyViewportTransform) window.applyViewportTransform();
}

function zoomIn() {
  setZoom(zoomScale + 0.15);
}

function zoomOut() {
  setZoom(zoomScale - 0.15);
}

function zoomReset() {
  zoomScale = 1;
  panX = 0;
  panY = 0;
  if (window.applyViewportTransform) window.applyViewportTransform();
}

function zoomFit() {
  const container = document.getElementById('treeViewContainer');
  const content = document.getElementById('treeContent');
  if (!container || !content) return;

  const contWidth = container.clientWidth;
  const contHeight = container.clientHeight;
  const treeWidth = content.scrollWidth || 800;
  const treeHeight = content.scrollHeight || 400;

  const scaleX = (contWidth - 80) / treeWidth;
  const scaleY = (contHeight - 80) / treeHeight;
  let optimalScale = Math.min(scaleX, scaleY, 1);
  if (optimalScale < 0.4) optimalScale = 0.4;

  zoomScale = optimalScale;
  panX = 0;
  panY = 20;
  if (window.applyViewportTransform) window.applyViewportTransform();
}

// =========================================================================
// 5. TÌM KIẾM & BỘ LỌC TRÊN SƠ ĐỒ (SEARCH & FILTERS)
// =========================================================================
function handleSearch(keyword) {
  const term = keyword.trim().toLowerCase();
  const cards = document.querySelectorAll('.ioc-node-card');

  // Gỡ highlight cũ
  cards.forEach(card => card.classList.remove('is-highlight'));

  if (!term) return;

  let firstMatch = null;
  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    if (text.includes(term)) {
      card.classList.add('is-highlight');

      // Tự động mở rộng tất cả các nhánh cha của card này nếu đang bị thu gọn
      let parentLi = card.closest('li');
      while (parentLi) {
        parentLi.classList.remove('is-collapsed');
        const btn = parentLi.querySelector('.ioc-toggle-child-btn');
        if (btn) {
          const icon = btn.querySelector('.material-icons');
          if (icon) icon.textContent = 'remove';
          btn.style.background = 'var(--dx-bg-card)';
          btn.style.color = 'var(--dx-text-main)';
        }
        parentLi = parentLi.parentElement.closest('li');
      }

      if (!firstMatch) firstMatch = card;
    }
  });

  // Cuộn đến kết quả đầu tiên nếu có
  if (firstMatch && activeView === 'tree') {
    firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
  }
}

function handleLevelFilter(levelNum) {
  const cards = document.querySelectorAll('.ioc-node-card');
  cards.forEach(card => {
    const lvl = card.getAttribute('data-level');
    if (!levelNum || lvl == levelNum) {
      card.style.opacity = '1';
      card.style.filter = 'none';
    } else {
      card.style.opacity = '0.35';
      card.style.filter = 'grayscale(80%)';
    }
  });
}

function handleAreaFilter(area) {
  const cards = document.querySelectorAll('.ioc-node-card');
  cards.forEach(card => {
    const a = card.getAttribute('data-area') || '';
    if (!area || a.toLowerCase().includes(area.toLowerCase())) {
      card.style.opacity = '1';
      card.style.filter = 'none';
    } else {
      card.style.opacity = '0.35';
      card.style.filter = 'grayscale(80%)';
    }
  });
}

function populateLevelFilters() {
  const sel = document.getElementById('levelFilterSelect');
  const curVal = sel.value;
  sel.innerHTML = '<option value="">-- Tất cả cấp bậc --</option>';
  configuredLevels.forEach(lvl => {
    sel.innerHTML += `<option value="${lvl.level_number}">${escapeHtml(lvl.level_name)}</option>`;
  });
  sel.value = curVal;
}

function populateAreaFilters() {
  const sel = document.getElementById('areaFilterSelect');
  const curVal = sel.value;
  const areas = [];
  flatNodesList.forEach(n => {
    if (n.area_assigned && !areas.includes(n.area_assigned)) {
      areas.push(n.area_assigned);
    }
  });

  sel.innerHTML = '<option value="">-- Tất cả khu vực --</option>';
  areas.forEach(a => {
    sel.innerHTML += `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`;
  });
  sel.value = curVal;
}

// =========================================================================
// 6. THÊM / SỬA VỊ TRÍ NHÂN SỰ (NODE MODAL CRUD)
// =========================================================================
function openAddNodeModal(parentId) {
  document.getElementById('nodeForm').reset();
  document.getElementById('nodeId').value = '0';
  document.getElementById('nodeCampaignId').value = currentCampaignId;
  document.getElementById('nodeModalTitleText').textContent = 'Thêm Vị Trí Nhân Sự Kiểm Kê';
  clearEmployeeSelection();

  // Đổ dữ liệu cây vào select Cấp Trên (Parent)
  populateParentSelect(0, parentId);

  // Đổ dữ liệu cấp bậc
  populateLevelSelect();

  // Nếu có parentId truyền vào, tự động tính cấp bậc tiếp theo
  if (parentId) {
    const parentNode = findNodeById(parentId);
    if (parentNode) {
      const nextLvl = parseInt(parentNode.level_number) + 1;
      document.getElementById('nodeLevelNumber').value = nextLvl;
    }
  }

  const modal = new bootstrap.Modal(document.getElementById('nodeModal'));
  modal.show();
}

function openEditNodeModal(nodeId) {
  const node = findNodeById(nodeId);
  if (!node) return;

  document.getElementById('nodeForm').reset();
  document.getElementById('nodeId').value = node.id;
  document.getElementById('nodeCampaignId').value = node.campaign_id;
  document.getElementById('nodeModalTitleText').textContent = 'Chỉnh Sửa Vị Trí: ' + node.position_title;

  // Đổ dữ liệu cây vào select Cấp Trên (trừ chính nó và các con của nó)
  populateParentSelect(node.id, node.parent_id);

  // Đổ cấp bậc
  populateLevelSelect(node.level_number);

  // Điền thông tin form
  document.getElementById('nodePositionTitle').value = node.position_title || '';
  document.getElementById('nodeArea').value = node.area_assigned || '';
  document.getElementById('nodePhone').value = node.phone || '';
  document.getElementById('nodeDuties').value = node.duties || '';
  document.getElementById('nodeSortOrder').value = node.sort_order || 1;
  document.getElementById('nodeStatus').value = node.status || 'active';

  // Nếu đã gắn nhân viên
  if (node.employee_code || node.full_name) {
    selectEmployee({
      employee_code: node.employee_code || '',
      full_name: node.full_name || '',
      cost_center: node.department || '',
      job_level: node.job_level || ''
    });
  } else {
    clearEmployeeSelection();
  }

  const modal = new bootstrap.Modal(document.getElementById('nodeModal'));
  modal.show();
}

function populateParentSelect(excludeNodeId, selectedParentId) {
  const sel = document.getElementById('nodeParentId');
  sel.innerHTML = '<option value="">-- Là cấp cao nhất (Trưởng Ban / Gốc) --</option>';

  // Lấy danh sách con cháu cần loại trừ nếu đang edit
  const excludeIds = [excludeNodeId];
  if (excludeNodeId > 0) {
    const queue = [excludeNodeId];
    while (queue.length > 0) {
      const curr = queue.shift();
      flatNodesList.forEach(n => {
        if (n.parent_id == curr) {
          excludeIds.push(n.id);
          queue.push(n.id);
        }
      });
    }
  }

  flatNodesList.forEach(n => {
    if (!excludeIds.includes(n.id)) {
      const indent = '&nbsp;&nbsp;'.repeat((n.level_number - 1) * 2);
      const isSel = (n.id == selectedParentId) ? 'selected' : '';
      sel.innerHTML += `<option value="${n.id}" ${isSel}>${indent}↳ [Cấp ${n.level_number}] ${escapeHtml(n.position_title)} - ${escapeHtml(n.full_name || 'Chưa gán')}</option>`;
    }
  });
}

function populateLevelSelect(selectedLevel) {
  const sel = document.getElementById('nodeLevelNumber');
  sel.innerHTML = '';
  configuredLevels.forEach(lvl => {
    const isSel = (lvl.level_number == selectedLevel) ? 'selected' : '';
    sel.innerHTML += `<option value="${lvl.level_number}" ${isSel}>${escapeHtml(lvl.level_name)}</option>`;
  });
}

function setRoleSuggest(roleName) {
  document.getElementById('nodePositionTitle').value = roleName;
}

// Lưu Node (Tạo mới hoặc Cập nhật)
async function saveNode(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitNode');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  const formData = new FormData(document.getElementById('nodeForm'));
  formData.append('action', 'save_node');

  try {
    const res = await fetch('api/inventory_org_chart.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('nodeModal')).hide();
      await loadTreeData(currentCampaignId);
    } else {
      alert(data.message || 'Có lỗi xảy ra khi lưu vị trí');
    }
  } catch (err) {
    console.error('Lỗi saveNode:', err);
    alert('Không thể kết nối máy chủ: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Vị Trí';
  }
}

// Xác nhận Xóa Node
function confirmDeleteNode(nodeId, nodeTitle, childCount) {
  let msg = `Bạn có chắc chắn muốn xóa vị trí "${nodeTitle}" không?`;
  let mode = 'reparent';

  if (childCount > 0) {
    const choice = confirm(
      `Vị trí "${nodeTitle}" đang có ${childCount} vị trí cấp dưới trực thuộc!\n\n` +
      `• Nhấn OK để XÓA VỊ TRÍ NÀY và CHUYỂN CÁC CẤP DƯỚI LÊN CẤP TRÊN.\n` +
      `• Nhấn Cancel để hủy bỏ.`
    );
    if (!choice) return;
    mode = 'reparent';
  } else {
    if (!confirm(msg)) return;
  }

  deleteNode(nodeId, mode);
}

async function deleteNode(nodeId, mode) {
  const formData = new FormData();
  formData.append('action', 'delete_node');
  formData.append('id', nodeId);
  formData.append('mode', mode);

  try {
    const res = await fetch('api/inventory_org_chart.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      await loadTreeData(currentCampaignId);
    } else {
      alert(data.message || 'Lỗi khi xóa vị trí');
    }
  } catch (err) {
    console.error('Lỗi deleteNode:', err);
    alert('Lỗi kết nối máy chủ: ' + err.message);
  }
}

// =========================================================================
// 7. TÌM KIẾM VÀ CHỌN NHÂN VIÊN TỪ BẢNG EMPLOYEES
// =========================================================================
function initEmployeeAutocomplete() {
  const input = document.getElementById('empSearchInput');
  const box = document.getElementById('empSuggestBox');

  input.addEventListener('input', () => {
    clearTimeout(employeeSearchTimeout);
    const q = input.value.trim();

    if (q.length < 1) {
      box.style.display = 'none';
      return;
    }

    employeeSearchTimeout = setTimeout(async () => {
      box.innerHTML = '<div class="p-2 text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Đang tìm nhân viên...</div>';
      box.style.display = 'block';

      try {
        const res = await fetch(`api/inventory_org_chart.php?action=search_employees&q=${encodeURIComponent(q)}`);
        const data = await res.json();

        if (!data.success || !data.employees || data.employees.length === 0) {
          box.innerHTML = '<div class="p-2 text-muted small">Không tìm thấy nhân viên phù hợp</div>';
          return;
        }

        let html = '';
        data.employees.forEach(emp => {
          html += `
            <div class="emp-suggest-item" onclick='selectEmployee(${JSON.stringify(emp)})'>
              <div>
                <span class="fw-bold small text-primary">${escapeHtml(emp.full_name)}</span>
                <span class="badge bg-light text-dark border font-monospace ms-1">${escapeHtml(emp.employee_code)}</span>
              </div>
              <div class="text-muted" style="font-size: 11px;">
                ${escapeHtml(emp.cost_center || '')} ${emp.job_level ? `| Cấp: ${escapeHtml(emp.job_level)}` : ''}
              </div>
            </div>
          `;
        });
        box.innerHTML = html;
      } catch (err) {
        console.error('Lỗi tìm kiếm nhân viên:', err);
        box.innerHTML = '<div class="p-2 text-danger small">Lỗi kết nối tìm kiếm</div>';
      }
    }, 250);
  });

  // Đóng box khi click ra ngoài
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !box.contains(e.target)) {
      box.style.display = 'none';
    }
  });
}

function selectEmployee(emp) {
  document.getElementById('nodeEmpCode').value = emp.employee_code || '';
  document.getElementById('nodeFullName').value = emp.full_name || '';
  document.getElementById('nodeDepartment').value = emp.cost_center || '';
  document.getElementById('nodeJobLevel').value = emp.job_level || '';

  // Ẩn search input và hiện preview card
  document.getElementById('empSearchInput').value = '';
  document.getElementById('empSuggestBox').style.display = 'none';

  const preview = document.getElementById('selectedEmpPreview');
  preview.style.setProperty('display', 'flex', 'important');

  document.getElementById('prevEmpName').textContent = emp.full_name || 'Nhân sự ngoài';
  document.getElementById('prevEmpCode').textContent = emp.employee_code || 'N/A';
  document.getElementById('prevEmpDept').textContent = emp.cost_center || 'N/A';
  document.getElementById('prevEmpLevel').textContent = emp.job_level || 'N/A';
  document.getElementById('prevEmpAvatar').textContent = getInitials(emp.full_name || 'NV');
  document.getElementById('prevEmpAvatar').style.background = getAvatarColor(emp.full_name || 'NV');
}

function clearEmployeeSelection() {
  document.getElementById('nodeEmpCode').value = '';
  document.getElementById('nodeFullName').value = '';
  document.getElementById('nodeDepartment').value = '';
  document.getElementById('nodeJobLevel').value = '';

  document.getElementById('selectedEmpPreview').style.setProperty('display', 'none', 'important');
  document.getElementById('empSearchInput').value = '';
}

// =========================================================================
// 8. QUẢN LÝ CẤP BẬC (LEVELS MODAL CRUD)
// =========================================================================
async function openLevelsModal() {
  await loadLevelsList();
  resetLevelForm();
  const modal = new bootstrap.Modal(document.getElementById('levelsModal'));
  modal.show();
}

async function loadLevelsList() {
  const tbody = document.getElementById('levelsTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Đang tải danh sách cấp bậc...</td></tr>';

  try {
    const res = await fetch('api/inventory_org_chart.php?action=get_levels');
    const data = await res.json();

    if (!data.success || !data.levels) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Lỗi tải dữ liệu cấp bậc</td></tr>';
      return;
    }

    configuredLevels = data.levels;
    let html = '';
    data.levels.forEach(lvl => {
      const colorClass = `badge-lvl-${lvl.badge_color || 'primary'}`;
      html += `
        <tr>
          <td class="text-center fw-bold">Cấp ${lvl.level_number}</td>
          <td><strong>${escapeHtml(lvl.level_name)}</strong></td>
          <td class="text-center">
            <span class="ioc-node-level-badge ${colorClass}">${lvl.badge_color}</span>
          </td>
          <td><small class="text-muted">${escapeHtml(lvl.description || '-')}</small></td>
          <td class="text-center fw-bold">${lvl.node_count || 0}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-secondary p-1" onclick='editLevel(${JSON.stringify(lvl)})' title="Sửa">
              <span class="material-icons" style="font-size: 14px;">edit</span>
            </button>
            <button class="btn btn-sm btn-outline-danger p-1" onclick="deleteLevel(${lvl.id}, ${lvl.level_number}, ${lvl.node_count || 0})" title="Xóa">
              <span class="material-icons" style="font-size: 14px;">delete</span>
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  } catch (err) {
    console.error('Lỗi loadLevelsList:', err);
    tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Lỗi kết nối máy chủ</td></tr>';
  }
}

function editLevel(lvl) {
  document.getElementById('lvlId').value = lvl.id;
  document.getElementById('lvlNumber').value = lvl.level_number;
  document.getElementById('lvlName').value = lvl.level_name;
  document.getElementById('lvlColor').value = lvl.badge_color || 'primary';
  document.getElementById('lvlSort').value = lvl.sort_order || lvl.level_number;
  document.getElementById('lvlDesc').value = lvl.description || '';

  document.getElementById('levelFormTitle').innerHTML = '<span class="material-icons fs-6">edit</span> Chỉnh Sửa Cấp Bậc ' + lvl.level_number;
  document.getElementById('btnCancelLevel').style.display = 'inline-flex';
}

function resetLevelForm() {
  document.getElementById('lvlId').value = '0';
  document.getElementById('levelForm').reset();
  document.getElementById('levelFormTitle').innerHTML = '<span class="material-icons fs-6">add_circle</span> Thêm Cấp Bậc Mới';
  document.getElementById('btnCancelLevel').style.display = 'none';
}

async function saveLevel(e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('action', 'save_level');
  formData.append('id', document.getElementById('lvlId').value);
  formData.append('level_number', document.getElementById('lvlNumber').value);
  formData.append('level_name', document.getElementById('lvlName').value);
  formData.append('badge_color', document.getElementById('lvlColor').value);
  formData.append('sort_order', document.getElementById('lvlSort').value);
  formData.append('description', document.getElementById('lvlDesc').value);

  try {
    const res = await fetch('api/inventory_org_chart.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      resetLevelForm();
      await loadLevelsList();
      await loadTreeData(currentCampaignId);
    } else {
      alert(data.message || 'Lỗi lưu cấp bậc');
    }
  } catch (err) {
    console.error('Lỗi saveLevel:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

async function deleteLevel(id, levelNum, nodeCount) {
  if (nodeCount > 0) {
    alert(`Không thể xóa Cấp ${levelNum} vì đang có ${nodeCount} vị trí trong sơ đồ đang sử dụng cấp bậc này! Vui lòng chuyển các vị trí sang cấp khác trước.`);
    return;
  }
  if (!confirm(`Bạn có chắc muốn xóa cấu hình Cấp ${levelNum} không?`)) return;

  const formData = new FormData();
  formData.append('action', 'delete_level');
  formData.append('id', id);

  try {
    const res = await fetch('api/inventory_org_chart.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      await loadLevelsList();
      await loadTreeData(currentCampaignId);
    } else {
      alert(data.message || 'Lỗi xóa cấp bậc');
    }
  } catch (err) {
    console.error('Lỗi deleteLevel:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

// =========================================================================
// 9. QUẢN LÝ ĐỢT KIỂM KÊ (CAMPAIGNS MODAL)
// =========================================================================
async function loadCampaignsDropdown() {
  try {
    const res = await fetch('api/inventory_org_chart.php?action=get_campaigns');
    const data = await res.json();
    if (!data.success || !data.campaigns) return;

    const cont = document.getElementById('campaignListContainer');
    const copySelect = document.getElementById('campCopyFrom');

    let html = '';
    let copyHtml = '<option value="0">-- Tạo sơ đồ trống --</option>';

    data.campaigns.forEach(c => {
      const isCurr = (c.id == currentCampaignId);
      html += `
        <li>
          <a class="dropdown-item d-flex align-items-center justify-content-between ${isCurr ? 'active fw-bold' : ''}" href="javascript:void(0)" onclick="loadTreeData(${c.id})">
            <div>
              <span class="d-block">${escapeHtml(c.campaign_name)}</span>
              <small class="text-muted">${c.audit_date ? 'Ngày: ' + c.audit_date : ''} (${c.node_count || 0} nhân sự)</small>
            </div>
            ${isCurr ? '<span class="material-icons fs-6">check</span>' : ''}
          </a>
        </li>
      `;

      copyHtml += `<option value="${c.id}">Sao chép từ: ${escapeHtml(c.campaign_name)} (${c.node_count || 0} vị trí)</option>`;
    });

    cont.innerHTML = html;
    if (copySelect) copySelect.innerHTML = copyHtml;
  } catch (err) {
    console.error('Lỗi loadCampaignsDropdown:', err);
  }
}

async function openCampaignModal() {
  const tbody = document.getElementById('campaignsTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Đang nạp danh sách đợt kiểm kê...</td></tr>';

  try {
    const res = await fetch('api/inventory_org_chart.php?action=get_campaigns');
    const data = await res.json();

    if (data.success && data.campaigns) {
      let html = '';
      data.campaigns.forEach(c => {
        const isCurr = (c.id == currentCampaignId);
        const statusBadge = c.status === 'completed' ? '<span class="badge bg-success">Hoàn thành</span>' :
                            c.status === 'planning' ? '<span class="badge bg-warning text-dark">Lên kế hoạch</span>' :
                            '<span class="badge bg-primary">Đang diễn ra</span>';
        html += `
          <tr class="${isCurr ? 'table-primary' : ''}">
            <td class="text-center fw-bold">${c.id}</td>
            <td>
              <strong>${escapeHtml(c.campaign_name)}</strong>
              ${isCurr ? '<span class="badge bg-primary ms-1">Đang xem</span>' : ''}
              ${c.notes ? `<div class="small text-muted">${escapeHtml(c.notes)}</div>` : ''}
            </td>
            <td>${c.audit_date || '-'}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center fw-bold">${c.node_count || 0}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="loadTreeData(${c.id}); bootstrap.Modal.getInstance(document.getElementById('campaignModal')).hide();">
                Chọn xem
              </button>
            </td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }
  } catch (err) {
    console.error('Lỗi openCampaignModal:', err);
  }

  const modal = new bootstrap.Modal(document.getElementById('campaignModal'));
  modal.show();
}

async function saveCampaign(e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('action', 'save_campaign');
  formData.append('id', document.getElementById('campId').value);
  formData.append('campaign_name', document.getElementById('campName').value);
  formData.append('audit_date', document.getElementById('campDate').value);
  formData.append('status', document.getElementById('campStatus').value);
  formData.append('notes', document.getElementById('campNotes').value);
  formData.append('copy_from_id', document.getElementById('campCopyFrom').value);

  try {
    const res = await fetch('api/inventory_org_chart.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('campaignForm').reset();
      bootstrap.Modal.getInstance(document.getElementById('campaignModal')).hide();
      await loadTreeData(data.campaign_id);
    } else {
      alert(data.message || 'Lỗi lưu đợt kiểm kê');
    }
  } catch (err) {
    console.error('Lỗi saveCampaign:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

// =========================================================================
// 10. XEM CHI TIẾT VỊ TRÍ (DETAIL MODAL)
// =========================================================================
function openNodeDetail(nodeId) {
  const node = findNodeById(nodeId);
  if (!node) return;

  const parentNode = flatNodesList.find(p => p.id == node.parent_id);
  const parentName = parentNode ? `${parentNode.position_title} (${parentNode.full_name || 'Chưa gán'})` : 'Không có (Cấp cao nhất)';

  // Tìm các nút con trực tiếp
  const directChildren = flatNodesList.filter(c => c.parent_id == node.id);

  const colorClass = `badge-lvl-${node.badge_color || 'primary'}`;
  const avatarBg = getAvatarColor(node.full_name || node.position_title);

  let childrenListHtml = '<span class="text-muted">Không có vị trí cấp dưới trực thuộc</span>';
  if (directChildren.length > 0) {
    childrenListHtml = '<ul class="list-group list-group-flush border rounded mt-1">';
    directChildren.forEach(ch => {
      childrenListHtml += `
        <li class="list-group-item d-flex align-items-center justify-content-between p-2 small">
          <div>
            <strong>${escapeHtml(ch.position_title)}</strong>: ${escapeHtml(ch.full_name || 'Chưa gán')}
            <span class="text-muted font-monospace">(${ch.employee_code || 'N/A'})</span>
          </div>
          <span class="badge bg-light text-dark border">📍 ${escapeHtml(ch.area_assigned || 'Chưa phân khu')}</span>
        </li>
      `;
    });
    childrenListHtml += '</ul>';
  }

  const body = document.getElementById('nodeDetailBody');
  body.innerHTML = `
    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded border mb-3">
      <div class="ioc-node-avatar" style="background: ${avatarBg}; width: 50px; height: 50px; font-size: 18px;">
        ${getInitials(node.full_name || node.position_title)}
      </div>
      <div>
        <h5 class="fw-bold m-0 text-primary">${escapeHtml(node.full_name || 'Chưa chỉ định nhân sự')}</h5>
        <div class="small text-muted mt-1">
          Mã NV: <span class="fw-bold font-monospace">${escapeHtml(node.employee_code || 'N/A')}</span> |
          Phòng ban: <strong>${escapeHtml(node.department || '-')}</strong> |
          Cấp bậc: <span class="badge bg-secondary">${escapeHtml(node.job_level || '-')}</span>
        </div>
      </div>
    </div>

    <div class="row g-2 mb-3 small">
      <div class="col-6">
        <span class="text-muted d-block">Chức danh kiểm kê:</span>
        <strong class="text-dark fs-6">${escapeHtml(node.position_title)}</strong>
      </div>
      <div class="col-6">
        <span class="text-muted d-block">Cấp bậc sơ đồ:</span>
        <span class="ioc-node-level-badge ${colorClass}">Cấp ${node.level_number} - ${escapeHtml(node.level_name || '')}</span>
      </div>
      <div class="col-6 mt-2">
        <span class="text-muted d-block">Khu vực phụ trách:</span>
        <strong>📍 ${escapeHtml(node.area_assigned || 'Toàn bộ nhà máy')}</strong>
      </div>
      <div class="col-6 mt-2">
        <span class="text-muted d-block">Điện thoại / Bộ đàm:</span>
        <strong>📞 ${escapeHtml(node.phone || 'Chưa cấu hình')}</strong>
      </div>
      <div class="col-12 mt-2">
        <span class="text-muted d-block">Báo cáo trực tiếp cho:</span>
        <strong>↳ ${parentName}</strong>
      </div>
    </div>

    <div class="mb-3">
      <span class="text-muted small fw-bold d-block mb-1">Nhiệm vụ & Trách nhiệm kiểm kê:</span>
      <div class="p-2 rounded border bg-white small" style="white-space: pre-line; line-height: 1.5;">
        ${escapeHtml(node.duties || 'Chưa có phân công chi tiết nhiệm vụ.')}
      </div>
    </div>

    <div>
      <span class="text-muted small fw-bold d-block mb-1">Danh sách cấp dưới trực thuộc (${directChildren.length}):</span>
      ${childrenListHtml}
    </div>
  `;

  document.getElementById('btnDetailEdit').onclick = () => {
    bootstrap.Modal.getInstance(document.getElementById('nodeDetailModal')).hide();
    openEditNodeModal(nodeId);
  };

  const modal = new bootstrap.Modal(document.getElementById('nodeDetailModal'));
  modal.show();
}

// =========================================================================
// 11. CÁC HÀM TIỆN ÍCH TRỢ GIÚP (HELPERS)
// =========================================================================
function findNodeById(id) {
  return flatNodesList.find(n => n.id == id) || null;
}

function getInitials(name) {
  if (!name) return 'NV';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function getAvatarColor(str) {
  const colors = [
    'linear-gradient(135deg, #1e40af, #3b82f6)',
    'linear-gradient(135deg, #0284c7, #38bdf8)',
    'linear-gradient(135deg, #16a34a, #4ade80)',
    'linear-gradient(135deg, #d97706, #fbbf24)',
    'linear-gradient(135deg, #7c3aed, #a78bfa)',
    'linear-gradient(135deg, #0d9488, #2dd4bf)',
    'linear-gradient(135deg, #e11d48, #fb7185)'
  ];
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  const index = Math.abs(hash) % colors.length;
  return colors[index];
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

