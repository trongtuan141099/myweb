<?php
/**
 * MODULE TIỆN ÍCH: TRANG NHẬP BÁO CÁO SẢN XUẤT ĐÙN THỦ CÔNG (MANUAL INPUT & EDIT)
 * Bám sát 100% cấu trúc, công thức và danh mục của file Format_Báo Cáo Đùn 7.2026.xlsm (Sheet Input, DATA_PLASTIC, DATA_TIME)
 * Hỗ trợ truy cập nhanh từ Màn hình đăng nhập (Login Screen) và chỉnh sửa (Update) trực tiếp.
 */
$editReportId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$isLoggedIn   = isset($_SESSION['user_id']) || isset($_SESSION['user']);
?>
<style>
/* ==========================================================================
   CSS TỐI ƯU GIAO DIỆN NHẬP BÁO CÁO ĐÙN THỦ CÔNG (FORMAT 7.2026)
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

/* Thanh KPI & Kiểm tra thời gian thực (Sticky Bar) */
.ext-kpi-sticky-bar {
    position: sticky;
    top: 60px;
    z-index: 40;
    background: var(--ext-card-bg);
    border: 1px solid var(--ext-border);
    border-left: 5px solid var(--ext-accent);
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px -6px rgba(15, 23, 42, 0.12);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.ext-kpi-items {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;
}
.ext-kpi-chip {
    display: flex;
    flex-direction: column;
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--ext-bg-soft);
    border: 1px solid var(--ext-border);
    min-width: 108px;
}
.ext-kpi-chip .kpi-lbl {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--dx-text-muted, #64748b);
    letter-spacing: 0.02em;
}
.ext-kpi-chip .kpi-val {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dx-text-main, #0f172a);
    line-height: 1.25;
}
.ext-check-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
}
.ext-check-ok {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.3);
}
.ext-check-warn {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.35);
}
.ext-check-err {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.35);
}
[data-theme="dark"] .ext-check-ok { color: #34d399; }
[data-theme="dark"] .ext-check-warn { color: #fbbf24; }
[data-theme="dark"] .ext-check-err { color: #f87171; }

/* Card Section theo chuẩn phiếu Excel Input */
.ext-section-card {
    background: var(--ext-card-bg);
    border: 1px solid var(--ext-border);
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
.ext-section-header {
    background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-weight: 700;
    font-size: 13.5px;
}
.ext-section-header .sec-title {
    display: flex;
    align-items: center;
    gap: 8px;
}
.ext-section-header .sec-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(255,255,255,0.14);
    color: #38bdf8;
}
.ext-section-body {
    padding: 14px 16px;
}

/* Input fields: Ô vàng nhập liệu & Ô xanh tự động tính toán giống Excel */
.ext-field-group {
    margin-bottom: 10px;
}
.ext-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--dx-text-main, #1e293b);
    margin-bottom: 4px;
}
.ext-label .cell-ref {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    background: rgba(100, 116, 139, 0.12);
    padding: 0 5px;
    border-radius: 4px;
    font-family: monospace;
}
.ext-input {
    width: 100%;
    height: 36px;
    padding: 4px 10px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 7px;
    border: 1px solid var(--ext-border);
    background: var(--ext-card-bg);
    color: var(--dx-text-main, #0f172a);
    transition: all 0.15s ease;
}
.ext-input-manual {
    background: var(--ext-input-yellow);
    border-color: #eab308;
}
.ext-input-calc {
    background: var(--ext-calc-blue);
    border-color: var(--ext-calc-blue-border);
    color: #0369a1;
    font-weight: 700;
}
[data-theme="dark"] .ext-input-calc {
    color: #38bdf8;
}
.ext-input:focus {
    outline: none;
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.18);
}

/* Bảng thời gian dừng máy 41 hạng mục */
.ext-downtime-table-wrap {
    max-height: 540px;
    overflow-y: auto;
    border: 1px solid var(--ext-border);
    border-radius: 8px;
}
.ext-dt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.ext-dt-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #1e293b;
    color: #ffffff;
    padding: 8px 8px;
    font-weight: 700;
    font-size: 11.5px;
    text-align: center;
    border-bottom: 2px solid #334155;
    white-space: nowrap;
}
.ext-dt-table tbody td {
    padding: 5px 8px;
    border-bottom: 1px solid var(--ext-border);
    vertical-align: middle;
}
.ext-dt-table tbody tr:hover {
    background: rgba(2, 132, 199, 0.05);
}
.ext-dt-table tbody tr.dt-row-active {
    background: rgba(16, 185, 129, 0.09) !important;
}
.ext-dt-table tbody tr.dt-row-summary {
    background: rgba(59, 130, 246, 0.1) !important;
    font-weight: 700;
}
.ext-dt-input {
    width: 100%;
    height: 29px;
    padding: 2px 6px;
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    border: 1px solid var(--ext-border);
    border-radius: 5px;
    background: var(--ext-card-bg);
    color: var(--dx-text-main);
}
.ext-dt-input:focus {
    outline: none;
    border-color: #0284c7;
    background: var(--ext-input-yellow);
}
.dt-group-pill {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: 700;
}
.dt-grp-mgmt { background: #e0f2fe; color: #0369a1; }
.dt-grp-prod { background: #dcfce7; color: #15803d; }
.dt-grp-equip { background: #fef3c7; color: #b45309; }
.dt-grp-clean { background: #f3e8ff; color: #7e22ce; }
[data-theme="dark"] .dt-grp-mgmt { background: rgba(2,132,199,0.2); color: #38bdf8; }
[data-theme="dark"] .dt-grp-prod { background: rgba(22,163,74,0.2); color: #4ade80; }
[data-theme="dark"] .dt-grp-equip { background: rgba(217,119,6,0.2); color: #fbbf24; }
[data-theme="dark"] .dt-grp-clean { background: rgba(147,51,234,0.2); color: #c084fc; }

/* Banner chế độ Chỉnh sửa (Edit Mode) */
.ext-edit-banner {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 10px;
    background: linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #f59e0b;
    color: #92400e;
    margin-bottom: 14px;
    font-weight: 700;
    font-size: 13px;
}
[data-theme="dark"] .ext-edit-banner {
    background: rgba(245, 158, 11, 0.15);
    border-color: rgba(245, 158, 11, 0.45);
    color: #fbbf24;
}

/* Filter tab buttons cho bảng dừng máy */
.dt-filter-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.dt-tab-btn {
    padding: 5px 10px;
    font-size: 11.5px;
    font-weight: 700;
    border-radius: 6px;
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

/* Phím tắt gợi ý */
.kbd-tag {
    display: inline-block;
    padding: 1px 5px;
    font-size: 10px;
    font-family: monospace;
    background: rgba(15, 23, 42, 0.15);
    border-radius: 4px;
    font-weight: 700;
}
</style>

<div class="app-page-wrapper ext-manual-page">
    <!-- HEADER CHÍNH -->
    <div class="app-page-header" style="margin-bottom: 14px;">
        <div>
            <h1 class="app-page-title" style="display:flex; align-items:center; gap:10px;">
                <span class="material-icons" style="color:#0284c7;">edit_note</span>
                <span>Nhập Báo Cáo Sản Xuất Đùn Thủ Công (Format 7.2026)</span>
            </h1>
            <p class="app-page-subtitle">
                Biểu mẫu chuẩn hóa theo <b>Format_Báo Cáo Đùn 7.2026.xlsm</b> • Tự động đồng bộ 100% vào dữ liệu thực tích (<code>DATA_PLASTIC</code> &amp; <code>DATA_TIME</code>)
            </p>
        </div>

        <div class="app-page-actions" style="display:flex; flex-wrap:wrap; gap:8px;">
            <?php if (!$isLoggedIn): ?>
            <a href="index.php?mainpage=authentication&subpage=login" class="app-btn app-btn-secondary" title="Quay về Màn hình đăng nhập">
                <span class="material-icons">login</span> Màn Hình Đăng Nhập
            </a>
            <?php endif; ?>

            <a href="index.php?mainpage=utilities&subpage=extrusion_actual_lookup" class="app-btn app-btn-warning" title="Mở trang Tra cứu & Xuất Excel DATA_PLASTIC / DATA_TIME">
                <span class="material-icons">manage_search</span> Tra Cứu &amp; Xuất Thực Tích
            </a>

            <button type="button" class="app-btn app-btn-success" onclick="exportStandardExcelQuick('both')" title="Xuất file Excel chuẩn gồm 2 sheet DATA_PLASTIC và DATA_TIME">
                <span class="material-icons">download</span> Xuất Mẫu DATA_PLASTIC &amp; DATA_TIME
            </button>

            <button type="button" class="app-btn app-btn-secondary" onclick="resetManualForm()" title="Làm mới form để nhập báo cáo mới (Alt+N)">
                <span class="material-icons">restart_alt</span> Làm Mới <span class="kbd-tag">Alt+N</span>
            </button>

            <button type="button" class="app-btn app-btn-primary" id="btnTopSaveReport" onclick="submitManualReport()" title="Lưu báo cáo sản xuất (Ctrl+S)">
                <span class="material-icons">save</span> <span id="lblTopSaveBtn">Ghi Nhận Báo Cáo</span> <span class="kbd-tag">Ctrl+S</span>
            </button>
        </div>
    </div>

    <!-- BANNER HIỂN THỊ KHI ĐANG Ở CHẾ ĐỘ CHỈNH SỬA (EDIT) -->
    <div id="editModeBanner" class="ext-edit-banner">
        <div style="display:flex; align-items:center; gap:8px;">
            <span class="material-icons">edit_calendar</span>
            <span id="editModeText">ĐANG CHỈNH SỬA BÁO CÁO ĐÃ NHẬP</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="app-btn app-btn-secondary" style="height:30px; padding:0 10px; font-size:12px;" onclick="resetManualForm()">
                <span class="material-icons" style="font-size:15px;">close</span> Hủy sửa / Nhập phiếu mới
            </button>
        </div>
    </div>

    <!-- THANH KPI & KIỂM TRA THỜI GIAN THỰC (TƯƠNG ĐƯƠNG V6, W6, AA6, F48, F52, F53 TRÊN SHEET INPUT) -->
    <div class="ext-kpi-sticky-bar">
        <div class="ext-kpi-items">
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">Mã SX (Tự động)</span>
                <span class="kpi-val" id="kpi_prod_order" style="color:#0284c7; font-family:monospace;">P_NT_...</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">Thành phẩm A (W10 / F48)</span>
                <span class="kpi-val"><span id="kpi_fin_m">0</span> m • <span id="kpi_fin_kg" style="color:#059669;">0.00</span> kg</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">Tổng KL Liệu (J15=A+B+D)</span>
                <span class="kpi-val"><span id="kpi_total_kg">0.00</span> kg</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">TG Chạy Máy (AK10)</span>
                <span class="kpi-val"><span id="kpi_runtime_h">8.00</span> h</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">TG Dừng Máy (AL56)</span>
                <span class="kpi-val" style="color:#d97706;"><span id="kpi_downtime_h">0.00</span> h</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">TG Máy Thực Tế (V6)</span>
                <span class="kpi-val"><span id="kpi_actual_mch_h">0.00</span> h</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">Hiệu Suất Máy (W6)</span>
                <span class="kpi-val" style="color:#2563eb;"><span id="kpi_efficiency_pct">0.00</span>%</span>
            </div>
            <div class="ext-kpi-chip">
                <span class="kpi-lbl">TG Con Người (AA6)</span>
                <span class="kpi-val"><span id="kpi_human_h">0.00</span> h</span>
            </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
            <div id="badgeCheckMachineTime" class="ext-check-badge ext-check-warn" title="F52: Kiểm tra TG máy thực tế (V6) nằm trong khoảng 90% - 110% TG chạy máy (AK10)">
                <span class="material-icons" style="font-size:16px;">speed</span>
                <span id="txtCheckMachineTime">KT TG Máy: Chưa đủ dữ liệu</span>
            </div>
            <div id="badgeCheckHumanTime" class="ext-check-badge ext-check-warn" title="F53: Kiểm tra tổng thời gian thao tác con người (AA6) >= 7.2 giờ/ca">
                <span class="material-icons" style="font-size:16px;">engineering</span>
                <span id="txtCheckHumanTime">KT TG Người: 0.00h / 7.20h</span>
            </div>
        </div>
    </div>

    <!-- FORM NHẬP BÁO CÁO CHÍNH -->
    <form id="extrusionManualForm" onsubmit="event.preventDefault(); submitManualReport();">
        <input type="hidden" id="f_id" value="0">
        <input type="hidden" id="f_production_order_code" value="">

        <!-- =================================================================
             KHỐI 1 & 2: THÔNG TIN CA/MÁY/NHÂN VIÊN & SẢN LƯỢNG THÀNH PHẨM
             ================================================================= -->
        <div class="row g-3">
            <!-- CỘT TRÁI: THÔNG TIN CA SẢN XUẤT, NHÂN VIÊN & MÃ SẢN PHẨM -->
            <div class="col-12 col-xl-6">
                <div class="ext-section-card h-100">
                    <div class="ext-section-header">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:18px; color:#38bdf8;">badge</span>
                            <span>1. Thông Tin Ca Sản Xuất, Máy Đùn &amp; Nhân Viên Vận Hành</span>
                        </div>
                        <span class="sec-badge">Sheet Input • B5:Q11</span>
                    </div>
                    <div class="ext-section-body">
                        <div class="row g-2">
                            <!-- Ngày SX (K6) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Ngày sản xuất <span class="text-danger">*</span></span>
                                    <span class="cell-ref">K6</span>
                                </label>
                                <input type="date" id="f_production_date" class="ext-input ext-input-manual" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Ca làm việc (E10) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Ca làm việc <span class="text-danger">*</span></span>
                                    <span class="cell-ref">E10</span>
                                </label>
                                <select id="f_shift" class="ext-input ext-input-manual" onchange="onShiftChanged()">
                                    <option value="Ca 1">Ca 1 (06:00 - 14:00)</option>
                                    <option value="Ca 2">Ca 2 (14:00 - 22:00)</option>
                                    <option value="Ca 3">Ca 3 (22:00 - 06:00)</option>
                                    <option value="Ca HC">Ca HC (08:00 - 17:00)</option>
                                </select>
                            </div>

                            <!-- Loại sản xuất (B10) -->
                            <div class="col-12 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Loại sản xuất <span class="text-danger">*</span></span>
                                    <span class="cell-ref">B10</span>
                                </label>
                                <select id="f_production_type" class="ext-input ext-input-manual" onchange="onProductionTypeOrProductChanged()">
                                    <option value="Hàng sản xuất">Hàng sản xuất (DATA_PLASTIC)</option>
                                    <option value="Hàng bù">Hàng bù (DATA_BU)</option>
                                    <option value="Hàng điều chỉnh">Hàng điều chỉnh (DC)</option>
                                </select>
                            </div>

                            <!-- Mã nhân viên MSNV (P6) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã NV (MSNV) <span class="text-danger">*</span></span>
                                    <span class="cell-ref">P6</span>
                                </label>
                                <input type="text" id="f_employee_code" list="dl_employees" class="ext-input ext-input-manual" placeholder="VD: 02020965" oninput="onEmployeeCodeInput()" required>
                                <datalist id="dl_employees"></datalist>
                            </div>

                            <!-- Tên nhân viên (Q6) -->
                            <div class="col-6 col-md-5 ext-field-group">
                                <label class="ext-label">
                                    <span>Họ tên nhân viên <span class="text-danger">*</span></span>
                                    <span class="cell-ref">Q6</span>
                                </label>
                                <input type="text" id="f_employee_name" list="dl_employee_names" class="ext-input ext-input-calc" placeholder="Tự động điền hoặc gõ tên..." oninput="onEmployeeNameInput()" required>
                                <datalist id="dl_employee_names"></datalist>
                            </div>

                            <!-- Thử nghiệm (N6) -->
                            <div class="col-12 col-md-3 ext-field-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
                                <label class="ext-label">
                                    <span>Hàng thử nghiệm</span>
                                    <span class="cell-ref">N6</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; height:36px; padding:0 10px; border:1px solid var(--ext-border); border-radius:7px; background:var(--ext-bg-soft); cursor:pointer; font-size:12.5px; font-weight:700;">
                                    <input type="checkbox" id="f_is_test" style="width:16px; height:16px;">
                                    <span>Thử nghiệm (Test)</span>
                                </label>
                            </div>

                            <!-- Mã sản phẩm (G10) -->
                            <div class="col-12 col-md-5 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã sản phẩm <span class="text-danger">*</span></span>
                                    <span class="cell-ref">G10</span>
                                </label>
                                <input type="text" id="f_product_code" list="dl_products" class="ext-input ext-input-manual" placeholder="VD: TU0604B-1MZ2" oninput="onProductionTypeOrProductChanged()" required>
                                <datalist id="dl_products"></datalist>
                            </div>

                            <!-- Mã chỉ thị sản xuất CTSX (C10) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã CTSX</span>
                                    <span class="cell-ref">C10</span>
                                </label>
                                <input type="text" id="f_mfg_order_code" class="ext-input ext-input-calc" placeholder="Tự động tra từ Mã SP">
                            </div>

                            <!-- Size ống (H10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Size ống</span>
                                    <span class="cell-ref">H10</span>
                                </label>
                                <input type="text" id="f_pipe_size" class="ext-input ext-input-calc" placeholder="TU0604" readonly>
                            </div>

                            <!-- Mã thiết bị / Máy đùn (F10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã thiết bị <span class="text-danger">*</span></span>
                                    <span class="cell-ref">F10</span>
                                </label>
                                <select id="f_device_code" class="ext-input ext-input-manual">
                                    <?php for ($m = 1; $m <= 24; $m++): $mc = sprintf('PL%02d', $m); ?>
                                    <option value="<?= $mc ?>"><?= $mc ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Số máy chờ (F11) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Số máy chờ</span>
                                    <span class="cell-ref">F11</span>
                                </label>
                                <input type="number" id="f_waiting_machine_count" class="ext-input ext-input-manual" value="0" min="0" max="24">
                            </div>

                            <!-- Loại máy in (N10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Loại máy in</span>
                                    <span class="cell-ref">N10</span>
                                </label>
                                <select id="f_printer_type" class="ext-input ext-input-manual">
                                    <option value="A200">A200</option>
                                    <option value="AX300i">AX300i</option>
                                </select>
                            </div>

                            <!-- Màu mực in (N11) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Màu mực in</span>
                                    <span class="cell-ref">N11</span>
                                </label>
                                <select id="f_ink_type" class="ext-input ext-input-calc">
                                    <option value="Trắng">Trắng</option>
                                    <option value="Đen">Đen</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dải thông tin định mức tự động của Size ống -->
                        <div style="margin-top:4px; padding:8px 12px; border-radius:8px; background:var(--ext-bg-soft); border:1px dashed var(--ext-border); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; font-size:12px;">
                            <div>
                                <span style="color:var(--dx-text-muted);">Màu ống (K10):</span>
                                <b id="info_color_code" style="color:#0284c7;">--</b>
                            </div>
                            <div>
                                <span style="color:var(--dx-text-muted);">Định mức BOM (List!G:H):</span>
                                <b id="info_bom_kg_m" style="color:#059669;">0.0000</b> <span>kg/m</span>
                            </div>
                            <div>
                                <span style="color:var(--dx-text-muted);">Chiều dài chuẩn (List!P:Q):</span>
                                <b id="info_std_coil_m" style="color:#7c3aed;">0</b> <span>m/bobin</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI: SẢN LƯỢNG THÀNH PHẨM, BOBIN & THỜI GIAN CHẠY MÁY -->
            <div class="col-12 col-xl-6">
                <div class="ext-section-card h-100">
                    <div class="ext-section-header">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:18px; color:#34d399;">precision_manufacturing</span>
                            <span>2. Sản Lượng Thành Phẩm, Bobin &amp; Thời Gian Chạy Máy</span>
                        </div>
                        <span class="sec-badge">Sheet Input • R8:AK11</span>
                    </div>
                    <div class="ext-section-body">
                        <div class="row g-2">
                            <!-- Tốc độ thực tế Mpm (R11) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Tốc độ (Mpm) <span class="text-danger">*</span></span>
                                    <span class="cell-ref">R11</span>
                                </label>
                                <input type="number" step="0.1" id="f_cycle_time" class="ext-input ext-input-manual" value="25" min="0" oninput="recalcAllMetrics()">
                            </div>

                            <!-- Giờ bắt đầu (AE10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Giờ bắt đầu</span>
                                    <span class="cell-ref">AE10</span>
                                </label>
                                <input type="time" id="f_start_time" class="ext-input ext-input-manual" value="06:00" onchange="onStartEndTimeChanged()">
                            </div>

                            <!-- Giờ kết thúc (AH10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Giờ kết thúc</span>
                                    <span class="cell-ref">AH10</span>
                                </label>
                                <input type="time" id="f_end_time" class="ext-input ext-input-manual" value="14:00" onchange="onStartEndTimeChanged()">
                            </div>

                            <!-- Thời gian chạy máy (AK10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>TG chạy máy (h)</span>
                                    <span class="cell-ref">AK10</span>
                                </label>
                                <input type="number" step="0.01" id="f_total_runtime" class="ext-input ext-input-calc" value="8.00" min="0" max="24" oninput="recalcAllMetrics()">
                            </div>

                            <!-- Bobin PL7-3: Số cuộn (AB10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Bobin PL7-3 (Cuộn)</span>
                                    <span class="cell-ref">AB10</span>
                                </label>
                                <input type="number" step="1" id="f_bobbin_pl7_3_count" class="ext-input ext-input-manual" value="0" min="0" oninput="onBobbinCountChanged('pl73')">
                            </div>

                            <!-- Bobin PL7-3: Số mét (AC10) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Số mét PL7-3 (m)</span>
                                    <span class="cell-ref">AC10</span>
                                </label>
                                <input type="number" step="0.01" id="f_bobbin_pl7_3_meters" class="ext-input ext-input-manual" value="0" min="0" oninput="onBobbinMetersChanged()">
                            </div>

                            <!-- Bobin PL4-7: Số cuộn (AB11) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Bobin PL4-7 (Cuộn)</span>
                                    <span class="cell-ref">AB11</span>
                                </label>
                                <input type="number" step="1" id="f_bobbin_pl4_7_count" class="ext-input ext-input-manual" value="0" min="0" oninput="onBobbinCountChanged('pl47')">
                            </div>

                            <!-- Bobin PL4-7: Số mét (AC11) -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Số mét PL4-7 (m)</span>
                                    <span class="cell-ref">AC11</span>
                                </label>
                                <input type="number" step="0.01" id="f_bobbin_pl4_7_meters" class="ext-input ext-input-manual" value="0" min="0" oninput="onBobbinMetersChanged()">
                            </div>

                            <!-- Thành phẩm A (m) (W10 = AC10 + AC11) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Thành phẩm A (m)</span>
                                    <span class="cell-ref">W10</span>
                                </label>
                                <input type="number" step="0.01" id="f_finished_qty_m" class="ext-input ext-input-calc" value="0" min="0" oninput="recalcAllMetrics()">
                            </div>

                            <!-- Khối lượng Thành phẩm A (kg) (F48 = W10 * BOM) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>KL Thành phẩm A (kg)</span>
                                    <span class="cell-ref">F48</span>
                                </label>
                                <input type="number" step="0.01" id="f_finished_qty_kg" class="ext-input ext-input-calc" value="0.00" min="0" oninput="onFinishedKgManualEdit()">
                            </div>

                            <!-- Số bobin quy đổi (Col AN) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Số Bobin chuẩn</span>
                                    <span class="cell-ref">Col AN</span>
                                </label>
                                <input type="number" step="0.01" id="f_total_bobbin_calc" class="ext-input ext-input-calc" value="0.00" readonly>
                            </div>

                            <!-- Số mét hàng điều chỉnh (V10) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Số mét Hàng ĐC (m)</span>
                                    <span class="cell-ref">V10</span>
                                </label>
                                <input type="number" step="0.01" id="f_adj_meters" class="ext-input ext-input-manual" value="0" min="0" oninput="recalcAllMetrics()">
                            </div>

                            <!-- Khối lượng hàng điều chỉnh (F54) -->
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>KL Hàng ĐC (kg)</span>
                                    <span class="cell-ref">F54</span>
                                </label>
                                <input type="number" step="0.01" id="f_adj_weight_kg" class="ext-input ext-input-calc" value="0.00" readonly>
                            </div>

                            <!-- Nút nhân nhanh số mét từ số cuộn -->
                            <div class="col-12 col-md-4 ext-field-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
                                <button type="button" class="app-btn app-btn-secondary w-100" style="height:36px; font-size:12px;" onclick="autoMultiplyBobbinMeters()" title="Tính nhanh Số mét = Số cuộn × Chiều dài chuẩn của Size ống">
                                    <span class="material-icons" style="font-size:16px;">calculate</span> Tính Mét Từ Số Cuộn
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================
             KHỐI 3 & 4: NGUYÊN VẬT LIỆU / PHẾ PHẨM & BẢNG DỪNG MÁY 41 HẠNG MỤC
             ================================================================= -->
        <div class="row g-3 mt-1">
            <!-- CỘT TRÁI: NGUYÊN VẬT LIỆU, PHẾ PHẨM (A, B, C, D), CHẤT VỆ SINH, KHUÔN -->
            <div class="col-12 col-xl-5">
                <div class="ext-section-card h-100">
                    <div class="ext-section-header">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:18px; color:#fbbf24;">inventory_2</span>
                            <span>3. Nguyên Vật Liệu, Phế Phẩm (B/C/D) &amp; Khuôn Đùn</span>
                        </div>
                        <span class="sec-badge">Sheet Input • B13:Q34</span>
                    </div>
                    <div class="ext-section-body">
                        <!-- A. Nguyên liệu sản xuất (Row 15) -->
                        <div style="font-size:12px; font-weight:800; color:#0284c7; text-transform:uppercase; margin-bottom:6px; border-bottom:1px solid var(--ext-border); padding-bottom:3px;">
                            A. Nguyên Liệu Sản Xuất Chính (Row 15)
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-md-5 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã Lot NL (Lot No)</span>
                                    <span class="cell-ref">B15</span>
                                </label>
                                <input type="text" id="f_material_code" list="dl_recent_lots" class="ext-input ext-input-manual" placeholder="VD: 34N1250099" oninput="onMainLotInput()">
                                <datalist id="dl_recent_lots"></datalist>
                            </div>
                            <div class="col-6 col-md-4 ext-field-group">
                                <label class="ext-label">
                                    <span>Loại vật liệu</span>
                                    <span class="cell-ref">F15</span>
                                </label>
                                <input type="text" id="f_material_type" list="dl_material_types" class="ext-input ext-input-manual" placeholder="BASF / DIC / VLN">
                                <datalist id="dl_material_types">
                                    <option value="BASF"></option>
                                    <option value="DIC"></option>
                                    <option value="HUNSTMAN"></option>
                                    <option value="COVESTRO"></option>
                                    <option value="VLN"></option>
                                </datalist>
                            </div>
                            <div class="col-4 col-md-3 ext-field-group">
                                <label class="ext-label">
                                    <span>Lần nghiền</span>
                                    <span class="cell-ref">G15</span>
                                </label>
                                <select id="f_regrind_count" class="ext-input ext-input-manual">
                                    <option value="0">0 (Zin)</option>
                                    <option value="1">1 lần</option>
                                    <option value="2">2 lần</option>
                                    <option value="3">3 lần</option>
                                </select>
                            </div>
                            <div class="col-8 col-md-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã kiện/bao nghiền</span>
                                    <span class="cell-ref">H15</span>
                                </label>
                                <input type="text" id="f_regrind_package_code" class="ext-input ext-input-calc" placeholder="Mặc định theo Lot B15">
                            </div>
                            <div class="col-12 col-md-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Tổng TL Nguyên liệu (A+B+D)</span>
                                    <span class="cell-ref">J15</span>
                                </label>
                                <input type="number" step="0.01" id="f_total_weight_kg" class="ext-input ext-input-calc" value="0.00" readonly>
                            </div>
                        </div>

                        <!-- B. Phế phẩm điều chỉnh & Phân loại phế phẩm A1..A4 -->
                        <div style="font-size:12px; font-weight:800; color:#d97706; text-transform:uppercase; margin:10px 0 6px; border-bottom:1px solid var(--ext-border); padding-bottom:3px; display:flex; justify-content:space-between;">
                            <span>B. Phế Phẩm Điều Chỉnh / NG Trong Ca (P15:Q22)</span>
                            <span>Đơn vị: kg</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Lot No Phế phẩm ĐC</span>
                                    <span class="cell-ref">P15</span>
                                </label>
                                <input type="text" id="f_lot_in" class="ext-input ext-input-calc" placeholder="Tự động theo B15">
                            </div>
                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Tổng TL Phế phẩm B (kg)</span>
                                    <span class="cell-ref">Q15</span>
                                </label>
                                <input type="number" step="0.01" id="f_ng_qty_kg" class="ext-input ext-input-manual" value="0.00" min="0" oninput="recalcAllMetrics()">
                            </div>

                            <!-- 4 ô phân loại phế phẩm A1 - A4 -->
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label" title="A1: Phế phẩm công đoạn trước">
                                    <span>A1: CĐ trước</span>
                                    <span class="cell-ref">Q19</span>
                                </label>
                                <input type="number" step="0.01" id="f_defect_a1" class="ext-input ext-input-manual" value="0" min="0" oninput="onDefectDetailInput()">
                            </div>
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label" title="A2: Phế phẩm điều chỉnh">
                                    <span>A2: Điều chỉnh</span>
                                    <span class="cell-ref">Q20</span>
                                </label>
                                <input type="number" step="0.01" id="f_defect_a2" class="ext-input ext-input-manual" value="0" min="0" oninput="onDefectDetailInput()">
                            </div>
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label" title="A3: Phế phẩm bất thường">
                                    <span>A3: Bất thường</span>
                                    <span class="cell-ref">Q21</span>
                                </label>
                                <input type="number" step="0.01" id="f_defect_a3" class="ext-input ext-input-manual" value="0" min="0" oninput="onDefectDetailInput()">
                            </div>
                            <div class="col-6 col-md-3 ext-field-group">
                                <label class="ext-label" title="A4: Phế phẩm thử nghiệm">
                                    <span>A4: Thử nghiệm</span>
                                    <span class="cell-ref">Q22</span>
                                </label>
                                <input type="number" step="0.01" id="f_defect_a4" class="ext-input ext-input-manual" value="0" min="0" oninput="onDefectDetailInput()">
                            </div>
                        </div>

                        <!-- C & D: Nguyên liệu NG & Nguyên liệu hủy (Cục cứng) -->
                        <div style="font-size:12px; font-weight:800; color:#dc2626; text-transform:uppercase; margin:10px 0 6px; border-bottom:1px solid var(--ext-border); padding-bottom:3px;">
                            C &amp; D. Nguyên Liệu NG &amp; Nguyên Liệu Hủy / Cục Cứng (B20:J25)
                        </div>
                        <div class="row g-2">
                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>C - Lot Nguyên liệu NG</span>
                                    <span class="cell-ref">B20</span>
                                </label>
                                <input type="text" id="f_lot_material_ng" class="ext-input ext-input-manual" placeholder="Lot NL NG (nếu có)">
                            </div>
                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>C - TL Nguyên liệu NG (kg)</span>
                                    <span class="cell-ref">J20</span>
                                </label>
                                <input type="number" step="0.01" id="f_material_ng_qty" class="ext-input ext-input-manual" value="0.00" min="0">
                            </div>

                            <div class="col-12 ext-field-group">
                                <label class="ext-label">
                                    <span>D - TL Nguyên liệu hủy / Cục cứng (kg)</span>
                                    <span class="cell-ref">J25</span>
                                </label>
                                <input type="number" step="0.01" id="f_hard_waste_qty_kg" class="ext-input ext-input-manual" value="0.00" min="0" oninput="recalcAllMetrics()">
                            </div>
                        </div>

                        <!-- Chất vệ sinh & Mã khuôn / Spider -->
                        <div style="font-size:12px; font-weight:800; color:#7c3aed; text-transform:uppercase; margin:10px 0 6px; border-bottom:1px solid var(--ext-border); padding-bottom:3px;">
                            Nguyên Liệu Vệ Sinh Máy &amp; Mã Khuôn / Mandrell (B29:F34)
                        </div>
                        <div class="row g-2">
                            <div class="col-4 ext-field-group">
                                <label class="ext-label">
                                    <span>HDPE 6200</span>
                                    <span class="cell-ref">J29</span>
                                </label>
                                <input type="number" step="0.01" id="f_hdpe_qty" class="ext-input ext-input-manual" value="0" min="0">
                            </div>
                            <div class="col-4 ext-field-group">
                                <label class="ext-label">
                                    <span>LIOCLEAN-Z</span>
                                    <span class="cell-ref">J30</span>
                                </label>
                                <input type="number" step="0.01" id="f_lio_clean_qty" class="ext-input ext-input-manual" value="0" min="0">
                            </div>
                            <div class="col-4 ext-field-group">
                                <label class="ext-label">
                                    <span>TI-CLEAN</span>
                                    <span class="cell-ref">J31</span>
                                </label>
                                <input type="number" step="0.01" id="f_ti_clean_qty" class="ext-input ext-input-manual" value="0" min="0">
                            </div>

                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã khuôn (Mold Code)</span>
                                    <span class="cell-ref">F33</span>
                                </label>
                                <input type="text" id="f_mold_code" class="ext-input ext-input-manual" value="1" placeholder="VD: 1, 2...">
                            </div>
                            <div class="col-6 ext-field-group">
                                <label class="ext-label">
                                    <span>Mã Mandrell / Spider</span>
                                    <span class="cell-ref">F34</span>
                                </label>
                                <input type="text" id="f_spider_code" class="ext-input ext-input-manual" value="1" placeholder="VD: 1, 2...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI: BẢNG THỜI GIAN DỪNG MÁY & THAO TÁC CON NGƯỜI (41 HẠNG MỤC -> DATA_TIME) -->
            <div class="col-12 col-xl-7">
                <div class="ext-section-card h-100">
                    <div class="ext-section-header">
                        <div class="sec-title">
                            <span class="material-icons" style="font-size:18px; color:#f87171;">timer</span>
                            <span>4. Bảng Thời Gian Dừng Máy &amp; Thao Tác (Xuất Sang Sheet DATA_TIME)</span>
                        </div>
                        <span class="sec-badge">Sheet Input • S15:AO56</span>
                    </div>
                    <div class="ext-section-body">
                        <!-- Thanh lọc nhanh nhóm lỗi & Tìm kiếm mã lỗi -->
                        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px;">
                            <div class="dt-filter-tabs" id="dtGroupTabs">
                                <button type="button" class="dt-tab-btn active" data-grp="ALL" onclick="filterDowntimeRows('ALL', this)">Tất cả (41)</button>
                                <button type="button" class="dt-tab-btn" data-grp="ACTIVE" onclick="filterDowntimeRows('ACTIVE', this)">Đã nhập (<span id="cntActiveDt">0</span>)</button>
                                <button type="button" class="dt-tab-btn" data-grp="Lỗi quản lý" onclick="filterDowntimeRows('Lỗi quản lý', this)">Lỗi quản lý (6)</button>
                                <button type="button" class="dt-tab-btn" data-grp="Lỗi sản xuất" onclick="filterDowntimeRows('Lỗi sản xuất', this)">Lỗi SX (6)</button>
                                <button type="button" class="dt-tab-btn" data-grp="Lỗi thiết bị/ điều chỉnh" onclick="filterDowntimeRows('Lỗi thiết bị/ điều chỉnh', this)">Thiết bị &amp; ĐC (22)</button>
                                <button type="button" class="dt-tab-btn" data-grp="Đổi màu/ vệ sinh" onclick="filterDowntimeRows('Đổi màu/ vệ sinh', this)">Đổi màu/VS (7)</button>
                            </div>

                            <div style="display:flex; align-items:center; gap:6px; flex:1; max-width:260px;">
                                <input type="text" id="dtSearchInput" class="ext-input" style="height:31px; font-size:12px;" placeholder="Tìm nhanh mã (104, 114, 308-3...)" oninput="renderDowntimeTableVisibility()">
                                <button type="button" class="app-btn app-btn-secondary" style="height:31px; padding:0 8px; font-size:11.5px;" onclick="clearAllDowntimeInputs()" title="Xóa trắng các ô thời gian dừng máy">
                                    Xóa giờ
                                </button>
                            </div>
                        </div>

                        <!-- Bảng 41 hạng mục dừng máy -->
                        <div class="ext-downtime-table-wrap">
                            <table class="ext-dt-table">
                                <thead>
                                    <tr>
                                        <th style="width:110px;">Phân loại</th>
                                        <th style="width:64px;">Mã lỗi</th>
                                        <th style="text-align:left;">Hạng mục dừng máy / Thao tác</th>
                                        <th style="width:68px;">Số lần<br><span style="font-size:9.5px; color:#94a3b8;">(AE)</span></th>
                                        <th style="width:78px;">Số phút<br><span style="font-size:9.5px; color:#fde047;">(Nhập nhanh)</span></th>
                                        <th style="width:82px;">Giờ (h)<br><span style="font-size:9.5px; color:#93c5fd;">(DATA_TIME)</span></th>
                                        <th style="width:64px;" title="AL: Máy đang chạy = 1 (Tính vào dừng máy), 0 (Không tính dừng máy)">Máy chạy<br><span style="font-size:9.5px; color:#94a3b8;">(AL: 1/0)</span></th>
                                        <th style="width:64px;" title="AM: Số người thực hiện thao tác">Số người<br><span style="font-size:9.5px; color:#94a3b8;">(AM)</span></th>
                                        <th style="width:64px;" title="AN: Số người hỗ trợ">Hỗ trợ<br><span style="font-size:9.5px; color:#94a3b8;">(AN)</span></th>
                                    </tr>
                                </thead>
                                <tbody id="downtimeTableBody">
                                    <!-- Render tự động từ danh mục 41 hạng mục của Format 7.2026 -->
                                </tbody>
                                <tfoot>
                                    <tr style="background:#0f172a; color:#ffffff; font-weight:800; font-size:12.5px;">
                                        <td colspan="3" style="text-align:right; padding:8px 12px;">TỔNG CỘNG (Dòng 56 Sheet Input):</td>
                                        <td id="ft_total_occ" style="text-align:center; color:#fde047;">0</td>
                                        <td id="ft_total_mins" style="text-align:center; color:#fde047;">0 p</td>
                                        <td id="ft_total_hours" style="text-align:center; color:#38bdf8;">0.00 h</td>
                                        <td id="ft_mch_downtime" style="text-align:center; color:#fb923c;" title="AL56: Tổng thời gian dừng máy (h)">0.00 h</td>
                                        <td id="ft_human_time" colspan="2" style="text-align:center; color:#4ade80;" title="AM56 / AA6: Tổng thời gian con người (h)">Người: 0.00 h</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- THANH NÚT LƯU BÁO CÁO DƯỚI FORM -->
        <div class="app-card mt-3" style="padding:12px 18px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; border:1px solid var(--ext-border);">
            <div style="font-size:12.5px; color:var(--dx-text-muted);">
                <span class="material-icons" style="font-size:16px; vertical-align:middle; color:#0284c7;">info</span>
                Mẹo thao tác nhanh: Nhấn <b>Enter</b> để di chuyển qua các ô nhập liệu • Nhấn <b>Ctrl + S</b> để lưu ngay báo cáo • Nhấn <b>Alt + N</b> để làm mới phiếu.
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="app-btn app-btn-secondary" onclick="resetManualForm()">
                    <span class="material-icons">restart_alt</span> Nhập Lại Từ Đầu
                </button>
                <button type="submit" class="app-btn app-btn-primary" id="btnBottomSaveReport" style="padding:0 22px; height:40px; font-size:14px; font-weight:700;">
                    <span class="material-icons">check_circle</span>
                    <span id="lblBottomSaveBtn">LƯU BÁO CÁO SẢN XUẤT (CTRL+S)</span>
                </button>
            </div>
        </div>
    </form>

    <!-- =====================================================================
         KHỐI 5: DANH SÁCH BÁO CÁO ĐÃ NHẬP GẦN ĐÂY & CHỨC NĂNG CHỈNH SỬA (EDIT)
         ===================================================================== -->
    <div class="ext-section-card mt-4">
        <div class="ext-section-header">
            <div class="sec-title">
                <span class="material-icons" style="font-size:18px; color:#a78bfa;">history_edu</span>
                <span>5. Danh Sách Báo Cáo Thực Tích Đã Nhập (Hỗ Trợ Chỉnh Sửa / Cập Nhật Trực Tiếp)</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="index.php?mainpage=utilities&subpage=extrusion_actual_lookup" class="app-btn app-btn-warning" style="height:28px; padding:0 10px; font-size:11.5px;">
                    <span class="material-icons" style="font-size:15px;">open_in_new</span> Mở Trang Tra Cứu &amp; Xuất Excel Đầy Đủ
                </a>
            </div>
        </div>
        <div class="ext-section-body">
            <!-- Bộ lọc danh sách báo cáo bên dưới -->
            <div class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-2">
                    <label class="ext-label">Chế độ lọc thời gian</label>
                    <select id="lst_mode" class="ext-input" onchange="onListFilterModeChange()">
                        <option value="all" selected>Tất cả thời gian</option>
                        <option value="month">Theo tháng</option>
                        <option value="range">Khoảng ngày</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_month">
                    <label class="ext-label">Chọn tháng</label>
                    <input type="month" id="lst_month" class="ext-input" value="<?= date('Y-m') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_start">
                    <label class="ext-label">Từ ngày</label>
                    <input type="date" id="lst_start_date" class="ext-input" value="<?= date('Y-m-01') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2 d-none" id="wrap_lst_end">
                    <label class="ext-label">Đến ngày</label>
                    <input type="date" id="lst_end_date" class="ext-input" value="<?= date('Y-m-t') ?>" onchange="loadRecentReports(1)">
                </div>
                <div class="col-6 col-md-2">
                    <label class="ext-label">Ca làm việc</label>
                    <select id="lst_shift" class="ext-input" onchange="loadRecentReports(1)">
                        <option value="ALL">-- Tất cả ca --</option>
                        <option value="Ca 1">Ca 1</option>
                        <option value="Ca 2">Ca 2</option>
                        <option value="Ca 3">Ca 3</option>
                        <option value="Ca HC">Ca HC</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="ext-label">Nguồn dữ liệu</label>
                    <select id="lst_source" class="ext-input" onchange="loadRecentReports(1)">
                        <option value="MANUAL" selected>Báo cáo thủ công (MANUAL)</option>
                        <option value="ALL">Tất cả nguồn (MANUAL + EXCEL)</option>
                    </select>
                </div>
                <div class="col-8 col-md-3">
                    <label class="ext-label">Tìm kiếm nhanh</label>
                    <input type="text" id="lst_search" class="ext-input" placeholder="Mã SP, MSNV, Tên NV, Máy, Mã SX..." oninput="debounceLoadRecentReports()">
                </div>
                <div class="col-4 col-md-1">
                    <button type="button" class="app-btn app-btn-primary w-100" style="height:36px;" onclick="loadRecentReports(1)">
                        <span class="material-icons" style="font-size:17px;">refresh</span>
                    </button>
                </div>
            </div>

            <!-- Bảng dữ liệu đã nhập -->
            <div style="overflow-x:auto; border:1px solid var(--ext-border); border-radius:8px;">
                <table class="ext-dt-table" style="min-width:1150px;">
                    <thead>
                        <tr>
                            <th style="width:110px;">Thao tác</th>
                            <th>Ngày SX</th>
                            <th>Ca</th>
                            <th>Máy</th>
                            <th>MSNV &amp; Họ tên</th>
                            <th>Mã CTSX</th>
                            <th>Mã sản phẩm</th>
                            <th>Size</th>
                            <th>Thành phẩm (m)</th>
                            <th>KL TP (kg)</th>
                            <th>KL NG (kg)</th>
                            <th>KL Cứng (kg)</th>
                            <th>Tổng KL (kg)</th>
                            <th>TG Chạy (h)</th>
                            <th>TG Dừng (h)</th>
                            <th>H.Suất (%)</th>
                            <th>Mã SX (DATA_PLASTIC)</th>
                        </tr>
                    </thead>
                    <tbody id="recentReportsTbody">
                        <tr>
                            <td colspan="17" style="text-align:center; padding:24px; color:var(--dx-text-muted);">
                                Đang tải danh sách báo cáo sản xuất...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Phân trang -->
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; font-size:12.5px;">
                <div id="recentPaginationInfo" style="color:var(--dx-text-muted); font-weight:600;">Hiển thị 0 bản ghi</div>
                <div id="recentPaginationBtns" style="display:flex; gap:5px;"></div>
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
    products: {},
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

        // 2. Datalist Mã sản phẩm
        const dlProd = document.getElementById('dl_products');
        if (dlProd) {
            let htmlProd = '';
            Object.entries(masterData.products || {}).forEach(([pCode, ctsx]) => {
                htmlProd += `<option value="${escapeHtml(pCode)}">CTSX: ${escapeHtml(ctsx)}</option>`;
            });
            dlProd.innerHTML = htmlProd;
        }

        // 3. Datalist Lot nguyên liệu gần đây
        const dlLots = document.getElementById('dl_recent_lots');
        if (dlLots && Array.isArray(masterData.recent_lots)) {
            dlLots.innerHTML = masterData.recent_lots.map(item =>
                `<option value="${escapeHtml(item.material_code)}">${escapeHtml(item.material_type || '')}</option>`
            ).join('');
        }

        // 4. Render bảng 41 hạng mục dừng máy
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

        const readonlyAttr = isSum ? 'readonly tabindex="-1" style="background:rgba(59,130,246,0.12); color:#0284c7;"' : '';

        return `
            <tr class="dt-item-row ${isSum ? 'dt-row-summary' : ''}"
                data-code="${escapeHtml(code)}"
                data-group="${escapeHtml(item.group)}"
                data-parent="${escapeHtml(parent)}"
                data-is-summary="${isSum ? '1' : '0'}"
                data-def-running="${item.def_running}"
                data-def-workers="${item.def_workers}"
                data-def-support="${item.def_support}">
                <td><span class="dt-group-pill ${grpClass}">${escapeHtml(item.group)}</span></td>
                <td style="text-align:center; font-weight:800; font-family:monospace;">${escapeHtml(code)}</td>
                <td style="font-weight:${isSum ? '800' : '600'};">${escapeHtml(item.name)}</td>
                <td>
                    <input type="number" step="1" min="0" class="ext-dt-input dt-inp-occ"
                           data-code="${escapeHtml(code)}" value="" placeholder="0"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'occ')">
                </td>
                <td>
                    <input type="number" step="1" min="0" class="ext-dt-input dt-inp-mins"
                           data-code="${escapeHtml(code)}" value="" placeholder="Phút"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'mins')">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="ext-dt-input dt-inp-hours"
                           data-code="${escapeHtml(code)}" value="" placeholder="0.00"
                           ${readonlyAttr}
                           oninput="onDowntimeRowChanged('${escapeHtml(code)}', 'hours')">
                </td>
                <td>
                    <select class="ext-dt-input dt-inp-running" data-code="${escapeHtml(code)}"
                            onchange="recalcAllMetrics()">
                        <option value="0" ${item.def_running == 0 ? 'selected' : ''}>0</option>
                        <option value="1" ${item.def_running == 1 ? 'selected' : ''}>1</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="1" min="0" max="10" class="ext-dt-input dt-inp-workers"
                           data-code="${escapeHtml(code)}" value="${item.def_workers}"
                           oninput="recalcAllMetrics()">
                </td>
                <td>
                    <input type="number" step="1" min="0" max="10" class="ext-dt-input dt-inp-support"
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

    // Tự động cộng tổng cho nhóm cha 300 (300-1..300-9) và 308 (308-1..308-11) giống hệt công thức Excel
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

function onProductionTypeOrProductChanged() {
    const prodType = document.getElementById('f_production_type').value;
    const prodCode = document.getElementById('f_product_code').value.trim().toUpperCase();

    // 1. Tính Size ống (H10)
    const pipeSize = extractPipeSizeJs(prodCode);
    document.getElementById('f_pipe_size').value = pipeSize;

    // 2. Tra Mã CTSX (C10) theo đúng công thức Sheet Input:
    // =IF(B10="Hàng điều chỉnh","DC",IF(OR(B10="Hàng bù",B10="Hàng sản xuất"),VLOOKUP(G10,List!M:N,2,0),FALSE))
    const ctsxEl = document.getElementById('f_mfg_order_code');
    if (prodType === 'Hàng điều chỉnh') {
        ctsxEl.value = 'DC';
    } else if (prodCode && masterData.products && masterData.products[prodCode]) {
        ctsxEl.value = masterData.products[prodCode];
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
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_coil_m) : 0;

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
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_coil_m) : 0;

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
    const stdCoilM = sizeInfo ? parseFloat(sizeInfo.std_coil_m) : 0;

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

        // Bỏ qua 2 dòng tổng nhóm 300 & 308 khi cộng tổng toàn bảng để không bị tính trùng 2 lần
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
    // Công thức Sheet Input:
    // V6 = (W10 / R11) / 60 + AL56
    // W6 = (W10 / R11) / (AK10 * 60)
    const speedMpm = parseFloat(document.getElementById('f_cycle_time').value) || 0;
    const runtimeH = parseFloat(document.getElementById('f_total_runtime').value) || 0;

    const netRunHours = speedMpm > 0 ? ((finM / speedMpm) / 60) : 0;
    const actualMachineH = netRunHours + machineDowntime;
    const efficiencyPct  = (speedMpm > 0 && runtimeH > 0)
        ? (((finM / speedMpm) / (runtimeH * 60)) * 100)
        : 0;

    // 7. Cập nhật thanh KPI Sticky Bar
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
        txtMch.textContent = `KT TG Máy: ${actualMachineH.toFixed(2)}h / Chuẩn [${minMch.toFixed(1)}h - ${maxMch.toFixed(1)}h]`;
    } else if (actualMachineH >= minMch && actualMachineH <= maxMch) {
        badgeMch.className = 'ext-check-badge ext-check-ok';
        txtMch.textContent = `KT TG Máy ĐẠT: ${actualMachineH.toFixed(2)}h (Chuẩn ${minMch.toFixed(1)}-${maxMch.toFixed(1)}h)`;
    } else {
        badgeMch.className = 'ext-check-badge ext-check-err';
        txtMch.textContent = `Cảnh báo TG Máy: ${actualMachineH.toFixed(2)}h (Ngoài ${minMch.toFixed(1)}-${maxMch.toFixed(1)}h)`;
    }

    // 9. Kiểm tra điều kiện F53 (TG con người AA6 >= 7.2h)
    const badgeHum = document.getElementById('badgeCheckHumanTime');
    const txtHum   = document.getElementById('txtCheckHumanTime');
    if (humanTime >= 7.2) {
        badgeHum.className = 'ext-check-badge ext-check-ok';
        txtHum.textContent = `KT TG Người ĐẠT: ${humanTime.toFixed(2)}h (>= 7.2h)`;
    } else {
        badgeHum.className = 'ext-check-badge ext-check-warn';
        txtHum.textContent = `TG Người: ${humanTime.toFixed(2)}h / 7.20h`;
    }
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
        mold_code: document.getElementById('f_mold_code').value.trim() || '1',
        spider_code: document.getElementById('f_spider_code').value.trim() || '1',
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
    const btnBot = document.getElementById('btnBottomSaveReport');
    if (btnTop) btnTop.disabled = true;
    if (btnBot) btnBot.disabled = true;

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
        if (btnBot) btnBot.disabled = false;
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

        onProductionTypeOrProductChanged();

        if (d.mfg_order_code) {
            document.getElementById('f_mfg_order_code').value = d.mfg_order_code;
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
        document.getElementById('f_mold_code').value = d.mold_code || '1';
        document.getElementById('f_spider_code').value = d.spider_code || '1';

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
        clearAllDowntimeInputs();
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
            document.getElementById('lblBottomSaveBtn').textContent = `CẬP NHẬT BÁO CÁO #${d.id} (CTRL+S)`;
        } else {
            banner.style.display = 'none';
            document.getElementById('lblTopSaveBtn').textContent = 'Ghi Nhận Báo Cáo';
            document.getElementById('lblBottomSaveBtn').textContent = 'LƯU BÁO CÁO SẢN XUẤT (CTRL+S)';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (err) {
        alert('Không thể nạp báo cáo: ' + err.message);
    }
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
    document.getElementById('lblTopSaveBtn').textContent = 'Ghi Nhận Báo Cáo';
    document.getElementById('lblBottomSaveBtn').textContent = 'LƯU BÁO CÁO SẢN XUẤT (CTRL+S)';

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
    clearAllDowntimeInputs();
    isManualFinishedKg = false;
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
                    <td colspan="17" style="text-align:center; padding:24px; color:var(--dx-text-muted);">
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
                            <button type="button" class="app-btn app-btn-primary" style="height:26px; padding:0 8px; font-size:11px;"
                                    onclick="loadReportForEdit(${r.id}, false)" title="Sửa báo cáo #${r.id}">
                                <span class="material-icons" style="font-size:14px;">edit</span> Sửa
                            </button>
                            <button type="button" class="app-btn app-btn-secondary" style="height:26px; padding:0 6px; font-size:11px;"
                                    onclick="loadReportForEdit(${r.id}, true)" title="Nhân bản thành phiếu mới">
                                <span class="material-icons" style="font-size:14px;">content_copy</span>
                            </button>
                            <button type="button" class="app-btn app-btn-danger" style="height:26px; padding:0 6px; font-size:11px;"
                                    onclick="deleteManualReport(${r.id}, '${escapeHtml(r.production_order_code || '')}')" title="Xóa báo cáo">
                                <span class="material-icons" style="font-size:14px;">delete</span>
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
                        <td style="font-family:monospace; font-size:11.5px; color:var(--dx-text-muted);">${escapeHtml(r.production_order_code || '')}</td>
                    </tr>
                `;
            }).join('');
        }

        // Render phân trang
        const pg = json.pagination || { current_page: 1, total_pages: 1, total_records: rows.length };
        document.getElementById('recentPaginationInfo').textContent =
            `Tổng cộng ${pg.total_records} báo cáo • Trang ${pg.current_page}/${pg.total_pages}`;

        const btnWrap = document.getElementById('recentPaginationBtns');
        let pgHtml = '';
        for (let p = 1; p <= Math.min(pg.total_pages, 10); p++) {
            const active = p === pg.current_page ? 'app-btn-primary' : 'app-btn-secondary';
            pgHtml += `<button type="button" class="app-btn ${active}" style="height:28px; padding:0 10px; font-size:12px;" onclick="loadRecentReports(${p})">${p}</button>`;
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
 * Xuất nhanh file Excel chuẩn (DATA_PLASTIC & DATA_TIME) theo bộ lọc hiện tại
 */
function exportStandardExcelQuick(sheetMode = 'both') {
    const mode   = document.getElementById('lst_mode').value;
    const month  = document.getElementById('lst_month').value;
    const startD = document.getElementById('lst_start_date').value;
    const endD   = document.getElementById('lst_end_date').value;
    const shift  = document.getElementById('lst_shift').value;
    const source = document.getElementById('lst_source').value;
    const search = document.getElementById('lst_search').value.trim();

    const params = new URLSearchParams({
        action: 'export_excel',
        sheet: sheetMode,
        format: 'xlsx',
        mode: mode,
        month: month,
        start_date: startD,
        end_date: endD,
        shift: shift,
        data_source: source,
        search: search
    });

    window.location.href = `${EXTRUSION_MANUAL_API}?${params.toString()}`;
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
        } else if (e.key === 'Enter' && e.target.matches('.ext-input, .ext-dt-input')) {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('#extrusionManualForm .ext-input:not([readonly]), #extrusionManualForm .ext-dt-input:not([readonly])'))
                .filter(el => el.offsetParent !== null);
            const idx = inputs.indexOf(e.target);
            if (idx >= 0 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
                if (inputs[idx + 1].select) inputs[idx + 1].select();
            }
        }
    });
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

