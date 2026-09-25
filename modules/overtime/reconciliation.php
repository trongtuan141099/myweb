<?php
/**
 * Module: Đối Soát Tăng Ca Kế Hoạch & Thực Tế
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();
requirePermission('overtime.reconcile');

$currentMonth = intval(date('m'));
$currentYear = intval(date('Y'));
$userRole = $_SESSION['user']['role'] ?? 'viewer';
$canManageRec = ($userRole !== 'viewer') && hasPermission('overtime.reconcile');
$canSyncHrm = ($userRole !== 'viewer') && hasPermission('api.overtime.hrm_sync');
?>

<style>
.badge-rec-matched     { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-weight: 700; }
.badge-rec-plan_only   { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 700; }
.badge-rec-actual_only { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-weight: 700; }
.badge-rec-time_diff   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-weight: 700; }
.badge-rec-overdue     { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 700; }
.badge-rec-explained   { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; }

[data-theme="dark"] .badge-rec-matched     { background: rgba(34, 197, 94, 0.18); color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
[data-theme="dark"] .badge-rec-plan_only   { background: rgba(217, 119, 6, 0.18); color: #fbbf24; border-color: rgba(217, 119, 6, 0.35); }
[data-theme="dark"] .badge-rec-actual_only { background: rgba(126, 34, 206, 0.18); color: #c084fc; border-color: rgba(126, 34, 206, 0.35); }
[data-theme="dark"] .badge-rec-time_diff   { background: rgba(234, 88, 12, 0.18); color: #fb923c; border-color: rgba(234, 88, 12, 0.35); }
[data-theme="dark"] .badge-rec-overdue     { background: rgba(220, 38, 38, 0.18); color: #f87171; border-color: rgba(220, 38, 38, 0.35); }
[data-theme="dark"] .badge-rec-explained   { background: rgba(148, 163, 184, 0.18); color: #94a3b8; border-color: rgba(148, 163, 184, 0.35); }

/* Tô xám hàng đã hoàn thành đủ 2 bước */
.row-completed {
  opacity: 0.65;
  background-color: var(--dx-bg-subtle, rgba(0, 0, 0, 0.025)) !important;
}
.row-completed td {
  background-color: transparent !important;
}

/* Tô xám hàng đã chuyển giải trình */
.row-explained {
  opacity: 0.65;
  background-color: var(--dx-bg-subtle, rgba(0, 0, 0, 0.03)) !important;
}
.row-explained td {
  background-color: transparent !important;
  color: var(--dx-text-muted) !important;
}

/* Tô xám hàng đã tạm hủy */
.row-dismissed {
  opacity: 0.55;
  background-color: var(--dx-bg-subtle, rgba(0, 0, 0, 0.04)) !important;
}
.row-dismissed td {
  background-color: transparent !important;
  color: var(--dx-text-muted) !important;
}

/* Cảnh báo viền đỏ hàng thiếu bước quá 3 ngày */
.row-overdue-3days {
  background-color: rgba(239, 68, 68, 0.04) !important;
  border-left: 3px solid #ef4444 !important;
}
[data-theme="dark"] .row-overdue-3days {
  background-color: rgba(239, 68, 68, 0.09) !important;
  border-left: 3px solid #ef4444 !important;
}

/* Viền cảnh báo hàng chưa hoàn thành Thực tế */
.row-uncompleted-actual {
  background-color: rgba(245, 158, 11, 0.04) !important;
  border-left: 3px solid #f59e0b !important;
}
[data-theme="dark"] .row-uncompleted-actual {
  background-color: rgba(245, 158, 11, 0.08) !important;
  border-left: 3px solid #f59e0b !important;
}

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
.rec-filter-tab.active .badge {
  background: #fff !important;
  color: var(--dx-primary) !important;
}
</style>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">fact_check</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">ĐỐI SOÁT TĂNG CA KẾ HOẠCH & THỰC TẾ</h1>
        <p class="text-muted small mb-0">Theo dõi lệnh tăng ca theo ID duy nhất, kiểm tra hoàn thành 2 bước, phát hiện quá hạn 3 ngày & xử lý giải trình</p>
      </div>
    </div>
    <div class="app-page-actions d-flex align-items-center gap-2 flex-wrap">
      <!-- Bộ lọc Ngày Tăng Ca (ot_date) -->
      <div class="input-group input-group-sm" style="width: 220px;" title="Lọc theo ngày tăng ca cụ thể">
        <span class="input-group-text bg-transparent border-end-0">
          <span class="material-icons fs-6 text-muted">event</span>
        </span>
        <input type="date" class="app-form-control app-form-control-sm border-start-0" id="recOtDate" onchange="handleOtDateChange()">
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearOtDateFilter()" title="Xóa lọc ngày">
          <span class="material-icons fs-6">close</span>
        </button>
      </div>

      <!-- Bộ lọc Tháng / Năm -->
      <select class="app-form-select app-form-select-sm" id="recMonth" style="width: 120px;" onchange="loadReconciliations(1)">
        <option value="">-- Cả năm --</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($m === $currentMonth) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
        <?php endfor; ?>
      </select>
      <select class="app-form-select app-form-select-sm" id="recYear" style="width: 95px;" onchange="loadReconciliations(1)">
        <option value="2026" <?= ($currentYear === 2026) ? 'selected' : '' ?>>2026</option>
        <option value="2025" <?= ($currentYear === 2025) ? 'selected' : '' ?>>2025</option>
        <option value="2024">2024</option>
      </select>

      <?php if ($canSyncHrm): ?>
      <button class="app-btn app-btn-secondary btn-sm" type="button" onclick="triggerHrmSyncFromRec(event)" title="Tự động đồng bộ dữ liệu từ máy chủ HRM">
        <span class="material-icons fs-6">sync</span> Đồng Bộ HRM
      </button>
      <?php endif; ?>

      <?php if ($canManageRec): ?>
      <button class="app-btn app-btn-primary btn-sm" type="button" onclick="triggerRunReconcile()">
        <span class="material-icons fs-6">play_arrow</span> Chạy Đối Soát Tự Động
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- 1.1. Thanh Trạng Thái Đồng Bộ HRM (HRM Sync Bar) -->
  <div class="app-card mb-3 py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-left: 4px solid var(--dx-primary);">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="material-icons text-primary fs-5">satellite_alt</span>
      <span class="fw-bold small">Đồng bộ HRM (hrm.smcmfg.com.vn):</span>
      <span class="badge bg-secondary font-monospace" id="hrmSyncLastTimeBadge">Đang kiểm tra...</span>
      <span class="badge bg-light text-dark border" id="hrmSyncStatusBadge">-</span>
      <span class="badge bg-primary-subtle text-primary border font-monospace d-inline-flex align-items-center gap-1" id="hrmSyncCountdownBadge" title="Thời gian tự động đồng bộ kế tiếp">
        <span class="material-icons" style="font-size:13px;">timer</span>
        <span id="hrmSyncCountdownText">--:--:--</span>
      </span>
      <span class="text-muted small d-none d-md-inline" id="hrmSyncMsgBadge"></span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($canSyncHrm): ?>
      <button class="btn btn-outline-primary btn-sm py-0 px-2 d-inline-flex align-items-center gap-1" onclick="triggerHrmSyncFromRec(event)" title="Đồng bộ lại từ HRM ngay bây giờ">
        <span class="material-icons fs-6">refresh</span> Đồng bộ lại
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- 2. Thanh Tabs Phân Loại & Bộ Đếm Thống Kê -->
  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="recFilterTabsContainer">
    <div class="rec-filter-tab active" onclick="setRecStatusFilter('', this)">
      Tất cả <span class="badge bg-light text-dark rounded-pill" id="badgeAll">0</span>
    </div>
    <!-- Tab Ưu tiên: Chưa hoàn thành Bước 2 (Thực tế) -->
    <div class="rec-filter-tab" onclick="setRecStatusFilter('uncompleted_actual', this)" style="border-color: #f59e0b;">
      <span class="material-icons fs-6 text-warning">pending_actions</span> Chờ xong B2 (Thiếu TT) 
      <span class="badge bg-warning rounded-pill text-dark" id="badgeUncompletedActual">0</span>
    </div>
    <!-- Tab Quá hạn 3 ngày -->
    <div class="rec-filter-tab" onclick="setRecStatusFilter('overdue_3days', this)" style="border-color: #ef4444;">
      <span class="material-icons fs-6 text-danger">warning</span> Quá hạn 3 ngày 
      <span class="badge bg-danger rounded-pill text-white" id="badgeOverdue3Days">0</span>
    </div>
    <!-- Tab Hoàn thành đủ 2 bước -->
    <div class="rec-filter-tab" onclick="setRecStatusFilter('completed', this)">
      <span class="material-icons fs-6 text-success">check_circle</span> Hoàn thành (Đủ 2 bước) 
      <span class="badge bg-light text-dark rounded-pill" id="badgeCompleted">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('needs_explanation', this)">
      <span class="material-icons fs-6 text-danger">campaign</span> Cần giải trình 
      <span class="badge bg-danger rounded-pill text-white" id="badgeNeedsExp">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('dismissed', this)">
      <span class="material-icons fs-6 text-secondary">block</span> Tạm hủy 
      <span class="badge bg-light text-dark rounded-pill" id="badgeDismissed">0</span>
    </div>
    <div class="rec-filter-tab" onclick="setRecStatusFilter('explained', this)">
      <span class="material-icons fs-6 text-muted">done_all</span> Đã chuyển giải trình 
      <span class="badge bg-light text-dark rounded-pill" id="badgeExplained">0</span>
    </div>
  </div>

  <!-- 3. Toolbar Tìm Kiếm & Toggle Tự Động Tạm Ẩn Hoàn Thành -->
  <div class="app-filter-card mb-3">
    <div class="row g-2 w-100 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">search</span>
          </span>
          <input type="text" class="app-form-control border-start-0" id="recSearchInput" placeholder="Tìm theo mã lệnh (#OT-...), tên hoặc mã nhân viên..." oninput="handleRecSearch(this.value)">
        </div>
      </div>
      <div class="col-md-7 d-flex align-items-center justify-content-end gap-3 flex-wrap small">
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" id="recToggleHideCompleted" checked onchange="handleHideCompletedToggle(this.checked)">
          <label class="form-check-label fw-bold text-muted cursor-pointer" for="recToggleHideCompleted" title="Khi bật, các lệnh đã hoàn thành đủ 2 bước sẽ tạm ẩn để ưu tiên xử lý các ca còn thiếu bước hoặc quá hạn">
            Tự động tạm ẩn ca đã hoàn thành
          </label>
        </div>
        <span class="d-inline-flex align-items-center gap-1 text-warning fw-bold">
          <span class="material-icons fs-6">priority_high</span> Ưu tiên ca chưa xong B2 & quá hạn lên đầu
        </span>
      </div>
    </div>
  </div>

  <!-- 4. Bảng Đối Soát 2 Cột Song Song (Kế Hoạch vs Thực Tế) -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 380px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 105px;">Mã Lệnh OT</th>
            <th style="min-width: 170px;">Nhân Viên</th>
            <th style="width: 95px;">Ngày OT</th>
            <th style="width: 130px; text-align: center;">Tiến Trình 2 Bước</th>
            <!-- Cột Kế Hoạch -->
            <th style="background: rgba(30, 64, 175, 0.05); border-left: 2px solid var(--dx-primary); min-width: 190px;">
              Bước 1: Kế Hoạch (KH)
            </th>
            <th style="background: rgba(30, 64, 175, 0.05); width: 80px; text-align: right;">Phút KH</th>
            <!-- Cột Thực Tế -->
            <th style="background: rgba(22, 163, 74, 0.05); border-left: 2px solid var(--dx-success); min-width: 190px;">
              Bước 2: Thực Tế (TT)
            </th>
            <th style="background: rgba(22, 163, 74, 0.05); width: 80px; text-align: right;">Phút TT</th>
            <!-- Độ lệch & Trạng thái -->
            <th style="width: 95px; text-align: right;">Chênh Lệch</th>
            <th style="width: 165px; text-align: center;">Trạng Thái Đối Soát</th>
            <th style="width: 140px; text-align: center;">Thao Tác</th>
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

<!-- Modal 1: Chi Tiết Đối Soát & So Sánh 2 Cột -->
<div class="modal fade" id="recDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">compare_arrows</span>
          Đối Chiếu Chi Tiết Lệnh Tăng Ca <span id="detailModalOrderCode" class="font-monospace"></span>
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

<!-- Modal 2: Admin Chủ Động Yêu Cầu Giải Trình -->
<div class="modal fade" id="requestExplanationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">campaign</span>
          Yêu Cầu Giải Trình Lệnh <span id="reqExpOrderCode" class="font-monospace"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form onsubmit="handleConfirmRequestExplanation(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="reqExpRecId">
          <div class="p-2 mb-3 bg-light rounded border small">
            <div>Nhân viên: <strong id="reqExpEmpName">-</strong> (<span id="reqExpEmpCode" class="font-monospace"></span>)</div>
            <div>Ngày tăng ca: <strong id="reqExpOtDate">-</strong></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Lý do / Loại vi phạm yêu cầu giải trình <span class="text-danger">*</span></label>
            <input type="text" class="app-form-control app-form-control-sm" id="reqExpViolationType" required placeholder="VD: Thiếu thực tế quá hạn 3 ngày">
            <!-- Gợi ý nhanh -->
            <div class="d-flex align-items-center gap-1 mt-2 flex-wrap small">
              <span class="text-muted">Gợi ý:</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setReqExpSuggest('Thiếu thực tế quá hạn 3 ngày')">Quá hạn 3 ngày</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setReqExpSuggest('Có kế hoạch nhưng thiếu thực tế')">Thiếu thực tế</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setReqExpSuggest('Có thực tế nhưng thiếu kế hoạch')">Thiếu kế hoạch</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setReqExpSuggest('Chênh lệch giờ giữa Kế hoạch và Thực tế')">Lệch giờ</span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Ghi chú bổ sung của Quản lý / Admin (Không bắt buộc)</label>
            <textarea class="app-form-control" id="reqExpAdminNote" rows="2" placeholder="Nhập thêm yêu cầu hoặc thời hạn nhân viên cần gửi lại giải trình..."></textarea>
            <small class="text-muted d-block mt-1">
              * Dữ liệu giải trình sẽ được <strong>lưu vào trang Quản lý giải trình</strong> để nhân viên / tổ trưởng nạp lý do và Quản lý thẩm định.
            </small>
          </div>
        </div>

        <div class="modal-footer py-2 px-3">
          <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-primary btn-sm" id="btnSubmitReqExp">
            <span class="material-icons fs-6">send</span> Gửi Yêu Cầu
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 3: Admin Tạm Hủy Yêu Cầu Giải Trình (Dismiss Modal) -->
<div class="modal fade" id="dismissModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">block</span>
          Tạm Hủy / Bỏ Qua Giải Trình Lệnh <span id="dismissOrderCode" class="font-monospace"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form onsubmit="handleConfirmDismiss(event)">
        <div class="modal-body p-3">
          <input type="hidden" id="dismissRecId">
          <div class="p-2 mb-3 bg-light rounded border small">
            <div>Nhân viên: <strong id="dismissEmpName">-</strong> (<span id="dismissEmpCode" class="font-monospace"></span>)</div>
            <div>Ngày tăng ca: <strong id="dismissOtDate">-</strong></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-muted">Lý do tạm hủy / bỏ qua giải trình <span class="text-danger">*</span></label>
            <input type="text" class="app-form-control app-form-control-sm" id="dismissReasonInput" required placeholder="VD: Trưởng ca đã xác nhận bằng văn bản">
            <!-- Gợi ý nhanh -->
            <div class="d-flex align-items-center gap-1 mt-2 flex-wrap small">
              <span class="text-muted">Gợi ý:</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setDismissSuggest('Trưởng ca đã xác nhận hợp lệ')">Trưởng ca xác nhận</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setDismissSuggest('Trường hợp sự cố bất khả kháng')">Sự cố kỹ thuật</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setDismissSuggest('Ca làm bù giờ theo kế hoạch tổ')">Bù giờ theo tổ</span>
              <span class="badge bg-light text-dark border cursor-pointer" onclick="setDismissSuggest('Lỗi nhập dữ liệu hoặc trùng ca')">Lỗi dữ liệu</span>
            </div>
            <small class="text-muted d-block mt-2">
              * Sau khi bấm tạm hủy, hệ thống sẽ <strong>tô xám lệnh</strong>, gỡ bỏ cảnh báo quá hạn và không gửi thông báo nhắc việc nữa. Bạn có thể khôi phục bất cứ lúc nào.
            </small>
          </div>
        </div>

        <div class="modal-footer py-2 px-3">
          <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="app-btn app-btn-danger btn-sm" id="btnSubmitDismiss">
            <span class="material-icons fs-6">check</span> Xác Nhận Tạm Hủy
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 4: Tiến Trình & Kết Quả Đồng Bộ HRM -->
<div class="modal fade" id="hrmSyncDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">sync</span>
          Tiến Trình & Kết Quả Đồng Bộ HRM
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="hrmSyncDetailModalBody">
        <!-- Render nội dung kết quả và danh sách các bước -->
      </div>
      <div class="modal-footer py-2 px-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small">Xem toàn bộ URL & Endpoint tại Console (F12)</span>
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal 5: Chi Tiết Lịch Sử Tăng Ca Nhân Viên -->
<div class="modal fade" id="empHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">history</span>
          Chi Tiết Lịch Sử Tăng Ca: <span id="modalEmpTitle" class="font-monospace">...</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div id="modalEmpSummaryCard" class="p-3 mb-3 rounded bg-light border"></div>
        <div class="app-table-responsive" style="max-height: 420px; overflow-y: auto;">
          <table class="app-table table-sm" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th style="width: 45px; text-align: center;">STT</th>
                <th>Ngày OT</th>
                <th>Bắt Đầu TT</th>
                <th>Kết Thúc TT</th>
                <th style="text-align: right;">Số Phút TT</th>
                <th style="text-align: right;">Số Giờ TT</th>
                <th>Lý Do Tăng Ca</th>
                <th>Quản Lý Duyệt</th>
                <th style="text-align: center;">Trạng Thái</th>
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

<script>
const CAN_MANAGE_REC = <?= json_encode($canManageRec) ?>;
const CAN_SYNC_HRM   = <?= json_encode($canSyncHrm) ?>;

let currentRecStatus = '';
let currentRecPage = 1;
let recSearchTimeout = null;
let hideCompletedActive = true;

document.addEventListener('DOMContentLoaded', () => {
  loadReconciliations(1);
  fetch('api/overtime_hrm_sync.php?action=check_schedule')
    .then(r => r.json())
    .then(d => {
      if (d.last_sync_time) {
        updateHrmSyncDisplay({
          last_sync_time: d.last_sync_time,
          last_sync_time_formatted: d.last_sync_time_formatted,
          last_sync_status: d.success ? 'success' : 'failed',
          last_sync_message: d.message,
          seconds_remaining: d.seconds_remaining
        });
      }
    })
    .catch(console.error);
});

function handleOtDateChange() {
  const otDateVal = document.getElementById('recOtDate').value;
  const monthSelect = document.getElementById('recMonth');
  const yearSelect = document.getElementById('recYear');

  if (otDateVal) {
    monthSelect.disabled = true;
    yearSelect.disabled = true;
  } else {
    monthSelect.disabled = false;
    yearSelect.disabled = false;
  }
  loadReconciliations(1);
}

function clearOtDateFilter() {
  document.getElementById('recOtDate').value = '';
  document.getElementById('recMonth').disabled = false;
  document.getElementById('recYear').disabled = false;
  loadReconciliations(1);
}

function setRecStatusFilter(status, el) {
  currentRecStatus = status;
  document.querySelectorAll('.rec-filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  loadReconciliations(1);
}

function handleHideCompletedToggle(isChecked) {
  hideCompletedActive = isChecked;
  loadReconciliations(1);
}

function handleRecSearch(val) {
  clearTimeout(recSearchTimeout);
  recSearchTimeout = setTimeout(() => {
    loadReconciliations(1);
  }, 300);
}

function formatVnDateTimeClient(dtStr) {
  if (!dtStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/.test(dtStr)) return dtStr;
  const d = new Date(dtStr.replace(' ', 'T'));
  if (isNaN(d.getTime())) return dtStr;
  const pad = n => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

// Cập nhật thanh hiển thị trạng thái HRM Sync
function updateHrmSyncDisplay(info) {
  const timeBadge = document.getElementById('hrmSyncLastTimeBadge');
  const statusBadge = document.getElementById('hrmSyncStatusBadge');
  const msgBadge = document.getElementById('hrmSyncMsgBadge');
  if (!timeBadge || !info) return;

  if (info.last_sync_time) {
    timeBadge.textContent = info.last_sync_time_formatted || formatVnDateTimeClient(info.last_sync_time);
  } else {
    timeBadge.textContent = 'Chưa có dữ liệu';
  }

  if (info.last_sync_status === 'success') {
    statusBadge.className = 'badge bg-success';
    statusBadge.textContent = 'Thành công';
  } else if (info.last_sync_status === 'failed') {
    statusBadge.className = 'badge bg-danger';
    statusBadge.textContent = 'Thất bại';
  } else {
    statusBadge.className = 'badge bg-secondary';
    statusBadge.textContent = 'Chưa chạy';
  }

  if (msgBadge && info.last_sync_message) {
    msgBadge.textContent = '— ' + info.last_sync_message;
  }

  if (info.seconds_remaining !== undefined) {
    startOtCountdown(info.seconds_remaining);
  }
}

let otCountdownInterval = null;
let otRemainingSec = 0;

function startOtCountdown(sec) {
  otRemainingSec = Math.max(0, parseInt(sec) || 0);
  const cdText = document.getElementById('hrmSyncCountdownText');
  if (!cdText) return;

  if (otCountdownInterval) clearInterval(otCountdownInterval);

  function renderOtCd() {
    const h = Math.floor(otRemainingSec / 3600);
    const m = Math.floor((otRemainingSec % 3600) / 60);
    const s = otRemainingSec % 60;
    cdText.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  }

  renderOtCd();
  otCountdownInterval = setInterval(() => {
    otRemainingSec--;
    if (otRemainingSec <= 0) {
      clearInterval(otCountdownInterval);
      cdText.textContent = 'Đang đồng bộ...';
      fetch('api/overtime_hrm_sync.php?action=check_schedule')
        .then(r => r.json())
        .then(d => {
          if (d.ran_sync) {
            loadReconciliationData();
          }
          if (d.seconds_remaining !== undefined) {
            startOtCountdown(d.seconds_remaining);
          }
        })
        .catch(console.error);
    } else {
      renderOtCd();
    }
  }, 1000);
}

async function triggerHrmSyncFromRec(e) {
  const btn = e ? e.target.closest('button') : null;
  const oldHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang đồng bộ HRM...';
  }

  try {
    const res = await fetch('api/overtime_hrm_sync.php?action=trigger_sync', { method: 'POST' });
    const data = await res.json();

    // 1. In toàn bộ URL và tiến trình chi tiết ra Console (F12)
    console.group('%c[HRM SYNC] TIẾN TRÌNH & ENDPOINT ĐỒNG BỘ DỮ LIỆU HRM (TỪ ĐỐI SOÁT)', 'color: #0d6efd; font-weight: bold; font-size: 14px; padding: 4px;');
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

    // 2. Render Modal Chi Tiết Tiến Trình & Kết Quả
    const modalBody = document.getElementById('hrmSyncDetailModalBody');
    let stepsHtml = '';
    if (data.steps && data.steps.length > 0) {
      stepsHtml = `
        <div class="mt-3">
          <div class="fw-bold small mb-2 d-flex align-items-center justify-content-between">
            <span><span class="material-icons fs-6 align-middle">timeline</span> Chi tiết các bước thực hiện (${data.steps.length} bước):</span>
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

    if (modalBody) {
      if (data.success) {
        modalBody.innerHTML = `
          <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
            <span class="material-icons fs-4">check_circle</span>
            <div><strong>${escapeHtml(data.message)}</strong></div>
          </div>
          <div class="p-3 bg-light rounded border mb-2 small">
            <div class="fw-bold mb-1 text-primary">Tổng Hợp Kết Quả:</div>
            <div>• Tăng ca Kế hoạch: <strong>+${data.plan_result?.inserted_rows || 0}</strong> mới, <strong>${data.plan_result?.updated_rows || 0}</strong> cập nhật</div>
            <div>• Tăng ca Thực tế: <strong>+${data.actual_result?.inserted_rows || 0}</strong> mới, <strong>${data.actual_result?.updated_rows || 0}</strong> cập nhật</div>
            <div>• Tự động đối soát: <strong>${data.reconciliation?.total || 0}</strong> ca</div>
          </div>
          ${stepsHtml}
        `;
      } else {
        modalBody.innerHTML = `
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <span class="material-icons fs-4">error</span>
            <div><strong>Lỗi đồng bộ HRM:</strong> ${escapeHtml(data.message)}</div>
          </div>
          ${stepsHtml}
        `;
      }
      new bootstrap.Modal(document.getElementById('hrmSyncDetailModal')).show();
    }

    if (data.success) {
      await loadReconciliations(1);
    }
  } catch (err) {
    console.error('Lỗi triggerHrmSyncFromRec:', err);
    alert('Lỗi kết nối máy chủ: ' + err.message);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = oldHtml;
    }
  }
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
      alert(`Đối soát hoàn tất!\n- Tổng ca: ${data.summary.total}\n- Khớp hợp lệ: ${data.summary.matched}\n- Chờ hoàn thành thực tế: ${data.summary.plan_only}`);
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

// Format badge trạng thái duyệt 2 cấp trực quan
function formatApprovalStatusBadge(status) {
  if (!status) return '<span class="text-muted small">-</span>';
  const s = status.trim();
  if (s === 'Chưa duyệt') {
    return `<span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1" style="font-size: 11px;" title="Chờ cấp trên trực tiếp duyệt">
      <span class="material-icons align-middle" style="font-size: 13px;">hourglass_top</span> Chờ duyệt Cấp 1
    </span>`;
  }
  if (s === 'Chờ cấp trên gián tiếp' || s === 'Chờ cấp trên gian tiếp') {
    return `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle py-1" style="font-size: 11px;" title="Chờ quản lý cấp 2 duyệt">
      <span class="material-icons align-middle" style="font-size: 13px;">hourglass_bottom</span> Chờ QL Cấp 2
    </span>`;
  }
  if (s === 'Chấp Nhận' || s === 'Đã duyệt') {
    return `<span class="badge bg-success-subtle text-success border border-success-subtle py-1" style="font-size: 11px;">
      <span class="material-icons align-middle" style="font-size: 13px;">done</span> Đã duyệt
    </span>`;
  }
  if (s === 'Từ chối' || s === 'Không duyệt') {
    return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1" style="font-size: 11px;">
      <span class="material-icons align-middle" style="font-size: 13px;">close</span> Từ chối
    </span>`;
  }
  return `<span class="badge bg-light text-dark border" style="font-size: 11px;">${escapeHtml(s)}</span>`;
}

async function loadReconciliations(page = 1) {
  currentRecPage = page;
  const month = document.getElementById('recMonth').value;
  const year = document.getElementById('recYear').value;
  const otDate = document.getElementById('recOtDate').value;
  const search = document.getElementById('recSearchInput').value.trim();
  const hideCompleted = hideCompletedActive ? '1' : '0';
  const tbody = document.getElementById('recTableBody');

  tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách đối soát...</td></tr>';

  try {
    const url = `api/overtime_reconciliation.php?action=get_reconciliations&page=${page}&limit=25&month=${month}&year=${year}&ot_date=${encodeURIComponent(otDate)}&status=${encodeURIComponent(currentRecStatus)}&hide_completed=${hideCompleted}&search=${encodeURIComponent(search)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="11" class="text-danger text-center p-3">Lỗi: ${data.message}</td></tr>`;
      return;
    }

    // Cập nhật thông tin đồng bộ HRM
    if (data.hrm_sync) {
      updateHrmSyncDisplay(data.hrm_sync);
    }

    // Cập nhật bộ đếm Badges
    const b = data.badges || {};
    document.getElementById('badgeAll').textContent = b.total || 0;
    document.getElementById('badgeUncompletedActual').textContent = b.uncompleted_actual || 0;
    document.getElementById('badgeOverdue3Days').textContent = b.overdue_3days || 0;
    document.getElementById('badgeCompleted').textContent = b.completed || 0;
    document.getElementById('badgeNeedsExp').textContent = b.needs_explanation || 0;
    document.getElementById('badgeDismissed').textContent = b.dismissed_count || 0;
    document.getElementById('badgeExplained').textContent = b.explained_count || 0;

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted p-4"><span class="material-icons text-muted d-block fs-3 mb-1">filter_alt_off</span>Không tìm thấy lệnh tăng ca nào khớp với bộ lọc.</td></tr>';
      document.getElementById('recPaginationContainer').innerHTML = '';
      return;
    }

    let html = '';
    data.data.forEach((r) => {
      const isFullyCompleted = r.is_fully_completed;
      const isUncompletedActual = !r.step2_done || r.reconcile_status === 'plan_only';
      const isOverdue3Days = r.is_overdue_3days;
      const isDismissed = (r.is_dismissed == 1);
      const isExplained = (r.is_explained == 1);
      const isExpRequested = (r.explanation_requested == 1);

      // Định hình class hàng
      let rowClass = '';
      if (isDismissed) {
        rowClass = 'row-dismissed';
      } else if (isExplained) {
        rowClass = 'row-explained';
      } else if (isFullyCompleted) {
        rowClass = 'row-completed';
      } else if (isOverdue3Days) {
        rowClass = 'row-overdue-3days';
      } else if (isUncompletedActual) {
        rowClass = 'row-uncompleted-actual';
      }

      // Độ lệch
      let diffBadge = '-';
      if (r.diff_minutes > 0) {
        diffBadge = `<span class="badge bg-warning text-dark">+${r.diff_minutes}p</span>`;
      } else if (r.diff_minutes < 0) {
        diffBadge = `<span class="badge bg-danger">${r.diff_minutes}p</span>`;
      } else if (isFullyCompleted && r.diff_minutes === 0) {
        diffBadge = `<span class="badge bg-success">0p</span>`;
      }

      // Trạng thái 2 bước
      let stepProgressBadge = '';
      if (isFullyCompleted) {
        stepProgressBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle py-1" style="font-size: 11px;"><span class="material-icons align-middle" style="font-size: 13px;">check_circle</span> Đủ 2 bước</span>';
      } else if (r.step1_done && !r.step2_done) {
        stepProgressBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1" style="font-size: 11px;"><span class="material-icons align-middle" style="font-size: 13px;">pending</span> Chờ Bước 2 (TT)</span>';
      } else {
        stepProgressBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1" style="font-size: 11px;"><span class="material-icons align-middle" style="font-size: 13px;">warning</span> Thiếu Bước 1 (KH)</span>';
      }

      // Cột Trạng Thái Đối Soát
      let statusHtml = '';
      if (isDismissed) {
        statusHtml = `
          <span class="badge bg-secondary-subtle text-muted border py-1 px-2" title="Đã tạm hủy yêu cầu giải trình">
            <span class="material-icons align-middle" style="font-size: 13px;">block</span> Đã tạm hủy
          </span>
          <div class="text-muted small mt-1" style="font-size: 10px;" title="${escapeHtml(r.dismiss_reason || '')}">
            ${escapeHtml(r.dismiss_reason ? (r.dismiss_reason.length > 20 ? r.dismiss_reason.substr(0, 20) + '...' : r.dismiss_reason) : 'Bỏ qua')}
          </div>
        `;
      } else if (isExplained) {
        statusHtml = `
          <span class="badge badge-rec-explained py-1 px-2">
            <span class="material-icons align-middle" style="font-size: 13px;">done_all</span> Đã giải trình
          </span>
        `;
      } else if (isExpRequested) {
        statusHtml = `
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2 fw-bold" title="Admin đã gửi yêu cầu giải trình">
            <span class="material-icons align-middle" style="font-size: 13px;">campaign</span> Đã yêu cầu GT
          </span>
        `;
      } else if (isFullyCompleted) {
        statusHtml = `
          <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 fw-bold">
            <span class="material-icons align-middle" style="font-size: 13px;">check_circle</span> Hoàn thành
          </span>
        `;
      } else if (isOverdue3Days) {
        statusHtml = `
          <span class="badge badge-rec-overdue py-1 px-2 fw-bold" title="Ca tăng ca chưa hoàn thành đủ 2 bước và đã quá 3 ngày kể từ ngày tăng ca">
            <span class="material-icons align-middle" style="font-size: 13px;">warning</span> Quá hạn 3 ngày
          </span>
          <div class="text-danger small mt-1 fw-bold" style="font-size: 10px;">(${r.days_diff} ngày trước)</div>
        `;
      } else if (!r.step2_done) {
        statusHtml = `
          <span class="badge badge-rec-plan_only py-1 px-2">
            <span class="material-icons align-middle" style="font-size: 13px;">hourglass_top</span> Chờ Bước 2 (TT)
          </span>
          <div class="text-muted small mt-1" style="font-size: 10px;">(Còn ${Math.max(0, 3 - r.days_diff)}/3 ngày)</div>
        `;
      } else {
        statusHtml = `
          <span class="badge badge-rec-actual_only py-1 px-2">
            <span class="material-icons align-middle" style="font-size: 13px;">warning</span> Thiếu Bước 1 (KH)
          </span>
        `;
      }

      // Cụm Icon Buttons chức năng chuẩn UI/UX kèm Bootstrap Tooltip
      // 1. Xem chi tiết (Eye icon)
      let actionsHtml = `

      `;

        //       <button class="btn btn-sm btn-outline-primary p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
        //   onclick="openRecDetail(${r.id})" 
        //   data-bs-toggle="tooltip" data-bs-placement="top" title="Xem chi tiết đối chiếu" style="width: 28px; height: 28px;">
        //   <span class="material-icons" style="font-size: 15px;">visibility</span>
        // </button>

      // 2. Xem lịch sử tăng ca (History icon)
      // actionsHtml += `
      //   <button class="btn btn-sm btn-outline-secondary p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
      //     onclick="openEmpHistoryModal('${escapeHtml(r.employee_code)}', '${escapeHtml(r.full_name || '')}')" 
      //     data-bs-toggle="tooltip" data-bs-placement="top" title="Xem lịch sử tăng ca" style="width: 28px; height: 28px;">
      //     <span class="material-icons" style="font-size: 15px;">history</span>
      //   </button>
      // `;

      if (CAN_MANAGE_REC) {
        // 3. Đồng bộ lại / So khớp lại lệnh này (Refresh icon)
        // actionsHtml += `
        //   <button class="btn btn-sm btn-outline-info p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
        //     onclick="reconcileSingleRow(${r.id})" 
        //     data-bs-toggle="tooltip" data-bs-placement="top" title="So khớp lại ca này" style="width: 28px; height: 28px;">
        //     <span class="material-icons" style="font-size: 15px;">refresh</span>
        //   </button>
        // `;

        if (isDismissed) {
          // Khôi phục lại (Undo / Restore icon)
          // actionsHtml += `
          //   <button class="btn btn-sm btn-outline-success p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
          //     onclick="handleRestoreRequest(${r.id})" 
          //     data-bs-toggle="tooltip" data-bs-placement="top" title="Khôi phục lại lệnh" style="width: 28px; height: 28px;">
          //     <span class="material-icons" style="font-size: 15px;">undo</span>
          //   </button>
          // `;
        } else if (isExpRequested) {
          // Tạm hủy yêu cầu (Block / Cancel icon)
          actionsHtml += `
            <button class="btn btn-sm btn-outline-secondary p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
              onclick="openDismissModal(${r.id}, '${escapeHtml(r.employee_code)}', '${escapeHtml(r.full_name || '')}', '${r.ot_date}')" 
              data-bs-toggle="tooltip" data-bs-placement="top" title="Tạm hủy yêu cầu giải trình" style="width: 28px; height: 28px;">
              <span class="material-icons" style="font-size: 15px;">block</span>
            </button>
          `;
        } else if (!isFullyCompleted || r.diff_minutes !== 0 || r.is_overdue) {
          // Yêu cầu giải trình (Edit/Pencil icon)
          actionsHtml += `
            <button class="btn btn-sm btn-outline-warning p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
              onclick='openRequestExplanationModal(${JSON.stringify(r)})' 
              data-bs-toggle="tooltip" data-bs-placement="top" title="Yêu cầu giải trình" style="width: 28px; height: 28px;">
              <span class="material-icons" style="font-size: 15px;">edit_note</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
              onclick="openDismissModal(${r.id}, '${escapeHtml(r.employee_code)}', '${escapeHtml(r.full_name || '')}', '${r.ot_date}')" 
              data-bs-toggle="tooltip" data-bs-placement="top" title="Bỏ qua yêu cầu giải trình" style="width: 28px; height: 28px;">
              <span class="material-icons" style="font-size: 15px;">block</span>
            </button>
          `;
        }

        // Xóa lệnh (Trash icon)
        actionsHtml += `
          <button class="btn btn-sm btn-outline-danger p-1 rounded-circle d-inline-flex align-items-center justify-content-center" 
            onclick="deleteRecRow(${r.id}, '${escapeHtml(r.order_code)}')" 
            data-bs-toggle="tooltip" data-bs-placement="top" title="Xóa bản ghi đối soát" style="width: 28px; height: 28px;">
            <span class="material-icons" style="font-size: 15px;">delete_outline</span>
          </button>
        `;
      }

      html += `
        <tr class="${rowClass}">
          <!-- Mã Lệnh OT -->
          <td>
            <strong class="font-monospace text-primary">${r.order_code}</strong>
            ${isUncompletedActual && !isExplained && !isDismissed ? '<span class="badge bg-warning text-dark ms-1" style="font-size: 10px;" title="Cần hoàn thành thực tế">Nhắc</span>' : ''}
          </td>

          <!-- Nhân Viên -->
          <td>
            <strong>${escapeHtml(r.full_name || 'N/A')}</strong>
            <span class="badge bg-light text-dark font-monospace border ms-1">${escapeHtml(r.employee_code)}</span>
            <div class="text-muted" style="font-size: 11px;">${escapeHtml(r.team_name || r.group_name || '-')}</div>
          </td>

          <!-- Ngày OT -->
          <td><strong>${r.ot_date}</strong></td>

          <!-- Tiến Trình 2 Bước -->
          <td style="text-align: center;">
            ${stepProgressBadge}
          </td>

          <!-- Bước 1: Kế hoạch -->
          <td style="border-left: 2px solid var(--dx-primary);">
            ${r.plan_start_time ? `
              <div class="small fw-bold">${r.plan_start_time.substr(11, 5)} - ${r.plan_end_time.substr(11, 5)}</div>
              <div class="mt-1">${formatApprovalStatusBadge(r.plan_approval_status)}</div>
            ` : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Thiếu Kế hoạch</span>'}
          </td>
          <td style="text-align: right;"><strong>${r.plan_minutes}</strong></td>

          <!-- Bước 2: Thực tế -->
          <td style="border-left: 2px solid var(--dx-success);">
            ${r.actual_start_time ? `
              <div class="small fw-bold text-success">${r.actual_start_time.substr(11, 5)} - ${r.actual_end_time.substr(11, 5)}</div>
              <div class="mt-1">${formatApprovalStatusBadge(r.actual_approval_status)}</div>
            ` : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">Chưa có Thực tế</span>'}
          </td>
          <td style="text-align: right;"><strong>${r.actual_minutes}</strong></td>

          <!-- Chênh lệch -->
          <td style="text-align: right;">${diffBadge}</td>

          <!-- Trạng thái Đối Soát -->
          <td style="text-align: center;">
            ${statusHtml}
          </td>

          <!-- Thao tác -->
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
              ${actionsHtml}
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;

    // Kích hoạt Bootstrap tooltips cho tất cả icon buttons trong bảng
    const recTooltips = [].slice.call(document.querySelectorAll('#recTableBody [data-bs-toggle="tooltip"]'));
    recTooltips.map(el => new bootstrap.Tooltip(el));

    // Phân trang
    renderRecPagination(data.total, page, 25);

  } catch (err) {
    console.error('Lỗi loadReconciliations:', err);
    tbody.innerHTML = '<tr><td colspan="11" class="text-danger text-center p-3">Lỗi kết nối máy chủ</td></tr>';
  }
}

function renderRecPagination(total, curPage, limit) {
  const container = document.getElementById('recPaginationContainer');
  const totalPages = Math.ceil(total / limit);
  if (totalPages <= 1) {
    container.innerHTML = `<span class="small text-muted">Tổng cộng: <strong>${total}</strong> lệnh tăng ca</span>`;
    return;
  }

  let html = `<span class="small text-muted">Trang <strong>${curPage}</strong> / ${totalPages} (${total} lệnh tăng ca)</span><div class="btn-group btn-group-sm">`;
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

// -------------------------------------------------------------
// MODAL & ACTION HANDLERS
// -------------------------------------------------------------

// 1. Mở modal Yêu cầu giải trình chủ động
function openRequestExplanationModal(row) {
  document.getElementById('reqExpRecId').value = row.id;
  document.getElementById('reqExpOrderCode').textContent = row.order_code;
  document.getElementById('reqExpEmpName').textContent = row.full_name || '-';
  document.getElementById('reqExpEmpCode').textContent = row.employee_code;
  document.getElementById('reqExpOtDate').textContent = row.ot_date;
  document.getElementById('reqExpAdminNote').value = '';

  let defaultViolation = 'Yêu cầu giải trình vi phạm tăng ca';
  if (row.is_overdue_3days) {
    defaultViolation = 'Thiếu thực tế quá hạn 3 ngày';
  } else if (!row.step2_done) {
    defaultViolation = 'Có kế hoạch nhưng thiếu thực tế';
  } else if (!row.step1_done) {
    defaultViolation = 'Có thực tế nhưng thiếu kế hoạch';
  } else if (row.diff_minutes !== 0) {
    defaultViolation = `Chênh lệch giờ kế hoạch vs thực tế (${row.diff_minutes > 0 ? '+' : ''}${row.diff_minutes} phút)`;
  }
  document.getElementById('reqExpViolationType').value = defaultViolation;

  new bootstrap.Modal(document.getElementById('requestExplanationModal')).show();
}

function setReqExpSuggest(text) {
  document.getElementById('reqExpViolationType').value = text;
}

async function handleConfirmRequestExplanation(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitReqExp');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang gửi...';

  const recId = document.getElementById('reqExpRecId').value;
  const violationType = document.getElementById('reqExpViolationType').value.trim();
  const adminNote = document.getElementById('reqExpAdminNote').value.trim();

  try {
    const formData = new FormData();
    formData.append('action', 'request_explanation');
    formData.append('rec_id', recId);
    formData.append('violation_type', violationType);
    formData.append('admin_note', adminNote);

    const res = await fetch('api/overtime_reconciliation.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('requestExplanationModal')).hide();
      alert(data.message);
      await loadReconciliations(currentRecPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi handleConfirmRequestExplanation:', err);
    alert('Lỗi kết nối máy chủ');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">send</span> Gửi Yêu Cầu';
  }
}

// 2. Mở modal Tạm hủy
function openDismissModal(recId, empCode, empName, otDate) {
  document.getElementById('dismissRecId').value = recId;
  document.getElementById('dismissOrderCode').textContent = '#OT-' + String(recId).padStart(6, '0');
  document.getElementById('dismissEmpName').textContent = empName || '-';
  document.getElementById('dismissEmpCode').textContent = empCode || '-';
  document.getElementById('dismissOtDate').textContent = otDate || '-';
  document.getElementById('dismissReasonInput').value = 'Trưởng ca đã xác nhận hợp lệ';

  new bootstrap.Modal(document.getElementById('dismissModal')).show();
}

function setDismissSuggest(text) {
  document.getElementById('dismissReasonInput').value = text;
}

async function handleConfirmDismiss(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitDismiss');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

  const recId = document.getElementById('dismissRecId').value;
  const reason = document.getElementById('dismissReasonInput').value.trim();

  try {
    const formData = new FormData();
    formData.append('action', 'dismiss_request');
    formData.append('rec_id', recId);
    formData.append('reason', reason);

    const res = await fetch('api/overtime_reconciliation.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('dismissModal')).hide();
      alert(data.message);
      await loadReconciliations(currentRecPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi handleConfirmDismiss:', err);
    alert('Lỗi kết nối máy chủ');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons fs-6">check</span> Xác Nhận Tạm Hủy';
  }
}

// 3. Khôi phục lệnh đã tạm hủy
async function handleRestoreRequest(recId) {
  if (!confirm(`Bạn có chắc muốn khôi phục lệnh tăng ca #OT-${String(recId).padStart(6, '0')} về trạng thái đối soát ban đầu?`)) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append('action', 'restore_request');
    formData.append('rec_id', recId);

    const res = await fetch('api/overtime_reconciliation.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      alert(data.message);
      await loadReconciliations(currentRecPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi handleRestoreRequest:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

// 4. Mở chi tiết đối soát
async function openRecDetail(recId) {
  try {
    const res = await fetch(`api/overtime_reconciliation.php?action=get_detail&id=${recId}`);
    const data = await res.json();
    if (!data.success) return;

    const d = data.detail;
    const isDismissed = (d.is_dismissed == 1);
    const isExplained = (d.is_explained == 1);
    const isFullyCompleted = (d.step1_done && d.step2_done);

    document.getElementById('detailModalOrderCode').textContent = d.order_code;

    let statusBadgeHtml = '';
    if (isDismissed) {
      statusBadgeHtml = '<span class="badge bg-secondary px-3 py-2 fs-6">Đã tạm hủy</span>';
    } else if (isExplained) {
      statusBadgeHtml = '<span class="badge bg-secondary-subtle text-secondary px-3 py-2 fs-6 border">Đã giải trình</span>';
    } else if (isFullyCompleted) {
      statusBadgeHtml = '<span class="badge bg-success-subtle text-success px-3 py-2 fs-6 border border-success-subtle">Hoàn thành (Đủ 2 bước)</span>';
    } else {
      statusBadgeHtml = `<span class="badge bg-warning-subtle text-dark px-3 py-2 fs-6 border">${escapeHtml(d.reconcile_status)}</span>`;
    }

    const body = document.getElementById('recDetailBody');
    body.innerHTML = `
      <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-3 flex-wrap gap-2">
        <div>
          <h6 class="fw-bold m-0 text-primary">${escapeHtml(d.full_name)} (${d.employee_code})</h6>
          <div class="small text-muted mt-1">Mã lệnh: <strong>${d.order_code}</strong> | Tổ đội: <strong>${escapeHtml(d.team_name || d.group_name || '-')}</strong> | Ngày tăng ca: <strong>${d.ot_date}</strong></div>
        </div>
        <div class="text-end">
          ${statusBadgeHtml}
          ${isExplained ? `<div class="text-muted small mt-1">Đã giải trình bởi: <strong>${escapeHtml(d.explained_by || '-')}</strong> lúc ${d.explained_at}</div>` : ''}
          ${isDismissed ? `<div class="text-muted small mt-1">Tạm hủy bởi: <strong>${escapeHtml(d.dismissed_by || '-')}</strong> lúc ${d.dismissed_at} (${escapeHtml(d.dismiss_reason || '')})</div>` : ''}
        </div>
      </div>

      <div class="row g-3">
        <!-- Cột Kế Hoạch -->
        <div class="col-md-6">
          <div class="card p-3 border-primary-subtle h-100">
            <h6 class="fw-bold text-primary border-bottom pb-2 d-flex align-items-center justify-content-between">
              <span class="d-flex align-items-center gap-1"><span class="material-icons fs-6">event_available</span> Bước 1: Kế Hoạch (Plan)</span>
              ${d.step1_done ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Hoàn thành</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Thiếu</span>'}
            </h6>
            <div class="small mt-2" style="line-height: 1.8;">
              Bắt đầu: <strong>${d.plan_start || '<span class="text-danger">Không có</span>'}</strong><br/>
              Kết thúc: <strong>${d.plan_end || '<span class="text-danger">Không có</span>'}</strong><br/>
              Tổng phút: <strong>${d.plan_min || 0} phút</strong><br/>
              Cấp trên trực tiếp: <strong>${escapeHtml(d.plan_dm || '-')}</strong><br/>
              Cấp trên gián tiếp: <strong>${escapeHtml(d.plan_idm || '-')}</strong><br/>
              Lý do KH: <em>${escapeHtml(d.plan_reason || '-')}</em><br/>
              Trạng thái duyệt: ${formatApprovalStatusBadge(d.plan_status)}
            </div>
          </div>
        </div>

        <!-- Cột Thực Tế -->
        <div class="col-md-6">
          <div class="card p-3 border-success-subtle h-100">
            <h6 class="fw-bold text-success border-bottom pb-2 d-flex align-items-center justify-content-between">
              <span class="d-flex align-items-center gap-1"><span class="material-icons fs-6">done_all</span> Bước 2: Thực Tế (Actual)</span>
              ${d.step2_done ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Hoàn thành</span>' : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Chưa hoàn thành</span>'}
            </h6>
            <div class="small mt-2" style="line-height: 1.8;">
              Bắt đầu TT: <strong>${d.act_start || '<span class="text-danger">Không có</span>'}</strong><br/>
              Kết thúc TT: <strong>${d.act_end || '<span class="text-danger">Không có</span>'}</strong><br/>
              Tổng phút TT: <strong>${d.act_min || 0} phút</strong><br/>
              Cấp trên trực tiếp: <strong>${escapeHtml(d.act_dm || '-')}</strong><br/>
              Cấp trên gián tiếp: <strong>${escapeHtml(d.act_idm || '-')}</strong><br/>
              Lý do TT: <em>${escapeHtml(d.act_reason || '-')}</em><br/>
              Trạng thái duyệt: ${formatApprovalStatusBadge(d.act_status)}
            </div>
          </div>
        </div>

        <!-- Phần Giải Trình nếu có -->
        ${(d.needs_explanation || isExplained || d.exp_id || isDismissed) ? `
          <div class="col-12">
            <div class="card p-3 border-warning bg-light">
              <h6 class="fw-bold text-warning-emphasis d-flex align-items-center gap-1 m-0">
                <span class="material-icons fs-6">rate_review</span> Hồ Sơ Giải Trình & Trạng Thái Xử Lý
              </h6>
              <div class="small mt-2" style="line-height: 1.7;">
                Loại vi phạm: <strong class="text-danger">${escapeHtml(d.violation_type || '-')}</strong><br/>
                Trạng thái: <strong>${isDismissed ? '<span class="badge bg-secondary">Đã tạm hủy (Bỏ qua giải trình)</span>' : (isExplained ? '<span class="badge bg-secondary">Đã chuyển giải trình</span>' : (d.exp_status || 'Chờ gửi giải trình'))}</strong><br/>
                ${d.explanation_content ? `Nội dung giải trình: <em>${escapeHtml(d.explanation_content)}</em><br/>` : ''}
                ${d.explanation_note ? `Ghi chú xử lý: <em>${escapeHtml(d.explanation_note)}</em><br/>` : ''}
                ${d.dismiss_reason ? `Lý do tạm hủy: <em>${escapeHtml(d.dismiss_reason)}</em><br/>` : ''}
                ${d.approver_username ? `Người thẩm định: <strong>${escapeHtml(d.approver_username)}</strong> - Ý kiến: <em>${escapeHtml(d.approver_notes || '-')}</em>` : ''}
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

// 5. So khớp lại đơn lẻ một dòng
async function reconcileSingleRow(recId) {
  if (!confirm(`Bạn có chắc chắn muốn so khớp lại lệnh đối soát #OT-${String(recId).padStart(6, '0')}?`)) return;
  try {
    const res = await fetch(`api/overtime_reconciliation.php?action=reconcile_single&id=${recId}`, { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      alert(data.message || 'Đã so khớp lại thành công!');
      loadReconciliations(currentRecPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi reconcileSingleRow:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

// 6. Xóa bản ghi đối soát
async function deleteRecRow(recId, orderCode) {
  if (!confirm(`CẢNH BÁO: Bạn có chắc chắn muốn xóa bản ghi đối soát ${orderCode}? Thao tác này không thể hoàn tác!`)) return;
  try {
    const res = await fetch(`api/overtime_reconciliation.php?action=delete_reconciliation&id=${recId}`, { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      alert(data.message || 'Đã xóa bản ghi thành công!');
      loadReconciliations(currentRecPage);
    } else {
      alert('Lỗi: ' + data.message);
    }
  } catch (err) {
    console.error('Lỗi deleteRecRow:', err);
    alert('Lỗi kết nối máy chủ');
  }
}

// 7. Mở popup Lịch sử tăng ca của nhân viên
async function openEmpHistoryModal(empCode, empName) {
  const modalEl = document.getElementById('empHistoryModal');
  const titleEl = document.getElementById('modalEmpTitle');
  const summaryEl = document.getElementById('modalEmpSummaryCard');
  const bodyEl = document.getElementById('modalEmpHistoryBody');

  titleEl.textContent = `${empName} (${empCode})`;
  summaryEl.innerHTML = '<div class="text-center text-muted p-2"><div class="spinner-border spinner-border-sm me-2"></div>Đang tải dữ liệu...</div>';
  bodyEl.innerHTML = '<tr><td colspan="9" class="text-center text-muted p-3">Đang tải lịch sử...</td></tr>';

  const modalInstance = new bootstrap.Modal(modalEl);
  modalInstance.show();

  try {
    const year = document.getElementById('recYear').value || new Date().getFullYear();
    const res = await fetch(`api/overtime_yearly.php?action=get_employee_history&employee_code=${encodeURIComponent(empCode)}&year=${year}`);
    const data = await res.json();

    if (!data.success) {
      summaryEl.innerHTML = `<div class="text-danger">Lỗi: ${data.message}</div>`;
      bodyEl.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Không tải được dữ liệu</td></tr>`;
      return;
    }

    const emp = data.employee || {};
    const acc = data.accumulation || {};
    const totalHours = acc.total_hours_year || 0;
    const remainHours = acc.remaining_hours !== undefined ? acc.remaining_hours : Math.max(0, 200 - totalHours);
    const usagePct = acc.usage_percent !== undefined ? acc.usage_percent : (totalHours / 200 * 100).toFixed(1);
    const warnBadge = acc.warning_level === 'red' ? '<span class="badge bg-danger">Vượt giới hạn 200h</span>' : (acc.warning_level === 'yellow' ? '<span class="badge bg-warning text-dark">Cảnh báo (160h-200h)</span>' : '<span class="badge bg-success">An toàn (&lt;160h)</span>');

    summaryEl.innerHTML = `
      <div class="row g-2 align-items-center">
        <div class="col-md-4">
          <div class="fw-bold fs-6 text-primary">${escapeHtml(emp.full_name || empCode)}</div>
          <div class="small text-muted">Mã NV: <strong>${escapeHtml(emp.employee_code || empCode)}</strong> | Bộ phận: <strong>${escapeHtml(emp.cost_center || '-')}</strong></div>
        </div>
        <div class="col-md-5">
          <div class="d-flex justify-content-between small mb-1">
            <span>Tiến độ lũy kế năm ${year}: <strong>${totalHours}h / 200h</strong></span>
            <span><strong>${usagePct}%</strong></span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar ${acc.warning_level === 'red' ? 'bg-danger' : (acc.warning_level === 'yellow' ? 'bg-warning text-dark' : 'bg-success')}" style="width: ${Math.min(100, usagePct)}%"></div>
          </div>
        </div>
        <div class="col-md-3 text-md-end">
          <div>${warnBadge}</div>
          <div class="small text-muted mt-1">Còn lại: <strong class="text-success">${remainHours}h</strong></div>
        </div>
      </div>
    `;

    if (!data.history || data.history.length === 0) {
      bodyEl.innerHTML = `<tr><td colspan="9" class="text-center text-muted p-4"><span class="material-icons d-block fs-3 mb-1">event_busy</span>Không có bản ghi tăng ca thực tế nào trong năm ${year}.</td></tr>`;
      return;
    }

    let rowsHtml = '';
    data.history.forEach((h, idx) => {
      const startTime = h.start_time_actual ? h.start_time_actual.substr(11, 5) : '-';
      const endTime = h.end_time_actual ? h.end_time_actual.substr(11, 5) : '-';
      const hours = (parseFloat(h.duration_hours_actual) || (h.duration_minutes_actual / 60)).toFixed(2);
      
      let stBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">Hợp lệ</span>';
      if (h.needs_explanation == 1) {
        stBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Cần GT</span>';
      }
      if (h.explanation_status === 'approved') {
        stBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle">Đã duyệt GT</span>';
      }

      rowsHtml += `
        <tr>
          <td style="text-align: center;">${idx + 1}</td>
          <td><strong>${h.ot_date}</strong></td>
          <td class="font-monospace text-primary">${startTime}</td>
          <td class="font-monospace text-primary">${endTime}</td>
          <td style="text-align: right;">${h.duration_minutes_actual || 0} p</td>
          <td style="text-align: right;" class="fw-bold text-success">${hours} h</td>
          <td><small class="text-muted">${escapeHtml(h.reason || '-')}</small></td>
          <td><small>${escapeHtml(h.direct_manager_name || h.indirect_manager_name || '-')}</small></td>
          <td style="text-align: center;">${stBadge}</td>
        </tr>
      `;
    });
    bodyEl.innerHTML = rowsHtml;
  } catch (err) {
    console.error('Lỗi openEmpHistoryModal:', err);
    summaryEl.innerHTML = '<div class="text-danger">Lỗi kết nối máy chủ</div>';
    bodyEl.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Lỗi kết nối máy chủ</td></tr>';
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
