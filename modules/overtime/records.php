<?php
/**
 * Module: Tra Cứu Dữ Liệu Tăng Ca Chi Tiết (Kế Hoạch & Thực Tế)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
$defaultDateFrom = date('Y-m-01');
$defaultDateTo = date('Y-m-d');

// Lấy danh sách Cost Center từ CSDL
$costCenters = [];
$resCc = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center ASC");
if ($resCc) {
    while ($r = $resCc->fetch_assoc()) {
        $costCenters[] = $r['cost_center'];
    }
}

// Lấy danh sách Tổ / Đội từ ot_plans
$teams = [];
$resTeams = $conn->query("SELECT DISTINCT team_name FROM ot_plans WHERE team_name IS NOT NULL AND team_name != '' ORDER BY team_name ASC");
if ($resTeams) {
    while ($r = $resTeams->fetch_assoc()) {
        $teams[] = $r['team_name'];
    }
}
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
      <!-- Nút Xuất OT Kế Hoạch -->
      <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm px-3" onclick="openExportPlanModal()" title="Xuất dữ liệu tăng ca kế hoạch theo mẫu chuẩn Excel overtime.xlsx">
        <span class="material-icons fs-6">file_download</span>
        <span class="fw-bold">Xuất OT Kế Hoạch</span>
      </button>

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
      <div class="col-md-7 d-flex align-items-center justify-content-end gap-3 small text-muted">
        <div>Tổng số bản ghi: <strong id="recordCountLabel" class="text-primary">0</strong> ca tăng ca</div>
        <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" onclick="openExportPlanModal()">
          <span class="material-icons fs-6">file_download</span> Xuất OT Kế Hoạch
        </button>
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

<!-- Modal Xuất OT Kế Hoạch Theo Mẫu Chuẩn overtime.xlsx -->
<div class="modal fade" id="modalExportPlan" tabindex="-1" aria-labelledby="modalExportPlanTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-success text-white py-3 px-4">
        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold" id="modalExportPlanTitle">
          <span class="material-icons">file_download</span>
          Xuất Dữ Liệu Tăng Ca Kế Hoạch (Mẫu Chuẩn overtime.xlsx)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Chú thích quy định mẫu -->
        <div class="alert alert-light border d-flex align-items-start gap-2 mb-3 py-2 px-3 small text-muted">
          <span class="material-icons text-success fs-5">verified</span>
          <div>
            File xuất giữ nguyên <strong>100% cấu trúc, màu sắc, dropdown danh sách</strong> từ mẫu gốc <code>Data/overtime.xlsx</code>.<br>
            Cột <em>"Lý do"</em> để trống ô dữ liệu & giữ dropdown; Cột <em>"Cần điện - khí"</em>: <code>SMC2 - B2 - F1</code>; Cột <em>"Nhà máy"</em>: <code>SMC2</code>.
          </div>
        </div>

        <form id="formExportPlan" onsubmit="event.preventDefault(); triggerPlanExport();">
          <div class="row g-3">
            <!-- Kiểu chọn ngày -->
            <div class="col-12">
              <label class="form-label fw-bold small">Hình thức chọn thời gian tăng ca:</label>
              <div class="d-flex align-items-center gap-4">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="exportDateMode" id="modeDateRange" value="range" checked onchange="toggleExportDateMode()">
                  <label class="form-check-label small fw-semibold" for="modeDateRange">Khoảng ngày (Từ ngày - Đến ngày)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="exportDateMode" id="modeDateSpecific" value="specific" onchange="toggleExportDateMode()">
                  <label class="form-check-label small fw-semibold" for="modeDateSpecific">Một ngày cụ thể</label>
                </div>
              </div>
            </div>

            <!-- Từ ngày & Đến ngày -->
            <div class="col-md-6" id="boxDateFrom">
              <label class="form-label fw-bold small text-muted">Từ ngày <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" id="expDateFrom" name="start_date" value="<?= $defaultDateFrom ?>" onchange="previewExportCount()">
            </div>
            <div class="col-md-6" id="boxDateTo">
              <label class="form-label fw-bold small text-muted">Đến ngày <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" id="expDateTo" name="end_date" value="<?= $defaultDateTo ?>" onchange="previewExportCount()">
            </div>

            <!-- Một ngày cụ thể -->
            <div class="col-md-12" id="boxDateSpecific" style="display: none;">
              <label class="form-label fw-bold small text-muted">Ngày tăng ca cụ thể <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" id="expDateSpecific" name="date_specific" value="<?= $defaultDateTo ?>" onchange="previewExportCount()">
            </div>

            <!-- Phím chọn nhanh thời gian -->
            <div class="col-12">
              <div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="small text-muted me-1">Chọn nhanh:</span>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setExportQuickDate('today')">Hôm nay</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setExportQuickDate('this_month')">Tháng <?= $currentMonth ?>/<?= $currentYear ?> (Mặc định)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setExportQuickDate('sep_2026')">Tháng 9/2026 (222 bản ghi)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="setExportQuickDate('all_2026')">Cả năm 2026</button>
              </div>
            </div>

            <hr class="my-2 text-muted">

            <!-- Bộ lọc: Bộ phận / CostCenter -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Bộ phận / CostCenter</label>
              <select class="form-select form-select-sm" id="expCostCenter" onchange="previewExportCount()">
                <option value="">-- Tất cả Bộ Phận / Cost Center --</option>
                <?php foreach ($costCenters as $cc): ?>
                  <option value="<?= htmlspecialchars($cc) ?>"><?= htmlspecialchars($cc) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Bộ lọc: Tổ / Đội / Nhóm -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-muted">Tổ / Đội / Nhóm</label>
              <select class="form-select form-select-sm" id="expTeamName" onchange="previewExportCount()">
                <option value="">-- Tất cả Tổ / Đội / Nhóm --</option>
                <?php foreach ($teams as $t): ?>
                  <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Bộ lọc: Nhà máy -->
            <div class="col-md-4">
              <label class="form-label fw-bold small text-muted">Nhà máy</label>
              <select class="form-select form-select-sm" id="expFactory" onchange="previewExportCount()">
                <option value="SMC2" selected>SMC2 (Mặc định)</option>
                <option value="SMC1">SMC1</option>
                <option value="SMC3">SMC3</option>
                <option value="SMC4">SMC4</option>
              </select>
            </div>

            <!-- Bộ lọc: Mã nhân viên -->
            <div class="col-md-4">
              <label class="form-label fw-bold small text-muted">Mã nhân viên</label>
              <input type="text" class="form-control form-control-sm" id="expEmployeeCode" placeholder="VD: 02021788" oninput="debouncePreviewCount()">
            </div>

            <!-- Bộ lọc: Họ tên nhân viên -->
            <div class="col-md-4">
              <label class="form-label fw-bold small text-muted">Tên nhân viên</label>
              <input type="text" class="form-control form-control-sm" id="expFullName" placeholder="VD: Nguyễn Văn..." oninput="debouncePreviewCount()">
            </div>
          </div>

          <!-- Khối thống kê & Preview trước khi tải -->
          <div class="card bg-light border-0 mt-3 p-3" id="expPreviewCard">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="material-icons text-primary fs-3" id="expPreviewIcon">query_stats</span>
                <div>
                  <div class="small text-muted">Số lượng bản ghi kế hoạch được duyệt dự kiến xuất:</div>
                  <div class="fw-bold fs-6 text-dark" id="expPreviewCountText">Đang kiểm tra dữ liệu...</div>
                </div>
              </div>
              <span class="badge bg-primary px-3 py-2 fs-6" id="expPreviewBadge">0 bản ghi</span>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm px-3" onclick="triggerPlanExport('csv')" title="Tải xuống tệp CSV nhanh chóng, mở trực tiếp bằng Excel">
            <span class="material-icons fs-6">description</span>
            <span>Xuất CSV</span>
          </button>
          <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm px-3" id="btnDoExportPlan" onclick="triggerPlanExport('xlsx')">
            <span class="material-icons fs-6">download</span>
            <span id="btnExportText">Tải File Excel (.xlsx)</span>
          </button>
        </div>
      </div>
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

// =========================================================================
// CÁC HÀM XỬ LÝ XUẤT DỮ LIỆU TĂNG CA KẾ HOẠCH THEO MẪU OVERTIME.XLSX
// =========================================================================
let exportPlanModalInstance = null;
let previewCountTimeout = null;
let currentPreviewCount = 0;

function getExportPlanModal() {
  if (!exportPlanModalInstance) {
    const modalEl = document.getElementById('modalExportPlan');
    exportPlanModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
  }
  return exportPlanModalInstance;
}

function openExportPlanModal() {
  // Tự động đồng bộ khoảng thời gian theo kỳ tháng & năm đang chọn trên giao diện tra cứu nếu hợp lệ
  const selMonth = document.getElementById('recFilterMonth') ? document.getElementById('recFilterMonth').value : '';
  const selYear = document.getElementById('recFilterYear') ? document.getElementById('recFilterYear').value : '';
  if (selYear) {
    const y = parseInt(selYear);
    if (selMonth) {
      const m = parseInt(selMonth);
      const mm = String(m).padStart(2, '0');
      const lastDay = new Date(y, m, 0).getDate();
      const fromStr = `${y}-${mm}-01`;
      const toStr = `${y}-${mm}-${String(lastDay).padStart(2, '0')}`;
      const fromEl = document.getElementById('expDateFrom');
      const toEl = document.getElementById('expDateTo');
      const specEl = document.getElementById('expDateSpecific');
      if (fromEl) fromEl.value = fromStr;
      if (toEl) toEl.value = toStr;
      if (specEl) specEl.value = fromStr;
    } else {
      const fromStr = `${y}-01-01`;
      const toStr = `${y}-12-31`;
      const fromEl = document.getElementById('expDateFrom');
      const toEl = document.getElementById('expDateTo');
      if (fromEl) fromEl.value = fromStr;
      if (toEl) toEl.value = toStr;
    }
  }

  getExportPlanModal().show();
  previewExportCount();
}

function toggleExportDateMode() {
  const isSpecific = document.getElementById('modeDateSpecific').checked;
  document.getElementById('boxDateFrom').style.display = isSpecific ? 'none' : 'block';
  document.getElementById('boxDateTo').style.display = isSpecific ? 'none' : 'block';
  document.getElementById('boxDateSpecific').style.display = isSpecific ? 'block' : 'none';
  previewExportCount();
}

function setExportQuickDate(type) {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const dd = String(now.getDate()).padStart(2, '0');
  const todayStr = `${yyyy}-${mm}-${dd}`;

  document.getElementById('modeDateRange').checked = true;
  toggleExportDateMode();

  if (type === 'today') {
    document.getElementById('expDateFrom').value = todayStr;
    document.getElementById('expDateTo').value = todayStr;
    document.getElementById('expDateSpecific').value = todayStr;
  } else if (type === 'this_month') {
    document.getElementById('expDateFrom').value = `${yyyy}-${mm}-01`;
    document.getElementById('expDateTo').value = todayStr;
  } else if (type === 'sep_2026') {
    document.getElementById('expDateFrom').value = '2026-09-01';
    document.getElementById('expDateTo').value = '2026-09-30';
  } else if (type === 'all_2026') {
    document.getElementById('expDateFrom').value = '2026-01-01';
    document.getElementById('expDateTo').value = '2026-12-31';
  }

  previewExportCount();
}

function debouncePreviewCount() {
  clearTimeout(previewCountTimeout);
  previewCountTimeout = setTimeout(() => {
    previewExportCount();
  }, 350);
}

function getExportParams(action = 'preview') {
  const isSpecific = document.getElementById('modeDateSpecific') && document.getElementById('modeDateSpecific').checked;
  const params = new URLSearchParams();
  params.set('action', action);

  if (isSpecific) {
    const specific = (document.getElementById('expDateSpecific') ? document.getElementById('expDateSpecific').value : '').trim();
    params.set('date_specific', specific);
    params.set('start_date', specific);
    params.set('end_date', specific);
    params.set('date_from', specific);
    params.set('date_to', specific);
  } else {
    const from = (document.getElementById('expDateFrom') ? document.getElementById('expDateFrom').value : '').trim();
    const to = (document.getElementById('expDateTo') ? document.getElementById('expDateTo').value : '').trim();
    params.set('start_date', from);
    params.set('end_date', to);
    params.set('date_from', from);
    params.set('date_to', to);
  }

  // Bổ sung month & year hiện tại để làm fallback an toàn
  const recMonth = document.getElementById('recFilterMonth') ? document.getElementById('recFilterMonth').value : '';
  const recYear = document.getElementById('recFilterYear') ? document.getElementById('recFilterYear').value : '';
  if (recMonth) params.set('month', recMonth);
  if (recYear) params.set('year', recYear);

  const costCenter = document.getElementById('expCostCenter') ? document.getElementById('expCostCenter').value : '';
  if (costCenter) params.set('cost_center', costCenter);

  const teamName = document.getElementById('expTeamName') ? document.getElementById('expTeamName').value : '';
  if (teamName) params.set('team_name', teamName);

  const factory = document.getElementById('expFactory') ? document.getElementById('expFactory').value : '';
  if (factory) params.set('factory', factory);

  const empCode = document.getElementById('expEmployeeCode') ? document.getElementById('expEmployeeCode').value.trim() : '';
  if (empCode) params.set('employee_code', empCode);

  const fullName = document.getElementById('expFullName') ? document.getElementById('expFullName').value.trim() : '';
  if (fullName) params.set('full_name', fullName);

  return params;
}

async function previewExportCount() {
  const badge = document.getElementById('expPreviewBadge');
  const countText = document.getElementById('expPreviewCountText');
  const icon = document.getElementById('expPreviewIcon');
  const btn = document.getElementById('btnDoExportPlan');

  badge.className = 'badge bg-secondary px-3 py-2 fs-6';
  badge.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang kiểm tra...';
  countText.textContent = 'Đang truy vấn số lượng bản ghi thỏa điều kiện...';

  try {
    const params = getExportParams('preview');
    const res = await fetch(`api/overtime_export_plan_template.php?${params.toString()}`);
    const json = await res.json();

    if (json.success) {
      currentPreviewCount = parseInt(json.count || 0);
      if (currentPreviewCount > 0) {
        badge.className = 'badge bg-success px-3 py-2 fs-6';
        badge.textContent = `${currentPreviewCount} bản ghi`;
        countText.innerHTML = `Sẵn sàng xuất <strong>${currentPreviewCount}</strong> bản ghi tăng ca kế hoạch đã được phê duyệt.`;
        icon.textContent = 'check_circle';
        icon.className = 'material-icons text-success fs-3';
      } else {
        badge.className = 'badge bg-warning text-dark px-3 py-2 fs-6';
        badge.textContent = '0 bản ghi';
        countText.textContent = 'Không có bản ghi tăng ca kế hoạch nào khớp với bộ lọc đã chọn.';
        icon.textContent = 'warning';
        icon.className = 'material-icons text-warning fs-3';
      }
    } else {
      badge.className = 'badge bg-danger px-3 py-2 fs-6';
      badge.textContent = 'Lỗi';
      countText.textContent = json.message || 'Không thể kiểm tra số lượng bản ghi.';
    }
  } catch (err) {
    console.error('Lỗi previewExportCount:', err);
    badge.className = 'badge bg-danger px-3 py-2 fs-6';
    badge.textContent = 'Lỗi kết nối';
    countText.textContent = 'Lỗi kết nối máy chủ khi đếm số lượng.';
  }
}

function triggerPlanExport(format = 'xlsx') {
  if (currentPreviewCount === 0) {
    alert('Không có dữ liệu tăng ca kế hoạch nào để xuất trong khoảng thời gian đã chọn! Vui lòng chọn lại khoảng ngày hoặc xóa bớt điều kiện lọc.');
    return;
  }

  const btn = document.getElementById('btnDoExportPlan');
  const oldText = document.getElementById('btnExportText').textContent;
  btn.disabled = true;
  document.getElementById('btnExportText').textContent = 'Đang tạo file...';

  const params = getExportParams('export');
  params.set('format', format);
  const exportUrl = `api/overtime_export_plan_template.php?${params.toString()}`;

  // Kích hoạt tải file an toàn
  const link = document.createElement('a');
  link.href = exportUrl;
  link.setAttribute('download', '');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  setTimeout(() => {
    btn.disabled = false;
    document.getElementById('btnExportText').textContent = oldText;
    getExportPlanModal().hide();
    const typeLabel = (format === 'csv') ? 'CSV' : 'Excel (.xlsx)';
    showExportToast(`Đã xuất thành công ${currentPreviewCount} bản ghi tăng ca kế hoạch (${typeLabel})!`);
  }, 1000);
}

function showExportToast(msg) {
  let toastContainer = document.getElementById('exportToastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'exportToastContainer';
    toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; max-width: 400px;';
    document.body.appendChild(toastContainer);
  }

  const toastEl = document.createElement('div');
  toastEl.className = 'alert alert-success alert-dismissible shadow-lg fade show border-0 d-flex align-items-center gap-2 py-3 px-4';
  toastEl.style.cssText = 'background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 500; border-radius: 8px;';
  toastEl.innerHTML = `
    <span class="material-icons fs-5 text-white">task_alt</span>
    <div>${escapeHtml(msg)}</div>
    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
  `;

  toastContainer.appendChild(toastEl);
  setTimeout(() => {
    toastEl.classList.remove('show');
    setTimeout(() => toastEl.remove(), 300);
  }, 4500);
}
</script>

