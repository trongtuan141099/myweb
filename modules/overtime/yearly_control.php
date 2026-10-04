<?php
/**
 * Module: Kiểm Soát Giới Hạn Tăng Ca 200 Giờ/Năm
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();
requirePermission('overtime.yearly');

$currentYear = intval(date('Y'));
?>

<style>
.tab-pill-btn {
  cursor: pointer;
  padding: 8px 16px;
  border-radius: var(--dx-radius-sm);
  font-weight: 700;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  color: var(--dx-text-muted);
}
.tab-pill-btn.active {
  background: var(--dx-primary);
  color: #fff;
  border-color: var(--dx-primary);
}
.tab-pill-btn.btn-warning-tab.active {
  background: #d97706;
  border-color: #d97706;
  color: #fff;
}
.tab-pill-btn.btn-danger-tab.active {
  background: #dc2626;
  border-color: #dc2626;
  color: #fff;
}

/* Tô xám toàn bộ thông tin nhân sự đã có ngày nghỉ việc */
.row-resigned {
  background-color: rgba(148, 163, 184, 0.15) !important;
  color: #64748b !important;
  opacity: 0.65;
}
.row-resigned td {
  background-color: transparent !important;
  color: inherit !important;
}
.row-resigned .font-monospace {
  color: #64748b !important;
}
.row-resigned:hover {
  background-color: rgba(148, 163, 184, 0.25) !important;
  opacity: 0.9;
}
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-danger" style="font-size: 28px;">alarm_on</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">KIỂM SOÁT GIỚI HẠN TĂNG CA 200 GIỜ/NĂM</h1>
        <p class="text-muted small mb-0">Theo dõi lũy kế giờ làm thêm theo tháng & năm, ngăn chặn vi phạm trần 200 giờ theo Điều 107 Bộ luật Lao động</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Chọn Năm -->
      <select class="app-form-select app-form-select-sm" id="controlYear" style="width: 110px;" onchange="loadYearlyControl()">
        <option value="2026" selected>Năm 2026</option>
        <option value="2025">Năm 2025</option>
      </select>

      <a href="api/overtime_export.php?type=warning_200h&year=2026" class="app-btn app-btn-secondary btn-sm" id="btnExportWarning">
        <span class="material-icons fs-6">file_download</span> Xuất DS Cảnh Báo
      </a>
    </div>
  </div>

  <!-- 2. Thẻ Chỉ Số KPI Kiểm Soát Ngưỡng -->
  <div class="row g-2 mb-3">
    <div class="col-md" style="flex: 1 1 190px;">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: var(--dx-primary-light); color: var(--dx-primary);">
          <span class="material-icons">groups</span>
        </div>
        <div>
          <div class="ot-kpi-val" id="ycTotalEmp">0</div>
          <div class="ot-kpi-lbl">Tổng nhân sự theo dõi</div>
        </div>
      </div>
    </div>

    <div class="col-md" style="flex: 1 1 190px;">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: #f0fdf4; color: #16a34a;">
          <span class="material-icons">verified_user</span>
        </div>
        <div>
          <div class="ot-kpi-val text-success" id="ycGreenCount">0</div>
          <div class="ot-kpi-lbl">An toàn (&lt; 160h)</div>
        </div>
      </div>
    </div>

    <div class="col-md" style="flex: 1 1 190px;">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: #fffbeb; color: #d97706;">
          <span class="material-icons">warning</span>
        </div>
        <div>
          <div class="ot-kpi-val text-warning" id="ycYellowCount">0</div>
          <div class="ot-kpi-lbl">Cảnh báo năm (160 - 199h)</div>
        </div>
      </div>
    </div>

    <div class="col-md" style="flex: 1 1 190px;">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: #fef2f2; color: #dc2626;">
          <span class="material-icons">error</span>
        </div>
        <div>
          <div class="ot-kpi-val text-danger" id="ycRedCount">0</div>
          <div class="ot-kpi-lbl">Vượt trần năm (&ge; 200h)</div>
        </div>
      </div>
    </div>

    <!-- Thẻ KPI Cảnh báo Tháng > 36h / tháng 40h -->
    <div class="col-md" style="flex: 1 1 210px;">
      <div class="app-card p-3 d-flex align-items-center gap-3" style="border-left: 3px solid #f59e0b;">
        <div class="ot-kpi-icon" style="background: #fffbeb; color: #d97706;">
          <span class="material-icons">alarm</span>
        </div>
        <div>
          <div class="ot-kpi-val text-warning" id="ycMonthWarningCount">0</div>
          <div class="ot-kpi-lbl">Cảnh báo tháng (&gt; 36h/40h)</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Tabs Phân Loại Mức Cảnh Báo & Tìm Kiếm -->
  <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="tab-pill-btn active" onclick="setWarningFilter('', this)">
        Tất cả nhân sự
      </div>
      <div class="tab-pill-btn btn-warning-tab" onclick="setWarningFilter('month_warning_36h', this)" title="Lọc các nhân viên có tháng vượt trên 36 giờ (ngưỡng 90% giới hạn tháng)">
        <span class="material-icons fs-6">alarm</span> Cảnh báo tháng (&gt; 36h)
      </div>
      <div class="tab-pill-btn btn-warning-tab" onclick="setWarningFilter('yellow', this)">
        <span class="material-icons fs-6">warning</span> Cảnh báo năm (160 - 199.9h)
      </div>
      <div class="tab-pill-btn btn-danger-tab" onclick="setWarningFilter('red', this)">
        <span class="material-icons fs-6">error</span> Báo động Đỏ (&ge; 200h)
      </div>
    </div>

    <!-- Tìm kiếm & Bộ phận -->
    <div class="d-flex align-items-center gap-2">
      <select class="app-form-select app-form-select-sm" id="ycDeptSelect" style="width: 200px;" onchange="loadYearlyControl()">
        <option value="">-- Tất cả phòng ban --</option>
      </select>
      <input type="text" class="app-form-control form-control-sm" id="ycSearchInput" placeholder="Tìm tên hoặc mã NV..." style="width: 200px;" oninput="handleYcSearch(this.value)">
    </div>
  </div>

  <!-- 4. Bảng Lũy Kế 12 Tháng & Tiến Độ 200 Giờ -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 380px); overflow-y: auto;">
      <table class="app-table table-sticky-header table-sm">
        <thead>
          <tr>
            <th style="width: 45px;">STT</th>
            <th>Mã NV</th>
            <th>Họ và Tên</th>
            <th>Phòng Ban</th>
            <th style="width: 50px;">Cấp</th>
            <!-- 12 Tháng -->
            <th style="text-align: right; width: 45px;">T1</th>
            <th style="text-align: right; width: 45px;">T2</th>
            <th style="text-align: right; width: 45px;">T3</th>
            <th style="text-align: right; width: 45px;">T4</th>
            <th style="text-align: right; width: 45px;">T5</th>
            <th style="text-align: right; width: 45px;">T6</th>
            <th style="text-align: right; width: 45px;">T7</th>
            <th style="text-align: right; width: 45px;">T8</th>
            <th style="text-align: right; width: 45px;">T9</th>
            <th style="text-align: right; width: 45px;">T10</th>
            <th style="text-align: right; width: 45px;">T11</th>
            <th style="text-align: right; width: 45px;">T12</th>
            <!-- Tổng & Tiến độ -->
            <th style="text-align: right; width: 95px; background: rgba(30, 64, 175, 0.05);">Tổng Năm</th>
            <th style="text-align: right; width: 85px;">Còn Lại</th>
            <th style="width: 140px;">Tiến Độ 200h</th>
            <th style="text-align: center; width: 120px;">Cảnh Báo</th>
            <th style="text-align: center; width: 80px;">Lịch Sử</th>
          </tr>
        </thead>
        <tbody id="ycTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>
    <!-- Chú thích quy định Điều 107 BLLĐ -->
    <div class="p-3 border-top bg-light-subtle d-flex align-items-center gap-2 small text-muted">
      <span class="material-icons text-primary fs-5">info</span>
      <div>
        <strong>Quy định Điều 107 Bộ luật Lao động:</strong> Giới hạn làm thêm tối đa <strong>40 giờ/tháng</strong> và <strong>200 giờ/năm</strong>.
        Hệ thống tự động kích hoạt <span class="badge bg-warning text-dark border border-warning">Cảnh báo tháng &gt; 36h</span> khi đạt từ 36.1 giờ (ngưỡng 90% giới hạn tháng) nhằm giúp quản trị viên chủ động điều chuyển nhân sự, ngăn chặn vượt trần 40h/tháng.
      </div>
    </div>
  </div>
</div>

<script>
let currentWarningLevel = '';
let ycSearchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
  loadYearlyControl();
});

function setWarningFilter(level, el) {
  currentWarningLevel = level;
  document.querySelectorAll('.tab-pill-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  loadYearlyControl();
}

function handleYcSearch(val) {
  clearTimeout(ycSearchTimeout);
  ycSearchTimeout = setTimeout(() => {
    loadYearlyControl();
  }, 300);
}

function renderMonthCell(hours) {
  const h = parseFloat(hours) || 0;
  if (h <= 0) return '<small class="text-muted">-</small>';
  if (h > 40.0) {
    return `<span class="badge bg-danger text-white py-1 px-1 font-monospace fw-bold" style="font-size: 11px;" title="VƯỢT TRẦN THÁNG QUY ĐỊNH (>40h/tháng: ${h}h)">${h}h ⛔</span>`;
  }
  if (h > 36.0) {
    return `<span class="badge bg-warning text-dark border border-warning py-1 px-1 font-monospace fw-bold" style="font-size: 11px;" title="CẢNH BÁO: Tăng ca tháng vượt 36h/40h (${h}h)">${h}h ⚠️</span>`;
  }
  return `<small class="fw-bold">${h}h</small>`;
}

async function loadYearlyControl() {
  const year = document.getElementById('controlYear').value;
  const dept = document.getElementById('ycDeptSelect').value;
  const search = document.getElementById('ycSearchInput').value.trim();
  const tbody = document.getElementById('ycTableBody');

  document.getElementById('btnExportWarning').href = `api/overtime_export.php?type=warning_200h&year=${year}`;
  tbody.innerHTML = '<tr><td colspan="22" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tính toán dữ liệu lũy kế năm...</td></tr>';

  try {
    const url = `api/overtime_yearly.php?action=get_yearly_summary&year=${year}&warning_level=${encodeURIComponent(currentWarningLevel)}&department=${encodeURIComponent(dept)}&search=${encodeURIComponent(search)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="22" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    // Cập nhật thẻ KPI
    const st = data.stats || {};
    document.getElementById('ycTotalEmp').textContent = st.total_employees || 0;
    document.getElementById('ycGreenCount').textContent = st.count_green || 0;
    document.getElementById('ycYellowCount').textContent = st.count_yellow || 0;
    document.getElementById('ycRedCount').textContent = st.count_red || 0;
    if (document.getElementById('ycMonthWarningCount')) {
      document.getElementById('ycMonthWarningCount').textContent = st.count_month_warning_36h || 0;
    }

    // Đổ danh sách phòng ban nếu chưa có
    const deptSel = document.getElementById('ycDeptSelect');
    if (deptSel.options.length <= 1 && data.departments) {
      data.departments.forEach(d => {
        deptSel.innerHTML += `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`;
      });
    }

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="22" class="text-center text-muted p-4">Không tìm thấy nhân viên nào phù hợp.</td></tr>';
      return;
    }

    let html = '';
    data.data.forEach((r, idx) => {
      const isRed = r.warning_level === 'red';
      const isYellow = r.warning_level === 'yellow';
      const badgeText = isRed ? '🔴 VƯỢT TRẦN NĂM' : (isYellow ? '🟡 CẢNH BÁO NĂM' : '🟢 An toàn năm');
      const badgeClass = isRed ? 'badge-danger-red' : (isYellow ? 'badge-warning-yellow' : 'badge-success-green');
      const barColor = isRed ? '#dc2626' : (isYellow ? '#d97706' : '#16a34a');
      const pct = Math.min(100, parseFloat(r.usage_percent)).toFixed(1);

      // Nhãn cảnh báo các tháng vượt 36h hoặc 40h
      let monthWarningBadges = '';
      if (r.month_warnings && r.month_warnings.length > 0) {
        monthWarningBadges = '<div class="mt-1 d-flex flex-wrap gap-1 justify-content-center">';
        r.month_warnings.forEach(mw => {
          const bClass = mw.level === 'red' ? 'bg-danger text-white' : 'bg-warning text-dark border border-warning';
          monthWarningBadges += `<span class="badge ${bClass}" style="font-size: 9px;" title="${escapeHtml(mw.title)}">⚠️ ${mw.label}: ${mw.hours}h</span>`;
        });
        monthWarningBadges += '</div>';
      }

      const isResigned = r.has_resigned || (r.resignation_date && r.resignation_date !== '-' && r.resignation_date !== '0000-00-00');

      html += `
        <tr class="${isResigned ? 'row-resigned' : ''}">
          <td class="text-muted fw-bold">#${idx + 1}</td>
          <td><strong class="font-monospace text-primary">${r.employee_code}</strong></td>
          <td>
            <strong>${escapeHtml(r.full_name)}</strong>
            ${isResigned ? `<span class="badge bg-secondary text-white ms-1" style="font-size: 10px;" title="Ngày nghỉ việc">Đã nghỉ (${escapeHtml(r.resignation_date)})</span>` : ''}
          </td>
          <td><small>${escapeHtml(r.department)}</small></td>
          <td><span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">${r.job_level}</span></td>

          <!-- 12 Tháng: Hiển thị cảnh báo vàng khi > 36h và đỏ khi > 40h -->
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m1)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m2)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m3)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m4)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m5)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m6)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m7)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m8)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m9)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m10)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m11)}</td>
          <td style="text-align: right;">${renderMonthCell(r.total_hours_m12)}</td>

          <!-- Tổng & Tiến độ -->
          <td style="text-align: right; background: rgba(30, 64, 175, 0.05);"><strong class="${isRed ? 'text-danger' : (isYellow ? 'text-warning' : 'text-primary')}">${r.total_hours_year}h</strong></td>
          <td style="text-align: right;"><small class="fw-bold">${r.remaining_hours}h</small></td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <div class="progress flex-grow-1" style="height: 6px;">
                <div class="progress-bar" style="width: ${pct}%; background-color: ${barColor};"></div>
              </div>
              <small style="font-size: 10px;" class="fw-bold">${r.usage_percent}%</small>
            </div>
          </td>
          <td style="text-align: center;">
            <span class="badge ${badgeClass} px-2 py-1 small">${badgeText}</span>
            ${monthWarningBadges}
          </td>
          <td style="text-align: center;">
            <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openEmpHistoryModal('${r.employee_code}', ${r.year})">
              Xem
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  } catch (err) {
    console.error('Lỗi loadYearlyControl:', err);
    tbody.innerHTML = '<tr><td colspan="22" class="text-danger text-center p-3">Lỗi kết nối máy chủ</td></tr>';
  }
}

async function openEmpHistoryModal(empCode, year) {
  try {
    const res = await fetch(`api/overtime_yearly.php?action=get_employee_history&employee_code=${empCode}&year=${year}`);
    const data = await res.json();
    if (!data.success) return;

    alert(`Nhân viên: ${data.employee.full_name} (${data.employee.employee_code})\n- Tổng giờ năm ${year}: ${data.accumulation.total_hours_year || 0} giờ\n- Số giờ còn lại: ${data.accumulation.remaining_hours || 0} giờ\n- Số ca thực tế đã làm: ${data.history.length} ca`);
  } catch (err) {
    console.error('Lỗi openEmpHistoryModal:', err);
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

