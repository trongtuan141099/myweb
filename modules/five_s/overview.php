<?php
// modules/five_s/overview.php
// All-in-One 5S Central Workspace (Navigator Hub)

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/check_permission.php';

// Enforce standard authentication and permission check
requirePermission(['five_s.view', 'five_s.audit', 'five_s.settings', 'five_s.proposals']);

// Capability flags for current user
$can5sView = hasPermission('five_s.view');
$can5sAudit = hasPermission('five_s.audit');
$can5sSet = hasPermission('five_s.settings');
$can5sProps = hasPermission('five_s.proposals');

$currentMonth = date('Y-m');
$currentDate = date('Y-m-d');
$initialTab = trim($_GET['tab'] ?? '');
$initialZoneId = intval($_GET['zone_id'] ?? 0);
$initialZoneCode = trim($_GET['zone_code'] ?? '');

// Filter permitted tabs
$permittedTabs = [];
if ($can5sView) $permittedTabs[] = 'dashboard';
if ($can5sAudit) $permittedTabs[] = 'audit';
if ($can5sView || $can5sAudit || $can5sSet) $permittedTabs[] = 'issues';
if ($can5sView || $can5sSet) {
    $permittedTabs[] = 'plans';
    $permittedTabs[] = 'zones';
    $permittedTabs[] = 'standards';
}
if ($can5sView || $can5sAudit || $can5sSet) $permittedTabs[] = 'qrcode';

if (!in_array($initialTab, $permittedTabs, true)) {
    $initialTab = !empty($permittedTabs) ? $permittedTabs[0] : 'dashboard';
}
?>

<!-- QR Code Generator & Scanner (Offline local bundles) -->
<script src="resources/js/qrcode.min.js"></script>
<script src="resources/js/html5-qrcode.min.js"></script>

<style>
/* ==========================================================================
   DX 5S ALL-IN-ONE SINGLE WORKSPACE STYLES
   ========================================================================== */
.five-s-container {
    padding: 0.5rem 0.75rem 2.5rem 0.75rem;
    color: var(--dx-text-main, #0f172a);
}

/* Navigator Tab Bar */
.fives-nav-tabs {
    display: flex;
    gap: 0.35rem;
    background: #ffffff;
    padding: 0.45rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow-x: auto;
    scrollbar-width: thin;
    margin-bottom: 1.25rem;
}
.fives-nav-item {
    flex: 0 0 auto;
}
.fives-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    border-radius: 8px;
    border: none;
    background: transparent;
    transition: all 0.18s ease-in-out;
    white-space: nowrap;
    text-decoration: none;
}
.fives-nav-link:hover {
    background: #f1f5f9;
    color: #0284c7;
}
.fives-nav-link.active {
    background: #0284c7;
    color: #ffffff !important;
    box-shadow: 0 4px 10px -2px rgba(2, 132, 199, 0.4);
}
.fives-nav-link .material-icons {
    font-size: 18px;
}

/* KPI Mini Cards */
.kpi-tile {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
    height: 100%;
}
.kpi-tile::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #cbd5e1;
}
.kpi-tile.kpi-primary::before { background: #0284c7; }
.kpi-tile.kpi-success::before { background: #10b981; }
.kpi-tile.kpi-danger::before { background: #ef4444; }
.kpi-tile.kpi-warning::before { background: #f59e0b; }
.kpi-tile.kpi-purple::before { background: #8b5cf6; }
.kpi-tile.kpi-teal::before { background: #0d9488; }
.kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 2px;
}
.kpi-value {
    font-size: 22px;
    font-weight: 800;
    line-height: 1.2;
}
.kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}

/* Master Cards */
.fives-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.fives-card-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
}
.fives-card-title {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Gantt Matrix Grid */
.gantt-table-container {
    overflow-x: auto;
    max-height: 520px;
}
.gantt-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    font-size: 12px;
}
.gantt-table th, .gantt-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
}
.gantt-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    z-index: 10;
    font-weight: 700;
    color: #475569;
}
.gantt-table .sticky-col {
    position: sticky;
    left: 0;
    background: #ffffff;
    z-index: 5;
    text-align: left;
    font-weight: 600;
    min-width: 180px;
    border-right: 2px solid #e2e8f0;
}
.gantt-table thead .sticky-col {
    z-index: 15;
    background: #f8fafc;
}
.gantt-cell {
    width: 28px;
    min-width: 28px;
    height: 28px;
    padding: 0 !important;
    cursor: pointer;
    transition: transform 0.1s ease;
}
.gantt-cell:hover {
    transform: scale(1.15);
    z-index: 2;
    box-shadow: 0 0 4px rgba(0,0,0,0.2);
}
.gantt-status-ok { background-color: var(--fives-color-ok, #10b981) !important; color: #fff; }
.gantt-status-ng { background-color: var(--fives-color-ng, #ef4444) !important; color: #fff; }
.gantt-status-pending { background-color: var(--fives-color-pending, #94a3b8) !important; color: #fff; }
.gantt-status-none { background-color: #f1f5f9 !important; }

/* Offcanvas custom styling */
.offcanvas-wide {
    width: 580px !important;
}
@media (max-width: 768px) {
    .offcanvas-wide {
        width: 100% !important;
    }
}

/* User Tag Picker Styling */
.user-tag-box {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px;
    background: #ffffff;
    position: relative;
    min-height: 42px;
}
.user-tag-box:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
}
.user-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 4px;
}
.user-tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 20px;
    padding: 2px 8px 2px 4px;
    font-size: 12px;
    color: #166534;
}
.user-tag-chip .user-avatar-circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0284c7;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
}
.user-tag-chip .tag-remove-btn {
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    padding: 0 2px;
    font-size: 14px;
    line-height: 1;
}
.user-tag-chip .tag-remove-btn:hover {
    color: #ef4444;
}
.user-search-input {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    padding: 4px 6px;
    font-size: 12px;
    width: 100%;
}
.user-search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
    margin-top: 4px;
}
.user-search-item {
    padding: 6px 12px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid #f8fafc;
}
.user-search-item:hover {
    background: #f1f5f9;
}

/* Standard Cards View */
.standard-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.standard-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}
.standard-card-header {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-radius: 10px 10px 0 0;
}
.standard-card-body {
    padding: 12px 14px;
    flex-grow: 1;
}
.std-img-thumb {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    transition: opacity 0.15s ease;
}
.std-img-thumb:hover {
    opacity: 0.85;
}

/* Touch Mobile First Audit Buttons */
.audit-item-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.audit-touch-btn-group {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-top: 10px;
}
.touch-btn-audit {
    padding: 10px 4px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
}
.touch-btn-audit.active-ok {
    background: #10b981;
    border-color: #059669;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
}
.touch-btn-audit.active-ng {
    background: #ef4444;
    border-color: #dc2626;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
}
.touch-btn-audit.active-na {
    background: #64748b;
    border-color: #475569;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(100, 116, 139, 0.4);
}

/* Printable QR Card Styles */
.qr-ticket-card {
    border: 2px dashed #0284c7;
    border-radius: 12px;
    padding: 16px;
    background: #ffffff;
    text-align: center;
    position: relative;
    max-width: 320px;
    margin: 0 auto;
}
@media print {
    body * {
        visibility: hidden;
    }
    #printableQrArea, #printableQrArea * {
        visibility: visible;
    }
    #printableQrArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="five-s-container">

    <!-- HEADER TITLE & LIVE ACTIONS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <span class="material-icons text-primary fs-3">verified</span>
                Hệ Thống Quản Lý 5S Toàn Diện
            </h1>
            <p class="text-muted small mb-0">Không gian điều hành tập trung: Danh mục, Tiêu chuẩn khu vực, QR Code, Lập kế hoạch, Kiểm tra hiện trường, Giám sát Gantt và Đối sách</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 small font-monospace d-none d-md-inline-block">
                <span class="material-icons fs-6 align-middle text-primary">schedule</span> <?php echo date('d/m/Y'); ?>
            </span>
            <button class="btn btn-outline-secondary btn-sm" onclick="reloadCurrentTabData()" title="Làm mới dữ liệu">
                <span class="material-icons fs-6 align-middle">refresh</span> Làm mới
            </button>
        </div>
    </div>

    <!-- NAVIGATOR TAB BAR (ALL-IN-ONE HUB) -->
    <div class="fives-nav-tabs" role="tablist">
        <?php if ($can5sView): ?>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-dashboard" href="#tab-dashboard" onclick="switchNavTab('dashboard')">
                <span class="material-icons">analytics</span> 1. Dashboard Giám Sát
            </a>
        </div>
        <?php endif; ?>
        <?php if ($can5sAudit): ?>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-audit" href="#tab-audit" onclick="switchNavTab('audit')">
                <span class="material-icons text-primary">fact_check</span> 2. Thực Hiện Kiểm Tra
            </a>
        </div>
        <?php endif; ?>
        <?php if ($can5sView || $can5sAudit || $can5sSet): ?>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-issues" href="#tab-issues" onclick="switchNavTab('issues')">
                <span class="material-icons text-danger">healing</span> 3. Quản Lý Lỗi & Đối Sách
            </a>
        </div>
        <?php endif; ?>
        <?php if ($can5sView || $can5sSet): ?>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-plans" href="#tab-plans" onclick="switchNavTab('plans')">
                <span class="material-icons text-success">calendar_month</span> 4. Kế Hoạch Kiểm Tra
            </a>
        </div>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-zones" href="#tab-zones" onclick="switchNavTab('zones')">
                <span class="material-icons text-info">domain</span> 5. Danh Mục Vị Trí
            </a>
        </div>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-standards" href="#tab-standards" onclick="switchNavTab('standards')">
                <span class="material-icons text-warning">rule</span> 6. Tiêu Chuẩn Khu Vực
            </a>
        </div>
        <?php endif; ?>
        <?php if ($can5sView || $can5sAudit || $can5sSet): ?>
        <div class="fives-nav-item">
            <a class="fives-nav-link" id="tablink-qrcode" href="#tab-qrcode" onclick="switchNavTab('qrcode')">
                <span class="material-icons text-purple">qr_code_2</span> 7. QR Code & In Thẻ
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB CONTENTS -->
    <div class="tab-content" id="fivesMasterTabContent">

        <!-- ====================================================================
             TAB 1: DASHBOARD GIÁM SÁT 5S TRỰC QUAN
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-dashboard" role="tabpanel">
            <!-- Thẻ KPI thời gian thực -->
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-primary">
                        <div class="kpi-label">Tổng Kế Hoạch</div>
                        <div class="kpi-value text-primary" id="kpi-total-scheduled">0</div>
                        <div class="kpi-sub">Lượt kiểm tra trong kỳ</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-success">
                        <div class="kpi-label">Đạt Chuẩn (OK)</div>
                        <div class="kpi-value text-success" id="kpi-total-ok">0</div>
                        <div class="kpi-sub">Lượt kiểm tra chuẩn 5S</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-danger">
                        <div class="kpi-label">Phát Hiện Lỗi (NG)</div>
                        <div class="kpi-value text-danger" id="kpi-total-ng">0</div>
                        <div class="kpi-sub">Cần lập phiếu đối sách</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-warning">
                        <div class="kpi-label">Chưa Kiểm Tra</div>
                        <div class="kpi-value text-warning" id="kpi-total-pending">0</div>
                        <div class="kpi-sub">Cần thực hiện quét QR</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-teal">
                        <div class="kpi-label">Tỷ Lệ Hoàn Thành</div>
                        <div class="kpi-value text-teal" id="kpi-completion-rate">0%</div>
                        <div class="kpi-sub">Tiến độ thực hiện kỳ này</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-tile kpi-purple">
                        <div class="kpi-label">Tỷ Lệ Đạt Chuẩn</div>
                        <div class="kpi-value text-purple" id="kpi-compliance-rate">0%</div>
                        <div class="kpi-sub">Chất lượng 5S hiện trường</div>
                    </div>
                </div>
            </div>

            <!-- Gantt Matrix Visual Board -->
            <div class="fives-card">
                <div class="fives-card-header">
                    <div>
                        <h2 class="fives-card-title">
                            <span class="material-icons text-primary">view_timeline</span>
                            Ma Trận Giám Sát Trực Quan Theo Thời Gian (Gantt Matrix)
                        </h2>
                        <small class="text-muted">Theo dõi trực quan trạng thái theo Ngày, Tuần, Tháng, Khu vực và Người phụ trách</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="month" id="dash_filter_month" class="form-control form-control-sm" value="<?php echo $currentMonth; ?>" onchange="loadDashboardData()">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openOffcanvasColorConfig()">
                            <span class="material-icons fs-6 align-middle">palette</span> Màu sắc
                        </button>
                    </div>
                </div>

                <!-- Lọc nhanh Ma trận Gantt -->
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <select id="dash_filter_factory" class="form-select form-select-sm" onchange="applyDashFilters()">
                                <option value="">-- Tất cả nhà xưởng --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="dash_filter_area" class="form-select form-select-sm" onchange="applyDashFilters()">
                                <option value="">-- Tất cả khu vực --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="dash_filter_inspector" class="form-select form-select-sm" onchange="applyDashFilters()">
                                <option value="">-- Tất cả người phụ trách --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="dash_filter_status" class="form-select form-select-sm" onchange="applyDashFilters()">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="ok">Đạt (Xanh)</option>
                                <option value="ng">Không đạt (Đỏ)</option>
                                <option value="pending">Chưa kiểm tra (Xám)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bảng Ma trận Gantt -->
                <div class="gantt-table-container p-2">
                    <table class="gantt-table" id="matrixGanttTable">
                        <thead id="matrixGanttHead">
                            <tr><th class="sticky-col">Khu vực / Vị trí 5S</th></tr>
                        </thead>
                        <tbody id="matrixGanttBody">
                            <tr><td class="text-center py-4 text-muted">Đang tải ma trận giám sát...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Chú thích màu sắc trực quan -->
                <div class="p-3 border-top bg-light d-flex flex-wrap align-items-center justify-content-between gap-3 small">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-muted">Trực quan hóa:</span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="badge" style="background: var(--fives-color-ok, #10b981); width: 14px; height: 14px; padding: 0;"> </span> Đạt (OK)
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="badge" style="background: var(--fives-color-ng, #ef4444); width: 14px; height: 14px; padding: 0;"> </span> Không đạt (NG)
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="badge" style="background: var(--fives-color-pending, #94a3b8); width: 14px; height: 14px; padding: 0;"> </span> Chưa kiểm tra
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="badge bg-light border text-muted" style="width: 14px; height: 14px; padding: 0;"> </span> Không có lịch
                        </span>
                    </div>
                    <div class="text-muted">Nhấp vào ô ngày để xem chi tiết kết quả hoặc thực hiện kiểm tra</div>
                </div>
            </div>

            <!-- Cảnh báo các vị trí quá hạn / Chưa kiểm tra -->
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="fives-card">
                        <div class="fives-card-header bg-danger-subtle">
                            <h2 class="fives-card-title text-danger">
                                <span class="material-icons">alarm_on</span>
                                Vị Trí Đến Hạn Chưa Kiểm Tra Trong Ngày
                            </h2>
                            <span class="badge bg-danger rounded-pill" id="badge-unchecked-today">0</span>
                        </div>
                        <div class="p-0">
                            <div class="list-group list-group-flush small" id="list-unchecked-today" style="max-height: 280px; overflow-y: auto;">
                                <div class="text-muted p-3 text-center">Đang kiểm tra...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="fives-card">
                        <div class="fives-card-header bg-warning-subtle">
                            <h2 class="fives-card-title text-warning-emphasis">
                                <span class="material-icons">pending_actions</span>
                                Phiếu Khắc Phục Lỗi Quá Hạn Chưa Hoàn Thành
                            </h2>
                            <span class="badge bg-warning text-dark rounded-pill" id="badge-overdue-issues">0</span>
                        </div>
                        <div class="p-0">
                            <div class="list-group list-group-flush small" id="list-overdue-issues" style="max-height: 280px; overflow-y: auto;">
                                <div class="text-muted p-3 text-center">Đang kiểm tra...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 2: THỰC HIỆN KIỂM TRA HIỆN TRƯỜNG (BẮT BUỘC QUÉT QR)
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-audit" role="tabpanel">
            <div class="row g-3 justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="fives-card">
                        <div class="fives-card-header bg-light">
                            <h2 class="fives-card-title">
                                <span class="material-icons text-primary">qr_code_scanner</span>
                                Phiếu Kiểm Tra 5S Hiện Trường (Mobile First)
                            </h2>
                            <span class="badge bg-primary" id="audit-flow-state-badge">Bắt buộc quét QR</span>
                        </div>
                        <div class="p-3">
                            <!-- BƯỚC 1: QUÉT HOẶC XÁC THỰC MÃ QR VỊ TRÍ -->
                            <div id="audit-step-qr">
                                <div class="text-center py-3">
                                    <div class="h5 fw-bold mb-1">Xác thực mã QR vị trí thực tế</div>
                                    <p class="small text-muted mb-3">
                                        Hướng camera vào mã QR dán tại vị trí để tự động nạp tiêu chuẩn kiểm tra của khu vực đó.
                                    </p>
                                    
                                    <!-- Camera Stream Container -->
                                    <div id="html5-qr-scanner-box" style="max-width: 380px; margin: 0 auto; border-radius: 8px; overflow: hidden;"></div>

                                    <div id="audit-qr-alert" class="alert d-none py-2 px-3 small mt-3 mx-auto" style="max-width: 420px;"></div>

                                    <!-- Quick selection fallback -->
                                    <div class="border-top pt-3 mt-3 mx-auto" style="max-width: 420px;">
                                        <label class="form-label small fw-bold text-muted mb-1">Hoặc chọn vị trí & nhập mã token:</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <select id="audit-select-zone" class="form-select" onchange="autoFillZoneToken(this)"></select>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="audit-input-token" class="form-control" placeholder="Mã QR (VD: 5S-ZONE-01)">
                                            <button class="btn btn-primary" type="button" onclick="verifyQrCodeToken()">
                                                <span class="material-icons fs-6 align-middle">login</span> Vào kiểm tra
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BƯỚC 2: CHECKLIST HIỆN TRƯỜNG KHI ĐÃ QUÉT QR HỢP LỆ -->
                            <div id="audit-step-checklist" class="d-none">
                                <form id="form-audit-execution" onsubmit="submitAuditExecution(event)">
                                    <input type="hidden" name="zone_id" id="exec_zone_id">
                                    <input type="hidden" name="schedule_id" id="exec_schedule_id" value="0">
                                    <input type="hidden" name="qr_code" id="exec_qr_code">
                                    <input type="hidden" name="audit_result" id="exec_audit_result" value="OK">
                                    <input type="hidden" name="checklist_json" id="exec_checklist_json">

                                    <!-- Thẻ vị trí đã xác thực -->
                                    <div class="alert alert-success d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                                        <div>
                                            <div class="fw-bold" id="exec-zone-name">Tên vị trí</div>
                                            <small class="font-monospace" id="exec-zone-meta">Mã vị trí · Bộ phận</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success font-monospace" id="exec-scan-time">00:00:00</span>
                                            <button type="button" class="btn btn-link btn-sm text-dark p-0 d-block small" onclick="resetAuditQrVerification()">Quét lại</button>
                                        </div>
                                    </div>

                                    <!-- Action Bar: Đạt tất cả 1 chạm -->
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="fw-bold small text-dark d-flex align-items-center gap-1">
                                            <span class="material-icons text-primary fs-6">checklist</span>
                                            Tiêu chuẩn kiểm tra khu vực
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 fw-bold" onclick="setAllChecklistStatus('OK')">
                                            <span class="material-icons fs-6 align-middle me-1">done_all</span> Đạt tất cả (1 chạm)
                                        </button>
                                    </div>

                                    <!-- Bảng Checklist động (Mobile First cards) -->
                                    <div id="exec-checklist-container" class="mb-3"></div>

                                    <!-- Box ghi nhận vi phạm khi có mục NG (Tự động mở khi bất kỳ mục nào NG) -->
                                    <div id="exec-box-ng-details" class="p-3 border border-danger rounded bg-danger-subtle mb-3 d-none">
                                        <div class="fw-bold text-danger small mb-2 d-flex align-items-center gap-1">
                                            <span class="material-icons fs-6">warning</span> Ghi nhận lỗi vi phạm (Hệ thống sẽ tự động tạo Phiếu Đối Sách)
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-4">
                                                <label class="form-label small fw-bold">Phân loại 5S</label>
                                                <select name="s_category" id="exec_s_category" class="form-select form-select-sm">
                                                    <option value="S1">S1 - Sàng lọc</option>
                                                    <option value="S2">S2 - Sắp xếp</option>
                                                    <option value="S3">S3 - Sạch sẽ</option>
                                                    <option value="S4">S4 - Săn sóc</option>
                                                    <option value="S5">S5 - Sẵn sàng</option>
                                                    <option value="An Toàn">An Toàn & PCCC</option>
                                                </select>
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label small fw-bold">Mô tả vi phạm <span class="text-danger">*</span></label>
                                                <input type="text" name="description" id="exec_ng_description" class="form-control form-control-sm" placeholder="VD: Vật tư để sai vạch định vị, bụi bẩn bám cụm đùn...">
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold text-danger">Chụp ảnh / Tải ảnh hiện trạng vi phạm (Bắt buộc) <span class="text-danger">*</span></label>
                                            <input type="file" name="before_image" id="exec_before_image" class="form-control form-control-sm" accept="image/*" capture="environment">
                                            <small class="text-muted" style="font-size: 11px;">Chụp ảnh trực tiếp từ camera hoặc chọn file ảnh minh chứng</small>
                                        </div>
                                    </div>

                                    <!-- Ghi chú chung -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Ghi chú kiểm tra (nếu có)</label>
                                        <textarea name="note" id="exec_general_note" class="form-control form-control-sm" rows="2" placeholder="Ghi nhận thêm tình hình hiện trường..."></textarea>
                                    </div>

                                    <!-- Nút hoàn tất kiểm tra -->
                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow" id="btn-submit-audit-exec">
                                        <span class="material-icons">send</span>
                                        <span id="btn-submit-audit-label">Hoàn Thành Đánh Giá Hiện Trường</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 3: QUẢN LÝ LỖI & ĐỐI SÁCH KHẮC PHỤC (5 TRẠNG THÁI)
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-issues" role="tabpanel">
            <div class="fives-card">
                <div class="fives-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="fives-card-title">
                            <span class="material-icons text-danger">healing</span>
                            Danh Sách Phiếu Đối Sách & Khắc Phục Lỗi 5S
                        </h2>
                        <span class="badge bg-danger rounded-pill" id="issues-total-badge">0</span>
                    </div>
                </div>
                <!-- Filter bar -->
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <select id="issue_filter_status" class="form-select form-select-sm" onchange="renderIssuesTable()">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="pending">Chưa xử lý (Pending)</option>
                                <option value="in_progress">Đang xử lý (In Progress)</option>
                                <option value="pending_approval">Chờ nghiệm thu (Pending Approval)</option>
                                <option value="resolved">Đã hoàn thành (Resolved)</option>
                                <option value="overdue">Quá hạn xử lý (Overdue)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="issue_filter_category" class="form-select form-select-sm" onchange="renderIssuesTable()">
                                <option value="">-- Tất cả phân loại 5S --</option>
                                <option value="S1">S1 - Sàng lọc</option>
                                <option value="S2">S2 - Sắp xếp</option>
                                <option value="S3">S3 - Sạch sẽ</option>
                                <option value="S4">S4 - Săn sóc</option>
                                <option value="S5">S5 - Sẵn sàng</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="issue_filter_zone" class="form-select form-select-sm" onchange="renderIssuesTable()">
                                <option value="">-- Tất cả vị trí --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <input type="search" id="issue_search_kw" class="form-control form-control-sm" placeholder="Tìm mô tả, người phụ trách..." oninput="renderIssuesTable()">
                        </div>
                    </div>
                </div>
                <!-- Table -->
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0" id="table-issues-list">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#ID</th>
                                <th>Khu vực / Ngày phát hiện</th>
                                <th>Phân loại</th>
                                <th>Mô tả lỗi vi phạm</th>
                                <th>Hình ảnh Trước / Sau</th>
                                <th>Người phụ trách</th>
                                <th>Hạn xử lý</th>
                                <th>Trạng thái</th>
                                <th class="text-center" style="width: 100px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="issues-table-tbody">
                            <tr><td colspan="9" class="text-center py-4 text-muted">Đang tải danh sách lỗi...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 4: KẾ HOẠCH KIỂM TRA 5S & PHÂN CÔNG NHÂN SỰ
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-plans" role="tabpanel">
            <div class="fives-card">
                <div class="fives-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="fives-card-title">
                            <span class="material-icons text-success">calendar_month</span>
                            Kế Hoạch Kiểm Tra 5S & Phân Công Nhân Sự
                        </h2>
                        <span class="badge bg-success rounded-pill" id="plans-count-badge">0</span>
                    </div>
                    <?php if ($can5sSet): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" onclick="openOffcanvasPlan(null)">
                            <span class="material-icons fs-6 align-middle">add_circle</span> Lập Kế Hoạch Mới
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Nav sub-tabs: Danh sách kế hoạch vs Lịch ma trận -->
                <div class="px-3 pt-3 bg-light border-bottom">
                    <ul class="nav nav-pills nav-fill mb-2" id="plans-subnav" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2 small fw-bold" id="pills-plan-list-tab" data-bs-toggle="pill" data-bs-target="#plans-list-view" type="button" role="tab">
                                <span class="material-icons fs-6 align-middle me-1">format_list_bulleted</span> Danh Sách Kế Hoạch Đang Áp Dụng
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 small fw-bold" id="pills-plan-matrix-tab" data-bs-toggle="pill" data-bs-target="#plans-matrix-view" type="button" role="tab">
                                <span class="material-icons fs-6 align-middle me-1">grid_on</span> Lịch &amp; Ma Trận Kiểm Tra Tổng Thể
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content p-3" id="plans-subnav-content">
                    <!-- VIEW 1: BẢNG DANH SÁCH KẾ HOẠCH -->
                    <div class="tab-pane fade show active" id="plans-list-view" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="table-plans-list">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Tên kế hoạch</th>
                                        <th>Tần suất</th>
                                        <th>Khu vực áp dụng</th>
                                        <th>Người kiểm tra</th>
                                        <th>Người chịu trách nhiệm</th>
                                        <th>Người xác nhận</th>
                                        <th>Thời gian hiệu lực</th>
                                        <th>Trạng thái</th>
                                        <th class="text-center" style="width: 140px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="plans-table-tbody">
                                    <tr><td colspan="10" class="text-center py-4 text-muted">Đang tải danh sách kế hoạch...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- VIEW 2: LỊCH VÀ MA TRẬN TỔNG THỂ -->
                    <div class="tab-pane fade" id="plans-matrix-view" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle small" id="table-plan-overview">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vị trí kiểm tra</th>
                                        <th>Nhà xưởng / Khu vực</th>
                                        <th>Tần suất</th>
                                        <th>Tiến độ hoàn thành</th>
                                        <th>Trạng thái hoạt động</th>
                                    </tr>
                                </thead>
                                <tbody id="plans-overview-tbody">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Đang tải kế hoạch tổng thể...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 5: QUẢN LÝ DANH MỤC VỊ TRÍ 5S
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-zones" role="tabpanel">
            <div class="fives-card">
                <div class="fives-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="fives-card-title">
                            <span class="material-icons text-primary">domain</span> Danh Mục Vị Trí Kiểm Tra 5S
                        </h2>
                        <span class="badge bg-primary rounded-pill" id="zones-count-badge">0</span>
                    </div>
                    <?php if ($can5sSet): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" onclick="openOffcanvasZone(null)">
                            <span class="material-icons fs-6 align-middle">add_location_alt</span> Thêm vị trí mới
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Filter Bar -->
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <input type="search" id="zones_search_kw" class="form-control form-control-sm" placeholder="Tìm mã, tên vị trí..." oninput="renderZonesList()">
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="zones_filter_factory" class="form-select form-select-sm" onchange="renderZonesList()">
                                <option value="">-- Tất cả nhà xưởng --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="zones_filter_area" class="form-select form-select-sm" onchange="renderZonesList()">
                                <option value="">-- Tất cả khu vực --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="zones_filter_active" class="form-select form-select-sm" onchange="renderZonesList()">
                                <option value="all">Tất cả trạng thái</option>
                                <option value="1">Đang sử dụng</option>
                                <option value="0">Ngừng sử dụng</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Zones Table (Clean of Personnel Assignment) -->
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0" id="table-zones-list">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Mã vị trí</th>
                                <th>Tên vị trí kiểm tra</th>
                                <th>Nhà xưởng</th>
                                <th>Khu vực / Phân xưởng</th>
                                <th>Mã QR Code</th>
                                <th>Trạng thái</th>
                                <th class="text-center" style="width: 140px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="zones-table-tbody">
                            <tr><td colspan="8" class="text-center py-4 text-muted">Đang tải danh mục vị trí...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 6: TIÊU CHUẨN ĐÁNH GIÁ 5S THEO KHU VỰC (CARD VIEW)
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-standards" role="tabpanel">
            <div class="fives-card">
                <div class="fives-card-header">
                    <h2 class="fives-card-title text-warning-emphasis">
                        <span class="material-icons text-warning">rule</span>
                        Hệ Thống Tiêu Chuẩn Đánh Giá 5S Theo Khu Vực
                    </h2>
                    <?php if ($can5sSet): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openOffcanvasCopyStandards()">
                            <span class="material-icons fs-6 align-middle">content_copy</span> Sao chép tiêu chuẩn
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openOffcanvasStandard(null)">
                            <span class="material-icons fs-6 align-middle">add_task</span> Thêm tiêu chuẩn mới
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Zone selection bar -->
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-bold text-muted mb-1">Chọn Khu Vực / Vị Trí Để Xem &amp; Thiết Lập Tiêu Chuẩn:</label>
                            <select id="standards_zone_filter" class="form-select form-select-sm" onchange="onStandardsZoneChanged()">
                                <option value="0">-- Tất cả khu vực --</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7 text-md-end pt-3 pt-md-0">
                            <span class="text-muted small">
                                Mỗi khu vực có tiêu chuẩn riêng biệt. Ảnh chuẩn OK và ảnh lỗi NG hỗ trợ xem phóng to trực tiếp.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Standards Cards Container -->
                <div class="p-3" id="standards-cards-container">
                    <div class="text-muted small">Đang tải tiêu chuẩn đánh giá...</div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TAB 7: QR CODE & IN THẺ DÁN HIỆN TRƯỜNG
             ==================================================================== -->
        <div class="tab-pane fade" id="tab-qrcode" role="tabpanel">
            <div class="fives-card">
                <div class="fives-card-header">
                    <div>
                        <h2 class="fives-card-title">
                            <span class="material-icons text-purple">qr_code_2</span>
                            Quản Lý & In Thẻ QR Code Định Danh Vị Trí
                        </h2>
                        <small class="text-muted">Mỗi mã QR chứa đường dẫn trực tiếp mở đúng checklist kiểm tra của vị trí</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="printAllVisibleQrCards()">
                            <span class="material-icons fs-6 align-middle">print</span> In toàn bộ thẻ QR
                        </button>
                    </div>
                </div>
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <input type="search" id="qr_search_input" class="form-control form-control-sm" placeholder="Tìm theo tên hoặc mã vị trí..." oninput="renderQrCardsGrid()">
                        </div>
                        <div class="col-6 col-md-4">
                            <select id="qr_filter_factory" class="form-select form-select-sm" onchange="renderQrCardsGrid()">
                                <option value="">-- Tất cả nhà xưởng --</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <select id="qr_filter_area" class="form-select form-select-sm" onchange="renderQrCardsGrid()">
                                <option value="">-- Tất cả khu vực --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row g-3" id="qr-cards-grid-container">
                        <div class="col-12 text-center py-4 text-muted">Đang sinh mã QR trực tiếp...</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 1: KHAI BÁO / CHỈNH SỬA VỊ TRÍ 5S (LOẠI BỎ PHÂN CÔNG NHÂN SỰ)
     ========================================================================== -->
<div class="offcanvas offcanvas-end offcanvas-wide" tabindex="-1" id="offcanvasZone" aria-labelledby="offcanvasZoneLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasZoneLabel">Khai Báo Vị Trí Kiểm Tra 5S</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="form-offcanvas-zone" onsubmit="saveOffcanvasZone(event)">
            <input type="hidden" name="zone_id" id="oc_zone_id">

            <div class="row g-2 mb-2">
                <div class="col-4">
                    <label class="form-label small fw-bold">Mã vị trí <span class="text-danger">*</span></label>
                    <input type="text" name="zone_code" id="oc_zone_code" class="form-control form-control-sm font-monospace" placeholder="ZONE-01" required oninput="syncOcQrToken()">
                </div>
                <div class="col-8">
                    <label class="form-label small fw-bold">Tên vị trí kiểm tra <span class="text-danger">*</span></label>
                    <input type="text" name="zone_name" id="oc_zone_name" class="form-control form-control-sm" placeholder="Khu Vực Máy Đùn 1" required>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Nhà xưởng <span class="text-danger">*</span></label>
                    <input type="text" name="factory" id="oc_factory" class="form-control form-control-sm" placeholder="Nhà Máy 1" required list="oc-factory-list">
                    <datalist id="oc-factory-list">
                        <option value="Nhà Máy 1">
                        <option value="Nhà Máy 2">
                        <option value="Kho Vận Tổng">
                    </datalist>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Khu vực / Phân xưởng <span class="text-danger">*</span></label>
                    <input type="text" name="area" id="oc_area" class="form-control form-control-sm" placeholder="Xưởng Đùn Nhựa" required>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Máy móc / Thiết bị</label>
                    <input type="text" name="machine" id="oc_machine" class="form-control form-control-sm" placeholder="Máy đùn số 01, Máy nghiền...">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Công đoạn sản xuất</label>
                    <input type="text" name="process_step" id="oc_process_step" class="form-control form-control-sm" placeholder="Gia công nhiệt, Đóng gói...">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Mã QR Code nhận diện</label>
                <input type="text" name="qr_code" id="oc_qr_code" class="form-control form-control-sm font-monospace" placeholder="5S-ZONE-01">
                <small class="text-muted" style="font-size: 11px;">Mã QR dùng để định danh vị trí khi kiểm tra hiện trường.</small>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Mô tả / Lưu ý tại vị trí</label>
                <textarea name="description" id="oc_description" class="form-control form-control-sm" rows="3" placeholder="Ghi chú về vị trí, các quy định đặc thù..."></textarea>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="oc_is_active" name="is_active" value="1" checked>
                <label class="form-check-label small fw-bold" for="oc_is_active">Kích hoạt sử dụng vị trí này</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 py-2 fw-bold">
                    <span class="material-icons fs-6 align-middle">save</span> Lưu Thông Tin Vị Trí
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Hủy</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 2: LẬP KẾ HOẠCH KIỂM TRA 5S & PHÂN CÔNG NHÂN SỰ
     ========================================================================== -->
<div class="offcanvas offcanvas-end offcanvas-wide" tabindex="-1" id="offcanvasPlan" aria-labelledby="offcanvasPlanLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasPlanLabel">Lập Kế Hoạch Kiểm Tra 5S</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="form-offcanvas-plan" onsubmit="saveOffcanvasPlan(event)">
            <input type="hidden" name="id" id="oc_plan_id" value="0">
            <input type="hidden" name="action" value="save">

            <div class="mb-2">
                <label class="form-label small fw-bold">Tên kế hoạch kiểm tra <span class="text-danger">*</span></label>
                <input type="text" name="plan_name" id="oc_plan_name" class="form-control form-control-sm" placeholder="VD: Kế hoạch kiểm tra định kỳ Nhà Máy 1" required>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Tần suất kiểm tra <span class="text-danger">*</span></label>
                    <select name="frequency" id="oc_plan_frequency" class="form-select form-select-sm" onchange="onPlanFrequencyChanged()">
                        <option value="daily">Hàng ngày (Daily)</option>
                        <option value="weekly">Hàng tuần (Weekly)</option>
                        <option value="monthly">Hàng tháng (Monthly)</option>
                        <option value="ad_hoc">Đột xuất (Ad-hoc)</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Trạng thái kế hoạch</label>
                    <select name="status" id="oc_plan_status" class="form-select form-select-sm">
                        <option value="active">Đang chạy (Active)</option>
                        <option value="paused">Tạm dừng (Paused)</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Ngày bắt đầu hiệu lực <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" id="oc_plan_start_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Ngày kết thúc (tùy chọn)</label>
                    <input type="date" name="end_date" id="oc_plan_end_date" class="form-control form-control-sm">
                </div>
            </div>

            <!-- Weekly Days Selectors -->
            <div class="mb-2 d-none" id="oc-plan-weekly-days-box">
                <label class="form-label small fw-bold">Chọn các ngày trong tuần kiểm tra:</label>
                <div class="d-flex flex-wrap gap-2">
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="1" checked> Thứ 2</label>
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="2" checked> Thứ 3</label>
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="3" checked> Thứ 4</label>
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="4" checked> Thứ 5</label>
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="5" checked> Thứ 6</label>
                    <label class="small"><input type="checkbox" name="weekly_days[]" value="6" checked> Thứ 7</label>
                </div>
            </div>

            <!-- Khu vực áp dụng (Multi-select) -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-bold mb-0">Khu vực / Vị trí áp dụng <span class="text-danger">*</span></label>
                    <button type="button" class="btn btn-link btn-sm p-0 small" onclick="toggleSelectAllPlanZones()">Chọn tất cả</button>
                </div>
                <div id="oc-plan-zones-container" class="border rounded p-2 bg-light" style="max-height: 140px; overflow-y: auto;">
                    <div class="text-muted small">Đang tải danh sách khu vực...</div>
                </div>
            </div>

            <!-- PHÂN CÔNG NHÂN SỰ VỚI USER TAG PICKER CHUYÊN NGHIỆP -->
            <div class="p-3 border rounded bg-light mb-3">
                <div class="small fw-bold mb-2 text-primary d-flex align-items-center gap-1">
                    <span class="material-icons fs-6">groups</span> Phân Công Nhân Sự (Tìm theo tên / mã nhân viên)
                </div>

                <!-- 1. Người kiểm tra -->
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1 text-dark">
                        Người kiểm tra (Cho phép chọn nhiều người) <span class="text-danger">*</span>
                    </label>
                    <div id="picker-inspectors-box"></div>
                </div>

                <!-- 2. Người chịu trách nhiệm xử lý lỗi -->
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1 text-dark">
                        Người chịu trách nhiệm xử lý lỗi (Cho phép chọn nhiều người)
                    </label>
                    <div id="picker-assignees-box"></div>
                </div>

                <!-- 3. Người xác nhận hoàn thành -->
                <div class="mb-1">
                    <label class="form-label small fw-bold mb-1 text-dark">
                        Người xác nhận hoàn thành đối sách (Cho phép chọn nhiều người)
                    </label>
                    <div id="picker-approvers-box"></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Ghi chú kế hoạch</label>
                <textarea name="notes" id="oc_plan_notes" class="form-control form-control-sm" rows="2" placeholder="Ghi chú thêm về kế hoạch..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm flex-grow-1 py-2 fw-bold">
                    <span class="material-icons fs-6 align-middle">save</span> Lưu Kế Hoạch Kiểm Tra
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Hủy</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 3: THÊM / SỬA TIÊU CHUẨN ĐÁNH GIÁ THEO KHU VỰC
     ========================================================================== -->
<div class="offcanvas offcanvas-end offcanvas-wide" tabindex="-1" id="offcanvasStandard" aria-labelledby="offcanvasStdLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasStdLabel">Tiêu Chuẩn Đánh Giá 5S Khu Vực</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="form-offcanvas-standard" onsubmit="saveOffcanvasStandard(event)">
            <input type="hidden" name="id" id="oc_std_id">

            <div class="mb-2">
                <label class="form-label small fw-bold">Khu vực / Vị trí áp dụng <span class="text-danger">*</span></label>
                <select name="zone_id" id="oc_std_zone_id" class="form-select form-select-sm" required>
                    <option value="">-- Chọn khu vực --</option>
                </select>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-bold">Nhóm tiêu chuẩn <span class="text-danger">*</span></label>
                <select name="group_name" id="oc_std_group" class="form-select form-select-sm" required>
                    <option value="S1 - Sàng lọc (Seiri)">S1 - Sàng lọc (Seiri)</option>
                    <option value="S2 - Sắp xếp (Seiton)">S2 - Sắp xếp (Seiton)</option>
                    <option value="S3 - Sạch sẽ (Seiso)">S3 - Sạch sẽ (Seiso)</option>
                    <option value="S4 - Săn sóc (Seiketsu)">S4 - Săn sóc (Seiketsu)</option>
                    <option value="S5 - Sẵn sàng (Shitsuke)">S5 - Sẵn sàng (Shitsuke)</option>
                    <option value="An Toàn Lao Động & PCCC">An Toàn Lao Động & PCCC</option>
                </select>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-4">
                    <label class="form-label small fw-bold">Mã tiêu chuẩn</label>
                    <input type="text" name="standard_code" id="oc_std_code" class="form-control form-control-sm font-monospace" placeholder="STD-S1-01">
                </div>
                <div class="col-8">
                    <label class="form-label small fw-bold">Tên tiêu chuẩn / Hạng mục kiểm tra <span class="text-danger">*</span></label>
                    <input type="text" name="item_name" id="oc_std_name" class="form-control form-control-sm" required placeholder="Phân loại dụng cụ cần thiết">
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Mức độ ưu tiên / Nghiêm trọng</label>
                    <select name="severity_level" id="oc_std_severity" class="form-select form-select-sm">
                        <option value="low">Thấp (Low)</option>
                        <option value="medium" selected>Trung bình (Medium)</option>
                        <option value="high">Cao (High)</option>
                        <option value="critical">Nghiêm trọng (Critical)</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Thứ tự sắp xếp</label>
                    <input type="number" name="sort_order" id="oc_std_sort" class="form-control form-control-sm" value="1">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-bold">Nội dung kiểm tra / Tiêu chuẩn đánh giá</label>
                <textarea name="evaluation_criteria" id="oc_std_criteria" class="form-control form-control-sm" rows="3" placeholder="Quy định cụ thể để đạt chuẩn OK..."></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-bold">Ghi chú bổ sung</label>
                <input type="text" name="note" id="oc_std_note" class="form-control form-control-sm" placeholder="Ghi chú riêng cho khu vực này...">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-success">Ảnh minh họa chuẩn OK</label>
                    <input type="file" name="ok_reference_image" class="form-control form-control-sm" accept="image/*">
                    <div id="oc_std_ok_preview" class="mt-1"></div>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-danger">Ảnh minh họa lỗi mẫu NG</label>
                    <input type="file" name="ng_reference_image" class="form-control form-control-sm" accept="image/*">
                    <div id="oc_std_ng_preview" class="mt-1"></div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 py-2 fw-bold">
                    <span class="material-icons fs-6 align-middle">save</span> Lưu Tiêu Chuẩn Khu Vực
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Hủy</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 4: SAO CHÉP TIÊU CHUẨN GIỮA CÁC KHU VỰC
     ========================================================================== -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCopyStandards" aria-labelledby="offcanvasCopyLabel" style="width: 440px;">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasCopyLabel">Sao Chép Tiêu Chuẩn 5S</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <p class="small text-muted mb-3">Sao chép nhanh toàn bộ tiêu chuẩn đánh giá từ một khu vực đã thiết lập sang khu vực khác để tiết kiệm thời gian cấu hình.</p>
        <form id="form-copy-standards" onsubmit="submitCopyStandards(event)">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Khu vực nguồn (Đã có tiêu chuẩn) <span class="text-danger">*</span></label>
                <select name="source_zone_id" id="copy_source_zone_id" class="form-select form-select-sm" required>
                    <option value="">-- Chọn khu vực nguồn --</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Khu vực đích (Khu vực nhận tiêu chuẩn) <span class="text-danger">*</span></label>
                <select name="target_zone_id" id="copy_target_zone_id" class="form-select form-select-sm" required>
                    <option value="">-- Chọn khu vực đích --</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold">
                <span class="material-icons fs-6 align-middle">content_copy</span> Bắt Đầu Sao Chép Tiêu Chuẩn
            </button>
        </form>
    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 5: XỬ LÝ PHIẾU ĐỐI SÁCH (5 TRẠNG THÁI)
     ========================================================================== -->
<div class="offcanvas offcanvas-end offcanvas-wide" tabindex="-1" id="offcanvasIssue" aria-labelledby="offcanvasIssueLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasIssueLabel">Phiếu Đối Sách Khắc Phục 5S</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="form-offcanvas-issue" onsubmit="submitOffcanvasIssue(event)">
            <input type="hidden" name="issue_id" id="oc_issue_id">
            <input type="hidden" name="status" id="oc_issue_status_val" value="in_progress">

            <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                <div>
                    <div class="fw-bold" id="oc_issue_zone_name">Khu vực máy đùn 1</div>
                    <small class="text-muted" id="oc_issue_zone_meta">ZONE-01 · 24/10/2026</small>
                </div>
                <span class="badge bg-danger" id="oc_issue_status_badge">Chưa xử lý</span>
            </div>

            <!-- Ảnh trước cải thiện (lỗi vi phạm) -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-danger">Hình ảnh vi phạm thực tế (Trước cải thiện)</label>
                <div class="text-center p-2 border rounded bg-light" id="oc_issue_before_img_box">
                    <img id="oc_issue_before_img" class="img-fluid rounded" style="max-height: 160px;" alt="Ảnh lỗi">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Mô tả lỗi vi phạm</label>
                <div class="p-2 border rounded bg-light small" id="oc_issue_desc_view"></div>
            </div>

            <!-- Form cập nhật nguyên nhân & đối sách -->
            <div class="mb-2">
                <label class="form-label small fw-bold">Phân tích nguyên nhân gốc rễ (Root Cause)</label>
                <textarea name="root_cause" id="oc_issue_root_cause" class="form-control form-control-sm" rows="2" placeholder="Tại sao phát sinh lỗi này?"></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-bold">Hành động khắc phục & Đối sách phòng ngừa (Countermeasure)</label>
                <textarea name="action_plan" id="oc_issue_action_plan" class="form-control form-control-sm" rows="2" placeholder="Biện pháp xử lý triệt để..."></textarea>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Người chịu trách nhiệm xử lý</label>
                    <select name="assignee_id" id="oc_issue_assignee_id" class="form-select form-select-sm"></select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Hạn hoàn thành</label>
                    <input type="date" name="due_date" id="oc_issue_due_date" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">Người kiểm tra / Xác nhận</label>
                    <select name="approver_id" id="oc_issue_approver_id" class="form-select form-select-sm"></select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-success">Ảnh minh chứng sau cải thiện</label>
                    <input type="file" name="after_image" id="oc_issue_after_image" class="form-control form-control-sm" accept="image/*">
                    <div id="oc_issue_after_img_preview"></div>
                </div>
            </div>

            <!-- Action Buttons for 5 statuses -->
            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <button type="button" class="btn btn-warning btn-sm" onclick="setIssueTargetStatus('in_progress')">
                    <span class="material-icons fs-6 align-middle">play_arrow</span> Đang Xử Lý
                </button>
                <button type="button" class="btn btn-info btn-sm text-white" onclick="setIssueTargetStatus('pending_approval')">
                    <span class="material-icons fs-6 align-middle">assignment_turned_in</span> Gửi Nghiệm Thu
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btn-issue-resolve-action" onclick="setIssueTargetStatus('resolved')">
                    <span class="material-icons fs-6 align-middle">check_circle</span> Xác Nhận Hoàn Thành
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" data-bs-dismiss="offcanvas">Đóng</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     OFFCANVAS 6: CẤU HÌNH MÀU SẮC TRỰC QUAN
     ========================================================================== -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasColorConfig" aria-labelledby="offcanvasColorLabel" style="width: 360px;">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasColorLabel">Cấu Hình Màu Sắc Trực Quan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <p class="small text-muted mb-3">Cấu hình màu sắc đại diện cho tình trạng kiểm tra trên biểu đồ Gantt giám sát.</p>
        <div class="mb-3">
            <label class="form-label small fw-bold d-block">Trạng thái: Đạt chuẩn 5S (OK)</label>
            <div class="d-flex align-items-center gap-2">
                <input type="color" id="cfg_color_ok" class="form-control form-control-color p-0 border-0" value="#10b981">
                <span class="small font-monospace" id="lbl_cfg_ok">#10b981</span>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold d-block">Trạng thái: Không đạt / Vi phạm (NG)</label>
            <div class="d-flex align-items-center gap-2">
                <input type="color" id="cfg_color_ng" class="form-control form-control-color p-0 border-0" value="#ef4444">
                <span class="small font-monospace" id="lbl_cfg_ng">#ef4444</span>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold d-block">Trạng thái: Chưa kiểm tra</label>
            <div class="d-flex align-items-center gap-2">
                <input type="color" id="cfg_color_pending" class="form-control form-control-color p-0 border-0" value="#94a3b8">
                <span class="small font-monospace" id="lbl_cfg_pending">#94a3b8</span>
            </div>
        </div>
        <button type="button" class="btn btn-primary btn-sm w-100 py-2 fw-bold" onclick="applyCustomColors()">
            <span class="material-icons fs-6 align-middle">check</span> Áp Dụng Bảng Màu
        </button>
    </div>
</div>

<!-- ==========================================================================
     MODAL LIGHTBOX: PHÓNG TO HÌNH ẢNH MINH HỌA
     ========================================================================== -->
<div class="modal fade" id="modalImagePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="modalImageTitle">Xem hình ảnh</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2 bg-dark">
                <img id="modalImageSrc" src="" class="img-fluid rounded" style="max-height: 80vh;" alt="Preview">
            </div>
        </div>
    </div>
</div>

<!-- Printable QR Cards Area -->
<div id="printableQrArea" class="d-none"></div>

<!-- ==========================================================================
     JAVASCRIPT WORKSPACE ENGINE
     ========================================================================== -->
<script>
let currentNavTab = '<?php echo $initialTab; ?>';
let rawDashboardData = null;
let rawStandardsData = [];
let rawPlansData = [];
let activeVerifiedZone = null;
let activeChecklistItems = [];
let html5QrScannerInstance = null;

// User Permission & Role Flags
const CAN_5S_VIEW = <?php echo json_encode($can5sView); ?>;
const CAN_5S_AUDIT = <?php echo json_encode($can5sAudit); ?>;
const CAN_5S_SETTINGS = <?php echo json_encode($can5sSet); ?>;
const CURRENT_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;

// User Tag Picker Instances
let pickerInspectors = null;
let pickerAssignees = null;
let pickerApprovers = null;

// --------------------------------------------------------------------------
// USER TAG PICKER COMPONENT
// --------------------------------------------------------------------------
class UserTagPicker {
    constructor(containerId, inputName, placeholder = 'Tìm nhân viên...') {
        this.container = document.getElementById(containerId);
        this.inputName = inputName;
        this.placeholder = placeholder;
        this.selectedUsers = []; // array of {id, username, fullname}
        this.allUsers = [];
        this.init();
    }

    init() {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="user-tag-box">
                <div class="user-tag-list"></div>
                <input type="text" class="user-search-input" placeholder="${this.placeholder}">
                <div class="user-search-dropdown d-none"></div>
            </div>
            <div class="user-hidden-inputs"></div>
        `;

        this.tagListEl = this.container.querySelector('.user-tag-list');
        this.searchInputEl = this.container.querySelector('.user-search-input');
        this.dropdownEl = this.container.querySelector('.user-search-dropdown');
        this.hiddenInputsEl = this.container.querySelector('.user-hidden-inputs');

        this.searchInputEl.addEventListener('input', () => this.onSearch());
        this.searchInputEl.addEventListener('focus', () => this.onSearch());
        
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.dropdownEl.classList.add('d-none');
            }
        });
    }

    setUsers(users) {
        this.allUsers = users || [];
    }

    setValues(userIds) {
        if (!Array.isArray(userIds)) userIds = [];
        userIds = userIds.map(Number);
        this.selectedUsers = (this.allUsers || []).filter(u => userIds.includes(Number(u.id)));
        this.render();
    }

    getValues() {
        return this.selectedUsers.map(u => u.id);
    }

    onSearch() {
        const kw = this.searchInputEl.value.trim().toLowerCase();
        const selectedIds = this.selectedUsers.map(u => u.id);
        const filtered = (this.allUsers || []).filter(u => {
            if (selectedIds.includes(u.id)) return false;
            if (!kw) return true;
            const fn = (u.fullname || '').toLowerCase();
            const un = (u.username || '').toLowerCase();
            return fn.includes(kw) || un.includes(kw);
        }).slice(0, 10);

        if (!filtered.length) {
            this.dropdownEl.innerHTML = '<div class="user-search-item text-muted">Không tìm thấy nhân viên</div>';
        } else {
            this.dropdownEl.innerHTML = filtered.map(u => {
                const initials = (u.fullname || u.username || 'NV').slice(0, 2).toUpperCase();
                return `
                    <div class="user-search-item" data-id="${u.id}">
                        <span class="user-avatar-circle" style="width:20px;height:20px;font-size:9px;">${initials}</span>
                        <span><strong>${escapeHtml(u.fullname || u.username)}</strong> <small class="text-muted">(${escapeHtml(u.username)})</small></span>
                    </div>
                `;
            }).join('');

            this.dropdownEl.querySelectorAll('.user-search-item').forEach(el => {
                el.addEventListener('click', () => {
                    const id = Number(el.dataset.id);
                    const user = this.allUsers.find(u => Number(u.id) === id);
                    if (user) {
                        this.selectedUsers.push(user);
                        this.searchInputEl.value = '';
                        this.dropdownEl.classList.add('d-none');
                        this.render();
                    }
                });
            });
        }
        this.dropdownEl.classList.remove('d-none');
    }

    removeUser(id) {
        this.selectedUsers = this.selectedUsers.filter(u => Number(u.id) !== Number(id));
        this.render();
    }

    render() {
        this.tagListEl.innerHTML = this.selectedUsers.map(u => {
            const initials = (u.fullname || u.username || 'NV').slice(0, 2).toUpperCase();
            return `
                <div class="user-tag-chip">
                    <span class="user-avatar-circle">${initials}</span>
                    <span>${escapeHtml(u.fullname || u.username)}</span>
                    <button type="button" class="tag-remove-btn" onclick="this.closest('.user-tag-box').userPickerRef.removeUser(${u.id})">&times;</button>
                </div>
            `;
        }).join('');

        this.hiddenInputsEl.innerHTML = this.selectedUsers.map(u => 
            `<input type="hidden" name="${this.inputName}[]" value="${u.id}">`
        ).join('');

        this.container.querySelector('.user-tag-box').userPickerRef = this;
    }
}

// --------------------------------------------------------------------------
// INITIALIZATION
// --------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initColorListeners();
    initUserPickers();
    loadDashboardData();
    loadPlansList();
    switchNavTab(currentNavTab, false);

    // Deep link directly to audit position if passed via QR URL
    const urlParams = new URLSearchParams(window.location.search);
    const paramZoneCode = urlParams.get('zone_code') || '<?php echo $initialZoneCode; ?>';
    const paramZoneId = urlParams.get('zone_id') || '<?php echo $initialZoneId; ?>';

    if (paramZoneCode || (paramZoneId && paramZoneId > 0)) {
        setTimeout(() => {
            switchNavTab('audit', true);
            autoActivateZoneAudit(paramZoneCode, paramZoneId);
        }, 500);
    }
});

function initUserPickers() {
    pickerInspectors = new UserTagPicker('picker-inspectors-box', 'inspectors', 'Tìm người kiểm tra...');
    pickerAssignees = new UserTagPicker('picker-assignees-box', 'assignees', 'Tìm người xử lý...');
    pickerApprovers = new UserTagPicker('picker-approvers-box', 'approvers', 'Tìm người xác nhận...');
}

function initColorListeners() {
    ['ok', 'ng', 'pending'].forEach(k => {
        const inp = document.getElementById('cfg_color_' + k);
        const lbl = document.getElementById('lbl_cfg_' + k);
        if (inp && lbl) {
            inp.addEventListener('input', () => lbl.textContent = inp.value);
        }
    });
}

function switchNavTab(tabId, pushState = true) {
    currentNavTab = tabId;
    document.querySelectorAll('.fives-nav-link').forEach(link => {
        link.classList.toggle('active', link.id === 'tablink-' + tabId);
    });
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.toggle('show', pane.id === 'tab-' + tabId);
        pane.classList.toggle('active', pane.id === 'tab-' + tabId);
    });

    if (pushState && history.pushState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        history.pushState(null, '', url);
    }

    if (tabId === 'audit') {
        startCameraStream();
    } else {
        stopCameraStream();
    }

    if (tabId === 'qrcode') {
        renderQrCardsGrid();
    }

    if (tabId === 'plans') {
        loadPlansList();
    }

    if (tabId === 'standards') {
        loadStandardsForZone();
    }
}

function reloadCurrentTabData() {
    loadDashboardData();
    loadPlansList();
    loadStandardsForZone();
}

function loadDashboardData() {
    const month = document.getElementById('dash_filter_month').value || new Date().toISOString().slice(0, 7);
    fetch(`api/five_s_get_dashboard.php?month=${month}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            rawDashboardData = data;

            // Pass users to tag pickers
            if (data.users) {
                if (pickerInspectors) pickerInspectors.setUsers(data.users);
                if (pickerAssignees) pickerAssignees.setUsers(data.users);
                if (pickerApprovers) pickerApprovers.setUsers(data.users);
                fillGlobalUserSelects(data.users);
            }

            // Render Tab 1 Dashboard
            renderDashboardKpis(data.kpi);
            populateDashFilters(data);
            applyDashFilters();
            renderUncheckedZones(data.unchecked_zones);

            // Render Tab 3 Issues
            renderIssuesTable();

            // Render Tab 4 Plans Matrix
            renderPlansOverviewTable(data);

            // Render Tab 5 Zones
            populateZoneFilters(data);
            renderZonesList();

            // Populate Standards Zone dropdown
            populateStandardsZoneFilter(data.zones);

            // Render Tab 7 QR
            populateQrFilters(data);
            renderQrCardsGrid();
        })
        .catch(err => console.error('Lỗi load dashboard:', err));
}

// --------------------------------------------------------------------------
// TAB 1: DASHBOARD
// --------------------------------------------------------------------------
function renderDashboardKpis(kpi) {
    if (!kpi) return;
    document.getElementById('kpi-total-scheduled').textContent = kpi.total_scheduled || 0;
    document.getElementById('kpi-total-ok').textContent = kpi.total_ok || 0;
    document.getElementById('kpi-total-ng').textContent = kpi.total_ng || 0;
    document.getElementById('kpi-total-pending').textContent = kpi.total_pending || 0;
    document.getElementById('kpi-completion-rate').textContent = (kpi.completion_rate || 0) + '%';
    document.getElementById('kpi-compliance-rate').textContent = (kpi.compliance_rate || 0) + '%';
}

function populateDashFilters(data) {
    const facSel = document.getElementById('dash_filter_factory');
    const areaSel = document.getElementById('dash_filter_area');
    const inspSel = document.getElementById('dash_filter_inspector');

    facSel.innerHTML = '<option value="">-- Tất cả nhà xưởng --</option>' + (data.factories || []).map(f => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join('');
    areaSel.innerHTML = '<option value="">-- Tất cả khu vực --</option>' + (data.areas || []).map(a => `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`).join('');
    inspSel.innerHTML = '<option value="">-- Tất cả người phụ trách --</option>' + (data.users || []).map(u => `<option value="${u.id}">${escapeHtml(u.fullname || u.username)}</option>`).join('');
}

function applyDashFilters() {
    if (!rawDashboardData) return;
    const fac = document.getElementById('dash_filter_factory').value;
    const area = document.getElementById('dash_filter_area').value;
    const inspId = document.getElementById('dash_filter_inspector').value;

    let rows = rawDashboardData.gantt_rows || [];
    if (fac) rows = rows.filter(r => r.factory === fac);
    if (area) rows = rows.filter(r => r.area === area || r.department === area);
    if (inspId) rows = rows.filter(r => Number(r.manager_id) === Number(inspId));

    renderGanttMatrix(rows, rawDashboardData.days_in_month, rawDashboardData.month_year);
}

function renderGanttMatrix(rows, daysInMonth, monthStr) {
    const head = document.getElementById('matrixGanttHead');
    const body = document.getElementById('matrixGanttBody');

    let headHtml = '<tr><th class="sticky-col">Khu vực / Vị trí 5S</th>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dStr = d < 10 ? '0' + d : d;
        headHtml += `<th title="${dStr}/${monthStr}">${d}</th>`;
    }
    headHtml += '</tr>';
    head.innerHTML = headHtml;

    if (!rows.length) {
        body.innerHTML = `<tr><td colspan="${daysInMonth + 1}" class="text-center py-4 text-muted">Không có dữ liệu kế hoạch trong tháng này.</td></tr>`;
        return;
    }

    let bodyHtml = '';
    rows.forEach(r => {
        bodyHtml += `<tr>`;
        bodyHtml += `<td class="sticky-col">
            <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="${escapeHtml(r.zone_name)}">${escapeHtml(r.zone_name)}</div>
            <div class="small text-muted font-monospace">${escapeHtml(r.zone_code)} · ${escapeHtml(r.area || r.department)}</div>
        </td>`;

        for (let d = 1; d <= daysInMonth; d++) {
            const dKey = (d < 10 ? '0' + d : '' + d);
            const cell = r.days ? r.days[dKey] : null;

            if (!cell || cell.status === 'none') {
                bodyHtml += `<td class="gantt-cell gantt-status-none" title="Không có lịch"></td>`;
            } else if (cell.status === 'OK') {
                bodyHtml += `<td class="gantt-cell gantt-status-ok" title="Đạt chuẩn (OK) - ${cell.inspector_name || 'Đã kiểm tra'}">✓</td>`;
            } else if (cell.status === 'NG') {
                bodyHtml += `<td class="gantt-cell gantt-status-ng" title="Lỗi (NG) - ${cell.inspector_name || ''}">✗</td>`;
            } else {
                bodyHtml += `<td class="gantt-cell gantt-status-pending" title="Chưa kiểm tra - Phụ trách: ${cell.inspector_name || 'Chưa gán'}" onclick="quickJumpToAudit('${r.zone_code}')">?</td>`;
            }
        }
        bodyHtml += `</tr>`;
    });
    body.innerHTML = bodyHtml;
}

function renderUncheckedZones(unchecked) {
    const list = document.getElementById('list-unchecked-today');
    const badge = document.getElementById('badge-unchecked-today');
    badge.textContent = (unchecked || []).length;

    if (!unchecked || !unchecked.length) {
        list.innerHTML = '<div class="text-success p-3 text-center fw-bold">✓ Tất cả các vị trí đã hoàn thành kiểm tra trong ngày!</div>';
        return;
    }

    list.innerHTML = unchecked.map(z => `
        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
                <strong>${escapeHtml(z.zone_name)}</strong>
                <div class="small text-muted">${escapeHtml(z.zone_code)} · ${escapeHtml(z.department || z.area)}</div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="quickJumpToAudit('${z.zone_code}')">Kiểm tra ngay</button>
        </div>
    `).join('');
}

function openOffcanvasColorConfig() {
    new bootstrap.Offcanvas(document.getElementById('offcanvasColorConfig')).show();
}

function applyCustomColors() {
    const ok = document.getElementById('cfg_color_ok').value;
    const ng = document.getElementById('cfg_color_ng').value;
    const pending = document.getElementById('cfg_color_pending').value;

    document.documentElement.style.setProperty('--fives-color-ok', ok);
    document.documentElement.style.setProperty('--fives-color-ng', ng);
    document.documentElement.style.setProperty('--fives-color-pending', pending);

    bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasColorConfig')).hide();
    alert('Đã cập nhật bảng màu trực quan!');
}

// --------------------------------------------------------------------------
// TAB 2: THỰC HIỆN KIỂM TRA (MOBILE FIRST + DYNAMIC STANDARDS PER ZONE)
// --------------------------------------------------------------------------
function startCameraStream() {
    const box = document.getElementById('html5-qr-scanner-box');
    if (!box || html5QrScannerInstance) return;

    if (typeof Html5QrcodeScanner !== 'undefined') {
        html5QrScannerInstance = new Html5QrcodeScanner(
            "html5-qr-scanner-box", 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            false
        );
        html5QrScannerInstance.render((decodedText) => {
            verifyScannedTokenText(decodedText);
        }, (errorMessage) => {});
    }
}

function stopCameraStream() {
    if (html5QrScannerInstance) {
        try {
            html5QrScannerInstance.clear();
        } catch(e) {}
        html5QrScannerInstance = null;
    }
}

function autoFillZoneToken(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt && opt.dataset.token) {
        document.getElementById('audit-input-token').value = opt.dataset.token;
    }
}

function verifyQrCodeToken() {
    const token = document.getElementById('audit-input-token').value.trim();
    if (!token) {
        alert('Vui lòng nhập hoặc quét mã QR Code');
        return;
    }
    verifyScannedTokenText(token);
}

function verifyScannedTokenText(tokenText) {
    if (!rawDashboardData || !rawDashboardData.zones) return;

    let cleanToken = tokenText;
    if (tokenText.includes('zone_code=')) {
        try {
            const parsed = new URL(tokenText, window.location.origin);
            cleanToken = parsed.searchParams.get('zone_code') || cleanToken;
        } catch(e) {}
    }

    const matchedZone = rawDashboardData.zones.find(z => {
        return (z.qr_code_hash && z.qr_code_hash.trim() === cleanToken) ||
               (z.zone_code && z.zone_code.trim() === cleanToken) ||
               ('5S-' + z.zone_code === cleanToken);
    });

    const alertEl = document.getElementById('audit-qr-alert');
    if (!matchedZone) {
        alertEl.className = 'alert alert-danger py-2 px-3 small mt-3 mx-auto';
        alertEl.textContent = `Mã quét [${cleanToken}] không khớp với bất kỳ vị trí 5S nào trong hệ thống!`;
        alertEl.classList.remove('d-none');
        return;
    }

    stopCameraStream();
    activeVerifiedZone = matchedZone;

    document.getElementById('exec_zone_id').value = matchedZone.id;
    document.getElementById('exec_qr_code').value = cleanToken;
    document.getElementById('exec-zone-name').textContent = matchedZone.zone_name;
    document.getElementById('exec-zone-meta').textContent = `${matchedZone.zone_code} · ${matchedZone.department || matchedZone.area}`;
    document.getElementById('exec-scan-time').textContent = new Date().toLocaleTimeString('vi-VN');

    // Load dynamic standards for this specific zone
    fetch(`api/five_s_standards.php?action=list&zone_id=${matchedZone.id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.standards && data.standards.length > 0) {
                activeChecklistItems = data.standards.map(s => ({
                    id: s.id,
                    standard_code: s.standard_code,
                    category: s.group_name || '5S',
                    name: s.item_name,
                    standard: s.evaluation_criteria || s.description || '',
                    severity: s.severity_level || 'medium',
                    ok_reference_image: s.ok_reference_image || '',
                    ng_reference_image: s.ng_reference_image || '',
                    result: 'OK'
                }));
            } else {
                // Fallback to standard 5S
                activeChecklistItems = [
                    { id: 'S1', category: 'S1 - Sàng lọc', name: 'Sàng lọc đồ dùng', standard: 'Không có vật dụng thừa, rác tại vị trí', result: 'OK' },
                    { id: 'S2', category: 'S2 - Sắp xếp', name: 'Sắp xếp ngăn nắp', standard: 'Đồ dùng đặt đúng vạch định vị, quy định', result: 'OK' },
                    { id: 'S3', category: 'S3 - Sạch sẽ', name: 'Vệ sinh sạch sẽ', standard: 'Máy móc, sàn nhà sạch không bụi bẩn', result: 'OK' },
                    { id: 'S4', category: 'S4 - Săn sóc', name: 'Duy trì chuẩn 3S', standard: 'Tiêu chuẩn 3S được duy trì đúng quy trình', result: 'OK' },
                    { id: 'S5', category: 'S5 - Sẵn sàng', name: 'Tự giác tuân thủ', standard: 'Ý thức tự giác, chấp hành tốt an toàn 5S', result: 'OK' }
                ];
            }
            renderExecChecklistRows();
            document.getElementById('audit-step-qr').classList.add('d-none');
            document.getElementById('audit-step-checklist').classList.remove('d-none');
            document.getElementById('audit-flow-state-badge').textContent = 'Đang đánh giá checklist';
            document.getElementById('audit-flow-state-badge').className = 'badge bg-success';
        })
        .catch(err => {
            console.error('Lỗi tải tiêu chuẩn kiểm tra:', err);
        });
}

function autoActivateZoneAudit(zoneCode, zoneId) {
    if (!rawDashboardData || !rawDashboardData.zones) {
        setTimeout(() => autoActivateZoneAudit(zoneCode, zoneId), 400);
        return;
    }
    const z = rawDashboardData.zones.find(item => (zoneId && item.id == zoneId) || (zoneCode && item.zone_code === zoneCode));
    if (z) {
        verifyScannedTokenText(z.qr_code_hash || ('5S-' + z.zone_code));
    }
}

function quickJumpToAudit(zoneCode) {
    switchNavTab('audit');
    autoActivateZoneAudit(zoneCode, 0);
}

function resetAuditQrVerification() {
    activeVerifiedZone = null;
    document.getElementById('audit-step-qr').classList.remove('d-none');
    document.getElementById('audit-step-checklist').classList.add('d-none');
    document.getElementById('audit-flow-state-badge').textContent = 'Bắt buộc quét QR';
    document.getElementById('audit-flow-state-badge').className = 'badge bg-primary';
    startCameraStream();
}

function renderExecChecklistRows() {
    const container = document.getElementById('exec-checklist-container');
    container.innerHTML = activeChecklistItems.map((item, idx) => `
        <div class="audit-item-box" id="audit-item-${idx}">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <div>
                    <span class="badge bg-primary-subtle text-primary me-1">${escapeHtml(item.category || '5S')}</span>
                    ${item.severity ? `<span class="badge bg-${getSeverityColor(item.severity)}-subtle text-${getSeverityColor(item.severity)} border">${escapeHtml(item.severity)}</span>` : ''}
                    <div class="fw-bold text-dark mt-1">${escapeHtml(item.name || '')}</div>
                </div>
            </div>
            ${item.standard ? `<div class="small text-muted mb-2">${escapeHtml(item.standard)}</div>` : ''}

            <!-- OK / NG thumbnails if present -->
            ${(item.ok_reference_image || item.ng_reference_image) ? `
                <div class="d-flex gap-2 my-2">
                    ${item.ok_reference_image ? `
                        <div class="text-center" style="width: 70px;">
                            <img src="${item.ok_reference_image}" class="img-thumbnail" style="height: 48px; object-fit: cover; cursor: pointer;" onclick="previewImageZoom('${item.ok_reference_image}', 'Chuẩn OK - ${escapeHtml(item.name)}')">
                            <span class="badge bg-success-subtle text-success p-0" style="font-size: 9px;">Chuẩn OK</span>
                        </div>
                    ` : ''}
                    ${item.ng_reference_image ? `
                        <div class="text-center" style="width: 70px;">
                            <img src="${item.ng_reference_image}" class="img-thumbnail" style="height: 48px; object-fit: cover; cursor: pointer;" onclick="previewImageZoom('${item.ng_reference_image}', 'Lỗi NG - ${escapeHtml(item.name)}')">
                            <span class="badge bg-danger-subtle text-danger p-0" style="font-size: 9px;">Lỗi mẫu NG</span>
                        </div>
                    ` : ''}
                </div>
            ` : ''}

            <!-- Large Mobile Touch Buttons -->
            <div class="audit-touch-btn-group">
                <button type="button" class="touch-btn-audit ${item.result === 'OK' ? 'active-ok' : ''}" onclick="updateExecChecklistItem(${idx}, 'OK')">
                    ✓ ĐẠT (OK)
                </button>
                <button type="button" class="touch-btn-audit ${item.result === 'NG' ? 'active-ng' : ''}" onclick="updateExecChecklistItem(${idx}, 'NG')">
                    ✗ KHÔNG ĐẠT (NG)
                </button>
                <button type="button" class="touch-btn-audit ${item.result === 'NA' ? 'active-na' : ''}" onclick="updateExecChecklistItem(${idx}, 'NA')">
                    N/A
                </button>
            </div>
        </div>
    `).join('');

    evaluateExecOverallResult();
}

function updateExecChecklistItem(idx, val) {
    if (activeChecklistItems[idx]) {
        activeChecklistItems[idx].result = val;
        renderExecChecklistRows();
    }
}

function setAllChecklistStatus(statusVal) {
    activeChecklistItems.forEach(item => item.result = statusVal);
    renderExecChecklistRows();
}

function evaluateExecOverallResult() {
    const hasNg = activeChecklistItems.some(item => item.result === 'NG');
    const resultInput = document.getElementById('exec_audit_result');
    const ngBox = document.getElementById('exec-box-ng-details');
    const submitLabel = document.getElementById('btn-submit-audit-label');

    if (hasNg) {
        resultInput.value = 'NG';
        ngBox.classList.remove('d-none');
        submitLabel.textContent = 'Gửi Đánh Giá & Tự Động Tạo Phiếu Đối Sách';
        document.getElementById('btn-submit-audit-exec').className = 'btn btn-danger w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow';
        
        // Auto pick category from first NG item
        const firstNg = activeChecklistItems.find(item => item.result === 'NG');
        if (firstNg) {
            document.getElementById('exec_ng_description').value = `Không đạt chuẩn: ${firstNg.name} - ${firstNg.standard || ''}`;
        }
    } else {
        resultInput.value = 'OK';
        ngBox.classList.add('d-none');
        submitLabel.textContent = 'Hoàn Thành Đánh Giá Hiện Trường (Đạt Chuẩn)';
        document.getElementById('btn-submit-audit-exec').className = 'btn btn-primary w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow';
    }
}

function submitAuditExecution(event) {
    event.preventDefault();
    const result = document.getElementById('exec_audit_result').value;
    const ngBox = document.getElementById('exec-box-ng-details');

    if (result === 'NG') {
        const desc = document.getElementById('exec_ng_description').value.trim();
        const fileInput = document.getElementById('exec_before_image');
        if (!desc) {
            alert('Vui lòng nhập mô tả lỗi vi phạm hiện trường');
            return;
        }
        if (!fileInput.files || !fileInput.files.length) {
            alert('Bắt buộc phải chụp ảnh / tải ảnh hiện trạng vi phạm khi đánh giá Không Đạt (NG)!');
            return;
        }
    }

    document.getElementById('exec_checklist_json').value = JSON.stringify(activeChecklistItems);
    const formData = new FormData(document.getElementById('form-audit-execution'));

    fetch('api/five_s_save_audit_full.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                resetAuditQrVerification();
                switchNavTab(result === 'NG' ? 'issues' : 'dashboard');
                loadDashboardData();
            }
        })
        .catch(err => {
            alert('Lỗi khi gửi kết quả kiểm tra: ' + err);
        });
}

// --------------------------------------------------------------------------
// TAB 3: QUẢN LÝ LỖI & ĐỐI SÁCH KHẮC PHỤC
// --------------------------------------------------------------------------
function renderIssuesTable() {
    if (!rawDashboardData) return;
    const tbody = document.getElementById('issues-table-tbody');
    const totalBadge = document.getElementById('issues-total-badge');
    const filterSt = document.getElementById('issue_filter_status').value;
    const filterCat = document.getElementById('issue_filter_category').value;
    const filterZone = document.getElementById('issue_filter_zone').value;
    const kw = document.getElementById('issue_search_kw').value.trim().toLowerCase();

    let items = rawDashboardData.issues || [];
    totalBadge.textContent = items.length;

    items = items.filter(it => {
        const matchesSt = !filterSt || it.status === filterSt || (filterSt === 'overdue' && it.is_overdue);
        const matchesCat = !filterCat || it.s_category === filterCat;
        const matchesZone = !filterZone || it.zone_id == filterZone;
        const matchesKw = !kw || (it.description || '').toLowerCase().includes(kw) || (it.assignee_name || '').toLowerCase().includes(kw);
        return matchesSt && matchesCat && matchesZone && matchesKw;
    });

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Không có lỗi hoặc phiếu đối sách nào phù hợp.</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => `
        <tr class="${item.is_overdue ? 'table-danger' : ''}">
            <td><strong class="font-monospace">#${item.id}</strong></td>
            <td>
                <strong>${escapeHtml(item.zone_name)}</strong>
                <div class="small text-muted font-monospace">${escapeHtml(item.zone_code)}</div>
                <div class="small text-muted">${escapeHtml(item.audit_date || '')}</div>
            </td>
            <td><span class="badge bg-secondary font-monospace">${escapeHtml(item.s_category || '5S')}</span></td>
            <td>
                <div class="small text-dark fw-bold">${escapeHtml(item.description)}</div>
                ${item.action_plan ? `<div class="small text-success mt-1"><strong>Đối sách:</strong> ${escapeHtml(item.action_plan)}</div>` : ''}
            </td>
            <td>
                <div class="d-flex gap-1">
                    ${item.before_image ? `<img src="${item.before_image}" class="img-thumbnail" style="width: 44px; height: 44px; object-fit: cover; cursor: pointer;" onclick="previewImageZoom('${item.before_image}', 'Ảnh trước cải thiện #${item.id}')" title="Trước cải thiện">` : '<span class="text-muted small">Không ảnh</span>'}
                    ${item.after_image ? `<img src="${item.after_image}" class="img-thumbnail" style="width: 44px; height: 44px; object-fit: cover; cursor: pointer;" onclick="previewImageZoom('${item.after_image}', 'Ảnh sau cải thiện #${item.id}')" title="Sau cải thiện">` : ''}
                </div>
            </td>
            <td>${escapeHtml(item.assignee_name || 'Chưa gán')}</td>
            <td>
                ${item.due_date ? `<span class="badge bg-light text-dark border ${item.is_overdue ? 'text-danger fw-bold' : ''}">${escapeHtml(item.due_date)}</span>` : '<span class="text-muted small">Chưa đặt</span>'}
            </td>
            <td>
                <span class="badge bg-${getIssueStatusBadgeClass(item.status)}">
                    ${getIssueStatusLabel(item.status)}
                </span>
                ${item.is_overdue ? '<span class="badge bg-danger ms-1">Quá hạn</span>' : ''}
            </td>
            <td class="text-center">
                ${(CAN_5S_AUDIT || CAN_5S_SETTINGS) ? `
                    <button type="button" class="btn btn-outline-primary btn-sm p-1" onclick="openOffcanvasIssue(${item.id})" title="Cập nhật đối sách">
                        <span class="material-icons fs-6">edit_note</span>
                    </button>
                ` : '<span class="text-muted small">--</span>'}
            </td>
        </tr>
    `).join('');
}

function getIssueStatusBadgeClass(st) {
    if (st === 'resolved') return 'success';
    if (st === 'pending_approval') return 'info text-white';
    if (st === 'in_progress') return 'warning text-dark';
    if (st === 'overdue') return 'danger';
    return 'danger';
}

function getIssueStatusLabel(st) {
    const map = {
        'pending': 'Chưa xử lý',
        'in_progress': 'Đang xử lý',
        'pending_approval': 'Chờ nghiệm thu',
        'resolved': 'Đã hoàn thành',
        'overdue': 'Quá hạn'
    };
    return map[st] || st;
}

function openOffcanvasIssue(issueId) {
    if (!rawDashboardData || !rawDashboardData.issues) return;
    const item = rawDashboardData.issues.find(it => Number(it.id) === Number(issueId));
    if (!item) return;

    document.getElementById('form-offcanvas-issue').reset();
    document.getElementById('oc_issue_id').value = item.id;
    document.getElementById('oc_issue_zone_name').textContent = item.zone_name;
    document.getElementById('oc_issue_zone_meta').textContent = `${item.zone_code} · ${item.audit_date || ''}`;
    document.getElementById('oc_issue_desc_view').textContent = item.description;
    document.getElementById('oc_issue_root_cause').value = item.root_cause || '';
    document.getElementById('oc_issue_action_plan').value = item.action_plan || '';
    document.getElementById('oc_issue_due_date').value = item.due_date || '';
    document.getElementById('oc_issue_assignee_id').value = item.assignee_id || '';
    document.getElementById('oc_issue_approver_id').value = item.approver_id || '';

    const badge = document.getElementById('oc_issue_status_badge');
    badge.textContent = getIssueStatusLabel(item.status);
    badge.className = 'badge bg-' + getIssueStatusBadgeClass(item.status);

    const bImg = document.getElementById('oc_issue_before_img');
    if (item.before_image) {
        bImg.src = item.before_image;
        bImg.classList.remove('d-none');
    } else {
        bImg.classList.add('d-none');
    }

    const prevEl = document.getElementById('oc_issue_after_img_preview');
    if (item.after_image) {
        prevEl.innerHTML = `<img src="${item.after_image}" class="img-thumbnail mt-1" style="max-height: 80px;">`;
    } else {
        prevEl.innerHTML = '';
    }

    // Control resolve button permission (Approval only for Settings manager, Admin, or designated Approver)
    const canApproveIssue = CAN_5S_SETTINGS || (typeof window.CURRENT_USER_ROLE !== 'undefined' && window.CURRENT_USER_ROLE === 'admin') || (item.approver_id && item.approver_id == CURRENT_USER_ID);
    const resolveBtn = document.getElementById('btn-issue-resolve-action');
    if (resolveBtn) {
        resolveBtn.classList.toggle('d-none', !canApproveIssue);
    }

    new bootstrap.Offcanvas(document.getElementById('offcanvasIssue')).show();
}

function setIssueTargetStatus(st) {
    document.getElementById('oc_issue_status_val').value = st;
    document.getElementById('form-offcanvas-issue').dispatchEvent(new Event('submit', { cancelable: true }));
}

function submitOffcanvasIssue(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('form-offcanvas-issue'));

    fetch('api/five_s_resolve_issue.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasIssue')).hide();
                loadDashboardData();
            }
        })
        .catch(err => alert('Lỗi kết nối khi cập nhật đối sách'));
}

// --------------------------------------------------------------------------
// TAB 4: KẾ HOẠCH KIỂM TRA 5S & PHÂN CÔNG NHÂN SỰ
// --------------------------------------------------------------------------
function loadPlansList() {
    fetch('api/five_s_plans.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            rawPlansData = data.plans || [];
            renderPlansList();
        })
        .catch(err => console.error('Lỗi tải danh sách kế hoạch:', err));
}

function renderPlansList() {
    const tbody = document.getElementById('plans-table-tbody');
    const badge = document.getElementById('plans-count-badge');
    badge.textContent = rawPlansData.length;

    if (!rawPlansData.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Chưa có kế hoạch kiểm tra nào. Nhấn "Lập Kế Hoạch Mới" để bắt đầu.</td></tr>';
        return;
    }

    tbody.innerHTML = rawPlansData.map((plan, idx) => {
        const renderAvatars = (users) => {
            if (!users || !users.length) return '<span class="text-muted small">Chưa gán</span>';
            return `<div class="d-flex flex-wrap gap-1">${users.map(u => {
                const initials = (u.fullname || u.username || 'NV').slice(0, 2).toUpperCase();
                return `<span class="user-avatar-circle" title="${escapeHtml(u.fullname || u.username)} (${escapeHtml(u.username)})">${initials}</span>`;
            }).join('')}</div>`;
        };

        const renderZoneBadges = (zones) => {
            if (!zones || !zones.length) return '<span class="text-muted small">Tất cả khu vực</span>';
            return `<div class="d-flex flex-wrap gap-1">${zones.map(z => 
                `<span class="badge bg-light text-dark border font-monospace" style="font-size:11px;">${escapeHtml(z.zone_code)}</span>`
            ).join('')}</div>`;
        };

        const freqLabels = {
            'daily': 'Hàng ngày',
            'weekly': 'Hàng tuần',
            'monthly': 'Hàng tháng',
            'ad_hoc': 'Đột xuất'
        };

        const isActive = plan.status === 'active';

        return `
            <tr class="${isActive ? '' : 'table-secondary opacity-75'}">
                <td>${idx + 1}</td>
                <td>
                    <strong class="text-dark">${escapeHtml(plan.plan_name)}</strong>
                    ${plan.notes ? `<div class="small text-muted">${escapeHtml(plan.notes)}</div>` : ''}
                </td>
                <td><span class="badge bg-primary-subtle text-primary">${freqLabels[plan.frequency] || plan.frequency}</span></td>
                <td>${renderZoneBadges(plan.zones_list)}</td>
                <td>${renderAvatars(plan.inspectors_list)}</td>
                <td>${renderAvatars(plan.assignees_list)}</td>
                <td>${renderAvatars(plan.approvers_list)}</td>
                <td>
                    <div class="small font-monospace">${escapeHtml(plan.start_date)}</div>
                    ${plan.end_date ? `<div class="small text-muted font-monospace">đến ${escapeHtml(plan.end_date)}</div>` : '<div class="small text-muted">Vô thời hạn</div>'}
                </td>
                <td>
                    <span class="badge ${isActive ? 'bg-success' : 'bg-secondary'}" ${CAN_5S_SETTINGS ? `style="cursor: pointer;" onclick="togglePlanStatus(${plan.id}, '${plan.status}')" title="Nhấp để chuyển trạng thái"` : ''}>
                        ${isActive ? 'Đang chạy' : 'Tạm dừng'}
                    </span>
                </td>
                <td class="text-center">
                    ${CAN_5S_SETTINGS ? `
                        <button type="button" class="btn btn-outline-primary btn-sm p-1 me-1" onclick="openOffcanvasPlan(${plan.id})" title="Chỉnh sửa">
                            <span class="material-icons fs-6">edit</span>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="deletePlan(${plan.id})" title="Xóa kế hoạch">
                            <span class="material-icons fs-6">delete</span>
                        </button>
                    ` : '<span class="badge bg-light text-muted border">Chỉ xem</span>'}
                </td>
            </tr>
        `;
    }).join('');
}

function onPlanFrequencyChanged() {
    const f = document.getElementById('oc_plan_frequency').value;
    document.getElementById('oc-plan-weekly-days-box').classList.toggle('d-none', f !== 'weekly');
}

function toggleSelectAllPlanZones() {
    const checks = document.querySelectorAll('#oc-plan-zones-container input[type="checkbox"]');
    const allChecked = Array.from(checks).every(c => c.checked);
    checks.forEach(c => c.checked = !allChecked);
}

function openOffcanvasPlan(planId) {
    document.getElementById('form-offcanvas-plan').reset();
    document.getElementById('oc_plan_id').value = 0;
    document.getElementById('offcanvasPlanLabel').textContent = 'Lập Kế Hoạch Kiểm Tra 5S Mới';
    onPlanFrequencyChanged();

    // Populate zones checkboxes
    const zonesContainer = document.getElementById('oc-plan-zones-container');
    const zones = (rawDashboardData && rawDashboardData.zones) ? rawDashboardData.zones : [];
    
    zonesContainer.innerHTML = zones.map(z => `
        <div class="form-check form-check-inline me-3 mb-1">
            <input class="form-check-input plan-zone-chk" type="checkbox" name="zone_ids[]" value="${z.id}" id="chk_pz_${z.id}">
            <label class="form-check-label small" for="chk_pz_${z.id}">${escapeHtml(z.zone_name)} (${escapeHtml(z.zone_code)})</label>
        </div>
    `).join('');

    if (planId && rawPlansData) {
        const plan = rawPlansData.find(p => Number(p.id) === Number(planId));
        if (plan) {
            document.getElementById('offcanvasPlanLabel').textContent = 'Chỉnh Sửa Kế Hoạch: ' + plan.plan_name;
            document.getElementById('oc_plan_id').value = plan.id;
            document.getElementById('oc_plan_name').value = plan.plan_name;
            document.getElementById('oc_plan_frequency').value = plan.frequency;
            document.getElementById('oc_plan_status').value = plan.status;
            document.getElementById('oc_plan_start_date').value = plan.start_date;
            document.getElementById('oc_plan_end_date').value = plan.end_date || '';
            document.getElementById('oc_plan_notes').value = plan.notes || '';
            onPlanFrequencyChanged();

            // Check selected zones
            const selectedZIds = JSON.parse(plan.zone_ids_json || '[]').map(Number);
            document.querySelectorAll('.plan-zone-chk').forEach(chk => {
                chk.checked = selectedZIds.includes(Number(chk.value));
            });

            // Set users in pickers
            if (pickerInspectors) pickerInspectors.setValues(JSON.parse(plan.inspectors_json || '[]'));
            if (pickerAssignees) pickerAssignees.setValues(JSON.parse(plan.assignees_json || '[]'));
            if (pickerApprovers) pickerApprovers.setValues(JSON.parse(plan.approvers_json || '[]'));
        }
    } else {
        // Reset pickers
        if (pickerInspectors) pickerInspectors.setValues([]);
        if (pickerAssignees) pickerAssignees.setValues([]);
        if (pickerApprovers) pickerApprovers.setValues([]);
        // Default check all active zones
        document.querySelectorAll('.plan-zone-chk').forEach(chk => chk.checked = true);
    }

    new bootstrap.Offcanvas(document.getElementById('offcanvasPlan')).show();
}

function saveOffcanvasPlan(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('form-offcanvas-plan'));

    fetch('api/five_s_plans.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasPlan')).hide();
                loadPlansList();
                loadDashboardData();
            }
        })
        .catch(err => alert('Lỗi lưu kế hoạch kiểm tra'));
}

function togglePlanStatus(planId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('id', planId);
    fd.append('status', newStatus);

    fetch('api/five_s_plans.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) loadPlansList();
        });
}

function deletePlan(planId) {
    if (!confirm('Bạn có chắc chắn muốn xóa kế hoạch kiểm tra này?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', planId);

    fetch('api/five_s_plans.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                loadPlansList();
                loadDashboardData();
            }
        });
}

function renderPlansOverviewTable(data) {
    const tbody = document.getElementById('plans-overview-tbody');
    const zones = data.zones || [];
    if (!zones.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Chưa có vị trí 5S nào.</td></tr>';
        return;
    }

    tbody.innerHTML = zones.map(z => `
        <tr>
            <td><strong>${escapeHtml(z.zone_name)}</strong><div class="small text-muted font-monospace">${escapeHtml(z.zone_code)}</div></td>
            <td>${escapeHtml(z.factory || 'Nhà Máy 1')} · ${escapeHtml(z.area || z.department)}</td>
            <td><span class="badge bg-light text-dark font-monospace">${escapeHtml(z.frequency || 'daily')}</span></td>
            <td><span class="badge bg-success-subtle text-success">Đang theo dõi</span></td>
            <td><span class="badge ${Number(z.is_active) ? 'bg-success' : 'bg-secondary'}">${Number(z.is_active) ? 'Hoạt động' : 'Ngừng dùng'}</span></td>
        </tr>
    `).join('');
}

// --------------------------------------------------------------------------
// TAB 5: QUẢN LÝ DANH MỤC VỊ TRÍ (LOẠI BỎ PHÂN CÔNG NHÂN SỰ)
// --------------------------------------------------------------------------
function populateZoneFilters(data) {
    const facSel = document.getElementById('zones_filter_factory');
    const areaSel = document.getElementById('zones_filter_area');
    const auditSel = document.getElementById('audit-select-zone');

    facSel.innerHTML = '<option value="">-- Tất cả nhà xưởng --</option>' + (data.factories || []).map(f => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join('');
    areaSel.innerHTML = '<option value="">-- Tất cả khu vực --</option>' + (data.areas || []).map(a => `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`).join('');
    auditSel.innerHTML = '<option value="">-- Chọn vị trí để kiểm tra --</option>' + (data.zones || []).map(z => `<option value="${z.id}" data-token="${escapeHtml(z.qr_code_hash || ('5S-' + z.zone_code))}">${escapeHtml(z.zone_name)} (${escapeHtml(z.zone_code)})</option>`).join('');
}

function renderZonesList() {
    if (!rawDashboardData) return;
    const tbody = document.getElementById('zones-table-tbody');
    const kw = document.getElementById('zones_search_kw').value.trim().toLowerCase();
    const fac = document.getElementById('zones_filter_factory').value;
    const area = document.getElementById('zones_filter_area').value;
    const act = document.getElementById('zones_filter_active').value;

    let items = rawDashboardData.zones || [];
    document.getElementById('zones-count-badge').textContent = items.length;

    items = items.filter(z => {
        const matchesKw = !kw || (z.zone_name || '').toLowerCase().includes(kw) || (z.zone_code || '').toLowerCase().includes(kw);
        const matchesFac = !fac || z.factory === fac;
        const matchesArea = !area || (z.area === area || z.department === area);
        const matchesAct = (act === 'all') || (Number(z.is_active) === Number(act));
        return matchesKw && matchesFac && matchesArea && matchesAct;
    });

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Không tìm thấy vị trí nào.</td></tr>';
        return;
    }

    tbody.innerHTML = items.map((z, idx) => `
        <tr class="${Number(z.is_active) ? '' : 'table-secondary opacity-75'}">
            <td>${idx + 1}</td>
            <td><strong class="font-monospace text-primary">${escapeHtml(z.zone_code)}</strong></td>
            <td>
                <strong>${escapeHtml(z.zone_name)}</strong>
                <div class="small text-muted">${escapeHtml(z.description || '')}</div>
            </td>
            <td>${escapeHtml(z.factory || 'Nhà Máy 1')}</td>
            <td>${escapeHtml(z.area || z.department)}</td>
            <td><span class="badge bg-light text-dark font-monospace border">${escapeHtml(z.qr_code_hash || ('5S-' + z.zone_code))}</span></td>
            <td>
                <span class="badge ${Number(z.is_active) ? 'bg-success' : 'bg-secondary'}" ${CAN_5S_SETTINGS ? `style="cursor: pointer;" onclick="toggleZoneActive(${z.id})" title="Nhấp để chuyển trạng thái"` : ''}>
                    ${Number(z.is_active) ? 'Đang dùng' : 'Ngừng dùng'}
                </span>
            </td>
            <td class="text-center">
                ${CAN_5S_SETTINGS ? `
                    <button type="button" class="btn btn-outline-primary btn-sm p-1 me-1" onclick="openOffcanvasZone(${z.id})" title="Chỉnh sửa">
                        <span class="material-icons fs-6">edit</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="deleteZone(${z.id})" title="Xóa vị trí">
                        <span class="material-icons fs-6">delete</span>
                    </button>
                ` : '<span class="badge bg-light text-muted border">Chỉ xem</span>'}
            </td>
        </tr>
    `).join('');
}

function openOffcanvasZone(zoneId) {
    document.getElementById('form-offcanvas-zone').reset();
    document.getElementById('oc_zone_id').value = '';
    document.getElementById('offcanvasZoneLabel').textContent = 'Khai Báo Vị Trí Kiểm Tra Mới';

    if (zoneId && rawDashboardData) {
        const z = (rawDashboardData.zones || []).find(item => Number(item.id) === Number(zoneId));
        if (z) {
            document.getElementById('offcanvasZoneLabel').textContent = 'Chỉnh Sửa Vị Trí: ' + z.zone_name;
            document.getElementById('oc_zone_id').value = z.id;
            document.getElementById('oc_zone_code').value = z.zone_code || '';
            document.getElementById('oc_zone_name').value = z.zone_name || '';
            document.getElementById('oc_factory').value = z.factory || 'Nhà Máy 1';
            document.getElementById('oc_area').value = z.area || z.department || '';
            document.getElementById('oc_machine').value = z.machine || '';
            document.getElementById('oc_process_step').value = z.process_step || '';
            document.getElementById('oc_qr_code').value = z.qr_code_hash || ('5S-' + z.zone_code);
            document.getElementById('oc_description').value = z.description || '';
            document.getElementById('oc_is_active').checked = Number(z.is_active) === 1;
        }
    }

    new bootstrap.Offcanvas(document.getElementById('offcanvasZone')).show();
}

function syncOcQrToken() {
    const c = document.getElementById('oc_zone_code').value.trim();
    if (c) document.getElementById('oc_qr_code').value = '5S-' + c;
}

function saveOffcanvasZone(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('form-offcanvas-zone'));

    fetch('api/five_s_save_zone.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasZone')).hide();
                loadDashboardData();
            }
        })
        .catch(err => alert('Lỗi lưu vị trí 5S'));
}

function toggleZoneActive(zoneId) {
    const fd = new FormData();
    fd.append('action', 'toggle_active');
    fd.append('zone_id', zoneId);

    fetch('api/five_s_save_zone.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadDashboardData();
            else alert(data.message);
        });
}

function deleteZone(zoneId) {
    if (!confirm('Bạn có chắc chắn muốn xóa vị trí kiểm tra 5S này?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('zone_id', zoneId);

    fetch('api/five_s_save_zone.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) loadDashboardData();
        });
}

// --------------------------------------------------------------------------
// TAB 6: TIÊU CHUẨN ĐÁNH GIÁ 5S THEO KHU VỰC (CARD VIEW)
// --------------------------------------------------------------------------
function populateStandardsZoneFilter(zones) {
    if (!zones) return;
    const filterSel = document.getElementById('standards_zone_filter');
    const ocStdZoneSel = document.getElementById('oc_std_zone_id');
    const copySrcSel = document.getElementById('copy_source_zone_id');
    const copyTgtSel = document.getElementById('copy_target_zone_id');

    const opts = zones.map(z => `<option value="${z.id}">${escapeHtml(z.zone_name)} (${escapeHtml(z.zone_code)})</option>`).join('');

    filterSel.innerHTML = '<option value="0">-- Tất cả khu vực --</option>' + opts;
    ocStdZoneSel.innerHTML = '<option value="">-- Chọn khu vực --</option>' + opts;
    copySrcSel.innerHTML = '<option value="">-- Chọn khu vực nguồn --</option>' + opts;
    copyTgtSel.innerHTML = '<option value="">-- Chọn khu vực đích --</option>' + opts;
}

function onStandardsZoneChanged() {
    loadStandardsForZone();
}

function loadStandardsForZone() {
    const filterEl = document.getElementById('standards_zone_filter');
    const zoneId = filterEl ? Number(filterEl.value) : 0;

    let url = 'api/five_s_standards.php?action=list';
    if (zoneId > 0) url += `&zone_id=${zoneId}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            rawStandardsData = data.standards || [];
            renderStandardsCards(data.tree || {});
        })
        .catch(err => console.error('Lỗi tải tiêu chuẩn:', err));
}

function renderStandardsCards(tree) {
    const container = document.getElementById('standards-cards-container');
    const groups = Object.keys(tree);

    if (!groups.length) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <span class="material-icons fs-1 text-muted">playlist_add</span>
                <div class="mt-2 fw-bold">Chưa có tiêu chuẩn đánh giá nào cho khu vực này.</div>
                <div class="small">Nhấn "Thêm tiêu chuẩn mới" hoặc "Sao chép tiêu chuẩn" từ khu vực khác để bắt đầu.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = groups.map(g => `
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                <h3 class="h6 fw-bold text-primary mb-0 d-flex align-items-center gap-1">
                    <span class="material-icons fs-5">folder</span> ${escapeHtml(g)}
                </h3>
                <span class="badge bg-primary-subtle text-primary">${tree[g].length} tiêu chí</span>
            </div>
            <div class="row g-3">
                ${tree[g].map(std => `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="standard-card">
                            <div class="standard-card-header">
                                <span class="badge bg-light text-dark border font-monospace">${escapeHtml(std.standard_code)}</span>
                                <span class="badge bg-${getSeverityColor(std.severity_level)}-subtle text-${getSeverityColor(std.severity_level)} border">
                                    Ưu tiên: ${escapeHtml(std.severity_level || 'medium')}
                                </span>
                            </div>
                            <div class="standard-card-body">
                                <h4 class="h6 fw-bold text-dark mb-1">${escapeHtml(std.item_name)}</h4>
                                ${std.zone_name ? `<div class="small text-muted mb-2 font-monospace">Khu vực: ${escapeHtml(std.zone_name)}</div>` : ''}
                                <div class="small text-secondary mb-3">${escapeHtml(std.evaluation_criteria || std.description || 'Chưa có tiêu chuẩn chi tiết')}</div>
                                ${std.note ? `<div class="small text-muted fst-italic mb-2">Ghi chú: ${escapeHtml(std.note)}</div>` : ''}

                                <!-- OK / NG Preview Images -->
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="text-center p-1 border rounded bg-light">
                                            <div class="small fw-bold text-success mb-1" style="font-size:10px;">Chuẩn OK</div>
                                            ${std.ok_reference_image ? `
                                                <img src="${std.ok_reference_image}" class="std-img-thumb" onclick="previewImageZoom('${std.ok_reference_image}', 'Chuẩn OK - ${escapeHtml(std.item_name)}')" title="Nhấp để phóng to">
                                            ` : '<div class="small text-muted py-3" style="font-size:11px;">Chưa có ảnh</div>'}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-1 border rounded bg-light">
                                            <div class="small fw-bold text-danger mb-1" style="font-size:10px;">Lỗi mẫu NG</div>
                                            ${std.ng_reference_image ? `
                                                <img src="${std.ng_reference_image}" class="std-img-thumb" onclick="previewImageZoom('${std.ng_reference_image}', 'Lỗi NG - ${escapeHtml(std.item_name)}')" title="Nhấp để phóng to">
                                            ` : '<div class="small text-muted py-3" style="font-size:11px;">Chưa có ảnh</div>'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ${CAN_5S_SETTINGS ? `
                            <div class="p-2 border-top bg-light d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm p-1" onclick="openOffcanvasStandard(${std.id})" title="Sửa tiêu chuẩn">
                                    <span class="material-icons fs-6">edit</span>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="deleteStandard(${std.id})" title="Xóa tiêu chuẩn">
                                    <span class="material-icons fs-6">delete</span>
                                </button>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

function getSeverityColor(lvl) {
    if (lvl === 'critical') return 'danger';
    if (lvl === 'high') return 'warning';
    if (lvl === 'low') return 'info';
    return 'primary';
}

function openOffcanvasStandard(id) {
    document.getElementById('form-offcanvas-standard').reset();
    document.getElementById('oc_std_id').value = '';
    document.getElementById('offcanvasStdLabel').textContent = 'Khai Báo Tiêu Chuẩn 5S Khu Vực';
    document.getElementById('oc_std_ok_preview').innerHTML = '';
    document.getElementById('oc_std_ng_preview').innerHTML = '';

    // Auto select zone from filter if active
    const curFilterZone = document.getElementById('standards_zone_filter').value;
    if (curFilterZone && Number(curFilterZone) > 0) {
        document.getElementById('oc_std_zone_id').value = curFilterZone;
    }

    if (id && rawStandardsData) {
        const item = rawStandardsData.find(s => Number(s.id) === Number(id));
        if (item) {
            document.getElementById('offcanvasStdLabel').textContent = 'Chỉnh Sửa Tiêu Chuẩn: ' + item.standard_code;
            document.getElementById('oc_std_id').value = item.id;
            document.getElementById('oc_std_zone_id').value = item.zone_id || '';
            document.getElementById('oc_std_group').value = item.group_name;
            document.getElementById('oc_std_code').value = item.standard_code;
            document.getElementById('oc_std_name').value = item.item_name;
            document.getElementById('oc_std_criteria').value = item.evaluation_criteria || '';
            document.getElementById('oc_std_severity').value = item.severity_level || 'medium';
            document.getElementById('oc_std_note').value = item.note || '';
            document.getElementById('oc_std_sort').value = item.sort_order || 1;

            if (item.ok_reference_image) {
                document.getElementById('oc_std_ok_preview').innerHTML = `<img src="${item.ok_reference_image}" class="img-thumbnail mt-1" style="max-height:60px;">`;
            }
            if (item.ng_reference_image) {
                document.getElementById('oc_std_ng_preview').innerHTML = `<img src="${item.ng_reference_image}" class="img-thumbnail mt-1" style="max-height:60px;">`;
            }
        }
    }

    new bootstrap.Offcanvas(document.getElementById('offcanvasStandard')).show();
}

function saveOffcanvasStandard(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('form-offcanvas-standard'));
    formData.append('action', 'save');

    fetch('api/five_s_standards.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasStandard')).hide();
                loadStandardsForZone();
            }
        })
        .catch(err => alert('Lỗi kết nối lưu tiêu chuẩn'));
}

function deleteStandard(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa tiêu chuẩn này?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('api/five_s_standards.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) loadStandardsForZone();
        });
}

function openOffcanvasCopyStandards() {
    new bootstrap.Offcanvas(document.getElementById('offcanvasCopyStandards')).show();
}

function submitCopyStandards(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('form-copy-standards'));
    formData.append('action', 'copy_from_zone');

    fetch('api/five_s_standards.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasCopyStandards')).hide();
                loadStandardsForZone();
            }
        })
        .catch(err => alert('Lỗi sao chép tiêu chuẩn'));
}

// --------------------------------------------------------------------------
// TAB 7: QR CODE & IN THẺ
// --------------------------------------------------------------------------
function populateQrFilters(data) {
    const facSel = document.getElementById('qr_filter_factory');
    const areaSel = document.getElementById('qr_filter_area');

    facSel.innerHTML = '<option value="">-- Tất cả nhà xưởng --</option>' + (data.factories || []).map(f => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join('');
    areaSel.innerHTML = '<option value="">-- Tất cả khu vực --</option>' + (data.areas || []).map(a => `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`).join('');
}

function renderQrCardsGrid() {
    if (!rawDashboardData) return;
    const container = document.getElementById('qr-cards-grid-container');
    const kw = document.getElementById('qr_search_input').value.trim().toLowerCase();
    const fac = document.getElementById('qr_filter_factory').value;
    const area = document.getElementById('qr_filter_area').value;

    let items = (rawDashboardData.zones || []).filter(z => Number(z.is_active) === 1);
    items = items.filter(z => {
        const matchesKw = !kw || (z.zone_name || '').toLowerCase().includes(kw) || (z.zone_code || '').toLowerCase().includes(kw);
        const matchesFac = !fac || z.factory === fac;
        const matchesArea = !area || (z.area === area || z.department === area);
        return matchesKw && matchesFac && matchesArea;
    });

    if (!items.length) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted">Không tìm thấy vị trí nào.</div>';
        return;
    }

    container.innerHTML = items.map(z => `
        <div class="col-12 col-md-6 col-xl-4">
            <div class="qr-ticket-card" id="qr-ticket-${z.id}">
                <div class="h5 fw-bold text-primary mb-1">DX PLASTIC GROUP</div>
                <div class="small text-uppercase fw-bold text-muted mb-2">VỊ TRÍ KIỂM TRA 5S TIÊU CHUẨN</div>
                
                <div id="qrcode-box-${z.id}" class="d-flex justify-content-center my-3"></div>

                <div class="h6 fw-bold text-dark mb-1">${escapeHtml(z.zone_name)}</div>
                <div class="font-monospace small text-primary fw-bold mb-1">Mã: ${escapeHtml(z.zone_code)}</div>
                <div class="badge bg-light text-dark border mb-2">${escapeHtml(z.factory || 'Nhà Máy 1')} · ${escapeHtml(z.area || z.department)}</div>
                
                <div class="small text-muted mb-3" style="font-size: 11px;">
                    Quét mã QR để mở trực tiếp checklist kiểm tra hiện trường
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm no-print" onclick="printSingleQrTicket(${z.id})">
                    <span class="material-icons fs-6 align-middle">print</span> In thẻ này
                </button>
            </div>
        </div>
    `).join('');

    // Generate QR canvases with direct URL
    items.forEach(z => {
        const directUrl = `${window.location.origin}${window.location.pathname}?mainpage=five_s&tab=audit&zone_code=${encodeURIComponent(z.zone_code)}`;
        const box = document.getElementById(`qrcode-box-${z.id}`);
        if (box) {
            box.innerHTML = '';
            new QRCode(box, {
                text: directUrl,
                width: 140,
                height: 140,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }
    });
}

function printSingleQrTicket(zoneId) {
    const card = document.getElementById(`qr-ticket-${zoneId}`);
    if (!card) return;
    const printArea = document.getElementById('printableQrArea');
    printArea.innerHTML = `<div class="p-4 d-flex justify-content-center">${card.outerHTML}</div>`;
    printArea.classList.remove('d-none');
    window.print();
    printArea.classList.add('d-none');
}

function printAllVisibleQrCards() {
    const grid = document.getElementById('qr-cards-grid-container');
    if (!grid) return;
    const printArea = document.getElementById('printableQrArea');
    printArea.innerHTML = `<div class="p-3"><div class="row g-3">${grid.innerHTML}</div></div>`;
    printArea.classList.remove('d-none');
    window.print();
    printArea.classList.add('d-none');
}

// --------------------------------------------------------------------------
// HELPER UTILITIES
// --------------------------------------------------------------------------
function fillGlobalUserSelects(users) {
    if (!users || !users.length) return;
    const opts = users.map(u => `<option value="${u.id}">${escapeHtml(u.fullname || u.username)}</option>`).join('');
    const assSel = document.getElementById('oc_issue_assignee_id');
    const appSel = document.getElementById('oc_issue_approver_id');
    if (assSel) assSel.innerHTML = '<option value="">-- Chọn người xử lý --</option>' + opts;
    if (appSel) appSel.innerHTML = '<option value="">-- Chọn người xác nhận --</option>' + opts;
}

function previewImageZoom(src, title = 'Xem hình ảnh') {
    if (!src) return;
    document.getElementById('modalImageTitle').textContent = title;
    document.getElementById('modalImageSrc').src = src;
    new bootstrap.Modal(document.getElementById('modalImagePreview')).show();
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