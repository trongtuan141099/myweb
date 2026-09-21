<style>
/* CSS RIÊNG BẢNG MA TRẬN BỘ TRỘN MÀU */
.mixer-summary-wrapper {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  overflow: hidden;
}

.mixer-summary-wrapper .app-page-header {
  flex-shrink: 0;
  margin-bottom: 0;
}

.mixer-summary-wrapper .app-filter-card {
  flex-shrink: 0;
  padding: 10px 16px;
}

.matrix-card {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  margin-bottom: 0;
}

.matrix-scroll-container {
  flex: 1;
  min-height: 0;
  height: 100%;
  width: 100%;
  overflow-x: auto;
  overflow-y: auto;
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-sm);
  background-color: var(--dx-bg-card);
}

.table-matrix {
  font-size: 0.8rem;
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
}

.table-matrix th, .table-matrix td {
  border-right: 1px solid var(--dx-border);
  border-bottom: 1px solid var(--dx-border);
  padding: 6px 8px;
  text-align: center;
  white-space: nowrap;
}

/* Cố định các hàng tiêu đề trên cùng */
.table-matrix thead tr:nth-child(1) th {
  position: sticky;
  top: 0;
  z-index: 20;
  height: 32px;
  line-height: 18px;
  box-sizing: border-box;
  background: var(--dx-bg-subtle, #e2e8f0);
  color: var(--dx-text-main, #1e293b);
  font-weight: 700;
}

.table-matrix thead tr:nth-child(2) th {
  position: sticky;
  top: 32px;
  z-index: 20;
  height: 30px;
  line-height: 18px;
  box-sizing: border-box;
  background: var(--dx-bg-app, #f1f5f9);
  color: var(--dx-text-muted, #334155);
  font-weight: 700;
}

/* Cố định cột STT và Cột Mã Màu bên trái */
.sticky-col-head {
  position: sticky;
  left: 0;
  top: 0;
  z-index: 45 !important;
  width: 120px;
  min-width: 120px;
  max-width: 120px;
  box-sizing: border-box;
  background: var(--dx-bg-subtle, #cbd5e1) !important;
  color: var(--dx-text-main) !important;
  font-weight: 700;
  text-align: center;
  border-right: 2px solid var(--dx-border-strong, #94a3b8) !important;
}

.sticky-col-1 {
  position: sticky;
  left: 0;
  z-index: 30;
  width: 44px;
  min-width: 44px;
  max-width: 44px;
  box-sizing: border-box;
  background: var(--dx-bg-card, #ffffff);
  color: var(--dx-text-main);
  text-align: center;
}

.sticky-col-2 {
  position: sticky;
  left: 44px;
  z-index: 30;
  width: 76px;
  min-width: 76px;
  max-width: 76px;
  box-sizing: border-box;
  background: var(--dx-bg-card, #ffffff);
  color: var(--dx-text-main);
  font-weight: 700;
  text-align: center;
  border-right: 2px solid var(--dx-border-strong, #94a3b8) !important;
}

.table-matrix thead tr:nth-child(2) th.sticky-col-1,
.table-matrix thead tr:nth-child(2) th.sticky-col-2 {
  top: 32px;
  z-index: 40;
  background: var(--dx-bg-subtle, #cbd5e1);
  color: var(--dx-text-main);
}

/* Mã hóa màu sắc ô ma trận */
.cell-unused { background-color: #64748b !important; color: #64748b !important; user-select: none; }
.cell-pending { background-color: #fef08a !important; color: #854d0e !important; font-weight: 600; }
.cell-active { background-color: #e0f2fe; color: #0369a1; font-weight: 700; }

/* Chế độ tối cho ma trận */
[data-theme="dark"] .table-matrix thead tr:nth-child(1) th {
  background: #1e293b;
  color: #f8fafc;
}
[data-theme="dark"] .table-matrix thead tr:nth-child(2) th {
  background: #0f172a;
  color: #94a3b8;
}
[data-theme="dark"] .sticky-col-head {
  background: #1e293b !important;
  color: #f8fafc !important;
  border-right-color: #3b82f6 !important;
}
[data-theme="dark"] .sticky-col-1,
[data-theme="dark"] .sticky-col-2 {
  background: var(--dx-bg-card);
  color: #f8fafc;
}
[data-theme="dark"] .sticky-col-2 {
  border-right-color: #3b82f6 !important;
}
[data-theme="dark"] .table-matrix thead tr:nth-child(2) th.sticky-col-1,
[data-theme="dark"] .table-matrix thead tr:nth-child(2) th.sticky-col-2 {
  background: #0f172a;
  color: #f8fafc;
}
[data-theme="dark"] .cell-unused { background-color: #1f293d !important; color: #475569 !important; }
[data-theme="dark"] .cell-pending { background-color: rgba(245, 158, 11, 0.2) !important; color: #fbbf24 !important; }
[data-theme="dark"] .cell-active { background-color: rgba(59, 130, 246, 0.22) !important; color: #60a5fa !important; }
[data-theme="dark"] .legend-swatch-unused { background-color: #1f293d !important; border: 1px solid #334155; }
</style>

<div class="app-page-wrapper mixer-summary-wrapper">
  <!-- HEADER & ĐIỀU HƯỚNG -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">grid_view</span>
        Bảng Tổng Hợp Tốc Độ Trộn Màu Toàn Hệ Thống
      </h1>
      <p class="app-page-subtitle">Chế độ xem ma trận trực quan theo từng Loại bộ trộn, Size ống, Tốc độ kéo và Mã màu</p>
    </div>
    
    <div class="app-page-actions">
      <a href="index.php?mainpage=utilities&subpage=color_mixer" class="app-btn app-btn-secondary">
        <span class="material-icons">arrow_back</span> Về Trang Tra Cứu
      </a>
      <button class="app-btn app-btn-primary" onclick="fetchSummaryMatrix()">
        <span class="material-icons">refresh</span> Làm Mới Dữ Liệu
      </button>
    </div>
  </div>

  <!-- THANH BỘ LỌC ĐỊNH HƯỚNG TỔNG QUAN -->
  <div class="app-filter-card">
    <div class="row g-2 align-items-center w-100">
      <div class="col-6 col-md-3">
        <label class="app-form-label">Loại Bộ Trộn Màu</label>
        <select id="matrix_mixer_type" class="app-form-select w-100" onchange="fetchSummaryMatrix()">
          <option value="mixer_speed_small">Bộ Trộn TRỤC NHỎ</option>
          <option value="mixer_speed_large">Bộ Trộn TRỤC LỚN</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="app-form-label">Loại Ống</label>
        <select id="matrix_pipe_type" class="app-form-select w-100" onchange="fetchSummaryMatrix()">
          <option value="">-- Tất cả loại ống --</option>
          <option value="TPU">Ống TPU</option>
          <option value="NYLON-T">Ống NYLON-T</option>
        </select>
      </div>
      <!-- CHÚ THÍCH MÀU SẮC TRỰC QUAN -->
      <div class="col-12 col-md-6 d-flex justify-content-md-end gap-3 pt-2 pt-md-0 small">
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1 cell-active" style="width:14px;height:14px;border:1px solid rgba(59,130,246,0.5);"></span> Có dữ liệu RPM</span>
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1 cell-pending" style="width:14px;height:14px;border:1px solid rgba(245,158,11,0.5);"></span> Đang chờ cập nhật</span>
        <span class="d-flex align-items-center gap-1"><span class="d-inline-block rounded-1 cell-unused legend-swatch-unused" style="width:14px;height:14px;"></span> Không sử dụng (-)</span>
      </div>
    </div>
  </div>

  <!-- MA TRẬN DỮ LIỆU BẢNG TỔNG QUAN -->
  <div class="app-card matrix-card">
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

<script>
const COLOR_CODES_DEFAULT = [
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
        renderMatrix(res.columns, res.matrixData, mixerType, res.colorCodes);
      } else {
        alert(res.message || 'Lỗi nạp dữ liệu ma trận tổng quan!');
      }
    })
    .catch(err => console.error('Lỗi API Summary:', err));
}

function renderMatrix(columns, matrixData, mixerType, colorCodesList) {
  const thead = document.getElementById('matrix-head');
  const tbody = document.getElementById('matrix-body');
  thead.innerHTML = '';
  tbody.innerHTML = '';

  if (!columns || columns.length === 0) {
    thead.innerHTML = `<tr><th class="p-3 text-center">Thông báo</th></tr>`;
    tbody.innerHTML = `<tr><td class="p-4 text-center text-muted">
      <span class="material-icons d-block fs-2 mb-2">inbox</span>
      Không có dữ liệu phù hợp với bộ lọc hiện tại
    </td></tr>`;
    return;
  }

  const mixerTitle = (mixerType === 'mixer_speed_small') ? 'TRỤC NHỎ' : 'TRỤC LỚN';

  // Nhóm các cột theo pipe_size: Map { pipe_size => [speed1, speed2, ...] }
  // Đảm bảo ở mỗi size có bao nhiêu tốc độ thì đều được gom nhóm và hiển thị đầy đủ
  const sizeGroups = new Map();
  columns.forEach(col => {
    const size = col.pipe_size;
    const speedRaw = parseFloat(col.hauler_speed);
    const speed = isNaN(speedRaw) ? col.hauler_speed : speedRaw.toFixed(2);

    if (!sizeGroups.has(size)) {
      sizeGroups.set(size, []);
    }
    if (!sizeGroups.get(size).includes(speed)) {
      sizeGroups.get(size).push(speed);
    }
  });

  // HÀNG TIÊU ĐỀ 1: TRỤC TRỘN & CÁC SIZE ỐNG
  // sticky-col-head gộp STT & Màu thành 1 ô cố định rộng đúng 120px
  let tr1 = `<tr>
    <th colspan="2" class="sticky-col-head">${mixerTitle}</th>`;

  sizeGroups.forEach((speeds, size) => {
    tr1 += `<th colspan="${speeds.length}">${size}</th>`;
  });
  tr1 += `</tr>`;

  // HÀNG TIÊU ĐỀ 2: TẤT CẢ CÁC TỐC ĐỘ KÉO (m/min) THEO TỪNG SIZE
  let tr2 = `<tr>
    <th class="sticky-col-1">#</th>
    <th class="sticky-col-2">Mã Màu</th>`;

  sizeGroups.forEach((speeds) => {
    speeds.forEach(speed => {
      const displaySpeed = parseFloat(speed);
      tr2 += `<th>${displaySpeed} m/p</th>`;
    });
  });
  tr2 += `</tr>`;

  thead.innerHTML = tr1 + tr2;

  // DANH SÁCH MÃ MÀU HIỂN THỊ
  const codesToRender = (Array.isArray(colorCodesList) && colorCodesList.length > 0)
    ? colorCodesList
    : COLOR_CODES_DEFAULT;

  let bodyHtml = '';
  codesToRender.forEach((colorCode, idx) => {
    let tr = `<tr>
      <td class="sticky-col-1 fw-bold text-muted">${idx + 1}</td>
      <td class="sticky-col-2">${colorCode}</td>`;

    sizeGroups.forEach((speeds, size) => {
      speeds.forEach(speed => {
        const key = `${size}_${speed}_${colorCode}`;
        const val = matrixData[key];

        if (val === null || val === undefined || val.toString().trim() === '') {
          tr += `<td class="cell-pending" title="Đang chờ cập nhật">Chờ</td>`;
        } else if (val.toString().trim() === '-') {
          tr += `<td class="cell-unused" title="Không sử dụng">-</td>`;
        } else {
          tr += `<td class="cell-active">${val}</td>`;
        }
      });
    });

    tr += `</tr>`;
    bodyHtml += tr;
  });

  tbody.innerHTML = bodyHtml;
}

// Lắng nghe sự kiện đổi chế độ Sáng / Tối
window.addEventListener('dxThemeChanged', () => {
  fetchSummaryMatrix();
});
</script>