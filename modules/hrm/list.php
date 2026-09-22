<?php
// modules/hrm/list.php
require_once __DIR__ . '/../../core/check_permission.php';

// Xử lý xóa nhân viên nếu có yêu cầu
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $del_code = trim($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_code = ?");
    if ($stmt) {
        $stmt->bind_param("s", $del_code);
        $stmt->execute();
        $stmt->close();
    }
    echo "<script>window.location.href='index.php?mainpage=hrm&subpage=list';</script>";
    exit;
}
?>

<style>
/* Module-specific styles for Employee List */
.badge-stt {
  color: var(--dx-text-muted);
  font-family: monospace;
  font-weight: 600;
}

.emp-code {
  font-family: monospace;
  font-weight: 700;
  color: var(--dx-primary);
  background-color: var(--dx-primary-light);
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

.gender-nam { background-color: var(--dx-info-bg, #e0f2fe); color: var(--dx-info, #0284c7); border: 1px solid var(--dx-info-border, transparent); }
.gender-nu { background-color: rgba(219, 39, 119, 0.15); color: #db2777; border: 1px solid rgba(219, 39, 119, 0.25); }

[data-theme="dark"] .gender-nam { background-color: rgba(2, 132, 199, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
[data-theme="dark"] .gender-nu { background-color: rgba(219, 39, 119, 0.2); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.35); }

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
  color: var(--dx-primary);
  background-color: var(--dx-primary-light);
  border-color: var(--dx-primary-border, #bfdbfe);
}
.btn-act-edit:hover {
  background-color: var(--dx-primary);
  color: #ffffff;
}

.btn-act-delete {
  color: var(--dx-danger);
  background-color: var(--dx-danger-bg, #fef2f2);
  border-color: var(--dx-danger-border, #fecaca);
}
.btn-act-delete:hover {
  background-color: var(--dx-danger);
  color: #ffffff;
}
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">people</span>
                Danh Sách Nhân Viên
            </h1>
            <p class="app-page-subtitle">Quản lý hồ sơ lao động, vị trí làm việc và phân bổ nhân sự các xưởng</p>
        </div>
        <div class="app-page-actions">
            <?php if (hasPermission('hrm.manage')): ?>
            <a href="index.php?mainpage=hrm&subpage=add_employee" class="app-btn app-btn-primary">
                <span class="material-icons">person_add</span> Thêm Mới Nhân Viên
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="app-filter-card mb-3">
        <div class="row g-3 w-100">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                        <span class="material-icons fs-6 text-muted">search</span>
                    </span>
                    <input type="text" class="app-form-control border-start-0" id="searchInput" placeholder="Tìm kiếm theo tên, mã nhân viên, phòng ban...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="app-form-control" id="departmentFilter">
                    <option value="">-- Tất cả phòng ban / Cost center --</option>
                    <?php
                    $result = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['cost_center']) . '">' . htmlspecialchars($row['cost_center']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="app-form-control" id="genderFilter">
                    <option value="">-- Tất cả giới tính --</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card chứa Bảng -->
    <div class="app-card">
        <div class="app-table-responsive" style="max-height: calc(100vh - 300px); overflow-y: auto;">
            <table class="app-table table-sticky-header">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th style="width: 130px;">Mã Nhân Viên</th>
                        <th>Họ và Tên</th>
                        <th style="width: 100px;">Giới Tính</th>
                        <th>Cấp Bậc</th>
                        <th>Cost Center</th>
                        <th style="width: 130px;">Ngày Vào Cty</th>
                        <th style="width: 130px;">Ngày Nghỉ Việc</th>
                        <th style="text-align: center; width: 130px;">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="employeeTable">
                    <!-- Dynamic client-side paginated rows -->
                </tbody>
            </table>
        </div>
        <!-- Card Footer Chứa Phân Trang Chuẩn -->
        <div class="app-card-footer">
            <div id="hrmPagination" class="w-100"></div>
        </div>
    </div>
</div>

<?php
$sql = "SELECT * FROM employees ORDER BY hire_date DESC";
$result = $conn->query($sql);
$employeesList = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employeesList[] = [
            'employee_code' => $row['employee_code'],
            'full_name' => $row['full_name'],
            'gender' => $row['gender'] ?? '',
            'job_level' => $row['job_level'] ?? '-',
            'cost_center' => $row['cost_center'] ?? '-',
            'hire_date' => $row['hire_date'] ?? '-',
            'resignation_date' => (!empty($row['resignation_date']) && $row['resignation_date'] != '0000-00-00') ? $row['resignation_date'] : '-'
        ];
    }
}
?>

<!-- Script Tìm kiếm, Lọc & Phân Trang Chuẩn -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rawEmployees = <?= json_encode($employeesList, JSON_UNESCAPED_UNICODE) ?>;
    const canManage = <?= hasPermission('hrm.manage') ? 'true' : 'false' ?>;

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    const pagination = createClientTablePagination({
        tbodyId: 'employeeTable',
        paginationId: 'hrmPagination',
        data: rawEmployees,
        colSpan: 9,
        defaultPageSize: 25,
        pageSizeOptions: [10, 25, 50, 100],
        emptyMessage: 'Chưa có dữ liệu nhân viên.',
        renderRow: (row, stt) => {
            const genderClass = (row.gender === 'Nam') ? 'gender-nam' : ((row.gender === 'Nữ') ? 'gender-nu' : '');
            const genderBadge = row.gender ? `<span class="gender-badge ${genderClass}">${escapeHtml(row.gender)}</span>` : '-';
            const editAction = canManage ? `
                <a href="index.php?mainpage=hrm&subpage=add_employee&id=${encodeURIComponent(row.employee_code)}" class="btn-act btn-act-edit" title="Chỉnh sửa">
                    <span class="material-icons" style="font-size: 14px;">edit</span> Sửa
                </a>
                <a href="index.php?mainpage=hrm&subpage=list&action=delete&id=${encodeURIComponent(row.employee_code)}" class="btn-act btn-act-delete" onclick="return confirm('Xác nhận xóa nhân viên này?');" title="Xóa">
                    <span class="material-icons" style="font-size: 14px;">delete</span> Xóa
                </a>
            ` : `<span class="text-muted small">Chỉ xem</span>`;

            return `
            <tr>
                <td class="badge-stt">#${stt}</td>
                <td><span class="emp-code">${escapeHtml(row.employee_code)}</span></td>
                <td><strong>${escapeHtml(row.full_name)}</strong></td>
                <td>${genderBadge}</td>
                <td>${escapeHtml(row.job_level)}</td>
                <td>${escapeHtml(row.cost_center)}</td>
                <td>${escapeHtml(row.hire_date)}</td>
                <td>${escapeHtml(row.resignation_date)}</td>
                <td>
                    <div class="action-btns">
                        ${editAction}
                    </div>
                </td>
            </tr>`;
        }
    });

    const searchInput = document.getElementById("searchInput");
    const departmentFilter = document.getElementById("departmentFilter");
    const genderFilter = document.getElementById("genderFilter");

    function applyFilters() {
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const deptTerm = (departmentFilter.value || '').toLowerCase().trim();
        const genderTerm = (genderFilter.value || '').toLowerCase().trim();

        pagination.setFilter(row => {
            const empCode = (row.employee_code || '').toLowerCase();
            const fullName = (row.full_name || '').toLowerCase();
            const costCenter = (row.cost_center || '').toLowerCase();
            const gender = (row.gender || '').toLowerCase();

            const matchSearch = (!searchTerm) || empCode.includes(searchTerm) || fullName.includes(searchTerm);
            const matchDept = (!deptTerm) || costCenter === deptTerm || costCenter.includes(deptTerm);
            const matchGender = (!genderTerm) || gender === genderTerm;

            return matchSearch && matchDept && matchGender;
        });
    }

    searchInput.addEventListener("input", applyFilters);
    departmentFilter.addEventListener("change", applyFilters);
    genderFilter.addEventListener("change", applyFilters);
});
</script>