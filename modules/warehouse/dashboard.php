<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Báo Cáo Thống Kê & Dashboard (Warehouse Dashboard)
 * DX Plastic Group - Factory Management System
 */

$currentMonth = intval(date('m'));
$currentYear  = intval(date('Y'));
$userRole     = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');
$canManage    = hasPermission(['warehouse.manage', 'admin']);
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang & Bộ Lọc Thời Gian -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">insights</span>
        <span data-i18n="nav.wh_dashboard"><?= __('nav.wh_dashboard', 'TỔNG QUAN XUẤT VẬT TƯ & CẢNH BÁO TỒN KHO') ?></span>
      </h1>
      <p class="app-page-subtitle">Thống kê xu hướng xuất kho hằng tháng, cơ cấu nhóm, danh sách mặt hàng tiêu hao nhiều nhất và kiểm soát điểm đặt hàng lại (ROP)</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <!-- Bộ lọc Tháng & Năm -->
      <div class="d-flex align-items-center gap-1 bg-white p-1 rounded border shadow-sm">
        <span class="material-icons text-muted ps-1" style="font-size: 18px;">calendar_month</span>
        <select class="form-select form-select-sm border-0 fw-bold text-primary" id="dashMonth" style="width: 115px;" onchange="loadWarehouseDashboard()">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m === 8) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
          <?php endfor; ?>
        </select>
        <select class="form-select form-select-sm border-0 fw-bold text-main" id="dashYear" style="width: 90px;" onchange="loadWarehouseDashboard()">
          <option value="2026" selected>2026</option>
          <option value="2025">2025</option>
        </select>
      </div>

      <a href="index.php?mainpage=warehouse&subpage=issue_request" class="app-btn app-btn-outline">
        <span class="material-icons">post_add</span>
        <span class="d-none d-sm-inline" data-i18n="common.btn_add"><?= __('common.btn_add', 'Tạo Phiếu') ?></span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-secondary">
        <span class="material-icons">verified_user</span>
        <span class="d-none d-sm-inline" data-i18n="common.btn_approve"><?= __('common.btn_approve', 'Xét Duyệt') ?></span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=materials" class="app-btn app-btn-primary">
        <span class="material-icons">category</span>
        <span class="d-none d-sm-inline">Danh Mục Vật Tư</span>
      </a>
    </div>
  </div>

  <!-- 1. Hàng Thẻ KPI Chính (Warehouse Executive KPI Cards) -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #3b82f6 !important;">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Phiếu Xuất Trong Tháng</div>
            <div class="h2 fw-bold text-main my-1" id="kpiTotalIssues">0</div>
            <div class="small text-muted">Kỳ: <span class="fw-semibold text-primary" id="kpiPeriodLabel">Tháng 8/2026</span></div>
          </div>
          <div class="p-2 rounded bg-primary-subtle text-primary">
            <span class="material-icons" style="font-size: 24px;">assignment</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #10b981 !important;">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Tổng Mặt Hàng Xuất</div>
            <div class="h2 fw-bold text-success my-1" id="kpiTotalItems">0</div>
            <div class="small text-muted">Dòng vật tư cấp phát</div>
          </div>
          <div class="p-2 rounded bg-success-subtle text-success">
            <span class="material-icons" style="font-size: 24px;">shopping_bag</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #8b5cf6 !important;">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Phiếu Đang Chờ Duyệt</div>
            <div class="h2 fw-bold my-1" style="color: #8b5cf6;" id="kpiPendingApproval">0</div>
            <div class="small text-muted">Các cấp đang xử lý</div>
          </div>
          <div class="p-2 rounded" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <span class="material-icons" style="font-size: 24px;">pending_actions</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #f59e0b !important;">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-semibold text-uppercase">Cảnh Báo Tồn Kho ROP</div>
            <div class="h2 fw-bold text-warning my-1" id="kpiRopAlerts">0</div>
            <div class="small text-muted">Vật tư &le; Điểm đặt hàng</div>
          </div>
          <div class="p-2 rounded bg-warning-subtle text-warning">
            <span class="material-icons" style="font-size: 24px;">notifications_active</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Hàng Biểu Đồ 1: Xu Hướng Tiêu Hao & Phân Bổ Theo Nhóm -->
  <div class="row g-3 mb-4">
    <!-- Biểu đồ Xu hướng tiêu hao theo tháng -->
    <div class="col-lg-8">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-main d-flex align-items-center gap-2">
            <span class="material-icons text-primary" style="font-size: 20px;">trending_up</span>
            <span>Xu Hướng Xuất Vật Tư Qua Các Tháng (Lịch Sử & Thực Tế)</span>
          </h6>
          <span class="badge bg-light text-dark border">Dữ liệu chuẩn T5 - T8/2026</span>
        </div>
        <div class="p-3">
          <div id="chartMonthlyTrend" style="min-height: 310px;"></div>
        </div>
      </div>
    </div>

    <!-- Biểu đồ Tỷ trọng theo Nhóm công việc -->
    <div class="col-lg-4">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-main d-flex align-items-center gap-2">
            <span class="material-icons text-primary" style="font-size: 20px;">donut_large</span>
            <span>Cơ Cấu Tiêu Hao Theo Nhóm</span>
          </h6>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Tháng được chọn</span>
        </div>
        <div class="p-3 d-flex flex-column align-items-center justify-content-center">
          <div id="chartGroupBreakdown" style="min-height: 290px; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Hàng Biểu Đồ 2: Top 10 Mặt Hàng Tiêu Hao Nhiều Nhất -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="app-card border">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-main d-flex align-items-center gap-2">
            <span class="material-icons text-primary" style="font-size: 20px;">leaderboard</span>
            <span>Top 10 Mặt Hàng Tiêu Hao Nhiều Nhất Trong Tháng</span>
          </h6>
          <span class="badge bg-light text-muted border">Xếp hạng theo khối lượng xuất</span>
        </div>
        <div class="p-3">
          <div id="chartTopMaterials" style="min-height: 320px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Bảng Cảnh Báo Tồn Kho Tối Thiểu (ROP) & Điểm Đặt Hàng Lại Cần Mua Sắm Bổ Sung -->
  <div class="app-card border mb-4">
    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h6 class="mb-0 fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-warning" style="font-size: 22px;">warning</span>
          <span>BẢNG CẢNH BÁO TỒN KHO TỐI THIỂU (ROP) & ĐỀ XUẤT ĐẶT HÀNG</span>
        </h6>
        <div class="small text-muted">Tự động phát cảnh báo gửi Thủ kho và Quản lý khi tồn kho chạm hoặc thấp hơn ngưỡng an toàn ROP</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="app-btn app-btn-outline btn-sm" onclick="loadReorderAlerts()">
          <span class="material-icons">refresh</span>
          <span>Làm mới cảnh báo</span>
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="tableLowStock">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 50px;">STT</th>
            <th style="width: 150px;">Mã Vật Tư / SAP</th>
            <th>Tên Mặt Hàng & Quy Cách</th>
            <th style="width: 120px;">Nhóm</th>
            <th class="text-center" style="width: 100px;">Vị Trí BIN</th>
            <th class="text-end" style="width: 120px;">Tồn Hiện Tại</th>
            <th class="text-center" style="width: 110px;">Ngưỡng ROP</th>
            <th class="text-end" style="width: 130px;" class="table-primary text-primary fw-bold">Đề Xuất Mua (MOQ)</th>
            <th class="text-center" style="width: 130px;">Dự Kiến Hết (Runway)</th>
            <th class="text-center" style="width: 140px;">Tình Trạng Xử Lý</th>
            <th class="text-end pe-3" style="width: 120px;">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="tbodyLowStock">
          <tr>
            <td colspan="11" class="text-center py-4 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>Đang kiểm tra mức tồn kho an toàn...</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL CẬP NHẬT TÌNH TRẠNG CẢNH BÁO ĐẶT HÀNG (ROP Alert Modal)
     ========================================================================= -->
<div class="modal fade" id="modalUpdateRopAlert" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">edit_note</span>
          <span>XỬ LÝ CẢNH BÁO ĐẶT HÀNG: <span id="mRopAlertCode" class="text-primary font-monospace">--</span></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="ropAlertId" value="0">
        <div class="mb-3">
          <label class="form-label small fw-bold">Tên vật tư cần bổ sung</label>
          <input type="text" class="form-control form-control-sm bg-light" id="mRopAlertName" readonly>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="p-2 border rounded bg-white text-center">
              <div class="small text-muted">Tồn hiện tại</div>
              <strong class="text-danger h6 mb-0" id="mRopAlertCurrentStock">0</strong>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2 border rounded bg-white text-center">
              <div class="small text-muted">Đề xuất mua</div>
              <strong class="text-success h6 mb-0" id="mRopAlertMoq">0</strong>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Trạng thái xử lý đặt hàng <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="mRopAlertStatus">
            <option value="pending">Chờ đặt hàng (Pending)</option>
            <option value="ordered">Đã gửi đơn đặt hàng / PR-PO (Ordered)</option>
            <option value="completed">Đã nhập kho bổ sung (Completed)</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Ghi chú của Quản lý / Thủ kho</label>
          <textarea class="form-control form-control-sm" id="mRopAlertNotes" rows="3" placeholder="Nhập số PO, mã PR, ngày dự kiến hàng về..."></textarea>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="app-btn app-btn-primary" onclick="submitRopAlertUpdate()">
          <span class="material-icons">save</span>
          <span>Lưu Cập Nhật</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let chartMonthlyTrendInstance = null;
let chartGroupBreakdownInstance = null;
let chartTopMaterialsInstance = null;
let currentDashboardData = null;

document.addEventListener('DOMContentLoaded', function() {
  loadWarehouseDashboard();
});

// 1. Tải toàn bộ dữ liệu thống kê Dashboard
function loadWarehouseDashboard() {
  const month = document.getElementById('dashMonth').value;
  const year  = document.getElementById('dashYear').value;

  document.getElementById('kpiPeriodLabel').textContent = `Tháng ${month}/${year}`;

  fetch(`api/warehouse.php?action=get_dashboard&month=${month}&year=${year}`)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        console.error('Dashboard API Error:', res.message);
        return;
      }

      currentDashboardData = res.data;
      updateKpis(res.data.kpi || {});
      renderMonthlyTrendChart(res.data.trend || {});
      renderGroupBreakdownChart(res.data.group_breakdown || {});
      renderTopMaterialsChart(res.data.top_materials || []);
      renderLowStockTable(res.data.low_stock_list || []);
    })
    .catch(err => {
      console.error('Fetch dashboard error:', err);
    });
}

// 2. Cập nhật thẻ KPI
function updateKpis(kpi) {
  document.getElementById('kpiTotalIssues').textContent = kpi.total_issues || 0;
  document.getElementById('kpiTotalItems').textContent = kpi.total_items || 0;
  document.getElementById('kpiPendingApproval').textContent = kpi.pending_approval || 0;
  document.getElementById('kpiRopAlerts').textContent = kpi.rop_alerts || 0;
}

// 3. Biểu đồ Xu hướng tiêu hao qua các tháng (Monthly Trend)
function renderMonthlyTrendChart(trend) {
  const el = document.getElementById('chartMonthlyTrend');
  if (!el) return;

  const months = trend.months || ['Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8'];
  const seriesData = trend.series || [2250, 2410, 2580, 2690];

  const options = {
    series: [{
      name: 'Tổng số lượng vật tư xuất',
      data: seriesData
    }],
    chart: {
      type: 'area',
      height: 310,
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    colors: ['#3b82f6'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.45,
        opacityTo: 0.05,
        stops: [0, 90, 100]
      }
    },
    xaxis: {
      categories: months,
      labels: { style: { colors: '#64748b', fontSize: '12px' } }
    },
    yaxis: {
      labels: {
        style: { colors: '#64748b' },
        formatter: (val) => val.toLocaleString('vi-VN')
      }
    },
    tooltip: {
      y: { formatter: (val) => `${val.toLocaleString('vi-VN')} đơn vị` }
    },
    grid: { borderColor: '#f1f5f9' }
  };

  if (chartMonthlyTrendInstance) {
    chartMonthlyTrendInstance.destroy();
  }
  chartMonthlyTrendInstance = new ApexCharts(el, options);
  chartMonthlyTrendInstance.render();
}

// 4. Biểu đồ Tỷ trọng theo Nhóm (Donut Chart)
function renderGroupBreakdownChart(breakdown) {
  const el = document.getElementById('chartGroupBreakdown');
  if (!el) return;

  const labels = breakdown.labels || ['Thiết bị', 'Bảo trì khuôn', 'Sản xuất', 'Nghiền'];
  const series = (breakdown.series && breakdown.series.length > 0) ? breakdown.series : [45, 25, 20, 10];

  // Nếu series toàn 0
  const hasData = series.some(v => v > 0);
  const displaySeries = hasData ? series : [1, 1, 1, 1];

  const options = {
    series: displaySeries,
    labels: labels,
    chart: {
      type: 'donut',
      height: 290
    },
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
    legend: {
      position: 'bottom',
      fontSize: '12px',
      markers: { width: 10, height: 10, radius: 10 }
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Tổng xuất',
              formatter: () => hasData ? `${series.reduce((a,b)=>a+b,0)}` : '0'
            }
          }
        }
      }
    },
    tooltip: {
      y: { formatter: (val) => `${val} món` }
    }
  };

  if (chartGroupBreakdownInstance) {
    chartGroupBreakdownInstance.destroy();
  }
  chartGroupBreakdownInstance = new ApexCharts(el, options);
  chartGroupBreakdownInstance.render();
}

// 5. Biểu đồ Top 10 Vật Tư Tiêu Hao Nhiều Nhất (Horizontal Bar Chart)
function renderTopMaterialsChart(topMaterials) {
  const el = document.getElementById('chartTopMaterials');
  if (!el) return;

  if (topMaterials.length === 0) {
    el.innerHTML = '<div class="text-center py-5 text-muted">Chưa có dữ liệu tiêu hao trong tháng này.</div>';
    return;
  }

  const categories = topMaterials.map(m => m.material_name.length > 25 ? m.material_name.substring(0, 25) + '...' : m.material_name);
  const dataSeries = topMaterials.map(m => parseFloat(m.total_issued) || 0);

  const options = {
    series: [{
      name: 'Số lượng xuất',
      data: dataSeries
    }],
    chart: {
      type: 'bar',
      height: 320,
      toolbar: { show: false }
    },
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: true,
        dataLabels: { position: 'top' }
      }
    },
    colors: ['#3b82f6'],
    dataLabels: {
      enabled: true,
      formatter: (val) => val.toLocaleString('vi-VN'),
      offsetX: 20,
      style: { fontSize: '11px', colors: ['#334155'] }
    },
    xaxis: {
      categories: categories,
      labels: { style: { colors: '#64748b' } }
    },
    yaxis: {
      labels: { style: { colors: '#1e293b', fontWeight: 600, fontSize: '12px' } }
    },
    grid: { borderColor: '#f1f5f9' },
    tooltip: {
      y: { formatter: (val) => `${val.toLocaleString('vi-VN')}` }
    }
  };

  if (chartTopMaterialsInstance) {
    chartTopMaterialsInstance.destroy();
  }
  chartTopMaterialsInstance = new ApexCharts(el, options);
  chartTopMaterialsInstance.render();
}

// 6. Render Bảng Cảnh Báo Tồn Kho Tối Thiểu (ROP)
function renderLowStockTable(lowStockList) {
  const tbody = document.getElementById('tbodyLowStock');
  if (lowStockList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" class="text-center py-4 text-success fw-semibold">
          <span class="material-icons align-middle me-1">verified</span>
          Tuyệt vời! Toàn bộ vật tư trong kho đều đang ở ngưỡng an toàn &gt; ROP.
        </td>
      </tr>`;
    return;
  }

  let html = '';
  lowStockList.forEach((it, idx) => {
    const stock = parseFloat(it.stock_current) || 0;
    const rop   = parseFloat(it.reorder_point) || 0;
    const moq   = parseFloat(it.reorder_qty) || 0;

    let runwayBadge = '';
    if (it.runway_months !== null && it.runway_months !== undefined) {
      const rw = parseFloat(it.runway_months);
      if (rw <= 0.5) runwayBadge = `<span class="badge bg-danger">Hết hàng (${rw} thg)</span>`;
      else if (rw <= 1.0) runwayBadge = `<span class="badge bg-warning text-dark">&lt; 1 tháng (${rw})</span>`;
      else runwayBadge = `<span class="badge bg-secondary-subtle text-dark">${rw} tháng</span>`;
    } else {
      runwayBadge = '<span class="text-muted">N/A</span>';
    }

    // Trạng thái xử lý
    let statusBadge = '';
    const alertSt = it.alert_status || 'pending';
    if (alertSt === 'completed') statusBadge = '<span class="badge bg-success">Đã nhập kho</span>';
    else if (alertSt === 'ordered') statusBadge = '<span class="badge bg-primary">Đã đặt hàng</span>';
    else statusBadge = '<span class="badge bg-warning text-dark">Chờ đặt hàng</span>';

    html += `
      <tr>
        <td class="ps-3 text-muted fw-bold">${idx + 1}</td>
        <td>
          <strong class="font-monospace text-primary">${escapeHtml(it.material_code)}</strong>
          ${it.sap_code ? `<div class="small text-muted font-monospace">SAP: ${escapeHtml(it.sap_code)}</div>` : ''}
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(it.material_name)}</div>
          <div class="small text-muted">Quy cách: ${escapeHtml(it.pack_spec || 'Gói lẻ')}</div>
        </td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(it.group_name)}</span></td>
        <td class="text-center font-monospace small">
          ${it.bin_location ? `<span class="badge bg-light text-secondary border">${escapeHtml(it.bin_location)}</span>` : '<span class="text-muted">--</span>'}
        </td>
        <td class="text-end">
          <strong class="text-danger font-monospace" style="font-size: 15px;">${stock}</strong>
          <span class="small text-muted">${it.unit}</span>
        </td>
        <td class="text-center font-monospace fw-bold text-warning">${rop}</td>
        <td class="text-end table-primary font-monospace fw-bold text-primary">${moq} ${it.unit}</td>
        <td class="text-center">${runwayBadge}</td>
        <td class="text-center">${statusBadge}</td>
        <td class="text-end pe-3">
          <button class="app-btn app-btn-outline btn-sm" onclick="openUpdateRopModal(${it.alert_id || 0}, '${escapeHtml(it.material_code)}', '${escapeHtml(it.material_name)}', ${stock}, ${moq}, '${alertSt}', '${escapeHtml(it.admin_notes || '')}')">
            <span class="material-icons" style="font-size: 15px;">edit</span>
            <span>Xử lý</span>
          </button>
        </td>
      </tr>`;
  });

  tbody.innerHTML = html;
}

// 7. Mở modal xử lý cảnh báo đặt hàng
function openUpdateRopModal(alertId, code, name, stock, moq, status, notes) {
  document.getElementById('ropAlertId').value = alertId;
  document.getElementById('mRopAlertCode').textContent = code;
  document.getElementById('mRopAlertName').value = name;
  document.getElementById('mRopAlertCurrentStock').textContent = stock;
  document.getElementById('mRopAlertMoq').textContent = moq;
  document.getElementById('mRopAlertStatus').value = status;
  document.getElementById('mRopAlertNotes').value = notes;

  const modal = new bootstrap.Modal(document.getElementById('modalUpdateRopAlert'));
  modal.show();
}

// 8. Lưu cập nhật tình trạng cảnh báo đặt hàng
function submitRopAlertUpdate() {
  const alertId = document.getElementById('ropAlertId').value;
  const status  = document.getElementById('mRopAlertStatus').value;
  const notes   = document.getElementById('mRopAlertNotes').value.trim();

  const formData = new FormData();
  formData.append('alert_id', alertId);
  formData.append('status', status);
  formData.append('admin_notes', notes);

  fetch('api/warehouse.php?action=update_reorder_alert', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Cập nhật tình trạng đặt hàng thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalUpdateRopAlert'));
        if (modal) modal.hide();
        loadWarehouseDashboard();
      } else {
        alert(res.message || 'Không thể cập nhật cảnh báo.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi cập nhật cảnh báo.');
    });
}

function loadReorderAlerts() {
  loadWarehouseDashboard();
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>
