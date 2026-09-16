<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  .filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }
  .bg-unused {
    background-color: #f1f5f9 !important;
    color: #94a3b8 !important;
    font-style: italic;
    font-weight: normal !important;
  }
  .bg-pending {
    background-color: #fffbeb !important;
    color: #b45309 !important;
    border: 1px dashed #fcd34d !important;
    font-weight: 500 !important;
  }
  .table-scroll-container {
    max-height: 550px;
    overflow-x: auto;
    overflow-y: auto;
  }
  .table-scroll-container th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.82rem;
    text-transform: uppercase;
    white-space: nowrap;
    box-shadow: inset 0 -1px 0 #e2e8f0;
  }
  .btn-action-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 6px;
  }
</style>

<div class="container-fluid py-3">
  <!-- HEADER & THAO TÁC -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold text-primary mb-1"><i class="bi bi-sliders me-2"></i>Tra Cứu Thông Số Bộ Trộn Màu</h4>
      <p class="text-muted small mb-0">Hỗ trợ tra cứu nhanh thông số kéo ống và cài đặt bộ trộn màu (Trục lớn / Trục nhỏ)</p>
    </div>
    
    <div class="d-flex gap-2">

      <!-- Nút chuyển sang trang Tổng quan Ma Trận vừa tạo -->
      <a href="index.php?mainpage=utilities&subpage=color_mixer_summary" class="btn btn-warning btn-sm fw-bold">
        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Xem Bảng Tổng Quan
      </a>

      <!-- Nút xóa nhiều mục được chọn -->
      <button class="btn btn-danger btn-sm d-none" id="btnDeleteMultiple" onclick="deleteMultipleSettings()">
        <i class="bi bi-trash-fill me-1"></i>Xóa Mục Đã Chọn (<span id="selectedCount">0</span>)
      </button>

      <button class="btn btn-outline-primary btn-sm" onclick="exportDataCsv()">
        <i class="bi bi-download me-1"></i>Xuất CSV Dữ Liệu
      </button>
      <button class="btn btn-outline-success btn-sm" onclick="openImportCsvModal()">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import dữ liệu
      </button>
      <button class="btn btn-primary btn-sm" onclick="openAddFormModal()">
        <i class="bi bi-plus-circle me-1"></i>Thêm Mới Form
      </button>
    </div>
  </div>

  <!-- BỘ LỌC THÔNG SỐ SIÊU TỐC -->
  <div class="filter-card p-3 mb-3">
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <label class="form-label fw-bold small text-primary mb-1">Loại Màu</label>
        <select id="filter_color_type" class="form-select form-select-sm" onchange="applyFilterAndFetch(1)">
          <option value="">-- Tất cả loại màu --</option>
          <option value="BASF">BASF</option>
          <option value="COVESTRO">COVESTRO</option>
          <option value="NYLON">NYLON</option>
          <option value="Masterbatch">Masterbatch</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label fw-bold small text-secondary mb-1">Loại Ống</label>
        <select id="filter_pipe_type" class="form-select form-select-sm" onchange="applyFilterAndFetch(1)">
          <option value="">-- Tất cả loại ống --</option>
          <option value="TPU">TPU</option>
          <option value="NYLON-T">NYLON-T</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-bold small text-secondary mb-1">Size Ống</label>
        <select id="filter_pipe_size" class="form-select form-select-sm" onchange="applyFilterAndFetch(1)">
          <option value="">-- Tất cả size --</option>
          <option value="TU0425">TU0425</option>
          <option value="TU0604">TU0604</option>
          <option value="TU0805">TU0805</option>
          <option value="TU1065">TU1065</option>
          <option value="TU1208">TU1208</option>
          <option value="TU1610">TU1610</option>
          <option value="TIUB01">TIUB01</option>
          <option value="TIUB05">TIUB05</option>
          <option value="TIUB07">TIUB07</option>
          <option value="TIUB11">TIUB11</option>
          <option value="TIUB13">TIUB13</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-bold small text-secondary mb-1">Mã Màu</label>
        <select id="filter_color_code" class="form-select form-select-sm" onchange="applyFilterAndFetch(1)">
          <option value="">-- Tất cả mã màu --</option>
          <option value="B">B</option>
          <option value="BU">BU</option>
          <option value="G">G</option>
          <option value="R">R</option>
          <option value="W">W</option>
          <option value="Y">Y</option>
          <option value="YR">YR</option>
          <option value="BU1">BU1</option>
          <option value="BU2">BU2</option>
          <option value="G2">G2</option>
          <option value="G3">G3</option>
          <option value="P1">P1</option>
          <option value="PU2">PU2</option>
          <option value="S1">S1</option>
          <option value="Y2">Y2</option>
          <option value="Y3">Y3</option>
          <option value="YR1">YR1</option>
          <option value="BU3">BU3</option>
          <option value="BR1">BR1</option>
          <option value="GR2">GR2</option>
          <option value="PU1">PU1</option>
          <option value="R1">R1</option>
          <option value="G4">G4</option>
          <option value="GR1">GR1</option>
          <option value="R2">R2</option>
          <option value="Y1">Y1</option>
          <option value="YR2">YR2</option>
          <option value="G1">G1</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex align-items-end">
        <button class="btn btn-light btn-sm w-100 border text-muted" onclick="resetFilters()">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Xóa Bộ Lọc
        </button>
      </div>
    </div>
  </div>

  <!-- BẢNG DỮ LIỆU CÓ PHÂN TRANG & THAO TÁC XÓA NHIỀU -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
      <div class="table-scroll-container">
        <table class="table table-hover align-middle mb-0 text-nowrap">
          <thead>
            <tr>
              <!-- Checkbox Chọn tất cả -->
              <th class="ps-3" style="width: 40px;">
                <input type="checkbox" class="form-check-input" id="checkAll" onchange="toggleSelectAll(this)">
              </th>
              <th>#</th>
              <th>Loại Màu</th>
              <th>Loại Ống</th>
              <th>Size</th>
              <th>Mã Màu</th>
              <th class="text-center text-primary">Tốc Độ Kéo (m/p)</th>
              <th class="text-center bg-danger-subtle text-danger">Bộ Trộn TRỤC LỚN (RPM)</th>
              <th class="text-center bg-warning-subtle text-warning-emphasis">Bộ Trộn TRỤC NHỎ (RPM)</th>
              <th class="text-end">Nhựa Nguyên Sinh (kg/h)</th>
              <th class="text-end">Lưu Lượng Màu (kg/h)</th>
              <th>Người & Ngày Cập Nhật</th>
              <th class="text-center">Thao Tác</th>
            </tr>
          </thead>
          <tbody id="mixer-table-body">
            <!-- Render từ API -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- PHÂN TRANG -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="text-muted small" id="pagination-info">Hiển thị 0 dòng</div>
    <nav>
      <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>
    </nav>
  </div>
</div>

<!-- MODAL FORM -->
<div class="modal fade" id="modalAddForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="formModalTitle"><i class="bi bi-plus-circle me-2"></i>Đăng Ký Thông Số Mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addMixerForm" onsubmit="saveFormSetting(event)">
        <input type="hidden" name="edit_id" id="edit_id" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-bold small">Loại Màu</label>
              <input type="text" name="color_type" id="form_color_type" class="form-control" placeholder="VD: BASF, TPU" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Loại Ống</label>
              <input type="text" name="pipe_type" id="form_pipe_type" class="form-control" placeholder="VD: TPU, NYLON-T" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Size Ống</label>
              <input type="text" name="pipe_size" id="form_pipe_size" class="form-control" placeholder="VD: TU0425" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Mã Màu</label>
              <input type="text" name="color_code" id="form_color_code" class="form-control" placeholder="VD: BU1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Tốc Độ Kéo Ống (m/p)</label>
              <input type="number" step="0.01" name="hauler_speed" id="form_hauler_speed" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Bộ Trộn Trục Lớn (RPM)</label>
              <input type="text" name="mixer_speed_large" id="form_mixer_speed_large" class="form-control" placeholder="Nhập số, - hoặc để trống">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Bộ Trộn Trục Nhỏ (RPM)</label>
              <input type="text" name="mixer_speed_small" id="form_mixer_speed_small" class="form-control" placeholder="Nhập số, - hoặc để trống">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Lượng Nhựa Nguyên Sinh (kg/h)</label>
              <input type="number" step="0.01" name="virgin_resin" id="form_virgin_resin" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Lưu Lượng Nhựa Màu (kg/h)</label>
              <input type="number" step="0.01" name="color_output" id="form_color_output" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary fw-bold">Lưu Thông Số</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL IMPORT CSV -->
<div class="modal fade" id="modalImportCsv" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Nhập Thông Số Từ File CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="importCsvForm" onsubmit="submitCsvImport(event)">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-bold small">Chọn tập tin CSV (.csv)</label>
            <input type="file" id="csv_file_input" name="csv_file" class="form-control" accept=".csv" required>
          </div>
          <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i> Tải file mẫu cấu trúc chuẩn: 
            <a href="javascript:void(0)" onclick="downloadColorMixerTemplate()" class="fw-bold text-decoration-underline text-primary">Tải về mẫu CSV</a>
            <br>
            <span class="text-danger fw-bold">* Lưu ý:</span> Hệ thống sẽ tự động cập nhật ghi đè nếu trùng (Loại màu, Loại ống, Size ống, Mã màu).
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-success fw-bold">Bắt Đầu Nhập Dữ Liệu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let currentPage = 1;
const limit = 15;
let loadedDatabaseData = [];
let selectedIds = new Set();

let formModalInstance = null;
let csvModalInstance = null;

document.addEventListener("DOMContentLoaded", () => {
  formModalInstance = new bootstrap.Modal(document.getElementById('modalAddForm'));
  csvModalInstance = new bootstrap.Modal(document.getElementById('modalImportCsv'));
  applyFilterAndFetch(1);
});

function formatSpeed(val) {
  if (val === null || val === undefined || val.toString().trim() === '') {
    return `<span class="badge bg-pending w-100 py-2"><i class="bi bi-hourglass-split me-1"></i>Đang chờ bổ sung</span>`;
  }
  if (val.toString().trim() === '-') {
    return `<span class="badge bg-unused w-100 py-2">Không sử dụng</span>`;
  }
  return `<span class="fw-bold fs-6">${val}</span>`;
}

function applyFilterAndFetch(page = 1) {
  currentPage = page;
  selectedIds.clear();
  updateDeleteButtonState();

  const cType = document.getElementById('filter_color_type').value;
  const pType = document.getElementById('filter_pipe_type').value;
  const pSize = document.getElementById('filter_pipe_size').value;
  const cCode = document.getElementById('filter_color_code').value;

  const queryParams = new URLSearchParams({
    page: currentPage,
    limit: limit,
    color_type: cType,
    pipe_type: pType,
    pipe_size: pSize,
    color_code: cCode
  });

  fetch(`api/color_mixer_get.php?${queryParams.toString()}`)
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        loadedDatabaseData = res.data;
        renderTable(res.data, (currentPage - 1) * limit);
        renderPagination(res.total, res.page, res.total_pages);
      } else {
        alert(res.message || 'Lỗi nạp dữ liệu từ máy chủ!');
      }
    })
    .catch(err => console.error('Lỗi API:', err));
}

function renderTable(data, startIndex = 0) {
  const tbody = document.getElementById('mixer-table-body');
  tbody.innerHTML = '';
  document.getElementById('checkAll').checked = false;

  if (!data || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-muted">Không tìm thấy dữ liệu phù hợp trong cơ sở dữ liệu.</td></tr>`;
    return;
  }

  data.forEach((item, index) => {
    const virginResin = item.virgin_resin_output ?? item.virgin_resin ?? 0;
    const colorOutput = item.color_masterbatch_output ?? item.color_output ?? 0;
    const isChecked = selectedIds.has(item.id) ? 'checked' : '';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="ps-3">
        <input type="checkbox" class="form-check-input item-checkbox" value="${item.id}" ${isChecked} onchange="toggleItemSelect(${item.id}, this.checked)">
      </td>
      <td class="fw-bold text-muted">${startIndex + index + 1}</td>
      <td><span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">${item.color_type}</span></td>
      <td><strong>${item.pipe_type}</strong></td>
      <td><span class="badge bg-secondary">${item.pipe_size}</span></td>
      <td><strong class="text-dark">${item.color_code}</strong></td>
      <td class="text-center fw-bold text-primary">${parseFloat(item.hauler_speed).toFixed(2)}</td>
      <td class="text-center bg-danger-subtle text-danger">${formatSpeed(item.mixer_speed_large)}</td>
      <td class="text-center bg-warning-subtle text-warning-emphasis">${formatSpeed(item.mixer_speed_small)}</td>
      <td class="text-end">${parseFloat(virginResin).toFixed(2)}</td>
      <td class="text-end fw-bold text-success">${parseFloat(colorOutput).toFixed(2)}</td>
      <td class="small">
        <div class="fw-bold text-dark">${item.updated_by_name || 'Hệ thống'}</div>
        <div class="text-muted fs-7">${item.updated_at || ''}</div>
      </td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-primary btn-action-icon me-1" onclick="editSetting(${item.id})" title="Chỉnh sửa">
          <i class="bi bi-pencil-fill"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger btn-action-icon" onclick="deleteSetting(${item.id})" title="Xóa">
          <i class="bi bi-trash-fill"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// THAO TÁC XÓA NHIỀU MỤC (BULK DELETE)
function toggleSelectAll(master) {
  const checkboxes = document.querySelectorAll('.item-checkbox');
  checkboxes.forEach(cb => {
    cb.checked = master.checked;
    const id = parseInt(cb.value);
    if (master.checked) selectedIds.add(id);
    else selectedIds.delete(id);
  });
  updateDeleteButtonState();
}

function toggleItemSelect(id, isChecked) {
  if (isChecked) selectedIds.add(id);
  else selectedIds.delete(id);

  const checkboxes = document.querySelectorAll('.item-checkbox');
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  document.getElementById('checkAll').checked = allChecked;
  
  updateDeleteButtonState();
}

function updateDeleteButtonState() {
  const btn = document.getElementById('btnDeleteMultiple');
  const countSpan = document.getElementById('selectedCount');
  countSpan.textContent = selectedIds.size;
  if (selectedIds.size > 0) btn.classList.remove('d-none');
  else btn.classList.add('d-none');
}

function deleteMultipleSettings() {
  const idsArray = Array.from(selectedIds);
  if (idsArray.length === 0) return;

  if (confirm(`Bạn có chắc chắn muốn xóa ${idsArray.length} mục thông số màu đã chọn?`)) {
    const formData = new FormData();
    formData.append('ids', JSON.stringify(idsArray));

    fetch('api/color_mixer_delete.php', { method: 'POST', body: formData })
      .then(res => res.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          if (data.success) {
            alert(data.message);
            applyFilterAndFetch(currentPage);
          } else alert(data.message);
        } catch (err) {
          alert('Lỗi 404/500 từ máy chủ. Kiểm tra lại file api/color_mixer_delete.php');
        }
      });
  }
}

function renderPagination(total, page, totalPages) {
  const info = document.getElementById('pagination-info');
  const list = document.getElementById('pagination-list');
  info.textContent = `Tổng cộng ${total} dòng (Trang ${page}/${totalPages || 1})`;
  list.innerHTML = '';
  if (totalPages <= 1) return;

  list.innerHTML += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="applyFilterAndFetch(${page - 1})">Trước</a></li>`;
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
      list.innerHTML += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="applyFilterAndFetch(${i})">${i}</a></li>`;
    }
  }
  list.innerHTML += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="applyFilterAndFetch(${page + 1})">Sau</a></li>`;
}

function resetFilters() {
  document.getElementById('filter_color_type').value = '';
  document.getElementById('filter_pipe_type').value = '';
  document.getElementById('filter_pipe_size').value = '';
  document.getElementById('filter_color_code').value = '';
  applyFilterAndFetch(1);
}

function exportDataCsv() {
  const cType = document.getElementById('filter_color_type').value;
  const pType = document.getElementById('filter_pipe_type').value;
  const pSize = document.getElementById('filter_pipe_size').value;
  const cCode = document.getElementById('filter_color_code').value;

  const queryParams = new URLSearchParams({
    export: 'csv',
    color_type: cType,
    pipe_type: pType,
    pipe_size: pSize,
    color_code: cCode
  });

  window.location.href = `api/color_mixer_export.php?${queryParams.toString()}`;
}

function editSetting(id) {
  const item = loadedDatabaseData.find(d => d.id == id);
  if (!item) return;

  document.getElementById('formModalTitle').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Thông Số`;
  document.getElementById('edit_id').value = item.id;
  document.getElementById('form_color_type').value = item.color_type;
  document.getElementById('form_pipe_type').value = item.pipe_type;
  document.getElementById('form_pipe_size').value = item.pipe_size;
  document.getElementById('form_color_code').value = item.color_code;
  document.getElementById('form_hauler_speed').value = item.hauler_speed;
  document.getElementById('form_mixer_speed_large').value = item.mixer_speed_large ?? '';
  document.getElementById('form_mixer_speed_small').value = item.mixer_speed_small ?? '';
  document.getElementById('form_virgin_resin').value = item.virgin_resin_output ?? item.virgin_resin ?? 0;
  document.getElementById('form_color_output').value = item.color_masterbatch_output ?? item.color_output ?? 0;

  formModalInstance.show();
}

function deleteSetting(id) {
  if (confirm("Bạn có chắc chắn muốn xóa dòng thông số này khỏi hệ thống?")) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('api/color_mixer_delete.php', { method: 'POST', body: formData })
      .then(res => res.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          if (data.success) {
            alert(data.message);
            applyFilterAndFetch(currentPage);
          } else alert(data.message);
        } catch (err) {
          alert('Lỗi 404/500 từ máy chủ. Kiểm tra lại file api/color_mixer_delete.php!');
        }
      });
  }
}

function openAddFormModal() {
  document.getElementById('addMixerForm').reset();
  document.getElementById('edit_id').value = '';
  document.getElementById('formModalTitle').innerHTML = `<i class="bi bi-plus-circle me-2"></i>Đăng Ký Thông Số Mới`;
  formModalInstance.show();
}

function openImportCsvModal() {
  document.getElementById('importCsvForm').reset();
  csvModalInstance.show();
}

function saveFormSetting(e) {
  e.preventDefault();
  const formData = new FormData(document.getElementById('addMixerForm'));

  fetch('api/color_mixer_save.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(text => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          alert(data.message);
          formModalInstance.hide();
          applyFilterAndFetch(currentPage);
        } else alert(data.message);
      } catch (err) {
        alert('Lỗi 404/500 từ máy chủ. Kiểm tra file api/color_mixer_save.php!');
      }
    });
}

function downloadColorMixerTemplate() {
  let csvContent = "\uFEFFLoại Màu,Loại Ống,Size Ống,Mã Màu,Tốc Độ Kéo (m/p),Bộ Trộn Trục Lớn (RPM),Bộ Trộn Trục Nhỏ (RPM),Nhựa Nguyên Sinh (kg/h),Lưu Lượng Màu (kg/h)\n";
  csvContent += "BASF,TPU,TU0425,BU1,60.00,50.00,-,30.00,3.00\n";
  csvContent += "COVESTRO,NYLON-T,TU0604,BU2,45.00,-,,25.00,2.50\n";

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  link.setAttribute("href", url);
  link.setAttribute("download", "Mau_Bo_Tron_Mau.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function submitCsvImport(e) {
  e.preventDefault();
  const fileInput = document.getElementById('csv_file_input');
  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('csv_file', fileInput.files[0]);

  fetch('api/color_mixer_import.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(text => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          alert(data.message);
          csvModalInstance.hide();
          applyFilterAndFetch(1);
        } else alert(data.message);
      } catch (err) {
        alert('Lỗi 404/500 từ server. Kiểm tra file api/color_mixer_import.php!');
      }
    });
}
</script>