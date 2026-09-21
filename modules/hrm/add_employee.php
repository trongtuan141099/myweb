<?php
// modules/hrm/add_employee.php
require_once __DIR__ . '/../../core/check_permission.php';

// Tự động kiểm tra chế độ (Thêm hay Sửa)
$isEdit = false;
$employee_code_param = $_GET['id'] ?? '';

// Biến lưu dữ liệu Form
$emp = [
    'employee_code' => '',
    'full_name'     => '',
    'email'         => '',
    'phone'         => '',
    'dob'           => '',
    'gender'        => '',
    'cost_center'   => '',
    'job_level'     => '',
    'hire_date'     => '',
    'salary'        => '',
    'status'        => 'Active'
];

// Nếu có ID truyền lên -> Lấy dữ liệu cũ từ CSDL để Sửa
if (!empty($employee_code_param)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_code = ?");
    if ($stmt) {
        $stmt->bind_param("s", $employee_code_param);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $emp = array_merge($emp, $row);
        }
        $stmt->close();
    }
}

// Xử lý khi người dùng nhấn nút LƯU (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code       = trim($_POST['employee_code'] ?? '');
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $dob        = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $gender     = $_POST['gender'] ?? '';
    $cost_center= $_POST['cost_center'] ?? '';
    $job_level  = trim($_POST['job_level'] ?? '');
    $hire_date  = !empty($_POST['hire_date']) ? $_POST['hire_date'] : NULL;
    $salary     = !empty($_POST['salary']) ? floatval(str_replace(['.', ','], '', $_POST['salary'])) : 0;
    $status     = $_POST['status'] ?? 'Active';

    if ($isEdit) {
        // CẬP NHẬT DỮ LIỆU CŨ
        $sql = "UPDATE employees SET full_name=?, email=?, phone=?, dob=?, gender=?, cost_center=?, job_level=?, hire_date=?, salary=?, status=? WHERE employee_code=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssdss", $name, $email, $phone, $dob, $gender, $cost_center, $job_level, $hire_date, $salary, $status, $code);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // THÊM MỚI HÀNG DỮ LIỆU
        $sql = "INSERT INTO employees (employee_code, full_name, email, phone, dob, gender, cost_center, job_level, hire_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssdss", $code, $name, $email, $phone, $dob, $gender, $cost_center, $job_level, $hire_date, $salary, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Sau khi lưu xong -> Điều hướng về trang Danh sách nhân viên
    echo "<script>window.location.href='index.php?mainpage=hrm&subpage=list';</script>";
    exit;
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
  font-size: 14px;
  font-weight: 600;
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
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div class="app-page-title">
            <span class="material-icons text-primary"><?= $isEdit ? 'edit_note' : 'person_add'; ?></span>
            <h1><?= $isEdit ? 'CHỈNH SỬA THÔNG TIN NHÂN VIÊN' : 'THÊM MỚI NHÂN VIÊN'; ?></h1>
        </div>
        <div class="app-page-actions">
            <a href="index.php?mainpage=hrm&subpage=list" class="app-btn app-btn-secondary">
                <span class="material-icons">arrow_back</span> Quay Lại Danh Sách
            </a>
        </div>
    </div>

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
                           placeholder="Ví dụ: EMP001" required <?= $isEdit ? 'readonly' : ''; ?>>
                </div>

                <!-- Tên đầy đủ -->
                <div class="emp-form-group">
                    <label>Tên đầy đủ <span class="required">*</span></label>
                    <input type="text" name="full_name" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['full_name']); ?>" 
                           placeholder="Nhập họ và tên..." required>
                </div>

                <!-- Email -->
                <div class="emp-form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['email']); ?>" 
                           placeholder="nguyenvana@email.com" required>
                </div>

                <!-- Điện thoại -->
                <div class="emp-form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['phone']); ?>" 
                           placeholder="0901234567">
                </div>

                <!-- Ngày sinh -->
                <div class="emp-form-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['dob']); ?>">
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

                <!-- Phòng ban / Cost Center -->
                <div class="emp-form-group">
                    <label>Phòng ban (Cost Center)</label>
                    <select name="cost_center" class="app-form-control">
                        <option value="">-- Chọn phòng ban --</option>
                        <option value="IT" <?= $emp['cost_center'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                        <option value="HR" <?= $emp['cost_center'] === 'HR' ? 'selected' : ''; ?>>HR</option>
                        <option value="Sales" <?= $emp['cost_center'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                        <option value="Finance" <?= $emp['cost_center'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                        <option value="A00330" <?= $emp['cost_center'] === 'A00330' ? 'selected' : ''; ?>>A00330</option>
                    </select>
                </div>

                <!-- Vị trí / Cấp bậc -->
                <div class="emp-form-group">
                    <label>Vị trí / Cấp bậc</label>
                    <input type="text" name="job_level" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['job_level']); ?>" 
                           placeholder="vd: Kỹ sư phần mềm">
                </div>

                <!-- Ngày tuyển -->
                <div class="emp-form-group">
                    <label>Ngày tuyển</label>
                    <input type="date" name="hire_date" class="app-form-control" 
                           value="<?= htmlspecialchars($emp['hire_date']); ?>">
                </div>

                <!-- Lương cơ bản -->
                <div class="emp-form-group">
                    <label>Lương cơ bản (VND)</label>
                    <input type="text" name="salary" class="app-form-control" 
                           value="<?= $emp['salary'] ? number_format($emp['salary'], 0, ',', '.') : ''; ?>" 
                           placeholder="25.000.000">
                </div>

                <!-- Trạng thái -->
                <div class="emp-form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="app-form-control">
                        <option value="Active" <?= $emp['status'] === 'Active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="Inactive" <?= $emp['status'] === 'Inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                        <option value="On Leave" <?= $emp['status'] === 'On Leave' ? 'selected' : ''; ?>>Đang nghỉ</option>
                    </select>
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