<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Báo cáo Sản xuất - MES Dashboard v7</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <!-- <link rel="stylesheet" href="../resources/icons.css"> -->
  <!-- <link rel="stylesheet" href="../css/dashboard1.css"> -->
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/dashboard-body.css">
  <link rel="stylesheet" href="../css/footer.css">
  <!-- Nạp thư viện Bootstrap 5 JavaScript CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="../resources/bootstrap.bundle.min.js"></script> -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- <link rel="stylesheet" href="../resources/bootstrap.min.css"> -->
</head>
<body>
  <div class="app-container">
    <!-- 1. Ghép Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-wrapper">
      <!-- 2. Ghép Header -->
      <?php 
        include '../includes/header.php'; 
      ?>

      <!-- 3. Nội dung chính của trang (Main Content) -->
      <main class="dashboard-body">
        <!-- KPIS -->
        <!-- <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-header">
              <span>SẢN LƯỢNG / CA</span>
              <span class="material-icons">speed</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">450</span>
              <span class="kpi-unit">PCS / CA</span>
            </div>
            <input type="range" class="kpi-slider" value="75">
          </div>

          <div class="kpi-card">
            <div class="kpi-header">
              <span>UTILIZATION (HIỆU SUẤT)</span>
              <span class="material-icons">trending_up</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">89%</span>
              <span class="kpi-unit">MỤC TIÊU 90%</span>
            </div>
            <input type="range" class="kpi-slider" value="89">
          </div>

          <div class="kpi-card">
            <div class="kpi-header">
              <span>CA SẢN XUẤT</span>
              <span class="material-icons">schedule</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">2</span>
              <span class="kpi-unit">CA / NGÀY</span>
            </div>
            <input type="range" class="kpi-slider" value="50">
          </div>
        </div> -->

        <!-- BẢNG DỮ LIỆU KẾ HOẠCH -->
        <!-- <div class="data-card">
          <div class="filter-bar">
            <input type="month" class="form-control" value="2026-06">
            <select class="form-control">
              <option>Mã sản phẩm: MB63TD</option>
              <option>Mã sản phẩm: MB64TD</option>
            </select>
            <select class="form-control">
              <option>Nhóm Series: Cold Drawing</option>
            </select>
            <button class="btn btn-primary" style="margin-left: auto;">
              <span class="material-icons">filter_alt</span>
              <span>Lọc dữ liệu</span>
            </button>
          </div>

          <div class="table-container">
            <table class="mes-table">
              <thead>
                <tr>
                  <th style="width: 120px;">MÃ SP</th>
                  <th style="width: 100px;">KH / TT</th>
                  <th>1 (T2)</th>
                  <th>2 (T3)</th>
                  <th>3 (T4)</th>
                  <th>4 (T5)</th>
                  <th>5 (T6)</th>
                  <th>6 (T7)</th>
                  <th>7 (CN)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td rowspan="2" style="font-weight: 700;">PB63TD</td>
                  <td class="cell-kh">Kế hoạch</td>
                  <td>100</td>
                  <td>100</td>
                  <td>120</td>
                  <td>120</td>
                  <td>100</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                <tr>
                  <td class="cell-tt">Thực tế</td>
                  <td>98</td>
                  <td>102</td>
                  <td>115</td>
                  <td>120</td>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div> -->
      <!-- KHU VỰC 3 MỤC TIÊU LỚN CỦA BỘ PHẬN -->
      <div class="row g-3 mb-4">
        <!-- 1. Mục tiêu Chất lượng -->
        <div class="col-md-4">
          <div class="target-card target-quality">
            <div class="target-icon">
              <span class="material-icons">verified</span>
            </div>
            <div class="target-info">
              <span class="target-label">MỤC TIÊU NĂM</span>
              <h5 class="target-title">Mục tiêu Chất lượng</h5>
              <p class="target-sub">Quality Objectives 2026</p>
            </div>
            <!-- Nút xem trực tiếp file PDF theo ID -->
            <!-- <a href="pages/document_viewer.php?id=1" class="btn-view-target" target="_blank">
              <span class="material-icons">visibility</span> Xem PDF
            </a> -->
            <!-- Thẻ 1: Mục tiêu Chất lượng -->
            <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Chất lượng 2026', '../documents/QAR-00344-01 2026 mục tiêu chất lượng.pdf')">
              <span class="material-icons">visibility</span> Xem PDF
            </button>
          </div>
        </div>

        <!-- 2. Mục tiêu Môi trường -->
        <div class="col-md-4">
          <div class="target-card target-env">
            <div class="target-icon">
              <span class="material-icons">eco</span>
            </div>
            <div class="target-info">
              <span class="target-label">MỤC TIÊU NĂM</span>
              <h5 class="target-title">Mục tiêu Môi trường</h5>
              <p class="target-sub">Environmental Objectives</p>
            </div>
            <!-- <a href="pages/document_viewer.php?id=2" class="btn-view-target" target="_blank">
              <span class="material-icons">visibility</span> Xem PDF
            </a> -->
            <!-- Thẻ 2: Mục tiêu Môi trường -->
            <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Môi trường 2026', '../documents/ISO_Environmental Target_Plastic Extrusion_24 Jun 2026.pdf')">
              <span class="material-icons">visibility</span> Xem PDF
            </button>
          </div>
        </div>

        <!-- 3. Mục tiêu An toàn -->
        <div class="col-md-4">
          <div class="target-card target-safety">
            <div class="target-icon">
              <span class="material-icons">health_and_safety</span>
            </div>
            <div class="target-info">
              <span class="target-label">MỤC TIÊU NĂM</span>
              <h5 class="target-title">Mục tiêu An toàn</h5>
              <p class="target-sub">Safety Objectives (ISO 45001)</p>
            </div>
            <!-- <a href="pages/document_viewer.php?id=3" class="btn-view-target" target="_blank">
              <span class="material-icons">visibility</span> Xem PDF
            </a> -->
            <!-- Thẻ 3: Mục tiêu An toàn -->
          <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu An toàn 2026', '../documents/HIRAC - PLASTIC_EXTRUSION_2026.pdf')">
            <span class="material-icons">visibility</span> Xem PDF
          </button>
          </div>
        </div>
      </div>

            <!-- POP-UP MODAL XEM TRỰC TIẾP TÀI LIỆU PDF -->
    <div class="modal fade" id="targetPdfModal" tabindex="-1" aria-labelledby="targetPdfModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 85vh;">
        <div class="modal-content" style="height: 100%; border-radius: 12px; overflow: hidden;">
          
          <!-- Header của Modal -->
          <div class="modal-header py-2 px-3" style="background-color: #173b73; color: #ffffff;">
            <h6 class="modal-title d-flex align-items-center gap-2" id="targetPdfModalLabel">
              <span class="material-icons" style="font-size: 18px;">description</span>
              <span id="modalDocTitle">Xem tài liệu mục tiêu</span>
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Nội dung chứa Iframe hiển thị PDF -->
          <div class="modal-body p-0" style="background-color: #f8fafc;">
            <iframe id="modalPdfViewer" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
          </div>
          
        </div>
      </div>
    </div>

      
      <style>
              /* ==========================================
        CSS HIỂN THỊ THẺ MỤC TIÊU LỚN (KPI HERO CARDS)
        ========================================== */
      .target-card {
        position: relative;
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
      }

      .target-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      }

      /* 1. Phối màu đặc trưng cho từng mục tiêu */
      /* Chất lượng: Xanh lam đậm */
      .target-quality {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
      }
      /* Môi trường: Xanh lá cây */
      .target-env {
        background: linear-gradient(135deg, #15803d 0%, #22c55e 100%);
      }
      /* An toàn: Cam đỏ cảnh báo */
      .target-safety {
        background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
      }

      /* Icon lớn chìm */
      .target-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
      }
      .target-icon .material-icons {
        font-size: 28px;
        color: #ffffff;
      }

      /* Thông tin văn bản */
      .target-info {
        flex: 1;
      }
      .target-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.08em;
        opacity: 0.85;
      }
      .target-title {
        margin: 2px 0 0;
        font-size: 16px;
        font-weight: 700;
        color: #ffffff;
      }
      .target-sub {
        margin: 0;
        font-size: 11px;
        opacity: 0.8;
      }

      /* Nút Xem PDF nhỏ gọn */
      .btn-view-target {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        backdrop-filter: blur(4px);
        transition: background 0.2s;
      }

      .btn-view-target:hover {
        background: #ffffff;
        color: #0f172a !important;
      }
      .btn-view-target .material-icons {
        font-size: 14px;
      }


            /* ==========================================
        FIX LỖI HIỂN THỊ BOOTSTRAP MODAL
        ========================================== */
      /* Mặc định ẩn Modal khi chưa nhấp nút */
      #targetPdfModal.modal:not(.show) {
        display: none !important;
      }

      /* Cấu hình đè lớp phủ Pop-up lên trên cùng */
      #targetPdfModal {
        z-index: 1055 !important;
        background-color: rgba(0, 0, 0, 0.5); /* Nền tối mờ xung quanh Pop-up */
      }

      #targetPdfModal .modal-dialog {
        max-width: 90vw;
        margin: 1.75rem auto;
      }

      #targetPdfModal .modal-content {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      }

      /* Nút X đóng Pop-up */
      .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
      }
      </style>
      
      
      
      
      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->


    <script>
      // Hàm mở Modal và nạp đường dẫn file PDF
      function openPdfModal(title, filePath) {
        // 1. Gán tên tiêu đề tài liệu lên Header của Modal
        document.getElementById('modalDocTitle').textContent = title;
        
        // 2. Gán đường dẫn file PDF vào iframe (kèm #toolbar=1 để hiện thanh công cụ in/tải về)
        document.getElementById('modalPdfViewer').src = filePath + '#toolbar=1';
        
        // 3. Khởi tạo và hiển thị Bootstrap Modal
        const pdfModal = new bootstrap.Modal(document.getElementById('targetPdfModal'));
        pdfModal.show();
      }

      // Xử lý dọn dẹp iframe khi người dùng đóng Pop-up (tránh làm nặng bộ nhớ trình duyệt)
      document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('targetPdfModal');
        if (modalElement) {
          modalElement.addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalPdfViewer').src = 'about:blank';
          });
        }
      });

    </script>



</body>
</html>