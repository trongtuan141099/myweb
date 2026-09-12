<!-- CSS TÙY CHỈNH TỐI ƯU GIAO DIỆN & THANH CUỘN BẢNG -->
<style>
.act-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #334155; }
.act-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; }
.act-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; }
.act-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
.act-subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }
.act-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.act-input, .act-select { background-color: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 8px 12px; font-size: 13px; border-radius: 8px; outline: none; transition: border 0.2s; }
.act-input:focus, .act-select:focus { border-color: #2563eb; background-color: #fff; }
.act-btn { border: none; padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.act-btn-success { background-color: #10b981; color: #ffffff; }
.act-btn-success:hover { background-color: #059669; }
.act-btn-primary { background-color: #2563eb; color: #ffffff; }
.act-btn-primary:hover { background-color: #1d4ed8; }
.act-btn-danger { background-color: #ef4444; color: #ffffff; }
.act-btn-danger:hover { background-color: #dc2626; }
.act-btn-secondary { background-color: #e2e8f0; color: #475569; }
.act-btn-secondary:hover { background-color: #cbd5e1; }

/* 1. KHUNG CHỨA BẢNG TỰ ĐỘNG HIỂN THỊ CẢ 2 THANH CUỘN (NGANG & DỌC) */
.act-table-wrapper { 
    overflow-x: auto; 
    overflow-y: auto; 
    max-height: calc(100vh - 280px); /* Đảm bảo thấy ngay thanh cuộn ngang không cần zoom màn hình */
    border: 1px solid #e2e8f0; 
    border-radius: 8px; 
    background: #ffffff;
}

/* Custom Scrollbar cho Bảng mỏng và mượt */
.act-table-wrapper::-webkit-scrollbar { width: 8px; height: 8px; }
.act-table-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.act-table-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.act-table-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* 2. STYLE BẢNG VÀ CỐ ĐỊNH (STICKY) CỘT THAO TÁC */
.act-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; white-space: nowrap; text-align: left; }

.act-table th { 
    background-color: #f8fafc; 
    color: #475569; 
    font-weight: 700; 
    padding: 10px 12px; 
    border-bottom: 1px solid #e2e8f0; 
    border-right: 1px solid #f1f5f9;
    position: sticky; 
    top: 0; 
    z-index: 10; 
}

.act-table td { 
    padding: 8px 12px; 
    border-bottom: 1px solid #f1f5f9; 
    border-right: 1px solid #f8fafc;
    color: #334155; 
}
.act-table tr:hover td { background-color: #f8fafc; }

/* Cố định cột Checkbox (Cột 1) khi cuộn ngang */
.act-table th:nth-child(1),
.act-table td:nth-child(1) {
    position: sticky;
    left: 0;
    background-color: #ffffff;
    z-index: 12;
    border-right: 1px solid #e2e8f0;
}

/* Cố định cột Hành động (Cột 2) khi cuộn ngang */
.act-table th:nth-child(2),
.act-table td:nth-child(2) {
    position: sticky;
    left: 40px;
    background-color: #ffffff;
    z-index: 12;
    border-right: 2px solid #cbd5e1;
}

.act-table th:nth-child(1),
.act-table th:nth-child(2) {
    z-index: 20; /* Đảm bảo Header của 2 cột cố định đè lên trên */
    background-color: #f8fafc;
}

/* Modal Design */
.act-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.act-modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
</style>

<div class="act-container">

    <!-- BAR TIÊU ĐỀ VÀ THANH LỌC DỮ LIỆU -->
    <div class="act-card" style="margin-bottom: 12px; padding: 15px 20px;">
        <div class="act-header">
            <div>
                <h1 class="act-title">📋 QUẢN LÝ THỰC TÍCH SẢN XUẤT CHI TIẾT (押出)</h1>
                <div class="act-subtitle">Tra cứu, lọc, chỉnh sửa, xóa hàng loạt và xuất dữ liệu thực tích sang Excel</div>
            </div>

            <div class="act-controls">
                <button onclick="openUploadModal()" class="act-btn act-btn-success">
                    📥 Upload Excel Thực Tích
                </button>
                <button onclick="exportExcel()" class="act-btn act-btn-primary">
                    📤 Download Excel
                </button>
            </div>
        </div>

        <hr style="margin: 12px 0; border: 0; border-top: 1px solid #e2e8f0;">

        <!-- BỘ LỌC CHI TIẾT -->
        <div class="act-controls">
            <select id="filterMode" class="act-select" onchange="toggleFilterMode()">
                <option value="month">Theo Tháng</option>
                <option value="range">Theo Khoảng Ngày</option>
            </select>

            <div id="monthFilterContainer">
                <input type="month" id="filterMonth" value="2026-08" class="act-input" onchange="loadActualData(1)">
            </div>

            <div id="rangeFilterContainer" style="display:none;">
                <input type="date" id="filterStartDate" class="act-input" onchange="loadActualData(1)">
                <span style="color:#94a3b8;">-</span>
                <input type="date" id="filterEndDate" class="act-input" onchange="loadActualData(1)">
            </div>

            <select id="filterPipeSize" class="act-select" onchange="loadActualData(1)">
                <option value="ALL">Tất cả Size ống</option>
                <option value="TU04">TU04</option><option value="TU06">TU06</option>
                <option value="TU08">TU08</option><option value="TU10">TU10</option>
                <option value="TU12">TU12</option><option value="TU16">TU16</option>
                <option value="TIUB01">TIUB01</option><option value="TIUB05">TIUB05</option>
                <option value="TIUB07">TIUB07</option><option value="TIUB11">TIUB11</option>
                <option value="TIUB13">TIUB13</option>
            </select>

            <input type="text" id="filterSearch" placeholder="🔍 Mã NV, Tên NV, Mã CTSX, Mã SP..." class="act-input" style="width: 240px;" onkeyup="delaySearch()">

            <button id="btnDeleteSelected" onclick="deleteSelectedRows()" class="act-btn act-btn-danger" style="display:none;">
                🗑️ Xóa Đã Chọn (<span id="selectedCount">0</span>)
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

        <!-- PHÂN TRANG -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
            <div style="font-size: 13px; color: #64748b;">
                Hiển thị <span id="recordRange">0-0</span> trên tổng số <span id="totalRecords">0</span> bản ghi
            </div>
            <div id="paginationControls" style="display: flex; gap: 4px;"></div>
        </div>
    </div>

    <!-- MODAL UPLOAD FILE EXCEL -->
    <div id="uploadModal" class="act-modal" style="display:none;">
        <div class="act-modal-content" style="max-width: 450px;">
            <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 style="margin:0; font-size:16px; font-weight:700;">📥 Upload Dữ Liệu Thực Tích Excel</h3>
                <button onclick="closeUploadModal()" style="border:none; background:none; font-size:22px; cursor:pointer;">&times;</button>
            </div>
            <div style="padding: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Chọn file báo cáo (.xlsx, .xls):</label>
                <input type="file" id="excelFileInput" accept=".xlsx, .xls" class="act-input" style="width: 100%;">
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc;">
                <button onclick="closeUploadModal()" class="act-btn act-btn-secondary">Hủy</button>
                <button id="btnSubmitUpload" onclick="handleUploadExcel()" class="act-btn act-btn-success">Bắt Đầu Upload</button>
            </div>
        </div>
    </div>

    <!-- MODAL CHỈNH SỬA DÒNG THỰC TÍCH -->
    <div id="editModal" class="act-modal" style="display:none;">
        <div class="act-modal-content">
            <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 style="margin:0; font-size:16px; font-weight:700;">✏️ Chỉnh Sửa Thực Tích Sản Xuất</h3>
                <button onclick="closeEditModal()" style="border:none; background:none; font-size:22px; cursor:pointer;">&times;</button>
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
            <div style="padding: 12px 20px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc;">
                <button onclick="closeEditModal()" class="act-btn act-btn-secondary">Hủy</button>
                <button onclick="saveEditRow()" class="act-btn act-btn-primary">Lưu Cập Nhật</button>
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

// 1. Tải danh sách thực tích có Lọc & Phân trang
async function loadActualData(page = 1) {
    currentPage = page;
    const mode = document.getElementById("filterMode").value;
    const month = document.getElementById("filterMonth").value;
    const startDate = document.getElementById("filterStartDate").value;
    const endDate = document.getElementById("filterEndDate").value;
    const pipeSize = document.getElementById("filterPipeSize").value;
    const search = document.getElementById("filterSearch").value.trim();

    let url = `/myweb/api/get_extrusion_actuals.php?page=${page}&limit=50&mode=${mode}&pipe_size=${pipeSize}&search=${encodeURIComponent(search)}`;
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
        tbody.innerHTML = `<tr><td colspan="18" style="text-align:center; padding: 20px; color: #94a3b8;">Không tìm thấy dữ liệu thực tích nào.</td></tr>`;
        return;
    }

    rows.forEach((r, idx) => {
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
                <td>${r.production_date}</td>
                <td>${r.employee_code}</td>
                <td>${r.employee_name}</td>
                <td>${r.shift}</td>
                <td>${r.mfg_order_code}</td>
                <td><b>${r.product_code}</b></td>
                <td><span style="background:#eff6ff; color:#2563eb; padding:2px 6px; border-radius:4px; font-weight:700;">${r.pipe_size}</span></td>
                <td>${r.device_code}</td>
                <td style="font-weight:700; color:#059669;">${Number(r.finished_qty_m).toLocaleString()}</td>
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

// 3. Phân trang
function renderPagination(p) {
    document.getElementById("totalRecords").innerText = p.total_records.toLocaleString();
    const startRecord = (p.current_page - 1) * p.limit + (p.total_records > 0 ? 1 : 0);
    const endRecord = Math.min(p.current_page * p.limit, p.total_records);
    document.getElementById("recordRange").innerText = `${startRecord}-${endRecord}`;

    const container = document.getElementById("paginationControls");
    container.innerHTML = "";

    if (p.total_pages <= 1) return;

    if (p.current_page > 1) {
        container.innerHTML += `<button onclick="loadActualData(${p.current_page - 1})" class="act-btn act-btn-secondary" style="padding:4px 8px;">&laquo;</button>`;
    }

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 2 && i <= p.current_page + 2)) {
            const activeStyle = (i === p.current_page) ? 'background:#2563eb; color:#fff;' : '';
            container.innerHTML += `<button onclick="loadActualData(${i})" class="act-btn act-btn-secondary" style="padding:4px 8px; ${activeStyle}">${i}</button>`;
        } else if (i === p.current_page - 3 || i === p.current_page + 3) {
            container.innerHTML += `<span style="padding:4px;">...</span>`;
        }
    }

    if (p.current_page < p.total_pages) {
        container.innerHTML += `<button onclick="loadActualData(${p.current_page + 1})" class="act-btn act-btn-secondary" style="padding:4px 8px;">&raquo;</button>`;
    }
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
        const res = await fetch('/myweb/api/delete_extrusion_actuals.php', {
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
        const res = await fetch('/myweb/api/delete_extrusion_actuals.php', {
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
        const res = await fetch('/myweb/api/save_extrusion_actual_row.php', {
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
        const res = await fetch('/myweb/api/upload_extrusion_actuals.php', {
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

    let url = `/myweb/api/export_extrusion_actuals.php?mode=${mode}&pipe_size=${pipeSize}&search=${encodeURIComponent(search)}`;
    if (mode === 'month') url += `&month=${month}`;
    else url += `&start_date=${startDate}&end_date=${endDate}`;

    window.location.href = url;
}
</script>