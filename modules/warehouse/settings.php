<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Cấu Hình Người Phê Duyệt (Workflow Approvers Settings)
 * DX Plastic Group - Factory Management System
 */

$userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');
$canEditApprovers = ($userRole === 'admin' || hasPermission('role.manage'));

// Lấy danh sách tài khoản người dùng hệ thống để gợi ý khi thêm người duyệt
global $conn;
$systemUsers = [];
if (isset($conn) && $conn instanceof mysqli) {
    $resU = $conn->query("SELECT id, username, fullname, role FROM users ORDER BY fullname ASC");
    if ($resU) {
        while ($u = $resU->fetch_assoc()) {
            $systemUsers[] = $u;
        }
    }
}
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">admin_panel_settings</span>
        <span>CẤU HÌNH NGƯỜI PHÊ DUYỆT XUẤT KHO</span>
      </h1>
      <p class="app-page-subtitle">Phân quyền linh hoạt nhiều tài khoản có thẩm quyền ở các cấp: Người kiểm tra, Quản lý phê duyệt, Admin xuất kho và Bàn giao hiện trường theo từng nhóm công việc</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-outline">
        <span class="material-icons">verified_user</span>
        <span>Quy Trình Phê Duyệt</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=dashboard" class="app-btn app-btn-secondary">
        <span class="material-icons">dashboard</span>
        <span>Dashboard Kho</span>
      </a>
      <?php if ($canEditApprovers): ?>
      <button class="app-btn app-btn-primary" onclick="openAddApproverModal()">
        <span class="material-icons">person_add</span>
        <span>Thêm Người Phê Duyệt</span>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Thanh Điều Hướng 2 Tab Cấu Hình & Cài Đặt -->
  <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-2">
    <button class="app-btn app-btn-primary btn-sm active" id="tabBtnApprovers" type="button" onclick="switchSettingsTab('approvers')">
      <span class="material-icons" style="font-size: 16px;">admin_panel_settings</span>
      <span>1. Cấu Hình Người Phê Duyệt</span>
    </button>
    <button class="app-btn app-btn-outline btn-sm" id="tabBtnMaterials" type="button" onclick="switchSettingsTab('materials')">
      <span class="material-icons" style="font-size: 16px;">category</span>
      <span>2. Cài Đặt Danh Mục & Nhóm Vật Tư (Đăng Ký Nhiều Nhóm, Import/Export Tồn Kho)</span>
    </button>
  </div>

  <!-- TAB 1: CẤU HÌNH NGƯỜI PHÊ DUYỆT -->
  <div id="tabContentApprovers">
  <!-- Sơ đồ 4 cấp phê duyệt có cấu hình tài khoản -->
  <div class="row g-3 mb-4">
    <!-- Cấp 1: Người kiểm tra -->
    <div class="col-md-6 col-xl-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #f59e0b !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge bg-warning text-dark font-monospace">BƯỚC 2</span>
          <span class="material-icons text-warning">fact_check</span>
        </div>
        <h6 class="fw-bold text-main mb-1">Người Kiểm Tra</h6>
        <p class="small text-muted mb-2">Thẩm định tính hợp lệ của định mức (A × B × C) và số lượng tồn hiện trường.</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
          <span class="small text-muted">Đang cấu hình:</span>
          <strong class="text-warning font-monospace" id="countChecker">0 người</strong>
        </div>
      </div>
    </div>

    <!-- Cấp 2: Quản lý phê duyệt -->
    <div class="col-md-6 col-xl-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #8b5cf6 !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge text-white font-monospace" style="background: #8b5cf6;">BƯỚC 3</span>
          <span class="material-icons" style="color: #8b5cf6;">verified</span>
        </div>
        <h6 class="fw-bold text-main mb-1">Quản Lý Phê Duyệt</h6>
        <p class="small text-muted mb-2">Trưởng bộ phận / Quản lý phân xưởng phê chuẩn lệnh xuất vật tư sản xuất.</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
          <span class="small text-muted">Đang cấu hình:</span>
          <strong class="font-monospace" style="color: #8b5cf6;" id="countManager">0 người</strong>
        </div>
      </div>
    </div>

    <!-- Cấp 3: Admin / Thủ kho xuất hàng -->
    <div class="col-md-6 col-xl-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #06b6d4 !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge bg-info text-dark font-monospace">BƯỚC 4</span>
          <span class="material-icons text-info">warehouse</span>
        </div>
        <h6 class="fw-bold text-main mb-1">Thủ Kho / Admin Xuất Hàng</h6>
        <p class="small text-muted mb-2">Kiểm tra BIN lưu kho, làm thủ tục trừ kho thực tế và kích hoạt cảnh báo ROP.</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
          <span class="small text-muted">Đang cấu hình:</span>
          <strong class="text-info font-monospace" id="countAdmin">0 người</strong>
        </div>
      </div>
    </div>

    <!-- Cấp 4: Bàn giao hiện trường -->
    <div class="col-md-6 col-xl-3">
      <div class="app-card p-3 border h-100" style="border-top: 4px solid #10b981 !important;">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="badge bg-success font-monospace">BƯỚC 5</span>
          <span class="material-icons text-success">handshake</span>
        </div>
        <h6 class="fw-bold text-main mb-1">Bàn Giao & Nhận Vật Tư</h6>
        <p class="small text-muted mb-2">Kỹ thuật viên / Nhân viên hiện trường xác nhận đã nhận bàn giao đủ số lượng.</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
          <span class="small text-muted">Đang cấu hình:</span>
          <strong class="text-success font-monospace" id="countReceiver">0 người</strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Danh sách bảng người phê duyệt theo từng cấp -->
  <div class="row g-4">
    <!-- 1. Danh sách Người kiểm tra -->
    <div class="col-lg-6">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="material-icons text-warning">fact_check</span>
            <span>Cấp 2: Người Kiểm Tra (Checkers)</span>
          </h6>
          <?php if ($canEditApprovers): ?>
          <button class="app-btn app-btn-outline btn-sm" onclick="openAddApproverModal('checker')">
            <span class="material-icons" style="font-size: 15px;">add</span>
            <span>Thêm</span>
          </button>
          <?php endif; ?>
        </div>
        <div class="p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small text-muted">
                <tr>
                  <th class="ps-3">Người Phê Duyệt</th>
                  <th>Nhóm Phụ Trách</th>
                  <th class="text-center">Trạng Thái</th>
                  <?php if ($canEditApprovers): ?><th class="text-end pe-3">Thao Tác</th><?php endif; ?>
                </tr>
              </thead>
              <tbody id="tbodyApproversChecker">
                <tr><td colspan="4" class="text-center py-3 text-muted">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Danh sách Quản lý phê duyệt -->
    <div class="col-lg-6">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="material-icons" style="color: #8b5cf6;">verified</span>
            <span>Cấp 3: Quản Lý Phê Duyệt (Managers)</span>
          </h6>
          <?php if ($canEditApprovers): ?>
          <button class="app-btn app-btn-outline btn-sm" onclick="openAddApproverModal('manager')">
            <span class="material-icons" style="font-size: 15px;">add</span>
            <span>Thêm</span>
          </button>
          <?php endif; ?>
        </div>
        <div class="p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small text-muted">
                <tr>
                  <th class="ps-3">Quản Lý</th>
                  <th>Nhóm Phụ Trách</th>
                  <th class="text-center">Trạng Thái</th>
                  <?php if ($canEditApprovers): ?><th class="text-end pe-3">Thao Tác</th><?php endif; ?>
                </tr>
              </thead>
              <tbody id="tbodyApproversManager">
                <tr><td colspan="4" class="text-center py-3 text-muted">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Danh sách Thủ kho / Admin xuất hàng -->
    <div class="col-lg-6">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="material-icons text-info">warehouse</span>
            <span>Cấp 4: Thủ Kho / Admin Xuất Hàng (Storekeepers)</span>
          </h6>
          <?php if ($canEditApprovers): ?>
          <button class="app-btn app-btn-outline btn-sm" onclick="openAddApproverModal('admin_warehouse')">
            <span class="material-icons" style="font-size: 15px;">add</span>
            <span>Thêm</span>
          </button>
          <?php endif; ?>
        </div>
        <div class="p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small text-muted">
                <tr>
                  <th class="ps-3">Thủ Kho / Admin</th>
                  <th>Nhóm Phụ Trách</th>
                  <th class="text-center">Trạng Thái</th>
                  <?php if ($canEditApprovers): ?><th class="text-end pe-3">Thao Tác</th><?php endif; ?>
                </tr>
              </thead>
              <tbody id="tbodyApproversAdmin">
                <tr><td colspan="4" class="text-center py-3 text-muted">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Danh sách Người nhận bàn giao hiện trường -->
    <div class="col-lg-6">
      <div class="app-card border h-100">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="material-icons text-success">handshake</span>
            <span>Cấp 5: Người Nhận Bàn Giao Hiện Trường (Receivers)</span>
          </h6>
          <?php if ($canEditApprovers): ?>
          <button class="app-btn app-btn-outline btn-sm" onclick="openAddApproverModal('receiver')">
            <span class="material-icons" style="font-size: 15px;">add</span>
            <span>Thêm</span>
          </button>
          <?php endif; ?>
        </div>
        <div class="p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small text-muted">
                <tr>
                  <th class="ps-3">Người Nhận Bàn Giao</th>
                  <th>Nhóm Phụ Trách</th>
                  <th class="text-center">Trạng Thái</th>
                  <?php if ($canEditApprovers): ?><th class="text-end pe-3">Thao Tác</th><?php endif; ?>
                </tr>
              </thead>
              <tbody id="tbodyApproversReceiver">
                <tr><td colspan="4" class="text-center py-3 text-muted">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div> <!-- /tabContentApprovers -->

  <!-- TAB 2: CÀI ĐẶT DANH MỤC & NHÓM VẬT TƯ (ĐĂNG KÝ NHIỀU NHÓM, IMPORT/EXPORT) -->
  <div id="tabContentMaterials" style="display: none;">
    <!-- KPI Thống Kê Danh Mục -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="app-card p-3 border text-center h-100" style="border-top: 3px solid #3b82f6 !important;">
          <div class="text-muted small fw-semibold text-uppercase">Tổng Số Vật Tư</div>
          <div class="h4 mb-0 fw-bold text-primary mt-1" id="mStatTotal">0</div>
          <small class="text-muted">Mặt hàng trong kho</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="app-card p-3 border text-center h-100" style="border-top: 3px solid #8b5cf6 !important;">
          <div class="text-muted small fw-semibold text-uppercase">Dùng Cho Nhiều Nhóm</div>
          <div class="h4 mb-0 fw-bold text-purple mt-1" style="color: #8b5cf6;" id="mStatMultiGroup">0</div>
          <small class="text-muted">Đăng ký &ge; 2 nhóm tiêu hao</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="app-card p-3 border text-center h-100" style="border-top: 3px solid #10b981 !important;">
          <div class="text-muted small fw-semibold text-uppercase">Vật Tư Tiêu Hao (Định Mức)</div>
          <div class="h4 mb-0 fw-bold text-success mt-1" id="mStatConsumable">0</div>
          <small class="text-muted">Tự động tính theo A x B x C</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="app-card p-3 border text-center h-100" style="border-top: 3px solid #f59e0b !important;">
          <div class="text-muted small fw-semibold text-uppercase">Dưới Điểm Đặt Hàng (ROP)</div>
          <div class="h4 mb-0 fw-bold text-warning mt-1" id="mStatBelowRop">0</div>
          <small class="text-muted">Cần đặt hàng lại</small>
        </div>
      </div>
    </div>

    <!-- Thanh Lọc & Nút Xuất/Nhập Excel -->
    <div class="app-card p-3 mb-3 border">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
          <div style="min-width: 220px;" class="flex-grow-1">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><span class="material-icons text-primary" style="font-size: 16px;">search</span></span>
              <input type="text" class="form-control" id="matSettingsSearch" placeholder="Tìm theo mã VT, mã SAP, tên vật tư, kệ BIN..." onkeyup="filterMaterialsSettings()">
            </div>
          </div>
          <div style="width: 170px;">
            <select class="form-select form-select-sm" id="matSettingsFilterGroup" onchange="filterMaterialsSettings()">
              <option value="ALL">-- Tất cả nhóm --</option>
              <option value="Thiết bị">Nhóm Thiết bị</option>
              <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
              <option value="Sản xuất">Nhóm Sản xuất</option>
              <option value="Nghiền">Nhóm Nghiền</option>
            </select>
          </div>
          <div style="width: 160px;">
            <select class="form-select form-select-sm" id="matSettingsFilterCategory" onchange="filterMaterialsSettings()">
              <option value="ALL">-- Tất cả loại --</option>
              <option value="consumable">Vật tư tiêu hao</option>
              <option value="irregular">Vật tư bất thường</option>
            </select>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <a href="api/warehouse.php?action=export_materials_excel" class="app-btn app-btn-outline btn-sm text-success border-success d-flex align-items-center gap-1" title="Xuất toàn bộ danh mục và số lượng tồn kho hiện tại ra file Excel">
            <span class="material-icons" style="font-size: 16px;">file_download</span>
            <span>Xuất Excel Tồn Kho</span>
          </a>
          <?php if ($canEditApprovers): ?>
          <button class="app-btn app-btn-success btn-sm d-flex align-items-center gap-1" type="button" onclick="openImportStockModal()" title="Nhập cập nhật tồn kho hàng loạt từ file Excel/CSV">
            <span class="material-icons" style="font-size: 16px;">upload_file</span>
            <span>Import Tồn Kho</span>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Bảng Danh Mục Vật Tư & Nhóm -->
    <div class="app-card border overflow-hidden mb-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tableMaterialsSettings" style="font-size: 12.5px;">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="text-center">STT</th>
              <th style="width: 130px;">Mã VT / SAP</th>
              <th>Tên Vật Tư & Quy Cách</th>
              <th style="width: 110px;">Nhóm Chính</th>
              <th style="min-width: 180px;">Các Nhóm Tiêu Hao (Đăng ký nhiều nhóm)</th>
              <th style="width: 100px;">Loại VT</th>
              <th style="width: 140px;" class="text-center">Định Mức (A x B x C)</th>
              <th style="width: 110px;" class="text-end">Tồn Kho Hiện Tại</th>
              <th style="width: 80px;" class="text-center">Kệ BIN</th>
              <th style="width: 80px;" class="text-center">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="tbodyMaterialsSettings">
            <tr>
              <td colspan="10" class="text-center py-4 text-muted">Đang tải danh mục vật tư...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div> <!-- /tabContentMaterials -->

  <!-- =========================================================================
       MODAL CHỈNH SỬA DANH MỤC VẬT TƯ & ĐĂNG KÝ NHIỀU NHÓM TIÊU HAO
       ========================================================================= -->
  <div class="modal fade" id="modalEditMaterialSettings" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-light border-bottom py-2">
          <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
            <span class="material-icons text-primary">edit_note</span>
            <span>CHỈNH SỬA THÔNG TIN & NHÓM ÁP DỤNG VẬT TƯ</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-3">
          <form id="formEditMaterialSettings" onsubmit="event.preventDefault(); submitSaveMaterialSettings();">
            <input type="hidden" id="editMatId" value="0">

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold">Mã Vật Tư:</label>
                <input type="text" id="editMatCode" class="form-control form-control-sm font-monospace bg-light fw-bold" readonly>
              </div>
              <div class="col-md-8">
                <label class="form-label small fw-bold">Tên Vật Tư (Tiếng Việt): <span class="text-danger">*</span></label>
                <input type="text" id="editMatNameVn" class="form-control form-control-sm fw-bold" required>
              </div>
            </div>

            <!-- NHÓM CHÍNH VÀ ĐĂNG KÝ SỬ DỤNG TIÊU HAO CHO NHIỀU NHÓM (YÊU CẦU 3) -->
            <div class="p-3 mb-3 rounded border" style="background: #f8fafc;">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-primary">Nhóm Chính (Mặc định): <span class="text-danger">*</span></label>
                  <select id="editMatGroup" class="form-select form-select-sm" required>
                    <option value="Thiết bị">Nhóm Thiết bị</option>
                    <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
                    <option value="Sản xuất">Nhóm Sản xuất</option>
                    <option value="Nghiền">Nhóm Nghiền</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="form-label small fw-bold text-primary">
                    Đăng Ký Sử Dụng Tiêu Hao Cho Nhiều Nhóm:
                    <span class="badge bg-primary-subtle text-primary border ms-1" style="font-size:10px;">Một VT dùng nhiều nhóm</span>
                  </label>
                  <div class="d-flex flex-wrap gap-3 pt-1">
                    <div class="form-check">
                      <input class="form-check-input chk-app-group" type="checkbox" value="Thiết bị" id="chkGrpThietBi">
                      <label class="form-check-label small fw-semibold" for="chkGrpThietBi">Thiết bị</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input chk-app-group" type="checkbox" value="Bảo trì khuôn" id="chkGrpBaoTriKhuon">
                      <label class="form-check-label small fw-semibold" for="chkGrpBaoTriKhuon">Bảo trì khuôn</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input chk-app-group" type="checkbox" value="Sản xuất" id="chkGrpSanXuat">
                      <label class="form-check-label small fw-semibold" for="chkGrpSanXuat">Sản xuất</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input chk-app-group" type="checkbox" value="Nghiền" id="chkGrpNghien">
                      <label class="form-check-label small fw-semibold" for="chkGrpNghien">Nghiền</label>
                    </div>
                  </div>
                  <small class="text-muted" style="font-size: 11px;">Khi được chọn, mã hàng này sẽ tự động xuất hiện trong danh sách Đề xuất vật tư của các nhóm đó.</small>
                </div>
              </div>
            </div>

            <!-- Phân loại & Quy cách -->
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold">Phân Loại Vật Tư:</label>
                <select id="editMatCategory" class="form-select form-select-sm">
                  <option value="consumable">Vật tư tiêu hao (Định mức)</option>
                  <option value="irregular">Vật tư bất thường (Sự cố)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Đơn Vị Tính (ĐVT):</label>
                <input type="text" id="editMatUnit" class="form-control form-control-sm" placeholder="Ea, Roll, Chai, Gói...">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Quy Cách Đóng Gói:</label>
                <input type="text" id="editMatSpec" class="form-control form-control-sm" placeholder="10 Ea/gói, Chai 500ml...">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Số Lượng / Gói Quy Đổi:</label>
                <input type="number" step="1" min="1" id="editMatPackQty" class="form-control form-control-sm font-monospace" value="1">
              </div>
            </div>

            <!-- Định mức tiêu hao mặc định -->
            <div class="p-3 mb-3 rounded border" style="background: #eff6ff;">
              <h6 class="small fw-bold text-primary mb-2">Định Mức Tiêu Hao Mặc Định (A x B x C):</h6>
              <div class="row g-2 text-center">
                <div class="col-md-4">
                  <label class="form-label small text-muted mb-1">Số máy/line mặc định (A)</label>
                  <input type="number" min="0" step="1" id="editMatNormA" class="form-control form-control-sm font-monospace text-center">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-muted mb-1">Số lần/máy mặc định (B)</label>
                  <input type="number" min="0" step="1" id="editMatNormB" class="form-control form-control-sm font-monospace text-center">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-muted mb-1">Định mức cho mỗi lần (C)</label>
                  <input type="number" min="0" step="0.1" id="editMatNormC" class="form-control form-control-sm font-monospace text-center">
                </div>
              </div>
            </div>

            <!-- Quản trị Tồn kho & ROP -->
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold">Kệ / Vị Trí BIN:</label>
                <input type="text" id="editMatBin" class="form-control form-control-sm font-monospace text-uppercase" placeholder="BIN-A1-02...">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Tồn Kho Hiện Tại:</label>
                <input type="number" step="0.1" min="0" id="editMatStock" class="form-control form-control-sm font-monospace fw-bold text-success">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Điểm Đặt Hàng (ROP):</label>
                <input type="number" step="0.1" min="0" id="editMatRop" class="form-control form-control-sm font-monospace fw-bold text-danger">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Lượng Đặt Chuẩn (MOQ):</label>
                <input type="number" step="0.1" min="0" id="editMatMoq" class="form-control form-control-sm font-monospace">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="button" class="app-btn app-btn-primary btn-sm" onclick="submitSaveMaterialSettings()">
            <span class="material-icons" style="font-size: 16px;">save</span>
            <span>Lưu Thay Đổi Vật Tư</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       MODAL IMPORT DỮ LIỆU TỒN KHO HÀNG LOẠT
       ========================================================================= -->
  <div class="modal fade" id="modalImportStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-light border-bottom py-2">
          <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
            <span class="material-icons text-success">upload_file</span>
            <span>IMPORT DỮ LIỆU TỒN KHO VẬT TƯ HÀNG LOẠT</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="p-3 mb-3 bg-light rounded border small">
            <strong>Hướng dẫn nạp dữ liệu:</strong>
            <ul class="mb-1 ps-3">
              <li>Hỗ trợ file định dạng <strong>Excel (.xlsx)</strong> hoặc <strong>CSV (.csv)</strong>.</li>
              <li>Hệ thống sẽ tự động đối chiếu theo cột <strong>Mã Vật Tư (item_code)</strong> để cập nhật Tồn hiện tại, Điểm đặt hàng ROP, Số lượng đặt MOQ và Vị trí kệ BIN.</li>
              <li>Bạn có thể tải file danh mục hiện tại bằng nút <em>"Xuất Excel Tồn Kho"</em>, cập nhật số liệu rồi tải lên lại.</li>
            </ul>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Chọn File Excel / CSV Cần Import: <span class="text-danger">*</span></label>
            <input type="file" id="fileImportStock" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
          </div>

          <div id="importResultAlert" class="alert d-none py-2 px-3 small mb-0"></div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="app-btn app-btn-success btn-sm d-flex align-items-center gap-1" id="btnSubmitImportStock" onclick="submitImportStock()">
            <span class="material-icons" style="font-size: 16px;">file_upload</span>
            <span>Bắt Đầu Import Tồn Kho</span>
          </button>
        </div>
      </div>
    </div>
  </div>


<!-- =========================================================================
     MODAL THÊM / CHỈNH SỬA NGƯỜI PHÊ DUYỆT
     ========================================================================= -->
<?php if ($canEditApprovers): ?>
<div class="modal fade" id="modalApproverForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">person_add</span>
          <span id="mApproverFormTitle">THÊM NGƯỜI PHÊ DUYỆT MỚI</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="formApprover" onsubmit="event.preventDefault(); submitApproverForm();">
          <input type="hidden" id="approverId" value="0">

          <div class="mb-3">
            <label class="form-label small fw-bold">Vai Trò Phê Duyệt <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="approverRoleType" required>
              <option value="checker">Người kiểm tra (Bước 2)</option>
              <option value="manager">Quản lý phê duyệt (Bước 3)</option>
              <option value="admin_warehouse">Thủ kho / Admin xuất hàng (Bước 4)</option>
              <option value="receiver">Người nhận bàn giao hiện trường (Bước 5)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Nhóm Công Việc Phụ Trách <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="approverGroup" required>
              <option value="ALL">ALL - Áp dụng cho TẤT CẢ các nhóm</option>
              <option value="Thiết bị">Nhóm Thiết bị</option>
              <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
              <option value="Sản xuất">Nhóm Sản xuất</option>
              <option value="Nghiền">Nhóm Nghiền</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Chọn Từ Tài Khoản Hệ Thống (Tùy chọn)</label>
            <select class="form-select form-select-sm" id="selectSystemUser" onchange="onSelectSystemUser(this)">
              <option value="">-- Chọn tài khoản có sẵn --</option>
              <?php foreach ($systemUsers as $su): ?>
                <option value="<?= htmlspecialchars($su['username']) ?>" data-fullname="<?= htmlspecialchars($su['fullname']) ?>">
                  <?= htmlspecialchars($su['fullname']) ?> (<?= htmlspecialchars($su['username']) ?> - <?= htmlspecialchars($su['role']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Tên đăng nhập (Username) <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm font-monospace" id="approverUsername" placeholder="Ví dụ: tuantran" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Họ và tên đầy đủ <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="approverFullname" placeholder="Ví dụ: Trần Văn Tuấn" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="app-btn app-btn-primary" onclick="submitApproverForm()">
          <span class="material-icons">save</span>
          <span>Lưu Cấu Hình</span>
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
let approversMasterData = null;
const canEditApproversPerm = <?= json_encode($canEditApprovers) ?>;

document.addEventListener('DOMContentLoaded', function() {
  loadApproversSettings();
});

// 1. Tải danh sách người duyệt
function loadApproversSettings() {
  fetch('api/warehouse.php?action=get_approvers')
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert(res.message || 'Lỗi tải danh sách người duyệt.');
        return;
      }

      approversMasterData = res.approvers || {};
      renderApproversCategory('checker', approversMasterData.checker || [], 'tbodyApproversChecker', 'countChecker');
      renderApproversCategory('manager', approversMasterData.manager || [], 'tbodyApproversManager', 'countManager');
      renderApproversCategory('admin_warehouse', approversMasterData.admin_warehouse || [], 'tbodyApproversAdmin', 'countAdmin');
      renderApproversCategory('receiver', approversMasterData.receiver || [], 'tbodyApproversReceiver', 'countReceiver');
    })
    .catch(err => {
      console.error(err);
      alert('Không thể kết nối máy chủ API.');
    });
}

// 2. Render từng bảng người duyệt
function renderApproversCategory(roleType, list, tbodyId, countId) {
  const tbody = document.getElementById(tbodyId);
  document.getElementById(countId).textContent = `${list.length} người`;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">Chưa cấu hình tài khoản nào.</td></tr>`;
    return;
  }

  let html = '';
  list.forEach(item => {
    const groupBadge = (item.group_name === 'ALL')
      ? '<span class="badge bg-primary text-white">Tất cả nhóm (ALL)</span>'
      : `<span class="badge bg-light text-dark border">${escapeHtml(item.group_name)}</span>`;

    const actions = canEditApproversPerm 
      ? `
        <td class="text-end pe-3">
          <button class="btn btn-sm btn-outline-secondary p-1" title="Chỉnh sửa" onclick="openEditApproverModal(${item.id}, '${item.role_type}', '${escapeHtml(item.group_name)}', '${escapeHtml(item.username)}', '${escapeHtml(item.full_name)}')">
            <span class="material-icons" style="font-size: 15px;">edit</span>
          </button>
          <button class="btn btn-sm btn-outline-danger p-1 ms-1" title="Xóa" onclick="deleteApprover(${item.id}, '${escapeHtml(item.full_name)}')">
            <span class="material-icons" style="font-size: 15px;">delete</span>
          </button>
        </td>`
      : '';

    html += `
      <tr>
        <td class="ps-3">
          <div class="fw-semibold text-main">${escapeHtml(item.full_name)}</div>
          <div class="small text-muted font-monospace">${escapeHtml(item.username)}</div>
        </td>
        <td>${groupBadge}</td>
        <td class="text-center">
          <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
        </td>
        ${actions}
      </tr>`;
  });

  tbody.innerHTML = html;
}

// 3. Mở modal thêm người duyệt
function openAddApproverModal(defaultRoleType = 'checker') {
  document.getElementById('approverId').value = 0;
  document.getElementById('mApproverFormTitle').textContent = 'THÊM NGƯỜI PHÊ DUYỆT MỚI';
  document.getElementById('approverRoleType').value = defaultRoleType;
  document.getElementById('approverGroup').value = 'ALL';
  document.getElementById('selectSystemUser').value = '';
  document.getElementById('approverUsername').value = '';
  document.getElementById('approverFullname').value = '';

  const modal = new bootstrap.Modal(document.getElementById('modalApproverForm'));
  modal.show();
}

// 4. Mở modal sửa người duyệt
function openEditApproverModal(id, roleType, groupName, username, fullName) {
  document.getElementById('approverId').value = id;
  document.getElementById('mApproverFormTitle').textContent = 'CHỈNH SỬA NGƯỜI PHÊ DUYỆT';
  document.getElementById('approverRoleType').value = roleType;
  document.getElementById('approverGroup').value = groupName;
  document.getElementById('selectSystemUser').value = username;
  document.getElementById('approverUsername').value = username;
  document.getElementById('approverFullname').value = fullName;

  const modal = new bootstrap.Modal(document.getElementById('modalApproverForm'));
  modal.show();
}

// 5. Khi chọn tài khoản hệ thống từ dropdown
function onSelectSystemUser(select) {
  const selectedOpt = select.options[select.selectedIndex];
  if (select.value) {
    document.getElementById('approverUsername').value = select.value;
    document.getElementById('approverFullname').value = selectedOpt.getAttribute('data-fullname') || select.value;
  }
}

// 6. Gửi dữ liệu lưu
function submitApproverForm() {
  const id       = document.getElementById('approverId').value;
  const roleType = document.getElementById('approverRoleType').value;
  const group    = document.getElementById('approverGroup').value;
  const username = document.getElementById('approverUsername').value.trim();
  const fullname = document.getElementById('approverFullname').value.trim();

  if (!roleType || !username || !fullname) {
    alert('Vui lòng điền đầy đủ vai trò, tên đăng nhập và họ tên.');
    return;
  }

  const formData = new FormData();
  formData.append('id', id);
  formData.append('role_type', roleType);
  formData.append('group_name', group);
  formData.append('username', username);
  formData.append('full_name', fullname);

  fetch('api/warehouse.php?action=save_approver', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Lưu thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalApproverForm'));
        if (modal) modal.hide();
        loadApproversSettings();
      } else {
        alert(res.message || 'Lưu thất bại.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi lưu người phê duyệt.');
    });
}

// 7. Xóa người duyệt
function deleteApprover(id, fullName) {
  if (!confirm(`Bạn có chắc chắn muốn xóa quyền phê duyệt của [${fullName}]?`)) return;

  const formData = new FormData();
  formData.append('id', id);

  fetch('api/warehouse.php?action=delete_approver', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Đã xóa thành công!');
        loadApproversSettings();
      } else {
        alert(res.message || 'Xóa thất bại.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi xóa.');
    });
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

/* =========================================================================
   JAVASCRIPT CHO TAB 2: CÀI ĐẶT DANH MỤC & NHÓM VẬT TƯ (YÊU CẦU 3)
   ========================================================================= */

let allMaterialsSettingsCache = [];

function switchSettingsTab(tab) {
  const btnApp = document.getElementById('tabBtnApprovers');
  const btnMat = document.getElementById('tabBtnMaterials');
  const cApp   = document.getElementById('tabContentApprovers');
  const cMat   = document.getElementById('tabContentMaterials');

  if (tab === 'materials') {
    btnApp.classList.remove('app-btn-primary', 'active');
    btnApp.classList.add('app-btn-outline');
    btnMat.classList.remove('app-btn-outline');
    btnMat.classList.add('app-btn-primary', 'active');

    cApp.style.display = 'none';
    cMat.style.display = 'block';

    if (allMaterialsSettingsCache.length === 0) {
      loadMaterialsSettings();
    }
  } else {
    btnMat.classList.remove('app-btn-primary', 'active');
    btnMat.classList.add('app-btn-outline');
    btnApp.classList.remove('app-btn-outline');
    btnApp.classList.add('app-btn-primary', 'active');

    cMat.style.display = 'none';
    cApp.style.display = 'block';
  }
}

function loadMaterialsSettings() {
  fetch('api/warehouse.php?action=get_materials&include_inactive=1')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        allMaterialsSettingsCache = res.materials || [];
        updateMaterialsSettingsStats();
        filterMaterialsSettings();
      }
    })
    .catch(err => console.error('Lỗi tải danh mục vật tư:', err));
}

function updateMaterialsSettingsStats() {
  const total = allMaterialsSettingsCache.length;
  let multiGrp = 0;
  let consumable = 0;
  let belowRop = 0;

  allMaterialsSettingsCache.forEach(m => {
    const grps = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim()).filter(Boolean);
    if (grps.length > 1) multiGrp++;
    if (m.category_type === 'consumable') consumable++;
    if (parseFloat(m.stock_current) <= parseFloat(m.reorder_point)) belowRop++;
  });

  document.getElementById('mStatTotal').textContent = total;
  document.getElementById('mStatMultiGroup').textContent = multiGrp;
  document.getElementById('mStatConsumable').textContent = consumable;
  document.getElementById('mStatBelowRop').textContent = belowRop;
}

function filterMaterialsSettings() {
  const search = document.getElementById('matSettingsSearch').value.toLowerCase().trim();
  const group  = document.getElementById('matSettingsFilterGroup').value;
  const cat    = document.getElementById('matSettingsFilterCategory').value;
  const tbody  = document.getElementById('tbodyMaterialsSettings');

  const filtered = allMaterialsSettingsCache.filter(m => {
    if (group !== 'ALL') {
      const appGrps = (m.applicable_groups || m.group_name || '');
      if (m.group_name !== group && !appGrps.includes(group)) return false;
    }
    if (cat !== 'ALL' && m.category_type !== cat) return false;
    if (search) {
      const target = `${m.item_code} ${m.item_name_vn} ${m.item_name_en || ''} ${m.bin_location || ''}`.toLowerCase();
      if (!target.includes(search)) return false;
    }
    return true;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">Không tìm thấy vật tư nào phù hợp với bộ lọc.</td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach((m, idx) => {
    const appGrps = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim()).filter(Boolean);
    const grpBadges = appGrps.map(g => `<span class="badge bg-primary-subtle text-primary border me-1 mb-1 font-monospace" style="font-size:10.5px;">${escapeHtml(g)}</span>`).join('');

    const isConsumable = (m.category_type === 'consumable');
    const catBadge = isConsumable 
      ? '<span class="badge bg-success-subtle text-success border">Tiêu hao</span>' 
      : '<span class="badge bg-warning-subtle text-warning border">Bất thường</span>';

    const stock = Number(m.stock_current || 0).toFixed(1);
    const rop   = Number(m.reorder_point || 0).toFixed(1);
    const isBelowRop = (parseFloat(stock) <= parseFloat(rop));

    const normText = isConsumable
      ? `<div class="font-monospace small">A:<strong>${m.default_machines || 1}</strong> x B:<strong>${m.default_uses_per_machine || 1}</strong> x C:<strong>${Number(m.norm_per_use || 1).toFixed(1)}</strong></div>`
      : '<span class="text-muted small">Xuất đột xuất</span>';

    html += `
      <tr>
        <td class="text-center text-muted fw-bold">${idx + 1}</td>
        <td>
          <strong class="font-monospace text-primary">${escapeHtml(m.item_code)}</strong>
          ${m.sap_code ? `<div class="text-muted font-monospace small">SAP: ${escapeHtml(m.sap_code)}</div>` : ''}
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(m.item_name_vn)}</div>
          <div class="small text-muted">${escapeHtml(m.packaging_spec || 'Gói lẻ')} (${Number(m.pack_quantity || 1).toFixed(1)} ${escapeHtml(m.unit)}/gói)</div>
        </td>
        <td><span class="badge bg-light text-dark border">${escapeHtml(m.group_name)}</span></td>
        <td>${grpBadges}</td>
        <td>${catBadge}</td>
        <td class="text-center">${normText}</td>
        <td class="text-end font-monospace">
          <strong class="${isBelowRop ? 'text-danger' : 'text-success'}">${stock}</strong> <small class="text-muted">${escapeHtml(m.unit)}</small>
          <div class="text-muted small" style="font-size:10px;">ROP: ${rop}</div>
        </td>
        <td class="text-center font-monospace small"><span class="badge bg-light text-secondary border">${escapeHtml(m.bin_location || '-')}</span></td>
        <td class="text-center">
          <button class="app-btn app-btn-outline btn-sm py-1 px-2 text-primary border-primary-subtle" onclick="openEditMaterialModal(${m.id})" title="Chỉnh sửa nhóm, loại và thông số định mức">
            <span class="material-icons" style="font-size: 15px;">edit</span>
          </button>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function openEditMaterialModal(id) {
  const m = allMaterialsSettingsCache.find(it => it.id == id);
  if (!m) return;

  document.getElementById('editMatId').value = m.id;
  document.getElementById('editMatCode').value = m.item_code;
  document.getElementById('editMatNameVn').value = m.item_name_vn;
  document.getElementById('editMatGroup').value = m.group_name || 'Thiết bị';
  document.getElementById('editMatCategory').value = m.category_type || 'consumable';
  document.getElementById('editMatUnit').value = m.unit || 'Ea';
  document.getElementById('editMatSpec').value = m.packaging_spec || '';
  document.getElementById('editMatPackQty').value = m.pack_quantity || 1;
  document.getElementById('editMatNormA').value = m.default_machines || 1;
  document.getElementById('editMatNormB').value = m.default_uses_per_machine || 1;
  document.getElementById('editMatNormC').value = Number(m.norm_per_use || 1).toFixed(1);
  document.getElementById('editMatBin').value = m.bin_location || '';
  document.getElementById('editMatStock').value = Number(m.stock_current || 0).toFixed(1);
  document.getElementById('editMatRop').value = Number(m.reorder_point || 0).toFixed(1);
  document.getElementById('editMatMoq').value = Number(m.reorder_qty || 0).toFixed(1);

  // Tick checkboxes cho các nhóm áp dụng (Đăng ký nhiều nhóm)
  const appGrps = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim());
  document.querySelectorAll('.chk-app-group').forEach(chk => {
    chk.checked = appGrps.includes(chk.value);
  });

  // Nếu chưa tick nhóm chính thì tự tick
  const mainGrp = m.group_name;
  document.querySelectorAll('.chk-app-group').forEach(chk => {
    if (chk.value === mainGrp) chk.checked = true;
  });

  new bootstrap.Modal(document.getElementById('modalEditMaterialSettings')).show();
}

function submitSaveMaterialSettings() {
  const id = document.getElementById('editMatId').value;
  const nameVn = document.getElementById('editMatNameVn').value.trim();
  const groupName = document.getElementById('editMatGroup').value;

  if (!nameVn) {
    alert('Vui lòng nhập tên vật tư.');
    return;
  }

  // Thu thập các nhóm được tích chọn
  const checkedGroups = [];
  document.querySelectorAll('.chk-app-group:checked').forEach(chk => {
    checkedGroups.push(chk.value);
  });
  if (!checkedGroups.includes(groupName)) {
    checkedGroups.unshift(groupName);
  }
  const applicableGroups = checkedGroups.join(', ');

  const formData = new FormData();
  formData.append('material_id', id);
  formData.append('id', id);
  formData.append('item_name_vn', nameVn);
  formData.append('group_name', groupName);
  formData.append('applicable_groups', applicableGroups);
  formData.append('category_type', document.getElementById('editMatCategory').value);
  formData.append('unit', document.getElementById('editMatUnit').value.trim());
  formData.append('packaging_spec', document.getElementById('editMatSpec').value.trim());
  formData.append('pack_quantity', document.getElementById('editMatPackQty').value);
  formData.append('default_machines', document.getElementById('editMatNormA').value);
  formData.append('default_uses_per_machine', document.getElementById('editMatNormB').value);
  formData.append('norm_per_use', document.getElementById('editMatNormC').value);
  formData.append('bin_location', document.getElementById('editMatBin').value.trim());
  formData.append('stock_current', document.getElementById('editMatStock').value);
  formData.append('reorder_point', document.getElementById('editMatRop').value);
  formData.append('reorder_qty', document.getElementById('editMatMoq').value);

  fetch('api/warehouse.php?action=save_material_settings', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Đã cập nhật thông tin vật tư thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditMaterialSettings'));
        if (modal) modal.hide();
        loadMaterialsSettings();
      } else {
        alert(res.message || 'Cập nhật thất bại.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi lưu cài đặt vật tư.');
    });
}

function openImportStockModal() {
  document.getElementById('fileImportStock').value = '';
  const alertEl = document.getElementById('importResultAlert');
  alertEl.className = 'alert d-none py-2 px-3 small mb-0';
  alertEl.textContent = '';
  new bootstrap.Modal(document.getElementById('modalImportStock')).show();
}

function submitImportStock() {
  const fileInput = document.getElementById('fileImportStock');
  if (!fileInput.files || fileInput.files.length === 0) {
    alert('Vui lòng chọn file Excel hoặc CSV để import.');
    return;
  }

  const formData = new FormData();
  formData.append('excel_file', fileInput.files[0]);

  const btn = document.getElementById('btnSubmitImportStock');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang nạp dữ liệu...';

  const alertEl = document.getElementById('importResultAlert');
  alertEl.className = 'alert alert-info py-2 px-3 small mb-0';
  alertEl.textContent = 'Đang đọc và đối chiếu dữ liệu tồn kho...';

  fetch('api/warehouse.php?action=import_materials_stock', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alertEl.className = 'alert alert-success py-2 px-3 small mb-0';
        alertEl.innerHTML = `<strong>Thành công!</strong> ${escapeHtml(res.message)}`;
        loadMaterialsSettings();
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportStock'));
          if (modal) modal.hide();
        }, 1800);
      } else {
        alertEl.className = 'alert alert-danger py-2 px-3 small mb-0';
        alertEl.innerHTML = `<strong>Lỗi:</strong> ${escapeHtml(res.message)}`;
      }
    })
    .catch(err => {
      console.error(err);
      alertEl.className = 'alert alert-danger py-2 px-3 small mb-0';
      alertEl.textContent = 'Lỗi kết nối máy chủ khi import dữ liệu.';
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-icons" style="font-size: 16px;">file_upload</span> <span>Bắt Đầu Import Tồn Kho</span>';
    });
}
</script>
