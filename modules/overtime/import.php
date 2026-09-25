<?php
/**
 * Module: Import & Tự Động Đồng Bộ Dữ Liệu Tăng Ca (UPSERT)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();
requirePermission('overtime.import');
?>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">cloud_sync</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">IMPORT & TỰ ĐỘNG ĐỒNG BỘ DỮ LIỆU TĂNG CA</h1>
        <p class="text-muted small mb-0">Tự động kết nối HRM (smcmfg.com.vn) hoặc nạp tập tin Excel thủ công với cơ chế UPSERT</p>
      </div>
    </div>
    <div class="app-page-actions d-flex gap-2">
      <button class="app-btn app-btn-primary btn-sm" type="button" id="btnHrmSyncNowTop" onclick="triggerHrmSyncNow()">
        <span class="material-icons fs-6">sync</span> Đồng Bộ Ngay Từ HRM
      </button>
      <button class="app-btn app-btn-secondary btn-sm" type="button" onclick="autoImportDataFolderFiles()">
        <span class="material-icons fs-6">folder_open</span> Nạp 2 File Từ Thư Mục Data
      </button>
    </div>
  </div>

  <!-- 2. Thẻ Tự Động Hóa Đồng Bộ Đa Tài Khoản HRM (Multi-Account HRM Auto Sync Card) -->
  <div class="app-card mb-4" style="border-left: 4px solid var(--dx-primary);">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-primary fs-5">satellite_alt</span>
        <strong class="fs-6">KẾT NỐI TỰ ĐỘNG ĐA TÀI KHOẢN HRM (https://hrm.smcmfg.com.vn)</strong>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small ms-1" id="hrmAccountCountBadge">2 tài khoản</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary" id="hrmLastStatusBadge">Đang kiểm tra...</span>
        <span class="text-muted small" id="hrmLastSyncTimeText"></span>
        <span class="badge bg-primary-subtle text-primary border font-monospace ms-1 d-inline-flex align-items-center gap-1" id="importHrmCountdownBadge" title="Thời gian tự động đồng bộ kế tiếp">
          <span class="material-icons" style="font-size:13px;">timer</span>
          <span id="importHrmCountdownText">--:--:--</span>
        </span>
        <button class="app-btn app-btn-secondary btn-sm ms-2" type="button" onclick="openAddAccountModal()">
          <span class="material-icons fs-6">person_add</span> Thêm Tài Khoản HRM
        </button>
        <button type="button" class="app-btn app-btn-primary btn-sm" id="btnHrmSyncNow" onclick="triggerHrmSyncNow()">
          <span class="material-icons fs-6">sync</span> Đồng Bộ Tất Cả Tài Khoản
        </button>
      </div>
    </div>

    <div class="p-3">
      <!-- Bảng Danh Sách Các Tài Khoản HRM -->
      <div class="table-responsive mb-3 border rounded">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
          <thead class="table-light">
            <tr>
              <th style="width: 45px; text-align: center;">STT</th>
              <th style="min-width: 180px;">Tên Tài Khoản</th>
              <th style="width: 140px;">Mã Đăng Nhập</th>
              <th style="width: 120px;">Mật Khẩu</th>
              <th style="width: 130px; text-align: center;">Trạng Thái</th>
              <th style="min-width: 160px;">Lần Cuối Đồng Bộ</th>
              <th style="min-width: 220px;">Kết Quả / Thống Kê Gần Nhất</th>
              <th style="width: 125px; text-align: center;">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="hrmAccountsTableBody">
            <tr>
              <td colspan="8" class="text-center py-3 text-muted">
                <div class="spinner-border spinner-border-sm me-1" role="status"></div> Đang tải danh sách tài khoản HRM...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cấu hình chung & Lịch trình tự động -->
      <form id="hrmSyncGlobalConfigForm" onsubmit="handleSaveGlobalHrmConfig(event)">
        <div class="row g-3 p-3 bg-light rounded border align-items-center">
          <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Chu kỳ tự động <span class="text-danger">*</span></label>
            <select class="app-form-select app-form-select-sm" id="hrmSyncInterval">
              <option value="1">Mỗi 1 giờ</option>
              <option value="2">Mỗi 2 giờ</option>
              <option value="3" selected>Mỗi 3 giờ (Mặc định)</option>
              <option value="6">Mỗi 6 giờ</option>
              <option value="12">Mỗi 12 giờ</option>
              <option value="24">Mỗi 24 giờ</option>
            </select>
          </div>

          <div class="col-md-3">
            <div class="form-check form-switch mt-3">
              <input class="form-check-input" type="checkbox" id="hrmAutoSyncEnabled" checked>
              <label class="form-check-label fw-bold small ms-1" for="hrmAutoSyncEnabled">
                Bật tự động đồng bộ ngầm
              </label>
            </div>
          </div>

          <div class="col-md-2">
            <label class="form-label fw-bold text-muted small">Từ ngày (Mặc định):</label>
            <input type="date" class="app-form-control app-form-control-sm" id="hrmSyncDateFrom">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-bold text-muted small">Đến ngày (Mặc định):</label>
            <input type="date" class="app-form-control app-form-control-sm" id="hrmSyncDateTo">
          </div>

          <div class="col-md-2 text-end mt-4">
            <button type="submit" class="app-btn app-btn-secondary btn-sm" id="btnSaveGlobalHrmConfig">
              <span class="material-icons fs-6">save</span> Lưu Chu Kỳ
            </button>
          </div>
        </div>
      </form>

      <!-- Alert thông báo kết quả đồng bộ HRM -->
      <div id="hrmSyncAlert" class="mt-3 p-3 rounded border" style="display: none;"></div>
    </div>
  </div>

  <!-- 3. Khu Vực Tải Lên Thủ Công & Hướng Dẫn -->
  <div class="row g-3 mb-4">
    <!-- Form Upload Thủ Công -->
    <div class="col-12 col-xl-7">
      <div class="app-card h-100">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
          <strong class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">cloud_upload</span> Tải Lên Thủ Công Tập Tin Excel Mới (.XLSX)
          </strong>
          <span class="badge bg-light text-muted border">Cơ chế UPSERT</span>
        </div>

        <div class="p-3">
          <form id="importForm" onsubmit="handleUploadSubmit(event)">
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Loại Dữ Liệu Tăng Ca <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="file_type" id="typePlan" value="plan" checked>
                  <label class="form-check-label fw-bold" for="typePlan">
                    Tăng ca kế hoạch (DanhSachDuyetTangCaKeHoach.xlsx)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="file_type" id="typeActual" value="actual">
                  <label class="form-check-label fw-bold" for="typeActual">
                    Tăng ca thực tế (DanhSachDuyetTangCaThucTe.xlsx)
                  </label>
                </div>
              </div>
            </div>

            <!-- Vùng chọn file -->
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Chọn tập tin từ máy tính <span class="text-danger">*</span></label>
              <input type="file" class="app-form-control" id="excelFile" name="excel_file" accept=".xlsx" required>
              <small class="text-muted d-block mt-1">Cơ chế **UPSERT** tự động: Nếu bản ghi chưa có $\rightarrow$ INSERT mới; nếu đã có $\rightarrow$ UPDATE ghi đè và lưu vết audit log.</small>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-2">
              <button type="submit" class="app-btn app-btn-primary" id="btnUploadSubmit">
                <span class="material-icons fs-6">upload</span> Bắt Đầu Import File
              </button>
              <div id="uploadSpinner" style="display: none;" class="d-flex align-items-center gap-2 text-primary small">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Đang phân tích và nạp dữ liệu...</span>
              </div>
            </div>
          </form>

          <!-- Thẻ kết quả import ngay sau khi nạp -->
          <div id="importResultAlert" class="mt-3 p-3 rounded border" style="display: none;"></div>
        </div>
      </div>
    </div>

    <!-- Hướng Dẫn Quy Tắc UPSERT & Validation -->
    <div class="col-12 col-xl-5">
      <div class="app-card h-100">
        <div class="p-3 border-bottom d-flex align-items-center gap-2">
          <span class="material-icons text-info">info</span>
          <strong>Quy Tắc Import & Khóa Nhận Diện</strong>
        </div>
        <div class="p-3 small text-muted" style="line-height: 1.6;">
          <h6 class="fw-bold text-dark small mb-1">1. Khóa nhận diện duy nhất (Unique Business Key):</h6>
          <p class="mb-2">
            • <strong>Kế hoạch</strong>: <code>Mã NV + Ngày tăng ca + Thời gian bắt đầu</code><br/>
            • <strong>Thực tế</strong>: <code>Mã NV + Ngày tăng ca + Thời gian bắt đầu TT</code>
          </p>

          <h6 class="fw-bold text-dark small mb-1">2. Cơ chế xử lý trùng (UPSERT):</h6>
          <p class="mb-2">
            Không tạo bản ghi trùng lặp. Khi import lại file có cùng ca tăng ca, hệ thống sẽ ghi đè số phút, lý do, người duyệt mới nhất và tự động lưu vết dữ liệu cũ vào <strong>Audit Trail</strong>.
          </p>

          <h6 class="fw-bold text-dark small mb-1">3. Kiểm tra tính toàn vẹn (Validation):</h6>
          <p class="mb-0">
            • Dòng thiếu Mã NV hoặc sai định dạng ngày giờ sẽ bị loại trừ và ghi nhận vào bảng lỗi.<br/>
            • Sau khi import thành công, hệ thống tự động cập nhật bảng <strong>Lũy kế 200 giờ/năm</strong>.
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Bảng Lịch Sử Các Đợt Import (Batch History) -->
  <div class="app-card">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-primary">history</span>
        <strong>LỊCH SỬ CÁC ĐỢT IMPORT DỮ LIỆU</strong>
      </div>
      <button class="app-btn app-btn-secondary btn-sm" onclick="loadBatchesList()">
        <span class="material-icons fs-6">refresh</span> Làm mới
      </button>
    </div>

    <div class="app-table-responsive" style="max-height: 450px; overflow-y: auto;">
      <table class="app-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Mã Đợt (Batch Code)</th>
            <th>Loại Dữ Liệu</th>
            <th>Tên Tập Tin</th>
            <th style="text-align: right;">Tổng Dòng</th>
            <th style="text-align: right; color: var(--dx-success);">Thêm Mới</th>
            <th style="text-align: right; color: var(--dx-primary);">Ghi Đè (Update)</th>
            <th style="text-align: right; color: var(--dx-danger);">Lỗi</th>
            <th style="text-align: center;">Trạng Thái</th>
            <th>Người Thực Hiện</th>
            <th>Thời Gian</th>
            <th style="text-align: center; width: 140px;">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="batchesTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Xem Chi Tiết Dòng Lỗi -->
<div class="modal fade" id="batchErrorsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">error_outline</span>
          Chi Tiết Dòng Dữ Liệu Bị Lỗi (Batch: <span id="errorModalBatchCode">...</span>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="table-responsive" style="max-height: 350px;">
          <table class="table table-bordered table-sm align-middle small">
            <thead class="table-light">
              <tr>
                <th style="width: 80px;" class="text-center">Dòng Excel</th>
                <th>Nguyên Nhân Lỗi</th>
                <th>Dữ Liệu Thô (Raw Data)</th>
              </tr>
            </thead>
            <tbody id="batchErrorsTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Xem Audit Log Lịch Sử Thay Đổi -->
<div class="modal fade" id="batchLogsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">receipt_long</span>
          Audit Log Lịch Sử Ghi Đè (Batch: <span id="logModalBatchCode">...</span>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="table-responsive" style="max-height: 400px;">
          <table class="table table-bordered table-sm align-middle small">
            <thead class="table-light">
              <tr>
                <th style="width: 100px;">Mã NV</th>
                <th style="width: 110px;">Ngày OT</th>
                <th style="width: 80px;" class="text-center">Thao Tác</th>
                <th>Dữ Liệu Trước Khi Ghi Đè</th>
                <th>Dữ Liệu Sau Khi Cập Nhật</th>
                <th style="width: 140px;">Thời Gian</th>
              </tr>
            </thead>
            <tbody id="batchLogsTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Thêm / Chỉnh Sửa Tài Khoản HRM -->
<div class="modal fade" id="hrmAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">manage_accounts</span>
          <span id="hrmAccountModalTitle">Thêm Mới Tài Khoản HRM</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="hrmAccountForm" onsubmit="handleSaveAccountForm(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="modalAccountId" value="0">
          
          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Tên Định Danh Tài Khoản <span class="text-danger">*</span></label>
            <input type="text" class="app-form-control" id="modalAccountName" placeholder="VD: Tài khoản HRM 1 (Chính), Tài khoản HRM 2..." required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Mã Đăng Nhập HRM (Username) <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><span class="material-icons fs-6">person</span></span>
              <input type="text" class="app-form-control" id="modalAccountUsername" placeholder="VD: 02114273" required autocomplete="username">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Mật Khẩu HRM <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><span class="material-icons fs-6">lock</span></span>
              <input type="password" class="app-form-control" id="modalAccountPassword" placeholder="Nhập mật khẩu" autocomplete="current-password">
              <button class="btn btn-outline-secondary" type="button" onclick="toggleModalPasswordVisibility()" title="Ẩn/Hiện">
                <span class="material-icons fs-6" id="modalEyeIcon">visibility</span>
              </button>
            </div>
            <small class="text-muted" id="modalPasswordHelpText">Để trống nếu không muốn thay đổi mật khẩu cũ khi chỉnh sửa.</small>
          </div>

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="modalAccountIsActive" checked>
            <label class="form-check-label fw-bold small" for="modalAccountIsActive">
              Kích hoạt tài khoản này khi tự động đồng bộ
            </label>
          </div>
        </div>
        <div class="modal-footer py-2 px-3">
          <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary btn-sm" id="btnSubmitAccountModal">
            <span class="material-icons fs-6">save</span> Lưu Tài Khoản
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  loadHrmSyncConfig();
  loadBatchesList();
});

// =========================================================================
// HRM AUTOMATED SYNC HANDLERS
// =========================================================================
let currentHrmAccounts = [];

function formatVnDateTimeClient(dtStr) {
  if (!dtStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/.test(dtStr)) return dtStr;
  const d = new Date(dtStr.replace(' ', 'T'));
  if (isNaN(d.getTime())) return dtStr;
  const pad = n => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

async function loadHrmSyncConfig() {
  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=get_config');
    const data = await res.json();
    if (!data.success) return;

    currentHrmAccounts = data.accounts || [];
    const gc = data.global_config || {};

    // 1. Render badge số lượng tài khoản & trạng thái chung
    const countBadge = document.getElementById('hrmAccountCountBadge');
    if (countBadge) countBadge.textContent = `${currentHrmAccounts.length} tài khoản`;
    
    const badge = document.getElementById('hrmLastStatusBadge');
    const timeText = document.getElementById('hrmLastSyncTimeText');

    if (gc.last_sync_status === 'success') {
      badge.className = 'badge bg-success';
      badge.innerHTML = '<span class="material-icons fs-6 align-middle">check_circle</span> Đã đồng bộ';
    } else if (gc.last_sync_status === 'failed') {
      badge.className = 'badge bg-danger';
      badge.innerHTML = '<span class="material-icons fs-6 align-middle">error</span> Lỗi đồng bộ';
    } else {
      badge.className = 'badge bg-secondary';
      badge.textContent = 'Chưa đồng bộ';
    }

    if (gc.last_sync_time) {
      timeText.textContent = `Lần cuối: ${gc.last_sync_time_formatted || formatVnDateTimeClient(gc.last_sync_time)}`;
    } else {
      timeText.textContent = 'Lần cuối: Chưa có';
    }

    // 2. Điền cấu hình chu kỳ & ngày
    if (document.getElementById('hrmSyncInterval')) document.getElementById('hrmSyncInterval').value = gc.sync_interval_hours || 3;
    if (document.getElementById('hrmAutoSyncEnabled')) document.getElementById('hrmAutoSyncEnabled').checked = !!gc.auto_sync_enabled;
    if (document.getElementById('hrmSyncDateFrom') && gc.sync_date_from) document.getElementById('hrmSyncDateFrom').value = gc.sync_date_from;
    if (document.getElementById('hrmSyncDateTo') && gc.sync_date_to) document.getElementById('hrmSyncDateTo').value = gc.sync_date_to;

    fetch('api/overtime_hrm_sync.php?action=check_schedule')
      .then(r => r.json())
      .then(d => {
        if (d.seconds_remaining !== undefined && d.auto_sync_enabled) {
          startImportCountdown(d.seconds_remaining);
        } else if (!d.auto_sync_enabled) {
          const cdText = document.getElementById('importHrmCountdownText');
          if (cdText) cdText.textContent = 'Đã tắt';
        }
      })
      .catch(console.error);

    // 3. Render bảng danh sách tài khoản
    const tbody = document.getElementById('hrmAccountsTableBody');
    if (!tbody) return;

    if (currentHrmAccounts.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Chưa có tài khoản HRM nào. Vui lòng bấm "Thêm Tài Khoản HRM".</td></tr>';
      return;
    }

    let rowsHtml = '';
    currentHrmAccounts.forEach((acc, idx) => {
      const isAct = acc.is_active;
      let statusBadge = '<span class="badge bg-secondary">Chưa chạy</span>';
      if (acc.last_sync_status === 'success') {
        statusBadge = '<span class="badge bg-success"><span class="material-icons align-middle" style="font-size:12px;">check</span> Thành công</span>';
      } else if (acc.last_sync_status === 'failed') {
        statusBadge = '<span class="badge bg-danger"><span class="material-icons align-middle" style="font-size:12px;">warning</span> Thất bại</span>';
      }

      let statsText = '<span class="text-muted fst-italic">Chưa có số liệu</span>';
      if (acc.last_sync_stats) {
        const s = acc.last_sync_stats;
        statsText = `<span class="badge bg-light text-dark border">KH: +${s.plan_inserted||0}/sửa ${s.plan_updated||0}</span> <span class="badge bg-light text-dark border">TT: +${s.actual_inserted||0}/sửa ${s.actual_updated||0}</span>`;
      }

      rowsHtml += `
        <tr>
          <td style="text-align: center;" class="text-muted fw-bold">${idx + 1}</td>
          <td>
            <strong>${escapeHtml(acc.account_name)}</strong>
          </td>
          <td>
            <span class="badge bg-light text-primary font-monospace border fs-7">${escapeHtml(acc.username)}</span>
          </td>
          <td>
            <span class="text-muted font-monospace">••••••••</span>
          </td>
          <td style="text-align: center;">
            <div class="form-check form-switch d-inline-block">
              <input class="form-check-input cursor-pointer" type="checkbox" ${isAct ? 'checked' : ''} onchange="handleToggleAccount(${acc.id}, this.checked)" title="${isAct ? 'Đang hoạt động' : 'Đang tạm ngưng'}">
            </div>
            <div class="small ${isAct ? 'text-success fw-bold' : 'text-muted'}">${isAct ? 'Hoạt động' : 'Tạm ngưng'}</div>
          </td>
          <td>
            <div class="font-monospace small fw-bold text-dark">${acc.last_sync_time ? (acc.last_sync_time_formatted || formatVnDateTimeClient(acc.last_sync_time)) : '<span class="text-muted">-</span>'}</div>
            <div class="mt-1">${statusBadge}</div>
          </td>
          <td>
            ${statsText}
            ${acc.last_sync_message ? `<div class="text-muted mt-1 text-truncate" style="max-width: 250px; font-size: 11px;" title="${escapeHtml(acc.last_sync_message)}">${escapeHtml(acc.last_sync_message)}</div>` : ''}
          </td>
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1">
              <button class="btn btn-sm btn-outline-primary p-1 rounded-circle" onclick="triggerHrmSyncNow(${acc.id})" title="Đồng bộ riêng tài khoản này" data-bs-toggle="tooltip">
                <span class="material-icons" style="font-size: 15px;">play_arrow</span>
              </button>
              <button class="btn btn-sm btn-outline-warning p-1 rounded-circle" onclick='openEditAccountModal(${JSON.stringify(acc)})' title="Chỉnh sửa tài khoản" data-bs-toggle="tooltip">
                <span class="material-icons" style="font-size: 15px;">edit</span>
              </button>
              <button class="btn btn-sm btn-outline-danger p-1 rounded-circle" onclick="handleDeleteAccount(${acc.id}, '${escapeHtml(acc.account_name)}')" title="Xóa tài khoản" data-bs-toggle="tooltip">
                <span class="material-icons" style="font-size: 15px;">delete_outline</span>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = rowsHtml;

    const tooltips = [].slice.call(document.querySelectorAll('#hrmAccountsTableBody [data-bs-toggle="tooltip"]'));
    tooltips.map(el => new bootstrap.Tooltip(el));

  } catch (err) {
    console.error('Lỗi loadHrmSyncConfig:', err);
  }
}

function openAddAccountModal() {
  document.getElementById('hrmAccountModalTitle').textContent = 'Thêm Mới Tài Khoản HRM';
  document.getElementById('modalAccountId').value = '0';
  document.getElementById('modalAccountName').value = `Tài khoản HRM ${currentHrmAccounts.length + 1}`;
  document.getElementById('modalAccountUsername').value = '';
  document.getElementById('modalAccountPassword').value = '';
  document.getElementById('modalAccountPassword').required = true;
  document.getElementById('modalPasswordHelpText').textContent = 'Bắt buộc nhập mật khẩu cho tài khoản mới.';
  document.getElementById('modalAccountIsActive').checked = true;

  const modalEl = document.getElementById('hrmAccountModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function openEditAccountModal(acc) {
  document.getElementById('hrmAccountModalTitle').textContent = `Chỉnh Sửa: ${acc.account_name}`;
  document.getElementById('modalAccountId').value = acc.id;
  document.getElementById('modalAccountName').value = acc.account_name || '';
  document.getElementById('modalAccountUsername').value = acc.username || '';
  document.getElementById('modalAccountPassword').value = '';
  document.getElementById('modalAccountPassword').required = false;
  document.getElementById('modalPasswordHelpText').textContent = 'Để trống nếu muốn giữ nguyên mật khẩu cũ.';
  document.getElementById('modalAccountIsActive').checked = !!acc.is_active;

  const modalEl = document.getElementById('hrmAccountModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function toggleModalPasswordVisibility() {
  const pwd = document.getElementById('modalAccountPassword');
  const icon = document.getElementById('modalEyeIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    pwd.type = 'password';
    icon.textContent = 'visibility';
  }
}

async function handleSaveAccountForm(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitAccountModal');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  const payload = {
    id: parseInt(document.getElementById('modalAccountId').value) || 0,
    account_name: document.getElementById('modalAccountName').value.trim(),
    username: document.getElementById('modalAccountUsername').value.trim(),
    password: document.getElementById('modalAccountPassword').value.trim(),
    is_active: document.getElementById('modalAccountIsActive').checked
  };

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=save_account', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      const modalEl = document.getElementById('hrmAccountModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      await loadHrmSyncConfig();
    } else {
      alert('Lỗi: ' + (data.message || 'Không thể lưu tài khoản'));
    }
  } catch (err) {
    console.error('Lỗi handleSaveAccountForm:', err);
    alert('Có lỗi xảy ra khi lưu tài khoản.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Tài Khoản';
  }
}

async function handleDeleteAccount(id, name) {
  if (!confirm(`Bạn có chắc chắn muốn xóa tài khoản "${name}" khỏi hệ thống không?`)) {
    return;
  }

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=delete_account', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.success) {
      await loadHrmSyncConfig();
    } else {
      alert('Lỗi: ' + (data.message || 'Không thể xóa'));
    }
  } catch (err) {
    console.error('Lỗi handleDeleteAccount:', err);
  }
}

async function handleToggleAccount(id, isChecked) {
  try {
    await fetch('api/overtime_hrm_sync.php?action=toggle_account_status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, is_active: isChecked })
    });
    await loadHrmSyncConfig();
  } catch (err) {
    console.error('Lỗi handleToggleAccount:', err);
  }
}

async function handleSaveGlobalHrmConfig(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveGlobalHrmConfig');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  const payload = {
    sync_interval_hours: parseInt(document.getElementById('hrmSyncInterval').value),
    auto_sync_enabled: document.getElementById('hrmAutoSyncEnabled').checked,
    sync_date_from: document.getElementById('hrmSyncDateFrom').value.trim(),
    sync_date_to: document.getElementById('hrmSyncDateTo').value.trim()
  };

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=save_global_config', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    const alertBox = document.getElementById('hrmSyncAlert');
    alertBox.style.display = 'block';

    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      alertBox.innerHTML = `<span class="material-icons align-middle fs-5 me-1">check_circle</span> ${data.message}`;
      await loadHrmSyncConfig();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `<strong>Lỗi:</strong> ${data.message}`;
    }
  } catch (err) {
    console.error('Lỗi handleSaveGlobalHrmConfig:', err);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Chu Kỳ';
  }
}

async function triggerHrmSyncNow(accountId = null) {
  const btn = document.getElementById('btnHrmSyncNow');
  const alertBox = document.getElementById('hrmSyncAlert');

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang đồng bộ...';
  }

  alertBox.style.display = 'block';
  alertBox.className = 'mt-3 p-3 rounded border bg-info-subtle border-info text-info';
  const targetText = accountId ? `tài khoản được chọn` : 'tất cả các tài khoản HRM đang hoạt động';
  alertBox.innerHTML = `<div class="d-flex align-items-center gap-2"><div class="spinner-border spinner-border-sm text-info"></div><span>Đang kết nối đến hệ thống HRM, đồng bộ và gộp dữ liệu ${targetText}... Vui lòng đợi trong giây lát.</span></div>`;

  try {
    const formData = new FormData();
    if (accountId) formData.append('account_id', accountId);
    const df = document.getElementById('hrmSyncDateFrom');
    const dt = document.getElementById('hrmSyncDateTo');
    if (df && df.value) formData.append('TuNgay', df.value);
    if (dt && dt.value) formData.append('DenNgay', dt.value);

    const res = await fetch('api/overtime_hrm_sync.php?action=trigger_sync', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    // In log chi tiết ra console
    console.group('%c[HRM SYNC] KẾT QUẢ ĐỒNG BỘ ĐA TÀI KHOẢN HRM', 'color: #0d6efd; font-weight: bold; font-size: 14px; padding: 4px;');
    console.log(data);
    console.groupEnd();

    // Render danh sách tiến trình các bước
    let stepsHtml = '';
    if (data.steps && data.steps.length > 0) {
      stepsHtml = `
        <div class="mt-3 pt-2 border-top">
          <div class="fw-bold small mb-2 d-flex align-items-center justify-content-between">
            <span><span class="material-icons fs-6 align-middle">timeline</span> Tiến trình thực hiện (${data.steps.length} bước):</span>
          </div>
          <div class="list-group list-group-flush rounded border" style="max-height: 280px; overflow-y: auto; font-size: 12px;">
      `;
      data.steps.forEach(s => {
        const icon = s.status === 'success' ? 'check_circle' : (s.status === 'warning' ? 'warning' : 'cancel');
        const color = s.status === 'success' ? 'text-success' : (s.status === 'warning' ? 'text-warning' : 'text-danger');
        stepsHtml += `
          <div class="list-group-item d-flex align-items-start gap-2 py-2">
            <span class="material-icons fs-6 ${color} mt-1">${icon}</span>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center justify-content-between">
                <strong>${s.account ? `[${escapeHtml(s.account)}] ` : ''}${escapeHtml(s.title)}</strong>
                <span class="text-muted font-monospace" style="font-size: 11px;">${s.time || ''}</span>
              </div>
              <div class="text-muted small">${escapeHtml(s.detail)}</div>
            </div>
          </div>
        `;
      });
      stepsHtml += '</div></div>';
    }

    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      const tot = data.totals || {};
      const rec = data.reconciliation || {};
      alertBox.innerHTML = `
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <strong class="d-flex align-items-center gap-1 fs-6">
              <span class="material-icons fs-5">check_circle</span> Đồng Bộ Thành Công!
            </strong>
            <div class="small mt-1">${escapeHtml(data.message)}</div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
              <span class="badge bg-success">KH Mới: +${tot.plan_inserted||0}</span>
              <span class="badge bg-primary">KH Sửa: ${tot.plan_updated||0}</span>
              <span class="badge bg-success">TT Mới: +${tot.actual_inserted||0}</span>
              <span class="badge bg-primary">TT Sửa: ${tot.actual_updated||0}</span>
              <span class="badge bg-dark">Đối Soát: ${rec.total||0} ca</span>
            </div>
          </div>
          <div class="text-end small">
            <div>Thời gian: <strong>${data.last_sync_time_formatted || formatVnDateTimeClient(data.last_sync_time)}</strong></div>
            <div class="text-muted">Múi giờ: GMT+7 (Asia/Ho_Chi_Minh)</div>
          </div>
        </div>
        ${stepsHtml}
      `;
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `
        <div>
          <strong class="d-flex align-items-center gap-1 fs-6">
            <span class="material-icons fs-5">error</span> Đồng Bộ Thất Bại
          </strong>
          <div class="small mt-1">${escapeHtml(data.message || 'Không thể đồng bộ từ máy chủ HRM.')}</div>
        </div>
        ${stepsHtml}
      `;
    }

    // Cập nhật lại danh sách tài khoản & batches ngay lập tức
    await loadHrmSyncConfig();
    if (typeof loadBatchesList === 'function') {
      await loadBatchesList();
    }

  } catch (err) {
    console.error('Lỗi triggerHrmSyncNow:', err);
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi kết nối:</strong> ${err.message}`;
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-icons fs-6">sync</span> Đồng Bộ Tất Cả Tài Khoản';
    }
  }
}

// =========================================================================
// BATCH LIST & MANUAL UPLOAD HANDLERS
// =========================================================================
async function loadBatchesList() {
  const tbody = document.getElementById('batchesTableBody');
  tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải lịch sử import...</td></tr>';

  try {
    const res = await fetch('api/overtime_import.php?action=get_batches');
    const data = await res.json();

    if (!data.success || !data.batches || data.batches.length === 0) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4">Chưa có lịch sử đợt import nào.</td></tr>';
      return;
    }

    let html = '';
    data.batches.forEach(b => {
      const typeBadge = b.file_type === 'plan' 
        ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Kế hoạch</span>' 
        : '<span class="badge bg-success-subtle text-success border border-success-subtle">Thực tế</span>';
      const statusBadge = b.status === 'completed'
        ? '<span class="badge bg-success">Hoàn thành</span>'
        : (b.status === 'processing' ? '<span class="badge bg-warning text-dark">Đang xử lý</span>' : '<span class="badge bg-danger">Thất bại</span>');

      html += `
        <tr>
          <td class="text-muted fw-bold">#${b.id}</td>
          <td><strong class="font-monospace text-primary">${b.batch_code}</strong></td>
          <td>${typeBadge}</td>
          <td><small class="text-muted">${escapeHtml(b.file_name)}</small></td>
          <td style="text-align: right;"><strong>${b.total_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-success);"><strong>+${b.inserted_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-primary);"><strong>${b.updated_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-danger);"><strong>${b.error_rows}</strong></td>
          <td style="text-align: center;">${statusBadge}</td>
          <td><small>${escapeHtml(b.created_by)}</small></td>
          <td><small class="text-muted">${b.created_at}</small></td>
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1">
              ${b.error_rows > 0 ? `
                <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="openBatchErrors(${b.id}, '${b.batch_code}')" title="Xem lỗi">
                  <span class="material-icons" style="font-size: 14px;">error_outline</span>
                </button>
              ` : ''}
              <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="openBatchLogs(${b.id}, '${b.batch_code}')" title="Xem audit trail">
                <span class="material-icons" style="font-size: 14px;">receipt_long</span>
              </button>
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  } catch (err) {
    console.error('Lỗi loadBatchesList:', err);
    tbody.innerHTML = '<tr><td colspan="12" class="text-danger text-center">Lỗi kết nối máy chủ</td></tr>';
  }
}

async function handleUploadSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('btnUploadSubmit');
  const spinner = document.getElementById('uploadSpinner');
  const alertBox = document.getElementById('importResultAlert');

  btn.disabled = true;
  spinner.style.display = 'flex';
  alertBox.style.display = 'none';

  const formData = new FormData(document.getElementById('importForm'));
  formData.append('action', 'upload');

  try {
    const res = await fetch('api/overtime_import.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    alertBox.style.display = 'block';
    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">check_circle</span> ${data.message}</h6>
        <div class="small">
          Mã đợt: <strong class="font-monospace">${data.batch_code}</strong> | 
          Tổng số dòng: <strong>${data.total_rows}</strong> | 
          Thêm mới: <strong>+${data.inserted_rows}</strong> | 
          Ghi đè: <strong>${data.updated_rows}</strong> | 
          Lỗi: <strong>${data.error_rows}</strong>
        </div>
      `;
      document.getElementById('excelFile').value = '';
      await loadBatchesList();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `<strong>Lỗi:</strong> ${data.message}`;
    }
  } catch (err) {
    console.error('Lỗi handleUploadSubmit:', err);
    alertBox.style.display = 'block';
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi kết nối:</strong> ${err.message}`;
  } finally {
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

async function autoImportDataFolderFiles() {
  if (!confirm('Bạn có muốn nạp 2 file DanhSachDuyetTangCaKeHoach.xlsx và DanhSachDuyetTangCaThucTe.xlsx từ thư mục Data không?')) return;

  const alertBox = document.getElementById('importResultAlert');
  alertBox.style.display = 'block';
  alertBox.className = 'mt-3 p-3 rounded border bg-info-subtle border-info text-info';
  alertBox.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang nạp 2 file từ thư mục Data...';

  try {
    const res = await fetch('api/overtime_import.php?action=import_data_files', {
      method: 'POST'
    });
    const data = await res.json();

    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      const planRes = data.results.plan;
      const actRes = data.results.actual;
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">check_circle</span> ${data.message}</h6>
        <div class="small mt-1">
          • Kế hoạch: ${planRes.message || 'Xong'}<br/>
          • Thực tế: ${actRes.message || 'Xong'}
        </div>
      `;
      await loadBatchesList();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `<strong>Lỗi:</strong> ${data.message}`;
    }
  } catch (err) {
    console.error('Lỗi autoImportDataFolderFiles:', err);
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi:</strong> ${err.message}`;
  }
}

async function openBatchErrors(batchId, batchCode) {
  document.getElementById('errorModalBatchCode').textContent = batchCode;
  const tbody = document.getElementById('batchErrorsTableBody');
  tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-2">Đang tải danh sách lỗi...</td></tr>';

  try {
    const res = await fetch(`api/overtime_import.php?action=get_batch_errors&batch_id=${batchId}`);
    const data = await res.json();
    if (!data.success || !data.errors || data.errors.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-2">Không có dòng lỗi nào.</td></tr>';
    } else {
      let html = '';
      data.errors.forEach(e => {
        html += `
          <tr>
            <td class="text-center fw-bold text-danger">Dòng ${e.row_index}</td>
            <td class="text-danger fw-bold">${escapeHtml(e.error_reason)}</td>
            <td><code style="word-break: break-all;">${escapeHtml(e.raw_data)}</code></td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('batchErrorsModal')).show();
  } catch (err) {
    console.error('Lỗi openBatchErrors:', err);
  }
}

async function openBatchLogs(batchId, batchCode) {
  document.getElementById('logModalBatchCode').textContent = batchCode;
  const tbody = document.getElementById('batchLogsTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Đang tải audit log...</td></tr>';

  try {
    const res = await fetch(`api/overtime_import.php?action=get_batch_logs&batch_id=${batchId}`);
    const data = await res.json();
    if (!data.success || !data.logs || data.logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Chưa có lịch sử audit log cho đợt này.</td></tr>';
    } else {
      let html = '';
      data.logs.forEach(l => {
        const actBadge = l.action === 'insert' 
          ? '<span class="badge bg-success">Thêm mới</span>' 
          : '<span class="badge bg-primary">Ghi đè</span>';
        html += `
          <tr>
            <td class="font-monospace fw-bold text-primary">${escapeHtml(l.employee_code)}</td>
            <td>${l.ot_date}</td>
            <td class="text-center">${actBadge}</td>
            <td><small class="text-muted"><pre class="m-0" style="font-size: 10px;">${escapeHtml(l.before_data || '-')}</pre></small></td>
            <td><small class="text-dark"><pre class="m-0" style="font-size: 10px;">${escapeHtml(l.after_data)}</pre></small></td>
            <td><small class="text-muted">${l.created_at}</small></td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('batchLogsModal')).show();
  } catch (err) {
    console.error('Lỗi openBatchLogs:', err);
  }
}

let importCountdownInterval = null;
let importRemainingSec = 0;

function startImportCountdown(sec) {
  importRemainingSec = Math.max(0, parseInt(sec) || 0);
  const cdText = document.getElementById('importHrmCountdownText');
  if (!cdText) return;

  if (importCountdownInterval) clearInterval(importCountdownInterval);

  function renderImportCd() {
    const h = Math.floor(importRemainingSec / 3600);
    const m = Math.floor((importRemainingSec % 3600) / 60);
    const s = importRemainingSec % 60;
    cdText.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  }

  renderImportCd();
  importCountdownInterval = setInterval(() => {
    importRemainingSec--;
    if (importRemainingSec <= 0) {
      clearInterval(importCountdownInterval);
      cdText.textContent = 'Đang đồng bộ...';
      fetch('api/overtime_hrm_sync.php?action=check_schedule')
        .then(r => r.json())
        .then(d => {
          if (d.ran_sync) {
            loadHrmGlobalConfig();
          }
          if (d.seconds_remaining !== undefined && d.auto_sync_enabled) {
            startImportCountdown(d.seconds_remaining);
          } else if (!d.auto_sync_enabled) {
            cdText.textContent = 'Đã tắt';
          }
        })
        .catch(console.error);
    } else {
      renderImportCd();
    }
  }, 1000);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
