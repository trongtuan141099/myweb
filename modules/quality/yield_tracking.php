<?php
// modules/quality/yield_tracking.php
// DX Plastic Group - Module Quản Lý Chất Lượng & Theo Dõi Tỉ Lệ Thành Phẩm (良品率)

require_once __DIR__ . '/../../core/check_permission.php';

checkAuth();
requirePermission('quality.view');

$canManageQuality = hasPermission(['quality.manage', 'admin']);
$canInvestigate   = hasPermission(['quality.investigate', 'quality.manage', 'admin']);

$currentYear  = intval(date('Y'));
$currentMonth = intval(date('m'));
?>

<style>
/* Quality Module Industrial Theme */
.quality-kpi-card {
  border-radius: var(--dx-radius-md, 8px);
  padding: 16px;
  background: var(--dx-bg-card, #ffffff);
  border: 1px solid var(--dx-border, #e2e8f0);
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.quality-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08);
}
.quality-kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.quality-kpi-val {
  font-size: 20px;
  font-weight: 800;
  font-family: var(--dx-font-mono, monospace);
  line-height: 1.2;
}
.quality-kpi-lbl {
  font-size: 12px;
  color: var(--dx-text-muted, #64748b);
  font-weight: 600;
  margin-top: 2px;
}

/* Tabs */
.quality-nav-tabs {
  display: flex;
  gap: 6px;
  border-bottom: 2px solid var(--dx-border, #e2e8f0);
  margin-bottom: 20px;
  overflow-x: auto;
  padding-bottom: 2px;
}
.quality-tab-btn {
  background: transparent;
  border: none;
  padding: 10px 18px;
  font-size: 13.5px;
  font-weight: 700;
  color: var(--dx-text-muted, #64748b);
  border-radius: 6px 6px 0 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.quality-tab-btn:hover {
  color: var(--dx-primary, #0284c7);
  background: var(--dx-bg-subtle, #f8fafc);
}
.quality-tab-btn.active {
  color: var(--dx-primary, #0284c7);
  background: var(--dx-bg-card, #ffffff);
}
.quality-tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--dx-primary, #0284c7);
  border-radius: 3px 3px 0 0;
}

/* Status Badges */
.badge-yield-pass { background-color: rgba(34, 197, 94, 0.15); color: #15803d; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-yield-warn { background-color: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3); }
.badge-yield-danger { background-color: rgba(239, 68, 68, 0.15); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.3); }

/* Matrix Table Styling */
.table-matrix-wrapper {
  overflow-x: auto;
  max-height: 520px;
}
.table-matrix th, .table-matrix td {
  padding: 6px 4px;
  font-size: 11px;
  text-align: center;
  border: 1px solid var(--dx-border, #cbd5e1);
  min-width: 32px;
}
.table-matrix th.sticky-col, .table-matrix td.sticky-col {
  position: sticky;
  left: 0;
  background: var(--dx-bg-card, #ffffff);
  z-index: 2;
  font-weight: bold;
  min-width: 90px;
  text-align: left;
  padding-left: 8px;
}
.cell-rate-pass { background: rgba(34, 197, 94, 0.18); color: #166534; font-weight: 700; }
.cell-rate-warn { background: rgba(245, 158, 11, 0.20); color: #92400e; font-weight: 700; }
.cell-rate-danger { background: rgba(239, 68, 68, 0.22); color: #991b1b; font-weight: 800; }

/* Filter Bar */
.filter-card-quality {
  background: var(--dx-bg-card, #ffffff);
  border: 1px solid var(--dx-border, #e2e8f0);
  border-radius: var(--dx-radius-md, 8px);
  padding: 14px 18px;
  margin-bottom: 20px;
}
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title d-flex align-items-center gap-2">
        <span class="material-icons text-primary" style="font-size: 28px;">query_stats</span>
        <span>THEO DÕI TỈ LỆ THÀNH PHẨM (良品率)</span>
      </h1>
      <p class="app-page-subtitle">Quản lý tỉ lệ thành phẩm xưởng ống nhựa TU, phân tích lỗi (A1-A5), đối soát benchmark và xử lý phiếu điều tra bất thường</p>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2 flex-wrap">
      <?php if ($canManageQuality): ?>
      <!-- Nút Thêm mới -->
      <button type="button" class="app-btn app-btn-primary" onclick="openAddRecordModal()">
        <span class="material-icons">add_circle</span> Thêm Lô Hàng
      </button>

      <!-- Nút Import Excel -->
      <button type="button" class="app-btn app-btn-secondary" onclick="openImportModal()" title="Import file Excel sản xuất (.xlsx / .xlsm / .csv) với Upsert thông minh">
        <span class="material-icons">upload_file</span> Import Excel (.xlsm)
      </button>

      <!-- Nút Cấu Hình Phân Loại Vật Liệu -->
      <button type="button" class="app-btn app-btn-outline" onclick="openMaterialRulesModal()" title="Cấu hình quy tắc phân loại nguyên vật liệu theo ký tự thứ 2 mã LOT">
        <span class="material-icons">category</span> Cấu Hình Vật Liệu
      </button>

      <!-- Nút Benchmark -->
      <button type="button" class="app-btn app-btn-outline" onclick="openBenchmarkModal()">
        <span class="material-icons">tune</span> Benchmark
      </button>
      <?php endif; ?>

      <!-- Dropdown Xuất Dữ Liệu Excel / CSV -->
      <div class="dropdown d-inline-block">
        <button class="app-btn app-btn-secondary dropdown-toggle" type="button" id="dropdownExportData" data-bs-toggle="dropdown" aria-expanded="false" title="Xuất dữ liệu đang lọc">
          <span class="material-icons">file_download</span> Xuất Dữ Liệu
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="dropdownExportData" style="min-width: 250px;">
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="#" id="btnExportExcelXlsx">
              <span class="material-icons text-success fs-5">table_view</span>
              <div>
                <div class="fw-bold">Xuất File Excel (.xlsx)</div>
                <div class="text-muted" style="font-size: 11px;">Chuẩn sheet データ (53 cột)</div>
              </div>
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="#" id="btnExportExcelCsv">
              <span class="material-icons text-primary fs-5">description</span>
              <div>
                <div class="fw-bold">Xuất File CSV (.csv)</div>
                <div class="text-muted" style="font-size: 11px;">Định dạng văn bản UTF-8</div>
              </div>
            </a>
          </li>
        </ul>
      </div>

      <!-- Nút Xuất PDF -->
      <button type="button" class="app-btn app-btn-outline text-danger border-danger-subtle" onclick="openPdfModal()" title="Xem trước và in báo cáo chuẩn A4 PDF">
        <span class="material-icons">picture_as_pdf</span> Báo Cáo PDF
      </button>
    </div>
  </div>

  <!-- 2. Thanh Bộ Lọc Đa Dạng (Universal Filter Bar) -->
  <div class="filter-card-quality">
    <div class="row g-2 align-items-end">
      <!-- Loại Ngày -->
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Loại Ngày:</label>
        <select id="filterDateType" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="komaki" selected>Ngày cuộn (小巻日)</option>
          <option value="extrusion">Ngày đùn (成形日)</option>
        </select>
      </div>

      <!-- Chế độ thời gian -->
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Kiểu thời gian:</label>
        <select id="filterPeriodMode" class="app-form-control app-form-control-sm" onchange="togglePeriodInputs()">
          <option value="day" selected>Theo khoảng ngày</option>
          <option value="month">Theo tháng / năm</option>
        </select>
      </div>

      <!-- Khoảng ngày (Date Range) -->
      <div class="col-md-2 col-sm-6" id="divPeriodDay">
        <label class="form-label small fw-bold text-muted mb-1">Từ ngày - Đến ngày:</label>
        <div class="input-group input-group-sm">
          <input type="date" id="filterDateFrom" class="app-form-control" onchange="triggerFilter()">
          <span class="input-group-text bg-light text-muted">-</span>
          <input type="date" id="filterDateTo" class="app-form-control" onchange="triggerFilter()">
        </div>
      </div>

      <!-- Theo tháng / năm (Month Select) -->
      <div class="col-md-2 col-sm-6" id="divPeriodMonth" style="display: none;">
        <label class="form-label small fw-bold text-muted mb-1">Tháng & Năm:</label>
        <div class="input-group input-group-sm">
          <select id="filterMonth" class="app-form-control" onchange="triggerFilter()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= ($m == 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
            <?php endfor; ?>
          </select>
          <select id="filterYear" class="app-form-control" onchange="triggerFilter()">
            <option value="2026" selected>2026</option>
            <option value="2025">2025</option>
          </select>
        </div>
      </div>

      <!-- Nhóm Vật Liệu (Nguyên sinh / Nghiền) -->
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Nhóm Vật Liệu:</label>
        <select id="filterMaterialGroup" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="">-- Tất cả vật liệu --</option>
          <option value="virgin">Nguyên sinh (Zin)</option>
          <option value="recycled">Nghiền (Tái sinh)</option>
        </select>
      </div>

      <!-- Máy Đùn -->
      <div class="col-md-1 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Máy đùn:</label>
        <select id="filterExtMachine" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="">Tất cả</option>
        </select>
      </div>

      <!-- Máy Cuộn -->
      <div class="col-md-1 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Máy cuộn:</label>
        <select id="filterKomMachine" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="">Tất cả</option>
        </select>
      </div>

      <!-- Kích cỡ (Size) -->
      <div class="col-md-1 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Size:</label>
        <select id="filterSize" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="">Tất cả</option>
        </select>
      </div>

      <!-- Đánh giá chất lượng -->
      <div class="col-md-1 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Đánh giá:</label>
        <select id="filterStatus" class="app-form-control app-form-control-sm" onchange="triggerFilter()">
          <option value="">Tất cả</option>
          <option value="pass">Đạt chuẩn</option>
          <option value="warning">Cảnh báo</option>
          <option value="danger">Bất thường</option>
        </select>
      </div>
    </div>
  </div>

  <!-- 3. Navigation Tabs Chính (4 Tabs) -->
  <div class="quality-nav-tabs">
    <button class="quality-tab-btn active" id="tabBtnDashboard" onclick="switchQualityTab('dashboard')">
      <span class="material-icons">dashboard</span>
      <span>Dashboard & Biểu Đồ</span>
    </button>
    <button class="quality-tab-btn" id="tabBtnMatrix" onclick="switchQualityTab('matrix')">
      <span class="material-icons">grid_on</span>
      <span>Ma Trận Tỉ Lệ (Sheet 良品率)</span>
    </button>
    <button class="quality-tab-btn" id="tabBtnRecords" onclick="switchQualityTab('records')">
      <span class="material-icons">table_rows</span>
      <span>Dữ Liệu Chi Tiết (Sheet データ)</span>
    </button>
    <button class="quality-tab-btn" id="tabBtnInvestigations" onclick="switchQualityTab('investigations')">
      <span class="material-icons">assignment_late</span>
      <span>Phiếu Yêu Cầu Điều Tra & Đối Ứng (Sheet Đối ứng)</span>
      <span class="badge bg-danger rounded-pill" id="badgePendingInv">0</span>
    </button>
  </div>

  <!-- =========================================================================
       TAB 1: DASHBOARD TRỰC QUAN & BIỂU ĐỒ APEXCHARTS
       ========================================================================= -->
  <div id="tabContentDashboard">
    <!-- Thẻ KPI -->
    <div class="row g-3 mb-4">
      <div class="col-md col-sm-6">
        <div class="quality-kpi-card">
          <div class="quality-kpi-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;">
            <span class="material-icons">inventory_2</span>
          </div>
          <div>
            <div class="quality-kpi-val" id="kpiTotalProduced">0</div>
            <div class="quality-kpi-lbl">Tổng Sản Lượng (Cuộn)</div>
          </div>
        </div>
      </div>

      <div class="col-md col-sm-6">
        <div class="quality-kpi-card">
          <div class="quality-kpi-icon" style="background: rgba(34, 197, 94, 0.12); color: #16a34a;">
            <span class="material-icons">check_circle</span>
          </div>
          <div>
            <div class="quality-kpi-val text-success" id="kpiTotalGood">0</div>
            <div class="quality-kpi-lbl">Thành Phẩm Đạt (Cuộn)</div>
          </div>
        </div>
      </div>

      <div class="col-md col-sm-6">
        <div class="quality-kpi-card">
          <div class="quality-kpi-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">
            <span class="material-icons">trending_up</span>
          </div>
          <div>
            <div class="quality-kpi-val" id="kpiYieldRate">0%</div>
            <div class="quality-kpi-lbl">Tỉ Lệ Thành Phẩm (Target: <span id="kpiBenchmark">98%</span>)</div>
          </div>
        </div>
      </div>

      <div class="col-md col-sm-6">
        <div class="quality-kpi-card">
          <div class="quality-kpi-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
            <span class="material-icons">highlight_off</span>
          </div>
          <div>
            <div class="quality-kpi-val text-danger" id="kpiDefectRate">0%</div>
            <div class="quality-kpi-lbl">Tỉ Lệ Phế Phẩm Lỗi (<span id="kpiTotalDefect">0</span> cuộn)</div>
          </div>
        </div>
      </div>

      <div class="col-md col-sm-6">
        <div class="quality-kpi-card" style="border-left: 3px solid #f59e0b;">
          <div class="quality-kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
            <span class="material-icons">warning_amber</span>
          </div>
          <div>
            <div class="quality-kpi-val text-warning" id="kpiDangerCount">0</div>
            <div class="quality-kpi-lbl">Lô Bất Thường (&lt; 95%)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Phân Loại Nguyên Vật Liệu (Vật liệu nguyên sinh vs Vật liệu nghiền) -->
    <div class="row g-3 mb-4">
      <!-- Thẻ Vật liệu nguyên sinh (Zin) -->
      <div class="col-md-6">
        <div class="app-card p-3" style="border-left: 4px solid #16a34a !important; background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success text-white px-2 py-1 fw-bold">VẬT LIỆU NGUYÊN SINH (ZIN)</span>
              <span class="small text-muted" id="matVirginLots">0 lô</span>
            </div>
            <span class="badge bg-white text-success border border-success-subtle fw-bold fs-6" id="matVirginRatio">0% sản lượng</span>
          </div>
          <div class="row g-2 text-center mt-1">
            <div class="col-4 border-end">
              <div class="small text-muted">Sản lượng</div>
              <div class="fw-bold fs-5 text-dark" id="matVirginProduced">0</div>
            </div>
            <div class="col-4 border-end">
              <div class="small text-muted">Tỉ lệ đạt</div>
              <div class="fw-bold fs-5 text-success" id="matVirginRate">0%</div>
            </div>
            <div class="col-4">
              <div class="small text-muted">Phế phẩm</div>
              <div class="fw-bold fs-5 text-danger" id="matVirginDefect">0</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Thẻ Vật liệu nghiền (Recycled) -->
      <div class="col-md-6">
        <div class="app-card p-3" style="border-left: 4px solid #0284c7 !important; background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%);">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary text-white px-2 py-1 fw-bold">VẬT LIỆU NGHIỀN (RECYCLED)</span>
              <span class="small text-muted" id="matRecycledLots">0 lô</span>
            </div>
            <span class="badge bg-white text-primary border border-primary-subtle fw-bold fs-6" id="matRecycledRatio">0% sản lượng</span>
          </div>
          <div class="row g-2 text-center mt-1">
            <div class="col-4 border-end">
              <div class="small text-muted">Sản lượng</div>
              <div class="fw-bold fs-5 text-dark" id="matRecycledProduced">0</div>
            </div>
            <div class="col-4 border-end">
              <div class="small text-muted">Tỉ lệ đạt</div>
              <div class="fw-bold fs-5 text-primary" id="matRecycledRate">0%</div>
            </div>
            <div class="col-4">
              <div class="small text-muted">Phế phẩm</div>
              <div class="fw-bold fs-5 text-danger" id="matRecycledDefect">0</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hàng Biểu Đồ 1: Xu hướng Tỉ lệ thành phẩm & Phân tích lỗi Pareto -->
    <div class="row g-3 mb-4">
      <div class="col-lg-8">
        <div class="app-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons text-primary fs-5">show_chart</span>
              <span>Xu Hướng Tỉ Lệ Thành Phẩm Theo Thời Gian vs Target Benchmark (98%)</span>
            </h6>
          </div>
          <div id="chartTrend" style="min-height: 310px;"></div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="app-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons text-danger fs-5">pie_chart</span>
              <span>Phân Tích Nhóm Lỗi (Pareto A1 - A5)</span>
            </h6>
          </div>
          <div id="chartPareto" style="min-height: 310px;"></div>
        </div>
      </div>
    </div>

    <!-- Biểu Đồ Cột Chồng (Stacked Bar Chart) Cơ Cấu Lỗi A1-A5 & Phân Loại Vật Liệu -->
    <div class="app-card p-3 mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
          <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
            <span class="material-icons text-warning fs-5">stacked_bar_chart</span>
            <span>Cơ Cấu Lỗi & Phân Bố Phế Phẩm (Stacked Bar Chart A1 - A5) Theo Line Máy Đùn</span>
          </h6>
          <small class="text-muted">Biểu đồ cột chồng phân tích các loại lỗi đang chiếm tỉ lệ lớn và phân tách rõ ràng giữa Vật liệu nguyên sinh và Vật liệu nghiền</small>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" id="btnStackedModeDefects" onclick="toggleStackedMode('defects')">
              <span class="material-icons align-middle" style="font-size: 15px;">bar_chart</span> Theo Loại Lỗi (A1 - A5)
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnStackedModeMaterials" onclick="toggleStackedMode('materials')">
              <span class="material-icons align-middle" style="font-size: 15px;">category</span> Theo Nhóm Vật Liệu (Zin / Nghiền)
            </button>
          </div>
          <span class="badge bg-success-subtle text-success border ms-2">Lỗi Zin: <span id="lblVirginDefectCount">0</span></span>
          <span class="badge bg-primary-subtle text-primary border">Lỗi Nghiền: <span id="lblRecycledDefectCount">0</span></span>
        </div>
      </div>
      <div id="chartDefectStacked" style="min-height: 330px;"></div>
    </div>

    <!-- Hàng Biểu Đồ 2: So sánh Máy Đùn & So sánh Kích Cỡ Size -->
    <div class="row g-3 mb-4">
      <div class="col-lg-7">
        <div class="app-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons text-info fs-5">precision_manufacturing</span>
              <span>So Sánh Tỉ Lệ Thành Phẩm Theo Từng Máy Đùn (Extruder Comparison)</span>
            </h6>
          </div>
          <div id="chartExtrusion" style="min-height: 290px;"></div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="app-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons text-success fs-5">aspect_ratio</span>
              <span>Tỉ Lệ Thành Phẩm Theo Kích Cỡ Ống (Size)</span>
            </h6>
          </div>
          <div id="chartSize" style="min-height: 290px;"></div>
        </div>
      </div>
    </div>

    <!-- Tiến Độ Chi Tiết Theo Từng Line Máy Đùn (Multi-Line Chart View) -->
    <div class="app-card p-3 mb-4" id="sectionMultiLine">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
          <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
            <span class="material-icons text-primary fs-5">view_module</span>
            <span>Tiến Độ Chi Tiết Theo Từng Line Máy Đùn (Multi-Line View)</span>
          </h6>
          <small class="text-muted">Theo dõi độc lập tỉ lệ thành phẩm, cơ cấu vật liệu (Zin/Nghiền), phân bố lỗi A1-A5 và biểu đồ xu hướng của từng line máy</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border px-2 py-1 fw-bold" id="badgeMultiLineCount">0 Lines</span>
      </div>
      <div class="row g-3" id="lineChartsContainer">
        <!-- Rendered dynamically by renderMultiLineCards() -->
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 2: MA TRẬN TỈ LỆ THÀNH PHẨM (MÔ PHỎNG SHEET 良品率)
       ========================================================================= -->
  <div id="tabContentMatrix" style="display: none;">
    <div class="app-card p-3">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
          <h5 class="fw-bold m-0 d-flex align-items-center gap-2 fs-6">
            <span class="material-icons text-primary">grid_on</span>
            <span>BẢNG MA TRẬN TỈ LỆ THÀNH PHẨM THEO MÁY & NGÀY TRONG THÁNG (良品率)</span>
          </h5>
          <p class="text-muted small mb-0">Theo dõi tỉ lệ hàng đạt của từng máy đùn qua 31 ngày trong tháng (Chuẩn file Excel sản xuất TU)</p>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center gap-2 small">
            <span class="d-inline-block cell-rate-pass px-2 py-1 rounded" style="font-size: 11px;">>= 98% (Đạt chuẩn)</span>
            <span class="d-inline-block cell-rate-warn px-2 py-1 rounded" style="font-size: 11px;">95 - 97.9% (Cảnh báo)</span>
            <span class="d-inline-block cell-rate-danger px-2 py-1 rounded" style="font-size: 11px;">&lt; 95% (Bất thường)</span>
          </div>
        </div>
      </div>

      <div class="table-matrix-wrapper border rounded">
        <table class="table-matrix w-100" id="matrixTable">
          <thead>
            <tr id="matrixHeaderRow">
              <th class="sticky-col">Máy Đùn</th>
              <!-- Days 1 to 31 generated by JS -->
              <th style="min-width: 65px; background: #e0f2fe; color: #0369a1;">TB Tháng</th>
            </tr>
          </thead>
          <tbody id="matrixTableBody">
            <tr><td colspan="33" class="text-center py-4 text-muted">Đang tải ma trận tỉ lệ thành phẩm...</td></tr>
          </tbody>
          <tfoot id="matrixTableFoot">
            <!-- Day averages -->
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 3: BẢNG DỮ LIỆU CHI TIẾT & NHẬP LIỆU (SHEET データ)
       ========================================================================= -->
  <div id="tabContentRecords" style="display: none;">
    <div class="app-card">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary">table_view</span>
          <span class="fw-bold">Danh Sách Lô Thành Phẩm & Phân Loại Lỗi (A1 - A5)</span>
          <span class="badge bg-primary-subtle text-primary border ms-2" id="recordsTotalBadge">0 bản ghi</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <input type="text" id="recordsSearchInput" class="app-form-control app-form-control-sm" placeholder="Tìm theo LOT, Mã SP, Size..." style="width: 220px;" oninput="debounceFilterRecords()">
        </div>
      </div>

      <div class="app-table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
        <table class="app-table table-sticky-header">
          <thead>
            <tr>
              <th style="width: 45px;">STT</th>
              <th style="width: 100px;">Ngày Cuộn</th>
              <th style="width: 75px;">Máy Cuộn</th>
              <th style="width: 80px;">Size</th>
              <th style="width: 90px;">Mã LOT</th>
              <th style="width: 95px; text-align: center;">Nhóm VL</th>
              <th>Mã Sản Phẩm</th>
              <th style="width: 75px;">Máy Đùn</th>
              <th style="width: 95px;">Ngày Đùn</th>
              <th style="width: 85px;">Giờ Bobin</th>
              <th style="text-align: right; width: 65px;">Đạt</th>
              <th style="text-align: right; width: 65px;">Tổng</th>
              <th style="text-align: right; width: 90px;">Tỉ Lệ TP</th>
              <th style="text-align: right; width: 50px;" title="Lỗi ngoại quan A1">A1</th>
              <th style="text-align: right; width: 50px;" title="Vượt giới hạn trên A2">A2</th>
              <th style="text-align: right; width: 50px;" title="Vượt giới hạn dưới A3">A3</th>
              <th style="text-align: right; width: 50px;" title="Độ dẹt A4">A4</th>
              <th style="text-align: right; width: 50px;" title="Lỗi khác A5">A5</th>
              <th style="text-align: center; width: 105px;">Đánh Giá</th>
              <th style="text-align: center; width: 120px;">Hành Động</th>
            </tr>
          </thead>
          <tbody id="recordsTableBody">
            <tr><td colspan="20" class="text-center py-4 text-muted">Đang tải dữ liệu...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="app-card-footer">
        <div id="recordsPagination" class="w-100"></div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 4: PHIẾU YÊU CẦU ĐIỀU TRA & ĐỐI ỨNG BẤT THƯỜNG (SHEET Đối ứng)
       ========================================================================= -->
  <div id="tabContentInvestigations" style="display: none;">
    <div class="app-card">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h5 class="fw-bold m-0 d-flex align-items-center gap-2 fs-6">
            <span class="material-icons text-danger">assignment_late</span>
            <span>QUẢN LÝ PHIẾU YÊU CẦU ĐIỀU TRA NGUYÊN NHÂN & BIỆN PHÁP ĐỐI SÁCH</span>
          </h5>
          <p class="text-muted small mb-0">Theo dõi quy trình xử lý bất thường thành phẩm khi tỉ lệ giảm sút, phân công người phụ trách và cập nhật kết quả</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if ($canInvestigate): ?>
          <button type="button" class="app-btn app-btn-primary btn-sm" onclick="openCreateInvestigationModal()">
            <span class="material-icons">add_alert</span> Tạo Phiếu Điều Tra Mới
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="app-table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
        <table class="app-table table-sticky-header">
          <thead>
            <tr>
              <th style="width: 120px;">Mã Phiếu</th>
              <th style="width: 105px;">Ngày Y/C</th>
              <th style="width: 85px;">Size</th>
              <th>Mã Sản Phẩm</th>
              <th style="width: 80px;">Máy Đùn</th>
              <th style="width: 90px;">Mã LOT</th>
              <th style="width: 90px; text-align: right;">TLTP %</th>
              <th>Tình Trạng Phát Sinh</th>
              <th>Nguyên Nhân Cốt Lõi</th>
              <th>Biện Pháp Đối Ứng / Đối Sách</th>
              <th style="width: 120px;">Người Đối Ứng</th>
              <th style="width: 120px; text-align: center;">Kết Quả</th>
              <th style="width: 100px; text-align: center;">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="investigationsTableBody">
            <tr><td colspan="13" class="text-center py-4 text-muted">Đang tải phiếu điều tra...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 1: THÊM / CHỈNH SỬA BẢN GHI THÀNH PHẨM (YIELD RECORD MODAL)
     ========================================================================= -->
<div class="modal fade" id="modalRecordForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold" id="modalRecordTitle">
          <span class="material-icons">edit_note</span>
          <span>Thông Tin Lô Thành Phẩm</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formRecordModal" onsubmit="submitRecordModal(event)">
        <input type="hidden" id="recId" name="id" value="0">
        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Ngày cuộn -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Ngày cuộn (小巻日) <span class="text-danger">*</span></label>
              <input type="date" id="recKomakiDate" name="komaki_date" class="form-control form-control-sm" required>
            </div>
            <!-- Máy cuộn -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Máy cuộn <span class="text-danger">*</span></label>
              <input type="text" id="recKomakiMachine" name="komaki_machine" class="form-control form-control-sm" placeholder="VD: ST01, ST02" required>
            </div>
            <!-- Size -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Kích cỡ (Size) <span class="text-danger">*</span></label>
              <input type="text" id="recSize" name="size" class="form-control form-control-sm" placeholder="VD: TU0425, TU0604" required>
            </div>
            <!-- LOT -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Mã LOT <span class="text-danger">*</span></label>
              <input type="text" id="recLotNo" name="lot_no" class="form-control form-control-sm font-monospace fw-bold" placeholder="VD: ADEVZ" oninput="detectMaterialGroupFromLot(this.value)" required>
              <div id="recMaterialNotice" class="small mt-1 text-primary fw-semibold" style="font-size: 11px;"></div>
            </div>

            <!-- Nhóm vật liệu -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Nhóm vật liệu (Ký tự thứ 2 LOT)</label>
              <select id="recMaterialGroup" name="material_group" class="form-select form-select-sm fw-bold">
                <option value="virgin">Nguyên sinh (Zin)</option>
                <option value="recycled">Nghiền (Tái sinh)</option>
                <option value="other">Khác</option>
              </select>
            </div>
            <!-- Mã sản phẩm -->
            <div class="col-md-3">
              <label class="form-label small fw-bold">Mã sản phẩm (製品品番) <span class="text-danger">*</span></label>
              <input type="text" id="recProductCode" name="product_code" class="form-control form-control-sm" placeholder="VD: TU0425C-100Z2" required>
            </div>
            <!-- Máy đùn -->
            <div class="col-md-2">
              <label class="form-label small fw-bold">Máy đùn</label>
              <input type="text" id="recExtMachine" name="extrusion_machine" class="form-control form-control-sm" placeholder="VD: PL08, PL14">
            </div>
            <!-- Ngày đùn -->
            <div class="col-md-2">
              <label class="form-label small fw-bold">Ngày đùn (成形日)</label>
              <input type="date" id="recExtDate" name="extrusion_date" class="form-control form-control-sm">
            </div>
            <!-- Giờ bobin (GIỜ ĐÙN) -->
            <div class="col-md-2">
              <label class="form-label small fw-bold">Giờ bobin</label>
              <input type="text" id="recBobbinTime" name="bobbin_time" class="form-control form-control-sm font-monospace" placeholder="VD: 20:00">
            </div>

            <!-- Số lượng đạt & Tổng số -->
            <div class="col-md-3">
              <label class="form-label small fw-bold text-success">Sản lượng đạt (良品数) <span class="text-danger">*</span></label>
              <input type="number" id="recGoodQty" name="good_qty" class="form-control form-control-sm fw-bold text-success" min="0" oninput="calculateRecordYield()" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Tổng thiết lập (設定数) <span class="text-danger">*</span></label>
              <input type="number" id="recTotalQty" name="total_qty" class="form-control form-control-sm fw-bold" min="1" oninput="calculateRecordYield()" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold text-primary">Tỉ lệ thành phẩm (良品率 %)</label>
              <input type="number" step="0.01" id="recYieldRate" name="yield_rate" class="form-control form-control-sm fw-bold text-primary bg-light" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold text-danger">Số phế phẩm (不良数)</label>
              <input type="number" id="recDefectQty" name="defect_qty" class="form-control form-control-sm text-danger bg-light" readonly>
            </div>

            <!-- Phân loại lỗi chi tiết -->
            <div class="col-12 mt-2">
              <div class="p-2 border rounded bg-light">
                <span class="small fw-bold text-muted d-block mb-2">CHI TIẾT CÁC NHÓM LỖI (A1 - A5):</span>
                <div class="row g-2">
                  <div class="col-md">
                    <label class="form-label small" title="Lỗi ngoại quan: trầy, vón, gel, xước...">A1 (Ngoại quan)</label>
                    <input type="number" id="recDefA1" name="defect_a1" class="form-control form-control-sm" value="0" min="0">
                  </div>
                  <div class="col-md">
                    <label class="form-label small" title="Vượt giới hạn trên">A2 (Vượt trên)</label>
                    <input type="number" id="recDefA2" name="defect_a2" class="form-control form-control-sm" value="0" min="0">
                  </div>
                  <div class="col-md">
                    <label class="form-label small" title="Vượt giới hạn dưới">A3 (Vượt dưới)</label>
                    <input type="number" id="recDefA3" name="defect_a3" class="form-control form-control-sm" value="0" min="0">
                  </div>
                  <div class="col-md">
                    <label class="form-label small" title="Lỗi độ dẹt / dẹt">A4 (Độ dẹt)</label>
                    <input type="number" id="recDefA4" name="defect_a4" class="form-control form-control-sm" value="0" min="0">
                  </div>
                  <div class="col-md">
                    <label class="form-label small" title="Bất thường khác">A5 (Khác)</label>
                    <input type="number" id="recDefA5" name="defect_a5" class="form-control form-control-sm" value="0" min="0">
                  </div>
                </div>
              </div>
            </div>

            <!-- Ghi chú -->
            <div class="col-12">
              <label class="form-label small fw-bold">Ghi chú / Đánh giá nguyên nhân</label>
              <textarea id="recNote" name="note" class="form-control form-control-sm" rows="2" placeholder="Nhập ghi chú nếu có phát sinh bất thường..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1" id="btnSaveRecord">
            <span class="material-icons fs-6">save</span> Lưu Bản Ghi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 2: TẠO / CẬP NHẬT PHIẾU YÊU CẦU ĐIỀU TRA (INVESTIGATION MODAL)
     ========================================================================= -->
<div class="modal fade" id="modalInvestigationForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold" id="modalInvTitle">
          <span class="material-icons">assignment_late</span>
          <span>Phiếu Yêu Cầu Điều Tra Bất Thường Thành Phẩm</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formInvModal" onsubmit="submitInvestigationModal(event)">
        <input type="hidden" id="invId" name="id" value="0">
        <input type="hidden" id="invYieldRecordId" name="yield_record_id" value="">
        <div class="modal-body p-4">
          <!-- Thông tin đính kèm từ lô hàng tại thời điểm bất thường -->
          <div class="p-3 bg-light border rounded mb-3">
            <span class="small fw-bold text-primary d-block mb-2">📌 THÔNG TIN THÀNH PHẨM TẠI THỜI ĐIỂM PHÁT SINH BẤT THƯỜNG:</span>
            <div class="row g-2 small">
              <div class="col-md-3"><strong>Mã LOT:</strong> <input type="text" id="invLotNo" name="lot_no" class="form-control form-control-sm font-monospace fw-bold" required></div>
              <div class="col-md-3"><strong>Kích cỡ:</strong> <input type="text" id="invSize" name="size" class="form-control form-control-sm fw-bold" required></div>
              <div class="col-md-6"><strong>Mã sản phẩm:</strong> <input type="text" id="invProductCode" name="product_code" class="form-control form-control-sm" required></div>
              <div class="col-md-3"><strong>Máy đùn:</strong> <input type="text" id="invExtMachine" name="extrusion_machine" class="form-control form-control-sm"></div>
              <div class="col-md-3"><strong>Ngày đùn:</strong> <input type="date" id="invExtDate" name="extrusion_date" class="form-control form-control-sm"></div>
              <div class="col-md-3"><strong>Ngày cuộn:</strong> <input type="date" id="invKomDate" name="komaki_date" class="form-control form-control-sm"></div>
              <div class="col-md-3"><strong>Tỉ lệ thành phẩm:</strong> <input type="number" step="0.01" id="invYieldRate" name="yield_rate" class="form-control form-control-sm fw-bold text-danger"></div>
            </div>
          </div>

          <!-- Chi tiết điều tra -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Tình trạng phát sinh bất thường <span class="text-danger">*</span></label>
              <input type="text" id="invStatusDesc" name="status_description" class="form-control form-control-sm" placeholder="VD: Phát sinh lỗi lớn, Biểu đồ lượn cao vượt dung sai..." required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Người phụ trách điều tra (PIC) <span class="text-danger">*</span></label>
              <input type="text" id="invAssignedTo" name="assigned_to" class="form-control form-control-sm" placeholder="VD: Nguyễn Văn A (QC)" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Kết quả / Tiến độ <span class="text-danger">*</span></label>
              <select id="invResultStatus" name="result_status" class="form-select form-select-sm fw-bold">
                <option value="Chờ điều tra">Chờ điều tra</option>
                <option value="Đang xử lý">Đang xử lý</option>
                <option value="Hoàn thành">Hoàn thành</option>
                <option value="Cần theo dõi thêm">Cần theo dõi thêm</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-danger">Nguyên nhân cốt lõi (Root Cause)</label>
              <textarea id="invRootCause" name="root_cause" class="form-control form-control-sm" rows="3" placeholder="Ghi nhận chi tiết kết quả điều tra nguyên nhân..."></textarea>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-success">Biện pháp đối ứng tức thời & Biện pháp đối sách lâu dài</label>
              <textarea id="invCountermeasure" name="countermeasure" class="form-control form-control-sm" rows="3" placeholder="Đề xuất và thực thi biện pháp khắc phục, phòng ngừa tái diễn..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-1" id="btnSaveInv">
            <span class="material-icons fs-6">save</span> Lưu Phiếu Điều Tra
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 3: IMPORT EXCEL (.xlsm / .xlsx / .csv) - SMART UPSERT
     ========================================================================= -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold">
          <span class="material-icons">upload_file</span>
          <span>Import Dữ Liệu Sản Xuất (.xlsm / .xlsx / .csv) & Upsert Thông Minh</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formImportExcel" onsubmit="submitImportExcel(event)">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Chọn file dữ liệu cần import:</label>
            <input type="file" id="importFileInput" name="excel_file" class="form-control" accept=".xlsx, .xlsm, .xls, .csv" required>
            <div class="form-text small">
              Tương thích hoàn hảo với file <code>26年09月生産進捗(TU).xlsm</code> (hệ thống tự động đọc sheet <strong>データ</strong> và sheet <strong>Đối ứng</strong>), hoặc file Excel xuất từ hệ thống.
            </div>
          </div>
          
          <div class="p-3 bg-light border rounded small">
            <h6 class="fw-bold text-primary d-flex align-items-center gap-1 mb-2">
              <span class="material-icons fs-6">auto_awesome</span>
              <span>CƠ CHẾ UPSERT THÔNG MINH (4 TRƯỜNG ĐỊNH DANH UNIQUE KEY):</span>
            </h6>
            <ul class="mb-2 ps-3">
              <li><strong>Khóa duy nhất (Unique ID):</strong> Tổ hợp 4 trường gồm <code>Giờ bobin</code> + <code>Ngày đùn</code> + <code>Máy cuộn</code> + <code>Ngày cuộn</code>.</li>
              <li><strong>Trường hợp trùng khóa:</strong> Tự động <strong>CẬP NHẬT (Update)</strong> lại số lượng đạt, tổng số, phế phẩm A1-A5 và thông tin nguyên vật liệu.</li>
              <li><strong>Trường hợp khóa mới:</strong> Tự động <strong>THÊM MỚI (Insert)</strong> bản ghi vào cơ sở dữ liệu.</li>
              <li><strong>Tự động nhận diện vật liệu:</strong> Phân loại Zin / Nghiền dựa trên ký tự thứ 2 của mã LOT.</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-success btn-sm d-flex align-items-center gap-1" id="btnSubmitImport">
            <span class="material-icons fs-6">cloud_upload</span> Bắt Đầu Import & Đồng Bộ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 4: CẤU HÌNH MỐC TIÊU CHUẨN (BENCHMARK MODAL)
     ========================================================================= -->
<div class="modal fade" id="modalBenchmark" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold">
          <span class="material-icons">tune</span>
          <span>Thiết Lập Mốc Tiêu Chuẩn (Benchmark) Tỉ Lệ Thành Phẩm</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Form thêm/sửa benchmark -->
        <form id="formBenchmark" onsubmit="submitBenchmark(event)" class="row g-2 mb-4 p-3 bg-light border rounded">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Kích cỡ (Size)</label>
            <input type="text" id="bmSize" name="size" class="form-control form-control-sm" placeholder="VD: ALL hoặc TU0425" value="ALL" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Máy đùn</label>
            <input type="text" id="bmMachine" name="extrusion_machine" class="form-control form-control-sm" placeholder="VD: ALL hoặc PL08" value="ALL" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-success">Target (%)</label>
            <input type="number" step="0.1" id="bmTarget" name="benchmark_rate" class="form-control form-control-sm" value="98.0" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-danger">Min (%)</label>
            <input type="number" step="0.1" id="bmMin" name="min_acceptable_rate" class="form-control form-control-sm" value="95.0" required>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100">Lưu Mốc</button>
          </div>
        </form>

        <h6 class="fw-bold small text-muted">DANH SÁCH MỐC TIÊU CHUẨN ĐANG ÁP DỤNG:</h6>
        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
          <table class="app-table table-sm">
            <thead>
              <tr>
                <th>Size</th>
                <th>Máy Đùn</th>
                <th class="text-end">Mục Tiêu (Target %)</th>
                <th class="text-end">Ngưỡng Báo Động (Min %)</th>
                <th>Mô Tả</th>
                <th>Cập Nhật</th>
              </tr>
            </thead>
            <tbody id="benchmarkTableBody">
              <tr><td colspan="6" class="text-center py-3 text-muted">Đang tải cấu hình...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 px-4">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 5: XEM TRƯỚC BÁO CÁO PDF (A4 LANDSCAPE)
     ========================================================================= -->
<div class="modal fade" id="modalPdfPreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2 px-3">
        <h6 class="modal-title d-flex align-items-center gap-2 m-0 fs-6">
          <span class="material-icons text-danger">picture_as_pdf</span>
          <span>Bản Xem Trước Báo Cáo Chất Lượng PDF (A4 Khổ Ngang)</span>
        </h6>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1" onclick="printPdfIframe()">
            <span class="material-icons fs-6">print</span> In Báo Cáo / Lưu PDF
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body p-0" style="height: calc(100vh - 55px); background: #334155;">
        <iframe id="pdfPreviewIframe" style="width: 100%; height: 100%; border: none; background: #fff;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 6: CẤU HÌNH PHÂN LOẠI NGUYÊN VẬT LIỆU (KÝ TỰ THỨ 2 MÃ LOT)
     ========================================================================= -->
<div class="modal fade" id="modalMaterialRules" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold">
          <span class="material-icons">category</span>
          <span>Cấu Hình Quy Tắc Phân Loại Vật Liệu (Ký Tự Thứ 2 Mã LOT)</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Form thêm / sửa quy tắc -->
        <form id="formMaterialRule" onsubmit="submitMaterialRule(event)" class="row g-2 mb-4 p-3 bg-light border rounded align-items-end">
          <input type="hidden" id="mrId" name="id" value="0">
          <div class="col-md-2">
            <label class="form-label small fw-bold">Ký tự thứ 2 <span class="text-danger">*</span></label>
            <input type="text" id="mrCharCode" name="char_code" class="form-control form-control-sm text-uppercase fw-bold text-center" maxlength="2" placeholder="VD: A" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-bold">Tên vật liệu <span class="text-danger">*</span></label>
            <input type="text" id="mrMaterialName" name="material_name" class="form-control form-control-sm" placeholder="VD: Nhựa Zin TU hoặc Nhựa Nghiền" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Nhóm phân loại <span class="text-danger">*</span></label>
            <select id="mrMaterialGroup" name="material_group" class="form-select form-select-sm fw-bold">
              <option value="virgin">Nguyên sinh (Zin)</option>
              <option value="recycled">Nghiền (Tái sinh)</option>
              <option value="other">Khác</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Mô tả / Ghi chú</label>
            <input type="text" id="mrDescription" name="description" class="form-control form-control-sm" placeholder="Ghi chú thêm...">
          </div>
          <div class="col-12 text-end mt-2">
            <button type="button" class="btn btn-secondary btn-sm me-1" onclick="resetMaterialRuleForm()">Làm mới</button>
            <button type="submit" class="btn btn-primary btn-sm" id="btnSaveMaterialRule">
              <span class="material-icons align-middle" style="font-size: 15px;">save</span> Lưu Quy Tắc
            </button>
          </div>
        </form>

        <h6 class="fw-bold small text-muted mb-2">DANH SÁCH QUY TẮC ĐANG ÁP DỤNG:</h6>
        <div class="table-responsive border rounded" style="max-height: 280px; overflow-y: auto;">
          <table class="app-table table-sm w-100">
            <thead>
              <tr>
                <th style="width: 70px; text-align: center;">Ký Tự</th>
                <th>Tên Vật Liệu</th>
                <th style="width: 150px; text-align: center;">Nhóm Phân Loại</th>
                <th>Mô Tả</th>
                <th style="width: 90px; text-align: center;">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="materialRulesTableBody">
              <tr><td colspan="5" class="text-center py-3 text-muted">Đang tải danh sách quy tắc...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 px-4">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     JAVASCRIPT: CONTROLLER & BIỂU ĐỒ APEXCHARTS
     ========================================================================= -->
<script>
let currentTab = 'dashboard';
let charts = {};
let multiLineChartInstances = {};
let currentStackedData = null;
let stackedDefectMode = 'defects'; // 'defects' or 'materials'
let materialRulesList = [];
let recordsCurrentPage = 1;
let debounceTimeout = null;

function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// 1. Khởi tạo khi nạp trang
document.addEventListener("DOMContentLoaded", async function () {
  // Nạp ngày mặc định: từ 2026-09-01 đến 2026-09-30 (phù hợp với dữ liệu mẫu)
  document.getElementById('filterDateFrom').value = '2026-09-01';
  document.getElementById('filterDateTo').value = '2026-09-30';

  await loadFilterOptions();
  updateExportLink();
  await loadDashboardData();
});

// Chuyển Tab
function switchQualityTab(tab) {
  currentTab = tab;
  ['dashboard', 'matrix', 'records', 'investigations'].forEach(t => {
    const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
    const content = document.getElementById('tabContent' + t.charAt(0).toUpperCase() + t.slice(1));
    if (btn) btn.classList.toggle('active', t === tab);
    if (content) content.style.display = (t === tab) ? 'block' : 'none';
  });

  if (tab === 'dashboard') {
    // Redraw charts if needed
    Object.values(charts).forEach(c => { try { c.render(); } catch(e){} });
    Object.values(multiLineChartInstances).forEach(c => { try { c.render(); } catch(e){} });
  } else if (tab === 'matrix') {
    loadMatrixData();
  } else if (tab === 'records') {
    loadRecordsData(recordsCurrentPage);
  } else if (tab === 'investigations') {
    loadInvestigationsData();
  }
}

// Toggle kiểu thời gian (ngày vs tháng)
function togglePeriodInputs() {
  const mode = document.getElementById('filterPeriodMode').value;
  document.getElementById('divPeriodDay').style.display = (mode === 'day') ? 'block' : 'none';
  document.getElementById('divPeriodMonth').style.display = (mode === 'month') ? 'block' : 'none';
  triggerFilter();
}

function getFilterParams() {
  const params = new URLSearchParams();
  params.set('date_type', document.getElementById('filterDateType').value);
  const mode = document.getElementById('filterPeriodMode').value;
  params.set('period_mode', mode);

  if (mode === 'day') {
    params.set('date_from', document.getElementById('filterDateFrom').value);
    params.set('date_to', document.getElementById('filterDateTo').value);
  } else {
    params.set('month', document.getElementById('filterMonth').value);
    params.set('year', document.getElementById('filterYear').value);
  }

  const mg = document.getElementById('filterMaterialGroup').value;
  if (mg) params.set('material_group', mg);

  const extM = document.getElementById('filterExtMachine').value;
  if (extM) params.set('extrusion_machine', extM);

  const komM = document.getElementById('filterKomMachine').value;
  if (komM) params.set('komaki_machine', komM);

  const sz = document.getElementById('filterSize').value;
  if (sz) params.set('size', sz);

  const st = document.getElementById('filterStatus').value;
  if (st) params.set('quality_status', st);

  return params;
}

function updateExportLink() {
  const params = getFilterParams();
  params.set('action', 'export_excel');

  params.set('format', 'xlsx');
  const btnXlsx = document.getElementById('btnExportExcelXlsx');
  if (btnXlsx) btnXlsx.href = `api/quality_export.php?${params.toString()}`;

  params.set('format', 'csv');
  const btnCsv = document.getElementById('btnExportExcelCsv');
  if (btnCsv) btnCsv.href = `api/quality_export.php?${params.toString()}`;
}

function triggerFilter() {
  updateExportLink();
  if (currentTab === 'dashboard') {
    loadDashboardData();
  } else if (currentTab === 'matrix') {
    loadMatrixData();
  } else if (currentTab === 'records') {
    loadRecordsData(1);
  } else if (currentTab === 'investigations') {
    loadInvestigationsData();
  }
}

function debounceFilterRecords() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    loadRecordsData(1);
  }, 300);
}

// Nạp danh sách options cho các filter
async function loadFilterOptions() {
  try {
    const res = await fetch('api/quality_yield.php?action=get_filter_options');
    const data = await res.json();
    if (!data.success) return;

    materialRulesList = data.material_rules || [];

    const extSel = document.getElementById('filterExtMachine');
    data.extrusion_machines.forEach(m => {
      extSel.innerHTML += `<option value="${escapeHtml(m)}">${escapeHtml(m)}</option>`;
    });

    const komSel = document.getElementById('filterKomMachine');
    data.komaki_machines.forEach(m => {
      komSel.innerHTML += `<option value="${escapeHtml(m)}">${escapeHtml(m)}</option>`;
    });

    const sizeSel = document.getElementById('filterSize');
    data.sizes.forEach(s => {
      sizeSel.innerHTML += `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`;
    });
  } catch(e) {
    console.error('Error loadFilterOptions:', e);
  }
}

// =========================================================================
// 2. DASHBOARD DATA & APEXCHARTS
// =========================================================================
async function loadDashboardData() {
  const params = getFilterParams();
  params.set('action', 'get_dashboard');

  try {
    const res = await fetch(`api/quality_yield.php?${params.toString()}`);
    const data = await res.json();
    if (!data.success) return;

    // 1. KPI Cards
    const kpi = data.kpi;
    document.getElementById('kpiTotalProduced').textContent = Number(kpi.total_produced).toLocaleString();
    document.getElementById('kpiTotalGood').textContent = Number(kpi.total_good).toLocaleString();
    document.getElementById('kpiYieldRate').textContent = kpi.yield_rate + '%';
    document.getElementById('kpiBenchmark').textContent = kpi.benchmark_rate + '%';
    document.getElementById('kpiDefectRate').textContent = kpi.defect_rate + '%';
    document.getElementById('kpiTotalDefect').textContent = Number(kpi.total_defect).toLocaleString();
    document.getElementById('kpiDangerCount').textContent = kpi.count_danger;
    document.getElementById('badgePendingInv').textContent = kpi.pending_investigations;

    // 2. Material Summary (Nguyên sinh vs Nghiền)
    if (data.material_summary) {
      const ms = data.material_summary;
      const v = ms.virgin || {};
      const r = ms.recycled || {};
      document.getElementById('matVirginProduced').textContent = Number(v.produced || 0).toLocaleString();
      document.getElementById('matVirginRate').textContent = (v.yield_rate || 0) + '%';
      document.getElementById('matVirginDefect').textContent = Number(v.defect || 0).toLocaleString();
      document.getElementById('matVirginLots').textContent = `${v.lots || 0} lô`;
      document.getElementById('matVirginRatio').textContent = `${v.ratio_pct || 0}% sản lượng`;

      document.getElementById('matRecycledProduced').textContent = Number(r.produced || 0).toLocaleString();
      document.getElementById('matRecycledRate').textContent = (r.yield_rate || 0) + '%';
      document.getElementById('matRecycledDefect').textContent = Number(r.defect || 0).toLocaleString();
      document.getElementById('matRecycledLots').textContent = `${r.lots || 0} lô`;
      document.getElementById('matRecycledRatio').textContent = `${r.ratio_pct || 0}% sản lượng`;
    }

    // 3. Trend Chart
    renderTrendChart(data.trend);

    // 4. Pareto Chart
    renderParetoChart(data.pareto_defects);

    // 5. Stacked Bar Chart (Cơ cấu lỗi A1-A5 & Phân tách Zin/Nghiền)
    renderStackedDefectChart(data.stacked_defect_data);

    // 6. Extruder Comparison
    renderExtrusionChart(data.extrusion_comparison);

    // 7. Size Chart
    renderSizeChart(data.size_comparison);

    // 8. Multi-Line Chart View Cards
    renderMultiLineCards(data.lines_data || []);

  } catch(e) {
    console.error('Error loadDashboardData:', e);
  }
}

// Biểu đồ xu hướng (Trend Chart)
function renderTrendChart(trendData) {
  const dates = trendData.map(d => d.date);
  const rates = trendData.map(d => d.yield_rate);
  const benchs = trendData.map(d => d.benchmark_rate);

  const options = {
    series: [
      { name: 'Tỉ Lệ Thành Phẩm Thực Tế (%)', type: 'area', data: rates },
      { name: 'Mục Tiêu Benchmark (%)', type: 'line', data: benchs }
    ],
    chart: { height: 310, type: 'line', toolbar: { show: true }, zoom: { enabled: false } },
    stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 5] },
    colors: ['#0284c7', '#dc2626'],
    fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.45, opacityTo: 0.05 } },
    xaxis: { categories: dates, labels: { rotate: -45, style: { fontSize: '11px' } } },
    yaxis: { min: 80, max: 100, labels: { formatter: v => v.toFixed(1) + '%' } },
    tooltip: { shared: true, y: { formatter: v => v ? v.toFixed(2) + '%' : '-' } },
    markers: { size: [4, 0] }
  };

  if (charts.trend) charts.trend.destroy();
  charts.trend = new ApexCharts(document.querySelector("#chartTrend"), options);
  charts.trend.render();
}

// Biểu đồ Pareto (Pareto Defect Chart)
function renderParetoChart(paretoData) {
  const categories = paretoData.map(d => d.code);
  const counts = paretoData.map(d => d.count);
  const cumPcts = paretoData.map(d => d.cum_percent);

  const options = {
    series: [
      { name: 'Số lượng lỗi (Cuộn)', type: 'column', data: counts },
      { name: 'Tỉ lệ tích lũy (%)', type: 'line', data: cumPcts }
    ],
    chart: { height: 310, type: 'line', toolbar: { show: false } },
    stroke: { width: [0, 3], curve: 'straight' },
    colors: ['#f87171', '#0284c7'],
    xaxis: { categories: categories },
    yaxis: [
      { title: { text: 'Số lỗi' }, labels: { formatter: v => Math.round(v) } },
      { opposite: true, max: 100, title: { text: 'Tích lũy %' }, labels: { formatter: v => v + '%' } }
    ],
    tooltip: { shared: true }
  };

  if (charts.pareto) charts.pareto.destroy();
  charts.pareto = new ApexCharts(document.querySelector("#chartPareto"), options);
  charts.pareto.render();
}

// Toggle chế độ biểu đồ cột chồng (Lỗi vs Vật liệu)
function toggleStackedMode(mode) {
  stackedDefectMode = mode;
  const btnD = document.getElementById('btnStackedModeDefects');
  const btnM = document.getElementById('btnStackedModeMaterials');
  if (btnD) btnD.classList.toggle('active', mode === 'defects');
  if (btnM) btnM.classList.toggle('active', mode === 'materials');
  if (currentStackedData) {
    renderStackedDefectChart(currentStackedData);
  }
}

// Biểu đồ Cột Chồng (Stacked Bar Chart: A1-A5 & Phân tách Zin / Nghiền)
function renderStackedDefectChart(stackedData) {
  if (!stackedData || !stackedData.categories || stackedData.categories.length === 0) return;
  currentStackedData = stackedData;

  const sumVir = (stackedData.series_virgin || []).reduce((a, b) => a + b, 0);
  const sumRec = (stackedData.series_recycled || []).reduce((a, b) => a + b, 0);
  const elV = document.getElementById('lblVirginDefectCount');
  const elR = document.getElementById('lblRecycledDefectCount');
  if (elV) elV.textContent = Number(sumVir).toLocaleString();
  if (elR) elR.textContent = Number(sumRec).toLocaleString();

  let series = [];
  let colors = [];

  if (stackedDefectMode === 'materials') {
    series = [
      { name: 'Vật liệu nguyên sinh (Zin)', data: stackedData.series_virgin || [] },
      { name: 'Vật liệu nghiền (Recycled)', data: stackedData.series_recycled || [] }
    ];
    colors = ['#16a34a', '#0284c7'];
  } else {
    series = [
      { name: 'A1: Ngoại quan (Trầy, gel...)', data: stackedData.series_a1 || [] },
      { name: 'A2: Vượt giới hạn trên', data: stackedData.series_a2 || [] },
      { name: 'A3: Vượt giới hạn dưới', data: stackedData.series_a3 || [] },
      { name: 'A4: Lỗi độ dẹt', data: stackedData.series_a4 || [] },
      { name: 'A5: Lỗi khác', data: stackedData.series_a5 || [] }
    ];
    colors = ['#ef4444', '#f97316', '#eab308', '#8b5cf6', '#64748b'];
  }

  const options = {
    series: series,
    chart: {
      type: 'bar',
      height: 330,
      stacked: true,
      toolbar: { show: true },
      zoom: { enabled: false }
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        borderRadius: 3,
        dataLabels: {
          total: {
            enabled: true,
            style: { fontSize: '11px', fontWeight: 700, color: '#334155' },
            formatter: v => v > 0 ? Number(v).toLocaleString() : ''
          }
        }
      }
    },
    colors: colors,
    xaxis: {
      categories: stackedData.categories,
      labels: { style: { fontSize: '12px', fontWeight: 600 } }
    },
    yaxis: {
      title: { text: 'Số lượng phế phẩm lỗi (Cuộn)' },
      labels: { formatter: v => Math.round(v) }
    },
    legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
    fill: { opacity: 1 },
    tooltip: {
      shared: true,
      intersect: false,
      y: { formatter: v => Number(v).toLocaleString() + ' cuộn' }
    }
  };

  if (charts.stackedDefects) charts.stackedDefects.destroy();
  charts.stackedDefects = new ApexCharts(document.querySelector("#chartDefectStacked"), options);
  charts.stackedDefects.render();
}

// Biểu đồ So sánh máy đùn
function renderExtrusionChart(extData) {
  const machines = extData.map(d => d.machine);
  const rates = extData.map(d => d.yield_rate);

  const options = {
    series: [{ name: 'Tỉ lệ thành phẩm (%)', data: rates }],
    chart: { height: 290, type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4, distributed: true } },
    colors: rates.map(r => r >= 98 ? '#16a34a' : (r >= 95 ? '#d97706' : '#dc2626')),
    xaxis: { categories: machines, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
    yaxis: { min: 80, max: 100, labels: { formatter: v => v.toFixed(0) + '%' } },
    legend: { show: false },
    tooltip: { y: { formatter: v => v + '%' } }
  };

  if (charts.extrusion) charts.extrusion.destroy();
  charts.extrusion = new ApexCharts(document.querySelector("#chartExtrusion"), options);
  charts.extrusion.render();
}

// Biểu đồ Size
function renderSizeChart(sizeData) {
  const sizes = sizeData.map(d => d.size);
  const rates = sizeData.map(d => d.yield_rate);

  const options = {
    series: [{ name: 'Tỉ lệ thành phẩm (%)', data: rates }],
    chart: { height: 290, type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4 } },
    colors: ['#0284c7'],
    xaxis: { min: 85, max: 100, labels: { formatter: v => v.toFixed(0) + '%' } },
    yaxis: { categories: sizes, labels: { style: { fontWeight: 600 } } },
    tooltip: { y: { formatter: v => v + '%' } }
  };

  if (charts.size) charts.size.destroy();
  charts.size = new ApexCharts(document.querySelector("#chartSize"), options);
  charts.size.render();
}

// Hiển thị Chi Tiết Theo Từng Line Máy Đùn (Multi-Line Chart View)
function renderMultiLineCards(linesData) {
  const container = document.getElementById('lineChartsContainer');
  const badge = document.getElementById('badgeMultiLineCount');
  if (!container) return;

  if (badge) badge.textContent = `${linesData.length} Lines`;

  // Destroy previous instances
  Object.keys(multiLineChartInstances).forEach(k => {
    try { multiLineChartInstances[k].destroy(); } catch(e){}
  });
  multiLineChartInstances = {};

  if (!linesData || linesData.length === 0) {
    container.innerHTML = '<div class="col-12 text-center py-4 text-muted">Không có dữ liệu line máy đùn phù hợp.</div>';
    return;
  }

  let html = '';
  linesData.forEach(line => {
    const mac = line.machine;
    const cardId = `chart_line_${mac}`;
    const isPass = line.yield_rate >= line.benchmark_rate;
    const isDanger = line.yield_rate < 95.0;
    const stBadgeCl = isPass ? 'bg-success' : (isDanger ? 'bg-danger' : 'bg-warning text-dark');
    const stBadgeTxt = isPass ? '✓ Đạt chuẩn' : (isDanger ? '🔴 Bất thường' : '🟡 Cảnh báo');

    html += `
      <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card border shadow-sm h-100" style="background: var(--dx-bg-card, #ffffff);">
          <div class="card-header bg-transparent py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <span class="material-icons text-primary" style="font-size: 20px;">precision_manufacturing</span>
              <strong class="text-primary fs-6">Line: ${escapeHtml(mac)}</strong>
            </div>
            <div class="d-flex align-items-center gap-1">
              <span class="badge ${stBadgeCl} px-2 py-1" style="font-size: 11px;">${stBadgeTxt} (${line.yield_rate}%)</span>
              <span class="badge bg-light text-muted border" style="font-size: 11px;">Target: ${line.benchmark_rate}%</span>
            </div>
          </div>
          <div class="card-body p-3">
            <!-- Stats Summary Row -->
            <div class="row g-1 text-center small mb-2 p-2 bg-light rounded border">
              <div class="col-3 border-end">
                <div class="text-muted" style="font-size: 10px;">SẢN LƯỢNG</div>
                <strong class="text-dark">${Number(line.total_produced).toLocaleString()}</strong>
              </div>
              <div class="col-3 border-end">
                <div class="text-muted" style="font-size: 10px;">ĐẠT CHUẨN</div>
                <strong class="text-success">${Number(line.total_good).toLocaleString()}</strong>
              </div>
              <div class="col-3 border-end">
                <div class="text-muted" style="font-size: 10px;">PHẾ PHẨM</div>
                <strong class="text-danger">${Number(line.total_defect).toLocaleString()}</strong>
              </div>
              <div class="col-3">
                <div class="text-muted" style="font-size: 10px;">TL THÀNH PHẨM</div>
                <strong class="${isPass ? 'text-success' : 'text-danger'}">${line.yield_rate}%</strong>
              </div>
            </div>

            <!-- Material Breakdown Progress -->
            <div class="mb-2">
              <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 11px;">
                <span><span class="text-success fw-bold">Zin:</span> ${Number(line.virgin_qty).toLocaleString()} cuộn (${line.virgin_pct}%)</span>
                <span><span class="text-primary fw-bold">Nghiền:</span> ${Number(line.recycled_qty).toLocaleString()} cuộn (${line.recycled_pct}%)</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: ${line.virgin_pct}%" title="Nguyên sinh: ${line.virgin_pct}%"></div>
                <div class="progress-bar bg-primary" role="progressbar" style="width: ${line.recycled_pct}%" title="Nghiền: ${line.recycled_pct}%"></div>
              </div>
            </div>

            <!-- Defect Badges Row -->
            <div class="d-flex align-items-center justify-content-between gap-1 mb-2 p-1 border rounded bg-white" style="font-size: 11px;">
              <span class="text-muted fw-semibold ps-1">Lỗi:</span>
              <span class="badge ${line.defects.a1 > 0 ? 'bg-danger' : 'bg-light text-muted'}" title="A1 Ngoại quan">A1: ${line.defects.a1}</span>
              <span class="badge ${line.defects.a2 > 0 ? 'bg-danger' : 'bg-light text-muted'}" title="A2 Vượt trên">A2: ${line.defects.a2}</span>
              <span class="badge ${line.defects.a3 > 0 ? 'bg-danger' : 'bg-light text-muted'}" title="A3 Vượt dưới">A3: ${line.defects.a3}</span>
              <span class="badge ${line.defects.a4 > 0 ? 'bg-danger' : 'bg-light text-muted'}" title="A4 Độ dẹt">A4: ${line.defects.a4}</span>
              <span class="badge ${line.defects.a5 > 0 ? 'bg-danger' : 'bg-light text-muted'}" title="A5 Lỗi khác">A5: ${line.defects.a5}</span>
            </div>

            <!-- Mini Trend Chart -->
            <div id="${cardId}" style="min-height: 140px;"></div>
          </div>
        </div>
      </div>
    `;
  });
  container.innerHTML = html;

  // Render ApexCharts for each line
  setTimeout(() => {
    linesData.forEach(line => {
      const mac = line.machine;
      const elem = document.querySelector(`#chart_line_${mac}`);
      if (!elem) return;

      const dates = (line.daily_trend || []).map(d => d.date);
      const rates = (line.daily_trend || []).map(d => d.rate);

      const options = {
        chart: {
          height: 140,
          type: 'line',
          toolbar: { show: false },
          sparkline: { enabled: false },
          zoom: { enabled: false }
        },
        series: [{ name: 'Tỉ lệ TP (%)', data: rates }],
        stroke: { curve: 'smooth', width: 2.5 },
        colors: [line.yield_rate >= line.benchmark_rate ? '#16a34a' : (line.yield_rate >= 95.0 ? '#d97706' : '#dc2626')],
        xaxis: {
          categories: dates,
          labels: { show: false },
          tooltip: { enabled: false }
        },
        yaxis: {
          min: 80,
          max: 100,
          labels: { show: true, style: { fontSize: '10px' }, formatter: v => Math.round(v) + '%' }
        },
        annotations: {
          yaxis: [{
            y: line.benchmark_rate,
            borderColor: '#dc2626',
            strokeDashArray: 3,
            label: {
              borderColor: '#dc2626',
              style: { color: '#fff', background: '#dc2626', fontSize: '9px' },
              text: `${line.benchmark_rate}%`
            }
          }]
        },
        tooltip: {
          x: { formatter: (val, opts) => dates[opts.dataPointIndex] || '' },
          y: { formatter: v => v + '%' }
        }
      };

      const chart = new ApexCharts(elem, options);
      chart.render();
      multiLineChartInstances[mac] = chart;
    });
  }, 100);
}

// =========================================================================
// 3. MA TRẬN TỈ LỆ THÀNH PHẨM (良品率 MATRIX)
// =========================================================================
async function loadMatrixData() {
  const params = getFilterParams();
  params.set('action', 'get_dashboard');

  try {
    const res = await fetch(`api/quality_yield.php?${params.toString()}`);
    const data = await res.json();
    if (!data.success || !data.matrix) return;

    const m = data.matrix;
    const headerRow = document.getElementById('matrixHeaderRow');
    let hHtml = '<th class="sticky-col">Máy Đùn</th>';
    for (let d = 1; d <= m.days_in_month; d++) {
      hHtml += `<th>${d}</th>`;
    }
    hHtml += '<th style="min-width: 65px; background: #e0f2fe; color: #0369a1;">TB Tháng</th>';
    headerRow.innerHTML = hHtml;

    // Body rows
    const tbody = document.getElementById('matrixTableBody');
    let bHtml = '';
    m.machines.forEach(mac => {
      const avg = m.machine_averages[mac];
      const avgClass = (avg !== null) ? (avg >= 98 ? 'cell-rate-pass' : (avg >= 95 ? 'cell-rate-warn' : 'cell-rate-danger')) : '';
      bHtml += `<tr><td class="sticky-col fw-bold">${escapeHtml(mac)}</td>`;

      for (let d = 1; d <= m.days_in_month; d++) {
        const val = m.grid[mac][d];
        if (val !== null && val !== undefined) {
          const cl = val >= 98 ? 'cell-rate-pass' : (val >= 95 ? 'cell-rate-warn' : 'cell-rate-danger');
          bHtml += `<td class="${cl}" title="Máy ${mac} ngày ${d}: ${val}%">${val}</td>`;
        } else {
          bHtml += '<td class="text-muted" style="color: #cbd5e1 !important;">-</td>';
        }
      }
      bHtml += `<td class="${avgClass} fw-bold">${avg !== null ? avg + '%' : '-'}</td></tr>`;
    });
    tbody.innerHTML = bHtml;

    // Foot row (Day averages)
    const tfoot = document.getElementById('matrixTableFoot');
    let fHtml = '<tr><td class="sticky-col fw-bold bg-light">TB Ngày</td>';
    for (let d = 1; d <= m.days_in_month; d++) {
      const dt = m.day_totals[d];
      if (dt !== null && dt !== undefined) {
        const cl = dt >= 98 ? 'cell-rate-pass' : (dt >= 95 ? 'cell-rate-warn' : 'cell-rate-danger');
        fHtml += `<td class="${cl} fw-bold">${dt}</td>`;
      } else {
        fHtml += '<td class="text-muted">-</td>';
      }
    }
    fHtml += `<td class="cell-rate-pass fw-bold text-primary fs-6">${data.kpi.yield_rate}%</td></tr>`;
    tfoot.innerHTML = fHtml;

  } catch(e) {
    console.error('Error loadMatrixData:', e);
  }
}

// =========================================================================
// 4. DANH SÁCH BẢN GHI THÀNH PHẨM (CHI TIẾT & NHẬP LIỆU)
// =========================================================================
async function loadRecordsData(page = 1) {
  recordsCurrentPage = page;
  const params = getFilterParams();
  params.set('action', 'get_records');
  params.set('page', page);
  params.set('limit', 25);

  const searchVal = document.getElementById('recordsSearchInput').value.trim();
  if (searchVal) params.set('search', searchVal);

  const tbody = document.getElementById('recordsTableBody');
  tbody.innerHTML = '<tr><td colspan="20" class="text-center py-4 text-muted">Đang tải dữ liệu lô thành phẩm...</td></tr>';

  try {
    const res = await fetch(`api/quality_yield.php?${params.toString()}`);
    const json = await res.json();
    if (!json.success) return;

    document.getElementById('recordsTotalBadge').textContent = `${json.pagination.total_records} bản ghi`;

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="20" class="text-center py-4 text-muted">Không tìm thấy lô thành phẩm nào phù hợp.</td></tr>';
      document.getElementById('recordsPagination').innerHTML = '';
      return;
    }

    let html = '';
    const startIndex = (json.pagination.current_page - 1) * json.pagination.limit + 1;
    json.data.forEach((r, idx) => {
      const isPass = r.yield_rate >= r.benchmark_rate;
      const isDanger = r.yield_rate < 95.0;
      const badgeCl = isPass ? 'badge-yield-pass' : (isDanger ? 'badge-yield-danger' : 'badge-yield-warn');
      const badgeTxt = isPass ? '✓ Đạt chuẩn' : (isDanger ? '🔴 Bất thường' : '🟡 Cảnh báo');

      const isVirgin = (r.material_group === 'virgin');
      const matBadgeCl = isVirgin ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle';
      const matBadgeTxt = isVirgin ? 'Zin' : 'Nghiền';

      // Nút tạo phiếu điều tra nếu có bất thường
      const btnInvestigate = (isDanger || !isPass) ? `
        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 d-inline-flex align-items-center" onclick='openCreateInvFromRecord(${JSON.stringify(r)})' title="Tạo phiếu yêu cầu điều tra nguyên nhân">
          <span class="material-icons" style="font-size: 13px;">report_problem</span> Điều tra
        </button>
      ` : '';

      html += `<tr>
        <td class="text-muted fw-bold">#${startIndex + idx}</td>
        <td>${r.komaki_date}</td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(r.komaki_machine)}</span></td>
        <td><strong>${escapeHtml(r.size)}</strong></td>
        <td><span class="font-monospace fw-bold text-primary">${escapeHtml(r.lot_no)}</span></td>
        <td class="text-center"><span class="badge ${matBadgeCl} px-2 py-1" style="font-size: 11px;">${matBadgeTxt}</span></td>
        <td><small>${escapeHtml(r.product_code)}</small></td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(r.extrusion_machine)}</span></td>
        <td><small class="text-muted">${r.extrusion_date || '-'}</small></td>
        <td><small class="font-monospace fw-bold text-dark">${r.bobbin_time || '-'}</small></td>
        <td class="text-end fw-bold text-success">${r.good_qty}</td>
        <td class="text-end">${r.total_qty}</td>
        <td class="text-end fw-bold ${isPass ? 'text-success' : 'text-danger'}">${r.yield_rate}%</td>
        <td class="text-end ${r.defect_a1 > 0 ? 'text-danger fw-bold' : 'text-muted'}">${r.defect_a1}</td>
        <td class="text-end ${r.defect_a2 > 0 ? 'text-danger fw-bold' : 'text-muted'}">${r.defect_a2}</td>
        <td class="text-end ${r.defect_a3 > 0 ? 'text-danger fw-bold' : 'text-muted'}">${r.defect_a3}</td>
        <td class="text-end ${r.defect_a4 > 0 ? 'text-danger fw-bold' : 'text-muted'}">${r.defect_a4}</td>
        <td class="text-end ${r.defect_a5 > 0 ? 'text-danger fw-bold' : 'text-muted'}">${r.defect_a5}</td>
        <td class="text-center"><span class="badge ${badgeCl} px-2 py-1" style="font-size: 10px;">${badgeTxt}</span></td>
        <td class="text-center">
          <div class="d-flex align-items-center justify-content-center gap-1">
            ${btnInvestigate}
            <?php if ($canManageQuality): ?>
            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" onclick='openEditRecordModal(${JSON.stringify(r)})' title="Sửa">
              <span class="material-icons" style="font-size: 13px;">edit</span>
            </button>
            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="deleteRecordItem(${r.id})" title="Xóa">
              <span class="material-icons" style="font-size: 13px;">delete</span>
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>`;
    });
    tbody.innerHTML = html;

    renderPaginationUi(json.pagination, 'recordsPagination', 'loadRecordsData');

  } catch(e) {
    console.error('Error loadRecordsData:', e);
  }
}

function renderPaginationUi(p, containerId, fnName) {
  const container = document.getElementById(containerId);
  if (!container || p.total_pages <= 1) {
    if (container) container.innerHTML = '';
    return;
  }

  let html = `
    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2 text-muted small">
      <div>Trang ${p.current_page} / ${p.total_pages} (Tổng ${p.total_records} dòng)</div>
      <div class="d-flex gap-1">
  `;

  if (p.current_page > 1) {
    html += `<button class="app-btn app-btn-outline app-btn-xs" onclick="${fnName}(${p.current_page - 1})">Trước</button>`;
  }
  for (let i = Math.max(1, p.current_page - 2); i <= Math.min(p.total_pages, p.current_page + 2); i++) {
    html += `<button class="app-btn app-btn-xs ${i === p.current_page ? 'app-btn-primary' : 'app-btn-outline'}" onclick="${fnName}(${i})">${i}</button>`;
  }
  if (p.current_page < p.total_pages) {
    html += `<button class="app-btn app-btn-outline app-btn-xs" onclick="${fnName}(${p.current_page + 1})">Sau</button>`;
  }
  html += `</div></div>`;
  container.innerHTML = html;
}

// =========================================================================
// 5. MODAL RECORD (THÊM / SỬA THÀNH PHẨM)
// =========================================================================
let recordModalInstance = null;
function getRecordModal() {
  if (!recordModalInstance) recordModalInstance = new bootstrap.Modal(document.getElementById('modalRecordForm'));
  return recordModalInstance;
}

function openAddRecordModal() {
  document.getElementById('formRecordModal').reset();
  document.getElementById('recId').value = '0';
  document.getElementById('modalRecordTitle').innerHTML = '<span class="material-icons">add_circle</span> Thêm Mới Lô Thành Phẩm';
  document.getElementById('recKomakiDate').value = new Date().toISOString().split('T')[0];
  document.getElementById('recKomakiMachine').value = 'ST01';
  document.getElementById('recExtMachine').value = 'PL08';
  document.getElementById('recGoodQty').value = 0;
  document.getElementById('recTotalQty').value = 0;
  document.getElementById('recYieldRate').value = '100.00';
  document.getElementById('recDefectQty').value = 0;
  document.getElementById('recBobbinTime').value = '';
  document.getElementById('recMaterialGroup').value = 'virgin';
  const notice = document.getElementById('recMaterialNotice');
  if (notice) notice.textContent = '';
  getRecordModal().show();
}

function openEditRecordModal(r) {
  document.getElementById('formRecordModal').reset();
  document.getElementById('recId').value = r.id;
  document.getElementById('modalRecordTitle').innerHTML = `<span class="material-icons">edit_note</span> Sửa Lô Thành Phẩm: <strong>${escapeHtml(r.lot_no)}</strong>`;
  document.getElementById('recKomakiDate').value = r.komaki_date || '';
  document.getElementById('recKomakiMachine').value = r.komaki_machine || 'ST01';
  document.getElementById('recSize').value = r.size || '';
  document.getElementById('recLotNo').value = r.lot_no || '';
  document.getElementById('recProductCode').value = r.product_code || '';
  document.getElementById('recExtMachine').value = r.extrusion_machine || 'PL08';
  document.getElementById('recExtDate').value = r.extrusion_date || '';
  document.getElementById('recBobbinTime').value = r.bobbin_time || '';
  document.getElementById('recMaterialGroup').value = r.material_group || 'virgin';
  detectMaterialGroupFromLot(r.lot_no);
  document.getElementById('recGoodQty').value = r.good_qty;
  document.getElementById('recTotalQty').value = r.total_qty;
  document.getElementById('recYieldRate').value = r.yield_rate;
  document.getElementById('recDefectQty').value = r.defect_qty;
  document.getElementById('recDefA1').value = r.defect_a1 || 0;
  document.getElementById('recDefA2').value = r.defect_a2 || 0;
  document.getElementById('recDefA3').value = r.defect_a3 || 0;
  document.getElementById('recDefA4').value = r.defect_a4 || 0;
  document.getElementById('recDefA5').value = r.defect_a5 || 0;
  document.getElementById('recNote').value = r.note || '';
  getRecordModal().show();
}

function detectMaterialGroupFromLot(lot) {
  const notice = document.getElementById('recMaterialNotice');
  const sel = document.getElementById('recMaterialGroup');
  if (!lot || lot.length < 2) {
    if (notice) notice.textContent = '';
    return;
  }
  const char2 = lot.charAt(1).toUpperCase();
  const rule = materialRulesList.find(r => r.char_code.toUpperCase() === char2);
  if (rule) {
    if (sel) sel.value = rule.material_group;
    if (notice) notice.innerHTML = `✓ Ký tự '${char2}': <strong>${escapeHtml(rule.material_name)}</strong> (${rule.material_group === 'virgin' ? 'Zin' : 'Nghiền'})`;
  } else {
    if (notice) notice.innerHTML = `Ký tự '${char2}': Chưa có quy tắc phân loại`;
  }
}

function calculateRecordYield() {
  const good = parseInt(document.getElementById('recGoodQty').value) || 0;
  const total = parseInt(document.getElementById('recTotalQty').value) || 0;
  const rateInput = document.getElementById('recYieldRate');
  const defectInput = document.getElementById('recDefectQty');

  if (total > 0) {
    const rate = Math.min(100, Math.max(0, (good / total) * 100));
    rateInput.value = rate.toFixed(2);
    defectInput.value = Math.max(0, total - good);
  } else {
    rateInput.value = '100.00';
    defectInput.value = 0;
  }
}

async function submitRecordModal(e) {
  e.preventDefault();
  const form = document.getElementById('formRecordModal');
  const fd = new FormData(form);
  fd.append('action', 'save_record');

  const btn = document.getElementById('btnSaveRecord');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      getRecordModal().hide();
      loadRecordsData(recordsCurrentPage);
      loadDashboardData();
    } else {
      alert(json.message || 'Lỗi khi lưu bản ghi.');
    }
  } catch(err) {
    console.error(err);
    alert('Lỗi kết nối khi lưu bản ghi.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Bản Ghi';
  }
}

async function deleteRecordItem(id) {
  if (!confirm('Xác nhận xóa bản ghi thành phẩm này?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_record');
  fd.append('id', id);

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      loadRecordsData(recordsCurrentPage);
      loadDashboardData();
    } else {
      alert(json.message || 'Không thể xóa bản ghi.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi xóa.');
  }
}

// =========================================================================
// 6. PHIẾU YÊU CẦU ĐIỀU TRA & ĐỐI ỨNG (INVESTIGATIONS)
// =========================================================================
async function loadInvestigationsData() {
  const tbody = document.getElementById('investigationsTableBody');
  tbody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-muted">Đang tải danh sách phiếu điều tra...</td></tr>';

  try {
    const res = await fetch('api/quality_yield.php?action=get_investigations');
    const json = await res.json();
    if (!json.success) return;

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-muted">Chưa có phiếu yêu cầu điều tra nào.</td></tr>';
      return;
    }

    let html = '';
    json.data.forEach(inv => {
      let stClass = 'bg-secondary';
      if (inv.result_status === 'Hoàn thành') stClass = 'bg-success';
      else if (inv.result_status === 'Đang xử lý') stClass = 'bg-primary';
      else if (inv.result_status === 'Chờ điều tra') stClass = 'bg-danger';
      else if (inv.result_status === 'Cần theo dõi thêm') stClass = 'bg-warning text-dark';

      html += `<tr>
        <td><strong class="font-monospace text-primary">${escapeHtml(inv.investigation_code)}</strong></td>
        <td>${escapeHtml(inv.investigation_date)}</td>
        <td class="fw-bold">${escapeHtml(inv.size)}</td>
        <td><small>${escapeHtml(inv.product_code)}</small></td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(inv.extrusion_machine || '-')}</span></td>
        <td><span class="font-monospace">${escapeHtml(inv.lot_no || '-')}</span></td>
        <td class="text-end fw-bold text-danger">${inv.yield_rate}%</td>
        <td><small class="fw-bold text-danger">${escapeHtml(inv.status_description)}</small></td>
        <td><small class="text-muted">${escapeHtml(inv.root_cause || 'Chưa cập nhật')}</small></td>
        <td><small class="text-success">${escapeHtml(inv.countermeasure || 'Chưa có đối sách')}</small></td>
        <td><small class="fw-bold">${escapeHtml(inv.assigned_to || '-')}</small></td>
        <td class="text-center"><span class="badge ${stClass} px-2 py-1 small">${escapeHtml(inv.result_status)}</span></td>
        <td class="text-center">
          <div class="d-flex align-items-center justify-content-center gap-1">
            <?php if ($canInvestigate): ?>
            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" onclick='openEditInvModal(${JSON.stringify(inv)})' title="Cập nhật tiến độ / đối ứng">
              <span class="material-icons" style="font-size: 13px;">edit</span>
            </button>
            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="deleteInvItem(${inv.id})" title="Xóa phiếu">
              <span class="material-icons" style="font-size: 13px;">delete</span>
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>`;
    });
    tbody.innerHTML = html;

  } catch(e) {
    console.error('Error loadInvestigationsData:', e);
  }
}

let invModalInstance = null;
function getInvModal() {
  if (!invModalInstance) invModalInstance = new bootstrap.Modal(document.getElementById('modalInvestigationForm'));
  return invModalInstance;
}

function openCreateInvestigationModal() {
  document.getElementById('formInvModal').reset();
  document.getElementById('invId').value = '0';
  document.getElementById('invYieldRecordId').value = '';
  document.getElementById('modalInvTitle').innerHTML = '<span class="material-icons">add_alert</span> Tạo Phiếu Yêu Cầu Điều Tra Mới';
  document.getElementById('invResultStatus').value = 'Chờ điều tra';
  getInvModal().show();
}

function openCreateInvFromRecord(r) {
  document.getElementById('formInvModal').reset();
  document.getElementById('invId').value = '0';
  document.getElementById('invYieldRecordId').value = r.id;
  document.getElementById('modalInvTitle').innerHTML = `<span class="material-icons">report_problem</span> Phiếu Điều Tra Bất Thường Cho Lô: <strong>${escapeHtml(r.lot_no)}</strong>`;
  
  // Đính kèm tự động dữ liệu tại thời điểm đó
  document.getElementById('invLotNo').value = r.lot_no || '';
  document.getElementById('invSize').value = r.size || '';
  document.getElementById('invProductCode').value = r.product_code || '';
  document.getElementById('invExtMachine').value = r.extrusion_machine || '';
  document.getElementById('invExtDate').value = r.extrusion_date || '';
  document.getElementById('invKomDate').value = r.komaki_date || '';
  document.getElementById('invYieldRate').value = r.yield_rate || 0;
  document.getElementById('invStatusDesc').value = `Tỉ lệ thành phẩm thấp (${r.yield_rate}% so với benchmark ${r.benchmark_rate}%)`;
  document.getElementById('invResultStatus').value = 'Chờ điều tra';
  getInvModal().show();
}

function openEditInvModal(inv) {
  document.getElementById('formInvModal').reset();
  document.getElementById('invId').value = inv.id;
  document.getElementById('invYieldRecordId').value = inv.yield_record_id || '';
  document.getElementById('modalInvTitle').innerHTML = `<span class="material-icons">edit_note</span> Cập Nhật Phiếu Điều Tra: <strong>${escapeHtml(inv.investigation_code)}</strong>`;

  document.getElementById('invLotNo').value = inv.lot_no || '';
  document.getElementById('invSize').value = inv.size || '';
  document.getElementById('invProductCode').value = inv.product_code || '';
  document.getElementById('invExtMachine').value = inv.extrusion_machine || '';
  document.getElementById('invExtDate').value = inv.extrusion_date || '';
  document.getElementById('invKomDate').value = inv.komaki_date || '';
  document.getElementById('invYieldRate').value = inv.yield_rate || 0;
  document.getElementById('invStatusDesc').value = inv.status_description || '';
  document.getElementById('invAssignedTo').value = inv.assigned_to || '';
  document.getElementById('invResultStatus').value = inv.result_status || 'Chờ điều tra';
  document.getElementById('invRootCause').value = inv.root_cause || '';
  document.getElementById('invCountermeasure').value = inv.countermeasure || '';
  getInvModal().show();
}

async function submitInvestigationModal(e) {
  e.preventDefault();
  const form = document.getElementById('formInvModal');
  const fd = new FormData(form);
  fd.append('action', 'save_investigation');

  const btn = document.getElementById('btnSaveInv');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      getInvModal().hide();
      loadInvestigationsData();
      loadDashboardData();
    } else {
      alert(json.message || 'Lỗi khi lưu phiếu điều tra.');
    }
  } catch(err) {
    console.error(err);
    alert('Lỗi kết nối khi lưu phiếu điều tra.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Phiếu Điều Tra';
  }
}

async function deleteInvItem(id) {
  if (!confirm('Xác nhận xóa phiếu điều tra này?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_investigation');
  fd.append('id', id);

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      loadInvestigationsData();
      loadDashboardData();
    } else {
      alert(json.message || 'Không thể xóa phiếu.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi xóa phiếu.');
  }
}

// =========================================================================
// 7. IMPORT EXCEL
// =========================================================================
let importModalInstance = null;
function getImportModal() {
  if (!importModalInstance) importModalInstance = new bootstrap.Modal(document.getElementById('modalImportExcel'));
  return importModalInstance;
}

function openImportModal() {
  document.getElementById('formImportExcel').reset();
  getImportModal().show();
}

async function submitImportExcel(e) {
  e.preventDefault();
  const form = document.getElementById('formImportExcel');
  const fd = new FormData(form);
  fd.append('action', 'import_excel');

  const btn = document.getElementById('btnSubmitImport');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang phân tích file Excel & đồng bộ...';

  try {
    const res = await fetch('api/quality_export.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      alert(`✅ ${json.message}\n\n• Số dòng thêm mới: ${json.inserted || 0}\n• Số dòng cập nhật: ${json.updated || 0}`);
      getImportModal().hide();
      loadDashboardData();
      loadRecordsData(1);
    } else {
      alert(json.message || 'Lỗi import file.');
    }
  } catch(err) {
    console.error(err);
    alert('Lỗi kết nối khi import Excel.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">cloud_upload</span> Bắt Đầu Import & Đồng Bộ';
  }
}

// =========================================================================
// 8. CẤU HÌNH BENCHMARK
// =========================================================================
let benchmarkModalInstance = null;
function getBenchmarkModal() {
  if (!benchmarkModalInstance) benchmarkModalInstance = new bootstrap.Modal(document.getElementById('modalBenchmark'));
  return benchmarkModalInstance;
}

async function openBenchmarkModal() {
  getBenchmarkModal().show();
  await loadBenchmarksTable();
}

async function loadBenchmarksTable() {
  const tbody = document.getElementById('benchmarkTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Đang tải...</td></tr>';

  try {
    const res = await fetch('api/quality_yield.php?action=get_benchmarks');
    const json = await res.json();
    if (!json.success || !json.data) return;

    let html = '';
    json.data.forEach(b => {
      html += `<tr>
        <td class="fw-bold">${escapeHtml(b.size)}</td>
        <td>${escapeHtml(b.extrusion_machine)}</td>
        <td class="text-end fw-bold text-success">${b.benchmark_rate}%</td>
        <td class="text-end fw-bold text-danger">${b.min_acceptable_rate}%</td>
        <td><small class="text-muted">${escapeHtml(b.description || '-')}</small></td>
        <td><small>${escapeHtml(b.updated_by || 'ADMIN')}</small></td>
      </tr>`;
    });
    tbody.innerHTML = html;
  } catch(e) {
    console.error(e);
  }
}

async function submitBenchmark(e) {
  e.preventDefault();
  const form = document.getElementById('formBenchmark');
  const fd = new FormData(form);
  fd.append('action', 'save_benchmark');

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadBenchmarksTable();
      loadDashboardData();
    } else {
      alert(json.message || 'Lỗi lưu benchmark.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi lưu benchmark.');
  }
}

// =========================================================================
// 9. XUẤT & IN BÁO CÁO PDF (A4 LANDSCAPE)
// =========================================================================
let pdfModalInstance = null;
function getPdfModal() {
  if (!pdfModalInstance) pdfModalInstance = new bootstrap.Modal(document.getElementById('modalPdfPreview'));
  return pdfModalInstance;
}

function openPdfModal() {
  const params = getFilterParams();
  params.set('action', 'render_pdf_html');
  const iframe = document.getElementById('pdfPreviewIframe');
  iframe.src = `api/quality_export.php?${params.toString()}`;
  getPdfModal().show();
}

function printPdfIframe() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe && iframe.contentWindow) {
    iframe.contentWindow.print();
  }
}

// =========================================================================
// 10. CẤU HÌNH QUY TẮC PHÂN LOẠI NGUYÊN VẬT LIỆU (MATERIAL RULES CRUD)
// =========================================================================
let materialRulesModalInstance = null;
function getMaterialRulesModal() {
  if (!materialRulesModalInstance) materialRulesModalInstance = new bootstrap.Modal(document.getElementById('modalMaterialRules'));
  return materialRulesModalInstance;
}

function openMaterialRulesModal() {
  resetMaterialRuleForm();
  getMaterialRulesModal().show();
  loadMaterialRulesTable();
}

function resetMaterialRuleForm() {
  document.getElementById('formMaterialRule').reset();
  document.getElementById('mrId').value = '0';
  document.getElementById('mrCharCode').disabled = false;
  document.getElementById('btnSaveMaterialRule').innerHTML = '<span class="material-icons align-middle" style="font-size: 15px;">save</span> Lưu Quy Tắc';
}

async function loadMaterialRulesTable() {
  const tbody = document.getElementById('materialRulesTableBody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Đang tải danh sách quy tắc...</td></tr>';

  try {
    const res = await fetch('api/quality_yield.php?action=get_material_rules');
    const json = await res.json();
    if (!json.success || !json.data) return;

    materialRulesList = json.data;

    let html = '';
    json.data.forEach(rule => {
      const isVirgin = (rule.material_group === 'virgin');
      const badgeCl = isVirgin ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle';
      const grpLabel = isVirgin ? 'Nguyên sinh (Zin)' : 'Nghiền (Tái sinh)';

      html += `<tr>
        <td class="text-center"><span class="badge bg-dark font-monospace fs-6 px-2 py-1">${escapeHtml(rule.char_code)}</span></td>
        <td><strong>${escapeHtml(rule.material_name)}</strong></td>
        <td class="text-center"><span class="badge ${badgeCl} px-2 py-1">${grpLabel}</span></td>
        <td><small class="text-muted">${escapeHtml(rule.description || '-')}</small></td>
        <td class="text-center">
          <div class="d-flex align-items-center justify-content-center gap-1">
            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" onclick='editMaterialRule(${JSON.stringify(rule)})' title="Sửa">
              <span class="material-icons" style="font-size: 13px;">edit</span>
            </button>
            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="deleteMaterialRuleItem(${rule.id})" title="Xóa">
              <span class="material-icons" style="font-size: 13px;">delete</span>
            </button>
          </div>
        </td>
      </tr>`;
    });
    tbody.innerHTML = html;
  } catch(e) {
    console.error('Error loadMaterialRulesTable:', e);
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Lỗi tải quy tắc phân loại vật liệu.</td></tr>';
  }
}

function editMaterialRule(rule) {
  document.getElementById('mrId').value = rule.id;
  document.getElementById('mrCharCode').value = rule.char_code;
  document.getElementById('mrCharCode').disabled = true;
  document.getElementById('mrMaterialName').value = rule.material_name;
  document.getElementById('mrMaterialGroup').value = rule.material_group;
  document.getElementById('mrDescription').value = rule.description || '';
  document.getElementById('btnSaveMaterialRule').innerHTML = '<span class="material-icons align-middle" style="font-size: 15px;">check</span> Cập Nhật Quy Tắc';
}

async function submitMaterialRule(e) {
  e.preventDefault();
  const form = document.getElementById('formMaterialRule');
  const fd = new FormData(form);
  const cc = document.getElementById('mrCharCode').value.trim().toUpperCase();
  fd.set('char_code', cc);
  fd.append('action', 'save_material_rule');

  const btn = document.getElementById('btnSaveMaterialRule');
  btn.disabled = true;

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      resetMaterialRuleForm();
      await loadMaterialRulesTable();
      loadDashboardData();
    } else {
      alert(json.message || 'Lỗi khi lưu quy tắc.');
    }
  } catch(err) {
    console.error(err);
    alert('Lỗi kết nối khi lưu quy tắc.');
  } finally {
    btn.disabled = false;
  }
}

async function deleteMaterialRuleItem(id) {
  if (!confirm('Xác nhận xóa quy tắc phân loại này?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_material_rule');
  fd.append('id', id);

  try {
    const res = await fetch('api/quality_yield.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      await loadMaterialRulesTable();
      loadDashboardData();
    } else {
      alert(json.message || 'Không thể xóa quy tắc.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi xóa quy tắc.');
  }
}
</script>
