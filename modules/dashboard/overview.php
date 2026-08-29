<main class="dashboard-body">
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
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Chất lượng 2026', '/myweb/documents/QAR-00344-01 2026 mục tiêu chất lượng.pdf')">
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
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Môi trường 2026', '/myweb/documents/ISO_Environmental Target_Plastic Extrusion_24 Jun 2026.pdf')">
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
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu An toàn 2026', '/myweb/documents/HIRAC - PLASTIC_EXTRUSION_2026.pdf')">
        <span class="material-icons">visibility</span> Xem PDF
      </button>
    </div>
  </div>
</main>

<!-- POP-UP MODAL XEM TRỰC TIẾP TÀI LIỆU PDF (NẰM NGOÀI MAIN ĐỂ NỔI TOÀN MÀN HÌNH) -->
<div class="modal fade" id="targetPdfModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <!-- Header thanh màu xanh navy đậm + Nút X ở góc bên phải -->
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2">
          <span class="material-icons" style="font-size: 19px;">description</span>
          <span id="modalDocTitle">Mục tiêu</span>
        </h6>
        <!-- Nút đóng dạng Dấu X ở góc phải chuẩn thiết kế -->
        <button type="button" class="btn-close-custom" onclick="closePdfModal()" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>
      
      <!-- Khung hiển thị PDF -->
      <div class="modal-body p-0">
        <iframe id="modalPdfViewer" src="about:blank"></iframe>
      </div>
    </div>
  </div>
</div>

<style>
/* CSS NỀN TẢNG DASHBOARD BODY */
.dashboard-body { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 12px; }

/* LAYOUT 3 THẺ NẰM HÀNG NGANG */
.target-grid-wrapper { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 16px !important; width: 100% !important; box-sizing: border-box !important; }
@media (max-width: 992px) { .target-grid-wrapper { grid-template-columns: 1fr !important; } }

.target-card { position: relative; border-radius: 12px; padding: 16px 18px; display: flex !important; flex-direction: row !important; align-items: center; gap: 14px; color: #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden; }
.target-quality { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
.target-env { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); }
.target-safety { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }

.target-icon { width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 10px; display: grid; place-items: center; flex-shrink: 0; }
.target-icon .material-icons { font-size: 26px; color: #ffffff; }

.target-info { flex: 1; min-width: 0; }
.target-label { font-size: 10px; font-weight: 800; opacity: 0.85; display: block; }
.target-title { margin: 2px 0 0; font-size: 15px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.target-sub { margin: 0; font-size: 11px; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.btn-view-target { background: rgba(255, 255, 255, 0.25); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.4); padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; cursor: pointer; }
.btn-view-target:hover { background: #ffffff; color: #0f172a !important; }

/* ==========================================
   CSS POPUP FULL CHE MỜ MÀN HÌNH CHUẨN MẪU
   ========================================== */
#targetPdfModal {
position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 999999 !important;
  /* Dùng màu đen trong suốt (0.65) để nhìn xuyên thấu chữ phía sau */
  background-color: rgba(0, 0, 0, 0.5) !important; 
}

#targetPdfModal.modal:not(.show) { 
  display: none !important; 
}

/* Khung Popup chính */
#targetPdfModal .modal-dialog {
  max-width: 92vw !important;
  width: 92vw !important;
  height: 88vh !important;
  margin: 6vh auto !important;
}

#targetPdfModal .modal-content {
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  border-radius: 8px !important;
  overflow: hidden !important;
  border: none !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4) !important;
}

/* Thanh Header màu Xanh Navy đậm */
#targetPdfModal .modal-header {
  background-color: #173b73 !important;
  color: #ffffff !important;
  height: 44px !important;
  min-height: 44px !important;
  padding: 0 16px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  border-bottom: none !important;
}

#targetPdfModal .modal-title {
  color: #ffffff !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  margin: 0 !important;
}

/* Nút Tắt (Icon Dấu X màu trắng ở góc phải) */
.btn-close-custom {
  background: transparent !important;
  border: none !important;
  color: #ffffff !important;
  padding: 4px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  border-radius: 4px !important;
  transition: background 0.2s !important;
}

.btn-close-custom:hover {
  background: rgba(255, 255, 255, 0.2) !important;
}

.btn-close-custom .material-icons {
  font-size: 22px !important;
  color: #ffffff !important;
}

/* Phần hiển thị nội dung file PDF */
#targetPdfModal .modal-body {
  flex: 1 !important;
  height: calc(100% - 44px) !important;
  background-color: #323639 !important;
}

#targetPdfModal #modalPdfViewer {
  width: 100% !important;
  height: 100% !important;
  border: none !important;
  display: block !important;
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