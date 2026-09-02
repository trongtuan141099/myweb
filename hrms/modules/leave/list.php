<h1 class="mb-4"><i class="bi bi-calendar-event"></i> Danh sách đơn xin nghỉ phép</h1>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Loại nghỉ</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
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
                        ORDER BY lr.created_at DESC
                    ");

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $statusBadge = '';
                            if ($row['status'] == 'Pending') {
                                $statusBadge = '<span class="badge badge-warning">Chờ phê duyệt</span>';
                            } elseif ($row['status'] == 'Approved') {
                                $statusBadge = '<span class="badge badge-success">Đã phê duyệt</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-danger">Từ chối</span>';
                            }

                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['leave_type']) . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['start_date'])) . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['end_date'])) . '</td>';
                            echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
                            echo '<td>' . $statusBadge . '</td>';
                            echo '<td>';
                            echo '<a href="index.php?mainpage=leave&subpage=view&id=' . $row['id'] . '" class="btn btn-sm btn-info" title="Xem">';
                            echo '<i class="bi bi-eye"></i></a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center text-muted">Không có đơn xin nghỉ</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
