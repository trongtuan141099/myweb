<?php
/**
 * Module: Đối Soát Tăng Ca Kế Hoạch & Thực Tế
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
?>

<style>
.badge-rec-matched     { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-weight: 700; }
.badge-rec-plan_only   { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-weight: 700; }
.badge-rec-actual_only { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-weight: 700; }
.badge-rec-time_diff   { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 700; }
.badge-rec-overdue     { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 700; }

[data-theme="dark"] .badge-rec-matched     { background: rgba(34, 197, 94, 0.18); color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
[data-theme="dark"] .badge-rec-plan_only   { background: rgba(2, 132, 199, 0.18); color: #38bdf8; border-color: rgba(2, 132, 199, 0.35); }
[data-theme="dark"] .badge-rec-actual_only { background: rgba(126, 34, 206, 0.18); color: #c084fc; border-color: rgba(126, 34, 206, 0.35); }
[data-theme="dark"] .badge-rec-time_diff   { background: rgba(217, 119, 6, 0.18); color: #fbbf24; border-color: rgba(217, 119, 6, 0.35); }
[data-theme="dark"] .badge-rec-overdue     { background: rgba(220, 38, 38, 0.18); color: #f87171; border-color: rgba(220, 38, 38, 0.35); }

.rec-filter-tab {
  cursor: pointer;
  padding: 8px 14px;
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
.rec-filter-tab.active {
  background: var(--dx-primary);
  color: #fff;
  border-color: var(--dx-primary);
}
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">fact_check</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">ĐỐI SOÁT TĂNG CA KẾ HOẠCH & THỰC TẾ</h1>
        <p class="text-muted small mb-0">Tự động so khớp thời gian, phát hiện chênh lệch và kiểm tra quy định phê duyệt trong 03 ngày</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Bộ lọc Tháng / Năm -->
      <select class="app-form-select app-form-select-sm" id="recMonth" style="width: 120px;" onchange="loadReconciliations(1)">
        <option value="">-- Cả năm --</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($m === 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
      <select class="app-form-select app-form-select-sm" id="recYear" style="width: 100px;" onchange="loadReconciliations(1)">
        <option value="2026" selected>2026</option>
        <option value="2025">2025</option>
      </select>

      <button class="app-btn app-btn-primary btn-sm" type="button" onclick="triggerRunReconcile()">
        <span class="material-icons fs-6">play_arrow</span> Chạy Đối Soát Tự Động
      </button>
    </div>
  </div>

  <!-- 2. Thanh Tabs Phân Loại & Bộ Đếm Thống Kê -->
  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="recFilterTabsContainer">
    <div class="rec-filter-tab active" onclick="setRecStatusFilter('', this)">
      Tất cả <span class="badge bg-light text-dark rounded-pill" id="badgeAll">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('matched', this)">
      <span class="material-icons fs-6 text-success">check_circle</span> Khớp hợp lệ 
      <span class="badge bg-light text-dark rounded-pill" id="badgeMatched">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('needs_explanation', this)">
      <span class="material-icons fs-6 text-danger">report_problem</span> Cần giải trình 
      <span class="badge bg-danger rounded-pill text-white" id="badgeNeedsExp">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('time_diff', this)">
      <span class="material-icons fs-6 text-warning">timelapse</span> Lệch giờ/phút 
      <span class="badge bg-light text-dark rounded-pill" id="badgeTimeDiff">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('plan_only', this)">
      <span class="material-icons fs-6 text-info">calendar_today</span> Có KH thiếu TT 
      <span class="badge bg-light text-dark rounded-pill" id="badgePlanOnly">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('actual_only', this)">
      <span class="material-icons fs-6 text-secondary">flash_on</span> Có TT thiếu KH 
      <span class="badge bg-light text-dark rounded-pill" id="badgeActualOnly">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('overdue', this)">
      <span class="material-icons fs-6 text-danger">schedule</span> Quá hạn 3 ngày 
      <span class="badge bg-light text-dark rounded-pill" id="badgeOverdue">0</span>
    </div>
  </div>

  <!-- 3. Toolbar Tìm Kiếm -->
  <div class="app-filter-card mb-3">
    <div class="row g-2 w-100 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">search</span>
          </span>
          <input type="text" class="app-form-control border-start-0" id="recSearchInput" placeholder="Tìm theo tên hoặc mã nhân viên..." oninput="handleRecSearch(this.value)">
        </div>
      </div>
      <div class="col-md-7 text-end small text-muted">
        Hiển thị tối đa 25 ca đối soát trên mỗi trang
      </div>
    </div>
  </div>

  <!-- 4. Bảng Đối Soát 2 Cột Song Song (Kế Hoạch vs Thực Tế) -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 50px;">STT</th>
            <th>Nhân Viên</th>
            <th style="width: 100px;">Ngày OT</th>
            <!-- Cột Kế Hoạch -->
            <th style="background: rgba(30, 64, 175, 0.05); border-left: 2px solid var(--dx-primary);">Kế Hoạch (KH)</th>
            <th style="background: rgba(30, 64, 175, 0.05); width: 85px; text-align: right;">Phút KH</th>
            <!-- Cột Thực Tế -->
            <th style="background: rgba(22, 163, 74, 0.05); border-left: 2px solid var(--dx-success);">Thực Tế (TT)</th>
            <th style="background: rgba(22, 163, 74, 0.05); width: 85px; text-align: right;">Phút TT</th>
            <!-- Độ lệch & Trạng thái -->
            <th style="width: 100px; text-align: right;">Chênh Lệch</th>
            <th style="width: 160px; text-align: center;">Trạng Thái Đối Soát</th>
            <th style="width: 130px; text-align: center;">Hành Động</th>
          </tr>
        </thead>
        <tbody id="recTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>

    <!-- Phân Trang -->
    <div class="p-3 border-top d-flex align-items-center justify-content-between" id="recPaginationContainer"></div>
  </div>
</div>

<!-- Modal Chi Tiết Đối Soát & So Sánh 2 Cột -->
<div class="modal fade" id="recDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">compare_arrows</span>
          Đối Chiếu Chi Tiết Ca Tăng Ca
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3" id="recDetailBody">
        <!-- Render bằng JS -->
      </div>

      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentRecStatus = '';
let currentRecPage = 1;
let recSearchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
  loadReconciliations(1);
});

function setRecStatusFilter(status, el) {
  currentRecStatus = status;
  document.querySelectorAll('.rec-filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  loadReconciliations(1);
}

function handleRecSearch(val) {
  clearTimeout(recSearchTimeout);
  recSearchTimeout = setTimeout(() => {
    loadReconciliations(1);
  }, 300);
}

async function triggerRunReconcile() {
  const month = document.getElementById('recMonth').value;
  const year = document.getElementById('recYear').value;

  const btn = event.target.closest('button');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang đối soát...';

  try {
    const formData = new FormData();
    formData.append('action', 'run_reconcile');
    formData.append('month', month);
    formData.append('year', year);

    const res = await fetch('api/overtime_reconciliation.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      alert(`Đối soát hoàn tất!\n- Tổng ca: ${data.summary.total}\n- Khớp hợp lệ: ${data.summary.matched}\n- Cần giải trình: ${data.summary.needs_explanation}`);
      await loadReconciliations(1);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi triggerRunReconcile:', err);
    alert('Lỗi kết nối máy chủ');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">play_arrow</span> Chạy Đối Soát Tự Động';
  }
}

async function loadReconciliations(page = 1) {
  currentRecPage = page;
  const month = document.getElementById('recMonth').value;
  const year = document.getElementById('recYear').value;
  const search = document.getElementById('recSearchInput').value.trim();
  const tbody = document.getElementById('recTableBody');

  tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách đối soát...</td></tr>';

  try {
    const url = `api/overtime_reconciliation.php?action=get_reconciliations&page=${page}&limit=25&month=${month}&year=${year}&status=${encodeURIComponent(currentRecStatus)}&search=${encodeURIComponent(search)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="10" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    // Cập nhật bộ đếm Badges
    const b = data.badges || {};
    document.getElementById('badgeAll').textContent = b.total || 0;
    document.getElementById('badgeMatched').textContent = b.matched || 0;
    document.getElementById('badgeNeedsExp').textContent = b.needs_explanation || 0;
    document.getElementById('badgeTimeDiff').textContent = b.time_diff || 0;
    document.getElementById('badgePlanOnly').textContent = b.plan_only || 0;
    document.getElementById('badgeActualOnly').textContent = b.actual_only || 0;
    document.getElementById('badgeOverdue').textContent = b.overdue || 0;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted p-4">Không tìm thấy ca tăng ca nào khớp với bộ lọc.</td></tr>';
      document.getElementById('recPaginationContainer').innerHTML = '';
      return;
    }

    let html = '';
    data.data.forEach((r, idx) => {
      const stt = (page - 1) * 25 + idx + 1;
      const statusMeta = getReconcileStatusMeta(r.reconcile_status, r.is_overdue);

      let diffBadge = '-';
      if (r.diff_minutes > 0) {
        diffBadge = `<span class="badge bg-warning text-dark">+${r.diff_minutes}p</span>`;
      } else if (r.diff_minutes < 0) {
        diffBadge = `<span class="badge bg-danger">${r.diff_minutes}p</span>`;
      } else if (r.reconcile_status === 'matched') {
        diffBadge = `<span class="badge bg-success">0p</span>`;
      }

      html += `
        <tr>
          <td class="text-muted fw-bold">#${stt}</td>
          <td>
            <strong>${escapeHtml(r.full_name || 'N/A')}</strong>
            <span class="badge bg-light text-dark font-monospace border ms-1">${escapeHtml(r.employee_code)}</span>
            <div class="text-muted" style="font-size: 11px;">${escapeHtml(r.group_name || '-')}</div>
          </td>
          <td><strong>${r.ot_date}</strong></td>

          <!-- Kế hoạch -->
          <td style="border-left: 2px solid var(--dx-primary);">
            ${r.plan_start_time ? `<div class="small fw-bold">${r.plan_start_time.substr(11, 5)} - ${r.plan_end_time.substr(11, 5)}</div>` : '<span class="text-muted small">Không có KH</span>'}
          </td>
          <td style="text-align: right;"><strong>${r.plan_minutes}</strong></td>

          <!-- Thực tế -->
          <td style="border-left: 2px solid var(--dx-success);">
            ${r.actual_start_time ? `<div class="small fw-bold text-success">${r.actual_start_time.substr(11, 5)} - ${r.actual_end_time.substr(11, 5)}</div>` : '<span class="text-muted small">Không có TT</span>'}
          </td>
          <td style="text-align: right;"><strong>${r.actual_minutes}</strong></td>

          <!-- Chênh lệch -->
          <td style="text-align: right;">${diffBadge}</td>

          <!-- Trạng thái -->
          <td style="text-align: center;">
            <span class="badge ${statusMeta.badgeClass} px-2 py-1">${statusMeta.label}</span>
          </td>

          <!-- Thao tác -->
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1">
              <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openRecDetail(${r.id})" title="Xem chi tiết">
                So sánh
              </button>
              ${r.needs_explanation ? `
                <a href="index.php?mainpage=overtime&subpage=explanations" class="btn btn-sm btn-outline-danger py-0 px-1" title="Xem giải trình">
                  <span class="material-icons" style="font-size: 14px;">rate_review</span>
                </a>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;

    // Phân trang
    renderRecPagination(data.total, page, 25);

  } catch (err) {
    console.error('Lỗi loadReconciliations:', err);
    tbody.innerHTML = '<tr><td colspan="10" class="text-danger text-center p-3">Lỗi kết nối máy chủ</td></tr>';
  }
}

function getReconcileStatusMeta(status, isOverdue) {
  if (isOverdue) return { label: 'Quá hạn 3 ngày', badgeClass: 'badge-rec-overdue' };
  switch (status) {
    case 'matched': return { label: 'Hợp lệ (Khớp)', badgeClass: 'badge-rec-matched' };
    case 'plan_only': return { label: 'Có KH thiếu TT', badgeClass: 'badge-rec-plan_only' };
    case 'actual_only': return { label: 'Có TT thiếu KH', badgeClass: 'badge-rec-actual_only' };
    case 'time_diff': return { label: 'Lệch giờ/phút', badgeClass: 'badge-rec-time_diff' };
    case 'overdue': return { label: 'Quá hạn 3 ngày', badgeClass: 'badge-rec-overdue' };
    default: return { label: status, badgeClass: 'badge bg-secondary' };
  }
}

function renderRecPagination(total, curPage, limit) {
  const container = document.getElementById('recPaginationContainer');
  const totalPages = Math.ceil(total / limit);
  if (totalPages <= 1) {
    container.innerHTML = `<span class="small text-muted">Tổng cộng: <strong>${total}</strong> ca đối soát</span>`;
    return;
  }

  let html = `<span class="small text-muted">Trang <strong>${curPage}</strong> / ${totalPages} (${total} ca)</span><div class="btn-group btn-group-sm">`;
  if (curPage > 1) {
    html += `<button class="btn btn-outline-secondary" onclick="loadReconciliations(${curPage - 1})">Trước</button>`;
  }
  for (let p = Math.max(1, curPage - 2); p <= Math.min(totalPages, curPage + 2); p++) {
    html += `<button class="btn ${p === curPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadReconciliations(${p})">${p}</button>`;
  }
  if (curPage < totalPages) {
    html += `<button class="btn btn-outline-secondary" onclick="loadReconciliations(${curPage + 1})">Sau</button>`;
  }
  html += '</div>';
  container.innerHTML = html;
}

async function openRecDetail(recId) {
  try {
    const res = await fetch(`api/overtime_reconciliation.php?action=get_detail&id=${recId}`);
    const data = await res.json();
    if (!data.success) return;

    const d = data.detail;
    const body = document.getElementById('recDetailBody');
    body.innerHTML = `
      <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-3">
        <div>
          <h6 class="fw-bold m-0 text-primary">${d.full_name} (${d.employee_code})</h6>
          <div class="small text-muted mt-1">Tổ đội: <strong>${d.team_name || d.group_name || '-'}</strong> | Ngày tăng ca: <strong>${d.ot_date}</strong></div>
        </div>
        <span class="badge ${getReconcileStatusMeta(d.reconcile_status, d.is_overdue).badgeClass} px-3 py-2 fs-6">
          ${getReconcileStatusMeta(d.reconcile_status, d.is_overdue).label}
        </span>
      </div>

      <div class="row g-3">
        <!-- Cột Kế Hoạch -->
        <div class="col-md-6">
          <div class="card p-3 border-primary-subtle h-100">
            <h6 class="fw-bold text-primary border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">event_available</span> Dữ Liệu Kế Hoạch (Plan)
            </h6>
            <div class="small mt-2" style="line-height: 1.8;">
              Bắt đầu: <strong>${d.plan_start || '<span class="text-danger">Không có</span>'}</strong><br/>
              Kết thúc: <strong>${d.plan_end || '<span class="text-danger">Không có</span>'}</strong><br/>
              Tổng phút: <strong>${d.plan_min || 0} phút</strong><br/>
              Cấp trên duyệt: <strong>${d.plan_dm || '-'}</strong><br/>
              Lý do KH: <em>${d.plan_reason || '-'}</em><br/>
              Trạng thái: <span class="badge bg-light text-dark border">${d.plan_status || '-'}</span>
            </div>
          </div>
        </div>

        <!-- Cột Thực Tế -->
        <div class="col-md-6">
          <div class="card p-3 border-success-subtle h-100">
            <h6 class="fw-bold text-success border-bottom pb-2 d-flex align-items-center gap-1">
              <span class="material-icons fs-6">done_all</span> Dữ Liệu Thực Tế (Actual)
            </h6>
            <div class="small mt-2" style="line-height: 1.8;">
              Bắt đầu TT: <strong>${d.act_start || '<span class="text-danger">Không có</span>'}</strong><br/>
              Kết thúc TT: <strong>${d.act_end || '<span class="text-danger">Không có</span>'}</strong><br/>
              Tổng phút TT: <strong>${d.act_min || 0} phút</strong><br/>
              Cấp trên duyệt: <strong>${d.act_dm || '-'}</strong><br/>
              Lý do TT: <em>${d.act_reason || '-'}</em><br/>
              Trạng thái: <span class="badge bg-light text-dark border">${d.act_status || '-'}</span>
            </div>
          </div>
        </div>

        <!-- Phần Giải Trình nếu có -->
        ${d.needs_explanation ? `
          <div class="col-12">
            <div class="card p-3 border-warning bg-light">
              <h6 class="fw-bold text-warning d-flex align-items-center gap-1 m-0">
                <span class="material-icons fs-6">rate_review</span> Tình Trạng Giải Trình
              </h6>
              <div class="small mt-2">
                Loại vi phạm: <strong class="text-danger">${d.violation_type || '-'}</strong><br/>
                Trạng thái: <strong>${d.exp_status || 'Chưa thực hiện'}</strong><br/>
                ${d.explanation_content ? `Nội dung giải trình: <em>${d.explanation_content}</em><br/>` : ''}
                ${d.approver_username ? `Người thẩm định: <strong>${d.approver_username}</strong> - Ý kiến: <em>${d.approver_notes || '-'}</em>` : ''}
              </div>
            </div>
          </div>
        ` : ''}
      </div>
    `;

    new bootstrap.Modal(document.getElementById('recDetailModal')).show();
  } catch (err) {
    console.error('Lỗi openRecDetail:', err);
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

