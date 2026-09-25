<?php
/**
 * Module: Quản Lý Phép Nghỉ & Lịch Nghỉ Lao Động
 * DX Plastic Group - Factory Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();
requirePermission(['hrm.leave_view', 'hrm.view']);

$currentMonth = intval(date('m'));
$currentYear  = intval(date('Y'));
$userRole     = $_SESSION['user']['role'] ?? 'viewer';
$curUsername  = $_SESSION['user']['username'] ?? 'User';

// Kiểm tra quyền chi tiết theo ma trận phân quyền hệ thống
$canManageLeave = hasPermission(['hrm.leave_manage', 'admin']);
$canGroupManage = hasPermission(['hrm.leave_group_manage', 'hrm.leave_manage', 'admin']);
$canPlanLeave   = hasPermission(['hrm.leave_plan', 'hrm.leave_manage', 'admin']);
$canApproveLeave= hasPermission(['hrm.leave_approve', 'hrm.leave_manage', 'admin']);
$canSyncLeave   = hasPermission(['hrm.leave_sync', 'hrm.leave_manage', 'admin']);
?>

<style>
/* CSS Ma Trận Timeline & Dashboard Nghỉ Phép */
.leave-container {
  padding-bottom: 30px;
}

/* 1. Header Trang & Tab Navigation */
.leave-main-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid var(--dx-border);
  margin-bottom: 20px;
  overflow-x: auto;
}
.leave-main-tab {
  padding: 10px 18px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--dx-text-muted);
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s ease;
  white-space: nowrap;
}
.leave-main-tab:hover {
  color: var(--dx-primary);
  background: var(--dx-bg-subtle);
  border-radius: 6px 6px 0 0;
}
.leave-main-tab.active {
  color: var(--dx-primary);
  border-bottom-color: var(--dx-primary);
  font-weight: 700;
  background: transparent;
}
.leave-main-tab .tab-badge {
  padding: 2px 7px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  background: rgba(245, 158, 11, 0.15);
  color: #d97706;
}

/* 2. Thẻ KPI Dashboard (Tương tự Overtime Dashboard) */
.leave-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}
.leave-kpi-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--dx-shadow-sm);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.leave-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}
.leave-kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  flex-shrink: 0;
}
.leave-kpi-val {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.1;
  color: var(--dx-text-main);
}
.leave-kpi-lbl {
  font-size: 11px;
  font-weight: 700;
  color: var(--dx-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-top: 3px;
}

/* Card Biểu đồ Dashboard */
.leave-chart-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  box-shadow: var(--dx-shadow-sm);
  margin-bottom: 20px;
  overflow: hidden;
}
.leave-chart-header {
  padding: 12px 18px;
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
  font-size: 13.5px;
  background: var(--dx-bg-subtle, #f8fafc);
}
.leave-chart-body {
  padding: 16px;
  min-height: 280px;
}

/* 3. Khung Điều Khiển Filter Bar */
.filter-card-leave {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 12px 16px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.filter-group-left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.mode-pill-group {
  display: inline-flex;
  background: var(--dx-bg-subtle, #f1f5f9);
  padding: 3px;
  border-radius: 8px;
  border: 1px solid var(--dx-border);
}
.mode-pill {
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  background: transparent;
  color: var(--dx-text-muted);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.mode-pill.active {
  background: var(--dx-bg-card, #ffffff);
  color: var(--dx-text-main);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  font-weight: 700;
}

/* Chú thích màu sắc (Legend) */
.legend-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  padding: 8px 16px;
  background: var(--dx-bg-subtle, rgba(0,0,0,0.02));
  border-radius: var(--dx-radius-sm);
  border: 1px solid var(--dx-border);
  font-size: 12.5px;
  margin-bottom: 16px;
}
.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--dx-text-main);
  font-weight: 500;
}
.legend-square {
  width: 15px;
  height: 15px;
  border-radius: 4px;
  display: inline-block;
  flex-shrink: 0;
}
.sq-green  { background-color: #10b981 !important; border: 1px solid #059669 !important; box-shadow: 0 1px 2px rgba(16, 185, 129, 0.35); }
.sq-yellow { background-color: #f59e0b !important; border: 1px solid #d97706 !important; box-shadow: 0 1px 2px rgba(245, 158, 11, 0.35); }

/* Đồng Hồ Đếm Ngược Tự Động Đồng Bộ HRM (Countdown Timer) */
.sync-countdown-chip {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 12px;
  background: var(--dx-bg-card, #ffffff);
  border: 1px solid var(--dx-border, #cbd5e1);
  border-radius: 20px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--dx-text-main, #334155);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}
.sync-countdown-chip:hover {
  background: var(--dx-bg-subtle, #f8fafc);
  border-color: var(--dx-primary, #3b82f6);
  color: var(--dx-primary, #3b82f6);
  transform: translateY(-1px);
}
.sync-countdown-chip .sync-pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #10b981;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* 4. Bảng Theo Dõi Quân Số Nhóm Làm Việc (Manpower Matrix) */
.manpower-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  margin-bottom: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.manpower-header {
  padding: 12px 18px;
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--dx-bg-subtle, #f8fafc);
}
.manpower-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
}
.manpower-table th, .manpower-table td {
  padding: 6px 3px;
  text-align: center;
  border-bottom: 1px solid var(--dx-border);
  border-right: 1px solid var(--dx-border);
  white-space: nowrap;
}
.manpower-table th {
  background: var(--dx-bg-subtle, #f1f5f9);
  font-weight: 700;
  color: var(--dx-text-muted);
}
.manpower-table .col-grp-name {
  text-align: left;
  font-weight: 700;
  padding-left: 14px;
  min-width: 160px;
  position: sticky;
  left: 0;
  background: var(--dx-bg-card);
  z-index: 5;
  border-right: 2px solid var(--dx-border-strong, #cbd5e1);
}
.manpower-table .col-grp-total {
  font-weight: 700;
  min-width: 70px;
  background: var(--dx-bg-card);
}

/* 2 Mã Màu Rõ Ràng Phân Biệt Nghỉ Phép Thực Tế & Dự Kiến Trong Bảng Quân Số */
.manpower-chip-act {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 10.5px;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 4px;
  background-color: rgba(16, 185, 129, 0.18);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.4);
  min-width: 18px;
  cursor: default;
}
[data-theme="dark"] .manpower-chip-act {
  background-color: rgba(16, 185, 129, 0.25);
  color: #34d399;
}

.manpower-chip-plan {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 10.5px;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 4px;
  background-color: rgba(245, 158, 11, 0.18);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.4);
  min-width: 18px;
  cursor: default;
}
[data-theme="dark"] .manpower-chip-plan {
  background-color: rgba(245, 158, 11, 0.25);
  color: #fbbf24;
}

.manpower-cell-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  min-height: 22px;
}

/* 5. Cột Hôm Nay Nổi Bật (Highlight Today Column) */
.col-today-highlight {
  background-color: rgba(59, 130, 246, 0.12) !important;
  border-left: 1.5px solid rgba(59, 130, 246, 0.5) !important;
  border-right: 1.5px solid rgba(59, 130, 246, 0.5) !important;
}
.th-today-highlight {
  background-color: var(--dx-primary) !important;
  color: #ffffff !important;
  font-weight: 800 !important;
}
[data-theme="dark"] .col-today-highlight {
  background-color: rgba(59, 130, 246, 0.22) !important;
}

/* 6. Sticky Header 2 Hàng Của Bảng Chi Tiết Nghỉ Phép */
.timeline-wrapper {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.timeline-scroll {
  overflow-x: auto;
  overflow-y: auto;
  max-height: calc(100vh - 340px);
  position: relative;
}
.timeline-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12.5px;
}

/* Hàng 1 (Ngày) cố định top: 0 */
.timeline-table thead tr:first-child th {
  position: sticky;
  top: 0;
  z-index: 22;
  background: var(--dx-bg-card);
  border-bottom: 1px solid var(--dx-border);
  border-right: 1px solid var(--dx-border);
  padding: 6px 4px;
  text-align: center;
  font-weight: 700;
  white-space: nowrap;
  height: 32px;
}

/* Hàng 2 (Thứ) cố định top: 32px */
.timeline-table thead tr:nth-child(2) th {
  position: sticky;
  top: 32px;
  z-index: 21;
  background: var(--dx-bg-card);
  border-bottom: 2px solid var(--dx-border-strong, #cbd5e1);
  border-right: 1px solid var(--dx-border);
  padding: 4px 2px;
  text-align: center;
  font-weight: 600;
  white-space: nowrap;
  height: 26px;
}

/* 3 Cột cố định STT, Mã NV, Họ Tên */
.timeline-table thead th.col-sticky-stt {
  position: sticky;
  left: 0;
  top: 0;
  z-index: 45 !important;
  background: var(--dx-bg-card);
  width: 44px;
  min-width: 44px;
  max-width: 44px;
  border-right: 1px solid var(--dx-border);
}
.timeline-table thead th.col-sticky-code {
  position: sticky;
  left: 44px;
  top: 0;
  z-index: 45 !important;
  background: var(--dx-bg-card);
  width: 85px;
  min-width: 85px;
  border-right: 1px solid var(--dx-border);
}
.timeline-table thead th.col-sticky-name {
  position: sticky;
  left: 129px;
  top: 0;
  z-index: 45 !important;
  background: var(--dx-bg-card);
  width: 170px;
  min-width: 170px;
  border-right: 2px solid var(--dx-border-strong, #cbd5e1);
}

.timeline-table tbody td.col-sticky-stt {
  position: sticky;
  left: 0;
  z-index: 10;
  background: var(--dx-bg-card);
  border-right: 1px solid var(--dx-border);
  text-align: center;
}
.timeline-table tbody td.col-sticky-code {
  position: sticky;
  left: 44px;
  z-index: 10;
  background: var(--dx-bg-card);
  border-right: 1px solid var(--dx-border);
  padding-left: 8px;
  padding-right: 8px;
}
.timeline-table tbody td.col-sticky-name {
  position: sticky;
  left: 129px;
  z-index: 10;
  background: var(--dx-bg-card);
  border-right: 2px solid var(--dx-border-strong, #cbd5e1);
  padding-left: 8px;
  padding-right: 8px;
}

/* Các ô ngày & Trạng thái nghỉ */
.timeline-table td.day-cell {
  padding: 4px 2px;
  text-align: center;
  vertical-align: middle;
  border-bottom: 1px solid var(--dx-border);
  border-right: 1px solid var(--dx-border);
  width: 32px;
  min-width: 32px;
  height: 34px;
  position: relative;
}
.bg-sunday { background-color: rgba(239, 68, 68, 0.05) !important; }
.th-sunday { color: #ef4444 !important; }

.leave-cell-box {
  width: 24px;
  height: 24px;
  margin: 0 auto;
  border-radius: 5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #ffffff;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  user-select: none;
}
.leave-cell-box:hover {
  transform: scale(1.22);
  z-index: 15;
}

.group-header-row td {
  background: var(--dx-bg-subtle, #f8fafc);
  padding: 8px 14px;
  font-weight: 700;
  font-size: 13px;
  color: var(--dx-primary);
  border-bottom: 1px solid var(--dx-border);
  border-top: 1px solid var(--dx-border);
}

/* 7. Badges Ca Làm Việc (Shifts) & Nhóm */
.badge-shift {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}
.badge-shift-ca1 { background: rgba(59, 130, 246, 0.15); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3); }
.badge-shift-ca2 { background: rgba(139, 92, 246, 0.15); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.3); }
.badge-shift-ca3 { background: rgba(245, 158, 11, 0.15); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3); }
.badge-shift-hc  { background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }

/* Badges Nhóm làm việc */
.badge-wg {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 600;
}
.badge-wg-dun_tu      { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
.badge-wg-dun_t       { background: rgba(139, 92, 246, 0.15); color: #7c3aed; }
.badge-wg-thiet_bi    { background: rgba(6, 182, 212, 0.15); color: #0891b2; }
.badge-wg-shotblast   { background: rgba(245, 158, 11, 0.15); color: #d97706; }
.badge-wg-nghien_nhua { background: rgba(16, 185, 129, 0.15); color: #059669; }
.badge-wg-khac        { background: rgba(100, 116, 139, 0.15); color: #475569; }

/* 8. Bảng Danh Sách Phân Bổ Nhóm & Ca (Modal Table View) */
.matrix-table-container {
  max-height: 520px;
  overflow-y: auto;
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-sm);
}
.matrix-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}
.matrix-table th {
  position: sticky;
  top: 0;
  background: var(--dx-bg-subtle, #f1f5f9);
  padding: 8px 10px;
  border-bottom: 2px solid var(--dx-border);
  font-weight: 700;
  z-index: 5;
}
.matrix-table td {
  padding: 7px 10px;
  border-bottom: 1px solid var(--dx-border);
  vertical-align: middle;
}
.matrix-table tr:hover {
  background-color: var(--dx-bg-subtle, #f8fafc);
}

/* Autocomplete suggestion */
.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1060;
  background: var(--dx-bg-card, #ffffff);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-sm);
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);
  max-height: 240px;
  overflow-y: auto;
  margin-top: 4px;
  display: none;
}
.autocomplete-item {
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid var(--dx-border);
}
.autocomplete-item:hover, .autocomplete-item.active {
  background-color: var(--dx-bg-subtle, #f1f5f9);
}

/* Terminal Log */
.sync-terminal {
  background: #0f172a;
  color: #38bdf8;
  font-family: 'Consolas', monospace;
  font-size: 12px;
  padding: 14px;
  border-radius: 8px;
  max-height: 320px;
  overflow-y: auto;
  border: 1px solid #1e293b;
  margin-top: 14px;
}
.sync-terminal .log-line { margin-bottom: 4px; line-height: 1.4; }
.sync-terminal .log-time { color: #64748b; margin-right: 8px; }
.sync-terminal .log-success { color: #4ade80; }
.sync-terminal .log-warning { color: #fbbf24; }
.sync-terminal .log-error   { color: #f87171; }
</style>

<div class="app-page-wrapper leave-container">
  <!-- 1. Header Trang Chuẩn Công Nghiệp -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 26px;">event_busy</span>
        <span>QUẢN LÝ PHÉP NGHỈ & LỊCH NGHỈ LAO ĐỘNG</span>
      </h1>
      <p class="app-page-subtitle">Theo dõi tổng quan dashboard, ma trận timeline quân số, phê duyệt kế hoạch và đồng bộ dữ liệu HRM</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <!-- Đồng hồ đếm ngược tự động đồng bộ HRM -->
      <div class="sync-countdown-chip" id="leaveSyncCountdownBadge" title="Thời gian tự động đồng bộ kế tiếp từ HRM. Bấm để xem chi tiết." onclick="switchMainTab('sync')">
        <span class="sync-pulse-dot" id="syncPulseDot"></span>
        <span class="material-icons text-primary" style="font-size: 15px;">timer</span>
        <span class="text-muted small">Đồng bộ HRM:</span>
        <span id="leaveSyncCountdownText" class="font-monospace fw-bold text-primary">--:--:--</span>
      </div>

      <?php if ($canGroupManage): ?>
      <button class="app-btn app-btn-secondary" type="button" onclick="openWorkGroupMatrixModal()">
        <span class="material-icons">view_kanban</span>
        <span>Phân bổ nhóm & Ca làm việc</span>
      </button>
      <?php endif; ?>

      <?php if ($canPlanLeave): ?>
      <button class="app-btn app-btn-primary" type="button" onclick="openPlanModal()">
        <span class="material-icons">add_circle</span>
        <span>Đăng ký phép kế hoạch</span>
      </button>
      <?php endif; ?>

      <?php if ($canSyncLeave): ?>
      <button class="app-btn app-btn-outline" type="button" onclick="switchMainTab('sync')">
        <span class="material-icons">sync</span>
        <span>Đồng bộ HRM</span>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- 2. Navigation Tabs Chính (5 Tabs Tối Ưu UX) -->
  <div class="leave-main-tabs">
    <button class="leave-main-tab active" id="tabBtnTimeline" onclick="switchMainTab('timeline')">
      <span class="material-icons">grid_on</span>
      <span>Ma Trận Timeline & Quân Số</span>
    </button>
    <button class="leave-main-tab" id="tabBtnDashboard" onclick="switchMainTab('dashboard')">
      <span class="material-icons">dashboard</span>
      <span>Tổng Quan Dashboard</span>
    </button>
    <button class="leave-main-tab" id="tabBtnSummary" onclick="switchMainTab('summary')">
      <span class="material-icons">fact_check</span>
      <span>Tổng Hợp Phép HRM & 12 Tháng</span>
    </button>
    <button class="leave-main-tab" id="tabBtnPlans" onclick="switchMainTab('plans')">
      <span class="material-icons">approval</span>
      <span>Đăng Ký & Phê Duyệt Kế Hoạch</span>
      <span class="tab-badge" id="badgePendingCount">0 chờ duyệt</span>
    </button>
    <?php if ($canSyncLeave): ?>
    <button class="leave-main-tab" id="tabBtnSync" onclick="switchMainTab('sync')">
      <span class="material-icons">cloud_sync</span>
      <span>Đồng Bộ Dữ Liệu HRM & File Excel</span>
    </button>
    <?php endif; ?>
  </div>

  <!-- =========================================================================
       TAB 1: MA TRẬN TIMELINE & QUÂN SỐ LÀM VIỆC (YÊU CẦU 2, 3, 4)
       ========================================================================= -->
  <div id="tabContentTimeline">
    <!-- Filter bar -->
    <div class="filter-card-leave">
      <div class="filter-group-left">
        <!-- Chọn Tháng / Năm -->
        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Tháng:</label>
          <select id="filterMonth" class="form-select form-select-sm" style="width: 105px;" onchange="loadTimelineData()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= ($m == $currentMonth) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
            <?php endfor; ?>
          </select>
          <select id="filterYear" class="form-select form-select-sm" style="width: 88px;" onchange="loadTimelineData()">
            <?php for ($y = $currentYear - 1; $y <= $currentYear + 2; $y++): ?>
              <option value="<?= $y ?>" <?= ($y == $currentYear) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Lọc theo nhóm làm việc -->
        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Nhóm:</label>
          <select id="filterGroup" class="form-select form-select-sm" style="min-width: 150px;" onchange="loadTimelineData()">
            <option value="">-- Tất cả nhóm --</option>
            <option value="Đùn TU">Đùn TU</option>
            <option value="Đùn T">Đùn T</option>
            <option value="Thiết bị">Thiết bị</option>
            <option value="Shotblast">Shotblast</option>
            <option value="Nghiền nhựa">Nghiền nhựa</option>
            <option value="Khác">Khác</option>
          </select>
        </div>

        <!-- Lọc theo Ca làm việc (Yêu cầu 3) -->
        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Ca làm việc:</label>
          <select id="filterShift" class="form-select form-select-sm" style="min-width: 130px;" onchange="loadTimelineData()">
            <option value="">-- Tất cả ca --</option>
            <option value="Ca 1">Ca 1</option>
            <option value="Ca 2">Ca 2</option>
            <option value="Ca 3">Ca 3</option>
            <option value="Hành chính">Hành chính</option>
          </select>
        </div>

        <!-- Tìm kiếm -->
        <div class="d-flex align-items-center gap-1">
          <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Mã hoặc tên NV..." style="width: 160px;" onkeyup="if(event.key==='Enter') loadTimelineData()">
          <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="loadTimelineData()">
            <span class="material-icons" style="font-size:16px;">search</span>
          </button>
        </div>
      </div>

      <!-- Chế độ Timeline & Hành động nhanh -->
      <div class="d-flex align-items-center gap-2">
        <div class="mode-pill-group">
          <button class="mode-pill active" id="modeBtnStandard" onclick="setTimelineMode('standard')">
            <span class="material-icons" style="font-size: 15px;">auto_awesome</span>
            <span>Quy tắc chuẩn</span>
          </button>
          <button class="mode-pill" id="modeBtnPlan" onclick="setTimelineMode('plan')">
            <span class="legend-square sq-yellow" style="width:10px;height:10px;"></span>
            <span>Chỉ Kế hoạch</span>
          </button>
          <button class="mode-pill" id="modeBtnActual" onclick="setTimelineMode('actual')">
            <span class="legend-square sq-green" style="width:10px;height:10px;"></span>
            <span>Chỉ Thực tế</span>
          </button>
        </div>

        <?php if ($canGroupManage): ?>
        <button class="app-btn app-btn-secondary app-btn-sm" type="button" onclick="openWorkGroupMatrixModal()" title="Điều chỉnh phân bổ nhóm & ca">
          <span class="material-icons" style="font-size: 15px;">view_kanban</span>
          <span class="d-none d-md-inline ms-1">Phân bổ nhóm</span>
        </button>
        <?php endif; ?>

        <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="loadTimelineData()" title="Làm mới">
          <span class="material-icons" style="font-size:16px;">refresh</span>
        </button>
      </div>
    </div>

    <!-- 2.1. BẢNG THEO DÕI QUÂN SỐ LÀM VIỆC & NGHỈ PHÉP (YÊU CẦU 2: 2 MÃ MÀU + CỘT HÔM NAY NỔI BẬT) -->
    <div class="manpower-card">
      <div class="manpower-header">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary" style="font-size: 20px;">groups</span>
          <span class="fw-bold text-main" style="font-size: 13.5px;">Quân Số Làm Việc & Nghỉ Phép Theo Từng Nhóm Công Việc</span>
          <span class="badge bg-light text-muted border ms-2" id="manpowerMonthLabel">Tháng <?= $currentMonth ?>/<?= $currentYear ?></span>
        </div>
        <!-- Legend 2 mã màu rõ ràng trong Bảng Quân Số -->
        <div class="d-flex align-items-center gap-3 small">
          <span class="d-inline-flex align-items-center gap-1">
            <span class="manpower-chip-act">1</span> : Phép thực tế (HRM)
          </span>
          <span class="d-inline-flex align-items-center gap-1">
            <span class="manpower-chip-plan">1</span> : Phép dự kiến (Kế hoạch)
          </span>
        </div>
      </div>
      <div class="timeline-scroll">
        <table class="manpower-table" id="manpowerTable">
          <thead id="manpowerThead">
            <!-- Header render bằng JS -->
          </thead>
          <tbody id="manpowerTbody">
            <tr><td colspan="34" class="text-center py-3 text-muted">Đang tải bảng quân số nhóm...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Legend Chú thích màu sắc cho Ma trận Timeline -->
    <div class="legend-bar">
      <span class="fw-bold me-2"><span class="material-icons align-middle text-primary" style="font-size:16px;">info</span> Quy ước hiển thị duy nhất:</span>
      <div class="legend-item">
        <span class="legend-square sq-green"></span>
        <span><strong>Màu xanh lá:</strong> Phép thực tế từ HRM (Áp dụng $\le$ Hôm nay)</span>
      </div>
      <div class="legend-item">
        <span class="legend-square sq-yellow"></span>
        <span><strong>Màu vàng:</strong> Phép dự kiến kế hoạch (Áp dụng $>$ Hôm nay)</span>
      </div>
      <div class="legend-item text-muted small ms-auto">
        <span class="material-icons align-middle text-primary" style="font-size:16px;">touch_app</span> Di chuột vào ô để xem chi tiết
      </div>
    </div>

    <!-- 2.2. BẢNG MA TRẬN TIMELINE CHI TIẾT (YÊU CẦU 4: CỐ ĐỊNH 2 HÀNG HEADER NGÀY & THỨ) -->
    <div class="timeline-wrapper">
      <div class="timeline-scroll" id="timelineScrollContainer">
        <table class="timeline-table" id="timelineTable">
          <thead id="timelineThead">
            <!-- Header 2 Hàng cố định Sticky render bằng JS -->
          </thead>
          <tbody id="timelineTbody">
            <tr>
              <td colspan="35" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                <div>Đang tải ma trận nghỉ phép...</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 2: TỔNG QUAN DASHBOARD THEO DÕI NGHỈ PHÉP (YÊU CẦU 5)
       Thiết kế & cách hoạt động tương tự Overtime Dashboard
       ========================================================================= -->
  <div id="tabContentDashboard" style="display: none;">
    <!-- Dashboard Header Controls -->
    <div class="filter-card-leave mb-3">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-primary fs-5">insights</span>
        <strong class="text-main" style="font-size: 14.5px;">BÁO CÁO & THỐNG KÊ TỔNG QUAN NGHỈ PHÉP NHÀ MÁY</strong>
      </div>
      <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" id="dashMonth" style="width: 120px;" onchange="loadDashboardData()">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m === $currentMonth) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
          <?php endfor; ?>
        </select>
        <select class="form-select form-select-sm" id="dashYear" style="width: 95px;" onchange="loadDashboardData()">
          <option value="2026" <?= ($currentYear === 2026) ? 'selected' : '' ?>>2026</option>
          <option value="2025" <?= ($currentYear === 2025) ? 'selected' : '' ?>>2025</option>
        </select>
        <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="loadDashboardData()" title="Làm mới">
          <span class="material-icons" style="font-size:16px;">refresh</span>
        </button>
      </div>
    </div>

    <!-- 5 KPI Thẻ Chỉ Số Điều Hành -->
    <div class="leave-kpi-grid">
      <!-- KPI 1: Nhân viên có nghỉ phép -->
      <div class="leave-kpi-card">
        <div class="leave-kpi-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
          <span class="material-icons">people</span>
        </div>
        <div>
          <div class="leave-kpi-val" id="kpiEmpCount">0</div>
          <div class="leave-kpi-lbl">NV nghỉ phép (tháng)</div>
        </div>
      </div>

      <!-- KPI 2: Tổng ngày nghỉ tháng -->
      <div class="leave-kpi-card">
        <div class="leave-kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
          <span class="material-icons">event_available</span>
        </div>
        <div>
          <div class="leave-kpi-val" id="kpiDaysMonth">0.0 <span style="font-size:13px;font-weight:500;">ngày</span></div>
          <div class="leave-kpi-lbl">Tổng ngày nghỉ (tháng)</div>
        </div>
      </div>

      <!-- KPI 3: Lũy kế năm YTD -->
      <div class="leave-kpi-card">
        <div class="leave-kpi-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
          <span class="material-icons">trending_up</span>
        </div>
        <div>
          <div class="leave-kpi-val" id="kpiDaysYear">0.0 <span style="font-size:13px;font-weight:500;">ngày</span></div>
          <div class="leave-kpi-lbl">Tổng ngày nghỉ lũy kế năm</div>
        </div>
      </div>

      <!-- KPI 4: Đơn kế hoạch chờ duyệt -->
      <div class="leave-kpi-card">
        <div class="leave-kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
          <span class="material-icons">pending_actions</span>
        </div>
        <div>
          <div class="leave-kpi-val text-warning" id="kpiPendingPlans">0</div>
          <div class="leave-kpi-lbl">Đơn kế hoạch chờ duyệt</div>
        </div>
      </div>

      <!-- KPI 5: Lượt ghi nhận HRM -->
      <div class="leave-kpi-card">
        <div class="leave-kpi-icon" style="background: rgba(6, 182, 212, 0.12); color: #0891b2;">
          <span class="material-icons">cloud_done</span>
        </div>
        <div>
          <div class="leave-kpi-val text-info" id="kpiActualRecords">0</div>
          <div class="leave-kpi-lbl">Lượt phép thực tế HRM</div>
        </div>
      </div>
    </div>

    <!-- Biểu Đồ Hàng 1: Xu Hướng 12 Tháng & Phân Bổ Theo Nhóm -->
    <div class="row g-3">
      <div class="col-12 col-xl-8">
        <div class="leave-chart-card">
          <div class="leave-chart-header">
            <div class="d-flex align-items-center gap-2">
              <span class="material-icons text-primary fs-5">show_chart</span>
              <span>Xu Hướng Số Ngày Nghỉ Phép 12 Tháng Năm <span id="dashTrendYear">2026</span> (Ngày)</span>
            </div>
          </div>
          <div class="leave-chart-body">
            <div id="chartLeaveMonthlyTrend" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="leave-chart-card">
          <div class="leave-chart-header">
            <div class="d-flex align-items-center gap-2">
              <span class="material-icons text-primary fs-5">pie_chart</span>
              <span>Tỷ Lệ Ngày Nghỉ Theo Nhóm Công Việc</span>
            </div>
          </div>
          <div class="leave-chart-body">
            <div id="chartLeaveDeptDist" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Biểu Đồ Hàng 2: Top 10 Nhân Viên & Cơ Cấu Loại Phép -->
    <div class="row g-3">
      <div class="col-12 col-xl-7">
        <div class="leave-chart-card">
          <div class="leave-chart-header">
            <div class="d-flex align-items-center gap-2">
              <span class="material-icons text-primary fs-5">leaderboard</span>
              <span>Top 10 Nhân Viên Có Số Ngày Nghỉ Nhiều Nhất Năm</span>
            </div>
          </div>
          <div class="leave-chart-body">
            <div id="chartLeaveTop10" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-5">
        <div class="leave-chart-card">
          <div class="leave-chart-header">
            <div class="d-flex align-items-center gap-2">
              <span class="material-icons text-primary fs-5">donut_small</span>
              <span>Cơ Cấu Các Loại Phép Trong Tháng</span>
            </div>
          </div>
          <div class="leave-chart-body">
            <div id="chartLeaveTypeDist" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bảng Cảnh Báo & Giám Sát Nhân Sự Nghỉ Phép Nhiều Trong Năm -->
    <div class="app-card mb-4">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-warning">warning</span>
          <strong style="font-size: 14.5px;">DANH SÁCH NHÂN SỰ CẦN GIÁM SÁT SỐ NGÀY NGHỈ (TIỆM CẬN TRẦN 12 NGÀY PHÉP NĂM)</strong>
        </div>
        <button class="app-btn app-btn-secondary btn-sm" type="button" onclick="switchMainTab('summary')">
          Xem bảng tổng hợp 12 tháng <span class="material-icons fs-6">arrow_forward</span>
        </button>
      </div>

      <div class="app-table-responsive">
        <table class="app-table">
          <thead>
            <tr>
              <th style="width: 50px; text-align: center;">STT</th>
              <th>Mã NV</th>
              <th>Họ và Tên</th>
              <th>Nhóm làm việc</th>
              <th>Ca làm việc</th>
              <th>Bộ phận / Cost Center</th>
              <th style="text-align: right;">Đã Nghỉ (Ngày)</th>
              <th style="text-align: right;">Còn Lại Đến 12 Ngày</th>
              <th style="width: 170px;">% Phép Năm (12 Ngày)</th>
              <th style="text-align: center; width: 110px;">Thao tác</th>
            </tr>
          </thead>
          <tbody id="dashCriticalTableBody">
            <tr><td colspan="10" class="text-center py-4 text-muted">Đang tải dữ liệu cảnh báo...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 3: TỔNG HỢP TOÀN BỘ DỮ LIỆU PHÉP HRM & MA TRẬN 12 THÁNG (YÊU CẦU 5)
       ========================================================================= -->
  <div id="tabContentSummary" style="display: none;">
    <!-- Filter Bar cho Tổng Hợp Phép HRM -->
    <div class="filter-card-leave">
      <div class="filter-group-left">
        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Năm:</label>
          <select id="sumYear" class="form-select form-select-sm" style="width: 90px;" onchange="loadCurrentSummaryView()">
            <option value="2026" selected>2026</option>
            <option value="2025">2025</option>
          </select>
        </div>

        <div class="d-flex align-items-center gap-1" id="sumMonthFilterContainer">
          <label class="small text-muted fw-bold">Tháng:</label>
          <select id="sumMonth" class="form-select form-select-sm" style="width: 110px;" onchange="loadCurrentSummaryView()">
            <option value="0">-- Cả năm --</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= ($m === $currentMonth) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Nhóm:</label>
          <select id="sumGroup" class="form-select form-select-sm" style="min-width: 140px;" onchange="loadCurrentSummaryView()">
            <option value="">-- Tất cả nhóm --</option>
            <option value="Đùn TU">Đùn TU</option>
            <option value="Đùn T">Đùn T</option>
            <option value="Thiết bị">Thiết bị</option>
            <option value="Shotblast">Shotblast</option>
            <option value="Nghiền nhựa">Nghiền nhựa</option>
            <option value="Khác">Khác</option>
          </select>
        </div>

        <div class="d-flex align-items-center gap-1">
          <label class="small text-muted fw-bold">Ca:</label>
          <select id="sumShift" class="form-select form-select-sm" style="min-width: 120px;" onchange="loadCurrentSummaryView()">
            <option value="">-- Tất cả ca --</option>
            <option value="Ca 1">Ca 1</option>
            <option value="Ca 2">Ca 2</option>
            <option value="Ca 3">Ca 3</option>
            <option value="Hành chính">Hành chính</option>
          </select>
        </div>

        <div class="d-flex align-items-center gap-1">
          <input type="text" id="sumSearch" class="form-control form-control-sm" placeholder="Tìm mã, họ tên, lý do..." style="width: 180px;" onkeyup="if(event.key==='Enter') loadCurrentSummaryView()">
          <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="loadCurrentSummaryView()">
            <span class="material-icons" style="font-size:16px;">search</span>
          </button>
        </div>
      </div>

      <!-- Chuyển đổi giữa Chế độ Danh Sách Chi Tiết và Ma Trận 12 Tháng -->
      <div class="d-flex align-items-center gap-2">
        <div class="mode-pill-group">
          <button class="mode-pill active" id="sumViewBtnList" onclick="switchSummaryViewMode('list')">
            <span class="material-icons" style="font-size: 15px;">list_alt</span>
            <span>Danh sách chi tiết HRM</span>
          </button>
          <button class="mode-pill" id="sumViewBtnMatrix" onclick="switchSummaryViewMode('matrix12m')">
            <span class="material-icons" style="font-size: 15px;">calendar_view_month</span>
            <span>Ma trận tổng hợp 12 tháng</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 3.1. Chế độ 1: Danh sách chi tiết các đơn nghỉ phép đã phê duyệt từ HRM -->
    <div id="sumListContainer" class="app-card mb-4">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-success">verified</span>
          <strong style="font-size: 14.5px;">BẢNG TỔNG HỢP DỮ LIỆU NGHỈ PHÉP ĐÃ PHÊ DUYỆT TỪ HRM</strong>
          <span class="badge bg-light text-muted border ms-2" id="sumListTotalRowsBadge">0 bản ghi</span>
        </div>
        <div class="small text-muted" id="sumListDaysTotalText">Tổng ngày nghỉ: <strong>0</strong> ngày</div>
      </div>

      <div class="app-table-responsive">
        <table class="app-table">
          <thead>
            <tr>
              <th style="width: 45px; text-align: center;">STT</th>
              <th style="width: 90px;">Mã NV</th>
              <th>Họ và Tên</th>
              <th>Nhóm làm việc</th>
              <th>Ca</th>
              <th>Loại phép</th>
              <th>Hình thức</th>
              <th style="width: 105px; text-align: center;">Từ ngày</th>
              <th style="width: 105px; text-align: center;">Đến ngày</th>
              <th style="width: 80px; text-align: right;">Số ngày</th>
              <th>Lý do nghỉ phép</th>
              <th>Cấp trên duyệt</th>
              <th style="width: 125px; text-align: center;">Ngày đồng bộ</th>
            </tr>
          </thead>
          <tbody id="sumListTbody">
            <tr><td colspan="13" class="text-center py-4 text-muted">Đang tải dữ liệu tổng hợp...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex align-items-center justify-content-between p-3 border-top" id="sumListPagination">
        <div class="small text-muted" id="sumListPageInfo">Hiển thị 0 dòng</div>
        <div class="d-flex gap-1" id="sumListPageBtns"></div>
      </div>
    </div>

    <!-- 3.2. Chế độ 2: Ma trận tổng hợp số ngày nghỉ phép theo từng tháng (12 Tháng) -->
    <div id="sumMatrixContainer" class="app-card mb-4" style="display: none;">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary">calendar_month</span>
          <strong style="font-size: 14.5px;">MA TRẬN TỔNG HỢP THỜI GIAN NGHỈ PHÉP THEO TỪNG THÁNG TRONG NĂM</strong>
          <span class="badge bg-light text-muted border ms-2" id="sumMatrixYearBadge">Năm 2026</span>
        </div>
      </div>

      <div class="app-table-responsive">
        <table class="app-table table-bordered">
          <thead>
            <tr class="text-center">
              <th style="width: 45px;">STT</th>
              <th style="width: 90px;">Mã NV</th>
              <th style="text-align: left; min-width: 160px;">Họ và Tên</th>
              <th style="text-align: left;">Nhóm làm việc</th>
              <th>Ca</th>
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <th style="width: 55px;">T<?= $m ?></th>
              <?php endfor; ?>
              <th style="width: 85px; background: rgba(59, 130, 246, 0.08); font-weight: 800;">Tổng Năm</th>
            </tr>
          </thead>
          <tbody id="sumMatrixTbody">
            <tr><td colspan="18" class="text-center py-4 text-muted">Đang tải ma trận 12 tháng...</td></tr>
          </tbody>
          <tfoot id="sumMatrixTfoot" style="background: var(--dx-bg-subtle, #f1f5f9); font-weight: 700;">
            <!-- Tổng cộng chân bảng render bằng JS -->
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 4: ĐĂNG KÝ & PHÊ DUYỆT KẾ HOẠCH
       ========================================================================= -->
  <div id="tabContentPlans" style="display: none;">
    <div class="filter-card-leave">
      <div class="filter-group-left">
        <select id="planStatusFilter" class="form-select form-select-sm" style="width: 160px;" onchange="loadPlansData(1)">
          <option value="all">Tất cả trạng thái</option>
          <option value="pending" selected>⏳ Chờ phê duyệt</option>
          <option value="approved">✅ Đã phê duyệt</option>
          <option value="rejected">❌ Đã từ chối</option>
        </select>
        <input type="text" id="planSearchInput" class="form-control form-control-sm" placeholder="Tìm theo mã NV, tên..." style="width: 220px;" onkeyup="if(event.key==='Enter') loadPlansData(1)">
        <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="loadPlansData(1)">
          <span class="material-icons" style="font-size:16px;">search</span>
        </button>
      </div>

      <div class="d-flex align-items-center gap-2">
        <?php if ($canApproveLeave): ?>
        <button class="app-btn app-btn-success app-btn-sm" type="button" id="btnBulkApprove" onclick="bulkApprovePlans()" style="display: none;">
          <span class="material-icons" style="font-size:16px;">check_circle</span>
          <span id="bulkApproveText">Duyệt đã chọn</span>
        </button>
        <?php endif; ?>

        <?php if ($canPlanLeave): ?>
        <button class="app-btn app-btn-primary app-btn-sm" type="button" onclick="openPlanModal()">
          <span class="material-icons" style="font-size:16px;">add</span>
          <span>Tạo đơn phép mới</span>
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bảng danh sách kế hoạch -->
    <div class="app-card">
      <div class="app-table-responsive">
        <table class="app-table">
          <thead>
            <tr>
              <?php if ($canApproveLeave): ?>
              <th style="width: 38px; text-align: center;">
                <input type="checkbox" id="checkAllPlans" onchange="toggleSelectAllPlans(this)">
              </th>
              <?php endif; ?>
              <th style="width: 50px; text-align: center;">STT</th>
              <th style="width: 95px;">Mã NV</th>
              <th>Họ và tên</th>
              <th>Nhóm làm việc</th>
              <th style="width: 105px; text-align: center;">Ngày nghỉ</th>
              <th style="width: 85px; text-align: center;">Buổi</th>
              <th>Loại phép</th>
              <th>Lý do</th>
              <th style="width: 110px; text-align: center;">Trạng thái</th>
              <th>Phê duyệt bởi</th>
              <th style="width: 110px; text-align: center;">Thao tác</th>
            </tr>
          </thead>
          <tbody id="plansTbody">
            <tr><td colspan="12" class="text-center py-4 text-muted">Đang tải danh sách phép...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex align-items-center justify-content-between p-3 border-top" id="plansPagination">
        <div class="small text-muted" id="plansPageInfo">Hiển thị 0 dòng</div>
        <div class="d-flex gap-1" id="plansPageBtns"></div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 5: ĐỒNG BỘ HRM & EXCEL (CHỈ DÀNH CHO USER CÓ QUYỀN)
       ========================================================================= -->
  <?php if ($canSyncLeave): ?>
  <div id="tabContentSync" style="display: none;">
    <div class="row g-3">
      <!-- Cột Trái: Trạng thái & Kích hoạt HRM -->
      <div class="col-lg-7">
        <div class="app-card h-100 p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-main d-flex align-items-center gap-2">
              <span class="material-icons text-primary">sync</span>
              <span>Đồng Bộ Tự Động Từ Máy Chủ HRM</span>
            </h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="syncStatusBadge">
              Trực tuyến
            </span>
          </div>

          <p class="text-muted small mb-3">
            Hệ thống tự động kết nối cổng nội bộ HRM (<code>https://hrm.smcmfg.com.vn</code>), gửi xác thực Token và tải dữ liệu <strong>/Portal/NghiPhep/ExportDeDuyetNghiPhep</strong> với cơ chế UPSERT thông minh vào cơ sở dữ liệu.
          </p>

          <div class="bg-subtle p-3 rounded mb-4 border">
            <div class="d-flex justify-content-between py-1 border-bottom">
              <span class="text-muted small">Lần đồng bộ thành công gần nhất:</span>
              <strong class="text-main small" id="lastSyncTime">--</strong>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
              <span class="text-muted small">Tự động đồng bộ kế tiếp sau:</span>
              <span class="small fw-bold text-primary font-monospace" id="tabSyncCountdownText">--:--:--</span>
            </div>
            <div class="d-flex justify-content-between py-1">
              <span class="text-muted small">Kết quả lần chạy trước:</span>
              <span class="small fw-bold text-success" id="lastSyncMsg">Chưa chạy</span>
            </div>
          </div>

          <!-- Khoảng ngày đồng bộ -->
          <div class="row g-2 mb-3">
            <div class="col-sm-6">
              <label class="form-label small fw-bold">Từ ngày:</label>
              <input type="date" id="syncDateFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-bold">Đến ngày:</label>
              <input type="date" id="syncDateTo" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button class="app-btn app-btn-primary" type="button" id="btnTriggerSync" onclick="triggerHrmSync()">
              <span class="material-icons">sync</span>
              <span>Bắt đầu Đồng Bộ HRM Ngay</span>
            </button>
            <button class="app-btn app-btn-outline" type="button" onclick="clearSyncTerminal()">
              <span class="material-icons">clear_all</span>
              <span>Xóa nhật ký</span>
            </button>
          </div>

          <div class="sync-terminal mt-3" id="syncTerminal">
            <div class="log-line"><span class="log-time">[<?= date('H:i:s') ?>]</span> Hệ thống sẵn sàng kết nối HRM SMC...</div>
          </div>
        </div>
      </div>

      <!-- Cột Phải: Import file Excel thủ công -->
      <div class="col-lg-5">
        <div class="app-card h-100 p-4">
          <h5 class="fw-bold mb-3 text-main d-flex align-items-center gap-2">
            <span class="material-icons text-primary">upload_file</span>
            <span>Import File Excel Thủ Công</span>
          </h5>
          <p class="text-muted small mb-3">
            Trường hợp cần nạp tức thì file Excel xuất từ HRM (Mẫu chuẩn <code>data/DanhSachDuyetNghiPhep.xlsx</code>), bạn có thể tải lên trực tiếp tại đây:
          </p>

          <form id="excelUploadForm" onsubmit="handleManualExcelUpload(event)">
            <div class="border-2 border-dashed rounded p-4 text-center mb-3" style="border: 2px dashed var(--dx-border); background: var(--dx-bg-subtle);">
              <span class="material-icons text-muted" style="font-size: 44px;">cloud_upload</span>
              <div class="fw-bold mt-2 mb-1">Chọn file Excel (.xlsx, .xls)</div>
              <div class="small text-muted mb-3">Hỗ trợ tệp xuất nguyên bản từ cổng HRM</div>
              <input type="file" id="manualExcelFile" name="excel_file" class="form-control form-control-sm mx-auto" style="max-width: 260px;" accept=".xlsx,.xls" required>
            </div>

            <button type="submit" class="app-btn app-btn-success w-100" id="btnUploadExcel">
              <span class="material-icons">upload</span>
              <span>Bắt Đầu Nạp Dữ Liệu Excel</span>
            </button>
          </form>

          <div id="uploadResultAlert" class="mt-3" style="display: none;"></div>
        </div>
      </div>
    </div>

    <!-- Hàng 2: Cấu Hình Tham Số Kết Nối HRM & Quản Lý Tài Khoản -->
    <div class="row g-3 mt-2">
      <div class="col-lg-7">
        <div class="app-card h-100 p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-main d-flex align-items-center gap-2">
              <span class="material-icons text-primary">tune</span>
              <span>Cấu Hình Tham Số Kết Nối HRM Tùy Chỉnh</span>
            </h5>
            <button class="app-btn app-btn-outline app-btn-xs" type="button" onclick="resetHrmConfigToDefault()" title="Khôi phục endpoints mặc định">
              <span class="material-icons" style="font-size:14px;">restart_alt</span>
              <span>Mặc định</span>
            </button>
          </div>
          <form id="formHrmConfig" onsubmit="saveHrmConfig(event)">
            <div class="mb-2">
              <label class="form-label small fw-bold">HRM Base URL:</label>
              <input type="text" id="cfgHrmUrl" name="hrm_url" class="form-control form-control-sm font-monospace" placeholder="https://hrm.smcmfg.com.vn" required>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Login Endpoint:</label>
                <input type="text" id="cfgLoginEndpoint" name="login_endpoint" class="form-control form-control-sm font-monospace" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Auth Endpoint:</label>
                <input type="text" id="cfgAuthEndpoint" name="auth_endpoint" class="form-control form-control-sm font-monospace" required>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Portal Endpoint:</label>
                <input type="text" id="cfgPortalEndpoint" name="portal_endpoint" class="form-control form-control-sm font-monospace" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Export Endpoint:</label>
                <input type="text" id="cfgExportEndpoint" name="export_endpoint" class="form-control form-control-sm font-monospace" required>
              </div>
            </div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="app-btn app-btn-primary" id="btnSaveHrmConfig">
                <span class="material-icons">save</span>
                <span>Lưu Cấu Hình Kết Nối</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="app-card h-100 p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-main d-flex align-items-center gap-2">
              <span class="material-icons text-primary">manage_accounts</span>
              <span>Tài Khoản Kết Nối HRM</span>
            </h5>
            <button class="app-btn app-btn-primary app-btn-sm" type="button" onclick="openHrmAccountModal()">
              <span class="material-icons">person_add</span>
              <span>Thêm tài khoản</span>
            </button>
          </div>
          <div class="app-table-responsive">
            <table class="app-table align-middle">
              <thead>
                <tr>
                  <th style="width:38px;">ID</th>
                  <th>Mô tả / Tên tài khoản</th>
                  <th>Username</th>
                  <th style="width:85px; text-align:center;">Trạng thái</th>
                  <th style="width:110px; text-align:center;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="hrmAccountsTbody">
                <tr><td colspan="5" class="text-center py-3 text-muted">Đang tải danh sách tài khoản...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- =========================================================================
     MODAL: QUẢN LÝ PHÂN BỐ NHÓM LÀM VIỆC & CẤU HÌNH CA (YÊU CẦU 1 & 3)
     Thiết kế dạng BẢNG + Nút CHỈNH SỬA trên mỗi dòng + Cấu hình Ca trực tiếp
     ========================================================================= -->
<div class="modal fade" id="modalWorkGroupMatrix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary" style="font-size: 26px;">manage_accounts</span>
          <div>
            <h5 class="modal-title fw-bold mb-0">Quản Lý Phân Bố Nhóm Làm Việc & Cấu Hình Ca Trực Tiếp</h5>
            <div class="small text-muted">Danh sách phân bổ nhân sự dạng bảng, chỉnh sửa thông tin từng nhân viên hoặc chuyển nhóm/ca hàng loạt</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">
        <!-- Thanh Điều Khiển & Bộ Lọc Trong Modal -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-2 bg-subtle rounded border">
          <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Tìm kiếm -->
            <input type="text" id="matrixEmpSearch" class="form-control form-control-sm" placeholder="Tìm theo tên hoặc mã NV..." style="width: 200px;" oninput="applyMatrixFilter()">
            
            <!-- Lọc theo nhóm -->
            <select id="matrixFilterGroup" class="form-select form-select-sm" style="width: 150px;" onchange="applyMatrixFilter()">
              <option value="">-- Tất cả nhóm --</option>
              <option value="Đùn TU">Đùn TU</option>
              <option value="Đùn T">Đùn T</option>
              <option value="Thiết bị">Thiết bị</option>
              <option value="Shotblast">Shotblast</option>
              <option value="Nghiền nhựa">Nghiền nhựa</option>
              <option value="Khác">Khác</option>
            </select>

            <!-- Lọc theo ca làm việc -->
            <select id="matrixFilterShift" class="form-select form-select-sm" style="width: 140px;" onchange="applyMatrixFilter()">
              <option value="">-- Tất cả ca --</option>
              <option value="Ca 1">Ca 1</option>
              <option value="Ca 2">Ca 2</option>
              <option value="Ca 3">Ca 3</option>
              <option value="Hành chính">Hành chính</option>
            </select>

            <span class="badge bg-light text-primary border fw-bold" id="matrixTotalEmpsBadge">0 nhân sự</span>
          </div>

          <!-- Nút chuyển đổi View Mode (Dạng Bảng vs Dạng Thẻ Kanban) -->
          <div class="d-flex align-items-center gap-2">
            <div class="mode-pill-group">
              <button class="mode-pill active" id="matrixViewBtnTable" onclick="setMatrixViewMode('table')">
                <span class="material-icons" style="font-size: 15px;">table_rows</span>
                <span>Dạng Bảng</span>
              </button>
              <button class="mode-pill" id="matrixViewBtnKanban" onclick="setMatrixViewMode('kanban')">
                <span class="material-icons" style="font-size: 15px;">view_kanban</span>
                <span>Dạng Thẻ</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Khung Cấu hình nhanh & Thao tác hàng loạt (Bulk Actions) -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-2 rounded border" style="background: rgba(59, 130, 246, 0.04);">
          <div class="d-flex align-items-center gap-2">
            <span class="small fw-bold text-primary" id="matrixSelectedCount">Đã chọn: 0 nhân viên</span>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="small text-muted fw-bold">Chuyển sang:</span>
            <!-- Chọn nhóm mới -->
            <select id="matrixBulkTargetGroup" class="form-select form-select-sm" style="width: 150px;">
              <option value="">-- Giữ nguyên nhóm --</option>
              <option value="Đùn TU">Đùn TU</option>
              <option value="Đùn T">Đùn T</option>
              <option value="Thiết bị">Thiết bị</option>
              <option value="Shotblast">Shotblast</option>
              <option value="Nghiền nhựa">Nghiền nhựa</option>
              <option value="Khác">Khác</option>
            </select>
            <!-- Chọn ca làm việc mới -->
            <select id="matrixBulkTargetShift" class="form-select form-select-sm" style="width: 150px;">
              <option value="">-- Giữ nguyên ca --</option>
              <option value="Ca 1">Ca 1</option>
              <option value="Ca 2">Ca 2</option>
              <option value="Ca 3">Ca 3</option>
              <option value="Hành chính">Hành chính</option>
            </select>
            <button class="app-btn app-btn-primary app-btn-sm" type="button" onclick="submitBulkMatrixMove()">
              <span class="material-icons" style="font-size:15px;">done_all</span>
              <span>Áp dụng hàng loạt</span>
            </button>
          </div>
        </div>

        <!-- 1. GIAO DIỆN DẠNG BẢNG (MẶC ĐỊNH - YÊU CẦU 1) -->
        <div id="matrixTableViewWrapper" class="matrix-table-container">
          <table class="matrix-table">
            <thead>
              <tr>
                <th style="width: 38px; text-align: center;">
                  <input type="checkbox" id="checkAllMatrix" onchange="toggleSelectAllMatrix(this)">
                </th>
                <th style="width: 45px; text-align: center;">STT</th>
                <th style="width: 100px;">Mã NV</th>
                <th>Họ và Tên</th>
                <th>Bộ Phận / Cost Center</th>
                <th>Nhóm Làm Việc Hiện Tại</th>
                <th>Ca Làm Việc Hiện Tại</th>
                <th style="width: 110px; text-align: center;">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="matrixTableBody">
              <tr><td colspan="8" class="text-center py-4 text-muted">Đang tải danh sách phân bổ...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- 2. GIAO DIỆN DẠNG THẺ KANBAN -->
        <div id="matrixKanbanViewWrapper" class="workgroup-matrix-board" style="display: none;">
          <!-- Render bằng JS -->
        </div>
      </div>

      <div class="modal-footer border-top py-2">
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: CHỈNH SỬA THÔNG TIN PHÂN BỔ 1 NHÂN VIÊN (YÊU CẦU 1)
     ========================================================================= -->
<div class="modal fade" id="modalEditSingleEmployee" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom py-2">
        <h6 class="modal-title fw-bold d-flex align-items-center gap-1">
          <span class="material-icons text-primary fs-5">edit</span>
          <span>Chỉnh Sửa Thông Tin Nhân Viên</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditSingleEmployee" onsubmit="submitSingleEmployeeEdit(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="editEmpCode">
          <div class="mb-2">
            <div class="small text-muted">Mã nhân viên:</div>
            <strong class="text-primary font-monospace" id="editEmpCodeLabel">--</strong>
          </div>
          <div class="mb-3">
            <div class="small text-muted">Họ và tên:</div>
            <strong class="text-main" id="editEmpNameLabel">--</strong>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Nhóm làm việc: <span class="text-danger">*</span></label>
            <select id="editEmpGroup" class="form-select form-select-sm" required>
              <option value="Đùn TU">Đùn TU</option>
              <option value="Đùn T">Đùn T</option>
              <option value="Thiết bị">Thiết bị</option>
              <option value="Shotblast">Shotblast</option>
              <option value="Nghiền nhựa">Nghiền nhựa</option>
              <option value="Khác">Khác</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Ca làm việc: <span class="text-danger">*</span></label>
            <select id="editEmpShift" class="form-select form-select-sm" required>
              <option value="Ca 1">Ca 1</option>
              <option value="Ca 2">Ca 2</option>
              <option value="Ca 3">Ca 3</option>
              <option value="Hành chính">Hành chính</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-top p-2">
          <button type="button" class="app-btn app-btn-outline app-btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary app-btn-sm" id="btnSubmitEditSingle">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: ĐĂNG KÝ PHÉP KẾ HOẠCH (VỚI AUTOCOMPLETE THÔNG MINH)
     ========================================================================= -->
<div class="modal fade" id="modalPlanLeave" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <span class="material-icons text-primary">event_note</span>
          <span>Đăng Ký Phép Nghỉ Kế Hoạch</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formPlanLeave" onsubmit="submitPlanLeave(event)">
        <div class="modal-body p-4">
          <div class="mb-3 position-relative">
            <label class="form-label small fw-bold">Tìm nhân viên (Mã hoặc tên): <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-subtle"><span class="material-icons" style="font-size:16px;">search</span></span>
              <input type="text" id="planEmpSearch" class="form-control" placeholder="Ví dụ: 02021645 hoặc Tỉnh..." autocomplete="off" oninput="handleEmpSearchInput(this.value)" onkeydown="handleEmpSearchKeydown(event)">
            </div>
            <div id="empSuggestionBox" class="autocomplete-dropdown"></div>

            <input type="hidden" id="planEmpCode" name="employee_code" required>
            <input type="hidden" id="planFullName" name="full_name">
            <input type="hidden" id="planGroupName" name="group_name" value="Plastic Extrusion">
            <input type="hidden" id="planTeamName" name="team_name">

            <div id="planSelectedCard" class="mt-2 p-2 rounded border bg-light d-flex align-items-center justify-content-between" style="display: none !important;">
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:12px;" id="planSelectedAvatar">NV</div>
                <div>
                  <div class="fw-bold text-main" id="planSelectedName">--</div>
                  <div class="small text-muted" id="planSelectedSub">--</div>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="clearSelectedEmployee()" title="Bỏ chọn">
                <span class="material-icons" style="font-size:18px;">close</span>
              </button>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Nhóm làm việc:</label>
              <select name="work_group" id="planWorkGroup" class="form-select form-select-sm" required>
                <option value="Đùn TU">Đùn TU</option>
                <option value="Đùn T">Đùn T</option>
                <option value="Thiết bị">Thiết bị</option>
                <option value="Shotblast">Shotblast</option>
                <option value="Nghiền nhựa">Nghiền nhựa</option>
                <option value="Khác">Khác</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Ca làm việc:</label>
              <select name="work_shift" id="planWorkShift" class="form-select form-select-sm">
                <option value="Ca 1">Ca 1</option>
                <option value="Ca 2">Ca 2</option>
                <option value="Ca 3">Ca 3</option>
                <option value="Hành chính">Hành chính</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Từ ngày: <span class="text-danger">*</span></label>
              <input type="date" id="planStartDate" name="start_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>" onchange="syncEndDate(this.value)">
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Đến ngày:</label>
              <input type="date" id="planEndDate" name="end_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Buổi nghỉ:</label>
              <select name="session_type" id="planSessionType" class="form-select form-select-sm">
                <option value="all_day">Cả ngày (1.0 ngày)</option>
                <option value="morning">Buổi sáng (0.5 ngày)</option>
                <option value="afternoon">Buổi chiều (0.5 ngày)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Loại phép:</label>
              <select name="leave_type" id="planLeaveType" class="form-select form-select-sm">
                <option value="Phép năm">Phép năm (Có lương)</option>
                <option value="Nghỉ việc riêng">Việc riêng (Hưởng lương)</option>
                <option value="Nghỉ không lương">Nghỉ không hưởng lương</option>
                <option value="Nghỉ ốm">Nghỉ ốm đau / BHXH</option>
                <option value="Khác">Lý do khác</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Lý do nghỉ cụ thể:</label>
            <textarea name="reason" id="planReason" class="form-control form-control-sm" rows="2" placeholder="Ghi chú lý do nghỉ phép..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary" id="btnSubmitPlan">Gửi Đăng Ký Phép</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: TỪ CHỐI ĐƠN PHÉP
     ========================================================================= -->
<div class="modal fade" id="modalRejectLeave" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom py-2">
        <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-1">
          <span class="material-icons fs-5">cancel</span>
          <span>Từ Chối Đơn Phép</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formRejectLeave" onsubmit="submitRejectLeave(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="rejectPlanId">
          <div class="mb-2">
            <label class="form-label small fw-bold">Lý do từ chối: <span class="text-danger">*</span></label>
            <textarea id="rejectReason" class="form-control form-control-sm" rows="3" placeholder="Nhập lý do không chấp thuận..." required></textarea>
          </div>
        </div>
        <div class="modal-footer border-top p-2">
          <button type="button" class="app-btn app-btn-outline app-btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-danger app-btn-sm" id="btnSubmitReject">Từ Chối</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: XEM CHI TIẾT LỊCH SỬ NGHỈ PHÉP NHÂN VIÊN (TỪ DASHBOARD)
     ========================================================================= -->
<div class="modal fade" id="modalEmpLeaveHistory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom py-2">
        <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
          <span class="material-icons text-primary fs-5">history</span>
          <span>Lịch Sử Nghỉ Phép: <span id="histEmpName" class="text-primary">--</span> (<span id="histEmpCode">--</span>)</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="app-table-responsive" style="max-height: 400px; overflow-y: auto;">
          <table class="app-table">
            <thead>
              <tr>
                <th style="width: 40px; text-align: center;">STT</th>
                <th>Ngày Nghỉ</th>
                <th>Loại Phép</th>
                <th>Hình Thức</th>
                <th style="text-align: right;">Số Ngày</th>
                <th>Lý Do</th>
                <th>Nguồn Ghi Nhận</th>
              </tr>
            </thead>
            <tbody id="histTableBody">
              <tr><td colspan="7" class="text-center py-3 text-muted">Đang tải lịch sử...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="app-btn app-btn-secondary app-btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: QUẢN LÝ TÀI KHOẢN KẾT NỐI HRM
     ========================================================================= -->
<div class="modal fade" id="modalHrmAccount" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--dx-bg-card); color: var(--dx-text-main); border: 1px solid var(--dx-border);">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
          <span class="material-icons text-primary">manage_accounts</span>
          <span id="hrmAccountModalTitle">Thêm Tài Khoản HRM</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formHrmAccount" onsubmit="submitHrmAccount(event)">
        <div class="modal-body p-4">
          <input type="hidden" id="accFormId" name="id" value="0">
          <div class="mb-3">
            <label class="form-label small fw-bold">Tên mô tả tài khoản: <span class="text-danger">*</span></label>
            <input type="text" id="accFormName" name="account_name" class="form-control form-control-sm" placeholder="Ví dụ: Tài khoản HRM SMC" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Username HRM: <span class="text-danger">*</span></label>
              <input type="text" id="accFormUser" name="username" class="form-control form-control-sm font-monospace" placeholder="Ví dụ: 02114273" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Mật khẩu HRM: <span id="accPassRequired" class="text-danger">*</span></label>
              <input type="password" id="accFormPass" name="password" class="form-control form-control-sm font-monospace" placeholder="••••••••">
            </div>
          </div>
          <div class="mb-2">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="accFormActive" name="is_active" value="1" checked>
              <label class="form-check-label small fw-bold" for="accFormActive">Kích hoạt tài khoản này khi đồng bộ</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary" id="btnSubmitHrmAccount">Lưu Tài Khoản</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     JAVASCRIPT TOÀN BỘ LOGIC MODULE
     ========================================================================= -->
<script>
// Biến trạng thái toàn cục
let currentMainTab = 'timeline';
let currentTimelineMode = 'standard';
let currentSummaryViewMode = 'list';
let currentMatrixViewMode = 'table';
let cachedTimelineData = null;
let cachedMatrixEmployees = [];

// ApexCharts Instances
let chartMonthlyTrendInstance = null;
let chartDeptDistInstance     = null;
let chartTop10Instance        = null;
let chartTypeDistInstance     = null;

// Khởi chạy khi load DOM
document.addEventListener('DOMContentLoaded', () => {
  const savedTab = sessionStorage.getItem('leave_active_tab') || 'timeline';
  switchMainTab(savedTab);
  initLeaveAutoSyncCountdown();
});

// =========================================================================
// ĐỒNG HỒ ĐẾM NGƯỢC TỰ ĐỘNG ĐỒNG BỘ HRM (COUNTDOWN TIMER)
// =========================================================================
let leaveCountdownInterval = null;
let leaveRemainingSeconds = 0;

function initLeaveAutoSyncCountdown() {
  checkLeaveAutoSyncSchedule();
}

async function checkLeaveAutoSyncSchedule() {
  try {
    const res = await fetch('api/hrm_leave_sync.php?action=check_schedule');
    const data = await res.json();
    if (!data.success) return;

    if (data.last_sync_time) {
      const elLastSync = document.getElementById('lastSyncTime');
      if (elLastSync) elLastSync.textContent = data.last_sync_time;
      const elLastMsg = document.getElementById('lastSyncMsg');
      if (elLastMsg) elLastMsg.textContent = data.last_sync_message || 'Thành công';
    }

    if (data.auto_sync_enabled) {
      startLeaveCountdown(data.seconds_remaining);
    } else {
      setLeaveCountdownText('Đã tắt');
    }
  } catch (e) {
    console.error('Lỗi checkLeaveAutoSyncSchedule:', e);
  }
}

function startLeaveCountdown(sec) {
  leaveRemainingSeconds = Math.max(0, parseInt(sec) || 0);
  if (leaveCountdownInterval) clearInterval(leaveCountdownInterval);

  renderLeaveCountdown();
  leaveCountdownInterval = setInterval(() => {
    leaveRemainingSeconds--;
    if (leaveRemainingSeconds <= 0) {
      clearInterval(leaveCountdownInterval);
      setLeaveCountdownText('Đang đồng bộ...');
      triggerAutoSyncScheduleRun();
    } else {
      renderLeaveCountdown();
    }
  }, 1000);
}

function renderLeaveCountdown() {
  const h = Math.floor(leaveRemainingSeconds / 3600);
  const m = Math.floor((leaveRemainingSeconds % 3600) / 60);
  const s = leaveRemainingSeconds % 60;
  const timeStr = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  setLeaveCountdownText(timeStr);
}

function setLeaveCountdownText(text) {
  const badgeText = document.getElementById('leaveSyncCountdownText');
  if (badgeText) badgeText.textContent = text;
  const tabText = document.getElementById('tabSyncCountdownText');
  if (tabText) tabText.textContent = text;
}

async function triggerAutoSyncScheduleRun() {
  try {
    const res = await fetch('api/hrm_leave_sync.php?action=check_schedule');
    const data = await res.json();
    if (data.ran_sync) {
      logTerminal('Hệ thống vừa tự động đồng bộ HRM theo lịch trình.', 'success');
      loadTimelineData();
    }
    if (data.auto_sync_enabled && data.seconds_remaining !== undefined) {
      startLeaveCountdown(data.seconds_remaining);
    } else {
      setLeaveCountdownText('Đã tắt');
    }
  } catch (err) {
    console.error('Lỗi triggerAutoSyncScheduleRun:', err);
    setTimeout(checkLeaveAutoSyncSchedule, 30000);
  }
}

/**
 * 1. Chuyển đổi các Tab Chính
 */
function switchMainTab(tabName) {
  currentMainTab = tabName;
  sessionStorage.setItem('leave_active_tab', tabName);

  const tabs = ['timeline', 'dashboard', 'summary', 'plans', 'sync'];
  tabs.forEach(t => {
    const btn = document.getElementById('tabBtn' + capitalizeFirst(t));
    const content = document.getElementById('tabContent' + capitalizeFirst(t));
    if (btn) btn.classList.toggle('active', t === tabName);
    if (content) content.style.display = (t === tabName) ? 'block' : 'none';
  });

  if (tabName === 'timeline') {
    if (!cachedTimelineData) loadTimelineData();
  } else if (tabName === 'dashboard') {
    loadDashboardData();
  } else if (tabName === 'summary') {
    loadCurrentSummaryView();
  } else if (tabName === 'plans') {
    loadPlansData(1);
  } else if (tabName === 'sync') {
    loadSyncConfig();
    loadHrmAccounts();
  }
}

function capitalizeFirst(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * 2. TAB 1: MA TRẬN TIMELINE & QUÂN SỐ
 */
function setTimelineMode(mode) {
  currentTimelineMode = mode;
  ['Standard', 'Plan', 'Actual'].forEach(m => {
    const b = document.getElementById('modeBtn' + m);
    if (b) b.classList.toggle('active', m.toLowerCase() === mode);
  });
  loadTimelineData();
}

async function loadTimelineData() {
  const month = document.getElementById('filterMonth').value;
  const year  = document.getElementById('filterYear').value;
  const group = document.getElementById('filterGroup').value;
  const shift = document.getElementById('filterShift').value;
  const search = document.getElementById('filterSearch').value;

  const manpowerTbody = document.getElementById('manpowerTbody');
  const timelineTbody = document.getElementById('timelineTbody');
  if (manpowerTbody) manpowerTbody.innerHTML = `<tr><td colspan="35" class="text-center py-3 text-muted">Đang tải bảng quân số...</td></tr>`;
  if (timelineTbody) timelineTbody.innerHTML = `<tr><td colspan="35" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><div>Đang tải ma trận nghỉ phép...</div></td></tr>`;

  try {
    const res = await fetch(`api/hrm_leave.php?action=get_timeline&month=${month}&year=${year}&group_name=${encodeURIComponent(group)}&shift=${encodeURIComponent(shift)}&search=${encodeURIComponent(search)}&mode=${currentTimelineMode}`);
    const resData = await res.json();
    if (!resData.success) {
      alert(resData.message || 'Lỗi khi tải dữ liệu timeline');
      return;
    }
    cachedTimelineData = resData.data;
    renderManpowerMatrix(cachedTimelineData);
    renderTimeline(cachedTimelineData);
  } catch (err) {
    console.error('Error loadTimelineData:', err);
  }
}

/**
 * Render Bảng Quân Số Làm Việc (YÊU CẦU 2: 2 Mã màu rõ ràng & Nổi bật hôm nay)
 */
function renderManpowerMatrix(data) {
  const thead = document.getElementById('manpowerThead');
  const tbody = document.getElementById('manpowerTbody');
  const label = document.getElementById('manpowerMonthLabel');
  if (label) label.textContent = `Tháng ${data.month}/${data.year}`;

  const daysInMonth = data.days_in_month;
  const today = data.today;
  const curYear = data.year;
  const curMonth = data.month;

  // Render Thead Bảng Quân Số
  let thRow1 = `<tr><th class="col-grp-name" rowspan="2">Nhóm Công Việc</th><th class="col-grp-total" rowspan="2">Tổng QS</th>`;
  let thRow2 = `<tr>`;

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${curYear}-${String(curMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const dt = new Date(curYear, curMonth - 1, d);
    const dayOfWeek = dt.getDay();
    const isSunday = (dayOfWeek === 0);
    const isToday  = (dateStr === today);

    const dowName = isSunday ? 'CN' : ('T' + (dayOfWeek + 1));
    const thClass = isToday ? 'th-today-highlight col-today-highlight' : (isSunday ? 'th-sunday bg-sunday' : '');

    thRow1 += `<th class="${thClass}" style="width:34px; min-width:34px;">${d}</th>`;
    thRow2 += `<th class="${isToday ? 'col-today-highlight font-weight-bold text-primary' : (isSunday ? 'th-sunday bg-sunday' : 'text-muted')}" style="font-size:10px;">${dowName}</th>`;
  }
  thRow1 += `</tr>`;
  thRow2 += `</tr>`;
  thead.innerHTML = thRow1 + thRow2;

  // Render Tbody Bảng Quân Số
  if (!data.work_group_summary || data.work_group_summary.length === 0) {
    tbody.innerHTML = `<tr><td colspan="${daysInMonth + 2}" class="text-center py-3 text-muted">Không có dữ liệu nhóm phù hợp</td></tr>`;
    return;
  }

  let html = '';
  data.work_group_summary.forEach(g => {
    html += `<tr>`;
    html += `<td class="col-grp-name">${escapeHtml(g.work_group)}</td>`;
    html += `<td class="col-grp-total">${g.total_emps}</td>`;

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${curYear}-${String(curMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const dt = new Date(curYear, curMonth - 1, d);
      const isSunday = (dt.getDay() === 0);
      const isToday  = (dateStr === today);

      const actLeaves = g.daily_actual_leaves ? (g.daily_actual_leaves[d] || 0) : 0;
      const plnLeaves = g.daily_planned_leaves ? (g.daily_planned_leaves[d] || 0) : 0;
      const working   = g.daily_working ? (g.daily_working[d] ?? g.total_emps) : g.total_emps;

      const tdClass = (isToday ? 'col-today-highlight' : '') + (isSunday ? ' bg-sunday' : '');

      let chipsHtml = '';
      if (dateStr <= today) {
        // Mốc thời gian <= Hôm nay: CHỈ hiển thị Phép thực tế từ HRM (Màu xanh lá)
        if (actLeaves > 0) {
          chipsHtml = `<span class="manpower-chip-act" title="Phép thực tế HRM: ${actLeaves} người">${actLeaves}</span>`;
        } else {
          chipsHtml = `<span class="text-muted" style="font-size:11px;">-</span>`;
        }
      } else {
        // Mốc thời gian > Hôm nay: CHỈ hiển thị Phép dự kiến kế hoạch (Màu vàng)
        if (plnLeaves > 0) {
          chipsHtml = `<span class="manpower-chip-plan" title="Phép dự kiến kế hoạch: ${plnLeaves} người">${plnLeaves}</span>`;
        } else {
          chipsHtml = `<span class="text-muted" style="font-size:11px;">-</span>`;
        }
      }

      const tooltipText = (dateStr <= today)
        ? `Nhóm: ${escapeHtml(g.work_group)} | Ngày ${d}/${curMonth}: Đi làm ${working}/${g.total_emps} (Thực tế HRM: ${actLeaves})`
        : `Nhóm: ${escapeHtml(g.work_group)} | Ngày ${d}/${curMonth}: Đi làm ${working}/${g.total_emps} (Dự kiến KH: ${plnLeaves})`;

      html += `<td class="${tdClass}" title="${tooltipText}">
        <div class="manpower-cell-wrapper">
          ${chipsHtml}
        </div>
      </td>`;
    }
    html += `</tr>`;
  });

  // Dòng Tổng Cộng Toàn Xưởng
  if (data.work_group_summary.length > 1) {
    html += `<tr style="background: var(--dx-bg-subtle, #f1f5f9); font-weight: 700;">`;
    html += `<td class="col-grp-name" style="background: var(--dx-bg-subtle, #f1f5f9);">TỔNG CỘNG TOÀN XƯỞNG</td>`;
    html += `<td class="col-grp-total" style="background: var(--dx-bg-subtle, #f1f5f9);">${data.total_employees}</td>`;

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${curYear}-${String(curMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const dt = new Date(curYear, curMonth - 1, d);
      const isSunday = (dt.getDay() === 0);
      const isToday  = (dateStr === today);

      const totAct = data.daily_actual_total ? (data.daily_actual_total[d] || 0) : 0;
      const totPln = data.daily_planned_total ? (data.daily_planned_total[d] || 0) : 0;
      const tdClass = (isToday ? 'col-today-highlight' : '') + (isSunday ? ' bg-sunday' : '');

      let chips = '';
      if (dateStr <= today) {
        if (totAct > 0) {
          chips = `<span class="manpower-chip-act" title="Tổng phép thực tế HRM: ${totAct}">${totAct}</span>`;
        } else {
          chips = `<span class="text-muted">-</span>`;
        }
      } else {
        if (totPln > 0) {
          chips = `<span class="manpower-chip-plan" title="Tổng phép dự kiến: ${totPln}">${totPln}</span>`;
        } else {
          chips = `<span class="text-muted">-</span>`;
        }
      }

      html += `<td class="${tdClass} text-center"><div class="manpower-cell-wrapper">${chips}</div></td>`;
    }
    html += `</tr>`;
  }

  tbody.innerHTML = html;
}

/**
 * Render Ma Trận Timeline Chi Tiết (YÊU CẦU 4: Sticky Header 2 Hàng Ngày & Thứ)
 */
function renderTimeline(data) {
  const thead = document.getElementById('timelineThead');
  const tbody = document.getElementById('timelineTbody');

  const daysInMonth = data.days_in_month;
  const today = data.today;
  const curYear = data.year;
  const curMonth = data.month;

  // Hàng 1 (Ngày) & Hàng 2 (Thứ) với Sticky Top chuẩn xác
  let thRow1 = `<tr>
    <th class="col-sticky-stt" rowspan="2">STT</th>
    <th class="col-sticky-code" rowspan="2">Mã NV</th>
    <th class="col-sticky-name" rowspan="2">Họ và tên</th>`;
  let thRow2 = `<tr>`;

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${curYear}-${String(curMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const dt = new Date(curYear, curMonth - 1, d);
    const dayOfWeek = dt.getDay();
    const isSunday = (dayOfWeek === 0);
    const isToday  = (dateStr === today);

    const dowName = isSunday ? 'CN' : ('T' + (dayOfWeek + 1));
    const thClass = isToday ? 'th-today-highlight col-today-highlight' : (isSunday ? 'th-sunday bg-sunday' : '');

    thRow1 += `<th class="day-cell ${thClass}">${d}</th>`;
    thRow2 += `<th class="day-cell ${isToday ? 'col-today-highlight font-weight-bold text-primary' : (isSunday ? 'th-sunday bg-sunday' : 'text-muted')}" style="font-size:10px;">${dowName}</th>`;
  }
  thRow1 += `</tr>`;
  thRow2 += `</tr>`;
  thead.innerHTML = thRow1 + thRow2;

  // Render Tbody Timeline
  if (!data.groups || data.groups.length === 0) {
    tbody.innerHTML = `<tr><td colspan="${daysInMonth + 3}" class="text-center py-5 text-muted">Không tìm thấy nhân viên phù hợp bộ lọc</td></tr>`;
    return;
  }

  let html = '';
  let globalStt = 1;

  data.groups.forEach(grp => {
    // Header Nhóm
    html += `<tr class="group-header-row">
      <td colspan="${daysInMonth + 3}">
        <span class="material-icons align-middle me-1 text-primary" style="font-size:16px;">workspaces</span>
        <strong>${escapeHtml(grp.group_name)}</strong>
        <span class="badge bg-light text-muted border ms-2">${grp.total_emps} nhân sự</span>
      </td>
    </tr>`;

    // Nhân viên trong nhóm
    grp.employees.forEach(emp => {
      const shiftBadge = `<span class="badge-shift badge-shift-${sanitizeShift(emp.work_shift)} ms-1" style="font-size:10px;">${escapeHtml(emp.work_shift || 'Ca 1')}</span>`;
      
      html += `<tr>
        <td class="col-sticky-stt">${globalStt++}</td>
        <td class="col-sticky-code font-monospace">${escapeHtml(emp.employee_code)}</td>
        <td class="col-sticky-name">
          <div class="d-flex align-items-center justify-content-between">
            <span class="fw-bold">${escapeHtml(emp.full_name)}</span>
            ${shiftBadge}
          </div>
        </td>`;

      for (let d = 1; d <= daysInMonth; d++) {
        const cell = emp.days[d];
        const dateStr = cell.date;
        const isSunday = cell.is_sunday;
        const isToday  = cell.is_today;

        const cellClass = (isToday ? 'col-today-highlight' : '') + (isSunday ? ' bg-sunday' : '');

        let cellContent = '';
        // Quy ước màu sắc duy nhất: Xanh lá (TT) hoặc Vàng (KH)
        if (cell.state === 'actual') {
          cellContent = `<div class="leave-cell-box sq-green" title="${escapeHtml(cell.tooltip)}">TT</div>`;
        } else if (cell.state === 'plan') {
          cellContent = `<div class="leave-cell-box sq-yellow" title="${escapeHtml(cell.tooltip)}">KH</div>`;
        }

        html += `<td class="day-cell ${cellClass}">${cellContent}</td>`;
      }
      html += `</tr>`;
    });
  });

  tbody.innerHTML = html;
}

function sanitizeShift(s) {
  if (!s) return 'ca1';
  if (s.includes('1')) return 'ca1';
  if (s.includes('2')) return 'ca2';
  if (s.includes('3')) return 'ca3';
  return 'hc';
}

/**
 * 3. TAB 2: TỔNG QUAN DASHBOARD NGHỈ PHÉP (YÊU CẦU 5)
 */
async function loadDashboardData() {
  const month = document.getElementById('dashMonth').value;
  const year  = document.getElementById('dashYear').value;
  document.getElementById('dashTrendYear').textContent = year;

  try {
    const res = await fetch(`api/hrm_leave.php?action=get_dashboard&month=${month}&year=${year}`);
    const resData = await res.json();
    if (!resData.success) {
      console.error('Lỗi API Dashboard:', resData.message);
      return;
    }

    const data = resData.data;

    // 1. Cập nhật các thẻ KPI Cards
    document.getElementById('kpiEmpCount').textContent = data.kpi.employees_with_leave;
    document.getElementById('kpiDaysMonth').innerHTML = `${data.kpi.total_leave_days_month} <span style="font-size:13px;font-weight:500;">ngày</span>`;
    document.getElementById('kpiDaysYear').innerHTML = `${data.kpi.total_leave_days_year} <span style="font-size:13px;font-weight:500;">ngày</span>`;
    document.getElementById('kpiPendingPlans').textContent = data.kpi.pending_plans;
    document.getElementById('kpiActualRecords').textContent = data.kpi.actual_records;

    // 2. Render Biểu đồ 1: Xu Hướng 12 Tháng (Area Chart)
    renderDashboardMonthlyTrend(data.monthly_trend);

    // 3. Render Biểu đồ 2: Phân Bổ Theo Nhóm (Donut Chart)
    renderDashboardDeptDist(data.dept_distribution);

    // 4. Render Biểu đồ 3: Top 10 Nhân Viên (Bar Chart)
    renderDashboardTop10(data.top_employees);

    // 5. Render Biểu đồ 4: Cơ Cấu Loại Phép (Donut Chart)
    renderDashboardTypeDist(data.type_distribution);

    // 6. Render Bảng Cảnh Báo Giám Sát
    renderDashboardCriticalTable(data.critical_employees);

  } catch (err) {
    console.error('Error loadDashboardData:', err);
  }
}

function renderDashboardMonthlyTrend(trendData) {
  const options = {
    series: [
      { name: 'Nghỉ thực tế (HRM)', data: trendData.actual },
      { name: 'Nghỉ dự kiến (Kế hoạch)', data: trendData.planned }
    ],
    chart: { type: 'area', height: 280, toolbar: { show: false } },
    colors: ['#10b981', '#f59e0b'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: { categories: trendData.categories },
    yaxis: { title: { text: 'Ngày nghỉ' } },
    tooltip: { y: { formatter: val => `${val} ngày` } }
  };

  if (chartMonthlyTrendInstance) chartMonthlyTrendInstance.destroy();
  chartMonthlyTrendInstance = new ApexCharts(document.getElementById('chartLeaveMonthlyTrend'), options);
  chartMonthlyTrendInstance.render();
}

function renderDashboardDeptDist(deptData) {
  const options = {
    series: deptData.series,
    labels: deptData.labels,
    chart: { type: 'donut', height: 280 },
    colors: ['#3b82f6', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#64748b'],
    legend: { position: 'bottom' },
    tooltip: { y: { formatter: val => `${val} ngày` } }
  };

  if (chartDeptDistInstance) chartDeptDistInstance.destroy();
  chartDeptDistInstance = new ApexCharts(document.getElementById('chartLeaveDeptDist'), options);
  chartDeptDistInstance.render();
}

function renderDashboardTop10(topEmps) {
  const names = topEmps.map(e => e.full_name);
  const days  = topEmps.map(e => parseFloat(e.total_days_year));

  const options = {
    series: [{ name: 'Tổng ngày nghỉ năm', data: days }],
    chart: { type: 'bar', height: 280, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
    colors: ['#ef4444'],
    dataLabels: { enabled: true, formatter: val => `${val}d` },
    xaxis: { categories: names },
    tooltip: { y: { formatter: val => `${val} ngày` } }
  };

  if (chartTop10Instance) chartTop10Instance.destroy();
  chartTop10Instance = new ApexCharts(document.getElementById('chartLeaveTop10'), options);
  chartTop10Instance.render();
}

function renderDashboardTypeDist(typeList) {
  const labels = typeList.map(t => t.type_name);
  const series = typeList.map(t => parseFloat(t.total_days));

  const options = {
    series: series.length ? series : [1],
    labels: labels.length ? labels : ['Chưa có dữ liệu'],
    chart: { type: 'donut', height: 280 },
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
    legend: { position: 'bottom' }
  };

  if (chartTypeDistInstance) chartTypeDistInstance.destroy();
  chartTypeDistInstance = new ApexCharts(document.getElementById('chartLeaveTypeDist'), options);
  chartTypeDistInstance.render();
}

function renderDashboardCriticalTable(list) {
  const tbody = document.getElementById('dashCriticalTableBody');
  if (!list || list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-success">Không có nhân viên nào tiệm cận ngưỡng cảnh báo phép năm.</td></tr>`;
    return;
  }

  let html = '';
  list.forEach((e, idx) => {
    const pct = parseFloat(e.percent_limit) || 0;
    const barClass = (pct >= 85) ? 'bg-danger' : (pct >= 60 ? 'bg-warning' : 'bg-primary');

    html += `<tr>
      <td class="text-center">${idx + 1}</td>
      <td class="font-monospace fw-bold">${escapeHtml(e.employee_code)}</td>
      <td><strong>${escapeHtml(e.full_name)}</strong></td>
      <td><span class="badge-wg badge-wg-dun_tu">${escapeHtml(e.work_group || 'Khác')}</span></td>
      <td><span class="badge-shift badge-shift-${sanitizeShift(e.work_shift)}">${escapeHtml(e.work_shift || 'Ca 1')}</span></td>
      <td>${escapeHtml(e.cost_center || '-')}</td>
      <td class="text-end fw-bold text-danger">${e.total_days_year} d</td>
      <td class="text-end fw-bold text-success">${e.remaining_days} d</td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div class="progress flex-grow-1" style="height: 6px;">
            <div class="progress-bar ${barClass}" style="width: ${Math.min(100, pct)}%"></div>
          </div>
          <span style="font-size:11px; font-weight:700;">${pct}%</span>
        </div>
      </td>
      <td class="text-center">
        <button class="app-btn app-btn-secondary app-btn-xs" type="button" onclick="openEmpLeaveHistory('${escapeHtml(e.employee_code)}', '${escapeHtml(e.full_name)}')">
          <span class="material-icons" style="font-size:13px;">history</span> Lịch sử
        </button>
      </td>
    </tr>`;
  });
  tbody.innerHTML = html;
}

async function openEmpLeaveHistory(empCode, empName) {
  document.getElementById('histEmpName').textContent = empName;
  document.getElementById('histEmpCode').textContent = empCode;
  const tbody = document.getElementById('histTableBody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">Đang tải lịch sử nghỉ phép...</td></tr>`;

  const modal = new bootstrap.Modal(document.getElementById('modalEmpLeaveHistory'));
  modal.show();

  try {
    const year = document.getElementById('dashYear').value;
    const res = await fetch(`api/hrm_leave.php?action=get_hrm_summary&year=${year}&search=${encodeURIComponent(empCode)}&limit=100`);
    const data = await res.json();

    if (!data.success || !data.data || data.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">Chưa ghi nhận lịch sử nghỉ phép trong năm ${year}</td></tr>`;
      return;
    }

    let h = '';
    data.data.forEach((r, idx) => {
      h += `<tr>
        <td class="text-center">${idx + 1}</td>
        <td><strong>${r.leave_date}</strong></td>
        <td><span class="badge bg-light text-primary border">${escapeHtml(r.leave_type || 'Phép năm')}</span></td>
        <td>${escapeHtml(r.leave_shift || 'Cả ngày')}</td>
        <td class="text-end fw-bold">${r.leave_days} d</td>
        <td>${escapeHtml(r.reason || '-')}</td>
        <td><span class="small text-muted">${escapeHtml(r.direct_manager || 'HRM Sync')}</span></td>
      </tr>`;
    });
    tbody.innerHTML = h;
  } catch (err) {
    console.error('Error openEmpLeaveHistory:', err);
  }
}

/**
 * 4. TAB 3: TỔNG HỢP TOÀN BỘ PHÉP HRM & MA TRẬN 12 THÁNG (YÊU CẦU 5)
 */
function switchSummaryViewMode(mode) {
  currentSummaryViewMode = mode;
  document.getElementById('sumViewBtnList').classList.toggle('active', mode === 'list');
  document.getElementById('sumViewBtnMatrix').classList.toggle('active', mode === 'matrix12m');

  document.getElementById('sumListContainer').style.display   = (mode === 'list') ? 'block' : 'none';
  document.getElementById('sumMatrixContainer').style.display = (mode === 'matrix12m') ? 'block' : 'none';

  // Ẩn chọn tháng nếu ở chế độ ma trận 12 tháng
  document.getElementById('sumMonthFilterContainer').style.display = (mode === 'list') ? 'flex' : 'none';

  loadCurrentSummaryView();
}

function loadCurrentSummaryView() {
  if (currentSummaryViewMode === 'list') {
    loadHrmSummaryList(1);
  } else {
    loadMonthlyMatrixData();
  }
}

async function loadHrmSummaryList(page = 1) {
  const year   = document.getElementById('sumYear').value;
  const month  = document.getElementById('sumMonth').value;
  const group  = document.getElementById('sumGroup').value;
  const shift  = document.getElementById('sumShift').value;
  const search = document.getElementById('sumSearch').value;

  const tbody = document.getElementById('sumListTbody');
  tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-muted">Đang tải danh sách phép HRM...</td></tr>`;

  try {
    const res = await fetch(`api/hrm_leave.php?action=get_hrm_summary&year=${year}&month=${month}&group_name=${encodeURIComponent(group)}&shift=${encodeURIComponent(shift)}&search=${encodeURIComponent(search)}&page=${page}&limit=50`);
    const data = await res.json();

    if (!data.success) {
      alert(data.message || 'Lỗi khi tải dữ liệu tổng hợp');
      return;
    }

    document.getElementById('sumListTotalRowsBadge').textContent = `${data.summary.total_rows} bản ghi (${data.summary.distinct_employees} NV)`;
    document.getElementById('sumListDaysTotalText').innerHTML = `Tổng ngày nghỉ: <strong class="text-danger">${data.summary.total_leave_days}</strong> ngày`;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-muted">Không tìm thấy bản ghi nghỉ phép nào phù hợp</td></tr>`;
      document.getElementById('sumListPageBtns').innerHTML = '';
      return;
    }

    let html = '';
    const startIndex = (data.pagination.current_page - 1) * data.pagination.limit + 1;
    data.data.forEach((r, idx) => {
      html += `<tr>
        <td class="text-center">${startIndex + idx}</td>
        <td class="font-monospace fw-bold text-primary">${escapeHtml(r.employee_code)}</td>
        <td><strong>${escapeHtml(r.full_name)}</strong></td>
        <td><span class="badge-wg badge-wg-dun_tu">${escapeHtml(r.final_work_group || 'Khác')}</span></td>
        <td><span class="badge-shift badge-shift-${sanitizeShift(r.final_work_shift)}">${escapeHtml(r.final_work_shift || 'Ca 1')}</span></td>
        <td>${escapeHtml(r.leave_type || 'Phép năm')}</td>
        <td>${escapeHtml(r.leave_shift || 'Cả ngày')}</td>
        <td class="text-center">${r.start_date || r.leave_date}</td>
        <td class="text-center">${r.end_date || r.leave_date}</td>
        <td class="text-end fw-bold text-danger">${r.leave_days} d</td>
        <td><small>${escapeHtml(r.reason || '-')}</small></td>
        <td><small class="text-muted">${escapeHtml(r.direct_manager || '-')}</small></td>
        <td class="text-center small text-muted">${r.synced_at ? r.synced_at.substring(0, 16) : '-'}</td>
      </tr>`;
    });
    tbody.innerHTML = html;

    renderSummaryPagination(data.pagination);
  } catch (err) {
    console.error('Error loadHrmSummaryList:', err);
  }
}

function renderSummaryPagination(p) {
  const info = document.getElementById('sumListPageInfo');
  const btns = document.getElementById('sumListPageBtns');
  info.textContent = `Trang ${p.current_page} / ${p.total_pages} (Tổng ${p.total_rows} dòng)`;

  let bHtml = '';
  if (p.current_page > 1) {
    bHtml += `<button class="app-btn app-btn-outline app-btn-xs" onclick="loadHrmSummaryList(${p.current_page - 1})">Trước</button>`;
  }
  for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.total_pages, p.current_page + 2); i++) {
    bHtml += `<button class="app-btn app-btn-xs ${i === p.current_page ? 'app-btn-primary' : 'app-btn-outline'}" onclick="loadHrmSummaryList(${i})">${i}</button>`;
  }
  if (p.current_page < p.total_pages) {
    bHtml += `<button class="app-btn app-btn-outline app-btn-xs" onclick="loadHrmSummaryList(${p.current_page + 1})">Sau</button>`;
  }
  btns.innerHTML = bHtml;
}

async function loadMonthlyMatrixData() {
  const year   = document.getElementById('sumYear').value;
  const group  = document.getElementById('sumGroup').value;
  const shift  = document.getElementById('sumShift').value;
  const search = document.getElementById('sumSearch').value;

  document.getElementById('sumMatrixYearBadge').textContent = `Năm ${year}`;
  const tbody = document.getElementById('sumMatrixTbody');
  const tfoot = document.getElementById('sumMatrixTfoot');
  tbody.innerHTML = `<tr><td colspan="18" class="text-center py-4 text-muted">Đang tải ma trận 12 tháng...</td></tr>`;

  try {
    const res = await fetch(`api/hrm_leave.php?action=get_monthly_matrix&year=${year}&group_name=${encodeURIComponent(group)}&shift=${encodeURIComponent(shift)}&search=${encodeURIComponent(search)}`);
    const data = await res.json();

    if (!data.success || !data.employees || data.employees.length === 0) {
      tbody.innerHTML = `<tr><td colspan="18" class="text-center py-4 text-muted">Không tìm thấy dữ liệu nhân viên</td></tr>`;
      tfoot.innerHTML = '';
      return;
    }

    let html = '';
    data.employees.forEach((e, idx) => {
      html += `<tr>
        <td class="text-center">${idx + 1}</td>
        <td class="font-monospace fw-bold text-primary">${escapeHtml(e.employee_code)}</td>
        <td><strong>${escapeHtml(e.full_name)}</strong></td>
        <td><span class="badge-wg badge-wg-dun_tu">${escapeHtml(e.work_group)}</span></td>
        <td class="text-center"><span class="badge-shift badge-shift-${sanitizeShift(e.work_shift)}">${escapeHtml(e.work_shift)}</span></td>`;

      for (let m = 1; m <= 12; m++) {
        const val = e.months[m] || 0;
        const cellStyle = (val > 0) ? 'font-weight:700; color: #dc2626; background: rgba(239, 68, 68, 0.05);' : 'color:#94a3b8;';
        html += `<td class="text-center" style="${cellStyle}">${val > 0 ? val : '-'}</td>`;
      }

      html += `<td class="text-center fw-bold text-primary" style="background: rgba(59, 130, 246, 0.08);">${e.total_year} d</td>`;
      html += `</tr>`;
    });
    tbody.innerHTML = html;

    // Tfoot Tổng Cộng Toàn Xưởng
    let footHtml = `<tr>
      <td colspan="5" class="text-end fw-bold">TỔNG CỘNG TOÀN XƯỞNG:</td>`;
    for (let m = 1; m <= 12; m++) {
      footHtml += `<td class="text-center fw-bold">${data.monthly_totals[m]}</td>`;
    }
    footHtml += `<td class="text-center fw-bold text-danger" style="background: rgba(239, 68, 68, 0.1); font-size:13px;">${data.grand_total} d</td>`;
    footHtml += `</tr>`;
    tfoot.innerHTML = footHtml;

  } catch (err) {
    console.error('Error loadMonthlyMatrixData:', err);
  }
}

/**
 * 5. MODAL: QUẢN LÝ PHÂN BỐ NHÓM LÀM VIỆC & CA (YÊU CẦU 1 & 3)
 */
function openWorkGroupMatrixModal() {
  const modal = new bootstrap.Modal(document.getElementById('modalWorkGroupMatrix'));
  modal.show();
  loadWorkGroupMatrixData();
}

function setMatrixViewMode(mode) {
  currentMatrixViewMode = mode;
  document.getElementById('matrixViewBtnTable').classList.toggle('active', mode === 'table');
  document.getElementById('matrixViewBtnKanban').classList.toggle('active', mode === 'kanban');

  document.getElementById('matrixTableViewWrapper').style.display  = (mode === 'table') ? 'block' : 'none';
  document.getElementById('matrixKanbanViewWrapper').style.display = (mode === 'kanban') ? 'grid' : 'none';
}

async function loadWorkGroupMatrixData() {
  try {
    const res = await fetch(`api/hrm_leave.php?action=get_group_allocations`);
    const data = await res.json();
    if (!data.success) {
      alert(data.message || 'Lỗi khi tải dữ liệu phân bổ');
      return;
    }
    cachedMatrixEmployees = data.table_employees || [];
    document.getElementById('matrixTotalEmpsBadge').textContent = `${data.total_employees} nhân sự`;

    applyMatrixFilter();
    renderMatrixKanbanBoard(data.groups || []);
  } catch (err) {
    console.error('Error loadWorkGroupMatrixData:', err);
  }
}

function applyMatrixFilter() {
  const kw    = (document.getElementById('matrixEmpSearch').value || '').toLowerCase().trim();
  const group = document.getElementById('matrixFilterGroup').value;
  const shift = document.getElementById('matrixFilterShift').value;

  const filtered = cachedMatrixEmployees.filter(e => {
    const matchKw = !kw || e.employee_code.toLowerCase().includes(kw) || e.full_name.toLowerCase().includes(kw);
    const matchGrp = !group || e.work_group === group;
    const matchShf = !shift || e.work_shift === shift;
    return matchKw && matchGrp && matchShf;
  });

  renderMatrixTable(filtered);
}

/**
 * Render Bảng Danh Sách Phân Bổ Nhóm (YÊU CẦU 1: Dạng Bảng + Nút Chỉnh Sửa trên từng dòng)
 */
function renderMatrixTable(list) {
  const tbody = document.getElementById('matrixTableBody');
  if (!list || list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Không tìm thấy nhân viên nào phù hợp</td></tr>`;
    return;
  }

  let html = '';
  list.forEach((e, idx) => {
    html += `<tr>
      <td class="text-center">
        <input type="checkbox" class="matrix-emp-check" value="${escapeHtml(e.employee_code)}" onchange="updateMatrixSelectedCount()">
      </td>
      <td class="text-center">${idx + 1}</td>
      <td class="font-monospace fw-bold text-primary">${escapeHtml(e.employee_code)}</td>
      <td><strong>${escapeHtml(e.full_name)}</strong></td>
      <td>${escapeHtml(e.cost_center || '-')}</td>
      <td><span class="badge-wg badge-wg-dun_tu">${escapeHtml(e.work_group)}</span></td>
      <td><span class="badge-shift badge-shift-${sanitizeShift(e.work_shift)}">${escapeHtml(e.work_shift)}</span></td>
      <td class="text-center">
        <button class="app-btn app-btn-secondary app-btn-xs" type="button" onclick="openEditSingleEmployeeModal('${escapeHtml(e.employee_code)}', '${escapeHtml(e.full_name)}', '${escapeHtml(e.work_group)}', '${escapeHtml(e.work_shift)}')">
          <span class="material-icons" style="font-size:14px;">edit</span>
          <span>Chỉnh sửa</span>
        </button>
      </td>
    </tr>`;
  });
  tbody.innerHTML = html;
  updateMatrixSelectedCount();
}

function toggleSelectAllMatrix(master) {
  document.querySelectorAll('.matrix-emp-check').forEach(cb => {
    cb.checked = master.checked;
  });
  updateMatrixSelectedCount();
}

function updateMatrixSelectedCount() {
  const checked = document.querySelectorAll('.matrix-emp-check:checked');
  document.getElementById('matrixSelectedCount').textContent = `Đã chọn: ${checked.length} nhân viên`;
}

/**
 * Modal Chỉnh sửa thông tin 1 nhân viên (YÊU CẦU 1)
 */
function openEditSingleEmployeeModal(code, name, group, shift) {
  document.getElementById('editEmpCode').value = code;
  document.getElementById('editEmpCodeLabel').textContent = code;
  document.getElementById('editEmpNameLabel').textContent = name;
  document.getElementById('editEmpGroup').value = group || 'Đùn TU';
  document.getElementById('editEmpShift').value = shift || 'Ca 1';

  const modal = new bootstrap.Modal(document.getElementById('modalEditSingleEmployee'));
  modal.show();
}

async function submitSingleEmployeeEdit(e) {
  e.preventDefault();
  const code  = document.getElementById('editEmpCode').value;
  const group = document.getElementById('editEmpGroup').value;
  const shift = document.getElementById('editEmpShift').value;

  const btn = document.getElementById('btnSubmitEditSingle');
  btn.disabled = true;
  btn.textContent = 'Đang lưu...';

  try {
    const formData = new FormData();
    formData.append('action', 'update_single_employee_allocation');
    formData.append('employee_code', code);
    formData.append('work_group', group);
    formData.append('work_shift', shift);

    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalEditSingleEmployee')).hide();
      loadWorkGroupMatrixData();
      if (currentMainTab === 'timeline') loadTimelineData();
    } else {
      alert(data.message || 'Lỗi khi cập nhật nhân viên');
    }
  } catch (err) {
    console.error('Error submitSingleEmployeeEdit:', err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu thay đổi';
  }
}

/**
 * Thao tác chuyển Nhóm / Ca Hàng Loạt (YÊU CẦU 3: Cấu hình ca trực tiếp)
 */
async function submitBulkMatrixMove() {
  const checked = Array.from(document.querySelectorAll('.matrix-emp-check:checked')).map(cb => cb.value);
  if (checked.length === 0) {
    alert('Vui lòng tích chọn ít nhất 1 nhân viên cần phân bổ.');
    return;
  }

  const targetGroup = document.getElementById('matrixBulkTargetGroup').value;
  const targetShift = document.getElementById('matrixBulkTargetShift').value;

  if (!targetGroup && !targetShift) {
    alert('Vui lòng chọn nhóm làm việc hoặc ca làm việc mới cần áp dụng.');
    return;
  }

  if (!confirm(`Bạn có chắc chắn muốn cập nhật phân bổ cho ${checked.length} nhân viên đã chọn?`)) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append('action', 'update_employee_work_group');
    if (targetGroup) formData.append('work_group', targetGroup);
    if (targetShift) formData.append('work_shift', targetShift);
    checked.forEach(code => formData.append('employee_codes[]', code));

    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      alert(data.message);
      loadWorkGroupMatrixData();
      if (currentMainTab === 'timeline') loadTimelineData();
    } else {
      alert(data.message || 'Lỗi khi phân bổ hàng loạt');
    }
  } catch (err) {
    console.error('Error submitBulkMatrixMove:', err);
  }
}

function renderMatrixKanbanBoard(groups) {
  const container = document.getElementById('matrixKanbanViewWrapper');
  if (!groups || groups.length === 0) {
    container.innerHTML = `<div class="text-center py-4 text-muted w-100">Không có nhóm nào</div>`;
    return;
  }

  let html = '';
  groups.forEach(g => {
    html += `<div class="workgroup-column">
      <div class="workgroup-col-header" style="background: rgba(59, 130, 246, 0.08); border-color: ${g.color_code || '#3b82f6'};">
        <span class="fw-bold">${escapeHtml(g.group_name)}</span>
        <span class="badge bg-white text-primary border">${g.count || 0} NV</span>
      </div>
      <div class="workgroup-col-body">`;

    (g.employees || []).forEach(emp => {
      html += `<div class="emp-matrix-card">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="emp-code-badge">${escapeHtml(emp.employee_code)}</span>
          <span class="badge-shift badge-shift-${sanitizeShift(emp.work_shift)}">${escapeHtml(emp.work_shift || 'Ca 1')}</span>
        </div>
        <div class="fw-bold text-main small">${escapeHtml(emp.full_name)}</div>
      </div>`;
    });

    html += `</div></div>`;
  });
  container.innerHTML = html;
}

/**
 * 6. TAB 4: ĐĂNG KÝ & DUYỆT PHÉP KẾ HOẠCH
 */
let currentPlansPage = 1;

async function loadPlansData(page = 1) {
  currentPlansPage = page;
  const status = document.getElementById('planStatusFilter').value;
  const search = document.getElementById('planSearchInput').value;

  const tbody = document.getElementById('plansTbody');
  tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-muted">Đang tải danh sách phép...</td></tr>`;

  try {
    const res = await fetch(`api/hrm_leave.php?action=get_plans&status=${status}&search=${encodeURIComponent(search)}&page=${page}&limit=25`);
    const data = await res.json();

    if (!data.success) {
      alert(data.message || 'Lỗi khi tải danh sách kế hoạch');
      return;
    }

    // Cập nhật badge chờ duyệt
    const pendingCount = (status === 'pending' || status === 'all') ? data.pagination.total_rows : 0;
    const badge = document.getElementById('badgePendingCount');
    if (badge) badge.textContent = `${pendingCount} chờ duyệt`;

    renderPlansTable(data.data, data.pagination);
  } catch (err) {
    console.error('Error loadPlansData:', err);
  }
}

function renderPlansTable(list, pagination) {
  const tbody = document.getElementById('plansTbody');
  if (!list || list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-muted">Không có đơn phép kế hoạch nào</td></tr>`;
    document.getElementById('plansPageBtns').innerHTML = '';
    return;
  }

  const canApprove = hasPermission(['hrm.leave_approve', 'hrm.leave_manage', 'admin']);

  let html = '';
  const startIndex = (pagination.current_page - 1) * pagination.limit + 1;
  list.forEach((p, idx) => {
    let statusBadge = '<span class="badge-status badge-pending">Chờ duyệt</span>';
    if (p.status === 'approved') statusBadge = '<span class="badge-status badge-approved">Đã duyệt</span>';
    if (p.status === 'rejected') statusBadge = '<span class="badge-status badge-rejected">Từ chối</span>';

    let actionBtns = '';
    if (p.status === 'pending' && canApprove) {
      actionBtns = `
        <button class="app-btn app-btn-success app-btn-xs" onclick="approvePlan(${p.id})" title="Phê duyệt">
          <span class="material-icons" style="font-size:14px;">check</span>
        </button>
        <button class="app-btn app-btn-danger app-btn-xs" onclick="openRejectModal(${p.id})" title="Từ chối">
          <span class="material-icons" style="font-size:14px;">close</span>
        </button>
      `;
    }

    html += `<tr>
      ${canApprove ? `<td class="text-center">${p.status === 'pending' ? `<input type="checkbox" class="plan-check-item" value="${p.id}" onchange="updateBulkButton()">` : ''}</td>` : ''}
      <td class="text-center">${startIndex + idx}</td>
      <td class="font-monospace fw-bold text-primary">${escapeHtml(p.employee_code)}</td>
      <td><strong>${escapeHtml(p.full_name)}</strong></td>
      <td><span class="badge-wg badge-wg-dun_tu">${escapeHtml(p.work_group || 'Khác')}</span></td>
      <td class="text-center"><strong>${p.leave_date}</strong></td>
      <td class="text-center">${p.session_type === 'all_day' ? 'Cả ngày' : (p.session_type === 'morning' ? 'Sáng' : 'Chiều')}</td>
      <td>${escapeHtml(p.leave_type || 'Phép năm')}</td>
      <td><small>${escapeHtml(p.reason || '-')}</small></td>
      <td class="text-center">${statusBadge}</td>
      <td><small class="text-muted">${escapeHtml(p.approved_by || '-')}</small></td>
      <td class="text-center">${actionBtns}</td>
    </tr>`;
  });

  tbody.innerHTML = html;
  renderPagination(pagination);
  updateBulkButton();
}

function renderPagination(p) {
  const info = document.getElementById('plansPageInfo');
  const btns = document.getElementById('plansPageBtns');
  info.textContent = `Trang ${p.current_page} / ${p.total_pages} (Tổng ${p.total_rows} đơn)`;

  let bHtml = '';
  if (p.current_page > 1) {
    bHtml += `<button class="app-btn app-btn-outline app-btn-xs" onclick="loadPlansData(${p.current_page - 1})">Trước</button>`;
  }
  for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.total_pages, p.current_page + 2); i++) {
    bHtml += `<button class="app-btn app-btn-xs ${i === p.current_page ? 'app-btn-primary' : 'app-btn-outline'}" onclick="loadPlansData(${i})">${i}</button>`;
  }
  if (p.current_page < p.total_pages) {
    bHtml += `<button class="app-btn app-btn-outline app-btn-xs" onclick="loadPlansData(${p.current_page + 1})">Sau</button>`;
  }
  btns.innerHTML = bHtml;
}

function updateBulkButton() {
  const checked = document.querySelectorAll('.plan-check-item:checked');
  const btn = document.getElementById('btnBulkApprove');
  if (btn) {
    btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    const txt = document.getElementById('bulkApproveText');
    if (txt) txt.textContent = `Duyệt đã chọn (${checked.length})`;
  }
}

function toggleSelectAllPlans(master) {
  document.querySelectorAll('.plan-check-item').forEach(cb => {
    cb.checked = master.checked;
  });
  updateBulkButton();
}

async function approvePlan(id) {
  if (!confirm('Bạn có chắc chắn muốn phê duyệt đơn phép này?')) return;
  try {
    const formData = new FormData();
    formData.append('action', 'approve_plan');
    formData.append('plan_id', id);

    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      loadPlansData(currentPlansPage);
      if (cachedTimelineData) loadTimelineData();
    } else {
      alert(data.message || 'Lỗi khi duyệt');
    }
  } catch (err) {
    console.error('Error approvePlan:', err);
  }
}

async function bulkApprovePlans() {
  const ids = Array.from(document.querySelectorAll('.plan-check-item:checked')).map(cb => cb.value);
  if (ids.length === 0) return;
  if (!confirm(`Phê duyệt đồng thời ${ids.length} đơn phép đã chọn?`)) return;

  try {
    const formData = new FormData();
    formData.append('action', 'approve_plan');
    ids.forEach(id => formData.append('plan_ids[]', id));

    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      loadPlansData(currentPlansPage);
      if (cachedTimelineData) loadTimelineData();
    } else {
      alert(data.message || 'Lỗi khi duyệt');
    }
  } catch (err) {
    console.error('Error bulkApprovePlans:', err);
  }
}

function openRejectModal(id) {
  document.getElementById('rejectPlanId').value = id;
  document.getElementById('rejectReason').value = '';
  const modal = new bootstrap.Modal(document.getElementById('modalRejectLeave'));
  modal.show();
}

async function submitRejectLeave(e) {
  e.preventDefault();
  const id = document.getElementById('rejectPlanId').value;
  const reason = document.getElementById('rejectReason').value;

  try {
    const formData = new FormData();
    formData.append('action', 'reject_plan');
    formData.append('plan_id', id);
    formData.append('reason', reason);

    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalRejectLeave')).hide();
      loadPlansData(currentPlansPage);
    } else {
      alert(data.message || 'Lỗi khi từ chối');
    }
  } catch (err) {
    console.error('Error submitRejectLeave:', err);
  }
}

/**
 * 7. MODAL: ĐĂNG KÝ PHÉP KẾ HOẠCH & AUTOCOMPLETE
 */
function openPlanModal() {
  const modal = new bootstrap.Modal(document.getElementById('modalPlanLeave'));
  modal.show();
}

let searchDebounceTimer = null;
let autocompleteItemsCache = [];

function handleEmpSearchInput(kw) {
  clearTimeout(searchDebounceTimer);
  const box = document.getElementById('empSuggestionBox');
  if (!kw || kw.trim().length < 2) {
    box.style.display = 'none';
    return;
  }
  searchDebounceTimer = setTimeout(async () => {
    try {
      const res = await fetch(`api/hrm_leave.php?action=get_employees&search=${encodeURIComponent(kw)}`);
      const data = await res.json();
      if (data.success && data.employees) {
        autocompleteItemsCache = data.employees;
        renderAutocompleteItems(data.employees, kw);
      }
    } catch (e) {
      console.error(e);
    }
  }, 250);
}

function renderAutocompleteItems(items, kw) {
  const box = document.getElementById('empSuggestionBox');
  if (!items || items.length === 0) {
    box.innerHTML = `<div class="p-2 small text-muted text-center">Không tìm thấy nhân viên nào</div>`;
    box.style.display = 'block';
    return;
  }

  let html = '';
  items.forEach((emp, idx) => {
    html += `<div class="autocomplete-item" onclick="selectEmployee(${idx})">
      <div>
        <strong class="text-primary font-monospace">${escapeHtml(emp.employee_code)}</strong> - 
        <span class="text-main fw-bold">${escapeHtml(emp.full_name)}</span>
        <div class="small text-muted">${escapeHtml(emp.cost_center || '')}</div>
      </div>
      <span class="badge-wg badge-wg-dun_tu">${escapeHtml(emp.work_group || 'Khác')}</span>
    </div>`;
  });
  box.innerHTML = html;
  box.style.display = 'block';
}

function selectEmployee(idx) {
  const emp = autocompleteItemsCache[idx];
  if (!emp) return;

  document.getElementById('planEmpCode').value = emp.employee_code;
  document.getElementById('planFullName').value = emp.full_name;
  document.getElementById('planWorkGroup').value = emp.work_group || 'Đùn TU';

  document.getElementById('planSelectedName').textContent = `${emp.employee_code} - ${emp.full_name}`;
  document.getElementById('planSelectedSub').textContent = `${emp.cost_center || ''} | Nhóm: ${emp.work_group || ''}`;
  document.getElementById('planSelectedAvatar').textContent = emp.full_name.substring(0, 2).toUpperCase();

  document.getElementById('planSelectedCard').style.display = 'flex !important';
  document.getElementById('empSuggestionBox').style.display = 'none';
  document.getElementById('planEmpSearch').value = '';
}

function clearSelectedEmployee() {
  document.getElementById('planEmpCode').value = '';
  document.getElementById('planFullName').value = '';
  document.getElementById('planSelectedCard').style.display = 'none !important';
}

function syncEndDate(startVal) {
  const endInput = document.getElementById('planEndDate');
  if (endInput && !endInput.value) {
    endInput.value = startVal;
  }
}

function handleEmpSearchKeydown(e) {
  if (e.key === 'Escape') {
    document.getElementById('empSuggestionBox').style.display = 'none';
  }
}

async function submitPlanLeave(e) {
  e.preventDefault();
  const form = document.getElementById('formPlanLeave');
  const formData = new FormData(form);
  formData.append('action', 'create_plan');

  const btn = document.getElementById('btnSubmitPlan');
  btn.disabled = true;
  btn.textContent = 'Đang gửi...';

  try {
    const res = await fetch('api/hrm_leave.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      bootstrap.Modal.getInstance(document.getElementById('modalPlanLeave')).hide();
      form.reset();
      clearSelectedEmployee();
      if (currentMainTab === 'plans') loadPlansData(1);
      if (currentMainTab === 'timeline') loadTimelineData();
    } else {
      alert(data.message || 'Lỗi khi đăng ký phép');
    }
  } catch (err) {
    console.error('Error submitPlanLeave:', err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Gửi Đăng Ký Phép';
  }
}

/**
 * 8. TAB 5: ĐỒNG BỘ HRM & FILE EXCEL (NẾU CÓ QUYỀN)
 */
async function loadSyncConfig() {
  try {
    const res = await fetch('api/hrm_leave_sync.php?action=get_config');
    const data = await res.json();
    if (data.success && data.config) {
      const cfg = data.config;
      if (document.getElementById('cfgHrmUrl')) document.getElementById('cfgHrmUrl').value = cfg.hrm_url || '';
      if (document.getElementById('cfgLoginEndpoint')) document.getElementById('cfgLoginEndpoint').value = cfg.login_endpoint || '';
      if (document.getElementById('cfgAuthEndpoint')) document.getElementById('cfgAuthEndpoint').value = cfg.auth_endpoint || '';
      if (document.getElementById('cfgPortalEndpoint')) document.getElementById('cfgPortalEndpoint').value = cfg.portal_endpoint || '';
      if (document.getElementById('cfgExportEndpoint')) document.getElementById('cfgExportEndpoint').value = cfg.export_endpoint || '';
      if (document.getElementById('lastSyncTime')) document.getElementById('lastSyncTime').textContent = cfg.last_sync_time || 'Chưa chạy';
    }
  } catch (e) {
    console.error(e);
  }
}

async function saveHrmConfig(e) {
  e.preventDefault();
  const form = document.getElementById('formHrmConfig');
  const formData = new FormData(form);
  formData.append('action', 'save_config');

  try {
    const res = await fetch('api/hrm_leave_sync.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message || 'Đã lưu cấu hình HRM');
  } catch (err) {
    console.error(err);
  }
}

async function resetHrmConfigToDefault() {
  if (!confirm('Khôi phục endpoints kết nối HRM về mặc định chuẩn?')) return;
  try {
    const res = await fetch('api/hrm_leave_sync.php?action=reset_config');
    const data = await res.json();
    alert(data.message || 'Đã khôi phục');
    loadSyncConfig();
  } catch (e) {
    console.error(e);
  }
}

async function loadHrmAccounts() {
  const tbody = document.getElementById('hrmAccountsTbody');
  if (!tbody) return;
  try {
    const res = await fetch('api/hrm_leave_sync.php?action=get_accounts');
    const data = await res.json();
    if (!data.success || !data.accounts || data.accounts.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">Chưa có tài khoản HRM nào được cấu hình</td></tr>`;
      return;
    }
    let html = '';
    data.accounts.forEach(a => {
      html += `<tr>
        <td>${a.id}</td>
        <td><strong>${escapeHtml(a.account_name)}</strong></td>
        <td class="font-monospace">${escapeHtml(a.username)}</td>
        <td class="text-center">
          <span class="badge ${a.is_active == 1 ? 'bg-success' : 'bg-secondary'}">${a.is_active == 1 ? 'Bật' : 'Tắt'}</span>
        </td>
        <td class="text-center">
          <button class="app-btn app-btn-outline app-btn-xs" onclick="testHrmAccount(${a.id}, '${escapeHtml(a.username)}')">Test</button>
          <button class="app-btn app-btn-danger app-btn-xs" onclick="deleteHrmAccount(${a.id})">Xóa</button>
        </td>
      </tr>`;
    });
    tbody.innerHTML = html;
  } catch (e) {
    console.error(e);
  }
}

function openHrmAccountModal() {
  document.getElementById('accFormId').value = '0';
  document.getElementById('accFormName').value = '';
  document.getElementById('accFormUser').value = '';
  document.getElementById('accFormPass').value = '';
  const modal = new bootstrap.Modal(document.getElementById('modalHrmAccount'));
  modal.show();
}

async function submitHrmAccount(e) {
  e.preventDefault();
  const form = document.getElementById('formHrmAccount');
  const formData = new FormData(form);
  formData.append('action', 'save_account');

  try {
    const res = await fetch('api/hrm_leave_sync.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalHrmAccount')).hide();
      loadHrmAccounts();
    } else {
      alert(data.message || 'Lỗi lưu tài khoản');
    }
  } catch (err) {
    console.error(err);
  }
}

async function deleteHrmAccount(id) {
  if (!confirm('Xóa tài khoản kết nối HRM này?')) return;
  try {
    const formData = new FormData();
    formData.append('action', 'delete_account');
    formData.append('id', id);
    const res = await fetch('api/hrm_leave_sync.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) loadHrmAccounts();
  } catch (e) {
    console.error(e);
  }
}

async function testHrmAccount(id, user) {
  logTerminal(`[TEST] Đang kiểm tra đăng nhập tài khoản [${user}]...`, 'info');
  try {
    const res = await fetch(`api/hrm_leave_sync.php?action=test_account&id=${id}`);
    const data = await res.json();
    logTerminal(`[TEST KẾT QUẢ] ${data.message}`, data.success ? 'success' : 'error');
  } catch (e) {
    logTerminal(`[TEST LỖI] ${e.message}`, 'error');
  }
}

async function triggerHrmSync() {
  const dateFrom = document.getElementById('syncDateFrom').value;
  const dateTo   = document.getElementById('syncDateTo').value;

  const btn = document.getElementById('btnTriggerSync');
  btn.disabled = true;
  btn.textContent = 'Đang đồng bộ HRM...';

  logTerminal(`Bắt đầu đồng bộ tự động HRM từ ngày ${dateFrom} đến ${dateTo}...`, 'info');

  try {
    const formData = new FormData();
    formData.append('action', 'trigger_sync');
    formData.append('date_from', dateFrom);
    formData.append('date_to', dateTo);

    const res = await fetch('api/hrm_leave_sync.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      const ins = data.inserted_count ?? (data.stats ? data.stats.inserted : 0);
      const upd = data.updated_count ?? (data.stats ? data.stats.updated : 0);
      logTerminal(`Đồng bộ HRM hoàn tất! Đã nạp ${ins} bản ghi mới, cập nhật ${upd} bản ghi.`, 'success');
      loadTimelineData();
      checkLeaveAutoSyncSchedule();
    } else {
      logTerminal(`Đồng bộ thất bại: ${data.message}`, 'error');
    }
  } catch (err) {
    logTerminal(`Lỗi ngoại lệ: ${err.message}`, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<span class="material-icons">sync</span><span>Bắt đầu Đồng Bộ HRM Ngay</span>`;
  }
}

async function handleManualExcelUpload(e) {
  e.preventDefault();
  const fileInput = document.getElementById('manualExcelFile');
  if (!fileInput.files.length) return;

  const btn = document.getElementById('btnUploadExcel');
  btn.disabled = true;
  btn.textContent = 'Đang tải lên & xử lý...';

  const formData = new FormData();
  formData.append('action', 'upload_excel');
  formData.append('excel_file', fileInput.files[0]);

  try {
    const res = await fetch('api/hrm_leave_sync.php', { method: 'POST', body: formData });
    const data = await res.json();

    const alertBox = document.getElementById('uploadResultAlert');
    alertBox.style.display = 'block';
    alertBox.className = `alert alert-${data.success ? 'success' : 'danger'} small`;
    alertBox.textContent = data.message;

    if (data.success) {
      fileInput.value = '';
      loadTimelineData();
    }
  } catch (err) {
    console.error('Error handleManualExcelUpload:', err);
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<span class="material-icons">upload</span><span>Bắt Đầu Nạp Dữ Liệu Excel</span>`;
  }
}

function logTerminal(msg, type = 'info') {
  const terminal = document.getElementById('syncTerminal');
  if (!terminal) return;
  const time = new Date().toTimeString().split(' ')[0];
  const typeClass = type === 'success' ? 'log-success' : (type === 'error' ? 'log-error' : (type === 'warning' ? 'log-warning' : ''));
  terminal.innerHTML += `<div class="log-line"><span class="log-time">[${time}]</span> <span class="${typeClass}">${escapeHtml(msg)}</span></div>`;
  terminal.scrollTop = terminal.scrollHeight;
}

function clearSyncTerminal() {
  const terminal = document.getElementById('syncTerminal');
  if (terminal) terminal.innerHTML = '';
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
}
</script>

