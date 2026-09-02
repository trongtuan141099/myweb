<!-- Add Employee Form -->
<h1 class="mb-4"><i class="bi bi-plus-circle"></i> Thêm nhân viên mới</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';
    $join_date = $_POST['join_date'] ?? '';
    $salary = $_POST['salary'] ?? 0;
    $status = $_POST['status'] ?? 'Active';

    if (!empty($employee_id) && !empty($full_name) && !empty($email)) {
        $sql = "INSERT INTO employees (employee_id, full_name, email, phone, dob, gender, position, department, join_date, salary, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssssds", $employee_id, $full_name, $email, $phone, $dob, $gender, $position, $department, $join_date, $salary, $status);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Thêm nhân viên thành công!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> Lỗi: ' . htmlspecialchars($stmt->error) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
            $stmt->close();
        }
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> Vui lòng điền các trường bắt buộc!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> Thông tin nhân viên
            </div>
            <div class="card-body">
                <form method="POST" id="employeeForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã nhân viên <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" name="employee_id" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tên đầy đủ <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email <span style="color: red;">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điện thoại</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" name="dob">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select class="form-select" name="gender">
                                <option value="">-- Chọn --</option>
                                <option value="Male">Nam</option>
                                <option value="Female">Nữ</option>
                                <option value="Other">Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phòng ban</label>
                            <select class="form-select" name="department">
                                <option value="">-- Chọn phòng ban --</option>
                                <?php
                                $result = $conn->query("SELECT name FROM departments ORDER BY name");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vị trí</label>
                            <input type="text" class="form-control" name="position" placeholder="vd: Kỹ sư phần mềm">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày tuyển</label>
                            <input type="date" class="form-control" name="join_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lương cơ bản (VND)</label>
                            <input type="number" class="form-control" name="salary" min="0" step="100000">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="Active">Hoạt động</option>
                                <option value="Inactive">Không hoạt động</option>
                                <option value="On Leave">Đang nghỉ</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Lưu
                        </button>
                        <a href="index.php?mainpage=employees&subpage=list" class="btn btn-secondary">
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
    // Validate email
    $('input[name="email"]').on('blur', function(){
        var email = $(this).val();
        if (email) {
            $.ajax({
                url: 'api/check_email.php',
                method: 'POST',
                data: {email: email},
                success: function(response) {
                    if (response == '1') {
                        alert('Email này đã được sử dụng!');
                        $('input[name="email"]').val('');
                    }
                }
            });
        }
    });

    // Auto fill date today
    $('input[name="join_date"]').val(new Date().toISOString().split('T')[0]);
});
</script>
