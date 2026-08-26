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
  <?php include '../php/config.php'; ?>
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
            /* 1. Khung chứa chính tối ưu không gian */
            .document-page-wrapper {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .document-card {
                width: 100%;
                padding: 14px 16px;
                border: 1px solid var(--border-color, #e2e8f0);
                border-radius: 8px;
                background: var(--bg-card, #ffffff);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            }

            .section-heading-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 12px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .section-heading {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                color: #173b73;
                font-size: 14px;
                font-weight: 700;
            }
            .section-heading .material-icons { color: #176b9d; font-size: 18px; }

            /* 2. Cấu hình Form Input & Button */
            .document-card label {
                display: block;
                margin-bottom: 4px;
                color: var(--text-muted, #64748b);
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            .document-card .form-control, 
            .document-card .form-select {
                height: 36px;
                padding: 4px 10px;
                font-size: 12px;
                border-color: var(--border-color, #e2e8f0);
                border-radius: 6px;
                background-color: var(--bg-main, #f8fafc);
            }

            .document-card .form-control:focus, 
            .document-card .form-select:focus {
                border-color: #176b9d;
                box-shadow: 0 0 0 2px rgba(23, 107, 157, 0.15);
                background-color: #fff;
            }

            .upload-button {
                height: 36px;
                border: 0;
                border-radius: 6px;
                background: #176b9d;
                font-size: 12px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
            }
            .upload-button:hover { background: #12577f; }

            /* 3. Thanh tìm kiếm & lọc dữ liệu */
            .search-filter-box {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* 4. Tối ưu bảng */
            .table-container-custom {
                width: 100%;
                overflow-x: auto;
                border: 1px solid var(--border-color, #e2e8f0);
                border-radius: 6px;
            }

            .document-table {
                width: 100%;
                margin: 0;
                font-size: 12px;
                border-collapse: collapse;
            }

            .document-table thead th {
                padding: 8px 10px;
                color: var(--text-muted, #64748b);
                background: #f1f5f9;
                border: 1px solid var(--border-color, #e2e8f0);
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .document-table tbody td {
                padding: 8px 10px;
                border: 1px solid var(--border-color, #e2e8f0);
                color: var(--text-main, #0f172a);
                vertical-align: middle;
            }

            .document-table tbody tr:hover { background: #f8fafc; }

            .action-link { padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; }
        </style>

        <div class="document-page-wrapper">
            <!-- CARD 1: FORM CẬP NHẬT TÀI LIỆU NẰM HOÀN TOÀN TRÊN 1 HÀNG NGANG -->
            <div class="document-card">
                <h4 class="section-heading mb-2">
                    <span class="material-icons">cloud_upload</span>Thêm tài liệu mới
                </h4>
                <form action="api/upload_doc.php" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <!-- Div 1: Mã tài liệu -->
                    <div class="col-md-2">
                        <label>Mã tài liệu</label>
                        <input type="text" name="doc_code" class="form-control" placeholder="VD: PMW-05703" required>
                    </div>
                    <!-- Div 2: Tên tài liệu -->
                    <div class="col-md-3">
                        <label>Tên tài liệu</label>
                        <input type="text" name="doc_title" class="form-control" placeholder="Nhập tên tài liệu..." required>
                    </div>
                    <!-- Div 3: Loại tài liệu -->
                    <div class="col-md-3">
                        <label>Loại tài liệu</label>
                        <select name="doc_type" class="form-select" required>
                            <option value="Hướng dẫn công việc">Hướng dẫn công việc</option>
                            <option value="Tiêu chuẩn sản phẩm">Tiêu chuẩn sản phẩm</option>
                            <option value="Phiếu kiểm tra">Phiếu kiểm tra</option>
                            <option value="Mục tiêu hiện trường">Mục tiêu hiện trường</option>
                            <option value="Điều kiện sản xuất">Điều kiện sản xuất</option>
                            <option value="Báo cáo NC">Báo cáo NC</option>
                            <option value="Phiếu đề xuất cải tiến">Phiếu đề xuất cải tiến</option>
                            <option value="Sơ đồ chất lượng">Sơ đồ chất lượng</option>
                        </select>
                    </div>
                    <!-- Div 4: Chọn File PDF -->
                    <div class="col-md-2">
                        <label>Tệp PDF</label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                    </div>
                    <!-- Div 5: Nút Tải lên -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 upload-button">
                            <span class="material-icons" style="font-size:16px;">upload</span> Tải lên
                        </button>
                    </div>
                </form>
            </div>

            <!-- CARD 2: DANH SÁCH TÀI LIỆU TÍCH HỢP TÌM KIẾM TRỰC TIẾP -->
            <div class="document-card">
                <div class="section-heading-bar">
                    <h4 class="section-heading">
                        <span class="material-icons">inventory_2</span>Danh sách tài liệu
                    </h4>
                    
                    <!-- Ô TÌM KIẾM VÀ LỌC TÀI LIỆU -->
                    <div class="search-filter-box">
                        <input type="text" id="adminSearchInput" class="form-control" style="width: 220px;" placeholder="Tìm theo tên/mã tài liệu..." onkeyup="filterAdminDocs()">
                        <select id="adminFilterType" class="form-select" style="width: 180px;" onchange="filterAdminDocs()">
                            <option value="">-- Tất cả loại --</option>
                            <option value="Hướng dẫn công việc">Hướng dẫn công việc</option>
                            <option value="Tiêu chuẩn sản phẩm">Tiêu chuẩn sản phẩm</option>
                            <option value="Phiếu kiểm tra">Phiếu kiểm tra</option>
                            <option value="Mục tiêu hiện trường">Mục tiêu hiện trường</option>
                            <option value="Điều kiện sản xuất">Điều kiện sản xuất</option>
                            <option value="Báo cáo NC">Báo cáo NC</option>
                            <option value="Phiếu đề xuất cải tiến">Phiếu đề xuất cải tiến</option>
                            <option value="Sơ đồ chất lượng">Sơ đồ chất lượng</option>
                        </select>
                    </div>
                </div>

                <div class="table-container-custom">
                    <table class="table table-hover align-middle document-table" id="adminDocTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Mã tài liệu</th>
                                <th>Tên tài liệu</th>
                                <th>Loại tài liệu</th>
                                <th>Đường dẫn File</th>
                                <th>Ngày tạo</th>
                                <th class="text-center" style="width: 130px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM documents ORDER BY id DESC");
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                            ?>
                                <tr class="doc-row">
                                    <td class="text-center"><b><?= $row['id'] ?></b></td>
                                    <td class="col-code"><span class="badge bg-secondary"><?= htmlspecialchars($row['document_code']) ?></span></td>
                                    <td class="col-title"><b><?= htmlspecialchars($row['title']) ?></b></td>
                                    <td class="col-type"><span class="badge bg-info text-dark"><?= $row['doc_type'] ?></span></td>
                                    <td><small class="text-muted file-name d-inline-block" title="<?= htmlspecialchars($row['file_path']) ?>"><?= htmlspecialchars($row['file_path']) ?></small></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['uploaded_at'])) ?></td>
                                    <td class="text-center">
                                        <a href="document_viewer.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary action-link">
                                            <span class="material-icons" style="font-size:13px;vertical-align:-2px">visibility</span> Xem
                                        </a>
                                        <a href="api/delete_doc.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger action-link" onclick="return confirm('Xóa tài liệu này khỏi Server?')">
                                            <span class="material-icons" style="font-size:13px;vertical-align:-2px">delete_outline</span> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted py-3">Chưa có tài liệu nào trong hệ thống.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
        // JavaScript Lọc và Tìm kiếm tức thì không làm tải lại trang
        function filterAdminDocs() {
            let keyword = document.getElementById('adminSearchInput').value.toLowerCase();
            let typeFilter = document.getElementById('adminFilterType').value.toLowerCase();
            let rows = document.querySelectorAll('#adminDocTable .doc-row');

            rows.forEach(row => {
                let code = row.querySelector('.col-code').textContent.toLowerCase();
                let title = row.querySelector('.col-title').textContent.toLowerCase();
                let type = row.querySelector('.col-type').textContent.toLowerCase();

                let matchesSearch = code.includes(keyword) || title.includes(keyword);
                let matchesType = (typeFilter === "") || type.includes(typeFilter);

                if (matchesSearch && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
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