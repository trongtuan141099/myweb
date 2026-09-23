<?php
/**
 * Module: Dashboard Quản Lý Tăng Ca
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
?>

<style>
/* Style riêng cho Overtime Dashboard */
.ot-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}
.ot-kpi-card {
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
.ot-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}
.ot-kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  flex-shrink: 0;
}
.ot-kpi-val {
  font-size: 24px;
  font-weight: 800;
  line-height: 1.1;
  color: var(--dx-text-main);
}
.ot-kpi-lbl {
  font-size: 11px;
  font-weight: 700;
  color: var(--dx-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-top: 3px;
}
.ot-chart-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  box-shadow: var(--dx-shadow-sm);
  margin-bottom: 20px;
  overflow: hidden;
}
.ot-chart-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
  font-size: 14px;
}
.ot-chart-body {
  padding: 16px;
  min-height: 280px;
}
.badge-warning-yellow {
  background-color: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
  font-weight: 700;
}
.badge-danger-red {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  font-weight: 700;
}
.badge-success-green {
  background-color: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  font-weight: 700;
}
[data-theme="dark"] .badge-warning-yellow { background-color: rgba(245, 158, 11, 0.18); color: #fbbf24; border-color: rgba(245, 158, 11, 0.35); }
[data-theme="dark"] .badge-danger-red { background-color: rgba(239, 68, 68, 0.18); color: #f87171; border-color: rgba(239, 68, 68, 0.35); }
[data-theme="dark"] .badge-success-green { background-color: rgba(34, 197, 94, 0.18); color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang & Bộ Lọc Nhanh -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">more_time</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">TỔNG QUAN QUẢN LÝ TĂNG CA (OVERTIME DASHBOARD)</h1>
        <p class="text-muted small mb-0">Theo dõi tăng ca thực tế, đối soát kế hoạch và kiểm soát giới hạn 200 giờ/năm theo luật lao động</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Chọn Tháng & Năm -->
      <select class="app-form-select app-form-select-sm" id="dashMonth" style="width: 120px;" onchange="loadDashboardData()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($m === 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
      <select class="app-form-select app-form-select-sm" id="dashYear" style="width: 100px;" onchange="loadDashboardData()">
        <option value="2026" selected>2026</option>
        <option value="2025">2025</option>
      </select>

      <a href="index.php?mainpage=overtime&subpage=import" class="app-btn app-btn-secondary btn-sm">
        <span class="material-icons fs-6">upload_file</span> Import Excel
      </a>
      <a href="index.php?mainpage=overtime&subpage=reconciliation" class="app-btn app-btn-primary btn-sm">
        <span class="material-icons fs-6">fact_check</span> Đối Soát
      </a>
    </div>
  </div>

  <!-- 2. Thẻ Chỉ Số KPI Điều Hành -->
  <div class="ot-kpi-grid">
    <!-- KPI 1: Nhân viên có OT -->
    <div class="ot-kpi-card">
      <div class="ot-kpi-icon" style="background: var(--dx-primary-light); color: var(--dx-primary);">
        <span class="material-icons">people</span>
      </div>
      <div>
        <div class="ot-kpi-val" id="kpiEmpCount">0</div>
        <div class="ot-kpi-lbl">Nhân viên tăng ca (tháng)</div>
      </div>
    </div>

    <!-- KPI 2: Tổng giờ OT tháng -->
    <div class="ot-kpi-card">
      <div class="ot-kpi-icon" style="background: #f0f9ff; color: #0284c7;">
        <span class="material-icons">schedule</span>
      </div>
      <div>
        <div class="ot-kpi-val" id="kpiHoursMonth">0.0 <span style="font-size: 14px; font-weight: 500;">h</span></div>
        <div class="ot-kpi-lbl">Tổng giờ OT trong tháng</div>
      </div>
    </div>

    <!-- KPI 3: Tổng giờ OT năm -->
    <div class="ot-kpi-card">
      <div class="ot-kpi-icon" style="background: #f0fdf4; color: #16a34a;">
        <span class="material-icons">trending_up</span>
      </div>
      <div>
        <div class="ot-kpi-val" id="kpiHoursYear">0.0 <span style="font-size: 14px; font-weight: 500;">h</span></div>
        <div class="ot-kpi-lbl">Tổng giờ OT lũy kế năm</div>
      </div>
    </div>

    <!-- KPI 4: Ca cần giải trình -->
    <div class="ot-kpi-card">
      <div class="ot-kpi-icon" style="background: #fffbeb; color: #d97706;">
        <span class="material-icons">rate_review</span>
      </div>
      <div>
        <div class="ot-kpi-val text-warning" id="kpiPendingExp">0</div>
        <div class="ot-kpi-lbl">Ca cần / chờ giải trình</div>
      </div>
    </div>

    <!-- KPI 5: Cảnh báo vượt 200h -->
    <div class="ot-kpi-card">
      <div class="ot-kpi-icon" style="background: #fef2f2; color: #dc2626;">
        <span class="material-icons">alarm_on</span>
      </div>
      <div>
        <div class="ot-kpi-val text-danger d-flex align-items-center gap-1">
          <span id="kpiRedWarnings">0</span>
          <span style="font-size: 14px; color: #b45309;" id="kpiYellowBadge">/ 0</span>
        </div>
        <div class="ot-kpi-lbl">Vượt trần (Đỏ) / Sắp chạm (Vàng)</div>
      </div>
    </div>
  </div>

  <!-- 3. Biểu Đồ Hàng 1: Xu Hướng & Phân Bổ Bộ Phận -->
  <div class="row g-3">
    <!-- Xu hướng OT 12 tháng -->
    <div class="col-12 col-xl-8">
      <div class="ot-chart-card">
        <div class="ot-chart-header">
          <div class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">show_chart</span>
            <span>Xu Hướng Giờ Tăng Ca 12 Tháng Năm <span id="trendYearLabel">2026</span> (Giờ)</span>
          </div>
        </div>
        <div class="ot-chart-body">
          <div id="chartMonthlyTrend" style="min-height: 280px;"></div>
        </div>
      </div>
    </div>

    <!-- Tỷ lệ OT theo bộ phận / tổ đội -->
    <div class="col-12 col-xl-4">
      <div class="ot-chart-card">
        <div class="ot-chart-header">
          <div class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">pie_chart</span>
            <span>Tỷ Lệ Giờ OT Theo Bộ Phận</span>
          </div>
        </div>
        <div class="ot-chart-body">
          <div id="chartDeptDistribution" style="min-height: 280px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Biểu Đồ Hàng 2: Top 10 Nhân Viên & Tỷ Lệ Đối Soát -->
  <div class="row g-3">
    <!-- Top 10 nhân viên OT cao nhất -->
    <div class="col-12 col-xl-7">
      <div class="ot-chart-card">
        <div class="ot-chart-header">
          <div class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">leaderboard</span>
            <span>Top 10 Nhân Viên Có Giờ Tăng Ca Cao Nhất Năm</span>
          </div>
        </div>
        <div class="ot-chart-body">
          <div id="chartTop10" style="min-height: 280px;"></div>
        </div>
      </div>
    </div>

    <!-- Tỷ lệ kết quả đối soát -->
    <div class="col-12 col-xl-5">
      <div class="ot-chart-card">
        <div class="ot-chart-header">
          <div class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">fact_check</span>
            <span>Tỷ Lệ Kết Quả Đối Soát Kế Hoạch vs Thực Tế</span>
          </div>
        </div>
        <div class="ot-chart-body">
          <div id="chartReconcilePie" style="min-height: 280px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. Bảng Cảnh Báo Khẩn Cấp Ngưỡng 200 Giờ/Năm -->
  <div class="app-card mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-danger">warning</span>
        <strong style="font-size: 15px;">DANH SÁCH NHÂN SỰ CẦN GIÁM SÁT NGƯỠNG 200 GIỜ (ƯU TIÊN CAN THIỆP)</strong>
      </div>
      <a href="index.php?mainpage=overtime&subpage=yearly_control" class="app-btn app-btn-secondary btn-sm">
        Xem tất cả nhân sự <span class="material-icons fs-6">arrow_forward</span>
      </a>
    </div>

    <div class="app-table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th style="width: 50px;">STT</th>
            <th>Mã NV</th>
            <th>Họ và Tên</th>
            <th>Bộ Phận / Cost Center</th>
            <th>Cấp Bậc</th>
            <th style="text-align: right;">Đã Làm (Giờ)</th>
            <th style="text-align: right;">Còn Lại Đến 200h</th>
            <th style="width: 170px;">% Giới Hạn (200h)</th>
            <th style="text-align: center; width: 140px;">Mức Cảnh Báo</th>
            <th style="text-align: center; width: 100px;">Hành Động</th>
          </tr>
        </thead>
        <tbody id="criticalTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Xem Chi Tiết Lịch Sử OT Của Nhân Viên -->
<div class="modal fade" id="empHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">history</span>
          Chi Tiết Lịch Sử Tăng Ca: <span id="modalEmpTitle">...</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div id="modalEmpSummaryCard" class="p-3 mb-3 rounded bg-light border"></div>
        <div class="app-table-responsive" style="max-height: 420px; overflow-y: auto;">
          <table class="app-table table-sm">
            <thead>
              <tr>
                <th>STT</th>
                <th>Ngày OT</th>
                <th>Bắt Đầu TT</th>
                <th>Kết Thúc TT</th>
                <th>Số Phút TT</th>
                <th>Số Giờ TT</th>
                <th>Lý Do Tăng Ca</th>
                <th>Cấp Trên Duyệt</th>
                <th>Trạng Thái Đối Soát</th>
              </tr>
            </thead>
            <tbody id="modalEmpHistoryBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Render Charts & API Calls -->
<script>
let chartMonthlyTrendInstance = null;
let chartDeptInstance = null;
let chartTop10Instance = null;
let chartReconcileInstance = null;

document.addEventListener('DOMContentLoaded', () => {
  loadDashboardData();
});

async function loadDashboardData() {
  const month = document.getElementById('dashMonth').value;
  const year = document.getElementById('dashYear').value;
  document.getElementById('trendYearLabel').textContent = year;

  try {
    const res = await fetch(`api/overtime_dashboard.php?month=${month}&year=${year}`);
    const data = await res.json();

    if (!data.success) {
      console.error('Lỗi API Dashboard:', data.message);
      return;
    }

    // 1. Cập nhật thẻ KPI Cards
    document.getElementById('kpiEmpCount').textContent = data.kpis.emp_count_month;
    document.getElementById('kpiHoursMonth').innerHTML = `${data.kpis.hours_month} <span style="font-size: 14px; font-weight: 500;">h</span>`;
    document.getElementById('kpiHoursYear').innerHTML = `${data.kpis.hours_year} <span style="font-size: 14px; font-weight: 500;">h</span>`;
    document.getElementById('kpiPendingExp').textContent = data.kpis.pending_explanations;
    document.getElementById('kpiRedWarnings').textContent = data.kpis.red_warnings;
    document.getElementById('kpiYellowBadge').textContent = `/ ${data.kpis.yellow_warnings} vàng`;

    // 2. Render Biểu đồ Xu hướng 12 Tháng
    renderMonthlyTrendChart(data.monthly_trend);

    // 3. Render Biểu đồ Tỷ lệ Bộ phận
    renderDeptDistributionChart(data.department_distribution);

    // 4. Render Biểu đồ Top 10
    renderTop10Chart(data.top_10);

    // 5. Render Biểu đồ Tỷ lệ Đối soát
    renderReconciliationPieChart(data.reconciliation_breakdown);

    // 6. Render Bảng Cảnh Báo Khẩn Cấp
    renderCriticalTable(data.critical_list);

  } catch (err) {
    console.error('Lỗi loadDashboardData:', err);
  }
}

function renderMonthlyTrendChart(trendData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: [{
      name: 'Giờ tăng ca thực tế',
      data: trendData
    }],
    chart: {
      type: 'area',
      height: 280,
      toolbar: { show: false },
      fontFamily: 'inherit'
    },
    colors: ['#1e40af'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2.5 },
    xaxis: {
      categories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
      labels: { style: { colors: isDark ? '#94a3b8' : '#64748b' } }
    },
    yaxis: {
      labels: {
        formatter: (val) => val.toFixed(0) + 'h',
        style: { colors: isDark ? '#94a3b8' : '#64748b' }
      }
    },
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100] }
    },
    grid: { borderColor: isDark ? '#1f293d' : '#e2e8f0' },
    tooltip: { y: { formatter: (val) => val + ' giờ' } }
  };

  if (chartMonthlyTrendInstance) chartMonthlyTrendInstance.destroy();
  chartMonthlyTrendInstance = new ApexCharts(document.querySelector("#chartMonthlyTrend"), options);
  chartMonthlyTrendInstance.render();
}

function renderDeptDistributionChart(deptData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: deptData.values.length > 0 ? deptData.values : [1],
    labels: deptData.labels.length > 0 ? deptData.labels : ['Chưa có dữ liệu'],
    chart: {
      type: 'donut',
      height: 280,
      fontFamily: 'inherit'
    },
    colors: ['#1e40af', '#0284c7', '#16a34a', '#d97706', '#7c3aed', '#ec4899'],
    legend: {
      position: 'bottom',
      labels: { colors: isDark ? '#94a3b8' : '#64748b' }
    },
    tooltip: { y: { formatter: (val) => val + ' giờ' } }
  };

  if (chartDeptInstance) chartDeptInstance.destroy();
  chartDeptInstance = new ApexCharts(document.querySelector("#chartDeptDistribution"), options);
  chartDeptInstance.render();
}

function renderTop10Chart(topData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const names = topData.map(d => `${d.full_name} (${d.employee_code})`);
  const values = topData.map(d => parseFloat(d.total_hours_year));
  const colors = topData.map(d => d.warning_level === 'red' ? '#dc2626' : (d.warning_level === 'yellow' ? '#d97706' : '#1e40af'));

  const options = {
    series: [{ name: 'Tổng giờ năm', data: values }],
    chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
    colors: colors,
    dataLabels: {
      enabled: true,
      formatter: (val) => val + 'h',
      style: { fontSize: '11px', colors: ['#fff'] }
    },
    xaxis: {
      categories: names,
      labels: {
        formatter: (val) => val + 'h',
        style: { colors: isDark ? '#94a3b8' : '#64748b' }
      }
    },
    yaxis: { labels: { style: { colors: isDark ? '#94a3b8' : '#64748b' } } },
    legend: { show: false },
    grid: { borderColor: isDark ? '#1f293d' : '#e2e8f0' }
  };

  if (chartTop10Instance) chartTop10Instance.destroy();
  chartTop10Instance = new ApexCharts(document.querySelector("#chartTop10"), options);
  chartTop10Instance.render();
}

function renderReconciliationPieChart(recData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const options = {
    series: [recData.matched, recData.time_diff, recData.plan_only, recData.actual_only, recData.overdue],
    labels: ['Hợp lệ (Khớp)', 'Lệch giờ/phút', 'Có KH thiếu TT', 'Có TT thiếu KH', 'Quá hạn duyệt 3 ngày'],
    chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
    colors: ['#16a34a', '#d97706', '#0284c7', '#ec4899', '#dc2626'],
    legend: { position: 'bottom', labels: { colors: isDark ? '#94a3b8' : '#64748b' } },
    tooltip: { y: { formatter: (val) => val + ' ca' } }
  };

  if (chartReconcileInstance) chartReconcileInstance.destroy();
  chartReconcileInstance = new ApexCharts(document.querySelector("#chartReconcilePie"), options);
  chartReconcileInstance.render();
}

function renderCriticalTable(list) {
  const tbody = document.getElementById('criticalTableBody');
  if (!list || list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted p-4"><span class="material-icons align-middle text-success">check_circle</span> Không có nhân sự nào chạm hoặc vượt ngưỡng 200 giờ.</td></tr>';
    return;
  }

  let html = '';
  list.forEach((item, idx) => {
    const isRed = item.warning_level === 'red';
    const badgeClass = isRed ? 'badge-danger-red' : 'badge-warning-yellow';
    const badgeText = isRed ? '🔴 ĐÃ VƯỢT (>=200h)' : '🟡 CẢNH BÁO (160-199h)';
    const barColor = isRed ? '#dc2626' : '#d97706';
    const pct = Math.min(100, parseFloat(item.usage_percent)).toFixed(1);

    html += `
      <tr>
        <td class="text-muted fw-bold">#${idx + 1}</td>
        <td><strong class="font-monospace text-primary">${item.employee_code}</strong></td>
        <td><strong>${item.full_name}</strong></td>
        <td>${item.department}</td>
        <td><span class="badge bg-secondary">${item.job_level}</span></td>
        <td style="text-align: right;"><strong class="${isRed ? 'text-danger' : 'text-warning'}">${item.total_hours_year} h</strong></td>
        <td style="text-align: right;">${item.remaining_hours} h</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height: 7px;">
              <div class="progress-bar" style="width: ${pct}%; background-color: ${barColor};"></div>
            </div>
            <small class="fw-bold">${item.usage_percent}%</small>
          </div>
        </td>
        <td style="text-align: center;">
          <span class="badge ${badgeClass} px-2 py-1">${badgeText}</span>
        </td>
        <td style="text-align: center;">
          <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openEmpHistory('${item.employee_code}', ${item.year})">
            Xem lịch sử
          </button>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

async function openEmpHistory(empCode, year) {
  try {
    const res = await fetch(`api/overtime_yearly.php?action=get_employee_history&employee_code=${empCode}&year=${year}`);
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('modalEmpTitle').textContent = `${data.employee.full_name} (${data.employee.employee_code})`;
    const acc = data.accumulation || {};
    document.getElementById('modalEmpSummaryCard').innerHTML = `
      <div class="row g-2 small">
        <div class="col-md-3"><strong>Phòng ban:</strong> ${data.employee.cost_center || '-'}</div>
        <div class="col-md-3"><strong>Cấp bậc:</strong> ${data.employee.job_level || '-'}</div>
        <div class="col-md-3"><strong>Tổng giờ năm ${year}:</strong> <span class="badge bg-primary fs-6">${acc.total_hours_year || 0} h</span></div>
        <div class="col-md-3"><strong>Số giờ còn lại:</strong> <span class="badge bg-success fs-6">${acc.remaining_hours || 0} h</span></div>
      </div>
    `;

    const tbody = document.getElementById('modalEmpHistoryBody');
    if (!data.history || data.history.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted p-3">Chưa có ca làm việc thực tế.</td></tr>';
    } else {
      let html = '';
      data.history.forEach((h, idx) => {
        html += `
          <tr>
            <td>${idx + 1}</td>
            <td><strong>${h.ot_date}</strong></td>
            <td>${h.start_time_actual}</td>
            <td>${h.end_time_actual}</td>
            <td>${h.total_minutes_actual} phút</td>
            <td><strong>${h.total_hours_actual} h</strong></td>
            <td><small>${h.reason || '-'}</small></td>
            <td><small>${h.direct_manager || '-'}</small></td>
            <td><span class="badge bg-light text-dark border">${h.reconcile_status || 'Chưa đối soát'}</span></td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }

    new bootstrap.Modal(document.getElementById('empHistoryModal')).show();
  } catch (err) {
    console.error('Lỗi openEmpHistory:', err);
  }
}
</script>

