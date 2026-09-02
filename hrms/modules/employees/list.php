<!-- Employee List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-people"></i> Danh sách nhân viên</h1>
    <a href="index.php?mainpage=employees&subpage=add" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Thêm mới
    </a>
</div>

<div class="card">
    <div class="card-body">
        <!-- Search Form -->
        <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên hoặc mã nhân viên...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="departmentFilter">
                    <option value="">Tất cả phòng ban</option>
                    <?php
                    $result = $conn->query("SELECT id, name FROM departments ORDER BY name");
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="Active">Hoạt động</option>
                    <option value="Inactive">Không hoạt động</option>
                    <option value="On Leave">Đang nghỉ</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Tên nhân viên</th>
                        <th>Email</th>
                        <th>Phòng ban</th>
                        <th>Vị trí</th>
                        <th>Lương</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="employeeTable">
                    <?php
                    $result = $conn->query("
                        SELECT id, employee_id, full_name, email, phone, position, department, salary, status
                        FROM employees
                        ORDER BY created_at DESC
                    ");

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $statusBadge = '';
                            if ($row['status'] == 'Active') {
                                $statusBadge = '<span class="badge badge-success">Hoạt động</span>';
                            } elseif ($row['status'] == 'Inactive') {
                                $statusBadge = '<span class="badge badge-danger">Không hoạt động</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-warning">Đang nghỉ</span>';
                            }

                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($row['employee_id']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                            echo '<td>' . number_format($row['salary'], 0, ',', '.') . ' VND</td>';
                            echo '<td>' . $statusBadge . '</td>';
                            echo '<td>';
                            echo '<a href="index.php?mainpage=employees&subpage=edit&id=' . $row['id'] . '" class="btn btn-sm btn-info me-2" title="Sửa">';
                            echo '<i class="bi bi-pencil"></i></a>';
                            echo '<button class="btn btn-sm btn-danger" onclick="deleteEmployee(' . $row['id'] . ')" title="Xóa">';
                            echo '<i class="bi bi-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted">Chưa có nhân viên nào</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Search functionality
    $("#searchInput").on("keyup", function(){
        var searchTerm = $(this).val().toLowerCase();
        filterTable();
    });

    // Filter by department
    $("#departmentFilter").on("change", function(){
        filterTable();
    });

    // Filter by status
    $("#statusFilter").on("change", function(){
        filterTable();
    });

    function filterTable() {
        var searchTerm = $("#searchInput").val().toLowerCase();
        var department = $("#departmentFilter").val().toLowerCase();
        var status = $("#statusFilter").val().toLowerCase();

        $("#employeeTable tr").filter(function(){
            var text = $(this).text().toLowerCase();
            var showRow = true;

            if (searchTerm && !text.includes(searchTerm)) {
                showRow = false;
            }
            if (department && !text.includes(department)) {
                showRow = false;
            }
            if (status && !text.includes(status)) {
                showRow = false;
            }

            $(this).toggle(showRow);
        });
    }
});

function deleteEmployee(id) {
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
        window.location.href = 'api/delete_employee.php?id=' + id;
    }
}
</script>
