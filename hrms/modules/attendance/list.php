<!-- Attendance List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-calendar-check"></i> Danh sách chuyên cần</h1>
    <a href="index.php?mainpage=attendance&subpage=checkin" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Điểm danh
    </a>
</div>

<div class="card">
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Ngày</label>
                <input type="date" class="form-control" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nhân viên</label>
                <input type="text" class="form-control" id="employeeFilter" placeholder="Tìm kiếm nhân viên...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" id="statusFilter">
                    <option value="">Tất cả</option>
                    <option value="Present">Có mặt</option>
                    <option value="Absent">Vắng</option>
                    <option value="Late">Muộn</option>
                    <option value="Half Day">Nửa ngày</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-info w-100" id="filterBtn">
                    <i class="bi bi-funnel"></i> Lọc
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tên nhân viên</th>
                        <th>Mã NV</th>
                        <th>Ngày</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="attendanceTable">
                    <?php
                    $today = date('Y-m-d');
                    $result = $conn->query("
                        SELECT a.id, e.employee_id, e.full_name, a.date, a.check_in, a.check_out, a.status, a.notes
                        FROM attendance a
                        JOIN employees e ON a.employee_id = e.id
                        WHERE a.date = '$today'
                        ORDER BY a.date DESC, e.full_name
                    ");

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $statusClass = '';
                            $statusText = '';
                            switch($row['status']) {
                                case 'Present':
                                    $statusClass = 'badge-success';
                                    $statusText = 'Có mặt';
                                    break;
                                case 'Absent':
                                    $statusClass = 'badge-danger';
                                    $statusText = 'Vắng';
                                    break;
                                case 'Late':
                                    $statusClass = 'badge-warning';
                                    $statusText = 'Muộn';
                                    break;
                                default:
                                    $statusClass = 'badge-secondary';
                                    $statusText = $row['status'];
                            }

                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($row['full_name']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($row['employee_id']) . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['date'])) . '</td>';
                            echo '<td>' . ($row['check_in'] ? date('H:i', strtotime($row['check_in'])) : '-') . '</td>';
                            echo '<td>' . ($row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '-') . '</td>';
                            echo '<td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>';
                            echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-info me-2" onclick="editAttendance(' . $row['id'] . ')" title="Sửa">';
                            echo '<i class="bi bi-pencil"></i></button>';
                            echo '<button class="btn btn-sm btn-danger" onclick="deleteAttendance(' . $row['id'] . ')" title="Xóa">';
                            echo '<i class="bi bi-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted">Không có dữ liệu cho ngày này</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#filterBtn").on("click", function(){
        var date = $("#dateFilter").val();
        var employee = $("#employeeFilter").val().toLowerCase();
        var status = $("#statusFilter").val();

        window.location.href = "index.php?mainpage=attendance&subpage=list&date=" + date + "&employee=" + employee + "&status=" + status;
    });
});

function editAttendance(id) {
    window.location.href = "index.php?mainpage=attendance&subpage=edit&id=" + id;
}

function deleteAttendance(id) {
    if (confirm('Bạn có chắc chắn muốn xóa?')) {
        window.location.href = "api/delete_attendance.php?id=" + id;
    }
}
</script>
