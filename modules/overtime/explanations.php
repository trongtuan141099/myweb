<?php
/**
 * Module: Quản Lý & Phê Duyệt Giải Trình Tăng Ca
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
      <span class="material-icons text-primary" style="font-size: 28px;">rate_review</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">QUẢN LÝ & PHÊ DUYỆT GIẢI TRÌNH TĂNG CA</h1>
        <p class="text-muted small mb-0">Tiếp nhận lý do giải trình cho các ca tăng ca sai lệch (lệch giờ, thiếu kế hoạch, thiếu thực tế, quá hạn 3 ngày)</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2">
      <!-- Bộ lọc Tháng / Năm -->
      <select class="app-form-select app-form-select-sm" id="expMonth" style="width: 120px;" onchange="loadExplanations(1)">
        <option value="">-- Cả năm --</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($m === 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
      <select class="app-form-select app-form-select-sm" id="expYear" style="width: 100px;" onchange="loadExplanations(1)">
        <option value="2026" selected>2026</option>
        <option value="2025">2025</option>
      </select>

      <button class="app-btn app-btn-secondary btn-sm" onclick="loadExplanations(1)">
        <span class="material-icons fs-6">refresh</span> Làm mới
      </button>
    </div>
  </div>

  <!-- 2. Tabs Trạng Thái Giải Trình -->
  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <button class="btn btn-sm btn-outline-primary active fw-bold exp-tab-btn" onclick="setExpStatusFilter('', this)">
      Tất cả <span class="badge bg-secondary ms-1" id="badgeExpAll">0</span>
    </button>
    <button class="btn btn-sm btn-outline-warning fw-bold exp-tab-btn" onclick="setExpStatusFilter('pending', this)">
      Chưa giải trình <span class="badge bg-warning text-dark ms-1" id="badgeExpPending">0</span>
    </button>
    <button class="btn btn-sm btn-outline-info fw-bold exp-tab-btn" onclick="setExpStatusFilter('submitted', this)">
      Đã gửi (Chờ duyệt) <span class="badge bg-info text-white ms-1" id="badgeExpSubmitted">0</span>
    </button>
    <button class="btn btn-sm btn-outline-success fw-bold exp-tab-btn" onclick="setExpStatusFilter('approved', this)">
      Đã phê duyệt <span class="badge bg-success ms-1" id="badgeExpApproved">0</span>
    </button>
    <button class="btn btn-sm btn-outline-danger fw-bold exp-tab-btn" onclick="setExpStatusFilter('rejected', this)">
      Từ chối giải trình <span class="badge bg-danger ms-1" id="badgeExpRejected">0</span>
    </button>
  </div>

  <!-- 3. Toolbar Tìm Kiếm -->
  <div class="app-filter-card mb-3">
    <div class="row g-2 w-100 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">search</span>
          </span>
          <input type="text" class="app-form-control border-start-0" id="expSearchInput" placeholder="Tìm kiếm theo mã NV hoặc họ tên..." oninput="handleExpSearch(this.value)">
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Bảng Danh Sách Giải Trình -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 50px;">STT</th>
            <th>Nhân Viên</th>
            <th style="width: 100px;">Ngày OT</th>
            <th>Loại Vi Phạm / Sai Lệch</th>
            <th>Nội Dung Giải Trình</th>
            <th style="width: 130px; text-align: center;">Trạng Thái</th>
            <th>Người Thẩm Định</th>
            <th style="width: 140px; text-align: center;">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="expTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>

    <!-- Phân trang -->
    <div class="p-3 border-top d-flex align-items-center justify-content-between" id="expPaginationContainer"></div>
  </div>
</div>

<!-- Modal 1: Nhập Nội Dung Giải Trình (Dành cho nhân viên / tổ trưởng) -->
<div class="modal fade" id="submitExpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">edit_note</span>
          Gửi Giải Trình Tăng Ca
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form onsubmit="handleSubmitExpForm(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="submitExpId" value="0">
          <div class="p-2 mb-3 bg-light rounded border small">
            Nhân viên: <strong id="submitExpEmp">...</strong><br/>
            Ngày tăng ca: <strong id="submitExpDate">...</strong><br/>
            Lý do sai lệch: <strong class="text-danger" id="submitExpViolation">...</strong>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Lý do giải trình cụ thể <span class="text-danger">*</span></label>
            <textarea class="app-form-control" id="submitExpContent" rows="4" placeholder="Nhập rõ nguyên nhân phát sinh tăng ca, sự cố kỹ thuật, đơn hàng khẩn hoặc lý do lệch giờ..." required></textarea>
            <!-- Gợi ý nhanh -->
            <div class="d-flex align-items-center gap-1 mt-2 flex-wrap small">
              <span class="text-muted">Gợi ý:</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setExpSuggest('Máy đùn gặp sự cố kỹ thuật cần xử lý nối ca')">Sự cố máy</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setExpSuggest('Đơn hàng HDPE gấp cần hoàn thành trước ca sáng')">Đơn hàng gấp</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setExpSuggest('Hỗ trợ lắp rack xưởng 3 theo phân công của tổ trưởng')">Hỗ trợ lắp rack</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setExpSuggest('Quên quét thẻ vân tay lúc kết thúc ca, đã có tổ trưởng xác nhận')">Quên quét thẻ</span>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2 px-3">
          <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary btn-sm" id="btnSaveExp">
            <span class="material-icons fs-6">send</span> Gửi Giải Trình
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 2: Quản Lý Phê Duyệt / Từ Chối Giải Trình -->
<div class="modal fade" id="reviewExpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5 text-warning">gavel</span>
          Thẩm Định Giải Trình Tăng Ca
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">
        <input type="hidden" id="reviewExpId" value="0">
        <div class="p-3 mb-3 bg-light rounded border small">
          <div class="mb-1">Nhân viên: <strong id="reviewExpEmp" class="text-primary">...</strong></div>
          <div class="mb-1">Ngày tăng ca: <strong id="reviewExpDate">...</strong></div>
          <div class="mb-2">Loại sai lệch: <strong class="text-danger" id="reviewExpViolation">...</strong></div>
          <div class="p-2 bg-white rounded border">
            <strong class="text-muted d-block mb-1">Nội dung nhân viên giải trình:</strong>
            <span id="reviewExpContentText" class="fst-italic text-dark">...</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-muted">Ý kiến thẩm định / Lý do phê duyệt hoặc từ chối</label>
          <input type="text" class="app-form-control" id="reviewExpNotes" placeholder="VD: Đồng ý tính lương OT theo thực tế do đơn hàng gấp...">
        </div>
      </div>

      <div class="modal-footer py-2 px-3 d-flex justify-content-between">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-danger d-flex align-items-center gap-1" onclick="submitReviewDecision('rejected')">
            <span class="material-icons fs-6">cancel</span> Từ Chối
          </button>
          <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-1 text-white" onclick="submitReviewDecision('approved')">
            <span class="material-icons fs-6">check_circle</span> Phê Duyệt
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentExpStatus = '';
let currentExpPage = 1;
let expSearchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
  loadExplanations(1);
});

function setExpStatusFilter(status, el) {
  currentExpStatus = status;
  document.querySelectorAll('.exp-tab-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  loadExplanations(1);
}

function handleExpSearch(val) {
  clearTimeout(expSearchTimeout);
  expSearchTimeout = setTimeout(() => {
    loadExplanations(1);
  }, 300);
}

function setExpSuggest(text) {
  document.getElementById('submitExpContent').value = text;
}

async function loadExplanations(page = 1) {
  currentExpPage = page;
  const month = document.getElementById('expMonth').value;
  const year = document.getElementById('expYear').value;
  const search = document.getElementById('expSearchInput').value.trim();
  const tbody = document.getElementById('expTableBody');

  tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách giải trình...</td></tr>';

  try {
    const url = `api/overtime_explanation.php?action=get_explanations&page=${page}&limit=25&month=${month}&year=${year}&status=${encodeURIComponent(currentExpStatus)}&search=${encodeURIComponent(search)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    // Cập nhật badges
    const b = data.badges || {};
    document.getElementById('badgeExpAll').textContent = b.total || 0;
    document.getElementById('badgeExpPending').textContent = b.pending || 0;
    document.getElementById('badgeExpSubmitted').textContent = b.submitted || 0;
    document.getElementById('badgeExpApproved').textContent = b.approved || 0;
    document.getElementById('badgeExpRejected').textContent = b.rejected || 0;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted p-4">Không có bản ghi giải trình nào.</td></tr>';
      document.getElementById('expPaginationContainer').innerHTML = '';
      return;
    }

    let html = '';
    data.data.forEach((r, idx) => {
      const stt = (page - 1) * 25 + idx + 1;
      const statusMeta = getExpStatusMeta(r.approval_status);

      html += `
        <tr>
          <td class="text-muted fw-bold">#${stt}</td>
          <td>
            <strong>${escapeHtml(r.full_name || 'N/A')}</strong>
            <span class="badge bg-light text-dark font-monospace border ms-1">${escapeHtml(r.employee_code)}</span>
            <div class="text-muted" style="font-size: 11px;">${escapeHtml(r.team_name || r.group_name || '-')}</div>
          </td>
          <td><strong>${r.ot_date}</strong></td>
          <td><span class="text-danger fw-bold small">${escapeHtml(r.violation_type || '-')}</span></td>
          <td>
            ${r.explanation_content ? `<span class="small text-dark">${escapeHtml(r.explanation_content)}</span>` : '<span class="text-muted small fst-italic">Chưa có nội dung giải trình</span>'}
          </td>
          <td style="text-align: center;">
            <span class="badge ${statusMeta.badgeClass} px-2 py-1">${statusMeta.label}</span>
          </td>
          <td>
            ${r.approver_username ? `<strong>${r.approver_username}</strong><div class="small text-muted">${r.approver_notes || ''}</div>` : '<span class="text-muted small">-</span>'}
          </td>
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1">
              <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick='openSubmitExpModal(${JSON.stringify(r)})' title="Nhập giải trình">
                Giải trình
              </button>
              <button class="btn btn-sm btn-outline-dark py-0 px-2" onclick='openReviewExpModal(${JSON.stringify(r)})' title="Thẩm định duyệt/từ chối">
                Duyệt
              </button>
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;

    renderExpPagination(data.total, page, 25);
  } catch (err) {
    console.error('Lỗi loadExplanations:', err);
    tbody.innerHTML = '<tr><td colspan="8" class="text-danger text-center p-3">Lỗi kết nối máy chủ</td></tr>';
  }
}

function getExpStatusMeta(st) {
  switch (st) {
    case 'pending': return { label: 'Chưa giải trình', badgeClass: 'bg-warning-subtle text-warning border border-warning' };
    case 'submitted': return { label: 'Đã gửi (Chờ duyệt)', badgeClass: 'bg-info-subtle text-info border border-info' };
    case 'approved': return { label: 'Đã phê duyệt', badgeClass: 'bg-success-subtle text-success border border-success' };
    case 'rejected': return { label: 'Bị từ chối', badgeClass: 'bg-danger-subtle text-danger border border-danger' };
    default: return { label: st, badgeClass: 'bg-secondary' };
  }
}

function renderExpPagination(total, curPage, limit) {
  const container = document.getElementById('expPaginationContainer');
  const totalPages = Math.ceil(total / limit);
  if (totalPages <= 1) {
    container.innerHTML = `<span class="small text-muted">Tổng cộng: <strong>${total}</strong> phiếu giải trình</span>`;
    return;
  }

  let html = `<span class="small text-muted">Trang <strong>${curPage}</strong> / ${totalPages} (${total} phiếu)</span><div class="btn-group btn-group-sm">`;
  if (curPage > 1) {
    html += `<button class="btn btn-outline-secondary" onclick="loadExplanations(${curPage - 1})">Trước</button>`;
  }
  for (let p = Math.max(1, curPage - 2); p <= Math.min(totalPages, curPage + 2); p++) {
    html += `<button class="btn ${p === curPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadExplanations(${p})">${p}</button>`;
  }
  if (curPage < totalPages) {
    html += `<button class="btn btn-outline-secondary" onclick="loadExplanations(${curPage + 1})">Sau</button>`;
  }
  html += '</div>';
  container.innerHTML = html;
}

function openSubmitExpModal(item) {
  document.getElementById('submitExpId').value = item.id;
  document.getElementById('submitExpEmp').textContent = `${item.full_name} (${item.employee_code})`;
  document.getElementById('submitExpDate').textContent = item.ot_date;
  document.getElementById('submitExpViolation').textContent = item.violation_type || '-';
  document.getElementById('submitExpContent').value = item.explanation_content || '';
  new bootstrap.Modal(document.getElementById('submitExpModal')).show();
}

async function handleSubmitExpForm(e) {
  e.preventDefault();
  const id = document.getElementById('submitExpId').value;
  const content = document.getElementById('submitExpContent').value.trim();

  const formData = new FormData();
  formData.append('action', 'submit_explanation');
  formData.append('id', id);
  formData.append('explanation_content', content);

  try {
    const res = await fetch('api/overtime_explanation.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('submitExpModal')).hide();
      await loadExplanations(currentExpPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi handleSubmitExpForm:', err);
    alert('Lỗi kết nối');
  }
}

function openReviewExpModal(item) {
  document.getElementById('reviewExpId').value = item.id;
  document.getElementById('reviewExpEmp').textContent = `${item.full_name} (${item.employee_code})`;
  document.getElementById('reviewExpDate').textContent = item.ot_date;
  document.getElementById('reviewExpViolation').textContent = item.violation_type || '-';
  document.getElementById('reviewExpContentText').textContent = item.explanation_content || '(Chưa có nội dung giải trình)';
  document.getElementById('reviewExpNotes').value = item.approver_notes || '';
  new bootstrap.Modal(document.getElementById('reviewExpModal')).show();
}

async function submitReviewDecision(decision) {
  const id = document.getElementById('reviewExpId').value;
  const notes = document.getElementById('reviewExpNotes').value.trim();

  const formData = new FormData();
  formData.append('action', 'review_explanation');
  formData.append('id', id);
  formData.append('decision', decision);
  formData.append('approver_notes', notes);

  try {
    const res = await fetch('api/overtime_explanation.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('reviewExpModal')).hide();
      await loadExplanations(currentExpPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi submitReviewDecision:', err);
    alert('Lỗi kết nối');
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

