<!-- Check-in Form -->
<h1 class="mb-4"><i class="bi bi-clock"></i> Điểm danh</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $status = $_POST['status'] ?? 'Present';
    $notes = $_POST['notes'] ?? '';

    if (!empty($employee_id)) {
        // Check if employee exists
        $emp_check = $conn->query("SELECT id FROM employees WHERE id = $employee_id");

        if ($emp_check->num_rows > 0) {
            // Check if attendance record exists for this date
            $att_check = $conn->query("SELECT id FROM attendance WHERE employee_id = $employee_id AND date = '$date'");

            if ($att_check->num_rows > 0) {
                // Update
                $sql = "UPDATE attendance SET check_in = ?, check_out = ?, status = ?, notes = ? WHERE employee_id = ? AND date = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssis", $check_in, $check_out, $status, $notes, $employee_id, $date);
            } else {
                // Insert
                $sql = "INSERT INTO attendance (employee_id, date, check_in, check_out, status, notes) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssss", $employee_id, $date, $check_in, $check_out, $status, $notes);
            }

            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Lưu điểm danh thành công!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> Lỗi: ' . htmlspecialchars($stmt->error) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> Nhân viên không tồn tại!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> Vui lòng chọn nhân viên!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard-check"></i> Thông tin điểm danh
            </div>
            <div class="card-body">
                <form method="POST" id="checkinForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày <span style="color: red;">*</span></label>
                            <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên <span style="color: red;">*</span></label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">-- Chọn nhân viên --</option>
                                <?php
                                $result = $conn->query("
                                    SELECT id, employee_id, full_name, position
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
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Giờ vào</label>
                            <input type="time" class="form-control" name="check_in">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giờ ra</label>
                            <input type="time" class="form-control" name="check_out">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="Present" selected>Có mặt</option>
                                <option value="Absent">Vắng</option>
                                <option value="Late">Muộn</option>
                                <option value="Half Day">Nửa ngày</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Nhập ghi chú nếu cần..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Lưu
                        </button>
                        <a href="index.php?mainpage=attendance&subpage=list" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Lịch sử điểm danh hôm nay
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tên nhân viên</th>
                                <th>Giờ vào</th>
                                <th>Giờ ra</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            $result = $conn->query("
                                SELECT e.full_name, a.check_in, a.check_out, a.status
                                FROM attendance a
                                JOIN employees e ON a.employee_id = e.id
                                WHERE a.date = '$today'
                                ORDER BY e.full_name
                            ");

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $statusBadge = '';
                                    switch($row['status']) {
                                        case 'Present':
                                            $statusBadge = '<span class="badge badge-success">Có mặt</span>';
                                            break;
                                        case 'Absent':
                                            $statusBadge = '<span class="badge badge-danger">Vắng</span>';
                                            break;
                                        case 'Late':
                                            $statusBadge = '<span class="badge badge-warning">Muộn</span>';
                                            break;
                                        default:
                                            $statusBadge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status']) . '</span>';
                                    }

                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                                    echo '<td>' . ($row['check_in'] ? date('H:i', strtotime($row['check_in'])) : '-') . '</td>';
                                    echo '<td>' . ($row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '-') . '</td>';
                                    echo '<td>' . $statusBadge . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" class="text-center text-muted">Chưa có ai điểm danh</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Auto set current time
    $('input[name="check_in"]').on('focus', function(){
        if (!$(this).val()) {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            $(this).val(hours + ':' + minutes);
        }
    });
});
</script>
