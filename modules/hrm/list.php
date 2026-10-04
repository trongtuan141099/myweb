<?php
// modules/hrm/list.php
require_once __DIR__ . '/../../core/check_permission.php';

checkAuth();

// Xử lý xóa nhân viên nếu có yêu cầu truyền thống qua GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    if (hasPermission(['hrm.manage', 'admin'])) {
        $del_code = trim($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_code = ?");
        if ($stmt) {
            $stmt->bind_param("s", $del_code);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "<script>window.location.href='index.php?mainpage=hrm&subpage=list';</script>";
    exit;
}

// Lấy danh sách Cost Center cho bộ lọc và modal
$costCenters = [];
$resCc = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center ASC");
if ($resCc) {
    while ($r = $resCc->fetch_assoc()) {
        $costCenters[] = $r['cost_center'];
    }
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

/* Tô xám nhân viên đã có ngày nghỉ việc */
.row-resigned {
  background-color: rgba(148, 163, 184, 0.15) !important;
  color: #64748b !important;
  opacity: 0.65;
}
.row-resigned td {
  background-color: transparent !important;
  color: inherit !important;
}
.row-resigned .emp-code {
  background-color: rgba(100, 116, 139, 0.15) !important;
  color: #64748b !important;
}
.row-resigned:hover {
  background-color: rgba(148, 163, 184, 0.25) !important;
  opacity: 0.9;
}

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
  cursor: pointer;
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
            <h1 class="app-page-title" data-i18n="nav.hrm_list">
                <span class="material-icons">people</span>
                <?= __('nav.hrm_list', 'Danh Sách Nhân Viên') ?>
            </h1>
            <p class="app-page-subtitle">Quản lý hồ sơ lao động, vị trí làm việc, trạng thái ngày nghỉ việc và phân bổ nhân sự</p>
        </div>
        <div class="app-page-actions d-flex align-items-center gap-2">
            <!-- Nút Xuất Excel -->
            <a href="api/employee_export.php" id="btnExportExcel" class="app-btn app-btn-secondary" title="Xuất toàn bộ danh sách nhân sự ra file Excel/CSV (UTF-8 BOM)">
                <span class="material-icons">file_download</span> <span data-i18n="common.btn_export"><?= __('common.btn_export', 'Xuất Excel') ?></span>
            </a>

            <?php if (hasPermission(['hrm.manage', 'admin'])): ?>
            <!-- Nút Thêm Mới Nhân Viên -->
            <button type="button" class="app-btn app-btn-primary" onclick="openAddEmployeeModal()">
                <span class="material-icons">person_add</span> <span data-i18n="nav.hrm_add"><?= __('nav.hrm_add', 'Thêm Mới Nhân Viên') ?></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="app-filter-card mb-3">
        <div class="row g-3 w-100">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                        <span class="material-icons fs-6 text-muted">search</span>
                    </span>
                    <input type="text" class="app-form-control border-start-0" id="searchInput" placeholder="Tìm theo tên, mã NV, phòng ban...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="app-form-control" id="departmentFilter">
                    <option value="">-- Tất cả phòng ban / Cost center --</option>
                    <?php foreach ($costCenters as $cc): ?>
                        <option value="<?= htmlspecialchars($cc) ?>"><?= htmlspecialchars($cc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="app-form-control" id="genderFilter">
                    <option value="">-- Tất cả giới tính --</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="app-form-control" id="statusFilter">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active">Đang làm việc</option>
                    <option value="resigned">Đã nghỉ việc (Có ngày nghỉ)</option>
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
                        <th style="width: 120px;">Mã NV</th>
                        <th>Họ và Tên</th>
                        <th style="width: 90px;">Giới Tính</th>
                        <th>Cấp Bậc</th>
                        <th>Cost Center</th>
                        <th>Nhóm Làm Việc</th>
                        <th>Ca</th>
                        <th style="width: 120px;">Ngày Vào Cty</th>
                        <th style="width: 130px;">Ngày Nghỉ Việc</th>
                        <th style="width: 120px; text-align: center;">Trạng Thái</th>
                        <th style="text-align: center; width: 140px;">Hành Động</th>
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

<!-- =========================================================================
     MODAL: THÊM MỚI / CHỈNH SỬA THÔNG TIN NHÂN VIÊN
     ========================================================================= -->
<div class="modal fade" id="modalEmployeeForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fs-6 fw-bold" id="modalEmployeeTitle">
          <span class="material-icons">badge</span>
          <span>Thông Tin Nhân Viên</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEmployeeModal" onsubmit="submitEmployeeModal(event)">
        <input type="hidden" id="modalIsEdit" name="is_edit" value="0">
        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Mã NV -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Mã nhân viên <span class="text-danger">*</span></label>
              <input type="text" id="modalEmpCode" name="employee_code" class="form-control form-control-sm font-monospace fw-bold" maxlength="8" placeholder="VD: 01920163" required>
              <div class="form-text" style="font-size: 11px;">Mã số nhân sự định danh duy nhất (tối đa 8 ký tự).</div>
            </div>

            <!-- Họ và Tên -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Họ và Tên <span class="text-danger">*</span></label>
              <input type="text" id="modalEmpName" name="full_name" class="form-control form-control-sm" placeholder="Nhập họ và tên đầy đủ..." required>
            </div>

            <!-- Giới tính -->
            <div class="col-md-4">
              <label class="form-label fw-bold small">Giới tính</label>
              <select id="modalEmpGender" name="gender" class="form-select form-select-sm">
                <option value="">-- Chọn giới tính --</option>
                <option value="Nam">Nam</option>
                <option value="Nữ">Nữ</option>
              </select>
            </div>

            <!-- Cấp bậc / Vị trí -->
            <div class="col-md-4">
              <label class="form-label fw-bold small">Cấp bậc / Vị trí</label>
              <input type="text" id="modalEmpJobLevel" name="job_level" class="form-control form-control-sm" placeholder="VD: M1, S3, S4, W1...">
            </div>

            <!-- Cost Center -->
            <div class="col-md-4">
              <label class="form-label fw-bold small">Cost Center</label>
              <input type="text" id="modalEmpCostCenter" name="cost_center" list="modalCostCenterList" class="form-control form-control-sm" placeholder="VD: A00330, A00430...">
              <datalist id="modalCostCenterList">
                <?php foreach ($costCenters as $cc): ?>
                  <option value="<?= htmlspecialchars($cc) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>

            <!-- Nhóm làm việc -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Nhóm làm việc</label>
              <select id="modalEmpWorkGroup" name="work_group" class="form-select form-select-sm">
                <option value="Đùn TU">Đùn TU</option>
                <option value="Đùn T">Đùn T</option>
                <option value="Thiết bị">Thiết bị</option>
                <option value="Shotblast">Shotblast</option>
                <option value="Nghiền nhựa">Nghiền nhựa</option>
                <option value="Khác" selected>Khác</option>
              </select>
            </div>

            <!-- Ca làm việc -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Ca làm việc</label>
              <select id="modalEmpWorkShift" name="work_shift" class="form-select form-select-sm">
                <option value="Ca 1" selected>Ca 1 (06:00 - 14:00)</option>
                <option value="Ca 2">Ca 2 (14:00 - 22:00)</option>
                <option value="Ca HC">Ca HC (Hành chính)</option>
                <option value="Ca 3">Ca 3 (22:00 - 06:00)</option>
              </select>
            </div>

            <!-- Ngày vào công ty -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Ngày vào công ty (Hire Date)</label>
              <input type="date" id="modalEmpHireDate" name="hire_date" class="form-control form-control-sm">
            </div>

            <!-- Xe đưa rước (Sắp xe) -->
            <div class="col-md-6">
              <label class="form-label fw-bold small d-flex align-items-center gap-1">
                <span class="material-icons fs-6 text-primary">directions_bus</span>
                Sắp xe (Xe đưa rước)
              </label>
              <select id="modalEmpShuttleBus" name="use_shuttle_bus" class="form-select form-select-sm">
                <option value="0">Không sử dụng xe đưa rước</option>
                <option value="1">Có sử dụng xe đưa rước</option>
              </select>
            </div>

            <!-- Ngày nghỉ việc -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-danger d-flex align-items-center gap-1">
                <span class="material-icons fs-6">person_off</span>
                Ngày nghỉ việc (Resignation Date)
              </label>
              <input type="date" id="modalEmpResignationDate" name="resignation_date" class="form-control form-control-sm border-danger-subtle">
            </div>

            <div class="col-12">
              <div class="p-2 bg-light rounded border small text-muted">
                <span class="material-icons text-warning align-middle fs-6">info</span>
                <strong>Ghi chú:</strong> Khi cập nhật <strong>Ngày nghỉ việc</strong>, hệ thống sẽ tự động chuyển trạng thái của nhân viên sang <em>"Đã nghỉ việc"</em> và <strong>tô xám toàn bộ</strong> thông tin của nhân viên trên:
                <strong>Tổng Hợp Phép HRM & 12 Tháng</strong> và <strong>Kiểm Soát Giới Hạn Tăng Ca 200 Giờ/Năm</strong>.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
          <a id="modalFullPageEditBtn" href="#" class="btn btn-outline-secondary btn-sm" style="display: none;">
            <span class="material-icons fs-6 align-middle">open_in_new</span> Mở trang sửa chi tiết
          </a>
          <div class="d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1" id="btnSaveEmpModal">
              <span class="material-icons fs-6">save</span> Lưu Thông Tin
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$sql = "SELECT * FROM employees ORDER BY hire_date DESC, employee_code ASC";
$result = $conn->query($sql);
$employeesList = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $hasResigned = (!empty($row['resignation_date']) && $row['resignation_date'] !== '0000-00-00');
        $employeesList[] = [
            'employee_code'    => $row['employee_code'],
            'full_name'        => $row['full_name'],
            'gender'           => $row['gender'] ?? '',
            'job_level'        => $row['job_level'] ?? '-',
            'cost_center'      => $row['cost_center'] ?? '-',
            'work_group'       => $row['work_group'] ?? 'Khác',
            'work_shift'       => $row['work_shift'] ?? 'Ca 1',
            'hire_date'        => $row['hire_date'] ?? '-',
            'resignation_date' => $hasResigned ? $row['resignation_date'] : '-',
            'has_resigned'     => $hasResigned
        ];
    }
}
?>

<!-- Script Tìm kiếm, Lọc, Phân Trang & Modal CRUD -->
<script>
let rawEmployees = <?= json_encode($employeesList, JSON_UNESCAPED_UNICODE) ?>;
const canManage = <?= hasPermission(['hrm.manage', 'admin']) ? 'true' : 'false' ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

let paginationInstance = null;

document.addEventListener("DOMContentLoaded", function () {
    paginationInstance = createClientTablePagination({
        tbodyId: 'employeeTable',
        paginationId: 'hrmPagination',
        data: rawEmployees,
        colSpan: 12,
        defaultPageSize: 25,
        pageSizeOptions: [10, 25, 50, 100],
        emptyMessage: 'Chưa có dữ liệu nhân viên.',
        renderRow: (row, stt) => {
            const isResigned = row.has_resigned;
            const genderClass = (row.gender === 'Nam') ? 'gender-nam' : ((row.gender === 'Nữ') ? 'gender-nu' : '');
            const genderBadge = row.gender ? `<span class="gender-badge ${genderClass}">${escapeHtml(row.gender)}</span>` : '-';
            const statusBadge = isResigned
                ? `<span class="badge bg-secondary text-white" title="Ngày nghỉ việc: ${escapeHtml(row.resignation_date)}">Đã nghỉ việc</span>`
                : `<span class="badge bg-success-subtle text-success border border-success-subtle">Đang làm việc</span>`;

            const editAction = canManage ? `
                <button type="button" class="btn-act btn-act-edit" onclick="openEditEmployeeModal('${escapeHtml(row.employee_code)}')" title="Chỉnh sửa thông tin">
                    <span class="material-icons" style="font-size: 14px;">edit</span> Sửa
                </button>
                <button type="button" class="btn-act btn-act-delete" onclick="deleteEmployee('${escapeHtml(row.employee_code)}')" title="Xóa nhân sự">
                    <span class="material-icons" style="font-size: 14px;">delete</span> Xóa
                </button>
            ` : `<span class="text-muted small">Chỉ xem</span>`;

            return `
            <tr class="${isResigned ? 'row-resigned' : ''}">
                <td class="badge-stt">#${stt}</td>
                <td><span class="emp-code">${escapeHtml(row.employee_code)}</span></td>
                <td>
                    <strong>${escapeHtml(row.full_name)}</strong>
                    ${isResigned ? `<span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:10px;">Đã nghỉ (${escapeHtml(row.resignation_date)})</span>` : ''}
                </td>
                <td>${genderBadge}</td>
                <td>${escapeHtml(row.job_level)}</td>
                <td>${escapeHtml(row.cost_center)}</td>
                <td><small class="badge bg-light text-dark border">${escapeHtml(row.work_group)}</small></td>
                <td><small class="badge bg-light text-dark border">${escapeHtml(row.work_shift)}</small></td>
                <td>${escapeHtml(row.hire_date)}</td>
                <td class="${isResigned ? 'text-danger fw-bold' : 'text-muted'}">${escapeHtml(row.resignation_date)}</td>
                <td class="text-center">${statusBadge}</td>
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
    const statusFilter = document.getElementById("statusFilter");
    const btnExportExcel = document.getElementById("btnExportExcel");

    function applyFilters() {
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const deptTerm = (departmentFilter.value || '').toLowerCase().trim();
        const genderTerm = (genderFilter.value || '').toLowerCase().trim();
        const statusTerm = (statusFilter.value || '').trim();

        // Cập nhật URL xuất Excel theo filter
        const exportUrl = new URL('api/employee_export.php', window.location.href);
        if (searchTerm) exportUrl.searchParams.set('search', searchTerm);
        if (deptTerm) exportUrl.searchParams.set('department', departmentFilter.value);
        if (genderTerm) exportUrl.searchParams.set('gender', genderFilter.value);
        if (statusTerm) exportUrl.searchParams.set('status', statusTerm);
        btnExportExcel.href = exportUrl.toString();

        paginationInstance.setFilter(row => {
            const empCode = (row.employee_code || '').toLowerCase();
            const fullName = (row.full_name || '').toLowerCase();
            const costCenter = (row.cost_center || '').toLowerCase();
            const gender = (row.gender || '').toLowerCase();

            const matchSearch = (!searchTerm) || empCode.includes(searchTerm) || fullName.includes(searchTerm) || costCenter.includes(searchTerm);
            const matchDept = (!deptTerm) || costCenter === deptTerm || costCenter.includes(deptTerm);
            const matchGender = (!genderTerm) || gender === genderTerm;
            let matchStatus = true;
            if (statusTerm === 'active') {
                matchStatus = !row.has_resigned;
            } else if (statusTerm === 'resigned') {
                matchStatus = row.has_resigned;
            }

            return matchSearch && matchDept && matchGender && matchStatus;
        });
    }

    searchInput.addEventListener("input", applyFilters);
    departmentFilter.addEventListener("change", applyFilters);
    genderFilter.addEventListener("change", applyFilters);
    statusFilter.addEventListener("change", applyFilters);
});

// Modal instance
let empModalInstance = null;
function getEmpModal() {
    if (!empModalInstance) {
        empModalInstance = new bootstrap.Modal(document.getElementById('modalEmployeeForm'));
    }
    return empModalInstance;
}

// Mở modal thêm mới nhân viên
function openAddEmployeeModal() {
    document.getElementById('formEmployeeModal').reset();
    document.getElementById('modalIsEdit').value = '0';
    document.getElementById('modalEmployeeTitle').innerHTML = '<span class="material-icons">person_add</span> Thêm Mới Nhân Viên';
    const codeInp = document.getElementById('modalEmpCode');
    codeInp.readOnly = false;
    codeInp.style.backgroundColor = '';
    document.getElementById('modalEmpHireDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalEmpWorkShift').value = 'Ca 1';
    document.getElementById('modalEmpWorkGroup').value = 'Khác';
    document.getElementById('modalEmpShuttleBus').value = '0';
    document.getElementById('modalFullPageEditBtn').style.display = 'none';
    getEmpModal().show();
}

// Mở modal sửa thông tin nhân viên
async function openEditEmployeeModal(empCode) {
    document.getElementById('formEmployeeModal').reset();
    document.getElementById('modalIsEdit').value = '1';
    document.getElementById('modalEmployeeTitle').innerHTML = `<span class="material-icons">edit_note</span> Chỉnh Sửa Nhân Viên: <span class="font-monospace ms-1">${escapeHtml(empCode)}</span>`;
    
    const codeInp = document.getElementById('modalEmpCode');
    codeInp.value = empCode;
    codeInp.readOnly = true;
    codeInp.style.backgroundColor = 'var(--dx-bg-subtle, #f1f5f9)';

    const fullPageBtn = document.getElementById('modalFullPageEditBtn');
    fullPageBtn.href = `index.php?mainpage=hrm&subpage=add_employee&id=${encodeURIComponent(empCode)}`;
    fullPageBtn.style.display = 'inline-flex';

    getEmpModal().show();

    // Fetch dữ liệu mới nhất từ server
    try {
        const res = await fetch(`api/employee.php?action=get&code=${encodeURIComponent(empCode)}`);
        const json = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            document.getElementById('modalEmpName').value = d.full_name || '';
            document.getElementById('modalEmpGender').value = d.gender || '';
            document.getElementById('modalEmpJobLevel').value = d.job_level || '';
            document.getElementById('modalEmpCostCenter').value = d.cost_center || '';
            document.getElementById('modalEmpWorkGroup').value = d.work_group || 'Khác';
            document.getElementById('modalEmpWorkShift').value = d.work_shift || 'Ca 1';
            document.getElementById('modalEmpShuttleBus').value = (d.use_shuttle_bus == 1) ? '1' : '0';
            document.getElementById('modalEmpHireDate').value = d.hire_date || '';
            document.getElementById('modalEmpResignationDate').value = d.resignation_date || '';
        } else {
            alert(json.message || 'Không tìm thấy dữ liệu nhân viên.');
        }
    } catch (err) {
        console.error('Error fetching employee details:', err);
    }
}

// Lưu thông tin nhân viên qua AJAX
async function submitEmployeeModal(e) {
    e.preventDefault();
    const form = document.getElementById('formEmployeeModal');
    const formData = new FormData(form);
    formData.append('action', 'save');

    const saveBtn = document.getElementById('btnSaveEmpModal');
    const oldBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

    try {
        const res = await fetch('api/employee.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            getEmpModal().hide();
            // Tải lại trang hoặc cập nhật danh sách mượt mà
            window.location.reload();
        } else {
            alert(json.message || 'Lỗi khi lưu thông tin nhân viên.');
        }
    } catch (err) {
        console.error('Submit error:', err);
        alert('Lỗi kết nối khi lưu nhân viên.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = oldBtnText;
    }
}

// Xóa nhân viên
async function deleteEmployee(empCode) {
    if (!confirm(`Xác nhận xóa nhân viên [${empCode}] khỏi hệ thống?`)) return;

    try {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('employee_code', empCode);

        const res = await fetch('api/employee.php', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || 'Không thể xóa nhân sự này.');
        }
    } catch (err) {
        console.error('Delete error:', err);
        alert('Lỗi kết nối khi xóa nhân viên.');
    }
}
</script>