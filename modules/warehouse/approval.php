<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Quy Trình Phê Duyệt & Bàn Giao (Workflow Approval)
 * DX Plastic Group - Factory Management System
 */

$currentMonth = intval(date('m'));
$currentYear  = intval(date('Y'));
$currentUser  = $_SESSION['user'] ?? [
    'id'       => $_SESSION['user_id'] ?? 1,
    'username' => $_SESSION['username'] ?? 'user',
    'fullname' => $_SESSION['fullname'] ?? 'Người dùng',
    'role'     => $_SESSION['role'] ?? 'viewer'
];
$userRole = $currentUser['role'] ?? 'viewer';
$userName = $currentUser['username'] ?? '';
$userFullname = $currentUser['fullname'] ?? $userName;
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang Chuẩn Công Nghiệp -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">verified_user</span>
        <span>QUY TRÌNH PHÊ DUYỆT & BÀN GIAO XUẤT VẬT TƯ</span>
        <span class="material-icons text-primary" style="font-size: 28px;">fact_check</span>
        <span>QUẢN LÝ DANH SÁCH & PHÊ DUYỆT PHIẾU XUẤT VẬT TƯ</span>
      </h1>
      <p class="app-page-subtitle">Xử lý 5 bước phê duyệt chuẩn: Nhân viên tạo -> Người kiểm tra -> Quản lý phê duyệt -> Admin xuất kho -> Bàn giao hiện trường</p>
      <p class="app-page-subtitle">Quản lý tập trung toàn bộ phiếu yêu cầu xuất kho và quy trình phê duyệt 5 cấp: Người lập -> Người kiểm tra -> Quản lý phê duyệt -> Admin xuất kho -> Bàn giao hiện trường</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button class="app-btn app-btn-success" onclick="exportMonthlyReportExcel()" title="Xuất báo cáo tổng hợp các phiếu trong tháng ra file Excel">
        <span class="material-icons">file_download</span>
        <span>Xuất Excel Báo Cáo</span>
      </button>
      <a href="index.php?mainpage=warehouse&subpage=issue_request" class="app-btn app-btn-outline">
        <span class="material-icons">post_add</span>
        <span>Tạo Phiếu Mới</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=materials" class="app-btn app-btn-secondary">
        <span class="material-icons">category</span>
        <span>Danh Mục Vật Tư</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=dashboard" class="app-btn app-btn-primary">
        <span class="material-icons">dashboard</span>
        <span>Dashboard Kho</span>
      </a>
    </div>
  </div>

  <!-- Quy trình 5 bước tổng quan (Workflow Pipeline Cards) -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
      <div class="app-card p-3 border h-100 text-center workflow-stat-card" style="border-top: 4px solid #3b82f6 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Bước 1: Khởi tạo</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-primary" style="font-size: 24px;">note_add</span>
          <span class="h4 mb-0 fw-bold" id="wfCountCreated">0</span>
        </div>
        <div class="small text-muted">Nhân viên tạo phiếu</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="app-card p-3 border h-100 text-center workflow-stat-card" style="border-top: 4px solid #f59e0b !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Bước 2: Kiểm tra</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-warning" style="font-size: 24px;">fact_check</span>
          <span class="h4 mb-0 fw-bold text-warning" id="wfCountChecker">0</span>
        </div>
        <div class="small text-muted">Chờ người kiểm tra duyệt</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="app-card p-3 border h-100 text-center workflow-stat-card" style="border-top: 4px solid #8b5cf6 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Bước 3: Quản lý</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons" style="color: #8b5cf6; font-size: 24px;">verified</span>
          <span class="h4 mb-0 fw-bold" style="color: #8b5cf6;" id="wfCountManager">0</span>
        </div>
        <div class="small text-muted">Chờ quản lý phê duyệt</div>
      </div>
    </div>
    <div class="col-6 col-md-6 col-xl">
      <div class="app-card p-3 border h-100 text-center workflow-stat-card" style="border-top: 4px solid #06b6d4 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Bước 4: Xuất kho</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-info" style="font-size: 24px;">inventory_2</span>
          <span class="h4 mb-0 fw-bold text-info" id="wfCountAdminIssue">0</span>
        </div>
        <div class="small text-muted">Thủ kho / Admin trừ kho</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl">
      <div class="app-card p-3 border h-100 text-center workflow-stat-card" style="border-top: 4px solid #10b981 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Bước 5: Bàn giao</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-success" style="font-size: 24px;">handshake</span>
          <span class="h4 mb-0 fw-bold text-success" id="wfCountHandover">0</span>
        </div>
        <div class="small text-muted">Hiện trường nhận vật tư</div>
      </div>
    </div>
  </div>

  <!-- Bộ Lọc Trạng Thái Tabs -->
  <div class="app-card p-3 mb-4 border">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom pb-3">
      <!-- Tabs điều hướng nhanh theo bước -->
      <div class="d-flex flex-wrap gap-2" id="approvalTabs">
        <button class="app-btn active" data-status="ALL" onclick="filterStatusTab('ALL', this)">
          <span>Tất cả phiếu</span>
          <span class="badge bg-secondary ms-1" id="badgeAll">0</span>
        </button>
        <button class="app-btn app-btn-outline text-warning border-warning" data-status="pending_checker" onclick="filterStatusTab('pending_checker', this)">
          <span class="material-icons" style="font-size: 16px;">pending</span>
          <span>Chờ Kiểm Tra</span>
          <span class="badge bg-warning text-dark ms-1" id="badgePendingChecker">0</span>
        </button>
        <button class="app-btn app-btn-outline" style="color: #8b5cf6; border-color: #8b5cf6;" data-status="pending_manager" onclick="filterStatusTab('pending_manager', this)">
          <span class="material-icons" style="font-size: 16px;">rule</span>
          <span>Chờ Quản Lý Duyệt</span>
          <span class="badge ms-1 text-white" style="background: #8b5cf6;" id="badgePendingManager">0</span>
        </button>
        <button class="app-btn app-btn-outline text-info border-info" data-status="pending_admin_issue" onclick="filterStatusTab('pending_admin_issue', this)">
          <span class="material-icons" style="font-size: 16px;">warehouse</span>
          <span>Chờ Admin Xuất Kho</span>
          <span class="badge bg-info text-dark ms-1" id="badgePendingAdmin">0</span>
        </button>
        <button class="app-btn app-btn-outline text-success border-success" data-status="pending_handover" onclick="filterStatusTab('pending_handover', this)">
          <span class="material-icons" style="font-size: 16px;">handshake</span>
          <span>Chờ Bàn Giao</span>
          <span class="badge bg-success ms-1" id="badgePendingHandover">0</span>
        </button>
        <button class="app-btn app-btn-outline" data-status="completed" onclick="filterStatusTab('completed', this)">
          <span class="material-icons text-success" style="font-size: 16px;">check_circle</span>
          <span>Hoàn tất</span>
          <span class="badge bg-secondary ms-1" id="badgeCompleted">0</span>
        </button>
        <button class="app-btn app-btn-outline" data-status="rejected" onclick="filterStatusTab('rejected', this)">
          <span class="material-icons text-danger" style="font-size: 16px;">cancel</span>
          <span>Từ chối</span>
          <span class="badge bg-secondary ms-1" id="badgeRejected">0</span>
        </button>
      </div>

      <!-- Nút Làm mới -->
      <button class="app-btn app-btn-outline btn-sm" onclick="loadWorkflowIssues()">
        <span class="material-icons">refresh</span>
        <span>Làm mới</span>
      </button>
    </div>

    <!-- Toolbar bộ lọc nâng cao -->
    <div class="row g-2 align-items-center">
      <div class="col-md-3">
        <label class="form-label small fw-bold mb-1">Nhóm Công Việc</label>
        <select class="form-select form-select-sm" id="filterGroup" onchange="loadWorkflowIssues()">
          <option value="ALL">-- Tất cả nhóm --</option>
          <option value="Thiết bị">Nhóm Thiết bị</option>
          <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
          <option value="Sản xuất">Nhóm Sản xuất</option>
          <option value="Nghiền">Nhóm Nghiền</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Loại Phiếu</label>
        <select class="form-select form-select-sm" id="filterType" onchange="loadWorkflowIssues()">
          <option value="ALL">-- Tất cả loại --</option>
          <option value="consumable">Tiêu hao định mức</option>
          <option value="irregular">Bất thường</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Kỳ Áp Dụng</label>
        <div class="d-flex gap-1">
          <select class="form-select form-select-sm" id="filterMonth" onchange="loadWorkflowIssues()">
            <option value="0" selected>Tất cả tháng</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>">Tháng <?= $m ?></option>
            <?php endfor; ?>
          </select>
          <select class="form-select form-select-sm" id="filterYear" onchange="loadWorkflowIssues()">
            <option value="2026" selected>2026</option>
            <option value="2025">2025</option>
          </select>
        </div>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-bold mb-1">Tìm Kiếm Nhanh</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text"><span class="material-icons" style="font-size: 16px;">search</span></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Tìm theo mã phiếu, người lập, mục đích..." onkeyup="if(event.key === 'Enter') loadWorkflowIssues()">
          <button class="app-btn app-btn-primary btn-sm" onclick="loadWorkflowIssues()">Tìm kiếm</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Danh sách phiếu chờ xét duyệt -->
  <div class="app-card border overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="tableWorkflowIssues">
        <thead class="table-light">
          <tr class="text-uppercase small text-muted">
            <th class="ps-3" style="width: 140px;">Mã Phiếu</th>
            <th>Nhóm & Kỳ Áp Dụng</th>
            <th>Loại Phiếu & Mục Đích</th>
            <th>Người Lập</th>
            <th class="text-center">Số Mặt Hàng</th>
            <th class="text-center" style="width: 220px;">Tiến Độ 5 Bước</th>
            <th class="text-center">Trạng Thái</th>
            <th class="text-end pe-3" style="width: 130px;">Hành Động</th>
          </tr>
        </thead>
        <tbody id="tbodyWorkflowIssues">
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>Đang tải danh sách phiếu xét duyệt...</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Phân trang -->
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
      <div class="small text-muted" id="paginationInfo">Hiển thị 0 trên 0 phiếu</div>
      <div class="d-flex gap-1" id="paginationControls"></div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL CHI TIẾT & XÉT DUYỆT PHIẾU XUẤT KHO (5-STEP WORKFLOW MODAL)
     ========================================================================= -->
<div class="modal fade" id="modalWorkflowDetail" tabindex="-1" aria-labelledby="modalWorkflowDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <div>
          <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2" id="modalWorkflowDetailLabel">
            <span class="material-icons text-primary">fact_check</span>
            <span>XÉT DUYỆT PHIẾU XUẤT VẬT TƯ: <span id="mDetailIssueCode" class="text-primary font-monospace">PXK-...</span></span>
          </h5>
          <div class="small text-muted" id="mDetailSubTitle">Kỳ áp dụng: Tháng 8/2026 | Nhóm Thiết bị</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4">
        <!-- 1. Thanh Tiến Trình 5 Bước (Visual 5-Step Progress Bar) -->
        <div class="card p-3 mb-4 bg-light border-0 shadow-sm">
          <div class="text-uppercase small fw-bold text-muted mb-3 text-center">Tiến trình phê duyệt & Bàn giao (5 Cấp)</div>
          <div class="workflow-stepper" id="mWorkflowStepper">
            <!-- Step 1 -->
            <div class="step-item" id="wfStep1">
              <div class="step-circle"><span class="material-icons">note_add</span></div>
              <div class="step-content">
                <div class="step-title">1. Khởi tạo</div>
                <div class="step-desc" id="wfStep1Desc">Người lập phiếu</div>
                <div class="step-time small text-muted" id="wfStep1Time">--:--</div>
              </div>
            </div>
            <div class="step-line" id="wfLine1"></div>

            <!-- Step 2 -->
            <div class="step-item" id="wfStep2">
              <div class="step-circle"><span class="material-icons">fact_check</span></div>
              <div class="step-content">
                <div class="step-title">2. Người Kiểm Tra</div>
                <div class="step-desc" id="wfStep2Desc">Chờ duyệt</div>
                <div class="step-time small text-muted" id="wfStep2Time">--:--</div>
              </div>
            </div>
            <div class="step-line" id="wfLine2"></div>

            <!-- Step 3 -->
            <div class="step-item" id="wfStep3">
              <div class="step-circle"><span class="material-icons">verified</span></div>
              <div class="step-content">
                <div class="step-title">3. Quản Lý Phê Duyệt</div>
                <div class="step-desc" id="wfStep3Desc">Chờ duyệt</div>
                <div class="step-time small text-muted" id="wfStep3Time">--:--</div>
              </div>
            </div>
            <div class="step-line" id="wfLine3"></div>

            <!-- Step 4 -->
            <div class="step-item" id="wfStep4">
              <div class="step-circle"><span class="material-icons">inventory_2</span></div>
              <div class="step-content">
                <div class="step-title">4. Admin Xuất Kho</div>
                <div class="step-desc" id="wfStep4Desc">Chờ trừ kho</div>
                <div class="step-time small text-muted" id="wfStep4Time">--:--</div>
              </div>
            </div>
            <div class="step-line" id="wfLine4"></div>

            <!-- Step 5 -->
            <div class="step-item" id="wfStep5">
              <div class="step-circle"><span class="material-icons">handshake</span></div>
              <div class="step-content">
                <div class="step-title">5. Bàn Giao Hiện Trường</div>
                <div class="step-desc" id="wfStep5Desc">Chờ nhận hàng</div>
                <div class="step-time small text-muted" id="wfStep5Time">--:--</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Thông tin chung phiếu yêu cầu -->
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="border rounded p-2 bg-white">
              <span class="text-muted small d-block">Nhóm công việc</span>
              <strong id="mDetailGroup" class="text-main">--</strong>
            </div>
          </div>
          <div class="col-md-3">
            <div class="border rounded p-2 bg-white">
              <span class="text-muted small d-block">Loại xuất</span>
              <strong id="mDetailType" class="text-main">--</strong>
            </div>
          </div>
          <div class="col-md-3">
            <div class="border rounded p-2 bg-white">
              <span class="text-muted small d-block">Người tạo phiếu</span>
              <strong id="mDetailCreator" class="text-main">--</strong>
            </div>
          </div>
          <div class="col-md-3">
            <div class="border rounded p-2 bg-white">
              <span class="text-muted small d-block">Thời gian tạo</span>
              <strong id="mDetailCreatedAt" class="text-main">--</strong>
            </div>
          </div>
          <div class="col-12" id="mDetailPurposeContainer">
            <div class="border rounded p-2 bg-white">
              <span class="text-muted small d-block">Mục đích xuất & Ghi chú</span>
              <span id="mDetailPurpose" class="fw-semibold">--</span>
            </div>
          </div>
        </div>

        <!-- 3. Bảng danh sách vật tư yêu cầu xuất -->
        <div class="card border mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
              <span class="material-icons text-primary" style="font-size: 20px;">format_list_bulleted</span>
              <span>Danh Sách Vật Tư Chi Tiết (<span id="mDetailItemCount">0</span> món)</span>
            </h6>
            <div class="badge bg-primary px-3 py-1 font-monospace" id="mDetailStatusBadge">ĐANG CHỜ</div>
          </div>

          <!-- Quy ước màu sắc trực quan (Color Legend Bar) -->
          <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 border-bottom bg-light" style="font-size: 12px;">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <span class="fw-bold text-muted">Quy ước màu cột:</span>
              <span class="d-flex align-items-center gap-1">
                <span class="d-inline-block rounded border" style="width: 14px; height: 14px; background: #fffbeb; border-color: #f59e0b !important;"></span>
                <span><strong>Cột cần nhập dữ liệu:</strong> Số máy A, Số lần B, Tồn hiện trường, Tái sử dụng</span>
              </span>
              <span class="d-flex align-items-center gap-1">
                <span class="d-inline-block rounded border" style="width: 14px; height: 14px; background: #eff6ff; border-color: #3b82f6 !important;"></span>
                <span><strong>Cột tự động tính toán:</strong> SL Lý thuyết, SL Xuất thực tế, Tồn sau xuất, Runway</span>
              </span>
            </div>
            <div class="text-muted fst-italic">Tất cả số lượng hiển thị chuẩn 1 chữ số thập phân (.0)</div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" id="mDetailItemsTable">
              <thead class="text-center small">
                <tr>
                  <th style="width: 40px;" class="table-light text-muted">STT</th>
                  <th style="width: 60px;" class="table-light text-muted">Ảnh</th>
                  <th class="table-light text-muted">Mã VT / SAP</th>
                  <th class="table-light text-muted">Tên Vật Tư & Quy Cách</th>
                  <th style="width: 150px;" class="th-input-data">
                    <span class="material-icons text-warning align-middle" style="font-size: 14px;">edit_note</span>
                    Định Mức Lập Phiếu<br><small>(A × B = Tổng / C)</small>
                  </th>
                  <th style="width: 120px;" class="th-input-data">
                    <span class="material-icons text-warning align-middle" style="font-size: 14px;">edit_note</span>
                    Tồn HT / Tái SD
                  </th>
                  <th style="width: 100px;" class="th-auto-calc">
                    <span class="material-icons text-primary align-middle" style="font-size: 14px;">functions</span>
                    SL Lý Thuyết
                  </th>
                  <th style="width: 120px;" class="th-auto-calc fw-bold text-primary">
                    <span class="material-icons text-primary align-middle" style="font-size: 14px;">local_shipping</span>
                    SL Xuất Thực Tế
                  </th>
                  <th style="width: 130px;" class="th-auto-calc">
                    <span class="material-icons text-primary align-middle" style="font-size: 14px;">inventory</span>
                    Tồn Kho<br><small>Trước &rarr; Sau xuất</small>
                  </th>
                  <th style="width: 110px;" class="th-auto-calc">
                    <span class="material-icons text-primary align-middle" style="font-size: 14px;">speed</span>
                    Tháng Còn Lại<br><small>(Runway)</small>
                  </th>
                </tr>
              </thead>
              <tbody id="mDetailItemsTbody">
                <!-- Nội dung được nạp từ JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- 4. Khối Hành Động Xét Duyệt Tương Ứng (Dynamic Action Form) -->
        <div id="mActionContainer" class="card border border-2 border-primary p-3 mb-4 bg-light">
          <h6 class="fw-bold text-primary d-flex align-items-center gap-2 mb-3">
            <span class="material-icons">gavel</span>
            <span id="mActionTitle">XỬ LÝ BƯỚC PHÊ DUYỆT HIỆN TẠI</span>
          </h6>

          <!-- Form phê duyệt / từ chối chung -->
          <div class="row g-3">
            <!-- Nếu ở bước Admin Xuất Kho: Hiển thị cảnh báo trừ kho -->
            <div class="col-12 d-none" id="mAdminIssueNotice">
              <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                <span class="material-icons">info</span>
                <span><strong>Lưu ý Thủ kho:</strong> Thao tác "Xuất Kho" sẽ tự động <strong>trừ tồn kho thực tế</strong> của toàn bộ vật tư trong phiếu. Nếu tồn kho sau xuất $\le$ ROP (Điểm đặt hàng lại), hệ thống sẽ tự động kích hoạt cảnh báo mua hàng gửi đến bộ phận liên quan.</span>
              </div>
            </div>

            <!-- Nếu ở bước Bàn giao hiện trường: Nhập tên và mã người nhận -->
            <div class="col-md-6 d-none" id="mHandoverReceiverNameCol">
              <label class="form-label small fw-bold">Họ tên người nhận bàn giao <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="mHandoverReceiverName" placeholder="Ví dụ: Nguyễn Văn A">
            </div>
            <div class="col-md-6 d-none" id="mHandoverReceiverCodeCol">
              <label class="form-label small fw-bold">Mã nhân viên người nhận <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="mHandoverReceiverCode" placeholder="Ví dụ: EMP-00123">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Ý kiến / Ghi chú xét duyệt</label>
              <textarea class="form-control form-control-sm" id="mActionComment" rows="2" placeholder="Nhập ý kiến phê duyệt, ghi chú bàn giao hoặc lý do từ chối (bắt buộc khi từ chối)..."></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
              <button type="button" class="app-btn app-btn-danger" id="mBtnReject" onclick="submitApprovalAction('reject')">
                <span class="material-icons">cancel</span>
                <span>Từ Chối Phiếu</span>
              </button>
              <button type="button" class="app-btn app-btn-primary" id="mBtnApprove" onclick="submitApprovalAction('approve')">
                <span class="material-icons">check_circle</span>
                <span id="mBtnApproveText">Phê Duyệt Chuyển Tiếp</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Thông báo không có quyền hoặc phiếu đã kết thúc -->
        <div id="mNoActionNotice" class="alert alert-secondary d-none py-2 px-3 small d-flex align-items-center gap-2 mb-4">
          <span class="material-icons">lock</span>
          <span id="mNoActionText">Phiếu này đã hoàn tất hoặc tài khoản của bạn không được phân quyền xử lý ở bước này.</span>
        </div>

        <!-- 5. Khung 5 Dấu Ký & Xác Nhận Chuẩn (Approval Stamps) -->
        <div class="card border mb-4">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0 fw-bold small text-uppercase text-muted d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 18px;">draw</span>
              <span>Tiến Trình 5 Chữ Ký & Dấu Xác Nhận Theo Phiếu (Signatures & Stamps)</span>
            </h6>
          </div>
          <div class="p-3">
            <div class="row g-2 text-center" id="mApprovalStampsContainer">
              <!-- Render động 5 dấu ký từ JS -->
            </div>
          </div>
        </div>

        <!-- 6. Lịch sử luồng phê duyệt (Workflow Audit Logs) -->
        <div class="card border">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0 fw-bold small text-uppercase text-muted d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 18px;">history</span>
              <span>Lịch Sử Thao Tác & Xét Duyệt (Audit Log)</span>
            </h6>
          </div>
          <div class="p-3">
            <div class="workflow-logs-timeline" id="mWorkflowLogsContainer">
              <!-- Logs được tải từ API -->
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light d-flex justify-content-between">
        <div class="d-flex gap-2">
          <button type="button" id="btnModalPdf" onclick="openPdfPreviewModal(currentActionIssueId)" class="app-btn app-btn-outline btn-sm text-danger border-danger d-flex align-items-center gap-1" title="Xem trước bản in A4 PDF có 5 dấu ký">
            <span class="material-icons" style="font-size: 16px;">picture_as_pdf</span>
            <span>Xem / In PDF (A4)</span>
          </button>
          <a id="btnModalExcel" href="#" class="app-btn app-btn-outline btn-sm text-success border-success d-flex align-items-center gap-1" title="Xuất dữ liệu phiếu ra Excel">
            <span class="material-icons" style="font-size: 16px;">file_download</span>
            <span>Xuất Excel Phiếu</span>
          </a>
          <button type="button" id="btnModalDeleteIssue" onclick="deleteCurrentIssueFromModal()" class="app-btn app-btn-outline btn-sm text-danger border-danger-subtle d-flex align-items-center gap-1" title="Xóa phiếu yêu cầu xuất kho này">
            <span class="material-icons" style="font-size: 16px;">delete</span>
            <span>Xóa Phiếu</span>
          </button>
        </div>
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL XEM TRƯỚC / IN PHIẾU XUẤT KHO A4 PDF (5 DẤU KÝ)
     ========================================================================= -->
<div class="modal fade" id="modalPreviewPDF" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <h6 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">picture_as_pdf</span>
          <span>XEM TRƯỚC BẢN IN PHIẾU XUẤT KHO (CHUẨN A4 - 5 DẤU KÝ)</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 bg-white" id="pdfPreviewContent">
        <!-- Nạp động iframe từ API render_issue_pdf_html -->
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

<style>
/* 5-Step Stepper Styling */
.workflow-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  margin: 10px 0;
}
.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  z-index: 2;
  min-width: 110px;
}
.step-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
  font-weight: bold;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}
.step-item.active .step-circle {
  background: #3b82f6;
  color: #ffffff;
  border-color: #93c5fd;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
}
.step-item.completed .step-circle {
  background: #10b981;
  color: #ffffff;
}
.step-item.rejected .step-circle {
  background: #ef4444;
  color: #ffffff;
}
.step-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--dx-text-main, #1e293b);
}
.step-desc {
  font-size: 11.5px;
  color: var(--dx-text-muted, #64748b);
}
.step-line {
  flex-grow: 1;
  height: 4px;
  background: #e2e8f0;
  margin: 0 4px;
  margin-bottom: 35px;
  transition: all 0.3s ease;
}
.step-line.completed {
  background: #10b981;
}

/* Workflow Logs Timeline */
.workflow-logs-timeline {
  position: relative;
  padding-left: 20px;
}
.workflow-logs-timeline::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 5px;
  bottom: 5px;
  width: 2px;
  background: #e2e8f0;
}
.log-item {
  position: relative;
  margin-bottom: 16px;
}
.log-item:last-child {
  margin-bottom: 0;
}
.log-dot {
  position: absolute;
  left: -20px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #3b82f6;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.log-dot.success { background: #10b981; }
.log-dot.warning { background: #f59e0b; }
.log-dot.danger  { background: #ef4444; }

/* Status pill dots */
.dot-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 4px;
}
.dot-indicator.done { background-color: #10b981; }
.dot-indicator.current { background-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3); }
.dot-indicator.wait { background-color: #cbd5e1; }
.dot-indicator.reject { background-color: #ef4444; }

/* Color-coded Column Styles */
.th-input-data {
  background-color: #fffbeb !important;
  color: #b45309 !important;
  border-bottom: 2px solid #f59e0b !important;
  vertical-align: middle;
}
.col-input-data {
  background-color: #fffdf5 !important;
}
.th-auto-calc {
  background-color: #eff6ff !important;
  color: #1d4ed8 !important;
  border-bottom: 2px solid #3b82f6 !important;
  vertical-align: middle;
}
.col-auto-calc {
  background-color: #f8faff !important;
}
</style>

<script>
// Helper hiển thị số chuẩn 1 chữ số thập phân
function fmt1(val) {
  if (val === null || val === undefined || isNaN(val) || val === '') return '0.0';
  return Number(val).toFixed(1);
}

let currentFilterStatus = 'ALL';
let currentIssueDetail = null;
let currentStepForAction = '';
let currentActionIssueId = 0;
let workflowApproversData = null;

// Lấy thông tin người dùng từ PHP
const sessionUser = {
  id: <?= json_encode($currentUser['id'] ?? 1) ?>,
  username: <?= json_encode($userName) ?>,
  fullname: <?= json_encode($userFullname) ?>,
  role: <?= json_encode($userRole) ?>,
  permissions: <?= json_encode($_SESSION['permissions'] ?? []) ?>
};

document.addEventListener('DOMContentLoaded', function() {
  loadWorkflowApprovers();
  loadWorkflowIssues();

  // Tự động mở modal xét duyệt khi click từ thông báo có chứa issue_id
  const urlParams = new URLSearchParams(window.location.search);
  const targetIssueId = urlParams.get('issue_id');
  if (targetIssueId) {
    setTimeout(() => {
      openWorkflowModal(parseInt(targetIssueId, 10));
    }, 400);
  }
});

// 1. Tải danh sách người phê duyệt được cấp quyền
function loadWorkflowApprovers() {
  fetch('api/warehouse.php?action=get_approvers')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        workflowApproversData = res.approvers;
      }
    })
    .catch(err => console.error('Error fetching approvers:', err));
}

// 2. Chuyển đổi tab trạng thái
function filterStatusTab(status, btn) {
  currentFilterStatus = status;
  document.querySelectorAll('#approvalTabs button').forEach(b => {
    b.classList.remove('active');
    b.classList.add('app-btn-outline');
  });
  if (btn) {
    btn.classList.add('active');
    btn.classList.remove('app-btn-outline');
  }
  loadWorkflowIssues(1);
}

// 3. Tải danh sách phiếu xuất kho theo bộ lọc
function loadWorkflowIssues(page = 1) {
  const tbody = document.getElementById('tbodyWorkflowIssues');
  tbody.innerHTML = `
    <tr>
      <td colspan="8" class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
        <div>Đang tải dữ liệu phiếu...</div>
      </td>
    </tr>`;

  const groupName = document.getElementById('filterGroup').value;
  const issueType = document.getElementById('filterType').value;
  const month     = document.getElementById('filterMonth').value;
  const year      = document.getElementById('filterYear').value;
  const search    = document.getElementById('filterSearch').value.trim();

  const url = `api/warehouse.php?action=get_issues&status=${encodeURIComponent(currentFilterStatus)}&group_name=${encodeURIComponent(groupName)}&issue_type=${encodeURIComponent(issueType)}&month=${month}&year=${year}&search=${encodeURIComponent(search)}&page=${page}&limit=15`;

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${res.message || 'Lỗi tải dữ liệu'}</td></tr>`;
        return;
      }

      // Cập nhật số liệu các badge
      updatePipelineCounts(res.counts || {});

      const issuesList = res.issues || res.data || [];
      const totalCount = res.total || res.total_rows || 0;
      renderWorkflowIssuesTable(issuesList, totalCount, page, 15);
    })
    .catch(err => {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Không thể kết nối máy chủ API.</td></tr>`;
    });
}

// 4. Cập nhật số liệu badges & cards
function updatePipelineCounts(counts) {
  const allCount = (counts.pending_checker || 0) + (counts.pending_manager || 0) + 
                   (counts.pending_admin_issue || 0) + (counts.pending_handover || 0) + 
                   (counts.completed || 0) + (counts.rejected || 0);

  document.getElementById('badgeAll').textContent = allCount;
  document.getElementById('badgePendingChecker').textContent = counts.pending_checker || 0;
  document.getElementById('badgePendingManager').textContent = counts.pending_manager || 0;
  document.getElementById('badgePendingAdmin').textContent = counts.pending_admin_issue || 0;
  document.getElementById('badgePendingHandover').textContent = counts.pending_handover || 0;
  document.getElementById('badgeCompleted').textContent = counts.completed || 0;
  document.getElementById('badgeRejected').textContent = counts.rejected || 0;

  document.getElementById('wfCountCreated').textContent = allCount;
  document.getElementById('wfCountChecker').textContent = counts.pending_checker || 0;
  document.getElementById('wfCountManager').textContent = counts.pending_manager || 0;
  document.getElementById('wfCountAdminIssue').textContent = counts.pending_admin_issue || 0;
  document.getElementById('wfCountHandover').textContent = counts.pending_handover || 0;
}

// 5. Render bảng phiếu
function renderWorkflowIssuesTable(issues, total, page, limit) {
  const tbody = document.getElementById('tbodyWorkflowIssues');
  if (issues.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-center py-5 text-muted">
          <span class="material-icons mb-2" style="font-size: 40px; color: #cbd5e1;">inbox</span>
          <p class="mb-0">Không có phiếu xuất kho nào phù hợp với bộ lọc hiện tại.</p>
        </td>
      </tr>`;
    document.getElementById('paginationInfo').textContent = 'Hiển thị 0 trên 0 phiếu';
    document.getElementById('paginationControls').innerHTML = '';
    return;
  }

  let html = '';
  issues.forEach(item => {
    // Render tiến độ 5 bước dạng dots
    const dotsHtml = renderStepperDots(item.status);
    const statusBadge = getStatusBadge(item.status);
    const typeLabel = (item.issue_type === 'consumable') 
      ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Tiêu hao định mức</span>' 
      : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Bất thường</span>';

    html += `
      <tr>
        <td class="ps-3">
          <strong class="font-monospace text-primary">${escapeHtml(item.issue_code)}</strong>
          <div class="small text-muted">${formatDate(item.created_at)}</div>
        </td>
        <td>
          <span class="fw-semibold text-main">${escapeHtml(item.group_name)}</span>
          <div class="small text-muted">Kỳ: Tháng ${item.month}/${item.year}</div>
        </td>
        <td>
          <div>${typeLabel}</div>
          <div class="small text-muted text-truncate" style="max-width: 200px;" title="${escapeHtml(item.purpose || '')}">
            ${escapeHtml(item.purpose || 'Xuất vật tư định mức')}
          </div>
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(item.creator_name)}</div>
          <div class="small text-muted">${escapeHtml(item.department || 'Phân xưởng')}</div>
        </td>
        <td class="text-center">
          <span class="badge bg-light text-dark border px-2 py-1">${item.total_items} món</span>
        </td>
        <td class="text-center">
          ${dotsHtml}
        </td>
        <td class="text-center">
          ${statusBadge}
        </td>
        <td class="text-end pe-3">
          <div class="d-flex justify-content-end gap-1">
            <button class="app-btn app-btn-primary btn-sm py-1 px-2" onclick="openWorkflowModal(${item.id})" title="Xem chi tiết & phê duyệt">
              <span class="material-icons" style="font-size: 15px;">visibility</span>
            </button>
            <button type="button" class="app-btn app-btn-outline btn-sm py-1 px-2 text-danger border-danger-subtle" onclick="openPdfPreviewModal(${item.id})" title="Xem trước PDF (A4) 5 dấu ký">
              <span class="material-icons" style="font-size: 15px;">picture_as_pdf</span>
            </button>
            <a href="api/warehouse.php?action=export_issue_excel&issue_id=${item.id}" class="app-btn app-btn-outline btn-sm py-1 px-2 text-success border-success-subtle" title="Xuất Excel phiếu này">
              <span class="material-icons" style="font-size: 15px;">file_download</span>
            </a>
            <button type="button" class="app-btn app-btn-outline btn-sm py-1 px-2 text-danger border-danger-subtle" onclick="deleteIssue(${item.id}, '${escapeHtml(item.issue_code)}')" title="Xóa phiếu yêu cầu xuất kho">
              <span class="material-icons" style="font-size: 15px;">delete</span>
            </button>
          </div>
        </td>
      </tr>`;
  });

  tbody.innerHTML = html;

  // Pagination info
  const start = (page - 1) * limit + 1;
  const end = Math.min(page * limit, total);
  document.getElementById('paginationInfo').textContent = `Hiển thị ${start} - ${end} trên ${total} phiếu`;

  // Pagination buttons
  const totalPages = Math.ceil(total / limit);
  let pgn = '';
  if (totalPages > 1) {
    pgn += `<button class="btn btn-sm btn-outline-secondary ${page <= 1 ? 'disabled' : ''}" onclick="loadWorkflowIssues(${page - 1})">Trước</button>`;
    for (let p = 1; p <= totalPages; p++) {
      if (p === 1 || p === totalPages || (p >= page - 1 && p <= page + 1)) {
        pgn += `<button class="btn btn-sm ${p === page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadWorkflowIssues(${p})">${p}</button>`;
      } else if (p === page - 2 || p === page + 2) {
        pgn += `<span class="px-1 text-muted">...</span>`;
      }
    }
    pgn += `<button class="btn btn-sm btn-outline-secondary ${page >= totalPages ? 'disabled' : ''}" onclick="loadWorkflowIssues(${page + 1})">Sau</button>`;
  }
  document.getElementById('paginationControls').innerHTML = pgn;
}

// 6. Hiển thị 5 chấm trạng thái (Step dots)
function renderStepperDots(status) {
  const steps = ['pending_checker', 'pending_manager', 'pending_admin_issue', 'pending_handover', 'completed'];
  let currentIdx = -1;
  if (status === 'pending_checker') currentIdx = 0;
  else if (status === 'pending_manager') currentIdx = 1;
  else if (status === 'pending_admin_issue') currentIdx = 2;
  else if (status === 'pending_handover') currentIdx = 3;
  else if (status === 'completed') currentIdx = 4;
  else if (status === 'rejected') currentIdx = -99;

  let dots = '<div class="d-flex align-items-center justify-content-center gap-1" title="Tiến trình 5 bước">';
  for (let i = 0; i < 5; i++) {
    let cls = 'wait';
    if (status === 'rejected') {
      cls = 'reject';
    } else if (i < currentIdx) {
      cls = 'done';
    } else if (i === currentIdx) {
      cls = 'current';
    }
    dots += `<span class="dot-indicator ${cls}"></span>`;
  }
  dots += '</div>';
  return dots;
}

// 7. Nhãn badge trạng thái
function getStatusBadge(status) {
  switch (status) {
    case 'pending_checker':
      return '<span class="badge bg-warning text-dark"><span class="material-icons align-middle" style="font-size: 13px;">schedule</span> Chờ Kiểm Tra</span>';
    case 'pending_manager':
      return '<span class="badge text-white" style="background: #8b5cf6;"><span class="material-icons align-middle" style="font-size: 13px;">rule</span> Chờ QL Duyệt</span>';
    case 'pending_admin_issue':
      return '<span class="badge bg-info text-dark"><span class="material-icons align-middle" style="font-size: 13px;">warehouse</span> Chờ Xuất Kho</span>';
    case 'pending_handover':
      return '<span class="badge bg-primary"><span class="material-icons align-middle" style="font-size: 13px;">handshake</span> Chờ Bàn Giao</span>';
    case 'completed':
      return '<span class="badge bg-success"><span class="material-icons align-middle" style="font-size: 13px;">check_circle</span> Đã Hoàn Tất</span>';
    case 'rejected':
      return '<span class="badge bg-danger"><span class="material-icons align-middle" style="font-size: 13px;">cancel</span> Đã Từ Chối</span>';
    default:
      return '<span class="badge bg-secondary">' + status + '</span>';
  }
}

// 8. Mở modal chi tiết & xét duyệt
function openWorkflowModal(issueId) {
  currentActionIssueId = issueId;
  fetch(`api/warehouse.php?action=get_issue_detail&issue_id=${issueId}`)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert(res.message || 'Không thể tải chi tiết phiếu');
        return;
      }

      currentIssueDetail = res.issue;
      renderWorkflowModalContent(res.issue);
      const modal = new bootstrap.Modal(document.getElementById('modalWorkflowDetail'));
      modal.show();
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi tải chi tiết phiếu xuất kho.');
    });
}

// 9. Render toàn bộ nội dung trong modal
function renderWorkflowModalContent(issue) {
  document.getElementById('mDetailIssueCode').textContent = issue.issue_code;
  document.getElementById('mDetailSubTitle').textContent = `Kỳ: Tháng ${issue.month}/${issue.year} | Nhóm: ${issue.group_name} | Người tạo: ${issue.creator_name}`;
  document.getElementById('mDetailGroup').textContent = issue.group_name;
  document.getElementById('mDetailType').textContent = (issue.issue_type === 'consumable') ? 'Tiêu hao theo định mức' : 'Bất thường ngoài định mức';
  document.getElementById('mDetailCreator').textContent = `${issue.creator_name} (${issue.creator_role || 'Nhân viên'})`;
  document.getElementById('mDetailCreatedAt').textContent = formatDate(issue.created_at);
  document.getElementById('mDetailPurpose').textContent = issue.purpose || 'Xuất vật tư định mức phục vụ sản xuất';
  document.getElementById('mDetailItemCount').textContent = (issue.items || []).length;
  document.getElementById('mDetailStatusBadge').outerHTML = `<div class="badge px-3 py-1 font-monospace" id="mDetailStatusBadge">${getStatusBadge(issue.status)}</div>`;

  // Render 5-Step Stepper bar
  updateStepperBar(issue);

  // Render Items table
  renderModalItemsTable(issue.items || []);

  // Render 5 Dấu Ký & Xác Nhận Chuẩn
  renderApprovalStamps(issue);

  // Render Workflow Audit Logs
  renderWorkflowLogs(issue.logs || []);

  // Configure Approval Actions based on Current Step & User Permissions
  configureActionForm(issue);

  // Gán link xuất PDF & Excel vào nút trong modal
  const pdfBtn = document.getElementById('btnModalPdf');
  if (pdfBtn) {
    pdfBtn.onclick = () => openPdfPreviewModal(issue.id);
  }
  const xlsBtn = document.getElementById('btnModalExcel');
  if (xlsBtn) xlsBtn.href = `api/warehouse.php?action=export_issue_excel&issue_id=${issue.id}`;
}

// 10. Cập nhật thanh stepper 5 bước
function updateStepperBar(issue) {
  const status = issue.status;
  const logs = issue.logs || [];

  // Reset all
  for (let i = 1; i <= 5; i++) {
    const s = document.getElementById(`wfStep${i}`);
    s.classList.remove('completed', 'active', 'rejected');
    if (i < 5) document.getElementById(`wfLine${i}`).classList.remove('completed');
  }

  // Helper tìm log theo bước
  const getLogForStep = (stepName) => logs.slice().reverse().find(l => l.step === stepName || l.action.toLowerCase().includes(stepName));

  // Step 1: Khởi tạo (luôn xong)
  document.getElementById('wfStep1').classList.add('completed');
  document.getElementById('wfStep1Desc').textContent = issue.creator_name;
  document.getElementById('wfStep1Time').textContent = formatDate(issue.created_at);
  document.getElementById('wfLine1').classList.add('completed');

  const logChecker = logs.find(l => l.step === 'checker');
  const logManager = logs.find(l => l.step === 'manager');
  const logAdmin   = logs.find(l => l.step === 'admin_issue');
  const logHandover= logs.find(l => l.step === 'handover');

  if (logChecker) {
    document.getElementById('wfStep2Desc').textContent = `${logChecker.actor_name} (${logChecker.action})`;
    document.getElementById('wfStep2Time').textContent = formatDate(logChecker.created_at);
  }
  if (logManager) {
    document.getElementById('wfStep3Desc').textContent = `${logManager.actor_name} (${logManager.action})`;
    document.getElementById('wfStep3Time').textContent = formatDate(logManager.created_at);
  }
  if (logAdmin) {
    document.getElementById('wfStep4Desc').textContent = `${logAdmin.actor_name} (${logAdmin.action})`;
    document.getElementById('wfStep4Time').textContent = formatDate(logAdmin.created_at);
  }
  if (logHandover) {
    const rx = logHandover.handover_receiver_name ? `Nhận: ${logHandover.handover_receiver_name}` : logHandover.action;
    document.getElementById('wfStep5Desc').textContent = `${logHandover.actor_name} (${rx})`;
    document.getElementById('wfStep5Time').textContent = formatDate(logHandover.created_at);
  }

  if (status === 'rejected') {
    // Tìm bước từ chối
    const rejLog = logs.slice().reverse().find(l => l.action.includes('từ chối') || l.action.includes('Từ chối'));
    const rejStep = rejLog ? rejLog.step : 'checker';
    if (rejStep === 'checker') document.getElementById('wfStep2').classList.add('rejected');
    else if (rejStep === 'manager') {
      document.getElementById('wfStep2').classList.add('completed');
      document.getElementById('wfLine2').classList.add('completed');
      document.getElementById('wfStep3').classList.add('rejected');
    }
  } else if (status === 'pending_checker') {
    document.getElementById('wfStep2').classList.add('active');
  } else if (status === 'pending_manager') {
    document.getElementById('wfStep2').classList.add('completed');
    document.getElementById('wfLine2').classList.add('completed');
    document.getElementById('wfStep3').classList.add('active');
  } else if (status === 'pending_admin_issue') {
    document.getElementById('wfStep2').classList.add('completed');
    document.getElementById('wfLine2').classList.add('completed');
    document.getElementById('wfStep3').classList.add('completed');
    document.getElementById('wfLine3').classList.add('completed');
    document.getElementById('wfStep4').classList.add('active');
  } else if (status === 'pending_handover') {
    document.getElementById('wfStep2').classList.add('completed');
    document.getElementById('wfLine2').classList.add('completed');
    document.getElementById('wfStep3').classList.add('completed');
    document.getElementById('wfLine3').classList.add('completed');
    document.getElementById('wfStep4').classList.add('completed');
    document.getElementById('wfLine4').classList.add('completed');
    document.getElementById('wfStep5').classList.add('active');
  } else if (status === 'completed') {
    for (let i = 1; i <= 5; i++) {
      document.getElementById(`wfStep${i}`).classList.add('completed');
      if (i < 5) document.getElementById(`wfLine${i}`).classList.add('completed');
    }
  }
}

// 11. Render bảng vật tư
function renderModalItemsTable(items) {
  const tbody = document.getElementById('mDetailItemsTbody');
  if (items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">Không có mặt hàng nào.</td></tr>`;
    return;
  }

  let html = '';
  items.forEach((it, idx) => {
    const imgHtml = it.image_url 
      ? `<img src="${escapeHtml(it.image_url)}" class="rounded border" style="width: 36px; height: 36px; object-fit: contain;">`
      : `<div class="bg-light text-muted d-flex align-items-center justify-content-center border rounded" style="width: 36px; height: 36px;"><span class="material-icons" style="font-size: 18px;">photo</span></div>`;

    const isConsumable = (it.category_type === 'consumable');
    const totUses = it.total_uses ? Number(it.total_uses) : (Number(it.machines_count || 0) * Number(it.uses_per_machine || 0));
    const normFormula = isConsumable 
      ? `<div class="font-monospace small">A: <strong>${fmt1(it.machines_count)}</strong> × B: <strong>${fmt1(it.uses_per_machine)}</strong> = <strong>${fmt1(totUses)}</strong></div><div class="text-muted small">C: ${fmt1(it.norm_per_use)} ${it.unit}/lần</div>`
      : `<div class="text-muted small">Bất thường: Không theo định mức</div>`;

    const fieldStock = isConsumable
      ? `<div class="small">Tồn HT: <strong>${fmt1(it.field_stock)}</strong></div><div class="small text-muted">Tái SD: <strong>${fmt1(it.reusable_stock)}</strong></div>`
      : `<div class="small">Tồn HT: <strong>${fmt1(it.field_stock)}</strong></div>`;

    const theoQty = fmt1(it.theoretical_qty);
    const actQty = fmt1(it.actual_qty !== undefined ? it.actual_qty : it.issued_qty);
    const stBefore = fmt1(it.stock_before_issue !== undefined ? it.stock_before_issue : it.stock_current);
    const stAfter = fmt1(it.stock_after_issue);
    const ropVal = fmt1(it.reorder_point);

    // Runway badge
    let runwayBadge = '';
    if (it.runway_months !== null && it.runway_months !== undefined) {
      const rw = parseFloat(it.runway_months);
      if (rw <= 0.5) runwayBadge = `<span class="badge bg-danger">Hết hàng (${fmt1(rw)} thg)</span>`;
      else if (rw <= 1.0) runwayBadge = `<span class="badge bg-warning text-dark">&lt; 1 tháng (${fmt1(rw)})</span>`;
      else runwayBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle">${fmt1(rw)} tháng</span>`;
    } else {
      runwayBadge = `<span class="text-muted small">N/A</span>`;
    }

    // Tồn kho cảnh báo
    let stockAlert = '';
    const stAfterNum = parseFloat(it.stock_after_issue) || 0;
    const ropNum = parseFloat(it.reorder_point) || 0;
    if (stAfterNum <= 0) {
      stockAlert = '<div class="text-danger small fw-bold">⚠ Hết hàng sau xuất</div>';
    } else if (stAfterNum <= ropNum) {
      stockAlert = `<div class="text-warning small fw-bold">⚠ &le; ROP (${ropVal})</div>`;
    }

    html += `
      <tr>
        <td class="text-center fw-bold text-muted">${idx + 1}</td>
        <td class="text-center">${imgHtml}</td>
        <td>
          <strong class="font-monospace text-primary">${escapeHtml(it.material_code || it.item_code)}</strong>
          ${it.sap_code ? `<div class="small text-muted font-monospace">SAP: ${escapeHtml(it.sap_code)}</div>` : ''}
          ${it.bin_location ? `<div class="badge bg-light text-secondary border font-monospace mt-1">BIN: ${escapeHtml(it.bin_location)}</div>` : ''}
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(it.material_name || it.item_name_vn)}</div>
          <div class="small text-muted">Quy cách: ${escapeHtml(it.pack_spec || it.packaging_spec || 'Gói lẻ')} (${fmt1(it.pack_quantity || 1)} ${it.unit}/gói)</div>
        </td>
        <td class="col-input-data">${normFormula}</td>
        <td class="text-center col-input-data">${fieldStock}</td>
        <td class="text-center fw-bold col-auto-calc">${theoQty} ${it.unit}</td>
        <td class="text-center fw-bold text-primary font-monospace col-auto-calc" style="font-size: 15px; background-color: #e0f2fe !important;">
          ${actQty} ${it.unit}
        </td>
        <td class="text-center col-auto-calc">
          <div class="fw-semibold">${stBefore} &rarr; <strong>${stAfter}</strong></div>
          ${stockAlert}
        </td>
        <td class="text-center col-auto-calc">${runwayBadge}</td>
      </tr>`;
  });

  tbody.innerHTML = html;
}

// 12. Render Audit logs
function renderWorkflowLogs(logs) {
  const container = document.getElementById('mWorkflowLogsContainer');
  if (logs.length === 0) {
    container.innerHTML = `<div class="text-muted small">Chưa có bản ghi thao tác.</div>`;
    return;
  }

  let html = '';
  logs.forEach(log => {
    let dotClass = 'log-dot';
    if (log.action.includes('hoàn tất') || log.action.includes('phê duyệt') || log.action.includes('Xuất kho')) dotClass += ' success';
    else if (log.action.includes('từ chối') || log.action.includes('Từ chối')) dotClass += ' danger';
    else dotClass += ' warning';

    const handoverExtra = (log.handover_receiver_name || log.handover_receiver_code) 
      ? `<div class="mt-1 small bg-white p-2 rounded border"><strong>Người nhận bàn giao:</strong> ${escapeHtml(log.handover_receiver_name || '')} (Mã NV: ${escapeHtml(log.handover_receiver_code || '')})</div>` 
      : '';

    html += `
      <div class="log-item">
        <div class="${dotClass}"></div>
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong class="text-main small">${escapeHtml(log.action)}</strong>
          <span class="small text-muted">${formatDate(log.created_at)}</span>
        </div>
        <div class="small text-muted">Thực hiện bởi: <strong>${escapeHtml(log.actor_name)}</strong> (${escapeHtml(log.actor_role)})</div>
        ${log.comment ? `<div class="mt-1 small text-dark fst-italic">"${escapeHtml(log.comment)}"</div>` : ''}
        ${handoverExtra}
      </div>`;
  });

  container.innerHTML = html;
}

// 13. Cấu hình Form xét duyệt theo bước hiện tại & quyền người dùng
function configureActionForm(issue) {
  const status = issue.status;
  const group = issue.group_name;
  const actionContainer = document.getElementById('mActionContainer');
  const noActionNotice  = document.getElementById('mNoActionNotice');
  const adminNotice     = document.getElementById('mAdminIssueNotice');
  const receiverNameCol = document.getElementById('mHandoverReceiverNameCol');
  const receiverCodeCol = document.getElementById('mHandoverReceiverCodeCol');
  const btnApprove      = document.getElementById('mBtnApprove');
  const btnReject       = document.getElementById('mBtnReject');
  const btnApproveText  = document.getElementById('mBtnApproveText');
  const actionTitle     = document.getElementById('mActionTitle');

  // Reset ẩn các phần mở rộng
  adminNotice.classList.add('d-none');
  receiverNameCol.classList.add('d-none');
  receiverCodeCol.classList.add('d-none');
  document.getElementById('mActionComment').value = '';
  document.getElementById('mHandoverReceiverName').value = '';
  document.getElementById('mHandoverReceiverCode').value = '';

  if (status === 'completed' || status === 'rejected') {
    actionContainer.classList.add('d-none');
    noActionNotice.classList.remove('d-none');
    document.getElementById('mNoActionText').textContent = (status === 'completed') 
      ? 'Phiếu xuất kho này đã được bàn giao và hoàn tất toàn bộ quy trình.' 
      : 'Phiếu này đã bị từ chối phê duyệt.';
    return;
  }

  // Xác định bước và quyền
  let step = '';
  let canApprove = false;
  const userRole = (sessionUser.role || '').toLowerCase();
  const isSuperAdmin = (userRole === 'admin' || userRole === 'editor');

  const hasWarehousePerm = (perm) => {
    if (typeof window.hasPermission === 'function') {
      return window.hasPermission(perm) || window.hasPermission('admin') || window.hasPermission('warehouse.manage');
    }
    if (Array.isArray(sessionUser.permissions)) {
      return sessionUser.permissions.includes(perm) || sessionUser.permissions.includes('admin') || sessionUser.permissions.includes('warehouse.manage');
    }
    return false;
  };

  if (status === 'pending_checker') {
    step = 'checker';
    canApprove = isSuperAdmin || hasWarehousePerm('warehouse.check') || checkApproverRole('checker', group);
    actionTitle.textContent = 'BƯỚC 2: NGƯỜI KIỂM TRA PHÊ DUYỆT';
    btnApproveText.textContent = 'Xác Nhận Kiểm Tra & Chuyển Quản Lý';
    btnReject.classList.remove('d-none');
  } else if (status === 'pending_manager') {
    step = 'manager';
    canApprove = isSuperAdmin || hasWarehousePerm('warehouse.approve') || checkApproverRole('manager', group);
    actionTitle.textContent = 'BƯỚC 3: QUẢN LÝ PHÊ DUYỆT';
    btnApproveText.textContent = 'Phê Duyệt & Chuyển Cho Thủ Kho Xuất Hàng';
    btnReject.classList.remove('d-none');
  } else if (status === 'pending_admin_issue') {
    step = 'admin_issue';
    canApprove = isSuperAdmin || hasWarehousePerm('warehouse.issue') || checkApproverRole('admin_warehouse', group);
    actionTitle.textContent = 'BƯỚC 4: THỦ KHO / ADMIN LÀM THỦ TỤC XUẤT KHO';
    btnApproveText.textContent = 'Xác Nhận Xuất Kho (Trừ Tồn Kho Thực Tế)';
    btnReject.classList.remove('d-none');
    adminNotice.classList.remove('d-none');
  } else if (status === 'pending_handover') {
    step = 'handover';
    canApprove = isSuperAdmin || hasWarehousePerm('warehouse.handover') || checkApproverRole('receiver', group);
    actionTitle.textContent = 'BƯỚC 5: XÁC NHẬN BÀN GIAO & NHẬN HÀNG HIỆN TRƯỜNG';
    btnApproveText.textContent = 'Xác Nhận Hoàn Tất Bàn Giao';
    btnReject.classList.add('d-none'); // Bước bàn giao chỉ xác nhận nhận hàng hoặc liên hệ thủ kho
    receiverNameCol.classList.remove('d-none');
    receiverCodeCol.classList.remove('d-none');
  }

  // Nếu backend trả về can_approve = true, kích hoạt quyền duyệt
  if (issue.can_approve === true) {
    canApprove = true;
  }

  currentStepForAction = step;

  if (canApprove) {
    actionContainer.classList.remove('d-none');
    noActionNotice.classList.add('d-none');
  } else {
    actionContainer.classList.add('d-none');
    noActionNotice.classList.remove('d-none');
    document.getElementById('mNoActionText').textContent = `Tài khoản của bạn (${sessionUser.fullname || sessionUser.username} - vai trò: ${sessionUser.role}) không được phân quyền phê duyệt ở bước này cho nhóm [${group}].`;
  }
}

// 14. Kiểm tra quyền của người dùng trong cấu hình người phê duyệt
function checkApproverRole(roleType, groupName) {
  if (!workflowApproversData || !workflowApproversData[roleType]) return false;
  const uname = (sessionUser.username || '').toLowerCase().trim();
  return workflowApproversData[roleType].some(a => {
    const aUname = (a.username || '').toLowerCase().trim();
    const aGroup = (a.group_name || '').trim();
    return aUname === uname && (aGroup === groupName || aGroup === 'ALL' || !aGroup);
  });
}

// 15. Gửi lệnh phê duyệt / từ chối
function submitApprovalAction(actionType) {
  if (!currentActionIssueId || !currentStepForAction) return;

  const comment = document.getElementById('mActionComment').value.trim();
  if (actionType === 'reject' && !comment) {
    alert('Vui lòng nhập lý do từ chối vào ô Ý kiến / Ghi chú xét duyệt.');
    document.getElementById('mActionComment').focus();
    return;
  }

  let receiverName = '';
  let receiverCode = '';
  if (currentStepForAction === 'handover' && actionType === 'approve') {
    receiverName = document.getElementById('mHandoverReceiverName').value.trim();
    receiverCode = document.getElementById('mHandoverReceiverCode').value.trim();
    if (!receiverName || !receiverCode) {
      alert('Vui lòng nhập đầy đủ Họ tên và Mã nhân viên của người nhận bàn giao.');
      return;
    }
  }

  const confirmMsg = (actionType === 'approve')
    ? (currentStepForAction === 'admin_issue' 
        ? 'Bạn có chắc chắn muốn XUẤT KHO? Thao tác này sẽ trừ số lượng tồn kho thực tế của các vật tư.' 
        : 'Bạn có chắc chắn muốn phê duyệt bước này?')
    : 'Bạn có chắc chắn muốn TỪ CHỐI phiếu xuất kho này?';

  if (!confirm(confirmMsg)) return;

  const formData = new FormData();
  formData.append('issue_id', currentActionIssueId);
  formData.append('step', currentStepForAction);
  formData.append('action_type', actionType);
  formData.append('comment', comment);
  formData.append('receiver_name', receiverName);
  formData.append('receiver_code', receiverCode);

  fetch('api/warehouse.php?action=approve_step', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Thao tác thành công!');
        // Tải lại chi tiết trong modal để xem trạng thái mới
        openWorkflowModal(currentActionIssueId);
        // Tải lại danh sách ngoài trang chính
        loadWorkflowIssues();
      } else {
        alert(res.message || 'Thao tác thất bại.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi gửi dữ liệu xét duyệt.');
    });
}

// Tiện ích format
function formatDate(dtStr) {
  if (!dtStr) return '--';
  const d = new Date(dtStr);
  if (isNaN(d.getTime())) return dtStr;
  const day = String(d.getDate()).padStart(2, '0');
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const y = d.getFullYear();
  const h = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  return `${day}/${m}/${y} ${h}:${min}`;
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// 16. Render khung 5 Dấu Ký & Xác Nhận Chuẩn (Signatures & Stamps)
function renderApprovalStamps(issue) {
  const container = document.getElementById('mApprovalStampsContainer');
  if (!container) return;

  const st1 = {
    title: '1. NGƯỜI LẬP PHIẾU',
    signed: true,
    badgeText: 'ĐÃ LẬP & KÝ',
    badgeCls: 'bg-success',
    name: issue.creator_name,
    date: formatDate(issue.created_at)
  };

  const st2 = {
    title: '2. NGƯỜI KIỂM TRA',
    signed: !!issue.checker_approved_at,
    badgeText: issue.checker_approved_at ? 'ĐÃ KIỂM TRA' : 'CHỜ KIỂM TRA',
    badgeCls: issue.checker_approved_at ? 'bg-success' : 'bg-warning text-dark',
    name: issue.checker_name || 'Người kiểm tra',
    date: issue.checker_approved_at ? formatDate(issue.checker_approved_at) : '--'
  };

  const st3 = {
    title: '3. QUẢN LÝ PHÊ DUYỆT',
    signed: !!issue.manager_approved_at,
    badgeText: issue.manager_approved_at ? 'ĐÃ PHÊ DUYỆT' : 'CHỜ DUYỆT',
    badgeCls: issue.manager_approved_at ? 'bg-success' : 'bg-secondary',
    name: issue.manager_name || 'Quản lý',
    date: issue.manager_approved_at ? formatDate(issue.manager_approved_at) : '--'
  };

  const st4 = {
    title: '4. THỦ KHO XUẤT HÀNG',
    signed: !!issue.admin_issued_at,
    badgeText: issue.admin_issued_at ? 'ĐÃ XUẤT KHO' : 'CHỜ XUẤT',
    badgeCls: issue.admin_issued_at ? 'bg-info text-dark' : 'bg-secondary',
    name: issue.admin_issuer_name || 'Thủ kho',
    date: issue.admin_issued_at ? formatDate(issue.admin_issued_at) : '--'
  };

  const st5 = {
    title: '5. NHẬN BÀN GIAO',
    signed: !!issue.handover_completed_at,
    badgeText: issue.handover_completed_at ? 'ĐÃ NHẬN ĐỦ' : 'CHỜ BÀN GIAO',
    badgeCls: issue.handover_completed_at ? 'bg-success' : 'bg-secondary',
    name: issue.handover_receiver_name || 'Hiện trường',
    date: issue.handover_completed_at ? formatDate(issue.handover_completed_at) : '--'
  };

  const stamps = [st1, st2, st3, st4, st5];
  let html = '';

  stamps.forEach(s => {
    html += `
      <div class="col">
        <div class="border rounded p-2 h-100 bg-white shadow-sm d-flex flex-column justify-content-between" style="min-height: 120px;">
          <div class="fw-bold small text-muted text-uppercase pb-1 border-bottom" style="font-size: 10px;">${s.title}</div>
          <div class="my-2">
            <span class="badge ${s.badgeCls} px-2 py-1" style="font-size: 10px;">${s.badgeText}</span>
          </div>
          <div class="mt-auto">
            <div class="fw-bold text-main small text-truncate" title="${escapeHtml(s.name)}">${escapeHtml(s.name)}</div>
            <div class="text-muted font-monospace" style="font-size: 10px;">${s.date}</div>
          </div>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

// 17. Xuất Excel báo cáo tổng hợp danh sách phiếu theo tháng
function exportMonthlyReportExcel() {
  const group = document.getElementById('filterGroup').value;
  const month = document.getElementById('filterMonth').value;
  const year  = document.getElementById('filterYear').value;
  const status = currentFilterStatus;
  window.location.href = `api/warehouse.php?action=export_issues_monthly_excel&month=${month}&year=${year}&group_name=${encodeURIComponent(group)}&status=${encodeURIComponent(status)}`;
}

// 18. Xem trước & In PDF (A4) 5 dấu ký
function openPdfPreviewModal(issueId) {
  if (!issueId) return;
  const content = document.getElementById('pdfPreviewContent');
  if (content) {
    content.innerHTML = `
      <div style="height: 75vh; width: 100%;">
        <iframe id="pdfPreviewIframe" src="api/warehouse.php?action=render_issue_pdf_html&issue_id=${issueId}" style="width:100%; height:100%; border:none; border-radius:4px;"></iframe>
      </div>
    `;
  }
  const modalEl = document.getElementById('modalPreviewPDF');
  if (modalEl) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}

function printPdfPreview() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe && iframe.contentWindow) {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  } else {
    window.print();
  }
}

function openPdfInNewTab() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe && iframe.src) {
    window.open(iframe.src, '_blank');
  }
}

// 19. Xóa phiếu yêu cầu xuất kho
function deleteIssue(issueId, issueCode) {
  if (!issueId) return;
  const label = issueCode || ('#' + issueId);
  if (!confirm(`Bạn có chắc chắn muốn XÓA phiếu xuất kho [${label}] không?\n\nLưu ý:\n- Toàn bộ dữ liệu của phiếu và các bản ghi xét duyệt sẽ bị xóa.\n- Nếu phiếu đã được thủ kho xuất hàng, số lượng vật tư sẽ tự động được hoàn trả lại vào tồn kho thực tế.`)) {
    return;
  }

  const formData = new FormData();
  formData.append('issue_id', issueId);

  fetch('api/warehouse.php?action=delete_issue', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || `Đã xóa thành công phiếu [${label}].`);
        loadWorkflowIssues();
      } else {
        alert(res.message || 'Lỗi khi xóa phiếu.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi thực hiện xóa phiếu.');
    });
}

function deleteCurrentIssueFromModal() {
  if (!currentActionIssueId) return;
  const label = currentIssueDetail ? (currentIssueDetail.issue_code || ('#' + currentActionIssueId)) : ('#' + currentActionIssueId);
  if (!confirm(`Bạn có chắc chắn muốn XÓA phiếu xuất kho [${label}] đang mở không?\n\nLưu ý: Thao tác này không thể hoàn tác!`)) {
    return;
  }

  const formData = new FormData();
  formData.append('issue_id', currentActionIssueId);

  fetch('api/warehouse.php?action=delete_issue', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || `Đã xóa thành công phiếu [${label}].`);
        const modalEl = document.getElementById('modalWorkflowDetail');
        if (modalEl) {
          const inst = bootstrap.Modal.getInstance(modalEl);
          if (inst) inst.hide();
        }
        loadWorkflowIssues();
      } else {
        alert(res.message || 'Lỗi khi xóa phiếu.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi thực hiện xóa phiếu.');
    });
}
</script>
