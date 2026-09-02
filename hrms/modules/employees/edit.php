<h1 class="mb-4"><i class="bi bi-pencil"></i> Chỉnh sửa nhân viên</h1>

<?php
$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM employees WHERE id = $id");

if ($result->num_rows == 0) {
    echo '<div class="alert alert-danger">Nhân viên không tìm thấy!</div>';
    exit;
}

$emp = $result->fetch_assoc();

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

    $sql = "UPDATE employees SET
            employee_id=?, full_name=?, email=?, phone=?, dob=?, gender=?, position=?, department=?, join_date=?, salary=?, status=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssssssdssi", $employee_id, $full_name, $email, $phone, $dob, $gender, $position, $department, $join_date, $salary, $status, $id);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Cập nhật thành công!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $emp = $_POST;
        }
        $stmt->close();
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person"></i> Thông tin nhân viên
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã nhân viên</label>
                            <input type="text" class="form-control" name="employee_id" value="<?php echo htmlspecialchars($emp['employee_id']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tên đầy đủ</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($emp['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($emp['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điện thoại</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($emp['phone']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($emp['dob']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select class="form-select" name="gender">
                                <option value="">-- Chọn --</option>
                                <option value="Male" <?php echo $emp['gender'] == 'Male' ? 'selected' : ''; ?>>Nam</option>
                                <option value="Female" <?php echo $emp['gender'] == 'Female' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="Other" <?php echo $emp['gender'] == 'Other' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phòng ban</label>
                            <select class="form-select" name="department">
                                <option value="">-- Chọn --</option>
                                <?php
                                $result = $conn->query("SELECT name FROM departments ORDER BY name");
                                while ($row = $result->fetch_assoc()) {
                                    $selected = $emp['department'] == $row['name'] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['name']) . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vị trí</label>
                            <input type="text" class="form-control" name="position" value="<?php echo htmlspecialchars($emp['position']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày tuyển</label>
                            <input type="date" class="form-control" name="join_date" value="<?php echo htmlspecialchars($emp['join_date']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lương cơ bản (VND)</label>
                            <input type="number" class="form-control" name="salary" value="<?php echo htmlspecialchars($emp['salary']); ?>" min="0">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="Active" <?php echo $emp['status'] == 'Active' ? 'selected' : ''; ?>>Hoạt động</option>
                                <option value="Inactive" <?php echo $emp['status'] == 'Inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                                <option value="On Leave" <?php echo $emp['status'] == 'On Leave' ? 'selected' : ''; ?>>Đang nghỉ</option>
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
