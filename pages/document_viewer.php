<?php 
require_once '../php/config.php'; 
// 📌 VỊ TRÍ 1: THÊM HÀM XỬ LÝ ĐƯỜNG DẪN VÀO ĐÂY
function getCorrectPath($path) {
    if (empty($path)) return '';
    // Lùi 1 cấp thư mục từ /myweb/pages/ về /myweb/documents/
    return '../' . ltrim($path, '/');
}
// Xác định file mở mặc định (lấy theo ID query hoặc file đầu tiên)
$selected_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$default_file = "";

if ($selected_id > 0) {
    $stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        // $default_file = $row['file_path'];
        $default_file = getCorrectPath($row['file_path']);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Báo cáo Sản xuất - MES Dashboard v7</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <!-- <link rel="stylesheet" href="../css/dashboard1.css"> -->
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/dashboard-body.css">
  <link rel="stylesheet" href="../css/footer.css">

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
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { background-color: #f1f5f9; height: 100vh; overflow: hidden; font-family: system-ui, sans-serif; }
                
                .doc-layout { display: flex; height: 100vh; padding: 10px; gap: 10px; }
                
                /* Sidebar Trái */
                .doc-sidebar { width: 340px; background: #fff; border-radius: 8px; border: 1px solid #cbd5e1; display: flex; flex-direction: column; flex-shrink: 0; }
                .doc-sidebar-header { background: #0265bc; color: #fff; padding: 12px 16px; font-weight: bold; font-size: 15px; border-radius: 7px 7px 0 0; display: flex; align-items: center; gap: 8px; }
                .doc-search-box { padding: 10px; border-bottom: 1px solid #e2e8f0; display: flex; gap: 6px; }
                .doc-list { flex: 1; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 6px; }
                
                /* Item Tài liệu */
                .doc-item { padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; text-decoration: none; color: #334155; font-size: 13px; line-height: 1.4; transition: all 0.15s; }
                .doc-item:hover, .doc-item.active { background: #e0f2fe; border-color: #0265bc; color: #0265bc; font-weight: 600; }
                
                /* Viewer Phải */
                .doc-viewer-container { flex: 1; background: #fff; border-radius: 8px; border: 1px solid #cbd5e1; overflow: hidden; display: flex; flex-direction: column; }
                .doc-iframe { width: 100%; height: 100%; border: none; }
            </style>

                    <div class="doc-layout">
            <!-- Cột danh sách tài liệu -->
            <div class="doc-sidebar">
                <div class="doc-sidebar-header">
                    <span>Tài liệu công việc</span>
                </div>
                <div class="doc-search-box">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Nhập tên tài liệu..." onkeyup="filterDocs()">
                    <button class="btn btn-primary btn-sm px-3">Tìm kiếm</button>
                </div>
                <div class="doc-list" id="docList">
                    <?php
                        $docs = $conn->query("SELECT * FROM documents ORDER BY id ASC");
                        $first_id = 0;
                        $first_path = "";

                        if ($docs && $docs->num_rows > 0) {
                            while($doc = $docs->fetch_assoc()) {
                                // Chuẩn hóa đường dẫn file về thư mục myweb/documents/
                                $real_path = '../' . ltrim($doc['file_path'], '/');
                                
                                if ($first_id == 0) {
                                    $first_id = $doc['id'];
                                    $first_path = $real_path;
                                }

                                // Kiểm tra xem dòng hiện tại có trùng với ID trên URL ?id=... hay không
                                $isActive = ($selected_id == $doc['id']) || ($selected_id == 0 && $first_id == $doc['id']);
                                if ($selected_id == $doc['id']) {
                                    $default_file = $real_path;
                                }
                        ?>
                            <!-- Truyền cả ID và đường dẫn File vào hàm loadPdf -->
                            <a class="doc-item <?= $isActive ? 'active' : '' ?>" 
                            onclick="loadPdf(<?= $doc['id'] ?>, '<?= htmlspecialchars($real_path, ENT_QUOTES) ?>', this)">
                                <?= $doc['id'] ?>. <?= htmlspecialchars($doc['document_code']) ?> - <?= htmlspecialchars($doc['title']) ?>.pdf
                            </a>
                        <?php 
                            }
                        }
                        ?>
                </div>
            </div>

            <!-- Khung hiển thị PDF -->
            <div class="doc-viewer-container">
                <?php $view_url = !empty($default_file) ? $default_file : $first_path; ?>
                <iframe id="pdfViewer" src="<?= !empty($view_url) ? htmlspecialchars($view_url) . '#toolbar=1' : 'about:blank' ?>" class="doc-iframe"></iframe>
            </div>
        </div>

        <!-- <script>
        function loadPdf(filePath, element) {
            document.getElementById('pdfViewer').src = filePath + '#toolbar=1';
            document.querySelectorAll('.doc-item').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
        }

        function filterDocs() {
            let filter = document.getElementById('searchInput').value.toLowerCase();
            let items = document.querySelectorAll('.doc-item');
            items.forEach(item => {
                let text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        }
        </script> -->

                <!-- 📌 MÃ JAVASCRIPT XỬ LÝ ĐỔI URL VÀ LOAD PDF -->
        <script>
        function loadPdf(id, filePath, element) {
            console.log("👉 Đường dẫn file PDF:", filePath);
            // 1. Cập nhật đường dẫn iframe hiển thị file PDF
            document.getElementById('pdfViewer').src = filePath + '#toolbar=1';
            
            // 2. Cập nhật ID lên thanh địa chỉ trình duyệt mà không làm reload/tải lại trang
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?id=' + id;
            window.history.pushState({ path: newUrl }, '', newUrl);

            // 3. Đổi trạng thái bôi xanh menu đang chọn
            document.querySelectorAll('.doc-item').forEach(item => item.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }
        }

        // Xử lý tìm kiếm tài liệu tức thì
        function filterDocs() {
            let filter = document.getElementById('searchInput').value.toLowerCase();
            let items = document.querySelectorAll('.doc-item');
            items.forEach(item => {
                let text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        }
        </script>
      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

</body>
</html>