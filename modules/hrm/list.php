
    <!-- Employee List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people"></i> Danh sách nhân viên</h1>
        <a href="index.php?mainpage=employees&subpage=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Thêm mới
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Form -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên hoặc mã nhân viên...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="departmentFilter">
                        <option value="">Tất cả phòng ban</option>
                        <?php
                        // $result = $conn->query("SELECT id, name FROM departments ORDER BY name");
                        // while ($row = $result->fetch_assoc()) {
                        //     echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                        // }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active">Hoạt động</option>
                        <option value="Inactive">Không hoạt động</option>
                        <option value="On Leave">Đang nghỉ</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Mã nhân viên</th>
                            <th>Họ và tên</th>
                            <th>Giới tính</th>
                            <th>Cấp bậc</th>
                            <th>Cost center</th>
                            <th>Ngày vào công ty</th>
                            <th>Ngày nghỉ việc</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTable">
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
        </div>
    </div>

    <script>
    $(document).ready(function(){
        // Search functionality
        $("#searchInput").on("keyup", function(){
            var searchTerm = $(this).val().toLowerCase();
            filterTable();
        });

        // Filter by department
        $("#departmentFilter").on("change", function(){
            filterTable();
        });

        // Filter by status
        $("#statusFilter").on("change", function(){
            filterTable();
        });

        function filterTable() {
            var searchTerm = $("#searchInput").val().toLowerCase();
            var department = $("#departmentFilter").val().toLowerCase();
            var status = $("#statusFilter").val().toLowerCase();

            $("#employeeTable tr").filter(function(){
                var text = $(this).text().toLowerCase();
                var showRow = true;

                if (searchTerm && !text.includes(searchTerm)) {
                    showRow = false;
                }
                if (department && !text.includes(department)) {
                    showRow = false;
                }
                if (status && !text.includes(status)) {
                    showRow = false;
                }

                $(this).toggle(showRow);
            });
        }
    });

    function deleteEmployee(id) {
        if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
            window.location.href = 'api/delete_employee.php?id=' + id;
        }
    }
    </script>
