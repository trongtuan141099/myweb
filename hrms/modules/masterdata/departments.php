<h1 class="mb-4"><i class="bi bi-building"></i> Quản lý phòng ban</h1>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Thêm phòng ban</div>
            <div class="card-body">
                <form id="departmentForm">
                    <div class="mb-3">
                        <label class="form-label">Tên phòng ban</label>
                        <input type="text" class="form-control" id="deptName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" id="deptDesc" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Thêm</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Danh sách phòng ban</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên phòng ban</th>
                                <th>Mô tả</th>
                                <th>Nhân viên</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("
                                SELECT d.id, d.name, d.description,
                                       COUNT(e.id) as emp_count
                                FROM departments d
                                LEFT JOIN employees e ON d.name = e.department
                                GROUP BY d.id
                                ORDER BY d.name
                            ");

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong></td>';
                                    echo '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
                                    echo '<td><span class="badge badge-info">' . $row['emp_count'] . '</span></td>';
                                    echo '<td>';
                                    echo '<button class="btn btn-sm btn-info me-2" onclick="editDept(' . $row['id'] . ')"><i class="bi bi-pencil"></i></button>';
                                    echo '<button class="btn btn-sm btn-danger" onclick="deleteDept(' . $row['id'] . ')"><i class="bi bi-trash"></i></button>';
                                    echo '</td>';
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
</div>

<script>
$(document).ready(function(){
    $("#departmentForm").on("submit", function(e){
        e.preventDefault();
        var name = $("#deptName").val();
        var desc = $("#deptDesc").val();

        $.ajax({
            url: 'api/add_department.php',
            method: 'POST',
            data: {name: name, description: desc},
            success: function(response) {
                alert('Thêm phòng ban thành công!');
                location.reload();
            }
        });
    });
});

function editDept(id) {
    // Implement edit functionality
}

function deleteDept(id) {
    if (confirm('Bạn có chắc muốn xóa?')) {
        window.location.href = 'api/delete_department.php?id=' + id;
    }
}
</script>
