<!-- Dashboard Overview -->
<h1 class="mb-4">
    <i class="bi bi-speedometer2"></i> Dashboard
</h1>

<div class="row mb-4">
    <!-- Total Employees -->
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-people" style="font-size: 2rem; color: #1976d2;"></i>
            <div class="stat-value">
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status='Active'");
                $row = $result->fetch_assoc();
                echo $row['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Nhân viên hoạt động</div>
        </div>
    </div>

    <!-- Total Departments -->
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-building" style="font-size: 2rem; color: #388e3c;"></i>
            <div class="stat-value">
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM departments");
                $row = $result->fetch_assoc();
                echo $row['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Phòng ban</div>
        </div>
    </div>

    <!-- Present Today -->
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-check-circle" style="font-size: 2rem; color: #f57c00;"></i>
            <div class="stat-value">
                <?php
                $today = date('Y-m-d');
                $result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date='$today' AND status='Present'");
                $row = $result->fetch_assoc();
                echo $row['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Có mặt hôm nay</div>
        </div>
    </div>

    <!-- Pending Leaves -->
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-hourglass-split" style="font-size: 2rem; color: #d32f2f;"></i>
            <div class="stat-value">
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='Pending'");
                $row = $result->fetch_assoc();
                echo $row['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Chờ phê duyệt</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Employees -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-people"></i> Nhân viên mới
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Vị trí</th>
                            <th>Ngày tuyển</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT full_name, position, join_date FROM employees ORDER BY join_date DESC LIMIT 5");
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                                echo '<td>' . date('d/m/Y', strtotime($row['join_date'])) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-check"></i> Thống kê chuyên cần
            </div>
            <div class="card-body">
                <?php
                $today = date('Y-m-d');
                $present = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date='$today' AND status='Present'")->fetch_assoc()['total'];
                $absent = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date='$today' AND status='Absent'")->fetch_assoc()['total'];
                $late = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date='$today' AND status='Late'")->fetch_assoc()['total'];
                $total = $present + $absent + $late;
                ?>
                <div class="row text-center">
                    <div class="col-md-4">
                        <h5 style="color: #4caf50;">Có mặt</h5>
                        <p style="font-size: 1.5rem; font-weight: bold; color: #4caf50;"><?php echo $present; ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5 style="color: #f57c00;">Muộn</h5>
                        <p style="font-size: 1.5rem; font-weight: bold; color: #f57c00;"><?php echo $late; ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5 style="color: #d32f2f;">Vắng</h5>
                        <p style="font-size: 1.5rem; font-weight: bold; color: #d32f2f;"><?php echo $absent; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
