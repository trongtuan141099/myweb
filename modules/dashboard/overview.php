<div class="app-page-wrapper">
  <!-- TIÊU ĐỀ TRANG TỔNG QUAN -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">dashboard</span>
        Tổng Quan Bộ Phận
      </h1>
      <p class="app-page-subtitle">Theo dõi các mục tiêu trọng tâm năm 2026 và chỉ số nhà máy DX Plastic</p>
    </div>
  </div>

  <!-- KHU VỰC 3 MỤC TIÊU LỚN CỦA BỘ PHẬN -->
  <div class="target-grid-wrapper mb-3">
    <!-- 1. Mục tiêu Chất lượng -->
    <div class="target-card target-quality">
      <div class="target-icon">
        <span class="material-icons">verified</span>
      </div>
      <div class="target-info">
        <span class="target-label">MỤC TIÊU NĂM</span>
        <h5 class="target-title">Mục tiêu Chất lượng</h5>
        <p class="target-sub">Quality Objectives 2026</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Chất lượng 2026', 'documents/QAR-00344-01 2026 mục tiêu chất lượng.pdf')">
        <span class="material-icons">visibility</span> Xem PDF
      </button>
    </div>

    <!-- 2. Mục tiêu Môi trường -->
    <div class="target-card target-env">
      <div class="target-icon">
        <span class="material-icons">eco</span>
      </div>
      <div class="target-info">
        <span class="target-label">MỤC TIÊU NĂM</span>
        <h5 class="target-title">Mục tiêu Môi trường</h5>
        <p class="target-sub">Environmental Objectives</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Môi trường 2026', 'documents/ISO_Environmental Target_Plastic Extrusion_24 Jun 2026.pdf')">
        <span class="material-icons">visibility</span> Xem PDF
      </button>
    </div>

    <!-- 3. Mục tiêu An toàn -->
    <div class="target-card target-safety">
      <div class="target-icon">
        <span class="material-icons">health_and_safety</span>
      </div>
      <div class="target-info">
        <span class="target-label">MỤC TIÊU NĂM</span>
        <h5 class="target-title">Mục tiêu An toàn</h5>
        <p class="target-sub">Safety Objectives (ISO 45001)</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu An toàn 2026', 'documents/HIRAC - PLASTIC_EXTRUSION_2026.pdf')">
        <span class="material-icons">visibility</span> Xem PDF
      </button>
    </div>
  </div>
</div>

<!-- POP-UP MODAL XEM TRỰC TIẾP TÀI LIỆU PDF -->
<div class="modal fade" id="targetPdfModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2">
          <span class="material-icons" style="font-size: 19px;">description</span>
          <span id="modalDocTitle">Mục tiêu</span>
        </h6>
        <button type="button" class="btn-close-custom" onclick="closePdfModal()" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <iframe id="modalPdfViewer" src="about:blank"></iframe>
      </div>
    </div>
  </div>
</div>

<style>
/* CSS RIÊNG CỦA MODULE DASHBOARD OVERVIEW */
.target-grid-wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  width: 100%;
}
@media (max-width: 992px) {
  .target-grid-wrapper { grid-template-columns: 1fr; }
}

.target-card {
  position: relative;
  border-radius: var(--dx-radius-md);
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  color: #ffffff;
  box-shadow: var(--dx-shadow-sm);
  overflow: hidden;
  transition: transform 0.15s ease;
}
.target-card:hover {
  transform: translateY(-2px);
}
.target-quality { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
.target-env     { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); }
.target-safety  { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }

.target-icon {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.target-icon .material-icons { font-size: 26px; color: #ffffff; }

.target-info { flex: 1; min-width: 0; }
.target-label { font-size: 10px; font-weight: 800; opacity: 0.85; display: block; letter-spacing: 0.5px; }
.target-title { margin: 2px 0 0; font-size: 15px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.target-sub { margin: 0; font-size: 11px; opacity: 0.85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.btn-view-target {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-view-target:hover { background: #ffffff; color: var(--dx-text-main) !important; }

/* CSS Modal PDF Viewer */
#targetPdfModal .modal-dialog {
  max-width: 92vw;
  width: 92vw;
  height: 88vh;
  margin: 6vh auto;
}
#targetPdfModal .modal-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: var(--dx-radius-md);
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}
#targetPdfModal .modal-header {
  background-color: var(--dx-primary);
  color: #ffffff;
  height: 44px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.btn-close-custom {
  background: transparent;
  border: none;
  color: #ffffff;
  padding: 4px;
  display: flex;
  align-items: center;
  cursor: pointer;
  border-radius: 4px;
}
.btn-close-custom:hover { background: rgba(255, 255, 255, 0.2); }
#targetPdfModal .modal-body {
  flex: 1;
  background-color: #323639;
}
#targetPdfModal #modalPdfViewer {
  width: 100%;
  height: 100%;
  border: none;
}
</style>

<script>
let pdfModalInstance = null;

function openPdfModal(title, filePath) {
  document.getElementById('modalDocTitle').textContent = title;
  document.getElementById('modalPdfViewer').src = filePath + '#toolbar=1';
  
  const modalEl = document.getElementById('targetPdfModal');
  if (!pdfModalInstance) {
    pdfModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
  }
  pdfModalInstance.show();
}

function closePdfModal() {
  if (pdfModalInstance) {
    pdfModalInstance.hide();
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const modalElement = document.getElementById('targetPdfModal');
  if (modalElement) {
    modalElement.addEventListener('hidden.bs.modal', function () {
      document.getElementById('modalPdfViewer').src = 'about:blank';
    });
  }
});
</script>