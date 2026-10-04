<?php
/**
 * Module Quản Lý Đơn Đặt Hàng (Đơn B)
 * Phân hệ: Quản Lý Đơn Hàng (Order Management) - DX Plastic Group
 */

require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền truy cập module
requirePermission(['orders.view', 'admin']);

$currentUser = $_SESSION['user'] ?? [
    'id'       => $_SESSION['user_id'] ?? 1,
    'username' => $_SESSION['username'] ?? 'User',
    'fullname' => $_SESSION['fullname'] ?? 'Người dùng',
    'role'     => $_SESSION['role'] ?? 'viewer'
];
$userRole     = $currentUser['role'] ?? 'viewer';
$userName     = $currentUser['username'] ?? 'User';
$userFullName = $currentUser['fullname'] ?? $userName;
$isAdmin      = ($userRole === 'admin');

// Quyền chi tiết theo RBAC (cấu hình động từ Admin)
$canView        = hasPermission(['orders.view', 'admin']);
$canCreate      = hasPermission(['orders.create', 'admin']);
$canEdit        = hasPermission(['orders.edit', 'admin']);
$canDunConfirm  = hasPermission(['orders.dun_confirm', 'admin']);
$canCuonConfirm = hasPermission(['orders.cuon_confirm', 'admin']);
$canApproveA    = hasPermission(['orders.approve_a', 'admin']);
$canDelete      = hasPermission(['orders.delete', 'admin']);
$canExport      = hasPermission(['orders.export', 'admin']);
?>

<style>
/* CSS RIÊNG CỦA MODULE QUẢN LÝ ĐƠN HÀNG (ĐƠN B) */
.donb-card-kpi {
    border-radius: 10px;
    padding: 16px;
    background: var(--dx-bg-card, #ffffff);
    border: 1px solid var(--dx-border, #e2e8f0);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.donb-card-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.donb-card-kpi.active {
    border-color: var(--dx-primary, #0ea5e9);
    box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
}
.donb-card-kpi .kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 8px;
}
.donb-card-kpi .kpi-value {
    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--dx-text-main, #0f172a);
}
.donb-card-kpi .kpi-label {
    font-size: 12px;
    color: var(--dx-text-muted, #64748b);
    font-weight: 500;
    margin-top: 4px;
}
.donb-card-kpi .kpi-sub {
    font-size: 11px;
    color: var(--dx-text-muted, #64748b);
    margin-top: 2px;
}

/* Alert badge & highlight */
.alert-pulse {
    animation: alertBlink 2s infinite ease-in-out;
}
@keyframes alertBlink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.03); }
}

.table-row-alert {
    background-color: rgba(239, 68, 68, 0.08) !important;
}
.table-row-alert:hover {
    background-color: rgba(239, 68, 68, 0.14) !important;
}
.table-row-completed {
    background-color: rgba(16, 185, 129, 0.05) !important;
}
.table-row-converted {
    opacity: 0.75;
}

/* Department pill switch */
.dept-pill-btn {
    border: 1px solid var(--dx-border, #cbd5e1);
    background: var(--dx-bg-card, #ffffff);
    color: var(--dx-text-muted, #475569);
    font-weight: 600;
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.dept-pill-btn:hover {
    background: var(--dx-bg-hover, #f1f5f9);
    color: var(--dx-text-main, #0f172a);
}
.dept-pill-btn.active {
    background: var(--dx-primary, #0ea5e9);
    border-color: var(--dx-primary, #0ea5e9);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(14, 165, 233, 0.3);
}

/* Table styling */
.donb-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--dx-border, #e2e8f0);
    border-radius: 8px;
    background: var(--dx-bg-card, #ffffff);
}
.donb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    white-space: nowrap;
}
.donb-table th {
    background: var(--dx-bg-subtle, #f8fafc);
    color: var(--dx-text-muted, #475569);
    font-weight: 700;
    padding: 10px 8px;
    border-bottom: 2px solid var(--dx-border, #cbd5e1);
    text-align: left;
    position: sticky;
    top: 0;
    z-index: 5;
}
.donb-table td {
    padding: 8px;
    border-bottom: 1px solid var(--dx-border, #e2e8f0);
    color: var(--dx-text-main, #1e293b);
    vertical-align: middle;
}

/* Modal details */
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
}
.detail-item {
    background: var(--dx-bg-subtle, #f8fafc);
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid var(--dx-border, #e2e8f0);
}
.detail-item .label {
    font-size: 11px;
    color: var(--dx-text-muted, #64748b);
    font-weight: 600;
    text-transform: uppercase;
}
.detail-item .value {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--dx-text-main, #0f172a);
    margin-top: 2px;
}
</style>

<div class="app-page-wrapper">

    <!-- 1. HEADER TRANG CHUẨN CÔNG NGHIỆP -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title d-flex align-items-center gap-2">
                <span class="material-icons text-primary" style="font-size: 28px;">receipt_long</span>
                <span data-i18n="orders.title"><?= __('orders.title', 'QUẢN LÝ ĐƠN ĐẶT HÀNG (ĐƠN B)') ?></span>
            </h1>
            <p class="app-page-subtitle">
                Phân hệ: <strong>Quản Lý Đơn Hàng</strong> &bull; Quy trình phối hợp 3 bộ phận: <strong>PC Tiếp nhận & Khởi tạo</strong> &rarr; <strong>Đùn xác nhận</strong> &rarr; <strong>Cuộn sản xuất & Thực tích</strong> &rarr; <strong>PC Duyệt chuyển B &rarr; A</strong>
            </p>
        </div>

        <div class="app-page-actions d-flex flex-wrap align-items-center gap-2">
            <!-- Bộ chọn góc nhìn bộ phận -->
            <div class="d-flex align-items-center bg-light p-1 rounded-pill border" id="deptSwitcher">
                <button type="button" class="dept-pill-btn active" data-dept="all" onclick="switchDepartment('all')" title="Xem toàn bộ dữ liệu">
                    <span class="material-icons" style="font-size: 16px;">tune</span> <span data-i18n="common.all"><?= __('common.all', 'Tất cả') ?></span>
                </button>
                <button type="button" class="dept-pill-btn" data-dept="pc" onclick="switchDepartment('pc')" title="Góc nhìn Phòng Kế hoạch (PC)">
                    <span class="material-icons" style="font-size: 16px;">assignment</span> PC (Kế hoạch)
                </button>
                <button type="button" class="dept-pill-btn" data-dept="dun" onclick="switchDepartment('dun')" title="Góc nhìn Bộ phận Đùn nhựa">
                    <span class="material-icons" style="font-size: 16px;">precision_manufacturing</span> Xưởng Đùn
                </button>
                <button type="button" class="dept-pill-btn" data-dept="cuon" onclick="switchDepartment('cuon')" title="Góc nhìn Bộ phận Cuộn nhựa">
                    <span class="material-icons" style="font-size: 16px;">rotate_right</span> Xưởng Cuộn
                </button>
            </div>

            <!-- Nút thao tác (Kiểm soát theo RBAC) -->
            <?php if ($canCreate || $canEdit || $isAdmin): ?>
            <button type="button" class="app-btn app-btn-success" onclick="openExcelImportModal()" title="Upload file Excel danh sách đơn">
                <span class="material-icons">upload_file</span> <span data-i18n="common.btn_import"><?= __('common.btn_import', 'Import Excel') ?></span>
            </button>
            <button type="button" class="app-btn app-btn-primary" onclick="openCreateOrderModal()" title="Tạo đơn hàng thủ công">
                <span class="material-icons">add_circle</span> <span data-i18n="common.btn_add"><?= __('common.btn_add', '+ Nhập Đơn Mới') ?></span>
            </button>
            <?php endif; ?>

            <?php if ($canExport || $isAdmin): ?>
            <button type="button" class="app-btn app-btn-outline" onclick="exportExcelFile()" title="Xuất dữ liệu Excel">
                <span class="material-icons">download</span> <span data-i18n="common.btn_export"><?= __('common.btn_export', 'Xuất Excel') ?></span>
            </button>
            <?php endif; ?>

            <button type="button" class="app-btn app-btn-secondary" onclick="refreshData()" title="<?= __('common.btn_refresh', 'Làm mới') ?>">
                <span class="material-icons">refresh</span>
            </button>
        </div>
    </div>

    <!-- BANNER BỘ PHẬN & QUYỀN ĐANG KÍCH HOẠT -->
    <div id="deptBanner" class="alert alert-secondary py-2 px-3 mb-3 d-flex align-items-center justify-content-between rounded-3 border" style="font-size: 13px;">
        <div class="d-flex align-items-center gap-2">
            <span class="material-icons" id="deptBannerIcon">tune</span>
            <span id="deptBannerText">Đang hiển thị toàn bộ tiến độ đơn hàng. Các nút thao tác sẽ kích hoạt tương ứng theo quyền tài khoản của bạn.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary fw-bold">Vai trò: <?= htmlspecialchars(strtoupper($userRole)) ?></span>
            <?php if ($isAdmin): ?>
            <a href="index.php?mainpage=system&subpage=roles" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 2px 8px;">
                <span class="material-icons" style="font-size: 12px; vertical-align: -2px;">manage_accounts</span> Cài đặt quyền
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. KPI STATS CARDS -->
    <div class="row g-2 mb-3">
        <!-- Card 1: Tổng đơn B -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi" onclick="filterByTab('don_b_all')">
                <div class="kpi-icon bg-primary-subtle text-primary">
                    <span class="material-icons">inventory_2</span>
                </div>
                <div class="kpi-value text-primary" id="kpiTotalB">0</div>
                <div class="kpi-label">Tổng Đơn B</div>
                <div class="kpi-sub" id="kpiMetCanB">0 m cần SX</div>
            </div>
        </div>

        <!-- Card 2: Cảnh báo 5 ngày (CRITICAL ALERT) -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi border-danger" onclick="filterByTab('alert_5days')">
                <div class="kpi-icon bg-danger-subtle text-danger alert-pulse">
                    <span class="material-icons">notification_important</span>
                </div>
                <div class="kpi-value text-danger d-flex align-items-center gap-1">
                    <span id="kpiAlert5Days">0</span>
                    <span class="badge bg-danger text-white rounded-pill" style="font-size: 10px;">CẤP BÁCH</span>
                </div>
                <div class="kpi-label text-danger fw-bold">Hạn kho &le; 5 Ngày (Chưa SX)</div>
                <div class="kpi-sub text-danger">Chưa có thực tích</div>
            </div>
        </div>

        <!-- Card 3: Chờ Đùn Xác Nhận -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi" onclick="filterByTab('pending_confirm')">
                <div class="kpi-icon bg-warning-subtle text-warning">
                    <span class="material-icons">hourglass_top</span>
                </div>
                <div class="kpi-value text-warning" id="kpiDunPending">0</div>
                <div class="kpi-label">Chờ Đùn Xác Nhận</div>
                <div class="kpi-sub">Hiện trường chưa duyệt</div>
            </div>
        </div>

        <!-- Card 4: Đang Sản Xuất -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi" onclick="filterByTab('in_production')">
                <div class="kpi-icon bg-info-subtle text-info">
                    <span class="material-icons">published_with_changes</span>
                </div>
                <div class="kpi-value text-info" id="kpiInProduction">0</div>
                <div class="kpi-label">Đang Sản Xuất</div>
                <div class="kpi-sub" id="kpiMetDaSxB">0 m đã SX</div>
            </div>
        </div>

        <!-- Card 5: Hoàn thành chờ duyệt B -> A -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi border-success" onclick="filterByTab('completed_pending_a')">
                <div class="kpi-icon bg-success-subtle text-success">
                    <span class="material-icons">task_alt</span>
                </div>
                <div class="kpi-value text-success" id="kpiCompletedPendingA">0</div>
                <div class="kpi-label">Hoàn Thành (Chờ Duyệt A)</div>
                <div class="kpi-sub">Đã đủ số lượng</div>
            </div>
        </div>

        <!-- Card 6: Đã Chuyển Sang A -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="donb-card-kpi" onclick="filterByTab('converted_a')">
                <div class="kpi-icon bg-secondary-subtle text-secondary">
                    <span class="material-icons">verified</span>
                </div>
                <div class="kpi-value text-secondary" id="kpiConvertedA">0</div>
                <div class="kpi-label">Đã Chuyển Sang A</div>
                <div class="kpi-sub">Hoàn tất quy trình</div>
            </div>
        </div>
    </div>

    <!-- 3. BẢNG TAB LỌC & THANH TÌM KIẾM -->
    <div class="app-card mb-3">
        <div class="app-card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-3 mb-3">
                <ul class="nav nav-pills gap-1" id="orderTabs">
                    <li class="nav-item">
                        <button class="nav-link active py-1 px-3" data-tab="all" onclick="switchTab('all', this)">
                            Tất cả đơn
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-3" data-tab="don_b_all" onclick="switchTab('don_b_all', this)">
                            Đơn B
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-danger py-1 px-3 fw-bold" data-tab="alert_5days" onclick="switchTab('alert_5days', this)">
                            <span class="material-icons" style="font-size: 15px; vertical-align: -2px;">warning</span>
                            Cảnh báo &le; 5 ngày <span class="badge bg-danger text-white ms-1" id="tabAlertBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-3" data-tab="pending_confirm" onclick="switchTab('pending_confirm', this)">
                            Chờ xác nhận
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-3" data-tab="in_production" onclick="switchTab('in_production', this)">
                            Đang sản xuất
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-success py-1 px-3 fw-bold" data-tab="completed_pending_a" onclick="switchTab('completed_pending_a', this)">
                            Hoàn thành &rarr; Chờ duyệt A <span class="badge bg-success text-white ms-1" id="tabCompletedBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-3" data-tab="converted_a" onclick="switchTab('converted_a', this)">
                            Lịch sử Đơn A
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-3" data-tab="rejected" onclick="switchTab('rejected', this)">
                            Bị từ chối
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="totalRecordsText">Đang tải...</span>
                </div>
            </div>

            <!-- Form Tìm kiếm & Bộ lọc nâng cao -->
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4 col-xl-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <span class="material-icons text-muted" style="font-size: 18px;">search</span>
                        </span>
                        <input type="text" id="searchInput" class="app-form-control border-start-0" placeholder="Tìm theo Mã đơn (オーダー), Mã SP (品番), Tháp No..." onkeyup="if(event.key==='Enter') loadOrders(1);">
                        <button class="app-btn app-btn-secondary" type="button" onclick="loadOrders(1)">Tìm</button>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <select id="filterDunStatus" class="app-form-select" onchange="loadOrders(1)">
                        <option value="">-- Đùn xác nhận --</option>
                        <option value="OK">Xác nhận (OK)</option>
                        <option value="Chưa xác định">Chưa xác định</option>
                        <option value="Từ chối">Từ chối</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <select id="filterCuonStatus" class="app-form-select" onchange="loadOrders(1)">
                        <option value="">-- Cuộn xác nhận --</option>
                        <option value="OK">Xác nhận (OK)</option>
                        <option value="Chưa xác định">Chưa xác định</option>
                        <option value="K CÓ LÔ">K CÓ LÔ</option>
                        <option value="Từ chối">Từ chối</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-xl-2">
                    <select id="pageLimit" class="app-form-select" onchange="loadOrders(1)">
                        <option value="25">25 dòng/trang</option>
                        <option value="50">50 dòng/trang</option>
                        <option value="100">100 dòng/trang</option>
                        <option value="200">200 dòng/trang</option>
                    </select>
                </div>

                <div class="col-6 col-md-12 col-xl-2 text-end">
                    <button type="button" class="app-btn app-btn-outline w-100" onclick="resetFilters()">
                        <span class="material-icons">clear_all</span> Xóa lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. BẢNG DỮ LIỆU CHÍNH (DON B TABLE) -->
    <div class="donb-table-wrap mb-3 shadow-sm">
        <table class="donb-table" id="ordersTable">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">STT</th>
                    <th style="width: 120px;">Cảnh báo / Tình trạng</th>
                    <th>Mã đơn hàng (オーダー)</th>
                    <th>Tháp No (手配No.)</th>
                    <th>Mã sản phẩm (品番)</th>
                    <th style="text-align: right;">Quy cách</th>
                    <th style="text-align: right;">SL Đặt (cuộn)</th>
                    <th style="text-align: right;">Tổng mét cần</th>
                    <th style="text-align: center;">Loại (A/B)</th>
                    <th>Kỳ hạn nhập kho</th>
                    <th>Kỳ hạn đơn hàng</th>
                    <th>Đùn xác nhận</th>
                    <th>Cuộn xác nhận</th>
                    <th style="text-align: right;">Cuộn đã SX</th>
                    <th style="text-align: right;">Tổng mét SX</th>
                    <th style="text-align: right;">Còn thiếu</th>
                    <th>Ngày B &rarr; A</th>
                    <th>PC Note</th>
                    <th>Sản xuất Note</th>
                    <th style="text-align: center; position: sticky; right: 0; background: var(--dx-bg-subtle, #f8fafc); z-index: 6;">Thao tác</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <tr>
                    <td colspan="20" class="text-center py-4 text-muted">
                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div> Đang tải danh sách đơn hàng...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 5. PHÂN TRANG -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div class="text-muted small" id="paginationInfo">Hiển thị 0 - 0 của 0 đơn</div>
        <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
    </div>

</div>

<!-- ======================================================== -->
<!-- MODAL 1: TẠO / SỬA ĐƠN HÀNG THỦ CÔNG (PC / ADMIN)       -->
<!-- ======================================================== -->
<div class="modal fade" id="modalOrderManual" tabindex="-1" aria-labelledby="modalOrderManualTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalOrderManualTitle">
                    <span class="material-icons text-primary">edit_note</span>
                    <span id="orderModalHeading">Khởi Tạo Đơn Hàng Mới (PC)</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formOrderManual" onsubmit="submitOrderManual(event)">
                <input type="hidden" name="id" id="editOrderId" value="">
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <span class="material-icons" style="font-size: 16px; vertical-align: -3px;">info</span>
                        <strong>Quy tắc hệ thống:</strong> Nếu mã đơn hàng (<code>オーダー</code>) đã tồn tại, hệ thống sẽ tự động cập nhật thông tin thay vì tạo bản ghi trùng lặp.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mã Đơn Hàng (オーダー) <span class="text-danger">*</span></label>
                            <input type="text" name="ma_don_hang" id="inputMaDonHang" class="app-form-control" required placeholder="VD: BIN-AM-012373 -333">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Số sắp xếp / Tháp No (手配No.)</label>
                            <input type="text" name="so_phieu_nhap" id="inputSoPhieuNhap" class="app-form-control" placeholder="VD: NT1HH-1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mã Sản Phẩm (品番) <span class="text-danger">*</span></label>
                            <input type="text" name="ma_san_pham" id="inputMaSanPham" class="app-form-control" required placeholder="VD: TU0805R-100Z2" oninput="autoDetectMeters()">
                            <small class="text-muted">Chứa quy cách mét/cuộn sau dấu gạch ngang (VD: -100 &rarr; 100m)</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Quy Cách (m/cuộn)</label>
                            <input type="number" name="so_met_quy_cach" id="inputSoMetQuyCach" class="app-form-control" value="100" min="1" oninput="recalcTotalMeters()">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Số Lượng Đặt (cuộn) <span class="text-danger">*</span></label>
                            <input type="number" name="so_luong_dat" id="inputSoLuongDat" class="app-form-control" value="1" min="1" required oninput="recalcTotalMeters()">
                        </div>

                        <div class="col-md-12">
                            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
                                <span class="small text-muted fw-semibold">TỔNG SỐ MÉT CẦN SẢN XUẤT:</span>
                                <span class="fw-bold fs-6 text-primary" id="previewTongMetCan">100 mét</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Phân Loại Đơn (A/B) <span class="text-danger">*</span></label>
                            <select name="phan_loai_don" id="inputPhanLoaiDon" class="app-form-select">
                                <option value="B" selected>Đơn B (Thiếu tồn kho / Cần SX)</option>
                                <option value="A">Đơn A (Đủ hàng tồn kho)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ngày Nhận Đơn</label>
                            <input type="date" name="ngay_nhan_don" id="inputNgayNhanDon" class="app-form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kỳ Hạn Đơn Hàng</label>
                            <input type="date" name="ky_han_giao_hang" id="inputKyHanGiaoHang" class="app-form-control" onchange="autoSuggestDueStorage()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Kỳ Hạn Nhập Kho <span class="text-danger">*</span></label>
                            <input type="date" name="ngay_yc_nhap_kho" id="inputNgayYcNhapKho" class="app-form-control border-danger" required>
                            <small class="text-muted">Kỳ hạn hiện trường phải hoàn thành</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ngày Dự Kiến Xuất</label>
                            <input type="date" name="ngay_du_kien_xuat" id="inputNgayDuKienXuat" class="app-form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vận Chuyển</label>
                            <select name="phuong_thuc_van_chuyen" id="inputPhuongThuc" class="app-form-select">
                                <option value="SEA">SEA (Đường biển)</option>
                                <option value="AIR">AIR (Đường bay)</option>
                                <option value="DOMESTIC">Nội địa</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mã Khách Hàng (客先コード)</label>
                            <input type="text" name="ma_khach_hang" id="inputMaKhachHang" class="app-form-control" placeholder="VD: 95018-07">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tên Khách Hàng (客先)</label>
                            <input type="text" name="ten_khach_hang" id="inputTenKhachHang" class="app-form-control" placeholder="VD: JAPAN">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">PC Note (Ghi chú Kế hoạch)</label>
                            <textarea name="pc_note" id="inputPcNote" class="app-form-control" rows="2" placeholder="Ghi chú về tình trạng thiếu tồn kho, yêu cầu giao hàng..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="app-btn app-btn-primary" id="btnSubmitOrder">
                        <span class="material-icons">save</span> Lưu Đơn Hàng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: XÁC NHẬN BỘ PHẬN ĐÙN NHỰA                       -->
<!-- ======================================================== -->
<div class="modal fade" id="modalDunConfirm" tabindex="-1" aria-labelledby="modalDunConfirmTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons text-warning">precision_manufacturing</span>
                    <span>Xác Nhận Đơn Hàng (Xưởng Đùn Nhựa)</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formDunConfirm" onsubmit="submitDunConfirm(event)">
                <input type="hidden" name="id" id="dunConfirmId" value="">
                <div class="modal-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="fw-bold fs-6 text-primary" id="dunModalOrderCode">-</div>
                        <div class="small text-muted mt-1" id="dunModalProductInfo">-</div>
                        <div class="small text-danger fw-bold mt-1" id="dunModalDueDate">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Xác Nhận Sản Xuất <span class="text-danger">*</span></label>
                        <select name="status" id="dunConfirmStatus" class="app-form-select fw-bold" required>
                            <option value="OK">Xác nhận thực hiện (OK)</option>
                            <option value="Từ chối">Từ chối sản xuất</option>
                            <option value="Chưa xác định">Chưa xác định</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ngày Dự Kiến Xuất Hàng</label>
                        <input type="date" name="ngay_du_kien_xuat" id="dunNgayDuKienXuat" class="app-form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi Chú Sản Xuất Đùn</label>
                        <textarea name="san_xuat_note" id="dunSanXuatNote" class="app-form-control" rows="3" placeholder="Nhập lý do nếu từ chối hoặc phương án chạy máy đùn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="app-btn app-btn-warning">
                        <span class="material-icons">check_circle</span> Lưu Xác Nhận Đùn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 3: CẬP NHẬT THỰC TÍCH BỘ PHẬN CUỘN NHỰA             -->
<!-- ======================================================== -->
<div class="modal fade" id="modalCuonUpdate" tabindex="-1" aria-labelledby="modalCuonUpdateTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons text-info">rotate_right</span>
                    <span>Cập Nhật Thực Tích & Cuộn (Xưởng Cuộn)</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formCuonUpdate" onsubmit="submitCuonUpdate(event)">
                <input type="hidden" name="id" id="cuonUpdateId" value="">
                <input type="hidden" id="cuonOrderQty" value="0">
                <input type="hidden" id="cuonOrderSpec" value="0">
                <div class="modal-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-6 text-primary" id="cuonModalOrderCode">-</span>
                            <span class="badge bg-secondary" id="cuonModalSpecTag">0 m/cuộn</span>
                        </div>
                        <div class="small text-muted mt-1" id="cuonModalProductInfo">-</div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="small fw-semibold">Số lượng đặt: <strong id="cuonModalQtyOrder" class="text-primary">0</strong> cuộn</span>
                            <span class="small fw-semibold">Tổng mét cần: <strong id="cuonModalTotalMetersReq" class="text-primary">0</strong> m</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cuộn Xác Nhận</label>
                            <select name="cuon_xac_nhan" id="cuonConfirmStatus" class="app-form-select">
                                <option value="OK">OK (Xác nhận)</option>
                                <option value="Chưa xác định">Chưa xác định</option>
                                <option value="K CÓ LÔ">K CÓ LÔ (Thiếu lô)</option>
                                <option value="Từ chối">Từ chối</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Cuộn Đã SX (Thực tích) <span class="text-danger">*</span></label>
                            <input type="number" name="cuon_da_sx" id="cuonDaSxInput" class="app-form-control border-success fw-bold fs-6" value="0" min="0" required oninput="calcCuonShortage()">
                        </div>

                        <!-- Card tính toán tự động -->
                        <div class="col-md-12">
                            <div class="p-2 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted">Số lượng còn thiếu:</span>
                                    <span class="fw-bold fs-6" id="cuonCalcRemaining">0 cuộn</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted">Tổng mét đã SX:</span>
                                    <span class="fw-bold text-success" id="cuonCalcProducedMeters">0 m</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" id="cuonProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="small text-center text-muted mt-1" id="cuonPercentText">0% hoàn thành</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Tình Trạng Bobin</label>
                            <input type="text" name="tinh_trang_bobin" id="cuonBobinInput" class="app-form-control" placeholder="VD: Đủ bobin, Thiếu bobin loại A...">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Ghi Chú Sản Xuất</label>
                            <textarea name="san_xuat_note" id="cuonNoteInput" class="app-form-control" rows="2" placeholder="Ghi chú thực tích theo ca, tiến độ cuộn..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="app-btn app-btn-success">
                        <span class="material-icons">save</span> Cập Nhật Thực Tích
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 4: XÉT DUYỆT CHUYỂN B SANG A (PC / ADMIN)          -->
<!-- ======================================================== -->
<div class="modal fade" id="modalApproveBToA" tabindex="-1" aria-labelledby="modalApproveBToATitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons">verified</span>
                    <span>Xét Duyệt Chuyển Đơn B &rarr; Đơn A (PC)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formApproveBToA" onsubmit="submitApproveBToA(event)">
                <input type="hidden" name="id" id="approveOrderId" value="">
                <div class="modal-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="fw-bold fs-6 text-primary" id="approveModalOrderCode">-</div>
                        <div class="small text-muted mt-1" id="approveModalProductInfo">-</div>
                        <div class="small text-success fw-bold mt-2 d-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size: 16px;">check_circle</span>
                            <span id="approveModalStatusText">Đã đủ số lượng thực tích sản xuất!</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ngày PC Xác Nhận Chuyển B &rarr; A <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_chuyen_b_to_a" id="approveNgayChuyen" class="app-form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi Chú Duyệt Của PC</label>
                        <textarea name="pc_note" id="approvePcNote" class="app-form-control" rows="2" placeholder="Ghi chú phê duyệt..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="app-btn app-btn-success">
                        <span class="material-icons">done_all</span> Xác Nhận Duyệt Đơn A
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 5: IMPORT FILE EXCEL (PC / ADMIN)                 -->
<!-- ======================================================== -->
<div class="modal fade" id="modalExcelImport" tabindex="-1" aria-labelledby="modalExcelImportTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons text-success">upload_file</span>
                    <span>Upload File Excel Danh Sách Đơn B</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formExcelImport" onsubmit="submitExcelImport(event)">
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <span class="material-icons" style="font-size: 16px; vertical-align: -3px;">help_outline</span>
                        Hệ thống đọc sheet <strong>ĐƠN B</strong> (hoặc sheet đầu tiên), tự động nhận diện tiêu đề tiếng Nhật/Việt (<code>品番</code>, <code>手配No.</code>, <code>オーダー</code>, <code>受注数</code>...).<br>
                        <strong>Check trùng:</strong> Dựa trên Mã đơn hàng (<code>オーダー</code>). Nếu đã tồn tại sẽ <strong>chỉ cập nhật thông tin mới</strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn File Excel (.xlsx, .xls) <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" id="excelFileInput" class="app-form-control" accept=".xlsx,.xls,.xlsm" required>
                    </div>

                    <div id="importProgress" class="d-none">
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                        </div>
                        <div class="small text-center text-muted">Đang xử lý dữ liệu và kiểm tra trùng lặp...</div>
                    </div>

                    <div id="importResult" class="d-none mt-3"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="app-btn app-btn-success" id="btnSubmitImport">
                        <span class="material-icons">upload</span> Bắt Đầu Tải Lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 6: CHI TIẾT & LỊCH SỬ THAO TÁC (AUDIT TRAIL)       -->
<!-- ======================================================== -->
<div class="modal fade" id="modalOrderDetail" tabindex="-1" aria-labelledby="modalOrderDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span class="material-icons text-primary">feed</span>
                    <span>Chi Tiết Đơn Hàng & Lịch Sử Thao Tác</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-primary">
                    <span class="material-icons" style="font-size: 18px;">info</span>
                    Thông Tin Đơn Hàng (36 Trường Dữ Liệu Sheet Đơn B)
                </h6>
                <div class="detail-grid mb-4" id="orderDetailGrid"></div>

                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-primary border-top pt-3">
                    <span class="material-icons" style="font-size: 18px;">history</span>
                    Nhật Ký Thao Tác Liên Phòng Ban (Audit Log)
                </h6>
                <div id="orderHistoryList" class="p-2 bg-light rounded border small">
                    Đang tải nhật ký...
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOGIC CHO MODULE ĐƠN HÀNG (ĐƠN B) -->
<script>
// Quyền RBAC tiêm từ Backend sang JS
const CAN_CREATE       = <?= json_encode($canCreate || $isAdmin) ?>;
const CAN_EDIT         = <?= json_encode($canEdit || $isAdmin) ?>;
const CAN_DUN_CONFIRM  = <?= json_encode($canDunConfirm || $isAdmin) ?>;
const CAN_CUON_CONFIRM = <?= json_encode($canCuonConfirm || $isAdmin) ?>;
const CAN_APPROVE_A    = <?= json_encode($canApproveA || $isAdmin) ?>;
const CAN_DELETE       = <?= json_encode($canDelete || $isAdmin) ?>;
const CAN_EXPORT       = <?= json_encode($canExport || $isAdmin) ?>;

// Biến trạng thái
let currentDept = 'all';
let currentTab = 'all';
let currentPage = 1;
let currentLimit = 25;
let currentOrdersData = [];

document.addEventListener('DOMContentLoaded', function() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const orderCodeParam = urlParams.get('order_code');
        const searchInput = document.getElementById('searchInput');
        if (orderCodeParam && searchInput) {
            searchInput.value = orderCodeParam;
        }
        
        loadStats();
        loadOrders(1);
    } catch (e) {
        console.error('Lỗi khi tải trang Quản lý Đơn B:', e);
    }
});

// Chuyển góc nhìn bộ phận
function switchDepartment(dept) {
    currentDept = dept;
    document.querySelectorAll('#deptSwitcher .dept-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.dept === dept);
    });

    const bannerText = document.getElementById('deptBannerText');
    const bannerIcon = document.getElementById('deptBannerIcon');
    const banner     = document.getElementById('deptBanner');

    if (dept === 'pc') {
        banner.className = 'alert alert-primary py-2 px-3 mb-3 d-flex align-items-center justify-content-between rounded-3 border';
        bannerIcon.textContent = 'assignment';
        bannerText.innerHTML = 'Góc nhìn: <strong>Phòng Kế hoạch (PC)</strong>. Tiếp nhận đơn, gán loại A/B, kiểm tra thiếu tồn kho và duyệt chuyển B &rarr; A.';
    } else if (dept === 'dun') {
        banner.className = 'alert alert-warning py-2 px-3 mb-3 d-flex align-items-center justify-content-between rounded-3 border';
        bannerIcon.textContent = 'precision_manufacturing';
        bannerText.innerHTML = 'Góc nhìn: <strong>Bộ phận Đùn nhựa</strong>. Xác nhận đơn hàng, cập nhật ngày dự kiến xuất và ghi chú sản xuất.';
    } else if (dept === 'cuon') {
        banner.className = 'alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between rounded-3 border';
        bannerIcon.textContent = 'rotate_right';
        bannerText.innerHTML = 'Góc nhìn: <strong>Bộ phận Cuộn nhựa</strong>. Cập nhật số lượng thực tích (cuộn/mét), theo dõi số lượng thiếu và tình trạng bobin.';
    } else {
        banner.className = 'alert alert-secondary py-2 px-3 mb-3 d-flex align-items-center justify-content-between rounded-3 border';
        bannerIcon.textContent = 'tune';
        bannerText.innerHTML = 'Góc nhìn: <strong>Tất cả (Quản trị)</strong>. Hiển thị toàn bộ dữ liệu và đầy đủ nút thao tác.';
    }

    renderTable();
}

function switchTab(tab, el) {
    currentTab = tab;
    document.querySelectorAll('#orderTabs .nav-link').forEach(btn => btn.classList.remove('active'));
    if (el) el.classList.add('active');
    loadOrders(1);
}

function filterByTab(tab) {
    const tabBtn = document.querySelector(`#orderTabs .nav-link[data-tab="${tab}"]`);
    if (tabBtn) {
        switchTab(tab, tabBtn);
    }
}

function refreshData() {
    loadStats();
    loadOrders(currentPage);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterDunStatus').value = '';
    document.getElementById('filterCuonStatus').value = '';
    loadOrders(1);
}

// 1. TẢI KPI STATS (Có bắt lỗi & phòng vệ)
function loadStats() {
    fetch('api/don_b_api.php?action=stats')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(res => {
            if (res.success && res.stats) {
                const s = res.stats;
                const setTxt = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setTxt('kpiTotalB', Number(s.total_b || 0).toLocaleString());
                setTxt('kpiMetCanB', Number(s.total_met_can_b || 0).toLocaleString() + ' m cần SX');
                setTxt('kpiAlert5Days', Number(s.alert_5days_count || 0).toLocaleString());
                setTxt('tabAlertBadge', Number(s.alert_5days_count || 0));
                setTxt('kpiDunPending', Number(s.dun_pending_count || 0).toLocaleString());
                setTxt('kpiInProduction', Number(s.in_production_count || 0).toLocaleString());
                setTxt('kpiMetDaSxB', Number(s.total_met_da_sx_b || 0).toLocaleString() + ' m đã SX');
                setTxt('kpiCompletedPendingA', Number(s.completed_pending_a_count || 0).toLocaleString());
                setTxt('tabCompletedBadge', Number(s.completed_pending_a_count || 0));
                setTxt('kpiConvertedA', Number(s.total_a || 0).toLocaleString());
            }
        })
        .catch(err => console.warn('Lỗi tải KPI stats:', err));
}

// 2. TẢI DANH SÁCH ĐƠN HÀNG (Có try/catch & hiển thị lỗi trực quan)
function loadOrders(page = 1) {
    currentPage = page;
    const limitEl = document.getElementById('pageLimit');
    currentLimit = parseInt((limitEl ? limitEl.value : null) || '25');
    const search = encodeURIComponent((document.getElementById('searchInput')?.value || '').trim());
    const dunStatus = encodeURIComponent(document.getElementById('filterDunStatus')?.value || '');
    const cuonStatus = encodeURIComponent(document.getElementById('filterCuonStatus')?.value || '');

    const tbody = document.getElementById('ordersTableBody');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="20" class="text-center py-4 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div> Đang tải dữ liệu đơn hàng...</td></tr>`;
    }

    const url = `api/don_b_api.php?action=list&tab=${currentTab}&search=${search}&dun_status=${dunStatus}&cuon_status=${cuonStatus}&page=${currentPage}&limit=${currentLimit}`;

    fetch(url)
        .then(async res => {
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                if (res.status === 401) {
                    throw new Error('Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
                }
                if (res.status === 403) {
                    throw new Error(data?.message || 'Tài khoản chưa được cấp quyền xem Đơn hàng (orders.view).');
                }
                throw new Error(data?.message || `Lỗi máy chủ (HTTP ${res.status})`);
            }
            return data;
        })
        .then(res => {
            if (res && res.success) {
                currentOrdersData = res.data || [];
                renderTable();
                renderPagination(res.pagination);
            } else {
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="20" class="text-center py-4 text-danger">
                        <span class="material-icons d-block mb-2 text-danger" style="font-size: 32px;">error_outline</span>
                        <strong>Không thể tải dữ liệu:</strong> ${escapeHtml(res?.message || 'Lỗi không xác định')}
                    </td></tr>`;
                }
            }
        })
        .catch(err => {
            console.error('Lỗi API loadOrders:', err);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="20" class="text-center py-4 text-danger">
                    <span class="material-icons d-block mb-2 text-danger" style="font-size: 36px;">warning</span>
                    <strong>Lỗi kết nối dữ liệu:</strong> ${escapeHtml(err.message || 'Không thể kết nối đến máy chủ.')}<br>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadOrders(${currentPage})">
                            <span class="material-icons" style="font-size: 14px; vertical-align: -2px;">refresh</span> Thử tải lại
                        </button>
                    </div>
                </td></tr>`;
            }
        });
}

// 3. HIỂN THỊ DỮ LIỆU BẢNG (Có Try-Catch Error Boundary)
function renderTable() {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    try {
        if (!currentOrdersData || currentOrdersData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="20" class="text-center py-5 text-muted"><span class="material-icons d-block mb-2" style="font-size: 36px;">inbox</span>Không tìm thấy đơn hàng nào phù hợp với bộ lọc.</td></tr>`;
            return;
        }

        let html = '';
        currentOrdersData.forEach((row, idx) => {
            const stt = (currentPage - 1) * currentLimit + idx + 1;
            const isAlert = Boolean(row.is_alert_5days);
            const daysLeft = row.days_to_due !== null ? parseInt(row.days_to_due) : null;
            const isTypeA = (row.phan_loai_don === 'A');
            const isCompleted = (row.tinh_trang_nhap_kho === 'Hoàn thành' || parseInt(row.con_thieu) <= 0);

            let trClass = '';
            if (isAlert) trClass = 'table-row-alert';
            else if (isCompleted && !isTypeA) trClass = 'table-row-completed';
            else if (isTypeA) trClass = 'table-row-converted';

            // Badge cảnh báo
            let alertBadge = '';
            if (isAlert) {
                if (daysLeft < 0) {
                    alertBadge = `<span class="badge bg-danger text-white pulse-badge d-inline-flex align-items-center gap-1" title="Quá hạn nhập kho"><span class="material-icons" style="font-size: 13px;">error</span> Quá hạn ${Math.abs(daysLeft)} ngày</span>`;
                } else {
                    alertBadge = `<span class="badge bg-danger text-white pulse-badge d-inline-flex align-items-center gap-1" title="Sắp đến hạn nhập kho"><span class="material-icons" style="font-size: 13px;">warning</span> Hạn: còn ${daysLeft} ngày</span>`;
                }
            } else if (isTypeA) {
                alertBadge = `<span class="badge bg-secondary-subtle text-secondary">Đã duyệt A</span>`;
            } else if (isCompleted) {
                alertBadge = `<span class="badge bg-success-subtle text-success fw-bold">Hoàn thành</span>`;
            } else if (row.tinh_trang_nhap_kho === 'Từ chối') {
                alertBadge = `<span class="badge bg-danger-subtle text-danger">Từ chối</span>`;
            } else {
                alertBadge = `<span class="badge bg-primary-subtle text-primary">Đang SX</span>`;
            }

            // Đùn badge
            let dunBadge = `<span class="text-muted small">Chưa XĐ</span>`;
            if (row.dun_xac_nhan === 'OK' || row.dun_xac_nhan === 'Xác nhận') {
                dunBadge = `<span class="badge bg-success-subtle text-success">OK</span>`;
            } else if (row.dun_xac_nhan === 'Từ chối') {
                dunBadge = `<span class="badge bg-danger-subtle text-danger">Từ chối</span>`;
            }

            // Cuộn badge
            let cuonBadge = `<span class="text-muted small">Chưa XĐ</span>`;
            if (row.cuon_xac_nhan === 'OK' || row.cuon_xac_nhan === 'Xác nhận') {
                cuonBadge = `<span class="badge bg-success-subtle text-success">OK</span>`;
            } else if (row.cuon_xac_nhan === 'K CÓ LÔ') {
                cuonBadge = `<span class="badge bg-warning-subtle text-warning">K CÓ LÔ</span>`;
            } else if (row.cuon_xac_nhan === 'Từ chối') {
                cuonBadge = `<span class="badge bg-danger-subtle text-danger">Từ chối</span>`;
            }

            const dateStorage = row.ngay_yc_nhap_kho || '<span class="text-muted">-</span>';
            const dateOrder = row.ky_han_giao_hang || '<span class="text-muted">-</span>';
            const dateBtoA = row.ngay_chuyen_b_to_a || '<span class="text-muted">-</span>';

            // Action buttons kiểm tra theo RBAC & góc nhìn bộ phận
            let actionButtons = '';

            // Nút chi tiết
            actionButtons += `<button class="btn btn-sm btn-outline-secondary p-1" onclick="openOrderDetailModal(${row.id})" title="Xem chi tiết & lịch sử"><span class="material-icons" style="font-size: 16px;">visibility</span></button> `;

            // Thao tác PC: Phải có quyền CAN_EDIT hoặc CAN_APPROVE_A
            if ((currentDept === 'all' || currentDept === 'pc')) {
                if (!isTypeA && isCompleted && CAN_APPROVE_A) {
                    actionButtons += `<button class="btn btn-sm btn-success p-1 px-2 fw-bold" onclick="openApproveModal(${row.id})" title="Xét duyệt chuyển B -> A (PC)"><span class="material-icons" style="font-size: 15px; vertical-align: -2px;">verified</span> Duyệt A</button> `;
                }
                if (CAN_EDIT) {
                    actionButtons += `<button class="btn btn-sm btn-outline-primary p-1" onclick="openEditOrderModal(${row.id})" title="Chỉnh sửa đơn hàng (PC)"><span class="material-icons" style="font-size: 16px;">edit</span></button> `;
                }
            }

            // Thao tác Đùn: Phải có quyền CAN_DUN_CONFIRM
            if ((currentDept === 'all' || currentDept === 'dun') && CAN_DUN_CONFIRM) {
                actionButtons += `<button class="btn btn-sm btn-outline-warning p-1" onclick="openDunConfirmModal(${row.id})" title="Xác nhận xưởng Đùn"><span class="material-icons" style="font-size: 16px;">precision_manufacturing</span></button> `;
            }

            // Thao tác Cuộn: Phải có quyền CAN_CUON_CONFIRM
            if ((currentDept === 'all' || currentDept === 'cuon') && CAN_CUON_CONFIRM) {
                actionButtons += `<button class="btn btn-sm btn-outline-info p-1" onclick="openCuonUpdateModal(${row.id})" title="Cập nhật thực tích xưởng Cuộn"><span class="material-icons" style="font-size: 16px;">rotate_right</span></button> `;
            }

            html += `
                <tr class="${trClass}">
                    <td style="text-align: center; color: var(--dx-text-muted);">${stt}</td>
                    <td>${alertBadge}</td>
                    <td>
                        <strong>${escapeHtml(row.ma_don_hang)}</strong>
                    </td>
                    <td><code>${escapeHtml(row.so_phieu_nhap || '-')}</code></td>
                    <td>
                        <span class="badge bg-light text-dark border font-monospace">${escapeHtml(row.ma_san_pham)}</span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">${Number(row.so_met_quy_cach || 0).toLocaleString()} m</td>
                    <td style="text-align: right; font-weight: 700; color: var(--dx-primary);">${Number(row.so_luong_dat || 0).toLocaleString()}</td>
                    <td style="text-align: right; font-weight: 600;">${Number(row.tong_met_can || 0).toLocaleString()} m</td>
                    <td style="text-align: center;">
                        <span class="badge ${isTypeA ? 'bg-secondary' : 'bg-primary'}">${escapeHtml(row.phan_loai_don)}</span>
                    </td>
                    <td><strong class="${isAlert ? 'text-danger' : ''}">${dateStorage}</strong></td>
                    <td>${dateOrder}</td>
                    <td>${dunBadge}</td>
                    <td>${cuonBadge}</td>
                    <td style="text-align: right; font-weight: 700; color: var(--dx-success);">${Number(row.cuon_da_sx || 0).toLocaleString()}</td>
                    <td style="text-align: right; font-weight: 600; color: var(--dx-success);">${Number(row.tong_met_da_sx || 0).toLocaleString()} m</td>
                    <td style="text-align: right; font-weight: 700; color: ${parseInt(row.con_thieu) > 0 ? 'var(--dx-danger)' : 'var(--dx-success)'};">${Number(row.con_thieu || 0).toLocaleString()}</td>
                    <td>${dateBtoA}</td>
                    <td class="text-truncate" style="max-width: 140px;" title="${escapeHtml(row.pc_note || '')}">${escapeHtml(row.pc_note || '-')}</td>
                    <td class="text-truncate" style="max-width: 140px;" title="${escapeHtml(row.san_xuat_note || '')}">${escapeHtml(row.san_xuat_note || '-')}</td>
                    <td style="text-align: center; position: sticky; right: 0; background: inherit; z-index: 6;">
                        <div class="d-inline-flex gap-1 align-items-center">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (err) {
        console.error('Lỗi trong renderTable:', err);
        tbody.innerHTML = `<tr><td colspan="20" class="text-center py-4 text-danger"><span class="material-icons d-block mb-2 text-danger" style="font-size: 32px;">error_outline</span>Lỗi hiển thị bảng dữ liệu: ${escapeHtml(err.message)}</td></tr>`;
    }
}

// 4. HIỂN THỊ PHÂN TRANG
function renderPagination(pg) {
    if (!pg) return;
    document.getElementById('totalRecordsText').textContent = `Tổng cộng: ${Number(pg.total_rows || 0).toLocaleString()} đơn`;
    document.getElementById('paginationInfo').textContent = `Trang ${pg.page} / ${pg.total_pages || 1} (Tổng: ${Number(pg.total_rows || 0).toLocaleString()} đơn)`;

    const controls = document.getElementById('paginationControls');
    let html = '';

    if (pg.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    html += `<li class="page-item ${pg.page <= 1 ? 'disabled' : ''}"><button class="page-link" onclick="loadOrders(${pg.page - 1})">&laquo;</button></li>`;

    const start = Math.max(1, pg.page - 2);
    const end = Math.min(pg.total_pages, pg.page + 2);

    for (let p = start; p <= end; p++) {
        html += `<li class="page-item ${p === pg.page ? 'active' : ''}"><button class="page-link" onclick="loadOrders(${p})">${p}</button></li>`;
    }

    html += `<li class="page-item ${pg.page >= pg.total_pages ? 'disabled' : ''}"><button class="page-link" onclick="loadOrders(${pg.page + 1})">&raquo;</button></li>`;

    controls.innerHTML = html;
}

// 5. TỰ ĐỘNG TÍNH TOÁN KHI NHẬP FORM
function autoDetectMeters() {
    const code = document.getElementById('inputMaSanPham').value.trim();
    const match = code.match(/-(\d+)/);
    if (match && match[1]) {
        document.getElementById('inputSoMetQuyCach').value = parseInt(match[1]);
    }
    recalcTotalMeters();
}

function recalcTotalMeters() {
    const spec = parseInt(document.getElementById('inputSoMetQuyCach').value || '0');
    const qty = parseInt(document.getElementById('inputSoLuongDat').value || '0');
    const total = spec * qty;
    document.getElementById('previewTongMetCan').textContent = Number(total).toLocaleString() + ' mét';
}

function autoSuggestDueStorage() {
    const orderDueDate = document.getElementById('inputKyHanGiaoHang').value;
    if (orderDueDate && !document.getElementById('inputNgayYcNhapKho').value) {
        const d = new Date(orderDueDate);
        d.setDate(d.getDate() - 2);
        document.getElementById('inputNgayYcNhapKho').value = d.toISOString().split('T')[0];
    }
}

// 6. THAO TÁC MODAL PC (TẠO / SỬA)
function openCreateOrderModal() {
    if (!CAN_CREATE) {
        alert('Bạn không có quyền khởi tạo đơn hàng mới!');
        return;
    }
    document.getElementById('formOrderManual').reset();
    document.getElementById('editOrderId').value = '';
    document.getElementById('orderModalHeading').textContent = 'Khởi Tạo Đơn Hàng Mới (PC)';
    document.getElementById('inputMaDonHang').readOnly = false;
    document.getElementById('previewTongMetCan').textContent = '0 mét';
    new bootstrap.Modal(document.getElementById('modalOrderManual')).show();
}

function openEditOrderModal(id) {
    if (!CAN_EDIT) {
        alert('Bạn không có quyền chỉnh sửa đơn hàng!');
        return;
    }
    fetch(`api/don_b_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('editOrderId').value = d.id;
                document.getElementById('inputMaDonHang').value = d.ma_don_hang;
                document.getElementById('inputMaDonHang').readOnly = true;
                document.getElementById('inputSoPhieuNhap').value = d.so_phieu_nhap || '';
                document.getElementById('inputMaSanPham').value = d.ma_san_pham;
                document.getElementById('inputSoMetQuyCach').value = d.so_met_quy_cach;
                document.getElementById('inputSoLuongDat').value = d.so_luong_dat;
                document.getElementById('inputPhanLoaiDon').value = d.phan_loai_don;
                document.getElementById('inputNgayNhanDon').value = d.ngay_nhan_don || '';
                document.getElementById('inputKyHanGiaoHang').value = d.ky_han_giao_hang || '';
                document.getElementById('inputNgayYcNhapKho').value = d.ngay_yc_nhap_kho || '';
                document.getElementById('inputNgayDuKienXuat').value = d.ngay_du_kien_xuat || '';
                document.getElementById('inputPhuongThuc').value = d.phuong_thuc_van_chuyen || 'SEA';
                document.getElementById('inputMaKhachHang').value = d.ma_khach_hang || '';
                document.getElementById('inputTenKhachHang').value = d.ten_khach_hang || '';
                document.getElementById('inputPcNote').value = d.pc_note || '';

                recalcTotalMeters();
                document.getElementById('orderModalHeading').textContent = `Chỉnh Sửa Đơn Hàng: ${d.ma_don_hang}`;
                new bootstrap.Modal(document.getElementById('modalOrderManual')).show();
            } else {
                alert(res.message || 'Lỗi lấy thông tin đơn hàng');
            }
        });
}

function submitOrderManual(e) {
    e.preventDefault();
    const form = document.getElementById('formOrderManual');
    const formData = new FormData(form);
    formData.append('action', 'save_manual');

    const btn = document.getElementById('btnSubmitOrder');
    btn.disabled = true;

    fetch('api/don_b_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalOrderManual')).hide();
                loadStats();
                loadOrders(currentPage);
                alert(res.message || 'Lưu thành công!');
            } else {
                alert(res.message || 'Lỗi khi lưu đơn hàng');
            }
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Lỗi kết nối khi lưu đơn hàng.');
        });
}

// 7. THAO TÁC MODAL ĐÙN
function openDunConfirmModal(id) {
    if (!CAN_DUN_CONFIRM) {
        alert('Bạn không có quyền xác nhận xưởng Đùn!');
        return;
    }
    fetch(`api/don_b_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('dunConfirmId').value = d.id;
                document.getElementById('dunModalOrderCode').textContent = `${d.ma_don_hang} (Tháp: ${d.so_phieu_nhap || 'N/A'})`;
                document.getElementById('dunModalProductInfo').textContent = `Sản phẩm: ${d.ma_san_pham} | Số lượng đặt: ${Number(d.so_luong_dat).toLocaleString()} cuộn (${Number(d.tong_met_can).toLocaleString()} m)`;
                document.getElementById('dunModalDueDate').textContent = `Kỳ hạn nhập kho: ${d.ngay_yc_nhap_kho || 'Chưa xác định'}`;
                document.getElementById('dunConfirmStatus').value = d.dun_xac_nhan || 'OK';
                document.getElementById('dunNgayDuKienXuat').value = d.ngay_du_kien_xuat || '';
                document.getElementById('dunSanXuatNote').value = '';
                new bootstrap.Modal(document.getElementById('modalDunConfirm')).show();
            }
        });
}

function submitDunConfirm(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formDunConfirm'));
    formData.append('action', 'dun_confirm');

    fetch('api/don_b_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalDunConfirm')).hide();
                loadStats();
                loadOrders(currentPage);
                alert(res.message || 'Đã lưu xác nhận Đùn!');
            } else {
                alert(res.message || 'Lỗi xác nhận Đùn');
            }
        });
}

// 8. THAO TÁC MODAL CUỘN
function openCuonUpdateModal(id) {
    if (!CAN_CUON_CONFIRM) {
        alert('Bạn không có quyền cập nhật thực tích xưởng Cuộn!');
        return;
    }
    fetch(`api/don_b_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('cuonUpdateId').value = d.id;
                document.getElementById('cuonOrderQty').value = d.so_luong_dat;
                document.getElementById('cuonOrderSpec').value = d.so_met_quy_cach;
                document.getElementById('cuonModalOrderCode').textContent = `${d.ma_don_hang}`;
                document.getElementById('cuonModalSpecTag').textContent = `${Number(d.so_met_quy_cach).toLocaleString()} m/cuộn`;
                document.getElementById('cuonModalProductInfo').textContent = `Sản phẩm: ${d.ma_san_pham} | Tháp No: ${d.so_phieu_nhap || 'N/A'}`;
                document.getElementById('cuonModalQtyOrder').textContent = Number(d.so_luong_dat).toLocaleString();
                document.getElementById('cuonModalTotalMetersReq').textContent = Number(d.tong_met_can).toLocaleString();

                document.getElementById('cuonConfirmStatus').value = d.cuon_xac_nhan || 'OK';
                document.getElementById('cuonDaSxInput').value = d.cuon_da_sx || 0;
                document.getElementById('cuonBobinInput').value = d.tinh_trang_bobin || '';
                document.getElementById('cuonNoteInput').value = '';

                calcCuonShortage();
                new bootstrap.Modal(document.getElementById('modalCuonUpdate')).show();
            }
        });
}

function calcCuonShortage() {
    const qty = parseInt(document.getElementById('cuonOrderQty').value || '0');
    const spec = parseInt(document.getElementById('cuonOrderSpec').value || '0');
    const actual = parseInt(document.getElementById('cuonDaSxInput').value || '0');

    const remaining = Math.max(0, qty - actual);
    const producedMeters = spec * actual;
    const percent = qty > 0 ? Math.min(100, Math.round((actual / qty) * 100)) : 0;

    const remainingEl = document.getElementById('cuonCalcRemaining');
    remainingEl.textContent = Number(remaining).toLocaleString() + ' cuộn';
    remainingEl.style.color = (remaining <= 0) ? 'var(--dx-success)' : 'var(--dx-danger)';

    document.getElementById('cuonCalcProducedMeters').textContent = Number(producedMeters).toLocaleString() + ' m';
    document.getElementById('cuonProgress').style.width = percent + '%';
    document.getElementById('cuonPercentText').textContent = `${percent}% hoàn thành (${actual}/${qty} cuộn)`;
}

function submitCuonUpdate(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formCuonUpdate'));
    formData.append('action', 'cuon_update');

    fetch('api/don_b_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCuonUpdate')).hide();
                loadStats();
                loadOrders(currentPage);
                alert(res.message || 'Đã cập nhật thực tích thành công!');
            } else {
                alert(res.message || 'Lỗi cập nhật thực tích');
            }
        });
}

// 9. THAO TÁC MODAL XÉT DUYỆT B -> A (PC)
function openApproveModal(id) {
    if (!CAN_APPROVE_A) {
        alert('Bạn không có quyền xét duyệt chuyển Đơn B sang Đơn A!');
        return;
    }
    fetch(`api/don_b_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('approveOrderId').value = d.id;
                document.getElementById('approveModalOrderCode').textContent = `${d.ma_don_hang} (Tháp: ${d.so_phieu_nhap || 'N/A'})`;
                document.getElementById('approveModalProductInfo').textContent = `Sản phẩm: ${d.ma_san_pham} | Thực tích: ${d.cuon_da_sx} / ${d.so_luong_dat} cuộn (${d.tong_met_da_sx} / ${d.tong_met_can} m)`;
                document.getElementById('approveNgayChuyen').value = new Date().toISOString().split('T')[0];
                document.getElementById('approvePcNote').value = '';
                new bootstrap.Modal(document.getElementById('modalApproveBToA')).show();
            }
        });
}

function submitApproveBToA(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formApproveBToA'));
    formData.append('action', 'approve_b_to_a');

    fetch('api/don_b_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalApproveBToA')).hide();
                loadStats();
                loadOrders(currentPage);
                alert(res.message || 'Đã duyệt chuyển Đơn A thành công!');
            } else {
                alert(res.message || 'Lỗi xét duyệt Đơn A');
            }
        });
}

// 10. THAO TÁC IMPORT EXCEL
function openExcelImportModal() {
    if (!CAN_CREATE && !CAN_EDIT) {
        alert('Bạn không có quyền Import file Excel!');
        return;
    }
    document.getElementById('formExcelImport').reset();
    document.getElementById('importProgress').classList.add('d-none');
    document.getElementById('importResult').classList.add('d-none');
    document.getElementById('btnSubmitImport').disabled = false;
    new bootstrap.Modal(document.getElementById('modalExcelImport')).show();
}

function submitExcelImport(e) {
    e.preventDefault();
    const form = document.getElementById('formExcelImport');
    const formData = new FormData(form);
    formData.append('action', 'import_excel');

    const btn      = document.getElementById('btnSubmitImport');
    const progress = document.getElementById('importProgress');
    const result   = document.getElementById('importResult');

    btn.disabled = true;
    progress.classList.remove('d-none');
    result.classList.add('d-none');

    fetch('api/don_b_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            progress.classList.add('d-none');
            result.classList.remove('d-none');

            if (res.success) {
                result.className = 'alert alert-success mt-3 small';
                result.innerHTML = `<strong>Thành công!</strong> ${res.message}`;
                loadStats();
                loadOrders(1);
            } else {
                result.className = 'alert alert-danger mt-3 small';
                result.innerHTML = `<strong>Lỗi:</strong> ${res.message}`;
            }
        })
        .catch(err => {
            btn.disabled = false;
            progress.classList.add('d-none');
            result.classList.remove('d-none');
            result.className = 'alert alert-danger mt-3 small';
            result.innerHTML = `<strong>Lỗi kết nối mạng:</strong> Không thể tải lên file.`;
        });
}

// 11. XUẤT EXCEL
function exportExcelFile() {
    if (!CAN_EXPORT) {
        alert('Bạn không có quyền xuất dữ liệu Excel!');
        return;
    }
    const search = encodeURIComponent(document.getElementById('searchInput').value.trim());
    window.location.href = `api/don_b_api.php?action=export_excel&tab=${currentTab}&search=${search}`;
}

// 12. XEM CHI TIẾT & AUDIT TRAIL
function openOrderDetailModal(id) {
    const grid = document.getElementById('orderDetailGrid');
    const hist = document.getElementById('orderHistoryList');
    grid.innerHTML = '<div class="text-muted p-2">Đang tải thông tin...</div>';
    hist.innerHTML = '<div class="text-muted p-2">Đang tải nhật ký...</div>';

    fetch(`api/don_b_api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                const hList = res.history || [];

                grid.innerHTML = `
                    <div class="detail-item"><div class="label">Mã Đơn Hàng (オーダー)</div><div class="value text-primary">${escapeHtml(d.ma_don_hang)}</div></div>
                    <div class="detail-item"><div class="label">Mã Sản Phẩm (品番)</div><div class="value">${escapeHtml(d.ma_san_pham)}</div></div>
                    <div class="detail-item"><div class="label">Tháp No (手配No.)</div><div class="value">${escapeHtml(d.so_phieu_nhap || '-')}</div></div>
                    <div class="detail-item"><div class="label">Phân Loại (A/B)</div><div class="value"><span class="badge ${d.phan_loai_don === 'A' ? 'bg-secondary' : 'bg-primary'}">${d.phan_loai_don}</span></div></div>
                    <div class="detail-item"><div class="label">Quy Cách Số Mét</div><div class="value">${Number(d.so_met_quy_cach).toLocaleString()} m/cuộn</div></div>
                    <div class="detail-item"><div class="label">Số Lượng Đặt (cuộn)</div><div class="value text-primary">${Number(d.so_luong_dat).toLocaleString()} cuộn</div></div>
                    <div class="detail-item"><div class="label">Tổng Mét Cần Sản Xuất</div><div class="value text-primary">${Number(d.tong_met_can).toLocaleString()} m</div></div>
                    <div class="detail-item"><div class="label">Cuộn Đã SX (Thực Tích)</div><div class="value text-success">${Number(d.cuon_da_sx).toLocaleString()} cuộn</div></div>
                    <div class="detail-item"><div class="label">Tổng Mét Đã SX</div><div class="value text-success">${Number(d.tong_met_da_sx).toLocaleString()} m</div></div>
                    <div class="detail-item"><div class="label">Còn Thiếu</div><div class="value ${parseInt(d.con_thieu) > 0 ? 'text-danger' : 'text-success'}">${Number(d.con_thieu).toLocaleString()} cuộn</div></div>
                    <div class="detail-item"><div class="label">Kỳ Hạn Nhập Kho</div><div class="value text-danger">${d.ngay_yc_nhap_kho || 'Chưa xác định'}</div></div>
                    <div class="detail-item"><div class="label">Kỳ Hạn Đơn Hàng</div><div class="value">${d.ky_han_giao_hang || '-'}</div></div>
                    <div class="detail-item"><div class="label">Ngày Nhận Đơn</div><div class="value">${d.ngay_nhan_don || '-'}</div></div>
                    <div class="detail-item"><div class="label">Khách Hàng (Mã & Tên)</div><div class="value">${escapeHtml(d.ma_khach_hang || '-')} &bull; ${escapeHtml(d.ten_khach_hang || '-')}</div></div>
                    <div class="detail-item"><div class="label">Vận Chuyển</div><div class="value">${escapeHtml(d.phuong_thuc_van_chuyen || 'SEA')}</div></div>
                    <div class="detail-item"><div class="label">Đùn Xác Nhận</div><div class="value">${escapeHtml(d.dun_xac_nhan || 'Chưa xác định')}</div></div>
                    <div class="detail-item"><div class="label">Cuộn Xác Nhận</div><div class="value">${escapeHtml(d.cuon_xac_nhan || 'Chưa xác định')}</div></div>
                    <div class="detail-item"><div class="label">Tình Trạng Nhập Kho</div><div class="value">${escapeHtml(d.tinh_trang_nhap_kho || 'Đang thực hiện')}</div></div>
                    <div class="detail-item"><div class="label">Ngày Dự Kiến Xuất</div><div class="value">${d.ngay_du_kien_xuat || '-'}</div></div>
                    <div class="detail-item"><div class="label">Ngày Xuất Thực Tế</div><div class="value">${d.ngay_xuat_thuc_te || '-'}</div></div>
                    <div class="detail-item"><div class="label">Ngày Chuyển B &rarr; A</div><div class="value text-success">${d.ngay_chuyen_b_to_a || '-'}</div></div>
                    <div class="detail-item"><div class="label">Tình Trạng Bobin</div><div class="value">${escapeHtml(d.tinh_trang_bobin || '-')}</div></div>
                    <div class="detail-item"><div class="label">CTSX Status</div><div class="value">${escapeHtml(d.ctsx_status || '-')}</div></div>
                    <div class="detail-item"><div class="label">Thời Gian Cập Nhật Gần Nhất</div><div class="value text-muted">${d.ngay_cap_nhat || '-'}</div></div>
                    <div class="detail-item" style="grid-column: 1 / -1;"><div class="label">PC Note</div><div class="value">${escapeHtml(d.pc_note || 'Không có')}</div></div>
                    <div class="detail-item" style="grid-column: 1 / -1;"><div class="label">Sản Xuất Note</div><div class="value">${escapeHtml(d.san_xuat_note || 'Không có')}</div></div>
                `;

                if (hList.length === 0) {
                    hist.innerHTML = '<div class="text-muted text-center p-3">Chưa có bản ghi nhật ký thao tác nào.</div>';
                } else {
                    let hHtml = '<div class="list-group list-group-flush">';
                    hList.forEach(h => {
                        let deptBadge = 'bg-secondary';
                        if (h.department === 'PC') deptBadge = 'bg-primary';
                        else if (h.department === 'Đùn') deptBadge = 'bg-warning text-dark';
                        else if (h.department === 'Cuộn') deptBadge = 'bg-info text-dark';

                        hHtml += `
                            <div class="list-group-item bg-transparent px-2 py-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="badge ${deptBadge}">${escapeHtml(h.department)}</span>
                                        <strong>${escapeHtml(h.action)}</strong>
                                        <span class="text-muted small">&bull; ${escapeHtml(h.user_name)}</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 11px;">${h.created_at}</span>
                                </div>
                                <div class="text-secondary ps-1">${escapeHtml(h.content)}</div>
                            </div>
                        `;
                    });
                    hHtml += '</div>';
                    hist.innerHTML = hHtml;
                }

                new bootstrap.Modal(document.getElementById('modalOrderDetail')).show();
            } else {
                alert(res.message || 'Lỗi lấy chi tiết đơn hàng');
            }
        });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
