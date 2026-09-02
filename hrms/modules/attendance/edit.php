<h1 class="mb-4"><i class="bi bi-pencil"></i> Chỉnh sửa điểm danh</h1>

<?php
$id = $_GET['id'] ?? 0;
$result = $conn->query("
    SELECT a.*, e.full_name, e.employee_id
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.id = $id
");

if ($result->num_rows == 0) {
    echo '<div class="alert alert-danger">Bản ghi không tìm thấy!</div>';
    exit;
}

$att = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $status = $_POST['status'] ?? 'Present';
    $notes = $_POST['notes'] ?? '';

    $sql = "UPDATE attendance SET check_in = ?, check_out = ?, status = ?, notes = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssi", $check_in, $check_out, $status, $notes, $id);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Cập nhật thành công!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
        $stmt->close();
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
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($att['full_name']) . ' (' . htmlspecialchars($att['employee_id']) . ')'; ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày</label>
                            <input type="date" class="form-control" value="<?php echo htmlspecialchars($att['date']); ?>" disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Giờ vào</label>
                            <input type="time" class="form-control" name="check_in" value="<?php echo htmlspecialchars($att['check_in']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giờ ra</label>
                            <input type="time" class="form-control" name="check_out" value="<?php echo htmlspecialchars($att['check_out']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="Present" <?php echo $att['status'] == 'Present' ? 'selected' : ''; ?>>Có mặt</option>
                                <option value="Absent" <?php echo $att['status'] == 'Absent' ? 'selected' : ''; ?>>Vắng</option>
                                <option value="Late" <?php echo $att['status'] == 'Late' ? 'selected' : ''; ?>>Muộn</option>
                                <option value="Half Day" <?php echo $att['status'] == 'Half Day' ? 'selected' : ''; ?>>Nửa ngày</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($att['notes']); ?></textarea>
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
