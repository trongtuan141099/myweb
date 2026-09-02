<h1 class="mb-4"><i class="bi bi-plus-circle"></i> Tạo bảng lương</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $month = $_POST['month'] ?? date('m');
    $year = $_POST['year'] ?? date('Y');
    $employee_ids = $_POST['employee_ids'] ?? [];

    if (!empty($employee_ids)) {
        $success = 0;
        foreach ($employee_ids as $emp_id) {
            $result = $conn->query("SELECT salary FROM employees WHERE id = $emp_id");
            if ($result->num_rows > 0) {
                $emp = $result->fetch_assoc();
                $basic_salary = $emp['salary'];
                $allowances = $basic_salary * 0.1; // 10% allowance
                $deductions = $basic_salary * 0.15; // 15% deduction
                $net_salary = $basic_salary + $allowances - $deductions;

                $sql = "INSERT INTO payroll (employee_id, month, year, basic_salary, allowances, deductions, net_salary)
                        VALUES ($emp_id, $month, $year, $basic_salary, $allowances, $deductions, $net_salary)
                        ON DUPLICATE KEY UPDATE
                        basic_salary=$basic_salary, allowances=$allowances, deductions=$deductions, net_salary=$net_salary";

                if ($conn->query($sql)) {
                    $success++;
                }
            }
        }

        if ($success > 0) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Tạo bảng lương thành công! (' . $success . ' nhân viên)
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
}
?>

<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-table"></i> Thiết lập bảng lương
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Tháng <span style="color: red;">*</span></label>
                            <input type="number" class="form-control" name="month" min="1" max="12" value="<?php echo date('m'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Năm <span style="color: red;">*</span></label>
                            <input type="number" class="form-control" name="year" min="2020" value="<?php echo date('Y'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chọn nhân viên <span style="color: red;">*</span></label>
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                            <?php
                            $result = $conn->query("
                                SELECT id, employee_id, full_name, salary
                                FROM employees
                                WHERE status = 'Active'
                                ORDER BY full_name
                            ");

                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="form-check">';
                                echo '<input class="form-check-input" type="checkbox" name="employee_ids[]" value="' . $row['id'] . '" id="emp_' . $row['id'] . '">';
                                echo '<label class="form-check-label" for="emp_' . $row['id'] . '">';
                                echo htmlspecialchars($row['full_name']) . ' (' . htmlspecialchars($row['employee_id']) . ')';
                                echo ' - ' . number_format($row['salary'], 0, ',', '.') . ' VND';
                                echo '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-secondary" id="selectAllBtn">Chọn tất cả</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="deselectAllBtn">Bỏ chọn tất cả</button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Tạo bảng lương
                        </button>
                        <a href="index.php?mainpage=payroll&subpage=list" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#selectAllBtn").on("click", function(){
        $('input[name="employee_ids[]"]').prop('checked', true);
    });

    $("#deselectAllBtn").on("click", function(){
        $('input[name="employee_ids[]"]').prop('checked', false);
    });
});
</script>
