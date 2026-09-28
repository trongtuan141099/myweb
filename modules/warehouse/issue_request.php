<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Màn Hình Tạo Phiếu Yêu Cầu & Danh Sách Phiếu
 * DX Plastic Group - Factory Management System
 */

$currentMonth = intval(date('m'));
$currentYear  = intval(date('Y'));
$userRole     = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');
$canCreate    = hasPermission(['warehouse.create', 'warehouse.manage', 'admin']);
$canApproveAny = hasPermission(['warehouse.check', 'warehouse.approve', 'warehouse.issue', 'warehouse.handover', 'admin']);

// Gộp màn hình: chuyển hướng tab=list sang màn hình Quản lý & Duyệt phiếu tập trung
if (isset($_GET['tab']) && $_GET['tab'] === 'list') {
    header("Location: index.php?mainpage=warehouse&subpage=approval");
    exit;
}
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang Chuẩn Công Nghiệp -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">post_add</span>
        <span>YÊU CẦU XUẤT VẬT TƯ & THEO DÕI ĐỊNH MỨC</span>
      </h1>
      <p class="app-page-subtitle">Tạo phiếu xuất tiêu hao theo định mức / bất thường, tham khảo lịch sử 3 tháng, kiểm soát tồn kho khả dụng và cảnh báo điểm đặt hàng (ROP)</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php?mainpage=warehouse&subpage=dashboard" class="app-btn app-btn-outline">
        <span class="material-icons">dashboard</span>
        <span>Dashboard Kho</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-secondary">
        <span class="material-icons">verified_user</span>
        <span>Quy trình phê duyệt</span>
        <span class="material-icons">fact_check</span>
        <span>Quản Lý & Duyệt Phiếu</span>
      </a>
      <?php if ($canCreate): ?>
      <button class="app-btn app-btn-primary" type="button" onclick="switchTab('create')">
        <span class="material-icons">add_circle</span>
        <span>Tạo Phiếu Xuất Mới</span>
      </button>
      <?php endif; ?>
      <a href="index.php?mainpage=warehouse&subpage=materials" class="app-btn app-btn-outline">
        <span class="material-icons">category</span>
        <span>Danh Mục Vật Tư</span>
      </a>
    </div>
  </div>

  <!-- Tabs Điều Hướng: Tạo phiếu vs Danh sách phiếu -->
  <!-- Thanh Điều Hướng Nhanh -->
  <div class="leave-main-tabs mb-3" style="display: flex; gap: 8px; border-bottom: 2px solid var(--dx-border, #e2e8f0); padding-bottom: 8px;">
    <button class="app-btn active" id="tabBtnCreate" onclick="switchTab('create')" style="font-weight: 600;">
    <button class="app-btn active" id="tabBtnCreate" style="font-weight: 600;">
      <span class="material-icons">edit_note</span>
      <span>1. Tạo Phiếu Yêu Cầu Xuất Kho</span>
    </button>
    <button class="app-btn app-btn-outline" id="tabBtnList" onclick="switchTab('list')" style="font-weight: 600;">
      <span class="material-icons">list_alt</span>
      <span>2. Danh Sách Phiếu Đã Tạo (<span id="countMyIssues">0</span>)</span>
    </button>
    <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-outline" style="font-weight: 600;">
      <span class="material-icons">fact_check</span>
      <span>2. Quản Lý & Duyệt Phiếu Tập Trung</span>
    </a>
  </div>

  <!-- =========================================================================
       TAB 1: MÀN HÌNH TẠO PHIẾU YÊU CẦU THÔNG MINH
       ========================================================================= -->
  <div id="tabContentCreate">
    <div class="app-card p-3 mb-3 border">
      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
        <h5 class="fw-bold mb-0 text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">tune</span>
          <span>Thông Tin Phiếu Yêu Cầu & Thiết Lập Nhóm Làm Việc</span>
        </h5>
        <span class="badge bg-primary-subtle text-primary border px-3 py-1 font-monospace" id="newIssueCodePreview">
          PXK-<?= date('Ym') ?>-MỚI
        </span>
      </div>

      <div class="row g-3">
        <!-- 1. Chọn Nhóm làm việc -->
        <div class="col-md-3">
          <label class="form-label small fw-bold text-main">Nhóm công việc: <span class="text-danger">*</span></label>
          <select id="reqGroupSelect" class="form-select form-select-sm" onchange="onGroupChanged()">
            <option value="Thiết bị" selected>Nhóm Thiết bị</option>
            <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
            <option value="Sản xuất">Nhóm Sản xuất</option>
            <option value="Nghiền">Nhóm Nghiền</option>
          </select>
          <small class="text-muted" style="font-size: 11px;">Chọn nhóm, sau đó bấm nút "Đề xuất" bên dưới để nạp vật tư</small>
        </div>

        <!-- 2. Loại phiếu -->
        <div class="col-md-3">
          <label class="form-label small fw-bold text-main">Phân loại xuất kho: <span class="text-danger">*</span></label>
          <select id="reqIssueType" class="form-select form-select-sm" onchange="onIssueTypeChanged()">
            <option value="consumable" selected>Vật tư tiêu hao (Theo định mức máy/người)</option>
            <option value="irregular">Vật tư bất thường (Sự cố / Đột xuất / Không định mức)</option>
          </select>
          <small class="text-muted" style="font-size: 11px;">Vật tư bất thường bắt buộc ghi rõ lý do và mục đích</small>
        </div>

        <!-- 3. Tháng / Năm xuất -->
        <div class="col-md-2">
          <label class="form-label small fw-bold text-main">Kỳ xuất (Tháng/Năm):</label>
          <div class="d-flex gap-1">
            <select id="reqMonth" class="form-select form-select-sm" style="width: 90px;" onchange="onPeriodChanged()">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($m === $currentMonth) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
              <?php endfor; ?>
            </select>
            <select id="reqYear" class="form-select form-select-sm" style="width: 80px;" onchange="onPeriodChanged()">
              <option value="<?= $currentYear ?>" selected><?= $currentYear ?></option>
              <option value="<?= $currentYear - 1 ?>"><?= $currentYear - 1 ?></option>
            </select>
          </div>
        </div>

        <!-- 4. Mục đích xuất -->
        <div class="col-md-4">
          <label class="form-label small fw-bold text-main">Mục đích xuất kho: <span class="text-danger">*</span></label>
          <input type="text" id="reqPurpose" class="form-control form-control-sm" placeholder="Ví dụ: Xuất vật tư tiêu hao định kỳ tháng <?= $currentMonth ?>" value="Xuất vật tư tiêu hao định kỳ tháng <?= $currentMonth ?>">
        </div>

        <!-- Trường bắt buộc cho vật tư bất thường -->
        <div class="col-12" id="irregularReasonWrapper" style="display: none;">
          <div class="p-3 rounded border border-warning" style="background: #fffbeb;">
            <label class="form-label small fw-bold text-danger d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 16px;">warning</span>
              <span>Lý do & Mục đích xuất vật tư bất thường: (Bắt buộc)</span>
            </label>
            <input type="text" id="reqIrregularReason" class="form-control form-control-sm" placeholder="Ghi rõ: Mã máy/line xảy ra sự cố, mã lỗi thiết bị, lý do thay thế khẩn cấp...">
          </div>
        </div>
      </div>
    </div>

    <!-- Thanh Công Cụ Tinh Gọn (Gộp Tìm Kiếm, Đề Xuất, Tùy Biến, Xóa Hết) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 mb-2 rounded bg-light border">
      <div class="d-flex align-items-center gap-2">
        <button class="app-btn app-btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" type="button" onclick="openProductSearchModal()">
          <span class="material-icons" style="font-size: 16px;">search</span>
          <span>Tìm & Chọn Vật Tư</span>
        </button>
        <button class="app-btn app-btn-success btn-sm d-flex align-items-center gap-1 shadow-sm" type="button" onclick="triggerGroupSuggestions()" id="btnTriggerSuggest" title="Chủ động nạp danh sách vật tư đề xuất chuẩn theo nhóm">
          <span class="material-icons" style="font-size: 16px;">auto_awesome</span>
          <strong>Đề Xuất Vật Tư Theo Nhóm</strong>
        </button>
        <button class="app-btn app-btn-outline btn-sm d-flex align-items-center gap-1" type="button" onclick="openCustomItemModal()" title="Thêm mặt hàng tùy biến ngoài danh mục">
          <span class="material-icons" style="font-size: 16px;">add</span>
          <span>Thêm Tùy Biến</span>
        </button>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="app-btn app-btn-outline btn-sm d-flex align-items-center gap-1 text-primary border-primary" type="button" onclick="openPdfPreviewModal()" title="Xem trước bản in A4 PDF có 5 dấu ký">
          <span class="material-icons" style="font-size: 16px;">picture_as_pdf</span>
          <span>Xem Trước PDF (A4)</span>
        </button>
        <button class="app-btn app-btn-outline btn-sm d-flex align-items-center gap-1 text-danger border-danger" type="button" onclick="clearAllItems()" title="Xóa toàn bộ dòng hiện tại">
          <span class="material-icons" style="font-size: 16px;">delete_sweep</span>
          <span>Xóa Hết</span>
        </button>
      </div>
    </div>

    <!-- BẢNG MA TRẬN TÍNH TOÁN XUẤT VẬT TƯ (DESKTOP OPTIMIZED) -->
    <div class="app-card p-0 mb-4 border overflow-hidden">
      <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
        <table class="table table-bordered table-hover align-middle mb-0" id="issueItemsTable" style="font-size: 12px;">
          <thead class="table-light text-center sticky-top" style="z-index: 20; background: #f8fafc;">
            <tr>
              <th style="width: 35px;" rowspan="2">STT</th>
              <th style="width: 55px;" rowspan="2">Ảnh</th>
              <th style="min-width: 140px; text-align: left;" rowspan="2">Mã VT / Tên Vật Tư</th>
              <th style="width: 80px;" rowspan="2">Kệ (BIN)</th>
              <th style="width: 50px;" rowspan="2">ĐVT</th>
              <th style="min-width: 135px;" rowspan="2" class="text-center" title="Số lượng đã xuất của 3 tháng gần nhất (T5, T6, T7) & Tiêu hao TB">
                LỊCH SỬ 3 THÁNG<br><small class="text-muted fw-normal">(T5 | T6 | T7 | TB)</small>
              </th>
              
              <!-- Cột cần nhập dữ liệu (Tô màu cam vàng) -->
              <th colspan="3" class="th-input-data text-warning-emphasis fw-bold">
                <span class="material-icons align-middle" style="font-size: 14px;">edit_note</span> CỘT CẦN NHẬP DỮ LIỆU
              </th>
              
              <!-- Cột tự động tính (Tô màu xanh dương) -->
              <th colspan="2" class="th-auto-calc text-primary fw-bold">
                <span class="material-icons align-middle" style="font-size: 14px;">calculate</span> TỰ ĐỘNG TÍNH TOÁN
              </th>

              <!-- Cột tồn hiện trường cần nhập -->
              <th colspan="2" class="th-input-data text-warning-emphasis fw-bold">
                <span class="material-icons align-middle" style="font-size: 14px;">inventory</span> TỒN HIỆN TRƯỜNG
              </th>

              <!-- Cột kết quả xuất thực tế & tồn sau xuất (Tự động) -->
              <th colspan="5" class="th-auto-calc text-primary fw-bold">
                <span class="material-icons align-middle" style="font-size: 14px;">verified</span> QUY ĐỔI ĐÓNG GÓI & TỒN KHO KHẢ DỤNG
              </th>

              <th style="width: 75px;" rowspan="2">Thao tác</th>
            </tr>
            <tr>
              <!-- Sub-headers Input -->
              <th style="width: 85px;" class="th-input-data text-warning-emphasis" title="Số lượng máy hoặc người dùng (A) - Định dạng số nguyên">
                Số máy (A)<br><span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 9px;">Số nguyên</span>
              </th>
              <th style="width: 85px;" class="th-input-data text-warning-emphasis" title="Số lần sử dụng / máy (B) - Định dạng số nguyên">
                Số lần (B)<br><span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 9px;">Số nguyên</span>
              </th>
              <th style="width: 80px;" class="th-input-data text-warning-emphasis" title="Định mức trên lần (C)">Định mức (C)</th>

              <!-- Sub-headers Auto Calc -->
              <th style="width: 85px;" class="th-auto-calc text-primary" title="Tổng số lần = A x B (Số nguyên)">
                Tổng lần (AxB)<br><span class="badge bg-primary-subtle text-primary border px-1" style="font-size: 9px;">Số nguyên</span>
              </th>
              <th style="width: 85px;" class="th-auto-calc text-primary fw-bold" title="Tổng số lượng lý thuyết = A x B x C">Tổng LT (AxBxC)</th>

              <!-- Sub-headers Input Tồn HT -->
              <th style="width: 80px;" class="th-input-data text-warning-emphasis" title="Vật tư còn dư tại hiện trường">Tồn HT</th>
              <th style="width: 80px;" class="th-input-data text-warning-emphasis" title="Tồn tái sử dụng">Tái SD</th>

              <!-- Sub-headers Auto Calc Result -->
              <th style="width: 85px;" class="th-auto-calc text-primary" title="Thực xuất lý thuyết = Tổng LT - Tồn HT - Tái SD">Thực xuất LT</th>
              <th style="min-width: 100px;">Quy cách đóng gói</th>
              <th style="width: 95px;" class="th-auto-calc table-primary text-primary fw-bold" title="Số lượng xuất thực tế tự động quy đổi làm tròn lên theo bao gói">Xuất Thực Tế</th>
              <th style="width: 85px;" class="th-auto-calc" title="Tồn còn lại sau xuất = Tồn hiện tại - Xuất thực tế">Tồn sau xuất</th>
              <th style="width: 85px;" class="th-auto-calc" title="Số tháng tồn kho khả dụng = Tồn sau xuất / TB 3 tháng">Tháng tồn</th>
            </tr>
          </thead>
          <tbody id="issueItemsTableBody">
            <!-- Nội dung nạp động từ JS -->
          </tbody>
        </table>
      </div>

      <!-- Footer Tổng Hợp Phiếu & Nút Tạo Phiếu -->
      <div class="p-3 bg-light border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="badge bg-secondary px-3 py-2 fs-7">
            Tổng mặt hàng: <strong id="summaryTotalItems" class="fs-6 text-white ms-1">0</strong>
          </div>
          <div class="badge bg-primary px-3 py-2 fs-7">
            Tổng SL xuất thực tế: <strong id="summaryTotalActualQty" class="fs-6 text-white ms-1">0.0</strong>
          </div>
          <div class="badge bg-danger-subtle text-danger border px-3 py-2 fs-7 d-none align-items-center gap-1" id="summaryRopBadge">
            <span class="material-icons fs-6">warning</span>
            <span>Có <strong id="summaryRopCount">0</strong> mặt hàng dưới điểm đặt hàng (ROP)</span>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button class="app-btn app-btn-outline" type="button" onclick="switchTab('list')">
            <span class="material-icons">arrow_back</span>
            <span>Hủy bỏ</span>
          </button>
          <button class="app-btn app-btn-success" type="button" id="btnSubmitIssue" onclick="submitCreateIssue()" style="font-weight: 700; padding: 8px 24px;">
            <span class="material-icons">send</span>
            <span>GỬI PHIẾU YÊU CẦU XUẤT KHO</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       TAB 2: DANH SÁCH CÁC PHIẾU YÊU CẦU ĐÃ TẠO
       ========================================================================= -->
  <div id="tabContentList" style="display: none;">
    <!-- Pipeline KPI Summary Cards -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border" onclick="filterByPipelineStatus('ALL')" style="cursor: pointer;">
          <div class="small text-muted fw-bold">Tổng Số Phiếu</div>
          <div class="fs-5 fw-bold text-main" id="kpiTotalIssues">0</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border border-warning-subtle bg-warning-subtle text-dark" onclick="filterByPipelineStatus('pending_checker')" style="cursor: pointer;">
          <div class="small fw-bold">1. Chờ Kiểm Tra</div>
          <div class="fs-5 fw-bold text-warning-emphasis" id="kpiPendingChecker">0</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border text-white" onclick="filterByPipelineStatus('pending_manager')" style="background:#8b5cf6; cursor: pointer;">
          <div class="small fw-bold">2. Chờ Quản Lý</div>
          <div class="fs-5 fw-bold" id="kpiPendingManager">0</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border bg-info-subtle text-dark" onclick="filterByPipelineStatus('pending_admin_issue')" style="cursor: pointer;">
          <div class="small fw-bold">3. Chờ Xuất Kho</div>
          <div class="fs-5 fw-bold text-info-emphasis" id="kpiPendingAdminIssue">0</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border bg-primary-subtle text-primary" onclick="filterByPipelineStatus('pending_handover')" style="cursor: pointer;">
          <div class="small fw-bold">4. Chờ Bàn Giao</div>
          <div class="fs-5 fw-bold" id="kpiPendingHandover">0</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card p-2 text-center border bg-success-subtle text-success" onclick="filterByPipelineStatus('completed')" style="cursor: pointer;">
          <div class="small fw-bold">5. Hoàn Tất</div>
          <div class="fs-5 fw-bold" id="kpiCompleted">0</div>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="app-card p-3 mb-3 border">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" id="filterListSearch" class="form-control form-control-sm" placeholder="Tìm theo mã phiếu, người tạo, mục đích..." onkeyup="if(event.key==='Enter') loadIssuesList(1)">
        </div>
        <div class="col-md-2">
          <select id="filterListGroup" class="form-select form-select-sm" onchange="loadIssuesList(1)">
            <option value="ALL">-- Tất cả nhóm --</option>
            <option value="Thiết bị">Nhóm Thiết bị</option>
            <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
            <option value="Sản xuất">Nhóm Sản xuất</option>
            <option value="Nghiền">Nhóm Nghiền</option>
          </select>
        </div>
        <div class="col-md-2">
          <select id="filterListStatus" class="form-select form-select-sm" onchange="loadIssuesList(1)">
            <option value="ALL">-- Tất cả trạng thái --</option>
            <option value="pending_checker">1. Chờ kiểm tra duyệt</option>
            <option value="pending_manager">2. Chờ quản lý duyệt</option>
            <option value="pending_admin_issue">3. Chờ Admin xuất kho</option>
            <option value="pending_handover">4. Chờ bàn giao hiện trường</option>
            <option value="completed">5. Đã hoàn tất</option>
            <option value="rejected">Từ chối</option>
            <option value="cancelled">Đã hủy</option>
          </select>
        </div>
        <div class="col-md-2">
          <select id="filterListMonth" class="form-select form-select-sm" onchange="loadIssuesList(1)">
            <option value="0" selected>-- Tất cả tháng --</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>">Tháng <?= $m ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-3 text-end">
          <button class="app-btn app-btn-primary app-btn-sm" type="button" onclick="loadIssuesList(1)">
            <span class="material-icons">filter_alt</span> Lọc Dữ Liệu
          </button>
          <button class="app-btn app-btn-outline app-btn-sm" type="button" onclick="resetListFilter()">
            <span class="material-icons">restart_alt</span> Làm Mới
          </button>
        </div>
      </div>
    </div>

    <!-- Bảng danh sách phiếu xuất kho -->
    <div class="app-card p-0 border overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
          <thead class="table-light">
            <tr>
              <th style="width: 45px; text-align: center;">STT</th>
              <th style="min-width: 140px;">Mã Phiếu</th>
              <th style="width: 120px;">Nhóm</th>
              <th style="width: 130px;">Phân Loại</th>
              <th style="width: 90px; text-align: center;">Kỳ Xuất</th>
              <th style="min-width: 180px;">Mục Đích Xuất</th>
              <th style="width: 80px; text-align: center;">Mặt Hàng</th>
              <th style="width: 100px; text-align: right;">Tổng Thực Xuất</th>
              <th style="min-width: 160px; text-align: center;">Trạng Thái Quy Trình</th>
              <th style="min-width: 140px;">Người Tạo / Ngày</th>
              <th style="width: 130px; text-center;">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="issuesTableBody">
            <tr>
              <td colspan="11" class="text-center py-4 text-muted">Đang tải danh sách phiếu xuất kho...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Phân trang -->
      <div class="p-3 border-top d-flex align-items-center justify-content-between" id="issuesPaginationWrapper">
        <div class="small text-muted" id="issuesPageInfo">Hiển thị 0 phiếu</div>
        <div class="d-flex gap-1" id="issuesPageBtns"></div>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL TÌM KIẾM & CHỌN VẬT TƯ (MODAL TÌM KIẾM SẢN PHẨM RIÊNG BIỆT)
     ========================================================================= -->
<div class="modal fade" id="modalProductSearch" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">search</span>
          <span>TÌM KIẾM & CHỌN VẬT TƯ THÊM VÀO PHIẾU XUẤT</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <!-- Bộ lọc tìm kiếm nhanh trong modal -->
        <div class="row g-2 mb-3 align-items-center bg-light p-2 rounded border">
          <div class="col-md-5">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><span class="material-icons text-primary" style="font-size: 16px;">search</span></span>
              <input type="text" id="mSearchKeyword" class="form-control" placeholder="Tìm theo mã VT, mã SAP, tên vật tư, vị trí kệ BIN..." onkeyup="filterModalProducts()">
            </div>
          </div>
          <div class="col-md-3">
            <select id="mSearchGroup" class="form-select form-select-sm" onchange="filterModalProducts()">
              <option value="ALL">-- Tất cả nhóm --</option>
              <option value="Thiết bị">Nhóm Thiết bị</option>
              <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
              <option value="Sản xuất">Nhóm Sản xuất</option>
              <option value="Nghiền">Nhóm Nghiền</option>
            </select>
          </div>
          <div class="col-md-4 text-end text-muted small">
            <span>Tìm thấy: <strong id="mSearchCount" class="text-primary">0</strong> vật tư khả dụng</span>
          </div>
        </div>

        <!-- Bảng danh sách vật tư để chọn -->
        <div class="table-responsive border rounded" style="max-height: 450px;">
          <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
            <thead class="table-light sticky-top">
              <tr>
                <th style="width: 40px;" class="text-center">STT</th>
                <th style="width: 55px;" class="text-center">Ảnh</th>
                <th style="width: 140px;">Mã VT / SAP</th>
                <th>Tên Vật Tư & Quy Cách</th>
                <th style="width: 110px;">Nhóm</th>
                <th style="width: 80px;" class="text-center">Kệ BIN</th>
                <th style="width: 100px;" class="text-end">Tồn Hiện Tại</th>
                <th style="width: 90px;" class="text-center">Ngưỡng ROP</th>
                <th style="width: 120px;" class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="mSearchTableBody">
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">Đang tải danh sách vật tư...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Đóng cửa sổ</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL CHI TIẾT PHIẾU XUẤT KHO & PHÊ DUYỆT TRỰC TIẾP
     ========================================================================= -->
<div class="modal fade" id="modalIssueDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom bg-light">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary fs-4">inventory_2</span>
          <h5 class="modal-title fw-bold" id="detailModalTitle">Chi Tiết Phiếu Xuất Kho</h5>
          <span class="badge ms-2" id="detailStatusBadge">--</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailModalBody">
        <!-- Nội dung nạp động bằng JavaScript -->
      </div>
      <div class="modal-footer border-top bg-light d-flex justify-content-between" id="detailModalFooter">
        <div class="d-flex gap-2">
          <a id="btnDetailModalPdf" href="#" target="_blank" class="app-btn app-btn-outline btn-sm text-danger border-danger d-flex align-items-center gap-1" title="Xem & In PDF chuẩn A4">
            <span class="material-icons" style="font-size: 16px;">picture_as_pdf</span>
            <span>Xem / In PDF (A4)</span>
          </a>
          <a id="btnDetailModalExcel" href="#" class="app-btn app-btn-outline btn-sm text-success border-success d-flex align-items-center gap-1" title="Xuất file Excel của phiếu">
            <span class="material-icons" style="font-size: 16px;">file_download</span>
            <span>Xuất Excel Phiếu</span>
          </a>
        </div>
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL PHÓNG TO HÌNH ẢNH VẬT TƯ
     ========================================================================= -->
<div class="modal fade" id="modalImagePreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold" id="previewImageTitle">Hình ảnh vật tư</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-3 bg-dark">
        <img id="previewImageTag" src="" alt="Vật tư" style="max-width: 100%; max-height: 480px; object-fit: contain; border-radius: 4px;">
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL CHỈNH SỬA CHI TIẾT TỪNG DÒNG VẬT TƯ (YÊU CẦU 1)
     ========================================================================= -->
<div class="modal fade" id="modalRowDetailEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-primary fs-5">tune</span>
          <h5 class="modal-title fw-bold text-main" id="rowModalTitle">Chỉnh Sửa Chi Tiết Vật Tư</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <!-- Thông tin cơ bản vật tư -->
        <div class="d-flex align-items-start gap-3 p-2 mb-3 bg-light rounded border">
          <img id="rowModalImg" src="" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
              <strong id="rowModalCode" class="font-monospace text-primary fs-6"></strong>
              <span id="rowModalCategoryBadge" class="badge bg-light text-primary border"></span>
              <span id="rowModalBin" class="badge bg-secondary-subtle text-secondary border font-monospace"></span>
            </div>
            <div id="rowModalName" class="fw-bold text-main"></div>
            <div class="small text-muted d-flex flex-wrap gap-3 mt-1">
              <span>ĐVT: <strong id="rowModalUnit" class="text-dark"></strong></span>
              <span>Quy cách: <strong id="rowModalSpec" class="text-dark"></strong></span>
              <span>Tồn kho hiện tại: <strong id="rowModalStockCurrent" class="text-success font-monospace"></strong></span>
              <span>Điểm đặt hàng (ROP): <strong id="rowModalRop" class="text-danger font-monospace"></strong></span>
            </div>
          </div>
        </div>

        <!-- Khối hiển thị Lịch sử 3 tháng gần nhất -->
        <div class="p-2 mb-3 rounded border" style="background: #f8fafc;">
          <div class="small fw-bold text-secondary mb-1 d-flex align-items-center gap-1">
            <span class="material-icons" style="font-size: 15px;">history</span>
            <span>LỊCH SỬ TIÊU HAO 3 THÁNG GẦN NHẤT & MỨC TIÊU THỤ TRUNG BÌNH:</span>
          </div>
          <div class="row g-2 text-center" style="font-size: 12px;">
            <div class="col-3">
              <div class="p-1 border rounded bg-white">
                <span class="text-muted small">Tháng 5:</span>
                <div class="fw-bold font-monospace" id="rowModalH1">0.0</div>
              </div>
            </div>
            <div class="col-3">
              <div class="p-1 border rounded bg-white">
                <span class="text-muted small">Tháng 6:</span>
                <div class="fw-bold font-monospace" id="rowModalH2">0.0</div>
              </div>
            </div>
            <div class="col-3">
              <div class="p-1 border rounded bg-white">
                <span class="text-muted small">Tháng 7:</span>
                <div class="fw-bold font-monospace" id="rowModalH3">0.0</div>
              </div>
            </div>
            <div class="col-3">
              <div class="p-1 border rounded bg-primary-subtle text-primary border-primary-subtle">
                <span class="small fw-bold">TB 3 Tháng:</span>
                <div class="fw-bold font-monospace" id="rowModalAvg3m">0.0</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form nhập dữ liệu -->
        <div class="row g-3 mb-3">
          <!-- Cột CẦN NHẬP DỮ LIỆU -->
          <div class="col-md-6 border-end">
            <h6 class="fw-bold text-warning-emphasis mb-2 d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 16px;">edit_note</span>
              <span>Thông Số Đầu Vào (Cần nhập)</span>
            </h6>
            
            <div class="mb-2">
              <label class="form-label small fw-bold mb-1">
                Số máy hoặc người sử dụng (A): <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 10px;">Số nguyên</span>
              </label>
              <input type="number" id="rowModalA" class="form-control form-control-sm form-input-highlight font-monospace text-center" min="0" step="1" oninput="onRowModalCalculate()">
              <small class="text-muted" style="font-size: 11px;">Số nguyên không có phần thập phân</small>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-bold mb-1">
                Số lần sử dụng / máy (B): <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 10px;">Số nguyên</span>
              </label>
              <input type="number" id="rowModalB" class="form-control form-control-sm form-input-highlight font-monospace text-center" min="0" step="1" oninput="onRowModalCalculate()">
              <small class="text-muted" style="font-size: 11px;">Số nguyên không có phần thập phân</small>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-bold mb-1">Định mức cho mỗi lần (C):</label>
              <input type="number" id="rowModalC" class="form-control form-control-sm form-input-highlight font-monospace text-center" min="0" step="0.1" oninput="onRowModalCalculate()">
            </div>

            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-bold mb-1">Tồn hiện trường:</label>
                <input type="number" id="rowModalFieldStock" class="form-control form-control-sm form-input-highlight font-monospace text-center text-danger" min="0" step="0.1" oninput="onRowModalCalculate()">
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold mb-1">Tồn tái sử dụng:</label>
                <input type="number" id="rowModalReusable" class="form-control form-control-sm form-input-highlight font-monospace text-center" min="0" step="0.1" oninput="onRowModalCalculate()">
              </div>
            </div>

            <div id="rowModalIrregularWrap" style="display: none;">
              <label class="form-label small fw-bold text-danger mb-1">Lý do xuất bất thường: *</label>
              <input type="text" id="rowModalIrregularReason" class="form-control form-control-sm border-danger" placeholder="Nhập lý do xuất bất thường...">
            </div>
          </div>

          <!-- Cột KẾT QUẢ TỰ ĐỘNG TÍNH TOÁN & ĐIỀU CHỈNH XUẤT -->
          <div class="col-md-6">
            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 16px;">calculate</span>
              <span>Kết Quả Tự Động Tính Toán</span>
            </h6>

            <div class="p-2 rounded bg-light border mb-2" style="font-size: 12px;">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Tổng số lần (A x B):</span>
                <strong class="font-monospace text-primary fs-7" id="rowModalTotalUses">0</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Tổng lượng lý thuyết (AxBxC):</span>
                <strong class="font-monospace text-dark" id="rowModalTheorQty">0.0</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Thực xuất lý thuyết (Trừ tồn HT):</span>
                <strong class="font-monospace text-secondary" id="rowModalNetTheorQty">0.0</strong>
              </div>
            </div>

            <div class="mb-2 p-2 rounded border border-primary bg-primary-subtle">
              <label class="form-label small fw-bold text-primary mb-1 d-flex justify-content-between">
                <span>Số Lượng Xuất Thực Tế (Quy đổi bao gói):</span>
                <span class="badge bg-primary text-white" id="rowModalPackBadge">1 Ea/gói</span>
              </label>
              <input type="number" id="rowModalActualQty" class="form-control form-control-sm font-monospace fw-bold text-center text-primary border-primary bg-white fs-6" min="0" step="0.1" oninput="onRowModalCalculateActual()">
              <small class="text-muted" style="font-size: 11px;">* Tự động làm tròn theo quy cách bao gói hoặc nhập tay điều chỉnh</small>
            </div>

            <div class="p-2 rounded bg-light border" style="font-size: 12px;">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Tồn khả dụng sau khi xuất:</span>
                <strong class="font-monospace" id="rowModalStockAfter">0.0</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Số tháng tồn kho dự kiến:</span>
                <span id="rowModalRunwayBadge"></span>
              </div>
              <div id="rowModalRopAlert" class="mt-2 alert alert-warning py-1 px-2 mb-0 small d-none">
                <span class="material-icons align-middle" style="font-size: 14px;">warning</span> Cảnh báo: Tồn sau xuất dưới ngưỡng ROP!
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Hủy bỏ</button>
        <button type="button" class="app-btn app-btn-success btn-sm" onclick="saveRowDetailModal()">
          <span class="material-icons" style="font-size: 16px;">check</span>
          <span>Lưu Thay Đổi Vào Bảng Phiếu</span>
        </button>
      </div>
    </div>
  </div>
<!-- =========================================================================
     MODAL BÁO THỦ KHO / YÊU CẦU KIỂM TRA TỒN KHO & BÁO KỲ HẠN GIAO HÀNG
     ========================================================================= -->
<div class="modal fade" id="modalRequestStockCheck" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
          <span class="material-icons">inventory_2</span>
          <span>BÁO THỦ KHO / YÊU CẦU KIỂM TRA TỒN KHO</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="stockReqMatId" value="0">
        <div class="p-2 mb-3 bg-light rounded border">
          <div>Vật tư: <strong id="stockReqMatName" class="text-main">--</strong></div>
          <div class="small text-muted">
            Mã: <span id="stockReqMatCode" class="font-monospace fw-bold text-primary">--</span> | 
            Tồn hiện có: <span id="stockReqStockCurrent" class="text-danger fw-bold font-monospace">0.0</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Loại yêu cầu gửi Thủ kho (Admin): <span class="text-danger">*</span></label>
          <select id="stockReqType" class="form-select form-select-sm">
            <option value="stock_check" selected>1. Yêu cầu kiểm tra tồn kho thực tế (Kiểm kê lại kệ/BIN)</option>
            <option value="delivery_deadline">2. Yêu cầu thông báo kỳ hạn giao hàng dự kiến (ETA đặt hàng)</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Số lượng đang cần xuất / đề xuất mua:</label>
          <input type="number" id="stockReqNeededQty" class="form-control form-control-sm font-monospace fw-bold" min="0.1" step="0.1" value="1.0">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Ghi chú cụ thể gửi Thủ kho:</label>
          <textarea id="stockReqNotes" class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Cần gấp cho chuyền sản xuất số 2, đề nghị kiểm tra lại kho hoặc liên hệ nhà cung cấp giao sớm..."></textarea>
        </div>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Đóng</button>
        <button type="button" class="app-btn app-btn-danger btn-sm d-flex align-items-center gap-1" onclick="submitStockCheckRequest()">
          <span class="material-icons" style="font-size:16px;">send</span>
          <span>Gửi Yêu Cầu Đến Thủ Kho</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL XÁC NHẬN GỬI PHIẾU YÊU CẦU XUẤT KHO (BẮT BUỘC CHECKBOX)
     ========================================================================= -->
<div class="modal fade" id="modalConfirmCreateIssue" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <h6 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">verified</span>
          <span>XÁC NHẬN THÔNG TIN PHIẾU XUẤT KHO</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <div class="p-3 mb-3 rounded bg-light border" style="font-size: 13px;">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Nhóm công việc:</span>
            <strong id="confirmGroup" class="text-main">--</strong>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Kỳ xuất kho:</span>
            <strong id="confirmPeriod" class="text-main">--</strong>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Số loại vật tư:</span>
            <strong id="confirmItemsCount" class="text-primary font-monospace">0</strong>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Tổng SL xuất thực tế:</span>
            <strong id="confirmTotalQty" class="text-primary font-monospace fs-6">0.0</strong>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Mục đích:</span>
            <span id="confirmPurpose" class="fw-semibold text-truncate" style="max-width: 250px;">--</span>
          </div>
        </div>

        <div class="p-3 mb-2 border rounded border-warning" style="background:#fffbeb;">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="chkConfirmAccuracy" onchange="toggleConfirmButton(this.checked)" style="border-color:#f59e0b; cursor:pointer;">
            <label class="form-check-label small fw-bold text-dark" for="chkConfirmAccuracy" style="cursor: pointer;">
              Tôi đã kiểm tra kỹ thông tin đề xuất và chịu trách nhiệm về số liệu xuất kho này.
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 d-flex justify-content-between">
        <button type="button" class="app-btn app-btn-outline btn-sm d-flex align-items-center gap-1 text-primary border-primary" onclick="openPdfPreviewModalFromConfirm()">
          <span class="material-icons" style="font-size:16px;">picture_as_pdf</span>
          <span>Xem Trước PDF (A4)</span>
        </button>
        <div class="d-flex gap-2">
          <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Hủy</button>
          <button type="button" class="app-btn app-btn-success btn-sm d-flex align-items-center gap-1" id="btnFinalSubmit" disabled onclick="doExecuteCreateIssue()">
            <span class="material-icons" style="font-size:16px;">send</span>
            <span>Xác Nhận & Gửi Phiếu</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL XEM TRƯỚC / IN PHIẾU XUẤT KHO A4 PDF (5 DẤU KÝ)
     ========================================================================= -->
<div class="modal fade" id="modalPreviewPDF" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <h6 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">picture_as_pdf</span>
          <span>XEM TRƯỚC BẢN IN PHIẾU XUẤT KHO (CHUẨN A4 - 5 DẤU KÝ)</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-white" id="pdfPreviewContent" style="font-family: 'Times New Roman', Times, serif;">
        <!-- Nội dung nạp động từ renderPdfPreviewHtml() -->
      </div>
      <div class="modal-footer bg-light py-2 d-flex justify-content-between">
        <div class="d-flex gap-2">
          <button type="button" class="app-btn app-btn-outline btn-sm d-flex align-items-center gap-1 text-primary border-primary" onclick="openPdfInNewTab()" title="Mở bản in toàn màn hình trong tab mới">
            <span class="material-icons" style="font-size:16px;">open_in_new</span>
            <span>Mở Trong Tab Mới</span>
          </button>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="app-btn app-btn-primary btn-sm d-flex align-items-center gap-1" onclick="printPdfPreview()">
            <span class="material-icons" style="font-size:16px;">print</span>
            <span>In Phiếu / Lưu PDF (A4)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- STYLES CHO BẢNG MÀU SẮC & DESKTOP TINH GỌN -->
<style>
/* Phân màu các cột theo yêu cầu giao diện */
.th-input-data, td.col-input-data {
  background-color: rgba(254, 243, 199, 0.45) !important;
}
.th-auto-calc, td.col-auto-calc {
  background-color: rgba(239, 246, 255, 0.55) !important;
}
[data-theme="dark"] .th-input-data, [data-theme="dark"] td.col-input-data {
  background-color: rgba(245, 158, 11, 0.12) !important;
}
[data-theme="dark"] .th-auto-calc, [data-theme="dark"] td.col-auto-calc {
  background-color: rgba(59, 130, 246, 0.12) !important;
}

/* Ô nhập liệu nổi bật viền cam để người dùng nhận biết ngay */
.form-input-highlight {
  border: 1.5px solid #f59e0b !important;
  background-color: #ffffff !important;
  font-weight: 600;
  color: #1e293b;
  font-size: 12px;
}
.form-input-highlight:focus {
  border-color: #d97706 !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
}

.thumb-small {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid #cbd5e1;
  transition: transform 0.15s;
}
.thumb-small:hover {
  transform: scale(1.15);
}

/* Workflow Stepper in Modal */
.workflow-timeline {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  margin: 15px 0 25px 0;
}
.workflow-timeline::before {
  content: '';
  position: absolute;
  top: 24px;
  left: 30px;
  right: 30px;
  height: 3px;
  background: #e2e8f0;
  z-index: 1;
}
.workflow-step-node {
  position: relative;
  z-index: 2;
  text-align: center;
  width: 140px;
}
.workflow-step-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #ffffff;
  border: 3px solid #cbd5e1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 15px;
  color: #64748b;
  margin-bottom: 6px;
  transition: all 0.2s;
}
.workflow-step-node.completed .workflow-step-circle {
  background: #10b981;
  border-color: #059669;
  color: #ffffff;
}
.workflow-step-node.active .workflow-step-circle {
  background: #3b82f6;
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
}
.workflow-step-node.rejected .workflow-step-circle {
  background: #ef4444;
  border-color: #dc2626;
  color: #ffffff;
}
.workflow-step-label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}
.workflow-step-sub {
  font-size: 11px;
  color: #94a3b8;
}
</style>

<!-- JAVASCRIPT LOGIC -->
<script>
let currentTab = 'create';
let issueItems = []; // Danh sách vật tư trong bảng tạo phiếu (Mặc định rỗng)
let allMaterialsCache = []; // Cache toàn bộ vật tư
let currentViewingIssue = null;

// Helper định dạng chuẩn 1 chữ số thập phân
function fmt1(val) {
  if (val === null || val === undefined || isNaN(val)) return '0.0';
  return Number(val).toFixed(1);
}

document.addEventListener('DOMContentLoaded', () => {
  // Yêu cầu 3: Ban đầu KHÔNG tự động đề xuất vật tư. Render bảng rỗng chờ người dùng bấm đề xuất
  renderEmptyItemsTable();
  loadAllMaterialsCache();
  loadIssuesList(1);

  // Check URL parameters for tab navigation or direct issue opening
  const urlParams = new URLSearchParams(window.location.search);
  const tabParam = urlParams.get('tab');
  if (tabParam === 'list') {
    switchTab('list');
  }
  const issueIdParam = urlParams.get('issue_id');
  if (issueIdParam) {
    switchTab('list');
    openIssueDetailModal(parseInt(issueIdParam, 10));
  }
});

function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tabContentCreate').style.display = (tab === 'create') ? 'block' : 'none';
  document.getElementById('tabContentList').style.display = (tab === 'list') ? 'block' : 'none';

  document.getElementById('tabBtnCreate').className = (tab === 'create') ? 'app-btn active' : 'app-btn app-btn-outline';
  document.getElementById('tabBtnList').className = (tab === 'list') ? 'app-btn active' : 'app-btn app-btn-outline';

  if (tab === 'list') {
    loadIssuesList(1);
  }
}

async function loadAllMaterialsCache() {
  try {
    const res = await fetch('api/warehouse.php?action=get_materials&include_inactive=0');
    const data = await res.json();
    if (data.success) {
      allMaterialsCache = data.materials || [];
    }
  } catch (e) {
    console.error('Lỗi nạp cache vật tư:', e);
  }
}

/**
 * Render bảng rỗng ban đầu khi chưa bấm đề xuất
 */
function renderEmptyItemsTable() {
  const tbody = document.getElementById('issueItemsTableBody');
  const group = document.getElementById('reqGroupSelect').value || 'Thiết bị';
  tbody.innerHTML = `
    <tr>
      <td colspan="19" class="text-center py-5 text-muted">
        <span class="material-icons mb-2 text-primary" style="font-size: 42px;">auto_awesome</span>
        <h6 class="fw-bold text-main mb-1">Danh sách vật tư hiện đang trống</h6>
        <p class="text-muted small mb-3">
          Bạn đang chọn <strong>[${escapeHtml(group)}]</strong>. Hãy bấm nút <strong>"Đề Xuất Vật Tư Theo Nhóm"</strong> để nạp danh mục tiêu hao chuẩn,<br>
          hoặc bấm <strong>"Tìm Kiếm & Chọn Vật Tư"</strong> để chủ động chọn các mặt hàng cần xuất.
        </p>
        <div class="d-flex justify-content-center gap-2">
          <button class="app-btn app-btn-success btn-sm shadow-sm" type="button" onclick="triggerGroupSuggestions()">
            <span class="material-icons">auto_awesome</span>
            <span>Đề Xuất Vật Tư Theo Nhóm [${escapeHtml(group)}]</span>
          </button>
          <button class="app-btn app-btn-primary btn-sm" type="button" onclick="openProductSearchModal()">
            <span class="material-icons">search</span>
            <span>Tìm Kiếm & Chọn Vật Tư</span>
          </button>
        </div>
      </td>
    </tr>
  `;
  updateSummaryFooter();
}

/**
 * Yêu cầu 3: Người dùng chủ động bấm nút "Đề xuất" mới nạp vật tư
 */
async function triggerGroupSuggestions() {
  const group = document.getElementById('reqGroupSelect').value;
  const month = document.getElementById('reqMonth').value;
  const year  = document.getElementById('reqYear').value;

  if (issueItems.length > 0) {
    if (!confirm(`Bạn có chắc chắn muốn nạp lại danh sách đề xuất chuẩn cho nhóm [${group}]? Các mặt hàng đã thêm trước đó sẽ được thay thế.`)) {
      return;
    }
  }

  const tbody = document.getElementById('issueItemsTableBody');
  tbody.innerHTML = `
    <tr>
      <td colspan="19" class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
        <div>Đang nạp danh mục đề xuất chuẩn định mức từ Excel T8 cho nhóm [${escapeHtml(group)}]...</div>
      </td>
    </tr>
  `;

  try {
    const res = await fetch(`api/warehouse.php?action=get_suggestions&group_name=${encodeURIComponent(group)}&month=${month}&year=${year}`);
    const data = await res.json();

    if (!data.success || !data.suggestions || data.suggestions.length === 0) {
      tbody.innerHTML = `<tr><td colspan="19" class="text-center py-4 text-warning">Không có vật tư tiêu hao đề xuất cho nhóm [${escapeHtml(group)}]. Vui lòng bấm "Tìm Kiếm & Chọn Vật Tư" để thêm.</td></tr>`;
      issueItems = [];
      updateSummaryFooter();
      return;
    }

    issueItems = data.suggestions.map(s => {
      const machines = parseInt(s.suggested_machines, 10) || 0;
      const uses = parseInt(s.suggested_uses, 10) || 0;
      const norm = parseFloat(s.suggested_norm) || 1;
      const totalUses = machines * uses;
      const theo = parseFloat(s.suggested_theoretical_qty) || 0;
      const actual = parseFloat(s.suggested_actual_qty) || 0;

      return {
        material_id: parseInt(s.id),
        item_code: s.item_code,
        item_name_vn: s.item_name_vn,
        item_name_en: s.item_name_en,
        group_name: s.group_name,
        unit: s.unit || 'Ea',
        bin_location: s.bin_location || '-',
        image_url: s.image_url || '',
        packaging_spec: s.packaging_spec || '1 ' + s.unit + '/gói',
        pack_quantity: parseFloat(s.pack_quantity) || 1,
        category_type: s.category_type || 'consumable',
        machines_count: machines,
        uses_per_machine: uses,
        total_uses: totalUses,
        norm_per_use: norm,
        theoretical_qty: theo,
        field_stock: 0,
        reusable_stock: 0,
        net_theoretical_qty: theo,
        actual_qty: actual,
        stock_current: parseFloat(s.stock_current) || 0,
        stock_after_issue: parseFloat(s.stock_after_issue) || 0,
        reorder_point: parseFloat(s.reorder_point) || 0,
        avg_3months_consumption: parseFloat(s.avg_monthly_consumption || s.avg_3months_consumption) || 0,
        runway_months: parseFloat(s.runway_months) || 99,
        history_m1: parseFloat(s.history_m1) || 0,
        history_m2: parseFloat(s.history_m2) || 0,
        history_m3: parseFloat(s.history_m3) || 0,
        is_below_rop: (parseFloat(s.stock_after_issue) <= parseFloat(s.reorder_point)) ? 1 : 0,
        irregular_reason: ''
      };
    });

    renderItemsTable();
  } catch (e) {
    console.error('Lỗi nạp đề xuất:', e);
    tbody.innerHTML = '<tr><td colspan="19" class="text-center py-4 text-danger">Lỗi kết nối máy chủ khi nạp đề xuất vật tư.</td></tr>';
  }
}

function onGroupChanged() {
  // Thay đổi nhóm: không tự động nạp mà chỉ cập nhật nếu bảng đang rỗng
  if (issueItems.length === 0) {
    renderEmptyItemsTable();
  }
}

function onPeriodChanged() {
  if (issueItems.length > 0) {
    issueItems.forEach((it, idx) => recalcItem(idx, false));
    renderItemsTable();
  }
}

function onIssueTypeChanged() {
  const type = document.getElementById('reqIssueType').value;
  const irrWrapper = document.getElementById('irregularReasonWrapper');
  irrWrapper.style.display = (type === 'irregular') ? 'block' : 'none';

  if (type === 'irregular') {
    issueItems.forEach(it => { it.category_type = 'irregular'; });
    renderItemsTable();
  }
}

/**
 * Yêu cầu 1: Render bảng ma trận tính toán với phân biệt màu sắc & 1 chữ số thập phân
 */
function renderItemsTable() {
  const tbody = document.getElementById('issueItemsTableBody');
  if (!issueItems || issueItems.length === 0) {
    renderEmptyItemsTable();
    return;
  }

  let html = '';
  issueItems.forEach((it, idx) => {
    recalcItem(idx, false);

    const imgTag = it.image_url 
      ? `<img src="${escapeHtml(it.image_url)}" class="thumb-small" onclick="previewImage('${escapeHtml(it.image_url)}', '${escapeHtml(it.item_name_vn)}')" title="Bấm để xem ảnh phóng to">`
      : `<div class="thumb-small d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:9px;">No img</div>`;

    const isConsumable = (it.category_type === 'consumable');
    const isOverStock = (parseFloat(it.actual_qty) > parseFloat(it.stock_current));
    const isZeroStock = (parseFloat(it.stock_current) <= 0);

    let stockWarningHtml = '';
    if (isZeroStock) {
      stockWarningHtml = `
        <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
          <span class="badge bg-danger" style="font-size:10px;">Hết hàng (0.0)</span>
          <button class="btn btn-outline-danger btn-xs py-0 px-1" style="font-size:10px;" type="button" onclick="openRequestStockCheckModal(${it.material_id}, '${escapeHtml(it.item_code)}', '${escapeHtml(it.item_name_vn)}', ${it.actual_qty}, ${it.stock_current})" title="Báo Thủ Kho kiểm tra tồn kho hoặc thông báo hạn giao hàng">
            <span class="material-icons align-middle" style="font-size:11px;">send</span> Báo Thủ Kho
          </button>
        </div>`;
    } else if (isOverStock) {
      stockWarningHtml = `
        <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
          <span class="badge bg-danger" style="font-size:10px;">Vượt tồn (Còn ${fmt1(it.stock_current)})</span>
          <button class="btn btn-outline-danger btn-xs py-0 px-1" style="font-size:10px;" type="button" onclick="openRequestStockCheckModal(${it.material_id}, '${escapeHtml(it.item_code)}', '${escapeHtml(it.item_name_vn)}', ${it.actual_qty}, ${it.stock_current})" title="Báo Thủ Kho kiểm tra tồn kho hoặc thông báo hạn giao hàng">
            <span class="material-icons align-middle" style="font-size:11px;">send</span> Báo Thủ Kho
          </button>
        </div>`;
    }

    let rowClass = '';
    if (isOverStock) rowClass = 'table-danger-subtle';
    else if (it.is_below_rop == 1) rowClass = 'table-warning-subtle';

    // Runway badge
    let runwayBadge = '';
    const rw = parseFloat(it.runway_months);
    if (rw <= 0.5) {
      runwayBadge = `<span class="badge bg-danger">${fmt1(rw)} thg</span>`;
    } else if (rw <= 1.0) {
      runwayBadge = `<span class="badge bg-warning text-dark">&lt; 1 thg (${fmt1(rw)})</span>`;
    } else {
      runwayBadge = `<span class="badge bg-light text-success border font-monospace">${fmt1(rw)} thg</span>`;
    }

    html += `
      <tr class="${rowClass}">
        <!-- STT -->
        <td class="text-center text-muted fw-bold">${idx + 1}</td>

        <!-- Ảnh -->
        <td class="text-center p-1">${imgTag}</td>

        <!-- Tên / Mã -->
        <td>
          <div class="fw-bold font-monospace text-primary">${escapeHtml(it.item_code)}</div>
          <div class="small fw-semibold text-main text-truncate" style="max-width: 170px;" title="${escapeHtml(it.item_name_vn)}">
            ${escapeHtml(it.item_name_vn)}
          </div>
          ${stockWarningHtml}
          ${!isConsumable ? `<input type="text" class="form-control form-control-sm form-input-highlight mt-1" style="font-size:11px;" placeholder="Lý do xuất bất thường *" value="${escapeHtml(it.irregular_reason || '')}" oninput="updateItemField(${idx}, 'irregular_reason', this.value)">` : ''}
        </td>

        <!-- BIN -->
        <td class="text-center font-monospace small">
          <span class="badge bg-light text-secondary border">${escapeHtml(it.bin_location || '-')}</span>
        </td>

        <!-- ĐVT -->
        <td class="text-center text-muted fw-semibold">${escapeHtml(it.unit)}</td>

        <!-- CỘT LỊCH SỬ 3 THÁNG -->
        <td class="text-center" style="font-size: 11px; white-space: nowrap;">
          <div class="d-flex justify-content-center align-items-center gap-1 font-monospace">
            <span class="badge bg-light text-secondary border px-1" title="Tháng 5: ${fmt1(it.history_m1)}">${fmt1(it.history_m1)}</span>
            <span class="text-muted">|</span>
            <span class="badge bg-light text-secondary border px-1" title="Tháng 6: ${fmt1(it.history_m2)}">${fmt1(it.history_m2)}</span>
            <span class="text-muted">|</span>
            <span class="badge bg-light text-secondary border px-1" title="Tháng 7: ${fmt1(it.history_m3)}">${fmt1(it.history_m3)}</span>
          </div>
          <div class="small text-muted mt-1" title="Tiêu hao trung bình 3 tháng">TB: <strong class="text-primary">${fmt1(it.avg_3months_consumption)}</strong></div>
        </td>

        <!-- 1. CỘT CẦN NHẬP: Số máy/người (A) - SỐ NGUYÊN -->
        <td class="col-input-data text-center p-1">
          ${isConsumable ? `<input type="number" min="0" step="1" class="form-control form-control-sm form-input-highlight text-center font-monospace" value="${parseInt(it.machines_count, 10) || 0}" oninput="updateItemField(${idx}, 'machines_count', this.value)">` : '<span class="text-muted">-</span>'}
        </td>

        <!-- 2. CỘT CẦN NHẬP: Số lần SD (B) - SỐ NGUYÊN -->
        <td class="col-input-data text-center p-1">
          ${isConsumable ? `<input type="number" min="0" step="1" class="form-control form-control-sm form-input-highlight text-center font-monospace" value="${parseInt(it.uses_per_machine, 10) || 0}" oninput="updateItemField(${idx}, 'uses_per_machine', this.value)">` : '<span class="text-muted">-</span>'}
        </td>

        <!-- 3. CỘT CẦN NHẬP: Định mức (C) -->
        <td class="col-input-data text-center p-1">
          ${isConsumable ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm form-input-highlight text-center" value="${fmt1(it.norm_per_use)}" oninput="updateItemField(${idx}, 'norm_per_use', this.value)">` : '<span class="text-muted">-</span>'}
        </td>

        <!-- 4. CỘT TỰ ĐỘNG TÍNH: Tổng số lần (AxB) - SỐ NGUYÊN -->
        <td class="col-auto-calc text-center font-monospace fw-semibold text-primary">
          ${isConsumable ? (parseInt(it.total_uses, 10) || 0) : '-'}
        </td>

        <!-- 5. CỘT TỰ ĐỘNG TÍNH: Tổng LT (AxBxC) -->
        <td class="col-auto-calc text-center font-monospace fw-bold text-main">
          ${isConsumable ? fmt1(it.theoretical_qty) : fmt1(it.actual_qty)}
        </td>

        <!-- 6. CỘT CẦN NHẬP: Tồn HT -->
        <td class="col-input-data text-center p-1">
          ${isConsumable ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm form-input-highlight text-center text-danger" value="${fmt1(it.field_stock)}" oninput="updateItemField(${idx}, 'field_stock', this.value)">` : '<span class="text-muted">-</span>'}
        </td>

        <!-- 7. CỘT CẦN NHẬP: Tái SD -->
        <td class="col-input-data text-center p-1">
          ${isConsumable ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm form-input-highlight text-center" value="${fmt1(it.reusable_stock)}" oninput="updateItemField(${idx}, 'reusable_stock', this.value)">` : '<span class="text-muted">-</span>'}
        </td>

        <!-- 8. CỘT TỰ ĐỘNG TÍNH: Thực xuất LT -->
        <td class="col-auto-calc text-center font-monospace fw-bold text-secondary">
          ${isConsumable ? fmt1(it.net_theoretical_qty) : fmt1(it.actual_qty)}
        </td>

        <!-- Quy cách đóng gói -->
        <td class="small text-muted text-center">
          <span class="badge bg-light text-dark border">${escapeHtml(it.packaging_spec)}</span>
        </td>

        <!-- 9. CỘT TỰ ĐỘNG TÍNH & ĐIỀU CHỈNH: Xuất Thực Tế (Quy đổi) -->
        <td class="col-auto-calc text-center table-primary p-1">
          <input type="number" min="0" step="0.1" class="form-control form-control-sm text-center font-monospace fw-bold text-primary border-primary bg-white" value="${fmt1(it.actual_qty)}" oninput="updateItemField(${idx}, 'actual_qty', this.value)">
        </td>

        <!-- 10. CỘT TỰ ĐỘNG TÍNH: Tồn sau xuất -->
        <td class="col-auto-calc text-center font-monospace fw-bold ${it.stock_after_issue <= it.reorder_point ? 'text-danger' : 'text-primary'}">
          ${fmt1(it.stock_after_issue)}
        </td>

        <!-- 11. CỘT TỰ ĐỘNG TÍNH: Số tháng tồn (Runway) -->
        <td class="col-auto-calc text-center">${runwayBadge}</td>

        <!-- Thao tác: Nút Chỉnh Sửa Modal + Xóa -->
        <td class="text-center">
          <div class="d-inline-flex align-items-center gap-1">
            <button class="app-btn app-btn-outline btn-sm p-1 text-primary border-primary-subtle" type="button" onclick="openRowDetailModal(${idx})" title="Chỉnh sửa chi tiết dòng vật tư này trong modal riêng">
              <span class="material-icons" style="font-size: 16px;">tune</span>
            </button>
            <button class="btn btn-sm btn-link text-danger p-1" type="button" onclick="removeItem(${idx})" title="Xóa dòng này">
              <span class="material-icons" style="font-size: 18px;">delete</span>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
  updateSummaryFooter();
}

/**
 * Tính lại dòng vật tư (Luôn làm tròn 1 chữ số thập phân, riêng Số máy & Số lần là Số nguyên)
 */
function recalcItem(idx, reRender = true) {
  const it = issueItems[idx];
  if (!it) return;

  if (it.category_type === 'consumable') {
    const a = parseInt(it.machines_count, 10) || 0;
    const b = parseInt(it.uses_per_machine, 10) || 0;
    const c = parseFloat(it.norm_per_use) || 0;
    const fs = parseFloat(it.field_stock) || 0;
    const rs = parseFloat(it.reusable_stock) || 0;

    it.machines_count = a;
    it.uses_per_machine = b;
    it.total_uses = a * b; // Số nguyên
    it.theoretical_qty = Math.round(it.total_uses * c * 10) / 10;
    it.net_theoretical_qty = Math.max(0, Math.round((it.theoretical_qty - fs - rs) * 10) / 10);

    const pack = parseFloat(it.pack_quantity) || 1;
    if (it.net_theoretical_qty > 0) {
      if (pack > 1) {
        it.actual_qty = Math.ceil(it.net_theoretical_qty / pack) * pack;
      } else {
        it.actual_qty = it.net_theoretical_qty;
      }
    } else {
      it.actual_qty = 0;
    }
  }

  const stockBefore = parseFloat(it.stock_current) || 0;
  const actual = parseFloat(it.actual_qty) || 0;
  it.stock_after_issue = Math.round((stockBefore - actual) * 10) / 10;

  const rop = parseFloat(it.reorder_point) || 0;
  it.is_below_rop = (it.stock_after_issue <= rop) ? 1 : 0;

  const avg3m = parseFloat(it.avg_3months_consumption) || 0;
  it.runway_months = (avg3m > 0) ? Math.round((it.stock_after_issue / avg3m) * 10) / 10 : 99;

  if (reRender) {
    renderItemsTable();
  }
}

function updateItemField(idx, field, value) {
  if (!issueItems[idx]) return;
  if (field === 'machines_count' || field === 'uses_per_machine') {
    issueItems[idx][field] = parseInt(value, 10) || 0;
  } else if (field === 'irregular_reason') {
    issueItems[idx][field] = value;
  } else {
    issueItems[idx][field] = parseFloat(value) || 0;
  }
  recalcItem(idx, true);
}

function removeItem(idx) {
  issueItems.splice(idx, 1);
  renderItemsTable();
}

function clearAllItems() {
  if (issueItems.length === 0) return;
  if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ các mặt hàng đang chọn?')) return;
  issueItems = [];
  renderEmptyItemsTable();
}

function updateSummaryFooter() {
  const totItems = issueItems.length;
  let totActual = 0;
  let ropCount = 0;

  issueItems.forEach(it => {
    totActual += parseFloat(it.actual_qty) || 0;
    if (it.is_below_rop == 1) ropCount++;
  });

  document.getElementById('summaryTotalItems').textContent = totItems;
  document.getElementById('summaryTotalActualQty').textContent = fmt1(totActual);

  const ropEl = document.getElementById('summaryRopBadge');
  if (ropCount > 0) {
    ropEl.classList.remove('d-none');
    ropEl.classList.add('d-inline-flex');
    document.getElementById('summaryRopCount').textContent = ropCount;
  } else {
    ropEl.classList.add('d-none');
    ropEl.classList.remove('d-inline-flex');
  }
}

/**
 * =========================================================================
 * YÊU CẦU 1: MODAL CHỈNH SỬA CHI TIẾT TỪNG DÒNG VẬT TƯ
 * =========================================================================
 */
let editingRowIndex = -1;

function openRowDetailModal(idx) {
  const it = issueItems[idx];
  if (!it) return;

  editingRowIndex = idx;
  document.getElementById('rowModalTitle').textContent = `Chỉnh Sửa Chi Tiết: ${it.item_code} - ${it.item_name_vn}`;
  document.getElementById('rowModalCode').textContent = it.item_code;
  document.getElementById('rowModalName').textContent = it.item_name_vn;
  document.getElementById('rowModalUnit').textContent = it.unit;
  document.getElementById('rowModalSpec').textContent = it.packaging_spec || ('1 ' + it.unit + '/gói');
  document.getElementById('rowModalStockCurrent').textContent = fmt1(it.stock_current) + ' ' + it.unit;
  document.getElementById('rowModalRop').textContent = fmt1(it.reorder_point) + ' ' + it.unit;
  document.getElementById('rowModalBin').textContent = 'Kệ: ' + (it.bin_location || '-');

  const catBadge = document.getElementById('rowModalCategoryBadge');
  catBadge.textContent = (it.category_type === 'consumable') ? 'Tiêu hao định mức' : 'Bất thường';
  catBadge.className = (it.category_type === 'consumable') ? 'badge bg-light text-primary border' : 'badge bg-warning-subtle text-warning border';

  const imgEl = document.getElementById('rowModalImg');
  if (it.image_url) {
    imgEl.src = it.image_url;
    imgEl.style.display = 'block';
  } else {
    imgEl.src = '';
    imgEl.style.display = 'none';
  }

  // Lịch sử 3 tháng
  document.getElementById('rowModalH1').textContent = fmt1(it.history_m1);
  document.getElementById('rowModalH2').textContent = fmt1(it.history_m2);
  document.getElementById('rowModalH3').textContent = fmt1(it.history_m3);
  document.getElementById('rowModalAvg3m').textContent = fmt1(it.avg_3months_consumption);

  // Inputs: Số máy A và Số lần B là số nguyên
  document.getElementById('rowModalA').value = parseInt(it.machines_count, 10) || 0;
  document.getElementById('rowModalB').value = parseInt(it.uses_per_machine, 10) || 0;
  document.getElementById('rowModalC').value = fmt1(it.norm_per_use);
  document.getElementById('rowModalFieldStock').value = fmt1(it.field_stock);
  document.getElementById('rowModalReusable').value = fmt1(it.reusable_stock);
  document.getElementById('rowModalActualQty').value = fmt1(it.actual_qty);
  document.getElementById('rowModalPackBadge').textContent = it.packaging_spec || ('1 ' + it.unit + '/gói');

  const irrWrap = document.getElementById('rowModalIrregularWrap');
  if (it.category_type === 'irregular') {
    irrWrap.style.display = 'block';
    document.getElementById('rowModalIrregularReason').value = it.irregular_reason || '';
  } else {
    irrWrap.style.display = 'none';
  }

  onRowModalCalculate();

  const modal = new bootstrap.Modal(document.getElementById('modalRowDetailEdit'));
  modal.show();
}

function onRowModalCalculate() {
  if (editingRowIndex < 0 || !issueItems[editingRowIndex]) return;
  const it = issueItems[editingRowIndex];

  // A và B là số nguyên
  const a = parseInt(document.getElementById('rowModalA').value, 10) || 0;
  const b = parseInt(document.getElementById('rowModalB').value, 10) || 0;
  const c = parseFloat(document.getElementById('rowModalC').value) || 0;
  const fs = parseFloat(document.getElementById('rowModalFieldStock').value) || 0;
  const rs = parseFloat(document.getElementById('rowModalReusable').value) || 0;
  const pack = parseFloat(it.pack_quantity) || 1;

  const totalUses = a * b; // Số nguyên
  const theorQty = Math.round(totalUses * c * 10) / 10;
  const netTheorQty = Math.max(0, Math.round((theorQty - fs - rs) * 10) / 10);

  let actualQty = 0;
  if (netTheorQty > 0) {
    if (pack > 1) {
      actualQty = Math.ceil(netTheorQty / pack) * pack;
    } else {
      actualQty = netTheorQty;
    }
  }

  document.getElementById('rowModalTotalUses').textContent = totalUses;
  document.getElementById('rowModalTheorQty').textContent = fmt1(theorQty);
  document.getElementById('rowModalNetTheorQty').textContent = fmt1(netTheorQty);
  document.getElementById('rowModalActualQty').value = fmt1(actualQty);

  updateRowModalStockPreview(actualQty);
}

function onRowModalCalculateActual() {
  const actualQty = parseFloat(document.getElementById('rowModalActualQty').value) || 0;
  updateRowModalStockPreview(actualQty);
}

function updateRowModalStockPreview(actualQty) {
  if (editingRowIndex < 0 || !issueItems[editingRowIndex]) return;
  const it = issueItems[editingRowIndex];
  const stockBefore = parseFloat(it.stock_current) || 0;
  const stockAfter = Math.round((stockBefore - actualQty) * 10) / 10;
  const rop = parseFloat(it.reorder_point) || 0;
  const avg3m = parseFloat(it.avg_3months_consumption) || 0;
  const runway = (avg3m > 0) ? Math.round((stockAfter / avg3m) * 10) / 10 : 99;

  const stockAfterEl = document.getElementById('rowModalStockAfter');
  stockAfterEl.textContent = fmt1(stockAfter) + ' ' + it.unit;
  stockAfterEl.className = 'font-monospace ' + (stockAfter <= rop ? 'text-danger fw-bold' : 'text-primary fw-bold');

  const runwayEl = document.getElementById('rowModalRunwayBadge');
  if (runway <= 0.5) {
    runwayEl.innerHTML = `<span class="badge bg-danger">${fmt1(runway)} tháng</span>`;
  } else if (runway <= 1.0) {
    runwayEl.innerHTML = `<span class="badge bg-warning text-dark">&lt; 1 tháng (${fmt1(runway)})</span>`;
  } else {
    runwayEl.innerHTML = `<span class="badge bg-light text-success border font-monospace">${fmt1(runway)} tháng</span>`;
  }

  const ropAlert = document.getElementById('rowModalRopAlert');
  if (stockAfter <= rop) {
    ropAlert.classList.remove('d-none');
  } else {
    ropAlert.classList.add('d-none');
  }
}

function saveRowDetailModal() {
  if (editingRowIndex < 0 || !issueItems[editingRowIndex]) return;
  const it = issueItems[editingRowIndex];

  // Lưu các thông số đã điều chỉnh
  it.machines_count = parseInt(document.getElementById('rowModalA').value, 10) || 0;
  it.uses_per_machine = parseInt(document.getElementById('rowModalB').value, 10) || 0;
  it.norm_per_use = parseFloat(document.getElementById('rowModalC').value) || 0;
  it.field_stock = parseFloat(document.getElementById('rowModalFieldStock').value) || 0;
  it.reusable_stock = parseFloat(document.getElementById('rowModalReusable').value) || 0;
  it.actual_qty = parseFloat(document.getElementById('rowModalActualQty').value) || 0;

  if (it.category_type === 'irregular') {
    it.irregular_reason = document.getElementById('rowModalIrregularReason').value.trim();
  }

  recalcItem(editingRowIndex, false);
  renderItemsTable();

  const modalEl = document.getElementById('modalRowDetailEdit');
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) modal.hide();
}

function openCustomItemModal() {
  openProductSearchModal();
}

function filterByPipelineStatus(statusKey) {
  document.getElementById('filterListStatus').value = statusKey;
  loadIssuesList(1);
}

/**
 * =========================================================================
 * MODAL TÌM KIẾM & CHỌN VẬT TƯ
 * =========================================================================
 */
function openProductSearchModal() {
  const currentGroup = document.getElementById('reqGroupSelect').value;
  document.getElementById('mSearchGroup').value = currentGroup || 'ALL';
  document.getElementById('mSearchKeyword').value = '';
  filterModalProducts();
  const modal = new bootstrap.Modal(document.getElementById('modalProductSearch'));
  modal.show();
}

function filterModalProducts() {
  const kw = (document.getElementById('mSearchKeyword').value || '').toLowerCase().trim();
  const grp = document.getElementById('mSearchGroup').value;
  const tbody = document.getElementById('mSearchTableBody');

  const filtered = allMaterialsCache.filter(m => {
    if (grp !== 'ALL' && m.group_name !== grp) return false;
    if (kw) {
      const matchCode = (m.item_code || m.material_code || '').toLowerCase().includes(kw);
      const matchSap  = (m.sap_code || '').toLowerCase().includes(kw);
      const matchName = (m.item_name_vn || m.material_name || '').toLowerCase().includes(kw);
      const matchBin  = (m.bin_location || '').toLowerCase().includes(kw);
      if (!matchCode && !matchSap && !matchName && !matchBin) return false;
    }
    return true;
  });

  document.getElementById('mSearchCount').textContent = filtered.length;

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Không tìm thấy vật tư nào phù hợp với từ khóa.</td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach((m, idx) => {
    const isAdded = issueItems.some(it => it.material_id == m.id);
    const imgUrl = m.image_url || '';
    const imgTag = imgUrl 
      ? `<img src="${escapeHtml(imgUrl)}" class="thumb-small" onclick="previewImage('${escapeHtml(imgUrl)}', '${escapeHtml(m.item_name_vn)}')">`
      : `<div class="thumb-small d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:9px;">No img</div>`;

    const currStock = parseFloat(m.stock_current) || 0;
    const isOutOfStock = currStock <= 0;

    let btnAction = '';
    if (isAdded) {
      btnAction = `<span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:13px;">check</span> Đã thêm</span>`;
    } else if (isOutOfStock) {
      btnAction = `<button class="app-btn app-btn-outline btn-sm py-1 px-2 text-danger border-danger d-inline-flex align-items-center gap-1" type="button" onclick="openRequestStockCheckModal(${m.id}, '${escapeHtml(m.item_code || m.material_code)}', '${escapeHtml(m.item_name_vn || m.material_name)}', 1, ${currStock})" title="Hết tồn kho! Gửi yêu cầu kiểm tra hoặc hỏi kỳ hạn giao hàng đến Thủ kho"><span class="material-icons" style="font-size:13px;">report_problem</span> Báo Thủ Kho</button>`;
    } else {
      btnAction = `<button class="app-btn app-btn-primary btn-sm py-1 px-2" type="button" onclick="addMaterialToIssue(${m.id})"><span class="material-icons" style="font-size:14px;">add</span> Thêm</button>`;
    }

    html += `
      <tr class="${isOutOfStock ? 'table-light text-muted' : ''}">
        <td class="text-center text-muted fw-bold">${idx + 1}</td>
        <td class="text-center p-1">${imgTag}</td>
        <td>
          <strong class="font-monospace ${isOutOfStock ? 'text-secondary' : 'text-primary'}">${escapeHtml(m.item_code || m.material_code)}</strong>
          ${m.sap_code ? `<div class="text-muted font-monospace small">SAP: ${escapeHtml(m.sap_code)}</div>` : ''}
          ${isOutOfStock ? `<div class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1" style="font-size:9px;">Hết tồn kho</div>` : ''}
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(m.item_name_vn || m.material_name)}</div>
          <div class="small text-muted">Quy cách: ${escapeHtml(m.packaging_spec || m.pack_spec || '1 ' + m.unit + '/gói')}</div>
        </td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(m.group_name)}</span></td>
        <td class="text-center font-monospace small">${escapeHtml(m.bin_location || '-')}</td>
        <td class="text-end font-monospace fw-bold ${isOutOfStock ? 'text-danger' : ''}">${fmt1(m.stock_current)} <small class="text-muted">${m.unit}</small></td>
        <td class="text-center font-monospace">${fmt1(m.reorder_point)}</td>
        <td class="text-center" id="mActionCol_${m.id}">${btnAction}</td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function addMaterialToIssue(matId) {
  const m = allMaterialsCache.find(it => it.id == matId);
  if (!m) return;

  const currStock = parseFloat(m.stock_current) || 0;
  if (currStock <= 0) {
    alert(`RÀNG BUỘC TỒN KHO:\nMã hàng [${m.item_code} - ${m.item_name_vn}] hiện tại có số lượng tồn kho bằng 0 (hết hàng)!\n\nHệ thống sẽ mở hộp thoại để bạn gửi yêu cầu đến Thủ kho/Admin kiểm tra lại tồn kho thực tế hoặc phản hồi kỳ hạn giao hàng (ETA).`);
    openRequestStockCheckModal(m.id, m.item_code || m.material_code, m.item_name_vn || m.material_name, 1, currStock);
    return;
  }

  const issueType = document.getElementById('reqIssueType').value;
  const isConsumable = (issueType === 'consumable');

  const machines = isConsumable ? (parseInt(m.default_machines, 10) || 1) : 0;
  const uses = isConsumable ? (parseInt(m.default_uses_per_machine, 10) || 1) : 0;
  const norm = isConsumable ? (parseFloat(m.norm_per_use) || 1) : 0;
  const totalUses = isConsumable ? (machines * uses) : 0;
  const theo = isConsumable ? Math.round(totalUses * norm * 10) / 10 : 1;
  const pack = parseFloat(m.pack_quantity) || 1;
  const actual = isConsumable ? (pack > 1 ? Math.ceil(theo / pack) * pack : theo) : 1;

  issueItems.push({
    material_id: parseInt(m.id),
    item_code: m.item_code || m.material_code,
    item_name_vn: m.item_name_vn || m.material_name,
    item_name_en: m.item_name_en || '',
    group_name: m.group_name,
    unit: m.unit || 'Ea',
    bin_location: m.bin_location || '-',
    image_url: m.image_url || '',
    packaging_spec: m.packaging_spec || m.pack_spec || '1 ' + m.unit + '/gói',
    pack_quantity: pack,
    category_type: issueType,
    machines_count: machines,
    uses_per_machine: uses,
    total_uses: totalUses,
    norm_per_use: norm,
    theoretical_qty: theo,
    field_stock: 0,
    reusable_stock: 0,
    net_theoretical_qty: theo,
    actual_qty: actual,
    stock_current: parseFloat(m.stock_current) || 0,
    stock_after_issue: parseFloat(m.stock_current) - actual,
    reorder_point: parseFloat(m.reorder_point) || 0,
    avg_3months_consumption: parseFloat(m.avg_monthly_consumption || m.avg_3months_consumption) || 0,
    runway_months: (parseFloat(m.avg_monthly_consumption || m.avg_3months_consumption) > 0) ? Math.round(((parseFloat(m.stock_current) - actual) / parseFloat(m.avg_monthly_consumption || m.avg_3months_consumption)) * 10) / 10 : 99,
    history_m1: parseFloat(m.history_m1) || 0,
    history_m2: parseFloat(m.history_m2) || 0,
    history_m3: parseFloat(m.history_m3) || 0,
    is_below_rop: ((parseFloat(m.stock_current) - actual) <= parseFloat(m.reorder_point)) ? 1 : 0,
    irregular_reason: ''
  });

  renderItemsTable();

  // Cập nhật nút trong modal thành "Đã thêm"
  const col = document.getElementById(`mActionCol_${matId}`);
  if (col) {
    col.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:13px;">check</span> Đã thêm</span>`;
  }
}

/**
 * Gửi tạo phiếu yêu cầu
 */
async function submitCreateIssue() {
  if (!issueItems || issueItems.length === 0) {
    alert('Phiếu yêu cầu đang trống. Vui lòng bấm "Đề Xuất Vật Tư Theo Nhóm" hoặc "Tìm Kiếm & Chọn Vật Tư" trước khi gửi!');
    return;
  }

  const groupName = document.getElementById('reqGroupSelect').value;
  const issueType = document.getElementById('reqIssueType').value;
  const month     = document.getElementById('reqMonth').value;
  const year      = document.getElementById('reqYear').value;
  const purpose   = document.getElementById('reqPurpose').value.trim();
  const irrReason = document.getElementById('reqIrregularReason').value.trim();

  if (!purpose) {
    alert('Vui lòng nhập Mục đích xuất kho.');
    document.getElementById('reqPurpose').focus();
    return;
  }

  if (issueType === 'irregular' && !irrReason) {
    alert('Vật tư bất thường bắt buộc phải nhập lý do và mục đích xuất!');
    document.getElementById('reqIrregularReason').focus();
    return;
  }

  // Kiểm tra vật tư bất thường từng dòng
  for (let it of issueItems) {
    if (it.category_type === 'irregular' && !it.irregular_reason && !irrReason) {
      alert(`Mặt hàng [${it.item_code}] là loại bất thường, bắt buộc phải nhập lý do.`);
      return;
    }
  }

  // Ràng buộc số lượng xuất thực tế không vượt quá số lượng tồn kho khả dụng
  const overStockItems = issueItems.filter(it => parseFloat(it.actual_qty) > parseFloat(it.stock_current));
  if (overStockItems.length > 0) {
    const firstOver = overStockItems[0];
    alert(`RÀNG BUỘC TỒN KHO KHẢ DỤNG:\nMặt hàng [${firstOver.item_code} - ${firstOver.item_name_vn}] yêu cầu xuất ${fmt1(firstOver.actual_qty)} ${firstOver.unit}, vượt quá số lượng tồn kho khả dụng hiện có (${fmt1(firstOver.stock_current)} ${firstOver.unit})!\n\nVui lòng điều chỉnh lại số lượng xuất hoặc bấm nút "Báo Thủ Kho" tại dòng đó để gửi yêu cầu kiểm tra.`);
    return;
  }

  // Hiển thị modal xác nhận thông tin & cam kết
  document.getElementById('confirmGroup').textContent = groupName;
  document.getElementById('confirmPeriod').textContent = `Tháng ${month}/${year}`;
  document.getElementById('confirmItemsCount').textContent = issueItems.length;
  let totAct = 0;
  issueItems.forEach(it => { totAct += parseFloat(it.actual_qty) || 0; });
  document.getElementById('confirmTotalQty').textContent = fmt1(totAct);
  document.getElementById('confirmPurpose').textContent = purpose;
  document.getElementById('chkConfirmAccuracy').checked = false;
  document.getElementById('btnFinalSubmit').disabled = true;

  const confirmModalEl = document.getElementById('modalConfirmCreateIssue');
  if (confirmModalEl && confirmModalEl.parentElement !== document.body) {
    document.body.appendChild(confirmModalEl);
  }
  const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
  confirmModal.show();
}

function toggleConfirmButton(checked) {
  const btn = document.getElementById('btnFinalSubmit');
  if (btn) btn.disabled = !checked;
}

/**
 * Thực hiện gửi phiếu sau khi người dùng đã tích xác nhận
 */
async function doExecuteCreateIssue() {
  const groupName = document.getElementById('reqGroupSelect').value;
  const issueType = document.getElementById('reqIssueType').value;
  const month     = document.getElementById('reqMonth').value;
  const year      = document.getElementById('reqYear').value;
  const purpose   = document.getElementById('reqPurpose').value.trim();
  const irrReason = document.getElementById('reqIrregularReason').value.trim();

  const payload = {
    group_name: groupName,
    issue_type: issueType,
    month: parseInt(month),
    year: parseInt(year),
    department: 'Phân xưởng sản xuất',
    purpose: purpose,
    reason_for_irregular: irrReason,
    notes: '',
    items: issueItems.map(it => ({
      material_id: it.material_id,
      machines_count: it.machines_count,
      uses_per_machine: it.uses_per_machine,
      norm_per_use: it.norm_per_use,
      field_stock: it.field_stock,
      reusable_stock: it.reusable_stock,
      actual_qty: it.actual_qty,
      category_type: it.category_type,
      irregular_reason: it.irregular_reason || irrReason
    }))
  };

  const btn = document.getElementById('btnFinalSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo phiếu...';

  try {
    const res = await fetch('api/warehouse.php?action=create_issue', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      // Đóng modal xác nhận an toàn
      const modalEl = document.getElementById('modalConfirmCreateIssue');
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
      forceCleanBackdrops();

      setTimeout(() => {
        alert(`Đã khởi tạo phiếu xuất kho [${data.issue_code}] thành công!\nPhiếu đã chuyển sang Bước 2: Chờ Người Kiểm Tra Duyệt.`);
        issueItems = [];
        renderEmptyItemsTable();
        switchTab('list');
      }, 200);
    } else {
      alert(data.message || 'Lỗi tạo phiếu xuất kho.');
    }
  } catch (err) {
    console.error('Lỗi doExecuteCreateIssue:', err);
    alert('Lỗi kết nối máy chủ khi gửi phiếu yêu cầu.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons" style="font-size:16px;">send</span> <span>Xác Nhận & Gửi Phiếu</span>';
  }
}

/**
 * Hàm dọn dẹp backdrop mồ côi và mở khóa màn hình
 */
function forceCleanBackdrops() {
  const openModals = document.querySelectorAll('.modal.show');
  if (openModals.length === 0) {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }
}

/**
 * Mở modal gửi yêu cầu kiểm tra tồn kho / báo kỳ hạn giao hàng
 */
function openRequestStockCheckModal(matId, code, name, actualQty, currentStock) {
  // Đóng modal tìm kiếm nếu đang mở để tránh chồng chéo backdrop
  const searchModalEl = document.getElementById('modalProductSearch');
  if (searchModalEl) {
    const searchModal = bootstrap.Modal.getInstance(searchModalEl);
    if (searchModal) searchModal.hide();
  }

  document.getElementById('stockReqMatId').value = matId;
  document.getElementById('stockReqMatCode').textContent = code;
  document.getElementById('stockReqMatName').textContent = name;
  document.getElementById('stockReqStockCurrent').textContent = fmt1(currentStock);
  document.getElementById('stockReqNeededQty').value = fmt1(actualQty > 0 ? actualQty : 1);
  document.getElementById('stockReqNotes').value = `Mặt hàng tồn kho không đủ (Hiện có: ${fmt1(currentStock)}). Đề nghị kiểm tra lại hoặc thông báo thời hạn giao hàng dự kiến.`;

  const modalEl = document.getElementById('modalRequestStockCheck');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

/**
 * Gửi yêu cầu kiểm tra tồn kho đến Thủ kho
 */
async function submitStockCheckRequest() {
  const matId = document.getElementById('stockReqMatId').value;
  const reqType = document.getElementById('stockReqType').value;
  const neededQty = document.getElementById('stockReqNeededQty').value;
  const notes = document.getElementById('stockReqNotes').value.trim();

  if (!notes) {
    alert('Vui lòng nhập ghi chú cụ thể gửi Thủ kho.');
    return;
  }

  const formData = new FormData();
  formData.append('material_id', matId);
  formData.append('request_type', reqType);
  formData.append('needed_qty', neededQty);
  formData.append('notes', notes);

  try {
    const res = await fetch('api/warehouse.php?action=create_stock_request', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      const modalEl = document.getElementById('modalRequestStockCheck');
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
      forceCleanBackdrops();

      setTimeout(() => {
        alert(data.message || 'Đã gửi yêu cầu đến Thủ kho thành công!');
      }, 200);
    } else {
      alert(data.message || 'Lỗi gửi yêu cầu kiểm tra.');
    }
  } catch (err) {
    console.error(err);
    alert('Lỗi kết nối máy chủ.');
  }
}

/**
 * Mở modal preview PDF từ modal xác nhận
 */
function openPdfPreviewModalFromConfirm() {
  const confirmEl = document.getElementById('modalConfirmCreateIssue');
  if (confirmEl) {
    const confirmInst = bootstrap.Modal.getInstance(confirmEl);
    if (confirmInst) confirmInst.hide();
  }
  setTimeout(() => {
    forceCleanBackdrops();
    openPdfPreviewModal();
  }, 200);
}

/**
 * Mở Modal xem trước bản in PDF (A4) có 5 dấu ký
 */
function openPdfPreviewModal(issueId = null) {
  const modalEl = document.getElementById('modalPreviewPDF');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }

  if (issueId) {
    document.getElementById('pdfPreviewContent').innerHTML = `
      <div style="height: 75vh; width: 100%;">
        <iframe id="pdfPreviewIframe" src="api/warehouse.php?action=render_issue_pdf_html&issue_id=${issueId}" style="width:100%; height:100%; border:none; border-radius:4px;"></iframe>
      </div>
    `;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    return;
  }

  if (!issueItems || issueItems.length === 0) {
    alert('Bảng vật tư đang trống. Vui lòng thêm vật tư vào phiếu trước khi xem trước bản in!');
    return;
  }

  const groupName = document.getElementById('reqGroupSelect').value;
  const issueType = document.getElementById('reqIssueType').value;
  const month     = document.getElementById('reqMonth').value;
  const year      = document.getElementById('reqYear').value;
  const purpose   = document.getElementById('reqPurpose').value.trim();

  let totTheo = 0;
  let totAct = 0;
  let rowsHtml = '';

  issueItems.forEach((it, idx) => {
    totTheo += parseFloat(it.theoretical_qty) || 0;
    totAct += parseFloat(it.actual_qty) || 0;
    const totUses = it.total_uses || (parseFloat(it.machines_count || 0) * parseFloat(it.uses_per_machine || 0));

    rowsHtml += `
      <tr>
        <td style="text-align:center;">${idx + 1}</td>
        <td style="text-align:center; font-weight:bold; font-family:monospace;">${escapeHtml(it.item_code)}</td>
        <td><strong>${escapeHtml(it.item_name_vn)}</strong></td>
        <td style="text-align:center;">${escapeHtml(it.unit)}</td>
        <td style="text-align:center; font-family:monospace;">${escapeHtml(it.bin_location || '-')}</td>
        <td style="text-align:center; background:#fffbeb;">${parseInt(it.machines_count, 10) || 0}</td>
        <td style="text-align:center; background:#fffbeb;">${parseInt(it.uses_per_machine, 10) || 0}</td>
        <td style="text-align:right; background:#fffbeb;">${fmt1(it.norm_per_use)}</td>
        <td style="text-align:center; background:#eff6ff; font-weight:bold;">${parseInt(totUses, 10) || 0}</td>
        <td style="text-align:right; background:#eff6ff;">${fmt1(it.theoretical_qty)}</td>
        <td style="text-align:right; background:#fffbeb;">${fmt1(it.field_stock)}</td>
        <td style="text-align:right; background:#fffbeb;">${fmt1(it.reusable_stock)}</td>
        <td style="text-align:right; background:#eff6ff;">${fmt1(it.net_theoretical_qty)}</td>
        <td style="text-align:right; font-weight:bold; background:#e2efda; color:#166534;">${fmt1(it.actual_qty)}</td>
        <td style="text-align:right; background:#eff6ff;">${fmt1(it.stock_after_issue)}</td>
        <td style="text-align:center; background:#eff6ff;">${fmt1(it.runway_months)}</td>
        <td style="font-size:10px;">${escapeHtml(it.irregular_reason || '-')}</td>
      </tr>
    `;
  });

  const previewHtml = `
    <div style="max-width: 1050px; margin: 0 auto; color: #111827; font-size: 12px;">
      <!-- Header công ty -->
      <table style="width:100%; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px;">
        <tr>
          <td style="vertical-align:top; width:65%;">
            <strong style="font-size:15px; text-transform:uppercase;">DX PLASTIC GROUP - BỘ PHẬN KHO & SẢN XUẤT</strong>
            <div style="font-size:11px; color:#475569;">Quy Trình Quản Lý & Xuất Vật Tư Tiêu Hao Chuẩn Nhà Máy</div>
          </td>
          <td style="vertical-align:top; text-align:right; font-size:11px; width:35%;">
            <div>Mã biểu mẫu: <strong>BM-WH-XK-05</strong></div>
            <div>Kỳ xuất: Tháng ${month}/${year} | Bản xem trước dự thảo</div>
          </td>
        </tr>
      </table>

      <!-- Tiêu đề phiếu -->
      <div style="text-align:center; margin: 10px 0 14px 0;">
        <h3 style="margin:0; font-size:18px; text-transform:uppercase; letter-spacing:0.5px;">PHIẾU YÊU CẦU XUẤT VẬT TƯ (BẢN XEM TRƯỚC)</h3>
        <p style="margin:3px 0 0 0; font-style:italic; font-size:11.5px;">Nhóm công việc: <strong>${escapeHtml(groupName)}</strong> | Mục đích: ${escapeHtml(purpose)}</p>
      </div>

      <!-- Bảng dữ liệu chuẩn 17 cột -->
      <table style="width:100%; border-collapse:collapse; margin-bottom: 16px; font-size:11px;" border="1">
        <thead>
          <tr style="background:#f1f5f9; text-align:center;">
            <th rowspan="2">STT</th>
            <th rowspan="2">Mã VT</th>
            <th rowspan="2">Tên Vật Tư</th>
            <th rowspan="2">ĐVT</th>
            <th rowspan="2">Kệ BIN</th>
            <th colspan="3" style="background:#fef3c7; color:#92400e;">CỘT CẦN NHẬP DỮ LIỆU</th>
            <th colspan="2" style="background:#eff6ff; color:#1e40af;">TỰ ĐỘNG TÍNH</th>
            <th colspan="2" style="background:#fef3c7; color:#92400e;">TỒN HIỆN TRƯỜNG</th>
            <th colspan="4" style="background:#eff6ff; color:#1e40af;">QUY ĐỔI & TỒN KHẢ DỤNG</th>
            <th rowspan="2">Ghi chú</th>
          </tr>
          <tr style="text-align:center; font-size:10px;">
            <th style="background:#fef3c7; color:#92400e;">Số máy (A)</th>
            <th style="background:#fef3c7; color:#92400e;">Số lần (B)</th>
            <th style="background:#fef3c7; color:#92400e;">Định mức (C)</th>
            <th style="background:#eff6ff; color:#1e40af;">Tổng lần<br>(AxB)</th>
            <th style="background:#eff6ff; color:#1e40af;">Tổng LT</th>
            <th style="background:#fef3c7; color:#92400e;">Tồn HT</th>
            <th style="background:#fef3c7; color:#92400e;">Tái SD</th>
            <th style="background:#eff6ff; color:#1e40af;">Thực xuất LT</th>
            <th style="background:#c6e0b4; color:#166534; font-weight:bold;">Xuất thực tế</th>
            <th style="background:#eff6ff; color:#1e40af;">Tồn sau xuất</th>
            <th style="background:#eff6ff; color:#1e40af;">Tháng tồn</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
          <tr style="font-weight:bold; background:#f8fafc;">
            <td colspan="5" style="text-align:center;">TỔNG CỘNG (${issueItems.length} MẶT HÀNG)</td>
            <td colspan="4"></td>
            <td style="text-align:right;">${fmt1(totTheo)}</td>
            <td colspan="3"></td>
            <td style="text-align:right; color:#166534; font-size:12px; background:#c6e0b4;">${fmt1(totAct)}</td>
            <td colspan="3"></td>
          </tr>
        </tbody>
      </table>

      <!-- 5 Khung Ký Tên Chuẩn -->
      <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:8px; margin-top:16px;">
        <div style="border:1px solid #94a3b8; border-radius:4px; padding:6px; text-align:center; min-height:115px; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
          <div style="font-weight:bold; font-size:10.5px; border-bottom:1px dashed #cbd5e1; padding-bottom:3px;">1. NGƯỜI LẬP PHIẾU</div>
          <div style="border:1.5px solid #16a34a; color:#16a34a; font-weight:bold; font-size:9.5px; padding:2px 4px; border-radius:3px; margin:4px auto; display:inline-block;">✓ BẢN DỰ THẢO</div>
          <div style="font-weight:bold; font-size:11px;">(Người dùng hiện tại)</div>
        </div>
        <div style="border:1px solid #94a3b8; border-radius:4px; padding:6px; text-align:center; min-height:115px; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
          <div style="font-weight:bold; font-size:10.5px; border-bottom:1px dashed #cbd5e1; padding-bottom:3px;">2. NGƯỜI KIỂM TRA</div>
          <div style="color:#94a3b8; font-size:10px;">(Ký & ghi rõ họ tên)</div>
          <div style="color:#94a3b8; font-size:11px;">........................</div>
        </div>
        <div style="border:1px solid #94a3b8; border-radius:4px; padding:6px; text-align:center; min-height:115px; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
          <div style="font-weight:bold; font-size:10.5px; border-bottom:1px dashed #cbd5e1; padding-bottom:3px;">3. QUẢN LÝ PHÊ DUYỆT</div>
          <div style="color:#94a3b8; font-size:10px;">(Ký & ghi rõ họ tên)</div>
          <div style="color:#94a3b8; font-size:11px;">........................</div>
        </div>
        <div style="border:1px solid #94a3b8; border-radius:4px; padding:6px; text-align:center; min-height:115px; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
          <div style="font-weight:bold; font-size:10.5px; border-bottom:1px dashed #cbd5e1; padding-bottom:3px;">4. THỦ KHO XUẤT HÀNG</div>
          <div style="color:#94a3b8; font-size:10px;">(Ký & xuất kho)</div>
          <div style="color:#94a3b8; font-size:11px;">........................</div>
        </div>
        <div style="border:1px solid #94a3b8; border-radius:4px; padding:6px; text-align:center; min-height:115px; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
          <div style="font-weight:bold; font-size:10.5px; border-bottom:1px dashed #cbd5e1; padding-bottom:3px;">5. NHẬN BÀN GIAO</div>
          <div style="color:#94a3b8; font-size:10px;">(Ký & nhận đủ)</div>
          <div style="color:#94a3b8; font-size:11px;">........................</div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('pdfPreviewContent').innerHTML = previewHtml;
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function printPdfPreview() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe && iframe.contentWindow) {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    return;
  }

  const content = document.getElementById('pdfPreviewContent').innerHTML;
  let printFrame = document.getElementById('print_frame_pdf_preview');
  if (!printFrame) {
    printFrame = document.createElement('iframe');
    printFrame.id = 'print_frame_pdf_preview';
    printFrame.style.position = 'fixed';
    printFrame.style.right = '0';
    printFrame.style.bottom = '0';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = 'none';
    document.body.appendChild(printFrame);
  }
  const frameDoc = printFrame.contentWindow.document;
  frameDoc.open();
  frameDoc.write(`
    <!DOCTYPE html>
    <html lang="vi">
    <head>
      <meta charset="utf-8">
      <title>Phiếu Yêu Cầu Xuất Vật Tư (Bản In)</title>
      <style>
        @page { size: A4 portrait; margin: 10mm 8mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; padding: 10px; color: #000; background: #fff; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 12px; }
        th, td { border: 0.8pt solid #000; padding: 4px 6px; }
        th { background-color: #f1f5f9; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        @media print {
          th { background-color: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          .highlight-cell { background-color: #e2efda !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>
      ${content}
    </body>
    </html>
  `);
  frameDoc.close();
  setTimeout(() => {
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();
  }, 350);
}

function openPdfInNewTab() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe && iframe.src) {
    window.open(iframe.src, '_blank');
    return;
  }

  const content = document.getElementById('pdfPreviewContent').innerHTML;
  const fullHtml = `<!DOCTYPE html>
  <html lang="vi">
  <head>
    <meta charset="utf-8">
    <title>Xem Trước Phiếu Xuất Kho A4 - DX Plastic</title>
    <style>
      @page { size: A4 portrait; margin: 10mm 8mm; }
      * { box-sizing: border-box; }
      body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; padding: 20px; background: #f8fafc; color: #111827; }
      .container { max-width: 1050px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #cbd5e1; }
      .toolbar { max-width: 1050px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; background: #1e293b; color: #fff; padding: 10px 18px; border-radius: 6px; }
      .btn { padding: 8px 16px; border-radius: 4px; font-family: system-ui, sans-serif; font-weight: bold; cursor: pointer; border: none; font-size: 13px; }
      .btn-print { background: #2563eb; color: #fff; }
      .btn-close { background: #475569; color: #fff; }
      table { border-collapse: collapse; width: 100%; }
      th, td { border: 1px solid #64748b; }
      @media print {
        .toolbar { display: none !important; }
        body { padding: 0 !important; background: #fff !important; }
        .container { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; }
        th, td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
    </style>
  </head>
  <body>
    <div class="toolbar">
      <div style="font-family: system-ui, sans-serif; font-size: 14px; font-weight: bold;">BẢN XEM TRƯỚC PHIẾU XUẤT KHO (CHUẨN A4)</div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-print" onclick="window.print()">🖨️ In Phiếu / Lưu PDF (A4)</button>
        <button class="btn btn-close" onclick="window.close()">✕ Đóng</button>
      </div>
    </div>
    <div class="container">
      ${content}
    </div>
  </body>
  </html>`;

  // Sử dụng Blob URL để chống lỗi chặn popup và đảm bảo hiển thị mượt mà không bị trắng trang
  const blob = new Blob([fullHtml], { type: 'text/html;charset=utf-8' });
  const blobUrl = URL.createObjectURL(blob);
  const win = window.open(blobUrl, '_blank');
  if (!win) {
    alert('Trình duyệt đang chặn cửa sổ pop-up. Bạn có thể bấm nút "In Phiếu / Lưu PDF" để in trực tiếp trong trang.');
  }
}

/**
 * =========================================================================
 * TAB 2: DANH SÁCH PHIẾU XUẤT KHO
 * =========================================================================
 */
async function loadIssuesList(page = 1) {
  const tbody = document.getElementById('issuesTableBody');
  tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div> Đang tải dữ liệu phiếu...</td></tr>';

  const search = document.getElementById('filterListSearch').value.trim();
  const group  = document.getElementById('filterListGroup').value;
  const status = document.getElementById('filterListStatus').value;
  const month  = document.getElementById('filterListMonth').value;

  const url = `api/warehouse.php?action=get_issues&search=${encodeURIComponent(search)}&group_name=${encodeURIComponent(group)}&status=${encodeURIComponent(status)}&month=${month}&year=2026&page=${page}&limit=12`;

  try {
    const res = await fetch(url);
    const data = await res.json();

    const issues = data.issues || data.data || [];
    const total = (data.total !== undefined) ? data.total : (data.total_rows !== undefined ? data.total_rows : issues.length);

    // Cập nhật các thẻ thống kê Pipeline KPI
    if (data.counts) {
      const elAll = document.getElementById('kpiTotalIssues');
      if (elAll) elAll.textContent = data.counts.all || 0;
      const elChk = document.getElementById('kpiPendingChecker');
      if (elChk) elChk.textContent = data.counts.pending_checker || 0;
      const elMgr = document.getElementById('kpiPendingManager');
      if (elMgr) elMgr.textContent = data.counts.pending_manager || 0;
      const elAdm = document.getElementById('kpiPendingAdminIssue');
      if (elAdm) elAdm.textContent = data.counts.pending_admin_issue || 0;
      const elHnd = document.getElementById('kpiPendingHandover');
      if (elHnd) elHnd.textContent = data.counts.pending_handover || 0;
      const elCmp = document.getElementById('kpiCompleted');
      if (elCmp) elCmp.textContent = data.counts.completed || 0;
    }

    if (!data.success || issues.length === 0) {
      tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-muted">Không tìm thấy phiếu xuất kho nào phù hợp.</td></tr>';
      document.getElementById('issuesPageInfo').textContent = 'Hiển thị 0 phiếu';
      document.getElementById('issuesPageBtns').innerHTML = '';
      document.getElementById('countMyIssues').textContent = '0';
      return;
    }

    document.getElementById('countMyIssues').textContent = total;
    document.getElementById('issuesPageInfo').textContent = `Hiển thị ${issues.length} trên tổng số ${total} phiếu`;

    let html = '';
    issues.forEach((iss, idx) => {
      const stt = ((data.page || page) - 1) * (data.limit || 12) + idx + 1;
      const statusBadge = getStatusBadge(iss.status);
      const typeBadge = (iss.issue_type === 'consumable') 
        ? '<span class="badge bg-light text-primary border">Tiêu hao định mức</span>' 
        : '<span class="badge bg-warning-subtle text-warning border">Bất thường</span>';

      const isPending = iss.status.startsWith('pending_');
      const actionBtn = isPending
        ? `<button class="app-btn app-btn-primary btn-sm py-1 px-2" type="button" onclick="openIssueDetailModal(${iss.id})">
             <span class="material-icons" style="font-size:14px;">gavel</span> Xét duyệt
           </button>`
        : `<button class="app-btn app-btn-secondary btn-sm py-1 px-2" type="button" onclick="openIssueDetailModal(${iss.id})">
             <span class="material-icons" style="font-size:14px;">visibility</span> Xem
           </button>`;

      const pdfBtn = `<a href="api/warehouse.php?action=render_issue_pdf_html&issue_id=${iss.id}" target="_blank" class="app-btn app-btn-outline btn-sm py-1 px-2 text-danger border-danger-subtle" title="Xem & In PDF (A4 - 5 Dấu Ký)">
        <span class="material-icons" style="font-size:14px;">picture_as_pdf</span>
      </a>`;
      const xlsBtn = `<a href="api/warehouse.php?action=export_issue_excel&issue_id=${iss.id}" class="app-btn app-btn-outline btn-sm py-1 px-2 text-success border-success-subtle" title="Xuất Excel Phiếu">
        <span class="material-icons" style="font-size:14px;">file_download</span>
      </a>`;

      html += `
        <tr>
          <td class="text-center text-muted fw-bold">${stt}</td>
          <td>
            <a href="javascript:void(0)" class="fw-bold font-monospace text-primary text-decoration-none" onclick="openIssueDetailModal(${iss.id})">
              ${escapeHtml(iss.issue_code)}
            </a>
          </td>
          <td><span class="badge bg-light text-dark border">${escapeHtml(iss.group_name)}</span></td>
          <td>${typeBadge}</td>
          <td class="text-center font-monospace small">T${iss.month}/${iss.year}</td>
          <td>
            <div class="text-truncate" style="max-width: 220px;" title="${escapeHtml(iss.purpose)}">
              ${escapeHtml(iss.purpose)}
            </div>
          </td>
          <td class="text-center fw-bold">${iss.total_items}</td>
          <td class="text-end font-monospace fw-bold text-success">${fmt1(iss.total_actual_qty)}</td>
          <td class="text-center">${statusBadge}</td>
          <td>
            <div class="fw-semibold small">${escapeHtml(iss.creator_name)}</div>
            <div class="text-muted" style="font-size: 11px;">${iss.created_at ? iss.created_at.substring(0, 16) : ''}</div>
          </td>
          <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
              ${actionBtn}
              ${pdfBtn}
              ${xlsBtn}
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    renderPagination(data.total_pages, data.page);
  } catch (err) {
    console.error('Lỗi loadIssuesList:', err);
    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Lỗi tải danh sách phiếu từ máy chủ.</td></tr>';
  }
}

function getStatusBadge(st) {
  switch (st) {
    case 'pending_checker':
      return '<span class="badge bg-warning text-dark"><span class="material-icons align-middle" style="font-size:13px;">schedule</span> 1. Chờ Kiểm Tra</span>';
    case 'pending_manager':
      return '<span class="badge text-white" style="background:#8b5cf6;"><span class="material-icons align-middle" style="font-size:13px;">rule</span> 2. Chờ Quản Lý</span>';
    case 'pending_admin_issue':
      return '<span class="badge bg-info text-dark"><span class="material-icons align-middle" style="font-size:13px;">warehouse</span> 3. Chờ Xuất Kho</span>';
    case 'pending_handover':
      return '<span class="badge bg-primary text-white"><span class="material-icons align-middle" style="font-size:13px;">handshake</span> 4. Chờ Bàn Giao</span>';
    case 'completed':
      return '<span class="badge bg-success text-white"><span class="material-icons align-middle" style="font-size:13px;">check_circle</span> 5. Hoàn Tất</span>';
    case 'rejected':
      return '<span class="badge bg-danger text-white"><span class="material-icons align-middle" style="font-size:13px;">cancel</span> Từ Chối</span>';
    default:
      return `<span class="badge bg-light text-dark">${st}</span>`;
  }
}

function renderPagination(totalPages, currentPage) {
  const container = document.getElementById('issuesPageBtns');
  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }
  let btns = '';
  for (let p = 1; p <= totalPages; p++) {
    btns += `<button class="btn btn-sm ${p === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadIssuesList(${p})">${p}</button>`;
  }
  container.innerHTML = btns;
}

function resetListFilter() {
  document.getElementById('filterListSearch').value = '';
  document.getElementById('filterListGroup').value = 'ALL';
  document.getElementById('filterListStatus').value = 'ALL';
  document.getElementById('filterListMonth').value = '0';
  loadIssuesList(1);
}

/**
 * =========================================================================
 * YÊU CẦU 2: CHI TIẾT PHIẾU XUẤT KHO & PHÊ DUYỆT TRỰC TIẾP TẠI MODAL
 * =========================================================================
 */
async function openIssueDetailModal(issueId) {
  const modal = new bootstrap.Modal(document.getElementById('modalIssueDetail'));
  const body = document.getElementById('detailModalBody');
  body.innerHTML = '<div class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div> Đang tải thông tin phiếu...</div>';
  modal.show();

  try {
    const res = await fetch(`api/warehouse.php?action=get_issue_detail&issue_id=${issueId}`);
    const data = await res.json();
    if (!data.success || !data.issue) {
      body.innerHTML = '<div class="alert alert-danger">Không tìm thấy thông tin phiếu xuất kho.</div>';
      return;
    }

    const iss = data.issue;
    currentViewingIssue = iss;

    document.getElementById('detailModalTitle').textContent = `Phiếu Xuất Kho: ${iss.issue_code}`;
    const badgeEl = document.getElementById('detailStatusBadge');
    badgeEl.outerHTML = `<span class="badge ms-2" id="detailStatusBadge">${getStatusBadge(iss.status)}</span>`;

    // Gắn link PDF và Excel vào footer modal
    const pdfBtn = document.getElementById('btnDetailModalPdf');
    if (pdfBtn) pdfBtn.href = `api/warehouse.php?action=render_issue_pdf_html&issue_id=${iss.id}`;
    const xlsBtn = document.getElementById('btnDetailModalExcel');
    if (xlsBtn) xlsBtn.href = `api/warehouse.php?action=export_issue_excel&issue_id=${iss.id}`;

    // 1. Timeline tiến trình 5 bước
    const timelineHtml = renderWorkflowTimeline(iss.status, iss.logs || []);

    // 2. Thông tin chung
    const infoHtml = `
      <div class="row g-3 mb-3 p-3 bg-light rounded border" style="font-size: 13px;">
        <div class="col-md-3">
          <div class="small text-muted">Nhóm công việc:</div>
          <strong class="text-main">${escapeHtml(iss.group_name)}</strong>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Kỳ xuất:</div>
          <strong class="text-main">Tháng ${iss.month}/${iss.year}</strong>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Người tạo phiếu:</div>
          <strong class="text-main">${escapeHtml(iss.creator_name)} (${iss.created_at ? iss.created_at.substring(0, 16) : ''})</strong>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Phân loại:</div>
          <strong class="text-primary">${iss.issue_type === 'consumable' ? 'Tiêu hao định mức' : 'Bất thường'}</strong>
        </div>
        <div class="col-12 border-top pt-2">
          <div class="small text-muted">Mục đích xuất:</div>
          <div>${escapeHtml(iss.purpose || '-')}</div>
          ${iss.reason_for_irregular ? `<div class="mt-1 small text-danger"><strong>Lý do bất thường:</strong> ${escapeHtml(iss.reason_for_irregular)}</div>` : ''}
        </div>
      </div>
    `;

    // 3. Khối PHÊ DUYỆT TRỰC TIẾP (Nếu phiếu chưa hoàn tất)
    let approvalBoxHtml = '';
    const isPending = iss.status.startsWith('pending_');
    if (isPending) {
      let stepName = '';
      let approveBtnLabel = 'Phê Duyệt Chuyển Tiếp';
      let extraInputs = '';

      if (iss.status === 'pending_checker') {
        stepName = 'BƯỚC 2: NGƯỜI KIỂM TRA PHÊ DUYỆT';
        approveBtnLabel = 'Xác Nhận Kiểm Tra & Chuyển Quản Lý';
      } else if (iss.status === 'pending_manager') {
        stepName = 'BƯỚC 3: QUẢN LÝ PHÊ DUYỆT';
        approveBtnLabel = 'Quản Lý Phê Duyệt Chuyển Thủ Kho';
      } else if (iss.status === 'pending_admin_issue') {
        stepName = 'BƯỚC 4: THỦ KHO / ADMIN XUẤT KHO';
        approveBtnLabel = 'Xác Nhận Xuất Kho (Trừ Tồn Thực Tế)';
        extraInputs = `<div class="alert alert-info py-2 px-3 small mb-2"><span class="material-icons align-middle" style="font-size:16px;">info</span> Thao tác này sẽ tự động trừ tồn kho thực tế và kích hoạt cảnh báo ROP nếu thiếu hàng.</div>`;
      } else if (iss.status === 'pending_handover') {
        stepName = 'BƯỚC 5: XÁC NHẬN BÀN GIAO HIỆN TRƯỜNG';
        approveBtnLabel = 'Xác Nhận Hoàn Tất Bàn Giao';
        extraInputs = `
          <div class="row g-2 mb-2">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Họ tên người nhận bàn giao *</label>
              <input type="text" id="mHandoverName" class="form-control form-control-sm" placeholder="Ví dụ: Nguyễn Văn A">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Mã nhân viên người nhận *</label>
              <input type="text" id="mHandoverCode" class="form-control form-control-sm" placeholder="Ví dụ: EMP-00123">
            </div>
          </div>
        `;
      }

      approvalBoxHtml = `
        <div class="card border border-2 border-primary p-3 mb-3 bg-light shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-1">
              <span class="material-icons">gavel</span>
              <span>${stepName}</span>
            </h6>
            <span class="badge bg-primary text-white font-monospace">Hành động phê duyệt</span>
          </div>
          ${extraInputs}
          <div class="mb-2">
            <label class="form-label small fw-bold text-muted">Ý kiến / Ghi chú xét duyệt:</label>
            <input type="text" id="detailApprovalComment" class="form-control form-control-sm" placeholder="Nhập ý kiến phê duyệt hoặc lý do từ chối (bắt buộc khi từ chối)...">
          </div>
          <div class="d-flex justify-content-end gap-2 pt-1">
            <button class="app-btn app-btn-danger btn-sm" type="button" onclick="submitModalApprovalAction(${iss.id}, '${iss.status}', 'reject')">
              <span class="material-icons" style="font-size:14px;">cancel</span> Từ Chối Phiếu
            </button>
            <button class="app-btn app-btn-primary btn-sm" type="button" onclick="submitModalApprovalAction(${iss.id}, '${iss.status}', 'approve')">
              <span class="material-icons" style="font-size:14px;">check_circle</span> ${approveBtnLabel}
            </button>
          </div>
        </div>
      `;
    }

    // 4. Bảng chi tiết mặt hàng với 1 chữ số thập phân
    let itemsHtml = `
      <h6 class="fw-bold mb-2 text-main d-flex align-items-center gap-1">
        <span class="material-icons text-primary fs-5">format_list_bulleted</span>
        <span>Danh Sách Vật Tư Đã Yêu Cầu (${iss.items ? iss.items.length : 0} mặt hàng)</span>
      </h6>
      <div class="table-responsive mb-3 border rounded">
        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 12px;">
          <thead class="table-light text-center">
            <tr>
              <th style="width: 35px;">STT</th>
              <th style="width: 45px;">Ảnh</th>
              <th style="text-align: left;">Mã & Tên Vật Tư</th>
              <th>Kệ BIN</th>
              <th>ĐVT</th>
              <th>Số máy (A)</th>
              <th>Số lần (B)</th>
              <th>Định mức (C)</th>
              <th>Lý Thuyết</th>
              <th>Tồn HT</th>
              <th class="table-primary text-primary fw-bold">Xuất Thực Tế</th>
              <th>Tồn Còn Lại</th>
              <th>Tháng Tồn</th>
              <th>Điểm ROP</th>
            </tr>
          </thead>
          <tbody>
    `;

    if (iss.items && iss.items.length > 0) {
      iss.items.forEach((it, idx) => {
        const imgTag = it.image_url 
          ? `<img src="${escapeHtml(it.image_url)}" class="thumb-small" onclick="previewImage('${escapeHtml(it.image_url)}', '${escapeHtml(it.item_name_vn)}')">` 
          : `<div class="thumb-small d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:9px;">No img</div>`;

        itemsHtml += `
          <tr class="${it.is_below_rop == 1 ? 'table-warning-subtle' : ''}">
            <td class="text-center text-muted fw-bold">${idx + 1}</td>
            <td class="text-center p-1">${imgTag}</td>
            <td>
              <div class="fw-bold font-monospace text-primary">${escapeHtml(it.item_code)}</div>
              <div>${escapeHtml(it.item_name_vn)}</div>
              ${it.category_type === 'irregular' && it.irregular_reason ? `<small class="text-danger">Lý do: ${escapeHtml(it.irregular_reason)}</small>` : ''}
            </td>
            <td class="text-center font-monospace">${escapeHtml(it.bin_location || '-')}</td>
            <td class="text-center">${escapeHtml(it.unit)}</td>
            <td class="text-center font-monospace">${parseInt(it.machines_count, 10) || 0}</td>
            <td class="text-center font-monospace">${parseInt(it.uses_per_machine, 10) || 0}</td>
            <td class="text-center font-monospace">${fmt1(it.norm_per_use)}</td>
            <td class="text-center font-monospace">${fmt1(it.theoretical_qty)}</td>
            <td class="text-center font-monospace">${fmt1(it.field_stock)}</td>
            <td class="text-center font-monospace fw-bold text-primary table-primary" style="font-size:13px;">${fmt1(it.actual_qty)}</td>
            <td class="text-center font-monospace fw-bold ${it.stock_after_issue <= it.reorder_point ? 'text-danger' : 'text-primary'}">${fmt1(it.stock_after_issue)}</td>
            <td class="text-center font-monospace">${fmt1(it.runway_months)} thg</td>
            <td class="text-center font-monospace">
              ${it.is_below_rop == 1 ? `<span class="badge bg-danger-subtle text-danger border">Dưới ROP (${fmt1(it.reorder_point)})</span>` : `<span class="badge bg-light text-muted border">${fmt1(it.reorder_point)}</span>`}
            </td>
          </tr>
        `;
      });
    } else {
      itemsHtml += '<tr><td colspan="14" class="text-center py-3 text-muted">Không có mặt hàng nào.</td></tr>';
    }
    itemsHtml += `</tbody></table></div>`;

    // 5. Nhật ký Audit Log
    let logsHtml = `
      <h6 class="fw-bold mb-2 text-main d-flex align-items-center gap-1">
        <span class="material-icons text-primary fs-5">history</span>
        <span>Lịch Sử Phê Duyệt & Bàn Giao (Audit Log)</span>
      </h6>
      <div class="table-responsive border rounded">
        <table class="table table-sm align-middle mb-0" style="font-size: 12px;">
          <thead class="table-light">
            <tr>
              <th style="width: 140px;">Thời Gian</th>
              <th style="width: 140px;">Người Thực Hiện</th>
              <th style="width: 120px;">Vai Trò</th>
              <th style="width: 160px;">Thao Tác</th>
              <th>Ghi Chú / Người Nhận Hiện Trường</th>
            </tr>
          </thead>
          <tbody>
    `;

    if (iss.logs && iss.logs.length > 0) {
      iss.logs.forEach(l => {
        let note = l.comment || '-';
        if (l.handover_receiver_name) {
          note += ` (Người nhận: <strong>${escapeHtml(l.handover_receiver_name)}</strong> - Mã: ${escapeHtml(l.handover_receiver_code || '-')})`;
        }
        logsHtml += `
          <tr>
            <td class="text-muted font-monospace">${l.created_at}</td>
            <td class="fw-semibold">${escapeHtml(l.actor_name)}</td>
            <td><span class="badge bg-light text-dark border">${escapeHtml(l.actor_role)}</span></td>
            <td><span class="badge bg-primary-subtle text-primary">${escapeHtml(l.action)}</span></td>
            <td>${note}</td>
          </tr>
        `;
      });
    } else {
      logsHtml += '<tr><td colspan="5" class="text-center py-2 text-muted">Chưa có lịch sử phê duyệt</td></tr>';
    }
    logsHtml += `</tbody></table></div>`;

    body.innerHTML = timelineHtml + infoHtml + approvalBoxHtml + itemsHtml + logsHtml;
  } catch (e) {
    console.error('Lỗi openIssueDetailModal:', e);
    body.innerHTML = '<div class="alert alert-danger">Lỗi khi tải chi tiết phiếu.</div>';
  }
}

/**
 * Xử lý lệnh xét duyệt trực tiếp tại modal của issue_request
 */
async function submitModalApprovalAction(issueId, currentStatus, actionType) {
  let step = '';
  if (currentStatus === 'pending_checker') step = 'checker';
  else if (currentStatus === 'pending_manager') step = 'manager';
  else if (currentStatus === 'pending_admin_issue') step = 'admin_issue';
  else if (currentStatus === 'pending_handover') step = 'handover';

  if (!step) return;

  const commentEl = document.getElementById('detailApprovalComment');
  const comment = commentEl ? commentEl.value.trim() : '';

  if (actionType === 'reject' && !comment) {
    alert('Vui lòng nhập lý do từ chối vào ô Ghi chú xét duyệt.');
    if (commentEl) commentEl.focus();
    return;
  }

  let receiverName = '';
  let receiverCode = '';
  if (step === 'handover' && actionType === 'approve') {
    receiverName = (document.getElementById('mHandoverName')?.value || '').trim();
    receiverCode = (document.getElementById('mHandoverCode')?.value || '').trim();
    if (!receiverName || !receiverCode) {
      alert('Vui lòng nhập đầy đủ Họ tên và Mã nhân viên người nhận bàn giao!');
      return;
    }
  }

  const confirmMsg = (actionType === 'approve') 
    ? (step === 'admin_issue' ? 'Xác nhận XUẤT KHO và trừ tồn kho thực tế?' : 'Bạn có chắc chắn muốn phê duyệt bước này?')
    : 'Bạn có chắc chắn muốn TỪ CHỐI phiếu xuất kho này?';

  if (!confirm(confirmMsg)) return;

  const formData = new FormData();
  formData.append('issue_id', issueId);
  formData.append('step', step);
  formData.append('action_type', actionType);
  formData.append('comment', comment);
  formData.append('receiver_name', receiverName);
  formData.append('receiver_code', receiverCode);

  try {
    const res = await fetch('api/warehouse.php?action=approve_step', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message || 'Thao tác thành công!');
      openIssueDetailModal(issueId);
      loadIssuesList(1);
    } else {
      alert(data.message || 'Thao tác thất bại.');
    }
  } catch (err) {
    console.error('Lỗi approve_step:', err);
    alert('Lỗi kết nối khi gửi xét duyệt.');
  }
}

function renderWorkflowTimeline(status, logs) {
  const steps = [
    { key: 'create', num: 1, title: '1. Tạo Phiếu', sub: 'Nhân viên yêu cầu' },
    { key: 'checker', num: 2, title: '2. Kiểm Tra', sub: 'Người kiểm tra' },
    { key: 'manager', num: 3, title: '3. Quản Lý', sub: 'Trưởng bộ phận' },
    { key: 'admin_issue', num: 4, title: '4. Xuất Kho', sub: 'Thủ kho xuất hàng' },
    { key: 'handover', num: 5, title: '5. Bàn Giao', sub: 'Hiện trường xác nhận' }
  ];

  let currentStepIdx = 1;
  if (status === 'pending_checker') currentStepIdx = 2;
  else if (status === 'pending_manager') currentStepIdx = 3;
  else if (status === 'pending_admin_issue') currentStepIdx = 4;
  else if (status === 'pending_handover') currentStepIdx = 5;
  else if (status === 'completed') currentStepIdx = 6;
  else if (status === 'rejected' || status === 'cancelled') currentStepIdx = -1;

  let html = '<div class="workflow-timeline">';
  steps.forEach(s => {
    let nodeClass = '';
    let icon = s.num;

    if (currentStepIdx === -1) {
      nodeClass = (s.num === 1) ? 'completed' : 'rejected';
      if (s.num > 1) icon = '✕';
    } else if (s.num < currentStepIdx) {
      nodeClass = 'completed';
      icon = '✓';
    } else if (s.num === currentStepIdx) {
      nodeClass = 'active';
    }

    html += `
      <div class="workflow-step-node ${nodeClass}">
        <div class="workflow-step-circle">${icon}</div>
        <div class="workflow-step-label">${s.title}</div>
        <div class="workflow-step-sub">${s.sub}</div>
      </div>
    `;
  });
  html += '</div>';
  return html;
}

function previewImage(url, title) {
  document.getElementById('previewImageTag').src = url;
  document.getElementById('previewImageTitle').textContent = title || 'Vật tư';
  new bootstrap.Modal(document.getElementById('modalImagePreview')).show();
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
}
</script>
