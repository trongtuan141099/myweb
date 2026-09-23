<?php
/**
 * Module: Trung Tâm Xuất Báo Cáo Excel (Export Center)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();

$currentYear = intval(date('Y'));
$currentMonth = intval(date('m'));
?>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">file_download</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">TRUNG TÂM XUẤT BÁO CÁO TĂNG CA (EXPORT CENTER)</h1>
        <p class="text-muted small mb-0">Xuất 9 loại báo cáo Excel/CSV chuẩn công nghiệp phục vụ kế toán lương, thanh kiểm tra và quản trị</p>
      </div>
    </div>
  </div>

  <!-- 2. Thanh Điều Khiển Bộ Lọc Chung -->
  <div class="app-filter-card mb-4">
    <div class="row g-3 align-items-center w-100">
      <div class="col-md-3">
        <label class="form-label small text-muted fw-bold m-0 mb-1">Năm Báo Cáo</label>
        <select class="app-form-select app-form-select-sm" id="exportYear" onchange="updateExportLinks()">
          <option value="2026" selected>Năm 2026</option>
          <option value="2025">Năm 2025</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted fw-bold m-0 mb-1">Kỳ Tháng</label>
        <select class="app-form-select app-form-select-sm" id="exportMonth" onchange="updateExportLinks()">
          <option value="0">-- Tất cả 12 tháng --</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m === 9) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-6 text-end">
        <span class="badge bg-light text-muted border p-2 small">
          <span class="material-icons fs-6 align-middle text-success">verified</span>
          Tự động nhúng UTF-8 BOM hiển thị tiếng Việt chuẩn 100% trên MS Excel
        </span>
      </div>
    </div>
  </div>

  <!-- 3. Grid 9 Loại Báo Cáo Excel -->
  <div class="row g-3">
    <!-- Báo cáo 1 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Mẫu 01</span>
            <span class="material-icons text-primary fs-4">calendar_month</span>
          </div>
          <h6 class="fw-bold mb-1">1. Danh Sách Tăng Ca Kế Hoạch</h6>
          <p class="text-muted small mb-3">Toàn bộ danh sách đăng ký tăng ca kế hoạch đã được phê duyệt từ hệ thống HRM.</p>
        </div>
        <a href="api/overtime_export.php?type=plan&year=2026&month=9" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="plan">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 2 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Mẫu 02</span>
            <span class="material-icons text-success fs-4">done_all</span>
          </div>
          <h6 class="fw-bold mb-1">2. Danh Sách Tăng Ca Thực Tế</h6>
          <p class="text-muted small mb-3">Dữ liệu chấm công tăng ca thực tế sau khi hoàn thành ca làm việc đã duyệt.</p>
        </div>
        <a href="api/overtime_export.php?type=actual&year=2026&month=9" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="actual">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 3 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between border-primary">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-primary text-white px-2 py-1">Mẫu 03 (Quan trọng)</span>
            <span class="material-icons text-primary fs-4">fact_check</span>
          </div>
          <h6 class="fw-bold mb-1 text-primary">3. Bảng Đối Soát Tăng Ca Tổng Hợp</h6>
          <p class="text-muted small mb-3">Đối chiếu song song 2 cột Kế Hoạch vs Thực Tế, chênh lệch số phút và trạng thái.</p>
        </div>
        <a href="api/overtime_export.php?type=reconciliation&year=2026&month=9" class="app-btn app-btn-primary w-100 justify-content-center export-btn-link" data-type="reconciliation">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 4 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">Mẫu 04</span>
            <span class="material-icons text-warning fs-4">report_problem</span>
          </div>
          <h6 class="fw-bold mb-1">4. Danh Sách Các Ca Cần Giải Trình</h6>
          <p class="text-muted small mb-3">Tập hợp các ca lệch giờ, thiếu kế hoạch, thiếu thực tế hoặc duyệt quá 3 ngày.</p>
        </div>
        <a href="api/overtime_export.php?type=need_explanation&year=2026&month=9" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="need_explanation">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 5 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-info-subtle text-info border border-info px-2 py-1">Mẫu 05</span>
            <span class="material-icons text-info fs-4">rate_review</span>
          </div>
          <h6 class="fw-bold mb-1">5. Báo Cáo Giải Trình Chi Tiết</h6>
          <p class="text-muted small mb-3">Chi tiết nội dung nhân viên giải trình, cấp thẩm quyền phê duyệt và ý kiến thẩm định.</p>
        </div>
        <a href="api/overtime_export.php?type=explanation_report&year=2026&month=9" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="explanation_report">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 6 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">Mẫu 06</span>
            <span class="material-icons text-secondary fs-4">view_week</span>
          </div>
          <h6 class="fw-bold mb-1">6. Tổng Hợp Tăng Ca Theo Tháng (Pivot)</h6>
          <p class="text-muted small mb-3">Bảng Pivot ma trận 12 tháng (T1 đến T12) của từng nhân viên và tổng giờ năm.</p>
        </div>
        <a href="api/overtime_export.php?type=monthly_summary&year=2026" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="monthly_summary">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 7 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-light text-dark border px-2 py-1">Mẫu 07</span>
            <span class="material-icons text-dark fs-4">summarize</span>
          </div>
          <h6 class="fw-bold mb-1">7. Tổng Hợp Giờ Tăng Ca Theo Năm</h6>
          <p class="text-muted small mb-3">Tổng hợp số giờ đã làm, số giờ còn lại trước khi chạm trần 200 giờ và % sử dụng.</p>
        </div>
        <a href="api/overtime_export.php?type=yearly_summary&year=2026" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="yearly_summary">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>

    <!-- Báo cáo 8 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between border-danger">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-danger text-white px-2 py-1">Mẫu 08 (Cảnh Báo Pháp Lý)</span>
            <span class="material-icons text-danger fs-4">alarm_on</span>
          </div>
          <h6 class="fw-bold mb-1 text-danger">8. Danh Sách Cảnh Báo Vượt Giới Hạn 200 Giờ</h6>
          <p class="text-muted small mb-3">Danh sách lọc riêng các nhân sự Mức Vàng (160-199h) và Mức Đỏ (&ge; 200h).</p>
        </div>
        <a href="api/overtime_export.php?type=warning_200h&year=2026" class="app-btn app-btn-danger w-100 justify-content-center export-btn-link text-white" data-type="warning_200h">
          <span class="material-icons fs-6">download</span> Tải Xuống DS Cảnh Báo
        </a>
      </div>
    </div>

    <!-- Báo cáo 9 -->
    <div class="col-12 col-md-6 col-xl-4">
      <div class="app-card p-3 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-light text-dark border px-2 py-1">Mẫu 09</span>
            <span class="material-icons text-dark fs-4">dashboard_customize</span>
          </div>
          <h6 class="fw-bold mb-1">9. Báo Cáo Dashboard Summary</h6>
          <p class="text-muted small mb-3">Tóm tắt các chỉ số quản trị điều hành, tỷ lệ sai lệch đối soát và top giờ OT cao.</p>
        </div>
        <a href="api/overtime_export.php?type=dashboard_summary&year=2026" class="app-btn app-btn-secondary w-100 justify-content-center export-btn-link" data-type="dashboard_summary">
          <span class="material-icons fs-6">download</span> Tải Xuống Excel
        </a>
      </div>
    </div>
  </div>
</div>

<script>
function updateExportLinks() {
  const year = document.getElementById('exportYear').value;
  const month = document.getElementById('exportMonth').value;

  document.querySelectorAll('.export-btn-link').forEach(btn => {
    const type = btn.getAttribute('data-type');
    btn.href = `api/overtime_export.php?type=${type}&year=${year}&month=${month}`;
  });
}
</script>

