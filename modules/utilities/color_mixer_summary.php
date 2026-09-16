<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  .summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }

  /* Khung cuộn 2 chiều siêu mượt cho bảng matrix lớn */
  .matrix-scroll-container {
    max-height: 720px;
    overflow-x: auto;
    overflow-y: auto;
    border: 1px solid #cbd5e1;
  }

  .table-matrix {
    font-size: 0.8rem;
    border-collapse: separate;
    border-spacing: 0;
  }

  .table-matrix th, .table-matrix td {
    border-right: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
    padding: 6px 8px;
    text-align: center;
    white-space: nowrap;
  }

  /* Cố định 3 hàng tiêu đề trên cùng */
  .table-matrix thead tr:nth-child(1) th { position: sticky; top: 0; z-index: 20; background: #e2e8f0; color: #1e293b; font-weight: 700; }
  .table-matrix thead tr:nth-child(2) th { position: sticky; top: 31px; z-index: 20; background: #f1f5f9; color: #334155; font-weight: 700; }
  .table-matrix thead tr:nth-child(3) th { position: sticky; top: 62px; z-index: 20; background: #f8fafc; color: #475569; font-size: 0.75rem; }

  /* Cố định cột STT và Cột Mã Màu bên trái */
  .sticky-col-1 { position: sticky; left: 0; z-index: 30; background: #ffffff; width: 45px; }
  .sticky-col-2 { position: sticky; left: 45px; z-index: 30; background: #ffffff; font-weight: 700; width: 70px; border-right: 2px solid #94a3b8 !important; }

  /* Tiêu đề góc trên bên trái */
  .table-matrix thead tr:nth-child(1) th.sticky-col-1,
  .table-matrix thead tr:nth-child(1) th.sticky-col-2,
  .table-matrix thead tr:nth-child(2) th.sticky-col-1,
  .table-matrix thead tr:nth-child(2) th.sticky-col-2,
  .table-matrix thead tr:nth-child(3) th.sticky-col-1,
  .table-matrix thead tr:nth-child(3) th.sticky-col-2 {
    z-index: 40;
    background: #cbd5e1;
  }

  /* MÃ HÓA MÀU SẮC Ô DỮ LIỆU */
  .cell-unused { background-color: #64748b !important; color: #64748b !important; user-select: none; } /* Ô tô màu xám đen - Không sử dụng */
  .cell-pending { background-color: #fef08a !important; color: #854d0e !important; font-weight: 600; } /* Ô màu vàng - Đang chờ bổ sung */
  .cell-active { background-color: #e0f2fe; color: #0369a1; font-weight: 700; } /* Ô có tốc độ */
</style>

<div class="container-fluid py-3">
  <!-- HEADER & ĐIỀU HƯỚNG -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold text-primary mb-1"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Bảng Tổng Hợp Tốc Độ Trộn Màu Dữ Liệu Toàn Hệ Thống</h4>
      <p class="text-muted small mb-0">Chế độ xem ma trận dữ liệu trực quan theo từng Loại bộ trộn, Size ống, Tốc độ kéo và Mã màu</p>
    </div>
    
    <div class="d-flex gap-2">
      <!-- Nút quay lại trang quản lý chi tiết -->
      <a href="index.php?mainpage=utilities&subpage=color_mixer" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Về Trang Quản Lý
      </a>
      <button class="btn btn-outline-primary btn-sm" onclick="fetchSummaryMatrix()">
        <i class="bi bi-arrow-clockwise me-1"></i>Làm Mới Dữ Liệu
      </button>
    </div>
  </div>

  <!-- THANH BỘ LỌC ĐỊNH HƯỚNG TỔNG QUAN -->
  <div class="summary-card p-3 mb-3">
    <div class="row g-2 align-items-center">
      <div class="col-6 col-md-3">
        <label class="form-label fw-bold small text-primary mb-1">Loại Bộ Trộn Màu</label>
        <select id="matrix_mixer_type" class="form-select form-select-sm" onchange="fetchSummaryMatrix()">
          <option value="mixer_speed_small">Bộ Trộn TRỤC NHỎ</option>
          <option value="mixer_speed_large">Bộ Trộn TRỤC LỚN</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label fw-bold small text-secondary mb-1">Loại Ống</label>
        <select id="matrix_pipe_type" class="form-select form-select-sm" onchange="fetchSummaryMatrix()">
          <option value="">-- Tất cả loại ống --</option>
          <option value="TPU">Ống TPU</option>
          <option value="NYLON-T">Ống NYLON-T</option>
        </select>
      </div>
      <!-- CHÚ THÍCH MÀU SẮC TRỰC QUAN -->
      <div class="col-12 col-md-6 d-flex justify-content-md-end gap-3 pt-2 pt-md-0 fs-7">
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1" style="width:14px;height:14px;background:#e0f2fe;border:1px solid #0284c7"></span> Có dữ liệu RPM</span>
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1" style="width:14px;height:14px;background:#fef08a;border:1px solid #eab308"></span> Đang chờ cập nhật</span>
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1" style="width:14px;height:14px;background:#64748b;"></span> Không sử dụng (-)</span>
      </div>
    </div>
  </div>

  <!-- MA TRẬN DỮ LIỆU BẢNG TỔNG QUAN -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="matrix-scroll-container">
        <table class="table-matrix w-100" id="matrix-table">
          <thead id="matrix-head">
            <!-- Nạp các hàng tiêu đề phân tầng qua JS -->
          </thead>
          <tbody id="matrix-body">
            <!-- Nạp các dòng mã màu qua JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const COLOR_CODES = [
  "B", "BU", "G", "R", "W", "Y", "YR", "BU1", "BU2", "G2", "G3", "P1", "PU2", 
  "S1", "Y2", "Y3", "YR1", "BU3", "BR1", "GR2", "PU1", "R1", "G4", "GR1", "R2", "Y1", "YR2", "G1"
];

document.addEventListener("DOMContentLoaded", () => {
  fetchSummaryMatrix();
});

function fetchSummaryMatrix() {
  const mixerType = document.getElementById('matrix_mixer_type').value;
  const pipeType = document.getElementById('matrix_pipe_type').value;

  const queryParams = new URLSearchParams({
    mixer_type: mixerType,
    pipe_type: pipeType
  });

  fetch(`api/color_mixer_summary_get.php?${queryParams.toString()}`)
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        renderMatrix(res.columns, res.matrixData, mixerType);
      } else {
        alert(res.message || 'Lỗi nạp dữ liệu ma trận tổng quan!');
      }
    })
    .catch(err => console.error('Lỗi API Summary:', err));
}

function renderMatrix(columns, matrixData, mixerType) {
  const thead = document.getElementById('matrix-head');
  const tbody = document.getElementById('matrix-body');
  thead.innerHTML = '';
  tbody.innerHTML = '';

  const mixerTitle = (mixerType === 'mixer_speed_small') ? 'TRỤC NHỎ' : 'TRỤC LỚN';

  // HÀNG TIÊU ĐỀ 1: TRỤC TRỘN & CÁC SIZE ỐNG
  let tr1 = `<tr>
    <th class="sticky-col-1">${mixerTitle}</th>
    <th class="sticky-col-2">Màu</th>`;
  
  // Gom nhóm Size ống để merge colspan
  const sizeMap = {};
  columns.forEach(col => {
    sizeMap[col.pipe_size] = (sizeMap[col.pipe_size] || 0) + 1;
  });

  for (const [size, count] of Object.entries(sizeMap)) {
    tr1 += `<th colspan="${count}">${size}</th>`;
  }
  tr1 += `</tr>`;

  // HÀNG TIÊU ĐỀ 2: TỐC ĐỘ KÉO (m/min)
  let tr2 = `<tr>
    <th class="sticky-col-1">#</th>
    <th class="sticky-col-2">Mã</th>`;
  columns.forEach(col => {
    tr2 += `<th>${col.hauler_speed} m/p</th>`;
  });
  tr2 += `</tr>`;

  thead.innerHTML = tr1 + tr2;

  // DỰNG 28 DÒNG MÃ MÀU
  COLOR_CODES.forEach((colorCode, idx) => {
    let tr = `<tr>
      <td class="sticky-col-1 fw-bold text-muted">${idx + 1}</td>
      <td class="sticky-col-2">${colorCode}</td>`;

    columns.forEach(col => {
      // Key tra cứu ghép từ Size_Speed_Color
      const key = `${col.pipe_size}_${col.hauler_speed}_${colorCode}`;
      const val = matrixData[key];

      if (val === null || val === undefined || val.toString().trim() === '') {
        // Ô ĐANG CHỜ CẬP NHẬT -> TÔ MÀU VÀNG
        tr += `<td class="cell-pending" title="Đang chờ cập nhật">Chờ</td>`;
      } else if (val.toString().trim() === '-') {
        // Ô KHÔNG SỬ DỤNG -> TÔ MÀU XÁM ĐEN
        tr += `<td class="cell-unused" title="Không sử dụng">-</td>`;
      } else {
        // Ô CÓ THÔNG SỐ CỤ THỂ
        tr += `<td class="cell-active">${val}</td>`;
      }
    });

    tr += `</tr>`;
    tbody.innerHTML += tr;
  });
}
</script>