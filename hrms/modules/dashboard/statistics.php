<h1 class="mb-4"><i class="bi bi-bar-chart"></i> Thống kê</h1>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Thống kê nhân viên theo phòng ban
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Phòng ban</th>
                                <th>Số nhân viên</th>
                                <th>Đang hoạt động</th>
                                <th>Không hoạt động</th>
                                <th>Tỷ lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_emp = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'];

                            $result = $conn->query("
                                SELECT department, COUNT(*) as total,
                                       SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active,
                                       SUM(CASE WHEN status='Inactive' THEN 1 ELSE 0 END) as inactive
                                FROM employees
                                GROUP BY department
                                ORDER BY total DESC
                            ");

                            while ($row = $result->fetch_assoc()) {
                                $percentage = $total_emp > 0 ? round(($row['total'] / $total_emp) * 100, 2) : 0;
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($row['department']) . '</strong></td>';
                                echo '<td>' . $row['total'] . '</td>';
                                echo '<td><span class="badge badge-success">' . $row['active'] . '</span></td>';
                                echo '<td><span class="badge badge-danger">' . $row['inactive'] . '</span></td>';
                                echo '<td>';
                                echo '<div class="progress" style="height: 20px;">';
                                echo '<div class="progress-bar" role="progressbar" style="width: ' . $percentage . '%" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">' . $percentage . '%</div>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-check"></i> Thống kê chuyên cần tháng này
            </div>
            <div class="card-body">
                <?php
                $current_month = date('Y-m');
                $stats = [];
                $statuses = ['Present', 'Absent', 'Late', 'Half Day'];

                foreach ($statuses as $status) {
                    $result = $conn->query("
                        SELECT COUNT(*) as total FROM attendance
                        WHERE date LIKE '$current_month%' AND status='$status'
                    ");
                    $stats[$status] = $result->fetch_assoc()['total'];
                }

                $colors = ['Present' => '#4caf50', 'Absent' => '#f44336', 'Late' => '#ff9800', 'Half Day' => '#2196f3'];
                ?>
                <div class="row text-center">
                    <?php
                    foreach ($statuses as $status) {
                        $count = $stats[$status];
                        $color = $colors[$status];
                        $labels = ['Present' => 'Có mặt', 'Absent' => 'Vắng', 'Late' => 'Muộn', 'Half Day' => 'Nửa ngày'];
                        echo '<div class="col-md-6 mb-3">';
                        echo '<div style="padding: 15px; background-color: #f5f5f5; border-radius: 8px;">';
                        echo '<h5 style="color: ' . $color . ';">' . $labels[$status] . '</h5>';
                        echo '<p style="font-size: 2rem; font-weight: bold; color: ' . $color . ';">' . $count . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-briefcase"></i> Vị trí phổ biến nhất
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Vị trí</th>
                            <th>Số lượng</th>
                            <th>Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT position, COUNT(*) as total
                            FROM employees
                            WHERE position IS NOT NULL AND position != ''
                            GROUP BY position
                            ORDER BY total DESC
                            LIMIT 10
                        ");

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $percentage = round(($row['total'] / $total_emp) * 100, 1);
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                                echo '<td><span class="badge badge-info">' . $row['total'] . '</span></td>';
                                echo '<td>' . $percentage . '%</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
