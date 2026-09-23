<?php
/**
 * Module: Import Dữ Liệu Tăng Ca Excel (UPSERT)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../../core/check_permission.php';
checkAuth();
?>

<div class="app-page-wrapper">
  <!-- 1. Header Trang -->
  <div class="app-page-header">
    <div class="app-page-title">
      <span class="material-icons text-primary" style="font-size: 28px;">upload_file</span>
      <div>
        <h1 style="font-size: 19px; font-weight: 800; margin: 0;">IMPORT DỮ LIỆU TĂNG CA EXCEL (UPSERT)</h1>
        <p class="text-muted small mb-0">Nạp dữ liệu DanhSachDuyetTangCaKeHoach.xlsx và DanhSachDuyetTangCaThucTe.xlsx từ HRM</p>
      </div>
    </div>
    <div class="app-page-actions">
      <button class="app-btn app-btn-primary btn-sm" type="button" onclick="autoImportDataFolderFiles()">
        <span class="material-icons fs-6">sync</span> Nạp 2 File Từ Thư Mục Data
      </button>
    </div>
  </div>

  <!-- 2. Khu Vực Upload & Hướng Dẫn -->
  <div class="row g-3 mb-4">
    <!-- Form Upload -->
    <div class="col-12 col-xl-7">
      <div class="app-card h-100">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
          <strong class="d-flex align-items-center gap-2">
            <span class="material-icons text-primary">cloud_upload</span> Tải Lên Tập Tin Excel Mới
          </strong>
          <span class="badge bg-light text-muted border">Định dạng hỗ trợ: .XLSX</span>
        </div>

        <div class="p-3">
          <form id="importForm" onsubmit="handleUploadSubmit(event)">
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Loại Dữ Liệu Tăng Ca <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="file_type" id="typePlan" value="plan" checked>
                  <label class="form-check-label fw-bold" for="typePlan">
                    Tăng ca kế hoạch (DanhSachDuyetTangCaKeHoach.xlsx)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="file_type" id="typeActual" value="actual">
                  <label class="form-check-label fw-bold" for="typeActual">
                    Tăng ca thực tế (DanhSachDuyetTangCaThucTe.xlsx)
                  </label>
                </div>
              </div>
            </div>

            <!-- Vùng chọn file -->
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Chọn tập tin từ máy tính <span class="text-danger">*</span></label>
              <input type="file" class="app-form-control" id="excelFile" name="excel_file" accept=".xlsx" required>
              <small class="text-muted d-block mt-1">Cơ chế **UPSERT** tự động: Nếu bản ghi chưa có $\rightarrow$ INSERT mới; nếu đã có $\rightarrow$ UPDATE ghi đè và lưu vết audit log.</small>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-2">
              <button type="submit" class="app-btn app-btn-primary" id="btnUploadSubmit">
                <span class="material-icons fs-6">upload</span> Bắt Đầu Import Dữ Liệu
              </button>
              <div id="uploadSpinner" style="display: none;" class="d-flex align-items-center gap-2 text-primary small">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Đang phân tích và nạp dữ liệu...</span>
              </div>
            </div>
          </form>

          <!-- Thẻ kết quả import ngay sau khi nạp -->
          <div id="importResultAlert" class="mt-3 p-3 rounded border" style="display: none;"></div>
        </div>
      </div>
    </div>

    <!-- Hướng Dẫn Quy Tắc UPSERT & Validation -->
    <div class="col-12 col-xl-5">
      <div class="app-card h-100">
        <div class="p-3 border-bottom d-flex align-items-center gap-2">
          <span class="material-icons text-info">info</span>
          <strong>Quy Tắc Import & Khóa Nhận Diện</strong>
        </div>
        <div class="p-3 small text-muted" style="line-height: 1.6;">
          <h6 class="fw-bold text-dark small mb-1">1. Khóa nhận diện duy nhất (Unique Business Key):</h6>
          <p class="mb-2">
            • <strong>Kế hoạch</strong>: <code>Mã NV + Ngày tăng ca + Thời gian bắt đầu</code><br/>
            • <strong>Thực tế</strong>: <code>Mã NV + Ngày tăng ca + Thời gian bắt đầu TT</code>
          </p>

          <h6 class="fw-bold text-dark small mb-1">2. Cơ chế xử lý trùng (UPSERT):</h6>
          <p class="mb-2">
            Không tạo bản ghi trùng lặp. Khi import lại file có cùng ca tăng ca, hệ thống sẽ ghi đè số phút, lý do, người duyệt mới nhất và tự động lưu vết dữ liệu cũ vào <strong>Audit Trail</strong>.
          </p>

          <h6 class="fw-bold text-dark small mb-1">3. Kiểm tra tính toàn vẹn (Validation):</h6>
          <p class="mb-0">
            • Dòng thiếu Mã NV hoặc sai định dạng ngày giờ sẽ bị loại trừ và ghi nhận vào bảng lỗi.<br/>
            • Sau khi import thành công, hệ thống tự động cập nhật bảng <strong>Lũy kế 200 giờ/năm</strong>.
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Bảng Lịch Sử Các Đợt Import (Batch History) -->
  <div class="app-card">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="material-icons text-primary">history</span>
        <strong>LỊCH SỬ CÁC ĐỢT IMPORT DỮ LIỆU</strong>
      </div>
      <button class="app-btn app-btn-secondary btn-sm" onclick="loadBatchesList()">
        <span class="material-icons fs-6">refresh</span> Làm mới
      </button>
    </div>

    <div class="app-table-responsive" style="max-height: 450px; overflow-y: auto;">
      <table class="app-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Mã Đợt (Batch Code)</th>
            <th>Loại Dữ Liệu</th>
            <th>Tên Tập Tin</th>
            <th style="text-align: right;">Tổng Dòng</th>
            <th style="text-align: right; color: var(--dx-success);">Thêm Mới</th>
            <th style="text-align: right; color: var(--dx-primary);">Ghi Đè (Update)</th>
            <th style="text-align: right; color: var(--dx-danger);">Lỗi</th>
            <th style="text-align: center;">Trạng Thái</th>
            <th>Người Thực Hiện</th>
            <th>Thời Gian</th>
            <th style="text-align: center; width: 140px;">Thao Tác</th>
          </tr>
        </thead>
        <tbody id="batchesTableBody">
          <!-- Render bằng JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Xem Chi Tiết Dòng Lỗi -->
<div class="modal fade" id="batchErrorsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">error_outline</span>
          Chi Tiết Dòng Dữ Liệu Bị Lỗi (Batch: <span id="errorModalBatchCode">...</span>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="table-responsive" style="max-height: 350px;">
          <table class="table table-bordered table-sm align-middle small">
            <thead class="table-light">
              <tr>
                <th style="width: 80px;" class="text-center">Dòng Excel</th>
                <th>Nguyên Nhân Lỗi</th>
                <th>Dữ Liệu Thô (Raw Data)</th>
              </tr>
            </thead>
            <tbody id="batchErrorsTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Xem Audit Log Lịch Sử Thay Đổi -->
<div class="modal fade" id="batchLogsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2">
          <span class="material-icons fs-5">receipt_long</span>
          Audit Log Lịch Sử Ghi Đè (Batch: <span id="logModalBatchCode">...</span>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="table-responsive" style="max-height: 400px;">
          <table class="table table-bordered table-sm align-middle small">
            <thead class="table-light">
              <tr>
                <th style="width: 100px;">Mã NV</th>
                <th style="width: 110px;">Ngày OT</th>
                <th style="width: 80px;" class="text-center">Thao Tác</th>
                <th>Dữ Liệu Trước Khi Ghi Đè</th>
                <th>Dữ Liệu Sau Khi Cập Nhật</th>
                <th style="width: 140px;">Thời Gian</th>
              </tr>
            </thead>
            <tbody id="batchLogsTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="app-btn app-btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  loadBatchesList();
});

async function loadBatchesList() {
  const tbody = document.getElementById('batchesTableBody');
  tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-3">Đang tải lịch sử import...</td></tr>';

  try {
    const res = await fetch('api/overtime_import.php?action=get_batches');
    const data = await res.json();

    if (!data.success || !data.batches || data.batches.length === 0) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4">Chưa có lịch sử đợt import nào.</td></tr>';
      return;
    }

    let html = '';
    data.batches.forEach(b => {
      const typeBadge = b.file_type === 'plan' 
        ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Kế hoạch</span>' 
        : '<span class="badge bg-success-subtle text-success border border-success-subtle">Thực tế</span>';
      const statusBadge = b.status === 'completed'
        ? '<span class="badge bg-success">Hoàn thành</span>'
        : (b.status === 'processing' ? '<span class="badge bg-warning text-dark">Đang xử lý</span>' : '<span class="badge bg-danger">Thất bại</span>');

      html += `
        <tr>
          <td class="text-muted fw-bold">#${b.id}</td>
          <td><strong class="font-monospace text-primary">${b.batch_code}</strong></td>
          <td>${typeBadge}</td>
          <td><small class="text-muted">${b.file_name}</small></td>
          <td style="text-align: right;"><strong>${b.total_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-success);"><strong>+${b.inserted_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-primary);"><strong>${b.updated_rows}</strong></td>
          <td style="text-align: right; color: var(--dx-danger);"><strong>${b.error_rows}</strong></td>
          <td style="text-align: center;">${statusBadge}</td>
          <td><small>${b.created_by}</small></td>
          <td><small class="text-muted">${b.created_at}</small></td>
          <td style="text-align: center;">
            <div class="d-flex align-items-center justify-content-center gap-1">
              ${b.error_rows > 0 ? `
                <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="openBatchErrors(${b.id}, '${b.batch_code}')" title="Xem lỗi">
                  <span class="material-icons" style="font-size: 14px;">error_outline</span>
                </button>
              ` : ''}
              <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="openBatchLogs(${b.id}, '${b.batch_code}')" title="Xem audit trail">
                <span class="material-icons" style="font-size: 14px;">receipt_long</span>
              </button>
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  } catch (err) {
    console.error('Lỗi loadBatchesList:', err);
    tbody.innerHTML = '<tr><td colspan="12" class="text-danger text-center">Lỗi kết nối máy chủ</td></tr>';
  }
}

async function handleUploadSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('btnUploadSubmit');
  const spinner = document.getElementById('uploadSpinner');
  const alertBox = document.getElementById('importResultAlert');

  btn.disabled = true;
  spinner.style.display = 'flex';
  alertBox.style.display = 'none';

  const formData = new FormData(document.getElementById('importForm'));
  formData.append('action', 'upload');

  try {
    const res = await fetch('api/overtime_import.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    alertBox.style.display = 'block';
    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">check_circle</span> ${data.message}</h6>
        <div class="small">
          Mã đợt: <strong class="font-monospace">${data.batch_code}</strong> | 
          Tổng số dòng: <strong>${data.total_rows}</strong> | 
          Thêm mới: <strong>+${data.inserted_rows}</strong> | 
          Ghi đè: <strong>${data.updated_rows}</strong> | 
          Lỗi: <strong>${data.error_rows}</strong>
        </div>
      `;
      document.getElementById('excelFile').value = '';
      await loadBatchesList();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `<strong>Lỗi:</strong> ${data.message}`;
    }
  } catch (err) {
    console.error('Lỗi handleUploadSubmit:', err);
    alertBox.style.display = 'block';
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi kết nối:</strong> ${err.message}`;
  } finally {
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

async function autoImportDataFolderFiles() {
  if (!confirm('Bạn có muốn tự động nạp 2 file DanhSachDuyetTangCaKeHoach.xlsx và DanhSachDuyetTangCaThucTe.xlsx từ thư mục Data không?')) return;

  const alertBox = document.getElementById('importResultAlert');
  alertBox.style.display = 'block';
  alertBox.className = 'mt-3 p-3 rounded border bg-info-subtle border-info text-info';
  alertBox.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang nạp 2 file từ thư mục Data...';

  try {
    const res = await fetch('api/overtime_import.php?action=import_data_files');
    const data = await res.json();

    if (data.success) {
      alertBox.className = 'mt-3 p-3 rounded border bg-success-subtle border-success text-success';
      const planRes = data.results.plan;
      const actRes = data.results.actual;
      alertBox.innerHTML = `
        <h6 class="fw-bold mb-1"><span class="material-icons align-middle fs-5">check_circle</span> ${data.message}</h6>
        <div class="small mt-1">
          • Kế hoạch: ${planRes.message || 'Xong'}<br/>
          • Thực tế: ${actRes.message || 'Xong'}
        </div>
      `;
      await loadBatchesList();
    } else {
      alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
      alertBox.innerHTML = `<strong>Lỗi:</strong> ${data.message}`;
    }
  } catch (err) {
    console.error('Lỗi autoImportDataFolderFiles:', err);
    alertBox.className = 'mt-3 p-3 rounded border bg-danger-subtle border-danger text-danger';
    alertBox.innerHTML = `<strong>Lỗi:</strong> ${err.message}`;
  }
}

async function openBatchErrors(batchId, batchCode) {
  document.getElementById('errorModalBatchCode').textContent = batchCode;
  const tbody = document.getElementById('batchErrorsTableBody');
  tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-2">Đang tải danh sách lỗi...</td></tr>';

  try {
    const res = await fetch(`api/overtime_import.php?action=get_batch_errors&batch_id=${batchId}`);
    const data = await res.json();
    if (!data.success || !data.errors || data.errors.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-2">Không có dòng lỗi nào.</td></tr>';
    } else {
      let html = '';
      data.errors.forEach(e => {
        html += `
          <tr>
            <td class="text-center fw-bold text-danger">Dòng ${e.row_index}</td>
            <td class="text-danger fw-bold">${e.error_reason}</td>
            <td><code style="word-break: break-all;">${e.raw_data}</code></td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('batchErrorsModal')).show();
  } catch (err) {
    console.error('Lỗi openBatchErrors:', err);
  }
}

async function openBatchLogs(batchId, batchCode) {
  document.getElementById('logModalBatchCode').textContent = batchCode;
  const tbody = document.getElementById('batchLogsTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Đang tải audit log...</td></tr>';

  try {
    const res = await fetch(`api/overtime_import.php?action=get_batch_logs&batch_id=${batchId}`);
    const data = await res.json();
    if (!data.success || !data.logs || data.logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-2">Chưa có lịch sử audit log cho đợt này.</td></tr>';
    } else {
      let html = '';
      data.logs.forEach(l => {
        const actBadge = l.action === 'insert' 
          ? '<span class="badge bg-success">Thêm mới</span>' 
          : '<span class="badge bg-primary">Ghi đè</span>';
        html += `
          <tr>
            <td class="font-monospace fw-bold text-primary">${l.employee_code}</td>
            <td>${l.ot_date}</td>
            <td class="text-center">${actBadge}</td>
            <td><small class="text-muted"><pre class="m-0" style="font-size: 10px;">${l.before_data || '-'}</pre></small></td>
            <td><small class="text-dark"><pre class="m-0" style="font-size: 10px;">${l.after_data}</pre></small></td>
            <td><small class="text-muted">${l.created_at}</small></td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('batchLogsModal')).show();
  } catch (err) {
    console.error('Lỗi openBatchLogs:', err);
  }
}
</script>

