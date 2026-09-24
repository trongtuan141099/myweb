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

  <!-- 2. Thẻ Tự Động Hóa Đồng Bộ HRM (HRM Auto Sync Card) -->
  <div class="app-card mb-4" style="border-left: 4px solid var(--dx-primary);">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-primary fs-5">satellite_alt</span>
        <strong class="fs-6">KẾT NỐI TỰ ĐỘNG HRM (https://hrm.smcmfg.com.vn)</strong>
      </div>
      <div class="d-flex align-items-center gap-2" id="hrmSyncStatusBadgeContainer">
        <span class="badge bg-secondary" id="hrmLastStatusBadge">Đang kiểm tra...</span>
        <span class="text-muted small" id="hrmLastSyncTimeText"></span>
      </div>
    </div>

    <div class="p-3">
      <form id="hrmSyncConfigForm" onsubmit="handleSaveHrmConfig(event)">
        <div class="row g-3">
          <!-- Tài khoản HRM -->
          <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Tài khoản HRM <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><span class="material-icons fs-6">person</span></span>
              <input type="text" class="app-form-control" id="hrmUsername" placeholder="VD: 0202xxxx" required autocomplete="username">
            </div>
          </div>

          <!-- Mật khẩu HRM -->
          <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Mật khẩu HRM <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><span class="material-icons fs-6">lock</span></span>
              <input type="password" class="app-form-control" id="hrmPassword" placeholder="Nhập mật khẩu" autocomplete="current-password">
              <button class="btn btn-outline-secondary" type="button" onclick="toggleHrmPasswordVisibility()" title="Ẩn/Hiện">
                <span class="material-icons fs-6" id="eyeIcon">visibility</span>
              </button>
            </div>
          </div>

          <!-- Tần suất đồng bộ -->
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

          <!-- Kích hoạt Tự động -->
          <div class="col-md-3 d-flex align-items-center">
            <div class="form-check form-switch mt-3">
              <input class="form-check-input" type="checkbox" id="hrmAutoSyncEnabled" checked>
              <label class="form-check-label fw-bold small ms-1" for="hrmAutoSyncEnabled">
                Bật tự động đồng bộ ngầm
              </label>
            </div>
          </div>

          <!-- Cấu hình Endpoint & Kỳ xuất dữ liệu HRM (Collapsible / Advanced) -->
          <div class="col-12">
            <details class="small">
              <summary class="text-primary fw-bold cursor-pointer" style="cursor: pointer;">
                <span class="material-icons fs-6 align-middle">settings_suggest</span> Cấu hình nâng cao Endpoint & Kỳ xuất dữ liệu HRM (Động)
              </summary>
              <div class="row g-2 mt-2 p-3 bg-light rounded border">
                <div class="col-md-6">
                  <label class="form-label fw-bold text-muted small">Endpoint Portal Duyệt Tăng Ca (Lấy Token):</label>
                  <input type="text" class="app-form-control app-form-control-sm" id="hrmPortalEndpoint" value="https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaKeHoach">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold text-muted small">Endpoint POST Xuất Kế Hoạch:</label>
                  <input type="text" class="app-form-control app-form-control-sm" id="hrmPlanExportEndpoint" value="https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaKeHoach">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold text-muted small">Endpoint POST Xuất Thực Tế:</label>
                  <input type="text" class="app-form-control app-form-control-sm" id="hrmActualExportEndpoint" value="https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaThucTe">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold text-muted small">Từ ngày:</label>
                  <input type="date" class="app-form-control app-form-control-sm" id="hrmSyncDateFrom">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold text-muted small">Đến ngày:</label>
                  <input type="date" class="app-form-control app-form-control-sm" id="hrmSyncDateTo">
                </div>
              </div>
            </details>
          </div>

          <!-- Thao tác cấu hình -->
          <div class="col-12 d-flex align-items-center justify-content-between pt-1">
            <div class="small text-muted" id="hrmLastSyncSummaryText">
              Chưa có dữ liệu đồng bộ.
            </div>
            <div class="d-flex align-items-center gap-2">
              <button type="submit" class="app-btn app-btn-secondary btn-sm" id="btnSaveHrmConfig">
                <span class="material-icons fs-6">save</span> Lưu Cấu Hình
              </button>
              <button type="button" class="app-btn app-btn-primary btn-sm" id="btnHrmSyncNow" onclick="triggerHrmSyncNow()">
                <span class="material-icons fs-6">play_arrow</span> Đồng Bộ Ngay
              </button>
            </div>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  loadHrmSyncConfig();
  loadBatchesList();
});

// =========================================================================
// HRM AUTOMATED SYNC HANDLERS
// =========================================================================
async function loadHrmSyncConfig() {
  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=get_config');
    const data = await res.json();
    if (!data.success || !data.config) return;

    const c = data.config;
    document.getElementById('hrmUsername').value = c.username || '';
    document.getElementById('hrmPassword').value = c.password || '';
    document.getElementById('hrmSyncInterval').value = c.sync_interval_hours || 3;
    document.getElementById('hrmAutoSyncEnabled').checked = !!c.auto_sync_enabled;
    if (c.portal_endpoint && document.getElementById('hrmPortalEndpoint')) document.getElementById('hrmPortalEndpoint').value = c.portal_endpoint;
    if (c.plan_export_endpoint && document.getElementById('hrmPlanExportEndpoint')) document.getElementById('hrmPlanExportEndpoint').value = c.plan_export_endpoint;
    if (c.actual_export_endpoint && document.getElementById('hrmActualExportEndpoint')) document.getElementById('hrmActualExportEndpoint').value = c.actual_export_endpoint;
    if (c.sync_date_from && document.getElementById('hrmSyncDateFrom')) document.getElementById('hrmSyncDateFrom').value = c.sync_date_from;
    if (c.sync_date_to && document.getElementById('hrmSyncDateTo')) document.getElementById('hrmSyncDateTo').value = c.sync_date_to;

    // Hiển thị trạng thái lần chạy gần nhất
    const badge = document.getElementById('hrmLastStatusBadge');
    const timeText = document.getElementById('hrmLastSyncTimeText');
    const summaryText = document.getElementById('hrmLastSyncSummaryText');

    if (c.last_sync_status === 'success') {
      badge.className = 'badge bg-success';
      badge.innerHTML = '<span class="material-icons fs-6 align-middle">check_circle</span> Đã đồng bộ';
    } else if (c.last_sync_status === 'failed') {
      badge.className = 'badge bg-danger';
      badge.innerHTML = '<span class="material-icons fs-6 align-middle">error</span> Lỗi đồng bộ';
    } else {
      badge.className = 'badge bg-secondary';
      badge.textContent = 'Chưa đồng bộ';
    }

    if (c.last_sync_time) {
      timeText.textContent = `Lần cuối: ${c.last_sync_time}`;
    } else {
      timeText.textContent = '';
    }

    if (c.last_sync_message) {
      summaryText.innerHTML = `<strong>Nhật ký:</strong> ${escapeHtml(c.last_sync_message)}`;
    }
  } catch (err) {
    console.error('Lỗi loadHrmSyncConfig:', err);
  }
}

function toggleHrmPasswordVisibility() {
  const pwd = document.getElementById('hrmPassword');
  const icon = document.getElementById('eyeIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    pwd.type = 'password';
    icon.textContent = 'visibility';
  }
}

async function handleSaveHrmConfig(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveHrmConfig');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  const payload = {
    username: document.getElementById('hrmUsername').value.trim(),
    password: document.getElementById('hrmPassword').value.trim(),
    sync_interval_hours: parseInt(document.getElementById('hrmSyncInterval').value),
    auto_sync_enabled: document.getElementById('hrmAutoSyncEnabled').checked,
    portal_endpoint: document.getElementById('hrmPortalEndpoint') ? document.getElementById('hrmPortalEndpoint').value.trim() : '',
    plan_export_endpoint: document.getElementById('hrmPlanExportEndpoint') ? document.getElementById('hrmPlanExportEndpoint').value.trim() : '',
    actual_export_endpoint: document.getElementById('hrmActualExportEndpoint') ? document.getElementById('hrmActualExportEndpoint').value.trim() : '',
    sync_date_from: document.getElementById('hrmSyncDateFrom') ? document.getElementById('hrmSyncDateFrom').value.trim() : '',
    sync_date_to: document.getElementById('hrmSyncDateTo') ? document.getElementById('hrmSyncDateTo').value.trim() : '',
  };

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=save_config', {
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
    console.error('Lỗi handleSaveHrmConfig:', err);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">save</span> Lưu Cấu Hình';
  }
}

async function triggerHrmSyncNow() {
  const btn = document.getElementById('btnHrmSyncNow');
  const btnTop = document.getElementById('btnHrmSyncNowTop');
  const alertBox = document.getElementById('hrmSyncAlert');

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang đồng bộ...';
  }
  if (btnTop) {
    btnTop.disabled = true;
    btnTop.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang nạp HRM...';
  }

  alertBox.style.display = 'block';
  alertBox.className = 'mt-3 p-3 rounded border bg-info-subtle border-info text-info';
  alertBox.innerHTML = '<div class="d-flex align-items-center gap-2"><div class="spinner-border spinner-border-sm text-info"></div><span>Đang kết nối đến hệ thống HRM, tải file và thực hiện đối soát tự động... Vui lòng mở <strong>Console (F12)</strong> để theo dõi log URL chi tiết.</span></div>';

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=trigger_sync', {
      method: 'POST'
    });
    const data = await res.json();

    // 1. In toàn bộ URL và tiến trình chi tiết ra Console (F12)
    console.group('%c[HRM SYNC] TIẾN TRÌNH & ENDPOINT ĐỒNG BỘ DỮ LIỆU HRM', 'color: #0d6efd; font-weight: bold; font-size: 14px; padding: 4px;');
    if (data.urls_called && data.urls_called.length > 0) {
      console.log('%c Danh sách URL & Endpoint được gọi:', 'font-weight: bold; color: #198754;');
      data.urls_called.forEach((u, idx) => {
        console.log(`%c [${idx + 1}] ${u}`, 'color: #0d6efd; font-family: monospace; font-size: 12px; font-weight: bold;');
      });
    }
    if (data.steps && data.steps.length > 0) {
      console.log('%c Chi tiết từng bước thực thi:', 'font-weight: bold; color: #495057;');
      data.steps.forEach(s => {
        const badgeColor = s.status === 'success' ? '#198754' : (s.status === 'warning' ? '#fd7e14' : '#dc3545');
        console.log(`%c[Bước ${s.step}] [${s.status.toUpperCase()}] ${s.title}`, `color: ${badgeColor}; font-weight: bold; font-size: 12px;`);
        if (s.url) console.log(`   └─ URL: %c${s.method} ${s.url}`, 'color: #0d6efd; font-family: monospace;');
        if (s.http_code) console.log(`   └─ HTTP Code: ${s.http_code}`);
        console.log(`   └─ Chi tiết: ${s.detail}`);
      });
    }
    console.groupEnd();

    // 2. Render danh sách tiến trình các bước trên giao diện (UI Step Progress)
    let stepsHtml = '';
    if (data.steps && data.steps.length > 0) {
      stepsHtml = `
        <div class="mt-3 pt-2 border-top">
          <div class="fw-bold small mb-2 d-flex align-items-center justify-content-between">
            <span><span class="material-icons fs-6 align-middle">timeline</span> Tiến trình thực hiện (${data.steps.length} bước):</span>
            <span class="text-muted" style="font-size: 11px;">Mở Console (F12) để xem chi tiết log URL</span>
          </div>
          <div class="list-group list-group-flush rounded border" style="max-height: 280px; overflow-y: auto; font-size: 12px;">
      `;
      data.steps.forEach(s => {
        const icon = s.status === 'success' ? 'check_circle' : (s.status === 'warning' ? 'warning' : 'cancel');
        const colorClass = s.status === 'success' ? 'text-success' : (s.status === 'warning' ? 'text-warning' : 'text-danger');
        stepsHtml += `
          <div class="list-group-item d-flex align-items-start gap-2 py-2 px-3">
            <span class="material-icons fs-6 ${colorClass} mt-0">${icon}</span>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center justify-content-between">
                <strong class="${colorClass}">Bước ${s.step}: ${escapeHtml(s.title)}</strong>
                <small class="text-muted font-monospace">${s.time || ''}</small>
              </div>
              ${s.url ? `<div class="font-monospace text-truncate text-muted mt-1" style="max-width: 600px; font-size: 11px;" title="${escapeHtml(s.url)}"><strong>${s.method}</strong> ${escapeHtml(s.url)}</div>` : ''}
              <div class="text-secondary mt-1">${escapeHtml(s.detail)}</div>
            </div>
          </div>
        `;
      });
      stepsHtml += `</div></div>`;
    }

    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">check_circle</span> ${data.message}</h6>
        <div class="small mt-2">
          • Kế hoạch: <strong>+${data.plan_result?.inserted_rows || 0}</strong> mới, <strong>${data.plan_result?.updated_rows || 0}</strong> cập nhật<br/>
          • Thực tế: <strong>+${data.actual_result?.inserted_rows || 0}</strong> mới, <strong>${data.actual_result?.updated_rows || 0}</strong> cập nhật<br/>
          • Đối soát: Tổng <strong>${data.reconciliation?.total || 0}</strong> ca (Khớp: <strong>${data.reconciliation?.matched || 0}</strong>, Cần giải trình: <strong>${data.reconciliation?.needs_explanation || 0}</strong>)
        </div>
        ${stepsHtml}
      `;
      await loadHrmSyncConfig();
      await loadBatchesList();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">error</span> Lỗi đồng bộ HRM: ${escapeHtml(data.message)}</h6>
        ${stepsHtml}
      `;
      await loadHrmSyncConfig();
    }
  } catch (err) {
    console.error('Lỗi triggerHrmSyncNow:', err);
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi kết nối máy chủ:</strong> ${err.message}`;
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-icons fs-6">play_arrow</span> Đồng Bộ Ngay';
    }
    if (btnTop) {
      btnTop.disabled = false;
      btnTop.innerHTML = '<span class="material-icons fs-6">sync</span> Đồng Bộ Ngay Từ HRM';
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

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
