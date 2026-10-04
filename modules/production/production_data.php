<style>
/* CSS RIÊNG CỦA MODULE DỮ LIỆU THỰC TÍCH SẢN XUẤT */
.act-table-wrapper { 
    overflow-x: auto; 
    overflow-y: auto; 
    max-height: calc(100vh - 280px); 
    border: 1px solid var(--dx-border); 
    border-radius: var(--dx-radius-sm); 
    background: var(--dx-bg-card, #ffffff);
}

.act-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; white-space: nowrap; text-align: left; }
.act-table th { 
    background-color: var(--dx-bg-subtle, #f8fafc); 
    color: var(--dx-text-muted, #475569); 
    font-weight: 700; 
    padding: 9px 12px; 
    border-bottom: 1px solid var(--dx-border); 
    border-right: 1px solid var(--dx-border);
    position: sticky; 
    top: 0; 
    z-index: 10; 
}
.act-table td { 
    padding: 8px 12px; 
    border-bottom: 1px solid var(--dx-border); 
    border-right: 1px solid var(--dx-border);
    color: var(--dx-text-main, #334155); 
}
.act-table tr:hover td { background-color: var(--dx-bg-hover, #f8fafc); }

/* Cố định cột Checkbox (Cột 1) khi cuộn ngang */
.act-table th:nth-child(1),
.act-table td:nth-child(1) {
    position: sticky;
    left: 0;
    background-color: var(--dx-bg-card, #ffffff);
    z-index: 12;
    border-right: 1px solid var(--dx-border);
}

/* Cố định cột Hành động (Cột 2) khi cuộn ngang */
.act-table th:nth-child(2),
.act-table td:nth-child(2) {
    position: sticky;
    left: 40px;
    background-color: var(--dx-bg-card, #ffffff);
    z-index: 12;
    border-right: 2px solid var(--dx-border-strong);
}

.act-table th:nth-child(1),
.act-table th:nth-child(2) {
    z-index: 20;
    background-color: var(--dx-bg-subtle, #f8fafc);
}

/* Modal Design */
.act-modal { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 20px; }
.act-modal-content { background: var(--dx-bg-card, #ffffff); color: var(--dx-text-main); border: 1px solid var(--dx-border); border-radius: var(--dx-radius-md); width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: var(--dx-shadow-lg); overflow: hidden; }

/* Custom Inputs & Buttons cho form Modal */
.act-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-sm);
    background: var(--dx-bg-subtle);
    color: var(--dx-text-main);
    font-size: 12px;
}
.act-input:focus {
    background: var(--dx-bg-card);
    border-color: var(--dx-primary);
    outline: none;
    box-shadow: 0 0 0 2px var(--dx-primary-light);
}
.act-subtitle {
    font-size: 11px;
    font-weight: 600;
    color: var(--dx-text-muted);
    margin-bottom: 4px;
    display: block;
}
.act-card {
    background: var(--dx-bg-card);
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-md);
    box-shadow: var(--dx-shadow-sm);
}
.act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: var(--dx-radius-sm);
    border: 1px solid transparent;
    cursor: pointer;
    transition: all var(--dx-transition-fast);
}
.act-btn-primary { background: var(--dx-primary); color: #fff; }
.act-btn-primary:hover { background: var(--dx-primary-hover); color: #fff; }
.act-btn-secondary { background: var(--dx-bg-subtle); color: var(--dx-text-main); border-color: var(--dx-border); }
.act-btn-secondary:hover { background: var(--dx-bg-hover); color: var(--dx-text-main); }
.act-btn-danger { background: var(--dx-danger); color: #fff; }
.act-btn-danger:hover { opacity: 0.9; color: #fff; }
.act-btn-success { background: var(--dx-success); color: #fff; }
.act-btn-success:hover { opacity: 0.9; color: #fff; }
</style>

<div class="app-page-wrapper">

    <!-- BAR TIÊU ĐỀ VÀ THANH LỌC DỮ LIỆU -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">fact_check</span>
                Quản Lý Thực Tích Sản Xuất Chi Tiết (押出)
            </h1>
            <p class="app-page-subtitle">Tra cứu, lọc, chỉnh sửa, xóa hàng loạt và xuất dữ liệu thực tích sang Excel</p>
        </div>

        <div class="app-page-actions">
            <?php if (hasPermission('production.data')): ?>
            <button onclick="openVnsystemSyncModal()" class="app-btn" style="background:#0284c7; color:#fff; font-weight:600; box-shadow: 0 1px 3px rgba(2,132,199,0.3);">
                <span class="material-icons">sync</span> Đồng bộ Extrusion Report
            </button>
            <button onclick="openUploadModal()" class="app-btn app-btn-success">
                <span class="material-icons">file_upload</span> Upload Excel Thực Tích
            </button>
            <?php endif; ?>
            <button onclick="openVnsystemHistoryModal()" class="app-btn app-btn-secondary" title="Xem lịch sử các đợt đồng bộ Extrusion Report">
                <span class="material-icons">history</span> Lịch sử đồng bộ
            </button>
            <button onclick="exportExcel()" class="app-btn app-btn-primary">
                <span class="material-icons">file_download</span> Download Excel
            </button>
        </div>
    </div>

    <!-- BANNER TRẠNG THÁI ĐỒNG BỘ GẦN NHẤT TỪ VNSYSTEM -->
    <div id="vnsystemSyncBanner" class="act-card mb-2" style="padding: 10px 16px; border-left: 4px solid var(--dx-primary, #0284c7); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 12px;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <span style="color:var(--dx-text-muted);"><span class="material-icons" style="font-size:15px; vertical-align:middle;">schedule</span> Lần cuối đồng bộ:</span>
                <strong id="bannerLastSyncTime" style="color:var(--dx-text-main); margin-left: 4px;">--</strong>
            </div>
            <div class="d-flex align-items-center gap-1">
                <span style="color:var(--dx-text-muted);">Trạng thái:</span>
                <span id="bannerLastSyncStatus" style="padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px; background: var(--dx-bg-subtle); color: var(--dx-text-muted);">Đang kiểm tra</span>
            </div>
            <div>
                <span style="color:var(--dx-text-muted);">Số bản ghi:</span>
                <strong id="bannerLastSyncCount" style="color:var(--dx-text-main); margin-left: 4px;">0</strong>
            </div>
            <div>
                <span style="color:var(--dx-text-muted);">Người thực hiện:</span>
                <span id="bannerLastSyncUser" style="color:var(--dx-text-main); margin-left: 4px;">-</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="openVnsystemSyncModal()" class="act-btn act-btn-primary" style="padding: 3px 8px; font-size: 11px;">
                <span class="material-icons" style="font-size: 13px;">sync</span> Đồng bộ ngay
            </button>
            <button onclick="openVnsystemHistoryModal()" class="act-btn act-btn-secondary" style="padding: 3px 8px; font-size: 11px;">
                <span class="material-icons" style="font-size: 13px;">history</span> Lịch sử
            </button>
        </div>
    </div>

    <!-- BỘ LỌC CHI TIẾT -->
    <div class="app-filter-card">
        <div class="d-flex align-items-center gap-2 flex-wrap w-100">
            <select id="filterMode" class="app-form-select" onchange="toggleFilterMode()">
                <option value="month">Theo Tháng</option>
                <option value="range">Theo Khoảng Ngày</option>
            </select>

            <div id="monthFilterContainer">
                <input type="month" id="filterMonth" value="<?= date('Y-m') ?>" class="app-form-control" onchange="loadActualData(1)">
            </div>

            <div id="rangeFilterContainer" style="display:none;" class="d-flex align-items-center gap-1">
                <input type="date" id="filterStartDate" class="app-form-control" onchange="loadActualData(1)">
                <span class="text-muted">-</span>
                <input type="date" id="filterEndDate" class="app-form-control" onchange="loadActualData(1)">
            </div>

            <select id="filterPipeSize" class="app-form-select" onchange="loadActualData(1)">
                <option value="ALL">Tất cả Size ống</option>
                <option value="TU0425">TU0425</option><option value="TU0604">TU0604</option>
                <option value="TU0805">TU0805</option><option value="TU1065">TU1065</option>
                <option value="TU1208">TU1208</option><option value="TU1610">TU1610</option>
                <option value="TIUB01">TIUB01</option><option value="TIUB05">TIUB05</option>
                <option value="TIUB07">TIUB07</option><option value="TIUB11">TIUB11</option>
                <option value="TIUB13">TIUB13</option>
            </select>

            <div class="app-input-with-icon">
                <span class="material-icons">search</span>
                <input type="text" id="filterSearch" placeholder="Tìm NV, Mã CTSX, Mã SP..." class="app-form-control" style="width: 240px;" onkeyup="delaySearch()">
            </div>

            <button id="btnDeleteSelected" onclick="deleteSelectedRows()" class="app-btn app-btn-danger ms-auto" style="display:none;">
                <span class="material-icons">delete</span> Xóa Đã Chọn (<span id="selectedCount">0</span>)
            </button>
        </div>
    </div>

    <!-- BẢNG HIỂN THỊ DỮ LIỆU THỰC TÍCH VỚI THANH CUỘN DỌC & NGANG TỰ ĐỘNG -->
    <div class="act-card" style="padding: 12px;">
        <div class="act-table-wrapper">
            <table class="act-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th style="width: 110px; text-align: center;">Hành động</th>
                        <th>ID</th>
                        <th>Nguồn</th>
                        <th>Ngày SX</th>
                        <th>Mã NV</th>
                        <th>Tên Nhân Viên</th>
                        <th>Ca</th>
                        <th>Mã CTSX</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Size Ống</th>
                        <th>Mã Thiết Bị</th>
                        <th>Thành Phẩm (M)</th>
                        <th>KL TP (KG)</th>
                        <th>KL NG (KG)</th>
                        <th>KL Cứng (KG)</th>
                        <th>Tổng T/g Dừng (h)</th>
                        <th>Thời Gian Chạy (h)</th>
                        <th>Hiệu Suất (%)</th>
                    </tr>
                </thead>
                <tbody id="actualTableBody">
                    <!-- Dữ liệu render động tại đây -->
                </tbody>
            </table>
        </div>

        <!-- PHÂN TRANG CHUẨN HÓA -->
        <div class="app-card-footer mt-2" style="background: transparent; border-top: 1px solid var(--dx-border); padding: 10px 4px 4px 4px;">
            <div id="productionDataPagination" class="w-100"></div>
        </div>
    </div>

    <!-- MODAL UPLOAD FILE EXCEL -->
    <div id="uploadModal" class="act-modal" style="display:none;">
        <div class="act-modal-content" style="max-width: 450px;">
            <div style="padding: 15px 20px; border-bottom: 1px solid var(--dx-border); display: flex; justify-content: space-between; align-items: center; background: var(--dx-bg-subtle);">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--dx-text-main);">📥 Upload Dữ Liệu Thực Tích Excel</h3>
                <button onclick="closeUploadModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:var(--dx-text-muted);">&times;</button>
            </div>
            <div style="padding: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: var(--dx-text-muted); display: block; margin-bottom: 8px;">Chọn file báo cáo (.xlsx, .xls):</label>
                <input type="file" id="excelFileInput" accept=".xlsx, .xls" class="act-input" style="width: 100%;">
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid var(--dx-border); text-align: right; background: var(--dx-bg-subtle);">
                <button onclick="closeUploadModal()" class="act-btn act-btn-secondary">Hủy</button>
                <button id="btnSubmitUpload" onclick="handleUploadExcel()" class="act-btn act-btn-success">Bắt Đầu Upload</button>
            </div>
        </div>
    </div>

    <!-- MODAL CHỈNH SỬA DÒNG THỰC TÍCH -->
    <div id="editModal" class="act-modal" style="display:none;">
        <div class="act-modal-content">
            <div style="padding: 15px 20px; border-bottom: 1px solid var(--dx-border); display: flex; justify-content: space-between; align-items: center; background: var(--dx-bg-subtle);">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--dx-text-main);">✏️ Chỉnh Sửa Thực Tích Sản Xuất</h3>
                <button onclick="closeEditModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:var(--dx-text-muted);">&times;</button>
            </div>
            <div style="padding: 20px; overflow-y: auto; max-height: calc(90vh - 130px);">
                <form id="editActualForm" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <input type="hidden" id="edit_id">
                    <div>
                        <label class="act-subtitle">Ngày SX:</label>
                        <input type="date" id="edit_production_date" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Mã Nhân Viên:</label>
                        <input type="text" id="edit_employee_code" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Tên Nhân Viên:</label>
                        <input type="text" id="edit_employee_name" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Ca làm việc:</label>
                        <input type="text" id="edit_shift" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Mã CTSX:</label>
                        <input type="text" id="edit_mfg_order_code" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Mã Sản Phẩm:</label>
                        <input type="text" id="edit_product_code" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Mã Thiết Bị:</label>
                        <input type="text" id="edit_device_code" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Thành Phẩm (Mètres):</label>
                        <input type="number" step="any" id="edit_finished_qty_m" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">KL Thành Phẩm (KG):</label>
                        <input type="number" step="any" id="edit_finished_qty_kg" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">KL Phế NG (KG):</label>
                        <input type="number" step="any" id="edit_ng_qty_kg" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Tổng T/G Dừng (Giờ):</label>
                        <input type="number" step="any" id="edit_total_downtime" class="act-input" style="width:100%;">
                    </div>
                    <div>
                        <label class="act-subtitle">Hiệu Suất Máy (%):</label>
                        <input type="number" step="any" id="edit_machine_efficiency" class="act-input" style="width:100%;">
                    </div>
                </form>
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid var(--dx-border); text-align: right; background: var(--dx-bg-subtle);">
                <button onclick="closeEditModal()" class="act-btn act-btn-secondary">Hủy</button>
                <button onclick="saveEditRow()" class="act-btn act-btn-primary">Lưu Cập Nhật</button>
            </div>
        </div>
    </div>

    <!-- MODAL ĐỒNG BỘ DỮ LIỆU TỪ VNSYSTEM -->
    <div id="vnsystemSyncModal" class="act-modal" style="display:none;">
        <div class="act-modal-content" style="max-width: 540px;">
            <div style="padding: 15px 20px; border-bottom: 1px solid var(--dx-border); display: flex; justify-content: space-between; align-items: center; background: var(--dx-bg-subtle);">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--dx-text-main); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:#0284c7;">sync</span> Đồng Bộ Extrusion Report (VNSYSTEM)
                </h3>
                <button onclick="closeVnsystemSyncModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:var(--dx-text-muted);">&times;</button>
            </div>
            
            <div style="padding: 20px; max-height: calc(90vh - 140px); overflow-y: auto;">
                <div style="background: var(--dx-primary-bg, #eff6ff); border: 1px solid var(--dx-primary-border, #bfdbfe); border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: var(--dx-primary-text, #1e40af); line-height: 1.5;">
                    <span class="material-icons" style="font-size:16px; vertical-align:text-bottom;">info</span>
                    Hệ thống sẽ kết nối tự động đến API VNSYSTEM để tải báo cáo <strong>Extrusion Report</strong> và thực hiện <strong>UPSERT</strong> (chống trùng lặp) vào thực tích sản xuất.
                </div>

                <!-- CHỌN THỜI GIAN ĐỒNG BỘ -->
                <div style="margin-bottom: 16px;">
                    <label class="act-subtitle" style="font-size: 12px; font-weight: 700; margin-bottom: 6px;">Khoảng thời gian đồng bộ:</label>
                    <div style="display: flex; gap: 16px; margin-bottom: 8px;">
                        <label style="font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="syncPeriodType" value="month" checked onchange="toggleSyncPeriodType('month')">
                            Theo Tháng (YYYY-MM)
                        </label>
                        <label style="font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="syncPeriodType" value="range" onchange="toggleSyncPeriodType('range')">
                            Theo Khoảng Ngày
                        </label>
                    </div>

                    <div id="syncMonthContainer">
                        <input type="month" id="syncMonthInput" class="act-input" value="<?= date('Y-m') ?>">
                    </div>

                    <div id="syncRangeContainer" style="display: none; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="act-subtitle">Từ ngày:</label>
                            <input type="date" id="syncStartDateInput" class="act-input" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div>
                            <label class="act-subtitle">Đến ngày:</label>
                            <input type="date" id="syncEndDateInput" class="act-input" value="<?= date('Y-m-t') ?>">
                        </div>
                    </div>
                </div>

                <!-- TÀI KHOẢN VNSYSTEM -->
                <div style="margin-bottom: 16px; border-top: 1px solid var(--dx-border); padding-top: 12px;">
                    <label class="act-subtitle" style="font-size: 12px; font-weight: 700; margin-bottom: 6px;">Thông tin xác thực VNSYSTEM:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                        <div>
                            <label class="act-subtitle">Tài khoản (Username):</label>
                            <input type="text" id="syncUsernameInput" placeholder="Ví dụ: 1920862..." class="act-input">
                        </div>
                        <div>
                            <label class="act-subtitle">Mật khẩu (Password):</label>
                            <input type="password" id="syncPasswordInput" placeholder="Nhập mật khẩu..." class="act-input">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-size: 11px; color: var(--dx-text-muted); display: flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" id="syncSaveCredentials" checked> Lưu thông tin cho các lần sau
                        </label>
                        <label style="font-size: 11px; color: var(--dx-primary, #0284c7); display: flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" id="syncUseSample"> <i>Chạy thử từ file mẫu nội bộ</i>
                        </label>
                    </div>
                </div>

                <!-- TRẠNG THÁI TIẾN TRÌNH LIVE -->
                <div id="syncProgressBox" style="display: none; background: var(--dx-bg-subtle); border-radius: 6px; padding: 12px; border: 1px dashed var(--dx-border); text-align: center;">
                    <div style="display:inline-block; width: 18px; height: 18px; border: 2px solid #0284c7; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 6px;"></div>
                    <div id="syncProgressText" style="font-size: 12px; font-weight: 600; color: var(--dx-text-main);">Đang kết nối VNSYSTEM...</div>
                    <div style="font-size: 11px; color: var(--dx-text-muted); margin-top: 2px;">Vui lòng không đóng trình duyệt trong khi đang đồng bộ</div>
                </div>
            </div>

            <div style="padding: 12px 20px; border-top: 1px solid var(--dx-border); display: flex; justify-content: space-between; align-items: center; background: var(--dx-bg-subtle);">
                <button onclick="closeVnsystemSyncModal()" class="act-btn act-btn-secondary">Đóng</button>
                <button id="btnExecuteVnSync" onclick="handleVnsystemSync()" class="act-btn" style="background:#0284c7; color:#fff; font-weight:700;">
                    <span class="material-icons" style="font-size: 14px;">cloud_download</span> Bắt Đầu Đồng Bộ
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL LỊCH SỬ ĐỒNG BỘ VNSYSTEM -->
    <div id="vnsystemHistoryModal" class="act-modal" style="display:none;">
        <div class="act-modal-content" style="max-width: 900px;">
            <div style="padding: 15px 20px; border-bottom: 1px solid var(--dx-border); display: flex; justify-content: space-between; align-items: center; background: var(--dx-bg-subtle);">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--dx-text-main); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--dx-text-muted);">history</span> Lịch Sử Đồng Bộ Extrusion Report (VNSYSTEM)
                </h3>
                <button onclick="closeVnsystemHistoryModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:var(--dx-text-muted);">&times;</button>
            </div>
            
            <div style="padding: 16px 20px; max-height: calc(85vh - 120px); overflow-y: auto;">
                <div class="act-table-wrapper" style="max-height: 400px;">
                    <table class="act-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Thời Gian</th>
                                <th>Tham Số</th>
                                <th>Thời Lượng</th>
                                <th>Tổng Đọc</th>
                                <th>Thêm Mới</th>
                                <th>Cập Nhật</th>
                                <th>Trạng Thái</th>
                                <th>Người Chạy</th>
                            </tr>
                        </thead>
                        <tbody id="vnsystemHistoryTableBody">
                            <tr><td colspan="9" style="text-align:center; padding: 20px;">Đang tải lịch sử...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div id="vnsystemHistoryPagination" class="mt-2"></div>
            </div>

            <div style="padding: 12px 20px; border-top: 1px solid var(--dx-border); text-align: right; background: var(--dx-bg-subtle);">
                <button onclick="closeVnsystemHistoryModal()" class="act-btn act-btn-secondary">Đóng</button>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPT LOGIC TRA CỨU, UPLOAD, TẢI EXCEL VÀ XÓA HÀNG LOẠT -->
<script>
let currentPage = 1;
let searchTimer = null;
let currentRowsData = [];

document.addEventListener("DOMContentLoaded", () => {
    loadActualData(1);
    loadLastSyncBanner();
});

function toggleFilterMode() {
    const mode = document.getElementById("filterMode").value;
    document.getElementById("monthFilterContainer").style.display = (mode === "month") ? "block" : "none";
    document.getElementById("rangeFilterContainer").style.display = (mode === "range") ? "flex" : "none";
    loadActualData(1);
}

function delaySearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { loadActualData(1); }, 400);
}

let currentActualLimit = 50;

// 1. Tải danh sách thực tích có Lọc & Phân trang
async function loadActualData(page = 1) {
    currentPage = page;
    const mode = document.getElementById("filterMode").value;
    let month = document.getElementById("filterMonth").value;
    if (!month) {
        month = new Date().toISOString().slice(0, 7);
        document.getElementById("filterMonth").value = month;
    }
    const startDate = document.getElementById("filterStartDate").value;
    const endDate = document.getElementById("filterEndDate").value;
    const pipeSize = document.getElementById("filterPipeSize").value;
    const search = document.getElementById("filterSearch").value.trim();

    let url = `api/get_extrusion_actuals.php?page=${page}&limit=${currentActualLimit}&mode=${mode}&pipe_size=${pipeSize}&search=${encodeURIComponent(search)}`;
    if (mode === 'month') url += `&month=${month}`;
    else url += `&start_date=${startDate}&end_date=${endDate}`;

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            currentRowsData = data.data;
            renderTableRows(data.data);
            renderPagination(data.pagination);
            document.getElementById("selectAllCheckbox").checked = false;
            updateSelectedCount();
        } else {
            alert("Lỗi tải dữ liệu: " + data.message);
        }
    } catch (err) {
        console.error("Lỗi khi tải dữ liệu thực tích:", err);
    }
}

// 2. Render dòng bảng HTML
function renderTableRows(rows) {
    const tbody = document.getElementById("actualTableBody");
    tbody.innerHTML = "";

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="19" style="text-align:center; padding: 20px; color: #94a3b8;">Không tìm thấy dữ liệu thực tích nào.</td></tr>`;
        return;
    }

    rows.forEach((r, idx) => {
        const sourceBadge = (r.data_source === 'VNSYSTEM')
            ? `<span style="background:#e0f2fe; color:#0284c7; border:1px solid rgba(2,132,199,0.3); padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700;">VNSYSTEM</span>`
            : `<span style="background:#f0fdf4; color:#16a34a; border:1px solid rgba(22,163,74,0.3); padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700;">EXCEL</span>`;

        tbody.innerHTML += `
            <tr>
                <td style="text-align:center;">
                    <input type="checkbox" class="row-checkbox" value="${r.id}" onchange="updateSelectedCount()">
                </td>
                <td style="text-align:center;">
                    <button onclick="openEditModal(${idx})" class="act-btn act-btn-primary" style="padding: 2px 6px; font-size:11px;">✏️ Sửa</button>
                    <button onclick="deleteSingleRow(${r.id})" class="act-btn act-btn-danger" style="padding: 2px 6px; font-size:11px;">🗑️ Xóa</button>
                </td>
                <td><b>${r.id}</b></td>
                <td>${sourceBadge}</td>
                <td>${r.production_date}</td>
                <td>${r.employee_code}</td>
                <td>${r.employee_name}</td>
                <td>${r.shift}</td>
                <td>${r.mfg_order_code}</td>
                <td><b>${r.product_code}</b></td>
                <td><span style="background:var(--dx-primary-bg, #eff6ff); color:var(--dx-primary, #2563eb); border:1px solid var(--dx-primary-border, rgba(37,99,235,0.2)); padding:2px 6px; border-radius:4px; font-weight:700;">${r.pipe_size}</span></td>
                <td>${r.device_code}</td>
                <td style="font-weight:700; color:var(--dx-success, #059669);">${Number(r.finished_qty_m).toLocaleString()}</td>
                <td>${Number(r.finished_qty_kg).toLocaleString()}</td>
                <td>${Number(r.ng_qty_kg).toLocaleString()}</td>
                <td>${Number(r.hard_waste_qty_kg).toLocaleString()}</td>
                <td>${r.total_downtime}</td>
                <td>${r.total_runtime}</td>
                <td>${r.machine_efficiency}%</td>
            </tr>
        `;
    });
}

// 3. Phân trang Chuẩn Hóa Toàn Hệ Thống
function renderPagination(p) {
    if (!p) return;
    renderStandardPagination("productionDataPagination", {
        currentPage: p.current_page,
        totalPages: p.total_pages,
        totalRecords: p.total_records,
        pageSize: p.limit || currentActualLimit,
        pageSizeOptions: [10, 25, 50, 100],
        onPageChange: (newPage) => loadActualData(newPage),
        onPageSizeChange: (newLimit) => {
            currentActualLimit = newLimit;
            loadActualData(1);
        }
    });
}

// 4. Chọn nhiều dòng & Xóa hàng loạt
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll(".row-checkbox");
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll(".row-checkbox:checked");
    const count = selected.length;
    const btn = document.getElementById("btnDeleteSelected");
    document.getElementById("selectedCount").innerText = count;

    if (count > 0) btn.style.display = "inline-flex";
    else btn.style.display = "none";
}

async function deleteSelectedRows() {
    const selected = document.querySelectorAll(".row-checkbox:checked");
    const ids = Array.from(selected).map(cb => parseInt(cb.value));

    if (ids.length === 0) return;

    if (!confirm(`Bạn có chắc chắn muốn xóa ${ids.length} dòng dữ liệu thực tích đã chọn?`)) return;

    try {
        const res = await fetch('api/delete_extrusion_actuals.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        });
        const result = await res.json();

        if (result.success) {
            alert(result.message);
            loadActualData(currentPage);
        } else {
            alert("Lỗi xóa dữ liệu: " + result.message);
        }
    } catch (err) {
        alert("Không thể kết nối Server để xóa dữ liệu!");
    }
}

async function deleteSingleRow(id) {
    if (!confirm(`Xác nhận xóa bản ghi ID: ${id}?`)) return;

    try {
        const res = await fetch('api/delete_extrusion_actuals.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: [id] })
        });
        const result = await res.json();

        if (result.success) {
            loadActualData(currentPage);
        } else {
            alert("Lỗi: " + result.message);
        }
    } catch (err) {
        alert("Lỗi kết nối Server!");
    }
}

// 5. Chỉnh sửa dòng
function openEditModal(index) {
    const row = currentRowsData[index];
    if (!row) return;

    document.getElementById("edit_id").value = row.id;
    document.getElementById("edit_production_date").value = row.production_date;
    document.getElementById("edit_employee_code").value = row.employee_code;
    document.getElementById("edit_employee_name").value = row.employee_name;
    document.getElementById("edit_shift").value = row.shift;
    document.getElementById("edit_mfg_order_code").value = row.mfg_order_code;
    document.getElementById("edit_product_code").value = row.product_code;
    document.getElementById("edit_device_code").value = row.device_code;
    document.getElementById("edit_finished_qty_m").value = row.finished_qty_m;
    document.getElementById("edit_finished_qty_kg").value = row.finished_qty_kg;
    document.getElementById("edit_ng_qty_kg").value = row.ng_qty_kg;
    document.getElementById("edit_total_downtime").value = row.total_downtime;
    document.getElementById("edit_machine_efficiency").value = row.machine_efficiency;

    document.getElementById("editModal").style.display = "flex";
}

function closeEditModal() { document.getElementById("editModal").style.display = "none"; }

async function saveEditRow() {
    const payload = {
        id: document.getElementById("edit_id").value,
        production_date: document.getElementById("edit_production_date").value,
        employee_code: document.getElementById("edit_employee_code").value,
        employee_name: document.getElementById("edit_employee_name").value,
        shift: document.getElementById("edit_shift").value,
        mfg_order_code: document.getElementById("edit_mfg_order_code").value,
        product_code: document.getElementById("edit_product_code").value,
        device_code: document.getElementById("edit_device_code").value,
        finished_qty_m: parseFloat(document.getElementById("edit_finished_qty_m").value) || 0,
        finished_qty_kg: parseFloat(document.getElementById("edit_finished_qty_kg").value) || 0,
        ng_qty_kg: parseFloat(document.getElementById("edit_ng_qty_kg").value) || 0,
        total_downtime: parseFloat(document.getElementById("edit_total_downtime").value) || 0,
        machine_efficiency: parseFloat(document.getElementById("edit_machine_efficiency").value) || 0
    };

    try {
        const res = await fetch('api/save_extrusion_actual_row.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            alert(result.message);
            closeEditModal();
            loadActualData(currentPage);
        } else {
            alert("Lỗi cập nhật: " + result.message);
        }
    } catch (err) {
        alert("Lỗi kết nối Server khi lưu cập nhật!");
    }
}

// 6. Upload & Download Excel
function openUploadModal() { document.getElementById("uploadModal").style.display = "flex"; }
function closeUploadModal() { document.getElementById("uploadModal").style.display = "none"; }

async function handleUploadExcel() {
    const fileInput = document.getElementById("excelFileInput");
    if (!fileInput.files.length) {
        alert("Vui lòng chọn file Excel!");
        return;
    }

    const btn = document.getElementById("btnSubmitUpload");
    btn.innerText = "Đang xử lý...";
    btn.disabled = true;

    const formData = new FormData();
    formData.append("excel_file", fileInput.files[0]);

    try {
        const res = await fetch('api/upload_extrusion_actuals.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            alert(result.message);
            closeUploadModal();
            fileInput.value = "";
            loadActualData(1);
        } else {
            alert("Lỗi Upload: " + result.message);
        }
    } catch (err) {
        alert("Không thể upload file Excel!");
    } finally {
        btn.innerText = "Bắt Đầu Upload";
        btn.disabled = false;
    }
}

function exportExcel() {
    const mode = document.getElementById("filterMode").value;
    const month = document.getElementById("filterMonth").value;
    const startDate = document.getElementById("filterStartDate").value;
    const endDate = document.getElementById("filterEndDate").value;
    const pipeSize = document.getElementById("filterPipeSize").value;
    const search = document.getElementById("filterSearch").value.trim();

    let url = `api/export_extrusion_actuals.php?mode=${mode}&pipe_size=${pipeSize}&search=${encodeURIComponent(search)}`;
    if (mode === 'month') url += `&month=${month}`;
    else url += `&start_date=${startDate}&end_date=${endDate}`;

    window.location.href = url;
}

// Lắng nghe sự kiện thay đổi theme
window.addEventListener('dxThemeChanged', () => {
    loadActualData(currentPage);
});

// =========================================================================
// 7. QUẢN LÝ ĐỒNG BỘ VNSYSTEM (EXTRUSION REPORT)
// =========================================================================

// Tải thông tin lần đồng bộ gần nhất để hiển thị lên Banner
async function loadLastSyncBanner() {
    try {
        const res = await fetch('api/sync_vnsystem_extrusion.php?action=get_last_sync');
        const data = await res.json();
        if (data.success) {
            const last = data.last_sync;
            const timeEl = document.getElementById("bannerLastSyncTime");
            const statusEl = document.getElementById("bannerLastSyncStatus");
            const countEl = document.getElementById("bannerLastSyncCount");
            const userEl = document.getElementById("bannerLastSyncUser");

            if (last) {
                timeEl.innerText = last.end_time || last.start_time || '--';
                if (last.status === 'SUCCESS') {
                    statusEl.innerText = 'Thành công';
                    statusEl.style.background = '#dcfce7';
                    statusEl.style.color = '#15803d';
                } else if (last.status === 'FAILED') {
                    statusEl.innerText = 'Thất bại';
                    statusEl.style.background = '#fee2e2';
                    statusEl.style.color = '#b91c1c';
                } else {
                    statusEl.innerText = 'Đang xử lý';
                    statusEl.style.background = '#fef3c7';
                    statusEl.style.color = '#b45309';
                }
                countEl.innerText = Number(last.total_fetched || 0).toLocaleString();
                userEl.innerText = last.synced_by || '-';
            } else {
                timeEl.innerText = 'Chưa có dữ liệu đồng bộ';
                statusEl.innerText = 'Chưa chạy';
                statusEl.style.background = 'var(--dx-bg-subtle)';
                statusEl.style.color = 'var(--dx-text-muted)';
                countEl.innerText = '0';
                userEl.innerText = '-';
            }

            // Điền username cấu hình nếu có
            if (data.config && data.config.username) {
                const userInput = document.getElementById("syncUsernameInput");
                if (userInput && !userInput.value) userInput.value = data.config.username;
            }
        }
    } catch (err) {
        console.error("Lỗi khi tải thông tin lần cuối đồng bộ:", err);
    }
}

// Chuyển đổi qua lại giữa đồng bộ Theo Tháng và Theo Khoảng Ngày trong Modal
function toggleSyncPeriodType(type) {
    const monthBox = document.getElementById("syncMonthContainer");
    const rangeBox = document.getElementById("syncRangeContainer");
    if (type === 'month') {
        monthBox.style.display = "block";
        rangeBox.style.display = "none";
    } else {
        monthBox.style.display = "none";
        rangeBox.style.display = "grid";
    }
}

function openVnsystemSyncModal() {
    document.getElementById("vnsystemSyncModal").style.display = "flex";
    document.getElementById("syncProgressBox").style.display = "none";
    document.getElementById("btnExecuteVnSync").disabled = false;
}

function closeVnsystemSyncModal() {
    document.getElementById("vnsystemSyncModal").style.display = "none";
}

// Thực hiện gọi API đồng bộ
async function handleVnsystemSync() {
    const periodType = document.querySelector('input[name="syncPeriodType"]:checked').value;
    const month = document.getElementById("syncMonthInput").value;
    const startDate = document.getElementById("syncStartDateInput").value;
    const endDate = document.getElementById("syncEndDateInput").value;
    const username = document.getElementById("syncUsernameInput").value.trim();
    const password = document.getElementById("syncPasswordInput").value;
    const saveCreds = document.getElementById("syncSaveCredentials").checked ? 1 : 0;
    const useSample = document.getElementById("syncUseSample").checked ? 1 : 0;

    if (!useSample && !username) {
        alert("Vui lòng nhập tài khoản VNSYSTEM hoặc chọn 'Chạy thử từ file mẫu nội bộ'!");
        return;
    }

    const btn = document.getElementById("btnExecuteVnSync");
    const progressBox = document.getElementById("syncProgressBox");
    const progressText = document.getElementById("syncProgressText");

    btn.disabled = true;
    progressBox.style.display = "block";
    progressText.innerText = "Đang kết nối VNSYSTEM & xác thực tài khoản...";

    const formData = new FormData();
    formData.append("action", "sync");
    formData.append("mode", periodType);
    formData.append("month", month);
    formData.append("start_date", startDate);
    formData.append("end_date", endDate);
    formData.append("username", username);
    formData.append("password", password);
    formData.append("save_credentials", saveCreds);
    formData.append("use_sample", useSample);

    setTimeout(() => {
        if (progressBox.style.display !== "none") {
            progressText.innerText = "Đang tải Extrusion Report & phân tích thuộc tính...";
        }
    }, 1200);

    setTimeout(() => {
        if (progressBox.style.display !== "none") {
            progressText.innerText = "Đang thực hiện UPSERT khử trùng lặp vào CSDL...";
        }
    }, 2800);

    try {
        const res = await fetch('api/sync_vnsystem_extrusion.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            alert(result.message);
            closeVnsystemSyncModal();
            loadLastSyncBanner();
            loadActualData(currentPage);
        } else {
            alert("Lỗi đồng bộ VNSYSTEM: " + result.message);
            loadLastSyncBanner();
        }
    } catch (err) {
        alert("Lỗi kết nối máy chủ khi thực hiện đồng bộ VNSYSTEM!");
    } finally {
        btn.disabled = false;
        progressBox.style.display = "none";
    }
}

// 8. LỊCH SỬ ĐỒNG BỘ VNSYSTEM
let historyCurrentPage = 1;

function openVnsystemHistoryModal() {
    document.getElementById("vnsystemHistoryModal").style.display = "flex";
    loadSyncHistory(1);
}

function closeVnsystemHistoryModal() {
    document.getElementById("vnsystemHistoryModal").style.display = "none";
}

async function loadSyncHistory(page = 1) {
    historyCurrentPage = page;
    const tbody = document.getElementById("vnsystemHistoryTableBody");
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color: var(--dx-text-muted);">Đang tải dữ liệu lịch sử...</td></tr>`;

    try {
        const res = await fetch(`api/sync_vnsystem_extrusion.php?action=get_history&page=${page}&limit=10`);
        const data = await res.json();

        if (data.success) {
            renderHistoryRows(data.data);
            renderHistoryPagination(data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color: var(--dx-danger);">Lỗi: ${data.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color: var(--dx-danger);">Không thể kết nối API lịch sử!</td></tr>`;
    }
}

function renderHistoryRows(rows) {
    const tbody = document.getElementById("vnsystemHistoryTableBody");
    tbody.innerHTML = "";

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color: var(--dx-text-muted);">Chưa có lịch sử đồng bộ nào.</td></tr>`;
        return;
    }

    rows.forEach(r => {
        const statusBadge = (r.status === 'SUCCESS')
            ? `<span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:2px 6px; border-radius:4px; font-weight:700; font-size:10px;">Thành công</span>`
            : (r.status === 'FAILED'
                ? `<span style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:2px 6px; border-radius:4px; font-weight:700; font-size:10px;" title="${r.error_message || ''}">Thất bại</span>`
                : `<span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:2px 6px; border-radius:4px; font-weight:700; font-size:10px;">Đang chạy</span>`);

        tbody.innerHTML += `
            <tr>
                <td><b>#${r.id}</b></td>
                <td>${r.end_time || r.start_time}</td>
                <td><span style="background:var(--dx-bg-subtle); padding:2px 6px; border-radius:3px; font-weight:600;">${r.time_param}</span></td>
                <td>${r.duration_seconds}s</td>
                <td style="font-weight:700;">${Number(r.total_fetched).toLocaleString()}</td>
                <td style="color:#16a34a; font-weight:700;">+${Number(r.total_inserted).toLocaleString()}</td>
                <td style="color:#0284c7; font-weight:700;">${Number(r.total_updated).toLocaleString()}</td>
                <td>${statusBadge}</td>
                <td>${r.synced_by}</td>
            </tr>
        `;
    });
}

function renderHistoryPagination(p) {
    const el = document.getElementById("vnsystemHistoryPagination");
    if (!el || !p || p.total_pages <= 1) {
        if (el) el.innerHTML = "";
        return;
    }
    let html = `<div style="display:flex; justify-content:flex-end; align-items:center; gap:6px; font-size:12px;">`;
    html += `<span>Trang ${p.current_page}/${p.total_pages} (${p.total_records} lần đồng bộ)</span>`;
    if (p.current_page > 1) {
        html += `<button class="act-btn act-btn-secondary" style="padding:2px 8px;" onclick="loadSyncHistory(${p.current_page - 1})">Trước</button>`;
    }
    if (p.current_page < p.total_pages) {
        html += `<button class="act-btn act-btn-secondary" style="padding:2px 8px;" onclick="loadSyncHistory(${p.current_page + 1})">Sau</button>`;
    }
    html += `</div>`;
    el.innerHTML = html;
}
</script>