<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Danh Mục Vật Tư & Quản Lý Tồn Kho (Materials & Stock Catalogue)
 * DX Plastic Group - Factory Management System
 */

$currentMonth = intval(date('m'));
$currentYear  = intval(date('Y'));
$userRole     = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');
$canManageStock = hasPermission(['warehouse.manage', 'admin']);
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">category</span>
        <span>DANH MỤC VẬT TƯ & THEO DÕI TỒN KHO - ROP</span>
      </h1>
      <p class="app-page-subtitle">Quản lý 45+ chủng loại vật tư tiêu hao và bất thường, hình ảnh thực tế, vị trí BIN, định mức và điểm đặt hàng lại (ROP)</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php if ($canManageStock): ?>
      <button type="button" class="app-btn app-btn-primary" onclick="openCreateMaterialModal()" title="Đăng ký thêm vật tư mới vào hệ thống">
        <span class="material-icons">add_box</span>
        <span>Đăng Ký Vật Tư Mới</span>
      </button>
      <?php endif; ?>
      <a href="api/warehouse.php?action=export_materials_excel" class="app-btn app-btn-outline text-success border-success" title="Xuất toàn bộ danh mục vật tư & tồn kho ra file Excel">
        <span class="material-icons">file_download</span>
        <span>Xuất Excel</span>
      </a>
      <?php if ($canManageStock): ?>
      <button type="button" class="app-btn app-btn-outline" onclick="openImportStockModal()" title="Import cập nhật tồn kho từ file Excel">
        <span class="material-icons">file_upload</span>
        <span>Import Tồn Kho</span>
      </button>
      <button type="button" class="app-btn app-btn-outline text-warning border-warning" onclick="triggerSyncRopAlerts()" title="Tự động quét & gửi cảnh báo điểm đặt hàng ROP đến Thủ kho">
        <span class="material-icons">sync</span>
        <span>Quét ROP</span>
      </button>
      <?php endif; ?>
      <a href="index.php?mainpage=warehouse&subpage=issue_request" class="app-btn app-btn-outline">
        <span class="material-icons">post_add</span>
        <span>Tạo Phiếu Xuất</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-secondary">
        <span class="material-icons">verified_user</span>
        <span>Phê Duyệt</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=dashboard" class="app-btn app-btn-primary">
        <span class="material-icons">dashboard</span>
        <span>Dashboard</span>
      </a>
    </div>
  </div>

  <!-- Thống Kê Nhanh Tồn Kho & Sức Khỏe Kho (Inventory Health KPIs) -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100" style="border-top: 4px solid #3b82f6 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Tổng Số Mặt Hàng</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-primary" style="font-size: 26px;">inventory_2</span>
          <span class="h3 mb-0 fw-bold" id="statTotalItems">0</span>
        </div>
        <div class="small text-muted">Vật tư hoạt động trong kho</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100" style="border-top: 4px solid #10b981 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Tồn Kho An Toàn</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-success" style="font-size: 26px;">check_circle</span>
          <span class="h3 mb-0 fw-bold text-success" id="statSafeStock">0</span>
        </div>
        <div class="small text-muted">Tồn &gt; Điểm đặt hàng (ROP)</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100" style="border-top: 4px solid #f59e0b !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Cảnh Báo Dưới ROP</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-warning" style="font-size: 26px;">warning_amber</span>
          <span class="h3 mb-0 fw-bold text-warning" id="statRopAlerts">0</span>
        </div>
        <div class="small text-muted">Cần liên hệ đặt hàng lại</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100" style="border-top: 4px solid #ef4444 !important;">
        <div class="text-muted small fw-semibold text-uppercase mb-1">Hết Hàng / Khẩn Cấp</div>
        <div class="d-flex align-items-center justify-content-center gap-2 my-1">
          <span class="material-icons text-danger" style="font-size: 26px;">production_quantity_limits</span>
          <span class="h3 mb-0 fw-bold text-danger" id="statOutOfStock">0</span>
        </div>
        <div class="small text-muted">Tồn kho = 0 hoặc &le; 0.5 tháng</div>
      </div>
    </div>
  </div>

  <!-- Bộ Lọc Nâng Cao & Chuyển Đổi View -->
  <div class="app-card p-3 mb-4 border">
    <div class="row g-2 align-items-center">
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Nhóm Công Việc</label>
        <select class="form-select form-select-sm" id="matFilterGroup" onchange="filterMaterials()">
          <option value="ALL">-- Tất cả nhóm --</option>
          <option value="Thiết bị">Nhóm Thiết bị</option>
          <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
          <option value="Sản xuất">Nhóm Sản xuất</option>
          <option value="Nghiền">Nhóm Nghiền</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Tình Trạng Tồn Kho</label>
        <select class="form-select form-select-sm" id="matFilterStatus" onchange="filterMaterials()">
          <option value="ALL">-- Tất cả tình trạng --</option>
          <option value="rop_warning">Cảnh báo ROP (Tồn &le; ROP)</option>
          <option value="out_of_stock">Hết hàng (Tồn = 0)</option>
          <option value="safe">Tồn kho an toàn</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Trạng Thái Sử Dụng</label>
        <select class="form-select form-select-sm" id="matFilterActive" onchange="filterMaterials()">
          <option value="ALL">-- Tất cả trạng thái --</option>
          <option value="1" selected>Đang sử dụng</option>
          <option value="0">Đã vô hiệu hóa</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold mb-1">Tìm Kiếm Vật Tư</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text"><span class="material-icons" style="font-size: 16px;">search</span></span>
          <input type="text" class="form-control" id="matFilterSearch" placeholder="Tìm theo mã VT, mã SAP, tên, vị trí BIN..." onkeyup="filterMaterials()">
          <button class="app-btn app-btn-outline btn-sm" onclick="document.getElementById('matFilterSearch').value=''; filterMaterials();">
            <span class="material-icons" style="font-size: 16px;">clear</span>
          </button>
        </div>
      </div>
      <div class="col-md-2 text-md-end pt-3 pt-md-0">
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-primary active" id="btnViewTable" onclick="switchMaterialView('table')">
            <span class="material-icons" style="font-size: 16px;">table_chart</span>
          </button>
          <button type="button" class="btn btn-outline-primary" id="btnViewGrid" onclick="switchMaterialView('grid')">
            <span class="material-icons" style="font-size: 16px;">grid_view</span>
          </button>
        </div>
        <button class="app-btn app-btn-outline btn-sm ms-1" onclick="loadAllMaterials()">
          <span class="material-icons">refresh</span>
        </button>
      </div>
    </div>
  </div>

  <!-- CHẾ ĐỘ 1: BẢNG CHI TIẾT (Table View) -->
  <div class="app-card border overflow-hidden mb-4" id="containerTableView">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="tableMaterials">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 50px;">STT</th>
            <th style="width: 70px;">Hình Ảnh</th>
            <th style="width: 150px;">Mã VT / SAP</th>
            <th>Tên Vật Tư & Quy Cách Đóng Gói</th>
            <th style="width: 120px;">Nhóm / Loại</th>
            <th style="width: 160px;">Định Mức Chuẩn<br><small class="text-primary">(A × B × C)</small></th>
            <th class="text-center" style="width: 100px;">Vị Trí BIN</th>
            <th class="text-end" style="width: 110px;">Tồn Kho</th>
            <th class="text-center" style="width: 100px;">ROP / MOQ</th>
            <th class="text-center" style="width: 120px;">Tình Trạng</th>
            <th class="text-end pe-3" style="width: 110px;">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="tbodyMaterials">
          <tr>
            <td colspan="11" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>Đang nạp danh mục vật tư...</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
      <div class="small text-muted" id="materialsCountInfo">Hiển thị 0 trên 0 vật tư</div>
    </div>
  </div>

  <!-- CHẾ ĐỘ 2: LƯỚI THẺ HÌNH ẢNH (Grid View) -->
  <div class="row g-3 d-none mb-4" id="containerGridView">
    <!-- Grid items will be rendered by JS -->
  </div>
</div>

<!-- =========================================================================
     MODAL CHI TIẾT VẬT TƯ & LỊCH SỬ TIÊU HAO 3 THÁNG
     ========================================================================= -->
<div class="modal fade" id="modalMaterialDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">info</span>
          <span>CHI TIẾT VẬT TƯ: <span id="mMatDetailCode" class="text-primary font-monospace">--</span></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-4">
          <!-- Ảnh lớn -->
          <div class="col-md-5 text-center">
            <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height: 240px;">
              <img src="" id="mMatDetailImage" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;" alt="Hình ảnh sản phẩm">
            </div>
            <div class="mt-2 text-muted small">Hình ảnh thực tế trong kho DX Plastic Group</div>
          </div>

          <!-- Thông tin chính -->
          <div class="col-md-7">
            <h5 class="fw-bold text-main mb-1" id="mMatDetailName">Tên vật tư</h5>
            <div class="badge bg-primary-subtle text-primary border border-primary-subtle mb-3" id="mMatDetailGroup">Nhóm Thiết bị</div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <div class="p-2 border rounded bg-white">
                  <div class="text-muted small">Mã SAP</div>
                  <strong class="font-monospace" id="mMatDetailSap">--</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 border rounded bg-white">
                  <div class="text-muted small">Vị trí lưu kho (BIN)</div>
                  <strong class="font-monospace text-primary" id="mMatDetailBin">--</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 border rounded bg-white">
                  <div class="text-muted small">Quy cách đóng gói</div>
                  <strong id="mMatDetailSpec">--</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 border rounded bg-white">
                  <div class="text-muted small">Đơn vị tính</div>
                  <strong id="mMatDetailUnit">--</strong>
                </div>
              </div>
            </div>

            <!-- Định mức sản xuất chuẩn -->
            <div class="p-3 border rounded bg-light mb-3">
              <div class="fw-bold small text-uppercase text-muted mb-2">Định mức tiêu hao chuẩn (File T8.xlsx)</div>
              <div class="d-flex justify-content-between text-center">
                <div>
                  <div class="small text-muted">A (Số máy/người)</div>
                  <strong class="h6 mb-0 text-main" id="mMatDetailNormA">0</strong>
                </div>
                <div class="text-muted pt-2">&times;</div>
                <div>
                  <div class="small text-muted">B (Số lần/máy)</div>
                  <strong class="h6 mb-0 text-main" id="mMatDetailNormB">0</strong>
                </div>
                <div class="text-muted pt-2">&times;</div>
                <div>
                  <div class="small text-muted">C (Định mức/lần)</div>
                  <strong class="h6 mb-0 text-main" id="mMatDetailNormC">0</strong>
                </div>
              </div>
            </div>

            <!-- Tồn kho & ROP -->
            <div class="row g-2">
              <div class="col-4">
                <div class="p-2 border rounded text-center bg-white">
                  <div class="text-muted small">Tồn Hiện Tại</div>
                  <strong class="h5 mb-0 text-primary" id="mMatDetailStock">0</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded text-center bg-white">
                  <div class="text-muted small">Điểm ROP</div>
                  <strong class="h5 mb-0 text-warning" id="mMatDetailRop">0</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded text-center bg-white">
                  <div class="text-muted small">Đề xuất mua</div>
                  <strong class="h5 mb-0 text-success" id="mMatDetailMoq">0</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Lịch sử tiêu hao 3 tháng gần nhất -->
          <div class="col-12 border-top pt-3">
            <h6 class="fw-bold small text-uppercase text-muted mb-2 d-flex align-items-center gap-1">
              <span class="material-icons" style="font-size: 16px;">history</span>
              <span>Lịch Sử Xuất Kho 3 Tháng Gần Nhất (Tham khảo T5, T6, T7)</span>
            </h6>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle mb-0 text-center">
                <thead class="table-light small">
                  <tr>
                    <th>Tháng 5/2026</th>
                    <th>Tháng 6/2026</th>
                    <th>Tháng 7/2026</th>
                    <th class="table-primary text-primary">Trung Bình 3 Tháng</th>
                    <th class="table-info text-info">Số Tháng Dùng Còn Lại (Runway)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="fw-bold">
                    <td id="mMatDetailT5">0</td>
                    <td id="mMatDetailT6">0</td>
                    <td id="mMatDetailT7">0</td>
                    <td class="text-primary table-primary" id="mMatDetailAvg3M">0</td>
                    <td class="table-info" id="mMatDetailRunway">--</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Đóng</button>
        <?php if ($canManageStock): ?>
        <button type="button" class="app-btn app-btn-primary" onclick="openEditStockModalFromDetail()">
          <span class="material-icons">edit</span>
          <span>Điều Chỉnh Tồn Kho & ROP</span>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL CHỈNH SỬA VẬT TƯ & CẤU HÌNH ĐA NHÓM
     ========================================================================= -->
<?php if ($canManageStock): ?>
<!-- CSS Tinh chỉnh Giao diện Chuyên nghiệp cho Modal Cấu hình Vật Tư -->
<style>
#modalEditMaterialFull .modal-content {
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 16px;
  box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3);
  overflow: hidden;
}
#modalEditMaterialFull .modal-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  padding: 18px 24px;
}
#modalEditMaterialFull .modal-body {
  background: #f8fafc;
  padding: 24px;
  max-height: calc(85vh - 130px);
  overflow-y: auto;
}
.wh-edit-section {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}
.wh-edit-section:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}
.wh-edit-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}
.wh-group-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  cursor: pointer;
  user-select: none;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  transition: all 0.2s ease;
}
.wh-group-chip:hover {
  border-color: #3b82f6;
  background: #f0f7ff;
  color: #1d4ed8;
}
.wh-group-chip input[type="checkbox"] {
  width: 17px;
  height: 17px;
  cursor: pointer;
  accent-color: #2563eb;
  margin: 0;
}
.wh-group-chip.active, .wh-group-chip:has(input:checked) {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 600;
  box-shadow: 0 2px 5px rgba(59, 130, 246, 0.15);
}
.wh-unit-tag {
  display: inline-block;
  padding: 3px 9px;
  font-size: 11px;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  cursor: pointer;
  border: 1px solid #e2e8f0;
  transition: all 0.15s ease;
  user-select: none;
}
.wh-unit-tag:hover {
  background: #3b82f6;
  color: #ffffff;
  border-color: #3b82f6;
}
.wh-kpi-box {
  border-radius: 12px;
  padding: 16px;
  transition: all 0.2s ease;
  height: 100%;
}
.wh-kpi-box-blue {
  background: #f0f9ff;
  border: 1.5px solid #bae6fd;
}
.wh-kpi-box-amber {
  background: #fffbeb;
  border: 1.5px solid #fde68a;
}
.wh-kpi-box-emerald {
  background: #f0fdf4;
  border: 1.5px solid #bbf7d0;
}
.wh-kpi-box:focus-within {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.wh-input-lg-number {
  font-size: 18px;
  font-weight: 700;
  font-family: monospace;
}
</style>

<div class="modal fade" id="modalEditMaterialFull" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg">
      <!-- HEADER -->
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(59, 130, 246, 0.12); display: flex; align-items: center; justify-content: center; color: #2563eb; flex-shrink: 0;">
            <span class="material-icons" style="font-size: 28px;">tune</span>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="badge" id="mEditModeBadge" style="background: #3b82f6; font-size: 11px; font-weight: 600; text-transform: uppercase;">Chỉnh Sửa Vật Tư</span>
              <span class="badge bg-light text-secondary border font-monospace" id="mEditMatCodeChip">--</span>
              <span class="badge bg-light text-muted border font-monospace" id="mEditMatSapChip" style="display:none;"></span>
            </div>
            <h5 class="modal-title fw-bold text-main mb-0" id="mEditModalHeaderLabel">CHỈNH SỬA VẬT TƯ & CẤU HÌNH ĐA NHÓM</h5>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body p-4">
        <form id="formEditMaterialFull" onsubmit="event.preventDefault(); submitSaveMaterial();">
          <input type="hidden" id="editMatId" value="0">
          
          <!-- PHẦN 1: ĐỊNH DANH VẬT TƯ & PHÂN NHÓM CHÍNH -->
          <div class="wh-edit-section">
            <div class="wh-edit-section-title">
              <span class="material-icons text-primary" style="font-size: 18px;">badge</span>
              <span>1. Thông Tin Định Danh & Phân Loại Cơ Bản</span>
            </div>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold text-primary">Mã Vật Tư <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><span class="material-icons" style="font-size: 16px;">tag</span></span>
                  <input type="text" class="form-control form-control-sm font-monospace text-uppercase fw-bold" id="editMatCode" required placeholder="Ví dụ: EA109B">
                </div>
                <div class="form-text text-muted" style="font-size: 11px;">Mã định danh duy nhất trong CSDL.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold">Mã SAP Tra Cứu</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light font-monospace">SAP</span>
                  <input type="text" class="form-control form-control-sm font-monospace" id="editMatSapCode" placeholder="Ví dụ: 00530-PN55">
                </div>
                <div class="form-text text-muted" style="font-size: 11px;">Mã đối soát hệ thống ERP/SAP.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-primary">Nhóm Quản Lý Chính <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-semibold" id="editMatGroup" required onchange="syncApplicableGroupWithMain(this.value)">
                  <option value="Thiết bị">Nhóm Thiết bị</option>
                  <option value="Bảo trì khuôn">Nhóm Bảo trì khuôn</option>
                  <option value="Sản xuất">Nhóm Sản xuất</option>
                  <option value="Nghiền">Nhóm Nghiền</option>
                  <option value="Khác">Nhóm Khác</option>
                </select>
                <div class="form-text text-muted" style="font-size: 11px;">Đơn vị phụ trách kiểm soát chính.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-primary">Phân Loại Định Mức <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-semibold" id="editMatCategoryType" required onchange="onCategoryTypeChanged()">
                  <option value="consumable">Tiêu hao (Định mức A×B×C)</option>
                  <option value="irregular">Bất thường (Không định mức)</option>
                </select>
                <div class="form-text text-muted" style="font-size: 11px;">Quy định cách tính khi lập phiếu.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-primary">Tên Vật Tư (Tiếng Việt) <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-semibold" id="editMatNameVn" required placeholder="Nhập tên gọi tiếng Việt chuẩn...">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Tên Vật Tư (Tiếng Anh / Tên Quốc Tế)</label>
                <input type="text" class="form-control form-control-sm" id="editMatNameEn" placeholder="Tên tiếng Anh (nếu có)...">
              </div>
            </div>
          </div>

          <!-- PHẦN 2: CẤU HÌNH ĐA NHÓM SỬ DỤNG (MULTI-GROUP) -->
          <div class="wh-edit-section">
            <div class="wh-edit-section-title d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="material-icons text-primary" style="font-size: 18px;">hub</span>
                <span>2. Đăng Ký Phân Bổ Sử Dụng Cho Nhiều Nhóm (Đa Nhóm Tiêu Hao)</span>
              </div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 11px;">Tính Năng Đa Nhóm</span>
            </div>
            <p class="small text-muted mb-3" style="font-size: 12px; line-height: 1.5;">
              Tích chọn tất cả các nhóm công việc được phép sử dụng mã vật tư này. Vật tư sẽ xuất hiện trong danh sách đề xuất và cho phép chọn khi tạo phiếu yêu cầu xuất kho của các nhóm tương ứng.
            </p>
            <div class="d-flex flex-wrap gap-2 pt-1" id="containerGroupPills">
              <label class="wh-group-chip" for="chkGrpThietBi">
                <input class="chk-app-group" type="checkbox" value="Thiết bị" id="chkGrpThietBi" onchange="updateGroupChipState(this)">
                <span class="material-icons text-secondary" style="font-size: 18px;">precision_manufacturing</span>
                <span>Nhóm Thiết bị</span>
              </label>

              <label class="wh-group-chip" for="chkGrpKhuon">
                <input class="chk-app-group" type="checkbox" value="Bảo trì khuôn" id="chkGrpKhuon" onchange="updateGroupChipState(this)">
                <span class="material-icons text-secondary" style="font-size: 18px;">handyman</span>
                <span>Nhóm Bảo trì khuôn</span>
              </label>

              <label class="wh-group-chip" for="chkGrpSanXuat">
                <input class="chk-app-group" type="checkbox" value="Sản xuất" id="chkGrpSanXuat" onchange="updateGroupChipState(this)">
                <span class="material-icons text-secondary" style="font-size: 18px;">factory</span>
                <span>Nhóm Sản xuất</span>
              </label>

              <label class="wh-group-chip" for="chkGrpNghien">
                <input class="chk-app-group" type="checkbox" value="Nghiền" id="chkGrpNghien" onchange="updateGroupChipState(this)">
                <span class="material-icons text-secondary" style="font-size: 18px;">recycling</span>
                <span>Nhóm Nghiền</span>
              </label>

              <label class="wh-group-chip" for="chkGrpKhac">
                <input class="chk-app-group" type="checkbox" value="Khác" id="chkGrpKhac" onchange="updateGroupChipState(this)">
                <span class="material-icons text-secondary" style="font-size: 18px;">more_horiz</span>
                <span>Nhóm Khác</span>
              </label>
            </div>
          </div>

          <!-- PHẦN 3: LƯU KHO & QUY CÁCH ĐÓNG GÓI -->
          <div class="wh-edit-section">
            <div class="wh-edit-section-title">
              <span class="material-icons text-primary" style="font-size: 18px;">inventory</span>
              <span>3. Quy Cách Đóng Gói & Vị Trí Lưu Kho</span>
            </div>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold">Đơn Vị Tính (ĐVT) <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-bold font-monospace" id="editMatUnit" required placeholder="Ea, Bot, Kg..." oninput="updateUnitBadges(this.value)">
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Ea')">Ea</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Bot')">Bot</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Kg')">Kg</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Box')">Box</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Set')">Set</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Cuộn')">Cuộn</span>
                  <span class="wh-unit-tag" onclick="selectQuickUnit('Gói')">Gói</span>
                </div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold">Vị Trí Kệ Lưu Kho (BIN)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><span class="material-icons" style="font-size: 16px;">warehouse</span></span>
                  <input type="text" class="form-control form-control-sm font-monospace text-uppercase" id="editMatBin" placeholder="Ví dụ: B021-V61-01...">
                </div>
                <div class="form-text text-muted" style="font-size: 11px;">Mã ô/kệ lưu trữ trong kho.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold">Quy Cách Đóng Gói</label>
                <input type="text" class="form-control form-control-sm" id="editMatPackSpec" placeholder="Ví dụ: Đóng gói 12 Bot/hộp">
                <div class="form-text text-muted" style="font-size: 11px;">Mô tả bao bì đóng gói.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold">SL Quy Đổi / Gói</label>
                <input type="number" step="any" min="1" class="form-control form-control-sm font-monospace" id="editMatPackQty" value="1">
                <div class="form-text text-muted" style="font-size: 11px;">Số lượng đơn vị trong 1 gói lẻ.</div>
              </div>
            </div>
          </div>

          <!-- PHẦN 4: QUẢN LÝ TỒN KHO & ĐIỂM ĐẶT HÀNG LẠI (ROP) -->
          <div class="wh-edit-section">
            <div class="wh-edit-section-title d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="material-icons text-primary" style="font-size: 18px;">monitoring</span>
                <span>4. Quản Lý Tồn Kho & Điểm Đặt Hàng Lại (ROP / MOQ)</span>
              </div>
              <div id="editMatStockHealthBadge">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Tồn kho An toàn</span>
              </div>
            </div>
            
            <div class="row g-3 mb-3">
              <!-- Thẻ Tồn Kho Hiện Tại -->
              <div class="col-md-4">
                <div class="wh-kpi-box wh-kpi-box-blue">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-primary text-uppercase">Tồn Kho Hiện Tại <span class="text-danger">*</span></span>
                    <span class="material-icons text-primary" style="font-size: 20px;">inventory_2</span>
                  </div>
                  <div class="input-group">
                    <input type="number" step="any" min="0" class="form-control form-control-sm wh-input-lg-number text-primary" id="editMatStock" required oninput="triggerStockHealthPreview()">
                    <span class="input-group-text bg-white fw-bold editMatUnitBadge">Ea</span>
                  </div>
                  <div class="small text-muted mt-2" style="font-size: 11px;">Số lượng vật tư thực tế sẵn sàng xuất.</div>
                </div>
              </div>

              <!-- Thẻ Điểm Đặt Hàng Lại (ROP) -->
              <div class="col-md-4">
                <div class="wh-kpi-box wh-kpi-box-amber">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-warning text-uppercase">Điểm Đặt Hàng (ROP)</span>
                    <span class="material-icons text-warning" style="font-size: 20px;">warning_amber</span>
                  </div>
                  <div class="input-group">
                    <input type="number" step="any" min="0" class="form-control form-control-sm wh-input-lg-number text-warning" id="editMatRop" oninput="triggerStockHealthPreview()">
                    <span class="input-group-text bg-white fw-bold editMatUnitBadge">Ea</span>
                  </div>
                  <div class="small text-muted mt-2" style="font-size: 11px;">Ngưỡng tự động gửi cảnh báo đặt hàng lại.</div>
                </div>
              </div>

              <!-- Thẻ Lượng Đặt Tối Thiểu (MOQ) -->
              <div class="col-md-4">
                <div class="wh-kpi-box wh-kpi-box-emerald">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-success text-uppercase">Đề Xuất Mua (MOQ)</span>
                    <span class="material-icons text-success" style="font-size: 20px;">shopping_cart</span>
                  </div>
                  <div class="input-group">
                    <input type="number" step="any" min="0" class="form-control form-control-sm wh-input-lg-number text-success" id="editMatMoq">
                    <span class="input-group-text bg-white fw-bold editMatUnitBadge">Ea</span>
                  </div>
                  <div class="small text-muted mt-2" style="font-size: 11px;">Số lượng tối thiểu mỗi lần phát hành đơn mua.</div>
                </div>
              </div>
            </div>

            <!-- Thanh Xem Trước Sức Khỏe Tồn Kho -->
            <div class="p-2 px-3 rounded border d-flex align-items-center justify-content-between" id="editMatHealthBar" style="background: #f8fafc;">
              <div class="d-flex align-items-center gap-2 small">
                <span class="material-icons" id="editMatHealthIcon" style="font-size: 18px; color: #10b981;">check_circle</span>
                <span id="editMatHealthText" class="fw-semibold text-secondary">Tồn kho hiện tại đang nằm trong ngưỡng an toàn.</span>
              </div>
              <span class="small text-muted" id="editMatHealthSub">Tồn &gt; ROP</span>
            </div>
          </div>

          <!-- PHẦN 5: ĐỊNH MỨC TIÊU HAO CHUẨN (A × B × C) -->
          <div class="wh-edit-section" id="sectionNormCalculation">
            <div class="wh-edit-section-title d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="material-icons text-primary" style="font-size: 18px;">calculate</span>
                <span>5. Cấu Hình Định Mức Tiêu Hao Chuẩn (A × B × C)</span>
              </div>
              <div class="badge bg-light text-primary border border-primary-subtle" style="font-size: 11px;">
                Định Mức Tính: <strong class="font-monospace" id="editMatLiveNormCalc">1.0</strong> <span class="editMatUnitBadge">Ea</span>/chu kỳ
              </div>
            </div>
            
            <p class="small text-muted mb-3" style="font-size: 12px;">
              Công thức tự động tính số lượng xuất lý thuyết theo chu kỳ: <strong class="text-primary font-monospace">Tổng Định Mức = Số máy (A) × Số lần dùng (B) × Định mức/lần (C)</strong>.
            </p>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Số máy áp dụng (A)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light">A</span>
                  <input type="number" step="any" min="0" class="form-control form-control-sm text-center font-monospace fw-bold" id="editMatNormA" value="1" oninput="triggerLiveNormCalc()">
                  <span class="input-group-text bg-light text-muted small">máy</span>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Số lần dùng/máy (B)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light">B</span>
                  <input type="number" step="any" min="0" class="form-control form-control-sm text-center font-monospace fw-bold" id="editMatNormB" value="1" oninput="triggerLiveNormCalc()">
                  <span class="input-group-text bg-light text-muted small">lần/máy</span>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Định mức tiêu hao/lần (C)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light">C</span>
                  <input type="number" step="any" min="0" class="form-control form-control-sm text-center font-monospace fw-bold text-primary" id="editMatNormC" value="1" oninput="triggerLiveNormCalc()">
                  <span class="input-group-text bg-light text-muted small editMatUnitBadge">Ea</span>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- FOOTER -->
      <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="small text-muted d-none d-md-flex align-items-center gap-1">
          <span class="material-icons text-primary" style="font-size: 16px;">sync</span>
          <span>Cơ chế <strong>UPSERT</strong>: Tự động cập nhật nếu trùng mã, thêm mới nếu chưa có.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">
            <span class="material-icons" style="font-size: 16px;">close</span>
            <span>Hủy Bỏ</span>
          </button>
          <button type="button" class="app-btn app-btn-primary" id="btnSubmitSaveMaterial" onclick="submitSaveMaterial()">
            <span class="material-icons" style="font-size: 16px;">save</span>
            <span id="btnSaveMatText">Lưu Thông Tin Vật Tư</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL IMPORT DỮ LIỆU TỒN KHO TỪ EXCEL / CSV
     ========================================================================= -->
<div class="modal fade" id="modalImportStock" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-light border-bottom py-2">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">file_upload</span>
          <span>IMPORT CẬP NHẬT TỒN KHO & DANH MỤC VẬT TƯ (UPSERT)</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Nút tải file mẫu Import -->
        <div class="mb-3 p-3 bg-light rounded border d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-bold small text-dark">Chưa có file dữ liệu mẫu?</div>
            <div class="text-muted small" style="font-size: 11px;">Tải mẫu Excel/CSV chuẩn để nhập tồn kho & thông tin vật tư</div>
          </div>
          <a href="api/warehouse.php?action=download_material_import_template" class="app-btn app-btn-outline btn-sm text-primary border-primary d-flex align-items-center gap-1">
            <span class="material-icons" style="font-size: 16px;">file_download</span>
            <span>Tải File Mẫu (.CSV)</span>
          </a>
        </div>

        <form id="formImportStock" onsubmit="event.preventDefault(); submitImportStock();">
          <div class="mb-3">
            <label class="form-label small fw-bold">Chọn File Excel (.xlsx) hoặc CSV <span class="text-danger">*</span></label>
            <input type="file" class="form-control form-control-sm" id="importStockFile" accept=".xlsx, .csv" required>
            <div class="form-text small mt-1">
              File cần có các cột: <strong>Mã Vật Tư</strong>, <strong>Tồn Hiện Tại</strong>, <strong>Điểm Đặt Hàng (ROP)</strong>, <strong>Kệ BIN</strong>.
            </div>
          </div>
          <div class="alert alert-info py-2 small mb-0 d-flex align-items-start gap-2">
            <span class="material-icons fs-5 text-info">info</span>
            <div>
              Hệ thống sẽ đối chiếu theo <strong>Mã vật tư</strong> để cập nhật số lượng tồn kho mới nhất, đồng thời tự động kích hoạt cảnh báo ROP nếu tồn kho mới &le; điểm đặt hàng.
              Hệ thống tự động đồng bộ theo cơ chế <strong>UPSERT</strong>: Nếu mã vật tư đã có sẽ cập nhật tồn kho & thông số; nếu chưa có sẽ tự động đăng ký mới vào danh mục!
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="app-btn app-btn-outline" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="app-btn app-btn-primary" onclick="submitImportStock()">
          <span class="material-icons">cloud_upload</span>
          <span>Tiến Hành Import</span>
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// Helper hiển thị số chuẩn 1 chữ số thập phân
function fmt1(val) {
  if (val === null || val === undefined || isNaN(val) || val === '') return '0.0';
  return Number(val).toFixed(1);
}

let allMaterialsData = [];
let currentViewingMaterial = null;
let currentViewMode = 'table';
const canManageStockPerm = <?= json_encode($canManageStock) ?>;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadAllMaterials);
} else {
  loadAllMaterials();
}

// 1. Tải toàn bộ vật tư từ API (bao gồm cả vật tư đã vô hiệu hóa)
function loadAllMaterials() {
  const tbody = document.getElementById('tbodyMaterials');
  tbody.innerHTML = `
    <tr>
      <td colspan="11" class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
        <div>Đang nạp danh mục vật tư...</div>
      </td>
    </tr>`;

  fetch('api/warehouse.php?action=get_materials&include_inactive=1')
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger">${res.message || 'Lỗi tải danh mục'}</td></tr>`;
        return;
      }

      allMaterialsData = res.materials || [];
      calculateStockKpis(allMaterialsData);
      filterMaterials();
    })
    .catch(err => {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger">Không thể kết nối máy chủ API.</td></tr>`;
    });
}

// 2. Tính toán các KPI sức khỏe kho (chỉ tính vật tư đang sử dụng)
function calculateStockKpis(materials) {
  let total = 0;
  let safe = 0;
  let ropAlerts = 0;
  let outOfStock = 0;

  materials.forEach(m => {
    if (m.is_active == 0) return; // Bỏ qua vật tư ngừng sử dụng
    total++;
    const stock = parseFloat(m.stock_current) || 0;
    const rop   = parseFloat(m.reorder_point) || 0;
    const runway = (m.runway_months !== null && m.runway_months !== undefined) ? parseFloat(m.runway_months) : 99;

    if (stock <= 0 || runway <= 0.5) {
      outOfStock++;
    } else if (stock <= rop) {
      ropAlerts++;
    } else {
      safe++;
    }
  });

  document.getElementById('statTotalItems').textContent = total;
  document.getElementById('statSafeStock').textContent = safe;
  document.getElementById('statRopAlerts').textContent = ropAlerts;
  document.getElementById('statOutOfStock').textContent = outOfStock;
}

// 3. Lọc danh mục theo tiêu chí
function filterMaterials() {
  const group = document.getElementById('matFilterGroup').value;
  const status = document.getElementById('matFilterStatus').value;
  const activeFilter = document.getElementById('matFilterActive') ? document.getElementById('matFilterActive').value : '1';
  const search = document.getElementById('matFilterSearch').value.toLowerCase().trim();

  const filtered = allMaterialsData.filter(m => {
    // Lọc trạng thái sử dụng
    if (activeFilter !== 'ALL') {
      if (String(m.is_active) !== activeFilter) return false;
    }

    // Lọc nhóm (Bao gồm Nhóm Quản Lý Chính hoặc thuộc Đa Nhóm Tiêu Hao được đăng ký)
    if (group !== 'ALL') {
      const appGroups = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim().toLowerCase());
      const matchPrimary = (m.group_name || '').toLowerCase() === group.toLowerCase();
      const matchApp = appGroups.includes(group.toLowerCase());
      if (!matchPrimary && !matchApp) return false;
    }

    // Lọc tình trạng tồn kho
    const stock = parseFloat(m.stock_current) || 0;
    const rop   = parseFloat(m.reorder_point) || 0;
    const runway = (m.runway_months !== null && m.runway_months !== undefined) ? parseFloat(m.runway_months) : 99;

    if (status === 'out_of_stock' && !(stock <= 0 || runway <= 0.5)) return false;
    if (status === 'rop_warning' && !(stock > 0 && stock <= rop)) return false;
    if (status === 'safe' && !(stock > rop && runway > 0.5)) return false;

    // Tìm kiếm văn bản
    if (search) {
      const matchCode = (m.material_code || m.item_code || m.ma_vt || '').toLowerCase().includes(search);
      const matchSap  = (m.sap_code || '').toLowerCase().includes(search);
      const matchName = (m.material_name || m.item_name_vn || m.ten_vt || '').toLowerCase().includes(search);
      const matchBin  = (m.bin_location || '').toLowerCase().includes(search);
      if (!matchCode && !matchSap && !matchName && !matchBin) return false;
    }

    return true;
  });

  document.getElementById('materialsCountInfo').textContent = `Hiển thị ${filtered.length} trên ${allMaterialsData.length} vật tư`;

  if (currentViewMode === 'table') {
    renderMaterialsTable(filtered);
  } else {
    renderMaterialsGrid(filtered);
  }
}

// 4. Render Bảng chi tiết
function renderMaterialsTable(materials) {
  const tbody = document.getElementById('tbodyMaterials');
  if (materials.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" class="text-center py-5 text-muted">
          <span class="material-icons mb-2" style="font-size: 36px; color: #cbd5e1;">search_off</span>
          <p class="mb-0">Không tìm thấy vật tư nào phù hợp với bộ lọc.</p>
        </td>
      </tr>`;
    return;
  }

  let html = '';
  materials.forEach((m, idx) => {
    const isInactive = (m.is_active == 0);
    const rowClass = isInactive ? 'class="table-light opacity-75"' : '';

    const imgHtml = m.image_url 
      ? `<img src="${escapeHtml(m.image_url)}" class="rounded border shadow-sm cursor-pointer" style="width: 44px; height: 44px; object-fit: contain;" onclick="openMaterialDetail(${m.id})">`
      : `<div class="bg-light text-muted d-flex align-items-center justify-content-center border rounded" style="width: 44px; height: 44px;"><span class="material-icons" style="font-size: 20px;">photo</span></div>`;

    const isConsumable = (m.category_type === 'consumable');
    const normText = isConsumable 
      ? `<div class="small font-monospace">A:${fmt1(m.norm_machines_count || m.default_machines)} × B:${fmt1(m.norm_uses_per_machine || m.default_uses_per_machine)}</div><div class="text-muted small">C:${fmt1(m.norm_per_use)} ${m.unit}/lần</div>`
      : `<span class="badge bg-light text-muted border">Bất thường</span>`;

    // Sức khỏe tồn kho
    const stock = parseFloat(m.stock_current) || 0;
    const rop   = parseFloat(m.reorder_point) || 0;
    const runway = (m.runway_months !== null && m.runway_months !== undefined) ? parseFloat(m.runway_months) : null;

    let healthBadge = '';
    if (isInactive) {
      healthBadge = '<span class="badge bg-secondary">Ngừng sử dụng</span>';
    } else if (stock <= 0) {
      healthBadge = '<span class="badge bg-danger">Hết hàng (0.0)</span>';
    } else if (runway !== null && runway <= 0.5) {
      healthBadge = `<span class="badge bg-danger">Khẩn cấp (${fmt1(runway)} thg)</span>`;
    } else if (stock <= rop) {
      healthBadge = `<span class="badge bg-warning text-dark">&le; ROP (${fmt1(stock)}/${fmt1(rop)})</span>`;
    } else {
      healthBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">An toàn</span>';
    }

    const editBtn = canManageStockPerm 
      ? `<button class="btn btn-sm btn-outline-primary p-1" title="Chỉnh sửa vật tư & cấu hình đa nhóm" onclick="openEditMaterialModal(${m.id})"><span class="material-icons" style="font-size: 16px;">edit</span></button>` 
      : '';

    const mCode = m.material_code || m.item_code || m.ma_vt || '--';
    const mName = m.material_name || m.item_name_vn || m.ten_vt || ('Vật tư #' + m.id);

    const toggleActiveBtn = canManageStockPerm
      ? (isInactive 
          ? `<button class="btn btn-sm btn-outline-success p-1" title="Kích hoạt lại vật tư" onclick="toggleMaterialActive(${m.id}, 1, '${escapeHtml(mName)}')"><span class="material-icons" style="font-size: 16px;">check_circle</span></button>`
          : `<button class="btn btn-sm btn-outline-danger p-1" title="Vô hiệu hóa (Ngừng sử dụng)" onclick="toggleMaterialActive(${m.id}, 0, '${escapeHtml(mName)}')"><span class="material-icons" style="font-size: 16px;">block</span></button>`)
      : '';

    // Badges cho các nhóm áp dụng (Đa nhóm)
    const appGroupsList = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim()).filter(Boolean);
    const groupBadges = appGroupsList.map(g => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1 me-1" style="font-size:10px;">${escapeHtml(g)}</span>`).join('');

    html += `
      <tr ${rowClass}>
        <td class="ps-3 text-muted fw-bold">${idx + 1}</td>
        <td>${imgHtml}</td>
        <td>
          <a href="javascript:void(0)" class="fw-bold font-monospace text-primary text-decoration-none" onclick="openMaterialDetail(${m.id})">
            ${escapeHtml(mCode)}
          </a>
          ${m.sap_code ? `<div class="small text-muted font-monospace">SAP: ${escapeHtml(m.sap_code)}</div>` : ''}
        </td>
        <td>
          <div class="fw-semibold text-main cursor-pointer" onclick="openMaterialDetail(${m.id})">
            ${escapeHtml(mName)}
            ${isInactive ? '<span class="badge bg-secondary ms-1 small">Ngừng SD</span>' : ''}
          </div>
          <div class="small text-muted">Quy cách: ${escapeHtml(m.pack_spec || 'Gói lẻ')} (${fmt1(m.pack_quantity || 1)} ${m.unit}/gói)</div>
        </td>
        <td>
          <div class="fw-bold text-dark small mb-1">${escapeHtml(m.group_name)}</div>
          <div class="d-flex flex-wrap gap-1 mb-1">${groupBadges}</div>
          <div class="small text-muted" style="font-size:11px;">${isConsumable ? 'Tiêu hao (Định mức)' : 'Bất thường'}</div>
        </td>
        <td>${normText}</td>
        <td class="text-center font-monospace small">
          ${m.bin_location ? `<span class="badge bg-light text-secondary border font-monospace">${escapeHtml(m.bin_location)}</span>` : '<span class="text-muted">--</span>'}
        </td>
        <td class="text-end">
          <strong class="font-monospace text-primary" style="font-size: 14.5px;">${fmt1(stock)}</strong>
          <span class="small text-muted">${m.unit}</span>
        </td>
        <td class="text-center font-monospace small">
          <div>ROP: <strong>${fmt1(rop)}</strong></div>
          <div class="text-muted">MOQ: ${fmt1(m.reorder_qty || 0)}</div>
        </td>
        <td class="text-center">${healthBadge}</td>
        <td class="text-end pe-3">
          <div class="d-flex justify-content-end gap-1">
            <button class="btn btn-sm btn-outline-primary p-1" title="Xem chi tiết" onclick="openMaterialDetail(${m.id})">
              <span class="material-icons" style="font-size: 16px;">visibility</span>
            </button>
            ${editBtn}
            ${toggleActiveBtn}
          </div>
        </td>
      </tr>`;
  });

  tbody.innerHTML = html;
}

// 5. Render Chế độ Lưới (Grid View)
function renderMaterialsGrid(materials) {
  const container = document.getElementById('containerGridView');
  if (materials.length === 0) {
    container.innerHTML = `
      <div class="col-12 text-center py-5 text-muted">
        <span class="material-icons mb-2" style="font-size: 36px; color: #cbd5e1;">search_off</span>
        <p class="mb-0">Không tìm thấy vật tư nào phù hợp với bộ lọc.</p>
      </div>`;
    return;
  }

  let html = '';
  materials.forEach(m => {
    const isInactive = (m.is_active == 0);
    const stock = parseFloat(m.stock_current) || 0;
    const rop   = parseFloat(m.reorder_point) || 0;
    const runway = (m.runway_months !== null && m.runway_months !== undefined) ? parseFloat(m.runway_months) : null;

    let healthBadge = '';
    if (isInactive) healthBadge = '<span class="badge bg-secondary">Ngừng sử dụng</span>';
    else if (stock <= 0) healthBadge = '<span class="badge bg-danger">Hết hàng</span>';
    else if (runway !== null && runway <= 0.5) healthBadge = `<span class="badge bg-danger">&le; 0.5 tháng</span>`;
    else if (stock <= rop) healthBadge = `<span class="badge bg-warning text-dark">&le; ROP</span>`;
    else healthBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">An toàn</span>';

    const imgUrl = m.image_url || 'resources/images/warehouse/placeholder.png';

    const mCode = m.material_code || m.item_code || m.ma_vt || '--';
    const mName = m.material_name || m.item_name_vn || m.ten_vt || ('Vật tư #' + m.id);

    html += `
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100 border shadow-sm material-grid-card ${isInactive ? 'opacity-75 bg-light' : ''}">
          <div class="p-3 text-center bg-light border-bottom position-relative">
            <span class="position-absolute top-0 start-0 m-2">${healthBadge}</span>
            <span class="position-absolute top-0 end-0 m-2 badge bg-light text-secondary border font-monospace">${escapeHtml(m.bin_location || 'N/A')}</span>
            <img src="${escapeHtml(imgUrl)}" class="img-fluid rounded" style="height: 120px; object-fit: contain;" alt="Ảnh sản phẩm">
          </div>
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge bg-light text-dark border small">${escapeHtml(m.group_name)}</span>
              <strong class="font-monospace text-primary small">${escapeHtml(mCode)}</strong>
            </div>
            <h6 class="fw-bold text-main mb-2 text-truncate" title="${escapeHtml(mName)}">
              ${escapeHtml(mName)}
              ${isInactive ? '<span class="badge bg-secondary ms-1 small">Ngừng SD</span>' : ''}
            </h6>
            <div class="small text-muted mb-2">Quy cách: ${escapeHtml(m.pack_spec || 'Gói lẻ')}</div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <div>
                <span class="text-muted small">Tồn kho:</span>
                <strong class="text-primary font-monospace">${fmt1(stock)} ${m.unit}</strong>
              </div>
              <div>
                <span class="text-muted small">ROP:</span>
                <span class="font-monospace">${fmt1(rop)}</span>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-top p-2 d-flex justify-content-between align-items-center gap-1">
            <button class="app-btn app-btn-outline btn-sm w-100" onclick="openMaterialDetail(${m.id})">
              <span class="material-icons" style="font-size: 15px;">visibility</span>
              <span>Chi tiết</span>
            </button>
            ${canManageStockPerm ? (isInactive 
                ? `<button class="btn btn-sm btn-outline-success" title="Kích hoạt lại" onclick="toggleMaterialActive(${m.id}, 1, '${escapeHtml(mName)}')"><span class="material-icons" style="font-size: 15px;">check_circle</span></button>`
                : `<button class="btn btn-sm btn-outline-danger" title="Vô hiệu hóa" onclick="toggleMaterialActive(${m.id}, 0, '${escapeHtml(mName)}')"><span class="material-icons" style="font-size: 15px;">block</span></button>`) : ''}
          </div>
        </div>
      </div>`;
  });

  container.innerHTML = html;
}

// 6. Chuyển đổi View Mode
function switchMaterialView(mode) {
  currentViewMode = mode;
  const tableCont = document.getElementById('containerTableView');
  const gridCont  = document.getElementById('containerGridView');
  const btnTable  = document.getElementById('btnViewTable');
  const btnGrid   = document.getElementById('btnViewGrid');

  if (mode === 'table') {
    tableCont.classList.remove('d-none');
    gridCont.classList.add('d-none');
    btnTable.classList.add('active');
    btnGrid.classList.remove('active');
  } else {
    tableCont.classList.add('d-none');
    gridCont.classList.remove('d-none');
    btnTable.classList.remove('active');
    btnGrid.classList.add('active');
  }
  filterMaterials();
}

// 7. Mở modal chi tiết vật tư
function openMaterialDetail(matId) {
  fetch(`api/warehouse.php?action=get_material_detail&material_id=${matId}`)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert(res.message || 'Không tìm thấy vật tư');
        return;
      }

      const m = res.material;
      currentViewingMaterial = m;

      const mCode = m.material_code || m.item_code || m.ma_vt || '--';
      const mName = m.material_name || m.item_name_vn || m.ten_vt || '';

      document.getElementById('mMatDetailCode').textContent = mCode;
      document.getElementById('mMatDetailName').textContent = mName;
      document.getElementById('mMatDetailGroup').textContent = `Nhóm ${m.group_name} (${m.category_type === 'consumable' ? 'Tiêu hao định mức' : 'Bất thường'})`;
      document.getElementById('mMatDetailSap').textContent = m.sap_code || 'Không có';
      document.getElementById('mMatDetailBin').textContent = m.bin_location || 'Chưa xếp BIN';
      document.getElementById('mMatDetailSpec').textContent = `${m.pack_spec || 'Gói lẻ'} (${m.pack_quantity || 1} ${m.unit})`;
      document.getElementById('mMatDetailUnit').textContent = m.unit;

      document.getElementById('mMatDetailNormA').textContent = fmt1(m.norm_machines_count || 0);
      document.getElementById('mMatDetailNormB').textContent = fmt1(m.norm_uses_per_machine || 0);
      document.getElementById('mMatDetailNormC').textContent = `${fmt1(m.norm_per_use || 0)} ${m.unit}`;

      document.getElementById('mMatDetailStock').textContent = `${fmt1(m.stock_current)} ${m.unit}`;
      document.getElementById('mMatDetailRop').textContent = `${fmt1(m.reorder_point)} ${m.unit}`;
      document.getElementById('mMatDetailMoq').textContent = `${fmt1(m.reorder_qty || 0)} ${m.unit}`;

      const imgEl = document.getElementById('mMatDetailImage');
      if (m.image_url) {
        imgEl.src = m.image_url;
        imgEl.style.display = 'inline-block';
      } else {
        imgEl.src = '';
        imgEl.style.display = 'none';
      }

      // Lịch sử 3 tháng
      const hist = m.history_3m || {};
      document.getElementById('mMatDetailT5').textContent = `${fmt1(hist.month_5 || 0)} ${m.unit}`;
      document.getElementById('mMatDetailT6').textContent = `${fmt1(hist.month_6 || 0)} ${m.unit}`;
      document.getElementById('mMatDetailT7').textContent = `${fmt1(hist.month_7 || 0)} ${m.unit}`;
      document.getElementById('mMatDetailAvg3M').textContent = `${fmt1(hist.avg_3m || 0)} ${m.unit}/tháng`;

      const runwayVal = m.runway_months;
      let rwHtml = '';
      if (runwayVal !== null && runwayVal !== undefined) {
        const val = parseFloat(runwayVal);
        if (val <= 0.5) rwHtml = `<span class="badge bg-danger">${fmt1(val)} tháng (Báo động)</span>`;
        else if (val <= 1.0) rwHtml = `<span class="badge bg-warning text-dark">${fmt1(val)} tháng</span>`;
        else rwHtml = `<span class="badge bg-success">${fmt1(val)} tháng</span>`;
      } else {
        rwHtml = '<span class="text-muted">N/A</span>';
      }
      document.getElementById('mMatDetailRunway').innerHTML = rwHtml;

      const modalEl = document.getElementById('modalMaterialDetail');
      if (modalEl.parentElement !== document.body) document.body.appendChild(modalEl);
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối khi tải chi tiết vật tư.');
    });
}

// 8. Mở modal đăng ký vật tư mới vào hệ thống (UPSERT)
function openCreateMaterialModal() {
  document.getElementById('editMatId').value = 0;
  
  const modeBadge = document.getElementById('mEditModeBadge');
  if (modeBadge) {
    modeBadge.textContent = 'Đăng Ký Mới';
    modeBadge.style.background = '#10b981';
  }
  const codeChip = document.getElementById('mEditMatCodeChip');
  if (codeChip) codeChip.textContent = 'MÃ MỚI';
  const sapChip = document.getElementById('mEditMatSapChip');
  if (sapChip) sapChip.style.display = 'none';

  document.getElementById('mEditModalHeaderLabel').textContent = 'ĐĂNG KÝ VẬT TƯ MỚI VÀO KHO (UPSERT)';
  document.getElementById('editMatCode').value = '';
  document.getElementById('editMatCode').readOnly = false;
  document.getElementById('editMatNameVn').value = '';
  document.getElementById('editMatNameEn').value = '';
  document.getElementById('editMatSapCode').value = '';
  document.getElementById('editMatGroup').value = 'Thiết bị';
  document.getElementById('editMatCategoryType').value = 'consumable';

  // Checkboxes
  document.querySelectorAll('.chk-app-group').forEach(chk => {
    chk.checked = (chk.value === 'Thiết bị');
    updateGroupChipState(chk);
  });

  document.getElementById('editMatUnit').value = 'Ea';
  updateUnitBadges('Ea');
  document.getElementById('editMatBin').value = 'KHO';
  document.getElementById('editMatPackSpec').value = '';
  document.getElementById('editMatPackQty').value = 1;
  document.getElementById('editMatNormA').value = 1;
  document.getElementById('editMatNormB').value = 1;
  document.getElementById('editMatNormC').value = 1;
  document.getElementById('editMatStock').value = 0;
  document.getElementById('editMatRop').value = 0;
  document.getElementById('editMatMoq').value = 0;

  triggerStockHealthPreview();
  triggerLiveNormCalc();
  onCategoryTypeChanged();

  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditMaterialFull'));
  modal.show();
}

// 8b. Mở modal chỉnh sửa toàn diện vật tư & cấu hình đa nhóm
function openEditMaterialModal(matId) {
  const m = allMaterialsData.find(item => item.id == matId);
  if (!m) return;

  const mCode = m.material_code || m.item_code || m.ma_vt || '';
  const mName = m.material_name || m.item_name_vn || m.ten_vt || '';

  document.getElementById('editMatId').value = m.id;
  
  const modeBadge = document.getElementById('mEditModeBadge');
  if (modeBadge) {
    modeBadge.textContent = 'Chỉnh Sửa Vật Tư';
    modeBadge.style.background = '#3b82f6';
  }
  const codeChip = document.getElementById('mEditMatCodeChip');
  if (codeChip) codeChip.textContent = mCode;
  
  const sapChip = document.getElementById('mEditMatSapChip');
  if (sapChip) {
    if (m.sap_code) {
      sapChip.textContent = 'SAP: ' + m.sap_code;
      sapChip.style.display = 'inline-block';
    } else {
      sapChip.style.display = 'none';
    }
  }

  document.getElementById('mEditModalHeaderLabel').textContent = 'CHỈNH SỬA VẬT TƯ: ' + mName;
  document.getElementById('editMatCode').value = mCode;
  document.getElementById('editMatCode').readOnly = false;
  document.getElementById('editMatNameVn').value = mName;
  document.getElementById('editMatNameEn').value = m.item_name_en || '';
  document.getElementById('editMatSapCode').value = m.sap_code || '';
  document.getElementById('editMatGroup').value = m.group_name || 'Thiết bị';
  document.getElementById('editMatCategoryType').value = m.category_type || 'consumable';

  // Tick chọn các checkbox đa nhóm
  const appGroups = (m.applicable_groups || m.group_name || '').split(',').map(s => s.trim());
  const checkboxes = document.querySelectorAll('.chk-app-group');
  checkboxes.forEach(chk => {
    chk.checked = appGroups.includes(chk.value);
    updateGroupChipState(chk);
  });
  // Đảm bảo nhóm chính luôn được chọn
  syncApplicableGroupWithMain(m.group_name);

  const unitVal = m.unit || 'Ea';
  document.getElementById('editMatUnit').value = unitVal;
  updateUnitBadges(unitVal);

  document.getElementById('editMatBin').value = m.bin_location || '';
  document.getElementById('editMatPackSpec').value = m.pack_spec || m.packaging_spec || '';
  document.getElementById('editMatPackQty').value = m.pack_quantity || 1;
  document.getElementById('editMatNormA').value = m.norm_machines_count || m.default_machines || 1;
  document.getElementById('editMatNormB').value = m.norm_uses_per_machine || m.default_uses_per_machine || 1;
  document.getElementById('editMatNormC').value = m.norm_per_use || 1;
  document.getElementById('editMatStock').value = m.stock_current || 0;
  document.getElementById('editMatRop').value = m.reorder_point || 0;
  document.getElementById('editMatMoq').value = m.reorder_qty || 0;

  triggerStockHealthPreview();
  triggerLiveNormCalc();
  onCategoryTypeChanged();

  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditMaterialFull'));
  modal.show();
}

function syncApplicableGroupWithMain(mainGroup) {
  if (!mainGroup) return;
  const checkboxes = document.querySelectorAll('.chk-app-group');
  checkboxes.forEach(chk => {
    if (chk.value === mainGroup) {
      chk.checked = true;
      updateGroupChipState(chk);
    }
  });
}

// Helpers cho UI/UX Modal Chỉnh Sửa Vật Tư
function selectQuickUnit(unit) {
  document.getElementById('editMatUnit').value = unit;
  updateUnitBadges(unit);
}

function updateUnitBadges(unit) {
  const u = (unit && unit.trim()) ? unit.trim() : 'ĐVT';
  document.querySelectorAll('.editMatUnitBadge').forEach(el => {
    el.textContent = u;
  });
  triggerLiveNormCalc();
}

function updateGroupChipState(input) {
  const parent = input.closest('.wh-group-chip');
  if (parent) {
    if (input.checked) parent.classList.add('active');
    else parent.classList.remove('active');
  }
}

function onCategoryTypeChanged() {
  const cat = document.getElementById('editMatCategoryType').value;
  const sec = document.getElementById('sectionNormCalculation');
  if (sec) {
    if (cat === 'irregular') {
      sec.style.opacity = '0.55';
    } else {
      sec.style.opacity = '1';
    }
  }
}

function triggerStockHealthPreview() {
  const stock = parseFloat(document.getElementById('editMatStock').value) || 0;
  const rop = parseFloat(document.getElementById('editMatRop').value) || 0;
  const badgeContainer = document.getElementById('editMatStockHealthBadge');
  const icon = document.getElementById('editMatHealthIcon');
  const text = document.getElementById('editMatHealthText');
  const sub = document.getElementById('editMatHealthSub');
  const bar = document.getElementById('editMatHealthBar');

  if (stock <= 0) {
    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Hết hàng (0.0)</span>';
    if (icon) { icon.textContent = 'error'; icon.style.color = '#ef4444'; }
    if (text) { text.textContent = 'Báo động: Tồn kho đã hết hoặc bằng 0, cần đặt hàng bổ sung khẩn cấp!'; text.className = 'fw-semibold text-danger'; }
    if (sub) sub.textContent = 'Tồn = 0.0';
    if (bar) bar.style.borderColor = '#fecaca';
  } else if (stock <= rop) {
    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Cảnh báo ROP</span>';
    if (icon) { icon.textContent = 'warning_amber'; icon.style.color = '#f59e0b'; }
    if (text) { text.textContent = `Cảnh báo: Tồn kho (${fmt1(stock)}) đã chạm hoặc thấp hơn điểm đặt hàng (${fmt1(rop)}).`; text.className = 'fw-semibold text-warning'; }
    if (sub) sub.textContent = 'Tồn ≤ ROP';
    if (bar) bar.style.borderColor = '#fed7aa';
  } else {
    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Tồn kho An toàn</span>';
    if (icon) { icon.textContent = 'check_circle'; icon.style.color = '#10b981'; }
    if (text) { text.textContent = `Tồn kho an toàn (${fmt1(stock)}), cao hơn ngưỡng đặt hàng lại (${fmt1(rop)}).`; text.className = 'fw-semibold text-success'; }
    if (sub) sub.textContent = 'Tồn > ROP';
    if (bar) bar.style.borderColor = '#bbf7d0';
  }
}

function triggerLiveNormCalc() {
  const a = parseFloat(document.getElementById('editMatNormA').value) || 0;
  const b = parseFloat(document.getElementById('editMatNormB').value) || 0;
  const c = parseFloat(document.getElementById('editMatNormC').value) || 0;
  const total = a * b * c;
  const el = document.getElementById('editMatLiveNormCalc');
  if (el) el.textContent = fmt1(total);
}

function openEditStockModal(matId) {
  openEditMaterialModal(matId);
}

function openEditStockModalFromDetail() {
  if (!currentViewingMaterial) return;
  const detailModal = bootstrap.Modal.getInstance(document.getElementById('modalMaterialDetail'));
  if (detailModal) detailModal.hide();
  openEditMaterialModal(currentViewingMaterial.id);
}

// 9. Lưu cài đặt vật tư & cấu hình đa nhóm (Hỗ trợ UPSERT tạo mới & cập nhật)
function submitSaveMaterial() {
  const matId = document.getElementById('editMatId').value;
  const itemCode = document.getElementById('editMatCode').value.trim();
  const itemNameVn = document.getElementById('editMatNameVn').value.trim();
  const itemNameEn = document.getElementById('editMatNameEn').value.trim();
  const mainGroup = document.getElementById('editMatGroup').value;
  const catType = document.getElementById('editMatCategoryType').value;
  const unit = document.getElementById('editMatUnit').value.trim();
  const stock = document.getElementById('editMatStock').value;
  const rop = document.getElementById('editMatRop').value;
  const moq = document.getElementById('editMatMoq').value;

  if (!itemCode || !itemNameVn || !unit || stock === '') {
    alert('Vui lòng điền đầy đủ Mã vật tư, Tên vật tư (VN), Đơn vị tính và Tồn kho hiện tại.');
    return;
  }

  // Thu thập các nhóm được tick
  const checkedGroups = [];
  document.querySelectorAll('.chk-app-group:checked').forEach(c => checkedGroups.push(c.value));
  if (!checkedGroups.includes(mainGroup)) checkedGroups.unshift(mainGroup);

  const saveBtn = document.getElementById('btnSubmitSaveMaterial');
  const saveText = document.getElementById('btnSaveMatText');
  if (saveBtn) saveBtn.disabled = true;
  if (saveText) saveText.textContent = 'Đang lưu dữ liệu...';

  const formData = new FormData();
  formData.append('material_id', matId);
  formData.append('id', matId);
  formData.append('item_code', itemCode);
  formData.append('item_name_vn', itemNameVn);
  formData.append('item_name_en', itemNameEn);
  formData.append('group_name', mainGroup);
  formData.append('applicable_groups', checkedGroups.join(','));
  formData.append('category_type', catType);
  formData.append('unit', unit);
  formData.append('bin_location', document.getElementById('editMatBin').value.trim());
  formData.append('packaging_spec', document.getElementById('editMatPackSpec').value.trim());
  formData.append('pack_quantity', document.getElementById('editMatPackQty').value);
  formData.append('default_machines', document.getElementById('editMatNormA').value);
  formData.append('default_uses_per_machine', document.getElementById('editMatNormB').value);
  formData.append('norm_per_use', document.getElementById('editMatNormC').value);
  formData.append('stock_current', stock);
  formData.append('reorder_point', rop);
  formData.append('reorder_qty', moq);

  fetch('api/warehouse.php?action=save_material_settings', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (saveBtn) saveBtn.disabled = false;
      if (saveText) saveText.textContent = 'Lưu Thông Tin Vật Tư';

      if (res.success) {
        alert(res.message || 'Cập nhật cài đặt vật tư và cấu hình đa nhóm thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditMaterialFull'));
        if (modal) modal.hide();
        loadAllMaterials();
      } else {
        alert(res.message || 'Không thể lưu cài đặt vật tư.');
      }
    })
    .catch(err => {
      if (saveBtn) saveBtn.disabled = false;
      if (saveText) saveText.textContent = 'Lưu Thông Tin Vật Tư';
      console.error(err);
      alert('Lỗi kết nối máy chủ khi lưu cài đặt vật tư.');
    });
}

function submitUpdateStock() {
  submitSaveMaterial();
}

// 10. Import Excel / CSV tồn kho
function openImportStockModal() {
  document.getElementById('importStockFile').value = '';
  const modal = new bootstrap.Modal(document.getElementById('modalImportStock'));
  modal.show();
}

function submitImportStock() {
  const fileInput = document.getElementById('importStockFile');
  if (!fileInput.files || fileInput.files.length === 0) {
    alert('Vui lòng chọn file Excel (.xlsx) hoặc CSV.');
    return;
  }

  const formData = new FormData();
  formData.append('stock_file', fileInput.files[0]);

  fetch('api/warehouse.php?action=import_materials_stock', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Import thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportStock'));
        if (modal) modal.hide();
        loadAllMaterials();
      } else {
        alert(res.message || 'Lỗi khi import tồn kho.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi import file.');
    });
}

// 11. Kích hoạt quét tự động đồng bộ ROP
function triggerSyncRopAlerts() {
  fetch('api/warehouse.php?action=sync_rop_alerts')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Đã quét và đồng bộ điểm đặt hàng ROP thành công!');
        loadAllMaterials();
      } else {
        alert(res.message || 'Không thể quét đồng bộ ROP.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi quét ROP.');
    });
}

// 10. Chuyển đổi trạng thái hoạt động / vô hiệu hóa vật tư (is_active)
function toggleMaterialActive(matId, newStatus, matName) {
  const actionText = (newStatus == 1) ? 'KÍCH HOẠT LẠI' : 'VÔ HIỆU HÓA (Ngừng sử dụng)';
  const msg = `Bạn có chắc chắn muốn ${actionText} vật tư:\n"${matName}"?\n\n${newStatus == 0 ? 'Lưu ý: Vật tư bị vô hiệu hóa sẽ không còn xuất hiện khi lập phiếu xuất mới.' : 'Vật tư sẽ được kích hoạt và xuất hiện lại trên toàn hệ thống.'}`;
  if (!confirm(msg)) return;

  const formData = new FormData();
  formData.append('material_id', matId);
  formData.append('is_active', newStatus);

  fetch('api/warehouse.php?action=toggle_material_active', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Cập nhật trạng thái thành công!');
        loadAllMaterials();
      } else {
        alert(res.message || 'Không thể cập nhật trạng thái.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ khi đổi trạng thái vật tư.');
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
</script>
