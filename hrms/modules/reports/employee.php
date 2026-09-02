<h1 class="mb-4"><i class="bi bi-people"></i> Báo cáo nhân viên</h1>

<div class="card">
    <div class="card-header">
        <i class="bi bi-file-earmark-pdf"></i> Danh sách nhân viên
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Phòng ban</th>
                        <th>Vị trí</th>
                        <th>Lương</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("
                        SELECT employee_id, full_name, email, department, position, salary, status
                        FROM employees
                        ORDER BY full_name
                    ");

                    while ($row = $result->fetch_assoc()) {
                        $statusBadge = $row['status'] == 'Active' ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-danger">' . htmlspecialchars($row['status']) . '</span>';

                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['employee_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                        echo '<td>' . number_format($row['salary'], 0, ',', '.') . '</td>';
                        echo '<td>' . $statusBadge . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary mt-3" onclick="window.print()">
            <i class="bi bi-printer"></i> In báo cáo
        </button>
    </div>
</div>
