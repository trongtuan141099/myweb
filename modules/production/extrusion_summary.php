<?php
/**
 * Module: Quản Lý & Tổng Hợp Sản Lượng Đùn Ép (Extrusion Production Summary)
 * DX Plastic Group - Production MES System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

// Kiểm tra quyền truy cập
$canView = hasPermission(['production.data', 'production.view', 'production.plan', 'admin']);
$canManage = hasPermission(['production.data', 'production.plan', 'admin']);

$currentMonth = date('Y-m');
$currentYear = intval(date('Y'));
?>

<style>
/* CSS TỐI ƯU GIAO DIỆN PHÂN HỆ ĐÙN ÉP */
.ext-header-box {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 18px 24px;
  margin-bottom: 20px;
  box-shadow: var(--dx-shadow-sm);
}

.ext-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.ext-kpi-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--dx-shadow-sm);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.ext-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}

.ext-kpi-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  flex-shrink: 0;
}

.ext-kpi-val {
  font-size: 24px;
  font-weight: 800;
  line-height: 1.15;
  color: var(--dx-text-main);
  font-family: var(--dx-font-mono, monospace);
}

.ext-kpi-lbl {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--dx-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 4px;
}

.ext-filter-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 16px 20px;
  margin-bottom: 20px;
  box-shadow: var(--dx-shadow-sm);
}

.ext-tabs-nav {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--dx-border);
  margin-bottom: 20px;
  overflow-x: auto;
  white-space: nowrap;
}

.ext-tab-btn {
  padding: 10px 18px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--dx-text-muted);
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.ext-tab-btn:hover {
  color: var(--dx-primary);
}

.ext-tab-btn.active {
  color: var(--dx-primary);
  border-bottom-color: var(--dx-primary);
  font-weight: 700;
}

.ext-table-box {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  box-shadow: var(--dx-shadow-sm);
  overflow: hidden;
}

.ext-table-responsive {
  overflow-x: auto;
  max-height: calc(100vh - 350px);
}

.ext-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12.5px;
  text-align: left;
}

.ext-table th {
  background-color: var(--dx-bg-subtle, #f8fafc);
  color: var(--dx-text-muted, #475569);
  font-weight: 700;
  padding: 10px 14px;
  border-bottom: 1px solid var(--dx-border);
  border-right: 1px solid var(--dx-border);
  position: sticky;
  top: 0;
  z-index: 10;
  white-space: nowrap;
}

.ext-table td {
  padding: 9px 14px;
  border-bottom: 1px solid var(--dx-border);
  border-right: 1px solid var(--dx-border);
  color: var(--dx-text-main, #334155);
  white-space: nowrap;
}

.ext-table tr:hover td {
  background-color: var(--dx-bg-hover, #f1f5f9);
}

.ext-table tfoot td {
  background-color: var(--dx-bg-subtle, #f8fafc);
  font-weight: 800;
  border-top: 2px solid var(--dx-border);
}

.ext-chart-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 18px 20px;
  box-shadow: var(--dx-shadow-sm);
  height: 100%;
}

.ext-dropzone {
  border: 2px dashed var(--dx-primary, #0d6efd);
  border-radius: var(--dx-radius-md);
  padding: 40px 20px;
  text-align: center;
  background: rgba(13, 110, 253, 0.02);
  cursor: pointer;
  transition: all 0.2s ease;
}

.ext-dropzone:hover, .ext-dropzone.dragover {
  background: rgba(13, 110, 253, 0.08);
  border-color: #0b5ed7;
}

.badge-size-ao {
  background-color: rgba(13, 110, 253, 0.12);
  color: #0d6efd;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid rgba(13, 110, 253, 0.25);
  font-family: var(--dx-font-mono, monospace);
  font-size: 11.5px;
}
</style>

<div class="app-page-wrapper p-3">
  <!-- 1. Header Bar -->
  <div class="ext-header-box d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">PHÂN HỆ MES SẢN XUẤT</span>
        <span class="text-muted small">| Xưởng Đùn Nhựa V61 - SMC Plastic</span>
      </div>
      <h4 class="fw-bold m-0 d-flex align-items-center gap-2">
        <span class="material-icons text-primary fs-3">stacked_bar_chart</span>
        Tổng Hợp & Báo Cáo Sản Lượng Đùn Ép
      </h4>
      <p class="text-muted small m-0 mt-1">
        Quản lý thực tích, chuẩn hóa Size ống theo công thức AO & tổng hợp sản lượng đa chiều từ tệp Excel
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button class="app-btn app-btn-secondary btn-sm" onclick="reloadCurrentTab()">
        <span class="material-icons fs-6">refresh</span> Làm mới
      </button>
      <button class="app-btn app-btn-secondary btn-sm text-primary" id="btnSyncExtrusion" onclick="triggerSyncData()" title="Đồng bộ dữ liệu từ bảng chuẩn Actual Logs sang Productions">
        <span class="material-icons fs-6">sync</span> Đồng bộ dữ liệu
      </button>
      <button class="app-btn app-btn-primary btn-sm" onclick="switchExtTab('import')">
        <span class="material-icons fs-6">cloud_upload</span> Import Excel
      </button>
      <button class="app-btn app-btn-success btn-sm" onclick="switchExtTab('export')">
        <span class="material-icons fs-6">file_download</span> Xuất Báo Cáo
      </button>
    </div>
  </div>

  <!-- 2. Global Filter Bar -->
  <div class="ext-filter-card">
    <div class="row g-2 align-items-end">
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Từ ngày</label>
        <input type="date" id="filterDateFrom" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Đến ngày</label>
        <input type="date" id="filterDateTo" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Năm</label>
        <select id="filterYear" class="form-select form-control-sm" onchange="handleYearFilterChange()">
          <option value="">-- Tất cả năm --</option>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Tháng</label>
        <input type="month" id="filterMonth" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Size Ống (Col AO)</label>
        <select id="filterPipeSize" class="form-select form-control-sm">
          <option value="all">-- Tất cả Size --</option>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Máy Sản Xuất</label>
        <select id="filterMachine" class="form-select form-control-sm">
          <option value="all">-- Tất cả Máy --</option>
        </select>
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Mã Sản Phẩm / Từ Khóa</label>
        <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Nhập mã SP, mã SX, mã CTSX...">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small fw-bold text-muted mb-1">Phân Xưởng</label>
        <select id="filterWorkshop" class="form-select form-control-sm">
          <option value="all">-- Tất cả Phân Xưởng --</option>
        </select>
      </div>
      <div class="col-md-6 col-sm-12 d-flex gap-2 justify-content-end">
        <button class="app-btn app-btn-secondary btn-sm" onclick="resetExtFilters()">
          <span class="material-icons fs-6">restart_alt</span> Đặt lại
        </button>
        <button class="app-btn app-btn-primary btn-sm px-3" onclick="applyExtFilters()">
          <span class="material-icons fs-6">filter_alt</span> Áp Dụng Lọc
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Navigation Tabs -->
  <div class="ext-tabs-nav">
    <button class="ext-tab-btn active" id="tabBtn-dashboard" onclick="switchExtTab('dashboard')">
      <span class="material-icons fs-5">dashboard</span> Dashboard Tổng Hợp
    </button>
    <button class="ext-tab-btn" id="tabBtn-summary" onclick="switchExtTab('summary')">
      <span class="material-icons fs-5">table_chart</span> Báo Cáo Tổng Hợp Sản Lượng
    </button>
    <button class="ext-tab-btn" id="tabBtn-details" onclick="switchExtTab('details')">
      <span class="material-icons fs-5">list_alt</span> Dữ Liệu Chi Tiết Đùn Ép
    </button>
    <button class="ext-tab-btn" id="tabBtn-import" onclick="switchExtTab('import')">
      <span class="material-icons fs-5">cloud_upload</span> Import Dữ Liệu Excel
    </button>
    <button class="ext-tab-btn" id="tabBtn-export" onclick="switchExtTab('export')">
      <span class="material-icons fs-5">file_download</span> Trung Tâm Xuất Báo Cáo
    </button>
  </div>

  <!-- ===================================================================== -->
  <!-- TAB 1: DASHBOARD TỔNG HỢP                                             -->
  <!-- ===================================================================== -->
  <div id="tabContent-dashboard" class="tab-pane-content">
    <!-- 4 Thẻ KPI Cards -->
    <div class="ext-kpi-grid">
      <div class="ext-kpi-card">
        <div class="ext-kpi-icon" style="background: rgba(13, 110, 253, 0.12); color: #0d6efd;">
          <span class="material-icons">linear_scale</span>
        </div>
        <div>
          <div class="ext-kpi-val text-primary" id="kpiTotalLength">0 <span class="fs-6 fw-normal">m</span></div>
          <div class="ext-kpi-lbl">Tổng Sản Lượng (Mét)</div>
        </div>
      </div>
      <div class="ext-kpi-card">
        <div class="ext-kpi-icon" style="background: rgba(25, 135, 84, 0.12); color: #198754;">
          <span class="material-icons">scale</span>
        </div>
        <div>
          <div class="ext-kpi-val text-success" id="kpiTotalWeight">0 <span class="fs-6 fw-normal">kg</span></div>
          <div class="ext-kpi-lbl">Tổng Trọng Lượng (Kg)</div>
        </div>
      </div>
      <div class="ext-kpi-card">
        <div class="ext-kpi-icon" style="background: rgba(255, 193, 7, 0.15); color: #b45309;">
          <span class="material-icons">inventory_2</span>
        </div>
        <div>
          <div class="ext-kpi-val text-warning-emphasis" id="kpiTotalCoils">0 <span class="fs-6 fw-normal">cuộn</span></div>
          <div class="ext-kpi-lbl">Tổng Số Cuộn (Bobin)</div>
        </div>
      </div>
      <div class="ext-kpi-card">
        <div class="ext-kpi-icon" style="background: rgba(111, 66, 193, 0.12); color: #6f42c1;">
          <span class="material-icons">straighten</span>
        </div>
        <div>
          <div class="ext-kpi-val" style="color: #6f42c1;" id="kpiCountSizes">0 <span class="fs-6 fw-normal">Sizes</span></div>
          <div class="ext-kpi-lbl">Số Chủng Loại Size Đang SX</div>
        </div>
      </div>
    </div>

    <!-- 4 Biểu Đồ Trực Quan -->
    <div class="row g-3 mb-4">
      <div class="col-lg-8">
        <div class="ext-chart-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons fs-5 text-primary">calendar_month</span>
              Sản Lượng Đùn Ép Theo Tháng (Mét & Kg)
            </h6>
            <small class="text-muted">Tổng mét thành phẩm & khối lượng</small>
          </div>
          <div id="chartMonthlyTrend" style="min-height: 310px;"></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="ext-chart-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons fs-5 text-info">donut_large</span>
              Tỷ Trọng Sản Lượng Theo Size
            </h6>
          </div>
          <div id="chartSizeDistribution" style="min-height: 310px;"></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ext-chart-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons fs-5 text-success">bar_chart</span>
              Top 10 Size Sản Xuất Nhiều Nhất (Mét)
            </h6>
          </div>
          <div id="chartTopSizes" style="min-height: 310px;"></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ext-chart-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              <span class="material-icons fs-5 text-warning">precision_manufacturing</span>
              Sản Lượng Phân Bổ Theo Máy Sản Xuất
            </h6>
          </div>
          <div id="chartMachineOutput" style="min-height: 310px;"></div>
        </div>
      </div>
    </div>

    <!-- 2 Bảng Top 10 -->
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="ext-table-box p-3">
          <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
            <span class="material-icons fs-5">stars</span>
            Bảng Top 10 Size Ống Dẫn Đầu Sản Lượng
          </h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" style="font-size: 12.5px;">
              <thead class="table-light">
                <tr>
                  <th style="width: 45px; text-align: center;">Hạng</th>
                  <th>Size Ống (Col AO)</th>
                  <th style="text-align: right;">Số Lô/Dòng</th>
                  <th style="text-align: right;">Tổng Cuộn</th>
                  <th style="text-align: right;">Khối Lượng (kg)</th>
                  <th style="text-align: right;">Sản Lượng (m)</th>
                </tr>
              </thead>
              <tbody id="topSizesTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ext-table-box p-3">
          <h6 class="fw-bold text-success mb-3 d-flex align-items-center gap-2">
            <span class="material-icons fs-5">leaderboard</span>
            Bảng Top 10 Mã Sản Phẩm Đùn Ép Cao Nhất
          </h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" style="font-size: 12.5px;">
              <thead class="table-light">
                <tr>
                  <th style="width: 45px; text-align: center;">Hạng</th>
                  <th>Mã Sản Phẩm</th>
                  <th>Size AO</th>
                  <th style="text-align: right;">Tổng Cuộn</th>
                  <th style="text-align: right;">Khối Lượng (kg)</th>
                  <th style="text-align: right;">Sản Lượng (m)</th>
                </tr>
              </thead>
              <tbody id="topProductsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- TAB 2: BÁO CÁO TỔNG HỢP SẢN LƯỢNG                                      -->
  <!-- ===================================================================== -->
  <div id="tabContent-summary" class="tab-pane-content" style="display: none;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <!-- Bộ chọn chế độ xem tổng hợp -->
      <div class="btn-group btn-group-sm" role="group">
        <input type="radio" class="btn-check" name="aggMode" id="aggModeSize" value="size" checked onchange="changeAggMode('size')">
        <label class="btn btn-outline-primary" for="aggModeSize"><span class="material-icons fs-6 align-middle me-1">straighten</span>Theo Size Ống</label>

        <input type="radio" class="btn-check" name="aggMode" id="aggModeMonth" value="month" onchange="changeAggMode('month')">
        <label class="btn btn-outline-primary" for="aggModeMonth"><span class="material-icons fs-6 align-middle me-1">calendar_month</span>Theo Tháng</label>

        <input type="radio" class="btn-check" name="aggMode" id="aggModeProduct" value="range_product" onchange="changeAggMode('range_product')">
        <label class="btn btn-outline-primary" for="aggModeProduct"><span class="material-icons fs-6 align-middle me-1">category</span>Theo Mã Sản Phẩm</label>

        <input type="radio" class="btn-check" name="aggMode" id="aggModeMachine" value="range_machine" onchange="changeAggMode('range_machine')">
        <label class="btn btn-outline-primary" for="aggModeMachine"><span class="material-icons fs-6 align-middle me-1">precision_manufacturing</span>Theo Máy Sản Xuất</label>

        <input type="radio" class="btn-check" name="aggMode" id="aggModeWorkshop" value="range_workshop" onchange="changeAggMode('range_workshop')">
        <label class="btn btn-outline-primary" for="aggModeWorkshop"><span class="material-icons fs-6 align-middle me-1">domain</span>Theo Phân Xưởng</label>
      </div>

      <!-- Nút sắp xếp -->
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small">Sắp xếp sản lượng:</span>
        <button class="btn btn-sm btn-outline-secondary active" id="btnSortDesc" onclick="setAggSort('desc')">
          <span class="material-icons fs-6 align-middle">south</span> Giảm dần
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="btnSortAsc" onclick="setAggSort('asc')">
          <span class="material-icons fs-6 align-middle">north</span> Tăng dần
        </button>
      </div>
    </div>

    <!-- Bảng tổng hợp dữ liệu -->
    <div class="ext-table-box">
      <div class="ext-table-responsive">
        <table class="ext-table" id="aggTable">
          <thead id="aggTableHeader"></thead>
          <tbody id="aggTableBody">
            <tr><td colspan="8" class="text-center p-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải dữ liệu tổng hợp...</td></tr>
          </tbody>
          <tfoot id="aggTableFooter"></tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- TAB 3: DỮ LIỆU CHI TIẾT ĐÙN ÉP                                        -->
  <!-- ===================================================================== -->
  <div id="tabContent-details" class="tab-pane-content" style="display: none;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">Hiển thị:</span>
        <select id="detailsLimit" class="form-select form-select-sm" style="width: 80px;" onchange="loadDetailsData(1)">
          <option value="25" selected>25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="small text-muted" id="detailsSummaryBadge"></span>
      </div>
      <div id="detailsPaginationContainer" class="d-flex align-items-center gap-2"></div>
    </div>

    <div class="ext-table-box">
      <div class="ext-table-responsive">
        <table class="ext-table">
          <thead>
            <tr>
              <th style="width: 45px; text-align: center;">STT</th>
              <th>Mã SX (Col Z)</th>
              <th>Ngày SX</th>
              <th>Ca</th>
              <th>Mã CTSX</th>
              <th>Mã Sản Phẩm</th>
              <th style="text-align: center;">Size AO</th>
              <th>Máy Sản Xuất</th>
              <th style="text-align: right;">Sản Lượng (m)</th>
              <th style="text-align: right;">Khối Lượng (kg)</th>
              <th style="text-align: right;">Tổng Cuộn</th>
              <th>Dây Chuyền PL7</th>
              <th>Dây Chuyền PL4</th>
              <th>Người Thực Hiện</th>
              <th style="text-align: center; width: 60px;">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="detailsTableBody">
            <tr><td colspan="15" class="text-center p-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách chi tiết...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- TAB 4: IMPORT DỮ LIỆU EXCEL                                            -->
  <!-- ===================================================================== -->
  <div id="tabContent-import" class="tab-pane-content" style="display: none;">
    <div class="row g-4 mb-4">
      <!-- Cột 1: Form Tải File -->
      <div class="col-lg-6">
        <div class="ext-table-box p-4 h-100">
          <h5 class="fw-bold mb-2 text-primary d-flex align-items-center gap-2">
            <span class="material-icons">upload_file</span> Tải Lên Tệp Excel Mẫu
          </h5>
          <p class="text-muted small mb-3">
            Hệ thống hỗ trợ tệp định dạng chuẩn <code>Extrusion Report Sample.xlsx</code>. Cơ chế <strong>UPSERT</strong> tự động thêm mới hoặc cập nhật đè dữ liệu theo khóa kinh doanh <code>Mã SX (Col Z)</code>, đảm bảo 0 dòng trùng lặp.
          </p>

          <form id="extUploadForm" onsubmit="handleExtUpload(event)">
            <div class="ext-dropzone mb-3" id="extDropZone" onclick="document.getElementById('extFileInput').click()">
              <span class="material-icons text-primary fs-1 mb-2 d-block">cloud_upload</span>
              <h6 class="fw-bold m-0 text-primary">Kéo thả tệp Excel vào đây hoặc nhấp để chọn tệp</h6>
              <p class="text-muted small mt-1 mb-2">Hỗ trợ tệp .xlsx, .xlsm, .xls (Dung lượng lên tới 100MB / >100.000 dòng)</p>
              <span id="selectedFileName" class="badge bg-light text-dark border px-3 py-1 font-monospace">Chưa chọn tệp</span>
              <input type="file" id="extFileInput" style="display: none;" accept=".xlsx,.xlsm,.xls" onchange="handleFileSelected(this)">
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2">
              <button type="button" class="app-btn app-btn-success btn-sm d-inline-flex align-items-center gap-1" onclick="handleImportSampleData()">
                <span class="material-icons fs-6">folder_open</span> Nạp File Mẫu Từ Thư Mục Data
              </button>
              <button type="submit" class="app-btn app-btn-primary btn-sm px-4" id="btnSubmitUpload">
                <span class="material-icons fs-6">upload</span> Tiến Hành Import
              </button>
            </div>
          </form>

          <div id="importProgressBox" class="mt-3" style="display: none;">
            <div class="d-flex justify-content-between small mb-1">
              <span id="importProgressText" class="fw-bold text-primary">Đang xử lý dữ liệu...</span>
              <span class="spinner-border spinner-border-sm text-primary"></span>
            </div>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cột 2: Hướng Dẫn & Quy Chuẩn Cột AO -->
      <div class="col-lg-6">
        <div class="ext-table-box p-4 h-100 bg-light">
          <h5 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
            <span class="material-icons text-info">rule</span> Quy Chuẩn Trích Xuất Size Ống (Cột AO)
          </h5>
          <p class="text-muted small mb-2">
            Hệ thống tự động thực thi thuật toán mô phỏng 100% logic công thức <code>_xlfn.IFS</code> tại Cột AO:
          </p>
          <div class="list-group list-group-flush rounded border bg-white mb-3" style="font-size: 12.5px;">
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong>1. Tiền tố HF2B:</strong> Cắt từ vị trí 6 lấy 6 ký tự</div>
              <span class="badge-size-ao">HF2B-TU1208... &rarr; TU1208</span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong>2. Tiền tố TIUB:</strong> Lấy 6 ký tự đầu</div>
              <span class="badge-size-ao">TIUB07C... &rarr; TIUB07</span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong>3. Tiền tố TIA:</strong> Lấy 5 ký tự đầu</div>
              <span class="badge-size-ao">TIA07B... &rarr; TIA07</span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong>4. Tiền tố TU:</strong> Lấy 6 ký tự đầu</div>
              <span class="badge-size-ao">TU0604C... &rarr; TU0604</span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div><strong>5. Tiền tố T:</strong> Lấy 5 ký tự đầu</div>
              <span class="badge-size-ao">T0604B... &rarr; T0604</span>
            </div>
          </div>
          <div class="alert alert-info py-2 px-3 small m-0 d-flex align-items-center gap-2">
            <span class="material-icons fs-5 text-info">info</span>
            <span>Khóa UPSERT duy nhất: <strong>Mã SX (Col Z)</strong>. Bản ghi đã tồn tại sẽ được cập nhật phiên bản mới nhất, không tạo dữ liệu trùng lặp.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bảng Lịch Sử Các Đợt Import (Audit Trail) -->
    <div class="ext-table-box p-3">
      <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
        <span class="material-icons fs-5 text-secondary">history</span>
        Lịch Sử Các Đợt Import Dữ Liệu
      </h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size: 12.5px;">
          <thead class="table-light">
            <tr>
              <th style="width: 50px;">STT</th>
              <th>Mã Lô (Batch ID)</th>
              <th>Tên Tệp Gốc</th>
              <th>Dung Lượng</th>
              <th style="text-align: right;">Tổng Dòng Đọc</th>
              <th style="text-align: right;">Thêm Mới</th>
              <th style="text-align: right;">Cập Nhật (UPSERT)</th>
              <th style="text-align: right;">Lỗi</th>
              <th style="text-align: center;">Trạng Thái</th>
              <th>Người Thực Hiện</th>
              <th>Thời Gian</th>
            </tr>
          </thead>
          <tbody id="importHistoryTableBody">
            <tr><td colspan="11" class="text-center p-3 text-muted">Đang tải lịch sử import...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- TAB 5: TRUNG TÂM XUẤT BÁO CÁO                                         -->
  <!-- ===================================================================== -->
  <div id="tabContent-export" class="tab-pane-content" style="display: none;">
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 p-3 border shadow-sm">
          <div class="text-center mb-3">
            <span class="material-icons text-primary fs-1">straighten</span>
            <h6 class="fw-bold mt-2">Báo Cáo Theo Size Ống</h6>
            <p class="text-muted small">Xuất tổng hợp sản lượng, số cuộn, khối lượng phân loại theo Size ống chuẩn hóa.</p>
          </div>
          <button class="app-btn app-btn-primary btn-sm mt-auto w-100" onclick="triggerExtExport('summary_size')">
            <span class="material-icons fs-6">download</span> Tải Báo Cáo Size
          </button>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 p-3 border shadow-sm">
          <div class="text-center mb-3">
            <span class="material-icons text-success fs-1">calendar_month</span>
            <h6 class="fw-bold mt-2">Báo Cáo Theo Tháng</h6>
            <p class="text-muted small">Xuất tổng hợp tiến độ sản lượng 12 tháng, tổng số mét và tổng trọng lượng.</p>
          </div>
          <button class="app-btn app-btn-success btn-sm mt-auto w-100" onclick="triggerExtExport('summary_month')">
            <span class="material-icons fs-6">download</span> Tải Báo Cáo Tháng
          </button>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 p-3 border shadow-sm">
          <div class="text-center mb-3">
            <span class="material-icons text-warning fs-1">precision_manufacturing</span>
            <h6 class="fw-bold mt-2">Báo Cáo Theo Máy & Xưởng</h6>
            <p class="text-muted small">Xuất tổng hợp sản lượng theo từng máy đùn V61-PL01 đến PL22 và phân xưởng.</p>
          </div>
          <button class="app-btn app-btn-warning btn-sm mt-auto w-100 text-dark" onclick="triggerExtExport('summary_range')">
            <span class="material-icons fs-6">download</span> Tải Báo Cáo Thiết Bị
          </button>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 p-3 border shadow-sm">
          <div class="text-center mb-3">
            <span class="material-icons text-danger fs-1">description</span>
            <h6 class="fw-bold mt-2">Dữ Liệu Chi Tiết Đầy Đủ</h6>
            <p class="text-muted small">Xuất toàn bộ 41 cột thông số kỹ thuật đùn ép của tất cả các mẻ theo bộ lọc.</p>
          </div>
          <button class="app-btn app-btn-danger btn-sm mt-auto w-100" onclick="triggerExtExport('details')">
            <span class="material-icons fs-6">download</span> Tải Dữ Liệu Chi Tiết
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL XEM CHI TIẾT 41 THUỘC TÍNH (DETAIL MODAL)                       -->
<!-- ===================================================================== -->
<div class="modal fade" id="extrusionDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">visibility</span>
          Chi Tiết Thực Tích Đùn Ép: <span id="modalProdCodeTitle" class="font-monospace text-warning">...</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="modalDetailContent" style="max-height: 80vh; overflow-y: auto;">
        <!-- Render nội dung chi tiết -->
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
// BIẾN TOÀN CỤC CỦA MODULE ĐÙN ÉP
let currentTab = 'dashboard';
let currentAggMode = 'size';
let currentAggSortCol = 'output';
let currentAggSortOrder = 'desc';
let currentDetailsPage = 1;

// Khởi tạo các biểu đồ ApexCharts
let chartMonthly = null;
let chartSizeDist = null;
let chartTopSize = null;
let chartMachine = null;

document.addEventListener('DOMContentLoaded', async () => {
  await loadFilterOptions();
  switchExtTab('dashboard');
});

// 1. Tải danh sách Options cho các dropdown bộ lọc
async function loadFilterOptions() {
  try {
    const res = await fetch('api/extrusion_production.php?action=get_filter_options');
    const data = await res.json();
    if (!data.success) return;

    // Sizes
    const sizeSel = document.getElementById('filterPipeSize');
    data.sizes.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s;
      opt.textContent = s;
      sizeSel.appendChild(opt);
    });

    // Machines
    const mcSel = document.getElementById('filterMachine');
    data.machines.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m;
      opt.textContent = m;
      mcSel.appendChild(opt);
    });

    // Years
    const yrSel = document.getElementById('filterYear');
    data.years.forEach(y => {
      const opt = document.createElement('option');
      opt.value = y;
      opt.textContent = 'Năm ' + y;
      yrSel.appendChild(opt);
    });

    // Workshops
    const wsSel = document.getElementById('filterWorkshop');
    data.workshops.forEach(w => {
      const opt = document.createElement('option');
      opt.value = w;
      opt.textContent = w;
      wsSel.appendChild(opt);
    });

  } catch (err) {
    console.error('Lỗi loadFilterOptions:', err);
  }
}

// 2. Chuyển đổi Tab Navigation
function switchExtTab(tabName) {
  currentTab = tabName;
  document.querySelectorAll('.ext-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-pane-content').forEach(p => p.style.display = 'none');

  const btn = document.getElementById(`tabBtn-${tabName}`);
  const pane = document.getElementById(`tabContent-${tabName}`);
  if (btn) btn.classList.add('active');
  if (pane) pane.style.display = 'block';

  reloadCurrentTab();
}

function reloadCurrentTab() {
  if (currentTab === 'dashboard') {
    loadDashboardData();
  } else if (currentTab === 'summary') {
    loadAggregationData();
  } else if (currentTab === 'details') {
    loadDetailsData(1);
  } else if (currentTab === 'import') {
    loadImportHistory();
  }
}

async function triggerSyncData() {
  const btn = document.getElementById('btnSyncExtrusion');
  const originalHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang đồng bộ...';
  }

  try {
    const res = await fetch('api/extrusion_production.php?action=sync_now');
    const data = await res.json();
    if (data.success) {
      const msg = `ĐỒNG BỘ DỮ LIỆU THÀNH CÔNG!\n- Bảng chuẩn (SSOT Actual Logs): ${(data.count_actual_logs || 0).toLocaleString()} bản ghi\n- Bảng đích (Productions): ${(data.count_productions || 0).toLocaleString()} bản ghi`;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Đồng bộ thành công',
          html: `<p>Đã đồng bộ dữ liệu từ bảng chuẩn <code>extrusion_actual_logs</code> sang <code>extrusion_productions</code>.</p>
                 <ul class="text-start mb-0">
                   <li>Số dòng bảng chuẩn: <b>${(data.count_actual_logs || 0).toLocaleString()}</b></li>
                   <li>Số dòng bảng đích: <b>${(data.count_productions || 0).toLocaleString()}</b></li>
                 </ul>`,
          timer: 3500
        });
      } else {
        alert(msg);
      }
      reloadCurrentTab();
    } else {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi đồng bộ',
          text: data.message || 'Không thể đồng bộ dữ liệu!'
        });
      } else {
        alert('Lỗi đồng bộ: ' + (data.message || 'Không thể đồng bộ dữ liệu!'));
      }
    }
  } catch (err) {
    console.error('Lỗi triggerSyncData:', err);
    alert('Lỗi kết nối máy chủ khi thực hiện đồng bộ dữ liệu!');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  }
}

// 3. Lấy tham số bộ lọc từ UI
function getFilterParams() {
  const p = new URLSearchParams();
  const dateFrom = document.getElementById('filterDateFrom').value;
  const dateTo = document.getElementById('filterDateTo').value;
  const year = document.getElementById('filterYear').value;
  const month = document.getElementById('filterMonth').value;
  const size = document.getElementById('filterPipeSize').value;
  const machine = document.getElementById('filterMachine').value;
  const search = document.getElementById('filterSearch').value.trim();
  const workshop = document.getElementById('filterWorkshop').value;

  if (dateFrom) p.append('date_from', dateFrom);
  if (dateTo) p.append('date_to', dateTo);
  if (year) p.append('year', year);
  if (month) p.append('month', month);
  if (size && size !== 'all') p.append('pipe_size', size);
  if (machine && machine !== 'all') p.append('machine_code', machine);
  if (search) p.append('search', search);
  if (workshop && workshop !== 'all') p.append('workshop', workshop);

  return p.toString();
}

function applyExtFilters() {
  reloadCurrentTab();
}

function resetExtFilters() {
  document.getElementById('filterDateFrom').value = '';
  document.getElementById('filterDateTo').value = '';
  document.getElementById('filterYear').value = '';
  document.getElementById('filterMonth').value = '';
  document.getElementById('filterPipeSize').value = 'all';
  document.getElementById('filterMachine').value = 'all';
  document.getElementById('filterSearch').value = '';
  document.getElementById('filterWorkshop').value = 'all';
  reloadCurrentTab();
}

function handleYearFilterChange() {
  const y = document.getElementById('filterYear').value;
  const mInput = document.getElementById('filterMonth');
  if (y) {
    mInput.value = `${y}-01`;
  } else {
    mInput.value = '';
  }
}

// -------------------------------------------------------------
// TAB 1: DASHBOARD
// -------------------------------------------------------------
async function loadDashboardData() {
  try {
    const q = getFilterParams();
    const res = await fetch(`api/extrusion_production.php?action=get_dashboard&${q}`);
    const data = await res.json();
    if (!data.success) return;

    // 1. Thẻ KPI
    const kpi = data.kpis || {};
    document.getElementById('kpiTotalLength').innerHTML = `${formatNum(kpi.total_length_m)} <span class="fs-6 fw-normal">m</span>`;
    document.getElementById('kpiTotalWeight').innerHTML = `${formatNum(kpi.total_weight_kg)} <span class="fs-6 fw-normal">kg</span>`;
    document.getElementById('kpiTotalCoils').innerHTML = `${formatNum(kpi.total_coils)} <span class="fs-6 fw-normal">cuộn</span>`;
    document.getElementById('kpiCountSizes').innerHTML = `${kpi.count_sizes || 0} <span class="fs-6 fw-normal">Sizes</span>`;

    // 2. Render Biểu đồ Tháng
    renderMonthlyChart(data.charts.monthly);

    // 3. Render Biểu đồ Tỷ trọng Size
    renderSizeShareChart(data.charts.size_share);

    // 4. Render Biểu đồ Top Sizes
    renderTopSizesChart(data.top_sizes);

    // 5. Render Biểu đồ Máy sản xuất
    renderMachineChart(data.charts.machines);

    // 6. Render Bảng Top 10 Sizes
    renderTopSizesTable(data.top_sizes);

    // 7. Render Bảng Top 10 Sản Phẩm
    renderTopProductsTable(data.top_products);

  } catch (err) {
    console.error('Lỗi loadDashboardData:', err);
  }
}

function renderMonthlyChart(cData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: [
      { name: 'Sản Lượng (Mét)', type: 'column', data: cData.length },
      { name: 'Khối Lượng (Kg)', type: 'line', data: cData.weight }
    ],
    chart: { height: 310, type: 'line', toolbar: { show: false } },
    stroke: { width: [0, 3], curve: 'smooth' },
    colors: ['#0d6efd', '#198754'],
    labels: cData.labels,
    yaxis: [
      { title: { text: 'Mét' }, labels: { formatter: v => formatShortNum(v) } },
      { opposite: true, title: { text: 'Kg' }, labels: { formatter: v => formatShortNum(v) } }
    ],
    theme: { mode: isDark ? 'dark' : 'light' }
  };

  if (chartMonthly) chartMonthly.destroy();
  chartMonthly = new ApexCharts(document.querySelector("#chartMonthlyTrend"), options);
  chartMonthly.render();
}

function renderSizeShareChart(sData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: sData.data,
    labels: sData.labels,
    chart: { type: 'donut', height: 310 },
    legend: { position: 'bottom', fontSize: '11px' },
    colors: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#0dcaf0', '#6c757d'],
    theme: { mode: isDark ? 'dark' : 'light' }
  };

  if (chartSizeDist) chartSizeDist.destroy();
  chartSizeDist = new ApexCharts(document.querySelector("#chartSizeDistribution"), options);
  chartSizeDist.render();
}

function renderTopSizesChart(topSizes) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const labels = topSizes.map(s => s.size_calculated);
  const data = topSizes.map(s => parseFloat(s.total_length_m));

  const options = {
    series: [{ name: 'Sản lượng (m)', data: data }],
    chart: { type: 'bar', height: 310, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
    colors: ['#0d6efd'],
    xaxis: { categories: labels, labels: { formatter: v => formatShortNum(v) } },
    theme: { mode: isDark ? 'dark' : 'light' }
  };

  if (chartTopSize) chartTopSize.destroy();
  chartTopSize = new ApexCharts(document.querySelector("#chartTopSizes"), options);
  chartTopSize.render();
}

function renderMachineChart(mData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: [{ name: 'Sản lượng (m)', data: mData.length }],
    chart: { type: 'bar', height: 310, toolbar: { show: false } },
    plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
    colors: ['#198754'],
    xaxis: { categories: mData.labels },
    yaxis: { labels: { formatter: v => formatShortNum(v) } },
    theme: { mode: isDark ? 'dark' : 'light' }
  };

  if (chartMachine) chartMachine.destroy();
  chartMachine = new ApexCharts(document.querySelector("#chartMachineOutput"), options);
  chartMachine.render();
}

function renderTopSizesTable(topSizes) {
  const tbody = document.getElementById('topSizesTableBody');
  if (!topSizes || topSizes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-3">Không có dữ liệu</td></tr>';
    return;
  }
  let html = '';
  topSizes.forEach((s, idx) => {
    html += `
      <tr>
        <td style="text-align: center;"><span class="badge ${idx < 3 ? 'bg-primary' : 'bg-light text-dark'} rounded-pill">${idx + 1}</span></td>
        <td><strong class="font-monospace text-primary">${s.size_calculated}</strong></td>
        <td style="text-align: right;">${formatNum(s.record_count)}</td>
        <td style="text-align: right;">${formatNum(s.total_coils)}</td>
        <td style="text-align: right;">${formatNum(s.total_weight_kg)}</td>
        <td style="text-align: right;" class="fw-bold text-success">${formatNum(s.total_length_m)} m</td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function renderTopProductsTable(topProducts) {
  const tbody = document.getElementById('topProductsTableBody');
  if (!topProducts || topProducts.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-3">Không có dữ liệu</td></tr>';
    return;
  }
  let html = '';
  topProducts.forEach((p, idx) => {
    html += `
      <tr>
        <td style="text-align: center;"><span class="badge ${idx < 3 ? 'bg-success' : 'bg-light text-dark'} rounded-pill">${idx + 1}</span></td>
        <td><strong class="font-monospace">${p.product_code}</strong></td>
        <td><span class="badge-size-ao">${p.size_calculated}</span></td>
        <td style="text-align: right;">${formatNum(p.total_coils)}</td>
        <td style="text-align: right;">${formatNum(p.total_weight_kg)}</td>
        <td style="text-align: right;" class="fw-bold text-primary">${formatNum(p.total_length_m)} m</td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

// -------------------------------------------------------------
// TAB 2: BÁO CÁO TỔNG HỢP (AGGREGATIONS)
// -------------------------------------------------------------
function changeAggMode(mode) {
  currentAggMode = mode;
  loadAggregationData();
}

function setAggSort(order) {
  currentAggSortOrder = order;
  document.getElementById('btnSortDesc').classList.toggle('active', order === 'desc');
  document.getElementById('btnSortAsc').classList.toggle('active', order === 'asc');
  loadAggregationData();
}

async function loadAggregationData() {
  const thead = document.getElementById('aggTableHeader');
  const tbody = document.getElementById('aggTableBody');
  const tfoot = document.getElementById('aggTableFooter');

  tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tổng hợp dữ liệu...</td></tr>';

  try {
    const q = getFilterParams();
    const res = await fetch(`api/extrusion_production.php?action=get_aggregation&type=${currentAggMode}&sort_col=${currentAggSortCol}&sort_order=${currentAggSortOrder}&${q}`);
    const data = await res.json();
    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    // Render Headers tùy theo chế độ
    let headHtml = '<tr><th style="width: 45px; text-align: center;">STT</th>';
    if (currentAggMode === 'size') {
      headHtml += '<th>Size Ống (Col AO)</th><th style="text-align: right;">Số Lô/Mẫu</th><th style="text-align: right;">Tổng Số Cuộn (Bobin)</th><th style="text-align: right;">Tổng Trọng Lượng (kg)</th><th style="text-align: right;">Tổng Chiều Dài (m)</th><th style="text-align: right;">Tỷ Trọng (%)</th>';
    } else if (currentAggMode === 'month') {
      headHtml += '<th>Tháng</th><th style="text-align: right;">Số Mẫu/Lô</th><th style="text-align: right;">Tổng Số Cuộn</th><th style="text-align: right;">Tổng Trọng Lượng (kg)</th><th style="text-align: right;">Tổng Chiều Dài (m)</th><th style="text-align: center;">Số Chủng Size</th>';
    } else if (currentAggMode === 'range_product') {
      headHtml += '<th>Mã Sản Phẩm</th><th>Size AO</th><th style="text-align: right;">Số Lô</th><th style="text-align: right;">Tổng Cuộn</th><th style="text-align: right;">Khối Lượng (kg)</th><th style="text-align: right;">Chiều Dài (m)</th><th style="text-align: right;">Tỷ Trọng (%)</th>';
    } else if (currentAggMode === 'range_machine') {
      headHtml += '<th>Mã Thiết Bị (Máy Đùn)</th><th style="text-align: right;">Số Lô Chạy</th><th style="text-align: right;">Tổng Cuộn</th><th style="text-align: right;">Khối Lượng (kg)</th><th style="text-align: right;">Chiều Dài (m)</th><th style="text-align: right;">TG Chạy Máy (h)</th>';
    } else if (currentAggMode === 'range_workshop') {
      headHtml += '<th>Phân Xưởng</th><th style="text-align: right;">Số Lô Chạy</th><th style="text-align: right;">Tổng Cuộn</th><th style="text-align: right;">Khối Lượng (kg)</th><th style="text-align: right;">Chiều Dài (m)</th><th style="text-align: center;">Số Máy</th>';
    }
    headHtml += '</tr>';
    thead.innerHTML = headHtml;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-muted">Không tìm thấy dữ liệu phù hợp với bộ lọc.</td></tr>';
      tfoot.innerHTML = '';
      return;
    }

    let rowsHtml = '';
    data.data.forEach((r, idx) => {
      rowsHtml += `<tr><td style="text-align: center;">${idx + 1}</td>`;
      if (currentAggMode === 'size') {
        rowsHtml += `
          <td><strong class="font-monospace text-primary">${r.pipe_size}</strong></td>
          <td style="text-align: right;">${formatNum(r.total_records)}</td>
          <td style="text-align: right;">${formatNum(r.total_coils)}</td>
          <td style="text-align: right;">${formatNum(r.total_weight_kg)}</td>
          <td style="text-align: right;" class="fw-bold text-success">${formatNum(r.total_length_m)} m</td>
          <td style="text-align: right;"><span class="badge bg-light text-dark border font-monospace">${r.percentage}%</span></td>
        `;
      } else if (currentAggMode === 'month') {
        rowsHtml += `
          <td><strong>${r.production_month}</strong></td>
          <td style="text-align: right;">${formatNum(r.total_records)}</td>
          <td style="text-align: right;">${formatNum(r.total_coils)}</td>
          <td style="text-align: right;">${formatNum(r.total_weight_kg)}</td>
          <td style="text-align: right;" class="fw-bold text-primary">${formatNum(r.total_length_m)} m</td>
          <td style="text-align: center;"><span class="badge bg-info-subtle text-info">${r.count_sizes} sizes</span></td>
        `;
      } else if (currentAggMode === 'range_product') {
        rowsHtml += `
          <td><strong class="font-monospace">${r.product_code}</strong></td>
          <td><span class="badge-size-ao">${r.size_calculated}</span></td>
          <td style="text-align: right;">${formatNum(r.total_records)}</td>
          <td style="text-align: right;">${formatNum(r.total_coils)}</td>
          <td style="text-align: right;">${formatNum(r.total_weight_kg)}</td>
          <td style="text-align: right;" class="fw-bold text-success">${formatNum(r.total_length_m)} m</td>
          <td style="text-align: right;"><span class="badge bg-light text-dark border font-monospace">${r.percentage}%</span></td>
        `;
      } else if (currentAggMode === 'range_machine') {
        rowsHtml += `
          <td><strong class="text-primary font-monospace">${r.machine_code}</strong></td>
          <td style="text-align: right;">${formatNum(r.total_records)}</td>
          <td style="text-align: right;">${formatNum(r.total_coils)}</td>
          <td style="text-align: right;">${formatNum(r.total_weight_kg)}</td>
          <td style="text-align: right;" class="fw-bold text-success">${formatNum(r.total_length_m)} m</td>
          <td style="text-align: right;">${formatNum(r.total_run_time)} h</td>
        `;
      } else if (currentAggMode === 'range_workshop') {
        rowsHtml += `
          <td><strong>${r.workshop}</strong></td>
          <td style="text-align: right;">${formatNum(r.total_records)}</td>
          <td style="text-align: right;">${formatNum(r.total_coils)}</td>
          <td style="text-align: right;">${formatNum(r.total_weight_kg)}</td>
          <td style="text-align: right;" class="fw-bold text-success">${formatNum(r.total_length_m)} m</td>
          <td style="text-align: center;"><span class="badge bg-secondary">${r.count_machines} máy</span></td>
        `;
      }
      rowsHtml += '</tr>';
    });
    tbody.innerHTML = rowsHtml;

    // Dòng tổng cộng Footer
    const s = data.summary || {};
    let footHtml = '<tr><td colspan="2" class="text-uppercase">TỔNG CỘNG</td>';
    footHtml += `
      <td style="text-align: right;">${formatNum(s.total_records)}</td>
      <td style="text-align: right;">${formatNum(s.total_coils)}</td>
      <td style="text-align: right;">${formatNum(s.total_weight_kg)} kg</td>
      <td style="text-align: right;" class="text-success">${formatNum(s.total_length_m)} m</td>
      <td style="text-align: right;">100%</td>
    </tr>`;
    tfoot.innerHTML = footHtml;

  } catch (err) {
    console.error('Lỗi loadAggregationData:', err);
  }
}

// -------------------------------------------------------------
// TAB 3: DỮ LIỆU CHI TIẾT
// -------------------------------------------------------------
async function loadDetailsData(page = 1) {
  currentDetailsPage = page;
  const limit = document.getElementById('detailsLimit').value;
  const tbody = document.getElementById('detailsTableBody');

  tbody.innerHTML = '<tr><td colspan="15" class="text-center p-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải dữ liệu...</td></tr>';

  try {
    const q = getFilterParams();
    const res = await fetch(`api/extrusion_production.php?action=get_data_list&page=${page}&limit=${limit}&${q}`);
    const data = await res.json();
    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="15" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    const s = data.summary || {};
    document.getElementById('detailsSummaryBadge').textContent = `Tổng: ${formatNum(data.total)} bản ghi | ${formatNum(s.sum_length)} m | ${formatNum(s.sum_weight)} kg | ${formatNum(s.sum_coils)} cuộn`;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="15" class="text-center p-4 text-muted">Không có bản ghi nào.</td></tr>';
      document.getElementById('detailsPaginationContainer').innerHTML = '';
      return;
    }

    let rowsHtml = '';
    data.data.forEach((r, idx) => {
      const stt = (page - 1) * limit + idx + 1;
      rowsHtml += `
        <tr>
          <td style="text-align: center;">${stt}</td>
          <td><strong class="font-monospace text-primary">${r.production_code}</strong></td>
          <td>${r.production_date}</td>
          <td>${r.shift || '-'}</td>
          <td><span class="font-monospace">${r.directive_code}</span></td>
          <td><strong>${r.product_code}</strong></td>
          <td style="text-align: center;"><span class="badge-size-ao">${r.size_calculated}</span></td>
          <td><span class="font-monospace small">${r.machine_code}</span></td>
          <td style="text-align: right;" class="fw-bold text-success">${formatNum(r.finished_length)}</td>
          <td style="text-align: right;">${formatNum(r.finished_weight)}</td>
          <td style="text-align: right;"><strong>${formatNum(r.total_coils)}</strong></td>
          <td><small class="text-muted">${r.coils_pl7} c / ${formatNum(r.length_pl7)}m</small></td>
          <td><small class="text-muted">${r.coils_pl4} c / ${formatNum(r.length_pl4)}m</small></td>
          <td><small>${r.employee_name || '-'}</small></td>
          <td style="text-align: center;">
            <button class="btn btn-sm btn-outline-primary p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
              onclick="openExtDetailModal(${r.id})" title="Xem chi tiết 41 trường" style="width: 28px; height: 28px;">
              <span class="material-icons" style="font-size: 15px;">visibility</span>
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

    renderPaginationControls(data.total, page, limit);

  } catch (err) {
    console.error('Lỗi loadDetailsData:', err);
  }
}

function renderPaginationControls(total, curPage, limit) {
  const container = document.getElementById('detailsPaginationContainer');
  const totalPages = Math.ceil(total / limit);
  if (totalPages <= 1) {
    container.innerHTML = `<span class="small text-muted">Trang 1 / 1</span>`;
    return;
  }

  let html = `<span class="small text-muted me-2">Trang <strong>${curPage}</strong> / ${totalPages}</span><div class="btn-group btn-group-sm">`;
  if (curPage > 1) {
    html += `<button class="btn btn-outline-secondary" onclick="loadDetailsData(${curPage - 1})">Trước</button>`;
  }
  for (let p = Math.max(1, curPage - 2); p <= Math.min(totalPages, curPage + 2); p++) {
    html += `<button class="btn ${p === curPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadDetailsData(${p})">${p}</button>`;
  }
  if (curPage < totalPages) {
    html += `<button class="btn btn-outline-secondary" onclick="loadDetailsData(${curPage + 1})">Sau</button>`;
  }
  html += '</div>';
  container.innerHTML = html;
}

// -------------------------------------------------------------
// TAB 4: IMPORT EXCEL
// -------------------------------------------------------------
function handleFileSelected(input) {
  if (input.files && input.files[0]) {
    document.getElementById('selectedFileName').textContent = input.files[0].name + ' (' + (input.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
  }
}

async function handleExtUpload(e) {
  e.preventDefault();
  const fileInput = document.getElementById('extFileInput');
  if (!fileInput.files || !fileInput.files[0]) {
    alert('Vui lòng chọn tệp Excel để tải lên!');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'import_excel');
  formData.append('file_source', 'upload');
  formData.append('file', fileInput.files[0]);

  await doImportProcess(formData);
}

async function handleImportSampleData() {
  if (!confirm('Bạn có muốn thực hiện nạp tệp mẫu Extrusion Report Sample.xlsx (16.592 dòng) từ thư mục data/ không?')) {
    return;
  }

  const formData = new FormData();
  formData.append('action', 'import_excel');
  formData.append('file_source', 'sample');

  await doImportProcess(formData);
}

async function doImportProcess(formData) {
  const pBox = document.getElementById('importProgressBox');
  const pText = document.getElementById('importProgressText');
  const btn = document.getElementById('btnSubmitUpload');

  btn.disabled = true;
  pBox.style.display = 'block';
  pText.textContent = 'Đang phân tích cú pháp và thực thi Bulk UPSERT...';

  try {
    const res = await fetch('api/extrusion_production.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      alert(`IMPORT THÀNH CÔNG!\n- Tổng dòng đọc: ${formatNum(data.total_read)}\n- Thêm mới: ${formatNum(data.inserted)}\n- Cập nhật (UPSERT): ${formatNum(data.updated)}\n- Lỗi: ${data.errors}`);
      loadImportHistory();
      loadFilterOptions();
    } else {
      alert('Lỗi Import: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi doImportProcess:', err);
    alert('Lỗi kết nối máy chủ khi import tệp!');
  } finally {
    btn.disabled = false;
    pBox.style.display = 'none';
  }
}

async function loadImportHistory() {
  const tbody = document.getElementById('importHistoryTableBody');
  try {
    const res = await fetch('api/extrusion_production.php?action=get_import_history');
    const data = await res.json();
    if (!data.success || !data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="11" class="text-center p-3 text-muted">Chưa có đợt import nào.</td></tr>';
      return;
    }

    let html = '';
    data.data.forEach((b, idx) => {
      const stBadge = b.status === 'success' ? '<span class="badge bg-success">Thành công</span>' : (b.status === 'processing' ? '<span class="badge bg-warning text-dark">Đang chạy</span>' : '<span class="badge bg-danger">Thất bại</span>');
      html += `
        <tr>
          <td style="text-align: center;">${idx + 1}</td>
          <td><strong class="font-monospace text-primary">${b.batch_code}</strong></td>
          <td>${b.file_name}</td>
          <td>${(b.file_size / 1024 / 1024).toFixed(2)} MB</td>
          <td style="text-align: right;"><strong>${formatNum(b.total_rows)}</strong></td>
          <td style="text-align: right;" class="text-success">${formatNum(b.inserted_rows)}</td>
          <td style="text-align: right;" class="text-info">${formatNum(b.updated_rows)}</td>
          <td style="text-align: right;" class="text-danger">${formatNum(b.error_rows)}</td>
          <td style="text-align: center;">${stBadge}</td>
          <td><small>${b.imported_by}</small></td>
          <td><small class="text-muted">${b.created_at}</small></td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  } catch (err) {
    console.error('Lỗi loadImportHistory:', err);
  }
}

// -------------------------------------------------------------
// TAB 5: TRUNG TÂM XUẤT BÁO CÁO (EXPORT)
// -------------------------------------------------------------
function triggerExtExport(type) {
  const q = getFilterParams();
  window.location.href = `api/extrusion_production.php?action=export_excel&export_type=${type}&${q}`;
}

// -------------------------------------------------------------
// MODAL CHI TIẾT 41 THUỘC TÍNH
// -------------------------------------------------------------
async function openExtDetailModal(id) {
  const body = document.getElementById('modalDetailContent');
  body.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary"></div> Đang nạp chi tiết...</div>';
  new bootstrap.Modal(document.getElementById('extrusionDetailModal')).show();

  try {
    const res = await fetch(`api/extrusion_production.php?action=get_detail&id=${id}`);
    const data = await res.json();
    if (!data.success) {
      body.innerHTML = `<div class="text-danger p-3">${data.message}</div>`;
      return;
    }

    const d = data.data;
    document.getElementById('modalProdCodeTitle').textContent = d.production_code;

    body.innerHTML = `
      <div class="row g-3">
        <!-- Nhóm 1: Thông tin chung -->
        <div class="col-md-6">
          <div class="card p-3 border h-100">
            <h6 class="fw-bold text-primary border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">info</span> 1. Thông Tin Nhập Liệu & Ca Kíp
            </h6>
            <div class="small" style="line-height: 2;">
              Mã SX (Col Z): <strong class="font-monospace text-primary">${d.production_code}</strong><br/>
              Ngày SX (Col B): <strong>${d.production_date}</strong> (Nhập: ${d.input_date || '-' })<br/>
              Ca làm việc (Col E): <strong>${d.shift || '-'}</strong><br/>
              Mã CTSX (Col F): <strong>${d.directive_code}</strong><br/>
              Nhân viên (Col C/D): <strong>${d.employee_name || '-'}</strong> (${d.employee_code || '-'})
            </div>
          </div>
        </div>

        <!-- Nhóm 2: Sản phẩm & Size AO -->
        <div class="col-md-6">
          <div class="card p-3 border h-100 border-primary-subtle">
            <h6 class="fw-bold text-primary border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">straighten</span> 2. Sản Phẩm & Size Tính Toán (Col AO)
            </h6>
            <div class="small" style="line-height: 2;">
              Mã sản phẩm (Col G): <strong class="fs-6">${d.product_code}</strong><br/>
              Size Tính Toán (Col AO): <span class="badge-size-ao fs-6">${d.size_calculated}</span><br/>
              Size gốc file (Col AO): <em>${d.size_original || '-'}</em><br/>
              Cost Center (Col H): <strong>${d.cost_center || '-'}</strong><br/>
              Công đoạn (Col I): <strong>${d.stage}</strong> | Xưởng: <strong>${d.workshop}</strong>
            </div>
          </div>
        </div>

        <!-- Nhóm 3: Sản lượng & Khối lượng -->
        <div class="col-md-6">
          <div class="card p-3 border h-100">
            <h6 class="fw-bold text-success border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">scale</span> 3. Sản Lượng & Khối Lượng
            </h6>
            <div class="small" style="line-height: 2;">
              Thành phẩm (M) (Col K): <strong class="text-success fs-6">${formatNum(d.finished_length)} m</strong><br/>
              KL Thành Phẩm (KG) (Col L): <strong>${formatNum(d.finished_weight)} kg</strong><br/>
              KL Phế Phẩm NG (KG) (Col M): <strong class="text-danger">${formatNum(d.ng_weight)} kg</strong><br/>
              KL Cứng (KG) (Col N): <strong>${formatNum(d.hard_weight)} kg</strong><br/>
              Tổng KL (KG) (Col O): <strong>${formatNum(d.total_weight)} kg</strong>
            </div>
          </div>
        </div>

        <!-- Nhóm 4: Đóng cuộn & Dây chuyền PL7 / PL4 -->
        <div class="col-md-6">
          <div class="card p-3 border h-100">
            <h6 class="fw-bold text-warning-emphasis border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">inventory_2</span> 4. Đóng Gói Cuộn (Bobin)
            </h6>
            <div class="small" style="line-height: 2;">
              Tổng số cuộn: <strong class="fs-6 text-warning-emphasis">${formatNum(d.total_coils)} cuộn</strong><br/>
              Line PL7-3 (Col AH/AI): <strong>${d.coils_pl7} cuộn</strong> / <strong>${formatNum(d.length_pl7)} m</strong><br/>
              Line PL4-7 (Col AJ/AK): <strong>${d.coils_pl4} cuộn</strong> / <strong>${formatNum(d.length_pl4)} m</strong>
            </div>
          </div>
        </div>

        <!-- Nhóm 5: Thiết bị & Khuôn -->
        <div class="col-md-6">
          <div class="card p-3 border h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">tune</span> 5. Thiết Bị & Khuôn Ép
            </h6>
            <div class="small" style="line-height: 1.9;">
              Mã Thiết Bị (Col J): <strong class="text-primary font-monospace">${d.machine_code}</strong><br/>
              Mã Khuôn (Col X): <strong>${d.mold_code || '-'}</strong> | Spider (Col Y): <strong>${d.spider_code || '-'}</strong><br/>
              TG Dừng Máy (Col T): <strong>${d.stop_time_total} h</strong> | Chạy Máy (Col U): <strong>${d.run_time} h</strong><br/>
              Thời Gian Chu Kỳ (Col V): <strong>${d.cycle_time} s/m</strong><br/>
              Tỷ Lệ Khả Dụng (Col W): <strong class="text-success">${d.availability_rate}%</strong>
            </div>
          </div>
        </div>

        <!-- Nhóm 6: Nguyên vật liệu & Khác -->
        <div class="col-md-6">
          <div class="card p-3 border h-100">
            <h6 class="fw-bold text-secondary border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">science</span> 6. Nguyên Vật Liệu & Phụ Trợ
            </h6>
            <div class="small" style="line-height: 1.9;">
              Mã VL (Col P): <strong>${d.material_code || '-'}</strong> | Loại: <strong>${d.material_type || '-'}</strong><br/>
              Số Lần Nghiền (Col Q): <strong>${d.grind_num}</strong> | Mã Bì (Col R): <strong>${d.grind_package_code || '-'}</strong><br/>
              Lot In (Col S): <strong>${d.lot_in || '-'}</strong> | Thử Nghiệm: <strong>${d.is_trial == 1 ? 'Có' : 'Không'}</strong><br/>
              Máy In (Col AL): <strong>${d.printer_type || '-'}</strong> | Mực In: <strong>${d.ink_type || '-'}</strong><br/>
              Vệ Sinh: HDPE (${d.hdpe_material || '-'}), LIO (${d.lio_clean || '-'}), TI (${d.ti_clean || '-'})
            </div>
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    console.error('Lỗi openExtDetailModal:', err);
  }
}

// TIỆN ÍCH ĐỊNH DẠNG SỐ
function formatNum(v) {
  if (v === null || v === undefined || v === '') return '0';
  const n = parseFloat(v);
  if (isNaN(n)) return '0';
  return n.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
}

function formatShortNum(v) {
  if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M';
  if (v >= 1000) return (v / 1000).toFixed(0) + 'k';
  return v;
}
</script>
