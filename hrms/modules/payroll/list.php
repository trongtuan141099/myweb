<!-- Payroll List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-credit-card"></i> Danh sách bảng lương</h1>
    <a href="index.php?mainpage=payroll&subpage=create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tạo bảng lương
    </a>
</div>

<div class="card">
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Tháng</label>
                <input type="number" class="form-control" id="monthFilter" min="1" max="12" value="<?php echo date('m'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Năm</label>
                <input type="number" class="form-control" id="yearFilter" min="2020" value="<?php echo date('Y'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nhân viên</label>
                <input type="text" class="form-control" id="employeeFilter" placeholder="Tìm kiếm...">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-info w-100" id="filterBtn">
                    <i class="bi bi-funnel"></i> Lọc
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tên nhân viên</th>
                        <th>Mã NV</th>
                        <th>Tháng/Năm</th>
                        <th>Lương cơ bản</th>
                        <th>Phụ cấp</th>
                        <th>Khấu trừ</th>
                        <th>Lương ròng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="payrollTable">
                    <?php
                    $month = date('m');
                    $year = date('Y');

                    $result = $conn->query("
                        SELECT p.id, e.full_name, e.employee_id, p.month, p.year,
                               p.basic_salary, p.allowances, p.deductions, p.net_salary
                        FROM payroll p
                        JOIN employees e ON p.employee_id = e.id
                        WHERE p.month = $month AND p.year = $year
                        ORDER BY e.full_name
                    ");

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($row['full_name']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($row['employee_id']) . '</td>';
                            echo '<td>' . $row['month'] . '/' . $row['year'] . '</td>';
                            echo '<td>' . number_format($row['basic_salary'], 0, ',', '.') . '</td>';
                            echo '<td>' . number_format($row['allowances'], 0, ',', '.') . '</td>';
                            echo '<td>' . number_format($row['deductions'], 0, ',', '.') . '</td>';
                            echo '<td><strong>' . number_format($row['net_salary'], 0, ',', '.') . '</strong></td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-info me-2" onclick="viewPayroll(' . $row['id'] . ')" title="Xem">';
                            echo '<i class="bi bi-eye"></i></button>';
                            echo '<button class="btn btn-sm btn-warning me-2" onclick="editPayroll(' . $row['id'] . ')" title="Sửa">';
                            echo '<i class="bi bi-pencil"></i></button>';
                            echo '<button class="btn btn-sm btn-danger" onclick="deletePayroll(' . $row['id'] . ')" title="Xóa">';
                            echo '<i class="bi bi-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted">Không có dữ liệu bảng lương cho tháng/năm này</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#filterBtn").on("click", function(){
        var month = $("#monthFilter").val();
        var year = $("#yearFilter").val();
        var employee = $("#employeeFilter").val();

        window.location.href = "index.php?mainpage=payroll&subpage=list&month=" + month + "&year=" + year + "&employee=" + employee;
    });
});

function viewPayroll(id) {
    window.location.href = "index.php?mainpage=payroll&subpage=view&id=" + id;
}

function editPayroll(id) {
    window.location.href = "index.php?mainpage=payroll&subpage=edit&id=" + id;
}

function deletePayroll(id) {
    if (confirm('Bạn có chắc chắn muốn xóa?')) {
        window.location.href = "api/delete_payroll.php?id=" + id;
    }
}
</script>
