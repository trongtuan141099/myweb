<style>
/* Scope riêng biệt để tránh xung đột CSS hệ thống */
.employee-wrapper {
  padding: 20px;
  height: calc(100vh - var(--header-height) - var(--footer-height));
  overflow-y: auto;
  background-color: var(--bg-main);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Header Trang */
.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-card);
  padding: 14px 20px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  flex-shrink: 0;
}

.employee-header h1 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
}

.employee-header h1 .material-icons {
  color: var(--primary);
  font-size: 22px;
}

/* Card chứa Bộ lọc & Bảng */
.employee-card {
  background: var(--bg-card);
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  overflow: hidden;
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Thanh Bộ Lọc (Filter Bar) */
.filter-bar {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--bg-card);
}

.search-box-group {
  position: relative;
}

.search-box-group .material-icons {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 18px;
}

.search-input {
  padding-left: 38px !important;
}

.custom-input, .custom-select {
  width: 100%;
  padding: 8px 12px;
  background: var(--bg-main);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.15s ease;
}

.custom-input:focus, .custom-select:focus {
  border-color: var(--primary);
}

/* Khung Bảng có Thanh Cuộn Nội Bộ (Sticky Header) */
.table-responsive-container {
  width: 100%;
  overflow-x: auto;
  overflow-y: auto;
  flex: 1;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.custom-table th {
  background-color: var(--bg-main);
  color: var(--text-muted);
  font-weight: 700;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 10;
  white-space: nowrap;
}

.custom-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-main);
  white-space: nowrap;
  vertical-align: middle;
}

.custom-table tbody tr {
  transition: background-color 0.15s ease;
}

.custom-table tbody tr:hover {
  background-color: var(--bg-hover);
}

/* Trạng thái & Badge */
.badge-stt {
  color: var(--text-muted);
  font-family: monospace;
  font-weight: 600;
}

.emp-code {
  font-family: monospace;
  font-weight: 700;
  color: var(--primary);
  background-color: #dbeafe;
  padding: 3px 8px;
  border-radius: 4px;
}

.gender-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.gender-nam { background-color: #e0f2fe; color: #0284c7; }
.gender-nu { background-color: #fce7f3; color: #db2777; }

/* Nút thao tác Sửa/Xóa UI/UX */
.action-btns {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.btn-act {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s ease;
  border: 1px solid transparent;
}

.btn-act-edit {
  color: var(--primary);
  background-color: #eff6ff;
  border-color: #bfdbfe;
}
.btn-act-edit:hover {
  background-color: var(--primary);
  color: #ffffff;
}

.btn-act-delete {
  color: var(--danger);
  background-color: #fef2f2;
  border-color: #fecaca;
}
.btn-act-delete:hover {
  background-color: var(--danger);
  color: #ffffff;
}
</style>

<div class="employee-wrapper">
    <!-- Header -->
    <div class="employee-header">
        <h1><span class="material-icons">people</span> Danh Sách Nhân Viên</h1>
        <a href="index.php?mainpage=hrm&subpage=add_employee" class="btn btn-primary">
            <span class="material-icons">person_add</span> Thêm Mới Nhân Viên
        </a>
    </div>

    <!-- Main Card chứa Filter và Table -->
    <div class="employee-card">
        <!-- Thanh bộ lọc -->
        <div class="filter-bar">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="search-box-group">
                        <span class="material-icons">search</span>
                        <input type="text" class="custom-input search-input" id="searchInput" placeholder="Tìm kiếm theo tên, mã nhân viên, phòng ban...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="custom-select" id="departmentFilter">
                        <option value="">-- Tất cả phòng ban / Cost center --</option>
                        <?php
                        // Có thể mở comment để load động từ CSDL
                        $result = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center");
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['cost_center']) . '">' . htmlspecialchars($row['cost_center']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="custom-select" id="genderFilter">
                        <option value="">-- Tất cả giới tính --</option>
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Khung Bảng Lịch Sử / Danh Sách Cuộn Động -->
        <div class="table-responsive-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Mã Nhân Viên</th>
                        <th>Họ và Tên</th>
                        <th>Giới Tính</th>
                        <th>Cấp Bậc</th>
                        <th>Cost Center</th>
                        <th>Ngày Vào Công Ty</th>
                        <th>Ngày Nghỉ Việc</th>
                        <th style="text-align: center; width: 120px;">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="employeeTable">
                    <?php
                    $sql = "SELECT * FROM employees ORDER BY hire_date DESC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        $stt = 1;
                        while($row = $result->fetch_assoc()) {
                            $genderClass = ($row["gender"] == 'Nam') ? 'gender-nam' : (($row["gender"] == 'Nữ') ? 'gender-nu' : '');
                            $resignation = (!empty($row["resignation_date"]) && $row["resignation_date"] != '0000-00-00') ? htmlspecialchars($row["resignation_date"]) : '-';
                    ?>
                    <tr>
                        <td class="badge-stt">#<?= $stt++; ?></td>
                        <td><span class="emp-code"><?= htmlspecialchars($row["employee_code"]); ?></span></td>
                        <td><strong><?= htmlspecialchars($row["full_name"]); ?></strong></td>
                        <td>
                            <?php if(!empty($row["gender"])): ?>
                                <span class="gender-badge <?= $genderClass; ?>"><?= htmlspecialchars($row["gender"]); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row["job_level"] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($row["cost_center"] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($row["hire_date"] ?? '-'); ?></td>
                        <td><?= $resignation; ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="pages/sua.php?id=<?= $row["employee_code"]; ?>" class="btn-act btn-act-edit" title="Chỉnh sửa">
                                    <span class="material-icons" style="font-size: 14px;">edit</span> Sửa
                                </a>
                                <a href="pages/xoa.php?id=<?= $row["employee_code"]; ?>" class="btn-act btn-act-delete" onclick="return confirm('Xác nhận xóa nhân viên này?');" title="Xóa">
                                    <span class="material-icons" style="font-size: 14px;">delete</span> Xóa
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        // Căn chuẩn 9 cột khớp với thẻ <th> phía trên
                        echo '<tr><td colspan="9" style="text-align:center; color:var(--text-muted); padding: 30px;">Chưa có dữ liệu nhân viên.</td></tr>';
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script Tìm kiếm & Lọc Động (Chuẩn hóa) -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const departmentFilter = document.getElementById("departmentFilter");
    const genderFilter = document.getElementById("genderFilter");
    const tableRows = document.querySelectorAll("#employeeTable tr");

    function filterEmployeeTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const departmentTerm = departmentFilter.value.toLowerCase().trim();
        const genderTerm = genderFilter.value.toLowerCase().trim();

        tableRows.forEach(row => {
            // Nếu là dòng thông báo "Chưa có dữ liệu" thì bỏ qua
            if (row.cells.length < 9) return;

            // Lấy giá trị chính xác từ các ô tương ứng:
            // Ô 1: Mã NV, Ô 2: Tên, Ô 3: Giới tính, Ô 5: Cost Center (Phòng ban)
            const empCode = row.cells[1].textContent.toLowerCase().trim();
            const fullName = row.cells[2].textContent.toLowerCase().trim();
            const gender = row.cells[3].textContent.toLowerCase().trim();
            const costCenter = row.cells[5].textContent.toLowerCase().trim();

            // 1. Kiểm tra Từ khóa tìm kiếm (khớp với Mã NV hoặc Họ tên)
            const matchSearch = (searchTerm === "") || empCode.includes(searchTerm) || fullName.includes(searchTerm);

            // 2. Kiểm tra Phòng ban (khớp chính xác cột Cost Center)
            const matchDept = (departmentTerm === "") || costCenter === departmentTerm || costCenter.includes(departmentTerm);

            // 3. Kiểm tra Giới tính (khớp chính xác cột Giới tính)
            const matchGender = (genderTerm === "") || gender.includes(genderTerm);

            // Hiện dòng nếu thỏa mãn TẤT CẢ các điều kiện
            if (matchSearch && matchDept && matchGender) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    // Đăng ký sự kiện lắng nghe
    searchInput.addEventListener("input", filterEmployeeTable);
    departmentFilter.addEventListener("change", filterEmployeeTable);
    genderFilter.addEventListener("change", filterEmployeeTable);
});
</script>