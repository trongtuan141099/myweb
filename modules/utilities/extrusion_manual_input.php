<?php
/**
 * MODULE TIỆN ÍCH: TRANG NHẬP BÁO CÁO SẢN XUẤT ĐÙN THỦ CÔNG (MANUAL INPUT & EDIT)
 * Thiết kế giao diện theo chuẩn MES & Enterprise UI/UX tối ưu cho màn hình 1920x1080
 * Bám sát 100% cấu trúc và công thức của Format_Báo Cáo Đùn 7.2026.xlsm
 */
$editReportId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$isLoggedIn   = isset($_SESSION['user_id']) || isset($_SESSION['user']);
?>
<style>
/* ==========================================================================
   CSS TỐI ƯU GIAO DIỆN MES CHO MÀN HÌNH 1920x1080 (COMPACT & HIGH DENSITY)
   ========================================================================== */
.ext-manual-page {
    --ext-border: var(--dx-border, #cbd5e1);
    --ext-card-bg: var(--dx-card-bg, #ffffff);
    --ext-bg-soft: #f8fafc;
    --ext-input-yellow: #fefce8;
    --ext-input-yellow-border: #fde047;
    --ext-calc-blue: #eff6ff;
    --ext-calc-blue-border: #93c5fd;
    --ext-header-navy: #0f172a;
    --ext-accent: #0284c7;
    --ext-success: #059669;
    --ext-warning: #d97706;
    --ext-danger: #dc2626;
    padding-bottom: 30px;
}
[data-theme="dark"] .ext-manual-page {
    --ext-border: #334155;
    --ext-card-bg: #1e293b;
    --ext-bg-soft: #0f172a;
    --ext-input-yellow: rgba(250, 204, 21, 0.08);
    --ext-input-yellow-border: rgba(250, 204, 21, 0.35);
    --ext-calc-blue: rgba(56, 189, 248, 0.1);
    --ext-calc-blue-border: rgba(56, 189, 248, 0.35);
    --ext-header-navy: #090d16;
}

/* Floating Action Bar cố định góc trên cùng bên phải */
.ext-floating-bar {
    position: sticky;
    top: 56px;
    z-index: 1020;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid var(--ext-border);
    border-radius: 10px;
    padding: 6px 14px;
    margin-bottom: 12px;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
[data-theme="dark"] .ext-floating-bar {
    background: rgba(30, 41, 59, 0.94);
}
.ext-floating-bar .bar-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ext-floating-bar .bar-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dx-text-main, #0f172a);
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
}
.ext-floating-bar .bar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Các Card chính theo chuẩn MES */
.ext-card {
    background: var(--ext-card-bg);
    border: 1px solid var(--ext-border);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    margin-bottom: 12px;
}
.ext-card-header {
    background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 7px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-weight: 700;
    font-size: 13px;
}
.ext-card-header.sec-teal {
    background: linear-gradient(90deg, #0f766e 0%, #115e59 100%);
}
.ext-card-header.sec-indigo {
    background: linear-gradient(90deg, #3730a3 0%, #1e1b4b 100%);
}
.ext-card-header.sec-slate {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}
.ext-card-header .sec-title {
    display: flex;
    align-items: center;
    gap: 6px;
}
.ext-card-header .sec-badge {
    font-size: 10.5px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 5px;
    background: rgba(255,255,255,0.15);
    color: #38bdf8;
}
.ext-card-body {
    padding: 10px 12px;
}

/* Trường nhập liệu thu gọn & Label */
.ext-fg {
    margin-bottom: 6px;
}
.ext-lbl {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: var(--dx-text-main, #334155);
    margin-bottom: 2px;
    line-height: 1.2;
}
.ext-lbl .cell-ref {
    font-size: 9.5px;
    font-weight: 600;
    color: #64748b;
    background: rgba(100, 116, 139, 0.12);
    padding: 0 4px;
    border-radius: 3px;
    font-family: monospace;
}
.ext-inp {
    width: 100%;
    height: 31px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid var(--ext-border);
    background: var(--ext-card-bg);
    color: var(--dx-text-main, #0f172a);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.ext-inp-manual {
    background: var(--ext-input-yellow);
    border-color: #eab308;
}
.ext-inp-calc {
    background: var(--ext-calc-blue);
    border-color: var(--ext-calc-blue-border);
    color: #0369a1;
    font-weight: 700;
}
[data-theme="dark"] .ext-inp-calc {
    color: #38bdf8;
}
.ext-inp:focus {
    outline: none;
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.18);
}

/* Vùng con trong Card Vật Liệu (3 vùng ngang) */
.mat-zone {
    background: var(--ext-bg-soft);
    border: 1px solid var(--ext-border);
    border-radius: 8px;
    padding: 8px 10px;
    height: 100%;
}
.mat-zone-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    padding-bottom: 4px;
    margin-bottom: 6px;
    border-bottom: 1.5px solid;
}
.mat-zone-virgin .mat-zone-header { color: #0284c7; border-color: #bae6fd; }
.mat-zone-ng .mat-zone-header { color: #d97706; border-color: #fde68a; }
.mat-zone-clean .mat-zone-header { color: #7c3aed; border-color: #ddd6fe; }

/* KPI Box Summary */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}
.kpi-card {
    background: var(--ext-bg-soft);
    border: 1px solid var(--ext-border);
    border-radius: 8px;
    padding: 6px 10px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3.5px;
    background: #0284c7;
}
.kpi-card.kpi-eff::before { background: #2563eb; }
.kpi-card.kpi-run::before { background: #059669; }
.kpi-card.kpi-hum::before { background: #7c3aed; }
.kpi-card.kpi-err::before { background: #dc2626; }
.kpi-card.kpi-stop::before { background: #d97706; }
.kpi-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--dx-text-muted, #64748b);
    letter-spacing: 0.02em;
}
.kpi-num {
    font-size: 15px;
    font-weight: 800;
    color: var(--dx-text-main, #0f172a);
    line-height: 1.25;
}

/* Bảng thời gian dừng máy gọn gàng */
.ext-dt-wrap {
    max-height: 235px;
    overflow-y: auto;
    border: 1px solid var(--ext-border);
    border-radius: 7px;
}
.ext-dt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.ext-dt-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #1e293b;
    color: #ffffff;
    padding: 6px 6px;
    font-weight: 700;
    font-size: 11px;
    text-align: center;
    border-bottom: 2px solid #334155;
    white-space: nowrap;
}
.ext-dt-table tbody td {
    padding: 3px 6px;
    border-bottom: 1px solid var(--ext-border);
    vertical-align: middle;
}
.ext-dt-table tbody tr:hover {
    background: rgba(2, 132, 199, 0.06);
}
.ext-dt-table tbody tr.dt-row-active {
    background: rgba(16, 185, 129, 0.1) !important;
}
.ext-dt-table tbody tr.dt-row-summary {
    background: rgba(59, 130, 246, 0.12) !important;
    font-weight: 700;
}
.ext-dt-inp {
    width: 100%;
    height: 26px;
    padding: 1px 4px;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--ext-border);
    border-radius: 4px;
    background: var(--ext-card-bg);
    color: var(--dx-text-main);
}
.ext-dt-inp:focus {
    outline: none;
    border-color: #0284c7;
    background: var(--ext-input-yellow);
}
.dt-group-pill {
    display: inline-block;
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}
.dt-grp-mgmt { background: #e0f2fe; color: #0369a1; }
.dt-grp-prod { background: #dcfce7; color: #15803d; }
.dt-grp-equip { background: #fef3c7; color: #b45309; }
.dt-grp-clean { background: #f3e8ff; color: #7e22ce; }

/* Filter Tabs & Quick Add Bar */
.dt-filter-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.dt-tab-btn {
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 5px;
    border: 1px solid var(--ext-border);
    background: var(--ext-bg-soft);
    color: var(--dx-text-main);
    cursor: pointer;
    transition: all 0.15s;
}
.dt-tab-btn.active, .dt-tab-btn:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

/* Badge trạng thái chỉ thị CTSX */
.status-badge-ctsx {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid transparent;
}
.status-badge-ctsx.valid {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.35);
}
.status-badge-ctsx.adjust {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.35);
}
.status-badge-ctsx.empty {
    background: rgba(100, 116, 139, 0.12);
    color: #64748b;
    border-color: rgba(100, 116, 139, 0.25);
}

/* Check badges Realtime */
.ext-check-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
}
.ext-check-ok { background: rgba(16, 185, 129, 0.12); color: #059669; border-color: rgba(16, 185, 129, 0.3); }
.ext-check-warn { background: rgba(245, 158, 11, 0.14); color: #d97706; border-color: rgba(245, 158, 11, 0.35); }
.ext-check-err { background: rgba(239, 68, 68, 0.12); color: #dc2626; border-color: rgba(239, 68, 68, 0.35); }

/* Banner Edit Mode */
.ext-edit-banner {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #fffbeb;
    border: 1px solid #f59e0b;
    color: #92400e;
    margin-bottom: 10px;
    font-weight: 700;
    font-size: 12.5px;
}
[data-theme="dark"] .ext-edit-banner {
    background: rgba(245, 158, 11, 0.15);
    border-color: rgba(245, 158, 11, 0.45);
    color: #fbbf24;
}

/* Phím tắt gợi ý */
.kbd-tag {
    display: inline-block;
    padding: 1px 4px;
    font-size: 9.5px;
    font-family: monospace;
    background: rgba(15, 23, 42, 0.15);
    border-radius: 3px;
    font-weight: 700;
}

/* Modal Xem TG Thao Tác */
.ext-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 1090;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.ext-modal-content {
    background: var(--ext-card-bg);
    border: 1px solid var(--ext-border);
    border-radius: 12px;
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

/* In ấn (Print Stylesheet) */
@media print {
    .top-header, .app-sidebar, .ext-floating-bar, .dt-filter-tabs, .dt-quick-bar,
    .app-page-actions, #editModeBanner, #recentReportsSection, .ext-modal-backdrop {
        display: none !important;
    }
    .main-wrapper, .app-container, .app-page-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .ext-card {
        border: 1px solid #94a3b8 !important;
        page-break-inside: avoid;
    }
    .ext-dt-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
}
</style>

<div class="app-page-wrapper ext-manual-page">
    <!-- =====================================================================
         FLOATING ACTION BAR (CỐ ĐỊNH TRÊN CÙNG BÊN PHẢI THEO MỤC 8)
         ===================================================================== -->
    <div class="ext-floating-bar">
        <div class="bar-title-wrap">
            <h1 class="bar-title">
                <span class="material-icons" style="color:#0284c7; font-size:22px;">edit_note</span>
                <span>Nhập Báo Cáo Đùn (Thủ Công)</span>
            </h1>
            <div id="badge_directive_status" class="status-badge-ctsx empty" title="Trạng thái chỉ thị sản xuất">
                <span class="material-icons" style="font-size:14px;">pending</span>
                <span id="txt_directive_status">Chờ mã CTSX</span>
            </div>
        </div>

        <div class="bar-actions">
            <!-- Nút Tra Cứu Thực Tích -->
            <a href="index.php?mainpage=utilities&subpage=extrusion_actual_lookup" class="app-btn app-btn-secondary" style="height:31px; padding:0 9px; font-size:12px;" title="Mở trang Tra cứu & Xuất Excel DATA_PLASTIC / DATA_TIME">
                <span class="material-icons" style="font-size:16px;">manage_search</span> Tra Cứu
            </a>

            <!-- Nút Xem Thời Gian Thao Tác (Theo mục 8) -->
            <button type="button" class="app-btn app-btn-secondary" style="height:31px; padding:0 9px; font-size:12px;" onclick="openHumanTimeDetailModal()" title="Xem chi tiết thời gian thao tác con người đối chiếu chuẩn 7.2h">
                <span class="material-icons" style="font-size:16px; color:#7c3aed;">engineering</span> Xem TG Thao Tác
            </button>

            <!-- Nút In (Theo mục 8) -->
            <button type="button" class="app-btn app-btn-secondary" style="height:31px; padding:0 9px; font-size:12px;" onclick="printExtrusionReport()" title="In biểu mẫu phiếu báo cáo sản xuất">
                <span class="material-icons" style="font-size:16px;">print</span> In
            </button>

            <!-- Nút Làm Mới (Alt+N) -->
            <button type="button" class="app-btn app-btn-secondary" style="height:31px; padding:0 9px; font-size:12px;" onclick="resetManualForm()" title="Làm mới form để nhập báo cáo mới (Alt+N)">
                <span class="material-icons" style="font-size:16px;">restart_alt</span> Làm Mới <span class="kbd-tag">Alt+N</span>
            </button>

            <!-- Nút Lưu / Cập Nhật Chính (Ctrl+S) -->
            <button type="button" class="app-btn app-btn-primary" id="btnTopSaveReport" style="height:32px; padding:0 14px; font-size:12.5px; font-weight:800; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%);" onclick="submitManualReport()" title="Lưu báo cáo sản xuất (Ctrl+S)">
                <span class="material-icons" style="font-size:17px;">save</span> <span id="lblTopSaveBtn">Lưu Báo Cáo</span> <span class="kbd-tag">Ctrl+S</span>
            </button>
        </div>
    </div>

    <!-- BANNER CHẾ ĐỘ CHỈNH SỬA (EDIT MODE) -->
    <div id="editModeBanner" class="ext-edit-banner">
        <div style="display:flex; align-items:center; gap:8px;">
            <span class="material-icons" style="font-size:18px;">edit_calendar</span>
            <span id="editModeText">ĐANG CHỈNH SỬA BÁO CÁO ĐÃ NHẬP</span>
        </div>
        <div>
            <button type="button" class="app-btn app-btn-secondary" style="height:26px; padding:0 8px; font-size:11.5px;" onclick="resetManualForm()">
                <span class="material-icons" style="font-size:14px;">close</span> Hủy sửa / Nhập phiếu mới
            </button>
        </div>
    </div>

    <!-- FORM NHẬP BÁO CÁO CHÍNH -->
    <form id="extrusionManualForm" onsubmit="event.preventDefault(); submitManualReport();">
        <input type="hidden" id="f_id" value="0">
        <input type="hidden" id="f_production_order_code" value="">

        <!-- =================================================================
             KHỐI 1: THÔNG TIN SẢN XUẤT (LAYOUT 4 CỘT THEO MỤC 2)
             ================================================================= -->
        <div class="ext-card">
            <div class="ext-card-header">
                <div class="sec-title">
                    <span class="material-icons" style="font-size:17px; color:#38bdf8;">badge</span>
                    <span>1. Thông Tin Sản Xuất</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:11px; color:#94a3b8;">Mã SX: <b id="kpi_prod_order" style="color:#38bdf8; font-family:monospace;">P_NT_...</b></span>
                    <span class="sec-badge">Layout 4 Cột</span>
                </div>
            </div>
            <div class="ext-card-body">
                <div class="row g-2">
                    <!-- Cột 1: Ngày sản xuất -->
                    <div class="col-12 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Ngày sản xuất <span class="text-danger">*</span></span>
                            <span class="cell-ref">K6</span>
                        </label>
                        <input type="date" id="f_production_date" class="ext-inp ext-inp-manual" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Cột 2: Ca làm việc -->
                    <div class="col-6 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Ca làm việc <span class="text-danger">*</span></span>
                            <span class="cell-ref">E10</span>
                        </label>
                        <select id="f_shift" class="ext-inp ext-inp-manual" onchange="onShiftChanged()">
                            <option value="Ca 1">Ca 1 (06:00 - 14:00)</option>
                            <option value="Ca 2">Ca 2 (14:00 - 22:00)</option>
                            <option value="Ca 3">Ca 3 (22:00 - 06:00)</option>
                            <option value="Ca HC">Ca HC (08:00 - 17:00)</option>
                        </select>
                    </div>

                    <!-- Cột 3: Mã nhân viên -->
                    <div class="col-6 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Mã nhân viên (MSNV) <span class="text-danger">*</span></span>
                            <span class="cell-ref">P6</span>
                        </label>
                        <input type="text" id="f_employee_code" list="dl_employees" class="ext-inp ext-inp-manual" placeholder="VD: 02020965" oninput="onEmployeeCodeInput()" required>
                        <datalist id="dl_employees"></datalist>
                    </div>

                    <!-- Cột 4: Tên nhân viên -->
                    <div class="col-12 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Họ tên nhân viên <span class="text-danger">*</span></span>
                            <span class="cell-ref">Q6</span>
                        </label>
                        <input type="text" id="f_employee_name" list="dl_employee_names" class="ext-inp ext-inp-calc" placeholder="Tự động theo MSNV..." oninput="onEmployeeNameInput()" required>
                        <datalist id="dl_employee_names"></datalist>
                    </div>

                    <!-- Cột 1: Mã CTSX (Tự động điền thông tin sau khi nhập) -->
                    <div class="col-12 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Mã CTSX (Chỉ thị) <span class="text-danger">*</span></span>
                            <span class="cell-ref">C10</span>
                        </label>
                        <input type="text" id="f_mfg_order_code" list="dl_ctsx" class="ext-inp ext-inp-calc" placeholder="VD: P250201305 hoặc DC..." oninput="onMfgOrderCodeInput()">
                        <datalist id="dl_ctsx"></datalist>
                    </div>

                    <!-- Cột 2: Mã sản phẩm -->
                    <div class="col-12 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Mã sản phẩm <span class="text-danger">*</span></span>
                            <span class="cell-ref">G10</span>
                        </label>
                        <input type="text" id="f_product_code" list="dl_products" class="ext-inp ext-inp-manual" placeholder="VD: TU0604B-1MZ2" oninput="onProductionTypeOrProductChanged()" required>
                        <datalist id="dl_products"></datalist>
                    </div>

                    <!-- Cột 3: Số lượng chỉ thị (Kế hoạch / Chỉ thị mét) -->
                    <div class="col-6 col-sm-6 col-lg-3 ext-fg">
                        <label class="ext-lbl">
                            <span>Số lượng chỉ thị (m)</span>
                            <span class="cell-ref">Chỉ thị</span>
                        </label>
                        <input type="number" step="1" id="f_directive_qty" class="ext-inp ext-inp-manual" placeholder="Số mét chỉ thị..." value="0">
                    </div>

                    <!-- Cột 4: Số máy chờ & Loại thử nghiệm & Loại SX -->
                    <div class="col-6 col-sm-6 col-lg-3 ext-fg">
                        <div class="row g-1">
                            <div class="col-6">
                                <label class="ext-lbl">
                                    <span>Máy chờ</span>
                                    <span class="cell-ref">F11</span>
                                </label>
                                <input type="number" id="f_waiting_machine_count" class="ext-inp ext-inp-manual" value="0" min="0" max="24">
                            </div>
                            <div class="col-6">
                                <label class="ext-lbl">
                                    <span>Thử nghiệm</span>
                                    <span class="cell-ref">N6</span>
                                </label>
                                <label style="display:flex; align-items:center; justify-content:center; gap:5px; height:31px; border:1px solid var(--ext-border); border-radius:6px; background:var(--ext-bg-soft); cursor:pointer; font-size:11.5px; font-weight:700;">
                                    <input type="checkbox" id="f_is_test" style="width:15px; height:15px;">
                                    <span>Test</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dải thông tin bổ trợ Size ống & Định mức BOM -->
                <div style="margin-top:4px; padding:4px 10px; border-radius:6px; background:var(--ext-bg-soft); border:1px dashed var(--ext-border); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px; font-size:11.5px;">
                    <div>
                        <span style="color:var(--dx-text-muted);">Loại sản xuất (B10):</span>
                        <select id="f_production_type" style="height:24px; padding:0 6px; font-size:11.5px; border-radius:4px; border:1px solid var(--ext-border); background:var(--ext-card-bg);" onchange="onProductionTypeOrProductChanged()">
                            <option value="Hàng sản xuất">Hàng sản xuất (DATA_PLASTIC)</option>
                            <option value="Hàng bù">Hàng bù (DATA_BU)</option>
                            <option value="Hàng điều chỉnh">Hàng điều chỉnh (DC)</option>
                        </select>
                    </div>
                    <div>
                        <span style="color:var(--dx-text-muted);">Size ống (H10):</span>
                        <input type="text" id="f_pipe_size" readonly style="width:75px; height:24px; padding:0 4px; font-size:11.5px; font-weight:700; text-align:center; border:none; background:transparent; color:#0284c7;">
                    </div>
                    <div>
                        <span style="color:var(--dx-text-muted);">Màu ống (K10):</span>
                        <b id="info_color_code" style="color:#0284c7;">--</b>
                    </div>
                    <div>
                        <span style="color:var(--dx-text-muted);">Định mức BOM:</span>
                        <b id="info_bom_kg_m" style="color:#059669;">0.0000</b> <span style="font-size:10.5px;">kg/m</span>
                    </div>
                    <div>
                        <span style="color:var(--dx-text-muted);">Dài chuẩn bobin:</span>
                        <b id="info_std_coil_m" style="color:#7c3aed;">0</b> <span style="font-size:10.5px;">m/cuộn</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================
             KHỐI 2 & 4: HÀNG NGANG GỒM [VẬT LIỆU 3 VÙNG] & [THÀNH PHẨM & THIẾT BỊ 2 CỘT]
             ================================================================= -->
        <div class="row g-2">
            <!-- KHỐI 2: THÔNG TIN VẬT LIỆU (GỘP NGUYÊN SINH | NG | VỆ SINH THEO MỤC 3) -->
            <div class="col-12 col-xl-6">
                <div class="ext-card h-100">
                    <div class="ext-card-header sec-teal">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:17px; color:#5eead4;">inventory_2</span>
                            <span>2. Thông Tin Vật Liệu (Nguyên Sinh • NG • Vệ Sinh)</span>
                        </div>
                        <span class="sec-badge">Gộp 3 Vùng Ngang</span>
                    </div>
                    <div class="ext-card-body">
                        <div class="row g-2">
                            <!-- VÙNG 1: NGUYÊN SINH -->
                            <div class="col-12 col-md-4">
                                <div class="mat-zone mat-zone-virgin">
                                    <div class="mat-zone-header">
                                        <span>① Nguyên Sinh</span>
                                        <span class="cell-ref">B15:J15</span>
                                    </div>
                                    <div class="ext-fg">
                                        <label class="ext-lbl"><span>Mã Lot NL</span><span class="cell-ref">B15</span></label>
                                        <input type="text" id="f_material_code" list="dl_recent_lots" class="ext-inp ext-inp-manual" placeholder="Lot hạt..." oninput="onMainLotInput()">
                                        <datalist id="dl_recent_lots"></datalist>
                                    </div>
                                    <div class="ext-fg">
                                        <label class="ext-lbl"><span>Loại vật liệu</span><span class="cell-ref">F15</span></label>
                                        <input type="text" id="f_material_type" list="dl_material_types" class="ext-inp ext-inp-manual" placeholder="BASF/DIC...">
                                        <datalist id="dl_material_types">
                                            <option value="BASF"></option>
                                            <option value="DIC"></option>
                                            <option value="HUNSTMAN"></option>
                                            <option value="COVESTRO"></option>
                                            <option value="VLN"></option>
                                        </datalist>
                                    </div>
                                    <div class="row g-1">
                                        <div class="col-5 ext-fg">
                                            <label class="ext-lbl"><span>Nghiền</span></label>
                                            <select id="f_regrind_count" class="ext-inp ext-inp-manual">
                                                <option value="0">0 (Zin)</option>
                                                <option value="1">1 lần</option>
                                                <option value="2">2 lần</option>
                                                <option value="3">3 lần</option>
                                            </select>
                                        </div>
                                        <div class="col-7 ext-fg">
                                            <label class="ext-lbl"><span>Mã kiện</span></label>
                                            <input type="text" id="f_regrind_package_code" class="ext-inp ext-inp-calc" placeholder="Theo B15">
                                        </div>
                                    </div>
                                    <div class="ext-fg mt-1">
                                        <label class="ext-lbl"><span style="color:#0284c7;">Tổng TL Liệu (A+B+D)</span><span class="cell-ref">J15</span></label>
                                        <input type="number" step="0.01" id="f_total_weight_kg" class="ext-inp ext-inp-calc" style="font-weight:800; font-size:13px;" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- VÙNG 2: NG & PHẾ PHẨM -->
                            <div class="col-12 col-md-5">
                                <div class="mat-zone mat-zone-ng">
                                    <div class="mat-zone-header">
                                        <span>② NG &amp; Phế Phẩm</span>
                                        <span class="cell-ref">P15:Q22</span>
                                    </div>
                                    <div class="row g-1">
                                        <div class="col-6 ext-fg">
                                            <label class="ext-lbl"><span style="color:#d97706;">Tổng TL NG B (kg)</span><span class="cell-ref">Q15</span></label>
                                            <input type="number" step="0.01" id="f_ng_qty_kg" class="ext-inp ext-inp-manual" value="0.00" min="0" oninput="recalcAllMetrics()">
                                        </div>
                                        <div class="col-6 ext-fg">
                                            <label class="ext-lbl"><span style="color:#dc2626;">Cục cứng D (kg)</span><span class="cell-ref">J25</span></label>
                                            <input type="number" step="0.01" id="f_hard_waste_qty_kg" class="ext-inp ext-inp-manual" value="0.00" min="0" oninput="recalcAllMetrics()">
                                        </div>
                                    </div>

                                    <!-- 4 ô phân loại phế phẩm A1..A4 thu gọn -->
                                    <div class="row g-1 mt-1">
                                        <div class="col-3 ext-fg">
                                            <label class="ext-lbl" title="A1: CĐ trước"><span>A1 CĐ</span></label>
                                            <input type="number" step="0.01" id="f_defect_a1" class="ext-dt-inp" value="0" min="0" placeholder="0" oninput="onDefectDetailInput()">
                                        </div>
                                        <div class="col-3 ext-fg">
                                            <label class="ext-lbl" title="A2: Điều chỉnh"><span>A2 ĐC</span></label>
                                            <input type="number" step="0.01" id="f_defect_a2" class="ext-dt-inp" value="0" min="0" placeholder="0" oninput="onDefectDetailInput()">
                                        </div>
                                        <div class="col-3 ext-fg">
                                            <label class="ext-lbl" title="A3: Bất thường"><span>A3 BT</span></label>
                                            <input type="number" step="0.01" id="f_defect_a3" class="ext-dt-inp" value="0" min="0" placeholder="0" oninput="onDefectDetailInput()">
                                        </div>
                                        <div class="col-3 ext-fg">
                                            <label class="ext-lbl" title="A4: Thử nghiệm"><span>A4 Test</span></label>
                                            <input type="number" step="0.01" id="f_defect_a4" class="ext-dt-inp" value="0" min="0" placeholder="0" oninput="onDefectDetailInput()">
                                        </div>
                                    </div>

                                    <!-- Lot NL NG & Khối lượng (Ẩn nếu không có) -->
                                    <div class="row g-1 mt-1">
                                        <div class="col-7 ext-fg">
                                            <label class="ext-lbl"><span>Lot NL NG</span><span class="cell-ref">B20</span></label>
                                            <input type="text" id="f_lot_material_ng" class="ext-inp ext-inp-manual" placeholder="Lot NL NG...">
                                        </div>
                                        <div class="col-5 ext-fg">
                                            <label class="ext-lbl"><span>TL NG (kg)</span><span class="cell-ref">J20</span></label>
                                            <input type="number" step="0.01" id="f_material_ng_qty" class="ext-inp ext-inp-manual" value="0.00" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- VÙNG 3: VỆ SINH -->
                            <div class="col-12 col-md-3">
                                <div class="mat-zone mat-zone-clean">
                                    <div class="mat-zone-header">
                                        <span>③ Vệ Sinh</span>
                                        <span class="cell-ref">J29:J31</span>
                                    </div>
                                    <div class="ext-fg">
                                        <label class="ext-lbl"><span>HDPE 6200</span><span class="cell-ref">J29</span></label>
                                        <input type="number" step="0.01" id="f_hdpe_qty" class="ext-inp ext-inp-manual" value="0" min="0" placeholder="0 kg">
                                    </div>
                                    <div class="ext-fg">
                                        <label class="ext-lbl"><span>LIOCLEAN</span><span class="cell-ref">J30</span></label>
                                        <input type="number" step="0.01" id="f_lio_clean_qty" class="ext-inp ext-inp-manual" value="0" min="0" placeholder="0 kg">
                                    </div>
                                    <div class="ext-fg">
                                        <label class="ext-lbl"><span>TI-CLEAN</span><span class="cell-ref">J31</span></label>
                                        <input type="number" step="0.01" id="f_ti_clean_qty" class="ext-inp ext-inp-manual" value="0" min="0" placeholder="0 kg">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHỐI 4: THÀNH PHẨM & THIẾT BỊ (GỘP 2 CỘT CÂN ĐỐI THEO MỤC 6) -->
            <div class="col-12 col-xl-6">
                <div class="ext-card h-100">
                    <div class="ext-card-header sec-indigo">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:17px; color:#a5b4fc;">precision_manufacturing</span>
                            <span>4. Thành Phẩm &amp; Thiết Bị (2 Cột Cân Đối)</span>
                        </div>
                        <span class="sec-badge">Thành Phẩm | Thiết Bị</span>
                    </div>
                    <div class="ext-card-body">
                        <div class="row g-2">
                            <!-- BÊN TRÁI: THÔNG TIN THÀNH PHẨM -->
                            <div class="col-12 col-md-6" style="border-right: 1px dashed var(--ext-border);">
                                <div style="font-size:11.5px; font-weight:800; color:#4338ca; text-transform:uppercase; margin-bottom:6px; display:flex; justify-content:space-between;">
                                    <span>Thông Tin Thành Phẩm</span>
                                    <button type="button" style="border:none; background:none; color:#0284c7; font-size:10.5px; font-weight:700; cursor:pointer;" onclick="autoMultiplyBobbinMeters()" title="Tính Mét = Cuộn × Dài chuẩn">
                                        <span class="material-icons" style="font-size:12px; vertical-align:middle;">calculate</span> Tính mét
                                    </button>
                                </div>
                                <div class="ext-fg">
                                    <label class="ext-lbl"><span>Lot in (Mã lot in ống)</span><span class="cell-ref">P15</span></label>
                                    <input type="text" id="f_lot_in" class="ext-inp ext-inp-calc" placeholder="Tự động theo Lot B15">
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>PL7 (Cuộn)</span><span class="cell-ref">AB10</span></label>
                                        <input type="number" step="1" id="f_bobbin_pl7_3_count" class="ext-inp ext-inp-manual" value="0" min="0" oninput="onBobbinCountChanged('pl73')">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>PL7 (Mét)</span><span class="cell-ref">AC10</span></label>
                                        <input type="number" step="0.01" id="f_bobbin_pl7_3_meters" class="ext-inp ext-inp-manual" value="0" min="0" oninput="onBobbinMetersChanged()">
                                    </div>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>PL4 (Cuộn)</span><span class="cell-ref">AB11</span></label>
                                        <input type="number" step="1" id="f_bobbin_pl4_7_count" class="ext-inp ext-inp-manual" value="0" min="0" oninput="onBobbinCountChanged('pl47')">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>PL4 (Mét)</span><span class="cell-ref">AC11</span></label>
                                        <input type="number" step="0.01" id="f_bobbin_pl4_7_meters" class="ext-inp ext-inp-manual" value="0" min="0" oninput="onBobbinMetersChanged()">
                                    </div>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span style="color:#059669;">SL Thành Phẩm (m)</span><span class="cell-ref">W10</span></label>
                                        <input type="number" step="0.01" id="f_finished_qty_m" class="ext-inp ext-inp-calc" style="font-weight:800;" value="0" min="0" oninput="recalcAllMetrics()">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span style="color:#059669;">KL TP (kg)</span><span class="cell-ref">F48</span></label>
                                        <input type="number" step="0.01" id="f_finished_qty_kg" class="ext-inp ext-inp-calc" style="font-weight:800;" value="0.00" min="0" oninput="onFinishedKgManualEdit()">
                                    </div>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Chu kỳ (Mpm)</span><span class="cell-ref">R11</span></label>
                                        <input type="number" step="0.1" id="f_cycle_time" class="ext-inp ext-inp-manual" value="25" min="0" oninput="recalcAllMetrics()">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Bobin quy đổi</span><span class="cell-ref">Col AN</span></label>
                                        <input type="number" step="0.01" id="f_total_bobbin_calc" class="ext-inp ext-inp-calc" value="0.00" readonly>
                                    </div>
                                </div>
                                <!-- Hàng điều chỉnh ẩn/thu gọn nếu 0 -->
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Hàng ĐC (m)</span><span class="cell-ref">V10</span></label>
                                        <input type="number" step="0.01" id="f_adj_meters" class="ext-inp ext-inp-manual" value="0" min="0" oninput="recalcAllMetrics()">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>KL ĐC (kg)</span><span class="cell-ref">F54</span></label>
                                        <input type="number" step="0.01" id="f_adj_weight_kg" class="ext-inp ext-inp-calc" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- BÊN PHẢI: THÔNG TIN THIẾT BỊ -->
                            <div class="col-12 col-md-6">
                                <div style="font-size:11.5px; font-weight:800; color:#4338ca; text-transform:uppercase; margin-bottom:6px;">
                                    Thông Tin Thiết Bị &amp; Khuôn
                                </div>
                                <div class="ext-fg">
                                    <label class="ext-lbl"><span>Máy đùn <span class="text-danger">*</span></span><span class="cell-ref">F10</span></label>
                                    <select id="f_device_code" class="ext-inp ext-inp-manual">
                                        <?php for ($m = 1; $m <= 24; $m++): $mc = sprintf('PL%02d', $m); ?>
                                        <option value="<?= $mc ?>"><?= $mc ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Khuôn (Mold)</span><span class="cell-ref">F33</span></label>
                                        <input type="text" id="f_mold_code" class="ext-inp ext-inp-manual" value="DA-5" placeholder="Mã khuôn">
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Mandrel</span><span class="cell-ref">F34</span></label>
                                        <input type="text" id="f_spider_code" class="ext-inp ext-inp-manual" value="SA-5" placeholder="Mandrel/Spider">
                                    </div>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Loại máy in</span><span class="cell-ref">N10</span></label>
                                        <select id="f_printer_type" class="ext-inp ext-inp-manual">
                                            <option value="A200">A200</option>
                                            <option value="AX300i">AX300i</option>
                                        </select>
                                    </div>
                                    <div class="col-6 ext-fg">
                                        <label class="ext-lbl"><span>Loại mực</span><span class="cell-ref">N11</span></label>
                                        <select id="f_ink_type" class="ext-inp ext-inp-calc">
                                            <option value="Trắng">Trắng</option>
                                            <option value="Đen">Đen</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-top:14px; padding:8px 10px; border-radius:6px; background:var(--ext-bg-soft); border:1px solid var(--ext-border); font-size:11.5px;">
                                    <div style="font-weight:700; color:#0284c7; margin-bottom:4px;">Quy chuẩn thiết bị:</div>
                                    <div style="color:var(--dx-text-muted); line-height:1.4;">
                                        • Tốc độ tiêu chuẩn: <b id="info_std_speed">25 - 35</b> m/phút<br>
                                        • Kiểm tra mực in theo màu ống tự động (Đen/Trắng).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================
             KHỐI 3: HIỆU SUẤT & THỜI GIAN SẢN XUẤT (THEO MỤC 4 & 5)
             ================================================================= -->
        <div class="ext-card mt-2">
            <div class="ext-card-header sec-slate">
                <div class="sec-title">
                    <span class="material-icons" style="font-size:17px; color:#f87171;">speed</span>
                    <span>3. Hiệu Suất &amp; Thời Gian Sản Xuất (KPI Summary &amp; Bảng Dừng Máy)</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <div id="badgeCheckMachineTime" class="ext-check-badge ext-check-warn" title="F52: TG máy thực tế trong khoảng 90%-110% TG chạy máy">
                        <span id="txtCheckMachineTime">KT TG Máy</span>
                    </div>
                    <div id="badgeCheckHumanTime" class="ext-check-badge ext-check-warn" title="F53: Tổng TG con người >= 7.2h">
                        <span id="txtCheckHumanTime">KT TG Người</span>
                    </div>
                </div>
            </div>
            <div class="ext-card-body">
                <!-- 1. KPI SUMMARY BOXES ĐƯA LÊN TRÊN CÙNG (THEO MỤC 4) -->
                <div class="kpi-grid">
                    <div class="kpi-card kpi-eff">
                        <span class="kpi-title">Hiệu Suất Máy (%)</span>
                        <span class="kpi-num" style="color:#2563eb;"><span id="kpi_efficiency_pct">0.00</span>%</span>
                    </div>
                    <div class="kpi-card kpi-run">
                        <span class="kpi-title">TG Chạy Máy (AK10)</span>
                        <span class="kpi-num"><span id="kpi_runtime_h">8.00</span> h</span>
                    </div>
                    <div class="kpi-card kpi-hum">
                        <span class="kpi-title">TG Thao Tác Người (AA6)</span>
                        <span class="kpi-num" style="color:#7c3aed;"><span id="kpi_human_h">0.00</span> h</span>
                    </div>
                    <div class="kpi-card kpi-err">
                        <span class="kpi-title">TG Dừng Máy (AL56)</span>
                        <span class="kpi-num" style="color:#dc2626;"><span id="kpi_downtime_h">0.00</span> h</span>
                    </div>
                    <div class="kpi-card kpi-stop">
                        <span class="kpi-title">TG Máy Thực Tế (V6)</span>
                        <span class="kpi-num" style="color:#d97706;"><span id="kpi_actual_mch_h">0.00</span> h</span>
                    </div>
                    <div class="kpi-card" style="display:none;">
                        <span id="kpi_fin_m">0</span>
                        <span id="kpi_fin_kg">0</span>
                        <span id="kpi_total_kg">0</span>
                    </div>
                </div>

                <!-- 2. THÔNG TIN THỜI GIAN CHẠY MÁY & THANH THÊM NHANH LỖI -->
                <div style="background:var(--ext-bg-soft); border:1px solid var(--ext-border); border-radius:8px; padding:6px 10px; margin-bottom:8px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:11.5px; font-weight:700; color:var(--dx-text-main);">Thời gian ca:</span>
                        <div style="display:flex; align-items:center; gap:4px;">
                            <span style="font-size:11px; color:var(--dx-text-muted);">Bắt đầu:</span>
                            <input type="time" id="f_start_time" class="ext-inp ext-inp-manual" style="width:90px; height:27px; font-size:11.5px;" value="06:00" onchange="onStartEndTimeChanged()">
                        </div>
                        <div style="display:flex; align-items:center; gap:4px;">
                            <span style="font-size:11px; color:var(--dx-text-muted);">Kết thúc:</span>
                            <input type="time" id="f_end_time" class="ext-inp ext-inp-manual" style="width:90px; height:27px; font-size:11.5px;" value="14:00" onchange="onStartEndTimeChanged()">
                        </div>
                        <div style="display:flex; align-items:center; gap:4px;">
                            <span style="font-size:11px; color:var(--dx-text-muted);">Tổng chạy (h):</span>
                            <input type="number" step="0.01" id="f_total_runtime" class="ext-inp ext-inp-calc" style="width:75px; height:27px; font-size:11.5px; font-weight:800;" value="8.00" oninput="recalcAllMetrics()">
                        </div>
                    </div>

                    <!-- Thanh thêm mới lỗi nằm ngay trên bảng (Theo mục 5) -->
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span style="font-size:11.5px; font-weight:700; color:#0284c7;">+ Thêm nhanh lỗi:</span>
                        <select id="quick_dt_code" style="height:27px; padding:0 6px; font-size:11.5px; border-radius:5px; border:1px solid var(--ext-border); background:var(--ext-card-bg); max-width:200px;">
                            <option value="">-- Chọn mã lỗi --</option>
                        </select>
                        <input type="number" id="quick_dt_mins" placeholder="Phút" min="1" style="width:65px; height:27px; padding:0 5px; font-size:11.5px; border-radius:5px; border:1px solid var(--ext-border); background:var(--ext-input-yellow);">
                        <input type="number" id="quick_dt_workers" placeholder="Người" min="0" value="1" style="width:55px; height:27px; padding:0 5px; font-size:11.5px; border-radius:5px; border:1px solid var(--ext-border);">
                        <button type="button" class="app-btn app-btn-primary" style="height:27px; padding:0 8px; font-size:11px; font-weight:700;" onclick="quickAddDowntime()" title="Cập nhật dòng lỗi tương ứng trong bảng">
                            <span class="material-icons" style="font-size:14px;">add</span> Thêm
                        </button>
                    </div>
                </div>

                <!-- BỘ LỌC TABS & TÌM KIẾM MÃ LỖI (THEO MỤC 5) -->
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px; margin-bottom:6px;">
                    <div class="dt-filter-tabs" id="dtGroupTabs">
                        <button type="button" class="dt-tab-btn active" data-grp="ALL" onclick="filterDowntimeRows('ALL', this)">Tất cả (41)</button>
                        <button type="button" class="dt-tab-btn" data-grp="ACTIVE" onclick="filterDowntimeRows('ACTIVE', this)">Đã nhập (<span id="cntActiveDt">0</span>)</button>
                        <button type="button" class="dt-tab-btn" data-grp="Lỗi quản lý" onclick="filterDowntimeRows('Lỗi quản lý', this)">Quản lý (6)</button>
                        <button type="button" class="dt-tab-btn" data-grp="Lỗi sản xuất" onclick="filterDowntimeRows('Lỗi sản xuất', this)">Sản xuất (6)</button>
                        <button type="button" class="dt-tab-btn" data-grp="Lỗi thiết bị/ điều chỉnh" onclick="filterDowntimeRows('Lỗi thiết bị/ điều chỉnh', this)">Thiết bị &amp; ĐC (22)</button>
                        <button type="button" class="dt-tab-btn" data-grp="Đổi màu/ vệ sinh" onclick="filterDowntimeRows('Đổi màu/ vệ sinh', this)">Đổi màu/VS (7)</button>
                    </div>

                    <div style="display:flex; align-items:center; gap:6px;">
                        <input type="text" id="dtSearchInput" class="ext-inp" style="height:26px; width:180px; font-size:11px;" placeholder="Tìm mã hoặc tên lỗi..." oninput="renderDowntimeTableVisibility()">
                        <button type="button" class="app-btn app-btn-secondary" style="height:26px; padding:0 6px; font-size:11px;" onclick="clearAllDowntimeInputs()" title="Xóa toàn bộ thời gian dừng máy đã nhập">
                            Xóa giờ
                        </button>
                    </div>
                </div>

                <!-- 3. BẢNG THỜI GIAN LỖI THU GỌN CHIỀU CAO & SCROLL RIÊNG (THEO MỤC 5) -->
                <div class="ext-dt-wrap">
                    <table class="ext-dt-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">Xóa</th>
                                <th style="width:64px;">Mã lỗi</th>
                                <th style="text-align:left;">Hạng mục dừng máy / Thao tác</th>
                                <th style="width:68px;">Số lần<br><span style="font-size:9.5px; color:#94a3b8;">(AE)</span></th>
                                <th style="width:75px;">Số phút<br><span style="font-size:9.5px; color:#fde047;">(Nhập)</span></th>
                                <th style="width:80px;">TG Lỗi (h)<br><span style="font-size:9.5px; color:#93c5fd;">(DATA_TIME)</span></th>
                                <th style="width:64px;" title="Máy đang chạy (1=Dừng máy, 0=Không dừng)">Máy chạy<br><span style="font-size:9.5px; color:#94a3b8;">(AL: 1/0)</span></th>
                                <th style="width:64px;" title="Số người thao tác">Số người<br><span style="font-size:9.5px; color:#94a3b8;">(AM)</span></th>
                                <th style="width:60px;" title="Số người hỗ trợ">Hỗ trợ<br><span style="font-size:9.5px; color:#94a3b8;">(AN)</span></th>
                            </tr>
                        </thead>
                        <tbody id="downtimeTableBody">
                            <!-- Nạp danh mục 41 hạng mục dừng máy qua JS -->
                        </tbody>
                        <tfoot>
                            <tr style="background:#0f172a; color:#ffffff; font-weight:800; font-size:11.5px;">
                                <td colspan="3" style="text-align:right; padding:5px 8px;">TỔNG CỘNG (Sheet Input Dòng 56):</td>
                                <td id="ft_total_occ" style="text-align:center; color:#fde047;">0</td>
                                <td id="ft_total_mins" style="text-align:center; color:#fde047;">0 p</td>
                                <td id="ft_total_hours" style="text-align:center; color:#38bdf8;">0.00 h</td>
                                <td id="ft_mch_downtime" style="text-align:center; color:#fb923c;" title="Tổng TG dừng máy AL56">0.00 h</td>
                                <td id="ft_human_time" colspan="2" style="text-align:center; color:#4ade80;" title="Tổng TG con người AA6">Người: 0.00 h</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <!-- =====================================================================
         KHỐI 5: DANH SÁCH BÁO CÁO ĐÃ NHẬP GẦN ĐÂY (COLLAPSIBLE / ACCORDION)
         ===================================================================== -->
    <div class="ext-card mt-3" id="recentReportsSection">
        <div class="ext-card-header" style="cursor:pointer;" onclick="toggleRecentSection()">
            <div class="sec-title">
                <span class="material-icons" style="font-size:17px; color:#a78bfa;">history_edu</span>
                <span>5. Danh Sách Báo Cáo Thực Tích Đã Nhập (Sửa / Xóa / Nhân Bản)</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="sec-badge" id="recentToggleTxt">Thu gọn ▲</span>
            </div>
        </div>
        <div class="ext-card-body" id="recentSectionBody">
            <!-- Bộ lọc danh sách báo cáo -->
            <div class="row g-1 align-items-end mb-2">
                <div class="col-6 col-md-2">
                    <label class="ext-lbl">Thời gian</label>
                    <select id="lst_mode" class="ext-inp" style="height:28px;" onchange="onListFilterModeChange()">
                        <option value="all" selected>Tất cả thời gian</option>
                        <option value="month">Theo tháng</option>
                        <option value="range">Khoảng ngày</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_month">
                    <label class="ext-lbl">Chọn tháng</label>
                    <input type="month" id="lst_month" class="ext-inp" style="height:28px;" value="<?= date('Y-m') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_start">
                    <label class="ext-lbl">Từ ngày</label>
                    <input type="date" id="lst_start_date" class="ext-inp" style="height:28px;" value="<?= date('Y-m-01') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_end">
                    <label class="ext-lbl">Đến ngày</label>
                    <input type="date" id="lst_end_date" class="ext-inp" style="height:28px;" value="<?= date('Y-m-t') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2">
                    <label class="ext-lbl">Ca làm việc</label>
                    <select id="lst_shift" class="ext-inp" style="height:28px;" onchange="loadRecentReports(1)">
                        <option value="ALL">-- Tất cả ca --</option>
                        <option value="Ca 1">Ca 1</option>
                        <option value="Ca 2">Ca 2</option>
                        <option value="Ca 3">Ca 3</option>
                        <option value="Ca HC">Ca HC</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="ext-lbl">Nguồn dữ liệu</label>
                    <select id="lst_source" class="ext-inp" style="height:28px;" onchange="loadRecentReports(1)">
                        <option value="MANUAL" selected>Thủ công (MANUAL)</option>
                        <option value="ALL">Tất cả nguồn</option>
                    </select>
                </div>
                <div class="col-8 col-md-3">
                    <label class="ext-lbl">Tìm kiếm nhanh</label>
                    <input type="text" id="lst_search" class="ext-inp" style="height:28px;" placeholder="Mã SP, MSNV, Tên NV, Máy..." oninput="debounceLoadRecentReports()">
                </div>
                <div class="col-4 col-md-1">
                    <button type="button" class="app-btn app-btn-primary w-100" style="height:28px; padding:0 6px;" onclick="loadRecentReports(1)">
                        <span class="material-icons" style="font-size:16px;">refresh</span>
                    </button>
                </div>
            </div>

            <!-- Bảng báo cáo đã nhập -->
            <div style="overflow-x:auto; border:1px solid var(--ext-border); border-radius:6px; max-height:260px;">
                <table class="ext-dt-table" style="min-width:1100px;">
                    <thead>
                        <tr>
                            <th style="width:105px;">Thao tác</th>
                            <th>Ngày SX</th>
                            <th>Ca</th>
                            <th>Máy</th>
                            <th>MSNV &amp; Tên</th>
                            <th>Mã CTSX</th>
                            <th>Mã sản phẩm</th>
                            <th>Size</th>
                            <th>TP (m)</th>
                            <th>KL TP (kg)</th>
                            <th>NG (kg)</th>
                            <th>Cứng (kg)</th>
                            <th>Tổng KL</th>
                            <th>TG Chạy</th>
                            <th>TG Dừng</th>
                            <th>H.Suất (%)</th>
                            <th>Mã SX</th>
                        </tr>
                    </thead>
                    <tbody id="recentReportsTbody">
                        <tr>
                            <td colspan="17" style="text-align:center; padding:16px; color:var(--dx-text-muted);">
                                Đang tải danh sách báo cáo...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Phân trang -->
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px; margin-top:6px; font-size:11.5px;">
                <div id="recentPaginationInfo" style="color:var(--dx-text-muted); font-weight:600;">Hiển thị 0 bản ghi</div>
                <div id="recentPaginationBtns" style="display:flex; gap:3px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL CHI TIẾT THỜI GIAN THAO TÁC CON NGƯỜI (AA6)
     ===================================================================== -->
<div id="humanTimeModal" class="ext-modal-backdrop" onclick="if(event.target===this) closeHumanTimeDetailModal()">
    <div class="ext-modal-content">
        <div style="background:linear-gradient(90deg, #312e81 0%, #1e1b4b 100%); color:#ffffff; padding:10px 16px; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:14px;">
                <span class="material-icons" style="color:#a5b4fc;">engineering</span>
                <span>Chi Tiết Thời Gian Thao Tác Con Người (AA6 / Sheet Input)</span>
            </div>
            <button type="button" style="border:none; background:none; color:#ffffff; cursor:pointer;" onclick="closeHumanTimeDetailModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div style="padding:14px 16px;">
            <div id="modalHumanSummaryBox" style="padding:10px 12px; border-radius:8px; margin-bottom:12px; border:1px solid; display:flex; align-items:center; justify-content:space-between;">
                <!-- Nội dung tổng quan nạp động -->
            </div>

            <div style="font-size:12px; font-weight:700; margin-bottom:6px; color:var(--dx-text-main);">
                Các hạng mục có thời gian thao tác con người trong ca:
            </div>
            <div style="max-height:280px; overflow-y:auto; border:1px solid var(--ext-border); border-radius:6px;">
                <table class="ext-dt-table">
                    <thead>
                        <tr>
                            <th style="width:65px;">Mã lỗi</th>
                            <th style="text-align:left;">Hạng mục công việc / dừng máy</th>
                            <th style="width:75px;">Thời gian (h)</th>
                            <th style="width:70px;">Số người</th>
                            <th style="width:85px;">Nhân công (h)</th>
                        </tr>
                    </thead>
                    <tbody id="modalHumanTbody">
                        <!-- Danh sách động -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top:14px; text-align:right;">
                <button type="button" class="app-btn app-btn-secondary" onclick="closeHumanTimeDetailModal()" style="height:32px; padding:0 14px; font-size:12px;">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * JAVASCRIPT XỬ LÝ TRANG NHẬP BÁO CÁO ĐÙN THỦ CÔNG (FORMAT 7.2026)
 */
const EXTRUSION_MANUAL_API = 'api/extrusion_manual_report.php';
const INITIAL_EDIT_ID = <?= (int)$editReportId ?>;

let masterData = {
    employees: {},
    products: [],
    size_bom: {},
    color_ink_map: {},
    downtime_catalog: [],
    recent_lots: []
};

let currentFilterGroup = 'ALL';
let isManualFinishedKg = false;
let recentSearchTimer  = null;

document.addEventListener('DOMContentLoaded', async () => {
    initShiftDefaultByCurrentTime();
    await loadMasterDataCatalog();
    setupKeyboardShortcuts();

    if (INITIAL_EDIT_ID > 0) {
        await loadReportForEdit(INITIAL_EDIT_ID);
    } else {
        recalcAllMetrics();
    }

    loadRecentReports(1);
});

/**
 * Tự động gợi ý Ca làm việc theo giờ hiện tại
 */
function initShiftDefaultByCurrentTime() {
    const h = new Date().getHours();
    const shiftEl = document.getElementById('f_shift');
    if (!shiftEl) return;
    if (h >= 6 && h < 14) {
        shiftEl.value = 'Ca 1';
    } else if (h >= 14 && h < 22) {
        shiftEl.value = 'Ca 2';
    } else {
        shiftEl.value = 'Ca 3';
    }
    onShiftChanged();
}

/**
 * Nạp danh mục chuẩn từ API (Sheet List + DB)
 */
async function loadMasterDataCatalog() {
    try {
        const res = await fetch(`${EXTRUSION_MANUAL_API}?action=get_master_data`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Lỗi tải danh mục');
        masterData = json.data;

        // 1. Datalist Nhân viên (MSNV & Họ tên)
        const dlEmp = document.getElementById('dl_employees');
        const dlEmpNames = document.getElementById('dl_employee_names');
        if (dlEmp && dlEmpNames) {
            let htmlCode = '';
            let htmlName = '';
            Object.entries(masterData.employees || {}).forEach(([code, name]) => {
                htmlCode += `<option value="${escapeHtml(code)}">${escapeHtml(name)}</option>`;
                htmlName += `<option value="${escapeHtml(name)}">${escapeHtml(code)}</option>`;
            });
            dlEmp.innerHTML = htmlCode;
            dlEmpNames.innerHTML = htmlName;
        }

        // 2. Datalist Mã sản phẩm & Datalist Mã CTSX
        const dlProd = document.getElementById('dl_products');
        const dlCtsx = document.getElementById('dl_ctsx');
        const prodList = Array.isArray(masterData.products) ? masterData.products : [];

        if (dlProd) {
            dlProd.innerHTML = prodList.map(p =>
                `<option value="${escapeHtml(p.product_code)}">${p.mfg_order_code ? 'CTSX: ' + escapeHtml(p.mfg_order_code) : ''}</option>`
            ).join('');
        }

        if (dlCtsx) {
            const seenCtsx = new Set();
            let ctsxOpts = '';
            prodList.forEach(p => {
                const ctsx = trimStr(p.mfg_order_code);
                if (ctsx && !seenCtsx.has(ctsx)) {
                    seenCtsx.add(ctsx);
                    ctsxOpts += `<option value="${escapeHtml(ctsx)}">${escapeHtml(p.product_code || '')}</option>`;
                }
            });
            dlCtsx.innerHTML = ctsxOpts;
        }

        // 3. Datalist Lot nguyên liệu gần đây
        const dlLots = document.getElementById('dl_recent_lots');
        if (dlLots && Array.isArray(masterData.recent_lots)) {
            dlLots.innerHTML = masterData.recent_lots.map(item =>
                `<option value="${escapeHtml(item.material_code)}">${escapeHtml(item.material_type || '')}</option>`
            ).join('');
        }

        // 4. Nạp danh mục vào ô Chọn nhanh lỗi (Quick Add)
        const quickSel = document.getElementById('quick_dt_code');
        if (quickSel) {
            const dtList = masterData.downtime_catalog || [];
            quickSel.innerHTML = '<option value="">-- Chọn mã lỗi --</option>' +
                dtList.filter(x => !x.is_summary).map(x =>
                    `<option value="${escapeHtml(x.code)}">${escapeHtml(x.code)} - ${escapeHtml(x.name)}</option>`
                ).join('');
        }

        // 5. Render bảng 41 hạng mục dừng máy
        renderDowntimeCatalogTable();
    } catch (err) {
        console.error('Lỗi loadMasterDataCatalog:', err);
    }
}

/**
 * Render 41 hạng mục dừng máy từ Format_Báo Cáo Đùn 7.2026.xlsm
 */
function renderDowntimeCatalogTable() {
    const tbody = document.getElementById('downtimeTableBody');
    if (!tbody) return;

    const list = masterData.downtime_catalog || [];
    tbody.innerHTML = list.map(item => {
        const code = String(item.code);
        const isSum = !!item.is_summary;
        const parent = item.parent ? String(item.parent) : '';
        let grpClass = 'dt-grp-mgmt';
        if (item.group === 'Lỗi sản xuất') grpClass = 'dt-grp-prod';
        else if (item.group === 'Lỗi thiết bị/ điều chỉnh') grpClass = 'dt-grp-equip';
        else if (item.group === 'Đổi màu/ vệ sinh') grpClass = 'dt-grp-clean';

        const readonlyAttr = isSum ? 'readonly tabindex="-1" style="background:rgba(59,130,246,0.1); color:#0284c7;"' : '';

        return `
            <tr class="dt-item-row ${isSum ? 'dt-row-summary' : ''}"
                data-code="${escapeHtml(code)}"
                data-group="${escapeHtml(item.group)}"
                data-parent="${escapeHtml(parent)}"
                data-is-summary="${isSum ? '1' : '0'}"
                data-def-running="${item.def_running}"
                data-def-workers="${item.def_workers}"
                data-def-support="${item.def_support}">
                <td style="text-align:center;">
                    ${!isSum ? `
                    <button type="button" style="border:none; background:none; color:#ef4444; cursor:pointer; padding:2px;"
                            onclick="clearDowntimeRow('${escapeHtml(code)}')" title="Xóa dòng lỗi này">
                        <span class="material-icons" style="font-size:16px;">delete_outline</span>
                    </button>` : ''}
                </td>
                <td style="text-align:center; font-weight:800; font-family:monospace; color:#0284c7;">${escapeHtml(code)}</td>
                <td style="font-weight:${isSum ? '800' : '600'};">
                    <span class="dt-group-pill ${grpClass}" style="margin-right:4px;">${escapeHtml(item.group)}</span>
                    ${escapeHtml(item.name)}
                </td>
                <td>
                    <input type="number" step="1" min="0" class="ext-dt-inp dt-inp-occ"
                           data-code="${escapeHtml(code)}" value="" placeholder="0"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'occ')">
                </td>
                <td>
                    <input type="number" step="1" min="0" class="ext-dt-inp dt-inp-mins"
                           data-code="${escapeHtml(code)}" value="" placeholder="Phút"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'mins')">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="ext-dt-inp dt-inp-hours"
                           data-code="${escapeHtml(code)}" value="" placeholder="0.00"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'hours')">
                </td>
                <td>
                    <select class="ext-dt-inp dt-inp-running" data-code="${escapeHtml(code)}"
                            onchange="recalcAllMetrics()">
                        <option value="0" ${item.def_running == 0 ? 'selected' : ''}>0</option>
                        <option value="1" ${item.def_running == 1 ? 'selected' : ''}>1</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="1" min="0" max="10" class="ext-dt-inp dt-inp-workers"
                           data-code="${escapeHtml(code)}" value="${item.def_workers}"
                           oninput="recalcAllMetrics()">
                </td>
                <td>
                    <input type="number" step="1" min="0" max="10" class="ext-dt-inp dt-inp-support"
                           data-code="${escapeHtml(code)}" value="${item.def_support}"
                           oninput="recalcAllMetrics()">
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Xử lý khi nhập Số lần / Số phút / Số giờ trên 1 dòng dừng máy
 */
function onDowntimeRowChanged(code, changedField) {
    const row = document.querySelector(`.dt-item-row[data-code="${CSS.escape(code)}"]`);
    if (!row) return;

    const occEl   = row.querySelector('.dt-inp-occ');
    const minsEl  = row.querySelector('.dt-inp-mins');
    const hoursEl = row.querySelector('.dt-inp-hours');

    if (changedField === 'mins') {
        const mins = parseFloat(minsEl.value) || 0;
        hoursEl.value = mins > 0 ? (mins / 60).toFixed(4).replace(/\.?0+$/, '') : '';
        if (mins > 0 && (!occEl.value || parseInt(occEl.value, 10) === 0)) {
            occEl.value = 1;
        } else if (mins === 0) {
            occEl.value = '';
        }
    } else if (changedField === 'hours') {
        const hrs = parseFloat(hoursEl.value) || 0;
        minsEl.value = hrs > 0 ? Math.round(hrs * 60) : '';
        if (hrs > 0 && (!occEl.value || parseInt(occEl.value, 10) === 0)) {
            occEl.value = 1;
        } else if (hrs === 0) {
            occEl.value = '';
        }
    }

    updateParentDowntimeGroup('300');
    updateParentDowntimeGroup('308');
    recalcAllMetrics();
}

/**
 * Thêm / cập nhật nhanh lỗi từ thanh công cụ phía trên bảng (Theo mục 5)
 */
function quickAddDowntime() {
    const code = document.getElementById('quick_dt_code').value;
    const mins = parseFloat(document.getElementById('quick_dt_mins').value) || 0;
    const workers = parseInt(document.getElementById('quick_dt_workers').value, 10) || 1;

    if (!code) {
        alert('Vui lòng chọn Mã lỗi!');
        return;
    }
    if (mins <= 0) {
        alert('Vui lòng nhập Số phút (> 0)!');
        return;
    }

    const row = document.querySelector(`.dt-item-row[data-code="${CSS.escape(code)}"]`);
    if (!row) return;

    row.querySelector('.dt-inp-mins').value = mins;
    row.querySelector('.dt-inp-hours').value = (mins / 60).toFixed(4).replace(/\.?0+$/, '');
    if (!row.querySelector('.dt-inp-occ').value) {
        row.querySelector('.dt-inp-occ').value = 1;
    }
    if (row.querySelector('.dt-inp-workers')) {
        row.querySelector('.dt-inp-workers').value = workers;
    }

    updateParentDowntimeGroup('300');
    updateParentDowntimeGroup('308');
    recalcAllMetrics();

    // Reset ô nhập nhanh
    document.getElementById('quick_dt_mins').value = '';

    // Cuộn tới dòng vừa thêm và highlight
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    row.classList.add('dt-row-active');
}

/**
 * Xóa 1 dòng lỗi (Icon delete_outline theo mục 5)
 */
function clearDowntimeRow(code) {
    const row = document.querySelector(`.dt-item-row[data-code="${CSS.escape(code)}"]`);
    if (!row) return;
    row.querySelector('.dt-inp-occ').value = '';
    row.querySelector('.dt-inp-mins').value = '';
    row.querySelector('.dt-inp-hours').value = '';
    row.classList.remove('dt-row-active');
    updateParentDowntimeGroup('300');
    updateParentDowntimeGroup('308');
    recalcAllMetrics();
}

/**
 * Tự động cộng nhóm 300 và 308 từ các mã con
 */
function updateParentDowntimeGroup(parentCode) {
    const parentRow = document.querySelector(`.dt-item-row[data-code="${parentCode}"]`);
    if (!parentRow) return;

    let sumOcc  = 0;
    let sumMins = 0;
    let sumHrs  = 0;

    document.querySelectorAll(`.dt-item-row[data-parent="${parentCode}"]`).forEach(child => {
        const occ  = parseInt(child.querySelector('.dt-inp-occ')?.value, 10) || 0;
        const hrs  = parseFloat(child.querySelector('.dt-inp-hours')?.value) || 0;
        const mins = parseFloat(child.querySelector('.dt-inp-mins')?.value) || (hrs * 60);
        sumOcc  += occ;
        sumMins += mins;
        sumHrs  += hrs;
    });

    parentRow.querySelector('.dt-inp-occ').value   = sumOcc > 0 ? sumOcc : '';
    parentRow.querySelector('.dt-inp-mins').value  = sumMins > 0 ? Math.round(sumMins) : '';
    parentRow.querySelector('.dt-inp-hours').value = sumHrs > 0 ? sumHrs.toFixed(4).replace(/\.?0+$/, '') : '';
}

/**
 * Lọc hiển thị dòng trong bảng dừng máy theo Tab nhóm hoặc từ khóa
 */
function filterDowntimeRows(groupName, btnEl) {
    currentFilterGroup = groupName;
    document.querySelectorAll('#dtGroupTabs .dt-tab-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    renderDowntimeTableVisibility();
}

function renderDowntimeTableVisibility() {
    const kw = (document.getElementById('dtSearchInput')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        const grp  = row.getAttribute('data-group') || '';
        const code = (row.getAttribute('data-code') || '').toLowerCase();
        const name = (row.children[2]?.textContent || '').toLowerCase();
        const occ  = parseInt(row.querySelector('.dt-inp-occ')?.value, 10) || 0;
        const hrs  = parseFloat(row.querySelector('.dt-inp-hours')?.value) || 0;
        const isActive = (occ > 0 || hrs > 0);

        let matchGrp = true;
        if (currentFilterGroup === 'ACTIVE') {
            matchGrp = isActive;
        } else if (currentFilterGroup !== 'ALL') {
            matchGrp = (grp === currentFilterGroup);
        }

        const matchKw = !kw || code.includes(kw) || name.includes(kw) || grp.toLowerCase().includes(kw);
        row.style.display = (matchGrp && matchKw) ? '' : 'none';
    });
}

function clearAllDowntimeInputs() {
    if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ thời gian dừng máy đã nhập?')) return;
    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        row.querySelector('.dt-inp-occ').value = '';
        row.querySelector('.dt-inp-mins').value = '';
        row.querySelector('.dt-inp-hours').value = '';
        row.classList.remove('dt-row-active');
    });
    recalcAllMetrics();
}

/**
 * Tự động điền Tên nhân viên khi nhập MSNV và ngược lại
 */
function onEmployeeCodeInput() {
    let raw = document.getElementById('f_employee_code').value.trim();
    const empMap = masterData.employees || {};
    let matchedName = empMap[raw] || empMap['0' + raw] || '';
    if (matchedName) {
        document.getElementById('f_employee_name').value = matchedName;
    }
}

function onEmployeeNameInput() {
    const nameVal = document.getElementById('f_employee_name').value.trim().toLowerCase();
    if (!nameVal) return;
    for (const [code, name] of Object.entries(masterData.employees || {})) {
        if (name.toLowerCase() === nameVal) {
            document.getElementById('f_employee_code').value = code;
            break;
        }
    }
}

/**
 * Tự động điền Loại vật liệu, Lot Phế phẩm, Mã kiện nghiền khi nhập Lot chính (B15)
 */
function onMainLotInput() {
    const lotVal = document.getElementById('f_material_code').value.trim();
    const pkgEl  = document.getElementById('f_regrind_package_code');
    const lotInEl = document.getElementById('f_lot_in');
    if (pkgEl && (!pkgEl.value || pkgEl.dataset.auto === '1')) {
        pkgEl.value = lotVal;
        pkgEl.dataset.auto = '1';
    }
    if (lotInEl && (!lotInEl.value || lotInEl.dataset.auto === '1')) {
        lotInEl.value = lotVal;
        lotInEl.dataset.auto = '1';
    }

    if (Array.isArray(masterData.recent_lots)) {
        const found = masterData.recent_lots.find(x => x.material_code === lotVal);
        if (found && found.material_type) {
            document.getElementById('f_material_type').value = found.material_type;
        }
    }
}

/**
 * Xử lý khi nhập Mã CTSX (Theo mục 2: Tự động điền thông tin & hiển thị nổi bật trạng thái chỉ thị)
 */
function onMfgOrderCodeInput() {
    const ctsxVal = document.getElementById('f_mfg_order_code').value.trim().toUpperCase();
    const badge = document.getElementById('badge_directive_status');
    const txt   = document.getElementById('txt_directive_status');
    const prodList = Array.isArray(masterData.products) ? masterData.products : [];

    if (!ctsxVal) {
        badge.className = 'status-badge-ctsx empty';
        txt.innerHTML = 'Chờ mã CTSX';
        return;
    }

    if (ctsxVal === 'DC' || ctsxVal.includes('ĐC') || ctsxVal.includes('DIEU CHINH')) {
        badge.className = 'status-badge-ctsx adjust';
        txt.innerHTML = '⚡ Hàng Điều Chỉnh (DC)';
        document.getElementById('f_production_type').value = 'Hàng điều chỉnh';
        return;
    }

    // Tra cứu xem CTSX này ứng với sản phẩm nào
    const matched = prodList.find(p => trimStr(p.mfg_order_code).toUpperCase() === ctsxVal);
    if (matched) {
        badge.className = 'status-badge-ctsx valid';
        txt.innerHTML = `✓ Chỉ thị: <b>${escapeHtml(ctsxVal)}</b> (Hợp lệ)`;

        // Tự động điền Mã sản phẩm và các thông số máy, khuôn, mực
        if (!document.getElementById('f_product_code').value || document.getElementById('f_product_code').dataset.auto === '1') {
            document.getElementById('f_product_code').value = matched.product_code;
            document.getElementById('f_product_code').dataset.auto = '1';
            onProductionTypeOrProductChanged(false);
        }
        if (matched.cycle_time && parseFloat(matched.cycle_time) > 0) {
            document.getElementById('f_cycle_time').value = matched.cycle_time;
        }
        if (matched.mold_code) document.getElementById('f_mold_code').value = matched.mold_code;
        if (matched.spider_code) document.getElementById('f_spider_code').value = matched.spider_code;
    } else {
        badge.className = 'status-badge-ctsx valid';
        txt.innerHTML = `Chỉ thị: <b>${escapeHtml(ctsxVal)}</b>`;
    }
}

/**
 * Tự động trích xuất Size ống, Màu ống, Màu mực in, Mã CTSX, BOM kg/m khi chọn Mã sản phẩm hoặc Loại SX
 */
function extractPipeSizeJs(productCode) {
    const raw = (productCode || '').trim().toUpperCase();
    if (!raw) return '';
    const prefix = raw.split('-')[0];
    const m = prefix.match(/^(TU\d{4}|TIUB\d{2})/);
    if (m) return m[1];
    return prefix.substring(0, 6);
}

function extractColorCodeJs(productCode, pipeSize) {
    const raw = (productCode || '').trim().toUpperCase();
    if (!raw) return '';
    const prefix = raw.split('-')[0];
    if (pipeSize && prefix.startsWith(pipeSize)) {
        return prefix.substring(pipeSize.length);
    }
    return '';
}

function onProductionTypeOrProductChanged(syncCtsx = true) {
    const prodType = document.getElementById('f_production_type').value;
    const prodCode = document.getElementById('f_product_code').value.trim().toUpperCase();
    const prodList = Array.isArray(masterData.products) ? masterData.products : [];

    // 1. Tính Size ống (H10)
    const pipeSize = extractPipeSizeJs(prodCode);
    document.getElementById('f_pipe_size').value = pipeSize;

    // 2. Tra Mã CTSX (C10)
    const ctsxEl = document.getElementById('f_mfg_order_code');
    if (prodType === 'Hàng điều chỉnh') {
        ctsxEl.value = 'DC';
        onMfgOrderCodeInput();
    } else if (syncCtsx && prodCode) {
        const found = prodList.find(p => p.product_code.toUpperCase() === prodCode);
        if (found && found.mfg_order_code) {
            ctsxEl.value = found.mfg_order_code;
            onMfgOrderCodeInput();
        }
    }

    // 3. Tra Màu ống (K10) -> Màu mực in (N11)
    const colorCode = extractColorCodeJs(prodCode, pipeSize);
    document.getElementById('info_color_code').textContent = colorCode || '--';
    if (colorCode && masterData.color_ink_map && masterData.color_ink_map[colorCode]) {
        document.getElementById('f_ink_type').value = masterData.color_ink_map[colorCode];
    }

    // 4. Tra BOM (kg/m) & Chiều dài chuẩn (m/cuộn)
    const sizeInfo = (masterData.size_bom && masterData.size_bom[pipeSize]) ? masterData.size_bom[pipeSize] : null;
    const bomKgM   = sizeInfo ? parseFloat(sizeInfo.bom_kg_per_m) : 0;
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_length_m || sizeInfo.std_coil_m) : 0;

    document.getElementById('info_bom_kg_m').textContent   = bomKgM > 0 ? bomKgM.toFixed(4) : '0.0000';
    document.getElementById('info_std_coil_m').textContent = stdCoilM > 0 ? stdCoilM : '0';

    isManualFinishedKg = false;
    recalcAllMetrics();
}

/**
 * Thay đổi Ca làm việc -> gợi ý Giờ bắt đầu & Giờ kết thúc chuẩn
 */
function onShiftChanged() {
    const s = document.getElementById('f_shift').value;
    const st = document.getElementById('f_start_time');
    const et = document.getElementById('f_end_time');
    if (s === 'Ca 1') { st.value = '06:00'; et.value = '14:00'; }
    else if (s === 'Ca 2') { st.value = '14:00'; et.value = '22:00'; }
    else if (s === 'Ca 3') { st.value = '22:00'; et.value = '06:00'; }
    else if (s === 'Ca HC') { st.value = '08:00'; et.value = '17:00'; }
    onStartEndTimeChanged();
}

function onStartEndTimeChanged() {
    const st = document.getElementById('f_start_time').value;
    const et = document.getElementById('f_end_time').value;
    if (!st || !et) return;

    const [h1, m1] = st.split(':').map(Number);
    const [h2, m2] = et.split(':').map(Number);
    let mins1 = h1 * 60 + m1;
    let mins2 = h2 * 60 + m2;
    if (mins2 <= mins1) {
        mins2 += 24 * 60; // Qua đêm (Ca 3)
    }
    const diffHours = (mins2 - mins1) / 60;
    document.getElementById('f_total_runtime').value = diffHours.toFixed(2);
    recalcAllMetrics();
}

/**
 * Xử lý khi nhập Số cuộn Bobin hoặc Số mét Bobin
 */
function onBobbinCountChanged(which) {
    const pipeSize = document.getElementById('f_pipe_size').value;
    const sizeInfo = (masterData.size_bom && masterData.size_bom[pipeSize]) ? masterData.size_bom[pipeSize] : null;
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_length_m || sizeInfo.std_coil_m) : 0;

    if (stdCoilM > 0) {
        if (which === 'pl73') {
            const cnt = parseFloat(document.getElementById('f_bobbin_pl7_3_count').value) || 0;
            document.getElementById('f_bobbin_pl7_3_meters').value = (cnt * stdCoilM).toFixed(0);
        } else if (which === 'pl47') {
            const cnt = parseFloat(document.getElementById('f_bobbin_pl4_7_count').value) || 0;
            document.getElementById('f_bobbin_pl4_7_meters').value = (cnt * stdCoilM).toFixed(0);
        }
    }
    onBobbinMetersChanged();
}

function autoMultiplyBobbinMeters() {
    onBobbinCountChanged('pl73');
    onBobbinCountChanged('pl47');
}

function onBobbinMetersChanged() {
    const m73 = parseFloat(document.getElementById('f_bobbin_pl7_3_meters').value) || 0;
    const m47 = parseFloat(document.getElementById('f_bobbin_pl4_7_meters').value) || 0;
    document.getElementById('f_finished_qty_m').value = (m73 + m47).toFixed(2).replace(/\.00$/, '');
    isManualFinishedKg = false;
    recalcAllMetrics();
}

function onFinishedKgManualEdit() {
    isManualFinishedKg = true;
    recalcAllMetrics();
}

/**
 * Cộng tự động 4 ô chi tiết phế phẩm A1..A4 vào Tổng TL Phế phẩm B (Q15)
 */
function onDefectDetailInput() {
    const a1 = parseFloat(document.getElementById('f_defect_a1').value) || 0;
    const a2 = parseFloat(document.getElementById('f_defect_a2').value) || 0;
    const a3 = parseFloat(document.getElementById('f_defect_a3').value) || 0;
    const a4 = parseFloat(document.getElementById('f_defect_a4').value) || 0;
    const sumNg = a1 + a2 + a3 + a4;
    document.getElementById('f_ng_qty_kg').value = sumNg.toFixed(2);
    recalcAllMetrics();
}

/**
 * TÍNH TOÁN TOÀN BỘ CHỈ SỐ THỜI GIAN THỰC & VALIDATE GIỐNG 100% SHEET INPUT
 */
function recalcAllMetrics() {
    const pipeSize = document.getElementById('f_pipe_size').value;
    const sizeInfo = (masterData.size_bom && masterData.size_bom[pipeSize]) ? masterData.size_bom[pipeSize] : null;
    const bomKgM   = sizeInfo ? parseFloat(sizeInfo.bom_kg_per_m) : 0;
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_length_m || sizeInfo.std_coil_m) : 0;

    // 1. Thành phẩm A (m) & Khối lượng TP A (kg) (F48 = W10 * BOM)
    const finM = parseFloat(document.getElementById('f_finished_qty_m').value) || 0;
    let finKg  = parseFloat(document.getElementById('f_finished_qty_kg').value) || 0;
    if (!isManualFinishedKg && bomKgM > 0) {
        finKg = Math.round(finM * bomKgM * 100) / 100;
        document.getElementById('f_finished_qty_kg').value = finKg.toFixed(2);
    }

    // 2. Số bobin chuẩn (Col AN = finM / stdCoilM)
    const c73 = parseInt(document.getElementById('f_bobbin_pl7_3_count').value, 10) || 0;
    const c47 = parseInt(document.getElementById('f_bobbin_pl4_7_count').value, 10) || 0;
    const bobbinCalc = stdCoilM > 0 ? (finM / stdCoilM) : (c73 + c47);
    document.getElementById('f_total_bobbin_calc').value = bobbinCalc.toFixed(2);

    // 3. Hàng điều chỉnh (V10 -> F54)
    const adjM  = parseFloat(document.getElementById('f_adj_meters').value) || 0;
    const adjKg = bomKgM > 0 ? (adjM * bomKgM) : 0;
    document.getElementById('f_adj_weight_kg').value = adjKg.toFixed(2);

    // 4. Tổng KL nguyên liệu (J15 = F48 + Q15 + J25)
    const ngKg   = parseFloat(document.getElementById('f_ng_qty_kg').value) || 0;
    const hardKg = parseFloat(document.getElementById('f_hard_waste_qty_kg').value) || 0;
    const totKg  = finKg + ngKg + hardKg;
    document.getElementById('f_total_weight_kg').value = totKg.toFixed(2);

    // 5. Tính toán bảng dừng máy (AL56 & AM56 / AA6)
    let totalOcc        = 0;
    let totalAllHours   = 0;
    let machineDowntime = 0; // AL56: Tổng thời gian dừng máy (khi AL = 1)
    let humanTime       = 0; // AM56 / AA6: Tổng thời gian con người (khi AM >= 1)
    let activeCount     = 0;

    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        const isSum   = row.getAttribute('data-is-summary') === '1';
        const occ     = parseInt(row.querySelector('.dt-inp-occ')?.value, 10) || 0;
        const hrs     = parseFloat(row.querySelector('.dt-inp-hours')?.value) || 0;
        const running = parseInt(row.querySelector('.dt-inp-running')?.value, 10) || 0;
        const workers = parseInt(row.querySelector('.dt-inp-workers')?.value, 10) || 0;

        if (occ > 0 || hrs > 0) {
            row.classList.add('dt-row-active');
            if (!isSum) activeCount++;
        } else {
            row.classList.remove('dt-row-active');
        }

        // Bỏ qua 2 dòng tổng nhóm 300 & 308 khi cộng tổng toàn bảng
        if (!isSum) {
            totalOcc      += occ;
            totalAllHours += hrs;
            if (running === 1) {
                machineDowntime += hrs;
            }
            if (workers >= 1) {
                humanTime += (hrs * workers);
            }
        }
    });

    document.getElementById('cntActiveDt').textContent     = activeCount;
    document.getElementById('ft_total_occ').textContent    = totalOcc;
    document.getElementById('ft_total_mins').textContent   = Math.round(totalAllHours * 60) + ' p';
    document.getElementById('ft_total_hours').textContent  = totalAllHours.toFixed(2) + ' h';
    document.getElementById('ft_mch_downtime').textContent = machineDowntime.toFixed(2) + ' h';
    document.getElementById('ft_human_time').textContent   = 'Người: ' + humanTime.toFixed(2) + ' h';

    // 6. Thời gian máy thực tế (V6) & Hiệu suất máy (W6)
    const speedMpm = parseFloat(document.getElementById('f_cycle_time').value) || 0;
    const runtimeH = parseFloat(document.getElementById('f_total_runtime').value) || 0;

    const netRunHours = speedMpm > 0 ? ((finM / speedMpm) / 60) : 0;
    const actualMachineH = netRunHours + machineDowntime;
    const efficiencyPct  = (speedMpm > 0 && runtimeH > 0)
        ? (((finM / speedMpm) / (runtimeH * 60)) * 100)
        : 0;

    // 7. Cập nhật thanh KPI Boxes
    document.getElementById('kpi_fin_m').textContent          = finM.toLocaleString('vi-VN');
    document.getElementById('kpi_fin_kg').textContent         = finKg.toFixed(2);
    document.getElementById('kpi_total_kg').textContent       = totKg.toFixed(2);
    document.getElementById('kpi_runtime_h').textContent      = runtimeH.toFixed(2);
    document.getElementById('kpi_downtime_h').textContent     = machineDowntime.toFixed(2);
    document.getElementById('kpi_actual_mch_h').textContent   = actualMachineH.toFixed(2);
    document.getElementById('kpi_efficiency_pct').textContent = efficiencyPct.toFixed(2);
    document.getElementById('kpi_human_h').textContent        = humanTime.toFixed(2);

    // 8. Kiểm tra điều kiện F52 (TG máy thực tế trong ngưỡng 90% - 110% TG chạy máy AK10)
    const badgeMch = document.getElementById('badgeCheckMachineTime');
    const txtMch   = document.getElementById('txtCheckMachineTime');
    const minMch   = runtimeH * 0.9;
    const maxMch   = runtimeH * 1.1;

    if (runtimeH <= 0 || (finM <= 0 && machineDowntime <= 0)) {
        badgeMch.className = 'ext-check-badge ext-check-warn';
        txtMch.textContent = `KT TG Máy: ${actualMachineH.toFixed(2)}h / [${minMch.toFixed(1)}h-${maxMch.toFixed(1)}h]`;
    } else if (actualMachineH >= minMch && actualMachineH <= maxMch) {
        badgeMch.className = 'ext-check-badge ext-check-ok';
        txtMch.textContent = `KT TG Máy ĐẠT: ${actualMachineH.toFixed(2)}h`;
    } else {
        badgeMch.className = 'ext-check-badge ext-check-err';
        txtMch.textContent = `Cảnh báo TG Máy: ${actualMachineH.toFixed(2)}h`;
    }

    // 9. Kiểm tra điều kiện F53 (TG con người AA6 >= 7.2h)
    const badgeHum = document.getElementById('badgeCheckHumanTime');
    const txtHum   = document.getElementById('txtCheckHumanTime');
    if (humanTime >= 7.2) {
        badgeHum.className = 'ext-check-badge ext-check-ok';
        txtHum.textContent = `KT TG Người ĐẠT: ${humanTime.toFixed(2)}h`;
    } else {
        badgeHum.className = 'ext-check-badge ext-check-warn';
        txtHum.textContent = `TG Người: ${humanTime.toFixed(2)}h / 7.20h`;
    }
}

/**
 * Mở Modal chi tiết Thời gian con người (Theo mục 8)
 */
function openHumanTimeDetailModal() {
    const modal = document.getElementById('humanTimeModal');
    const summaryBox = document.getElementById('modalHumanSummaryBox');
    const tbody = document.getElementById('modalHumanTbody');
    if (!modal || !summaryBox || !tbody) return;

    const humanTime = parseFloat(document.getElementById('kpi_human_h').textContent) || 0;
    const isPass = humanTime >= 7.2;

    summaryBox.style.background = isPass ? 'rgba(16, 185, 129, 0.12)' : 'rgba(245, 158, 11, 0.15)';
    summaryBox.style.borderColor = isPass ? '#10b981' : '#f59e0b';
    summaryBox.innerHTML = `
        <div>
            <div style="font-size:12px; font-weight:700; color:${isPass ? '#059669' : '#d97706'};">
                ${isPass ? '✓ ĐẠT CHUẨN THỜI GIAN THAO TÁC CON NGƯỜI (AA6 >= 7.2h)' : '⚠ CHƯA ĐẠT CHUẨN THỜI GIAN THAO TÁC (AA6 < 7.2h)'}
            </div>
            <div style="font-size:11.5px; color:var(--dx-text-muted); margin-top:2px;">
                Định mức tối thiểu mỗi ca: <b>7.20 giờ</b> • Thực tế ca hiện tại: <b>${humanTime.toFixed(2)} giờ</b>
            </div>
        </div>
        <div style="font-size:22px; font-weight:800; color:${isPass ? '#059669' : '#d97706'};">
            ${humanTime.toFixed(2)}h
        </div>
    `;

    // Quét các dòng có số người tham gia thao tác
    let rowsHtml = '';
    let count = 0;
    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        const isSum = row.getAttribute('data-is-summary') === '1';
        if (isSum) return;

        const code = row.getAttribute('data-code');
        const name = row.children[2]?.textContent || '';
        const hrs = parseFloat(row.querySelector('.dt-inp-hours')?.value) || 0;
        const workers = parseInt(row.querySelector('.dt-inp-workers')?.value, 10) || 0;

        if (hrs > 0 && workers > 0) {
            count++;
            const manHours = (hrs * workers).toFixed(2);
            rowsHtml += `
                <tr>
                    <td style="text-align:center; font-weight:700; color:#0284c7;">${escapeHtml(code)}</td>
                    <td style="font-weight:600;">${escapeHtml(name)}</td>
                    <td style="text-align:center;">${hrs.toFixed(2)} h</td>
                    <td style="text-align:center; font-weight:700;">${workers}</td>
                    <td style="text-align:center; font-weight:800; color:#7c3aed;">${manHours} h</td>
                </tr>
            `;
        }
    });

    if (count === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center; padding:18px; color:var(--dx-text-muted);">
                    Chưa có hạng mục dừng máy / thao tác nào phát sinh giờ con người.
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = rowsHtml;
    }

    modal.style.display = 'flex';
}

function closeHumanTimeDetailModal() {
    const modal = document.getElementById('humanTimeModal');
    if (modal) modal.style.display = 'none';
}

/**
 * In phiếu báo cáo sản xuất (Theo mục 8)
 */
function printExtrusionReport() {
    window.print();
}

/**
 * Thu gọn / Mở rộng bảng lịch sử bên dưới (Collapsible)
 */
function toggleRecentSection() {
    const body = document.getElementById('recentSectionBody');
    const txt  = document.getElementById('recentToggleTxt');
    if (!body) return;
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? '' : 'none';
    if (txt) txt.textContent = isHidden ? 'Thu gọn ▲' : 'Mở rộng ▼';
}

/**
 * Thu thập dữ liệu trên form thành Payload JSON gửi lên API save_report
 */
function collectManualReportPayload() {
    const downtimeItems = [];
    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        const code    = row.getAttribute('data-code');
        const occ     = parseInt(row.querySelector('.dt-inp-occ')?.value, 10) || 0;
        const hrs     = parseFloat(row.querySelector('.dt-inp-hours')?.value) || 0;
        const running = parseInt(row.querySelector('.dt-inp-running')?.value, 10) || 0;
        const workers = parseInt(row.querySelector('.dt-inp-workers')?.value, 10) || 0;
        const support = parseInt(row.querySelector('.dt-inp-support')?.value, 10) || 0;

        if (occ > 0 || hrs > 0) {
            const totMins = Math.round(hrs * 60);
            const hm = String(Math.floor(totMins / 60)).padStart(2, '0') + ':' + String(totMins % 60).padStart(2, '0');
            downtimeItems.push({
                code: code,
                occurrences: occ,
                duration_hours: hrs,
                duration_hm: hm,
                is_machine_running: running,
                worker_count: workers,
                support_worker_count: support
            });
        }
    });

    const defectItems = [
        { code: 'A1', name: 'Phế phẩm công đoạn trước', weight_kg: parseFloat(document.getElementById('f_defect_a1').value) || 0 },
        { code: 'A2', name: 'Phế phẩm điều chỉnh',      weight_kg: parseFloat(document.getElementById('f_defect_a2').value) || 0 },
        { code: 'A3', name: 'Phế phẩm bất thường',      weight_kg: parseFloat(document.getElementById('f_defect_a3').value) || 0 },
        { code: 'A4', name: 'Phế phẩm thử nghiệm',      weight_kg: parseFloat(document.getElementById('f_defect_a4').value) || 0 }
    ];

    return {
        id: parseInt(document.getElementById('f_id').value, 10) || 0,
        production_order_code: document.getElementById('f_production_order_code').value.trim(),
        production_date: document.getElementById('f_production_date').value,
        shift: document.getElementById('f_shift').value,
        production_type: document.getElementById('f_production_type').value,
        employee_code: document.getElementById('f_employee_code').value.trim(),
        employee_name: document.getElementById('f_employee_name').value.trim(),
        is_test: document.getElementById('f_is_test').checked ? 1 : 0,
        product_code: document.getElementById('f_product_code').value.trim().toUpperCase(),
        mfg_order_code: document.getElementById('f_mfg_order_code').value.trim(),
        pipe_size: document.getElementById('f_pipe_size').value.trim(),
        directive_qty: parseFloat(document.getElementById('f_directive_qty').value) || 0,
        device_code: document.getElementById('f_device_code').value,
        waiting_machine_count: parseInt(document.getElementById('f_waiting_machine_count').value, 10) || 0,
        printer_type: document.getElementById('f_printer_type').value,
        ink_type: document.getElementById('f_ink_type').value,
        cycle_time: parseFloat(document.getElementById('f_cycle_time').value) || 0,
        start_time: document.getElementById('f_start_time').value,
        end_time: document.getElementById('f_end_time').value,
        total_runtime: parseFloat(document.getElementById('f_total_runtime').value) || 0,
        bobbin_pl7_3_count: parseInt(document.getElementById('f_bobbin_pl7_3_count').value, 10) || 0,
        bobbin_pl7_3_meters: parseFloat(document.getElementById('f_bobbin_pl7_3_meters').value) || 0,
        bobbin_pl4_7_count: parseInt(document.getElementById('f_bobbin_pl4_7_count').value, 10) || 0,
        bobbin_pl4_7_meters: parseFloat(document.getElementById('f_bobbin_pl4_7_meters').value) || 0,
        finished_qty_m: parseFloat(document.getElementById('f_finished_qty_m').value) || 0,
        finished_qty_kg: parseFloat(document.getElementById('f_finished_qty_kg').value) || 0,
        adj_meters: parseFloat(document.getElementById('f_adj_meters').value) || 0,
        material_code: document.getElementById('f_material_code').value.trim(),
        material_type: document.getElementById('f_material_type').value.trim(),
        regrind_count: parseInt(document.getElementById('f_regrind_count').value, 10) || 0,
        regrind_package_code: document.getElementById('f_regrind_package_code').value.trim(),
        lot_in: document.getElementById('f_lot_in').value.trim(),
        ng_qty_kg: parseFloat(document.getElementById('f_ng_qty_kg').value) || 0,
        lot_material_ng: document.getElementById('f_lot_material_ng').value.trim(),
        material_ng_qty: parseFloat(document.getElementById('f_material_ng_qty').value) || 0,
        hard_waste_qty_kg: parseFloat(document.getElementById('f_hard_waste_qty_kg').value) || 0,
        hdpe_qty: parseFloat(document.getElementById('f_hdpe_qty').value) || 0,
        lio_clean_qty: parseFloat(document.getElementById('f_lio_clean_qty').value) || 0,
        ti_clean_qty: parseFloat(document.getElementById('f_ti_clean_qty').value) || 0,
        mold_code: document.getElementById('f_mold_code').value.trim() || 'DA-5',
        spider_code: document.getElementById('f_spider_code').value.trim() || 'SA-5',
        defect_items: defectItems,
        downtime_items: downtimeItems
    };
}

/**
 * Lưu báo cáo (Thêm mới hoặc Cập nhật bản ghi đang sửa)
 */
async function submitManualReport() {
    const payload = collectManualReportPayload();

    if (!payload.production_date) {
        alert('Vui lòng chọn Ngày sản xuất (K6)!');
        document.getElementById('f_production_date').focus();
        return;
    }
    if (!payload.employee_code) {
        alert('Vui lòng nhập Mã số nhân viên MSNV (P6)!');
        document.getElementById('f_employee_code').focus();
        return;
    }
    if (!payload.product_code) {
        alert('Vui lòng chọn hoặc nhập Mã sản phẩm (G10)!');
        document.getElementById('f_product_code').focus();
        return;
    }

    const btnTop = document.getElementById('btnTopSaveReport');
    if (btnTop) btnTop.disabled = true;

    try {
        const res = await fetch(`${EXTRUSION_MANUAL_API}?action=save_report`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) {
            throw new Error(json.message || 'Lỗi khi lưu báo cáo');
        }

        document.getElementById('kpi_prod_order').textContent = json.production_order_code || 'P_NT_...';

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: payload.id > 0 ? 'Đã cập nhật báo cáo!' : 'Đã lưu báo cáo thành công!',
                text: json.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert(json.message);
        }

        resetManualForm(true);
        loadRecentReports(1);
    } catch (err) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Không thể lưu báo cáo', text: err.message });
        } else {
            alert('Lỗi: ' + err.message);
        }
    } finally {
        if (btnTop) btnTop.disabled = false;
    }
}

/**
 * Nạp 1 báo cáo từ danh sách lên Form để chỉnh sửa (Edit) hoặc nhân bản (Clone)
 */
async function loadReportForEdit(reportId, isClone = false) {
    try {
        const res = await fetch(`${EXTRUSION_MANUAL_API}?action=get_report_detail&id=${reportId}`);
        const json = await res.json();
        if (!json.success || !json.data) {
            throw new Error(json.message || 'Không tìm thấy dữ liệu báo cáo');
        }
        const d = json.data;

        document.getElementById('f_id').value = isClone ? 0 : d.id;
        document.getElementById('f_production_order_code').value = isClone ? '' : (d.production_order_code || '');
        document.getElementById('kpi_prod_order').textContent = isClone ? 'P_NT_... (Bản sao mới)' : (d.production_order_code || `#${d.id}`);

        document.getElementById('f_production_date').value = d.production_date || '';
        document.getElementById('f_shift').value = d.shift || 'Ca 1';
        document.getElementById('f_production_type').value = d.production_type || 'Hàng sản xuất';
        document.getElementById('f_employee_code').value = d.employee_code || '';
        document.getElementById('f_employee_name').value = d.employee_name || '';
        document.getElementById('f_is_test').checked = parseInt(d.is_test, 10) === 1;
        document.getElementById('f_product_code').value = d.product_code || '';

        onProductionTypeOrProductChanged(false);

        if (d.mfg_order_code) {
            document.getElementById('f_mfg_order_code').value = d.mfg_order_code;
            onMfgOrderCodeInput();
        }
        if (d.device_code) {
            const devClean = String(d.device_code).replace(/^3\./, '');
            document.getElementById('f_device_code').value = devClean;
        }
        document.getElementById('f_waiting_machine_count').value = d.waiting_machine_count ?? 0;
        document.getElementById('f_printer_type').value = d.printer_type || 'A200';
        document.getElementById('f_ink_type').value = d.ink_type || 'Trắng';
        document.getElementById('f_cycle_time').value = d.cycle_time ?? 25;
        if (d.start_time) document.getElementById('f_start_time').value = d.start_time;
        if (d.end_time) document.getElementById('f_end_time').value = d.end_time;
        document.getElementById('f_total_runtime').value = parseFloat(d.total_runtime || 8).toFixed(2);

        document.getElementById('f_bobbin_pl7_3_count').value  = d.bobbin_pl7_3_count ?? 0;
        document.getElementById('f_bobbin_pl7_3_meters').value = parseFloat(d.bobbin_pl7_3_meters || 0);
        document.getElementById('f_bobbin_pl4_7_count').value  = d.bobbin_pl4_7_count ?? 0;
        document.getElementById('f_bobbin_pl4_7_meters').value = parseFloat(d.bobbin_pl4_7_meters || 0);
        document.getElementById('f_finished_qty_m').value      = parseFloat(d.finished_qty_m || 0);
        document.getElementById('f_finished_qty_kg').value     = parseFloat(d.finished_qty_kg || 0).toFixed(2);
        isManualFinishedKg = true;

        document.getElementById('f_adj_meters').value = parseFloat(d.adj_meters || 0);
        document.getElementById('f_material_code').value = d.material_code || '';
        document.getElementById('f_material_type').value = d.material_type || '';
        document.getElementById('f_regrind_count').value = d.regrind_count ?? 0;
        document.getElementById('f_regrind_package_code').value = d.regrind_package_code || '';
        document.getElementById('f_lot_in').value = d.lot_in || '';
        document.getElementById('f_ng_qty_kg').value = parseFloat(d.ng_qty_kg || 0).toFixed(2);
        document.getElementById('f_lot_material_ng').value = d.lot_material_ng || '';
        document.getElementById('f_material_ng_qty').value = parseFloat(d.material_ng_qty || 0).toFixed(2);
        document.getElementById('f_hard_waste_qty_kg').value = parseFloat(d.hard_waste_qty_kg || 0).toFixed(2);
        document.getElementById('f_hdpe_qty').value = parseFloat(d.hdpe_qty || 0);
        document.getElementById('f_lio_clean_qty').value = parseFloat(d.lio_clean_qty || 0);
        document.getElementById('f_ti_clean_qty').value = parseFloat(d.ti_clean_qty || 0);
        document.getElementById('f_mold_code').value = d.mold_code || 'DA-5';
        document.getElementById('f_spider_code').value = d.spider_code || 'SA-5';

        // Điền chi tiết phế phẩm A1..A4
        ['a1','a2','a3','a4'].forEach(k => document.getElementById('f_defect_' + k).value = 0);
        if (Array.isArray(d.defect_items)) {
            d.defect_items.forEach(df => {
                const code = String(df.code || '').toLowerCase();
                const el = document.getElementById('f_defect_' + code);
                if (el) el.value = parseFloat(df.weight_kg || 0);
            });
        }

        // Điền bảng thời gian dừng máy
        clearAllDowntimeInputsSilently();
        const dtList = Array.isArray(d.downtime_logs) && d.downtime_logs.length > 0
            ? d.downtime_logs
            : (Array.isArray(d.downtime_items) ? d.downtime_items : []);

        dtList.forEach(item => {
            const code = String(item.downtime_code || item.code || '');
            const row = document.querySelector(`.dt-item-row[data-code="${CSS.escape(code)}"]`);
            if (!row) return;
            const occ = parseInt(item.occurrences, 10) || 0;
            const hrs = parseFloat(item.duration_hours) || 0;
            row.querySelector('.dt-inp-occ').value     = occ > 0 ? occ : '';
            row.querySelector('.dt-inp-hours').value   = hrs > 0 ? hrs.toFixed(4).replace(/\.?0+$/, '') : '';
            row.querySelector('.dt-inp-mins').value    = hrs > 0 ? Math.round(hrs * 60) : '';
            if (item.is_machine_running !== undefined) {
                row.querySelector('.dt-inp-running').value = parseInt(item.is_machine_running, 10) || 0;
            }
            if (item.worker_count !== undefined) {
                row.querySelector('.dt-inp-workers').value = parseInt(item.worker_count, 10) || 0;
            }
            if (item.support_worker_count !== undefined) {
                row.querySelector('.dt-inp-support').value = parseInt(item.support_worker_count, 10) || 0;
            }
        });

        updateParentDowntimeGroup('300');
        updateParentDowntimeGroup('308');
        recalcAllMetrics();

        // Hiển thị Banner chế độ Sửa
        const banner = document.getElementById('editModeBanner');
        const txt    = document.getElementById('editModeText');
        if (!isClone) {
            banner.style.display = 'flex';
            txt.textContent = `ĐANG CHỈNH SỬA BÁO CÁO #${d.id} • Mã SX: ${d.production_order_code || '--'} • Ngày SX: ${d.production_date} (${d.shift} - ${d.device_code})`;
            document.getElementById('lblTopSaveBtn').textContent = `Cập Nhật Báo Cáo #${d.id}`;
        } else {
            banner.style.display = 'none';
            document.getElementById('lblTopSaveBtn').textContent = 'Lưu Báo Cáo';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (err) {
        alert('Không thể nạp báo cáo: ' + err.message);
    }
}

function clearAllDowntimeInputsSilently() {
    document.querySelectorAll('#downtimeTableBody .dt-item-row').forEach(row => {
        row.querySelector('.dt-inp-occ').value = '';
        row.querySelector('.dt-inp-mins').value = '';
        row.querySelector('.dt-inp-hours').value = '';
        row.classList.remove('dt-row-active');
    });
}

/**
 * Làm mới Form về trạng thái thêm mới
 */
function resetManualForm(keepShiftAndEmployee = false) {
    const curShift = document.getElementById('f_shift').value;
    const curDate  = document.getElementById('f_production_date').value;
    const curEmpC  = document.getElementById('f_employee_code').value;
    const curEmpN  = document.getElementById('f_employee_name').value;
    const curDev   = document.getElementById('f_device_code').value;

    document.getElementById('extrusionManualForm').reset();
    document.getElementById('f_id').value = 0;
    document.getElementById('f_production_order_code').value = '';
    document.getElementById('kpi_prod_order').textContent = 'P_NT_...';
    document.getElementById('editModeBanner').style.display = 'none';
    document.getElementById('lblTopSaveBtn').textContent = 'Lưu Báo Cáo';

    if (keepShiftAndEmployee) {
        document.getElementById('f_production_date').value = curDate;
        document.getElementById('f_shift').value = curShift;
        document.getElementById('f_employee_code').value = curEmpC;
        document.getElementById('f_employee_name').value = curEmpN;
        document.getElementById('f_device_code').value = curDev;
    } else {
        document.getElementById('f_production_date').value = '<?= date('Y-m-d') ?>';
        initShiftDefaultByCurrentTime();
    }

    onShiftChanged();
    clearAllDowntimeInputsSilently();
    isManualFinishedKg = false;
    onMfgOrderCodeInput();
    recalcAllMetrics();
}

/**
 * Danh sách báo cáo đã nhập gần đây bên dưới form
 */
function onListFilterModeChange() {
    const mode = document.getElementById('lst_mode').value;
    document.getElementById('wrap_lst_month').classList.toggle('d-none', mode !== 'month');
    document.getElementById('wrap_lst_start').classList.toggle('d-none', mode !== 'range');
    document.getElementById('wrap_lst_end').classList.toggle('d-none', mode !== 'range');
    loadRecentReports(1);
}

function debounceLoadRecentReports() {
    clearTimeout(recentSearchTimer);
    recentSearchTimer = setTimeout(() => loadRecentReports(1), 300);
}

async function loadRecentReports(page = 1) {
    const tbody = document.getElementById('recentReportsTbody');
    if (!tbody) return;

    const mode   = document.getElementById('lst_mode').value;
    const month  = document.getElementById('lst_month').value;
    const startD = document.getElementById('lst_start_date').value;
    const endD   = document.getElementById('lst_end_date').value;
    const shift  = document.getElementById('lst_shift').value;
    const source = document.getElementById('lst_source').value;
    const search = document.getElementById('lst_search').value.trim();

    const params = new URLSearchParams({
        action: 'list_reports',
        view_type: 'plastic',
        page: page,
        limit: 15,
        mode: mode,
        month: month,
        start_date: startD,
        end_date: endD,
        shift: shift,
        data_source: source,
        search: search
    });

    try {
        const res = await fetch(`${EXTRUSION_MANUAL_API}?${params.toString()}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Lỗi tải danh sách');

        const rows = json.data || [];
        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" style="text-align:center; padding:16px; color:var(--dx-text-muted);">
                        Không tìm thấy bản ghi báo cáo sản xuất nào phù hợp với bộ lọc.
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = rows.map(r => {
                const eff = parseFloat(r.machine_efficiency || 0).toFixed(1);
                const effColor = eff >= 85 ? '#059669' : (eff >= 70 ? '#d97706' : '#dc2626');
                return `
                    <tr>
                        <td style="white-space:nowrap; text-align:center;">
                            <button type="button" class="app-btn app-btn-primary" style="height:24px; padding:0 6px; font-size:11px;"
                                    onclick="loadReportForEdit(${r.id}, false)" title="Sửa báo cáo #${r.id}">
                                <span class="material-icons" style="font-size:13px;">edit</span> Sửa
                            </button>
                            <button type="button" class="app-btn app-btn-secondary" style="height:24px; padding:0 5px; font-size:11px;"
                                    onclick="loadReportForEdit(${r.id}, true)" title="Nhân bản thành phiếu mới">
                                <span class="material-icons" style="font-size:13px;">content_copy</span>
                            </button>
                            <button type="button" class="app-btn app-btn-danger" style="height:24px; padding:0 5px; font-size:11px;"
                                    onclick="deleteManualReport(${r.id}, '${escapeHtml(r.production_order_code || '')}')" title="Xóa báo cáo">
                                <span class="material-icons" style="font-size:13px;">delete</span>
                            </button>
                        </td>
                        <td style="text-align:center; font-weight:700;">${escapeHtml(r.production_date)}</td>
                        <td style="text-align:center;"><span class="dt-group-pill dt-grp-mgmt">${escapeHtml(r.shift)}</span></td>
                        <td style="text-align:center; font-weight:800; color:#0284c7;">${escapeHtml(r.device_code || '')}</td>
                        <td><b>${escapeHtml(r.employee_code || '')}</b> - ${escapeHtml(r.employee_name || '')}</td>
                        <td style="font-family:monospace;">${escapeHtml(r.mfg_order_code || '')}</td>
                        <td style="font-weight:800;">${escapeHtml(r.product_code || '')}</td>
                        <td style="text-align:center;"><span class="dt-group-pill dt-grp-prod">${escapeHtml(r.pipe_size || '')}</span></td>
                        <td style="text-align:right; font-weight:700;">${parseFloat(r.finished_qty_m || 0).toLocaleString('vi-VN')}</td>
                        <td style="text-align:right; font-weight:700; color:#059669;">${parseFloat(r.finished_qty_kg || 0).toFixed(2)}</td>
                        <td style="text-align:right; color:#d97706;">${parseFloat(r.ng_qty_kg || 0).toFixed(2)}</td>
                        <td style="text-align:right; color:#dc2626;">${parseFloat(r.hard_waste_qty_kg || 0).toFixed(2)}</td>
                        <td style="text-align:right; font-weight:800;">${parseFloat(r.total_weight_kg || 0).toFixed(2)}</td>
                        <td style="text-align:center;">${parseFloat(r.total_runtime || 0).toFixed(2)}</td>
                        <td style="text-align:center; color:#d97706;">${parseFloat(r.total_downtime || 0).toFixed(2)}</td>
                        <td style="text-align:center; font-weight:800; color:${effColor};">${eff}%</td>
                        <td style="font-family:monospace; font-size:11px; color:var(--dx-text-muted);">${escapeHtml(r.production_order_code || '')}</td>
                    </tr>
                `;
            }).join('');
        }

        // Render phân trang
        const pg = json.pagination || { current_page: 1, total_pages: 1, total_records: rows.length };
        document.getElementById('recentPaginationInfo').textContent =
            `Tổng ${pg.total_records} báo cáo • Trang ${pg.current_page}/${pg.total_pages}`;

        const btnWrap = document.getElementById('recentPaginationBtns');
        let pgHtml = '';
        for (let p = 1; p <= Math.min(pg.total_pages, 8); p++) {
            const active = p === pg.current_page ? 'app-btn-primary' : 'app-btn-secondary';
            pgHtml += `<button type="button" class="app-btn ${active}" style="height:25px; padding:0 8px; font-size:11px;" onclick="loadRecentReports(${p})">${p}</button>`;
        }
        btnWrap.innerHTML = pgHtml;
    } catch (err) {
        console.error('Lỗi loadRecentReports:', err);
    }
}

/**
 * Xóa 1 báo cáo
 */
async function deleteManualReport(id, prodOrderCode) {
    if (!confirm(`Bạn có chắc chắn muốn xóa báo cáo #${id} (${prodOrderCode})? Dữ liệu thực tích và thời gian dừng máy liên quan sẽ được đồng bộ lại.`)) {
        return;
    }
    try {
        const res = await fetch(`${EXTRUSION_MANUAL_API}?action=delete_report`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Lỗi xóa báo cáo');
        loadRecentReports(1);
    } catch (err) {
        alert('Lỗi: ' + err.message);
    }
}

/**
 * Phím tắt bàn phím: Ctrl+S (Lưu), Alt+N (Làm mới), Enter nhảy sang ô kế tiếp
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            submitManualReport();
        } else if (e.altKey && e.key.toLowerCase() === 'n') {
            e.preventDefault();
            resetManualForm(false);
        } else if (e.key === 'Enter' && e.target.matches('.ext-inp, .ext-dt-inp')) {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('#extrusionManualForm .ext-inp:not([readonly]), #extrusionManualForm .ext-dt-inp:not([readonly])'))
                .filter(el => el.offsetParent !== null);
            const idx = inputs.indexOf(e.target);
            if (idx >= 0 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
                if (inputs[idx + 1].select) inputs[idx + 1].select();
            }
        }
    });
}

function trimStr(str) {
    return String(str ?? '').trim();
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
