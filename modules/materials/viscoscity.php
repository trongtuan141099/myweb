<style>
.vis-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #334155; }
.vis-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 15px 20px; margin-bottom: 15px; }
.vis-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; }
.vis-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
.vis-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.vis-input, .vis-select { background-color: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 8px 12px; font-size: 13px; border-radius: 8px; outline: none; }
.vis-btn { border: none; padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.vis-btn-success { background-color: #10b981; color: #ffffff; }
.vis-btn-primary { background-color: #2563eb; color: #ffffff; }
.vis-btn-secondary { background-color: #ffffff; border: 1px solid #cbd5e1; color: #334155; }

/* BẢNG VỚI KHUNG CUỘN ĐỘNG 2 CHIỀU */
.vis-table-wrapper { 
    overflow-x: auto; 
    overflow-y: auto; 
    max-height: calc(100vh - 270px); 
    border: 1px solid #e2e8f0; 
    border-radius: 8px; 
}
.vis-table-wrapper::-webkit-scrollbar { width: 8px; height: 8px; }
.vis-table-wrapper::-webkit-scrollbar-track { background: #f1f5f9; }
.vis-table-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.vis-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; white-space: nowrap; text-align: left; }
.vis-table th { background-color: #f8fafc; color: #475569; font-weight: 700; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 10; }
.vis-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.vis-table tr:hover td { background-color: #f8fafc; }

/* Modal */
.vis-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.vis-modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 450px; overflow: hidden; }
</style>

<div class="vis-container">

    <!-- BAR THAO TÁC & BỘ LỌC -->
    <div class="vis-card">
        <div class="vis-header">
            <div>
                <h1 class="vis-title">🧪 QUẢN LÝ ĐỘ NHỚT NGUYÊN VẬT LIỆU</h1>
                <div style="font-size: 13px; color: #64748b;">Theo dõi chỉ số độ nhớt, YI và số lượng vật liệu nhập kho</div>
            </div>

            <div class="vis-controls">
                <button onclick="downloadTemplate()" class="vis-btn vis-btn-secondary">📄 Tải File Mẫu (.csv)</button>
                <button onclick="openImportModal()" class="vis-btn vis-btn-success">📥 Import Excel/CSV</button>
                <button onclick="exportData()" class="vis-btn vis-btn-primary">📤 Export Excel</button>
            </div>
        </div>

        <hr style="margin: 12px 0; border: 0; border-top: 1px solid #e2e8f0;">

        <!-- BỘ LỌC CHI TIẾT -->
        <div class="vis-controls">
            <span style="font-size: 13px; font-weight: 600;">Từ ngày:</span>
            <input type="date" id="startDate" class="vis-input" onchange="loadViscosityData(1)">
            <span style="font-size: 13px; font-weight: 600;">Đến ngày:</span>
            <input type="date" id="endDate" class="vis-input" onchange="loadViscosityData(1)">
            <input type="text" id="searchInput" placeholder="🔍 LVF, Mã LK, Lot No..." class="vis-input" style="width: 240px;" onkeyup="delaySearch()">
        </div>
    </div>

    <!-- BẢNG HIỂN THỊ DỮ LIỆU -->
    <div class="vis-card" style="padding: 12px;">
        <div class="vis-table-wrapper">
            <table class="vis-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">STT</th>
                        <th>LVF</th>
                        <th>Ngày nhập kho</th>
                        <th>Chủng loại</th>
                        <th>MÃ LINH KIỆN</th>
                        <th>LOT NO</th>
                        <th>ĐỘ NHỚT</th>
                        <th>YI</th>
                        <th>SỐ LƯỢNG</th>
                        <th>GHI CHÚ</th>
                    </tr>
                </thead>
                <tbody id="viscosityTableBody"></tbody>
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

    <!-- MODAL IMPORT FILE -->
    <div id="importModal" class="vis-modal" style="display:none;">
        <div class="vis-modal-content">
            <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 style="margin:0; font-size:16px; font-weight:700;">📥 Import Dữ Liệu Độ Nhớt</h3>
                <button onclick="closeImportModal()" style="border:none; background:none; font-size:22px; cursor:pointer;">&times;</button>
            </div>
            <div style="padding: 20px;">
                <input type="file" id="importFileInput" accept=".xlsx, .xls, .csv" class="vis-input" style="width: 100%;">
            </div>
            <div style="padding: 12px 20px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc;">
                <button onclick="closeImportModal()" class="vis-btn vis-btn-secondary">Hủy</button>
                <button onclick="handleImportFile()" class="vis-btn vis-btn-success">Bắt Đầu Import</button>
            </div>
        </div>
    </div>

</div>

<script>
let currentPage = 1;
let searchTimer = null;

document.addEventListener("DOMContentLoaded", () => {
    loadViscosityData(1);
});

function delaySearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { loadViscosityData(1); }, 400);
}

async function loadViscosityData(page = 1) {
    currentPage = page;
    const startDate = document.getElementById("startDate").value;
    const endDate   = document.getElementById("endDate").value;
    const search    = document.getElementById("searchInput").value.trim();

    const url = `/myweb/api/get_viscosity_logs.php?page=${page}&limit=50&start_date=${startDate}&end_date=${endDate}&search=${encodeURIComponent(search)}`;

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            renderTable(data.data, (page - 1) * 50);
            renderPagination(data.pagination);
        } else {
            alert("Lỗi: " + data.message);
        }
    } catch (err) {
        console.error("Lỗi khi tải dữ liệu:", err);
    }
}

function renderTable(rows, startIdx) {
    const tbody = document.getElementById("viscosityTableBody");
    tbody.innerHTML = "";

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding: 20px; color: #94a3b8;">Không có dữ liệu.</td></tr>`;
        return;
    }

    rows.forEach((r, i) => {
        tbody.innerHTML += `
            <tr>
                <td style="text-align:center;"><b>${startIdx + i + 1}</b></td>
                <td><b>${r.lvf}</b></td>
                <td>${r.received_date}</td>
                <td>${r.category || ''}</td>
                <td><b>${r.part_code}</b></td>
                <td><span style="background:#eff6ff; color:#2563eb; padding:2px 6px; border-radius:4px; font-weight:700;">${r.lot_no}</span></td>
                <td>${r.viscosity || ''}</td>
                <td>${r.yi_val || 0}</td>
                <td style="font-weight:700; color:#059669;">${Number(r.quantity).toLocaleString()}</td>
                <td>${r.note || ''}</td>
            </tr>
        `;
    });
}

function renderPagination(p) {
    document.getElementById("totalRecords").innerText = p.total_records.toLocaleString();
    const startRecord = (p.current_page - 1) * p.limit + (p.total_records > 0 ? 1 : 0);
    const endRecord   = Math.min(p.current_page * p.limit, p.total_records);
    document.getElementById("recordRange").innerText = `${startRecord}-${endRecord}`;

    const container = document.getElementById("paginationControls");
    container.innerHTML = "";

    if (p.total_pages <= 1) return;

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 2 && i <= p.current_page + 2)) {
            const activeStyle = (i === p.current_page) ? 'background:#2563eb; color:#fff;' : '';
            container.innerHTML += `<button onclick="loadViscosityData(${i})" class="vis-btn vis-btn-secondary" style="padding:4px 8px; ${activeStyle}">${i}</button>`;
        }
    }
}

// TẢI FILE MẪU CSV
function downloadTemplate() {
    let csv = "\uFEFFLVF,Ngày nhập kho,chủng loại,MÃ LINH KIỆN,LOT NO,ĐỘ NHỚT,YI,SỐ LƯỢNG,GHI CHÚ\n";
    csv += "LVF940,2023-09-09,TUMA-C,FPU1195A10TR,F18340UCW2,8*1000(200℃-294N),1.9,5000,\n";
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "File_Mau_Do_Nhot.csv";
    link.click();
}

// IMPORT / EXPORT
function openImportModal() { document.getElementById("importModal").style.display = "flex"; }
function closeImportModal() { document.getElementById("importModal").style.display = "none"; }

async function handleImportFile() {
    const input = document.getElementById("importFileInput");
    if (!input.files.length) { alert("Vui lòng chọn file!"); return; }

    const formData = new FormData();
    formData.append("excel_file", input.files[0]);

    try {
        const res = await fetch('/myweb/api/upload_viscosity_logs.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            closeImportModal();
            loadViscosityData(1);
        } else {
            alert("Lỗi: " + result.message);
        }
    } catch (err) {
        alert("Lỗi kết nối Server!");
    }
}

function exportData() {
    const startDate = document.getElementById("startDate").value;
    const endDate   = document.getElementById("endDate").value;
    const search    = document.getElementById("searchInput").value.trim();
    window.location.href = `/myweb/api/export_viscosity_logs.php?start_date=${startDate}&end_date=${endDate}&search=${encodeURIComponent(search)}`;
}
</script>