<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Danh sách nhân viên</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <!-- <link rel="stylesheet" href="../css/dashboard1.css"> -->
  <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
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


        <div class="col-12 employee-management">
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
                    
                    <div class="d-flex justify-content-between align-items-center w-100 py-2">
                      <!-- Ô tìm kiếm bo tròn bên trái -->
                      <div class="input-group rounded-pill border bg-light" style="max-width: 550px;">
                        <span class="input-group-text bg-transparent border-0 pe-1 text-muted">
                          <i class="bi bi-search"></i>
                        </span>
                        <input 
                          type="text" 
                          id="employeeSearch"
                          class="form-control bg-transparent border-0 shadow-none ps-1 text-secondary" 
                          placeholder="🔎Tìm nhanh..." 
                          aria-label="Search">
                      </div>
                    </div>

                    <!-- <a class="btn btn-primary" data-bs-toggle="offcanvas" href="#offcanvasRight" role="button" aria-controls="offcanvasRight">
                      Link with href
                    </a> -->

                    <button type="submit" class="btn-action btn-action-primary text-nowrap" formaction="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                      <span class="material-icons" style="font-size:17px;vertical-align:-4px">person_add</span> Thêm mới
                    </button>

                </div>


                  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
                    <div class="offcanvas-header">
                      <h5 class="offcanvas-title" id="offcanvasRightLabel">Thêm mới nhân viên</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                      <form method="post">
                        <div>
                            <div class="mb-3">
                              <label for="employee_code">Mã số nhân viên <span>*</span></label>
                              <input id="employee_code" type="text" name="employee_code" class="form-control" placeholder="Ví dụ: NV001" required>
                            </div>
                            <div class="mb-3">
                              <label for="full_name">Họ và tên <span>*</span></label>
                              <input id="full_name" type="text" name="full_name" class="form-control" placeholder="Nhập họ và tên" required>
                            </div>
                            <div class="mb-3">
                              <label for="hire_date">Ngày vào công ty <span>*</span></label>
                              <input id="hire_date" type="date" name="hire_date" class="form-control" required>
                            </div>
                            <div class="employee-actions">
                              <button type="submit" class="btn-action btn-action-primary text-nowrap" formaction="../php/add_employee.php">
                                <span class="material-icons" style="font-size:17px;vertical-align:-4px">person_add</span> Thêm mới
                              </button>
                            </div>
                        </div>
                      </form>
                    </div>
                </div>









      </div>
  </div>

  <!-- Bảng dữ liệu hiển thị -->
  <div class="table-responsive-custom">
    <table class="table-custom">
      <thead>
        <tr>
          <th style="width: 70px;">#</th>
          <th>Mã nhân viên</th>
          <th>Họ và tên</th>
          <th>Giới tính</th>
          <th>Cấp bậc</th>
          <th>Cost center</th>
          <th>Ngày vào công ty</th>
          <th>Ngày nghỉ việc</th>
          <th style="width: 150px; text-align: center;">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          $sql = "SELECT * FROM employees ORDER BY hire_date DESC";
          $result = $conn->query($sql);
          if ($result && $result->num_rows > 0) {
              $stt = 1;
              while($row = $result->fetch_assoc()) {
        ?>
          <tr>
            <td><span class="badge-id"><?= $stt++; ?></span></td>
            <td><strong><?= htmlspecialchars($row["employee_code"]); ?></strong></td>
            <td><?= htmlspecialchars($row["full_name"]); ?></td>
            <td><?= htmlspecialchars($row["gender"]); ?></td>
            <td><?= htmlspecialchars($row["job_level"]); ?></td>
            <td><?= htmlspecialchars($row["cost_center"]); ?></td>
            <td><?= htmlspecialchars($row["hire_date"]); ?></td>
            <td><?= htmlspecialchars($row["resignation_date"]); ?></td>
            <td style="text-align: center;">
              <a href="pages/sua.php?id=<?= $row["employee_code"]; ?>" class="btn-tbl-sm btn-tbl-edit">Sửa</a>
              <a href="pages/xoa.php?id=<?= $row["employee_code"]; ?>" class="btn-tbl-sm btn-tbl-delete" onclick="return confirm('Xác nhận xóa nhân viên này?');">Xóa</a>
            </td>
          </tr>
        <?php
              }
          } else {
              echo '<tr><td colspan="9" style="text-align:center; color:#94a3b8; padding: 20px;">Chưa có dữ liệu nhân viên.</td></tr>';
          }
          $conn->close();
        ?>
      </tbody>
    </table>
  </div>

                

      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

  <script>
    document.getElementById('employeeSearch').addEventListener('input', function () {
      const keyword = this.value.trim().toLowerCase();
      const rows = document.querySelectorAll('.table-custom tbody tr');

      rows.forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
      });
    });
  </script>

</body>
</html>