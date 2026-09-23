<?php
/**
 * Module: Tra Cứu Dữ Liệu Tăng Ca Chi Tiết (Kế Hoạch & Thực Tế)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
?>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">list_alt</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">TRA CỨU DỮ LIỆU TĂNG CA CHI TIẾT</h1>
        <p class="text-muted small mb-0">Xem và lọc dữ liệu gốc đã được phê duyệt từ hệ thống HRM (Kế hoạch và Thực tế)</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Chọn Tháng & Năm -->
      <select class="app-form-select app-form-select-sm" id="recFilterMonth" style="width: 120px;" onchange="loadRecordsData()">
        <option value="">-- Cả năm --</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($m === 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
      <select class="app-form-select app-form-select-sm" id="recFilterYear" style="width: 100px;" onchange="loadRecordsData()">
        <option value="2026" selected>2026</option>
        <option value="2025">2025</option>
      </select>
    </div>
  </div>

  <!-- 2. Tabs Chuyển Đổi Kế Hoạch / Thực Tế -->
  <ul class="nav nav-tabs mb-3" id="recordsTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-bold d-flex align-items-center gap-1" id="tabPlanBtn" onclick="switchRecordType('plan')">
        <span class="material-icons fs-6">calendar_month</span> Dữ Liệu Kế Hoạch (Plan OT)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-bold d-flex align-items-center gap-1" id="tabActualBtn" onclick="switchRecordType('actual')">
        <span class="material-icons fs-6">done_all</span> Dữ Liệu Thực Tế (Actual OT)
      </button>
    </li>
  </ul>

  <!-- 3. Toolbar Tìm Kiếm -->
  <div class="app-filter-card mb-3">
    <div class="row g-2 w-100 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">search</span>
          </span>
          <input type="text" class="app-form-control border-start-0" id="recSearchInput" placeholder="Tìm theo tên, mã NV, cấp trên duyệt..." oninput="handleRecordsSearch(this.value)">
        </div>
      </div>
      <div class="col-md-7 text-end small text-muted">
        Tổng số bản ghi: <strong id="recordCountLabel">0</strong> ca tăng ca
      </div>
    </div>
  </div>

  <!-- 4. Bảng Dữ Liệu -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead id="recordsThead"></thead>
        <tbody id="recordsTbody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let activeRecordType = 'plan';
let recordsSearchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
  loadRecordsData();
});

function switchRecordType(type) {
  activeRecordType = type;
  document.getElementById('tabPlanBtn').classList.toggle('active', type === 'plan');
  document.getElementById('tabActualBtn').classList.toggle('active', type === 'actual');
  loadRecordsData();
}

function handleRecordsSearch(val) {
  clearTimeout(recordsSearchTimeout);
  recordsSearchTimeout = setTimeout(() => {
    loadRecordsData();
  }, 300);
}

async function loadRecordsData() {
  const month = document.getElementById('recFilterMonth').value;
  const year = document.getElementById('recFilterYear').value;
  const search = document.getElementById('recSearchInput').value.trim();
  const thead = document.getElementById('recordsThead');
  const tbody = document.getElementById('recordsTbody');

  tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải dữ liệu tăng ca...</td></tr>';

  if (activeRecordType === 'plan') {
    thead.innerHTML = `
      <tr>
        <th style="width: 50px;">STT</th>
        <th>Mã NV</th>
        <th>Họ và Tên</th>
        <th>Tổ Đội / Nhóm</th>
        <th style="width: 110px;">Ngày Tăng Ca</th>
        <th>Bắt Đầu KH</th>
        <th>Kết Thúc KH</th>
        <th style="text-align: right; width: 90px;">Số Phút</th>
        <th style="text-align: right; width: 80px;">Số Giờ</th>
        <th>Cấp Trên Duyệt</th>
        <th>Lý Do Tăng Ca</th>
        <th style="text-align: center; width: 110px;">Trạng Thái</th>
      </tr>
    `;
  } else {
    thead.innerHTML = `
      <tr>
        <th style="width: 50px;">STT</th>
        <th>Mã NV</th>
        <th>Họ và Tên</th>
        <th>Tổ Đội / Nhóm</th>
        <th style="width: 110px;">Ngày Tăng Ca</th>
        <th>Bắt Đầu TT</th>
        <th>Kết Thúc TT</th>
        <th style="text-align: right; width: 90px;">Số Phút TT</th>
        <th style="text-align: right; width: 80px;">Số Giờ TT</th>
        <th style="text-align: right; width: 90px;">Lệch KH</th>
        <th>Cấp Trên Duyệt</th>
        <th>Lý Do</th>
        <th style="text-align: center; width: 110px;">Trạng Thái</th>
      </tr>
    `;
  }

  try {
    // Tải dữ liệu thông qua export API hoặc query
    const res = await fetch(`api/overtime_export.php?type=${activeRecordType}&year=${year}&month=${month}`);
    const text = await res.text();
    
    // Parse CSV
    const lines = text.trim().split('\n');
    if (lines.length <= 1) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4">Không tìm thấy dữ liệu phù hợp.</td></tr>';
      document.getElementById('recordCountLabel').textContent = '0';
      return;
    }

    let rowsData = [];
    for (let i = 1; i < lines.length; i++) {
      // Split CSV line handling quotes
      const row = parseCsvLine(lines[i]);
      if (row.length > 2) {
        if (!search || row.some(cell => cell.toLowerCase().includes(search.toLowerCase()))) {
          rowsData.push(row);
        }
      }
    }

    document.getElementById('recordCountLabel').textContent = rowsData.length;

    if (rowsData.length === 0) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4">Không có bản ghi nào khớp với tìm kiếm.</td></tr>';
      return;
    }

    let html = '';
    rowsData.slice(0, 100).forEach((r, idx) => {
      if (activeRecordType === 'plan') {
        html += `
          <tr>
            <td class="text-muted fw-bold">#${idx + 1}</td>
            <td><strong class="font-monospace text-primary">${escapeHtml(r[1] || '')}</strong></td>
            <td><strong>${escapeHtml(r[2] || '')}</strong></td>
            <td>${escapeHtml(r[4] || r[3] || '-')}</td>
            <td>${escapeHtml(r[5] || '')}</td>
            <td>${escapeHtml(r[6] || '')}</td>
            <td>${escapeHtml(r[7] || '')}</td>
            <td style="text-align: right;"><strong>${r[8] || 0}</strong></td>
            <td style="text-align: right;"><strong>${r[9] || 0}h</strong></td>
            <td><small>${escapeHtml(r[10] || '-')}</small></td>
            <td><small class="text-muted">${escapeHtml(r[12] || '-')}</small></td>
            <td style="text-align: center;"><span class="badge bg-success-subtle text-success border border-success-subtle">${escapeHtml(r[13] || 'Đã duyệt')}</span></td>
          </tr>
        `;
      } else {
        const diffMin = parseInt(r[10] || 0);
        let diffBadge = `<span class="badge bg-success">0p</span>`;
        if (diffMin > 0) diffBadge = `<span class="badge bg-warning text-dark">+${diffMin}p</span>`;
        else if (diffMin < 0) diffBadge = `<span class="badge bg-danger">${diffMin}p</span>`;

        html += `
          <tr>
            <td class="text-muted fw-bold">#${idx + 1}</td>
            <td><strong class="font-monospace text-primary">${escapeHtml(r[1] || '')}</strong></td>
            <td><strong>${escapeHtml(r[2] || '')}</strong></td>
            <td>${escapeHtml(r[4] || r[3] || '-')}</td>
            <td>${escapeHtml(r[5] || '')}</td>
            <td>${escapeHtml(r[6] || '')}</td>
            <td>${escapeHtml(r[7] || '')}</td>
            <td style="text-align: right;"><strong>${r[8] || 0}</strong></td>
            <td style="text-align: right;"><strong>${r[9] || 0}h</strong></td>
            <td style="text-align: right;">${diffBadge}</td>
            <td><small>${escapeHtml(r[12] || '-')}</small></td>
            <td><small class="text-muted">${escapeHtml(r[13] || '-')}</small></td>
            <td style="text-align: center;"><span class="badge bg-success-subtle text-success border border-success-subtle">${escapeHtml(r[14] || 'Đã duyệt')}</span></td>
          </tr>
        `;
      }
    });
    tbody.innerHTML = html;

  } catch (err) {
    console.error('Lỗi loadRecordsData:', err);
    tbody.innerHTML = '<tr><td colspan="12" class="text-danger text-center p-3">Lỗi nạp dữ liệu</td></tr>';
  }
}

function parseCsvLine(text) {
  let p = '', row = [''], i = 0, r = 0, q = false;
  for (let c of text) {
    if (c === '"') {
      if (q && p === '"') { row[r] += '"'; }
      q = !q;
    } else if (c === ',' && !q) {
      row[++r] = '';
    } else {
      row[r] += c;
    }
    p = c;
  }
  return row.map(cell => cell.trim().replace(/^"|"$/g, ''));
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

