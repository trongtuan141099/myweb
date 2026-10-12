<?php
/**
 * MODULE TIỆN ÍCH: TRANG TRA CỨU & XUẤT EXCEL DỮ LIỆU THỰC TÍCH ĐÙN (DATA_PLASTIC & DATA_TIME)
 * Hỗ trợ tra cứu đa điều kiện, đối chiếu song song 2 cấu trúc Sheet chuẩn:
 *   1. DATA_PLASTIC (40 cột dữ liệu thực tích sản xuất)
 *   2. DATA_TIME (16 cột dữ liệu thời gian dừng máy & thao tác)
 * Hỗ trợ chỉnh sửa (Edit) bản ghi và xuất file Excel (.xlsx / .csv) đúng chuẩn Format_Báo Cáo Đùn 7.2026.xlsm
 */
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
?>
<style>
.ext-lookup-page {
    --lk-border: var(--dx-border, #cbd5e1);
    --lk-card-bg: var(--dx-card-bg, #ffffff);
    --lk-bg-soft: #f8fafc;
}
[data-theme="dark"] .ext-lookup-page {
    --lk-border: #334155;
    --lk-card-bg: #1e293b;
    --lk-bg-soft: #0f172a;
}

/* KPI Cards */
.lk-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.lk-kpi-card {
    background: var(--lk-card-bg);
    border: 1px solid var(--lk-border);
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
}
.lk-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lk-kpi-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--dx-text-muted, #64748b);
}
.lk-kpi-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--dx-text-main, #0f172a);
    line-height: 1.2;
}
.lk-kpi-sub {
    font-size: 11px;
    color: var(--dx-text-muted, #64748b);
}

/* Sheet Switcher Tabs (DATA_PLASTIC vs DATA_TIME) */
.lk-sheet-tabs {
    display: inline-flex;
    background: var(--lk-bg-soft);
    border: 1px solid var(--lk-border);
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}
.lk-sheet-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 16px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 800;
    border: none;
    background: transparent;
    color: var(--dx-text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.lk-sheet-tab.active {
    background: #0284c7;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
}

/* Bảng dữ liệu chuẩn Excel 40 cột / 16 cột */
.lk-table-container {
    max-height: 600px;
    overflow: auto;
    border: 1px solid var(--lk-border);
    border-radius: 10px;
    background: var(--lk-card-bg);
}
.lk-excel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.lk-excel-table thead th {
    position: sticky;
    top: 0;
    z-index: 15;
    background: #0f172a;
    color: #ffffff;
    padding: 8px 10px;
    font-weight: 700;
    text-align: center;
    border-right: 1px solid #334155;
    border-bottom: 2px solid #0284c7;
    white-space: nowrap;
}
.lk-excel-table thead tr.jp-en-subhead th {
    top: 34px;
    z-index: 14;
    background: #1e293b;
    color: #94a3b8;
    font-size: 10.5px;
    font-weight: 600;
    padding: 4px 8px;
}
.lk-excel-table tbody td {
    padding: 6px 10px;
    border-bottom: 1px solid var(--lk-border);
    border-right: 1px solid rgba(148, 163, 184, 0.2);
    white-space: nowrap;
    vertical-align: middle;
}
.lk-excel-table tbody tr:hover {
    background: rgba(2, 132, 199, 0.06);
}
.src-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 5px;
    font-size: 10.5px;
    font-weight: 800;
}
.src-manual { background: #dcfce7; color: #15803d; }
.src-excel  { background: #e0f2fe; color: #0369a1; }
.src-vn     { background: #f3e8ff; color: #7e22ce; }
[data-theme="dark"] .src-manual { background: rgba(22,163,74,0.2); color: #4ade80; }
[data-theme="dark"] .src-excel  { background: rgba(2,132,199,0.2); color: #38bdf8; }
[data-theme="dark"] .src-vn     { background: rgba(147,51,234,0.2); color: #c084fc; }
</style>

<div class="app-page-wrapper ext-lookup-page">
    <!-- HEADER & NÚT XUẤT EXCEL CHUẨN -->
    <div class="app-page-header" style="margin-bottom:14px;">
        <div>
            <h1 class="app-page-title" style="display:flex; align-items:center; gap:10px;">
                <span class="material-icons" style="color:#0284c7;">table_view</span>
                <span>Tra Cứu &amp; Xuất Excel Dữ Liệu Thực Tích Đùn (DATA_PLASTIC &amp; DATA_TIME)</span>
            </h1>
            <p class="app-page-subtitle">
                Tra cứu tổng hợp dữ liệu thực tích đã nhập • Xuất báo cáo chuẩn cấu trúc 2 Sheet <code>DATA_PLASTIC</code> (40 cột) và <code>DATA_TIME</code> (16 cột)
            </p>
        </div>

        <div class="app-page-actions" style="display:flex; flex-wrap:wrap; gap:8px;">
            <?php if (!$isLoggedIn): ?>
            <a href="index.php?mainpage=authentication&subpage=login" class="app-btn app-btn-secondary">
                <span class="material-icons">login</span> Màn Hình Đăng Nhập
            </a>
            <?php endif; ?>

            <a href="index.php?mainpage=utilities&subpage=extrusion_manual_input" class="app-btn app-btn-primary">
                <span class="material-icons">add_circle</span> Nhập Báo Cáo Thủ Công
            </a>

            <button type="button" class="app-btn app-btn-success" onclick="exportActualExcel('both', 'xlsx')" title="Xuất 1 file Excel (.xlsx) gồm cả 2 Sheet DATA_PLASTIC và DATA_TIME">
                <span class="material-icons">download</span> Xuất Excel Chuẩn (2 Sheet: DATA_PLASTIC + DATA_TIME)
            </button>

            <button type="button" class="app-btn app-btn-secondary" onclick="exportActualExcel('DATA_PLASTIC', 'xlsx')" title="Chỉ xuất Sheet DATA_PLASTIC (.xlsx)">
                <span class="material-icons">fact_check</span> Xuất DATA_PLASTIC (.xlsx)
            </button>

            <button type="button" class="app-btn app-btn-secondary" onclick="exportActualExcel('DATA_TIME', 'xlsx')" title="Chỉ xuất Sheet DATA_TIME (.xlsx)">
                <span class="material-icons">timer</span> Xuất DATA_TIME (.xlsx)
            </button>
        </div>
    </div>

    <!-- BỘ LỌC ĐA TIÊU CHÍ -->
    <div class="app-card" style="padding:14px 16px; margin-bottom:16px; border:1px solid var(--lk-border);">
        <div class="row g-2 align-items-end">
            <!-- Chế độ thời gian -->
            <div class="col-6 col-md-2">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Khoảng thời gian</label>
                <select id="flt_mode" class="app-form-select w-100" style="height:36px;" onchange="onLookupModeChange()">
                    <option value="all" selected>Tất cả thời gian</option>
                    <option value="month">Theo tháng</option>
                    <option value="range">Từ ngày - Đến ngày</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-none" id="wrap_flt_month">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Tháng SX</label>
                <input type="month" id="flt_month" class="app-form-control w-100" style="height:36px;" value="<?= date('Y-m') ?>" onchange="fetchLookupData(1)">
            </div>

            <div class="col-6 col-md-2 d-none" id="wrap_flt_start">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Từ ngày</label>
                <input type="date" id="flt_start" class="app-form-control w-100" style="height:36px;" value="<?= date('Y-m-01') ?>" onchange="fetchLookupData(1)">
            </div>

            <div class="col-6 col-md-2 d-none" id="wrap_flt_end">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Đến ngày</label>
                <input type="date" id="flt_end" class="app-form-control w-100" style="height:36px;" value="<?= date('Y-m-t') ?>" onchange="fetchLookupData(1)">
            </div>

            <!-- Ca làm việc -->
            <div class="col-6 col-md-1">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Ca</label>
                <select id="flt_shift" class="app-form-select w-100" style="height:36px;" onchange="fetchLookupData(1)">
                    <option value="ALL">Tất cả</option>
                    <option value="Ca 1">Ca 1</option>
                    <option value="Ca 2">Ca 2</option>
                    <option value="Ca 3">Ca 3</option>
                    <option value="Ca HC">Ca HC</option>
                </select>
            </div>

            <!-- Máy đùn -->
            <div class="col-6 col-md-1">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Máy đùn</label>
                <select id="flt_device" class="app-form-select w-100" style="height:36px;" onchange="fetchLookupData(1)">
                    <option value="ALL">Tất cả</option>
                    <?php for ($m = 1; $m <= 24; $m++): $mc = sprintf('PL%02d', $m); ?>
                    <option value="<?= $mc ?>"><?= $mc ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Size ống -->
            <div class="col-6 col-md-1">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Size ống</label>
                <select id="flt_size" class="app-form-select w-100" style="height:36px;" onchange="fetchLookupData(1)">
                    <option value="ALL">Tất cả</option>
                    <option value="TU0425">TU0425</option>
                    <option value="TU0604">TU0604</option>
                    <option value="TU0805">TU0805</option>
                    <option value="TU1065">TU1065</option>
                    <option value="TU1208">TU1208</option>
                    <option value="TU1610">TU1610</option>
                    <option value="TIUB01">TIUB01</option>
                    <option value="TIUB07">TIUB07</option>
                    <option value="TIUB11">TIUB11</option>
                    <option value="TIUB13">TIUB13</option>
                </select>
            </div>

            <!-- Loại sản xuất -->
            <div class="col-6 col-md-2">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Loại sản xuất</label>
                <select id="flt_prod_type" class="app-form-select w-100" style="height:36px;" onchange="fetchLookupData(1)">
                    <option value="ALL">Tất cả loại SX</option>
                    <option value="SX">Hàng sản xuất (SX)</option>
                    <option value="BU">Hàng bù (BU)</option>
                    <option value="DC">Hàng điều chỉnh (DC)</option>
                </select>
            </div>

            <!-- Nguồn dữ liệu -->
            <div class="col-6 col-md-2">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Nguồn dữ liệu</label>
                <select id="flt_source" class="app-form-select w-100" style="height:36px;" onchange="fetchLookupData(1)">
                    <option value="ALL" selected>Tất cả nguồn</option>
                    <option value="MANUAL">Nhập thủ công (MANUAL)</option>
                    <option value="EXCEL">Tải lên Excel (EXCEL)</option>
                    <option value="VNSYSTEM">VNSystem (VNSYSTEM)</option>
                </select>
            </div>

            <!-- Tìm kiếm từ khóa -->
            <div class="col-8 col-md-2">
                <label class="app-form-label" style="font-size:11.5px; font-weight:700;">Tìm kiếm nhanh</label>
                <input type="text" id="flt_search" class="app-form-control w-100" style="height:36px;" placeholder="Mã SP, MSNV, Lot, Mã SX..." oninput="debounceLookupSearch()">
            </div>

            <div class="col-4 col-md-1">
                <button type="button" class="app-btn app-btn-primary w-100" style="height:36px;" onclick="fetchLookupData(1)" title="Làm mới dữ liệu">
                    <span class="material-icons" style="font-size:18px;">search</span> Lọc
                </button>
            </div>
        </div>
    </div>

    <!-- THẺ KPI TỔNG HỢP THEO BỘ LỌC -->
    <div class="lk-kpi-grid" id="lookupKpiGrid">
        <div class="lk-kpi-card">
            <div class="lk-kpi-icon" style="background:rgba(2,132,199,0.12); color:#0284c7;">
                <span class="material-icons">receipt_long</span>
            </div>
            <div>
                <div class="lk-kpi-title" id="kpi1_title">Tổng Số Bản Ghi</div>
                <div class="lk-kpi-val" id="kpi1_val">0</div>
                <div class="lk-kpi-sub" id="kpi1_sub">Phiếu báo cáo thực tích</div>
            </div>
        </div>
        <div class="lk-kpi-card">
            <div class="lk-kpi-icon" style="background:rgba(16,185,129,0.12); color:#059669;">
                <span class="material-icons">straighten</span>
            </div>
            <div>
                <div class="lk-kpi-title" id="kpi2_title">Tổng Thành Phẩm (m)</div>
                <div class="lk-kpi-val" id="kpi2_val">0 m</div>
                <div class="lk-kpi-sub" id="kpi2_sub">Sản lượng mét ống đạt</div>
            </div>
        </div>
        <div class="lk-kpi-card">
            <div class="lk-kpi-icon" style="background:rgba(37,99,235,0.12); color:#2563eb;">
                <span class="material-icons">scale</span>
            </div>
            <div>
                <div class="lk-kpi-title" id="kpi3_title">KL Thành Phẩm (kg)</div>
                <div class="lk-kpi-val" id="kpi3_val">0.00 kg</div>
                <div class="lk-kpi-sub" id="kpi3_sub">Tổng TL liệu: 0.00 kg</div>
            </div>
        </div>
        <div class="lk-kpi-card">
            <div class="lk-kpi-icon" style="background:rgba(245,158,11,0.12); color:#d97706;">
                <span class="material-icons">warning_amber</span>
            </div>
            <div>
                <div class="lk-kpi-title" id="kpi4_title">Phế Phẩm NG + Cứng</div>
                <div class="lk-kpi-val" id="kpi4_val">0.00 kg</div>
                <div class="lk-kpi-sub" id="kpi4_sub">NG: 0 kg • Cứng: 0 kg</div>
            </div>
        </div>
        <div class="lk-kpi-card">
            <div class="lk-kpi-icon" style="background:rgba(139,92,246,0.12); color:#7c3aed;">
                <span class="material-icons">speed</span>
            </div>
            <div>
                <div class="lk-kpi-title" id="kpi5_title">Hiệu Suất &amp; Thời Gian</div>
                <div class="lk-kpi-val" id="kpi5_val">0.00%</div>
                <div class="lk-kpi-sub" id="kpi5_sub">Chạy: 0h • Dừng: 0h</div>
            </div>
        </div>
    </div>

    <!-- THANH CHUYỂN ĐỔI SHEET (DATA_PLASTIC / DATA_TIME) & CÔNG CỤ BẢNG -->
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
        <div class="lk-sheet-tabs">
            <button type="button" class="lk-sheet-tab active" id="tabBtnPlastic" onclick="switchLookupView('plastic')">
                <span class="material-icons" style="font-size:18px;">dataset</span>
                <span>1. Sheet DATA_PLASTIC (Thực Tích Sản Xuất - 40 Cột)</span>
            </button>
            <button type="button" class="lk-sheet-tab" id="tabBtnTime" onclick="switchLookupView('time')">
                <span class="material-icons" style="font-size:18px;">schedule</span>
                <span>2. Sheet DATA_TIME (Thời Gian Dừng Máy - 16 Cột)</span>
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:12px; color:var(--dx-text-muted);">Số dòng/trang:</span>
            <select id="flt_limit" class="app-form-select" style="width:80px; height:32px; font-size:12px;" onchange="fetchLookupData(1)">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>

            <button type="button" class="app-btn app-btn-secondary" style="height:32px; font-size:12px;" onclick="exportCurrentSheetCsv()">
                <span class="material-icons" style="font-size:16px;">csv</span> Xuất CSV Sheet Hiện Tại
            </button>
        </div>
    </div>

    <!-- KHUNG BẢNG DỮ LIỆU CHUẨN EXCEL -->
    <div class="lk-table-container">
        <table class="lk-excel-table" id="lookupMainTable">
            <thead id="lookupThead"></thead>
            <tbody id="lookupTbody"></tbody>
        </table>
    </div>

    <!-- PHÂN TRANG -->
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-top:12px; font-size:13px;">
        <div id="lookupPageInfo" style="font-weight:700; color:var(--dx-text-muted);">Đang tải dữ liệu...</div>
        <div id="lookupPageButtons" style="display:flex; gap:5px;"></div>
    </div>
</div>

<script>
const LOOKUP_API_URL = 'api/extrusion_manual_report.php';
let currentViewType = 'plastic'; // 'plastic' hoặc 'time'
let currentPage     = 1;
let searchDebounce  = null;

document.addEventListener('DOMContentLoaded', () => {
    renderLookupTableHeaders();
    fetchLookupData(1);
});

function onLookupModeChange() {
    const mode = document.getElementById('flt_mode').value;
    document.getElementById('wrap_flt_month').classList.toggle('d-none', mode !== 'month');
    document.getElementById('wrap_flt_start').classList.toggle('d-none', mode !== 'range');
    document.getElementById('wrap_flt_end').classList.toggle('d-none', mode !== 'range');
    fetchLookupData(1);
}

function debounceLookupSearch() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchLookupData(1), 300);
}

function switchLookupView(viewType) {
    currentViewType = viewType;
    document.getElementById('tabBtnPlastic').classList.toggle('active', viewType === 'plastic');
    document.getElementById('tabBtnTime').classList.toggle('active', viewType === 'time');
    renderLookupTableHeaders();
    fetchLookupData(1);
}

/**
 * Render tiêu đề cột song ngữ (Việt / Nhật / Anh) bám sát 100% Sheet DATA_PLASTIC (40 cột) và DATA_TIME (16 cột)
 */
function renderLookupTableHeaders() {
    const thead = document.getElementById('lookupThead');
    if (currentViewType === 'plastic') {
        thead.innerHTML = `
            <tr>
                <th>Thao tác</th>
                <th>Nguồn</th>
                <th>Ngày Nhập</th>
                <th>Ngày SX</th>
                <th>Mã nhân viên</th>
                <th>Tên nhân viên</th>
                <th>Ca</th>
                <th>Mã CTSX</th>
                <th>Mã sản phẩm</th>
                <th>Size</th>
                <th>Công đoạn</th>
                <th>Mã thiết bị</th>
                <th>Thành phẩm (m)</th>
                <th>Thành phẩm (Kg)</th>
                <th>Số lượng NG (kg)</th>
                <th>Cục cứng (kg)</th>
                <th>Tổng trọng lượng</th>
                <th>Mã nguyên liệu</th>
                <th>Số lần nghiền</th>
                <th>Mã kiện nghiền</th>
                <th>Lot in</th>
                <th>Tổng TG dừng máy</th>
                <th>Tổng TG chạy máy</th>
                <th>Cycle time</th>
                <th>Hiệu suất máy</th>
                <th>Mã khuôn</th>
                <th>Mã spider</th>
                <th>Mã SX</th>
                <th>Thử nghiệm</th>
                <th>Loại vật liệu</th>
                <th>KL nguyên liệu NG</th>
                <th>Lot nguyên liệu NG</th>
                <th>HDPE 6200</th>
                <th>Lio clean</th>
                <th>Ti clean</th>
                <th>Bobin PL7-3</th>
                <th>Số mét PL7-3</th>
                <th>Bobin PL4-7</th>
                <th>Số mét PL4-7</th>
                <th>Loại máy in</th>
                <th>Mực in</th>
                <th>Số máy chờ</th>
                <th>Số bobin</th>
            </tr>
            <tr class="jp-en-subhead">
                <th>Action</th>
                <th>Source</th>
                <th>入力日 / Input Date</th>
                <th>製造日 / Prod Date</th>
                <th>社員番号 / Emp ID</th>
                <th>氏名 / Emp Name</th>
                <th>シフト / Shift</th>
                <th>指令コード / Directive</th>
                <th>品番 / Product Code</th>
                <th>Pipe Size</th>
                <th>工程 / Stages</th>
                <th>管理№ / Machine</th>
                <th>完成品 / Good (m)</th>
                <th>完成品 / Good (kg)</th>
                <th>不良数 / Defect</th>
                <th>ダンゴ / Hard Waste</th>
                <th>総重量 / Total Wt</th>
                <th>材料コード / Material</th>
                <th>粉砕回数 / Crush</th>
                <th>粉砕ロット / Crush Lot</th>
                <th>Lot In</th>
                <th>停止時間 / Stop (h)</th>
                <th>稼働時間 / Run (h)</th>
                <th>Mpm</th>
                <th>稼働率 / Efficiency</th>
                <th>金型 / Mold</th>
                <th>Spider</th>
                <th>Production Code</th>
                <th>Test</th>
                <th>Material Type</th>
                <th>Mat NG (kg)</th>
                <th>Mat NG Lot</th>
                <th>HDPE</th>
                <th>Lio</th>
                <th>Ti</th>
                <th>PL7-3 Qty</th>
                <th>PL7-3 (m)</th>
                <th>PL4-7 Qty</th>
                <th>PL4-7 (m)</th>
                <th>Printer</th>
                <th>Ink</th>
                <th>Wait Mch</th>
                <th>Total Bobbin</th>
            </tr>
        `;
    } else {
        thead.innerHTML = `
            <tr>
                <th>Thao tác</th>
                <th>Ngày Nhập</th>
                <th>Ngày SX</th>
                <th>Mã nhân viên</th>
                <th>Tên nhân viên</th>
                <th>Ca</th>
                <th>Mã CTSX</th>
                <th>Mã sản phẩm</th>
                <th>Công đoạn</th>
                <th>Mã thiết bị</th>
                <th>Mã lỗi</th>
                <th>Mô tả hạng mục dừng máy</th>
                <th>Số lần</th>
                <th>Thời gian (h)</th>
                <th>Giờ:Phút</th>
                <th>Máy đang chạy</th>
                <th>Số người</th>
                <th>Số người hỗ trợ</th>
                <th>Mã SX</th>
            </tr>
            <tr class="jp-en-subhead">
                <th>Action</th>
                <th>入力日 / Input Date</th>
                <th>製造日 / Production Date</th>
                <th>社員番号 / Employee ID</th>
                <th>氏名 / Employee Name</th>
                <th>シフト / Shifts</th>
                <th>指令コード / Directive Code</th>
                <th>品番 / Product Code</th>
                <th>工程 / Stages</th>
                <th>管理№ / Machine Code</th>
                <th>Downtime Code</th>
                <th>Downtime Description</th>
                <th>Occurrences</th>
                <th>Duration (Hours)</th>
                <th>HH:MM</th>
                <th>Machine Running (1/0)</th>
                <th>Workers</th>
                <th>Support Workers</th>
                <th>Production Code</th>
            </tr>
        `;
    }
}

function getLookupFilterParams() {
    return {
        mode: document.getElementById('flt_mode').value,
        month: document.getElementById('flt_month').value,
        start_date: document.getElementById('flt_start').value,
        end_date: document.getElementById('flt_end').value,
        shift: document.getElementById('flt_shift').value,
        device_code: document.getElementById('flt_device').value,
        pipe_size: document.getElementById('flt_size').value,
        production_type: document.getElementById('flt_prod_type').value,
        data_source: document.getElementById('flt_source').value,
        search: document.getElementById('flt_search').value.trim(),
        limit: document.getElementById('flt_limit').value
    };
}

async function fetchLookupData(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('lookupTbody');
    const colCount = currentViewType === 'plastic' ? 43 : 19;
    tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center; padding:28px; color:var(--dx-text-muted);">Đang truy vấn dữ liệu thực tích...</td></tr>`;

    const flt = getLookupFilterParams();
    const params = new URLSearchParams({
        action: 'list_reports',
        view_type: currentViewType,
        page: page,
        ...flt
    });

    try {
        const res = await fetch(`${LOOKUP_API_URL}?${params.toString()}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Lỗi tải dữ liệu');

        updateLookupKpiCards(json.summary || {});
        renderLookupTableRows(json.data || []);
        renderLookupPagination(json.pagination || { current_page: 1, total_pages: 1, total_records: 0 });
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center; padding:28px; color:#dc2626;">Lỗi: ${escapeHtml(err.message)}</td></tr>`;
    }
}

function updateLookupKpiCards(s) {
    if (currentViewType === 'plastic') {
        const totalRec = parseInt(s.total_records || 0, 10);
        const finM     = parseFloat(s.total_finished_m || 0);
        const finKg    = parseFloat(s.total_finished_kg || 0);
        const totKg    = parseFloat(s.total_weight_kg || 0);
        const ngKg     = parseFloat(s.total_ng_kg || 0);
        const hardKg   = parseFloat(s.total_hard_kg || 0);
        const runH     = parseFloat(s.total_runtime_h || 0);
        const stopH    = parseFloat(s.total_downtime_h || 0);
        const avgEff   = parseFloat(s.avg_efficiency || 0);

        document.getElementById('kpi1_title').textContent = 'Tổng Số Bản Ghi (DATA_PLASTIC)';
        document.getElementById('kpi1_val').textContent   = totalRec.toLocaleString('vi-VN');
        document.getElementById('kpi1_sub').textContent   = 'Bản ghi thực tích sản xuất';

        document.getElementById('kpi2_title').textContent = 'Tổng Thành Phẩm (m)';
        document.getElementById('kpi2_val').textContent   = finM.toLocaleString('vi-VN') + ' m';
        document.getElementById('kpi2_sub').textContent   = 'Chiều dài ống thành phẩm';

        document.getElementById('kpi3_title').textContent = 'KL Thành Phẩm (kg)';
        document.getElementById('kpi3_val').textContent   = finKg.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
        document.getElementById('kpi3_sub').textContent   = `Tổng TL liệu: ${totKg.toLocaleString('vi-VN', { maximumFractionDigits: 1 })} kg`;

        document.getElementById('kpi4_title').textContent = 'Phế Phẩm NG + Cục Cứng';
        document.getElementById('kpi4_val').textContent   = (ngKg + hardKg).toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
        document.getElementById('kpi4_sub').textContent   = `NG: ${ngKg.toFixed(1)} kg • Cứng: ${hardKg.toFixed(1)} kg`;

        document.getElementById('kpi5_title').textContent = 'Hiệu Suất TB & Thời Gian';
        document.getElementById('kpi5_val').textContent   = avgEff.toFixed(2) + '%';
        document.getElementById('kpi5_sub').textContent   = `Chạy: ${runH.toFixed(1)}h • Dừng: ${stopH.toFixed(1)}h`;
    } else {
        const totalRec  = parseInt(s.total_records || 0, 10);
        const totalOcc  = parseInt(s.total_occurrences || 0, 10);
        const totalHrs  = parseFloat(s.total_downtime_h || 0);
        const totalOrds = parseInt(s.total_orders || 0, 10);

        document.getElementById('kpi1_title').textContent = 'Tổng Dòng Dừng Máy (DATA_TIME)';
        document.getElementById('kpi1_val').textContent   = totalRec.toLocaleString('vi-VN');
        document.getElementById('kpi1_sub').textContent   = 'Bản ghi chi tiết DATA_TIME';

        document.getElementById('kpi2_title').textContent = 'Số Phiếu Sản Xuất (Mã SX)';
        document.getElementById('kpi2_val').textContent   = totalOrds.toLocaleString('vi-VN') + ' phiếu';
        document.getElementById('kpi2_sub').textContent   = 'Có phát sinh ghi nhận thời gian';

        document.getElementById('kpi3_title').textContent = 'Tổng Số Lần Phát Sinh';
        document.getElementById('kpi3_val').textContent   = totalOcc.toLocaleString('vi-VN') + ' lần';
        document.getElementById('kpi3_sub').textContent   = 'Tổng số lần dừng / thao tác';

        document.getElementById('kpi4_title').textContent = 'Tổng Thời Gian Ghi Nhận';
        document.getElementById('kpi4_val').textContent   = totalHrs.toFixed(2) + ' giờ';
        document.getElementById('kpi4_sub').textContent   = `Tương đương ${Math.round(totalHrs * 60).toLocaleString('vi-VN')} phút`;

        document.getElementById('kpi5_title').textContent = 'Trung Bình / Phiếu SX';
        document.getElementById('kpi5_val').textContent   = (totalOrds > 0 ? (totalHrs / totalOrds).toFixed(2) : '0.00') + ' h/phiếu';
        document.getElementById('kpi5_sub').textContent   = 'Thời gian dừng & thao tác TB';
    }
}

function renderLookupTableRows(rows) {
    const tbody = document.getElementById('lookupTbody');
    const colCount = currentViewType === 'plastic' ? 43 : 19;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center; padding:32px; color:var(--dx-text-muted);">Không có dữ liệu phù hợp với điều kiện lọc hiện tại.</td></tr>`;
        return;
    }

    if (currentViewType === 'plastic') {
        tbody.innerHTML = rows.map(r => {
            const src = r.data_source || 'EXCEL';
            const srcClass = src === 'MANUAL' ? 'src-manual' : (src === 'VNSYSTEM' ? 'src-vn' : 'src-excel');
            const effPct = parseFloat(r.machine_efficiency || 0).toFixed(2);
            const importDt = r.import_datetime || ((r.import_date || r.production_date) + ' 08:00:00');
            return `
                <tr>
                    <td style="text-align:center;">
                        <a href="index.php?mainpage=utilities&subpage=extrusion_manual_input&edit_id=${r.id}"
                           class="app-btn app-btn-primary" style="height:25px; padding:0 8px; font-size:11px; text-decoration:none;">
                            <span class="material-icons" style="font-size:13px;">edit</span> Sửa
                        </a>
                        <button type="button" class="app-btn app-btn-danger" style="height:25px; padding:0 6px; font-size:11px;"
                                onclick="deleteLookupRecord(${r.id}, '${escapeHtml(r.production_order_code || '')}')" title="Xóa bản ghi">
                            <span class="material-icons" style="font-size:13px;">delete</span>
                        </button>
                    </td>
                    <td style="text-align:center;"><span class="src-badge ${srcClass}">${escapeHtml(src)}</span></td>
                    <td>${escapeHtml(importDt)}</td>
                    <td style="font-weight:700; text-align:center;">${escapeHtml(r.production_date)}</td>
                    <td style="font-family:monospace; font-weight:700;">${escapeHtml(r.employee_code || '')}</td>
                    <td>${escapeHtml(r.employee_name || '')}</td>
                    <td style="text-align:center;">${escapeHtml(r.shift || '')}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.mfg_order_code || '')}</td>
                    <td style="font-weight:800; color:#0284c7;">${escapeHtml(r.product_code || '')}</td>
                    <td style="text-align:center; font-weight:700;">${escapeHtml(r.pipe_size || '')}</td>
                    <td>${escapeHtml(r.process_name || 'Extrusion')}</td>
                    <td style="text-align:center; font-weight:800;">${escapeHtml(r.device_code || '')}</td>
                    <td style="text-align:right; font-weight:800;">${parseFloat(r.finished_qty_m || 0).toLocaleString('vi-VN')}</td>
                    <td style="text-align:right; font-weight:700; color:#059669;">${parseFloat(r.finished_qty_kg || 0).toFixed(2)}</td>
                    <td style="text-align:right; color:#d97706;">${parseFloat(r.ng_qty_kg || 0).toFixed(2)}</td>
                    <td style="text-align:right; color:#dc2626;">${parseFloat(r.hard_waste_qty_kg || 0).toFixed(2)}</td>
                    <td style="text-align:right; font-weight:800;">${parseFloat(r.total_weight_kg || 0).toFixed(2)}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.material_code || '')}</td>
                    <td style="text-align:center;">${r.regrind_count ?? 0}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.regrind_package_code || '')}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.lot_in || '')}</td>
                    <td style="text-align:right; color:#d97706;">${parseFloat(r.total_downtime || 0).toFixed(2)}</td>
                    <td style="text-align:right; font-weight:700;">${parseFloat(r.total_runtime || 0).toFixed(2)}</td>
                    <td style="text-align:center;">${r.cycle_time ?? 0}</td>
                    <td style="text-align:right; font-weight:800; color:#2563eb;">${effPct}%</td>
                    <td style="text-align:center;">${escapeHtml(r.mold_code || '')}</td>
                    <td style="text-align:center;">${escapeHtml(r.spider_code || '')}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.production_order_code || '')}</td>
                    <td style="text-align:center;">${parseInt(r.is_test, 10) === 1 ? '1' : ''}</td>
                    <td>${escapeHtml(r.material_type || '')}</td>
                    <td style="text-align:right;">${parseFloat(r.material_ng_qty || 0).toFixed(2)}</td>
                    <td>${escapeHtml(r.lot_material_ng || '')}</td>
                    <td style="text-align:right;">${parseFloat(r.hdpe_qty || 0)}</td>
                    <td style="text-align:right;">${parseFloat(r.lio_clean_qty || 0)}</td>
                    <td style="text-align:right;">${parseFloat(r.ti_clean_qty || 0)}</td>
                    <td style="text-align:center;">${r.bobbin_pl7_3_count ?? 0}</td>
                    <td style="text-align:right;">${parseFloat(r.bobbin_pl7_3_meters || 0)}</td>
                    <td style="text-align:center;">${r.bobbin_pl4_7_count ?? 0}</td>
                    <td style="text-align:right;">${parseFloat(r.bobbin_pl4_7_meters || 0)}</td>
                    <td style="text-align:center;">${escapeHtml(r.printer_type || '')}</td>
                    <td style="text-align:center;">${escapeHtml(r.ink_type || '')}</td>
                    <td style="text-align:center;">${r.waiting_machine_count ?? 0}</td>
                    <td style="text-align:right; font-weight:700;">${parseFloat(r.total_bobbin_calc || 0).toFixed(2)}</td>
                </tr>
            `;
        }).join('');
    } else {
        tbody.innerHTML = rows.map(r => {
            const editBtn = r.actual_log_id
                ? `<a href="index.php?mainpage=utilities&subpage=extrusion_manual_input&edit_id=${r.actual_log_id}" class="app-btn app-btn-primary" style="height:24px; padding:0 8px; font-size:11px; text-decoration:none;"><span class="material-icons" style="font-size:13px;">edit</span> Phiếu gốc</a>`
                : `<span style="color:var(--dx-text-muted); font-size:11px;">--</span>`;
            const isSum = (r.downtime_code === '300' || r.downtime_code === '308');
            return `
                <tr style="${isSum ? 'background:rgba(59,130,246,0.08); font-weight:700;' : ''}">
                    <td style="text-align:center;">${editBtn}</td>
                    <td>${escapeHtml(r.import_datetime || '')}</td>
                    <td style="text-align:center; font-weight:700;">${escapeHtml(r.production_date || '')}</td>
                    <td style="font-family:monospace; font-weight:700;">${escapeHtml(r.employee_code || '')}</td>
                    <td>${escapeHtml(r.employee_name || '')}</td>
                    <td style="text-align:center;">${escapeHtml(r.shift || '')}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.mfg_order_code || '')}</td>
                    <td style="font-weight:800; color:#0284c7;">${escapeHtml(r.product_code || '')}</td>
                    <td>${escapeHtml(r.process_name || 'Extrusion')}</td>
                    <td style="text-align:center; font-weight:800;">${escapeHtml(r.device_code || '')}</td>
                    <td style="text-align:center; font-family:monospace; font-weight:800; color:#dc2626;">${escapeHtml(r.downtime_code || '')}</td>
                    <td>${escapeHtml(r.downtime_name || '')}</td>
                    <td style="text-align:center; font-weight:700;">${r.occurrences ?? 0}</td>
                    <td style="text-align:right; font-weight:800; color:#0284c7;">${parseFloat(r.duration_hours || 0).toFixed(4)}</td>
                    <td style="text-align:center; font-family:monospace;">${escapeHtml(r.duration_hm || '00:00')}</td>
                    <td style="text-align:center;">${r.is_machine_running ?? 0}</td>
                    <td style="text-align:center;">${r.worker_count ?? 0}</td>
                    <td style="text-align:center;">${r.support_worker_count ?? 0}</td>
                    <td style="font-family:monospace;">${escapeHtml(r.production_order_code || '')}</td>
                </tr>
            `;
        }).join('');
    }
}

function renderLookupPagination(pg) {
    document.getElementById('lookupPageInfo').textContent =
        `Hiển thị trang ${pg.current_page} / ${pg.total_pages} (Tổng cộng ${parseInt(pg.total_records || 0, 10).toLocaleString('vi-VN')} dòng)`;

    const wrap = document.getElementById('lookupPageButtons');
    let html = '';
    const maxButtons = 8;
    let startP = Math.max(1, pg.current_page - 3);
    let endP   = Math.min(pg.total_pages, startP + maxButtons - 1);
    if (endP - startP < maxButtons - 1) {
        startP = Math.max(1, endP - maxButtons + 1);
    }

    if (pg.current_page > 1) {
        html += `<button type="button" class="app-btn app-btn-secondary" style="height:30px; padding:0 10px;" onclick="fetchLookupData(${pg.current_page - 1})">« Trước</button>`;
    }
    for (let p = startP; p <= endP; p++) {
        const cls = p === pg.current_page ? 'app-btn-primary' : 'app-btn-secondary';
        html += `<button type="button" class="app-btn ${cls}" style="height:30px; padding:0 11px;" onclick="fetchLookupData(${p})">${p}</button>`;
    }
    if (pg.current_page < pg.total_pages) {
        html += `<button type="button" class="app-btn app-btn-secondary" style="height:30px; padding:0 10px;" onclick="fetchLookupData(${pg.current_page + 1})">Sau »</button>`;
    }
    wrap.innerHTML = html;
}

async function deleteLookupRecord(id, code) {
    if (!confirm(`Bạn có chắc chắn muốn xóa bản ghi thực tích #${id} (${code})?`)) return;
    try {
        const res = await fetch(`${LOOKUP_API_URL}?action=delete_report`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Lỗi xóa bản ghi');
        fetchLookupData(currentPage);
    } catch (err) {
        alert('Lỗi: ' + err.message);
    }
}

/**
 * Xuất file Excel (.xlsx / .csv) theo đúng chuẩn DATA_PLASTIC và DATA_TIME
 */
function exportActualExcel(sheetMode = 'both', format = 'xlsx') {
    const flt = getLookupFilterParams();
    const params = new URLSearchParams({
        action: 'export_excel',
        sheet: sheetMode,
        format: format,
        ...flt
    });
    window.location.href = `${LOOKUP_API_URL}?${params.toString()}`;
}

function exportCurrentSheetCsv() {
    const sheet = currentViewType === 'time' ? 'DATA_TIME' : 'DATA_PLASTIC';
    exportActualExcel(sheet, 'csv');
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

