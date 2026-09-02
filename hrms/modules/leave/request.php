<h1 class="mb-4"><i class="bi bi-plus-circle"></i> Đăng ký nghỉ phép</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!empty($employee_id) && !empty($leave_type) && !empty($start_date) && !empty($end_date)) {
        $sql = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status)
                VALUES (?, ?, ?, ?, ?, 'Pending')";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $employee_id, $leave_type, $start_date, $end_date, $reason);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Đơn xin nghỉ đã được gửi thành công!
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
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar2-event"></i> Thông tin đơn xin nghỉ
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên <span style="color: red;">*</span></label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">-- Chọn --</option>
                                <?php
                                $result = $conn->query("
                                    SELECT id, employee_id, full_name
                                    FROM employees
                                    WHERE status = 'Active'
                                    ORDER BY full_name
                                ");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['full_name']) . ' (' . htmlspecialchars($row['employee_id']) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại nghỉ <span style="color: red;">*</span></label>
                            <select class="form-select" name="leave_type" required>
                                <option value="">-- Chọn loại --</option>
                                <option value="Annual Leave">Nghỉ phép thường niên</option>
                                <option value="Sick Leave">Nghỉ ốm</option>
                                <option value="Personal Leave">Nghỉ cá nhân</option>
                                <option value="Maternity Leave">Nghỉ thai sản</option>
                                <option value="Unpaid Leave">Nghỉ không lương</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Từ ngày <span style="color: red;">*</span></label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Đến ngày <span style="color: red;">*</span></label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Lý do</label>
                            <textarea class="form-control" name="reason" rows="4" placeholder="Nhập lý do xin nghỉ..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Gửi đơn
                        </button>
                        <a href="index.php?mainpage=leave&subpage=list" class="btn btn-secondary">
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
    $('input[name="start_date"]').on('change', function(){
        var startDate = new Date($(this).val());
        $('input[name="end_date"]').attr('min', $(this).val());
    });
});
</script>
