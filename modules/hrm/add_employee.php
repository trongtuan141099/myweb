<?php
// modules/hrm/add_employee.php
require_once __DIR__ . '/../../core/check_permission.php';

checkAuth();
requirePermission('hrm.manage');

// Tự động kiểm tra chế độ (Thêm hay Sửa)
$isEdit = false;
$employee_code_param = trim($_GET['id'] ?? ($_GET['code'] ?? ''));

// Biến lưu dữ liệu Form
$emp = [
    'employee_code'    => '',
    'full_name'        => '',
    'gender'           => '',
    'job_level'        => '',
    'cost_center'      => '',
    'work_group'       => 'Khác',
    'work_shift'       => 'Ca 1',
    'hire_date'        => date('Y-m-d'),
    'resignation_date' => ''
];

$errorMessage = '';
$successMessage = '';

// Nếu có ID truyền lên -> Lấy dữ liệu cũ từ CSDL để Sửa
if (!empty($employee_code_param)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT employee_code, full_name, gender, job_level, cost_center, work_group, work_shift, hire_date, resignation_date FROM employees WHERE employee_code = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $employee_code_param);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['resignation_date'] === '0000-00-00') $row['resignation_date'] = '';
            $emp = array_merge($emp, $row);
        } else {
            $errorMessage = 'Không tìm thấy nhân viên với mã: ' . htmlspecialchars($employee_code_param);
            $isEdit = false;
        }
        $stmt->close();
    }
}

// Xử lý khi người dùng nhấn nút LƯU (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code       = trim($_POST['employee_code'] ?? '');
    $name       = trim($_POST['full_name'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $cost_center= trim($_POST['cost_center'] ?? '');
    $job_level  = trim($_POST['job_level'] ?? '');
    $work_group = trim($_POST['work_group'] ?? '');
    $work_shift = trim($_POST['work_shift'] ?? 'Ca 1');
    $hire_date  = !empty($_POST['hire_date']) ? trim($_POST['hire_date']) : NULL;
    $resig_date = !empty($_POST['resignation_date']) ? trim($_POST['resignation_date']) : NULL;

    if ($hire_date === '' || $hire_date === '0000-00-00') $hire_date = NULL;
    if ($resig_date === '' || $resig_date === '0000-00-00') $resig_date = NULL;

    if (empty($code) || empty($name)) {
        $errorMessage = 'Vui lòng điền đầy đủ Mã nhân viên và Họ tên!';
    } else {
        if ($isEdit) {
            // CẬP NHẬT DỮ LIỆU CŨ
            $sql = "UPDATE employees SET full_name=?, gender=?, job_level=?, cost_center=?, work_group=?, work_shift=?, hire_date=?, resignation_date=? WHERE employee_code=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssssss", $name, $gender, $job_level, $cost_center, $work_group, $work_shift, $hire_date, $resig_date, $code);
                if ($stmt->execute()) {
                    header("Location: index.php?mainpage=hrm&subpage=list&status=updated");
                    exit;
                } else {
                    $errorMessage = 'Lỗi cập nhật: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $errorMessage = 'Lỗi truy vấn: ' . $conn->error;
            }
        } else {
            // Kiểm tra trùng mã
            $chk = $conn->prepare("SELECT employee_code FROM employees WHERE employee_code = ? LIMIT 1");
            $chk->bind_param("s", $code);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                $errorMessage = "Mã nhân viên {$code} đã tồn tại trên hệ thống!";
                $chk->close();
            } else {
                $chk->close();
                // THÊM MỚI HÀNG DỮ LIỆU
                $sql = "INSERT INTO employees (employee_code, full_name, gender, job_level, cost_center, work_group, work_shift, hire_date, resignation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssssssss", $code, $name, $gender, $job_level, $cost_center, $work_group, $work_shift, $hire_date, $resig_date);
                    if ($stmt->execute()) {
                        header("Location: index.php?mainpage=hrm&subpage=list&status=created");
                        exit;
                    } else {
                        $errorMessage = 'Lỗi thêm mới: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errorMessage = 'Lỗi truy vấn: ' . $conn->error;
                }
            }
        }
    }
}

// Lấy danh sách các Cost Center và Work Group có sẵn
$costCenters = [];
$resCc = $conn->query("SELECT DISTINCT cost_center FROM employees WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center ASC");
if ($resCc) {
    while ($r = $resCc->fetch_assoc()) {
        $costCenters[] = $r['cost_center'];
    }
}
?>

<style>
/* Module-specific styles for Add/Edit Employee */
.emp-form-card {
  max-width: 860px;
  margin: 0 auto;
  width: 100%;
}

.emp-card-header {
  background-color: var(--dx-primary);
  color: #ffffff;
  padding: 14px 20px;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  border-top-left-radius: var(--dx-radius-md);
  border-top-right-radius: var(--dx-radius-md);
}

.emp-form-body {
  padding: 24px;
}

.emp-form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px 20px;
}

@media (max-width: 768px) {
  .emp-form-grid { grid-template-columns: 1fr; }
}

.emp-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.emp-form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--dx-text-main);
}

.emp-form-group label .required {
  color: var(--dx-danger);
}

.emp-form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--dx-border);
}

.field-tip {
  font-size: 11px;
  color: var(--dx-text-muted);
  line-height: 1.4;
}
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons"><?= $isEdit ? 'edit_note' : 'person_add'; ?></span>
                <?= $isEdit ? 'Chỉnh Sửa Thông Tin Nhân Viên' : 'Thêm Mới Nhân Viên'; ?>
            </h1>
            <p class="app-page-subtitle"><?= $isEdit ? 'Cập nhật hồ sơ, ngày nghỉ việc và phân bổ nhóm ca của nhân viên' : 'Đăng ký thông tin hồ sơ nhân sự mới vào hệ thống HRM'; ?></p>
        </div>
        <div class="app-page-actions">
            <a href="index.php?mainpage=hrm&subpage=list" class="app-btn app-btn-secondary">
                <span class="material-icons">arrow_back</span> Quay Lại Danh Sách
            </a>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
        <span class="material-icons me-2">error</span>
        <div><?= htmlspecialchars($errorMessage); ?></div>
    </div>
    <?php endif; ?>

    <!-- Card Form -->
    <div class="app-card emp-form-card">
        <div class="emp-card-header">
            <span class="material-icons fs-5">badge</span>
            <?= $isEdit ? 'Cập nhật thông tin nhân viên: ' . htmlspecialchars($emp['employee_code']) : 'Nhập thông tin nhân sự mới'; ?>
        </div>

        <form method="POST" class="emp-form-body">
            <div class="emp-form-grid">
                <!-- Mã nhân viên -->
                <div class="emp-form-group">
                    <label>Mã nhân viên <span class="required">*</span></label>
                    <input type="text" name="employee_code" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['employee_code']); ?>" 
                           maxlength="8"
                           placeholder="Ví dụ: 01920163" required <?= $isEdit ? 'readonly style="background-color: var(--dx-bg-subtle, #f1f5f9);"' : ''; ?>>
                    <div class="field-tip">Mã số định danh nhân sự duy nhất (tối đa 8 ký tự).</div>
                </div>

                <!-- Tên đầy đủ -->
                <div class="emp-form-group">
                    <label>Họ và Tên <span class="required">*</span></label>
                    <input type="text" name="full_name" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['full_name']); ?>" 
                           placeholder="Nhập họ và tên đầy đủ..." required>
                </div>

                <!-- Giới tính -->
                <div class="emp-form-group">
                    <label>Giới tính</label>
                    <select name="gender" class="app-form-control">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="Nam" <?= $emp['gender'] === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                        <option value="Nữ" <?= $emp['gender'] === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                    </select>
                </div>

                <!-- Vị trí / Cấp bậc -->
                <div class="emp-form-group">
                    <label>Cấp bậc / Vị trí</label>
                    <input type="text" name="job_level" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['job_level']); ?>" 
                           placeholder="Ví dụ: M1, S3, S4, W1...">
                </div>

                <!-- Phòng ban / Cost Center -->
                <div class="emp-form-group">
                    <label>Phòng ban (Cost Center)</label>
                    <input type="text" name="cost_center" list="costCenterList" class="app-form-control"
                           value="<?= htmlspecialchars($emp['cost_center']); ?>"
                           placeholder="Ví dụ: A00330, A00430, A00791...">
                    <datalist id="costCenterList">
                        <?php foreach ($costCenters as $cc): ?>
                            <option value="<?= htmlspecialchars($cc) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Nhóm làm việc -->
                <div class="emp-form-group">
                    <label>Nhóm làm việc</label>
                    <select name="work_group" class="app-form-control">
                        <option value="">-- Chọn nhóm làm việc --</option>
                        <option value="Đùn TU" <?= $emp['work_group'] === 'Đùn TU' ? 'selected' : ''; ?>>Đùn TU</option>
                        <option value="Đùn T" <?= $emp['work_group'] === 'Đùn T' ? 'selected' : ''; ?>>Đùn T</option>
                        <option value="Thiết bị" <?= $emp['work_group'] === 'Thiết bị' ? 'selected' : ''; ?>>Thiết bị</option>
                        <option value="Shotblast" <?= $emp['work_group'] === 'Shotblast' ? 'selected' : ''; ?>>Shotblast</option>
                        <option value="Nghiền nhựa" <?= $emp['work_group'] === 'Nghiền nhựa' ? 'selected' : ''; ?>>Nghiền nhựa</option>
                        <option value="Khác" <?= ($emp['work_group'] === 'Khác' || empty($emp['work_group'])) ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>

                <!-- Ca làm việc -->
                <div class="emp-form-group">
                    <label>Ca làm việc mặc định</label>
                    <select name="work_shift" class="app-form-control">
                        <option value="Ca 1" <?= $emp['work_shift'] === 'Ca 1' ? 'selected' : ''; ?>>Ca 1 (06:00 - 14:00)</option>
                        <option value="Ca 2" <?= $emp['work_shift'] === 'Ca 2' ? 'selected' : ''; ?>>Ca 2 (14:00 - 22:00)</option>
                        <option value="Ca HC" <?= $emp['work_shift'] === 'Ca HC' ? 'selected' : ''; ?>>Ca HC (Hành chính)</option>
                        <option value="Ca 3" <?= $emp['work_shift'] === 'Ca 3' ? 'selected' : ''; ?>>Ca 3 (22:00 - 06:00)</option>
                    </select>
                </div>

                <!-- Ngày vào công ty -->
                <div class="emp-form-group">
                    <label>Ngày vào công ty (Hire Date)</label>
                    <input type="date" name="hire_date" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['hire_date']); ?>">
                </div>

                <!-- Ngày nghỉ việc -->
                <div class="emp-form-group" style="grid-column: span 2;">
                    <label class="text-danger fw-bold d-flex align-items-center gap-1">
                        <span class="material-icons fs-6">person_off</span>
                        Ngày nghỉ việc (Resignation Date)
                    </label>
                    <input type="date" name="resignation_date" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['resignation_date']); ?>"
                           style="<?= !empty($emp['resignation_date']) ? 'border-color: #f87171; background-color: #fff1f2;' : '' ?>">
                    <div class="field-tip text-danger">
                        * Chú ý: Khi thiết lập <strong>Ngày nghỉ việc</strong>, hệ thống sẽ tự động chuyển nhân sự sang trạng thái <em>"Đã nghỉ việc"</em> và <strong>tô xám toàn bộ</strong> thông tin của nhân viên trên:
                        <strong>Tổng Hợp Phép HRM & 12 Tháng</strong> và <strong>Kiểm Soát Giới Hạn Tăng Ca 200 Giờ/Năm</strong>.
                        Để trống nếu nhân viên vẫn đang làm việc.
                    </div>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="emp-form-actions">
                <button type="submit" class="app-btn app-btn-primary">
                    <span class="material-icons">save</span> 
                    <?= $isEdit ? 'Lưu Cập Nhật' : 'Lưu Nhân Viên'; ?>
                </button>
                <a href="index.php?mainpage=hrm&subpage=list" class="app-btn app-btn-secondary">
                    <span class="material-icons">cancel</span> Hủy
                </a>
            </div>
        </form>
    </div>
</div>