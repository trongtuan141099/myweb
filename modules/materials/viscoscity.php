<div class="app-page-wrapper">
  <!-- HEADER & CÁC THAO TÁC -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">science</span>
        Quản Lý Độ Nhớt Nguyên Vật Liệu
      </h1>
      <p class="app-page-subtitle">Theo dõi chỉ số độ nhớt, YI và kiểm soát số lượng vật liệu nhập kho nhà máy</p>
    </div>

    <div class="app-page-actions">
      <button onclick="downloadTemplate()" class="app-btn app-btn-secondary">
        <span class="material-icons">description</span> Tải File Mẫu (.csv)
      </button>
      <?php if (hasPermission('materials.edit')): ?>
      <button onclick="openImportModal()" class="app-btn app-btn-success">
        <span class="material-icons">file_upload</span> Import Dữ Liệu
      </button>
      <?php endif; ?>
      <button onclick="exportData()" class="app-btn app-btn-primary">
        <span class="material-icons">file_download</span> Xuất Báo Cáo
      </button>
    </div>
  </div>

  <!-- BỘ LỌC TÌM KIẾM DỮ LIỆU -->
  <div class="app-filter-card">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="d-flex align-items-center gap-1">
        <label class="app-form-label mb-0">Từ ngày:</label>
        <input type="date" id="startDate" class="app-form-control" onchange="loadViscosityData(1)">
      </div>
      <div class="d-flex align-items-center gap-1">
        <label class="app-form-label mb-0">Đến ngày:</label>
        <input type="date" id="endDate" class="app-form-control" onchange="loadViscosityData(1)">
      </div>
      <div class="app-input-with-icon ms-md-auto">
        <span class="material-icons">search</span>
        <input type="text" id="searchInput" placeholder="Tìm LVF, Mã LK, Lot No..." class="app-form-control" style="width: 260px;" onkeyup="delaySearch()">
      </div>
    </div>
  </div>

  <!-- BẢNG HIỂN THỊ DỮ LIỆU CHUẨN CÔNG NGHIỆP -->
  <div class="app-card">
    <div class="app-table-container">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 50px; text-align: center;">STT</th>
            <th>LVF</th>
            <th>Ngày nhập kho</th>
            <th>Chủng loại</th>
            <th>Mã Linh Kiện</th>
            <th>Lot No</th>
            <th>Độ Nhớt</th>
            <th>YI</th>
            <th>Số Lượng</th>
            <th>Ghi Chú</th>
          </tr>
        </thead>
        <tbody id="viscosityTableBody"></tbody>
      </table>
    </div>

    <!-- PHÂN TRANG CHUẨN HÓA -->
    <div class="app-card-footer">
      <div id="viscosityPagination" class="w-100"></div>
    </div>
  </div>

  <!-- MODAL IMPORT FILE -->
  <div id="importModal" class="vis-modal" style="display:none;">
    <div class="vis-modal-content">
      <div class="vis-modal-header">
        <h3 class="vis-modal-title">
          <span class="material-icons">upload_file</span>
          Import Dữ Liệu Độ Nhớt
        </h3>
        <button onclick="closeImportModal()" class="vis-modal-close" aria-label="Đóng">&times;</button>
      </div>
      <div class="p-3">
        <label class="app-form-label mb-2">Chọn file bảng tính (.xlsx, .xls, .csv):</label>
        <input type="file" id="importFileInput" accept=".xlsx, .xls, .csv" class="app-form-control" style="width: 100%;">
      </div>
      <div class="vis-modal-footer">
        <button onclick="closeImportModal()" class="app-btn app-btn-secondary">Hủy</button>
        <button onclick="handleImportFile()" class="app-btn app-btn-success">Bắt Đầu Import</button>
      </div>
    </div>
  </div>
</div>

<style>
/* CSS RIÊNG BIỆT CHO MODULE QUẢN LÝ ĐỘ NHỚT */
.vis-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.vis-modal-content {
  background: var(--dx-bg-card, #ffffff);
  color: var(--dx-text-main);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  width: 100%;
  max-width: 460px;
  box-shadow: var(--dx-shadow-lg);
  overflow: hidden;
}

.vis-modal-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--dx-bg-subtle);
}

.vis-modal-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--dx-text-main);
}
.vis-modal-title .material-icons { color: var(--dx-primary); font-size: 20px; }

.vis-modal-close {
  border: none;
  background: none;
  font-size: 22px;
  cursor: pointer;
  color: var(--dx-text-muted);
}
.vis-modal-close:hover { color: var(--dx-danger); }

.vis-modal-footer {
  padding: 12px 18px;
  border-top: 1px solid var(--dx-border);
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  background: var(--dx-bg-subtle);
}

.lot-badge {
  background: var(--dx-primary-light, #eff6ff);
  color: var(--dx-primary, #1e40af);
  border: 1px solid var(--dx-primary-border, transparent);
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
  font-family: monospace;
}
</style>

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

let currentViscosityLimit = 50;

async function loadViscosityData(page = 1) {
  currentPage = page;
  const startDate = document.getElementById("startDate").value;
  const endDate   = document.getElementById("endDate").value;
  const search    = document.getElementById("searchInput").value.trim();

  const url = `api/get_viscosity_logs.php?page=${page}&limit=${currentViscosityLimit}&start_date=${startDate}&end_date=${endDate}&search=${encodeURIComponent(search)}`;

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.success) {
      renderTable(data.data, (page - 1) * currentViscosityLimit);
      renderPagination(data.pagination);
    } else {
      alert("Lỗi: " + data.message);
    }
  } catch (err) {
    console.error("Lỗi khi tải dữ liệu độ nhớt:", err);
  }
}

function renderTable(rows, startIdx) {
  const tbody = document.getElementById("viscosityTableBody");
  tbody.innerHTML = "";

  if (!rows || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding: 24px; color: var(--dx-text-muted);">Không có dữ liệu phù hợp.</td></tr>`;
    return;
  }

  rows.forEach((r, i) => {
    tbody.innerHTML += `
      <tr>
        <td style="text-align:center;"><b>${startIdx + i + 1}</b></td>
        <td><b>${escapeHtml(r.lvf || '')}</b></td>
        <td>${escapeHtml(r.received_date || '')}</td>
        <td>${escapeHtml(r.category || '')}</td>
        <td><b>${escapeHtml(r.part_code || '')}</b></td>
        <td><span class="lot-badge">${escapeHtml(r.lot_no || '')}</span></td>
        <td>${escapeHtml(r.viscosity || '')}</td>
        <td>${escapeHtml(r.yi_val || 0)}</td>
        <td style="font-weight:700; color:var(--dx-success);">${Number(r.quantity || 0).toLocaleString()}</td>
        <td>${escapeHtml(r.note || '')}</td>
      </tr>
    `;
  });
}

function renderPagination(p) {
  if (!p) return;
  renderStandardPagination("viscosityPagination", {
    currentPage: p.current_page,
    totalPages: p.total_pages,
    totalRecords: p.total_records,
    pageSize: p.limit || currentViscosityLimit,
    pageSizeOptions: [10, 25, 50, 100],
    onPageChange: (newPage) => loadViscosityData(newPage),
    onPageSizeChange: (newLimit) => {
      currentViscosityLimit = newLimit;
      loadViscosityData(1);
    }
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function downloadTemplate() {
  let csv = "\uFEFFLVF,Ngày nhập kho,chủng loại,MÃ LINH KIỆN,LOT NO,ĐỘ NHỚT,YI,SỐ LƯỢNG,GHI CHÚ\n";
  csv += "LVF940,2023-09-09,TUMA-C,FPU1195A10TR,F18340UCW2,8*1000(200℃-294N),1.9,5000,\n";
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "File_Mau_Do_Nhot.csv";
  link.click();
}

function openImportModal() { document.getElementById("importModal").style.display = "flex"; }
function closeImportModal() { document.getElementById("importModal").style.display = "none"; }

async function handleImportFile() {
  const input = document.getElementById("importFileInput");
  if (!input.files.length) { alert("Vui lòng chọn file cần import!"); return; }

  const formData = new FormData();
  formData.append("excel_file", input.files[0]);

  try {
    const res = await fetch('api/upload_viscosity_logs.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
      alert(result.message);
      closeImportModal();
      loadViscosityData(1);
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    alert("Lỗi kết nối máy chủ!");
  }
}

function exportData() {
  const startDate = document.getElementById("startDate").value;
  const endDate   = document.getElementById("endDate").value;
  const search    = document.getElementById("searchInput").value.trim();
  window.location.href = `api/export_viscosity_logs.php?start_date=${startDate}&end_date=${endDate}&search=${encodeURIComponent(search)}`;
}

// Lắng nghe sự kiện đổi chế độ Sáng / Tối
window.addEventListener('dxThemeChanged', () => {
  loadViscosityData(currentPage);
});
</script>