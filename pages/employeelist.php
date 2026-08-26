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
  <link rel="stylesheet" href="../css/employee.css">
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


        <div class="col-10 employee-management">
                <style>
                  .employee-form-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}
                  .employee-form-title{margin:0;color:#172033;font-size:20px;font-weight:700}
                  .employee-form-subtitle{margin:5px 0 0;color:#64748b;font-size:13px}
                  .employee-form-icon{display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:#e8f0ff;color:#2563eb}
                  .employee-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;align-items:end}
                  .employee-field{display:flex;flex-direction:column;gap:8px}
                  .employee-field label{color:#334155;font-size:13px;font-weight:600}
                  .employee-field label span{color:#ef4444}
                  .employee-field .form-control{height:44px;border:1px solid #dbe3ef;border-radius:9px;padding:0 13px;background:#f8fafc;transition:.2s}
                  .employee-field .form-control:focus{outline:0;border-color:#3b82f6;background:#fff;box-shadow:0 0 0 3px #dbeafe}
                  .employee-actions{display:flex;gap:10px}
                  .employee-actions .btn-action{height:44px;flex:1;border:0;border-radius:9px;font-weight:600;cursor:pointer;transition:.2s}
                  .employee-actions .btn-action:hover{transform:translateY(-1px);filter:brightness(.96)}
                  @media(max-width:900px){.employee-form-grid{grid-template-columns:1fr 1fr}.employee-actions{grid-column:1/-1}}
                  @media(max-width:560px){.employee-form-grid{grid-template-columns:1fr}}
                </style>
                <div class="admin-card">
                  <div class="employee-form-header">
                    <div>
                      <h2 class="employee-form-title">Quản lý nhân viên</h2>
                      <p class="employee-form-subtitle">Thêm mới hoặc cập nhật thông tin nhân sự</p>
                    </div>
                    <div class="employee-form-icon"><span class="material-icons">badge</span></div>
                  </div>
                  <form method="post">
                    <div class="employee-form-grid">
                      <div class="employee-field">
                        <label for="msnv">Mã số nhân viên <span>*</span></label>
                        <input id="msnv" type="text" name="msnv" class="form-control" placeholder="Ví dụ: NV001" required>
                      </div>
                      <div class="employee-field">
                        <label for="ten">Họ và tên <span>*</span></label>
                        <input id="ten" type="text" name="ten" class="form-control" placeholder="Nhập họ và tên" required>
                      </div>
                      <div class="employee-field">
                        <label for="ngayvao">Ngày vào công ty <span>*</span></label>
                        <input id="ngayvao" type="date" name="ngayvao" class="form-control" required>
                      </div>
                      <div class="employee-actions">
                        <button type="submit" class="btn-action btn-action-primary text-nowrap" formaction="pages/themmoi.php">
                          <span class="material-icons" style="font-size:17px;vertical-align:-4px">person_add</span> Thêm mới
                        </button>
                        <button type="submit" class="btn-action btn-action-success text-nowrap" formaction="pages/capnhat.php">
                          <span class="material-icons" style="font-size:17px;vertical-align:-4px">save</span> Cập nhật
                        </button>
                      </div>
                    </div>
    </form>
  </div>

  <!-- Bảng dữ liệu hiển thị -->
  <div class="table-responsive-custom">
    <table class="table-custom">
      <thead>
        <tr>
          <th style="width: 70px;">#</th>
          <th>Mã nhân viên</th>
          <th>Họ và tên</th>
          <th>Ngày vào Cty</th>
          <th style="width: 150px; text-align: center;">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          $sql = "SELECT * FROM nhanvien";
          $result = $conn->query($sql);
          if ($result && $result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
        ?>
          <tr>
            <td><span class="badge-id"><?= htmlspecialchars($row["id"]); ?></span></td>
            <td><strong><?= htmlspecialchars($row["msnv"]); ?></strong></td>
            <td><?= htmlspecialchars($row["hoten"]); ?></td>
            <td><?= htmlspecialchars($row["ngayvao"]); ?></td>
            <td style="text-align: center;">
              <a href="pages/sua.php?id=<?= $row["id"]; ?>" class="btn-tbl-sm btn-tbl-edit">Sửa</a>
              <a href="pages/xoa.php?id=<?= $row["id"]; ?>" class="btn-tbl-sm btn-tbl-delete" onclick="return confirm('Xác nhận xóa nhân viên này?');">Xóa</a>
            </td>
          </tr>
        <?php
              }
          } else {
              echo '<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding: 20px;">Chưa có dữ liệu nhân viên.</td></tr>';
          }
          $conn->close();
        ?>
      </tbody>
    </table>
  </div>
</div>
                

      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

</body>
</html>