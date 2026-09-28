<?php
/**
 * Module Quản Lý Kho (Xuất Vật Tư) - Theo Dõi & Xác Nhận Tình Trạng Đơn Hàng (Dưới Định Mức ROP)
 * DX Plastic Group - Factory Management System
 */

$userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');
$canManageOrders = hasPermission(['warehouse.manage', 'admin']);
?>

<div class="app-page-wrapper warehouse-container">
  <!-- Header Trang -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons text-primary" style="font-size: 28px;">track_changes</span>
        <span>THEO DÕI & XÁC NHẬN TÌNH TRẠNG ĐƠN HÀNG (ROP)</span>
      </h1>
      <p class="app-page-subtitle">Quản lý các mặt hàng dưới điểm chuẩn tồn kho an toàn, xác nhận tiến độ: <em>Đã đặt hàng, Chờ đặt hàng, Chờ giao hàng, Đã giao hàng</em> và phản hồi kỳ hạn giao hàng (ETA) đến xưởng sản xuất</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php?mainpage=warehouse&subpage=materials" class="app-btn app-btn-outline">
        <span class="material-icons">category</span>
        <span>Danh Mục Vật Tư</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=approval" class="app-btn app-btn-secondary">
        <span class="material-icons">verified_user</span>
        <span>Quy Trình Phê Duyệt</span>
      </a>
      <a href="index.php?mainpage=warehouse&subpage=dashboard" class="app-btn app-btn-primary">
        <span class="material-icons">dashboard</span>
        <span>Dashboard Kho</span>
      </a>
    </div>
  </div>

  <!-- Sơ đồ 4 Trạng Thái Đơn Hàng Dưới Điểm Đặt Hàng (ROP KPI Cards) -->
  <div class="row g-3 mb-4">
    <!-- 1. Chờ đặt hàng -->
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100 cursor-pointer" style="border-top: 4px solid #f59e0b !important;" onclick="quickFilterStatus('cho_dat_hang')">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">BƯỚC 1</span>
          <span class="material-icons text-warning" style="font-size: 20px;">pending_actions</span>
        </div>
        <div class="text-muted small fw-semibold text-uppercase">Chờ Đặt Hàng</div>
        <div class="h3 mb-0 fw-bold text-warning my-1" id="kpiChoDatHang">0</div>
        <div class="small text-muted" style="font-size: 11px;">Cần liên hệ NCC / Lên đơn mua</div>
      </div>
    </div>

    <!-- 2. Đã đặt hàng -->
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100 cursor-pointer" style="border-top: 4px solid #3b82f6 !important;" onclick="quickFilterStatus('da_dat_hang')">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="badge bg-primary text-white font-monospace" style="font-size: 10px;">BƯỚC 2</span>
          <span class="material-icons text-primary" style="font-size: 20px;">shopping_cart_checkout</span>
        </div>
        <div class="text-muted small fw-semibold text-uppercase">Đã Đặt Hàng</div>
        <div class="h3 mb-0 fw-bold text-primary my-1" id="kpiDaDatHang">0</div>
        <div class="small text-muted" style="font-size: 11px;">Đã phát hành đơn mua hàng (PO)</div>
      </div>
    </div>

    <!-- 3. Chờ giao hàng -->
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100 cursor-pointer" style="border-top: 4px solid #8b5cf6 !important;" onclick="quickFilterStatus('cho_giao_hang')">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="badge text-white font-monospace" style="background: #8b5cf6; font-size: 10px;">BƯỚC 3</span>
          <span class="material-icons" style="color: #8b5cf6; font-size: 20px;">local_shipping</span>
        </div>
        <div class="text-muted small fw-semibold text-uppercase">Chờ Giao Hàng</div>
        <div class="h3 mb-0 fw-bold text-purple my-1" style="color: #8b5cf6;" id="kpiChoGiaoHang">0</div>
        <div class="small text-muted" style="font-size: 11px;">NCC đang vận chuyển / Đã có ETA</div>
      </div>
    </div>

    <!-- 4. Đã giao hàng -->
    <div class="col-6 col-md-3">
      <div class="app-card p-3 border text-center h-100 cursor-pointer" style="border-top: 4px solid #10b981 !important;" onclick="quickFilterStatus('da_giao_hang')">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="badge bg-success text-white font-monospace" style="font-size: 10px;">BƯỚC 4</span>
          <span class="material-icons text-success" style="font-size: 20px;">check_circle</span>
        </div>
        <div class="text-muted small fw-semibold text-uppercase">Đã Giao Hàng</div>
        <div class="h3 mb-0 fw-bold text-success my-1" id="kpiDaGiaoHang">0</div>
        <div class="small text-muted" style="font-size: 11px;">Đã nhập kho / Hoàn tất cấp hàng</div>
      </div>
    </div>
  </div>

  <!-- Thanh Bộ Lọc & Tìm Kiếm -->
  <div class="app-card p-3 mb-4 border">
    <div class="row g-2 align-items-center">
      <div class="col-md-3">
        <label class="form-label small fw-bold mb-1">Tình Trạng Đơn Hàng</label>
        <select class="form-select form-select-sm" id="filterOrderStatus" onchange="loadReorderTracking()">
          <option value="ALL">-- Tất cả tình trạng --</option>
          <option value="cho_dat_hang">🟡 Chờ đặt hàng (Pending)</option>
          <option value="da_dat_hang">🔵 Đã đặt hàng (Ordered)</option>
          <option value="cho_giao_hang">🟣 Chờ giao hàng (Shipping / ETA)</option>
          <option value="da_giao_hang">🟢 Đã giao hàng (Delivered)</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold mb-1">Nguồn Phát Sinh Cảnh Báo</label>
        <select class="form-select form-select-sm" id="filterRequestType" onchange="loadReorderTracking()">
          <option value="ALL">-- Tất cả nguồn --</option>
          <option value="stock_check">Kiểm tra tồn kho từ hiện trường</option>
          <option value="delivery_deadline">Yêu cầu hỏi kỳ hạn giao hàng (ETA)</option>
          <option value="auto_rop">Cảnh báo tự động ROP sau xuất kho</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-bold mb-1">Tìm Kiếm</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><span class="material-icons text-primary" style="font-size: 16px;">search</span></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Tìm theo mã VT, tên vật tư, mã PO, nhà cung cấp..." onkeyup="filterLocalReorders()">
          <button class="app-btn app-btn-outline btn-sm" onclick="document.getElementById('filterSearch').value=''; filterLocalReorders();">
            <span class="material-icons" style="font-size: 16px;">clear</span>
          </button>
        </div>
      </div>

      <div class="col-md-2 text-md-end pt-3 pt-md-0">
        <button class="app-btn app-btn-outline btn-sm" onclick="loadReorderTracking()" title="Tải lại danh sách">
          <span class="material-icons" style="font-size: 16px;">refresh</span>
          <span>Làm mới</span>
        </button>
      </div>
    </div>
  </div>

  <!-- BẢNG DANH SÁCH THEO DÕI ĐƠN HÀNG ROP -->
  <div class="app-card border overflow-hidden mb-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="tableReorders" style="font-size: 12.5px;">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;" class="text-center">STT</th>
            <th style="width: 120px;">Mã VT / SAP</th>
            <th style="min-width: 160px;">Tên Vật Tư & Nhóm</th>
            <th style="width: 75px;" class="text-center">Kệ BIN</th>
            <th style="width: 120px;" class="text-end">Tồn Hiện Có / ROP</th>
            <th style="width: 100px;" class="text-end">Đề Xuất Đặt</th>
            <th style="width: 130px;" class="text-center">Tình Trạng Đơn Hàng</th>
            <th style="min-width: 140px;">Thông Tin Đơn (PO)</th>
            <th style="width: 120px;" class="text-center">Kỳ Hạn Giao (ETA)</th>
            <th style="min-width: 150px;">Ghi Chú & Phản Hồi</th>
            <th style="width: 100px;" class="text-center">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="tbodyReorders">
          <tr>
            <td colspan="11" class="text-center py-4 text-muted">Đang tải danh sách theo dõi đặt hàng...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL XÁC NHẬN & CẬP NHẬT TÌNH TRẠNG ĐƠN HÀNG (YÊU CẦU 4)
     ========================================================================= -->
<div class="modal fade" id="modalUpdateOrderStatus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom py-2">
        <h5 class="modal-title fw-bold text-main d-flex align-items-center gap-2">
          <span class="material-icons text-primary">edit_note</span>
          <span>XÁC NHẬN TÌNH TRẠNG ĐƠN HÀNG & KỲ HẠN GIAO</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form id="formUpdateOrderStatus" onsubmit="event.preventDefault(); submitUpdateOrderStatus();">
          <input type="hidden" id="editAlertId" value="0">

          <!-- Card thông tin cơ bản mặt hàng -->
          <div class="p-2 mb-3 bg-light rounded border d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold text-main fs-6" id="viewItemName">--</div>
              <div class="small text-muted">
                Mã: <strong class="font-monospace text-primary" id="viewItemCode">--</strong> | 
                Nhóm: <span class="badge bg-light text-dark border" id="viewItemGroup">--</span> | 
                Kệ: <span class="font-monospace fw-semibold" id="viewItemBin">--</span>
              </div>
            </div>
            <div class="text-end">
              <div>Tồn hiện có: <strong class="font-monospace text-danger fs-6" id="viewStockCurrent">0.0</strong></div>
              <div class="small text-muted">Điểm ROP: <strong class="font-monospace" id="viewRop">0.0</strong></div>
            </div>
          </div>

          <!-- Trạng thái đơn hàng (3 trạng thái chính theo yêu cầu + Đã giao hàng) -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-primary">
                Tình Trạng Xử Lý Đơn Hàng: <span class="text-danger">*</span>
              </label>
              <select class="form-select form-select-sm fw-bold border-primary" id="editOrderStatus" required onchange="onOrderStatusChange()">
                <option value="cho_dat_hang">🟡 1. Chờ đặt hàng (Chưa phát hành PO)</option>
                <option value="da_dat_hang">🔵 2. Đã đặt hàng (Đã phát hành PO)</option>
                <option value="cho_giao_hang">🟣 3. Chờ giao hàng (Đang vận chuyển / Đã có ETA)</option>
                <option value="da_giao_hang">🟢 4. Đã giao hàng (Đã nhập kho thực tế)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-danger">
                Kỳ Hạn Giao Hàng Dự Kiến (ETA):
              </label>
              <input type="date" class="form-control form-control-sm font-monospace border-danger fw-bold" id="editExpectedDeliveryDate">
              <small class="text-muted" style="font-size: 11px;">Phản hồi thời điểm hàng về xưởng cho người yêu cầu</small>
            </div>
          </div>

          <!-- Chi tiết mua hàng: NCC, PO, Số lượng, Ngày đặt -->
          <div class="p-3 mb-3 rounded border" style="background: #f8fafc;">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Nhà Cung Cấp (Supplier):</label>
                <input type="text" class="form-control form-control-sm" id="editSupplierName" placeholder="Tên nhà cung cấp / đối tác...">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Mã Đơn Mua Hàng (Số PO):</label>
                <input type="text" class="form-control form-control-sm font-monospace text-uppercase" id="editPoCode" placeholder="PO-2026-XXXX">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Số Lượng Đặt Hàng:</label>
                <input type="number" step="0.1" min="0" class="form-control form-control-sm font-monospace fw-bold text-primary" id="editOrderedQty">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Ngày Phát Hành Đơn (Order Date):</label>
                <input type="date" class="form-control form-control-sm font-monospace" id="editOrderedAt">
              </div>
            </div>
          </div>

          <!-- Ghi chú & Tùy chọn cộng dồn tồn kho -->
          <div class="mb-3">
            <label class="form-label small fw-bold">Ghi Chú Phản Hồi Cho Hiện Trường / Thủ Kho:</label>
            <textarea class="form-control form-control-sm" id="editAdminNotes" rows="2" placeholder="Ghi chú về tiến độ giao hàng, lý do chậm trễ hoặc thông báo hàng về..."></textarea>
          </div>

          <div class="form-check p-2 rounded bg-light border" id="wrapAutoAddStock" style="display: none;">
            <input class="form-check-input ms-1" type="checkbox" id="chkAutoAddStock" value="1" checked>
            <label class="form-check-label small fw-bold text-success ms-2" for="chkAutoAddStock">
              Tự động cộng dồn số lượng đặt vào tồn kho thực tế của vật tư ngay khi hoàn tất
            </label>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light py-2">
        <button type="button" class="app-btn app-btn-outline btn-sm" data-bs-dismiss="modal">Đóng</button>
        <button type="button" class="app-btn app-btn-primary btn-sm d-flex align-items-center gap-1" onclick="submitUpdateOrderStatus()">
          <span class="material-icons" style="font-size: 16px;">save</span>
          <span>Lưu Cập Nhật Tình Trạng</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let allReordersCache = [];

document.addEventListener('DOMContentLoaded', () => {
  loadReorderTracking();
});

function loadReorderTracking() {
  const status = document.getElementById('filterOrderStatus').value;
  const reqType = document.getElementById('filterRequestType').value;

  const url = `api/warehouse.php?action=get_reorder_tracking&order_status=${encodeURIComponent(status)}&request_type=${encodeURIComponent(reqType)}`;

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        allReordersCache = res.data || [];
        updateReorderKpis();
        filterLocalReorders();
      }
    })
    .catch(err => {
      console.error(err);
      document.getElementById('tbodyReorders').innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Lỗi tải dữ liệu theo dõi đặt hàng.</td></tr>';
    });
}

function updateReorderKpis() {
  let choDat = 0, daDat = 0, choGiao = 0, daGiao = 0;

  allReordersCache.forEach(it => {
    const st = it.order_status || 'cho_dat_hang';
    if (st === 'cho_dat_hang') choDat++;
    else if (st === 'da_dat_hang') daDat++;
    else if (st === 'cho_giao_hang') choGiao++;
    else if (st === 'da_giao_hang') daGiao++;
  });

  document.getElementById('kpiChoDatHang').textContent = choDat;
  document.getElementById('kpiDaDatHang').textContent = daDat;
  document.getElementById('kpiChoGiaoHang').textContent = choGiao;
  document.getElementById('kpiDaGiaoHang').textContent = daGiao;
}

function quickFilterStatus(status) {
  document.getElementById('filterOrderStatus').value = status;
  loadReorderTracking();
}

function filterLocalReorders() {
  const search = document.getElementById('filterSearch').value.toLowerCase().trim();
  const tbody = document.getElementById('tbodyReorders');

  const filtered = allReordersCache.filter(it => {
    if (search) {
      const target = `${it.item_code} ${it.item_name_vn} ${it.po_code || ''} ${it.supplier_name || ''} ${it.bin_location || ''}`.toLowerCase();
      if (!target.includes(search)) return false;
    }
    return true;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted">Không tìm thấy bản ghi theo dõi nào phù hợp.</td></tr>';
    return;
  }

  let html = '';
  filtered.forEach((it, idx) => {
    const st = it.order_status || 'cho_dat_hang';
    let stBadge = '';
    if (st === 'cho_dat_hang') {
      stBadge = '<span class="badge bg-warning text-dark border px-2 py-1 font-monospace" style="font-size:11px;">🟡 Chờ đặt hàng</span>';
    } else if (st === 'da_dat_hang') {
      stBadge = '<span class="badge bg-primary text-white border px-2 py-1 font-monospace" style="font-size:11px;">🔵 Đã đặt hàng</span>';
    } else if (st === 'cho_giao_hang') {
      stBadge = '<span class="badge text-white border px-2 py-1 font-monospace" style="background:#8b5cf6; font-size:11px;">🟣 Chờ giao hàng</span>';
    } else if (st === 'da_giao_hang') {
      stBadge = '<span class="badge bg-success text-white border px-2 py-1 font-monospace" style="font-size:11px;">🟢 Đã giao hàng</span>';
    }

    const stock = Number(it.stock_remain || it.current_stock_realtime || 0).toFixed(1);
    const rop   = Number(it.reorder_point || 0).toFixed(1);
    const unit  = escapeHtml(it.unit || 'Ea');

    // Đề xuất đặt: nếu có ordered_qty > 0 dùng ordered_qty, ngược lại dùng reorder_qty
    const needed = (parseFloat(it.ordered_qty) > 0) ? it.ordered_qty : (it.reorder_qty || (parseFloat(rop) * 2));

    const poInfo = it.po_code 
      ? `<div><strong class="font-monospace text-primary">${escapeHtml(it.po_code)}</strong></div><div class="small text-muted">${escapeHtml(it.supplier_name || 'Chưa có NCC')}</div>`
      : `<span class="text-muted small">Chưa có PO</span>`;

    const etaText = it.expected_delivery_date
      ? `<span class="badge bg-light text-danger border font-monospace fw-bold">${formatDateOnly(it.expected_delivery_date)}</span>`
      : '<span class="text-muted small">--</span>';

    html += `
      <tr>
        <td class="text-center text-muted fw-bold">${idx + 1}</td>
        <td>
          <strong class="font-monospace text-primary">${escapeHtml(it.item_code)}</strong>
        </td>
        <td>
          <div class="fw-semibold text-main">${escapeHtml(it.item_name_vn)}</div>
          <div class="small text-muted"><span class="badge bg-light text-dark border">${escapeHtml(it.group_name || 'Kho')}</span></div>
        </td>
        <td class="text-center font-monospace small">
          <span class="badge bg-light text-secondary border">${escapeHtml(it.bin_location || '-')}</span>
        </td>
        <td class="text-end font-monospace">
          <strong class="text-danger">${stock}</strong> <small class="text-muted">${unit}</small>
          <div class="text-muted small" style="font-size:10px;">ROP: ${rop}</div>
        </td>
        <td class="text-end font-monospace fw-bold text-primary">
          ${Number(needed).toFixed(1)} <small class="text-muted">${unit}</small>
        </td>
        <td class="text-center">${stBadge}</td>
        <td>${poInfo}</td>
        <td class="text-center">${etaText}</td>
        <td>
          <div class="small text-truncate" style="max-width: 180px;" title="${escapeHtml(it.admin_notes || '')}">
            ${escapeHtml(it.admin_notes || 'Không có ghi chú')}
          </div>
          ${it.requested_by_user ? `<div class="text-muted" style="font-size:10px;">Yêu cầu: ${escapeHtml(it.requested_by_user)}</div>` : ''}
        </td>
        <td class="text-center">
          <button class="app-btn app-btn-outline btn-sm py-1 px-2 text-primary border-primary-subtle" onclick="openUpdateOrderStatusModal(${it.id})" title="Cập nhật tình trạng đơn hàng và kỳ hạn giao ETA">
            <span class="material-icons" style="font-size: 15px;">tune</span>
            <span style="font-size: 11px;">Cập nhật</span>
          </button>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function openUpdateOrderStatusModal(alertId) {
  const item = allReordersCache.find(it => it.id == alertId);
  if (!item) return;

  document.getElementById('editAlertId').value = item.id;
  document.getElementById('viewItemCode').textContent = item.item_code;
  document.getElementById('viewItemName').textContent = item.item_name_vn;
  document.getElementById('viewItemGroup').textContent = item.group_name || 'Kho';
  document.getElementById('viewItemBin').textContent = item.bin_location || '-';
  document.getElementById('viewStockCurrent').textContent = Number(item.stock_remain || item.current_stock_realtime || 0).toFixed(1) + ' ' + (item.unit || 'Ea');
  document.getElementById('viewRop').textContent = Number(item.reorder_point || 0).toFixed(1);

  document.getElementById('editOrderStatus').value = item.order_status || 'cho_dat_hang';
  document.getElementById('editSupplierName').value = item.supplier_name || '';
  document.getElementById('editPoCode').value = item.po_code || '';
  document.getElementById('editOrderedQty').value = Number(item.ordered_qty > 0 ? item.ordered_qty : (item.reorder_qty || (parseFloat(item.reorder_point || 0) * 2))).toFixed(1);
  document.getElementById('editOrderedAt').value = item.ordered_at ? item.ordered_at.substring(0, 10) : '';
  document.getElementById('editExpectedDeliveryDate').value = item.expected_delivery_date || '';
  document.getElementById('editAdminNotes').value = item.admin_notes || '';

  onOrderStatusChange();
  new bootstrap.Modal(document.getElementById('modalUpdateOrderStatus')).show();
}

function onOrderStatusChange() {
  const st = document.getElementById('editOrderStatus').value;
  const wrap = document.getElementById('wrapAutoAddStock');
  if (st === 'da_giao_hang') {
    wrap.style.display = 'block';
  } else {
    wrap.style.display = 'none';
  }
}

function submitUpdateOrderStatus() {
  const alertId = document.getElementById('editAlertId').value;
  const orderStatus = document.getElementById('editOrderStatus').value;
  const poCode = document.getElementById('editPoCode').value.trim();
  const supplierName = document.getElementById('editSupplierName').value.trim();
  const orderedQty = document.getElementById('editOrderedQty').value;
  const orderedAt = document.getElementById('editOrderedAt').value;
  const expectedDate = document.getElementById('editExpectedDeliveryDate').value;
  const adminNotes = document.getElementById('editAdminNotes').value.trim();
  const autoAddStock = document.getElementById('chkAutoAddStock').checked ? 1 : 0;

  const formData = new FormData();
  formData.append('alert_id', alertId);
  formData.append('order_status', orderStatus);
  formData.append('po_code', poCode);
  formData.append('supplier_name', supplierName);
  formData.append('ordered_qty', orderedQty);
  formData.append('ordered_at', orderedAt);
  formData.append('expected_delivery_date', expectedDate);
  formData.append('admin_notes', adminNotes);
  formData.append('auto_add_stock', autoAddStock);

  fetch('api/warehouse.php?action=update_reorder_order_status', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Cập nhật tình trạng đơn hàng thành công!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalUpdateOrderStatus'));
        if (modal) modal.hide();
        loadReorderTracking();
      } else {
        alert(res.message || 'Cập nhật thất bại.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Lỗi kết nối máy chủ.');
    });
}

function formatDateOnly(dtStr) {
  if (!dtStr) return '--';
  const parts = dtStr.split('-');
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }
  return dtStr;
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

