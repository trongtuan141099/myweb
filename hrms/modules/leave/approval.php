<h1 class="mb-4"><i class="bi bi-check-circle"></i> Phê duyệt nghỉ phép</h1>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Loại nghỉ</th>
                        <th>Từ - Đến</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("
                        SELECT lr.id, e.full_name, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status
                        FROM leave_requests lr
                        JOIN employees e ON lr.employee_id = e.id
                        WHERE lr.status = 'Pending'
                        ORDER BY lr.created_at
                    ");

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['leave_type']) . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['start_date'])) . ' - ' . date('d/m/Y', strtotime($row['end_date'])) . '</td>';
                            echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
                            echo '<td><span class="badge badge-warning">Chờ phê duyệt</span></td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-success" onclick="approveLeave(' . $row['id'] . ')"><i class="bi bi-check"></i> Duyệt</button>';
                            echo '<button class="btn btn-sm btn-danger" onclick="rejectLeave(' . $row['id'] . ')"><i class="bi bi-x"></i> Từ chối</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center text-muted">Không có đơn chờ phê duyệt</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function approveLeave(id) {
    if (confirm('Bạn muốn phê duyệt đơn này?')) {
        window.location.href = 'api/approve_leave.php?id=' + id + '&action=approve';
    }
}

function rejectLeave(id) {
    if (confirm('Bạn muốn từ chối đơn này?')) {
        window.location.href = 'api/approve_leave.php?id=' + id + '&action=reject';
    }
}
</script>
