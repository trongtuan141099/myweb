<?php
/**
 * Module: Kiểm Soát Giới Hạn Tăng Ca 200 Giờ/Năm
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

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
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: var(--dx-primary-light); color: var(--dx-primary);">
          <span class="material-icons">groups</span>
        </div>
        <div>
          <div class="ot-kpi-val" id="ycTotalEmp">0</div>
          <div class="ot-kpi-lbl">Tổng nhân sự có OT năm</div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
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

    <div class="col-md-3">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: #fffbeb; color: #d97706;">
          <span class="material-icons">warning</span>
        </div>
        <div>
          <div class="ot-kpi-val text-warning" id="ycYellowCount">0</div>
          <div class="ot-kpi-lbl">Cảnh báo (160h - 199.9h)</div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="app-card p-3 d-flex align-items-center gap-3">
        <div class="ot-kpi-icon" style="background: #fef2f2; color: #dc2626;">
          <span class="material-icons">error</span>
        </div>
        <div>
          <div class="ot-kpi-val text-danger" id="ycRedCount">0</div>
          <div class="ot-kpi-lbl">Vượt quy định (&ge; 200h)</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Tabs Phân Loại Mức Cảnh Báo & Tìm Kiếm -->
  <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div class="d-flex align-items-center gap-2">
      <div class="tab-pill-btn active" onclick="setWarningFilter('', this)">
        Tất cả nhân sự
      </div>
      <div class="tab-pill-btn btn-warning-tab" onclick="setWarningFilter('yellow', this)">
        <span class="material-icons fs-6">warning</span> Cảnh báo Vàng (160 - 199.9h)
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
      const badgeText = isRed ? '🔴 VƯỢT TRẦN' : (isYellow ? '🟡 CẢNH BÁO' : '🟢 An toàn');
      const badgeClass = isRed ? 'badge-danger-red' : (isYellow ? 'badge-warning-yellow' : 'badge-success-green');
      const barColor = isRed ? '#dc2626' : (isYellow ? '#d97706' : '#16a34a');
      const pct = Math.min(100, parseFloat(r.usage_percent)).toFixed(1);

      html += `
        <tr>
          <td class="text-muted fw-bold">#${idx + 1}</td>
          <td><strong class="font-monospace text-primary">${r.employee_code}</strong></td>
          <td><strong>${escapeHtml(r.full_name)}</strong></td>
          <td><small>${escapeHtml(r.department)}</small></td>
          <td><span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">${r.job_level}</span></td>

          <!-- 12 Tháng -->
          <td style="text-align: right;"><small class="${r.total_hours_m1 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m1 > 0 ? r.total_hours_m1 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m2 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m2 > 0 ? r.total_hours_m2 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m3 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m3 > 0 ? r.total_hours_m3 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m4 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m4 > 0 ? r.total_hours_m4 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m5 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m5 > 0 ? r.total_hours_m5 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m6 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m6 > 0 ? r.total_hours_m6 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m7 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m7 > 0 ? r.total_hours_m7 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m8 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m8 > 0 ? r.total_hours_m8 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m9 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m9 > 0 ? r.total_hours_m9 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m10 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m10 > 0 ? r.total_hours_m10 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m11 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m11 > 0 ? r.total_hours_m11 : '-'}</small></td>
          <td style="text-align: right;"><small class="${r.total_hours_m12 > 0 ? 'fw-bold' : 'text-muted'}">${r.total_hours_m12 > 0 ? r.total_hours_m12 : '-'}</small></td>

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

